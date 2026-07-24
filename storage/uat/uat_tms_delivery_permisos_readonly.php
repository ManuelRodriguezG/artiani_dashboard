<?php

/**
 * IA: Codex GPT-5
 * Fecha: 2026-07-24
 * Proposito: auditar permisos TMS en seguridad sin sembrarlos.
 * Impacto: permite saber si /tms/servicios sera visible y usable por permisos.
 * Contrato: read-only; no crea roles, permisos ni relaciones.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";

$db = (new class extends CRUD {
  public function conexion() {
    return $this->getConexion();
  }
})->conexion();

$permisos = array(
  "tms.ver",
  "tms.crear",
  "tms.programar",
  "tms.operar",
  "tms.evidencias",
  "tms.costos",
  "tms.autorizar",
  "tms.reportes"
);
$roles = array("direccion", "administrador_erp", "ventas", "almacen", "crm", "finanzas_contabilidad", "auditor", "solo_lectura");
$resultadoPermisos = array();
$resultadoRoles = array();
$tablas = array("sys_permisos" => false, "sys_roles" => false, "sys_roles_permisos" => false);

if ($db) {
  foreach (array_keys($tablas) as $tabla) {
    $stmtTabla = $db->prepare("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=:base AND TABLE_NAME=:tabla LIMIT 1");
    $stmtTabla->execute(array(":base" => MYSQLBASE, ":tabla" => $tabla));
    $tablas[$tabla] = (bool) $stmtTabla->fetch(PDO::FETCH_ASSOC);
  }
}

if ($db && $tablas["sys_permisos"]) {
  $stmt = $db->prepare("SELECT permiso, modulo, accion, estatus FROM sys_permisos WHERE permiso=:permiso LIMIT 1");
  foreach ($permisos as $permiso) {
    $stmt->execute(array(":permiso" => $permiso));
    $fila = $stmt->fetch(PDO::FETCH_ASSOC);
    $resultadoPermisos[] = array(
      "permiso" => $permiso,
      "existe" => (bool) $fila,
      "registro" => $fila ?: null
    );
  }
}

if ($db && $tablas["sys_roles"] && $tablas["sys_roles_permisos"] && $tablas["sys_permisos"]) {
  $stmtRol = $db->prepare("SELECT sr.rol, sp.permiso
    FROM sys_roles sr
    LEFT JOIN sys_roles_permisos rp ON rp.id_rol=sr.id_rol
    LEFT JOIN sys_permisos sp ON sp.id_permiso=rp.id_permiso AND sp.permiso LIKE 'tms.%'
    WHERE sr.rol=:rol
    ORDER BY sp.permiso ASC");
  foreach ($roles as $rol) {
    $stmtRol->execute(array(":rol" => $rol));
    $filas = $stmtRol->fetchAll(PDO::FETCH_ASSOC);
    $resultadoRoles[] = array(
      "rol" => $rol,
      "permisos_tms" => array_values(array_filter(array_map(function ($fila) {
        return isset($fila["permiso"]) ? $fila["permiso"] : null;
      }, $filas)))
    );
  }
}

$pendientes = array_values(array_filter($resultadoPermisos, function ($item) {
  return empty($item["existe"]);
}));

echo json_encode(array(
  "ok" => (bool) $db,
  "modo" => "read-only",
  "tablas" => $tablas,
  "permisos" => $resultadoPermisos,
  "roles" => $resultadoRoles,
  "pendientes" => $pendientes,
  "token_apply" => "TMS_PERMISOS_BASE",
  "siguiente_paso" => "Si hay pendientes, sincronizar seguridad con autorizacion TMS_PERMISOS_BASE. No aplicar DDL TMS con este token."
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
