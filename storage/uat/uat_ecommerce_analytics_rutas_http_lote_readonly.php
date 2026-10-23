<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-10-22.
 * Proposito: comprobar por HTTP un lote deduplicado de rutas descubiertas por Analytics.
 * Impacto: crea evidencia local para corregir frontend/SEO sin escribir la BD.
 * Contrato: recibe un inventario JSON, revisa GET publicos y conserva estado previo.
 */

function argumentoLoteAnalytics($argv, $nombre, $default) {
  foreach ((array) $argv as $arg) {
    $prefijo = "--" . $nombre . "=";
    if (strpos($arg, $prefijo) === 0) { return substr($arg, strlen($prefijo)); }
  }
  return $default;
}

function clasificarLoteAnalytics($status, $error) {
  if ($error !== "") { return "error_conexion"; }
  if ($status >= 200 && $status < 300) { return "ok"; }
  if ($status >= 300 && $status < 400) { return "redireccion"; }
  if ($status === 400) { return "error_400"; }
  if ($status === 404) { return "error_404"; }
  if ($status >= 500) { return "error_500"; }
  if ($status >= 400) { return "error_http"; }
  return "sin_respuesta";
}

function probeLoteAnalytics($url, $timeout) {
  $ch = curl_init($url);
  curl_setopt_array($ch, array(
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => false,
    CURLOPT_CONNECTTIMEOUT => $timeout,
    CURLOPT_TIMEOUT => $timeout,
    CURLOPT_USERAGENT => "ArtianiAnalyticsUrlBatch/1.0",
    CURLOPT_NOSIGNAL => true,
    CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
    CURLOPT_ENCODING => ""
  ));
  $body = curl_exec($ch);
  $error = curl_error($ch);
  $status = intval(curl_getinfo($ch, CURLINFO_RESPONSE_CODE));
  $resultado = clasificarLoteAnalytics($status, $error);
  $respuesta = array(
    "status_http" => $status,
    "resultado" => $resultado,
    "error" => $error,
    "location" => (string) curl_getinfo($ch, CURLINFO_REDIRECT_URL),
    "final_url" => (string) curl_getinfo($ch, CURLINFO_EFFECTIVE_URL),
    "content_type" => (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE),
    "body_bytes" => strlen((string) $body)
  );
  curl_close($ch);
  return $respuesta;
}

$inventarioPath = argumentoLoteAnalytics($argv, "inventario", "");
$base = rtrim((string) argumentoLoteAnalytics($argv, "base", "https://artiani.com.mx"), "/");
$limite = max(1, min(500, intval(argumentoLoteAnalytics($argv, "limite", 50))));
$timeout = max(3, min(30, intval(argumentoLoteAnalytics($argv, "timeout", 10))));
$delayMs = max(0, min(3000, intval(argumentoLoteAnalytics($argv, "delay_ms", 350))));
$soloBanderas = argumentoLoteAnalytics($argv, "solo_banderas", "1") === "1";
$soloPendientes = argumentoLoteAnalytics($argv, "solo_pendientes", "1") === "1";
$outDir = argumentoLoteAnalytics($argv, "salida", sys_get_temp_dir() . "/artiani_ecommerce_analytics");
if (!is_dir($outDir)) { @mkdir($outDir, 0777, true); }
$estadoPath = argumentoLoteAnalytics($argv, "estado", $outDir . "/ecommerce_analytics_rutas_revision_estado.json");
$reportesPrevios = array_filter(array_map("trim", explode(",", argumentoLoteAnalytics($argv, "reportes_previos", ""))));
$estadoPrevio = is_file($estadoPath) ? json_decode((string) file_get_contents($estadoPath), true) : array();
$reportesPrevios[] = $estadoPath;
$revisionPorClave = array();
foreach ($reportesPrevios as $reporte) {
  if (!is_file($reporte)) { continue; }
  $datosReporte = json_decode((string) file_get_contents($reporte), true);
  foreach ((array) ($datosReporte["items"] ?? array()) as $revisado) {
    $claveRevisada = trim((string) ($revisado["clave_normalizada"] ?? $revisado["ruta"] ?? ""));
    if ($claveRevisada === "") { continue; }
    $revisionPorClave[$claveRevisada] = $revisado;
  }
}

