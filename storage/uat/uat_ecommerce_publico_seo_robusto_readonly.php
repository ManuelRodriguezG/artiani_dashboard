<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-31.
 * Proposito: validar SEO robusto del API ecommerce publico.
 * Impacto: protege sitemap, robots, rutas y JSON-LD sugeridos para el frontend externo.
 * Contrato: read-only; no escribe BD, no genera archivos reales y no expone stock exacto.
 */

$opciones = getopt("", array("base::", "limite::"));
$base = isset($opciones["base"]) ? rtrim(trim((string) $opciones["base"]), "/") : "http://panel.com.local";
$limite = isset($opciones["limite"]) ? max(1, min(200, intval($opciones["limite"]))) : 20;

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/EcommerceCatalogoPublico.php";

$api = new EcommerceCatalogoPublico();
$seo = $api->seoPublico(array("limite" => $limite));
$http = requestSeoRobusto($base . "/ecommercePublico/seo?limite=" . intval($limite));
$dep = valorSeoRobusto($seo, array("depurar"), array());

$bloqueos = array();
if (!empty($seo["error"])) {
  $bloqueos[] = "seo_modelo_error";
}
if (!is_array(valorSeoRobusto($dep, array("meta"), null))) {
  $bloqueos[] = "seo_meta_faltante";
}
if (intval(valorSeoRobusto($dep, array("resumen", "rutas_total"), 0)) <= 0) {
  $bloqueos[] = "seo_sin_rutas";
}
if (intval(valorSeoRobusto($dep, array("resumen", "productos"), 0)) <= 0) {
  $bloqueos[] = "seo_sin_productos";
}
if (trim((string) valorSeoRobusto($dep, array("sitemap_xml_sugerido"), "")) === "") {
  $bloqueos[] = "seo_sitemap_xml_faltante";
}
if (empty(valorSeoRobusto($dep, array("guardrails", "frontend_genera_archivos_seo"), false))) {
  $bloqueos[] = "guardrail_frontend_genera_archivos_faltante";
}
if (empty(valorSeoRobusto($dep, array("guardrails", "no_muestra_stock_exacto"), false))) {
  $bloqueos[] = "guardrail_no_stock_exacto_faltante";
}
if (empty($http["json_valido"]) || $http["tipo"] !== "success") {
  $bloqueos[] = "http_seo_no_success";
}

$ok = empty($bloqueos);
echo json_encode(array(
  "ok" => $ok,
  "modo" => "read-only",
  "senal_frontend" => $ok ? "seo_robusto_listo" : "seo_robusto_incompleto",
  "base_url" => $base,
  "bloqueos" => array_values(array_unique($bloqueos)),
  "resumen" => valorSeoRobusto($dep, array("resumen"), array()),
  "sitemap_xml_generado" => trim((string) valorSeoRobusto($dep, array("sitemap_xml_sugerido"), "")) !== "",
  "robots_txt_generado" => trim((string) valorSeoRobusto($dep, array("robots", "robots_txt_sugerido"), "")) !== "",
  "http" => $http,
  "guardrails" => array(
    "no_escribe_bd" => true,
    "no_genera_archivos" => true,
    "no_stock_exacto" => true,
    "no_usa_ecom_legacy" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function requestSeoRobusto($url) {
  $context = stream_context_create(array(
    "http" => array(
      "method" => "GET",
      "header" => "Accept: application/json\r\n",
      "ignore_errors" => true,
      "timeout" => 10
    )
  ));
  $raw = @file_get_contents($url, false, $context);
  $json = json_decode((string) $raw, true);
  return array(
    "url" => $url,
    "json_valido" => is_array($json),
    "tipo" => is_array($json) ? valorSeoRobusto($json, array("tipo"), "") : "",
    "mensaje" => is_array($json) ? valorSeoRobusto($json, array("mensaje"), "") : "",
    "raw_inicio" => substr((string) $raw, 0, 80)
  );
}

function valorSeoRobusto($datos, $ruta, $default = null) {
  $actual = $datos;
  foreach ($ruta as $segmento) {
    if (!is_array($actual) || !array_key_exists($segmento, $actual)) {
      return $default;
    }
    $actual = $actual[$segmento];
  }
  return $actual;
}
