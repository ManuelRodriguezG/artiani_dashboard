<?php
/**
 * Genera reportes filtrados de redirecciones con destino problemático.
 * Version IA: GPT-5 Codex, 2026-09-22.
 * Proposito: convertir la auditoria HTTP masiva en una lista accionable sin timeouts.
 * Impacto: escribe archivos locales CSV/JSON/HTML en storage/tmp; no modifica BD.
 */

$archivo = isset($argv[1]) ? trim((string)$argv[1]) : "";
if ($archivo === "" || !is_file($archivo)) {
  fwrite(STDERR, "Uso: php storage/uat/uat_ecommerce_generar_reporte_errores_redirecciones.php <reporte_http.json>\n");
  exit(1);
}

$datos = json_decode(file_get_contents($archivo), true);
$problemas = isset($datos["problemas"]) && is_array($datos["problemas"]) ? $datos["problemas"] : array();
$accionables = array_values(array_filter($problemas, function ($item) {
  return isset($item["problema"]) && in_array($item["problema"], array("status_no_ok", "contenido_no_encontrado"), true);
}));

usort($accionables, function ($a, $b) {
  $pa = isset($a["problema"]) ? $a["problema"] : "";
  $pb = isset($b["problema"]) ? $b["problema"] : "";
  if ($pa !== $pb) {
    return strcmp($pa, $pb);
  }
  return strcmp(isset($a["path"]) ? $a["path"] : "", isset($b["path"]) ? $b["path"] : "");
});

$rows = array();
foreach ($accionables as $item) {
  $origenes = isset($item["origenes"]) && is_array($item["origenes"]) ? $item["origenes"] : array();
  foreach ($origenes as $origen) {
    $rows[] = array(
      "problema" => isset($item["problema"]) ? $item["problema"] : "",
      "status_destino" => isset($item["status"]) ? intval($item["status"]) : 0,
      "destino_actual" => isset($item["path"]) ? $item["path"] : "",
      "url_staging" => isset($item["url"]) ? $item["url"] : "",
      "origen_viejo" => isset($origen["from"]) ? $origen["from"] : "",
      "tipo" => isset($origen["tipo"]) ? $origen["tipo"] : "",
      "accion_sugerida" => accionSugerida(isset($item["problema"]) ? $item["problema"] : "", isset($item["status"]) ? intval($item["status"]) : 0),
      "nuevo_destino" => "",
      "notas_revision" => "",
    );
  }
}

$dir = dirname($archivo);
$base = preg_replace('/\.json$/i', "", basename($archivo));
$csv = $dir . "/" . $base . "_solo_errores_accionables.csv";
$json = $dir . "/" . $base . "_solo_errores_accionables.json";
$html = $dir . "/" . $base . "_solo_errores_accionables.html";

guardarCsv($csv, $rows);
file_put_contents($json, json_encode(array(
  "fuente" => $archivo,
  "frontend" => isset($datos["frontend"]) ? $datos["frontend"] : "",
  "total_destinos_accionables" => count($accionables),
  "total_filas_revision" => count($rows),
  "resumen" => resumen($accionables),
  "items" => $rows,
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
guardarHtml($html, $rows, $datos, $accionables);

echo json_encode(array(
  "total_destinos_accionables" => count($accionables),
  "total_filas_revision" => count($rows),
  "csv" => $csv,
  "json" => $json,
  "html" => $html,
  "resumen" => resumen($accionables),
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function accionSugerida($problema, $status)
{
  if ($problema === "contenido_no_encontrado") {
    return "Buscar publicación real y editar destino; la ruta responde 200 pero muestra no encontrado.";
  }
  if ($status === 404) {
    return "Editar destino hacia slug publicado real o cambiar a categoría equivalente.";
  }
  return "Revisar destino manualmente.";
}

function resumen($items)
{
  $resumen = array();
  foreach ($items as $item) {
    $k = isset($item["problema"]) ? $item["problema"] : "desconocido";
    if (!isset($resumen[$k])) {
      $resumen[$k] = 0;
    }
    $resumen[$k]++;
  }
  return $resumen;
}

function guardarCsv($archivo, $rows)
{
  $fp = fopen($archivo, "w");
  $headers = array("problema", "status_destino", "destino_actual", "url_staging", "origen_viejo", "tipo", "accion_sugerida", "nuevo_destino", "notas_revision");
  fputcsv($fp, $headers);
  foreach ($rows as $row) {
    fputcsv($fp, array_map(function ($h) use ($row) {
      return isset($row[$h]) ? $row[$h] : "";
    }, $headers));
  }
  fclose($fp);
}

function guardarHtml($archivo, $rows, $datos, $accionables)
{
  $html = '<!doctype html><html lang="es"><head><meta charset="utf-8"><title>Redirecciones SEO por corregir</title>';
  $html .= '<style>body{font-family:Arial,sans-serif;margin:24px;color:#1f2937}table{border-collapse:collapse;width:100%;font-size:13px}th,td{border:1px solid #d1d5db;padding:8px;vertical-align:top}th{background:#f3f4f6;text-align:left}.path{font-family:Consolas,monospace;word-break:break-word}.badge{display:inline-block;padding:2px 8px;border-radius:12px;background:#fee2e2;color:#991b1b;font-weight:700}.note{color:#6b7280}.ok{background:#fef3c7;color:#92400e}</style>';
  $html .= '</head><body>';
  $html .= '<h1>Redirecciones SEO por corregir</h1>';
  $html .= '<p class="note">Fuente: ' . e(isset($datos["frontend"]) ? $datos["frontend"] : "") . '. Se excluyeron timeouts para que esta lista solo tenga casos accionables.</p>';
  $html .= '<p><strong>Destinos accionables:</strong> ' . count($accionables) . ' | <strong>Filas de revisión:</strong> ' . count($rows) . '</p>';
  $html .= '<table><thead><tr><th>Problema</th><th>Status</th><th>Origen viejo</th><th>Destino actual</th><th>URL staging</th><th>Acción sugerida</th><th>Nuevo destino</th><th>Notas</th></tr></thead><tbody>';
  foreach ($rows as $row) {
    $html .= '<tr>';
    $html .= '<td><span class="badge">' . e($row["problema"]) . '</span></td>';
    $html .= '<td>' . e($row["status_destino"]) . '</td>';
    $html .= '<td class="path">' . e($row["origen_viejo"]) . '</td>';
    $html .= '<td class="path">' . e($row["destino_actual"]) . '</td>';
    $html .= '<td class="path"><a href="' . e($row["url_staging"]) . '" target="_blank" rel="noopener">' . e($row["url_staging"]) . '</a></td>';
    $html .= '<td>' . e($row["accion_sugerida"]) . '</td>';
    $html .= '<td></td><td></td>';
    $html .= '</tr>';
  }
  $html .= '</tbody></table></body></html>';
  file_put_contents($archivo, $html);
}

function e($value)
{
  return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}
