<?php
/**
 * Documentacion IA: Codex GPT-5 | Fecha: 2026-07-26
 * Proposito: evaluar si el API ERP esta listo para que el frontend ecommerce se conecte en salida productiva.
 * Impacto: checklist tecnico para publicar frontend externo sin checkout ni pagos online.
 * Contrato: read-only; no escribe BD, no cambia CORS, no publica productos y no registra cotizaciones.
 */

$opciones = getopt("", array("base::", "origin::", "url::", "min_publicadas::"));
$base = isset($opciones["base"]) ? rtrim(trim((string) $opciones["base"]), "/") : "http://panel.com.local";
$origin = isset($opciones["origin"]) ? rtrim(trim((string) $opciones["origin"]), "/") : "https://artiani.com.mx";
$urlFrontend = isset($opciones["url"]) ? rtrim(trim((string) $opciones["url"]), "/") : $origin;
$minPublicadas = isset($opciones["min_publicadas"]) ? max(1, intval($opciones["min_publicadas"])) : 6;

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/EcommerceCatalogoPublico.php";

$api = new EcommerceCatalogoPublico();
$estado = $api->estadoApiPublica();
$configuracion = $api->configuracionPublica();
$politicas = $api->politicasPublicas();
$taxonomia = $api->taxonomiaMascotasPublica();
$catalogo = $api->catalogoPublico(array("limite" => max($minPublicadas, 24)));
$items = valorProductivoFrontend($catalogo, array("depurar", "items"), array());
$primerItem = !empty($items) ? $items[0] : array();
$dryrun = !empty($primerItem) ? $api->cotizacionDryRun(array(
  "items" => array(array(
    "id_publicacion" => intval(valorProductivoFrontend($primerItem, array("id_publicacion"), 0)),
    "cantidad" => 1
  ))
)) : array();
$registroBloqueado = $api->cotizacionRegistrarBloqueada(array(
  "items" => array(array("id_publicacion" => intval(valorProductivoFrontend($primerItem, array("id_publicacion"), 0)), "cantidad" => 1))
));

$config = valorProductivoFrontend($configuracion, array("depurar", "configuracion"), array());
$publicadas = intval(valorProductivoFrontend($estado, array("depurar", "publicaciones", "total_publicadas"), count($items)));
$bloqueos = array();
$advertencias = array();

if (!valorProductivoFrontend($estado, array("depurar", "ready"), false)) {
  $bloqueos[] = "api_no_ready";
}
if (valorProductivoFrontend($estado, array("depurar", "schema", "ddl_pendiente"), true)) {
  $bloqueos[] = "ddl_pendiente";
}
if ($publicadas < $minPublicadas) {
  $bloqueos[] = "publicaciones_menor_a_minimo_" . $minPublicadas;
}
if (!$api->origenCorsPermitido($origin)) {
  $bloqueos[] = "cors_no_permite_origin_productivo";
}
if (strpos($origin, "https://") !== 0) {
  $bloqueos[] = "origin_productivo_debe_ser_https";
}
if (strpos($urlFrontend, "https://") !== 0) {
  $bloqueos[] = "url_frontend_productiva_debe_ser_https";
}
if (trim((string) valorProductivoFrontend($config, array("whatsapp_numero_principal"), "")) === "") {
  $bloqueos[] = "whatsapp_no_configurado";
}
if ((string) valorProductivoFrontend($config, array("mostrar_stock_exacto"), "1") !== "0") {
  $bloqueos[] = "mostrar_stock_exacto_debe_ser_0";
}
if (trim((string) valorProductivoFrontend($config, array("url_sitio_publico"), "")) !== $urlFrontend) {
  $advertencias[] = "url_sitio_publico_no_coincide_con_url_productiva";
}
if (!empty($politicas["error"]) || !is_array(valorProductivoFrontend($politicas, array("depurar", "items"), null))) {
  $bloqueos[] = "politicas_no_ok";
}
if (!empty($taxonomia["error"]) || !is_array(valorProductivoFrontend($taxonomia, array("depurar", "mascotas"), null)) || !is_array(valorProductivoFrontend($taxonomia, array("depurar", "necesidades"), null))) {
  $bloqueos[] = "taxonomia_no_ok";
}
if (empty($dryrun) || !empty($dryrun["error"]) || empty(valorProductivoFrontend($dryrun, array("depurar", "lineas"), array()))) {
  $bloqueos[] = "cotizacion_dryrun_no_ok";
}
if (valorProductivoFrontend($registroBloqueado, array("depurar", "bloqueado"), false) !== true) {
  $bloqueos[] = "cotizacion_registrar_debe_seguir_bloqueado_en_fase1";
}

echo json_encode(array(
  "ok" => empty($bloqueos),
  "modo" => "read-only",
  "senal_productivo_frontend" => empty($bloqueos) ? "verde_productivo_frontend_basico" : "amarillo_pendientes_productivo",
  "base_api" => $base . "/ecommercePublico",
  "origin_productivo" => $origin,
  "url_frontend_productiva" => $urlFrontend,
  "min_publicadas" => $minPublicadas,
  "checks" => array(
    "api_ready" => valorProductivoFrontend($estado, array("depurar", "ready"), false),
    "ddl_pendiente" => valorProductivoFrontend($estado, array("depurar", "schema", "ddl_pendiente"), true),
    "publicadas" => $publicadas,
    "cors_productivo_permitido" => $api->origenCorsPermitido($origin),
    "whatsapp_configurado" => trim((string) valorProductivoFrontend($config, array("whatsapp_numero_principal"), "")) !== "",
    "stock_exacto_oculto" => (string) valorProductivoFrontend($config, array("mostrar_stock_exacto"), "1") === "0",
    "politicas_ok" => empty($politicas["error"]) && is_array(valorProductivoFrontend($politicas, array("depurar", "items"), null)),
    "taxonomia_ok" => empty($taxonomia["error"]) && is_array(valorProductivoFrontend($taxonomia, array("depurar", "mascotas"), null)),
    "cotizacion_dryrun_ok" => !empty($dryrun) && empty($dryrun["error"]),
    "cotizacion_registrar_bloqueado" => valorProductivoFrontend($registroBloqueado, array("depurar", "bloqueado"), false) === true
  ),
  "advertencias" => $advertencias,
  "bloqueos" => $bloqueos,
  "guardrails" => array(
    "no_escribe_bd" => true,
    "no_publica_productos" => true,
    "no_cambia_cors" => true,
    "no_checkout" => true,
    "no_pagos_online" => true,
    "no_descuenta_inventario" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function valorProductivoFrontend($datos, $ruta, $default = null) {
  $actual = $datos;
  foreach ($ruta as $segmento) {
    if (!is_array($actual) || !array_key_exists($segmento, $actual)) {
      return $default;
    }
    $actual = $actual[$segmento];
  }
  return $actual;
}
