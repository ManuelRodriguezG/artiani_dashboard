<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-08-05.
 * Proposito: validar plan y bloqueo del resumen diario Ecommerce / Analytics.
 * Impacto: prepara agregados para dashboard sin activar jobs ni escribir BD.
 * Contrato: read-only; usa token invalido a proposito.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/EcommerceAnalyticsErp.php";

$analytics = new EcommerceAnalyticsErp();
$datos = array("desde" => date("Y-m-d", strtotime("-2 days")), "hasta" => date("Y-m-d"));
$plan = $analytics->resumenDiarioPlanInterno($datos);
$recalculo = $analytics->recalcularResumenDiarioAutorizado($datos, array("autorizar" => "TOKEN_INVALIDO_READONLY"));

$ok = !empty($plan["depurar"]["no_escribe_bd"])
  && !empty($recalculo["error"])
  && !empty($recalculo["depurar"]["bloqueado"])
  && !empty($recalculo["depurar"]["no_escribe_bd"])
  && (($recalculo["depurar"]["token_requerido"] ?? "") === "ECOMMERCE_ANALYTICS_RESUMEN_DIARIO");

echo json_encode(array(
  "ok" => $ok,
  "modo" => "read-only",
  "senal_resumen_diario" => $ok ? "recalculo_bloqueado_correctamente_sin_token" : "revisar_guard_resumen_diario",
  "plan" => array(
    "rango" => $plan["depurar"]["rango"] ?? array(),
    "faltantes" => $plan["depurar"]["faltantes"] ?? array(),
    "token_requerido" => $plan["depurar"]["token_requerido"] ?? ""
  ),
  "recalculo_bloqueado" => $recalculo["depurar"] ?? array(),
  "guardrails" => array(
    "no_escribe_bd" => true,
    "no_jobs_activos" => true,
    "no_token_real_en_uat" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
