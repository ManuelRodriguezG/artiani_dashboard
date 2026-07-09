<?php

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/modelos/RentabilidadErp.php";

$modelo = new RentabilidadErp();

function uatRevisionResumen($respuesta) {
    $depurar = isset($respuesta["depurar"]) ? $respuesta["depurar"] : array();
    $salida = array(
        "ok" => empty($respuesta["error"]),
        "total_skus_evaluados" => intval(isset($depurar["total_skus_evaluados"]) ? $depurar["total_skus_evaluados"] : 0),
        "grupos" => array()
    );
    foreach (isset($depurar["grupos"]) ? $depurar["grupos"] : array() as $clave => $grupo) {
        $salida["grupos"][$clave] = array(
            "total" => intval(isset($grupo["total"]) ? $grupo["total"] : 0),
            "primer_sku" => isset($grupo["items"][0]["sku"]) ? $grupo["items"][0]["sku"] : null,
            "primer_utilidad" => isset($grupo["items"][0]["utilidad"]) ? $grupo["items"][0]["utilidad"] : null
        );
    }
    return $salida;
}

$general = $modelo->revisionOperativa(array("canal" => "menudeo"));
$tp40372 = $modelo->revisionOperativa(array("q" => "TP-40372", "canal" => "menudeo"));
$tp40352 = $modelo->revisionOperativa(array("q" => "TP-40352", "canal" => "menudeo"));

echo json_encode(array(
    "ok" => empty($general["error"]) && empty($tp40372["error"]) && empty($tp40352["error"]),
    "general" => uatRevisionResumen($general),
    "tp40372" => uatRevisionResumen($tp40372),
    "tp40352" => uatRevisionResumen($tp40352)
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
