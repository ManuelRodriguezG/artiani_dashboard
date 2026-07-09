<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-05.
 * Proposito: ejecutar UAT real controlada de entrega de pedido/apartado POS.
 * Impacto: consume reservas, genera kardex y trazabilidad de detalle.
 * Contrato: bloqueado por token; no registra pagos.
 */

$args = isset($argv) ? $argv : array();
$autorizar = "";
$respaldo = "";
$idUsuario = 1;
$folio = "";

foreach ($args as $arg) {
    if (strpos($arg, "--autorizar=") === 0) {
        $autorizar = trim(substr($arg, 12), "\"' ");
    } elseif (strpos($arg, "--respaldo=") === 0) {
        $respaldo = trim(substr($arg, 11), "\"' ");
    } elseif (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--folio=") === 0) {
        $folio = trim(substr($arg, 8), "\"' ");
    }
}

if ($autorizar !== "VENTAS_POS_PEDIDO_ENTREGA_REAL" || $respaldo === "" || $folio === "") {
    echo json_encode(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "Falta autorizacion VENTAS_POS_PEDIDO_ENTREGA_REAL, respaldo vigente o folio",
        "contrato" => array("no_escribe_bd" => true, "no_entrega" => true, "no_mueve_kardex" => true)
    ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";

$ventas = new VentasErp();
$respuesta = $ventas->pedidoEntregarReal(array("id_usuario" => $idUsuario, "folio" => $folio));

echo json_encode(array(
    "ok" => empty($respuesta["error"]),
    "modo" => "pedido_entrega_real_authorized",
    "respaldo" => $respaldo,
    "respuesta" => $respuesta
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
