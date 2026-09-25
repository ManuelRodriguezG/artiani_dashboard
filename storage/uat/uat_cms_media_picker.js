/* IA: Codex GPT-6 | Fecha: 2026-09-24
 * Proposito: verificar selector/optimizador Media sin autenticar ni modificar medios.
 * Impacto: UAT CMS; mocks de DOM, canvas, fetch y almacenamiento, nunca red o BD real.
 * Uso: node storage/uat/uat_cms_media_picker.js desde cualquier directorio.
 */
'use strict';
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const root = path.resolve(__dirname, '../..');

/* IA: Codex GPT-6 | Fecha: 2026-09-24. Proposito: simular elementos usados por render Media; contrato: captura HTML y acciones, no abre navegador. */
function element(value = '') {
  return {value, innerHTML: '', textContent: '', addEventListener() {}};
}

/* IA: Codex GPT-6 | Fecha: 2026-09-24. Proposito: probar fuente autoritativa, formatos y permisos; impacto: impide regresiones de selector y borrados. */
async function probarPicker() {
  const nodes = {cms_actual_media_carga: element(), cms_actual_media_estado: element()};
  const cache = {}, requests = [];
  let fallar = false;
  let informeAcceso = {ok: false, mensaje: 'Guardada en la biblioteca; acceso no confirmado. HTTP 403. No vuelvas a subirla.'};
  let tipoRespuesta = 'success';
  const comprobadas = [], aplicadas = [];
  const context = {
    window: {ERP_CSRF_TOKEN: 'uat-mock', confirm: () => true},
    document: {
      addEventListener() {}, getElementById: id => nodes[id] || null,
      createElement() {
        let text = '';
        return {set textContent(value) {text = String(value);}, get innerHTML() {return text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');}};
      }
    },
    localStorage: {getItem: key => cache[key] || '[]', setItem: (key, value) => {cache[key] = value;}},
    fetch: async (url, options) => {
      requests.push({url, options});
      if (fallar) throw new Error('Sin conexion simulada');
      const depurar = url.includes('reemplazar') || url.includes('subir') ? {
        id_media_archivo: 4, url: '/assets/media/cms/ecommerce/collar-perro-4-abcd1234.webp', nombre_seo: options.body.get('nombre_seo') || 'collar-perro',
        alt: options.body.get('alt') || 'Collar de perro', bytes: 100, urls_anteriores: ['/assets/media/cms/ecommerce/logo.ico']
      } : url.includes('preflight') ? {permisos: {editar: true, publicar: false}} : {
        items: [{id_media_archivo: url.includes('offset=0') ? 2 : 1, url: '/assets/media/cms/ecommerce/test.png', ancho: 64, alto: 64, bytes: 42}],
        hay_mas: url.includes('offset=0')
      };
      return {ok: true, json: async () => ({error: false, tipo: tipoRespuesta, mensaje: 'Aviso servidor simulado.', depurar}), text: async () => JSON.stringify({error: false, tipo: tipoRespuesta, mensaje: 'Aviso servidor simulado.', depurar})};
    }, FormData, File
  };
  vm.createContext(context);
  const source = fs.readFileSync(path.join(root, 'public/assets/js/custom/apps/erp/cms/frontend_actual.js'), 'utf8');
  vm.runInContext(fs.readFileSync(path.join(root, 'public/assets/js/custom/apps/erp/cms/media_tools.js'), 'utf8'), context);
  vm.runInContext(source.replace(/\}\)\(\);\s*$/, 'globalThis.uat = {estado, validarMediaFile, normalizarMediaServidor, mediaEnEditorActual, cargarMediaServidorPicker, eliminarArchivoMediaPicker, modificarArchivoMediaPicker, agregarYUsarMediaDesdeModal, renderMediaPicker, simularAplicar: function(fn) {aplicarMediaSeleccionada = fn;}};})();'), context);
  const t = context.uat;
  context.window.CmsMediaTools.verificarDisponibilidad = async item => {comprobadas.push(item); return informeAcceso;};
  t.simularAplicar(id => aplicadas.push(id));
  for (const ext of ['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif', 'ico']) assert.equal(t.validarMediaFile({name: 'test.' + ext, type: '', size: 100}), '');
  assert.notEqual(t.validarMediaFile({name: 'x.svg', type: 'image/svg+xml', size: 100}), '');
  assert.notEqual(t.validarMediaFile({name: 'x.jpg', type: 'image/jpeg', size: 0}), '');
  assert.notEqual(t.validarMediaFile({name: 'x.jpg', type: 'image/jpeg', size: 3 * 1024 * 1024}), '');
  assert.equal(t.validarMediaFile({name: 'x.jpg', type: 'image/jpeg', size: 3 * 1024 * 1024}, true), '');
  assert.notEqual(t.validarMediaFile({name: 'x.jpg', type: 'image/jpeg', size: 21 * 1024 * 1024}, true), '');
  const row = t.normalizarMediaServidor({id_media_archivo: 4, url: '/assets/media/cms/ecommerce/logo.ico', preview_url: '/assets/media/cms/ecommerce/logo.ico?v=abc', ancho: 64, alto: 64, bytes: 900, nombre_original: '<script>prueba</script>'});
  assert.equal(row.extension, 'ico');
  assert.equal(row.url, '/assets/media/cms/ecommerce/logo.ico');
  assert.notEqual(row.preview_url, row.url);
  t.estado.datos = {draft: {favicon: 'https://panel.com.local/assets/media/cms/ecommerce/logo.ico?v=old'}};
  assert.equal(t.mediaEnEditorActual(row.url), true);
  assert.equal(t.mediaEnEditorActual('/assets/media/cms/ecommerce/nuevo.webp', [row.url]), true, 'Un alias antiguo en el borrador sigue bloqueando el borrado');
  t.estado.mediaBiblioteca.permisos = {editar: true, publicar: true};
  t.estado.mediaBiblioteca.usos[row.id] = {puede_eliminar: true, usos: []};
  t.eliminarArchivoMediaPicker(row);
  assert.equal(requests.length, 0, 'No hacer POST si el borrador actual usa el archivo');
  nodes.cms_actual_media_lista = element(); nodes.cms_actual_media_preview_seleccion = element();
  t.estado.mediaBiblioteca.cargada = true; t.estado.mediaBiblioteca.items = [row];
  t.renderMediaPicker();
  let html = nodes.cms_actual_media_preview_seleccion.innerHTML;
  assert(html.includes('ICO')); assert(html.includes('64 × 64 px'));
  assert(html.includes('logo.ico?v=abc')); assert(!html.includes('<script>prueba'));
  assert(/id="cms_actual_media_eliminar" disabled/.test(html), 'Borrador bloquea eliminar en UI');
  assert(html.includes('cms_actual_media_reemplazar'));
  assert(!html.includes('id="cms_actual_media_optimizar"'), 'ICO no ofrece optimizacion');
  assert(!html.includes('id="cms_actual_media_convertir_webp"'), 'ICO no ofrece conversion de la imagen actual');
  assert(html.includes('accept=".jpg,.jpeg,.png,.webp,.gif,.avif,.ico"'), 'Reemplazo permite formato diferente');
  assert(html.includes('cms_actual_media_detalle_nombre_seo'), 'Nombre SEO editable en detalle');
  t.estado.mediaBiblioteca.permisos = {};
  t.renderMediaPicker(); html = nodes.cms_actual_media_preview_seleccion.innerHTML;
  assert(!html.includes('id="cms_actual_media_reemplazar"')); assert(!html.includes('id="cms_actual_media_eliminar"'));
  delete nodes.cms_actual_media_lista; delete nodes.cms_actual_media_preview_seleccion;
  nodes.cms_actual_media_reemplazo = element();
  nodes.cms_actual_media_reemplazo.files = [new File(['webp-fixture'], 'collar.webp', {type: 'image/webp'})];
  nodes.cms_actual_media_reemplazo_webp = {checked: false}; nodes.cms_actual_media_reemplazo_optimizar = {checked: false};
  nodes.cms_actual_media_detalle_nombre_seo = element('collar-perro'); nodes.cms_actual_media_detalle_alt = element('Collar de perro');
  row.nombre_seo = 'collar-perro'; // Nombre sugerido por backend legacy, aun sin metadata persistida.
  t.estado.mediaBiblioteca.permisos = {editar: true, publicar: true};
  await t.modificarArchivoMediaPicker(row, 'reemplazar');
  let post = requests.filter(x => x.url.includes('reemplazar')).at(-1);
  assert(post, 'Permite reemplazar ICO por WebP preparado manualmente');
  assert.equal(post.options.body.get('archivo').name, 'collar.webp'); assert.equal(post.options.body.get('nombre_seo'), 'collar-perro');
  assert.equal(post.options.body.get('nombre_seo'), row.nombre_seo, 'Envia tambien nombre visible igual a sugerencia legacy');
  assert.equal(post.options.body.get('_csrf'), 'uat-mock');
  const reemplazada = t.estado.mediaBiblioteca.items.find(x => x.id === row.id);
  assert.equal(reemplazada.extension, 'webp'); assert.equal(reemplazada.urls_anteriores[0], row.url);
  assert.equal(comprobadas[0].id, row.id, 'Reemplazo comprueba la referencia guardada');
  assert(nodes.cms_actual_media_estado.className.includes('warning'), 'Reemplazo confirmado con HTTP fallido es advertencia');
  assert(nodes.cms_actual_media_estado.textContent.includes('No vuelvas a subirla'));
  nodes.cms_actual_media_detalle_nombre_seo.value = 'collar-perro-rojo';
  await t.modificarArchivoMediaPicker(reemplazada, 'guardar');
  post = requests.filter(x => x.url.includes('reemplazar')).at(-1);
  assert.equal(post.options.body.has('archivo'), false, 'Guardar nombre no reemplaza el archivo seleccionado');
  assert.equal(post.options.body.get('nombre_seo'), 'collar-perro-rojo');
  assert.equal(post.options.body.has('alt'), false, 'Alt sin cambios no se envia ni modifica contenido contextual');
  const numPosts = requests.filter(x => x.url.includes('reemplazar')).length;
  nodes.cms_actual_media_detalle_alt.value = '';
  await t.modificarArchivoMediaPicker(reemplazada, 'guardar');
  assert.equal(requests.filter(x => x.url.includes('reemplazar')).length, numPosts, 'Alt vacio bloquea cambios');
  // IA: Codex GPT-6 | 2026-09-25 | Alta conserva biblioteca si falla acceso y no autoasigna; con comprobacion completa aplica una vez.
  nodes.cms_actual_media_nuevo_alt = element('Collar'); nodes.cms_actual_media_nuevo_nombre_seo = element('collar');
  nodes.cms_actual_media_optimizar_nuevo = {checked: false}; nodes.cms_actual_media_webp_nuevo = {checked: false};
  nodes.cms_actual_media_archivo = element(); nodes.cms_actual_media_preview_nuevo = element();
  const file = new File(['webp'], 'collar.webp', {type: 'image/webp'});
  t.estado.mediaPicker.archivo = file; t.estado.mediaPicker.dataUrl = 'data:fixture';
  await t.agregarYUsarMediaDesdeModal();
  assert.equal(aplicadas.length, 0, 'No autoaplica imagen guardada pero inaccesible');
  assert(t.estado.mediaBiblioteca.items.some(x => x.id === 'bd_4'), 'No borra imagen por fallo HTTP');
  assert.equal(t.estado.mediaPicker.archivo, null, 'Limpia captura confirmada para evitar reenviar');
  assert(nodes.cms_actual_media_estado.className.includes('warning'));
  informeAcceso = {ok: true, mensaje: 'Archivo confirmado y acceso publico verificado.'};
  t.estado.mediaPicker.archivo = file; t.estado.mediaPicker.dataUrl = 'data:fixture';
  await t.agregarYUsarMediaDesdeModal();
  assert.deepEqual(aplicadas, ['bd_4'], 'Comprobacion positiva aplica exactamente la imagen guardada');
  tipoRespuesta = 'warning';
  t.estado.mediaPicker.archivo = file; t.estado.mediaPicker.dataUrl = 'data:fixture';
  await t.agregarYUsarMediaDesdeModal();
  assert.equal(aplicadas.length, 1, 'Advertencia de backend no se oculta ni autoaplica');
  assert(nodes.cms_actual_media_estado.className.includes('warning'));
  assert(nodes.cms_actual_media_estado.textContent.includes('Aviso servidor simulado.'));
  tipoRespuesta = 'success';
  cache.erp_cms_media_biblioteca_local_v1 = JSON.stringify([{id: 'bd_99', origen: 'bd', url: '/assets/media/cms/ecommerce/eliminada.png'}]);
  t.cargarMediaServidorPicker();
  await new Promise(setImmediate);
  assert.equal(t.estado.mediaBiblioteca.cargada, true);
  assert.equal(t.estado.mediaBiblioteca.items.length, 2);
  assert.equal(t.estado.mediaBiblioteca.permisos.publicar, false);
  assert(!cache.erp_cms_media_biblioteca_local_v1.includes('bd_99'));
  assert(requests.some(x => x.url.includes('offset=1')));
  fallar = true; t.cargarMediaServidorPicker(); await new Promise(setImmediate);
  assert.equal(t.estado.mediaBiblioteca.cargada, false, 'Fallo de listado no habilita cache');
  assert.equal(nodes.cms_actual_media_carga.hidden, true);
  return 'selector: reemplazo multiformato, nombre sin archivo, Alt, aliases en borrador, limites, permisos, escape, paginacion y cache';
}

