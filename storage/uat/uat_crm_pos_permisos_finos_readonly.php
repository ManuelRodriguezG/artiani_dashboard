<?php

/**
 * IA: Codex GPT-5
 * Fecha: 2026-07-30
 * Proposito: auditar separacion de permisos POS/CRM sin escribir BD.
 * Impacto: permite confirmar si ventas puede usar clientes en POS sin ver consola CRM completa.
 * Contrato: read-only; no crea permisos, no vincula roles y no modifica clientes/ventas.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";

$db = (new class extends CRUD {
  public function conexion() {
    return $this->getConexion();
  }
})->conexion();

$permisosFinos = array("crm.pos.buscar", "crm.pos.alta_express");
$permisosAmpliosVentas = array("crm.ver", "crm.crear");
$rolesObjetivo = array("ventas", "crm", "administrador_erp", "direccion");

if (!$db) {
  echo json_encode(array("ok" => false, "modo" => "read-only", "mensaje" => "No hay conexion MySQL"), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
  exit;
}

$permisos = consultar_permisos($db, array_merge($permisosFinos, $permisosAmpliosVentas));
$roles = consultar_roles_permisos($db, $rolesObjetivo, array_merge($permisosFinos, $permisosAmpliosVentas));
$ventasTieneAmplios = array();
foreach ($roles as $rol) {
  if ($rol["rol"] !== "ventas") {
    continue;
  }
  foreach ($rol["permisos"] as $permiso) {
    if (in_array($permiso, $permisosAmpliosVentas, true)) {
      $ventasTieneAmplios[] = $permiso;
    }
  }
}

echo json_encode(array(
  "ok" => true,
  "modo" => "read-only",
  "token_apply" => "CRM_POS_PERMISOS_FINOS",
  "permisos_finos" => $permisos,
  "roles" => $roles,
  "pendientes" => array(
    "permisos_finos_faltantes" => array_values(array_filter($permisos, function ($item) {
      return strpos($item["permiso"], "crm.pos.") === 0 && empty($item["existe"]);
    })),
    "ventas_permisos_amplios_a_retirar" => $ventasTieneAmplios
  ),
  "siguiente_paso" => "Con respaldo externo y autorizacion, ejecutar uat_crm_pos_permisos_finos_apply_authorized.php."
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

function consultar_permisos($db, $permisos) {
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

function consultar_roles_permisos($db, $roles, $permisos) {
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
