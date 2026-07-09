<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-06-28.
 * Proposito: validar que POS no acepte precio_unitario alterado por navegador.
 * Impacto: protege venta real y atenciones contra cambios de precio sin excepcion comercial autorizada.
 * Contrato: read-only; no crea venta, pago, caja, inventario ni autorizaciones.
 */

$args = isset($argv) ? $argv : array();
$idAlmacen = 5;
$idSku = 1760;
$identificador = "5550000000";
$precioCorrecto = 295;
$precioAlterado = 285;

foreach ($args as $arg) {
    if (strpos($arg, "--id_almacen=") === 0) {
        $idAlmacen = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_sku=") === 0) {
        $idSku = intval(trim(substr($arg, 9), "\"' "));
    } elseif (strpos($arg, "--identificador=") === 0) {
        $identificador = trim(substr($arg, 16), "\"' ");
    } elseif (strpos($arg, "--precio_correcto=") === 0) {
        $precioCorrecto = floatval(trim(substr($arg, 18), "\"' "));
    } elseif (strpos($arg, "--precio_alterado=") === 0) {
        $precioAlterado = floatval(trim(substr($arg, 18), "\"' "));
    }
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";

$ventas = new VentasErp();

$correcto = $ventas->prevalidarCarritoPos(array(
    "id_almacen" => $idAlmacen,
    "id_caja" => 0,
    "id_turno_caja" => 0,
    "canal" => "pos",
    "identificador_cliente" => $identificador,
    "items" => json_encode(array(array(
        "id_sku" => $idSku,
        "cantidad" => 1,
        "precio_unitario" => $precioCorrecto,
        "modo_salida" => "existencia_agregada"
    ))),
    "pagos" => "[]",
    "exigir_pago_completo" => 0
));

$alterado = $ventas->prevalidarCarritoPos(array(
    "id_almacen" => $idAlmacen,
    "id_caja" => 0,
    "id_turno_caja" => 0,
    "canal" => "pos",
    "identificador_cliente" => $identificador,
    "items" => json_encode(array(array(
        "id_sku" => $idSku,
        "cantidad" => 1,
        "precio_unitario" => $precioAlterado,
        "modo_salida" => "existencia_agregada"
    ))),
    "pagos" => "[]",
    "exigir_pago_completo" => 0
));

$bloqueosAlterado = isset($alterado["depurar"]["bloqueos"]) && is_array($alterado["depurar"]["bloqueos"])
    ? $alterado["depurar"]["bloqueos"]
    : array();
$bloqueoPrecio = false;
foreach ($bloqueosAlterado as $bloqueo) {
    if (strpos($bloqueo, "Precio enviado por POS no coincide") !== false) {
        $bloqueoPrecio = true;
    }
}

echo json_encode(array(
    "ok" => empty($correcto["error"]) && empty($alterado["error"]) && $bloqueoPrecio,
    "modo" => "precio_guardrail_readonly",
    "precio_correcto" => $correcto,
    "precio_alterado" => $alterado,
    "bloqueo_precio_alterado_detectado" => $bloqueoPrecio,
    "siguiente_paso" => "Preparar permisos y persistencia de excepcion comercial antes de permitir precio manual real."
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
