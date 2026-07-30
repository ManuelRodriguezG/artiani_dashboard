<?php

/**
 * IA: Codex GPT-5
 * Fecha: 2026-07-30
 * Proposito: aplicar separacion fina de permisos CRM usados por POS.
 * Impacto: permite que rol ventas busque/valide alta express sin ver consola CRM completa.
 * Contrato: requiere token CRM_POS_PERMISOS_FINOS y respaldo; no toca clientes, ventas, POS ni ecommerce.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";

$opciones = getopt("", array("autorizar::", "respaldo::"));
$autorizar = isset($opciones["autorizar"]) ? trim((string) $opciones["autorizar"]) : "";
$respaldo = isset($opciones["respaldo"]) ? trim((string) $opciones["respaldo"]) : "";
$validacion = validar_respaldo_crm_pos_permisos($respaldo);

if ($autorizar !== "CRM_POS_PERMISOS_FINOS" || !$validacion["ok"]) {
  echo json_encode(array(
    "ok" => false,
    "modo" => "bloqueado",
    "mensaje" => "No se ajustaron permisos finos CRM/POS. Falta token o respaldo valido.",
    "requerido" => array("autorizar" => "CRM_POS_PERMISOS_FINOS", "respaldo" => "RUTA_O_REFERENCIA"),
    "validacion_respaldo" => $validacion,
    "alcance" => array(
      "crea_permisos" => array("crm.pos.buscar", "crm.pos.alta_express"),
      "vincula_roles" => array("ventas", "crm", "administrador_erp"),
      "retira_de_ventas" => array("crm.ver", "crm.crear"),
      "toca_clientes" => false,
      "toca_ventas" => false
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

$permisos = array(
  array("modulo" => "crm", "accion" => "pos_buscar", "permiso" => "crm.pos.buscar", "descripcion" => "Buscar y seleccionar clientes CRM desde POS sin abrir consola CRM"),
  array("modulo" => "crm", "accion" => "pos_alta_express", "permiso" => "crm.pos.alta_express", "descripcion" => "Validar altas express de clientes desde POS sin editar ficha completa CRM")
);
$rolesVincular = array(
  "ventas" => array("crm.pos.buscar", "crm.pos.alta_express"),
  "crm" => array("crm.pos.buscar", "crm.pos.alta_express"),
  "administrador_erp" => array("crm.pos.buscar", "crm.pos.alta_express")
);

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

  $stmtRetirar = $db->prepare("DELETE srp
      FROM sys_roles_permisos srp
      INNER JOIN sys_roles sr ON sr.id_rol=srp.id_rol
      INNER JOIN sys_permisos sp ON sp.id_permiso=srp.id_permiso
      WHERE sr.rol='ventas' AND sp.permiso IN ('crm.ver','crm.crear')");
  $stmtRetirar->execute();
  $retirados = $stmtRetirar->rowCount();

  $db->commit();

  echo json_encode(array(
    "ok" => true,
    "modo" => "apply_authorized",
    "mensaje" => "Permisos finos CRM/POS aplicados",
    "permisos_creados_o_actualizados" => count($permisos),
    "relaciones_intentadas" => $relaciones,
    "relaciones_amplias_retiradas_de_ventas" => $retirados
  ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
} catch (Exception $e) {
  if ($db->inTransaction()) {
    $db->rollBack();
  }
  echo json_encode(array("ok" => false, "modo" => "error", "mensaje" => $e->getMessage()), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
}

function validar_respaldo_crm_pos_permisos($respaldo) {
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
