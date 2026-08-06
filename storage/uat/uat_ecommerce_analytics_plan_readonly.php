<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-08-04.
 * Proposito: validar contrato, preflights y plan DDL read-only de Ecommerce / Analytics.
 * Impacto: confirma que la Fase 1 no escribe BD, no guarda PII, no toca ventas ni inventario.
 * Contrato: read-only; no ejecuta DDL ni registra eventos.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/core/DBSchema.php";
require_once "../app/modelos/EcommerceAnalyticsEsquema.php";
require_once "../app/modelos/EcommerceAnalyticsErp.php";

$schema = new EcommerceAnalyticsEsquema();
$analytics = new EcommerceAnalyticsErp();

$auditoria = $schema->auditarEcommerceAnalytics();
$plan = $schema->planActualizarEcommerceAnalytics(false);
$contrato = $analytics->contratoFrontend();
$sesion = $analytics->sesionPreflight(array(
  "session_id" => "sess_uat_analytics_001",
  "canal" => "web_publica",
  "ruta" => "/productos/alimento-gato",
  "utm_source" => "uat",
  "dispositivo" => "desktop"
));
$evento = $analytics->eventoPreflight(array(
  "session_id" => "sess_uat_analytics_001",
  "tipo_evento" => "view_product",
  "ruta" => "/productos/alimento-gato",
  "id_publicacion" => 123,
  "id_sku" => 456,
  "slug" => "alimento-gato",
  "mascota" => "gato",
  "necesidad" => "alimento"
));
$busqueda = $analytics->busquedaPreflight(array(
  "session_id" => "sess_uat_analytics_001",
  "query" => "alimento gato adulto",
  "mascota" => "gato",
  "necesidad" => "alimento",
  "resultados_total" => 3
));
$conversion = $analytics->conversionPreflight(array(
  "session_id" => "sess_uat_analytics_001",
  "tipo_conversion" => "open_whatsapp",
  "ruta" => "/cotizacion",
  "id_publicacion" => 123,
  "id_sku" => 456,
  "slug" => "alimento-gato"
));
$pii = $analytics->eventoPreflight(array(
  "session_id" => "sess_uat_analytics_001",
  "tipo_evento" => "page_view",
  "metadata" => array("correo" => "cliente@example.com")
));

$ok = empty($contrato["error"])
  && empty($auditoria["error"])
  && empty($plan["error"])
  && !empty($plan["depurar"]["read_only"])
  && !empty($sesion["depurar"]["no_escribe_bd"])
  && !empty($evento["depurar"]["no_escribe_bd"])
  && !empty($busqueda["depurar"]["no_escribe_bd"])
  && !empty($conversion["depurar"]["no_escribe_bd"])
  && in_array("payload_no_debe_incluir_datos_personales", $pii["depurar"]["bloqueos"] ?? array(), true);

echo json_encode(array(
  "ok" => $ok,
  "modo" => "read-only",
  "senal_frontend_analytics" => $ok ? "puede_integrar_preflights_sin_persistencia" : "revisar_contrato_analytics",
  "schema" => array(
    "tablas_faltantes" => intval($auditoria["depurar"]["tablas_faltantes"] ?? 0),
    "columnas_faltantes_total" => intval($auditoria["depurar"]["columnas_faltantes_total"] ?? 0),
    "indices_faltantes_total" => intval($auditoria["depurar"]["indices_faltantes_total"] ?? 0),
    "ddl_pendiente" => intval($auditoria["depurar"]["tablas_faltantes"] ?? 0) > 0,
    "plan_readonly" => $plan["depurar"]["plan"] ?? array()
  ),
  "preflights" => array(
    "sesion" => array("ok" => empty($sesion["error"]), "no_escribe_bd" => $sesion["depurar"]["no_escribe_bd"] ?? false),
    "evento" => array("ok" => empty($evento["error"]), "no_escribe_bd" => $evento["depurar"]["no_escribe_bd"] ?? false),
    "busqueda" => array("ok" => empty($busqueda["error"]), "no_escribe_bd" => $busqueda["depurar"]["no_escribe_bd"] ?? false),
    "conversion" => array("ok" => empty($conversion["error"]), "no_escribe_bd" => $conversion["depurar"]["no_escribe_bd"] ?? false),
    "pii_bloqueada" => in_array("payload_no_debe_incluir_datos_personales", $pii["depurar"]["bloqueos"] ?? array(), true)
  ),
  "endpoints_frontend" => array(
    "GET /ecommercePublico/analytics_contrato",
    "POST /ecommercePublico/analytics_sesion",
    "POST /ecommercePublico/evento_navegacion",
    "POST /ecommercePublico/busqueda_registrar",
    "POST /ecommercePublico/analytics_conversion"
  ),
  "guardrails" => array(
    "no_escribe_bd" => true,
    "no_datos_personales" => true,
    "no_stock_exacto" => true,
    "no_checkout" => true,
    "no_ventas" => true,
    "no_inventario" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
