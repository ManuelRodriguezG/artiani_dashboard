<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-09-03
 * Proposito: reproducir la busqueda de SKUs comprables usada por Compras/Sugeridos sin escribir datos.
 * Impacto: solo lectura; ayuda a diagnosticar relaciones proveedor-SKU, listas de proveedor y terminos de busqueda.
 * Contrato: recibe --sku=CODIGO y opcionalmente --proveedores=1,9; consulta skusComprablesParaComprasErp por proveedor.
 */

if (empty($_SERVER['SERVER_NAME'])) {
  $_SERVER['SERVER_NAME'] = 'panel.com.local';
}

require_once dirname(__DIR__, 2) . '/app/iniciador.php';
require_once dirname(__DIR__, 2) . '/app/modelos/Proveedores.php';
require_once dirname(__DIR__, 2) . '/app/modelos/ComprasSugeridosCompraErp.php';

function uat_arg($nombre, $default = '') {
  global $argv;
  $prefijo = '--' . $nombre . '=';
  foreach ($argv as $arg) {
    if (strpos($arg, $prefijo) === 0) {
      return substr($arg, strlen($prefijo));
    }
  }
  return $default;
}

$sku = trim((string) uat_arg('sku'));
if ($sku === '') {
  fwrite(STDERR, "Uso: php storage\\uat\\uat_compras_sku_proveedor_busqueda_readonly.php --sku=SP-3641 --proveedores=1,9\n");
  exit(1);
}

$proveedoresArg = trim((string) uat_arg('proveedores'));
$proveedores = array();
if ($proveedoresArg !== '') {
  foreach (explode(',', $proveedoresArg) as $id) {
    $id = intval($id);
    if ($id > 0) {
      $proveedores[] = $id;
    }
  }
}

class UatComprasSkuProveedorBusquedaDb extends CRUD {
  public function db() {
    return $this->getConexion();
  }
}

$db = (new UatComprasSkuProveedorBusquedaDb())->db();
if (empty($proveedores)) {
  $stmt = $db->prepare("SELECT DISTINCT id_proveedor FROM erp_catalogo_sku_proveedores sp INNER JOIN erp_catalogo_skus s ON s.id_sku=sp.id_sku WHERE UPPER(TRIM(s.sku))=UPPER(TRIM(:sku)) ORDER BY id_proveedor");
  $stmt->execute(array(':sku' => $sku));
  $proveedores = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

$terminos = array($sku);
$stmt = $db->prepare("SELECT DISTINCT sp.sku_proveedor FROM erp_catalogo_sku_proveedores sp INNER JOIN erp_catalogo_skus s ON s.id_sku=sp.id_sku WHERE UPPER(TRIM(s.sku))=UPPER(TRIM(:sku)) AND TRIM(COALESCE(sp.sku_proveedor,''))<>'' ORDER BY sp.sku_proveedor");
$stmt->execute(array(':sku' => $sku));
foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $skuProveedor) {
  $skuProveedor = trim((string) $skuProveedor);
  if ($skuProveedor !== '') {
    $terminos[] = $skuProveedor;
  }
}
$terminos = array_values(array_unique($terminos));

$stmt = $db->prepare("SELECT sp.id_sku_proveedor, sp.id_proveedor, sp.id_sku, sp.sku_proveedor, sp.estatus,
    ld.id_lista_detalle_erp, ld.sku_proveedor AS lista_sku_proveedor, ld.codigo_interno, ld.codigo_barras,
    ld.descripcion_proveedor, ld.estado_match
  FROM erp_catalogo_sku_proveedores sp
  INNER JOIN erp_catalogo_skus s ON s.id_sku=sp.id_sku
  LEFT JOIN erp_proveedores_listas_detalle_erp ld ON ld.id_sku_proveedor=sp.id_sku_proveedor AND ld.id_sku=sp.id_sku
  WHERE UPPER(TRIM(s.sku))=UPPER(TRIM(:sku))
  ORDER BY sp.id_proveedor, ld.id_lista_detalle_erp DESC
  LIMIT 60");
$stmt->execute(array(':sku' => $sku));
$relacionesDetalle = $stmt->fetchAll(PDO::FETCH_ASSOC);

$modelo = new Proveedores();
$modeloSugeridos = new ComprasSugeridosCompraErp();
$salida = array();
foreach ($proveedores as $idProveedor) {
  foreach ($terminos as $termino) {
    $respuesta = $modelo->skusComprablesParaComprasErp($idProveedor, $termino, 'solicitudes');
    $items = isset($respuesta['depurar']) && is_array($respuesta['depurar']) ? $respuesta['depurar'] : array();
    $respuestaSugeridos = $modeloSugeridos->productosProveedor(array(
      'id_proveedor' => $idProveedor,
      'q' => $termino,
      'limite' => 40
    ));
    $depurarSugeridos = isset($respuestaSugeridos['depurar']) && is_array($respuestaSugeridos['depurar']) ? $respuestaSugeridos['depurar'] : array();
    $itemsSugeridos = isset($depurarSugeridos['items']) && is_array($depurarSugeridos['items']) ? $depurarSugeridos['items'] : array();
    $salida[] = array(
      'id_proveedor' => $idProveedor,
      'termino' => $termino,
      'compras_generico' => array(
        'error' => !empty($respuesta['error']),
        'mensaje' => isset($respuesta['mensaje']) ? $respuesta['mensaje'] : '',
        'total' => count($items),
        'items' => array_map(function ($item) {
          return array(
            'id_sku' => intval(isset($item['id_sku']) ? $item['id_sku'] : 0),
            'sku_visible_compra' => isset($item['sku']) ? $item['sku'] : '',
            'sku_erp' => isset($item['sku_erp']) ? $item['sku_erp'] : '',
            'sku_proveedor' => isset($item['sku_proveedor']) ? $item['sku_proveedor'] : '',
            'nombre' => isset($item['nombre']) ? $item['nombre'] : '',
            'id_sku_proveedor' => intval(isset($item['id_sku_proveedor']) ? $item['id_sku_proveedor'] : 0)
          );
        }, $items)
      ),
      'sugeridos_compra' => array(
        'error' => !empty($respuestaSugeridos['error']),
        'mensaje' => isset($respuestaSugeridos['mensaje']) ? $respuestaSugeridos['mensaje'] : '',
        'total' => count($itemsSugeridos),
        'items' => array_map(function ($item) {
        return array(
          'id_sku' => intval(isset($item['id_sku_erp']) ? $item['id_sku_erp'] : 0),
          'sku_erp' => isset($item['sku_erp']) ? $item['sku_erp'] : '',
          'sku_proveedor' => isset($item['sku_proveedor']) ? $item['sku_proveedor'] : '',
          'nombre' => isset($item['nombre_proveedor']) ? $item['nombre_proveedor'] : '',
          'id_sku_proveedor' => intval(isset($item['id_sku_proveedor']) ? $item['id_sku_proveedor'] : 0)
        );
        }, $itemsSugeridos)
      )
    );
  }
}

echo json_encode(array(
  'fecha' => date('Y-m-d H:i:s'),
  'modo' => 'readonly',
  'sku' => $sku,
  'proveedores' => $proveedores,
  'terminos_probados' => $terminos,
  'relaciones_detalle' => $relacionesDetalle,
  'resultados' => $salida
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
