<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-06-29.
 * Proposito: ejecutar dry-run de devolucion/cancelacion POS sin escribir BD.
 * Impacto: valida venta, detalle, cantidades, motivo, decision de inventario y reembolso estimado.
 * Contrato: read-only; no crea devolucion, no reembolsa caja y no mueve inventario.
 */

$args = isset($argv) ? $argv : array();
$folio = "";
$idVenta = 0;
$idUsuario = 0;
$idDetalle = 0;
$cantidad = 0;
$tipo = "devolucion";
$motivo = "UAT devolucion POS";
$decisionInventario = "cuarentena";

foreach ($args as $arg) {
    if (strpos($arg, "--folio=") === 0) {
        $folio = trim(substr($arg, 8), "\"' ");
    } elseif (strpos($arg, "--id_venta=") === 0) {
        $idVenta = intval(trim(substr($arg, 11), "\"' "));
    } elseif (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_venta_detalle=") === 0) {
        $idDetalle = intval(trim(substr($arg, 19), "\"' "));
    } elseif (strpos($arg, "--cantidad=") === 0) {
        $cantidad = floatval(trim(substr($arg, 11), "\"' "));
    } elseif (strpos($arg, "--tipo=") === 0) {
        $tipo = trim(substr($arg, 7), "\"' ");
    } elseif (strpos($arg, "--motivo=") === 0) {
        $motivo = trim(substr($arg, 9), "\"' ");
    } elseif (strpos($arg, "--decision_inventario=") === 0) {
        $decisionInventario = trim(substr($arg, 22), "\"' ");
    }
}

if ($folio === "" && $idVenta <= 0) {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "Indica --folio=FOLIO o --id_venta=ID."
    ));
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";

$ventas = new VentasErp();
$items = array();
if ($idDetalle > 0 || $cantidad > 0) {
    $items[] = array(
        "id_venta_detalle" => $idDetalle,
        "cantidad_base" => $cantidad
    );
}
$respuesta = $ventas->devolucionDryRun(array(
    "folio" => $folio,
    "id_venta" => $idVenta,
    "id_usuario" => $idUsuario,
    "tipo" => $tipo,
    "motivo" => $motivo,
    "decision_inventario" => $decisionInventario,
    "items" => json_encode($items)
));

$depurar = isset($respuesta["depurar"]) && is_array($respuesta["depurar"]) ? $respuesta["depurar"] : array();
responder(array(
    "ok" => empty($respuesta["error"]) && isset($respuesta["tipo"]) && $respuesta["tipo"] === "success",
    "modo" => "devolucion_dryrun_readonly",
    "respuesta" => $respuesta,
    "resumen" => array(
        "bloqueos" => isset($depurar["bloqueos"]) ? $depurar["bloqueos"] : array(),
        "avisos" => isset($depurar["avisos"]) ? $depurar["avisos"] : array(),
        "reembolso_estimado" => isset($depurar["totales"]["reembolso_estimado"]) ? $depurar["totales"]["reembolso_estimado"] : null,
        "partidas" => isset($depurar["partidas"]) ? count($depurar["partidas"]) : 0
    )
));

function responder($datos) {
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}
