<?php
/**
 * IA: Codex GPT-6 | Fecha: 2026-09-24
 * Proposito: reproducir fallos de archivos y transacciones sin BD ni medios reales.
 * Impacto: regresion de reemplazos WebP, nombres SEO, aliases, bajas y validacion ICO/AVIF.
 * Contrato: ejecutar por CLI; PDO simulado y fixtures exclusivos en un directorio temporal nuevo.
 */
namespace {
  if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
  require_once __DIR__ . '/../../app/core/CmsMediaArchivo.php';
  require_once __DIR__ . '/../../app/core/CmsMediaNombre.php';
  require_once __DIR__ . '/../../app/core/CmsMediaAlias.php';
}

namespace CmsMediaUat {
  use PDO;
  use PDOStatement;
  use Throwable;
  use \CmsMediaNombre;
  use \CmsMediaAlias;

  /** IA: Codex GPT-6 | Fecha: 2026-09-24 | Estado exclusivo de fixtures y fallos simulados. */
  class Escenario {
    public static $dir;
    public static $fallos = array();
    public static $eventos = array();
    public static $db;
    public static $accion;
    public static $duplicado;
    public static $cabeceras = array();
    public static $estadoHttp = 200;
  }

  /** IA: Codex GPT-6 | Fecha: 2026-09-24 | Simula upload valido solo dentro de esta copia de prueba del trait. */
  function move_uploaded_file($origen, $destino) { return \rename($origen, $destino); }
  /** IA: Codex GPT-6 | Fecha: 2026-09-24 | Inyecta fallo de copia sin alterar archivos ajenos al fixture. */
  function copy($origen, $destino) { return empty(Escenario::$fallos['copia']) && \copy($origen, $destino); }
  /** IA: Codex GPT-6 | Fecha: 2026-09-24 | Registra avisos de fallos inyectados sin escribir logs productivos. */
  function error_log($mensaje) { Escenario::$eventos[] = array('log', true); return true; }
  /** IA: Codex GPT-6 | Fecha: 2026-09-24 | Inyecta fallos de retiro, reemplazo o restauracion y registra el bloqueo. */
  function rename($origen, $destino) {
    $esRetiro = basename($origen) === 'imagen.png';
    $esRestauracion = strpos(basename($origen), 'resguardo_') === 0 && basename($destino) === 'imagen.png';
    $tipo = $esRetiro ? 'retiro' : ($esRestauracion ? 'restauracion' : 'reemplazo');
    Escenario::$eventos[] = array($tipo, Escenario::$db->inTransaction());
    if (!empty(Escenario::$fallos[$tipo])) return false;
    return \rename($origen, $destino);
  }
  /** IA: Codex GPT-6 | Fecha: 2026-09-24 | Prueba fallo de retiro del nombre anterior durante cambio de formato. */
  function unlink($ruta) {
    if (Escenario::$accion === 'reemplazar' && basename($ruta) === 'imagen.png') {
      Escenario::$eventos[] = array('retiro', Escenario::$db->inTransaction());
      if (!empty(Escenario::$fallos['retiro'])) return false;
    }
    return \unlink($ruta);
  }
  /** IA: Codex GPT-6 | Fecha: 2026-09-24 | Captura respuesta HTTP de controlador sin emitir cabeceras reales. */
  function header($cabecera, $reemplazar = true, $codigo = 0) {
    Escenario::$cabeceras[] = $cabecera;
    if ($codigo) Escenario::$estadoHttp = $codigo;
  }
  /** IA: Codex GPT-6 | Fecha: 2026-09-24 | Captura estado publico en CLI, independiente de la respuesta del proceso. */
  function http_response_code($codigo = null) {
    if ($codigo !== null) Escenario::$estadoHttp = $codigo;
    return Escenario::$estadoHttp;
  }

