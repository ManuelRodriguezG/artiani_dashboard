<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-15.
 * Proposito: emitir una especificacion OpenAPI basica para el frontend ecommerce externo.
 * Impacto: facilita generar clientes, docs o mocks sin crear endpoints nuevos.
 * Contrato: read-only; no consulta BD, no escribe datos y no toca inventario.
 */

$schema = array(
  "openapi" => "3.0.3",
  "info" => array(
    "title" => "ERP Ecommerce Publico API",
    "version" => "fase1-2026-07-12",
    "description" => "Catalogo vivo read-only conectado al ERP. Fase 1 sin checkout ni registro real de cotizacion."
  ),
  "servers" => array(
    array("url" => "http://panel.com.local/ecommercePublico")
  ),
  "paths" => array(
    "/contratos" => array("get" => endpointOpenApi("Contratos API ecommerce publico")),
    "/estado" => array("get" => endpointOpenApi("Readiness del API ecommerce publico")),
    "/configuracion" => array("get" => endpointOpenApi("Configuracion publica del canal ecommerce")),
    "/seo" => array("get" => endpointOpenApi("Metadatos SEO, robots, sitemap y JSON-LD sugeridos")),
    "/filtros" => array("get" => endpointOpenApi("Filtros disponibles de publicaciones vigentes")),
    "/catalogo" => array(
      "get" => array_merge(endpointOpenApi("Catalogo publico publicado"), array(
        "parameters" => array(
          queryParam("q", "Texto libre"),
          queryParam("mascota", "perro|gato|ave|pez|reptil|roedor|otra"),
          queryParam("necesidad", "alimento|premio|higiene|salud|paseo|habitat|juguete|estetica"),
          queryParam("marca", "ID marca ERP"),
          queryParam("categoria", "ID categoria ERP"),
          queryParam("pagina", "Pagina", "integer"),
          queryParam("limite", "Limite 1-60", "integer")
        )
      ))
    ),
    "/producto/{slug}" => array(
      "get" => array_merge(endpointOpenApi("Detalle publico de producto publicado"), array(
        "parameters" => array(
          array(
            "name" => "slug",
            "in" => "path",
            "required" => true,
            "schema" => array("type" => "string")
          )
        )
      ))
    ),
    "/disponibilidad" => array(
      "get" => array_merge(endpointOpenApi("Disponibilidad publica simple"), array(
        "parameters" => array(
          queryParam("slug", "Slug publico"),
          queryParam("id_sku", "ID SKU ERP", "integer")
        )
      ))
    ),
    "/cotizacion_dryrun" => array(
      "post" => array_merge(endpointOpenApi("Validacion de carrito sin persistencia"), array(
        "requestBody" => array(
          "required" => true,
          "content" => array(
            "application/json" => array(
              "schema" => array(
                "type" => "object",
                "properties" => array(
                  "items" => array(
                    "type" => "array",
                    "items" => array(
                      "type" => "object",
                      "properties" => array(
                        "id_publicacion" => array("type" => "integer"),
                        "slug" => array("type" => "string"),
                        "id_sku" => array("type" => "integer"),
                        "cantidad" => array("type" => "number")
                      ),
                      "required" => array("cantidad")
                    )
                  ),
                  "contacto" => array("type" => "object"),
                  "utm" => array("type" => "object")
                ),
                "required" => array("items")
              )
            )
          )
        )
      ))
    ),
    "/cotizacion_registrar" => array(
      "post" => endpointOpenApi("Reservado futuro; bloqueado en Fase 1")
    )
  ),
  "components" => array(
    "schemas" => array(
      "ErpApiMeta" => array(
        "type" => "object",
        "properties" => array(
          "nombre" => array("type" => "string"),
          "version" => array("type" => "string", "example" => "fase1-2026-07-12"),
          "modo" => array("type" => "string", "example" => "catalogo_vivo_readonly"),
          "fuente_verdad" => array("type" => "string", "example" => "ERP"),
          "moneda_default" => array("type" => "string", "example" => "MXN")
        )
      ),
      "ErpResponse" => array(
        "type" => "object",
        "properties" => array(
          "error" => array("type" => "boolean"),
          "tipo" => array("type" => "string"),
          "mensaje" => array("type" => "string"),
          "api" => array('$ref' => "#/components/schemas/ErpApiMeta"),
          "depurar" => array("type" => "object")
        ),
        "required" => array("error", "tipo", "mensaje", "api", "depurar")
      ),
      "ProductoCatalogo" => array(
        "type" => "object",
        "properties" => array(
          "id_publicacion" => array("type" => "integer"),
          "id_producto_erp" => array("type" => "integer"),
          "id_sku" => array("type" => "integer"),
          "slug" => array("type" => "string"),
          "sku" => array("type" => "string"),
          "nombre" => array("type" => "string"),
          "marca" => array("type" => "string", "nullable" => true),
          "categoria" => array("type" => "string", "nullable" => true),
          "presentacion" => array("type" => "string", "nullable" => true),
          "descripcion" => array("type" => "string", "nullable" => true),
          "imagen" => array("type" => "string", "nullable" => true),
          "precio" => array("type" => "number", "nullable" => true),
          "moneda" => array("type" => "string", "nullable" => true),
          "disponibilidad" => array("type" => "string", "enum" => array("disponible", "pocas_piezas", "consultar_disponibilidad", "agotado")),
          "mascota_especie" => array("type" => "string", "nullable" => true),
          "necesidades" => array("type" => "array", "items" => array("type" => "string")),
          "permite_cotizacion" => array("type" => "boolean"),
          "permite_whatsapp" => array("type" => "boolean")
        )
      )
    )
  ),
  "x-guardrails" => array(
    "no_checkout" => true,
    "no_descuenta_inventario" => true,
    "no_stock_exacto" => true,
    "no_ecom_legacy_fuente" => true,
    "cotizacion_registrar_bloqueado_fase1" => true
  )
);

echo json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function endpointOpenApi($summary) {
  return array(
    "summary" => $summary,
    "responses" => array(
      "200" => array(
        "description" => "Respuesta ERP ecommerce",
        "content" => array(
          "application/json" => array(
            "schema" => array('$ref' => "#/components/schemas/ErpResponse")
          )
        )
      )
    )
  );
}

function queryParam($name, $description, $type = "string") {
  return array(
    "name" => $name,
    "in" => "query",
    "required" => false,
    "description" => $description,
    "schema" => array("type" => $type)
  );
}
