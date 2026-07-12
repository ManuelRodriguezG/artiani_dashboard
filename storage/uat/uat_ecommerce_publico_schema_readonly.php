<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-11.
 * Proposito: preflight read-only para autorizar esquema ecommerce publico Fase 1.
 * Impacto: valida respaldo, auditoria y DDL propuesto sin escribir BD.
 * Contrato: no ejecuta DDL, no crea publicaciones, no registra cotizaciones y no toca `ecom_*`.
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
$catalogo = new EcommerceCatalogoPublico();

$validacionRespaldo = validarRespaldoEcommercePublico($respaldo);
$auditoria = $esquema->auditarEcommercePublico();
$plan = $esquema->planActualizarEcommercePublico(false);
$publicabilidad = $catalogo->auditarPublicabilidad(array("limite" => 10));

$ddlTotal = intval(valorEcommercePublico($plan, array("depurar", "ddl_total"), 0));
$ddlPendientes = intval(valorEcommercePublico($plan, array("depurar", "ddl_pendientes"), 0));
$tablasFaltantes = intval(valorEcommercePublico($auditoria, array("depurar", "tablas_faltantes"), 0));
$skusPublicables = intval(valorEcommercePublico($publicabilidad, array("depurar", "resumen", "skus_publicables_fase_1"), 0));

$bloqueos = array();
if (!$validacionRespaldo["ok"]) {
  $bloqueos[] = "Respaldo externo no valido o referencia insuficiente";
}
if (!empty($plan["error"])) {
  $bloqueos[] = "El plan DDL reporta errores";
}
if ($ddlTotal !== 5) {
  $bloqueos[] = "DDL esperado 5 tablas, recibido " . $ddlTotal;
}
if (!empty($publicabilidad["error"])) {
  $bloqueos[] = "La auditoria de publicabilidad reporta errores";
}

echo json_encode(array(
  "ok" => empty($bloqueos),
  "modo" => "read-only",
  "respaldo" => $validacionRespaldo,
  "auditoria" => array(
    "tablas_faltantes" => $tablasFaltantes,
    "detalle" => valorEcommercePublico($auditoria, array("depurar", "auditoria"), array())
  ),
  "ddl" => array(
    "total" => $ddlTotal,
    "pendientes" => $ddlPendientes,
    "plan" => valorEcommercePublico($plan, array("depurar", "plan"), array())
  ),
  "publicabilidad" => array(
    "skus_publicables_fase_1" => $skusPublicables,
    "resumen" => valorEcommercePublico($publicabilidad, array("depurar", "resumen"), array())
  ),
  "guardrails" => array(
    "token_apply" => "ECOMMERCE_PUBLICO_DDL_FASE1",
    "no_ejecuta_ddl" => true,
    "no_toca_ecom_legacy" => true,
    "no_mueve_inventario" => true,
    "no_crea_cotizaciones_reales" => true,
    "no_crea_checkout" => true
  ),
  "bloqueos" => $bloqueos,
  "siguiente_paso" => empty($bloqueos)
    ? "Listo para solicitar autorizacion textual del DDL Fase 1."
    : "Resolver bloqueos antes de solicitar autorizacion."
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

function validarRespaldoEcommercePublico($respaldo) {
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

function valorEcommercePublico($datos, $ruta, $default = null) {
  $actual = $datos;
  foreach ($ruta as $segmento) {
    if (!is_array($actual) || !array_key_exists($segmento, $actual)) {
      return $default;
    }
    $actual = $actual[$segmento];
  }
  return $actual;
}
