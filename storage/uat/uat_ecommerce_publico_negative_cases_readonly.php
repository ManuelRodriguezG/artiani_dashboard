<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-15.
 * Proposito: validar casos negativos controlados del API ecommerce publico.
 * Impacto: asegura que el frontend reciba JSON estable ante metodos, parametros o carritos invalidos.
 * Contrato: read-only; no escribe BD, no registra cotizaciones, no crea pedidos y no toca inventario.
 */

$opciones = getopt("", array("base::"));
$base = isset($opciones["base"]) ? rtrim(trim((string) $opciones["base"]), "/") : "http://panel.com.local";

$casos = array(
  "dryrun_get_metodo_incorrecto" => requestNegative($base . "/ecommercePublico/cotizacion_dryrun", "GET"),
  "dryrun_post_items_vacios" => requestNegative($base . "/ecommercePublico/cotizacion_dryrun", "POST", array("items" => array())),
  "dryrun_post_json_vacio" => requestNegative($base . "/ecommercePublico/cotizacion_dryrun", "POST", array()),
  "preflight_get_metodo_incorrecto" => requestNegative($base . "/ecommercePublico/cotizacion_preflight", "GET"),
  "preflight_post_items_vacios" => requestNegative($base . "/ecommercePublico/cotizacion_preflight", "POST", array("items" => array())),
  "disponibilidad_sin_parametros" => requestNegative($base . "/ecommercePublico/disponibilidad", "GET"),
  "producto_slug_inexistente" => requestNegative($base . "/ecommercePublico/producto/slug-de-prueba-no-publicado", "GET"),
  "catalogo_limite_excesivo" => requestNegative($base . "/ecommercePublico/catalogo?limite=999", "GET"),
  "cotizacion_registrar_bloqueado" => requestNegative($base . "/ecommercePublico/cotizacion_registrar", "POST", array(
    "items" => array(array("id_publicacion" => 1, "cantidad" => 1))
  )),
  "facturacion_get_metodo_incorrecto" => requestNegative($base . "/ecommercePublico/facturacion_solicitar", "GET"),
  "facturacion_post_folio_vacio" => requestNegative($base . "/ecommercePublico/facturacion_solicitar", "POST", array("folio_compra" => "")),
  "evento_get_metodo_incorrecto" => requestNegative($base . "/ecommercePublico/evento_navegacion", "GET"),
  "evento_post_metadata_sensible" => requestNegative($base . "/ecommercePublico/evento_navegacion", "POST", array(
    "session_id" => "sess_neg_123",
    "tipo_evento" => "page_view",
    "metadata" => array("correo" => "cliente@example.com")
  )),
  "busqueda_get_metodo_incorrecto" => requestNegative($base . "/ecommercePublico/busqueda_registrar", "GET"),
  "busqueda_post_query_vacia" => requestNegative($base . "/ecommercePublico/busqueda_registrar", "POST", array(
    "session_id" => "sess_neg_123",
    "query" => ""
  ))
);

$bloqueos = array();
foreach ($casos as $nombre => $caso) {
  if (!$caso["json_valido"]) {
    $bloqueos[] = $nombre . "_no_responde_json";
  }
  if ($caso["api_version"] !== "fase1-2026-07-12") {
    $bloqueos[] = $nombre . "_version_invalida";
  }
}

if (empty($casos["dryrun_get_metodo_incorrecto"]["error"])) {
  $bloqueos[] = "dryrun_get_debe_ser_error_funcional";
}
if (empty($casos["preflight_get_metodo_incorrecto"]["error"])) {
  $bloqueos[] = "preflight_get_debe_ser_error_funcional";
}
foreach (array("facturacion_get_metodo_incorrecto", "evento_get_metodo_incorrecto", "busqueda_get_metodo_incorrecto") as $casoGet) {
  if (empty($casos[$casoGet]["error"])) {
    $bloqueos[] = $casoGet . "_debe_ser_error_funcional";
  }
}
if (valorNegative($casos["dryrun_post_items_vacios"], array("depurar", "configurado"), true) !== false && empty($casos["dryrun_post_items_vacios"]["error"])) {
  $bloqueos[] = "dryrun_items_vacios_debe_ser_error_funcional";
}
if (valorNegative($casos["preflight_post_items_vacios"], array("depurar", "preflight"), false) !== true) {
  $bloqueos[] = "preflight_items_vacios_debe_responder_preflight";
}
if (empty($casos["cotizacion_registrar_bloqueado"]["depurar"]["bloqueado"])) {
  $bloqueos[] = "cotizacion_registrar_debe_permanecer_bloqueado";
}
if (valorNegative($casos["facturacion_post_folio_vacio"], array("depurar", "preflight"), false) !== true || !in_array("folio_compra_requerido", valorNegative($casos["facturacion_post_folio_vacio"], array("depurar", "bloqueos"), array()), true)) {
  $bloqueos[] = "facturacion_folio_vacio_debe_bloquear_preflight";
}
if (valorNegative($casos["evento_post_metadata_sensible"], array("depurar", "preflight"), false) !== true || !in_array("metadata_no_debe_incluir_datos_personales", valorNegative($casos["evento_post_metadata_sensible"], array("depurar", "bloqueos"), array()), true)) {
  $bloqueos[] = "evento_metadata_sensible_debe_bloquear_preflight";
}
if (valorNegative($casos["busqueda_post_query_vacia"], array("depurar", "preflight"), false) !== true || !in_array("query_requerido", valorNegative($casos["busqueda_post_query_vacia"], array("depurar", "bloqueos"), array()), true)) {
  $bloqueos[] = "busqueda_query_vacia_debe_bloquear_preflight";
}
if (valorNegative($casos["disponibilidad_sin_parametros"], array("depurar", "disponibilidad"), "") !== "consultar_disponibilidad") {
  $bloqueos[] = "disponibilidad_sin_parametros_debe_consultar";
}

echo json_encode(array(
  "ok" => empty($bloqueos),
  "modo" => "read-only",
  "base_url" => $base,
  "casos" => $casos,
  "bloqueos" => $bloqueos,
  "guardrails" => array(
    "no_escribe_bd" => true,
    "no_registra_cotizacion" => true,
    "no_crea_pedido" => true,
    "no_descuenta_inventario" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function requestNegative($url, $method = "GET", $body = null) {
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
    "error" => is_array($json) ? (bool) valorNegative($json, array("error"), false) : true,
    "tipo" => is_array($json) ? valorNegative($json, array("tipo"), "") : "",
    "mensaje" => is_array($json) ? valorNegative($json, array("mensaje"), "") : "",
    "api_version" => is_array($json) ? valorNegative($json, array("api", "version"), "") : "",
    "depurar" => is_array($json) ? valorNegative($json, array("depurar"), array()) : array(),
    "raw_inicio" => substr((string) $raw, 0, 120)
  );
}

function valorNegative($datos, $ruta, $default = null) {
  $actual = $datos;
  foreach ($ruta as $segmento) {
    if (!is_array($actual) || !array_key_exists($segmento, $actual)) {
      return $default;
    }
    $actual = $actual[$segmento];
  }
  return $actual;
}
