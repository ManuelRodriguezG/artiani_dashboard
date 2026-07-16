<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-15.
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
    requestPostmanReadonly("Configuracion publica", "GET", "{{base_url}}/ecommercePublico/configuracion"),
    requestPostmanReadonly("SEO publico", "GET", "{{base_url}}/ecommercePublico/seo"),
    requestPostmanReadonly("Filtros publicos", "GET", "{{base_url}}/ecommercePublico/filtros"),
    requestPostmanReadonly("Catalogo", "GET", "{{base_url}}/ecommercePublico/catalogo?q=&mascota=&necesidad=&pagina=1&limite=24"),
    requestPostmanReadonly("Producto por slug", "GET", "{{base_url}}/ecommercePublico/producto/{{producto_slug}}"),
    requestPostmanReadonly("Disponibilidad por slug", "GET", "{{base_url}}/ecommercePublico/disponibilidad?slug={{producto_slug}}"),
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
    requestPostmanReadonly("Cotizacion registrar bloqueada Fase 1", "POST", "{{base_url}}/ecommercePublico/cotizacion_registrar", array(
      "items" => array(
        array("id_publicacion" => 1, "cantidad" => 1)
      ),
      "contacto" => array(
        "nombre" => "Cliente prueba",
        "telefono" => "5555555555"
      )
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
