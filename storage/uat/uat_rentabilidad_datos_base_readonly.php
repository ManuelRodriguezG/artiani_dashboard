<?php

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/modelos/RentabilidadErp.php";

$modelo = new RentabilidadErp();

function uatDatosBaseResumen($respuesta) {
    $depurar = isset($respuesta["depurar"]) ? $respuesta["depurar"] : array();
    $grupos = isset($depurar["grupos"]) ? $depurar["grupos"] : array();
    $salida = array();
    foreach (array("costo", "precio", "fiscal", "margen") as $clave) {
        $grupo = isset($grupos[$clave]) ? $grupos[$clave] : array("total" => 0, "items" => array());
        $salida[$clave] = array(
            "total" => intval(isset($grupo["total"]) ? $grupo["total"] : 0),
            "primer_sku" => isset($grupo["items"][0]["sku"]) ? $grupo["items"][0]["sku"] : null,
            "accion" => isset($grupo["items"][0]["accion_sugerida"]) ? $grupo["items"][0]["accion_sugerida"] : null
        );
    }
    return array(
        "ok" => empty($respuesta["error"]),
        "total_skus_evaluados" => intval(isset($depurar["total_skus_evaluados"]) ? $depurar["total_skus_evaluados"] : 0),
        "grupos" => $salida
    );
}

$general = $modelo->auditarDatosBaseCierre(array("limite" => 120));
$tp40372 = $modelo->auditarDatosBaseCierre(array("q" => "TP-40372", "limite" => 120));
$tp40352 = $modelo->auditarDatosBaseCierre(array("q" => "TP-40352", "limite" => 120));

echo json_encode(array(
    "ok" => empty($general["error"]) && empty($tp40372["error"]) && empty($tp40352["error"]),
    "general" => uatDatosBaseResumen($general),
    "tp40372" => uatDatosBaseResumen($tp40372),
    "tp40352" => uatDatosBaseResumen($tp40352)
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
