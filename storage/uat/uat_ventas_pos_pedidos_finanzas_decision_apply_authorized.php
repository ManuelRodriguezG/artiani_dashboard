<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-06.
 * Proposito: registrar expediente financiero de pedido/apartado POS cancelado.
 * Impacto: inserta decision en `erp_ventas_pedidos_decisiones_financieras` y evento de venta.
 * Contrato: bloqueado por token; no mueve caja, no crea saldo CRM real, no penaliza y no mueve inventario.
 */

$args = isset($argv) ? $argv : array();
$autorizar = "";
$respaldo = "";
$idUsuario = 1;
$folio = "";
$decision = "saldo_favor";
$monto = null;
$motivo = "UAT decision financiera apartado cancelado";

foreach ($args as $arg) {
    if (strpos($arg, "--autorizar=") === 0) {
        $autorizar = trim(substr($arg, 12), "\"' ");
    } elseif (strpos($arg, "--respaldo=") === 0) {
        $respaldo = trim(substr($arg, 11), "\"' ");
    } elseif (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--folio=") === 0) {
        $folio = trim(substr($arg, 8), "\"' ");
    } elseif (strpos($arg, "--decision=") === 0) {
        $decision = trim(substr($arg, 11), "\"' ");
    } elseif (strpos($arg, "--monto=") === 0) {
        $monto = floatval(trim(substr($arg, 8), "\"' "));
    } elseif (strpos($arg, "--motivo=") === 0) {
        $motivo = trim(substr($arg, 9), "\"' ");
    }
}

$validacionRespaldo = validarRespaldo($respaldo);
if ($autorizar !== "VENTAS_POS_PEDIDOS_FINANZAS_REAL" || !$validacionRespaldo["ok"] || $idUsuario <= 0 || $folio === "" || $monto === null) {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "No se registro decision financiera. Falta token, respaldo, usuario, folio o monto.",
        "validacion_respaldo" => $validacionRespaldo,
        "contrato" => contrato(false)
    ));
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";

class UatVentasPosPedidosFinanzasDecisionApplyDb extends CRUD {
    public function db() {
        return $this->getConexion();
    }
}

$db = (new UatVentasPosPedidosFinanzasDecisionApplyDb())->db();
if (!$db) {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "Conexion BD no disponible.",
        "contrato" => contrato(false)
    ));
}

if (!tablaExiste($db, "erp_ventas_pedidos_decisiones_financieras")) {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "Falta tabla erp_ventas_pedidos_decisiones_financieras.",
        "contrato" => contrato(false)
    ));
}

