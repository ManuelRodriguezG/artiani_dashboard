<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-10-22.
 * Proposito: crear un inventario deduplicado de rutas page_view de Analytics.
 * Impacto: facilita revisar URLs publicas, detectar patrones invalidos y reutilizar
 * resultados HTTP anteriores sin escribir en la base productiva.
 * Contrato: solo SELECT sobre Analytics; los reportes se guardan en storage/tmp.
 */

function argumentoRutasUnicas($argv, $nombre, $default) {
  foreach ((array) $argv as $arg) {
    $prefijo = "--" . $nombre . "=";
    if (strpos($arg, $prefijo) === 0) { return substr($arg, strlen($prefijo)); }
  }
  return $default;
}

$servidor = argumentoRutasUnicas($argv, "servidor", "panel.com.local");
$_SERVER["SERVER_NAME"] = $servidor;
chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";

class EcommerceAnalyticsRutasUnicasReadonly extends CRUD {
  public function db() { return $this->getConexion(); }
}

function rutaBaseUnica($ruta) {
  $ruta = trim((string) $ruta);
  if ($ruta === "") { return ""; }
  $ruta = preg_replace('/#.*$/', '', $ruta);
  if (preg_match('/^https?:\/\//i', $ruta)) {
    $parsed = parse_url($ruta);
    if (is_array($parsed) && isset($parsed["path"])) {
      $ruta = $parsed["path"] . (isset($parsed["query"]) && $parsed["query"] !== "" ? "?" . $parsed["query"] : "");
    }
  }
  $ruta = preg_replace('#/{2,}#', '/', $ruta);
  return substr($ruta, 0, 500);
}

function rutaClaveUnica($ruta) {
  $ruta = rutaBaseUnica($ruta);
  if ($ruta === "") { return ""; }
  $partes = parse_url($ruta);
  $path = strtolower((string) ($partes["path"] ?? $ruta));
  $path = rtrim($path, "/");
  if ($path === "") { $path = "/"; }
  $query = array();
  if (!empty($partes["query"])) {
    foreach (explode("&", (string) $partes["query"]) as $par) {
      if ($par === "") { continue; }
      $kv = explode("=", $par, 2);
      $clave = strtolower(trim((string) $kv[0]));
      if (in_array($clave, array("utm_source", "utm_medium", "utm_campaign", "utm_term", "utm_content", "gclid", "fbclid", "gbraid", "wbraid", "msclkid", "ttclid", "srsltid"), true)) { continue; }
      $query[] = $clave . (isset($kv[1]) ? "=" . $kv[1] : "");
    }
    sort($query, SORT_STRING);
  }
  return substr($path . (empty($query) ? "" : "?" . implode("&", $query)), 0, 500);
}

function banderasRutaUnica($ruta) {
  $banderas = array();
  $valor = strtolower((string) $ruta);
  if (preg_match('/https?:\/\//i', $valor) && !preg_match('/^https?:\/\//i', $valor)) { $banderas[] = "url_concatenada"; }
  if (strpos($valor, "undefined") !== false || strpos($valor, "null") !== false) { $banderas[] = "parametro_vacio"; }
  if (preg_match('/\/(?:index|product)\.html(?:\?|$)/i', $valor)) { $banderas[] = "sufijo_html_legacy"; }
  if (preg_match('/^\/(?:media|assets|storage)\//i', $valor)) { $banderas[] = "recurso_no_pagina"; }
  if (preg_match('/^\/producto\/clasificacion\//i', $valor)) { $banderas[] = "estructura_producto_sospechosa"; }
  if (preg_match('/^\/paquete\/.*\/images\/product\//i', $valor)) { $banderas[] = "ruta_paquete_contaminada"; }
  if ($valor !== "" && $valor[0] !== "/") { $banderas[] = "sin_barra_inicial"; }
  return $banderas;
}

function cargarReportePrevioRutasUnicas($ruta) {
  if (!$ruta || !is_file($ruta)) { return array(); }
  $datos = json_decode((string) file_get_contents($ruta), true);
  $mapa = array();
  foreach ((array) ($datos["items"] ?? array()) as $item) {
    $clave = rutaClaveUnica($item["ruta"] ?? "");
    if ($clave === "") { continue; }
    $revision = is_array($item["revision_previa"] ?? null) ? $item["revision_previa"] : array();
    $mapa[$clave] = array(
      "resultado" => $item["resultado"] ?? ($revision["resultado"] ?? "revisado"),
      "status_http" => intval($item["status_http"] ?? ($revision["status_http"] ?? 0)),
      "revisado_en" => $datos["generado_en"] ?? ($datos["fecha"] ?? "")
    );
  }
  return $mapa;
}

