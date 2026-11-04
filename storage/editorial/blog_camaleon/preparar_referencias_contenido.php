<?php
/**
 * IA: Codex GPT-6 | Fecha: 2026-11-04
 * Proposito: reunir fotografias originales del catalogo para el chat de produccion de contenido.
 * Impacto: entrega editorial compartida; conserva identidad, proporciones y restricciones por SKU.
 * Contrato: CLI, solo GET de imagenes del panel local; sin edicion de imagenes, altas Media ni escrituras BD.
 */
if (PHP_SAPI !== 'cli') { exit; }
try {
  $todos = array();
  foreach (array('catalogo_publico_consultado.json', 'catalogo_publico_complemento.json') as $file) {
    $snapshot = json_decode(file_get_contents(__DIR__ . '/' . $file), true);
    foreach ($snapshot['consultas'] as $consulta) {
      foreach ($consulta['items'] as $item) { $todos[$item['id_publicacion']] = $item; }
    }
  }
  $detalle = json_decode(file_get_contents(__DIR__ . '/fichas_teral12_campanas.json'), true);
  foreach ($detalle['productos'] as $item) { $todos[$item['id_publicacion']] = $item; }
  $revision = json_decode(file_get_contents(__DIR__ . '/revision.json'), true);
  $condiciones = array_column($revision['candidatos_revision'], null, 'id_publicacion');
  $out = __DIR__ . '/referencias_catalogo';
  if (!is_dir($out)) { mkdir($out, 0755, true); }
  $manifest = array('ia' => 'Codex GPT-6', 'fecha' => '2026-11-04', 'uso' => 'Referencias originales para produccion visual; no son imagenes finales ni kit aprobado', 'productos' => array());
  foreach (array(269, 2174, 2175, 2176, 191, 2053, 1733, 193, 310) as $id) {
    if (empty($todos[$id])) { throw new RuntimeException('Referencia no encontrada'); }
    $item = $todos[$id];
    $parts = parse_url($item['imagen']);
    if (!empty($parts['host']) && !in_array($parts['host'], array('panel.com.local', 'sys.artiani.com.mx', 'artiani.com.mx'), true)) { throw new RuntimeException('Host fuera de alcance'); }
    $path = '/' . ltrim($parts['path'] ?? '', '/');
    $h = curl_init('http://127.0.0.1' . $path);
    curl_setopt_array($h, array(CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false, CURLOPT_TIMEOUT => 20, CURLOPT_HTTPHEADER => array('Host: panel.com.local')));
    $body = curl_exec($h); $status = curl_getinfo($h, CURLINFO_HTTP_CODE); curl_close($h);
    $info = is_string($body) ? @getimagesizefromstring($body) : false;
    $exts = array('image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp');
    if ($status !== 200 || !$info || !isset($exts[$info['mime']])) { throw new RuntimeException('Imagen publica no disponible'); }
    $file = $item['sku'] . '.' . $exts[$info['mime']];
    file_put_contents($out . '/' . $file, $body);
    $manifest['productos'][] = array('id_publicacion' => $id, 'sku' => $item['sku'], 'nombre' => $item['nombre'], 'url_producto' => $item['url'],
      'archivo' => 'referencias_catalogo/' . $file, 'origen' => 'http://panel.com.local' . $path, 'width' => $info[0], 'height' => $info[1], 'bytes' => strlen($body),
      'sha256' => hash('sha256', $body), 'condiciones_editoriales' => $condiciones[$id]);
    echo $item['sku'] . ': ' . $info[0] . 'x' . $info[1] . ' ' . strlen($body) . ' bytes' . PHP_EOL;
  }
  file_put_contents(__DIR__ . '/manifest_referencias_contenido.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
} catch (Throwable $e) {
  fwrite(STDERR, 'Paquete incompleto: revisar snapshots o imagenes publicas. Sin cambios BD.' . PHP_EOL); exit(1);
}
