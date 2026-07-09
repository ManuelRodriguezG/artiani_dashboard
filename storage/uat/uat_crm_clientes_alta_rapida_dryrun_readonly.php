<?php

/**
 * IA: Codex GPT-5
 * Fecha: 2026-06-29
 * Proposito: validar alta express CRM sin escribir registros.
 * Impacto: confirma que POS/CRM pueden proponer cliente canonico con identificador unico.
 * Contrato: dry-run; no inserta cliente, identificador, consentimiento ni evento.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/modelos/ClientesCrm.php";

$opciones = getopt("", array("nombre::", "identificador::", "almacen::", "consentimiento::"));
$datos = array(
  "nombre_publico" => isset($opciones["nombre"]) ? $opciones["nombre"] : "Cliente express UAT",
  "identificador" => isset($opciones["identificador"]) ? $opciones["identificador"] : "3312345678",
  "id_almacen" => isset($opciones["almacen"]) ? intval($opciones["almacen"]) : 1,
  "consentimiento_contacto" => isset($opciones["consentimiento"]) ? intval($opciones["consentimiento"]) : 0,
  "origen_alta" => "pos_uat"
);

$modelo = new ClientesCrm();
$respuesta = $modelo->altaRapidaDryRun($datos);
$depurar = isset($respuesta["depurar"]) && is_array($respuesta["depurar"]) ? $respuesta["depurar"] : array();

echo json_encode(array(
  "ok" => empty($respuesta["error"]),
  "modo" => "read-only",
  "mensaje" => isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : "",
  "puede_crear" => isset($depurar["puede_crear"]) ? $depurar["puede_crear"] : false,
  "bloqueos" => isset($depurar["bloqueos"]) ? $depurar["bloqueos"] : array(),
  "avisos" => isset($depurar["avisos"]) ? $depurar["avisos"] : array(),
  "cliente_propuesto" => isset($depurar["cliente_propuesto"]) ? $depurar["cliente_propuesto"] : array(),
  "identificador_propuesto" => isset($depurar["identificador_propuesto"]) ? $depurar["identificador_propuesto"] : array(),
  "coincidencias" => isset($depurar["coincidencias"]) ? $depurar["coincidencias"] : array(),
  "no_escribe_bd" => true
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
