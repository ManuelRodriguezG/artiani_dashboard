<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-04.
 * Proposito: validar corte formal POS read-only para un turno cerrado.
 * Impacto: evidencia impresion/reimpresion de corte sin mover caja ni inventario.
 * Contrato: no escribe BD.
 */

$folio = "TUR-20260704-002-002";
$compacto = false;
$esperarSaldoCrm = false;
foreach (isset($argv) ? $argv : array() as $arg) {
    if (strpos($arg, "--folio=") === 0) {
        $folio = trim(substr($arg, 8), "\"' ");
    } elseif ($arg === "--compact=1" || $arg === "--compacto=1") {
        $compacto = true;
    } elseif ($arg === "--esperar_saldo_crm=1") {
        $esperarSaldoCrm = true;
    }
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";

$ventas = new VentasErp();
$respuesta = $ventas->corteTurnoFormalReadOnly(array("folio" => $folio));
$depurar = isset($respuesta["depurar"]) && is_array($respuesta["depurar"]) ? $respuesta["depurar"] : array();
$texto = isset($depurar["corte_texto"]) ? $depurar["corte_texto"] : "";
$hallazgos = isset($depurar["hallazgos"]) && is_array($depurar["hallazgos"]) ? $depurar["hallazgos"] : array();
$checks = array(
    "tiene_corte_pos" => strpos($texto, "CORTE DE CAJA POS") !== false,
    "tiene_folio" => strpos($texto, $folio) !== false,
    "tiene_diferencia" => strpos($texto, "Diferencia") !== false,
    "muestra_pagos_sin_caja" => strpos($texto, "Pagos sin caja") !== false,
    "muestra_saldo_cliente_no_caja" => strpos($texto, "Saldo cliente no caja") !== false
);
if ($esperarSaldoCrm) {
    if (!$checks["muestra_pagos_sin_caja"]) {
        $hallazgos[] = "El corte no separa pagos sin caja.";
    }
    if (!$checks["muestra_saldo_cliente_no_caja"]) {
        $hallazgos[] = "El corte no muestra saldo cliente como pago sin caja.";
    }
}

$salida = array(
    "ok" => empty($respuesta["error"]) && isset($respuesta["tipo"]) && $respuesta["tipo"] === "success"
        && $checks["tiene_corte_pos"]
        && $checks["tiene_folio"]
        && $checks["tiene_diferencia"]
        && empty($hallazgos),
    "modo" => "corte_turno_readonly",
    "folio" => $folio,
    "lineas" => isset($depurar["corte_lineas"]) && is_array($depurar["corte_lineas"]) ? count($depurar["corte_lineas"]) : 0,
    "hallazgos" => $hallazgos,
    "checks" => $checks,
    "preview" => implode("\n", array_slice(explode("\n", $texto), 0, $compacto ? 32 : 18)),
    "contrato" => array(
        "no_escribe_bd" => true,
        "no_cierra_turno" => true,
        "no_mueve_caja" => true,
        "no_mueve_inventario" => true
    )
);

echo json_encode($salida, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
