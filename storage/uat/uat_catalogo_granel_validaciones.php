<?php

require_once __DIR__ . '/../../app/config/configuracion.php';
require_once __DIR__ . '/../../app/config/mysql.php';
require_once __DIR__ . '/../../app/core/CRUD.php';
require_once __DIR__ . '/../../app/modelos/CatalogoErpDatos.php';

$modelo = new CatalogoErpDatos();
$ref = new ReflectionClass($modelo);

$getConexion = $ref->getMethod('getConexion');
$getConexion->setAccessible(true);
$db = $getConexion->invoke($modelo);

$reglasGranel = $ref->getMethod('reglasGranelSku');
$reglasGranel->setAccessible(true);

function unidadPorCodigo(PDO $db, $codigo) {
  $stmt = $db->prepare("SELECT id_unidad FROM erp_catalogo_unidades WHERE codigo=:codigo AND estatus='activa' LIMIT 1");
  $stmt->execute(array(':codigo' => $codigo));
  return intval($stmt->fetchColumn());
}

function ejecutarCaso($nombre, $modelo, ReflectionMethod $metodo, PDO $db, $datos, $esperaError) {
  try {
    $resultado = $metodo->invoke($modelo, $db, $datos, true);
    return array(
      'caso' => $nombre,
      'ok' => $esperaError ? false : true,
      'resultado' => $resultado,
      'mensaje' => $esperaError ? 'Se esperaba error y no ocurrio' : 'Validacion aceptada'
    );
  } catch (Exception $e) {
    return array(
      'caso' => $nombre,
      'ok' => $esperaError ? true : false,
      'error' => $e->getMessage()
    );
  }
}

$kg = unidadPorCodigo($db, 'KG');
$pza = unidadPorCodigo($db, 'PZA');

$base = array(
  'id_unidad_base' => $kg,
  'permite_venta_fraccionaria' => 1,
  'precision_decimal' => 3,
  'incremento_minimo_venta' => '0.001',
  'unidad_venta_label' => 'kg',
  'permite_etiqueta_fraccionada' => 0,
  'generar_etiqueta_interna' => 0,
  'requiere_serie' => 0,
  'requiere_serie_fabricante' => 0
);

$casos = array(
  ejecutarCaso('kg_valido_0_001', $modelo, $reglasGranel, $db, $base, false),
  ejecutarCaso('pza_no_decimal', $modelo, $reglasGranel, $db, array_merge($base, array('id_unidad_base' => $pza)), true),
  ejecutarCaso('precision_fuera_rango', $modelo, $reglasGranel, $db, array_merge($base, array('precision_decimal' => 7)), true),
  ejecutarCaso('incremento_mas_decimales_que_precision', $modelo, $reglasGranel, $db, array_merge($base, array('precision_decimal' => 3, 'incremento_minimo_venta' => '0.0001')), true),
  ejecutarCaso('incremento_0_0015_precision_3', $modelo, $reglasGranel, $db, array_merge($base, array('precision_decimal' => 3, 'incremento_minimo_venta' => '0.0015')), true),
  ejecutarCaso('serie_sin_etiqueta_fraccionada', $modelo, $reglasGranel, $db, array_merge($base, array('requiere_serie' => 1)), true),
  ejecutarCaso('serie_con_etiqueta_fraccionada', $modelo, $reglasGranel, $db, array_merge($base, array('requiere_serie' => 1, 'permite_etiqueta_fraccionada' => 1)), false)
);

$fallas = array_values(array_filter($casos, function ($caso) {
  return empty($caso['ok']);
}));

echo json_encode(array(
  'ok' => count($fallas) === 0,
  'casos' => $casos,
  'fallas' => $fallas
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
