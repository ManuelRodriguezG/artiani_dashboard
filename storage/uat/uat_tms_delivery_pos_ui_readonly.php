<?php

/**
 * IA: Codex GPT-5
 * Fecha: 2026-07-28
 * Proposito: validar la UI POS opt-in de Entrega TMS con prevalidacion y creacion real separada.
 * Impacto: POS y TMS Delivery; confirma que la UI opera un servicio logistico separado.
 * Contrato: read-only; inspecciona codigo, no crea servicios TMS, no toca operaciones comerciales, no cobra y no mueve inventario.
 */

$root = realpath(__DIR__ . "/../..");
$checks = array();

$vista = contenido($root . "/app/vistas/paginas/apps/erp/ventas/pos.php");
$js = contenido($root . "/public/assets/js/custom/apps/erp/ventas/pos.js");

foreach (array(
  "pos_tms_panel",
  "pos_tms_activo",
  "pos_tms_campos",
  "pos_tms_tipo",
  "pos_tms_prioridad",
  "pos_tms_fecha",
  "pos_tms_ventana_inicio",
  "pos_tms_ventana_fin",
  "pos_tms_direccion",
  "pos_tms_zona",
  "pos_tms_cobro",
  "pos_tms_precio",
  "pos_tms_dryrun",
  "pos_tms_resultado"
) as $id) {
  $checks["vista_" . $id] = check_item(strpos($vista, 'id="' . $id . '"') !== false, "Vista POS #" . $id);
}

foreach (array(
  "payloadTmsPosDryRun",
  "payloadTmsPosReal",
  "crearTmsDesdePosReal",
  "alternarTmsPos",
  "prevalidarTmsPos",
  "renderTmsPosDryRun",
  "renderTmsPosReal"
) as $funcion) {
  $checks["js_funcion_" . $funcion] = check_item(preg_match('/function\s+' . preg_quote($funcion, '/') . '\s*\(/', $js) === 1, "JS " . $funcion);
}

$checks["js_endpoint_dryrun_tms"] = check_item(strpos($js, "/tms/servicio_pos_dryrun_erp") !== false, "Endpoint dry-run TMS desde POS");
$checks["js_endpoint_guardado_tms"] = check_item(strpos($js, "/tms/servicio_guardar_erp") !== false, "Endpoint guardado TMS desde POS");
$checks["js_payload_tms_no_cobro"] = check_item(strpos($js, 'request("/ventas/pos_confirmar_erp", payloadVentaPos())') !== false && strpos($js, 'request("/ventas/pos_confirmar_erp", payloadTmsPosDryRun())') === false, "Cobro POS conserva payloadVentaPos");
$checks["js_guardado_tms_post_pos"] = check_item(strpos($js, "renderCobroReal(response);") !== false && strpos($js, "crearTmsDesdePosReal(payloadTmsPendiente)") !== false, "Guardado TMS posterior al POS exitoso");
$checks["js_resultado_no_folio_real"] = check_item(strpos($js, "no crea folio TMS ni toca la operacion comercial") !== false, "Mensaje de preview sin folio real");
$checks["js_resultado_folio_real"] = check_item(strpos($js, "Folio logistico") !== false, "Mensaje de folio logistico real");
$checks["vista_importe_logistico"] = check_item(strpos($vista, "Importe delivery") !== false, "Importe logistico visible separado");
$checks["vista_mensaje_no_crea"] = check_item(strpos($vista, "Solo previsualiza el servicio logistico") !== false, "Mensaje operativo de separacion");

$fallos = array_values(array_filter($checks, function ($item) {
  return empty($item["ok"]);
}));

echo json_encode(array(
  "ok" => empty($fallos),
  "modo" => "read-only",
  "estado" => empty($fallos) ? "pos_tms_ui_real_lista" : "pos_tms_ui_real_pendiente",
  "checks_total" => count($checks),
  "checks_ok" => count($checks) - count($fallos),
  "checks_fallos" => count($fallos),
  "fallos" => $fallos,
  "reglas" => array(
    "read_only" => true,
    "dryrun_disponible" => true,
    "creacion_real_separada_disponible" => true,
    "no_toca_operaciones_comerciales" => true,
    "no_cobra_productos" => true,
    "no_mueve_inventario" => true
  ),
  "siguiente_paso" => "Validar visualmente en navegador el panel Entrega TMS dentro de POS y ejecutar UAT real controlado cuando se autorice."
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

function check_item($ok, $detalle) {
  return array("ok" => (bool) $ok, "detalle" => $detalle);
}

function contenido($ruta) {
  return file_exists($ruta) ? file_get_contents($ruta) : "";
}