/* IA: Codex GPT-6 | Fecha: 2026-09-24. Proposito: probar biblioteca separada con reemplazo/renombre; impacto: contrato multiformato y sin archivo, fetch completamente simulado. */
async function probarBiblioteca() {
  const nodes = {cms_media_estado: element(), cms_media_reemplazo: element(), cms_media_detalle_nombre_seo: element('collar-azul'), cms_media_detalle_alt: element('Collar azul')};
  const requests = [];
  let registro = {id_media_archivo: 9, url: '/assets/media/cms/ecommerce/vieja.png', bytes: 300, alt: 'Collar azul', nombre_seo: 'vieja', extension: 'png'};
  let informeAcceso = {ok: false, mensaje: 'Guardada en la biblioteca; acceso no confirmado. HTTP 404. No vuelvas a subirla.'};
  let tipoRespuesta = 'success', fallarListado = false;
  const comprobadas = [];
  const context = {
    window: {ERP_CSRF_TOKEN: 'uat-biblioteca', confirm: () => true}, FormData, File,
    document: {addEventListener() {}, querySelectorAll: () => [], getElementById: id => nodes[id] || null},
    localStorage: {setItem() {}},
    fetch: async (url, options) => {
      requests.push({url, options});
      if (fallarListado && url.includes('listar')) throw new Error('Listado desconectado');
      let depurar;
      if (url.includes('reemplazar')) {
        registro = Object.assign({}, registro, {url: '/assets/media/cms/ecommerce/collar-azul-9-abcd1234.webp', extension: 'webp', nombre_seo: options.body.get('nombre_seo') || registro.nombre_seo, bytes: 100, urls_anteriores: ['/assets/media/cms/ecommerce/vieja.png']});
        depurar = registro;
      } else if (url.includes('subir')) depurar = registro;
      else if (url.includes('usos')) depurar = {usos: [], total: 0, puede_eliminar: true};
      else depurar = {persistencia_real: true, items: [registro], hay_mas: false};
      return {ok: true, json: async () => ({error: false, tipo: tipoRespuesta, mensaje: 'Aviso servidor simulado.', depurar})};
    }
  };
  vm.createContext(context);
  vm.runInContext(fs.readFileSync(path.join(root, 'public/assets/js/custom/apps/erp/cms/media_tools.js'), 'utf8'), context);
  const source = fs.readFileSync(path.join(root, 'public/assets/js/custom/apps/erp/cms/media.js'), 'utf8');
  vm.runInContext(source.replace(/\}\)\(\);\s*$/, 'globalThis.uat = {estado, normalizarItemServidor, reemplazarMediaServidor, subirArchivoServidor};})();'), context);
  context.window.CmsMediaTools.verificarDisponibilidad = async item => {comprobadas.push(item); return informeAcceso;};
  const t = context.uat, row = t.normalizarItemServidor(registro);
  t.estado.items = [row]; t.estado.activo = row.id; t.estado.permisos = {editar: true, publicar: true};
  nodes.cms_media_reemplazo.files = [new File(['webp'], 'collar.webp', {type: 'image/webp'})];
  await t.reemplazarMediaServidor(row, 'reemplazar');
  let post = requests.filter(x => x.url.includes('reemplazar')).at(-1);
  assert(post, 'Biblioteca acepta PNG reemplazado por WebP'); assert.equal(post.options.body.get('archivo').name, 'collar.webp');
  assert.equal(t.estado.items[0].extension, 'webp'); assert.equal(t.estado.items[0].urls_anteriores[0], row.url);
  assert.equal(comprobadas[0].id, row.id);
  assert(nodes.cms_media_estado.className.includes('warning')); assert(nodes.cms_media_estado.textContent.includes('HTTP 404'));
  await t.reemplazarMediaServidor(t.estado.items[0], 'guardar');
  post = requests.filter(x => x.url.includes('reemplazar')).at(-1);
  assert.equal(post.options.body.get('nombre_seo'), t.estado.items[0].nombre_seo, 'Guardar envia nombre aunque coincida con sugerencia visible');
  assert.equal(post.options.body.has('archivo'), false);
  nodes.cms_media_detalle_nombre_seo.value = 'collar-azul-perro';
  await t.reemplazarMediaServidor(t.estado.items[0], 'guardar');
  post = requests.filter(x => x.url.includes('reemplazar')).at(-1);
  assert.equal(post.options.body.has('archivo'), false); assert.equal(post.options.body.get('nombre_seo'), 'collar-azul-perro');
  assert.equal(post.options.body.has('alt'), false); assert.equal(post.options.body.get('_csrf'), 'uat-biblioteca');
  // IA: Codex GPT-6 | 2026-09-25 | Confirmar que alta/duplicada se comprueba tambien, mantiene item y nunca marca exito tras fallo publico.
  nodes.cms_media_archivo = element(); nodes.cms_media_alt = element('Collar azul'); nodes.cms_media_nombre_seo = element('collar');
  t.estado.archivoPendiente = new File(['webp'], 'collar.webp', {type: 'image/webp'});
  await t.subirArchivoServidor();
  assert.equal(comprobadas.length, 4); assert.equal(t.estado.archivoPendiente, null);
  assert.equal(t.estado.items.length, 1, 'Respuesta de alta duplicada conserva un solo medio');
  assert(nodes.cms_media_estado.className.includes('warning'));
  informeAcceso = {ok: true, mensaje: 'Archivo y acceso publico verificados.'};
  nodes.cms_media_alt.value = 'Collar azul'; t.estado.archivoPendiente = new File(['webp'], 'collar.webp', {type: 'image/webp'});
  await t.subirArchivoServidor();
  assert(nodes.cms_media_estado.className.includes('success'));
  tipoRespuesta = 'warning';
  nodes.cms_media_alt.value = 'Collar azul'; t.estado.archivoPendiente = new File(['webp'], 'collar.webp', {type: 'image/webp'});
  await t.subirArchivoServidor();
  assert(nodes.cms_media_estado.className.includes('warning')); assert(nodes.cms_media_estado.textContent.includes('Aviso servidor simulado.'));
  tipoRespuesta = 'success'; fallarListado = true;
  nodes.cms_media_alt.value = 'Collar azul'; t.estado.archivoPendiente = new File(['webp'], 'collar.webp', {type: 'image/webp'});
  await t.subirArchivoServidor();
  assert(nodes.cms_media_estado.className.includes('warning')); assert(nodes.cms_media_estado.textContent.includes('Los cambios estan guardados'));
  assert.equal(t.estado.items.length, 1, 'Fallo al refrescar conserva item confirmado por POST');
  return 'biblioteca: PNG a WebP, alias preservado, nombre editable sin archivo y Alt estable';
}

