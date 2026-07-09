<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-06-26.
 * Proposito: validar si POS esta listo para una venta real sin escribir BD.
 * Impacto: confirma asignacion, turno, pagos, plan de salida inventario y contrato de kardex.
 * Contrato: read-only; no crea venta, no cobra, no descuenta inventario y no consume reservas.
 */

$args = isset($argv) ? $argv : array();
$idUsuario = 0;
$itemsArg = "";
$pagosArg = "";
$idSkuSimple = 0;
$cantidadSimple = 0;
$precioSimple = 0;
$pagoSimple = 0;
$respaldo = "C:\\xampp\\htdocs\\panel\\artianilocal_respaldo_completo_20260625_post_repair.sql";
$cliente = "Cliente mostrador UAT";
foreach ($args as $arg) {
    if (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    }
    if (strpos($arg, "--items=") === 0) {
        $itemsArg = trim(substr($arg, 8), "\"' ");
    }
    if (strpos($arg, "--pagos=") === 0) {
        $pagosArg = trim(substr($arg, 8), "\"' ");
    }
    if (strpos($arg, "--id_sku=") === 0) {
        $idSkuSimple = intval(trim(substr($arg, 9), "\"' "));
    }
    if (strpos($arg, "--cantidad=") === 0) {
        $cantidadSimple = floatval(trim(substr($arg, 11), "\"' "));
    }
    if (strpos($arg, "--precio=") === 0) {
        $precioSimple = floatval(trim(substr($arg, 9), "\"' "));
    }
    if (strpos($arg, "--pago=") === 0) {
        $pagoSimple = floatval(trim(substr($arg, 7), "\"' "));
    }
    if (strpos($arg, "--respaldo=") === 0) {
        $respaldo = trim(substr($arg, 11), "\"' ");
    }
    if (strpos($arg, "--cliente=") === 0) {
        $cliente = trim(substr($arg, 10), "\"' ");
    }
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";

$ventas = new VentasErp();
$asignacion = $ventas->asignacionActualTerminalPos(array("id_usuario" => $idUsuario));
$depurarAsignacion = isset($asignacion["depurar"]) && is_array($asignacion["depurar"]) ? $asignacion["depurar"] : array();
$asignacionActiva = !empty($depurarAsignacion["asignacion_activa"]);
$datosAsignacion = $asignacionActiva && isset($depurarAsignacion["asignacion"]) ? $depurarAsignacion["asignacion"] : array();
$turno = isset($depurarAsignacion["turno_abierto"]) && is_array($depurarAsignacion["turno_abierto"]) ? $depurarAsignacion["turno_abierto"] : array();

$items = $itemsArg !== "" ? $itemsArg : json_encode(array(array(
    "id_sku" => $idSkuSimple > 0 ? $idSkuSimple : 1113,
    "cantidad" => $cantidadSimple > 0 ? $cantidadSimple : 1,
    "precio_unitario" => $precioSimple > 0 ? $precioSimple : 10,
    "modo_salida" => "existencia_agregada"
)));
$pagos = $pagosArg !== "" ? $pagosArg : json_encode(array(array(
    "id_metodo_pago" => 1,
    "monto" => $pagoSimple > 0 ? $pagoSimple : ($precioSimple > 0 && $cantidadSimple > 0 ? $precioSimple * $cantidadSimple : 10),
    "referencia" => "UAT"
)));

$datosVenta = array(
    "id_almacen" => isset($datosAsignacion["id_almacen"]) ? intval($datosAsignacion["id_almacen"]) : 0,
    "id_caja" => isset($datosAsignacion["id_caja"]) ? intval($datosAsignacion["id_caja"]) : 0,
    "id_turno_caja" => isset($turno["id_turno_caja"]) ? intval($turno["id_turno_caja"]) : 0,
    "items" => $items,
    "pagos" => $pagos,
    "exigir_pago_completo" => 1
);
$prevalidacion = $ventas->prevalidarCarritoPos($datosVenta);
$confirmacion = $ventas->confirmarVentaPosDryRun($datosVenta);

$bloqueos = array();
if ($idUsuario <= 0) {
    $bloqueos[] = "Indica --id_usuario=ID";
}
foreach (valor($asignacion, array("depurar", "bloqueos"), array()) as $bloqueo) {
    $bloqueos[] = $bloqueo;
}
foreach (valor($prevalidacion, array("depurar", "bloqueos"), array()) as $bloqueo) {
    $bloqueos[] = $bloqueo;
}
foreach (valor($confirmacion, array("depurar", "bloqueos"), array()) as $bloqueo) {
    $bloqueos[] = $bloqueo;
}

$partidas = valor($prevalidacion, array("depurar", "partidas"), array());
$planesSalida = array();
foreach ($partidas as $partida) {
    $planesSalida[] = array(
        "renglon" => isset($partida["renglon"]) ? $partida["renglon"] : null,
        "sku" => isset($partida["sku"]["sku"]) ? $partida["sku"]["sku"] : null,
        "modo_salida" => isset($partida["plan_salida_inventario"]["modo"]) ? $partida["plan_salida_inventario"]["modo"] : null,
        "asignaciones" => isset($partida["plan_salida_inventario"]["asignaciones"]) ? $partida["plan_salida_inventario"]["asignaciones"] : array(),
        "faltante" => isset($partida["plan_salida_inventario"]["faltante"]) ? $partida["plan_salida_inventario"]["faltante"] : null
    );
}
$bloqueosUnicos = array_values(array_unique($bloqueos));
$puedeVender = empty($bloqueosUnicos);
$autorizacion = "AUTORIZO EJECUTAR VENTA POS UAT REAL usando respaldo " . $respaldo
    . " con id_usuario=" . $idUsuario
    . " id_sku=" . ($idSkuSimple > 0 ? $idSkuSimple : 1113)
    . " cantidad=" . numero($cantidadSimple > 0 ? $cantidadSimple : 1)
    . " precio=" . numero($precioSimple > 0 ? $precioSimple : 10)
    . " pago=" . numero($pagoSimple > 0 ? $pagoSimple : ($precioSimple > 0 && $cantidadSimple > 0 ? $precioSimple * $cantidadSimple : 10))
    . " cliente=\"" . $cliente . "\"";
$comando = "C:\\xampp\\php\\php.exe storage\\uat\\uat_ventas_pos_venta_apply_authorized.php"
    . " --autorizar=VENTAS_POS_VENTA_REAL"
    . " --respaldo=" . $respaldo
    . " --id_usuario=" . $idUsuario
    . " --id_sku=" . ($idSkuSimple > 0 ? $idSkuSimple : 1113)
    . " --cantidad=" . numero($cantidadSimple > 0 ? $cantidadSimple : 1)
    . " --precio=" . numero($precioSimple > 0 ? $precioSimple : 10)
    . " --pago=" . numero($pagoSimple > 0 ? $pagoSimple : ($precioSimple > 0 && $cantidadSimple > 0 ? $precioSimple * $cantidadSimple : 10))
    . " --cliente=\"" . $cliente . "\"";
$autorizacionHumana = str_replace($respaldo, etiquetaRespaldoHumana($respaldo), $autorizacion);

echo json_encode(array(
    "ok" => !$asignacion["error"] && !$prevalidacion["error"] && !$confirmacion["error"],
    "modo" => "read-only",
    "read_only" => true,
    "id_usuario" => $idUsuario,
    "puede_vender_real" => $puedeVender,
    "asignacion_activa" => $asignacionActiva,
    "turno_abierto" => !empty($turno),
    "prevalidacion" => array(
        "error" => !empty($prevalidacion["error"]),
        "tipo" => isset($prevalidacion["tipo"]) ? $prevalidacion["tipo"] : null,
        "mensaje" => isset($prevalidacion["mensaje"]) ? $prevalidacion["mensaje"] : null
    ),
    "confirmacion" => array(
        "error" => !empty($confirmacion["error"]),
        "tipo" => isset($confirmacion["tipo"]) ? $confirmacion["tipo"] : null,
        "mensaje" => isset($confirmacion["mensaje"]) ? $confirmacion["mensaje"] : null
    ),
    "contexto" => array(
        "id_almacen" => $datosVenta["id_almacen"],
        "id_caja" => $datosVenta["id_caja"],
        "id_turno_caja" => $datosVenta["id_turno_caja"]
    ),
    "totales" => valor($prevalidacion, array("depurar", "totales"), array()),
    "planes_salida" => $planesSalida,
    "bloqueos" => $bloqueosUnicos,
    "autorizacion_sugerida" => $puedeVender ? $autorizacionHumana : "",
    "comando_aplicador" => $puedeVender ? $comando : "",
    "contrato_venta_real" => array(
        "crear_erp_ventas" => true,
        "crear_erp_ventas_detalle" => true,
        "crear_erp_ventas_pagos" => true,
        "crear_erp_ventas_detalle_inventario" => true,
        "insertar_erp_inventario_movimientos_origen_venta_pos" => true,
        "actualizar_existencias_disponibles" => true,
        "actualizar_unidades_fisicas_si_aplica" => true,
        "validar_todo_en_transaccion_con_for_update" => true
    ),
    "siguiente_paso" => empty(array_unique($bloqueos))
        ? "Puede diseniarse aplicador autorizado de venta real."
        : "Resolver bloqueos antes de habilitar venta real."
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

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

function numero($valor) {
    return rtrim(rtrim(number_format(floatval($valor), 6, ".", ""), "0"), ".");
}

function etiquetaRespaldoHumana($respaldo) {
    return basename((string) $respaldo) === "artianilocal_respaldo_completo_20260625_post_repair.sql"
        ? "UAT POS vigente"
        : $respaldo;
}
