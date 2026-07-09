const fs = require('fs');
const path = require('path');
const { chromium } = require('../../public/js/node_modules/@playwright/test');

/**
 * Documentacion IA: Codex GPT-5, 2026-07-03.
 * Proposito: validar visualmente reportes POS y seguimiento de diferencias con Playwright.
 * Impacto: confirma filtros/estado de revision sin escribir BD.
 * Contrato: solo navega y consulta; no ejecuta resoluciones ni movimientos.
 */

const baseUrl = process.env.POS_UAT_REPORTES_URL || 'http://dashboard.com.local/ventas/reportes';
const loginUrl = process.env.POS_UAT_LOGIN_URL || 'http://dashboard.com.local/autenticacion/login';
const usuario = process.env.POS_UAT_USER || '';
const contrasenia = process.env.POS_UAT_PASS || '';
const outDir = path.resolve(__dirname, '../../public/storage/uat');
const screenshotPath = path.join(outDir, 'pos_reportes_diferencias_uat.png');

async function loginSiHaceFalta(page) {
  if (!/autenticacion\/login/i.test(page.url())) {
    return { intentoLogin: false, loginOk: true };
  }
  if (!usuario || !contrasenia) {
    return { intentoLogin: false, loginOk: false, motivo: 'credenciales_env_no_definidas' };
  }
  await page.fill('#celular', usuario);
  await page.fill('#contrasenia', contrasenia);
  await Promise.all([
    page.waitForLoadState('networkidle').catch(() => null),
    page.click('#kt_sign_in_submit')
  ]);
  await page.waitForTimeout(1500);
  if (/autenticacion\/login/i.test(page.url())) {
    return { intentoLogin: true, loginOk: false, motivo: 'permanece_en_login' };
  }
  return { intentoLogin: true, loginOk: true };
}

(async () => {
  fs.mkdirSync(outDir, { recursive: true });
  const browser = await chromium.launch({ headless: true });
  const page = await browser.newPage({
    viewport: { width: 1440, height: 950 },
    deviceScaleFactor: 1,
  });

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
    const response = await page.goto(baseUrl, { waitUntil: 'networkidle', timeout: 30000 });
    status = response ? response.status() : null;
  } catch (err) {
    navigationError = String(err.message || err);
  }

  const login = await loginSiHaceFalta(page);
  if (login.loginOk && !/ventas\/reportes/i.test(page.url())) {
    await page.goto(baseUrl, { waitUntil: 'networkidle', timeout: 30000 }).catch((err) => {
      navigationError = String(err.message || err);
    });
  }

  const estadoExiste = await page.locator('#pos_rep_estado_revision').count().catch(() => 0);
  if (estadoExiste) {
    await page.fill('#pos_rep_desde', '2026-07-03').catch(() => null);
    await page.fill('#pos_rep_hasta', '2026-07-04').catch(() => null);
    await page.selectOption('#pos_rep_estado_revision', 'todos').catch(() => null);
    await page.click('#pos_rep_consultar').catch(() => null);
    await page.waitForTimeout(2500);
  }

  const bodyText = await page.locator('body').innerText({ timeout: 5000 }).catch(() => '');
  const estadoValue = estadoExiste ? await page.locator('#pos_rep_estado_revision').inputValue().catch(() => '') : '';
  const rowsSeguimiento = await page.locator('#pos_reportes_diferencias tr').count().catch(() => 0);
  const hasReportes = /Reportes POS|Seguimiento de diferencias|Diferencias por empleado/i.test(bodyText);
  const hasExplicada = /explicada|DIF-CAJ-20260703-000001|TUR-20260703-002-002/i.test(bodyText);
  const hasPendingEmpty = /Sin diferencias pendientes|Sin diferencias en el rango/i.test(bodyText);
  const hasResolverButton = await page.locator('.pos-dif-resolver').count().catch(() => 0);

  await page.screenshot({ path: screenshotPath, fullPage: true }).catch(() => null);

  const result = {
    ok: !navigationError && login.loginOk && hasReportes && hasExplicada && estadoValue === 'todos',
    modo: 'ventas_pos_reportes_visual_uat',
    urlSolicitada: baseUrl,
    urlFinal: page.url(),
    status,
    login: Object.assign({}, login, { usuario: usuario ? 'definido' : 'no_definido' }),
    validaciones: {
      entroReportes: hasReportes,
      filtroEstadoExiste: Boolean(estadoExiste),
      estadoSeleccionado: estadoValue,
      muestraDiferenciaExplicada: hasExplicada,
      filasSeguimiento: rowsSeguimiento,
      botonesResolverVisibles: hasResolverButton,
      mensajeSinDiferencias: hasPendingEmpty,
      noEjecutaResolucion: true,
      noMueveCaja: true,
      noMueveInventario: true
    },
    evidencia: {
      screenshot: screenshotPath
    },
    consola: issues.slice(0, 25),
    textoInicial: bodyText.replace(/\s+/g, ' ').trim().slice(0, 1400)
  };

  console.log(JSON.stringify(result, null, 2));
  await browser.close();
})().catch((err) => {
  console.error(JSON.stringify({ ok: false, error: String(err.message || err) }, null, 2));
  process.exitCode = 1;
});
