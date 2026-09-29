<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-09-28.
 * Proposito: validar postcheck read-only del esquema Ecommerce Videos.
 * Impacto: confirma tablas, columnas e indices criticos para CMS/API Videos.
 * Contrato: read-only; no ejecuta DDL ni modifica datos.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/EcommerceVideosErp.php";

$videos = new EcommerceVideosErp();
$auditoria = $videos->esquemaAuditarVideos();
$depurar = $auditoria["depurar"] ?? array();
$tablasFaltantes = intval($depurar["tablas_faltantes"] ?? 0);
$columnasFaltantes = intval($depurar["columnas_faltantes_total"] ?? 0);
$indicesFaltantes = intval($depurar["indices_faltantes_total"] ?? 0);
$esquemaCompleto = $tablasFaltantes === 0 && $columnasFaltantes === 0 && $indicesFaltantes === 0;

echo json_encode(array(
  "ok" => empty($auditoria["error"]),
  "modo" => "read-only",
  "senal_schema_postcheck" => $esquemaCompleto ? "esquema_videos_completo" : "esquema_videos_pendiente",
  "esquema_completo" => $esquemaCompleto,
  "tablas_faltantes" => $tablasFaltantes,
  "columnas_faltantes_total" => $columnasFaltantes,
  "indices_faltantes_total" => $indicesFaltantes,
  "tablas" => $depurar["tablas"] ?? array(),
  "guardrails" => array(
    "read_only" => true,
    "no_ejecuta_ddl" => true,
    "no_escribe_bd" => true,
    "no_toca_ventas" => true,
    "no_toca_inventario" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
