<?php
/** IA: Codex GPT-6.1-sol | 2026-11-01.
 * Proposito: importar cinco WebP aprobados y asociarlos al borrador Blog1 con validadores Media existentes.
 * Impacto: archivos Media locales, cinco altas deduplicadas, portada/HTML/SEO/media/bloques de un borrador.
 * Contrato: CLI con token, respaldo externo previo, origen editorial restringido y verificaciones HTTP/hash.
 * No publica si el host de archivos publico no ha sido verificado; no suplanta sesiones ni usuarios.
 */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
ini_set('display_errors', '0');
chdir(__DIR__ . '/../../public');
require_once '../app/iniciador.php';
require_once '../app/modelos/EcommerceCatalogoPublico.php';
require_once '../app/modelos/EcommerceBlogPublico.php';
require_once '../app/modelos/SeguridadPermisos.php';
/** IA: Codex GPT-6.1-sol | 2026-11-01. Comprobar HTTP de imagen sin sesion ni redireccion, con hash del archivo aprobado. */
function verificarImagenBlog($base, $url, $hash) {
  $ch = curl_init(rtrim($base, '/') . $url . '?revision_blog=' . substr($hash, 0, 12));
  curl_setopt_array($ch, array(CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false, CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_TIMEOUT => 12));
  $body = curl_exec($ch); $status = curl_getinfo($ch, CURLINFO_HTTP_CODE); $mime = curl_getinfo($ch, CURLINFO_CONTENT_TYPE); curl_close($ch);
  return array('host' => $base, 'url' => $url, 'status' => $status, 'mime' => $mime, 'hash_coincide' => is_string($body) && hash_equals($hash, hash('sha256', $body)), 'ok' => $status === 200 && strpos((string) $mime, 'image/webp') === 0 && is_string($body) && hash_equals($hash, hash('sha256', $body)));
}
$opts = getopt('', array('autorizar:'));
if (($opts['autorizar'] ?? '') !== 'CMS_BLOG_MEDIA_20261101') { echo json_encode(array('ok' => false, 'mensaje' => 'Autorizacion CLI requerida')); exit(1); }
$db = null; $backup = null; $recibos = array();
try {
  $base = realpath(__DIR__ . '/../editorial/blog_piton_bola');
  $plan = json_decode(file_get_contents($base . '/preparacion_cms_v3.json'), true, 512, JSON_THROW_ON_ERROR);
  $template = json_decode(file_get_contents($base . '/payload_formulario_v4.json'), true, 512, JSON_THROW_ON_ERROR);
  $media = new EcommerceCatalogoPublico(); $blog = new EcommerceBlogPublico();
  $get = new ReflectionMethod(CRUD::class, 'getConexion'); $get->setAccessible(true); $db = $get->invoke($blog);
  if (!$db || $db->query('SELECT DATABASE()')->fetchColumn() !== 'artianicom_sys') throw new RuntimeException('Conexion no confirmada');
  $s = $db->query('SELECT * FROM erp_ecommerce_blog_publicaciones WHERE id_blog_publicacion=1'); $row = $s->fetch(PDO::FETCH_ASSOC);
  if (!$row || $row['slug'] !== 'piton-bola-preparar-habitat' || $row['estado'] !== 'borrador') throw new RuntimeException('Borrador cambiado');
  $esperado = trim(file_get_contents($base . '/articulo_cms_referencias_v4.html'));
  if ($row['contenido_html'] !== $esperado) throw new RuntimeException('El articulo tiene cambios nuevos; revisar antes de vincular');
  $snap = array('base' => MYSQLBASE, 'fecha' => date('c'), 'articulo' => $row, 'relaciones' => array(), 'media_existentes' => array());
  foreach (array('erp_ecommerce_blog_media','erp_ecommerce_blog_bloques_interactivos') as $table) { $snap['relaciones'][$table] = $db->query('SELECT * FROM ' . $table . ' WHERE id_blog_publicacion=1')->fetchAll(PDO::FETCH_ASSOC); }
  foreach ($plan['imagenes'] as $img) {
    $path = realpath($base . '/' . $img['archivo_local']);
    if (!$path || dirname($path) !== realpath($base . '/imagenes_revision') || basename($path) !== 'catalogo-v3-' . $img['name'] . '.webp') throw new RuntimeException('Origen no autorizado');
    $inspection = CmsMediaArchivo::inspeccionar($path, basename($path));
    if ($inspection['ancho'] !== 1536 || $inspection['alto'] !== 1024) throw new RuntimeException('Dimensiones cambiadas');
    $q = $db->prepare('SELECT * FROM erp_ecommerce_media_archivos WHERE hash_sha256=:hash'); $q->execute(array(':hash' => $inspection['hash']));
    $snap['media_existentes'][$img['key']] = $q->fetchAll(PDO::FETCH_ASSOC);
  }
  $backupDir = realpath('C:/xampp/panel_db_backups'); if (!$backupDir) throw new RuntimeException('Falta directorio respaldo');
  $backup = $backupDir . '/productivo_panel_20261101_' . date('His') . '_antes_blog1_imagenes.json';
  $snapshot = json_encode($snap, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
  $h = fopen($backup, 'xb'); if (!$h) throw new RuntimeException('No se pudo abrir respaldo'); $bytes = fwrite($h, $snapshot); fclose($h);
  if ($bytes !== strlen($snapshot) || !hash_equals(hash('sha256', $snapshot), hash_file('sha256', $backup))) throw new RuntimeException('Respaldo no verificado');
  $map = array();
  foreach ($plan['imagenes'] as $img) {
    $r = $media->mediaImportarEditorialCli($base . '/' . $img['archivo_local'], array('alt' => $img['alt'], 'nombre_seo' => 'piton-bola-' . $img['name'], 'uso' => 'blog', 'tipo' => 'editorial'));
    if (!empty($r['error']) || $r['tipo'] === 'warning' || empty($r['depurar']['url'])) throw new RuntimeException('Importacion no confirmada');
    $item = $r['depurar']; $check = verificarImagenBlog('http://panel.com.local', $item['url'], $item['hash_sha256']);
    $recibos[] = array('key' => $img['key'], 'id_media_archivo' => $item['id_media_archivo'], 'url' => $item['url'], 'hash_sha256' => $item['hash_sha256'], 'bytes' => $item['bytes'], 'ancho' => $item['ancho'], 'alto' => $item['alto'], 'verificacion_local' => $check);
    if (!$check['ok']) throw new RuntimeException('Acceso local no verificado');
    $map[$img['url_token']] = $item['url'];
  }
  $template = json_decode(strtr(json_encode($template, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $map), true, 512, JSON_THROW_ON_ERROR);
  // Los campos con JSON serializado no contienen escapes de las URLs de tokens; sustitucion explicita por campo.
  foreach (array('contenido_html','imagen_portada','seo','imagenes_json','bloques_interactivos_json') as $field) { $template[$field] = strtr($template[$field], $map); }
  if (strpos(json_encode($template), '@@MEDIA_') !== false) throw new RuntimeException('Marcadores sin resolver');
  $db->beginTransaction();
  $lock = $db->query('SELECT contenido_html,estado FROM erp_ecommerce_blog_publicaciones WHERE id_blog_publicacion=1 FOR UPDATE')->fetch(PDO::FETCH_ASSOC);
  if ($lock['estado'] !== 'borrador' || $lock['contenido_html'] !== $row['contenido_html']) throw new RuntimeException('Edicion concurrente');
  $data = array('id_blog_publicacion' => 1, 'tipo' => $row['tipo'], 'titulo' => $row['titulo'], 'slug' => $row['slug'], 'estado' => 'borrador', 'autor' => $row['autor'], 'extracto' => $row['extracto'], 'orden' => $row['orden'], 'destacado' => $row['destacado'], 'fecha_publicacion' => $row['fecha_publicacion']);
  foreach (array('contenido_html','imagen_portada','imagenes_json','bloques_interactivos_json') as $field) { $data[$field] = $template[$field]; }
  $seo = json_decode($row['seo_json'], true); $seo['og_image'] = $map['@@MEDIA_PORTADA_URL@@']; $data['seo'] = json_encode($seo, JSON_UNESCAPED_UNICODE);
  $r = $blog->adminGuardar($data, 0); if (!empty($r['error'])) throw new RuntimeException('No se guardaron relaciones');
  $db->commit();
  $event = (new SeguridadPermisos())->registrarEvento(array('id_usuario' => null, 'modulo' => 'cms_blog', 'accion' => 'importar_y_vincular_cinco_imagenes_cli_autorizado', 'entidad' => 'erp_ecommerce_blog_publicaciones', 'entidad_id' => 1, 'resultado' => 'ok', 'datos_despues' => array('respaldo' => $backup, 'imagenes' => $recibos, 'origen' => 'CLI_autorizado_dueno_20261101')));
  $remoto = array(); foreach ($recibos as $img) { $remoto[] = verificarImagenBlog('https://sys.artiani.com.mx', $img['url'], $img['hash_sha256']); }
  $actual = $blog->adminConsultar(1);
  echo json_encode(array('ok' => true, 'id' => 1, 'estado' => $actual['depurar']['publicacion']['estado'], 'imagenes' => $recibos, 'apoyos_vinculados' => count($actual['depurar']['relaciones']['imagenes']), 'bloques_vinculados' => count($actual['depurar']['relaciones']['bloques_interactivos']), 'auditoria_ok' => empty($event['error']), 'respaldo' => $backup, 'respaldo_sha256' => hash_file('sha256', $backup), 'verificacion_host_publico' => $remoto, 'publicado' => false), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
  if ($db && $db->inTransaction()) $db->rollBack();
  echo json_encode(array('ok' => false, 'mensaje' => $e instanceof PDOException ? 'Error BD; revisar recibos antes de reintentar' : $e->getMessage(), 'imagenes_importadas' => $recibos, 'respaldo' => $backup), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); exit(1);
}
