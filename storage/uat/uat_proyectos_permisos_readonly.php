<?php

/**
 * IA: Codex GPT-5
 * Fecha: 2026-07-25
 * Proposito: auditar permisos Proyectos en seguridad sin sembrarlos.
 * Impacto: permite saber si /proyecto sera visible y usable por permisos.
 * Contrato: read-only; no crea roles, permisos, relaciones, proyectos ni tareas.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";

$db = (new class extends CRUD {
  public function conexion() {
    return $this->getConexion();
  }
})->conexion();

$permisos = proyectos_permisos_seed();
$rolesPermisos = proyectos_roles_permisos_seed();
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
    $stmt->execute(array(":permiso" => $permiso["permiso"]));
    $fila = $stmt->fetch(PDO::FETCH_ASSOC);
    $resultadoPermisos[] = array(
      "permiso" => $permiso["permiso"],
      "existe" => (bool) $fila,
      "registro" => $fila ?: null
    );
  }
}

if ($db && $tablas["sys_roles"] && $tablas["sys_roles_permisos"] && $tablas["sys_permisos"]) {
  $stmtRol = $db->prepare("SELECT sr.rol, sp.permiso
    FROM sys_roles sr
    LEFT JOIN sys_roles_permisos rp ON rp.id_rol=sr.id_rol
    LEFT JOIN sys_permisos sp ON sp.id_permiso=rp.id_permiso AND sp.permiso LIKE 'proyectos.%'
    WHERE sr.rol=:rol
    ORDER BY sp.permiso ASC");
  foreach (array_keys($rolesPermisos) as $rol) {
    $stmtRol->execute(array(":rol" => $rol));
    $filas = $stmtRol->fetchAll(PDO::FETCH_ASSOC);
    $resultadoRoles[] = array(
      "rol" => $rol,
      "permisos_proyectos" => array_values(array_filter(array_map(function ($fila) {
        return isset($fila["permiso"]) ? $fila["permiso"] : null;
      }, $filas)))
    );
  }
}

$pendientesConexion = array();
if (!$db || !$tablas["sys_permisos"]) {
  $pendientesConexion = array_map(function ($permiso) {
    return array(
      "permiso" => $permiso["permiso"],
      "existe" => false,
      "registro" => null,
      "motivo" => "No fue posible auditar sys_permisos"
    );
  }, $permisos);
}

$pendientes = !empty($pendientesConexion) ? $pendientesConexion : array_values(array_filter($resultadoPermisos, function ($item) {
  return empty($item["existe"]);
}));

echo json_encode(array(
  "ok" => (bool) $db,
  "modo" => "read-only",
  "conexion_mysql" => (bool) $db,
  "tablas" => $tablas,
  "permisos" => $resultadoPermisos,
  "roles" => $resultadoRoles,
  "pendientes" => $pendientes,
  "token_apply" => "PROYECTOS_PERMISOS_BASE",
  "siguiente_paso" => "Si hay pendientes, sincronizar permisos con autorizacion PROYECTOS_PERMISOS_BASE. No aplicar DDL con este token."
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

function proyectos_permisos_seed() {
  return array(
    array("modulo" => "proyectos", "accion" => "ver", "permiso" => "proyectos.ver", "descripcion" => "Consultar proyectos, objetivos y tareas visibles"),
    array("modulo" => "proyectos", "accion" => "crear", "permiso" => "proyectos.crear", "descripcion" => "Crear proyectos y tareas operativas"),
    array("modulo" => "proyectos", "accion" => "editar", "permiso" => "proyectos.editar", "descripcion" => "Editar proyectos, tareas y comentarios operativos"),
    array("modulo" => "proyectos", "accion" => "asignar", "permiso" => "proyectos.asignar", "descripcion" => "Asignar responsables a tareas y proyectos"),
    array("modulo" => "proyectos", "accion" => "cerrar", "permiso" => "proyectos.cerrar", "descripcion" => "Cerrar, completar o descartar tareas y proyectos"),
    array("modulo" => "proyectos", "accion" => "auditoria", "permiso" => "proyectos.auditoria", "descripcion" => "Consultar actividad completa del modulo Proyectos"),
    array("modulo" => "proyectos", "accion" => "configurar", "permiso" => "proyectos.configurar", "descripcion" => "Configurar catalogos y reglas del modulo Proyectos")
  );
}

function proyectos_roles_permisos_seed() {
  return array(
    "direccion" => array("proyectos.ver", "proyectos.crear", "proyectos.editar", "proyectos.asignar", "proyectos.cerrar", "proyectos.auditoria", "proyectos.configurar"),
    "administrador_erp" => array("proyectos.ver", "proyectos.crear", "proyectos.editar", "proyectos.asignar", "proyectos.cerrar", "proyectos.auditoria", "proyectos.configurar"),
    "compras" => array("proyectos.ver"),
    "almacen" => array("proyectos.ver"),
    "inventario" => array("proyectos.ver"),
    "ventas" => array("proyectos.ver"),
    "crm" => array("proyectos.ver"),
    "ecommerce" => array("proyectos.ver"),
    "catalogo_productos" => array("proyectos.ver"),
    "finanzas_contabilidad" => array("proyectos.ver"),
    "auditor" => array("proyectos.ver", "proyectos.auditoria"),
    "solo_lectura" => array("proyectos.ver"),
    "soporte_sistema" => array("proyectos.ver", "proyectos.crear", "proyectos.editar", "proyectos.asignar", "proyectos.cerrar", "proyectos.auditoria", "proyectos.configurar")
  );
}
