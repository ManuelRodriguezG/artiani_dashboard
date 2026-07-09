<?php

/**
 * IA: Codex GPT-5
 * Fecha: 2026-06-29
 * Proposito: generar lote de migracion legacy como borrador no aplicado.
 * Impacto: clasifica registros migrables, duplicados y pendientes de revision.
 * Contrato: no inserta clientes, identificadores ni vinculos.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/modelos/ClientesCrm.php";

$opciones = getopt("", array("offset::", "limite::"));
$offset = isset($opciones["offset"]) ? intval($opciones["offset"]) : 0;
$limite = isset($opciones["limite"]) ? intval($opciones["limite"]) : 25;

$modelo = new ClientesCrm();
$respuesta = $modelo->migracionLegacyBorradorDryRun($offset, $limite);
$depurar = isset($respuesta["depurar"]) && is_array($respuesta["depurar"]) ? $respuesta["depurar"] : array();

echo json_encode(array(
  "ok" => empty($respuesta["error"]),
  "modo" => "read-only",
  "mensaje" => isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : "",
  "resumen" => isset($depurar["resumen"]) ? $depurar["resumen"] : array(),
  "lote" => isset($depurar["lote"]) ? $depurar["lote"] : array(),
  "no_inserta" => true
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
