<?php

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/modelos/RentabilidadErp.php";

$modelo = new RentabilidadErp();

function uatChecklistResumen($respuesta) {
    $depurar = isset($respuesta["depurar"]) ? $respuesta["depurar"] : array();
    $checks = isset($depurar["checks"]) ? $depurar["checks"] : array();
    $porId = array();
    foreach ($checks as $check) {
        $porId[$check["id"]] = $check;
    }
    return array(
        "ok" => empty($respuesta["error"]),
        "resumen" => isset($depurar["resumen"]) ? $depurar["resumen"] : array(),
        "por_id" => $porId
    );
}

$general = $modelo->checklistCierreComercial(array("canal" => "menudeo", "limite" => 120));
$tp40372 = $modelo->checklistCierreComercial(array("q" => "TP-40372", "canal" => "menudeo", "limite" => 120));
$tp40352 = $modelo->checklistCierreComercial(array("q" => "TP-40352", "canal" => "menudeo", "limite" => 120));
$subirPrecio = $modelo->checklistCierreComercial(array("accion" => "subir_precio", "canal" => "menudeo", "limite" => 120));

$salida = array(
    "general" => uatChecklistResumen($general),
    "tp40372" => uatChecklistResumen($tp40372),
    "tp40352" => uatChecklistResumen($tp40352),
    "subir_precio" => uatChecklistResumen($subirPrecio)
);

$ok = true;
foreach ($salida as $item) {
    if (empty($item["ok"])) {
        $ok = false;
    }
}
if (intval($salida["general"]["resumen"]["evaluados"]) !== 120) {
    $ok = false;
}
if (intval(isset($salida["general"]["por_id"]["COST-CHK-004"]["total"]) ? $salida["general"]["por_id"]["COST-CHK-004"]["total"] : 0) !== 59) {
    $ok = false;
}
if (intval(isset($salida["tp40372"]["por_id"]["COST-CHK-004"]["total"]) ? $salida["tp40372"]["por_id"]["COST-CHK-004"]["total"] : 0) !== 5) {
    $ok = false;
}
if (intval(isset($salida["tp40352"]["por_id"]["COST-CHK-004"]["total"]) ? $salida["tp40352"]["por_id"]["COST-CHK-004"]["total"] : 0) !== 2) {
    $ok = false;
}
if (intval(isset($salida["subir_precio"]["por_id"]["COST-CHK-002"]["total"]) ? $salida["subir_precio"]["por_id"]["COST-CHK-002"]["total"] : 0) < 1) {
    $ok = false;
}

echo json_encode(array(
    "ok" => $ok,
    "checklist_cierre" => $salida
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
