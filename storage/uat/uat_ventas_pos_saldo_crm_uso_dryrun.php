<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-06.
 * Proposito: simular uso de saldo monetario CRM como forma de pago POS.
 * Impacto: valida cliente, cuenta, saldo disponible, monto y contrato de pago sin caja.
 * Contrato: read-only; no crea venta, no descuenta saldo, no crea pago, no mueve caja ni inventario.
 */

$args = isset($argv) ? $argv : array();
$idClienteCrm = 0;
$monto = 0;
$idUsuario = 1;
$totalVenta = null;
$compacto = false;

foreach ($args as $arg) {
    if (strpos($arg, "--id_cliente_crm=") === 0) {
        $idClienteCrm = intval(trim(substr($arg, 17), "\"' "));
    } elseif (strpos($arg, "--monto=") === 0) {
        $monto = floatval(trim(substr($arg, 8), "\"' "));
    } elseif (strpos($arg, "--total_venta=") === 0) {
        $totalVenta = floatval(trim(substr($arg, 14), "\"' "));
    } elseif (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    } elseif ($arg === "--compact=1" || $arg === "--compacto=1") {
        $compacto = true;
    }
}

if ($idClienteCrm <= 0 || $monto <= 0) {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "Indica --id_cliente_crm=ID y --monto=MONTO mayor a cero.",
        "contrato" => contrato()
    ));
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";

class UatVentasPosSaldoCrmUsoDryrunDb extends VentasErp {
    public function db() {
        return $this->getConexion();
    }
}

$modelo = new UatVentasPosSaldoCrmUsoDryrunDb();
$db = $modelo->db();
if (!$db) {
    responder(array(
        "ok" => false,
        "modo" => "saldo_crm_uso_dryrun",
        "read_only" => true,
        "hallazgos" => array("Conexion BD no disponible"),
        "contrato" => contrato()
    ));
}

$tablas = estadoTablas($db, array(
    "crm_clientes_maestro",
    "crm_clientes_saldos_cuentas",
    "crm_clientes_saldos_movimientos",
    "erp_ventas",
    "erp_ventas_pagos"
));

