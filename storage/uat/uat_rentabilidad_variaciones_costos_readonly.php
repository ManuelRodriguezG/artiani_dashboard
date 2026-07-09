<?php

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/modelos/RentabilidadErp.php";

$modelo = new RentabilidadErp();

function uatVariacionesResumen($respuesta) {
    $depurar = isset($respuesta["depurar"]) ? $respuesta["depurar"] : array();
    $items = isset($depurar["items"]) ? $depurar["items"] : array();
    $primer = isset($items[0]) ? $items[0] : array();
    return array(
        "ok" => empty($respuesta["error"]),
        "umbral_pct" => isset($depurar["umbral_pct"]) ? $depurar["umbral_pct"] : null,
        "resumen" => isset($depurar["resumen"]) ? $depurar["resumen"] : array(),
        "primer_sku" => isset($primer["sku"]) ? $primer["sku"] : null,
        "primer_alertas" => isset($primer["alertas"]) ? $primer["alertas"] : null,
        "primer_diferencia_pct" => isset($primer["mayor_diferencia_pct"]) ? $primer["mayor_diferencia_pct"] : null,
        "primer_comparaciones" => isset($primer["comparaciones"]) ? count($primer["comparaciones"]) : 0
    );
}

$general = $modelo->variacionesCostos(array("canal" => "menudeo", "limite" => 120));
$tp40372 = $modelo->variacionesCostos(array("q" => "TP-40372", "canal" => "menudeo", "limite" => 120));
$tp40352 = $modelo->variacionesCostos(array("q" => "TP-40352", "canal" => "menudeo", "limite" => 120));
$conStock = $modelo->variacionesCostos(array("stock" => "con_stock", "canal" => "menudeo", "limite" => 120));

$salida = array(
    "general" => uatVariacionesResumen($general),
    "tp40372" => uatVariacionesResumen($tp40372),
    "tp40352" => uatVariacionesResumen($tp40352),
    "con_stock" => uatVariacionesResumen($conStock)
);

$ok = true;
foreach ($salida as $item) {
    if (empty($item["ok"])) {
        $ok = false;
    }
}
if (intval($salida["general"]["resumen"]["evaluados"]) <= 0) {
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
    "variaciones" => $salida
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
