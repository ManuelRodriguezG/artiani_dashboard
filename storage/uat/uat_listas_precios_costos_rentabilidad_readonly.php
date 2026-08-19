<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-08-19
 * Proposito: validar que Listas de precios consume el costo vigente central de Rentabilidad.
 * Impacto: evita que margen comercial use costos capturados en Catalogo para presentaciones, aperturas o paquetes.
 * Contrato: read-only; no escribe BD, no modifica Catalogo, no actualiza precios y no toca Ventas operativas.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/ListasPreciosErp.php";

$modelo = new ListasPreciosErp();
$respuesta = $modelo->productosParaListaReadOnly(array(
    "q" => "TP-40372-500GR",
    "solo" => "todos",
    "por_pagina" => 50
));

$productos = isset($respuesta["depurar"]["productos"]) && is_array($respuesta["depurar"]["productos"]) ? $respuesta["depurar"]["productos"] : array();
$objetivo = null;
foreach ($productos as $producto) {
    if (isset($producto["sku"]) && $producto["sku"] === "TP-40372-500GR") {
        $objetivo = $producto;
        break;
    }
}

$fallas = array();
if (!empty($respuesta["error"])) {
    $fallas[] = array("id" => "LP-COST-001", "mensaje" => $respuesta["mensaje"]);
}
if (!$objetivo) {
    $fallas[] = array("id" => "LP-COST-002", "mensaje" => "No se encontro TP-40372-500GR en productosParaListaReadOnly");
} else {
    $fuente = isset($objetivo["costo_fuente"]) ? $objetivo["costo_fuente"] : "";
    $costo = isset($objetivo["costo_referencia"]) ? floatval($objetivo["costo_referencia"]) : 0;
    $formula = isset($objetivo["costo_formula"]) ? $objetivo["costo_formula"] : "";
    $resolucion = isset($objetivo["costo_resolucion"]) && is_array($objetivo["costo_resolucion"]) ? $objetivo["costo_resolucion"] : array();

    if ($fuente !== "derivado_presentacion") {
        $fallas[] = array("id" => "LP-COST-003", "mensaje" => "Listas debe usar derivado_presentacion para TP-40372-500GR", "fuente_actual" => $fuente);
    }
    if (abs($costo - 92.133621) > 0.01) {
        $fallas[] = array("id" => "LP-COST-004", "mensaje" => "Costo de lista fuera de tolerancia", "costo_actual" => $costo);
    }
    if ($formula !== "costo_origen / factor_origen * factor_salida_base * (1 + merma)") {
        $fallas[] = array("id" => "LP-COST-005", "mensaje" => "Formula de costo derivado no viaja a Listas", "formula_actual" => $formula);
    }
    if ((isset($resolucion["sku_origen"]) ? $resolucion["sku_origen"] : "") !== "TP-40372") {
        $fallas[] = array("id" => "LP-COST-006", "mensaje" => "Listas no recibio SKU origen desde Rentabilidad", "sku_origen_actual" => isset($resolucion["sku_origen"]) ? $resolucion["sku_origen"] : null);
    }
}

header("Content-Type: application/json; charset=utf-8");
echo json_encode(array(
    "ok" => empty($fallas),
    "modo" => "listas_precios_costos_rentabilidad_readonly",
    "contrato" => array(
        "solo_lectura" => true,
        "no_escribe_bd" => true,
        "no_modifica_catalogo" => true,
        "no_actualiza_precios" => true,
        "no_toca_ventas_operativas" => true
    ),
    "fallas" => $fallas,
    "sku" => $objetivo ? array(
        "id_sku" => $objetivo["id_sku"],
        "sku" => $objetivo["sku"],
        "costo_lista" => $objetivo["costo_referencia"],
        "costo_catalogo_historico" => $objetivo["costo_referencia_original"],
        "fuente" => $objetivo["costo_fuente"],
        "confianza" => $objetivo["costo_confianza"],
        "formula" => $objetivo["costo_formula"],
        "sku_origen" => isset($objetivo["costo_resolucion"]["sku_origen"]) ? $objetivo["costo_resolucion"]["sku_origen"] : null
    ) : null
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
