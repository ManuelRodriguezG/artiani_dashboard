<?php

/**
 * Documentacion IA: Codex GPT-6, 2026-10-22.
 * Proposito: validar por HTTP el contrato TSV del feed de Meta sin escribir BD ni modificar catalogo.
 * Impacto: Ecommerce/Catalogo; detecta formato, campos obligatorios, disponibilidad e inventario exacto expuesto.
 * Contrato: read-only; requiere que el host HTTP y la conexion de datos esten disponibles.
 */

$opciones = getopt("", array("base::"));
$base = isset($opciones["base"]) ? rtrim(trim((string) $opciones["base"]), "/") : "http://panel.com.local";
$url = $base . "/ecommercePublico/feed_meta";
$contexto = stream_context_create(array(
  "http" => array(
    "method" => "GET",
    "timeout" => 30,
    "ignore_errors" => true,
    "header" => "Accept: text/tab-separated-values\r\n"
  )
));
$body = @file_get_contents($url, false, $contexto);
$headersHttp = isset($http_response_header) && is_array($http_response_header) ? $http_response_header : array();
$status = 0;
if (!empty($headersHttp[0]) && preg_match('/\s(\d{3})\s/', $headersHttp[0], $coincidencia)) {
  $status = intval($coincidencia[1]);
}

$bloqueos = array();
$lineas = $body === false ? array() : preg_split('/\r?\n/', trim((string) $body));
$encabezados = !empty($lineas) ? explode("\t", $lineas[0]) : array();
$columnasEsperadas = array(
  "id", "title", "description", "availability", "condition", "price", "link", "image_link", "brand",
  "google_product_category", "fb_product_category", "quantity_to_sell_on_facebook"
);
if ($body === false) {
  $bloqueos[] = "feed_no_responde";
} else {
  foreach ($columnasEsperadas as $columna) {
    if (!in_array($columna, $encabezados, true)) {
      $bloqueos[] = "falta_columna_" . $columna;
    }
  }
}
if ($status !== 200) {
  $bloqueos[] = "http_status_" . $status;
}
$filasValidas = 0;
$disponibilidades = array();
$indice = array_flip($encabezados);
for ($posicion = 1; $posicion < count($lineas); $posicion++) {
  if (trim($lineas[$posicion]) === "") {
    continue;
  }
  $valores = explode("\t", $lineas[$posicion]);
  if (count($valores) !== count($encabezados)) {
    $bloqueos[] = "fila_" . ($posicion + 1) . "_columnas_incorrectas";
    continue;
  }
  $filasValidas++;
  if (!isset($indice["availability"], $indice["quantity_to_sell_on_facebook"])) {
    continue;
  }
  $disponibilidad = trim((string) $valores[$indice["availability"]]);
  $disponibilidades[$disponibilidad] = isset($disponibilidades[$disponibilidad]) ? $disponibilidades[$disponibilidad] + 1 : 1;
  if (!in_array($disponibilidad, array("in stock", "out of stock"), true)) {
    $bloqueos[] = "fila_" . ($posicion + 1) . "_availability_invalida";
  }
  if (trim((string) $valores[$indice["quantity_to_sell_on_facebook"]]) !== "") {
    $bloqueos[] = "fila_" . ($posicion + 1) . "_expone_cantidad";
  }
}

echo json_encode(array(
  "error" => !empty($bloqueos),
  "url" => $url,
  "http_status" => $status,
  "filas_validas" => $filasValidas,
  "disponibilidades" => $disponibilidades,
  "bloqueos" => array_values(array_unique($bloqueos))
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
