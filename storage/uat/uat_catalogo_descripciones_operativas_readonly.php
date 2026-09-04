<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-09-02
 * Proposito: auditar textos operativos indebidamente guardados en descripciones comerciales de productos ERP.
 * Impacto: solo lectura; no modifica productos, SKUs, atributos, categorias, precios, costos ni ecommerce.
 * Contrato: imprime JSON con candidatos y propuesta de limpieza para revision humana antes de cualquier escritura masiva.
 */

if (empty($_SERVER['SERVER_NAME'])) {
  $_SERVER['SERVER_NAME'] = 'panel.com.local';
}

require_once dirname(__DIR__, 2) . '/app/iniciador.php';

class CatalogoDescripcionesOperativasReadonlyDb extends CRUD {
  public function db() {
    return $this->getConexion();
  }
}

function descripcion_operativa_patrones() {
  return array(
    '/^Descripcion proveedor:/iu',
    '/^Catalogo (creo|actualizo|genero|relaciono|migro)/iu',
    '/^Proveedor(es)? (debe|deben|pendiente|validar)/iu',
    '/^Compras? (debe|pendiente|validar|requiere)/iu',
    '/^Almacen (debe|pendiente|validar|requiere)/iu',
    '/^Inventario (debe|pendiente|validar|requiere)/iu',
    '/^Rentabilidad (debe|pendiente|validar|requiere)/iu',
    '/^POS (debe|pendiente|validar|requiere)/iu',
    '/^Incidencia\b/iu',
    '/^Pendiente\b/iu',
    '/^Proveedor\s*:/iu',
    '/^Lista\s*:/iu',
    '/^SKU temporal\b/iu',
    '/^Producto POS pendiente\b/iu',
    '/^Producto proveedor pendiente\b/iu',
    '/^Origen:/iu',
    '/^Estatus:/iu',
    '/^Movimiento:/iu',
    '/^Observaciones operativas:/iu',
    '/^Validar (compra|costo|proveedor|fiscal|inventario|catalogo)/iu',
    '/^Creado desde (proveedores|compras|pos|catalogo)/iu',
    '/^Migracion\b/iu'
  );
}

function descripcion_es_linea_operativa($linea) {
  $linea = trim((string) $linea);
  if ($linea === '') {
    return false;
  }
  foreach (descripcion_operativa_patrones() as $patron) {
    if (preg_match($patron, $linea)) {
      return true;
    }
  }
  return false;
}

function descripcion_limpia_operativa($descripcion) {
  $lineas = preg_split('/\R/', (string) $descripcion);
  $conservadas = array();
  $removidas = array();
  foreach ($lineas as $linea) {
    if (descripcion_es_linea_operativa($linea)) {
      $removidas[] = trim($linea);
      continue;
    }
    $conservadas[] = rtrim($linea);
  }
  $texto = trim(implode("\n", $conservadas));
  $texto = preg_replace('/\R{3,}/', "\n\n", $texto);
  return array(
    'descripcion_sugerida' => trim($texto),
    'lineas_removidas' => $removidas
  );
}

$limite = 80;
foreach ($argv as $arg) {
  if (strpos($arg, '--limit=') === 0) {
    $limite = max(1, intval(substr($arg, 8)));
  }
}

$db = (new CatalogoDescripcionesOperativasReadonlyDb())->db();
$productos = $db->query("
  SELECT id_producto_erp, codigo_producto, nombre, descripcion, estatus
  FROM erp_catalogo_productos
  WHERE estatus <> 'fusionado' AND TRIM(COALESCE(descripcion, '')) <> ''
  ORDER BY id_producto_erp
")->fetchAll(PDO::FETCH_ASSOC);

$candidatos = array();
$totalCandidatos = 0;
$lineasOperativas = 0;
$quedarianVacios = 0;

foreach ($productos as $producto) {
  $resultado = descripcion_limpia_operativa($producto['descripcion']);
  if (empty($resultado['lineas_removidas'])) {
    continue;
  }
  $totalCandidatos++;
  $lineasOperativas += count($resultado['lineas_removidas']);
  if ($resultado['descripcion_sugerida'] === '') {
    $quedarianVacios++;
  }
  if (count($candidatos) < $limite) {
    $candidatos[] = array(
      'id_producto_erp' => intval($producto['id_producto_erp']),
      'codigo_producto' => $producto['codigo_producto'],
      'nombre' => $producto['nombre'],
      'estatus' => $producto['estatus'],
      'lineas_operativas' => $resultado['lineas_removidas'],
      'quedaria_vacio' => $resultado['descripcion_sugerida'] === '',
      'descripcion_actual' => mb_substr((string) $producto['descripcion'], 0, 700, 'UTF-8'),
      'descripcion_sugerida' => mb_substr($resultado['descripcion_sugerida'], 0, 700, 'UTF-8')
    );
  }
}

echo json_encode(array(
  'fecha' => date('Y-m-d H:i:s'),
  'modo' => 'readonly',
  'proyecto' => 'C:\\xampp\\htdocs\\panel_de_control',
  'regla' => 'No modifica BD; solo propone quitar lineas operativas de descripcion comercial.',
  'resumen' => array(
    'productos_revisados' => count($productos),
    'productos_candidatos' => $totalCandidatos,
    'lineas_operativas_detectadas' => $lineasOperativas,
    'productos_que_quedarian_sin_descripcion' => $quedarianVacios,
    'limite_muestras' => $limite
  ),
  'muestras' => $candidatos
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
