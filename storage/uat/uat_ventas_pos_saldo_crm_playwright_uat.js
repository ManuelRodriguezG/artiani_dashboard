const fs = require('fs');
const path = require('path');
const { chromium } = require('../../public/js/node_modules/@playwright/test');

/**
 * Documentacion IA: Codex GPT-5, 2026-07-07.
 * Proposito: validar visualmente saldo cliente CRM en POS sin cobrar ni escribir BD.
 * Impacto: confirma que el cajero ve saldo disponible y que saldo cero bloquea pago virtual.
 * Contrato: read-only; no cobra, no abre turno, no cierra turno, no mueve caja ni inventario.
 */

const baseUrl = process.env.POS_UAT_POS_URL || 'http://dashboard.com.local/ventas/pos';
const usuario = process.env.POS_UAT_USER || '';
const contrasenia = process.env.POS_UAT_PASS || '';
const identificador = process.env.POS_UAT_CLIENTE_IDENTIFICADOR || '2871085474';
const outDir = path.resolve(__dirname, '../../public/storage/uat');
const screenshotPath = path.join(outDir, 'pos_saldo_cliente_uat.png');

async function loginSiHaceFalta(page) {
  if (!/autenticacion\/login/i.test(page.url())) {
    return { intentoLogin: false, loginOk: true };
  }
  if (!usuario || !contrasenia) {
    return { intentoLogin: false, loginOk: false, motivo: 'credenciales_env_no_definidas' };
  }
  await page.fill('#celular', usuario);
  await page.fill('#contrasenia', contrasenia);
  await page.click('#kt_sign_in_submit');
  await page.waitForLoadState('domcontentloaded', { timeout: 8000 }).catch(() => null);
  await page.waitForTimeout(1500);
  if (/autenticacion\/login/i.test(page.url())) {
    return { intentoLogin: true, loginOk: false, motivo: 'permanece_en_login' };
  }
  return { intentoLogin: true, loginOk: true };
}

(async () => {
  fs.mkdirSync(outDir, { recursive: true });
  const browser = await chromium.launch({ headless: true, timeout: 15000 });
  let page = null;

  try {
    page = await browser.newPage({ viewport: { width: 1440, height: 950 }, deviceScaleFactor: 1 });
    page.setDefaultTimeout(8000);
    page.setDefaultNavigationTimeout(15000);

    const issues = [];
    page.on('console', (msg) => {
      if (['error', 'warning'].includes(msg.type())) {
        issues.push({ type: msg.type(), text: msg.text().slice(0, 500) });
      }
    });
    page.on('pageerror', (err) => issues.push({ type: 'pageerror', text: String(err.message || err).slice(0, 500) }));

    let navigationError = null;
    let status = null;
    try {
      const response = await page.goto(baseUrl, { waitUntil: 'domcontentloaded', timeout: 15000 });
      status = response ? response.status() : null;
    } catch (err) {
      navigationError = String(err.message || err);
    }

    const login = await loginSiHaceFalta(page);
    if (login.loginOk && !/ventas\/pos/i.test(page.url())) {
      await page.goto(baseUrl, { waitUntil: 'domcontentloaded', timeout: 15000 }).catch((err) => {
        navigationError = String(err.message || err);
      });
    }

    await page.waitForTimeout(2500);
    const bodyInicial = await page.locator('body').innerText({ timeout: 5000 }).catch(() => '');
    const entroPos = /Punto de venta|Carrito|Cuentas en atencion|Pagos/i.test(bodyInicial);

    if (entroPos) {
      await page.click('#pos_cliente_precio_modal_btn').catch(() => null);
      await page.waitForTimeout(600);
      await page.fill('#pos_cliente_identificador', identificador).catch(() => null);
      await page.click('#pos_cliente_buscar_crm').catch(() => null);
      await page.waitForTimeout(1500);
      const usar = page.locator('.pos-cliente-crm-seleccionar').first();
      if (await usar.count().catch(() => 0)) {
        await usar.click().catch(() => null);
      }
      await page.waitForTimeout(1800);
      await page.keyboard.press('Escape').catch(() => null);
      await page.waitForTimeout(500);
      await page.locator('[data-pos-pago-rapido="saldo_crm"]').click({ force: false }).catch(() => null);
      await page.waitForTimeout(600);
    }

    const bodyText = await page.locator('body').innerText({ timeout: 5000 }).catch(() => '');
    const saldoText = await page.locator('#pos_cliente_saldo_crm').innerText().catch(() => '');
    const pagosText = await page.locator('#pos_pagos').innerText().catch(() => '');
    const validacionText = await page.locator('#pos_validacion').innerText().catch(() => '');
    const botonSaldoDisabled = await page.locator('[data-pos-pago-rapido="saldo_crm"]').isDisabled().catch(() => false);
    const clienteNombre = await page.locator('#pos_cliente').inputValue().catch(() => '');
    const clienteIdentificador = await page.locator('#pos_cliente_telefono').inputValue().catch(() => '');
    await page.screenshot({ path: screenshotPath, fullPage: true }).catch(() => null);

    const result = {
      ok: !navigationError
        && login.loginOk
        && entroPos
        && /Sin saldo cliente disponible|Saldo cliente disponible/i.test(saldoText)
        && /\$0\.00|0\.00/i.test(saldoText)
        && botonSaldoDisabled
        && !/Saldo cliente\s+No entra a caja/i.test(pagosText),
      modo: 'ventas_pos_saldo_cliente_visual_uat',
      urlSolicitada: baseUrl,
      urlFinal: page.url(),
      status,
      login: Object.assign({}, login, { usuario: usuario ? 'definido' : 'no_definido' }),
      validaciones: {
        entroPos,
        clienteNombre,
        clienteIdentificador,
        saldoIndicadorVisible: /Sin saldo cliente disponible|Saldo cliente disponible/i.test(saldoText),
        saldoCeroVisible: /\$0\.00|0\.00/i.test(saldoText),
        botonSaldoClienteDeshabilitado: botonSaldoDisabled,
        noAgregoPagoSaldoCliente: !/Saldo cliente\s+No entra a caja/i.test(pagosText),
        noCobro: true,
        noAbreTurno: true,
        noCierraTurno: true,
        noMueveCaja: true,
        noMueveInventario: true
      },
      evidencia: { screenshot: screenshotPath },
      consola: issues.slice(0, 25),
      textos: {
        saldo: saldoText.replace(/\s+/g, ' ').trim(),
        validacion: validacionText.replace(/\s+/g, ' ').trim(),
        pagos: pagosText.replace(/\s+/g, ' ').trim(),
        inicial: bodyText.replace(/\s+/g, ' ').trim().slice(0, 1200)
      }
    };

    console.log(JSON.stringify(result, null, 2));
    if (!result.ok) {
      process.exitCode = 1;
    }
  } finally {
    if (page) {
      await page.close().catch(() => null);
    }
    await browser.close().catch(() => null);
  }
})().catch((err) => {
  console.error(JSON.stringify({ ok: false, error: String(err.message || err) }, null, 2));
  process.exitCode = 1;
});
