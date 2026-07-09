<?php

$_SERVER["SERVER_NAME"] = "dashboard.com.local";

require_once __DIR__ . "/../../app/config/configuracion.php";
require_once __DIR__ . "/../../app/config/mysql.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/modelos/InventarioErp.php";

$inventario = new InventarioErp();

$resultados = array();
$resultados["ajuste_salida_mayor_disponible"] = $inventario->aplicarAjuste(array(
    "id_almacen" => 3,
    "tipo_ajuste" => "salida",
    "items" => json_encode(array(
        array("id_sku" => 1138, "cantidad" => 2)
    )),
    "observaciones" => "UAT-INV-006 prueba negativa salida mayor a disponible"
), 1);

$resultados["traspaso_mismo_almacen"] = $inventario->aplicarTraspaso(array(
    "id_almacen_origen" => 3,
    "id_almacen_destino" => 3,
    "items" => json_encode(array(
        array("id_sku" => 1138, "cantidad" => 1)
    )),
    "observaciones" => "UAT-INV prueba negativa traspaso mismo almacen"
), 1);

echo json_encode($resultados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
echo PHP_EOL;
