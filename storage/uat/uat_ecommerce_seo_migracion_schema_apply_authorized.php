<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-09-03.
 * Proposito: aplicar DDL SEO/migracion ecommerce solo con autorizacion explicita y respaldo documentado.
 * Impacto: crea tablas SEO nuevas; no importa URLs, no aprueba redirecciones y no toca frontend.
 * Contrato: apply_authorized; requiere --autorizar=ECOMMERCE_SEO_MIGRACION_DDL y respaldo en C:\xampp\panel_db_backups.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/core/DBSchema.php";
require_once "../app/modelos/EcommercePublicoEsquema.php";

$args = argumentos($argv);
$token = isset($args["autorizar"]) ? $args["autorizar"] : "";
$respaldo = isset($args["respaldo"]) ? $args["respaldo"] : "";
$tokenEsperado = "ECOMMERCE_SEO_MIGRACION_DDL";

if ($token !== $tokenEsperado) {
  salida(false, "Token de autorizacion invalido o ausente", array(
    "modo" => "apply_authorized",
    "ejecutado" => false,
    "token_requerido" => $tokenEsperado,
    "uso" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_seo_migracion_schema_apply_authorized.php --autorizar=ECOMMERCE_SEO_MIGRACION_DDL --respaldo=C:\\xampp\\panel_db_backups\\[ARCHIVO].sql"
  ));
}

if ($respaldo === "" || stripos($respaldo, "C:\\xampp\\panel_db_backups\\") !== 0 || !is_file($respaldo)) {
  salida(false, "Respaldo externo requerido en C:\\xampp\\panel_db_backups", array(
    "modo" => "apply_authorized",
    "ejecutado" => false,
    "respaldo_recibido" => $respaldo,
    "guardrail" => "No aplicar DDL SEO/migracion sin respaldo existente."
  ));
}

$schema = new EcommercePublicoEsquema();
$antes = $schema->auditarSeoMigracion();
$plan = $schema->planActualizarSeoMigracion(true);
$despues = $schema->auditarSeoMigracion();
$ok = empty($plan["error"]) && intval(isset($despues["depurar"]["tablas_faltantes"]) ? $despues["depurar"]["tablas_faltantes"] : 1) === 0;

salida($ok, $ok ? "DDL SEO/migracion ecommerce aplicado" : "DDL SEO/migracion con pendientes", array(
  "modo" => "apply_authorized",
  "ejecutado" => true,
  "respaldo" => $respaldo,
  "antes" => isset($antes["depurar"]) ? $antes["depurar"] : array(),
  "plan" => isset($plan["depurar"]) ? $plan["depurar"] : array(),
  "despues" => isset($despues["depurar"]) ? $despues["depurar"] : array(),
  "guardrails" => array(
    "no_importa_urls" => true,
    "no_crea_redirecciones" => true,
    "no_activa_301" => true,
    "no_toca_frontend" => true,
    "no_toca_inventario" => true
  )
));

function argumentos($argv) {
  $salida = array();
  foreach ((array) $argv as $arg) {
    if (strpos($arg, "--") !== 0 || strpos($arg, "=") === false) { continue; }
    $partes = explode("=", substr($arg, 2), 2);
    $salida[$partes[0]] = trim($partes[1], "\"' ");
  }
  return $salida;
}

function salida($ok, $mensaje, $depurar) {
  echo json_encode(array(
    "ok" => $ok,
    "mensaje" => $mensaje,
    "depurar" => $depurar
  ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
  exit($ok ? 0 : 1);
}