$cliente = $tablas["crm_clientes_maestro"]
    ? consultarUno($db, "SELECT id_cliente_crm, codigo_cliente, nombre_publico, estatus, calidad_datos
        FROM crm_clientes_maestro
        WHERE id_cliente_crm=:cliente
        LIMIT 1", array(":cliente" => $idClienteCrm))
    : null;

$cuenta = $tablas["crm_clientes_saldos_cuentas"]
    ? consultarUno($db, "SELECT id_cliente_saldo_cuenta, id_cliente_crm, moneda,
            saldo_disponible, saldo_retenido, saldo_total, estatus
        FROM crm_clientes_saldos_cuentas
        WHERE id_cliente_crm=:cliente AND moneda='MXN'
        LIMIT 1", array(":cliente" => $idClienteCrm))
    : null;

$bloqueos = validar($tablas, $cliente, $cuenta, $monto, $totalVenta);
$avisos = avisos($totalVenta, $monto);
$saldoDisponible = $cuenta ? floatval($cuenta["saldo_disponible"]) : 0;
$saldoResultante = round($saldoDisponible - $monto, 6);
$totalEvaluado = $totalVenta === null ? $monto : $totalVenta;

$propuesta = array(
    "pago_pos" => array(
        "id_metodo_pago" => null,
        "metodo_pago" => "saldo_crm",
        "tipo_pago" => "saldo_cliente",
        "monto" => round($monto, 6),
        "referencia" => "CRM-SALDO-DRY-" . date("Ymd-His"),
        "crearia_movimiento_caja" => false
    ),
    "movimiento_crm_saldo" => array(
        "tipo" => "uso_saldo_pos",
        "naturaleza" => "cargo",
        "monto" => round($monto, 6),
        "saldo_anterior" => round($saldoDisponible, 6),
        "saldo_resultante" => $saldoResultante,
        "origen_modulo" => "ventas_pos",
        "origen_tipo" => "venta_pos_pago_saldo_crm",
        "origen_id" => "FOLIO_VENTA_POS_REAL"
    ),
    "totales_simulados" => array(
        "total_venta" => round($totalEvaluado, 6),
        "cubierto_con_saldo_crm" => round(min($monto, $totalEvaluado), 6),
        "restante_por_otro_pago" => round(max(0, $totalEvaluado - $monto), 6),
        "cambio_no_permitido_desde_saldo" => $monto > $totalEvaluado
    ),
    "eventos" => array(
        "erp_ventas_eventos" => "pago_saldo_crm_aplicado",
        "crm_clientes_eventos" => "saldo_crm_usado_pos"
    )
);

$salida = array(
    "ok" => empty($bloqueos),
    "modo" => "saldo_crm_uso_dryrun",
    "read_only" => true,
    "id_cliente_crm" => $idClienteCrm,
    "monto" => round($monto, 6),
    "cliente" => $cliente,
    "cuenta" => $cuenta,
    "tablas" => $tablas,
    "propuesta" => $propuesta,
    "bloqueos" => $bloqueos,
    "avisos" => $avisos,
    "contrato" => contrato()
);

if ($compacto) {
    $salida = array(
        "ok" => empty($bloqueos),
        "modo" => "saldo_crm_uso_dryrun",
        "read_only" => true,
        "id_cliente_crm" => $idClienteCrm,
        "cliente" => $cliente ? $cliente["nombre_publico"] : null,
        "saldo_disponible" => round($saldoDisponible, 6),
        "monto" => round($monto, 6),
        "saldo_resultante" => $saldoResultante,
        "total_venta" => round($totalEvaluado, 6),
        "restante_por_otro_pago" => $propuesta["totales_simulados"]["restante_por_otro_pago"],
        "bloqueos" => $bloqueos,
        "avisos" => $avisos,
        "contrato" => contrato()
    );
}

responder($salida);

function validar($tablas, $cliente, $cuenta, $monto, $totalVenta) {
    $bloqueos = array();
    foreach (array("crm_clientes_maestro", "crm_clientes_saldos_cuentas", "crm_clientes_saldos_movimientos", "erp_ventas", "erp_ventas_pagos") as $tabla) {
        if (empty($tablas[$tabla])) {
            $bloqueos[] = "Falta tabla requerida: " . $tabla;
        }
    }
    if (!$cliente) {
        $bloqueos[] = "Cliente CRM no encontrado.";
    } elseif ($cliente["estatus"] !== "activo") {
        $bloqueos[] = "Cliente CRM no activo.";
    }
    if (!$cuenta) {
        $bloqueos[] = "Cliente CRM sin cuenta de saldos MXN.";
    } elseif ($cuenta["estatus"] !== "activa") {
        $bloqueos[] = "Cuenta de saldos no activa.";
    } elseif (floatval($cuenta["saldo_disponible"]) + 0.0001 < $monto) {
        $bloqueos[] = "Saldo CRM insuficiente.";
    }
    if ($monto <= 0) {
        $bloqueos[] = "Monto invalido.";
    }
    if ($totalVenta !== null && $totalVenta <= 0) {
        $bloqueos[] = "Total de venta invalido.";
    }
    if ($totalVenta !== null && $monto - $totalVenta > 0.0001) {
        $bloqueos[] = "El saldo CRM no debe generar cambio; monto mayor al total de venta.";
    }
    return array_values(array_unique($bloqueos));
}

function avisos($totalVenta, $monto) {
    $avisos = array();
    if ($totalVenta === null) {
        $avisos[] = "No se indico total_venta; se valida solo disponibilidad del saldo.";
    } elseif ($monto < $totalVenta) {
        $avisos[] = "Venta quedaria con restante por cubrir con otro metodo de pago.";
    }
    $avisos[] = "El apply real debe ejecutarse dentro de la misma transaccion que confirma la venta POS.";
    return array_values(array_unique($avisos));
}

function estadoTablas($db, $tablas) {
    $estado = array();
    foreach ($tablas as $tabla) {
        $estado[$tabla] = tablaExiste($db, $tabla);
    }
    return $estado;
}

function consultarUno($db, $sql, $params) {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $fila = $stmt->fetch(PDO::FETCH_ASSOC);
    return $fila ?: null;
}

function tablaExiste($db, $tabla) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=:tabla");
    $stmt->execute(array(":tabla" => $tabla));
    return intval($stmt->fetchColumn()) > 0;
}

function contrato() {
    return array(
        "no_escribe_bd" => true,
        "no_crea_venta" => true,
        "no_crea_pago" => true,
        "no_descuenta_saldo_crm" => true,
        "no_mueve_caja" => true,
        "no_mueve_inventario" => true,
        "no_usa_recompensas" => true
    );
}

function responder($datos) {
    echo json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    exit(empty($datos["ok"]) ? 1 : 0);
}
