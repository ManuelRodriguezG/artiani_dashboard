<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-06.
 * Proposito: ligar decision financiera POS a cliente CRM y crear movimiento de saldo favor.
 * Impacto: actualiza venta/decision, crea cuenta CRM si falta, crea movimiento de saldo y eventos.
 * Contrato: requiere token; no mueve caja, no mueve inventario y no usa recompensas.
 */

$args = isset($argv) ? $argv : array();
$autorizar = "";
$respaldo = "";
$folioDecision = "";
$idClienteCrm = 0;
$idUsuario = 1;
$motivo = "Aplicacion UAT saldo favor POS a ledger CRM";

foreach ($args as $arg) {
    if (strpos($arg, "--autorizar=") === 0) {
        $autorizar = trim(substr($arg, 12), "\"' ");
    } elseif (strpos($arg, "--respaldo=") === 0) {
        $respaldo = trim(substr($arg, 11), "\"' ");
    } elseif (strpos($arg, "--folio_decision=") === 0) {
        $folioDecision = trim(substr($arg, 17), "\"' ");
    } elseif (strpos($arg, "--id_cliente_crm=") === 0) {
        $idClienteCrm = intval(trim(substr($arg, 17), "\"' "));
    } elseif (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--motivo=") === 0) {
        $motivo = trim(substr($arg, 9), "\"' ");
    }
}

$validacionRespaldo = validarRespaldo($respaldo);
if ($autorizar !== "VENTAS_POS_PEDIDOS_CLIENTE_CRM_LINK_REAL" || !$validacionRespaldo["ok"] || $folioDecision === "" || $idClienteCrm <= 0 || $idUsuario <= 0) {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "No se aplico saldo favor CRM. Falta token, respaldo, folio, cliente o usuario.",
        "validacion_respaldo" => $validacionRespaldo,
        "contrato" => contrato(false)
    ));
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";

class UatVentasPosPedidosClienteCrmLinkApplyDb extends CRUD {
    public function db() {
        return $this->getConexion();
    }
}

$db = (new UatVentasPosPedidosClienteCrmLinkApplyDb())->db();
if (!$db) {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "Conexion BD no disponible.",
        "contrato" => contrato(false)
    ));
}

