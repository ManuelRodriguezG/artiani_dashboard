<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-03.
 * Proposito: resolver expediente UAT real de diferencia de caja POS solo con autorizacion explicita.
 * Impacto: actualiza `erp_pos_turnos_diferencias_revision`; no modifica turno, caja, ventas ni inventario.
 * Contrato: bloqueado por defecto; requiere token, respaldo externo, usuario, folio/expediente, decision y motivo.
 */

$args = isset($argv) ? $argv : array();
$autorizar = "";
$respaldo = "";
$idUsuario = 0;
$idRevision = 0;
$folio = "";
$decision = "";
$motivo = "";
$diagnostico = "";
$evidencia = "";

foreach ($args as $arg) {
    if (strpos($arg, "--autorizar=") === 0) {
        $autorizar = trim(substr($arg, 12), "\"' ");
    } elseif (strpos($arg, "--respaldo=") === 0) {
        $respaldo = trim(substr($arg, 11), "\"' ");
    } elseif (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_diferencia_revision=") === 0) {
        $idRevision = intval(trim(substr($arg, 25), "\"' "));
    } elseif (strpos($arg, "--folio=") === 0) {
        $folio = trim(substr($arg, 8), "\"' ");
    } elseif (strpos($arg, "--decision=") === 0) {
        $decision = trim(substr($arg, 11), "\"' ");
    } elseif (strpos($arg, "--motivo=") === 0) {
        $motivo = trim(substr($arg, 9), "\"' ");
    } elseif (strpos($arg, "--diagnostico=") === 0) {
        $diagnostico = trim(substr($arg, 14), "\"' ");
    } elseif (strpos($arg, "--evidencia_referencia=") === 0) {
        $evidencia = trim(substr($arg, 23), "\"' ");
    }
}

$decisiones = array("explicada", "aceptada", "ajustada", "escalada", "cancelada");
if ($autorizar !== "VENTAS_POS_CAJA_DIFERENCIA_RESOLVER_REAL"
    || $respaldo === ""
    || !is_file($respaldo)
    || $idUsuario <= 0
    || ($folio === "" && $idRevision <= 0)
    || !in_array($decision, $decisiones, true)
    || $motivo === "") {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "No se resolvio revision de diferencia caja. Falta token, respaldo, usuario, folio/expediente, decision o motivo.",
        "requerido" => array(
            "--autorizar=VENTAS_POS_CAJA_DIFERENCIA_RESOLVER_REAL",
            "--respaldo=RUTA_RESPALDO_EXISTENTE",
            "--id_usuario=ID",
            "--folio=DIF-CAJ-...",
            "--decision=explicada|aceptada|ajustada|escalada|cancelada",
            "--motivo=TEXTO"
        ),
        "recibido" => array(
            "autorizar" => $autorizar,
            "respaldo_existe" => is_file($respaldo),
            "id_usuario" => $idUsuario,
            "id_diferencia_revision" => $idRevision,
            "folio" => $folio,
            "decision" => $decision,
            "motivo" => $motivo
        )
    ));
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";

$ventas = new VentasErp();
$respuesta = $ventas->resolverRevisionDiferenciaCajaPosReal(array(
    "id_usuario" => $idUsuario,
    "id_diferencia_revision" => $idRevision,
    "folio" => $folio,
    "decision" => $decision,
    "motivo" => $motivo,
    "diagnostico" => $diagnostico,
    "evidencia_referencia" => $evidencia
));

responder(array(
    "ok" => empty($respuesta["error"]) && isset($respuesta["tipo"]) && $respuesta["tipo"] === "success",
    "modo" => "ventas_pos_diferencia_revision_resolver_real_uat",
    "respaldo_ref" => $respaldo,
    "folio" => $folio,
    "id_diferencia_revision" => $idRevision,
    "decision" => $decision,
    "respuesta" => $respuesta,
    "siguiente_paso" => empty($respuesta["error"])
        ? "Validar bandeja de diferencias y confirmar que el faltante historico sigue visible en reportes."
        : "Resolver bloqueo antes de repetir resolucion."
));

function responder($datos) {
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}
