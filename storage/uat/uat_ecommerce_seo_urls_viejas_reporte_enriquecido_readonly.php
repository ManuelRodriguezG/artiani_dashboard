<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-09-05.
 * Proposito: unir crawl y plan SEO para priorizar revision de URLs viejas.
 * Impacto: prepara decisiones de importacion/redireccion sin tocar base de datos.
 * Contrato: read-only; no importa URLs, no crea redirecciones y no escribe fuera de storage/tmp.
 */

$args = argumentosSeoReporte($argv);
$archivoPlan = rutaSeoReporte($args, "plan");
$archivoCrawl = rutaSeoReporte($args, "crawl");
if ($archivoPlan === "" || $archivoCrawl === "") {
  salidaSeoReporte(false, "Archivos no legibles", array(
    "plan" => valorSeoReporte($args, "plan", ""),
    "crawl" => valorSeoReporte($args, "crawl", ""),
    "uso" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_seo_urls_viejas_reporte_enriquecido_readonly.php --plan=storage\\tmp\\plan.json --crawl=storage\\tmp\\crawl.json"
  ));
}

$plan = json_decode(file_get_contents($archivoPlan), true);
$crawl = json_decode(file_get_contents($archivoCrawl), true);
$itemsPlan = valorRutaSeoReporte($plan, array("items"), array());
$itemsCrawl = valorRutaSeoReporte($crawl, array("items"), array());
$crawlPorPath = array();
foreach ($itemsCrawl as $item) {
  $path = normalizarPathSeoReporte(valorSeoReporte($item, "path", valorSeoReporte($item, "url", "")));
  if ($path !== "") {
    $crawlPorPath[$path] = $item;
  }
}

$items = array();
$resumen = array(
  "accion" => array(),
  "prioridad" => array(),
  "status" => array(),
  "tipo" => array(),
  "confianza" => array()
);
foreach ($itemsPlan as $item) {
  $path = normalizarPathSeoReporte(valorSeoReporte($item, "path_original", valorSeoReporte($item, "url_original", "")));
  $crawlItem = isset($crawlPorPath[$path]) ? $crawlPorPath[$path] : array();
  $status = intval(valorSeoReporte($crawlItem, "status", 0));
  $tipoCrawl = valorSeoReporte($crawlItem, "tipo_detectado", "");
  $tipoPlan = valorSeoReporte($item, "tipo_detectado", "desconocido");
  $confianza = valorSeoReporte($item, "confianza", "baja");
  $destino = trim((string) valorSeoReporte($item, "url_destino_sugerida", ""));
  $clasificacion = clasificarSeoReporte($path, $tipoPlan, $tipoCrawl, $status, $confianza, $destino);
  $fila = array(
    "url_original" => valorSeoReporte($item, "url_original", ""),
    "path_original" => $path,
    "status_http" => $status,
    "titulo_detectado" => valorSeoReporte($crawlItem, "titulo", ""),
    "tipo_crawl" => $tipoCrawl,
    "tipo_plan" => $tipoPlan,
    "url_destino_sugerida" => $destino,
    "confianza" => $confianza,
    "motivo" => valorSeoReporte($item, "motivo", ""),
    "prioridad_revision" => $clasificacion["prioridad"],
    "accion_sugerida" => $clasificacion["accion"],
    "nota" => $clasificacion["nota"]
  );
  $items[] = $fila;
  contarSeoReporte($resumen["accion"], $fila["accion_sugerida"]);
  contarSeoReporte($resumen["prioridad"], $fila["prioridad_revision"]);
  contarSeoReporte($resumen["status"], (string) $fila["status_http"]);
  contarSeoReporte($resumen["tipo"], $fila["tipo_plan"]);
  contarSeoReporte($resumen["confianza"], $fila["confianza"]);
}

foreach (array_keys($resumen) as $key) {
  ksort($resumen[$key]);
}

usort($items, function ($a, $b) {
  $ordenPrioridad = array("alta" => 0, "media" => 1, "baja" => 2);
  $ordenAccion = array("aprobar_301_candidato" => 0, "validar_301_candidato" => 1, "revisar_manual" => 2, "excluir_o_410" => 3);
  $pa = isset($ordenPrioridad[$a["prioridad_revision"]]) ? $ordenPrioridad[$a["prioridad_revision"]] : 9;
  $pb = isset($ordenPrioridad[$b["prioridad_revision"]]) ? $ordenPrioridad[$b["prioridad_revision"]] : 9;
  if ($pa !== $pb) { return $pa - $pb; }
  $aa = isset($ordenAccion[$a["accion_sugerida"]]) ? $ordenAccion[$a["accion_sugerida"]] : 9;
  $ab = isset($ordenAccion[$b["accion_sugerida"]]) ? $ordenAccion[$b["accion_sugerida"]] : 9;
  if ($aa !== $ab) { return $aa - $ab; }
  return strcmp($a["path_original"], $b["path_original"]);
});

$outDir = realpath(__DIR__ . "/../tmp");
$timestamp = date("Ymd_His");
$jsonPath = $outDir . DIRECTORY_SEPARATOR . "ecommerce_seo_urls_viejas_reporte_enriquecido_" . $timestamp . ".json";
$csvPath = $outDir . DIRECTORY_SEPARATOR . "ecommerce_seo_urls_viejas_reporte_enriquecido_" . $timestamp . ".csv";

