<?php
/**
 * Resume reportes JSON de verificacion HTTP de destinos SEO.
 * Version IA: GPT-5 Codex, 2026-09-22.
 * Proposito: separar timeouts de fallas reales para priorizar correcciones SEO.
 * Impacto: solo lectura; no consulta BD ni modifica datos.
 */

$archivo = isset($argv[1]) ? trim((string)$argv[1]) : "";
if ($archivo === "" || !is_file($archivo)) {
  fwrite(STDERR, "Uso: php storage/uat/uat_ecommerce_resumir_reporte_destinos.php <reporte.json>\n");
  exit(1);
}

$json = json_decode(file_get_contents($archivo), true);
$problemas = isset($json["problemas"]) && is_array($json["problemas"]) ? $json["problemas"] : array();
$sinTimeout = array_values(array_filter($problemas, function ($p) {
  return isset($p["problema"]) && $p["problema"] !== "error_http";
}));
$mapeados = array_map(function ($p) {
  return array(
    "problema" => isset($p["problema"]) ? $p["problema"] : "",
    "status" => isset($p["status"]) ? intval($p["status"]) : 0,
    "path" => isset($p["path"]) ? $p["path"] : "",
    "from" => isset($p["origenes"][0]["from"]) ? $p["origenes"][0]["from"] : "",
    "origenes" => isset($p["origenes"]) && is_array($p["origenes"]) ? count($p["origenes"]) : 0,
  );
}, $sinTimeout);

echo json_encode(array(
  "frontend" => isset($json["frontend"]) ? $json["frontend"] : "",
  "total_destinos_unicos" => isset($json["total_destinos_unicos"]) ? intval($json["total_destinos_unicos"]) : 0,
  "total_problemas" => isset($json["total_problemas"]) ? intval($json["total_problemas"]) : 0,
  "resumen_original" => isset($json["resumen"]) ? $json["resumen"] : array(),
  "total_sin_timeouts" => count($sinTimeout),
  "primeros_sin_timeouts" => array_slice($mapeados, 0, 120),
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
