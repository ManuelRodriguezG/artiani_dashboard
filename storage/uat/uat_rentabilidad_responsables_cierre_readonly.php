<?php

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/modelos/RentabilidadErp.php";

$modelo = new RentabilidadErp();

function uatResponsablesResumen($respuesta) {
    $depurar = isset($respuesta["depurar"]) ? $respuesta["depurar"] : array();
    $items = isset($depurar["items"]) ? $depurar["items"] : array();
    $porResponsable = array();
    foreach ($items as $item) {
        $porResponsable[$item["responsable"]] = $item;
    }
    return array(
        "ok" => empty($respuesta["error"]),
        "resumen" => isset($depurar["resumen"]) ? $depurar["resumen"] : array(),
        "primer_responsable" => isset($items[0]["responsable"]) ? $items[0]["responsable"] : null,
        "primer_skus" => isset($items[0]["skus"]) ? $items[0]["skus"] : null,
        "por_responsable" => $porResponsable
    );
}

$general = $modelo->responsablesCierreComercial(array("canal" => "menudeo", "limite" => 120));
$tp40372 = $modelo->responsablesCierreComercial(array("q" => "TP-40372", "canal" => "menudeo", "limite" => 120));
$tp40352 = $modelo->responsablesCierreComercial(array("q" => "TP-40352", "canal" => "menudeo", "limite" => 120));
$subirPrecio = $modelo->responsablesCierreComercial(array("accion" => "subir_precio", "canal" => "menudeo", "limite" => 120));

$salida = array(
    "general" => uatResponsablesResumen($general),
    "tp40372" => uatResponsablesResumen($tp40372),
    "tp40352" => uatResponsablesResumen($tp40352),
    "subir_precio" => uatResponsablesResumen($subirPrecio)
);

$ok = true;
foreach ($salida as $item) {
    if (empty($item["ok"])) {
        $ok = false;
    }
}
if (intval($salida["general"]["resumen"]["prioridades"]) !== 120) {
    $ok = false;
}
if (intval(isset($salida["tp40372"]["por_responsable"]["Catalogo/Fiscal"]["skus"]) ? $salida["tp40372"]["por_responsable"]["Catalogo/Fiscal"]["skus"] : 0) !== 5) {
    $ok = false;
}
if (intval(isset($salida["tp40352"]["por_responsable"]["Catalogo/Fiscal"]["skus"]) ? $salida["tp40352"]["por_responsable"]["Catalogo/Fiscal"]["skus"] : 0) !== 2) {
    $ok = false;
}
if (intval(isset($salida["subir_precio"]["por_responsable"]["Direccion/Comercial"]["alta"]) ? $salida["subir_precio"]["por_responsable"]["Direccion/Comercial"]["alta"] : 0) < 1) {
    $ok = false;
}

echo json_encode(array(
    "ok" => $ok,
    "responsables_cierre" => $salida
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
