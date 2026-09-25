/*
 * IA: Codex GPT-6 | Fecha: 2026-09-24
 * Proposito: administrar formatos originales, peso, usos y reemplazo de medios CMS.
 * Impacto: biblioteca compartida de Home, Global, categorias y contenido editorial.
 * Contrato: servidor autoriza usos y acciones; reemplazo conserva ID y resuelve URLs anteriores.
 * Solo temporales legados se guardan en el navegador; listado servidor autoritativo.
 */
(function () {
  "use strict";
  var STORAGE_KEY = "erp_cms_media_biblioteca_local_v1";
  var MAX_BYTES = 2 * 1024 * 1024;
  var EXTENSIONES = ["jpg", "jpeg", "png", "webp", "gif", "avif", "ico"];
  var estado = {
    items: [], activo: "", archivoPendiente: null, ocupado: false,
    permisos: window.CMS_MEDIA_PERMISOS || { editar: false, publicar: false },
    usos: {}, cargaListado: 0, previewVersion: {}, previewTemporal: ""
  };

  /** IA: Codex GPT-6 | Fecha: 2026-09-24. Proposito: iniciar biblioteca; impacto: UI; contrato: consulta sin escrituras. */
  document.addEventListener("DOMContentLoaded", function () {
    cargarLocal(); bindEventos(); aplicarPermisos(); renderTodo();
    cargarPreflightServidor(); cargarListadoServidor();
  });

  /** IA: Codex GPT-6 | Fecha: 2026-09-24. Proposito: conectar controles; impacto: biblioteca; contrato: acciones sobre seleccion vigente. */
  function bindEventos() {
    on("cms_media_archivo", "change", prepararArchivo);
    on("cms_media_optimizar", "change", prepararArchivo);
    on("cms_media_webp", "change", prepararArchivo);
    on("cms_media_nombre_seo", "input", renderNombreAlta);
    on("cms_media_sugerir_nombre", "click", function () { sugerirNombre("cms_media_nombre_seo", valor("cms_media_alt") || (estado.archivoPendiente || {}).name); renderNombreAlta(); });
    on("cms_media_agregar", "click", subirArchivoServidor);
    on("cms_media_buscar", "input", renderBibliotecaMedia);
    on("cms_media_filtro_uso", "change", renderBibliotecaMedia);
    on("cms_media_orden", "change", renderBibliotecaMedia);
    on("cms_media_recargar", "click", function () { cargarListadoServidor(); });
    on("cms_media_limpiar_temporales", "click", limpiarTemporalesLocales);
    on("cms_media_limpiar_archivados", "click", limpiarArchivados);
    on("cms_media_biblioteca", "click", function (event) {
      var button = event.target.closest("[data-media-action]");
      if (button) ejecutarAccionMedia(button.dataset.mediaAction, button.dataset.mediaId);
      else {
        var card = event.target.closest("[data-media-id]");
        if (card && !estado.ocupado) seleccionarMedia(card.dataset.mediaId);
      }
    });
    on("cms_media_biblioteca", "keydown", function (event) {
      if (!estado.ocupado && event.target.matches(".cms-media-card") && (event.key === "Enter" || event.key === " ")) {
        event.preventDefault(); seleccionarMedia(event.target.dataset.mediaId);
      }
    });
    on("cms_media_detalle", "click", function (event) {
      var button = event.target.closest("[data-media-detail-action]");
      if (button) ejecutarAccionMedia(button.dataset.mediaDetailAction, estado.activo);
    });
    on("cms_media_detalle", "input", renderNombreDetalle);
    on("cms_media_detalle", "change", renderNombreDetalle);
  }

  /** IA: Codex GPT-6 | Fecha: 2026-09-24. Proposito: preparar captura sin convertir; impacto: alta; contrato: valida extension/peso segun opcion explicita. */
  function prepararArchivo() {
    var input = $("cms_media_archivo"), file = input && input.files ? input.files[0] : null;
    estado.archivoPendiente = null;
    if (estado.previewTemporal) URL.revokeObjectURL(estado.previewTemporal);
    estado.previewTemporal = "";
    if (!file) return;
    var error = validarArchivo(file, marcado("cms_media_optimizar") || marcado("cms_media_webp"));
    if (error) { setEstado(error, "danger"); return; }
    estado.archivoPendiente = file;
    if (!valor("cms_media_nombre_seo")) sugerirNombre("cms_media_nombre_seo", valor("cms_media_alt") || file.name);
    renderNombreAlta();
    estado.previewTemporal = URL.createObjectURL(file);
    setEstado(file.name + " · " + formatoBytes(file.size) + " · " + extension(file.name).toUpperCase(), "info");
    var visual = $("ecom_cms_visual");
    if (visual) visual.innerHTML = '<div class="fw-bold mb-2">Vista previa del archivo a subir</div><img class="ecom-cms-preview-img" src="' + escapeAttr(estado.previewTemporal) + '" alt="' + escapeAttr(file.name) + '">';
  }

  /** IA: Codex GPT-6 | Fecha: 2026-09-24. Proposito: validar captura; impacto: alta/reemplazo; contrato: original2MB, fuente optimizable20MB; servidor valida contenido. */
  function validarArchivo(file, optimizar) {
    var ext = extension(file.name);
    if (EXTENSIONES.indexOf(ext) === -1) return "Selecciona JPG, JPEG, PNG, WebP, GIF, AVIF o ICO.";
    if (!file.size) return "El archivo esta vacio. Selecciona otra imagen.";
    if (optimizar && !extensionOptimizable(ext)) return "Este formato se conserva original. Desmarca Reducir peso y Convertir a WebP para subirlo.";
    if (file.size > (optimizar ? 20 * 1024 * 1024 : MAX_BYTES)) return optimizar ? "La imagen fuente supera 20 MB." : "Supera 2 MB. Activa Optimizar para JPG, PNG o WebP estaticos, o elige un archivo menor.";
    return "";
  }

  /** IA: Codex GPT-6 | Fecha: 2026-09-25. Proposito: subir y verificar archivo/acceso publico; impacto: biblioteca; contrato: un fallo de acceso conserva el guardado y muestra advertencia. */
  async function subirArchivoServidor() {
    if (estado.ocupado || !estado.permisos.editar) return;
    var file = estado.archivoPendiente;
    if (!file) { setEstado("Selecciona un archivo valido.", "warning"); return; }
    var alt = valor("cms_media_alt").trim();
    if (!alt) { setEstado("Escribe una descripcion accesible en Alt text.", "warning"); $("cms_media_alt").focus(); return; }
    establecerOcupado(true);
    try {
      setEstado(marcado("cms_media_optimizar") ? "Optimizando imagen..." : "Subiendo imagen...", "info");
      var convertir = marcado("cms_media_webp");
      if ((convertir || marcado("cms_media_optimizar")) && !window.CmsMediaTools) throw new Error("No se pudo cargar la herramienta de imagenes. Actualiza la pagina.");
      var archivoFinal = convertir ? await window.CmsMediaTools.convertirWebp(file) : marcado("cms_media_optimizar") ? await optimizarArchivo(file) : file;
      if (archivoFinal.size > MAX_BYTES) throw new Error("La imagen final supera 2 MB. Reduce sus dimensiones o selecciona un archivo menor.");
      if (convertir && !window.confirm("Subir como WebP?\n\n" + resumenAhorro(file.size, archivoFinal.size) + (archivoFinal.size >= file.size ? "\nLa version WebP no reduce el peso frente al original." : "") + "\nPuede ajustar dimensiones y calidad.")) return;
      var data = new FormData();
      data.append("archivo", archivoFinal); data.append("alt", alt);
      if (valor("cms_media_nombre_seo").trim()) data.append("nombre_seo", valor("cms_media_nombre_seo").trim());
      data.append("uso", valor("cms_media_uso") || "home"); data.append("tipo", valor("cms_media_tipo") || "banner");
      var json = await enviarMedia("/cms/media_admin_subir_erp", data);
      var item = normalizarItemServidor(json.depurar && (json.depurar.item || json.depurar));
      if (item) { mezclarItemsServidor([item]); estado.activo = item.id; }
      estado.archivoPendiente = null; $("cms_media_archivo").value = ""; $("cms_media_alt").value = "";
      $("cms_media_nombre_seo").value = ""; renderNombreAlta();
      if (estado.previewTemporal) URL.revokeObjectURL(estado.previewTemporal);
      estado.previewTemporal = "";
      if ($("ecom_cms_visual")) $("ecom_cms_visual").innerHTML = "";
      renderTodo();
      setEstado("Imagen guardada. Comprobando archivo y acceso publico...", "info");
      var comprobacion = await comprobarMediaGuardada(item);
      var avisoListado = await refrescarMediaTrasGuardar();
      setEstado((comprobacion.ok ? (json.mensaje || "Imagen agregada a biblioteca.") + " " + resumenAhorro(file.size, archivoFinal.size) + " " : "") + comprobacion.mensaje + (json.tipo === "warning" && json.mensaje ? " " + json.mensaje : "") + avisoListado, comprobacion.ok && !avisoListado && json.tipo !== "warning" ? "success" : "warning");
    } catch (error) { setEstado(error.message || "No se pudo subir la imagen.", "danger"); }
    finally { establecerOcupado(false); }
  }

  /** IA: Codex GPT-6 | Fecha: 2026-09-24. Proposito: usar optimizacion compartida; impacto: archivos; contrato: conserva formato y rechaza animaciones. */
  function optimizarArchivo(file) {
    if (!window.CmsMediaTools) return Promise.reject(new Error("No se pudo cargar la herramienta de optimizacion. Actualiza la pagina."));
    return window.CmsMediaTools.optimizar(file);
  }

  /** IA: Codex GPT-6 | Fecha: 2026-09-24. Proposito: mostrar ahorro real; impacto: confirmacion; contrato: compara bytes efectivos. */
  function resumenAhorro(antes, despues) {
    return despues < antes ? "Peso: " + formatoBytes(antes) + " → " + formatoBytes(despues) + "." : "Peso: " + formatoBytes(despues) + ".";
  }

  /** IA: Codex GPT-6 | Fecha: 2026-09-24. Proposito: refrescar UI; impacto: biblioteca; contrato: conserva seleccion. */
  function renderTodo() { renderBibliotecaMedia(); renderDetalle(); }

  /** IA: Codex GPT-6 | Fecha: 2026-09-24. Proposito: encontrar imagenes pesadas; impacto: biblioteca; contrato: presenta metadata y acceso a detalle. */
  function renderBibliotecaMedia() {
    var node = $("cms_media_biblioteca");
    if (!node) return;
    var items = filtrarItems(), peso = items.reduce(function (total, item) { return total + (Number(item.bytes) || 0); }, 0);
    setText("cms_media_resumen", items.length + " imagenes · " + formatoBytes(peso) + " en esta vista");
    if (!items.length) { node.innerHTML = '<div class="text-muted">No hay imagenes que coincidan con la busqueda.</div>'; return; }
    node.innerHTML = items.map(function (item) {
      return '<div class="cms-media-card ' + (item.id === estado.activo ? "is-active" : "") + '" tabindex="0" aria-label="Ver detalle de ' + escapeAttr(item.nombre) + '" data-media-id="' + escapeAttr(item.id) + '">' +
        '<img class="cms-media-thumb" loading="lazy" src="' + escapeAttr(previewUrl(item)) + '" alt="' + escapeAttr(item.alt) + '">' +
        '<div class="p-3"><div class="fw-bold text-truncate" title="' + escapeAttr(item.nombre) + '">' + escapeHtml(item.nombre) + '</div>' +
        '<div class="text-muted fs-8 text-truncate">' + escapeHtml(item.alt) + '</div>' +
        '<div class="fw-semibold mt-2">' + escapeHtml(formatoBytes(item.bytes)) + ' · ' + escapeHtml(extensionMedia(item).toUpperCase()) + '</div>' +
        '<div class="text-muted fs-8">' + escapeHtml(dimensiones(item)) + ' · ' + escapeHtml(labelUsoMedia(item.uso)) + '</div>' +
        (!esItemServidor(item) ? '<span class="badge badge-light-warning mt-2">Temporal local' + (item.estatus === "archivado" ? " archivado" : "") + '</span>' : '') +
        '<div class="cms-media-actions mt-3"><button class="btn btn-sm btn-light-primary" type="button" data-media-action="detalle" data-media-id="' + escapeAttr(item.id) + '">Ver detalle</button>' +
        '<button class="btn btn-sm btn-light" type="button" data-media-action="copiar" data-media-id="' + escapeAttr(item.id) + '">Copiar referencia</button></div></div></div>';
    }).join("");
  }

  /** IA: Codex GPT-6 | Fecha: 2026-09-24. Proposito: mostrar metadata/usos y acciones; impacto: biblioteca; contrato: reemplazo requiere editar+publicar. */
  function renderDetalle() {
    var item = mediaActiva(), node = $("cms_media_detalle");
    if (!node) return;
    if (!item) { node.innerHTML = '<div class="text-muted">Selecciona una imagen para ver detalles.</div>'; return; }
    var servidor = esItemServidor(item), puedeReemplazar = servidor && estado.permisos.editar && estado.permisos.publicar;
    node.innerHTML = '<div class="row g-5"><div class="col-lg-4"><img class="cms-media-detail-img" src="' + escapeAttr(previewUrl(item)) + '" alt="' + escapeAttr(item.alt) + '"></div><div class="col-lg-8">' +
      '<h4 class="fw-bold mb-2 text-break">' + escapeHtml(item.nombre) + '</h4><div class="text-muted fs-7 mb-4">' + escapeHtml(item.alt) + '</div>' +
      '<div class="cms-media-meta fs-7 mb-4">' + meta("Uso / tipo", labelUsoMedia(item.uso) + " / " + labelTipoMedia(item.tipo)) +
      meta("Formato", extensionMedia(item).toUpperCase()) + meta("Peso", formatoBytes(item.bytes)) + meta("Dimensiones", dimensiones(item)) +
      meta("Origen", servidor ? "Biblioteca compartida" : "Temporal de este navegador") + meta("Creado", String(item.creado_en || "").substring(0, 10)) + '</div>' +
      '<div class="cms-media-actions"><button class="btn btn-sm btn-light-primary" type="button" data-media-detail-action="copiar">Copiar referencia</button>' +
      (servidor ? '<button class="btn btn-sm btn-light" type="button" data-media-detail-action="usos">Actualizar usos</button>' : '<button class="btn btn-sm btn-light-warning" type="button" data-media-detail-action="archivar">' + (item.estatus === "archivado" ? "Restaurar temporal" : "Archivar temporal") + '</button>') +
      ((!servidor || estado.permisos.editar) ? '<button class="btn btn-sm btn-light-danger" type="button" data-media-detail-action="eliminar">Eliminar' + (!servidor ? " temporal" : "") + '</button>' : '') + '</div>' +
      (servidor ? '<div class="mt-5"><h5>Donde se utiliza</h5><div id="cms_media_usos" aria-live="polite">Consultando usos guardados...</div></div>' : '') +
      (puedeReemplazar ? '<div class="border rounded p-4 mt-5"><h5>Nombre, descripcion y archivo</h5><p class="text-muted fs-7">Puedes cambiar el nombre o el formato. Las referencias anteriores siguen funcionando. El reemplazo actualiza todos sus usos, incluidos los publicados.</p>' +
        '<label for="cms_media_detalle_nombre_seo" class="form-label">Nombre del archivo para SEO (sin extension)</label><div class="input-group"><input class="form-control" id="cms_media_detalle_nombre_seo" maxlength="120" value="' + escapeAttr(item.nombre_seo || nombreSugerido(item.nombre)) + '" placeholder="collares-para-perros"><button class="btn btn-light" type="button" data-media-detail-action="sugerir">Sugerir desde descripcion</button></div><div class="text-muted fs-7 mt-2" id="cms_media_detalle_nombre_preview"></div>' +
        '<label for="cms_media_detalle_alt" class="form-label mt-3">Descripcion accesible (Alt de biblioteca)</label><input class="form-control" id="cms_media_detalle_alt" value="' + escapeAttr(item.alt) + '"><div class="text-muted fs-7 mt-2">Describe lo visible con palabras naturales. Este Alt no modifica las descripciones de las paginas ya publicadas.</div>' +
        '<label for="cms_media_reemplazo" class="form-label mt-4">Nuevo archivo (opcional; puede tener otro formato)</label><input class="form-control" type="file" id="cms_media_reemplazo" accept=".jpg,.jpeg,.png,.webp,.gif,.avif,.ico">' +
        '<label class="form-check form-check-custom form-check-solid mt-3"><input class="form-check-input" id="cms_media_reemplazo_optimizar" type="checkbox"><span class="form-check-label">Reducir peso del nuevo archivo conservando formato</span></label>' +
        '<label class="form-check form-check-custom form-check-solid mt-3"><input class="form-check-input" id="cms_media_reemplazo_webp" type="checkbox"><span class="form-check-label">Convertir el nuevo archivo a WebP</span></label><div class="text-muted fs-7 mt-2">Opciones para JPG, PNG y WebP estaticos. Fuente hasta 20 MB; resultado hasta 2 MB. Convertir a WebP ajusta calidad/dimensiones; revisa el peso antes de guardar. ICO y favicon conservan su formato salvo reemplazo manual.</div>' +
        '<div class="cms-media-actions mt-4"><button class="btn btn-sm btn-warning" type="button" data-media-detail-action="reemplazar">Revisar reemplazo</button>' +
        '<button class="btn btn-sm btn-light-primary" type="button" data-media-detail-action="guardar">Guardar nombre y descripcion</button>' +
        (extensionOptimizable(extensionMedia(item)) ? '<button class="btn btn-sm btn-light-primary" type="button" data-media-detail-action="optimizar">Reducir peso actual</button>' : '') +
        (extensionOptimizable(extensionMedia(item)) && item.tipo !== "favicon" ? '<button class="btn btn-sm btn-light-primary" type="button" data-media-detail-action="webp">Convertir actual a WebP</button>' : '') + '</div></div>' : '') + '</div></div>';
    renderNombreDetalle();
    if (servidor) {
      if (estado.usos[item.id]) renderUsos(item.id, estado.usos[item.id]);
      else consultarUsos(item).catch(function (error) { mostrarErrorUsos(item.id, error); });
    }
    establecerOcupado(estado.ocupado);
  }

  /** IA: Codex GPT-6 | Fecha: 2026-09-24. Proposito: cargar permisos sin detalles internos; impacto: UI; contrato: falla cerrado. */
  async function cargarPreflightServidor() {
    try {
      var json = await consultarJson("/cms/media_admin_preflight_erp"), data = json.depurar || {};
      if (data.permisos) estado.permisos = data.permisos;
      aplicarPermisos(); renderDetalle();
      setText("cms_media_preflight", estado.permisos.editar ? "Actualizar el nombre, formato o archivo de imagenes existentes requiere permiso para publicar." : "Solo lectura: puedes consultar imagenes y copiar sus referencias.");
    } catch (error) { setText("cms_media_preflight", "No se pudieron verificar los permisos. Actualiza la pagina para volver a intentarlo."); }
  }

  /** IA: Codex GPT-6 | Fecha: 2026-09-24. Proposito: ocultar acciones no autorizadas; impacto: alta; contrato: permisos recibidos del servidor. */
  function aplicarPermisos() { var node = $("cms_media_alta_panel"); if (node) node.hidden = !estado.permisos.editar; }

  /** IA: Codex GPT-6 | Fecha: 2026-09-24. Proposito: consultar biblioteca completa; impacto: busqueda/peso; contrato: paginacion autoritativa sin cache de eliminados. */
  async function cargarListadoServidor(silencioso) {
    var carga = ++estado.cargaListado;
    if (!silencioso) setEstado("Actualizando biblioteca...", "info");
    try {
      var items = [], offset = 0, data;
      do {
        var json = await consultarJson("/cms/media_admin_listar_erp?limite=120&offset=" + offset + "&orden=peso");
        if (carga !== estado.cargaListado) return;
        data = json.depurar || {};
        if (!data.persistencia_real) throw new Error("La biblioteca compartida no esta disponible; solo se muestran temporales locales.");
        var pagina = Array.isArray(data.items) ? data.items : [];
        items = items.concat(pagina.map(normalizarItemServidor).filter(Boolean)); offset += pagina.length;
        if (data.hay_mas && !pagina.length) throw new Error("La biblioteca devolvio una pagina vacia. Actualiza para volver a intentar.");
      } while (data.hay_mas);
      reconciliarItemsServidor(items); estado.usos = {}; guardarLocal(); renderTodo();
      if (!silencioso) setEstado("Biblioteca actualizada: " + items.length + " imagenes compartidas.", "info");
    } catch (error) {
      if (carga === estado.cargaListado) setEstado(error.message || "No se pudo consultar la biblioteca.", "danger");
      if (silencioso) throw error;
    }
  }

  /** IA: Codex GPT-6 | Fecha: 2026-09-24. Proposito: consultar dependencias reales; impacto: proteccion CMS; contrato: incluye todos los estados persistidos. */
  async function consultarUsos(item) {
    var json = await consultarJson("/cms/media_admin_usos_erp?id_media_archivo=" + encodeURIComponent(item.media_id)), data = json.depurar || {};
    if (!Array.isArray(data.usos) || typeof data.puede_eliminar !== "boolean") throw new Error("No se pudo verificar donde se utiliza la imagen. Actualiza sus usos antes de continuar.");
    estado.usos[item.id] = data; renderUsos(item.id, data); return data;
  }

  /** IA: Codex GPT-6 | Fecha: 2026-09-24. Proposito: mostrar referencias persistidas; impacto: detalle; contrato: escapa todo texto recibido. */
  function renderUsos(id, data) {
    var node = $("cms_media_usos");
    if (!node || estado.activo !== id) return;
    var usos = data.usos || [];
    node.innerHTML = '<div class="mb-2">' + escapeHtml(String(data.total == null ? usos.length : data.total)) + ' usos guardados. ' + (data.puede_eliminar ? 'Se puede eliminar si continua sin usos.' : 'Retira sus referencias antes de eliminar.') + '</div>' +
      (usos.length ? '<ul class="mb-0">' + usos.map(function (uso) { return '<li>' + escapeHtml([uso.origen, uso.referencia, uso.estado].filter(Boolean).join(" · ")) + '</li>'; }).join("") + '</ul>' : '');
  }

  /** IA: Codex GPT-6 | Fecha: 2026-09-24. Proposito: informar fallo sin habilitar borrado; impacto: detalle; contrato: solo seleccion vigente. */
  function mostrarErrorUsos(id, error) { if (estado.activo === id) setText("cms_media_usos", error.message || "No se pudieron consultar los usos."); }

  /** IA: Codex GPT-6 | Fecha: 2026-09-24. Proposito: separar acciones servidor/local; impacto: biblioteca; contrato: archivar no simula escritura servidor. */
  function ejecutarAccionMedia(accion, id) {
    var item = buscarMedia(id);
    if (!item || estado.ocupado) return;
    if (accion === "detalle") { seleccionarMedia(id); $("cms_media_detalle").scrollIntoView({ behavior: "smooth", block: "start" }); return; }
    if (accion === "copiar") { copiarReferencia(item); return; }
    if (accion === "usos" && esItemServidor(item)) { consultarUsos(item).catch(function (error) { mostrarErrorUsos(id, error); }); return; }
    if (accion === "sugerir") { sugerirNombre("cms_media_detalle_nombre_seo", valor("cms_media_detalle_alt") || item.nombre); renderNombreDetalle(); return; }
    if (["reemplazar", "optimizar", "webp", "guardar"].indexOf(accion) !== -1) { reemplazarMediaServidor(item, accion); return; }
    if (esItemServidor(item)) { if (accion === "eliminar" && estado.permisos.editar) eliminarMediaServidor(item); return; }
    if (accion === "archivar") item.estatus = item.estatus === "archivado" ? "activo" : "archivado";
    if (accion === "eliminar") {
      if (!window.confirm('Quitar el temporal "' + item.nombre + '" solo de este navegador?')) return;
      quitarItem(item.id);
    }
    guardarLocal(); renderTodo();
  }

  /** IA: Codex GPT-6 | Fecha: 2026-09-24. Proposito: eliminar tras revisar usos; impacto: referencias CMS; contrato: servidor revalida dependencias al ejecutar POST. */
  async function eliminarMediaServidor(item) {
    if (!estado.permisos.editar || estado.ocupado) return;
    establecerOcupado(true);
    try {
      setEstado("Revisando usos antes de eliminar...", "info");
      var usos = await consultarUsos(item);
      if (!usos.puede_eliminar) { setEstado("Esta imagen tiene usos guardados. Retira sus referencias antes de eliminarla.", "warning"); seleccionarMedia(item.id); return; }
      if (!window.confirm('Eliminar definitivamente "' + item.nombre + '" (' + formatoBytes(item.bytes) + ')?\n\nNo se encontraron usos guardados. Se eliminara el archivo de la biblioteca compartida.')) return;
      var data = new FormData(); data.append("id_media_archivo", item.media_id);
      var json = await enviarMedia("/cms/media_admin_eliminar_erp", data);
      quitarItem(item.id); renderTodo(); await cargarListadoServidor(true);
      setEstado(json.mensaje || "Imagen eliminada.", "success");
    } catch (error) { setEstado(error.message || "No se pudo eliminar la imagen.", "danger"); }
    finally { establecerOcupado(false); }
  }

  /** IA: Codex GPT-6 | Fecha: 2026-09-25. Proposito: cambiar nombre/formato y verificar acceso; impacto: CMS; contrato: ID/referencias estables, advertir acceso fallido sin revertir un guardado confirmado. */
  async function reemplazarMediaServidor(item, accion) {
    if (estado.ocupado || !esItemServidor(item) || !estado.permisos.editar || !estado.permisos.publicar) return;
    var input = $("cms_media_reemplazo"), file = input && input.files ? input.files[0] : null;
    var soloOptimizar = accion === "optimizar", soloNombre = accion === "guardar", actualWebp = accion === "webp";
    var convertir = actualWebp || (!soloNombre && !soloOptimizar && marcado("cms_media_reemplazo_webp"));
    var optimizar = soloOptimizar || marcado("cms_media_reemplazo_optimizar");
    var nombreSeo = valor("cms_media_detalle_nombre_seo").trim(), alt = valor("cms_media_detalle_alt").trim();
    if (!nombreSeo || !nombreSugerido(nombreSeo)) { setEstado("Escribe un nombre descriptivo para el archivo.", "warning"); return; }
    if (!alt) { setEstado("Escribe una descripcion accesible para la biblioteca.", "warning"); return; }
    if (soloNombre) file = null;
    if (!soloOptimizar && !actualWebp && !soloNombre) {
      if (!file) { setEstado("Selecciona el nuevo archivo para reemplazar esta imagen.", "warning"); return; }
      var error = validarArchivo(file, optimizar || convertir);
      if (error) { setEstado(error, "warning"); return; }
    }
    establecerOcupado(true);
    try {
      setEstado(optimizar ? "Preparando imagen optimizada..." : "Revisando reemplazo...", "info");
      if (!soloNombre && (convertir || optimizar) && !window.CmsMediaTools) throw new Error("No se pudo cargar la herramienta de imagenes. Actualiza la pagina.");
      if (actualWebp) {
        file = await window.CmsMediaTools.convertirWebpUrl(item.preview_url || item.url);
      } else if (soloOptimizar) {
        if (!window.CmsMediaTools) throw new Error("No se pudo cargar la herramienta de optimizacion. Actualiza la pagina.");
        file = await window.CmsMediaTools.optimizarUrl(item.url, item.nombre_archivo || item.nombre);
        if (file.size >= item.bytes) { setEstado("No se obtuvo un archivo mas ligero. La imagen original se conserva.", "info"); return; }
      } else if (!soloNombre && convertir) file = await window.CmsMediaTools.convertirWebp(file);
      else if (!soloNombre && optimizar) file = await optimizarArchivo(file);
      if (file && file.size > MAX_BYTES) throw new Error("El archivo final supera 2 MB. Elige una imagen menor o reduce sus dimensiones.");
      var usos = await consultarUsos(item);
      var detalle = (usos.usos || []).slice(0, 5).map(function (uso) { return [uso.origen, uso.referencia, uso.estado].filter(Boolean).join(" · "); }).join("\n");
      var aviso = 'Guardar cambios de "' + item.nombre + '"?\n\nNombre: ' + nombreSugerido(nombreSeo) + '.' + (file ? extension(file.name) : extensionMedia(item)) + '\n' + (file ? resumenAhorro(item.bytes, file.size) : 'Se conserva el archivo actual.') + (convertir && file.size >= item.bytes ? '\nWebP no reduce el peso frente a la imagen actual.' : '') + '\nLas referencias anteriores siguen funcionando. El reemplazo afecta todos sus usos, incluidos los publicados. El Alt solo cambia en biblioteca.\n\nUsos guardados: ' + Number(usos.total || 0) + (detalle ? "\n" + detalle : "");
      if (!window.confirm(aviso)) { setEstado("Cambio cancelado. La imagen original se conserva.", "info"); return; }
      var data = new FormData(); data.append("id_media_archivo", item.media_id);
      if (file) data.append("archivo", file);
      // En legacy el nombre visible puede ser una sugerencia aun no persistida; el servidor decide si hay cambio real.
      data.append("nombre_seo", nombreSeo);
      if (alt !== item.alt) data.append("alt", alt);
      setEstado("Actualizando imagen...", "info");
      var json = await enviarMedia("/cms/media_admin_reemplazar_erp", data);
      estado.previewVersion[item.id] = Date.now();
      var actualizado = normalizarItemServidor(json.depurar && (json.depurar.item || json.depurar));
      if (actualizado) mezclarItemsServidor([actualizado]);
      setEstado("Cambios guardados. Comprobando archivo y acceso publico...", "info");
      var comprobacion = await comprobarMediaGuardada(actualizado);
      var avisoListado = await refrescarMediaTrasGuardar();
      setEstado((comprobacion.ok ? (json.mensaje || "Imagen actualizada conservando sus referencias.") + (file ? " " + resumenAhorro(item.bytes, file.size) : "") + " " : "") + comprobacion.mensaje + (json.tipo === "warning" && json.mensaje ? " " + json.mensaje : "") + avisoListado, comprobacion.ok && !avisoListado && json.tipo !== "warning" ? "success" : "warning");
    } catch (error) { setEstado(error.message || "No se pudo actualizar la imagen.", "danger"); }
    finally { establecerOcupado(false); }
  }

  /** IA: Codex GPT-6 | Fecha: 2026-09-25. Proposito: comprobar acceso despues del guardado; impacto: mensajes CMS; contrato: helper faltante advierte sin anunciar fallo de escritura. */
  function comprobarMediaGuardada(item) {
    return window.CmsMediaTools && window.CmsMediaTools.verificarDisponibilidad ? window.CmsMediaTools.verificarDisponibilidad(item) : Promise.resolve({ok: false, mensaje: "Guardada en la biblioteca; acceso no confirmado. Actualiza la pagina para cargar la herramienta de comprobacion. No vuelvas a subirla."});
  }

  /** IA: Codex GPT-6 | Fecha: 2026-09-25. Proposito: refrescar sin confundir un fallo de listado con fallo al guardar; impacto: biblioteca; contrato: conserva el item devuelto por POST. */
  async function refrescarMediaTrasGuardar() {
    try { await cargarListadoServidor(true); return ""; }
    catch (error) { return " Los cambios estan guardados, pero no se pudo actualizar el listado. Usa Recargar biblioteca; no repitas la carga."; }
  }

  /** IA: Codex GPT-6 | Fecha: 2026-09-24. Proposito: unificar respuestas autenticadas; impacto: endpoints media; contrato: no muestra HTML tecnico ante errores. */
  async function consultarJson(url, opciones) {
    var response = await fetch(url, Object.assign({ credentials: "same-origin", cache: "no-store", headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" } }, opciones || {}));
    var json;
    try { json = await response.json(); } catch (error) { throw new Error("No se pudo leer la respuesta. Verifica tu sesion y vuelve a intentar."); }
    if (!response.ok || !json || json.error) throw new Error(json && json.mensaje ? json.mensaje : "No se pudo completar la operacion.");
    return json;
  }

  /** IA: Codex GPT-6 | Fecha: 2026-09-24. Proposito: enviar CSRF en mutaciones; impacto: endpoints media; contrato: FormData mantiene bytes del archivo. */
  function enviarMedia(url, data) {
    data.append("_csrf", window.ERP_CSRF_TOKEN || "");
    return consultarJson(url, { method: "POST", body: data, headers: { "X-CSRF-Token": window.ERP_CSRF_TOKEN || "", Accept: "application/json", "X-Requested-With": "XMLHttpRequest" } });
  }

  /** IA: Codex GPT-6 | Fecha: 2026-09-24. Proposito: impedir duplicados durante cambios; impacto: UX; contrato: bloqueo temporal de controles. */
  function establecerOcupado(ocupado) {
    estado.ocupado = ocupado;
    document.querySelectorAll("#cms_media_alta_panel input, #cms_media_alta_panel select, #cms_media_alta_panel button, #cms_media_detalle input, #cms_media_detalle button, #cms_media_recargar").forEach(function (node) { node.disabled = ocupado; });
  }

  /** IA: Codex GPT-6 | Fecha: 2026-09-24. Proposito: copiar referencia estable; impacto: integraciones; contrato: nunca copia URL de preview temporal. */
  async function copiarReferencia(item) {
    var payload = JSON.stringify({ media_id: item.media_id || item.id, codigo: item.codigo || "", url: item.url, alt: item.alt, uso: item.uso, tipo: item.tipo }, null, 2);
    try {
      if (!navigator.clipboard) throw new Error("Clipboard unavailable");
      await navigator.clipboard.writeText(payload); setEstado("Referencia copiada.", "success");
    } catch (error) { window.prompt("Copia la referencia de esta imagen:", payload); }
  }

  /** IA: Codex GPT-6 | Fecha: 2026-09-25. Proposito: adaptar metadata y diagnostico; impacto: biblioteca; contrato: conserva validacion del archivo/hash devueltos por la mutacion. */
  function normalizarItemServidor(item) {
    if (!item || !item.url) return null;
    var id = item.id_media_archivo || item.media_id || "";
    return {
      id: id ? "bd_" + id : (item.codigo || item.url), media_id: id, codigo: item.codigo || "",
      nombre: item.nombre_seo || item.nombre_original || item.nombre || item.nombre_archivo || "Imagen CMS", nombre_archivo: item.nombre_archivo || "", nombre_seo: item.nombre_seo || "", urls_anteriores: Array.isArray(item.urls_anteriores) ? item.urls_anteriores : [],
      mime: item.mime || "", extension: item.extension || "", bytes: Number(item.bytes || 0), ancho: item.ancho, alto: item.alto,
      validacion_archivo: item.validacion_archivo || null, hash_sha256: item.hash_sha256 || "",
      url: item.url, preview_url: item.preview_url || "", alt: item.alt || item.alt_text || "", uso: item.uso || item.uso_sugerido || "general",
      tipo: item.tipo || item.tipo_sugerido || "editorial", estatus: item.estatus || "activo", creado_en: item.creado_en || item.fecha_registro || "", origen: "bd"
    };
  }

  /** IA: Codex GPT-6 | Fecha: 2026-09-24. Proposito: integrar cambio puntual; impacto: seleccion; contrato: mismo ID no se duplica. */
  function mezclarItemsServidor(items) {
    items.forEach(function (item) { var index = estado.items.findIndex(function (actual) { return actual.id === item.id; }); if (index < 0) estado.items.unshift(item); else estado.items[index] = item; });
  }

  /** IA: Codex GPT-6 | Fecha: 2026-09-24. Proposito: retirar cache obsoleta; impacto: biblioteca; contrato: listado servidor autoritativo conserva temporales. */
  function reconciliarItemsServidor(items) {
    estado.items = estado.items.filter(function (item) { return !esItemServidor(item); }); mezclarItemsServidor(items);
    if (!buscarMedia(estado.activo)) estado.activo = estado.items[0] ? estado.items[0].id : "";
  }

  /** IA: Codex GPT-6 | Fecha: 2026-09-24. Proposito: retirar seleccion eliminada; impacto: UI; contrato: solo estado del navegador. */
  function quitarItem(id) {
    estado.items = estado.items.filter(function (item) { return item.id !== id; }); delete estado.usos[id];
    if (estado.activo === id) estado.activo = estado.items[0] ? estado.items[0].id : "";
  }

  /** IA: Codex GPT-6 | Fecha: 2026-09-24. Proposito: limpiar temporales legados; impacto: navegador; contrato: nunca elimina archivos servidor. */
  function limpiarTemporalesLocales() {
    if (estado.ocupado || !window.confirm("Quitar todos los temporales de este navegador? Los archivos compartidos se conservan.")) return;
    estado.items = estado.items.filter(esItemServidor);
    if (!buscarMedia(estado.activo)) estado.activo = estado.items[0] ? estado.items[0].id : "";
    guardarLocal(); renderTodo(); setEstado("Temporales locales eliminados.", "success");
  }

  /** IA: Codex GPT-6 | Fecha: 2026-09-24. Proposito: limpiar temporales archivados; impacto: legado; contrato: siempre excluye servidor. */
  function limpiarArchivados() {
    if (estado.ocupado || !window.confirm("Quitar los temporales archivados de este navegador?")) return;
    estado.items = estado.items.filter(function (item) { return esItemServidor(item) || item.estatus !== "archivado"; });
    if (!buscarMedia(estado.activo)) estado.activo = estado.items[0] ? estado.items[0].id : "";
    guardarLocal(); renderTodo(); setEstado("Temporales archivados eliminados.", "success");
  }

  /** IA: Codex GPT-6 | Fecha: 2026-09-24. Proposito: seleccionar imagen; impacto: detalle; contrato: no modifica archivos. */
  function seleccionarMedia(id) { estado.activo = id; renderTodo(); }

  /** IA: Codex GPT-6 | Fecha: 2026-09-24. Proposito: buscar y priorizar peso; impacto: biblioteca; contrato: opera sobre todas las paginas cargadas. */
  function filtrarItems() {
    var busqueda = valor("cms_media_buscar").toLowerCase().trim(), uso = valor("cms_media_filtro_uso"), orden = valor("cms_media_orden");
    return estado.items.filter(function (item) {
      var texto = [item.nombre, item.alt, item.codigo, item.uso, item.tipo, extensionMedia(item)].join(" ").toLowerCase();
      return (!uso || item.uso === uso) && (!busqueda || texto.indexOf(busqueda) !== -1);
    }).sort(function (a, b) {
      if (orden === "nombre") return String(a.nombre).localeCompare(String(b.nombre), "es");
      if (orden === "recientes") return String(b.creado_en || "").localeCompare(String(a.creado_en || "")) || Number(b.media_id || 0) - Number(a.media_id || 0);
      return Number(b.bytes || 0) - Number(a.bytes || 0);
    });
  }

  /** IA: Codex GPT-6 | Fecha: 2026-09-24. Proposito: recuperar temporales sin revivir medios borrados; impacto: legado; contrato: descarta cache BD. */
  function cargarLocal() {
    try {
      var raw = JSON.parse(localStorage.getItem(STORAGE_KEY) || "[]");
      estado.items = Array.isArray(raw) ? raw.filter(function (item) { return item && item.id && item.url && !esItemServidor(item); }) : [];
    } catch (error) { estado.items = []; }
    estado.activo = estado.items[0] ? estado.items[0].id : "";
  }

  /** IA: Codex GPT-6 | Fecha: 2026-09-24. Proposito: persistir solo temporales; impacto: cache; contrato: error storage no revierte escrituras servidor. */
  function guardarLocal() {
    try { localStorage.setItem(STORAGE_KEY, JSON.stringify(estado.items.filter(function (item) { return !esItemServidor(item); }))); } catch (error) { /* Biblioteca compartida no depende de storage local. */ }
  }

  /** IA: Codex GPT-6 | Fecha: 2026-09-24. Proposito: reconocer medios compartidos legados; impacto: proteccion UI; contrato: origen, ID o ruta controlada identifican servidor. */
  function esItemServidor(item) { return !!(item && (item.origen === "bd" || item.media_id || String(item.id || "").indexOf("bd_") === 0 || String(item.url || "").indexOf("/assets/media/cms/ecommerce/") === 0)); }

  /** IA: Codex GPT-6 | Fecha: 2026-09-24. Proposito: refrescar preview tras reemplazo; impacto: UI; contrato: URL de asignacion permanece estable. */
  function previewUrl(item) { var url = item.preview_url || item.url; return estado.previewVersion[item.id] ? url + (url.indexOf("?") < 0 ? "?" : "&") + "cms_preview=" + estado.previewVersion[item.id] : url; }

  /** IA: Codex GPT-6 | Fecha: 2026-09-24. Proposito: resolver extension; impacto: formatos; contrato: ignora query/hash. */
  function extension(nombre) { return String(nombre || "").split(/[?#]/)[0].split(".").pop().toLowerCase(); }

  /** IA: Codex GPT-6 | Fecha: 2026-09-24. Proposito: usar formato persistido; impacto: reemplazo; contrato: nombre original historico no define extension. */
  function extensionMedia(item) { return String(item.extension || extension(item.nombre_archivo || item.url || item.nombre)).toLowerCase(); }

  /** IA: Codex GPT-6 | Fecha: 2026-09-24. Proposito: ofrecer formatos optimizables; impacto: UI; contrato: helper verifica imagen estatica. */
  function extensionOptimizable(ext) { return ["jpg", "jpeg", "png", "webp"].indexOf(ext) !== -1; }

  /** IA: Codex GPT-6 | Fecha: 2026-09-24. Proposito: normalizar sugerencia sin inventar contenido; impacto: SEO Media; contrato: solo transforma texto disponible. */
  function nombreSugerido(texto) { return window.CmsMediaTools ? window.CmsMediaTools.sugerirNombreSeo(texto) : ""; }
  /** IA: Codex GPT-6 | Fecha: 2026-09-24. Proposito: proponer nombre editable por eleccion; impacto: alta/detalle; contrato: no guarda datos. */
  function sugerirNombre(id, texto) { if ($(id)) $(id).value = nombreSugerido(texto); }
  /** IA: Codex GPT-6 | Fecha: 2026-09-24. Proposito: anticipar nombre y extension de alta; impacto: SEO Media; contrato: servidor agrega identificador unico. */
  function renderNombreAlta() {
    var file = estado.archivoPendiente;
    setText("cms_media_nombre_preview", (nombreSugerido(valor("cms_media_nombre_seo")) || "collares-para-perros") + "." + (marcado("cms_media_webp") ? "webp" : file ? extension(file.name) : "webp") + " · El servidor agrega un identificador unico.");
  }
  /** IA: Codex GPT-6 | Fecha: 2026-09-24. Proposito: anticipar nombre y formato elegidos; impacto: detalle; contrato: no cambia el archivo. */
  function renderNombreDetalle() {
    var item = mediaActiva(), input = $("cms_media_reemplazo"), file = input && input.files && input.files[0];
    if (!item) return;
    setText("cms_media_detalle_nombre_preview", (nombreSugerido(valor("cms_media_detalle_nombre_seo")) || "collares-para-perros") + "." + (marcado("cms_media_reemplazo_webp") ? "webp" : file ? extension(file.name) : extensionMedia(item)) + " · Se agrega un identificador unico. Usa un nombre breve y descriptivo.");
  }

  /** IA: Codex GPT-6 | Fecha: 2026-09-24. Proposito: mostrar dimensiones disponibles; impacto: revision; contrato: no inventa medidas. */
  function dimensiones(item) { return item.ancho && item.alto ? item.ancho + " × " + item.alto + " px" : "Dimensiones no disponibles"; }

  /** IA: Codex GPT-6 | Fecha: 2026-09-24. Proposito: ubicar seleccion; impacto: detalle; contrato: null si ausente. */
  function mediaActiva() { return buscarMedia(estado.activo); }

  /** IA: Codex GPT-6 | Fecha: 2026-09-24. Proposito: ubicar medio por ID; impacto: UI; contrato: no consulta servidor. */
  function buscarMedia(id) { return estado.items.find(function (item) { return item.id === id; }) || null; }

  function labelUsoMedia(uso) { return ({ home: "Home", categoria: "Categoria", producto: "Producto", global: "Global", blog: "Blog", general: "General" })[uso] || uso || "General"; }
  function labelTipoMedia(tipo) { return ({ logo: "Logo principal", logo_blanco: "Logo blanco", favicon: "Favicon", open_graph: "Imagen social SEO", banner: "Banner", hero: "Hero", card: "Card", thumb: "Thumbnail", editorial: "Editorial" })[tipo] || tipo || "Editorial"; }
  function meta(label, value) { return '<div><div class="text-muted fs-8 text-uppercase fw-bold">' + escapeHtml(label) + '</div><div class="fw-semibold">' + escapeHtml(value || "") + '</div></div>'; }
  function formatoBytes(bytes) { bytes = Number(bytes) || 0; return bytes < 1024 ? bytes + " B" : bytes < 1024 * 1024 ? Math.round(bytes / 1024) + " KB" : (bytes / (1024 * 1024)).toFixed(2) + " MB"; }

  /** IA: Codex GPT-6 | Fecha: 2026-09-24. Proposito: presentar resultado accesible; impacto: UX; contrato: texto visible aun en solo lectura. */
  function setEstado(texto, clase) { var node = $("cms_media_estado"); if (node) { node.className = "alert alert-" + (clase || "info"); node.textContent = texto; } }

  function setText(id, value) { var node = $(id); if (node) node.textContent = String(value == null ? "" : value); }
  function valor(id) { var node = $(id); return node ? String(node.value || "") : ""; }

  /** IA: Codex GPT-6 | Fecha: 2026-09-24. Proposito: leer opcion explicita; impacto: optimizacion; contrato: ausente conserva original. */
  function marcado(id) { var node = $(id); return !!(node && node.checked); }

  function on(id, eventName, callback) { var node = $(id); if (node) node.addEventListener(eventName, callback); }
  function $(id) { return document.getElementById(id); }
  function escapeHtml(value) { var div = document.createElement("div"); div.textContent = value == null ? "" : String(value); return div.innerHTML; }
  function escapeAttr(value) { return escapeHtml(value).replace(/"/g, "&quot;"); }
})();
