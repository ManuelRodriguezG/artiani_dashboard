<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-08-01.
 * Proposito: entregar fixtures JSON para iniciar o probar UI del frontend ecommerce.
 * Impacto: permite construir UI, bootstrap, navegacion, catalogo, ficha y carrito sin inventar contratos ni leer BD.
 * Contrato: read-only; no consulta BD, no ejecuta DDL, no registra cotizaciones y no toca inventario.
 */

$api = array(
  "nombre" => "ERP Ecommerce Publico",
  "version" => "fase1-2026-07-12",
  "modo" => "catalogo_vivo_readonly",
  "fuente_verdad" => "ERP",
  "moneda_default" => "MXN"
);

$items = array(
  array(
    "id_publicacion" => 1001,
    "id_producto_erp" => 501,
    "id_sku" => 9001,
    "slug" => "croqueta-perro-adulto-pollo-2kg",
    "sku" => "DEMO-PERRO-2KG",
    "nombre" => "Croqueta perro adulto pollo 2 kg",
    "marca" => "Marca Demo",
    "categoria" => "Perros / Alimento seco",
    "presentacion" => "Bolsa 2 kg",
    "descripcion" => "Alimento completo para perro adulto. Fixture para frontend; no es producto real.",
    "imagen" => "/assets/media/ecommerce/demo/croqueta-perro-2kg.jpg",
    "precio" => 289.00,
    "moneda" => "MXN",
    "disponibilidad" => "disponible",
    "mascota_especie" => "perro",
    "necesidades" => array("alimento"),
    "permite_cotizacion" => true,
    "permite_whatsapp" => true
  ),
  array(
    "id_publicacion" => 1002,
    "id_producto_erp" => 502,
    "id_sku" => 9002,
    "slug" => "arena-gato-aglutinante-10kg",
    "sku" => "DEMO-GATO-ARENA",
    "nombre" => "Arena aglutinante para gato 10 kg",
    "marca" => "Hogar Felino",
    "categoria" => "Gatos / Higiene",
    "presentacion" => "Bolsa 10 kg",
    "descripcion" => "Arena aglutinante para control de olor. Fixture para frontend; no es producto real.",
    "imagen" => "/assets/media/ecommerce/demo/arena-gato-10kg.jpg",
    "precio" => 215.50,
    "moneda" => "MXN",
    "disponibilidad" => "pocas_piezas",
    "mascota_especie" => "gato",
    "necesidades" => array("higiene"),
    "permite_cotizacion" => true,
    "permite_whatsapp" => true
  ),
  array(
    "id_publicacion" => 1003,
    "id_producto_erp" => 503,
    "id_sku" => 9003,
    "slug" => "premio-dental-perro-mediano",
    "sku" => "DEMO-PREMIO-DENTAL",
    "nombre" => "Premio dental perro mediano",
    "marca" => "Sonrisa Canina",
    "categoria" => "Perros / Premios",
    "presentacion" => "Paquete 7 piezas",
    "descripcion" => "Premio dental para rutina de higiene. Fixture para frontend; no es producto real.",
    "imagen" => "/assets/media/ecommerce/demo/premio-dental-perro.jpg",
    "precio" => 98.00,
    "moneda" => "MXN",
    "disponibilidad" => "consultar_disponibilidad",
    "mascota_especie" => "perro",
    "necesidades" => array("premio", "salud"),
    "permite_cotizacion" => true,
    "permite_whatsapp" => true
  )
);

