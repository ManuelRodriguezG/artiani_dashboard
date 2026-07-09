<?php

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/RentabilidadErp.php";

$modelo = new RentabilidadErp();

$ejecutar = in_array("--execute", isset($argv) ? $argv : array(), true);
$respaldo = "";
foreach (isset($argv) ? $argv : array() as $arg) {
    if (strpos($arg, "--respaldo=") === 0) {
        $respaldo = substr($arg, 11);
    }
}

$datos = array(
    "q" => "TP-40372",
    "canal" => "menudeo",
    "riesgo" => "",
    "descuento_pct" => "0",
    "gasto_pct" => "18",
    "comision_pct" => "0",
    "margen_objetivo_pct" => "25"
);

if ($ejecutar) {
    $datos["respaldo_externo_ref"] = $respaldo;
    $datos["confirmar_autorizacion"] = "AUTORIZO CREAR RECOMENDACIONES";
    $guardar = $modelo->guardarRecomendaciones($datos, 0);
} else {
    $guardar = $modelo->preflightRecomendaciones($datos);
}
$listar = $modelo->listarRecomendaciones(array("estatus" => "pendiente"));

echo json_encode(array(
    "ok" => !$guardar["error"] && !$listar["error"],
    "modo" => $ejecutar ? "execute" : "preflight",
    "resultado" => array(
        "error" => $guardar["error"],
        "mensaje" => $guardar["mensaje"],
        "depurar" => isset($guardar["depurar"]) ? $guardar["depurar"] : null
    ),
    "pendientes" => array(
        "total" => isset($listar["depurar"]["items"]) ? count($listar["depurar"]["items"]) : 0,
        "primer_sku" => isset($listar["depurar"]["items"][0]["sku"]) ? $listar["depurar"]["items"][0]["sku"] : null
    )
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
