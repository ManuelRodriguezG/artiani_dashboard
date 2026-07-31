<?php

/**
 * IA: Codex GPT-5
 * Fecha: 2026-07-30
 * Proposito: verificar salud modular CRM sin escribir BD.
 * Impacto: confirma rutas, archivos, permisos y matriz base por rol para UAT visual.
 * Contrato: read-only; no modifica clientes, permisos, roles, POS, ventas ni ecommerce.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";

$base = realpath(__DIR__ . "/../..");
$archivos = array(
  "controlador_crm" => "app/controladores/Crm.php",
  "sidebar" => "app/vistas/includes/header/sidebar.php",
  "clientes_vista" => "app/vistas/paginas/apps/crm/clientes/listado.php",
  "clientes_js" => "public/assets/js/custom/apps/crm/clientes/listado.js",
  "seguimiento_vista" => "app/vistas/paginas/apps/crm/seguimiento/index.php",
  "seguimiento_js" => "public/assets/js/custom/apps/crm/seguimiento/index.js",
  "comercial_vista" => "app/vistas/paginas/apps/crm/comercial/index.php",
  "comercial_js" => "public/assets/js/custom/apps/crm/comercial/index.js",
  "recompensas_vista" => "app/vistas/paginas/apps/crm/recompensas/index.php",
  "recompensas_js" => "public/assets/js/custom/apps/crm/recompensas/index.js",
  "reportes_vista" => "app/vistas/paginas/apps/crm/reportes/index.php",
  "reportes_js" => "public/assets/js/custom/apps/crm/reportes/index.js",
  "estado_actual" => "docs/crm_clientes_estado_actual.md"
);

$metodos = array("clientes", "seguimiento", "comercial", "recompensas", "reportes", "cliente");
$permisos = array(
  "crm.clientes.ver",
  "crm.clientes.editar",
  "crm.seguimiento.ver",
  "crm.seguimiento.operar",
  "crm.comercial.ver",
  "crm.comercial.operar",
  "crm.recompensas.ver",
  "crm.recompensas.operar",
  "crm.reportes.ver",
  "crm.pos.buscar",
  "crm.pos.alta_express"
);
$matrizRoles = array(
  "ventas" => array("crm.pos.buscar", "crm.pos.alta_express"),
  "direccion" => array("crm.clientes.ver", "crm.seguimiento.ver", "crm.comercial.ver", "crm.recompensas.ver", "crm.reportes.ver"),
  "crm" => array("crm.clientes.ver", "crm.clientes.editar", "crm.seguimiento.ver", "crm.seguimiento.operar", "crm.comercial.ver", "crm.comercial.operar", "crm.recompensas.ver", "crm.recompensas.operar", "crm.reportes.ver"),
  "administrador_erp" => array("crm.clientes.ver", "crm.clientes.editar", "crm.seguimiento.ver", "crm.seguimiento.operar", "crm.comercial.ver", "crm.comercial.operar", "crm.recompensas.ver", "crm.recompensas.operar", "crm.reportes.ver")
);

$db = (new class extends CRUD {
  public function conexion() {
    return $this->getConexion();
  }
})->conexion();

$resultadoArchivos = verificar_archivos_crm($base, $archivos);
$resultadoMetodos = verificar_metodos_crm($base, $metodos);
$resultadoPermisos = $db ? verificar_permisos_crm($db, $permisos) : array();
$resultadoRoles = $db ? verificar_roles_crm($db, $matrizRoles) : array();

$pendientes = array(
  "archivos_faltantes" => array_values(array_filter($resultadoArchivos, function ($item) { return !$item["existe"]; })),
  "metodos_faltantes" => array_values(array_filter($resultadoMetodos, function ($item) { return !$item["existe"]; })),
  "permisos_faltantes" => array_values(array_filter($resultadoPermisos, function ($item) { return !$item["existe"]; })),
  "roles_faltantes" => array_values(array_filter($resultadoRoles, function ($item) { return !$item["rol_existe"]; })),
  "relaciones_faltantes" => relaciones_faltantes_crm($resultadoRoles)
);

echo json_encode(array(
  "ok" => empty($pendientes["archivos_faltantes"]) && empty($pendientes["metodos_faltantes"]) && empty($pendientes["permisos_faltantes"]) && empty($pendientes["roles_faltantes"]) && empty($pendientes["relaciones_faltantes"]),
  "modo" => "read-only",
  "alcance" => "CRM modular: clientes, seguimiento, comercial, recompensas, reportes y POS fino",
  "archivos" => $resultadoArchivos,
  "metodos_controlador" => $resultadoMetodos,
  "permisos" => $resultadoPermisos,
  "roles" => $resultadoRoles,
  "pendientes" => $pendientes,
  "siguiente_paso" => "Probar visualmente con roles ventas, crm y direccion en http://panel.com.local/."
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

function verificar_archivos_crm($base, $archivos) {
  $resultado = array();
  foreach ($archivos as $clave => $relativo) {
    $ruta = $base . DIRECTORY_SEPARATOR . str_replace("/", DIRECTORY_SEPARATOR, $relativo);
    $resultado[] = array(
      "clave" => $clave,
      "ruta" => $relativo,
      "existe" => file_exists($ruta),
      "bytes" => file_exists($ruta) ? filesize($ruta) : null
    );
  }
  return $resultado;
}

function verificar_metodos_crm($base, $metodos) {
  $ruta = $base . DIRECTORY_SEPARATOR . "app" . DIRECTORY_SEPARATOR . "controladores" . DIRECTORY_SEPARATOR . "Crm.php";
  $contenido = file_exists($ruta) ? file_get_contents($ruta) : "";
  $resultado = array();
  foreach ($metodos as $metodo) {
    $resultado[] = array(
      "metodo" => $metodo,
      "existe" => preg_match('/public\s+function\s+' . preg_quote($metodo, '/') . '\s*\(/', $contenido) === 1
    );
  }
  return $resultado;
}

function verificar_permisos_crm($db, $permisos) {
  $stmt = $db->prepare("SELECT permiso, modulo, accion, estatus FROM sys_permisos WHERE permiso=:permiso LIMIT 1");
  $resultado = array();
  foreach ($permisos as $permiso) {
    $stmt->execute(array(":permiso" => $permiso));
    $fila = $stmt->fetch(PDO::FETCH_ASSOC);
    $resultado[] = array(
      "permiso" => $permiso,
      "existe" => (bool) $fila,
      "estatus" => $fila ? $fila["estatus"] : null
    );
  }
  return $resultado;
}

function verificar_roles_crm($db, $matrizRoles) {
  $resultado = array();
  $stmtRol = $db->prepare("SELECT id_rol, rol FROM sys_roles WHERE rol=:rol LIMIT 1");
  $stmtPermisos = $db->prepare("SELECT sp.permiso
                                FROM sys_roles sr
                                INNER JOIN sys_roles_permisos srp ON srp.id_rol=sr.id_rol
                                INNER JOIN sys_permisos sp ON sp.id_permiso=srp.id_permiso AND sp.estatus=1
                                WHERE sr.rol=:rol
                                ORDER BY sp.permiso");
  foreach ($matrizRoles as $rol => $esperados) {
    $stmtRol->execute(array(":rol" => $rol));
    $rolFila = $stmtRol->fetch(PDO::FETCH_ASSOC);
    $actuales = array();
    if ($rolFila) {
      $stmtPermisos->execute(array(":rol" => $rol));
      $actuales = array_map(function ($fila) { return $fila["permiso"]; }, $stmtPermisos->fetchAll(PDO::FETCH_ASSOC));
    }
    $resultado[] = array(
      "rol" => $rol,
      "rol_existe" => (bool) $rolFila,
      "esperados" => $esperados,
      "actuales_crm_relevantes" => array_values(array_filter($actuales, function ($permiso) {
        return strpos($permiso, "crm.") === 0;
      })),
      "faltantes" => array_values(array_diff($esperados, $actuales))
    );
  }
  return $resultado;
}

function relaciones_faltantes_crm($roles) {
  $faltantes = array();
  foreach ($roles as $rol) {
    if (!empty($rol["faltantes"])) {
      $faltantes[$rol["rol"]] = $rol["faltantes"];
    }
  }
  return $faltantes;
}
