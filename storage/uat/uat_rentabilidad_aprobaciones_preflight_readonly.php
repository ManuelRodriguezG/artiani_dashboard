<?php

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/RentabilidadErp.php";

$modelo = new RentabilidadErp();

$casos = array(
    "general" => array("canal" => "menudeo", "limite" => 120),
    "tp40372" => array("q" => "TP-40372", "canal" => "menudeo", "limite" => 120)
);

$salida = array();
foreach ($casos as $clave => $filtros) {
    $respuesta = $modelo->preflightAprobacionesInternas($filtros);
    $resumen = isset($respuesta["depurar"]["resumen"]) ? $respuesta["depurar"]["resumen"] : array();
    $items = isset($respuesta["depurar"]["items"]) ? $respuesta["depurar"]["items"] : array();
    $salida[$clave] = array(
        "ok" => empty($respuesta["error"]),
        "resumen" => $resumen,
        "primer_item" => isset($items[0]) ? array(
            "sku" => $items[0]["sku"],
            "accion_preflight" => $items[0]["accion_preflight"],
            "estado_precio" => $items[0]["estado_precio"],
            "evidencia_disponible" => isset($items[0]["evidencia"]["disponible"]) ? $items[0]["evidencia"]["disponible"] : null
        ) : null
    );
}

$ok = true;
foreach ($salida as $caso) {
    if (empty($caso["ok"]) || intval(isset($caso["resumen"]["evaluados"]) ? $caso["resumen"]["evaluados"] : 0) <= 0) {
        $ok = false;
    }
}
if (intval($salida["general"]["resumen"]["creables"]) !== 0) {
    $ok = false;
}
if (intval($salida["general"]["resumen"]["schema_disponible"]) !== 0) {
    $ok = false;
}
if (intval($salida["tp40372"]["resumen"]["bloqueados"]) <= 0) {
    $ok = false;
}

echo json_encode(array(
    "ok" => $ok,
    "modo" => "read-only",
    "aprobaciones_internas" => $salida
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

