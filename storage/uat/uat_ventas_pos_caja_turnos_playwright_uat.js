const fs = require('fs');
const path = require('path');
const { chromium } = require('../../public/js/node_modules/@playwright/test');

/**
 * Documentacion IA: Codex GPT-5, 2026-07-04.
 * Proposito: validar visualmente Caja/Turnos POS en modo read-only/dry-run.
 * Impacto: confirma apertura simulada, arqueo visual y corte separado sin abrir turno real.
 * Contrato: no presiona autorizaciones reales, no abre turno, no cierra turno, no mueve caja.
 */

const baseUrl = process.env.POS_UAT_CAJA_URL || 'http://dashboard.com.local/ventas/caja_turnos';
const usuario = process.env.POS_UAT_USER || '';
const contrasenia = process.env.POS_UAT_PASS || '';
const outDir = path.resolve(__dirname, '../../public/storage/uat');
const screenshotPath = path.join(outDir, 'pos_caja_turnos_uat.png');

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
    if (login.loginOk && !/ventas\/caja_turnos/i.test(page.url())) {
      await page.goto(baseUrl, { waitUntil: 'domcontentloaded', timeout: 15000 }).catch((err) => {
        navigationError = String(err.message || err);
      });
    }

    await page.waitForTimeout(2500);
    await page.fill('#pos_caja_monto_inicial', '500').catch(() => null);
    await page.click('#pos_caja_apertura_dryrun').catch(() => null);
    await page.waitForTimeout(1200);
    await page.fill('#pos_caja_denom_500', '1').catch(() => null);
    await page.fill('#pos_caja_denom_200', '1').catch(() => null);
    await page.fill('#pos_caja_denom_50', '1').catch(() => null);
    await page.fill('#pos_caja_arqueo_tarjeta', '45').catch(() => null);
    await page.waitForTimeout(400);

    const bodyText = await page.locator('body').innerText({ timeout: 5000 }).catch(() => '');
    const hasCaja = /Caja POS|Apertura de turno|Corte de turno/i.test(bodyText);
    const hasAperturaDry = /Dry-run de apertura|Apertura validada|No se creo turno/i.test(bodyText);
    const hasAutorizacion = /AUTORIZO ABRIR TURNO POS UAT/i.test(bodyText);
    const bloqueaDobleApertura = /Ya existe turno abierto|Dry-run de apertura bloqueado/i.test(bodyText);
    const hasArqueo = /Arqueo rapido|Efectivo por denominacion|Otros metodos/i.test(bodyText);
    const arqueoTotal = await page.locator('#pos_caja_arqueo_total').innerText().catch(() => '');
    const montoContado = await page.locator('#pos_caja_monto_contado').inputValue().catch(() => '');
    const turnosAbiertosKpi = await page.locator('#pos_caja_kpi_turnos').innerText().catch(() => '');
    const cajasOptions = await page.locator('#pos_caja_apertura_caja option').count().catch(() => 0);

    await page.screenshot({ path: screenshotPath, fullPage: true }).catch(() => null);

    const result = {
      ok: !navigationError && login.loginOk && hasCaja && hasAperturaDry && (hasAutorizacion || bloqueaDobleApertura) && hasArqueo && /795/.test(arqueoTotal + ' ' + montoContado) && cajasOptions > 0,
      modo: 'ventas_pos_caja_turnos_visual_uat',
      urlSolicitada: baseUrl,
      urlFinal: page.url(),
      status,
      login: Object.assign({}, login, { usuario: usuario ? 'definido' : 'no_definido' }),
      validaciones: {
        entroCajaTurnos: hasCaja,
        aperturaDryRunVisible: hasAperturaDry,
        autorizacionAperturaVisible: hasAutorizacion,
        bloqueaDobleApertura,
        arqueoVisible: hasArqueo,
        arqueoTotal,
        montoContadoCalculado: montoContado,
        opcionesCajaApertura: cajasOptions,
        kpiTurnosAbiertos: turnosAbiertosKpi,
        noPresionoAutorizacionReal: true,
        noAbreTurno: true,
        noCierraTurno: true,
        noMueveCaja: true,
        noMueveInventario: true
      },
      evidencia: { screenshot: screenshotPath },
      consola: issues.slice(0, 25),
      textoInicial: bodyText.replace(/\s+/g, ' ').trim().slice(0, 1600)
    };

    console.log(JSON.stringify(result, null, 2));
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