file_put_contents($jsonPath, json_encode(array(
  "ok" => true,
  "modo" => "read-only",
  "archivo_plan" => $archivoPlan,
  "archivo_crawl" => $archivoCrawl,
  "total" => count($items),
  "resumen" => $resumen,
  "items" => $items,
  "guardrails" => array("no_escribe_bd" => true, "no_importa_urls" => true, "no_crea_redirecciones" => true)
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

$csv = fopen($csvPath, "w");
fputcsv($csv, array("url_original", "path_original", "status_http", "titulo_detectado", "tipo_crawl", "tipo_plan", "url_destino_sugerida", "confianza", "motivo", "prioridad_revision", "accion_sugerida", "nota"));
foreach ($items as $item) {
  fputcsv($csv, array(
    $item["url_original"],
    $item["path_original"],
    $item["status_http"],
    $item["titulo_detectado"],
    $item["tipo_crawl"],
    $item["tipo_plan"],
    $item["url_destino_sugerida"],
    $item["confianza"],
    $item["motivo"],
    $item["prioridad_revision"],
    $item["accion_sugerida"],
    $item["nota"]
  ));
}
fclose($csv);

salidaSeoReporte(true, "Reporte enriquecido de URLs viejas generado", array(
  "total" => count($items),
  "resumen" => $resumen,
  "archivos" => array("json" => $jsonPath, "csv" => $csvPath),
  "muestra" => array_slice($items, 0, 15),
  "guardrails" => array("no_escribe_bd" => true, "no_importa_urls" => true, "no_crea_redirecciones" => true)
));

function clasificarSeoReporte($path, $tipoPlan, $tipoCrawl, $status, $confianza, $destino) {
  $pathLower = strtolower((string) $path);
  $basuraPlantilla = preg_match('/(index-skin|empty-cart|listing-|collections\/|\/product\.html|\/carrito\/product\.html)/', $pathLower);
  if ($status >= 400 || $basuraPlantilla) {
    return array("prioridad" => "baja", "accion" => "excluir_o_410", "nota" => $status >= 400 ? "origen_error_http" : "ruta_plantilla_legacy");
  }
  if ($destino !== "" && in_array($confianza, array("exacta", "alta"), true)) {
    return array("prioridad" => in_array($tipoPlan, array("producto", "categoria", "marca"), true) ? "alta" : "media", "accion" => "aprobar_301_candidato", "nota" => "requiere_revision_destino");
  }
  if ($destino !== "" && $confianza === "media") {
    return array("prioridad" => in_array($tipoPlan, array("producto", "categoria", "marca"), true) ? "alta" : "media", "accion" => "validar_301_candidato", "nota" => "validar_variante_marca_y_tamano");
  }
  if (in_array($tipoPlan, array("producto", "categoria", "marca"), true) || in_array($tipoCrawl, array("producto_legacy", "categoria_legacy", "clasificacion"), true)) {
    return array("prioridad" => "media", "accion" => "revisar_manual", "nota" => "url_indexable_sin_equivalencia_confiable");
  }
  return array("prioridad" => "baja", "accion" => "revisar_manual", "nota" => "sin_senal_seo_clara");
}

function rutaSeoReporte($args, $key) {
  $valor = valorSeoReporte($args, $key, "");
  if ($valor === "" || !is_file($valor) || !is_readable($valor)) {
    return "";
  }
  return realpath($valor);
}

function argumentosSeoReporte($argv) {
  $salida = array();
  foreach ((array) $argv as $arg) {
    if (strpos($arg, "--") !== 0 || strpos($arg, "=") === false) { continue; }
    $partes = explode("=", substr($arg, 2), 2);
    $salida[$partes[0]] = trim($partes[1], "\"' ");
  }
  return $salida;
}

function valorSeoReporte($datos, $key, $default = null) {
  return is_array($datos) && array_key_exists($key, $datos) ? $datos[$key] : $default;
}

function valorRutaSeoReporte($datos, $ruta, $default = null) {
  $actual = $datos;
  foreach ($ruta as $segmento) {
    if (!is_array($actual) || !array_key_exists($segmento, $actual)) {
      return $default;
    }
    $actual = $actual[$segmento];
  }
  return $actual;
}

function contarSeoReporte(&$items, $key) {
  $key = (string) $key;
  if (!isset($items[$key])) { $items[$key] = 0; }
  $items[$key]++;
}

function normalizarPathSeoReporte($path) {
  $path = trim((string) $path);
  if ($path === "") { return ""; }
  if (preg_match('/^https?:\/\//i', $path)) {
    $partes = parse_url($path);
    $path = isset($partes["path"]) ? $partes["path"] : "/";
    if (!empty($partes["query"])) { $path .= "?" . $partes["query"]; }
  }
  $path = "/" . ltrim($path, "/");
  return preg_replace('/\/+/', '/', $path);
}

function salidaSeoReporte($ok, $mensaje, $depurar) {
  echo json_encode(array(
    "ok" => $ok,
    "mensaje" => $mensaje,
    "depurar" => $depurar
  ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
  exit($ok ? 0 : 1);
}
