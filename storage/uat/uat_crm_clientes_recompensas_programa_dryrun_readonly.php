<?php

/**
 * IA: Codex GPT-5
 * Fecha: 2026-06-30
 * Proposito: validar programa CRM Recompensas sin escribir BD.
 * Impacto: prueba reglas de programa antes de pedir autorizacion.
 * Contrato: read-only; no crea programa, cuentas, movimientos ni puntos.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/modelos/ClientesCrm.php";

$opciones = getopt("", array("codigo::", "nombre::", "tipo::", "reglas::"));
$datos = array(
  "codigo" => isset($opciones["codigo"]) ? trim((string)$opciones["codigo"]) : "PUNTOS_BASE",
  "nombre" => isset($opciones["nombre"]) ? trim((string)$opciones["nombre"]) : "Programa base de puntos",
  "tipo" => isset($opciones["tipo"]) ? trim((string)$opciones["tipo"]) : "puntos",
  "estatus" => "activo",
  "reglas" => isset($opciones["reglas"]) ? (string)$opciones["reglas"] : json_encode(array(
    "acumulacion" => array("base" => "monto_pagado", "puntos_por_unidad" => 1, "unidad_monto" => 10, "incluye_impuestos" => true),
    "redencion" => array("modo" => "pendiente", "valor_punto" => 0, "minimo_puntos" => 0),
    "caducidad" => array("dias" => 0),
    "restricciones" => array("legacy_requiere_revision" => true),
    "notas" => "Programa candidato; no conectar POS hasta definir redencion."
  ), JSON_UNESCAPED_UNICODE)
);

echo json_encode((new ClientesCrm())->recompensaProgramaDryRun($datos), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
