<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-13.
 * Proposito: consultar detalle read-only de una atencion POS persistente.
 * Impacto: valida que caja pueda cargar partidas al carrito local sin tomar, bloquear, cobrar ni convertir la atencion.
 * Contrato: solo lectura; no mueve caja, inventario, ventas ni estatus.
 */

$idAtencion = 0;
$idAlmacen = 0;
foreach (isset($argv) ? $argv : array() as $arg) {
    if (strpos($arg, "--id_atencion=") === 0) {
        $idAtencion = intval(trim(substr($arg, 14), "\"' "));
    } elseif (strpos($arg, "--id_almacen=") === 0) {
        $idAlmacen = intval(trim(substr($arg, 13), "\"' "));
    }
}

chdir(__DIR__ . "/../../public");
$_SERVER["SERVER_NAME"] = "panel.com.local";
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";

$ventas = new VentasErp();
$resultado = $ventas->atencionDetalleReadOnly(array(
    "id_atencion" => $idAtencion,
    "id_almacen" => $idAlmacen
));

echo json_encode(array(
    "ok" => empty($resultado["error"]),
    "modo" => "ventas_pos_atencion_detalle_readonly",
    "host" => "panel.com.local",
    "id_atencion" => $idAtencion,
    "id_almacen" => $idAlmacen,
    "resultado" => $resultado
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