/* IA: Codex GPT-6 | Fecha: 2026-09-24. Proposito: generar cabeceras minimas para validar chunks; contrato: fixture sintetica solo se decodifica por mock. */
function png(tipo = 'IEND', longitud = 0, longitudReal = longitud) {
  const bytes = Buffer.alloc(8 + 12 + longitudReal);
  Buffer.from([137, 80, 78, 71, 13, 10, 26, 10]).copy(bytes);
  bytes.writeUInt32BE(longitud, 8); bytes.write(tipo, 12);
  return bytes;
}

/* IA: Codex GPT-6 | Fecha: 2026-09-24. Proposito: generar cabecera WebP animada para regresion; impacto: prueba evita aplanar animacion VP8X. */
function webpAnimada() {
  const bytes = Buffer.alloc(30);
  bytes.write('RIFF'); bytes.writeUInt32LE(22, 4); bytes.write('WEBPVP8X', 8); bytes.writeUInt32LE(10, 16); bytes[20] = 2;
  return bytes;
}

/* IA: Codex GPT-6 | Fecha: 2026-09-24. Proposito: probar optimizador sin imagenes reales; impacto: detecta conversion, animacion, perdida de alpha y limites. */
async function probarOptimizador() {
  const settings = {width: 4000, height: 2000, size: 2, tipoForzado: '', contexto: true, contentLength: '30000000', redirect: '', contenido: png()}, canvases = [], revocados = [], peticiones = [];
  const context = {
    window: {location: {origin: 'http://panel.com.local'}}, File, Blob, Uint8Array, DataView,
    URL: class extends URL {static createObjectURL() {return 'blob:uat';} static revokeObjectURL(url) {revocados.push(url);}},
    Image: class {constructor() {this.naturalWidth = settings.width; this.naturalHeight = settings.height;} set src(value) {queueMicrotask(() => this.onload());}},
    document: {createElement(tag) {
      assert.equal(tag, 'canvas');
      const canvas = {width: 0, height: 0, draw: 0, fill: 0, getContext() {return settings.contexto ? {drawImage() {canvas.draw++;}, fillRect() {canvas.fill++;}} : null;}, toBlob(cb, mime) {cb(new Blob([new Uint8Array(settings.size)], {type: settings.tipoForzado || mime}));}};
      canvases.push(canvas); return canvas;
    }},
    fetch: async url => {peticiones.push(url); return {ok: true, url: settings.redirect || url, headers: {get: () => settings.contentLength}, blob: async () => new Blob([settings.contenido])};}
  };
  vm.createContext(context);
  vm.runInContext(fs.readFileSync(path.join(root, 'public/assets/js/custom/apps/erp/cms/media_tools.js'), 'utf8'), context);
  const t = context.window.CmsMediaTools;
  const original = new File([png()], 'transparente.png', {type: 'image/png'});
  const resultado = await t.optimizar(original);
  assert.equal(resultado.type, 'image/png'); assert.equal(resultado.name, original.name); assert(resultado.size < original.size);
  assert.equal(canvases[0].width, 2560); assert.equal(canvases[0].height, 1280);
  assert.equal(canvases[0].draw, 1); assert.equal(canvases[0].fill, 0, 'No se pinta fondo que elimine transparencia');
  assert.equal(revocados.length, 1);
  const webp = await t.convertirWebp(original);
  assert.equal(webp.type, 'image/webp'); assert.equal(webp.name, 'transparente.webp');
  assert.equal(canvases.at(-1).fill, 0, 'WebP conserva alpha');
  assert.equal(t.sugerirNombreSeo('Colláres para Pérros.PNG'), 'collares-para-perros');
  assert.equal(t.sugerirNombreSeo('cms_20260924_143253_0c68019dd7bd_Foto de collar.png'), 'foto-de-collar');
  assert.equal(t.sugerirNombreSeo('X'.repeat(150)).length, 120);
  for (const ext of ['ico', 'gif', 'avif']) await assert.rejects(t.optimizar(new File([png()], 'x.' + ext)), /solo JPG/);
  for (const ext of ['ico', 'gif', 'avif']) await assert.rejects(t.convertirWebp(new File([png()], 'x.' + ext)), /solo JPG/);
  await assert.rejects(t.optimizar({size: 21 * 1024 * 1024, name: 'x.jpg', arrayBuffer() {throw new Error('No debe leer');}}), /20 MB/);
  await assert.rejects(t.optimizar(new File([png()], 'mal.jpg')), /no coincide/);
  await assert.rejects(t.optimizar(new File([png('acTL', 8)], 'animada.png')), /animada/);
  await assert.rejects(t.optimizar(new File([webpAnimada()], 'animada.webp')), /animada/);
  await assert.rejects(t.convertirWebp(new File([webpAnimada()], 'animada.webp')), /animada/);
  await assert.rejects(t.convertirWebp(new File([png('acTL', 8)], 'animada.png')), /animada/);
  await assert.rejects(t.optimizar(new File([png('IDAT', 30, 1)], 'truncada.png')), /estructura no valida/);
  settings.tipoForzado = 'image/jpeg'; await assert.rejects(t.optimizar(original), /sin convertirlo/); settings.tipoForzado = '';
  settings.size = 99999; assert.equal(await t.optimizar(original), original);
  const mayor = await t.convertirWebp(original); assert.equal(mayor.type, 'image/webp'); assert(mayor.size > original.size, 'Conversion expresa devuelve resultado mayor para que usuario compare'); settings.size = 2;
  settings.width = 10000; settings.height = 10000; await assert.rejects(t.optimizar(original), /demasiados pixeles/);
  settings.width = 64; settings.height = 64; settings.contexto = false; await assert.rejects(t.optimizar(original), /no dispone/); settings.contexto = true;
  await assert.rejects(t.optimizarUrl('https://externo.test/assets/media/cms/ecommerce/x.png'), /guardada/);
  await assert.rejects(t.optimizarUrl('/assets/media/otro/x.png'), /guardada/);
  assert.equal(peticiones.length, 0, 'No solicita URLs ajenas a Media local');
  await assert.rejects(t.optimizarUrl('/assets/media/cms/ecommerce/x.png'), /20 MB/);
  await assert.rejects(t.convertirWebpUrl('https://externo.test/assets/media/cms/ecommerce/x.png'), /guardada/);
  settings.contentLength = '12'; settings.redirect = 'http://panel.com.local/assets/media/cms/ecommerce/actual.webp';
  settings.contenido = Buffer.alloc(12); settings.contenido.write('RIFF'); settings.contenido.writeUInt32LE(4, 4); settings.contenido.write('WEBP', 8);
  const desdeAlias = await t.convertirWebpUrl('/assets/media/cms/ecommerce/anterior.png');
  assert.equal(desdeAlias.name, 'actual.webp', 'Descarga toma extension final de redirect PNG a WebP');
  settings.redirect = 'https://externo.test/assets/media/cms/ecommerce/actual.webp';
  await assert.rejects(t.convertirWebpUrl('/assets/media/cms/ecommerce/anterior.png'), /redirige fuera/);
  settings.redirect = 'http://panel.com.local/otra/carpeta/actual.webp';
  await assert.rejects(t.convertirWebpUrl('/assets/media/cms/ecommerce/anterior.png'), /redirige fuera/);
  return 'optimizador/conversor: WebP explicito, firma, alpha, dimensiones, animaciones, peso comparado, nombres y limites';
}

