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
    $respuesta = $modelo->planDesbloqueoComercial($filtros);
    $resumen = isset($respuesta["depurar"]["resumen"]) ? $respuesta["depurar"]["resumen"] : array();
    $acciones = isset($respuesta["depurar"]["acciones"]) ? $respuesta["depurar"]["acciones"] : array();
    $salida[$clave] = array(
        "ok" => empty($respuesta["error"]),
        "resumen" => $resumen,
        "primera_accion" => isset($acciones[0]) ? array(
            "id" => $acciones[0]["id"],
            "prioridad" => $acciones[0]["prioridad"],
            "responsable" => $acciones[0]["responsable"],
            "casos" => $acciones[0]["casos"]
        ) : null
    );
}

$ok = true;
foreach ($salida as $caso) {
    if (empty($caso["ok"]) || intval(isset($caso["resumen"]["acciones"]) ? $caso["resumen"]["acciones"] : 0) <= 0) {
        $ok = false;
    }
}
if ($salida["general"]["resumen"]["estado_general"] !== "bloqueado") {
    $ok = false;
}

echo json_encode(array(
    "ok" => $ok,
    "modo" => "read-only",
    "plan_desbloqueo" => $salida
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

