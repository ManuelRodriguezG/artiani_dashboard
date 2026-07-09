<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-06.
 * Proposito: auditar decision financiera pendiente de pedido/apartado POS cancelado.
 * Impacto: consulta venta, pagos, reservas y eventos sin modificar BD.
 * Contrato: read-only; no reembolsa, no genera saldo a favor, no penaliza y no mueve caja.
 */

$args = isset($argv) ? $argv : array();
$folio = "";
$compacto = false;

foreach ($args as $arg) {
    if (strpos($arg, "--folio=") === 0) {
        $folio = trim(substr($arg, 8), "\"' ");
    } elseif ($arg === "--compact=1" || $arg === "--compacto=1") {
        $compacto = true;
    }
}

if ($folio === "") {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "Indica --folio=APT/PED cancelado.",
        "contrato" => contratoReadOnly()
    ));
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";

class UatVentasPosPedidoCanceladoFinanzasDb extends VentasErp {
    public function db() {
        return $this->getConexion();
    }
}

$ventas = new UatVentasPosPedidoCanceladoFinanzasDb();
$db = $ventas->db();
if (!$db) {
    responder(array(
        "ok" => false,
        "modo" => "pedido_cancelado_finanzas_readonly",
        "read_only" => true,
        "folio" => $folio,
        "hallazgos" => array("Conexion BD no disponible"),
        "contrato" => contratoReadOnly()
    ));
}