try {
    $db->beginTransaction();
    $venta = consultarUno($db, "SELECT * FROM erp_ventas WHERE folio=:folio LIMIT 1 FOR UPDATE", array(":folio" => $folio));
    if (!$venta) {
        throw new Exception("Pedido/apartado no encontrado");
    }
    $pagos = consultarTodos($db, "SELECT * FROM erp_ventas_pagos WHERE id_venta=:venta AND estatus='registrado' ORDER BY id_venta_pago ASC", array(":venta" => intval($venta["id_venta"])));
    $reservas = consultarTodos($db, "SELECT * FROM erp_inventario_reservas WHERE origen_tipo='pedido_pos' AND origen_id=:venta ORDER BY id_reserva_inventario ASC", array(":venta" => intval($venta["id_venta"])));
    $existente = consultarUno($db, "SELECT * FROM erp_ventas_pedidos_decisiones_financieras WHERE id_venta=:venta AND estatus<>'cancelada' LIMIT 1 FOR UPDATE", array(":venta" => intval($venta["id_venta"])));
    $bloqueos = validarDecision($venta, $pagos, $reservas, $existente, $decision, $monto, $motivo);
    if (!empty($bloqueos)) {
        throw new Exception(implode("; ", $bloqueos));
    }

    $folioDecision = generarFolioDecision($db);
    $montoSaldoFavor = $decision === "saldo_favor" ? $monto : 0;
    $montoReembolso = $decision === "reembolso_caja" ? $monto : 0;
    $montoPenalizacion = $decision === "penalizacion" ? $monto : 0;
    $snapshot = array(
        "venta" => resumenVenta($venta),
        "pagos" => array_map("resumirPago", $pagos),
        "reservas" => array_map("resumirReserva", $reservas),
        "nota" => "saldo_favor queda como expediente; no se crea ledger CRM real en esta UAT"
    );

    $stmt = $db->prepare("INSERT INTO erp_ventas_pedidos_decisiones_financieras
        (folio, id_venta, folio_venta, tipo_documento, id_cliente_crm, cliente_snapshot,
         decision, monto_base, monto_saldo_favor, monto_reembolso, monto_penalizacion,
         id_turno_caja, id_caja, id_almacen, estatus, motivo, datos_snapshot,
         solicitado_por, autorizado_por, aplicado_por, fecha_autorizacion, fecha_aplicacion)
        VALUES
        (:folio, :venta, :folio_venta, :tipo_documento, :cliente_crm, :cliente_snapshot,
         :decision, :monto_base, :saldo_favor, :reembolso, :penalizacion,
         NULL, :caja, :almacen, :estatus, :motivo, :snapshot,
         :usuario, :usuario, NULL, NOW(), NULL)");
    $stmt->execute(array(
        ":folio" => $folioDecision,
        ":venta" => intval($venta["id_venta"]),
        ":folio_venta" => $venta["folio"],
        ":tipo_documento" => $venta["tipo_documento"],
        ":cliente_crm" => isset($venta["id_cliente_crm"]) && intval($venta["id_cliente_crm"]) > 0 ? intval($venta["id_cliente_crm"]) : null,
        ":cliente_snapshot" => json_encode(array(
            "cliente_nombre_publico" => isset($venta["cliente_nombre_publico"]) ? $venta["cliente_nombre_publico"] : null,
            "cliente_identificador_publico" => isset($venta["cliente_identificador_publico"]) ? $venta["cliente_identificador_publico"] : null,
            "cliente_snapshot" => isset($venta["cliente_snapshot"]) ? $venta["cliente_snapshot"] : null
        ), JSON_UNESCAPED_UNICODE),
        ":decision" => $decision,
        ":monto_base" => $monto,
        ":saldo_favor" => $montoSaldoFavor,
        ":reembolso" => $montoReembolso,
        ":penalizacion" => $montoPenalizacion,
        ":caja" => intval($venta["id_caja"]),
        ":almacen" => intval($venta["id_almacen"]),
        ":estatus" => $decision === "saldo_favor" ? "pendiente_ledger_crm" : "pendiente",
        ":motivo" => $motivo,
        ":snapshot" => json_encode($snapshot, JSON_UNESCAPED_UNICODE),
        ":usuario" => $idUsuario
    ));
    $idDecision = intval($db->lastInsertId());

    registrarEvento($db, intval($venta["id_venta"]), $venta["folio"], $decision, $monto, $folioDecision, $snapshot, $idUsuario);

    $db->commit();
    responder(array(
        "ok" => true,
        "modo" => "pedidos_finanzas_decision_real_authorized",
        "respaldo_ref" => $respaldo,
        "folio_venta" => $venta["folio"],
        "id_decision_financiera" => $idDecision,
        "folio_decision" => $folioDecision,
        "decision" => $decision,
        "monto_base" => $monto,
        "monto_saldo_favor" => $montoSaldoFavor,
        "estatus" => $decision === "saldo_favor" ? "pendiente_ledger_crm" : "pendiente",
        "contrato" => contrato(true)
    ));
} catch (Exception $e) {
    if ($db && $db->inTransaction()) {
        $db->rollBack();
    }
    responder(array(
        "ok" => false,
        "modo" => "pedidos_finanzas_decision_real_authorized",
        "mensaje" => $e->getMessage(),
        "rollback" => true,
        "contrato" => contrato(false)
    ));
}

function validarDecision($venta, $pagos, $reservas, $existente, $decision, $monto, $motivo) {
    $bloqueos = array();
    $pagado = sumar($pagos, "monto");
    if (!in_array($venta["tipo_documento"], array("pedido", "apartado"), true)) {
        $bloqueos[] = "El folio no es pedido/apartado";
    }
    if ($venta["estatus"] !== "cancelado") {
        $bloqueos[] = "El pedido/apartado debe estar cancelado";
    }
    if ($existente) {
        $bloqueos[] = "Ya existe decision financiera activa para este folio";
    }
    if (!in_array($decision, array("saldo_favor", "reembolso_caja", "penalizacion", "sin_reembolso"), true)) {
        $bloqueos[] = "Decision financiera invalida";
    }
    if ($monto < 0 || $monto - $pagado > 0.0001) {
        $bloqueos[] = "Monto invalido o mayor al pagado";
    }
    if ($pagado <= 0.0001 && $decision !== "sin_reembolso") {
        $bloqueos[] = "No hay pagos registrados para resolver";
    }
    foreach ($reservas as $reserva) {
        if (floatval($reserva["cantidad_consumida"]) > 0) {
            $bloqueos[] = "Hay reserva consumida; corresponde a reversa/devolucion";
            break;
        }
    }
    if ($decision === "saldo_favor" && intval(isset($venta["id_cliente_crm"]) ? $venta["id_cliente_crm"] : 0) <= 0 && trim((string) (isset($venta["cliente_identificador_publico"]) ? $venta["cliente_identificador_publico"] : "")) === "") {
        $bloqueos[] = "Saldo a favor requiere cliente CRM o identificador estable";
    }
    if ($decision === "reembolso_caja") {
        $bloqueos[] = "Reembolso de caja no se aplica con este script; requiere flujo de caja/evidencia";
    }
    if ($decision === "penalizacion" && ($monto <= 0.0001 || trim($motivo) === "")) {
        $bloqueos[] = "Penalizacion requiere monto y motivo";
    }
    if ($decision === "sin_reembolso" && $pagado > 0.0001) {
        $bloqueos[] = "Sin reembolso no aplica si hay pago registrado";
    }
    return array_values(array_unique($bloqueos));
}

function generarFolioDecision($db) {
    $prefijo = "PFIN-" . date("Ymd") . "-";
    $stmt = $db->prepare("SELECT COUNT(*) FROM erp_ventas_pedidos_decisiones_financieras WHERE folio LIKE :folio");
    $stmt->execute(array(":folio" => $prefijo . "%"));
    return $prefijo . str_pad(strval(intval($stmt->fetchColumn()) + 1), 6, "0", STR_PAD_LEFT);
}

function registrarEvento($db, $idVenta, $folioVenta, $decision, $monto, $folioDecision, $snapshot, $idUsuario) {
    if (!tablaExiste($db, "erp_ventas_eventos")) {
        return;
    }
    $stmt = $db->prepare("INSERT INTO erp_ventas_eventos
        (id_venta, folio, tipo_evento, estatus_anterior, estatus_nuevo, monto, referencia, datos_snapshot, observaciones, creado_por)
        VALUES (:venta, :folio, 'pedido_decision_financiera_registrada', 'cancelado', 'cancelado', :monto, :referencia, :snapshot, :observaciones, :usuario)");
    $stmt->execute(array(
        ":venta" => $idVenta,
        ":folio" => $folioVenta,
        ":monto" => $monto,
        ":referencia" => $folioDecision,
        ":snapshot" => json_encode($snapshot, JSON_UNESCAPED_UNICODE),
        ":observaciones" => "decision_financiera=" . $decision,
        ":usuario" => $idUsuario
    ));
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

function resumenVenta($venta) {
    return array(
        "id_venta" => intval($venta["id_venta"]),
        "folio" => $venta["folio"],
        "estatus" => $venta["estatus"],
        "total" => floatval($venta["total"]),
        "pagado_total" => floatval($venta["pagado_total"]),
        "saldo_total" => floatval($venta["saldo_total"])
    );
}

function resumirPago($pago) {
    return array(
        "id_venta_pago" => intval($pago["id_venta_pago"]),
        "tipo_pago" => $pago["tipo_pago"],
        "monto" => floatval($pago["monto"]),
        "referencia" => $pago["referencia"]
    );
}

function resumirReserva($reserva) {
    return array(
        "id_reserva_inventario" => intval($reserva["id_reserva_inventario"]),
        "cantidad_reservada" => floatval($reserva["cantidad_reservada"]),
        "cantidad_consumida" => floatval($reserva["cantidad_consumida"]),
        "cantidad_liberada" => floatval($reserva["cantidad_liberada"]),
        "estatus" => $reserva["estatus"]
    );
}

function validarRespaldo($respaldo) {
    $esRutaLocal = preg_match('/^[A-Za-z]:[\\\\\\/]/', $respaldo) === 1 || strpos($respaldo, "\\") !== false || strpos($respaldo, "/") !== false;
    $existe = false;
    $legible = false;
    $tamano = null;
    if ($respaldo !== "" && $esRutaLocal) {
        $existe = file_exists($respaldo);
        $legible = $existe && is_readable($respaldo);
        $tamano = $existe ? filesize($respaldo) : null;
    }
    $okReferencia = strlen($respaldo) >= 8;
    $okRuta = !$esRutaLocal || ($existe && $legible && $tamano !== null && $tamano > 0);
    return array(
        "ok" => $okReferencia && $okRuta,
        "referencia_presente" => $okReferencia,
        "parece_ruta_local" => $esRutaLocal,
        "archivo_existe" => $esRutaLocal ? $existe : null,
        "archivo_legible" => $esRutaLocal ? $legible : null,
        "tamano_bytes" => $tamano
    );
}

function contrato($escribio) {
    return array(
        "registro_expediente" => $escribio,
        "no_mueve_caja" => true,
        "no_crea_saldo_crm_real" => true,
        "no_penaliza" => true,
        "no_mueve_inventario" => true
    );
}

function responder($datos) {
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}
