<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-06.
 * Proposito: verificar apartado/pedido POS multipartida despues de UAT real.
 * Impacto: consulta venta, partidas, pagos, caja y reservas sin modificar datos.
 * Contrato: read-only; requiere --folio=APT/PED.
 */

$args = isset($argv) ? $argv : array();
$folio = "";
$compacto = false;
$esperarPartidas = 2;
$esperarTotal = null;
$esperarPagado = null;
$esperarSaldo = null;

foreach ($args as $arg) {
    if (strpos($arg, "--folio=") === 0) {
        $folio = trim(substr($arg, 8), "\"' ");
    } elseif (strpos($arg, "--esperar_partidas=") === 0) {
        $esperarPartidas = intval(trim(substr($arg, 19), "\"' "));
    } elseif (strpos($arg, "--esperar_total=") === 0) {
        $esperarTotal = floatval(trim(substr($arg, 16), "\"' "));
    } elseif (strpos($arg, "--esperar_pagado=") === 0) {
        $esperarPagado = floatval(trim(substr($arg, 17), "\"' "));
    } elseif (strpos($arg, "--esperar_saldo=") === 0) {
        $esperarSaldo = floatval(trim(substr($arg, 16), "\"' "));
    } elseif ($arg === "--compact=1" || $arg === "--compacto=1") {
        $compacto = true;
    }
}

if ($folio === "") {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "Indica --folio=APT/PED.",
        "contrato" => contratoReadOnly()
    ));
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";

class UatVentasPosPedidoMultipartidaPostDb extends VentasErp {
    public function db() {
        return $this->getConexion();
    }
}

$ventas = new UatVentasPosPedidoMultipartidaPostDb();
$db = $ventas->db();
if (!$db) {
    responder(array(
        "ok" => true,
        "modo" => "pedido_multipartida_post_readonly",
        "read_only" => true,
        "folio" => $folio,
        "hallazgos" => array("Conexion BD no disponible"),
        "contrato" => contratoReadOnly()
    ));
}

