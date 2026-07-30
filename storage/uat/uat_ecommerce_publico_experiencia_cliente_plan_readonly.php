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
require_once "../app/modelos/EcommerceCatalogoPublico.php";

$schema = new EcommercePublicoEsquema();
$api = new EcommerceCatalogoPublico();
$auditoria = $schema->auditarExperienciaCliente();
$plan = $schema->planActualizarExperienciaCliente(false);
$faltantes = intval($auditoria["depurar"]["tablas_faltantes"] ?? 0);
$facturacion = $api->facturacionSolicitudPreflight(array(
  "folio_compra" => "TICKET-123",
  "datos_fiscales" => array("rfc" => "XAXX010101000", "razon_social" => "Cliente UAT", "regimen_fiscal" => "616", "uso_cfdi" => "G03", "codigo_postal" => "44100"),
  "contacto" => array("correo" => "cliente@example.com"),
  "acepta_aviso_privacidad" => true
));
$evento = $api->eventoNavegacionPreflight(array("session_id" => "sess_exp_123", "tipo_evento" => "open_whatsapp", "ruta" => "/cotizacion"));
$busqueda = $api->busquedaRegistrarPreflight(array("session_id" => "sess_exp_123", "query" => "transportadora gato", "mascota" => "gato", "resultados_total" => 0));
$preflightsOk = empty($facturacion["error"]) && empty($evento["error"]) && empty($busqueda["error"])
  && (($facturacion["depurar"]["no_escribe_bd"] ?? false) === true)
  && (($evento["depurar"]["no_escribe_bd"] ?? false) === true)
  && (($busqueda["depurar"]["no_escribe_bd"] ?? false) === true);

echo json_encode(array(
  "ok" => $preflightsOk,
  "modo" => "read-only",
  "senal_frontend_experiencia" => $preflightsOk ? "puede_avanzar_ui_con_preflights_sin_persistencia" : "revisar_preflights_experiencia",
  "schema" => array(
    "tablas_faltantes" => $faltantes,
    "ddl_pendiente" => $faltantes > 0,
    "auditoria" => $auditoria["depurar"]["auditoria"] ?? array(),
    "plan_readonly" => $plan["depurar"]["plan"] ?? array()
  ),
  "frontend_puede_avanzar" => array(
    "politicas_ui" => true,
    "facturacion_ui_con_preflight" => $preflightsOk,
    "navegacion_mascota_necesidad" => true,
    "analytics_preflight" => $preflightsOk,
    "tracking_preflight" => $preflightsOk
  ),
  "frontend_puede_conectar_sin_persistencia" => array(
    "POST /ecommercePublico/facturacion_solicitar",
    "POST /ecommercePublico/evento_navegacion",
    "POST /ecommercePublico/busqueda_registrar"
  ),
  "frontend_no_esperar_aun" => array(
    "registro_real_en_bd",
    "panel_analitico_con_datos_reales",
    "factura_automatica",
    "vinculacion_a_cliente_registrado"
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
  "preflights" => array(
    "facturacion" => array("ok" => empty($facturacion["error"]), "listo_para_registro_futuro" => $facturacion["depurar"]["listo_para_registro_futuro"] ?? false),
    "evento_navegacion" => array("ok" => empty($evento["error"]), "listo_para_registro_futuro" => $evento["depurar"]["listo_para_registro_futuro"] ?? false),
    "busqueda" => array("ok" => empty($busqueda["error"]), "listo_para_registro_futuro" => $busqueda["depurar"]["listo_para_registro_futuro"] ?? false)
  ),
  "documento" => "docs/erp_ecommerce_publico_experiencia_cliente_politicas_facturacion_analytics.md",
  "guardrails" => array(
    "no_escribe_bd" => true,
    "recibe_datos_fiscales_solo_en_preflight_no_persistido" => true,
    "no_registra_tracking" => true,
    "no_activa_cookies" => true,
    "requiere_privacidad_y_rate_limit_para_post" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
