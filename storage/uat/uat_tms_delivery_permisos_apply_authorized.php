<?php

/**
 * IA: Codex GPT-5
 * Fecha: 2026-07-24
 * Proposito: sincronizar permisos TMS Delivery solo con autorizacion explicita.
 * Impacto: habilita menu/endpoints TMS segun roles base; no crea tablas TMS ni servicios logisticos.
 * Contrato: bloqueado por defecto; requiere --autorizar=TMS_PERMISOS_BASE y --respaldo valido.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";

$opciones = getopt("", array("autorizar::", "respaldo::"));
$autorizar = isset($opciones["autorizar"]) ? trim((string) $opciones["autorizar"]) : "";
$respaldo = isset($opciones["respaldo"]) ? trim((string) $opciones["respaldo"]) : "";
$validacion = validar_respaldo_tms_permisos($respaldo);

if ($autorizar !== "TMS_PERMISOS_BASE" || !$validacion["ok"]) {
  echo json_encode(array(
    "ok" => false,
    "modo" => "bloqueado",
    "mensaje" => "No se sincronizaron permisos TMS. Falta token o respaldo valido.",
    "requerido" => array(
      "autorizar" => "TMS_PERMISOS_BASE",
      "respaldo" => "RUTA_O_REFERENCIA"
    ),
    "validacion_respaldo" => $validacion,
    "alcance" => array(
      "crea_permisos_tms" => true,
      "vincula_roles_base" => true,
      "crea_tablas_tms" => false,
      "crea_servicios_tms" => false,
      "asigna_usuarios_directo" => false,
      "toca_ventas" => false,
      "toca_inventario" => false,
      "toca_garantias" => false
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

$permisos = tms_permisos_seed();
$rolesPermisos = tms_roles_permisos_seed();

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

  $rolesObjetivo = array_keys($rolesPermisos);
  $placeholdersRoles = implode(",", array_fill(0, count($rolesObjetivo), "?"));
  $stmtRoles = $db->prepare("SELECT id_rol, rol FROM sys_roles WHERE rol IN ({$placeholdersRoles})");
  $stmtRoles->execute($rolesObjetivo);
  $roles = array();
  foreach ($stmtRoles->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $roles[$row["rol"]] = intval($row["id_rol"]);
  }

  $stmtPermisos = $db->query("SELECT id_permiso, permiso FROM sys_permisos WHERE modulo='tms'");
  $permisosDb = array();
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
    "modo" => "tms_permisos_apply_authorized",
    "mensaje" => "Permisos TMS sincronizados",
    "validacion_respaldo" => $validacion,
    "permisos_total" => count($permisos),
    "roles_detectados" => array_keys($roles),
    "relaciones_intentadas" => $relaciones,
    "asigna_usuarios_directo" => false,
    "crea_tablas_tms" => false
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

function tms_permisos_seed() {
  return array(
    array("modulo" => "tms", "accion" => "ver", "permiso" => "tms.ver", "descripcion" => "Consultar servicios logisticos, estados, eventos y evidencias visibles"),
    array("modulo" => "tms", "accion" => "crear", "permiso" => "tms.crear", "descripcion" => "Crear solicitudes de entrega, recoleccion o traslado sin vender productos"),
    array("modulo" => "tms", "accion" => "programar", "permiso" => "tms.programar", "descripcion" => "Programar fecha, ventana y responsable de servicios logisticos"),
    array("modulo" => "tms", "accion" => "operar", "permiso" => "tms.operar", "descripcion" => "Operar estados logisticos sin cancelar ventas ni decidir garantias"),
    array("modulo" => "tms", "accion" => "evidencias", "permiso" => "tms.evidencias", "descripcion" => "Adjuntar o cancelar evidencias logisticas con baja logica"),
    array("modulo" => "tms", "accion" => "costos", "permiso" => "tms.costos", "descripcion" => "Consultar y registrar costos del servicio logistico"),
    array("modulo" => "tms", "accion" => "autorizar", "permiso" => "tms.autorizar", "descripcion" => "Autorizar bonificaciones, cortesias o excepciones logisticas"),
    array("modulo" => "tms", "accion" => "reportes", "permiso" => "tms.reportes", "descripcion" => "Consultar reportes e indicadores de servicios logisticos")
  );
}

function tms_roles_permisos_seed() {
  return array(
    "direccion" => array("tms.ver", "tms.autorizar", "tms.costos", "tms.reportes"),
    "administrador_erp" => array("tms.ver", "tms.crear", "tms.programar", "tms.operar", "tms.evidencias", "tms.costos", "tms.autorizar", "tms.reportes"),
    "ventas" => array("tms.ver", "tms.crear"),
    "almacen" => array("tms.ver"),
    "crm" => array("tms.ver", "tms.crear"),
    "finanzas_contabilidad" => array("tms.ver", "tms.costos", "tms.reportes"),
    "auditor" => array("tms.ver", "tms.reportes"),
    "solo_lectura" => array("tms.ver")
  );
}

function validar_respaldo_tms_permisos($respaldo) {
  $esRutaLocal = preg_match('/^[A-Za-z]:[\\\\\\/]/', $respaldo) === 1 || strpos($respaldo, "\\") !== false || strpos($respaldo, "/") !== false;
  $existe = false;
  $legible = false;
  $tamano = null;
  if ($respaldo !== "" && $esRutaLocal) {
    $existe = file_exists($respaldo);
    $legible = $existe && is_readable($respaldo);
    $tamano = $existe ? filesize($respaldo) : null;
  }
  $placeholder = respaldo_placeholder_tms_permisos($respaldo);
  $okReferencia = strlen($respaldo) >= 8 && !$placeholder;
  $okRuta = !$esRutaLocal || ($existe && $legible && $tamano !== null && $tamano > 0);
  return array(
    "ok" => $okReferencia && $okRuta,
    "referencia_presente" => $okReferencia,
    "referencia" => $respaldo,
    "parece_ruta_local" => $esRutaLocal,
    "archivo_existe" => $esRutaLocal ? $existe : null,
    "archivo_legible" => $esRutaLocal ? $legible : null,
    "tamano_bytes" => $tamano,
    "placeholder_bloqueado" => $placeholder
  );
}

function respaldo_placeholder_tms_permisos($valor) {
  $valor = strtoupper(trim((string) $valor));
  return $valor === ""
    || strpos($valor, "RUTA_O_REFERENCIA") !== false
    || strpos($valor, "RUTA_RESPALDO") !== false
    || strpos($valor, "PLACEHOLDER") !== false;
}