$filtrosDepurar = array(
  "mascotas" => array(
    array("valor" => "perro", "etiqueta" => "perro", "total" => 2),
    array("valor" => "gato", "etiqueta" => "gato", "total" => 1)
  ),
  "necesidades" => array(
    array("valor" => "alimento", "etiqueta" => "alimento", "total" => 1),
    array("valor" => "higiene", "etiqueta" => "higiene", "total" => 1),
    array("valor" => "premio", "etiqueta" => "premio", "total" => 1),
    array("valor" => "salud", "etiqueta" => "salud", "total" => 1)
  ),
  "marcas" => array(
    array("id" => 1, "etiqueta" => "Marca Demo", "total" => 1),
    array("id" => 2, "etiqueta" => "Hogar Felino", "total" => 1),
    array("id" => 3, "etiqueta" => "Sonrisa Canina", "total" => 1)
  ),
  "categorias" => array(
    array("id" => 10, "etiqueta" => "Perros / Alimento seco", "total" => 1),
    array("id" => 11, "etiqueta" => "Gatos / Higiene", "total" => 1),
    array("id" => 12, "etiqueta" => "Perros / Premios", "total" => 1)
  ),
  "disponibilidad" => array(
    array("valor" => "disponible", "etiqueta" => "Disponible", "total" => 1),
    array("valor" => "pocas_piezas", "etiqueta" => "Pocas piezas", "total" => 1),
    array("valor" => "consultar_disponibilidad", "etiqueta" => "Consultar disponibilidad", "total" => 1)
  )
);

$catalogoFrontend = array(
  "hay_resultados" => true,
  "items_en_pagina" => count($items),
  "total_paginas" => 1,
  "pagina_anterior" => null,
  "pagina_siguiente" => null,
  "rango_visible" => array(
    "desde" => 1,
    "hasta" => count($items),
    "total" => count($items),
    "texto" => "Mostrando 1-" . count($items) . " de " . count($items)
  ),
  "filtros_activos" => array(),
  "filtros_activos_total" => 0,
  "estado_vacio" => array(
    "mostrar" => false,
    "titulo" => "No encontramos productos con esos filtros",
    "accion_principal" => array("label" => "Limpiar filtros", "url" => "/catalogo")
  ),
  "guardrails_ui" => array(
    "no_mostrar_stock_exacto" => true,
    "precio_es_estimado" => true,
    "cotizacion_requiere_dryrun" => true
  )
);

$navegacionDepurar = array(
  "configurado" => true,
  "limite" => 8,
  "primaria" => array(
    array("codigo" => "inicio", "label" => "Inicio", "url" => "/", "tipo" => "ruta"),
    array("codigo" => "catalogo", "label" => "Catalogo", "url" => "/catalogo", "tipo" => "ruta"),
    array("codigo" => "cotizacion", "label" => "Cotizacion", "url" => "/cotizacion", "tipo" => "ruta"),
    array("codigo" => "politicas", "label" => "Politicas", "url" => "/politicas", "tipo" => "ruta")
  ),
  "mascotas" => array(
    array("codigo" => "mascota-perro", "tipo" => "mascota", "label" => "perro", "valor" => "perro", "url" => "/catalogo?mascota=perro", "total" => 2),
    array("codigo" => "mascota-gato", "tipo" => "mascota", "label" => "gato", "valor" => "gato", "url" => "/catalogo?mascota=gato", "total" => 1)
  ),
  "necesidades" => array(
    array("codigo" => "necesidad-alimento", "tipo" => "necesidad", "label" => "alimento", "valor" => "alimento", "url" => "/catalogo?necesidad=alimento", "total" => 1),
    array("codigo" => "necesidad-higiene", "tipo" => "necesidad", "label" => "higiene", "valor" => "higiene", "url" => "/catalogo?necesidad=higiene", "total" => 1)
  ),
  "categorias" => array(
    array("codigo" => "categoria-10", "tipo" => "categoria", "label" => "Perros / Alimento seco", "valor" => 10, "url" => "/catalogo?categoria=10", "total" => 1)
  ),
  "marcas" => array(
    array("codigo" => "marca-1", "tipo" => "marca", "label" => "Marca Demo", "valor" => 1, "url" => "/catalogo?marca=1", "total" => 1)
  ),
  "disponibilidad" => array(
    array("codigo" => "disponibilidad-disponible", "tipo" => "disponibilidad", "label" => "Disponible", "valor" => "disponible", "url" => "/catalogo?disponibilidad=disponible", "total" => 1),
    array("codigo" => "disponibilidad-pocas-piezas", "tipo" => "disponibilidad", "label" => "Pocas piezas", "valor" => "pocas_piezas", "url" => "/catalogo?disponibilidad=pocas_piezas", "total" => 1)
  ),
  "resumen" => array("total_items" => 12, "mascotas" => 2, "necesidades" => 2, "categorias" => 1, "marcas" => 1, "disponibilidad" => 2),
  "guardrails" => array("read_only" => true, "solo_derivado_de_publicaciones" => true, "no_expone_secretos" => true)
);

