<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-06.
 * Proposito: generar runbook UAT POS apartado multipartida sin ejecutar escrituras.
 * Impacto: ordena autorizaciones, comandos y verificaciones para cerrar ciclo completo.
 * Contrato: read-only; no abre turno, no carga stock, no crea apartado, no cobra, no entrega y no cierra caja.
 */

$args = isset($argv) ? $argv : array();
$idUsuario = 1;
$idAlmacen = 5;
$idSku = 1760;
$cantidadPorPartida = 1;
$precio = 295;
$anticipo = 120;
$montoInicial = 500;
$telefono = "3312345678";
$cliente = "Cliente UAT Apartado Multipartida POS";
$referenciaStock = "INV-INICIAL-POS-UAT-MULTIPARTIDA-1760";
$compacto = false;

foreach ($args as $arg) {
    if (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_almacen=") === 0) {
        $idAlmacen = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_sku=") === 0) {
        $idSku = intval(trim(substr($arg, 9), "\"' "));
    } elseif (strpos($arg, "--cantidad=") === 0) {
        $cantidadPorPartida = floatval(trim(substr($arg, 11), "\"' "));
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
    } elseif ($arg === "--compact=1" || $arg === "--compacto=1") {
        $compacto = true;
    }
}

$total = round($cantidadPorPartida * $precio * 2, 6);
$saldo = round(max(0, $total - $anticipo), 6);
$montoContadoFinal = round($montoInicial + $total, 6);
$stockNecesario = round($cantidadPorPartida * 2, 6);

$autorizaciones = array(
    "preparar_datos" => "AUTORIZO ABRIR TURNO POS UAT usando respaldo UAT POS vigente con id_usuario={$idUsuario} y monto_inicial={$montoInicial} observaciones=\"Apertura UAT POS apartado multipartida\"\n\nAUTORIZO CARGAR STOCK UAT POS usando respaldo UAT POS vigente con id_usuario={$idUsuario} id_almacen={$idAlmacen} id_sku={$idSku} cantidad={$stockNecesario} referencia={$referenciaStock}",
    "crear_apartado" => "AUTORIZO CREAR APARTADO MULTIPARTIDA POS UAT REAL usando respaldo UAT POS vigente con token VENTAS_POS_PEDIDO_REAL id_usuario={$idUsuario} telefono={$telefono} cliente=\"{$cliente}\" anticipo={$anticipo} item={$idSku},{$cantidadPorPartida},{$precio} item={$idSku},{$cantidadPorPartida},{$precio}",
    "abonar_saldo" => "AUTORIZO REGISTRAR ABONO APARTADO POS UAT REAL usando respaldo UAT POS vigente con token VENTAS_POS_APARTADO_ABONO_REAL id_usuario={$idUsuario} folio=FOLIO_APT monto={$saldo} referencia=UAT-LIQ-FOLIO_APT",
    "entregar" => "AUTORIZO ENTREGAR APARTADO POS UAT REAL usando respaldo UAT POS vigente con token VENTAS_POS_PEDIDO_ENTREGA_REAL id_usuario={$idUsuario} folio=FOLIO_APT",
    "cerrar_turno" => "AUTORIZO CERRAR TURNO POS UAT REAL usando respaldo UAT POS vigente con id_usuario={$idUsuario} monto_contado={$montoContadoFinal} observaciones=\"Cierre UAT POS apartado multipartida FOLIO_APT\""
);

