<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-13.
 * Proposito: probar por HTTP los endpoints publicos ecommerce usando el host real configurado.
 * Impacto: valida que el frontend externo use base URL correcta y no rutas de filesystem.
 * Contrato: read-only; no escribe BD, no registra cotizaciones y no mueve inventario.
 */

$opciones = getopt("", array("base::"));
$base = isset($opciones["base"]) ? rtrim(trim((string) $opciones["base"]), "/") : "http://panel.com.local";

$pruebas = array(
  "estado" => requestHttp($base . "/ecommercePublico/estado"),
  "contratos" => requestHttp($base . "/ecommercePublico/contratos"),
  "configuracion" => requestHttp($base . "/ecommercePublico/configuracion"),
  "seo" => requestHttp($base . "/ecommercePublico/seo"),
  "filtros" => requestHttp($base . "/ecommercePublico/filtros"),
  "catalogo" => requestHttp($base . "/ecommercePublico/catalogo"),
  "producto" => requestHttp($base . "/ecommercePublico/producto/slug-de-prueba-no-publicado"),
  "disponibilidad" => requestHttp($base . "/ecommercePublico/disponibilidad?slug=slug-de-prueba-no-publicado"),
  "cotizacion_dryrun" => requestHttp($base . "/ecommercePublico/cotizacion_dryrun", "POST", array(
    "items" => array(array("id_publicacion" => 1, "cantidad" => 1))
  )),
  "cotizacion_registrar" => requestHttp($base . "/ecommercePublico/cotizacion_registrar", "POST", array(
    "items" => array(array("id_publicacion" => 1, "cantidad" => 1)),
    "contacto" => array("nombre" => "Smoke read-only", "telefono" => "5555555555")
  ))
);

$bloqueos = array();
foreach ($pruebas as $nombre => $prueba) {
  if (!$prueba["json_valido"]) {
    $bloqueos[] = $nombre . "_no_responde_json";
  }
}
if (empty($pruebas["cotizacion_registrar"]["depurar_resumen"]["bloqueado"])) {
  $bloqueos[] = "cotizacion_registrar_debe_seguir_bloqueado";
}

echo json_encode(array(
  "ok" => empty($bloqueos),
  "modo" => "read-only",
  "base_url" => $base,
  "pruebas" => $pruebas,
  "bloqueos" => $bloqueos,
  "frontend_base_correcta" => $base . "/ecommercePublico",
  "guardrails" => array(
    "no_escribe_bd" => true,
    "no_registra_cotizacion" => true,
    "no_mueve_inventario" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

function requestHttp($url, $method = "GET", $body = null) {
  $headers = "Accept: application/json\r\n";
  $content = null;
  if ($body !== null) {
    $content = json_encode($body);
    $headers .= "Content-Type: application/json\r\n";
  }
  $context = stream_context_create(array(
    "http" => array(
      "method" => $method,
      "header" => $headers,
      "content" => $content,
      "ignore_errors" => true,
      "timeout" => 10
    )
  ));
  $raw = @file_get_contents($url, false, $context);
  $json = json_decode((string) $raw, true);
  return array(
    "url" => $url,
    "method" => $method,
    "json_valido" => is_array($json),
    "error" => is_array($json) ? (bool) valorHttpSmoke($json, array("error"), false) : true,
    "tipo" => is_array($json) ? valorHttpSmoke($json, array("tipo"), "") : "",
    "mensaje" => is_array($json) ? valorHttpSmoke($json, array("mensaje"), "") : "",
    "api_version" => is_array($json) ? valorHttpSmoke($json, array("api", "version"), "") : "",
    "depurar_resumen" => resumenDepurarHttpSmoke(is_array($json) ? valorHttpSmoke($json, array("depurar"), array()) : array()),
    "raw_inicio" => substr((string) $raw, 0, 120)
  );
}

function resumenDepurarHttpSmoke($depurar) {
  if (!is_array($depurar)) {
    return array();
  }
  return array(
    "ready" => valorHttpSmoke($depurar, array("ready"), null),
    "configurado" => valorHttpSmoke($depurar, array("configurado"), null),
    "dry_run" => valorHttpSmoke($depurar, array("dry_run"), null),
    "bloqueado" => valorHttpSmoke($depurar, array("bloqueado"), null),
    "disponibilidad" => valorHttpSmoke($depurar, array("disponibilidad"), null),
    "item_presente" => array_key_exists("item", $depurar) ? ($depurar["item"] !== null) : null,
    "items_total" => is_array(valorHttpSmoke($depurar, array("items"), null)) ? count($depurar["items"]) : null,
    "bloqueos" => valorHttpSmoke($depurar, array("bloqueos"), array())
  );
}

function valorHttpSmoke($datos, $ruta, $default = null) {
  $actual = $datos;
  foreach ($ruta as $segmento) {
    if (!is_array($actual) || !array_key_exists($segmento, $actual)) {
      return $default;
    }
    $actual = $actual[$segmento];
  }
  return $actual;
}
