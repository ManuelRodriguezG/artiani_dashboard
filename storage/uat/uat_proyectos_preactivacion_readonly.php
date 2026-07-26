<?php

/**
 * IA: Codex GPT-5
 * Fecha: 2026-07-25
 * Proposito: preparar preactivacion read-only del modulo Proyectos.
 * Impacto: ordena permisos y DDL antes de usar proyectos/tareas reales.
 * Contrato: read-only; no crea respaldo, no sincroniza permisos, no ejecuta DDL, no crea proyectos ni tareas.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/core/DBSchema.php";
require_once __DIR__ . "/../../app/modelos/ProyectosEsquema.php";

$root = realpath(__DIR__ . "/../..");
$fecha = date("Ymd_His");
$base = defined("MYSQLBASE") ? MYSQLBASE : "artianilocal";
$backupDir = "C:\\xampp\\panel_db_backups";
$backupPermisos = $backupDir . "\\" . $base . "_panel_" . $fecha . "_antes_proyectos_permisos.sql";
$backupSchema = $backupDir . "\\" . $base . "_panel_" . $fecha . "_antes_proyectos_schema.sql";

$scripts = array(
  "schema_readonly" => "storage/uat/uat_proyectos_schema_readonly.php",
  "schema_apply" => "storage/uat/uat_proyectos_schema_apply_authorized.php",
  "permisos_readonly" => "storage/uat/uat_proyectos_permisos_readonly.php",
  "permisos_apply" => "storage/uat/uat_proyectos_permisos_apply_authorized.php"
);

$checks = array();
foreach ($scripts as $clave => $relativa) {
  $checks["script_" . $clave] = check_item(file_exists($root . "/" . $relativa), $relativa);
}

$modelo = new ProyectosEsquema();
$auditoria = $modelo->auditarProyectosErp();
$plan = $modelo->planActualizarProyectosErp(false);
$schemaPendiente = isset($auditoria["depurar"]["tiene_pendientes"]) ? (bool) $auditoria["depurar"]["tiene_pendientes"] : true;
$ddlPendientes = 0;
foreach ((isset($plan["depurar"]["plan"]) && is_array($plan["depurar"]["plan"]) ? $plan["depurar"]["plan"] : array()) as $paso) {
  if (isset($paso["depurar"]["ejecutado"]) && $paso["depurar"]["ejecutado"] === false && isset($paso["depurar"]["sql"])) {
    $ddlPendientes++;
  }
}

$db = (new class extends CRUD {
  public function conexion() {
    return $this->getConexion();
  }
})->conexion();

$permisos = array("proyectos.ver", "proyectos.crear", "proyectos.editar", "proyectos.asignar", "proyectos.cerrar", "proyectos.auditoria", "proyectos.configurar");
$permisosPendientes = auditar_permisos_pendientes_proyectos($db, $permisos);
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
    "permisos_proyectos_pendientes" => count($permisosPendientes),
    "schema_proyectos_pendiente" => $schemaPendiente,
    "ddl_pendientes" => $ddlPendientes
  ),
  "orden_recomendado" => array(
    "1_respaldo_permisos",
    "2_aplicar_permisos_proyectos",
    "3_validar_menu_y_acceso_proyectos",
    "4_respaldo_schema",
    "5_aplicar_ddl_proyectos",
    "6_validar_schema_proyectos",
    "7_abrir_bandeja_vacia",
    "8_capturar_primer_proyecto_manual_si_el_dueno_lo_indica"
  ),
  "comandos_propuestos" => array(
    "respaldo_permisos" => "C:\\xampp\\mysql\\bin\\mysqldump.exe --host=localhost --user=root --result-file=\"" . $backupPermisos . "\" " . $base,
    "aplicar_permisos" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_proyectos_permisos_apply_authorized.php --autorizar=PROYECTOS_PERMISOS_BASE --respaldo=\"" . $backupPermisos . "\"",
    "respaldo_schema" => "C:\\xampp\\mysql\\bin\\mysqldump.exe --host=localhost --user=root --result-file=\"" . $backupSchema . "\" " . $base,
    "aplicar_schema" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_proyectos_schema_apply_authorized.php --autorizar=PROYECTOS_DDL_BASE --respaldo=\"" . $backupSchema . "\""
  ),
  "autorizaciones" => array(
    "permisos" => "AUTORIZO SEMBRAR PERMISOS PROYECTOS BASE usando respaldo [RUTA_RESPALDO] con token PROYECTOS_PERMISOS_BASE. Entiendo que solo crea permisos proyectos.* y vinculos con roles base, no crea proyectos, no crea tareas, no modifica otros modulos ni asigna usuarios directos.",
    "schema" => "AUTORIZO CREAR ESQUEMA PROYECTOS usando respaldo [RUTA_RESPALDO] con token PROYECTOS_DDL_BASE. Entiendo que solo crea tablas erp_proyecto* vacias para proyectos, objetivos, tareas, comentarios, adjuntos y eventos; no crea proyectos reales, no crea tareas reales, no importa pendientes de otros modulos y no modifica ventas, inventario, compras, catalogo, proveedores, CRM, TMS, garantias ni rentabilidad."
  ),
  "reglas" => array(
    "no_precargar_tareas_modulos" => true,
    "permisos_y_schema_en_autorizaciones_separadas" => true,
    "proyectos_no_reemplaza_documentos_vivos" => true,
    "notificaciones_solo_para_tareas_reales_futuras" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

function check_item($ok, $detalle) {
  return array("ok" => (bool) $ok, "detalle" => $detalle);
}

function auditar_permisos_pendientes_proyectos($db, $permisos) {
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
