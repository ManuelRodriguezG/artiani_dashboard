<?php
/** IA: Codex GPT-6.1-sol | 2026-11-01.
 * Proposito: activar acceso privado servidor-servidor para revisar Blog1 en la maquina local.
 * Contrato: CLI, clave aleatoria en storage fuera de public y Git. Sin BD, sin publicar, no muestra clave.
 */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$dir = __DIR__ . '/../cms_blog_preview_local';
if (!is_dir($dir) && !mkdir($dir, 0700, true)) { throw new RuntimeException('No se pudo preparar acceso local'); }
$file = $dir . '/access.json';
if (!is_file($file)) {
  $handle = fopen($file, 'xb');
  if (!$handle) { throw new RuntimeException('No se pudo crear acceso local'); }
  fwrite($handle, json_encode(array('habilitado' => true, 'key' => bin2hex(random_bytes(32)), 'id_blog_publicacion' => 1, 'slug' => 'piton-bola-preparar-habitat')));
  fclose($handle); chmod($file, 0600);
}
echo json_encode(array('ok' => true, 'preview_local' => true, 'publicado' => false, 'clave_no_expuesta' => true));
