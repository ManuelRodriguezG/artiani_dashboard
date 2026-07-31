<?php

/**
 * IA: Codex GPT-5
 * Fecha: 2026-07-30
 * Proposito: auditar permisos finos por submodulo CRM sin escribir BD.
 * Impacto: permite separar Clientes, Seguimiento, Comercial, Recompensas y Reportes.
 * Contrato: read-only; no crea permisos, no vincula roles y no modifica clientes/ventas.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";

$db = (new class extends CRUD {
  public function conexion() {
    return $this->getConexion();
  }
})->conexion();

$permisosSubmodulos = array(
  "crm.clientes.ver",
  "crm.clientes.editar",
  "crm.seguimiento.ver",
  "crm.seguimiento.operar",
  "crm.comercial.ver",
  "crm.comercial.operar",
  "crm.recompensas.ver",
  "crm.recompensas.operar",
  "crm.reportes.ver"
);

$rolesObjetivo = array("direccion", "crm", "administrador_erp", "ventas");
$mapaObjetivo = array(
  "direccion" => array("crm.clientes.ver", "crm.seguimiento.ver", "crm.comercial.ver", "crm.recompensas.ver", "crm.reportes.ver"),
  "crm" => $permisosSubmodulos,
  "administrador_erp" => $permisosSubmodulos,
  "ventas" => array()
);

if (!$db) {
  echo json_encode(array("ok" => false, "modo" => "read-only", "mensaje" => "No hay conexion MySQL"), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
  exit;
}

$permisos = consultar_permisos_crm_submodulos($db, $permisosSubmodulos);
$roles = consultar_roles_permisos_crm_submodulos($db, $rolesObjetivo, $permisosSubmodulos);
$faltantesPorRol = calcular_faltantes_crm_submodulos($roles, $mapaObjetivo);

echo json_encode(array(
  "ok" => true,
  "modo" => "read-only",
  "token_apply" => "CRM_SUBMODULOS_PERMISOS",
  "permisos_submodulos" => $permisos,
  "roles" => $roles,
  "pendientes" => array(
    "permisos_faltantes" => array_values(array_filter($permisos, function ($item) {
      return empty($item["existe"]);
    })),
    "relaciones_faltantes_por_rol" => $faltantesPorRol
  ),
  "siguiente_paso" => "Con respaldo externo y autorizacion, ejecutar uat_crm_submodulos_permisos_apply_authorized.php."
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

function consultar_permisos_crm_submodulos($db, $permisos) {
  $stmt = $db->prepare("SELECT permiso, modulo, accion, estatus FROM sys_permisos WHERE permiso=:permiso LIMIT 1");
  $resultado = array();
  foreach ($permisos as $permiso) {
    $stmt->execute(array(":permiso" => $permiso));
    $fila = $stmt->fetch(PDO::FETCH_ASSOC);
    $resultado[] = array(
      "permiso" => $permiso,
      "existe" => (bool) $fila,
      "registro" => $fila ?: null
    );
  }
  return $resultado;
}

function consultar_roles_permisos_crm_submodulos($db, $roles, $permisos) {
  $marcadoresRoles = implode(",", array_fill(0, count($roles), "?"));
  $marcadoresPermisos = implode(",", array_fill(0, count($permisos), "?"));
  $sql = "SELECT sr.rol, sp.permiso
          FROM sys_roles sr
          LEFT JOIN sys_roles_permisos srp ON srp.id_rol=sr.id_rol
          LEFT JOIN sys_permisos sp ON sp.id_permiso=srp.id_permiso AND sp.permiso IN ($marcadoresPermisos)
          WHERE sr.rol IN ($marcadoresRoles)
          ORDER BY sr.rol, sp.permiso";
  $stmt = $db->prepare($sql);
  $stmt->execute(array_merge($permisos, $roles));

  $resultado = array();
  foreach ($roles as $rol) {
    $resultado[$rol] = array("rol" => $rol, "permisos" => array());
  }
  foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
    $rol = $fila["rol"];
    if (!isset($resultado[$rol])) {
      $resultado[$rol] = array("rol" => $rol, "permisos" => array());
    }
    if (!empty($fila["permiso"])) {
      $resultado[$rol]["permisos"][] = $fila["permiso"];
    }
  }
  return array_values($resultado);
}

function calcular_faltantes_crm_submodulos($roles, $mapaObjetivo) {
  $porRol = array();
  foreach ($roles as $rolInfo) {
    $rol = $rolInfo["rol"];
    $esperados = isset($mapaObjetivo[$rol]) ? $mapaObjetivo[$rol] : array();
    $faltantes = array_values(array_diff($esperados, $rolInfo["permisos"]));
    if (!empty($faltantes)) {
      $porRol[$rol] = $faltantes;
    }
  }
  return $porRol;
}
