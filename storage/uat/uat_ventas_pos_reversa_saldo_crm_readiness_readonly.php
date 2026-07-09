<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-07.
 * Proposito: auditar si una reversa POS puede respetar pagos mixtos caja + saldo CRM antes de ejecutarse.
 * Impacto: evita reembolsar desde caja dinero que originalmente salio de saldo cliente.
 * Contrato: read-only; no crea devoluciones, no mueve caja, no mueve inventario y no afecta saldo CRM.
 */

$args = isset($argv) ? $argv : array();
$folio = "";
$idVenta = 0;
$idUsuario = 0;
$idDetalle = 0;
$cantidad = 0;
$tipo = "devolucion";
$motivo = "UAT reversa POS con saldo CRM";
$decisionInventario = "cuarentena";
$decisionFinanciera = "saldo_favor";
$compacto = false;

foreach ($args as $arg) {
    if (strpos($arg, "--folio=") === 0) {
        $folio = trim(substr($arg, 8), "\"' ");
    } elseif (strpos($arg, "--id_venta=") === 0) {
        $idVenta = intval(trim(substr($arg, 11), "\"' "));
    } elseif (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_venta_detalle=") === 0) {
        $idDetalle = intval(trim(substr($arg, 19), "\"' "));
    } elseif (strpos($arg, "--cantidad=") === 0) {
        $cantidad = floatval(trim(substr($arg, 11), "\"' "));
    } elseif (strpos($arg, "--tipo=") === 0) {
        $tipo = trim(substr($arg, 7), "\"' ");
    } elseif (strpos($arg, "--motivo=") === 0) {
        $motivo = trim(substr($arg, 9), "\"' ");
    } elseif (strpos($arg, "--decision_inventario=") === 0) {
        $decisionInventario = trim(substr($arg, 22), "\"' ");
    } elseif (strpos($arg, "--decision_financiera=") === 0) {
        $decisionFinanciera = trim(substr($arg, 22), "\"' ");
    } elseif ($arg === "--compact=1" || $arg === "--compacto=1") {
        $compacto = true;
    }
}

