<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-09-28.
 * Proposito: validar assets publicos del frontend de Videos ecommerce.
 * Impacto: confirma que el kit JS/CSS puede consumir API fixture o BD real sin iframes iniciales.
 * Contrato: read-only; no ejecuta DDL, no escribe BD, no toca ventas ni inventario.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/EcommerceVideosErp.php";

$js = realpath(__DIR__ . "/../../public/assets/js/custom/apps/ecommerce/videos-publico.js");
$css = realpath(__DIR__ . "/../../public/assets/css/custom/apps/ecommerce/videos-publico.css");
$videos = new EcommerceVideosErp();
$slugPrueba = "tiktok-articulos-para-animales-7526057146073566471";
$listado = $videos->videosPublicos(array("limite" => 2));
$detalle = $videos->videoDetallePublico($slugPrueba);
$evento = $videos->registrarAnalyticsEvento(array(
  "evento" => "video_play",
  "referencia_tipo" => "video",
  "referencia_slug" => $slugPrueba,
  "detalle" => array("pagina" => "/videos/" . $slugPrueba)
), array());

$items = $listado["depurar"]["items"] ?? array();
$primerItem = !empty($items) ? $items[0] : array();
$ok = $js && $css
  && empty($listado["error"])
  && empty($detalle["error"])
  && empty($evento["error"])
  && !empty($primerItem["thumbnail"]["url"])
  && empty($primerItem["video"]["iframe"])
  && !empty($detalle["depurar"]["frontend"]["cargar_player_hasta_click"])
  && (($evento["depurar"]["persistido"] ?? true) === false);

echo json_encode(array(
  "ok" => $ok,
  "modo" => "read-only",
  "senal_frontend_assets_videos" => $ok ? "verde_videos_frontend_assets" : "revisar_videos_frontend_assets",
  "assets" => array(
    "js" => $js ? str_replace("\\", "/", $js) : "",
    "css" => $css ? str_replace("\\", "/", $css) : "",
    "js_existe" => (bool) $js,
    "css_existe" => (bool) $css
  ),
  "contrato" => array(
    "listado_fuente" => $listado["depurar"]["fuente"] ?? "",
    "listado_items" => count($items),
    "thumbnail_obligatorio" => !empty($primerItem["thumbnail"]["url"]),
    "sin_iframe_en_card" => empty($primerItem["video"]["iframe"]),
    "detalle_player_hasta_click" => !empty($detalle["depurar"]["frontend"]["cargar_player_hasta_click"])
  ),
  "analytics" => array(
    "evento" => $evento["depurar"]["evento"] ?? "",
    "persistido" => $evento["depurar"]["persistido"] ?? null,
    "aceptado" => empty($evento["error"])
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
