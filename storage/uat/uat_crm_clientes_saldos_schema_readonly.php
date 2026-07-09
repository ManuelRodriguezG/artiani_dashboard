<?php

/**
 * IA: Codex GPT-5
 * Fecha: 2026-07-06
 * Proposito: generar plan DDL CRM Saldos sin ejecutar.
 * Impacto: permite revisar cuenta corriente monetaria antes de pedir apply real.
 * Contrato: read-only; no crea tablas, cuentas, movimientos ni saldos.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/DBSchema.php";
require_once __DIR__ . "/../../app/modelos/ClientesCrmEsquema.php";

$modelo = new ClientesCrmEsquema();
$respuesta = $modelo->planActualizarSaldosClientesCrm(false);
$depurar = isset($respuesta["depurar"]) && is_array($respuesta["depurar"]) ? $respuesta["depurar"] : array();
$pendientes = 0;

foreach ($depurar as $paso) {
  if (isset($paso["depurar"]["ejecutado"]) && $paso["depurar"]["ejecutado"] === false && isset($paso["depurar"]["sql"])) {
    $pendientes++;
  }
}

echo json_encode(array(
  "ok" => empty($respuesta["error"]),
  "modo" => "read-only",
  "mensaje" => isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : "",
  "ddl_total" => count($depurar),
  "ddl_pendientes" => $pendientes,
  "tablas" => array(
    "crm_clientes_saldos_cuentas",
    "crm_clientes_saldos_movimientos"
  ),
  "token_apply" => "CRM_CLIENTES_SALDOS_DDL",
  "contrato" => array(
    "no_escribe_bd" => true,
    "no_crea_cuentas" => true,
    "no_crea_movimientos" => true,
    "no_mueve_caja" => true,
    "no_mueve_inventario" => true,
    "no_usa_recompensas" => true
  ),
  "respuesta" => $respuesta
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
