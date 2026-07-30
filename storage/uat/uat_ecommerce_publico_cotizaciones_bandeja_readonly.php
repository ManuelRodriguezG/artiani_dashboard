<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-29.
 * Proposito: validar bandeja interna read-only de cotizaciones ecommerce.
 * Impacto: prepara seguimiento operativo post-WhatsApp sin crear cotizaciones, pedidos ni ventas.
 * Contrato: read-only; no escribe BD, no cambia estatus, no descuenta inventario y no toca legacy ecom_*.
 */

$opciones = getopt("", array(
  "folio::",
  "limite::"
));

$folio = isset($opciones["folio"]) ? trim((string) $opciones["folio"]) : "";
$limite = isset($opciones["limite"]) ? max(1, min(100, intval($opciones["limite"]))) : 25;

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/EcommerceCatalogoPublico.php";

$api = new EcommerceCatalogoPublico();
$bandeja = $api->cotizacionesBandejaInterna(array("limite" => $limite));
$items = valorBandejaReadonly($bandeja, array("depurar", "items"), array());
$primerFolio = $folio;
if ($primerFolio === "" && !empty($items)) {
  $primerFolio = (string) valorBandejaReadonly($items[0], array("folio"), "");
}
$detalle = $primerFolio !== "" ? $api->cotizacionDetalleInterna(array("folio" => $primerFolio)) : $api->cotizacionDetalleInterna(array("folio" => "ECOM-FOLIO-DE-PRUEBA"));
$accionSinFolio = $api->cotizacionAccionPlanInterna(array("accion" => "marcar_seguimiento"));
$accionFolioInexistente = $api->cotizacionAccionPlanInterna(array(
  "accion" => "descartar",
  "folio" => $primerFolio !== "" ? $primerFolio : "ECOM-FOLIO-DE-PRUEBA",
  "motivo" => "Validacion read-only"
));

$bloqueos = array();
if (valorBandejaReadonly($bandeja, array("depurar", "guardrails", "read_only"), false) !== true) {
  $bloqueos[] = "bandeja_debe_ser_read_only";
}
if (valorBandejaReadonly($bandeja, array("depurar", "guardrails", "no_crea_pedido"), false) !== true) {
  $bloqueos[] = "bandeja_debe_indicar_no_crea_pedido";
}
if (!is_array($items)) {
  $bloqueos[] = "bandeja_items_debe_ser_array";
}
if (valorBandejaReadonly($detalle, array("depurar", "guardrails", "no_descuenta_inventario"), false) !== true) {
  $bloqueos[] = "detalle_debe_indicar_no_descuenta_inventario";
}
if (valorBandejaReadonly($accionSinFolio, array("depurar", "no_escribe_bd"), false) !== true) {
  $bloqueos[] = "accion_plan_debe_indicar_no_escribe_bd";
}
if (!in_array("folio_o_id_cotizacion_requerido", valorBandejaReadonly($accionSinFolio, array("depurar", "bloqueos"), array()), true)) {
  $bloqueos[] = "accion_sin_folio_debe_bloquear";
}
if (valorBandejaReadonly($accionFolioInexistente, array("depurar", "no_crea_pedido"), false) !== true) {
  $bloqueos[] = "accion_folio_debe_indicar_no_crea_pedido";
}

echo json_encode(array(
  "ok" => empty($bloqueos),
  "modo" => "read-only",
  "bandeja" => array(
    "configurado" => valorBandejaReadonly($bandeja, array("depurar", "configurado"), false),
    "items_total_pagina" => count($items),
    "resumen" => valorBandejaReadonly($bandeja, array("depurar", "resumen"), array()),
    "paginacion" => valorBandejaReadonly($bandeja, array("depurar", "paginacion"), array()),
    "tablas" => valorBandejaReadonly($bandeja, array("depurar", "tablas"), array())
  ),
  "detalle" => array(
    "folio_consultado" => $primerFolio !== "" ? $primerFolio : "ECOM-FOLIO-DE-PRUEBA",
    "tipo" => valorBandejaReadonly($detalle, array("tipo"), ""),
    "item_presente" => is_array(valorBandejaReadonly($detalle, array("depurar", "item"), null)),
    "detalle_total" => is_array(valorBandejaReadonly($detalle, array("depurar", "detalle"), null)) ? count(valorBandejaReadonly($detalle, array("depurar", "detalle"), array())) : 0,
    "eventos_total" => is_array(valorBandejaReadonly($detalle, array("depurar", "eventos"), null)) ? count(valorBandejaReadonly($detalle, array("depurar", "eventos"), array())) : 0
  ),
  "acciones_plan" => array(
    "sin_folio_bloquea" => in_array("folio_o_id_cotizacion_requerido", valorBandejaReadonly($accionSinFolio, array("depurar", "bloqueos"), array()), true),
    "folio_inexistente_no_escribe" => valorBandejaReadonly($accionFolioInexistente, array("depurar", "no_escribe_bd"), false) === true,
    "acciones_permitidas" => valorBandejaReadonly($accionFolioInexistente, array("depurar", "acciones_permitidas"), array()),
    "bloqueos_folio_inexistente" => valorBandejaReadonly($accionFolioInexistente, array("depurar", "bloqueos"), array())
  ),
  "endpoints_internos" => array(
    "GET /ecommercePublico/cotizaciones_bandeja_erp",
    "GET /ecommercePublico/cotizacion_detalle_erp/{folio}",
    "POST /ecommercePublico/cotizacion_accion_plan_erp"
  ),
  "bloqueos" => $bloqueos,
  "guardrails" => array(
    "no_escribe_bd" => true,
    "no_registra_cotizacion" => true,
    "no_cambia_estatus" => true,
    "no_crea_pedido" => true,
    "no_crea_venta" => true,
    "no_descuenta_inventario" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function valorBandejaReadonly($datos, $ruta, $default = null) {
  $actual = $datos;
  foreach ($ruta as $segmento) {
    if (!is_array($actual) || !array_key_exists($segmento, $actual)) {
      return $default;
    }
    $actual = $actual[$segmento];
  }
  return $actual;
}
