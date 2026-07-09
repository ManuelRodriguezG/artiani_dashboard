<?php

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/modelos/InventarioErp.php";

$modelo = new InventarioErp();
$respuesta = $modelo->valuacionInventario(array("id_almacen" => 0));
$depurar = isset($respuesta["depurar"]) ? $respuesta["depurar"] : array();
$resumen = isset($depurar["resumen"]) ? $depurar["resumen"] : array();

echo json_encode(array(
    "ok" => empty($respuesta["error"]),
    "mensaje" => isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : "",
    "resumen" => array(
        "skus" => isset($resumen["skus"]) ? intval($resumen["skus"]) : 0,
        "cantidad_total" => isset($resumen["cantidad_total"]) ? round(floatval($resumen["cantidad_total"]), 4) : 0,
        "disponible_total" => isset($resumen["disponible_total"]) ? round(floatval($resumen["disponible_total"]), 4) : 0,
        "apartada_total" => isset($resumen["apartada_total"]) ? round(floatval($resumen["apartada_total"]), 4) : 0,
        "valor_total" => isset($resumen["valor_total"]) ? round(floatval($resumen["valor_total"]), 4) : 0
    )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
