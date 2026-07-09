<?php

/**
 * IA: Codex GPT-5
 * Fecha: 2026-06-29
 * Proposito: consultar ficha CRM completa en modo lectura.
 * Impacto: valida cliente canonico, identificadores, contactos, fiscal, notas e historial.
 * Contrato: no escribe BD.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/modelos/ClientesCrm.php";

$opciones = getopt("", array("id::"));
$id = isset($opciones["id"]) ? intval($opciones["id"]) : 1;
$modelo = new ClientesCrm();
$respuesta = $modelo->consultarFicha($id);
$depurar = isset($respuesta["depurar"]) && is_array($respuesta["depurar"]) ? $respuesta["depurar"] : array();

echo json_encode(array(
  "ok" => empty($respuesta["error"]),
  "modo" => "read-only",
  "mensaje" => isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : "",
  "cliente" => isset($depurar["cliente"]) ? $depurar["cliente"] : null,
  "conteos" => array(
    "identificadores" => isset($depurar["identificadores"]) ? count($depurar["identificadores"]) : 0,
    "contactos" => isset($depurar["contactos"]) ? count($depurar["contactos"]) : 0,
    "direcciones" => isset($depurar["direcciones"]) ? count($depurar["direcciones"]) : 0,
    "fiscales" => isset($depurar["fiscales"]) ? count($depurar["fiscales"]) : 0,
    "consentimientos" => isset($depurar["consentimientos"]) ? count($depurar["consentimientos"]) : 0,
    "notas" => isset($depurar["notas"]) ? count($depurar["notas"]) : 0,
    "eventos" => isset($depurar["eventos"]) ? count($depurar["eventos"]) : 0,
    "vinculos_externos" => isset($depurar["vinculos_externos"]) ? count($depurar["vinculos_externos"]) : 0
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
