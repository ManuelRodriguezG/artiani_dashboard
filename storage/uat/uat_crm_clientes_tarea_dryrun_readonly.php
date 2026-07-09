<?php

/**
 * IA: Codex GPT-5
 * Fecha: 2026-06-30
 * Proposito: validar una tarea CRM sin escribir BD.
 * Impacto: permite probar cliente, tipo, prioridad y titulo antes de pedir apply fuerte.
 * Contrato: read-only; no crea tareas, eventos ni notificaciones SYS.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/modelos/ClientesCrm.php";

$opciones = getopt("", array(
  "cliente::",
  "tipo::",
  "prioridad::",
  "titulo::",
  "descripcion::",
  "fecha_vencimiento::",
  "responsable::",
  "origen_tipo::",
  "origen_id::"
));

$datos = array(
  "id_cliente_crm" => isset($opciones["cliente"]) ? intval($opciones["cliente"]) : 0,
  "tipo" => isset($opciones["tipo"]) ? trim((string)$opciones["tipo"]) : "calidad_datos",
  "prioridad" => isset($opciones["prioridad"]) ? trim((string)$opciones["prioridad"]) : "normal",
  "titulo" => isset($opciones["titulo"]) ? trim((string)$opciones["titulo"]) : "",
  "descripcion" => isset($opciones["descripcion"]) ? trim((string)$opciones["descripcion"]) : "",
  "fecha_vencimiento" => isset($opciones["fecha_vencimiento"]) ? trim((string)$opciones["fecha_vencimiento"]) : "",
  "id_usuario_responsable" => isset($opciones["responsable"]) ? intval($opciones["responsable"]) : 0,
  "origen_tipo" => isset($opciones["origen_tipo"]) ? trim((string)$opciones["origen_tipo"]) : "uat_manual",
  "origen_id" => isset($opciones["origen_id"]) ? trim((string)$opciones["origen_id"]) : ""
);

echo json_encode((new ClientesCrm())->tareaSeguimientoDryRun($datos), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
