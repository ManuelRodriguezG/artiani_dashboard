<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-09-18
 * Proposito: diagnosticar SKUs especificos de ecommerce usando la conexion activa del proyecto.
 * Impacto: Read-only; no escribe BD, no modifica publicaciones ni catalogo.
 * Contrato: ejecutar por CLI con --skus=SP-3664,SP-3665 y opcional --server=sys.artiani.com.mx.
 */

$opciones = getopt('', array('skus:', 'server::'));
$_SERVER['SERVER_NAME'] = isset($opciones['server']) && trim((string) $opciones['server']) !== ''
  ? trim((string) $opciones['server'])
  : 'sys.artiani.com.mx';

require_once __DIR__ . '/../../app/iniciador.php';
require_once __DIR__ . '/../../app/modelos/EcommerceCatalogoPublico.php';

$skus = array_values(array_filter(array_map('trim', explode(',', isset($opciones['skus']) ? (string) $opciones['skus'] : ''))));
if (empty($skus)) {
  $skus = array('SP-3664', 'SP-3665');
}

$modelo = new EcommerceCatalogoPublico();
$ref = new ReflectionClass($modelo);
$metodoConexion = $ref->getMethod('getConexion');
$metodoConexion->setAccessible(true);
$db = $metodoConexion->invoke($modelo);
if (!$db) {
  try {
    $db = new PDO(
      'mysql:host=' . MYSQLHOST . ';port=' . MYSQLPORT . ';dbname=' . MYSQLBASE,
      MYSQLUSER,
      MYSQLPASS,
      array(
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4',
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 10
      )
    );
  } catch (Exception $e) {
    echo json_encode(array(
      'error' => true,
      'mensaje' => 'No se pudo conectar a la base activa desde CLI',
      'server_name' => $_SERVER['SERVER_NAME'],
      'base_activa' => defined('MYSQLBASE') ? MYSQLBASE : '',
      'host_configurado' => defined('MYSQLHOST') ? MYSQLHOST : '',
      'puerto_configurado' => defined('MYSQLPORT') ? MYSQLPORT : '',
      'detalle' => $e->getMessage()
    ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    echo PHP_EOL;
    exit(1);
  }
}

$placeholders = array();
$params = array();
foreach ($skus as $i => $sku) {
  $key = ':sku' . $i;
  $placeholders[] = $key;
  $params[$key] = $sku;
}

$sql = "SELECT s.id_sku, s.sku, s.nombre AS sku_nombre,
    p.id_producto_erp, p.nombre AS producto_nombre,
    LEFT(COALESCE(p.descripcion, ''), 500) AS descripcion,
    COALESCE(r.permite_venta_fraccionaria, 0) AS permite_fraccionaria,
    COALESCE(r.unidad_venta_label, '') AS unidad_venta_label,
    pub.id_publicacion, pub.estatus_publicacion, pub.slug,
    pub.titulo_publico, pub.descripcion_publica, pub.presentacion_publica
  FROM erp_catalogo_skus s
  INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
  LEFT JOIN erp_catalogo_sku_reglas_inventario r ON r.id_sku=s.id_sku
  LEFT JOIN erp_ecommerce_publicaciones pub ON pub.id_sku=s.id_sku AND pub.canal='catalogo_publico'
  WHERE s.sku IN (" . implode(',', $placeholders) . ")
  ORDER BY s.sku";
$stmt = $db->prepare($sql);
$stmt->execute($params);

$salida = array(
  'server_name' => $_SERVER['SERVER_NAME'],
  'base_activa' => defined('MYSQLBASE') ? MYSQLBASE : '',
  'skus_consultados' => $skus,
  'items' => array()
);

foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
  $preparacion = $modelo->prepararPublicacion(array('id_sku' => intval($row['id_sku'])));
  $row['bloqueos_preparacion'] = isset($preparacion['depurar']['bloqueos_publicacion'])
    ? $preparacion['depurar']['bloqueos_publicacion']
    : array();
  $row['alertas_editoriales'] = isset($preparacion['depurar']['auditoria_editorial']['alertas'])
    ? $preparacion['depurar']['auditoria_editorial']['alertas']
    : array();
  $row['bloqueos_editoriales'] = isset($preparacion['depurar']['auditoria_editorial']['bloqueos_criticos'])
    ? $preparacion['depurar']['auditoria_editorial']['bloqueos_criticos']
    : array();
  $row['publicacion_sugerida'] = isset($preparacion['depurar']['publicacion_sugerida'])
    ? $preparacion['depurar']['publicacion_sugerida']
    : array();
  $salida['items'][] = $row;
}

echo json_encode($salida, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
echo PHP_EOL;
