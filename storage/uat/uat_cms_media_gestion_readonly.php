<?php
/** IA: Codex GPT-6 | Fecha: 2026-09-24
 * Proposito: verificar contrato y referencias reales sin modificar imagenes ni BD.
 * Impacto: CMS; SELECT y lectura de archivos exclusivamente, sin DDL.
 */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$_SERVER['SERVER_NAME'] = 'panel.com.local';
$_SERVER['HTTP_HOST'] = 'panel.com.local';
chdir(__DIR__ . '/../../public');
require_once '../app/iniciador.php';
require_once '../app/modelos/EcommerceCatalogoPublico.php';
$fallos = array(); $total = 0; $usados = 0; $formatos = array(); $offset = 0;
try {
  $modelo = new EcommerceCatalogoPublico();
  $conexion = new ReflectionMethod($modelo, 'getConexion');
  $conexion->setAccessible(true);
  $db = $conexion->invoke($modelo);
  if (!$db) throw new Exception('conexion');
  $columnas = $db->query('SHOW COLUMNS FROM erp_ecommerce_media_archivos')->fetchAll(PDO::FETCH_COLUMN);
  foreach (array('nombre_original', 'mime', 'bytes', 'ancho', 'alto', 'hash_sha256', 'metadata_json', 'actualizado_por', 'fecha_actualizacion') as $campo) {
    if (!in_array($campo, $columnas, true)) $fallos[] = 'columna_' . $campo;
  }
  do {
    $res = $modelo->mediaAdminListarInterno(array('limite' => 3, 'offset' => $offset, 'orden' => 'peso'));
    if (!empty($res['error']) || empty($res['depurar']['persistencia_real'])) throw new Exception('listado');
    foreach ($res['depurar']['items'] as $item) {
      $total++;
      if (strpos($item['preview_url'], $item['url'] . '?v=') !== 0) $fallos[] = 'preview';
      if (!isset($item['nombre_seo']) || !is_array($item['urls_anteriores'] ?? null)) $fallos[] = 'contrato_nombre_alias';
      $usos = $modelo->mediaAdminUsosInterno(array('id_media_archivo' => $item['id_media_archivo']));
      if (!empty($usos['error'])) $fallos[] = 'referencias_' . $item['id_media_archivo'];
      elseif ($usos['depurar']['total'] > 0) $usados++;
      $ruta = CmsMediaArchivo::ruta($item['url']);
      if (is_file($ruta)) {
        $validado = CmsMediaArchivo::inspeccionar($ruta, $item['nombre_archivo']);
        $formatos[$validado['extension']] = true;
      } else $fallos[] = 'archivo_ausente_' . $item['id_media_archivo'];
    }
    $offset += count($res['depurar']['items']);
  } while (!empty($res['depurar']['hay_mas']));
  if ($total !== $res['depurar']['total']) $fallos[] = 'paginacion_total';
} catch (Throwable $e) { $fallos[] = 'consulta_o_validacion_fallida'; }
echo json_encode(array('ok' => !$fallos, 'solo_lectura' => true, 'imagenes' => $total, 'imagenes_con_usos' => $usados, 'formatos_validados' => array_keys($formatos), 'fallos' => $fallos), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
exit($fallos ? 1 : 0);
