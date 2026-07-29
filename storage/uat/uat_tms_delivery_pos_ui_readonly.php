<?php

/**
 * IA: Codex GPT-5
 * Fecha: 2026-07-28
 * Proposito: validar la UI POS opt-in de Entrega TMS en modo dry-run.
 * Impacto: POS y TMS Delivery; confirma que la UI solo previsualiza servicio logistico separado.
 * Contrato: read-only; no crea servicios TMS, no confirma ventas, no cobra y no mueve inventario.
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
  "alternarTmsPos",
  "prevalidarTmsPos",
  "renderTmsPosDryRun"
) as $funcion) {
  $checks["js_funcion_" . $funcion] = check_item(preg_match('/function\s+' . preg_quote($funcion, '/') . '\s*\(/', $js) === 1, "JS " . $funcion);
}

$checks["js_endpoint_dryrun_tms"] = check_item(strpos($js, "/tms/servicio_pos_dryrun_erp") !== false, "Endpoint dry-run TMS desde POS");
$checks["js_sin_guardado_tms"] = check_item(strpos($js, "/tms/servicio_guardar_erp") === false, "Sin llamada a guardado TMS desde POS");
$checks["js_payload_tms_no_cobro"] = check_item(strpos($js, 'request("/ventas/pos_confirmar_erp", payloadVentaPos())') !== false && strpos($js, 'request("/ventas/pos_confirmar_erp", payloadTmsPosDryRun())') === false, "Cobro POS conserva payloadVentaPos");
$checks["js_resultado_no_folio_real"] = check_item(strpos($js, "no crea folio TMS ni modifica la venta") !== false, "Mensaje de preview sin folio real");
$checks["vista_importe_logistico"] = check_item(strpos($vista, "Importe delivery") !== false, "Importe logistico visible separado");
$checks["vista_mensaje_no_crea"] = check_item(strpos($vista, "No crea servicio ni cambia la venta") !== false, "Mensaje operativo de separacion");

$fallos = array_values(array_filter($checks, function ($item) {
  return empty($item["ok"]);
}));

echo json_encode(array(
  "ok" => empty($fallos),
  "modo" => "read-only",
  "estado" => empty($fallos) ? "pos_tms_ui_dryrun_lista" : "pos_tms_ui_dryrun_pendiente",
  "checks_total" => count($checks),
  "checks_ok" => count($checks) - count($fallos),
  "checks_fallos" => count($fallos),
  "fallos" => $fallos,
  "reglas" => array(
    "read_only" => true,
    "solo_dryrun" => true,
    "no_crea_tms" => true,
    "no_confirma_venta" => true,
    "no_cobra_productos" => true,
    "no_mueve_inventario" => true
  ),
  "siguiente_paso" => "Validar visualmente en navegador el panel Entrega TMS dentro de POS."
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

function check_item($ok, $detalle) {
  return array("ok" => (bool) $ok, "detalle" => $detalle);
}

function contenido($ruta) {
  return file_exists($ruta) ? file_get_contents($ruta) : "";
}
