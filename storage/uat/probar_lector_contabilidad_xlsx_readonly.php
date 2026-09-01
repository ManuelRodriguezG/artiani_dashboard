<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-08-31
 * Proposito: probar lectura XLSX de Contabilidad contra un archivo real sin tocar datos.
 * Impacto: herramienta temporal read-only para verificar sharedStrings y filas mapeables.
 * Contrato: recibe ruta XLSX por argv[1] e imprime matriz inicial decodificada.
 */

if (PHP_SAPI !== "cli") {
  exit("Solo CLI\n");
}

$archivo = isset($argv[1]) ? $argv[1] : "";
if ($archivo === "" || !is_file($archivo)) {
  exit("Archivo no encontrado\n");
}

function sharedStrings($zip) {
  $xmlTexto = $zip->getFromName("xl/sharedStrings.xml");
  if ($xmlTexto === false) {
    return array();
  }
  $dom = new DOMDocument();
  if (!@$dom->loadXML($xmlTexto)) {
    return array();
  }
  $xpath = new DOMXPath($dom);
  $items = $xpath->query("//*[local-name()='si']");
  $strings = array();
  foreach ($items as $si) {
    $partes = array();
    foreach ($xpath->query(".//*[local-name()='t']", $si) as $texto) {
      $partes[] = $texto->nodeValue;
    }
    $strings[] = implode("", $partes);
  }
  return $strings;
}

function columnaIndice($columna) {
  $indice = 0;
  for ($i = 0; $i < strlen($columna); $i++) {
    $indice = ($indice * 26) + (ord($columna[$i]) - 64);
  }
  return max(0, $indice - 1);
}

$zip = new ZipArchive();
$zip->open($archivo);
$strings = sharedStrings($zip);
$sheetXml = $zip->getFromName("xl/worksheets/sheet1.xml");
$zip->close();

$dom = new DOMDocument();
$dom->loadXML($sheetXml);
$xpath = new DOMXPath($dom);
$rows = $xpath->query("//*[local-name()='sheetData']/*[local-name()='row']");
$salida = array();
foreach ($rows as $row) {
  $fila = array();
  $cells = $xpath->query("./*[local-name()='c']", $row);
  foreach ($cells as $cell) {
    $ref = $cell->getAttribute("r");
    $col = preg_replace("/[^A-Z]/", "", strtoupper($ref));
    $indice = columnaIndice($col);
    $tipo = $cell->getAttribute("t");
    $valorNode = $xpath->query("./*[local-name()='v']", $cell)->item(0);
    $valor = $valorNode ? $valorNode->nodeValue : "";
    if ($tipo === "s") {
      $valor = isset($strings[intval($valor)]) ? $strings[intval($valor)] : "";
    }
    $fila[$indice] = $valor;
  }
  if (!empty($fila)) {
    ksort($fila);
    $salida[] = array_values($fila);
  }
  if (count($salida) >= 8) {
    break;
  }
}

echo json_encode($salida, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
