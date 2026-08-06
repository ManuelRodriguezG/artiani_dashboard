<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-08-05.
 * Proposito: validar contrato read-only del dashboard Ecommerce / Analytics.
 * Impacto: confirma fallback entre resumen diario y eventos crudos sin escribir BD.
 * Contrato: read-only; no crea tablas ni registra eventos.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/EcommerceAnalyticsErp.php";

$analytics = new EcommerceAnalyticsErp();
$dashboard = $analytics->dashboardInterno(array(
  "desde" => date("Y-m-d", strtotime("-7 days")),
  "hasta" => date("Y-m-d"),
  "limite" => 10
));

$depurar = $dashboard["depurar"] ?? array();
$ok = empty($dashboard["error"])
  && !empty($depurar["read_only"])
  && isset($depurar["fuente_metricas"])
  && isset($depurar["resumen"])
  && isset($depurar["embudo"])
  && isset($depurar["visitas_por_dia"])
  && isset($depurar["guardrails"]["no_escribe_bd"]);

echo json_encode(array(
  "ok" => $ok,
  "modo" => "read-only",
  "senal_dashboard" => $ok ? "contrato_dashboard_analytics_estable" : "revisar_dashboard_analytics",
  "configurado" => $depurar["configurado"] ?? null,
  "fuente_metricas" => $depurar["fuente_metricas"] ?? null,
  "tablas" => $depurar["tablas"] ?? array(),
  "resumen" => $depurar["resumen"] ?? array(),
  "embudo_claves" => array_keys($depurar["embudo"] ?? array()),
  "guardrails" => array(
    "no_escribe_bd" => true,
    "fallback_resumen_diario_o_eventos_crudos" => true,
    "no_pii" => true,
    "no_stock_exacto" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
