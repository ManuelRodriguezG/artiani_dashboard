<?php

/**
 * IA: Codex GPT-5
 * Fecha: 2026-06-30
 * Proposito: validar una interaccion CRM sin escribir BD.
 * Impacto: permite probar cliente, tipo, canal y resumen antes de pedir apply fuerte.
 * Contrato: read-only; no crea interacciones, tareas, eventos ni notificaciones SYS.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/modelos/ClientesCrm.php";

$opciones = getopt("", array(
  "cliente::",
  "tipo::",
  "canal::",
  "direccion::",
  "resultado::",
  "resumen::",
  "detalle::",
  "fecha_interaccion::",
  "origen_tipo::",
  "origen_id::"
));

$datos = array(
  "id_cliente_crm" => isset($opciones["cliente"]) ? intval($opciones["cliente"]) : 0,
  "tipo" => isset($opciones["tipo"]) ? trim((string)$opciones["tipo"]) : "contacto",
  "canal" => isset($opciones["canal"]) ? trim((string)$opciones["canal"]) : "whatsapp",
  "direccion" => isset($opciones["direccion"]) ? trim((string)$opciones["direccion"]) : "saliente",
  "resultado" => isset($opciones["resultado"]) ? trim((string)$opciones["resultado"]) : "registrado",
  "resumen" => isset($opciones["resumen"]) ? trim((string)$opciones["resumen"]) : "",
  "detalle" => isset($opciones["detalle"]) ? trim((string)$opciones["detalle"]) : "",
  "fecha_interaccion" => isset($opciones["fecha_interaccion"]) ? trim((string)$opciones["fecha_interaccion"]) : "",
  "origen_tipo" => isset($opciones["origen_tipo"]) ? trim((string)$opciones["origen_tipo"]) : "uat_manual",
  "origen_id" => isset($opciones["origen_id"]) ? trim((string)$opciones["origen_id"]) : ""
);

echo json_encode((new ClientesCrm())->interaccionDryRun($datos), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
