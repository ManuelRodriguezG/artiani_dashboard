<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-05.
 * Proposito: ejecutar UAT real controlada de abono/liquidacion de pedido/apartado POS.
 * Impacto: registra pago, movimiento de caja, saldo y evento.
 * Contrato: bloqueado por token; no mueve inventario.
 */

$args = isset($argv) ? $argv : array();
$autorizar = "";
$respaldo = "";
$idUsuario = 1;
$folio = "";
$monto = 195;
$referencia = "UAT-PED-ABONO";
$idMetodoPago = 1;

foreach ($args as $arg) {
    if (strpos($arg, "--autorizar=") === 0) {
        $autorizar = trim(substr($arg, 12), "\"' ");
    } elseif (strpos($arg, "--respaldo=") === 0) {
        $respaldo = trim(substr($arg, 11), "\"' ");
    } elseif (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--folio=") === 0) {
        $folio = trim(substr($arg, 8), "\"' ");
    } elseif (strpos($arg, "--monto=") === 0) {
        $monto = floatval(trim(substr($arg, 8), "\"' "));
    } elseif (strpos($arg, "--referencia=") === 0) {
        $referencia = trim(substr($arg, 13), "\"' ");
    } elseif (strpos($arg, "--id_metodo_pago=") === 0) {
        $idMetodoPago = intval(trim(substr($arg, 17), "\"' "));
    }
}

if ($autorizar !== "VENTAS_POS_APARTADO_ABONO_REAL" || $respaldo === "" || $folio === "") {
    echo json_encode(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "Falta autorizacion VENTAS_POS_APARTADO_ABONO_REAL, respaldo vigente o folio",
        "contrato" => array(
            "no_escribe_bd" => true,
            "no_registra_abono" => true,
            "no_mueve_caja" => true,
            "no_mueve_inventario" => true
        )
    ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";

$ventas = new VentasErp();
$respuesta = $ventas->apartadoAbonoReal(array(
    "id_usuario" => $idUsuario,
    "folio" => $folio,
    "monto_abono" => $monto,
    "id_metodo_pago" => $idMetodoPago,
    "referencia" => $referencia
));

echo json_encode(array(
    "ok" => empty($respuesta["error"]),
    "modo" => "apartado_abono_real_authorized",
    "respaldo" => $respaldo,
    "respuesta" => $respuesta
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