$venta = consultarUno($db, "SELECT id_venta, folio, tipo_documento, estatus, id_almacen, id_caja,
        id_turno_caja, cliente_nombre_publico, cliente_identificador_publico,
        total, pagado_total, saldo_total, cancelado_por, motivo_cancelacion, fecha_cancelacion
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
$pagos = consultarTodos($db, "SELECT id_venta_pago, id_movimiento_caja, metodo_pago, tipo_pago,
        monto, referencia, estatus, fecha_pago
    FROM erp_ventas_pagos
    WHERE id_venta=:venta
    ORDER BY id_venta_pago ASC", array(":venta" => $idVenta));

$reservas = consultarTodos($db, "SELECT id_reserva_inventario, folio, cantidad_reservada,
        cantidad_consumida, cantidad_liberada, estatus
    FROM erp_inventario_reservas
    WHERE origen_tipo='pedido_pos' AND origen_id=:venta
    ORDER BY id_reserva_inventario ASC", array(":venta" => $idVenta));

$eventos = consultarTodos($db, "SELECT id_venta_evento, tipo_evento, estatus_anterior,
        estatus_nuevo, monto, referencia, datos_snapshot, fecha_registro
    FROM erp_ventas_eventos
    WHERE id_venta=:venta
    ORDER BY id_venta_evento ASC", array(":venta" => $idVenta));

$decisiones = tablaExiste($db, "erp_ventas_pedidos_decisiones_financieras")
    ? consultarTodos($db, "SELECT id_decision_financiera, folio, decision, monto_base,
            monto_saldo_favor, monto_reembolso, monto_penalizacion, estatus,
            id_movimiento_caja, id_saldo_cliente_movimiento, fecha_solicitud
        FROM erp_ventas_pedidos_decisiones_financieras
        WHERE id_venta=:venta
        ORDER BY id_decision_financiera ASC", array(":venta" => $idVenta))
    : array();

$pagado = sumar($pagos, "monto");
$reservado = sumar($reservas, "cantidad_reservada");
$consumido = sumar($reservas, "cantidad_consumida");
$liberado = sumar($reservas, "cantidad_liberada");
$hallazgos = hallazgos($venta, $pagos, $reservas, $eventos, $pagado, $reservado, $consumido, $liberado);
$opciones = opcionesFinancieras($pagado);

$salida = array(
    "ok" => empty($hallazgos),
    "modo" => "pedido_cancelado_finanzas_readonly",
    "read_only" => true,
    "venta" => $venta,
    "resumen" => array(
        "pagos" => count($pagos),
        "pagado_total_pagos" => $pagado,
        "reserva_cantidad" => $reservado,
        "reserva_consumida" => $consumido,
        "reserva_liberada" => $liberado,
        "eventos" => count($eventos),
        "decisiones_financieras" => count($decisiones),
        "monto_pendiente_decision" => $venta["estatus"] === "cancelado" && empty($decisiones) ? $pagado : 0,
        "monto_con_decision" => sumar($decisiones, "monto_base")
    ),
    "opciones_financieras" => $opciones,
    "pagos" => $pagos,
    "reservas" => $reservas,
    "eventos" => $eventos,
    "decisiones_financieras" => $decisiones,
    "hallazgos" => $hallazgos,
    "contrato" => contratoReadOnly()
);

if ($compacto) {
    $salida = array(
        "ok" => empty($hallazgos),
        "modo" => "pedido_cancelado_finanzas_readonly",
        "read_only" => true,
        "folio" => $venta["folio"],
        "estatus" => $venta["estatus"],
        "total" => floatval($venta["total"]),
        "pagado_total" => floatval($venta["pagado_total"]),
        "saldo_total" => floatval($venta["saldo_total"]),
        "resumen" => $salida["resumen"],
        "decision_financiera" => empty($decisiones) ? null : $decisiones[count($decisiones) - 1],
        "opciones_financieras" => $opciones,
        "hallazgos" => $hallazgos,
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

function tablaExiste($db, $tabla) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=:tabla");
    $stmt->execute(array(":tabla" => $tabla));
    return intval($stmt->fetchColumn()) > 0;
}

function sumar($filas, $campo) {
    $total = 0;
    foreach ($filas as $fila) {
        $total += floatval(isset($fila[$campo]) ? $fila[$campo] : 0);
    }
    return round($total, 6);
}

function hallazgos($venta, $pagos, $reservas, $eventos, $pagado, $reservado, $consumido, $liberado) {
    $hallazgos = array();
    if (!in_array($venta["tipo_documento"], array("pedido", "apartado"), true)) {
        $hallazgos[] = "El folio no es pedido/apartado.";
    }
    if ($venta["estatus"] !== "cancelado") {
        $hallazgos[] = "El pedido/apartado no esta cancelado.";
    }
    if ($pagado <= 0) {
        $hallazgos[] = "No hay pagos que requieran decision financiera.";
    }
    if ($consumido > 0) {
        $hallazgos[] = "Hay reserva consumida; no corresponde a cancelacion antes de entrega.";
    }
    if ($reservado > 0 && $liberado + 0.0001 < $reservado) {
        $hallazgos[] = "La reserva no esta completamente liberada.";
    }
    $eventoCancelado = false;
    foreach ($eventos as $evento) {
        if ($evento["tipo_evento"] === "pedido_cancelado") {
            $eventoCancelado = true;
            if (strpos((string) $evento["datos_snapshot"], "decision_financiera_pendiente") === false) {
                $hallazgos[] = "Evento de cancelacion sin bandera decision_financiera_pendiente.";
            }
            break;
        }
    }
    if (!$eventoCancelado) {
        $hallazgos[] = "No se encontro evento pedido_cancelado.";
    }
    return $hallazgos;
}

function opcionesFinancieras($monto) {
    return array(
        "monto_base" => round($monto, 6),
        "saldo_favor" => array(
            "recomendado_si" => "cliente seguira comprando o se migrara a CRM/saldo cliente",
            "requiere" => "modulo CRM/saldos o ledger formal de cliente"
        ),
        "reembolso_caja" => array(
            "recomendado_si" => "cliente solicita efectivo y supervisor autoriza",
            "requiere" => "turno abierto, movimiento de caja reembolso_cliente y evidencia"
        ),
        "penalizacion" => array(
            "recomendado_si" => "politica de apartado permite retener parte del anticipo",
            "requiere" => "politica autorizada, motivo y registro contable/gerencial"
        ),
        "sin_reembolso" => array(
            "recomendado_si" => "monto cero o cancelacion sin pago previo",
            "requiere" => "no aplica para este folio si monto_base > 0"
        )
    );
}

function contratoReadOnly() {
    return array(
        "no_escribe_bd" => true,
        "no_reembolsa" => true,
        "no_genera_saldo_favor" => true,
        "no_penaliza" => true,
        "no_mueve_caja" => true,
        "no_mueve_inventario" => true
    );
}

function responder($datos) {
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}
