<?php

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/modelos/RentabilidadErp.php";

$modelo = new RentabilidadErp();

function uatCanalesResumen($respuesta) {
    $depurar = isset($respuesta["depurar"]) ? $respuesta["depurar"] : array();
    $items = isset($depurar["items"]) ? $depurar["items"] : array();
    $primer = isset($items[0]) ? $items[0] : array();
    return array(
        "ok" => empty($respuesta["error"]),
        "resumen" => isset($depurar["resumen"]) ? $depurar["resumen"] : array(),
        "primer_sku" => isset($primer["sku"]) ? $primer["sku"] : null,
        "primer_estado" => isset($primer["estado"]) ? $primer["estado"] : null,
        "primer_canal" => isset($primer["canal_recomendado"]) ? $primer["canal_recomendado"] : null,
        "primer_utilidad" => isset($primer["utilidad"]) ? $primer["utilidad"] : null
    );
}

$general = $modelo->canalesRecomendados(array("limite" => 120));
$tp40372 = $modelo->canalesRecomendados(array("q" => "TP-40372", "limite" => 120));
$tp40352 = $modelo->canalesRecomendados(array("q" => "TP-40352", "limite" => 120));
$conStock = $modelo->canalesRecomendados(array("stock" => "con_stock", "limite" => 120));
$subirPrecio = $modelo->canalesRecomendados(array("accion" => "subir_precio", "limite" => 120));

$salida = array(
    "general" => uatCanalesResumen($general),
    "tp40372" => uatCanalesResumen($tp40372),
    "tp40352" => uatCanalesResumen($tp40352),
    "con_stock" => uatCanalesResumen($conStock),
    "subir_precio" => uatCanalesResumen($subirPrecio)
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
if (intval($salida["tp40372"]["resumen"]["evaluados"]) !== 5 || intval($salida["tp40372"]["resumen"]["listos"]) !== 5) {
    $ok = false;
}
if (intval($salida["tp40352"]["resumen"]["evaluados"]) !== 2 || intval($salida["tp40352"]["resumen"]["listos"]) !== 2) {
    $ok = false;
}

echo json_encode(array(
    "ok" => $ok,
    "canales" => $salida
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
