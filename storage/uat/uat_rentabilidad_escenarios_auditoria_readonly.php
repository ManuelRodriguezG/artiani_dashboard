<?php

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/RentabilidadErp.php";

$modelo = new RentabilidadErp();
$respuesta = $modelo->auditarEscenariosComerciales();

$items = isset($respuesta["depurar"]["items"]) ? $respuesta["depurar"]["items"] : array();

echo json_encode(array(
    "ok" => empty($respuesta["error"]) && count($items) >= 3,
    "escenarios" => array(
        "resumen" => isset($respuesta["depurar"]["resumen"]) ? $respuesta["depurar"]["resumen"] : null,
        "estados" => array_map(function ($item) {
            return array(
                "clave" => $item["clave"],
                "estado" => $item["estado"],
                "diferencias" => count($item["diferencias"])
            );
        }, $items)
    )
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

