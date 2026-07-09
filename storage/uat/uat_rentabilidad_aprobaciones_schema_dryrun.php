<?php

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/RentabilidadEsquema.php";

$modelo = new RentabilidadEsquema();
$respuesta = $modelo->planAprobacionesComerciales(false);
$resumen = isset($respuesta["depurar"]["resumen"]) ? $respuesta["depurar"]["resumen"] : array();
$tablas = isset($respuesta["depurar"]["tablas"]) ? $respuesta["depurar"]["tablas"] : array();
$plan = isset($respuesta["depurar"]["plan"]) ? $respuesta["depurar"]["plan"] : array();

$sqlGenerado = 0;
foreach ($plan as $item) {
    if (isset($item["depurar"]["sql"])) {
        $sqlGenerado++;
    }
}

$ok = empty($respuesta["error"])
    && isset($respuesta["depurar"]["ejecutar"])
    && $respuesta["depurar"]["ejecutar"] === false
    && count($tablas) === 2
    && intval(isset($resumen["total"]) ? $resumen["total"] : 0) === 2;

echo json_encode(array(
    "ok" => $ok,
    "modo" => "dry-run",
    "ejecutar" => isset($respuesta["depurar"]["ejecutar"]) ? $respuesta["depurar"]["ejecutar"] : null,
    "tablas" => $tablas,
    "resumen" => $resumen,
    "sql_generado" => $sqlGenerado,
    "mensaje" => isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : null
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

