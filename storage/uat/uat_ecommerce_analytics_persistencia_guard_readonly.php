<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-08-04.
 * Proposito: validar que la persistencia Ecommerce / Analytics permanece bloqueada sin token operativo.
 * Impacto: permite tener codigo write-ready sin activar tracking real ni escribir BD en Fase 1.
 * Contrato: read-only; usa token invalido a proposito.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/EcommerceAnalyticsErp.php";

$analytics = new EcommerceAnalyticsErp();
$payloadBase = array(
  "session_id" => "sess_guard_analytics_001",
  "canal" => "web_publica",
  "ruta" => "/uat",
  "id_publicacion" => 1,
  "id_sku" => 1,
  "slug" => "producto-uat"
);
$opciones = array("autorizar" => "TOKEN_INVALIDO_READONLY");

$plan = $analytics->persistenciaPlanInterno();
$sesion = $analytics->registrarSesionAutorizada($payloadBase, $opciones);
$evento = $analytics->registrarEventoAutorizado(array_merge($payloadBase, array("tipo_evento" => "view_product")), $opciones);
$busqueda = $analytics->registrarBusquedaAutorizada(array_merge($payloadBase, array("query" => "alimento uat", "resultados_total" => 0)), $opciones);
$conversion = $analytics->registrarConversionAutorizada(array_merge($payloadBase, array("tipo_conversion" => "open_whatsapp")), $opciones);

$respuestas = array("sesion" => $sesion, "evento" => $evento, "busqueda" => $busqueda, "conversion" => $conversion);
$ok = !empty($plan["depurar"]["no_escribe_bd"]);
foreach ($respuestas as $respuesta) {
  $ok = $ok
    && !empty($respuesta["error"])
    && !empty($respuesta["depurar"]["bloqueado"])
    && !empty($respuesta["depurar"]["no_escribe_bd"])
    && (($respuesta["depurar"]["token_requerido"] ?? "") === "ECOMMERCE_ANALYTICS_TRACKING");
}

echo json_encode(array(
  "ok" => $ok,
  "modo" => "read-only",
  "senal_persistencia" => $ok ? "bloqueada_correctamente_sin_token" : "revisar_bloqueo_persistencia",
  "plan" => array(
    "persistencia_activa" => $plan["depurar"]["persistencia_activa"] ?? null,
    "tablas" => $plan["depurar"]["tablas"] ?? array(),
    "faltantes" => $plan["depurar"]["faltantes"] ?? array(),
    "token_requerido" => $plan["depurar"]["token_requerido"] ?? ""
  ),
  "bloqueos" => array(
    "sesion" => $sesion["depurar"] ?? array(),
    "evento" => $evento["depurar"] ?? array(),
    "busqueda" => $busqueda["depurar"] ?? array(),
    "conversion" => $conversion["depurar"] ?? array()
  ),
  "guardrails" => array(
    "no_escribe_bd" => true,
    "no_token_real_en_uat" => true,
    "no_tracking_real" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
