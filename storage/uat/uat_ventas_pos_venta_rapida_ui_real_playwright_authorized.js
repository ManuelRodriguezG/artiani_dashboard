const fs = require('fs');
const path = require('path');
const { chromium } = require('../../public/js/node_modules/@playwright/test');

/**
 * Documentacion IA: Codex GPT-5, 2026-07-24.
 * Proposito: ejecutar UAT real UI de venta rapida controlada POS desde navegador.
 * Impacto: usa la pantalla /ventas/pos para capturar venta rapida, agregar pago y cobrar real.
 * Contrato: requiere token operativo, turno abierto y credenciales por variables de entorno.
 */

const token = process.env.POS_UAT_TOKEN || '';
const baseUrl = process.env.POS_UAT_POS_URL || 'http://panel.com.local/ventas/pos';
const usuario = process.env.POS_UAT_USER || '';
const contrasenia = process.env.POS_UAT_PASS || '';
const descripcion = process.env.POS_UAT_VR_DESCRIPCION || 'Producto UAT UI por clasificar';
const cantidad = process.env.POS_UAT_VR_CANTIDAD || '1';
const precio = process.env.POS_UAT_VR_PRECIO || '100';
const motivo = process.env.POS_UAT_VR_MOTIVO || 'producto_no_registrado';
const outDir = path.resolve(__dirname, '../../public/storage/uat');
const screenshotPath = path.join(outDir, 'pos_venta_rapida_ui_real_uat.png');

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
  await page.waitForLoadState('domcontentloaded', { timeout: 10000 }).catch(() => null);
  await page.waitForTimeout(1800);
  return {
    intentoLogin: true,
    loginOk: !/autenticacion\/login/i.test(page.url()),
    motivo: /autenticacion\/login/i.test(page.url()) ? 'permanece_en_login' : ''
  };
}

async function aceptarSwal(page) {
  const confirm = page.locator('.swal2-confirm').first();
  if (await confirm.count().catch(() => 0)) {
    await confirm.click();
    await page.waitForTimeout(1200);
  }
}

(async () => {
  fs.mkdirSync(outDir, { recursive: true });
  if (token !== 'VENTAS_POS_VENTA_RAPIDA_UI_REAL') {
    console.log(JSON.stringify({
      ok: false,
      modo: 'bloqueado',
      mensaje: 'Token operativo invalido para UAT UI real venta rapida POS.'
    }, null, 2));
    process.exitCode = 1;
    return;
  }

  const browser = await chromium.launch({ headless: true, timeout: 20000 });
  let page = null;
  const consola = [];

  try {
    page = await browser.newPage({ viewport: { width: 1440, height: 980 }, deviceScaleFactor: 1 });
    page.setDefaultTimeout(12000);
    page.setDefaultNavigationTimeout(20000);
    page.on('console', (msg) => {
      if (['error', 'warning'].includes(msg.type())) {
        consola.push({ type: msg.type(), text: msg.text().slice(0, 600) });
      }
    });
    page.on('pageerror', (err) => consola.push({ type: 'pageerror', text: String(err.message || err).slice(0, 600) }));

    const response = await page.goto(baseUrl, { waitUntil: 'domcontentloaded', timeout: 20000 }).catch((err) => {
      consola.push({ type: 'navigation', text: String(err.message || err).slice(0, 600) });
      return null;
    });
    const status = response ? response.status() : null;
    const login = await loginSiHaceFalta(page);
    if (login.loginOk && !/ventas\/pos/i.test(page.url())) {
      await page.goto(baseUrl, { waitUntil: 'domcontentloaded', timeout: 20000 });
    }
    await page.waitForLoadState('networkidle', { timeout: 12000 }).catch(() => null);
    await page.waitForTimeout(1800);

    await page.locator('#pos_venta_rapida_btn').click();
    await page.fill('#pos_vr_descripcion', descripcion);
    await page.fill('#pos_vr_cantidad', cantidad);
    await page.fill('#pos_vr_precio', precio);
    await page.selectOption('#pos_vr_motivo', motivo).catch(async () => {
      await page.selectOption('#pos_vr_motivo', { index: 0 });
    });
    await page.fill('#pos_vr_observaciones', 'UAT UI venta rapida controlada');
    await page.click('#pos_vr_validar');
    await page.waitForFunction(() => {
      const btn = document.querySelector('#pos_vr_agregar');
      return btn && !btn.disabled;
    }, null, { timeout: 12000 });
    await page.click('#pos_vr_agregar');
    await page.waitForTimeout(900);

    await page.locator('[data-pos-pago-rapido="efectivo"]').click();
    await page.waitForTimeout(700);
    await page.click('#pos_cobrar_real');
    await aceptarSwal(page);
    await page.waitForTimeout(3500);

    const validacionText = await page.locator('#pos_validacion').innerText().catch(() => '');
    const carritoText = await page.locator('#pos_carrito').innerText().catch(() => '');
    const pagosText = await page.locator('#pos_pagos').innerText().catch(() => '');
    const bodyText = await page.locator('body').innerText().catch(() => '');
    await page.screenshot({ path: screenshotPath, fullPage: true }).catch(() => null);
    const screenshotExiste = fs.existsSync(screenshotPath);

    const ok = login.loginOk
      && /ventas\/pos/i.test(page.url())
      && /Venta POS confirmada|confirmada/i.test(validacionText)
      && /Producto por clasificar|VENTA-RAPIDA|Venta rapida/i.test(bodyText);

    console.log(JSON.stringify({
      ok,
      modo: 'ventas_pos_venta_rapida_ui_real_playwright',
      urlSolicitada: baseUrl,
      urlFinal: page.url(),
      status,
      login: Object.assign({}, login, { usuario: usuario ? 'definido' : 'no_definido' }),
      entrada: { descripcion, cantidad, precio, motivo },
      evidencia: { screenshot: screenshotExiste ? screenshotPath : null, screenshot_generado: screenshotExiste },
      textos: {
        validacion: validacionText.replace(/\s+/g, ' ').trim(),
        carrito: carritoText.replace(/\s+/g, ' ').trim().slice(0, 700),
        pagos: pagosText.replace(/\s+/g, ' ').trim().slice(0, 700)
      },
      consola: consola.slice(0, 25)
    }, null, 2));
    if (!ok) {
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
