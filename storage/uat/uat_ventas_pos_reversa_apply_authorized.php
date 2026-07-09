<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-06-30.
 * Proposito: ejecutar una reversa POS real desde UAT usando el modelo ERP de ventas.
 * Impacto: crea devolucion/cancelacion, puede generar saldo a favor o reembolso de caja y puede mover inventario si se autoriza reintegro.
 * Contrato: BLOQUEADO por defecto; requiere token fuerte, respaldo externo, usuario y dry-run valido.
 */

$args = isset($argv) ? $argv : array();
$autorizar = "";
$respaldo = "";
$idUsuario = 0;
$folio = "";
$idVenta = 0;
$idVentaDetalle = 0;
$cantidad = 0;
$tipo = "devolucion";
$motivo = "";
$decisionInventario = "cuarentena";
$decisionFinanciera = "saldo_favor";
$observaciones = "UAT reversa POS real";

foreach ($args as $arg) {
    if (strpos($arg, "--autorizar=") === 0) {
        $autorizar = trim(substr($arg, 12), "\"' ");
    } elseif (strpos($arg, "--respaldo=") === 0) {
        $respaldo = trim(substr($arg, 11), "\"' ");
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

if ($autorizar !== "VENTAS_POS_REVERSA_REAL"
    || $idUsuario <= 0
    || $respaldo === ""
    || !is_file($respaldo)
    || ($folio === "" && $idVenta <= 0)
    || $idVentaDetalle <= 0
    || $cantidad <= 0
    || $motivo === "") {
    responder(array(
        "ok" => false,
        "bloqueado" => true,
        "modo" => "guardrail",
        "mensaje" => "No se ejecuto reversa POS real. Falta autorizacion, respaldo, venta, detalle, cantidad o motivo.",
        "requisitos" => array(
            "--autorizar=VENTAS_POS_REVERSA_REAL",
            "--respaldo=RUTA_RESPALDO_EXISTENTE",
            "--id_usuario=ID_OPERADOR_POS",
            "--folio=FOLIO_VENTA o --id_venta=ID_VENTA",
            "--id_venta_detalle=ID_DETALLE_ORIGINAL",
            "--cantidad=CANTIDAD_A_REVERSAR",
            "--tipo=devolucion|cancelacion",
            "--decision_inventario=cuarentena|reintegrar|merma|sin_reingreso",
            "--decision_financiera=saldo_favor|reembolso_caja|cambio_producto|sin_reembolso",
            "--motivo=MOTIVO_OPERATIVO"
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
responder(array(
    "ok" => empty($respuesta["error"]) && isset($respuesta["tipo"]) && $respuesta["tipo"] === "success",
    "modo" => "ventas_pos_reversa_real_uat",
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
    "siguiente_paso" => empty($respuesta["error"])
        ? "Validar venta original, devolucion, detalle, caja/inventario segun decision y ticket."
        : "Resolver bloqueo/error antes de repetir la reversa real."
));

function responder($datos) {
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}
