<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-25.
 * Proposito: validar metodos de lectura de Almacen/Apertura de empaques sin usar navegador.
 * Impacto: detecta errores de backend en listados base antes de UAT visual.
 * Contrato: read-only; no crea aperturas, no confirma inventario y no modifica configuracion.
 */

chdir(__DIR__ . "/../../public");
$_SERVER["SERVER_NAME"] = "panel.com.local";
require_once "../app/iniciador.php";
require_once "../app/modelos/Almacenes.php";

$almacen = new Almacenes();
$resultados = array(
    "skus_apertura" => resumir($almacen->consultar_skus_apertura_empaque(array())),
    "aperturas" => resumir($almacen->consultar_aperturas_empaque(array())),
    "almacenes" => resumir($almacen->obtener_almacenes(array("incluir_inactivos" => 0)))
);

$bloqueos = array();
foreach ($resultados as $clave => $resultado) {
    if (!empty($resultado["error"])) {
        $bloqueos[] = $clave . ": " . $resultado["mensaje"];
    }
}

echo json_encode(array(
    "ok" => empty($bloqueos),
    "modo" => "almacen_apertura_endpoints_readonly",
    "host" => "panel.com.local",
    "resultados" => $resultados,
    "bloqueos" => $bloqueos,
    "contrato" => array(
        "read_only" => true,
        "no_crea_aperturas" => true,
        "no_confirma_inventario" => true,
        "no_modifica_configuracion" => true
    )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

exit(empty($bloqueos) ? 0 : 1);

function resumir($respuesta) {
    $depurar = isset($respuesta["depurar"]) ? $respuesta["depurar"] : array();
    return array(
        "error" => !empty($respuesta["error"]),
        "mensaje" => isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : "",
        "total" => is_array($depurar) ? count($depurar) : 0,
        "muestra" => is_array($depurar) && isset($depurar[0]) ? $depurar[0] : null
    );
}
