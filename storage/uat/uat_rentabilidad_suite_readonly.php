<?php

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/RentabilidadErp.php";

$modelo = new RentabilidadErp();

function uatRentabilidadSuiteOk($respuesta) {
    return empty($respuesta["error"]);
}

function uatRentabilidadSuiteResumen($respuesta, $ruta) {
    return array(
        "ok" => uatRentabilidadSuiteOk($respuesta),
        "ruta_logica" => $ruta,
        "mensaje" => isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : null
    );
}

$filtrosGenerales = array("canal" => "menudeo", "limite" => 120);
$filtrosTp40372 = array("q" => "TP-40372", "canal" => "menudeo", "limite" => 120);

$analisis = $modelo->analizarSkus($filtrosGenerales);
$presentaciones = $modelo->auditarCostosPresentaciones(array("q" => "TP-40372", "limite" => 20));
$detalleTp40372 = $modelo->detalleSku($filtrosTp40372);
$aprobacion = $modelo->preflightAprobacionPrecios($filtrosGenerales);
$aprobacionesInternas = $modelo->preflightAprobacionesInternas($filtrosGenerales);
$aprobacionesInternasListado = $modelo->listarAprobacionesInternas($filtrosGenerales);
$aprobacionesPaquete = $modelo->paqueteAutorizacionAprobaciones($filtrosGenerales);
$workflow = $modelo->workflowComercial($filtrosGenerales);
$estado = $modelo->estadoModuloRentabilidad($filtrosGenerales);
$usoComercial = $modelo->preflightUsoComercial($filtrosGenerales);
$desbloqueo = $modelo->planDesbloqueoComercial($filtrosGenerales);
$auditoria = $modelo->auditoriaFinalModulo($filtrosGenerales);

$resAnalisis = isset($analisis["depurar"]["resumen"]) ? $analisis["depurar"]["resumen"] : array();
$resPresentaciones = isset($presentaciones["depurar"]) ? $presentaciones["depurar"] : array();
$detalleItem = isset($detalleTp40372["depurar"]["escenario_activo"]) ? $detalleTp40372["depurar"]["escenario_activo"] : array();
$detalleDictamen = isset($detalleTp40372["depurar"]["dictamen_cierre"]) ? $detalleTp40372["depurar"]["dictamen_cierre"] : array();
$resAprobacion = isset($aprobacion["depurar"]["resumen"]) ? $aprobacion["depurar"]["resumen"] : array();
$resAprobacionesInternas = isset($aprobacionesInternas["depurar"]["resumen"]) ? $aprobacionesInternas["depurar"]["resumen"] : array();
$resAprobacionesInternasListado = isset($aprobacionesInternasListado["depurar"]["resumen"]) ? $aprobacionesInternasListado["depurar"]["resumen"] : array();
$resAprobacionesPaquete = isset($aprobacionesPaquete["depurar"]["resumen"]) ? $aprobacionesPaquete["depurar"]["resumen"] : array();
$resWorkflow = isset($workflow["depurar"]["resumen"]) ? $workflow["depurar"]["resumen"] : array();
$resEstado = isset($estado["depurar"]["resumen"]) ? $estado["depurar"]["resumen"] : array();
$resUso = isset($usoComercial["depurar"]["resumen"]) ? $usoComercial["depurar"]["resumen"] : array();
$resDesbloqueo = isset($desbloqueo["depurar"]["resumen"]) ? $desbloqueo["depurar"]["resumen"] : array();
$resAuditoria = isset($auditoria["depurar"]["resumen"]) ? $auditoria["depurar"]["resumen"] : array();

$checks = array(
    "analisis_general" => uatRentabilidadSuiteResumen($analisis, "analizarSkus"),
    "presentaciones_tp40372" => uatRentabilidadSuiteResumen($presentaciones, "auditarCostosPresentaciones"),
    "detalle_tp40372" => uatRentabilidadSuiteResumen($detalleTp40372, "detalleSku"),
    "aprobacion_precios" => uatRentabilidadSuiteResumen($aprobacion, "preflightAprobacionPrecios"),
    "aprobaciones_internas" => uatRentabilidadSuiteResumen($aprobacionesInternas, "preflightAprobacionesInternas"),
    "aprobaciones_internas_listado" => uatRentabilidadSuiteResumen($aprobacionesInternasListado, "listarAprobacionesInternas"),
    "aprobaciones_paquete_autorizacion" => uatRentabilidadSuiteResumen($aprobacionesPaquete, "paqueteAutorizacionAprobaciones"),
    "workflow" => uatRentabilidadSuiteResumen($workflow, "workflowComercial"),
    "estado_modulo" => uatRentabilidadSuiteResumen($estado, "estadoModuloRentabilidad"),
    "uso_comercial" => uatRentabilidadSuiteResumen($usoComercial, "preflightUsoComercial"),
    "plan_desbloqueo" => uatRentabilidadSuiteResumen($desbloqueo, "planDesbloqueoComercial"),
    "auditoria_final" => uatRentabilidadSuiteResumen($auditoria, "auditoriaFinalModulo")
);

