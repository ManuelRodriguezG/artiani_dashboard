<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-09.
 * Proposito: preparar DDL de destino final de cuarentena POS sin escribir BD.
 * Impacto: evidencia columnas/indices requeridos antes del apply real con kardex.
 * Contrato: read-only; no ejecuta ALTER, no mueve inventario y no cierra devoluciones.
 */

$args = isset($argv) ? $argv : array();
$token = "";
foreach ($args as $arg) {
    if (strpos($arg, "--token=") === 0) {
        $token = trim(substr($arg, 8), "\"' ");
    }
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErpEsquema.php";

$esquema = new VentasErpEsquema();
$auditoria = $esquema->auditarDestinoFinalCuarentenaPos();
$plan = $esquema->planActualizarDestinoFinalCuarentenaPos(false);

responder(array(
    "ok" => $token === "VENTAS_POS_DESTINO_FINAL_CUARENTENA_DDL",
    "modo" => "ventas_pos_destino_final_cuarentena_ddl_prepare",
    "read_only" => true,
    "token_recibido" => $token,
    "token_valido" => $token === "VENTAS_POS_DESTINO_FINAL_CUARENTENA_DDL",
    "auditoria" => $auditoria,
    "plan" => $plan,
    "siguiente_autorizacion" => "AUTORIZO APLICAR DDL DESTINO FINAL CUARENTENA POS usando respaldo UAT POS vigente con token VENTAS_POS_DESTINO_FINAL_CUARENTENA_DDL para UAT POS"
));

function responder($datos) {
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}
