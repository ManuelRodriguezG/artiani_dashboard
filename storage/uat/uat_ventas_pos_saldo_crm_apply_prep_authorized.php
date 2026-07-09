<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-06.
 * Proposito: preparar el apply real de uso de saldo CRM en POS con validacion transaccional previa.
 * Impacto: valida cliente, saldo, turno, carrito e integracion sin mover caja, inventario ni saldo.
 * Contrato: read-only aun con token; la ejecucion real requiere autorizacion fuerte separada.
 */

$args = isset($argv) ? $argv : array();
$autorizar = "";
$respaldo = "";
$idUsuario = 0;
$idClienteCrm = 0;
$montoSaldoCrm = 0;
$idSkuSimple = 0;
$cantidadSimple = 0;
$precioSimple = 0;
$pagoCaja = 0;
$itemsArg = "";
$pagosCajaArg = "";
$compacto = false;

foreach ($args as $arg) {
    if (strpos($arg, "--autorizar=") === 0) {
        $autorizar = trim(substr($arg, 12), "\"' ");
    } elseif (strpos($arg, "--respaldo=") === 0) {
        $respaldo = trim(substr($arg, 11), "\"' ");
    } elseif (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_cliente_crm=") === 0) {
        $idClienteCrm = intval(trim(substr($arg, 17), "\"' "));
    } elseif (strpos($arg, "--monto_saldo_crm=") === 0) {
        $montoSaldoCrm = redondear(substr($arg, 18));
    } elseif (strpos($arg, "--id_sku=") === 0) {
        $idSkuSimple = intval(trim(substr($arg, 9), "\"' "));
    } elseif (strpos($arg, "--cantidad=") === 0) {
        $cantidadSimple = redondear(substr($arg, 11));
    } elseif (strpos($arg, "--precio=") === 0) {
        $precioSimple = redondear(substr($arg, 9));
    } elseif (strpos($arg, "--pago_caja=") === 0) {
        $pagoCaja = redondear(substr($arg, 12));
    } elseif (strpos($arg, "--items=") === 0) {
        $itemsArg = trim(substr($arg, 8), "\"' ");
    } elseif (strpos($arg, "--pagos_caja=") === 0) {
        $pagosCajaArg = trim(substr($arg, 13), "\"' ");
    } elseif ($arg === "--compact=1" || $arg === "--compacto=1") {
        $compacto = true;
    }
}