$ok = true;
foreach ($checks as $check) {
    if (empty($check["ok"])) {
        $ok = false;
    }
}
if (intval(isset($resAnalisis["skus"]) ? $resAnalisis["skus"] : 0) <= 0) {
    $ok = false;
}
if (intval(isset($resPresentaciones["alertas"]) ? $resPresentaciones["alertas"] : 0) !== 0) {
    $ok = false;
}
if (isset($detalleItem["sku"]) && $detalleItem["sku"] !== "TP-40372") {
    $ok = false;
}
if (isset($detalleDictamen["estado"]) && $detalleDictamen["estado"] !== "bloqueado") {
    $ok = false;
}
if (isset($resAprobacion["aprobables"]) && intval($resAprobacion["aprobables"]) !== 0) {
    $ok = false;
}
if (isset($resAprobacionesInternas["creables"]) && intval($resAprobacionesInternas["creables"]) !== 0) {
    $ok = false;
}
if (isset($resAprobacionesPaquete["estado"]) && $resAprobacionesPaquete["estado"] !== "requiere_autorizacion_esquema") {
    $ok = false;
}
if (isset($resEstado["estado_general"]) && $resEstado["estado_general"] !== "bloqueado") {
    $ok = false;
}
if (isset($resUso["estado_general"]) && $resUso["estado_general"] !== "bloqueado") {
    $ok = false;
}
if (isset($resAuditoria["estado_construccion"]) && $resAuditoria["estado_construccion"] !== "completo_readonly") {
    $ok = false;
}

echo json_encode(array(
    "ok" => $ok,
    "modo" => "read-only",
    "checks" => $checks,
    "resumen" => array(
        "skus_analizados" => intval(isset($resAnalisis["skus"]) ? $resAnalisis["skus"] : 0),
        "perdida" => intval(isset($resAnalisis["perdida"]) ? $resAnalisis["perdida"] : 0),
        "presentaciones_tp40372_alertas" => intval(isset($resPresentaciones["alertas"]) ? $resPresentaciones["alertas"] : 0),
        "tp40372_costo" => isset($detalleItem["costo_real_sin_impuesto"]) ? $detalleItem["costo_real_sin_impuesto"] : null,
        "tp40372_dictamen" => isset($detalleDictamen["estado"]) ? $detalleDictamen["estado"] : null,
        "aprobables" => intval(isset($resAprobacion["aprobables"]) ? $resAprobacion["aprobables"] : 0),
        "aprobaciones_internas_creables" => intval(isset($resAprobacionesInternas["creables"]) ? $resAprobacionesInternas["creables"] : 0),
        "aprobaciones_internas_schema" => intval(isset($resAprobacionesInternasListado["schema_disponible"]) ? $resAprobacionesInternasListado["schema_disponible"] : 0),
        "aprobaciones_paquete_estado" => isset($resAprobacionesPaquete["estado"]) ? $resAprobacionesPaquete["estado"] : null,
        "workflow_requiere_autorizacion" => intval(isset($resWorkflow["requiere_autorizacion"]) ? $resWorkflow["requiere_autorizacion"] : 0),
        "estado_modulo" => isset($resEstado["estado_general"]) ? $resEstado["estado_general"] : null,
        "uso_comercial" => isset($resUso["estado_general"]) ? $resUso["estado_general"] : null,
        "acciones_desbloqueo" => intval(isset($resDesbloqueo["acciones"]) ? $resDesbloqueo["acciones"] : 0),
        "construccion" => isset($resAuditoria["estado_construccion"]) ? $resAuditoria["estado_construccion"] : null
    )
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
