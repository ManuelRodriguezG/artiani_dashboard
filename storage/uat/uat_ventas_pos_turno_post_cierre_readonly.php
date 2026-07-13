<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-06-27.
 * Proposito: inspeccionar post-cierre de turno POS UAT sin escribir BD.
 * Impacto: consulta turno, ventas, pagos y movimientos de caja para evidencia de corte.
 * Contrato: read-only; requiere --id_turno_caja=ID o --folio=FOLIO.
 */

$args = isset($argv) ? $argv : array();
$idTurno = 0;
$folio = "";
$compacto = false;

foreach ($args as $arg) {
    if (strpos($arg, "--id_turno_caja=") === 0) {
        $idTurno = intval(trim(substr($arg, 16), "\"' "));
    } elseif (strpos($arg, "--folio=") === 0) {
        $folio = trim(substr($arg, 8), "\"' ");
    } elseif ($arg === "--compact=1" || $arg === "--compacto=1") {
        $compacto = true;
    }
}

if ($idTurno <= 0 && $folio === "") {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "Indica --id_turno_caja=ID o --folio=FOLIO."
    ));
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";

class UatVentasPosTurnoPostCierreDb extends VentasErp {
    public function db() {
        return $this->getConexion();
    }
}

$ventas = new UatVentasPosTurnoPostCierreDb();
$db = $ventas->db();

$where = $idTurno > 0 ? "id_turno_caja=:valor" : "folio=:valor";
$valor = $idTurno > 0 ? $idTurno : $folio;

$stmt = $db->prepare("SELECT id_turno_caja, folio, id_almacen, id_caja, id_usuario_apertura, id_usuario_cierre,
        monto_inicial, monto_esperado, monto_contado, diferencia, estatus,
        fecha_apertura, fecha_cierre, observaciones_apertura, observaciones_cierre
    FROM erp_pos_turnos
    WHERE {$where}
    LIMIT 1");
$stmt->execute(array(":valor" => $valor));
$turno = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$turno) {
    responder(array(
        "ok" => false,
        "modo" => "no_encontrado",
        "mensaje" => "No se encontro el turno solicitado."
    ));
}

$idTurno = intval($turno["id_turno_caja"]);
$idCaja = intval($turno["id_caja"]);