/* IA: Codex GPT-6 | Fecha: 2026-09-25
 * Proposito: probar diagnostico publico con respuestas y tiempos simulados.
 * Impacto: subidas/reemplazos; contrato: no red real y falla de acceso conserva resultado de guardado.
 */
async function probarDisponibilidad() {
  const crypto = require('node:crypto'), contenido = png(), requests = [];
  let caso = {};
  const context = {
    window: {location: {origin: 'https://panel.test'}, crypto: crypto.webcrypto}, URL, Uint8Array, DataView, AbortController,
    setTimeout(fn, ms) {assert.equal(ms, 10000); return setTimeout(fn, caso.timeout ? 0 : ms);}, clearTimeout,
    fetch: async (url, options) => {
      requests.push({url, options});
      if (caso.network) throw new Error('Falla simulada');
      if (caso.timeout) return new Promise((resolve, reject) => {options.signal.addEventListener('abort', () => {const e = new Error('aborted'); e.name = 'AbortError'; reject(e);});});
      return {
        ok: !caso.status || caso.status === 200, status: caso.status || 200,
        url: caso.url || url, type: caso.type || 'basic', redirected: !!caso.redirected,
        headers: {get(name) {return name === 'Content-Type' ? caso.mime || 'image/png' : String((caso.body || contenido).length);}},
        arrayBuffer: async () => new Uint8Array(caso.body || contenido).buffer
      };
    }
  };
  vm.createContext(context);
  vm.runInContext(fs.readFileSync(path.join(root, 'public/assets/js/custom/apps/erp/cms/media_tools.js'), 'utf8'), context);
  const t = context.window.CmsMediaTools;
  const item = {url: '/assets/media/cms/ecommerce/logo.png', preview_url: '/assets/media/cms/ecommerce/logo.png?v=abc', bytes: contenido.length, hash_sha256: crypto.createHash('sha256').update(contenido).digest('hex'), validacion_archivo: {ok: true}};
  let result = await t.verificarDisponibilidad(item);
  assert.equal(result.ok, true); assert.equal(result.hash_verificado, true);
  assert.equal(requests[0].options.credentials, 'omit'); assert.equal(requests[0].options.redirect, 'manual');
  assert.equal(requests[0].options.cache, 'no-store'); assert(requests[0].url.includes('_cms_verificar='));
  assert(requests[0].url.includes('v=abc'), 'Valida tambien la URL de preview que usa cachebuster');
  for (const [mock, estado] of [
    [{status: 401}, 'acceso_denegado'], [{status: 403}, 'acceso_denegado'], [{status: 404}, 'no_encontrada'],
    [{status: 503}, 'error_servidor'], [{status: 302}, 'redireccion'], [{type: 'opaqueredirect'}, 'redireccion'],
    [{redirected: true}, 'redireccion'], [{mime: 'text/html; charset=utf-8'}, 'contenido_no_imagen'],
    [{mime: 'image/jpeg'}, 'contenido_no_imagen'], [{body: Buffer.alloc(1)}, 'contenido_distinto'],
    [{body: Buffer.alloc(contenido.length)}, 'contenido_distinto'], [{network: true}, 'conexion'], [{timeout: true}, 'tiempo_agotado'],
    [{url: 'https://externo.test/assets/media/cms/ecommerce/logo.png'}, 'redireccion']
  ]) {
    caso = mock; result = await t.verificarDisponibilidad(item);
    assert.equal(result.ok, false); assert.equal(result.estado, estado); assert(result.mensaje.includes('No vuelvas a subirla'));
  }
  caso = {};
  result = await t.verificarDisponibilidad(Object.assign({}, item, {hash_sha256: 'a'.repeat(64)}));
  assert.equal(result.estado, 'contenido_distinto', 'Peso/firma iguales no ocultan hash distinto');
  const anteriores = requests.length;
  for (const estado of ['ausente', 'no_legible', 'permisos_restringidos', 'contenido_distinto', 'no_verificable']) {
    result = await t.verificarDisponibilidad(Object.assign({}, item, {validacion_archivo: {ok: false, estado, mensaje: 'Diagnostico de archivo.'}}));
    assert.equal(result.estado, estado); assert(result.mensaje.includes('Diagnostico de archivo.'));
  }
  assert.equal((await t.verificarDisponibilidad(Object.assign({}, item, {validacion_archivo: null}))).estado, 'sin_validacion');
  for (const url of ['https://externo.test/assets/media/cms/ecommerce/logo.png', '/app/config/mysql.php', '/assets/media/cms/ecommerce/../logo.png']) {
    assert.equal((await t.verificarDisponibilidad(Object.assign({}, item, {url, preview_url: url}))).estado, 'ruta_no_valida');
  }
  assert.equal(requests.length, anteriores, 'Servidor fallido o ruta ajena no dispara descarga');
  context.window.crypto = null;
  result = await t.verificarDisponibilidad(item);
  assert.equal(result.ok, true); assert.equal(result.hash_verificado, false); assert(result.mensaje.includes('Formato y peso'));
  const avif = Buffer.alloc(48);
  avif.writeUInt32BE(8, 0); avif.write('free', 4);
  avif.writeUInt32BE(40, 8); avif.write('ftypmif1', 12); avif.write('mif1msf1miafmiafavifavis', 24);
  caso = {mime: 'image/avif', body: avif};
  const itemAvif = {url: '/assets/media/cms/ecommerce/foto.avif', bytes: avif.length, validacion_archivo: {ok: true}};
  result = await t.verificarDisponibilidad(itemAvif);
  assert.equal(result.ok, true, 'AVIF admite caja previa y marca compatible despues del byte 32 sin WebCrypto');
  avif.writeUInt32BE(60, 8);
  assert.equal((await t.verificarDisponibilidad(itemAvif)).estado, 'contenido_distinto', 'AVIF rechaza caja ftyp que excede buffer');
  return 'disponibilidad: disco previo, acceso sin sesion, nonce, 200/401/403/404/503, redirecciones, HTML, MIME, firma, peso, hash, red y tiempo';
}

Promise.all([probarPicker(), probarBiblioteca(), probarOptimizador(), probarDisponibilidad()]).then(resultados => {
  resultados.forEach(resultado => console.log('OK ' + resultado));
  console.log('UAT completada. Sin solicitudes reales, cambios de BD ni archivos media.');
}).catch(error => {console.error(error); process.exitCode = 1;});