  /** IA: Codex GPT-6 | Fecha: 2026-09-24 | PDO sin conexion real, con snapshot transaccional. */
  class Conexion extends PDO {
    public $item;
    private $copia;
    private $transaccion = false;
    public function __construct($item) { $this->item = $item; }
    public function beginTransaction(): bool { $this->copia = $this->item; $this->transaccion = true; return true; }
    public function inTransaction(): bool { return $this->transaccion; }
    public function prepare($query, $options = array()): PDOStatement|false { return new Consulta($this, $query); }
    public function commit(): bool {
      if (!empty(Escenario::$fallos['commit'])) return false;
      $this->transaccion = false; return true;
    }
    public function rollBack(): bool {
      Escenario::$eventos[] = array('rollback', $this->transaccion);
      $this->item = $this->copia; $this->transaccion = false;
      if (!empty(Escenario::$fallos['rollback'])) throw new \PDOException('Rollback simulado');
      return true;
    }
  }

  /** IA: Codex GPT-6 | Fecha: 2026-09-24 | Simula UPDATE/DELETE y fallos false/exception de PDO. */
  class Consulta extends PDOStatement {
    private $db;
    private $sql;
    private $leida = false;
    public function __construct($db, $sql) { $this->db = $db; $this->sql = $sql; }
    public function execute(?array $params = null): bool {
      if (strpos($this->sql, 'UPDATE ') === 0 || strpos($this->sql, 'DELETE ') === 0) {
        if (!empty(Escenario::$fallos['sql'])) return false;
        if (!empty(Escenario::$fallos['excepcion_sql'])) throw new \PDOException('SQL simulado');
      }
      if (strpos($this->sql, 'DELETE ') === 0) $this->db->item = null;
      if (strpos($this->sql, 'UPDATE ') === 0) {
        preg_match_all('/([a-z_]+)\s*=\s*(:[a-z_]+)/i', $this->sql, $asignaciones, PREG_SET_ORDER);
        foreach ($asignaciones as $asignacion) {
          if ($asignacion[1] !== 'id_media_archivo') $this->db->item[$asignacion[1]] = $params[$asignacion[2]];
        }
        if (isset($this->db->item['ruta_publica'])) $this->db->item['url'] = $this->db->item['ruta_publica'];
      }
      return true;
    }
    /** IA: Codex GPT-6 | Fecha: 2026-09-24 | Entrega metadata bloqueada desde el snapshot simulado. */
    public function fetchColumn(int $column = 0): mixed { return $this->db->item['metadata_json'] ?? null; }
    /** IA: Codex GPT-6 | Fecha: 2026-09-24 | Iteracion de una sola fila permite ejecutar resolver real sin BD. */
    public function fetch(int $mode = PDO::FETCH_DEFAULT, int $cursorOrientation = PDO::FETCH_ORI_NEXT, int $cursorOffset = 0): mixed {
      if ($this->leida) return false;
      $this->leida = true;
      return $this->db->item ?: false;
    }
    /** IA: Codex GPT-6 | Fecha: 2026-09-24 | Reporta finalizacion normal de lectura simulada. */
    public function errorCode(): ?string { return '00000'; }
  }

  /** IA: Codex GPT-6 | Fecha: 2026-09-24 | Redirige solo la ruta de archivos hacia el fixture. */
  class CmsMediaArchivo {
    public static function ruta($url) {
      exigir(strpos($url, '/assets/media/cms/ecommerce/') === 0 && strpos($url, '..') === false, 'Ruta Media fuera del fixture');
      return Escenario::$dir . '/' . basename($url);
    }
    public static function inspeccionar($ruta, $nombre) { return \CmsMediaArchivo::inspeccionar($ruta, $nombre); }
  }
  /** IA: Codex GPT-6 | Fecha: 2026-09-24 | Simula dependencias sin consultar las tablas del CMS. */
  class CmsMediaReferencias {
    public static function usos($db, $item, $bloquear = false) { return !empty(Escenario::$fallos['referencias']) ? array(array('origen'=>'CMS','referencia'=>'Fixture','estado'=>'borrador')) : array(); }
  }

