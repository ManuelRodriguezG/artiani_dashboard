<?php
/**
 * IA: Codex GPT-6 | Fecha: 2026-09-25
 * Proposito: probar altas y duplicados Media con validacion fisica antes de insertar.
 * Impacto: regresion de cargas; ejecuta los metodos reales con BD simulada y archivos exclusivos.
 * Contrato: solo CLI, sin bootstrap, conexion real ni archivos de la biblioteca; limpia sus fixtures.
 */
namespace {
  if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
  require_once __DIR__ . '/../../app/core/CmsMediaArchivo.php';
  require_once __DIR__ . '/../../app/core/CmsMediaNombre.php';
}

namespace CmsMediaUploadUat {
  use \CmsMediaNombre;

  /** IA: Codex GPT-6 | Fecha: 2026-09-25 | Estado aislado, sin objetos ni rutas de produccion. */
  class Escenario {
    public static $dir;
    public static $db;
    public static $fallos = array();
    public static $eventos = array();
  }

  /** IA: Codex GPT-6 | Fecha: 2026-09-25 | Falla la suite ante un contrato incumplido. */
  function exigir($condicion, $mensaje) {
    if (!$condicion) throw new \RuntimeException($mensaje);
  }

  /** IA: Codex GPT-6 | Fecha: 2026-09-25 | Impide cualquier escritura fuera de la carpeta temporal propia. */
  function exigirRutaFixture($ruta) {
    $raiz = realpath(Escenario::$dir);
    $padre = realpath(\dirname($ruta));
    exigir($raiz !== false && $padre !== false && ($padre === $raiz || strpos($padre, $raiz . DIRECTORY_SEPARATOR) === 0), 'Ruta fuera del fixture');
    exigir(!is_link($ruta), 'No se permiten enlaces en fixtures');
  }

  /** IA: Codex GPT-6 | Fecha: 2026-09-25 | Simula move de upload e inyecta danos posteriores a la inspeccion inicial. */
  function move_uploaded_file($origen, $destino) {
    exigirRutaFixture($origen); exigirRutaFixture($destino);
    if (!empty(Escenario::$fallos['move'])) return false;
    if (!\rename($origen, $destino)) return false;
    if (!empty(Escenario::$fallos['truncado'])) file_put_contents($destino, substr(file_get_contents($destino), 0, -1));
    if (!empty(Escenario::$fallos['hash_distinto'])) {
      $bytes = file_get_contents($destino);
      $bytes[strlen($bytes) - 1] = chr(ord($bytes[strlen($bytes) - 1]) ^ 1);
      file_put_contents($destino, $bytes);
    }
    return true;
  }

  /** IA: Codex GPT-6 | Fecha: 2026-09-25 | El rollback solo puede retirar archivos del fixture actual. */
  function unlink($ruta) {
    exigirRutaFixture($ruta);
    return \unlink($ruta);
  }

  /** IA: Codex GPT-6 | Fecha: 2026-09-25 | Conexion minima sin PDO real; conserva evidencia de INSERT. */
  class Conexion {
    public $item = null;
    public $intentosInsert = 0;
    public $insertados = 0;
    public function prepare($sql) {
      exigir(strpos(ltrim($sql), 'INSERT INTO erp_ecommerce_media_archivos') === 0, 'SQL no esperado en alta');
      if (!empty(Escenario::$fallos['prepare_false'])) return false;
      return new Consulta($this);
    }
    /** IA: Codex GPT-6 | Fecha: 2026-09-25 | Simula fallo de identificacion posterior a un INSERT confirmado. */
    public function lastInsertId() {
      if (!empty(Escenario::$fallos['last_id'])) throw new \PDOException('Identificador no disponible simulado');
      return '81';
    }
  }

  /** IA: Codex GPT-6 | Fecha: 2026-09-25 | Persiste solo memoria y exige verificacion previa al INSERT. */
  class Consulta {
    private $db;
    public function __construct($db) { $this->db = $db; }
    public function execute($params) {
      $this->db->intentosInsert++;
      exigir(in_array('verificacion_correcta', Escenario::$eventos, true), 'Se intento insertar antes de comprobar archivo final');
      if (!empty(Escenario::$fallos['insert'])) throw new \PDOException('INSERT simulado');
      if (!empty(Escenario::$fallos['insert_false'])) return false;
      $this->db->item = array(
        'id_media_archivo' => 81, 'codigo' => $params[':codigo'], 'url' => $params[':ruta'],
        'nombre_archivo' => $params[':archivo'], 'nombre_original' => $params[':original'],
        'mime' => $params[':mime'], 'extension' => $params[':extension'], 'bytes' => $params[':bytes'],
        'hash_sha256' => $params[':hash'], 'alt' => $params[':alt'], 'estatus' => 'activo'
      );
      $this->db->insertados++;
      return true;
    }
  }

