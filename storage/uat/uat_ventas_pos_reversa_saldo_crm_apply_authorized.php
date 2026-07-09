<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-07.
 * Proposito: ejecutar reversa POS real con componentes financieros caja + saldo CRM solo con autorizacion explicita.
 * Impacto: puede crear devolucion, reembolso de caja, reintegro CRM, detalle financiero e inventario segun decision.
 * Contrato: BLOQUEADO por defecto; requiere token fuerte y respaldo UAT vigente o respaldo externo real.
 */

$args = isset($argv) ? $argv : array();
$autorizar = "";
$respaldo = "";
$respaldoUatVigente = false;
$idUsuario = 0;
$folio = "";
$idVenta = 0;
$idVentaDetalle = 0;
$cantidad = 0;
$tipo = "devolucion";
$motivo = "";
$decisionInventario = "cuarentena";
$decisionFinanciera = "mixta_saldo_crm";
$observaciones = "UAT reversa POS con saldo CRM real";

foreach ($args as $arg) {
    if (strpos($arg, "--autorizar=") === 0) {
        $autorizar = trim(substr($arg, 12), "\"' ");
    } elseif (strpos($arg, "--respaldo=") === 0) {
        $respaldo = trim(substr($arg, 11), "\"' ");
    } elseif ($arg === "--respaldo_uat_vigente=1") {
        $respaldoUatVigente = true;
    } elseif (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--folio=") === 0) {
        $folio = trim(substr($arg, 8), "\"' ");
    } elseif (strpos($arg, "--id_venta=") === 0) {
        $idVenta = intval(trim(substr($arg, 11), "\"' "));
    } elseif (strpos($arg, "--id_venta_detalle=") === 0) {
        $idVentaDetalle = intval(trim(substr($arg, 19), "\"' "));
    } elseif (strpos($arg, "--cantidad=") === 0) {
        $cantidad = floatval(trim(substr($arg, 11), "\"' "));
    } elseif (strpos($arg, "--tipo=") === 0) {
        $tipo = trim(substr($arg, 7), "\"' ");
    } elseif (strpos($arg, "--motivo=") === 0) {
        $motivo = trim(substr($arg, 9), "\"' ");
    } elseif (strpos($arg, "--decision_inventario=") === 0) {
        $decisionInventario = trim(substr($arg, 22), "\"' ");
    } elseif (strpos($arg, "--decision_financiera=") === 0) {
        $decisionFinanciera = trim(substr($arg, 22), "\"' ");
    } elseif (strpos($arg, "--observaciones=") === 0) {
        $observaciones = trim(substr($arg, 16), "\"' ");
    }
}

$respaldoValido = $respaldo !== "" && (is_file($respaldo) || ($respaldoUatVigente && strtolower($respaldo) === "uat_pos_vigente"));
$decisionesPermitidas = array("mixta_saldo_crm", "reintegro_saldo_crm");
if ($autorizar !== "VENTAS_POS_REVERSA_SALDO_CRM_REAL"
    || !$respaldoValido
    || $idUsuario <= 0
    || ($folio === "" && $idVenta <= 0)
    || $idVentaDetalle <= 0
    || $cantidad <= 0
    || $motivo === ""
    || !in_array($decisionFinanciera, $decisionesPermitidas, true)) {
    responder(array(
        "ok" => false,
        "bloqueado" => true,
        "modo" => "guardrail",
        "mensaje" => "No se ejecuto reversa POS saldo CRM real. Falta token, respaldo, venta, detalle, cantidad, motivo o decision permitida.",
        "requisitos" => array(
            "--autorizar=VENTAS_POS_REVERSA_SALDO_CRM_REAL",
            "--respaldo=UAT_POS_VIGENTE --respaldo_uat_vigente=1 o --respaldo=RUTA_SQL",
            "--id_usuario=ID_OPERADOR_POS",
            "--folio=FOLIO_VENTA o --id_venta=ID_VENTA",
            "--id_venta_detalle=ID_DETALLE_ORIGINAL",
            "--cantidad=CANTIDAD_A_REVERSAR",
            "--decision_inventario=cuarentena|reintegrar|merma|sin_reingreso",
            "--decision_financiera=mixta_saldo_crm|reintegro_saldo_crm",
            "--motivo=MOTIVO_OPERATIVO"
        ),
        "recibido" => array(
            "autorizar" => $autorizar,
            "respaldo" => $respaldo,
            "respaldo_valido" => $respaldoValido,
            "decision_financiera" => $decisionFinanciera
        )
    ));
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";

$ventas = new VentasErp();
$datos = array(
    "id_usuario" => $idUsuario,
    "folio" => $folio,
    "id_venta" => $idVenta,
    "tipo" => $tipo,
    "motivo" => $motivo,
    "decision_inventario" => $decisionInventario,
    "decision_financiera" => $decisionFinanciera,
    "observaciones" => $observaciones,
    "items" => json_encode(array(array(
        "id_venta_detalle" => $idVentaDetalle,
        "cantidad_base" => $cantidad
    )))
);

$respuesta = $ventas->confirmarReversaPosReal($datos);
$depurar = isset($respuesta["depurar"]) && is_array($respuesta["depurar"]) ? $respuesta["depurar"] : array();
$ok = empty($respuesta["error"]) && isset($respuesta["tipo"]) && $respuesta["tipo"] === "success";
responder(array(
    "ok" => $ok,
    "modo" => "ventas_pos_reversa_saldo_crm_real_uat",
    "respaldo_ref" => $respaldo,
    "payload" => array(
        "folio" => $folio,
        "id_venta" => $idVenta,
        "id_venta_detalle" => $idVentaDetalle,
        "cantidad" => $cantidad,
        "tipo" => $tipo,
        "decision_inventario" => $decisionInventario,
        "decision_financiera" => $decisionFinanciera
    ),
    "respuesta" => $respuesta,
    "id_devolucion" => isset($depurar["id_devolucion"]) ? $depurar["id_devolucion"] : null,
    "folio_devolucion" => isset($depurar["folio_devolucion"]) ? $depurar["folio_devolucion"] : null,
    "componentes_financieros" => isset($depurar["componentes_financieros"]) ? $depurar["componentes_financieros"] : array(),
    "siguiente_paso" => $ok
        ? "Validar post-readonly de devolucion, componentes financieros, caja, saldo CRM e inventario."
        : "Resolver bloqueo/error antes de repetir la reversa real."
));

function responder($datos) {
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit(empty($datos["ok"]) ? 1 : 0);
}
