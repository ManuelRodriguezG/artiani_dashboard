<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-31.
 * Proposito: generar paquete de entrega para iniciar el proyecto frontend ecommerce externo.
 * Impacto: concentra documentos, scripts, endpoints y semaforo actual sin escribir BD.
 * Contrato: read-only; no ejecuta DDL, no crea publicaciones y no toca inventario.
 */

$opciones = getopt("", array(
  "base::"
));

$base = isset($opciones["base"]) ? rtrim(trim((string) $opciones["base"]), "/") : "http://panel.com.local";

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/EcommerceCatalogoPublico.php";

$api = new EcommerceCatalogoPublico();
$estado = $api->estadoApiPublica();
$bootstrap = $api->bootstrapPublico(array("limite_secciones" => 4));
$configuracion = $api->configuracionPublica();
$catalogo = $api->catalogoPublico(array("limite" => 1));
$navegacion = $api->navegacionPublica(array("limite" => 8));
$sugerencias = $api->busquedaSugerenciasPublicas(array("q" => "filtro", "limite" => 4));
$canalesEstado = $api->canalesApiEstadoPublico();
$items = valorFrontendPackage($catalogo, array("depurar", "items"), array());
$primerItem = !empty($items) ? $items[0] : array();
$dryrun = array();
$preflight = array();
if (!empty($primerItem)) {
  $dryrun = $api->cotizacionDryRun(array(
    "items" => array(array(
      "id_publicacion" => intval(valorFrontendPackage($primerItem, array("id_publicacion"), 0)),
      "cantidad" => 1
    ))
  ));
  $preflight = $api->cotizacionPreflight(array(
    "items" => array(array(
      "id_publicacion" => intval(valorFrontendPackage($primerItem, array("id_publicacion"), 0)),
      "cantidad" => 1
    )),
    "contacto" => array("nombre" => "Cliente frontend", "telefono" => "3322068429"),
    "acepta_contacto_whatsapp" => true,
    "politicas_aceptadas" => array("aviso-privacidad", "cotizacion-whatsapp"),
    "utm" => array("source" => "frontend_package")
  ));
}

$bloqueosVerde = array();
if (valorFrontendPackage($estado, array("depurar", "schema", "ddl_pendiente"), true)) {
  $bloqueosVerde[] = "ddl_ecommerce_publico_pendiente";
}
if (intval(valorFrontendPackage($estado, array("depurar", "publicaciones", "total_publicadas"), 0)) <= 0) {
  $bloqueosVerde[] = "sin_publicaciones_activas";
}
if (trim((string) valorFrontendPackage($configuracion, array("depurar", "configuracion", "whatsapp_numero_principal"), "")) === "") {
  $bloqueosVerde[] = "whatsapp_no_configurado";
}
if (trim((string) valorFrontendPackage($configuracion, array("depurar", "configuracion", "cors_origenes_permitidos"), "")) === "") {
  $bloqueosVerde[] = "cors_origenes_permitidos_no_configurado";
}
if (empty($primerItem)) {
  $bloqueosVerde[] = "catalogo_sin_item_real";
}
if (empty($dryrun) || !empty($dryrun["error"]) || empty(valorFrontendPackage($dryrun, array("depurar", "lineas"), array()))) {
  $bloqueosVerde[] = "cotizacion_dryrun_sin_item_real";
}
if (empty($preflight) || !empty($preflight["error"]) || valorFrontendPackage($preflight, array("depurar", "preflight"), false) !== true || valorFrontendPackage($preflight, array("depurar", "listo_para_whatsapp"), false) !== true) {
  $bloqueosVerde[] = "cotizacion_preflight_no_ok";
}

$puedeIntegrarDatosReales = empty($bloqueosVerde);
$publicadasActuales = intval(valorFrontendPackage($estado, array("depurar", "publicaciones", "total_publicadas"), 0));
$catalogoTieneItemReal = !empty($primerItem);
$cotizacionDryrunOk = !empty($dryrun)
  && empty($dryrun["error"])
  && !empty(valorFrontendPackage($dryrun, array("depurar", "lineas"), array()));
