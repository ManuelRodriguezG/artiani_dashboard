<?php

/**
 * IA: Codex GPT-5
 * Fecha: 2026-07-30
 * Proposito: sembrar permisos finos por submodulo CRM con autorizacion explicita.
 * Impacto: separa accesos de Clientes, Seguimiento, Comercial, Recompensas y Reportes.
 * Contrato: requiere token CRM_SUBMODULOS_PERMISOS y respaldo; no toca clientes, POS, ventas ni ecommerce.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";

$opciones = getopt("", array("autorizar::", "respaldo::"));
$autorizar = isset($opciones["autorizar"]) ? trim((string) $opciones["autorizar"]) : "";
$respaldo = isset($opciones["respaldo"]) ? trim((string) $opciones["respaldo"]) : "";
$validacion = validar_respaldo_crm_submodulos($respaldo);

$permisos = permisos_crm_submodulos();
$rolesVincular = roles_crm_submodulos();

if ($autorizar !== "CRM_SUBMODULOS_PERMISOS" || !$validacion["ok"]) {
  echo json_encode(array(
    "ok" => false,
    "modo" => "bloqueado",
    "mensaje" => "No se sembraron permisos CRM por submodulo. Falta token o respaldo valido.",
    "requerido" => array("autorizar" => "CRM_SUBMODULOS_PERMISOS", "respaldo" => "RUTA_O_REFERENCIA"),
    "validacion_respaldo" => $validacion,
    "alcance" => array(
      "crea_permisos" => array_column($permisos, "permiso"),
      "vincula_roles" => array_keys($rolesVincular),
      "retira_permisos" => false,
      "toca_clientes" => false,
      "toca_ventas_pos" => false
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

try {
  $db->beginTransaction();

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

  $stmtRel = $db->prepare("INSERT IGNORE INTO sys_roles_permisos (id_rol, id_permiso)
      SELECT sr.id_rol, sp.id_permiso
      FROM sys_roles sr
      INNER JOIN sys_permisos sp ON sp.permiso=:permiso
      WHERE sr.rol=:rol");
  $relaciones = 0;
  foreach ($rolesVincular as $rol => $permisosRol) {
    foreach ($permisosRol as $permiso) {
      $stmtRel->execute(array(":rol" => $rol, ":permiso" => $permiso));
      $relaciones++;
    }
  }

  $db->commit();

  echo json_encode(array(
    "ok" => true,
    "modo" => "apply_authorized",
    "mensaje" => "Permisos CRM por submodulo sembrados",
    "permisos_creados_o_actualizados" => count($permisos),
    "relaciones_intentadas" => $relaciones,
    "roles_vinculados" => array_keys($rolesVincular)
  ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
} catch (Exception $e) {
  if ($db->inTransaction()) {
    $db->rollBack();
  }
  echo json_encode(array("ok" => false, "modo" => "error", "mensaje" => $e->getMessage()), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
}

function permisos_crm_submodulos() {
  return array(
    array("modulo" => "crm", "accion" => "clientes_ver", "permiso" => "crm.clientes.ver", "descripcion" => "Consultar listado y ficha de clientes CRM"),
    array("modulo" => "crm", "accion" => "clientes_editar", "permiso" => "crm.clientes.editar", "descripcion" => "Editar datos basicos y complementos de ficha CRM"),
    array("modulo" => "crm", "accion" => "seguimiento_ver", "permiso" => "crm.seguimiento.ver", "descripcion" => "Consultar tareas e interacciones de seguimiento CRM"),
    array("modulo" => "crm", "accion" => "seguimiento_operar", "permiso" => "crm.seguimiento.operar", "descripcion" => "Crear, cerrar o actualizar tareas e interacciones CRM"),
    array("modulo" => "crm", "accion" => "comercial_ver", "permiso" => "crm.comercial.ver", "descripcion" => "Consultar segmentos, condiciones y reportes comerciales CRM"),
    array("modulo" => "crm", "accion" => "comercial_operar", "permiso" => "crm.comercial.operar", "descripcion" => "Administrar segmentos, preferencias y condiciones comerciales CRM"),
    array("modulo" => "crm", "accion" => "recompensas_ver", "permiso" => "crm.recompensas.ver", "descripcion" => "Consultar programas, cuentas y movimientos de recompensas CRM"),
    array("modulo" => "crm", "accion" => "recompensas_operar", "permiso" => "crm.recompensas.operar", "descripcion" => "Administrar programas, cuentas y movimientos de recompensas CRM"),
    array("modulo" => "crm", "accion" => "reportes_ver", "permiso" => "crm.reportes.ver", "descripcion" => "Consultar reportes operativos de CRM")
  );
}

function roles_crm_submodulos() {
  $todos = array(
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
  return array(
    "direccion" => array("crm.clientes.ver", "crm.seguimiento.ver", "crm.comercial.ver", "crm.recompensas.ver", "crm.reportes.ver"),
    "crm" => $todos,
    "administrador_erp" => $todos
  );
}

function validar_respaldo_crm_submodulos($respaldo) {
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
