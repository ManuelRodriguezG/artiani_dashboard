<?php

/**
 * IA: Codex GPT-5
 * Fecha: 2026-06-30
 * Proposito: verificar recompensas dentro de la ficha CRM de un cliente.
 * Impacto: confirma que la ficha expone saldo, cuentas y movimientos en lectura.
 * Contrato: read-only; no modifica BD.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/modelos/ClientesCrm.php";

$opciones = getopt("", array("cliente::"));
$idCliente = isset($opciones["cliente"]) ? intval($opciones["cliente"]) : 1;
$respuesta = (new ClientesCrm())->consultarFicha($idCliente);
$depurar = isset($respuesta["depurar"]) && is_array($respuesta["depurar"]) ? $respuesta["depurar"] : array();
$recompensas = isset($depurar["recompensas"]) && is_array($depurar["recompensas"]) ? $depurar["recompensas"] : array();
$resumen = isset($recompensas["resumen"]) ? $recompensas["resumen"] : array();

echo json_encode(array(
  "error" => !empty($respuesta["error"]),
  "tipo" => !empty($respuesta["error"]) ? "warning" : "success",
  "mensaje" => !empty($respuesta["error"]) ? $respuesta["mensaje"] : "Ficha CRM recompensas verificada",
  "depurar" => array(
    "id_cliente_crm" => $idCliente,
    "recompensas_disponible" => !empty($recompensas["disponible"]),
    "cuentas" => intval(isset($resumen["cuentas"]) ? $resumen["cuentas"] : 0),
    "movimientos" => intval(isset($resumen["movimientos"]) ? $resumen["movimientos"] : 0),
    "saldo_puntos_total" => floatval(isset($resumen["saldo_puntos_total"]) ? $resumen["saldo_puntos_total"] : 0),
    "no_escribe_bd" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
