<?php

/**
 * IA: Codex GPT-5
 * Fecha: 2026-06-29
 * Proposito: validar cambios basicos de ficha CRM sin escribir.
 * Impacto: prueba preflight de edicion antes de crear apply autorizado.
 * Contrato: no actualiza BD.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/modelos/ClientesCrm.php";

$opciones = getopt("", array("id::", "nombre::", "tipo::", "estatus::", "observaciones::"));
$modelo = new ClientesCrm();
$respuesta = $modelo->fichaBasicaGuardarDryRun(array(
  "id_cliente_crm" => isset($opciones["id"]) ? intval($opciones["id"]) : 1,
  "nombre_publico" => isset($opciones["nombre"]) ? $opciones["nombre"] : "Cliente Express UAT",
  "tipo_cliente" => isset($opciones["tipo"]) ? $opciones["tipo"] : "persona",
  "estatus" => isset($opciones["estatus"]) ? $opciones["estatus"] : "activo",
  "observaciones_operativas" => isset($opciones["observaciones"]) ? $opciones["observaciones"] : "Validacion UAT"
));

echo json_encode(array(
  "ok" => empty($respuesta["error"]),
  "modo" => "dry-run",
  "mensaje" => isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : "",
  "depurar" => isset($respuesta["depurar"]) ? $respuesta["depurar"] : array()
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
