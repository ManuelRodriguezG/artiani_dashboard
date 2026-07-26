<?php

/**
 * IA: Codex GPT-5
 * Fecha: 2026-07-25
 * Proposito: generar plan DDL Proyectos sin ejecutar.
 * Impacto: permite revisar tablas erp_proyecto* antes de pedir respaldo/autorizacion.
 * Contrato: read-only; no crea tablas, no crea proyectos, no crea tareas ni toca otros modulos.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/core/DBSchema.php";
require_once __DIR__ . "/../../app/modelos/ProyectosEsquema.php";

$db = (new class extends CRUD {
  public function conexion() {
    return $this->getConexion();
  }
})->conexion();

$modelo = new ProyectosEsquema();
$auditoria = $modelo->auditarProyectosErp();
$plan = $modelo->planActualizarProyectosErp(false);
$depurarPlan = isset($plan["depurar"]["plan"]) && is_array($plan["depurar"]["plan"]) ? $plan["depurar"]["plan"] : array();
$pendientes = 0;

foreach ($depurarPlan as $paso) {
  if (isset($paso["depurar"]["ejecutado"]) && $paso["depurar"]["ejecutado"] === false && isset($paso["depurar"]["sql"])) {
    $pendientes++;
  }
}

echo json_encode(array(
  "ok" => (bool) $db && empty($auditoria["error"]) && empty($plan["error"]),
  "modo" => "read-only",
  "conexion_mysql" => (bool) $db,
  "mensaje_auditoria" => isset($auditoria["mensaje"]) ? $auditoria["mensaje"] : "",
  "mensaje_plan" => isset($plan["mensaje"]) ? $plan["mensaje"] : "",
  "ddl_total" => count($depurarPlan),
  "ddl_pendientes" => $pendientes,
  "token_apply" => "PROYECTOS_DDL_BASE",
  "auditoria" => $auditoria,
  "plan" => $plan,
  "regla" => "El plan no precarga tareas ni avances de otros modulos."
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
