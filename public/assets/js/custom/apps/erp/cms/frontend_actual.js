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
    mediaPicker: { contexto: "", index: 0, campo: "", archivo: null, dataUrl: "", seleccion: "" },
    datos: {
      global: {
        global_negocio: {
          codigo: "global_negocio",
          tipo: "negocio",
          visible: true,
          orden: 10,
          nombre_comercial: "Artiani",
          razon_social: "",
          slogan: "",
          descripcion_corta: "Tienda especializada en productos para acuario y mascotas.",
          logo_principal: "",
          logo_blanco: "",
          favicon: "",
          whatsapp: "",
          telefono: "",
          email_contacto: "",
          email_facturacion: ""
        },
        global_ubicacion: {
          codigo: "global_ubicacion",
          tipo: "direccion_mapa",
          visible: true,
          orden: 20,
          direccion: {
            calle: "",
            colonia: "",
            ciudad: "",
            estado: "",
            codigo_postal: "",
            pais: "Mexico",
            texto_publico: ""
          },
          mapa: {
            google_maps_url: "",
            embed_url: "",
            lat: "",
            lng: ""
          }
        },
        global_horarios: {
          codigo: "global_horarios",
          tipo: "horarios",
          visible: true,
          orden: 30,
          items: [
            { dias: "Lunes a viernes", horario: "10:00 a 19:00", visible: true, orden: 10 },
            { dias: "Sabado", horario: "10:00 a 15:00", visible: true, orden: 20 }
          ]
        },
        global_redes: {
          codigo: "global_redes",
          tipo: "redes_sociales",
          visible: true,
          orden: 40,
          facebook: "",
          instagram: "",
          tiktok: "",
          youtube: ""
        },
        global_seo: {
          codigo: "global_seo",
          tipo: "seo_defaults",
          visible: true,
          orden: 50,
          site_name: "Artiani",
          title_default: "Artiani",
          description_default: "Productos para acuario y mascotas.",
          og_image_default: "",
          robots_default: "index,follow"
        },
        global_navegacion: {
          codigo: "global_navegacion",
          tipo: "navegacion",
          visible: true,
          orden: 60,
          menu_principal: [
            { label: "Inicio", tipo: "ruta", url: "/", orden: 10, visible: true },
            { label: "Categorias", tipo: "categorias", url: "", orden: 20, visible: true },
            { label: "Contacto", tipo: "ruta", url: "/contacto", orden: 30, visible: true }
          ],
          footer_columnas: [
            { titulo: "Ayuda", links: "Como comprar|/como-comprar\nFacturacion|/facturacion", orden: 10, visible: true }
          ]
        }
      },
      navegacion: {
        nav_topbar: {
          codigo: "nav_topbar",
          tipo: "topbar",
          visible: true,
          orden: 10,
          texto: "Atencion personalizada por WhatsApp",
          telefono_label: "",
          whatsapp_label: "WhatsApp",
          whatsapp_url: "",
          mostrar_redes: true
        },
        nav_menu_principal: {
          codigo: "nav_menu_principal",
          tipo: "menu_principal",
          visible: true,
          orden: 20,
          items: [
            { label: "Inicio", tipo: "ruta", url: "/", orden: 10, visible: true },
            { label: "Categorias", tipo: "categorias", url: "", orden: 20, visible: true },
            { label: "Productos", tipo: "ruta", url: "/#productos", orden: 30, visible: true },
            { label: "Contacto", tipo: "ruta", url: "/contacto", orden: 40, visible: true }
          ]
        },
        nav_footer_columnas: {
          codigo: "nav_footer_columnas",
          tipo: "footer_columnas",
          visible: true,
          orden: 30,
          items: [
            {
              titulo: "Ayuda",
              links: [
                { label: "Como comprar", url: "/como-comprar", visible: true, orden: 10 },
                { label: "Facturacion", url: "/facturacion", visible: true, orden: 20 }
              ],
              orden: 10,
              visible: true
            },
            {
              titulo: "Tienda",
              links: [
                { label: "Categorias", url: "/categorias", visible: true, orden: 10 },
                { label: "Politicas", url: "/politicas", visible: true, orden: 20 }
              ],
              orden: 20,
              visible: true
            }
          ]
        },
        nav_footer_cta: {
          codigo: "nav_footer_cta",
          tipo: "footer_cta",
          visible: true,
          orden: 40,
          titulo: "Necesitas ayuda para elegir?",
          texto: "Te orientamos por WhatsApp para encontrar el producto correcto.",
          cta: { label: "Escribir por WhatsApp", url: "" }
        }
      },
      categorias: {
        categorias_config: {
          codigo: "categorias_config",
          tipo: "categorias_config",
          visible: true,
          orden: 10,
          titulo_listado: "Categorias",
          subtitulo_listado: "Explora productos por mascota o necesidad.",
          mostrar_en_home: true,
          mostrar_en_menu: true,
          fuente: "/ecommercePublico/categorias"
        },
        categorias_items: {
          codigo: "categorias_items",
          tipo: "categoria_editorial",
          visible: true,
          orden: 20,
          items: [
            {
              categoria_id: 0,
              slug: "peces",
              titulo: "Peces",
              subtitulo: "Acuarios, alimento y mantenimiento",
              descripcion_seo: "",
              imagen_card: "",
              imagen_banner: "",
              alt_card: "Categoria de peces",
              alt_banner: "Banner de categoria peces",
              destacado: true,
              visible: true,
              orden: 10,
              url: "/categoria/peces"
            },
            {
              categoria_id: 0,
              slug: "perros",
              titulo: "Perros",
              subtitulo: "Alimento, accesorios y cuidado diario",
              descripcion_seo: "",
              imagen_card: "",
              imagen_banner: "",
              alt_card: "Categoria de perros",
              alt_banner: "Banner de categoria perros",
              destacado: true,
              visible: true,
              orden: 20,
              url: "/categoria/perros"
            }
          ]
        }
      },
      marcas: {
        marcas_config: {
          codigo: "marcas_config",
          tipo: "marcas_config",
          visible: true,
          orden: 10,
          titulo_listado: "Marcas",
          subtitulo_listado: "Explora productos por marca.",
          mostrar_en_home: false,
          mostrar_en_menu: true,
          fuente: "/ecommercePublico/marcas"
        },
        marcas_items: {
          codigo: "marcas_items",
          tipo: "marca_editorial",
          visible: true,
          orden: 20,
          items: [
            {
              marca_id: 0,
              slug: "marca-destacada",
              titulo: "Marca destacada",
              subtitulo: "Productos seleccionados de esta marca.",
              descripcion_seo: "",
              logo: "",
              imagen_banner: "",
              alt_logo: "Logo de marca destacada",
              alt_banner: "Banner de marca destacada",
              destacado: true,
              visible: true,
              orden: 10,
              url: "/marca/marca-destacada"
            }
          ]
        }
      },
      paginas: {
        paginas_config: {
          codigo: "paginas_config",
          tipo: "paginas_config",
          visible: true,
          orden: 10,
          titulo_listado: "Ayuda",
          subtitulo_listado: "Informacion util para comprar en Artiani.",
          fuente: "/ecommercePublico/paginas"
        },
        paginas_items: {
          codigo: "paginas_items",
          tipo: "pagina_estatica",
          visible: true,
          orden: 20,
          items: [
            {
              slug: "como-comprar",
              titulo: "Como comprar",
              subtitulo: "Guia rapida para encontrar productos y solicitar atencion.",
              resumen: "Conoce el proceso para buscar productos, agregarlos al carrito y enviar tu solicitud.",
              contenido: "Busca tus productos, agregalos al carrito y envia tu solicitud por WhatsApp para confirmar disponibilidad y entrega.",
              imagen_principal: "",
              alt_imagen: "Pagina de ayuda para comprar en Artiani",
              seo_title: "Como comprar en Artiani",
              seo_description: "Guia para comprar productos de acuario y mascotas en Artiani.",
              visible: true,
              orden: 10,
              url: "/como-comprar"
            },
            {
              slug: "facturacion",
              titulo: "Facturacion",
              subtitulo: "Informacion para solicitar factura.",
              resumen: "Prepara tus datos fiscales y solicita apoyo al equipo Artiani.",
              contenido: "Para facturar tu compra, comparte tus datos fiscales y comprobante dentro del periodo correspondiente.",
              imagen_principal: "",
              alt_imagen: "Pagina de facturacion Artiani",
              seo_title: "Facturacion Artiani",
              seo_description: "Informacion para solicitar facturacion de compras en Artiani.",
              visible: true,
              orden: 20,
              url: "/facturacion"
            }
          ]
        }
      },
      politicas: {
        politicas_config: {
          codigo: "politicas_config",
          tipo: "politicas_config",
          visible: true,
          orden: 10,
          titulo_listado: "Politicas",
          subtitulo_listado: "Consulta las politicas de compra, envio, devoluciones y privacidad.",
          fuente: "/ecommercePublico/politicas"
        },
        politicas_items: {
          codigo: "politicas_items",
          tipo: "politica_publica",
          visible: true,
          orden: 20,
          items: [
            {
              slug: "privacidad",
              titulo: "Aviso de privacidad",
              resumen: "Como tratamos tus datos personales.",
              contenido: "Texto pendiente de revision legal.",
              version: "1.0",
              estatus: "borrador",
              vigente_desde: "",
              vigente_hasta: "",
              seo_title: "Aviso de privacidad Artiani",
              seo_description: "Aviso de privacidad para clientes de Artiani.",
              visible: true,
              orden: 10,
              url: "/politicas/privacidad"
            },
            {
              slug: "envios",
              titulo: "Politica de envios",
              resumen: "Condiciones generales de envio y entrega.",
              contenido: "Texto pendiente de definicion operativa.",
              version: "1.0",
              estatus: "borrador",
              vigente_desde: "",
              vigente_hasta: "",
              seo_title: "Politica de envios Artiani",
              seo_description: "Informacion sobre envios y entregas de Artiani.",
              visible: true,
              orden: 20,
              url: "/politicas/envios"
            },
            {
              slug: "devoluciones",
              titulo: "Politica de devoluciones",
              resumen: "Condiciones para cambios y devoluciones.",
              contenido: "Texto pendiente de definicion operativa.",
              version: "1.0",
              estatus: "borrador",
              vigente_desde: "",
              vigente_hasta: "",
              seo_title: "Politica de devoluciones Artiani",
              seo_description: "Informacion sobre cambios y devoluciones en Artiani.",
              visible: true,
              orden: 30,
              url: "/politicas/devoluciones"
            }
          ]
        }
      },
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
        seccion("global_negocio", "negocio", "Marca, logos y datos de contacto.", ["nombre_comercial", "logo_principal", "whatsapp", "email_contacto"]),
        seccion("global_ubicacion", "direccion_mapa", "Direccion publica y mapa.", ["direccion", "google_maps_url", "embed_url"]),
        seccion("global_horarios", "horarios", "Horarios visibles por dia o grupo de dias.", ["dias", "horario", "visible"]),
        seccion("global_redes", "redes_sociales", "Redes sociales publicas.", ["facebook", "instagram", "tiktok", "youtube"]),
        seccion("global_seo", "seo_defaults", "SEO global del sitio.", ["site_name", "title_default", "description_default", "og_image_default"]),
        seccion("global_navegacion", "navegacion", "Menu principal y columnas de footer.", ["menu_principal", "footer_columnas"])
      ]
    },
    {
      codigo: "navegacion",
      titulo: "Navegacion",
      subtitulo: "Topbar, menu principal, footer y CTAs globales.",
      endpoint: "GET /ecommercePublico/configuracion_inicial",
      prioridad: 2,
      secciones: [
        seccion("nav_topbar", "topbar", "Franja superior del sitio.", ["texto", "whatsapp", "redes"]),
        seccion("nav_menu_principal", "menu_principal", "Enlaces principales del header.", ["label", "tipo", "url", "visible"]),
        seccion("nav_footer_columnas", "footer_columnas", "Columnas y links visibles en footer.", ["titulo", "links"]),
        seccion("nav_footer_cta", "footer_cta", "Llamado a la accion del footer.", ["titulo", "texto", "cta"])
      ]
    },
    {
      codigo: "categorias",
      titulo: "Categorias",
      subtitulo: "Imagenes, banners, SEO, orden y destacados de categorias publicas.",
      endpoint: "GET /ecommercePublico/categorias",
      prioridad: 3,
      secciones: [
        seccion("categorias_config", "categorias_config", "Configuracion general de categorias.", ["titulo", "subtitulo", "fuente"]),
        seccion("categorias_items", "categoria_editorial", "Capa editorial por categoria.", ["slug", "imagen_card", "imagen_banner", "seo", "destacado"])
      ]
    },
    {
      codigo: "marcas",
      titulo: "Marcas",
      subtitulo: "Logos, banners, SEO, orden y destacados de marcas publicas.",
      endpoint: "GET /ecommercePublico/marcas",
      prioridad: 4,
      secciones: [
        seccion("marcas_config", "marcas_config", "Configuracion general de marcas.", ["titulo", "subtitulo", "fuente"]),
        seccion("marcas_items", "marca_editorial", "Capa editorial por marca.", ["slug", "logo", "imagen_banner", "seo", "destacado"])
      ]
    },
    {
      codigo: "paginas",
      titulo: "Paginas",
      subtitulo: "Paginas informativas del ecommerce: ayuda, contacto, facturacion y contenido editorial.",
      endpoint: "GET /ecommercePublico/paginas",
      prioridad: 5,
      secciones: [
        seccion("paginas_config", "paginas_config", "Configuracion general de paginas.", ["titulo", "subtitulo", "fuente"]),
        seccion("paginas_items", "pagina_estatica", "Paginas publicas editables.", ["slug", "titulo", "contenido", "seo", "imagen"])
      ]
    },
    {
      codigo: "politicas",
      titulo: "Politicas",
      subtitulo: "Privacidad, envios, devoluciones, terminos y avisos publicos.",
      endpoint: "GET /ecommercePublico/politicas",
      prioridad: 6,
      secciones: [
        seccion("politicas_config", "politicas_config", "Configuracion general de politicas.", ["titulo", "subtitulo", "fuente"]),
        seccion("politicas_items", "politica_publica", "Politicas publicas editables.", ["slug", "titulo", "contenido", "estatus", "vigencia"])
      ]
    },
    {
      codigo: "home",
      titulo: "Home",
      subtitulo: "Secciones principales de la portada publica.",
      endpoint: "GET /ecommercePublico/cms_frontend?pagina=home",
      prioridad: 7,
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
    if (item.codigo.indexOf("politicas_") === 0) {
      return renderPoliticasCmsSeccion(item);
    }
    if (item.codigo.indexOf("paginas_") === 0) {
      return renderPaginasCmsSeccion(item);
    }
    if (item.codigo.indexOf("marcas_") === 0) {
      return renderMarcasCmsSeccion(item);
    }
    if (item.codigo.indexOf("categorias_") === 0) {
      return renderCategoriasCmsSeccion(item);
    }
    if (item.codigo.indexOf("nav_") === 0) {
      return renderNavegacionSeccion(item);
    }
    if (item.codigo.indexOf("global_") === 0) {
      return renderGlobalSeccion(item);
    }
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
    Array.prototype.forEach.call(document.querySelectorAll("[data-global-field]"), function (node) {
      node.addEventListener("input", function () {
        actualizarGlobalField(node.getAttribute("data-global-section"), node.getAttribute("data-global-field"), node.value);
      });
      node.addEventListener("change", function () {
        actualizarGlobalField(node.getAttribute("data-global-section"), node.getAttribute("data-global-field"), node.value);
      });
    });
    Array.prototype.forEach.call(document.querySelectorAll("[data-nav-field]"), function (node) {
      node.addEventListener("input", function () {
        actualizarNavegacionField(node.getAttribute("data-nav-section"), node.getAttribute("data-nav-field"), node.value);
      });
      node.addEventListener("change", function () {
        actualizarNavegacionField(node.getAttribute("data-nav-section"), node.getAttribute("data-nav-field"), node.value);
      });
    });
    Array.prototype.forEach.call(document.querySelectorAll("[data-nav-action]"), function (button) {
      button.addEventListener("click", function () {
        ejecutarNavegacionAccion(
          button.getAttribute("data-nav-section") || "",
          button.getAttribute("data-nav-action") || "",
          parseInt(button.getAttribute("data-index") || "0", 10),
          parseInt(button.getAttribute("data-link-index") || "-1", 10)
        );
      });
    });
    on("cms_actual_nav_menu_agregar", "click", agregarMenuPrincipalItem);
    on("cms_actual_nav_footer_columna_agregar", "click", agregarFooterColumna);
    Array.prototype.forEach.call(document.querySelectorAll("[data-cms-cat-field]"), function (node) {
      node.addEventListener("input", function () {
        actualizarCategoriaCmsField(node.getAttribute("data-cms-cat-section"), node.getAttribute("data-cms-cat-field"), node.value);
      });
      node.addEventListener("change", function () {
        actualizarCategoriaCmsField(node.getAttribute("data-cms-cat-section"), node.getAttribute("data-cms-cat-field"), node.value);
      });
    });
    Array.prototype.forEach.call(document.querySelectorAll("[data-cms-cat-action]"), function (button) {
      button.addEventListener("click", function () {
        ejecutarCategoriaCmsAccion(button.getAttribute("data-cms-cat-action") || "", parseInt(button.getAttribute("data-index") || "0", 10));
      });
    });
    on("cms_actual_categoria_agregar", "click", agregarCategoriaCmsItem);
    Array.prototype.forEach.call(document.querySelectorAll("[data-cms-marca-field]"), function (node) {
      node.addEventListener("input", function () {
        actualizarMarcaCmsField(node.getAttribute("data-cms-marca-section"), node.getAttribute("data-cms-marca-field"), node.value);
      });
      node.addEventListener("change", function () {
        actualizarMarcaCmsField(node.getAttribute("data-cms-marca-section"), node.getAttribute("data-cms-marca-field"), node.value);
      });
    });
    Array.prototype.forEach.call(document.querySelectorAll("[data-cms-marca-action]"), function (button) {
      button.addEventListener("click", function () {
        ejecutarMarcaCmsAccion(button.getAttribute("data-cms-marca-action") || "", parseInt(button.getAttribute("data-index") || "0", 10));
      });
    });
    on("cms_actual_marca_agregar", "click", agregarMarcaCmsItem);
    Array.prototype.forEach.call(document.querySelectorAll("[data-cms-pagina-field]"), function (node) {
      node.addEventListener("input", function () {
        actualizarPaginaCmsField(node.getAttribute("data-cms-pagina-section"), node.getAttribute("data-cms-pagina-field"), node.value);
      });
      node.addEventListener("change", function () {
        actualizarPaginaCmsField(node.getAttribute("data-cms-pagina-section"), node.getAttribute("data-cms-pagina-field"), node.value);
      });
    });
    Array.prototype.forEach.call(document.querySelectorAll("[data-cms-pagina-action]"), function (button) {
      button.addEventListener("click", function () {
        ejecutarPaginaCmsAccion(button.getAttribute("data-cms-pagina-action") || "", parseInt(button.getAttribute("data-index") || "0", 10));
      });
    });
    on("cms_actual_pagina_agregar", "click", agregarPaginaCmsItem);
    Array.prototype.forEach.call(document.querySelectorAll("[data-cms-politica-field]"), function (node) {
      node.addEventListener("input", function () {
        actualizarPoliticaCmsField(node.getAttribute("data-cms-politica-section"), node.getAttribute("data-cms-politica-field"), node.value);
      });
      node.addEventListener("change", function () {
        actualizarPoliticaCmsField(node.getAttribute("data-cms-politica-section"), node.getAttribute("data-cms-politica-field"), node.value);
      });
    });
    Array.prototype.forEach.call(document.querySelectorAll("[data-cms-politica-action]"), function (button) {
      button.addEventListener("click", function () {
        ejecutarPoliticaCmsAccion(button.getAttribute("data-cms-politica-action") || "", parseInt(button.getAttribute("data-index") || "0", 10));
      });
    });
    on("cms_actual_politica_agregar", "click", agregarPoliticaCmsItem);
    Array.prototype.forEach.call(document.querySelectorAll("[data-media-picker]"), function (button) {
      button.addEventListener("click", function () {
        var rawIndex = button.getAttribute("data-index") || "0";
        abrirSelectorMedia(
          button.getAttribute("data-media-picker"),
          /^[0-9]+$/.test(rawIndex) ? parseInt(rawIndex, 10) : rawIndex,
          button.getAttribute("data-field") || ""
        );
      });
    });
  }

  function renderCategoriasCmsSeccion(item) {
    var data = categoriasCmsData(item.codigo);
    if (!data) return "";
    var contenido = item.codigo === "categorias_config" ? renderCategoriasCmsConfig(data) : renderCategoriasCmsItems(data);
    return '<div class="cms-actual-card mb-4">' +
      '<div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-4">' +
        '<div><div class="fw-bold">' + escapeHtml(item.descripcion) + '</div><div class="text-muted fs-8">' + escapeHtml(item.codigo) + '</div></div>' +
        '<span class="badge badge-light-success">Editor activo</span>' +
      '</div>' +
      contenido +
    '</div>';
  }

  function renderCategoriasCmsConfig(data) {
    return '<div class="row g-3">' +
      inputCategoriaCms("categorias_config", "titulo_listado", "Titulo listado", data.titulo_listado, "col-md-4") +
      inputCategoriaCms("categorias_config", "subtitulo_listado", "Subtitulo listado", data.subtitulo_listado, "col-md-5") +
      inputCategoriaCms("categorias_config", "fuente", "Fuente API", data.fuente, "col-md-3") +
      '<div class="col-md-3"><label class="form-label fs-8 fw-bold">Mostrar en Home</label><select class="form-select form-select-sm" data-cms-cat-section="categorias_config" data-cms-cat-field="mostrar_en_home"><option value="1"' + (data.mostrar_en_home ? ' selected' : '') + '>Si</option><option value="0"' + (!data.mostrar_en_home ? ' selected' : '') + '>No</option></select></div>' +
      '<div class="col-md-3"><label class="form-label fs-8 fw-bold">Mostrar en menu</label><select class="form-select form-select-sm" data-cms-cat-section="categorias_config" data-cms-cat-field="mostrar_en_menu"><option value="1"' + (data.mostrar_en_menu ? ' selected' : '') + '>Si</option><option value="0"' + (!data.mostrar_en_menu ? ' selected' : '') + '>No</option></select></div>' +
    '</div>';
  }

  function renderCategoriasCmsItems(data) {
    var items = data.items || [];
    return '<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">' +
      '<div class="fw-bold">Categorias editoriales</div><button class="btn btn-sm btn-light-primary" type="button" id="cms_actual_categoria_agregar"><i class="bi bi-plus-circle"></i> Agregar categoria</button>' +
    '</div>' +
    items.map(renderCategoriaCmsCard).join("") +
    '<div class="alert alert-light-info fs-7 mb-0">La categoria real debe existir en ERP/API. Aqui solo se prepara imagen, texto publico, SEO, destacado, visible y orden.</div>';
  }

  function renderCategoriaCmsCard(item, index) {
    var bg = item.imagen_banner || item.imagen_card;
    return '<div class="cms-actual-slide mb-4">' +
      '<div class="d-flex justify-content-between align-items-center gap-2 mb-3">' +
        '<div><div class="fw-semibold">' + escapeHtml(item.titulo || ("Categoria " + (index + 1))) + '</div><div class="text-muted fs-8">' + escapeHtml(item.slug || "sin-slug") + '</div></div>' +
        '<div class="d-flex gap-2">' +
          '<button class="btn btn-sm btn-light" type="button" data-cms-cat-action="subir" data-index="' + escapeAttr(index) + '"><i class="bi bi-arrow-up"></i></button>' +
          '<button class="btn btn-sm btn-light" type="button" data-cms-cat-action="bajar" data-index="' + escapeAttr(index) + '"><i class="bi bi-arrow-down"></i></button>' +
          '<button class="btn btn-sm btn-light-warning" type="button" data-cms-cat-action="toggle" data-index="' + escapeAttr(index) + '"><i class="bi bi-eye"></i></button>' +
          '<button class="btn btn-sm btn-light-danger" type="button" data-cms-cat-action="eliminar" data-index="' + escapeAttr(index) + '"><i class="bi bi-trash"></i></button>' +
        '</div>' +
      '</div>' +
      '<div class="row g-4">' +
        '<div class="col-lg-4">' +
          '<div class="cms-actual-slide-preview"' + (bg ? ' style="background-image:url(' + escapeAttr(urlPreviewSeguro(bg)) + ')"' : '') + '><div><div class="text-uppercase fs-8 fw-bold">Preview categoria</div><h4 class="text-white fw-bold mt-2">' + escapeHtml(item.titulo || "") + '</h4><div class="fs-7">' + escapeHtml(item.subtitulo || "") + '</div></div></div>' +
        '</div>' +
        '<div class="col-lg-8"><div class="row g-3">' +
          inputCategoriaItem(index, "categoria_id", "ID ERP", item.categoria_id, "col-md-2") +
          inputCategoriaItem(index, "slug", "Slug", item.slug, "col-md-3") +
          inputCategoriaItem(index, "titulo", "Titulo", item.titulo, "col-md-3") +
          inputCategoriaItem(index, "url", "URL publica", item.url, "col-md-4") +
          inputCategoriaItem(index, "subtitulo", "Subtitulo", item.subtitulo, "col-md-6") +
          inputCategoriaItem(index, "descripcion_seo", "Descripcion SEO", item.descripcion_seo, "col-md-6") +
          inputCategoriaItem(index, "imagen_card", "Imagen card", item.imagen_card, "col-md-6", true) +
          inputCategoriaItem(index, "imagen_banner", "Imagen banner", item.imagen_banner, "col-md-6", true) +
          inputCategoriaItem(index, "alt_card", "Alt card", item.alt_card, "col-md-6") +
          inputCategoriaItem(index, "alt_banner", "Alt banner", item.alt_banner, "col-md-6") +
          '<div class="col-md-3"><label class="form-label fs-8 fw-bold">Destacado</label><select class="form-select form-select-sm" data-cms-cat-section="categorias_items" data-cms-cat-field="items.' + escapeAttr(index) + '.destacado"><option value="1"' + (item.destacado ? ' selected' : '') + '>Si</option><option value="0"' + (!item.destacado ? ' selected' : '') + '>No</option></select></div>' +
          '<div class="col-md-3"><label class="form-label fs-8 fw-bold">Visible</label><select class="form-select form-select-sm" data-cms-cat-section="categorias_items" data-cms-cat-field="items.' + escapeAttr(index) + '.visible"><option value="1"' + (item.visible ? ' selected' : '') + '>Si</option><option value="0"' + (!item.visible ? ' selected' : '') + '>No</option></select></div>' +
        '</div></div>' +
      '</div>' +
    '</div>';
  }

  function inputCategoriaCms(seccionCodigo, campo, label, value, col) {
    return '<div class="' + escapeAttr(col || "col-md-6") + '"><label class="form-label fs-8 fw-bold">' + escapeHtml(label) + '</label><input class="form-control form-control-sm" data-cms-cat-section="' + escapeAttr(seccionCodigo) + '" data-cms-cat-field="' + escapeAttr(campo) + '" value="' + escapeAttr(value == null ? "" : value) + '"></div>';
  }

  function inputCategoriaItem(index, campo, label, value, col, media) {
    var input = '<input class="form-control form-control-sm" data-cms-cat-section="categorias_items" data-cms-cat-field="items.' + escapeAttr(index) + '.' + escapeAttr(campo) + '" value="' + escapeAttr(value == null ? "" : value) + '">';
    if (media) {
      input = '<div class="input-group input-group-sm">' + input + '<button class="btn btn-light-primary" type="button" data-media-picker="cms_categoria" data-index="' + escapeAttr(index) + '" data-field="' + escapeAttr(campo) + '"><i class="bi bi-images"></i> Media</button></div>';
    }
    return '<div class="' + escapeAttr(col || "col-md-4") + '"><label class="form-label fs-8 fw-bold">' + escapeHtml(label) + '</label>' + input + '</div>';
  }

  function renderMarcasCmsSeccion(item) {
    var data = marcasCmsData(item.codigo);
    if (!data) return "";
    var contenido = item.codigo === "marcas_config" ? renderMarcasCmsConfig(data) : renderMarcasCmsItems(data);
    return '<div class="cms-actual-card mb-4">' +
      '<div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-4">' +
        '<div><div class="fw-bold">' + escapeHtml(item.descripcion) + '</div><div class="text-muted fs-8">' + escapeHtml(item.codigo) + '</div></div>' +
        '<span class="badge badge-light-success">Editor activo</span>' +
      '</div>' +
      contenido +
    '</div>';
  }

  function renderMarcasCmsConfig(data) {
    return '<div class="row g-3">' +
      inputMarcaCms("marcas_config", "titulo_listado", "Titulo listado", data.titulo_listado, "col-md-4") +
      inputMarcaCms("marcas_config", "subtitulo_listado", "Subtitulo listado", data.subtitulo_listado, "col-md-5") +
      inputMarcaCms("marcas_config", "fuente", "Fuente API", data.fuente, "col-md-3") +
      '<div class="col-md-3"><label class="form-label fs-8 fw-bold">Mostrar en Home</label><select class="form-select form-select-sm" data-cms-marca-section="marcas_config" data-cms-marca-field="mostrar_en_home"><option value="1"' + (data.mostrar_en_home ? ' selected' : '') + '>Si</option><option value="0"' + (!data.mostrar_en_home ? ' selected' : '') + '>No</option></select></div>' +
      '<div class="col-md-3"><label class="form-label fs-8 fw-bold">Mostrar en menu</label><select class="form-select form-select-sm" data-cms-marca-section="marcas_config" data-cms-marca-field="mostrar_en_menu"><option value="1"' + (data.mostrar_en_menu ? ' selected' : '') + '>Si</option><option value="0"' + (!data.mostrar_en_menu ? ' selected' : '') + '>No</option></select></div>' +
    '</div>';
  }

  function renderMarcasCmsItems(data) {
    var items = data.items || [];
    return '<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">' +
      '<div class="fw-bold">Marcas editoriales</div><button class="btn btn-sm btn-light-primary" type="button" id="cms_actual_marca_agregar"><i class="bi bi-plus-circle"></i> Agregar marca</button>' +
    '</div>' +
    items.map(renderMarcaCmsCard).join("") +
    '<div class="alert alert-light-info fs-7 mb-0">La marca real debe existir en ERP/API. Aqui solo se prepara logo, banner, texto publico, SEO, destacado, visible y orden.</div>';
  }

  function renderMarcaCmsCard(item, index) {
    var bg = item.imagen_banner || item.logo;
    return '<div class="cms-actual-slide mb-4">' +
      '<div class="d-flex justify-content-between align-items-center gap-2 mb-3">' +
        '<div><div class="fw-semibold">' + escapeHtml(item.titulo || ("Marca " + (index + 1))) + '</div><div class="text-muted fs-8">' + escapeHtml(item.slug || "sin-slug") + '</div></div>' +
        '<div class="d-flex gap-2">' +
          '<button class="btn btn-sm btn-light" type="button" data-cms-marca-action="subir" data-index="' + escapeAttr(index) + '"><i class="bi bi-arrow-up"></i></button>' +
          '<button class="btn btn-sm btn-light" type="button" data-cms-marca-action="bajar" data-index="' + escapeAttr(index) + '"><i class="bi bi-arrow-down"></i></button>' +
          '<button class="btn btn-sm btn-light-warning" type="button" data-cms-marca-action="toggle" data-index="' + escapeAttr(index) + '"><i class="bi bi-eye"></i></button>' +
          '<button class="btn btn-sm btn-light-danger" type="button" data-cms-marca-action="eliminar" data-index="' + escapeAttr(index) + '"><i class="bi bi-trash"></i></button>' +
        '</div>' +
      '</div>' +
      '<div class="row g-4">' +
        '<div class="col-lg-4">' +
          '<div class="cms-actual-slide-preview"' + (bg ? ' style="background-image:url(' + escapeAttr(urlPreviewSeguro(bg)) + ')"' : '') + '><div><div class="text-uppercase fs-8 fw-bold">Preview marca</div><h4 class="text-white fw-bold mt-2">' + escapeHtml(item.titulo || "") + '</h4><div class="fs-7">' + escapeHtml(item.subtitulo || "") + '</div></div></div>' +
        '</div>' +
        '<div class="col-lg-8"><div class="row g-3">' +
          inputMarcaItem(index, "marca_id", "ID ERP", item.marca_id, "col-md-2") +
          inputMarcaItem(index, "slug", "Slug", item.slug, "col-md-3") +
          inputMarcaItem(index, "titulo", "Titulo", item.titulo, "col-md-3") +
          inputMarcaItem(index, "url", "URL publica", item.url, "col-md-4") +
          inputMarcaItem(index, "subtitulo", "Subtitulo", item.subtitulo, "col-md-6") +
          inputMarcaItem(index, "descripcion_seo", "Descripcion SEO", item.descripcion_seo, "col-md-6") +
          inputMarcaItem(index, "logo", "Logo", item.logo, "col-md-6", true) +
          inputMarcaItem(index, "imagen_banner", "Imagen banner", item.imagen_banner, "col-md-6", true) +
          inputMarcaItem(index, "alt_logo", "Alt logo", item.alt_logo, "col-md-6") +
          inputMarcaItem(index, "alt_banner", "Alt banner", item.alt_banner, "col-md-6") +
          '<div class="col-md-3"><label class="form-label fs-8 fw-bold">Destacado</label><select class="form-select form-select-sm" data-cms-marca-section="marcas_items" data-cms-marca-field="items.' + escapeAttr(index) + '.destacado"><option value="1"' + (item.destacado ? ' selected' : '') + '>Si</option><option value="0"' + (!item.destacado ? ' selected' : '') + '>No</option></select></div>' +
          '<div class="col-md-3"><label class="form-label fs-8 fw-bold">Visible</label><select class="form-select form-select-sm" data-cms-marca-section="marcas_items" data-cms-marca-field="items.' + escapeAttr(index) + '.visible"><option value="1"' + (item.visible ? ' selected' : '') + '>Si</option><option value="0"' + (!item.visible ? ' selected' : '') + '>No</option></select></div>' +
        '</div></div>' +
      '</div>' +
    '</div>';
  }

  function inputMarcaCms(seccionCodigo, campo, label, value, col) {
    return '<div class="' + escapeAttr(col || "col-md-6") + '"><label class="form-label fs-8 fw-bold">' + escapeHtml(label) + '</label><input class="form-control form-control-sm" data-cms-marca-section="' + escapeAttr(seccionCodigo) + '" data-cms-marca-field="' + escapeAttr(campo) + '" value="' + escapeAttr(value == null ? "" : value) + '"></div>';
  }

  function inputMarcaItem(index, campo, label, value, col, media) {
    var input = '<input class="form-control form-control-sm" data-cms-marca-section="marcas_items" data-cms-marca-field="items.' + escapeAttr(index) + '.' + escapeAttr(campo) + '" value="' + escapeAttr(value == null ? "" : value) + '">';
    if (media) {
      input = '<div class="input-group input-group-sm">' + input + '<button class="btn btn-light-primary" type="button" data-media-picker="cms_marca" data-index="' + escapeAttr(index) + '" data-field="' + escapeAttr(campo) + '"><i class="bi bi-images"></i> Media</button></div>';
    }
    return '<div class="' + escapeAttr(col || "col-md-4") + '"><label class="form-label fs-8 fw-bold">' + escapeHtml(label) + '</label>' + input + '</div>';
  }

  function renderPaginasCmsSeccion(item) {
    var data = paginasCmsData(item.codigo);
    if (!data) return "";
    var contenido = item.codigo === "paginas_config" ? renderPaginasCmsConfig(data) : renderPaginasCmsItems(data);
    return '<div class="cms-actual-card mb-4">' +
      '<div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-4">' +
        '<div><div class="fw-bold">' + escapeHtml(item.descripcion) + '</div><div class="text-muted fs-8">' + escapeHtml(item.codigo) + '</div></div>' +
        '<span class="badge badge-light-success">Editor activo</span>' +
      '</div>' +
      contenido +
    '</div>';
  }

  function renderPaginasCmsConfig(data) {
    return '<div class="row g-3">' +
      inputPaginaCms("paginas_config", "titulo_listado", "Titulo listado", data.titulo_listado, "col-md-4") +
      inputPaginaCms("paginas_config", "subtitulo_listado", "Subtitulo listado", data.subtitulo_listado, "col-md-5") +
      inputPaginaCms("paginas_config", "fuente", "Fuente API", data.fuente, "col-md-3") +
    '</div>';
  }

  function renderPaginasCmsItems(data) {
    var items = data.items || [];
    return '<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">' +
      '<div class="fw-bold">Paginas editoriales</div><button class="btn btn-sm btn-light-primary" type="button" id="cms_actual_pagina_agregar"><i class="bi bi-plus-circle"></i> Agregar pagina</button>' +
    '</div>' +
    items.map(renderPaginaCmsCard).join("") +
    '<div class="alert alert-light-info fs-7 mb-0">Usa contenido limpio y rutas publicas. La sanitizacion estricta final se hara en backend antes de publicar.</div>';
  }

  function renderPaginaCmsCard(item, index) {
    return '<div class="cms-actual-slide mb-4">' +
      '<div class="d-flex justify-content-between align-items-center gap-2 mb-3">' +
        '<div><div class="fw-semibold">' + escapeHtml(item.titulo || ("Pagina " + (index + 1))) + '</div><div class="text-muted fs-8">' + escapeHtml(item.url || "sin-url") + '</div></div>' +
        '<div class="d-flex gap-2">' +
          '<button class="btn btn-sm btn-light" type="button" data-cms-pagina-action="subir" data-index="' + escapeAttr(index) + '"><i class="bi bi-arrow-up"></i></button>' +
          '<button class="btn btn-sm btn-light" type="button" data-cms-pagina-action="bajar" data-index="' + escapeAttr(index) + '"><i class="bi bi-arrow-down"></i></button>' +
          '<button class="btn btn-sm btn-light-warning" type="button" data-cms-pagina-action="toggle" data-index="' + escapeAttr(index) + '"><i class="bi bi-eye"></i></button>' +
          '<button class="btn btn-sm btn-light-danger" type="button" data-cms-pagina-action="eliminar" data-index="' + escapeAttr(index) + '"><i class="bi bi-trash"></i></button>' +
        '</div>' +
      '</div>' +
      '<div class="row g-4">' +
        '<div class="col-lg-4">' +
          '<div class="cms-actual-slide-preview"' + (item.imagen_principal ? ' style="background-image:url(' + escapeAttr(urlPreviewSeguro(item.imagen_principal)) + ')"' : '') + '><div><div class="text-uppercase fs-8 fw-bold">Preview pagina</div><h4 class="text-white fw-bold mt-2">' + escapeHtml(item.titulo || "") + '</h4><div class="fs-7">' + escapeHtml(item.subtitulo || "") + '</div></div></div>' +
        '</div>' +
        '<div class="col-lg-8"><div class="row g-3">' +
          inputPaginaItem(index, "slug", "Slug", item.slug, "col-md-3") +
          inputPaginaItem(index, "titulo", "Titulo", item.titulo, "col-md-4") +
          inputPaginaItem(index, "url", "URL publica", item.url, "col-md-5") +
          inputPaginaItem(index, "subtitulo", "Subtitulo", item.subtitulo, "col-md-6") +
          inputPaginaItem(index, "resumen", "Resumen", item.resumen, "col-md-6") +
          inputPaginaItem(index, "imagen_principal", "Imagen principal", item.imagen_principal, "col-md-6", true) +
          inputPaginaItem(index, "alt_imagen", "Alt imagen", item.alt_imagen, "col-md-6") +
          inputPaginaItem(index, "seo_title", "SEO title", item.seo_title, "col-md-6") +
          inputPaginaItem(index, "seo_description", "SEO description", item.seo_description, "col-md-6") +
          '<div class="col-12"><label class="form-label fs-8 fw-bold">Contenido</label><textarea class="form-control form-control-sm" rows="4" data-cms-pagina-section="paginas_items" data-cms-pagina-field="items.' + escapeAttr(index) + '.contenido">' + escapeHtml(item.contenido || "") + '</textarea></div>' +
          '<div class="col-md-3"><label class="form-label fs-8 fw-bold">Visible</label><select class="form-select form-select-sm" data-cms-pagina-section="paginas_items" data-cms-pagina-field="items.' + escapeAttr(index) + '.visible"><option value="1"' + (item.visible ? ' selected' : '') + '>Si</option><option value="0"' + (!item.visible ? ' selected' : '') + '>No</option></select></div>' +
        '</div></div>' +
      '</div>' +
    '</div>';
  }

  function inputPaginaCms(seccionCodigo, campo, label, value, col) {
    return '<div class="' + escapeAttr(col || "col-md-6") + '"><label class="form-label fs-8 fw-bold">' + escapeHtml(label) + '</label><input class="form-control form-control-sm" data-cms-pagina-section="' + escapeAttr(seccionCodigo) + '" data-cms-pagina-field="' + escapeAttr(campo) + '" value="' + escapeAttr(value == null ? "" : value) + '"></div>';
  }

  function inputPaginaItem(index, campo, label, value, col, media) {
    var input = '<input class="form-control form-control-sm" data-cms-pagina-section="paginas_items" data-cms-pagina-field="items.' + escapeAttr(index) + '.' + escapeAttr(campo) + '" value="' + escapeAttr(value == null ? "" : value) + '">';
    if (media) {
      input = '<div class="input-group input-group-sm">' + input + '<button class="btn btn-light-primary" type="button" data-media-picker="cms_pagina" data-index="' + escapeAttr(index) + '" data-field="' + escapeAttr(campo) + '"><i class="bi bi-images"></i> Media</button></div>';
    }
    return '<div class="' + escapeAttr(col || "col-md-4") + '"><label class="form-label fs-8 fw-bold">' + escapeHtml(label) + '</label>' + input + '</div>';
  }

  function renderPoliticasCmsSeccion(item) {
    var data = politicasCmsData(item.codigo);
    if (!data) return "";
    var contenido = item.codigo === "politicas_config" ? renderPoliticasCmsConfig(data) : renderPoliticasCmsItems(data);
    return '<div class="cms-actual-card mb-4">' +
      '<div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-4">' +
        '<div><div class="fw-bold">' + escapeHtml(item.descripcion) + '</div><div class="text-muted fs-8">' + escapeHtml(item.codigo) + '</div></div>' +
        '<span class="badge badge-light-success">Editor activo</span>' +
      '</div>' +
      contenido +
    '</div>';
  }

  function renderPoliticasCmsConfig(data) {
    return '<div class="row g-3">' +
      inputPoliticaCms("politicas_config", "titulo_listado", "Titulo listado", data.titulo_listado, "col-md-4") +
      inputPoliticaCms("politicas_config", "subtitulo_listado", "Subtitulo listado", data.subtitulo_listado, "col-md-5") +
      inputPoliticaCms("politicas_config", "fuente", "Fuente API", data.fuente, "col-md-3") +
    '</div>';
  }

  function renderPoliticasCmsItems(data) {
    var items = data.items || [];
    return '<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">' +
      '<div class="fw-bold">Politicas publicas</div><button class="btn btn-sm btn-light-primary" type="button" id="cms_actual_politica_agregar"><i class="bi bi-plus-circle"></i> Agregar politica</button>' +
    '</div>' +
    items.map(renderPoliticaCmsCard).join("") +
    '<div class="alert alert-light-warning fs-7 mb-0">Estos textos requieren revision legal/operativa antes de publicarse. El estado inicial recomendado es borrador.</div>';
  }

  function renderPoliticaCmsCard(item, index) {
    return '<div class="cms-actual-slide mb-4">' +
      '<div class="d-flex justify-content-between align-items-center gap-2 mb-3">' +
        '<div><div class="fw-semibold">' + escapeHtml(item.titulo || ("Politica " + (index + 1))) + '</div><div class="text-muted fs-8">' + escapeHtml(item.url || "sin-url") + '</div></div>' +
        '<div class="d-flex gap-2">' +
          '<button class="btn btn-sm btn-light" type="button" data-cms-politica-action="subir" data-index="' + escapeAttr(index) + '"><i class="bi bi-arrow-up"></i></button>' +
          '<button class="btn btn-sm btn-light" type="button" data-cms-politica-action="bajar" data-index="' + escapeAttr(index) + '"><i class="bi bi-arrow-down"></i></button>' +
          '<button class="btn btn-sm btn-light-warning" type="button" data-cms-politica-action="toggle" data-index="' + escapeAttr(index) + '"><i class="bi bi-eye"></i></button>' +
          '<button class="btn btn-sm btn-light-danger" type="button" data-cms-politica-action="eliminar" data-index="' + escapeAttr(index) + '"><i class="bi bi-trash"></i></button>' +
        '</div>' +
      '</div>' +
      '<div class="row g-3">' +
        inputPoliticaItem(index, "slug", "Slug", item.slug, "col-md-3") +
        inputPoliticaItem(index, "titulo", "Titulo", item.titulo, "col-md-4") +
        inputPoliticaItem(index, "url", "URL publica", item.url, "col-md-5") +
        inputPoliticaItem(index, "resumen", "Resumen", item.resumen, "col-md-6") +
        inputPoliticaItem(index, "version", "Version", item.version, "col-md-2") +
        '<div class="col-md-2"><label class="form-label fs-8 fw-bold">Estatus</label><select class="form-select form-select-sm" data-cms-politica-section="politicas_items" data-cms-politica-field="items.' + escapeAttr(index) + '.estatus"><option value="borrador"' + (item.estatus === "borrador" ? ' selected' : '') + '>Borrador</option><option value="publicado"' + (item.estatus === "publicado" ? ' selected' : '') + '>Publicado</option><option value="pausado"' + (item.estatus === "pausado" ? ' selected' : '') + '>Pausado</option></select></div>' +
        '<div class="col-md-2"><label class="form-label fs-8 fw-bold">Visible</label><select class="form-select form-select-sm" data-cms-politica-section="politicas_items" data-cms-politica-field="items.' + escapeAttr(index) + '.visible"><option value="1"' + (item.visible ? ' selected' : '') + '>Si</option><option value="0"' + (!item.visible ? ' selected' : '') + '>No</option></select></div>' +
        inputPoliticaItem(index, "vigente_desde", "Vigente desde", item.vigente_desde, "col-md-3") +
        inputPoliticaItem(index, "vigente_hasta", "Vigente hasta", item.vigente_hasta, "col-md-3") +
        inputPoliticaItem(index, "seo_title", "SEO title", item.seo_title, "col-md-3") +
        inputPoliticaItem(index, "seo_description", "SEO description", item.seo_description, "col-md-3") +
        '<div class="col-12"><label class="form-label fs-8 fw-bold">Contenido</label><textarea class="form-control form-control-sm" rows="5" data-cms-politica-section="politicas_items" data-cms-politica-field="items.' + escapeAttr(index) + '.contenido">' + escapeHtml(item.contenido || "") + '</textarea></div>' +
      '</div>' +
    '</div>';
  }

  function inputPoliticaCms(seccionCodigo, campo, label, value, col) {
    return '<div class="' + escapeAttr(col || "col-md-6") + '"><label class="form-label fs-8 fw-bold">' + escapeHtml(label) + '</label><input class="form-control form-control-sm" data-cms-politica-section="' + escapeAttr(seccionCodigo) + '" data-cms-politica-field="' + escapeAttr(campo) + '" value="' + escapeAttr(value == null ? "" : value) + '"></div>';
  }

  function inputPoliticaItem(index, campo, label, value, col) {
    return '<div class="' + escapeAttr(col || "col-md-4") + '"><label class="form-label fs-8 fw-bold">' + escapeHtml(label) + '</label><input class="form-control form-control-sm" data-cms-politica-section="politicas_items" data-cms-politica-field="items.' + escapeAttr(index) + '.' + escapeAttr(campo) + '" value="' + escapeAttr(value == null ? "" : value) + '"></div>';
  }

  function renderNavegacionSeccion(item) {
    var data = navegacionData(item.codigo);
    if (!data) return "";
    var contenido = "";
    if (item.codigo === "nav_topbar") contenido = renderNavTopbar(data);
    if (item.codigo === "nav_menu_principal") contenido = renderNavMenuPrincipal(data);
    if (item.codigo === "nav_footer_columnas") contenido = renderNavFooterColumnas(data);
    if (item.codigo === "nav_footer_cta") contenido = renderNavFooterCta(data);
    return '<div class="cms-actual-card mb-4">' +
      '<div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-4">' +
        '<div><div class="fw-bold">' + escapeHtml(item.descripcion) + '</div><div class="text-muted fs-8">' + escapeHtml(item.codigo) + '</div></div>' +
        '<span class="badge badge-light-success">Editor activo</span>' +
      '</div>' +
      contenido +
    '</div>';
  }

  function renderNavTopbar(data) {
    return '<div class="row g-3">' +
      inputNav("nav_topbar", "texto", "Texto topbar", data.texto, "col-md-5") +
      inputNav("nav_topbar", "telefono_label", "Texto telefono", data.telefono_label, "col-md-3") +
      inputNav("nav_topbar", "whatsapp_label", "Texto WhatsApp", data.whatsapp_label, "col-md-2") +
      inputNav("nav_topbar", "whatsapp_url", "URL WhatsApp", data.whatsapp_url, "col-md-2") +
      '<div class="col-md-3"><label class="form-label fs-8 fw-bold">Mostrar redes</label><select class="form-select form-select-sm" data-nav-section="nav_topbar" data-nav-field="mostrar_redes"><option value="1"' + (data.mostrar_redes ? ' selected' : '') + '>Si</option><option value="0"' + (!data.mostrar_redes ? ' selected' : '') + '>No</option></select></div>' +
      '<div class="col-md-3"><label class="form-label fs-8 fw-bold">Visible</label><select class="form-select form-select-sm" data-nav-section="nav_topbar" data-nav-field="visible"><option value="1"' + (data.visible ? ' selected' : '') + '>Si</option><option value="0"' + (!data.visible ? ' selected' : '') + '>No</option></select></div>' +
    '</div>';
  }

  function renderNavMenuPrincipal(data) {
    return '<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">' +
      '<div class="fw-bold">Enlaces del header</div><button class="btn btn-sm btn-light-primary" type="button" id="cms_actual_nav_menu_agregar"><i class="bi bi-plus-circle"></i> Agregar enlace</button>' +
    '</div>' +
    (data.items || []).map(function (item, index) {
      return '<div class="cms-actual-slide mb-3">' +
        '<div class="d-flex justify-content-between align-items-center gap-2 mb-3">' +
          '<div class="fw-semibold">Enlace ' + escapeHtml(index + 1) + '</div>' +
          accionesNav("nav_menu_principal", index, -1) +
        '</div>' +
        '<div class="row g-3">' +
          inputNavItem("nav_menu_principal", index, "label", "Etiqueta", item.label, "col-md-3") +
          inputNavItem("nav_menu_principal", index, "tipo", "Tipo", item.tipo, "col-md-2") +
          inputNavItem("nav_menu_principal", index, "url", "URL", item.url, "col-md-4") +
          '<div class="col-md-2"><label class="form-label fs-8 fw-bold">Visible</label><select class="form-select form-select-sm" data-nav-section="nav_menu_principal" data-nav-field="items.' + escapeAttr(index) + '.visible"><option value="1"' + (item.visible ? ' selected' : '') + '>Si</option><option value="0"' + (!item.visible ? ' selected' : '') + '>No</option></select></div>' +
        '</div>' +
      '</div>';
    }).join("");
  }

  function renderNavFooterColumnas(data) {
    return '<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">' +
      '<div class="fw-bold">Columnas del footer</div><button class="btn btn-sm btn-light-primary" type="button" id="cms_actual_nav_footer_columna_agregar"><i class="bi bi-plus-circle"></i> Agregar columna</button>' +
    '</div>' +
    (data.items || []).map(function (columna, index) {
      return '<div class="cms-actual-slide mb-3">' +
        '<div class="d-flex justify-content-between align-items-center gap-2 mb-3">' +
          '<div class="fw-semibold">Columna ' + escapeHtml(index + 1) + '</div>' +
          accionesNav("nav_footer_columnas", index, -1) +
        '</div>' +
        '<div class="row g-3">' +
          inputNavItem("nav_footer_columnas", index, "titulo", "Titulo", columna.titulo, "col-md-5") +
          '<div class="col-md-2"><label class="form-label fs-8 fw-bold">Visible</label><select class="form-select form-select-sm" data-nav-section="nav_footer_columnas" data-nav-field="items.' + escapeAttr(index) + '.visible"><option value="1"' + (columna.visible ? ' selected' : '') + '>Si</option><option value="0"' + (!columna.visible ? ' selected' : '') + '>No</option></select></div>' +
          '<div class="col-12"><label class="form-label fs-8 fw-bold">Links de la columna</label><textarea class="form-control form-control-sm" rows="4" data-nav-section="nav_footer_columnas" data-nav-field="items.' + escapeAttr(index) + '.links_text">' + escapeHtml(linksToText(columna.links || [])) + '</textarea><div class="text-muted fs-8 mt-1">Un link por linea: Etiqueta|/ruta-publica</div></div>' +
        '</div>' +
      '</div>';
    }).join("");
  }

  function renderNavFooterCta(data) {
    return '<div class="row g-3">' +
      inputNav("nav_footer_cta", "titulo", "Titulo", data.titulo, "col-md-4") +
      inputNav("nav_footer_cta", "texto", "Texto", data.texto, "col-md-8") +
      inputNav("nav_footer_cta", "cta.label", "CTA etiqueta", data.cta ? data.cta.label : "", "col-md-4") +
      inputNav("nav_footer_cta", "cta.url", "CTA URL", data.cta ? data.cta.url : "", "col-md-5") +
      '<div class="col-md-3"><label class="form-label fs-8 fw-bold">Visible</label><select class="form-select form-select-sm" data-nav-section="nav_footer_cta" data-nav-field="visible"><option value="1"' + (data.visible ? ' selected' : '') + '>Si</option><option value="0"' + (!data.visible ? ' selected' : '') + '>No</option></select></div>' +
    '</div>';
  }

  function inputNav(seccionCodigo, campo, label, value, col) {
    return '<div class="' + escapeAttr(col || "col-md-6") + '"><label class="form-label fs-8 fw-bold">' + escapeHtml(label) + '</label><input class="form-control form-control-sm" data-nav-section="' + escapeAttr(seccionCodigo) + '" data-nav-field="' + escapeAttr(campo) + '" value="' + escapeAttr(value == null ? "" : value) + '"></div>';
  }

  function inputNavItem(seccionCodigo, index, campo, label, value, col) {
    return '<div class="' + escapeAttr(col || "col-md-4") + '"><label class="form-label fs-8 fw-bold">' + escapeHtml(label) + '</label><input class="form-control form-control-sm" data-nav-section="' + escapeAttr(seccionCodigo) + '" data-nav-field="items.' + escapeAttr(index) + '.' + escapeAttr(campo) + '" value="' + escapeAttr(value == null ? "" : value) + '"></div>';
  }

  function accionesNav(seccionCodigo, index, linkIndex) {
    return '<div class="d-flex gap-2">' +
      '<button class="btn btn-sm btn-light" type="button" data-nav-section="' + escapeAttr(seccionCodigo) + '" data-nav-action="subir" data-index="' + escapeAttr(index) + '" data-link-index="' + escapeAttr(linkIndex) + '"><i class="bi bi-arrow-up"></i></button>' +
      '<button class="btn btn-sm btn-light" type="button" data-nav-section="' + escapeAttr(seccionCodigo) + '" data-nav-action="bajar" data-index="' + escapeAttr(index) + '" data-link-index="' + escapeAttr(linkIndex) + '"><i class="bi bi-arrow-down"></i></button>' +
      '<button class="btn btn-sm btn-light-warning" type="button" data-nav-section="' + escapeAttr(seccionCodigo) + '" data-nav-action="toggle" data-index="' + escapeAttr(index) + '" data-link-index="' + escapeAttr(linkIndex) + '"><i class="bi bi-eye"></i></button>' +
      '<button class="btn btn-sm btn-light-danger" type="button" data-nav-section="' + escapeAttr(seccionCodigo) + '" data-nav-action="eliminar" data-index="' + escapeAttr(index) + '" data-link-index="' + escapeAttr(linkIndex) + '"><i class="bi bi-trash"></i></button>' +
    '</div>';
  }

  function renderGlobalSeccion(item) {
    var data = globalData(item.codigo);
    if (!data) return "";
    var campos = camposGlobal(item.codigo, data);
    return '<div class="cms-actual-card mb-4">' +
      '<div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-4">' +
        '<div><div class="fw-bold">' + escapeHtml(item.codigo) + '</div><div class="text-muted fs-8">' + escapeHtml(item.descripcion) + '</div></div>' +
        '<span class="badge badge-light-success">Editor activo</span>' +
      '</div>' +
      '<div class="row g-3">' + campos.join("") + '</div>' +
    '</div>';
  }

  function camposGlobal(codigo, data) {
    if (codigo === "global_negocio") {
      return [
        inputGlobal(codigo, "nombre_comercial", "Nombre comercial", data.nombre_comercial, "col-md-4"),
        inputGlobal(codigo, "razon_social", "Razon social", data.razon_social, "col-md-4"),
        inputGlobal(codigo, "slogan", "Slogan", data.slogan, "col-md-4"),
        inputGlobal(codigo, "descripcion_corta", "Descripcion corta", data.descripcion_corta, "col-md-12"),
        inputGlobal(codigo, "logo_principal", "Logo principal", data.logo_principal, "col-md-4"),
        inputGlobal(codigo, "logo_blanco", "Logo blanco", data.logo_blanco, "col-md-4"),
        inputGlobal(codigo, "favicon", "Favicon", data.favicon, "col-md-4"),
        inputGlobal(codigo, "whatsapp", "WhatsApp", data.whatsapp, "col-md-3"),
        inputGlobal(codigo, "telefono", "Telefono", data.telefono, "col-md-3"),
        inputGlobal(codigo, "email_contacto", "Email contacto", data.email_contacto, "col-md-3"),
        inputGlobal(codigo, "email_facturacion", "Email facturacion", data.email_facturacion, "col-md-3")
      ];
    }
    if (codigo === "global_ubicacion") {
      return [
        inputGlobal(codigo, "direccion.calle", "Calle", data.direccion.calle, "col-md-4"),
        inputGlobal(codigo, "direccion.colonia", "Colonia", data.direccion.colonia, "col-md-4"),
        inputGlobal(codigo, "direccion.ciudad", "Ciudad", data.direccion.ciudad, "col-md-4"),
        inputGlobal(codigo, "direccion.estado", "Estado", data.direccion.estado, "col-md-3"),
        inputGlobal(codigo, "direccion.codigo_postal", "Codigo postal", data.direccion.codigo_postal, "col-md-3"),
        inputGlobal(codigo, "direccion.pais", "Pais", data.direccion.pais, "col-md-3"),
        inputGlobal(codigo, "direccion.texto_publico", "Texto publico", data.direccion.texto_publico, "col-md-3"),
        inputGlobal(codigo, "mapa.google_maps_url", "Google Maps URL", data.mapa.google_maps_url, "col-md-6"),
        inputGlobal(codigo, "mapa.embed_url", "Embed URL", data.mapa.embed_url, "col-md-6"),
        inputGlobal(codigo, "mapa.lat", "Lat", data.mapa.lat, "col-md-3"),
        inputGlobal(codigo, "mapa.lng", "Lng", data.mapa.lng, "col-md-3")
      ];
    }
    if (codigo === "global_horarios") {
      return [
        '<div class="col-12"><div class="alert alert-light-info fs-7 mb-0">Edicion repetible fina pendiente. Por ahora edita el JSON generado o usa defaults locales.</div></div>'
      ].concat((data.items || []).map(function (item, index) {
        return inputGlobalHorario(index, "dias", "Dias", item.dias, "col-md-4") +
          inputGlobalHorario(index, "horario", "Horario", item.horario, "col-md-4") +
          inputGlobalHorario(index, "visible", "Visible 1/0", item.visible ? "1" : "0", "col-md-2");
      }));
    }
    if (codigo === "global_redes") {
      return [
        inputGlobal(codigo, "facebook", "Facebook", data.facebook, "col-md-6"),
        inputGlobal(codigo, "instagram", "Instagram", data.instagram, "col-md-6"),
        inputGlobal(codigo, "tiktok", "TikTok", data.tiktok, "col-md-6"),
        inputGlobal(codigo, "youtube", "YouTube", data.youtube, "col-md-6")
      ];
    }
    if (codigo === "global_seo") {
      return [
        inputGlobal(codigo, "site_name", "Site name", data.site_name, "col-md-4"),
        inputGlobal(codigo, "title_default", "Title default", data.title_default, "col-md-4"),
        inputGlobal(codigo, "robots_default", "Robots", data.robots_default, "col-md-4"),
        inputGlobal(codigo, "description_default", "Description default", data.description_default, "col-md-8"),
        inputGlobal(codigo, "og_image_default", "OG image default", data.og_image_default, "col-md-4")
      ];
    }
    return [
      '<div class="col-md-6"><label class="form-label fs-8 fw-bold">Menu principal</label><textarea class="form-control form-control-sm" rows="5" data-global-section="' + escapeAttr(codigo) + '" data-global-field="menu_principal_json">' + escapeHtml(JSON.stringify(data.menu_principal || [], null, 2)) + '</textarea></div>',
      '<div class="col-md-6"><label class="form-label fs-8 fw-bold">Footer columnas</label><textarea class="form-control form-control-sm" rows="5" data-global-section="' + escapeAttr(codigo) + '" data-global-field="footer_columnas_json">' + escapeHtml(JSON.stringify(data.footer_columnas || [], null, 2)) + '</textarea></div>'
    ];
  }

  function inputGlobal(seccionCodigo, campo, label, value, col) {
    var esImagen = campo === "logo_principal" || campo === "logo_blanco" || campo === "favicon" || campo === "og_image_default";
    var input = '<input class="form-control form-control-sm" data-global-section="' + escapeAttr(seccionCodigo) + '" data-global-field="' + escapeAttr(campo) + '" value="' + escapeAttr(value == null ? "" : value) + '">';
    if (esImagen) {
      input = '<div class="input-group input-group-sm">' + input + '<button class="btn btn-light-primary" type="button" data-media-picker="global" data-index="' + escapeAttr(seccionCodigo) + '" data-field="' + escapeAttr(campo) + '"><i class="bi bi-images"></i> Media</button></div>';
    }
    return '<div class="' + escapeAttr(col || "col-md-6") + '"><label class="form-label fs-8 fw-bold">' + escapeHtml(label) + '</label>' + input + '</div>';
  }

  function inputGlobalHorario(index, campo, label, value, col) {
    return '<div class="' + escapeAttr(col || "col-md-4") + '"><label class="form-label fs-8 fw-bold">' + escapeHtml(label) + '</label><input class="form-control form-control-sm" data-global-section="global_horarios" data-global-field="items.' + escapeAttr(index) + '.' + escapeAttr(campo) + '" value="' + escapeAttr(value == null ? "" : value) + '"></div>';
  }

  function actualizarGlobalField(seccionCodigo, campo, valor) {
    var data = globalData(seccionCodigo);
    if (!data) return;
    if (campo === "menu_principal_json" || campo === "footer_columnas_json") {
      try {
        data[campo.replace("_json", "")] = JSON.parse(valor || "[]");
      } catch (error) {
        setText("cms_actual_estado", "JSON invalido en navegacion");
        return;
      }
    } else if (campo.indexOf(".visible") !== -1) {
      setPath(data, campo, valor === "1");
    } else {
      setPath(data, campo, valor);
    }
    refrescarJson();
  }

  function actualizarNavegacionField(seccionCodigo, campo, valor) {
    var data = navegacionData(seccionCodigo);
    if (!data) return;
    if (campo === "visible" || campo === "mostrar_redes" || campo.indexOf(".visible") !== -1) {
      setPath(data, campo, valor === "1");
    } else if (campo.indexOf(".links_text") !== -1) {
      setPath(data, campo.replace(".links_text", ".links"), textToLinks(valor));
    } else {
      setPath(data, campo, valor);
    }
    refrescarJson();
  }

  function ejecutarNavegacionAccion(seccionCodigo, accion, index) {
    var data = navegacionData(seccionCodigo);
    var items = data && data.items ? data.items : [];
    if (!items[index]) return;
    if (accion === "subir" && index > 0) {
      items.splice(index - 1, 0, items.splice(index, 1)[0]);
    }
    if (accion === "bajar" && index < items.length - 1) {
      items.splice(index + 1, 0, items.splice(index, 1)[0]);
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

  function agregarMenuPrincipalItem() {
    var items = navegacionData("nav_menu_principal").items;
    items.push({ label: "Nuevo enlace", tipo: "ruta", url: "/", orden: (items.length + 1) * 10, visible: true });
    renderGrupo();
  }

  function agregarFooterColumna() {
    var items = navegacionData("nav_footer_columnas").items;
    items.push({
      titulo: "Nueva columna",
      links: [{ label: "Nuevo link", url: "/", visible: true, orden: 10 }],
      orden: (items.length + 1) * 10,
      visible: true
    });
    renderGrupo();
  }

  function actualizarCategoriaCmsField(seccionCodigo, campo, valor) {
    var data = categoriasCmsData(seccionCodigo);
    if (!data) return;
    if (campo === "mostrar_en_home" || campo === "mostrar_en_menu" || campo.indexOf(".visible") !== -1 || campo.indexOf(".destacado") !== -1) {
      setPath(data, campo, valor === "1");
    } else if (campo.indexOf(".categoria_id") !== -1) {
      setPath(data, campo, parseInt(valor || "0", 10) || 0);
    } else {
      setPath(data, campo, valor);
    }
    refrescarJson();
  }

  function ejecutarCategoriaCmsAccion(accion, index) {
    var items = categoriasCmsData("categorias_items").items;
    if (!items[index]) return;
    if (accion === "subir" && index > 0) {
      items.splice(index - 1, 0, items.splice(index, 1)[0]);
    }
    if (accion === "bajar" && index < items.length - 1) {
      items.splice(index + 1, 0, items.splice(index, 1)[0]);
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

  function agregarCategoriaCmsItem() {
    var items = categoriasCmsData("categorias_items").items;
    items.push({
      categoria_id: 0,
      slug: "nueva-categoria",
      titulo: "Nueva categoria",
      subtitulo: "",
      descripcion_seo: "",
      imagen_card: "",
      imagen_banner: "",
      alt_card: "",
      alt_banner: "",
      destacado: false,
      visible: true,
      orden: (items.length + 1) * 10,
      url: "/categoria/nueva-categoria"
    });
    renderGrupo();
  }

  function actualizarMarcaCmsField(seccionCodigo, campo, valor) {
    var data = marcasCmsData(seccionCodigo);
    if (!data) return;
    if (campo === "mostrar_en_home" || campo === "mostrar_en_menu" || campo.indexOf(".visible") !== -1 || campo.indexOf(".destacado") !== -1) {
      setPath(data, campo, valor === "1");
    } else if (campo.indexOf(".marca_id") !== -1) {
      setPath(data, campo, parseInt(valor || "0", 10) || 0);
    } else {
      setPath(data, campo, valor);
    }
    refrescarJson();
  }

  function ejecutarMarcaCmsAccion(accion, index) {
    var items = marcasCmsData("marcas_items").items;
    if (!items[index]) return;
    if (accion === "subir" && index > 0) {
      items.splice(index - 1, 0, items.splice(index, 1)[0]);
    }
    if (accion === "bajar" && index < items.length - 1) {
      items.splice(index + 1, 0, items.splice(index, 1)[0]);
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

  function agregarMarcaCmsItem() {
    var items = marcasCmsData("marcas_items").items;
    items.push({
      marca_id: 0,
      slug: "nueva-marca",
      titulo: "Nueva marca",
      subtitulo: "",
      descripcion_seo: "",
      logo: "",
      imagen_banner: "",
      alt_logo: "",
      alt_banner: "",
      destacado: false,
      visible: true,
      orden: (items.length + 1) * 10,
      url: "/marca/nueva-marca"
    });
    renderGrupo();
  }

  function actualizarPaginaCmsField(seccionCodigo, campo, valor) {
    var data = paginasCmsData(seccionCodigo);
    if (!data) return;
    if (campo.indexOf(".visible") !== -1) {
      setPath(data, campo, valor === "1");
    } else {
      setPath(data, campo, valor);
    }
    refrescarJson();
  }

  function ejecutarPaginaCmsAccion(accion, index) {
    var items = paginasCmsData("paginas_items").items;
    if (!items[index]) return;
    if (accion === "subir" && index > 0) {
      items.splice(index - 1, 0, items.splice(index, 1)[0]);
    }
    if (accion === "bajar" && index < items.length - 1) {
      items.splice(index + 1, 0, items.splice(index, 1)[0]);
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

  function agregarPaginaCmsItem() {
    var items = paginasCmsData("paginas_items").items;
    items.push({
      slug: "nueva-pagina",
      titulo: "Nueva pagina",
      subtitulo: "",
      resumen: "",
      contenido: "",
      imagen_principal: "",
      alt_imagen: "",
      seo_title: "",
      seo_description: "",
      visible: true,
      orden: (items.length + 1) * 10,
      url: "/nueva-pagina"
    });
    renderGrupo();
  }

  function actualizarPoliticaCmsField(seccionCodigo, campo, valor) {
    var data = politicasCmsData(seccionCodigo);
    if (!data) return;
    if (campo.indexOf(".visible") !== -1) {
      setPath(data, campo, valor === "1");
    } else {
      setPath(data, campo, valor);
    }
    refrescarJson();
  }

  function ejecutarPoliticaCmsAccion(accion, index) {
    var items = politicasCmsData("politicas_items").items;
    if (!items[index]) return;
    if (accion === "subir" && index > 0) {
      items.splice(index - 1, 0, items.splice(index, 1)[0]);
    }
    if (accion === "bajar" && index < items.length - 1) {
      items.splice(index + 1, 0, items.splice(index, 1)[0]);
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

  function agregarPoliticaCmsItem() {
    var items = politicasCmsData("politicas_items").items;
    items.push({
      slug: "nueva-politica",
      titulo: "Nueva politica",
      resumen: "",
      contenido: "",
      version: "1.0",
      estatus: "borrador",
      vigente_desde: "",
      vigente_hasta: "",
      seo_title: "",
      seo_description: "",
      visible: true,
      orden: (items.length + 1) * 10,
      url: "/politicas/nueva-politica"
    });
    renderGrupo();
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
    if (grupo.codigo === "politicas") {
      return previewPoliticasCmsJson();
    }
    if (grupo.codigo === "paginas") {
      return previewPaginasCmsJson();
    }
    if (grupo.codigo === "marcas") {
      return previewMarcasCmsJson();
    }
    if (grupo.codigo === "categorias") {
      return previewCategoriasCmsJson();
    }
    if (grupo.codigo === "navegacion") {
      return previewNavegacionJson();
    }
    if (grupo.codigo === "global") {
      return previewGlobalJson();
    }
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

  function previewGlobalJson() {
    var global = estado.datos.global || {};
    return {
      tipo: "success",
      mensaje: "CMS frontend global consultado",
      depurar: {
        version: "cms_frontend_2026_08_19",
        pagina: "global",
        actualizado_en: "pendiente",
        negocio: global.global_negocio,
        direccion: global.global_ubicacion ? global.global_ubicacion.direccion : {},
        mapa: global.global_ubicacion ? global.global_ubicacion.mapa : {},
        horarios: global.global_horarios ? global.global_horarios.items : [],
        redes_sociales: global.global_redes,
        seo_global: global.global_seo,
        navegacion: global.global_navegacion,
        guardrails: {
          no_archivos_erp: true,
          no_secretos: true,
          fuente: "preview_local_panel"
        }
      }
    };
  }

  function previewNavegacionJson() {
    var nav = estado.datos.navegacion || {};
    return {
      tipo: "success",
      mensaje: "CMS frontend navegacion consultado",
      depurar: {
        version: "cms_frontend_2026_08_19",
        pagina: "navegacion",
        endpoint_destino: "/ecommercePublico/configuracion_inicial",
        actualizado_en: "pendiente",
        header: {
          topbar: nav.nav_topbar || {},
          menu_principal: nav.nav_menu_principal ? nav.nav_menu_principal.items : []
        },
        footer: {
          columnas: nav.nav_footer_columnas ? nav.nav_footer_columnas.items : [],
          cta: nav.nav_footer_cta || {}
        },
        guardrails: {
          solo_rutas_publicas: true,
          no_rutas_erp: true,
          no_html_libre: true,
          fuente: "preview_local_panel"
        }
      }
    };
  }

  function previewCategoriasCmsJson() {
    var config = categoriasCmsData("categorias_config") || {};
    var items = categoriasCmsData("categorias_items") ? categoriasCmsData("categorias_items").items : [];
    return {
      tipo: "success",
      mensaje: "CMS frontend categorias consultado",
      depurar: {
        version: "cms_frontend_2026_08_19",
        pagina: "categorias",
        endpoint_destino: "/ecommercePublico/categorias",
        actualizado_en: "pendiente",
        config: config,
        categorias: (items || []).map(function (item) {
          return {
            categoria_id: item.categoria_id,
            slug: item.slug,
            titulo: item.titulo,
            subtitulo: item.subtitulo,
            descripcion_seo: item.descripcion_seo,
            imagen_card: item.imagen_card,
            imagen_banner: item.imagen_banner,
            alt_card: item.alt_card,
            alt_banner: item.alt_banner,
            destacado: item.destacado,
            visible: item.visible,
            orden: item.orden,
            url: item.url
          };
        }),
        guardrails: {
          no_modifica_catalogo: true,
          no_modifica_precios: true,
          no_modifica_inventario: true,
          fuente: "preview_local_panel"
        }
      }
    };
  }

  function previewMarcasCmsJson() {
    var config = marcasCmsData("marcas_config") || {};
    var items = marcasCmsData("marcas_items") ? marcasCmsData("marcas_items").items : [];
    return {
      tipo: "success",
      mensaje: "CMS frontend marcas consultado",
      depurar: {
        version: "cms_frontend_2026_08_19",
        pagina: "marcas",
        endpoint_destino: "/ecommercePublico/marcas",
        actualizado_en: "pendiente",
        config: config,
        marcas: (items || []).map(function (item) {
          return {
            marca_id: item.marca_id,
            slug: item.slug,
            titulo: item.titulo,
            subtitulo: item.subtitulo,
            descripcion_seo: item.descripcion_seo,
            logo: item.logo,
            imagen_banner: item.imagen_banner,
            alt_logo: item.alt_logo,
            alt_banner: item.alt_banner,
            destacado: item.destacado,
            visible: item.visible,
            orden: item.orden,
            url: item.url
          };
        }),
        guardrails: {
          no_modifica_catalogo: true,
          no_modifica_precios: true,
          no_modifica_inventario: true,
          fuente: "preview_local_panel"
        }
      }
    };
  }

  function previewPaginasCmsJson() {
    var config = paginasCmsData("paginas_config") || {};
    var items = paginasCmsData("paginas_items") ? paginasCmsData("paginas_items").items : [];
    return {
      tipo: "success",
      mensaje: "CMS frontend paginas consultado",
      depurar: {
        version: "cms_frontend_2026_08_19",
        pagina: "paginas",
        endpoint_destino: "/ecommercePublico/paginas",
        actualizado_en: "pendiente",
        config: config,
        paginas: (items || []).map(function (item) {
          return {
            slug: item.slug,
            titulo: item.titulo,
            subtitulo: item.subtitulo,
            resumen: item.resumen,
            contenido: item.contenido,
            imagen_principal: item.imagen_principal,
            alt_imagen: item.alt_imagen,
            seo: {
              title: item.seo_title,
              description: item.seo_description
            },
            visible: item.visible,
            orden: item.orden,
            url: item.url
          };
        }),
        guardrails: {
          no_js_libre: true,
          no_rutas_erp: true,
          sanitizacion_backend_pendiente: true,
          fuente: "preview_local_panel"
        }
      }
    };
  }

  function previewPoliticasCmsJson() {
    var config = politicasCmsData("politicas_config") || {};
    var items = politicasCmsData("politicas_items") ? politicasCmsData("politicas_items").items : [];
    return {
      tipo: "success",
      mensaje: "CMS frontend politicas consultado",
      depurar: {
        version: "cms_frontend_2026_08_19",
        pagina: "politicas",
        endpoint_destino: "/ecommercePublico/politicas",
        endpoint_detalle: "/ecommercePublico/politica/{slug}",
        actualizado_en: "pendiente",
        config: config,
        politicas: (items || []).map(function (item) {
          return {
            slug: item.slug,
            titulo: item.titulo,
            resumen: item.resumen,
            contenido: item.contenido,
            version: item.version,
            estatus: item.estatus,
            vigente_desde: item.vigente_desde,
            vigente_hasta: item.vigente_hasta,
            seo: {
              title: item.seo_title,
              description: item.seo_description
            },
            visible: item.visible,
            orden: item.orden,
            url: item.url
          };
        }),
        guardrails: {
          requiere_revision_legal: true,
          no_js_libre: true,
          sanitizacion_backend_pendiente: true,
          fuente: "preview_local_panel"
        }
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

  function globalData(codigo) {
    return estado.datos.global ? estado.datos.global[codigo] : null;
  }

  function navegacionData(codigo) {
    return estado.datos.navegacion ? estado.datos.navegacion[codigo] : null;
  }

  function categoriasCmsData(codigo) {
    return estado.datos.categorias ? estado.datos.categorias[codigo] : null;
  }

  function marcasCmsData(codigo) {
    return estado.datos.marcas ? estado.datos.marcas[codigo] : null;
  }

  function paginasCmsData(codigo) {
    return estado.datos.paginas ? estado.datos.paginas[codigo] : null;
  }

  function politicasCmsData(codigo) {
    return estado.datos.politicas ? estado.datos.politicas[codigo] : null;
  }

  function linksToText(items) {
    return (items || []).map(function (item) {
      return (item.label || "") + "|" + (item.url || "");
    }).join("\n");
  }

  function textToLinks(value) {
    return String(value || "").split("\n").map(function (line, index) {
      var partes = line.split("|");
      var label = (partes[0] || "").trim();
      var url = (partes.slice(1).join("|") || "").trim();
      if (!label && !url) return null;
      return { label: label || url, url: url || "/", visible: true, orden: (index + 1) * 10 };
    }).filter(Boolean);
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
    if (picker.contexto === "global") target = globalData(picker.index);
    if (picker.contexto === "cms_categoria") target = categoriasCmsData("categorias_items").items[picker.index];
    if (picker.contexto === "cms_marca") target = marcasCmsData("marcas_items").items[picker.index];
    if (picker.contexto === "cms_pagina") target = paginasCmsData("paginas_items").items[picker.index];
    if (!target) return;
    setPath(target, picker.campo, media.url);
    if (picker.contexto === "cms_categoria" && picker.campo === "imagen_card" && !target.alt_card && media.alt) target.alt_card = media.alt;
    if (picker.contexto === "cms_categoria" && picker.campo === "imagen_banner" && !target.alt_banner && media.alt) target.alt_banner = media.alt;
    if (picker.contexto === "cms_marca" && picker.campo === "logo" && !target.alt_logo && media.alt) target.alt_logo = media.alt;
    if (picker.contexto === "cms_marca" && picker.campo === "imagen_banner" && !target.alt_banner && media.alt) target.alt_banner = media.alt;
    if (picker.contexto === "cms_pagina" && picker.campo === "imagen_principal" && !target.alt_imagen && media.alt) target.alt_imagen = media.alt;
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
