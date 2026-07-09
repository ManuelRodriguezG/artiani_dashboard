<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-06-26.
 * Proposito: ejecutar una venta POS UAT real solo despues de autorizacion explicita.
 * Impacto: crea folio ERP, pagos, movimientos de caja, kardex y trazabilidad detalle-inventario.
 * Contrato: bloqueado por defecto; requiere respaldo validado, usuario, asignacion, turno abierto y --autorizar=VENTAS_POS_VENTA_REAL.
 */

$args = isset($argv) ? $argv : array();
$autorizar = "";
$respaldo = "";
$idUsuario = 0;
$itemsArg = "";
$pagosArg = "";
$idSkuSimple = 0;
$cantidadSimple = 0;
$precioSimple = 0;
$pagoSimple = 0;
$idAtencion = 0;
$folioExcepcion = "";
$cliente = "Cliente mostrador UAT";
$observaciones = "UAT venta POS autorizada";

foreach ($args as $arg) {
    if (strpos($arg, "--autorizar=") === 0) {
        $autorizar = trim(substr($arg, 12), "\"' ");
    } elseif (strpos($arg, "--respaldo=") === 0) {
        $respaldo = trim(substr($arg, 11), "\"' ");
    } elseif (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--items=") === 0) {
        $itemsArg = trim(substr($arg, 8), "\"' ");
    } elseif (strpos($arg, "--pagos=") === 0) {
        $pagosArg = trim(substr($arg, 8), "\"' ");
    } elseif (strpos($arg, "--id_sku=") === 0) {
        $idSkuSimple = intval(trim(substr($arg, 9), "\"' "));
    } elseif (strpos($arg, "--cantidad=") === 0) {
        $cantidadSimple = floatval(trim(substr($arg, 11), "\"' "));
    } elseif (strpos($arg, "--precio=") === 0) {
        $precioSimple = floatval(trim(substr($arg, 9), "\"' "));
    } elseif (strpos($arg, "--pago=") === 0) {
        $pagoSimple = floatval(trim(substr($arg, 7), "\"' "));
    } elseif (strpos($arg, "--id_atencion=") === 0) {
        $idAtencion = intval(trim(substr($arg, 14), "\"' "));
    } elseif (strpos($arg, "--folio_excepcion=") === 0) {
        $folioExcepcion = trim(substr($arg, 18), "\"' ");
    } elseif (strpos($arg, "--cliente=") === 0) {
        $cliente = trim(substr($arg, 10), "\"' ");
    } elseif (strpos($arg, "--observaciones=") === 0) {
        $observaciones = trim(substr($arg, 16), "\"' ");
    }
}

