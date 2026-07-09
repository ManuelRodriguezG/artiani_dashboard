<?php

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/RentabilidadErp.php";

$modelo = new RentabilidadErp();

$respuesta = $modelo->analizarSkus(array("q" => "", "canal" => "menudeo"));
$items = isset($respuesta["depurar"]["items"]) && is_array($respuesta["depurar"]["items"]) ? $respuesta["depurar"]["items"] : array();

$casos = array(
    "primer_sku" => isset($items[0]) ? $items[0] : null,
    "perdida" => primerCaso($items, "perdida_estimada"),
    "margen_bajo" => primerCaso($items, "margen_bajo"),
    "sin_costo" => primerCaso($items, "sin_costo"),
    "sin_precio" => primerCaso($items, "sin_precio"),
    "fiscal_incompleto" => primerCaso($items, "fiscal_incompleto")
);

$skusInventario = array("SAL-50L", "TP-7838", "SHF-600", "SP-2823");
$evidenciaInventario = array();
foreach ($skusInventario as $sku) {
    $consulta = $modelo->analizarSkus(array("q" => $sku, "canal" => "menudeo"));
    $evidenciaInventario[$sku] = array(
        "ok" => !$consulta["error"],
        "total" => isset($consulta["depurar"]["items"]) ? count($consulta["depurar"]["items"]) : 0,
        "primer_sku" => isset($consulta["depurar"]["items"][0]["sku"]) ? $consulta["depurar"]["items"][0]["sku"] : null,
        "origen_costo" => isset($consulta["depurar"]["items"][0]["origen_costo"]) ? $consulta["depurar"]["items"][0]["origen_costo"] : null,
        "disponible" => isset($consulta["depurar"]["items"][0]["inventario"]["disponible_total"]) ? $consulta["depurar"]["items"][0]["inventario"]["disponible_total"] : null,
        "riesgo" => isset($consulta["depurar"]["items"][0]["riesgo_clave"]) ? $consulta["depurar"]["items"][0]["riesgo_clave"] : null
    );
}

$comparacion = $modelo->compararEscenariosSku(array("q" => "TP-40372-100GR"));
$recomendaciones = $modelo->recomendacionesOperativas(array("q" => "", "limite" => 120));

$ok = !$respuesta["error"]
    && isset($respuesta["depurar"]["resumen"])
    && isset($respuesta["depurar"]["items"])
    && is_array($respuesta["depurar"]["items"])
    && isset($items[0]["hallazgos_detalle"])
    && !$comparacion["error"]
    && isset($comparacion["depurar"]["escenarios"])
    && count($comparacion["depurar"]["escenarios"]) === 3
    && !$recomendaciones["error"]
    && isset($recomendaciones["depurar"]["grupos"]);

echo json_encode(array(
    "ok" => $ok,
    "mensaje" => $respuesta["mensaje"],
    "resumen" => isset($respuesta["depurar"]["resumen"]) ? $respuesta["depurar"]["resumen"] : null,
    "casos" => resumenCasos($casos),
    "inventario_skus" => $evidenciaInventario,
    "comparacion_tp40372_100gr" => resumenComparacion($comparacion),
    "recomendaciones" => resumenRecomendaciones($recomendaciones)
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

function primerCaso($items, $clave) {
    foreach ($items as $item) {
        if (isset($item["hallazgos"]) && in_array($clave, $item["hallazgos"], true)) {
            return $item;
        }
    }
    return null;
}

function resumenCasos($casos) {
    $resumen = array();
    foreach ($casos as $clave => $item) {
        $resumen[$clave] = $item ? array(
            "sku" => $item["sku"],
            "riesgo" => $item["riesgo_clave"],
            "costo" => $item["costo_real_sin_impuesto"],
            "precio" => $item["precio_escenario_sin_impuesto"],
            "margen" => $item["margen_bruto_pct"],
            "hallazgos" => $item["hallazgos"]
        ) : null;
    }
    return $resumen;
}

function resumenComparacion($respuesta) {
    if ($respuesta["error"]) {
        return array("ok" => false, "mensaje" => $respuesta["mensaje"]);
    }
    $items = array();
    foreach ($respuesta["depurar"]["escenarios"] as $item) {
        $items[] = array(
            "canal" => $item["canal"],
            "precio" => $item["precio_escenario_sin_impuesto"],
            "utilidad" => $item["utilidad_estimada"],
            "margen" => $item["margen_bruto_pct"],
            "riesgo" => $item["riesgo_clave"]
        );
    }
    return array(
        "ok" => true,
        "sku" => $respuesta["depurar"]["sku"]["sku"],
        "escenarios" => $items
    );
}

function resumenRecomendaciones($respuesta) {
    if ($respuesta["error"]) {
        return array("ok" => false, "mensaje" => $respuesta["mensaje"]);
    }
    $salida = array("ok" => true, "total_skus_evaluados" => $respuesta["depurar"]["total_skus_evaluados"], "grupos" => array());
    foreach ($respuesta["depurar"]["grupos"] as $clave => $grupo) {
        $salida["grupos"][$clave] = array(
            "total" => $grupo["total"],
            "primer_sku" => isset($grupo["items"][0]["sku"]) ? $grupo["items"][0]["sku"] : null
        );
    }
    return $salida;
}
