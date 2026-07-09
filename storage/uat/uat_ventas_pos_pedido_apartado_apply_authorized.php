<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-05.
 * Proposito: ejecutar UAT real controlada de creacion de pedido/apartado POS.
 * Impacto: crea venta ERP tipo pedido/apartado, reserva inventario, registra anticipo y caja.
 * Contrato: bloqueado por token; no debe ejecutarse sin autorizacion explicita y respaldo vigente.
 */

$args = isset($argv) ? $argv : array();
$autorizar = "";
$respaldo = "";
$idUsuario = 1;
$idSku = 1760;
$cantidad = 1;
$precio = 295;
$anticipo = 100;
$telefono = "3312345678";
$cliente = "Cliente UAT Apartado POS";
$tipoDocumento = "apartado";
$fechaCompromiso = date("Y-m-d", strtotime("+7 days"));
$itemsJson = "";
$itemsCli = array();

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
    } elseif (strpos($arg, "--anticipo=") === 0) {
        $anticipo = floatval(trim(substr($arg, 11), "\"' "));
    } elseif (strpos($arg, "--telefono=") === 0) {
        $telefono = trim(substr($arg, 11), "\"' ");
    } elseif (strpos($arg, "--cliente=") === 0) {
        $cliente = trim(substr($arg, 10), "\"' ");
    } elseif (strpos($arg, "--tipo_documento=") === 0) {
        $tipoDocumento = trim(substr($arg, 17), "\"' ");
    } elseif (strpos($arg, "--fecha_compromiso=") === 0) {
        $fechaCompromiso = trim(substr($arg, 20), "\"' ");
    } elseif (strpos($arg, "--items_json=") === 0) {
        $itemsJson = trim(substr($arg, 13), "\"' ");
    } elseif (strpos($arg, "--item=") === 0) {
        $partes = preg_split('/[:,]/', trim(substr($arg, 7), "\"' "));
        if (count($partes) >= 3) {
            $itemsCli[] = array(
                "id_sku" => intval($partes[0]),
                "cantidad" => floatval($partes[1]),
                "precio_unitario" => floatval($partes[2]),
                "modo_salida" => "existencia_agregada"
            );
        }
    }
}

if ($autorizar !== "VENTAS_POS_PEDIDO_REAL" || $respaldo === "") {
    echo json_encode(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "Falta autorizacion VENTAS_POS_PEDIDO_REAL o respaldo vigente",
        "contrato" => array(
            "no_escribe_bd" => true,
            "no_crea_pedido" => true,
            "no_registra_abono" => true,
            "no_reserva_inventario" => true
        )
    ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";

$items = array();
if ($itemsJson !== "") {
    $items = json_decode($itemsJson, true);
    if (!is_array($items)) {
        echo json_encode(array(
            "ok" => false,
            "modo" => "bloqueado",
            "mensaje" => "items_json invalido",
            "contrato" => array("no_escribe_bd" => true)
        ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
} elseif (!empty($itemsCli)) {
    $items = $itemsCli;
} else {
    $items = array(array(
        "id_sku" => $idSku,
        "cantidad" => $cantidad,
        "precio_unitario" => $precio,
        "modo_salida" => "existencia_agregada"
    ));
}

$ventas = new VentasErp();
$respuesta = $ventas->pedidoGuardarReal(array(
    "id_usuario" => $idUsuario,
    "tipo_documento" => $tipoDocumento,
    "cliente_nombre_publico" => $cliente,
    "identificador_cliente" => $telefono,
    "fecha_entrega_compromiso" => $fechaCompromiso,
    "items" => $items,
    "pagos" => array(array(
        "id_metodo_pago" => 1,
        "monto" => $anticipo,
        "referencia" => "UAT-PED-ANTICIPO"
    )),
    "observaciones" => "UAT real pedido/apartado POS autorizada"
));

echo json_encode(array(
    "ok" => empty($respuesta["error"]),
    "modo" => "pedido_apartado_real_authorized",
    "respaldo" => $respaldo,
    "partidas_enviadas" => count($items),
    "respuesta" => $respuesta
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
