<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-09-05.
 * Proposito: validar listado interno de productos agregados a sesiones/leads ecommerce.
 * Impacto: confirma visibilidad operativa por producto sin crear pedidos, ventas ni inventario.
 * Contrato: read-only; consulta modelo protegido por controlador en HTTP y no modifica BD.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/EcommerceLeadsErp.php";

$modelo = new EcommerceLeadsErp();
$respuesta = $modelo->productosInterno(array("limite" => 20));
$depurar = $respuesta["depurar"] ?? array();
$items = $depurar["items"] ?? array();
$resumen = $depurar["resumen"] ?? array();
$respuestaFiltrada = null;
$itemsFiltrados = array();
if (!empty($items)) {
  $idLead = (int) $items[0]["id_carrito_lead"];
  $respuestaFiltrada = $modelo->productosInterno(array("limite" => 20, "id_carrito_lead" => $idLead));
  $itemsFiltrados = $respuestaFiltrada["depurar"]["items"] ?? array();
}

$bloqueos = array();
if (!empty($respuesta["error"])) { $bloqueos[] = "respuesta_error"; }
if (empty($depurar["configurado"])) { $bloqueos[] = "productos_leads_no_configurado"; }
if (!is_array($items)) { $bloqueos[] = "items_no_array"; }
if (!is_array($resumen)) { $bloqueos[] = "resumen_no_array"; }
if (!empty($items)) {
  $primero = $items[0];
  foreach (array("id_carrito_lead", "nombre", "cantidad", "validacion_publicacion", "imagen_url", "imagen_fuente", "session_id_hash", "estatus") as $campo) {
    if (!array_key_exists($campo, $primero)) { $bloqueos[] = "falta_campo_" . $campo; }
  }
  foreach ($itemsFiltrados as $itemFiltrado) {
    if ((int) $itemFiltrado["id_carrito_lead"] !== (int) $primero["id_carrito_lead"]) {
      $bloqueos[] = "filtro_lead_devuelve_otro_lead";
      break;
    }
  }
}

echo json_encode(array(
  "ok" => empty($bloqueos),
  "modo" => "read-only",
  "senal_productos_leads" => empty($bloqueos) ? "verde_productos_por_sesion" : "revisar_productos_leads",
  "total_items_respuesta" => count($items),
  "resumen" => $resumen,
  "primer_item" => !empty($items) ? $items[0] : null,
  "filtro_lead" => array(
    "id_carrito_lead" => !empty($items) ? (int) $items[0]["id_carrito_lead"] : 0,
    "total_filtrado" => count($itemsFiltrados),
    "ok" => empty($items) || empty($respuestaFiltrada["error"])
  ),
  "bloqueos" => $bloqueos,
  "guardrails" => array(
    "no_escribe_bd" => true,
    "no_crea_pedido" => true,
    "no_crea_venta" => true,
    "no_descuenta_inventario" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
