/*
 * Documentacion IA: Codex GPT-5, 2026-08-13.
 * Proposito: presentar contrato CMS alineado al frontend ecommerce actual.
 * Impacto: CMS; guia implementacion incremental sin builder generico.
 * Contrato: no escribe BD, no edita archivos frontend, genera preview JSON local.
 */
(function () {
  "use strict";

  var MEDIA_STORAGE_KEY = "erp_cms_media_biblioteca_local_v1";
  var MEDIA_MAX_BYTES = 2 * 1024 * 1024;
  var MEDIA_MIMES = ["image/jpeg", "image/png", "image/webp"];

  var estado = {
    grupo: "global",
    mediaPicker: { contexto: "", index: 0, campo: "", archivo: null, dataUrl: "" },
    datos: {
      home: {
        home_hero_carrusel: {
          codigo: "home_hero_carrusel",
          tipo: "hero_carrusel",
          visible: true,
          orden: 10,
          config: {
            autoplay: true,
            intervalo_ms: 5500,
            mostrar_flechas: true,
            mostrar_puntos: true,
            estilo: "wokiee_full_width"
          },
          items: [
            {
              titulo: "Acuario, alimento y accesorios",
              subtitulo: "Productos para peces, reptiles, aves, perros y gatos.",
              eyebrow: "Artiani",
              imagen_desktop: "",
              imagen_mobile: "",
              alt: "Productos Artiani para acuario y mascotas",
              cta: { label: "Comprar ahora", url: "/#productos" },
              cta_secundario: { label: "Buscar producto", url: "/buscar" },
              visible: true,
              orden: 10
            }
          ]
        },
        home_categorias_destacadas: {
          codigo: "home_categorias_destacadas",
          tipo: "categorias_destacadas",
          visible: true,
          orden: 30,
          titulo: "Categorias destacadas",
          subtitulo: "Encuentra rapido alimento, habitat, accesorios y cuidado por mascota.",
          config: {
            columnas_desktop: 4,
            columnas_mobile: 2,
            variante: "wokiee_category_cards"
          },
          items: [
            {
              categoria_id: 0,
              slug: "peces",
              titulo: "Peces",
              subtitulo: "Acuarios, alimento y mantenimiento",
              imagen_card: "",
              imagen_banner: "",
              alt: "Categoria de peces y articulos para acuario",
              url: "/categoria/peces",
              visible: true,
              orden: 10
            },
            {
              categoria_id: 0,
              slug: "perros",
              titulo: "Perros",
              subtitulo: "Alimento, accesorios y cuidado diario",
              imagen_card: "",
              imagen_banner: "",
              alt: "Categoria de perros con alimento y accesorios",
              url: "/categoria/perros",
              visible: true,
              orden: 20
            }
          ]
        },
        home_productos_destacados: {
          codigo: "home_productos_destacados",
          tipo: "productos_destacados",
          visible: true,
          orden: 40,
          titulo: "Productos destacados",
          subtitulo: "Seleccion editorial para mostrar primero en la portada.",
          fuente: {
            modo: "criterio",
            criterio: "destacados",
            categoria_slug: "",
            marca_slug: "",
            productos: [
              { producto_id: 0, sku: "", slug: "", titulo_override: "", orden: 10 }
            ]
          },
          limite: 12,
          cta: { label: "Ver catalogo", url: "/#productos" },
          config: {
            variante: "wokiee_product_carousel",
            mostrar_precio: true,
            mostrar_badges: true
          }
        },
        home_colecciones: {
          codigo: "home_colecciones",
          tipo: "coleccion_productos",
          visible: true,
          orden: 50,
          titulo: "Colecciones de productos",
          subtitulo: "Vitrinas editoriales para novedades, destacados o necesidades especificas.",
          config: {
            variante: "wokiee_collection_rows"
          },
          items: [
            {
              codigo: "coleccion_acuario",
              titulo: "Acuario destacado",
              subtitulo: "Productos utiles para iniciar o renovar tu acuario.",
              visible: true,
              orden: 10,
              fuente: {
                modo: "criterio",
                criterio: "destacados",
                categoria_slug: "peces",
                marca_slug: "",
                productos: []
              },
              limite: 8,
              cta: { label: "Ver coleccion", url: "/categoria/peces" },
              config: { variante: "wokiee_product_row" }
            }
          ]
        },
        home_banner: {
          codigo: "home_banner",
          tipo: "banner_simple",
          visible: true,
          orden: 60,
          titulo: "Banner de Home",
          subtitulo: "Banner visual de apoyo para la pagina principal.",
          config: {
            variante: "wokiee_banner_full_width",
            modo: "estatico"
          },
          items: [
            {
              titulo: "Encuentra lo que necesitas para tu mascota",
              subtitulo: "",
              imagen_desktop: "",
              imagen_mobile: "",
              alt: "Banner principal de apoyo en Home",
              cta: { label: "Ver productos", url: "/#productos" },
              visible: true,
              orden: 10
            }
          ]
        }
      }
    }
  };

  var grupos = [
    {
      codigo: "global",
      titulo: "Global",
      subtitulo: "Header, footer, WhatsApp y SEO defaults.",
      endpoint: "GET /ecommercePublico/cms_frontend?pagina=global",
      prioridad: 1,
      secciones: [
        seccion("global_header", "header", "Topbar y menu principal", ["topbar_texto", "menu_principal"]),
        seccion("global_footer", "footer", "Descripcion, links y visibilidad de marcas/categorias", ["descripcion", "links", "mostrar_marcas", "mostrar_categorias"]),
        seccion("global_whatsapp", "whatsapp", "Mensaje base y CTA del carrito", ["mensaje_base", "cta_carrito"]),
        seccion("global_seo", "seo_defaults", "SEO default del sitio", ["site_name", "title_default", "description_default"])
      ]
    },
    {
      codigo: "home",
      titulo: "Home",
      subtitulo: "Secciones principales de la portada publica.",
      endpoint: "GET /ecommercePublico/cms_frontend?pagina=home",
      prioridad: 2,
      secciones: [
        seccion("home_hero_carrusel", "hero_carrusel", "Banner principal con imagen desktop/mobile y slides.", ["items", "autoplay", "intervalo_ms", "cta"]),
        seccion("home_categorias_destacadas", "categorias_destacadas", "Categorias reales publicadas con imagen card/banner.", ["categoria_id", "slug", "imagen_card", "imagen_banner"]),
        seccion("home_productos_destacados", "productos_destacados", "Productos por criterio o lista manual.", ["fuente.modo", "fuente.criterio", "fuente.productos", "limite"]),
        seccion("home_colecciones", "coleccion_productos", "Colecciones repetibles: novedades, destacados, basicos.", ["titulo", "fuente", "cta"]),
        seccion("home_banner", "banner_simple", "Banner de Home estatico hoy, preparado para slides despues.", ["imagen_desktop", "imagen_mobile", "alt", "cta"])
      ]
    },
    {
      codigo: "categoria",
      titulo: "Categoria",
      subtitulo: "Contenido por categoria real.",
      endpoint: "GET /ecommercePublico/cms_frontend?pagina=categoria&slug={slug}",
      prioridad: 6,
      secciones: [
        seccion("categoria_hero", "banner_simple", "Hero por categoria.", ["slug", "imagen_banner", "descripcion_corta", "alt"]),
        seccion("categoria_card", "categorias_destacadas", "Imagen card y datos editoriales de categoria.", ["imagen_card", "subtitulo", "url"]),
        seccion("categoria_productos_destacados", "productos_destacados", "Destacados de la categoria.", ["fuente.categoria", "limite"])
      ]
    },
    {
      codigo: "producto",
      titulo: "Producto",
      subtitulo: "Galeria y bloques comerciales del producto.",
      endpoint: "GET /ecommercePublico/cms_frontend?pagina=producto&slug={slug}",
      prioridad: 7,
      secciones: [
        seccion("producto_galeria", "galeria_producto", "Imagenes y alt text.", ["imagenes", "alt"]),
        seccion("producto_badges", "badges_comerciales", "Nuevo, destacado, recomendado o promocion.", ["badges"]),
        seccion("producto_recomendados", "productos_destacados", "Relacionados, complementarios o cross-sell.", ["fuente.modo", "productos"])
      ]
    },
    {
      codigo: "carrito",
      titulo: "Carrito",
      subtitulo: "Textos para captar interesados por WhatsApp.",
      endpoint: "GET /ecommercePublico/cms_frontend?pagina=carrito",
      prioridad: 8,
      secciones: [
        seccion("carrito_textos", "carrito_whatsapp", "Titulo, pasos y CTA principal.", ["titulo", "pasos", "cta"]),
        seccion("carrito_estado_vacio", "estado_vacio", "Mensaje cuando el carrito esta vacio.", ["titulo", "mensaje", "imagen", "cta"])
      ]
    },
    {
      codigo: "estados",
      titulo: "Estados vacios",
      subtitulo: "Mensajes cuando no hay resultados o contenido.",
      endpoint: "GET /ecommercePublico/cms_frontend?pagina=global",
      prioridad: 9,
      secciones: [
        seccion("catalogo_sin_resultados", "estado_vacio", "Sin productos por filtros.", ["titulo", "mensaje", "cta"]),
        seccion("buscar_sin_resultados", "estado_vacio", "Sin resultados de busqueda.", ["busquedas_sugeridas", "categorias_sugeridas", "marcas_sugeridas"])
      ]
    }
  ];

  var reglas = [
    "No mostrar disponibilidad ni stock exacto.",
    "No mostrar ERP, preflight, dry-run, fases tecnicas ni guardrails.",
    "Todas las imagenes publicas deben tener alt text.",
    "Categorias y marcas reales son la navegacion principal.",
    "El frontend consume API publica, no archivos internos del ERP."
  ];

  document.addEventListener("DOMContentLoaded", function () {
    var grupoInicial = document.body ? document.body.getAttribute("data-cms-actual-grupo") : "";
    if (grupoInicial) estado.grupo = grupoInicial;
    renderTodo();
    on("cms_actual_copiar_json", "click", copiarJson);
  });

  function seccion(codigo, tipo, descripcion, campos) {
    return { codigo: codigo, tipo: tipo, descripcion: descripcion, campos: campos, visible: true };
  }

  function renderTodo() {
    renderPrioridad();
    renderNav();
    renderReglas();
    renderGrupo();
  }

  function renderPrioridad() {
    var node = $("cms_actual_prioridad");
    if (!node) return;
    node.innerHTML = grupos.slice(0, 5).map(function (grupo) {
      return '<div class="cms-actual-card"><div class="text-muted fs-8 text-uppercase fw-bold">Prioridad ' + escapeHtml(grupo.prioridad) + '</div><div class="fw-bold mt-2">' + escapeHtml(grupo.titulo) + '</div><div class="text-muted fs-8 mt-2">' + escapeHtml(grupo.subtitulo) + '</div></div>';
    }).join("");
  }

  function renderNav() {
    var node = $("cms_actual_nav");
    if (!node) return;
    node.innerHTML = grupos.map(function (grupo) {
      return '<button type="button" class="' + (grupo.codigo === estado.grupo ? 'is-active' : '') + '" data-grupo="' + escapeAttr(grupo.codigo) + '"><div class="fw-bold">' + escapeHtml(grupo.titulo) + '</div><div class="text-muted fs-8">' + escapeHtml(grupo.subtitulo) + '</div></button>';
    }).join("");
    Array.prototype.forEach.call(node.querySelectorAll("[data-grupo]"), function (button) {
      button.addEventListener("click", function () {
        estado.grupo = button.getAttribute("data-grupo") || "home";
        renderTodo();
      });
    });
  }

  function renderReglas() {
    var node = $("cms_actual_reglas");
    if (!node) return;
    node.innerHTML = reglas.map(function (regla) {
      return '<div class="d-flex gap-2 mb-3 fs-7"><i class="bi bi-check2-circle text-success"></i><span>' + escapeHtml(regla) + '</span></div>';
    }).join("");
  }

  function renderGrupo() {
    var grupo = grupoActual();
    setText("cms_actual_titulo", grupo.titulo);
    setText("cms_actual_subtitulo", grupo.subtitulo);
    setText("cms_actual_endpoint", grupo.endpoint);
    var node = $("cms_actual_secciones");
    if (node) {
      node.innerHTML = grupo.secciones.map(renderSeccion).join("");
      bindGrupoEditors();
    }
    setText("cms_actual_json", JSON.stringify(previewJson(grupo), null, 2));
  }

  function renderSeccion(item) {
    if (item.codigo === "home_hero_carrusel") {
      return renderHeroCarrusel(item);
    }
    if (item.codigo === "home_categorias_destacadas") {
      return renderCategoriasDestacadas(item);
    }
    if (item.codigo === "home_productos_destacados") {
      return renderProductosDestacados(item);
    }
    if (item.codigo === "home_colecciones") {
      return renderColeccionesProductos(item);
    }
    if (item.codigo === "home_banner") {
      return renderBannerHome(item);
    }
    return '<div class="cms-actual-card mb-4">' +
      '<div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-3">' +
        '<div><div class="fw-bold">' + escapeHtml(item.codigo) + '</div><div class="text-muted fs-8">' + escapeHtml(item.descripcion) + '</div></div>' +
        '<span class="badge badge-light-info">' + escapeHtml(item.tipo) + '</span>' +
      '</div>' +
      '<div class="cms-actual-fields">' + item.campos.map(function (campo) {
        return '<div><div class="text-muted fs-8 text-uppercase fw-bold">' + escapeHtml(campo) + '</div><div class="form-control form-control-sm bg-light">Pendiente de editor</div></div>';
      }).join("") + '</div>' +
    '</div>';
  }

  function renderHeroCarrusel(item) {
    var data = heroData();
    return '<div class="cms-actual-card mb-4">' +
      '<div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-4">' +
        '<div><div class="fw-bold">' + escapeHtml(item.codigo) + '</div><div class="text-muted fs-8">' + escapeHtml(item.descripcion) + '</div></div>' +
        '<span class="badge badge-light-success">Editor activo</span>' +
      '</div>' +
      '<div class="row g-3 mb-4">' +
        '<div class="col-md-3"><label class="form-label fs-8 fw-bold">Visible</label><select class="form-select form-select-sm" data-hero-config="visible"><option value="1"' + (data.visible ? ' selected' : '') + '>Si</option><option value="0"' + (!data.visible ? ' selected' : '') + '>No</option></select></div>' +
        '<div class="col-md-3"><label class="form-label fs-8 fw-bold">Autoplay</label><select class="form-select form-select-sm" data-hero-config="autoplay"><option value="1"' + (data.config.autoplay ? ' selected' : '') + '>Si</option><option value="0"' + (!data.config.autoplay ? ' selected' : '') + '>No</option></select></div>' +
        '<div class="col-md-3"><label class="form-label fs-8 fw-bold">Intervalo ms</label><input class="form-control form-control-sm" data-hero-config="intervalo_ms" value="' + escapeAttr(data.config.intervalo_ms) + '"></div>' +
        '<div class="col-md-3"><label class="form-label fs-8 fw-bold">Estilo</label><input class="form-control form-control-sm" data-hero-config="estilo" value="' + escapeAttr(data.config.estilo || "wokiee_full_width") + '"></div>' +
      '</div>' +
      '<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3"><div class="fw-bold">Slides</div><button class="btn btn-sm btn-light-primary" type="button" id="cms_actual_hero_agregar"><i class="bi bi-plus-circle"></i> Agregar slide</button></div>' +
      data.items.map(renderHeroSlide).join("") +
      '<div class="alert alert-light-info fs-7 mb-0">Recomendado: desktop 1920x820, mobile 768x980, imagen optimizada y alt obligatorio.</div>' +
    '</div>';
  }

  function renderHeroSlide(slide, index) {
    var bg = slide.imagen_desktop ? ' style="background-image:url(' + escapeAttr(urlPreviewSeguro(slide.imagen_desktop)) + ')"' : "";
    return '<div class="cms-actual-slide mb-4" data-hero-slide="' + escapeAttr(index) + '">' +
      '<div class="d-flex justify-content-between align-items-center gap-2 mb-3">' +
        '<div class="fw-semibold">Slide ' + escapeHtml(index + 1) + '</div>' +
        '<div class="d-flex gap-2">' +
          '<button class="btn btn-sm btn-light" type="button" data-hero-action="duplicar" data-index="' + escapeAttr(index) + '"><i class="bi bi-copy"></i></button>' +
          '<button class="btn btn-sm btn-light-warning" type="button" data-hero-action="toggle" data-index="' + escapeAttr(index) + '"><i class="bi ' + (slide.visible ? 'bi-eye-slash' : 'bi-eye') + '"></i></button>' +
          '<button class="btn btn-sm btn-light-danger" type="button" data-hero-action="eliminar" data-index="' + escapeAttr(index) + '"><i class="bi bi-trash"></i></button>' +
        '</div>' +
      '</div>' +
      '<div class="cms-actual-slide-preview mb-4"' + bg + '><div><div class="text-uppercase fs-8 fw-bold mb-2">' + escapeHtml(slide.eyebrow || "Artiani") + '</div><h2 class="text-white fw-bold mb-2">' + escapeHtml(slide.titulo || "Titulo del slide") + '</h2><div class="opacity-75">' + escapeHtml(slide.subtitulo || "") + '</div></div></div>' +
      '<div class="row g-3">' +
        inputSlide(index, "titulo", "Titulo", slide.titulo, "col-md-6") +
        inputSlide(index, "subtitulo", "Subtitulo", slide.subtitulo, "col-md-6") +
        inputSlide(index, "eyebrow", "Eyebrow", slide.eyebrow, "col-md-4") +
        inputSlide(index, "alt", "Alt obligatorio", slide.alt, "col-md-8") +
        inputSlide(index, "imagen_desktop", "Imagen desktop", slide.imagen_desktop, "col-md-6") +
        inputSlide(index, "imagen_mobile", "Imagen mobile", slide.imagen_mobile, "col-md-6") +
        inputSlide(index, "cta.label", "CTA texto", (slide.cta || {}).label, "col-md-3") +
        inputSlide(index, "cta.url", "CTA URL", (slide.cta || {}).url, "col-md-3") +
        inputSlide(index, "cta_secundario.label", "CTA secundario texto", (slide.cta_secundario || {}).label, "col-md-3") +
        inputSlide(index, "cta_secundario.url", "CTA secundario URL", (slide.cta_secundario || {}).url, "col-md-3") +
      '</div>' +
    '</div>';
  }

  function inputSlide(index, campo, label, value, col) {
    return inputConMedia("hero", index, campo, label, value, col, "data-hero-slide-field");
  }

  function bindGrupoEditors() {
    Array.prototype.forEach.call(document.querySelectorAll("[data-hero-config]"), function (node) {
      node.addEventListener("input", function () {
        actualizarHeroConfig(node.getAttribute("data-hero-config"), node.value);
      });
      node.addEventListener("change", function () {
        actualizarHeroConfig(node.getAttribute("data-hero-config"), node.value);
      });
    });
    Array.prototype.forEach.call(document.querySelectorAll("[data-hero-slide-field]"), function (node) {
      node.addEventListener("input", function () {
        actualizarHeroSlide(parseInt(node.getAttribute("data-index") || "0", 10), node.getAttribute("data-hero-slide-field"), node.value);
      });
    });
    Array.prototype.forEach.call(document.querySelectorAll("[data-hero-action]"), function (button) {
      button.addEventListener("click", function () {
        ejecutarHeroAccion(button.getAttribute("data-hero-action"), parseInt(button.getAttribute("data-index") || "0", 10));
      });
    });
    on("cms_actual_hero_agregar", "click", agregarHeroSlide);
    Array.prototype.forEach.call(document.querySelectorAll("[data-categoria-config]"), function (node) {
      node.addEventListener("input", function () {
        actualizarCategoriaConfig(node.getAttribute("data-categoria-config"), node.value);
      });
      node.addEventListener("change", function () {
        actualizarCategoriaConfig(node.getAttribute("data-categoria-config"), node.value);
      });
    });
    Array.prototype.forEach.call(document.querySelectorAll("[data-categoria-field]"), function (node) {
      node.addEventListener("input", function () {
        actualizarCategoriaItem(parseInt(node.getAttribute("data-index") || "0", 10), node.getAttribute("data-categoria-field"), node.value);
      });
      node.addEventListener("change", function () {
        actualizarCategoriaItem(parseInt(node.getAttribute("data-index") || "0", 10), node.getAttribute("data-categoria-field"), node.value);
      });
    });
    Array.prototype.forEach.call(document.querySelectorAll("[data-categoria-action]"), function (button) {
      button.addEventListener("click", function () {
        ejecutarCategoriaAccion(button.getAttribute("data-categoria-action"), parseInt(button.getAttribute("data-index") || "0", 10));
      });
    });
    on("cms_actual_categoria_agregar", "click", agregarCategoriaItem);
    Array.prototype.forEach.call(document.querySelectorAll("[data-productos-config]"), function (node) {
      node.addEventListener("input", function () {
        actualizarProductosConfig(node.getAttribute("data-productos-config"), node.value);
      });
      node.addEventListener("change", function () {
        actualizarProductosConfig(node.getAttribute("data-productos-config"), node.value);
      });
    });
    Array.prototype.forEach.call(document.querySelectorAll("[data-producto-field]"), function (node) {
      node.addEventListener("input", function () {
        actualizarProductoManual(parseInt(node.getAttribute("data-index") || "0", 10), node.getAttribute("data-producto-field"), node.value);
      });
      node.addEventListener("change", function () {
        actualizarProductoManual(parseInt(node.getAttribute("data-index") || "0", 10), node.getAttribute("data-producto-field"), node.value);
      });
    });
    Array.prototype.forEach.call(document.querySelectorAll("[data-producto-action]"), function (button) {
      button.addEventListener("click", function () {
        ejecutarProductoAccion(button.getAttribute("data-producto-action"), parseInt(button.getAttribute("data-index") || "0", 10));
      });
    });
    on("cms_actual_producto_agregar", "click", agregarProductoManual);
    Array.prototype.forEach.call(document.querySelectorAll("[data-colecciones-config]"), function (node) {
      node.addEventListener("input", function () {
        actualizarColeccionesConfig(node.getAttribute("data-colecciones-config"), node.value);
      });
      node.addEventListener("change", function () {
        actualizarColeccionesConfig(node.getAttribute("data-colecciones-config"), node.value);
      });
    });
    Array.prototype.forEach.call(document.querySelectorAll("[data-coleccion-field]"), function (node) {
      node.addEventListener("input", function () {
        actualizarColeccionItem(parseInt(node.getAttribute("data-index") || "0", 10), node.getAttribute("data-coleccion-field"), node.value);
      });
      node.addEventListener("change", function () {
        actualizarColeccionItem(parseInt(node.getAttribute("data-index") || "0", 10), node.getAttribute("data-coleccion-field"), node.value);
      });
    });
    Array.prototype.forEach.call(document.querySelectorAll("[data-coleccion-action]"), function (button) {
      button.addEventListener("click", function () {
        ejecutarColeccionAccion(button.getAttribute("data-coleccion-action"), parseInt(button.getAttribute("data-index") || "0", 10));
      });
    });
    on("cms_actual_coleccion_agregar", "click", agregarColeccionProducto);
    Array.prototype.forEach.call(document.querySelectorAll("[data-banner-config]"), function (node) {
      node.addEventListener("input", function () {
        actualizarBannerConfig(node.getAttribute("data-banner-config"), node.value);
      });
      node.addEventListener("change", function () {
        actualizarBannerConfig(node.getAttribute("data-banner-config"), node.value);
      });
    });
    Array.prototype.forEach.call(document.querySelectorAll("[data-banner-field]"), function (node) {
      node.addEventListener("input", function () {
        actualizarBannerItem(parseInt(node.getAttribute("data-index") || "0", 10), node.getAttribute("data-banner-field"), node.value);
      });
      node.addEventListener("change", function () {
        actualizarBannerItem(parseInt(node.getAttribute("data-index") || "0", 10), node.getAttribute("data-banner-field"), node.value);
      });
    });
    Array.prototype.forEach.call(document.querySelectorAll("[data-banner-action]"), function (button) {
      button.addEventListener("click", function () {
        ejecutarBannerAccion(button.getAttribute("data-banner-action"), parseInt(button.getAttribute("data-index") || "0", 10));
      });
    });
    on("cms_actual_banner_agregar", "click", agregarBannerItem);
    Array.prototype.forEach.call(document.querySelectorAll("[data-media-picker]"), function (button) {
      button.addEventListener("click", function () {
        abrirSelectorMedia(
          button.getAttribute("data-media-picker"),
          parseInt(button.getAttribute("data-index") || "0", 10),
          button.getAttribute("data-field") || ""
        );
      });
    });
  }

  function actualizarHeroConfig(campo, valor) {
    var data = heroData();
    if (campo === "visible") data.visible = valor === "1";
    else if (campo === "autoplay") data.config.autoplay = valor === "1";
    else if (campo === "intervalo_ms") data.config.intervalo_ms = parseInt(valor || "5500", 10) || 5500;
    else data.config[campo] = valor;
    refrescarJson();
  }

  function actualizarHeroSlide(index, campo, valor) {
    var slide = heroData().items[index];
    if (!slide) return;
    setPath(slide, campo, valor);
    refrescarJson();
  }

  function ejecutarHeroAccion(accion, index) {
    var items = heroData().items;
    if (!items[index]) return;
    if (accion === "duplicar") {
      var copia = JSON.parse(JSON.stringify(items[index]));
      copia.orden = (items.length + 1) * 10;
      items.splice(index + 1, 0, copia);
    }
    if (accion === "toggle") {
      items[index].visible = !items[index].visible;
    }
    if (accion === "eliminar" && items.length > 1) {
      items.splice(index, 1);
    }
    normalizarOrden(items);
    renderGrupo();
  }

  function agregarHeroSlide() {
    var items = heroData().items;
    items.push({
      titulo: "Nuevo slide",
      subtitulo: "",
      eyebrow: "Artiani",
      imagen_desktop: "",
      imagen_mobile: "",
      alt: "",
      cta: { label: "Ver catalogo", url: "/#productos" },
      cta_secundario: { label: "", url: "" },
      visible: true,
      orden: (items.length + 1) * 10
    });
    renderGrupo();
  }

  function renderCategoriasDestacadas(item) {
    var data = categoriasData();
    return '<div class="cms-actual-card mb-4">' +
      '<div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-4">' +
        '<div><div class="fw-bold">' + escapeHtml(item.codigo) + '</div><div class="text-muted fs-8">' + escapeHtml(item.descripcion) + '</div></div>' +
        '<span class="badge badge-light-success">Editor activo</span>' +
      '</div>' +
      '<div class="row g-3 mb-4">' +
        '<div class="col-md-3"><label class="form-label fs-8 fw-bold">Visible</label><select class="form-select form-select-sm" data-categoria-config="visible"><option value="1"' + (data.visible ? ' selected' : '') + '>Si</option><option value="0"' + (!data.visible ? ' selected' : '') + '>No</option></select></div>' +
        '<div class="col-md-5"><label class="form-label fs-8 fw-bold">Titulo</label><input class="form-control form-control-sm" data-categoria-config="titulo" value="' + escapeAttr(data.titulo || "") + '"></div>' +
        '<div class="col-md-4"><label class="form-label fs-8 fw-bold">Variante visual</label><input class="form-control form-control-sm" data-categoria-config="variante" value="' + escapeAttr(data.config.variante || "wokiee_category_cards") + '"></div>' +
        '<div class="col-md-8"><label class="form-label fs-8 fw-bold">Subtitulo</label><input class="form-control form-control-sm" data-categoria-config="subtitulo" value="' + escapeAttr(data.subtitulo || "") + '"></div>' +
        '<div class="col-md-2"><label class="form-label fs-8 fw-bold">Columnas desktop</label><input class="form-control form-control-sm" data-categoria-config="columnas_desktop" value="' + escapeAttr(data.config.columnas_desktop || 4) + '"></div>' +
        '<div class="col-md-2"><label class="form-label fs-8 fw-bold">Columnas mobile</label><input class="form-control form-control-sm" data-categoria-config="columnas_mobile" value="' + escapeAttr(data.config.columnas_mobile || 2) + '"></div>' +
      '</div>' +
      '<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3"><div class="fw-bold">Tarjetas de categoria</div><button class="btn btn-sm btn-light-primary" type="button" id="cms_actual_categoria_agregar"><i class="bi bi-plus-circle"></i> Agregar categoria</button></div>' +
      data.items.map(renderCategoriaItem).join("") +
      '<div class="alert alert-light-info fs-7 mb-0">Aqui solo se configura contenido visual. La categoria real se mantiene en catalogo y se referencia por categoria_id o slug.</div>' +
    '</div>';
  }

  function renderCategoriaItem(item, index) {
    var bg = item.imagen_card ? ' style="background-image:url(' + escapeAttr(urlPreviewSeguro(item.imagen_card)) + ')"' : "";
    return '<div class="cms-actual-slide mb-4" data-categoria-item="' + escapeAttr(index) + '">' +
      '<div class="d-flex justify-content-between align-items-center gap-2 mb-3">' +
        '<div class="fw-semibold">Categoria ' + escapeHtml(index + 1) + '</div>' +
        '<div class="d-flex gap-2">' +
          '<button class="btn btn-sm btn-light" type="button" data-categoria-action="duplicar" data-index="' + escapeAttr(index) + '"><i class="bi bi-copy"></i></button>' +
          '<button class="btn btn-sm btn-light-warning" type="button" data-categoria-action="toggle" data-index="' + escapeAttr(index) + '"><i class="bi ' + (item.visible ? 'bi-eye-slash' : 'bi-eye') + '"></i></button>' +
          '<button class="btn btn-sm btn-light-danger" type="button" data-categoria-action="eliminar" data-index="' + escapeAttr(index) + '"><i class="bi bi-trash"></i></button>' +
        '</div>' +
      '</div>' +
      '<div class="cms-actual-slide-preview mb-4"' + bg + '><div><div class="text-uppercase fs-8 fw-bold mb-2">' + escapeHtml(item.slug || "categoria") + '</div><h2 class="text-white fw-bold mb-2">' + escapeHtml(item.titulo || "Categoria") + '</h2><div class="opacity-75">' + escapeHtml(item.subtitulo || "") + '</div></div></div>' +
      '<div class="row g-3">' +
        inputCategoria(index, "titulo", "Titulo card", item.titulo, "col-md-4") +
        inputCategoria(index, "slug", "Slug", item.slug, "col-md-4") +
        inputCategoria(index, "categoria_id", "Categoria ID", item.categoria_id, "col-md-2") +
        inputCategoria(index, "visible", "Visible 1/0", item.visible ? "1" : "0", "col-md-2") +
        inputCategoria(index, "subtitulo", "Subtitulo", item.subtitulo, "col-md-6") +
        inputCategoria(index, "url", "URL publica", item.url, "col-md-6") +
        inputCategoria(index, "imagen_card", "Imagen card", item.imagen_card, "col-md-6") +
        inputCategoria(index, "imagen_banner", "Imagen banner", item.imagen_banner, "col-md-6") +
        inputCategoria(index, "alt", "Alt obligatorio", item.alt, "col-md-12") +
      '</div>' +
    '</div>';
  }

  function inputCategoria(index, campo, label, value, col) {
    return inputConMedia("categoria", index, campo, label, value, col, "data-categoria-field");
  }

  function actualizarCategoriaConfig(campo, valor) {
    var data = categoriasData();
    if (campo === "visible") data.visible = valor === "1";
    else if (campo === "titulo") data.titulo = valor;
    else if (campo === "subtitulo") data.subtitulo = valor;
    else if (campo === "columnas_desktop") data.config.columnas_desktop = parseInt(valor || "4", 10) || 4;
    else if (campo === "columnas_mobile") data.config.columnas_mobile = parseInt(valor || "2", 10) || 2;
    else data.config[campo] = valor;
    refrescarJson();
  }

  function actualizarCategoriaItem(index, campo, valor) {
    var item = categoriasData().items[index];
    if (!item) return;
    if (campo === "categoria_id") item.categoria_id = parseInt(valor || "0", 10) || 0;
    else if (campo === "visible") item.visible = valor === "1";
    else item[campo] = valor;
    refrescarJson();
  }

  function ejecutarCategoriaAccion(accion, index) {
    var items = categoriasData().items;
    if (!items[index]) return;
    if (accion === "duplicar") {
      var copia = JSON.parse(JSON.stringify(items[index]));
      copia.orden = (items.length + 1) * 10;
      items.splice(index + 1, 0, copia);
    }
    if (accion === "toggle") {
      items[index].visible = !items[index].visible;
    }
    if (accion === "eliminar" && items.length > 1) {
      items.splice(index, 1);
    }
    normalizarOrden(items);
    renderGrupo();
  }

  function agregarCategoriaItem() {
    var items = categoriasData().items;
    items.push({
      categoria_id: 0,
      slug: "nueva-categoria",
      titulo: "Nueva categoria",
      subtitulo: "",
      imagen_card: "",
      imagen_banner: "",
      alt: "",
      url: "/categoria/nueva-categoria",
      visible: true,
      orden: (items.length + 1) * 10
    });
    renderGrupo();
  }

  function renderProductosDestacados(item) {
    var data = productosData();
    return '<div class="cms-actual-card mb-4">' +
      '<div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-4">' +
        '<div><div class="fw-bold">' + escapeHtml(item.codigo) + '</div><div class="text-muted fs-8">' + escapeHtml(item.descripcion) + '</div></div>' +
        '<span class="badge badge-light-success">Editor activo</span>' +
      '</div>' +
      '<div class="row g-3 mb-4">' +
        '<div class="col-md-2"><label class="form-label fs-8 fw-bold">Visible</label><select class="form-select form-select-sm" data-productos-config="visible"><option value="1"' + (data.visible ? ' selected' : '') + '>Si</option><option value="0"' + (!data.visible ? ' selected' : '') + '>No</option></select></div>' +
        '<div class="col-md-4"><label class="form-label fs-8 fw-bold">Titulo</label><input class="form-control form-control-sm" data-productos-config="titulo" value="' + escapeAttr(data.titulo || "") + '"></div>' +
        '<div class="col-md-4"><label class="form-label fs-8 fw-bold">Subtitulo</label><input class="form-control form-control-sm" data-productos-config="subtitulo" value="' + escapeAttr(data.subtitulo || "") + '"></div>' +
        '<div class="col-md-2"><label class="form-label fs-8 fw-bold">Limite</label><input class="form-control form-control-sm" data-productos-config="limite" value="' + escapeAttr(data.limite || 12) + '"></div>' +
        '<div class="col-md-3"><label class="form-label fs-8 fw-bold">Modo</label><select class="form-select form-select-sm" data-productos-config="fuente.modo"><option value="criterio"' + (data.fuente.modo === "criterio" ? ' selected' : '') + '>Criterio automatico</option><option value="manual"' + (data.fuente.modo === "manual" ? ' selected' : '') + '>Lista manual</option></select></div>' +
        '<div class="col-md-3"><label class="form-label fs-8 fw-bold">Criterio</label><select class="form-select form-select-sm" data-productos-config="fuente.criterio"><option value="destacados"' + (data.fuente.criterio === "destacados" ? ' selected' : '') + '>Destacados</option><option value="novedades"' + (data.fuente.criterio === "novedades" ? ' selected' : '') + '>Novedades</option><option value="temporada"' + (data.fuente.criterio === "temporada" ? ' selected' : '') + '>Temporada</option><option value="mas_vistos"' + (data.fuente.criterio === "mas_vistos" ? ' selected' : '') + '>Mas vistos</option></select></div>' +
        '<div class="col-md-3"><label class="form-label fs-8 fw-bold">Categoria slug</label><input class="form-control form-control-sm" data-productos-config="fuente.categoria_slug" value="' + escapeAttr(data.fuente.categoria_slug || "") + '"></div>' +
        '<div class="col-md-3"><label class="form-label fs-8 fw-bold">Marca slug</label><input class="form-control form-control-sm" data-productos-config="fuente.marca_slug" value="' + escapeAttr(data.fuente.marca_slug || "") + '"></div>' +
        '<div class="col-md-3"><label class="form-label fs-8 fw-bold">Variante visual</label><input class="form-control form-control-sm" data-productos-config="config.variante" value="' + escapeAttr(data.config.variante || "wokiee_product_carousel") + '"></div>' +
        '<div class="col-md-3"><label class="form-label fs-8 fw-bold">Mostrar precio</label><select class="form-select form-select-sm" data-productos-config="config.mostrar_precio"><option value="1"' + (data.config.mostrar_precio ? ' selected' : '') + '>Si</option><option value="0"' + (!data.config.mostrar_precio ? ' selected' : '') + '>No</option></select></div>' +
        '<div class="col-md-3"><label class="form-label fs-8 fw-bold">CTA texto</label><input class="form-control form-control-sm" data-productos-config="cta.label" value="' + escapeAttr((data.cta || {}).label || "") + '"></div>' +
        '<div class="col-md-3"><label class="form-label fs-8 fw-bold">CTA URL</label><input class="form-control form-control-sm" data-productos-config="cta.url" value="' + escapeAttr((data.cta || {}).url || "") + '"></div>' +
      '</div>' +
      '<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3"><div class="fw-bold">Lista manual opcional</div><button class="btn btn-sm btn-light-primary" type="button" id="cms_actual_producto_agregar"><i class="bi bi-plus-circle"></i> Agregar producto</button></div>' +
      (data.fuente.productos || []).map(renderProductoManual).join("") +
      '<div class="alert alert-light-info fs-7 mb-0">El CMS solo envia referencias y criterio. El frontend debe consultar productos por API publica y no mostrar stock exacto.</div>' +
    '</div>';
  }

  function renderProductoManual(item, index) {
    return '<div class="cms-actual-slide mb-4" data-producto-item="' + escapeAttr(index) + '">' +
      '<div class="d-flex justify-content-between align-items-center gap-2 mb-3">' +
        '<div class="fw-semibold">Producto manual ' + escapeHtml(index + 1) + '</div>' +
        '<div class="d-flex gap-2">' +
          '<button class="btn btn-sm btn-light" type="button" data-producto-action="duplicar" data-index="' + escapeAttr(index) + '"><i class="bi bi-copy"></i></button>' +
          '<button class="btn btn-sm btn-light-danger" type="button" data-producto-action="eliminar" data-index="' + escapeAttr(index) + '"><i class="bi bi-trash"></i></button>' +
        '</div>' +
      '</div>' +
      '<div class="row g-3">' +
        inputProducto(index, "producto_id", "Producto ID", item.producto_id, "col-md-2") +
        inputProducto(index, "sku", "SKU", item.sku, "col-md-3") +
        inputProducto(index, "slug", "Slug producto", item.slug, "col-md-3") +
        inputProducto(index, "titulo_override", "Titulo opcional", item.titulo_override, "col-md-4") +
      '</div>' +
    '</div>';
  }

  function inputProducto(index, campo, label, value, col) {
    return '<div class="' + escapeAttr(col || "col-md-6") + '"><label class="form-label fs-8 fw-bold">' + escapeHtml(label) + '</label><input class="form-control form-control-sm" data-producto-field="' + escapeAttr(campo) + '" data-index="' + escapeAttr(index) + '" value="' + escapeAttr(value == null ? "" : value) + '"></div>';
  }

  function actualizarProductosConfig(campo, valor) {
    var data = productosData();
    if (campo === "visible") data.visible = valor === "1";
    else if (campo === "limite") data.limite = parseInt(valor || "12", 10) || 12;
    else if (campo === "config.mostrar_precio") data.config.mostrar_precio = valor === "1";
    else setPath(data, campo, valor);
    refrescarJson();
  }

  function actualizarProductoManual(index, campo, valor) {
    var item = productosData().fuente.productos[index];
    if (!item) return;
    if (campo === "producto_id") item.producto_id = parseInt(valor || "0", 10) || 0;
    else item[campo] = valor;
    refrescarJson();
  }

  function ejecutarProductoAccion(accion, index) {
    var items = productosData().fuente.productos;
    if (!items[index]) return;
    if (accion === "duplicar") {
      var copia = JSON.parse(JSON.stringify(items[index]));
      copia.orden = (items.length + 1) * 10;
      items.splice(index + 1, 0, copia);
    }
    if (accion === "eliminar" && items.length > 1) {
      items.splice(index, 1);
    }
    normalizarOrden(items);
    renderGrupo();
  }

  function agregarProductoManual() {
    var items = productosData().fuente.productos;
    items.push({
      producto_id: 0,
      sku: "",
      slug: "",
      titulo_override: "",
      orden: (items.length + 1) * 10
    });
    renderGrupo();
  }

  function renderColeccionesProductos(item) {
    var data = coleccionesData();
    return '<div class="cms-actual-card mb-4">' +
      '<div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-4">' +
        '<div><div class="fw-bold">' + escapeHtml(item.codigo) + '</div><div class="text-muted fs-8">' + escapeHtml(item.descripcion) + '</div></div>' +
        '<span class="badge badge-light-success">Editor activo</span>' +
      '</div>' +
      '<div class="row g-3 mb-4">' +
        '<div class="col-md-2"><label class="form-label fs-8 fw-bold">Visible</label><select class="form-select form-select-sm" data-colecciones-config="visible"><option value="1"' + (data.visible ? ' selected' : '') + '>Si</option><option value="0"' + (!data.visible ? ' selected' : '') + '>No</option></select></div>' +
        '<div class="col-md-4"><label class="form-label fs-8 fw-bold">Titulo grupo</label><input class="form-control form-control-sm" data-colecciones-config="titulo" value="' + escapeAttr(data.titulo || "") + '"></div>' +
        '<div class="col-md-4"><label class="form-label fs-8 fw-bold">Subtitulo grupo</label><input class="form-control form-control-sm" data-colecciones-config="subtitulo" value="' + escapeAttr(data.subtitulo || "") + '"></div>' +
        '<div class="col-md-2"><label class="form-label fs-8 fw-bold">Variante</label><input class="form-control form-control-sm" data-colecciones-config="config.variante" value="' + escapeAttr(data.config.variante || "wokiee_collection_rows") + '"></div>' +
      '</div>' +
      '<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3"><div class="fw-bold">Colecciones</div><button class="btn btn-sm btn-light-primary" type="button" id="cms_actual_coleccion_agregar"><i class="bi bi-plus-circle"></i> Agregar coleccion</button></div>' +
      (data.items || []).map(renderColeccionItem).join("") +
      '<div class="alert alert-light-info fs-7 mb-0">Cada coleccion es una vitrina independiente. Puedes usar criterio automatico o referencias manuales separadas por coma.</div>' +
    '</div>';
  }

  function renderColeccionItem(item, index) {
    return '<div class="cms-actual-slide mb-4" data-coleccion-item="' + escapeAttr(index) + '">' +
      '<div class="d-flex justify-content-between align-items-center gap-2 mb-3">' +
        '<div class="fw-semibold">Coleccion ' + escapeHtml(index + 1) + '</div>' +
        '<div class="d-flex gap-2">' +
          '<button class="btn btn-sm btn-light" type="button" data-coleccion-action="duplicar" data-index="' + escapeAttr(index) + '"><i class="bi bi-copy"></i></button>' +
          '<button class="btn btn-sm btn-light-warning" type="button" data-coleccion-action="toggle" data-index="' + escapeAttr(index) + '"><i class="bi ' + (item.visible ? 'bi-eye-slash' : 'bi-eye') + '"></i></button>' +
          '<button class="btn btn-sm btn-light-danger" type="button" data-coleccion-action="eliminar" data-index="' + escapeAttr(index) + '"><i class="bi bi-trash"></i></button>' +
        '</div>' +
      '</div>' +
      '<div class="row g-3">' +
        inputColeccion(index, "codigo", "Codigo interno", item.codigo, "col-md-3") +
        inputColeccion(index, "titulo", "Titulo", item.titulo, "col-md-3") +
        inputColeccion(index, "subtitulo", "Subtitulo", item.subtitulo, "col-md-6") +
        inputColeccion(index, "fuente.modo", "Modo criterio/manual", (item.fuente || {}).modo, "col-md-3") +
        inputColeccion(index, "fuente.criterio", "Criterio", (item.fuente || {}).criterio, "col-md-3") +
        inputColeccion(index, "fuente.categoria_slug", "Categoria slug", (item.fuente || {}).categoria_slug, "col-md-3") +
        inputColeccion(index, "fuente.marca_slug", "Marca slug", (item.fuente || {}).marca_slug, "col-md-3") +
        inputColeccion(index, "limite", "Limite", item.limite, "col-md-2") +
        inputColeccion(index, "cta.label", "CTA texto", (item.cta || {}).label, "col-md-3") +
        inputColeccion(index, "cta.url", "CTA URL", (item.cta || {}).url, "col-md-4") +
        inputColeccion(index, "config.variante", "Variante visual", (item.config || {}).variante, "col-md-3") +
        inputColeccion(index, "fuente.productos_csv", "SKUs/IDs/slugs manuales separados por coma", productosCsv((item.fuente || {}).productos), "col-md-12") +
      '</div>' +
    '</div>';
  }

  function inputColeccion(index, campo, label, value, col) {
    return '<div class="' + escapeAttr(col || "col-md-6") + '"><label class="form-label fs-8 fw-bold">' + escapeHtml(label) + '</label><input class="form-control form-control-sm" data-coleccion-field="' + escapeAttr(campo) + '" data-index="' + escapeAttr(index) + '" value="' + escapeAttr(value == null ? "" : value) + '"></div>';
  }

  function actualizarColeccionesConfig(campo, valor) {
    var data = coleccionesData();
    if (campo === "visible") data.visible = valor === "1";
    else setPath(data, campo, valor);
    refrescarJson();
  }

  function actualizarColeccionItem(index, campo, valor) {
    var item = coleccionesData().items[index];
    if (!item) return;
    if (campo === "limite") item.limite = parseInt(valor || "8", 10) || 8;
    else if (campo === "fuente.productos_csv") setPath(item, "fuente.productos", productosDesdeCsv(valor));
    else setPath(item, campo, valor);
    refrescarJson();
  }

  function ejecutarColeccionAccion(accion, index) {
    var items = coleccionesData().items;
    if (!items[index]) return;
    if (accion === "duplicar") {
      var copia = JSON.parse(JSON.stringify(items[index]));
      copia.codigo = (copia.codigo || "coleccion") + "_copia";
      copia.orden = (items.length + 1) * 10;
      items.splice(index + 1, 0, copia);
    }
    if (accion === "toggle") {
      items[index].visible = !items[index].visible;
    }
    if (accion === "eliminar" && items.length > 1) {
      items.splice(index, 1);
    }
    normalizarOrden(items);
    renderGrupo();
  }

  function agregarColeccionProducto() {
    var items = coleccionesData().items;
    items.push({
      codigo: "nueva_coleccion",
      titulo: "Nueva coleccion",
      subtitulo: "",
      visible: true,
      orden: (items.length + 1) * 10,
      fuente: { modo: "criterio", criterio: "destacados", categoria_slug: "", marca_slug: "", productos: [] },
      limite: 8,
      cta: { label: "Ver productos", url: "/#productos" },
      config: { variante: "wokiee_product_row" }
    });
    renderGrupo();
  }

  function renderBannerHome(item) {
    var data = bannerData();
    return '<div class="cms-actual-card mb-4">' +
      '<div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-4">' +
        '<div><div class="fw-bold">' + escapeHtml(item.codigo) + '</div><div class="text-muted fs-8">' + escapeHtml(item.descripcion) + '</div></div>' +
        '<span class="badge badge-light-success">Editor activo</span>' +
      '</div>' +
      '<div class="row g-3 mb-4">' +
        '<div class="col-md-2"><label class="form-label fs-8 fw-bold">Visible</label><select class="form-select form-select-sm" data-banner-config="visible"><option value="1"' + (data.visible ? ' selected' : '') + '>Si</option><option value="0"' + (!data.visible ? ' selected' : '') + '>No</option></select></div>' +
        '<div class="col-md-4"><label class="form-label fs-8 fw-bold">Titulo interno</label><input class="form-control form-control-sm" data-banner-config="titulo" value="' + escapeAttr(data.titulo || "") + '"></div>' +
        '<div class="col-md-4"><label class="form-label fs-8 fw-bold">Subtitulo interno</label><input class="form-control form-control-sm" data-banner-config="subtitulo" value="' + escapeAttr(data.subtitulo || "") + '"></div>' +
        '<div class="col-md-2"><label class="form-label fs-8 fw-bold">Modo</label><select class="form-select form-select-sm" data-banner-config="config.modo"><option value="estatico"' + (data.config.modo === "estatico" ? ' selected' : '') + '>Estatico</option><option value="slides"' + (data.config.modo === "slides" ? ' selected' : '') + '>Slides futuro</option></select></div>' +
        '<div class="col-md-4"><label class="form-label fs-8 fw-bold">Variante visual</label><input class="form-control form-control-sm" data-banner-config="config.variante" value="' + escapeAttr(data.config.variante || "wokiee_banner_full_width") + '"></div>' +
      '</div>' +
      '<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3"><div class="fw-bold">Imagenes del banner</div><button class="btn btn-sm btn-light-primary" type="button" id="cms_actual_banner_agregar"><i class="bi bi-plus-circle"></i> Agregar slide futuro</button></div>' +
      (data.items || []).map(renderBannerItem).join("") +
      '<div class="alert alert-light-info fs-7 mb-0">Por ahora se usa como banner estatico. Si despues el frontend lo soporta como carrusel, los items ya quedan preparados.</div>' +
    '</div>';
  }

  function renderBannerItem(item, index) {
    var bg = item.imagen_desktop ? ' style="background-image:url(' + escapeAttr(urlPreviewSeguro(item.imagen_desktop)) + ')"' : "";
    return '<div class="cms-actual-slide mb-4" data-banner-item="' + escapeAttr(index) + '">' +
      '<div class="d-flex justify-content-between align-items-center gap-2 mb-3">' +
        '<div class="fw-semibold">Banner ' + escapeHtml(index + 1) + '</div>' +
        '<div class="d-flex gap-2">' +
          '<button class="btn btn-sm btn-light" type="button" data-banner-action="duplicar" data-index="' + escapeAttr(index) + '"><i class="bi bi-copy"></i></button>' +
          '<button class="btn btn-sm btn-light-warning" type="button" data-banner-action="toggle" data-index="' + escapeAttr(index) + '"><i class="bi ' + (item.visible ? 'bi-eye-slash' : 'bi-eye') + '"></i></button>' +
          '<button class="btn btn-sm btn-light-danger" type="button" data-banner-action="eliminar" data-index="' + escapeAttr(index) + '"><i class="bi bi-trash"></i></button>' +
        '</div>' +
      '</div>' +
      '<div class="cms-actual-slide-preview mb-4"' + bg + '><div><h2 class="text-white fw-bold mb-2">' + escapeHtml(item.titulo || "Banner de Home") + '</h2><div class="opacity-75">' + escapeHtml(item.subtitulo || "") + '</div></div></div>' +
      '<div class="row g-3">' +
        inputBanner(index, "titulo", "Titulo visible", item.titulo, "col-md-4") +
        inputBanner(index, "subtitulo", "Subtitulo visible", item.subtitulo, "col-md-4") +
        inputBanner(index, "alt", "Alt obligatorio", item.alt, "col-md-4") +
        inputBanner(index, "imagen_desktop", "Imagen desktop", item.imagen_desktop, "col-md-6") +
        inputBanner(index, "imagen_mobile", "Imagen mobile", item.imagen_mobile, "col-md-6") +
        inputBanner(index, "cta.label", "CTA texto", (item.cta || {}).label, "col-md-3") +
        inputBanner(index, "cta.url", "CTA URL", (item.cta || {}).url, "col-md-3") +
      '</div>' +
    '</div>';
  }

  function inputBanner(index, campo, label, value, col) {
    return inputConMedia("banner", index, campo, label, value, col, "data-banner-field");
  }

  function inputConMedia(contexto, index, campo, label, value, col, dataAttr) {
    var esImagen = campo === "imagen_desktop" || campo === "imagen_mobile" || campo === "imagen_card" || campo === "imagen_banner";
    var input = '<input class="form-control form-control-sm" ' + dataAttr + '="' + escapeAttr(campo) + '" data-index="' + escapeAttr(index) + '" value="' + escapeAttr(value == null ? "" : value) + '">';
    if (esImagen) {
      input = '<div class="input-group input-group-sm">' +
        input +
        '<button class="btn btn-light-primary" type="button" data-media-picker="' + escapeAttr(contexto) + '" data-index="' + escapeAttr(index) + '" data-field="' + escapeAttr(campo) + '"><i class="bi bi-images"></i> Media</button>' +
      '</div>';
    }
    return '<div class="' + escapeAttr(col || "col-md-6") + '"><label class="form-label fs-8 fw-bold">' + escapeHtml(label) + '</label>' + input + '</div>';
  }

  function actualizarBannerConfig(campo, valor) {
    var data = bannerData();
    if (campo === "visible") data.visible = valor === "1";
    else setPath(data, campo, valor);
    refrescarJson();
  }

  function actualizarBannerItem(index, campo, valor) {
    var item = bannerData().items[index];
    if (!item) return;
    setPath(item, campo, valor);
    refrescarJson();
  }

  function ejecutarBannerAccion(accion, index) {
    var items = bannerData().items;
    if (!items[index]) return;
    if (accion === "duplicar") {
      var copia = JSON.parse(JSON.stringify(items[index]));
      copia.orden = (items.length + 1) * 10;
      items.splice(index + 1, 0, copia);
    }
    if (accion === "toggle") {
      items[index].visible = !items[index].visible;
    }
    if (accion === "eliminar" && items.length > 1) {
      items.splice(index, 1);
    }
    normalizarOrden(items);
    renderGrupo();
  }

  function agregarBannerItem() {
    var items = bannerData().items;
    items.push({
      titulo: "Nuevo banner",
      subtitulo: "",
      imagen_desktop: "",
      imagen_mobile: "",
      alt: "",
      cta: { label: "Ver productos", url: "/#productos" },
      visible: true,
      orden: (items.length + 1) * 10
    });
    renderGrupo();
  }

  function previewJson(grupo) {
    if (grupo.codigo === "home") {
      return previewHomeJson();
    }
    return {
      tipo: "success",
      mensaje: "CMS frontend consultado",
      depurar: {
        version: "cms_frontend_2026_08_13",
        pagina: grupo.codigo,
        actualizado_en: "pendiente",
        seo: {},
        config: {},
        secciones: grupo.secciones.map(function (item, index) {
          return {
            codigo: item.codigo,
            tipo: item.tipo,
            visible: true,
            orden: (index + 1) * 10,
            titulo: "",
            subtitulo: "",
            items: [],
            cta: {},
            config: {}
          };
        })
      }
    };
  }

  function previewHomeJson() {
    var grupo = grupos.filter(function (item) { return item.codigo === "home"; })[0];
    var hero = heroData();
    return {
      tipo: "success",
      mensaje: "CMS frontend consultado",
      depurar: {
        version: "cms_frontend_2026_08_13",
        pagina: "home",
        actualizado_en: "pendiente",
        seo: {},
        config: {},
        secciones: grupo.secciones.map(function (item, index) {
          if (item.codigo === "home_hero_carrusel") return hero;
          if (item.codigo === "home_categorias_destacadas") return categoriasData();
          if (item.codigo === "home_productos_destacados") return productosData();
          if (item.codigo === "home_colecciones") return coleccionesData();
          if (item.codigo === "home_banner") return bannerData();
          return {
            codigo: item.codigo,
            tipo: item.tipo,
            visible: true,
            orden: (index + 1) * 10,
            titulo: "",
            subtitulo: "",
            items: [],
            cta: {},
            config: {}
          };
        })
      }
    };
  }

  function heroData() {
    return estado.datos.home.home_hero_carrusel;
  }

  function categoriasData() {
    return estado.datos.home.home_categorias_destacadas;
  }

  function productosData() {
    return estado.datos.home.home_productos_destacados;
  }

  function coleccionesData() {
    return estado.datos.home.home_colecciones;
  }

  function bannerData() {
    return estado.datos.home.home_banner;
  }

  function abrirSelectorMedia(contexto, index, campo) {
    estado.mediaPicker = { contexto: contexto, index: index, campo: campo, archivo: null, dataUrl: "", seleccion: "" };
    asegurarModalMedia();
    renderMediaPicker();
    var modalNode = $("cms_actual_media_modal");
    if (window.bootstrap && bootstrap.Modal) {
      bootstrap.Modal.getOrCreateInstance(modalNode).show();
    } else if (modalNode) {
      modalNode.style.display = "block";
      modalNode.classList.add("show");
    }
  }

  function asegurarModalMedia() {
    if ($("cms_actual_media_modal")) return;
    var wrapper = document.createElement("div");
    wrapper.innerHTML = '<div class="modal fade" id="cms_actual_media_modal" tabindex="-1" aria-hidden="true">' +
      '<div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content">' +
        '<div class="modal-header"><div><h3 class="modal-title fw-bold">Seleccionar imagen de Media</h3><div class="text-muted fs-7">Biblioteca local de /cms/media.</div></div><button type="button" class="btn btn-icon btn-sm btn-light" data-bs-dismiss="modal"><i class="bi bi-x-lg"></i></button></div>' +
        '<div class="modal-body">' +
          '<div class="alert alert-info py-3 fs-7">Puedes elegir una imagen existente o cargar una nueva aqui mismo. En esta fase queda en biblioteca local; todavia no hay upload real al servidor.</div>' +
          '<div class="border rounded p-4 mb-5 bg-light">' +
            '<div class="fw-bold mb-3">Cargar nueva imagen</div>' +
            '<div class="row g-3 align-items-end">' +
              '<div class="col-md-4"><label class="form-label fs-8 fw-bold">Archivo</label><input class="form-control form-control-sm" id="cms_actual_media_archivo" type="file" accept="image/jpeg,image/png,image/webp"></div>' +
              '<div class="col-md-2"><label class="form-label fs-8 fw-bold">Uso</label><select class="form-select form-select-sm" id="cms_actual_media_nuevo_uso"><option value="home">Home</option><option value="categoria">Categoria</option><option value="producto">Producto</option><option value="global">Global</option><option value="blog">Blog futuro</option></select></div>' +
              '<div class="col-md-2"><label class="form-label fs-8 fw-bold">Tipo</label><select class="form-select form-select-sm" id="cms_actual_media_nuevo_tipo"><option value="banner">Banner</option><option value="hero">Hero</option><option value="card">Card</option><option value="thumb">Thumbnail</option><option value="editorial">Editorial</option></select></div>' +
              '<div class="col-md-3"><label class="form-label fs-8 fw-bold">Alt text</label><input class="form-control form-control-sm" id="cms_actual_media_nuevo_alt" type="text"></div>' +
              '<div class="col-md-1"><button class="btn btn-sm btn-primary w-100" type="button" id="cms_actual_media_agregar_usar"><i class="bi bi-check2"></i></button></div>' +
            '</div>' +
            '<div class="mt-3" id="cms_actual_media_preview_nuevo"></div>' +
          '</div>' +
          '<div class="row g-4">' +
            '<div class="col-lg-8">' +
              '<div class="d-flex justify-content-between align-items-end flex-wrap gap-3 mb-4"><div><div class="fw-bold">Galeria disponible</div><div class="text-muted fs-8">Previsualiza y elige una imagen.</div></div><div class="d-flex gap-2"><input class="form-control form-control-sm w-200px" id="cms_actual_media_buscar" type="text" placeholder="Filtro opcional"><select class="form-select form-select-sm w-150px" id="cms_actual_media_uso"><option value="">Todos</option><option value="home">Home</option><option value="categoria">Categoria</option><option value="producto">Producto</option><option value="global">Global</option><option value="blog">Blog futuro</option></select></div></div>' +
              '<div class="row g-4" id="cms_actual_media_lista"></div>' +
            '</div>' +
            '<div class="col-lg-4">' +
              '<div class="border rounded p-4 bg-white h-100" id="cms_actual_media_preview_seleccion"></div>' +
            '</div>' +
          '</div>' +
        '</div>' +
      '</div></div>' +
    '</div>';
    document.body.appendChild(wrapper.firstChild);
    on("cms_actual_media_archivo", "change", prepararMediaDesdeModal);
    on("cms_actual_media_agregar_usar", "click", agregarYUsarMediaDesdeModal);
    on("cms_actual_media_usar_seleccion", "click", function () {
      if (estado.mediaPicker && estado.mediaPicker.seleccion) aplicarMediaSeleccionada(estado.mediaPicker.seleccion);
    });
    on("cms_actual_media_buscar", "input", renderMediaPicker);
    on("cms_actual_media_uso", "change", renderMediaPicker);
    var lista = $("cms_actual_media_lista");
    if (lista) {
      lista.addEventListener("click", function (event) {
        var button = event.target.closest("[data-media-select]");
        if (!button) return;
        seleccionarMediaPreview(button.getAttribute("data-media-select"));
      });
    }
  }

  function prepararMediaDesdeModal() {
    var input = $("cms_actual_media_archivo");
    var file = input && input.files && input.files[0] ? input.files[0] : null;
    estado.mediaPicker.archivo = null;
    estado.mediaPicker.dataUrl = "";
    setText("cms_actual_media_preview_nuevo", "");
    if (!file) return;
    var bloqueo = validarMediaFile(file);
    if (bloqueo) {
      setText("cms_actual_media_preview_nuevo", bloqueo);
      input.value = "";
      return;
    }
    var reader = new FileReader();
    reader.onload = function () {
      estado.mediaPicker.archivo = file;
      estado.mediaPicker.dataUrl = String(reader.result || "");
      var node = $("cms_actual_media_preview_nuevo");
      if (node) {
        node.innerHTML = '<div class="d-flex align-items-center gap-3"><img src="' + escapeAttr(estado.mediaPicker.dataUrl) + '" alt="' + escapeAttr(file.name) + '" style="width:120px;aspect-ratio:16/10;object-fit:cover;border-radius:8px;border:1px solid #e7e9ef;background:#fff;"><div><div class="fw-semibold">' + escapeHtml(file.name) + '</div><div class="text-muted fs-8">' + escapeHtml(formatoBytes(file.size)) + '</div></div></div>';
      }
    };
    reader.onerror = function () {
      setText("cms_actual_media_preview_nuevo", "No se pudo leer la imagen.");
    };
    reader.readAsDataURL(file);
  }

  function agregarYUsarMediaDesdeModal() {
    var file = estado.mediaPicker.archivo;
    var dataUrl = estado.mediaPicker.dataUrl;
    if (!file || !dataUrl) {
      setText("cms_actual_media_preview_nuevo", "Selecciona una imagen primero.");
      return;
    }
    var alt = valor("cms_actual_media_nuevo_alt").trim();
    if (!alt) {
      setText("cms_actual_media_preview_nuevo", "Captura alt text antes de usar la imagen.");
      return;
    }
    var item = {
      id: "media_" + Date.now(),
      nombre: file.name,
      mime: file.type,
      bytes: file.size,
      url: dataUrl,
      alt: alt,
      uso: valor("cms_actual_media_nuevo_uso") || "home",
      tipo: valor("cms_actual_media_nuevo_tipo") || "banner",
      estatus: "activo",
      creado_en: new Date().toISOString()
    };
    var items = mediaLocalItems();
    items.unshift(item);
    guardarMediaLocalItems(items);
    estado.mediaPicker.archivo = null;
    estado.mediaPicker.dataUrl = "";
    if ($("cms_actual_media_archivo")) $("cms_actual_media_archivo").value = "";
    setText("cms_actual_media_preview_nuevo", "");
    aplicarMediaSeleccionada(item.id);
  }

  function renderMediaPicker() {
    var node = $("cms_actual_media_lista");
    if (!node) return;
    var items = mediaLocalItems().filter(function (item) {
      var busqueda = valor("cms_actual_media_buscar").toLowerCase();
      var uso = valor("cms_actual_media_uso");
      var texto = [item.nombre, item.alt, item.uso, item.tipo].join(" ").toLowerCase();
      return item.estatus !== "archivado" && (!uso || item.uso === uso) && (!busqueda || texto.indexOf(busqueda) !== -1);
    });
    if (!items.length) {
      node.innerHTML = '<div class="col-12"><div class="text-muted">Sin imagenes locales disponibles. Agrega imagenes desde /cms/media.</div></div>';
      renderMediaPickerPreview(null);
      return;
    }
    if (!estado.mediaPicker.seleccion || !items.some(function (item) { return item.id === estado.mediaPicker.seleccion; })) {
      estado.mediaPicker.seleccion = items[0].id;
    }
    node.innerHTML = items.map(function (item) {
      return '<div class="col-md-4 col-xl-3">' +
        '<div class="border rounded overflow-hidden h-100 bg-white ' + (item.id === estado.mediaPicker.seleccion ? 'border-primary' : '') + '">' +
          '<img src="' + escapeAttr(item.url) + '" alt="' + escapeAttr(item.alt) + '" style="width:100%;aspect-ratio:16/10;object-fit:cover;background:#f3f6f9;">' +
          '<div class="p-3">' +
            '<div class="fw-bold text-truncate">' + escapeHtml(item.nombre) + '</div>' +
            '<div class="text-muted fs-8 text-truncate mb-3">' + escapeHtml(item.alt) + '</div>' +
            '<div class="d-flex justify-content-between align-items-center gap-2 mb-3"><span class="badge badge-light-primary">' + escapeHtml(item.uso) + '</span><span class="text-muted fs-8">' + escapeHtml(item.tipo) + '</span></div>' +
            '<button type="button" class="btn btn-sm btn-light-primary w-100" data-media-select="' + escapeAttr(item.id) + '"><i class="bi bi-eye"></i> Previsualizar</button>' +
          '</div>' +
        '</div>' +
      '</div>';
    }).join("");
    renderMediaPickerPreview(mediaLocalItems().filter(function (item) { return item.id === estado.mediaPicker.seleccion; })[0] || null);
  }

  function seleccionarMediaPreview(id) {
    estado.mediaPicker.seleccion = id;
    renderMediaPicker();
  }

  function renderMediaPickerPreview(item) {
    var node = $("cms_actual_media_preview_seleccion");
    if (!node) return;
    if (!item) {
      node.innerHTML = '<div class="text-muted">Selecciona una imagen de la galeria para revisarla antes de aplicarla.</div>';
      return;
    }
    node.innerHTML = '<div class="fw-bold mb-3">Preview seleccionado</div>' +
      '<img src="' + escapeAttr(item.url) + '" alt="' + escapeAttr(item.alt) + '" style="width:100%;aspect-ratio:16/11;object-fit:cover;border-radius:8px;border:1px solid #e7e9ef;background:#f3f6f9;">' +
      '<div class="fw-semibold mt-3 text-break">' + escapeHtml(item.nombre) + '</div>' +
      '<div class="text-muted fs-7 mt-1">' + escapeHtml(item.alt) + '</div>' +
      '<div class="d-flex flex-wrap gap-2 mt-3"><span class="badge badge-light-primary">' + escapeHtml(item.uso) + '</span><span class="badge badge-light-info">' + escapeHtml(item.tipo) + '</span><span class="badge badge-light">' + escapeHtml(formatoBytes(item.bytes)) + '</span></div>' +
      '<button class="btn btn-primary w-100 mt-4" type="button" id="cms_actual_media_usar_seleccion"><i class="bi bi-check2-circle"></i> Usar imagen seleccionada</button>';
    on("cms_actual_media_usar_seleccion", "click", function () {
      aplicarMediaSeleccionada(item.id);
    });
  }

  function aplicarMediaSeleccionada(id) {
    var media = mediaLocalItems().filter(function (item) { return item.id === id; })[0];
    if (!media) return;
    var picker = estado.mediaPicker || {};
    var target = null;
    if (picker.contexto === "hero") target = heroData().items[picker.index];
    if (picker.contexto === "categoria") target = categoriasData().items[picker.index];
    if (picker.contexto === "banner") target = bannerData().items[picker.index];
    if (!target) return;
    setPath(target, picker.campo, media.url);
    if (!target.alt && media.alt) target.alt = media.alt;
    renderGrupo();
    setText("cms_actual_estado", "Media aplicada");
    var modalNode = $("cms_actual_media_modal");
    if (window.bootstrap && bootstrap.Modal && modalNode) {
      bootstrap.Modal.getOrCreateInstance(modalNode).hide();
    } else if (modalNode) {
      modalNode.style.display = "none";
      modalNode.classList.remove("show");
    }
  }

  function mediaLocalItems() {
    try {
      return JSON.parse(localStorage.getItem(MEDIA_STORAGE_KEY) || "[]");
    } catch (error) {
      return [];
    }
  }

  function guardarMediaLocalItems(items) {
    localStorage.setItem(MEDIA_STORAGE_KEY, JSON.stringify(items || []));
  }

  function validarMediaFile(file) {
    if (MEDIA_MIMES.indexOf(file.type) === -1) return "Tipo no permitido. Usa JPG, PNG o WebP.";
    if (file.size > MEDIA_MAX_BYTES) return "La imagen supera 2 MB.";
    return "";
  }

  function formatoBytes(bytes) {
    bytes = parseInt(bytes || 0, 10) || 0;
    if (bytes < 1024) return bytes + " B";
    if (bytes < 1024 * 1024) return Math.round(bytes / 1024) + " KB";
    return (bytes / (1024 * 1024)).toFixed(2) + " MB";
  }

  function productosCsv(items) {
    return (items || []).map(function (item) {
      return item.sku || item.slug || item.producto_id || "";
    }).filter(Boolean).join(", ");
  }

  function productosDesdeCsv(value) {
    return String(value || "").split(",").map(function (raw, index) {
      var token = raw.trim();
      if (!token) return null;
      var esNumero = /^[0-9]+$/.test(token);
      return {
        producto_id: esNumero ? parseInt(token, 10) : 0,
        sku: esNumero ? "" : token,
        slug: "",
        orden: (index + 1) * 10
      };
    }).filter(Boolean);
  }

  function refrescarJson() {
    setText("cms_actual_json", JSON.stringify(previewJson(grupoActual()), null, 2));
    setText("cms_actual_estado", "Editando local");
  }

  function setPath(obj, path, value) {
    var partes = String(path || "").split(".");
    var actual = obj;
    for (var i = 0; i < partes.length - 1; i++) {
      if (!actual[partes[i]] || typeof actual[partes[i]] !== "object") actual[partes[i]] = {};
      actual = actual[partes[i]];
    }
    actual[partes[partes.length - 1]] = value;
  }

  function normalizarOrden(items) {
    (items || []).forEach(function (item, index) {
      item.orden = (index + 1) * 10;
    });
  }

  function urlPreviewSeguro(url) {
    url = String(url || "").trim();
    if (!url || /^javascript:/i.test(url)) return "";
    return url.replace(/["'()\\]/g, "");
  }

  function grupoActual() {
    return grupos.filter(function (grupo) { return grupo.codigo === estado.grupo; })[0] || grupos[0];
  }

  function copiarJson() {
    var node = $("cms_actual_json");
    if (!node || !navigator.clipboard) return;
    navigator.clipboard.writeText(node.textContent || "");
    setText("cms_actual_estado", "JSON copiado");
  }

  function on(id, eventName, callback) {
    var node = $(id);
    if (node) node.addEventListener(eventName, callback);
  }

  function $(id) { return document.getElementById(id); }

  function valor(id) {
    var node = $(id);
    return node ? String(node.value || "") : "";
  }

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
})();
