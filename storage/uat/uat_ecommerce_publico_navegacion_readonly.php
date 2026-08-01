<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-31.
 * Proposito: validar navegacion publica del ecommerce.
 * Impacto: protege menus/chips frontend por mascota, necesidad, categoria, marca y disponibilidad.
 * Contrato: read-only; no escribe BD y solo usa informacion derivada de publicaciones.
 */

$opciones = getopt("", array("base::", "limite::"));
$base = isset($opciones["base"]) ? rtrim(trim((string) $opciones["base"]), "/") : "http://panel.com.local";
$limite = isset($opciones["limite"]) ? max(1, min(30, intval($opciones["limite"]))) : 5;

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/EcommerceCatalogoPublico.php";

$api = new EcommerceCatalogoPublico();
$navegacion = $api->navegacionPublica(array("limite" => $limite));
$bootstrap = $api->bootstrapPublico(array("limite_secciones" => 3));
$http = requestNavegacion($base . "/ecommercePublico/navegacion?limite=" . intval($limite));
$dep = valorNavegacion($navegacion, array("depurar"), array());
$bootDep = valorNavegacion($bootstrap, array("depurar"), array());

$bloqueos = array();
if (!empty($navegacion["error"])) {
  $bloqueos[] = "navegacion_modelo_error";
}
if (intval(valorNavegacion($dep, array("resumen", "total_items"), 0)) <= 0) {
  $bloqueos[] = "navegacion_sin_items";
}
if (count(valorNavegacion($dep, array("primaria"), array())) <= 0) {
  $bloqueos[] = "navegacion_primaria_vacia";
}
if (count(valorNavegacion($dep, array("mascotas"), array())) <= 0) {
  $bloqueos[] = "navegacion_mascotas_vacia";
}
if (count(valorNavegacion($dep, array("categorias"), array())) <= 0) {
  $bloqueos[] = "navegacion_categorias_vacia";
}
if (empty(valorNavegacion($dep, array("guardrails", "solo_derivado_de_publicaciones"), false))) {
  $bloqueos[] = "guardrail_derivado_publicaciones_faltante";
}
if (intval(valorNavegacion($bootDep, array("navegacion", "resumen", "total_items"), 0)) <= 0) {
  $bloqueos[] = "bootstrap_sin_navegacion";
}
if (empty($http["json_valido"]) || $http["tipo"] !== "success") {
  $bloqueos[] = "http_navegacion_no_success";
}

$ok = empty($bloqueos);
echo json_encode(array(
  "ok" => $ok,
  "modo" => "read-only",
  "senal_frontend" => $ok ? "navegacion_publica_lista" : "navegacion_publica_incompleta",
  "base_url" => $base,
  "bloqueos" => array_values(array_unique($bloqueos)),
  "navegacion" => array(
    "total_items" => intval(valorNavegacion($dep, array("resumen", "total_items"), 0)),
    "primaria" => count(valorNavegacion($dep, array("primaria"), array())),
    "mascotas" => count(valorNavegacion($dep, array("mascotas"), array())),
    "necesidades" => count(valorNavegacion($dep, array("necesidades"), array())),
    "categorias" => count(valorNavegacion($dep, array("categorias"), array())),
    "marcas" => count(valorNavegacion($dep, array("marcas"), array())),
    "disponibilidad" => count(valorNavegacion($dep, array("disponibilidad"), array()))
  ),
  "bootstrap_incluye_navegacion" => intval(valorNavegacion($bootDep, array("navegacion", "resumen", "total_items"), 0)) > 0,
  "http" => $http,
  "guardrails" => array(
    "no_escribe_bd" => true,
    "solo_derivado_de_publicaciones" => true,
    "no_expone_secretos" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function requestNavegacion($url) {
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
    "tipo" => is_array($json) ? valorNavegacion($json, array("tipo"), "") : "",
    "mensaje" => is_array($json) ? valorNavegacion($json, array("mensaje"), "") : "",
    "raw_inicio" => substr((string) $raw, 0, 80)
  );
}

function valorNavegacion($datos, $ruta, $default = null) {
  $actual = $datos;
  foreach ($ruta as $segmento) {
    if (!is_array($actual) || !array_key_exists($segmento, $actual)) {
      return $default;
    }
    $actual = $actual[$segmento];
  }
  return $actual;
}
