<?php

/**
 * IA: Codex GPT-5
 * Fecha: 2026-06-29
 * Proposito: listar clientes CRM canonicos en modo lectura.
 * Impacto: valida que el listado operativo no depende de legacy/POS.
 * Contrato: no escribe BD.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/modelos/ClientesCrm.php";

$opciones = getopt("", array("q::", "limite::"));
$modelo = new ClientesCrm();
$respuesta = $modelo->listarClientesCanonicos(array(
  "q" => isset($opciones["q"]) ? $opciones["q"] : "",
  "limite" => isset($opciones["limite"]) ? intval($opciones["limite"]) : 25
));
$depurar = isset($respuesta["depurar"]) && is_array($respuesta["depurar"]) ? $respuesta["depurar"] : array();

echo json_encode(array(
  "ok" => empty($respuesta["error"]),
  "modo" => "read-only",
  "mensaje" => isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : "",
  "total" => isset($depurar["total"]) ? $depurar["total"] : 0,
  "clientes" => isset($depurar["clientes"]) ? $depurar["clientes"] : array()
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
