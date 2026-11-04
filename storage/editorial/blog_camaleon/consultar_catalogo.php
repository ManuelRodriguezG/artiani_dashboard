<?php
/**
 * IA: Codex GPT-6 | Fecha: 2026-11-04
 * Proposito: conservar evidencia publica de productos para el borrador sobre camaleones.
 * Impacto: preparacion editorial local; no registra articulo, relaciones ni analytics.
 * Contrato: CLI, solo GET locales al catalogo; guarda respuestas publicas sin credenciales/precios fijos editoriales.
 */
if (PHP_SAPI !== 'cli') { exit; }
$consultas = array(
  'camaleon' => array('q' => 'camaleon'),
  'terrarios' => array('categoria' => 594, 'incluir_hijos' => 1),
  'calefaccion' => array('categoria' => 489, 'incluir_hijos' => 1),
  'decoracion' => array('categoria' => 490, 'incluir_hijos' => 1),
  'uvb' => array('q' => 'UVB'),
  'medicion' => array('q' => 'higrometro'),
  'lluvia' => array('q' => 'lluvia'),
  'goteo' => array('q' => 'goteo'),
  'malla' => array('q' => 'malla')
);
$complemento = in_array('--complemento', $argv, true);
if ($complemento) {
  $consultas = array('termometro' => array('q' => 'termometro'), 'termostato' => array('q' => 'termostato'),
    'pulverizador' => array('q' => 'pulverizador'), 'rama' => array('q' => 'rama'), 'liana' => array('q' => 'liana'),
    'uvb_lineal' => array('q' => 'T5'), 'calcio' => array('q' => 'calcio'));
}
$evidencia = array('ia' => 'Codex GPT-6', 'fecha' => '2026-11-04', 'modo' => 'GET_publico_sin_escrituras_BD', 'consultas' => array());
foreach ($consultas as $nombre => $filtros) {
  $params = array_merge($filtros, array('limite' => 60, 'vista' => 'card', 'agrupacion' => 'sku'));
  $path = '/ecommercePublico/catalogo?' . http_build_query($params);
  $h = curl_init('http://127.0.0.1' . $path);
  curl_setopt_array($h, array(CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false, CURLOPT_TIMEOUT => 25, CURLOPT_HTTPHEADER => array('Host: panel.com.local')));
  $body = curl_exec($h); $status = curl_getinfo($h, CURLINFO_HTTP_CODE); curl_close($h);
  $data = json_decode((string) $body, true);
  if ($status !== 200 || !is_array($data) || !empty($data['error'])) {
    fwrite(STDERR, 'Consulta publica no disponible: ' . $nombre . PHP_EOL); exit(1);
  }
  $items = $data['depurar']['items'] ?? array();
  $evidencia['consultas'][$nombre] = array('path' => $path, 'total' => $data['depurar']['paginacion']['total'] ?? count($items), 'items' => $items);
  echo $nombre . ': ' . count($items) . ' items' . PHP_EOL;
  foreach ($items as $item) {
    echo json_encode(array_intersect_key($item, array_flip(array('id_publicacion', 'id_producto_erp', 'sku', 'nombre', 'url', 'disponibilidad'))), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
  }
}
file_put_contents(__DIR__ . ($complemento ? '/catalogo_publico_complemento.json' : '/catalogo_publico_consultado.json'), json_encode($evidencia, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
