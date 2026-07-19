<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-07-15.
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
$configuracion = $api->configuracionPublica();
$catalogo = $api->catalogoPublico(array("limite" => 1));
$items = valorFrontendPackage($catalogo, array("depurar", "items"), array());
$primerItem = !empty($items) ? $items[0] : array();
$dryrun = array();
if (!empty($primerItem)) {
  $dryrun = $api->cotizacionDryRun(array(
    "items" => array(array(
      "id_publicacion" => intval(valorFrontendPackage($primerItem, array("id_publicacion"), 0)),
      "cantidad" => 1
    ))
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

$puedeIntegrarDatosReales = empty($bloqueosVerde);
$catalogoTieneItemReal = !empty($primerItem);
$cotizacionDryrunOk = !empty($dryrun)
  && empty($dryrun["error"])
  && !empty(valorFrontendPackage($dryrun, array("depurar", "lineas"), array()));

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
    "GET /ecommercePublico/configuracion",
    "GET /ecommercePublico/seo",
    "GET /ecommercePublico/filtros",
    "GET /ecommercePublico/catalogo",
    "GET /ecommercePublico/producto/{slug}",
    "GET /ecommercePublico/disponibilidad",
    "POST /ecommercePublico/cotizacion_dryrun"
  ),
  "endpoint_bloqueado_fase1" => "POST /ecommercePublico/cotizacion_registrar",
  "documentos" => array(
    "docs/erp_ecommerce_publico_prompt_inicio_frontend.txt",
    "docs/erp_ecommerce_publico_instrucciones_frontend_nuevo_proyecto.txt",
    "docs/erp_ecommerce_publico_frontend_handoff.md",
    "docs/erp_ecommerce_publico_frontend_AGENTS_template.md",
    "docs/erp_ecommerce_publico_frontend_archivos_iniciales.md",
    "docs/erp_ecommerce_publico_frontend_herramientas_integracion.md",
    "docs/erp_ecommerce_publico_frontend_snapshot_vivo.md",
    "docs/erp_ecommerce_publico_expansion_catalogo_plan.md",
    "docs/erp_ecommerce_publico_expansion_6_productos_runbook.md",
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
    "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_negative_cases_readonly.php --base=http://panel.com.local",
    "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_cors_preflight_readonly.php --base=http://panel.com.local --origin=http://artiani.com.local",
    "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_frontend_fixtures_readonly.php",
    "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_frontend_env_readonly.php --base=http://panel.com.local --frontend=http://artiani.com.local",
    "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_frontend_entregable_gate_readonly.php --base=http://panel.com.local --origin=http://artiani.com.local --skus_preview=415,866,386,1138 --min_publicadas=2 --min_preview=6",
    "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_frontend_snapshot_readonly.php --base=http://panel.com.local --origin=http://artiani.com.local --limite=2",
    "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_expansion_catalogo_readonly.php --limite=20 --pool=1500 --solo_disponibles=1",
    "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_expansion_bundle_readonly.php --base=http://panel.com.local --origin=http://artiani.com.local --respaldo=C:\\xampp\\panel_db_backups\\artianilocal_panel_20260716_232839_antes_ecommerce_publico_fase1.sql --skus=415,866,386,1138 --min_actual=2 --min_objetivo=6",
    "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_frontend_preview_expansion_readonly.php --base=http://panel.com.local --origin=http://artiani.com.local --skus=415,866,386,1138 --resumen=1",
    "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_expansion_publicacion_paquete_readonly.php --skus=415,866,386,1138 --base=http://panel.com.local",
    "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_expansion_apply_checklist_readonly.php --base=http://panel.com.local --respaldo=C:\\xampp\\panel_db_backups\\artianilocal_panel_20260716_232839_antes_ecommerce_publico_fase1.sql --skus=415,866,386,1138",
    "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_postman_collection_readonly.php --base=http://panel.com.local",
    "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_carrito_whatsapp_readonly.php",
    "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_cotizacion_registro_plan_readonly.php --base=http://panel.com.local --origin=http://artiani.com.local",
    "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_openapi_readonly.php",
    "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_autorizacion_paquete_readonly.php --base=http://panel.com.local --respaldo=RUTA_O_REFERENCIA --whatsapp=NUMERO_WHATSAPP --cors=ORIGEN_FRONTEND --url=URL_FRONTEND --sku1=1759 --sku2=1757",
    "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_apply_checklist_readonly.php --base=http://panel.com.local --respaldo=RUTA_O_REFERENCIA --whatsapp=NUMERO_WHATSAPP --cors=ORIGEN_FRONTEND --url=URL_FRONTEND --sku1=1759 --sku2=1757",
    "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_post_apply_verificacion_readonly.php --base=http://panel.com.local --origin=http://artiani.com.local",
    "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_reversa_preflight_readonly.php",
    "C:\\xampp\\php\\php.exe storage\\uat\\uat_ecommerce_publico_green_gate_readonly.php --base=http://panel.com.local"
  ),
  "no_usar" => array(
    "endpoint /bootstrap",
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
    "requiere_entorno_sano" => true,
    "requiere_ddl_configuracion_publicaciones" => true
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
