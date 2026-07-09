<?php

/**
 * IA: Codex GPT-5
 * Fecha: 2026-06-29
 * Proposito: generar plan DDL CRM Clientes sin ejecutar.
 * Impacto: permite revisar tablas canonicas antes de pedir respaldo/autorizacion.
 * Contrato: read-only; no crea tablas.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/DBSchema.php";
require_once __DIR__ . "/../../app/modelos/ClientesCrmEsquema.php";

$modelo = new ClientesCrmEsquema();
$respuesta = $modelo->planActualizarClientesCrm(false);
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
  "token_apply" => "CRM_CLIENTES_DDL_BASE",
  "respuesta" => $respuesta
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
