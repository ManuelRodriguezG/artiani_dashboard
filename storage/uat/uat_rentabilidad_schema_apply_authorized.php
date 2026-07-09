<?php

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/core/DBSchema.php";
require_once "../app/modelos/RentabilidadEsquema.php";

$modelo = new RentabilidadEsquema();
$respuesta = $modelo->planActualizarRentabilidad(true);

echo json_encode(array(
    "ok" => !$respuesta["error"],
    "mensaje" => $respuesta["mensaje"],
    "resumen" => isset($respuesta["depurar"]["resumen"]) ? $respuesta["depurar"]["resumen"] : null,
    "tablas" => isset($respuesta["depurar"]["tablas"]) ? $respuesta["depurar"]["tablas"] : array()
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

