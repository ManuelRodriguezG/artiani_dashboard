<?php

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/modelos/RentabilidadErp.php";

$modelo = new RentabilidadErp();

function uatImpactoCierreResumen($respuesta) {
    $depurar = isset($respuesta["depurar"]) ? $respuesta["depurar"] : array();
    $grupos = isset($depurar["grupos"]) ? $depurar["grupos"] : array();
    return array(
        "ok" => empty($respuesta["error"]),
        "resumen" => isset($depurar["resumen"]) ? $depurar["resumen"] : array(),
        "completar_datos" => isset($grupos["completar_datos"]) ? $grupos["completar_datos"] : array(),
        "revisar_precio" => isset($grupos["revisar_precio"]) ? $grupos["revisar_precio"] : array(),
        "validar_costo" => isset($grupos["validar_costo"]) ? $grupos["validar_costo"] : array(),
        "completar_fiscal" => isset($grupos["completar_fiscal"]) ? $grupos["completar_fiscal"] : array(),
        "cerrar" => isset($grupos["cerrar"]) ? $grupos["cerrar"] : array()
    );
}

$general = $modelo->impactoCierreComercial(array("canal" => "menudeo", "limite" => 120));
$tp40372 = $modelo->impactoCierreComercial(array("q" => "TP-40372", "canal" => "menudeo", "limite" => 120));
$tp40352 = $modelo->impactoCierreComercial(array("q" => "TP-40352", "canal" => "menudeo", "limite" => 120));
$conStock = $modelo->impactoCierreComercial(array("stock" => "con_stock", "canal" => "menudeo", "limite" => 120));
$subirPrecio = $modelo->impactoCierreComercial(array("accion" => "subir_precio", "canal" => "menudeo", "limite" => 120));

$salida = array(
    "general" => uatImpactoCierreResumen($general),
    "tp40372" => uatImpactoCierreResumen($tp40372),
    "tp40352" => uatImpactoCierreResumen($tp40352),
    "con_stock" => uatImpactoCierreResumen($conStock),
    "subir_precio" => uatImpactoCierreResumen($subirPrecio)
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
if (intval($salida["tp40372"]["resumen"]["evaluados"]) !== 5 || intval($salida["tp40372"]["completar_fiscal"]["skus"]) !== 5) {
    $ok = false;
}
if (intval($salida["tp40352"]["resumen"]["evaluados"]) !== 2 || intval($salida["tp40352"]["completar_fiscal"]["skus"]) !== 2) {
    $ok = false;
}
if (intval($salida["subir_precio"]["revisar_precio"]["skus"]) < 1) {
    $ok = false;
}

echo json_encode(array(
    "ok" => $ok,
    "impacto_cierre" => $salida
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
