<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-03.
 * Proposito: consultar diferencias de caja POS pendientes de seguimiento sin escribir BD.
 * Impacto: valida faltantes/sobrantes cerrados antes de crear flujo formal de revision.
 * Contrato: read-only; no resuelve diferencias, no mueve caja y no crea evidencia.
 */

$args = isset($argv) ? $argv : array();
$filtros = array(
    "fecha_desde" => date("Y-m-d", strtotime("-30 days")),
    "fecha_hasta" => date("Y-m-d"),
    "id_almacen" => 0,
    "id_caja" => 0,
    "estado_revision" => "pendiente_revision",
    "limite" => 50
);
$compacto = false;

foreach ($args as $arg) {
    if (strpos($arg, "--fecha_desde=") === 0) {
        $filtros["fecha_desde"] = trim(substr($arg, 14), "\"' ");
    } elseif (strpos($arg, "--fecha_hasta=") === 0) {
        $filtros["fecha_hasta"] = trim(substr($arg, 14), "\"' ");
    } elseif (strpos($arg, "--id_almacen=") === 0) {
        $filtros["id_almacen"] = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_caja=") === 0) {
        $filtros["id_caja"] = intval(trim(substr($arg, 10), "\"' "));
    } elseif (strpos($arg, "--estado_revision=") === 0) {
        $filtros["estado_revision"] = trim(substr($arg, 18), "\"' ");
    } elseif (strpos($arg, "--limite=") === 0) {
        $filtros["limite"] = intval(trim(substr($arg, 9), "\"' "));
    } elseif ($arg === "--compact=1") {
        $compacto = true;
    }
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";

$ventas = new VentasErp();
$respuesta = $ventas->diferenciasCajaPendientesReadOnly($filtros);
$depurar = isset($respuesta["depurar"]) && is_array($respuesta["depurar"]) ? $respuesta["depurar"] : array();

echo json_encode(array(
    "ok" => empty($respuesta["error"]),
    "modo" => "ventas_pos_diferencias_caja_readonly",
    "read_only" => true,
    "respuesta" => $compacto ? null : $respuesta,
    "resumen" => isset($depurar["resumen"]) ? $depurar["resumen"] : array(),
    "primer_diferencia" => !empty($depurar["diferencias"]) ? $depurar["diferencias"][0] : null,
    "contrato" => array(
        "no_escribe_bd" => true,
        "no_resuelve_diferencias" => true,
        "no_mueve_caja" => true,
        "no_crea_evidencia" => true
    )
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
