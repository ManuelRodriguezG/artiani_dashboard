<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-06-30.
 * Proposito: registrar inspeccion fisica real de devolucion POS solo con autorizacion explicita.
 * Impacto: primera fase segura; permite mantener cuarentena sin crear kardex ni mover inventario.
 * Contrato: escritura real controlada; requiere token, respaldo externo, usuario y partida.
 */

$args = isset($argv) ? $argv : array();
$autorizar = "";
$respaldo = "";
$respaldoUatVigente = false;
$idUsuario = 0;
$idDetalle = 0;
$decision = "mantener_cuarentena";
$condicion = "";
$motivo = "";
$diagnostico = "";

foreach ($args as $arg) {
    if (strpos($arg, "--autorizar=") === 0) {
        $autorizar = trim(substr($arg, strlen("--autorizar=")), "\"' ");
    } elseif (strpos($arg, "--respaldo=") === 0) {
        $respaldo = trim(substr($arg, strlen("--respaldo=")), "\"' ");
    } elseif ($arg === "--respaldo_uat_vigente=1") {
        $respaldoUatVigente = true;
    } elseif (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, strlen("--id_usuario=")), "\"' "));
    } elseif (strpos($arg, "--id_devolucion_detalle=") === 0) {
        $idDetalle = intval(trim(substr($arg, strlen("--id_devolucion_detalle=")), "\"' "));
    } elseif (strpos($arg, "--decision_fisica=") === 0) {
        $decision = trim(substr($arg, strlen("--decision_fisica=")), "\"' ");
    } elseif (strpos($arg, "--condicion_producto=") === 0) {
        $condicion = trim(substr($arg, strlen("--condicion_producto=")), "\"' ");
    } elseif (strpos($arg, "--motivo=") === 0) {
        $motivo = trim(substr($arg, strlen("--motivo=")), "\"' ");
    } elseif (strpos($arg, "--diagnostico=") === 0) {
        $diagnostico = trim(substr($arg, strlen("--diagnostico=")), "\"' ");
    }
}

if ($autorizar !== "VENTAS_POS_DEVOLUCION_FISICA_REAL" || !respaldoValido($respaldo, $respaldoUatVigente) || $idUsuario <= 0 || $idDetalle <= 0) {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "No se registro inspeccion fisica. Falta token, respaldo, usuario o partida.",
        "requerido" => array(
            "autorizar" => "VENTAS_POS_DEVOLUCION_FISICA_REAL",
            "respaldo" => "UAT_POS_VIGENTE con respaldo_uat_vigente=1 o archivo .sql existente",
            "id_usuario" => "entero positivo",
            "id_devolucion_detalle" => "entero positivo"
        ),
        "recibido" => array(
            "autorizar" => $autorizar,
            "respaldo" => $respaldo,
            "respaldo_valido" => respaldoValido($respaldo, $respaldoUatVigente),
            "id_usuario" => $idUsuario,
            "id_devolucion_detalle" => $idDetalle
        )
    ));
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";

$ventas = new VentasErp();
$dryRunAntes = $ventas->inspeccionFisicaDevolucionDryRun(array(
    "id_usuario" => $idUsuario,
    "id_devolucion_detalle" => $idDetalle,
    "decision_fisica" => $decision,
    "condicion_producto" => $condicion,
    "motivo" => $motivo,
    "diagnostico" => $diagnostico
));
$respuesta = $ventas->registrarInspeccionFisicaDevolucionPosReal(array(
    "id_usuario" => $idUsuario,
    "id_devolucion_detalle" => $idDetalle,
    "decision_fisica" => $decision,
    "condicion_producto" => $condicion,
    "motivo" => $motivo,
    "diagnostico" => $diagnostico
));
$dryRunDespues = $ventas->inspeccionFisicaDevolucionDryRun(array(
    "id_usuario" => $idUsuario,
    "id_devolucion_detalle" => $idDetalle,
    "decision_fisica" => $decision,
    "condicion_producto" => $condicion,
    "motivo" => $motivo,
    "diagnostico" => $diagnostico
));

responder(array(
    "ok" => empty($respuesta["error"]) && isset($respuesta["tipo"]) && $respuesta["tipo"] === "success",
    "modo" => "ventas_pos_devolucion_inspeccion_fisica_apply_authorized",
    "respaldo" => array("ruta" => $respaldo, "valido" => respaldoValido($respaldo, $respaldoUatVigente)),
    "dry_run_antes" => $dryRunAntes,
    "resultado" => $respuesta,
    "dry_run_despues" => $dryRunDespues,
    "reglas" => array(
        "Solo permite mantener_cuarentena en esta fase.",
        "No crea kardex.",
        "No mueve inventario.",
        "No crea reclamo de garantia."
    )
));

function responder($datos) {
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function respaldoValido($respaldo, $respaldoUatVigente) {
    if ($respaldoUatVigente && strtolower((string) $respaldo) === "uat_pos_vigente") {
        return true;
    }
    return $respaldo !== "" && is_file($respaldo);
}
