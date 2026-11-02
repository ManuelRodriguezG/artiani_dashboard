<?php
/**
 * IA: Codex GPT-6.1-sol | Fecha: 2026-11-01.
 * Proposito: actualizar solo texto/referencias del borrador ID1 con respaldo externo puntual.
 * Impacto: contenido_html/contenido_texto/fecha_actualizacion y auditoria; conserva estado, SEO, imagenes y relaciones.
 * Contrato: CLI y token explicito; compara contenido previo bajo bloqueo para no sobrescribir cambios concurrentes.
 */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
ini_set('display_errors', '0');
chdir(__DIR__ . '/../../public');
require_once '../app/iniciador.php';
require_once '../app/modelos/EcommerceBlogPublico.php';
require_once '../app/modelos/SeguridadPermisos.php';
$db = null;
$respaldo = null;
try {
  $opts = getopt('', array('autorizar:'));
  $spec = json_decode(file_get_contents(__DIR__ . '/../editorial/blog_piton_bola/cambio_referencias_v4.json'), true, 512, JSON_THROW_ON_ERROR);
  if ($spec['id'] !== 1 || $spec['slug'] !== 'piton-bola-preparar-habitat' || $spec['estado'] !== 'borrador') throw new RuntimeException('Objetivo inesperado');
  $blog = new EcommerceBlogPublico();
  $get = new ReflectionMethod(CRUD::class, 'getConexion');
  $get->setAccessible(true);
  $db = $get->invoke($blog);
  if (!$db || $db->query('SELECT DATABASE()')->fetchColumn() !== 'artianicom_sys') throw new RuntimeException('Conexion no confirmada');
  $sanitizer = new ReflectionMethod(EcommerceBlogPublico::class, 'sanitizarContenidoHtmlBlog');
  $sanitizer->setAccessible(true);
  $nuevo = $sanitizer->invoke($blog, $spec['nuevo']);
  if ($nuevo !== trim($spec['nuevo']) || substr_count($nuevo, '<h2>Fuentes de consulta</h2>') !== 1) throw new RuntimeException('HTML no esperado');
  if (($opts['autorizar'] ?? '') !== 'CMS_BLOG_REFERENCIAS_20261101') {
    echo json_encode(array('ok' => true, 'read_only' => true, 'id' => 1, 'sha256_nuevo' => hash('sha256', $nuevo))); exit;
  }
  $db->beginTransaction();
  $s = $db->prepare('SELECT id_blog_publicacion,slug,estado,contenido_html,contenido_texto,fecha_actualizacion FROM erp_ecommerce_blog_publicaciones WHERE id_blog_publicacion=1 FOR UPDATE');
  $s->execute(); $row = $s->fetch(PDO::FETCH_ASSOC);
  if (!$row || $row['slug'] !== $spec['slug'] || $row['estado'] !== 'borrador') throw new RuntimeException('Estado cambiado');
  if (hash_equals(hash('sha256', $nuevo), hash('sha256', $row['contenido_html']))) {
    $db->rollBack(); echo json_encode(array('ok' => true, 'ya_aplicado' => true, 'id' => 1)); exit;
  }
  if (!hash_equals(hash('sha256', $spec['anterior']), hash('sha256', $row['contenido_html']))) throw new RuntimeException('Contenido concurrente');
  $dir = realpath('C:/xampp/panel_db_backups');
  if (!$dir) throw new RuntimeException('Directorio de respaldo no disponible');
  $respaldo = $dir . '/productivo_panel_20261101_' . date('His') . '_antes_blog1_referencias.sql';
  $parts = array();
  foreach (array('contenido_html','contenido_texto','fecha_actualizacion') as $campo) {
    $parts[] = '`' . $campo . '`=' . ($row[$campo] === null ? 'NULL' : $db->quote($row[$campo]));
  }
  $sql = "-- Respaldo puntual antes de referencias Blog1; restaurar solo con plan autorizado.\nSET NAMES utf8mb4;\nUPDATE erp_ecommerce_blog_publicaciones SET " . implode(',', $parts) . " WHERE id_blog_publicacion=1 AND slug='piton-bola-preparar-habitat';\n-- Fin respaldo puntual\n";
  $file = fopen($respaldo, 'xb');
  if (!$file) throw new RuntimeException('No se pudo crear respaldo');
  $written = fwrite($file, $sql); fclose($file);
  if ($written !== strlen($sql) || hash_file('sha256', $respaldo) !== hash('sha256', $sql)) throw new RuntimeException('Respaldo no verificado');
  $u = $db->prepare('UPDATE erp_ecommerce_blog_publicaciones SET contenido_html=:html,contenido_texto=:texto,fecha_actualizacion=NOW() WHERE id_blog_publicacion=1 AND estado=:estado');
  $u->execute(array(':html' => $nuevo, ':texto' => trim(html_entity_decode(strip_tags($nuevo), ENT_QUOTES | ENT_HTML5, 'UTF-8')), ':estado' => 'borrador'));
  if ($u->rowCount() !== 1) throw new RuntimeException('No se modifico una fila');
  $db->commit();
  $event = (new SeguridadPermisos())->registrarEvento(array('id_usuario' => null, 'modulo' => 'cms_blog', 'accion' => 'referencias_editoriales_cli_autorizado', 'entidad' => 'erp_ecommerce_blog_publicaciones', 'entidad_id' => 1, 'resultado' => 'ok', 'datos_despues' => array('respaldo' => $respaldo, 'sha256_html' => hash('sha256', $nuevo), 'autorizacion' => 'dueno_chat_20261101', 'estado' => 'borrador')));
  $check = $blog->adminConsultar(1);
  echo json_encode(array('ok' => true, 'id' => 1, 'estado' => $check['depurar']['publicacion']['estado'], 'contenido_verificado' => $check['depurar']['publicacion']['contenido_html'] === $nuevo, 'auditoria_ok' => empty($event['error']), 'respaldo' => $respaldo, 'respaldo_bytes' => filesize($respaldo), 'respaldo_sha256' => hash_file('sha256', $respaldo)), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
  if ($db && $db->inTransaction()) $db->rollBack();
  echo json_encode(array('ok' => false, 'mensaje' => 'Revisar estado antes de reintentar', 'codigo' => (string) $e->getCode(), 'respaldo' => $respaldo)); exit(1);
}
