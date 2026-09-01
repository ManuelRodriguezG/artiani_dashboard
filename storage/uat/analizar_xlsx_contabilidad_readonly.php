<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-08-31
 * Proposito: diagnosticar lectura XLSX de estados de cuenta sin modificar el archivo origen.
 * Impacto: herramienta temporal read-only para comparar valores crudos contra valores formateados.
 * Contrato: recibe ruta XLSX por argv[1] e imprime hojas y primeras filas legibles.
 */

error_reporting(E_ERROR | E_PARSE);

if (PHP_SAPI !== "cli") {
  exit("Solo CLI\n");
}

$archivo = isset($argv[1]) ? $argv[1] : "";
if ($archivo === "" || !is_file($archivo)) {
  exit("Archivo no encontrado\n");
}

require_once __DIR__ . "/../../app/helpers/PHPExcel-1.8/Classes/PHPExcel.php";
require_once __DIR__ . "/../../app/helpers/PHPExcel-1.8/Classes/PHPExcel/IOFactory.php";

$reader = PHPExcel_IOFactory::createReaderForFile($archivo);
$reader->setReadDataOnly(false);
$excel = $reader->load($archivo);

echo "Archivo: " . $archivo . PHP_EOL;
echo "Hojas: " . $excel->getSheetCount() . PHP_EOL . PHP_EOL;

foreach ($excel->getWorksheetIterator() as $sheetIndex => $sheet) {
  $highestRow = $sheet->getHighestRow();
  $highestColumn = $sheet->getHighestColumn();
  $highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn);
  echo "Hoja " . ($sheetIndex + 1) . ": " . $sheet->getTitle() . PHP_EOL;
  echo "Rango detectado: A1:" . $highestColumn . $highestRow . " (" . $highestRow . " filas, " . $highestColumnIndex . " columnas)" . PHP_EOL;

  $limiteFilas = min($highestRow, 18);
  $limiteColumnas = min($highestColumnIndex, 14);
  for ($row = 1; $row <= $limiteFilas; $row++) {
    $valores = array();
    $crudos = array();
    for ($col = 0; $col < $limiteColumnas; $col++) {
      $cell = $sheet->getCellByColumnAndRow($col, $row);
      $formatted = trim((string) $cell->getFormattedValue());
      $raw = $cell->getValue();
      $valores[] = $formatted;
      $crudos[] = is_scalar($raw) ? (string) $raw : "";
    }
    if (trim(implode("", $valores)) === "" && trim(implode("", $crudos)) === "") {
      continue;
    }
    echo "F" . $row . " formatted: " . json_encode($valores, JSON_UNESCAPED_UNICODE) . PHP_EOL;
    echo "F" . $row . " raw      : " . json_encode($crudos, JSON_UNESCAPED_UNICODE) . PHP_EOL;
  }
  echo PHP_EOL;
}

echo "XML interno primeras filas:" . PHP_EOL;
$zip = new ZipArchive();
if ($zip->open($archivo) === true) {
  $shared = array();
  $sharedXml = $zip->getFromName("xl/sharedStrings.xml");
  if ($sharedXml !== false) {
    $sxShared = @simplexml_load_string($sharedXml);
    if ($sxShared) {
      foreach ($sxShared->si as $si) {
        $partes = array();
        if (isset($si->t)) {
          $partes[] = (string) $si->t;
        }
        if (isset($si->r)) {
          foreach ($si->r as $run) {
            if (isset($run->t)) {
              $partes[] = (string) $run->t;
            }
          }
        }
        $shared[] = implode("", $partes);
      }
    }
  }
  $sheetXml = $zip->getFromName("xl/worksheets/sheet1.xml");
  $zip->close();
  $sx = $sheetXml !== false ? @simplexml_load_string($sheetXml) : false;
  if ($sx) {
    $rowCount = 0;
    foreach ($sx->sheetData->row as $row) {
      $rowCount++;
      if ($rowCount > 8) {
        break;
      }
      $celdas = array();
      foreach ($row->c as $cell) {
        $tipo = isset($cell["t"]) ? (string) $cell["t"] : "";
        $ref = isset($cell["r"]) ? (string) $cell["r"] : "";
        $raw = isset($cell->v) ? (string) $cell->v : "";
        $decoded = $tipo === "s" && isset($shared[intval($raw)]) ? $shared[intval($raw)] : $raw;
        $celdas[] = array("ref" => $ref, "t" => $tipo, "v" => $raw, "decoded" => $decoded);
      }
      echo "Row " . $rowCount . ": " . json_encode($celdas, JSON_UNESCAPED_UNICODE) . PHP_EOL;
    }
  }
}
