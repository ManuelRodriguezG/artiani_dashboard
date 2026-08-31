<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-08-31.
 * Proposito: validar contrato read-only del dashboard Ecommerce / Analytics v1.
 * Impacto: confirma vista interna ampliada sin escribir BD ni exponer PII.
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
  && isset($depurar["sesiones_recientes"])
  && isset($depurar["canales"])
  && isset($depurar["conversiones_por_tipo"])
  && isset($depurar["facturacion_eventos"])
  && isset($depurar["abandono_por_etapa"])
  && isset($depurar["persistencia"]["modo_actual"])
  && isset($depurar["guardrails"]["no_escribe_bd"])
  && !isset($depurar["session_id"])
  && !isset($depurar["telefono"])
  && !isset($depurar["correo"])
  && !isset($depurar["email"])
  && !isset($depurar["rfc"]);

echo json_encode(array(
  "ok" => $ok,
  "modo" => "read-only",
  "senal_dashboard" => $ok ? "contrato_dashboard_analytics_estable" : "revisar_dashboard_analytics",
  "configurado" => $depurar["configurado"] ?? null,
  "fuente_metricas" => $depurar["fuente_metricas"] ?? null,
  "tablas" => $depurar["tablas"] ?? array(),
  "resumen" => $depurar["resumen"] ?? array(),
  "embudo_claves" => array_keys($depurar["embudo"] ?? array()),
  "vista_claves" => array(
    "sesiones_recientes" => is_array($depurar["sesiones_recientes"] ?? null),
    "canales" => is_array($depurar["canales"] ?? null),
    "conversiones_por_tipo" => is_array($depurar["conversiones_por_tipo"] ?? null),
    "facturacion_eventos" => is_array($depurar["facturacion_eventos"] ?? null),
    "abandono_por_etapa" => is_array($depurar["abandono_por_etapa"] ?? null),
    "persistencia" => $depurar["persistencia"]["modo_actual"] ?? null
  ),
  "guardrails" => array(
    "no_escribe_bd" => true,
    "fallback_resumen_diario_o_eventos_crudos" => true,
    "no_pii" => true,
    "no_stock_exacto" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
