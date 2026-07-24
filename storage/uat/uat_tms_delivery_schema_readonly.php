<?php

/**
 * IA: Codex GPT-5
 * Fecha: 2026-07-24
 * Proposito: generar plan DDL TMS Delivery sin ejecutar.
 * Impacto: permite revisar tablas erp_tms_* antes de pedir respaldo/autorizacion.
 * Contrato: read-only; no crea tablas, no modifica BD y no toca Ventas/Inventario/Garantias.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/DBSchema.php";
require_once __DIR__ . "/../../app/modelos/TmsEsquema.php";

$modelo = new TmsEsquema();
$auditoria = $modelo->auditarTmsDelivery();
$plan = $modelo->planActualizarTmsDelivery(false);
$depurarPlan = isset($plan["depurar"]["plan"]) && is_array($plan["depurar"]["plan"]) ? $plan["depurar"]["plan"] : array();
$pendientes = 0;

foreach ($depurarPlan as $paso) {
  if (isset($paso["depurar"]["ejecutado"]) && $paso["depurar"]["ejecutado"] === false && isset($paso["depurar"]["sql"])) {
    $pendientes++;
  }
}

echo json_encode(array(
  "ok" => empty($auditoria["error"]) && empty($plan["error"]),
  "modo" => "read-only",
  "mensaje_auditoria" => isset($auditoria["mensaje"]) ? $auditoria["mensaje"] : "",
  "mensaje_plan" => isset($plan["mensaje"]) ? $plan["mensaje"] : "",
  "ddl_total" => count($depurarPlan),
  "ddl_pendientes" => $pendientes,
  "token_apply" => "TMS_DELIVERY_DDL_BASE",
  "auditoria" => $auditoria,
  "plan" => $plan
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
