<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-13.
 * Proposito: consultar la bandeja POS de atenciones persistentes por almacen en modo UAT/read-only.
 * Impacto: valida que caja pueda ver cuentas creadas por otros operadores sin tomar, cobrar ni modificar registros.
 * Contrato: solo lectura; no bloquea atenciones ni mueve caja/inventario.
 */

$idAlmacen = 0;
foreach (isset($argv) ? $argv : array() as $arg) {
    if (strpos($arg, "--id_almacen=") === 0) {
        $idAlmacen = intval(trim(substr($arg, 13), "\"' "));
    }
}

chdir(__DIR__ . "/../../public");
$_SERVER["SERVER_NAME"] = "panel.com.local";
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";

$ventas = new VentasErp();
$resultado = $ventas->atencionesBandejaDryRun(array("id_almacen" => $idAlmacen));

echo json_encode(array(
    "ok" => empty($resultado["error"]),
    "modo" => "ventas_pos_atenciones_bandeja_readonly",
    "host" => "panel.com.local",
    "id_almacen" => $idAlmacen,
    "resultado" => $resultado
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