  // La copia de prueba cambia solo el namespace para interceptar operaciones; ejecuta el trait actual.
  $codigo = file_get_contents(__DIR__ . '/../../app/modelos/EcommerceMediaGestion.php');
  $codigo = preg_replace('/^<\?php\s*/', '', $codigo);
  eval('namespace CmsMediaUat; use \\Throwable; use \\Exception; use \\PDOException; use \\CmsMediaNombre; use \\CmsMediaAlias; ' . $codigo);

  /** IA: Codex GPT-6 | Fecha: 2026-09-24 | Adaptador minimo del modelo, sin bootstrap ni configuracion BD. */
  class Modelo {
    use EcommerceMediaGestion;
    private $temporales = 0;
    private function getConexion() { return Escenario::$db; }
    private function valor($datos, $key, $default) { return $datos[$key] ?? $default; }
    private function respuesta($error, $tipo, $mensaje, $depurar) { return compact('error','tipo','mensaje','depurar'); }
    private function mediaBuscarPorId($db, $id) {
      if (!$db->item) return null;
      $item = $db->item;
      $metadata = json_decode($item['metadata_json'] ?? '{}', true) ?: array();
      $item['urls_anteriores'] = $metadata['rutas_anteriores'] ?? array();
      $item['nombre_seo'] = $metadata['nombre_seo'] ?? \CmsMediaNombre::sugerir($item);
      $item['alt'] = $item['alt_text'];
      return $item;
    }
    private function mediaBuscarPorHash($db, $hash) {
      if (Escenario::$duplicado && Escenario::$duplicado['hash_sha256'] === $hash) return Escenario::$duplicado;
      return $db->item && $db->item['hash_sha256'] === $hash ? $db->item : null;
    }
    private function mediaValidarArchivoUpload($archivo) { if (!is_file($archivo['tmp_name'])) throw new \Exception('Fixture inexistente'); }
    private function mediaTemporalPrivado() {
      // Windows recorta prefijos tempnam: nombres propios identifican cada operacion simulada.
      $prefijo = Escenario::$accion === 'reemplazar' && $this->temporales++ === 0 ? 'staging_' : 'resguardo_';
      $ruta = Escenario::$dir . '/' . $prefijo . bin2hex(random_bytes(8));
      $archivo = fopen($ruta, 'x');
      if (!$archivo) throw new \RuntimeException('No se pudo crear temporal de fixture');
      fclose($archivo);
      return $ruta;
    }
    public function eliminar() { return $this->mediaGestionEliminar(array('id_media_archivo'=>17), 1); }
  }
  /** IA: Codex GPT-6 | Fecha: 2026-09-24 | Conecta controlador publico real con modelo de prueba, sin bootstrap ni sesion. */
  class Controlador {
    public function modelo($nombre) { return new Modelo(); }
  }
  $controladorCodigo = file_get_contents(__DIR__ . '/../../app/controladores/EcommercePublico.php');
  eval('namespace CmsMediaUat; ' . preg_replace('/^<\?php\s*/', '', $controladorCodigo));

