<?php

chdir(__DIR__ . '/../../public');
require '../app/iniciador.php';
require '../app/modelos/Almacenes.php';

$almacenes = new Almacenes();

$catalogo = $almacenes->consultar_presentaciones_preparables(array(
    'id_almacen' => 3
));

$existenciasGranel = $almacenes->consultar_existencias_base_preparacion(146, 3);
$existencias500 = $almacenes->consultar_existencias_base_preparacion(1756, 3);

echo json_encode(array(
    'catalogo_error' => $catalogo['error'],
    'catalogo_total' => count($catalogo['depurar']),
    'transformaciones_tp40372' => array_values(array_filter($catalogo['depurar'], function ($item) {
        return strpos($item['sku_base'], 'TP-40372') === 0 || strpos($item['sku_presentacion'], 'TP-40372') === 0;
    })),
    'existencias_granel_total' => count($existenciasGranel['depurar']),
    'existencias_500gr_total' => count($existencias500['depurar'])
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
echo PHP_EOL;
