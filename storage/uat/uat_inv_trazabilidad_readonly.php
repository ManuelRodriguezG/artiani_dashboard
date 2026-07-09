<?php

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/modelos/InventarioErp.php";

$modelo = new InventarioErp();
$claves = array(
    "EXI-50-31",
    "P25-II000043-0001",
    "INV-INICIAL-20260622-UAT01"
);

$salida = array();
foreach ($claves as $clave) {
    $respuesta = $modelo->consultarTrazabilidad(array("q" => $clave));
    $depurar = isset($respuesta["depurar"]) ? $respuesta["depurar"] : array();
    $salida[] = array(
        "clave" => $clave,
        "error" => !empty($respuesta["error"]),
        "mensaje" => isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : "",
        "existencias" => count(isset($depurar["existencias"]) ? $depurar["existencias"] : array()),
        "movimientos" => count(isset($depurar["movimientos"]) ? $depurar["movimientos"] : array()),
        "unidades" => count(isset($depurar["unidades"]) ? $depurar["unidades"] : array())
    );
}

echo json_encode(array("ok" => true, "resultados" => $salida), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
