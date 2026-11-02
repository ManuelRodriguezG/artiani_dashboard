/* IA: Codex GPT-6.1-sol | Fecha: 2026-11-01.
 * Proposito: probar cargas Blog exitosas, inciertas y sin permiso con mocks sin red/BD.
 * Impacto: protege asignacion de imagen, controles de permisos y ausencia de publicacion automatica.
 * Uso: node storage/uat/uat_cms_blog_upload_aislada.js
 */
'use strict';
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const {File} = require('node:buffer');
/** IA: Codex GPT-6.1-sol | 2026-11-01. Mock DOM minimo para probar efectos observables de carga. */
function nodo(value = '') { return {value, dataset: {}, files: [], disabled: false, className: '', textContent: '', focus() {}}; }
/** IA: Codex GPT-6.1-sol | 2026-11-01. Verifica autorizacion, POST unico, ALT, resultados ambiguos y cambio de articulo. */
async function main() {
  const nodes = Object.fromEntries(['file','alt','nombre','destino','btn','estado'].map(x => ['cms_blog_upload_' + x, nodo()]));
  const els = {id: nodo('1'), titulo: nodo('Piton'), slug: nodo('piton'), portadaUrl: nodo(), portadaAlt: nodo(), contenido: nodo('<p>Texto</p>'), imagenesJson: nodo('[]')};
  const requests = [];
  let modo = 'ok', mensaje = 'Verificada';
  const item = {url: '/assets/media/cms/ecommerce/piton.webp', alt: 'Piton bola', ancho: 1536, alto: 1024, bytes: 100, id_media_archivo: 123};
  const context = {console, FormData, document: {addEventListener() {}, getElementById: id => nodes[id] || null}, window: {ERP_CSRF_TOKEN: 'mock-csrf', CmsMediaTools: {verificarDisponibilidad: async () => {
    if (modo === 'cambio') els.id.value = '2';
    return {ok: modo !== 'sin_acceso', mensaje};
  }}}, fetch: async (url, options) => {
    requests.push({url, options});
    if (modo === 'red') throw new Error('Sin respuesta');
    return {ok: true, json: async () => modo === 'no_json' ? Promise.reject(new Error('HTML')) : {error: false, tipo: modo === 'warning' ? 'warning' : 'success', mensaje: 'Guardada', depurar: {item}}};
  }};
  vm.createContext(context);
  const src = fs.readFileSync(path.resolve(__dirname, '../../public/assets/js/custom/apps/erp/cms/blog.js'), 'utf8');
  vm.runInContext(src.replace(/\}\)\(\);\s*$/, 'globalThis.uat = {subirImagenBlog, configurar: function(e,p) {els=e;uploadPermitido=p;}};})();'), context);
  const t = context.uat;
  t.configurar(els, false);
  nodes.cms_blog_upload_file.files = [new File(['fixture'], 'piton.webp', {type: 'image/webp'})];
  nodes.cms_blog_upload_alt.value = 'Piton bola';
  nodes.cms_blog_upload_destino.value = 'portada';
  await t.subirImagenBlog(); assert.equal(requests.length, 0);
  t.configurar(els, true);
  await t.subirImagenBlog();
  assert.equal(els.portadaUrl.value, item.url);
  assert.equal(Number(els.portadaUrl.dataset.width), 1536);
  assert.equal(Number(els.portadaUrl.dataset.height), 1024);
  assert.equal(requests[0].options.body.get('uso'), 'blog');
  assert.equal(requests[0].options.body.get('_csrf'), 'mock-csrf');
  assert.match(nodes.cms_blog_upload_estado.textContent, /Guardar borrador/);
  for (const escenario of ['sin_acceso','warning','no_json','red','cambio']) {
    modo = escenario;
    els.id.value = '1'; els.portadaUrl.value = 'anterior';
    nodes.cms_blog_upload_file.value = 'seleccion';
    await t.subirImagenBlog();
    assert.equal(els.portadaUrl.value, 'anterior', escenario + ': no asignar la imagen');
    assert.equal(nodes.cms_blog_upload_btn.disabled, false);
  }
  modo = 'ok';
  nodes.cms_blog_upload_destino.value = 'contenido';
  await t.subirImagenBlog();
  assert.match(els.contenido.value, /<figure>/);
  assert.equal(JSON.parse(els.imagenesJson.value)[0].url, item.url);
  assert(requests.every(x => x.url === '/cms/media_admin_subir_erp'), 'No guardar ni publicar articulo desde carga');
  const antes = requests.length;
  nodes.cms_blog_upload_file.files = [new File([new Uint8Array(2097153)], 'grande.webp')];
  await t.subirImagenBlog(); assert.equal(requests.length, antes, 'Rechazar exceso antes de POST');
  console.log(JSON.stringify({ok: true, escenarios: 9, red_real: false, bd: false}));
}
main().catch(error => { console.error(error); process.exitCode = 1; });