$cotizacionPreflightOk = !empty($preflight)
  && empty($preflight["error"])
  && valorFrontendPackage($preflight, array("depurar", "preflight"), false) === true
  && valorFrontendPackage($preflight, array("depurar", "listo_para_whatsapp"), false) === true;
$facturacionPreflight = $api->facturacionSolicitudPreflight(array(
  "folio_compra" => "TICKET-123",
  "datos_fiscales" => array("rfc" => "XAXX010101000", "razon_social" => "Cliente frontend", "regimen_fiscal" => "616", "uso_cfdi" => "G03", "codigo_postal" => "44100"),
  "contacto" => array("correo" => "cliente@example.com"),
  "acepta_aviso_privacidad" => true
));
$eventoPreflight = $api->eventoNavegacionPreflight(array("session_id" => "sess_frontend_pkg", "tipo_evento" => "page_view", "ruta" => "/"));
$busquedaPreflight = $api->busquedaRegistrarPreflight(array("session_id" => "sess_frontend_pkg", "query" => "alimento perro", "mascota" => "perro", "resultados_total" => 1));
$experienciaPreflightOk = empty($facturacionPreflight["error"]) && empty($eventoPreflight["error"]) && empty($busquedaPreflight["error"])
  && valorFrontendPackage($facturacionPreflight, array("depurar", "no_escribe_bd"), false) === true
  && valorFrontendPackage($eventoPreflight, array("depurar", "no_escribe_bd"), false) === true
  && valorFrontendPackage($busquedaPreflight, array("depurar", "no_escribe_bd"), false) === true;