if ($autorizar !== "VENTAS_POS_VENTA_REAL" || $idUsuario <= 0 || $respaldo === "" || !is_file($respaldo)) {
    responder(array(
        "ok" => false,
        "bloqueado" => true,
        "modo" => "guardrail",
        "mensaje" => "No se ejecuto venta real. Falta autorizacion explicita, respaldo valido o id_usuario.",
        "requisitos" => array(
            "--autorizar=VENTAS_POS_VENTA_REAL",
            "--respaldo=RUTA_RESPALDO_EXISTENTE",
            "--id_usuario=ID_OPERADOR_POS",
            "--items='[{\"id_sku\":1113,\"cantidad\":1,\"precio_unitario\":10,\"modo_salida\":\"existencia_agregada\"}]'",
            "--pagos='[{\"id_metodo_pago\":1,\"monto\":10,\"referencia\":\"UAT\"}]'"
        )
    ));
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";
require_once "../app/modelos/GarantiasErp.php";

class VentasErpVentaRealUat extends VentasErp {
    public function conexionUat() {
        return $this->getConexion();
    }
}

$ventas = new VentasErpVentaRealUat();
$garantias = new GarantiasErp();
$db = $ventas->conexionUat();
$asignacion = $ventas->asignacionActualTerminalPos(array("id_usuario" => $idUsuario));
$depurarAsignacion = valor($asignacion, array("depurar"), array());
$datosAsignacion = valor($depurarAsignacion, array("asignacion"), array());
$turno = valor($depurarAsignacion, array("turno_abierto"), array());

$atencionOrigen = null;
if ($idAtencion > 0) {
    $atencionOrigen = cargarAtencion($db, $idAtencion);
    if (!$atencionOrigen) {
        responder(array(
            "ok" => false,
            "bloqueado" => true,
            "modo" => "preflight",
            "mensaje" => "Atencion POS no encontrada.",
            "id_atencion" => $idAtencion
        ));
    }
    $itemsArg = json_encode($atencionOrigen["items"]);
    $pagoSimple = $pagoSimple > 0 ? $pagoSimple : floatval($atencionOrigen["atencion"]["total"]);
    $cliente = $atencionOrigen["atencion"]["cliente_nombre_publico"] ?: $cliente;
    $observaciones = "UAT cobro de atencion " . $atencionOrigen["atencion"]["folio_temporal"];
}

$items = $itemsArg !== "" ? $itemsArg : json_encode(array(array(
    "id_sku" => $idSkuSimple > 0 ? $idSkuSimple : 1113,
    "cantidad" => $cantidadSimple > 0 ? $cantidadSimple : 1,
    "precio_unitario" => $precioSimple > 0 ? $precioSimple : 10,
    "modo_salida" => "existencia_agregada"
)));
$pagos = $pagosArg !== "" ? $pagosArg : json_encode(array(array(
    "id_metodo_pago" => 1,
    "monto" => $pagoSimple > 0 ? $pagoSimple : ($precioSimple > 0 && $cantidadSimple > 0 ? $precioSimple * $cantidadSimple : 10),
    "referencia" => $idAtencion > 0 ? "ATN-" . $idAtencion : "UAT"
)));

$datosVenta = array(
    "id_almacen" => intval(valor($datosAsignacion, array("id_almacen"), 0)),
    "id_caja" => intval(valor($datosAsignacion, array("id_caja"), 0)),
    "id_turno_caja" => intval(valor($turno, array("id_turno_caja"), 0)),
    "canal" => "pos",
    "id_cliente" => $atencionOrigen ? intval(valor($atencionOrigen, array("atencion", "id_cliente"), 0)) : 0,
    "identificador_cliente" => $atencionOrigen ? (string) valor($atencionOrigen, array("atencion", "cliente_identificador_publico"), "") : "",
    "items" => $items,
    "pagos" => $pagos,
    "exigir_pago_completo" => 1
);

$consumoExcepcion = null;
if ($folioExcepcion !== "") {
    $consumoExcepcion = $ventas->excepcionComercialConsumoDryRun(array(
        "folio_excepcion" => $folioExcepcion,
        "id_usuario" => $idUsuario,
        "id_almacen" => $datosVenta["id_almacen"],
        "id_caja" => $datosVenta["id_caja"],
        "id_turno_caja" => $datosVenta["id_turno_caja"],
        "id_cliente" => $datosVenta["id_cliente"],
        "identificador_cliente" => $datosVenta["identificador_cliente"],
        "items" => $items,
        "pagos" => $pagos,
        "exigir_pago_completo" => 1
    ));
    $prevalidacion = valor($consumoExcepcion, array("depurar", "prevalidacion_base"), array());
    $confirmacion = array(
        "error" => false,
        "tipo" => valor($consumoExcepcion, array("tipo"), "warning"),
        "mensaje" => "Dry-run de venta con excepcion delegado a consumo comercial",
        "depurar" => array(
            "bloqueos" => valor($consumoExcepcion, array("depurar", "bloqueos"), array()),
            "contrato_confirmacion" => valor($consumoExcepcion, array("depurar", "contrato_consumo_real"), array())
        )
    );
} else {
    $prevalidacion = $ventas->prevalidarCarritoPos($datosVenta);
    $confirmacion = $ventas->confirmarVentaPosDryRun($datosVenta);
}
$clientePrecio = $ventas->clientePrecioDryRun(array(
    "id_almacen" => $datosVenta["id_almacen"],
    "canal" => "pos",
    "id_cliente" => $atencionOrigen ? intval(valor($atencionOrigen, array("atencion", "id_cliente"), 0)) : 0,
    "identificador_cliente" => $atencionOrigen ? (string) valor($atencionOrigen, array("atencion", "cliente_identificador_publico"), "") : "",
    "items" => $items
));
$bloqueos = array();
foreach (array(
    valor($asignacion, array("depurar", "bloqueos"), array()),
    valor($consumoExcepcion, array("depurar", "bloqueos"), array()),
    valor($prevalidacion, array("depurar", "bloqueos"), array()),
    valor($confirmacion, array("depurar", "bloqueos"), array()),
    valor($clientePrecio, array("depurar", "bloqueos"), array())
) as $listaBloqueos) {
    foreach ($listaBloqueos as $bloqueo) {
        $bloqueos[] = $bloqueo;
    }
}
if (!$asignacion || !empty($asignacion["error"]) || empty($depurarAsignacion["asignacion_activa"])) {
    $bloqueos[] = "No hay asignacion POS activa para el usuario";
}
if (empty($turno)) {
    $bloqueos[] = "No hay turno abierto para la caja asignada";
}
if (!empty(array_unique($bloqueos))) {
    responder(array(
        "ok" => false,
        "bloqueado" => true,
        "modo" => "preflight",
        "mensaje" => "No se ejecuto venta real; preflight con bloqueos.",
        "bloqueos" => array_values(array_unique($bloqueos)),
        "contexto" => array(
            "id_usuario" => $idUsuario,
            "id_almacen" => $datosVenta["id_almacen"],
            "id_caja" => $datosVenta["id_caja"],
            "id_turno_caja" => $datosVenta["id_turno_caja"]
        )
    ));
}

$partidas = $folioExcepcion !== ""
    ? valor($consumoExcepcion, array("depurar", "partidas"), array())
    : valor($prevalidacion, array("depurar", "partidas"), array());
$partidasPrecio = indexarPreciosPorRenglon(valor($clientePrecio, array("depurar", "partidas"), array()));
$pagosPrevalidados = valor($prevalidacion, array("depurar", "pagos"), array());
$totales = valor($prevalidacion, array("depurar", "totales"), array());
if ($folioExcepcion !== "") {
    $totalesExcepcion = valor($consumoExcepcion, array("depurar", "totales"), array());
    $totales = array(
        "subtotal" => valor($totalesExcepcion, array("subtotal_original"), 0),
        "descuento_total" => valor($totalesExcepcion, array("descuento_total"), 0),
        "total_estimado" => valor($totalesExcepcion, array("total_con_excepcion"), 0),
        "pagado_total" => valor($totalesExcepcion, array("pagado_total"), 0),
        "saldo_total" => valor($totalesExcepcion, array("saldo_total"), 0),
        "cambio" => valor($totalesExcepcion, array("cambio"), 0)
    );
}

try {
    $db->beginTransaction();

    if ($idAtencion > 0) {
        $atencionBloqueada = bloquearAtencion($db, $idAtencion);
        if (!$atencionBloqueada || !in_array($atencionBloqueada["estatus"], array("abierta", "lista_para_cobro", "tomada_por_caja"), true)) {
            throw new Exception("La atencion ya no esta disponible para cobro");
        }
    }

    $turnoBloqueado = bloquearTurno($db, $datosVenta["id_turno_caja"], $datosVenta["id_caja"], $datosVenta["id_almacen"]);
    if (!$turnoBloqueado) {
        throw new Exception("El turno ya no esta abierto para la caja asignada");
    }

    $excepcionBloqueada = null;
    if ($folioExcepcion !== "") {
        $excepcionBloqueada = bloquearExcepcionComercial($db, $folioExcepcion);
        if (!$excepcionBloqueada) {
            throw new Exception("Excepcion comercial no encontrada al confirmar venta POS");
        }
        if ($excepcionBloqueada["estatus"] !== "autorizada" || intval($excepcionBloqueada["id_venta"]) > 0 || intval($excepcionBloqueada["id_venta_detalle"]) > 0) {
            throw new Exception("La excepcion comercial ya no esta disponible para consumo");
        }
    }

    $idClienteVenta = intval($datosVenta["id_cliente"]);
    $clienteVenta = $cliente;
    $identificadorClienteVenta = $datosVenta["identificador_cliente"];
    $idClienteCrmVenta = 0;
    $clienteCodigoSnapshot = "";
    $clienteOrigenSnapshot = "";
    $clienteSnapshot = null;
    if ($excepcionBloqueada && intval(valor($excepcionBloqueada, array("id_cliente"), 0)) > 0) {
        $clienteExcepcion = consultarClienteErp($db, intval($excepcionBloqueada["id_cliente"]));
        $idClienteVenta = intval($excepcionBloqueada["id_cliente"]);
        if ($clienteExcepcion) {
            $clienteVenta = valor($clienteExcepcion, array("nombre_publico"), $clienteVenta);
            $identificadorClienteVenta = valor($clienteExcepcion, array("identificador"), $identificadorClienteVenta);
        }
    }
    if ($excepcionBloqueada && intval(valor($excepcionBloqueada, array("id_cliente_crm"), 0)) > 0) {
        $idClienteCrmVenta = intval($excepcionBloqueada["id_cliente_crm"]);
        $clienteCodigoSnapshot = trim((string) valor($excepcionBloqueada, array("cliente_codigo_snapshot"), ""));
        $clienteVenta = trim((string) valor($excepcionBloqueada, array("cliente_nombre_snapshot"), $clienteVenta));
        $identificadorClienteVenta = trim((string) valor($excepcionBloqueada, array("cliente_identificador_snapshot"), $identificadorClienteVenta));
        $clienteOrigenSnapshot = trim((string) valor($excepcionBloqueada, array("cliente_origen_snapshot"), "crm"));
        $clienteSnapshot = json_encode(array(
            "id_cliente_crm" => $idClienteCrmVenta,
            "codigo_cliente" => $clienteCodigoSnapshot,
            "nombre_publico" => $clienteVenta,
            "identificador" => $identificadorClienteVenta,
            "origen_cliente" => $clienteOrigenSnapshot
        ), JSON_UNESCAPED_UNICODE);
    }
    $columnasClienteCrm = "";
    $valoresClienteCrm = "";
    $paramsClienteCrm = array();
    if (columnaExiste($db, "erp_ventas", "id_cliente_crm")) {
        $columnasClienteCrm = ", id_cliente_crm, cliente_codigo_snapshot, cliente_origen_snapshot, cliente_snapshot";
        $valoresClienteCrm = ", :id_cliente_crm, :cliente_codigo_snapshot, :cliente_origen_snapshot, :cliente_snapshot";
        $paramsClienteCrm = array(
            ":id_cliente_crm" => $idClienteCrmVenta > 0 ? $idClienteCrmVenta : null,
            ":cliente_codigo_snapshot" => $clienteCodigoSnapshot !== "" ? $clienteCodigoSnapshot : null,
            ":cliente_origen_snapshot" => $clienteOrigenSnapshot !== "" ? $clienteOrigenSnapshot : null,
            ":cliente_snapshot" => $clienteSnapshot
        );
    }

    $folio = generarFolioVenta($db, "POS");
    $subtotal = redondear(valor($totales, array("subtotal"), 0));
    $descuentoTotal = redondear(valor($totales, array("descuento_total"), 0));
    $total = redondear(valor($totales, array("total_estimado"), $subtotal));
    $pagadoTotal = redondear(min($total, valor($totales, array("pagado_total"), 0)));
    $saldoTotal = redondear(max(0, $total - $pagadoTotal));
    $estatus = $saldoTotal <= 0.0001 ? "pagada" : "pendiente_pago";

    $stmt = $db->prepare("INSERT INTO erp_ventas
        (folio, canal, tipo_documento, estatus, id_almacen, id_caja, id_turno_caja,
         id_cliente$columnasClienteCrm, cliente_nombre_publico, cliente_identificador_publico,
         subtotal, descuento_total, impuestos_total, total,
         pagado_total, saldo_total, creado_por, observaciones, descuento_motivo,
         autorizado_comercial_por, fecha_autorizacion_comercial)
        VALUES (:folio, 'pos', 'venta', :estatus, :almacen, :caja, :turno,
         :id_cliente$valoresClienteCrm, :cliente, :identificador_cliente, :subtotal, :descuento_total, 0, :total, :pagado, :saldo, :usuario, :observaciones,
         :descuento_motivo, :autorizado_comercial_por, :fecha_autorizacion_comercial)");
    $paramsVenta = array(
        ":folio" => $folio,
        ":estatus" => $estatus,
        ":almacen" => $datosVenta["id_almacen"],
        ":caja" => $datosVenta["id_caja"],
        ":turno" => $datosVenta["id_turno_caja"],
        ":id_cliente" => $idClienteVenta > 0 ? $idClienteVenta : null,
        ":cliente" => $clienteVenta,
        ":identificador_cliente" => $identificadorClienteVenta !== "" ? $identificadorClienteVenta : null,
        ":subtotal" => $subtotal,
        ":descuento_total" => $descuentoTotal,
        ":total" => $total,
        ":pagado" => $pagadoTotal,
        ":saldo" => $saldoTotal,
        ":usuario" => $idUsuario,
        ":observaciones" => $observaciones,
        ":descuento_motivo" => $excepcionBloqueada ? valor($excepcionBloqueada, array("motivo"), null) : null,
        ":autorizado_comercial_por" => $excepcionBloqueada ? intval(valor($excepcionBloqueada, array("autorizado_por"), 0)) : null,
        ":fecha_autorizacion_comercial" => $excepcionBloqueada ? valor($excepcionBloqueada, array("fecha_autorizacion"), null) : null
    );
    $stmt->execute(array_merge($paramsVenta, $paramsClienteCrm));
    $idVenta = intval($db->lastInsertId());

    $evidenciaInventario = array();
    $detallesGarantia = array();
    foreach ($partidas as $partida) {
        $sku = consultarSku($db, intval($partida["id_sku"]));
        if (!$sku) {
            throw new Exception("SKU no encontrado durante venta real: " . intval($partida["id_sku"]));
        }
        $stmt = $db->prepare("INSERT INTO erp_ventas_detalle
            (id_venta, renglon, id_producto_erp, id_sku_erp, sku, descripcion,
             tipo_partida, controla_inventario, modo_salida, cantidad_venta,
             unidad_venta, cantidad_base, unidad_base, precio_unitario,
             precio_unitario_sin_impuesto, precio_base, precio_aplicado, id_lista_precio,
             lista_precio_snapshot, regla_precio_origen, descuento, impuestos, subtotal, total, estatus,
             id_excepcion_comercial, tipo_excepcion_comercial, motivo_excepcion_comercial,
             autorizado_comercial_por, fecha_autorizacion_comercial)
            VALUES (:venta, :renglon, :producto, :sku_id, :sku, :descripcion,
             'producto', :controla, :modo, :cantidad, :unidad_venta, :cantidad_base,
             :unidad_base, :precio, :precio, :precio_base, :precio_aplicado, :lista_id,
             :lista_snapshot, :regla_precio, :descuento, 0, :subtotal, :total, 'confirmada',
             :id_excepcion, :tipo_excepcion, :motivo_excepcion, :autorizado_comercial_por,
             :fecha_autorizacion_comercial)");
        $modoSalida = valor($partida, array("plan_salida_inventario", "modo"), "existencia_agregada");
        $precioPartida = isset($partidasPrecio[intval($partida["renglon"])]) ? $partidasPrecio[intval($partida["renglon"])] : array();
        $aplicaExcepcion = !empty($partida["aplica_excepcion_comercial"]);
        $precioOriginal = redondear(valor($partida, array("precio_unitario_original"), valor($partida, array("precio_unitario"), 0)));
        $precioFinal = redondear(valor($partida, array("precio_unitario_final"), valor($partida, array("precio_unitario"), 0)));
        $descuentoPartida = redondear(valor($partida, array("descuento_excepcion"), 0));
        $subtotalPartida = redondear(valor($partida, array("subtotal_original"), valor($partida, array("subtotal"), 0)));
        $totalPartida = redondear(valor($partida, array("total_final"), valor($partida, array("subtotal"), 0)));
        $stmt->execute(array(
            ":venta" => $idVenta,
            ":renglon" => intval($partida["renglon"]),
            ":producto" => intval($sku["id_producto_erp"]),
            ":sku_id" => intval($sku["id_sku"]),
            ":sku" => $sku["sku"],
            ":descripcion" => $partida["descripcion"],
            ":controla" => intval($partida["controla_inventario"]),
            ":modo" => $modoSalida,
            ":cantidad" => redondear($partida["cantidad"]),
            ":unidad_venta" => $sku["unidad_venta_label"],
            ":cantidad_base" => redondear($partida["cantidad"]),
            ":unidad_base" => $sku["unidad_venta_label"],
            ":precio" => $precioFinal,
            ":precio_base" => redondear(valor($precioPartida, array("precio_base"), $precioOriginal)),
            ":precio_aplicado" => $precioFinal,
            ":lista_id" => valor($precioPartida, array("id_lista_precio"), null),
            ":lista_snapshot" => valor($precioPartida, array("lista_precio_snapshot"), null),
            ":regla_precio" => valor($precioPartida, array("regla_precio_origen"), "catalogo_general"),
            ":descuento" => $descuentoPartida,
            ":subtotal" => $subtotalPartida,
            ":total" => $totalPartida,
            ":id_excepcion" => $aplicaExcepcion ? intval($partida["id_excepcion_comercial"]) : null,
            ":tipo_excepcion" => $aplicaExcepcion ? $partida["tipo_excepcion_comercial"] : null,
            ":motivo_excepcion" => $aplicaExcepcion && $excepcionBloqueada ? valor($excepcionBloqueada, array("motivo"), null) : null,
            ":autorizado_comercial_por" => $aplicaExcepcion && $excepcionBloqueada ? intval(valor($excepcionBloqueada, array("autorizado_por"), 0)) : null,
            ":fecha_autorizacion_comercial" => $aplicaExcepcion && $excepcionBloqueada ? valor($excepcionBloqueada, array("fecha_autorizacion"), null) : null
        ));
        $idDetalle = intval($db->lastInsertId());
        if ($aplicaExcepcion && $excepcionBloqueada) {
            $db->prepare("UPDATE erp_ventas_excepciones_comerciales
                SET id_venta=:venta, id_venta_detalle=:detalle, estatus='aplicada',
                    aplicado_por=:usuario, fecha_aplicacion=NOW(), fecha_actualizacion=NOW()
                WHERE id_excepcion_comercial=:excepcion AND estatus='autorizada'")
                ->execute(array(
                    ":venta" => $idVenta,
                    ":detalle" => $idDetalle,
                    ":usuario" => $idUsuario,
                    ":excepcion" => intval($excepcionBloqueada["id_excepcion_comercial"])
                ));
        }
        $detallesGarantia[] = array(
            "id_venta_detalle" => $idDetalle,
            "id_producto_erp" => intval($sku["id_producto_erp"]),
            "id_sku_erp" => intval($sku["id_sku"])
        );

        if (intval($partida["controla_inventario"]) === 1) {
            foreach (valor($partida, array("plan_salida_inventario", "asignaciones"), array()) as $asignacionInv) {
                $evidenciaInventario[] = aplicarSalidaInventario($db, $idVenta, $idDetalle, $folio, $sku, $asignacionInv, $datosVenta["id_almacen"], $idUsuario);
            }
        }
    }

    $snapshotsGarantia = $garantias->guardarSnapshotsVenta($db, array(
        "id_venta" => $idVenta,
        "id_almacen" => $datosVenta["id_almacen"],
        "canal" => "pos",
        "fecha" => date("Y-m-d"),
        "detalles" => $detallesGarantia
    ));
    if (!empty($snapshotsGarantia["error"])) {
        throw new Exception("No se pudo guardar snapshot de garantia: " . $snapshotsGarantia["mensaje"]);
    }
    $bloqueosGarantia = valor($snapshotsGarantia, array("depurar", "bloqueos"), array());
    if (!empty($bloqueosGarantia)) {
        throw new Exception("Snapshot de garantia bloqueado: " . implode("; ", $bloqueosGarantia));
    }

    $evidenciaPagos = registrarPagos($db, $idVenta, $folio, $datosVenta, $pagosPrevalidados, $total, $idUsuario);
    if ($idAtencion > 0) {
        $db->prepare("UPDATE erp_pos_atenciones
            SET estatus='convertida', fecha_conversion=CURRENT_TIMESTAMP, id_venta_convertida=:venta, fecha_actualizacion=CURRENT_TIMESTAMP
            WHERE id_atencion_pos=:atencion")
            ->execute(array(":venta" => $idVenta, ":atencion" => $idAtencion));
    }
    $montoCaja = 0;
    foreach ($evidenciaPagos as $pago) {
        $montoCaja += floatval($pago["monto_aplicado"]);
    }
    $db->prepare("UPDATE erp_pos_turnos
        SET monto_esperado=ROUND(monto_esperado+:monto, 6)
        WHERE id_turno_caja=:turno")
        ->execute(array(":monto" => redondear($montoCaja), ":turno" => $datosVenta["id_turno_caja"]));

    $db->commit();
    responder(array(
        "ok" => true,
        "modo" => "venta_real_uat",
        "folio" => $folio,
        "id_venta" => $idVenta,
        "id_atencion_convertida" => $idAtencion,
        "estatus" => $estatus,
        "cliente" => array(
            "id_cliente" => $idClienteVenta > 0 ? $idClienteVenta : null,
            "id_cliente_crm" => $idClienteCrmVenta > 0 ? $idClienteCrmVenta : null,
            "nombre_publico" => $clienteVenta,
            "identificador" => $identificadorClienteVenta,
            "origen" => $clienteOrigenSnapshot
        ),
        "totales" => array(
            "subtotal" => $subtotal,
            "total" => $total,
            "pagado_total" => $pagadoTotal,
            "saldo_total" => $saldoTotal
        ),
        "inventario" => $evidenciaInventario,
        "garantias" => valor($snapshotsGarantia, array("depurar", "guardados"), array()),
        "pagos" => $evidenciaPagos,
        "excepcion_comercial" => $excepcionBloqueada ? array(
            "folio" => $folioExcepcion,
            "id_excepcion_comercial" => intval($excepcionBloqueada["id_excepcion_comercial"]),
            "estatus" => "aplicada",
            "descuento_total" => $descuentoTotal
        ) : null,
        "siguiente_paso" => "Registrar evidencia en docs/erp_ventas_pos_evidencia_uat.md y validar kardex/existencias por SKU."
    ));
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    responder(array(
        "ok" => false,
        "bloqueado" => false,
        "modo" => "rollback",
        "mensaje" => $e->getMessage()
    ));
}

function bloquearTurno($db, $idTurno, $idCaja, $idAlmacen) {
    $stmt = $db->prepare("SELECT * FROM erp_pos_turnos
        WHERE id_turno_caja=:turno AND id_caja=:caja AND id_almacen=:almacen AND estatus='abierto'
        FOR UPDATE");
    $stmt->execute(array(":turno" => $idTurno, ":caja" => $idCaja, ":almacen" => $idAlmacen));
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function bloquearAtencion($db, $idAtencion) {
    $stmt = $db->prepare("SELECT * FROM erp_pos_atenciones WHERE id_atencion_pos=:atencion FOR UPDATE");
    $stmt->execute(array(":atencion" => intval($idAtencion)));
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function bloquearExcepcionComercial($db, $folio) {
    $stmt = $db->prepare("SELECT *
        FROM erp_ventas_excepciones_comerciales
        WHERE folio=:folio
        LIMIT 1
        FOR UPDATE");
    $stmt->execute(array(":folio" => $folio));
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function cargarAtencion($db, $idAtencion) {
    $stmt = $db->prepare("SELECT * FROM erp_pos_atenciones WHERE id_atencion_pos=:atencion LIMIT 1");
    $stmt->execute(array(":atencion" => intval($idAtencion)));
    $atencion = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$atencion) {
        return null;
    }
    $stmt = $db->prepare("SELECT * FROM erp_pos_atenciones_detalle
        WHERE id_atencion_pos=:atencion AND estatus='activa'
        ORDER BY renglon ASC");
    $stmt->execute(array(":atencion" => intval($idAtencion)));
    $items = array();
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $detalle) {
        $items[] = array(
            "id_sku" => intval($detalle["id_sku_erp"]),
            "cantidad" => floatval($detalle["cantidad_venta"]),
            "precio_unitario" => floatval($detalle["precio_unitario"]),
            "modo_salida" => $detalle["modo_salida"] !== "" ? $detalle["modo_salida"] : "existencia_agregada"
        );
    }
    return array("atencion" => $atencion, "items" => $items);
}

function consultarClienteErp($db, $idCliente) {
    $stmt = $db->prepare("SELECT c.id_cliente, c.codigo_cliente, c.nombre_publico,
            i.valor identificador
        FROM erp_clientes c
        LEFT JOIN erp_clientes_identificadores i ON i.id_cliente=c.id_cliente AND i.estatus='activo'
        WHERE c.id_cliente=:cliente
        ORDER BY i.principal DESC, i.id_cliente_identificador ASC
        LIMIT 1");
    $stmt->execute(array(":cliente" => intval($idCliente)));
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
    return $cliente ?: null;
}

function columnaExiste($db, $tabla, $columna) {
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $tabla) || !preg_match('/^[a-zA-Z0-9_]+$/', $columna)) {
        return false;
    }
    $stmt = $db->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=:base AND TABLE_NAME=:tabla AND COLUMN_NAME=:columna LIMIT 1");
    $stmt->execute(array(":base" => MYSQLBASE, ":tabla" => $tabla, ":columna" => $columna));
    return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
}

function indexarPreciosPorRenglon($partidas) {
    $indexadas = array();
    foreach ($partidas as $partida) {
        if (isset($partida["renglon"])) {
            $indexadas[intval($partida["renglon"])] = $partida;
        }
    }
    return $indexadas;
}

function generarFolioVenta($db, $prefijo) {
    $base = $prefijo . "-" . date("Ymd") . "-";
    $stmt = $db->prepare("SELECT COUNT(*) FROM erp_ventas WHERE folio LIKE :folio");
    $stmt->execute(array(":folio" => $base . "%"));
    return $base . str_pad((string) (intval($stmt->fetchColumn()) + 1), 6, "0", STR_PAD_LEFT);
}

function consultarSku($db, $idSku) {
    $stmt = $db->prepare("SELECT s.id_sku, s.id_producto_erp, s.sku,
            COALESCE(s.nombre, p.nombre) nombre_sku,
            COALESCE(r.controla_inventario, CASE WHEN s.tipo_inventario IN ('servicio','cargo') THEN 0 ELSE 1 END) controla_inventario,
            COALESCE(NULLIF(r.unidad_venta_label, ''), ub.abreviatura, ub.codigo, '') unidad_venta_label
        FROM erp_catalogo_skus s
        INNER JOIN erp_catalogo_productos p ON p.id_producto_erp=s.id_producto_erp
        LEFT JOIN erp_catalogo_sku_reglas_inventario r ON r.id_sku=s.id_sku
        LEFT JOIN erp_catalogo_unidades ub ON ub.id_unidad=s.id_unidad_base
        WHERE s.id_sku=:sku AND s.estatus='activo' AND p.estatus='activo'
        LIMIT 1");
    $stmt->execute(array(":sku" => $idSku));
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function aplicarSalidaInventario($db, $idVenta, $idDetalle, $folio, $sku, $asignacionInv, $idAlmacen, $idUsuario) {
    $idExistencia = intval(valor($asignacionInv, array("id_existencia_inventario"), 0));
    $idUnidad = intval(valor($asignacionInv, array("id_inventario_unidad"), 0));
    $cantidad = redondear(valor($asignacionInv, array("cantidad_base"), 0));
    if ($idExistencia <= 0 || $cantidad <= 0) {
        throw new Exception("Asignacion de inventario invalida para venta POS");
    }

    $stmt = $db->prepare("SELECT * FROM erp_inventario_existencias
        WHERE id_existencia_inventario=:existencia AND id_almacen_clave=:almacen
        FOR UPDATE");
    $stmt->execute(array(":existencia" => $idExistencia, ":almacen" => $idAlmacen));
    $existencia = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$existencia) {
        throw new Exception("Existencia no encontrada o fuera de almacen POS");
    }
    if (floatval($existencia["cantidad_disponible"]) + 0.0001 < $cantidad) {
        throw new Exception("Existencia insuficiente al confirmar venta POS");
    }

    $cantidadAnterior = redondear($existencia["cantidad"]);
    $cantidadDisponibleAnterior = redondear($existencia["cantidad_disponible"]);
    $cantidadNueva = redondear($cantidadAnterior - $cantidad);
    $cantidadDisponibleNueva = redondear($cantidadDisponibleAnterior - $cantidad);
    $estatusExistencia = $cantidadNueva > 0.0001 ? "disponible" : "agotada";

    $db->prepare("UPDATE erp_inventario_existencias
        SET cantidad=:cantidad, cantidad_disponible=:disponible,
            estatus_existencia=:estatus, fecha_actualizacion=NOW()
        WHERE id_existencia_inventario=:existencia")
        ->execute(array(
            ":cantidad" => $cantidadNueva,
            ":disponible" => $cantidadDisponibleNueva,
            ":estatus" => $estatusExistencia,
            ":existencia" => $idExistencia
        ));

    $unidadAntes = 0;
    $unidadDespues = 0;
    $estadoUnidadDespues = null;
    if ($idUnidad > 0) {
        $stmt = $db->prepare("SELECT * FROM erp_inventario_unidades
            WHERE id_inventario_unidad=:unidad AND id_existencia_inventario=:existencia AND id_almacen=:almacen
            FOR UPDATE");
        $stmt->execute(array(":unidad" => $idUnidad, ":existencia" => $idExistencia, ":almacen" => $idAlmacen));
        $unidad = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$unidad || $unidad["estatus"] !== "disponible") {
            throw new Exception("Unidad fisica no disponible al confirmar venta POS");
        }
        $unidadAntes = redondear($unidad["cantidad_base_disponible"]);
        if ($unidadAntes + 0.0001 < $cantidad) {
            throw new Exception("Contenido insuficiente en unidad fisica al confirmar venta POS");
        }
        $unidadDespues = redondear($unidadAntes - $cantidad);
        if ($unidad["estado_fisico"] === "cerrada" && abs($unidadAntes - $cantidad) > 0.0001) {
            throw new Exception("La unidad cerrada debe venderse completa");
        }
        if ($unidadDespues <= 0.0001) {
            $estadoUnidadDespues = $unidad["estado_fisico"] === "cerrada" ? "vendida" : "agotada";
            $estatusUnidad = $estadoUnidadDespues;
        } else {
            $estadoUnidadDespues = "abierta";
            $estatusUnidad = "disponible";
        }
        $db->prepare("UPDATE erp_inventario_unidades
            SET cantidad_base_disponible=:disponible,
                estado_fisico=:estado,
                estatus=:estatus,
                fecha_actualizacion=NOW()
            WHERE id_inventario_unidad=:unidad")
            ->execute(array(
                ":disponible" => $unidadDespues,
                ":estado" => $estadoUnidadDespues,
                ":estatus" => $estatusUnidad,
                ":unidad" => $idUnidad
            ));
    }

    $costo = redondear(valor($existencia, array("costo_promedio"), 0));
    $stmt = $db->prepare("INSERT INTO erp_inventario_movimientos
        (id_producto, id_sku_erp, id_almacen, tipo_movimiento, origen_tipo, origen_id,
         id_existencia_inventario, codigo_existencia, lote, fecha_caducidad, ubicacion_id,
         ubicacion, cantidad, costo_unitario, costo_total, existencia_anterior,
         existencia_nueva, referencia, observaciones)
        VALUES (:producto, :sku, :almacen, 'salida', 'venta_pos', :venta,
         :existencia, :codigo, :lote, :caducidad, :ubicacion_id, :ubicacion,
         :cantidad, :costo, :costo_total, :anterior, :nueva, :referencia, :observaciones)");
    $stmt->execute(array(
        ":producto" => intval($sku["id_producto_erp"]),
        ":sku" => intval($sku["id_sku"]),
        ":almacen" => $idAlmacen,
        ":venta" => $idVenta,
        ":existencia" => $idExistencia,
        ":codigo" => valor($existencia, array("codigo_existencia"), null),
        ":lote" => valor($existencia, array("lote"), null),
        ":caducidad" => valor($existencia, array("fecha_caducidad"), null),
        ":ubicacion_id" => valor($existencia, array("ubicacion_id"), null),
        ":ubicacion" => valor($existencia, array("ubicacion"), null),
        ":cantidad" => $cantidad,
        ":costo" => $costo,
        ":costo_total" => redondear($cantidad * $costo),
        ":anterior" => $cantidadAnterior,
        ":nueva" => $cantidadNueva,
        ":referencia" => $folio,
        ":observaciones" => "venta_pos:" . $folio . " | usuario:" . intval($idUsuario)
    ));
    $idMovimiento = intval($db->lastInsertId());
    $db->prepare("UPDATE erp_inventario_existencias
        SET ultimo_movimiento_id=:movimiento
        WHERE id_existencia_inventario=:existencia")
        ->execute(array(":movimiento" => $idMovimiento, ":existencia" => $idExistencia));

    $db->prepare("INSERT INTO erp_ventas_detalle_inventario
        (id_venta, id_venta_detalle, id_existencia_inventario, id_inventario_unidad,
         id_movimiento_inventario, id_almacen, lote, fecha_caducidad, ubicacion_id,
         cantidad_base, cantidad_unidad_antes, cantidad_unidad_despues,
         estado_unidad_despues, estatus)
        VALUES (:venta, :detalle, :existencia, :unidad, :movimiento, :almacen,
         :lote, :caducidad, :ubicacion_id, :cantidad, :unidad_antes,
         :unidad_despues, :estado_unidad, 'confirmada')")
        ->execute(array(
            ":venta" => $idVenta,
            ":detalle" => $idDetalle,
            ":existencia" => $idExistencia,
            ":unidad" => $idUnidad > 0 ? $idUnidad : null,
            ":movimiento" => $idMovimiento,
            ":almacen" => $idAlmacen,
            ":lote" => valor($existencia, array("lote"), null),
            ":caducidad" => valor($existencia, array("fecha_caducidad"), null),
            ":ubicacion_id" => valor($existencia, array("ubicacion_id"), null),
            ":cantidad" => $cantidad,
            ":unidad_antes" => $unidadAntes,
            ":unidad_despues" => $unidadDespues,
            ":estado_unidad" => $estadoUnidadDespues
        ));

    return array(
        "id_existencia_inventario" => $idExistencia,
        "id_inventario_unidad" => $idUnidad ?: null,
        "id_movimiento_inventario" => $idMovimiento,
        "cantidad_base" => $cantidad,
        "existencia_anterior" => $cantidadAnterior,
        "existencia_nueva" => $cantidadNueva,
        "unidad_antes" => $unidadAntes,
        "unidad_despues" => $unidadDespues,
        "estado_unidad_despues" => $estadoUnidadDespues
    );
}

function registrarPagos($db, $idVenta, $folio, $datosVenta, $pagos, $total, $idUsuario) {
    $pendiente = redondear($total);
    $evidencia = array();
    foreach ($pagos as $pago) {
        if ($pendiente <= 0.0001) {
            break;
        }
        $monto = redondear(min($pendiente, valor($pago, array("monto"), 0)));
        if ($monto <= 0) {
            continue;
        }
        if (columnaExiste($db, "erp_pos_movimientos_caja", "id_caja")) {
            $stmt = $db->prepare("INSERT INTO erp_pos_movimientos_caja
                (id_turno_caja, id_caja, id_almacen, tipo, categoria, motivo, monto, estatus,
                 referencia, requiere_autorizacion, requiere_evidencia, observaciones, creado_por, fecha_registro, fecha_actualizacion)
                VALUES (:turno, :caja, :almacen, 'ingreso', 'venta_pos', 'venta_pos', :monto, 'registrado',
                 :referencia, 0, 0, :observaciones, :usuario, NOW(), NOW())");
            $stmt->execute(array(
                ":turno" => $datosVenta["id_turno_caja"],
                ":caja" => $datosVenta["id_caja"],
                ":almacen" => $datosVenta["id_almacen"],
                ":monto" => $monto,
                ":referencia" => $folio,
                ":observaciones" => "Pago venta POS " . $folio,
                ":usuario" => $idUsuario
            ));
        } else {
            $stmt = $db->prepare("INSERT INTO erp_pos_movimientos_caja
                (id_turno_caja, tipo, motivo, monto, referencia, observaciones, creado_por)
                VALUES (:turno, 'ingreso', 'venta_pos', :monto, :referencia, :observaciones, :usuario)");
            $stmt->execute(array(
                ":turno" => $datosVenta["id_turno_caja"],
                ":monto" => $monto,
                ":referencia" => $folio,
                ":observaciones" => "Pago venta POS " . $folio,
                ":usuario" => $idUsuario
            ));
        }
        $idMovimientoCaja = intval($db->lastInsertId());

        $stmt = $db->prepare("INSERT INTO erp_ventas_pagos
            (id_venta, id_caja, id_turno_caja, id_movimiento_caja, id_metodo_pago,
             metodo_pago, monto, moneda, referencia, estatus, creado_por)
            VALUES (:venta, :caja, :turno, :movimiento, :metodo_id,
             :metodo, :monto, 'MXN', :referencia, 'registrado', :usuario)");
        $stmt->execute(array(
            ":venta" => $idVenta,
            ":caja" => $datosVenta["id_caja"],
            ":turno" => $datosVenta["id_turno_caja"],
            ":movimiento" => $idMovimientoCaja,
            ":metodo_id" => intval(valor($pago, array("id_metodo_pago"), 0)),
            ":metodo" => valor($pago, array("metodo_pago"), ""),
            ":monto" => $monto,
            ":referencia" => valor($pago, array("referencia"), null),
            ":usuario" => $idUsuario
        ));
        $evidencia[] = array(
            "id_venta_pago" => intval($db->lastInsertId()),
            "id_movimiento_caja" => $idMovimientoCaja,
            "metodo_pago" => valor($pago, array("metodo_pago"), ""),
            "monto_aplicado" => $monto
        );
        $pendiente = redondear($pendiente - $monto);
    }
    if ($pendiente > 0.0001) {
        throw new Exception("Pagos insuficientes durante registro transaccional");
    }
    return $evidencia;
}

function redondear($valor) {
    return round(floatval($valor), 6);
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

function responder($datos) {
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}
