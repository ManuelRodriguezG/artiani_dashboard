<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-09-28.
 * Proposito: validar el primer video real como contenido de tortugueros por categoria.
 * Impacto: confirma copy, miniatura, busqueda y relacion editorial sin producto especifico.
 * Contrato: read-only; no escribe BD, no ejecuta DDL, no toca catalogo, ventas ni inventario.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/EcommerceVideosErp.php";

$slug = "tiktok-articulos-para-animales-7526057146073566471";
$categoriaPath = "reptiles-anfibios-e-invertebrados/tortugas/tortugueros";
$videos = new EcommerceVideosErp();
$detalle = $videos->videoDetallePublico($slug);
$categoria = $videos->videosCategoriaPublica($categoriaPath);
$producto = $videos->videosProductoPublico("filtro-cascada-sunny-shf-600");
$item = $detalle["depurar"]["item"] ?? array();
$thumbnail = $item["thumbnail"] ?? array();
$categorias = $item["categorias_relacionadas"] ?? array();
$thumbnailPath = __DIR__ . "/../../public" . ($thumbnail["url"] ?? "");
$copy = (string) ($item["copy_tiktok"] ?? "");
$hashtags = $item["hashtags"] ?? array();
$textoBusqueda = (string) ($item["texto_busqueda"] ?? "");

$categoriaRelacionada = false;
foreach ($categorias as $relacion) {
  if (($relacion["path_slug"] ?? "") === $categoriaPath) {
    $categoriaRelacionada = true;
    break;
  }
}

$ok = empty($detalle["error"])
  && empty($categoria["error"])
  && empty($producto["error"])
  && (($item["slug"] ?? "") === $slug)
  && (($item["tipo_video"] ?? "") === "consejo_rapido")
  && strpos($copy, "tortuguero") !== false
  && strpos($copy, "zona seca") !== false
  && in_array("Tortugueros", $hashtags, true)
  && strpos($textoBusqueda, "tortugueros") !== false
  && (($thumbnail["url"] ?? "") === "/assets/media/cms/ecommerce/videos/tortugueros-tiktok-7526057146073566471.png")
  && file_exists($thumbnailPath)
  && $categoriaRelacionada
  && count($categoria["depurar"]["items"] ?? array()) >= 1
  && count($producto["depurar"]["items"] ?? array()) === 0
  && empty($item["producto_principal"]);

echo json_encode(array(
  "ok" => $ok,
  "modo" => "read-only",
  "senal_video_tortugueros" => $ok ? "verde_video_tortugueros_categoria" : "revisar_video_tortugueros_categoria",
  "slug" => $slug,
  "tipo_video" => $item["tipo_video"] ?? "",
  "categoria_path_slug" => $categoriaPath,
  "thumbnail" => array(
    "url" => $thumbnail["url"] ?? "",
    "existe_local" => file_exists($thumbnailPath)
  ),
  "relaciones" => array(
    "categoria_relacionada" => $categoriaRelacionada,
    "categoria_items" => count($categoria["depurar"]["items"] ?? array()),
    "producto_items" => count($producto["depurar"]["items"] ?? array()),
    "producto_principal_vacio" => empty($item["producto_principal"])
  ),
  "contenido" => array(
    "copy_tortuguero" => strpos($copy, "tortuguero") !== false,
    "copy_zona_seca" => strpos($copy, "zona seca") !== false,
    "hashtag_tortugueros" => in_array("Tortugueros", $hashtags, true),
    "busqueda_tortugueros" => strpos($textoBusqueda, "tortugueros") !== false
  ),
  "guardrails" => array(
    "no_escribe_bd" => true,
    "no_ejecuta_ddl" => true,
    "sin_producto_especifico" => true,
    "no_descarga_video" => true,
    "no_toca_catalogo" => true,
    "no_ventas" => true,
    "no_inventario" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
