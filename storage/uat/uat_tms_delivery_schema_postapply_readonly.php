<?php

/**
 * IA: Codex GPT-5
 * Fecha: 2026-07-24
 * Proposito: verificar esquema TMS despues de aplicar autorizacion TMS_DELIVERY_DDL_BASE.
 * Impacto: TMS Delivery; confirma tablas y lecturas reales sin crear servicios.
 * Contrato: read-only; no inserta, no actualiza, no borra y no toca Ventas/Garantias/Inventario.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/core/DBSchema.php";
require_once __DIR__ . "/../../app/modelos/TmsEsquema.php";
require_once __DIR__ . "/../../app/modelos/TmsDelivery.php";

$detalleCompleto = in_array("--detalle=1", $argv, true);
$modeloEsquema = new TmsEsquema();
$modelo = new TmsDelivery();

$auditoria = $modeloEsquema->auditarTmsDelivery();
$plan = $modeloEsquema->planActualizarTmsDelivery(false);
$listado = $modelo->listarServicios(array("limite" => 5));
$reportes = $modelo->resumenReportes(array());
$dryrun = $modelo->servicioDryRun(array(
  "tipo_servicio" => "entrega_local",
  "prioridad" => "normal",
  "estatus_cobro" => "por_cobrar",
  "solicitado_por_modulo" => "manual",
  "solicitado_por_tipo" => "solicitud_manual",
  "cliente_nombre_snapshot" => "Cliente Post DDL",
  "cliente_contacto_snapshot" => "3312345678",
  "direccion_snapshot" => "Direccion Post DDL",
  "detalle" => json_encode(array(array(
    "descripcion_snapshot" => "Paquete de prueba read-only",
    "cantidad" => 1
  )))
));

$pendientes = isset($auditoria["depurar"]["pendientes"]) ? $auditoria["depurar"]["pendientes"] : array();
$schemaPendiente = isset($auditoria["depurar"]["tiene_pendientes"]) ? (bool) $auditoria["depurar"]["tiene_pendientes"] : true;
$resumenPlan = isset($plan["depurar"]["resumen"]) ? $plan["depurar"]["resumen"] : array();
$listadoSchemaPendiente = isset($listado["depurar"]["schema_pendiente"]) ? (bool) $listado["depurar"]["schema_pendiente"] : true;
$reportesSchemaPendiente = isset($reportes["depurar"]["schema_pendiente"]) ? (bool) $reportes["depurar"]["schema_pendiente"] : true;
$dryrunOk = isset($dryrun["depurar"]["puede_guardar_futuro"]) && $dryrun["depurar"]["puede_guardar_futuro"] === true;

$ok = !$schemaPendiente && !$listadoSchemaPendiente && !$reportesSchemaPendiente && $dryrunOk;
$respuesta = array(
  "ok" => $ok,
  "modo" => "read-only",
  "estado" => $ok ? "schema_tms_listo" : "schema_tms_pendiente",
  "resumen" => array(
    "tablas_total" => isset($auditoria["depurar"]["tablas"]) ? count($auditoria["depurar"]["tablas"]) : 0,
    "pendientes_schema" => count($pendientes),
    "plan_total" => isset($resumenPlan["total"]) ? intval($resumenPlan["total"]) : null,
    "listado_schema_pendiente" => $listadoSchemaPendiente,
    "reportes_schema_pendiente" => $reportesSchemaPendiente,
    "dryrun_valido" => $dryrunOk
  ),
  "reglas" => array(
    "no_crea_servicios" => true,
    "no_modifica_ventas" => true,
    "no_decide_garantias" => true,
    "no_mueve_inventario" => true
  ),
  "siguiente_paso" => $ok
    ? "Ejecutar UAT de guardado manual TMS con autorizacion operativa separada."
    : "Aplicar DDL TMS con token TMS_DELIVERY_DDL_BASE despues de respaldo externo."
);

if ($detalleCompleto) {
  $respuesta["auditoria"] = $auditoria;
  $respuesta["plan"] = $plan;
  $respuesta["listado"] = $listado;
  $respuesta["reportes"] = $reportes;
  $respuesta["dryrun"] = $dryrun;
}

echo json_encode($respuesta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
