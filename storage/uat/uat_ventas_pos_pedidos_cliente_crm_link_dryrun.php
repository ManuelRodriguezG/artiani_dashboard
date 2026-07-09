<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-06.
 * Proposito: simular la liga de una decision financiera POS a un cliente CRM antes de crear saldo favor.
 * Impacto: valida cliente, folio, decision, cuenta CRM y movimiento propuesto sin modificar BD.
 * Contrato: read-only; no actualiza ventas, no crea cuenta, no crea movimiento y no cambia decision.
 */

$args = isset($argv) ? $argv : array();
$folioDecision = "";
$idClienteCrm = 0;
$idUsuario = 1;
$compacto = false;

foreach ($args as $arg) {
    if (strpos($arg, "--folio_decision=") === 0) {
        $folioDecision = trim(substr($arg, 17), "\"' ");
    } elseif (strpos($arg, "--id_cliente_crm=") === 0) {
        $idClienteCrm = intval(trim(substr($arg, 17), "\"' "));
    } elseif (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    } elseif ($arg === "--compact=1" || $arg === "--compacto=1") {
        $compacto = true;
    }
}

if ($folioDecision === "" || $idClienteCrm <= 0) {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "Indica --folio_decision=PFIN-... y --id_cliente_crm=ID.",
        "contrato" => contrato()
    ));
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";

class UatVentasPosPedidosClienteCrmLinkDryrunDb extends VentasErp {
    public function db() {
        return $this->getConexion();
    }
}

$modelo = new UatVentasPosPedidosClienteCrmLinkDryrunDb();
$db = $modelo->db();
if (!$db) {
    responder(array(
        "ok" => false,
        "modo" => "pedidos_cliente_crm_link_dryrun",
        "read_only" => true,
        "hallazgos" => array("Conexion BD no disponible"),
        "contrato" => contrato()
    ));
}

$tablas = estadoTablas($db, array(
    "erp_ventas",
    "erp_ventas_pedidos_decisiones_financieras",
    "crm_clientes_maestro",
    "crm_clientes_identificadores",
    "crm_clientes_saldos_cuentas",
    "crm_clientes_saldos_movimientos",
    "crm_clientes_eventos"
));