$venta = consultarUno($db, "SELECT id_venta, folio, canal, tipo_documento, estatus,
        id_almacen, id_caja, id_turno_caja, id_cliente, cliente_nombre_publico,
        cliente_identificador_publico, subtotal, total, pagado_total, saldo_total,
        anticipo_minimo, fecha_vencimiento, fecha_entrega_compromiso, fecha_venta
    FROM erp_ventas
    WHERE folio=:folio
    LIMIT 1", array(":folio" => $folio));

if (!$venta) {
    responder(array(
        "ok" => false,
        "modo" => "no_encontrado",
        "read_only" => true,
        "folio" => $folio,
        "mensaje" => "No se encontro pedido/apartado.",
        "contrato" => contratoReadOnly()
    ));
}

$idVenta = intval($venta["id_venta"]);
$detalles = consultarTodos($db, "SELECT id_venta_detalle, renglon, id_sku_erp, sku, descripcion,
        controla_inventario, modo_salida, cantidad_venta, cantidad_base, precio_unitario,
        subtotal, total, estatus
    FROM erp_ventas_detalle
    WHERE id_venta=:venta
    ORDER BY renglon ASC", array(":venta" => $idVenta));

$reservas = consultarTodos($db, "SELECT id_reserva_inventario, folio, origen_detalle_id,
        id_existencia_inventario, id_sku_erp, id_almacen, cantidad_reservada,
        cantidad_consumida, cantidad_liberada, estatus, fecha_vencimiento
    FROM erp_inventario_reservas
    WHERE origen_tipo='pedido_pos' AND origen_id=:venta
    ORDER BY id_reserva_inventario ASC", array(":venta" => $idVenta));

$pagos = consultarTodos($db, "SELECT p.id_venta_pago, p.id_movimiento_caja, p.id_metodo_pago,
        p.metodo_pago, p.tipo_pago, p.monto, p.referencia, p.estatus, p.fecha_pago,
        mc.tipo movimiento_tipo, mc.categoria movimiento_categoria, mc.monto movimiento_monto,
        mc.estatus movimiento_estatus
    FROM erp_ventas_pagos p
    LEFT JOIN erp_pos_movimientos_caja mc ON mc.id_movimiento_caja=p.id_movimiento_caja
    WHERE p.id_venta=:venta
    ORDER BY p.id_venta_pago ASC", array(":venta" => $idVenta));

$trazas = consultarTodos($db, "SELECT id_venta_detalle_inventario, id_venta_detalle,
        id_existencia_inventario, id_reserva_inventario, id_movimiento_inventario,
        cantidad_base, estatus
    FROM erp_ventas_detalle_inventario
    WHERE id_venta=:venta
    ORDER BY id_venta_detalle_inventario ASC", array(":venta" => $idVenta));

$resumen = array(
    "partidas" => count($detalles),
    "cantidad_detalle" => sumar($detalles, "cantidad_base"),
    "total_detalle" => sumar($detalles, "total"),
    "reservas" => count($reservas),
    "cantidad_reservada" => sumar($reservas, "cantidad_reservada"),
    "cantidad_consumida" => sumar($reservas, "cantidad_consumida"),
    "pagos" => count($pagos),
    "pagos_total" => sumar($pagos, "monto"),
    "trazas_kardex" => count($trazas)
);

$salida = array(
    "ok" => true,
    "modo" => "pedido_multipartida_post_readonly",
    "read_only" => true,
    "venta" => $venta,
    "resumen" => $resumen,
    "detalles" => $detalles,
    "reservas" => $reservas,
    "pagos" => $pagos,
    "trazas_kardex" => $trazas,
    "esperado" => esperado($esperarPartidas, $esperarTotal, $esperarPagado, $esperarSaldo),
    "hallazgos" => hallazgos($venta, $detalles, $reservas, $pagos, $trazas, $resumen, $esperarPartidas, $esperarTotal, $esperarPagado, $esperarSaldo),
    "contrato" => contratoReadOnly()
);

if ($compacto) {
    $salida = array(
        "ok" => true,
        "modo" => "pedido_multipartida_post_readonly",
        "read_only" => true,
        "folio" => $venta["folio"],
        "estatus" => $venta["estatus"],
        "tipo_documento" => $venta["tipo_documento"],
        "id_turno_caja" => intval($venta["id_turno_caja"]),
        "total" => floatval($venta["total"]),
        "pagado_total" => floatval($venta["pagado_total"]),
        "saldo_total" => floatval($venta["saldo_total"]),
        "resumen" => $resumen,
        "esperado" => $salida["esperado"],
        "hallazgos" => $salida["hallazgos"],
        "contrato" => contratoReadOnly()
    );
}

responder($salida);

function consultarUno($db, $sql, $params) {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $fila = $stmt->fetch(PDO::FETCH_ASSOC);
    return $fila ?: null;
}

function consultarTodos($db, $sql, $params) {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function sumar($filas, $campo) {
    $total = 0;
    foreach ($filas as $fila) {
        $total += floatval(isset($fila[$campo]) ? $fila[$campo] : 0);
    }
    return round($total, 6);
}

function esperado($partidas, $total, $pagado, $saldo) {
    return array(
        "partidas" => intval($partidas),
        "total" => $total,
        "pagado_total" => $pagado,
        "saldo_total" => $saldo
    );
}

function hallazgos($venta, $detalles, $reservas, $pagos, $trazas, $resumen, $esperarPartidas, $esperarTotal, $esperarPagado, $esperarSaldo) {
    $hallazgos = array();
    if (!in_array($venta["tipo_documento"], array("pedido", "apartado"), true)) {
        $hallazgos[] = "El folio no corresponde a pedido/apartado.";
    }
    if ($esperarPartidas > 0 && count($detalles) !== $esperarPartidas) {
        $hallazgos[] = "La UAT esperaba " . intval($esperarPartidas) . " partida(s).";
    }
    if (round(floatval($venta["total"]), 6) !== round(floatval($resumen["total_detalle"]), 6)) {
        $hallazgos[] = "El total de venta no coincide con detalles.";
    }
    if (round(floatval($venta["pagado_total"]), 6) !== round(floatval($resumen["pagos_total"]), 6)) {
        $hallazgos[] = "Pagado total no coincide con pagos registrados.";
    }
    if (round(floatval($venta["saldo_total"]), 6) !== round(floatval($venta["total"]) - floatval($venta["pagado_total"]), 6)) {
        $hallazgos[] = "Saldo total no cuadra con total-pagado.";
    }
    if ($esperarTotal !== null && round(floatval($venta["total"]), 6) !== round(floatval($esperarTotal), 6)) {
        $hallazgos[] = "Total no coincide con esperado.";
    }
    if ($esperarPagado !== null && round(floatval($venta["pagado_total"]), 6) !== round(floatval($esperarPagado), 6)) {
        $hallazgos[] = "Pagado no coincide con esperado.";
    }
    if ($esperarSaldo !== null && round(floatval($venta["saldo_total"]), 6) !== round(floatval($esperarSaldo), 6)) {
        $hallazgos[] = "Saldo no coincide con esperado.";
    }
    if (in_array($venta["estatus"], array("reservado", "pendiente_pago", "pagado"), true) && count($reservas) < count($detalles)) {
        $hallazgos[] = "Hay menos reservas que partidas.";
    }
    if ($venta["estatus"] === "entregado" && count($trazas) < count($detalles)) {
        $hallazgos[] = "Entrega sin trazas/kardex suficientes.";
    }
    return $hallazgos;
}

function contratoReadOnly() {
    return array(
        "no_escribe_bd" => true,
        "no_crea_pedido" => true,
        "no_registra_pago" => true,
        "no_mueve_caja" => true,
        "no_reserva_inventario" => true,
        "no_mueve_kardex" => true
    );
}

function responder($payload) {
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}
