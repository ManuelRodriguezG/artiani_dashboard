<?php

/**
 * IA: Codex GPT-5
 * Fecha: 2026-06-30
 * Proposito: validar movimiento CRM Recompensas sin escribir BD.
 * Impacto: prueba saldo resultante antes de aplicar movimiento manual UAT.
 * Contrato: read-only; no crea movimiento ni cambia saldo.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/modelos/ClientesCrm.php";

$opciones = getopt("", array("cliente::", "programa::", "tipo::", "puntos::", "descripcion::", "origen_id::"));
$datos = array(
  "id_cliente_crm" => isset($opciones["cliente"]) ? intval($opciones["cliente"]) : 1,
  "id_programa_recompensa" => isset($opciones["programa"]) ? intval($opciones["programa"]) : 1,
  "tipo" => isset($opciones["tipo"]) ? trim((string)$opciones["tipo"]) : "acumulacion",
  "puntos" => isset($opciones["puntos"]) ? floatval($opciones["puntos"]) : 10,
  "origen_modulo" => "crm",
  "origen_tipo" => "uat_manual",
  "origen_id" => isset($opciones["origen_id"]) ? trim((string)$opciones["origen_id"]) : "UAT-CRM-RECOMPENSAS-001",
  "descripcion" => isset($opciones["descripcion"]) ? trim((string)$opciones["descripcion"]) : "Movimiento manual UAT de recompensas"
);

echo json_encode((new ClientesCrm())->recompensaMovimientoDryRun($datos), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