$decision = $tablas["erp_ventas_pedidos_decisiones_financieras"]
    ? consultarUno($db, "SELECT d.*, v.folio folio_venta, v.id_venta, v.tipo_documento,
            v.estatus estatus_venta, v.id_cliente_crm id_cliente_crm_venta,
            v.cliente_nombre_publico, v.cliente_identificador_publico,
            v.cliente_codigo_snapshot, v.cliente_origen_snapshot, v.cliente_snapshot
        FROM erp_ventas_pedidos_decisiones_financieras d
        INNER JOIN erp_ventas v ON v.id_venta=d.id_venta
        WHERE d.folio=:folio
        LIMIT 1", array(":folio" => $folioDecision))
    : null;

$cliente = $tablas["crm_clientes_maestro"]
    ? consultarUno($db, "SELECT c.id_cliente_crm, c.codigo_cliente, c.nombre_publico,
            c.estatus, c.calidad_datos, c.origen_alta,
            i.tipo identificador_tipo, i.valor identificador_valor
        FROM crm_clientes_maestro c
        LEFT JOIN crm_clientes_identificadores i ON i.id_cliente_crm=c.id_cliente_crm
            AND i.estatus='activo' AND i.principal=1
        WHERE c.id_cliente_crm=:cliente
        LIMIT 1", array(":cliente" => $idClienteCrm))
    : null;

$cuenta = $tablas["crm_clientes_saldos_cuentas"]
    ? consultarUno($db, "SELECT * FROM crm_clientes_saldos_cuentas
        WHERE id_cliente_crm=:cliente AND moneda='MXN'
        LIMIT 1", array(":cliente" => $idClienteCrm))
    : null;

$movimientoExistente = $decision && $tablas["crm_clientes_saldos_movimientos"]
    ? consultarUno($db, "SELECT * FROM crm_clientes_saldos_movimientos
        WHERE origen_modulo='ventas_pos' AND origen_tipo='pedido_cancelado_decision_financiera'
            AND origen_id=:origen AND estatus<>'cancelado'
        LIMIT 1", array(":origen" => $folioDecision))
    : null;

$bloqueos = validar($tablas, $decision, $cliente, $movimientoExistente);
$avisos = avisos($decision, $cliente, $cuenta);
$propuesta = propuesta($decision, $cliente, $cuenta, $idUsuario);

$salida = array(
    "ok" => empty($bloqueos),
    "modo" => "pedidos_cliente_crm_link_dryrun",
    "read_only" => true,
    "folio_decision" => $folioDecision,
    "id_cliente_crm" => $idClienteCrm,
    "decision" => $decision,
    "cliente_crm" => $cliente,
    "cuenta_saldos_actual" => $cuenta,
    "movimiento_existente" => $movimientoExistente,
    "tablas" => $tablas,
    "propuesta" => $propuesta,
    "bloqueos" => $bloqueos,
    "avisos" => $avisos,
    "contrato" => contrato()
);

if ($compacto) {
    $salida = array(
        "ok" => empty($bloqueos),
        "modo" => "pedidos_cliente_crm_link_dryrun",
        "read_only" => true,
        "folio_decision" => $folioDecision,
        "folio_venta" => $decision ? $decision["folio_venta"] : null,
        "id_cliente_crm" => $idClienteCrm,
        "cliente" => $cliente ? array(
            "codigo_cliente" => $cliente["codigo_cliente"],
            "nombre_publico" => $cliente["nombre_publico"],
            "estatus" => $cliente["estatus"],
            "identificador" => $cliente["identificador_valor"]
        ) : null,
        "monto_saldo_favor" => $decision ? floatval($decision["monto_saldo_favor"]) : 0,
        "cuenta_existe" => !empty($cuenta),
        "propuesta" => $propuesta,
        "bloqueos" => $bloqueos,
        "avisos" => $avisos,
        "contrato" => contrato()
    );
}

responder($salida);

function validar($tablas, $decision, $cliente, $movimientoExistente) {
    $bloqueos = array();
    foreach (array("erp_ventas", "erp_ventas_pedidos_decisiones_financieras", "crm_clientes_maestro", "crm_clientes_saldos_cuentas", "crm_clientes_saldos_movimientos") as $tabla) {
        if (empty($tablas[$tabla])) {
            $bloqueos[] = "Falta tabla requerida: " . $tabla;
        }
    }
    if (!$decision) {
        $bloqueos[] = "Decision financiera no encontrada.";
    } else {
        if ($decision["decision"] !== "saldo_favor") {
            $bloqueos[] = "La decision no es saldo_favor.";
        }
        if ($decision["estatus"] !== "pendiente_ledger_crm") {
            $bloqueos[] = "La decision no esta pendiente de ledger CRM.";
        }
        if ($decision["estatus_venta"] !== "cancelado") {
            $bloqueos[] = "El documento POS no esta cancelado.";
        }
        if (!in_array($decision["tipo_documento"], array("pedido", "apartado"), true)) {
            $bloqueos[] = "El folio POS no es pedido/apartado.";
        }
        if (floatval($decision["monto_saldo_favor"]) <= 0) {
            $bloqueos[] = "La decision no tiene monto de saldo favor.";
        }
        if (intval($decision["id_saldo_cliente_movimiento"]) > 0) {
            $bloqueos[] = "La decision ya esta ligada a un movimiento de saldo.";
        }
    }
    if (!$cliente) {
        $bloqueos[] = "Cliente CRM no encontrado.";
    } elseif ($cliente["estatus"] !== "activo") {
        $bloqueos[] = "Cliente CRM no activo.";
    }
    if ($movimientoExistente) {
        $bloqueos[] = "Ya existe movimiento de saldo CRM para esta decision.";
    }
    return array_values(array_unique($bloqueos));
}

function avisos($decision, $cliente, $cuenta) {
    $avisos = array();
    if ($decision && intval($decision["id_cliente_crm_venta"]) > 0 && $cliente && intval($decision["id_cliente_crm_venta"]) !== intval($cliente["id_cliente_crm"])) {
        $avisos[] = "La venta ya tiene otro id_cliente_crm; el apply real debe bloquear o requerir autorizacion superior.";
    }
    if ($decision && intval($decision["id_cliente_crm"]) > 0 && $cliente && intval($decision["id_cliente_crm"]) !== intval($cliente["id_cliente_crm"])) {
        $avisos[] = "La decision ya tiene otro id_cliente_crm; revisar antes de religar.";
    }
    if (!$cuenta) {
        $avisos[] = "No existe cuenta de saldos MXN; el apply real debera crearla con saldo cero antes del movimiento.";
    }
    if ($cliente && $cliente["origen_alta"] === "legacy_crm_clientes") {
        $avisos[] = "Cliente proviene de legacy migrado; validar que corresponde al cliente del apartado antes de aplicar.";
    }
    return array_values(array_unique($avisos));
}

function propuesta($decision, $cliente, $cuenta, $idUsuario) {
    if (!$decision || !$cliente) {
        return null;
    }
    $saldoAnterior = $cuenta ? floatval($cuenta["saldo_total"]) : 0;
    $monto = floatval($decision["monto_saldo_favor"]);
    return array(
        "actualizaria_venta" => array(
            "tabla" => "erp_ventas",
            "id_venta" => intval($decision["id_venta"]),
            "id_cliente_crm" => intval($cliente["id_cliente_crm"]),
            "cliente_nombre_publico" => $cliente["nombre_publico"],
            "cliente_identificador_publico" => $cliente["identificador_valor"],
            "cliente_codigo_snapshot" => $cliente["codigo_cliente"],
            "cliente_origen_snapshot" => "crm"
        ),
        "actualizaria_decision" => array(
            "tabla" => "erp_ventas_pedidos_decisiones_financieras",
            "folio" => $decision["folio"],
            "id_cliente_crm" => intval($cliente["id_cliente_crm"]),
            "estatus_despues_de_movimiento" => "aplicada",
            "id_saldo_cliente_movimiento" => "ID_MOVIMIENTO_CRM_SALDO"
        ),
        "crearia_cuenta_si_falta" => !$cuenta,
        "cuenta" => array(
            "id_cliente_saldo_cuenta" => $cuenta ? intval($cuenta["id_cliente_saldo_cuenta"]) : "NUEVA",
            "id_cliente_crm" => intval($cliente["id_cliente_crm"]),
            "moneda" => "MXN",
            "saldo_anterior" => $saldoAnterior,
            "saldo_resultante" => round($saldoAnterior + $monto, 6)
        ),
        "crearia_movimiento" => array(
            "folio" => "CRM-SAL-DRY-" . date("Ymd-His"),
            "tipo" => "abono_saldo_favor",
            "naturaleza" => "abono",
            "monto" => $monto,
            "saldo_anterior" => $saldoAnterior,
            "saldo_resultante" => round($saldoAnterior + $monto, 6),
            "origen_modulo" => "ventas_pos",
            "origen_tipo" => "pedido_cancelado_decision_financiera",
            "origen_id" => $decision["folio"],
            "referencia_externa" => $decision["folio_venta"],
            "creado_por" => $idUsuario
        ),
        "registraria_eventos" => array(
            "erp_ventas_eventos" => "pedido_saldo_favor_crm_aplicado",
            "crm_clientes_eventos" => "saldo_favor_abonado"
        )
    );
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
        "no_actualiza_venta" => true,
        "no_actualiza_decision" => true,
        "no_crea_cuenta_saldo" => true,
        "no_crea_movimiento_saldo" => true,
        "no_mueve_caja" => true,
        "no_mueve_inventario" => true,
        "no_usa_recompensas" => true
    );
}

function responder($datos) {
    echo json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    exit(empty($datos["ok"]) ? 1 : 0);
}