try {
    validarTablas($db);
    $db->beginTransaction();

    $decision = consultarUno($db, "SELECT d.*, v.folio folio_venta, v.id_venta,
            v.tipo_documento, v.estatus estatus_venta, v.id_cliente_crm id_cliente_crm_venta,
            v.cliente_nombre_publico, v.cliente_identificador_publico
        FROM erp_ventas_pedidos_decisiones_financieras d
        INNER JOIN erp_ventas v ON v.id_venta=d.id_venta
        WHERE d.folio=:folio
        LIMIT 1 FOR UPDATE", array(":folio" => $folioDecision));

    $cliente = consultarUno($db, "SELECT c.id_cliente_crm, c.codigo_cliente, c.nombre_publico,
            c.estatus, c.calidad_datos, c.origen_alta,
            i.tipo identificador_tipo, i.valor identificador_valor
        FROM crm_clientes_maestro c
        LEFT JOIN crm_clientes_identificadores i ON i.id_cliente_crm=c.id_cliente_crm
            AND i.estatus='activo' AND i.principal=1
        WHERE c.id_cliente_crm=:cliente
        LIMIT 1", array(":cliente" => $idClienteCrm));

    $movimientoExistente = consultarUno($db, "SELECT * FROM crm_clientes_saldos_movimientos
        WHERE origen_modulo='ventas_pos' AND origen_tipo='pedido_cancelado_decision_financiera'
            AND origen_id=:origen AND estatus<>'cancelado'
        LIMIT 1 FOR UPDATE", array(":origen" => $folioDecision));

    $bloqueos = validarAplicacion($decision, $cliente, $movimientoExistente);
    if (!empty($bloqueos)) {
        throw new Exception(implode("; ", $bloqueos));
    }

    $cuenta = consultarUno($db, "SELECT * FROM crm_clientes_saldos_cuentas
        WHERE id_cliente_crm=:cliente AND moneda='MXN'
        LIMIT 1 FOR UPDATE", array(":cliente" => $idClienteCrm));

    if (!$cuenta) {
        $stmtCuenta = $db->prepare("INSERT INTO crm_clientes_saldos_cuentas
            (id_cliente_crm, moneda, saldo_disponible, saldo_retenido, saldo_total, estatus, creado_por)
            VALUES (:cliente, 'MXN', 0, 0, 0, 'activa', :usuario)");
        $stmtCuenta->execute(array(":cliente" => $idClienteCrm, ":usuario" => $idUsuario));
        $cuenta = consultarUno($db, "SELECT * FROM crm_clientes_saldos_cuentas WHERE id_cliente_saldo_cuenta=:cuenta LIMIT 1 FOR UPDATE", array(":cuenta" => intval($db->lastInsertId())));
    }

    $monto = round(floatval($decision["monto_saldo_favor"]), 6);
    $saldoAnterior = round(floatval($cuenta["saldo_total"]), 6);
    $saldoResultante = round($saldoAnterior + $monto, 6);
    $folioMovimiento = generarFolioMovimiento($db);
    $snapshot = array(
        "decision" => array(
            "folio" => $decision["folio"],
            "folio_venta" => $decision["folio_venta"],
            "monto_saldo_favor" => $monto
        ),
        "cliente" => array(
            "id_cliente_crm" => intval($cliente["id_cliente_crm"]),
            "codigo_cliente" => $cliente["codigo_cliente"],
            "nombre_publico" => $cliente["nombre_publico"],
            "identificador" => $cliente["identificador_valor"]
        ),
        "motivo" => $motivo
    );

    $stmtMov = $db->prepare("INSERT INTO crm_clientes_saldos_movimientos
        (id_cliente_saldo_cuenta, id_cliente_crm, folio, tipo, naturaleza, moneda,
         monto, saldo_anterior, saldo_resultante, origen_modulo, origen_tipo, origen_id,
         referencia_externa, descripcion, datos_snapshot, estatus, creado_por)
        VALUES
        (:cuenta, :cliente, :folio, 'abono_saldo_favor', 'abono', 'MXN',
         :monto, :saldo_anterior, :saldo_resultante, 'ventas_pos', 'pedido_cancelado_decision_financiera', :origen,
         :referencia, :descripcion, :snapshot, 'aplicado', :usuario)");
    $stmtMov->execute(array(
        ":cuenta" => intval($cuenta["id_cliente_saldo_cuenta"]),
        ":cliente" => $idClienteCrm,
        ":folio" => $folioMovimiento,
        ":monto" => $monto,
        ":saldo_anterior" => $saldoAnterior,
        ":saldo_resultante" => $saldoResultante,
        ":origen" => $decision["folio"],
        ":referencia" => $decision["folio_venta"],
        ":descripcion" => "Saldo favor por cancelacion POS " . $decision["folio_venta"],
        ":snapshot" => json_encode($snapshot, JSON_UNESCAPED_UNICODE),
        ":usuario" => $idUsuario
    ));
    $idMovimiento = intval($db->lastInsertId());

    $db->prepare("UPDATE crm_clientes_saldos_cuentas
        SET saldo_disponible=:saldo, saldo_total=:saldo, fecha_actualizacion=NOW(), actualizado_por=:usuario
        WHERE id_cliente_saldo_cuenta=:cuenta")
        ->execute(array(":saldo" => $saldoResultante, ":usuario" => $idUsuario, ":cuenta" => intval($cuenta["id_cliente_saldo_cuenta"])));

    actualizarVenta($db, $decision, $cliente, $idUsuario);
    actualizarDecision($db, $decision, $cliente, $idMovimiento, $idUsuario, $snapshot);
    registrarEventos($db, $decision, $cliente, $monto, $folioMovimiento, $snapshot, $idUsuario);

    $db->commit();
    responder(array(
        "ok" => true,
        "modo" => "pedidos_cliente_crm_link_real_authorized",
        "respaldo_ref" => $respaldo,
        "folio_decision" => $decision["folio"],
        "folio_venta" => $decision["folio_venta"],
        "id_cliente_crm" => $idClienteCrm,
        "id_cliente_saldo_cuenta" => intval($cuenta["id_cliente_saldo_cuenta"]),
        "id_cliente_saldo_movimiento" => $idMovimiento,
        "folio_movimiento" => $folioMovimiento,
        "monto" => $monto,
        "saldo_anterior" => $saldoAnterior,
        "saldo_resultante" => $saldoResultante,
        "contrato" => contrato(true)
    ));
} catch (Exception $e) {
    if ($db && $db->inTransaction()) {
        $db->rollBack();
    }
    responder(array(
        "ok" => false,
        "modo" => "pedidos_cliente_crm_link_real_authorized",
        "mensaje" => $e->getMessage(),
        "rollback" => true,
        "contrato" => contrato(false)
    ));
}

function validarTablas($db) {
    $requeridas = array("erp_ventas", "erp_ventas_pedidos_decisiones_financieras", "erp_ventas_eventos", "crm_clientes_maestro", "crm_clientes_saldos_cuentas", "crm_clientes_saldos_movimientos");
    foreach ($requeridas as $tabla) {
        if (!tablaExiste($db, $tabla)) {
            throw new Exception("Falta tabla requerida: " . $tabla);
        }
    }
}

function validarAplicacion($decision, $cliente, $movimientoExistente) {
    $bloqueos = array();
    if (!$decision) {
        $bloqueos[] = "Decision financiera no encontrada";
    } else {
        if ($decision["decision"] !== "saldo_favor") {
            $bloqueos[] = "La decision no es saldo_favor";
        }
        if ($decision["estatus"] !== "pendiente_ledger_crm") {
            $bloqueos[] = "La decision no esta pendiente de ledger CRM";
        }
        if ($decision["estatus_venta"] !== "cancelado") {
            $bloqueos[] = "El documento POS no esta cancelado";
        }
        if (intval($decision["id_saldo_cliente_movimiento"]) > 0) {
            $bloqueos[] = "La decision ya esta ligada a saldo CRM";
        }
        if (floatval($decision["monto_saldo_favor"]) <= 0) {
            $bloqueos[] = "Monto saldo favor invalido";
        }
        if (intval($decision["id_cliente_crm_venta"]) > 0 && intval($decision["id_cliente_crm_venta"]) !== intval($cliente ? $cliente["id_cliente_crm"] : 0)) {
            $bloqueos[] = "La venta ya tiene otro cliente CRM";
        }
        if (intval($decision["id_cliente_crm"]) > 0 && intval($decision["id_cliente_crm"]) !== intval($cliente ? $cliente["id_cliente_crm"] : 0)) {
            $bloqueos[] = "La decision ya tiene otro cliente CRM";
        }
    }
    if (!$cliente) {
        $bloqueos[] = "Cliente CRM no encontrado";
    } elseif ($cliente["estatus"] !== "activo") {
        $bloqueos[] = "Cliente CRM no activo";
    }
    if ($movimientoExistente) {
        $bloqueos[] = "Ya existe movimiento de saldo CRM para esta decision";
    }
    return array_values(array_unique($bloqueos));
}

function actualizarVenta($db, $decision, $cliente, $idUsuario) {
    $snapshot = array(
        "id_cliente_crm" => intval($cliente["id_cliente_crm"]),
        "codigo_cliente" => $cliente["codigo_cliente"],
        "nombre_publico" => $cliente["nombre_publico"],
        "identificador" => $cliente["identificador_valor"],
        "origen_liga" => "decision_financiera_pos",
        "folio_decision" => $decision["folio"]
    );
    $db->prepare("UPDATE erp_ventas
        SET id_cliente_crm=:cliente,
            cliente_nombre_publico=:nombre,
            cliente_identificador_publico=:identificador,
            cliente_codigo_snapshot=:codigo,
            cliente_origen_snapshot='crm',
            cliente_snapshot=:snapshot,
            fecha_actualizacion=NOW()
        WHERE id_venta=:venta")
        ->execute(array(
            ":cliente" => intval($cliente["id_cliente_crm"]),
            ":nombre" => $cliente["nombre_publico"],
            ":identificador" => $cliente["identificador_valor"],
            ":codigo" => $cliente["codigo_cliente"],
            ":snapshot" => json_encode($snapshot, JSON_UNESCAPED_UNICODE),
            ":venta" => intval($decision["id_venta"])
        ));
}

function actualizarDecision($db, $decision, $cliente, $idMovimiento, $idUsuario, $snapshot) {
    $db->prepare("UPDATE erp_ventas_pedidos_decisiones_financieras
        SET id_cliente_crm=:cliente,
            cliente_snapshot=:cliente_snapshot,
            id_saldo_cliente_movimiento=:movimiento,
            estatus='aplicada',
            aplicado_por=:usuario,
            fecha_aplicacion=NOW(),
            fecha_actualizacion=NOW(),
            datos_snapshot=:snapshot
        WHERE id_decision_financiera=:decision")
        ->execute(array(
            ":cliente" => intval($cliente["id_cliente_crm"]),
            ":cliente_snapshot" => json_encode(array(
                "codigo_cliente" => $cliente["codigo_cliente"],
                "nombre_publico" => $cliente["nombre_publico"],
                "identificador" => $cliente["identificador_valor"]
            ), JSON_UNESCAPED_UNICODE),
            ":movimiento" => $idMovimiento,
            ":usuario" => $idUsuario,
            ":snapshot" => json_encode($snapshot, JSON_UNESCAPED_UNICODE),
            ":decision" => intval($decision["id_decision_financiera"])
        ));
}

function registrarEventos($db, $decision, $cliente, $monto, $folioMovimiento, $snapshot, $idUsuario) {
    $stmtVenta = $db->prepare("INSERT INTO erp_ventas_eventos
        (id_venta, folio, tipo_evento, estatus_anterior, estatus_nuevo, monto, referencia, datos_snapshot, observaciones, creado_por)
        VALUES (:venta, :folio, 'pedido_saldo_favor_crm_aplicado', 'cancelado', 'cancelado', :monto, :referencia, :snapshot, :obs, :usuario)");
    $stmtVenta->execute(array(
        ":venta" => intval($decision["id_venta"]),
        ":folio" => $decision["folio_venta"],
        ":monto" => $monto,
        ":referencia" => $folioMovimiento,
        ":snapshot" => json_encode($snapshot, JSON_UNESCAPED_UNICODE),
        ":obs" => "folio_decision=" . $decision["folio"],
        ":usuario" => $idUsuario
    ));

    if (tablaExiste($db, "crm_clientes_eventos")) {
        $stmtCrm = $db->prepare("INSERT INTO crm_clientes_eventos
            (id_cliente_crm, tipo_evento, origen_modulo, origen_tipo, origen_id, resumen, datos_snapshot, creado_por)
            VALUES (:cliente, 'saldo_favor_abonado', 'ventas_pos', 'pedido_cancelado_decision_financiera', :origen, :resumen, :snapshot, :usuario)");
        $stmtCrm->execute(array(
            ":cliente" => intval($cliente["id_cliente_crm"]),
            ":origen" => $decision["folio"],
            ":resumen" => "Saldo favor abonado por cancelacion POS " . $decision["folio_venta"],
            ":snapshot" => json_encode($snapshot, JSON_UNESCAPED_UNICODE),
            ":usuario" => $idUsuario
        ));
    }
}

function generarFolioMovimiento($db) {
    $prefijo = "CRM-SAL-" . date("Ymd") . "-";
    $stmt = $db->prepare("SELECT COUNT(*) FROM crm_clientes_saldos_movimientos WHERE folio LIKE :folio");
    $stmt->execute(array(":folio" => $prefijo . "%"));
    return $prefijo . str_pad(strval(intval($stmt->fetchColumn()) + 1), 6, "0", STR_PAD_LEFT);
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

function validarRespaldo($respaldo) {
    $respaldo = trim((string) $respaldo);
    $placeholder = preg_match('/(PENDIENTE|RUTA_O|REFERENCIA_EXTERNA|\\[RUTA|<ruta|ruta real)/i', $respaldo) === 1;
    $esRutaLocal = preg_match('/^[A-Za-z]:[\\\\\\/]/', $respaldo) === 1 || strpos($respaldo, "\\") !== false || strpos($respaldo, "/") !== false;
    $existe = $esRutaLocal ? file_exists($respaldo) : null;
    $legible = $esRutaLocal ? ($existe && is_readable($respaldo)) : null;
    $tamano = $esRutaLocal && $existe ? filesize($respaldo) : null;
    $respaldoOk = strlen($respaldo) >= 8 && !$placeholder && (!$esRutaLocal || ($existe && $legible && $tamano > 0));
    return array(
        "ok" => $respaldoOk,
        "referencia" => $respaldo,
        "placeholder_detectado" => $placeholder,
        "parece_ruta_local" => $esRutaLocal,
        "archivo_existe" => $existe,
        "archivo_legible" => $legible,
        "tamano_bytes" => $tamano
    );
}

function contrato($aplicado) {
    return array(
        "actualiza_venta_cliente_crm" => $aplicado,
        "actualiza_decision_financiera" => $aplicado,
        "crea_cuenta_saldo_si_falta" => $aplicado,
        "crea_movimiento_saldo" => $aplicado,
        "mueve_caja" => false,
        "mueve_inventario" => false,
        "usa_recompensas" => false
    );
}

function responder($datos) {
    echo json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    exit(empty($datos["ok"]) ? 1 : 0);
}
