<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-12.
 * Proposito: exportar en pantalla el SQL DDL propuesto para ecommerce publico sin ejecutarlo.
 * Impacto: permite revisar/autorizacion previa del esquema `erp_ecommerce_*` con huella verificable.
 * Contrato: read-only; no ejecuta DDL, no crea tablas, no escribe BD y no toca `ecom_*`.
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

$esquema = new EcommercePublicoEsquema();
$plan = $esquema->planActualizarEcommercePublico(false);
$items = valorEcommerceSqlReadonly($plan, array("depurar", "plan"), array());
$sql = array();

foreach ($items as $item) {
  $sentencia = trim((string) valorEcommerceSqlReadonly($item, array("depurar", "sql"), ""));
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
    "no_toca_ecom_legacy" => true,
    "no_mueve_inventario" => true,
    "requiere_apply_separado_con_token" => "ECOMMERCE_PUBLICO_DDL_FASE1"
  ),
  "sql" => $sql,
  "siguiente_paso" => "Revisar SQL y sha256; aplicar solo con uat_ecommerce_publico_schema_apply_authorized.php y respaldo autorizado."
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

function valorEcommerceSqlReadonly($datos, $ruta, $default = null) {
  $actual = $datos;
  foreach ($ruta as $segmento) {
    if (!is_array($actual) || !array_key_exists($segmento, $actual)) {
      return $default;
    }
    $actual = $actual[$segmento];
  }
  return $actual;
}
