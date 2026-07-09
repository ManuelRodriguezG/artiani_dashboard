<?php

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/modelos/RentabilidadErp.php";

$modelo = new RentabilidadErp();

function uatAutorizacionesResumen($respuesta) {
    $depurar = isset($respuesta["depurar"]) ? $respuesta["depurar"] : array();
    $acciones = isset($depurar["acciones"]) ? $depurar["acciones"] : array();
    $porId = array();
    foreach ($acciones as $accion) {
        $porId[$accion["id"]] = $accion;
    }
    return array(
        "ok" => empty($respuesta["error"]),
        "resumen" => isset($depurar["resumen"]) ? $depurar["resumen"] : array(),
        "por_id" => $porId
    );
}

$general = $modelo->autorizacionesCierreComercial(array("canal" => "menudeo", "limite" => 120));
$tp40372 = $modelo->autorizacionesCierreComercial(array("q" => "TP-40372", "canal" => "menudeo", "limite" => 120));
$tp40352 = $modelo->autorizacionesCierreComercial(array("q" => "TP-40352", "canal" => "menudeo", "limite" => 120));
$subirPrecio = $modelo->autorizacionesCierreComercial(array("accion" => "subir_precio", "canal" => "menudeo", "limite" => 120));

$salida = array(
    "general" => uatAutorizacionesResumen($general),
    "tp40372" => uatAutorizacionesResumen($tp40372),
    "tp40352" => uatAutorizacionesResumen($tp40352),
    "subir_precio" => uatAutorizacionesResumen($subirPrecio)
);

$ok = true;
foreach ($salida as $item) {
    if (empty($item["ok"])) {
        $ok = false;
    }
}
if (intval($salida["general"]["resumen"]["acciones"]) !== 5) {
    $ok = false;
}
if ($salida["general"]["por_id"]["AUTH-COST-001"]["estado"] !== "bloqueada") {
    $ok = false;
}
if ($salida["general"]["por_id"]["AUTH-COST-002"]["estado"] !== "requiere_respaldo") {
    $ok = false;
}
if ($salida["tp40372"]["por_id"]["AUTH-COST-001"]["estado"] !== "bloqueada") {
    $ok = false;
}
if ($salida["subir_precio"]["por_id"]["AUTH-COST-004"]["estado"] !== "bloqueada") {
    $ok = false;
}

echo json_encode(array(
    "ok" => $ok,
    "autorizaciones_cierre" => $salida
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
