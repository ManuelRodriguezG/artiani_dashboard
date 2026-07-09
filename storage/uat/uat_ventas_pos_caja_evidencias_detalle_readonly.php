<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-06-30.
 * Proposito: consultar detalle UAT de evidencias de caja POS sin modificar datos.
 * Impacto: apoya validacion de captura/revision de comprobantes de caja.
 * Contrato: read-only; no aprueba, no rechaza, no adjunta archivos.
 */

$args = isset($argv) ? $argv : array();
$idEvidencia = 0;
$idMovimiento = 0;
$estatus = "";
$limite = 50;

foreach ($args as $arg) {
    if (strpos($arg, "--id_evidencia_caja=") === 0) {
        $idEvidencia = intval(trim(substr($arg, 20), "\"' "));
    } elseif (strpos($arg, "--id_movimiento_caja=") === 0) {
        $idMovimiento = intval(trim(substr($arg, 21), "\"' "));
    } elseif (strpos($arg, "--estatus=") === 0) {
        $estatus = trim(substr($arg, 10), "\"' ");
    } elseif (strpos($arg, "--limite=") === 0) {
        $limite = intval(trim(substr($arg, 9), "\"' "));
    }
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";

$ventas = new VentasErp();
$respuesta = $ventas->evidenciasCajaDetalleReadOnly(array(
    "id_evidencia_caja" => $idEvidencia,
    "id_movimiento_caja" => $idMovimiento,
    "estatus" => $estatus,
    "limite" => $limite
));

echo json_encode(array(
    "ok" => empty($respuesta["error"]),
    "modo" => "ventas_pos_caja_evidencias_detalle_readonly",
    "filtros" => array(
        "id_evidencia_caja" => $idEvidencia,
        "id_movimiento_caja" => $idMovimiento,
        "estatus" => $estatus,
        "limite" => $limite
    ),
    "respuesta" => $respuesta
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
