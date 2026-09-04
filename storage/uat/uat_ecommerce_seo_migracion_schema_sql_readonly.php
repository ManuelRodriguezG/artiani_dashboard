<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-09-03.
 * Proposito: exportar SQL DDL SEO/migracion ecommerce sin ejecutarlo.
 * Impacto: permite revisar tablas SEO, redirecciones, URLs viejas y 404 con huella verificable.
 * Contrato: read-only; no ejecuta DDL, no escribe BD y no crea redirecciones.
 */

$args = argumentos($argv);
$respaldo = isset($args["respaldo"]) ? $args["respaldo"] : "";

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/core/DBSchema.php";
require_once "../app/modelos/EcommercePublicoEsquema.php";

$esquema = new EcommercePublicoEsquema();
$plan = $esquema->planActualizarSeoMigracion(false);
$items = valorSeoSqlReadonly($plan, array("depurar", "plan"), array());
$sql = array();

foreach ($items as $item) {
  $sentencia = trim((string) valorSeoSqlReadonly($item, array("depurar", "sql"), ""));
  if ($sentencia !== "") {
    $sql[] = rtrim($sentencia, ";") . ";";
  }
}

$paqueteSql = implode("\n\n", $sql);

echo json_encode(array(
  "ok" => !empty($sql) && empty($plan["error"]),
  "modo" => "read-only",
  "respaldo_referencia" => $respaldo,
  "ddl_total" => count($sql),
  "sha256_sql" => hash("sha256", $paqueteSql),
  "guardrails" => array(
    "no_ejecuta_ddl" => true,
    "no_escribe_bd" => true,
    "no_importa_urls" => true,
    "no_crea_redirecciones" => true,
    "requiere_apply_separado_con_token" => "ECOMMERCE_SEO_MIGRACION_DDL"
  ),
  "sql" => $sql,
  "siguiente_paso" => "Revisar SQL y sha256; aplicar solo con uat_ecommerce_seo_migracion_schema_apply_authorized.php y respaldo autorizado."
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

function valorSeoSqlReadonly($datos, $ruta, $default = null) {
  $actual = $datos;
  foreach ($ruta as $segmento) {
    if (!is_array($actual) || !array_key_exists($segmento, $actual)) {
      return $default;
    }
    $actual = $actual[$segmento];
  }
  return $actual;
}
