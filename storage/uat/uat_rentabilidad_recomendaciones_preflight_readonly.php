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
    $respuesta = $modelo->preflightRecomendaciones($filtros);
    $salida[$clave] = array(
        "ok" => empty($respuesta["error"]),
        "resumen" => isset($respuesta["depurar"]["resumen"]) ? $respuesta["depurar"]["resumen"] : null,
        "primer_sku" => isset($respuesta["depurar"]["items"][0]["sku"]) ? $respuesta["depurar"]["items"][0]["sku"] : null,
        "primera_accion" => isset($respuesta["depurar"]["items"][0]["accion_preflight"]) ? $respuesta["depurar"]["items"][0]["accion_preflight"] : null
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
if (intval($salida["general"]["resumen"]["candidatos"]) < intval($salida["general"]["resumen"]["creables"])) {
    $ok = false;
}
if (intval($salida["subir_precio"]["resumen"]["candidatos"]) <= 0) {
    $ok = false;
}

echo json_encode(array(
    "ok" => $ok,
    "preflight_recomendaciones" => $salida
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