$seccionesDepurar = array(
  "configurado" => true,
  "limite" => 3,
  "secciones" => array(
    array("codigo" => "recientes", "titulo" => "Recientes", "tipo" => "catalogo", "total" => count($items), "items" => $items, "params_catalogo" => array("orden" => "recientes")),
    array("codigo" => "disponibles", "titulo" => "Disponibles", "tipo" => "catalogo", "total" => 1, "items" => array($items[0]), "params_catalogo" => array("disponibilidad" => "disponible")),
    array("codigo" => "mascota_perro", "titulo" => "Para perro", "tipo" => "mascota", "total" => 2, "items" => array($items[0], $items[2]), "params_catalogo" => array("mascota" => "perro"))
  ),
  "guardrails" => array("read_only" => true, "solo_publicados" => true, "no_stock_exacto" => true)
);

$busquedaSugerenciasDepurar = array(
  "q" => "perro",
  "configurado" => true,
  "grupos" => array(
    "productos" => array(
      array("tipo" => "producto", "label" => $items[0]["nombre"], "subtitulo" => $items[0]["marca"] . " " . $items[0]["presentacion"], "valor" => $items[0]["slug"], "url" => "/producto/" . $items[0]["slug"], "imagen" => $items[0]["imagen"], "precio" => $items[0]["precio"], "moneda" => "MXN", "disponibilidad" => $items[0]["disponibilidad"])
    ),
    "marcas" => array(),
    "categorias" => array(),
    "mascotas" => array(array("tipo" => "mascota", "label" => "perro", "valor" => "perro", "url" => "/catalogo?mascota=perro", "total" => 2)),
    "necesidades" => array()
  ),
  "resumen" => array("total_sugerencias" => 2, "productos" => 1, "marcas" => 0, "categorias" => 0, "mascotas" => 1, "necesidades" => 0, "sin_resultados" => false),
  "frontend" => array("registrar_busqueda_futura" => "/ecommercePublico/busqueda_registrar", "usar_catalogo_para_resultados" => "/ecommercePublico/catalogo?q=perro", "min_caracteres_recomendado" => 2),
  "guardrails" => array("read_only" => true, "no_registra_busqueda" => true, "solo_publicados" => true, "no_stock_exacto" => true)
);

