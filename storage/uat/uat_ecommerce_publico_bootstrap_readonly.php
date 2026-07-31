<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-31.
 * Proposito: validar el paquete inicial `bootstrap` del API ecommerce publico.
 * Impacto: protege el arranque frontend con estado, configuracion, filtros, secciones, politicas y canales.
 * Contrato: read-only; no registra cotizaciones, no descuenta inventario y no expone secretos.
 */

$opciones = getopt("", array("base::", "limite::"));
$base = isset($opciones["base"]) ? rtrim(trim((string) $opciones["base"]), "/") : "http://panel.com.local";
$limite = isset($opciones["limite"]) ? max(1, min(12, intval($opciones["limite"]))) : 3;

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/EcommerceCatalogoPublico.php";

$api = new EcommerceCatalogoPublico();
$bootstrap = $api->bootstrapPublico(array("limite_secciones" => $limite));
$http = requestBootstrap($base . "/ecommercePublico/bootstrap?limite_secciones=" . intval($limite));
$dep = valorBootstrap($bootstrap, array("depurar"), array());

$bloqueos = array();
if (!empty($bootstrap["error"])) {
  $bloqueos[] = "bootstrap_modelo_error";
}
if (empty(valorBootstrap($dep, array("ready"), false))) {
  $bloqueos[] = "bootstrap_no_ready";
}
if (intval(valorBootstrap($dep, array("estado", "publicaciones", "total_publicadas"), 0)) <= 0) {
  $bloqueos[] = "bootstrap_sin_publicaciones";
}
if (count(valorBootstrap($dep, array("secciones", "secciones"), array())) <= 0) {
  $bloqueos[] = "bootstrap_sin_secciones";
}
if (empty(valorBootstrap($dep, array("frontend", "puede_renderizar_catalogo_real"), false))) {
  $bloqueos[] = "frontend_no_puede_renderizar_catalogo_real";
}
if (empty(valorBootstrap($dep, array("guardrails", "no_expone_secretos"), false))) {
  $bloqueos[] = "guardrail_no_expone_secretos_faltante";
}
if (empty($http["json_valido"]) || $http["tipo"] !== "success") {
  $bloqueos[] = "http_bootstrap_no_success";
}

$ok = empty($bloqueos);
echo json_encode(array(
  "ok" => $ok,
  "modo" => "read-only",
  "senal_frontend" => $ok ? "bootstrap_frontend_listo" : "bootstrap_frontend_incompleto",
  "base_url" => $base,
  "bloqueos" => array_values(array_unique($bloqueos)),
  "bootstrap" => array(
    "ready" => valorBootstrap($dep, array("ready"), false),
    "publicadas" => intval(valorBootstrap($dep, array("estado", "publicaciones", "total_publicadas"), 0)),
    "secciones" => count(valorBootstrap($dep, array("secciones", "secciones"), array())),
    "mascotas" => count(valorBootstrap($dep, array("filtros", "mascotas"), array())),
    "necesidades" => count(valorBootstrap($dep, array("filtros", "necesidades"), array())),
    "canales_modo" => valorBootstrap($dep, array("canales", "modo"), "")
  ),
  "http" => $http,
  "guardrails" => array(
    "no_escribe_bd" => true,
    "no_expone_secretos" => true,
    "no_stock_exacto" => true,
    "no_descuenta_inventario" => true,
    "no_registra_cotizacion" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function requestBootstrap($url) {
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
    "tipo" => is_array($json) ? valorBootstrap($json, array("tipo"), "") : "",
    "mensaje" => is_array($json) ? valorBootstrap($json, array("mensaje"), "") : "",
    "raw_inicio" => substr((string) $raw, 0, 80)
  );
}

function valorBootstrap($datos, $ruta, $default = null) {
  $actual = $datos;
  foreach ($ruta as $segmento) {
    if (!is_array($actual) || !array_key_exists($segmento, $actual)) {
      return $default;
    }
    $actual = $actual[$segmento];
  }
  return $actual;
}
