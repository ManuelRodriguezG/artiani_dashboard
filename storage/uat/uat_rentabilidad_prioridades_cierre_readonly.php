<?php

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/modelos/RentabilidadErp.php";

$modelo = new RentabilidadErp();

function uatPrioridadesResumen($respuesta) {
    $depurar = isset($respuesta["depurar"]) ? $respuesta["depurar"] : array();
    $items = isset($depurar["items"]) ? $depurar["items"] : array();
    $primer = isset($items[0]) ? $items[0] : array();
    return array(
        "ok" => empty($respuesta["error"]),
        "resumen" => isset($depurar["resumen"]) ? $depurar["resumen"] : array(),
        "primer_sku" => isset($primer["sku"]) ? $primer["sku"] : null,
        "primer_grupo" => isset($primer["grupo"]) ? $primer["grupo"] : null,
        "primer_nivel" => isset($primer["nivel"]) ? $primer["nivel"] : null,
        "primer_score" => isset($primer["score"]) ? $primer["score"] : null,
        "primer_responsable" => isset($primer["responsable_sugerido"]) ? $primer["responsable_sugerido"] : null
    );
}

$general = $modelo->prioridadesCierreComercial(array("canal" => "menudeo", "limite" => 120));
$tp40372 = $modelo->prioridadesCierreComercial(array("q" => "TP-40372", "canal" => "menudeo", "limite" => 120));
$tp40352 = $modelo->prioridadesCierreComercial(array("q" => "TP-40352", "canal" => "menudeo", "limite" => 120));
$subirPrecio = $modelo->prioridadesCierreComercial(array("accion" => "subir_precio", "canal" => "menudeo", "limite" => 120));

$salida = array(
    "general" => uatPrioridadesResumen($general),
    "tp40372" => uatPrioridadesResumen($tp40372),
    "tp40352" => uatPrioridadesResumen($tp40352),
    "subir_precio" => uatPrioridadesResumen($subirPrecio)
);

$ok = true;
foreach ($salida as $item) {
    if (empty($item["ok"])) {
        $ok = false;
    }
}
if (intval($salida["general"]["resumen"]["evaluados"]) !== 120 || intval($salida["general"]["resumen"]["prioridades"]) !== 120) {
    $ok = false;
}
if (intval($salida["tp40372"]["resumen"]["prioridades"]) !== 5 || $salida["tp40372"]["primer_grupo"] !== "completar_fiscal") {
    $ok = false;
}
if (intval($salida["tp40352"]["resumen"]["prioridades"]) !== 2 || $salida["tp40352"]["primer_grupo"] !== "completar_fiscal") {
    $ok = false;
}
if (intval($salida["subir_precio"]["resumen"]["alta"]) < 1) {
    $ok = false;
}

echo json_encode(array(
    "ok" => $ok,
    "prioridades_cierre" => $salida
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
