<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-06-30.
 * Proposito: generar ticket formal read-only de una devolucion/cancelacion POS aplicada.
 * Impacto: permite evidencia UAT de comprobante de devolucion sin escribir BD.
 * Contrato: read-only; no reembolsa, no modifica venta y no mueve inventario.
 */

$args = isset($argv) ? $argv : array();
$folioDevolucion = "DEV-20260630-000001";
$idDevolucion = 0;

foreach ($args as $arg) {
    if (strpos($arg, "--folio_devolucion=") === 0) {
        $folioDevolucion = trim(substr($arg, 19), "\"' ");
    } elseif (strpos($arg, "--folio=") === 0) {
        $folioDevolucion = trim(substr($arg, 8), "\"' ");
    } elseif (strpos($arg, "--id_devolucion=") === 0) {
        $idDevolucion = intval(trim(substr($arg, 16), "\"' "));
    }
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";

$ventas = new VentasErp();
$respuesta = $ventas->ticketDevolucionFormalReadOnly(array(
    "id_devolucion" => $idDevolucion,
    "folio_devolucion" => $folioDevolucion
));
$depurar = isset($respuesta["depurar"]) && is_array($respuesta["depurar"]) ? $respuesta["depurar"] : array();
responder(array(
    "ok" => empty($respuesta["error"]) && isset($respuesta["tipo"]) && $respuesta["tipo"] === "success" && empty($depurar["hallazgos"]),
    "modo" => "ventas_pos_ticket_devolucion_readonly",
    "read_only" => true,
    "folio_devolucion" => isset($depurar["devolucion"]["folio"]) ? $depurar["devolucion"]["folio"] : $folioDevolucion,
    "folio_venta" => isset($depurar["devolucion"]["folio_venta"]) ? $depurar["devolucion"]["folio_venta"] : null,
    "hallazgos" => isset($depurar["hallazgos"]) ? $depurar["hallazgos"] : array(),
    "ticket_texto" => isset($depurar["ticket_texto"]) ? $depurar["ticket_texto"] : null,
    "respuesta" => $respuesta
));

function responder($datos) {
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}
