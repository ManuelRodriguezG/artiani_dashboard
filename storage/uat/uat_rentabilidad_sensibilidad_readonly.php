<?php

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/modelos/RentabilidadErp.php";

$modelo = new RentabilidadErp();

function uatSensibilidadResumen($respuesta) {
    $depurar = isset($respuesta["depurar"]) ? $respuesta["depurar"] : array();
    $items = isset($depurar["items"]) ? $depurar["items"] : array();
    $primer = isset($items[0]) ? $items[0] : array();
    return array(
        "ok" => empty($respuesta["error"]),
        "shock" => isset($depurar["shock"]) ? $depurar["shock"] : array(),
        "resumen" => isset($depurar["resumen"]) ? $depurar["resumen"] : array(),
        "primer_sku" => isset($primer["sku"]) ? $primer["sku"] : null,
        "primer_vulnerable" => isset($primer["vulnerable"]) ? $primer["vulnerable"] : null,
        "primer_combinado" => isset($primer["combinado"]) ? $primer["combinado"] : null
    );
}

$general = $modelo->sensibilidadRentabilidad(array("canal" => "menudeo", "limite" => 120));
$tp40372 = $modelo->sensibilidadRentabilidad(array("q" => "TP-40372", "canal" => "menudeo", "limite" => 120));
$tp40352 = $modelo->sensibilidadRentabilidad(array("q" => "TP-40352", "canal" => "menudeo", "limite" => 120));
$conStock = $modelo->sensibilidadRentabilidad(array("stock" => "con_stock", "canal" => "menudeo", "limite" => 120));
$shockFuerte = $modelo->sensibilidadRentabilidad(array("q" => "TP-40372", "canal" => "menudeo", "limite" => 120, "costo_alza_pct" => 20, "precio_baja_pct" => 10));

$salida = array(
    "general" => uatSensibilidadResumen($general),
    "tp40372" => uatSensibilidadResumen($tp40372),
    "tp40352" => uatSensibilidadResumen($tp40352),
    "con_stock" => uatSensibilidadResumen($conStock),
    "tp40372_shock_fuerte" => uatSensibilidadResumen($shockFuerte)
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
if (intval($salida["tp40372"]["resumen"]["evaluados"]) !== 5) {
    $ok = false;
}
if (intval($salida["tp40352"]["resumen"]["evaluados"]) !== 2) {
    $ok = false;
}

echo json_encode(array(
    "ok" => $ok,
    "sensibilidad" => $salida
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
