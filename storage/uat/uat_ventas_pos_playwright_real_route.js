const fs = require('fs');
const path = require('path');
const { chromium } = require('../../public/js/node_modules/@playwright/test');

const baseUrl = process.env.POS_UAT_URL || 'http://dashboard.com.local/ventas/pos';
const outDir = path.resolve(__dirname, '../../public/storage/uat');
const screenshotPath = path.join(outDir, 'pos_playwright_real_route.png');

(async () => {
  fs.mkdirSync(outDir, { recursive: true });

  const browser = await chromium.launch({ headless: true });
  const page = await browser.newPage({
    viewport: { width: 1440, height: 900 },
    deviceScaleFactor: 1,
  });

  const consoleIssues = [];
  page.on('console', (msg) => {
    if (['error', 'warning'].includes(msg.type())) {
      consoleIssues.push({ type: msg.type(), text: msg.text().slice(0, 500) });
    }
  });

  page.on('pageerror', (err) => {
    consoleIssues.push({ type: 'pageerror', text: String(err.message || err).slice(0, 500) });
  });

  let responseStatus = null;
  let navigationError = null;

  try {
    const response = await page.goto(baseUrl, { waitUntil: 'networkidle', timeout: 30000 });
    responseStatus = response ? response.status() : null;
  } catch (err) {
    navigationError = String(err.message || err);
  }

  const currentUrl = page.url();
  const title = await page.title().catch(() => '');
  const bodyText = await page.locator('body').innerText({ timeout: 5000 }).catch(() => '');
  await page.screenshot({ path: screenshotPath, fullPage: true }).catch(() => null);

  const hasPosShell = /POS|Punto de venta|Caja|Turno|Carrito|Total/i.test(bodyText);
  const hasLogin = /login|iniciar sesi[oó]n|contrase[nñ]a|usuario|celular/i.test(bodyText);
  const hasPermissionIssue = /permiso|no autorizado|403|acceso denegado/i.test(bodyText);

  const result = {
    ok: !navigationError && hasPosShell && !hasLogin && !hasPermissionIssue,
    urlSolicitada: baseUrl,
    urlFinal: currentUrl,
    status: responseStatus,
    title,
    diagnostico: {
      entroAlPos: hasPosShell && !hasLogin && !hasPermissionIssue,
      requiereLogin: hasLogin,
      posiblePermiso: hasPermissionIssue,
      errorNavegacion: navigationError,
    },
    evidencia: {
      screenshot: screenshotPath,
    },
    consola: consoleIssues.slice(0, 25),
    textoInicial: bodyText.replace(/\s+/g, ' ').trim().slice(0, 1200),
  };

  console.log(JSON.stringify(result, null, 2));
  await browser.close();
})().catch((err) => {
  console.error(JSON.stringify({ ok: false, error: String(err.message || err) }, null, 2));
  process.exitCode = 1;
});