$dias = max(1, min(3650, intval(argumentoRutasUnicas($argv, "dias", 3650))));
$limite = max(0, min(250000, intval(argumentoRutasUnicas($argv, "limite", 0))));
$previo = argumentoRutasUnicas($argv, "reporte_previo", "");
$soloPendientes = argumentoRutasUnicas($argv, "solo_pendientes", "0") === "1";
$outDir = argumentoRutasUnicas($argv, "salida", sys_get_temp_dir() . "/artiani_ecommerce_analytics");
$db = (new EcommerceAnalyticsRutasUnicasReadonly())->db();
$revisadas = cargarReportePrevioRutasUnicas($previo);
$items = array();

if ($db) {
  $sql = "SELECT ruta, COUNT(*) page_views, COUNT(DISTINCT session_id_hash) sesiones,
      MIN(fecha_registro) primera_fecha, MAX(fecha_registro) ultima_fecha
    FROM erp_ecommerce_analytics_eventos
    WHERE tipo_evento='page_view'
      AND fecha_registro >= DATE_SUB(NOW(), INTERVAL " . intval($dias) . " DAY)
      AND TRIM(COALESCE(ruta,''))<>''
    GROUP BY ruta
    ORDER BY page_views DESC, ultima_fecha DESC";
  if ($limite > 0) { $sql .= " LIMIT " . intval($limite); }
  $stmt = $db->query($sql);
  while ($fila = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $ruta = trim((string) $fila["ruta"]);
    $clave = rutaClaveUnica($ruta);
    $revision = $revisadas[$clave] ?? array();
    if ($soloPendientes && !empty($revision)) { continue; }
    $items[] = array(
      "ruta" => $ruta,
      "clave_normalizada" => $clave,
      "page_views" => intval($fila["page_views"]),
      "sesiones" => intval($fila["sesiones"]),
      "primera_fecha" => $fila["primera_fecha"],
      "ultima_fecha" => $fila["ultima_fecha"],
      "banderas" => banderasRutaUnica($ruta),
      "revision_previa" => $revision,
      "resultado_previo" => $revision["resultado"] ?? "",
      "status_http_previo" => intval($revision["status_http"] ?? 0)
    );
  }
}

$timestamp = date("Ymd_His");
if (!is_dir($outDir)) { @mkdir($outDir, 0777, true); }
$jsonPath = $outDir . "/ecommerce_analytics_rutas_unicas_" . $timestamp . ".json";
$csvPath = $outDir . "/ecommerce_analytics_rutas_unicas_" . $timestamp . ".csv";
$conBanderas = count(array_filter($items, function ($item) { return !empty($item["banderas"]); }));
$claves = array_values(array_unique(array_map(function ($item) { return $item["clave_normalizada"]; }, $items)));
$payload = array(
  "ok" => (bool) $db,
  "modo" => "read-only",
  "servidor_db" => $servidor,
  "rango_dias" => $dias,
  "generado_en" => date("Y-m-d H:i:s"),
  "resumen" => array(
    "rutas_crudas" => count($items),
    "claves_normalizadas" => count($claves),
    "rutas_con_banderas" => $conBanderas,
    "rutas_con_revision_previa" => count(array_filter($items, function ($item) { return !empty($item["revision_previa"]); }))
  ),
  "items" => $items,
  "guardrails" => array("no_escribe_bd" => true, "no_crea_redirecciones" => true, "no_modifica_sitemap" => true)
);
file_put_contents($jsonPath, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
$csv = fopen($csvPath, "w");
fputcsv($csv, array("ruta", "clave_normalizada", "page_views", "sesiones", "primera_fecha", "ultima_fecha", "banderas", "resultado_previo", "status_http_previo"));
foreach ($items as $item) {
  fputcsv($csv, array($item["ruta"], $item["clave_normalizada"], $item["page_views"], $item["sesiones"], $item["primera_fecha"], $item["ultima_fecha"], implode("|", $item["banderas"]), $item["revision_previa"]["resultado"] ?? "", $item["revision_previa"]["status_http"] ?? ""));
}
fclose($csv);

echo json_encode(array(
  "ok" => (bool) $db,
  "modo" => "read-only",
  "resumen" => $payload["resumen"],
  "archivos" => array("json" => $jsonPath, "csv" => $csvPath),
  "guardrails" => $payload["guardrails"]
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
