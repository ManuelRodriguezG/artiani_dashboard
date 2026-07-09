<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-06-30.
 * Proposito: aplicar DDL UAT de correcciones de evidencias caja POS solo con autorizacion explicita.
 * Impacto: crea tabla de solicitudes de correccion; no modifica evidencias ni movimientos de caja.
 * Contrato: BLOQUEADO por defecto; requiere respaldo externo y token VENTAS_POS_CAJA_EVIDENCIAS_CORRECCION_DDL.
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

if ($autorizar !== "VENTAS_POS_CAJA_EVIDENCIAS_CORRECCION_DDL" || $respaldo === "" || !is_file($respaldo)) {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "No se aplico DDL de correcciones de evidencias de caja. Falta token o respaldo externo valido.",
        "requerido" => array(
            "--autorizar=VENTAS_POS_CAJA_EVIDENCIAS_CORRECCION_DDL",
            "--respaldo=RUTA_RESPALDO_EXISTENTE"
        )
    ));
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErpEsquema.php";

$esquema = new VentasErpEsquema();
$plan = $esquema->planActualizarCorreccionesEvidenciasCajaPos(true);
$auditoria = $esquema->auditarCorreccionesEvidenciasCajaPos();

responder(array(
    "ok" => empty($auditoria["error"]),
    "modo" => "ventas_pos_caja_evidencias_correccion_schema_apply_authorized",
    "respaldo_ref" => $respaldo,
    "plan" => $plan,
    "auditoria" => $auditoria,
    "contrato" => array(
        "no_modifica_evidencias_existentes" => true,
        "no_modifica_movimientos_caja" => true,
        "no_mueve_dinero" => true,
        "no_mueve_inventario" => true
    )
));

function responder($datos) {
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}