echo json_encode(array(
  "ok" => true,
  "modo" => "read-only",
  "senal_frontend_actual" => $puedeIntegrarDatosReales ? "verde_datos_reales" : "amarillo_mock_contratos",
  "puede_iniciar_frontend_mock" => true,
  "puede_integrar_datos_reales" => $puedeIntegrarDatosReales,
  "base_api" => $base . "/ecommercePublico",
  "endpoints_publicos" => array(
    "GET /ecommercePublico/contratos",
    "GET /ecommercePublico/estado",
    "GET /ecommercePublico/bootstrap",
    "GET /ecommercePublico/configuracion",
    "GET /ecommercePublico/seo",
    "GET /ecommercePublico/filtros",
    "GET /ecommercePublico/busqueda_sugerencias",
    "GET /ecommercePublico/navegacion",
    "GET /ecommercePublico/secciones",
    "GET /ecommercePublico/politicas",
    "GET /ecommercePublico/politica/{slug}",
    "GET /ecommercePublico/taxonomia_mascotas",
    "GET /ecommercePublico/catalogo",
    "GET /ecommercePublico/producto/{slug}",
    "GET /ecommercePublico/disponibilidad",
    "GET /ecommercePublico/canales_estado",
    "POST /ecommercePublico/cotizacion_dryrun",
    "POST /ecommercePublico/cotizacion_preflight",
    "POST /ecommercePublico/facturacion_solicitar",
    "POST /ecommercePublico/evento_navegacion",
    "POST /ecommercePublico/busqueda_registrar"
  ),
  "endpoint_bloqueado_fase1" => "POST /ecommercePublico/cotizacion_registrar",
  "documentos" => array(
    "docs/erp_ecommerce_publico_prompt_inicio_frontend.txt",
    "docs/erp_ecommerce_publico_instrucciones_frontend_nuevo_proyecto.txt",
    "docs/erp_ecommerce_publico_frontend_handoff.md",
    "docs/erp_ecommerce_publico_frontend_entregable_basico_productivo.md",
    "docs/erp_ecommerce_publico_frontend_AGENTS_template.md",
    "docs/erp_ecommerce_publico_frontend_archivos_iniciales.md",
    "docs/erp_ecommerce_publico_frontend_herramientas_integracion.md",
    "docs/erp_ecommerce_publico_frontend_snapshot_vivo.md",
    "docs/erp_ecommerce_publico_expansion_catalogo_plan.md",
    "docs/erp_ecommerce_publico_expansion_6_productos_runbook.md",
    "docs/erp_ecommerce_publico_expansion_revision_calidad_20260729.md",
    "docs/erp_ecommerce_publico_api_canales_partners.md",
    "docs/erp_ecommerce_publico_partner_activacion_checklist.md",
    "docs/erp_ecommerce_publico_experiencia_cliente_politicas_facturacion_analytics.md",
    "docs/erp_ecommerce_publico_seguridad_api_futura.md",
    "docs/erp_ecommerce_publico_seo_frontend.md",
    "docs/erp_ecommerce_publico_diagnostico_entorno.md",
    "docs/erp_ecommerce_publico_decision_activacion_fase1.md",
    "docs/erp_ecommerce_publico_orden_activacion_autorizada.md",
    "docs/erp_ecommerce_publico_api_contratos.md",
    "docs/erp_ecommerce_publico_cliente_api_frontend.md",
    "docs/erp_ecommerce_publico_frontend_contract_tests.md",
    "docs/erp_ecommerce_publico_frontend_estados_ui.md",
    "docs/erp_ecommerce_publico_carrito_whatsapp_frontend.md",
    "docs/erp_ecommerce_publico_cotizaciones_flujo_registro_futuro.md",
    "docs/erp_ecommerce_publico_fixtures_frontend.md",
    "docs/erp_ecommerce_publico_estado_actual.md",
    "docs/erp_ecommerce_publico_checklist_salida_fase1.md"
  ),
  "scripts_readonly" => array(
    "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_activacion_suite_readonly.php --base=http://panel.com.local --origin=http://artiani.com.local",
    "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_entorno_readonly.php --base=http://panel.com.local",
    "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_frontend_readiness_readonly.php",
    "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_http_smoke_readonly.php --base=http://panel.com.local",
    "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_contract_shape_readonly.php",
    "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_bootstrap_readonly.php --base=http://panel.com.local --limite=3",
    "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_navegacion_readonly.php --base=http://panel.com.local --limite=5",
    "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_busqueda_sugerencias_readonly.php --base=http://panel.com.local --q=filtro --limite=4",
    "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_catalogo_robusto_readonly.php --base=http://panel.com.local --limite=3",
    "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_seo_robusto_readonly.php --base=http://panel.com.local --limite=20",
    "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_canales_estado_readonly.php --base=http://panel.com.local",
    "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_negative_cases_readonly.php --base=http://panel.com.local",
    "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_cors_preflight_readonly.php --base=http://panel.com.local --origin=http://artiani.com.local",
    "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_frontend_fixtures_readonly.php",
    "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_frontend_env_readonly.php --base=http://panel.com.local --frontend=http://artiani.com.local",
    "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_base_cimentada_readonly.php --base=http://panel.com.local --origin=http://artiani.com.local --min_publicadas=6 --min_preview=6 --skus_preview=415,866,386,1138",
    "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_frontend_entregable_gate_readonly.php --base=http://panel.com.local --origin=http://artiani.com.local --skus_preview=415,866,386,1138 --min_publicadas=6 --min_preview=6",
    "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_frontend_productivo_gate_readonly.php --base=http://panel.com.local --origin=https://artiani.com.mx --url=https://artiani.com.mx --min_publicadas=6",
    "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_partner_api_plan_readonly.php --base=http://panel.com.local --origin=http://artiani.com.local --partner_origin=https://partner.example.com",
    "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_hmac_contract_readonly.php",
    "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_canales_api_apply_guard_readonly.php",
    "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_canales_seed_plan_readonly.php --base=http://panel.com.local --artiani_origin=http://artiani.com.local --artiani_prod=https://artiani.com.mx --partner_codigo=partner_mayoreo_001 --partner_origin=https://partner.example.com",
    "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_canales_seed_apply_guard_readonly.php",
    "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_canal_allowlist_plan_readonly.php --canal=partner_mayoreo_001 --publicaciones=1,2 --modo_precio=publico",
    "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_canal_allowlist_apply_guard_readonly.php",
    "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_credencial_plan_readonly.php --canal=partner_mayoreo_001 --modo=hmac",
    "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_credencial_emitir_apply_guard_readonly.php",
    "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_auth_observacion_readonly.php --method=GET --path=/ecommercePublico/catalogo --query=limite=2 --origin=http://artiani.com.local",
    "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_experiencia_cliente_plan_readonly.php",
    "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_experiencia_cliente_apply_guard_readonly.php",
    "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_experiencia_cliente_postcheck_readonly.php",
    "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_inteligencia_cliente_readonly.php",
    "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_experiencia_cliente_http_readonly.php --base=http://panel.com.local",
    "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_frontend_snapshot_readonly.php --base=http://panel.com.local --origin=http://artiani.com.local --limite=2",
    "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_expansion_catalogo_readonly.php --limite=20 --pool=1500 --solo_disponibles=1",
    "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_expansion_bundle_readonly.php --base=http://panel.com.local --origin=http://artiani.com.local --respaldo=C:\\xampp\\panel_db_backups\\artianilocal_panel_20260716_232839_antes_ecommerce_publico_fase1.sql --skus=415,866,386,1138 --min_actual=2 --min_objetivo=6",
    "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_frontend_preview_expansion_readonly.php --base=http://panel.com.local --origin=http://artiani.com.local --skus=415,866,386,1138 --resumen=1",
    "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_expansion_publicacion_paquete_readonly.php --skus=415,866,386,1138 --base=http://panel.com.local",
    "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_expansion_apply_checklist_readonly.php --base=http://panel.com.local --respaldo=C:\\xampp\\panel_db_backups\\artianilocal_panel_20260716_232839_antes_ecommerce_publico_fase1.sql --skus=415,866,386,1138",
    "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_publicacion_texto_curado_readonly.php --id_sku=1138 --titulo=\"Jaula para aves maxi tipo cilindro Monte Verde 33 x 56 cm\" --mascota=ave --necesidades=habitat",
    "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_expansion_curada_6_readonly.php --base=http://panel.com.local --origin=http://artiani.com.local --respaldo=C:\\xampp\\panel_db_backups\\artianilocal_panel_20260716_232839_antes_ecommerce_publico_fase1.sql",
    "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_postman_collection_readonly.php --base=http://panel.com.local",
    "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_carrito_whatsapp_readonly.php",
    "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_cotizacion_registro_plan_readonly.php --base=http://panel.com.local --origin=http://artiani.com.local",
    "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_cotizaciones_bandeja_readonly.php",
    "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_panel_publicaciones_readonly.php",
    "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_openapi_readonly.php",
    "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_autorizacion_paquete_readonly.php --base=http://panel.com.local --respaldo=RUTA_O_REFERENCIA --whatsapp=NUMERO_WHATSAPP --cors=ORIGEN_FRONTEND --url=URL_FRONTEND --sku1=1759 --sku2=1757",
    "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_apply_checklist_readonly.php --base=http://panel.com.local --respaldo=RUTA_O_REFERENCIA --whatsapp=NUMERO_WHATSAPP --cors=ORIGEN_FRONTEND --url=URL_FRONTEND --sku1=1759 --sku2=1757",
    "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_post_apply_verificacion_readonly.php --base=http://panel.com.local --origin=http://artiani.com.local",
    "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_reversa_preflight_readonly.php",
    "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_green_gate_readonly.php --base=http://panel.com.local"
  ),
  "no_usar" => array(
    "tablas internas ERP",
    "legacy ecom_*",
    "checkout",
    "pagos online",
    "pedido confirmado",
    "descuento de inventario",
    "stock exacto"
  ),
  "criterio_para_datos_reales" => array(
    "green_gate_ok" => $puedeIntegrarDatosReales,
    "catalogo_tiene_item_real" => $catalogoTieneItemReal,
    "cotizacion_dryrun_ok" => $cotizacionDryrunOk,
    "cotizacion_preflight_ok" => $cotizacionPreflightOk,
    "experiencia_cliente_preflights_ok" => $experienciaPreflightOk,
    "requiere_entorno_sano" => true,
    "requiere_ddl_configuracion_publicaciones" => true
  ),
  "frontend_puede_avanzar_ahora" => array(
    "bootstrap_inicial_desde_api" => empty($bootstrap["error"]),
    "politicas_publicas_ui_desde_api" => true,
    "facturacion_por_folio_ui_con_preflight" => $experienciaPreflightOk,
    "navegacion_por_mascota_necesidad_desde_api" => true,
    "navegacion_publica_desde_api" => empty($navegacion["error"]),
    "buscador_con_sugerencias_desde_api" => empty($sugerencias["error"]),
    "seo_rutas_y_sitemap_desde_api" => true,
    "canales_api_en_modo_readonly" => valorFrontendPackage($canalesEstado, array("depurar", "modo"), "") === "multi_canal_diseno_readonly",
    "analytics_mock_panel" => true,
    "tracking_preflight_sin_persistencia" => $experienciaPreflightOk,
    "erp_inteligencia_cliente_readonly" => "GET http://panel.com.local/ecommercePublico/inteligencia_cliente_erp",
    "erp_bandeja_cotizaciones_readonly" => "http://panel.com.local/ecommercePublico/cotizaciones"
  ),
  "expansion_catalogo" => array(
    "estado_actual" => $publicadasActuales >= 6 ? "6_productos_publicados" : "revision_calidad",
    "publicadas_reales_actuales" => $publicadasActuales,
    "candidatos_limpios" => array(415, 866, 386),
    "candidato_con_revision" => array(
      "id_sku" => 1138,
      "bloqueo" => "validar_texto_publico",
      "documento" => "docs/erp_ecommerce_publico_expansion_revision_calidad_20260729.md"
    ),
    "ruta_curada_disponible" => array(
      "senal" => "verde_expansion_curada_6_lista_para_revision",
      "id_sku_curado" => 1138,
      "titulo_publico_curado" => "Jaula para aves maxi tipo cilindro Monte Verde 33 x 56 cm",
      "publicaciones_estimadas_post_expansion" => 6,
      "requiere_autorizacion_antes_de_escribir_bd" => $publicadasActuales < 6,
      "frontend_puede_usar_como_preview_de_layout" => $publicadasActuales < 6,
      "frontend_no_tratar_como_publicado" => $publicadasActuales < 6
    ),
    "frontend_puede_usar_preview_para_layout" => $publicadasActuales < 6,
    "frontend_no_tratar_preview_como_publicado" => $publicadasActuales < 6
  ),
  "frontend_conectar_solo_como_preflight" => array(
    "POST /ecommercePublico/facturacion_solicitar",
    "POST /ecommercePublico/evento_navegacion",
    "POST /ecommercePublico/busqueda_registrar"
  ),
  "frontend_no_esperar_aun" => array(
    "facturacion_real_guardada",
    "tracking_real_guardado",
    "panel_analytics_con_datos_reales",
    "cliente_registrado"
  ),
  "bloqueos_para_verde_datos_reales" => $bloqueosVerde
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function valorFrontendPackage($datos, $ruta, $default = null) {
  $actual = $datos;
  foreach ($ruta as $segmento) {
    if (!is_array($actual) || !array_key_exists($segmento, $actual)) {
      return $default;
    }
    $actual = $actual[$segmento];
  }
  return $actual;
}
