<?php

/**
 * IA: Codex GPT-5
 * Fecha: 2026-06-29
 * Proposito: generar plan de migracion CRM Clientes en modo read-only.
 * Impacto: muestra bloqueos y pasos antes de cualquier apply autorizado.
 * Contrato: no crea tablas, no migra datos y no modifica ventas/clientes.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/modelos/ClientesCrm.php";

$modelo = new ClientesCrm();
$respuesta = $modelo->planMigracionClientesDryRun();
$depurar = isset($respuesta["depurar"]) && is_array($respuesta["depurar"]) ? $respuesta["depurar"] : array();

$salida = array(
  "ok" => empty($respuesta["error"]),
  "modo" => "read-only",
  "mensaje" => isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : "",
  "bloqueos" => isset($depurar["bloqueos"]) ? $depurar["bloqueos"] : array(),
  "pasos_total" => isset($depurar["pasos"]) && is_array($depurar["pasos"]) ? count($depurar["pasos"]) : 0,
  "requiere_respaldo_externo" => !empty($depurar["requiere_respaldo_externo"]),
  "requiere_autorizacion_textual" => !empty($depurar["requiere_autorizacion_textual"]),
  "respuesta" => $respuesta
);

echo json_encode($salida, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
