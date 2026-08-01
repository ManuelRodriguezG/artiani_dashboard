<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-31.
 * Proposito: generar una coleccion Postman/Insomnia para probar la API ecommerce publica desde el frontend externo.
 * Impacto: facilita QA de contratos sin leer BD, sin ejecutar DDL y sin tocar inventario.
 * Contrato: read-only; no registra cotizaciones reales y mantiene `cotizacion_registrar` como prueba bloqueada.
 */

$opciones = getopt("", array(
  "base::"
));

$base = isset($opciones["base"]) ? rtrim(trim((string) $opciones["base"]), "/") : "http://panel.com.local";

$collection = array(
  "info" => array(
    "name" => "ERP Ecommerce Publico Fase 1",
    "description" => "Catalogo vivo conectado al ERP. Fase 1 sin checkout, sin pagos, sin descuento de inventario y sin uso de legacy ecom_*.",
    "schema" => "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
  ),
  "variable" => array(
    array("key" => "base_url", "value" => $base),
    array("key" => "producto_slug", "value" => "slug-de-prueba-no-publicado"),
    array("key" => "id_publicacion", "value" => "1")
  ),
  "item" => array(
    requestPostmanReadonly("Contratos", "GET", "{{base_url}}/ecommercePublico/contratos"),
    requestPostmanReadonly("Estado readiness", "GET", "{{base_url}}/ecommercePublico/estado"),
    requestPostmanReadonly("Bootstrap frontend", "GET", "{{base_url}}/ecommercePublico/bootstrap?limite_secciones=6"),
    requestPostmanReadonly("Configuracion publica", "GET", "{{base_url}}/ecommercePublico/configuracion"),
    requestPostmanReadonly("SEO publico robusto", "GET", "{{base_url}}/ecommercePublico/seo?limite=20"),
    requestPostmanReadonly("Filtros publicos", "GET", "{{base_url}}/ecommercePublico/filtros"),
    requestPostmanReadonly("Busqueda sugerencias", "GET", "{{base_url}}/ecommercePublico/busqueda_sugerencias?q=filtro&limite=4"),
    requestPostmanReadonly("Navegacion publica", "GET", "{{base_url}}/ecommercePublico/navegacion?limite=8"),
    requestPostmanReadonly("Secciones home", "GET", "{{base_url}}/ecommercePublico/secciones?limite=4"),
    requestPostmanReadonly("Politicas publicas", "GET", "{{base_url}}/ecommercePublico/politicas"),
    requestPostmanReadonly("Politica facturacion", "GET", "{{base_url}}/ecommercePublico/politica/facturacion"),
    requestPostmanReadonly("Taxonomia mascotas", "GET", "{{base_url}}/ecommercePublico/taxonomia_mascotas"),
    requestPostmanReadonly("Catalogo robusto", "GET", "{{base_url}}/ecommercePublico/catalogo?q=&mascota=&necesidad=&disponibilidad=disponible&destacado=&orden=relevancia&pagina=1&limite=24"),
    requestPostmanReadonly("Producto por slug", "GET", "{{base_url}}/ecommercePublico/producto/{{producto_slug}}"),
    requestPostmanReadonly("Disponibilidad por slug", "GET", "{{base_url}}/ecommercePublico/disponibilidad?slug={{producto_slug}}"),
    requestPostmanReadonly("Canales/API estado", "GET", "{{base_url}}/ecommercePublico/canales_estado"),
    requestPostmanReadonly("Cotizacion dry-run", "POST", "{{base_url}}/ecommercePublico/cotizacion_dryrun", array(
      "items" => array(
        array("id_publicacion" => 1, "cantidad" => 1)
      ),
      "contacto" => array(
        "nombre" => "Cliente prueba",
        "telefono" => "",
        "mensaje" => "Quiero confirmar disponibilidad"
      ),
      "utm" => array("source" => "postman")
    )),
    requestPostmanReadonly("Cotizacion preflight sin persistencia", "POST", "{{base_url}}/ecommercePublico/cotizacion_preflight", array(
      "items" => array(
        array("id_publicacion" => 1, "cantidad" => 1)
      ),
      "contacto" => array(
        "nombre" => "Cliente prueba",
        "telefono" => "3322068429",
        "mensaje" => "Quiero confirmar disponibilidad"
      ),
      "acepta_contacto_whatsapp" => true,
      "politicas_aceptadas" => array("aviso-privacidad", "cotizacion-whatsapp"),
      "utm" => array("source" => "postman")
    )),
    requestPostmanReadonly("Cotizacion registrar bloqueada Fase 1", "POST", "{{base_url}}/ecommercePublico/cotizacion_registrar", array(
      "items" => array(
        array("id_publicacion" => 1, "cantidad" => 1)
      ),
      "contacto" => array(
        "nombre" => "Cliente prueba",
        "telefono" => "5555555555"
      )
    )),
    requestPostmanReadonly("Facturacion solicitar preflight", "POST", "{{base_url}}/ecommercePublico/facturacion_solicitar", array(
      "folio_compra" => "TICKET-123",
      "fecha_compra" => "2026-07-29",
      "importe" => 250,
      "datos_fiscales" => array(
        "rfc" => "XAXX010101000",
        "razon_social" => "Cliente prueba",
        "regimen_fiscal" => "616",
        "uso_cfdi" => "G03",
        "codigo_postal" => "44100"
      ),
      "contacto" => array(
        "correo" => "cliente@example.com",
        "telefono" => "3322068429"
      ),
      "acepta_aviso_privacidad" => true
    )),
    requestPostmanReadonly("Evento navegacion preflight", "POST", "{{base_url}}/ecommercePublico/evento_navegacion", array(
      "session_id" => "sess_postman_123",
      "tipo_evento" => "view_product",
      "ruta" => "/producto/demo",
      "id_publicacion" => 1,
      "metadata" => array("origen" => "postman")
    )),
    requestPostmanReadonly("Busqueda registrar preflight", "POST", "{{base_url}}/ecommercePublico/busqueda_registrar", array(
      "session_id" => "sess_postman_123",
      "query" => "arena para gato",
      "mascota" => "gato",
      "necesidad" => "higiene",
      "resultados_total" => 0,
      "filtros" => array("marca" => "")
    ))
  )
);

echo json_encode($collection, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function requestPostmanReadonly($nombre, $metodo, $url, $body = null) {
  $request = array(
    "name" => $nombre,
    "request" => array(
      "method" => $metodo,
      "header" => array(
        array("key" => "Accept", "value" => "application/json")
      ),
      "url" => array("raw" => $url)
    ),
    "event" => array(
      array(
        "listen" => "test",
        "script" => array(
          "type" => "text/javascript",
          "exec" => array(
            "pm.test('respuesta JSON', function () { pm.response.to.have.header('Content-Type'); });",
            "pm.test('contrato wrapper basico', function () { var json = pm.response.json(); pm.expect(json).to.have.property('error'); pm.expect(json).to.have.property('tipo'); pm.expect(json).to.have.property('mensaje'); pm.expect(json).to.have.property('api'); pm.expect(json).to.have.property('depurar'); });",
            "pm.test('fuente ERP', function () { var json = pm.response.json(); pm.expect(json.api.fuente_verdad).to.eql('ERP'); });"
          )
        )
      )
    )
  );
  if ($body !== null) {
    $request["request"]["header"][] = array("key" => "Content-Type", "value" => "application/json");
    $request["request"]["body"] = array(
      "mode" => "raw",
      "raw" => json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
      "options" => array("raw" => array("language" => "json"))
    );
  }
  return $request;
}
