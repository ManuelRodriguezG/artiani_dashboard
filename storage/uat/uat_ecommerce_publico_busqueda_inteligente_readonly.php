<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-09-09.
 * Proposito: validar busqueda inteligente publica ecommerce por HTTP.
 * Impacto: protege /buscar/{termino}, autocomplete, categorias relacionadas y URLs publicas para frontend.
 * Contrato: read-only; no escribe BD, no registra busquedas, no toca inventario ni precios.
 */

$opciones = getopt("", array("base::", "q::", "limite::"));
$base = isset($opciones["base"]) ? rtrim(trim((string) $opciones["base"]), "/") : "http://panel.com.local";
$q = isset($opciones["q"]) ? trim((string) $opciones["q"]) : "Filtro para pecera de 40 litros";
$limite = isset($opciones["limite"]) ? max(1, min(12, intval($opciones["limite"]))) : 3;
$httpBusqueda = requestBusquedaInteligente($base . "/ecommercePublico/busqueda?q=" . rawurlencode($q) . "&limite=" . intval($limite));
$httpSugerencias = requestBusquedaInteligente($base . "/ecommercePublico/busqueda_sugerencias?q=" . rawurlencode($q) . "&limite=" . intval($limite));
$httpManifest = requestBusquedaInteligente($base . "/ecommercePublico/busqueda_manifest");

$depBusqueda = valorBusquedaInteligente($httpBusqueda, array("depurar"), array());
$depSugerencias = valorBusquedaInteligente($httpSugerencias, array("depurar"), array());
$depManifest = valorBusquedaInteligente($httpManifest, array("depurar"), array());
$bloqueos = array();

if (valorBusquedaInteligente($depBusqueda, array("fase"), "") !== "busqueda_inteligente_v1") {
  $bloqueos[] = "busqueda_fase_incorrecta";
}
if (intval(valorBusquedaInteligente($depBusqueda, array("total"), 0)) <= 0) {
  $bloqueos[] = "busqueda_sin_productos";
}
if (count(valorBusquedaInteligente($depBusqueda, array("items"), array())) <= 0) {
  $bloqueos[] = "busqueda_items_vacios";
}
if (count(valorBusquedaInteligente($depBusqueda, array("categorias_relacionadas"), array())) <= 0) {
  $bloqueos[] = "busqueda_sin_categorias_relacionadas";
}
if (empty(valorBusquedaInteligente($depBusqueda, array("guardrails", "no_stock_exacto"), false))) {
  $bloqueos[] = "busqueda_guardrail_no_stock_exacto_faltante";
}
if (empty(valorBusquedaInteligente($depBusqueda, array("guardrails", "no_registra_busqueda"), false))) {
  $bloqueos[] = "busqueda_guardrail_no_registra_busqueda_faltante";
}

$primerItemUrl = (string) valorBusquedaInteligente($depBusqueda, array("items", 0, "url"), "");
if ($primerItemUrl !== "" && strpos($primerItemUrl, "/ecommercePublico/producto/") !== false) {
  $bloqueos[] = "producto_url_publica_incorrecta";
}
$primeraCategoriaUrl = (string) valorBusquedaInteligente($depBusqueda, array("categorias_relacionadas", 0, "url"), "");
if ($primeraCategoriaUrl !== "" && strpos($primeraCategoriaUrl, "/categoria/") !== 0 && strpos($primeraCategoriaUrl, "/buscar/") !== 0) {
  $bloqueos[] = "categoria_url_publica_incorrecta";
}

if (valorBusquedaInteligente($depSugerencias, array("fase"), "") !== "busqueda_sugerencias_inteligente_v1") {
  $bloqueos[] = "sugerencias_fase_incorrecta";
}
if (intval(valorBusquedaInteligente($depSugerencias, array("resumen", "productos"), 0)) <= 0) {
  $bloqueos[] = "sugerencias_sin_productos";
}
if (intval(valorBusquedaInteligente($depSugerencias, array("resumen", "categorias"), 0)) <= 0) {
  $bloqueos[] = "sugerencias_sin_categorias";
}

foreach (array("busqueda" => $httpBusqueda, "sugerencias" => $httpSugerencias) as $nombre => $http) {
  if (empty($http["json_valido"])) {
    $bloqueos[] = "http_" . $nombre . "_no_json";
  }
  if (!in_array($http["tipo"], array("success", "sin_resultados_con_sugerencias"), true)) {
    $bloqueos[] = "http_" . $nombre . "_tipo_incorrecto";
  }
}
if (empty($httpManifest["json_valido"]) || $httpManifest["tipo"] !== "success") {
  $bloqueos[] = "http_manifest_no_success";
}
if (valorBusquedaInteligente($depManifest, array("fase"), "") !== "busqueda_inteligente_v1") {
  $bloqueos[] = "manifest_fase_incorrecta";
}
if (empty(valorBusquedaInteligente($depManifest, array("configuracion", "sinonimos"), array()))) {
  $bloqueos[] = "manifest_sinonimos_vacios";
}
if (valorBusquedaInteligente($depManifest, array("cms", "clave_configuracion"), "") !== "busqueda_inteligente_config") {
  $bloqueos[] = "manifest_clave_cms_incorrecta";
}

