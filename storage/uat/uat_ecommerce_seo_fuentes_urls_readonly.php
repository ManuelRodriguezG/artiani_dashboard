<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-09-16.
 * Proposito: contar fuentes locales de URLs indexadas Google usadas por la mesa SEO.
 * Impacto: read-only; ayuda a detectar si el XLSX de Search Console trae mas filas que el JSON importado.
 * Contrato: no escribe BD, no importa URLs, no crea redirecciones.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";

$json = __DIR__ . "/../tmp/ecommerce_seo_urls_indexadas_google_20260910_044027.json";
$xlsx = "C:\\Users\\aleja\\Downloads\\urls indexadas actuales.xlsx";

$resultado = array(
  "json_indexadas" => contarJsonIndexadas($json),
  "xlsx_descargas" => contarXlsxFilas($xlsx),
  "guardrails" => array(
    "read_only" => true,
    "no_importa_urls" => true,
    "no_escribe_bd" => true
  )
);

echo json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function contarJsonIndexadas($archivo) {
  if (!is_file($archivo)) {
    return array("existe" => false, "archivo" => $archivo);
  }
  $payload = json_decode(file_get_contents($archivo), true);
  $items = isset($payload["items"]) && is_array($payload["items"]) ? $payload["items"] : array();
  return array(
    "existe" => true,
    "archivo" => $archivo,
    "bytes" => filesize($archivo),
    "total_campo" => isset($payload["total"]) ? intval($payload["total"]) : null,
    "items" => count($items),
    "primeras_urls" => array_slice(array_map(function($item) {
      return isset($item["url_original"]) ? $item["url_original"] : (isset($item["path_original"]) ? $item["path_original"] : "");
    }, $items), 0, 5)
  );
}

function contarXlsxFilas($archivo) {
  if (!is_file($archivo)) {
    return array("existe" => false, "archivo" => $archivo);
  }
  if (!class_exists("ZipArchive")) {
    return array("existe" => true, "archivo" => $archivo, "error" => "ziparchive_no_disponible");
  }
  $zip = new ZipArchive();
  if ($zip->open($archivo) !== true) {
    return array("existe" => true, "archivo" => $archivo, "error" => "no_se_pudo_abrir_xlsx");
  }
  $sheet = $zip->getFromName("xl/worksheets/sheet1.xml");
  $zip->close();
  if ($sheet === false || $sheet === "") {
    return array("existe" => true, "archivo" => $archivo, "error" => "sheet1_no_encontrada");
  }
  preg_match_all('/<row\b[^>]*\br="(\d+)"/', $sheet, $matches);
  $filas = isset($matches[1]) ? array_map("intval", $matches[1]) : array();
  $totalFilas = count($filas);
  $maxFila = empty($filas) ? 0 : max($filas);
  return array(
    "existe" => true,
    "archivo" => $archivo,
    "bytes" => filesize($archivo),
    "filas_xml" => $totalFilas,
    "max_fila" => $maxFila,
    "filas_datos_estimadas_sin_encabezado" => max(0, $totalFilas - 1)
  );
}
