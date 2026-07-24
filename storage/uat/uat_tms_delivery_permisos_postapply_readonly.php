<?php

/**
 * IA: Codex GPT-5
 * Fecha: 2026-07-24
 * Proposito: verificar permisos TMS despues de aplicar autorizacion TMS_PERMISOS_BASE.
 * Impacto: Seguridad/TMS; valida menu y roles sin tocar tablas TMS ni servicios.
 * Contrato: read-only; no crea permisos, no asigna roles y no ejecuta DDL.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";

$db = (new class extends CRUD {
  public function conexion() {
    return $this->getConexion();
  }
})->conexion();

$permisosEsperados = tms_permisos_esperados();
$rolesEsperados = tms_roles_permisos_esperados();
$detalleCompleto = in_array("--detalle=1", $argv, true);
$resultado = array(
  "permisos" => array(),
  "roles" => array(),
  "menu" => auditar_menu_tms()
);

if (!$db) {
  echo json_encode(array(
    "ok" => false,
    "modo" => "read-only",
    "estado" => "sin_conexion",
    "mensaje" => "No hay conexion MySQL para verificar permisos TMS",
    "resultado" => $resultado
  ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
  exit;
}

$permisosDb = consultar_permisos_tms($db, array_keys($permisosEsperados));
foreach ($permisosEsperados as $permiso => $esperado) {
  $registro = isset($permisosDb[$permiso]) ? $permisosDb[$permiso] : null;
  $resultado["permisos"][] = array(
    "permiso" => $permiso,
    "ok" => $registro && intval($registro["estatus"]) === 1,
    "esperado" => $esperado,
    "registro" => $registro
  );
}

$rolesDb = consultar_roles_tms($db, array_keys($rolesEsperados));
foreach ($rolesEsperados as $rol => $permisos) {
  $actuales = isset($rolesDb[$rol]) ? $rolesDb[$rol] : array();
  $faltantes = array_values(array_diff($permisos, $actuales));
  $extras = array_values(array_diff($actuales, $permisos));
  $resultado["roles"][] = array(
    "rol" => $rol,
    "ok" => empty($faltantes),
    "esperados" => $permisos,
    "actuales" => $actuales,
    "faltantes" => $faltantes,
    "extras_tms_no_bloqueantes" => $extras
  );
}

$fallosPermisos = array_values(array_filter($resultado["permisos"], function ($item) {
  return empty($item["ok"]);
}));
$fallosRoles = array_values(array_filter($resultado["roles"], function ($item) {
  return empty($item["ok"]);
}));
$menuOk = !empty($resultado["menu"]["ok"]);

$respuesta = array(
  "ok" => empty($fallosPermisos) && empty($fallosRoles) && $menuOk,
  "modo" => "read-only",
  "estado" => empty($fallosPermisos) && empty($fallosRoles) && $menuOk ? "permisos_tms_listos" : "permisos_tms_pendientes",
  "resumen" => array(
    "permisos_ok" => count($permisosEsperados) - count($fallosPermisos),
    "permisos_total" => count($permisosEsperados),
    "roles_ok" => count($rolesEsperados) - count($fallosRoles),
    "roles_total" => count($rolesEsperados),
    "menu_ok" => $menuOk
  ),
  "fallos" => array(
    "permisos_total" => count($fallosPermisos),
    "roles_total" => count($fallosRoles),
    "permisos" => array_column($fallosPermisos, "permiso"),
    "roles" => array_column($fallosRoles, "rol"),
    "menu" => $menuOk ? array() : $resultado["menu"]
  ),
  "siguiente_paso" => "Si estado=permisos_tms_listos, validar acceso /tms/servicios y preparar autorizacion separada para DDL TMS."
);

if ($detalleCompleto) {
  $respuesta["resultado"] = $resultado;
}

echo json_encode($respuesta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

function tms_permisos_esperados() {
  return array(
    "tms.ver" => array("modulo" => "tms", "accion" => "ver"),
    "tms.crear" => array("modulo" => "tms", "accion" => "crear"),
    "tms.programar" => array("modulo" => "tms", "accion" => "programar"),
    "tms.operar" => array("modulo" => "tms", "accion" => "operar"),
    "tms.evidencias" => array("modulo" => "tms", "accion" => "evidencias"),
    "tms.costos" => array("modulo" => "tms", "accion" => "costos"),
    "tms.autorizar" => array("modulo" => "tms", "accion" => "autorizar"),
    "tms.reportes" => array("modulo" => "tms", "accion" => "reportes")
  );
}

function tms_roles_permisos_esperados() {
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

function consultar_permisos_tms($db, $permisos) {
  $resultado = array();
  $stmt = $db->prepare("SELECT permiso, modulo, accion, estatus FROM sys_permisos WHERE permiso=:permiso LIMIT 1");
  foreach ($permisos as $permiso) {
    $stmt->execute(array(":permiso" => $permiso));
    $fila = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($fila) {
      $resultado[$permiso] = $fila;
    }
  }
  return $resultado;
}

function consultar_roles_tms($db, $roles) {
  $resultado = array();
  $stmt = $db->prepare("SELECT sr.rol, sp.permiso
    FROM sys_roles sr
    LEFT JOIN sys_roles_permisos rp ON rp.id_rol=sr.id_rol
    LEFT JOIN sys_permisos sp ON sp.id_permiso=rp.id_permiso AND sp.permiso LIKE 'tms.%'
    WHERE sr.rol=:rol
    ORDER BY sp.permiso ASC");
  foreach ($roles as $rol) {
    $stmt->execute(array(":rol" => $rol));
    $resultado[$rol] = array_values(array_filter(array_map(function ($fila) {
      return isset($fila["permiso"]) ? $fila["permiso"] : null;
    }, $stmt->fetchAll(PDO::FETCH_ASSOC))));
  }
  return $resultado;
}

function auditar_menu_tms() {
  $ruta = realpath(__DIR__ . "/../../app/vistas/includes/header/sidebar.php");
  $contenido = $ruta && file_exists($ruta) ? file_get_contents($ruta) : "";
  $checks = array(
    "modulo_tms" => strpos($contenido, "'TMS' => array('icono' => 'bi-truck')") !== false,
    "grupo_delivery" => strpos($contenido, "'seccion' => 'TMS'") !== false && strpos($contenido, "'titulo' => 'Delivery'") !== false,
    "bandeja_tms" => strpos($contenido, "'ruta' => '/tms/servicios'") !== false && strpos($contenido, "'permiso' => 'tms.ver'") !== false,
    "operacion" => strpos($contenido, "'ruta' => '/tms/operacion'") !== false && strpos($contenido, "'permiso' => 'tms.operar'") !== false,
    "costos" => strpos($contenido, "'ruta' => '/tms/costos'") !== false && strpos($contenido, "'permiso' => 'tms.costos'") !== false,
    "reportes" => strpos($contenido, "'ruta' => '/tms/reportes'") !== false && strpos($contenido, "'permiso' => 'tms.reportes'") !== false,
    "configuracion" => strpos($contenido, "'ruta' => '/tms/configuracion'") !== false && strpos($contenido, "'permiso' => 'tms.autorizar'") !== false
  );
  $fallos = array_keys(array_filter($checks, function ($ok) {
    return !$ok;
  }));
  return array("ok" => empty($fallos), "checks" => $checks, "fallos" => $fallos);
}
