/*
 * Documentacion IA: Codex GPT-5, 2026-08-10.
 * Proposito: render read-only de plantillas frontend administrables desde CMS.
 * Impacto: muestra layouts, componentes y contrato renderer sin escribir BD ni tocar archivos del frontend.
 * Contrato: consume GET interno protegido `/cms/frontend_admin_manifest_erp`.
 */
(function () {
  "use strict";

  var STORAGE_KEY = "erp_ecommerce_cms_preview_local_v1";

  var estado = {
    manifest: {},
    cmsEstado: {},
    contenidos: {},
    borradorLocal: null,
    usarBorradorLocal: true,
    paginaActiva: "home",
    plantillaActiva: "",
    seccionActiva: 0,
    contadorLocal: 1,
    componenteActivo: ""
  };

  document.addEventListener("DOMContentLoaded", function () {
    bindEvents();
    cargarManifest();
  });

  function bindEvents() {
    on("cms_frontend_cargar_borrador", "click", function () {
      estado.usarBorradorLocal = true;
      cargarManifest();
    });
    on("cms_frontend_ignorar_borrador", "click", function () {
      estado.usarBorradorLocal = false;
      estado.borradorLocal = null;
      cargarManifest();
    });
    on("cms_frontend_agregar_modulo", "click", function () {
      enfocarPaletaModulos();
    });
    on("cms_frontend_preview_full", "click", function () {
      abrirPreviewCompleto();
    });
    on("cms_frontend_preview_cerrar", "click", function () {
      cerrarPreviewCompleto();
    });
  }

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
      estado.plantillaActiva = plantillaCodigoPorPagina(data, estado.paginaActiva) || data.plantilla_activa_home || ((data.plantillas_vista || [])[0] || {}).codigo || "";
      estado.seccionActiva = 0;
      estado.componenteActivo = ((data.componentes || [])[0] || {}).codigo || "";
      return cargarContenidoPlantillas(data).then(function () {
        aplicarBorradorLocal(data);
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
      actualizarEstadoContenido();
      setText("cms_frontend_estado", "Read-only");
    }).catch(function (error) {
      setText("cms_frontend_estado", "Error");
      setText("cms_frontend_json", JSON.stringify({ error: true, mensaje: error.message || String(error) }, null, 2));
    });
  }

  function aplicarBorradorLocal(data) {
    estado.borradorLocal = null;
    if (!estado.usarBorradorLocal) {
      setText("cms_frontend_contenido_estado", "API read-only");
      return;
    }
    var borrador = leerBorradorLocal();
    if (!borrador || !Array.isArray(borrador.slots)) {
      setText("cms_frontend_contenido_estado", "Sin borrador local");
      return;
    }
    var pagina = borrador.pagina || "home";
    var paginaExiste = (data.plantillas_vista || []).some(function (plantilla) {
      return plantilla.pagina === pagina;
    });
    if (!paginaExiste) {
      setText("cms_frontend_contenido_estado", "Borrador no coincide");
      return;
    }
    estado.borradorLocal = borrador;
    estado.contenidos[pagina] = borrador;
    setText("cms_frontend_contenido_estado", "Borrador local conectado");
  }

  function leerBorradorLocal() {
    try {
      if (!window.localStorage) return null;
      var raw = localStorage.getItem(STORAGE_KEY);
      if (!raw) return null;
      var data = JSON.parse(raw);
      var preview = data.preview && Array.isArray(data.preview.slots) ? data.preview : null;
      var pagina = preview || data.pagina || {};
      if (Array.isArray(data.slots)) {
        pagina.slots = data.slots;
      }
      pagina.fuente = "preview_local_panel";
      pagina.origen_constructor = "localStorage";
      pagina.guardado_en = data.guardado_en || "";
      return pagina;
    } catch (error) {
      return null;
    }
  }

  function actualizarEstadoContenido() {
    if (estado.borradorLocal) {
      setText("cms_frontend_contenido_estado", "Borrador local conectado");
      return;
    }
    setText("cms_frontend_contenido_estado", estado.usarBorradorLocal ? "Contenido conectado" : "API read-only");
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
    renderPaginasEcommerce(data);
    renderBuilderPlantillas(data.plantillas_vista || []);
    renderBuilderCanvas();
    renderPaleta(data.componentes || []);
  }

  function paginasEcommerce(data) {
    var declaradas = {};
    (data.plantillas_vista || []).forEach(function (plantilla) {
      if (plantilla.pagina) declaradas[plantilla.pagina] = plantilla.codigo || "";
    });
    return [
      { codigo: "home", nombre: "Home", descripcion: "Portada, carrusel, promos, categorias y productos.", estado: declaradas.home ? "editable" : "pendiente" },
      { codigo: "categoria", nombre: "Categoria", descripcion: "Banner y productos filtrados por categoria.", estado: declaradas.categoria ? "editable" : "pendiente" },
      { codigo: "producto", nombre: "Producto", descripcion: "Galeria, precio, descripcion, relacionados.", estado: declaradas.producto ? "editable" : "preparar" },
      { codigo: "carrito", nombre: "Carrito", descripcion: "Resumen, envio, cupones y CTA de compra.", estado: declaradas.carrito ? "editable" : "preparar" },
      { codigo: "header", nombre: "Header", descripcion: "Logo, menu, busqueda, cuenta y carrito.", estado: "global" },
      { codigo: "footer", nombre: "Footer", descripcion: "Contacto, politicas, redes, ayuda y newsletter.", estado: "global" }
    ];
  }

  function renderPaginasEcommerce(data) {
    var node = $("cms_frontend_paginas");
    if (!node) return;
    node.innerHTML = paginasEcommerce(data).map(function (pagina) {
      var activo = pagina.codigo === estado.paginaActiva;
      var clase = pagina.estado === "editable" ? "badge-light-success" : (pagina.estado === "global" ? "badge-light-info" : "badge-light-warning");
      return '<button type="button" class="cms-page-choice mb-3' + (activo ? ' is-active' : '') + '" data-front-page="' + escapeHtml(pagina.codigo) + '">' +
        '<div class="d-flex justify-content-between gap-2 align-items-start">' +
          '<div><div class="fw-bold">' + escapeHtml(pagina.nombre) + '</div><div class="text-muted fs-8">' + escapeHtml(pagina.descripcion) + '</div></div>' +
          '<span class="badge ' + clase + '">' + escapeHtml(pagina.estado) + '</span>' +
        '</div>' +
      '</button>';
    }).join("");
    Array.prototype.forEach.call(node.querySelectorAll("[data-front-page]"), function (button) {
      button.addEventListener("click", function () {
        estado.paginaActiva = button.getAttribute("data-front-page") || "home";
        estado.plantillaActiva = plantillaCodigoPorPagina(estado.manifest, estado.paginaActiva) || "";
        estado.seccionActiva = 0;
        renderBuilder(estado.manifest);
      });
    });
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
    var plantillasPagina = plantillas.filter(function (plantilla) { return plantilla.pagina === estado.paginaActiva; });
    if (!plantillasPagina.length) {
      node.innerHTML = '<div class="alert alert-light-warning fs-7 mb-0">Esta pagina aun no tiene plantilla visual. Queda en cola para configurarla en CMS Frontend.</div>';
      return;
    }
    node.innerHTML = plantillasPagina.map(function (plantilla) {
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
      setText("cms_frontend_builder_subtitulo", "Pagina pendiente de plantilla visual.");
      preview.innerHTML = renderPaginaPendiente();
      renderInspector(null, null);
      renderEditorRapido(null, null);
      renderMapaSecciones(null);
      renderEstadoHome(null);
      renderPaletaModulos(null);
      return;
    }
    setText("cms_frontend_builder_titulo", plantilla.nombre || plantilla.codigo || "Plantilla");
    var contenido = contenidoActual(plantilla);
    var bloquesTotal = contarBloquesContenido(contenido);
    normalizarSeccionActiva(plantilla);
    setText("cms_frontend_builder_subtitulo", (plantilla.codigo || "") + " / " + (plantilla.layout || "") + " / " + (plantilla.pagina || "") + " / " + bloquesTotal + " bloques / " + fuenteContenido(contenido));
    preview.innerHTML = '<div class="cms-front-page-frame">' +
      '<div class="cms-front-page-top">' +
        '<div class="cms-front-logo">ARTIANI</div>' +
        '<div class="cms-front-page-nav"><span>Inicio</span><span>Categorias</span><span>Ofertas</span><span>Marcas</span></div>' +
        '<div class="cms-front-page-tools"><span>Buscar</span><span class="badge badge-light-primary">Carrito</span></div>' +
      '</div>' +
      (plantilla.secciones || []).map(renderPreviewSeccion).join("") +
      '<div class="cms-front-preview-section"><div class="cms-front-section-body"><div class="text-muted fs-8 text-uppercase fw-bold">Footer</div><div class="fw-semibold">Servicios, ayuda, contacto y politicas</div></div></div>' +
    '</div>';
    Array.prototype.forEach.call(preview.querySelectorAll("[data-front-section]"), function (button) {
      button.addEventListener("click", function () {
        estado.seccionActiva = parseInt(button.getAttribute("data-front-section") || "0", 10);
        renderBuilderCanvas();
      });
    });
    renderInspector(plantilla, (plantilla.secciones || [])[estado.seccionActiva] || null);
    renderEditorRapido(plantilla, (plantilla.secciones || [])[estado.seccionActiva] || null);
    renderEstadoHome(plantilla);
    renderMapaSecciones(plantilla);
    renderPaletaModulos(plantilla);
  }

  function renderPaginaPendiente() {
    var meta = paginasEcommerce(estado.manifest).filter(function (pagina) { return pagina.codigo === estado.paginaActiva; })[0] || {};
    return '<div class="cms-front-page-frame">' +
      '<div class="cms-front-page-top"><div class="cms-front-logo">ARTIANI</div><div class="cms-front-page-nav"><span>Pagina pendiente</span></div><div class="cms-front-page-tools"><span>CMS</span></div></div>' +
      '<div class="cms-front-preview-section"><div class="cms-front-section-body py-10 text-center">' +
        '<h2 class="fw-bold mb-3">' + escapeHtml(meta.nombre || estado.paginaActiva) + '</h2>' +
        '<p class="text-muted mb-4">' + escapeHtml(meta.descripcion || "Esta pagina se preparara en el constructor.") + '</p>' +
        '<div class="alert alert-light-warning mb-0 text-start">Esta pagina aun necesita plantilla visual y secciones. El siguiente paso sera crear su estructura como datos CMS frontend.</div>' +
      '</div></div>' +
    '</div>';
  }

  function renderPreviewSeccion(seccion, index) {
    if (seccionOculta(seccion)) return "";
    var activa = index === estado.seccionActiva;
    var bloques = bloquesDeSeccion(seccion);
    var header = '<div class="cms-front-section-meta"><button type="button" class="cms-front-section-btn' + (activa ? ' is-active' : '') + '" data-front-section="' + index + '">' +
      '<div class="d-flex justify-content-between gap-3"><span><span class="fw-bold">' + escapeHtml(seccion.orden || index + 1) + '. ' + escapeHtml(seccion.slot || "") + '</span><span class="text-muted fs-8 d-block">' + escapeHtml(seccion.componente || "") + ' / ' + escapeHtml(seccion.variante || "") + '</span></span><span class="badge badge-light-info">' + escapeHtml(bloques.length) + ' bloques</span></div>' +
    '</button></div>';
    return '<div class="cms-front-preview-section' + (activa ? ' is-selected' : '') + '">' + header + '<div class="cms-front-section-body">' + renderComponentePreview(seccion) + '</div></div>';
  }

  function renderComponentePreview(seccion) {
    var componente = seccion.componente || "";
    var bloques = bloquesDeSeccion(seccion);
    var principal = bloques[0] || {};
    if (componente === "HeroSlider") {
      var bg = imagenBloque(principal);
      return '<div class="cms-front-hero-preview"' + (bg ? ' style="background-image:url(' + escapeAttr(bg) + ')"' : '') + '><div><div class="fs-7 text-uppercase fw-bold mb-2">' + escapeHtml(principal.tipo || "hero_banner") + '</div><h2 class="text-white fw-bold mb-2">' + escapeHtml(principal.titulo || "Banner principal administrable") + '</h2><div class="opacity-75">' + escapeHtml(principal.subtitulo || "Imagen desktop/mobile, titulo, subtitulo y CTA desde CMS Contenido.") + '</div>' + renderCta(principal) + '</div></div>' + renderFuenteContenido(bloques);
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
        var img = imagenItem(item);
        return '<div class="cms-front-card-preview">' +
          '<div class="cms-front-card-image"' + (img ? ' style="background-image:url(' + escapeAttr(img) + ')"' : '') + '></div>' +
          '<div class="text-muted fs-8">Card ' + (index + 1) + '</div><div class="fw-semibold">' + escapeHtml(item.titulo || item.label || "Imagen + CTA") + '</div><div class="text-muted fs-8">' + escapeHtml(item.url || "") + '</div></div>';
      }).join("") + '</div>';
    }
    if (componente === "ProductCarousel") {
      var colecciones = bloques.length ? bloques : [principal];
      return '<div class="mb-3 fw-semibold">' + escapeHtml(principal.titulo || "Coleccion de productos") + '</div><div class="cms-front-products-preview">' + [1, 2, 3, 4].map(function (item) {
        return '<div class="cms-front-product-preview"><div class="cms-front-product-image">SKU</div><div class="fw-semibold fs-8">Producto ERP</div><div class="text-muted fs-8">' + escapeHtml((colecciones[0].source || {}).endpoint || "Precio/API") + '</div></div>';
      }).join("") + '</div>';
    }
    return '<div class="border rounded p-4 bg-light"><div class="fw-semibold">' + escapeHtml(principal.titulo || "Bloque de contenido seguro") + '</div><div class="text-muted fs-8">' + escapeHtml(principal.contenido_html || "Renderizado por componente permitido.") + '</div></div>' + renderFuenteContenido(bloques);
  }

  function renderPaletaModulos(plantilla) {
    var node = $("cms_frontend_modulos");
    if (!node) return;
    if (!plantilla || plantilla.pagina !== "home") {
      node.innerHTML = '<div class="text-muted fs-7">El agregado de modulos se habilita primero para Home.</div>';
      return;
    }
    var modulos = modulosDisponiblesHome();
    node.innerHTML = '<div class="cms-module-picker">' + modulos.map(function (modulo) {
      return '<button type="button" class="cms-module-picker__btn" data-add-module="' + escapeAttr(modulo.codigo) + '">' +
        '<div class="fw-semibold">' + escapeHtml(modulo.nombre) + '</div>' +
        '<div class="text-muted fs-8">' + escapeHtml(modulo.descripcion) + '</div>' +
      '</button>';
    }).join("") + '</div>';
    Array.prototype.forEach.call(node.querySelectorAll("[data-add-module]"), function (button) {
      button.addEventListener("click", function () {
        agregarModuloHome(plantilla, button.getAttribute("data-add-module") || "");
      });
    });
  }

  function modulosDisponiblesHome() {
    return [
      { codigo: "hero", nombre: "Portada / carrusel", descripcion: "Banner grande con imagen, texto y boton.", componente: "HeroSlider", variante: "full_width", tipo: "hero_banner" },
      { codigo: "promo", nombre: "Franja promocional", descripcion: "Mensaje corto para oferta, envio o beneficio.", componente: "PromoStrip", variante: "triple", tipo: "promo_strip" },
      { codigo: "cards", nombre: "Cards con imagen", descripcion: "Grid de categorias, promos o enlaces visuales.", componente: "ImageCardGrid", variante: "four_cards", tipo: "image_card_grid" },
      { codigo: "productos", nombre: "Productos destacados", descripcion: "Carrusel conectado a catalogo/publicaciones.", componente: "ProductCarousel", variante: "carousel_4", tipo: "product_collection" },
      { codigo: "html", nombre: "Bloque de contenido", descripcion: "Texto seguro para una seccion editorial.", componente: "SafeHtmlBlock", variante: "content", tipo: "content_html_safe" }
    ];
  }

  function agregarModuloHome(plantilla, codigo) {
    var modulo = modulosDisponiblesHome().filter(function (item) { return item.codigo === codigo; })[0];
    if (!plantilla || !modulo) return;
    plantilla.secciones = Array.isArray(plantilla.secciones) ? plantilla.secciones : [];
    var id = "home.local." + modulo.codigo + "." + Date.now();
    var seccion = {
      orden: plantilla.secciones.length + 1,
      slot: id,
      componente: modulo.componente,
      variante: modulo.variante,
      _cms_local: true,
      _cms_modulo: modulo.codigo
    };
    plantilla.secciones.push(seccion);
    var contenido = contenidoActual(plantilla);
    contenido.slots = Array.isArray(contenido.slots) ? contenido.slots : [];
    contenido.slots.push({ slot: id, codigo: id, bloques: [crearBloqueModuloLocal(modulo, seccion)] });
    estado.seccionActiva = plantilla.secciones.length - 1;
    marcarPlantillaLocal(plantilla);
    guardarBorradorConstructorLocal(plantilla);
    renderBuilderCanvas();
    setEditorEstado("Modulo agregado a la maqueta", "success");
  }

  function crearBloqueModuloLocal(modulo, seccion) {
    var bloque = crearBloqueBaseParaSeccion(Object.assign({}, seccion, { componente: modulo.componente }));
    bloque.tipo = modulo.tipo;
    bloque.titulo = modulo.nombre;
    if (modulo.tipo === "promo_strip") {
      bloque.texto = "Nueva franja promocional";
      bloque.subtitulo = "Nueva franja promocional";
    }
    if (modulo.tipo === "image_card_grid") {
      bloque.items = [
        { titulo: "Card 1", url: "", imagen: "" },
        { titulo: "Card 2", url: "", imagen: "" },
        { titulo: "Card 3", url: "", imagen: "" },
        { titulo: "Card 4", url: "", imagen: "" }
      ];
    }
    if (modulo.tipo === "content_html_safe") {
      bloque.contenido_html = "Nuevo bloque de contenido.";
    }
    return bloque;
  }

  function enfocarPaletaModulos() {
    var node = $("cms_frontend_modulos");
    if (node && node.scrollIntoView) {
      node.scrollIntoView({ behavior: "smooth", block: "center" });
    }
  }

  function abrirPreviewCompleto() {
    var plantilla = plantillaActual();
    var modal = $("cms_frontend_preview_modal");
    var node = $("cms_frontend_preview_publica");
    if (!modal || !node || !plantilla) return;
    node.innerHTML = renderPaginaPreviewPublica(plantilla);
    modal.classList.remove("d-none");
  }

  function cerrarPreviewCompleto() {
    var modal = $("cms_frontend_preview_modal");
    if (modal) modal.classList.add("d-none");
  }

  function renderPaginaPreviewPublica(plantilla) {
    normalizarSeccionActiva(plantilla);
    return '<div class="cms-front-page-frame" style="border:0;box-shadow:none;border-radius:0;">' +
      '<div class="cms-front-page-top">' +
        '<div class="cms-front-logo">ARTIANI</div>' +
        '<div class="cms-front-page-nav"><span>Inicio</span><span>Categorias</span><span>Ofertas</span><span>Marcas</span></div>' +
        '<div class="cms-front-page-tools"><span>Buscar</span><span class="badge badge-light-primary">Carrito</span></div>' +
      '</div>' +
      (plantilla.secciones || []).filter(function (seccion) { return !seccionOculta(seccion); }).map(function (seccion) {
        return '<section class="cms-front-preview-section"><div class="cms-front-section-body">' + renderComponentePreview(seccion) + '</div></section>';
      }).join("") +
      '<section class="cms-front-preview-section"><div class="cms-front-section-body"><div class="fw-semibold">Servicios, ayuda, contacto y politicas</div></div></section>' +
    '</div>';
  }

  function renderInspector(plantilla, seccion) {
    var node = $("cms_frontend_inspector");
    if (!node) return;
    if (!plantilla || !seccion) {
      node.innerHTML = '<div class="text-muted fs-7">Selecciona una seccion para inspeccionarla.</div>';
      return;
    }
    var componente = componentePorCodigo(seccion.componente);
    var bloques = bloquesDeSeccion(seccion);
    node.innerHTML =
      fila("Plantilla", plantilla.codigo || "") +
      fila("Pagina", plantilla.pagina || "") +
      fila("Layout", plantilla.layout || "") +
      '<div class="separator my-4"></div>' +
      fila("Slot", seccion.slot || "") +
      fila("Componente", seccion.componente || "") +
      fila("Variante", seccion.variante || "") +
      fila("Orden", seccion.orden || "") +
      fila("Bloques conectados", bloques.length) +
      '<div class="mt-4"><div class="text-muted fs-8 text-uppercase fw-bold mb-2">Bloques permitidos</div><div class="cms-front-chips">' + chips((componente || {}).bloques_permitidos || [], "badge-light-success") + '</div></div>' +
      '<div class="mt-4"><a class="btn btn-sm btn-primary w-100" href="' + escapeAttr(urlEditarContenido(plantilla, seccion, bloques[0] || null)) + '"><i class="bi bi-pencil-square"></i> Editar esta seccion</a></div>' +
      '<div class="mt-4"><div class="text-muted fs-8 text-uppercase fw-bold mb-2">Que significa</div><div class="alert alert-light-info py-3 px-4 fs-7 mb-0">' + escapeHtml(textoHumanoSeccion(seccion)) + '</div></div>';
  }

  function renderEditorRapido(plantilla, seccion) {
    var node = $("cms_frontend_editor_rapido");
    if (!node) return;
    if (!plantilla || !seccion) {
      node.innerHTML = '<div class="text-muted fs-7">Selecciona una seccion de Home para editar su contenido basico.</div>';
      return;
    }
    if (plantilla.pagina !== "home" && plantilla.pagina !== "categoria") {
      node.innerHTML = '<div class="alert alert-light-warning fs-7 mb-0">Esta pagina aun no tiene editor rapido. Primero se preparara su plantilla visual.</div>';
      return;
    }
    var bloque = bloquePrincipalDeSeccion(seccion, true);
    node.innerHTML = '<div class="cms-quick-editor">' +
      '<div class="mb-3"><label class="form-label fs-8 fw-bold">Titulo</label><input class="form-control form-control-sm" id="cms_front_q_titulo" value="' + escapeAttr(bloque.titulo || bloque.texto || "") + '"></div>' +
      '<div class="mb-3"><label class="form-label fs-8 fw-bold">Subtitulo / texto</label><textarea class="form-control form-control-sm" id="cms_front_q_subtitulo" rows="2">' + escapeHtml(bloque.subtitulo || bloque.texto || "") + '</textarea></div>' +
      '<div class="row g-2 mb-3">' +
        '<div class="col-6"><label class="form-label fs-8 fw-bold">Boton</label><input class="form-control form-control-sm" id="cms_front_q_cta_label" value="' + escapeAttr((bloque.cta || {}).label || bloque.cta_label || "") + '"></div>' +
        '<div class="col-6"><label class="form-label fs-8 fw-bold">URL boton</label><input class="form-control form-control-sm" id="cms_front_q_cta_url" value="' + escapeAttr((bloque.cta || {}).url || bloque.cta_url || "") + '"></div>' +
      '</div>' +
      '<div class="mb-3"><label class="form-label fs-8 fw-bold">Imagen desktop</label><input class="form-control form-control-sm" id="cms_front_q_img_desktop" value="' + escapeAttr(valorBloqueMedia(bloque, "imagen_desktop") || bloque.imagen_desktop || "") + '"></div>' +
      '<div class="mb-3"><label class="form-label fs-8 fw-bold">Imagen mobile</label><input class="form-control form-control-sm" id="cms_front_q_img_mobile" value="' + escapeAttr(valorBloqueMedia(bloque, "imagen_mobile") || bloque.imagen_mobile || "") + '"></div>' +
      '<div class="mb-3"><label class="form-label fs-8 fw-bold">Alt text</label><input class="form-control form-control-sm" id="cms_front_q_alt" value="' + escapeAttr(valorBloqueMedia(bloque, "alt") || "") + '"></div>' +
      '<div class="row g-2 mb-3">' +
        '<div class="col-6"><label class="form-label fs-8 fw-bold">Desde</label><input type="date" class="form-control form-control-sm" id="cms_front_q_desde" value="' + escapeAttr((bloque.vigencia || {}).desde || "") + '"></div>' +
        '<div class="col-6"><label class="form-label fs-8 fw-bold">Hasta</label><input type="date" class="form-control form-control-sm" id="cms_front_q_hasta" value="' + escapeAttr((bloque.vigencia || {}).hasta || "") + '"></div>' +
      '</div>' +
      '<div class="mb-3"><label class="form-label fs-8 fw-bold">Estatus</label><select class="form-select form-select-sm" id="cms_front_q_estatus">' +
        optionEstatus("borrador", bloque.estatus) + optionEstatus("publicado", bloque.estatus) + optionEstatus("pausado", bloque.estatus) +
      '</select><div class="form-text">Guardar desde aqui conserva el bloque como borrador controlado; publicar se hara en el cierre de seccion.</div></div>' +
      '<div class="d-grid gap-2">' +
        '<button class="btn btn-sm btn-primary" type="button" id="cms_front_q_aplicar"><i class="bi bi-eye"></i> Aplicar a maqueta</button>' +
        '<button class="btn btn-sm btn-light-success" type="button" id="cms_front_q_guardar"><i class="bi bi-save"></i> Guardar borrador en CMS</button>' +
        '<button class="btn btn-sm btn-success" type="button" id="cms_front_q_publicar"><i class="bi bi-check2-circle"></i> Publicar seccion</button>' +
        '<button class="btn btn-sm btn-light-warning" type="button" id="cms_front_q_pausar"><i class="bi bi-pause-circle"></i> Pausar seccion</button>' +
        '<a class="btn btn-sm btn-light" href="' + escapeAttr(urlEditarContenido(plantilla, seccion, bloque)) + '"><i class="bi bi-sliders"></i> Abrir editor avanzado</a>' +
      '</div>' +
      '<div class="mt-3" id="cms_front_q_estado"></div>' +
    '</div>';
    on("cms_front_q_aplicar", "click", function () { aplicarEditorRapido(plantilla, seccion, false); });
    on("cms_front_q_guardar", "click", function () { aplicarEditorRapido(plantilla, seccion, true); });
    on("cms_front_q_publicar", "click", function () { publicarSeccionRapida(plantilla, seccion); });
    on("cms_front_q_pausar", "click", function () { pausarSeccionRapida(plantilla, seccion); });
  }

  function optionEstatus(valor, actual) {
    actual = normalizarEstatus(actual);
    return '<option value="' + escapeAttr(valor) + '"' + (valor === actual ? " selected" : "") + '>' + escapeHtml(valor) + '</option>';
  }

  function aplicarEditorRapido(plantilla, seccion, guardar) {
    var bloque = bloquePrincipalDeSeccion(seccion, true);
    bloque.titulo = valor("cms_front_q_titulo");
    bloque.subtitulo = valor("cms_front_q_subtitulo");
    if (bloque.tipo === "promo_strip") {
      bloque.texto = bloque.subtitulo || bloque.titulo;
    }
    bloque.cta = { label: valor("cms_front_q_cta_label"), url: valor("cms_front_q_cta_url") };
    bloque.media = {
      imagen_desktop: valor("cms_front_q_img_desktop"),
      imagen_mobile: valor("cms_front_q_img_mobile"),
      alt: valor("cms_front_q_alt")
    };
    bloque.vigencia = { desde: valor("cms_front_q_desde"), hasta: valor("cms_front_q_hasta") };
    bloque.estatus = normalizarEstatus(valor("cms_front_q_estatus"));
    guardarBorradorConstructorLocal(plantilla);
    renderBuilderCanvas();
    setEditorEstado(guardar ? "Guardando borrador" : "Maqueta actualizada", guardar ? "info" : "success");
    if (guardar) {
      guardarBloqueRapido(plantilla, seccion, bloque);
    }
  }

  function guardarBloqueRapido(plantilla, seccion, bloque) {
    var errores = validarBloqueRapido(bloque);
    if (errores.length) {
      setEditorEstado(errores[0], "danger");
      return;
    }
    var data = new URLSearchParams();
    data.set("_csrf", window.ERP_CSRF_TOKEN || "");
    data.set("id_bloque", idBloquePersistido(bloque));
    data.set("tipo_bloque", bloque.tipo || "");
    data.set("codigo", bloque.codigo || "");
    data.set("nombre_interno", bloque.nombre_interno || bloque.titulo || nombreHumanoSeccion(seccion));
    data.set("titulo", bloque.titulo || bloque.texto || "");
    data.set("estatus", normalizarEstatusGuardado(bloque.estatus));
    data.set("payload_json", JSON.stringify(bloque));

    fetch("/cms/contenido_bloque_guardar_erp", {
      method: "POST",
      credentials: "same-origin",
      headers: {
        "Accept": "application/json",
        "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
        "X-CSRF-Token": window.ERP_CSRF_TOKEN || ""
      },
      body: data.toString()
    }).then(function (response) {
      return response.json();
    }).then(function (response) {
      var depurar = response.depurar || {};
      if (response.error) {
        setEditorEstado(response.mensaje || "No se pudo guardar", "danger");
        return;
      }
      bloque.id_bloque = depurar.id_bloque || bloque.id_bloque;
      bloque.codigo = depurar.codigo || bloque.codigo;
      bloque.estatus = depurar.estatus || "borrador";
      return guardarPublicacionRapida(plantilla, seccion, bloque, null);
    }).catch(function () {
      setEditorEstado("Error al guardar borrador", "danger");
    });
  }

  function guardarPublicacionRapida(plantilla, seccion, bloque, estatusFinal) {
    var data = new URLSearchParams();
    data.set("_csrf", window.ERP_CSRF_TOKEN || "");
    data.set("id_publicacion_contenido", idPublicacionPersistida(bloque));
    data.set("id_bloque", idBloquePersistido(bloque));
    data.set("plantilla", "artiani_default");
    data.set("pagina", plantilla.pagina || "home");
    data.set("categoria", plantilla.pagina === "categoria" ? "peces" : "");
    data.set("slot", seccion.slot || "");
    data.set("orden", "1");
    data.set("estatus", normalizarEstatusGuardado(bloque.estatus));
    data.set("vigente_desde", (bloque.vigencia || {}).desde || "");
    data.set("vigente_hasta", (bloque.vigencia || {}).hasta || "");

    return fetch("/cms/contenido_publicacion_guardar_erp", {
      method: "POST",
      credentials: "same-origin",
      headers: {
        "Accept": "application/json",
        "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
        "X-CSRF-Token": window.ERP_CSRF_TOKEN || ""
      },
      body: data.toString()
    }).then(function (response) {
      return response.json();
    }).then(function (response) {
      var depurar = response.depurar || {};
      if (response.error) {
        setEditorEstado(response.mensaje || "Bloque guardado, slot pendiente", "warning");
        return;
      }
      bloque.id_publicacion_contenido = depurar.id_publicacion_contenido || bloque.id_publicacion_contenido;
      bloque.publicacion = Object.assign({}, bloque.publicacion || {}, {
        id_publicacion_contenido: bloque.id_publicacion_contenido,
        slot: depurar.slot || seccion.slot,
        pagina: depurar.pagina || plantilla.pagina,
        estatus: depurar.estatus || "borrador",
        publica_api: false
      });
      guardarBorradorConstructorLocal(plantilla);
      if (estatusFinal) {
        return cambiarEstatusPublicacionRapida(bloque, estatusFinal);
      }
      setEditorEstado("Borrador guardado y colocado en la seccion", "success");
      refrescarContenidoConstructor();
    });
  }

  function publicarSeccionRapida(plantilla, seccion) {
    var bloque = bloquePrincipalDeSeccion(seccion, true);
    aplicarEditorRapidoSinRender(bloque);
    var errores = validarBloqueRapido(bloque);
    if (errores.length) {
      setEditorEstado(errores[0], "danger");
      return;
    }
    setEditorEstado("Validando y publicando seccion", "info");
    guardarBloqueRapidoConEstatus(plantilla, seccion, bloque, "publicado");
  }

  function pausarSeccionRapida(plantilla, seccion) {
    var bloque = bloquePrincipalDeSeccion(seccion, true);
    aplicarEditorRapidoSinRender(bloque);
    if (!idPublicacionPersistida(bloque)) {
      setEditorEstado("Guarda el borrador antes de pausar la seccion.", "warning");
      return;
    }
    setEditorEstado("Pausando seccion", "info");
    cambiarEstatusPublicacionRapida(bloque, "pausado");
  }

  function guardarBloqueRapidoConEstatus(plantilla, seccion, bloque, estatusFinal) {
    var data = new URLSearchParams();
    data.set("_csrf", window.ERP_CSRF_TOKEN || "");
    data.set("id_bloque", idBloquePersistido(bloque));
    data.set("tipo_bloque", bloque.tipo || "");
    data.set("codigo", bloque.codigo || "");
    data.set("nombre_interno", bloque.nombre_interno || bloque.titulo || nombreHumanoSeccion(seccion));
    data.set("titulo", bloque.titulo || bloque.texto || "");
    data.set("estatus", normalizarEstatusGuardado(bloque.estatus));
    data.set("payload_json", JSON.stringify(bloque));

    fetch("/cms/contenido_bloque_guardar_erp", {
      method: "POST",
      credentials: "same-origin",
      headers: {
        "Accept": "application/json",
        "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
        "X-CSRF-Token": window.ERP_CSRF_TOKEN || ""
      },
      body: data.toString()
    }).then(function (response) {
      return response.json();
    }).then(function (response) {
      var depurar = response.depurar || {};
      if (response.error) {
        setEditorEstado(response.mensaje || "No se pudo guardar antes de publicar", "danger");
        return;
      }
      bloque.id_bloque = depurar.id_bloque || bloque.id_bloque;
      bloque.codigo = depurar.codigo || bloque.codigo;
      bloque.estatus = depurar.estatus || "borrador";
      return guardarPublicacionRapida(plantilla, seccion, bloque, estatusFinal);
    }).catch(function () {
      setEditorEstado("Error al preparar publicacion", "danger");
    });
  }

  function cambiarEstatusPublicacionRapida(bloque, estatus) {
    var idPublicacion = idPublicacionPersistida(bloque);
    if (!idPublicacion) {
      setEditorEstado("Guarda la seccion antes de cambiar estatus.", "warning");
      return Promise.resolve();
    }
    var data = new URLSearchParams();
    data.set("_csrf", window.ERP_CSRF_TOKEN || "");
    data.set("id_publicacion_contenido", idPublicacion);
    data.set("estatus", estatus);

    return fetch("/cms/contenido_publicacion_estatus_erp", {
      method: "POST",
      credentials: "same-origin",
      headers: {
        "Accept": "application/json",
        "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
        "X-CSRF-Token": window.ERP_CSRF_TOKEN || ""
      },
      body: data.toString()
    }).then(function (response) {
      return response.json();
    }).then(function (response) {
      var depurar = response.depurar || {};
      if (response.error) {
        var bloqueos = depurar.bloqueos_publicacion || [];
        setEditorEstado((response.mensaje || "No se pudo cambiar estatus") + (bloqueos.length ? ": " + bloqueos.join(" / ") : ""), estatus === "publicado" ? "danger" : "warning");
        return;
      }
      bloque.estatus = depurar.estatus || estatus;
      bloque.publicacion = Object.assign({}, bloque.publicacion || {}, {
        id_publicacion_contenido: depurar.id_publicacion_contenido || idPublicacion,
        estatus: depurar.estatus || estatus,
        publica_api: estatus === "publicado"
      });
      guardarBorradorConstructorLocal(plantillaActual());
      setEditorEstado(estatus === "publicado" ? "Seccion publicada en CMS" : "Seccion pausada", "success");
      refrescarContenidoConstructor();
    }).catch(function () {
      setEditorEstado("Error al cambiar estatus de seccion", "danger");
    });
  }

  function aplicarEditorRapidoSinRender(bloque) {
    bloque.titulo = valor("cms_front_q_titulo");
    bloque.subtitulo = valor("cms_front_q_subtitulo");
    if (bloque.tipo === "promo_strip") {
      bloque.texto = bloque.subtitulo || bloque.titulo;
    }
    bloque.cta = { label: valor("cms_front_q_cta_label"), url: valor("cms_front_q_cta_url") };
    bloque.media = {
      imagen_desktop: valor("cms_front_q_img_desktop"),
      imagen_mobile: valor("cms_front_q_img_mobile"),
      alt: valor("cms_front_q_alt")
    };
    bloque.vigencia = { desde: valor("cms_front_q_desde"), hasta: valor("cms_front_q_hasta") };
    bloque.estatus = normalizarEstatus(valor("cms_front_q_estatus"));
  }

  function refrescarContenidoConstructor() {
    cargarContenidoPlantillas(estado.manifest).then(function () {
      aplicarBorradorLocal(estado.manifest);
      renderBuilderCanvas();
      actualizarEstadoContenido();
    });
  }

  function renderMapaSecciones(plantilla) {
    var node = $("cms_frontend_mapa_secciones");
    if (!node) return;
    if (!plantilla || !(plantilla.secciones || []).length) {
      node.innerHTML = '<div class="text-muted fs-7">Esta pagina aun no tiene secciones administrables.</div>';
      return;
    }
    node.innerHTML = '<div class="alert alert-light-info py-2 px-3 fs-8 mb-3">Acciones locales de maqueta. Para persistir orden/estructura de plantillas se activara despues CMS Frontend.</div>' +
      '<div class="cms-section-map">' + (plantilla.secciones || []).map(function (seccion, index) {
      var bloques = bloquesDeSeccion(seccion);
      var activa = index === estado.seccionActiva;
      var oculta = seccionOculta(seccion);
      return '<div class="cms-section-map__row' + (activa ? ' is-active' : '') + (oculta ? ' is-hidden' : '') + '">' +
        '<span class="badge badge-light-primary">' + escapeHtml(index + 1) + '</span>' +
        '<div>' +
          '<button type="button" class="btn btn-link text-start p-0 fw-semibold" data-section-action="seleccionar" data-section-index="' + escapeAttr(index) + '">' + escapeHtml(nombreHumanoSeccion(seccion)) + '</button>' +
          '<div class="text-muted fs-8">' + escapeHtml(seccion.slot || "") + ' / ' + escapeHtml(seccion.componente || "") + '</div>' +
          '<div class="mt-2"><span class="badge ' + (bloques.length ? 'badge-light-success' : 'badge-light-warning') + '">' + escapeHtml(bloques.length) + ' bloques</span> ' + (oculta ? '<span class="badge badge-light-warning">oculta</span>' : '<span class="badge badge-light-info">visible</span>') + '</div>' +
          '<div class="cms-section-map__actions">' +
            '<button type="button" class="btn btn-light btn-sm" title="Subir" data-section-action="subir" data-section-index="' + escapeAttr(index) + '"><i class="bi bi-arrow-up"></i></button>' +
            '<button type="button" class="btn btn-light btn-sm" title="Bajar" data-section-action="bajar" data-section-index="' + escapeAttr(index) + '"><i class="bi bi-arrow-down"></i></button>' +
            '<button type="button" class="btn btn-light btn-sm" title="' + (oculta ? 'Mostrar' : 'Ocultar') + '" data-section-action="toggle" data-section-index="' + escapeAttr(index) + '"><i class="bi ' + (oculta ? 'bi-eye' : 'bi-eye-slash') + '"></i></button>' +
            '<button type="button" class="btn btn-light btn-sm" title="Duplicar" data-section-action="duplicar" data-section-index="' + escapeAttr(index) + '"><i class="bi bi-copy"></i></button>' +
          '</div>' +
        '</div>' +
      '</div>';
    }).join("") + '</div>';
    Array.prototype.forEach.call(node.querySelectorAll("[data-section-action]"), function (button) {
      button.addEventListener("click", function () {
        ejecutarAccionMapaSeccion(plantilla, button.getAttribute("data-section-action"), parseInt(button.getAttribute("data-section-index") || "0", 10));
      });
    });
  }

  function renderEstadoHome(plantilla) {
    var node = $("cms_frontend_estado_home");
    if (!node) return;
    if (!plantilla) {
      node.innerHTML = '<div class="text-muted fs-7">Sin plantilla visual seleccionada.</div>';
      return;
    }
    if (plantilla.pagina !== "home") {
      node.innerHTML = '<div class="alert alert-light-info mb-0 fs-7">El tablero de estado detallado esta enfocado primero en Home. Esta pagina queda en cola para su checklist especifico.</div>';
      return;
    }
    var resumen = estadoPaginaConstructor(plantilla);
    node.innerHTML =
      '<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">' +
        '<div><div class="fw-bold">Estado de Home</div><div class="text-muted fs-8">Checklist operativo antes de conectar la pagina al frontend real.</div></div>' +
        '<span class="badge ' + (resumen.errores ? 'badge-light-danger' : (resumen.borradores || resumen.pausadas ? 'badge-light-warning' : 'badge-light-success')) + '">' + escapeHtml(resumen.estado_general) + '</span>' +
      '</div>' +
      '<div class="cms-home-status__grid mb-4">' +
        estadoHomeCaja("Listas", resumen.listas, "Secciones con contenido publicable", "badge-light-success") +
        estadoHomeCaja("Borrador", resumen.borradores, "Requieren publicar", "badge-light-warning") +
        estadoHomeCaja("Publicadas", resumen.publicadas, "Marcadas como publicadas", "badge-light-success") +
        estadoHomeCaja("Errores", resumen.errores, "Bloquean publicacion", resumen.errores ? "badge-light-danger" : "badge-light-secondary") +
      '</div>' +
      '<div class="cms-home-status__sections">' + resumen.secciones.map(renderEstadoHomeSeccion).join("") + '</div>';
  }

  function estadoHomeCaja(label, valor, texto, clase) {
    return '<div class="cms-home-status__item">' +
      '<div class="d-flex justify-content-between align-items-start gap-2"><div class="text-muted fs-8 text-uppercase fw-bold">' + escapeHtml(label) + '</div><span class="badge ' + clase + '">' + escapeHtml(valor) + '</span></div>' +
      '<div class="cms-home-status__value mt-2">' + escapeHtml(valor) + '</div>' +
      '<div class="text-muted fs-8 mt-2">' + escapeHtml(texto) + '</div>' +
    '</div>';
  }

  function renderEstadoHomeSeccion(item) {
    var clase = item.errores.length ? "badge-light-danger" : (item.estatus === "publicado" ? "badge-light-success" : (item.oculta || item.estatus === "pausado" ? "badge-light-warning" : "badge-light-secondary"));
    return '<div class="cms-home-status__section">' +
      '<div class="d-flex justify-content-between align-items-start gap-2 mb-2">' +
        '<button type="button" class="btn btn-link p-0 text-start fw-semibold" data-status-section="' + escapeAttr(item.index) + '">' + escapeHtml(item.nombre) + '</button>' +
        '<span class="badge ' + clase + '">' + escapeHtml(item.etiqueta) + '</span>' +
      '</div>' +
      '<div class="text-muted fs-8 mb-2">' + escapeHtml(item.slot) + '</div>' +
      '<div class="cms-front-chips mb-2">' +
        '<span class="badge ' + (item.bloques ? 'badge-light-success' : 'badge-light-warning') + '">' + escapeHtml(item.bloques) + ' bloques</span>' +
        '<span class="badge badge-light-info">' + escapeHtml(item.vigencia) + '</span>' +
      '</div>' +
      (item.errores.length ? '<div class="text-danger fs-8">' + escapeHtml(item.errores.join(" / ")) + '</div>' : '<div class="text-muted fs-8">' + escapeHtml(item.siguiente) + '</div>') +
    '</div>';
  }

  function estadoPaginaConstructor(plantilla) {
    var salida = { listas: 0, borradores: 0, publicadas: 0, pausadas: 0, errores: 0, secciones: [], estado_general: "Lista" };
    (plantilla.secciones || []).forEach(function (seccion, index) {
      var bloques = bloquesDeSeccion(seccion);
      var bloque = bloques[0] || {};
      var errores = seccionOculta(seccion) ? [] : validarSeccionParaEstado(seccion, bloque, bloques.length);
      var estatus = estatusBloquePublicacion(bloque);
      var item = {
        index: index,
        nombre: nombreHumanoSeccion(seccion),
        slot: seccion.slot || "",
        bloques: bloques.length,
        estatus: estatus,
        etiqueta: seccionOculta(seccion) ? "oculta" : etiquetaEstadoSeccion(estatus, errores, bloques.length),
        vigencia: textoVigencia(bloque),
        errores: errores,
        oculta: seccionOculta(seccion),
        siguiente: siguientePasoSeccion(estatus, bloques.length, seccionOculta(seccion))
      };
      if (!item.oculta && !errores.length && bloques.length) salida.listas++;
      if (estatus === "publicado") salida.publicadas++;
      if (estatus === "pausado") salida.pausadas++;
      if (estatus === "borrador" && bloques.length) salida.borradores++;
      salida.errores += errores.length;
      salida.secciones.push(item);
    });
    if (salida.errores) salida.estado_general = "Con errores";
    else if (salida.borradores || salida.pausadas) salida.estado_general = "Requiere cierre";
    else salida.estado_general = "Lista para frontend";
    setTimeout(function () {
      var node = $("cms_frontend_estado_home");
      if (!node) return;
      Array.prototype.forEach.call(node.querySelectorAll("[data-status-section]"), function (button) {
        button.addEventListener("click", function () {
          estado.seccionActiva = parseInt(button.getAttribute("data-status-section") || "0", 10);
          renderBuilderCanvas();
        });
      });
    }, 0);
    return salida;
  }

  function validarSeccionParaEstado(seccion, bloque, totalBloques) {
    var errores = [];
    if (!totalBloques) {
      errores.push("sin contenido");
      return errores;
    }
    if (!String(bloque.titulo || bloque.texto || "").trim()) {
      errores.push("falta titulo/texto");
    }
    if ((bloque.tipo === "hero_banner" || bloque.tipo === "category_banner") && !valorBloqueMedia(bloque, "alt")) {
      errores.push("falta alt text");
    }
    if (bloque.tipo === "product_collection" && (!bloque.source || !bloque.source.endpoint)) {
      errores.push("falta coleccion");
    }
    if (bloque.vigencia && bloque.vigencia.desde && bloque.vigencia.hasta && bloque.vigencia.desde > bloque.vigencia.hasta) {
      errores.push("vigencia invalida");
    }
    return errores;
  }

  function estatusBloquePublicacion(bloque) {
    if (bloque && bloque.publicacion && bloque.publicacion.estatus) return normalizarEstatus(bloque.publicacion.estatus);
    return normalizarEstatus((bloque || {}).estatus);
  }

  function etiquetaEstadoSeccion(estatus, errores, totalBloques) {
    if (errores.length) return "revisar";
    if (!totalBloques) return "vacia";
    if (estatus === "publicado") return "publicada";
    if (estatus === "pausado") return "pausada";
    return "borrador";
  }

  function siguientePasoSeccion(estatus, totalBloques, oculta) {
    if (oculta) return "Seccion oculta en maqueta local.";
    if (!totalBloques) return "Agrega contenido para poder publicar.";
    if (estatus === "publicado") return "Lista; revisa como la consume el frontend.";
    if (estatus === "pausado") return "Puedes publicarla de nuevo cuando este lista.";
    return "Publica la seccion cuando termines de revisarla.";
  }

  function textoVigencia(bloque) {
    var vigencia = (bloque || {}).vigencia || {};
    if (!vigencia.desde && !vigencia.hasta) return "sin vigencia";
    return (vigencia.desde || "sin inicio") + " / " + (vigencia.hasta || "sin fin");
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

  function on(id, eventName, callback) {
    var node = $(id);
    if (node) node.addEventListener(eventName, callback);
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

  function escapeAttr(value) {
    return escapeHtml(value).replace(/"/g, "&quot;");
  }

  function plantillaActual() {
    var plantillas = estado.manifest.plantillas_vista || [];
    if (!estado.plantillaActiva) return null;
    for (var i = 0; i < plantillas.length; i++) {
      if (plantillas[i].codigo === estado.plantillaActiva) return plantillas[i];
    }
    return null;
  }

  function plantillaCodigoPorPagina(data, pagina) {
    var plantillas = (data && data.plantillas_vista) || [];
    for (var i = 0; i < plantillas.length; i++) {
      if (plantillas[i].pagina === pagina) return plantillas[i].codigo || "";
    }
    return "";
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

  function imagenBloque(bloque) {
    if (!bloque) return "";
    var media = bloque.media || {};
    return urlPreviewSeguro(media.imagen_desktop || media.imagen_mobile || bloque.imagen_desktop || bloque.imagen || "");
  }

  function imagenItem(item) {
    item = item || {};
    return urlPreviewSeguro(item.imagen || item.image || item.url_imagen || "");
  }

  function urlPreviewSeguro(url) {
    url = String(url || "").trim();
    if (!url || /^javascript:/i.test(url)) return "";
    return url.replace(/["'()\\]/g, "");
  }

  function renderFuenteContenido(bloques) {
    var contenido = contenidoActual();
    return '<div class="mt-3"><span class="badge badge-light-secondary">' + (bloques.length ? 'Contenido conectado' : 'Sin bloque en slot') + '</span> <span class="badge badge-light-info">' + escapeHtml(fuenteContenido(contenido)) + '</span></div>';
  }

  function fuenteContenido(contenido) {
    var fuente = contenido && contenido.fuente ? contenido.fuente : "";
    if (fuente === "preview_local_panel") return "borrador local";
    if (fuente === "default_readonly") return "API default";
    return fuente || "contenido read-only";
  }

  function urlEditarContenido(plantilla, seccion, bloque) {
    var params = new URLSearchParams();
    params.set("pagina", plantilla && plantilla.pagina ? plantilla.pagina : "home");
    params.set("slot", seccion && seccion.slot ? seccion.slot : "");
    params.set("plantilla", "artiani_default");
    if (plantilla && plantilla.pagina === "categoria") {
      params.set("categoria", "peces");
    }
    if (bloque && bloque.id) {
      params.set("bloque", bloque.id);
    }
    return "/cms/contenido?" + params.toString();
  }

  function nombreHumanoSeccion(seccion) {
    var slot = seccion && seccion.slot ? seccion.slot : "";
    var nombres = {
      "home.hero": "Portada / carrusel principal",
      "home.promo": "Franja promocional",
      "home.categorias": "Categorias destacadas",
      "home.destacados": "Productos destacados",
      "categoria.banner": "Banner de categoria",
      "categoria.productos": "Productos de categoria",
      "catalogo.encabezado": "Encabezado de catalogo"
    };
    return nombres[slot] || (seccion && seccion.componente ? seccion.componente : slot || "Seccion");
  }

  function textoHumanoSeccion(seccion) {
    var slot = seccion && seccion.slot ? seccion.slot : "";
    var textos = {
      "home.hero": "Aqui administras la primera impresion de la tienda: imagen principal, titulo, subtitulo y boton.",
      "home.promo": "Aqui van mensajes cortos como envios, ofertas, WhatsApp o beneficios.",
      "home.categorias": "Aqui se muestran cards para llevar al cliente a categorias importantes.",
      "home.destacados": "Aqui se conecta una coleccion de productos publicados desde el ERP.",
      "categoria.banner": "Aqui se personaliza el encabezado visual de una categoria.",
      "categoria.productos": "Aqui se conecta el listado de productos filtrado por categoria.",
      "catalogo.encabezado": "Aqui se define el texto introductorio del catalogo."
    };
    return textos[slot] || "Esta seccion usa un componente visual permitido. El contenido se edita desde CMS > Editor avanzado.";
  }

  function ejecutarAccionMapaSeccion(plantilla, accion, index) {
    var secciones = plantilla && Array.isArray(plantilla.secciones) ? plantilla.secciones : [];
    if (!secciones[index]) return;
    if (accion === "seleccionar") {
      estado.seccionActiva = index;
      renderBuilderCanvas();
      return;
    }
    if (accion === "subir" && index > 0) {
      intercambiarSecciones(secciones, index, index - 1);
      estado.seccionActiva = index - 1;
      marcarPlantillaLocal(plantilla);
      renderBuilderCanvas();
      return;
    }
    if (accion === "bajar" && index < secciones.length - 1) {
      intercambiarSecciones(secciones, index, index + 1);
      estado.seccionActiva = index + 1;
      marcarPlantillaLocal(plantilla);
      renderBuilderCanvas();
      return;
    }
    if (accion === "toggle") {
      secciones[index]._cms_oculta = !seccionOculta(secciones[index]);
      estado.seccionActiva = index;
      marcarPlantillaLocal(plantilla);
      renderBuilderCanvas();
      return;
    }
    if (accion === "duplicar") {
      var copia = clonarSeccionParaMaqueta(secciones[index], secciones.length + 1);
      secciones.splice(index + 1, 0, copia);
      estado.seccionActiva = index + 1;
      marcarPlantillaLocal(plantilla);
      renderBuilderCanvas();
    }
  }

  function intercambiarSecciones(secciones, origen, destino) {
    var tmp = secciones[origen];
    secciones[origen] = secciones[destino];
    secciones[destino] = tmp;
    renumerarSecciones(secciones);
  }

  function renumerarSecciones(secciones) {
    (secciones || []).forEach(function (seccion, index) {
      seccion.orden = index + 1;
    });
  }

  function clonarSeccionParaMaqueta(seccion, orden) {
    var copia = JSON.parse(JSON.stringify(seccion || {}));
    copia.orden = orden;
    copia._cms_duplicada = true;
    copia._cms_local = true;
    copia._cms_oculta = false;
    return copia;
  }

  function marcarPlantillaLocal(plantilla) {
    if (!plantilla) return;
    plantilla._cms_layout_local = true;
    renumerarSecciones(plantilla.secciones || []);
    guardarLayoutConstructorLocal(plantilla);
  }

  function guardarLayoutConstructorLocal(plantilla) {
    try {
      if (!window.localStorage || !plantilla) return;
      localStorage.setItem("erp_ecommerce_cms_layout_local_v1_" + (plantilla.codigo || estado.paginaActiva), JSON.stringify({
        plantilla: plantilla.codigo || "",
        pagina: plantilla.pagina || "",
        secciones: plantilla.secciones || [],
        guardado_en: new Date().toISOString(),
        alcance: "maqueta_local_no_persistida"
      }));
    } catch (error) {
      setEditorEstado("No se pudo guardar el orden local", "warning");
    }
  }

  function cargarLayoutConstructorLocal(plantilla) {
    try {
      if (!window.localStorage || !plantilla || plantilla._cms_layout_local) return;
      var raw = localStorage.getItem("erp_ecommerce_cms_layout_local_v1_" + (plantilla.codigo || estado.paginaActiva));
      if (!raw) return;
      var data = JSON.parse(raw);
      if (Array.isArray(data.secciones) && data.secciones.length) {
        plantilla.secciones = data.secciones;
        plantilla._cms_layout_local = true;
      }
    } catch (error) {
      return;
    }
  }

  function seccionOculta(seccion) {
    return !!(seccion && seccion._cms_oculta);
  }

  function normalizarSeccionActiva(plantilla) {
    cargarLayoutConstructorLocal(plantilla);
    var secciones = plantilla && Array.isArray(plantilla.secciones) ? plantilla.secciones : [];
    if (!secciones.length) {
      estado.seccionActiva = 0;
      return;
    }
    if (!secciones[estado.seccionActiva]) {
      estado.seccionActiva = 0;
    }
    if (seccionOculta(secciones[estado.seccionActiva])) {
      for (var i = 0; i < secciones.length; i++) {
        if (!seccionOculta(secciones[i])) {
          estado.seccionActiva = i;
          return;
        }
      }
    }
  }

  function bloquePrincipalDeSeccion(seccion, crearSiFalta) {
    var contenido = contenidoActual();
    contenido.slots = Array.isArray(contenido.slots) ? contenido.slots : [];
    var slot = null;
    for (var i = 0; i < contenido.slots.length; i++) {
      if (contenido.slots[i].slot === seccion.slot || contenido.slots[i].codigo === seccion.slot) {
        slot = contenido.slots[i];
        break;
      }
    }
    if (!slot && crearSiFalta) {
      slot = { slot: seccion.slot, codigo: seccion.slot, bloques: [] };
      contenido.slots.push(slot);
    }
    if (!slot) return {};
    slot.bloques = Array.isArray(slot.bloques) ? slot.bloques : [];
    if (!slot.bloques.length && crearSiFalta) {
      slot.bloques.push(crearBloqueBaseParaSeccion(seccion));
    }
    return slot.bloques[0] || {};
  }

  function crearBloqueBaseParaSeccion(seccion) {
    var componente = componentePorCodigo(seccion.componente) || {};
    var tipo = (componente.bloques_permitidos || [])[0] || tipoPorComponente(seccion.componente);
    return {
      id: "front-local-" + Date.now() + "-" + estado.contadorLocal++,
      tipo: tipo,
      estatus: "borrador",
      titulo: nombreHumanoSeccion(seccion),
      subtitulo: "",
      texto: tipo === "promo_strip" ? nombreHumanoSeccion(seccion) : "",
      media: { imagen_desktop: "", imagen_mobile: "", alt: "" },
      cta: { label: "", url: "" },
      source: tipo === "product_collection" ? { tipo: "catalogo_dinamico", endpoint: "/ecommercePublico/catalogo?limite=8", metodo: "GET" } : undefined,
      limite: tipo === "product_collection" ? 8 : undefined,
      vigencia: { desde: "", hasta: "" },
      guardrails: { creado_desde_constructor: true, no_modifica_catalogo: true, no_modifica_inventario: true }
    };
  }

  function tipoPorComponente(componente) {
    var mapa = {
      HeroSlider: "hero_banner",
      PromoStrip: "promo_strip",
      CategoryGrid: "image_card_grid",
      ImageCardGrid: "image_card_grid",
      ProductCarousel: "product_collection",
      SafeHtmlBlock: "content_html_safe"
    };
    return mapa[componente] || "promo_strip";
  }

  function guardarBorradorConstructorLocal(plantilla) {
    var contenido = contenidoActual(plantilla);
    try {
      if (!window.localStorage || !contenido) return;
      localStorage.setItem(STORAGE_KEY, JSON.stringify({
        pagina: contenido.pagina || (plantilla || {}).pagina || estado.paginaActiva,
        slots: contenido.slots || [],
        preview: Object.assign({}, contenido, {
          fuente: "preview_local_panel",
          editable_desde_constructor: true,
          slots: contenido.slots || []
        }),
        guardado_en: new Date().toISOString(),
        origen: "cms_frontend_constructor"
      }));
      estado.borradorLocal = contenido;
    } catch (error) {
      setEditorEstado("No se pudo guardar borrador local", "warning");
    }
  }

  function validarBloqueRapido(bloque) {
    var errores = [];
    if (!String(bloque.titulo || bloque.texto || "").trim()) {
      errores.push("Captura titulo o texto principal.");
    }
    if ((bloque.tipo === "hero_banner" || bloque.tipo === "category_banner") && !valorBloqueMedia(bloque, "alt")) {
      errores.push("Captura alt text de la imagen.");
    }
    if (bloque.vigencia && bloque.vigencia.desde && bloque.vigencia.hasta && bloque.vigencia.desde > bloque.vigencia.hasta) {
      errores.push("La fecha hasta no puede ser menor que desde.");
    }
    if (bloque.tipo === "content_html_safe" && /<script[\s>]/i.test(String(bloque.contenido_html || ""))) {
      errores.push("El contenido HTML contiene script.");
    }
    return errores;
  }

  function normalizarEstatus(estatus) {
    estatus = String(estatus || "borrador").replace("_default", "");
    return ["borrador", "publicado", "pausado"].indexOf(estatus) >= 0 ? estatus : "borrador";
  }

  function normalizarEstatusGuardado(estatus) {
    estatus = normalizarEstatus(estatus);
    return estatus === "publicado" ? "borrador" : estatus;
  }

  function idBloquePersistido(bloque) {
    if (bloque && bloque.id_bloque) return String(bloque.id_bloque);
    var id = bloque && bloque.id ? String(bloque.id) : "";
    return id.indexOf("bd-") === 0 ? id.replace("bd-", "") : "";
  }

  function idPublicacionPersistida(bloque) {
    if (bloque && bloque.id_publicacion_contenido) return String(bloque.id_publicacion_contenido);
    if (bloque && bloque.publicacion && bloque.publicacion.id_publicacion_contenido) return String(bloque.publicacion.id_publicacion_contenido);
    return "";
  }

  function valorBloqueMedia(bloque, key) {
    if (!bloque || !bloque.media) return "";
    return bloque.media[key] || "";
  }

  function valor(id) {
    var node = $(id);
    return node ? String(node.value || "").trim() : "";
  }

  function setEditorEstado(texto, tipo) {
    var node = $("cms_front_q_estado");
    if (!node) return;
    var clase = tipo === "danger" ? "alert-light-danger" : (tipo === "warning" ? "alert-light-warning" : (tipo === "info" ? "alert-light-info" : "alert-light-success"));
    node.innerHTML = '<div class="alert ' + clase + ' py-2 px-3 mb-0 fs-8">' + escapeHtml(texto) + '</div>';
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
