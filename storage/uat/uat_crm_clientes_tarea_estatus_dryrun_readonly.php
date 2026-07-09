<?php

/**
 * IA: Codex GPT-5
 * Fecha: 2026-06-30
 * Proposito: validar cambio de estatus de una tarea CRM sin escribir BD.
 * Impacto: prepara cierre/cancelacion controlada de seguimiento.
 * Contrato: read-only; no modifica tareas, clientes, eventos ni notificaciones SYS.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/modelos/ClientesCrm.php";

$opciones = getopt("", array(
  "tarea::",
  "estatus::",
  "resultado::",
  "nota::"
));

$datos = array(
  "id_cliente_tarea" => isset($opciones["tarea"]) ? intval($opciones["tarea"]) : 0,
  "estatus" => isset($opciones["estatus"]) ? trim((string)$opciones["estatus"]) : "",
  "resultado_cierre" => isset($opciones["resultado"]) ? trim((string)$opciones["resultado"]) : "",
  "nota" => isset($opciones["nota"]) ? trim((string)$opciones["nota"]) : ""
);

echo json_encode((new ClientesCrm())->tareaEstatusDryRun($datos), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
