<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-07.
 * Proposito: consultar saldo CRM disponible para POS sin escribir BD.
 * Impacto: valida el indicador de saldo cliente antes de pruebas de cobro.
 * Contrato: read-only; no crea cuentas, no crea movimientos y no mueve caja.
 */

$idClienteCrm = 0;
$compacto = false;
foreach (isset($argv) ? $argv : array() as $arg) {
    if (strpos($arg, "--id_cliente_crm=") === 0) {
        $idClienteCrm = intval(trim(substr($arg, 17), "\"' "));
    } elseif (strpos($arg, "--id=") === 0) {
        $idClienteCrm = intval(trim(substr($arg, 5), "\"' "));
    } elseif ($arg === "--compact=1" || $arg === "--compacto=1") {
        $compacto = true;
    }
}

if ($idClienteCrm <= 0) {
    responder(array(
        "ok" => false,
        "modo" => "cliente_saldo_crm_readonly",
        "mensaje" => "Indica --id_cliente_crm=ID.",
        "read_only" => true
    ));
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";

$ventas = new VentasErp();
$respuesta = $ventas->clienteSaldoCrmReadOnly(array("id_cliente_crm" => $idClienteCrm));
$depurar = isset($respuesta["depurar"]) && is_array($respuesta["depurar"]) ? $respuesta["depurar"] : array();
$salida = array(
    "ok" => empty($respuesta["error"]),
    "modo" => "cliente_saldo_crm_readonly",
    "read_only" => true,
    "respuesta" => $respuesta
);

if ($compacto) {
    $cliente = isset($depurar["cliente"]) && is_array($depurar["cliente"]) ? $depurar["cliente"] : array();
    $salida = array(
        "ok" => empty($respuesta["error"]),
        "modo" => "cliente_saldo_crm_readonly",
        "read_only" => true,
        "id_cliente_crm" => $idClienteCrm,
        "cliente" => isset($cliente["nombre_publico"]) ? $cliente["nombre_publico"] : "",
        "saldo_disponible" => isset($depurar["saldo_disponible"]) ? floatval($depurar["saldo_disponible"]) : 0,
        "saldo_retenido" => isset($depurar["saldo_retenido"]) ? floatval($depurar["saldo_retenido"]) : 0,
        "saldo_total" => isset($depurar["saldo_total"]) ? floatval($depurar["saldo_total"]) : 0,
        "movimientos_recientes" => isset($depurar["movimientos_recientes"]) && is_array($depurar["movimientos_recientes"]) ? count($depurar["movimientos_recientes"]) : 0,
        "avisos" => isset($depurar["avisos"]) ? $depurar["avisos"] : array(),
        "contrato" => array(
            "no_escribe_bd" => true,
            "no_crea_cuenta" => true,
            "no_crea_movimientos" => true,
            "no_mueve_caja" => true
        )
    );
}

responder($salida);

function responder($datos) {
    echo json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    exit(empty($datos["ok"]) ? 1 : 0);
}
