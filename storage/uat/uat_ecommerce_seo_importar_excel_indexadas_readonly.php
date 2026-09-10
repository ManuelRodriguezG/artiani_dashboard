<?php
/**
 * Documentacion IA: Codex GPT-5 | Fecha: 2026-09-09
 * Proposito: convertir export XLSX de URLs indexadas de Google en reporte local SEO read-only.
 * Impacto: prioriza migracion de URLs indexadas sin escribir BD ni crear redirecciones.
 * Contrato: genera JSON en storage/tmp; no modifica tablas ni consulta el sitio productivo.
 */

$archivo = isset($argv[1]) ? $argv[1] : '';
if ($archivo === '' || !is_file($archivo)) {
  fwrite(STDERR, "Uso: php storage/uat/uat_ecommerce_seo_importar_excel_indexadas_readonly.php \"C:\\ruta\\urls.xlsx\"\n");
  exit(1);
}
if (!class_exists('ZipArchive')) {
  fwrite(STDERR, "ZipArchive no disponible en PHP.\n");
  exit(1);
}

$zip = new ZipArchive();
if ($zip->open($archivo) !== true) {
  fwrite(STDERR, "No se pudo abrir XLSX: " . $archivo . "\n");
  exit(1);
}

$sharedXml = $zip->getFromName('xl/sharedStrings.xml');
$sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
if ($sheetXml === false) {
  fwrite(STDERR, "No se encontro xl/worksheets/sheet1.xml\n");
  $zip->close();
  exit(1);
}

$shared = array();
if ($sharedXml !== false) {
  $xml = simplexml_load_string($sharedXml);
  foreach ($xml->si as $si) {
    $texto = '';
    if (isset($si->t)) {
      $texto = (string) $si->t;
    } else {
      foreach ($si->r as $r) {
        $texto .= (string) $r->t;
      }
    }
    $shared[] = $texto;
  }
}

$sheet = simplexml_load_string($sheetXml);
$rows = array();
foreach ($sheet->sheetData->row as $row) {
  $values = array();
  foreach ($row->c as $cell) {
    $value = (string) $cell->v;
    $type = (string) $cell['t'];
    $values[] = ($type === 's' && $value !== '' && isset($shared[(int) $value])) ? $shared[(int) $value] : $value;
  }
  $rows[] = $values;
}
$zip->close();

$headers = array_shift($rows);
$items = array();
$resumen = array(
  'total' => 0,
  'producto' => 0,
  'categoria' => 0,
  'paquete' => 0,
  'undefined' => 0,
  'otras' => 0
);

foreach ($rows as $row) {
  $url = trim((string) ($row[0] ?? ''));
  if ($url === '') { continue; }
  $partes = parse_url($url);
  $path = isset($partes['path']) ? $partes['path'] : '/';
  $tipo = 'otra';
  if (strpos($path, '/producto/categoria/') === 0 || strpos($path, '/categoria') === 0) {
    $tipo = 'categoria';
  } elseif (strpos($path, '/producto/') === 0) {
    $tipo = 'producto';
  } elseif (strpos($path, '/paquete/') === 0) {
    $tipo = 'paquete';
  }

  $resumen['total']++;
  if (isset($resumen[$tipo])) {
    $resumen[$tipo]++;
  } else {
    $resumen['otras']++;
  }
  if (stripos($url, 'undefined') !== false) {
    $resumen['undefined']++;
  }

  $items[] = array(
    'url_original' => $url,
    'path_original' => $path,
    'ultimo_rastreo_excel' => isset($row[1]) ? $row[1] : '',
    'tipo_detectado' => $tipo,
    'origen' => 'google_indexadas_excel',
    'estatus_mapeo' => 'pendiente'
  );
}

if (!is_dir('storage/tmp')) {
  mkdir('storage/tmp', 0777, true);
}
$salida = 'storage/tmp/ecommerce_seo_urls_indexadas_google_' . date('Ymd_His') . '.json';
$payload = array(
  'fuente' => $archivo,
  'headers' => $headers,
  'generado_en' => date('c'),
  'total' => count($items),
  'resumen' => $resumen,
  'items' => $items,
  'guardrails' => array(
    'read_only' => true,
    'no_escribe_bd' => true,
    'no_crea_redirecciones' => true,
    'fuente_google_indexadas' => true
  )
);
file_put_contents($salida, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

echo json_encode(array(
  'ok' => true,
  'modo' => 'read-only',
  'archivo_salida' => $salida,
  'headers' => $headers,
  'total' => count($items),
  'resumen' => $resumen
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
