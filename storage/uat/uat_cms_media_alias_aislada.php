<?php
/**
 * IA: Codex GPT-6 | Fecha: 2026-09-24
 * Proposito: comprobar aliases, validacion de rutas y deteccion de usos tras renombrar o cambiar formato.
 * Impacto: regresion de URLs CMS persistidas y acceso publico seguro.
 * Contrato: CLI, PDO simulado; no lee configuracion, conecta a BD ni toca archivos Media reales.
 */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../../app/core/CmsMediaAlias.php';
require_once __DIR__ . '/../../app/core/CmsMediaReferencias.php';

/** IA: Codex GPT-6 | Fecha: 2026-09-24 | Conexion sin servidor; entrega fixtures y fallos de consulta. */
class CmsMediaAliasUatPDO extends PDO {
  public $filas;
  public $fallo;
  public $consultas = array();
  public $modoUsos = false;
  public function __construct(array $filas, string $fallo = '') { $this->filas = $filas; $this->fallo = $fallo; }
  public function prepare($query, $options = array()): PDOStatement|false {
    $this->consultas[] = $query;
    if ($this->fallo === 'prepare') { return false; }
    if (strpos($query, 'SELECT ') !== 0) { throw new RuntimeException('La prueba solo admite consultas SELECT.'); }
    $filas = $this->filas;
    if ($this->modoUsos && strpos($query, 'INFORMATION_SCHEMA.COLUMNS') !== false) {
      $filas = array();
      foreach (array('id_bloque', 'nombre_interno', 'estatus', 'payload_json') as $columna) {
        $filas[] = array('TABLE_NAME' => 'erp_ecommerce_contenido_bloques', 'COLUMN_NAME' => $columna);
      }
    }
    return new CmsMediaAliasUatConsulta($filas, $this->fallo);
  }
}

/** IA: Codex GPT-6 | Fecha: 2026-09-24 | Cursor simulado para verificar resultados y errores de lectura. */
class CmsMediaAliasUatConsulta extends PDOStatement {
  private $filas;
  private $fallo;
  private $posicion = 0;
  public function __construct(array $filas, string $fallo) { $this->filas = $filas; $this->fallo = $fallo; }
  public function execute(?array $params = null): bool { return $this->fallo !== 'execute'; }
  public function fetch(int $mode = PDO::FETCH_DEFAULT, int $cursorOrientation = PDO::FETCH_ORI_NEXT, int $cursorOffset = 0): mixed {
    if ($this->fallo === 'lectura') { return false; }
    return $this->filas[$this->posicion++] ?? false;
  }
  public function errorCode(): ?string { return $this->fallo === 'lectura' ? 'HY000' : '00000'; }
}

/** IA: Codex GPT-6 | Fecha: 2026-09-24 | Falla inmediatamente si cambia una garantia del acceso o las referencias. */
function cmsAliasExigir(bool $condicion, string $mensaje): void {
  if (!$condicion) { throw new RuntimeException($mensaje); }
}

$prefijo = '/assets/media/cms/ecommerce/';
$actual = $prefijo . 'bolsa-alimento-perro.webp';
$previa = $prefijo . 'cms_20260924_abc_imagen.png';
$anterior = $prefijo . 'alimento-perro.jpg';
$item = array('id_media_archivo' => 41, 'codigo' => 'cms_fixture', 'ruta_publica' => $actual,
  'mime' => 'image/webp', 'metadata_json' => json_encode(array('rutas_anteriores' => array($previa, $anterior, $previa))));
$inseguras = array(null, false, 42, array(), '', 'https://externo.test' . $actual, '//externo.test' . $actual,
  $prefijo . '../privado.png', $prefijo . '%2e%2e/secreto.png', $prefijo . 'imagen%00.png',
  $prefijo . 'carpeta/imagen.png', $prefijo . 'carpeta\\imagen.png',
  $prefijo . 'imagen.svg', $prefijo . '.oculto.png', $prefijo . 'imagen.png?archivo=1',
  $prefijo . "imagen.png\r\nLocation: https://externo.test", $prefijo . "imagen\0.png",
  $prefijo . str_repeat('x', 253) . '.png');
cmsAliasExigir(CmsMediaAlias::rutas($item) === array($actual, $previa, $anterior), 'No deduplica rutas historicas.');
$formateado = array('url' => $actual, 'urls_anteriores' => array_merge(array($previa), $inseguras));
cmsAliasExigir(CmsMediaAlias::rutas($formateado) === array($actual, $previa), 'Una ruta insegura llego a la lista publica.');
cmsAliasExigir(CmsMediaAlias::rutas(array('url' => $actual, 'metadata_json' => '{roto')) === array($actual), 'JSON invalido no se ignora.');
cmsAliasExigir(CmsMediaAlias::rutas(array('url' => $actual, 'metadata_json' => array('rutas_anteriores' => $previa))) === array($actual), 'Alias escalar se acepto.');
echo 'OK rutas locales, JSON malformado y deduplicacion' . PHP_EOL;

