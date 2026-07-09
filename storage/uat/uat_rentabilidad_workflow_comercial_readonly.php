<?php

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/RentabilidadErp.php";

$modelo = new RentabilidadErp();

$casos = array(
    "general" => array("canal" => "menudeo", "limite" => 120),
    "tp40372" => array("q" => "TP-40372", "canal" => "menudeo", "limite" => 120),
    "subir_precio" => array("accion" => "subir_precio", "canal" => "menudeo", "limite" => 120)
);

$salida = array();
foreach ($casos as $clave => $filtros) {
    $respuesta = $modelo->workflowComercial($filtros);
    $bandejas = isset($respuesta["depurar"]["bandejas"]) ? $respuesta["depurar"]["bandejas"] : array();
    $salida[$clave] = array(
        "ok" => empty($respuesta["error"]),
        "resumen" => isset($respuesta["depurar"]["resumen"]) ? $respuesta["depurar"]["resumen"] : null,
        "crear_pendientes_estado" => isset($bandejas["crear_pendientes"]["estado"]) ? $bandejas["crear_pendientes"]["estado"] : null,
        "aprobar_precios_estado" => isset($bandejas["aprobar_precios"]["estado"]) ? $bandejas["aprobar_precios"]["estado"] : null
    );
}

$ok = true;
foreach ($salida as $caso) {
    if (empty($caso["ok"])) {
        $ok = false;
    }
}
if (intval($salida["general"]["resumen"]["prioridades"]) <= 0) {
    $ok = false;
}
if ($salida["general"]["aprobar_precios_estado"] !== "bloqueado") {
    $ok = false;
}

echo json_encode(array(
    "ok" => $ok,
    "workflow_comercial" => $salida
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

