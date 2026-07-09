<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-06.
 * Proposito: auditar si una decision financiera POS puede convertirse en saldo real de cliente CRM.
 * Impacto: prepara contrato POS -> CRM para saldos a favor sin mezclarlo con recompensas/monedero.
 * Contrato: read-only; no crea ledger, no modifica decisiones, no mueve caja ni inventario.
 */

$args = isset($argv) ? $argv : array();
$folioDecision = "";
$compacto = false;

foreach ($args as $arg) {
    if (strpos($arg, "--folio_decision=") === 0) {
        $folioDecision = trim(substr($arg, 17), "\"' ");
    } elseif ($arg === "--compact=1" || $arg === "--compacto=1") {
        $compacto = true;
    }
}

if ($folioDecision === "") {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "Indica --folio_decision=PFIN-...",
        "contrato" => contrato()
    ));
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";

class UatVentasPosPedidosCrmLedgerReadOnlyDb extends VentasErp {
    public function db() {
        return $this->getConexion();
    }
}

$modelo = new UatVentasPosPedidosCrmLedgerReadOnlyDb();
$db = $modelo->db();

if (!$db) {
    responder(array(
        "ok" => false,
        "modo" => "pedidos_crm_ledger_readonly",
        "read_only" => true,
        "folio_decision" => $folioDecision,
        "hallazgos" => array("Conexion BD no disponible"),
        "contrato" => contrato()
    ));
}

