<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-06-30.
 * Proposito: simular inspeccion fisica de una partida devuelta sin escribir BD.
 * Impacto: prepara decisiones de cuarentena, reintegro, merma o garantia/proveedor.
 * Contrato: dry-run/read-only; no registra inspeccion, no crea kardex y no mueve inventario.
 */

$args = isset($argv) ? $argv : array();
$idUsuario = 0;
$idDetalle = 0;
$decision = "mantener_cuarentena";
$condicion = "";
$motivo = "";
$diagnostico = "";

foreach ($args as $arg) {
    if (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_devolucion_detalle=") === 0) {
        $idDetalle = intval(trim(substr($arg, strlen("--id_devolucion_detalle=")), "\"' "));
    } elseif (strpos($arg, "--decision_fisica=") === 0) {
        $decision = trim(substr($arg, strlen("--decision_fisica=")), "\"' ");
    } elseif (strpos($arg, "--condicion_producto=") === 0) {
        $condicion = trim(substr($arg, strlen("--condicion_producto=")), "\"' ");
    } elseif (strpos($arg, "--motivo=") === 0) {
        $motivo = trim(substr($arg, strlen("--motivo=")), "\"' ");
    } elseif (strpos($arg, "--diagnostico=") === 0) {
        $diagnostico = trim(substr($arg, strlen("--diagnostico=")), "\"' ");
    }
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";

$ventas = new VentasErp();
$respuesta = $ventas->inspeccionFisicaDevolucionDryRun(array(
    "id_usuario" => $idUsuario,
    "id_devolucion_detalle" => $idDetalle,
    "decision_fisica" => $decision,
    "condicion_producto" => $condicion,
    "motivo" => $motivo,
    "diagnostico" => $diagnostico
));
$depurar = isset($respuesta["depurar"]) && is_array($respuesta["depurar"]) ? $respuesta["depurar"] : array();

responder(array(
    "ok" => empty($respuesta["error"]) && isset($respuesta["tipo"]) && $respuesta["tipo"] === "success",
    "modo" => "ventas_pos_devolucion_inspeccion_fisica_dryrun",
    "read_only" => true,
    "bloqueos" => isset($depurar["bloqueos"]) ? $depurar["bloqueos"] : array(),
    "avisos" => isset($depurar["avisos"]) ? $depurar["avisos"] : array(),
    "respuesta" => $respuesta
));

function responder($datos) {
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}
