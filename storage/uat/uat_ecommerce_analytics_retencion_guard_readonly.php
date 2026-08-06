<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-08-05.
 * Proposito: validar plan y bloqueo de retencion Ecommerce / Analytics.
 * Impacto: prepara purga futura de datos crudos anonimos sin borrar nada en Fase 1.
 * Contrato: read-only; usa token invalido a proposito.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/EcommerceAnalyticsErp.php";

$analytics = new EcommerceAnalyticsErp();
$datos = array("dias_retencion" => 180);
$plan = $analytics->retencionPlanInterno($datos);
$purga = $analytics->purgarRetencionAutorizada($datos, array("autorizar" => "TOKEN_INVALIDO_READONLY"));

$ok = !empty($plan["depurar"]["no_escribe_bd"])
  && !empty($plan["depurar"]["conserva_resumen_diario"])
  && !empty($purga["error"])
  && !empty($purga["depurar"]["bloqueado"])
  && !empty($purga["depurar"]["no_escribe_bd"])
  && (($purga["depurar"]["token_requerido"] ?? "") === "ECOMMERCE_ANALYTICS_RETENCION");

echo json_encode(array(
  "ok" => $ok,
  "modo" => "read-only",
  "senal_retencion" => $ok ? "purga_bloqueada_correctamente_sin_token" : "revisar_guard_retencion",
  "plan" => array(
    "dias_retencion" => $plan["depurar"]["dias_retencion"] ?? null,
    "fecha_corte_exclusiva" => $plan["depurar"]["fecha_corte_exclusiva"] ?? null,
    "faltantes" => $plan["depurar"]["faltantes"] ?? array(),
    "token_requerido" => $plan["depurar"]["token_requerido"] ?? "",
    "conserva_resumen_diario" => $plan["depurar"]["conserva_resumen_diario"] ?? false
  ),
  "purga_bloqueada" => $purga["depurar"] ?? array(),
  "guardrails" => array(
    "no_escribe_bd" => true,
    "no_delete_real" => true,
    "no_token_real_en_uat" => true,
    "conserva_resumen_diario" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
