/*
 * Documentacion IA: Codex GPT-5, 2026-08-14.
 * Proposito: biblioteca local inicial para media CMS frontend.
 * Impacto: permite seleccionar, validar, previsualizar, archivar y limpiar imagenes locales antes de persistencia real.
 * Contrato: no sube archivos, no borra fisicos, no escribe BD; usa localStorage como maqueta operativa.
 */
(function () {
  "use strict";

  var STORAGE_KEY = "erp_cms_media_biblioteca_local_v1";
  var MAX_BYTES = 2 * 1024 * 1024;
  var permitidos = ["image/jpeg", "image/png", "image/webp"];

  var estado = {
    items: [],
    activo: "",
    archivoPendiente: null,
    dataUrlPendiente: ""
  };

  document.addEventListener("DOMContentLoaded", function () {
    cargarLocal();
    bindEventos();
    renderTodo();
  });

  function bindEventos() {
    on("cms_media_archivo", "change", prepararArchivo);
    on("cms_media_agregar", "click", agregarArchivoLocal);
    on("cms_media_buscar", "input", renderBibliotecaMedia);
    on("cms_media_filtro_uso", "change", renderBibliotecaMedia);
    on("cms_media_limpiar_archivados", "click", limpiarArchivados);

    var biblioteca = $("cms_media_biblioteca");
    if (biblioteca) {
      biblioteca.addEventListener("click", function (event) {
        var button = event.target.closest("[data-media-action]");
        if (button) {
          ejecutarAccionMedia(button.getAttribute("data-media-action"), button.getAttribute("data-media-id"));
          return;
        }
        var card = event.target.closest("[data-media-id]");
        if (!card) return;
        seleccionarMedia(card.getAttribute("data-media-id"));
      });
    }
  }

  function prepararArchivo() {
    var input = $("cms_media_archivo");
    var file = input && input.files && input.files[0] ? input.files[0] : null;
    estado.archivoPendiente = null;
    estado.dataUrlPendiente = "";
    if (!file) return;
    var bloqueo = validarArchivo(file);
    if (bloqueo) {
      setEstado(bloqueo, "badge-light-danger");
      input.value = "";
      return;
    }
    var reader = new FileReader();
    reader.onload = function () {
      estado.archivoPendiente = file;
      estado.dataUrlPendiente = String(reader.result || "");
      setEstado("Imagen lista", "badge-light-success");
      renderPreviewTemporal(file, estado.dataUrlPendiente);
    };
    reader.onerror = function () {
      setEstado("No se pudo leer", "badge-light-danger");
    };
    reader.readAsDataURL(file);
  }

  function agregarArchivoLocal() {
    var file = estado.archivoPendiente;
    if (!file || !estado.dataUrlPendiente) {
      setEstado("Selecciona imagen", "badge-light-warning");
      return;
    }
    var alt = valor("cms_media_alt").trim();
    if (!alt) {
      setEstado("Falta alt text", "badge-light-danger");
      return;
    }
    var item = {
      id: "media_" + Date.now(),
      nombre: file.name,
      mime: file.type,
      bytes: file.size,
      url: estado.dataUrlPendiente,
      alt: alt,
      uso: valor("cms_media_uso") || "home",
      tipo: valor("cms_media_tipo") || "banner",
      estatus: "activo",
      creado_en: new Date().toISOString()
    };
    estado.items.unshift(item);
    estado.activo = item.id;
    estado.archivoPendiente = null;
    estado.dataUrlPendiente = "";
    if ($("cms_media_archivo")) $("cms_media_archivo").value = "";
    setValue("cms_media_alt", "");
    guardarLocal();
    renderTodo();
    setEstado("Agregada local", "badge-light-success");
  }

  function validarArchivo(file) {
    if (permitidos.indexOf(file.type) === -1) return "Tipo no permitido";
    if (file.size > MAX_BYTES) return "Supera 2 MB";
    return "";
  }

  function renderTodo() {
    renderBibliotecaMedia();
    renderDetalle();
  }

  function renderBibliotecaMedia() {
    var node = $("cms_media_biblioteca");
    if (!node) return;
    var items = filtrarItems();
    if (!items.length) {
      node.innerHTML = '<div class="text-muted">Sin imagenes en la biblioteca local.</div>';
      return;
    }
    node.innerHTML = items.map(function (item) {
      return '<div class="cms-media-card ' + (item.id === estado.activo ? 'is-active' : '') + '" data-media-id="' + escapeAttr(item.id) + '">' +
        '<img class="cms-media-thumb" src="' + escapeAttr(item.url) + '" alt="' + escapeAttr(item.alt) + '">' +
        '<div class="p-3">' +
          '<div class="fw-bold text-truncate">' + escapeHtml(item.nombre) + '</div>' +
          '<div class="text-muted fs-8 text-truncate">' + escapeHtml(item.alt) + '</div>' +
          '<div class="d-flex justify-content-between align-items-center mt-3 gap-2">' +
            '<span class="badge ' + (item.estatus === "archivado" ? 'badge-light-warning' : 'badge-light-success') + '">' + escapeHtml(item.estatus) + '</span>' +
            '<span class="text-muted fs-8">' + escapeHtml(item.uso) + ' / ' + escapeHtml(item.tipo) + '</span>' +
          '</div>' +
          '<div class="cms-media-actions mt-3">' +
            '<button class="btn btn-sm btn-light-primary" type="button" data-media-action="copiar" data-media-id="' + escapeAttr(item.id) + '"><i class="bi bi-clipboard"></i></button>' +
            '<button class="btn btn-sm btn-light-warning" type="button" data-media-action="archivar" data-media-id="' + escapeAttr(item.id) + '"><i class="bi bi-archive"></i></button>' +
            '<button class="btn btn-sm btn-light-danger" type="button" data-media-action="quitar" data-media-id="' + escapeAttr(item.id) + '"><i class="bi bi-trash"></i></button>' +
          '</div>' +
        '</div>' +
      '</div>';
    }).join("");
  }

  function renderDetalle() {
    var item = mediaActiva();
    var node = $("cms_media_detalle");
    var visual = $("ecom_cms_visual");
    if (!node) return;
    if (!item) {
      node.innerHTML = '<div class="text-muted">Selecciona una imagen para ver detalles.</div>';
      if (visual) visual.innerHTML = '<div class="ecom-cms-preview-img d-flex align-items-center justify-content-center text-muted">Sin imagen</div>';
      return;
    }
    node.innerHTML = '<img class="cms-media-detail-img mb-4" src="' + escapeAttr(item.url) + '" alt="' + escapeAttr(item.alt) + '">' +
      '<h4 class="fw-bold mb-2">' + escapeHtml(item.nombre) + '</h4>' +
      '<div class="text-muted fs-7 mb-4">' + escapeHtml(item.alt) + '</div>' +
      '<div class="cms-media-meta fs-7 mb-4">' +
        meta("Uso", item.uso) +
        meta("Tipo", item.tipo) +
        meta("Formato", item.mime) +
        meta("Peso", formatoBytes(item.bytes)) +
        meta("Estatus", item.estatus) +
        meta("Creado", item.creado_en ? item.creado_en.substring(0, 10) : "") +
      '</div>' +
      '<div class="cms-media-actions">' +
        '<button class="btn btn-sm btn-light-primary" type="button" data-media-detail-action="copiar"><i class="bi bi-clipboard"></i> Copiar referencia</button>' +
        '<button class="btn btn-sm btn-light-warning" type="button" data-media-detail-action="archivar"><i class="bi bi-archive"></i> Archivar</button>' +
      '</div>';
    if (visual) {
      visual.innerHTML = '<div class="text-muted fs-8 text-uppercase fw-bold mb-2">Preview de uso</div><img class="ecom-cms-preview-img" src="' + escapeAttr(item.url) + '" alt="' + escapeAttr(item.alt) + '">';
    }
    bindDetalleAcciones();
  }

  function bindDetalleAcciones() {
    Array.prototype.forEach.call(document.querySelectorAll("[data-media-detail-action]"), function (button) {
      button.addEventListener("click", function () {
        var item = mediaActiva();
        if (!item) return;
        ejecutarAccionMedia(button.getAttribute("data-media-detail-action"), item.id);
      });
    });
  }

  function renderPreviewTemporal(file, dataUrl) {
    var visual = $("ecom_cms_visual");
    if (!visual) return;
    visual.innerHTML = '<div class="text-muted fs-8 text-uppercase fw-bold mb-2">Preview antes de agregar</div><img class="ecom-cms-preview-img" src="' + escapeAttr(dataUrl) + '" alt="' + escapeAttr(file.name) + '">';
  }

  function ejecutarAccionMedia(accion, id) {
    var item = buscarMedia(id);
    if (!item) return;
    if (accion === "copiar") {
      copiarReferencia(item);
      return;
    }
    if (accion === "archivar") {
      item.estatus = item.estatus === "archivado" ? "activo" : "archivado";
    }
    if (accion === "quitar") {
      estado.items = estado.items.filter(function (actual) { return actual.id !== id; });
      if (estado.activo === id) estado.activo = estado.items[0] ? estado.items[0].id : "";
    }
    guardarLocal();
    renderTodo();
  }

  function copiarReferencia(item) {
    var payload = JSON.stringify({ media_id: item.id, alt: item.alt, uso: item.uso, tipo: item.tipo }, null, 2);
    if (navigator.clipboard) navigator.clipboard.writeText(payload);
    setEstado("Referencia copiada", "badge-light-success");
  }

  function limpiarArchivados() {
    estado.items = estado.items.filter(function (item) { return item.estatus !== "archivado"; });
    if (!buscarMedia(estado.activo)) estado.activo = estado.items[0] ? estado.items[0].id : "";
    guardarLocal();
    renderTodo();
    setEstado("Archivados limpiados", "badge-light-success");
  }

  function seleccionarMedia(id) {
    estado.activo = id;
    renderTodo();
  }

  function filtrarItems() {
    var busqueda = valor("cms_media_buscar").toLowerCase();
    var uso = valor("cms_media_filtro_uso");
    return estado.items.filter(function (item) {
      var coincideUso = !uso || item.uso === uso;
      var texto = [item.nombre, item.alt, item.uso, item.tipo].join(" ").toLowerCase();
      return coincideUso && (!busqueda || texto.indexOf(busqueda) !== -1);
    });
  }

  function mediaActiva() {
    return buscarMedia(estado.activo);
  }

  function buscarMedia(id) {
    return estado.items.filter(function (item) { return item.id === id; })[0] || null;
  }

  function cargarLocal() {
    try {
      var raw = localStorage.getItem(STORAGE_KEY);
      estado.items = raw ? JSON.parse(raw) : [];
      estado.activo = estado.items[0] ? estado.items[0].id : "";
    } catch (error) {
      estado.items = [];
      estado.activo = "";
    }
  }

  function guardarLocal() {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(estado.items));
  }

  function meta(label, value) {
    return '<div><div class="text-muted fs-8 text-uppercase fw-bold">' + escapeHtml(label) + '</div><div class="fw-semibold">' + escapeHtml(value || "") + '</div></div>';
  }

  function formatoBytes(bytes) {
    bytes = parseInt(bytes || 0, 10) || 0;
    if (bytes < 1024) return bytes + " B";
    if (bytes < 1024 * 1024) return Math.round(bytes / 1024) + " KB";
    return (bytes / (1024 * 1024)).toFixed(2) + " MB";
  }

  function setEstado(texto, clase) {
    var node = $("cms_media_estado");
    if (!node) return;
    node.className = "badge " + (clase || "badge-light-primary");
    node.textContent = texto;
  }

  function valor(id) {
    var node = $(id);
    return node ? String(node.value || "") : "";
  }

  function setValue(id, value) {
    var node = $(id);
    if (node) node.value = value == null ? "" : String(value);
  }

  function on(id, eventName, callback) {
    var node = $(id);
    if (node) node.addEventListener(eventName, callback);
  }

  function $(id) { return document.getElementById(id); }

  function escapeHtml(value) {
    var div = document.createElement("div");
    div.textContent = value == null ? "" : String(value);
    return div.innerHTML;
  }

  function escapeAttr(value) {
    return escapeHtml(value).replace(/"/g, "&quot;");
  }
})();
