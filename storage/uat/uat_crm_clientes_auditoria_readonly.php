<?php

/**
 * IA: Codex GPT-5
 * Fecha: 2026-06-29
 * Proposito: ejecutar auditoria read-only de fuentes CRM Clientes desde CLI.
 * Impacto: permite repetir diagnostico sin usar endpoints web ni escribir BD.
 * Contrato: no aplica DDL, no migra y no modifica clientes.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/modelos/ClientesCrm.php";

$modelo = new ClientesCrm();
$respuesta = $modelo->auditarFuentesClientes();
$depurar = isset($respuesta["depurar"]) && is_array($respuesta["depurar"]) ? $respuesta["depurar"] : array();
$fuentes = isset($depurar["fuentes"]) && is_array($depurar["fuentes"]) ? $depurar["fuentes"] : array();
$legacy = isset($fuentes["crm_clientes"]) && is_array($fuentes["crm_clientes"]) ? $fuentes["crm_clientes"] : array();
$erp = isset($fuentes["erp_clientes"]) && is_array($fuentes["erp_clientes"]) ? $fuentes["erp_clientes"] : array();

$salida = array(
  "ok" => empty($respuesta["error"]),
  "modo" => "read-only",
  "mensaje" => isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : "",
  "legacy_registros" => isset($legacy["registros"]) ? $legacy["registros"] : null,
  "legacy_sin_identificador_util" => isset($legacy["sin_identificador_util"]) ? $legacy["sin_identificador_util"] : null,
  "legacy_duplicados_identificador" => isset($legacy["duplicados_identificador"]) && is_array($legacy["duplicados_identificador"]) ? count($legacy["duplicados_identificador"]) : null,
  "erp_clientes_registros" => isset($erp["registros"]) ? $erp["registros"] : null,
  "hallazgos" => isset($depurar["hallazgos"]) ? $depurar["hallazgos"] : array(),
  "respuesta" => $respuesta
);

echo json_encode($salida, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
