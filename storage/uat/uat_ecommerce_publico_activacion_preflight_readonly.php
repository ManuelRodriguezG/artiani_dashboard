<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-12.
 * Proposito: preflight read-only antes de activar ecommerce publico Fase 1.
 * Impacto: valida respaldo, DDL pendiente, contratos API y publicabilidad sin escribir BD.
 * Contrato: no ejecuta DDL, no crea publicaciones, no registra cotizaciones y no mueve inventario.
 */

$args = isset($argv) ? $argv : array();
$respaldo = "";
foreach ($args as $arg) {
  if (strpos($arg, "--respaldo=") === 0) {
    $respaldo = trim(substr($arg, 11), "\"' ");
  }
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/core/DBSchema.php";
require_once "../app/modelos/EcommercePublicoEsquema.php";
require_once "../app/modelos/EcommerceCatalogoPublico.php";

$esquema = new EcommercePublicoEsquema();
$api = new EcommerceCatalogoPublico();

$validacionRespaldo = validarRespaldo($respaldo);
$auditoriaSchema = $esquema->auditarEcommercePublico();
$planSchema = $esquema->planActualizarEcommercePublico(false);
$estado = $api->estadoApiPublica();
$contratos = $api->contratosApiPublicos();
$configuracion = $api->configuracionPublica();
$publicabilidad = $api->auditarPublicabilidad(array("limite" => 10, "solo_publicables" => 1));
$dryrun = $api->cotizacionDryRun(array("items" => array(array("id_publicacion" => 1, "cantidad" => 1))));
$registroBloqueado = $api->cotizacionRegistrarBloqueada(array());
$publicacionBloqueada = $api->guardarPublicacionBloqueada(array("id_sku" => 1291));

$ddlTotal = intval(valor($planSchema, array("depurar", "ddl_total"), 0));
$tablasFaltantes = intval(valor($auditoriaSchema, array("depurar", "tablas_faltantes"), 0));
$endpoints = valor($contratos, array("depurar", "endpoints_publicos"), array());
$skusPublicables = intval(valor($publicabilidad, array("depurar", "resumen", "skus_publicables_fase_1"), 0));

$bloqueos = array();
if (!$validacionRespaldo["ok"]) {
  $bloqueos[] = "Falta respaldo externo valido o referencia suficiente";
}
if ($ddlTotal !== 5) {
  $bloqueos[] = "Plan DDL esperado 5 tablas, recibido " . $ddlTotal;
}
if (count($endpoints) < 8) {
  $bloqueos[] = "Contratos API incompletos";
}
if ($skusPublicables <= 0) {
  $bloqueos[] = "No hay SKUs publicables para lote inicial";
}
if (valor($registroBloqueado, array("depurar", "bloqueado"), false) !== true) {
  $bloqueos[] = "Registro real de cotizacion debe seguir bloqueado antes de activacion";
}
if (valor($publicacionBloqueada, array("depurar", "bloqueado"), false) !== true) {
  $bloqueos[] = "Guardado real de publicaciones debe seguir bloqueado antes de DDL";
}

echo json_encode(array(
  "ok" => empty($bloqueos),
  "modo" => "read-only",
  "respaldo" => $validacionRespaldo,
  "schema" => array(
    "tablas_faltantes" => $tablasFaltantes,
    "ddl_total" => $ddlTotal,
    "ddl_pendiente" => valor($estado, array("depurar", "schema", "ddl_pendiente"), true)
  ),
  "api" => array(
    "version" => valor($contratos, array("api", "version"), ""),
    "endpoints_total" => count($endpoints),
    "ready" => valor($estado, array("depurar", "ready"), false)
  ),
  "configuracion" => array(
    "configurado" => valor($configuracion, array("depurar", "configurado"), false),
    "whatsapp_configurado" => trim((string) valor($configuracion, array("depurar", "configuracion", "whatsapp_numero_principal"), "")) !== "",
    "cors_configurado" => trim((string) valor($configuracion, array("depurar", "configuracion", "cors_origenes_permitidos"), "")) !== ""
  ),
  "publicabilidad" => array(
    "skus_publicables_fase_1" => $skusPublicables,
    "muestra_total" => count(valor($publicabilidad, array("depurar", "candidatos"), array()))
  ),
  "cotizaciones" => array(
    "dryrun_responde" => valor($dryrun, array("depurar", "dry_run"), false),
    "registro_real_bloqueado" => valor($registroBloqueado, array("depurar", "bloqueado"), false)
  ),
  "publicaciones" => array(
    "guardado_real_bloqueado" => valor($publicacionBloqueada, array("depurar", "bloqueado"), false)
  ),
  "bloqueos" => $bloqueos,
  "siguiente_paso" => empty($bloqueos)
    ? "Preflight listo. Se puede solicitar autorizacion DDL con respaldo externo."
    : "Resolver bloqueos antes de solicitar autorizacion DDL."
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

function validarRespaldo($respaldo) {
  $esRutaLocal = preg_match('/^[A-Za-z]:[\\\\\\/]/', $respaldo) === 1 || strpos($respaldo, "\\") !== false || strpos($respaldo, "/") !== false;
  $existe = false;
  $legible = false;
  $tamano = null;
  if ($respaldo !== "" && $esRutaLocal) {
    $existe = file_exists($respaldo);
    $legible = $existe && is_readable($respaldo);
    $tamano = $existe ? filesize($respaldo) : null;
  }
  $okReferencia = strlen($respaldo) >= 8;
  $okRuta = !$esRutaLocal || ($existe && $legible && $tamano !== null && $tamano > 0);
  return array(
    "ok" => $okReferencia && $okRuta,
    "referencia" => $respaldo,
    "parece_ruta_local" => $esRutaLocal,
    "archivo_existe" => $esRutaLocal ? $existe : null,
    "archivo_legible" => $esRutaLocal ? $legible : null,
    "tamano_bytes" => $tamano
  );
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
