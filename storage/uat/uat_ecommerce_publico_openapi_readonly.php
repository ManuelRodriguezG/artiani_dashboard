<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-08-01.
 * Proposito: emitir una especificacion OpenAPI basica para el frontend ecommerce externo.
 * Impacto: facilita generar clientes, docs o mocks sin crear endpoints nuevos.
 * Contrato: read-only; no consulta BD, no escribe datos y no toca inventario.
 */

$schema = array(
  "openapi" => "3.0.3",
  "info" => array(
    "title" => "ERP Ecommerce Publico API",
    "version" => "fase1-2026-07-12",
    "description" => "Catalogo vivo read-only conectado al ERP. Fase 2 de catalogo robusto activa; sin checkout ni registro real de cotizacion."
  ),
  "servers" => array(
    array("url" => "http://panel.com.local/ecommercePublico")
  ),
  "paths" => array(
    "/contratos" => array("get" => endpointOpenApi("Contratos API ecommerce publico")),
    "/estado" => array("get" => endpointOpenApi("Readiness del API ecommerce publico")),
    "/frontend_handoff" => array(
      "get" => array_merge(endpointOpenApi("Handoff vivo para frontend externo", "#/components/schemas/FrontendHandoffResponse"), array(
        "parameters" => array(
          queryParam("limite", "Limite 1-6 productos ejemplo", "integer")
        )
      ))
    ),
    "/configuracion_inicial" => array(
      "get" => array_merge(endpointOpenApi("Configuracion inicial recomendada para primer render ecommerce"), array(
        "parameters" => array(
          queryParam("limite_secciones", "Limite 1-12 por seccion", "integer")
        )
      ))
    ),
    "/bootstrap" => array(
      "get" => array_merge(endpointOpenApi("Paquete inicial para frontend ecommerce"), array(
        "parameters" => array(
          queryParam("limite_secciones", "Limite 1-12 por seccion", "integer")
        )
      ))
    ),
    "/contenido_manifest" => array(
      "get" => array_merge(endpointOpenApi("Manifest del CMS ligero: plantillas, slots y tipos de bloque"), array(
        "parameters" => array(
          queryParam("plantilla", "Codigo de plantilla; default artiani_default")
        )
      ))
    ),
    "/contenido_pagina" => array(
      "get" => array_merge(endpointOpenApi("Contenido editorial por pagina para banners, colecciones y bloques"), array(
        "parameters" => array(
          queryParam("pagina", "home|categoria|catalogo"),
          queryParam("plantilla", "Codigo de plantilla; default artiani_default"),
          queryParam("categoria", "Slug/codigo de categoria cuando pagina=categoria")
        )
      ))
    ),
    "/configuracion" => array("get" => endpointOpenApi("Configuracion publica del canal ecommerce")),
    "/seo" => array(
      "get" => array_merge(endpointOpenApi("Metadatos SEO, robots, sitemap, rutas y JSON-LD sugeridos"), array(
        "parameters" => array(
          queryParam("limite", "Limite 1-200 productos", "integer")
        )
      ))
    ),
    "/filtros" => array("get" => endpointOpenApi("Filtros disponibles de publicaciones vigentes")),
    "/busqueda_sugerencias" => array(
      "get" => array_merge(endpointOpenApi("Sugerencias para buscador publico"), array(
        "parameters" => array(
          queryParam("q", "Texto libre opcional"),
          queryParam("limite", "Limite 1-12 por grupo", "integer")
        )
      ))
    ),
    "/navegacion" => array(
      "get" => array_merge(endpointOpenApi("Navegacion publica para menus, chips y rutas"), array(
        "parameters" => array(
          queryParam("limite", "Limite 1-30 por grupo", "integer")
        )
      ))
    ),
    "/secciones" => array(
      "get" => array_merge(endpointOpenApi("Secciones de catalogo listas para home/frontend"), array(
        "parameters" => array(
          queryParam("limite", "Limite 1-12 por seccion", "integer"),
          queryParam("incluir_vacias", "1 para devolver secciones vacias")
        )
      ))
    ),
    "/politicas" => array("get" => endpointOpenApi("Politicas publicas para terminos, privacidad, facturacion y tracking")),
    "/politica/{slug}" => array(
      "get" => array_merge(endpointOpenApi("Detalle de politica publica"), array(
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
    "/taxonomia_mascotas" => array("get" => endpointOpenApi("Taxonomia publica para navegacion por mascota y necesidad")),
    "/catalogo" => array(
      "get" => array_merge(endpointOpenApi("Catalogo publico publicado", "#/components/schemas/CatalogoResponse"), array(
        "parameters" => array(
          queryParam("q", "Texto libre"),
          queryParam("mascota", "perro|gato|ave|pez|reptil|roedor|otra"),
          queryParam("necesidad", "alimento|premio|higiene|salud|paseo|habitat|juguete|estetica"),
          queryParam("marca", "ID marca ERP"),
          queryParam("categoria", "ID categoria ERP"),
          queryParam("disponibilidad", "disponible|pocas_piezas|consultar_disponibilidad|agotado"),
          queryParam("destacado", "1 para solo destacados"),
          queryParam("orden", "relevancia|nombre|precio_asc|precio_desc|recientes"),
          queryParam("pagina", "Pagina", "integer"),
          queryParam("limite", "Limite 1-60", "integer")
        )
      ))
    ),
    "/catalogo_manifest" => array(
      "get" => array_merge(endpointOpenApi("Manifiesto robusto de catalogo para frontend", "#/components/schemas/CatalogoManifestResponse"), array(
        "parameters" => array(
          queryParam("limite_preview", "Limite 1-6 productos ejemplo", "integer")
        )
      ))
    ),
    "/fase_2_checklist" => array("get" => endpointOpenApi("Checklist de cierre Fase 2 para frontend externo")),
    "/canales_estado" => array("get" => endpointOpenApi("Estado publico seguro de canales/API para Artiani y partners")),
    "/producto/{slug}" => array(
      "get" => array_merge(endpointOpenApi("Detalle publico de producto publicado", "#/components/schemas/ProductoDetalleResponse"), array(
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
      "get" => array_merge(endpointOpenApi("Disponibilidad publica simple", "#/components/schemas/DisponibilidadResponse"), array(
        "parameters" => array(
          queryParam("slug", "Slug publico"),
          queryParam("id_sku", "ID SKU ERP", "integer")
        )
      ))
    ),
    "/cotizacion_dryrun" => array(
      "post" => array_merge(endpointOpenApi("Validacion de carrito sin persistencia", "#/components/schemas/CotizacionDryRunResponse"), array(
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
    "/cotizacion_preflight" => array(
      "post" => array_merge(endpointOpenApi("Preflight de carrito, contacto y WhatsApp sin persistencia"), array(
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
                  "contacto" => array(
                    "type" => "object",
                    "properties" => array(
                      "nombre" => array("type" => "string"),
                      "telefono" => array("type" => "string"),
                      "correo" => array("type" => "string"),
                      "mensaje" => array("type" => "string")
                    )
                  ),
                  "acepta_contacto_whatsapp" => array("type" => "boolean"),
                  "politicas_aceptadas" => array("type" => "array", "items" => array("type" => "string")),
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
    ),
    "/facturacion_solicitar" => array(
      "post" => array_merge(endpointOpenApi("Preflight de solicitud de factura por folio sin persistencia"), array(
        "requestBody" => jsonBodyOpenApi(array(
          "folio_compra" => array("type" => "string"),
          "fecha_compra" => array("type" => "string"),
          "importe" => array("type" => "number"),
          "datos_fiscales" => array("type" => "object"),
          "contacto" => array("type" => "object"),
          "acepta_aviso_privacidad" => array("type" => "boolean")
        ), array("folio_compra"))
      ))
    ),
    "/evento_navegacion" => array(
      "post" => array_merge(endpointOpenApi("Preflight de evento anonimo de navegacion sin persistencia"), array(
        "requestBody" => jsonBodyOpenApi(array(
          "session_id" => array("type" => "string"),
          "tipo_evento" => array("type" => "string"),
          "ruta" => array("type" => "string"),
          "mascota" => array("type" => "string"),
          "necesidad" => array("type" => "string"),
          "id_publicacion" => array("type" => "integer"),
          "id_sku" => array("type" => "integer"),
          "metadata" => array("type" => "object")
        ), array("session_id", "tipo_evento"))
      ))
    ),
    "/busqueda_registrar" => array(
      "post" => array_merge(endpointOpenApi("Preflight de busqueda anonima sin persistencia"), array(
        "requestBody" => jsonBodyOpenApi(array(
          "session_id" => array("type" => "string"),
          "query" => array("type" => "string"),
          "mascota" => array("type" => "string"),
          "necesidad" => array("type" => "string"),
          "resultados_total" => array("type" => "integer"),
          "filtros" => array("type" => "object")
        ), array("session_id", "query"))
      ))
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
      ),
      "CatalogoFrontend" => array(
        "type" => "object",
        "properties" => array(
          "hay_resultados" => array("type" => "boolean"),
          "items_en_pagina" => array("type" => "integer"),
          "total_paginas" => array("type" => "integer"),
          "pagina_anterior" => array("type" => "integer", "nullable" => true),
          "pagina_siguiente" => array("type" => "integer", "nullable" => true),
          "rango_visible" => array(
            "type" => "object",
            "properties" => array(
              "desde" => array("type" => "integer"),
              "hasta" => array("type" => "integer"),
              "total" => array("type" => "integer"),
              "texto" => array("type" => "string", "example" => "Mostrando 1-3 de 6")
            )
          ),
          "filtros_activos" => array("type" => "array", "items" => array("type" => "string")),
          "filtros_activos_total" => array("type" => "integer"),
          "estado_vacio" => array(
            "type" => "object",
            "properties" => array(
              "mostrar" => array("type" => "boolean"),
              "titulo" => array("type" => "string"),
              "accion_principal" => array("type" => "object")
            )
          ),
          "guardrails_ui" => array(
            "type" => "object",
            "properties" => array(
              "no_mostrar_stock_exacto" => array("type" => "boolean", "example" => true),
              "precio_es_estimado" => array("type" => "boolean", "example" => true),
              "cotizacion_requiere_dryrun" => array("type" => "boolean", "example" => true)
            )
          )
        )
      ),
      "CatalogoDepurar" => array(
        "type" => "object",
        "properties" => array(
          "configurado" => array("type" => "boolean"),
          "items" => array("type" => "array", "items" => array('$ref' => "#/components/schemas/ProductoCatalogo")),
          "paginacion" => array("type" => "object"),
          "filtros_aplicados" => array("type" => "object"),
          "ordenamientos_disponibles" => array("type" => "array", "items" => array("type" => "object")),
          "frontend" => array('$ref' => "#/components/schemas/CatalogoFrontend"),
          "fase_2" => array("type" => "object"),
          "guardrails" => array("type" => "object")
        )
      ),
      "CatalogoResponse" => erpResponseSchema("#/components/schemas/CatalogoDepurar"),
      "CatalogoManifestDepurar" => array(
        "type" => "object",
        "properties" => array(
          "fase" => array("type" => "string", "example" => "fase_2_api_catalogo_robusta"),
          "estado_catalogo" => array("type" => "object"),
          "parametros_soportados" => array("type" => "object"),
          "ordenamientos" => array("type" => "array", "items" => array("type" => "object")),
          "endpoints_relacionados" => array("type" => "object"),
          "ejemplos" => array("type" => "object"),
          "preview" => array("type" => "object"),
          "guardrails" => array("type" => "object")
        )
      ),
      "CatalogoManifestResponse" => erpResponseSchema("#/components/schemas/CatalogoManifestDepurar"),
      "ProductoDetalleDepurar" => array(
        "type" => "object",
        "properties" => array(
          "item" => array('$ref' => "#/components/schemas/ProductoCatalogo", "nullable" => true),
          "variantes" => array("type" => "array", "items" => array('$ref' => "#/components/schemas/ProductoCatalogo")),
          "relacionados" => array("type" => "array", "items" => array('$ref' => "#/components/schemas/ProductoCatalogo")),
          "breadcrumbs" => array("type" => "array", "items" => array("type" => "object")),
          "seo" => array("type" => "object"),
          "acciones" => array("type" => "object"),
          "fase_2" => array("type" => "object"),
          "guardrails" => array("type" => "object")
        )
      ),
      "ProductoDetalleResponse" => erpResponseSchema("#/components/schemas/ProductoDetalleDepurar"),
      "DisponibilidadFrontend" => array(
        "type" => "object",
        "properties" => array(
          "estado" => array("type" => "string", "enum" => array("disponible", "pocas_piezas", "consultar_disponibilidad", "agotado")),
          "badge" => array("type" => "object"),
          "mensaje" => array("type" => "string"),
          "cta" => array("type" => "object"),
          "mostrar_stock_exacto" => array("type" => "boolean", "example" => false),
          "precio_es_estimado" => array("type" => "boolean", "example" => true),
          "requiere_dryrun_antes_de_whatsapp" => array("type" => "boolean", "example" => true)
        )
      ),
      "DisponibilidadDepurar" => array(
        "type" => "object",
        "properties" => array(
          "id_sku" => array("type" => "integer"),
          "slug" => array("type" => "string"),
          "disponibilidad" => array("type" => "string", "enum" => array("disponible", "pocas_piezas", "consultar_disponibilidad", "agotado")),
          "mostrar_cantidad_exacta" => array("type" => "boolean", "example" => false),
          "permite_cotizacion" => array("type" => "boolean"),
          "frontend" => array('$ref' => "#/components/schemas/DisponibilidadFrontend"),
          "fase_2" => array("type" => "object")
        )
      ),
      "DisponibilidadResponse" => erpResponseSchema("#/components/schemas/DisponibilidadDepurar"),
      "CotizacionDryRunFrontend" => array(
        "type" => "object",
        "properties" => array(
          "estado" => array("type" => "string", "enum" => array("vacio", "listo", "observaciones", "bloqueado")),
          "mensaje" => array("type" => "string"),
          "lineas_total" => array("type" => "integer"),
          "bloqueos_total" => array("type" => "integer"),
          "advertencias_total" => array("type" => "integer"),
          "puede_continuar_preflight" => array("type" => "boolean"),
          "mostrar_total_estimado" => array("type" => "boolean"),
          "mostrar_whatsapp_preview" => array("type" => "boolean"),
          "total_estimado_texto" => array("type" => "string", "example" => "$85.00 MXN"),
          "cta_principal" => array("type" => "object"),
          "guardrails_ui" => array("type" => "object")
        )
      ),
      "CotizacionDryRunDepurar" => array(
        "type" => "object",
        "properties" => array(
          "configurado" => array("type" => "boolean"),
          "dry_run" => array("type" => "boolean", "example" => true),
          "no_escribe_bd" => array("type" => "boolean", "example" => true),
          "no_descuenta_inventario" => array("type" => "boolean", "example" => true),
          "no_crea_pedido" => array("type" => "boolean", "example" => true),
          "lineas" => array("type" => "array", "items" => array("type" => "object")),
          "totales" => array("type" => "object"),
          "resumen" => array("type" => "object"),
          "advertencias" => array("type" => "array", "items" => array("type" => "string")),
          "bloqueos" => array("type" => "array", "items" => array("type" => "string")),
          "whatsapp_preview" => array("type" => "string"),
          "frontend" => array('$ref' => "#/components/schemas/CotizacionDryRunFrontend"),
          "fase_2" => array("type" => "object")
        )
      ),
      "CotizacionDryRunResponse" => erpResponseSchema("#/components/schemas/CotizacionDryRunDepurar"),
      "FrontendHandoffDepurar" => array(
        "type" => "object",
        "properties" => array(
          "estado_actual" => array("type" => "object"),
          "variables_env_frontend" => array("type" => "object"),
          "endpoints_para_consumir" => array("type" => "array", "items" => array("type" => "object")),
          "orden_recomendado_integracion" => array("type" => "array", "items" => array("type" => "string")),
          "pruebas_con_api" => array("type" => "array", "items" => array("type" => "object")),
          "contratos_ui" => array("type" => "object"),
          "ejemplos" => array("type" => "object"),
          "fase_2" => array("type" => "object"),
          "no_usar" => array("type" => "array", "items" => array("type" => "string")),
          "guardrails" => array("type" => "object")
        )
      ),
      "FrontendHandoffResponse" => erpResponseSchema("#/components/schemas/FrontendHandoffDepurar"),
      "PoliticaPublica" => array(
        "type" => "object",
        "properties" => array(
          "codigo" => array("type" => "string"),
          "tipo" => array("type" => "string"),
          "titulo" => array("type" => "string"),
          "resumen" => array("type" => "string"),
          "contenido" => array("type" => "string"),
          "version" => array("type" => "string"),
          "requiere_aceptacion" => array("type" => "boolean"),
          "fecha_vigencia" => array("type" => "string", "nullable" => true)
        )
      ),
      "TaxonomiaMascota" => array(
        "type" => "object",
        "properties" => array(
          "codigo" => array("type" => "string"),
          "tipo" => array("type" => "string", "enum" => array("especie", "necesidad")),
          "parent_codigo" => array("type" => "string", "nullable" => true),
          "nombre" => array("type" => "string"),
          "descripcion" => array("type" => "string"),
          "icono" => array("type" => "string"),
          "orden" => array("type" => "integer")
        )
      )
    )
  ),
  "x-guardrails" => array(
    "no_checkout" => true,
    "no_descuenta_inventario" => true,
    "no_granel" => true,
    "no_stock_exacto" => true,
    "no_ecom_legacy_fuente" => true,
    "fase_2_catalogo_robusto" => true,
    "cotizacion_registrar_bloqueado_fase1" => true
  )
);

echo json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function endpointOpenApi($summary, $schemaRef = null) {
  $schema = $schemaRef === null
    ? array('$ref' => "#/components/schemas/ErpResponse")
    : array('$ref' => $schemaRef);

  return array(
    "summary" => $summary,
    "responses" => array(
      "200" => array(
        "description" => "Respuesta ERP ecommerce",
        "content" => array(
          "application/json" => array(
            "schema" => $schema
          )
        )
      )
    )
  );
}

function erpResponseSchema($depurarRef) {
  return array(
    "type" => "object",
    "properties" => array(
      "error" => array("type" => "boolean"),
      "tipo" => array("type" => "string"),
      "mensaje" => array("type" => "string"),
      "api" => array('$ref' => "#/components/schemas/ErpApiMeta"),
      "depurar" => array('$ref' => $depurarRef)
    ),
    "required" => array("error", "tipo", "mensaje", "api", "depurar")
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

function jsonBodyOpenApi($properties, $required = array()) {
  return array(
    "required" => true,
    "content" => array(
      "application/json" => array(
        "schema" => array(
          "type" => "object",
          "properties" => $properties,
          "required" => $required
        )
      )
    )
  );
}
