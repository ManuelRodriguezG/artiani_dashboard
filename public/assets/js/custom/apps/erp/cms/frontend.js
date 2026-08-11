/*
 * Documentacion IA: Codex GPT-5, 2026-08-10.
 * Proposito: render read-only de plantillas frontend administrables desde CMS.
 * Impacto: muestra layouts, componentes y contrato renderer sin escribir BD ni tocar archivos del frontend.
 * Contrato: consume GET interno protegido `/cms/frontend_admin_manifest_erp`.
 */
(function () {
  "use strict";

  document.addEventListener("DOMContentLoaded", function () {
    cargarManifest();
  });

  function cargarManifest() {
    setText("cms_frontend_estado", "Cargando");
    getJson("/cms/frontend_admin_manifest_erp").then(function (response) {
      var data = response.depurar || {};
      renderResumen(data);
      renderPlantillas(data.plantillas_vista || []);
      renderComponentes(data.componentes || []);
      renderRenderer(data.renderer_frontend || {}, data.guardrails || {});
      setText("cms_frontend_json", JSON.stringify(data, null, 2));
      setText("cms_frontend_estado", "Read-only");
    }).catch(function (error) {
      setText("cms_frontend_estado", "Error");
      setText("cms_frontend_json", JSON.stringify({ error: true, mensaje: error.message || String(error) }, null, 2));
    });
  }

  function renderResumen(data) {
    setText("cms_frontend_layouts_total", (data.layouts || []).length);
    setText("cms_frontend_componentes_total", (data.componentes || []).length);
    setText("cms_frontend_plantillas_total", (data.plantillas_vista || []).length);
    setText("cms_frontend_activa", data.plantilla_activa_home || "-");
  }

  function renderPlantillas(plantillas) {
    var node = $("cms_frontend_plantillas");
    if (!node) return;
    if (!plantillas.length) {
      node.innerHTML = '<div class="text-muted">Sin plantillas de vista declaradas.</div>';
      return;
    }
    node.innerHTML = plantillas.map(function (plantilla) {
      return '<div class="cms-front-card mb-4">' +
        '<div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-3">' +
          '<div><div class="fw-bold">' + escapeHtml(plantilla.nombre || plantilla.codigo) + '</div><div class="text-muted fs-8">' + escapeHtml(plantilla.codigo || "") + ' / ' + escapeHtml(plantilla.layout || "") + '</div></div>' +
          '<span class="badge badge-light-warning">' + escapeHtml(plantilla.estatus || "readonly") + '</span>' +
        '</div>' +
        '<div class="table-responsive"><table class="table table-row-dashed fs-8 gy-2 mb-0"><tbody>' +
          (plantilla.secciones || []).map(function (seccion) {
            return '<tr><td class="fw-semibold">' + escapeHtml(seccion.orden) + '</td><td><code>' + escapeHtml(seccion.slot) + '</code></td><td>' + escapeHtml(seccion.componente) + '</td><td class="text-end"><span class="badge badge-light-info">' + escapeHtml(seccion.variante) + '</span></td></tr>';
          }).join("") +
        '</tbody></table></div>' +
      '</div>';
    }).join("");
  }

  function renderComponentes(componentes) {
    var node = $("cms_frontend_componentes");
    if (!node) return;
    if (!componentes.length) {
      node.innerHTML = '<div class="text-muted">Sin componentes declarados.</div>';
      return;
    }
    node.innerHTML = componentes.map(function (componente) {
      return '<div class="cms-front-card mb-4">' +
        '<div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-3">' +
          '<div><div class="fw-bold">' + escapeHtml(componente.nombre || componente.codigo) + '</div><div class="text-muted fs-8">' + escapeHtml(componente.codigo || "") + '</div></div>' +
          '<span class="badge badge-light-primary">' + escapeHtml((componente.variantes || []).length) + ' variantes</span>' +
        '</div>' +
        '<div class="mb-2"><span class="text-muted fs-8 text-uppercase fw-bold">Bloques</span><div class="cms-front-chips mt-1">' + chips(componente.bloques_permitidos || [], "badge-light-success") + '</div></div>' +
        '<div class="mb-2"><span class="text-muted fs-8 text-uppercase fw-bold">Variantes</span><div class="cms-front-chips mt-1">' + chips(componente.variantes || [], "badge-light-info") + '</div></div>' +
        '<div><span class="text-muted fs-8 text-uppercase fw-bold">Slots compatibles</span><div class="cms-front-chips mt-1">' + chips(componente.slots_compatibles || [], "badge-light-secondary") + '</div></div>' +
      '</div>';
    }).join("");
  }

  function renderRenderer(renderer, guardrails) {
    var node = $("cms_frontend_renderer");
    if (!node) return;
    node.innerHTML = '<div class="row g-4">' +
      '<div class="col-lg-6"><div class="border rounded p-4 h-100"><div class="fw-bold mb-3">Consumo frontend</div>' +
        fila("Arranque", renderer.consume_desde || "/ecommercePublico/configuracion_inicial") +
        fila("Pagina", renderer.pagina || "/ecommercePublico/contenido_pagina") +
        fila("Contrato", renderer.contrato || "plantilla_vista + contenido.slots") +
      '</div></div>' +
      '<div class="col-lg-6"><div class="border rounded p-4 h-100"><div class="fw-bold mb-3">Guardrails</div>' +
        fila("HTML libre", guardrails.no_html_libre ? "Bloqueado" : "No definido") +
        fila("CSS libre", guardrails.no_css_libre ? "Bloqueado" : "No definido") +
        fila("JS libre", guardrails.no_js_libre ? "Bloqueado" : "No definido") +
      '</div></div>' +
    '</div>';
  }

  function fila(label, valor) {
    return '<div class="d-flex justify-content-between gap-3 fs-7 mb-2"><span class="text-muted">' + escapeHtml(label) + '</span><code class="text-end">' + escapeHtml(valor) + '</code></div>';
  }

  function chips(items, clase) {
    return items.map(function (item) {
      return '<span class="badge ' + clase + '">' + escapeHtml(item) + '</span>';
    }).join("");
  }

  function getJson(url) {
    return fetch(url, { credentials: "same-origin", headers: { "Accept": "application/json" } }).then(function (response) {
      return response.json();
    });
  }

  function $(id) { return document.getElementById(id); }

  function setText(id, value) {
    var node = $(id);
    if (node) node.textContent = String(value == null ? "" : value);
  }

  function escapeHtml(value) {
    var div = document.createElement("div");
    div.textContent = value == null ? "" : String(value);
    return div.innerHTML;
  }
})();
