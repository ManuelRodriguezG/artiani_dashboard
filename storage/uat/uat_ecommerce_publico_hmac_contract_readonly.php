<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-25.
 * Proposito: mostrar contrato canonico de firma HMAC para API ecommerce multi-canal.
 * Impacto: permite que frontend propio con backend y partners implementen firma sin secretos reales.
 * Contrato: read-only; usa secreto demo, no consulta credenciales, no escribe BD y no activa autenticacion.
 */

$opciones = getopt("", array("metodo::", "path::", "query::", "body::", "timestamp::", "nonce::", "api_key::", "secret_demo::"));
$metodo = strtoupper(trim((string) valorHmac($opciones, "metodo", "POST")));
$path = trim((string) valorHmac($opciones, "path", "/ecommercePublico/cotizacion_dryrun"));
$query = trim((string) valorHmac($opciones, "query", ""));
$body = (string) valorHmac($opciones, "body", '{"items":[{"id_publicacion":1,"cantidad":1}]}');
$timestamp = trim((string) valorHmac($opciones, "timestamp", gmdate("Y-m-d\TH:i:s\Z")));
$nonce = trim((string) valorHmac($opciones, "nonce", "demo-nonce-" . gmdate("YmdHis")));
$apiKey = trim((string) valorHmac($opciones, "api_key", "ak_demo_partner_mayoreo_001"));
$secretDemo = (string) valorHmac($opciones, "secret_demo", "secret_demo_no_usar_en_produccion");

$queryNormalizado = normalizarQueryHmac($query);
$bodyHash = hash("sha256", $body);
$baseCanonica = implode("\n", array(
  $metodo,
  $path,
  $queryNormalizado,
  $bodyHash,
  $timestamp,
  $nonce
));
$firma = hash_hmac("sha256", $baseCanonica, $secretDemo);

echo json_encode(array(
  "ok" => true,
  "modo" => "read-only",
  "advertencia" => "Firma generada con secreto demo. No usar este secreto en produccion.",
  "headers" => array(
    "X-Ecommerce-Api-Key" => $apiKey,
    "X-Ecommerce-Timestamp" => $timestamp,
    "X-Ecommerce-Nonce" => $nonce,
    "X-Ecommerce-Signature" => $firma
  ),
  "request" => array(
    "method" => $metodo,
    "path" => $path,
    "query_string_normalizado" => $queryNormalizado,
    "body_sha256_hex" => $bodyHash
  ),
  "base_canonica" => $baseCanonica,
  "curl_ejemplo" => "curl -X " . $metodo . " \"http://panel.com.local" . $path . ($queryNormalizado !== "" ? "?" . $queryNormalizado : "") . "\" -H \"Content-Type: application/json\" -H \"X-Ecommerce-Api-Key: " . $apiKey . "\" -H \"X-Ecommerce-Timestamp: " . $timestamp . "\" -H \"X-Ecommerce-Nonce: " . $nonce . "\" -H \"X-Ecommerce-Signature: " . $firma . "\" --data '" . $body . "'",
  "guardrails" => array(
    "no_escribe_bd" => true,
    "no_consulta_secretos_reales" => true,
    "no_activa_autenticacion" => true,
    "no_loggear_secretos" => true,
    "secret_debe_vivir_en_backend" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function normalizarQueryHmac($query) {
  $query = ltrim(trim((string) $query), "?");
  if ($query === "") {
    return "";
  }
  parse_str($query, $pares);
  ksort($pares);
  return http_build_query($pares, "", "&", PHP_QUERY_RFC3986);
}

function valorHmac($datos, $clave, $default = null) {
  return array_key_exists($clave, $datos) ? $datos[$clave] : $default;
}
