/* IA: Codex GPT-6 | Fecha: 2026-09-24
 * Proposito: reducir peso o convertir a WebP por eleccion expresa en ambos selectores Media.
 * Impacto: conserva transparencia; nunca procesa ICO/GIF/AVIF ni animaciones.
 * Contrato: Promise<File>, maximo 2560 px por lado, calidad 82%; WebP devuelve el resultado para comparar peso.
 */
(function () {
  'use strict';
  /* IA: Codex GPT-6 | Fecha: 2026-09-24
   * Proposito: optimizar solo imagenes estaticas con formato verificado; impacto: conserva formato/transparencia sin aplanar animaciones.
   * Contrato: fuente hasta 20 MB, firma acorde a extension; optimizar conserva MIME y solo ahorra, convertir genera WebP para comparar.
   */
  async function procesar(file, convertirWebp) {
    if (!file || !file.size) throw new Error('El archivo esta vacio.');
    if (file.size > 20 * 1024 * 1024) throw new Error('La imagen original supera 20 MB. Reduce su peso antes de seleccionarla.');
    var extension = String(file.name || '').split('.').pop().toLowerCase();
    var mime = {jpg: 'image/jpeg', jpeg: 'image/jpeg', png: 'image/png', webp: 'image/webp'}[extension];
    if (!mime) throw new Error('Optimiza solo JPG, PNG o WebP. Los demas formatos se conservan originales.');
    var buffer = await file.arrayBuffer();
    var bytes = new Uint8Array(buffer);
    var firma = String.fromCharCode.apply(null, bytes.subarray(0, 12));
    var esJpeg = bytes.length >= 3 && bytes[0] === 255 && bytes[1] === 216 && bytes[2] === 255;
    var esPng = bytes.length >= 8 && firma.substring(0, 8) === '\x89PNG\r\n\x1a\n';
    var esWebp = bytes.length >= 12 && firma.substring(0, 4) === 'RIFF' && firma.substring(8, 12) === 'WEBP';
    if ((mime === 'image/jpeg' && !esJpeg) || (mime === 'image/png' && !esPng) || (mime === 'image/webp' && !esWebp)) throw new Error('El contenido del archivo no coincide con su extension. Usa el archivo original con el formato correcto.');
    // Recorrer chunks reales impide aplanar APNG/WebP animados accidentalmente.
    var view = new DataView(buffer);
    var offset = extension === 'png' ? 8 : 12;
    if (extension === 'png' || extension === 'webp') {
      while (offset + 8 <= bytes.length) {
        var tipo = String.fromCharCode.apply(null, bytes.subarray(offset + (extension === 'png' ? 4 : 0), offset + (extension === 'png' ? 8 : 4)));
        if (tipo === 'acTL' || tipo === 'ANIM' || tipo === 'ANMF') throw new Error('Esta imagen es animada. Se debe conservar el archivo original.');
        var longitud = view.getUint32(offset + (extension === 'png' ? 0 : 4), extension === 'webp');
        var siguiente = offset + (extension === 'png' ? longitud + 12 : longitud + 8 + (longitud % 2));
        if (siguiente > bytes.length) throw new Error('La imagen esta incompleta o tiene una estructura no valida. Conserva el original.');
        if (tipo === 'VP8X' && longitud > 0 && (bytes[offset + 8] & 2)) throw new Error('Esta imagen es animada. Se debe conservar el archivo original.');
        offset = siguiente;
      }
    }
    var url = URL.createObjectURL(file);
    try {
      var img = new Image();
      await new Promise(function (resolve, reject) {
        img.onload = resolve;
        img.onerror = function () { reject(new Error('No se pudo leer la imagen para optimizar.')); };
        img.src = url;
      });
      if (!img.naturalWidth || !img.naturalHeight) throw new Error('No se pudieron leer las dimensiones de la imagen.');
      if (img.naturalWidth * img.naturalHeight > 40000000) throw new Error('La imagen tiene demasiados pixeles para optimizarla aqui.');
      var escala = Math.min(1, 2560 / Math.max(img.naturalWidth, img.naturalHeight));
      var canvas = document.createElement('canvas');
      canvas.width = Math.max(1, Math.round(img.naturalWidth * escala));
      canvas.height = Math.max(1, Math.round(img.naturalHeight * escala));
      var context = canvas.getContext('2d');
      if (!context) throw new Error('Tu navegador no dispone de la herramienta para reducir peso.');
      // Canvas nuevo conserva alpha; nunca pintar un fondo antes de PNG/WebP transparentes.
      context.drawImage(img, 0, 0, canvas.width, canvas.height);
      var salidaMime = convertirWebp ? 'image/webp' : mime;
      var blob = await new Promise(function (resolve) { canvas.toBlob(resolve, salidaMime, 0.82); });
      if (!blob || blob.type !== salidaMime) throw new Error(convertirWebp ? 'Tu navegador no permite convertir a WebP. Usa un archivo WebP preparado externamente.' : 'Tu navegador no puede optimizar este formato sin convertirlo.');
      if (!convertirWebp && blob.size >= file.size) return file;
      return new File([blob], convertirWebp ? file.name.replace(/\.[^.]+$/, '') + '.webp' : file.name, {type: salidaMime, lastModified: Date.now()});
    } finally { URL.revokeObjectURL(url); }
  }
  /* IA: Codex GPT-6 | Fecha: 2026-09-24
   * Proposito: obtener una fuente guardada para procesarla por eleccion expresa.
   * Impacto: ambas bibliotecas; solo descarga medios CMS del mismo servidor.
   */
  async function descargarArchivo(url) {
    var parsed = new URL(url, window.location.origin);
    if (parsed.origin !== window.location.origin || !parsed.pathname.startsWith('/assets/media/cms/ecommerce/')) throw new Error('Selecciona una imagen guardada en Media CMS.');
    var response = await fetch(parsed.href, {credentials: 'same-origin', cache: 'no-store'});
    if (!response.ok) throw new Error('No se pudo obtener la imagen guardada.');
    var finalUrl = new URL(response.url || parsed.href, window.location.origin);
    if (finalUrl.origin !== window.location.origin || !finalUrl.pathname.startsWith('/assets/media/cms/ecommerce/')) throw new Error('La imagen guardada redirige fuera de Media CMS.');
    if (Number(response.headers.get('Content-Length') || 0) > 20 * 1024 * 1024) throw new Error('La imagen guardada supera 20 MB. Usa un reemplazo mas ligero.');
    var blob = await response.blob();
    return new File([blob], finalUrl.pathname.split('/').pop(), {type: blob.type});
  }
  /** IA: Codex GPT-6 | Fecha: 2026-09-24. Proposito: optimizar conservando formato; impacto: CMS; contrato: devuelve original si no hay ahorro. */
  function optimizar(file) { return procesar(file, false); }
  /** IA: Codex GPT-6 | Fecha: 2026-09-24. Proposito: convertir explicitamente a WebP; impacto: CMS; contrato: no decide por el usuario si un resultado mayor conviene. */
  function convertirWebp(file) { return procesar(file, true); }
  /** IA: Codex GPT-6 | Fecha: 2026-09-24. Proposito: optimizar una imagen existente; impacto: CMS; contrato: solo mismo servidor y ahorro real. */
  async function optimizarUrl(url) {
    var file = await descargarArchivo(url);
    var result = await optimizar(file);
    if (result === file) throw new Error('La imagen ya tiene un peso adecuado; no se encontraron ahorros con esta optimizacion.');
    return result;
  }
  /** IA: Codex GPT-6 | Fecha: 2026-09-24. Proposito: convertir un medio existente de forma explicita; impacto: CMS; contrato: no persiste ni modifica el original. */
  async function convertirWebpUrl(url) { return convertirWebp(await descargarArchivo(url)); }
  /** IA: Codex GPT-6 | Fecha: 2026-09-24. Proposito: sugerir nombre legible desde texto disponible; impacto: SEO Media; contrato: no inventa descripcion ni agrega palabras clave. */
  function sugerirNombreSeo(texto) {
    return String(texto || '').replace(/\.(jpe?g|png|webp|gif|avif|ico)$/i, '').replace(/^cms_\d{8}_\d{6}_[a-f0-9]+_/i, '')
      .normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').slice(0, 120).replace(/-+$/g, '');
  }
  /** IA: Codex GPT-6 | Fecha: 2026-09-25. Proposito: reconocer AVIF sin asumir posicion de ftyp o marcas; impacto: comprobacion publica; contrato: recorre cajas y marcas declaradas dentro del buffer limitado a 2 MB. */
  function tieneMarcaAvif(buffer) {
    var view = new DataView(buffer), bytes = new Uint8Array(buffer), offset = 0;
    function texto(inicio) { return String.fromCharCode.apply(null, bytes.subarray(inicio, inicio + 4)); }
    while (offset + 8 <= bytes.length) {
      var tamano = view.getUint32(offset), header = 8, tipo = texto(offset + 4);
      if (tamano === 1) {
        if (offset + 16 > bytes.length || view.getUint32(offset + 8) !== 0) return false;
        tamano = view.getUint32(offset + 12); header = 16;
      } else if (tamano === 0) tamano = bytes.length - offset;
      if (tamano < header || tamano > bytes.length - offset) return false;
      if (tipo === 'ftyp') {
        var inicio = offset + header, fin = offset + tamano;
        if (fin - inicio < 8 || (fin - inicio) % 4 !== 0) return false;
        if (texto(inicio) === 'avif' || texto(inicio) === 'avis') return true;
        for (var marca = inicio + 8; marca + 4 <= fin; marca += 4) {
          if (texto(marca) === 'avif' || texto(marca) === 'avis') return true;
        }
      }
      offset += tamano;
    }
    return false;
  }
  /* IA: Codex GPT-6 | Fecha: 2026-09-25
   * Proposito: confirmar archivo guardado y acceso publico real antes de anunciar una carga completa.
   * Impacto: biblioteca/selector CMS; una falla de acceso no deshace ni duplica el guardado.
   * Contrato: Promise<{ok, estado, mensaje, http_status?, hash_verificado?}>; nunca arroja por red,
   * solicita solo el archivo CMS del mismo origen, sin sesion, sin cache ni seguir redirecciones.
   */
  async function verificarDisponibilidad(item) {
    var timer, controller;
    var aviso = 'Guardada en la biblioteca; acceso no confirmado. ';
    function fallo(estado, detalle, status) {
      return {ok: false, estado: estado, mensaje: aviso + detalle + ' No vuelvas a subirla: revisa este aviso y conserva la referencia.', http_status: status || null};
    }
    try {
      var validacion = item && item.validacion_archivo;
      if (!validacion) return fallo('sin_validacion', 'El servidor no devolvio la comprobacion del archivo. Actualiza los archivos del CMS en el servidor.');
      if (validacion.ok !== true) return fallo(validacion.estado || 'no_verificable', validacion.mensaje || 'No fue posible confirmar el archivo o sus permisos en el servidor.');
      var parsed = new URL(item.preview_url || item.url, window.location.origin);
      if (parsed.origin !== window.location.origin || parsed.username || parsed.password || !/^\/assets\/media\/cms\/ecommerce\/[A-Za-z0-9_-][A-Za-z0-9_.-]*\.(jpe?g|png|webp|gif|avif|ico)$/i.test(parsed.pathname)) return fallo('ruta_no_valida', 'La ruta devuelta no pertenece a Media CMS de este servidor.');
      var extension = parsed.pathname.split('.').pop().toLowerCase();
      var esperado = Number(item.bytes || 0);
      if (!Number.isInteger(esperado) || esperado <= 0 || esperado > 2 * 1024 * 1024) return fallo('sin_tamano', 'El servidor no devolvio un peso valido para comprobar la descarga.');
      if (typeof AbortController === 'undefined' || typeof fetch !== 'function') return fallo('navegador', 'Este navegador no permite comprobar el acceso publico.');
      controller = new AbortController();
      parsed.hash = '';
      parsed.searchParams.set('_cms_verificar', Date.now().toString(36) + '-' + Math.random().toString(36).slice(2));
      var tiempo = new Promise(function (resolve, reject) {
        timer = setTimeout(function () { controller.abort(); var error = new Error('Tiempo agotado'); error.name = 'TimeoutError'; reject(error); }, 10000);
      });
      var consulta = (async function () {
        var response = await fetch(parsed.href, {method: 'GET', credentials: 'omit', cache: 'no-store', redirect: 'manual', signal: controller.signal, referrerPolicy: 'no-referrer'});
        var status = Number(response.status || 0);
        if (response.type === 'opaqueredirect' || response.redirected || (status >= 300 && status < 400)) return fallo('redireccion', 'La URL redirige a otra pagina; revisa reglas de acceso o inicio de sesion.', status);
        if (status === 401 || status === 403) return fallo('acceso_denegado', 'El servidor denego el acceso publico (HTTP ' + status + '). Revisa permisos del archivo y reglas de acceso.', status);
        if (status === 404) return fallo('no_encontrada', 'El archivo existe para PHP, pero su URL publica responde 404. Revisa la carpeta publica y las reglas de rutas.', status);
        if (status >= 500) return fallo('error_servidor', 'El servidor fallo al entregar la imagen (HTTP ' + status + ').', status);
        if (!response.ok || status !== 200) return fallo('http_invalido', 'La URL no devolvio la imagen completa (HTTP ' + status + ').', status);
        var finalUrl = new URL(response.url || parsed.href, window.location.origin);
        if (finalUrl.origin !== parsed.origin || finalUrl.pathname !== parsed.pathname) return fallo('redireccion', 'La respuesta proviene de otra ruta; revisa las redirecciones.', status);
        var mime = String(response.headers.get('Content-Type') || '').split(';')[0].trim().toLowerCase();
        var mimes = {jpg: ['image/jpeg'], jpeg: ['image/jpeg'], png: ['image/png'], gif: ['image/gif'], webp: ['image/webp'], avif: ['image/avif'], ico: ['image/x-icon', 'image/vnd.microsoft.icon', 'image/ico']};
        if (mimes[extension].indexOf(mime) === -1) return fallo('contenido_no_imagen', mime.indexOf('text/html') === 0 ? 'La URL devuelve una pagina HTML en lugar de la imagen; puede ser el inicio de sesion o una pagina de error.' : 'El tipo de contenido entregado no corresponde al formato de la imagen. Revisa la configuracion de archivos estaticos.', status);
        var longitud = Number(response.headers.get('Content-Length') || 0);
        if (longitud > 2 * 1024 * 1024) return fallo('contenido_distinto', 'La URL devuelve un archivo mas grande que el guardado.', status);
        var buffer = await response.arrayBuffer();
        if (buffer.byteLength !== esperado) return fallo('contenido_distinto', 'El peso descargado no coincide con el archivo guardado. Revisa cache, rutas o reemplazos.', status);
        var bytes = new Uint8Array(buffer), firma = String.fromCharCode.apply(null, bytes.subarray(0, 32));
        var firmas = {
          jpg: bytes[0] === 255 && bytes[1] === 216 && bytes[2] === 255,
          png: firma.slice(0, 8) === '\x89PNG\r\n\x1a\n',
          gif: /^(GIF87a|GIF89a)/.test(firma),
          webp: firma.slice(0, 4) === 'RIFF' && firma.slice(8, 12) === 'WEBP',
          avif: extension === 'avif' && tieneMarcaAvif(buffer),
          ico: bytes.length >= 6 && bytes[0] === 0 && bytes[1] === 0 && bytes[2] === 1 && bytes[3] === 0
        };
        if (!firmas[extension === 'jpeg' ? 'jpg' : extension]) return fallo('contenido_distinto', 'La respuesta no contiene la firma del formato guardado.', status);
        var hashVerificado = false;
        if (/^[a-f0-9]{64}$/i.test(item.hash_sha256 || '') && window.crypto && window.crypto.subtle) {
          var digest = await window.crypto.subtle.digest('SHA-256', buffer);
          var hash = Array.from(new Uint8Array(digest)).map(function (byte) { return byte.toString(16).padStart(2, '0'); }).join('');
          if (hash !== item.hash_sha256.toLowerCase()) return fallo('contenido_distinto', 'El contenido descargado no coincide con la imagen guardada. Revisa cache o reglas de rutas.', status);
          hashVerificado = true;
        }
        return {ok: true, estado: 'verificado', http_status: status, hash_verificado: hashVerificado, mensaje: 'Archivo confirmado en el servidor y accesible sin iniciar sesion. ' + (hashVerificado ? 'Contenido verificado.' : 'Formato y peso verificados; comparacion completa de contenido no disponible en este navegador.')};
      }());
      return await Promise.race([consulta, tiempo]);
    } catch (error) {
      return fallo(error && (error.name === 'AbortError' || error.name === 'TimeoutError') ? 'tiempo_agotado' : 'conexion', error && (error.name === 'AbortError' || error.name === 'TimeoutError') ? 'La comprobacion tardo mas de 10 segundos.' : 'No se pudo completar la comprobacion publica. Revisa la conexion y las reglas del servidor.');
    } finally { if (timer) clearTimeout(timer); }
  }
  window.CmsMediaTools = {optimizar: optimizar, optimizarUrl: optimizarUrl, convertirWebp: convertirWebp, convertirWebpUrl: convertirWebpUrl, sugerirNombreSeo: sugerirNombreSeo, verificarDisponibilidad: verificarDisponibilidad};
}());
