<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-09-10.
 * Proposito: sincronizar URLs canonicas ecommerce hacia `erp_ecommerce_seo_urls` desde CLI.
 * Impacto: registra snapshot de rutas publicas actuales para frontend/SEO; no crea redirecciones ni toca inventario.
 * Contrato: apply_authorized; requiere token `ECOMMERCE_SEO_SYNC_URLS_CANONICAS` y respaldo externo existente.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/EcommerceCatalogoPublico.php";

$args = argumentos($argv);
$token = isset($args["autorizar"]) ? $args["autorizar"] : "";
$respaldo = isset($args["respaldo"]) ? $args["respaldo"] : "";
$limite = isset($args["limite"]) ? intval($args["limite"]) : 500;
$tokenEsperado = "ECOMMERCE_SEO_SYNC_URLS_CANONICAS";

if ($token !== $tokenEsperado) {
  salida(false, "Token de autorizacion invalido o ausente", array(
    "modo" => "apply_authorized",
    "ejecutado" => false,
    "token_requerido" => $tokenEsperado
  ));
}

if ($respaldo === "" || stripos($respaldo, "C:\\xampp\\panel_db_backups\\") !== 0 || !is_file($respaldo) || filesize($respaldo) <= 0) {
  salida(false, "Respaldo externo requerido en C:\\xampp\\panel_db_backups", array(
    "modo" => "apply_authorized",
    "ejecutado" => false,
    "respaldo_recibido" => $respaldo
  ));
}

$modelo = new EcommerceCatalogoPublico();
$plan = $modelo->seoUrlsCanonicasSincronizarPlanInterno(array("limite" => $limite));
$respuesta = $modelo->seoUrlsCanonicasSincronizarAutorizado(array(
  "limite" => $limite,
  "autorizar" => $tokenEsperado
));

salida(empty($respuesta["error"]), empty($respuesta["error"]) ? "URLs canonicas ecommerce sincronizadas" : "Sincronizacion de URLs canonicas con pendientes", array(
  "modo" => "apply_authorized",
  "ejecutado" => true,
  "respaldo" => $respaldo,
  "plan_previo" => isset($plan["depurar"]) ? $plan["depurar"] : array(),
  "respuesta" => $respuesta,
  "guardrails" => array(
    "no_crea_redirecciones" => true,
    "no_importa_urls_viejas" => true,
    "no_desactiva_ausentes" => true,
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
