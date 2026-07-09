<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-07.
 * Proposito: auditar una venta POS con saldo CRM despues de ejecutarla.
 * Impacto: verifica pagos, caja, ledger CRM, inventario y eventos sin escribir BD.
 * Contrato: read-only; no corrige diferencias ni crea movimientos.
 */

$args = isset($argv) ? $argv : array();
$folio = "";
$idVenta = 0;
$compacto = false;

foreach ($args as $arg) {
    if (strpos($arg, "--folio=") === 0) {
        $folio = trim(substr($arg, 8), "\"' ");
    } elseif (strpos($arg, "--id_venta=") === 0) {
        $idVenta = intval(trim(substr($arg, 11), "\"' "));
    } elseif ($arg === "--compact=1" || $arg === "--compacto=1") {
        $compacto = true;
    }
}

if ($folio === "" && $idVenta <= 0) {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "Indica --folio=POS-YYYYMMDD-###### o --id_venta=ID.",
        "read_only" => true
    ));
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";

class UatVentasPosSaldoCrmPostReadonlyDb extends CRUD {
    public function db() {
        return $this->getConexion();
    }
}

$db = (new UatVentasPosSaldoCrmPostReadonlyDb())->db();
if (!$db) {
    responder(array(
        "ok" => false,
        "modo" => "saldo_crm_post_readonly",
        "mensaje" => "Conexion BD no disponible.",
        "read_only" => true
    ));
}

