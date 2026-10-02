/*
 * IA: Codex GPT-6 | Fecha: 2026-09-30
 * Proposito: operar PIT como modulo independiente de preparacion y edicion masiva de imagenes.
 * Impacto: PIT/Media; procesa, sube y lista assets sin asignarlos todavia a Catalogo/CMS.
 * Contrato: usa endpoints /pit/*; las transformaciones ocurren en navegador y servidor valida el archivo final.
 */
(function () {
  "use strict";

  var MAX_BYTES = 2 * 1024 * 1024;
  var EXTENSIONES = ["jpg", "jpeg", "png", "webp", "gif", "avif", "ico"];
  var estado = { items: [], activo: "", archivo: null, lote: [], activoLote: "", ocupado: false, permisos: { editar: false, publicar: false, asignar: false }, carga: 0, preview: "", estimacionTimer: null, estimacionVersion: 0 };

  document.addEventListener("DOMContentLoaded", function () {
    bindEventos();
    renderTodo();
    cargarPreflight();
    cargarListado();
  });

  function bindEventos() {
    on("pit_tab_editor", "click", function () { mostrarVista("editor"); });
    on("pit_tab_biblioteca", "click", function () { mostrarVista("biblioteca"); });
    document.querySelectorAll("[data-pit-view]").forEach(function (node) {
      node.addEventListener("click", function () { mostrarVista(node.dataset.pitView); });
    });
    on("pit_media_archivo", "change", prepararArchivo);
    on("pit_media_optimizar", "change", prepararArchivo);
    on("pit_media_formato_salida", "change", function () { renderNombre(); renderLote(); programarEstimacion(); });
    on("pit_media_calidad", "input", function () { setText("pit_media_calidad_label", valor("pit_media_calidad") + "%"); programarEstimacion(); });
    on("pit_media_max_ancho", "input", function () { renderLote(); programarEstimacion(); });
    on("pit_media_max_alto", "input", function () { renderLote(); programarEstimacion(); });
    on("pit_media_aspecto_salida", "change", function () { renderPreviewEditor(); renderLote(); programarEstimacion(); });
    on("pit_media_ajuste_salida", "change", function () { renderPreviewEditor(); renderLote(); programarEstimacion(); });
    on("pit_media_zoom_salida", "input", function () {
      var fila = buscarFila(estado.activoLote);
      if (fila) fila.zoom = zoomActual();
      setText("pit_media_zoom_label", Math.round(zoomActual() * 100) + "%");
      renderPreviewEditor();
      programarEstimacion();
    });
    on("pit_media_fondo_salida", "change", function () { renderPreviewEditor(); programarEstimacion(); });
    on("pit_media_fondo_color", "input", function () { renderPreviewEditor(); programarEstimacion(); });
    on("pit_media_nombre_seo", "input", renderNombre);
    on("pit_media_sugerir_nombre", "click", function () {
      sugerirNombre("pit_media_nombre_seo", valor("pit_media_alt") || (estado.archivo || {}).name || "");
      renderNombre();
    });
    on("pit_media_agregar", "click", subirArchivo);
    on("pit_media_limpiar_lote", "click", limpiarLote);
    on("pit_media_lote", "input", function (event) {
      var input = event.target.closest("[data-pit-seo-id]");
      if (!input) return;
      var fila = buscarFila(input.dataset.pitSeoId);
      if (fila) fila.seo = input.value;
    });
    on("pit_media_lote", "click", function (event) {
      if (event.target.closest("input,button,select,textarea")) return;
      var row = event.target.closest("[data-pit-row-id]");
      if (row) seleccionarFila(row.dataset.pitRowId);
    });
    on("pit_media_visual", "pointerdown", function (event) {
      var stage = event.target.closest("[data-pit-preview-stage]");
      var fila = buscarFila(estado.activoLote);
      if (!stage || !fila || !puedeMoverEncuadre(fila)) return;
      event.preventDefault();
      var inicio = {
        x: event.clientX,
        y: event.clientY,
        focoX: typeof fila.focoX === "number" ? fila.focoX : 0.5,
        focoY: typeof fila.focoY === "number" ? fila.focoY : 0.5
      };
      stage.classList.add("is-dragging");
      if (stage.setPointerCapture) stage.setPointerCapture(event.pointerId);
      var mover = function (moveEvent) {
        moverEncuadreDesdeArrastre(stage, fila, inicio, moveEvent);
      };
      var soltar = function () {
        stage.classList.remove("is-dragging");
        stage.removeEventListener("pointermove", mover);
        stage.removeEventListener("pointerup", soltar);
        stage.removeEventListener("pointercancel", soltar);
        programarEstimacion();
      };
      stage.addEventListener("pointermove", mover);
      stage.addEventListener("pointerup", soltar);
      stage.addEventListener("pointercancel", soltar);
    });
    on("pit_media_buscar", "input", renderBiblioteca);
    on("pit_media_filtro_uso", "change", renderBiblioteca);
    on("pit_media_orden", "change", renderBiblioteca);
    on("pit_media_recargar", "click", function () { cargarListado(); });
    on("pit_media_recargar_top", "click", function () { cargarListado(); });
    on("pit_media_biblioteca", "click", function (event) {
      var button = event.target.closest("[data-pit-action]");
      if (button) { ejecutarAccion(button.dataset.pitAction, button.dataset.pitId); return; }
      var card = event.target.closest("[data-pit-id]");
      if (card) seleccionar(card.dataset.pitId);
    });
    on("pit_media_detalle", "click", function (event) {
      var button = event.target.closest("[data-pit-detail-action]");
      if (button) ejecutarAccion(button.dataset.pitDetailAction, estado.activo);
    });
  }

  function mostrarVista(vista) {
    var editor = $("pit_workspace_editor");
    var biblioteca = $("pit_workspace_biblioteca");
    if (editor) editor.hidden = vista !== "editor";
    if (biblioteca) biblioteca.hidden = vista !== "biblioteca";
    var tabEditor = $("pit_tab_editor"), tabBiblioteca = $("pit_tab_biblioteca");
    if (tabEditor) tabEditor.className = vista === "editor" ? "btn btn-primary" : "btn btn-light";
    if (tabBiblioteca) tabBiblioteca.className = vista === "biblioteca" ? "btn btn-primary" : "btn btn-light";
  }

  function prepararArchivo() {
    var input = $("pit_media_archivo");
    var files = input && input.files ? Array.prototype.slice.call(input.files) : [];
    estado.archivo = null;
    liberarPreview();
    limpiarLote(false);
    if (!files.length) return;
    estado.lote = files.map(function (file, index) {
      var preview = URL.createObjectURL(file);
      return {
        id: Date.now().toString(36) + "_" + index,
        file: file,
        preview: preview,
        estado: "pendiente",
        mensaje: "Pendiente",
        salida: null,
        seo: nombreSlug(file.name) || "imagen-pit",
        estimacion: "",
        estimacionTipo: "light",
        focoX: 0.5,
        focoY: 0.5,
        zoom: zoomActual()
      };
    });
    estado.activoLote = estado.lote[0].id;
    estado.archivo = estado.lote[0].file;
    var error = validarLote();
    if (error) { setEstado(error, "danger"); }
    if (!valor("pit_media_nombre_seo")) sugerirNombre("pit_media_nombre_seo", valor("pit_media_alt") || estado.archivo.name);
    renderNombre();
    setEstado(estado.lote.length + " imagenes listas para procesar." + (error ? " Revisa: " + error : ""), error ? "warning" : "info");
    renderLote();
    renderPreviewEditor();
    programarEstimacion();
  }

  function validarArchivo(file) {
    var ext = extension(file.name);
    if (EXTENSIONES.indexOf(ext) === -1) return "Selecciona JPG, JPEG, PNG, WebP, GIF, AVIF o ICO.";
    if (!file.size) return "El archivo esta vacio.";
    if (file.size > 20 * 1024 * 1024) return "La imagen fuente supera 20 MB.";
    if (requiereCanvas() && ["jpg", "jpeg", "png", "webp"].indexOf(ext) === -1) return "Solo JPG, PNG y WebP se pueden redimensionar o convertir en PIT. Conserva GIF, AVIF e ICO como original.";
    if (!requiereCanvas() && file.size > MAX_BYTES && !(marcado("pit_media_optimizar") && ["jpg", "jpeg", "png", "webp"].indexOf(ext) !== -1)) return "Supera 2 MB. Activa optimizar, redimensiona o elige un archivo menor.";
    return "";
  }

  function validarLote() {
    for (var i = 0; i < estado.lote.length; i++) {
      var error = validarArchivo(estado.lote[i].file);
      if (error) return estado.lote[i].file.name + ": " + error;
    }
    return "";
  }

  async function subirArchivo() {
    if (estado.ocupado || !estado.permisos.editar) return;
    var lote = estado.lote.length ? estado.lote : (estado.archivo ? [{ id: "unico", file: estado.archivo, estado: "pendiente", mensaje: "Pendiente", salida: null }] : []);
    if (!lote.length) { setEstado("Selecciona una o varias imagenes.", "warning"); return; }
    var errorLote = validarLote();
    if (errorLote) { setEstado(errorLote, "danger"); return; }
    establecerOcupado(true);
    var subidas = 0, errores = 0, nuevos = [];
    try {
      for (var i = 0; i < lote.length; i++) {
        var fila = lote[i], file = fila.file;
        try {
          fila.estado = "procesando"; fila.mensaje = "Procesando"; renderLote();
          setEstado("Procesando " + (i + 1) + " de " + lote.length + ": " + file.name, "info");
          var final = await procesarArchivoPit(file);
          if (final.size > MAX_BYTES) throw new Error("La imagen final supera 2 MB.");
          fila.salida = final; fila.estado = "subiendo"; fila.mensaje = formatoBytes(final.size); renderLote();
          var data = new FormData();
          data.append("archivo", final);
          data.append("alt", altParaArchivo(file));
          data.append("uso", valor("pit_media_uso") || "general");
          data.append("tipo", valor("pit_media_tipo") || "referencia");
          data.append("nombre_seo", nombreSeoParaArchivo(file, lote.length > 1, fila));
          var json = await enviarMedia("/pit/media_subir_erp", data);
          var item = normalizarItem(json.depurar && (json.depurar.item || json.depurar));
          if (item) nuevos.push(item);
          fila.estado = "ok"; fila.mensaje = resumenAhorro(file.size, final.size); subidas++;
        } catch (error) {
          fila.estado = "error"; fila.mensaje = error.message || "No se pudo procesar."; errores++;
        }
        renderLote();
      }
      if (nuevos.length) { mezclarItems(nuevos); estado.activo = nuevos[0].id; }
      if (!errores) {
        estado.archivo = null;
        if ($("pit_media_archivo")) $("pit_media_archivo").value = "";
        if ($("pit_media_alt")) $("pit_media_alt").value = "";
        if ($("pit_media_nombre_seo")) $("pit_media_nombre_seo").value = "";
        limpiarLote(false);
      }
      renderTodo();
      setEstado("PIT termino el lote: " + subidas + " subidas, " + errores + " con error.", errores ? "warning" : "success");
      cargarListado(true);
    } catch (error) {
      setEstado(error.message || "No se pudo procesar el lote.", "danger");
    } finally {
      establecerOcupado(false);
    }
  }

  async function procesarArchivoPit(file) {
    var formato = valor("pit_media_formato_salida") || "original";
    var ext = extension(file.name);
    var salidaMime = formato === "original" ? mimeDesdeExtension(ext) : formato === "jpg" ? "image/jpeg" : "image/" + formato;
    if (!salidaMime) throw new Error("Formato no soportado.");
    if (!requiereCanvas()) {
      if (marcado("pit_media_optimizar") && window.CmsMediaTools && ["jpg", "jpeg", "png", "webp"].indexOf(ext) !== -1) {
        return window.CmsMediaTools.optimizar(file);
      }
      return file;
    }
    var img = await leerImagen(file);
    if (img.width * img.height > 40000000) throw new Error("La imagen tiene demasiados pixeles para procesarla aqui.");
    var fila = filaPorArchivo(file);
    var salida = salidaCanvas(img.width, img.height);
    var canvas = document.createElement("canvas");
    canvas.width = salida.ancho;
    canvas.height = salida.alto;
    var ctx = canvas.getContext("2d");
    if (!ctx) throw new Error("Tu navegador no dispone de editor de imagenes.");
    var fondo = fondoSalida(salidaMime);
    if (fondo) {
      ctx.fillStyle = fondo;
      ctx.fillRect(0, 0, canvas.width, canvas.height);
    }
    dibujarEnCanvas(ctx, img, salida, fila || { focoX: 0.5, focoY: 0.5 });
    var calidad = Math.max(0.55, Math.min(0.95, Number(valor("pit_media_calidad") || 82) / 100));
    var blob = await new Promise(function (resolve) { canvas.toBlob(resolve, salidaMime, salidaMime === "image/png" ? undefined : calidad); });
    if (!blob) throw new Error("No se pudo generar la imagen final.");
    var nombre = nombreArchivoSalida(file.name, formato === "original" ? ext : formato);
    return new File([blob], nombre, { type: salidaMime, lastModified: Date.now() });
  }

  function leerImagen(file) {
    return new Promise(function (resolve, reject) {
      var url = URL.createObjectURL(file);
      var img = new Image();
      img.onload = function () { URL.revokeObjectURL(url); resolve({ image: img, width: img.naturalWidth, height: img.naturalHeight }); };
      img.onerror = function () { URL.revokeObjectURL(url); reject(new Error("No se pudo leer la imagen para editarla.")); };
      img.src = url;
    }).then(function (data) { return data; });
  }

  function requiereCanvas() {
    var formato = valor("pit_media_formato_salida") || "original";
    return formato !== "original" || numeroPositivo("pit_media_max_ancho") || numeroPositivo("pit_media_max_alto") || valor("pit_media_aspecto_salida") !== "original";
  }

  function limitesSalida(ancho, alto) {
    var maxAncho = numeroPositivo("pit_media_max_ancho") || 2560;
    var maxAlto = numeroPositivo("pit_media_max_alto") || 2560;
    var escala = Math.min(1, maxAncho / ancho, maxAlto / alto);
    return { ancho: Math.max(1, Math.round(ancho * escala)), alto: Math.max(1, Math.round(alto * escala)) };
  }

  function salidaCanvas(ancho, alto) {
    var ratio = ratioSalida();
    if (!ratio) return limitesSalida(ancho, alto);
    var maxAncho = numeroPositivo("pit_media_max_ancho") || 2560;
    var maxAlto = numeroPositivo("pit_media_max_alto") || 2560;
    var salidaAncho = maxAncho;
    var salidaAlto = Math.round(salidaAncho / ratio);
    if (salidaAlto > maxAlto) {
      salidaAlto = maxAlto;
      salidaAncho = Math.round(salidaAlto * ratio);
    }
    return { ancho: Math.max(1, salidaAncho), alto: Math.max(1, salidaAlto), ratio: ratio };
  }

  function ratioSalida() {
    var valorAspecto = valor("pit_media_aspecto_salida") || "original";
    var partes = valorAspecto.split(":");
    if (partes.length !== 2) return 0;
    var ancho = Number(partes[0]), alto = Number(partes[1]);
    return ancho > 0 && alto > 0 ? ancho / alto : 0;
  }

  function dibujarEnCanvas(ctx, img, salida, fila) {
    var modo = valor("pit_media_ajuste_salida") || "contain";
    var iw = img.width, ih = img.height, cw = salida.ancho, ch = salida.alto;
    var escala = modo === "cover" ? Math.max(cw / iw, ch / ih) : Math.min(cw / iw, ch / ih);
    escala = escala * Math.max(1, Number(fila.zoom || 1));
    var dw = iw * escala, dh = ih * escala;
    var fx = typeof fila.focoX === "number" ? fila.focoX : 0.5;
    var fy = typeof fila.focoY === "number" ? fila.focoY : 0.5;
    var dx = (modo === "cover" || dw > cw) ? (cw - dw) * fx : (cw - dw) / 2;
    var dy = (modo === "cover" || dh > ch) ? (ch - dh) * fy : (ch - dh) / 2;
    ctx.drawImage(img.image, dx, dy, dw, dh);
  }

  function numeroPositivo(id) {
    var numero = Number(valor(id));
    return Number.isFinite(numero) && numero > 0 ? numero : 0;
  }

  function zoomActual() {
    var zoom = Number(valor("pit_media_zoom_salida") || 1);
    return Number.isFinite(zoom) ? Math.max(1, Math.min(3, zoom)) : 1;
  }

  function fondoSalida(salidaMime) {
    var fondo = valor("pit_media_fondo_salida") || "transparent";
    if (salidaMime === "image/jpeg") return fondo === "custom" ? valor("pit_media_fondo_color") || "#ffffff" : "#ffffff";
    if (fondo === "white") return "#ffffff";
    if (fondo === "custom") return valor("pit_media_fondo_color") || "#ffffff";
    return "";
  }

  function mimeDesdeExtension(ext) {
    return { jpg: "image/jpeg", jpeg: "image/jpeg", png: "image/png", webp: "image/webp", gif: "image/gif", avif: "image/avif", ico: "image/x-icon" }[String(ext || "").toLowerCase()] || "";
  }

  function nombreArchivoSalida(nombre, ext) {
    ext = ext === "jpeg" ? "jpg" : ext;
    return String(nombre || "imagen").replace(/\.[^.]+$/, "") + "." + ext;
  }

  function altParaArchivo(file) {
    var alt = valor("pit_media_alt").trim();
    return alt || nombreLegible(file.name);
  }

  function nombreSeoParaArchivo(file, esLote, fila) {
    if (fila && String(fila.seo || "").trim()) return nombreSlug(fila.seo);
    var base = valor("pit_media_nombre_seo").trim();
    var sugerido = window.CmsMediaTools && window.CmsMediaTools.sugerirNombreSeo ? window.CmsMediaTools.sugerirNombreSeo(file.name) : nombreSlug(file.name);
    if (!base) return sugerido || "imagen-pit";
    return esLote ? base.replace(/-+$/g, "") + "-" + (sugerido || nombreSlug(file.name) || "imagen") : base;
  }

  function programarEstimacion() {
    if (estado.estimacionTimer) clearTimeout(estado.estimacionTimer);
    if (!estado.lote.length || estado.ocupado) return;
    estado.estimacionTimer = setTimeout(estimarLote, 350);
  }

  async function estimarLote() {
    var version = ++estado.estimacionVersion;
    estado.lote.forEach(function (fila) {
      fila.estimacion = "Estimando...";
      fila.estimacionTipo = "info";
    });
    renderLote();
    for (var i = 0; i < estado.lote.length; i++) {
      if (version !== estado.estimacionVersion) return;
      var fila = estado.lote[i];
      try {
        var final = await procesarArchivoPit(fila.file);
        if (version !== estado.estimacionVersion) return;
        fila.estimacion = "Aprox. " + formatoBytes(final.size);
        fila.estimacionTipo = final.size > MAX_BYTES ? "danger" : final.size > fila.file.size ? "warning" : "success";
      } catch (error) {
        fila.estimacion = "Sin estimacion";
        fila.estimacionTipo = "warning";
      }
      renderLote();
    }
  }

  function renderLote() {
    var node = $("pit_media_lote");
    if (!node) return;
    if (!estado.lote.length) {
      node.innerHTML = "";
      setText("pit_editor_resumen", "Selecciona imagenes para iniciar.");
      return;
    }
    setText("pit_editor_resumen", estado.lote.length + " imagenes en cola. Formato: " + labelFormatoSalida() + ". " + labelDimensionesSalida());
    node.innerHTML = estado.lote.map(function (fila) {
      var badge = fila.estado === "ok" ? "success" : fila.estado === "error" ? "danger" : fila.estado === "subiendo" ? "primary" : fila.estado === "procesando" ? "info" : "light";
      return '<div class="pit-queue-item ' + (fila.id === estado.activoLote ? "border-primary" : "") + '" data-pit-row-id="' + escapeAttr(fila.id) + '">' +
        '<img class="pit-queue-thumb" src="' + escapeAttr(fila.preview) + '" alt="' + escapeAttr(fila.file.name) + '">' +
        '<div class="min-w-0"><div class="fw-semibold text-truncate" title="' + escapeAttr(fila.file.name) + '">' + escapeHtml(fila.file.name) + '</div>' +
        '<div class="text-muted fs-8">' + escapeHtml(formatoBytes(fila.file.size)) + ' - ' + escapeHtml(extension(fila.file.name).toUpperCase()) + '</div>' +
        '<div class="mt-1"><span class="badge badge-light-' + escapeAttr(fila.estimacionTipo || "light") + '">' + escapeHtml(fila.estimacion || "Aprox. pendiente") + '</span></div></div>' +
        '<div class="pit-queue-seo"><label class="form-label fs-8 mb-1">Nombre SEO</label><input class="form-control form-control-sm form-control-solid" data-pit-seo-id="' + escapeAttr(fila.id) + '" value="' + escapeAttr(fila.seo || "") + '" maxlength="120" placeholder="nombre-seo"></div>' +
        '<div class="pit-queue-result"><span class="badge badge-light-' + badge + '">' + escapeHtml(fila.mensaje || "Pendiente") + '</span></div></div>';
    }).join("");
  }

  function limpiarLote(resetInput) {
    estado.lote.forEach(function (fila) { if (fila.preview) URL.revokeObjectURL(fila.preview); });
    estado.lote = [];
    estado.activoLote = "";
    if (resetInput !== false && $("pit_media_archivo")) $("pit_media_archivo").value = "";
    renderLote();
  }

  function seleccionarFila(id) {
    var fila = buscarFila(id);
    if (!fila) return;
    estado.activoLote = fila.id;
    estado.archivo = fila.file;
    if ($("pit_media_zoom_salida")) $("pit_media_zoom_salida").value = String(fila.zoom || 1);
    setText("pit_media_zoom_label", Math.round((fila.zoom || 1) * 100) + "%");
    renderLote();
    renderPreviewEditor();
  }

  function renderPreviewEditor() {
    var fila = buscarFila(estado.activoLote) || estado.lote[0];
    if (!fila) { setVisual("Selecciona archivos para previsualizar el lote."); return; }
    var ratio = ratioSalida();
    if (!ratio) {
      setVisual('<img class="pit-preview-img" src="' + escapeAttr(fila.preview) + '" alt="' + escapeAttr(fila.file.name) + '">');
      return;
    }
    var fit = valor("pit_media_ajuste_salida") || "contain";
    var objectPosition = Math.round((fila.focoX || 0.5) * 100) + "% " + Math.round((fila.focoY || 0.5) * 100) + "%";
    var orientacion = labelOrientacion();
    var zoom = Math.max(1, Number(fila.zoom || 1));
    var fondo = fondoSalida(valor("pit_media_formato_salida") === "jpg" ? "image/jpeg" : "");
    var fondoStyle = fondo ? "background:" + fondo + ";" : "";
    var movible = puedeMoverEncuadre(fila);
    var foco = movible ? '<span class="pit-preview-guides"></span><span class="badge badge-light-primary pit-preview-orientation">' + escapeHtml(orientacion) + '</span><span class="pit-preview-handle" aria-label="Foco de encuadre" style="left:' + escapeAttr(Math.round((fila.focoX || 0.5) * 100)) + '%;top:' + escapeAttr(Math.round((fila.focoY || 0.5) * 100)) + '%"></span>' : '<span class="badge badge-light-primary pit-preview-orientation">' + escapeHtml(orientacion) + '</span>';
    setVisual('<div class="pit-preview-stage" data-pit-preview-stage data-fit="' + escapeAttr(fit) + '" data-movable="' + (movible ? "1" : "0") + '" style="aspect-ratio:' + escapeAttr(String(ratio)) + ';' + escapeAttr(fondoStyle) + '">' +
      '<img src="' + escapeAttr(fila.preview) + '" alt="' + escapeAttr(fila.file.name) + '" style="object-position:' + escapeAttr(objectPosition) + ';transform:scale(' + escapeAttr(String(zoom)) + ')">' + foco + '</div>' +
      '<div class="text-muted fs-8 pit-preview-help">' + escapeHtml(movible ? "Arrastra el marcador o la imagen para acomodar la parte importante; usa zoom para acercar." : "Encajar completa: la imagen se mantiene completa dentro del formato elegido.") + '</div>');
  }

  function puedeMoverEncuadre(fila) {
    return valor("pit_media_ajuste_salida") === "cover" || Number(fila.zoom || 1) > 1;
  }

  function moverEncuadreDesdeArrastre(stage, fila, inicio, event) {
    var overflow = overflowPreview(stage, fila);
    var deltaX = event.clientX - inicio.x;
    var deltaY = event.clientY - inicio.y;
    fila.focoX = overflow.x > 0 ? clamp01(inicio.focoX - (deltaX / overflow.x)) : 0.5;
    fila.focoY = overflow.y > 0 ? clamp01(inicio.focoY - (deltaY / overflow.y)) : 0.5;
    var handle = stage.querySelector(".pit-preview-handle");
    var img = stage.querySelector("img");
    if (handle) {
      handle.style.left = Math.round(fila.focoX * 100) + "%";
      handle.style.top = Math.round(fila.focoY * 100) + "%";
    }
    if (img) img.style.objectPosition = Math.round(fila.focoX * 100) + "% " + Math.round(fila.focoY * 100) + "%";
  }

  function overflowPreview(stage, fila) {
    var img = stage.querySelector("img");
    var rect = stage.getBoundingClientRect();
    var naturalW = img && img.naturalWidth ? img.naturalWidth : rect.width;
    var naturalH = img && img.naturalHeight ? img.naturalHeight : rect.height;
    var modo = valor("pit_media_ajuste_salida") || "contain";
    var base = modo === "cover" ? Math.max(rect.width / naturalW, rect.height / naturalH) : Math.min(rect.width / naturalW, rect.height / naturalH);
    var escala = base * Math.max(1, Number(fila.zoom || 1));
    return {
      x: Math.max(0, naturalW * escala - rect.width),
      y: Math.max(0, naturalH * escala - rect.height)
    };
  }

  function clamp01(valorNumerico) {
    return Math.max(0, Math.min(1, Number(valorNumerico) || 0));
  }

  function labelOrientacion() {
    var aspecto = valor("pit_media_aspecto_salida") || "original";
    if (aspecto === "1:1") return "Cuadrada 1:1";
    if (aspecto === "4:3") return "Horizontal 4:3";
    if (aspecto === "16:9") return "Horizontal 16:9";
    if (aspecto === "3:4") return "Vertical 3:4";
    if (aspecto === "9:16") return "Vertical 9:16";
    return "Original";
  }

  function filaPorArchivo(file) {
    return estado.lote.find(function (fila) { return fila.file === file; });
  }

  async function cargarPreflight() {
    try {
      var json = await consultarJson("/pit/media_preflight_erp");
      var data = json.depurar || {};
      if (data.permisos) estado.permisos = data.permisos;
      aplicarPermisos();
      setText("pit_media_preflight", estado.permisos.editar ? "Puedes cargar imagenes a PIT. La asignacion a modulos se agregara en una fase posterior." : "Solo lectura: puedes consultar y copiar referencias.");
    } catch (error) {
      setText("pit_media_preflight", "No se pudieron verificar permisos de PIT.");
    }
  }

  function aplicarPermisos() {
    var panel = $("pit_media_alta_panel");
    if (panel) panel.hidden = !estado.permisos.editar;
  }

  async function cargarListado(silencioso) {
    var carga = ++estado.carga;
    if (!silencioso) setEstado("Actualizando biblioteca PIT...", "info");
    try {
      var items = [], offset = 0, data;
      do {
        var json = await consultarJson("/pit/media_listar_erp?limite=120&offset=" + offset + "&orden=peso");
        if (carga !== estado.carga) return;
        data = json.depurar || {};
        var pagina = Array.isArray(data.items) ? data.items : [];
        items = items.concat(pagina.map(normalizarItem).filter(Boolean));
        offset += pagina.length;
        if (data.hay_mas && !pagina.length) break;
      } while (data.hay_mas);
      estado.items = items;
      if (!estado.activo && items.length) estado.activo = items[0].id;
      renderTodo();
      if (!silencioso) setEstado("Biblioteca PIT actualizada: " + items.length + " imagenes.", "info");
    } catch (error) {
      setEstado(error.message || "No se pudo consultar la biblioteca PIT.", "danger");
    }
  }

  function renderTodo() {
    renderBiblioteca();
    renderDetalle();
  }

  function renderBiblioteca() {
    var node = $("pit_media_biblioteca");
    if (!node) return;
    var items = filtrarItems();
    var peso = items.reduce(function (total, item) { return total + (Number(item.bytes) || 0); }, 0);
    setText("pit_media_resumen", items.length + " imagenes - " + formatoBytes(peso));
    if (!items.length) { node.innerHTML = '<div class="text-muted">No hay imagenes que coincidan.</div>'; return; }
    node.innerHTML = items.map(function (item) {
      return '<div class="pit-card ' + (item.id === estado.activo ? "is-active" : "") + '" data-pit-id="' + escapeAttr(item.id) + '">' +
        '<img class="pit-thumb" loading="lazy" src="' + escapeAttr(item.url) + '" alt="' + escapeAttr(item.alt) + '">' +
        '<div class="p-3"><div class="fw-bold text-truncate" title="' + escapeAttr(item.nombre) + '">' + escapeHtml(item.nombre) + '</div>' +
        '<div class="text-muted fs-8 text-truncate">' + escapeHtml(item.alt || "Sin descripcion") + '</div>' +
        '<div class="fw-semibold mt-2">' + escapeHtml(formatoBytes(item.bytes)) + ' - ' + escapeHtml((item.extension || "").toUpperCase()) + '</div>' +
        '<div class="text-muted fs-8">' + escapeHtml(dimensiones(item)) + ' - ' + escapeHtml(item.uso || "general") + '</div>' +
        '<div class="pit-actions mt-3"><button class="btn btn-sm btn-light-primary" type="button" data-pit-action="detalle" data-pit-id="' + escapeAttr(item.id) + '">Ver</button>' +
        '<button class="btn btn-sm btn-light" type="button" data-pit-action="copiar" data-pit-id="' + escapeAttr(item.id) + '">Copiar</button></div></div></div>';
    }).join("");
  }

  function renderDetalle() {
    var item = buscar(estado.activo);
    var node = $("pit_media_detalle");
    if (!node) return;
    if (!item) { node.innerHTML = '<div class="text-muted">Selecciona una imagen para ver detalles.</div>'; return; }
    node.innerHTML = '<div class="row g-5"><div class="col-lg-4"><img class="pit-preview-img" src="' + escapeAttr(item.url) + '" alt="' + escapeAttr(item.alt) + '"></div>' +
      '<div class="col-lg-8"><h4 class="fw-bold mb-2 text-break">' + escapeHtml(item.nombre) + '</h4>' +
      '<div class="text-muted fs-7 mb-4">' + escapeHtml(item.alt || "Sin descripcion") + '</div>' +
      '<div class="pit-meta fs-7 mb-4">' + meta("Uso / tipo", (item.uso || "general") + " / " + (item.tipo || "referencia")) +
      meta("Formato", (item.extension || "").toUpperCase()) + meta("Peso", formatoBytes(item.bytes)) + meta("Dimensiones", dimensiones(item)) +
      meta("Ruta publica", item.url || "") + meta("Creado", String(item.creado_en || "").substring(0, 10)) + '</div>' +
      '<div class="alert alert-light-primary mb-4">Asignacion a productos, CMS, marcas y categorias: siguiente capa del PIT. Esta version prepara y conserva la imagen.</div>' +
      '<div class="pit-actions"><button class="btn btn-sm btn-light-primary" type="button" data-pit-detail-action="copiar">Copiar referencia</button>' +
      '<button class="btn btn-sm btn-light" type="button" data-pit-detail-action="usos">Consultar usos</button></div>' +
      '<div class="mt-4" id="pit_media_usos"></div></div></div>';
  }

  async function consultarUsos(item) {
    setText("pit_media_usos", "Consultando usos...");
    try {
      var json = await consultarJson("/pit/media_usos_erp?id_media_archivo=" + encodeURIComponent(item.media_id));
      var data = json.depurar || {};
      var usos = Array.isArray(data.usos) ? data.usos : [];
      $("pit_media_usos").innerHTML = '<div class="fw-semibold mb-2">' + escapeHtml(String(data.total == null ? usos.length : data.total)) + ' usos guardados</div>' +
        (usos.length ? '<ul class="mb-0">' + usos.map(function (uso) { return '<li>' + escapeHtml([uso.origen, uso.referencia, uso.estado].filter(Boolean).join(" - ")) + '</li>'; }).join("") + '</ul>' : '<div class="text-muted">Sin usos guardados detectados.</div>');
    } catch (error) {
      setText("pit_media_usos", error.message || "No se pudieron consultar usos.");
    }
  }

  function ejecutarAccion(accion, id) {
    var item = buscar(id);
    if (!item) return;
    if (accion === "detalle") { seleccionar(id); return; }
    if (accion === "copiar") { copiarReferencia(item); return; }
    if (accion === "usos") { consultarUsos(item); return; }
  }

  function seleccionar(id) {
    estado.activo = id;
    renderTodo();
  }

  function filtrarItems() {
    var q = valor("pit_media_buscar").trim().toLowerCase();
    var uso = valor("pit_media_filtro_uso");
    var items = estado.items.filter(function (item) {
      if (uso && item.uso !== uso) return false;
      if (!q) return true;
      return [item.nombre, item.alt, item.url, item.uso, item.tipo].join(" ").toLowerCase().indexOf(q) !== -1;
    });
    var orden = valor("pit_media_orden");
    items.sort(function (a, b) {
      if (orden === "nombre") return String(a.nombre).localeCompare(String(b.nombre));
      if (orden === "recientes") return String(b.creado_en || "").localeCompare(String(a.creado_en || ""));
      return (Number(b.bytes) || 0) - (Number(a.bytes) || 0);
    });
    return items;
  }

  function normalizarItem(raw) {
    if (!raw || typeof raw !== "object") return null;
    var id = raw.id_media_archivo || raw.media_id || raw.id || raw.codigo || raw.ruta_publica;
    var url = raw.ruta_publica || raw.url || "";
    if (!id || !url) return null;
    return {
      id: String(id),
      media_id: String(raw.id_media_archivo || raw.media_id || id),
      codigo: raw.codigo || "",
      nombre: raw.nombre_original || raw.nombre_archivo || raw.nombre || raw.codigo || "Imagen PIT",
      url: url,
      alt: raw.alt_text || raw.alt || "",
      bytes: Number(raw.bytes || raw.peso || 0),
      ancho: Number(raw.ancho || 0),
      alto: Number(raw.alto || 0),
      extension: String(raw.extension || extension(raw.nombre_archivo || raw.nombre_original || url)).toLowerCase(),
      uso: raw.uso_sugerido || raw.uso || "general",
      tipo: raw.tipo_sugerido || raw.tipo || "referencia",
      creado_en: raw.fecha_registro || raw.creado_en || ""
    };
  }

  function mezclarItems(items) {
    items.forEach(function (item) {
      estado.items = estado.items.filter(function (actual) { return actual.id !== item.id; });
      estado.items.unshift(item);
    });
  }

  function copiarReferencia(item) {
    var texto = JSON.stringify({ id_media_archivo: item.media_id, url: item.url, alt: item.alt, uso: item.uso, tipo: item.tipo }, null, 2);
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(texto).then(function () { setEstado("Referencia PIT copiada.", "success"); });
    } else {
      window.prompt("Referencia PIT", texto);
    }
  }

  async function consultarJson(url) {
    var respuesta = await fetch(url, { credentials: "same-origin", headers: { "Accept": "application/json" } });
    var json = await respuesta.json();
    if (!respuesta.ok || json.error) throw new Error(json.mensaje || "La operacion no se pudo completar.");
    return json;
  }

  async function enviarMedia(url, data) {
    if (window.ERP_CSRF_TOKEN) data.append("_csrf", window.ERP_CSRF_TOKEN);
    var respuesta = await fetch(url, { method: "POST", body: data, credentials: "same-origin", headers: { "Accept": "application/json" } });
    var json = await respuesta.json();
    if (!respuesta.ok || json.error) throw new Error(json.mensaje || "No se pudo guardar.");
    return json;
  }

  function establecerOcupado(valorOcupado) {
    estado.ocupado = !!valorOcupado;
    ["pit_media_agregar", "pit_media_limpiar_lote", "pit_media_recargar", "pit_media_recargar_top"].forEach(function (id) {
      var node = $(id);
      if (node) node.disabled = estado.ocupado;
    });
  }

  function setEstado(mensaje, tipo) {
    var node = $("pit_media_estado");
    if (node) {
      node.className = "alert alert-light-" + (tipo || "info");
      node.textContent = mensaje;
    }
    var resumen = $("pit_editor_resumen");
    if (resumen) resumen.textContent = mensaje;
  }

  function setVisual(html) {
    var node = $("pit_media_visual");
    if (node) node.innerHTML = html;
  }

  function renderNombre() {
    var base = valor("pit_media_nombre_seo").trim();
    var formato = valor("pit_media_formato_salida") || "original";
    var ext = formato === "original" ? extension((estado.archivo || {}).name || "") || "jpg" : formato;
    setText("pit_media_nombre_preview", base ? base + "." + ext : "");
  }

  function sugerirNombre(id, texto) {
    var slug = "";
    if (window.CmsMediaTools && window.CmsMediaTools.sugerirNombreSeo) {
      slug = window.CmsMediaTools.sugerirNombreSeo(texto || "");
    } else {
      slug = String(texto || "").toLowerCase().replace(/\.[a-z0-9]+$/i, "").normalize("NFD").replace(/[\u0300-\u036f]/g, "").replace(/[^a-z0-9]+/g, "-").replace(/^-+|-+$/g, "").substring(0, 90);
    }
    var node = $(id);
    if (node) node.value = slug || "imagen-pit";
  }

  function resumenAhorro(antes, despues) {
    return despues < antes ? "Peso: " + formatoBytes(antes) + " a " + formatoBytes(despues) + "." : "Peso: " + formatoBytes(despues) + ".";
  }

  function meta(label, value) {
    return '<div><div class="text-muted">' + escapeHtml(label) + '</div><div class="fw-semibold text-break">' + escapeHtml(value || "-") + '</div></div>';
  }

  function dimensiones(item) { return item.ancho && item.alto ? item.ancho + " x " + item.alto + " px" : "Sin dimensiones"; }
  function labelFormatoSalida() {
    var formato = valor("pit_media_formato_salida") || "original";
    return formato === "original" ? "conservar original" : formato.toUpperCase();
  }
  function labelDimensionesSalida() {
    var ancho = numeroPositivo("pit_media_max_ancho");
    var alto = numeroPositivo("pit_media_max_alto");
    if (!ancho && !alto) return "Dimensiones maximas automaticas 2560 x 2560 px.";
    return "Maximo " + (ancho || "auto") + " x " + (alto || "auto") + " px.";
  }
  function nombreLegible(nombre) { return String(nombre || "").replace(/\.[^.]+$/, "").replace(/[_-]+/g, " ").trim() || "Imagen PIT"; }
  function nombreSlug(nombre) { return nombreLegible(nombre).toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "").replace(/[^a-z0-9]+/g, "-").replace(/^-+|-+$/g, "").substring(0, 90); }
  function formatoBytes(bytes) {
    bytes = Number(bytes) || 0;
    if (bytes >= 1048576) return (bytes / 1048576).toFixed(2) + " MB";
    if (bytes >= 1024) return (bytes / 1024).toFixed(1) + " KB";
    return bytes + " B";
  }
  function extension(nombre) { var m = String(nombre || "").toLowerCase().match(/\.([a-z0-9]+)(?:\?|#|$)/); return m ? m[1] : ""; }
  function marcado(id) { var node = $(id); return !!(node && node.checked); }
  function valor(id) { var node = $(id); return node ? String(node.value || "") : ""; }
  function buscar(id) { return estado.items.find(function (item) { return item.id === String(id); }); }
  function buscarFila(id) { return estado.lote.find(function (fila) { return fila.id === String(id); }); }
  function on(id, event, cb) { var node = $(id); if (node) node.addEventListener(event, cb); }
  function $(id) { return document.getElementById(id); }
  function setText(id, text) { var node = $(id); if (node) node.textContent = text; }
  function liberarPreview() { if (estado.preview) URL.revokeObjectURL(estado.preview); estado.preview = ""; }
  function escapeHtml(text) { return String(text == null ? "" : text).replace(/[&<>"']/g, function (c) { return ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#039;" })[c]; }); }
  function escapeAttr(text) { return escapeHtml(text); }
})();