$validacionRespaldo = validarRespaldo($respaldo);
if ($autorizar !== "VENTAS_POS_SALDO_CRM_APPLY_PREP" || !$validacionRespaldo["ok"] || $idUsuario <= 0 || $idClienteCrm <= 0 || $montoSaldoCrm <= 0) {
    responder(array(
        "ok" => false,
        "modo" => "bloqueado",
        "mensaje" => "No se preparo apply saldo CRM. Falta token de preparacion, respaldo vigente, usuario, cliente CRM o monto.",
        "validacion_respaldo" => $validacionRespaldo,
        "requisitos" => array(
            "--autorizar=VENTAS_POS_SALDO_CRM_APPLY_PREP",
            "--respaldo=UAT_POS_VIGENTE_O_RUTA",
            "--id_usuario=ID_OPERADOR_POS",
            "--id_cliente_crm=ID_CLIENTE_CRM",
            "--monto_saldo_crm=MONTO",
            "--items='[{\"id_sku\":1760,\"cantidad\":1,\"precio_unitario\":295,\"modo_salida\":\"existencia_agregada\"}]'"
        ),
        "contrato" => contrato(false)
    ));
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";

class UatVentasPosSaldoCrmApplyPrepDb extends VentasErp {
    public function db() {
        return $this->getConexion();
    }
}

$ventas = new UatVentasPosSaldoCrmApplyPrepDb();
$db = $ventas->db();
if (!$db) {
    responder(array(
        "ok" => false,
        "modo" => "saldo_crm_apply_prep",
        "mensaje" => "Conexion BD no disponible.",
        "contrato" => contrato(false)
    ));
}

$items = $itemsArg !== "" ? $itemsArg : json_encode(array(array(
    "id_sku" => $idSkuSimple > 0 ? $idSkuSimple : 1760,
    "cantidad" => $cantidadSimple > 0 ? $cantidadSimple : 1,
    "precio_unitario" => $precioSimple > 0 ? $precioSimple : 295,
    "modo_salida" => "existencia_agregada"
)));
$pagosCaja = $pagosCajaArg !== "" ? json_decode($pagosCajaArg, true) : array();
if (!is_array($pagosCaja)) {
    $pagosCaja = array();
}
if ($pagoCaja > 0 && empty($pagosCaja)) {
    $pagosCaja[] = array(
        "id_metodo_pago" => 1,
        "monto" => $pagoCaja,
        "referencia" => "UAT-SALDO-CRM-MIXTO"
    );
}

$tablas = estadoTablas($db, array(
    "crm_clientes_maestro",
    "crm_clientes_saldos_cuentas",
    "crm_clientes_saldos_movimientos",
    "crm_clientes_eventos",
    "erp_ventas",
    "erp_ventas_detalle",
    "erp_ventas_pagos",
    "erp_ventas_eventos",
    "erp_pos_turnos",
    "erp_pos_movimientos_caja"
));
$cliente = $tablas["crm_clientes_maestro"]
    ? consultarUno($db, "SELECT c.id_cliente_crm, c.codigo_cliente, c.nombre_publico,
            c.estatus, c.calidad_datos, c.origen_alta,
            i.valor identificador
        FROM crm_clientes_maestro c
        LEFT JOIN crm_clientes_identificadores i ON i.id_cliente_crm=c.id_cliente_crm
            AND i.estatus='activo' AND i.principal=1
        WHERE c.id_cliente_crm=:cliente
        LIMIT 1", array(":cliente" => $idClienteCrm))
    : null;
$cuenta = $tablas["crm_clientes_saldos_cuentas"]
    ? consultarUno($db, "SELECT *
        FROM crm_clientes_saldos_cuentas
        WHERE id_cliente_crm=:cliente AND moneda='MXN'
        LIMIT 1", array(":cliente" => $idClienteCrm))
    : null;

$asignacion = $ventas->asignacionActualTerminalPos(array("id_usuario" => $idUsuario));
$depurarAsignacion = valor($asignacion, array("depurar"), array());
$datosAsignacion = valor($depurarAsignacion, array("asignacion"), array());
$turno = valor($depurarAsignacion, array("turno_abierto"), array());

$pagosParaPrevalidacion = $pagosCaja;
$pagosParaPrevalidacion[] = array(
    "id_metodo_pago" => null,
    "metodo_pago" => "saldo_crm",
    "tipo_pago" => "saldo_cliente",
    "monto" => $montoSaldoCrm,
    "referencia" => "CRM-SALDO-PREP"
);

$datosVenta = array(
    "id_almacen" => intval(valor($datosAsignacion, array("id_almacen"), 0)),
    "id_caja" => intval(valor($datosAsignacion, array("id_caja"), 0)),
    "id_turno_caja" => intval(valor($turno, array("id_turno_caja"), 0)),
    "canal" => "pos",
    "id_cliente" => $idClienteCrm,
    "identificador_cliente" => $cliente ? (string) valor($cliente, array("identificador"), "") : "",
    "items" => $items,
    "pagos" => json_encode($pagosParaPrevalidacion, JSON_UNESCAPED_UNICODE),
    "exigir_pago_completo" => 1
);

$prevalidacion = $ventas->prevalidarCarritoPos($datosVenta);
$confirmacion = $ventas->confirmarVentaPosDryRun($datosVenta);
$saldoDisponible = $cuenta ? redondear($cuenta["saldo_disponible"]) : 0;
$totales = valor($prevalidacion, array("depurar", "totales"), array());
$totalVenta = redondear(valor($totales, array("total_estimado"), 0));
$pagadoTotal = redondear(valor($totales, array("pagado_total"), 0));
$montoCaja = redondear($pagadoTotal - $montoSaldoCrm);

$bloqueos = array();
foreach (array(
    validarTablas($tablas),
    validarClienteSaldo($cliente, $cuenta, $montoSaldoCrm, $totalVenta),
    valor($asignacion, array("depurar", "bloqueos"), array()),
    valor($prevalidacion, array("depurar", "bloqueos"), array()),
    valor($confirmacion, array("depurar", "bloqueos"), array())
) as $lista) {
    foreach ($lista as $bloqueo) {
        $bloqueos[] = $bloqueo;
    }
}
if (!$asignacion || !empty($asignacion["error"]) || empty($depurarAsignacion["asignacion_activa"])) {
    $bloqueos[] = "No hay asignacion POS activa para el usuario.";
}
if (empty($turno)) {
    $bloqueos[] = "No hay turno abierto para la caja asignada.";
}
if ($montoCaja < -0.0001) {
    $bloqueos[] = "El saldo CRM no debe exceder el total de la venta ni generar cambio.";
}

$propuesta = array(
    "venta" => array(
        "id_usuario" => $idUsuario,
        "id_cliente_crm" => $idClienteCrm,
        "id_almacen" => intval($datosVenta["id_almacen"]),
        "id_caja" => intval($datosVenta["id_caja"]),
        "id_turno_caja" => intval($datosVenta["id_turno_caja"]),
        "total_venta" => $totalVenta,
        "monto_saldo_crm" => $montoSaldoCrm,
        "monto_caja_estimado" => max(0, $montoCaja)
    ),
    "pago_saldo_crm" => array(
        "erp_ventas_pagos.id_movimiento_caja" => null,
        "erp_ventas_pagos.id_metodo_pago" => null,
        "erp_ventas_pagos.metodo_pago" => "saldo_crm",
        "erp_ventas_pagos.tipo_pago" => "saldo_cliente",
        "crm_movimiento.tipo" => "uso_saldo_pos",
        "crm_movimiento.naturaleza" => "cargo",
        "mueve_caja" => false
    ),
    "transaccion_real" => array(
        "bloquear_turno_pos" => true,
        "bloquear_cuenta_saldo_crm_for_update" => true,
        "crear_venta_detalle_kardex" => true,
        "crear_movimiento_crm_saldo" => true,
        "registrar_pago_pos_sin_caja_para_saldo" => true,
        "actualizar_monto_esperado_solo_con_pagos_de_caja" => true
    )
);

$salida = array(
    "ok" => empty(array_unique($bloqueos)),
    "modo" => "saldo_crm_apply_prep",
    "read_only" => true,
    "validacion_respaldo" => $validacionRespaldo,
    "cliente" => $cliente,
    "cuenta" => $cuenta,
    "saldo_disponible" => $saldoDisponible,
    "saldo_resultante_estimado" => redondear($saldoDisponible - $montoSaldoCrm),
    "prevalidacion" => array(
        "tipo" => valor($prevalidacion, array("tipo"), null),
        "mensaje" => valor($prevalidacion, array("mensaje"), null),
        "totales" => $totales
    ),
    "bloqueos" => array_values(array_unique($bloqueos)),
    "propuesta_apply_real" => $propuesta,
    "siguiente_autorizacion_requerida" => "AUTORIZO EJECUTAR VENTA POS UAT REAL CON SALDO CRM usando respaldo UAT POS vigente con token VENTAS_POS_SALDO_CRM_REAL id_usuario=1 id_cliente_crm=157 id_sku=1760 cantidad=1 precio=295 monto_saldo_crm=100 pago_caja=195",
    "contrato" => contrato(true)
);

if ($compacto) {
    $salida = array(
        "ok" => $salida["ok"],
        "modo" => $salida["modo"],
        "read_only" => true,
        "cliente" => $cliente ? valor($cliente, array("nombre_publico"), null) : null,
        "saldo_disponible" => $saldoDisponible,
        "monto_saldo_crm" => $montoSaldoCrm,
        "saldo_resultante_estimado" => redondear($saldoDisponible - $montoSaldoCrm),
        "total_venta" => $totalVenta,
        "monto_caja_estimado" => max(0, $montoCaja),
        "bloqueos" => $salida["bloqueos"],
        "siguiente_autorizacion_requerida" => $salida["siguiente_autorizacion_requerida"],
        "contrato" => contrato(true)
    );
}

responder($salida);

function validarTablas($tablas) {
    $bloqueos = array();
    foreach (array("crm_clientes_maestro", "crm_clientes_saldos_cuentas", "crm_clientes_saldos_movimientos", "erp_ventas", "erp_ventas_pagos", "erp_pos_turnos") as $tabla) {
        if (empty($tablas[$tabla])) {
            $bloqueos[] = "Falta tabla requerida: " . $tabla;
        }
    }
    return $bloqueos;
}

function validarClienteSaldo($cliente, $cuenta, $monto, $totalVenta) {
    $bloqueos = array();
    if (!$cliente) {
        $bloqueos[] = "Cliente CRM no encontrado.";
    } elseif ($cliente["estatus"] !== "activo") {
        $bloqueos[] = "Cliente CRM no activo.";
    }
    if (!$cuenta) {
        $bloqueos[] = "Cliente CRM sin cuenta de saldos MXN.";
    } elseif ($cuenta["estatus"] !== "activa") {
        $bloqueos[] = "Cuenta de saldos CRM no activa.";
    } elseif (redondear($cuenta["saldo_disponible"]) + 0.0001 < $monto) {
        $bloqueos[] = "Saldo CRM insuficiente.";
    }
    if ($monto <= 0) {
        $bloqueos[] = "Monto saldo CRM invalido.";
    }
    if ($totalVenta > 0 && $monto - $totalVenta > 0.0001) {
        $bloqueos[] = "El saldo CRM no puede generar cambio.";
    }
    return array_values(array_unique($bloqueos));
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

function contrato($preparado) {
    return array(
        "prepara_apply_real" => $preparado,
        "no_escribe_bd" => true,
        "no_crea_venta" => true,
        "no_descuenta_saldo_crm" => true,
        "no_mueve_caja" => true,
        "no_mueve_inventario" => true,
        "requiere_autorizacion_fuerte_para_ejecutar" => true
    );
}

function valor($datos, $ruta, $default = null) {
    $actual = $datos;
    foreach ($ruta as $segmento) {
        if (!is_array($actual) || !array_key_exists($segmento, $actual)) {
            return $default;
        }
        $actual = $actual[$segmento];
    }
    return $actual;
}

function redondear($valor) {
    return round(floatval(trim((string) $valor, "\"' ")), 6);
}

function responder($datos) {
    echo json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    exit(empty($datos["ok"]) ? 1 : 0);
}
