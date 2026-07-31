<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-30.
 * Proposito: validar el contrato robusto del detalle publico de producto ecommerce.
 * Impacto: protege la vista frontend de producto: item, variantes, relacionados, breadcrumbs y SEO.
 * Contrato: read-only; no crea publicaciones, no registra cotizaciones y no toca inventario.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/EcommerceCatalogoPublico.php";

$api = new EcommerceCatalogoPublico();
$catalogo = $api->catalogoPublico(array("limite" => 1));
$items = valorProductoDetalle($catalogo, array("depurar", "items"), array());
$primerItem = !empty($items) ? $items[0] : array();
$detalle = array();
if (!empty($primerItem)) {
  $detalle = $api->productoPublico(valorProductoDetalle($primerItem, array("slug"), ""));
}

$item = valorProductoDetalle($detalle, array("depurar", "item"), null);
$variantes = valorProductoDetalle($detalle, array("depurar", "variantes"), array());
$relacionados = valorProductoDetalle($detalle, array("depurar", "relacionados"), array());
$breadcrumbs = valorProductoDetalle($detalle, array("depurar", "breadcrumbs"), array());
$seo = valorProductoDetalle($detalle, array("depurar", "seo"), array());
$guardrails = valorProductoDetalle($detalle, array("depurar", "guardrails"), array());

$bloqueos = array();
if (empty($primerItem)) {
  $bloqueos[] = "catalogo_sin_publicaciones";
}
if (empty($detalle) || !empty($detalle["error"]) || empty($item)) {
  $bloqueos[] = "detalle_no_disponible";
}
if (!is_array($variantes)) {
  $bloqueos[] = "variantes_no_array";
}
if (!is_array($relacionados)) {
  $bloqueos[] = "relacionados_no_array";
}
if (count($breadcrumbs) < 2) {
  $bloqueos[] = "breadcrumbs_insuficientes";
}
if (trim((string) valorProductoDetalle($seo, array("title"), "")) === "") {
  $bloqueos[] = "seo_title_faltante";
}
if (trim((string) valorProductoDetalle($seo, array("canonical_path"), "")) === "") {
  $bloqueos[] = "seo_canonical_faltante";
}
if (empty($guardrails["no_stock_exacto"])) {
  $bloqueos[] = "guardrail_stock_exacto_no_confirmado";
}
if (empty($guardrails["no_descuenta_inventario"])) {
  $bloqueos[] = "guardrail_no_descuenta_inventario_no_confirmado";
}

$ok = empty($bloqueos);
echo json_encode(array(
  "ok" => $ok,
  "modo" => "read-only",
  "senal_frontend" => $ok ? "detalle_producto_robusto" : "detalle_producto_incompleto",
  "bloqueos" => array_values(array_unique($bloqueos)),
  "producto" => empty($item) ? null : array(
    "id_publicacion" => intval(valorProductoDetalle($item, array("id_publicacion"), 0)),
    "id_sku" => intval(valorProductoDetalle($item, array("id_sku"), 0)),
    "slug" => valorProductoDetalle($item, array("slug"), ""),
    "nombre" => valorProductoDetalle($item, array("nombre"), ""),
    "disponibilidad" => valorProductoDetalle($item, array("disponibilidad"), "")
  ),
  "contexto" => array(
    "variantes" => count($variantes),
    "relacionados" => count($relacionados),
    "breadcrumbs" => count($breadcrumbs),
    "seo_title" => valorProductoDetalle($seo, array("title"), ""),
    "canonical_path" => valorProductoDetalle($seo, array("canonical_path"), "")
  ),
  "guardrails" => array(
    "no_escribe_bd" => true,
    "no_registra_cotizacion" => true,
    "no_descuenta_inventario" => !empty($guardrails["no_descuenta_inventario"]),
    "no_expone_stock_exacto" => !empty($guardrails["no_stock_exacto"]),
    "solo_publicado" => !empty($guardrails["solo_publicado"])
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

function valorProductoDetalle($datos, $ruta, $default = null) {
  $actual = $datos;
  foreach ($ruta as $segmento) {
    if (!is_array($actual) || !array_key_exists($segmento, $actual)) {
      return $default;
    }
    $actual = $actual[$segmento];
  }
  return $actual;
}