if ($folio === "" && $idVenta <= 0) {
    responder(array(
        "ok" => false,
        "modo" => "reversa_saldo_crm_readiness",
        "read_only" => true,
        "mensaje" => "Indica --folio=POS-YYYYMMDD-###### o --id_venta=ID."
    ));
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";

class UatVentasPosReversaSaldoCrmReadonlyDb extends CRUD {
    public function db() {
        return $this->getConexion();
    }
}

$db = (new UatVentasPosReversaSaldoCrmReadonlyDb())->db();
if (!$db) {
    responder(array(
        "ok" => false,
        "modo" => "reversa_saldo_crm_readiness",
        "read_only" => true,
        "mensaje" => "Conexion BD no disponible."
    ));
}

$venta = consultarVenta($db, $folio, $idVenta);
if (!$venta) {
    responder(array(
        "ok" => false,
        "modo" => "reversa_saldo_crm_readiness",
        "read_only" => true,
        "mensaje" => "Venta no encontrada.",
        "referencia" => $idVenta > 0 ? $idVenta : $folio
    ));
}

$idVenta = intval($venta["id_venta"]);
$folio = $venta["folio"];
$detalles = consultarTodos($db, "SELECT id_venta_detalle, id_venta, sku, descripcion, cantidad_base, total, estatus
    FROM erp_ventas_detalle
    WHERE id_venta=:venta AND estatus<>'cancelada'
    ORDER BY renglon ASC, id_venta_detalle ASC", array(":venta" => $idVenta));

if ($idDetalle <= 0 && count($detalles) === 1) {
    $idDetalle = intval($detalles[0]["id_venta_detalle"]);
}
if ($cantidad <= 0 && count($detalles) === 1) {
    $cantidad = floatval($detalles[0]["cantidad_base"]);
}

$items = array();
if ($idDetalle > 0 || $cantidad > 0) {
    $items[] = array(
        "id_venta_detalle" => $idDetalle,
        "cantidad_base" => $cantidad
    );
}

$ventas = new VentasErp();
$dryRun = $ventas->devolucionDryRun(array(
    "folio" => $folio,
    "id_venta" => $idVenta,
    "id_usuario" => $idUsuario,
    "tipo" => $tipo,
    "motivo" => $motivo,
    "decision_inventario" => $decisionInventario,
    "items" => json_encode($items)
));

$depurarDry = isset($dryRun["depurar"]) && is_array($dryRun["depurar"]) ? $dryRun["depurar"] : array();
$bloqueos = isset($depurarDry["bloqueos"]) ? $depurarDry["bloqueos"] : array();
$avisos = isset($depurarDry["avisos"]) ? $depurarDry["avisos"] : array();
$reembolsoEstimado = isset($depurarDry["totales"]["reembolso_estimado"]) ? redondear($depurarDry["totales"]["reembolso_estimado"]) : 0;
$partidas = isset($depurarDry["partidas"]) ? $depurarDry["partidas"] : array();

$pagos = consultarTodos($db, "SELECT id_venta_pago, id_movimiento_caja, metodo_pago, tipo_pago, monto, referencia, estatus
    FROM erp_ventas_pagos
    WHERE id_venta=:venta AND estatus='registrado'
    ORDER BY id_venta_pago ASC", array(":venta" => $idVenta));
$mezclaPagos = resumirPagos($pagos);
$reversasPrevias = consultarReversasPrevias($db, $idVenta);
$montoCajaDisponible = max(0, redondear($mezclaPagos["caja_pagada"] - $reversasPrevias["reembolso_caja_total"]));
$montoSaldoCrmOriginal = $mezclaPagos["saldo_crm_pagado"];
$montoNoCajaOriginal = $mezclaPagos["saldo_crm_pagado"] + $mezclaPagos["pagos_sin_caja_no_crm"];
$recomendacion = array();

if (!empty($bloqueos)) {
    $recomendacion[] = "Resolver primero los bloqueos del dry-run base.";
}
if ($reembolsoEstimado > 0 && $montoSaldoCrmOriginal > 0) {
    $avisos[] = "La venta original uso saldo CRM; la reversa debe separar dinero de caja y saldo cliente.";
}
if ($decisionFinanciera === "reembolso_caja" && $reembolsoEstimado > $montoCajaDisponible + 0.0001) {
    $bloqueos[] = "Reembolso caja solicitado excede caja pagada disponible por $" . formatoMonto($reembolsoEstimado - $montoCajaDisponible) . ".";
    $recomendacion[] = "No reembolsar desde caja la parte pagada con saldo CRM.";
}
if ($decisionFinanciera === "reembolso_caja" && $montoSaldoCrmOriginal > 0 && $reembolsoEstimado > $montoCajaDisponible + 0.0001) {
    $recomendacion[] = "Se requiere decision mixta: caja hasta $" . formatoMonto($montoCajaDisponible) . " y reintegro/saldo CRM por $" . formatoMonto($reembolsoEstimado - $montoCajaDisponible) . ".";
}
if ($decisionFinanciera === "saldo_favor" && $montoSaldoCrmOriginal > 0) {
    $avisos[] = "El saldo_favor actual de reversas POS queda en devolucion POS; no crea todavia movimiento CRM automatico.";
    $recomendacion[] = "Antes de aplicar real, preparar decision financiera ligada a CRM para reintegrar saldo cliente con ledger.";
}
if ($decisionFinanciera === "reintegro_saldo_crm" && $reembolsoEstimado > $montoSaldoCrmOriginal + 0.0001) {
    $bloqueos[] = "Reintegro saldo CRM solicitado excede saldo CRM pagado por $" . formatoMonto($reembolsoEstimado - $montoSaldoCrmOriginal) . ".";
    $recomendacion[] = "Usa reintegro_saldo_crm solo por la parte originalmente pagada con saldo CRM.";
}
if ($decisionFinanciera === "reintegro_saldo_crm" && $reembolsoEstimado <= $montoSaldoCrmOriginal + 0.0001) {
    $recomendacion[] = "Puede reintegrarse a saldo CRM sin movimiento de caja si el modelo real valida cliente/cuenta activa.";
}
if ($decisionFinanciera === "mixta_saldo_crm") {
    $montoCajaMixta = min($reembolsoEstimado, $montoCajaDisponible);
    $montoSaldoMixta = max(0, $reembolsoEstimado - $montoCajaMixta);
    if ($montoSaldoMixta > $montoSaldoCrmOriginal + 0.0001) {
        $bloqueos[] = "Reversa mixta excede saldo CRM pagado por $" . formatoMonto($montoSaldoMixta - $montoSaldoCrmOriginal) . ".";
    }
    $recomendacion[] = "Reversa mixta propuesta: caja $" . formatoMonto($montoCajaMixta) . " y saldo CRM $" . formatoMonto($montoSaldoMixta) . ".";
}
if ($decisionFinanciera === "sin_reembolso" && $reembolsoEstimado > 0) {
    $avisos[] = "Sin reembolso deja monto economico pendiente de politica/servicio al cliente.";
}
if ($montoNoCajaOriginal > 0 && !in_array($decisionFinanciera, array("saldo_favor", "sin_reembolso"), true)) {
    $avisos[] = "Hay pagos sin caja en la venta original; deben quedar fuera del arqueo de caja.";
}
if (empty($recomendacion)) {
    $recomendacion[] = "La decision financiera no rebasa caja pagada disponible segun esta auditoria read-only.";
}

$salida = array(
    "ok" => empty($bloqueos),
    "modo" => "reversa_saldo_crm_readiness",
    "read_only" => true,
    "venta" => array(
        "id_venta" => $idVenta,
        "folio" => $folio,
        "estatus" => $venta["estatus"],
        "total" => redondear($venta["total"]),
        "pagado_total" => redondear($venta["pagado_total"]),
        "id_cliente_crm" => isset($venta["id_cliente_crm"]) ? intval($venta["id_cliente_crm"]) : 0
    ),
    "solicitud" => array(
        "tipo" => $tipo,
        "decision_inventario" => $decisionInventario,
        "decision_financiera" => $decisionFinanciera,
        "items" => $items
    ),
    "dry_run" => array(
        "tipo" => isset($dryRun["tipo"]) ? $dryRun["tipo"] : "",
        "mensaje" => isset($dryRun["mensaje"]) ? $dryRun["mensaje"] : "",
        "reembolso_estimado" => $reembolsoEstimado,
        "partidas" => count($partidas)
    ),
    "pagos_originales" => $mezclaPagos,
    "reversas_previas" => $reversasPrevias,
    "capacidad_financiera" => array(
        "caja_disponible_para_reembolso" => redondear($montoCajaDisponible),
        "saldo_crm_original" => redondear($montoSaldoCrmOriginal),
        "monto_no_caja_original" => redondear($montoNoCajaOriginal),
        "monto_requiere_ruta_no_caja" => redondear(max(0, $reembolsoEstimado - $montoCajaDisponible))
    ),
    "bloqueos" => array_values(array_unique($bloqueos)),
    "avisos" => array_values(array_unique($avisos)),
    "recomendacion" => array_values(array_unique($recomendacion))
);

if ($compacto) {
    $salida = array(
        "ok" => empty($bloqueos),
        "modo" => "reversa_saldo_crm_readiness",
        "read_only" => true,
        "folio" => $folio,
        "id_venta" => $idVenta,
        "decision_financiera" => $decisionFinanciera,
        "reembolso_estimado" => $reembolsoEstimado,
        "pagos_originales" => $mezclaPagos,
        "capacidad_financiera" => $salida["capacidad_financiera"],
        "bloqueos" => $salida["bloqueos"],
        "avisos" => $salida["avisos"],
        "recomendacion" => $salida["recomendacion"]
    );
}

responder($salida);

function consultarVenta($db, $folio, $idVenta) {
    $where = $idVenta > 0 ? "id_venta=:ref" : "folio=:ref";
    $stmt = $db->prepare("SELECT * FROM erp_ventas WHERE " . $where . " LIMIT 1");
    $stmt->execute(array(":ref" => $idVenta > 0 ? $idVenta : $folio));
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

function consultarReversasPrevias($db, $idVenta) {
    if (!tablaExiste($db, "erp_ventas_devoluciones")) {
        return array(
            "reversas" => 0,
            "reembolso_caja_total" => 0,
            "saldo_favor_total" => 0
        );
    }
    $stmt = $db->prepare("SELECT COUNT(*) AS reversas,
            COALESCE(SUM(monto_reembolso), 0) AS reembolso_caja_total,
            COALESCE(SUM(monto_saldo_favor), 0) AS saldo_favor_total
        FROM erp_ventas_devoluciones
        WHERE id_venta=:venta AND estatus NOT IN ('cancelada','rechazada')");
    $stmt->execute(array(":venta" => intval($idVenta)));
    $fila = $stmt->fetch(PDO::FETCH_ASSOC);
    return array(
        "reversas" => intval($fila["reversas"]),
        "reembolso_caja_total" => redondear($fila["reembolso_caja_total"]),
        "saldo_favor_total" => redondear($fila["saldo_favor_total"])
    );
}

function resumirPagos($pagos) {
    $saldoCrm = 0;
    $caja = 0;
    $sinCajaNoCrm = 0;
    $total = 0;
    foreach ($pagos as $pago) {
        if ($pago["tipo_pago"] === "reembolso") {
            continue;
        }
        $monto = redondear($pago["monto"]);
        $total += $monto;
        $esSaldoCrm = $pago["metodo_pago"] === "saldo_crm" || $pago["tipo_pago"] === "saldo_cliente";
        if ($esSaldoCrm) {
            $saldoCrm += $monto;
        } elseif (intval($pago["id_movimiento_caja"]) > 0) {
            $caja += $monto;
        } else {
            $sinCajaNoCrm += $monto;
        }
    }
    return array(
        "total_pagado" => redondear($total),
        "caja_pagada" => redondear($caja),
        "saldo_crm_pagado" => redondear($saldoCrm),
        "pagos_sin_caja_no_crm" => redondear($sinCajaNoCrm),
        "pagos_registrados" => count($pagos)
    );
}

function redondear($valor) {
    return round(floatval($valor), 6);
}

function formatoMonto($valor) {
    return number_format(redondear($valor), 2, ".", "");
}

function responder($datos) {
    echo json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    exit(empty($datos["ok"]) ? 1 : 0);
}
