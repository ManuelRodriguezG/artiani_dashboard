<?php

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/RentabilidadErp.php";

$modelo = new RentabilidadErp();
$respuesta = $modelo->auditarCostosPresentaciones(array("limite" => 120));
$items = isset($respuesta["depurar"]["items"]) ? $respuesta["depurar"]["items"] : array();
$alertas = array();
foreach ($items as $item) {
    if ($item["estatus_consistencia"] !== "ok") {
        $alertas[] = $item;
    }
}

echo json_encode(array(
    "ok" => !$respuesta["error"],
    "mensaje" => $respuesta["mensaje"],
    "total" => count($items),
    "alertas" => count($alertas),
    "primeras_alertas" => array_slice($alertas, 0, 10),
    "tp40372" => array_values(array_filter($items, function ($item) {
        return strpos($item["sku_origen"], "TP-40372") === 0 || strpos($item["sku_resultado"], "TP-40372") === 0;
    }))
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
