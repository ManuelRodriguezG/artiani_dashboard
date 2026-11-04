<?php
/**
 * IA: Codex GPT-6 | Fecha: 2026-11-04
 * Proposito: revisar ficha publica y fotos de TERAL-12/campanas antes de corregir el borrador.
 * Impacto: evidencia editorial local; sin cambios al catalogo ni al CMS.
 * Contrato: CLI, solo GET de hosts Artiani conocidos; no ejecuta JS/analytics ni muestra errores de conexion.
 */
if (PHP_SAPI !== 'cli') { exit; }
function consultaLocalCamaleon($path, $host = 'panel.com.local') {
  $h = curl_init('http://127.0.0.1' . $path);
  curl_setopt_array($h, array(CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false, CURLOPT_TIMEOUT => 25, CURLOPT_HTTPHEADER => array('Host: ' . $host)));
  $body = curl_exec($h); $status = curl_getinfo($h, CURLINFO_HTTP_CODE); curl_close($h);
  if ($status !== 200 || !is_string($body)) { throw new RuntimeException('Consulta local no disponible'); }
  return $body;
}
try {
  $slug = 'terrario-ecuatorial-50-x-40-x-80cm-puertas-deslizables';
  $html = consultaLocalCamaleon('/producto/' . $slug, 'artiani.com.local');
  file_put_contents(__DIR__ . '/ficha_teral12_frontend.html', $html);
  $revision = array('fecha' => '2026-11-04', 'origen' => 'GET_local_sin_escrituras_BD', 'frontend_200' => true, 'productos' => array());
  foreach (array($slug, 'campana-aluminio-con-gancho', 'campana-de-metal-foco', 'campana-de-metal-para-doble-foco') as $actual) {
    $data = json_decode(consultaLocalCamaleon('/ecommercePublico/producto/' . $actual), true);
    $item = $data['depurar']['item'] ?? null;
    if (!empty($data['error']) || !is_array($item)) { throw new RuntimeException('Ficha publica no disponible'); }
    $revision['productos'][] = $item;
    echo json_encode(array_intersect_key($item, array_flip(array('id_publicacion', 'sku', 'nombre', 'descripcion_publica', 'imagen', 'imagenes', 'disponibilidad'))), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    if ($actual !== $slug) { continue; }
    $imagenes = $item['imagenes'] ?? array();
    if (empty($imagenes)) { $imagenes = array(array('url' => $item['imagen'] ?? '')); }
    foreach (array_slice($imagenes, 0, 3) as $i => $imagen) {
      $url = is_array($imagen) ? ($imagen['url'] ?? $imagen['imagen'] ?? '') : $imagen;
      if (!$url) { continue; }
      $parts = parse_url($url);
      $host = $parts['host'] ?? 'panel.com.local';
      if (!in_array($host, array('panel.com.local', 'sys.artiani.com.mx', 'artiani.com.mx'), true)) { continue; }
      $path = '/' . ltrim($parts['path'] ?? '', '/');
      $body = consultaLocalCamaleon($path);
      $info = @getimagesizefromstring($body);
      if (!$info || !in_array($info['mime'], array('image/jpeg', 'image/png', 'image/webp'), true)) { continue; }
      $ext = array('image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp')[$info['mime']];
      $file = 'teral12-referencia-' . ($i + 1) . '.' . $ext;
      file_put_contents(__DIR__ . '/' . $file, $body);
      echo 'imagen_local=' . $file . ' ' . $info[0] . 'x' . $info[1] . PHP_EOL;
    }
  }
  file_put_contents(__DIR__ . '/fichas_teral12_campanas.json', json_encode($revision, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
} catch (Throwable $e) {
  fwrite(STDERR, 'Revision incompleta: verificar acceso local y fichas publicas; no se modificaron datos.' . PHP_EOL); exit(1);
}
