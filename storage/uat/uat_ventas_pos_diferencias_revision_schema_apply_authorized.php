<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-03.
 * Proposito: aplicar DDL de revision formal de diferencias de caja POS solo con autorizacion explicita.
 * Impacto: crea tabla/indices de seguimiento; no modifica turnos, no mueve caja y no resuelve diferencias.
 * Contrato: escritura de esquema bloqueada por token y respaldo externo.
 */

$args = isset($argv) ? $argv : array();
$autorizar = "";
$respaldo = "";

foreach ($args as $arg) {
    if (strpos($arg, "--autorizar=") === 0) {
        $autorizar = trim(substr($arg, 12), "\"' ");
    } elseif (strpos($arg, "--respaldo=") === 0) {
        $respaldo = trim(substr($arg, 11), "\"' ");
    }
}

if ($autorizar !== "VENTAS_POS_CAJA_DIFERENCIAS_REVISION_DDL" || !is_file($respaldo)) {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "No se aplico DDL de revision de diferencias caja POS. Falta token o respaldo externo valido.",
        "requerido" => array(
            "autorizar" => "VENTAS_POS_CAJA_DIFERENCIAS_REVISION_DDL",
            "respaldo" => "archivo .sql existente"
        ),
        "recibido" => array(
            "autorizar" => $autorizar,
            "respaldo" => $respaldo,
            "respaldo_existe" => is_file($respaldo)
        )
    ));
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErpEsquema.php";

$esquema = new VentasErpEsquema();
$antes = $esquema->auditarRevisionDiferenciasCajaPos();
$plan = $esquema->planActualizarRevisionDiferenciasCajaPos(true);
$despues = $esquema->auditarRevisionDiferenciasCajaPos();

responder(array(
    "ok" => !hayErrores($plan),
    "modo" => "ventas_pos_diferencias_revision_schema_apply_authorized",
    "respaldo" => array(
        "ruta" => $respaldo,
        "existe" => is_file($respaldo)
    ),
    "auditoria_antes" => $antes,
    "plan" => $plan,
    "auditoria_despues" => $despues,
    "reglas" => array(
        "No modifica erp_pos_turnos.",
        "No mueve caja.",
        "No resuelve diferencias.",
        "No crea movimientos de dinero ni inventario."
    )
));

function hayErrores($plan) {
    foreach ($plan as $paso) {
        if (!empty($paso["error"])) {
            return true;
        }
    }
    return false;
}

function responder($datos) {
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}
