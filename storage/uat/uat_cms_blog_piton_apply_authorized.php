<?php
/**
 * IA: Codex GPT-6.1-sol | Fecha: 2026-11-01.
 * Proposito: aplicar las ocho tablas Blog autorizadas y crear un unico borrador de piton bola, sin imagenes.
 * Impacto: solo esquema Blog, borrador/relaciones y auditoria; no publica ni sube archivos.
 * Contrato: CLI, token de operacion y respaldo externo completo verificable. Reejecucion no modifica articulo existente.
 */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
ini_set('display_errors', '0');
chdir(__DIR__ . '/../../public');
require_once '../app/iniciador.php';
require_once '../app/modelos/EcommerceBlogPublico.php';
require_once '../app/modelos/EcommercePublicoEsquema.php';
require_once '../app/modelos/SeguridadPermisos.php';

/** IA: Codex GPT-6.1-sol | 2026-11-01. Exponer conexion del modelo solo al runner CLI para transaccion atomica del borrador. */
class BlogPitonApply extends EcommerceBlogPublico {
  /** IA: Codex GPT-6.1-sol | 2026-11-01. Contrato: misma conexion usada por adminGuardar. */
  public function db() { return $this->getConexion(); }
}

$opts = getopt('', array('autorizar:', 'respaldo:'));
$backup = realpath($opts['respaldo'] ?? '');
$backupDir = realpath('C:/xampp/panel_db_backups');
if (($opts['autorizar'] ?? '') !== 'CMS_BLOG_PITON_20261101' || !$backup || !$backupDir || strcasecmp(dirname($backup), $backupDir) !== 0 || !is_file($backup) || filesize($backup) <= 0) {
  echo json_encode(array('ok' => false, 'mensaje' => 'Token y respaldo externo valido obligatorios')); exit(1);
}
$tail = file_get_contents($backup, false, null, max(0, filesize($backup) - 2048));
if (strpos($tail, '-- Dump completed on ') === false) { echo json_encode(array('ok' => false, 'mensaje' => 'Respaldo incompleto')); exit(1); }
$receipt = array('respaldo' => $backup, 'sha256' => hash_file('sha256', $backup), 'bytes' => filesize($backup));
$db = null;
try {
  $blog = new BlogPitonApply();
  $db = $blog->db();
  if (!$db || $db->query('SELECT DATABASE()')->fetchColumn() !== 'artianicom_sys') throw new RuntimeException('Contexto BD inesperado');
  $esquema = new EcommercePublicoEsquema();
  $plan = $esquema->planActualizarCmsBlog(false);
  if (!empty($plan['error']) || intval($plan['depurar']['ddl_total'] ?? 0) !== 8) throw new RuntimeException('Plan invalido');
  $ddl = $esquema->planActualizarCmsBlog(true);
  if (!empty($ddl['error'])) throw new RuntimeException('DDL incompleto');
  $audit = $esquema->auditarCmsBlog();
  if (intval($audit['depurar']['tablas_faltantes'] ?? 8) !== 0) throw new RuntimeException('Faltan tablas despues del DDL');

  $payload = json_decode(file_get_contents(__DIR__ . '/../editorial/blog_piton_bola/payload_formulario_v3.json'), true, 512, JSON_THROW_ON_ERROR);
  if (($payload['slug'] ?? '') !== 'piton-bola-preparar-habitat') throw new RuntimeException('Payload inesperado');
  $payload['contenido_html'] = preg_replace('~<figure>.*?</figure>~s', '', $payload['contenido_html']);
  $payload['imagen_portada'] = '{}';
  $payload['imagenes_json'] = '[]';
  $payload['bloques_interactivos_json'] = '[]';
  $payload['estado'] = 'borrador';
  $payload['fecha_publicacion'] = '';
  $seo = json_decode($payload['seo'], true, 512, JSON_THROW_ON_ERROR);
  $seo['og_image'] = '';
  $payload['seo'] = json_encode($seo, JSON_UNESCAPED_UNICODE);
  if (strpos(json_encode($payload), '@@MEDIA_') !== false) throw new RuntimeException('Marcador Media pendiente');
  $db->beginTransaction();
  $stmt = $db->prepare('SELECT id_blog_publicacion, estado FROM erp_ecommerce_blog_publicaciones WHERE slug=:slug LIMIT 1 FOR UPDATE');
  $stmt->execute(array(':slug' => $payload['slug']));
  $existing = $stmt->fetch(PDO::FETCH_ASSOC);
  if ($existing) {
    $id = intval($existing['id_blog_publicacion']);
    $creado = false;
  } else {
    $saved = $blog->adminGuardar($payload, 0);
    if (!empty($saved['error']) || empty($saved['depurar']['id_blog_publicacion'])) throw new RuntimeException('No se guardo borrador');
    $id = intval($saved['depurar']['id_blog_publicacion']);
    $creado = true;
  }
  $db->commit();
  $event = (new SeguridadPermisos())->registrarEvento(array(
    'id_usuario' => null, 'modulo' => 'cms_blog', 'accion' => 'activar_blog_y_borrador_cli_autorizado',
    'entidad' => 'erp_ecommerce_blog_publicaciones', 'entidad_id' => $id, 'resultado' => 'ok',
    'datos_despues' => array_merge($receipt, array('creado' => $creado, 'estado_solicitado' => 'borrador', 'autorizacion' => 'dueno_chat_20261101', 'origen' => 'CLI_sin_suplantar_usuario'))
  ));
  $check = $blog->adminConsultar($id);
  echo json_encode(array('ok' => true, 'id_blog_publicacion' => $id, 'creado' => $creado, 'tablas_disponibles' => 8, 'estado' => $check['depurar']['publicacion']['estado'] ?? ($existing['estado'] ?? 'borrador'), 'auditoria_ok' => empty($event['error']), 'respaldo' => $receipt, 'imagenes_subidas' => 0, 'publicado' => false), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $error) {
  if ($db && $db->inTransaction()) $db->rollBack();
  echo json_encode(array('ok' => false, 'mensaje' => 'Operacion incompleta; revisar estado antes de reintentar', 'codigo' => (string) $error->getCode(), 'respaldo' => $receipt));
  exit(1);
}
