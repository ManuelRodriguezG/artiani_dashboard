<?php

/**
 * IA: Codex GPT-5
 * Fecha: 2026-07-28
 * Proposito: preflight read-only para futura creacion real POS -> TMS.
 * Impacto: TMS Delivery y POS; valida dependencias sin ejecutar venta, cobro ni servicio real.
 * Contrato: read-only; no crea servicios TMS, no confirma ventas, no toca caja ni inventario.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/core/DBSchema.php";
require_once __DIR__ . "/../../app/modelos/TmsEsquema.php";
require_once __DIR__ . "/../../app/modelos/TmsDelivery.php";

$root = realpath(__DIR__ . "/../..");
$checks = array();

$archivos = array(
  "solicitud_autorizacion" => "docs/erp_tms_delivery_pos_real_solicitud_autorizacion.md",
  "plan_integracion" => "docs/erp_tms_delivery_integracion_pos_plan.md",
  "controlador_tms" => "app/controladores/Tms.php",
  "modelo_tms" => "app/modelos/TmsDelivery.php",
  "controlador_ventas" => "app/controladores/Ventas.php",
  "modelo_ventas" => "app/modelos/VentasErp.php",
  "vista_pos" => "app/vistas/paginas/apps/erp/ventas/pos.php",
  "js_pos" => "public/assets/js/custom/apps/erp/ventas/pos.js",
  "uat_pos_contract" => "storage/uat/uat_tms_delivery_pos_contract_readonly.php",
  "uat_pos_ui" => "storage/uat/uat_tms_delivery_pos_ui_readonly.php"
);

foreach ($archivos as $clave => $relativa) {
  $checks["archivo_" . $clave] = check_item(file_exists($root . "/" . $relativa), $relativa);
}

$solicitud = contenido($root . "/docs/erp_tms_delivery_pos_real_solicitud_autorizacion.md");
$ventasCtrl = contenido($root . "/app/controladores/Ventas.php");
$ventasModelo = contenido($root . "/app/modelos/VentasErp.php");
$posJs = contenido($root . "/public/assets/js/custom/apps/erp/ventas/pos.js");
$tmsCtrl = contenido($root . "/app/controladores/Tms.php");
$tmsModelo = contenido($root . "/app/modelos/TmsDelivery.php");

$checks["solicitud_token"] = check_item(strpos($solicitud, "TMS_POS_REAL_BASE") !== false, "Token futuro documentado");
$checks["solicitud_respaldo"] = check_item(strpos($solicitud, "C:\\xampp\\panel_db_backups") !== false, "Respaldo externo requerido");
$checks["solicitud_frase_autorizacion"] = check_item(strpos($solicitud, "AUTORIZO EJECUTAR UAT REAL POS TMS DELIVERY") !== false, "Frase futura documentada");
$checks["solicitud_fuera_alcance"] = check_item(strpos($solicitud, "No mover kardex desde TMS") !== false && strpos($solicitud, "No cobrar productos desde TMS") !== false, "Fuera de alcance protegido");

$checks["tms_endpoint_pos_dryrun"] = check_item(preg_match('/public function\s+servicio_pos_dryrun_erp\s*\(/', $tmsCtrl) === 1, "Endpoint POS dry-run TMS");
$checks["tms_modelo_pos_dryrun"] = check_item(preg_match('/public function\s+servicioDesdePosDryRun\s*\(/', $tmsModelo) === 1, "Modelo POS dry-run TMS");
$checks["tms_no_endpoint_pos_real"] = check_item(preg_match('/public function\s+servicio_pos_guardar_erp\s*\(/', $tmsCtrl) !== 1, "Sin endpoint POS real TMS todavia");
$checks["ventas_no_tms_modelo"] = check_item(strpos($ventasCtrl, 'modelo("TmsDelivery")') === false && strpos($ventasModelo, "new TmsDelivery") === false, "Ventas no escribe TMS todavia");
$checks["pos_js_solo_dryrun"] = check_item(strpos($posJs, "/tms/servicio_pos_dryrun_erp") !== false && strpos($posJs, "/tms/servicio_guardar_erp") === false, "POS JS solo dry-run TMS");

$esquema = new TmsEsquema();
$auditoria = $esquema->auditarTmsDelivery();
$checks["schema_tms_listo"] = check_item(isset($auditoria["depurar"]["tiene_pendientes"]) && $auditoria["depurar"]["tiene_pendientes"] === false, "Schema TMS aplicado");

$modelo = new TmsDelivery();
$dryrun = $modelo->servicioDesdePosDryRun(array(
  "solicitado_por_tipo" => "pos_venta",
  "solicitado_por_id" => 999,
  "referencia_externa" => "VPOS-PREFLIGHT",
  "tipo_servicio" => "entrega_express",
  "prioridad" => "express",
  "estatus_cobro" => "por_cobrar",
  "precio_cobrado" => 75,
  "cliente_nombre_snapshot" => "Cliente Preflight POS TMS",
  "cliente_contacto_snapshot" => "3312345678",
  "direccion_snapshot" => "Direccion Preflight POS TMS",
  "zona_snapshot" => "Zona Preflight",
  "detalle" => json_encode(array(
    array("descripcion_snapshot" => "Paquete preflight POS TMS", "cantidad" => 1)
  ))
));
$checks["dryrun_pos_tms_valido"] = check_item(isset($dryrun["depurar"]["puede_guardar_futuro"]) && $dryrun["depurar"]["puede_guardar_futuro"] === true, "Dry-run POS/TMS valido");

$fallos = array_values(array_filter($checks, function ($item) {
  return empty($item["ok"]);
}));

echo json_encode(array(
  "ok" => empty($fallos),
  "modo" => "read-only",
  "estado" => empty($fallos) ? "pos_tms_real_preflight_listo" : "pos_tms_real_preflight_bloqueado",
  "checks_total" => count($checks),
  "checks_ok" => count($checks) - count($fallos),
  "checks_fallos" => count($fallos),
  "fallos" => $fallos,
  "token_futuro" => "TMS_POS_REAL_BASE",
  "requiere_respaldo_externo" => true,
  "dryrun" => $dryrun,
  "reglas" => array(
    "read_only" => true,
    "no_crea_tms" => true,
    "no_confirma_ventas" => true,
    "no_toca_caja" => true,
    "no_mueve_inventario" => true,
    "no_decide_garantias" => true
  ),
  "siguiente_paso" => "Validar navegador y solicitar autorizacion explicita antes de implementar/ejecutar creacion real POS -> TMS."
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

function check_item($ok, $detalle) {
  return array("ok" => (bool) $ok, "detalle" => $detalle);
}

function contenido($ruta) {
  return file_exists($ruta) ? file_get_contents($ruta) : "";
}
