<?php

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/modelos/RentabilidadErp.php";

$modelo = new RentabilidadErp();

function uatHallazgosResumen($respuesta) {
    $depurar = isset($respuesta["depurar"]) ? $respuesta["depurar"] : array();
    $hallazgos = isset($depurar["hallazgos"]) ? $depurar["hallazgos"] : array();
    $porId = array();
    foreach ($hallazgos as $h) {
        $porId[$h["id"]] = $h;
    }
    return array(
        "ok" => empty($respuesta["error"]),
        "resumen" => isset($depurar["resumen"]) ? $depurar["resumen"] : array(),
        "por_id" => $porId
    );
}

$general = $modelo->hallazgosCierreComercial(array("canal" => "menudeo", "limite" => 120));
$tp40372 = $modelo->hallazgosCierreComercial(array("q" => "TP-40372", "canal" => "menudeo", "limite" => 120));
$tp40352 = $modelo->hallazgosCierreComercial(array("q" => "TP-40352", "canal" => "menudeo", "limite" => 120));
$subirPrecio = $modelo->hallazgosCierreComercial(array("accion" => "subir_precio", "canal" => "menudeo", "limite" => 120));

$salida = array(
    "general" => uatHallazgosResumen($general),
    "tp40372" => uatHallazgosResumen($tp40372),
    "tp40352" => uatHallazgosResumen($tp40352),
    "subir_precio" => uatHallazgosResumen($subirPrecio)
);

$ok = true;
foreach ($salida as $item) {
    if (empty($item["ok"])) {
        $ok = false;
    }
}
if (intval($salida["general"]["resumen"]["evaluados"]) !== 120) {
    $ok = false;
}
if (intval(isset($salida["general"]["por_id"]["COST-H103"]["skus"]) ? $salida["general"]["por_id"]["COST-H103"]["skus"] : 0) < 1) {
    $ok = false;
}
if (intval(isset($salida["tp40372"]["por_id"]["COST-H103"]["skus"]) ? $salida["tp40372"]["por_id"]["COST-H103"]["skus"] : 0) !== 5) {
    $ok = false;
}
if (intval(isset($salida["tp40352"]["por_id"]["COST-H103"]["skus"]) ? $salida["tp40352"]["por_id"]["COST-H103"]["skus"] : 0) !== 2) {
    $ok = false;
}
if (intval(isset($salida["subir_precio"]["por_id"]["COST-H107"]["skus"]) ? $salida["subir_precio"]["por_id"]["COST-H107"]["skus"] : 0) < 1) {
    $ok = false;
}

echo json_encode(array(
    "ok" => $ok,
    "hallazgos_cierre" => $salida
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
