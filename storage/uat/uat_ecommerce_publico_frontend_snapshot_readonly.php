<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-18.
 * Proposito: generar un snapshot vivo de integracion para el frontend ecommerce externo.
 * Impacto: entrega ejemplos reales y normalizados de catalogo, producto, disponibilidad y dry-run sin escribir BD.
 * Contrato: read-only; no ejecuta DDL, no registra cotizaciones, no descuenta inventario y no toca legacy ecom_*.
 */

$opciones = getopt("", array("base::", "origin::", "limite::", "min_publicaciones::"));
$base = isset($opciones["base"]) ? rtrim(trim((string) $opciones["base"]), "/") : "http://panel.com.local";
$origin = isset($opciones["origin"]) ? trim((string) $opciones["origin"]) : "http://artiani.com.local";
$limite = isset($opciones["limite"]) ? max(1, min(10, intval($opciones["limite"]))) : 2;
$minPublicaciones = isset($opciones["min_publicaciones"]) ? max(1, intval($opciones["min_publicaciones"])) : 1;

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/EcommerceCatalogoPublico.php";

$api = new EcommerceCatalogoPublico();

$estado = $api->estadoApiPublica();
$configuracion = $api->configuracionPublica();
$filtros = $api->filtrosPublicos();
$catalogo = $api->catalogoPublico(array("limite" => $limite));
$items = valorSnapshot($catalogo, array("depurar", "items"), array());
$primerItem = !empty($items) ? $items[0] : array();
$producto = !empty($primerItem) ? $api->productoPublico((string) valorSnapshot($primerItem, array("slug"), "")) : array();
$disponibilidad = !empty($primerItem) ? $api->disponibilidadPublica(array(
  "id_sku" => intval(valorSnapshot($primerItem, array("id_sku"), 0))
)) : array();

$itemsDryRun = array();
foreach ($items as $item) {
  $itemsDryRun[] = array(
    "id_publicacion" => intval(valorSnapshot($item, array("id_publicacion"), 0)),
    "slug" => (string) valorSnapshot($item, array("slug"), ""),
    "cantidad" => 1
  );
}
$dryrunPayload = array(
  "items" => $itemsDryRun,
  "contacto" => array(
    "nombre" => "Cliente prueba frontend",
    "telefono" => "",
    "mensaje" => "Quiero validar disponibilidad de estos productos."
  ),
  "utm" => array(
    "source" => "frontend-local",
    "origin" => $origin
  )
);
$dryrun = !empty($itemsDryRun) ? $api->cotizacionDryRun($dryrunPayload) : array();

$bloqueos = array();
if (valorSnapshot($estado, array("depurar", "ready"), false) !== true) {
  $bloqueos[] = "estado_ready_false";
}
if (valorSnapshot($estado, array("depurar", "schema", "ddl_pendiente"), true) !== false) {
  $bloqueos[] = "ddl_pendiente";
}
if (empty($items)) {
  $bloqueos[] = "catalogo_sin_items_reales";
}
$publicadas = intval(valorSnapshot($estado, array("depurar", "publicaciones", "total_publicadas"), 0));
if ($publicadas < $minPublicaciones) {
  $bloqueos[] = "publicaciones_menor_a_minimo_" . $minPublicaciones;
}
if (empty($dryrun) || !empty($dryrun["error"])) {
  $bloqueos[] = "dryrun_no_validado";
}
if (!$api->origenCorsPermitido($origin)) {
  $bloqueos[] = "origin_cors_no_configurado";
}

$baseApi = $base . "/ecommercePublico";
$primerSlug = (string) valorSnapshot($primerItem, array("slug"), "slug-real");
$primerSku = intval(valorSnapshot($primerItem, array("id_sku"), 0));

