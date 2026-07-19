<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-15.
 * Proposito: validar preflight CORS del API ecommerce publico para un origen concreto.
 * Impacto: ayuda al frontend externo a distinguir API funcional de CORS pendiente o mal configurado.
 * Contrato: read-only; no escribe BD, no registra cotizaciones y no toca inventario.
 */

$opciones = getopt("", array("base::", "origin::"));
$base = isset($opciones["base"]) ? rtrim(trim((string) $opciones["base"]), "/") : "http://panel.com.local";
$origin = isset($opciones["origin"]) ? trim((string) $opciones["origin"]) : "http://localhost:5173";

$rutas = array(
  "estado" => "/ecommercePublico/estado",
  "catalogo" => "/ecommercePublico/catalogo",
  "cotizacion_dryrun" => "/ecommercePublico/cotizacion_dryrun"
);

$pruebas = array();
foreach ($rutas as $nombre => $ruta) {
  $pruebas[$nombre] = requestCorsPreflight($base . $ruta, $origin);
}

$corsAbierto = false;
$corsWildcard = false;
foreach ($pruebas as $prueba) {
  if ($prueba["access_control_allow_origin"] !== "") {
    $corsAbierto = true;
  }
  if ($prueba["access_control_allow_origin"] === "*") {
    $corsWildcard = true;
  }
}

echo json_encode(array(
  "ok" => true,
  "modo" => "read-only",
  "base_url" => $base,
  "origin_probado" => $origin,
  "cors_abierto_para_origin" => $corsAbierto,
  "cors_sin_wildcard" => !$corsWildcard,
  "estado_esperado_actual" => $corsAbierto ? "abierto_solo_para_origin_configurado" : "cerrado_para_origin_no_configurado",
  "pruebas" => $pruebas,
  "guardrails" => array(
    "no_escribe_bd" => true,
    "no_registra_cotizacion" => true,
    "no_mueve_inventario" => true,
    "cors_no_debe_usar_wildcard" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

function requestCorsPreflight($url, $origin) {
  $context = stream_context_create(array(
    "http" => array(
      "method" => "OPTIONS",
      "header" => "Origin: " . $origin . "\r\nAccess-Control-Request-Method: POST\r\nAccess-Control-Request-Headers: Content-Type\r\nAccept: application/json\r\n",
      "ignore_errors" => true,
      "timeout" => 10
    )
  ));
  $raw = @file_get_contents($url, false, $context);
  $headers = isset($http_response_header) && is_array($http_response_header) ? $http_response_header : array();
  $normalizados = array();
  foreach ($headers as $header) {
    $pos = strpos($header, ":");
    if ($pos !== false) {
      $normalizados[strtolower(trim(substr($header, 0, $pos)))] = trim(substr($header, $pos + 1));
    }
  }
  $json = json_decode((string) $raw, true);
  return array(
    "url" => $url,
    "body_json_no_requerido" => true,
    "body_json_valido_si_existe" => is_array($json),
    "tipo" => is_array($json) ? valorCors($json, array("tipo"), "") : "",
    "mensaje" => is_array($json) ? valorCors($json, array("mensaje"), "") : "",
    "access_control_allow_origin" => isset($normalizados["access-control-allow-origin"]) ? $normalizados["access-control-allow-origin"] : "",
    "access_control_allow_methods" => isset($normalizados["access-control-allow-methods"]) ? $normalizados["access-control-allow-methods"] : "",
    "vary" => isset($normalizados["vary"]) ? $normalizados["vary"] : "",
    "raw_inicio" => substr((string) $raw, 0, 120)
  );
}

function valorCors($datos, $ruta, $default = null) {
  $actual = $datos;
  foreach ($ruta as $segmento) {
    if (!is_array($actual) || !array_key_exists($segmento, $actual)) {
      return $default;
    }
    $actual = $actual[$segmento];
  }
  return $actual;
}
