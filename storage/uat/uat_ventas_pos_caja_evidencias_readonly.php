<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-06-30.
 * Proposito: consultar movimientos de caja POS que requieren evidencia sin escribir BD.
 * Impacto: evidencia UAT para reembolsos/gastos/vales pendientes de comprobante.
 * Contrato: read-only; no adjunta archivos, no aprueba evidencia y no cambia turnos.
 */

$args = isset($argv) ? $argv : array();
$idAlmacen = 0;
$idCaja = 0;
$idTurno = 0;
$estado = "pendiente";
$limite = 50;

foreach ($args as $arg) {
    if (strpos($arg, "--id_almacen=") === 0) {
        $idAlmacen = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_caja=") === 0) {
        $idCaja = intval(trim(substr($arg, 10), "\"' "));
    } elseif (strpos($arg, "--id_turno_caja=") === 0) {
        $idTurno = intval(trim(substr($arg, 16), "\"' "));
    } elseif (strpos($arg, "--evidencia_estado=") === 0) {
        $estado = trim(substr($arg, 19), "\"' ");
    } elseif (strpos($arg, "--limite=") === 0) {
        $limite = intval(trim(substr($arg, 9), "\"' "));
    }
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";

$ventas = new VentasErp();
$respuesta = $ventas->evidenciasCajaPendientesReadOnly(array(
    "id_almacen" => $idAlmacen,
    "id_caja" => $idCaja,
    "id_turno_caja" => $idTurno,
    "evidencia_estado" => $estado,
    "limite" => $limite
));
$depurar = isset($respuesta["depurar"]) && is_array($respuesta["depurar"]) ? $respuesta["depurar"] : array();
responder(array(
    "ok" => empty($respuesta["error"]) && isset($respuesta["tipo"]) && $respuesta["tipo"] === "success",
    "modo" => "ventas_pos_caja_evidencias_readonly",
    "read_only" => true,
    "resumen" => isset($depurar["resumen"]) ? $depurar["resumen"] : null,
    "pendientes" => isset($depurar["pendientes"]) ? $depurar["pendientes"] : array(),
    "respuesta" => $respuesta
));

function responder($datos) {
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}
