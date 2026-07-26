<?php

/**
 * IA: Codex GPT-5
 * Fecha: 2026-07-25
 * Proposito: consolidar checklist de activacion TMS y determinar el siguiente token permitido.
 * Impacto: TMS Delivery; evita saltar de permisos a DDL/UAT sin validaciones intermedias.
 * Contrato: read-only; no crea respaldos, no sincroniza permisos, no ejecuta DDL y no crea servicios.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/core/DBSchema.php";
require_once __DIR__ . "/../../app/modelos/TmsEsquema.php";

$db = (new class extends CRUD {
  public function conexion() {
    return $this->getConexion();
  }
})->conexion();

$permisos = array("tms.ver", "tms.crear", "tms.programar", "tms.operar", "tms.evidencias", "tms.costos", "tms.autorizar", "tms.reportes");
$permisosPendientes = auditar_permisos_pendientes($db, $permisos);
$modeloEsquema = new TmsEsquema();
$auditoria = $modeloEsquema->auditarTmsDelivery();
$schemaPendiente = isset($auditoria["depurar"]["tiene_pendientes"]) ? (bool) $auditoria["depurar"]["tiene_pendientes"] : true;
$tablasTms = isset($auditoria["depurar"]["tablas"]) ? $auditoria["depurar"]["tablas"] : array();
$tablasExistentes = contar_tablas_existentes($db, $tablasTms);
$serviciosTms = contar_filas_tabla($db, "erp_tms_servicios");

$pasos = array(
  array(
    "paso" => "permisos_tms",
    "estado" => count($permisosPendientes) === 0 ? "listo" : "pendiente",
    "token" => "TMS_PERMISOS_BASE",
    "puede_ejecutarse_ahora" => (bool) $db && count($permisosPendientes) > 0,
    "pendientes" => $permisosPendientes
  ),
  array(
    "paso" => "schema_tms",
    "estado" => !$schemaPendiente ? "listo" : "pendiente",
    "token" => "TMS_DELIVERY_DDL_BASE",
    "puede_ejecutarse_ahora" => (bool) $db && count($permisosPendientes) === 0 && $schemaPendiente,
    "pendientes" => isset($auditoria["depurar"]["pendientes"]) ? $auditoria["depurar"]["pendientes"] : array()
  ),
  array(
    "paso" => "uat_manual_tms",
    "estado" => $serviciosTms > 0 ? "listo" : ((!$schemaPendiente && count($permisosPendientes) === 0) ? "preparado" : "bloqueado_por_dependencias"),
    "token" => "TMS_UAT_SERVICIO_MANUAL",
    "puede_ejecutarse_ahora" => (bool) $db && count($permisosPendientes) === 0 && !$schemaPendiente && $serviciosTms === 0,
    "pendientes" => array_filter(array(
      count($permisosPendientes) === 0 ? null : "permisos_tms",
      !$schemaPendiente ? null : "schema_tms"
    )),
    "servicios_tms" => $serviciosTms
  )
);

$siguiente = siguiente_paso($pasos, $db);

echo json_encode(array(
  "ok" => (bool) $db,
  "modo" => "read-only",
  "estado" => $siguiente["estado_general"],
  "bd" => array(
    "conexion" => (bool) $db,
    "permisos_tms_pendientes" => count($permisosPendientes),
    "schema_tms_pendiente" => $schemaPendiente,
    "tablas_tms_existentes" => $tablasExistentes,
    "servicios_tms" => $serviciosTms
  ),
  "siguiente_token_permitido" => $siguiente["token"],
  "siguiente_accion" => $siguiente["accion"],
  "pasos" => $pasos,
  "reglas" => array(
    "no_saltar_a_ddl_sin_permisos_validados" => true,
    "no_ejecutar_uat_manual_sin_permisos_y_schema" => true,
    "no_integrar_pos_todavia" => true,
    "tms_no_modifica_ventas" => true,
    "tms_no_decide_garantias" => true,
    "tms_no_mueve_inventario" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

function auditar_permisos_pendientes($db, $permisos) {
  if (!$db) {
    return $permisos;
  }
  $pendientes = array();
  $stmt = $db->prepare("SELECT permiso FROM sys_permisos WHERE permiso=:permiso LIMIT 1");
  foreach ($permisos as $permiso) {
    $stmt->execute(array(":permiso" => $permiso));
    if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
      $pendientes[] = $permiso;
    }
  }
  return $pendientes;
}

function contar_tablas_existentes($db, $tablas) {
  if (!$db) {
    return 0;
  }
  $total = 0;
  $stmt = $db->prepare("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=:base AND TABLE_NAME=:tabla LIMIT 1");
  foreach ($tablas as $tabla) {
    $stmt->execute(array(":base" => MYSQLBASE, ":tabla" => $tabla));
    if ($stmt->fetch(PDO::FETCH_ASSOC)) {
      $total++;
    }
  }
  return $total;
}

function contar_filas_tabla($db, $tabla) {
  if (!$db) {
    return 0;
  }
  $stmt = $db->prepare("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=:base AND TABLE_NAME=:tabla LIMIT 1");
  $stmt->execute(array(":base" => MYSQLBASE, ":tabla" => $tabla));
  if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
    return 0;
  }
  $stmtConteo = $db->query("SELECT COUNT(*) total FROM `" . str_replace("`", "", $tabla) . "`");
  $fila = $stmtConteo->fetch(PDO::FETCH_ASSOC);
  return $fila ? intval($fila["total"]) : 0;
}

function siguiente_paso($pasos, $db) {
  if (!$db) {
    return array("estado_general" => "bloqueado_sin_conexion_mysql", "token" => null, "accion" => "Levantar MySQL antes de activar TMS.");
  }
  foreach ($pasos as $paso) {
    if (!empty($paso["puede_ejecutarse_ahora"])) {
      return array(
        "estado_general" => "listo_para_" . $paso["paso"],
        "token" => $paso["token"],
        "accion" => "Solicitar respaldo externo y autorizacion " . $paso["token"] . "."
      );
    }
  }
  return array("estado_general" => "activacion_base_completa", "token" => null, "accion" => "Validar UI TMS y planear integracion POS en tarea separada.");
}
