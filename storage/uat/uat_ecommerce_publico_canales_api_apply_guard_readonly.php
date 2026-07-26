<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-26.
 * Proposito: verificar que el apply de canales API ecommerce queda bloqueado sin token/respaldo.
 * Impacto: reduce riesgo de ejecutar DDL por accidente antes de autorizacion humana.
 * Contrato: read-only; no ejecuta apply valido, no crea tablas y no escribe BD.
 */

$script = __DIR__ . "/uat_ecommerce_publico_canales_api_schema_apply_authorized.php";
$cmd = escapeshellarg(PHP_BINARY) . " " . escapeshellarg($script);
$salida = shell_exec($cmd);
$json = json_decode((string) $salida, true);

$bloqueos = array();
if (!is_array($json)) {
  $bloqueos[] = "respuesta_no_json";
}
if (is_array($json) && !empty($json["ok"])) {
  $bloqueos[] = "apply_no_bloqueado_sin_token";
}
if (is_array($json) && (string) ($json["modo"] ?? "") !== "bloqueado") {
  $bloqueos[] = "modo_no_bloqueado";
}

echo json_encode(array(
  "ok" => empty($bloqueos),
  "modo" => "read-only",
  "mensaje" => empty($bloqueos) ? "Apply canales API bloquea correctamente sin token/respaldo" : "Revisar candado de apply canales API",
  "script_validado" => "storage/uat/uat_ecommerce_publico_canales_api_schema_apply_authorized.php",
  "respuesta_apply_sin_parametros" => $json,
  "bloqueos" => $bloqueos,
  "guardrails" => array(
    "no_ejecuta_apply_valido" => true,
    "no_escribe_bd" => true,
    "no_genera_secretos" => true,
    "no_activa_partners" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
