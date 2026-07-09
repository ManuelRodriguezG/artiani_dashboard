<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-06.
 * Proposito: simular decision financiera de pedido/apartado POS cancelado.
 * Impacto: valida folio, decision, monto, turno y duplicados sin insertar expediente ni mover caja.
 * Contrato: read-only; no crea decision, no reembolsa, no genera saldo a favor y no penaliza.
 */

$args = isset($argv) ? $argv : array();
$folio = "";
$decision = "saldo_favor";
$monto = null;
$idUsuario = 1;
$motivo = "UAT decision financiera apartado cancelado";
$compacto = false;

foreach ($args as $arg) {
    if (strpos($arg, "--folio=") === 0) {
        $folio = trim(substr($arg, 8), "\"' ");
    } elseif (strpos($arg, "--decision=") === 0) {
        $decision = trim(substr($arg, 11), "\"' ");
    } elseif (strpos($arg, "--monto=") === 0) {
        $monto = floatval(trim(substr($arg, 8), "\"' "));
    } elseif (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--motivo=") === 0) {
        $motivo = trim(substr($arg, 9), "\"' ");
    } elseif ($arg === "--compact=1" || $arg === "--compacto=1") {
        $compacto = true;
    }
}

if ($folio === "") {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "Indica --folio=APT/PED cancelado.",
        "contrato" => contrato()
    ));
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";

class UatVentasPosPedidosFinanzasDecisionDryrunDb extends VentasErp {
    public function db() {
        return $this->getConexion();
    }
}

$modelo = new UatVentasPosPedidosFinanzasDecisionDryrunDb();
$db = $modelo->db();
if (!$db) {
    responder(array(
        "ok" => false,
        "modo" => "pedidos_finanzas_decision_dryrun",
        "read_only" => true,
        "hallazgos" => array("Conexion BD no disponible"),
        "contrato" => contrato()
    ));
}

$venta = consultarUno($db, "SELECT id_venta, folio, tipo_documento, estatus, id_almacen, id_caja,
        id_turno_caja, id_cliente_crm, cliente_nombre_publico, cliente_identificador_publico,
        cliente_snapshot, total, pagado_total, saldo_total
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
        "contrato" => contrato()
    ));
}

