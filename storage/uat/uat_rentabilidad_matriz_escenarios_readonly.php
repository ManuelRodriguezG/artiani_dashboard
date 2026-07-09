<?php

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/modelos/RentabilidadErp.php";

$modelo = new RentabilidadErp();

function uatMatrizResumen($respuesta) {
    $depurar = isset($respuesta["depurar"]) ? $respuesta["depurar"] : array();
    $canales = array();
    foreach (isset($depurar["canales"]) ? $depurar["canales"] : array() as $canal) {
        $canales[$canal["canal"]] = array(
            "rentables" => intval($canal["rentables"]),
            "precaucion" => intval($canal["precaucion"]),
            "bloqueados" => intval($canal["bloqueados"]),
            "perdida" => intval($canal["perdida"]),
            "utilidad_total" => round(floatval($canal["utilidad_total"]), 4),
            "margen_promedio" => $canal["margen_promedio"]
        );
    }
    $items = isset($depurar["items"]) ? $depurar["items"] : array();
    return array(
        "ok" => empty($respuesta["error"]),
        "total_skus_evaluados" => intval(isset($depurar["total_skus_evaluados"]) ? $depurar["total_skus_evaluados"] : 0),
        "canales" => $canales,
        "primer_sku" => isset($items[0]["sku"]) ? $items[0]["sku"] : null,
        "primer_mejor_canal" => isset($items[0]["mejor_canal"]) ? $items[0]["mejor_canal"] : null,
        "primer_bloqueos" => isset($items[0]["bloqueos_escenario"]) ? intval($items[0]["bloqueos_escenario"]) : null
    );
}

$general = $modelo->matrizEscenarios(array("limite" => 120));
$tp40372 = $modelo->matrizEscenarios(array("q" => "TP-40372", "limite" => 120));
$tp40352 = $modelo->matrizEscenarios(array("q" => "TP-40352", "limite" => 120));

echo json_encode(array(
    "ok" => empty($general["error"]) && empty($tp40372["error"]) && empty($tp40352["error"]),
    "general" => uatMatrizResumen($general),
    "tp40372" => uatMatrizResumen($tp40372),
    "tp40352" => uatMatrizResumen($tp40352)
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
