<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-09-05.
 * Proposito: generar plan de equivalencias SEO desde un archivo de URLs viejas.
 * Impacto: prepara revision humana antes de importar URLs y aprobar redirecciones.
 * Contrato: read-only; no escribe BD, no importa URLs y no crea redirecciones.
 */

$args = argumentosSeoPlanArchivo($argv);
$archivo = valorSeoPlanArchivo($args, "archivo", "");
if ($archivo === "" || !is_file($archivo) || !is_readable($archivo)) {
  salidaSeoPlanArchivo(false, "Archivo de URLs no legible", array(
    "archivo" => $archivo,
    "uso" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_seo_urls_viejas_plan_desde_archivo_readonly.php --archivo=storage\\tmp\\ecommerce_seo_urls_viejas_import_YYYYMMDD_HHMMSS.txt"
  ));
}
$archivo = realpath($archivo);

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/EcommerceCatalogoPublico.php";

$catalogo = new EcommerceCatalogoPublico();
$plan = $catalogo->seoUrlsViejasImportarPlanInterno(array("urls_texto" => file_get_contents($archivo)));
$items = valorRutaSeoPlanArchivo($plan, array("depurar", "items"), array());
$outDir = realpath(__DIR__ . "/../tmp");
$timestamp = date("Ymd_His");
$jsonPath = $outDir . DIRECTORY_SEPARATOR . "ecommerce_seo_urls_viejas_plan_" . $timestamp . ".json";
$csvPath = $outDir . DIRECTORY_SEPARATOR . "ecommerce_seo_urls_viejas_plan_" . $timestamp . ".csv";

$resumen = array(
  "estatus" => array(),
  "tipo" => array(),
  "confianza" => array(),
  "con_sugerencia" => 0,
  "sin_sugerencia" => 0
);
foreach ($items as $item) {
  contarSeoPlanArchivo($resumen["estatus"], valorRutaSeoPlanArchivo($item, array("estatus_mapeo"), "sin_equivalente"));
  contarSeoPlanArchivo($resumen["tipo"], valorRutaSeoPlanArchivo($item, array("tipo_detectado"), "desconocido"));
  contarSeoPlanArchivo($resumen["confianza"], valorRutaSeoPlanArchivo($item, array("confianza"), "baja"));
  if (trim((string) valorRutaSeoPlanArchivo($item, array("url_destino_sugerida"), "")) !== "") {
    $resumen["con_sugerencia"]++;
  } else {
    $resumen["sin_sugerencia"]++;
  }
}
ksort($resumen["estatus"]);
ksort($resumen["tipo"]);
ksort($resumen["confianza"]);

file_put_contents($jsonPath, json_encode(array(
  "ok" => empty($plan["error"]),
  "modo" => "read-only",
  "archivo_origen" => $archivo,
  "total" => count($items),
  "resumen" => $resumen,
  "items" => $items,
  "guardrails" => array(
    "no_escribe_bd" => true,
    "no_importa_urls" => true,
    "no_crea_redirecciones" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

$csv = fopen($csvPath, "w");
fputcsv($csv, array("url_original", "path_original", "tipo_detectado", "estatus_mapeo", "url_destino_sugerida", "confianza", "motivo"));
foreach ($items as $item) {
  fputcsv($csv, array(
    valorRutaSeoPlanArchivo($item, array("url_original"), ""),
    valorRutaSeoPlanArchivo($item, array("path_original"), ""),
    valorRutaSeoPlanArchivo($item, array("tipo_detectado"), ""),
    valorRutaSeoPlanArchivo($item, array("estatus_mapeo"), ""),
    valorRutaSeoPlanArchivo($item, array("url_destino_sugerida"), ""),
    valorRutaSeoPlanArchivo($item, array("confianza"), ""),
    valorRutaSeoPlanArchivo($item, array("motivo"), "")
  ));
}
fclose($csv);

salidaSeoPlanArchivo(empty($plan["error"]), "Plan de URLs viejas generado", array(
  "archivo_origen" => $archivo,
  "total" => count($items),
  "resumen" => $resumen,
  "archivos" => array("json" => $jsonPath, "csv" => $csvPath),
  "muestra" => array_slice($items, 0, 12),
  "guardrails" => array(
    "no_escribe_bd" => true,
    "no_importa_urls" => true,
    "no_crea_redirecciones" => true
  )
));

function argumentosSeoPlanArchivo($argv) {
  $salida = array();
  foreach ((array) $argv as $arg) {
    if (strpos($arg, "--") !== 0 || strpos($arg, "=") === false) { continue; }
    $partes = explode("=", substr($arg, 2), 2);
    $salida[$partes[0]] = trim($partes[1], "\"' ");
  }
  return $salida;
}

function valorSeoPlanArchivo($datos, $key, $default = null) {
  return is_array($datos) && array_key_exists($key, $datos) ? $datos[$key] : $default;
}

function valorRutaSeoPlanArchivo($datos, $ruta, $default = null) {
  $actual = $datos;
  foreach ($ruta as $segmento) {
    if (!is_array($actual) || !array_key_exists($segmento, $actual)) {
      return $default;
    }
    $actual = $actual[$segmento];
  }
  return $actual;
}

function contarSeoPlanArchivo(&$items, $key) {
  $key = (string) $key;
  if (!isset($items[$key])) { $items[$key] = 0; }
  $items[$key]++;
}

function salidaSeoPlanArchivo($ok, $mensaje, $depurar) {
  echo json_encode(array(
    "ok" => $ok,
    "mensaje" => $mensaje,
    "depurar" => $depurar
  ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
  exit($ok ? 0 : 1);
}
