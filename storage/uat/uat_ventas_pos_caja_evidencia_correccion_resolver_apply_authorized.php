<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-06-30.
 * Proposito: resolver correccion UAT de evidencia caja POS solo con autorizacion explicita.
 * Impacto: cierra folio de correccion y marca evidencia correctiva; no modifica caja ni inventario.
 * Contrato: BLOQUEADO por defecto; requiere respaldo externo y token VENTAS_POS_CAJA_EVIDENCIA_CORRECCION_RESOLVER_REAL.
 */

$args = isset($argv) ? $argv : array();
$autorizar = "";
$respaldo = "";
$idUsuario = 0;
$folio = "";
$decision = "";
$motivo = "";

foreach ($args as $arg) {
    if (strpos($arg, "--autorizar=") === 0) {
        $autorizar = trim(substr($arg, 12), "\"' ");
    } elseif (strpos($arg, "--respaldo=") === 0) {
        $respaldo = trim(substr($arg, 11), "\"' ");
    } elseif (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--folio=") === 0) {
        $folio = trim(substr($arg, 8), "\"' ");
    } elseif (strpos($arg, "--decision=") === 0) {
        $decision = trim(substr($arg, 11), "\"' ");
    } elseif (strpos($arg, "--motivo=") === 0) {
        $motivo = trim(substr($arg, 9), "\"' ");
    }
}

if ($autorizar !== "VENTAS_POS_CAJA_EVIDENCIA_CORRECCION_RESOLVER_REAL"
    || $respaldo === ""
    || !is_file($respaldo)
    || $idUsuario <= 0
    || $folio === ""
    || !in_array($decision, array("aprobada", "rechazada"), true)
    || $motivo === "") {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "No se resolvio correccion de evidencia. Falta token, respaldo, usuario, folio, decision o motivo.",
        "requerido" => array(
            "--autorizar=VENTAS_POS_CAJA_EVIDENCIA_CORRECCION_RESOLVER_REAL",
            "--respaldo=RUTA_RESPALDO_EXISTENTE",
            "--id_usuario=ID",
            "--folio=COR-EVC-...",
            "--decision=aprobada|rechazada",
            "--motivo=TEXTO"
        )
    ));
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";

$ventas = new VentasErp();
$respuesta = $ventas->resolverCorreccionEvidenciaCajaPosReal(array(
    "id_usuario" => $idUsuario,
    "folio" => $folio,
    "decision" => $decision,
    "motivo_resolucion" => $motivo
));

responder(array(
    "ok" => empty($respuesta["error"]) && isset($respuesta["tipo"]) && $respuesta["tipo"] === "success",
    "modo" => "ventas_pos_caja_evidencia_correccion_resolver_real_uat",
    "respaldo_ref" => $respaldo,
    "folio" => $folio,
    "decision" => $decision,
    "respuesta" => $respuesta,
    "siguiente_paso" => empty($respuesta["error"])
        ? "Validar correccion resuelta y estados de evidencias."
        : "Resolver bloqueo antes de repetir resolucion."
));

function responder($datos) {
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}
