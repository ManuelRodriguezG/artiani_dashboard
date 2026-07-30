<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-29.
 * Proposito: aplicar DDL de experiencia cliente ecommerce solo con token y respaldo externo.
 * Impacto: crea tablas para politicas, facturacion, analitica y taxonomia; no activa persistencia publica.
 * Contrato: bloqueado por defecto; requiere --autorizar=ECOMMERCE_PUBLICO_EXPERIENCIA_CLIENTE_DDL y --respaldo.
 */

$opciones = getopt("", array("autorizar::", "respaldo::"));
$autorizar = isset($opciones["autorizar"]) ? trim((string) $opciones["autorizar"]) : "";
$respaldo = isset($opciones["respaldo"]) ? trim((string) $opciones["respaldo"]) : "";
$validacion = validarRespaldoExperienciaCliente($respaldo);

if ($autorizar !== "ECOMMERCE_PUBLICO_EXPERIENCIA_CLIENTE_DDL" || !$validacion["ok"]) {
  echo json_encode(array(
    "ok" => false,
    "modo" => "bloqueado",
    "mensaje" => "No se ejecuto DDL experiencia cliente ecommerce. Falta token o respaldo valido.",
    "requerido" => array(
      "autorizar" => "ECOMMERCE_PUBLICO_EXPERIENCIA_CLIENTE_DDL",
      "respaldo" => "C:\\xampp\\panel_db_backups\\[ARCHIVO].sql"
    ),
    "validacion_respaldo" => $validacion,
    "alcance" => array(
      "crea_tablas_politicas" => true,
      "crea_tabla_facturacion_solicitudes" => true,
      "crea_tablas_analytics" => true,
      "crea_taxonomia_mascotas" => true,
      "activa_post_persistente" => false,
      "emite_facturas" => false,
      "crea_clientes" => false,
      "crea_pedidos" => false,
      "toca_inventario" => false,
      "toca_ecom_legacy" => false
    ),
    "siguiente_post_apply" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_experiencia_cliente_postcheck_readonly.php"
  ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
  exit;
}

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/core/DBSchema.php";
require_once "../app/modelos/EcommercePublicoEsquema.php";

$modelo = new EcommercePublicoEsquema();
$respuesta = $modelo->planActualizarExperienciaCliente(true);

echo json_encode(array(
  "ok" => empty($respuesta["error"]),
  "modo" => "apply_authorized",
  "mensaje" => isset($respuesta["mensaje"]) ? $respuesta["mensaje"] : "",
  "validacion_respaldo" => $validacion,
  "respuesta" => $respuesta,
  "guardrails" => array(
    "no_activa_post_persistente" => true,
    "no_emite_facturas" => true,
    "no_crea_clientes" => true,
    "no_crea_pedidos" => true,
    "no_toca_inventario" => true,
    "no_toca_ecom_legacy" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function validarRespaldoExperienciaCliente($respaldo) {
  $esRutaLocal = preg_match('/^[A-Za-z]:[\\\\\\/]/', $respaldo) === 1 || strpos($respaldo, "\\") !== false || strpos($respaldo, "/") !== false;
  $existe = false;
  $legible = false;
  $tamano = null;
  if ($respaldo !== "" && $esRutaLocal) {
    $existe = file_exists($respaldo);
    $legible = $existe && is_readable($respaldo);
    $tamano = $existe ? filesize($respaldo) : null;
  }
  $placeholder = respaldoPlaceholderExperienciaCliente($respaldo);
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

function respaldoPlaceholderExperienciaCliente($valor) {
  $valor = strtoupper(trim((string) $valor));
  return $valor === ""
    || strpos($valor, "RUTA_O_REFERENCIA") !== false
    || strpos($valor, "[ARCHIVO]") !== false
    || strpos($valor, "REVISION_READONLY") !== false
    || strpos($valor, "PLACEHOLDER") !== false;
}
