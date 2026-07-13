<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-06-29.
 * Proposito: ejecutar UAT de cobro real POS UI invocando el mismo modelo usado por /ventas/pos_confirmar_erp.
 * Impacto: crea venta ERP, pagos, movimiento de caja, kardex, garantia snapshot y trazabilidad.
 * Contrato: BLOQUEADO por defecto; requiere autorizacion, respaldo, usuario, turno abierto y stock.
 */

$args = isset($argv) ? $argv : array();
$autorizar = "";
$respaldo = "";
$idUsuario = 0;
$idSku = 0;
$cantidad = 0;
$precio = 0;
$pago = 0;
$identificadorCliente = "";
$cliente = "Cliente mostrador UAT";

foreach ($args as $arg) {
    if (strpos($arg, "--autorizar=") === 0) {
        $autorizar = trim(substr($arg, 12), "\"' ");
    } elseif (strpos($arg, "--respaldo=") === 0) {
        $respaldo = trim(substr($arg, 11), "\"' ");
    } elseif (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_sku=") === 0) {
        $idSku = intval(trim(substr($arg, 9), "\"' "));
    } elseif (strpos($arg, "--cantidad=") === 0) {
        $cantidad = floatval(trim(substr($arg, 11), "\"' "));
    } elseif (strpos($arg, "--precio=") === 0) {
        $precio = floatval(trim(substr($arg, 9), "\"' "));
    } elseif (strpos($arg, "--pago=") === 0) {
        $pago = floatval(trim(substr($arg, 7), "\"' "));
    } elseif (strpos($arg, "--identificador_cliente=") === 0) {
        $identificadorCliente = trim(substr($arg, 24), "\"' ");
    } elseif (strpos($arg, "--cliente=") === 0) {
        $cliente = trim(substr($arg, 10), "\"' ");
    }
}

$respaldoPareceRuta = preg_match('/^[A-Za-z]:[\\\\\\/]/', $respaldo) === 1 || strpos($respaldo, "\\") !== false || strpos($respaldo, "/") !== false;
$respaldoOk = $respaldo !== "" && (!$respaldoPareceRuta || is_file($respaldo));
if ($autorizar !== "VENTAS_POS_COBRO_UI_REAL" || $idUsuario <= 0 || !$respaldoOk) {
    responder(array(
        "ok" => false,
        "bloqueado" => true,
        "modo" => "guardrail",
        "mensaje" => "No se ejecuto cobro real POS UI. Falta autorizacion, respaldo valido o id_usuario.",
        "requisitos" => array(
            "--autorizar=VENTAS_POS_COBRO_UI_REAL",
            "--respaldo=RUTA_RESPALDO_EXISTENTE_O_REFERENCIA_VIGENTE",
            "--id_usuario=ID_OPERADOR_POS",
            "--id_sku=ID_SKU",
            "--cantidad=CANTIDAD",
            "--precio=PRECIO",
            "--pago=PAGO"
        ),
        "validacion_respaldo" => array(
            "referencia" => $respaldo,
            "parece_ruta_local" => $respaldoPareceRuta,
            "archivo_existe" => $respaldoPareceRuta ? is_file($respaldo) : false
        )
    ));
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";

$ventas = new VentasErp();
$datos = array(
    "id_usuario" => $idUsuario,
    "canal" => "pos",
    "cliente_nombre_publico" => $cliente,
    "identificador_cliente" => $identificadorCliente,
    "items" => json_encode(array(array(
        "id_sku" => $idSku,
        "cantidad" => $cantidad,
        "precio_unitario" => $precio,
        "modo_salida" => "existencia_agregada"
    ))),
    "pagos" => json_encode(array(array(
        "id_metodo_pago" => 1,
        "monto" => $pago,
        "referencia" => "UAT-POS-UI"
    ))),
    "exigir_pago_completo" => 1,
    "observaciones" => "UAT cobro real POS UI"
);

$respuesta = $ventas->confirmarVentaPosReal($datos);
$depurar = isset($respuesta["depurar"]) && is_array($respuesta["depurar"]) ? $respuesta["depurar"] : array();
responder(array(
    "ok" => empty($respuesta["error"]) && isset($respuesta["tipo"]) && $respuesta["tipo"] === "success",
    "modo" => "cobro_real_pos_ui_uat",
    "respaldo_ref" => $respaldo,
    "respuesta" => $respuesta,
    "folio" => isset($depurar["folio"]) ? $depurar["folio"] : null,
    "id_venta" => isset($depurar["id_venta"]) ? $depurar["id_venta"] : null,
    "siguiente_paso" => empty($respuesta["error"]) ? "Validar post-venta, ticket, kardex y cierre." : "Resolver error antes de repetir UAT."
));

function responder($datos) {
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}
