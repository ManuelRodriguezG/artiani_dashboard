<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-08-04.
 * Proposito: validar por HTTP los endpoints publicos preflight de Ecommerce / Analytics.
 * Impacto: confirma que frontend externo puede integrar contrato sin persistencia real ni PII.
 * Contrato: read-only; no escribe BD, no crea checkout, no toca ventas ni inventario.
 */

$opciones = getopt("", array("base::"));
$base = isset($opciones["base"]) ? rtrim(trim((string) $opciones["base"]), "/") : "http://panel.com.local";

$pruebas = array(
  "analytics_contrato" => requestAnalyticsHttp($base . "/ecommercePublico/analytics_contrato"),
  "analytics_sesion" => requestAnalyticsHttp($base . "/ecommercePublico/analytics_sesion", array(
    "session_id" => "sess_http_analytics_001",
    "canal" => "web_publica",
    "ruta" => "/",
    "utm_source" => "uat",
    "dispositivo" => "desktop"
  )),
  "evento_navegacion" => requestAnalyticsHttp($base . "/ecommercePublico/evento_navegacion", array(
    "session_id" => "sess_http_analytics_001",
    "tipo_evento" => "quote_preflight",
    "ruta" => "/cotizacion",
    "id_publicacion" => 1,
    "id_sku" => 1,
    "slug" => "producto-uat"
  )),
  "busqueda_registrar" => requestAnalyticsHttp($base . "/ecommercePublico/busqueda_registrar", array(
    "session_id" => "sess_http_analytics_001",
    "query" => "alimento gato",
    "mascota" => "gato",
    "resultados_total" => 0
  )),
  "analytics_conversion" => requestAnalyticsHttp($base . "/ecommercePublico/analytics_conversion", array(
    "session_id" => "sess_http_analytics_001",
    "tipo_conversion" => "open_whatsapp",
    "ruta" => "/cotizacion",
    "id_publicacion" => 1,
    "id_sku" => 1,
    "slug" => "producto-uat"
  )),
  "pii_bloqueada" => requestAnalyticsHttp($base . "/ecommercePublico/evento_navegacion", array(
    "session_id" => "sess_http_analytics_001",
    "tipo_evento" => "page_view",
    "metadata" => array("correo" => "cliente@example.com")
  )),
  "stock_bloqueado" => requestAnalyticsHttp($base . "/ecommercePublico/evento_navegacion", array(
    "session_id" => "sess_http_analytics_001",
    "tipo_evento" => "view_product",
    "metadata" => array("stock_exacto" => 12)
  ))
);

$bloqueos = array();
foreach (array("analytics_contrato", "analytics_sesion", "evento_navegacion", "busqueda_registrar", "analytics_conversion") as $clave) {
  if (empty($pruebas[$clave]["json_valido"])) { $bloqueos[] = $clave . "_no_json"; }
  if (!empty($pruebas[$clave]["error"])) { $bloqueos[] = $clave . "_error"; }
}
foreach (array("analytics_sesion", "evento_navegacion", "busqueda_registrar", "analytics_conversion") as $clave) {
  if (empty($pruebas[$clave]["preflight"])) { $bloqueos[] = $clave . "_sin_preflight"; }
  if (empty($pruebas[$clave]["no_escribe_bd"])) { $bloqueos[] = $clave . "_debe_no_escribir_bd"; }
}
if (!in_array("payload_no_debe_incluir_datos_personales", $pruebas["pii_bloqueada"]["bloqueos_payload"], true)) {
  $bloqueos[] = "pii_debe_bloquearse";
}
if (!in_array("stock_exacto_no_permitido_en_analytics", $pruebas["stock_bloqueado"]["bloqueos_payload"], true)) {
  $bloqueos[] = "stock_exacto_debe_bloquearse";
}

echo json_encode(array(
  "ok" => empty($bloqueos),
  "modo" => "read-only",
  "base" => $base,
  "senal_frontend_analytics_http" => empty($bloqueos) ? "verde_preflight_sin_persistencia" : "revisar_analytics_http",
  "pruebas" => $pruebas,
  "bloqueos" => $bloqueos,
  "guardrails" => array(
    "no_escribe_bd" => true,
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
    "bloqueos_payload" => isset($depurar["bloqueos"]) && is_array($depurar["bloqueos"]) ? $depurar["bloqueos"] : array(),
    "raw_inicio" => substr((string) $raw, 0, 120)
  );
}
