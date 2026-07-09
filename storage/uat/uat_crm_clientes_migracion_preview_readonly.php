<?php

/**
 * IA: Codex GPT-5
 * Fecha: 2026-06-29
 * Proposito: previsualizar mapeo de legacy crm_clientes a CRM canonico.
 * Impacto: permite validar transformacion antes de autorizar una migracion real.
 * Contrato: no inserta clientes, identificadores ni vinculos.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/modelos/ClientesCrm.php";

$opciones = getopt("", array("limite::"));
$limite = isset($opciones["limite"]) ? intval($opciones["limite"]) : 25;
$modelo = new ClientesCrm();
$respuesta = $modelo->previewMigracionLegacyDryRun($limite);
$depurar = isset($respuesta["depurar"]) && is_array($respuesta["depurar"]) ? $respuesta["depurar"] : array();

echo json_encode(array(
  "ok" => empty($respuesta["error"]),
  "modo" => "read-only",
  "mensaje" => isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : "",
  "limite" => isset($depurar["limite"]) ? $depurar["limite"] : $limite,
  "preview_total" => isset($depurar["preview"]) && is_array($depurar["preview"]) ? count($depurar["preview"]) : 0,
  "preview" => isset($depurar["preview"]) ? $depurar["preview"] : array()
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