$ok = empty($bloqueos);
echo json_encode(array(
  "ok" => $ok,
  "modo" => "read-only",
  "senal_frontend" => $ok ? "busqueda_inteligente_lista" : "busqueda_inteligente_incompleta",
  "base_url" => $base,
  "q" => $q,
  "bloqueos" => array_values(array_unique($bloqueos)),
  "busqueda" => array(
    "fase" => valorBusquedaInteligente($depBusqueda, array("fase"), ""),
    "query_usada_catalogo" => valorBusquedaInteligente($depBusqueda, array("query_usada_catalogo"), ""),
    "total" => intval(valorBusquedaInteligente($depBusqueda, array("total"), 0)),
    "items" => count(valorBusquedaInteligente($depBusqueda, array("items"), array())),
    "categorias_relacionadas" => count(valorBusquedaInteligente($depBusqueda, array("categorias_relacionadas"), array())),
    "marcas_relacionadas" => count(valorBusquedaInteligente($depBusqueda, array("marcas_relacionadas"), array())),
    "primer_producto_url" => $primerItemUrl,
    "primera_categoria_url" => $primeraCategoriaUrl
  ),
  "sugerencias" => array(
    "fase" => valorBusquedaInteligente($depSugerencias, array("fase"), ""),
    "query_usada_productos" => valorBusquedaInteligente($depSugerencias, array("query_usada_productos"), ""),
    "resumen" => valorBusquedaInteligente($depSugerencias, array("resumen"), array())
  ),
  "manifest" => array(
    "fase" => valorBusquedaInteligente($depManifest, array("fase"), ""),
    "fuente" => valorBusquedaInteligente($depManifest, array("fuente"), ""),
    "clave_configuracion" => valorBusquedaInteligente($depManifest, array("cms", "clave_configuracion"), ""),
    "sinonimos" => count(valorBusquedaInteligente($depManifest, array("configuracion", "sinonimos"), array())),
    "categorias_probables" => count(valorBusquedaInteligente($depManifest, array("configuracion", "categorias_probables"), array()))
  ),
  "http" => array(
    "busqueda" => resumenHttpBusquedaInteligente($httpBusqueda),
    "sugerencias" => resumenHttpBusquedaInteligente($httpSugerencias),
    "manifest" => resumenHttpBusquedaInteligente($httpManifest)
  ),
  "guardrails" => array(
    "no_escribe_bd" => true,
    "no_registra_busqueda" => true,
    "solo_publicados" => true,
    "no_granel" => true,
    "no_stock_exacto" => true,
    "no_expone_costos" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function requestBusquedaInteligente($url) {
  $context = stream_context_create(array(
    "http" => array(
      "method" => "GET",
      "header" => "Accept: application/json\r\n",
      "ignore_errors" => true,
      "timeout" => 30
    )
  ));
  $raw = @file_get_contents($url, false, $context);
  $json = json_decode((string) $raw, true);
  return array(
    "url" => $url,
    "json_valido" => is_array($json),
    "tipo" => is_array($json) ? valorBusquedaInteligente($json, array("tipo"), "") : "",
    "mensaje" => is_array($json) ? valorBusquedaInteligente($json, array("mensaje"), "") : "",
    "fase" => is_array($json) ? valorBusquedaInteligente($json, array("depurar", "fase"), "") : "",
    "depurar" => is_array($json) ? valorBusquedaInteligente($json, array("depurar"), array()) : array(),
    "raw_inicio" => substr((string) $raw, 0, 80)
  );
}

function resumenHttpBusquedaInteligente($http) {
  return array(
    "url" => valorBusquedaInteligente($http, array("url"), ""),
    "json_valido" => !empty($http["json_valido"]),
    "tipo" => valorBusquedaInteligente($http, array("tipo"), ""),
    "mensaje" => valorBusquedaInteligente($http, array("mensaje"), ""),
    "fase" => valorBusquedaInteligente($http, array("fase"), ""),
    "raw_inicio" => valorBusquedaInteligente($http, array("raw_inicio"), "")
  );
}

function valorBusquedaInteligente($datos, $ruta, $default = null) {
  $actual = $datos;
  foreach ($ruta as $segmento) {
    if (!is_array($actual) || !array_key_exists($segmento, $actual)) {
      return $default;
    }
    $actual = $actual[$segmento];
  }
  return $actual;
}
