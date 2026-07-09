<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-06-27.
 * Proposito: simular movimiento de caja POS no venta sin escribir BD.
 * Impacto: valida gastos, retiros, entradas, vales y reembolsos antes de autorizarlos.
 * Contrato: read-only; no inserta movimiento y no modifica esperado del turno.
 */

$args = isset($argv) ? $argv : array();
$idUsuario = 0;
$tipo = "";
$motivo = "";
$monto = 0;
$referencia = "";
$responsable = "";
$observaciones = "";

foreach ($args as $arg) {
    if (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--tipo=") === 0) {
        $tipo = trim(substr($arg, 7), "\"' ");
    } elseif (strpos($arg, "--motivo=") === 0) {
        $motivo = trim(substr($arg, 9), "\"' ");
    } elseif (strpos($arg, "--monto=") === 0) {
        $monto = floatval(trim(substr($arg, 8), "\"' "));
    } elseif (strpos($arg, "--referencia=") === 0) {
        $referencia = trim(substr($arg, 13), "\"' ");
    } elseif (strpos($arg, "--responsable=") === 0) {
        $responsable = trim(substr($arg, 14), "\"' ");
    } elseif (strpos($arg, "--observaciones=") === 0) {
        $observaciones = trim(substr($arg, 16), "\"' ");
    }
}

if ($idUsuario <= 0) {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "Falta --id_usuario=ID para ubicar asignacion POS."
    ));
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";

$ventas = new VentasErp();
$asignacion = $ventas->asignacionActualTerminalPos(array("id_usuario" => $idUsuario));
$depurarAsignacion = valor($asignacion, "depurar", array());
$datosAsignacion = valor($depurarAsignacion, "asignacion", array());
$turno = valor($depurarAsignacion, "turno_abierto", array());

if (!empty($asignacion["error"]) || empty($depurarAsignacion["asignacion_activa"]) || empty($turno)) {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "No hay asignacion POS activa o turno abierto para simular movimiento.",
        "asignacion" => $depurarAsignacion
    ));
}

$resultado = $ventas->movimientoCajaDryRun(array(
    "id_almacen" => intval(valor($datosAsignacion, "id_almacen", 0)),
    "id_caja" => intval(valor($datosAsignacion, "id_caja", 0)),
    "id_turno_caja" => intval(valor($turno, "id_turno_caja", 0)),
    "tipo_movimiento" => $tipo,
    "motivo" => $motivo,
    "monto" => $monto,
    "referencia" => $referencia,
    "responsable" => $responsable,
    "observaciones" => $observaciones
));

responder(array(
    "ok" => empty($resultado["error"]) && empty(valor(valor($resultado, "depurar", array()), "bloqueos", array())),
    "modo" => "caja_movimiento_dryrun_readonly",
    "id_usuario" => $idUsuario,
    "resultado" => $resultado
));

function valor($datos, $campo, $default = null) {
    return is_array($datos) && array_key_exists($campo, $datos) ? $datos[$campo] : $default;
}

function responder($datos) {
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}
