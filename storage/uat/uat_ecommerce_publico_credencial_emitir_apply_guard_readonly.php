<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-26.
 * Proposito: verificar que la emision de credenciales API queda bloqueada sin token/respaldo.
 * Impacto: evita generar secretos por accidente.
 * Contrato: read-only; no ejecuta emision valida y no escribe BD.
 */

$script = __DIR__ . "/uat_ecommerce_publico_credencial_emitir_apply_authorized.php";
$cmd = escapeshellarg(PHP_BINARY) . " " . escapeshellarg($script);
$salida = shell_exec($cmd);
$json = json_decode((string) $salida, true);

$bloqueos = array();
if (!is_array($json)) {
  $bloqueos[] = "respuesta_no_json";
}
if (is_array($json) && !empty($json["ok"])) {
  $bloqueos[] = "emision_no_bloqueada_sin_token";
}
if (is_array($json) && (string) ($json["modo"] ?? "") !== "bloqueado") {
  $bloqueos[] = "modo_no_bloqueado";
}

echo json_encode(array(
  "ok" => empty($bloqueos),
  "modo" => "read-only",
  "mensaje" => empty($bloqueos) ? "Emision de credencial bloquea correctamente sin token/respaldo" : "Revisar candado emision credencial",
  "script_validado" => "storage/uat/uat_ecommerce_publico_credencial_emitir_apply_authorized.php",
  "respuesta_emision_sin_parametros" => $json,
  "bloqueos" => $bloqueos,
  "guardrails" => array(
    "no_ejecuta_emision_valida" => true,
    "no_escribe_bd" => true,
    "no_genera_secretos" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
