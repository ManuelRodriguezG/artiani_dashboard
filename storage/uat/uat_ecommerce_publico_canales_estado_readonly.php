<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-31.
 * Proposito: validar el estado publico seguro de canales/API ecommerce.
 * Impacto: permite que frontend/partners lean readiness multi-canal sin secretos ni autenticacion activa.
 * Contrato: read-only; no crea canales, no genera credenciales, no modifica CORS y no escribe logs.
 */

$opciones = getopt("", array("base::"));
$base = isset($opciones["base"]) ? rtrim(trim((string) $opciones["base"]), "/") : "http://panel.com.local";

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/EcommerceCatalogoPublico.php";

$api = new EcommerceCatalogoPublico();
$estado = $api->canalesApiEstadoPublico();
$http = requestCanalesEstado($base . "/ecommercePublico/canales_estado");
$dep = valorCanalesEstado($estado, array("depurar"), array());

$bloqueos = array();
if (!empty($estado["error"])) {
  $bloqueos[] = "modelo_canales_estado_error";
}
if (trim((string) valorCanalesEstado($dep, array("modo"), "")) === "") {
  $bloqueos[] = "modo_no_informado";
}
if (!is_array(valorCanalesEstado($dep, array("tablas"), null))) {
  $bloqueos[] = "tablas_no_informadas";
}
if (empty(valorCanalesEstado($dep, array("guardrails", "no_genera_secretos"), false))) {
  $bloqueos[] = "guardrail_no_genera_secretos_faltante";
}
if (empty(valorCanalesEstado($dep, array("guardrails", "no_expone_api_secret"), false))) {
  $bloqueos[] = "guardrail_no_expone_api_secret_faltante";
}
if (!empty(valorCanalesEstado($dep, array("guardrails", "no_activa_auth_obligatoria"), true)) !== true) {
  $bloqueos[] = "guardrail_auth_obligatoria_invalido";
}
if (empty($http["json_valido"])) {
  $bloqueos[] = "http_canales_estado_no_json";
}
if (valorCanalesEstado($http, array("tipo"), "") === "") {
  $bloqueos[] = "http_tipo_no_informado";
}

$ok = empty($bloqueos);
echo json_encode(array(
  "ok" => $ok,
  "modo" => "read-only",
  "senal_frontend" => $ok ? "canales_estado_seguro" : "canales_estado_incompleto",
  "base_url" => $base,
  "bloqueos" => array_values(array_unique($bloqueos)),
  "estado" => array(
    "tipo" => valorCanalesEstado($estado, array("tipo"), ""),
    "configurado" => valorCanalesEstado($dep, array("configurado"), false),
    "modo" => valorCanalesEstado($dep, array("modo"), ""),
    "bloqueos_total" => count(valorCanalesEstado($dep, array("bloqueos"), array())),
    "canales_total" => intval(valorCanalesEstado($dep, array("canales", "total"), 0)),
    "credenciales_activas" => intval(valorCanalesEstado($dep, array("credenciales", "activas"), 0))
  ),
  "http" => $http,
  "guardrails" => array(
    "no_escribe_bd" => true,
    "no_genera_credenciales" => true,
    "no_expone_secretos" => true,
    "no_activa_auth_obligatoria" => true,
    "no_modifica_cors" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function requestCanalesEstado($url) {
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
    "tipo" => is_array($json) ? valorCanalesEstado($json, array("tipo"), "") : "",
    "mensaje" => is_array($json) ? valorCanalesEstado($json, array("mensaje"), "") : "",
    "raw_inicio" => substr((string) $raw, 0, 80)
  );
}

function valorCanalesEstado($datos, $ruta, $default = null) {
  $actual = $datos;
  foreach ($ruta as $segmento) {
    if (!is_array($actual) || !array_key_exists($segmento, $actual)) {
      return $default;
    }
    $actual = $actual[$segmento];
  }
  return $actual;
}
