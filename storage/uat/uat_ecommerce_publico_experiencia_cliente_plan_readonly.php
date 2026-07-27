<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-26.
 * Proposito: resumir plan de politicas, facturacion, analytics y navegacion guiada ecommerce.
 * Impacto: coordina ERP y frontend sin activar escrituras publicas.
 * Contrato: read-only; no crea tablas, no registra eventos y no recibe datos fiscales.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/core/DBSchema.php";
require_once "../app/modelos/EcommercePublicoEsquema.php";

$schema = new EcommercePublicoEsquema();
$auditoria = $schema->auditarExperienciaCliente();
$plan = $schema->planActualizarExperienciaCliente(false);
$faltantes = intval($auditoria["depurar"]["tablas_faltantes"] ?? 0);

echo json_encode(array(
  "ok" => true,
  "modo" => "read-only",
  "senal_frontend_experiencia" => "puede_avanzar_ui_sin_post_real",
  "schema" => array(
    "tablas_faltantes" => $faltantes,
    "ddl_pendiente" => $faltantes > 0,
    "auditoria" => $auditoria["depurar"]["auditoria"] ?? array(),
    "plan_readonly" => $plan["depurar"]["plan"] ?? array()
  ),
  "frontend_puede_avanzar" => array(
    "politicas_ui" => true,
    "facturacion_ui_sin_post" => true,
    "navegacion_mascota_necesidad" => true,
    "analytics_mock" => true,
    "tracking_local_mock" => true
  ),
  "frontend_no_conectar_aun" => array(
    "POST /ecommercePublico/facturacion_solicitar",
    "POST /ecommercePublico/evento_navegacion",
    "POST /ecommercePublico/busqueda_registrar"
  ),
  "politicas_minimas" => array(
    "terminos_condiciones",
    "aviso_privacidad",
    "cotizacion_whatsapp",
    "disponibilidad",
    "precios_sujetos_confirmacion",
    "cambios_devoluciones",
    "facturacion",
    "cookies_tracking"
  ),
  "facturacion_flujo" => array(
    "cliente_captura_folio_y_datos",
    "erp_recibe_solicitud_futura",
    "contador_revisa",
    "contador_genera_factura",
    "erp_actualiza_estatus"
  ),
  "analytics_eventos_futuros" => array(
    "page_view",
    "select_mascota",
    "select_necesidad",
    "search",
    "view_product",
    "add_to_quote",
    "open_whatsapp",
    "facturacion_view",
    "facturacion_submit"
  ),
  "documento" => "docs/erp_ecommerce_publico_experiencia_cliente_politicas_facturacion_analytics.md",
  "guardrails" => array(
    "no_escribe_bd" => true,
    "no_recibe_datos_fiscales" => true,
    "no_registra_tracking" => true,
    "no_activa_cookies" => true,
    "requiere_privacidad_y_rate_limit_para_post" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
