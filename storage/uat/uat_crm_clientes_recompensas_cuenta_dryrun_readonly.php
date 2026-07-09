<?php

/**
 * IA: Codex GPT-5
 * Fecha: 2026-06-30
 * Proposito: validar cuenta CRM Recompensas sin escribir BD.
 * Impacto: prueba elegibilidad cliente/programa antes de crear cuenta.
 * Contrato: read-only; no crea cuenta, movimientos ni puntos.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/modelos/ClientesCrm.php";

$opciones = getopt("", array("cliente::", "programa::", "nivel::"));
$datos = array(
  "id_cliente_crm" => isset($opciones["cliente"]) ? intval($opciones["cliente"]) : 1,
  "id_programa_recompensa" => isset($opciones["programa"]) ? intval($opciones["programa"]) : 1,
  "nivel" => isset($opciones["nivel"]) ? trim((string)$opciones["nivel"]) : ""
);

echo json_encode((new ClientesCrm())->recompensaCuentaDryRun($datos), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
