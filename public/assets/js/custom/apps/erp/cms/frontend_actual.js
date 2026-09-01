/*
 * Documentacion IA: Codex GPT-5, 2026-08-13.
 * Proposito: presentar contrato CMS alineado al frontend ecommerce actual.
 * Impacto: CMS; guia implementacion incremental sin builder generico.
 * Contrato: no escribe BD, no edita archivos frontend, genera preview JSON local.
 */
(function () {
  "use strict";

  var MEDIA_STORAGE_KEY = "erp_cms_media_biblioteca_local_v1";
  var FRONTEND_DRAFT_STORAGE_KEY = "erp_cms_frontend_actual_borrador_v1";
  var MEDIA_MAX_BYTES = 2 * 1024 * 1024;
  var MEDIA_MIMES = ["image/jpeg", "image/png", "image/webp", "image/vnd.microsoft.icon", "image/x-icon", "image/icon", "application/ico"];

  var estado = {
    grupo: "global",
    vistaDedicada: false,
    borradorLocalCargado: false,
    mediaPicker: { contexto: "", index: 0, campo: "", archivo: null, dataUrl: "", seleccion: "" },
    catalogos: { categorias: [], categoriasPorId: {}, marcas: [], marcasPorId: {} },
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
        },
        global_whatsapp_chat: {
          codigo: "global_whatsapp_chat",
          slot: "global.whatsapp_chat",
          tipo: "whatsapp_chat",
          layout: "floating_multi_contact",
          visible: true,
          orden: 70,
          titulo: "Necesitas ayuda?",
          subtitulo: "Elige un asesor y escribenos por WhatsApp.",
          boton: { label: "WhatsApp", icono: "whatsapp" },
          mensaje_default: "Hola, vi el catalogo de Artiani y quiero mas informacion.",
          config: {
            posicion: "bottom_right",
            mostrar_en_mobile: true,
            mostrar_en_desktop: true,
            abrir_en_nueva_pestana: true,
            mostrar_horario: true,
            mostrar_estado_online: false
          },
          contactos: [
            {
              id: "ventas",
              nombre: "Ventas Artiani",
              descripcion: "Productos, precios y pedidos",
              telefono: "",
              mensaje: "Hola, quiero informacion sobre productos de Artiani.",
              avatar: "",
              icono: "whatsapp",
              horario: "Lunes a sabado de 10:00 a 19:00",
              orden: 10,
              visible: true
            }
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
      catalogo: {
        catalogo_configuracion: {
          codigo: "catalogo_configuracion",
          tipo: "content_html_safe",
          visible: true,
          orden: 10,
          titulo: "Catalogo Artiani",
          subtitulo: "Explora productos para acuario y mascotas.",
          contenido_html: "<p>Encuentra alimento, accesorios, habitats y cuidado especializado. Precios y disponibilidad se confirman al enviar tu solicitud.</p>",
          cta: { label: "Ver productos", url: "/catalogo" },
          seo: {
            title: "Catalogo Artiani",
            description: "Catalogo de productos para acuario y mascotas en Artiani."
          },
          estados: {
            sin_resultados_titulo: "No encontramos productos",
            sin_resultados_texto: "Intenta quitar filtros o buscar otra palabra."
          },
          config: {
            variante: "wokiee_catalog_header",
            mostrar_breadcrumbs: true,
            mostrar_conteo: true
          }
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
              heredar_banner: true,
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
              heredar_banner: true,
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
        home_promo: {
          codigo: "home_promo",
          tipo: "promo_strip",
          visible: true,
          orden: 20,
          titulo: "Franja promocional",
          config: {
            variante: "wokiee_promo_strip",
            tono: "info"
          },
          items: [
            {
              icono: "bi-whatsapp",
              texto: "Atencion personalizada por WhatsApp",
              cta: { label: "Escribir", url: "https://wa.me/" },
              visible: true,
              orden: 10
            }
          ]
        },
        home_promos_categoria: {
          codigo: "home_promos_categoria",
          tipo: "promos_categoria",
          visible: true,
          orden: 25,
          titulo: "Promos por categoria",
          subtitulo: "Accesos visuales hacia categorias comerciales fuertes.",
          config: {
            variante: "wokiee_promo_categories"
          },
          items: [
            {
              titulo: "Alimentos de acuario",
              subtitulo: "Nutricion para cada especie",
              imagen: "",
              alt: "Alimentos de acuario",
              url: "/categoria/acuario-y-peces/alimentacion/alimentos-de-acuario",
              path_slug: "acuario-y-peces/alimentacion/alimentos-de-acuario",
              categoria_id: 0,
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
        home_marcas_destacadas: {
          codigo: "home_marcas_destacadas",
          tipo: "marcas_destacadas",
          visible: true,
          orden: 45,
          titulo: "Marcas destacadas",
          subtitulo: "Marcas relacionadas con una categoria del catalogo.",
          layout: "marcas_contextuales",
          categoria_contexto: {
            categoria_id: 0,
            titulo: "",
            path_slug: "",
            url: ""
          },
          fuente: {
            modo: "mixto",
            categoria_slug: "",
            limite: 0,
            rellenar_automatico_si_faltan: true
          },
          config: {
            variante: "wokiee_brand_strip",
            mostrar_iniciales_si_sin_logo: true
          },
          items: []
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
        home_esenciales_artiani: {
          codigo: "home_esenciales_artiani",
          tipo: "bloque_editorial_cards",
          visible: true,
          orden: 70,
          titulo: "Esenciales Artiani",
          subtitulo: "Atajos para encontrar lo que buscas.",
          categoria_principal: {
            categoria_id: 0,
            titulo: "Acuario y peces",
            url: "/categoria/acuario-y-peces",
            path_slug: "acuario-y-peces",
            imagen: "",
            alt: "Productos para acuario y peces",
            objetivo: "Llevar a la categoria principal de acuario"
          },
          items: [
            {
              categoria_id: 0,
              titulo: "Alimentos de acuario",
              subtitulo: "Nutricion para peces tropicales y de ornato",
              url: "/categoria/acuario-y-peces/alimentacion/alimentos-de-acuario",
              path_slug: "acuario-y-peces/alimentacion/alimentos-de-acuario",
              imagen: "",
              alt: "Alimentos para peces",
              objetivo: "Enviar al cliente a alimentos de acuario",
              visible: true,
              orden: 10
            }
          ],
          config: {
            max_items: 3,
            variante: "wokiee_editorial_cards"
          }
        },
        home_compra_guiada: {
          codigo: "home_compra_guiada",
          tipo: "compra_guiada",
          visible: true,
          orden: 80,
          titulo: "Compra guiada",
          subtitulo: "Encuentra productos por mascota",
          config: {
            mostrar_mascotas: true,
            mostrar_necesidades: false,
            prioridad: "secundaria",
            variante: "wokiee_guided_chips"
          },
          tracking: {
            section_id: "home_compra_guiada",
            section_name: "Compra guiada"
          }
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
        seccion("global_navegacion", "navegacion", "Menu principal y columnas de footer.", ["menu_principal", "footer_columnas"]),
        seccion("global_whatsapp_chat", "whatsapp_chat", "Boton flotante multi contacto para el frontend.", ["visible", "contactos", "mensaje_default", "posicion"])
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
      codigo: "catalogo",
      titulo: "Catalogo",
      subtitulo: "Encabezado, SEO y estados del listado publico.",
      endpoint: "GET /ecommercePublico/cms_frontend?pagina=catalogo",
      prioridad: 3,
      secciones: [
        seccion("catalogo_configuracion", "content_html_safe", "Texto superior, SEO y estados sin resultados del catalogo.", ["titulo", "subtitulo", "contenido_html", "seo", "estados"])
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
        seccion("home_orden_componentes", "orden_componentes", "Orden visible de componentes del Home; el hero se mantiene fijo arriba.", ["slot", "tipo", "orden", "visible"]),
        seccion("home_promo", "promo_strip", "Franja corta para avisos, WhatsApp, envios o mensajes comerciales.", ["texto", "icono", "cta", "visible"]),
        seccion("home_promos_categoria", "promos_categoria", "Promos visuales grandes hacia categorias comerciales fuertes.", ["titulo", "imagen", "url", "path_slug"]),
        seccion("home_categorias_destacadas", "categorias_destacadas", "Categorias reales publicadas con imagen card/banner.", ["categoria_id", "slug", "imagen_card", "imagen_banner"]),
        seccion("home_productos_destacados", "productos_destacados", "Productos por criterio o lista manual.", ["fuente.modo", "fuente.criterio", "fuente.productos", "limite"]),
        seccion("home_marcas_destacadas", "marcas_destacadas", "Marcas destacadas desde una categoria origen.", ["categoria_contexto", "fuente", "items"]),
        seccion("home_colecciones", "coleccion_productos", "Colecciones repetibles: novedades, destacados, basicos.", ["titulo", "fuente", "cta"]),
        seccion("home_esenciales_artiani", "bloque_editorial_cards", "Cards editoriales tipo Esenciales Artiani por categoria principal.", ["categoria_principal", "items", "url", "imagen"]),
        seccion("home_compra_guiada", "compra_guiada", "Entrada visual para orientar compra por mascota o necesidad.", ["titulo", "subtitulo", "config"]),
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
    estado.vistaDedicada = document.body ? document.body.getAttribute("data-cms-actual-dedicada") === "1" : false;
    if (grupoInicial) estado.grupo = grupoInicial;
    var tieneBorrador = cargarBorradorFrontendLocal(!grupoInicial);
    if (grupoInicial) estado.grupo = grupoInicial;
    renderTodo();
    cargarCatalogoCategoriasCms();
    cargarCatalogoMarcasCms();
    if (!tieneBorrador) {
      cargarGlobalPublicadoFrontend(false);
    }
    on("cms_actual_copiar_json", "click", copiarJson);
    on("cms_actual_home_estado_refrescar", "click", consultarEstadoHomePublicado);
    bindEstadoHomePublicado();
  });

  function seccion(codigo, tipo, descripcion, campos) {
    return { codigo: codigo, tipo: tipo, descripcion: descripcion, campos: campos, visible: true };
  }

  function renderTodo() {
    renderPageLinks();
    renderPrioridad();
    renderNav();
    renderReglas();
    renderEstadoHomeVisibilidad();
    renderGrupo();
  }

  function renderEstadoHomeVisibilidad() {
    var panel = $("cms_actual_home_estado_panel");
    if (!panel) return;
    panel.classList.toggle("d-none", estado.grupo !== "home");
    if (estado.grupo === "home") consultarEstadoHomePublicado();
  }

  function renderPrioridad() {
    var node = $("cms_actual_prioridad");
    if (!node) return;
    node.innerHTML = grupos.slice(0, 5).map(function (grupo) {
      return '<div class="cms-actual-card"><div class="text-muted fs-8 text-uppercase fw-bold">Prioridad ' + escapeHtml(grupo.prioridad) + '</div><div class="fw-bold mt-2">' + escapeHtml(grupo.titulo) + '</div><div class="text-muted fs-8 mt-2">' + escapeHtml(grupo.subtitulo) + '</div></div>';
    }).join("");
  }

  function renderPageLinks() {
    var node = $("cms_actual_page_links");
    if (!node) return;
    node.innerHTML = grupos.map(function (grupo) {
      return '<a class="cms-actual-page-link text-decoration-none" href="' + escapeAttr(rutaGrupo(grupo.codigo)) + '">' +
        '<div class="d-flex justify-content-between align-items-start gap-2">' +
          '<div><div class="fw-bold">' + escapeHtml(grupo.titulo) + '</div><div class="text-muted fs-8 mt-1">' + escapeHtml(grupo.subtitulo) + '</div></div>' +
          '<i class="bi bi-arrow-right text-primary"></i>' +
        '</div>' +
      '</a>';
    }).join("");
  }

  function cargarCatalogoCategoriasCms() {
    if (!window.fetch) return;
    fetch("/ecommercePublico/categorias", {
      method: "GET",
      credentials: "same-origin",
      headers: {
        "Accept": "application/json",
        "X-Requested-With": "XMLHttpRequest"
      }
    }).then(function (response) {
      return response.text().then(function (text) {
        var json = null;
        try {
          json = JSON.parse(text);
        } catch (error) {
          throw new Error("Respuesta no JSON al consultar categorias");
        }
        if (!response.ok || !json || json.error) throw new Error((json && json.mensaje) || "No se pudieron consultar categorias");
        return json;
      });
    }).then(function (json) {
      var items = json && json.depurar && Array.isArray(json.depurar.items) ? json.depurar.items : [];
      estado.catalogos.categorias = items;
      estado.catalogos.categoriasPorId = {};
      items.forEach(function (item) {
        if (item && item.id != null) estado.catalogos.categoriasPorId[String(item.id)] = item;
      });
      renderGrupo();
    }).catch(function () {
      estado.catalogos.categorias = [];
      estado.catalogos.categoriasPorId = {};
    });
  }

  function cargarCatalogoMarcasCms() {
    if (!window.fetch) return;
    fetch("/ecommercePublico/marcas", {
      method: "GET",
      credentials: "same-origin",
      headers: {
        "Accept": "application/json",
        "X-Requested-With": "XMLHttpRequest"
      }
    }).then(function (response) {
      return response.text().then(function (text) {
        var json = null;
        try {
          json = JSON.parse(text);
        } catch (error) {
          throw new Error("Respuesta no JSON al consultar marcas");
        }
        if (!response.ok || !json || json.error) throw new Error((json && json.mensaje) || "No se pudieron consultar marcas");
        return json;
      });
    }).then(function (json) {
      var items = json && json.depurar && Array.isArray(json.depurar.items) ? json.depurar.items : [];
      estado.catalogos.marcas = items;
      estado.catalogos.marcasPorId = {};
      items.forEach(function (item) {
        if (item && item.id != null) estado.catalogos.marcasPorId[String(item.id)] = item;
      });
      renderGrupo();
    }).catch(function () {
      estado.catalogos.marcas = [];
      estado.catalogos.marcasPorId = {};
    });
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
        if (estado.vistaDedicada) {
          window.location.href = rutaGrupo(estado.grupo);
          return;
        }
        renderTodo();
      });
    });
  }

  function rutaGrupo(codigo) {
    if (codigo === "home") return "/cms/frontend/home";
    if (codigo === "global") return "/cms/frontend/global";
    if (codigo === "navegacion") return "/cms/frontend/navegacion";
    if (codigo === "catalogo") return "/cms/frontend/catalogo";
    if (codigo === "categorias") return "/cms/frontend/categorias";
    if (codigo === "marcas") return "/cms/frontend/marcas";
    if (codigo === "paginas") return "/cms/frontend/paginas";
    if (codigo === "politicas") return "/cms/frontend/politicas";
    return "/cms";
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
    guardarBorradorFrontendLocal(true);
    setText("cms_actual_titulo", grupo.titulo);
    setText("cms_actual_subtitulo", grupo.subtitulo);
    setText("cms_actual_endpoint", grupo.endpoint);
    var node = $("cms_actual_secciones");
    if (node) {
      node.innerHTML = renderAccionesGrupo(grupo) + grupo.secciones.map(renderSeccion).join("");
      bindGrupoEditors();
    }
    setText("cms_actual_json", JSON.stringify(previewJson(grupo), null, 2));
  }

  function renderAccionesGrupo(grupo) {
    if (!grupo) return "";
    if (grupo.codigo === "categorias") {
      return '<div class="alert alert-light-primary d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">' +
        '<div><div class="fw-bold">Publicacion de categorias frontend</div><div class="fs-7 text-muted">Guarda imagenes, textos SEO, destacado, visible y orden sin modificar categorias reales del ERP.</div></div>' +
        '<div class="d-flex flex-wrap gap-2"><button class="btn btn-sm btn-light-primary" type="button" id="cms_actual_categorias_borrador"><i class="bi bi-save"></i> Guardar borrador local</button><button class="btn btn-sm btn-primary" type="button" id="cms_actual_categorias_publicar"><i class="bi bi-cloud-check"></i> Guardar y publicar categorias</button></div>' +
      '</div><div class="alert alert-light-warning fs-7 py-3 mb-4" id="cms_actual_categorias_estado">Pendiente de publicar en la API.</div>';
    }
    if (grupo.codigo !== "global") return "";
    return '<div class="alert alert-light-primary d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">' +
      '<div><div class="fw-bold">Publicacion global del frontend</div><div class="fs-7 text-muted">Guarda marca, contacto, logos, favicon, redes, ubicacion, horarios y SEO para configuracion_inicial.</div></div>' +
      '<div class="d-flex flex-wrap gap-2">' +
        '<button class="btn btn-sm btn-light-primary" type="button" id="cms_actual_global_borrador"><i class="bi bi-save"></i> Guardar borrador local</button>' +
        '<button class="btn btn-sm btn-light-info" type="button" id="cms_actual_global_cargar_publicado"><i class="bi bi-arrow-clockwise"></i> Cargar publicado</button>' +
        '<button class="btn btn-sm btn-light-success" type="button" id="cms_actual_global_api"><i class="bi bi-broadcast"></i> Ver API publicada</button>' +
        '<button class="btn btn-sm btn-primary" type="button" id="cms_actual_global_publicar"><i class="bi bi-cloud-check"></i> Guardar y publicar global</button>' +
      '</div>' +
    '</div><div class="alert alert-light-warning fs-7 py-3 mb-4" id="cms_actual_global_estado">Pendiente de publicar en la API.</div>' +
    '<div class="alert alert-light-secondary fs-7 py-3 mb-4 d-none" id="cms_actual_global_api_estado"></div>';
  }

  function consultarEstadoHomePublicado() {
    var node = $("cms_actual_home_estado");
    var boton = $("cms_actual_home_estado_refrescar");
    if (!node) return;
    node.innerHTML = '<div class="cms-actual-status-card text-muted fs-7">Consultando API publica...</div>';
    if (boton) boton.disabled = true;
    fetch("/ecommercePublico/contenido_pagina?pagina=home", {
      method: "GET",
      credentials: "same-origin",
      headers: {
        "Accept": "application/json",
        "X-Requested-With": "XMLHttpRequest"
      }
    }).then(function (response) {
      return response.text().then(function (text) {
        var json = null;
        try {
          json = JSON.parse(text);
        } catch (error) {
          throw new Error("Respuesta no JSON del servidor (" + response.status + ")");
        }
        if (!response.ok) throw new Error((json && json.mensaje) || "No se pudo consultar Home");
        return json;
      });
    }).then(function (json) {
      renderEstadoHomePublicado(json && json.depurar ? json.depurar : {});
    }).catch(function (error) {
      node.innerHTML = '<div class="cms-actual-status-card"><div class="badge badge-light-danger mb-2">Error</div><div class="fs-7">' + escapeHtml(error.message || "No se pudo consultar API") + '</div></div>';
    }).finally(function () {
      if (boton) boton.disabled = false;
    });
  }

  function renderEstadoHomePublicado(depurar) {
    var node = $("cms_actual_home_estado");
    if (!node) return;
    var hero = bloquesSlotPublicado(depurar, "home.hero");
    var promo = bloquesSlotPublicado(depurar, "home.promo");
    var promosCategoria = bloquesSlotPublicado(depurar, "home.promos");
    var categorias = bloquesSlotPublicado(depurar, "home.categorias");
    var marcas = bloquesSlotPublicado(depurar, "home.marcas");
    var destacados = bloquesSlotPublicado(depurar, "home.destacados");
    var esenciales = bloquesSlotPublicado(depurar, "home.esenciales");
    var compraGuiada = bloquesSlotPublicado(depurar, "home.compra_guiada");
    node.innerHTML = [
      tarjetaEstadoHome("Hero / Banner", hero, "home.hero", "cms_actual_banner_api"),
      tarjetaEstadoHome("Promo", promo, "home.promo", "cms_actual_promo_api"),
      tarjetaEstadoHome("Promos categoria", promosCategoria, "home.promos", "cms_actual_home_promos_api"),
      tarjetaEstadoHome("Categorias", categorias, "home.categorias", "cms_actual_home_categorias_api"),
      tarjetaEstadoHome("Marcas", marcas, "home.marcas", "cms_actual_home_marcas_api"),
      tarjetaEstadoHome("Destacados", destacados, "home.destacados", "cms_actual_home_productos_api"),
      tarjetaEstadoHome("Esenciales", esenciales, "home.esenciales", "cms_actual_home_esenciales_api"),
      tarjetaEstadoHome("Compra guiada", compraGuiada, "home.compra_guiada", "cms_actual_home_compra_guiada_api")
    ].join("");
  }

  function bindEstadoHomePublicado() {
    var node = $("cms_actual_home_estado");
    if (!node) return;
    node.addEventListener("click", function (event) {
      var button = event.target && event.target.closest ? event.target.closest("[data-status-jump]") : null;
      if (!button) return;
      var targetId = button.getAttribute("data-status-jump") || "";
      estado.grupo = "home";
      renderTodo();
      window.setTimeout(function () {
        var target = $(targetId);
        if (target) {
          target.click();
          target.scrollIntoView({ behavior: "smooth", block: "center" });
        }
      }, 50);
    });
  }

  function tarjetaEstadoHome(titulo, bloques, slot, botonId) {
    var total = Array.isArray(bloques) ? bloques.length : 0;
    var publicado = total > 0;
    var codigos = publicado ? bloques.map(function (bloque) { return bloque.codigo || bloque.tipo || "bloque"; }).join(", ") : "Pendiente";
    var clase = publicado ? "badge-light-success" : "badge-light-warning";
    var estadoTexto = publicado ? "Publicado" : "Pendiente";
    return '<div class="cms-actual-status-card">' +
      '<div class="d-flex justify-content-between align-items-start gap-2 mb-3">' +
        '<div class="fw-bold">' + escapeHtml(titulo) + '</div>' +
        '<span class="badge ' + clase + '">' + escapeHtml(estadoTexto) + '</span>' +
      '</div>' +
      '<div class="text-muted fs-8 mb-2">' + escapeHtml(slot) + '</div>' +
      '<div class="fs-7 text-break">' + escapeHtml(codigos) + '</div>' +
      '<button class="btn btn-sm btn-light mt-3" type="button" data-status-jump="' + escapeAttr(botonId) + '"><i class="bi bi-broadcast"></i> Ver detalle</button>' +
    '</div>';
  }

  function cargarBorradorFrontendLocal(usarGrupoGuardado) {
    try {
      var raw = localStorage.getItem(FRONTEND_DRAFT_STORAGE_KEY);
      if (!raw) return false;
      var borrador = JSON.parse(raw);
      if (!borrador || !borrador.datos) return false;
      estado.datos = mergeProfundo(estado.datos, borrador.datos);
      if (usarGrupoGuardado && borrador.grupo) estado.grupo = borrador.grupo;
      estado.borradorLocalCargado = true;
      setText("cms_actual_estado", "Borrador local cargado");
      return true;
    } catch (error) {
      setText("cms_actual_estado", "Borrador local invalido");
      return false;
    }
  }

  function guardarBorradorFrontendLocal(silencioso) {
    try {
      localStorage.setItem(FRONTEND_DRAFT_STORAGE_KEY, JSON.stringify({
        version: "cms_frontend_actual_borrador_2026_08_27",
        grupo: estado.grupo,
        datos: estado.datos,
        actualizado_en: new Date().toISOString()
      }));
      if (!silencioso) setText("cms_actual_estado", "Borrador local guardado");
      return true;
    } catch (error) {
      if (!silencioso) setText("cms_actual_estado", "No se pudo guardar local");
      return false;
    }
  }

  function guardarBorradorGlobalManual() {
    if (guardarBorradorFrontendLocal(false)) {
      setGlobalEstado("Borrador local guardado en este navegador. Para enviarlo al frontend usa Guardar y publicar global.", "success");
    } else {
      setGlobalEstado("No se pudo guardar el borrador local.", "danger");
    }
  }

  function mergeProfundo(base, extra) {
    if (!extra || typeof extra !== "object" || Array.isArray(extra)) return base;
    Object.keys(extra).forEach(function (key) {
      if (Array.isArray(extra[key])) {
        base[key] = extra[key];
      } else if (extra[key] && typeof extra[key] === "object") {
        if (!base[key] || typeof base[key] !== "object" || Array.isArray(base[key])) {
          base[key] = {};
        }
        base[key] = mergeProfundo(base[key], extra[key]);
      } else {
        base[key] = extra[key];
      }
    });
    return base;
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
    if (item.codigo.indexOf("catalogo_") === 0) {
      return renderCatalogoCmsSeccion(item);
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
    if (item.codigo === "home_orden_componentes") {
      return renderHomeOrdenComponentes(item);
    }
    if (item.codigo === "home_promo") {
      return renderPromoHome(item);
    }
    if (item.codigo === "home_promos_categoria") {
      return renderHomePromosCategoria(item);
    }
    if (item.codigo === "home_categorias_destacadas") {
      return renderCategoriasDestacadas(item);
    }
    if (item.codigo === "home_productos_destacados") {
      return renderProductosDestacados(item);
    }
    if (item.codigo === "home_marcas_destacadas") {
      return renderHomeMarcasDestacadas(item);
    }
    if (item.codigo === "home_colecciones") {
      return renderColeccionesProductos(item);
    }
    if (item.codigo === "home_esenciales_artiani") {
      return renderHomeEsencialesArtiani(item);
    }
    if (item.codigo === "home_compra_guiada") {
      return renderCompraGuiadaHome(item);
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
      '<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3"><div class="fw-bold">Slides</div><div class="d-flex gap-2"><button class="btn btn-sm btn-light-primary" type="button" id="cms_actual_hero_borrador"><i class="bi bi-save"></i> Guardar borrador</button><button class="btn btn-sm btn-light-info" type="button" id="cms_actual_hero_api"><i class="bi bi-broadcast"></i> Ver API publicada</button><button class="btn btn-sm btn-primary" type="button" id="cms_actual_hero_publicar"><i class="bi bi-cloud-check"></i> Guardar y publicar hero</button><button class="btn btn-sm btn-light-primary" type="button" id="cms_actual_hero_agregar"><i class="bi bi-plus-circle"></i> Agregar slide</button></div></div>' +
      '<div class="alert alert-light-warning fs-7 py-3 mb-4" id="cms_actual_hero_estado">Pendiente de publicar en la API. Cada slide visible necesita imagen desktop y alt.</div>' +
      '<div class="alert alert-light-secondary fs-7 py-3 mb-4 d-none" id="cms_actual_hero_api_estado"></div>' +
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

  function renderHomeOrdenComponentes(item) {
    var componentes = homeComponentesOrdenables();
    return '<div class="cms-actual-card mb-4">' +
      '<div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-4">' +
        '<div><div class="fw-bold">' + escapeHtml(item.codigo) + '</div><div class="text-muted fs-8">' + escapeHtml(item.descripcion) + '</div></div>' +
        '<span class="badge badge-light-primary">Hero fijo arriba</span>' +
      '</div>' +
      '<div class="alert alert-light-info fs-7 py-3 mb-4">El hero/banner principal no entra en este orden: siempre queda primero. Lo demas se ordena con el campo <strong>orden</strong> que se guarda al publicar cada seccion.</div>' +
      '<div class="table-responsive"><table class="table align-middle table-row-dashed fs-7 mb-0">' +
        '<thead><tr class="text-muted text-uppercase fw-bold"><th>Componente</th><th>Slot</th><th style="width:120px;">Visible</th><th style="width:110px;">Orden</th><th style="width:150px;">Acciones</th></tr></thead>' +
        '<tbody>' + componentes.map(renderHomeOrdenFila).join("") + '</tbody>' +
      '</table></div>' +
      '<div class="alert alert-light-warning fs-7 py-3 mt-4 mb-0">Despues de cambiar el orden, publica las secciones que moviste para que /ecommercePublico/contenido_pagina?pagina=home lo refleje.</div>' +
    '</div>';
  }

  function homeComponentesOrdenables() {
    var defs = [
      ["home_promo", "home.promo", "Promo / avisos"],
      ["home_promos_categoria", "home.promos", "Promos por categoria"],
      ["home_categorias_destacadas", "home.categorias", "Categorias destacadas"],
      ["home_productos_destacados", "home.destacados", "Productos destacados"],
      ["home_marcas_destacadas", "home.marcas", "Marcas por categoria"],
      ["home_colecciones", "home.destacados", "Colecciones de productos"],
      ["home_esenciales_artiani", "home.esenciales", "Esenciales Artiani"],
      ["home_compra_guiada", "home.compra_guiada", "Compra guiada"],
      ["home_banner", "home.hero", "Banner simple legacy"]
    ];
    return defs.map(function (def) {
      return { key: def[0], slot: def[1], nombre: def[2], data: estado.datos.home[def[0]] || {} };
    }).sort(function (a, b) {
      return (parseInt(a.data.orden || "0", 10) || 0) - (parseInt(b.data.orden || "0", 10) || 0);
    });
  }

  function renderHomeOrdenFila(item) {
    var visible = item.data.visible !== false;
    return '<tr>' +
      '<td><div class="fw-semibold">' + escapeHtml(item.nombre) + '</div><div class="text-muted">' + escapeHtml(item.key) + '</div></td>' +
      '<td><span class="badge badge-light">' + escapeHtml(item.slot) + '</span></td>' +
      '<td><select class="form-select form-select-sm" data-home-order-key="' + escapeAttr(item.key) + '" data-home-order-field="visible"><option value="1"' + (visible ? ' selected' : '') + '>Si</option><option value="0"' + (!visible ? ' selected' : '') + '>No</option></select></td>' +
      '<td><input class="form-control form-control-sm" data-home-order-key="' + escapeAttr(item.key) + '" data-home-order-field="orden" value="' + escapeAttr(item.data.orden || 10) + '"></td>' +
      '<td><div class="d-flex gap-2"><button class="btn btn-sm btn-light" type="button" data-home-order-key="' + escapeAttr(item.key) + '" data-home-order-action="subir"><i class="bi bi-arrow-up"></i></button><button class="btn btn-sm btn-light" type="button" data-home-order-key="' + escapeAttr(item.key) + '" data-home-order-action="bajar"><i class="bi bi-arrow-down"></i></button><button class="btn btn-sm btn-light-warning" type="button" data-home-order-key="' + escapeAttr(item.key) + '" data-home-order-action="toggle"><i class="bi ' + (visible ? 'bi-eye-slash' : 'bi-eye') + '"></i></button></div></td>' +
    '</tr>';
  }

  function renderPromoHome(item) {
    var data = promoData();
    var items = data.items || [];
    return '<div class="cms-actual-card mb-4">' +
      '<div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-4">' +
        '<div><div class="fw-bold">' + escapeHtml(item.codigo) + '</div><div class="text-muted fs-8">' + escapeHtml(item.descripcion) + '</div></div>' +
        '<span class="badge badge-light-success">Editor activo</span>' +
      '</div>' +
      '<div class="row g-3 mb-4">' +
        '<div class="col-md-3"><label class="form-label fs-8 fw-bold">Visible</label><select class="form-select form-select-sm" data-promo-config="visible"><option value="1"' + (data.visible ? ' selected' : '') + '>Si</option><option value="0"' + (!data.visible ? ' selected' : '') + '>No</option></select></div>' +
        '<div class="col-md-4"><label class="form-label fs-8 fw-bold">Titulo interno</label><input class="form-control form-control-sm" data-promo-config="titulo" value="' + escapeAttr(data.titulo || "") + '"></div>' +
        '<div class="col-md-3"><label class="form-label fs-8 fw-bold">Variante</label><input class="form-control form-control-sm" data-promo-config="config.variante" value="' + escapeAttr(data.config.variante || "wokiee_promo_strip") + '"></div>' +
        '<div class="col-md-2"><label class="form-label fs-8 fw-bold">Tono</label><select class="form-select form-select-sm" data-promo-config="config.tono"><option value="info"' + (data.config.tono === "info" ? ' selected' : '') + '>Info</option><option value="success"' + (data.config.tono === "success" ? ' selected' : '') + '>Success</option><option value="warning"' + (data.config.tono === "warning" ? ' selected' : '') + '>Warning</option></select></div>' +
      '</div>' +
      '<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3"><div class="fw-bold">Avisos</div><div class="d-flex gap-2"><button class="btn btn-sm btn-light-info" type="button" id="cms_actual_promo_api"><i class="bi bi-broadcast"></i> Ver API publicada</button><button class="btn btn-sm btn-primary" type="button" id="cms_actual_promo_publicar"><i class="bi bi-cloud-check"></i> Guardar y publicar promo</button><button class="btn btn-sm btn-light-primary" type="button" id="cms_actual_promo_agregar"><i class="bi bi-plus-circle"></i> Agregar aviso</button></div></div>' +
      '<div class="alert alert-light-warning fs-7 py-3 mb-4" id="cms_actual_promo_estado">Pendiente de publicar en la API. Captura al menos un aviso visible.</div>' +
      '<div class="alert alert-light-secondary fs-7 py-3 mb-4 d-none" id="cms_actual_promo_api_estado"></div>' +
      items.map(renderPromoItem).join("") +
      '<div class="alert alert-light-info fs-7 mb-0">Usa esta franja para mensajes cortos. No modifica productos, precios ni inventario.</div>' +
    '</div>';
  }

  function renderPromoItem(item, index) {
    return '<div class="cms-actual-slide mb-3" data-promo-item="' + escapeAttr(index) + '">' +
      '<div class="d-flex justify-content-between align-items-center gap-2 mb-3">' +
        '<div><div class="fw-semibold">Aviso ' + escapeHtml(index + 1) + '</div><div class="text-muted fs-8">' + escapeHtml(item.visible ? "Visible" : "Oculto") + '</div></div>' +
        '<div class="d-flex gap-2">' +
          '<button class="btn btn-sm btn-light" type="button" data-promo-action="subir" data-index="' + escapeAttr(index) + '"><i class="bi bi-arrow-up"></i></button>' +
          '<button class="btn btn-sm btn-light" type="button" data-promo-action="bajar" data-index="' + escapeAttr(index) + '"><i class="bi bi-arrow-down"></i></button>' +
          '<button class="btn btn-sm btn-light-warning" type="button" data-promo-action="toggle" data-index="' + escapeAttr(index) + '"><i class="bi ' + (item.visible ? 'bi-eye-slash' : 'bi-eye') + '"></i></button>' +
          '<button class="btn btn-sm btn-light-danger" type="button" data-promo-action="eliminar" data-index="' + escapeAttr(index) + '"><i class="bi bi-trash"></i></button>' +
        '</div>' +
      '</div>' +
      '<div class="row g-3">' +
        inputPromo(index, "icono", "Icono", item.icono, "col-md-3") +
        inputPromo(index, "texto", "Texto visible", item.texto, "col-md-5") +
        inputPromo(index, "cta.label", "CTA texto", (item.cta || {}).label, "col-md-2") +
        inputPromo(index, "cta.url", "CTA URL", (item.cta || {}).url, "col-md-2") +
      '</div>' +
    '</div>';
  }

  function inputPromo(index, campo, label, value, col) {
    return '<div class="' + escapeAttr(col || "col-md-4") + '"><label class="form-label fs-8 fw-bold">' + escapeHtml(label) + '</label><input class="form-control form-control-sm" data-promo-field="' + escapeAttr(campo) + '" data-index="' + escapeAttr(index) + '" value="' + escapeAttr(value == null ? "" : value) + '"></div>';
  }

  function renderCatalogoCmsSeccion(item) {
    var data = catalogoData(item.codigo);
    if (!data) return "";
    return '<div class="cms-actual-card mb-4">' +
      '<div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-4">' +
        '<div><div class="fw-bold">' + escapeHtml(item.descripcion) + '</div><div class="text-muted fs-8">' + escapeHtml(item.codigo) + ' / catalogo.encabezado</div></div>' +
        '<span class="badge badge-light-success">Editor activo</span>' +
      '</div>' +
      '<div class="row g-3 mb-4">' +
        inputCatalogo("visible", "Visible 1/0", data.visible ? "1" : "0", "col-md-2") +
        inputCatalogo("orden", "Orden", data.orden, "col-md-2") +
        inputCatalogo("titulo", "Titulo", data.titulo, "col-md-4") +
        inputCatalogo("subtitulo", "Subtitulo", data.subtitulo, "col-md-4") +
        '<div class="col-12"><label class="form-label fs-8 fw-bold">Texto superior</label><textarea class="form-control form-control-sm" rows="4" data-catalogo-field="contenido_html">' + escapeHtml(data.contenido_html || "") + '</textarea><div class="text-muted fs-8 mt-1">HTML permitido al publicar: p, br, strong, b, em, i, ul, ol, li.</div></div>' +
        inputCatalogo("cta.label", "CTA texto", data.cta ? data.cta.label : "", "col-md-3") +
        inputCatalogo("cta.url", "CTA URL", data.cta ? data.cta.url : "", "col-md-3") +
        inputCatalogo("seo.title", "SEO title", data.seo ? data.seo.title : "", "col-md-3") +
        inputCatalogo("seo.description", "SEO description", data.seo ? data.seo.description : "", "col-md-3") +
        inputCatalogo("estados.sin_resultados_titulo", "Titulo sin resultados", data.estados ? data.estados.sin_resultados_titulo : "", "col-md-4") +
        inputCatalogo("estados.sin_resultados_texto", "Texto sin resultados", data.estados ? data.estados.sin_resultados_texto : "", "col-md-5") +
        inputCatalogo("config.variante", "Variante frontend", data.config ? data.config.variante : "", "col-md-3") +
      '</div>' +
      '<div class="cms-actual-slide mb-4">' +
        '<div class="text-uppercase fs-8 fw-bold text-muted mb-2">Preview administrativo</div>' +
        '<h2 class="fw-bold mb-2">' + escapeHtml(data.titulo || "Catalogo") + '</h2>' +
        '<div class="text-muted mb-3">' + escapeHtml(data.subtitulo || "") + '</div>' +
        '<div class="fs-7">' + escapeHtml(String(data.contenido_html || "").replace(/<[^>]+>/g, " ")) + '</div>' +
      '</div>' +
      '<div class="d-flex gap-2 flex-wrap mb-3">' +
        '<button class="btn btn-sm btn-light-primary" type="button" id="cms_actual_catalogo_borrador"><i class="bi bi-save"></i> Guardar borrador</button>' +
        '<button class="btn btn-sm btn-light-info" type="button" id="cms_actual_catalogo_api"><i class="bi bi-broadcast"></i> Ver API publicada</button>' +
        '<button class="btn btn-sm btn-primary" type="button" id="cms_actual_catalogo_publicar"><i class="bi bi-cloud-check"></i> Guardar y publicar catalogo</button>' +
      '</div>' +
      '<div class="alert alert-light-warning fs-7 py-3 mb-4" id="cms_actual_catalogo_estado">Pendiente de publicar en la API. Esto solo controla textos del catalogo, no productos ni filtros.</div>' +
      '<div class="alert alert-light-secondary fs-7 py-3 mb-0 d-none" id="cms_actual_catalogo_api_estado"></div>' +
    '</div>';
  }

  function inputCatalogo(campo, label, value, col) {
    return '<div class="' + escapeAttr(col || "col-md-4") + '"><label class="form-label fs-8 fw-bold">' + escapeHtml(label) + '</label><input class="form-control form-control-sm" data-catalogo-field="' + escapeAttr(campo) + '" value="' + escapeAttr(value == null ? "" : value) + '"></div>';
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
    on("cms_actual_hero_borrador", "click", function () {
      if (guardarBorradorFrontendLocal(false)) setHeroEstado("Borrador local guardado. Puedes publicar cuando las imagenes vengan de Media CMS.", "success");
    });
    on("cms_actual_hero_publicar", "click", publicarHeroCarrusel);
    on("cms_actual_hero_api", "click", consultarApiHeroCarrusel);
    Array.prototype.forEach.call(document.querySelectorAll("[data-promo-config]"), function (node) {
      node.addEventListener("input", function () {
        actualizarPromoConfig(node.getAttribute("data-promo-config"), node.value);
      });
      node.addEventListener("change", function () {
        actualizarPromoConfig(node.getAttribute("data-promo-config"), node.value);
      });
    });
    Array.prototype.forEach.call(document.querySelectorAll("[data-promo-field]"), function (node) {
      node.addEventListener("input", function () {
        actualizarPromoItem(parseInt(node.getAttribute("data-index") || "0", 10), node.getAttribute("data-promo-field"), node.value);
      });
      node.addEventListener("change", function () {
        actualizarPromoItem(parseInt(node.getAttribute("data-index") || "0", 10), node.getAttribute("data-promo-field"), node.value);
      });
    });
    Array.prototype.forEach.call(document.querySelectorAll("[data-promo-action]"), function (button) {
      button.addEventListener("click", function () {
        ejecutarPromoAccion(button.getAttribute("data-promo-action"), parseInt(button.getAttribute("data-index") || "0", 10));
      });
    });
    on("cms_actual_promo_agregar", "click", agregarPromoItem);
    on("cms_actual_promo_publicar", "click", publicarPromoHome);
    on("cms_actual_promo_api", "click", consultarApiPromoHome);
    Array.prototype.forEach.call(document.querySelectorAll("[data-home-list-config]"), function (node) {
      node.addEventListener("input", function () {
        actualizarHomeListConfig(node.getAttribute("data-home-list-config"), node.value);
      });
      node.addEventListener("change", function () {
        actualizarHomeListConfig(node.getAttribute("data-home-list-config"), node.value);
      });
    });
    Array.prototype.forEach.call(document.querySelectorAll("[data-home-list-field]"), function (node) {
      node.addEventListener("input", function () {
        actualizarHomeListItem(node.getAttribute("data-home-list-context"), parseInt(node.getAttribute("data-index") || "0", 10), node.getAttribute("data-home-list-field"), node.value);
      });
      node.addEventListener("change", function () {
        actualizarHomeListItem(node.getAttribute("data-home-list-context"), parseInt(node.getAttribute("data-index") || "0", 10), node.getAttribute("data-home-list-field"), node.value);
      });
    });
    Array.prototype.forEach.call(document.querySelectorAll("[data-home-list-action]"), function (button) {
      button.addEventListener("click", function () {
        ejecutarHomeListAccion(button.getAttribute("data-home-list-context"), button.getAttribute("data-home-list-action"), parseInt(button.getAttribute("data-index") || "0", 10));
      });
    });
    Array.prototype.forEach.call(document.querySelectorAll("[data-home-order-field]"), function (node) {
      node.addEventListener("input", function () {
        actualizarHomeOrdenField(node.getAttribute("data-home-order-key") || "", node.getAttribute("data-home-order-field") || "", node.value);
      });
      node.addEventListener("change", function () {
        actualizarHomeOrdenField(node.getAttribute("data-home-order-key") || "", node.getAttribute("data-home-order-field") || "", node.value);
      });
    });
    Array.prototype.forEach.call(document.querySelectorAll("[data-home-order-action]"), function (button) {
      button.addEventListener("click", function () {
        ejecutarHomeOrdenAccion(button.getAttribute("data-home-order-key") || "", button.getAttribute("data-home-order-action") || "");
      });
    });
    Array.prototype.forEach.call(document.querySelectorAll("[data-esenciales-config]"), function (node) {
      node.addEventListener("input", function () {
        actualizarEsencialesConfig(node.getAttribute("data-esenciales-config"), node.value);
      });
      node.addEventListener("change", function () {
        actualizarEsencialesConfig(node.getAttribute("data-esenciales-config"), node.value);
      });
    });
    Array.prototype.forEach.call(document.querySelectorAll("[data-esencial-principal-field]"), function (node) {
      node.addEventListener("input", function () {
        actualizarEsencialPrincipal(node.getAttribute("data-esencial-principal-field"), node.value);
      });
      node.addEventListener("change", function () {
        actualizarEsencialPrincipal(node.getAttribute("data-esencial-principal-field"), node.value);
      });
    });
    Array.prototype.forEach.call(document.querySelectorAll("[data-esencial-categoria-select]"), function (node) {
      node.addEventListener("change", function () {
        seleccionarCategoriaEsencialPrincipal(node.value);
      });
    });
    Array.prototype.forEach.call(document.querySelectorAll("[data-home-category-select]"), function (node) {
      node.addEventListener("change", function () {
        seleccionarCategoriaHomeLista(node.getAttribute("data-home-category-select"), parseInt(node.getAttribute("data-index") || "0", 10), node.value);
      });
    });
    Array.prototype.forEach.call(document.querySelectorAll("[data-home-marcas-category-select]"), function (node) {
      node.addEventListener("change", function () {
        seleccionarCategoriaHomeMarcas(node.value);
      });
    });
    Array.prototype.forEach.call(document.querySelectorAll("[data-home-marca-select]"), function (node) {
      node.addEventListener("change", function () {
        seleccionarMarcaHomeManual(parseInt(node.getAttribute("data-index") || "0", 10), node.value);
      });
    });
    Array.prototype.forEach.call(document.querySelectorAll("[data-use-category-image]"), function (button) {
      button.addEventListener("click", function () {
        usarImagenCategoriaEsenciales(button.getAttribute("data-use-category-image"), parseInt(button.getAttribute("data-index") || "0", 10));
      });
    });
    Array.prototype.forEach.call(document.querySelectorAll("[data-home-section-draft]"), function (button) {
      button.addEventListener("click", function () {
        guardarBorradorSeccion(button.getAttribute("data-home-section-draft") || "home");
      });
    });
    on("cms_actual_home_promo_categoria_agregar", "click", agregarHomePromoCategoria);
    on("cms_actual_home_promos_publicar", "click", publicarHomePromosCategoria);
    on("cms_actual_home_promos_api", "click", consultarApiHomePromosCategoria);
    on("cms_actual_home_marca_agregar", "click", agregarHomeMarca);
    on("cms_actual_home_marcas_publicar", "click", publicarHomeMarcas);
    on("cms_actual_home_marcas_api", "click", consultarApiHomeMarcas);
    on("cms_actual_home_marcas_preview", "click", consultarPreviewHomeMarcas);
    var previewMarcas = $("cms_actual_home_marcas_preview_lista");
    if (previewMarcas) {
      previewMarcas.addEventListener("click", function (event) {
        var button = event.target.closest("[data-home-marca-preview-add]");
        if (!button) return;
        agregarMarcaDesdePreview(parseInt(button.getAttribute("data-home-marca-preview-add") || "0", 10));
      });
    }
    on("cms_actual_home_esencial_agregar", "click", agregarHomeEsencial);
    on("cms_actual_home_esenciales_publicar", "click", publicarHomeEsenciales);
    on("cms_actual_home_esenciales_api", "click", consultarApiHomeEsenciales);
    Array.prototype.forEach.call(document.querySelectorAll("[data-compra-guiada-field]"), function (node) {
      node.addEventListener("input", function () {
        actualizarCompraGuiadaField(node.getAttribute("data-compra-guiada-field"), node.value);
      });
      node.addEventListener("change", function () {
        actualizarCompraGuiadaField(node.getAttribute("data-compra-guiada-field"), node.value);
      });
    });
    Array.prototype.forEach.call(document.querySelectorAll("[data-compra-guiada-config]"), function (node) {
      node.addEventListener("input", function () {
        actualizarCompraGuiadaConfig(node.getAttribute("data-compra-guiada-config"), node.value);
      });
      node.addEventListener("change", function () {
        actualizarCompraGuiadaConfig(node.getAttribute("data-compra-guiada-config"), node.value);
      });
    });
    on("cms_actual_home_compra_guiada_borrador", "click", guardarBorradorCompraGuiadaHome);
    on("cms_actual_home_compra_guiada_publicar", "click", publicarCompraGuiadaHome);
    on("cms_actual_home_compra_guiada_api", "click", consultarApiCompraGuiadaHome);
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
    on("cms_actual_home_categoria_agregar", "click", agregarCategoriaItem);
    on("cms_actual_home_categorias_publicar", "click", publicarHomeCategorias);
    on("cms_actual_home_categorias_api", "click", consultarApiHomeCategorias);
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
    on("cms_actual_home_productos_publicar", "click", publicarHomeProductos);
    on("cms_actual_home_productos_api", "click", consultarApiHomeProductos);
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
    on("cms_actual_home_colecciones_publicar", "click", publicarHomeColecciones);
    on("cms_actual_home_colecciones_api", "click", consultarApiHomeColecciones);
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
    on("cms_actual_banner_publicar", "click", publicarBannerHome);
    on("cms_actual_banner_api", "click", consultarApiBannerHome);
    Array.prototype.forEach.call(document.querySelectorAll("[data-global-field]"), function (node) {
      node.addEventListener("input", function () {
        actualizarGlobalField(node.getAttribute("data-global-section"), node.getAttribute("data-global-field"), node.value);
      });
      node.addEventListener("change", function () {
        actualizarGlobalField(node.getAttribute("data-global-section"), node.getAttribute("data-global-field"), node.value);
      });
    });
    on("cms_actual_global_borrador", "click", guardarBorradorGlobalManual);
    on("cms_actual_global_cargar_publicado", "click", function () { cargarGlobalPublicadoFrontend(true); });
    on("cms_actual_global_api", "click", consultarApiGlobalFrontend);
    on("cms_actual_global_publicar", "click", publicarGlobalFrontend);
    on("cms_actual_global_whatsapp_agregar", "click", agregarWhatsappContacto);
    on("cms_actual_global_whatsapp_publicar", "click", publicarGlobalWhatsapp);
    on("cms_actual_global_whatsapp_api", "click", consultarApiGlobalWhatsapp);
    Array.prototype.forEach.call(document.querySelectorAll("[data-global-whatsapp-field]"), function (node) {
      node.addEventListener("input", function () {
        actualizarWhatsappGlobal(node.getAttribute("data-global-whatsapp-field"), node.value);
      });
      node.addEventListener("change", function () {
        actualizarWhatsappGlobal(node.getAttribute("data-global-whatsapp-field"), node.value);
      });
    });
    Array.prototype.forEach.call(document.querySelectorAll("[data-global-whatsapp-contacto-field]"), function (node) {
      node.addEventListener("input", function () {
        actualizarWhatsappContacto(parseInt(node.getAttribute("data-index") || "0", 10), node.getAttribute("data-global-whatsapp-contacto-field"), node.value);
      });
      node.addEventListener("change", function () {
        actualizarWhatsappContacto(parseInt(node.getAttribute("data-index") || "0", 10), node.getAttribute("data-global-whatsapp-contacto-field"), node.value);
      });
    });
    Array.prototype.forEach.call(document.querySelectorAll("[data-global-whatsapp-contacto-action]"), function (button) {
      button.addEventListener("click", function () {
        ejecutarWhatsappContactoAccion(button.getAttribute("data-global-whatsapp-contacto-action") || "", parseInt(button.getAttribute("data-index") || "0", 10));
      });
    });
    Array.prototype.forEach.call(document.querySelectorAll("[data-catalogo-field]"), function (node) {
      node.addEventListener("input", function () {
        actualizarCatalogoField(node.getAttribute("data-catalogo-field"), node.value);
      });
      node.addEventListener("change", function () {
        actualizarCatalogoField(node.getAttribute("data-catalogo-field"), node.value);
      });
    });
    on("cms_actual_catalogo_borrador", "click", guardarBorradorCatalogo);
    on("cms_actual_catalogo_publicar", "click", publicarCatalogoFrontend);
    on("cms_actual_catalogo_api", "click", consultarApiCatalogoFrontend);
    on("cms_actual_categorias_publicar", "click", publicarCategoriasFrontend);
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
    Array.prototype.forEach.call(document.querySelectorAll("[data-cms-category-select]"), function (node) {
      node.addEventListener("change", function () {
        seleccionarCategoriaCmsItem(parseInt(node.getAttribute("data-index") || "0", 10), node.value);
      });
    });
    Array.prototype.forEach.call(document.querySelectorAll("[data-cms-cat-use-image]"), function (button) {
      button.addEventListener("click", function () {
        usarImagenCategoriaCmsItem(parseInt(button.getAttribute("data-index") || "0", 10), button.getAttribute("data-field") || "imagen_card");
      });
    });
    on("cms_actual_categorias_borrador", "click", guardarBorradorCategoriasCms);
    on("cms_actual_categoria_agregar", "click", agregarCategoriaCmsItem);
    on("cms_actual_marcas_publicar", "click", publicarMarcasFrontend);
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
          selectorCategoriaCmsItem(index, item) +
          botonUsarImagenCategoriaCmsItem(index, "imagen_card", item) +
          botonUsarImagenCategoriaCmsItem(index, "imagen_banner", item) +
          inputCategoriaItem(index, "categoria_id", "ID ERP", item.categoria_id, "col-md-2") +
          inputCategoriaItem(index, "slug", "Slug", item.slug, "col-md-3") +
          inputCategoriaItem(index, "titulo", "Titulo", item.titulo, "col-md-3") +
          inputCategoriaItem(index, "url", "URL publica", item.url, "col-md-4") +
          inputCategoriaItem(index, "subtitulo", "Subtitulo", item.subtitulo, "col-md-6") +
          inputCategoriaItem(index, "descripcion_seo", "Descripcion SEO", item.descripcion_seo, "col-md-6") +
          inputCategoriaItem(index, "imagen_card", "Imagen card", item.imagen_card, "col-md-6", true) +
          inputCategoriaItem(index, "imagen_banner", "Banner principal ecommerce", item.imagen_banner, "col-md-6", true) +
          inputCategoriaItem(index, "alt_card", "Alt card", item.alt_card, "col-md-6") +
          inputCategoriaItem(index, "alt_banner", "Alt banner", item.alt_banner, "col-md-6") +
          '<div class="col-md-3"><label class="form-label fs-8 fw-bold">Heredar banner</label><select class="form-select form-select-sm" data-cms-cat-section="categorias_items" data-cms-cat-field="items.' + escapeAttr(index) + '.heredar_banner"><option value="1"' + (item.heredar_banner !== false ? ' selected' : '') + '>Si</option><option value="0"' + (item.heredar_banner === false ? ' selected' : '') + '>No</option></select><div class="text-muted fs-8 mt-1">Si no tiene banner propio, usa el primer banner de su jerarquia padre.</div></div>' +
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

  function selectorCategoriaCmsItem(index, item) {
    return '<div class="col-md-12">' +
      '<label class="form-label fs-8 fw-bold">Seleccionar categoria real</label>' +
      '<select class="form-select form-select-sm" data-cms-category-select="categorias_items" data-index="' + escapeAttr(index) + '">' +
        opcionesCategoriasCms(item && item.categoria_id ? item.categoria_id : 0) +
      '</select>' +
      '<div class="text-muted fs-8 mt-1">Al seleccionar se completan ID ERP, titulo, slug, URL publica y alt. Despues puedes ajustar el texto comercial.</div>' +
    '</div>';
  }

  function botonUsarImagenCategoriaCmsItem(index, campo, item) {
    var categoria = categoriaCmsPorId(item && item.categoria_id ? item.categoria_id : 0);
    var imagen = campo === "imagen_banner" ? imagenBannerCategoriaCms(categoria) : imagenCardCategoriaCms(categoria);
    var texto = campo === "imagen_banner" ? "Usar banner de categoria" : "Usar card de categoria";
    var ayuda = imagen ? "Copia la imagen editorial de la categoria seleccionada." : "La categoria no tiene imagen disponible; puedes subir una desde Media.";
    return '<div class="col-md-6">' +
      '<button class="btn btn-sm ' + (imagen ? "btn-light-success" : "btn-light-secondary") + '" type="button" data-cms-cat-use-image="' + escapeAttr(index) + '" data-index="' + escapeAttr(index) + '" data-field="' + escapeAttr(campo) + '"' + (!imagen ? ' disabled' : '') + '><i class="bi bi-image"></i> ' + escapeHtml(texto) + '</button>' +
      '<div class="text-muted fs-8 mt-1">' + escapeHtml(ayuda) + '</div>' +
    '</div>';
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
      '<div class="fw-bold">Marcas editoriales</div><div class="d-flex gap-2"><button class="btn btn-sm btn-primary" type="button" id="cms_actual_marcas_publicar"><i class="bi bi-cloud-check"></i> Guardar y publicar marcas</button><button class="btn btn-sm btn-light-primary" type="button" id="cms_actual_marca_agregar"><i class="bi bi-plus-circle"></i> Agregar marca</button></div>' +
    '</div>' +
    '<div class="alert alert-light-warning fs-7 py-3 mb-4" id="cms_actual_marcas_estado">Pendiente de publicar. Cada marca visible necesita ID ERP o slug; logo/banner deben venir de Media CMS.</div>' +
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
    if (item.codigo === "global_whatsapp_chat") {
      return renderGlobalWhatsappChat(item, data);
    }
    var campos = camposGlobal(item.codigo, data);
    return '<div class="cms-actual-card mb-4">' +
      '<div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-4">' +
        '<div><div class="fw-bold">' + escapeHtml(item.codigo) + '</div><div class="text-muted fs-8">' + escapeHtml(item.descripcion) + '</div></div>' +
        '<span class="badge badge-light-success">Editor activo</span>' +
      '</div>' +
      '<div class="row g-3">' + campos.join("") + '</div>' +
    '</div>';
  }

  function renderGlobalWhatsappChat(item, data) {
    var contactos = Array.isArray(data.contactos) ? data.contactos : [];
    return '<div class="cms-actual-card mb-4">' +
      '<div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-4">' +
        '<div><div class="fw-bold">' + escapeHtml(item.codigo) + '</div><div class="text-muted fs-8">' + escapeHtml(item.descripcion) + '</div></div>' +
        '<span class="badge badge-light-success">Editor activo</span>' +
      '</div>' +
      '<div class="row g-3 mb-4">' +
        selectWhatsappGlobal("visible", "Modulo activo", data.visible ? "1" : "0", [["1", "Si"], ["0", "No"]], "col-md-2") +
        inputWhatsappGlobal("titulo", "Titulo", data.titulo, "col-md-3") +
        inputWhatsappGlobal("subtitulo", "Subtitulo", data.subtitulo, "col-md-5") +
        inputWhatsappGlobal("orden", "Orden", data.orden, "col-md-2") +
        inputWhatsappGlobal("boton.label", "Texto boton", (data.boton || {}).label, "col-md-3") +
        inputWhatsappGlobal("mensaje_default", "Mensaje default", data.mensaje_default, "col-md-6") +
        selectWhatsappGlobal("config.posicion", "Posicion", (data.config || {}).posicion || "bottom_right", [["bottom_right", "Abajo derecha"], ["bottom_left", "Abajo izquierda"]], "col-md-3") +
        selectWhatsappGlobal("config.mostrar_en_mobile", "Mobile", (data.config || {}).mostrar_en_mobile ? "1" : "0", [["1", "Mostrar"], ["0", "Ocultar"]], "col-md-3") +
        selectWhatsappGlobal("config.mostrar_en_desktop", "Desktop", (data.config || {}).mostrar_en_desktop ? "1" : "0", [["1", "Mostrar"], ["0", "Ocultar"]], "col-md-3") +
        selectWhatsappGlobal("config.abrir_en_nueva_pestana", "Nueva pestana", (data.config || {}).abrir_en_nueva_pestana ? "1" : "0", [["1", "Si"], ["0", "No"]], "col-md-3") +
        selectWhatsappGlobal("config.mostrar_horario", "Horario", (data.config || {}).mostrar_horario ? "1" : "0", [["1", "Mostrar"], ["0", "Ocultar"]], "col-md-3") +
      '</div>' +
      '<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">' +
        '<div><div class="fw-bold">Contactos WhatsApp</div><div class="text-muted fs-8">Telefono en formato internacional sin espacios. Ejemplo Mexico: 521XXXXXXXXXX.</div></div>' +
        '<div class="d-flex flex-wrap gap-2"><button class="btn btn-sm btn-light-info" type="button" id="cms_actual_global_whatsapp_api"><i class="bi bi-broadcast"></i> Ver API global</button><button class="btn btn-sm btn-primary" type="button" id="cms_actual_global_whatsapp_publicar"><i class="bi bi-cloud-check"></i> Guardar y publicar WhatsApp</button><button class="btn btn-sm btn-light-primary" type="button" id="cms_actual_global_whatsapp_agregar"><i class="bi bi-plus-circle"></i> Agregar contacto</button></div>' +
      '</div>' +
      '<div class="alert alert-light-warning fs-7 py-3 mb-4" id="cms_actual_global_whatsapp_estado">Pendiente de publicar en /ecommercePublico/contenido_pagina?pagina=global.</div>' +
      '<div class="alert alert-light-secondary fs-7 py-3 mb-4 d-none" id="cms_actual_global_whatsapp_api_estado"></div>' +
      contactos.map(renderWhatsappContacto).join("") +
      '<div class="alert alert-light-info fs-7 mb-0">Avatar recomendado: WebP o PNG cuadrado 400x400. Puede quedar vacio; frontend usara iniciales o icono WhatsApp.</div>' +
    '</div>';
  }

  function inputWhatsappGlobal(campo, label, value, col) {
    return '<div class="' + escapeAttr(col || "col-md-4") + '"><label class="form-label fs-8 fw-bold">' + escapeHtml(label) + '</label><input class="form-control form-control-sm" data-global-whatsapp-field="' + escapeAttr(campo) + '" value="' + escapeAttr(value == null ? "" : value) + '"></div>';
  }

  function selectWhatsappGlobal(campo, label, value, opciones, col) {
    return '<div class="' + escapeAttr(col || "col-md-3") + '"><label class="form-label fs-8 fw-bold">' + escapeHtml(label) + '</label><select class="form-select form-select-sm" data-global-whatsapp-field="' + escapeAttr(campo) + '">' + opciones.map(function (opcion) {
      return '<option value="' + escapeAttr(opcion[0]) + '"' + (String(value) === String(opcion[0]) ? ' selected' : '') + '>' + escapeHtml(opcion[1]) + '</option>';
    }).join("") + '</select></div>';
  }

  function renderWhatsappContacto(contacto, index) {
    var avatar = contacto.avatar ? '<img src="' + escapeAttr(urlPreviewSeguro(contacto.avatar)) + '" alt="" style="width:54px;height:54px;object-fit:cover;border-radius:8px;border:1px solid #e7e9ef;">' : '<div class="d-flex align-items-center justify-content-center bg-light-success text-success fw-bold" style="width:54px;height:54px;border-radius:8px;">WA</div>';
    return '<div class="cms-actual-slide mb-3">' +
      '<div class="d-flex justify-content-between align-items-center gap-3 mb-3">' +
        '<div class="d-flex align-items-center gap-3">' + avatar + '<div><div class="fw-semibold">' + escapeHtml(contacto.nombre || "Contacto") + '</div><div class="text-muted fs-8">' + escapeHtml(contacto.visible ? "Visible" : "Oculto") + '</div></div></div>' +
        '<div class="d-flex gap-2">' +
          '<button class="btn btn-sm btn-light" type="button" data-global-whatsapp-contacto-action="subir" data-index="' + escapeAttr(index) + '"><i class="bi bi-arrow-up"></i></button>' +
          '<button class="btn btn-sm btn-light" type="button" data-global-whatsapp-contacto-action="bajar" data-index="' + escapeAttr(index) + '"><i class="bi bi-arrow-down"></i></button>' +
          '<button class="btn btn-sm btn-light-warning" type="button" data-global-whatsapp-contacto-action="toggle" data-index="' + escapeAttr(index) + '"><i class="bi ' + (contacto.visible ? 'bi-eye-slash' : 'bi-eye') + '"></i></button>' +
          '<button class="btn btn-sm btn-light-danger" type="button" data-global-whatsapp-contacto-action="eliminar" data-index="' + escapeAttr(index) + '"><i class="bi bi-trash"></i></button>' +
        '</div>' +
      '</div>' +
      '<div class="row g-3">' +
        inputWhatsappContacto(index, "id", "ID publico", contacto.id, "col-md-3") +
        inputWhatsappContacto(index, "nombre", "Nombre obligatorio", contacto.nombre, "col-md-3") +
        inputWhatsappContacto(index, "telefono", "Telefono obligatorio", contacto.telefono, "col-md-3") +
        inputWhatsappContacto(index, "orden", "Orden", contacto.orden, "col-md-3") +
        inputWhatsappContacto(index, "descripcion", "Descripcion", contacto.descripcion, "col-md-6") +
        inputWhatsappContacto(index, "mensaje", "Mensaje personalizado", contacto.mensaje, "col-md-6") +
        inputWhatsappContacto(index, "avatar", "Avatar", contacto.avatar, "col-md-6", true) +
        inputWhatsappContacto(index, "horario", "Horario", contacto.horario, "col-md-6") +
      '</div>' +
    '</div>';
  }

  function inputWhatsappContacto(index, campo, label, value, col, media) {
    var input = '<input class="form-control form-control-sm" data-index="' + escapeAttr(index) + '" data-global-whatsapp-contacto-field="' + escapeAttr(campo) + '" value="' + escapeAttr(value == null ? "" : value) + '">';
    if (media) {
      input = '<div class="input-group input-group-sm">' + input + '<button class="btn btn-light-primary" type="button" data-media-picker="global_whatsapp_contacto" data-index="' + escapeAttr(index) + '" data-field="' + escapeAttr(campo) + '"><i class="bi bi-images"></i> Media</button></div>';
    }
    return '<div class="' + escapeAttr(col || "col-md-4") + '"><label class="form-label fs-8 fw-bold">' + escapeHtml(label) + '</label>' + input + '</div>';
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
        '<div class="col-12"><div class="alert alert-light-info fs-7 mb-0">Google Maps: puedes pegar el iframe completo de Google o solo el URL del atributo src. El CMS guardara el embed URL limpio.</div></div>',
        inputGlobal(codigo, "direccion.calle", "Calle", data.direccion.calle, "col-md-4"),
        inputGlobal(codigo, "direccion.colonia", "Colonia", data.direccion.colonia, "col-md-4"),
        inputGlobal(codigo, "direccion.ciudad", "Ciudad", data.direccion.ciudad, "col-md-4"),
        inputGlobal(codigo, "direccion.estado", "Estado", data.direccion.estado, "col-md-3"),
        inputGlobal(codigo, "direccion.codigo_postal", "Codigo postal", data.direccion.codigo_postal, "col-md-3"),
        inputGlobal(codigo, "direccion.pais", "Pais", data.direccion.pais, "col-md-3"),
        inputGlobal(codigo, "direccion.texto_publico", "Texto publico", data.direccion.texto_publico, "col-md-3"),
        inputGlobal(codigo, "mapa.google_maps_url", "Google Maps URL", data.mapa.google_maps_url, "col-md-6"),
        inputGlobal(codigo, "mapa.embed_url", "Embed URL o iframe", data.mapa.embed_url, "col-md-6"),
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
    if (seccionCodigo === "global_ubicacion" && campo === "mapa.embed_url") {
      valor = extraerGoogleMapsEmbed(valor);
    }
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

  function extraerGoogleMapsEmbed(valor) {
    var texto = String(valor || "").trim();
    if (!texto) return "";
    var matchSrc = texto.match(/\ssrc=["']([^"']+)["']/i);
    if (matchSrc && matchSrc[1]) {
      return matchSrc[1].replace(/&amp;/g, "&").trim();
    }
    return texto.replace(/&amp;/g, "&");
  }

  function cargarGlobalPublicadoFrontend(forzar) {
    var boton = $("cms_actual_global_cargar_publicado");
    if (boton) boton.disabled = true;
    if (forzar) setGlobalEstado("Consultando configuracion_inicial...", "info");
    fetch("/ecommercePublico/configuracion_inicial", {
      method: "GET",
      credentials: "same-origin",
      headers: {
        "Accept": "application/json",
        "X-Requested-With": "XMLHttpRequest"
      }
    }).then(function (response) {
      return response.text().then(function (text) {
        var json = null;
        try {
          json = JSON.parse(text);
        } catch (error) {
          throw new Error("Respuesta no JSON del servidor (" + response.status + ")");
        }
        if (!response.ok) throw new Error((json && json.mensaje) || "No se pudo consultar configuracion_inicial");
        return json;
      });
    }).then(function (json) {
      var publicado = json && json.depurar ? json.depurar.cms_global : null;
      if (!publicado || publicado.fuente !== "bd_publicada") {
        if (forzar) setGlobalEstado("No hay Global publicado en BD todavia.", "warning");
        return;
      }
      aplicarGlobalPublicado(publicado);
      guardarBorradorFrontendLocal(true);
      if (estado.grupo === "global" || forzar) renderTodo();
      setGlobalEstado("Global publicado cargado desde configuracion_inicial.", "success");
    }).catch(function (error) {
      if (forzar) setGlobalEstado(error.message || "No se pudo cargar Global publicado.", "danger");
    }).finally(function () {
      if (boton) boton.disabled = false;
    });
  }

  function aplicarGlobalPublicado(publicado) {
    if (!publicado || typeof publicado !== "object") return;
    var global = estado.datos.global || {};
    if (publicado.negocio) global.global_negocio = mergeProfundo(global.global_negocio || {}, publicado.negocio);
    if (publicado.direccion || publicado.mapa) {
      global.global_ubicacion = global.global_ubicacion || {};
      global.global_ubicacion.direccion = mergeProfundo(global.global_ubicacion.direccion || {}, publicado.direccion || {});
      global.global_ubicacion.mapa = mergeProfundo(global.global_ubicacion.mapa || {}, publicado.mapa || {});
    }
    if (Array.isArray(publicado.horarios)) {
      global.global_horarios = global.global_horarios || {};
      global.global_horarios.items = publicado.horarios;
    }
    if (publicado.redes_sociales) global.global_redes = mergeProfundo(global.global_redes || {}, publicado.redes_sociales);
    if (publicado.seo_global) global.global_seo = mergeProfundo(global.global_seo || {}, publicado.seo_global);
    if (publicado.navegacion) global.global_navegacion = mergeProfundo(global.global_navegacion || {}, publicado.navegacion);
    if (publicado.whatsapp_chat) global.global_whatsapp_chat = mergeProfundo(global.global_whatsapp_chat || whatsappGlobalDefault(), publicado.whatsapp_chat);
    estado.datos.global = global;
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
    if (campo === "mostrar_en_home" || campo === "mostrar_en_menu" || campo.indexOf(".visible") !== -1 || campo.indexOf(".destacado") !== -1 || campo.indexOf(".heredar_banner") !== -1) {
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
      heredar_banner: true,
      destacado: false,
      visible: true,
      orden: (items.length + 1) * 10,
      url: "/categoria/nueva-categoria"
    });
    renderGrupo();
  }

  function seleccionarCategoriaCmsItem(index, idCategoria) {
    var categoria = categoriaCmsPorId(idCategoria);
    var data = categoriasCmsData("categorias_items");
    var item = data && data.items ? data.items[index] : null;
    if (!categoria || !item) return;
    aplicarCategoriaEditorialCms(item, categoria, true);
    setCategoriasEstado("Categoria real aplicada al borrador. Puedes ajustar textos o imagenes antes de publicar.", "success");
    renderGrupo();
  }

  function usarImagenCategoriaCmsItem(index, campo) {
    var data = categoriasCmsData("categorias_items");
    var item = data && data.items ? data.items[index] : null;
    if (!item) return;
    var categoria = categoriaCmsPorId(item.categoria_id);
    var imagen = campo === "imagen_banner" ? imagenBannerCategoriaCms(categoria) : imagenCardCategoriaCms(categoria);
    if (!imagen) {
      setCategoriasEstado("La categoria seleccionada no tiene imagen disponible. Sube una imagen desde Media CMS.", "warning");
      return;
    }
    item[campo] = imagen;
    if (campo === "imagen_banner" && !item.alt_banner) item.alt_banner = "Banner de categoria " + (item.titulo || "");
    if (campo === "imagen_card" && !item.alt_card) item.alt_card = "Categoria " + (item.titulo || "");
    setCategoriasEstado("Imagen aplicada al borrador de categoria.", "success");
    renderGrupo();
  }

  function aplicarCategoriaEditorialCms(destino, categoria, conservarImagen) {
    if (!destino || !categoria) return;
    destino.categoria_id = parseInt(categoria.id || "0", 10) || 0;
    destino.titulo = categoria.nombre || categoria.nombre_completo || destino.titulo || "";
    destino.slug = categoria.path_slug || categoria.slug_publico || categoria.slug || destino.slug || "";
    destino.path_slug = categoria.path_slug || destino.slug;
    destino.url = categoria.url_canonica || categoria.url || (destino.slug ? "/categoria/" + destino.slug : destino.url || "");
    if (!destino.subtitulo && categoria.descripcion_corta) destino.subtitulo = categoria.descripcion_corta;
    if (!destino.descripcion_seo && categoria.descripcion_corta) destino.descripcion_seo = categoria.descripcion_corta;
    if (!destino.alt_card) destino.alt_card = "Categoria " + (destino.titulo || "");
    if (!destino.alt_banner) destino.alt_banner = "Banner de categoria " + (destino.titulo || "");
    if (destino.heredar_banner !== false) destino.heredar_banner = true;
    if (!conservarImagen) {
      if (!destino.imagen_card) destino.imagen_card = imagenCardCategoriaCms(categoria);
      if (!destino.imagen_banner) destino.imagen_banner = imagenBannerCategoriaCms(categoria);
    }
  }

  function guardarBorradorCategoriasCms() {
    if (guardarBorradorFrontendLocal(false)) {
      setCategoriasEstado("Borrador local de categorias guardado. Para enviarlo al frontend usa Guardar y publicar categorias.", "success");
    } else {
      setCategoriasEstado("No se pudo guardar el borrador local de categorias.", "danger");
    }
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

  function actualizarPromoConfig(campo, valor) {
    var data = promoData();
    if (campo === "visible") data.visible = valor === "1";
    else setPath(data, campo, valor);
    refrescarJson();
  }

  function actualizarPromoItem(index, campo, valor) {
    var item = promoData().items[index];
    if (!item) return;
    setPath(item, campo, valor);
    refrescarJson();
  }

  function ejecutarPromoAccion(accion, index) {
    var items = promoData().items;
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

  function agregarPromoItem() {
    var items = promoData().items;
    items.push({
      icono: "bi-stars",
      texto: "Nuevo aviso para Home",
      cta: { label: "", url: "" },
      visible: true,
      orden: (items.length + 1) * 10
    });
    renderGrupo();
  }

  function renderHomePromosCategoria(item) {
    var data = promosCategoriaData();
    return '<div class="cms-actual-card mb-4">' +
      '<div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-4">' +
        '<div><div class="fw-bold">' + escapeHtml(item.codigo) + '</div><div class="text-muted fs-8">' + escapeHtml(item.descripcion) + '</div></div>' +
        '<span class="badge badge-light-success">Editor activo</span>' +
      '</div>' +
      '<div class="row g-3 mb-4">' +
        '<div class="col-md-2"><label class="form-label fs-8 fw-bold">Visible</label><select class="form-select form-select-sm" data-home-list-config="promos_categoria.visible"><option value="1"' + (data.visible ? ' selected' : '') + '>Si</option><option value="0"' + (!data.visible ? ' selected' : '') + '>No</option></select></div>' +
        '<div class="col-md-4"><label class="form-label fs-8 fw-bold">Titulo interno</label><input class="form-control form-control-sm" data-home-list-config="promos_categoria.titulo" value="' + escapeAttr(data.titulo || "") + '"></div>' +
        '<div class="col-md-4"><label class="form-label fs-8 fw-bold">Subtitulo interno</label><input class="form-control form-control-sm" data-home-list-config="promos_categoria.subtitulo" value="' + escapeAttr(data.subtitulo || "") + '"></div>' +
        '<div class="col-md-2"><label class="form-label fs-8 fw-bold">Variante</label><input class="form-control form-control-sm" data-home-list-config="promos_categoria.config.variante" value="' + escapeAttr((data.config || {}).variante || "") + '"></div>' +
      '</div>' +
      '<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3"><div class="fw-bold">Promos de categoria</div><div class="d-flex gap-2"><button class="btn btn-sm btn-light-primary" type="button" data-home-section-draft="promos_categoria"><i class="bi bi-save"></i> Guardar borrador</button><button class="btn btn-sm btn-light-info" type="button" id="cms_actual_home_promos_api"><i class="bi bi-broadcast"></i> Ver API publicada</button><button class="btn btn-sm btn-primary" type="button" id="cms_actual_home_promos_publicar"><i class="bi bi-cloud-check"></i> Guardar y publicar promos</button><button class="btn btn-sm btn-light-primary" type="button" id="cms_actual_home_promo_categoria_agregar"><i class="bi bi-plus-circle"></i> Agregar promo</button></div></div>' +
      '<div class="alert alert-light-warning fs-7 py-3 mb-4" id="cms_actual_home_promos_estado">Pendiente de publicar. Cada promo visible necesita titulo, URL canonica e imagen Media CMS.</div>' +
      '<div class="alert alert-light-secondary fs-7 py-3 mb-4 d-none" id="cms_actual_home_promos_api_estado"></div>' +
      (data.items || []).map(function (promo, index) { return renderHomePromoCategoriaItem(promo, index); }).join("") +
      '<div class="alert alert-light-info fs-7 mb-0">Estas cards dirigen a categorias fuertes. Usa URLs completas tipo /categoria/acuario-y-peces/alimentacion/alimentos-de-acuario.</div>' +
    '</div>';
  }

  function renderHomePromoCategoriaItem(item, index) {
    var bg = item.imagen ? ' style="background-image:url(' + escapeAttr(urlPreviewSeguro(item.imagen)) + ')"' : "";
    return '<div class="cms-actual-slide mb-4">' +
      '<div class="d-flex justify-content-between align-items-center gap-2 mb-3">' +
        '<div class="fw-semibold">Promo categoria ' + escapeHtml(index + 1) + '</div>' +
        accionesHomeLista("promos_categoria", index) +
      '</div>' +
      '<div class="cms-actual-slide-preview mb-4"' + bg + '><div><h2 class="text-white fw-bold mb-2">' + escapeHtml(item.titulo || "Promo categoria") + '</h2><div class="opacity-75">' + escapeHtml(item.subtitulo || "") + '</div></div></div>' +
      '<div class="row g-3">' +
        inputHomeLista("promos_categoria", index, "titulo", "Titulo", item.titulo, "col-md-4") +
        inputHomeLista("promos_categoria", index, "subtitulo", "Subtitulo", item.subtitulo, "col-md-4") +
        inputHomeLista("promos_categoria", index, "categoria_id", "Categoria ID", item.categoria_id, "col-md-2") +
        inputHomeLista("promos_categoria", index, "visible", "Visible 1/0", item.visible ? "1" : "0", "col-md-2") +
        inputHomeLista("promos_categoria", index, "url", "URL publica", item.url, "col-md-6") +
        inputHomeLista("promos_categoria", index, "path_slug", "Path slug canonico", item.path_slug, "col-md-6") +
        inputHomeLista("promos_categoria", index, "imagen", "Imagen", item.imagen, "col-md-6", true) +
        inputHomeLista("promos_categoria", index, "alt", "Alt obligatorio", item.alt, "col-md-6") +
      '</div>' +
    '</div>';
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
      '<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3"><div class="fw-bold">Tarjetas de categoria</div><div class="d-flex gap-2"><button class="btn btn-sm btn-light-info" type="button" id="cms_actual_home_categorias_api"><i class="bi bi-broadcast"></i> Ver API publicada</button><button class="btn btn-sm btn-primary" type="button" id="cms_actual_home_categorias_publicar"><i class="bi bi-cloud-check"></i> Guardar y publicar categorias Home</button><button class="btn btn-sm btn-light-primary" type="button" id="cms_actual_home_categoria_agregar"><i class="bi bi-plus-circle"></i> Agregar categoria</button></div></div>' +
      '<div class="alert alert-light-warning fs-7 py-3 mb-4" id="cms_actual_home_categorias_estado">Pendiente de publicar en la API. Cada categoria visible necesita slug o ID ERP.</div>' +
      '<div class="alert alert-light-secondary fs-7 py-3 mb-4 d-none" id="cms_actual_home_categorias_api_estado"></div>' +
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
        selectorCategoriaHomeLista("categorias", index, item, "col-md-12") +
        botonUsarImagenCategoria("categorias", index, item) +
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

  function publicarHomeCategorias() {
    var data = categoriasData();
    var visibles = (data.items || []).filter(function (item) {
      return item && item.visible !== false;
    });
    if (!data.visible || !visibles.length) {
      setHomeCategoriasEstado("Deja al menos una categoria visible antes de publicar.", "warning");
      return;
    }
    var invalida = visibles.filter(function (item) {
      return !item.categoria_id && !String(item.slug || "").trim();
    })[0];
    if (invalida) {
      setHomeCategoriasEstado("Cada categoria visible necesita ID ERP o slug.", "warning");
      return;
    }
    var boton = $("cms_actual_home_categorias_publicar");
    var form = new FormData();
    form.append("_csrf", window.ERP_CSRF_TOKEN || "");
    form.append("payload_json", JSON.stringify(data));
    if (boton) boton.disabled = true;
    setHomeCategoriasEstado("Publicando categorias destacadas en la API...", "info");
    fetch("/cms/frontend_home_categorias_publicar_erp", {
      method: "POST",
      body: form,
      credentials: "same-origin",
      headers: {
        "X-CSRF-Token": window.ERP_CSRF_TOKEN || "",
        "Accept": "application/json",
        "X-Requested-With": "XMLHttpRequest"
      }
    }).then(function (response) {
      return response.text().then(function (text) {
        var json = null;
        try {
          json = JSON.parse(text);
        } catch (error) {
          throw new Error("Respuesta no JSON del servidor (" + response.status + "): " + text.substring(0, 140));
        }
        if (!response.ok && json && json.mensaje) {
          throw new Error(json.mensaje);
        }
        return json;
      });
    }).then(function (json) {
      if (!json || json.error) {
        throw new Error(json && json.mensaje ? json.mensaje : "No se pudo publicar categorias Home");
      }
      setHomeCategoriasEstado("Categorias Home publicadas. El endpoint /ecommercePublico/contenido_pagina?pagina=home ya debe entregar home.categorias.", "success");
      consultarEstadoHomePublicado();
    }).catch(function (error) {
      setHomeCategoriasEstado(error.message || "Error al publicar categorias Home.", "danger");
    }).finally(function () {
      if (boton) boton.disabled = false;
    });
  }

  function consultarApiHomeCategorias() {
    var node = $("cms_actual_home_categorias_api_estado");
    var boton = $("cms_actual_home_categorias_api");
    if (node) {
      node.className = "alert alert-light-info fs-7 py-3 mb-4";
      node.textContent = "Consultando /ecommercePublico/contenido_pagina?pagina=home...";
    }
    if (boton) boton.disabled = true;
    fetch("/ecommercePublico/contenido_pagina?pagina=home", {
      method: "GET",
      credentials: "same-origin",
      headers: {
        "Accept": "application/json",
        "X-Requested-With": "XMLHttpRequest"
      }
    }).then(function (response) {
      return response.text().then(function (text) {
        var json = null;
        try {
          json = JSON.parse(text);
        } catch (error) {
          throw new Error("Respuesta no JSON del servidor (" + response.status + "): " + text.substring(0, 140));
        }
        if (!response.ok) {
          throw new Error((json && json.mensaje) || "No se pudo consultar la API publica");
        }
        return json;
      });
    }).then(function (json) {
      var depurar = json && json.depurar ? json.depurar : {};
      var bloques = bloquesSlotPublicado(depurar, "home.categorias");
      if (!bloques.length) {
        mostrarApiHomeCategorias("No hay categorias publicadas en home.categorias. El frontend usara fallback/default.", "warning");
        return;
      }
      var bloque = bloques[0] || {};
      var items = Array.isArray(bloque.items) ? bloque.items : [];
      mostrarApiHomeCategorias(
        '<div class="fw-bold mb-2">Categorias publicadas para Home</div>' +
        '<div><span class="fw-semibold">Fuente:</span> ' + escapeHtml(depurar.fuente || "sin fuente") + '</div>' +
        '<div><span class="fw-semibold">Total:</span> ' + escapeHtml(items.length) + '</div>' +
        items.slice(0, 6).map(function (item, index) {
          return '<div class="mt-2 text-break"><span class="fw-semibold">' + escapeHtml(index + 1) + '.</span> ' + escapeHtml(item.titulo || item.slug || "") + ' · ' + escapeHtml(item.imagen_card || "sin imagen") + '</div>';
        }).join(""),
        "success",
        true
      );
    }).catch(function (error) {
      mostrarApiHomeCategorias(error.message || "Error al consultar API publicada.", "danger");
    }).finally(function () {
      if (boton) boton.disabled = false;
    });
  }

  function setHomeCategoriasEstado(mensaje, tipo) {
    setText("cms_actual_estado", mensaje);
    var node = $("cms_actual_home_categorias_estado");
    if (!node) return;
    node.className = "alert fs-7 py-3 mb-4 alert-light-" + (tipo || "info");
    node.textContent = mensaje;
  }

  function mostrarApiHomeCategorias(mensaje, tipo, esHtml) {
    setText("cms_actual_estado", esHtml ? "API publicada consultada" : mensaje);
    var node = $("cms_actual_home_categorias_api_estado");
    if (!node) return;
    node.className = "alert fs-7 py-3 mb-4 alert-light-" + (tipo || "info");
    if (esHtml) {
      node.innerHTML = mensaje;
    } else {
      node.textContent = mensaje;
    }
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
      '<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3"><div class="fw-bold">Lista manual opcional</div><div class="d-flex gap-2"><button class="btn btn-sm btn-light-info" type="button" id="cms_actual_home_productos_api"><i class="bi bi-broadcast"></i> Ver API publicada</button><button class="btn btn-sm btn-primary" type="button" id="cms_actual_home_productos_publicar"><i class="bi bi-cloud-check"></i> Guardar y publicar productos Home</button><button class="btn btn-sm btn-light-primary" type="button" id="cms_actual_producto_agregar"><i class="bi bi-plus-circle"></i> Agregar producto</button></div></div>' +
      '<div class="alert alert-light-warning fs-7 py-3 mb-4" id="cms_actual_home_productos_estado">Pendiente de publicar en la API. Define criterio automatico o lista manual.</div>' +
      '<div class="alert alert-light-secondary fs-7 py-3 mb-4 d-none" id="cms_actual_home_productos_api_estado"></div>' +
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

  function publicarHomeProductos() {
    var data = productosData();
    var fuente = data.fuente || {};
    if (!data.visible) {
      setHomeProductosEstado("Activa la seccion antes de publicar productos Home.", "warning");
      return;
    }
    if (fuente.modo === "manual") {
      var refs = (fuente.productos || []).filter(function (item) {
        return item && (item.producto_id || String(item.sku || "").trim() || String(item.slug || "").trim());
      });
      if (!refs.length) {
        setHomeProductosEstado("En modo manual agrega al menos un producto por SKU, ID o slug.", "warning");
        return;
      }
    } else if (!String(fuente.criterio || "").trim()) {
      setHomeProductosEstado("En modo automatico selecciona un criterio.", "warning");
      return;
    }
    var boton = $("cms_actual_home_productos_publicar");
    var form = new FormData();
    form.append("_csrf", window.ERP_CSRF_TOKEN || "");
    form.append("payload_json", JSON.stringify(data));
    if (boton) boton.disabled = true;
    setHomeProductosEstado("Publicando productos destacados en la API...", "info");
    fetch("/cms/frontend_home_productos_publicar_erp", {
      method: "POST",
      body: form,
      credentials: "same-origin",
      headers: {
        "X-CSRF-Token": window.ERP_CSRF_TOKEN || "",
        "Accept": "application/json",
        "X-Requested-With": "XMLHttpRequest"
      }
    }).then(function (response) {
      return response.text().then(function (text) {
        var json = null;
        try {
          json = JSON.parse(text);
        } catch (error) {
          throw new Error("Respuesta no JSON del servidor (" + response.status + "): " + text.substring(0, 140));
        }
        if (!response.ok && json && json.mensaje) {
          throw new Error(json.mensaje);
        }
        return json;
      });
    }).then(function (json) {
      if (!json || json.error) {
        throw new Error(json && json.mensaje ? json.mensaje : "No se pudo publicar productos Home");
      }
      setHomeProductosEstado("Productos Home publicados. El endpoint /ecommercePublico/contenido_pagina?pagina=home ya debe entregar home.destacados.", "success");
      consultarEstadoHomePublicado();
    }).catch(function (error) {
      setHomeProductosEstado(error.message || "Error al publicar productos Home.", "danger");
    }).finally(function () {
      if (boton) boton.disabled = false;
    });
  }

  function consultarApiHomeProductos() {
    var node = $("cms_actual_home_productos_api_estado");
    var boton = $("cms_actual_home_productos_api");
    if (node) {
      node.className = "alert alert-light-info fs-7 py-3 mb-4";
      node.textContent = "Consultando /ecommercePublico/contenido_pagina?pagina=home...";
    }
    if (boton) boton.disabled = true;
    fetch("/ecommercePublico/contenido_pagina?pagina=home", {
      method: "GET",
      credentials: "same-origin",
      headers: {
        "Accept": "application/json",
        "X-Requested-With": "XMLHttpRequest"
      }
    }).then(function (response) {
      return response.text().then(function (text) {
        var json = null;
        try {
          json = JSON.parse(text);
        } catch (error) {
          throw new Error("Respuesta no JSON del servidor (" + response.status + "): " + text.substring(0, 140));
        }
        if (!response.ok) {
          throw new Error((json && json.mensaje) || "No se pudo consultar la API publica");
        }
        return json;
      });
    }).then(function (json) {
      var depurar = json && json.depurar ? json.depurar : {};
      var bloques = bloquesSlotPublicado(depurar, "home.destacados");
      if (!bloques.length) {
        mostrarApiHomeProductos("No hay productos publicados en home.destacados. El frontend usara fallback/default.", "warning");
        return;
      }
      var bloque = bloques[0] || {};
      var fuente = bloque.fuente || {};
      var productos = Array.isArray(fuente.productos) ? fuente.productos : [];
      mostrarApiHomeProductos(
        '<div class="fw-bold mb-2">Productos destacados publicados para Home</div>' +
        '<div><span class="fw-semibold">Fuente:</span> ' + escapeHtml(depurar.fuente || "sin fuente") + '</div>' +
        '<div><span class="fw-semibold">Modo:</span> ' + escapeHtml(fuente.modo || "") + '</div>' +
        '<div><span class="fw-semibold">Criterio:</span> ' + escapeHtml(fuente.criterio || "") + '</div>' +
        '<div><span class="fw-semibold">Limite:</span> ' + escapeHtml(bloque.limite || "") + '</div>' +
        '<div><span class="fw-semibold">Referencias manuales:</span> ' + escapeHtml(productos.length) + '</div>',
        "success",
        true
      );
    }).catch(function (error) {
      mostrarApiHomeProductos(error.message || "Error al consultar API publicada.", "danger");
    }).finally(function () {
      if (boton) boton.disabled = false;
    });
  }

  function setHomeProductosEstado(mensaje, tipo) {
    setText("cms_actual_estado", mensaje);
    var node = $("cms_actual_home_productos_estado");
    if (!node) return;
    node.className = "alert fs-7 py-3 mb-4 alert-light-" + (tipo || "info");
    node.textContent = mensaje;
  }

  function mostrarApiHomeProductos(mensaje, tipo, esHtml) {
    setText("cms_actual_estado", esHtml ? "API publicada consultada" : mensaje);
    var node = $("cms_actual_home_productos_api_estado");
    if (!node) return;
    node.className = "alert fs-7 py-3 mb-4 alert-light-" + (tipo || "info");
    if (esHtml) {
      node.innerHTML = mensaje;
    } else {
      node.textContent = mensaje;
    }
  }

  function renderHomeMarcasDestacadas(item) {
    var data = marcasHomeData();
    return '<div class="cms-actual-card mb-4">' +
      '<div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-4">' +
        '<div><div class="fw-bold">' + escapeHtml(item.codigo) + '</div><div class="text-muted fs-8">' + escapeHtml(item.descripcion) + '</div></div>' +
        '<span class="badge badge-light-success">Editor activo</span>' +
      '</div>' +
      '<div class="row g-3 mb-4">' +
        '<div class="col-md-2"><label class="form-label fs-8 fw-bold">Visible</label><select class="form-select form-select-sm" data-home-list-config="marcas.visible"><option value="1"' + (data.visible ? ' selected' : '') + '>Si</option><option value="0"' + (!data.visible ? ' selected' : '') + '>No</option></select></div>' +
        '<div class="col-md-4"><label class="form-label fs-8 fw-bold">Titulo</label><input class="form-control form-control-sm" data-home-list-config="marcas.titulo" value="' + escapeAttr(data.titulo || "") + '"></div>' +
        '<div class="col-md-4"><label class="form-label fs-8 fw-bold">Subtitulo</label><input class="form-control form-control-sm" data-home-list-config="marcas.subtitulo" value="' + escapeAttr(data.subtitulo || "") + '"></div>' +
        '<div class="col-md-2"><label class="form-label fs-8 fw-bold">Orden</label><input class="form-control form-control-sm" data-home-list-config="marcas.orden" value="' + escapeAttr(data.orden || 45) + '"></div>' +
        selectorCategoriaMarcasHome(data) +
        '<div class="col-md-3"><label class="form-label fs-8 fw-bold">Modo</label><select class="form-select form-select-sm" data-home-list-config="marcas.fuente.modo"><option value="mixto"' + ((data.fuente || {}).modo === "mixto" ? ' selected' : '') + '>Mixto: categoria + marcas manuales</option><option value="automatico_categoria"' + ((data.fuente || {}).modo === "automatico_categoria" ? ' selected' : '') + '>Automatico por categoria</option><option value="manual"' + ((data.fuente || {}).modo === "manual" ? ' selected' : '') + '>Solo marcas manuales</option></select></div>' +
        '<div class="col-md-2"><label class="form-label fs-8 fw-bold">Limite 0=todas</label><input class="form-control form-control-sm" data-home-list-config="marcas.fuente.limite" value="' + escapeAttr((data.fuente || {}).limite || 0) + '"></div>' +
        '<div class="col-md-3"><label class="form-label fs-8 fw-bold">Completar automatico</label><select class="form-select form-select-sm" data-home-list-config="marcas.fuente.rellenar_automatico_si_faltan"><option value="1"' + ((data.fuente || {}).rellenar_automatico_si_faltan !== false ? ' selected' : '') + '>Si</option><option value="0"' + ((data.fuente || {}).rellenar_automatico_si_faltan === false ? ' selected' : '') + '>No</option></select></div>' +
        '<div class="col-md-2"><label class="form-label fs-8 fw-bold">Variante</label><input class="form-control form-control-sm" data-home-list-config="marcas.config.variante" value="' + escapeAttr((data.config || {}).variante || "") + '"></div>' +
        '<div class="col-md-6"><label class="form-label fs-8 fw-bold">Path categoria</label><input class="form-control form-control-sm" data-home-list-config="marcas.categoria_contexto.path_slug" value="' + escapeAttr((data.categoria_contexto || {}).path_slug || (data.fuente || {}).categoria_slug || "") + '"></div>' +
        '<div class="col-md-6"><label class="form-label fs-8 fw-bold">URL categoria</label><input class="form-control form-control-sm" data-home-list-config="marcas.categoria_contexto.url" value="' + escapeAttr((data.categoria_contexto || {}).url || "") + '"></div>' +
        '<div class="col-md-6"><label class="form-label fs-8 fw-bold">Imagen card categoria</label><input class="form-control form-control-sm" data-home-list-config="marcas.categoria_contexto.imagen_card" value="' + escapeAttr((data.categoria_contexto || {}).imagen_card || "") + '"></div>' +
        '<div class="col-md-6"><label class="form-label fs-8 fw-bold">Imagen banner categoria</label><input class="form-control form-control-sm" data-home-list-config="marcas.categoria_contexto.imagen_banner" value="' + escapeAttr((data.categoria_contexto || {}).imagen_banner || "") + '"></div>' +
      '</div>' +
      '<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3"><div class="fw-bold">Marcas de la categoria</div><button class="btn btn-sm btn-light-info" type="button" id="cms_actual_home_marcas_preview"><i class="bi bi-eye"></i> Ver marcas de la categoria</button></div>' +
      '<div class="alert alert-light-secondary fs-7 py-3 mb-4" id="cms_actual_home_marcas_preview_estado">Selecciona una categoria origen y revisa aqui si sus marcas tienen logo/banner.</div>' +
      '<div class="row g-3 mb-4" id="cms_actual_home_marcas_preview_lista"></div>' +
      '<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3"><div class="fw-bold">Marcas manuales opcionales</div><div class="d-flex gap-2"><button class="btn btn-sm btn-light-primary" type="button" data-home-section-draft="marcas"><i class="bi bi-save"></i> Guardar borrador</button><button class="btn btn-sm btn-light-info" type="button" id="cms_actual_home_marcas_api"><i class="bi bi-broadcast"></i> Ver API publicada</button><button class="btn btn-sm btn-primary" type="button" id="cms_actual_home_marcas_publicar"><i class="bi bi-cloud-check"></i> Guardar y publicar marcas</button><button class="btn btn-sm btn-light-primary" type="button" id="cms_actual_home_marca_agregar"><i class="bi bi-plus-circle"></i> Agregar marca manual</button></div></div>' +
      '<div class="alert alert-light-warning fs-7 py-3 mb-4" id="cms_actual_home_marcas_estado">Pendiente de publicar. Selecciona la categoria que manda el contexto; las marcas manuales solo sirven para priorizar o reemplazar.</div>' +
      '<div class="alert alert-light-secondary fs-7 py-3 mb-4 d-none" id="cms_actual_home_marcas_api_estado"></div>' +
      (!(data.items || []).length ? '<div class="alert alert-light-secondary fs-7 py-3 mb-4">No hay marcas manuales. Si usas modo automatico o mixto, la categoria origen sera suficiente para publicar el bloque.</div>' : '') +
      (data.items || []).map(renderHomeMarcaItem).join("") +
      '<div class="alert alert-light-info fs-7 mb-0">El CMS no crea marcas reales. La categoria define el contexto y frontend/API pueden completar marcas reales con productos publicados; las marcas manuales solo afinan la seleccion.</div>' +
    '</div>';
  }

  function selectorCategoriaMarcasHome(data) {
    var contexto = data.categoria_contexto || {};
    var selected = contexto.categoria_id || 0;
    var categorias = estado.catalogos.categorias || [];
    var options = '<option value="">Selecciona categoria de origen</option>' + categorias.map(function (categoria) {
      var id = parseInt(categoria.id || "0", 10) || 0;
      var label = categoria.nombre_completo || categoria.ruta || categoria.nombre || ("Categoria " + id);
      return '<option value="' + escapeAttr(id) + '"' + (parseInt(selected || "0", 10) === id ? ' selected' : '') + '>' + escapeHtml(label) + '</option>';
    }).join("");
    return '<div class="col-md-4"><label class="form-label fs-8 fw-bold">Categoria origen</label><select class="form-select form-select-sm" data-home-marcas-category-select>' + options + '</select></div>';
  }

  function renderHomeMarcaItem(item, index) {
    var bg = item.imagen_banner || item.logo;
    return '<div class="cms-actual-slide mb-4">' +
      '<div class="d-flex justify-content-between align-items-center gap-2 mb-3">' +
        '<div class="fw-semibold">Marca ' + escapeHtml(index + 1) + '</div>' +
        accionesHomeLista("marcas", index) +
      '</div>' +
      '<div class="cms-actual-slide-preview mb-4"' + (bg ? ' style="background-image:url(' + escapeAttr(urlPreviewSeguro(bg)) + ')"' : '') + '><div><h2 class="text-white fw-bold mb-2">' + escapeHtml(item.nombre || "Marca") + '</h2><div class="opacity-75">' + escapeHtml(item.subtitulo || item.descripcion_corta || "") + '</div></div></div>' +
      '<div class="row g-3">' +
        selectorMarcaHomeManual(index, item, "col-md-4") +
        inputHomeLista("marcas", index, "nombre", "Nombre", item.nombre, "col-md-3") +
        inputHomeLista("marcas", index, "subtitulo", "Subtitulo", item.subtitulo, "col-md-3") +
        inputHomeLista("marcas", index, "marca_id", "Marca ID", item.marca_id, "col-md-2") +
        inputHomeLista("marcas", index, "visible", "Visible 1/0", item.visible ? "1" : "0", "col-md-2") +
        inputHomeLista("marcas", index, "slug", "Slug", item.slug, "col-md-2") +
        inputHomeLista("marcas", index, "url", "URL publica", item.url, "col-md-4") +
        inputHomeLista("marcas", index, "logo", "Logo", item.logo, "col-md-4", true) +
        inputHomeLista("marcas", index, "imagen_banner", "Banner marca opcional", item.imagen_banner, "col-md-4", true) +
        inputHomeLista("marcas", index, "alt_logo", "Alt logo", item.alt_logo, "col-md-6") +
        inputHomeLista("marcas", index, "descripcion_corta", "Descripcion corta", item.descripcion_corta, "col-md-6") +
      '</div>' +
    '</div>';
  }

  function selectorMarcaHomeManual(index, item, col) {
    var selected = parseInt((item || {}).marca_id || "0", 10) || 0;
    var marcas = estado.catalogos.marcas || [];
    var options = '<option value="">Selecciona marca real</option>' + marcas.map(function (marca) {
      var id = parseInt(marca.id || "0", 10) || 0;
      var label = marca.nombre || marca.nombre_publico || ("Marca " + id);
      return '<option value="' + escapeAttr(id) + '"' + (selected === id ? ' selected' : '') + '>' + escapeHtml(label) + '</option>';
    }).join("");
    return '<div class="' + escapeAttr(col || "col-md-4") + '"><label class="form-label fs-8 fw-bold">Marca real</label><select class="form-select form-select-sm" data-home-marca-select data-index="' + escapeAttr(index) + '">' + options + '</select></div>';
  }

  /**
   * IA: Codex GPT-5 | Fecha: 2026-08-31
   * Proposito: previsualizar marcas reales por categoria con diagnostico de imagen.
   * Impacto: CMS Frontend Home; muestra que marcas enviaria `home_marcas_destacadas` y cuales requieren logo/banner en CMS Marcas.
   */
  function consultarPreviewHomeMarcas() {
    var data = marcasHomeData();
    var contexto = data.categoria_contexto || {};
    var fuente = data.fuente || {};
    var categoriaId = parseInt(contexto.categoria_id || "0", 10) || 0;
    var categoriaSlug = String(fuente.categoria_slug || contexto.path_slug || "").trim();
    if (!categoriaId && !categoriaSlug) {
      setPreviewHomeMarcas("Selecciona una categoria origen primero.", "warning");
      return;
    }
    var limite = parseInt(fuente.limite || "0", 10) || 0;
    var url = "/cms/frontend_home_marcas_preview_erp?categoria_id=" + encodeURIComponent(categoriaId) + "&categoria_slug=" + encodeURIComponent(categoriaSlug) + "&limite=" + encodeURIComponent(limite);
    var boton = $("cms_actual_home_marcas_preview");
    if (boton) boton.disabled = true;
    setPreviewHomeMarcas("Consultando marcas reales de la categoria...", "info");
    fetch(url, {
      method: "GET",
      credentials: "same-origin",
      headers: {
        "Accept": "application/json",
        "X-Requested-With": "XMLHttpRequest"
      }
    }).then(function (response) {
      return response.text().then(function (text) {
        var json = null;
        try {
          json = JSON.parse(text);
        } catch (error) {
          throw new Error("Respuesta no JSON del servidor (" + response.status + "): " + text.substring(0, 140));
        }
        if (!response.ok && json && json.mensaje) throw new Error(json.mensaje);
        return json;
      });
    }).then(function (json) {
      if (!json || json.error) throw new Error(json && json.mensaje ? json.mensaje : "No se pudo consultar preview");
      var depurar = json.depurar || {};
      estado.previewMarcasCategoria = depurar.items || [];
      renderPreviewHomeMarcas(depurar);
    }).catch(function (error) {
      setPreviewHomeMarcas(error.message || "Error al consultar preview de marcas.", "danger");
    }).finally(function () {
      if (boton) boton.disabled = false;
    });
  }

  function renderPreviewHomeMarcas(depurar) {
    var resumen = depurar.resumen || {};
    var items = Array.isArray(depurar.items) ? depurar.items : [];
    setPreviewHomeMarcas(
      "Marcas encontradas: " + (resumen.total || items.length) + ". Con imagen: " + (resumen.con_imagen || 0) + ". Sin imagen: " + (resumen.sin_imagen || 0) + ".",
      resumen.sin_imagen ? "warning" : "success"
    );
    var lista = $("cms_actual_home_marcas_preview_lista");
    if (!lista) return;
    if (!items.length) {
      lista.innerHTML = '<div class="col-12"><div class="alert alert-light-warning fs-7 mb-0">No encontre marcas publicadas para esa categoria.</div></div>';
      return;
    }
    lista.innerHTML = items.map(function (item, index) {
      var imagen = item.logo || item.imagen_banner || "";
      var tieneImagen = !!item.tiene_imagen;
      return '<div class="col-md-4 col-xl-3">' +
        '<div class="border rounded bg-white overflow-hidden h-100">' +
          (imagen ? '<img src="' + escapeAttr(urlPreviewSeguro(imagen)) + '" alt="' + escapeAttr(item.alt_logo || item.nombre || "") + '" style="width:100%;aspect-ratio:16/10;object-fit:contain;background:#f3f6f9;padding:10px;">' : '<div class="d-flex align-items-center justify-content-center bg-light text-muted" style="width:100%;aspect-ratio:16/10;">Sin imagen</div>') +
          '<div class="p-3">' +
            '<div class="fw-bold text-truncate">' + escapeHtml(item.nombre || "Marca") + '</div>' +
            '<div class="text-muted fs-8 text-truncate mb-2">' + escapeHtml(item.url || "") + '</div>' +
            '<div class="d-flex flex-wrap gap-2 mb-3"><span class="badge ' + (tieneImagen ? 'badge-light-success' : 'badge-light-warning') + '">' + (tieneImagen ? 'Con imagen' : 'Sin imagen') + '</span><span class="badge badge-light">' + escapeHtml((item.total_productos || 0) + " productos") + '</span></div>' +
            '<button class="btn btn-sm ' + (tieneImagen ? 'btn-light-primary' : 'btn-light-secondary') + ' w-100" type="button" data-home-marca-preview-add="' + escapeAttr(index) + '"' + (!tieneImagen ? ' disabled' : '') + '><i class="bi bi-plus-circle"></i> Agregar al borrador</button>' +
          '</div>' +
        '</div>' +
      '</div>';
    }).join("");
  }

  function agregarMarcaDesdePreview(index) {
    var item = estado.previewMarcasCategoria && estado.previewMarcasCategoria[index] ? estado.previewMarcasCategoria[index] : null;
    if (!item || !item.tiene_imagen) return;
    var data = marcasHomeData();
    data.items = data.items || [];
    var marcaId = parseInt(item.marca_id || "0", 10) || 0;
    var yaExiste = data.items.some(function (actual) {
      return parseInt((actual || {}).marca_id || "0", 10) === marcaId;
    });
    if (yaExiste) {
      setPreviewHomeMarcas("Esa marca ya esta en el borrador manual.", "info");
      return;
    }
    data.items.push({
      marca_id: marcaId,
      nombre: item.nombre || "",
      subtitulo: item.subtitulo || "Ver marca",
      slug: item.slug || "",
      slug_publico: item.slug || "",
      logo: item.logo || "",
      imagen_banner: item.imagen_banner || "",
      alt_logo: item.alt_logo || ("Logo de " + (item.nombre || "")),
      descripcion_corta: item.descripcion_corta || "",
      url: item.url || "",
      visible: true,
      visible_frontend: true,
      orden: (data.items.length + 1) * 10
    });
    setPreviewHomeMarcas("Marca agregada al borrador manual.", "success");
    renderGrupo();
  }

  function setPreviewHomeMarcas(mensaje, tipo) {
    setText("cms_actual_estado", mensaje);
    var node = $("cms_actual_home_marcas_preview_estado");
    if (!node) return;
    node.className = "alert fs-7 py-3 mb-4 alert-light-" + (tipo || "info");
    node.textContent = mensaje;
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
      '<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3"><div class="fw-bold">Colecciones</div><div class="d-flex gap-2"><button class="btn btn-sm btn-light-info" type="button" id="cms_actual_home_colecciones_api"><i class="bi bi-broadcast"></i> Ver API publicada</button><button class="btn btn-sm btn-primary" type="button" id="cms_actual_home_colecciones_publicar"><i class="bi bi-cloud-check"></i> Guardar y publicar colecciones</button><button class="btn btn-sm btn-light-primary" type="button" id="cms_actual_coleccion_agregar"><i class="bi bi-plus-circle"></i> Agregar coleccion</button></div></div>' +
      '<div class="alert alert-light-warning fs-7 py-3 mb-4" id="cms_actual_home_colecciones_estado">Pendiente de publicar en la API. Define al menos una coleccion visible.</div>' +
      '<div class="alert alert-light-secondary fs-7 py-3 mb-4 d-none" id="cms_actual_home_colecciones_api_estado"></div>' +
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

  function publicarHomeColecciones() {
    var data = coleccionesData();
    var visibles = (data.items || []).filter(function (item) {
      return item && item.visible !== false && String(item.titulo || "").trim();
    });
    if (!data.visible || !visibles.length) {
      setHomeColeccionesEstado("Deja al menos una coleccion visible con titulo antes de publicar.", "warning");
      return;
    }
    var invalida = visibles.filter(function (item) {
      var fuente = item.fuente || {};
      if (fuente.modo === "manual") {
        return !(Array.isArray(fuente.productos) && fuente.productos.length);
      }
      return !String(fuente.criterio || "").trim();
    })[0];
    if (invalida) {
      setHomeColeccionesEstado("Cada coleccion visible necesita criterio automatico o referencias manuales.", "warning");
      return;
    }
    var boton = $("cms_actual_home_colecciones_publicar");
    var form = new FormData();
    form.append("_csrf", window.ERP_CSRF_TOKEN || "");
    form.append("payload_json", JSON.stringify(data));
    if (boton) boton.disabled = true;
    setHomeColeccionesEstado("Publicando colecciones en la API...", "info");
    fetch("/cms/frontend_home_colecciones_publicar_erp", {
      method: "POST",
      body: form,
      credentials: "same-origin",
      headers: {
        "X-CSRF-Token": window.ERP_CSRF_TOKEN || "",
        "Accept": "application/json",
        "X-Requested-With": "XMLHttpRequest"
      }
    }).then(function (response) {
      return response.text().then(function (text) {
        var json = null;
        try {
          json = JSON.parse(text);
        } catch (error) {
          throw new Error("Respuesta no JSON del servidor (" + response.status + "): " + text.substring(0, 140));
        }
        if (!response.ok && json && json.mensaje) {
          throw new Error(json.mensaje);
        }
        return json;
      });
    }).then(function (json) {
      if (!json || json.error) {
        throw new Error(json && json.mensaje ? json.mensaje : "No se pudo publicar colecciones");
      }
      setHomeColeccionesEstado("Colecciones publicadas. El endpoint /ecommercePublico/contenido_pagina?pagina=home ya debe entregar el bloque de colecciones.", "success");
      consultarEstadoHomePublicado();
    }).catch(function (error) {
      setHomeColeccionesEstado(error.message || "Error al publicar colecciones.", "danger");
    }).finally(function () {
      if (boton) boton.disabled = false;
    });
  }

  function consultarApiHomeColecciones() {
    var node = $("cms_actual_home_colecciones_api_estado");
    var boton = $("cms_actual_home_colecciones_api");
    if (node) {
      node.className = "alert alert-light-info fs-7 py-3 mb-4";
      node.textContent = "Consultando /ecommercePublico/contenido_pagina?pagina=home...";
    }
    if (boton) boton.disabled = true;
    fetch("/ecommercePublico/contenido_pagina?pagina=home", {
      method: "GET",
      credentials: "same-origin",
      headers: {
        "Accept": "application/json",
        "X-Requested-With": "XMLHttpRequest"
      }
    }).then(function (response) {
      return response.text().then(function (text) {
        var json = null;
        try {
          json = JSON.parse(text);
        } catch (error) {
          throw new Error("Respuesta no JSON del servidor (" + response.status + "): " + text.substring(0, 140));
        }
        if (!response.ok) {
          throw new Error((json && json.mensaje) || "No se pudo consultar la API publica");
        }
        return json;
      });
    }).then(function (json) {
      var depurar = json && json.depurar ? json.depurar : {};
      var bloques = bloquesSlotPublicado(depurar, "home.destacados").filter(function (bloque) {
        return bloque && bloque.frontend && bloque.frontend.origen === "home_colecciones";
      });
      if (!bloques.length) {
        mostrarApiHomeColecciones("No hay colecciones publicadas en home.destacados. El frontend usara fallback/default para esa parte.", "warning");
        return;
      }
      var bloque = bloques[0] || {};
      var items = Array.isArray(bloque.items) ? bloque.items : [];
      mostrarApiHomeColecciones(
        '<div class="fw-bold mb-2">Colecciones publicadas para Home</div>' +
        '<div><span class="fw-semibold">Fuente:</span> ' + escapeHtml(depurar.fuente || "sin fuente") + '</div>' +
        '<div><span class="fw-semibold">Total:</span> ' + escapeHtml(items.length) + '</div>' +
        items.slice(0, 6).map(function (item, index) {
          return '<div class="mt-2"><span class="fw-semibold">' + escapeHtml(index + 1) + '.</span> ' + escapeHtml(item.titulo || item.codigo || "") + ' · ' + escapeHtml((item.fuente || {}).criterio || (item.fuente || {}).modo || "") + '</div>';
        }).join(""),
        "success",
        true
      );
    }).catch(function (error) {
      mostrarApiHomeColecciones(error.message || "Error al consultar API publicada.", "danger");
    }).finally(function () {
      if (boton) boton.disabled = false;
    });
  }

  function setHomeColeccionesEstado(mensaje, tipo) {
    setText("cms_actual_estado", mensaje);
    var node = $("cms_actual_home_colecciones_estado");
    if (!node) return;
    node.className = "alert fs-7 py-3 mb-4 alert-light-" + (tipo || "info");
    node.textContent = mensaje;
  }

  function mostrarApiHomeColecciones(mensaje, tipo, esHtml) {
    setText("cms_actual_estado", esHtml ? "API publicada consultada" : mensaje);
    var node = $("cms_actual_home_colecciones_api_estado");
    if (!node) return;
    node.className = "alert fs-7 py-3 mb-4 alert-light-" + (tipo || "info");
    if (esHtml) {
      node.innerHTML = mensaje;
    } else {
      node.textContent = mensaje;
    }
  }

  function renderHomeEsencialesArtiani(item) {
    var data = esencialesData();
    var principal = data.categoria_principal || {};
    return '<div class="cms-actual-card mb-4">' +
      '<div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-4">' +
        '<div><div class="fw-bold">' + escapeHtml(item.codigo) + '</div><div class="text-muted fs-8">' + escapeHtml(item.descripcion) + '</div></div>' +
        '<span class="badge badge-light-success">Editor activo</span>' +
      '</div>' +
      '<div class="row g-3 mb-4">' +
        '<div class="col-md-2"><label class="form-label fs-8 fw-bold">Visible</label><select class="form-select form-select-sm" data-esenciales-config="visible"><option value="1"' + (data.visible ? ' selected' : '') + '>Si</option><option value="0"' + (!data.visible ? ' selected' : '') + '>No</option></select></div>' +
        '<div class="col-md-4"><label class="form-label fs-8 fw-bold">Titulo visible</label><input class="form-control form-control-sm" data-esenciales-config="titulo" value="' + escapeAttr(data.titulo || "") + '"></div>' +
        '<div class="col-md-4"><label class="form-label fs-8 fw-bold">Subtitulo</label><input class="form-control form-control-sm" data-esenciales-config="subtitulo" value="' + escapeAttr(data.subtitulo || "") + '"></div>' +
        '<div class="col-md-2"><label class="form-label fs-8 fw-bold">Orden</label><input class="form-control form-control-sm" data-esenciales-config="orden" value="' + escapeAttr(data.orden || 70) + '"></div>' +
      '</div>' +
      '<div class="fw-bold mb-3">Categoria principal</div>' +
      '<div class="cms-actual-slide mb-4"><div class="row g-3">' +
        selectorCategoriaEsencialPrincipal(principal) +
        botonUsarImagenCategoria("principal", 0, principal) +
        inputEsencialPrincipal("titulo", "Titulo", principal.titulo, "col-md-4") +
        inputEsencialPrincipal("url", "URL publica generada", principal.url, "col-md-4") +
        inputEsencialPrincipal("path_slug", "Path slug generado", principal.path_slug, "col-md-4") +
        inputEsencialPrincipal("imagen", "Imagen", principal.imagen, "col-md-6", true) +
        inputEsencialPrincipal("alt", "Alt", principal.alt, "col-md-6") +
        inputEsencialPrincipal("objetivo", "Objetivo interno", principal.objetivo, "col-md-12") +
      '</div></div>' +
      '<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3"><div class="fw-bold">Cards esenciales</div><div class="d-flex gap-2"><button class="btn btn-sm btn-light-primary" type="button" data-home-section-draft="esenciales"><i class="bi bi-save"></i> Guardar borrador</button><button class="btn btn-sm btn-light-info" type="button" id="cms_actual_home_esenciales_api"><i class="bi bi-broadcast"></i> Ver API publicada</button><button class="btn btn-sm btn-primary" type="button" id="cms_actual_home_esenciales_publicar"><i class="bi bi-cloud-check"></i> Guardar y publicar esenciales</button><button class="btn btn-sm btn-light-primary" type="button" id="cms_actual_home_esencial_agregar"><i class="bi bi-plus-circle"></i> Agregar card</button></div></div>' +
      '<div class="alert alert-light-warning fs-7 py-3 mb-4" id="cms_actual_home_esenciales_estado">Pendiente de publicar. Maximo 3 cards visibles con titulo, URL, path_slug e imagen.</div>' +
      '<div class="alert alert-light-secondary fs-7 py-3 mb-4 d-none" id="cms_actual_home_esenciales_api_estado"></div>' +
      (data.items || []).map(renderHomeEsencialItem).join("") +
      '<div class="alert alert-light-info fs-7 mb-0">Usa esta seccion para destacar una categoria principal y tres accesos relevantes; no sustituye el arbol real de categorias.</div>' +
    '</div>';
  }

  function renderHomeEsencialItem(item, index) {
    var bg = item.imagen ? ' style="background-image:url(' + escapeAttr(urlPreviewSeguro(item.imagen)) + ')"' : "";
    return '<div class="cms-actual-slide mb-4">' +
      '<div class="d-flex justify-content-between align-items-center gap-2 mb-3">' +
        '<div class="fw-semibold">Card esencial ' + escapeHtml(index + 1) + '</div>' +
        accionesHomeLista("esenciales", index) +
      '</div>' +
      '<div class="cms-actual-slide-preview mb-4"' + bg + '><div><h2 class="text-white fw-bold mb-2">' + escapeHtml(item.titulo || "Esencial") + '</h2><div class="opacity-75">' + escapeHtml(item.subtitulo || "") + '</div></div></div>' +
      '<div class="row g-3">' +
        selectorCategoriaHomeLista("esenciales", index, item, "col-md-12") +
        botonUsarImagenCategoria("esenciales", index, item) +
        inputHomeLista("esenciales", index, "titulo", "Titulo", item.titulo, "col-md-4") +
        inputHomeLista("esenciales", index, "subtitulo", "Subtitulo", item.subtitulo, "col-md-4") +
        inputHomeLista("esenciales", index, "visible", "Visible 1/0", item.visible ? "1" : "0", "col-md-2") +
        inputHomeLista("esenciales", index, "url", "URL publica generada", item.url, "col-md-6") +
        inputHomeLista("esenciales", index, "path_slug", "Path slug generado", item.path_slug, "col-md-6") +
        inputHomeLista("esenciales", index, "imagen", "Imagen", item.imagen, "col-md-6", true) +
        inputHomeLista("esenciales", index, "alt", "Alt", item.alt, "col-md-6") +
        inputHomeLista("esenciales", index, "objetivo", "Objetivo interno", item.objetivo, "col-md-12") +
      '</div>' +
    '</div>';
  }

  function renderCompraGuiadaHome(item) {
    var data = compraGuiadaData();
    var config = data.config || {};
    return '<div class="cms-actual-card mb-4">' +
      '<div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-4">' +
        '<div><div class="fw-bold">' + escapeHtml(item.codigo) + '</div><div class="text-muted fs-8">' + escapeHtml(item.descripcion) + '</div></div>' +
        '<span class="badge badge-light-success">Editor activo</span>' +
      '</div>' +
      '<div class="row g-3 mb-4">' +
        '<div class="col-md-2"><label class="form-label fs-8 fw-bold">Visible</label><select class="form-select form-select-sm" data-compra-guiada-field="visible"><option value="1"' + (data.visible ? ' selected' : '') + '>Si</option><option value="0"' + (!data.visible ? ' selected' : '') + '>No</option></select></div>' +
        '<div class="col-md-4"><label class="form-label fs-8 fw-bold">Titulo</label><input class="form-control form-control-sm" data-compra-guiada-field="titulo" value="' + escapeAttr(data.titulo || "") + '"></div>' +
        '<div class="col-md-4"><label class="form-label fs-8 fw-bold">Subtitulo</label><input class="form-control form-control-sm" data-compra-guiada-field="subtitulo" value="' + escapeAttr(data.subtitulo || "") + '"></div>' +
        '<div class="col-md-2"><label class="form-label fs-8 fw-bold">Orden</label><input class="form-control form-control-sm" data-compra-guiada-field="orden" value="' + escapeAttr(data.orden || 80) + '"></div>' +
        '<div class="col-md-3"><label class="form-label fs-8 fw-bold">Mostrar mascotas</label><select class="form-select form-select-sm" data-compra-guiada-config="mostrar_mascotas"><option value="1"' + (config.mostrar_mascotas ? ' selected' : '') + '>Si</option><option value="0"' + (!config.mostrar_mascotas ? ' selected' : '') + '>No</option></select></div>' +
        '<div class="col-md-3"><label class="form-label fs-8 fw-bold">Mostrar necesidades</label><select class="form-select form-select-sm" data-compra-guiada-config="mostrar_necesidades"><option value="1"' + (config.mostrar_necesidades ? ' selected' : '') + '>Si</option><option value="0"' + (!config.mostrar_necesidades ? ' selected' : '') + '>No</option></select></div>' +
        '<div class="col-md-3"><label class="form-label fs-8 fw-bold">Prioridad</label><select class="form-select form-select-sm" data-compra-guiada-config="prioridad"><option value="secundaria"' + (config.prioridad !== "principal" ? ' selected' : '') + '>Secundaria</option><option value="principal"' + (config.prioridad === "principal" ? ' selected' : '') + '>Principal</option></select></div>' +
        '<div class="col-md-3"><label class="form-label fs-8 fw-bold">Variante frontend</label><input class="form-control form-control-sm" data-compra-guiada-config="variante" value="' + escapeAttr(config.variante || "wokiee_guided_chips") + '"></div>' +
      '</div>' +
      '<div class="cms-actual-slide mb-4">' +
        '<div class="fw-bold mb-2">' + escapeHtml(data.titulo || "Compra guiada") + '</div>' +
        '<div class="text-muted fs-7 mb-3">' + escapeHtml(data.subtitulo || "") + '</div>' +
        '<div class="d-flex flex-wrap gap-2">' +
          (config.mostrar_mascotas ? '<span class="badge badge-light-primary">Perros</span><span class="badge badge-light-primary">Gatos</span><span class="badge badge-light-primary">Acuario</span><span class="badge badge-light-primary">Aves</span>' : '') +
          (config.mostrar_necesidades ? '<span class="badge badge-light-info">Alimento</span><span class="badge badge-light-info">Habitat</span><span class="badge badge-light-info">Cuidado</span>' : '') +
        '</div>' +
      '</div>' +
      '<div class="d-flex gap-2 flex-wrap mb-3">' +
        '<button class="btn btn-sm btn-light-primary" type="button" id="cms_actual_home_compra_guiada_borrador"><i class="bi bi-save"></i> Guardar borrador</button>' +
        '<button class="btn btn-sm btn-light-info" type="button" id="cms_actual_home_compra_guiada_api"><i class="bi bi-broadcast"></i> Ver API publicada</button>' +
        '<button class="btn btn-sm btn-primary" type="button" id="cms_actual_home_compra_guiada_publicar"><i class="bi bi-cloud-check"></i> Guardar y publicar compra guiada</button>' +
      '</div>' +
      '<div class="alert alert-light-warning fs-7 py-3 mb-4" id="cms_actual_home_compra_guiada_estado">Pendiente de publicar en la API. El frontend pinta las opciones con su taxonomia/catalogo.</div>' +
      '<div class="alert alert-light-secondary fs-7 py-3 mb-0 d-none" id="cms_actual_home_compra_guiada_api_estado"></div>' +
    '</div>';
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
      '<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3"><div class="fw-bold">Imagenes del banner</div><div class="d-flex gap-2"><button class="btn btn-sm btn-light-info" type="button" id="cms_actual_banner_api"><i class="bi bi-broadcast"></i> Ver API publicada</button><button class="btn btn-sm btn-primary" type="button" id="cms_actual_banner_publicar"><i class="bi bi-cloud-check"></i> Guardar y publicar banner</button><button class="btn btn-sm btn-light-primary" type="button" id="cms_actual_banner_agregar"><i class="bi bi-plus-circle"></i> Agregar slide futuro</button></div></div>' +
      '<div class="alert alert-light-warning fs-7 py-3 mb-4" id="cms_actual_banner_estado">Pendiente de publicar en la API. Usa una imagen guardada en Media CMS y captura el alt obligatorio.</div>' +
      '<div class="alert alert-light-secondary fs-7 py-3 mb-4 d-none" id="cms_actual_banner_api_estado"></div>' +
      (data.items || []).map(renderBannerItem).join("") +
      '<div class="alert alert-light-info fs-7 mb-0">Por ahora se usa como banner estatico. Si despues el frontend lo soporta como carrusel, los items ya quedan preparados.</div>' +
    '</div>';
  }

  function renderBannerItem(item, index) {
    var bg = item.imagen_desktop ? ' style="background-image:url(' + escapeAttr(urlPreviewSeguro(item.imagen_desktop)) + ')"' : "";
    var resumenImagen = resumenUrlMedia(item.imagen_desktop);
    var estadoImagen = esUrlMediaCms(item.imagen_desktop)
      ? '<span class="badge badge-light-success">Imagen Media CMS lista para API</span>'
      : '<span class="badge badge-light-warning">Imagen no guardada en Media CMS</span>';
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
      '<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">' + estadoImagen + '<span class="text-muted fs-8 text-truncate mw-100">Desktop: ' + escapeHtml(resumenImagen) + '</span></div>' +
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
    var inputValue = esImagen && String(value || "").indexOf("data:image/") === 0 ? "" : value;
    var input = '<input class="form-control form-control-sm" ' + dataAttr + '="' + escapeAttr(campo) + '" data-index="' + escapeAttr(index) + '" value="' + escapeAttr(inputValue == null ? "" : inputValue) + '">';
    if (esImagen) {
      input = '<div class="input-group input-group-sm">' +
        input +
        '<button class="btn btn-light-primary" type="button" data-media-picker="' + escapeAttr(contexto) + '" data-index="' + escapeAttr(index) + '" data-field="' + escapeAttr(campo) + '"><i class="bi bi-images"></i> Media</button>' +
      '</div>';
    }
    return '<div class="' + escapeAttr(col || "col-md-6") + '"><label class="form-label fs-8 fw-bold">' + escapeHtml(label) + '</label>' + input + '</div>';
  }

  function inputHomeLista(contexto, index, campo, label, value, col, media) {
    var inputValue = media && String(value || "").indexOf("data:image/") === 0 ? "" : value;
    var input = '<input class="form-control form-control-sm" data-home-list-context="' + escapeAttr(contexto) + '" data-home-list-field="' + escapeAttr(campo) + '" data-index="' + escapeAttr(index) + '" value="' + escapeAttr(inputValue == null ? "" : inputValue) + '">';
    if (media) {
      input = '<div class="input-group input-group-sm">' + input + '<button class="btn btn-light-primary" type="button" data-media-picker="home_' + escapeAttr(contexto) + '" data-index="' + escapeAttr(index) + '" data-field="' + escapeAttr(campo) + '"><i class="bi bi-images"></i> Media</button></div>';
    }
    return '<div class="' + escapeAttr(col || "col-md-6") + '"><label class="form-label fs-8 fw-bold">' + escapeHtml(label) + '</label>' + input + '</div>';
  }

  function inputEsencialPrincipal(campo, label, value, col, media) {
    var inputValue = media && String(value || "").indexOf("data:image/") === 0 ? "" : value;
    var input = '<input class="form-control form-control-sm" data-esencial-principal-field="' + escapeAttr(campo) + '" value="' + escapeAttr(inputValue == null ? "" : inputValue) + '">';
    if (media) {
      input = '<div class="input-group input-group-sm">' + input + '<button class="btn btn-light-primary" type="button" data-media-picker="home_esencial_principal" data-index="0" data-field="' + escapeAttr(campo) + '"><i class="bi bi-images"></i> Media</button></div>';
    }
    return '<div class="' + escapeAttr(col || "col-md-6") + '"><label class="form-label fs-8 fw-bold">' + escapeHtml(label) + '</label>' + input + '</div>';
  }

  function selectorCategoriaEsencialPrincipal(principal) {
    return '<div class="col-md-12">' +
      '<label class="form-label fs-8 fw-bold">Seleccionar categoria principal</label>' +
      '<select class="form-select form-select-sm" data-esencial-categoria-select="principal">' +
        opcionesCategoriasCms(principal && principal.categoria_id ? principal.categoria_id : 0) +
      '</select>' +
      '<div class="text-muted fs-8 mt-1">Al seleccionar se completan titulo, URL publica y path_slug. Puedes ajustar el texto despues si necesitas un titulo comercial.</div>' +
    '</div>';
  }

  function selectorCategoriaHomeLista(contexto, index, item, col) {
    return '<div class="' + escapeAttr(col || "col-md-12") + '">' +
      '<label class="form-label fs-8 fw-bold">Seleccionar categoria</label>' +
      '<select class="form-select form-select-sm" data-home-category-select="' + escapeAttr(contexto) + '" data-index="' + escapeAttr(index) + '">' +
        opcionesCategoriasCms(item && item.categoria_id ? item.categoria_id : 0) +
      '</select>' +
      '<div class="text-muted fs-8 mt-1">Usa la categoria real del catalogo; el CMS deriva URL y slug canonico para frontend.</div>' +
    '</div>';
  }

  function botonUsarImagenCategoria(contexto, index, item) {
    var categoria = categoriaCmsPorId(item && item.categoria_id ? item.categoria_id : 0);
    var imagen = imagenCategoriaCms(categoria);
    var texto = imagen ? "Usar imagen de categoria" : "Categoria sin imagen disponible";
    var clase = imagen ? "btn-light-success" : "btn-light-secondary";
    var ayuda = imagen ? "Copia la imagen de la categoria seleccionada. Si despues no te gusta, puedes reemplazarla desde Media." : "Primero asigna imagen a esa categoria o sube una imagen propia para esta seccion.";
    return '<div class="col-md-12">' +
      '<button class="btn btn-sm ' + clase + '" type="button" data-use-category-image="' + escapeAttr(contexto) + '" data-index="' + escapeAttr(index) + '"' + (!imagen ? ' disabled' : '') + '><i class="bi bi-image"></i> ' + escapeHtml(texto) + '</button>' +
      '<div class="text-muted fs-8 mt-1">' + escapeHtml(ayuda) + '</div>' +
    '</div>';
  }

  function opcionesCategoriasCms(seleccionada) {
    var items = estado.catalogos && Array.isArray(estado.catalogos.categorias) ? estado.catalogos.categorias : [];
    var html = '<option value="">Seleccionar categoria...</option>';
    if (!items.length) {
      return html + '<option value="" disabled>Cargando categorias publicas...</option>';
    }
    items.forEach(function (cat) {
      var id = cat && cat.id != null ? String(cat.id) : "";
      if (!id) return;
      var nombre = cat.nombre_completo || cat.nombre || cat.path_slug || ("Categoria " + id);
      var total = cat.total_productos != null ? " (" + cat.total_productos + ")" : "";
      html += '<option value="' + escapeAttr(id) + '"' + (String(seleccionada || "") === id ? ' selected' : '') + '>' + escapeHtml(nombre + total) + '</option>';
    });
    return html;
  }

  function categoriaCmsPorId(id) {
    var key = String(id || "");
    return estado.catalogos && estado.catalogos.categoriasPorId ? estado.catalogos.categoriasPorId[key] : null;
  }

  function imagenCategoriaCms(categoria) {
    if (!categoria) return "";
    return primeraImagenCategoriaCms(categoria, ["imagen_banner", "imagen_card", "imagen_menu", "imagen", "url_imagen", "imagen_publica", "thumbnail", "foto"]);
  }

  function imagenCardCategoriaCms(categoria) {
    if (!categoria) return "";
    return primeraImagenCategoriaCms(categoria, ["imagen_card", "imagen_menu", "imagen", "url_imagen", "imagen_publica", "thumbnail", "imagen_banner", "foto"]);
  }

  function imagenBannerCategoriaCms(categoria) {
    if (!categoria) return "";
    return primeraImagenCategoriaCms(categoria, ["imagen_banner", "imagen", "url_imagen", "imagen_publica", "imagen_card", "imagen_menu", "thumbnail", "foto"]);
  }

  function primeraImagenCategoriaCms(categoria, campos) {
    if (!categoria) return "";
    for (var i = 0; i < campos.length; i++) {
      var valor = categoria[campos[i]];
      if (valor) return valor;
    }
    var grupos = [categoria.media, categoria.cms, categoria.editorial, categoria.imagenes, categoria.imagenes_catalogo];
    for (var g = 0; g < grupos.length; g++) {
      var grupo = grupos[g];
      if (Array.isArray(grupo)) {
        for (var a = 0; a < grupo.length; a++) {
          var item = grupo[a];
          if (!item || typeof item !== "object") continue;
          for (var ac = 0; ac < campos.length; ac++) {
            if (item[campos[ac]]) return item[campos[ac]];
          }
          if (item.url_imagen) return item.url_imagen;
          if (item.url) return item.url;
        }
        continue;
      }
      if (!grupo || typeof grupo !== "object") continue;
      for (var c = 0; c < campos.length; c++) {
        if (grupo[campos[c]]) return grupo[campos[c]];
      }
      if (grupo.url_imagen) return grupo.url_imagen;
      if (grupo.url) return grupo.url;
    }
    return "";
  }

  function aplicarCategoriaCmsDestino(destino, categoria, conservarImagen) {
    if (!destino || !categoria) return;
    destino.categoria_id = parseInt(categoria.id || "0", 10) || 0;
    destino.titulo = categoria.nombre || categoria.nombre_completo || destino.titulo || "";
    destino.url = categoria.url_canonica || categoria.url || (categoria.path_slug ? "/categoria/" + categoria.path_slug : destino.url || "");
    destino.path_slug = categoria.path_slug || categoria.slug_publico || destino.path_slug || "";
    if (!destino.subtitulo && categoria.descripcion_corta) destino.subtitulo = categoria.descripcion_corta;
    if (!destino.alt) destino.alt = "Categoria " + (categoria.nombre || categoria.nombre_completo || "");
    if (!conservarImagen && !destino.imagen && imagenCategoriaCms(categoria)) {
      destino.imagen = imagenCategoriaCms(categoria);
    }
  }

  function accionesHomeLista(contexto, index) {
    return '<div class="d-flex gap-2">' +
      '<button class="btn btn-sm btn-light" type="button" data-home-list-context="' + escapeAttr(contexto) + '" data-home-list-action="duplicar" data-index="' + escapeAttr(index) + '"><i class="bi bi-copy"></i></button>' +
      '<button class="btn btn-sm btn-light-warning" type="button" data-home-list-context="' + escapeAttr(contexto) + '" data-home-list-action="toggle" data-index="' + escapeAttr(index) + '"><i class="bi bi-eye"></i></button>' +
      '<button class="btn btn-sm btn-light-danger" type="button" data-home-list-context="' + escapeAttr(contexto) + '" data-home-list-action="eliminar" data-index="' + escapeAttr(index) + '"><i class="bi bi-trash"></i></button>' +
    '</div>';
  }

  function homeListaData(contexto) {
    if (contexto === "promos_categoria") return promosCategoriaData();
    if (contexto === "categorias") return categoriasData();
    if (contexto === "marcas") return marcasHomeData();
    if (contexto === "esenciales") return esencialesData();
    return null;
  }

  function actualizarHomeOrdenField(key, campo, valor) {
    var data = estado.datos.home ? estado.datos.home[key] : null;
    if (!data) return;
    if (campo === "visible") data.visible = valor === "1";
    if (campo === "orden") data.orden = parseInt(valor || "10", 10) || 10;
    refrescarJson();
  }

  function ejecutarHomeOrdenAccion(key, accion) {
    var componentes = homeComponentesOrdenables();
    var index = componentes.findIndex(function (item) { return item.key === key; });
    if (index < 0) return;
    if (accion === "toggle") {
      componentes[index].data.visible = componentes[index].data.visible === false;
      renderGrupo();
      return;
    }
    if (accion === "subir" && index > 0) {
      componentes.splice(index - 1, 0, componentes.splice(index, 1)[0]);
    }
    if (accion === "bajar" && index < componentes.length - 1) {
      componentes.splice(index + 1, 0, componentes.splice(index, 1)[0]);
    }
    componentes.forEach(function (item, pos) {
      if (item.data) item.data.orden = (pos + 2) * 10;
    });
    renderGrupo();
  }

  function actualizarHomeListConfig(path, valor) {
    var partes = String(path || "").split(".");
    var contexto = partes.shift();
    var data = homeListaData(contexto);
    if (!data) return;
    var campo = partes.join(".");
    if (campo === "visible") data.visible = valor === "1";
    else if (campo === "orden") data.orden = parseInt(valor || "45", 10) || 45;
    else if (campo === "fuente.limite") setPath(data, campo, Math.max(0, parseInt(valor || "0", 10) || 0));
    else if (campo === "fuente.rellenar_automatico_si_faltan") setPath(data, campo, valor === "1");
    else {
      setPath(data, campo, valor);
      if (contexto === "marcas" && campo === "categoria_contexto.path_slug") {
        data.fuente = data.fuente || {};
        data.fuente.categoria_slug = String(valor || "").trim().replace(/^\/+|\/+$/g, "");
      }
    }
    refrescarJson();
  }

  function actualizarHomeListItem(contexto, index, campo, valor) {
    var data = homeListaData(contexto);
    var item = data && data.items ? data.items[index] : null;
    if (!item) return;
    if (campo === "categoria_id" || campo === "marca_id") item[campo] = parseInt(valor || "0", 10) || 0;
    else if (campo === "visible") item.visible = valor === "1";
    else setPath(item, campo, valor);
    refrescarJson();
  }

  function actualizarEsencialesConfig(campo, valor) {
    var data = esencialesData();
    if (campo === "visible") data.visible = valor === "1";
    else if (campo === "orden") data.orden = parseInt(valor || "70", 10) || 70;
    else setPath(data, campo, valor);
    refrescarJson();
  }

  function actualizarEsencialPrincipal(campo, valor) {
    var data = esencialesData();
    data.categoria_principal = data.categoria_principal || {};
    if (campo === "categoria_id") data.categoria_principal.categoria_id = parseInt(valor || "0", 10) || 0;
    else setPath(data.categoria_principal, campo, valor);
    refrescarJson();
  }

  function seleccionarCategoriaEsencialPrincipal(idCategoria) {
    var categoria = categoriaCmsPorId(idCategoria);
    if (!categoria) return;
    var data = esencialesData();
    data.categoria_principal = data.categoria_principal || {};
    aplicarCategoriaCmsDestino(data.categoria_principal, categoria, !!data.categoria_principal.imagen);
    renderGrupo();
  }

  function seleccionarCategoriaHomeLista(contexto, index, idCategoria) {
    var categoria = categoriaCmsPorId(idCategoria);
    var data = homeListaData(contexto);
    var item = data && data.items ? data.items[index] : null;
    if (!categoria || !item) return;
    aplicarCategoriaCmsDestino(item, categoria, !!item.imagen);
    if (contexto === "categorias") {
      if (!item.imagen_card) item.imagen_card = imagenCardCategoriaCms(categoria);
      if (!item.imagen_banner) item.imagen_banner = imagenBannerCategoriaCms(categoria);
      if (!item.alt) item.alt = "Categoria " + (item.titulo || categoria.nombre || categoria.nombre_completo || "");
    }
    renderGrupo();
  }

  function seleccionarCategoriaHomeMarcas(idCategoria) {
    var categoria = categoriaCmsPorId(idCategoria);
    if (!categoria) return;
    var data = marcasHomeData();
    data.categoria_contexto = data.categoria_contexto || {};
    aplicarCategoriaCmsDestino(data.categoria_contexto, categoria, true);
    delete data.categoria_contexto.subtitulo;
    data.categoria_contexto.imagen = categoria.imagen_card || categoria.imagen_banner || categoria.imagen_menu || data.categoria_contexto.imagen || "";
    data.categoria_contexto.imagen_menu = categoria.imagen_menu || "";
    data.categoria_contexto.imagen_card = categoria.imagen_card || "";
    data.categoria_contexto.imagen_banner = categoria.imagen_banner || "";
    data.categoria_contexto.imagenes_catalogo = categoria.imagenes_catalogo || [];
    data.fuente = data.fuente || {};
    data.fuente.categoria_slug = data.categoria_contexto.path_slug || data.fuente.categoria_slug || "";
    if (!data.titulo || data.titulo === "Marcas destacadas") data.titulo = "Marcas para " + (data.categoria_contexto.titulo || categoria.nombre || "esta categoria");
    if (!data.subtitulo || data.subtitulo === "Entradas rapidas por marca.") data.subtitulo = "Seleccion de marcas relacionadas con esta categoria.";
    renderGrupo();
  }

  function seleccionarMarcaHomeManual(index, idMarca) {
    var data = marcasHomeData();
    var item = data && data.items ? data.items[index] : null;
    var marca = estado.catalogos.marcasPorId ? estado.catalogos.marcasPorId[String(idMarca || "")] : null;
    if (!item || !marca) return;
    item.marca_id = parseInt(marca.id || "0", 10) || 0;
    item.nombre = marca.nombre_publico || marca.nombre || item.nombre || "";
    item.slug = marca.slug_publico || marca.slug || item.slug || "";
    item.slug_publico = item.slug;
    item.url = marca.url || (item.slug ? "/marca/" + item.slug : item.url || "");
    item.logo = marca.logo || item.logo || "";
    item.imagen_banner = marca.imagen_banner || item.imagen_banner || "";
    item.alt_logo = marca.alt_logo || ("Logo de " + item.nombre);
    item.descripcion_corta = marca.descripcion_corta || item.descripcion_corta || "";
    item.visible = true;
    item.visible_frontend = true;
    renderGrupo();
  }

  function usarImagenCategoriaEsenciales(contexto, index) {
    var target = null;
    if (contexto === "principal") {
      target = esencialesData().categoria_principal || {};
      esencialesData().categoria_principal = target;
    } else {
      var data = homeListaData(contexto);
      target = data && data.items ? data.items[index] : null;
    }
    if (!target) return;
    var categoria = categoriaCmsPorId(target.categoria_id);
    var imagen = imagenCategoriaCms(categoria);
    if (!imagen) {
      if (contexto === "categorias") setHomeCategoriasEstado("La categoria seleccionada no tiene imagen editorial disponible. Sube una imagen propia o configura imagen en CMS > Frontend > Categorias.", "warning");
      else setHomeListaEstado("esenciales", "La categoria seleccionada no tiene imagen editorial disponible. Sube una imagen propia o configura imagen en CMS > Frontend > Categorias.", "warning");
      return;
    }
    if (contexto === "categorias") {
      target.imagen_card = imagenCardCategoriaCms(categoria) || imagen;
      target.imagen_banner = target.imagen_banner || imagenBannerCategoriaCms(categoria) || imagen;
    } else {
      target.imagen = imagen;
    }
    if (!target.alt) target.alt = "Categoria " + (categoria.nombre || categoria.nombre_completo || target.titulo || "");
    if (contexto === "categorias") setHomeCategoriasEstado("Imagen de categoria aplicada al borrador.", "success");
    else setHomeListaEstado("esenciales", "Imagen de categoria aplicada al borrador.", "success");
    renderGrupo();
  }

  function guardarBorradorSeccion(contexto) {
    if (guardarBorradorFrontendLocal(false)) {
      setHomeListaEstado(contexto, "Borrador guardado localmente. Puedes volver despues aunque falte imagen; para publicar si se validara la imagen.", "success");
    }
  }

  function ejecutarHomeListAccion(contexto, accion, index) {
    var data = homeListaData(contexto);
    var items = data && data.items ? data.items : [];
    if (!items[index]) return;
    if (accion === "duplicar") {
      var copia = JSON.parse(JSON.stringify(items[index]));
      copia.orden = (items.length + 1) * 10;
      items.splice(index + 1, 0, copia);
    }
    if (accion === "toggle") items[index].visible = !items[index].visible;
    if (accion === "eliminar" && items.length > 1) items.splice(index, 1);
    if (contexto === "esenciales" && items.length > 3) items.splice(3);
    normalizarOrden(items);
    renderGrupo();
  }

  function agregarHomePromoCategoria() {
    var items = promosCategoriaData().items;
    items.push({ titulo: "Nueva promo", subtitulo: "", imagen: "", alt: "", url: "/categoria/nueva-categoria", path_slug: "nueva-categoria", categoria_id: 0, visible: true, orden: (items.length + 1) * 10 });
    renderGrupo();
  }

  function agregarHomeMarca() {
    var items = marcasHomeData().items;
    items.push({ marca_id: 0, nombre: "Nueva marca", subtitulo: "Ver marca", slug: "nueva-marca", slug_publico: "nueva-marca", logo: "", imagen_banner: "", alt_logo: "Logo de nueva marca", descripcion_corta: "", url: "/marca/nueva-marca", visible: true, visible_frontend: true, orden: (items.length + 1) * 10 });
    renderGrupo();
  }

  function agregarHomeEsencial() {
    var items = esencialesData().items;
    if (items.length >= 3) {
      setHomeListaEstado("esenciales", "Maximo 3 cards visibles para Esenciales Artiani.", "warning");
      return;
    }
    items.push({ categoria_id: 0, titulo: "Nuevo esencial", subtitulo: "", url: "", path_slug: "", imagen: "", alt: "", objetivo: "", visible: true, orden: (items.length + 1) * 10 });
    renderGrupo();
  }

  function publicarHomePromosCategoria() {
    completarImagenesPromosCategoriaDesdeCategoria();
    publicarHomeLista("promos_categoria", promosCategoriaData(), "/cms/frontend_home_promos_categoria_publicar_erp", "promos_categoria");
  }

  function publicarHomeMarcas() {
    publicarHomeLista("marcas", marcasHomeData(), "/cms/frontend_home_marcas_publicar_erp", "marcas_destacadas");
  }

  function publicarHomeEsenciales() {
    completarImagenesEsencialesDesdeCategoria();
    publicarHomeLista("esenciales", esencialesData(), "/cms/frontend_home_esenciales_publicar_erp", "bloque_editorial_cards");
  }

  function publicarHomeLista(contexto, data, endpoint, tipoContrato) {
    var validacion = validarHomeLista(contexto, data);
    if (validacion) {
      setHomeListaEstado(contexto, validacion, "warning");
      return;
    }
    var boton = $("cms_actual_home_" + contextoBoton(contexto) + "_publicar");
    var form = new FormData();
    form.append("_csrf", window.ERP_CSRF_TOKEN || "");
    form.append("payload_json", JSON.stringify(data));
    if (boton) boton.disabled = true;
    setHomeListaEstado(contexto, "Publicando " + tipoContrato + " en la API...", "info");
    fetch(endpoint, {
      method: "POST",
      body: form,
      credentials: "same-origin",
      headers: {
        "X-CSRF-Token": window.ERP_CSRF_TOKEN || "",
        "Accept": "application/json",
        "X-Requested-With": "XMLHttpRequest"
      }
    }).then(function (response) {
      return response.text().then(function (text) {
        var json = null;
        try {
          json = JSON.parse(text);
        } catch (error) {
          throw new Error("Respuesta no JSON del servidor (" + response.status + "): " + text.substring(0, 140));
        }
        if (!response.ok && json && json.mensaje) throw new Error(json.mensaje);
        return json;
      });
    }).then(function (json) {
      if (!json || json.error) throw new Error(json && json.mensaje ? json.mensaje : "No se pudo publicar");
      setHomeListaEstado(contexto, "Publicado. Verifica con Ver API publicada.", "success");
      consultarEstadoHomePublicado();
      consultarApiHomeLista(contexto);
    }).catch(function (error) {
      setHomeListaEstado(contexto, error.message || "Error al publicar.", "danger");
    }).finally(function () {
      if (boton) boton.disabled = false;
    });
  }

  function validarHomeLista(contexto, data) {
    if (!data || !data.visible) return "Activa la seccion antes de publicar.";
    var items = (data.items || []).filter(function (item) { return item && item.visible !== false; });
    if (contexto === "esenciales" && items.length > 3) return "Esenciales permite maximo 3 cards visibles.";
    if (contexto === "marcas") {
      var fuente = data.fuente || {};
      var contextoCategoria = data.categoria_contexto || {};
      var modo = fuente.modo || "mixto";
      var categoriaSlug = String(fuente.categoria_slug || contextoCategoria.path_slug || "").trim();
      if (modo !== "manual" && !categoriaSlug) return "Selecciona una categoria origen para marcas destacadas.";
      if (modo === "manual" && !items.length) return "Deja al menos una marca manual visible.";
    }
    if (contexto !== "marcas" && !items.length) return "Deja al menos un item visible.";
    for (var i = 0; i < items.length; i++) {
      var item = items[i] || {};
      if (contexto === "marcas") {
        if (modo !== "manual" && !String(item.nombre || item.url || item.slug || item.logo || "").trim() && !(parseInt(item.marca_id || "0", 10) > 0)) continue;
        if (!String(item.nombre || "").trim()) return "Cada marca visible necesita nombre.";
        if (!String(item.url || "").trim()) return "Cada marca visible necesita URL publica.";
      } else {
        if (!String(item.titulo || "").trim()) return "Cada item visible necesita titulo.";
        if (!String(item.url || "").trim()) return "Cada item visible necesita URL publica.";
        if (contexto === "esenciales" && !(parseInt(item.categoria_id || "0", 10) > 0) && !String(item.path_slug || "").trim()) return "Selecciona categoria o deja path_slug canonico en cada card esencial visible.";
        if (contexto === "promos_categoria" && !String(item.path_slug || "").trim()) return "Cada promo visible necesita path_slug canonico.";
        item.imagen = normalizarUrlMediaCms(item.imagen);
        if (!String(item.imagen || "").trim()) return "Cada item visible necesita imagen.";
        if (!esUrlImagenPublicaPersistente(item.imagen)) return "Item " + (i + 1) + ": imagen no publica o temporal.";
        if (!String(item.alt || "").trim()) return "Item " + (i + 1) + ": falta alt.";
      }
    }
    return "";
  }

  function completarImagenesPromosCategoriaDesdeCategoria() {
    var data = promosCategoriaData();
    (data.items || []).forEach(function (item) {
      if (!item || item.imagen || !item.categoria_id) return;
      var categoria = categoriaCmsPorId(item.categoria_id);
      var imagen = imagenCategoriaCms(categoria);
      if (!imagen) return;
      item.imagen = imagen;
      if (!item.alt) item.alt = "Categoria " + (item.titulo || categoria.nombre || "");
    });
  }

  function completarImagenesEsencialesDesdeCategoria() {
    var data = esencialesData();
    if (!data) return;
    if (data.categoria_principal && !data.categoria_principal.imagen && data.categoria_principal.categoria_id) {
      var categoriaPrincipal = categoriaCmsPorId(data.categoria_principal.categoria_id);
      var imagenPrincipal = imagenCategoriaCms(categoriaPrincipal);
      if (imagenPrincipal) data.categoria_principal.imagen = imagenPrincipal;
      if (imagenPrincipal && !data.categoria_principal.alt) data.categoria_principal.alt = "Categoria " + (data.categoria_principal.titulo || "");
    }
    (data.items || []).forEach(function (item) {
      if (!item || item.imagen || !item.categoria_id) return;
      var categoria = categoriaCmsPorId(item.categoria_id);
      var imagen = imagenCategoriaCms(categoria);
      if (!imagen) return;
      item.imagen = imagen;
      if (!item.alt) item.alt = "Categoria " + (item.titulo || "");
    });
  }

  function consultarApiHomePromosCategoria() {
    consultarApiHomeLista("promos_categoria");
  }

  function consultarApiHomeMarcas() {
    consultarApiHomeLista("marcas");
  }

  function consultarApiHomeEsenciales() {
    consultarApiHomeLista("esenciales");
  }

  function consultarApiHomeLista(contexto) {
    var boton = $("cms_actual_home_" + contextoBoton(contexto) + "_api");
    if (boton) boton.disabled = true;
    setHomeListaApi(contexto, "Consultando /ecommercePublico/cms_frontend?pagina=home...", "info");
    fetch("/ecommercePublico/cms_frontend?pagina=home", {
      method: "GET",
      credentials: "same-origin",
      headers: {
        "Accept": "application/json",
        "X-Requested-With": "XMLHttpRequest"
      }
    }).then(function (response) {
      return response.text().then(function (text) {
        var json = null;
        try {
          json = JSON.parse(text);
        } catch (error) {
          throw new Error("Respuesta no JSON del servidor (" + response.status + "): " + text.substring(0, 140));
        }
        if (!response.ok) throw new Error((json && json.mensaje) || "No se pudo consultar la API publica");
        return json;
      });
    }).then(function (json) {
      var depurar = json && json.depurar ? json.depurar : {};
      var slot = contexto === "promos_categoria" ? "home.promos" : (contexto === "marcas" ? "home.marcas" : "home.esenciales");
      var bloques = bloquesSlotPublicado(depurar, slot);
      if (!bloques.length) {
        setHomeListaApi(contexto, "No hay contenido publicado en " + slot + ".", "warning");
        return;
      }
      var bloque = bloques[0] || {};
      var items = Array.isArray(bloque.items) ? bloque.items : [];
      setHomeListaApi(contexto, '<div class="fw-bold mb-2">' + escapeHtml(slot) + ' publicado</div><div><span class="fw-semibold">Fuente:</span> ' + escapeHtml(depurar.fuente || "sin fuente") + '</div><div><span class="fw-semibold">Tipo:</span> ' + escapeHtml(bloque.tipo || bloque.frontend_tipo || "") + '</div><div><span class="fw-semibold">Titulo:</span> ' + escapeHtml(bloque.titulo || "") + '</div><div><span class="fw-semibold">Items:</span> ' + escapeHtml(items.length) + '</div>' + resumenImagenesItemsApi(items, contexto), "success", true);
    }).catch(function (error) {
      setHomeListaApi(contexto, error.message || "Error al consultar API publicada.", "danger");
    }).finally(function () {
      if (boton) boton.disabled = false;
    });
  }

  function contextoBoton(contexto) {
    if (contexto === "promos_categoria") return "promos";
    if (contexto === "marcas") return "marcas";
    if (contexto === "esenciales") return "esenciales";
    return contexto;
  }

  function setHomeListaEstado(contexto, mensaje, tipo) {
    setText("cms_actual_estado", mensaje);
    var node = $("cms_actual_home_" + contextoBoton(contexto) + "_estado");
    if (!node) return;
    node.className = "alert fs-7 py-3 mb-4 alert-light-" + (tipo || "info");
    node.textContent = mensaje;
  }

  function setHomeListaApi(contexto, mensaje, tipo, esHtml) {
    setText("cms_actual_estado", esHtml ? "API publicada consultada" : mensaje);
    var node = $("cms_actual_home_" + contextoBoton(contexto) + "_api_estado");
    if (!node) return;
    node.className = "alert fs-7 py-3 mb-4 alert-light-" + (tipo || "info");
    if (esHtml) node.innerHTML = mensaje;
    else node.textContent = mensaje;
  }

  function actualizarCompraGuiadaField(campo, valor) {
    var data = compraGuiadaData();
    if (campo === "visible") data.visible = valor === "1";
    else if (campo === "orden") data.orden = parseInt(valor || "80", 10) || 80;
    else setPath(data, campo, valor);
    refrescarJson();
  }

  function actualizarCompraGuiadaConfig(campo, valor) {
    var data = compraGuiadaData();
    if (!data.config) data.config = {};
    if (campo === "mostrar_mascotas" || campo === "mostrar_necesidades") {
      data.config[campo] = valor === "1";
    } else {
      data.config[campo] = valor;
    }
    refrescarJson();
  }

  function guardarBorradorCompraGuiadaHome() {
    if (guardarBorradorFrontendLocal(false)) {
      setCompraGuiadaEstado("Borrador local guardado. Para enviarlo al frontend usa Guardar y publicar compra guiada.", "success");
    } else {
      setCompraGuiadaEstado("No se pudo guardar el borrador local.", "danger");
    }
  }

  function publicarCompraGuiadaHome() {
    var data = compraGuiadaData();
    if (!data.visible) {
      setCompraGuiadaEstado("Activa la seccion antes de publicarla.", "warning");
      return;
    }
    if (!String(data.titulo || "").trim()) {
      setCompraGuiadaEstado("Captura el titulo antes de publicar.", "warning");
      return;
    }
    var config = data.config || {};
    if (!config.mostrar_mascotas && !config.mostrar_necesidades) {
      setCompraGuiadaEstado("Activa mascotas o necesidades para que frontend tenga algo que pintar.", "warning");
      return;
    }
    var boton = $("cms_actual_home_compra_guiada_publicar");
    var form = new FormData();
    form.append("_csrf", window.ERP_CSRF_TOKEN || "");
    form.append("payload_json", JSON.stringify(data));
    if (boton) boton.disabled = true;
    setCompraGuiadaEstado("Publicando Compra guiada en la API...", "info");
    fetch("/cms/frontend_home_compra_guiada_publicar_erp", {
      method: "POST",
      body: form,
      credentials: "same-origin",
      headers: {
        "X-CSRF-Token": window.ERP_CSRF_TOKEN || "",
        "Accept": "application/json",
        "X-Requested-With": "XMLHttpRequest"
      }
    }).then(function (response) {
      return response.text().then(function (text) {
        var json = null;
        try {
          json = JSON.parse(text);
        } catch (error) {
          throw new Error("Respuesta no JSON del servidor (" + response.status + "): " + text.substring(0, 140));
        }
        if (!response.ok && json && json.mensaje) throw new Error(json.mensaje);
        return json;
      });
    }).then(function (json) {
      if (!json || json.error) throw new Error(json && json.mensaje ? json.mensaje : "No se pudo publicar Compra guiada");
      setCompraGuiadaEstado("Compra guiada publicada. Verifica con Ver API publicada.", "success");
      consultarEstadoHomePublicado();
      consultarApiCompraGuiadaHome();
    }).catch(function (error) {
      setCompraGuiadaEstado(error.message || "Error al publicar Compra guiada.", "danger");
    }).finally(function () {
      if (boton) boton.disabled = false;
    });
  }

  function consultarApiCompraGuiadaHome() {
    var boton = $("cms_actual_home_compra_guiada_api");
    if (boton) boton.disabled = true;
    setCompraGuiadaApi("Consultando /ecommercePublico/cms_frontend?pagina=home...", "info");
    fetch("/ecommercePublico/cms_frontend?pagina=home", {
      method: "GET",
      credentials: "same-origin",
      headers: {
        "Accept": "application/json",
        "X-Requested-With": "XMLHttpRequest"
      }
    }).then(function (response) {
      return response.text().then(function (text) {
        var json = null;
        try {
          json = JSON.parse(text);
        } catch (error) {
          throw new Error("Respuesta no JSON del servidor (" + response.status + "): " + text.substring(0, 140));
        }
        if (!response.ok) throw new Error((json && json.mensaje) || "No se pudo consultar la API publica");
        return json;
      });
    }).then(function (json) {
      var depurar = json && json.depurar ? json.depurar : {};
      var bloques = bloquesSlotPublicado(depurar, "home.compra_guiada");
      if (!bloques.length) {
        setCompraGuiadaApi("No hay Compra guiada publicada en home.compra_guiada.", "warning");
        return;
      }
      var bloque = bloques[0] || {};
      var config = bloque.config || {};
      setCompraGuiadaApi(
        '<div class="fw-bold mb-2">Compra guiada publicada para Home</div>' +
        '<div><span class="fw-semibold">Fuente:</span> ' + escapeHtml(depurar.fuente || "sin fuente") + '</div>' +
        '<div><span class="fw-semibold">Titulo:</span> ' + escapeHtml(bloque.titulo || "") + '</div>' +
        '<div><span class="fw-semibold">Mascotas:</span> ' + escapeHtml(config.mostrar_mascotas ? "si" : "no") + '</div>' +
        '<div><span class="fw-semibold">Necesidades:</span> ' + escapeHtml(config.mostrar_necesidades ? "si" : "no") + '</div>' +
        '<div><span class="fw-semibold">Prioridad:</span> ' + escapeHtml(config.prioridad || "") + '</div>',
        "success",
        true
      );
    }).catch(function (error) {
      setCompraGuiadaApi(error.message || "Error al consultar API publicada.", "danger");
    }).finally(function () {
      if (boton) boton.disabled = false;
    });
  }

  function setCompraGuiadaEstado(mensaje, tipo) {
    setText("cms_actual_estado", mensaje);
    var node = $("cms_actual_home_compra_guiada_estado");
    if (!node) return;
    node.className = "alert fs-7 py-3 mb-4 alert-light-" + (tipo || "info");
    node.textContent = mensaje;
  }

  function setCompraGuiadaApi(mensaje, tipo, esHtml) {
    setText("cms_actual_estado", esHtml ? "API publicada consultada" : mensaje);
    var node = $("cms_actual_home_compra_guiada_api_estado");
    if (!node) return;
    node.className = "alert fs-7 py-3 mb-0 alert-light-" + (tipo || "info");
    if (esHtml) node.innerHTML = mensaje;
    else node.textContent = mensaje;
  }

  function actualizarBannerConfig(campo, valor) {
    var data = bannerData();
    if (campo === "visible") data.visible = valor === "1";
    else setPath(data, campo, valor);
    refrescarJson();
  }

  /**
   * IA: Codex GPT-5 | Fecha: 2026-08-31
   * Proposito: publicar el editor `home_hero_carrusel` en el slot publico `home.hero`.
   * Impacto: CMS Frontend Home; garantiza media.imagen_desktop/mobile, items[].imagen_desktop/mobile y alt para frontend.
   */
  function publicarHeroCarrusel() {
    var data = heroData();
    var validacion = validarHeroCarrusel(data);
    if (validacion) {
      setHeroEstado(validacion, "warning");
      renderGrupo();
      return;
    }
    var boton = $("cms_actual_hero_publicar");
    var form = new FormData();
    form.append("_csrf", window.ERP_CSRF_TOKEN || "");
    form.append("payload_json", JSON.stringify(data));
    if (boton) boton.disabled = true;
    setHeroEstado("Publicando hero en la API...", "info");
    fetch("/cms/frontend_home_banner_publicar_erp", {
      method: "POST",
      body: form,
      credentials: "same-origin",
      headers: {
        "X-CSRF-Token": window.ERP_CSRF_TOKEN || "",
        "Accept": "application/json",
        "X-Requested-With": "XMLHttpRequest"
      }
    }).then(function (response) {
      return response.text().then(function (text) {
        var json = null;
        try {
          json = JSON.parse(text);
        } catch (error) {
          throw new Error("Respuesta no JSON del servidor (" + response.status + "): " + text.substring(0, 140));
        }
        if (!response.ok && json && json.mensaje) throw new Error(json.mensaje);
        return json;
      });
    }).then(function (json) {
      if (!json || json.error) throw new Error(json && json.mensaje ? json.mensaje : "No se pudo publicar hero");
      setHeroEstado("Hero publicado. Verifica con Ver API publicada.", "success");
      consultarEstadoHomePublicado();
      consultarApiHeroCarrusel();
    }).catch(function (error) {
      setHeroEstado(error.message || "Error al publicar hero.", "danger");
    }).finally(function () {
      if (boton) boton.disabled = false;
    });
  }

  function validarHeroCarrusel(data) {
    if (!data || !data.visible) return "Activa el hero antes de publicar.";
    var visibles = (data.items || []).filter(function (item) { return item && item.visible !== false; });
    if (!visibles.length) return "Deja al menos un slide visible.";
    for (var i = 0; i < visibles.length; i++) {
      var item = visibles[i];
      item.imagen_desktop = normalizarUrlMediaCms(item.imagen_desktop);
      if (item.imagen_mobile) item.imagen_mobile = normalizarUrlMediaCms(item.imagen_mobile);
      if (!String(item.imagen_desktop || "").trim()) return "Slide " + (i + 1) + ": falta imagen desktop.";
      if (!esUrlMediaCms(item.imagen_desktop)) return "Slide " + (i + 1) + ": usa imagen de Media CMS.";
      if (item.imagen_mobile && !esUrlMediaCms(item.imagen_mobile)) return "Slide " + (i + 1) + ": imagen mobile no es Media CMS.";
      if (!String(item.alt || "").trim()) return "Slide " + (i + 1) + ": falta alt.";
    }
    return "";
  }

  function consultarApiHeroCarrusel() {
    var boton = $("cms_actual_hero_api");
    if (boton) boton.disabled = true;
    setHeroApi("Consultando /ecommercePublico/cms_frontend?pagina=home...", "info");
    fetch("/ecommercePublico/cms_frontend?pagina=home", {
      method: "GET",
      credentials: "same-origin",
      headers: {
        "Accept": "application/json",
        "X-Requested-With": "XMLHttpRequest"
      }
    }).then(function (response) {
      return response.text().then(function (text) {
        var json = null;
        try {
          json = JSON.parse(text);
        } catch (error) {
          throw new Error("Respuesta no JSON del servidor (" + response.status + "): " + text.substring(0, 140));
        }
        if (!response.ok) throw new Error((json && json.mensaje) || "No se pudo consultar la API publica");
        return json;
      });
    }).then(function (json) {
      var depurar = json && json.depurar ? json.depurar : {};
      var bloque = bloqueHomeHeroPublicado(depurar);
      if (!bloque) {
        setHeroApi("No hay hero publicado en home.hero.", "warning");
        return;
      }
      var media = bloque.media || {};
      var items = Array.isArray(bloque.items) ? bloque.items : [];
      setHeroApi(
        '<div class="fw-bold mb-2">Hero publicado para frontend</div>' +
        '<div><span class="fw-semibold">Fuente:</span> ' + escapeHtml(depurar.fuente || "sin fuente") + '</div>' +
        '<div class="text-break"><span class="fw-semibold">media.desktop:</span> ' + escapeHtml(media.imagen_desktop || "") + '</div>' +
        '<div class="text-break"><span class="fw-semibold">media.mobile:</span> ' + escapeHtml(media.imagen_mobile || "") + '</div>' +
        '<div><span class="fw-semibold">media.alt:</span> ' + escapeHtml(media.alt || "") + '</div>' +
        '<div><span class="fw-semibold">Items:</span> ' + escapeHtml(items.length) + '</div>' +
        resumenImagenesItemsApi(items, "hero"),
        "success",
        true
      );
    }).catch(function (error) {
      setHeroApi(error.message || "Error al consultar API publicada.", "danger");
    }).finally(function () {
      if (boton) boton.disabled = false;
    });
  }

  function setHeroEstado(mensaje, tipo) {
    setText("cms_actual_estado", mensaje);
    var node = $("cms_actual_hero_estado");
    if (!node) return;
    node.className = "alert fs-7 py-3 mb-4 alert-light-" + (tipo || "info");
    node.textContent = mensaje;
  }

  function setHeroApi(mensaje, tipo, esHtml) {
    setText("cms_actual_estado", esHtml ? "API publicada consultada" : mensaje);
    var node = $("cms_actual_hero_api_estado");
    if (!node) return;
    node.className = "alert fs-7 py-3 mb-4 alert-light-" + (tipo || "info");
    if (esHtml) node.innerHTML = mensaje;
    else node.textContent = mensaje;
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

  function publicarBannerHome() {
    var data = bannerData();
    var item = (data.items || []).filter(function (actual) {
      return actual && actual.visible !== false;
    })[0];
    if (!item) {
      setBannerEstado("No hay ningun banner visible para publicar.", "warning");
      return;
    }
    item.imagen_desktop = normalizarUrlMediaCms(item.imagen_desktop);
    if (!esUrlMediaCms(item.imagen_desktop)) {
      setBannerEstado("Selecciona una imagen guardada en Media CMS. Si apenas elegiste un archivo local, primero usa Subir y usar.", "warning");
      renderGrupo();
      return;
    }
    if (item.imagen_mobile) item.imagen_mobile = normalizarUrlMediaCms(item.imagen_mobile);
    if (!item.alt) {
      setBannerEstado("Falta el texto alt del banner. Es obligatorio para publicar.", "warning");
      return;
    }
    var boton = $("cms_actual_banner_publicar");
    var form = new FormData();
    form.append("_csrf", window.ERP_CSRF_TOKEN || "");
    form.append("payload_json", JSON.stringify(data));
    if (boton) boton.disabled = true;
    setBannerEstado("Publicando banner en la API...", "info");
    fetch("/cms/frontend_home_banner_publicar_erp", {
      method: "POST",
      body: form,
      credentials: "same-origin",
      headers: {
        "X-CSRF-Token": window.ERP_CSRF_TOKEN || "",
        "Accept": "application/json",
        "X-Requested-With": "XMLHttpRequest"
      }
    }).then(function (response) {
      return response.text().then(function (text) {
        var json = null;
        try {
          json = JSON.parse(text);
        } catch (error) {
          throw new Error("Respuesta no JSON del servidor (" + response.status + "): " + text.substring(0, 140));
        }
        if (!response.ok && json && json.mensaje) {
          throw new Error(json.mensaje);
        }
        return json;
      });
    }).then(function (json) {
      if (!json || json.error) {
        throw new Error(json && json.mensaje ? json.mensaje : "No se pudo publicar banner");
      }
      setBannerEstado("Banner publicado. El endpoint /ecommercePublico/contenido_pagina?pagina=home ya debe entregar esta imagen.", "success");
      consultarEstadoHomePublicado();
    }).catch(function (error) {
      setBannerEstado(error.message || "Error al publicar banner.", "danger");
    }).finally(function () {
      if (boton) boton.disabled = false;
    });
  }

  function setBannerEstado(mensaje, tipo) {
    setText("cms_actual_estado", mensaje);
    var node = $("cms_actual_banner_estado");
    if (!node) return;
    node.className = "alert fs-7 py-3 mb-4 alert-light-" + (tipo || "info");
    node.textContent = mensaje;
  }

  function consultarApiBannerHome() {
    var node = $("cms_actual_banner_api_estado");
    var boton = $("cms_actual_banner_api");
    if (node) {
      node.className = "alert alert-light-info fs-7 py-3 mb-4";
      node.textContent = "Consultando /ecommercePublico/contenido_pagina?pagina=home...";
    }
    if (boton) boton.disabled = true;
    fetch("/ecommercePublico/contenido_pagina?pagina=home", {
      method: "GET",
      credentials: "same-origin",
      headers: {
        "Accept": "application/json",
        "X-Requested-With": "XMLHttpRequest"
      }
    }).then(function (response) {
      return response.text().then(function (text) {
        var json = null;
        try {
          json = JSON.parse(text);
        } catch (error) {
          throw new Error("Respuesta no JSON del servidor (" + response.status + "): " + text.substring(0, 140));
        }
        if (!response.ok) {
          throw new Error((json && json.mensaje) || "No se pudo consultar la API publica");
        }
        return json;
      });
    }).then(function (json) {
      var depurar = json && json.depurar ? json.depurar : {};
      var bloque = bloqueHomeHeroPublicado(depurar);
      if (!bloque) {
        mostrarApiBannerHome("No hay banner publicado en home.hero. El frontend usara fallback/default.", "warning");
        return;
      }
      var media = bloque.media || {};
      mostrarApiBannerHome(
        '<div class="fw-bold mb-2">Banner publicado para frontend</div>' +
        '<div><span class="fw-semibold">Fuente:</span> ' + escapeHtml(depurar.fuente || "sin fuente") + '</div>' +
        '<div><span class="fw-semibold">Titulo:</span> ' + escapeHtml(bloque.titulo || "") + '</div>' +
        '<div class="text-break"><span class="fw-semibold">Desktop:</span> ' + escapeHtml(media.imagen_desktop || "") + '</div>' +
        '<div class="text-break"><span class="fw-semibold">Mobile:</span> ' + escapeHtml(media.imagen_mobile || "") + '</div>' +
        '<div><span class="fw-semibold">Alt:</span> ' + escapeHtml(media.alt || "") + '</div>',
        "success",
        true
      );
    }).catch(function (error) {
      mostrarApiBannerHome(error.message || "Error al consultar API publicada.", "danger");
    }).finally(function () {
      if (boton) boton.disabled = false;
    });
  }

  function bloqueHomeHeroPublicado(depurar) {
    var slots = depurar && Array.isArray(depurar.slots) ? depurar.slots : [];
    for (var i = 0; i < slots.length; i++) {
      if (!slots[i] || slots[i].slot !== "home.hero") continue;
      var bloques = Array.isArray(slots[i].bloques) ? slots[i].bloques : [];
      for (var j = 0; j < bloques.length; j++) {
        if (bloques[j] && bloques[j].media && bloques[j].media.imagen_desktop) {
          return bloques[j];
        }
      }
    }
    return null;
  }

  function mostrarApiBannerHome(mensaje, tipo, esHtml) {
    setText("cms_actual_estado", esHtml ? "API publicada consultada" : mensaje);
    var node = $("cms_actual_banner_api_estado");
    if (!node) return;
    node.className = "alert fs-7 py-3 mb-4 alert-light-" + (tipo || "info");
    if (esHtml) {
      node.innerHTML = mensaje;
    } else {
      node.textContent = mensaje;
    }
  }

  function publicarPromoHome() {
    var data = promoData();
    var visibles = (data.items || []).filter(function (item) {
      return item && item.visible !== false && String(item.texto || "").trim() !== "";
    });
    if (!data.visible || !visibles.length) {
      setPromoEstado("Captura al menos un aviso visible con texto antes de publicar.", "warning");
      return;
    }
    var boton = $("cms_actual_promo_publicar");
    var form = new FormData();
    form.append("_csrf", window.ERP_CSRF_TOKEN || "");
    form.append("payload_json", JSON.stringify(data));
    if (boton) boton.disabled = true;
    setPromoEstado("Publicando promo en la API...", "info");
    fetch("/cms/frontend_home_promo_publicar_erp", {
      method: "POST",
      body: form,
      credentials: "same-origin",
      headers: {
        "X-CSRF-Token": window.ERP_CSRF_TOKEN || "",
        "Accept": "application/json",
        "X-Requested-With": "XMLHttpRequest"
      }
    }).then(function (response) {
      return response.text().then(function (text) {
        var json = null;
        try {
          json = JSON.parse(text);
        } catch (error) {
          throw new Error("Respuesta no JSON del servidor (" + response.status + "): " + text.substring(0, 140));
        }
        if (!response.ok && json && json.mensaje) {
          throw new Error(json.mensaje);
        }
        return json;
      });
    }).then(function (json) {
      if (!json || json.error) {
        throw new Error(json && json.mensaje ? json.mensaje : "No se pudo publicar promo");
      }
      setPromoEstado("Promo publicada. El endpoint /ecommercePublico/contenido_pagina?pagina=home ya debe entregar home.promo.", "success");
      consultarEstadoHomePublicado();
    }).catch(function (error) {
      setPromoEstado(error.message || "Error al publicar promo.", "danger");
    }).finally(function () {
      if (boton) boton.disabled = false;
    });
  }

  function setPromoEstado(mensaje, tipo) {
    setText("cms_actual_estado", mensaje);
    var node = $("cms_actual_promo_estado");
    if (!node) return;
    node.className = "alert fs-7 py-3 mb-4 alert-light-" + (tipo || "info");
    node.textContent = mensaje;
  }

  function consultarApiPromoHome() {
    var node = $("cms_actual_promo_api_estado");
    var boton = $("cms_actual_promo_api");
    if (node) {
      node.className = "alert alert-light-info fs-7 py-3 mb-4";
      node.textContent = "Consultando /ecommercePublico/contenido_pagina?pagina=home...";
    }
    if (boton) boton.disabled = true;
    fetch("/ecommercePublico/contenido_pagina?pagina=home", {
      method: "GET",
      credentials: "same-origin",
      headers: {
        "Accept": "application/json",
        "X-Requested-With": "XMLHttpRequest"
      }
    }).then(function (response) {
      return response.text().then(function (text) {
        var json = null;
        try {
          json = JSON.parse(text);
        } catch (error) {
          throw new Error("Respuesta no JSON del servidor (" + response.status + "): " + text.substring(0, 140));
        }
        if (!response.ok) {
          throw new Error((json && json.mensaje) || "No se pudo consultar la API publica");
        }
        return json;
      });
    }).then(function (json) {
      var depurar = json && json.depurar ? json.depurar : {};
      var bloques = bloquesSlotPublicado(depurar, "home.promo");
      if (!bloques.length) {
        mostrarApiPromoHome("No hay promo publicada en home.promo. El frontend usara fallback/default.", "warning");
        return;
      }
      mostrarApiPromoHome(
        '<div class="fw-bold mb-2">Promos publicadas para frontend</div>' +
        '<div><span class="fw-semibold">Fuente:</span> ' + escapeHtml(depurar.fuente || "sin fuente") + '</div>' +
        bloques.map(function (bloque, index) {
          return '<div class="mt-2"><span class="fw-semibold">Aviso ' + escapeHtml(index + 1) + ':</span> ' + escapeHtml(bloque.texto || bloque.titulo || "") + '</div>';
        }).join(""),
        "success",
        true
      );
    }).catch(function (error) {
      mostrarApiPromoHome(error.message || "Error al consultar API publicada.", "danger");
    }).finally(function () {
      if (boton) boton.disabled = false;
    });
  }

  function bloquesSlotPublicado(depurar, slotCodigo) {
    var slots = depurar && Array.isArray(depurar.slots) ? depurar.slots : [];
    for (var i = 0; i < slots.length; i++) {
      if (!slots[i] || slots[i].slot !== slotCodigo) continue;
      return Array.isArray(slots[i].bloques) ? slots[i].bloques : [];
    }
    return [];
  }

  function mostrarApiPromoHome(mensaje, tipo, esHtml) {
    setText("cms_actual_estado", esHtml ? "API publicada consultada" : mensaje);
    var node = $("cms_actual_promo_api_estado");
    if (!node) return;
    node.className = "alert fs-7 py-3 mb-4 alert-light-" + (tipo || "info");
    if (esHtml) {
      node.innerHTML = mensaje;
    } else {
      node.textContent = mensaje;
    }
  }

  function consultarApiGlobalFrontend() {
    var node = $("cms_actual_global_api_estado");
    var boton = $("cms_actual_global_api");
    if (node) {
      node.className = "alert alert-light-info fs-7 py-3 mb-4";
      node.textContent = "Consultando /ecommercePublico/configuracion_inicial...";
    }
    if (boton) boton.disabled = true;
    fetch("/ecommercePublico/configuracion_inicial", {
      method: "GET",
      credentials: "same-origin",
      headers: {
        "Accept": "application/json",
        "X-Requested-With": "XMLHttpRequest"
      }
    }).then(function (response) {
      return response.text().then(function (text) {
        var json = null;
        try {
          json = JSON.parse(text);
        } catch (error) {
          throw new Error("Respuesta no JSON del servidor (" + response.status + "): " + text.substring(0, 140));
        }
        if (!response.ok) throw new Error((json && json.mensaje) || "No se pudo consultar configuracion_inicial");
        return json;
      });
    }).then(function (json) {
      var publicado = json && json.depurar ? json.depurar.cms_global : null;
      if (!publicado || publicado.fuente !== "bd_publicada") {
        mostrarApiGlobalFrontend("No hay Global publicado en BD. El frontend recibira defaults hasta usar Guardar y publicar global.", "warning");
        return;
      }
      var negocio = publicado.negocio || {};
      var mapa = publicado.mapa || {};
      var seo = publicado.seo_global || {};
      mostrarApiGlobalFrontend(
        '<div class="fw-bold mb-2">Global publicado para frontend</div>' +
        '<div><span class="fw-semibold">Nombre:</span> ' + escapeHtml(negocio.nombre_comercial || "sin nombre") + '</div>' +
        '<div><span class="fw-semibold">Logo:</span> ' + escapeHtml(resumenUrlMedia(negocio.logo_principal || "")) + '</div>' +
        '<div><span class="fw-semibold">Favicon:</span> ' + escapeHtml(resumenUrlMedia(negocio.favicon || "")) + '</div>' +
        '<div><span class="fw-semibold">Mapa embed:</span> ' + escapeHtml(mapa.embed_url ? "configurado" : "sin configurar") + '</div>' +
        '<div><span class="fw-semibold">SEO title:</span> ' + escapeHtml(seo.title_default || "sin titulo") + '</div>',
        "success",
        true
      );
    }).catch(function (error) {
      mostrarApiGlobalFrontend(error.message || "No se pudo consultar API publicada.", "danger");
    }).finally(function () {
      if (boton) boton.disabled = false;
    });
  }

  function mostrarApiGlobalFrontend(mensaje, tipo, esHtml) {
    setText("cms_actual_estado", esHtml ? "API global consultada" : mensaje);
    var node = $("cms_actual_global_api_estado");
    if (!node) return;
    node.className = "alert fs-7 py-3 mb-4 alert-light-" + (tipo || "info");
    if (esHtml) {
      node.innerHTML = mensaje;
    } else {
      node.textContent = mensaje;
    }
  }

  function actualizarWhatsappGlobal(campo, valor) {
    var data = whatsappGlobalData();
    if (!data) return;
    if (campo === "visible" || campo === "config.mostrar_en_mobile" || campo === "config.mostrar_en_desktop" || campo === "config.abrir_en_nueva_pestana" || campo === "config.mostrar_horario" || campo === "config.mostrar_estado_online") {
      setPath(data, campo, valor === "1");
    } else if (campo === "orden") {
      data.orden = parseInt(valor || "10", 10) || 10;
    } else {
      setPath(data, campo, valor);
    }
    refrescarJson();
  }

  function actualizarWhatsappContacto(index, campo, valor) {
    var data = whatsappGlobalData();
    var contactos = data && Array.isArray(data.contactos) ? data.contactos : [];
    if (!contactos[index]) return;
    if (campo === "telefono") valor = String(valor || "").replace(/\D+/g, "");
    if (campo === "orden") valor = parseInt(valor || ((index + 1) * 10), 10) || ((index + 1) * 10);
    setPath(contactos[index], campo, valor);
    refrescarJson();
  }

  function agregarWhatsappContacto() {
    var data = whatsappGlobalData();
    if (!data.contactos) data.contactos = [];
    data.contactos.push({
      id: "contacto_" + (data.contactos.length + 1),
      nombre: "Nuevo contacto",
      descripcion: "",
      telefono: "",
      mensaje: "",
      avatar: "",
      icono: "whatsapp",
      horario: "",
      orden: (data.contactos.length + 1) * 10,
      visible: true
    });
    renderGrupo();
  }

  function ejecutarWhatsappContactoAccion(accion, index) {
    var data = whatsappGlobalData();
    var contactos = data && Array.isArray(data.contactos) ? data.contactos : [];
    if (!contactos[index]) return;
    if (accion === "subir" && index > 0) {
      contactos.splice(index - 1, 0, contactos.splice(index, 1)[0]);
    }
    if (accion === "bajar" && index < contactos.length - 1) {
      contactos.splice(index + 1, 0, contactos.splice(index, 1)[0]);
    }
    if (accion === "toggle") {
      contactos[index].visible = !contactos[index].visible;
    }
    if (accion === "eliminar" && contactos.length > 1) {
      contactos.splice(index, 1);
    }
    normalizarOrden(contactos);
    renderGrupo();
  }

  function telefonoWhatsappValido(telefono) {
    return /^[1-9][0-9]{9,15}$/.test(String(telefono || "").replace(/\D+/g, ""));
  }

  function publicarGlobalWhatsapp() {
    var data = whatsappGlobalData();
    var contactos = Array.isArray(data.contactos) ? data.contactos : [];
    var visibles = contactos.filter(function (contacto) { return contacto && contacto.visible !== false; });
    var invalidos = visibles.filter(function (contacto) {
      return !String(contacto.nombre || "").trim() || !telefonoWhatsappValido(contacto.telefono);
    });
    if (data.visible && (!visibles.length || invalidos.length)) {
      setGlobalWhatsappEstado("Cada contacto visible necesita nombre y telefono internacional sin espacios.", "warning");
      return;
    }
    guardarBorradorFrontendLocal(true);
    var boton = $("cms_actual_global_whatsapp_publicar");
    var form = new FormData();
    form.append("_csrf", window.ERP_CSRF_TOKEN || "");
    form.append("payload_json", JSON.stringify(data));
    if (boton) boton.disabled = true;
    setGlobalWhatsappEstado("Publicando WhatsApp global...", "info");
    fetch("/cms/frontend_global_whatsapp_publicar_erp", {
      method: "POST",
      body: form,
      credentials: "same-origin",
      headers: {
        "X-CSRF-Token": window.ERP_CSRF_TOKEN || "",
        "Accept": "application/json",
        "X-Requested-With": "XMLHttpRequest"
      }
    }).then(function (response) {
      return response.text().then(function (text) {
        var json = null;
        try {
          json = JSON.parse(text);
        } catch (error) {
          throw new Error("Respuesta no JSON del servidor (" + response.status + "): " + text.substring(0, 140));
        }
        if (!response.ok && json && json.mensaje) throw new Error(json.mensaje);
        return json;
      });
    }).then(function (json) {
      if (!json || json.error) throw new Error(json && json.mensaje ? json.mensaje : "No se pudo publicar WhatsApp");
      setGlobalWhatsappEstado("WhatsApp publicado. El frontend puede leer global.whatsapp_chat.", "success");
      consultarApiGlobalWhatsapp();
    }).catch(function (error) {
      setGlobalWhatsappEstado(error.message || "Error al publicar WhatsApp.", "danger");
    }).finally(function () {
      if (boton) boton.disabled = false;
    });
  }

  function consultarApiGlobalWhatsapp() {
    var boton = $("cms_actual_global_whatsapp_api");
    if (boton) boton.disabled = true;
    setGlobalWhatsappApi("Consultando /ecommercePublico/contenido_pagina?pagina=global...", "info");
    fetch("/ecommercePublico/contenido_pagina?pagina=global", {
      method: "GET",
      credentials: "same-origin",
      headers: {
        "Accept": "application/json",
        "X-Requested-With": "XMLHttpRequest"
      }
    }).then(function (response) {
      return response.text().then(function (text) {
        var json = null;
        try {
          json = JSON.parse(text);
        } catch (error) {
          throw new Error("Respuesta no JSON del servidor (" + response.status + "): " + text.substring(0, 140));
        }
        if (!response.ok) throw new Error((json && json.mensaje) || "No se pudo consultar pagina global");
        return json;
      });
    }).then(function (json) {
      var depurar = json && json.depurar ? json.depurar : {};
      var bloques = bloquesSlotPublicado(depurar, "global.whatsapp_chat");
      if (!bloques.length) {
        setGlobalWhatsappApi("No hay WhatsApp publicado en global.whatsapp_chat.", "warning");
        return;
      }
      var bloque = bloques[0] || {};
      var contactos = Array.isArray(bloque.contactos) ? bloque.contactos.filter(function (item) { return item && item.visible !== false; }) : [];
      setGlobalWhatsappApi(
        '<div class="fw-bold mb-2">WhatsApp publicado</div>' +
        '<div><span class="fw-semibold">Fuente:</span> ' + escapeHtml(depurar.fuente || "sin fuente") + '</div>' +
        '<div><span class="fw-semibold">Visible:</span> ' + escapeHtml(bloque.visible ? "si" : "no") + '</div>' +
        '<div><span class="fw-semibold">Titulo:</span> ' + escapeHtml(bloque.titulo || "") + '</div>' +
        '<div><span class="fw-semibold">Contactos visibles:</span> ' + escapeHtml(contactos.length) + '</div>',
        "success",
        true
      );
    }).catch(function (error) {
      setGlobalWhatsappApi(error.message || "Error al consultar WhatsApp global.", "danger");
    }).finally(function () {
      if (boton) boton.disabled = false;
    });
  }

  function setGlobalWhatsappEstado(mensaje, tipo) {
    setText("cms_actual_estado", mensaje);
    var node = $("cms_actual_global_whatsapp_estado");
    if (!node) return;
    node.className = "alert fs-7 py-3 mb-4 alert-light-" + (tipo || "info");
    node.textContent = mensaje;
  }

  function setGlobalWhatsappApi(mensaje, tipo, esHtml) {
    setText("cms_actual_estado", esHtml ? "API WhatsApp global consultada" : mensaje);
    var node = $("cms_actual_global_whatsapp_api_estado");
    if (!node) return;
    node.className = "alert fs-7 py-3 mb-4 alert-light-" + (tipo || "info");
    if (esHtml) node.innerHTML = mensaje;
    else node.textContent = mensaje;
  }

  function publicarGlobalFrontend() {
    var json = previewGlobalJson();
    var negocio = json.depurar && json.depurar.negocio ? json.depurar.negocio : {};
    if (!String(negocio.nombre_comercial || "").trim()) {
      setGlobalEstado("Captura el nombre comercial antes de publicar.", "warning");
      return;
    }
    guardarBorradorFrontendLocal(true);
    var boton = $("cms_actual_global_publicar");
    var form = new FormData();
    form.append("_csrf", window.ERP_CSRF_TOKEN || "");
    form.append("payload_json", JSON.stringify(json.depurar || {}));
    if (boton) boton.disabled = true;
    setGlobalEstado("Publicando configuracion global...", "info");
    fetch("/cms/frontend_global_publicar_erp", {
      method: "POST",
      body: form,
      credentials: "same-origin",
      headers: {
        "X-CSRF-Token": window.ERP_CSRF_TOKEN || "",
        "Accept": "application/json",
        "X-Requested-With": "XMLHttpRequest"
      }
    }).then(function (response) {
      return response.text().then(function (text) {
        var jsonRespuesta = null;
        try {
          jsonRespuesta = JSON.parse(text);
        } catch (error) {
          throw new Error("Respuesta no JSON del servidor (" + response.status + "): " + text.substring(0, 140));
        }
        if (!response.ok && jsonRespuesta && jsonRespuesta.mensaje) {
          throw new Error(jsonRespuesta.mensaje);
        }
        return jsonRespuesta;
      });
    }).then(function (jsonRespuesta) {
      if (!jsonRespuesta || jsonRespuesta.error) {
        throw new Error(jsonRespuesta && jsonRespuesta.mensaje ? jsonRespuesta.mensaje : "No se pudo publicar global");
      }
      setGlobalEstado("Configuracion global publicada. configuracion_inicial ya puede entregarla.", "success");
      consultarApiGlobalFrontend();
    }).catch(function (error) {
      setGlobalEstado(error.message || "Error al publicar global.", "danger");
    }).finally(function () {
      if (boton) boton.disabled = false;
    });
  }

  function setGlobalEstado(mensaje, tipo) {
    setText("cms_actual_estado", mensaje);
    var node = $("cms_actual_global_estado");
    if (!node) return;
    node.className = "alert fs-7 py-3 mb-4 alert-light-" + (tipo || "info");
    node.textContent = mensaje;
  }

  function actualizarCatalogoField(campo, valor) {
    var data = catalogoData("catalogo_configuracion");
    if (!data) return;
    if (campo === "visible") data.visible = valor === "1";
    else if (campo === "orden") data.orden = parseInt(valor || "10", 10) || 10;
    else setPath(data, campo, valor);
    refrescarJson();
  }

  function guardarBorradorCatalogo() {
    if (guardarBorradorFrontendLocal(false)) {
      setCatalogoEstado("Borrador local guardado. Para enviarlo al frontend usa Guardar y publicar catalogo.", "success");
    } else {
      setCatalogoEstado("No se pudo guardar el borrador local.", "danger");
    }
  }

  function publicarCatalogoFrontend() {
    var data = catalogoData("catalogo_configuracion");
    if (!data || !String(data.titulo || "").trim()) {
      setCatalogoEstado("Captura el titulo del catalogo antes de publicar.", "warning");
      return;
    }
    var boton = $("cms_actual_catalogo_publicar");
    var form = new FormData();
    form.append("_csrf", window.ERP_CSRF_TOKEN || "");
    form.append("payload_json", JSON.stringify(data));
    if (boton) boton.disabled = true;
    setCatalogoEstado("Publicando Catalogo en la API...", "info");
    fetch("/cms/frontend_catalogo_publicar_erp", {
      method: "POST",
      body: form,
      credentials: "same-origin",
      headers: {
        "X-CSRF-Token": window.ERP_CSRF_TOKEN || "",
        "Accept": "application/json",
        "X-Requested-With": "XMLHttpRequest"
      }
    }).then(function (response) {
      return response.text().then(function (text) {
        var json = null;
        try {
          json = JSON.parse(text);
        } catch (error) {
          throw new Error("Respuesta no JSON del servidor (" + response.status + "): " + text.substring(0, 140));
        }
        if (!response.ok && json && json.mensaje) throw new Error(json.mensaje);
        return json;
      });
    }).then(function (json) {
      if (!json || json.error) throw new Error(json && json.mensaje ? json.mensaje : "No se pudo publicar Catalogo");
      setCatalogoEstado("Catalogo publicado. Verifica con Ver API publicada.", "success");
      consultarApiCatalogoFrontend();
    }).catch(function (error) {
      setCatalogoEstado(error.message || "Error al publicar Catalogo.", "danger");
    }).finally(function () {
      if (boton) boton.disabled = false;
    });
  }

  function consultarApiCatalogoFrontend() {
    var boton = $("cms_actual_catalogo_api");
    if (boton) boton.disabled = true;
    setCatalogoApi("Consultando /ecommercePublico/cms_frontend?pagina=catalogo...", "info");
    fetch("/ecommercePublico/cms_frontend?pagina=catalogo", {
      method: "GET",
      credentials: "same-origin",
      headers: {
        "Accept": "application/json",
        "X-Requested-With": "XMLHttpRequest"
      }
    }).then(function (response) {
      return response.text().then(function (text) {
        var json = null;
        try {
          json = JSON.parse(text);
        } catch (error) {
          throw new Error("Respuesta no JSON del servidor (" + response.status + "): " + text.substring(0, 140));
        }
        if (!response.ok) throw new Error((json && json.mensaje) || "No se pudo consultar la API publica");
        return json;
      });
    }).then(function (json) {
      var depurar = json && json.depurar ? json.depurar : {};
      var bloques = bloquesSlotPublicado(depurar, "catalogo.encabezado");
      if (!bloques.length) {
        setCatalogoApi("No hay encabezado publicado en catalogo.encabezado.", "warning");
        return;
      }
      var bloque = bloques[0] || {};
      setCatalogoApi(
        '<div class="fw-bold mb-2">Catalogo publicado</div>' +
        '<div><span class="fw-semibold">Fuente:</span> ' + escapeHtml(depurar.fuente || "sin fuente") + '</div>' +
        '<div><span class="fw-semibold">Titulo:</span> ' + escapeHtml(bloque.titulo || "") + '</div>' +
        '<div><span class="fw-semibold">Tipo:</span> ' + escapeHtml(bloque.tipo || "") + '</div>' +
        '<div><span class="fw-semibold">SEO:</span> ' + escapeHtml((bloque.seo || {}).title || "") + '</div>',
        "success",
        true
      );
    }).catch(function (error) {
      setCatalogoApi(error.message || "Error al consultar API publicada.", "danger");
    }).finally(function () {
      if (boton) boton.disabled = false;
    });
  }

  function setCatalogoEstado(mensaje, tipo) {
    setText("cms_actual_estado", mensaje);
    var node = $("cms_actual_catalogo_estado");
    if (!node) return;
    node.className = "alert fs-7 py-3 mb-4 alert-light-" + (tipo || "info");
    node.textContent = mensaje;
  }

  function setCatalogoApi(mensaje, tipo, esHtml) {
    setText("cms_actual_estado", esHtml ? "API catalogo consultada" : mensaje);
    var node = $("cms_actual_catalogo_api_estado");
    if (!node) return;
    node.className = "alert fs-7 py-3 mb-0 alert-light-" + (tipo || "info");
    if (esHtml) node.innerHTML = mensaje;
    else node.textContent = mensaje;
  }

  function publicarCategoriasFrontend() {
    var json = previewCategoriasCmsJson();
    var categorias = json.depurar && Array.isArray(json.depurar.categorias) ? json.depurar.categorias : [];
    var visibles = categorias.filter(function (item) { return item && item.visible !== false; });
    if (!visibles.length) {
      setCategoriasEstado("Deja al menos una categoria visible antes de publicar.", "warning");
      return;
    }
    var invalida = visibles.filter(function (item) { return !item.categoria_id && !String(item.slug || "").trim(); })[0];
    if (invalida) {
      setCategoriasEstado("Cada categoria visible necesita ID ERP o slug.", "warning");
      return;
    }
    var boton = $("cms_actual_categorias_publicar");
    var form = new FormData();
    form.append("_csrf", window.ERP_CSRF_TOKEN || "");
    form.append("payload_json", JSON.stringify(json.depurar || {}));
    if (boton) boton.disabled = true;
    setCategoriasEstado("Publicando categorias...", "info");
    fetch("/cms/frontend_categorias_publicar_erp", {
      method: "POST",
      body: form,
      credentials: "same-origin",
      headers: {
        "X-CSRF-Token": window.ERP_CSRF_TOKEN || "",
        "Accept": "application/json",
        "X-Requested-With": "XMLHttpRequest"
      }
    }).then(function (response) {
      return response.text().then(function (text) {
        var jsonRespuesta = null;
        try {
          jsonRespuesta = JSON.parse(text);
        } catch (error) {
          throw new Error("Respuesta no JSON del servidor (" + response.status + "): " + text.substring(0, 140));
        }
        if (!response.ok && jsonRespuesta && jsonRespuesta.mensaje) {
          throw new Error(jsonRespuesta.mensaje);
        }
        return jsonRespuesta;
      });
    }).then(function (jsonRespuesta) {
      if (!jsonRespuesta || jsonRespuesta.error) {
        throw new Error(jsonRespuesta && jsonRespuesta.mensaje ? jsonRespuesta.mensaje : "No se pudo publicar categorias");
      }
      setCategoriasEstado("Categorias publicadas. /ecommercePublico/categorias ya puede entregar el enriquecimiento CMS.", "success");
    }).catch(function (error) {
      setCategoriasEstado(error.message || "Error al publicar categorias.", "danger");
    }).finally(function () {
      if (boton) boton.disabled = false;
    });
  }

  function setCategoriasEstado(mensaje, tipo) {
    setText("cms_actual_estado", mensaje);
    var node = $("cms_actual_categorias_estado");
    if (!node) return;
    node.className = "alert fs-7 py-3 mb-4 alert-light-" + (tipo || "info");
    node.textContent = mensaje;
  }

  function publicarMarcasFrontend() {
    var json = previewMarcasCmsJson();
    var marcas = json.depurar && Array.isArray(json.depurar.marcas) ? json.depurar.marcas : [];
    var visibles = marcas.filter(function (item) { return item && item.visible !== false; });
    if (!visibles.length) {
      setMarcasEstado("Deja al menos una marca visible antes de publicar.", "warning");
      return;
    }
    var invalida = visibles.filter(function (item) { return !item.marca_id && !String(item.slug || "").trim(); })[0];
    if (invalida) {
      setMarcasEstado("Cada marca visible necesita ID ERP o slug.", "warning");
      return;
    }
    var boton = $("cms_actual_marcas_publicar");
    var form = new FormData();
    form.append("_csrf", window.ERP_CSRF_TOKEN || "");
    form.append("payload_json", JSON.stringify(json.depurar || {}));
    if (boton) boton.disabled = true;
    setMarcasEstado("Publicando marcas...", "info");
    fetch("/cms/frontend_marcas_publicar_erp", {
      method: "POST",
      body: form,
      credentials: "same-origin",
      headers: {
        "X-CSRF-Token": window.ERP_CSRF_TOKEN || "",
        "Accept": "application/json",
        "X-Requested-With": "XMLHttpRequest"
      }
    }).then(function (response) {
      return response.text().then(function (text) {
        var jsonRespuesta = null;
        try {
          jsonRespuesta = JSON.parse(text);
        } catch (error) {
          throw new Error("Respuesta no JSON del servidor (" + response.status + "): " + text.substring(0, 140));
        }
        if (!response.ok && jsonRespuesta && jsonRespuesta.mensaje) {
          throw new Error(jsonRespuesta.mensaje);
        }
        return jsonRespuesta;
      });
    }).then(function (jsonRespuesta) {
      if (!jsonRespuesta || jsonRespuesta.error) {
        throw new Error(jsonRespuesta && jsonRespuesta.mensaje ? jsonRespuesta.mensaje : "No se pudo publicar marcas");
      }
      setMarcasEstado("Marcas publicadas. /ecommercePublico/marcas ya puede entregar logos, banners y SEO desde CMS.", "success");
    }).catch(function (error) {
      setMarcasEstado(error.message || "Error al publicar marcas.", "danger");
    }).finally(function () {
      if (boton) boton.disabled = false;
    });
  }

  function setMarcasEstado(mensaje, tipo) {
    setText("cms_actual_estado", mensaje);
    var node = $("cms_actual_marcas_estado");
    if (!node) return;
    node.className = "alert fs-7 py-3 mb-4 alert-light-" + (tipo || "info");
    node.textContent = mensaje;
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
    if (grupo.codigo === "catalogo") {
      return previewCatalogoCmsJson();
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
        orden_componentes: homeComponentesOrdenables().map(function (item) {
          return {
            codigo: item.key,
            slot: item.slot,
            visible: item.data.visible !== false,
            orden: parseInt(item.data.orden || "0", 10) || 0
          };
        }),
        secciones_ordenadas: homeComponentesOrdenables().filter(function (item) {
          return item.data.visible !== false;
        }).map(function (item) {
          return {
            codigo: item.key,
            slot: item.slot,
            tipo: item.data.tipo || "",
            layout: item.data.layout || (item.data.config ? item.data.config.variante : ""),
            orden: parseInt(item.data.orden || "0", 10) || 0
          };
        }),
        secciones: grupo.secciones.filter(function (item) {
          return item.codigo !== "home_orden_componentes";
        }).map(function (item, index) {
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
        whatsapp_chat: whatsappGlobalData(),
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

  function previewCatalogoCmsJson() {
    var data = catalogoData("catalogo_configuracion") || {};
    return {
      tipo: "success",
      mensaje: "CMS frontend catalogo consultado",
      depurar: {
        version: "cms_frontend_catalogo_2026_08_28",
        pagina: "catalogo",
        endpoint_destino: "/ecommercePublico/cms_frontend?pagina=catalogo",
        actualizado_en: "pendiente",
        slot: "catalogo.encabezado",
        secciones: [data],
        guardrails: {
          no_modifica_catalogo: true,
          no_modifica_productos: true,
          no_modifica_precios: true,
          no_modifica_inventario: true,
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
            path_slug: item.path_slug || item.slug,
            titulo: item.titulo,
            subtitulo: item.subtitulo,
            descripcion_seo: item.descripcion_seo,
            imagen_card: item.imagen_card,
            imagen_banner: item.imagen_banner,
            alt_card: item.alt_card,
            alt_banner: item.alt_banner,
            heredar_banner: item.heredar_banner !== false,
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
          if (item.codigo === "home_promo") return promoData();
          if (item.codigo === "home_promos_categoria") return promosCategoriaData();
          if (item.codigo === "home_categorias_destacadas") return categoriasData();
          if (item.codigo === "home_productos_destacados") return productosData();
          if (item.codigo === "home_marcas_destacadas") return marcasHomeData();
          if (item.codigo === "home_colecciones") return coleccionesData();
          if (item.codigo === "home_esenciales_artiani") return esencialesData();
          if (item.codigo === "home_compra_guiada") return compraGuiadaData();
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

  function promoData() {
    return estado.datos.home.home_promo;
  }

  function categoriasData() {
    return estado.datos.home.home_categorias_destacadas;
  }

  function promosCategoriaData() {
    return estado.datos.home.home_promos_categoria;
  }

  function productosData() {
    return estado.datos.home.home_productos_destacados;
  }

  function marcasHomeData() {
    return estado.datos.home.home_marcas_destacadas;
  }

  function coleccionesData() {
    return estado.datos.home.home_colecciones;
  }

  function esencialesData() {
    return estado.datos.home.home_esenciales_artiani;
  }

  function compraGuiadaData() {
    return estado.datos.home.home_compra_guiada;
  }

  function bannerData() {
    return estado.datos.home.home_banner;
  }

  function globalData(codigo) {
    if (!estado.datos.global) estado.datos.global = {};
    if (codigo === "global_whatsapp_chat" && !estado.datos.global.global_whatsapp_chat) {
      estado.datos.global.global_whatsapp_chat = whatsappGlobalDefault();
    }
    return estado.datos.global ? estado.datos.global[codigo] : null;
  }

  function whatsappGlobalData() {
    return globalData("global_whatsapp_chat");
  }

  function whatsappGlobalDefault() {
    return {
      codigo: "global_whatsapp_chat",
      slot: "global.whatsapp_chat",
      tipo: "whatsapp_chat",
      layout: "floating_multi_contact",
      visible: true,
      orden: 70,
      titulo: "Necesitas ayuda?",
      subtitulo: "Elige un asesor y escribenos por WhatsApp.",
      boton: { label: "WhatsApp", icono: "whatsapp" },
      mensaje_default: "Hola, vi el catalogo de Artiani y quiero mas informacion.",
      config: {
        posicion: "bottom_right",
        mostrar_en_mobile: true,
        mostrar_en_desktop: true,
        abrir_en_nueva_pestana: true,
        mostrar_horario: true,
        mostrar_estado_online: false
      },
      contactos: [
        {
          id: "ventas",
          nombre: "Ventas Artiani",
          descripcion: "Productos, precios y pedidos",
          telefono: "",
          mensaje: "Hola, quiero informacion sobre productos de Artiani.",
          avatar: "",
          icono: "whatsapp",
          horario: "Lunes a sabado de 10:00 a 19:00",
          orden: 10,
          visible: true
        }
      ]
    };
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

  function catalogoData(codigo) {
    return estado.datos.catalogo ? estado.datos.catalogo[codigo] : null;
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
    aplicarDefaultsMediaPicker();
    renderMediaPicker();
    cargarMediaServidorPicker();
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
        '<div class="modal-header"><div><h3 class="modal-title fw-bold">Seleccionar imagen de Media</h3><div class="text-muted fs-7">Biblioteca Media CMS.</div></div><button type="button" class="btn btn-icon btn-sm btn-light" data-bs-dismiss="modal"><i class="bi bi-x-lg"></i></button></div>' +
        '<div class="modal-body">' +
          '<div class="alert alert-info py-3 fs-7">Seleccionar un archivo solo muestra preview. Para guardarlo en servidor pulsa <strong>Subir y usar</strong>; despues quedara en Media CMS y podra salir en la API publica.</div>' +
          '<div class="alert alert-light-primary py-3 fs-7" id="cms_actual_media_recomendacion"></div>' +
          '<div class="border rounded p-4 mb-5 bg-light">' +
            '<div class="fw-bold mb-3">Cargar nueva imagen</div>' +
            '<div class="row g-3 align-items-end">' +
              '<div class="col-md-4"><label class="form-label fs-8 fw-bold">Archivo</label><input class="form-control form-control-sm" id="cms_actual_media_archivo" type="file" accept="image/jpeg,image/png,image/webp,image/vnd.microsoft.icon,image/x-icon,.ico"></div>' +
              '<div class="col-md-2"><label class="form-label fs-8 fw-bold">Uso</label><select class="form-select form-select-sm" id="cms_actual_media_nuevo_uso"><option value="home">Home</option><option value="categoria">Categoria</option><option value="producto">Producto</option><option value="global">Global</option><option value="blog">Blog futuro</option></select></div>' +
              '<div class="col-md-2"><label class="form-label fs-8 fw-bold">Tipo</label><select class="form-select form-select-sm" id="cms_actual_media_nuevo_tipo"><option value="logo">Logo principal</option><option value="logo_blanco">Logo blanco</option><option value="favicon">Favicon</option><option value="open_graph">Imagen social SEO</option><option value="banner">Banner</option><option value="hero">Hero</option><option value="card">Card</option><option value="thumb">Thumbnail</option><option value="editorial">Editorial</option></select></div>' +
              '<div class="col-md-3"><label class="form-label fs-8 fw-bold">Alt text</label><input class="form-control form-control-sm" id="cms_actual_media_nuevo_alt" type="text"></div>' +
              '<div class="col-md-2"><button class="btn btn-sm btn-primary w-100" type="button" id="cms_actual_media_agregar_usar"><i class="bi bi-cloud-upload"></i> Subir y usar</button></div>' +
            '</div>' +
            '<div class="mt-3" id="cms_actual_media_preview_nuevo"></div>' +
          '</div>' +
          '<div class="row g-4">' +
            '<div class="col-lg-8">' +
              '<div class="d-flex justify-content-between align-items-end flex-wrap gap-3 mb-4"><div><div class="fw-bold">Galeria disponible</div><div class="text-muted fs-8">Previsualiza y elige una imagen.</div></div><div class="d-flex gap-2"><button class="btn btn-sm btn-light-warning" type="button" id="cms_actual_media_limpiar_temporales"><i class="bi bi-eraser"></i> Limpiar temporales</button><input class="form-control form-control-sm w-200px" id="cms_actual_media_buscar" type="text" placeholder="Filtro opcional"><select class="form-select form-select-sm w-150px" id="cms_actual_media_uso"><option value="">Todos</option><option value="home">Home</option><option value="categoria">Categoria</option><option value="producto">Producto</option><option value="global">Global</option><option value="blog">Blog futuro</option></select></div></div>' +
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
    on("cms_actual_media_nuevo_uso", "change", renderMediaRecomendacion);
    on("cms_actual_media_nuevo_tipo", "change", renderMediaRecomendacion);
    on("cms_actual_media_usar_seleccion", "click", function () {
      if (estado.mediaPicker && estado.mediaPicker.seleccion) aplicarMediaSeleccionada(estado.mediaPicker.seleccion);
    });
    on("cms_actual_media_buscar", "input", renderMediaPicker);
    on("cms_actual_media_uso", "change", renderMediaPicker);
    on("cms_actual_media_limpiar_temporales", "click", limpiarMediaTemporalesPicker);
    var lista = $("cms_actual_media_lista");
    if (lista) {
      lista.addEventListener("click", function (event) {
        var button = event.target.closest("[data-media-select]");
        if (!button) return;
        seleccionarMediaPreview(button.getAttribute("data-media-select"));
      });
    }
  }

  function aplicarDefaultsMediaPicker() {
    var picker = estado.mediaPicker || {};
    var uso = picker.contexto === "global" ? "global" : "";
    var tipo = "";
    if (String(picker.contexto || "").indexOf("home_") === 0) uso = "home";
    if (picker.campo === "logo_principal") tipo = "logo";
    if (picker.campo === "logo_blanco") tipo = "logo_blanco";
    if (picker.campo === "favicon") tipo = "favicon";
    if (picker.campo === "og_image_default") tipo = "open_graph";
    if (picker.campo === "logo") tipo = "logo";
    if (picker.campo === "imagen") tipo = "card";
    if (picker.contexto === "home_esenciales" || picker.contexto === "home_esencial_principal") tipo = "editorial";
    if (!tipo && picker.campo && picker.campo.indexOf("imagen_desktop") !== -1) tipo = "hero";
    if (!tipo && picker.campo && picker.campo.indexOf("imagen_mobile") !== -1) tipo = "hero";
    if (!tipo && picker.campo && picker.campo.indexOf("imagen_banner") !== -1) tipo = "banner";
    if (!tipo && picker.campo && picker.campo.indexOf("imagen_card") !== -1) tipo = "card";
    if (uso && $("cms_actual_media_nuevo_uso")) $("cms_actual_media_nuevo_uso").value = uso;
    if (uso && $("cms_actual_media_uso")) $("cms_actual_media_uso").value = uso;
    if (tipo && $("cms_actual_media_nuevo_tipo")) $("cms_actual_media_nuevo_tipo").value = tipo;
    renderMediaRecomendacion();
  }

  function renderMediaRecomendacion() {
    var node = $("cms_actual_media_recomendacion");
    if (!node) return;
    node.innerHTML = '<span class="fw-bold">Proporcion recomendada:</span> ' + escapeHtml(recomendacionMediaActual());
  }

  function recomendacionMediaActual() {
    var uso = valor("cms_actual_media_nuevo_uso") || "";
    var tipo = valor("cms_actual_media_nuevo_tipo") || "";
    var contexto = estado.mediaPicker ? estado.mediaPicker.contexto : "";
    if (tipo === "favicon") return "ICO/PNG cuadrado 64 x 64 o 128 x 128.";
    if (tipo === "logo" || tipo === "logo_blanco") return "Logo horizontal en PNG/WebP transparente, aprox. 600 x 200; conserva margen.";
    if (tipo === "open_graph") return "1200 x 630 en JPG/WebP para SEO y redes sociales.";
    if (tipo === "hero") return "1920 x 700 o 1600 x 600 en JPG/WebP; deja texto importante centrado.";
    if (tipo === "banner") return "1600 x 600 para banner ancho; si es banner de categoria usa tambien una version mobile despues.";
    if (contexto === "home_esenciales" || contexto === "home_esencial_principal" || tipo === "editorial") return "1200 x 900 o 1600 x 1200 en JPG/WebP; ideal para cards editoriales de Home.";
    if (uso === "categoria" || tipo === "card") return "Categoria/card: ideal 1200 x 1200 cuadrada. Tu formato 900 x 900 sirve; mejor subir 1200 x 1200 optimizada.";
    if (tipo === "thumb") return "600 x 600 cuadrada.";
    return "JPG/WebP optimizada, menos de 500 KB si se puede, con alt text descriptivo.";
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
    var boton = $("cms_actual_media_agregar_usar");
    var data = new FormData();
    data.append("_csrf", window.ERP_CSRF_TOKEN || "");
    data.append("archivo", file);
    data.append("alt", alt);
    data.append("uso", valor("cms_actual_media_nuevo_uso") || "home");
    data.append("tipo", valor("cms_actual_media_nuevo_tipo") || "banner");
    if (boton) boton.disabled = true;
    setText("cms_actual_media_preview_nuevo", "Subiendo imagen a Media CMS...");
    fetch("/cms/media_admin_subir_erp", {
      method: "POST",
      body: data,
      credentials: "same-origin",
      headers: {
        "X-CSRF-Token": window.ERP_CSRF_TOKEN || "",
        "Accept": "application/json",
        "X-Requested-With": "XMLHttpRequest"
      }
    }).then(function (response) {
      return response.text().then(function (text) {
        var json = null;
        try {
          json = JSON.parse(text);
        } catch (error) {
          throw new Error("Respuesta no JSON del servidor (" + response.status + "): " + text.substring(0, 140));
        }
        if (!response.ok && json && json.mensaje) {
          throw new Error(json.mensaje);
        }
        return json;
      });
    }).then(function (json) {
      if (!json || json.error) {
        throw new Error(json && json.mensaje ? json.mensaje : "No se pudo subir la imagen");
      }
      var item = normalizarMediaServidor(json.depurar || {});
      if (!item || !item.id) {
        throw new Error("El servidor no devolvio la imagen guardada");
      }
      guardarMediaLocalItems(mezclarMediaItems(mediaLocalItems(), [item]));
      estado.mediaPicker.archivo = null;
      estado.mediaPicker.dataUrl = "";
      if ($("cms_actual_media_archivo")) $("cms_actual_media_archivo").value = "";
      setText("cms_actual_media_preview_nuevo", "");
      aplicarMediaSeleccionada(item.id);
    }).catch(function (error) {
      setText("cms_actual_media_preview_nuevo", error.message || "No se pudo subir la imagen.");
    }).finally(function () {
      if (boton) boton.disabled = false;
    });
  }

  function cargarMediaServidorPicker() {
    if (!window.fetch) return;
    fetch("/cms/media_admin_listar_erp?limite=80", { credentials: "same-origin" })
      .then(function (response) { return response.json(); })
      .then(function (json) {
        var data = json && json.depurar ? json.depurar : {};
        if (!Array.isArray(data.items)) return;
        guardarMediaLocalItems(reconciliarMediaServidor(mediaLocalItems(), data.items.map(normalizarMediaServidor).filter(Boolean)));
        renderMediaPicker();
      })
      .catch(function () {
        // La galeria local queda como fallback visual si el listado protegido no responde.
      });
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
      node.innerHTML = '<div class="col-12"><div class="text-muted">Sin imagenes disponibles. Sube una imagen o revisa /cms/media.</div></div>';
      renderMediaPickerPreview(null);
      return;
    }
    if (!estado.mediaPicker.seleccion || !items.some(function (item) { return item.id === estado.mediaPicker.seleccion; })) {
      estado.mediaPicker.seleccion = items[0].id;
    }
    node.innerHTML = items.map(function (item) {
      var esServidor = esMediaServidor(item);
      return '<div class="col-md-4 col-xl-3">' +
        '<div class="border rounded overflow-hidden h-100 bg-white ' + (item.id === estado.mediaPicker.seleccion ? 'border-primary' : '') + '">' +
          '<img src="' + escapeAttr(item.url) + '" alt="' + escapeAttr(item.alt) + '" style="width:100%;aspect-ratio:16/10;object-fit:cover;background:#f3f6f9;">' +
          '<div class="p-3">' +
            '<div class="fw-bold text-truncate">' + escapeHtml(item.nombre) + '</div>' +
            '<div class="text-muted fs-8 text-truncate mb-3">' + escapeHtml(item.alt) + '</div>' +
            '<div class="d-flex justify-content-between align-items-center gap-2 mb-3"><span class="badge ' + (esServidor ? 'badge-light-success' : 'badge-light-warning') + '">' + (esServidor ? 'Servidor BD' : 'Temporal local') + '</span><span class="text-muted fs-8">' + escapeHtml(labelUsoMedia(item.uso)) + ' / ' + escapeHtml(labelTipoMedia(item.tipo)) + '</span></div>' +
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
    var esServidor = esMediaServidor(item);
    node.innerHTML = '<div class="fw-bold mb-3">Preview seleccionado</div>' +
      '<img src="' + escapeAttr(item.url) + '" alt="' + escapeAttr(item.alt) + '" style="width:100%;aspect-ratio:16/11;object-fit:cover;border-radius:8px;border:1px solid #e7e9ef;background:#f3f6f9;">' +
      '<div class="fw-semibold mt-3 text-break">' + escapeHtml(item.nombre) + '</div>' +
      '<div class="text-muted fs-7 mt-1">' + escapeHtml(item.alt) + '</div>' +
      '<div class="d-flex flex-wrap gap-2 mt-3"><span class="badge ' + (esServidor ? 'badge-light-success' : 'badge-light-warning') + '">' + (esServidor ? 'Servidor BD' : 'Temporal local') + '</span><span class="badge badge-light-primary">' + escapeHtml(labelUsoMedia(item.uso)) + '</span><span class="badge badge-light-info">' + escapeHtml(labelTipoMedia(item.tipo)) + '</span><span class="badge badge-light">' + escapeHtml(formatoBytes(item.bytes)) + '</span></div>' +
      (esServidor
        ? '<button class="btn btn-primary w-100 mt-4" type="button" id="cms_actual_media_usar_seleccion"><i class="bi bi-check2-circle"></i> Usar imagen seleccionada</button>'
        : '<div class="alert alert-light-warning fs-7 mt-4 mb-0">Esta imagen solo vive en este navegador. Para usarla en el banner primero subela con <strong>Subir y usar</strong>.</div>');
    on("cms_actual_media_usar_seleccion", "click", function () {
      aplicarMediaSeleccionada(item.id);
    });
  }

  function aplicarMediaSeleccionada(id) {
    var media = mediaLocalItems().filter(function (item) { return item.id === id; })[0];
    if (!media) return;
    if (!esMediaServidor(media)) {
      var preview = $("cms_actual_media_preview_seleccion");
      if (preview) {
        preview.insertAdjacentHTML("beforeend", '<div class="alert alert-light-warning fs-7 mt-4 mb-0">No se aplico: esta imagen es temporal local y no existe para la API.</div>');
      }
      setText("cms_actual_estado", "Imagen temporal local no aplicada");
      return;
    }
    var picker = estado.mediaPicker || {};
    var target = null;
    if (picker.contexto === "hero") target = heroData().items[picker.index];
    if (picker.contexto === "categoria") target = categoriasData().items[picker.index];
    if (picker.contexto === "banner") target = bannerData().items[picker.index];
    if (picker.contexto === "global") target = globalData(picker.index);
    if (picker.contexto === "home_promos_categoria") target = promosCategoriaData().items[picker.index];
    if (picker.contexto === "home_marcas") target = marcasHomeData().items[picker.index];
    if (picker.contexto === "home_esenciales") target = esencialesData().items[picker.index];
    if (picker.contexto === "home_esencial_principal") target = esencialesData().categoria_principal;
    if (picker.contexto === "cms_categoria") target = categoriasCmsData("categorias_items").items[picker.index];
    if (picker.contexto === "cms_marca") target = marcasCmsData("marcas_items").items[picker.index];
    if (picker.contexto === "cms_pagina") target = paginasCmsData("paginas_items").items[picker.index];
    if (picker.contexto === "global_whatsapp_contacto") target = whatsappGlobalData().contactos[picker.index];
    if (!target) return;
    setPath(target, picker.campo, normalizarUrlMediaCms(media.url));
    if (picker.contexto === "cms_categoria" && picker.campo === "imagen_card" && !target.alt_card && media.alt) target.alt_card = media.alt;
    if (picker.contexto === "cms_categoria" && picker.campo === "imagen_banner" && !target.alt_banner && media.alt) target.alt_banner = media.alt;
    if (picker.contexto === "cms_marca" && picker.campo === "logo" && !target.alt_logo && media.alt) target.alt_logo = media.alt;
    if (picker.contexto === "cms_marca" && picker.campo === "imagen_banner" && !target.alt_banner && media.alt) target.alt_banner = media.alt;
    if (picker.contexto === "cms_pagina" && picker.campo === "imagen_principal" && !target.alt_imagen && media.alt) target.alt_imagen = media.alt;
    if (picker.contexto === "home_promos_categoria" && picker.campo === "imagen" && !target.alt && media.alt) target.alt = media.alt;
    if (picker.contexto === "home_esenciales" && picker.campo === "imagen" && !target.alt && media.alt) target.alt = media.alt;
    if (picker.contexto === "home_esencial_principal" && picker.campo === "imagen" && !target.alt && media.alt) target.alt = media.alt;
    if (picker.contexto === "home_marcas" && picker.campo === "logo" && !target.alt_logo && media.alt) target.alt_logo = media.alt;
    if (picker.contexto === "global_whatsapp_contacto" && picker.campo === "avatar" && !target.descripcion && media.alt) target.descripcion = media.alt;
    if (!target.alt && media.alt) target.alt = media.alt;
    renderGrupo();
    setText("cms_actual_estado", "Media aplicada: " + normalizarUrlMediaCms(media.url));
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

  function normalizarMediaServidor(item) {
    if (!item || !item.url) return null;
    var mediaId = item.id_media_archivo || item.media_id || "";
    return {
      id: mediaId ? "bd_" + mediaId : (item.codigo || item.url),
      media_id: mediaId,
      codigo: item.codigo || "",
      nombre: item.nombre_original || item.nombre || item.nombre_archivo || "Imagen CMS",
      mime: item.mime || "",
      bytes: Number(item.bytes || 0),
      url: item.url,
      alt: item.alt || item.alt_text || "",
      uso: item.uso || item.uso_sugerido || "general",
      tipo: item.tipo || item.tipo_sugerido || "editorial",
      estatus: item.estatus || "activo",
      creado_en: item.creado_en || item.fecha_registro || "",
      origen: "bd"
    };
  }

  function esMediaServidor(item) {
    return !!(item && item.origen === "bd" && esUrlMediaCms(item.url));
  }

  function labelUsoMedia(uso) {
    var labels = {
      home: "Home",
      categoria: "Categoria",
      producto: "Producto",
      global: "Global",
      blog: "Blog futuro",
      general: "General"
    };
    return labels[String(uso || "")] || uso || "General";
  }

  function labelTipoMedia(tipo) {
    var labels = {
      logo: "Logo principal",
      logo_blanco: "Logo blanco",
      favicon: "Favicon",
      open_graph: "Imagen social SEO",
      banner: "Banner",
      hero: "Hero",
      card: "Card",
      thumb: "Thumbnail",
      editorial: "Editorial"
    };
    return labels[String(tipo || "")] || tipo || "Editorial";
  }

  function mezclarMediaItems(actuales, nuevos) {
    var salida = (actuales || []).slice();
    (nuevos || []).forEach(function (item) {
      if (!item || !item.id) return;
      var index = salida.findIndex(function (actual) {
        return actual.id === item.id || (actual.codigo && item.codigo && actual.codigo === item.codigo);
      });
      if (index >= 0) {
        salida[index] = item;
      } else {
        salida.unshift(item);
      }
    });
    return salida;
  }

  function reconciliarMediaServidor(actuales, nuevos) {
    var idsServidor = {};
    (nuevos || []).forEach(function (item) {
      if (item && item.id) idsServidor[item.id] = true;
    });
    var salida = (actuales || []).filter(function (item) {
      return !(item && item.origen === "bd" && !idsServidor[item.id]);
    });
    return mezclarMediaItems(salida, nuevos || []);
  }

  function limpiarMediaTemporalesPicker() {
    if (!window.confirm("Quitar las imagenes temporales locales de esta galeria? No afecta archivos del servidor.")) return;
    guardarMediaLocalItems(mediaLocalItems().filter(function (item) { return item && item.origen === "bd"; }));
    if (estado.mediaPicker) estado.mediaPicker.seleccion = "";
    renderMediaPicker();
    setText("cms_actual_estado", "Temporales limpiados");
  }

  function validarMediaFile(file) {
    var nombre = String(file.name || "").toLowerCase();
    var esIco = /\.ico$/.test(nombre);
    if (MEDIA_MIMES.indexOf(file.type) === -1 && !esIco) return "Tipo no permitido. Usa JPG, PNG, WebP o ICO.";
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
    guardarBorradorFrontendLocal(true);
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

  function normalizarUrlMediaCms(url) {
    url = String(url || "").trim();
    if (!url) return "";
    var match = url.match(/\/assets\/media\/cms\/ecommerce\/[^?#\s"']+/);
    return match ? match[0] : url;
  }

  function esUrlMediaCms(url) {
    return normalizarUrlMediaCms(url).indexOf("/assets/media/cms/ecommerce/") === 0;
  }

  function esUrlImagenPublicaPersistente(url) {
    url = String(url || "").trim();
    if (!url || /^data:image\//i.test(url) || /^blob:/i.test(url) || /^file:/i.test(url)) return false;
    if (/^[a-zA-Z]:[\\/]/.test(url) || url.indexOf("../") !== -1) return false;
    if (/^\/(app|storage|tmp)\//i.test(url)) return false;
    return /^(\/|https?:\/\/)/i.test(url);
  }

  function resumenImagenesItemsApi(items, contexto) {
    var lista = (items || []).slice(0, 3);
    if (!lista.length) return "";
    return '<div class="mt-2">' + lista.map(function (item, index) {
      var imagen = item.imagen || item.imagen_card || item.imagen_banner || item.logo || item.imagen_desktop || "";
      var mobile = item.imagen_mobile || "";
      var alt = item.alt || item.alt_card || item.alt_logo || "";
      return '<div class="text-break"><span class="fw-semibold">' + escapeHtml(contexto === "hero" ? "Slide " : "Item ") + escapeHtml(index + 1) + ':</span> ' + escapeHtml(resumenUrlMedia(imagen)) + (mobile ? ' / mobile: ' + escapeHtml(resumenUrlMedia(mobile)) : '') + (alt ? ' / alt: ' + escapeHtml(alt) : '') + '</div>';
    }).join("") + '</div>';
  }

  function resumenUrlMedia(url) {
    url = String(url || "").trim();
    if (!url) return "sin imagen";
    if (url.indexOf("data:image/") === 0) return "archivo temporal sin subir";
    var normalizada = normalizarUrlMediaCms(url);
    var partes = normalizada.split("/");
    var nombre = partes[partes.length - 1] || normalizada;
    return nombre.length > 54 ? nombre.substring(0, 51) + "..." : nombre;
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