  /** IA: Codex GPT-6 | Fecha: 2026-09-24 | Aserciones operativas con fallo inmediato de UAT. */
  function exigir($condicion, $mensaje) { if (!$condicion) throw new \RuntimeException($mensaje); }
  /** IA: Codex GPT-6 | Fecha: 2026-09-24 | Datos editoriales y tecnicos minimos que deben sobrevivir los reemplazos. */
  function itemFixture($png) {
    return array('id_media_archivo'=>17, 'url'=>'/assets/media/cms/ecommerce/imagen.png',
      'ruta_publica'=>'/assets/media/cms/ecommerce/imagen.png', 'codigo'=>'media_fixture', 'extension'=>'png',
      'nombre_archivo'=>'imagen.png', 'nombre_original'=>'original.png', 'mime'=>'image/png', 'alt_text'=>'Imagen de prueba',
      'bytes'=>strlen($png), 'ancho'=>1, 'alto'=>1, 'hash_sha256'=>hash('sha256',$png),
      'metadata_json'=>json_encode(array('editorial'=>array('autor'=>'fixture','orden'=>3)), JSON_UNESCAPED_SLASHES));
  }
  /** IA: Codex GPT-6 | Fecha: 2026-09-24 | Solo retira archivos directos creados dentro del fixture verificado. */
  function limpiarFixture() {
    $base = realpath(Escenario::$dir);
    if (!$base || strpos($base, realpath(sys_get_temp_dir()) . DIRECTORY_SEPARATOR) !== 0 || strpos(basename($base), 'cms_media_uat_') !== 0) throw new \RuntimeException('Directorio de fixture fuera del temporal autorizado');
    foreach (scandir($base) as $nombre) {
      if ($nombre === '.' || $nombre === '..') continue;
      $ruta = $base . DIRECTORY_SEPARATOR . $nombre;
      exigir(is_file($ruta) && !is_link($ruta) && dirname(realpath($ruta)) === $base, 'Archivo de fixture fuera de alcance');
      \unlink($ruta);
    }
  }