  /** IA: Codex GPT-6 | Fecha: 2026-09-25 | Mapea rutas Media a fixtures y usa inspeccion/verificacion reales. */
  class CmsMediaArchivo {
    public static function ruta($url) {
      if (!empty(Escenario::$fallos['diagnostico']) && Escenario::$db->insertados) throw new \Exception('Diagnostico inaccesible simulado');
      exigir(preg_match('~\A/assets/media/cms/ecommerce/[A-Za-z0-9_.-]+\.png\z~D', $url) === 1 && strpos($url, '..') === false, 'URL inesperada');
      return Escenario::$dir . '/public/assets/media/cms/ecommerce/' . basename($url);
    }
    public static function inspeccionar($ruta, $nombre) {
      exigirRutaFixture($ruta);
      return \CmsMediaArchivo::inspeccionar($ruta, $nombre);
    }
    public static function asegurarLecturaPublica($ruta) {
      exigirRutaFixture($ruta);
      return empty(Escenario::$fallos['permisos_carga']) && \CmsMediaArchivo::asegurarLecturaPublica($ruta);
    }
    public static function verificarGuardado($ruta, $bytes, $hash) {
      exigirRutaFixture($ruta);
      $informe = \CmsMediaArchivo::verificarGuardado($ruta, $bytes, $hash);
      // Simula POSIX restringido tambien en Windows; la suite del helper cubre sus bits reales.
      if (!empty(Escenario::$fallos['duplicado_privado'])) {
        $informe = array_merge($informe, array('ok' => false, 'estado' => 'permisos_restringidos',
          'permisos_publicos' => false, 'permisos' => '0600', 'mensaje' => 'El archivo tiene permisos restringidos (0600).'));
      }
      Escenario::$eventos[] = $informe['ok'] ? 'verificacion_correcta' : 'verificacion_fallida';
      return $informe;
    }
  }

  /**
   * IA: Codex GPT-6 | Fecha: 2026-09-25
   * Proposito: ejecutar el metodo actual sin cargar el modelo completo ni su conexion.
   * Contrato: tokeniza llaves PHP; cambia unicamente __DIR__ por la raiz del fixture para almacenamiento.
   */
  function extraerMetodo($ruta, $nombre, $visibilidad) {
    $tokens = token_get_all(file_get_contents($ruta));
    $total = count($tokens);
    for ($i = 0; $i < $total; $i++) {
      if (!is_array($tokens[$i]) || $tokens[$i][0] !== T_FUNCTION) continue;
      $j = $i + 1;
      while ($j < $total && is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) $j++;
      if (!isset($tokens[$j]) || !is_array($tokens[$j]) || $tokens[$j][0] !== T_STRING || $tokens[$j][1] !== $nombre) continue;
      $codigo = $visibilidad . ' '; $nivel = 0; $abierto = false;
      for ($k = $i; $k < $total; $k++) {
        $token = $tokens[$k];
        $codigo .= is_array($token) ? ($token[0] === T_DIR ? "(Escenario::\$dir . '/app/modelos')" : $token[1]) : $token;
        if ($token === '{') { $nivel++; $abierto = true; }
        if ($token === '}' && --$nivel === 0 && $abierto) return $codigo;
      }
    }
    throw new \RuntimeException('No se encontro el metodo real ' . $nombre);
  }

  // IA: Codex GPT-6 | 2026-09-25 | Los dos metodos vienen del codigo actual; las dependencias son minimas y aisladas.
  $alta = extraerMetodo(__DIR__ . '/../../app/modelos/EcommerceCatalogoPublico.php', 'mediaAdminSubirInterno', 'public');
  $diagnostico = extraerMetodo(__DIR__ . '/../../app/modelos/EcommerceMediaGestion.php', 'mediaAdjuntarValidacionArchivo', 'private');
  eval('namespace CmsMediaUploadUat; use \\Throwable; use \\Exception; use \\PDOException; use \\CmsMediaNombre; trait MetodosReales {' . $alta . $diagnostico . '}');

