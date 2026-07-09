<?php

$_SERVER["SERVER_NAME"] = "dashboard.com.local";

require_once __DIR__ . "/../../app/config/configuracion.php";
require_once __DIR__ . "/../../app/config/mysql.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/modelos/InventarioErp.php";

$inventario = new InventarioErp();

$respuesta = $inventario->aplicarAjuste(array(
    "id_almacen" => 3,
    "tipo_ajuste" => "entrada",
    "referencia" => "CAT-GRANEL-H001",
    "observaciones" => "CAT-GRANEL-H001 correccion historica REC-OC-20 TP-40372: conversion granel 5 cajas x 4 kg = 20 kg; ajuste L1 +12 kg y L2 +3 kg sin reabrir recepcion",
    "items" => json_encode(array(
        array(
            "id_sku" => 146,
            "id_existencia_inventario" => 26,
            "cantidad" => 12,
            "lote" => "L1",
            "fecha_caducidad" => "2026-10-30",
            "ubicacion_id" => 13
        ),
        array(
            "id_sku" => 146,
            "id_existencia_inventario" => 27,
            "cantidad" => 3,
            "lote" => "L2",
            "fecha_caducidad" => "2027-01-29",
            "ubicacion_id" => 13
        )
    ))
), 1);

echo json_encode($respuesta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
echo PHP_EOL;