  Escenario::$dir = sys_get_temp_dir() . '/cms_media_uat_' . bin2hex(random_bytes(8));
  mkdir(Escenario::$dir, 0700);
  $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aYfkAAAAASUVORK5CYII=');
  $casos = array(
    array('baja: retiro falla', 'eliminar', array('retiro'=>true), false),
    array('baja: SQL false', 'eliminar', array('sql'=>true), false),
    array('baja: SQL exception', 'eliminar', array('excepcion_sql'=>true), false),
    array('baja: commit false', 'eliminar', array('commit'=>true), false),
    array('baja: conserva resguardo si restauracion falla', 'eliminar', array('sql'=>true,'restauracion'=>true), false),
    array('baja: tiene referencias', 'eliminar', array('referencias'=>true), false),
    array('baja exitosa', 'eliminar', array(), true),
    array('reemplazo: copia falla', 'reemplazar', array('copia'=>true), false),
    array('reemplazo: rename falla', 'reemplazar', array('reemplazo'=>true), false),
    array('reemplazo: SQL false', 'reemplazar', array('sql'=>true), false),
    array('reemplazo: commit false', 'reemplazar', array('commit'=>true), false),
    array('reemplazo: rollback lanza', 'reemplazar', array('sql'=>true,'rollback'=>true), false),
    array('reemplazo: conserva resguardo si restauracion falla', 'reemplazar', array('sql'=>true,'restauracion'=>true), false),
    array('reemplazo: recuperacion de archivo ausente falla', 'reemplazar', array('sql'=>true,'ausente'=>true), false),
    array('reemplazo exitoso', 'reemplazar', array(), true)
  );
  try {
    foreach ($casos as [$titulo, $accion, $fallos, $exito]) {
      limpiarFixture(); Escenario::$fallos = $fallos; Escenario::$eventos = array(); Escenario::$accion = $accion;
      if (empty($fallos['ausente'])) file_put_contents(Escenario::$dir . '/imagen.png', $png);
      file_put_contents(Escenario::$dir . '/nuevo.png', $png . 'fixture_nuevo');
      $item = itemFixture($png);
      Escenario::$db = new Conexion($item);
      $modelo = new Modelo();
      $respuesta = $accion === 'eliminar' ? $modelo->eliminar() : $modelo->mediaAdminReemplazarInterno(array('tmp_name'=>Escenario::$dir.'/nuevo.png','name'=>'nuevo.png','error'=>UPLOAD_ERR_OK), array('id_media_archivo'=>17), 1);
      exigir($respuesta['error'] === !$exito, $titulo . ': respuesta inesperada');
      if (!$exito) {
        if (!empty($fallos['restauracion'])) {
          $resguardos = glob(Escenario::$dir.'/resguardo_*');
          exigir(count($resguardos) === 1 && file_get_contents($resguardos[0]) === $png, $titulo . ': no conservo el original para recuperar');
        } elseif (!empty($fallos['ausente'])) {
          exigir(!file_exists(Escenario::$dir.'/imagen.png'), $titulo . ': archivo nuevo huerfano');
        } else { exigir(file_get_contents(Escenario::$dir.'/imagen.png') === $png, $titulo . ': original alterado'); }
        exigir(Escenario::$db->item === $item, $titulo . ': registro alterado');
      } elseif ($accion === 'eliminar') {
        exigir(!file_exists(Escenario::$dir.'/imagen.png') && Escenario::$db->item === null, $titulo . ': baja incompleta');
      } else {
        exigir(file_get_contents(Escenario::$dir.'/imagen.png') === $png.'fixture_nuevo', $titulo . ': no cambio archivo');
        exigir(Escenario::$db->item['url'] === $item['url'] && Escenario::$db->item['codigo'] === $item['codigo'], $titulo . ': referencias cambiaron');
      }
      foreach (Escenario::$eventos as [$evento,$bloqueado]) if ($evento === 'restauracion') exigir($bloqueado, $titulo . ': restauracion sin bloqueo');
      exigir(count(glob(Escenario::$dir.'/resguardo_*')) === (!empty($fallos['restauracion']) ? 1 : 0), $titulo . ': resguardos inesperados');
      exigir(count(glob(Escenario::$dir.'/staging_*')) === 0, $titulo . ': staging sin limpiar');
      echo 'OK ' . $titulo . PHP_EOL;
    }
    // IA: Codex GPT-6 | 2026-09-24 | Casos de formato/nombre con bytes reales y rollback de ruta/metadatos.
    $webp = base64_decode('UklGRiIAAABXRUJQVlA4IBYAAAAwAQCdASoBAAEADsD+JaQAA3AAAAAA');
    $casosCambio = array(
      array('WebP en imagen utilizada', array('referencias'=>true), true, true),
      array('WebP: copia falla', array('copia'=>true), false, true),
      array('WebP: instalacion falla', array('reemplazo'=>true), false, true),
      array('WebP: retiro anterior falla', array('retiro'=>true), false, true),
      array('WebP: SQL false', array('sql'=>true), false, true),
      array('WebP: SQL exception', array('excepcion_sql'=>true), false, true),
      array('WebP: commit false', array('commit'=>true), false, true),
      array('WebP: rollback lanza', array('sql'=>true,'rollback'=>true), false, true),
      array('WebP: restauracion falla y conserva respaldo', array('sql'=>true,'restauracion'=>true), false, true),
      array('WebP: original ausente y SQL falla', array('ausente'=>true,'sql'=>true), false, true),
      array('WebP: recupera original ausente', array('ausente'=>true), true, true),
      array('WebP: hash de otro registro', array('duplicado'=>true), false, true),
      array('WebP: rechaza PNG con extension WebP', array('formato_falso'=>true), false, true),
      array('WebP: metadata corrupta', array('metadata_invalida'=>true), false, true),
      array('Nombre: conserva formato y contenido', array(), true, false),
      array('Nombre: SQL falla', array('sql'=>true), false, false),
      array('Nombre: commit falla', array('commit'=>true), false, false),
      array('Nombre: exige archivo existente', array('ausente'=>true), false, false),
      array('Nombre: descripcion vacia', array('nombre_vacio'=>true), false, false)
    );
    foreach ($casosCambio as [$titulo, $fallos, $exito, $subir]) {
      limpiarFixture(); Escenario::$fallos = $fallos; Escenario::$eventos = array(); Escenario::$accion = 'reemplazar'; Escenario::$duplicado = null;
      if (empty($fallos['ausente'])) file_put_contents(Escenario::$dir.'/imagen.png', $png);
      file_put_contents(Escenario::$dir.'/nuevo.webp', empty($fallos['formato_falso']) ? $webp : $png);
      $item = itemFixture($png);
      $metadata = json_decode($item['metadata_json'], true);
      $metadata['rutas_anteriores'] = array('/assets/media/cms/ecommerce/antecesora.png');
      $item['metadata_json'] = empty($fallos['metadata_invalida']) ? json_encode($metadata, JSON_UNESCAPED_SLASHES) : '{';
      Escenario::$db = new Conexion($item);
      if (!empty($fallos['duplicado'])) Escenario::$duplicado = array('id_media_archivo'=>33, 'hash_sha256'=>hash('sha256',$webp));
      $datos = array('id_media_archivo'=>17, 'nombre_seo'=>!empty($fallos['nombre_vacio']) ? '---' : 'Pelota AZÚL para Niño.png');
      $archivo = $subir ? array('tmp_name'=>Escenario::$dir.'/nuevo.webp','name'=>'nuevo.webp','error'=>UPLOAD_ERR_OK) : null;
      $res = (new Modelo())->mediaAdminReemplazarInterno($archivo, $datos, 1);
      exigir($res['error'] === !$exito, $titulo . ': ' . $res['mensaje']);
      if ($exito) {
        $guardado = Escenario::$db->item;
        $extension = $subir ? 'webp' : 'png';
        exigir(preg_match('~^/assets/media/cms/ecommerce/pelota-azul-para-nino-17-[a-f0-9]{8}\.' . $extension . '$~', $guardado['url']) === 1, $titulo . ': nombre/url incorrecto');
        exigir($guardado['id_media_archivo'] === 17 && $guardado['codigo'] === $item['codigo'], $titulo . ': identidad modificada');
        exigir(!file_exists(Escenario::$dir.'/imagen.png'), $titulo . ': anterior aun sirve bytes viejos');
        $ruta = CmsMediaArchivo::ruta($guardado['url']);
        exigir(file_get_contents($ruta) === ($subir ? $webp : $png), $titulo . ': contenido incorrecto');
        exigir(\CmsMediaArchivo::inspeccionar($ruta, $guardado['nombre_archivo'])['extension'] === $extension, $titulo . ': formato real incorrecto');
        $despues = json_decode($guardado['metadata_json'], true);
        exigir($despues['editorial'] === $metadata['editorial'] && $guardado['alt_text'] === $item['alt_text'], $titulo . ': cambio metadata editorial');
        exigir(count($despues['rutas_anteriores']) === 2 && in_array($item['url'], $despues['rutas_anteriores'], true) && in_array('/assets/media/cms/ecommerce/antecesora.png', $res['depurar']['urls_anteriores'], true), $titulo . ': se perdio alias anterior');
      } else {
        exigir(Escenario::$db->item === $item, $titulo . ': registro o metadata alterado');
        if (!empty($fallos['restauracion'])) {
          $resguardos = glob(Escenario::$dir.'/resguardo_*');
          exigir(count($resguardos) === 1 && file_get_contents($resguardos[0]) === $png, $titulo . ': respaldo perdido');
        } elseif (empty($fallos['ausente'])) {
          exigir(is_file(Escenario::$dir.'/imagen.png') && file_get_contents(Escenario::$dir.'/imagen.png') === $png, $titulo . ': original perdido');
        } else { exigir(!file_exists(Escenario::$dir.'/imagen.png'), $titulo . ': original ausente recreado accidentalmente'); }
        exigir(count(glob(Escenario::$dir.'/pelota-*')) === 0, $titulo . ': nuevo archivo huerfano');
      }
      exigir(count(glob(Escenario::$dir.'/staging_*')) === 0, $titulo . ': staging sin limpiar');
      exigir(count(glob(Escenario::$dir.'/resguardo_*')) === (!empty($fallos['restauracion']) ? 1 : 0), $titulo . ': resguardos inesperados');
      foreach (Escenario::$eventos as [$evento,$bloqueado]) if (in_array($evento, array('restauracion','retiro'), true)) exigir($bloqueado, $titulo . ': operacion sin bloqueo');
      echo 'OK ' . $titulo . PHP_EOL;
    }
    // IA: Codex GPT-6 | 2026-09-24 | Encadenar cambios debe apuntar todos los aliases a una sola identidad actual.
    limpiarFixture(); Escenario::$fallos = array(); Escenario::$eventos = array(); Escenario::$duplicado = null;
    file_put_contents(Escenario::$dir.'/imagen.png', $png);
    file_put_contents(Escenario::$dir.'/nuevo.webp', $webp);
    Escenario::$db = new Conexion(itemFixture($png));
    $primero = (new Modelo())->mediaAdminReemplazarInterno(array('tmp_name'=>Escenario::$dir.'/nuevo.webp','name'=>'nuevo.webp','error'=>UPLOAD_ERR_OK), array('id_media_archivo'=>17,'nombre_seo'=>'Juguete para perros'), 1);
    exigir(!$primero['error'], 'Primer cambio secuencial fallo');
    $segundo = (new Modelo())->mediaAdminReemplazarInterno(null, array('id_media_archivo'=>17,'nombre_seo'=>'Pelota azul para perros'), 1);
    exigir(!$segundo['error'], 'Segundo cambio secuencial fallo');
    $rutas = CmsMediaAlias::rutas($segundo['depurar']);
    exigir(count($rutas) === 3 && in_array('/assets/media/cms/ecommerce/imagen.png', $rutas, true) && in_array($primero['depurar']['url'], $rutas, true) && in_array($segundo['depurar']['url'], $rutas, true), 'Nombres sucesivos perdieron rutas');
    exigir(!file_exists(CmsMediaArchivo::ruta($primero['depurar']['url'])) && file_get_contents(CmsMediaArchivo::ruta($segundo['depurar']['url'])) === $webp, 'Renombre sucesivo no movio el archivo');
    $sinCambios = (new Modelo())->mediaAdminReemplazarInterno(null, array('id_media_archivo'=>17,'nombre_seo'=>'Pelota azul para perros'), 1);
    exigir(!$sinCambios['error'] && $sinCambios['tipo'] === 'info' && $sinCambios['depurar']['url'] === $segundo['depurar']['url'], 'Guardar mismo nombre cambio URL');
    $soloAlt = (new Modelo())->mediaAdminReemplazarInterno(null, array('id_media_archivo'=>17,'alt'=>'Pelota azul de prueba'), 1);
    exigir(!$soloAlt['error'] && $soloAlt['depurar']['url'] === $segundo['depurar']['url'] && $soloAlt['depurar']['alt'] === 'Pelota azul de prueba' && count(CmsMediaAlias::rutas($soloAlt['depurar'])) === 3, 'Edicion alt cambio ruta o perdio aliases');
    exigir(Escenario::$db->item['id_media_archivo'] === 17 && Escenario::$db->item['codigo'] === 'media_fixture', 'Renombres cambiaron identidad');
    echo 'OK nombres sucesivos, mismo nombre y alt preservan identidad y todos los aliases' . PHP_EOL;
    // IA: Codex GPT-6 | 2026-09-24 | Flujo real controlador -> modelo -> resolver con cabeceras HTTP capturadas.
    foreach (array(array('GET','imagen.png',301), array('HEAD','imagen.png',301),
      array('GET',basename($soloAlt['depurar']['url']),404), array('GET','desconocida.png',404),
      array('GET','../fuera.webp',404), array('GET','https://externo.invalid/imagen.webp',404),
      array('POST','imagen.png',405)) as [$metodo,$nombre,$codigo]) {
      Escenario::$estadoHttp = 200; Escenario::$cabeceras = array(); $_SERVER['REQUEST_METHOD'] = $metodo;
      $cuerpo = (new EcommercePublico())->media_alias($nombre);
      exigir(Escenario::$estadoHttp === $codigo && $cuerpo === '', 'Alias publico devolvio codigo/cuerpo incorrecto: '.$metodo.' '.$nombre);
      $locations = array_values(array_filter(Escenario::$cabeceras, function ($cabecera) { return strpos($cabecera, 'Location: ') === 0; }));
      if ($codigo === 301) {
        exigir($locations === array('Location: '.$soloAlt['depurar']['url']), 'Alias publico destino incorrecto');
        exigir(in_array('Cache-Control: no-cache', Escenario::$cabeceras, true), 'Alias publico sin control de cache');
      } else { exigir(!$locations, 'Respuesta de error emitio redireccion'); }
      if ($codigo === 405) exigir(in_array('Allow: GET, HEAD', Escenario::$cabeceras, true), 'Metodo publico no permitido sin Allow');
    }
    $rutaActual = CmsMediaArchivo::ruta($soloAlt['depurar']['url']);
    \rename($rutaActual, Escenario::$dir.'/destino_ausente');
    try {
      $_SERVER['REQUEST_METHOD'] = 'GET'; Escenario::$estadoHttp = 200; Escenario::$cabeceras = array();
      (new EcommercePublico())->media_alias('imagen.png');
      exigir(Escenario::$estadoHttp === 404 && !Escenario::$cabeceras, 'Alias a archivo ausente no devolvio 404');
    } finally { \rename(Escenario::$dir.'/destino_ausente', $rutaActual); }
    echo 'OK controlador alias GET/HEAD301, POST405, desconocidos/self/externos/ausentes404' . PHP_EOL;
    $dib = pack('VVVvvVVVVVV',40,1,2,1,32,0,0,0,0,0,0);
    $crearIco = function ($contenido) { return pack('vvv',0,1,1).pack('CCCCvvVV',1,1,0,0,1,32,strlen($contenido),22).$contenido; };
    $rechazado = false;
    try { \CmsMediaArchivo::dimensionesIco($crearIco($dib)); } catch (Throwable $e) { $rechazado = true; }
    exigir($rechazado, 'ICO sin pixeles fue aceptado');
    exigir(\CmsMediaArchivo::dimensionesIco($crearIco($dib."\0\0\0\xff")) === array(1,1), 'ICO ARGB valido fue rechazado');
    exigir(\CmsMediaArchivo::dimensionesIco($crearIco($png)) === array(1,1), 'ICO PNG valido fue rechazado');
    echo 'OK ICO truncado/ARGB/PNG' . PHP_EOL;
    $caja = function ($tipo, $payload) { return pack('N', strlen($payload) + 8) . $tipo . $payload; };
    $ftyp = $caja('ftyp', 'avif'.pack('N',0).'avifmif1');
    $ispe = $caja('ispe', pack('NNN',0,64,32));
    $avif = $ftyp.$caja('meta',pack('N',0).$caja('iprp',$caja('ipco',$ispe))).$caja('mdat','fixture_codificado');
    exigir(\CmsMediaArchivo::dimensionesAvif($avif) === array(64,32), 'Propiedad AVIF ispe no fue leida');
    foreach (array(substr($avif,0,-1),$ftyp.$caja('mdat',$ispe)) as $invalido) {
      $rechazado = false;
      try { \CmsMediaArchivo::dimensionesAvif($invalido); } catch (Throwable $e) { $rechazado = true; }
      exigir($rechazado, 'Estructura AVIF truncada o ispe ajeno fue aceptado');
    }
    echo 'OK estructura AVIF y dimensiones fuera de lugar' . PHP_EOL;
    echo 'UAT aislada completada: sin BD real ni archivos Media existentes.' . PHP_EOL;
  } finally {
    limpiarFixture(); rmdir(Escenario::$dir);
  }
}
