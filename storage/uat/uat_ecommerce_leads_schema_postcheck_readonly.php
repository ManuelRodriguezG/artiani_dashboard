<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-08-31.
 * Proposito: validar postcheck read-only del esquema Ecommerce Leads.
 * Impacto: confirma tablas, columnas e indices antes de activar escritura publica.
 * Contrato: read-only; no ejecuta DDL ni modifica datos.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/core/DBSchema.php";
require_once "../app/modelos/EcommerceLeadsEsquema.php";

$schema = new EcommerceLeadsEsquema();
$auditoria = $schema->auditarEcommerceLeads();
$depurar = $auditoria["depurar"] ?? array();
$tablasFaltantes = intval($depurar["tablas_faltantes"] ?? 0);
$columnasFaltantes = intval($depurar["columnas_faltantes_total"] ?? 0);
$indicesFaltantes = intval($depurar["indices_faltantes_total"] ?? 0);
$esquemaCompleto = $tablasFaltantes === 0 && $columnasFaltantes === 0 && $indicesFaltantes === 0;

echo json_encode(array(
  "ok" => empty($auditoria["error"]),
  "modo" => "read-only",
  "senal_schema_postcheck" => $esquemaCompleto ? "esquema_leads_completo" : "esquema_leads_pendiente",
  "esquema_completo" => $esquemaCompleto,
  "tablas_faltantes" => $tablasFaltantes,
  "columnas_faltantes_total" => $columnasFaltantes,
  "indices_faltantes_total" => $indicesFaltantes,
  "auditoria" => $depurar["auditoria"] ?? array(),
  "guardrails" => array(
    "read_only" => true,
    "no_ejecuta_ddl" => true,
    "no_escribe_bd" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
