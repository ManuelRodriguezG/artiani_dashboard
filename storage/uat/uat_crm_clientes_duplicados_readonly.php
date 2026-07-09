<?php

/**
 * IA: Codex GPT-5
 * Fecha: 2026-06-29
 * Proposito: listar duplicados probables CRM en modo read-only.
 * Impacto: apoya revision antes de migrar o fusionar clientes.
 * Contrato: no modifica fuentes ni crea cola de fusion.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/modelos/ClientesCrm.php";

$opciones = getopt("", array("limite::"));
$limite = isset($opciones["limite"]) ? intval($opciones["limite"]) : 50;
$modelo = new ClientesCrm();
$respuesta = $modelo->duplicadosProbablesDryRun($limite);
$depurar = isset($respuesta["depurar"]) && is_array($respuesta["depurar"]) ? $respuesta["depurar"] : array();

echo json_encode(array(
  "ok" => empty($respuesta["error"]),
  "modo" => "read-only",
  "mensaje" => isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : "",
  "total_grupos" => isset($depurar["total_grupos"]) ? $depurar["total_grupos"] : 0,
  "limite" => isset($depurar["limite"]) ? $depurar["limite"] : $limite,
  "grupos" => isset($depurar["grupos"]) ? $depurar["grupos"] : array()
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
