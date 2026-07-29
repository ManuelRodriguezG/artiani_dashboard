<?php

/**
 * IA: Codex GPT-5
 * Fecha: 2026-07-28
 * Proposito: validar contrato read-only para futura integracion POS -> TMS.
 * Impacto: TMS Delivery y Ventas/POS; confirma puntos de enganche sin escribir BD ni modificar UI POS.
 * Contrato: read-only; no crea servicios TMS, no confirma ventas, no cobra, no mueve inventario.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/modelos/TmsDelivery.php";

$root = realpath(__DIR__ . "/../..");
$checks = array();

$archivos = array(
  "plan_pos_tms" => "docs/erp_tms_delivery_integracion_pos_plan.md",
  "controlador_tms" => "app/controladores/Tms.php",
  "modelo_tms" => "app/modelos/TmsDelivery.php",
  "controlador_ventas" => "app/controladores/Ventas.php",
  "modelo_ventas" => "app/modelos/VentasErp.php",
  "vista_pos" => "app/vistas/paginas/apps/erp/ventas/pos.php",
  "js_pos" => "public/assets/js/custom/apps/erp/ventas/pos.js"
);

foreach ($archivos as $clave => $relativa) {
  $checks["archivo_" . $clave] = check_item(file_exists($root . "/" . $relativa), $relativa);
}

$plan = contenido($root . "/docs/erp_tms_delivery_integracion_pos_plan.md");
$ventasCtrl = contenido($root . "/app/controladores/Ventas.php");
$ventasModelo = contenido($root . "/app/modelos/VentasErp.php");
$posJs = contenido($root . "/public/assets/js/custom/apps/erp/ventas/pos.js");
$tmsCtrl = contenido($root . "/app/controladores/Tms.php");
$tmsModelo = contenido($root . "/app/modelos/TmsDelivery.php");

$checks["plan_separa_dominios"] = check_item(strpos($plan, "TMS Delivery se mantiene como modulo completo e independiente") !== false, "Decision TMS independiente");
$checks["plan_pos_solo_solicitante"] = check_item(strpos($plan, "POS/Ventas puede solicitar un servicio logistico") !== false, "POS como solicitante");
$checks["plan_no_producto_envio"] = check_item(strpos($plan, "no convierte el envio en producto") !== false, "Envio no es producto");
$checks["plan_no_venta_tms"] = check_item(strpos($plan, "estatus de venta") !== false && strpos($plan, "cancelacion de venta") !== false, "TMS no gobierna venta");
$checks["plan_no_garantia_tms"] = check_item(strpos($plan, "aplicacion de garantia") !== false, "TMS no decide garantia");
$checks["plan_no_inventario_tms"] = check_item(strpos($plan, "salida de inventario") !== false, "TMS no mueve inventario");
$checks["plan_punto_seguro_confirmacion"] = check_item(strpos($plan, "despues de `VentasErp::confirmarVentaPosReal`") !== false, "Punto seguro despues de venta real");
$checks["plan_adapter_dryrun_primero"] = check_item(strpos($plan, "adapter dry-run") !== false, "Adapter dry-run primero");

$checks["ventas_endpoint_dryrun"] = check_item(preg_match('/public function\s+pos_confirmar_dryrun_erp\s*\(/', $ventasCtrl) === 1, "Ventas::pos_confirmar_dryrun_erp");
$checks["ventas_endpoint_confirmar"] = check_item(preg_match('/public function\s+pos_confirmar_erp\s*\(/', $ventasCtrl) === 1, "Ventas::pos_confirmar_erp");
$checks["ventas_endpoint_pedido_dryrun"] = check_item(preg_match('/public function\s+pedido_reserva_dryrun_erp\s*\(/', $ventasCtrl) === 1, "Ventas::pedido_reserva_dryrun_erp");
$checks["ventas_endpoint_pedido_real"] = check_item(preg_match('/public function\s+pedido_guardar_erp\s*\(/', $ventasCtrl) === 1, "Ventas::pedido_guardar_erp");
$checks["ventas_modelo_dryrun"] = check_item(preg_match('/public function\s+confirmarVentaPosDryRun\s*\(/', $ventasModelo) === 1, "VentasErp::confirmarVentaPosDryRun");
$checks["ventas_modelo_real"] = check_item(preg_match('/public function\s+confirmarVentaPosReal\s*\(/', $ventasModelo) === 1, "VentasErp::confirmarVentaPosReal");
$checks["ventas_modelo_pedido_dryrun"] = check_item(preg_match('/public function\s+pedidoReservaDryRun\s*\(/', $ventasModelo) === 1, "VentasErp::pedidoReservaDryRun");
$checks["ventas_modelo_transaccion_real"] = check_item(strpos($ventasModelo, '$db->beginTransaction();') !== false, "POS real usa transaccion");

$checks["pos_js_prevalidar"] = check_item(strpos($posJs, "/ventas/pos_carrito_prevalidar_erp") !== false, "POS JS prevalidar carrito");
$checks["pos_js_confirmar_dryrun"] = check_item(strpos($posJs, "/ventas/pos_confirmar_dryrun_erp") !== false, "POS JS confirmar dry-run");
$checks["pos_js_confirmar_real"] = check_item(strpos($posJs, "/ventas/pos_confirmar_erp") !== false, "POS JS confirmar real");
$checks["pos_js_tms_solo_dryrun"] = check_item(strpos($posJs, "/tms/servicio_pos_dryrun_erp") !== false && strpos($posJs, "/tms/servicio_guardar_erp") === false, "POS JS usa solo dry-run TMS");
$checks["ventas_ctrl_no_tms_activo"] = check_item(strpos($ventasCtrl, 'modelo("TmsDelivery")') === false && strpos($ventasCtrl, "/tms/") === false, "Ventas controller sin escritura TMS");

$vistaPos = contenido($root . "/app/vistas/paginas/apps/erp/ventas/pos.php");
foreach (array("pos_tms_panel", "pos_tms_activo", "pos_tms_tipo", "pos_tms_prioridad", "pos_tms_direccion", "pos_tms_cobro", "pos_tms_precio", "pos_tms_dryrun", "pos_tms_resultado") as $idPosTms) {
  $checks["pos_ui_" . $idPosTms] = check_item(strpos($vistaPos, 'id="' . $idPosTms . '"') !== false, "POS UI #" . $idPosTms);
}
$checks["pos_payload_tms_no_confirmar"] = check_item(strpos($posJs, "payloadTmsPosDryRun") !== false && strpos($posJs, "pos_confirmar_erp\", payloadTmsPosDryRun") === false, "Payload TMS separado de cobro POS");

$payloadContrato = array(
  "solicitado_por_modulo" => "ventas",
  "solicitado_por_tipo" => "pos_venta",
  "solicitado_por_id" => 123,
  "referencia_externa" => "VPOS-UAT-CONTRATO",
  "tipo_servicio" => "entrega_express",
  "prioridad" => "express",
  "estatus_cobro" => "por_cobrar",
  "precio_cobrado" => 75,
  "cliente_nombre_snapshot" => "Cliente POS Contrato",
  "cliente_contacto_snapshot" => "3312345678",
  "direccion_snapshot" => "Direccion POS Contrato",
  "zona_snapshot" => "Zona POS Contrato",
  "detalle" => array(
    array(
      "descripcion_snapshot" => "Paquete POS contrato",
      "cantidad" => 1,
      "referencia_detalle_externa" => "renglon-1"
    )
  )
);

$modelo = new TmsDelivery();
$dryrun = $modelo->servicioDryRun($payloadContrato);
$checks["tms_dryrun_payload_pos"] = check_item(isset($dryrun["depurar"]["puede_guardar_futuro"]) && $dryrun["depurar"]["puede_guardar_futuro"] === true, "TMS acepta payload POS como dry-run");
$checks["tms_controlador_guardar"] = check_item(preg_match('/public function\s+servicio_guardar_erp\s*\(/', $tmsCtrl) === 1, "Tms::servicio_guardar_erp");
$checks["tms_modelo_guardar"] = check_item(preg_match('/public function\s+guardarServicio\s*\(/', $tmsModelo) === 1, "TmsDelivery::guardarServicio");
$checks["tms_controlador_pos_dryrun"] = check_item(preg_match('/public function\s+servicio_pos_dryrun_erp\s*\(/', $tmsCtrl) === 1, "Tms::servicio_pos_dryrun_erp");
$checks["tms_modelo_pos_dryrun"] = check_item(preg_match('/public function\s+servicioDesdePosDryRun\s*\(/', $tmsModelo) === 1, "TmsDelivery::servicioDesdePosDryRun");

$dryrunPos = $modelo->servicioDesdePosDryRun($payloadContrato);
$checks["tms_adapter_pos_dryrun"] = check_item(isset($dryrunPos["depurar"]["puede_guardar_futuro"]) && $dryrunPos["depurar"]["puede_guardar_futuro"] === true, "Adapter POS -> TMS valido en dry-run");
$checks["tms_adapter_pos_regla_no_venta"] = check_item(isset($dryrunPos["depurar"]["reglas_pos_tms"]["fallo_entrega_no_cancela_venta"]) && $dryrunPos["depurar"]["reglas_pos_tms"]["fallo_entrega_no_cancela_venta"] === true, "Fallo entrega no cancela venta");

$fallos = array_values(array_filter($checks, function ($item) {
  return empty($item["ok"]);
}));

echo json_encode(array(
  "ok" => empty($fallos),
  "modo" => "read-only",
  "estado" => empty($fallos) ? "pos_tms_integracion_planificada" : "pos_tms_contrato_pendiente",
  "checks_total" => count($checks),
  "checks_ok" => count($checks) - count($fallos),
  "checks_fallos" => count($fallos),
  "fallos" => $fallos,
  "payload_contrato" => $payloadContrato,
  "tms_dryrun" => $dryrun,
  "tms_pos_dryrun" => $dryrunPos,
  "reglas" => array(
    "read_only" => true,
    "no_crea_servicios_tms" => true,
    "no_confirma_ventas" => true,
    "no_cobra_productos" => true,
    "pos_ui_solo_dryrun" => true,
    "no_mueve_inventario" => true,
    "no_decide_garantias" => true
  ),
  "siguiente_paso" => "Validar UI POS opt-in en navegador; despues preparar UAT de creacion real POS -> TMS con autorizacion separada."
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

function check_item($ok, $detalle) {
  return array("ok" => (bool) $ok, "detalle" => $detalle);
}

function contenido($ruta) {
  return file_exists($ruta) ? file_get_contents($ruta) : "";
}
