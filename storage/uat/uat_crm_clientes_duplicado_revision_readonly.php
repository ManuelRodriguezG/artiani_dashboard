<?php

/**
 * IA: Codex GPT-5
 * Fecha: 2026-06-29
 * Proposito: revisar un grupo duplicado CRM/legacy sin marcar ni fusionar.
 * Impacto: prepara cola de decision humana previa a migracion.
 * Contrato: no escribe BD.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/modelos/ClientesCrm.php";

$opciones = getopt("", array("identificador::"));
$identificador = isset($opciones["identificador"]) ? $opciones["identificador"] : "telefono:3322068429";
$modelo = new ClientesCrm();
$respuesta = $modelo->duplicadoRevisionDryRun($identificador);
$depurar = isset($respuesta["depurar"]) && is_array($respuesta["depurar"]) ? $respuesta["depurar"] : array();

echo json_encode(array(
  "ok" => empty($respuesta["error"]),
  "modo" => "read-only",
  "mensaje" => isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : "",
  "identificador" => isset($depurar["identificador"]) ? $depurar["identificador"] : null,
  "total_items" => isset($depurar["total_items"]) ? $depurar["total_items"] : 0,
  "fuentes" => isset($depurar["fuentes"]) ? $depurar["fuentes"] : array(),
  "severidad" => isset($depurar["severidad"]) ? $depurar["severidad"] : "",
  "recomendacion" => isset($depurar["recomendacion"]) ? $depurar["recomendacion"] : array(),
  "items" => isset($depurar["items"]) ? $depurar["items"] : array()
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
