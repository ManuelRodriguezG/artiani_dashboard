<?php

/**
 * IA: Codex GPT-5
 * Fecha: 2026-07-24
 * Proposito: aplicar DDL TMS Delivery solo con autorizacion explicita.
 * Impacto: crea tablas `erp_tms_*`; no crea servicios, no toca Ventas, garantias ni inventario.
 * Contrato: bloqueado por defecto; requiere --autorizar=TMS_DELIVERY_DDL_BASE y --respaldo valido.
 */

$opciones = getopt("", array("autorizar::", "respaldo::"));
$autorizar = isset($opciones["autorizar"]) ? trim((string) $opciones["autorizar"]) : "";
$respaldo = isset($opciones["respaldo"]) ? trim((string) $opciones["respaldo"]) : "";
$validacion = validar_respaldo_tms_schema($respaldo);

if ($autorizar !== "TMS_DELIVERY_DDL_BASE" || !$validacion["ok"]) {
  echo json_encode(array(
    "ok" => false,
    "modo" => "bloqueado",
    "mensaje" => "No se ejecuto DDL TMS Delivery. Falta token o respaldo valido.",
    "requerido" => array(
      "autorizar" => "TMS_DELIVERY_DDL_BASE",
      "respaldo" => "RUTA_O_REFERENCIA"
    ),
    "validacion_respaldo" => $validacion,
    "alcance" => array(
      "crea_tablas_erp_tms" => true,
      "crea_servicios_tms" => false,
      "toca_ventas" => false,
      "toca_pos" => false,
      "toca_inventario" => false,
      "toca_garantias" => false,
      "sincroniza_permisos" => false
    )
  ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
  exit;
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/core/DBSchema.php";
require_once "../app/modelos/TmsEsquema.php";

$modelo = new TmsEsquema();
$respuesta = $modelo->planActualizarTmsDelivery(true);

echo json_encode(array(
  "ok" => empty($respuesta["error"]),
  "modo" => "tms_delivery_schema_apply_authorized",
  "mensaje" => isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : "",
  "validacion_respaldo" => $validacion,
  "respuesta" => $respuesta
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

function validar_respaldo_tms_schema($respaldo) {
  $esRutaLocal = preg_match('/^[A-Za-z]:[\\\\\\/]/', $respaldo) === 1 || strpos($respaldo, "\\") !== false || strpos($respaldo, "/") !== false;
  $existe = false;
  $legible = false;
  $tamano = null;
  if ($respaldo !== "" && $esRutaLocal) {
    $existe = file_exists($respaldo);
    $legible = $existe && is_readable($respaldo);
    $tamano = $existe ? filesize($respaldo) : null;
  }
  $placeholder = respaldo_placeholder_tms_schema($respaldo);
  $okReferencia = strlen($respaldo) >= 8 && !$placeholder;
  $okRuta = !$esRutaLocal || ($existe && $legible && $tamano !== null && $tamano > 0);
  return array(
    "ok" => $okReferencia && $okRuta,
    "referencia_presente" => $okReferencia,
    "referencia" => $respaldo,
    "parece_ruta_local" => $esRutaLocal,
    "archivo_existe" => $esRutaLocal ? $existe : null,
    "archivo_legible" => $esRutaLocal ? $legible : null,
    "tamano_bytes" => $tamano,
    "placeholder_bloqueado" => $placeholder
  );
}

function respaldo_placeholder_tms_schema($valor) {
  $valor = strtoupper(trim((string) $valor));
  return $valor === ""
    || strpos($valor, "RUTA_O_REFERENCIA") !== false
    || strpos($valor, "RUTA_RESPALDO") !== false
    || strpos($valor, "PLACEHOLDER") !== false;
}