$pagos = consultarTodos($db, "SELECT id_venta_pago, id_movimiento_caja, metodo_pago, tipo_pago,
        monto, referencia, estatus
    FROM erp_ventas_pagos
    WHERE id_venta=:venta AND estatus='registrado'
    ORDER BY id_venta_pago ASC", array(":venta" => intval($venta["id_venta"])));

$reservas = consultarTodos($db, "SELECT id_reserva_inventario, cantidad_reservada,
        cantidad_consumida, cantidad_liberada, estatus
    FROM erp_inventario_reservas
    WHERE origen_tipo='pedido_pos' AND origen_id=:venta
    ORDER BY id_reserva_inventario ASC", array(":venta" => intval($venta["id_venta"])));

$decisionExistente = tablaExiste($db, "erp_ventas_pedidos_decisiones_financieras")
    ? consultarUno($db, "SELECT * FROM erp_ventas_pedidos_decisiones_financieras WHERE id_venta=:venta AND estatus<>'cancelada' LIMIT 1", array(":venta" => intval($venta["id_venta"])))
    : null;

$asignacion = $modelo->asignacionActualTerminalPos(array("id_usuario" => $idUsuario));
$depurarAsignacion = isset($asignacion["depurar"]) && is_array($asignacion["depurar"]) ? $asignacion["depurar"] : array();
$turnoAbierto = isset($depurarAsignacion["turno_abierto"]) && is_array($depurarAsignacion["turno_abierto"]) ? $depurarAsignacion["turno_abierto"] : array();

$montoPagado = sumar($pagos, "monto");
$montoBase = $monto === null ? $montoPagado : round($monto, 6);
$bloqueos = validar($venta, $reservas, $decisionExistente, $decision, $montoBase, $montoPagado, $motivo, $turnoAbierto);
$propuesta = propuesta($venta, $decision, $montoBase, $turnoAbierto, $idUsuario, $motivo);

$salida = array(
    "ok" => empty($bloqueos),
    "modo" => "pedidos_finanzas_decision_dryrun",
    "read_only" => true,
    "folio" => $folio,
    "decision" => $decision,
    "monto_base" => $montoBase,
    "venta" => $venta,
    "resumen" => array(
        "pagos" => count($pagos),
        "pagado_total_pagos" => $montoPagado,
        "reserva_cantidad" => sumar($reservas, "cantidad_reservada"),
        "reserva_consumida" => sumar($reservas, "cantidad_consumida"),
        "reserva_liberada" => sumar($reservas, "cantidad_liberada"),
        "turno_abierto" => !empty($turnoAbierto),
        "decision_existente" => !empty($decisionExistente)
    ),
    "propuesta" => $propuesta,
    "bloqueos" => $bloqueos,
    "contrato" => contrato()
);

if ($compacto) {
    $salida = array(
        "ok" => empty($bloqueos),
        "modo" => "pedidos_finanzas_decision_dryrun",
        "read_only" => true,
        "folio" => $folio,
        "estatus" => $venta["estatus"],
        "decision" => $decision,
        "monto_base" => $montoBase,
        "resumen" => $salida["resumen"],
        "propuesta" => $propuesta,
        "bloqueos" => $bloqueos,
        "contrato" => contrato()
    );
}

responder($salida);

function validar($venta, $reservas, $decisionExistente, $decision, $montoBase, $montoPagado, $motivo, $turnoAbierto) {
    $bloqueos = array();
    if (!in_array($venta["tipo_documento"], array("pedido", "apartado"), true)) {
        $bloqueos[] = "El folio no es pedido/apartado.";
    }
    if ($venta["estatus"] !== "cancelado") {
        $bloqueos[] = "El pedido/apartado debe estar cancelado.";
    }
    if ($decisionExistente) {
        $bloqueos[] = "Ya existe decision financiera activa para este folio.";
    }
    if (!in_array($decision, array("saldo_favor", "reembolso_caja", "penalizacion", "sin_reembolso"), true)) {
        $bloqueos[] = "Decision financiera invalida.";
    }
    if ($montoPagado <= 0.0001 && $decision !== "sin_reembolso") {
        $bloqueos[] = "No hay pagos registrados para resolver.";
    }
    if ($montoBase < 0 || $montoBase - $montoPagado > 0.0001) {
        $bloqueos[] = "Monto a resolver invalido o mayor al pagado.";
    }
    foreach ($reservas as $reserva) {
        if (floatval($reserva["cantidad_consumida"]) > 0) {
            $bloqueos[] = "Hay reserva consumida; corresponde a reversa/devolucion, no a decision de cancelacion.";
            break;
        }
    }
    if ($decision === "saldo_favor" && intval($venta["id_cliente_crm"]) <= 0 && trim((string) $venta["cliente_identificador_publico"]) === "") {
        $bloqueos[] = "Saldo a favor requiere cliente CRM o identificador estable.";
    }
    if ($decision === "reembolso_caja" && empty($turnoAbierto)) {
        $bloqueos[] = "Reembolso de caja requiere turno abierto.";
    }
    if ($decision === "penalizacion" && ($montoBase <= 0.0001 || trim($motivo) === "")) {
        $bloqueos[] = "Penalizacion requiere monto y motivo.";
    }
    if ($decision === "sin_reembolso" && $montoPagado > 0.0001) {
        $bloqueos[] = "Sin reembolso no aplica si hay pago registrado.";
    }
    return array_values(array_unique($bloqueos));
}

function propuesta($venta, $decision, $montoBase, $turnoAbierto, $idUsuario, $motivo) {
    $montoSaldoFavor = $decision === "saldo_favor" ? $montoBase : 0;
    $montoReembolso = $decision === "reembolso_caja" ? $montoBase : 0;
    $montoPenalizacion = $decision === "penalizacion" ? $montoBase : 0;
    return array(
        "insertaria_decision" => true,
        "folio_decision_temporal" => "PFIN-DRY-" . date("Ymd-His"),
        "id_venta" => intval($venta["id_venta"]),
        "folio_venta" => $venta["folio"],
        "decision" => $decision,
        "monto_base" => $montoBase,
        "monto_saldo_favor" => $montoSaldoFavor,
        "monto_reembolso" => $montoReembolso,
        "monto_penalizacion" => $montoPenalizacion,
        "id_turno_caja" => !empty($turnoAbierto) ? intval($turnoAbierto["id_turno_caja"]) : null,
        "id_caja" => !empty($turnoAbierto) ? intval($turnoAbierto["id_caja"]) : intval($venta["id_caja"]),
        "id_almacen" => !empty($turnoAbierto) ? intval($turnoAbierto["id_almacen"]) : intval($venta["id_almacen"]),
        "estatus_inicial" => "pendiente",
        "solicitado_por" => $idUsuario,
        "motivo" => $motivo,
        "acciones_reales_posteriores" => accionesPosteriores($decision)
    );
}

function accionesPosteriores($decision) {
    if ($decision === "saldo_favor") {
        return array("registrar_saldo_cliente_crm_o_ledger", "registrar_evento_venta");
    }
    if ($decision === "reembolso_caja") {
        return array("registrar_movimiento_caja_reembolso_cliente", "registrar_pago_reembolso", "exigir_evidencia_si_aplica", "registrar_evento_venta");
    }
    if ($decision === "penalizacion") {
        return array("registrar_penalizacion_gerencial", "registrar_evento_venta");
    }
    return array("registrar_evento_venta");
}

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

function contrato() {
    return array(
        "no_escribe_bd" => true,
        "no_crea_decision" => true,
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