$decision = tablaExiste($db, "erp_ventas_pedidos_decisiones_financieras")
    ? consultarUno($db, "SELECT d.*, v.folio folio_venta, v.id_cliente_crm, v.cliente_nombre_publico,
            v.cliente_identificador_publico, v.estatus estatus_venta, v.tipo_documento
        FROM erp_ventas_pedidos_decisiones_financieras d
        INNER JOIN erp_ventas v ON v.id_venta=d.id_venta
        WHERE d.folio=:folio
        LIMIT 1", array(":folio" => $folioDecision))
    : null;

$tablas = array(
    "crm_clientes_maestro" => tablaExiste($db, "crm_clientes_maestro"),
    "crm_clientes_identificadores" => tablaExiste($db, "crm_clientes_identificadores"),
    "crm_clientes_eventos" => tablaExiste($db, "crm_clientes_eventos"),
    "crm_clientes_saldos_cuentas" => tablaExiste($db, "crm_clientes_saldos_cuentas"),
    "crm_clientes_saldos_movimientos" => tablaExiste($db, "crm_clientes_saldos_movimientos"),
    "crm_clientes_recompensas_cuentas" => tablaExiste($db, "crm_clientes_recompensas_cuentas"),
    "crm_clientes_recompensas_movimientos" => tablaExiste($db, "crm_clientes_recompensas_movimientos")
);

$cliente = null;
if ($decision && intval($decision["id_cliente_crm"]) > 0 && $tablas["crm_clientes_maestro"]) {
    $cliente = consultarUno($db, "SELECT id_cliente_crm, codigo_cliente, nombre_publico, estatus, calidad_datos
        FROM crm_clientes_maestro
        WHERE id_cliente_crm=:cliente
        LIMIT 1", array(":cliente" => intval($decision["id_cliente_crm"])));
}

$hallazgos = array();
$bloqueos = array();

if (!$decision) {
    $bloqueos[] = "Decision financiera no encontrada.";
} else {
    if ($decision["decision"] !== "saldo_favor") {
        $bloqueos[] = "Solo las decisiones saldo_favor entran a ledger CRM.";
    }
    if ($decision["estatus"] !== "pendiente_ledger_crm") {
        $bloqueos[] = "La decision no esta pendiente de ledger CRM.";
    }
    if (floatval($decision["monto_saldo_favor"]) <= 0) {
        $bloqueos[] = "La decision no tiene monto de saldo favor.";
    }
    if (intval($decision["id_saldo_cliente_movimiento"]) > 0) {
        $bloqueos[] = "La decision ya esta enlazada a un movimiento de saldo cliente.";
    }
    if (intval($decision["id_cliente_crm"]) <= 0) {
        $bloqueos[] = "Falta id_cliente_crm; no se debe crear saldo anonimo.";
    } elseif (!$cliente) {
        $bloqueos[] = "El cliente CRM indicado no existe o no esta disponible.";
    } elseif ($cliente["estatus"] !== "activo") {
        $bloqueos[] = "El cliente CRM no esta activo.";
    }
}

if (!$tablas["crm_clientes_saldos_cuentas"] || !$tablas["crm_clientes_saldos_movimientos"]) {
    $bloqueos[] = "Falta esquema CRM de saldos/cuenta corriente.";
}

if ($tablas["crm_clientes_recompensas_cuentas"] || $tablas["crm_clientes_recompensas_movimientos"]) {
    $hallazgos[] = "Existen tablas de recompensas/monedero, pero no deben usarse para saldo favor de cancelacion; son beneficios/incentivos, no pasivo con cliente.";
}

$propuesta = array(
    "requiere_ddl_crm_saldos" => !$tablas["crm_clientes_saldos_cuentas"] || !$tablas["crm_clientes_saldos_movimientos"],
    "tablas_recomendadas" => array("crm_clientes_saldos_cuentas", "crm_clientes_saldos_movimientos"),
    "movimiento_propuesto" => $decision ? array(
        "id_cliente_crm" => intval($decision["id_cliente_crm"]),
        "origen_modulo" => "ventas_pos",
        "origen_tipo" => "pedido_cancelado_decision_financiera",
        "origen_id" => $decision["folio"],
        "folio_venta" => $decision["folio_venta"],
        "tipo" => "abono_saldo_favor",
        "monto" => floatval($decision["monto_saldo_favor"]),
        "moneda" => "MXN",
        "estatus" => "aplicado"
    ) : null,
    "actualizacion_pos_posterior" => $decision ? array(
        "tabla" => "erp_ventas_pedidos_decisiones_financieras",
        "folio" => $decision["folio"],
        "estatus" => "aplicada",
        "campo_enlace" => "id_saldo_cliente_movimiento"
    ) : null
);

$salida = array(
    "ok" => empty($bloqueos),
    "modo" => "pedidos_crm_ledger_readonly",
    "read_only" => true,
    "folio_decision" => $folioDecision,
    "decision" => $decision,
    "cliente_crm" => $cliente,
    "tablas" => $tablas,
    "propuesta" => $propuesta,
    "bloqueos" => array_values(array_unique($bloqueos)),
    "hallazgos" => array_values(array_unique($hallazgos)),
    "contrato" => contrato()
);

if ($compacto) {
    $salida = array(
        "ok" => empty($bloqueos),
        "modo" => "pedidos_crm_ledger_readonly",
        "read_only" => true,
        "folio_decision" => $folioDecision,
        "folio_venta" => $decision ? $decision["folio_venta"] : null,
        "id_cliente_crm" => $decision ? intval($decision["id_cliente_crm"]) : 0,
        "monto_saldo_favor" => $decision ? floatval($decision["monto_saldo_favor"]) : 0,
        "estatus_decision" => $decision ? $decision["estatus"] : null,
        "requiere_ddl_crm_saldos" => $propuesta["requiere_ddl_crm_saldos"],
        "bloqueos" => array_values(array_unique($bloqueos)),
        "hallazgos" => array_values(array_unique($hallazgos)),
        "contrato" => contrato()
    );
}

responder($salida);

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
        "no_crea_ledger" => true,
        "no_modifica_decision_pos" => true,
        "no_mueve_caja" => true,
        "no_mueve_inventario" => true,
        "no_usa_recompensas_como_saldo_favor" => true
    );
}

function responder($datos) {
    echo json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    exit(empty($datos["ok"]) ? 1 : 0);
}
