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
    contenidos: {},
    plantillaActiva: "",
    seccionActiva: 0,
    componenteActivo: ""
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
      estado.componenteActivo = ((data.componentes || [])[0] || {}).codigo || "";
      return cargarContenidoPlantillas(data).then(function () {
        return { data: data, estadoApi: estadoApi };
      });
    }).then(function (payload) {
      var data = payload.data || {};
      var estadoApi = payload.estadoApi || {};
      renderResumen(data);
      renderBuilder(data);
      renderPlantillas(data.plantillas_vista || []);
      renderComponentes(data.componentes || []);
      renderActivaciones(data.activaciones || []);
      renderRenderer(data.renderer_frontend || {}, data.guardrails || {});
      renderEsquema(estadoApi.esquema || {}, estadoApi.post_bloqueados || []);
      setText("cms_frontend_json", JSON.stringify(data, null, 2));
      setText("cms_frontend_contenido_estado", "Contenido conectado");
      setText("cms_frontend_estado", "Read-only");
    }).catch(function (error) {
      setText("cms_frontend_estado", "Error");
      setText("cms_frontend_json", JSON.stringify({ error: true, mensaje: error.message || String(error) }, null, 2));
    });
  }

  function cargarContenidoPlantillas(data) {
    var paginas = {};
    (data.plantillas_vista || []).forEach(function (plantilla) {
      if (plantilla && plantilla.pagina) {
        paginas[plantilla.pagina] = true;
      }
    });
    var solicitudes = Object.keys(paginas).map(function (pagina) {
      var url = "/cms/contenido_admin_pagina_erp?pagina=" + encodeURIComponent(pagina);
      if (pagina === "categoria") {
        url += "&categoria=peces";
      }
      return getJson(url).then(function (response) {
        estado.contenidos[pagina] = response.depurar || {};
      }).catch(function () {
        estado.contenidos[pagina] = {};
      });
    });
    if (!solicitudes.length) {
      return Promise.resolve();
    }
    return Promise.all(solicitudes);
  }

  function renderResumen(data) {
    setText("cms_frontend_layouts_total", (data.layouts || []).length);
    setText("cms_frontend_componentes_total", (data.componentes || []).length);
    setText("cms_frontend_plantillas_total", $("cms_frontend_activaciones") ? (data.activaciones || []).length : (data.plantillas_vista || []).length);
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
    var contenido = contenidoActual(plantilla);
    var bloquesTotal = contarBloquesContenido(contenido);
    setText("cms_frontend_builder_subtitulo", (plantilla.codigo || "") + " / " + (plantilla.layout || "") + " / " + (plantilla.pagina || "") + " / " + bloquesTotal + " bloques");
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
    var bloques = bloquesDeSeccion(seccion);
    var header = '<button type="button" class="cms-front-section-btn mb-3' + (activa ? ' is-active' : '') + '" data-front-section="' + index + '">' +
      '<div class="d-flex justify-content-between gap-3"><span><span class="fw-bold">' + escapeHtml(seccion.orden || index + 1) + '. ' + escapeHtml(seccion.slot || "") + '</span><span class="text-muted fs-8 d-block">' + escapeHtml(seccion.componente || "") + ' / ' + escapeHtml(seccion.variante || "") + '</span></span><span class="badge badge-light-info">' + escapeHtml(bloques.length) + ' bloques</span></div>' +
    '</button>';
    return '<div class="cms-front-preview-section' + (activa ? ' is-selected' : '') + '">' + header + renderComponentePreview(seccion) + '</div>';
  }

  function renderComponentePreview(seccion) {
    var componente = seccion.componente || "";
    var bloques = bloquesDeSeccion(seccion);
    var principal = bloques[0] || {};
    if (componente === "HeroSlider") {
      return '<div class="cms-front-hero-preview"><div><div class="fs-7 text-uppercase fw-bold mb-2">' + escapeHtml(principal.tipo || "hero_banner") + '</div><h2 class="text-white fw-bold mb-2">' + escapeHtml(principal.titulo || "Banner principal administrable") + '</h2><div class="opacity-75">' + escapeHtml(principal.subtitulo || "Imagen desktop/mobile, titulo, subtitulo y CTA desde CMS Contenido.") + '</div>' + renderCta(principal) + '</div></div>' + renderFuenteContenido(bloques);
    }
    if (componente === "PromoStrip") {
      var promos = bloques.length ? bloques : [{ texto: "Promo pendiente" }];
      return '<div class="cms-front-promo-preview">' + promos.slice(0, 3).map(function (bloque) {
        return '<div><div class="fw-semibold">' + escapeHtml(bloque.texto || bloque.titulo || "Promo") + '</div><div class="text-muted fs-8">' + escapeHtml((bloque.cta || {}).label || "") + '</div></div>';
      }).join("") + '</div>' + renderFuenteContenido(bloques);
    }
    if (componente === "CategoryGrid" || componente === "ImageCardGrid") {
      var cards = extraerItemsGrid(principal);
      return '<div class="cms-front-grid-preview">' + cards.slice(0, 4).map(function (item, index) {
        return '<div class="cms-front-card-preview"><div class="text-muted fs-8">Card ' + (index + 1) + '</div><div class="fw-semibold">' + escapeHtml(item.titulo || item.label || "Imagen + CTA") + '</div><div class="text-muted fs-8">' + escapeHtml(item.url || "") + '</div></div>';
      }).join("") + '</div>';
    }
    if (componente === "ProductCarousel") {
      var colecciones = bloques.length ? bloques : [principal];
      return '<div class="mb-3 fw-semibold">' + escapeHtml(principal.titulo || "Coleccion de productos") + '</div><div class="cms-front-products-preview">' + [1, 2, 3, 4].map(function (item) {
        return '<div class="cms-front-product-preview"><div class="ratio ratio-1x1 bg-light rounded mb-2"></div><div class="fw-semibold fs-8">Producto ERP</div><div class="text-muted fs-8">' + escapeHtml((colecciones[0].source || {}).endpoint || "Precio/API") + '</div></div>';
      }).join("") + '</div>';
    }
    return '<div class="border rounded p-4 bg-light"><div class="fw-semibold">' + escapeHtml(principal.titulo || "Bloque de contenido seguro") + '</div><div class="text-muted fs-8">' + escapeHtml(principal.contenido_html || "Renderizado por componente permitido.") + '</div></div>' + renderFuenteContenido(bloques);
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
      fila("Bloques conectados", bloquesDeSeccion(seccion).length) +
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
    renderComponentesSelector(componentes);
    renderComponenteDetalle();
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

  function renderComponentesSelector(componentes) {
    var node = $("cms_frontend_componentes_selector");
    if (!node) return;
    if (!componentes.length) {
      node.innerHTML = '<div class="text-muted fs-7">Sin componentes declarados.</div>';
      return;
    }
    node.innerHTML = componentes.map(function (componente) {
      var activo = componente.codigo === estado.componenteActivo;
      return '<button type="button" class="cms-front-component-btn mb-3' + (activo ? ' is-active' : '') + '" data-front-component="' + escapeHtml(componente.codigo || "") + '">' +
        '<div class="d-flex justify-content-between gap-2 align-items-start">' +
          '<div><div class="fw-bold">' + escapeHtml(componente.nombre || componente.codigo) + '</div><div class="text-muted fs-8">' + escapeHtml(componente.codigo || "") + '</div></div>' +
          '<span class="badge badge-light-primary">' + escapeHtml((componente.variantes || []).length) + '</span>' +
        '</div>' +
      '</button>';
    }).join("");
    Array.prototype.forEach.call(node.querySelectorAll("[data-front-component]"), function (button) {
      button.addEventListener("click", function () {
        estado.componenteActivo = button.getAttribute("data-front-component") || "";
        renderComponentes(estado.manifest.componentes || []);
      });
    });
  }

  function renderComponenteDetalle() {
    var componente = componentePorCodigo(estado.componenteActivo) || ((estado.manifest.componentes || [])[0] || null);
    var titulo = $("cms_frontend_componente_titulo");
    var subtitulo = $("cms_frontend_componente_subtitulo");
    var preview = $("cms_frontend_componente_preview");
    var compatibilidad = $("cms_frontend_componente_compatibilidad");
    var uso = $("cms_frontend_componente_uso");
    if (!titulo && !subtitulo && !preview && !compatibilidad && !uso) return;
    if (!componente) {
      setText("cms_frontend_componente_titulo", "Componente");
      setText("cms_frontend_componente_subtitulo", "Sin componente seleccionado.");
      if (preview) preview.innerHTML = '<div class="text-muted">No hay preview disponible.</div>';
      if (compatibilidad) compatibilidad.innerHTML = '<div class="text-muted">Sin compatibilidad declarada.</div>';
      if (uso) uso.innerHTML = '<div class="text-muted">Sin uso declarado.</div>';
      return;
    }
    estado.componenteActivo = componente.codigo || estado.componenteActivo;
    setText("cms_frontend_componente_titulo", componente.nombre || componente.codigo || "Componente");
    setText("cms_frontend_componente_subtitulo", (componente.codigo || "") + " / " + ((estado.manifest.tema_activo || {}).codigo || "tema"));
    if (preview) {
      preview.innerHTML = renderComponentePreview({ componente: componente.codigo, variante: (componente.variantes || [])[0] || "" });
    }
    if (compatibilidad) {
      compatibilidad.innerHTML =
        '<div class="mb-4"><div class="text-muted fs-8 text-uppercase fw-bold mb-2">Bloques permitidos</div><div class="cms-front-chips">' + chips(componente.bloques_permitidos || [], "badge-light-success") + '</div></div>' +
        '<div class="mb-4"><div class="text-muted fs-8 text-uppercase fw-bold mb-2">Variantes</div><div class="cms-front-chips">' + chips(componente.variantes || [], "badge-light-info") + '</div></div>' +
        '<div><div class="text-muted fs-8 text-uppercase fw-bold mb-2">Slots compatibles</div><div class="cms-front-chips">' + chips(componente.slots_compatibles || [], "badge-light-secondary") + '</div></div>';
    }
    if (uso) {
      var usos = usosComponente(componente.codigo);
      uso.innerHTML = usos.length ? usos.map(function (item) {
        return '<div class="border rounded p-3 mb-3">' +
          '<div class="fw-semibold">' + escapeHtml(item.plantilla) + '</div>' +
          '<div class="text-muted fs-8">' + escapeHtml(item.pagina) + ' / <code>' + escapeHtml(item.slot) + '</code></div>' +
          '<div class="mt-2"><span class="badge badge-light-info">' + escapeHtml(item.variante) + '</span> <span class="badge badge-light-secondary">orden ' + escapeHtml(item.orden) + '</span></div>' +
        '</div>';
      }).join("") : '<div class="text-muted fs-7">Este componente aun no aparece en una plantilla declarada.</div>';
    }
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

  function renderActivaciones(activaciones) {
    var node = $("cms_frontend_activaciones");
    if (!node) return;
    if (!activaciones.length) {
      node.innerHTML = '<div class="text-muted">Sin activaciones declaradas.</div>';
      return;
    }
    node.innerHTML = '<div class="cms-front-activation-grid">' + activaciones.map(function (item) {
      var plantilla = plantillaPorCodigo(item.plantilla_vista);
      return '<div class="cms-front-activation-card">' +
        '<div class="d-flex justify-content-between align-items-start gap-3 mb-4">' +
          '<div><div class="fw-bold fs-5">' + escapeHtml(item.pagina || "") + '</div><div class="text-muted fs-8">' + escapeHtml(item.canal || "") + ' / ' + escapeHtml(item.contexto_clave || "*") + '</div></div>' +
          '<span class="badge badge-light-success">' + escapeHtml(item.estatus || "readonly") + '</span>' +
        '</div>' +
        fila("Tema", item.tema || "") +
        fila("Plantilla", item.plantilla_vista || "") +
        fila("Layout", (plantilla || {}).layout || "") +
        fila("Vigencia", item.vigencia || "") +
        '<div class="mt-4"><div class="text-muted fs-8 text-uppercase fw-bold mb-2">Endpoint publico</div><code class="fs-8">' + escapeHtml(item.endpoint_publico || "") + '</code></div>' +
        '<div class="mt-4"><div class="text-muted fs-8 text-uppercase fw-bold mb-2">Secciones</div><div class="cms-front-chips">' + chips(((plantilla || {}).secciones || []).map(function (seccion) { return seccion.slot; }), "badge-light-info") + '</div></div>' +
      '</div>';
    }).join("") + '</div>';
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

  function plantillaPorCodigo(codigo) {
    var plantillas = estado.manifest.plantillas_vista || [];
    for (var i = 0; i < plantillas.length; i++) {
      if (plantillas[i].codigo === codigo) return plantillas[i];
    }
    return null;
  }

  function contenidoActual(plantilla) {
    plantilla = plantilla || plantillaActual();
    var pagina = plantilla && plantilla.pagina ? plantilla.pagina : "home";
    return estado.contenidos[pagina] || {};
  }

  function bloquesDeSeccion(seccion) {
    var contenido = contenidoActual();
    var slots = contenido.slots || [];
    for (var i = 0; i < slots.length; i++) {
      if (slots[i].slot === seccion.slot || slots[i].codigo === seccion.slot) {
        return slots[i].bloques || [];
      }
    }
    return [];
  }

  function contarBloquesContenido(contenido) {
    var total = 0;
    (contenido.slots || []).forEach(function (slot) {
      total += (slot.bloques || []).length;
    });
    return total;
  }

  function renderCta(bloque) {
    var cta = bloque.cta || {};
    if (!cta.label && !bloque.cta_label) return "";
    return '<div class="mt-4"><span class="badge badge-light text-dark">' + escapeHtml(cta.label || bloque.cta_label || "CTA") + '</span></div>';
  }

  function renderFuenteContenido(bloques) {
    return '<div class="mt-3"><span class="badge badge-light-secondary">' + (bloques.length ? 'Contenido conectado' : 'Sin bloque en slot') + '</span></div>';
  }

  function extraerItemsGrid(bloque) {
    var items = bloque.items || [];
    if (!items.length && bloque.payload && bloque.payload.items) {
      items = bloque.payload.items;
    }
    if (!items.length) {
      items = [
        { titulo: "Card 1", url: "" },
        { titulo: "Card 2", url: "" },
        { titulo: "Card 3", url: "" },
        { titulo: "Card 4", url: "" }
      ];
    }
    return items;
  }

  function usosComponente(codigo) {
    var usos = [];
    (estado.manifest.plantillas_vista || []).forEach(function (plantilla) {
      (plantilla.secciones || []).forEach(function (seccion) {
        if (seccion.componente === codigo) {
          usos.push({
            plantilla: plantilla.codigo || "",
            pagina: plantilla.pagina || "",
            slot: seccion.slot || "",
            variante: seccion.variante || "",
            orden: seccion.orden || ""
          });
        }
      });
    });
    return usos;
  }
})();