echo json_encode(array(
  "ok" => empty($bloqueos),
  "modo" => "read-only",
  "fecha_generacion" => date("Y-m-d H:i:s"),
  "senal_frontend" => empty($bloqueos) ? "verde_datos_reales" : "revisar_bloqueos",
  "base_api" => $baseApi,
  "origin_frontend" => $origin,
  "cors" => array(
    "origin_permitido" => $api->origenCorsPermitido($origin),
    "sin_wildcard" => true,
    "preflight_uat" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_cors_preflight_readonly.php --base=" . $base . " --origin=" . $origin
  ),
  "variables_env_frontend" => array(
    "VITE_ERP_API_BASE_URL" => $base,
    "VITE_ERP_ECOMMERCE_BASE_PATH" => "/ecommercePublico",
    "VITE_ERP_ECOMMERCE_API_VERSION" => "fase1-2026-07-12"
  ),
  "endpoints_para_consumir" => array(
    "estado" => "GET " . $baseApi . "/estado",
    "configuracion" => "GET " . $baseApi . "/configuracion",
    "filtros" => "GET " . $baseApi . "/filtros",
    "catalogo" => "GET " . $baseApi . "/catalogo?pagina=1&limite=24",
    "producto" => "GET " . $baseApi . "/producto/" . $primerSlug,
    "disponibilidad" => "GET " . $baseApi . "/disponibilidad?id_sku=" . $primerSku,
    "cotizacion_dryrun" => "POST " . $baseApi . "/cotizacion_dryrun"
  ),
  "fetch_minimo" => array(
    "catalogo" => "fetch(\"" . $baseApi . "/catalogo?pagina=1&limite=24\")",
    "cotizacion_dryrun" => "fetch(\"" . $baseApi . "/cotizacion_dryrun\", {method:\"POST\", headers:{\"Content-Type\":\"application/json\"}, body: JSON.stringify(payload)})"
  ),
  "resumen" => array(
    "ready" => valorSnapshot($estado, array("depurar", "ready"), false),
    "ddl_pendiente" => valorSnapshot($estado, array("depurar", "schema", "ddl_pendiente"), true),
    "publicadas" => $publicadas,
    "min_publicaciones_esperadas" => $minPublicaciones,
    "min_publicaciones_ok" => $publicadas >= $minPublicaciones,
    "catalogo_items_snapshot" => count($items),
    "whatsapp_configurado" => trim((string) valorSnapshot($configuracion, array("depurar", "configuracion", "whatsapp_numero_principal"), "")) !== "",
    "dryrun_ok" => !empty($dryrun) && empty($dryrun["error"]),
    "registro_real_bloqueado_fase1" => true
  ),
  "catalogo_items" => $items,
  "producto_detalle_ejemplo" => valorSnapshot($producto, array("depurar", "item"), null),
  "filtros_disponibles" => array(
    "mascotas" => valorSnapshot($filtros, array("depurar", "mascotas"), array()),
    "necesidades" => valorSnapshot($filtros, array("depurar", "necesidades"), array()),
    "marcas_total" => count(valorSnapshot($filtros, array("depurar", "marcas"), array())),
    "categorias_total" => count(valorSnapshot($filtros, array("depurar", "categorias"), array()))
  ),
  "disponibilidad_ejemplo" => valorSnapshot($disponibilidad, array("depurar"), array()),
  "dryrun_payload_ejemplo" => $dryrunPayload,
  "dryrun_respuesta_resumen" => array(
    "tipo" => valorSnapshot($dryrun, array("tipo"), ""),
    "mensaje" => valorSnapshot($dryrun, array("mensaje"), ""),
    "lineas" => valorSnapshot($dryrun, array("depurar", "lineas"), array()),
    "totales" => valorSnapshot($dryrun, array("depurar", "totales"), array()),
    "bloqueos" => valorSnapshot($dryrun, array("depurar", "bloqueos"), array()),
    "whatsapp_preview" => valorSnapshot($dryrun, array("depurar", "whatsapp_preview"), "")
  ),
  "guardrails" => array(
    "no_escribe_bd" => true,
    "no_registra_cotizacion" => true,
    "no_descuenta_inventario" => true,
    "no_expone_stock_exacto" => true,
    "no_usa_legacy_ecom_fuente" => true,
    "no_checkout" => true
  ),
  "bloqueos" => $bloqueos
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function valorSnapshot($datos, $ruta, $default = null) {
  $actual = $datos;
  foreach ($ruta as $segmento) {
    if (!is_array($actual) || !array_key_exists($segmento, $actual)) {
      return $default;
    }
    $actual = $actual[$segmento];
  }
  return $actual;
}
