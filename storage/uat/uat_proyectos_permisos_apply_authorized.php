<?php

/**
 * IA: Codex GPT-5
 * Fecha: 2026-07-25
 * Proposito: sincronizar permisos Proyectos solo con autorizacion explicita.
 * Impacto: habilita menu/endpoints Proyectos segun roles base; no crea proyectos ni tareas.
 * Contrato: bloqueado por defecto; requiere --autorizar=PROYECTOS_PERMISOS_BASE y --respaldo valido.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";

$opciones = getopt("", array("autorizar::", "respaldo::"));
$autorizar = isset($opciones["autorizar"]) ? trim((string) $opciones["autorizar"]) : "";
$respaldo = isset($opciones["respaldo"]) ? trim((string) $opciones["respaldo"]) : "";
$validacion = validar_respaldo_proyectos_permisos($respaldo);

if ($autorizar !== "PROYECTOS_PERMISOS_BASE" || !$validacion["ok"]) {
  echo json_encode(array(
    "ok" => false,
    "modo" => "bloqueado",
    "mensaje" => "No se sincronizaron permisos Proyectos. Falta token o respaldo valido.",
    "requerido" => array(
      "autorizar" => "PROYECTOS_PERMISOS_BASE",
      "respaldo" => "RUTA_O_REFERENCIA"
    ),
    "validacion_respaldo" => $validacion,
    "alcance" => array(
      "crea_permisos_proyectos" => true,
      "vincula_roles_base" => true,
      "crea_tablas_proyectos" => false,
      "crea_proyectos_reales" => false,
      "crea_tareas_reales" => false,
      "asigna_usuarios_directo" => false,
      "toca_otros_modulos" => false
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

$permisos = proyectos_permisos_seed();
$rolesPermisos = proyectos_roles_permisos_seed();

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

  $stmtPermisos = $db->query("SELECT id_permiso, permiso FROM sys_permisos WHERE modulo='proyectos'");
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
    "modo" => "proyectos_permisos_apply_authorized",
    "mensaje" => "Permisos Proyectos sincronizados",
    "validacion_respaldo" => $validacion,
    "permisos_total" => count($permisos),
    "roles_detectados" => array_keys($roles),
    "relaciones_intentadas" => $relaciones,
    "asigna_usuarios_directo" => false,
    "crea_tablas_proyectos" => false,
    "crea_proyectos_reales" => false,
    "crea_tareas_reales" => false
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

function validar_respaldo_proyectos_permisos($respaldo) {
  $esRutaLocal = preg_match('/^[A-Za-z]:[\\\\\\/]/', $respaldo) === 1 || strpos($respaldo, "\\") !== false || strpos($respaldo, "/") !== false;
  $existe = false;
  $legible = false;
  $tamano = null;
  if ($respaldo !== "" && $esRutaLocal) {
    $existe = file_exists($respaldo);
    $legible = $existe && is_readable($respaldo);
    $tamano = $existe ? filesize($respaldo) : null;
  }
  $placeholder = respaldo_placeholder_proyectos_permisos($respaldo);
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

function respaldo_placeholder_proyectos_permisos($valor) {
  $valor = strtoupper(trim((string) $valor));
  return $valor === ""
    || strpos($valor, "RUTA_O_REFERENCIA") !== false
    || strpos($valor, "RUTA_RESPALDO") !== false
    || strpos($valor, "PLACEHOLDER") !== false;
}
