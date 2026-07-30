<?php

/**
 * IA: Codex GPT-5
 * Fecha: 2026-07-30
 * Proposito: validar comprobante logistico TMS read-only.
 * Impacto: TMS Delivery; confirma ticket ARTIANI Entregas sin escribir BD ni tocar POS/Ventas.
 * Contrato: read-only; usa folio POS/TMS UAT si no se envia referencia.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/CRUD.php";
require_once __DIR__ . "/../../app/modelos/TmsDelivery.php";

$referencia = argumento("--referencia");
if ($referencia === "") {
  $referencia = "POS-SOL-UAT-20260729-210207";
}

$modelo = new TmsDelivery();
$ticket = $modelo->ticketServicioReadOnly(array("referencia_externa" => $referencia));
$texto = isset($ticket["depurar"]["ticket_texto"]) ? (string) $ticket["depurar"]["ticket_texto"] : "";
$config = isset($ticket["depurar"]["configuracion"]) ? $ticket["depurar"]["configuracion"] : array();
$checks = array(
  "ticket_ok" => isset($ticket["error"]) && $ticket["error"] === false,
  "contiene_nombre_cliente" => strpos($texto, "ARTIANI ENTREGAS") !== false,
  "contiene_comprobante" => strpos($texto, "COMPROBANTE LOGISTICO") !== false,
  "contiene_folio" => strpos($texto, "TMS-20260729-210207-625") !== false,
  "contiene_referencia" => strpos($texto, $referencia) !== false,
  "contiene_cobro_logistico" => strpos($texto, "COBRO LOGISTICO") !== false,
  "contiene_separacion_producto" => strpos($texto, "servicio logistico") !== false,
  "config_nombre" => isset($config["nombre_servicio_cliente"]) && $config["nombre_servicio_cliente"] === "ARTIANI Entregas",
  "config_80mm" => isset($config["ticket_ancho_mm"]) && intval($config["ticket_ancho_mm"]) === 80,
  "no_escritura" => isset($ticket["depurar"]["no_escritura_bd"]) && $ticket["depurar"]["no_escritura_bd"] === true
);
$fallos = array_keys(array_filter($checks, function ($ok) {
  return !$ok;
}));

echo json_encode(array(
  "ok" => empty($fallos),
  "modo" => "read-only",
  "estado" => empty($fallos) ? "tms_ticket_readonly_listo" : "tms_ticket_readonly_fallos",
  "checks_total" => count($checks),
  "checks_ok" => count($checks) - count($fallos),
  "checks_fallos" => count($fallos),
  "fallos" => $fallos,
  "referencia" => $referencia,
  "ticket" => $ticket,
  "preview" => $texto
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

function argumento($prefijo) {
  global $argv;
  foreach ($argv as $arg) {
    if (strpos($arg, $prefijo . "=") === 0) {
      return trim(substr($arg, strlen($prefijo) + 1), "\"' ");
    }
  }
  return "";
}
