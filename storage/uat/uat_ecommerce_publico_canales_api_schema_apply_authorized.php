<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-26.
 * Proposito: aplicar DDL de canales/API ecommerce solo con token y respaldo externo.
 * Impacto: crea tablas para canales, credenciales, allowlist, nonces y logs; no activa partners.
 * Contrato: bloqueado por defecto; requiere --autorizar=ECOMMERCE_PUBLICO_CANALES_API_DDL y --respaldo.
 */

$opciones = getopt("", array("autorizar::", "respaldo::"));
$autorizar = isset($opciones["autorizar"]) ? trim((string) $opciones["autorizar"]) : "";
$respaldo = isset($opciones["respaldo"]) ? trim((string) $opciones["respaldo"]) : "";
$validacion = validarRespaldoCanalesApi($respaldo);

if ($autorizar !== "ECOMMERCE_PUBLICO_CANALES_API_DDL" || !$validacion["ok"]) {
  echo json_encode(array(
    "ok" => false,
    "modo" => "bloqueado",
    "mensaje" => "No se ejecuto DDL canales API ecommerce. Falta token o respaldo valido.",
    "requerido" => array(
      "autorizar" => "ECOMMERCE_PUBLICO_CANALES_API_DDL",
      "respaldo" => "RUTA_O_REFERENCIA"
    ),
    "validacion_respaldo" => $validacion,
    "alcance" => array(
      "crea_tablas_canales_api" => true,
      "genera_secretos" => false,
      "crea_canales" => false,
      "habilita_partners" => false,
      "habilita_auth_obligatoria" => false,
      "crea_publicaciones" => false,
      "registra_cotizaciones" => false,
      "toca_inventario" => false,
      "toca_pos_ventas" => false,
      "toca_ecom_legacy" => false
    )
  ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
  exit;
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/core/DBSchema.php";
require_once "../app/modelos/EcommercePublicoEsquema.php";

$modelo = new EcommercePublicoEsquema();
$respuesta = $modelo->planActualizarCanalesApi(true);

echo json_encode(array(
  "ok" => empty($respuesta["error"]),
  "modo" => "apply_authorized",
  "mensaje" => isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : "",
  "validacion_respaldo" => $validacion,
  "respuesta" => $respuesta,
  "guardrails" => array(
    "no_genera_secretos" => true,
    "no_activa_partners" => true,
    "no_exige_auth_al_frontend_actual" => true,
    "no_registra_cotizaciones" => true,
    "no_toca_inventario" => true,
    "no_toca_ecom_legacy" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function validarRespaldoCanalesApi($respaldo) {
  $esRutaLocal = preg_match('/^[A-Za-z]:[\\\\\\/]/', $respaldo) === 1 || strpos($respaldo, "\\") !== false || strpos($respaldo, "/") !== false;
  $existe = false;
  $legible = false;
  $tamano = null;
  if ($respaldo !== "" && $esRutaLocal) {
    $existe = file_exists($respaldo);
    $legible = $existe && is_readable($respaldo);
    $tamano = $existe ? filesize($respaldo) : null;
  }
  $placeholder = respaldoPlaceholderCanalesApi($respaldo);
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

function respaldoPlaceholderCanalesApi($valor) {
  $valor = strtoupper(trim((string) $valor));
  return $valor === ""
    || strpos($valor, "RUTA_O_REFERENCIA") !== false
    || strpos($valor, "[ARCHIVO]") !== false
    || strpos($valor, "REVISION_READONLY") !== false
    || strpos($valor, "PLACEHOLDER") !== false;
}
