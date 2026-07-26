<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-25.
 * Proposito: resumir plan multi-canal/partners para API ecommerce sin ejecutar DDL.
 * Impacto: permite preparar tokens, scopes, allowlist y auditoria para frontend propio y partners.
 * Contrato: read-only; no crea canales, no genera secretos, no aplica DDL y no registra cotizaciones.
 */

$opciones = getopt("", array("base::", "origin::", "partner_origin::"));
$base = isset($opciones["base"]) ? rtrim(trim((string) $opciones["base"]), "/") : "http://panel.com.local";
$origin = isset($opciones["origin"]) ? trim((string) $opciones["origin"]) : "http://artiani.com.local";
$partnerOrigin = isset($opciones["partner_origin"]) ? trim((string) $opciones["partner_origin"]) : "https://partner.example.com";

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/EcommerceCatalogoPublico.php";
require_once "../app/modelos/EcommercePublicoEsquema.php";

$api = new EcommerceCatalogoPublico();
$schema = new EcommercePublicoEsquema();
$estado = $api->estadoApiPublica();
$configuracion = $api->configuracionPublica();
$auditoriaCanales = $schema->auditarCanalesApi();
$planCanales = $schema->planActualizarCanalesApi(false);

$ddlPendientes = intval(valorPartnerPlan($planCanales, array("depurar", "ddl_pendientes"), 0));
$tablasFaltantes = intval(valorPartnerPlan($auditoriaCanales, array("depurar", "tablas_faltantes"), 0));
$bloqueosPartner = array();
if ($tablasFaltantes > 0) {
  $bloqueosPartner[] = "ddl_canales_api_pendiente";
}
if (valorPartnerPlan($estado, array("depurar", "schema", "ddl_pendiente"), true)) {
  $bloqueosPartner[] = "ddl_ecommerce_base_pendiente";
}
if (!$api->origenCorsPermitido($origin)) {
  $bloqueosPartner[] = "origin_artiani_no_permitido";
}
if (trim((string) valorPartnerPlan($configuracion, array("depurar", "configuracion", "whatsapp_numero_principal"), "")) === "") {
  $bloqueosPartner[] = "whatsapp_no_configurado";
}

echo json_encode(array(
  "ok" => true,
  "modo" => "read-only",
  "senal_api_actual" => empty($bloqueosPartner) || $bloqueosPartner === array("ddl_canales_api_pendiente") ? "frontend_artiani_verde_partner_en_diseno" : "revisar_bloqueos_base",
  "base_api" => $base . "/ecommercePublico",
  "origin_artiani" => $origin,
  "partner_origin_ejemplo" => $partnerOrigin,
  "estado_api_actual" => array(
    "ready" => valorPartnerPlan($estado, array("depurar", "ready"), false),
    "publicadas" => valorPartnerPlan($estado, array("depurar", "publicaciones", "total_publicadas"), 0),
    "whatsapp_configurado" => trim((string) valorPartnerPlan($configuracion, array("depurar", "configuracion", "whatsapp_numero_principal"), "")) !== "",
    "cors_artiani_permitido" => $api->origenCorsPermitido($origin)
  ),
  "canales_recomendados" => array(
    array(
      "codigo" => "artiani_web",
      "tipo_canal" => "frontend_propio",
      "allowed_origins" => array("http://artiani.com.local", "https://artiani.com.mx"),
      "scopes" => array("catalogo:leer", "producto:leer", "filtros:leer", "disponibilidad:leer", "cotizacion:dryrun"),
      "cotizacion_registrar" => "pendiente"
    ),
    array(
      "codigo" => "partner_mayoreo_001",
      "tipo_canal" => "partner_mayoreo",
      "allowed_origins" => array($partnerOrigin),
      "scopes" => array("catalogo:leer", "producto:leer", "filtros:leer", "disponibilidad:leer", "cotizacion:dryrun"),
      "cotizacion_registrar" => "pendiente_hasta_backend_hmac"
    )
  ),
  "seguridad" => array(
    "no_secret_en_javascript" => true,
    "cors_no_es_autenticacion" => true,
    "hmac_para_acciones_sensibles" => true,
    "rate_limit_requerido" => true,
    "nonce_antireplay_requerido" => true,
    "logs_por_canal_requeridos" => true
  ),
  "schema_canales_api" => array(
    "tablas_faltantes" => $tablasFaltantes,
    "ddl_pendientes" => $ddlPendientes,
    "tablas" => array_keys(valorPartnerPlan($auditoriaCanales, array("depurar", "auditoria"), array())),
    "plan_readonly" => valorPartnerPlan($planCanales, array("depurar", "plan"), array())
  ),
  "siguiente_autorizacion_sugerida" => "AUTORIZO APLICAR DDL CANALES API ECOMMERCE usando respaldo C:\\xampp\\panel_db_backups\\[ARCHIVO].sql con token ECOMMERCE_PUBLICO_CANALES_API_DDL. Entiendo que solo crea tablas de canales, credenciales, allowlist y logs; no genera secretos, no publica productos, no habilita partners, no registra cotizaciones, no toca inventario ni legacy ecom_*.",
  "documentos" => array(
    "docs/erp_ecommerce_publico_api_canales_partners.md",
    "docs/erp_ecommerce_publico_api_contratos.md",
    "docs/erp_ecommerce_publico_seguridad_api_futura.md"
  ),
  "bloqueos_partner_productivo" => $bloqueosPartner,
  "guardrails" => array(
    "no_escribe_bd" => true,
    "no_genera_secretos" => true,
    "no_activa_auth" => true,
    "no_rompe_frontend_artiani" => true,
    "no_registra_cotizaciones" => true,
    "no_descuenta_inventario" => true,
    "no_toca_ecom_legacy" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function valorPartnerPlan($datos, $ruta, $default = null) {
  $actual = $datos;
  foreach ($ruta as $segmento) {
    if (!is_array($actual) || !array_key_exists($segmento, $actual)) {
      return $default;
    }
    $actual = $actual[$segmento];
  }
  return $actual;
}
