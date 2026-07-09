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
    $respuesta = $modelo->estadoModuloRentabilidad($filtros);
    $resumen = isset($respuesta["depurar"]["resumen"]) ? $respuesta["depurar"]["resumen"] : array();
    $componentes = isset($respuesta["depurar"]["componentes"]) ? $respuesta["depurar"]["componentes"] : array();
    $salida[$clave] = array(
        "ok" => empty($respuesta["error"]),
        "estado_general" => isset($resumen["estado_general"]) ? $resumen["estado_general"] : null,
        "resumen" => $resumen,
        "componentes" => array_keys($componentes),
        "aprobacion_precios" => isset($componentes["aprobacion_precios"]) ? $componentes["aprobacion_precios"] : null,
        "paquete_autorizacion" => isset($componentes["paquete_autorizacion"]) ? $componentes["paquete_autorizacion"] : null
    );
}

$ok = true;
foreach ($salida as $caso) {
    if (empty($caso["ok"]) || intval(isset($caso["resumen"]["componentes"]) ? $caso["resumen"]["componentes"] : 0) < 5) {
        $ok = false;
    }
}
if ($salida["general"]["estado_general"] !== "bloqueado") {
    $ok = false;
}

echo json_encode(array(
    "ok" => $ok,
    "modo" => "read-only",
    "estado_modulo" => $salida
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

