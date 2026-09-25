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
  window.CmsMediaTools = {optimizar: optimizar, optimizarUrl: optimizarUrl, convertirWebp: convertirWebp, convertirWebpUrl: convertirWebpUrl, sugerirNombreSeo: sugerirNombreSeo};
}());
