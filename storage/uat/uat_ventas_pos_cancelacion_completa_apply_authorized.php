<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-10-13.
 * Proposito: ejecutar cancelacion completa de una venta POS desde script autorizado.
 * Impacto: crea folio CAN, registra reversa financiera, marca venta/detalle como cancelados y cierra incidencias POS ligadas.
 * Contrato: BLOQUEADO por defecto; requiere token fuerte, usuario, venta, motivo y referencia de respaldo externo.
 */

$args = isset($argv) ? $argv : array();
$autorizar = "";
$respaldo = "";
$respaldoRef = "";
$idUsuario = 0;
$folio = "";
$idVenta = 0;
$motivo = "";
$decisionInventario = "sin_reingreso";
$decisionFinanciera = "reembolso_caja";
$observaciones = "Cancelacion completa POS autorizada";

foreach ($args as $arg) {
    if (strpos($arg, "--autorizar=") === 0) {
        $autorizar = trim(substr($arg, 12), "\"' ");
    } elseif (strpos($arg, "--respaldo=") === 0) {
        $respaldo = trim(substr($arg, 11), "\"' ");
    } elseif (strpos($arg, "--respaldo_ref=") === 0) {
        $respaldoRef = trim(substr($arg, 15), "\"' ");
    } elseif (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--folio=") === 0) {
        $folio = trim(substr($arg, 8), "\"' ");
    } elseif (strpos($arg, "--id_venta=") === 0) {
        $idVenta = intval(trim(substr($arg, 11), "\"' "));
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

$respaldoValido = ($respaldo !== "" && is_file($respaldo)) || $respaldoRef !== "";
if ($autorizar !== "VENTAS_POS_CANCELACION_COMPLETA_REAL"
    || $idUsuario <= 0
    || !$respaldoValido
    || ($folio === "" && $idVenta <= 0)
    || $motivo === "") {
    responder(array(
        "ok" => false,
        "bloqueado" => true,
        "modo" => "guardrail",
        "mensaje" => "No se ejecuto cancelacion completa POS. Falta autorizacion, respaldo, venta o motivo.",
        "requisitos" => array(
            "--autorizar=VENTAS_POS_CANCELACION_COMPLETA_REAL",
            "--respaldo=RUTA_RESPALDO_EXISTENTE o --respaldo_ref=RESPALDO_PRODUCTIVO_VIGENTE",
            "--id_usuario=ID_OPERADOR_POS",
            "--folio=FOLIO_VENTA o --id_venta=ID_VENTA",
            "--decision_inventario=sin_reingreso|cuarentena|reintegrar|merma",
            "--decision_financiera=reembolso_caja|saldo_favor|sin_reembolso|cambio_producto",
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
    "tipo" => "cancelacion",
    "motivo" => $motivo,
    "decision_inventario" => $decisionInventario,
    "decision_financiera" => $decisionFinanciera,
    "observaciones" => $observaciones
);

$respuesta = $ventas->confirmarReversaPosReal($datos);
$depurar = isset($respuesta["depurar"]) && is_array($respuesta["depurar"]) ? $respuesta["depurar"] : array();
responder(array(
    "ok" => empty($respuesta["error"]) && isset($respuesta["tipo"]) && $respuesta["tipo"] === "success",
    "modo" => "ventas_pos_cancelacion_completa_real",
    "respaldo_ref" => $respaldo !== "" ? $respaldo : $respaldoRef,
    "payload" => array(
        "folio" => $folio,
        "id_venta" => $idVenta,
        "tipo" => "cancelacion",
        "decision_inventario" => $decisionInventario,
        "decision_financiera" => $decisionFinanciera
    ),
    "respuesta" => $respuesta,
    "id_devolucion" => isset($depurar["id_devolucion"]) ? $depurar["id_devolucion"] : null,
    "folio_devolucion" => isset($depurar["folio_devolucion"]) ? $depurar["folio_devolucion"] : null,
    "incidencias_canceladas" => isset($depurar["incidencias_canceladas"]) ? $depurar["incidencias_canceladas"] : array(),
    "siguiente_paso" => empty($respuesta["error"])
        ? "Recapturar la venta con el almacen correcto y validar que no queden pendientes activos del folio original."
        : "Resolver bloqueo/error antes de repetir la cancelacion real."
));

function responder($datos) {
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}
