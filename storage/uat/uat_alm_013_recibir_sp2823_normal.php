<?php

require __DIR__ . "/../../app/config/configuracion.php";
require __DIR__ . "/../../app/config/mysql.php";
require __DIR__ . "/../../app/core/CRUD.php";
require __DIR__ . "/../../app/modelos/Almacenes.php";

$almacen = new Almacenes();
$partidas = array(
    array(
        "id_recepcion_detalle" => 6,
        "cantidad" => 1,
        "ubicacion" => "UAT-ALM-013",
        "observaciones" => "UAT-ALM-013 recepcion normal sin lote ni etiqueta"
    )
);

$respuesta = $almacen->guardar_recepcion_almacen(1, $partidas, 0);
echo json_encode($respuesta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
