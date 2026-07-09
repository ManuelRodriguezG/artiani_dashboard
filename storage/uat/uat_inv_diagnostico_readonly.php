<?php

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/modelos/InventarioErp.php";

$modelo = new InventarioErp();
$respuesta = $modelo->diagnosticoOperativo(array("id_almacen" => 0));
$depurar = isset($respuesta["depurar"]) ? $respuesta["depurar"] : array();

$salida = array(
    "ok" => empty($respuesta["error"]),
    "mensaje" => isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : "",
    "resumen" => isset($depurar["resumen"]) ? $depurar["resumen"] : array(),
    "hallazgos" => array()
);

foreach (isset($depurar["hallazgos"]) ? $depurar["hallazgos"] : array() as $hallazgo) {
    $salida["hallazgos"][] = array(
        "id" => $hallazgo["id"],
        "severidad" => $hallazgo["severidad"],
        "titulo" => $hallazgo["titulo"],
        "total" => $hallazgo["total"]
    );
}

echo json_encode($salida, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
