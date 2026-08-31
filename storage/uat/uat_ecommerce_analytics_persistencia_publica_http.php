<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-08-31.
 * Proposito: validar por HTTP la persistencia publica real de Ecommerce / Analytics v1.
 * Impacto: registra eventos anonimos UAT; no guarda PII, no toca ventas, inventario, checkout ni cotizaciones reales.
 * Contrato: requiere esquema aplicado y ECOMMERCE_ANALYTICS_TRACKING_PUBLICO=true.
 */

$opciones = getopt("", array("base::"));
$base = isset($opciones["base"]) ? rtrim(trim((string) $opciones["base"]), "/") : "http://panel.com.local";
$sessionId = "sess_http_analytics_real_" . date("Ymd_His");

$pruebas = array(
  "analytics_contrato" => requestAnalyticsHttp($base . "/ecommercePublico/analytics_contrato"),
  "analytics_sesion" => requestAnalyticsHttp($base . "/ecommercePublico/analytics_sesion", array(
    "session_id" => $sessionId,
    "canal" => "web_publica",
    "ruta" => "/uat-analytics",
    "utm_source" => "uat",
    "dispositivo" => "desktop",
    "metadata" => array("origen" => "uat_persistencia_publica")
  )),
  "page_view" => requestAnalyticsHttp($base . "/ecommercePublico/evento_navegacion", array(
    "session_id" => $sessionId,
    "tipo_evento" => "page_view",
    "ruta" => "/uat-analytics",
    "metadata" => array("pantalla" => "home")
  )),
  "view_product" => requestAnalyticsHttp($base . "/ecommercePublico/evento_navegacion", array(
    "session_id" => $sessionId,
    "tipo_evento" => "view_product",
    "ruta" => "/producto/producto-uat",
    "id_publicacion" => 1,
    "id_sku" => 1,
    "slug" => "producto-uat"
  )),
  "busqueda_registrar" => requestAnalyticsHttp($base . "/ecommercePublico/busqueda_registrar", array(
    "session_id" => $sessionId,
    "query" => "alimento uat sin resultado",
    "ruta" => "/buscar/alimento-uat",
    "mascota" => "gato",
    "resultados_total" => 0,
    "sin_resultados" => true
  )),
  "analytics_conversion" => requestAnalyticsHttp($base . "/ecommercePublico/analytics_conversion", array(
    "session_id" => $sessionId,
    "tipo_conversion" => "open_whatsapp",
    "ruta" => "/cotizacion",
    "id_publicacion" => 1,
    "id_sku" => 1,
    "slug" => "producto-uat",
    "metadata" => array("origen" => "uat_button")
  )),
  "pii_bloqueada" => requestAnalyticsHttp($base . "/ecommercePublico/evento_navegacion", array(
    "session_id" => $sessionId,
    "tipo_evento" => "page_view",
    "metadata" => array("correo" => "cliente@example.com")
  )),
  "stock_bloqueado" => requestAnalyticsHttp($base . "/ecommercePublico/evento_navegacion", array(
    "session_id" => $sessionId,
    "tipo_evento" => "view_product",
    "metadata" => array("stock_exacto" => 12)
  ))
);

$bloqueos = array();
$contrato = isset($pruebas["analytics_contrato"]["depurar"]) ? $pruebas["analytics_contrato"]["depurar"] : array();
if (empty($contrato["persistencia"]["activa"]) || ($contrato["persistencia"]["modo_actual"] ?? "") !== "registra_bd") {
  $bloqueos[] = "contrato_no_indica_persistencia_activa";
}

foreach (array("analytics_sesion", "page_view", "view_product", "busqueda_registrar", "analytics_conversion") as $clave) {
  if (empty($pruebas[$clave]["json_valido"])) { $bloqueos[] = $clave . "_no_json"; }
  if (!empty($pruebas[$clave]["error"])) { $bloqueos[] = $clave . "_error"; }
  if (empty($pruebas[$clave]["escribe_bd"])) { $bloqueos[] = $clave . "_no_escribio_bd"; }
}
if (!in_array("payload_no_debe_incluir_datos_personales", $pruebas["pii_bloqueada"]["bloqueos_payload"], true)) {
  $bloqueos[] = "pii_debe_bloquearse";
}
if (empty($pruebas["pii_bloqueada"]["no_escribe_bd"])) {
  $bloqueos[] = "pii_no_debe_escribir_bd";
}
if (!in_array("stock_exacto_no_permitido_en_analytics", $pruebas["stock_bloqueado"]["bloqueos_payload"], true)) {
  $bloqueos[] = "stock_exacto_debe_bloquearse";
}
if (empty($pruebas["stock_bloqueado"]["no_escribe_bd"])) {
  $bloqueos[] = "stock_no_debe_escribir_bd";
}

echo json_encode(array(
  "ok" => empty($bloqueos),
  "modo" => "persistencia-publica",
  "base" => $base,
  "session_id_uat" => $sessionId,
  "senal_frontend_analytics_http" => empty($bloqueos) ? "verde_persistencia_publica_anonima" : "revisar_analytics_persistencia",
  "pruebas" => $pruebas,
  "bloqueos" => $bloqueos,
  "guardrails" => array(
    "escribe_solo_analytics_anonimo" => true,
    "no_pii" => true,
    "no_stock_exacto" => true,
    "no_checkout" => true,
    "no_ventas" => true,
    "no_inventario" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function requestAnalyticsHttp($url, $body = null) {
  $method = $body === null ? "GET" : "POST";
  $headers = "Accept: application/json\r\n";
  $content = null;
  if ($body !== null) {
    $content = json_encode($body);
    $headers .= "Content-Type: application/json\r\n";
  }
  $context = stream_context_create(array(
    "http" => array(
      "method" => $method,
      "header" => $headers,
      "content" => $content,
      "ignore_errors" => true,
      "timeout" => 10
    )
  ));
  $raw = @file_get_contents($url, false, $context);
  $json = json_decode((string) $raw, true);
  $depurar = is_array($json) && isset($json["depurar"]) && is_array($json["depurar"]) ? $json["depurar"] : array();
  return array(
    "url" => $url,
    "method" => $method,
    "json_valido" => is_array($json),
    "error" => is_array($json) ? (bool) ($json["error"] ?? true) : true,
    "tipo" => is_array($json) ? ($json["tipo"] ?? "") : "",
    "mensaje" => is_array($json) ? ($json["mensaje"] ?? "") : "",
    "preflight" => !empty($depurar["preflight"]),
    "no_escribe_bd" => !empty($depurar["no_escribe_bd"]),
    "escribe_bd" => !empty($depurar["escribe_bd"]),
    "bloqueos_payload" => isset($depurar["bloqueos"]) && is_array($depurar["bloqueos"]) ? $depurar["bloqueos"] : array(),
    "depurar" => $depurar,
    "raw_inicio" => substr((string) $raw, 0, 120)
  );
}
