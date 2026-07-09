<?php

/**
 * IA: Codex GPT-5
 * Fecha: 2026-06-29
 * Proposito: validar complemento CRM sin escribir.
 * Impacto: prueba contactos, direcciones, fiscales y notas antes del apply.
 * Contrato: no inserta BD.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/modelos/ClientesCrm.php";

$opciones = getopt("", array("id::", "tipo_complemento::", "tipo::", "valor::", "nombre::", "rfc::", "razon::", "calle::", "cp::", "nota::"));
$tipoComplemento = isset($opciones["tipo_complemento"]) ? $opciones["tipo_complemento"] : "contacto";
$datos = array(
  "id_cliente_crm" => isset($opciones["id"]) ? intval($opciones["id"]) : 1,
  "tipo" => isset($opciones["tipo"]) ? $opciones["tipo"] : "telefono",
  "valor" => isset($opciones["valor"]) ? $opciones["valor"] : "3312345678",
  "nombre_contacto" => isset($opciones["nombre"]) ? $opciones["nombre"] : "",
  "rfc" => isset($opciones["rfc"]) ? $opciones["rfc"] : "XAXX010101000",
  "razon_social" => isset($opciones["razon"]) ? $opciones["razon"] : "Cliente Express UAT",
  "calle" => isset($opciones["calle"]) ? $opciones["calle"] : "Calle UAT",
  "codigo_postal" => isset($opciones["cp"]) ? $opciones["cp"] : "00000",
  "nota" => isset($opciones["nota"]) ? $opciones["nota"] : "Nota operativa UAT"
);

$modelo = new ClientesCrm();
$respuesta = $modelo->complementoGuardarDryRun($tipoComplemento, $datos);
echo json_encode(array(
  "ok" => empty($respuesta["error"]),
  "modo" => "dry-run",
  "mensaje" => isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : "",
  "depurar" => isset($respuesta["depurar"]) ? $respuesta["depurar"] : array()
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
