<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-06-27.
 * Proposito: auditar atenciones POS persistentes y simular bandeja sin escribir BD.
 * Impacto: prepara flujo multiusuario donde vendedores levantan cuentas y caja cobra.
 * Contrato: read-only; no crea atenciones, no reserva y no descuenta inventario.
 */

$args = isset($argv) ? $argv : array();
$idUsuario = 0;
$idAlmacen = 0;

foreach ($args as $arg) {
    if (strpos($arg, "--id_usuario=") === 0) {
        $idUsuario = intval(trim(substr($arg, 13), "\"' "));
    } elseif (strpos($arg, "--id_almacen=") === 0) {
        $idAlmacen = intval(trim(substr($arg, 13), "\"' "));
    }
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/VentasErp.php";
require_once "../app/modelos/VentasErpEsquema.php";

$ventas = new VentasErp();
$esquema = new VentasErpEsquema();

$asignacion = array();
if ($idUsuario > 0) {
    $asignacion = $ventas->asignacionActualTerminalPos(array("id_usuario" => $idUsuario));
    $depurar = isset($asignacion["depurar"]) && is_array($asignacion["depurar"]) ? $asignacion["depurar"] : array();
    $asignacionDatos = isset($depurar["asignacion"]) && is_array($depurar["asignacion"]) ? $depurar["asignacion"] : array();
    if ($idAlmacen <= 0 && !empty($asignacionDatos["id_almacen"])) {
        $idAlmacen = intval($asignacionDatos["id_almacen"]);
    }
}

$auditoria = $esquema->auditarAtencionesPos();
$tablasPendientes = array();
foreach (isset($auditoria["depurar"]) && is_array($auditoria["depurar"]) ? $auditoria["depurar"] : array() as $tabla) {
    if (empty($tabla["existe"])) {
        $tablasPendientes[] = $tabla["tabla"];
    }
}

echo json_encode(array(
    "ok" => true,
    "modo" => "atenciones_readonly",
    "id_usuario" => $idUsuario,
    "id_almacen" => $idAlmacen,
    "auditoria" => $auditoria,
    "bandeja" => $ventas->atencionesBandejaDryRun(array("id_almacen" => $idAlmacen)),
    "contrato" => array(
        "cuentas_locales_actuales" => true,
        "atenciones_persistentes_requieren_ddl_expandido" => !empty($tablasPendientes),
        "tablas_pendientes" => $tablasPendientes,
        "no_reserva_inventario" => true,
        "no_descuenta_inventario" => true,
        "caja_debe_revalidar_al_cobrar" => true
    )
), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
