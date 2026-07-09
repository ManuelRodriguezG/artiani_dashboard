<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-09.
 * Proposito: prevalidar destino final de una devolucion POS en cuarentena confirmada sin escribir BD.
 * Impacto: prepara reintegro, merma, garantia o reparacion con evidencia antes de autorizacion real.
 * Contrato: read-only/dry-run; no crea kardex, no mueve inventario, no actualiza devoluciones.
 */

$args = isset($argv) ? $argv : array();
$idUsuario = 1;
$idDetalle = 0;
$destinoFinal = "reintegrar_disponible";
$motivo = "Dry-run destino final cuarentena POS";

foreach ($args as $arg) {
    if (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_devolucion_detalle=") === 0) {
        $idDetalle = intval(trim(substr($arg, 24), "\"' "));
    } elseif (strpos($arg, "--destino_final=") === 0) {
        $destinoFinal = trim(substr($arg, 16), "\"' ");
    } elseif (strpos($arg, "--motivo=") === 0) {
        $motivo = trim(substr($arg, 9), "\"' ");
    }
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";

$ventas = new VentasErp();
$respuesta = $ventas->destinoFinalCuarentenaDevolucionDryRun(array(
    "id_usuario" => $idUsuario,
    "id_devolucion_detalle" => $idDetalle,
    "destino_final" => $destinoFinal,
    "motivo" => $motivo
));
$depurar = isset($respuesta["depurar"]) && is_array($respuesta["depurar"]) ? $respuesta["depurar"] : array();

responder(array(
    "ok" => empty($respuesta["error"]) && isset($respuesta["tipo"]) && $respuesta["tipo"] === "success",
    "modo" => "ventas_pos_destino_final_cuarentena_dryrun",
    "read_only" => true,
    "dry_run" => true,
    "id_devolucion_detalle" => $idDetalle,
    "destino_final" => $destinoFinal,
    "bloqueos" => isset($depurar["bloqueos"]) ? $depurar["bloqueos"] : array(),
    "avisos" => isset($depurar["avisos"]) ? $depurar["avisos"] : array(),
    "ddl_requerido_para_apply_real" => isset($depurar["ddl_requerido_para_apply_real"]) ? $depurar["ddl_requerido_para_apply_real"] : array(),
    "plan" => isset($depurar["plan"]) ? $depurar["plan"] : array(),
    "partida" => isset($depurar["partida"]) ? $depurar["partida"] : null,
    "respuesta" => $respuesta
));

function responder($datos) {
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}
