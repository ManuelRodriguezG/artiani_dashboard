<?php

/**
 * IA: Codex GPT-5
 * Fecha: 2026-06-29
 * Proposito: aplicar DDL CRM Clientes solo con token y respaldo externo.
 * Impacto: crea esquema canonico CRM; no migra clientes ni vincula fuentes.
 * Contrato: bloqueado por defecto; requiere --autorizar=CRM_CLIENTES_DDL_BASE y --respaldo.
 */

require_once __DIR__ . "/../../app/iniciador.php";
require_once __DIR__ . "/../../app/core/DBSchema.php";
require_once __DIR__ . "/../../app/modelos/ClientesCrmEsquema.php";

$opciones = getopt("", array("autorizar::", "respaldo::"));
$autorizar = isset($opciones["autorizar"]) ? trim((string) $opciones["autorizar"]) : "";
$respaldo = isset($opciones["respaldo"]) ? trim((string) $opciones["respaldo"]) : "";
$validacion = validar_respaldo_crm_clientes($respaldo);

if ($autorizar !== "CRM_CLIENTES_DDL_BASE" || !$validacion["ok"]) {
  echo json_encode(array(
    "ok" => false,
    "modo" => "bloqueado",
    "mensaje" => "No se ejecuto DDL CRM Clientes. Falta token o respaldo valido.",
    "requerido" => array(
      "autorizar" => "CRM_CLIENTES_DDL_BASE",
      "respaldo" => "RUTA_O_REFERENCIA"
    ),
    "validacion_respaldo" => $validacion,
    "alcance" => array(
      "crea_esquema_crm" => true,
      "migra_legacy" => false,
      "vincula_pos_ecommerce" => false,
      "toca_ventas" => false
    )
  ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
  exit;
}

$modelo = new ClientesCrmEsquema();
$respuesta = $modelo->planActualizarClientesCrm(true);

echo json_encode(array(
  "ok" => empty($respuesta["error"]),
  "modo" => "apply_authorized",
  "mensaje" => isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : "",
  "respuesta" => $respuesta
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

function validar_respaldo_crm_clientes($respaldo) {
  $esRutaLocal = preg_match('/^[A-Za-z]:[\\\\\\/]/', $respaldo) === 1 || strpos($respaldo, "\\") !== false || strpos($respaldo, "/") !== false;
  $existe = false;
  $legible = false;
  $tamano = null;
  if ($respaldo !== "" && $esRutaLocal) {
    $existe = file_exists($respaldo);
    $legible = $existe && is_readable($respaldo);
    $tamano = $existe ? filesize($respaldo) : null;
  }
  $okReferencia = strlen($respaldo) >= 8;
  $okRuta = !$esRutaLocal || ($existe && $legible && $tamano !== null && $tamano > 0);
  return array(
    "ok" => $okReferencia && $okRuta,
    "referencia_presente" => $okReferencia,
    "parece_ruta_local" => $esRutaLocal,
    "archivo_existe" => $esRutaLocal ? $existe : null,
    "archivo_legible" => $esRutaLocal ? $legible : null,
    "tamano_bytes" => $tamano
  );
}
