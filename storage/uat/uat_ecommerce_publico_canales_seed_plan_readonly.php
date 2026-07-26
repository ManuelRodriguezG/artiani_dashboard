<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-26.
 * Proposito: proponer semillas de canales API ecommerce sin escribir BD.
 * Impacto: prepara Artiani y primer partner con scopes, origins y politicas claras.
 * Contrato: read-only; no crea canales, no genera credenciales y no asigna productos.
 */

$opciones = getopt("", array("base::", "artiani_origin::", "artiani_prod::", "partner_codigo::", "partner_origin::", "partner_nombre::"));
$base = isset($opciones["base"]) ? rtrim(trim((string) $opciones["base"]), "/") : "http://panel.com.local";
$artianiOrigin = isset($opciones["artiani_origin"]) ? trim((string) $opciones["artiani_origin"]) : "http://artiani.com.local";
$artianiProd = isset($opciones["artiani_prod"]) ? trim((string) $opciones["artiani_prod"]) : "https://artiani.com.mx";
$partnerCodigo = isset($opciones["partner_codigo"]) ? trim((string) $opciones["partner_codigo"]) : "partner_mayoreo_001";
$partnerOrigin = isset($opciones["partner_origin"]) ? trim((string) $opciones["partner_origin"]) : "https://partner.example.com";
$partnerNombre = isset($opciones["partner_nombre"]) ? trim((string) $opciones["partner_nombre"]) : "Partner mayoreo 001";

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/core/DBSchema.php";
require_once "../app/modelos/EcommercePublicoEsquema.php";

$schema = new EcommercePublicoEsquema();
$auditoria = $schema->auditarCanalesApi();
$tablasFaltantes = intval($auditoria["depurar"]["tablas_faltantes"] ?? 0);

$scopesLectura = array("catalogo:leer", "producto:leer", "filtros:leer", "disponibilidad:leer", "cotizacion:dryrun");
$canales = array(
  array(
    "codigo" => "artiani_web",
    "nombre" => "Artiani ecommerce publico",
    "tipo_canal" => "frontend_propio",
    "estatus" => "activo",
    "url_publica" => $artianiProd,
    "allowed_origins" => array($artianiOrigin, $artianiProd),
    "scopes" => $scopesLectura,
    "politica_precios" => "publico",
    "canal_publicacion" => "catalogo_publico",
    "puede_registrar_cotizacion" => false,
    "rate_limit_minuto" => 120,
    "rate_limit_dia" => 10000
  ),
  array(
    "codigo" => $partnerCodigo,
    "nombre" => $partnerNombre,
    "tipo_canal" => "partner_mayoreo",
    "estatus" => "borrador",
    "url_publica" => $partnerOrigin,
    "allowed_origins" => array($partnerOrigin),
    "scopes" => $scopesLectura,
    "politica_precios" => "publico_o_consultar",
    "canal_publicacion" => "catalogo_publico",
    "puede_registrar_cotizacion" => false,
    "rate_limit_minuto" => 60,
    "rate_limit_dia" => 5000
  )
);

echo json_encode(array(
  "ok" => true,
  "modo" => "read-only",
  "base_api" => $base . "/ecommercePublico",
  "ddl_canales_api_disponible" => $tablasFaltantes === 0,
  "bloqueos_para_seed_real" => $tablasFaltantes === 0 ? array() : array("ddl_canales_api_pendiente"),
  "canales_propuestos" => $canales,
  "orden_recomendado" => array(
    "aplicar_ddl_canales_api_con_autorizacion",
    "crear_canal_artiani_web",
    "crear_canal_partner_en_borrador",
    "asignar_allowlist_productos_partner",
    "emitir_credenciales_solo_si_hay_backend_o_politica_readonly_limitada"
  ),
  "siguiente_apply_futuro_no_ejecutado" => "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_canales_seed_apply_authorized.php --autorizar=ECOMMERCE_PUBLICO_CANALES_SEED --respaldo=C:\\xampp\\panel_db_backups\\[ARCHIVO].sql --partner_codigo=" . $partnerCodigo . " --partner_origin=" . $partnerOrigin,
  "guardrails" => array(
    "no_escribe_bd" => true,
    "no_genera_secretos" => true,
    "no_activa_partner" => true,
    "partner_en_borrador" => true,
    "no_asigna_productos_por_defecto" => true,
    "no_rompe_artiani" => true
  )
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
