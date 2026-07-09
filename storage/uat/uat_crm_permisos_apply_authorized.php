<?php

/**
 * IA: Codex GPT-5
 * Fecha: 2026-06-29
 * Proposito: sembrar permisos CRM solo con autorizacion explicita.
 * Impacto: habilita menu/endpoints CRM segun roles base; no toca clientes ni ventas.
 * Contrato: bloqueado por defecto; requiere --autorizar=CRM_PERMISOS_BASE y --respaldo.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";

$opciones = getopt("", array("autorizar::", "respaldo::"));
$autorizar = isset($opciones["autorizar"]) ? trim((string) $opciones["autorizar"]) : "";
$respaldo = isset($opciones["respaldo"]) ? trim((string) $opciones["respaldo"]) : "";
$validacion = validar_respaldo_crm_permisos($respaldo);

if ($autorizar !== "CRM_PERMISOS_BASE" || !$validacion["ok"]) {
  echo json_encode(array(
    "ok" => false,
    "modo" => "bloqueado",
    "mensaje" => "No se sembraron permisos CRM. Falta token o respaldo valido.",
    "requerido" => array(
      "autorizar" => "CRM_PERMISOS_BASE",
      "respaldo" => "RUTA_O_REFERENCIA"
    ),
    "validacion_respaldo" => $validacion,
    "alcance" => array(
      "crea_permisos_crm" => true,
      "vincula_roles_base" => true,
      "asigna_usuarios" => false,
      "toca_clientes" => false
    )
  ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
  exit;
}

$db = (new class extends CRUD {
  public function conexion() {
    return $this->getConexion();
  }
})->conexion();

if (!$db) {
  echo json_encode(array("ok" => false, "modo" => "error", "mensaje" => "No hay conexion MySQL"), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
  exit;
}

$permisos = crm_permisos_seed();
$rolesPermisos = array(
  "direccion" => array("crm.ver", "crm.auditoria"),
  "administrador_erp" => array("crm.ver", "crm.crear", "crm.editar", "crm.fusionar", "crm.auditoria"),
  "ventas" => array("crm.ver", "crm.crear"),
  "crm" => array("crm.ver", "crm.crear", "crm.editar", "crm.fusionar", "crm.auditoria")
);

try {
  $db->beginTransaction();

  $stmtRol = $db->prepare("INSERT INTO sys_roles (rol, descripcion, estatus, fecha_actualizacion)
      VALUES (:rol, :descripcion, 1, CURRENT_TIMESTAMP)
      ON DUPLICATE KEY UPDATE descripcion=VALUES(descripcion), estatus=1, fecha_actualizacion=CURRENT_TIMESTAMP");
  $stmtRol->execute(array(
    ":rol" => "crm",
    ":descripcion" => "Administra clientes, contactos, segmentos, historial comercial y relacion postventa"
  ));

  $stmtPermiso = $db->prepare("INSERT INTO sys_permisos (modulo, accion, permiso, descripcion, estatus, fecha_actualizacion)
      VALUES (:modulo, :accion, :permiso, :descripcion, 1, CURRENT_TIMESTAMP)
      ON DUPLICATE KEY UPDATE modulo=VALUES(modulo), accion=VALUES(accion), descripcion=VALUES(descripcion), estatus=1, fecha_actualizacion=CURRENT_TIMESTAMP");
  foreach ($permisos as $permiso) {
    $stmtPermiso->execute(array(
      ":modulo" => $permiso["modulo"],
      ":accion" => $permiso["accion"],
      ":permiso" => $permiso["permiso"],
      ":descripcion" => $permiso["descripcion"]
    ));
  }

  $roles = array();
  $stmtRoles = $db->query("SELECT id_rol, rol FROM sys_roles WHERE rol IN ('direccion','administrador_erp','ventas','crm')");
  foreach ($stmtRoles->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $roles[$row["rol"]] = intval($row["id_rol"]);
  }

  $permisosDb = array();
  $stmtPermisos = $db->query("SELECT id_permiso, permiso FROM sys_permisos WHERE modulo='crm'");
  foreach ($stmtPermisos->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $permisosDb[$row["permiso"]] = intval($row["id_permiso"]);
  }

  $stmtRel = $db->prepare("INSERT IGNORE INTO sys_roles_permisos (id_rol, id_permiso) VALUES (:rol, :permiso)");
  $relaciones = 0;
  foreach ($rolesPermisos as $rol => $listaPermisos) {
    if (empty($roles[$rol])) {
      continue;
    }
    foreach ($listaPermisos as $permiso) {
      if (empty($permisosDb[$permiso])) {
        continue;
      }
      $stmtRel->execute(array(":rol" => $roles[$rol], ":permiso" => $permisosDb[$permiso]));
      $relaciones++;
    }
  }

  $db->commit();
  echo json_encode(array(
    "ok" => true,
    "modo" => "apply_authorized",
    "mensaje" => "Permisos CRM sembrados",
    "permisos_total" => count($permisos),
    "roles_detectados" => array_keys($roles),
    "relaciones_intentadas" => $relaciones,
    "asigna_usuarios" => false
  ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
} catch (Exception $e) {
  if ($db->inTransaction()) {
    $db->rollBack();
  }
  echo json_encode(array(
    "ok" => false,
    "modo" => "error",
    "mensaje" => $e->getMessage()
  ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
}

function crm_permisos_seed() {
  return array(
    array("modulo" => "crm", "accion" => "ver", "permiso" => "crm.ver", "descripcion" => "Consultar clientes, ficha, historial y relaciones CRM"),
    array("modulo" => "crm", "accion" => "crear", "permiso" => "crm.crear", "descripcion" => "Crear clientes y altas express autorizadas"),
    array("modulo" => "crm", "accion" => "editar", "permiso" => "crm.editar", "descripcion" => "Editar ficha, contactos, direcciones y condiciones CRM"),
    array("modulo" => "crm", "accion" => "fusionar", "permiso" => "crm.fusionar", "descripcion" => "Fusionar duplicados de clientes con trazabilidad"),
    array("modulo" => "crm", "accion" => "auditoria", "permiso" => "crm.auditoria", "descripcion" => "Auditar fuentes, migraciones, duplicados y esquema CRM")
  );
}

function validar_respaldo_crm_permisos($respaldo) {
  $esRutaLocal = preg_match('/^[A-Za-z]:[\\\\\\/]/', $respaldo) === 1 || strpos($respaldo, "\\") !== false || strpos($respaldo, "/") !== false;
  $existe = false;
  $legible = false;
  $tamano = null;
  if ($respaldo !== "" && $esRutaLocal) {
    $existe = file_exists($respaldo);
    $legible = $existe && is_readable($respaldo);
    $tamano = $existe ? filesize($respaldo) : null;
  }
  $okReferencia = strlen($respaldo) >= 8;
  $okRuta = !$esRutaLocal || ($existe && $legible && $tamano !== null && $tamano > 0);
  return array(
    "ok" => $okReferencia && $okRuta,
    "referencia_presente" => $okReferencia,
    "parece_ruta_local" => $esRutaLocal,
    "archivo_existe" => $esRutaLocal ? $existe : null,
    "archivo_legible" => $esRutaLocal ? $legible : null,
    "tamano_bytes" => $tamano
  );
}
