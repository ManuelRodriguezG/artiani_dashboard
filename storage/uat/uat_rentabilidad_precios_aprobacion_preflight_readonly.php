<?php

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/RentabilidadErp.php";

$modelo = new RentabilidadErp();

$casos = array(
    "general" => array("canal" => "menudeo", "limite" => 120),
    "tp40372" => array("q" => "TP-40372", "canal" => "menudeo", "limite" => 120),
    "tp40352" => array("q" => "TP-40352", "canal" => "menudeo", "limite" => 120),
    "subir_precio" => array("accion" => "subir_precio", "canal" => "menudeo", "limite" => 120)
);

$salida = array();
foreach ($casos as $clave => $filtros) {
    $respuesta = $modelo->preflightAprobacionPrecios($filtros);
    $items = isset($respuesta["depurar"]["items"]) ? $respuesta["depurar"]["items"] : array();
    $salida[$clave] = array(
        "ok" => empty($respuesta["error"]),
        "resumen" => isset($respuesta["depurar"]["resumen"]) ? $respuesta["depurar"]["resumen"] : null,
        "primer_sku" => isset($items[0]["sku"]) ? $items[0]["sku"] : null,
        "primer_estado" => isset($items[0]["estado"]) ? $items[0]["estado"] : null,
        "primer_accion" => isset($items[0]["accion_precio"]) ? $items[0]["accion_precio"] : null
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
if (intval($salida["subir_precio"]["resumen"]["subir_precio"]) <= 0) {
    $ok = false;
}
if (intval($salida["tp40372"]["resumen"]["evaluados"]) <= 0 || intval($salida["tp40352"]["resumen"]["evaluados"]) <= 0) {
    $ok = false;
}

echo json_encode(array(
    "ok" => $ok,
    "preflight_aprobacion_precios" => $salida
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

