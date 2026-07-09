<?php

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/modelos/RentabilidadErp.php";

$modelo = new RentabilidadErp();

function uatPreciosObjetivoResumen($respuesta) {
    $depurar = isset($respuesta["depurar"]) ? $respuesta["depurar"] : array();
    $items = isset($depurar["items"]) ? $depurar["items"] : array();
    $primer = isset($items[0]) ? $items[0] : array();
    return array(
        "ok" => empty($respuesta["error"]),
        "resumen" => isset($depurar["resumen"]) ? $depurar["resumen"] : array(),
        "primer_sku" => isset($primer["sku"]) ? $primer["sku"] : null,
        "primer_menudeo" => isset($primer["escenarios"]["menudeo"]) ? $primer["escenarios"]["menudeo"] : null,
        "primer_mayoreo" => isset($primer["escenarios"]["mayoreo"]) ? $primer["escenarios"]["mayoreo"] : null,
        "primer_alianza" => isset($primer["escenarios"]["alianza"]) ? $primer["escenarios"]["alianza"] : null
    );
}

$general = $modelo->preciosObjetivo(array("limite" => 120));
$tp40372 = $modelo->preciosObjetivo(array("q" => "TP-40372", "limite" => 120));
$tp40352 = $modelo->preciosObjetivo(array("q" => "TP-40352", "limite" => 120));

echo json_encode(array(
    "ok" => empty($general["error"]) && empty($tp40372["error"]) && empty($tp40352["error"]),
    "general" => uatPreciosObjetivoResumen($general),
    "tp40372" => uatPreciosObjetivoResumen($tp40372),
    "tp40352" => uatPreciosObjetivoResumen($tp40352)
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
