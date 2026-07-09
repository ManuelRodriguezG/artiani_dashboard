<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-06-28.
 * Proposito: validar excepciones comerciales POS sin escribir BD.
 * Impacto: prepara precio manual/descuento con autorizacion antes de venta real.
 * Contrato: read-only; no crea ventas, pagos, caja, descuentos ni movimientos de inventario.
 */

$args = isset($argv) ? $argv : array();
$idAlmacen = 5;
$idSku = 1760;
$identificador = "5550000000";
$precioManual = 285;
$descuentoMonto = 20;

foreach ($args as $arg) {
    if (strpos($arg, "--id_almacen=") === 0) {
        $idAlmacen = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_sku=") === 0) {
        $idSku = intval(trim(substr($arg, 9), "\"' "));
    } elseif (strpos($arg, "--identificador=") === 0) {
        $identificador = trim(substr($arg, 16), "\"' ");
    } elseif (strpos($arg, "--precio_manual=") === 0) {
        $precioManual = floatval(trim(substr($arg, 16), "\"' "));
    } elseif (strpos($arg, "--descuento_monto=") === 0) {
        $descuentoMonto = floatval(trim(substr($arg, 18), "\"' "));
    }
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";

$ventas = new VentasErp();
$items = json_encode(array(array(
    "id_sku" => $idSku,
    "cantidad" => 1,
    "modo_salida" => "existencia_agregada"
)));

$sinAutorizacion = $ventas->excepcionComercialDryRun(array(
    "id_almacen" => $idAlmacen,
    "canal" => "pos",
    "identificador_cliente" => $identificador,
    "tipo_excepcion" => "precio_manual",
    "id_sku" => $idSku,
    "precio_manual" => $precioManual,
    "items" => $items
));

$precioManualOk = $ventas->excepcionComercialDryRun(array(
    "id_almacen" => $idAlmacen,
    "canal" => "pos",
    "identificador_cliente" => $identificador,
    "tipo_excepcion" => "precio_manual",
    "id_sku" => $idSku,
    "precio_manual" => $precioManual,
    "motivo" => "UAT precio manual",
    "codigo_autorizacion" => "SUP-UAT-001",
    "items" => $items
));

$descuentoGeneralOk = $ventas->excepcionComercialDryRun(array(
    "id_almacen" => $idAlmacen,
    "canal" => "pos",
    "identificador_cliente" => $identificador,
    "tipo_excepcion" => "descuento_general",
    "descuento_monto" => $descuentoMonto,
    "motivo" => "UAT descuento general",
    "codigo_autorizacion" => "SUP-UAT-002",
    "items" => $items
));

echo json_encode(array(
    "ok" => empty($sinAutorizacion["error"]) && empty($precioManualOk["error"]) && empty($descuentoGeneralOk["error"]),
    "modo" => "excepcion_comercial_dryrun_readonly",
    "sin_autorizacion" => $sinAutorizacion,
    "precio_manual_ok" => $precioManualOk,
    "descuento_general_ok" => $descuentoGeneralOk,
    "siguiente_paso" => "Definir permisos/supervisor y aplicador real para guardar excepciones en venta, solo con autorizacion y respaldo."
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