$venta = consultarUno($db, "SELECT *
    FROM erp_ventas
    WHERE " . ($idVenta > 0 ? "id_venta=:ref" : "folio=:ref") . "
    LIMIT 1", array(":ref" => $idVenta > 0 ? $idVenta : $folio));

if (!$venta) {
    responder(array(
        "ok" => false,
        "modo" => "saldo_crm_post_readonly",
        "mensaje" => "Venta no encontrada.",
        "read_only" => true,
        "referencia" => $idVenta > 0 ? $idVenta : $folio
    ));
}

$pagos = consultarTodos($db, "SELECT *
    FROM erp_ventas_pagos
    WHERE id_venta=:venta AND estatus='registrado'
    ORDER BY id_venta_pago ASC", array(":venta" => intval($venta["id_venta"])));
$movimientosCaja = consultarTodos($db, "SELECT *
    FROM erp_pos_movimientos_caja
    WHERE referencia=:folio OR id_venta=:venta
    ORDER BY id_movimiento_caja ASC", array(":folio" => $venta["folio"], ":venta" => intval($venta["id_venta"])));
$movimientosSaldo = tablaExiste($db, "crm_clientes_saldos_movimientos")
    ? consultarTodos($db, "SELECT *
        FROM crm_clientes_saldos_movimientos
        WHERE origen_modulo='ventas_pos'
          AND origen_tipo='venta_pos_pago_saldo_crm'
          AND origen_id=:folio
        ORDER BY id_cliente_saldo_movimiento ASC", array(":folio" => $venta["folio"]))
    : array();
$detalleInventario = consultarTodos($db, "SELECT *
    FROM erp_ventas_detalle_inventario
    WHERE id_venta=:venta
    ORDER BY id_venta_detalle_inventario ASC", array(":venta" => intval($venta["id_venta"])));
$eventosVenta = tablaExiste($db, "erp_ventas_eventos")
    ? consultarTodos($db, "SELECT tipo_evento, monto, referencia, fecha_registro
        FROM erp_ventas_eventos
        WHERE id_venta=:venta
        ORDER BY id_venta_evento ASC", array(":venta" => intval($venta["id_venta"])))
    : array();

$resumen = resumir($venta, $pagos, $movimientosCaja, $movimientosSaldo, $detalleInventario, $eventosVenta);
$hallazgos = hallazgos($resumen, $pagos, $movimientosSaldo);

$salida = array(
    "ok" => empty($hallazgos),
    "modo" => "saldo_crm_post_readonly",
    "read_only" => true,
    "venta" => $venta,
    "resumen" => $resumen,
    "hallazgos" => $hallazgos,
    "pagos" => $pagos,
    "movimientos_caja" => $movimientosCaja,
    "movimientos_saldo_crm" => $movimientosSaldo,
    "detalle_inventario" => $detalleInventario,
    "eventos_venta" => $eventosVenta
);

if ($compacto) {
    $salida = array(
        "ok" => empty($hallazgos),
        "modo" => "saldo_crm_post_readonly",
        "read_only" => true,
        "folio" => $venta["folio"],
        "id_venta" => intval($venta["id_venta"]),
        "resumen" => $resumen,
        "hallazgos" => $hallazgos
    );
}

responder($salida);

function resumir($venta, $pagos, $movimientosCaja, $movimientosSaldo, $detalleInventario, $eventosVenta) {
    $saldoCrmPagado = 0;
    $cajaPagada = 0;
    $pagosSaldoSinCaja = 0;
    foreach ($pagos as $pago) {
        if ($pago["metodo_pago"] === "saldo_crm" || $pago["tipo_pago"] === "saldo_cliente") {
            $saldoCrmPagado += floatval($pago["monto"]);
            if (intval($pago["id_movimiento_caja"]) === 0) {
                $pagosSaldoSinCaja++;
            }
        } else {
            $cajaPagada += floatval($pago["monto"]);
        }
    }
    $montoCajaMovimientos = 0;
    foreach ($movimientosCaja as $movimiento) {
        $montoCajaMovimientos += floatval($movimiento["monto"]);
    }
    $montoSaldoCargo = 0;
    foreach ($movimientosSaldo as $movimientoSaldo) {
        if ($movimientoSaldo["tipo"] === "uso_saldo_pos" && $movimientoSaldo["naturaleza"] === "cargo") {
            $montoSaldoCargo += floatval($movimientoSaldo["monto"]);
        }
    }
    return array(
        "total_venta" => redondear($venta["total"]),
        "pagado_total_venta" => redondear($venta["pagado_total"]),
        "pagos_total" => redondear(array_sum(array_map(function ($pago) {
            return floatval($pago["monto"]);
        }, $pagos))),
        "saldo_crm_pagado" => redondear($saldoCrmPagado),
        "caja_pagada" => redondear($cajaPagada),
        "pagos_saldo_sin_caja" => $pagosSaldoSinCaja,
        "movimientos_caja_total" => redondear($montoCajaMovimientos),
        "movimientos_saldo_crm_total" => redondear($montoSaldoCargo),
        "movimientos_saldo_crm_count" => count($movimientosSaldo),
        "trazas_inventario" => count($detalleInventario),
        "eventos_venta" => count($eventosVenta)
    );
}

function hallazgos($resumen, $pagos, $movimientosSaldo) {
    $hallazgos = array();
    if ($resumen["saldo_crm_pagado"] <= 0) {
        $hallazgos[] = "La venta no tiene pago saldo_crm.";
    }
    if ($resumen["pagos_saldo_sin_caja"] <= 0) {
        $hallazgos[] = "El pago saldo_crm no aparece sin movimiento de caja.";
    }
    if (abs($resumen["saldo_crm_pagado"] - $resumen["movimientos_saldo_crm_total"]) > 0.0001) {
        $hallazgos[] = "Monto saldo_crm pagado no coincide con movimiento CRM.";
    }
    if (abs($resumen["caja_pagada"] - $resumen["movimientos_caja_total"]) > 0.0001) {
        $hallazgos[] = "Monto caja pagado no coincide con movimientos de caja.";
    }
    foreach ($pagos as $pago) {
        if (($pago["metodo_pago"] === "saldo_crm" || $pago["tipo_pago"] === "saldo_cliente") && intval($pago["id_movimiento_caja"]) > 0) {
            $hallazgos[] = "Pago saldo_crm tiene movimiento de caja.";
        }
    }
    foreach ($movimientosSaldo as $movimiento) {
        if ($movimiento["naturaleza"] !== "cargo") {
            $hallazgos[] = "Movimiento CRM de saldo no es cargo.";
        }
    }
    return array_values(array_unique($hallazgos));
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

function redondear($valor) {
    return round(floatval($valor), 6);
}

function responder($datos) {
    echo json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    exit(empty($datos["ok"]) ? 1 : 0);
}
