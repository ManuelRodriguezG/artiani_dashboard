<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-13.
 * Proposito: validar la compuerta final para avisar que el frontend externo puede consumir datos reales.
 * Impacto: evita declarar verde solo por existir DDL; exige publicacion real y cotizacion dry-run con item publicado.
 * Contrato: read-only; no crea publicaciones, no registra cotizaciones, no toca inventario y no usa legacy `ecom_*`.
 */

$opciones = getopt("", array(
  "base::"
));

$base = isset($opciones["base"]) ? rtrim(trim((string) $opciones["base"]), "/") : "http://panel.com.local";

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/EcommerceCatalogoPublico.php";

$api = new EcommerceCatalogoPublico();
$estado = $api->estadoApiPublica();
$configuracion = $api->configuracionPublica();
$catalogo = $api->catalogoPublico(array("limite" => 1));
$items = valorGreenGate($catalogo, array("depurar", "items"), array());
$primerItem = !empty($items) ? $items[0] : array();
$dryrun = array();
if (!empty($primerItem)) {
  $dryrun = $api->cotizacionDryRun(array(
    "items" => array(array(
      "id_publicacion" => intval(valorGreenGate($primerItem, array("id_publicacion"), 0)),
      "cantidad" => 1
    )),
    "contacto" => array(
      "nombre" => "Prueba read-only",
      "telefono" => "",
      "mensaje" => "Validacion green gate"
    )
  ));
}

$http = array(
  "estado" => requestGreenGate($base . "/ecommercePublico/estado"),
  "catalogo" => requestGreenGate($base . "/ecommercePublico/catalogo?limite=1"),
  "configuracion" => requestGreenGate($base . "/ecommercePublico/configuracion")
);

$bloqueos = array();
if (valorGreenGate($estado, array("depurar", "schema", "ddl_pendiente"), true)) {
  $bloqueos[] = "ddl_ecommerce_publico_pendiente";
}
if (intval(valorGreenGate($estado, array("depurar", "publicaciones", "total_publicadas"), 0)) <= 0) {
  $bloqueos[] = "sin_publicaciones_activas";
}
if (trim((string) valorGreenGate($configuracion, array("depurar", "configuracion", "whatsapp_numero_principal"), "")) === "") {
  $bloqueos[] = "whatsapp_no_configurado";
}
if (trim((string) valorGreenGate($configuracion, array("depurar", "configuracion", "cors_origenes_permitidos"), "")) === "") {
  $bloqueos[] = "cors_origenes_permitidos_no_configurado";
}
if (empty($primerItem)) {
  $bloqueos[] = "catalogo_sin_item_real";
}
if (empty($dryrun) || !empty($dryrun["error"]) || empty(valorGreenGate($dryrun, array("depurar", "lineas"), array()))) {
  $bloqueos[] = "cotizacion_dryrun_sin_item_real";
}
foreach ($http as $nombre => $prueba) {
  if (empty($prueba["json_valido"])) {
    $bloqueos[] = "http_" . $nombre . "_no_json";
  }
}

$verde = empty($bloqueos);

echo json_encode(array(
  "ok" => $verde,
  "modo" => "read-only",
  "senal_frontend" => $verde ? "verde_datos_reales" : "amarillo_mock_contratos",
  "puede_integrar_datos_reales" => $verde,
  "base_url_verificada" => $base,
  "frontend_base_correcta" => $base . "/ecommercePublico",
  "bloqueos" => array_values(array_unique($bloqueos)),
  "estado" => array(
    "ready" => valorGreenGate($estado, array("depurar", "ready"), false),
    "ddl_pendiente" => valorGreenGate($estado, array("depurar", "schema", "ddl_pendiente"), true),
    "publicadas" => intval(valorGreenGate($estado, array("depurar", "publicaciones", "total_publicadas"), 0))
  ),
  "configuracion" => array(
    "whatsapp_configurado" => trim((string) valorGreenGate($configuracion, array("depurar", "configuracion", "whatsapp_numero_principal"), "")) !== "",
    "cors_configurado" => trim((string) valorGreenGate($configuracion, array("depurar", "configuracion", "cors_origenes_permitidos"), "")) !== ""
  ),
  "catalogo" => array(
    "tiene_item_real" => !empty($primerItem),
    "primer_item" => empty($primerItem) ? null : array(
      "id_publicacion" => intval(valorGreenGate($primerItem, array("id_publicacion"), 0)),
      "id_sku" => intval(valorGreenGate($primerItem, array("id_sku"), 0)),
      "slug" => valorGreenGate($primerItem, array("slug"), ""),
      "nombre" => valorGreenGate($primerItem, array("nombre"), ""),
      "disponibilidad" => valorGreenGate($primerItem, array("disponibilidad"), "")
    )
  ),
  "cotizacion_dryrun" => array(
    "ok" => !empty($dryrun) && empty($dryrun["error"]) && !empty(valorGreenGate($dryrun, array("depurar", "lineas"), array())),
    "lineas" => count(valorGreenGate($dryrun, array("depurar", "lineas"), array())),
    "no_escribe_bd" => valorGreenGate($dryrun, array("depurar", "no_escribe_bd"), true),
    "no_descuenta_inventario" => valorGreenGate($dryrun, array("depurar", "no_descuenta_inventario"), true)
  ),
  "http" => $http,
  "mensaje_para_frontend" => $verde
    ? "Ya puedes integrar la vista ecommerce externa con datos reales."
    : "Aun no avisar verde; resolver bloqueos antes de integrar datos reales.",
  "guardrails" => array(
    "no_escribe_bd" => true,
    "no_registra_cotizacion" => true,
    "no_descuenta_inventario" => true,
    "no_toca_ecom_legacy" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

function requestGreenGate($url) {
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
    "tipo" => is_array($json) ? valorGreenGate($json, array("tipo"), "") : "",
    "mensaje" => is_array($json) ? valorGreenGate($json, array("mensaje"), "") : "",
    "raw_inicio" => substr((string) $raw, 0, 80)
  );
}

function valorGreenGate($datos, $ruta, $default = null) {
  $actual = $datos;
  foreach ($ruta as $segmento) {
    if (!is_array($actual) || !array_key_exists($segmento, $actual)) {
      return $default;
    }
    $actual = $actual[$segmento];
  }
  return $actual;
}
