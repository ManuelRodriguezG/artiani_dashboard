<?php

/**
 * IA: Codex GPT-5
 * Fecha: 2026-07-28
 * Proposito: validar contrato read-only para integracion POS -> TMS.
 * Impacto: TMS Delivery y Ventas/POS; confirma que POS captura TMS separado sin escribir desde Ventas.
 * Contrato: read-only; no crea servicios TMS, no toca operaciones comerciales, no cobra, no mueve inventario.
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
$checks["plan_tms_solo_logistica"] = check_item(strpos($plan, "recoger;") !== false && strpos($plan, "evidenciar;") !== false && strpos($plan, "cerrar;") !== false, "TMS solo compromiso logistico");
$checks["plan_pos_canal_captura"] = check_item(strpos($plan, "POS puede abrir una solicitud logistica") !== false, "POS como canal de captura");
$checks["plan_no_venta_obligatoria"] = check_item(strpos($plan, "referencia operativa opcional, no folio obligatorio de venta") !== false, "Sin folio de venta obligatorio");
$checks["plan_entrega_tercero"] = check_item(strpos($plan, "`entrega_tercero`") !== false, "Entrega por tercero explicada");
$checks["plan_adapter_dryrun"] = check_item(strpos($plan, "Adapter dry-run") !== false, "Adapter dry-run documentado");
$checks["plan_creacion_real"] = check_item(strpos($plan, "Creacion real desde POS") !== false || strpos($plan, "Creacion real autorizada") !== false, "Creacion real documentada");

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
$checks["pos_js_tms_dryrun_y_real"] = check_item(strpos($posJs, "/tms/servicio_pos_dryrun_erp") !== false && strpos($posJs, "/tms/servicio_guardar_erp") !== false, "POS JS prevalida y crea TMS");
$checks["pos_js_tms_post_pos"] = check_item(strpos($posJs, "renderCobroReal(response);") !== false && strpos($posJs, "crearTmsDesdePosReal(payloadTmsPendiente)") !== false, "TMS se crea despues de confirmar POS");
$checks["ventas_ctrl_no_tms_activo"] = check_item(strpos($ventasCtrl, 'modelo("TmsDelivery")') === false && strpos($ventasCtrl, "/tms/") === false, "Ventas controller sin escritura TMS");
$checks["ventas_modelo_no_tms_activo"] = check_item(strpos($ventasModelo, "TmsDelivery") === false && strpos($ventasModelo, "erp_tms_") === false, "Ventas modelo sin escritura TMS");
$checks["pos_js_solicitud_pos"] = check_item(strpos($posJs, 'solicitado_por_tipo: "solicitud_pos"') !== false && strpos($posJs, 'solicitado_por_tipo: "pos_venta"') === false, "POS envia solicitud_pos, no pos_venta");

$vistaPos = contenido($root . "/app/vistas/paginas/apps/erp/ventas/pos.php");
foreach (array("pos_tms_panel", "pos_tms_activo", "pos_tms_tipo", "pos_tms_prioridad", "pos_tms_direccion", "pos_tms_cobro", "pos_tms_precio", "pos_tms_dryrun", "pos_tms_resultado") as $idPosTms) {
  $checks["pos_ui_" . $idPosTms] = check_item(strpos($vistaPos, 'id="' . $idPosTms . '"') !== false, "POS UI #" . $idPosTms);
}
$checks["pos_payload_tms_no_confirmar"] = check_item(strpos($posJs, "payloadTmsPosDryRun") !== false && strpos($posJs, "pos_confirmar_erp\", payloadTmsPosDryRun") === false, "Payload TMS separado de cobro POS");

$payloadContrato = array(
  "solicitado_por_modulo" => "pos",
  "solicitado_por_tipo" => "solicitud_pos",
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
$checks["tms_adapter_pos_regla_logistica"] = check_item(isset($dryrunPos["depurar"]["reglas_pos_tms"]["tms_solo_compromiso_logistico"]) && $dryrunPos["depurar"]["reglas_pos_tms"]["tms_solo_compromiso_logistico"] === true, "TMS solo compromiso logistico");
$checks["tms_adapter_origen_pos"] = check_item(isset($dryrunPos["depurar"]["origen_pos"]["solicitado_por_modulo"]) && $dryrunPos["depurar"]["origen_pos"]["solicitado_por_modulo"] === "pos", "Origen logistico POS");

$fallos = array_values(array_filter($checks, function ($item) {
  return empty($item["ok"]);
}));

echo json_encode(array(
  "ok" => empty($fallos),
  "modo" => "read-only",
  "estado" => empty($fallos) ? "pos_tms_integracion_real_lista" : "pos_tms_contrato_pendiente",
  "checks_total" => count($checks),
  "checks_ok" => count($checks) - count($fallos),
  "checks_fallos" => count($fallos),
  "fallos" => $fallos,
  "payload_contrato" => $payloadContrato,
  "tms_dryrun" => $dryrun,
  "tms_pos_dryrun" => $dryrunPos,
  "reglas" => array(
    "read_only" => true,
    "creacion_real_solo_desde_pos_js" => true,
    "no_toca_operaciones_comerciales" => true,
    "no_cobra_productos" => true,
    "pos_ui_dryrun_y_guardado_tms" => true,
    "no_mueve_inventario" => true,
    "sin_garantias_o_postventa" => true
  ),
  "siguiente_paso" => "Validar UI POS opt-in en navegador; despues ejecutar UAT real controlado POS -> TMS con autorizacion separada."
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

function check_item($ok, $detalle) {
  return array("ok" => (bool) $ok, "detalle" => $detalle);
}

function contenido($ruta) {
  return file_exists($ruta) ? file_get_contents($ruta) : "";
}
