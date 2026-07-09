<?php

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/modelos/RentabilidadErp.php";

$modelo = new RentabilidadErp();

function uatDetalleRentabilidadResumen($respuesta) {
    $depurar = isset($respuesta["depurar"]) ? $respuesta["depurar"] : array();
    $item = isset($depurar["escenario_activo"]) ? $depurar["escenario_activo"] : array();
    $datos = isset($depurar["datos_base"]) ? $depurar["datos_base"] : array();
    $presentaciones = isset($depurar["presentaciones"]) ? $depurar["presentaciones"] : array();
    $fiscal = isset($depurar["fiscal_xml"]) ? $depurar["fiscal_xml"] : array();
    $snapshots = isset($depurar["snapshots"]) ? $depurar["snapshots"] : array();
    $dictamen = isset($depurar["dictamen_cierre"]) ? $depurar["dictamen_cierre"] : array();

    return array(
        "ok" => empty($respuesta["error"]),
        "sku" => isset($item["sku"]) ? $item["sku"] : null,
        "riesgo" => isset($item["riesgo_clave"]) ? $item["riesgo_clave"] : null,
        "costo" => isset($item["costo_real_sin_impuesto"]) ? $item["costo_real_sin_impuesto"] : null,
        "origen_costo" => isset($item["origen_costo"]) ? $item["origen_costo"] : null,
        "precio" => isset($item["precio_escenario_sin_impuesto"]) ? $item["precio_escenario_sin_impuesto"] : null,
        "utilidad" => isset($item["utilidad_estimada"]) ? $item["utilidad_estimada"] : null,
        "escenarios" => isset($depurar["escenarios"]) ? count($depurar["escenarios"]) : 0,
        "faltantes_fiscal" => isset($datos["faltantes_fiscal"]) ? count($datos["faltantes_fiscal"]) : null,
        "presentaciones" => intval(isset($presentaciones["total"]) ? $presentaciones["total"] : 0),
        "alertas_presentaciones" => intval(isset($presentaciones["alertas"]) ? $presentaciones["alertas"] : 0),
        "fiscal_xml_pendiente" => intval(isset($fiscal["total_fiscal_incompleto"]) ? $fiscal["total_fiscal_incompleto"] : 0),
        "snapshots_desfasados" => intval(isset($snapshots["desfasados"]) ? $snapshots["desfasados"] : 0),
        "dictamen_estado" => isset($dictamen["estado"]) ? $dictamen["estado"] : null,
        "dictamen_bloqueos" => isset($dictamen["bloqueos"]) ? count($dictamen["bloqueos"]) : null,
        "dictamen_alertas" => isset($dictamen["alertas"]) ? count($dictamen["alertas"]) : null,
        "aprobacion_estado" => isset($dictamen["aprobacion"]["estado"]) ? $dictamen["aprobacion"]["estado"] : null,
        "estado_modulo" => isset($dictamen["estado_modulo"]["estado_general"]) ? $dictamen["estado_modulo"]["estado_general"] : null
    );
}

$tp40372 = $modelo->detalleSku(array("q" => "TP-40372", "canal" => "menudeo"));
$tp40372_500 = $modelo->detalleSku(array("q" => "TP-40372-500GR", "canal" => "menudeo"));
$tp40352 = $modelo->detalleSku(array("q" => "TP-40352", "canal" => "menudeo"));
$incompleto = $modelo->detalleSku(array("q" => "0080", "canal" => "menudeo"));

$resumen = array(
    "tp40372" => uatDetalleRentabilidadResumen($tp40372),
    "tp40372_500gr" => uatDetalleRentabilidadResumen($tp40372_500),
    "tp40352" => uatDetalleRentabilidadResumen($tp40352),
    "incompleto_0080" => uatDetalleRentabilidadResumen($incompleto)
);

$ok = true;
foreach ($resumen as $item) {
    if (empty($item["ok"]) || intval($item["escenarios"]) !== 3) {
        $ok = false;
    }
    if ($item["dictamen_estado"] === null || $item["aprobacion_estado"] === null || $item["estado_modulo"] === null) {
        $ok = false;
    }
}
if ($resumen["tp40372"]["sku"] !== "TP-40372" || $resumen["tp40372"]["riesgo"] !== "rentable") {
    $ok = false;
}
if ($resumen["tp40372"]["dictamen_estado"] !== "bloqueado" || $resumen["tp40372"]["aprobacion_estado"] !== "bloqueados") {
    $ok = false;
}
if ($resumen["tp40352"]["sku"] !== "TP-40352" || $resumen["tp40352"]["riesgo"] !== "rentable") {
    $ok = false;
}
if ($resumen["incompleto_0080"]["riesgo"] !== "incompleto") {
    $ok = false;
}

echo json_encode(array(
    "ok" => $ok,
    "detalles" => $resumen
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
