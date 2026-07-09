<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-06-30.
 * Proposito: consultar devoluciones POS con decision fisica pendiente sin escribir BD.
 * Impacto: evidencia UAT para separar reversa comercial de inspeccion de almacen/inventario.
 * Contrato: read-only; no crea kardex, no reintegra inventario y no modifica devoluciones.
 */

$args = isset($argv) ? $argv : array();
$idAlmacen = 0;
$decisionInventario = "pendientes";
$folio = "";
$limite = 50;

foreach ($args as $arg) {
    if (strpos($arg, "--id_almacen=") === 0) {
        $idAlmacen = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--decision_inventario=") === 0) {
        $decisionInventario = trim(substr($arg, 22), "\"' ");
    } elseif (strpos($arg, "--folio=") === 0) {
        $folio = trim(substr($arg, 8), "\"' ");
    } elseif (strpos($arg, "--limite=") === 0) {
        $limite = intval(trim(substr($arg, 9), "\"' "));
    }
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";

$ventas = new VentasErp();
$respuesta = $ventas->devolucionesInventarioPendientesReadOnly(array(
    "id_almacen" => $idAlmacen,
    "decision_inventario" => $decisionInventario,
    "folio" => $folio,
    "limite" => $limite
));
$depurar = isset($respuesta["depurar"]) && is_array($respuesta["depurar"]) ? $respuesta["depurar"] : array();

responder(array(
    "ok" => empty($respuesta["error"]) && isset($respuesta["tipo"]) && $respuesta["tipo"] === "success",
    "modo" => "ventas_pos_devoluciones_inventario_pendientes_readonly",
    "read_only" => true,
    "filtros" => isset($depurar["filtros"]) ? $depurar["filtros"] : array(),
    "resumen" => isset($depurar["resumen"]) ? $depurar["resumen"] : null,
    "pendientes" => isset($depurar["pendientes"]) ? $depurar["pendientes"] : array(),
    "respuesta" => $respuesta
));

function responder($datos) {
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}
