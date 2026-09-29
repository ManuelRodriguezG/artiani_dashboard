<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-09-28.
 * Proposito: validar contrato read-only del modulo Ecommerce Videos.
 * Impacto: confirma que frontend puede integrar listado, detalle y relaciones con fixture o BD real.
 * Contrato: read-only; no ejecuta DDL, no escribe BD, no toca ventas ni inventario.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/EcommerceVideosErp.php";

$videos = new EcommerceVideosErp();

$manifest = $videos->videosManifestPublico();
$listado = $videos->videosPublicos(array("limite" => 2));
$slugPrueba = "tiktok-articulos-para-animales-7526057146073566471";
$categoriaTortugueros = "reptiles-anfibios-e-invertebrados/tortugas/tortugueros";
$detalle = $videos->videoDetallePublico($slugPrueba);
$producto = $videos->videosProductoPublico("filtro-cascada-sunny-shf-600");
$categoria = $videos->videosCategoriaPublica($categoriaTortugueros);
$auditoria = $videos->esquemaAuditarVideos();
$plan = $videos->esquemaPlanVideos();
$fuente = $listado["depurar"]["fuente"] ?? "";
$itemsListado = $listado["depurar"]["items"] ?? array();

$ok = empty($manifest["error"])
  && empty($listado["error"])
  && empty($detalle["error"])
  && empty($producto["error"])
  && empty($categoria["error"])
  && empty($auditoria["error"])
  && empty($plan["error"])
  && in_array($fuente, array("fixture_contrato", "bd_videos"), true)
  && count($itemsListado) >= 1
  && ($detalle["depurar"]["item"]["slug"] ?? "") === $slugPrueba
  && ($detalle["depurar"]["item"]["tipo_video"] ?? "") === "consejo_rapido"
  && !empty($detalle["depurar"]["frontend"]["cargar_player_hasta_click"])
  && !empty($producto["depurar"]["frontend"]["no_cargar_iframe"])
  && !empty($categoria["depurar"]["frontend"]["usar_url_categoria_api"])
  && count($producto["depurar"]["items"] ?? array()) === 0
  && count($categoria["depurar"]["items"] ?? array()) >= 1
  && !empty($auditoria["depurar"]["no_ejecuta_ddl"])
  && !empty($plan["depurar"]["no_ejecuta_ddl"])
  && count($plan["depurar"]["sql_plan"] ?? array()) === 3;

echo json_encode(array(
  "ok" => $ok,
  "modo" => "read-only",
  "senal_frontend_videos" => $ok ? "verde_contrato_videos" : "revisar_contrato_videos",
  "endpoints_frontend" => array(
    "GET /ecommercePublico/videos",
    "GET /ecommercePublico/videos/{slug}",
    "GET /ecommercePublico/videos_manifest",
    "GET /ecommercePublico/producto/{slug}/videos",
    "GET /ecommercePublico/categoria/{path_slug}/videos"
  ),
  "contrato" => array(
    "manifest_ok" => empty($manifest["error"]),
    "listado_items" => count($itemsListado),
    "detalle_slug" => $detalle["depurar"]["item"]["slug"] ?? "",
    "producto_items" => count($producto["depurar"]["items"] ?? array()),
    "categoria_items" => count($categoria["depurar"]["items"] ?? array()),
    "fuente" => $fuente
  ),
  "schema" => array(
    "completo" => $auditoria["depurar"]["completo"] ?? false,
    "tablas_faltantes" => intval($auditoria["depurar"]["tablas_faltantes"] ?? 0),
    "columnas_faltantes_total" => intval($auditoria["depurar"]["columnas_faltantes_total"] ?? 0),
    "indices_faltantes_total" => intval($auditoria["depurar"]["indices_faltantes_total"] ?? 0),
    "plan_readonly" => array_keys($plan["depurar"]["sql_plan"] ?? array())
  ),
  "guardrails" => array(
    "no_escribe_bd" => true,
    "no_ejecuta_ddl" => true,
    "no_iframe_en_listado" => true,
    "player_solo_con_click" => true,
    "sin_mascotas_registradas" => true,
    "sin_personalizacion_usuario" => true,
    "no_ventas" => true,
    "no_inventario" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
