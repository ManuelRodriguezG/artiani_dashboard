<?php

if (!in_array('--execute', $argv, true)) {
    echo "UAT ALM-PREP-001 ya fue ejecutado. Para reejecutar y afectar inventario, usa --execute de forma explicita." . PHP_EOL;
    exit(0);
}

chdir(__DIR__ . '/../../public');
require '../app/iniciador.php';
require '../app/modelos/Almacenes.php';

$almacenes = new Almacenes();

$borrador = $almacenes->guardar_borrador_preparacion(array(
    'id_almacen' => 3,
    'id_sku_presentacion_regla' => 2,
    'unidades_preparadas' => 20,
    'observaciones' => 'UAT ALM-PREP-001 20 bolsas 25g con etiquetas'
), 0);

$confirmacion = null;
if (empty($borrador['error'])) {
    $confirmacion = $almacenes->confirmar_preparacion(
        $borrador['depurar']['id_preparacion_almacen'],
        0
    );
}

echo json_encode(array(
    'borrador' => $borrador,
    'confirmacion' => $confirmacion
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
echo PHP_EOL;
