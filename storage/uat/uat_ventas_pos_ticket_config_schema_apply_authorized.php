<?php
/**
 * Documentacion IA: Codex GPT-5, 2026-07-24.
 * Proposito: aplicar DDL de configuracion formal de ticket POS con autorizacion explicita.
 * Impacto: crea estructura de empresa/ticket y snapshot futuro en ventas; no crea ventas, no imprime y no configura hardware.
 * Contrato: escritura de esquema bloqueada por token, respaldo vigente y confirmacion exacta.
 */

$args = isset($argv) ? $argv : array();
$autorizar = "";
$respaldo = "";
$confirmacion = "";

foreach ($args as $arg) {
    if (strpos($arg, "--autorizar=") === 0) {
        $autorizar = trim(substr($arg, 12), "\"' ");
    } elseif (strpos($arg, "--respaldo=") === 0) {
        $respaldo = trim(substr($arg, 11), "\"' ");
    } elseif (strpos($arg, "--confirmacion=") === 0) {
        $confirmacion = strtoupper(trim(substr($arg, 15), "\"' "));
    }
}

$validacionRespaldo = validarRespaldo($respaldo);
if ($autorizar !== "VENTAS_POS_TICKET_CONFIG_DDL" || !$validacionRespaldo["ok"] || $confirmacion !== "APLICAR CONFIG TICKET POS") {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "No se aplico DDL configuracion ticket POS. Falta token, respaldo valido o confirmacion exacta.",
        "requerido" => array(
            "--autorizar=VENTAS_POS_TICKET_CONFIG_DDL",
            "--respaldo=UAT POS vigente o archivo .sql existente",
            "--confirmacion=\"APLICAR CONFIG TICKET POS\""
        ),
        "validacion_respaldo" => $validacionRespaldo,
        "contrato" => contrato(false)
    ));
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErpEsquema.php";

$esquema = new VentasErpEsquema();
$antes = $esquema->auditarTicketConfigPos();
$plan = $esquema->planActualizarTicketConfigPos(true);
$despues = $esquema->auditarTicketConfigPos();

responder(array(
    "ok" => !hayErrores($plan),
    "modo" => "ventas_pos_ticket_config_schema_apply_authorized",
    "respaldo_ref" => $respaldo,
    "validacion_respaldo" => $validacionRespaldo,
    "auditoria_antes" => $antes,
    "plan" => $plan,
    "auditoria_despues" => $despues,
    "contrato" => contrato(true),
    "siguiente_paso" => "Sembrar configuracion inicial de empresa/ticket y conectar el formateador del ticket a la configuracion efectiva."
));

function hayErrores($plan) {
    foreach ($plan as $paso) {
        if (!empty($paso["error"])) {
            return true;
        }
    }
    return false;
}

function validarRespaldo($respaldo) {
    $respaldo = trim((string) $respaldo);
    if ($respaldo === "UAT POS vigente") {
        return array("ok" => true, "tipo" => "referencia_operativa", "referencia" => $respaldo);
    }
    $esRuta = preg_match('/^[A-Za-z]:[\\\\\\/]/', $respaldo) === 1 || strpos($respaldo, "\\") !== false || strpos($respaldo, "/") !== false;
    if ($esRuta) {
        return array(
            "ok" => is_file($respaldo) && is_readable($respaldo),
            "tipo" => "archivo",
            "ruta" => $respaldo,
            "existe" => is_file($respaldo),
            "legible" => is_readable($respaldo),
            "tamano" => is_file($respaldo) ? filesize($respaldo) : null
        );
    }
    return array("ok" => false, "tipo" => "invalido", "recibido" => $respaldo);
}

function contrato($aplica) {
    return array(
        "aplica_ddl" => $aplica,
        "no_crea_venta" => true,
        "no_modifica_importes" => true,
        "no_mueve_caja" => true,
        "no_mueve_inventario" => true,
        "no_toca_ecommerce" => true,
        "no_configura_impresora" => true,
        "no_imprime" => true
    );
}

function responder($datos) {
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}