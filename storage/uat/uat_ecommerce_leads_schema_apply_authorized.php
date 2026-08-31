<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-08-31.
 * Proposito: aplicar DDL Ecommerce Leads solo con autorizacion explicita y respaldo documentado.
 * Impacto: crea tablas de carritos/leads; no activa persistencia publica ni registra pedidos/ventas.
 * Contrato: apply_authorized; requiere --autorizar=ECOMMERCE_LEADS_DDL y --respaldo=...
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/core/DBSchema.php";
require_once "../app/modelos/EcommerceLeadsEsquema.php";

$args = argumentos($argv);
$token = $args["autorizar"] ?? "";
$respaldo = $args["respaldo"] ?? "";
$tokenEsperado = "ECOMMERCE_LEADS_DDL";

if ($token !== $tokenEsperado) {
  salida(false, "Token de autorizacion invalido o ausente", array(
    "modo" => "apply_authorized",
    "ejecutado" => false,
    "token_requerido" => $tokenEsperado,
    "uso" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_leads_schema_apply_authorized.php --autorizar=ECOMMERCE_LEADS_DDL --respaldo=C:\\xampp\\panel_db_backups\\[ARCHIVO].sql"
  ));
}

if ($respaldo === "" || stripos($respaldo, "C:\\xampp\\panel_db_backups\\") !== 0 || !is_file($respaldo)) {
  salida(false, "Respaldo externo requerido en C:\\xampp\\panel_db_backups", array(
    "modo" => "apply_authorized",
    "ejecutado" => false,
    "respaldo_recibido" => $respaldo,
    "guardrail" => "No aplicar DDL sin respaldo existente."
  ));
}

$schema = new EcommerceLeadsEsquema();
$antes = $schema->auditarEcommerceLeads();
$plan = $schema->planActualizarEcommerceLeads(true);
$despues = $schema->auditarEcommerceLeads();
$ok = empty($plan["error"])
  && intval($despues["depurar"]["tablas_faltantes"] ?? 1) === 0
  && intval($despues["depurar"]["columnas_faltantes_total"] ?? 1) === 0
  && intval($despues["depurar"]["indices_faltantes_total"] ?? 1) === 0;

salida($ok, $ok ? "DDL Ecommerce Leads aplicado" : "DDL Ecommerce Leads con pendientes", array(
  "modo" => "apply_authorized",
  "ejecutado" => true,
  "respaldo" => $respaldo,
  "antes" => $antes["depurar"] ?? array(),
  "plan" => $plan["depurar"] ?? array(),
  "despues" => $despues["depurar"] ?? array(),
  "guardrails" => array(
    "no_activa_persistencia_publica" => true,
    "no_registra_leads" => true,
    "no_crea_pedido" => true,
    "no_crea_venta" => true,
    "no_toca_inventario" => true
  )
));

function argumentos($argv) {
  $salida = array();
  foreach ($argv as $arg) {
    if (strpos($arg, "--") !== 0 || strpos($arg, "=") === false) { continue; }
    $partes = explode("=", substr($arg, 2), 2);
    $salida[$partes[0]] = $partes[1];
  }
  return $salida;
}

function salida($ok, $mensaje, $depurar) {
  echo json_encode(array(
    "ok" => $ok,
    "mensaje" => $mensaje,
    "depurar" => $depurar
  ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
  exit($ok ? 0 : 1);
}
