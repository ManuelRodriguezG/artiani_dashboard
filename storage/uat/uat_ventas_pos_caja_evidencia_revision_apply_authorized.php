<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-06-30.
 * Proposito: revisar evidencia UAT de caja POS solo con autorizacion explicita.
 * Impacto: cambia estatus de evidencia y movimiento de caja; no mueve dinero ni inventario.
 * Contrato: BLOQUEADO por defecto; requiere respaldo externo y token VENTAS_POS_CAJA_EVIDENCIA_REVISION_REAL.
 */

$args = isset($argv) ? $argv : array();
$autorizar = "";
$respaldo = "";
$respaldoUatVigente = false;
$idUsuario = 0;
$idEvidencia = 0;
$decision = "";
$motivo = "";
$permitirMismoUsuario = 0;

foreach ($args as $arg) {
    if (strpos($arg, "--autorizar=") === 0) {
        $autorizar = trim(substr($arg, 12), "\"' ");
    } elseif (strpos($arg, "--respaldo=") === 0) {
        $respaldo = trim(substr($arg, 11), "\"' ");
    } elseif ($arg === "--respaldo_uat_vigente=1") {
        $respaldoUatVigente = true;
    } elseif (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_evidencia_caja=") === 0) {
        $idEvidencia = intval(trim(substr($arg, 20), "\"' "));
    } elseif (strpos($arg, "--decision=") === 0) {
        $decision = trim(substr($arg, 11), "\"' ");
    } elseif (strpos($arg, "--motivo=") === 0) {
        $motivo = trim(substr($arg, 9), "\"' ");
    } elseif (strpos($arg, "--permitir_mismo_usuario=") === 0) {
        $permitirMismoUsuario = intval(trim(substr($arg, 25), "\"' "));
    }
}

if ($autorizar !== "VENTAS_POS_CAJA_EVIDENCIA_REVISION_REAL"
    || !respaldoValido($respaldo, $respaldoUatVigente)
    || $idUsuario <= 0
    || $idEvidencia <= 0
    || !in_array($decision, array("aprobada", "rechazada"), true)
    || ($decision === "rechazada" && $motivo === "")) {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "No se reviso evidencia de caja. Falta token, respaldo, usuario, evidencia, decision valida o motivo de rechazo.",
        "requerido" => array(
            "--autorizar=VENTAS_POS_CAJA_EVIDENCIA_REVISION_REAL",
            "--respaldo=UAT_POS_VIGENTE --respaldo_uat_vigente=1 o --respaldo=RUTA_RESPALDO_EXISTENTE",
            "--id_usuario=ID_REVISOR",
            "--id_evidencia_caja=ID",
            "--decision=aprobada|rechazada",
            "--motivo=TEXTO si decision=rechazada"
        )
    ));
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";

$ventas = new VentasErp();
$respuesta = $ventas->revisarEvidenciaCajaPosReal(array(
    "id_usuario" => $idUsuario,
    "id_evidencia_caja" => $idEvidencia,
    "decision" => $decision,
    "motivo" => $motivo,
    "permitir_mismo_usuario" => $permitirMismoUsuario
));

responder(array(
    "ok" => empty($respuesta["error"]) && isset($respuesta["tipo"]) && $respuesta["tipo"] === "success",
    "modo" => "ventas_pos_caja_evidencia_revision_real_uat",
    "respaldo_ref" => $respaldo,
    "id_evidencia_caja" => $idEvidencia,
    "decision" => $decision,
    "respuesta" => $respuesta,
    "siguiente_paso" => empty($respuesta["error"])
        ? "Validar detalle de evidencia y bandeja de evidencias de caja."
        : "Resolver bloqueo antes de repetir revision."
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
