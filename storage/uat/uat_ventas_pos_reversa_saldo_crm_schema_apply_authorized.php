<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-07.
 * Proposito: aplicar DDL de reversas POS con componentes financieros para saldo CRM solo con autorizacion.
 * Impacto: crea columnas/tabla para separar reembolso caja, reintegro saldo CRM y saldo favor CRM.
 * Contrato: escritura de esquema; no crea devoluciones, no registra reembolsos, no mueve CRM ni inventario.
 */

$args = isset($argv) ? $argv : array();
$autorizar = "";
$respaldo = "";
$permitirRespaldoVigente = false;

foreach ($args as $arg) {
    if (strpos($arg, "--autorizar=") === 0) {
        $autorizar = trim(substr($arg, 12), "\"' ");
    } elseif (strpos($arg, "--respaldo=") === 0) {
        $respaldo = trim(substr($arg, 11), "\"' ");
    } elseif ($arg === "--respaldo_uat_vigente=1") {
        $permitirRespaldoVigente = true;
    }
}

$respaldoValido = $respaldo !== "" && (is_file($respaldo) || ($permitirRespaldoVigente && strtolower($respaldo) === "uat_pos_vigente"));
if ($autorizar !== "VENTAS_POS_REVERSA_SALDO_CRM_DDL" || !$respaldoValido) {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "No se aplico DDL de reversas POS con saldo CRM. Falta token o respaldo valido.",
        "requerido" => array(
            "autorizar" => "VENTAS_POS_REVERSA_SALDO_CRM_DDL",
            "respaldo" => "archivo .sql existente o UAT_POS_VIGENTE con --respaldo_uat_vigente=1"
        ),
        "recibido" => array(
            "autorizar" => $autorizar,
            "respaldo" => $respaldo,
            "respaldo_existe" => is_file($respaldo),
            "respaldo_uat_vigente" => $permitirRespaldoVigente
        )
    ));
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErpEsquema.php";

$esquema = new VentasErpEsquema();
$antes = $esquema->auditarReversasSaldoCrmPos();
$plan = $esquema->planActualizarReversasSaldoCrmPos(true);
$despues = $esquema->auditarReversasSaldoCrmPos();

responder(array(
    "ok" => !hayErrores($plan),
    "modo" => "ventas_pos_reversa_saldo_crm_schema_apply_authorized",
    "respaldo" => array(
        "ruta" => $respaldo,
        "existe" => is_file($respaldo),
        "uat_pos_vigente" => $permitirRespaldoVigente && strtolower($respaldo) === "uat_pos_vigente"
    ),
    "auditoria_antes" => $antes,
    "plan" => $plan,
    "auditoria_despues" => $despues,
    "reglas" => array(
        "No crea devoluciones reales.",
        "No registra reembolsos.",
        "No mueve saldo CRM.",
        "No mueve caja.",
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
    exit(empty($datos["ok"]) ? 1 : 0);
}