$inventario = is_file($inventarioPath) ? json_decode((string) file_get_contents($inventarioPath), true) : null;
$agrupadas = array();
foreach ((array) ($inventario["items"] ?? array()) as $item) {
  $banderas = (array) ($item["banderas"] ?? array());
  $revision = (array) ($item["revision_previa"] ?? array());
  $clave = trim((string) ($item["clave_normalizada"] ?? $item["ruta"] ?? ""));
  if (isset($revisionPorClave[$clave])) { $revision = $revisionPorClave[$clave]; }
  if ($soloBanderas && empty($banderas)) { continue; }
  if ($soloPendientes && !empty($revision)) { continue; }
  if ($clave === "") { continue; }
  if (!isset($agrupadas[$clave]) || intval($item["page_views"] ?? 0) > intval($agrupadas[$clave]["page_views"] ?? 0)) {
    $agrupadas[$clave] = $item;
  }
}

uasort($agrupadas, function ($a, $b) {
  return intval($b["page_views"] ?? 0) <=> intval($a["page_views"] ?? 0);
});
$pendientes = array_slice(array_values($agrupadas), 0, $limite);
$items = array();
$resumen = array("urls_consultadas" => 0, "ok" => 0, "error_400" => 0, "error_404" => 0, "error_500" => 0, "error_http" => 0, "redireccion" => 0, "error_conexion" => 0, "sin_respuesta" => 0);

foreach ($pendientes as $item) {
  $ruta = trim((string) ($item["ruta"] ?? ""));
  $url = $base . "/" . ltrim($ruta, "/");
  $probe = probeLoteAnalytics($url, $timeout);
  // Un estado 0 no se toma como error definitivo hasta repetirlo individualmente.
  if ($probe["resultado"] === "sin_respuesta") {
    usleep(250000);
    $probe = probeLoteAnalytics($url, $timeout);
  }
  $resultado = $probe["resultado"];
  if (!isset($resumen[$resultado])) { $resumen[$resultado] = 0; }
  $resumen[$resultado]++;
  $resumen["urls_consultadas"]++;
  $items[] = array_merge($item, array(
    "url" => $url,
    "status_http" => $probe["status_http"],
    "resultado" => $resultado,
    "error" => $probe["error"],
    "location" => $probe["location"],
    "final_url" => $probe["final_url"],
    "content_type" => $probe["content_type"],
    "body_bytes" => $probe["body_bytes"],
    "revisado_en" => date("Y-m-d H:i:s")
  ));
  if ($delayMs > 0) { usleep($delayMs * 1000); }
}

$timestamp = date("Ymd_His");
$jsonPath = $outDir . "/ecommerce_analytics_rutas_http_lote_" . $timestamp . ".json";
$csvPath = $outDir . "/ecommerce_analytics_rutas_http_lote_" . $timestamp . ".csv";
$payload = array(
  "ok" => is_array($inventario),
  "modo" => "read-only",
  "base" => $base,
  "inventario" => $inventarioPath,
  "generado_en" => date("Y-m-d H:i:s"),
  "filtros" => array("solo_banderas" => $soloBanderas, "solo_pendientes" => $soloPendientes, "limite" => $limite),
  "resumen" => $resumen,
  "items" => $items,
  "guardrails" => array("no_escribe_bd" => true, "no_crea_redirecciones" => true, "no_modifica_sitemap" => true)
);
file_put_contents($jsonPath, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
$estadoItems = array();
foreach ($revisionPorClave as $clave => $revisado) { $estadoItems[$clave] = $revisado; }
foreach ($items as $revisado) {
  if (in_array($revisado["resultado"], array("ok", "redireccion", "error_400", "error_404", "error_500", "error_http"), true)) {
    $estadoItems[$revisado["clave_normalizada"]] = $revisado;
  }
}
file_put_contents($estadoPath, json_encode(array(
  "modo" => "read-only",
  "actualizado_en" => date("Y-m-d H:i:s"),
  "items" => array_values($estadoItems),
  "guardrails" => $payload["guardrails"]
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
$csv = fopen($csvPath, "w");
fputcsv($csv, array("ruta", "clave_normalizada", "page_views", "sesiones", "banderas", "resultado", "status_http", "url", "location", "error"));
foreach ($items as $item) {
  fputcsv($csv, array($item["ruta"], $item["clave_normalizada"], $item["page_views"], $item["sesiones"], implode("|", (array) $item["banderas"]), $item["resultado"], $item["status_http"], $item["url"], $item["location"], $item["error"]));
}
fclose($csv);

echo json_encode(array("ok" => $payload["ok"], "resumen" => $resumen, "archivos" => array("json" => $jsonPath, "csv" => $csvPath, "estado" => $estadoPath), "guardrails" => $payload["guardrails"]), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