$ventasTurno = consultarTodos($db, "SELECT id_venta, folio, estatus, total, pagado_total, saldo_total, id_cliente,
        fecha_venta
    FROM erp_ventas
    WHERE id_turno_caja=:turno
      AND estatus NOT IN ('cancelada','cancelado')
    ORDER BY id_venta ASC", array(":turno" => $idTurno));

$pagos = consultarTodos($db, "SELECT p.id_venta_pago, p.id_venta, v.folio, p.metodo_pago, p.monto,
        p.referencia, p.fecha_pago
    FROM erp_ventas_pagos p
    INNER JOIN erp_ventas v ON v.id_venta=p.id_venta
    WHERE v.id_turno_caja=:turno
      AND p.estatus='registrado'
      AND v.estatus NOT IN ('cancelada','cancelado')
    ORDER BY p.id_venta_pago ASC", array(":turno" => $idTurno));

$movimientosCaja = consultarTodos($db, "SELECT mc.id_movimiento_caja, mc.tipo, mc.motivo, mc.monto,
        mc.referencia, mc.creado_por, mc.fecha_registro, mc.estatus, mc.categoria,
        mc.id_venta, v.folio folio_venta
    FROM erp_pos_movimientos_caja mc
    LEFT JOIN erp_ventas v ON v.id_venta=mc.id_venta
    WHERE mc.id_turno_caja=:turno
      AND mc.estatus IN ('registrado','aprobado')
      AND (mc.categoria<>'venta_pos' OR v.id_venta IS NOT NULL)
    ORDER BY mc.id_movimiento_caja ASC", array(":turno" => $idTurno));

$movimientosCajaExcluidos = consultarTodos($db, "SELECT mc.id_movimiento_caja, mc.tipo, mc.motivo, mc.monto,
        mc.referencia, mc.creado_por, mc.fecha_registro, mc.estatus, mc.categoria,
        mc.id_venta, v.folio folio_venta
    FROM erp_pos_movimientos_caja mc
    LEFT JOIN erp_ventas v ON v.id_venta=mc.id_venta
    WHERE mc.id_turno_caja=:turno
      AND (mc.estatus NOT IN ('registrado','aprobado')
        OR (mc.categoria='venta_pos' AND v.id_venta IS NULL))
    ORDER BY mc.id_movimiento_caja ASC", array(":turno" => $idTurno));

$totales = array(
    "ventas" => sumar($ventasTurno, "total"),
    "pagos" => sumar($pagos, "monto"),
    "movimientos_caja" => sumarMovimientosCaja($movimientosCaja),
    "ventas_count" => count($ventasTurno),
    "pagos_count" => count($pagos),
    "movimientos_caja_count" => count($movimientosCaja)
);

$asignacion = $ventas->asignacionActualTerminalPos(array(
    "id_usuario" => intval($turno["id_usuario_apertura"])
));

$salida = array(
    "ok" => true,
    "modo" => "turno_post_cierre_readonly",
    "turno" => $turno,
    "totales" => $totales,
    "ventas" => $ventasTurno,
    "pagos" => $pagos,
    "movimientos_caja" => $movimientosCaja,
    "movimientos_caja_excluidos" => $movimientosCajaExcluidos,
    "asignacion_actual_usuario_apertura" => isset($asignacion["depurar"]) ? $asignacion["depurar"] : $asignacion,
    "hallazgos" => hallazgos($turno, $totales)
);

if ($compacto) {
    $salida = array(
        "ok" => true,
        "modo" => "turno_post_cierre_readonly",
        "read_only" => true,
        "turno" => array(
            "id_turno_caja" => intval($turno["id_turno_caja"]),
            "folio" => $turno["folio"],
            "estatus" => $turno["estatus"],
            "id_almacen" => intval($turno["id_almacen"]),
            "id_caja" => intval($turno["id_caja"]),
            "monto_inicial" => floatval($turno["monto_inicial"]),
            "monto_esperado" => floatval($turno["monto_esperado"]),
            "monto_contado" => floatval($turno["monto_contado"]),
            "diferencia" => floatval($turno["diferencia"]),
            "fecha_cierre" => $turno["fecha_cierre"]
        ),
        "totales" => $totales,
        "hallazgos" => hallazgos($turno, $totales),
        "contrato" => array(
            "no_escribe_bd" => true,
            "no_modifica_turno" => true,
            "no_modifica_caja" => true,
            "no_mueve_dinero" => true
        )
    );
}

responder($salida);

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

function sumarMovimientosCaja($filas) {
    $total = 0;
    foreach ($filas as $fila) {
        $monto = floatval(isset($fila["monto"]) ? $fila["monto"] : 0);
        $tipo = isset($fila["tipo"]) ? $fila["tipo"] : "";
        $resta = in_array($tipo, array("salida", "gasto", "retiro", "vale", "reembolso"));
        $total += $resta ? -$monto : $monto;
    }
    return round($total, 6);
}

function hallazgos($turno, $totales) {
    $hallazgos = array();
    if ($turno["estatus"] !== "cerrado") {
        $hallazgos[] = "El turno no esta cerrado.";
    }
    if ($turno["fecha_cierre"] === null || $turno["fecha_cierre"] === "") {
        $hallazgos[] = "El turno no tiene fecha de cierre.";
    }
    if (round(floatval($turno["diferencia"]), 6) !== 0.0) {
        $hallazgos[] = "El cierre tiene diferencia distinta de cero.";
    }
    if (round(floatval($turno["monto_esperado"]), 6) !== round(floatval($turno["monto_contado"]), 6)) {
        $hallazgos[] = "Monto esperado y contado no coinciden.";
    }
    return $hallazgos;
}

function responder($datos) {
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}
