<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-10-22.
 * Proposito: comprobar por HTTP las URLs publicas registradas por Analytics.
 * Impacto: genera evidencia de URLs con 400, 404, 500, errores de conexion o redirecciones.
 * Contrato: solo SELECT sobre Analytics y requests GET read-only al frontend; no modifica BD.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";

class EcommerceAnalyticsUrlCheckReadonlyProbe extends CRUD {
  public function db() {
    return $this->getConexion();
  }
}

function argumentoUrlCheck($argv, $nombre, $default) {
  foreach ((array) $argv as $arg) {
    $prefijo = "--" . $nombre . "=";
    if (strpos($arg, $prefijo) === 0) { return substr($arg, strlen($prefijo)); }
  }
  return $default;
}

function clasificarUrlCheck($status, $error) {
  if ($error !== "") { return "error_conexion"; }
  if ($status >= 200 && $status < 300) { return "ok"; }
  if ($status >= 300 && $status < 400) { return "redireccion"; }
  if ($status === 400) { return "error_400"; }
  if ($status === 404) { return "error_404"; }
  if ($status >= 500) { return "error_500"; }
  if ($status >= 400) { return "error_http"; }
  return "sin_respuesta";
}

function httpProbeUrlCheck($url, $timeout) {
  $ch = curl_init($url);
  $bodyBytes = 0;
  curl_setopt_array($ch, array(
    CURLOPT_RETURNTRANSFER => false,
    CURLOPT_FOLLOWLOCATION => false,
    CURLOPT_CONNECTTIMEOUT => $timeout,
    CURLOPT_TIMEOUT => $timeout,
    CURLOPT_USERAGENT => "ArtianiAnalyticsUrlCheck/1.0",
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
    CURLOPT_ENCODING => "",
    CURLOPT_WRITEFUNCTION => function ($ch, $chunk) use (&$bodyBytes) {
      $bodyBytes += strlen($chunk);
      return strlen($chunk);
    }
  ));
  curl_exec($ch);
  $error = curl_error($ch);
  $status = intval(curl_getinfo($ch, CURLINFO_RESPONSE_CODE));
  $location = (string) curl_getinfo($ch, CURLINFO_REDIRECT_URL);
  $contentType = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
  $finalUrl = (string) curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
  curl_close($ch);
  return array(
    "status_http" => $status,
    "resultado" => clasificarUrlCheck($status, $error),
    "error" => $error,
    "location" => $location,
    "final_url" => $finalUrl,
    "content_type" => $contentType,
    "body_bytes" => $bodyBytes
  );
}

function httpProbeUrlsCheckBatch($rows, $base, $timeout) {
  $multi = curl_multi_init();
  $handles = array();
  $items = array();
  foreach ($rows as $index => $row) {
    $ruta = trim((string) $row["ruta"]);
    $url = $base . "/" . ltrim($ruta, "/");
    $ch = curl_init($url);
    curl_setopt_array($ch, array(
      CURLOPT_RETURNTRANSFER => false,
      CURLOPT_FOLLOWLOCATION => false,
      CURLOPT_CONNECTTIMEOUT => $timeout,
      CURLOPT_TIMEOUT => $timeout,
      CURLOPT_USERAGENT => "ArtianiAnalyticsUrlCheck/1.0",
      CURLOPT_SSL_VERIFYPEER => true,
      CURLOPT_SSL_VERIFYHOST => 2,
      CURLOPT_ENCODING => "",
      CURLOPT_PRIVATE => (string) $index,
      CURLOPT_WRITEFUNCTION => function ($ch, $chunk) { return strlen($chunk); }
    ));
    curl_multi_add_handle($multi, $ch);
    $handles[(int) $ch] = $ch;
  }
  $running = null;
  do {
    $status = curl_multi_exec($multi, $running);
    if ($running && $status === CURLM_OK) { curl_multi_select($multi, 1.0); }
  } while ($running && $status === CURLM_OK);
  foreach ($handles as $ch) {
    $index = intval(curl_getinfo($ch, CURLINFO_PRIVATE));
    $probe = array(
      "status_http" => intval(curl_getinfo($ch, CURLINFO_RESPONSE_CODE)),
      "resultado" => "",
      "error" => curl_error($ch),
      "location" => (string) curl_getinfo($ch, CURLINFO_REDIRECT_URL),
      "final_url" => (string) curl_getinfo($ch, CURLINFO_EFFECTIVE_URL),
      "content_type" => (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE),
      "body_bytes" => 0
    );
    $probe["resultado"] = clasificarUrlCheck($probe["status_http"], $probe["error"]);
    $row = $rows[$index];
    $items[$index] = array_merge(array(
      "ruta" => trim((string) $row["ruta"]),
      "url" => $base . "/" . ltrim((string) $row["ruta"], "/"),
      "page_views" => intval($row["page_views"]),
      "sesiones" => intval($row["sesiones"]),
      "ultima_fecha" => $row["ultima_fecha"]
    ), $probe);
    curl_multi_remove_handle($multi, $ch);
    curl_close($ch);
  }
  curl_multi_close($multi);
  ksort($items);
  return array_values($items);
}

