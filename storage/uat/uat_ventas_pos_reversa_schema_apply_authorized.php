<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-06-29.
 * Proposito: aplicar DDL de reversas POS solo con autorizacion explicita y respaldo externo.
 * Impacto: agrega columnas/indices para devoluciones/cancelaciones con reembolso, caja e inventario.
 * Contrato: escritura de esquema; no crea devoluciones, no reembolsa caja y no mueve inventario.
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

if ($autorizar !== "VENTAS_POS_REVERSA_DDL" || !is_file($respaldo)) {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "No se aplico DDL de reversas POS. Falta token o respaldo externo valido.",
        "requerido" => array(
            "autorizar" => "VENTAS_POS_REVERSA_DDL",
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
$antes = $esquema->auditarReversasPos();
$plan = $esquema->planActualizarReversasPos(true);
$despues = $esquema->auditarReversasPos();

responder(array(
    "ok" => !hayErrores($plan),
    "modo" => "ventas_pos_reversa_schema_apply_authorized",
    "respaldo" => array(
        "ruta" => $respaldo,
        "existe" => is_file($respaldo)
    ),
    "auditoria_antes" => $antes,
    "plan" => $plan,
    "auditoria_despues" => $despues,
    "reglas" => array(
        "No crea devoluciones reales.",
        "No registra reembolsos.",
        "No mueve inventario."
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
