<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-08-31.
 * Proposito: confirmar estado activo de Ecommerce Leads y conteos basicos sin modificar BD.
 * Impacto: evidencia post-activacion para frontend y operacion.
 * Contrato: read-only; no expone credenciales.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/EcommerceLeadsErp.php";

$modelo = new EcommerceLeadsErp();
$contrato = $modelo->contratoFrontend();
$db = new PDO(
  "mysql:host=" . MYSQLHOST . ";port=" . MYSQLPORT . ";dbname=" . MYSQLBASE . ";charset=utf8mb4",
  MYSQLUSER,
  MYSQLPASS,
  array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
);

$tablas = array(
  "erp_ecommerce_leads_carritos",
  "erp_ecommerce_leads_carrito_items",
  "erp_ecommerce_leads_eventos",
  "erp_ecommerce_leads_notas"
);
$conteos = array();
foreach ($tablas as $tabla) {
  $conteos[$tabla] = (int) $db->query("SELECT COUNT(*) FROM `" . $tabla . "`")->fetchColumn();
}

$estado = $contrato["depurar"]["estado"] ?? "";
$guardrails = $contrato["depurar"]["guardrails"] ?? array();
echo json_encode(array(
  "ok" => $estado === "persistencia_publica_activa" && empty($guardrails["no_escribe_bd"]),
  "base" => MYSQLBASE,
  "host" => MYSQLHOST,
  "port" => MYSQLPORT,
  "estado" => $estado,
  "flag_activa" => defined("ECOMMERCE_LEADS_PUBLICO") && ECOMMERCE_LEADS_PUBLICO === true,
  "no_escribe_bd" => !empty($guardrails["no_escribe_bd"]),
  "conteos" => $conteos,
  "guardrails" => $guardrails
), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