$db = new CmsMediaAliasUatPDO(array($item));
foreach (array($actual, $previa, $anterior) as $ruta) {
  cmsAliasExigir(CmsMediaAlias::resolver($db, $ruta) === array('url' => $actual, 'mime' => 'image/webp'), 'Ruta actual o historica no resuelta.');
}
cmsAliasExigir(CmsMediaAlias::resolver($db, $prefijo . 'alimento.webp') === null, 'Nombre parcial resuelto por coincidencia aproximada.');
$invalidasDb = new CmsMediaAliasUatPDO(array($item));
foreach ($inseguras as $ruta) {
  if (!is_string($ruta)) { continue; }
  cmsAliasExigir(CmsMediaAlias::resolver($invalidasDb, $ruta) === null, 'La ruta insegura se resolvio.');
}
cmsAliasExigir(!$invalidasDb->consultas, 'Ruta invalida genero consultas de BD.');
$externo = $item; $externo['ruta_publica'] = 'https://externo.test/imagen.webp';
cmsAliasExigir(CmsMediaAlias::resolver(new CmsMediaAliasUatPDO(array($externo)), $previa) === null, 'Redireccion externa permitida.');
$mimeInvalido = $item; $mimeInvalido['mime'] = 'image/png';
cmsAliasExigir(CmsMediaAlias::resolver(new CmsMediaAliasUatPDO(array($mimeInvalido)), $previa) === null, 'MIME incompatible permitido.');
$ambiguo = $item; $ambiguo['ruta_publica'] = $prefijo . 'otra.webp';
cmsAliasExigir(CmsMediaAlias::resolver(new CmsMediaAliasUatPDO(array($item, $ambiguo)), $previa) === null, 'Alias ambiguo eligio imagen arbitraria.');
$archivado = $item; $archivado['estatus'] = 'archivado';
cmsAliasExigir(CmsMediaAlias::resolver(new CmsMediaAliasUatPDO(array($archivado)), $previa) !== null, 'Archivo archivado rompio referencias existentes.');
echo 'OK resolucion exacta, destino seguro, MIME y aliases ambiguos' . PHP_EOL;

foreach (array('prepare', 'execute', 'lectura') as $fallo) {
  $rechazado = false;
  try { CmsMediaAlias::resolver(new CmsMediaAliasUatPDO(array($item), $fallo), $previa); }
  catch (RuntimeException $e) { $rechazado = true; }
  cmsAliasExigir($rechazado, 'Fallo ' . $fallo . ' no fue informado.');
}
echo 'OK errores PDO no se confunden con rutas inexistentes' . PHP_EOL;

foreach (array($actual, $previa, $anterior) as $ruta) {
  foreach (array($ruta, '<img src="https://panel.com.local' . $ruta . '?v=1">',
    json_encode(array('url_desktop' => $ruta)), rawurlencode($ruta), "<img src='" . $ruta . "'>\xff") as $contenido) {
    cmsAliasExigir(CmsMediaReferencias::contieneReferencia($contenido, $item), 'No detecta una referencia actual o historica.');
  }
  cmsAliasExigir(!CmsMediaReferencias::contieneReferencia($ruta . '.copia', $item), 'Confunde otro archivo con la ruta buscada.');
}
cmsAliasExigir(CmsMediaReferencias::contieneReferencia(array('media_id' => 41), $item), 'Referencia por ID dejo de funcionar.');
cmsAliasExigir(CmsMediaReferencias::contieneReferencia(array('codigo_media' => 'cms_fixture'), $item), 'Referencia por codigo dejo de funcionar.');
cmsAliasExigir(CmsMediaReferencias::contieneReferencia($previa, $formateado), 'No reconoce aliases del item formateado.');
$usosDb = new CmsMediaAliasUatPDO(array(array('id_bloque' => 7, 'nombre_interno' => 'Hero', 'estatus' => 'borrador', 'payload_json' => json_encode(array('imagen' => $previa)))));
$usosDb->modoUsos = true;
$usos = CmsMediaReferencias::usos($usosDb, $item);
cmsAliasExigir(count($usos) === 1 && $usos[0]['estado'] === 'borrador', 'El detector de usos omite un alias guardado en borrador.');
echo 'OK usos CMS con aliases, JSON, HTML, URL codificada, ID y codigo' . PHP_EOL;
echo 'UAT aliases completada: sin BD real ni archivos Media existentes.' . PHP_EOL;
