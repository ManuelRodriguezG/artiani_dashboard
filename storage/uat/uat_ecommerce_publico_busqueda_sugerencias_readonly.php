<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-31.
 * Proposito: validar sugerencias publicas de busqueda ecommerce.
 * Impacto: protege buscador frontend con productos, taxonomia y guardrails sin registrar busquedas.
 * Contrato: read-only; no escribe BD, no registra busqueda, no expone stock exacto.
 */

$opciones = getopt("", array("base::", "q::", "limite::"));
$base = isset($opciones["base"]) ? rtrim(trim((string) $opciones["base"]), "/") : "http://panel.com.local";
$q = isset($opciones["q"]) ? trim((string) $opciones["q"]) : "filtro";
$limite = isset($opciones["limite"]) ? max(1, min(12, intval($opciones["limite"]))) : 4;

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/EcommerceCatalogoPublico.php";

$api = new EcommerceCatalogoPublico();
$sugerencias = $api->busquedaSugerenciasPublicas(array("q" => $q, "limite" => $limite));
$http = requestBusquedaSugerencias($base . "/ecommercePublico/busqueda_sugerencias?q=" . rawurlencode($q) . "&limite=" . intval($limite));
$dep = valorBusquedaSugerencias($sugerencias, array("depurar"), array());

$bloqueos = array();
if (!empty($sugerencias["error"])) {
  $bloqueos[] = "modelo_sugerencias_error";
}
if (intval(valorBusquedaSugerencias($dep, array("resumen", "total_sugerencias"), 0)) <= 0) {
  $bloqueos[] = "sin_sugerencias";
}
if (intval(valorBusquedaSugerencias($dep, array("resumen", "productos"), 0)) <= 0) {
  $bloqueos[] = "sin_productos_sugeridos";
}
if (empty(valorBusquedaSugerencias($dep, array("guardrails", "no_registra_busqueda"), false))) {
  $bloqueos[] = "guardrail_no_registra_busqueda_faltante";
}
if (empty(valorBusquedaSugerencias($dep, array("guardrails", "no_stock_exacto"), false))) {
  $bloqueos[] = "guardrail_no_stock_exacto_faltante";
}
if (empty($http["json_valido"]) || $http["tipo"] !== "success") {
  $bloqueos[] = "http_sugerencias_no_success";
}

$ok = empty($bloqueos);
echo json_encode(array(
  "ok" => $ok,
  "modo" => "read-only",
  "senal_frontend" => $ok ? "busqueda_sugerencias_lista" : "busqueda_sugerencias_incompleta",
  "base_url" => $base,
  "q" => $q,
  "bloqueos" => array_values(array_unique($bloqueos)),
  "resumen" => valorBusquedaSugerencias($dep, array("resumen"), array()),
  "primer_producto" => valorBusquedaSugerencias($dep, array("grupos", "productos", 0), null),
  "http" => $http,
  "guardrails" => array(
    "no_escribe_bd" => true,
    "no_registra_busqueda" => true,
    "solo_publicados" => true,
    "no_stock_exacto" => true,
    "no_expone_costos" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function requestBusquedaSugerencias($url) {
  $context = stream_context_create(array(
    "http" => array(
      "method" => "GET",
      "header" => "Accept: application/json\r\n",
      "ignore_errors" => true,
      "timeout" => 10
    )
  ));
  $raw = @file_get_contents($url, false, $context);
  $json = json_decode((string) $raw, true);
  return array(
    "url" => $url,
    "json_valido" => is_array($json),
    "tipo" => is_array($json) ? valorBusquedaSugerencias($json, array("tipo"), "") : "",
    "mensaje" => is_array($json) ? valorBusquedaSugerencias($json, array("mensaje"), "") : "",
    "raw_inicio" => substr((string) $raw, 0, 80)
  );
}

function valorBusquedaSugerencias($datos, $ruta, $default = null) {
  $actual = $datos;
  foreach ($ruta as $segmento) {
    if (!is_array($actual) || !array_key_exists($segmento, $actual)) {
      return $default;
    }
    $actual = $actual[$segmento];
  }
  return $actual;
}