$comandos = array(
    "prevalidar_readonly" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ventas_pos_pedidos_apartados_readonly.php --compact=1 --monto_abono={$anticipo} --item={$idSku},{$cantidadPorPartida},{$precio} --item={$idSku},{$cantidadPorPartida},{$precio}",
    "crear_apartado_real" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ventas_pos_pedido_apartado_apply_authorized.php --autorizar=VENTAS_POS_PEDIDO_REAL --respaldo=\"UAT POS vigente\" --id_usuario={$idUsuario} --telefono={$telefono} --cliente=\"{$cliente}\" --anticipo={$anticipo} --item={$idSku},{$cantidadPorPartida},{$precio} --item={$idSku},{$cantidadPorPartida},{$precio}",
    "verificar_post_creacion" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ventas_pos_pedido_multipartida_post_readonly.php --folio=FOLIO_APT --compact=1 --esperar_partidas=2 --esperar_total={$total} --esperar_pagado={$anticipo} --esperar_saldo={$saldo}",
    "abonar_saldo_real" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ventas_pos_apartado_abono_apply_authorized.php --autorizar=VENTAS_POS_APARTADO_ABONO_REAL --respaldo=\"UAT POS vigente\" --id_usuario={$idUsuario} --folio=FOLIO_APT --monto={$saldo} --referencia=UAT-LIQ-FOLIO_APT",
    "entregar_real" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ventas_pos_pedido_entrega_apply_authorized.php --autorizar=VENTAS_POS_PEDIDO_ENTREGA_REAL --respaldo=\"UAT POS vigente\" --id_usuario={$idUsuario} --folio=FOLIO_APT",
    "verificar_post_abono" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ventas_pos_pedido_multipartida_post_readonly.php --folio=FOLIO_APT --compact=1 --esperar_partidas=2 --esperar_total={$total} --esperar_pagado={$total} --esperar_saldo=0",
    "verificar_post_entrega" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ventas_pos_pedido_multipartida_post_readonly.php --folio=FOLIO_APT --compact=1 --esperar_partidas=2 --esperar_total={$total} --esperar_pagado={$total} --esperar_saldo=0",
    "cerrar_turno_real" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ventas_pos_turno_cierre_apply_authorized.php --autorizar=VENTAS_POS_TURNO_CIERRE --respaldo=\"UAT POS vigente\" --id_usuario={$idUsuario} --monto_contado={$montoContadoFinal} --observaciones=\"Cierre UAT POS apartado multipartida FOLIO_APT\""
);

$salida = array(
    "ok" => true,
    "modo" => "pedido_multipartida_runbook_readonly",
    "read_only" => true,
    "parametros" => array(
        "id_usuario" => $idUsuario,
        "id_almacen" => $idAlmacen,
        "id_sku" => $idSku,
        "partidas" => 2,
        "cantidad_por_partida" => $cantidadPorPartida,
        "stock_necesario" => $stockNecesario,
        "precio" => $precio,
        "total" => $total,
        "anticipo" => $anticipo,
        "saldo" => $saldo,
        "monto_inicial" => $montoInicial,
        "monto_contado_final_sugerido" => $montoContadoFinal
    ),
    "autorizaciones" => $autorizaciones,
    "comandos" => $comandos,
    "verificaciones_esperadas" => array(
        "post_creacion" => array(
            "estatus" => "pendiente_pago",
            "partidas" => 2,
            "reservas" => 2,
            "pagos_total" => $anticipo,
            "saldo_total" => $saldo,
            "trazas_kardex" => 0
        ),
        "post_abono" => array(
            "estatus" => "pagado",
            "pagos_total" => $total,
            "saldo_total" => 0
        ),
        "post_entrega" => array(
            "estatus" => "entregado",
            "reservas_consumidas" => 2,
            "trazas_kardex_minimas" => 2
        ),
        "cierre_turno" => array(
            "monto_contado_sugerido" => $montoContadoFinal,
            "diferencia_esperada" => 0,
            "supuesto" => "sin otros ingresos, salidas, gastos, retiros, reembolsos o diferencias de caja durante el turno"
        )
    ),
    "contrato" => array(
        "no_escribe_bd" => true,
        "no_abre_turno" => true,
        "no_carga_stock" => true,
        "no_crea_apartado" => true,
        "no_registra_pago" => true,
        "no_entrega" => true,
        "no_cierra_turno" => true
    )
);

if ($compacto) {
    $salida = array(
        "ok" => true,
        "modo" => "pedido_multipartida_runbook_readonly",
        "read_only" => true,
        "parametros" => $salida["parametros"],
        "autorizaciones" => $autorizaciones,
        "contrato" => $salida["contrato"]
    );
}

echo json_encode($salida, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
