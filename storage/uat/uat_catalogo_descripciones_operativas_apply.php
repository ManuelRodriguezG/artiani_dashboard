<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-09-02
 * Proposito: retirar trazas operativas de incidencias/proveedores/listas guardadas indebidamente en descripciones comerciales.
 * Impacto: modifica `erp_catalogo_productos.descripcion`; puede dejar descripcion vacia si el contenido era solo operativo.
 * Contrato: requiere token CATALOGO_DESCRIPCIONES_OPERATIVAS_LIMPIAR, respaldo externo existente y confirmacion explicita.
 */

if (empty($_SERVER['SERVER_NAME'])) {
  $_SERVER['SERVER_NAME'] = 'panel.com.local';
}

require_once dirname(__DIR__, 2) . '/app/iniciador.php';

class CatalogoDescripcionesOperativasApplyDb extends CRUD {
  public function db() {
    return $this->getConexion();
  }
}

function argumento_descripcion_operativa($nombre, $default = '') {
  global $argv;
  $prefijo = '--' . $nombre . '=';
  foreach ($argv as $arg) {
    if (strpos($arg, $prefijo) === 0) {
      return substr($arg, strlen($prefijo));
    }
  }
  return $default;
}

function respaldo_externo_valido_descripcion_operativa($ruta) {
  $real = realpath($ruta);
  $proyecto = realpath(dirname(__DIR__, 2));
  if ($real === false || $proyecto === false || !is_file($real) || !is_readable($real) || filesize($real) <= 0) {
    return false;
  }
  return stripos($real, $proyecto) !== 0;
}

function patrones_descripcion_operativa_apply() {
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

function linea_descripcion_operativa_apply($linea) {
  $linea = trim((string) $linea);
  if ($linea === '') {
    return false;
  }
  foreach (patrones_descripcion_operativa_apply() as $patron) {
    if (preg_match($patron, $linea)) {
      return true;
    }
  }
  return false;
}

function limpiar_descripcion_operativa_apply($descripcion) {
  $lineas = preg_split('/\R/', (string) $descripcion);
  $conservadas = array();
  $removidas = array();
  foreach ($lineas as $linea) {
    if (linea_descripcion_operativa_apply($linea)) {
      $removidas[] = trim($linea);
      continue;
    }
    $conservadas[] = rtrim($linea);
  }
  $texto = trim(implode("\n", $conservadas));
  $texto = preg_replace('/\R{3,}/', "\n\n", $texto);
  return array('descripcion' => trim($texto), 'removidas' => $removidas);
}

$token = argumento_descripcion_operativa('autorizar');
$respaldo = argumento_descripcion_operativa('respaldo');
$confirmacion = argumento_descripcion_operativa('confirmacion');

if ($token !== 'CATALOGO_DESCRIPCIONES_OPERATIVAS_LIMPIAR') {
  fwrite(STDERR, "Falta token: --autorizar=CATALOGO_DESCRIPCIONES_OPERATIVAS_LIMPIAR\n");
  exit(1);
}
if ($confirmacion !== 'QUITAR_TEXTO_OPERATIVO_DESCRIPCIONES') {
  fwrite(STDERR, "Falta confirmacion: --confirmacion=QUITAR_TEXTO_OPERATIVO_DESCRIPCIONES\n");
  exit(1);
}
if (!respaldo_externo_valido_descripcion_operativa($respaldo)) {
  fwrite(STDERR, "El respaldo externo debe existir, ser legible, no vacio y estar fuera del proyecto.\n");
  exit(1);
}

$db = (new CatalogoDescripcionesOperativasApplyDb())->db();
$productos = $db->query("
  SELECT id_producto_erp, codigo_producto, descripcion
  FROM erp_catalogo_productos
  WHERE estatus <> 'fusionado' AND TRIM(COALESCE(descripcion, '')) <> ''
  ORDER BY id_producto_erp
")->fetchAll(PDO::FETCH_ASSOC);

$actualizados = 0;
$quedaronVacios = 0;
$lineasRemovidas = 0;
$muestras = array();

try {
  $db->beginTransaction();
  $stmt = $db->prepare("UPDATE erp_catalogo_productos
    SET descripcion=:descripcion, fecha_actualizacion=CURRENT_TIMESTAMP
    WHERE id_producto_erp=:producto");

  foreach ($productos as $producto) {
    $resultado = limpiar_descripcion_operativa_apply($producto['descripcion']);
    if (empty($resultado['removidas'])) {
      continue;
    }
    $stmt->execute(array(
      ':descripcion' => $resultado['descripcion'],
      ':producto' => intval($producto['id_producto_erp'])
    ));
    $actualizados++;
    $lineasRemovidas += count($resultado['removidas']);
    if ($resultado['descripcion'] === '') {
      $quedaronVacios++;
    }
    if (count($muestras) < 20) {
      $muestras[] = array(
        'id_producto_erp' => intval($producto['id_producto_erp']),
        'codigo_producto' => $producto['codigo_producto'],
        'lineas_removidas' => $resultado['removidas'],
        'descripcion_final' => mb_substr($resultado['descripcion'], 0, 180, 'UTF-8')
      );
    }
  }

  $db->commit();
} catch (Exception $e) {
  if ($db->inTransaction()) {
    $db->rollBack();
  }
  fwrite(STDERR, $e->getMessage() . "\n");
  exit(1);
}

echo json_encode(array(
  'fecha' => date('Y-m-d H:i:s'),
  'modo' => 'apply',
  'proyecto' => 'C:\\xampp\\htdocs\\panel_de_control',
  'respaldo' => $respaldo,
  'productos_actualizados' => $actualizados,
  'lineas_removidas' => $lineasRemovidas,
  'productos_que_quedaron_sin_descripcion' => $quedaronVacios,
  'muestras' => $muestras
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
