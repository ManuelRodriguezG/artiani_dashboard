<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-09-27.
 * Proposito: validar atribucion anonima de trafico ecommerce sin guardar click IDs crudos.
 * Impacto: distingue Meta, Google Ads y Google organico sin PII, ventas, checkout ni inventario.
 * Contrato: read-only; solo ejecuta preflights en memoria.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/EcommerceAnalyticsErp.php";

$analytics = new EcommerceAnalyticsErp();

$meta = $analytics->sesionPreflight(array(
  "session_id" => "sess_uat_atribucion_meta",
  "canal" => "web_publica",
  "ruta" => "/producto/filtro-canister?fbclid=FACEBOOK_CLICK_123&utm_campaign=peceras",
  "referrer" => "https://l.facebook.com/l.php?fbclid=FACEBOOK_REF_456",
  "dispositivo" => "mobile",
  "metadata" => array("fbclid" => "FACEBOOK_METADATA_789", "zona" => "home")
));

$googleAds = $analytics->eventoPreflight(array(
  "session_id" => "sess_uat_atribucion_google_ads",
  "tipo_evento" => "page_view",
  "ruta" => "/catalogo?gclid=GOOGLE_CLICK_123",
  "utm_source" => "google",
  "utm_medium" => "cpc",
  "utm_campaign" => "alimento-peces",
  "metadata" => array("gclid" => "GOOGLE_METADATA_456", "bloque" => "hero")
));

$googleOrganico = $analytics->eventoPreflight(array(
  "session_id" => "sess_uat_atribucion_google_organico",
  "tipo_evento" => "page_view",
  "ruta" => "/blog/cuidados-peces",
  "referrer" => "https://www.google.com/search?q=cuidados+peces"
));

$metaSesion = $meta["depurar"]["sesion_normalizada"] ?? array();
$googleAdsEvento = $googleAds["depurar"]["evento_normalizado"] ?? array();
$googleOrganicoEvento = $googleOrganico["depurar"]["evento_normalizado"] ?? array();

$json = json_encode(array($meta, $googleAds, $googleOrganico), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
$sinValoresCrudos = strpos($json, "FACEBOOK_CLICK_123") === false
  && strpos($json, "FACEBOOK_REF_456") === false
  && strpos($json, "FACEBOOK_METADATA_789") === false
  && strpos($json, "GOOGLE_CLICK_123") === false
  && strpos($json, "GOOGLE_METADATA_456") === false;

$ok = empty($meta["error"])
  && empty($googleAds["error"])
  && empty($googleOrganico["error"])
  && (($metaSesion["atribucion"]["fuente_detectada"] ?? "") === "meta")
  && (($metaSesion["atribucion"]["click_id_tipo"] ?? "") === "fbclid")
  && (($googleAdsEvento["atribucion"]["fuente_detectada"] ?? "") === "google_ads")
  && (($googleAdsEvento["atribucion"]["click_id_tipo"] ?? "") === "gclid")
  && (($googleOrganicoEvento["atribucion"]["fuente_detectada"] ?? "") === "google_organico")
  && (($googleOrganicoEvento["atribucion"]["click_id_tipo"] ?? "") === "")
  && $sinValoresCrudos
  && !empty($meta["depurar"]["no_escribe_bd"])
  && !empty($googleAds["depurar"]["no_escribe_bd"])
  && !empty($googleOrganico["depurar"]["no_escribe_bd"]);

echo json_encode(array(
  "ok" => $ok,
  "modo" => "read-only",
  "senal_atribucion" => $ok ? "atribucion_segura_meta_google_lista" : "revisar_atribucion_analytics",
  "casos" => array(
    "meta" => $metaSesion["atribucion"] ?? array(),
    "google_ads" => $googleAdsEvento["atribucion"] ?? array(),
    "google_organico" => $googleOrganicoEvento["atribucion"] ?? array()
  ),
  "redaccion_click_ids" => array(
    "sin_valores_crudos" => $sinValoresCrudos,
    "ruta_meta" => $metaSesion["primer_ruta"] ?? "",
    "ruta_google_ads" => $googleAdsEvento["ruta"] ?? ""
  ),
  "guardrails" => array(
    "no_escribe_bd" => true,
    "no_click_id_crudo" => true,
    "no_pii" => true,
    "no_stock_exacto" => true,
    "no_ventas" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
