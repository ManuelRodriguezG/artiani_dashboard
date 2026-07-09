<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-06-30.
 * Proposito: validar post-ejecucion de una reversa POS real sin escribir BD.
 * Impacto: revisa devolucion, venta, detalle, caja e inventario para evidencia UAT.
 * Contrato: read-only; no crea, actualiza ni elimina registros.
 */

$args = isset($argv) ? $argv : array();
$folioDevolucion = "";
$folioVenta = "";
$idDevolucion = 0;
$idVenta = 0;

foreach ($args as $arg) {
    if (strpos($arg, "--folio_devolucion=") === 0) {
        $folioDevolucion = trim(substr($arg, 19), "\"' ");
    } elseif (strpos($arg, "--id_devolucion=") === 0) {
        $idDevolucion = intval(trim(substr($arg, 16), "\"' "));
    } elseif (strpos($arg, "--folio_venta=") === 0) {
        $folioVenta = trim(substr($arg, 14), "\"' ");
    } elseif (strpos($arg, "--id_venta=") === 0) {
        $idVenta = intval(trim(substr($arg, 11), "\"' "));
    }
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";

class VentasPosReversaPostDb extends VentasErp {
    public function db() {
        return $this->getConexion();
    }
}

$ventas = new VentasPosReversaPostDb();
$db = $ventas->db();
$hallazgos = array();

if (!$db) {
    responder(array(
        "ok" => false,
        "modo" => "ventas_pos_reversa_post_readonly",
        "read_only" => true,
        "hallazgos" => array("No hay conexion a BD")
    ));
}

$devolucion = buscarDevolucion($db, $idDevolucion, $folioDevolucion, $idVenta, $folioVenta);
if (!$devolucion) {
    $hallazgos[] = "No se encontro devolucion/cancelacion POS con los filtros indicados";
    responder(array(
        "ok" => false,
        "modo" => "ventas_pos_reversa_post_readonly",
        "read_only" => true,
        "filtros" => compact("folioDevolucion", "folioVenta", "idDevolucion", "idVenta"),
        "hallazgos" => $hallazgos
    ));
}

$idDevolucion = intval($devolucion["id_devolucion"]);
$idVenta = intval($devolucion["id_venta"]);
$venta = buscarVenta($db, $idVenta);
$detalles = buscarDetalles($db, $idDevolucion);
$pagos = buscarPagos($db, $idVenta, $devolucion);
$movimientoCaja = buscarMovimientoCaja($db, $devolucion);
$movimientosInventario = buscarMovimientosInventario($db, $detalles);
$componentesFinancieros = buscarComponentesFinancieros($db, $idDevolucion);
$movimientosSaldoCrm = buscarMovimientosSaldoCrm($db, $componentesFinancieros);

if (!$venta) {
    $hallazgos[] = "La venta original ligada a la devolucion no existe";
}
if (empty($detalles)) {
    $hallazgos[] = "La devolucion no tiene detalle";
}
if ($devolucion["decision_financiera"] === "reembolso_caja" && !$movimientoCaja) {
    $hallazgos[] = "La devolucion requiere reembolso de caja pero no tiene movimiento de caja ligado";
}
if ($devolucion["decision_financiera"] === "saldo_favor" && floatval($devolucion["monto_saldo_favor"]) <= 0) {
    $hallazgos[] = "La devolucion a saldo favor no tiene monto_saldo_favor";
}
if (in_array($devolucion["decision_financiera"], array("mixta_saldo_crm", "reintegro_saldo_crm"), true)) {
    if (empty($componentesFinancieros)) {
        $hallazgos[] = "La devolucion saldo CRM no tiene componentes financieros";
    }
    if (floatval($devolucion["monto_reintegro_saldo_crm"]) > 0 && empty($movimientosSaldoCrm)) {
        $hallazgos[] = "La devolucion reintegra saldo CRM pero no tiene movimiento CRM ligado";
    }
}
foreach ($detalles as $detalle) {
    if ($detalle["decision_inventario"] === "reintegrar" && intval($detalle["id_movimiento_inventario_devolucion"]) <= 0) {
        $hallazgos[] = "Detalle " . $detalle["id_devolucion_detalle"] . " reintegra inventario pero no tiene kardex de entrada";
    }
    if ($detalle["decision_inventario"] !== "reintegrar" && intval($detalle["id_movimiento_inventario_devolucion"]) > 0) {
        $hallazgos[] = "Detalle " . $detalle["id_devolucion_detalle"] . " no reintegra inventario pero tiene kardex de entrada";
    }
}

responder(array(
    "ok" => empty($hallazgos),
    "modo" => "ventas_pos_reversa_post_readonly",
    "read_only" => true,
    "devolucion" => resumir($devolucion),
    "venta" => $venta ? resumir($venta) : null,
    "detalles" => array_map("resumir", $detalles),
    "pagos_reembolso" => array_map("resumir", $pagos),
    "movimiento_caja" => $movimientoCaja ? resumir($movimientoCaja) : null,
    "componentes_financieros" => array_map("resumir", $componentesFinancieros),
    "movimientos_saldo_crm" => array_map("resumir", $movimientosSaldoCrm),
    "movimientos_inventario_reversa" => array_map("resumir", $movimientosInventario),
    "hallazgos" => $hallazgos
));

function buscarDevolucion($db, $idDevolucion, $folioDevolucion, $idVenta, $folioVenta) {
    $where = array();
    $params = array();
    if ($idDevolucion > 0) {
        $where[] = "d.id_devolucion=:id_devolucion";
        $params[":id_devolucion"] = $idDevolucion;
    }
    if ($folioDevolucion !== "") {
        $where[] = "d.folio=:folio_devolucion";
        $params[":folio_devolucion"] = $folioDevolucion;
    }
    if ($idVenta > 0) {
        $where[] = "d.id_venta=:id_venta";
        $params[":id_venta"] = $idVenta;
    }
    if ($folioVenta !== "") {
        $where[] = "v.folio=:folio_venta";
        $params[":folio_venta"] = $folioVenta;
    }
    if (empty($where)) {
        return null;
    }
    $sql = "SELECT d.*, v.folio folio_venta, v.estatus estatus_venta
        FROM erp_ventas_devoluciones d
        LEFT JOIN erp_ventas v ON v.id_venta=d.id_venta
        WHERE " . implode(" AND ", $where) . "
        ORDER BY d.id_devolucion DESC
        LIMIT 1";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function buscarVenta($db, $idVenta) {
    $stmt = $db->prepare("SELECT id_venta, folio, estatus, id_almacen, id_caja, id_turno_caja,
            total, pagado_total, saldo_total, fecha_cancelacion, cancelado_por, motivo_cancelacion
        FROM erp_ventas
        WHERE id_venta=:venta");
    $stmt->execute(array(":venta" => intval($idVenta)));
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function buscarDetalles($db, $idDevolucion) {
    $stmt = $db->prepare("SELECT *
        FROM erp_ventas_devoluciones_detalle
        WHERE id_devolucion=:devolucion
        ORDER BY id_devolucion_detalle ASC");
    $stmt->execute(array(":devolucion" => intval($idDevolucion)));
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function buscarPagos($db, $idVenta, $devolucion) {
    if (empty($devolucion["folio"])) {
        return array();
    }
    $stmt = $db->prepare("SELECT *
        FROM erp_ventas_pagos
        WHERE id_venta=:venta AND referencia=:referencia
        ORDER BY id_venta_pago ASC");
    $stmt->execute(array(":venta" => intval($idVenta), ":referencia" => $devolucion["folio"]));
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function buscarMovimientoCaja($db, $devolucion) {
    if (intval($devolucion["id_movimiento_caja"]) <= 0) {
        return null;
    }
    $stmt = $db->prepare("SELECT *
        FROM erp_pos_movimientos_caja
        WHERE id_movimiento_caja=:movimiento");
    $stmt->execute(array(":movimiento" => intval($devolucion["id_movimiento_caja"])));
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function buscarMovimientosInventario($db, $detalles) {
    $ids = array();
    foreach ($detalles as $detalle) {
        $idMovimiento = intval($detalle["id_movimiento_inventario_devolucion"]);
        if ($idMovimiento > 0) {
            $ids[] = $idMovimiento;
        }
    }
    $ids = array_values(array_unique($ids));
    if (empty($ids)) {
        return array();
    }
    $placeholders = array();
    $params = array();
    foreach ($ids as $i => $id) {
        $key = ":mov" . $i;
        $placeholders[] = $key;
        $params[$key] = $id;
    }
    $stmt = $db->prepare("SELECT *
        FROM erp_inventario_movimientos
        WHERE id_movimiento_inventario IN (" . implode(",", $placeholders) . ")
        ORDER BY id_movimiento_inventario ASC");
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function buscarComponentesFinancieros($db, $idDevolucion) {
    if (!tablaExiste($db, "erp_ventas_devoluciones_finanzas")) {
        return array();
    }
    $stmt = $db->prepare("SELECT *
        FROM erp_ventas_devoluciones_finanzas
        WHERE id_devolucion=:devolucion
        ORDER BY id_devolucion_finanza ASC");
    $stmt->execute(array(":devolucion" => intval($idDevolucion)));
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function buscarMovimientosSaldoCrm($db, $componentes) {
    if (!tablaExiste($db, "crm_clientes_saldos_movimientos")) {
        return array();
    }
    $ids = array();
    foreach ($componentes as $componente) {
        $idMovimiento = intval($componente["id_cliente_saldo_movimiento"]);
        if ($idMovimiento > 0) {
            $ids[] = $idMovimiento;
        }
    }
    $ids = array_values(array_unique($ids));
    if (empty($ids)) {
        return array();
    }
    $placeholders = array();
    $params = array();
    foreach ($ids as $i => $id) {
        $key = ":movsaldo" . $i;
        $placeholders[] = $key;
        $params[$key] = $id;
    }
    $stmt = $db->prepare("SELECT *
        FROM crm_clientes_saldos_movimientos
        WHERE id_cliente_saldo_movimiento IN (" . implode(",", $placeholders) . ")
        ORDER BY id_cliente_saldo_movimiento ASC");
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function tablaExiste($db, $tabla) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=:tabla");
    $stmt->execute(array(":tabla" => $tabla));
    return intval($stmt->fetchColumn()) > 0;
}

function resumir($row) {
    if (!is_array($row)) {
        return $row;
    }
    unset($row["datos_snapshot"]);
    return $row;
}

function responder($datos) {
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}