$base = rtrim((string) argumentoUrlCheck($argv, "base", "https://artiani.com.mx"), "/");
$dias = max(1, min(90, intval(argumentoUrlCheck($argv, "dias", 30))));
$limite = max(1, min(2000, intval(argumentoUrlCheck($argv, "limite", 500))));
$timeout = max(3, min(30, intval(argumentoUrlCheck($argv, "timeout", 10))));
$delayMs = max(0, min(1000, intval(argumentoUrlCheck($argv, "delay_ms", 40))));
$outDir = argumentoUrlCheck($argv, "salida", sys_get_temp_dir() . "/artiani_ecommerce_analytics");
$db = (new EcommerceAnalyticsUrlCheckReadonlyProbe())->db();
$items = array();
$resumen = array(
  "urls_consultadas" => 0,
  "ok" => 0,
  "error_400" => 0,
  "error_404" => 0,
  "error_500" => 0,
  "error_http" => 0,
  "redireccion" => 0,
  "error_conexion" => 0
);

if ($db) {
  $stmt = $db->prepare("SELECT ruta, COUNT(*) page_views, COUNT(DISTINCT session_id_hash) sesiones, MAX(fecha_registro) ultima_fecha
    FROM erp_ecommerce_analytics_eventos
    WHERE tipo_evento='page_view'
      AND fecha_registro BETWEEN :inicio AND :fin
      AND TRIM(COALESCE(ruta,''))<>''
    GROUP BY ruta
    ORDER BY ultima_fecha DESC, page_views DESC
    LIMIT " . intval($limite));
  $stmt->execute(array(
    ":inicio" => date("Y-m-d H:i:s", strtotime("-" . $dias . " days")),
    ":fin" => date("Y-m-d H:i:s")
  ));
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  foreach (array_chunk($rows, 25) as $batch) {
    foreach (httpProbeUrlsCheckBatch($batch, $base, $timeout) as $item) {
      $resultado = $item["resultado"];
      if (!isset($resumen[$resultado])) { $resumen[$resultado] = 0; }
      $resumen[$resultado]++;
      $resumen["urls_consultadas"]++;
      $items[] = $item;
    }
    if ($delayMs > 0) { usleep($delayMs * 1000); }
  }
  /*
  while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $ruta = trim((string) $row["ruta"]);
    $url = $base . "/" . ltrim($ruta, "/");
    $probe = httpProbeUrlCheck($url, $timeout);
    $resultado = $probe["resultado"];
    if (!isset($resumen[$resultado])) { $resumen[$resultado] = 0; }
    $resumen[$resultado]++;
    $resumen["urls_consultadas"]++;
    $items[] = array_merge(array(
      "ruta" => $ruta,
      "url" => $url,
      "page_views" => intval($row["page_views"]),
      "sesiones" => intval($row["sesiones"]),
      "ultima_fecha" => $row["ultima_fecha"]
    ), $probe);
    if ($delayMs > 0) { usleep($delayMs * 1000); }
  }
  */
}

$errores = array_values(array_filter($items, function ($item) {
  return $item["resultado"] !== "ok";
}));
$timestamp = date("Ymd_His");
if (!is_dir($outDir)) { @mkdir($outDir, 0777, true); }
$jsonPath = $outDir . "/ecommerce_analytics_urls_http_" . $timestamp . ".json";
$csvPath = $outDir . "/ecommerce_analytics_urls_http_" . $timestamp . ".csv";
file_put_contents($jsonPath, json_encode(array(
  "ok" => $db ? true : false,
  "modo" => "read-only",
  "base" => $base,
  "rango_dias" => $dias,
  "resumen" => $resumen,
  "errores" => $errores,
  "items" => $items,
  "guardrails" => array("no_escribe_bd" => true, "no_crea_redirecciones" => true, "no_modifica_urls" => true)
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
$csv = fopen($csvPath, "w");
fputcsv($csv, array("ruta", "url", "resultado", "status_http", "page_views", "sesiones", "ultima_fecha", "location", "error"));
foreach ($items as $item) {
  fputcsv($csv, array($item["ruta"], $item["url"], $item["resultado"], $item["status_http"], $item["page_views"], $item["sesiones"], $item["ultima_fecha"], $item["location"], $item["error"]));
}
fclose($csv);

echo json_encode(array(
  "ok" => $db ? true : false,
  "modo" => "read-only",
  "base" => $base,
  "rango_dias" => $dias,
  "resumen" => $resumen,
  "archivos" => array("json" => $jsonPath, "csv" => $csvPath),
  "errores_muestra" => array_slice($errores, 0, 30),
  "guardrails" => array("no_escribe_bd" => true, "no_crea_redirecciones" => true, "no_modifica_urls" => true)
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
