<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-06-30.
 * Proposito: solicitar correccion UAT de evidencia de caja aprobada solo con autorizacion explicita.
 * Impacto: crea folio de correccion; no modifica evidencia original, caja, dinero ni inventario.
 * Contrato: BLOQUEADO por defecto; requiere respaldo externo y token VENTAS_POS_CAJA_EVIDENCIA_CORRECCION_REAL.
 */

$args = isset($argv) ? $argv : array();
$autorizar = "";
$respaldo = "";
$idUsuario = 0;
$idEvidencia = 0;
$tipoCorreccion = "reemplazo_evidencia";
$motivo = "";

foreach ($args as $arg) {
    if (strpos($arg, "--autorizar=") === 0) {
        $autorizar = trim(substr($arg, 12), "\"' ");
    } elseif (strpos($arg, "--respaldo=") === 0) {
        $respaldo = trim(substr($arg, 11), "\"' ");
    } elseif (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_evidencia_caja=") === 0) {
        $idEvidencia = intval(trim(substr($arg, 20), "\"' "));
    } elseif (strpos($arg, "--tipo_correccion=") === 0) {
        $tipoCorreccion = trim(substr($arg, 18), "\"' ");
    } elseif (strpos($arg, "--motivo=") === 0) {
        $motivo = trim(substr($arg, 9), "\"' ");
    }
}

if ($autorizar !== "VENTAS_POS_CAJA_EVIDENCIA_CORRECCION_REAL"
    || $respaldo === ""
    || !is_file($respaldo)
    || $idUsuario <= 0
    || $idEvidencia <= 0
    || $motivo === "") {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "No se solicito correccion de evidencia. Falta token, respaldo, usuario, evidencia o motivo.",
        "requerido" => array(
            "--autorizar=VENTAS_POS_CAJA_EVIDENCIA_CORRECCION_REAL",
            "--respaldo=RUTA_RESPALDO_EXISTENTE",
            "--id_usuario=ID",
            "--id_evidencia_caja=ID",
            "--motivo=TEXTO"
        )
    ));
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";

$ventas = new VentasErp();
$respuesta = $ventas->solicitarCorreccionEvidenciaCajaPosReal(array(
    "id_usuario" => $idUsuario,
    "id_evidencia_caja" => $idEvidencia,
    "tipo_correccion" => $tipoCorreccion,
    "motivo" => $motivo
));

responder(array(
    "ok" => empty($respuesta["error"]) && isset($respuesta["tipo"]) && $respuesta["tipo"] === "success",
    "modo" => "ventas_pos_caja_evidencia_correccion_real_uat",
    "respaldo_ref" => $respaldo,
    "id_evidencia_caja" => $idEvidencia,
    "respuesta" => $respuesta,
    "siguiente_paso" => empty($respuesta["error"])
        ? "Validar folio de correccion y preparar resolucion."
        : "Resolver bloqueo antes de repetir solicitud."
));

function responder($datos) {
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}
