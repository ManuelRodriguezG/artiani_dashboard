const fs = require('fs');
const path = require('path');
const { chromium } = require('../../public/js/node_modules/@playwright/test');

/**
 * Documentacion IA: Codex GPT-5, 2026-07-04.
 * Proposito: validar visualmente Configuracion POS con permisos finos sembrados.
 * Impacto: confirma carga, tablas y edicion de formularios sin guardar ni desactivar.
 * Contrato: read-only; no presiona botones Guardar ni Desactivar, no mueve caja ni inventario.
 */

const baseUrl = process.env.POS_UAT_CONFIG_URL || 'http://panel.com.local/ventas/pos_configuracion';
const loginUrl = process.env.POS_UAT_LOGIN_URL || 'http://panel.com.local/autenticacion/login';
const usuario = process.env.POS_UAT_USER || '';
const contrasenia = process.env.POS_UAT_PASS || '';
const outDir = path.resolve(__dirname, '../../public/storage/uat');
const screenshotPath = path.join(outDir, 'pos_configuracion_crud_uat.png');

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
  if (login.loginOk && !/ventas\/pos_configuracion/i.test(page.url())) {
    await page.goto(baseUrl, { waitUntil: 'networkidle', timeout: 30000 }).catch((err) => {
      navigationError = String(err.message || err);
    });
  }

  await page.waitForTimeout(2500);

  const bodyTextInicial = await page.locator('body').innerText({ timeout: 5000 }).catch(() => '');
  const hasConfig = /Configuracion POS|Tiendas, cajas, terminales|Administracion POS/i.test(bodyTextInicial);
  const hasGuardar = /Guardar|Guardar asignacion/i.test(bodyTextInicial);
  const cajasRows = await page.locator('#pos_config_cajas tr').count().catch(() => 0);
  const terminalesRows = await page.locator('#pos_config_terminales tr').count().catch(() => 0);
  const asignacionesRows = await page.locator('#pos_config_asignaciones tr').count().catch(() => 0);
  const botonesEditar = await page.locator('[data-pos-config-editar]').count().catch(() => 0);
  const botonesDesactivar = await page.locator('[data-pos-config-desactivar]').count().catch(() => 0);

  await page.locator('[data-pos-filtro="terminales"][data-valor="todos"]').click().catch(() => null);
  await page.locator('[data-pos-filtro="asignaciones"][data-valor="todos"]').click().catch(() => null);
  await page.waitForTimeout(400);
  const terminalesTodosRows = await page.locator('#pos_config_terminales tr').count().catch(() => 0);
  const asignacionesTodosRows = await page.locator('#pos_config_asignaciones tr').count().catch(() => 0);
  const muestraHistorico = /Historico|inactiva|inactivo/i.test(await page.locator('body').innerText({ timeout: 5000 }).catch(() => ''));

  await page.locator('[data-pos-filtro="terminales"][data-valor="historico"]').click().catch(() => null);
  await page.locator('[data-pos-filtro="asignaciones"][data-valor="historico"]').click().catch(() => null);
  await page.waitForTimeout(400);
  const terminalesHistoricoRows = await page.locator('#pos_config_terminales tr').count().catch(() => 0);
  const asignacionesHistoricoRows = await page.locator('#pos_config_asignaciones tr').count().catch(() => 0);

  await page.locator('[data-pos-filtro="terminales"][data-valor="activos"]').click().catch(() => null);
  await page.locator('[data-pos-filtro="asignaciones"][data-valor="activos"]').click().catch(() => null);
  await page.waitForTimeout(400);

  let editarCajaLlena = false;
  let editarTerminalLlena = false;
  let editarAsignacionLlena = false;

  const cajaEditar = page.locator('[data-pos-config-editar="caja"]').first();
  if (await cajaEditar.count().catch(() => 0)) {
    await cajaEditar.click();
    await page.waitForTimeout(400);
    editarCajaLlena = Boolean(await page.locator('#pos_cfg_caja_id').inputValue().catch(() => ''));
  }

  const terminalEditar = page.locator('[data-pos-config-editar="terminal"]').first();
  if (await terminalEditar.count().catch(() => 0)) {
    await terminalEditar.click();
    await page.waitForTimeout(400);
    editarTerminalLlena = Boolean(await page.locator('#pos_cfg_terminal_id').inputValue().catch(() => ''));
  }

  const asignacionEditar = page.locator('[data-pos-config-editar="asignacion"]').first();
  if (await asignacionEditar.count().catch(() => 0)) {
    await asignacionEditar.click();
    await page.waitForTimeout(400);
    editarAsignacionLlena = Boolean(await page.locator('#pos_cfg_asig_id').inputValue().catch(() => ''));
  }

  await page.screenshot({ path: screenshotPath, fullPage: true }).catch(() => null);

  const result = {
    ok: !navigationError && login.loginOk && hasConfig && hasGuardar && cajasRows > 0 && terminalesRows > 0 && asignacionesRows > 0 && botonesEditar >= 3 && editarCajaLlena && editarTerminalLlena && editarAsignacionLlena && terminalesTodosRows >= terminalesRows && asignacionesTodosRows >= asignacionesRows && terminalesHistoricoRows > 0 && asignacionesHistoricoRows > 0 && muestraHistorico,
    modo: 'ventas_pos_configuracion_visual_uat',
    urlSolicitada: baseUrl,
    urlFinal: page.url(),
    status,
    login: Object.assign({}, login, { usuario: usuario ? 'definido' : 'no_definido' }),
    validaciones: {
      entroConfiguracion: hasConfig,
      muestraGuardar: hasGuardar,
      filasCajas: cajasRows,
      filasTerminales: terminalesRows,
      filasAsignaciones: asignacionesRows,
      botonesEditar,
      botonesDesactivar,
      terminalesTodosRows,
      asignacionesTodosRows,
      terminalesHistoricoRows,
      asignacionesHistoricoRows,
      muestraHistorico,
      editarCajaLlenaFormulario: editarCajaLlena,
      editarTerminalLlenaFormulario: editarTerminalLlena,
      editarAsignacionLlenaFormulario: editarAsignacionLlena,
      noPresionoGuardar: true,
      noPresionoDesactivar: true,
      noAbreTurno: true,
      noMueveCaja: true,
      noMueveInventario: true
    },
    evidencia: {
      screenshot: screenshotPath
    },
    consola: issues.slice(0, 25),
    textoInicial: bodyTextInicial.replace(/\s+/g, ' ').trim().slice(0, 1600)
  };

  console.log(JSON.stringify(result, null, 2));
  await browser.close();
})().catch((err) => {
  console.error(JSON.stringify({ ok: false, error: String(err.message || err) }, null, 2));
  process.exitCode = 1;
});