$salida = array(
  "modo" => "fixtures_frontend_readonly",
  "advertencia" => "Fixtures para UI. No son productos reales ni deben mezclarse con ventas.",
  "base_api" => "http://panel.com.local/ecommercePublico",
  "bootstrap" => array(
    "error" => false,
    "tipo" => "success",
    "mensaje" => "Bootstrap ecommerce listo",
    "api" => $api,
    "depurar" => array(
      "ready" => true,
      "estado" => array("ready" => true, "publicaciones" => array("total_publicadas" => count($items))),
      "configuracion" => array("configurado" => true, "configuracion" => array("moneda_default" => "MXN", "mostrar_stock_exacto" => "0")),
      "filtros" => $filtrosDepurar,
      "navegacion" => $navegacionDepurar,
      "secciones" => $seccionesDepurar,
      "politicas" => array("items" => array()),
      "canales" => array("modo" => "multi_canal_diseno_readonly", "configurado" => false),
      "guardrails" => array("read_only" => true, "no_expone_secretos" => true, "no_stock_exacto" => true, "no_checkout" => true),
      "frontend" => array("usar_catalogo_para_paginacion" => "/ecommercePublico/catalogo", "usar_dryrun_antes_de_whatsapp" => true)
    )
  ),
  "estado" => array(
    "error" => false,
    "tipo" => "success",
    "mensaje" => "Estado API ecommerce consultado",
    "api" => $api,
    "depurar" => array(
      "ready" => true,
      "modo" => "catalogo_vivo_readonly",
      "schema" => array(
        "publicaciones_disponible" => true,
        "configuracion_disponible" => true,
        "ddl_pendiente" => false
      ),
      "publicaciones" => array(
        "total_publicadas" => count($items),
        "skus_publicables_fase_1" => count($items),
        "catalogo_publico_vacio" => false
      ),
      "guardrails" => array(
        "solo_readonly" => true,
        "no_checkout" => true,
        "no_descuenta_inventario" => true,
        "no_ecom_legacy_fuente" => true
      )
    )
  ),
  "configuracion" => array(
    "error" => false,
    "tipo" => "success",
    "mensaje" => "Configuracion ecommerce consultada",
    "api" => $api,
    "depurar" => array(
      "configurado" => true,
      "configuracion" => array(
        "moneda_default" => "MXN",
        "whatsapp_numero_principal" => "5215555555555",
        "whatsapp_mensaje_base" => "Hola, quiero cotizar estos productos:",
        "cors_origenes_permitidos" => "http://localhost:5173",
        "cotizacion_habilitada" => "1",
        "mostrar_stock_exacto" => "0",
        "modo_sin_stock" => "consultar",
        "texto_total_estimado" => "Total estimado sujeto a confirmacion",
        "url_sitio_publico" => "http://localhost:5173"
      ),
      "guardrails" => array("solo_claves_publicas" => true, "no_expone_secretos" => true)
    )
  ),
  "seo" => array(
    "error" => false,
    "tipo" => "success",
    "mensaje" => "SEO ecommerce publico consultado",
    "api" => $api,
    "depurar" => array(
      "configurado" => true,
      "meta" => array(
        "site_name" => "Catalogo mascotas",
        "title_default" => "Catalogo de productos para mascotas",
        "description_default" => "Consulta productos para tus mascotas, disponibilidad publica y cotiza por WhatsApp.",
        "og_type_catalogo" => "website",
        "og_type_producto" => "product",
        "canonical_base" => "http://localhost:5173",
        "robots_default" => "index,follow"
      ),
      "robots" => array(
        "permitir_indexacion" => true,
        "robots_txt_sugerido" => "User-agent: *\nAllow: /\nSitemap: http://localhost:5173/sitemap.xml",
        "noindex_si_catalogo_vacio" => true
      ),
      "sitemap" => array(
        "base_url_configurada" => "http://localhost:5173",
        "rutas_estaticas" => array(
          array("path" => "/", "priority" => "1.0", "changefreq" => "daily"),
          array("path" => "/cotizacion", "priority" => "0.3", "changefreq" => "weekly")
        ),
        "productos" => array_map(function($item) {
          return array(
            "slug" => $item["slug"],
            "path" => "/producto/" . $item["slug"],
            "title" => $item["nombre"],
            "description" => $item["descripcion"],
            "image" => $item["imagen"],
            "priority" => "0.8",
            "changefreq" => "daily"
          );
        }, $items),
        "filtros" => array(
          "mascotas" => array(
            array("valor" => "perro", "path" => "/?mascota=perro"),
            array("valor" => "gato", "path" => "/?mascota=gato")
          ),
          "necesidades" => array(
            array("valor" => "alimento", "path" => "/?necesidad=alimento"),
            array("valor" => "higiene", "path" => "/?necesidad=higiene")
          )
        )
      ),
      "rutas" => array(
        array("path" => "/", "tipo" => "estatica", "priority" => "1.0", "changefreq" => "daily"),
        array("path" => "/catalogo", "tipo" => "estatica", "priority" => "0.9", "changefreq" => "daily"),
        array("path" => "/catalogo?mascota=perro", "tipo" => "mascota", "priority" => "0.7", "changefreq" => "daily"),
        array("path" => "/producto/" . $items[0]["slug"], "tipo" => "producto", "priority" => "0.8", "changefreq" => "daily")
      ),
      "sitemap_xml_sugerido" => "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n  <url><loc>http://localhost:5173/</loc></url>\n  <url><loc>http://localhost:5173/catalogo</loc></url>\n</urlset>",
      "resumen" => array(
        "rutas_total" => 4,
        "productos" => count($items),
        "mascotas" => 2,
        "necesidades" => 4,
        "categorias" => 3,
        "marcas" => 3,
        "disponibilidad" => 3
      ),
      "json_ld" => array(
        "organization" => array(
          "@context" => "https://schema.org",
          "@type" => "PetStore",
          "name" => "Catalogo mascotas",
          "url" => "http://localhost:5173"
        ),
        "product_contract" => array(
          "@context" => "https://schema.org",
          "@type" => "Product",
          "name" => "item.nombre",
          "image" => "item.imagen",
          "description" => "item.descripcion",
          "sku" => "item.sku",
          "brand" => "item.marca"
        )
      ),
      "guardrails" => array("frontend_genera_archivos_seo" => true, "no_escribe_bd" => true, "no_usa_ecom_legacy" => true)
    )
  ),
  "filtros" => array(
    "error" => false,
    "tipo" => "success",
    "mensaje" => "Filtros ecommerce consultados",
    "api" => $api,
    "depurar" => $filtrosDepurar
  ),
  "busqueda_sugerencias" => array(
    "error" => false,
    "tipo" => "success",
    "mensaje" => "Sugerencias ecommerce consultadas",
    "api" => $api,
    "depurar" => $busquedaSugerenciasDepurar
  ),
  "navegacion" => array(
    "error" => false,
    "tipo" => "success",
    "mensaje" => "Navegacion ecommerce consultada",
    "api" => $api,
    "depurar" => $navegacionDepurar
  ),
  "secciones" => array(
    "error" => false,
    "tipo" => "success",
    "mensaje" => "Secciones ecommerce consultadas",
    "api" => $api,
    "depurar" => $seccionesDepurar
  ),
  "canales_estado" => array(
    "error" => false,
    "tipo" => "info",
    "mensaje" => "Capa canales/API ecommerce en diseno",
    "api" => $api,
    "depurar" => array(
      "configurado" => false,
      "modo" => "multi_canal_diseno_readonly",
      "tablas" => array(),
      "canales" => array("total" => 0, "items" => array()),
      "autenticacion" => array("credenciales" => array("activas" => 0, "secretos_expuestos" => false)),
      "activacion" => array("bloqueos" => array("tablas_canales_pendientes")),
      "guardrails" => array("read_only" => true, "no_genera_credenciales" => true, "no_expone_api_secret" => true, "no_activa_auth_obligatoria" => true)
    )
  ),
  "catalogo" => array(
    "error" => false,
    "tipo" => "success",
    "mensaje" => "Catalogo publico consultado",
    "api" => $api,
    "depurar" => array(
      "configurado" => true,
      "items" => $items,
      "paginacion" => array("pagina" => 1, "limite" => 24, "total" => count($items)),
      "filtros_aplicados" => array("q" => "", "mascota" => "", "necesidad" => "", "marca" => 0, "categoria" => 0, "disponibilidad" => "", "destacado" => false, "orden" => "relevancia"),
      "ordenamientos_disponibles" => array("relevancia", "nombre", "precio_asc", "precio_desc", "recientes"),
      "frontend" => $catalogoFrontend,
      "guardrails" => array("solo_publicados" => true, "no_stock_exacto" => true, "no_ecom_legacy_fuente" => true)
    )
  ),
  "catalogo_sin_resultados" => array(
    "error" => false,
    "tipo" => "success",
    "mensaje" => "Catalogo publico consultado",
    "api" => $api,
    "depurar" => array(
      "configurado" => true,
      "items" => array(),
      "paginacion" => array("pagina" => 1, "limite" => 24, "total" => 0),
      "filtros_aplicados" => array("q" => "__sin_resultados__", "mascota" => "", "necesidad" => "", "marca" => 0, "categoria" => 0, "disponibilidad" => "", "destacado" => false, "orden" => "relevancia"),
      "ordenamientos_disponibles" => array("relevancia", "nombre", "precio_asc", "precio_desc", "recientes"),
      "frontend" => array_merge($catalogoFrontend, array(
        "hay_resultados" => false,
        "items_en_pagina" => 0,
        "total_paginas" => 0,
        "rango_visible" => array("desde" => 0, "hasta" => 0, "total" => 0, "texto" => "Sin productos publicados para estos filtros"),
        "filtros_activos" => array("q"),
        "filtros_activos_total" => 1,
        "estado_vacio" => array("mostrar" => true, "titulo" => "No encontramos productos con esos filtros", "accion_principal" => array("label" => "Limpiar filtros", "url" => "/catalogo"))
      )),
      "guardrails" => array("solo_publicados" => true, "no_stock_exacto" => true, "no_ecom_legacy_fuente" => true)
    )
  ),
  "producto" => array(
    "error" => false,
    "tipo" => "success",
    "mensaje" => "Producto publico consultado",
    "api" => $api,
    "depurar" => array(
      "item" => $items[0],
      "variantes" => array(),
      "relacionados" => array($items[1], $items[2]),
      "breadcrumbs" => array(
        array("etiqueta" => "Inicio", "path" => "/"),
        array("etiqueta" => "Catalogo", "path" => "/catalogo"),
        array("etiqueta" => "Perros", "path" => "/catalogo?mascota=perro"),
        array("etiqueta" => $items[0]["nombre"], "path" => "/producto/" . $items[0]["slug"])
      ),
      "seo" => array("title" => $items[0]["nombre"] . " | Catalogo mascotas", "description" => $items[0]["descripcion"], "canonical" => "http://localhost:5173/producto/" . $items[0]["slug"]),
      "acciones" => array("puede_cotizar" => true, "puede_whatsapp" => true, "mostrar_precio" => true, "mostrar_disponibilidad" => true),
      "guardrails" => array("solo_publicado" => true, "solo_relacionados_publicados" => true, "no_stock_exacto" => true)
    )
  ),
  "disponibilidad" => array(
    "error" => false,
    "tipo" => "success",
    "mensaje" => "Disponibilidad publica consultada",
    "api" => $api,
    "depurar" => array(
      "id_sku" => $items[0]["id_sku"],
      "slug" => $items[0]["slug"],
      "disponibilidad" => $items[0]["disponibilidad"],
      "mostrar_cantidad_exacta" => false
    )
  ),
  "cotizacion_dryrun" => array(
    "error" => false,
    "tipo" => "success",
    "mensaje" => "Cotizacion dry-run validada",
    "api" => $api,
    "depurar" => array(
      "configurado" => true,
      "dry_run" => true,
      "no_escribe_bd" => true,
      "no_descuenta_inventario" => true,
      "no_crea_pedido" => true,
      "lineas" => array(
        array(
          "renglon" => 1,
          "id_publicacion" => $items[0]["id_publicacion"],
          "id_producto_erp" => $items[0]["id_producto_erp"],
          "id_sku" => $items[0]["id_sku"],
          "slug" => $items[0]["slug"],
          "sku" => $items[0]["sku"],
          "nombre" => $items[0]["nombre"],
          "presentacion" => $items[0]["presentacion"],
          "precio_unitario" => $items[0]["precio"],
          "moneda" => "MXN",
          "cantidad" => 2,
          "subtotal" => 578.00,
          "disponibilidad" => $items[0]["disponibilidad"],
          "permite_cotizacion" => true
        )
      ),
      "totales" => array(
        "subtotal_estimado" => 578.00,
        "total_estimado" => 578.00,
        "moneda" => "MXN",
        "texto" => "Total estimado sujeto a confirmacion"
      ),
      "bloqueos" => array(),
      "whatsapp_preview" => "Hola, quiero cotizar estos productos:\n\n1. Croqueta perro adulto pollo 2 kg - Cant. 2 - $578.00 MXN\n\nTotal estimado: $578.00 MXN\nSujeto a confirmacion de disponibilidad."
    )
  )
);

echo json_encode($salida, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
