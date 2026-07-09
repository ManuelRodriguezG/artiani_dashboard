<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-03.
 * Proposito: registrar expediente UAT real de revision de diferencia de caja con autorizacion explicita.
 * Impacto: inserta `erp_pos_turnos_diferencias_revision`; no modifica turno, caja, ventas ni inventario.
 * Contrato: bloqueado por defecto; requiere token, respaldo externo, usuario, turno y motivo.
 */

$args = isset($argv) ? $argv : array();
$autorizar = "";
$respaldo = "";
$idUsuario = 0;
$idTurno = 0;
$motivo = "";
$diagnostico = "";
$responsable = "";
$evidencia = "";

foreach ($args as $arg) {
    if (strpos($arg, "--autorizar=") === 0) {
        $autorizar = trim(substr($arg, 12), "\"' ");
    } elseif (strpos($arg, "--respaldo=") === 0) {
        $respaldo = trim(substr($arg, 11), "\"' ");
    } elseif (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_turno_caja=") === 0) {
        $idTurno = intval(trim(substr($arg, 16), "\"' "));
    } elseif (strpos($arg, "--motivo=") === 0) {
        $motivo = trim(substr($arg, 9), "\"' ");
    } elseif (strpos($arg, "--diagnostico=") === 0) {
        $diagnostico = trim(substr($arg, 14), "\"' ");
    } elseif (strpos($arg, "--responsable=") === 0) {
        $responsable = trim(substr($arg, 14), "\"' ");
    } elseif (strpos($arg, "--evidencia_referencia=") === 0) {
        $evidencia = trim(substr($arg, 23), "\"' ");
    }
}

if ($autorizar !== "VENTAS_POS_CAJA_DIFERENCIA_REVISION_REAL"
    || $respaldo === ""
    || !is_file($respaldo)
    || $idUsuario <= 0
    || $idTurno <= 0
    || $motivo === "") {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "No se registro revision de diferencia caja. Falta token, respaldo, usuario, turno o motivo.",
        "requerido" => array(
            "--autorizar=VENTAS_POS_CAJA_DIFERENCIA_REVISION_REAL",
            "--respaldo=RUTA_RESPALDO_EXISTENTE",
            "--id_usuario=ID",
            "--id_turno_caja=ID",
            "--motivo=TEXTO"
        ),
        "recibido" => array(
            "autorizar" => $autorizar,
            "respaldo_existe" => is_file($respaldo),
            "id_usuario" => $idUsuario,
            "id_turno_caja" => $idTurno,
            "motivo" => $motivo
        )
    ));
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";

$ventas = new VentasErp();
$respuesta = $ventas->registrarRevisionDiferenciaCajaPosReal(array(
    "id_usuario" => $idUsuario,
    "id_turno_caja" => $idTurno,
    "motivo" => $motivo,
    "diagnostico" => $diagnostico,
    "responsable_revision" => $responsable,
    "evidencia_referencia" => $evidencia
));

responder(array(
    "ok" => empty($respuesta["error"]) && in_array(isset($respuesta["tipo"]) ? $respuesta["tipo"] : "", array("success", "info"), true),
    "modo" => "ventas_pos_diferencia_revision_real_uat",
    "respaldo_ref" => $respaldo,
    "id_turno_caja" => $idTurno,
    "respuesta" => $respuesta,
    "siguiente_paso" => empty($respuesta["error"])
        ? "Validar bandeja de diferencias y preparar resolucion formal."
        : "Resolver bloqueo antes de repetir revision."
));

function responder($datos) {
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}
