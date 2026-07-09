<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-06.
 * Proposito: preparar runbook read-only para UAT de cancelacion de apartado POS antes de entrega.
 * Impacto: no escribe BD; solo calcula datos esperados y frases de autorizacion.
 * Contrato: read-only; no abre turno, no carga stock, no crea apartado y no cancela.
 */

$args = isset($argv) ? $argv : array();
$idUsuario = 1;
$idAlmacen = 5;
$idSku = 1760;
$cantidad = 1;
$precio = 295;
$anticipo = 100;
$montoInicial = 500;
$telefono = "3312345678";
$cliente = "Cliente UAT Cancelacion Apartado POS";
$referenciaStock = "INV-INICIAL-POS-UAT-CANCEL-APT-1760";
$motivo = "UAT cancelacion apartado antes de entrega";
$compacto = false;

foreach ($args as $arg) {
    if (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_almacen=") === 0) {
        $idAlmacen = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_sku=") === 0) {
        $idSku = intval(trim(substr($arg, 9), "\"' "));
    } elseif (strpos($arg, "--cantidad=") === 0) {
        $cantidad = floatval(trim(substr($arg, 11), "\"' "));
    } elseif (strpos($arg, "--precio=") === 0) {
        $precio = floatval(trim(substr($arg, 9), "\"' "));
    } elseif (strpos($arg, "--anticipo=") === 0) {
        $anticipo = floatval(trim(substr($arg, 11), "\"' "));
    } elseif (strpos($arg, "--monto_inicial=") === 0) {
        $montoInicial = floatval(trim(substr($arg, 16), "\"' "));
    } elseif (strpos($arg, "--telefono=") === 0) {
        $telefono = trim(substr($arg, 11), "\"' ");
    } elseif (strpos($arg, "--cliente=") === 0) {
        $cliente = trim(substr($arg, 10), "\"' ");
    } elseif (strpos($arg, "--referencia_stock=") === 0) {
        $referenciaStock = trim(substr($arg, 19), "\"' ");
    } elseif (strpos($arg, "--motivo=") === 0) {
        $motivo = trim(substr($arg, 9), "\"' ");
    } elseif ($arg === "--compact=1" || $arg === "--compacto=1") {
        $compacto = true;
    }
}

$total = round($cantidad * $precio, 6);
$saldo = max(0, round($total - $anticipo, 6));
$montoContadoFinal = round($montoInicial + $anticipo, 6);

$autorizaciones = array(
    "preparar_datos" => "AUTORIZO ABRIR TURNO POS UAT usando respaldo UAT POS vigente con id_usuario={$idUsuario} y monto_inicial={$montoInicial} observaciones=\"Apertura UAT POS cancelacion apartado\"\n\n"
        . "AUTORIZO CARGAR STOCK UAT POS usando respaldo UAT POS vigente con id_usuario={$idUsuario} id_almacen={$idAlmacen} id_sku={$idSku} cantidad={$cantidad} referencia={$referenciaStock}",
    "crear_apartado" => "AUTORIZO CREAR APARTADO POS UAT REAL usando respaldo UAT POS vigente con token VENTAS_POS_PEDIDO_REAL id_usuario={$idUsuario} telefono={$telefono} cliente=\"{$cliente}\" anticipo={$anticipo} item={$idSku},{$cantidad},{$precio}",
    "cancelar" => "AUTORIZO CANCELAR APARTADO POS UAT REAL usando respaldo UAT POS vigente con token VENTAS_POS_PEDIDO_CANCELAR_REAL id_usuario={$idUsuario} folio=FOLIO_APT motivo=\"{$motivo}\"",
    "cerrar_turno" => "AUTORIZO CERRAR TURNO POS UAT REAL usando respaldo UAT POS vigente con id_usuario={$idUsuario} monto_contado={$montoContadoFinal} observaciones=\"Cierre UAT POS cancelacion apartado FOLIO_APT\""
);

$comandos = array(
    "crear_apartado_real" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ventas_pos_pedido_apartado_apply_authorized.php --autorizar=VENTAS_POS_PEDIDO_REAL --respaldo=\"UAT POS vigente\" --id_usuario={$idUsuario} --telefono={$telefono} --cliente=\"{$cliente}\" --anticipo={$anticipo} --item={$idSku},{$cantidad},{$precio}",
    "cancelar_real" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ventas_pos_pedido_cancelar_apply_authorized.php --autorizar=VENTAS_POS_PEDIDO_CANCELAR_REAL --respaldo=\"UAT POS vigente\" --id_usuario={$idUsuario} --folio=FOLIO_APT --motivo=\"{$motivo}\"",
    "verificar_post" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ventas_pos_pedido_multipartida_post_readonly.php --folio=FOLIO_APT --compact=1 --esperar_partidas=1 --esperar_total={$total} --esperar_pagado={$anticipo} --esperar_saldo={$saldo}"
);

$salida = array(
    "ok" => true,
    "modo" => "pedido_cancelacion_runbook_readonly",
    "read_only" => true,
    "parametros" => array(
        "id_usuario" => $idUsuario,
        "id_almacen" => $idAlmacen,
        "id_sku" => $idSku,
        "cantidad" => $cantidad,
        "precio" => $precio,
        "total" => $total,
        "anticipo" => $anticipo,
        "saldo_pre_cancelacion" => $saldo,
        "monto_inicial" => $montoInicial,
        "monto_contado_final_sugerido" => $montoContadoFinal
    ),
    "validaciones_esperadas" => array(
        "apartado_creado_con_reserva_activa" => true,
        "cancelacion_libera_reserva" => true,
        "cancelacion_no_genera_kardex_salida" => true,
        "cancelacion_no_reembolsa_caja_en_este_flujo" => true,
        "pagos_quedan_para_decision_financiera" => true
    ),
    "autorizaciones" => $autorizaciones,
    "comandos_no_ejecutados" => $comandos,
    "contrato" => array(
        "no_escribe_bd" => true,
        "no_abre_turno" => true,
        "no_carga_stock" => true,
        "no_crea_apartado" => true,
        "no_cancela" => true,
        "no_mueve_caja" => true,
        "no_mueve_inventario" => true
    )
);

if ($compacto) {
    $salida = array(
        "ok" => true,
        "modo" => $salida["modo"],
        "read_only" => true,
        "parametros" => $salida["parametros"],
        "autorizaciones" => $autorizaciones,
        "contrato" => $salida["contrato"]
    );
}

echo json_encode($salida, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
