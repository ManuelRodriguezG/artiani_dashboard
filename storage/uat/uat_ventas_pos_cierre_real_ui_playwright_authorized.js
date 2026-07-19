const fs = require('fs');
const path = require('path');
const { chromium } = require('../../public/js/node_modules/@playwright/test');

/**
 * Documentacion IA: Codex GPT-5, 2026-07-04.
 * Proposito: ejecutar UAT real de cierre de turno desde Caja/Turnos UI solo con token explicito.
 * Impacto: cierra un turno POS real desde navegador, usando arqueo y confirmacion visual.
 * Contrato: BLOQUEADO por defecto; requiere POS_UAT_AUTORIZAR=VENTAS_POS_CAJA_CIERRE_UI_REAL.
 */

const token = process.env.POS_UAT_AUTORIZAR || '';
const baseUrl = process.env.POS_UAT_CAJA_URL || 'http://panel.com.local/ventas/caja_turnos';
const usuario = process.env.POS_UAT_USER || '';
const contrasenia = process.env.POS_UAT_PASS || '';
const contado = Number(process.env.POS_UAT_MONTO_CONTADO || 500);
const outDir = path.resolve(__dirname, '../../public/storage/uat');
const screenshotPath = path.join(outDir, 'pos_caja_turnos_cierre_real_ui.png');

function fail(payload, code = 1) {
  console.log(JSON.stringify(payload, null, 2));
  process.exitCode = code;
}

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
  await page.waitForTimeout(1200);
  return { intentoLogin: true, loginOk: !/autenticacion\/login/i.test(page.url()) };
}

function piezasParaMonto(monto) {
  const denominaciones = [1000, 500, 200, 100, 50, 20, 10, 5, 2, 1, 0.5];
  let restante = Math.round(monto * 100);
  return denominaciones.map((denominacion) => {
    const centavos = Math.round(denominacion * 100);
    const piezas = Math.floor(restante / centavos);
    restante -= piezas * centavos;
    return { denominacion, piezas };
  });
}

(async () => {
  if (token !== 'VENTAS_POS_CAJA_CIERRE_UI_REAL') {
    fail({
      ok: false,
      modo: 'bloqueado',
      mensaje: 'No se ejecuto cierre UI real. Falta POS_UAT_AUTORIZAR=VENTAS_POS_CAJA_CIERRE_UI_REAL.',
      contrato: {
        no_abre_turno: true,
        no_cierra_turno: true,
        no_mueve_caja: true,
        no_mueve_inventario: true
      }
    }, 0);
    return;
  }

  fs.mkdirSync(outDir, { recursive: true });
  const browser = await chromium.launch({ headless: true, timeout: 15000 });
  let page = null;
  try {
    page = await browser.newPage({ viewport: { width: 1440, height: 950 }, deviceScaleFactor: 1 });
    page.setDefaultTimeout(10000);
    page.setDefaultNavigationTimeout(15000);

    const issues = [];
    page.on('console', (msg) => {
      if (['error', 'warning'].includes(msg.type())) {
        issues.push({ type: msg.type(), text: msg.text().slice(0, 500) });
      }
    });
    page.on('pageerror', (err) => issues.push({ type: 'pageerror', text: String(err.message || err).slice(0, 500) }));

    await page.goto(baseUrl, { waitUntil: 'domcontentloaded', timeout: 15000 });
    const login = await loginSiHaceFalta(page);
    if (!login.loginOk) {
      throw new Error('Login no disponible para UAT UI real');
    }
    if (!/ventas\/caja_turnos/i.test(page.url())) {
      await page.goto(baseUrl, { waitUntil: 'domcontentloaded', timeout: 15000 });
    }

    await page.waitForTimeout(2500);
    const turnosAntes = await page.locator('#pos_caja_kpi_turnos').innerText().catch(() => '');
    if (!/[1-9]/.test(turnosAntes)) {
      throw new Error('No hay turno abierto visible para cierre UI real');
    }

    for (const item of piezasParaMonto(contado)) {
      const id = '#pos_caja_denom_' + String(item.denominacion).replace('.', '_');
      await page.fill(id, String(item.piezas)).catch(() => null);
    }
    await page.fill('#pos_caja_arqueo_tarjeta', '0').catch(() => null);
    await page.fill('#pos_caja_arqueo_transferencia', '0').catch(() => null);
    await page.fill('#pos_caja_arqueo_vales', '0').catch(() => null);
    await page.click('#pos_caja_corte_dryrun');
    await page.waitForTimeout(1200);
    await page.fill('#pos_caja_cierre_confirmacion', 'CERRAR TURNO');
    await page.click('#pos_caja_cierre_real');
    await page.waitForTimeout(2500);

    const bodyText = await page.locator('body').innerText({ timeout: 5000 }).catch(() => '');
    const turnosDespues = await page.locator('#pos_caja_kpi_turnos').innerText().catch(() => '');
    await page.screenshot({ path: screenshotPath, fullPage: true }).catch(() => null);

    console.log(JSON.stringify({
      ok: /Turno POS cerrado correctamente|Cierre procesado/i.test(bodyText) && String(turnosDespues).trim() === '0',
      modo: 'cierre_real_ui_playwright_authorized',
      url: page.url(),
      contado,
      turnosAntes,
      turnosDespues,
      evidencia: { screenshot: screenshotPath },
      consola: issues.slice(0, 25),
      contrato: {
        cierre_real_autorizado: true,
        no_crea_venta: true,
        no_mueve_inventario: true
      }
    }, null, 2));
  } finally {
    if (page) {
      await page.close().catch(() => null);
    }
    await browser.close().catch(() => null);
  }
})().catch((err) => {
  fail({ ok: false, modo: 'error', mensaje: String(err.message || err) });
});
