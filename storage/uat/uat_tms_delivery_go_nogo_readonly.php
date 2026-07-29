<?php

/**
 * IA: Codex GPT-5
 * Fecha: 2026-07-24
 * Proposito: consolidar readiness go/no-go de TMS Delivery antes de permisos/DDL.
 * Impacto: TMS Delivery; resume codigo, sidebar, permisos, esquema y scripts autorizados.
 * Contrato: read-only; no aplica permisos, no ejecuta DDL y no crea servicios.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/core/DBSchema.php";
require_once __DIR__ . "/../../app/modelos/TmsEsquema.php";
require_once __DIR__ . "/../../app/modelos/TmsDelivery.php";

$root = realpath(__DIR__ . "/../..");
$checks = array();
$detalleCompleto = in_array("--detalle=1", $argv, true);

$archivos = array(
  "controlador" => "app/controladores/Tms.php",
  "modelo_dominio" => "app/modelos/TmsDelivery.php",
  "modelo_esquema" => "app/modelos/TmsEsquema.php",
  "vista_servicios" => "app/vistas/paginas/apps/tms/servicios.php",
  "vista_operacion" => "app/vistas/paginas/apps/tms/operacion.php",
  "vista_costos" => "app/vistas/paginas/apps/tms/costos.php",
  "vista_reportes" => "app/vistas/paginas/apps/tms/reportes.php",
  "vista_configuracion" => "app/vistas/paginas/apps/tms/configuracion.php",
  "js_servicios" => "public/assets/js/custom/apps/tms/servicios.js",
  "js_operacion" => "public/assets/js/custom/apps/tms/operacion.js",
  "js_costos" => "public/assets/js/custom/apps/tms/costos.js",
  "js_reportes" => "public/assets/js/custom/apps/tms/reportes.js",
  "js_configuracion" => "public/assets/js/custom/apps/tms/configuracion.js",
  "activacion_checklist_readonly" => "storage/uat/uat_tms_delivery_activacion_checklist_readonly.php",
  "preactivacion_readonly" => "storage/uat/uat_tms_delivery_preactivacion_readonly.php",
  "permisos_postapply_readonly" => "storage/uat/uat_tms_delivery_permisos_postapply_readonly.php",
  "schema_postapply_readonly" => "storage/uat/uat_tms_delivery_schema_postapply_readonly.php",
  "reversa_preflight_readonly" => "storage/uat/uat_tms_delivery_reversa_preflight_readonly.php",
  "ui_datos_readonly" => "storage/uat/uat_tms_delivery_ui_datos_readonly.php",
  "pos_contract_readonly" => "storage/uat/uat_tms_delivery_pos_contract_readonly.php",
  "pos_ui_readonly" => "storage/uat/uat_tms_delivery_pos_ui_readonly.php",
  "pos_real_preflight_readonly" => "storage/uat/uat_tms_delivery_pos_real_preflight_readonly.php",
  "servicio_manual_apply" => "storage/uat/uat_tms_delivery_servicio_manual_apply_authorized.php",
  "apply_permisos" => "storage/uat/uat_tms_delivery_permisos_apply_authorized.php",
  "apply_schema" => "storage/uat/uat_tms_delivery_schema_apply_authorized.php"
);

foreach ($archivos as $clave => $relativa) {
  $checks["archivo_" . $clave] = check_item(file_exists($root . "/" . $relativa), $relativa);
}

$controlador = contenido($root . "/app/controladores/Tms.php");
$metodos = array(
  "servicios", "operacion", "costos", "reportes", "configuracion",
  "servicio_guardar_erp", "servicio_accion_erp", "evidencias_listar_erp",
  "evidencia_registrar_erp", "evidencia_cancelar_erp", "reportes_resumen_erp",
  "servicio_pos_dryrun_erp"
);
foreach ($metodos as $metodo) {
  $checks["controlador_" . $metodo] = check_item(preg_match('/public function\s+' . preg_quote($metodo, '/') . '\s*\(/', $controlador) === 1, $metodo);
}

$sidebar = contenido($root . "/app/vistas/includes/header/sidebar.php");
$checks["sidebar_modulo_tms"] = check_item(strpos($sidebar, "'TMS' => array('icono' => 'bi-truck')") !== false, "Modulo padre TMS");
$checks["sidebar_delivery"] = check_item(strpos($sidebar, "'seccion' => 'TMS'") !== false && strpos($sidebar, "'titulo' => 'Delivery'") !== false, "Grupo TMS > Delivery");

$seguridad = contenido($root . "/app/modelos/SeguridadEsquema.php");
$permisos = array("tms.ver", "tms.crear", "tms.programar", "tms.operar", "tms.evidencias", "tms.costos", "tms.autorizar", "tms.reportes");
foreach ($permisos as $permiso) {
  $checks["permiso_codigo_" . str_replace(".", "_", $permiso)] = check_item(strpos($seguridad, '"' . $permiso . '"') !== false, $permiso);
}

$modeloEsquema = new TmsEsquema();
$auditoria = $modeloEsquema->auditarTmsDelivery();
$plan = $modeloEsquema->planActualizarTmsDelivery(false);
$checks["schema_auditoria_readonly"] = check_item(isset($auditoria["depurar"]["tablas"]) && count($auditoria["depurar"]["tablas"]) === 5, "Auditoria TMS disponible");
$checks["schema_plan_readonly"] = check_item(isset($plan["depurar"]["resumen"]["total"]) && intval($plan["depurar"]["resumen"]["total"]) === 5, "Plan DDL TMS disponible");

$modelo = new TmsDelivery();
$listado = $modelo->listarServicios(array("limite" => 5));
$dryrun = $modelo->servicioDryRun(array(
  "tipo_servicio" => "entrega_express",
  "prioridad" => "express",
  "estatus_cobro" => "por_cobrar",
  "solicitado_por_modulo" => "manual",
  "solicitado_por_tipo" => "solicitud_manual",
  "cliente_nombre_snapshot" => "Cliente Go No-Go",
  "cliente_contacto_snapshot" => "3312345678",
  "direccion_snapshot" => "Direccion Go No-Go"
));
$reportes = $modelo->resumenReportes(array());

$checks["dominio_listado_controlado"] = check_item(isset($listado["depurar"]["servicios"]) && is_array($listado["depurar"]["servicios"]), "Listado TMS controlado");
$checks["dominio_dryrun_valido"] = check_item(isset($dryrun["depurar"]["puede_guardar_futuro"]) && $dryrun["depurar"]["puede_guardar_futuro"] === true, "Dry-run valido");
$checks["reportes_controlados"] = check_item(isset($reportes["depurar"]["kpis"]) && is_array($reportes["depurar"]["kpis"]), "Reportes controlados");

$db = (new class extends CRUD {
  public function conexion() {
    return $this->getConexion();
  }
})->conexion();
$permisosDb = auditar_permisos_db($db, $permisos);
$schemaPendiente = isset($auditoria["depurar"]["tiene_pendientes"]) ? (bool) $auditoria["depurar"]["tiene_pendientes"] : true;
$permisosPendientes = array_values(array_filter($permisosDb, function ($item) {
  return empty($item["existe"]);
}));

$fallos = array_values(array_filter($checks, function ($item) {
  return empty($item["ok"]);
}));
$estado = empty($fallos) ? "go_preparacion" : "no_go_codigo";
if (empty($fallos) && (!empty($permisosPendientes) || $schemaPendiente)) {
  $estado = "go_con_activaciones_pendientes";
}
$siguientePaso = "Activacion base TMS completa, UI/datos validados, UI POS/TMS dry-run lista y preflight real futuro preparado. Validar en navegador antes de creacion real autorizada.";
if (!empty($permisosPendientes)) {
  $siguientePaso = "Generar respaldo externo y aplicar primero permisos TMS; DDL TMS queda en autorizacion separada.";
} elseif ($schemaPendiente) {
  $siguientePaso = "Permisos TMS listos. Generar respaldo externo y solicitar autorizacion separada TMS_DELIVERY_DDL_BASE para crear tablas erp_tms_*.";
}

$respuesta = array(
  "ok" => empty($fallos),
  "modo" => "read-only",
  "estado" => $estado,
  "checks_total" => count($checks),
  "checks_ok" => count($checks) - count($fallos),
  "checks_fallos" => count($fallos),
  "fallos" => $fallos,
  "bd" => array(
    "conexion" => (bool) $db,
    "permisos_tms_pendientes" => count($permisosPendientes),
    "schema_tms_pendiente" => $schemaPendiente
  ),
  "tokens" => array(
    "permisos" => "TMS_PERMISOS_BASE",
    "ddl" => "TMS_DELIVERY_DDL_BASE"
  ),
  "siguiente_paso" => $siguientePaso
);

if ($detalleCompleto) {
  $respuesta["checks"] = $checks;
  $respuesta["bd"]["permisos"] = $permisosDb;
}

echo json_encode($respuesta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

function check_item($ok, $detalle) {
  return array("ok" => (bool) $ok, "detalle" => $detalle);
}

function contenido($ruta) {
  return file_exists($ruta) ? file_get_contents($ruta) : "";
}

function auditar_permisos_db($db, $permisos) {
  $resultado = array();
  if (!$db) {
    foreach ($permisos as $permiso) {
      $resultado[] = array("permiso" => $permiso, "existe" => false, "registro" => null);
    }
    return $resultado;
  }
  $stmt = $db->prepare("SELECT permiso, modulo, accion, estatus FROM sys_permisos WHERE permiso=:permiso LIMIT 1");
  foreach ($permisos as $permiso) {
    $stmt->execute(array(":permiso" => $permiso));
    $fila = $stmt->fetch(PDO::FETCH_ASSOC);
    $resultado[] = array("permiso" => $permiso, "existe" => (bool) $fila, "registro" => $fila ?: null);
  }
  return $resultado;
}
