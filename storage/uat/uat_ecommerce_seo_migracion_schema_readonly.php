<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-09-03.
 * Proposito: preflight read-only para esquema SEO/migracion URLs ecommerce.
 * Impacto: valida respaldo, auditoria, DDL propuesto y endpoints SEO sin escribir BD.
 * Contrato: no ejecuta DDL, no importa URLs, no crea redirecciones y no toca frontend.
 */

$args = argumentos($argv);
$respaldo = isset($args["respaldo"]) ? $args["respaldo"] : "";

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/core/DBSchema.php";
require_once "../app/modelos/EcommercePublicoEsquema.php";
require_once "../app/modelos/EcommerceCatalogoPublico.php";

$esquema = new EcommercePublicoEsquema();
$catalogo = new EcommerceCatalogoPublico();
$validacionRespaldo = validarRespaldoSeoMigracion($respaldo);
$auditoria = $esquema->auditarSeoMigracion();
$plan = $esquema->planActualizarSeoMigracion(false);
$estado = $catalogo->seoEstadoPublico();
$urls = $catalogo->seoUrlsPublicas(array("limite" => 20));
$sitemap = $catalogo->seoSitemapPublico(array("limite" => 20));

$ddlTotal = intval(valorSeoMigracion($plan, array("depurar", "ddl_total"), 0));
$ddlPendientes = intval(valorSeoMigracion($plan, array("depurar", "ddl_pendientes"), 0));
$tablasFaltantes = intval(valorSeoMigracion($auditoria, array("depurar", "tablas_faltantes"), 0));
$urlsItems = valorSeoMigracion($urls, array("depurar", "urls"), array());
$sitemapItems = valorSeoMigracion($sitemap, array("depurar", "items"), array());

$bloqueos = array();
if (!$validacionRespaldo["ok"]) {
  $bloqueos[] = "Respaldo externo no valido o referencia insuficiente";
}
if (!empty($plan["error"])) {
  $bloqueos[] = "El plan DDL SEO reporta errores";
}
if ($ddlTotal !== 5) {
  $bloqueos[] = "DDL esperado 5 tablas, recibido " . $ddlTotal;
}
if (strpos(json_encode($urlsItems), "/ecommercePublico") !== false || strpos(json_encode($sitemapItems), "/ecommercePublico") !== false) {
  $bloqueos[] = "URLs publicas contienen rutas internas /ecommercePublico";
}

echo json_encode(array(
  "ok" => empty($bloqueos),
  "modo" => "read-only",
  "respaldo" => $validacionRespaldo,
  "auditoria" => array(
    "tablas_faltantes" => $tablasFaltantes,
    "detalle" => valorSeoMigracion($auditoria, array("depurar", "auditoria"), array())
  ),
  "ddl" => array(
    "total" => $ddlTotal,
    "pendientes" => $ddlPendientes,
    "plan" => valorSeoMigracion($plan, array("depurar", "plan"), array())
  ),
  "seo" => array(
    "estado" => valorSeoMigracion($estado, array("depurar"), array()),
    "urls_muestra" => count($urlsItems),
    "sitemap_muestra" => count($sitemapItems),
    "urls_sin_api_interna" => strpos(json_encode($urlsItems), "/ecommercePublico") === false,
    "sitemap_sin_api_interna" => strpos(json_encode($sitemapItems), "/ecommercePublico") === false
  ),
  "guardrails" => array(
    "token_apply" => "ECOMMERCE_SEO_MIGRACION_DDL",
    "no_ejecuta_ddl" => true,
    "no_escribe_bd" => true,
    "no_importa_urls" => true,
    "no_crea_redirecciones" => true,
    "frontend_aplica_301" => true
  ),
  "bloqueos" => $bloqueos,
  "siguiente_paso" => empty($bloqueos)
    ? "Listo para solicitar autorizacion textual del DDL SEO/migracion."
    : "Resolver bloqueos antes de solicitar autorizacion."
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function argumentos($argv) {
  $salida = array();
  foreach ((array) $argv as $arg) {
    if (strpos($arg, "--") !== 0 || strpos($arg, "=") === false) { continue; }
    $partes = explode("=", substr($arg, 2), 2);
    $salida[$partes[0]] = trim($partes[1], "\"' ");
  }
  return $salida;
}

function validarRespaldoSeoMigracion($respaldo) {
  $esRutaLocal = preg_match('/^[A-Za-z]:[\\\\\\/]/', $respaldo) === 1 || strpos($respaldo, "\\") !== false || strpos($respaldo, "/") !== false;
  $existe = false;
  $legible = false;
  $tamano = null;
  if ($respaldo !== "" && $esRutaLocal) {
    $existe = file_exists($respaldo);
    $legible = $existe && is_readable($respaldo);
    $tamano = $existe ? filesize($respaldo) : null;
  }
  $placeholder = respaldoPlaceholderSeoMigracion($respaldo);
  $okReferencia = strlen($respaldo) >= 8 && !$placeholder;
  $okRuta = !$esRutaLocal || ($existe && $legible && $tamano !== null && $tamano > 0);
  return array(
    "ok" => $okReferencia && $okRuta,
    "referencia_presente" => $okReferencia,
    "referencia" => $respaldo,
    "parece_ruta_local" => $esRutaLocal,
    "archivo_existe" => $esRutaLocal ? $existe : null,
    "archivo_legible" => $esRutaLocal ? $legible : null,
    "tamano_bytes" => $tamano,
    "placeholder_bloqueado" => $placeholder
  );
}

function respaldoPlaceholderSeoMigracion($valor) {
  $valor = strtoupper(trim((string) $valor));
  return $valor === ""
    || strpos($valor, "RUTA_O_REFERENCIA") !== false
    || strpos($valor, "PLACEHOLDER") !== false
    || strpos($valor, "REVISION_READONLY") !== false;
}

function valorSeoMigracion($datos, $ruta, $default = null) {
  $actual = $datos;
  foreach ($ruta as $segmento) {
    if (!is_array($actual) || !array_key_exists($segmento, $actual)) {
      return $default;
    }
    $actual = $actual[$segmento];
  }
  return $actual;
}
