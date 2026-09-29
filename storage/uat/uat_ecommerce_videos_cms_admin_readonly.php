<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-09-28.
 * Proposito: validar contrato read-only del administrador CMS Videos.
 * Impacto: confirma que CMS puede consultar estado/listado sin DDL ni escrituras.
 * Contrato: read-only; no ejecuta DDL, no escribe BD, no toca ventas ni inventario.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/EcommerceVideosCms.php";

$cms = new EcommerceVideosCms();
$estado = $cms->adminEstado();
$listado = $cms->adminListar(array("limite" => 5));

$auditoria = $estado["depurar"]["esquema"]["auditoria"] ?? array();
$ok = empty($estado["error"])
  && empty($listado["error"])
  && !empty($estado["depurar"]["provider"]["guarda_solo_enlace"])
  && !empty($estado["depurar"]["provider"]["thumbnail_obligatorio"])
  && (($listado["depurar"]["requiere_ddl"] ?? false) === true || isset($listado["depurar"]["items"]))
  && (($auditoria["completo"] ?? false) === false || ($listado["tipo"] ?? "") === "success");

echo json_encode(array(
  "ok" => $ok,
  "modo" => "read-only",
  "senal_cms_videos" => $ok ? "verde_cms_videos_admin_readonly" : "revisar_cms_videos_admin",
  "provider" => $estado["depurar"]["provider"] ?? array(),
  "endpoints_admin" => $estado["depurar"]["endpoints_admin"] ?? array(),
  "listado_tipo" => $listado["tipo"] ?? "",
  "requiere_ddl" => $listado["depurar"]["requiere_ddl"] ?? false,
  "schema" => array(
    "completo" => $auditoria["completo"] ?? false,
    "tablas_faltantes" => intval($auditoria["tablas_faltantes"] ?? 0),
    "columnas_faltantes_total" => intval($auditoria["columnas_faltantes_total"] ?? 0),
    "indices_faltantes_total" => intval($auditoria["indices_faltantes_total"] ?? 0)
  ),
  "guardrails" => array(
    "no_escribe_bd" => true,
    "no_ejecuta_ddl" => true,
    "no_subir_video_local" => true,
    "no_descargar_video" => true,
    "no_ventas" => true,
    "no_inventario" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
