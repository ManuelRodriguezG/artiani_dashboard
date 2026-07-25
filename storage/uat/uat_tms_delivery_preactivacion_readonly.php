<?php

/**
 * IA: Codex GPT-5
 * Fecha: 2026-07-24
 * Proposito: preparar preactivacion read-only de TMS Delivery antes de respaldos/aplicaciones.
 * Impacto: TMS Delivery; reduce riesgo al ordenar permisos y DDL en autorizaciones separadas.
 * Contrato: read-only; no crea respaldo, no sincroniza permisos, no ejecuta DDL y no toca servicios.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/core/DBSchema.php";
require_once __DIR__ . "/../../app/modelos/TmsEsquema.php";

$root = realpath(__DIR__ . "/../..");
$fecha = date("Ymd_His");
$base = defined("MYSQLBASE") ? MYSQLBASE : "artianilocal";
$backupDir = "C:\\xampp\\panel_db_backups";
$backupPermisos = $backupDir . "\\" . $base . "_panel_" . $fecha . "_antes_tms_permisos.sql";
$backupSchema = $backupDir . "\\" . $base . "_panel_" . $fecha . "_antes_tms_delivery_schema.sql";
$backupUatManual = $backupDir . "\\" . $base . "_panel_" . $fecha . "_antes_tms_uat_manual.sql";

$scripts = array(
  "go_nogo" => "storage/uat/uat_tms_delivery_go_nogo_readonly.php",
  "permisos_readonly" => "storage/uat/uat_tms_delivery_permisos_readonly.php",
  "permisos_postapply" => "storage/uat/uat_tms_delivery_permisos_postapply_readonly.php",
  "schema_readonly" => "storage/uat/uat_tms_delivery_schema_readonly.php",
  "schema_postapply" => "storage/uat/uat_tms_delivery_schema_postapply_readonly.php",
  "dryrun_readonly" => "storage/uat/uat_tms_delivery_dryrun_readonly.php",
  "permisos_apply" => "storage/uat/uat_tms_delivery_permisos_apply_authorized.php",
  "schema_apply" => "storage/uat/uat_tms_delivery_schema_apply_authorized.php",
  "uat_manual_apply" => "storage/uat/uat_tms_delivery_servicio_manual_apply_authorized.php"
);

$checks = array();
foreach ($scripts as $clave => $relativa) {
  $checks["script_" . $clave] = check_item(file_exists($root . "/" . $relativa), $relativa);
}

$modelo = new TmsEsquema();
$auditoria = $modelo->auditarTmsDelivery();
$schemaPendiente = isset($auditoria["depurar"]["tiene_pendientes"]) ? (bool) $auditoria["depurar"]["tiene_pendientes"] : true;

$db = (new class extends CRUD {
  public function conexion() {
    return $this->getConexion();
  }
})->conexion();
$permisos = array("tms.ver", "tms.crear", "tms.programar", "tms.operar", "tms.evidencias", "tms.costos", "tms.autorizar", "tms.reportes");
$permisosPendientes = auditar_permisos_pendientes($db, $permisos);
$fallos = array_values(array_filter($checks, function ($item) {
  return empty($item["ok"]);
}));

echo json_encode(array(
  "ok" => empty($fallos),
  "modo" => "read-only",
  "estado" => empty($fallos) ? "preactivacion_preparada" : "preactivacion_bloqueada",
  "checks_total" => count($checks),
  "checks_fallos" => count($fallos),
  "fallos" => $fallos,
  "bd" => array(
    "conexion" => (bool) $db,
    "permisos_tms_pendientes" => count($permisosPendientes),
    "schema_tms_pendiente" => $schemaPendiente
  ),
  "orden_recomendado" => array(
    "1_respaldo_permisos",
    "2_aplicar_permisos_tms",
    "3_validar_menu_y_acceso_tms",
    "4_respaldo_schema",
    "5_aplicar_ddl_tms",
    "6_validar_schema_tms",
    "7_respaldo_uat_manual",
    "8_ejecutar_uat_manual_tms",
    "9_validar_ui_tms_con_datos_prueba"
  ),
  "comandos_propuestos" => array(
    "respaldo_permisos" => "C:\\xampp\\mysql\\bin\\mysqldump.exe --host=localhost --user=root --result-file=\"" . $backupPermisos . "\" " . $base,
    "aplicar_permisos" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_tms_delivery_permisos_apply_authorized.php --autorizar=TMS_PERMISOS_BASE --respaldo=\"" . $backupPermisos . "\"",
    "respaldo_schema" => "C:\\xampp\\mysql\\bin\\mysqldump.exe --host=localhost --user=root --result-file=\"" . $backupSchema . "\" " . $base,
    "aplicar_schema" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_tms_delivery_schema_apply_authorized.php --autorizar=TMS_DELIVERY_DDL_BASE --respaldo=\"" . $backupSchema . "\"",
    "respaldo_uat_manual" => "C:\\xampp\\mysql\\bin\\mysqldump.exe --host=localhost --user=root --result-file=\"" . $backupUatManual . "\" " . $base,
    "ejecutar_uat_manual" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_tms_delivery_servicio_manual_apply_authorized.php --autorizar=TMS_UAT_SERVICIO_MANUAL --respaldo=\"" . $backupUatManual . "\""
  ),
  "reglas" => array(
    "permisos_y_schema_en_autorizaciones_separadas" => true,
    "uat_manual_en_autorizacion_separada" => true,
    "tms_no_modifica_ventas" => true,
    "tms_no_decide_garantias" => true,
    "tms_no_mueve_inventario" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

function check_item($ok, $detalle) {
  return array("ok" => (bool) $ok, "detalle" => $detalle);
}

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
