/*
 * Documentacion IA: Codex GPT-5, 2026-08-10.
 * Proposito: render read-only de plantillas frontend administrables desde CMS.
 * Impacto: muestra layouts, componentes y contrato renderer sin escribir BD ni tocar archivos del frontend.
 * Contrato: consume GET interno protegido `/cms/frontend_admin_manifest_erp`.
 */
(function () {
  "use strict";

  var estado = {
    manifest: {},
    cmsEstado: {},
    plantillaActiva: "",
    seccionActiva: 0
  };

  document.addEventListener("DOMContentLoaded", function () {
    cargarManifest();
  });

  function cargarManifest() {
    setText("cms_frontend_estado", "Cargando");
    Promise.all([
      getJson("/cms/frontend_admin_manifest_erp"),
      getJson("/cms/frontend_admin_estado_erp")
    ]).then(function (responses) {
      var data = responses[0].depurar || {};
      var estadoApi = responses[1].depurar || {};
      estado.manifest = data;
      estado.cmsEstado = estadoApi;
      estado.plantillaActiva = data.plantilla_activa_home || ((data.plantillas_vista || [])[0] || {}).codigo || "";
      estado.seccionActiva = 0;
      renderResumen(data);
      renderBuilder(data);
      renderPlantillas(data.plantillas_vista || []);
      renderComponentes(data.componentes || []);
      renderRenderer(data.renderer_frontend || {}, data.guardrails || {});
      renderEsquema(estadoApi.esquema || {}, estadoApi.post_bloqueados || []);
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
    var tema = data.tema_activo || {};
    setText("cms_frontend_activa", tema.codigo || data.plantilla_activa_home || "-");
  }

  function renderBuilder(data) {
    renderTemaSelector(data.temas_disponibles || [], data.tema_activo || {});
    renderBuilderPlantillas(data.plantillas_vista || []);
    renderBuilderCanvas();
    renderPaleta(data.componentes || []);
  }

  function renderTemaSelector(temas, temaActivo) {
    var node = $("cms_frontend_tema_selector");
    if (!node) return;
    node.innerHTML = (temas.length ? temas : [temaActivo]).map(function (tema) {
      var codigo = tema.codigo || "";
      return '<option value="' + escapeHtml(codigo) + '"' + (codigo === (temaActivo.codigo || "") ? " selected" : "") + '>' + escapeHtml(tema.nombre || codigo) + '</option>';
    }).join("");
    node.disabled = true;
  }

  function renderBuilderPlantillas(plantillas) {
    var node = $("cms_frontend_builder_plantillas");
    if (!node) return;
    if (!plantillas.length) {
      node.innerHTML = '<div class="text-muted fs-7">Sin plantillas declaradas.</div>';
      return;
    }
    node.innerHTML = plantillas.map(function (plantilla) {
      var activa = plantilla.codigo === estado.plantillaActiva;
      return '<button type="button" class="cms-front-template-btn mb-3' + (activa ? ' is-active' : '') + '" data-front-template="' + escapeHtml(plantilla.codigo || "") + '">' +
        '<div class="d-flex justify-content-between gap-2 align-items-start">' +
          '<div><div class="fw-bold">' + escapeHtml(plantilla.nombre || plantilla.codigo) + '</div><div class="text-muted fs-8">' + escapeHtml(plantilla.pagina || "") + ' / ' + escapeHtml(plantilla.layout || "") + '</div></div>' +
          '<span class="badge badge-light-secondary">' + escapeHtml((plantilla.secciones || []).length) + '</span>' +
        '</div>' +
      '</button>';
    }).join("");
    Array.prototype.forEach.call(node.querySelectorAll("[data-front-template]"), function (button) {
      button.addEventListener("click", function () {
        estado.plantillaActiva = button.getAttribute("data-front-template") || "";
        estado.seccionActiva = 0;
        renderBuilder(estado.manifest);
      });
    });
  }

  function renderBuilderCanvas() {
    var plantilla = plantillaActual();
    var preview = $("cms_frontend_preview");
    if (!preview) return;
    if (!plantilla) {
      setText("cms_frontend_builder_titulo", "Plantilla");
      setText("cms_frontend_builder_subtitulo", "Sin plantilla seleccionada.");
      preview.innerHTML = '<div class="text-muted">No hay plantilla para previsualizar.</div>';
      renderInspector(null, null);
      return;
    }
    setText("cms_frontend_builder_titulo", plantilla.nombre || plantilla.codigo || "Plantilla");
    setText("cms_frontend_builder_subtitulo", (plantilla.codigo || "") + " / " + (plantilla.layout || "") + " / " + (plantilla.pagina || ""));
    preview.innerHTML = '<div class="cms-front-page-frame">' +
      '<div class="cms-front-page-top"><div class="cms-front-logo">ARTIANI</div><div class="text-muted fs-8">Menu / Buscar / Carrito</div></div>' +
      (plantilla.secciones || []).map(renderPreviewSeccion).join("") +
      '<div class="cms-front-preview-section"><div class="text-muted fs-8 text-uppercase fw-bold">Footer</div><div class="fw-semibold">Servicios, ayuda, contacto y politicas</div></div>' +
    '</div>';
    Array.prototype.forEach.call(preview.querySelectorAll("[data-front-section]"), function (button) {
      button.addEventListener("click", function () {
        estado.seccionActiva = parseInt(button.getAttribute("data-front-section") || "0", 10);
        renderBuilderCanvas();
      });
    });
    renderInspector(plantilla, (plantilla.secciones || [])[estado.seccionActiva] || null);
  }

  function renderPreviewSeccion(seccion, index) {
    var activa = index === estado.seccionActiva;
    var header = '<button type="button" class="cms-front-section-btn mb-3' + (activa ? ' is-active' : '') + '" data-front-section="' + index + '">' +
      '<div class="d-flex justify-content-between gap-3"><span><span class="fw-bold">' + escapeHtml(seccion.orden || index + 1) + '. ' + escapeHtml(seccion.slot || "") + '</span><span class="text-muted fs-8 d-block">' + escapeHtml(seccion.componente || "") + ' / ' + escapeHtml(seccion.variante || "") + '</span></span><span class="badge badge-light-info">Ver</span></div>' +
    '</button>';
    return '<div class="cms-front-preview-section' + (activa ? ' is-selected' : '') + '">' + header + renderComponentePreview(seccion) + '</div>';
  }

  function renderComponentePreview(seccion) {
    var componente = seccion.componente || "";
    if (componente === "HeroSlider") {
      return '<div class="cms-front-hero-preview"><div><div class="fs-7 text-uppercase fw-bold mb-2">Hero banner</div><h2 class="text-white fw-bold mb-2">Banner principal administrable</h2><div class="opacity-75">Imagen desktop/mobile, titulo, subtitulo y CTA desde CMS Contenido.</div></div></div>';
    }
    if (componente === "PromoStrip") {
      return '<div class="cms-front-promo-preview"><div>Promo 1</div><div>Promo 2</div><div>Promo 3</div></div>';
    }
    if (componente === "CategoryGrid" || componente === "ImageCardGrid") {
      return '<div class="cms-front-grid-preview">' + [1, 2, 3, 4].map(function (item) {
        return '<div class="cms-front-card-preview"><div class="text-muted fs-8">Card ' + item + '</div><div class="fw-semibold">Imagen + CTA</div></div>';
      }).join("") + '</div>';
    }
    if (componente === "ProductCarousel") {
      return '<div class="cms-front-products-preview">' + [1, 2, 3, 4].map(function (item) {
        return '<div class="cms-front-product-preview"><div class="ratio ratio-1x1 bg-light rounded mb-2"></div><div class="fw-semibold fs-8">Producto ERP</div><div class="text-muted fs-8">Precio/API</div></div>';
      }).join("") + '</div>';
    }
    return '<div class="border rounded p-4 bg-light"><div class="fw-semibold">Bloque de contenido seguro</div><div class="text-muted fs-8">Renderizado por componente permitido.</div></div>';
  }

  function renderInspector(plantilla, seccion) {
    var node = $("cms_frontend_inspector");
    if (!node) return;
    if (!plantilla || !seccion) {
      node.innerHTML = '<div class="text-muted fs-7">Selecciona una seccion para inspeccionarla.</div>';
      return;
    }
    var componente = componentePorCodigo(seccion.componente);
    node.innerHTML =
      fila("Plantilla", plantilla.codigo || "") +
      fila("Pagina", plantilla.pagina || "") +
      fila("Layout", plantilla.layout || "") +
      '<div class="separator my-4"></div>' +
      fila("Slot", seccion.slot || "") +
      fila("Componente", seccion.componente || "") +
      fila("Variante", seccion.variante || "") +
      fila("Orden", seccion.orden || "") +
      '<div class="mt-4"><div class="text-muted fs-8 text-uppercase fw-bold mb-2">Bloques permitidos</div><div class="cms-front-chips">' + chips((componente || {}).bloques_permitidos || [], "badge-light-success") + '</div></div>' +
      '<div class="mt-4"><div class="text-muted fs-8 text-uppercase fw-bold mb-2">Regla</div><div class="alert alert-light-info py-3 px-4 fs-7 mb-0">El CMS guarda contenido y configuracion JSON; el frontend produce el HTML final con este componente.</div></div>';
  }

  function renderPaleta(componentes) {
    var node = $("cms_frontend_paleta");
    if (!node) return;
    node.innerHTML = (componentes || []).map(function (componente) {
      return '<div class="cms-front-component-item mb-3">' +
        '<div class="fw-bold">' + escapeHtml(componente.nombre || componente.codigo) + '</div>' +
        '<div class="text-muted fs-8 mb-2">' + escapeHtml(componente.codigo || "") + '</div>' +
        '<div class="cms-front-chips">' + chips(componente.variantes || [], "badge-light-info") + '</div>' +
      '</div>';
    }).join("");
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

  function renderEsquema(esquema, postBloqueados) {
    var node = $("cms_frontend_esquema");
    if (!node) return;
    var auditoria = esquema.auditoria || {};
    var plan = esquema.plan || {};
    var tablas = auditoria.auditoria || {};
    var nombres = Object.keys(tablas);
    node.innerHTML = '<div class="row g-4">' +
      '<div class="col-lg-4"><div class="border rounded p-4 h-100">' +
        fila("Modo", plan.read_only ? "Read-only" : "No definido") +
        fila("DDL propuestos", plan.ddl_total || 0) +
        fila("Tablas faltantes", auditoria.tablas_faltantes || 0) +
      '</div></div>' +
      '<div class="col-lg-8"><div class="border rounded p-4 h-100">' +
        '<div class="fw-bold mb-3">Tablas CMS frontend</div>' +
        (nombres.length ? nombres.map(function (tabla) {
          var item = tablas[tabla] || {};
          return '<div class="d-flex justify-content-between gap-3 fs-7 mb-2"><code>' + escapeHtml(tabla) + '</code><span class="badge ' + (item.existe ? 'badge-light-success' : 'badge-light-warning') + '">' + (item.existe ? 'Existe' : 'Pendiente') + '</span></div>';
        }).join("") : '<div class="text-muted fs-7">Sin auditoria de tablas.</div>') +
      '</div></div>' +
      '<div class="col-12"><div class="border rounded p-4">' +
        '<div class="fw-bold mb-3">POST bloqueados por contrato</div>' +
        (postBloqueados.length ? postBloqueados.map(function (endpoint) {
          return '<div class="d-flex justify-content-between gap-3 fs-7 mb-2"><code>' + escapeHtml(endpoint.ruta) + '</code><span class="badge badge-light-warning">' + escapeHtml(endpoint.estado || 'bloqueado') + '</span></div>';
        }).join("") : '<div class="text-muted fs-7">Sin endpoints POST declarados.</div>') +
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

  function plantillaActual() {
    var plantillas = estado.manifest.plantillas_vista || [];
    for (var i = 0; i < plantillas.length; i++) {
      if (plantillas[i].codigo === estado.plantillaActiva) return plantillas[i];
    }
    return plantillas[0] || null;
  }

  function componentePorCodigo(codigo) {
    var componentes = estado.manifest.componentes || [];
    for (var i = 0; i < componentes.length; i++) {
      if (componentes[i].codigo === codigo) return componentes[i];
    }
    return null;
  }
})();
