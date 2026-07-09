<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-07.
 * Proposito: generar runbook UAT para venta POS con saldo CRM sin escribir BD.
 * Impacto: ordena apertura de turno, venta real, verificacion posterior y cierre esperado.
 * Contrato: read-only; no abre turno, no carga stock, no vende y no descuenta saldo.
 */

$args = isset($argv) ? $argv : array();
$idUsuario = 1;
$idAlmacen = 5;
$idSku = 1760;
$cantidad = 1;
$precio = 295;
$idClienteCrm = 157;
$montoSaldoCrm = 100;
$pagoCaja = 195;
$montoInicial = 500;
$respaldo = "UAT POS vigente";
$referenciaStock = "INV-INICIAL-POS-UAT-SALDO-CRM-01";
$compacto = false;

foreach ($args as $arg) {
    if (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_almacen=") === 0) {
        $idAlmacen = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_sku=") === 0) {
        $idSku = intval(trim(substr($arg, 9), "\"' "));
    } elseif (strpos($arg, "--cantidad=") === 0) {
        $cantidad = numero(substr($arg, 11));
    } elseif (strpos($arg, "--precio=") === 0) {
        $precio = numero(substr($arg, 9));
    } elseif (strpos($arg, "--id_cliente_crm=") === 0) {
        $idClienteCrm = intval(trim(substr($arg, 17), "\"' "));
    } elseif (strpos($arg, "--monto_saldo_crm=") === 0) {
        $montoSaldoCrm = numero(substr($arg, 18));
    } elseif (strpos($arg, "--pago_caja=") === 0) {
        $pagoCaja = numero(substr($arg, 12));
    } elseif (strpos($arg, "--monto_inicial=") === 0) {
        $montoInicial = numero(substr($arg, 16));
    } elseif (strpos($arg, "--respaldo=") === 0) {
        $respaldo = trim(substr($arg, 11), "\"' ");
    } elseif (strpos($arg, "--referencia_stock=") === 0) {
        $referenciaStock = trim(substr($arg, 19), "\"' ");
    } elseif ($arg === "--compact=1" || $arg === "--compacto=1") {
        $compacto = true;
    }
}

$total = redondear($cantidad * $precio);
$montoContadoEsperado = redondear($montoInicial + $pagoCaja);
$saldoCrmResultanteEsperado = redondear(max(0, $montoSaldoCrm - $montoSaldoCrm));

$autorizaciones = array(
    "abrir_turno" => "AUTORIZO ABRIR TURNO POS UAT usando respaldo {$respaldo} con id_usuario={$idUsuario} y monto_inicial=" . fmt($montoInicial) . " observaciones=\"Apertura UAT POS saldo CRM\"",
    "cargar_stock_opcional" => "AUTORIZO CARGAR STOCK UAT POS usando respaldo {$respaldo} con id_usuario={$idUsuario} id_almacen={$idAlmacen} id_sku={$idSku} cantidad=" . fmt($cantidad) . " referencia={$referenciaStock}",
    "ejecutar_venta" => "AUTORIZO EJECUTAR VENTA POS UAT REAL CON SALDO CRM usando respaldo {$respaldo} con token VENTAS_POS_SALDO_CRM_REAL id_usuario={$idUsuario} id_cliente_crm={$idClienteCrm} id_sku={$idSku} cantidad=" . fmt($cantidad) . " precio=" . fmt($precio) . " monto_saldo_crm=" . fmt($montoSaldoCrm) . " pago_caja=" . fmt($pagoCaja),
    "cerrar_turno" => "AUTORIZO CERRAR TURNO POS UAT REAL usando respaldo {$respaldo} con id_usuario={$idUsuario} monto_contado=" . fmt($montoContadoEsperado) . " observaciones=\"Cierre UAT POS venta con saldo CRM\""
);

$scripts = array(
    "readiness" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ventas_pos_saldo_crm_apply_prep_authorized.php --autorizar=VENTAS_POS_SALDO_CRM_APPLY_PREP --respaldo=\"{$respaldo}\" --id_usuario={$idUsuario} --id_cliente_crm={$idClienteCrm} --monto_saldo_crm=" . fmt($montoSaldoCrm) . " --id_sku={$idSku} --cantidad=" . fmt($cantidad) . " --precio=" . fmt($precio) . " --pago_caja=" . fmt($pagoCaja) . " --compact=1",
    "ejecutar" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ventas_pos_saldo_crm_apply_authorized.php --autorizar=VENTAS_POS_SALDO_CRM_REAL --respaldo=\"{$respaldo}\" --id_usuario={$idUsuario} --id_cliente_crm={$idClienteCrm} --id_sku={$idSku} --cantidad=" . fmt($cantidad) . " --precio=" . fmt($precio) . " --monto_saldo_crm=" . fmt($montoSaldoCrm) . " --pago_caja=" . fmt($pagoCaja),
    "post" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ventas_pos_saldo_crm_post_readonly.php --folio=FOLIO_POS --compact=1"
);

$salida = array(
    "ok" => true,
    "modo" => "saldo_crm_runbook_readonly",
    "read_only" => true,
    "parametros" => array(
        "id_usuario" => $idUsuario,
        "id_almacen" => $idAlmacen,
        "id_sku" => $idSku,
        "cantidad" => $cantidad,
        "precio" => $precio,
        "total" => $total,
        "id_cliente_crm" => $idClienteCrm,
        "monto_saldo_crm" => $montoSaldoCrm,
        "pago_caja" => $pagoCaja,
        "monto_inicial" => $montoInicial,
        "monto_contado_esperado" => $montoContadoEsperado
    ),
    "orden_operativo" => array(
        "1_readiness" => "Confirmar que solo falte turno abierto o que no existan bloqueos.",
        "2_abrir_turno" => "Abrir turno para usuario/caja asignada.",
        "3_stock" => "Cargar stock solo si readiness indica existencia insuficiente.",
        "4_venta" => "Ejecutar venta real con saldo CRM.",
        "5_post" => "Auditar que saldo CRM no movio caja y que caja solo recibio pago_caja.",
        "6_cierre" => "Cerrar turno con monto inicial + pago caja."
    ),
    "autorizaciones" => $autorizaciones,
    "scripts" => $scripts,
    "contrato" => array(
        "no_escribe_bd" => true,
        "no_abre_turno" => true,
        "no_carga_stock" => true,
        "no_vende" => true,
        "no_descuenta_saldo_crm" => true
    )
);

if ($compacto) {
    $salida = array(
        "ok" => true,
        "modo" => "saldo_crm_runbook_readonly",
        "read_only" => true,
        "total" => $total,
        "monto_contado_esperado" => $montoContadoEsperado,
        "autorizaciones" => $autorizaciones,
        "scripts" => $scripts
    );
}

responder($salida);

function numero($valor) {
    return round(floatval(trim((string) $valor, "\"' ")), 6);
}

function redondear($valor) {
    return round(floatval($valor), 6);
}

function fmt($valor) {
    $texto = rtrim(rtrim(number_format(floatval($valor), 6, ".", ""), "0"), ".");
    return $texto === "" ? "0" : $texto;
}

function responder($datos) {
    echo json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    exit(empty($datos["ok"]) ? 1 : 0);
}