  /** IA: Codex GPT-6 | Fecha: 2026-09-25 | Adaptador de respuestas y consultas sin acceso a BD. */
  class Modelo {
    use MetodosReales;
    private function getConexion() { return Escenario::$db; }
    private function tablaExiste($db, $tabla) { return true; }
    private function valor($datos, $clave, $default) { return $datos[$clave] ?? $default; }
    private function respuesta($error, $tipo, $mensaje, $depurar) { return compact('error', 'tipo', 'mensaje', 'depurar'); }
    private function mediaValorPermitido($valor, $permitidos, $default) { return in_array($valor, $permitidos, true) ? $valor : $default; }
    private function mediaValidarArchivoUpload($archivo) {
      exigir($archivo['error'] === UPLOAD_ERR_OK && is_file($archivo['tmp_name']), 'Fixture de upload invalido');
      exigirRutaFixture($archivo['tmp_name']);
    }
    private function mediaBuscarPorHash($db, $hash) { return $db->item && $db->item['hash_sha256'] === $hash ? $db->item : null; }
    /** IA: Codex GPT-6 | Fecha: 2026-09-25 | Inyecta errores de lectura despues de guardar para comprobar el recibo conservado. */
    private function mediaBuscarPorId($db, $id) {
      if (!empty(Escenario::$fallos['consulta'])) throw new \PDOException('Consulta posterior al INSERT simulada');
      if (!empty(Escenario::$fallos['consulta_vacia'])) return null;
      return $db->item && $db->item['id_media_archivo'] === $id ? $db->item : null;
    }
  }

  /** IA: Codex GPT-6 | Fecha: 2026-09-25 | Limpieza limitada a archivos/directorios reales bajo raiz temporal verificada. */
  function limpiarFixture($directorio) {
    $raiz = realpath(Escenario::$dir);
    $actual = realpath($directorio);
    exigir($raiz !== false && $actual !== false && ($actual === $raiz || strpos($actual, $raiz . DIRECTORY_SEPARATOR) === 0), 'Limpieza fuera del fixture');
    foreach (scandir($actual) as $nombre) {
      if ($nombre === '.' || $nombre === '..') continue;
      $ruta = $actual . DIRECTORY_SEPARATOR . $nombre;
      exigir(!is_link($ruta), 'Enlace inesperado en limpieza');
      if (is_dir($ruta)) { limpiarFixture($ruta); rmdir($ruta); }
      else { exigirRutaFixture($ruta); \unlink($ruta); }
    }
  }

