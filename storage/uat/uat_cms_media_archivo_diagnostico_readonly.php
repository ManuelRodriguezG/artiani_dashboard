<?php
/**
 * IA: Codex GPT-6 | Fecha: 2026-09-25
 * Proposito: distinguir un medio ausente, privado o distinto al registrado en el servidor actual.
 * Impacto: diagnostico CMS en XAMPP/cPanel, sin subir, reparar ni modificar BD/archivos.
 * Contrato: CLI --host=HOST --ruta=/assets/media/cms/ecommerce/ARCHIVO; solo SELECT y lecturas.
 */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$opciones = getopt('', array('host:', 'ruta:'));
$host = $opciones['host'] ?? '';
$entrada = $opciones['ruta'] ?? '';
if (!preg_match('/\A[a-z0-9.-]+\z/iD', $host) || strpos($entrada, '/assets/media/cms/ecommerce/') !== 0) {
  fwrite(STDERR, "Uso: php storage/uat/uat_cms_media_archivo_diagnostico_readonly.php --host=HOST --ruta=/assets/media/cms/ecommerce/ARCHIVO\n");
  exit(2);
}
$_SERVER['SERVER_NAME'] = $host;
$_SERVER['HTTP_HOST'] = $host;
chdir(__DIR__ . '/../../public');
require_once '../app/iniciador.php';
require_once '../app/modelos/EcommerceCatalogoPublico.php';
$resultado = array('solo_lectura' => true, 'host_configurado' => $host);
try {
  // La version del preview nunca forma parte del nombre fisico.
  $ruta = parse_url($entrada, PHP_URL_PATH);
  if (!is_string($ruta) || !CmsMediaAlias::rutas(array('url' => $ruta))) throw new Exception('ruta_invalida');
  $resultado['ruta_publica'] = $ruta;
  $archivo = CmsMediaArchivo::ruta($ruta);
  clearstatcache(true, $archivo);
  $existe = is_file($archivo);
  $legible = $existe && is_readable($archivo);
  $modo = $existe ? @fileperms($archivo) : false;
  $posix = DIRECTORY_SEPARATOR !== '\\';
  $resultado['archivo'] = array(
    'existe' => $existe, 'legible_por_php' => $legible,
    'permisos_posix' => $posix && $modo !== false ? sprintf('%04o', $modo & 0777) : null,
    'lectura_publica_posix' => $posix && $modo !== false ? ($modo & 0044) === 0044 : null,
    'bytes' => $existe ? filesize($archivo) : null
  );
  $modelo = new EcommerceCatalogoPublico();
  $conexion = new ReflectionMethod($modelo, 'getConexion');
  $conexion->setAccessible(true);
  $db = $conexion->invoke($modelo);
  if (!$db) throw new Exception('conexion_no_disponible');
  $stmt = $db->prepare('SELECT id_media_archivo, bytes, hash_sha256 FROM erp_ecommerce_media_archivos WHERE ruta_publica=:ruta');
  $stmt->execute(array(':ruta' => $ruta));
  $fila = $stmt->fetch(PDO::FETCH_ASSOC);
  $resultado['registro_actual'] = $fila ? array(
    'id_media_archivo' => (int) $fila['id_media_archivo'],
    'bytes_coinciden' => $existe ? (int) $fila['bytes'] === filesize($archivo) : false,
    'hash_coincide' => $legible ? hash_equals((string) $fila['hash_sha256'], hash_file('sha256', $archivo)) : false
  ) : null;
  $destino = CmsMediaAlias::resolver($db, $ruta);
  $resultado['alias_hacia'] = $destino && $destino['url'] !== $ruta ? $destino['url'] : null;
  $resultado['nota'] = 'La lectura por PHP no garantiza acceso por HTTP. Comparar la URL con y sin ?v y revisar permisos/reglas del servidor; una respuesta de cache no confirma el archivo en origen.';
  $resultado['ok'] = true;
} catch (Throwable $error) {
  $resultado['ok'] = false;
  $resultado['error'] = 'No se pudo completar el diagnostico. Verifica host, ruta, carpeta y acceso de lectura a la BD del ambiente.';
}
echo json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
exit($resultado['ok'] ? 0 : 1);
