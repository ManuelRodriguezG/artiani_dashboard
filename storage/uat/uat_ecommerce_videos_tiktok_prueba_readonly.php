<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-09-28.
 * Proposito: validar el primer video TikTok real usado como prueba de frontend.
 * Impacto: confirma URL/embed/thumbnail sin escribir BD ni cargar iframe.
 * Contrato: read-only; no ejecuta DDL, no escribe BD, no toca ventas ni inventario.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/EcommerceVideosErp.php";

$slug = "tiktok-articulos-para-animales-7526057146073566471";
$videos = new EcommerceVideosErp();
$detalle = $videos->videoDetallePublico($slug);
$item = $detalle["depurar"]["item"] ?? array();
$video = $item["video"] ?? array();
$thumbnail = $item["thumbnail"] ?? array();
$thumbnailPath = __DIR__ . "/../../public" . ($thumbnail["url"] ?? "");

$ok = empty($detalle["error"])
  && (($item["slug"] ?? "") === $slug)
  && (($video["provider"] ?? "") === "tiktok")
  && (($video["tiktok_post_id"] ?? "") === "7526057146073566471")
  && (($video["tiktok_author"] ?? "") === "articulos_para_animales")
  && (($video["url"] ?? "") === "https://www.tiktok.com/@articulos_para_animales/video/7526057146073566471")
  && (($video["embed_url"] ?? "") === "https://www.tiktok.com/player/v1/7526057146073566471?autoplay=0&description=0")
  && file_exists($thumbnailPath)
  && !empty($detalle["depurar"]["frontend"]["cargar_player_hasta_click"]);

echo json_encode(array(
  "ok" => $ok,
  "modo" => "read-only",
  "senal_tiktok_prueba" => $ok ? "verde_tiktok_prueba_frontend" : "revisar_tiktok_prueba_frontend",
  "slug" => $slug,
  "video" => array(
    "provider" => $video["provider"] ?? "",
    "tiktok_post_id" => $video["tiktok_post_id"] ?? "",
    "tiktok_author" => $video["tiktok_author"] ?? "",
    "url" => $video["url"] ?? "",
    "embed_url" => $video["embed_url"] ?? ""
  ),
  "thumbnail" => array(
    "url" => $thumbnail["url"] ?? "",
    "existe_local" => file_exists($thumbnailPath)
  ),
  "guardrails" => array(
    "no_escribe_bd" => true,
    "no_ejecuta_ddl" => true,
    "no_descarga_video" => true,
    "iframe_solo_click" => true,
    "no_ventas" => true,
    "no_inventario" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