  Escenario::$dir = sys_get_temp_dir() . '/cms_media_upload_uat_' . bin2hex(random_bytes(8));
  exigir(mkdir(Escenario::$dir, 0700), 'No se pudo crear fixture');
  $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aGXkAAAAASUVORK5CYII=');
  $casos = array(
    array('alta verificada', array(), null, 'success', false, 1),
    array('alta truncada antes del INSERT', array('truncado' => true), null, 'danger', true, 0),
    array('alta con hash cambiado antes del INSERT', array('hash_distinto' => true), null, 'danger', true, 0),
    array('alta sin permisos publicos', array('permisos_carga' => true), null, 'danger', true, 0),
    array('alta con fallo al mover', array('move' => true), null, 'danger', true, 0),
    array('alta con fallo INSERT limpia destino', array('insert' => true), null, 'danger', true, 0),
    array('alta con prepare false limpia destino', array('prepare_false' => true), null, 'danger', true, 0),
    array('alta con execute false limpia destino', array('insert_false' => true), null, 'danger', true, 0),
    array('lectura postinsert falla y conserva recibo', array('consulta' => true), null, 'warning', false, 1),
    array('lectura postinsert vacia conserva recibo', array('consulta_vacia' => true), null, 'warning', false, 1),
    array('lastInsertId falla y conserva archivo guardado', array('last_id' => true), null, 'warning', false, 1),
    array('diagnostico posterior fallido conserva alta', array('diagnostico' => true), null, 'warning', false, 1),
    array('duplicado verificado sin otra alta', array(), 'correcto', 'info', false, 0),
    array('duplicado ausente conserva ficha con aviso', array(), 'ausente', 'warning', false, 0),
    array('duplicado privado con aviso', array('duplicado_privado' => true), 'correcto', 'warning', false, 0),
    array('duplicado con contenido cambiado', array(), 'contenido_distinto', 'warning', false, 0),
    array('duplicado archivado no reutilizado', array(), 'archivado', 'danger', true, 0)
  );
  try {
    foreach ($casos as [$titulo, $fallos, $duplicado, $tipo, $error, $insertados]) {
      limpiarFixture(Escenario::$dir);
      $publico = Escenario::$dir . '/public/assets/media/cms/ecommerce';
      exigir(mkdir($publico, 0700, true), 'No se pudo crear destino de fixture');
      Escenario::$fallos = $fallos; Escenario::$eventos = array(); Escenario::$db = new Conexion();
      $entrada = Escenario::$dir . '/entrada.png'; file_put_contents($entrada, $png);
      if ($duplicado !== null) {
        Escenario::$db->item = array('id_media_archivo' => 29, 'codigo' => 'media_fixture',
          'url' => '/assets/media/cms/ecommerce/existente.png', 'bytes' => strlen($png),
          'hash_sha256' => hash('sha256', $png), 'estatus' => $duplicado === 'archivado' ? 'archivado' : 'activo');
        if ($duplicado !== 'ausente') {
          $bytesExistentes = $duplicado === 'contenido_distinto' ? substr($png, 0, -1) . chr(ord(substr($png, -1)) ^ 1) : $png;
          file_put_contents($publico . '/existente.png', $bytesExistentes);
          chmod($publico . '/existente.png', 0644);
        }
      }
      $respuesta = (new Modelo())->mediaAdminSubirInterno(array('error' => UPLOAD_ERR_OK, 'tmp_name' => $entrada, 'name' => 'Logotipo.png'), array('alt' => 'Logotipo de prueba', 'nombre_seo' => 'logotipo-prueba'), 7);
      exigir($respuesta['error'] === $error && $respuesta['tipo'] === $tipo, $titulo . ': respuesta incorrecta ' . json_encode($respuesta));
      exigir(Escenario::$db->insertados === $insertados, $titulo . ': INSERT inesperado');
      exigir(strpos(json_encode($respuesta), str_replace('\\', '\\\\', Escenario::$dir)) === false, $titulo . ': expone ruta privada');
      if ($duplicado !== null) {
        exigir(Escenario::$db->intentosInsert === 0 && Escenario::$db->item['id_media_archivo'] === 29, $titulo . ': duplico o altero registro');
        exigir(is_file($entrada), $titulo . ': movio una carga duplicada');
        exigir(count(glob($publico . '/*')) === ($duplicado === 'ausente' ? 0 : 1), $titulo . ': archivo extra');
      } elseif ($error) {
        exigir(Escenario::$db->item === null && count(glob($publico . '/*')) === 0, $titulo . ': registro o destino huerfano');
        exigir(Escenario::$db->intentosInsert === (!empty($fallos['insert']) || !empty($fallos['insert_false']) ? 1 : 0), $titulo . ': intento INSERT antes de validar');
      } else {
        $guardado = $publico . '/' . basename($respuesta['depurar']['url']);
        exigir(is_file($guardado) && file_get_contents($guardado) === $png && count(glob($publico . '/*')) === 1, $titulo . ': archivo guardado incorrecto');
        exigir($respuesta['depurar']['id_media_archivo'] === (!empty($fallos['last_id']) ? 0 : 81) && Escenario::$db->intentosInsert === 1, $titulo . ': perdio ficha confirmada');
        exigir($respuesta['depurar']['codigo'] === Escenario::$db->item['codigo'] && $respuesta['depurar']['url'] === Escenario::$db->item['url'] && $respuesta['depurar']['hash_sha256'] === hash('sha256', $png), $titulo . ': recibo no permite identificar la carga real');
        if (!empty($fallos['consulta']) || !empty($fallos['consulta_vacia']) || !empty($fallos['last_id'])) exigir(strpos($respuesta['mensaje'], 'no vuelvas a subirla') !== false, $titulo . ': no informa del guardado confirmado');
      }
      if (!$error) {
        $diagnostico = $respuesta['depurar']['validacion_archivo'];
        $estado = !empty($fallos['diagnostico']) ? 'no_verificable' : (!empty($fallos['duplicado_privado']) ? 'permisos_restringidos' : ($duplicado === 'ausente' ? 'ausente' : ($duplicado === 'contenido_distinto' ? 'contenido_distinto' : 'verificado')));
        exigir($diagnostico['estado'] === $estado && $diagnostico['ok'] === ($estado === 'verificado'), $titulo . ': diagnostico incorrecto');
        if ($estado === 'verificado') exigir($diagnostico['bytes_coinciden'] && $diagnostico['hash_coincide'] && $diagnostico['existe'] && $diagnostico['legible'], $titulo . ': verificacion incompleta');
        if ($estado === 'contenido_distinto') exigir($diagnostico['bytes_coinciden'] && !$diagnostico['hash_coincide'], $titulo . ': no detecto hash distinto con igual peso');
      }
      echo 'PASS ' . $titulo . PHP_EOL;
    }
    echo 'OK: ' . count($casos) . ' escenarios de alta Media aislados; sin BD ni medios reales.' . PHP_EOL;
  } finally {
    limpiarFixture(Escenario::$dir);
    rmdir(Escenario::$dir);
  }
}
