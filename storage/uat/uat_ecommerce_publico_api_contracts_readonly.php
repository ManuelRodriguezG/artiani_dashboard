<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-12.
 * Proposito: validar contratos API ecommerce publico en modo read-only para frontend externo.
 * Impacto: revisa manifiesto, estado, configuracion, catalogo y cotizacion dry-run sin escribir BD.
 * Contrato: no ejecuta DDL, no crea publicaciones, no registra cotizaciones y no toca `ecom_*`.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/EcommerceCatalogoPublico.php";

$modelo = new EcommerceCatalogoPublico();

$contratos = $modelo->contratosApiPublicos();
$estado = $modelo->estadoApiPublica();
$configuracion = $modelo->configuracionPublica();
$catalogo = $modelo->catalogoPublico(array("limite" => 3));
$filtros = $modelo->filtrosPublicos();
$disponibilidad = $modelo->disponibilidadPublica(array("id_sku" => 1));
$dryrun = $modelo->cotizacionDryRun(array(
  "items" => array(
    array("id_publicacion" => 1, "cantidad" => 2)
  )
));
$registroBloqueado = $modelo->cotizacionRegistrarBloqueada(array(
  "items" => array(
    array("id_publicacion" => 1, "cantidad" => 2)
  ),
  "contacto" => array("telefono" => "5555555555")
));
$publicacionBloqueada = $modelo->guardarPublicacionBloqueada(array(
  "id_sku" => 1291,
  "estatus_publicacion" => "borrador"
));

$bloqueos = array();
$endpoints = valor($contratos, array("depurar", "endpoints_publicos"), array());
if (count($endpoints) < 9) {
  $bloqueos[] = "El manifiesto API debe incluir al menos 9 endpoints documentados";
}
if (!contieneEndpoint($endpoints, "/ecommercePublico/catalogo")) {
  $bloqueos[] = "Falta contrato /ecommercePublico/catalogo";
}
if (!contieneEndpoint($endpoints, "/ecommercePublico/cotizacion_dryrun")) {
  $bloqueos[] = "Falta contrato /ecommercePublico/cotizacion_dryrun";
}
if (!contieneEndpoint($endpoints, "/ecommercePublico/cotizacion_registrar")) {
  $bloqueos[] = "Falta contrato /ecommercePublico/cotizacion_registrar";
}
if (valor($contratos, array("api", "version"), "") !== "fase1-2026-07-12") {
  $bloqueos[] = "Version API inesperada en contrato";
}
if (valor($estado, array("depurar", "guardrails", "no_checkout"), false) !== true) {
  $bloqueos[] = "Estado API no confirma guardrail no_checkout";
}
if (valor($configuracion, array("depurar", "configuracion", "mostrar_stock_exacto"), "") !== "0") {
  $bloqueos[] = "Configuracion publica debe mantener mostrar_stock_exacto=0";
}
if (valor($catalogo, array("depurar", "guardrails", "no_ecom_legacy_fuente"), true) !== true && valor($catalogo, array("depurar", "configurado"), false) === true) {
  $bloqueos[] = "Catalogo publico debe confirmar que no usa ecom legacy como fuente";
}
if (valor($disponibilidad, array("depurar", "mostrar_cantidad_exacta"), false) === true) {
  $bloqueos[] = "Disponibilidad publica no debe mostrar cantidad exacta";
}
if (valor($dryrun, array("depurar", "dry_run"), false) !== true) {
  $bloqueos[] = "Cotizacion dry-run debe identificarse como dry_run";
}
if (valor($registroBloqueado, array("depurar", "bloqueado"), false) !== true || valor($registroBloqueado, array("depurar", "no_escribe_bd"), false) !== true) {
  $bloqueos[] = "Registro real de cotizacion debe seguir bloqueado y sin escritura";
}
if (valor($publicacionBloqueada, array("depurar", "bloqueado"), false) !== true || valor($publicacionBloqueada, array("depurar", "no_escribe_bd"), false) !== true) {
  $bloqueos[] = "Guardado interno de publicacion debe seguir bloqueado y sin escritura";
}

echo json_encode(array(
  "ok" => empty($bloqueos),
  "modo" => "read-only",
  "api" => array(
    "version" => valor($contratos, array("api", "version"), ""),
    "base_path" => valor($contratos, array("depurar", "base_path"), "/ecommercePublico"),
    "endpoints_total" => count($endpoints)
  ),
  "estado" => array(
    "ready" => valor($estado, array("depurar", "ready"), false),
    "ddl_pendiente" => valor($estado, array("depurar", "schema", "ddl_pendiente"), true),
    "publicadas" => valor($estado, array("depurar", "publicaciones", "total_publicadas"), 0),
    "publicables" => valor($estado, array("depurar", "publicaciones", "skus_publicables_fase_1"), 0)
  ),
  "contratos" => array(
    "catalogo" => contieneEndpoint($endpoints, "/ecommercePublico/catalogo"),
    "configuracion" => contieneEndpoint($endpoints, "/ecommercePublico/configuracion"),
    "cotizacion_dryrun" => contieneEndpoint($endpoints, "/ecommercePublico/cotizacion_dryrun"),
    "cotizacion_registrar_bloqueado" => contieneEndpoint($endpoints, "/ecommercePublico/cotizacion_registrar")
  ),
  "guardrails" => array(
    "no_escribe_bd" => true,
    "no_ejecuta_ddl" => true,
    "no_toca_ecom_legacy" => true,
    "no_mueve_inventario" => true,
    "registro_cotizacion_bloqueado" => valor($registroBloqueado, array("depurar", "bloqueado"), false),
    "guardado_publicacion_bloqueado" => valor($publicacionBloqueada, array("depurar", "bloqueado"), false)
  ),
  "bloqueos" => $bloqueos,
  "siguiente_paso" => empty($bloqueos)
    ? "Contratos API listos para integracion del proyecto ecommerce externo en modo read-only."
    : "Resolver bloqueos antes de entregar contrato al frontend externo."
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

function contieneEndpoint($endpoints, $ruta) {
  foreach ($endpoints as $endpoint) {
    if (isset($endpoint["ruta"]) && $endpoint["ruta"] === $ruta) {
      return true;
    }
  }
  return false;
}

function valor($datos, $ruta, $default = null) {
  $actual = $datos;
  foreach ($ruta as $segmento) {
    if (!is_array($actual) || !array_key_exists($segmento, $actual)) {
      return $default;
    }
    $actual = $actual[$segmento];
  }
  return $actual;
}
