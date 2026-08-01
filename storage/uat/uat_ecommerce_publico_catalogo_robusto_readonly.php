<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-08-01.
 * Proposito: validar el catalogo publico robusto para frontend ecommerce.
 * Impacto: cubre filtros, ordenamientos, paginacion y metadatos UI sin escribir BD.
 * Contrato: read-only; no ejecuta DDL, no registra cotizaciones y no toca inventario.
 */

$opciones = getopt("", array("base::", "limite::"));
$base = isset($opciones["base"]) ? rtrim(trim((string) $opciones["base"]), "/") : "http://panel.com.local";
$limite = isset($opciones["limite"]) ? max(1, min(10, intval($opciones["limite"]))) : 3;

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/EcommerceCatalogoPublico.php";

$api = new EcommerceCatalogoPublico();

$casos = array(
  "base" => $api->catalogoPublico(array("limite" => $limite, "orden" => "relevancia")),
  "disponibles_precio_asc" => $api->catalogoPublico(array("limite" => $limite, "disponibilidad" => "disponible", "orden" => "precio_asc")),
  "pocas_piezas_precio_desc" => $api->catalogoPublico(array("limite" => $limite, "disponibilidad" => "pocas_piezas", "orden" => "precio_desc")),
  "busqueda_sin_resultados" => $api->catalogoPublico(array("limite" => $limite, "q" => "__sin_resultados_catalogo_frontend__", "orden" => "nombre"))
);

$bloqueos = array();
foreach ($casos as $nombre => $respuesta) {
  validarWrapperCatalogoRobusto($nombre, $respuesta, $bloqueos);
  validarCatalogoRobusto($nombre, $respuesta, $bloqueos);
}

if (count(valorCatalogoRobusto($casos["base"], array("depurar", "items"), array())) <= 0) {
  $bloqueos[] = "catalogo_base_sin_items";
}
if (valorCatalogoRobusto($casos["disponibles_precio_asc"], array("depurar", "filtros_aplicados", "disponibilidad"), "") !== "disponible") {
  $bloqueos[] = "disponibles_no_respeta_filtro";
}
if (valorCatalogoRobusto($casos["disponibles_precio_asc"], array("depurar", "filtros_aplicados", "orden"), "") !== "precio_asc") {
  $bloqueos[] = "precio_asc_no_respeta_orden";
}
if (valorCatalogoRobusto($casos["busqueda_sin_resultados"], array("depurar", "frontend", "estado_vacio", "mostrar"), false) !== true) {
  $bloqueos[] = "busqueda_sin_resultados_debe_mostrar_estado_vacio";
}

echo json_encode(array(
  "ok" => empty($bloqueos),
  "modo" => "read-only",
  "senal_frontend" => empty($bloqueos) ? "catalogo_robusto_listo" : "catalogo_robusto_revisar",
  "base_api" => $base . "/ecommercePublico",
  "resumen" => array(
    "base_items" => count(valorCatalogoRobusto($casos["base"], array("depurar", "items"), array())),
    "base_total" => intval(valorCatalogoRobusto($casos["base"], array("depurar", "paginacion", "total"), 0)),
    "disponibles_items" => count(valorCatalogoRobusto($casos["disponibles_precio_asc"], array("depurar", "items"), array())),
    "pocas_piezas_items" => count(valorCatalogoRobusto($casos["pocas_piezas_precio_desc"], array("depurar", "items"), array())),
    "sin_resultados_estado_vacio" => valorCatalogoRobusto($casos["busqueda_sin_resultados"], array("depurar", "frontend", "estado_vacio", "mostrar"), false),
    "ordenamientos" => valorCatalogoRobusto($casos["base"], array("depurar", "ordenamientos_disponibles"), array())
  ),
  "frontend" => array(
    "base" => valorCatalogoRobusto($casos["base"], array("depurar", "frontend"), array()),
    "disponibles_precio_asc" => valorCatalogoRobusto($casos["disponibles_precio_asc"], array("depurar", "frontend"), array()),
    "sin_resultados" => valorCatalogoRobusto($casos["busqueda_sin_resultados"], array("depurar", "frontend"), array())
  ),
  "endpoints_http_equivalentes" => array(
    "base" => "GET " . $base . "/ecommercePublico/catalogo?limite=" . $limite . "&orden=relevancia",
    "disponibles_precio_asc" => "GET " . $base . "/ecommercePublico/catalogo?disponibilidad=disponible&orden=precio_asc&limite=" . $limite,
    "sin_resultados" => "GET " . $base . "/ecommercePublico/catalogo?q=__sin_resultados_catalogo_frontend__&limite=" . $limite
  ),
  "bloqueos" => $bloqueos,
  "guardrails" => array(
    "no_escribe_bd" => true,
    "no_ejecuta_ddl" => true,
    "no_mueve_inventario" => true,
    "no_expone_stock_exacto" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function validarWrapperCatalogoRobusto($nombre, $respuesta, &$bloqueos) {
  foreach (array("error", "tipo", "mensaje", "api", "depurar") as $key) {
    if (!is_array($respuesta) || !array_key_exists($key, $respuesta)) {
      $bloqueos[] = $nombre . "_falta_wrapper_" . $key;
    }
  }
  if (valorCatalogoRobusto($respuesta, array("api", "fuente_verdad"), "") !== "ERP") {
    $bloqueos[] = $nombre . "_fuente_verdad_invalida";
  }
}

function validarCatalogoRobusto($nombre, $respuesta, &$bloqueos) {
  foreach (array("items", "paginacion", "filtros_aplicados", "ordenamientos_disponibles", "frontend", "guardrails") as $key) {
    if (!is_array(valorCatalogoRobusto($respuesta, array("depurar", $key), null))) {
      $bloqueos[] = $nombre . "_falta_array_" . $key;
    }
  }
  if (valorCatalogoRobusto($respuesta, array("depurar", "guardrails", "no_stock_exacto"), false) !== true) {
    $bloqueos[] = $nombre . "_debe_indicar_no_stock_exacto";
  }
  if (valorCatalogoRobusto($respuesta, array("depurar", "frontend", "guardrails_ui", "cotizacion_requiere_dryrun"), false) !== true) {
    $bloqueos[] = $nombre . "_frontend_debe_requerir_dryrun";
  }
}

function valorCatalogoRobusto($datos, $ruta, $default = null) {
  $actual = $datos;
  foreach ($ruta as $segmento) {
    if (!is_array($actual) || !array_key_exists($segmento, $actual)) {
      return $default;
    }
    $actual = $actual[$segmento];
  }
  return $actual;
}
