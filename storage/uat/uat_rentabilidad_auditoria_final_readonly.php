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
    $respuesta = $modelo->auditoriaFinalModulo($filtros);
    $resumen = isset($respuesta["depurar"]["resumen"]) ? $respuesta["depurar"]["resumen"] : array();
    $criterios = isset($respuesta["depurar"]["criterios"]) ? $respuesta["depurar"]["criterios"] : array();
    $salida[$clave] = array(
        "ok" => empty($respuesta["error"]),
        "resumen" => $resumen,
        "criterios" => count($criterios),
        "siguiente_paso" => isset($respuesta["depurar"]["siguiente_paso"]) ? $respuesta["depurar"]["siguiente_paso"] : null
    );
}

$ok = true;
foreach ($salida as $caso) {
    if (empty($caso["ok"]) || intval($caso["criterios"]) < 6) {
        $ok = false;
    }
}
if ($salida["general"]["resumen"]["estado_construccion"] !== "completo_readonly") {
    $ok = false;
}
if ($salida["general"]["resumen"]["estado_uso_comercial"] !== "bloqueado") {
    $ok = false;
}

echo json_encode(array(
    "ok" => $ok,
    "modo" => "read-only",
    "auditoria_final" => $salida
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

