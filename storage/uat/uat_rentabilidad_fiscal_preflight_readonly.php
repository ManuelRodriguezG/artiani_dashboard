<?php

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/RentabilidadErp.php";

$modelo = new RentabilidadErp();

$casos = array(
    "general" => array("canal" => "menudeo", "limite" => 120),
    "tp40372" => array("q" => "TP-40372", "canal" => "menudeo", "limite" => 120),
    "tp40352" => array("q" => "TP-40352", "canal" => "menudeo", "limite" => 120),
    "completar_fiscal" => array("accion" => "completar_fiscal", "canal" => "menudeo", "limite" => 120)
);

$salida = array();
foreach ($casos as $clave => $filtros) {
    $respuesta = $modelo->preflightFiscalCierre($filtros);
    $items = isset($respuesta["depurar"]["items"]) ? $respuesta["depurar"]["items"] : array();
    $salida[$clave] = array(
        "ok" => empty($respuesta["error"]),
        "resumen" => isset($respuesta["depurar"]["resumen"]) ? $respuesta["depurar"]["resumen"] : null,
        "primer_sku" => isset($items[0]["sku"]) ? $items[0]["sku"] : null,
        "primera_accion" => isset($items[0]["accion"]) ? $items[0]["accion"] : null
    );
}

$ok = true;
foreach ($salida as $caso) {
    if (empty($caso["ok"])) {
        $ok = false;
    }
}
if (intval($salida["general"]["resumen"]["evaluados"]) <= 0) {
    $ok = false;
}
if (intval($salida["tp40372"]["resumen"]["evaluados"]) <= 0 || intval($salida["tp40352"]["resumen"]["evaluados"]) <= 0) {
    $ok = false;
}

echo json_encode(array(
    "ok" => $ok,
    "preflight_fiscal" => $salida
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

