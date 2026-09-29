<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-09-28.
 * Proposito: validar puente frontend entre Videos y Analytics publico.
 * Impacto: confirma que el SDK analytics expone rawPost y que backend acepta eventos video_* sin persistir.
 * Contrato: read-only; no ejecuta DDL, no escribe BD, no toca ventas ni inventario.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/EcommerceVideosErp.php";

$trackerPath = __DIR__ . "/../../public/assets/js/custom/apps/ecommerce/analytics-tracker-publico.js";
$videosPath = __DIR__ . "/../../public/assets/js/custom/apps/ecommerce/videos-publico.js";
$tracker = file_exists($trackerPath) ? file_get_contents($trackerPath) : "";
$videosJs = file_exists($videosPath) ? file_get_contents($videosPath) : "";

$videos = new EcommerceVideosErp();
$eventos = array("video_view", "video_play", "video_producto_click", "video_add_to_cart", "video_categoria_click", "video_whatsapp_click");
$aceptados = array();
foreach ($eventos as $evento) {
  $respuesta = $videos->registrarAnalyticsEvento(array(
    "evento" => $evento,
    "referencia_tipo" => "video",
    "referencia_slug" => "filtro-cascada-sunny-shf-600-funcionamiento"
  ), array());
  $aceptados[$evento] = empty($respuesta["error"]) && (($respuesta["depurar"]["persistido"] ?? true) === false);
}

$ok = strpos($tracker, "rawPost: rawPost") !== false
  && strpos($tracker, "function rawPost") !== false
  && strpos($videosJs, "ArtianiEcommerceAnalytics.rawPost") !== false
  && count(array_filter($aceptados)) === count($eventos);

echo json_encode(array(
  "ok" => $ok,
  "modo" => "read-only",
  "senal_analytics_bridge_videos" => $ok ? "verde_videos_analytics_bridge" : "revisar_videos_analytics_bridge",
  "frontend" => array(
    "analytics_raw_post_expuesto" => strpos($tracker, "rawPost: rawPost") !== false,
    "videos_usa_raw_post_si_existe" => strpos($videosJs, "ArtianiEcommerceAnalytics.rawPost") !== false
  ),
  "eventos_video" => $aceptados,
  "guardrails" => array(
    "no_escribe_bd" => true,
    "no_ejecuta_ddl" => true,
    "sin_pii" => true,
    "no_ventas" => true,
    "no_inventario" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
