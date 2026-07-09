<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-04.
 * Proposito: validar reportes ejecutivos POS/caja en modo read-only.
 * Impacto: evidencia KPIs, turnos y corte imprimible sin mover caja ni inventario.
 * Contrato: no escribe BD.
 */

$fechaDesde = date("Y-m-d", strtotime("-10 days"));
$fechaHasta = date("Y-m-d");

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";

$ventas = new VentasErp();
$reporte = $ventas->reporteCajaPosReadOnly(array(
    "fecha_desde" => $fechaDesde,
    "fecha_hasta" => $fechaHasta,
    "id_almacen" => 5,
    "id_caja" => 2,
    "solo_diferencias" => 0
));
$corte = $ventas->corteTurnoFormalReadOnly(array("folio" => "TUR-20260704-002-002"));

$depurarReporte = isset($reporte["depurar"]) && is_array($reporte["depurar"]) ? $reporte["depurar"] : array();
$resumen = isset($depurarReporte["resumen"]) ? $depurarReporte["resumen"] : array();
$turnos = isset($depurarReporte["turnos"]) && is_array($depurarReporte["turnos"]) ? $depurarReporte["turnos"] : array();
$depurarCorte = isset($corte["depurar"]) && is_array($corte["depurar"]) ? $corte["depurar"] : array();

echo json_encode(array(
    "ok" => empty($reporte["error"]) && empty($corte["error"]) && intval(valorLocal($resumen, "turnos", 0)) > 0
        && array_key_exists("ventas_total", $resumen)
        && array_key_exists("movimientos_count", $resumen)
        && strpos((string) valorLocal($depurarCorte, "corte_texto", ""), "CORTE DE CAJA POS") !== false,
    "modo" => "reportes_caja_readonly",
    "filtros" => array(
        "fecha_desde" => $fechaDesde,
        "fecha_hasta" => $fechaHasta,
        "id_almacen" => 5,
        "id_caja" => 2
    ),
    "resumen" => $resumen,
    "turnos_muestra" => array_slice($turnos, 0, 5),
    "corte_lineas" => isset($depurarCorte["corte_lineas"]) && is_array($depurarCorte["corte_lineas"]) ? count($depurarCorte["corte_lineas"]) : 0,
    "corte_hallazgos" => isset($depurarCorte["hallazgos"]) ? $depurarCorte["hallazgos"] : array(),
    "contrato" => array(
        "no_escribe_bd" => true,
        "no_cierra_turno" => true,
        "no_resuelve_diferencias" => true,
        "no_mueve_inventario" => true
    )
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

function valorLocal($datos, $campo, $default = null) {
    return is_array($datos) && array_key_exists($campo, $datos) ? $datos[$campo] : $default;
}
