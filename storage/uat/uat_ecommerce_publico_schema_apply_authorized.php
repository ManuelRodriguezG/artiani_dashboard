<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-11.
 * Proposito: aplicar DDL ecommerce publico Fase 1 solo con token y respaldo externo.
 * Impacto: crea tablas nuevas `erp_ecommerce_*`; no migra legacy, no publica SKUs y no mueve inventario.
 * Contrato: bloqueado por defecto; requiere --autorizar=ECOMMERCE_PUBLICO_DDL_FASE1 y --respaldo.
 */

$opciones = getopt("", array("autorizar::", "respaldo::"));
$autorizar = isset($opciones["autorizar"]) ? trim((string) $opciones["autorizar"]) : "";
$respaldo = isset($opciones["respaldo"]) ? trim((string) $opciones["respaldo"]) : "";
$validacion = validarRespaldoEcommercePublico($respaldo);

if ($autorizar !== "ECOMMERCE_PUBLICO_DDL_FASE1" || !$validacion["ok"]) {
  echo json_encode(array(
    "ok" => false,
    "modo" => "bloqueado",
    "mensaje" => "No se ejecuto DDL ecommerce publico. Falta token o respaldo valido.",
    "requerido" => array(
      "autorizar" => "ECOMMERCE_PUBLICO_DDL_FASE1",
      "respaldo" => "RUTA_O_REFERENCIA"
    ),
    "validacion_respaldo" => $validacion,
    "alcance" => array(
      "crea_tablas_erp_ecommerce" => true,
      "migra_ecom_legacy" => false,
      "crea_publicaciones" => false,
      "crea_cotizaciones_reales" => false,
      "toca_inventario" => false,
      "toca_pos_ventas" => false,
      "crea_checkout" => false
    )
  ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
  exit;
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/core/DBSchema.php";
require_once "../app/modelos/EcommercePublicoEsquema.php";

$modelo = new EcommercePublicoEsquema();
$respuesta = $modelo->planActualizarEcommercePublico(true);

echo json_encode(array(
  "ok" => empty($respuesta["error"]),
  "modo" => "apply_authorized",
  "mensaje" => isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : "",
  "validacion_respaldo" => $validacion,
  "respuesta" => $respuesta
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;

function validarRespaldoEcommercePublico($respaldo) {
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
    "referencia" => $respaldo,
    "parece_ruta_local" => $esRutaLocal,
    "archivo_existe" => $esRutaLocal ? $existe : null,
    "archivo_legible" => $esRutaLocal ? $legible : null,
    "tamano_bytes" => $tamano
  );
}
