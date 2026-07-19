<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-19.
 * Proposito: validar en una sola salida si el ERP esta listo para entregar al frontend ecommerce externo.
 * Impacto: resume API real, CORS, WhatsApp, catalogo, dry-run y preview de expansion sin escribir BD.
 * Contrato: read-only; no crea publicaciones, no registra cotizaciones y no toca inventario.
 */

$opciones = getopt("", array("base::", "origin::", "skus_preview::", "min_publicadas::", "min_preview::"));
$base = isset($opciones["base"]) ? rtrim(trim((string) $opciones["base"]), "/") : "http://panel.com.local";
$origin = isset($opciones["origin"]) ? trim((string) $opciones["origin"]) : "http://artiani.com.local";
$skusPreviewTexto = isset($opciones["skus_preview"]) ? trim((string) $opciones["skus_preview"]) : "415,866,386,1138";
$minPublicadas = isset($opciones["min_publicadas"]) ? max(1, intval($opciones["min_publicadas"])) : 2;
$minPreview = isset($opciones["min_preview"]) ? max($minPublicadas, intval($opciones["min_preview"])) : 6;

$skusPreview = array();
foreach (explode(",", $skusPreviewTexto) as $sku) {
  $idSku = intval(trim($sku));
  if ($idSku > 0) {
    $skusPreview[] = $idSku;
  }
}
$skusPreview = array_values(array_unique($skusPreview));

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/EcommerceCatalogoPublico.php";

$api = new EcommerceCatalogoPublico();
$estado = $api->estadoApiPublica();
$configuracion = $api->configuracionPublica();
$catalogo = $api->catalogoPublico(array("limite" => max($minPublicadas, 24)));
$items = valorEntregableFrontend($catalogo, array("depurar", "items"), array());
$primerItem = !empty($items) ? $items[0] : array();
$dryrun = array();

if (!empty($primerItem)) {
  $dryrun = $api->cotizacionDryRun(array(
    "items" => array(array(
      "id_publicacion" => intval(valorEntregableFrontend($primerItem, array("id_publicacion"), 0)),
      "cantidad" => 1
    ))
  ));
}

$previewListos = 0;
$previewBloqueos = array();
foreach ($skusPreview as $idSku) {
  $preparacion = $api->prepararPublicacion(array("id_sku" => $idSku));
  $bloqueosPublicacion = valorEntregableFrontend($preparacion, array("depurar", "bloqueos_publicacion"), array());
  if (empty($bloqueosPublicacion)) {
    $previewListos++;
  } else {
    $previewBloqueos[] = array(
      "id_sku" => $idSku,
      "bloqueos" => $bloqueosPublicacion
    );
  }
}

$publicadas = intval(valorEntregableFrontend($estado, array("depurar", "publicaciones", "total_publicadas"), count($items)));
$previewTotal = $publicadas + $previewListos;
$bloqueos = array();

if (valorEntregableFrontend($estado, array("depurar", "schema", "ddl_pendiente"), true)) {
  $bloqueos[] = "ddl_pendiente";
}
if (!valorEntregableFrontend($estado, array("depurar", "ready"), false)) {
  $bloqueos[] = "api_no_ready";
}
if ($publicadas < $minPublicadas) {
  $bloqueos[] = "publicadas_menor_a_minimo_" . $minPublicadas;
}
if (trim((string) valorEntregableFrontend($configuracion, array("depurar", "configuracion", "whatsapp_numero_principal"), "")) === "") {
  $bloqueos[] = "whatsapp_no_configurado";
}
if (!$api->origenCorsPermitido($origin)) {
  $bloqueos[] = "cors_origin_no_permitido";
}
if (empty($dryrun) || !empty($dryrun["error"]) || empty(valorEntregableFrontend($dryrun, array("depurar", "lineas"), array()))) {
  $bloqueos[] = "cotizacion_dryrun_no_ok";
}
if ($previewTotal < $minPreview) {
  $bloqueos[] = "preview_menor_a_minimo_" . $minPreview;
}
if (!empty($previewBloqueos)) {
  $bloqueos[] = "preview_con_bloqueos";
}

echo json_encode(array(
  "ok" => empty($bloqueos),
  "modo" => "read-only",
  "senal_entregable_frontend" => empty($bloqueos) ? "verde_entregable_frontend" : "amarillo_revisar_bloqueos",
  "base_api" => $base . "/ecommercePublico",
  "origin_frontend" => $origin,
  "estado_actual" => array(
    "ready" => valorEntregableFrontend($estado, array("depurar", "ready"), false),
    "ddl_pendiente" => valorEntregableFrontend($estado, array("depurar", "schema", "ddl_pendiente"), true),
    "publicadas" => $publicadas,
    "min_publicadas" => $minPublicadas
  ),
  "integracion" => array(
    "cors_origin_permitido" => $api->origenCorsPermitido($origin),
    "whatsapp_configurado" => trim((string) valorEntregableFrontend($configuracion, array("depurar", "configuracion", "whatsapp_numero_principal"), "")) !== "",
    "catalogo_tiene_items" => count($items) > 0,
    "cotizacion_dryrun_ok" => !empty($dryrun) && empty($dryrun["error"])
  ),
  "preview_expansion" => array(
    "skus_revisados" => $skusPreview,
    "candidatos_listos" => $previewListos,
    "publicaciones_preview_total" => $previewTotal,
    "min_preview" => $minPreview,
    "bloqueos" => $previewBloqueos
  ),
  "comandos_siguientes" => array(
    "snapshot_real_actual" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_frontend_snapshot_readonly.php --base=" . $base . " --origin=" . $origin . " --limite=" . $minPublicadas,
    "preview_6_tarjetas" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_frontend_preview_expansion_readonly.php --base=" . $base . " --origin=" . $origin . " --skus=" . implode(",", $skusPreview) . " --resumen=1",
    "paquete_frontend" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_frontend_package_readonly.php --base=" . $base
  ),
  "guardrails" => array(
    "no_escribe_bd" => true,
    "no_crea_publicaciones" => true,
    "no_registra_cotizacion" => true,
    "no_descuenta_inventario" => true,
    "no_toca_ecom_legacy" => true
  ),
  "bloqueos" => $bloqueos
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function valorEntregableFrontend($datos, $ruta, $default = null) {
  $actual = $datos;
  foreach ($ruta as $segmento) {
    if (!is_array($actual) || !array_key_exists($segmento, $actual)) {
      return $default;
    }
    $actual = $actual[$segmento];
  }
  return $actual;
}
