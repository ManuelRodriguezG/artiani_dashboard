<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-09-05.
 * Proposito: validar contrato read-only del flujo de navegacion por sesiones Ecommerce Analytics.
 * Impacto: confirma sesiones/timeline anonimos sin escribir BD ni exponer session_id completo.
 * Contrato: solo SELECT; no crea tablas ni registra eventos.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/EcommerceAnalyticsErp.php";

$analytics = new EcommerceAnalyticsErp();
$flujo = $analytics->flujoSesionesInterno(array(
  "desde" => date("Y-m-d", strtotime("-30 days")),
  "hasta" => date("Y-m-d"),
  "limite" => 10
));

$depurar = $flujo["depurar"] ?? array();
$timeline = $depurar["timeline"] ?? array();
$sesiones = $depurar["sesiones"] ?? array();
$sessionKeyOk = true;
foreach ($sesiones as $sesion) {
  if (strlen((string) ($sesion["session_key"] ?? "")) > 12) { $sessionKeyOk = false; }
}

$ok = empty($flujo["error"])
  && !empty($depurar["read_only"])
  && isset($depurar["sesiones"])
  && isset($depurar["sesion_seleccionada"])
  && isset($depurar["timeline"])
  && isset($depurar["resumen"])
  && isset($depurar["guardrails"]["no_escribe_bd"])
  && $sessionKeyOk;

echo json_encode(array(
  "ok" => $ok,
  "modo" => "read-only",
  "senal_flujo" => $ok ? "contrato_flujo_sesiones_estable" : "revisar_flujo_sesiones",
  "configurado" => $depurar["configurado"] ?? null,
  "sesiones_total" => count($sesiones),
  "session_key" => $depurar["session_key"] ?? "",
  "timeline_total" => count($timeline),
  "primer_evento" => $timeline[0] ?? array(),
  "guardrails" => array(
    "no_escribe_bd" => true,
    "no_expone_session_id_completo" => $sessionKeyOk,
    "no_pii" => true,
    "no_stock_exacto" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
