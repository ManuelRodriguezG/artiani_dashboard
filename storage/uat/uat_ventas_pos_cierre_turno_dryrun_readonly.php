<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-06-27.
 * Proposito: simular cierre de turno POS sin escribir BD.
 * Impacto: valida corte de caja esperado/contado, pagos, ventas y movimientos antes de autorizar cierre real.
 * Contrato: read-only; no actualiza turnos, no crea movimientos y no cierra caja.
 */

$args = isset($argv) ? $argv : array();
$idUsuario = 0;
$montoContado = 0;

foreach ($args as $arg) {
    if (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--monto_contado=") === 0) {
        $montoContado = floatval(trim(substr($arg, 16), "\"' "));
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
$depurarAsignacion = isset($asignacion["depurar"]) && is_array($asignacion["depurar"]) ? $asignacion["depurar"] : array();
$datosAsignacion = isset($depurarAsignacion["asignacion"]) && is_array($depurarAsignacion["asignacion"]) ? $depurarAsignacion["asignacion"] : array();
$turno = isset($depurarAsignacion["turno_abierto"]) && is_array($depurarAsignacion["turno_abierto"]) ? $depurarAsignacion["turno_abierto"] : array();

if (!empty($asignacion["error"]) || empty($depurarAsignacion["asignacion_activa"]) || empty($turno)) {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "No hay asignacion POS activa o turno abierto para simular cierre.",
        "asignacion" => $depurarAsignacion
    ));
}

$cierre = $ventas->cierreTurnoDryRun(array(
    "id_almacen" => intval(valor($datosAsignacion, "id_almacen", 0)),
    "id_caja" => intval(valor($datosAsignacion, "id_caja", 0)),
    "id_turno_caja" => intval(valor($turno, "id_turno_caja", 0)),
    "monto_contado" => $montoContado
));

responder(array(
    "ok" => empty($cierre["error"]) && empty(valor(valor($cierre, "depurar", array()), "bloqueos", array())),
    "modo" => "cierre_turno_dryrun_readonly",
    "id_usuario" => $idUsuario,
    "resultado" => $cierre
));

function valor($datos, $campo, $default = null) {
    return is_array($datos) && array_key_exists($campo, $datos) ? $datos[$campo] : $default;
}

function responder($datos) {
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}
