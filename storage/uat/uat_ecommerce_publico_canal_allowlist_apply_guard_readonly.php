<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-26.
 * Proposito: verificar que el apply allowlist por canal queda bloqueado sin token/respaldo.
 * Impacto: protege productos de partner de asignaciones accidentales.
 * Contrato: read-only; no ejecuta apply valido y no escribe BD.
 */

$script = __DIR__ . "/uat_ecommerce_publico_canal_allowlist_apply_authorized.php";
$cmd = escapeshellarg(PHP_BINARY) . " " . escapeshellarg($script);
$salida = shell_exec($cmd);
$json = json_decode((string) $salida, true);

$bloqueos = array();
if (!is_array($json)) {
  $bloqueos[] = "respuesta_no_json";
}
if (is_array($json) && !empty($json["ok"])) {
  $bloqueos[] = "allowlist_no_bloqueado_sin_token";
}
if (is_array($json) && (string) ($json["modo"] ?? "") !== "bloqueado") {
  $bloqueos[] = "modo_no_bloqueado";
}

echo json_encode(array(
  "ok" => empty($bloqueos),
  "modo" => "read-only",
  "mensaje" => empty($bloqueos) ? "Allowlist por canal bloquea correctamente sin token/respaldo" : "Revisar candado allowlist por canal",
  "script_validado" => "storage/uat/uat_ecommerce_publico_canal_allowlist_apply_authorized.php",
  "respuesta_allowlist_sin_parametros" => $json,
  "bloqueos" => $bloqueos,
  "guardrails" => array(
    "no_ejecuta_apply_valido" => true,
    "no_escribe_bd" => true,
    "no_activa_partner" => true,
    "no_genera_secretos" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
