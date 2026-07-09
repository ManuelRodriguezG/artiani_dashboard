<?php

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/modelos/RentabilidadErp.php";

$modelo = new RentabilidadErp();

function uatPlanCierreResumen($respuesta) {
    $depurar = isset($respuesta["depurar"]) ? $respuesta["depurar"] : array();
    $grupos = isset($depurar["grupos"]) ? $depurar["grupos"] : array();
    $primerGrupo = null;
    $primerSku = null;
    $primerPaso = null;

    foreach ($grupos as $clave => $grupo) {
        if (!empty($grupo["items"])) {
            $primerGrupo = $clave;
            $primerSku = isset($grupo["items"][0]["sku"]) ? $grupo["items"][0]["sku"] : null;
            $primerPaso = isset($grupo["items"][0]["siguiente_paso"]) ? $grupo["items"][0]["siguiente_paso"] : null;
            break;
        }
    }

    return array(
        "ok" => empty($respuesta["error"]),
        "resumen" => isset($depurar["resumen"]) ? $depurar["resumen"] : array(),
        "primer_grupo_no_vacio" => $primerGrupo,
        "primer_sku" => $primerSku,
        "primer_paso" => $primerPaso
    );
}

$general = $modelo->planCierreComercial(array("canal" => "menudeo", "limite" => 120));
$tp40372 = $modelo->planCierreComercial(array("q" => "TP-40372", "canal" => "menudeo", "limite" => 120));
$tp40352 = $modelo->planCierreComercial(array("q" => "TP-40352", "canal" => "menudeo", "limite" => 120));
$conStock = $modelo->planCierreComercial(array("stock" => "con_stock", "canal" => "menudeo", "limite" => 120));
$subirPrecio = $modelo->planCierreComercial(array("accion" => "subir_precio", "canal" => "menudeo", "limite" => 120));

$salida = array(
    "general" => uatPlanCierreResumen($general),
    "tp40372" => uatPlanCierreResumen($tp40372),
    "tp40352" => uatPlanCierreResumen($tp40352),
    "con_stock" => uatPlanCierreResumen($conStock),
    "subir_precio" => uatPlanCierreResumen($subirPrecio)
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
if (intval($salida["tp40372"]["resumen"]["evaluados"]) !== 5) {
    $ok = false;
}
if (intval($salida["tp40352"]["resumen"]["evaluados"]) !== 2) {
    $ok = false;
}
if (intval($salida["subir_precio"]["resumen"]["revisar_precio"]) < 1) {
    $ok = false;
}

echo json_encode(array(
    "ok" => $ok,
    "plan_cierre" => $salida
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
