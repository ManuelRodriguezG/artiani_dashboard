<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-07-29
 * Proposito: validar el comparativo read-only de abastecimiento por SKU.
 * Impacto: Proveedores; no escribe datos ni modifica costos, relaciones, solicitudes u ordenes.
 * Contrato: ejecuta una consulta de muestra y reporta resumen tecnico.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/Proveedores.php";

$modelo = new Proveedores();
$filtros = array(
    "termino" => isset($argv[1]) ? $argv[1] : "TP",
    "solo_multiples" => 0,
    "limite" => 20
);

$respuesta = $modelo->compararAbastecimientoSkuErp($filtros);
$salida = array(
    "ok" => empty($respuesta["error"]),
    "tipo" => isset($respuesta["tipo"]) ? $respuesta["tipo"] : "",
    "mensaje" => isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : "",
    "termino" => $filtros["termino"],
    "resumen" => isset($respuesta["depurar"]["resumen"]) ? $respuesta["depurar"]["resumen"] : null,
    "primer_sku" => null
);

if (isset($respuesta["depurar"]["grupos"][0])) {
    $grupo = $respuesta["depurar"]["grupos"][0];
    $salida["primer_sku"] = array(
        "sku" => isset($grupo["sku"]) ? $grupo["sku"] : "",
        "nombre" => isset($grupo["nombre"]) ? $grupo["nombre"] : "",
        "opciones" => isset($grupo["opciones"]) ? count($grupo["opciones"]) : 0
    );
}

echo json_encode($salida, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
