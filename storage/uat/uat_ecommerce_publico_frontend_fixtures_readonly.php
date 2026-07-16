<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-15.
 * Proposito: entregar fixtures JSON para iniciar el frontend ecommerce mientras el catalogo real sigue en amarillo.
 * Impacto: permite construir UI, filtros, ficha y carrito sin inventar contratos ni leer BD.
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

$salida = array(
  "modo" => "fixtures_frontend_readonly",
  "advertencia" => "Fixtures para UI. No son productos reales ni deben mezclarse con ventas.",
  "base_api" => "http://panel.com.local/ecommercePublico",
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
    "depurar" => array(
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
      )
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
      "guardrails" => array("solo_publicado" => true, "no_stock_exacto" => true)
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
