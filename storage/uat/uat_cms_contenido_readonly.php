<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-08-10.
 * Proposito: validar contratos internos read-only del modulo CMS.
 * Impacto: protege estado, manifest y preview del panel sin activar persistencia real.
 * Contrato: read-only; no ejecuta DDL, no guarda bloques, no sube media ni modifica catalogo/inventario.
 */

chdir(__DIR__ . "/../../public");
require_once "../app/iniciador.php";
require_once "../app/modelos/EcommerceCatalogoPublico.php";
require_once "../app/modelos/EcommercePublicoEsquema.php";

$cms = new EcommerceCatalogoPublico();
$esquema = new EcommercePublicoEsquema();

$auditoria = $esquema->auditarCmsContenido();
$plan = $esquema->planActualizarCmsContenido(false);
$estado = $cms->contenidoAdminEstadoInterno($auditoria, $plan);
$manifest = $cms->contenidoAdminManifestInterno();
$frontendManifest = $cms->frontendPlantillasAdminManifestInterno();
$home = $cms->contenidoAdminPaginaInterna(array("pagina" => "home"));
$categoria = $cms->contenidoAdminPaginaInterna(array("pagina" => "categoria", "categoria" => "peces"));
$vistaContenido = file_get_contents("../app/vistas/paginas/apps/erp/cms/contenido.php");
$vistaPlantillas = file_get_contents("../app/vistas/paginas/apps/erp/cms/plantillas.php");
$vistaPersistencia = file_get_contents("../app/vistas/paginas/apps/erp/cms/persistencia.php");
$vistaSlots = file_get_contents("../app/vistas/paginas/apps/erp/cms/slots.php");
$vistaMedia = file_get_contents("../app/vistas/paginas/apps/erp/cms/media.php");
$vistaJson = file_get_contents("../app/vistas/paginas/apps/erp/cms/json.php");
$vistaFrontendPlantillas = file_get_contents("../app/vistas/paginas/apps/erp/cms/frontend_plantillas.php");
$vistaFrontendComponentes = file_get_contents("../app/vistas/paginas/apps/erp/cms/frontend_componentes.php");
$js = file_get_contents("../public/assets/js/custom/apps/erp/cms/contenido.js");
$jsFrontend = file_get_contents("../public/assets/js/custom/apps/erp/cms/frontend.js");
$sidebar = file_get_contents("../app/vistas/includes/header/sidebar.php");
$controladorCms = file_get_contents("../app/controladores/Cms.php");
$seguridadEsquema = file_get_contents("../app/modelos/SeguridadEsquema.php");
$manualCms = file_get_contents("../docs/erp_cms_manual_uso.md");

$bloqueos = array();

if (!empty($estado["error"])) { $bloqueos[] = "estado_error"; }
if (!empty($manifest["error"])) { $bloqueos[] = "manifest_error"; }
if (!empty($frontendManifest["error"])) { $bloqueos[] = "frontend_manifest_error"; }
if (!empty($home["error"])) { $bloqueos[] = "home_error"; }
if (!empty($categoria["error"])) { $bloqueos[] = "categoria_error"; }
if (empty(valorCmsAdmin($estado, array("depurar", "guardrails", "read_only"), false))) { $bloqueos[] = "estado_no_readonly"; }
if (empty(valorCmsAdmin($estado, array("depurar", "guardrails", "no_ejecuta_ddl"), false))) { $bloqueos[] = "estado_no_declara_no_ejecuta_ddl"; }
if (valorCmsAdmin($estado, array("depurar", "persistencia_real"), true) !== false) { $bloqueos[] = "persistencia_real_activa"; }
if (count(valorCmsAdmin($manifest, array("depurar", "tipos_bloque"), array())) < 6) { $bloqueos[] = "manifest_tipos_insuficientes"; }
if (count(valorCmsAdmin($frontendManifest, array("depurar", "componentes"), array())) < 6) { $bloqueos[] = "frontend_componentes_insuficientes"; }
if (count(valorCmsAdmin($frontendManifest, array("depurar", "plantillas_vista"), array())) < 3) { $bloqueos[] = "frontend_plantillas_insuficientes"; }
if (empty(valorCmsAdmin($frontendManifest, array("depurar", "guardrails", "no_js_libre"), false))) { $bloqueos[] = "frontend_no_bloquea_js_libre"; }
if (!slotExisteCmsAdmin(valorCmsAdmin($home, array("depurar", "slots"), array()), "home.hero")) { $bloqueos[] = "home_sin_hero"; }
if (!slotExisteCmsAdmin(valorCmsAdmin($home, array("depurar", "slots"), array()), "home.destacados")) { $bloqueos[] = "home_sin_destacados"; }
if (!slotExisteCmsAdmin(valorCmsAdmin($categoria, array("depurar", "slots"), array()), "categoria.banner")) { $bloqueos[] = "categoria_sin_banner"; }
if ((int) valorCmsAdmin($estado, array("depurar", "esquema", "plan", "ddl_total"), 0) < 5) { $bloqueos[] = "plan_ddl_incompleto"; }
if (strpos((string) $vistaContenido, "ecom_cms_bloques") === false) { $bloqueos[] = "vista_contenido_sin_listado_bloques"; }
if (strpos((string) $vistaContenido, "ecom_cms_form") === false) { $bloqueos[] = "vista_contenido_sin_editor"; }
if (strpos((string) $vistaContenido, "ecom_cms_resumen_editorial") === false) { $bloqueos[] = "vista_contenido_sin_resumen_editorial"; }
if (strpos((string) $vistaContenido, "ecom_cms_publicabilidad_slots") === false) { $bloqueos[] = "vista_contenido_sin_publicabilidad_slots"; }
if (strpos((string) $vistaContenido, "Cierre read-only de contenido") === false) { $bloqueos[] = "vista_contenido_sin_cierre_readonly"; }
if (strpos((string) $vistaPlantillas, "ecom_cms_tipos") === false) { $bloqueos[] = "vista_plantillas_sin_tipos"; }
if (strpos((string) $vistaPlantillas, "ecom_cms_esquema") === false) { $bloqueos[] = "vista_plantillas_sin_esquema"; }
if (strpos((string) $vistaPlantillas, "data-cms-json-mode=\"manifest\"") === false) { $bloqueos[] = "vista_plantillas_sin_json_manifest"; }
if (strpos((string) $vistaPlantillas, "ecom_cms_contratos") === false) { $bloqueos[] = "vista_plantillas_sin_contratos"; }
if (strpos((string) $vistaPlantillas, "Plantilla de contenido read-only") === false) { $bloqueos[] = "vista_plantillas_sin_aviso_contenido"; }
if (strpos((string) $vistaPlantillas, "erp_cms_manual_uso.md") === false) { $bloqueos[] = "vista_plantillas_sin_link_manual"; }
if (strpos((string) $vistaPersistencia, "ecom_cms_esquema") === false) { $bloqueos[] = "vista_persistencia_sin_esquema"; }
if (strpos((string) $vistaPersistencia, "Checklist de autorizacion") === false) { $bloqueos[] = "vista_persistencia_sin_checklist"; }
if (strpos((string) $vistaPersistencia, "contenido_bloque_guardar_erp") === false) { $bloqueos[] = "vista_persistencia_sin_post_bloque_guardar"; }
if (strpos((string) $vistaPersistencia, "No escribe BD") === false) { $bloqueos[] = "vista_persistencia_no_declara_no_escribe_bd"; }
if (strpos((string) $vistaPersistencia, "erp_cms_manual_uso.md") === false) { $bloqueos[] = "vista_persistencia_sin_link_manual"; }
if (strpos((string) $vistaSlots, "ecom_cms_slots") === false) { $bloqueos[] = "vista_slots_sin_slots"; }
if (strpos((string) $vistaSlots, "ecom_cms_slot_detalle") === false) { $bloqueos[] = "vista_slots_sin_detalle_slot"; }
if (strpos((string) $vistaSlots, "Vista estructural read-only") === false) { $bloqueos[] = "vista_slots_sin_readonly_estructural"; }
if (strpos((string) $vistaSlots, "erp_cms_manual_uso.md") === false) { $bloqueos[] = "vista_slots_sin_link_manual"; }
if (strpos((string) $vistaMedia, "ecom_cms_visual") === false) { $bloqueos[] = "vista_media_sin_preview_visual"; }
if (strpos((string) $vistaMedia, "data-cms-bloques-mode=\"seleccion\"") === false) { $bloqueos[] = "vista_media_no_es_solo_seleccion"; }
if (strpos((string) $vistaMedia, "Vista de inspeccion read-only") === false) { $bloqueos[] = "vista_media_sin_readonly_inspeccion"; }
if (strpos((string) $vistaMedia, "erp_cms_manual_uso.md") === false) { $bloqueos[] = "vista_media_sin_link_manual"; }
if (strpos((string) $vistaJson, "ecom_cms_json") === false) { $bloqueos[] = "vista_json_sin_preview_json"; }
if (strpos((string) $vistaJson, "ecom_cms_contratos") === false) { $bloqueos[] = "vista_json_sin_contratos_api"; }
if (strpos((string) $vistaJson, "ecom_cms_arranque") === false) { $bloqueos[] = "vista_json_sin_arranque_frontend"; }
if (strpos((string) $vistaJson, "Contrato API read-only") === false) { $bloqueos[] = "vista_json_sin_readonly_api"; }
if (strpos((string) $vistaJson, "erp_cms_manual_uso.md") === false) { $bloqueos[] = "vista_json_sin_link_manual"; }
if (strpos((string) $vistaFrontendPlantillas, "cms_frontend_plantillas") === false) { $bloqueos[] = "vista_frontend_plantillas_sin_listado"; }
if (strpos((string) $vistaFrontendPlantillas, "cms_frontend_renderer") === false) { $bloqueos[] = "vista_frontend_plantillas_sin_renderer"; }
if (strpos((string) $vistaFrontendPlantillas, "erp_cms_manual_uso.md") === false) { $bloqueos[] = "vista_frontend_plantillas_sin_link_manual"; }
if (strpos((string) $vistaFrontendPlantillas, "No edita archivos del ecommerce") === false) { $bloqueos[] = "vista_frontend_plantillas_sin_guardrail_archivos"; }
if (strpos((string) $vistaFrontendComponentes, "cms_frontend_componentes") === false) { $bloqueos[] = "vista_frontend_componentes_sin_listado"; }
if (strpos((string) $vistaFrontendComponentes, "cms_frontend_renderer") === false) { $bloqueos[] = "vista_frontend_componentes_sin_renderer"; }
if (strpos((string) $vistaFrontendComponentes, "erp_cms_manual_uso.md") === false) { $bloqueos[] = "vista_frontend_componentes_sin_link_manual"; }
if (strpos((string) $vistaFrontendComponentes, "Catalogo de componentes read-only") === false) { $bloqueos[] = "vista_frontend_componentes_sin_readonly"; }
if (strpos((string) $js, "function nuevoBloque") === false) { $bloqueos[] = "js_sin_nuevo_bloque"; }
if (strpos((string) $js, "function aplicarForm") === false) { $bloqueos[] = "js_sin_aplicar_form"; }
if (strpos((string) $js, "function ejecutarAccionBloque") === false) { $bloqueos[] = "js_sin_acciones_bloque"; }
if (strpos((string) $js, "function validarContenido") === false) { $bloqueos[] = "js_sin_validacion_local"; }
if (strpos((string) $js, "function guardarBorradorLocal") === false) { $bloqueos[] = "js_sin_borrador_local"; }
if (strpos((string) $js, "data-cms-action=\"duplicar\"") === false) { $bloqueos[] = "js_sin_duplicar_bloque"; }
if (strpos((string) $js, "function exportarJson") === false) { $bloqueos[] = "js_sin_exportar_json"; }
if (strpos((string) $js, "function importarJson") === false) { $bloqueos[] = "js_sin_importar_json"; }
if (strpos((string) $js, "function construirManifestPreview") === false) { $bloqueos[] = "js_sin_manifest_preview"; }
if (strpos((string) $js, "function accionesBloqueHtml") === false) { $bloqueos[] = "js_sin_acciones_contextuales"; }
if (strpos((string) $js, "function renderContratos") === false) { $bloqueos[] = "js_sin_render_contratos"; }
if (strpos((string) $js, "endpoints_publicos") === false) { $bloqueos[] = "js_sin_endpoints_publicos_en_contratos"; }
if (strpos((string) $js, "function renderArranque") === false) { $bloqueos[] = "js_sin_render_arranque_frontend"; }
if (strpos((string) $js, "/ecommercePublico/configuracion_inicial") === false) { $bloqueos[] = "js_sin_configuracion_inicial_recomendada"; }
if (strpos((string) $js, "Alias legacy") === false) { $bloqueos[] = "js_sin_alias_legacy_bootstrap"; }
if (strpos((string) $js, "function renderSlotDetalle") === false) { $bloqueos[] = "js_sin_detalle_slot"; }
if (strpos((string) $js, "function renderResumenEditorial") === false) { $bloqueos[] = "js_sin_resumen_editorial"; }
if (strpos((string) $js, "function estadoVigencia") === false) { $bloqueos[] = "js_sin_estado_vigencia"; }
if (strpos((string) $js, "function renderPublicabilidadSlots") === false) { $bloqueos[] = "js_sin_publicabilidad_slots"; }
if (strpos((string) $js, "function revisarSlot") === false) { $bloqueos[] = "js_sin_revision_slot"; }
if (strpos((string) $jsFrontend, "/cms/frontend_admin_manifest_erp") === false) { $bloqueos[] = "js_frontend_no_consume_manifest"; }
if (strpos((string) $jsFrontend, "function renderPlantillas") === false) { $bloqueos[] = "js_frontend_sin_plantillas"; }
if (strpos((string) $jsFrontend, "function renderComponentes") === false) { $bloqueos[] = "js_frontend_sin_componentes"; }
if (strpos((string) $vistaContenido, "ecom_cms_validacion") === false) { $bloqueos[] = "vista_contenido_sin_panel_validacion"; }
if (strpos((string) $vistaContenido, "ecom_cms_guardar_local") === false) { $bloqueos[] = "vista_contenido_sin_borrador_local"; }
if (strpos((string) $vistaContenido, "ecom_cms_filtro_estatus") === false) { $bloqueos[] = "vista_contenido_sin_filtro_estatus"; }
if (strpos((string) $vistaJson, "ecom_cms_import_json") === false) { $bloqueos[] = "vista_json_sin_importar_json"; }
if (strpos((string) $sidebar, "/cms/contenido") === false) { $bloqueos[] = "sidebar_sin_cms_contenido"; }
if (strpos((string) $sidebar, "/cms/plantillas") === false) { $bloqueos[] = "sidebar_sin_cms_plantillas"; }
if (strpos((string) $sidebar, "/cms/persistencia") === false) { $bloqueos[] = "sidebar_sin_cms_persistencia"; }
if (strpos((string) $sidebar, "/cms/slots") === false) { $bloqueos[] = "sidebar_sin_cms_slots"; }
if (strpos((string) $sidebar, "/cms/media") === false) { $bloqueos[] = "sidebar_sin_cms_media"; }
if (strpos((string) $sidebar, "/cms/json") === false) { $bloqueos[] = "sidebar_sin_cms_json"; }
if (strpos((string) $sidebar, "/cms/frontend_plantillas") === false) { $bloqueos[] = "sidebar_sin_cms_frontend_plantillas"; }
if (strpos((string) $sidebar, "/cms/frontend_componentes") === false) { $bloqueos[] = "sidebar_sin_cms_frontend_componentes"; }
if (strpos((string) $sidebar, "'titulo' => 'Frontend'") === false) { $bloqueos[] = "sidebar_sin_grupo_cms_frontend"; }
if (strpos((string) $sidebar, "'seccion' => 'CMS'") === false) { $bloqueos[] = "sidebar_sin_seccion_cms"; }
if (strpos((string) $sidebar, "/ecommercePublico/contenido") !== false) { $bloqueos[] = "sidebar_cms_colgado_de_ecommerce"; }
if (strpos((string) $sidebar, "#cms_tab_") !== false) { $bloqueos[] = "sidebar_usa_hashes_de_tabs"; }
if (strpos((string) $vistaContenido . $vistaPlantillas . $vistaPersistencia . $vistaSlots . $vistaMedia . $vistaJson . $vistaFrontendPlantillas . $vistaFrontendComponentes, "data-cms-tab") !== false) { $bloqueos[] = "vistas_aun_usan_tabs"; }
if (strpos((string) $controladorCms, "public function plantillas") === false) { $bloqueos[] = "controlador_sin_ruta_plantillas"; }
if (strpos((string) $controladorCms, "public function persistencia") === false) { $bloqueos[] = "controlador_sin_ruta_persistencia"; }
if (strpos((string) $controladorCms, "public function slots") === false) { $bloqueos[] = "controlador_sin_ruta_slots"; }
if (strpos((string) $controladorCms, "public function media") === false) { $bloqueos[] = "controlador_sin_ruta_media"; }
if (strpos((string) $controladorCms, "public function json") === false) { $bloqueos[] = "controlador_sin_ruta_json"; }
if (strpos((string) $controladorCms, "public function frontend_plantillas") === false) { $bloqueos[] = "controlador_sin_ruta_frontend_plantillas"; }
if (strpos((string) $controladorCms, "public function frontend_componentes") === false) { $bloqueos[] = "controlador_sin_ruta_frontend_componentes"; }
if (strpos((string) $controladorCms, "public function frontend_admin_manifest_erp") === false) { $bloqueos[] = "controlador_sin_manifest_frontend"; }
if (strpos((string) $controladorCms, "public function index") === false) { $bloqueos[] = "controlador_sin_ruta_index_cms"; }
if (strpos((string) $controladorCms, "public function contenido_bloque_guardar_erp") === false) { $bloqueos[] = "controlador_sin_post_bloque_guardar"; }
if (strpos((string) $controladorCms, "public function contenido_bloque_estatus_erp") === false) { $bloqueos[] = "controlador_sin_post_bloque_estatus"; }
if (strpos((string) $controladorCms, "public function contenido_publicacion_guardar_erp") === false) { $bloqueos[] = "controlador_sin_post_publicacion_guardar"; }
if (strpos((string) $controladorCms, "public function contenido_publicacion_estatus_erp") === false) { $bloqueos[] = "controlador_sin_post_publicacion_estatus"; }
if (strpos((string) $controladorCms, "respuestaEscrituraCmsBloqueada") === false) { $bloqueos[] = "controlador_post_no_bloquea_escritura"; }
if (strpos((string) $controladorCms, "\"no_escribe_bd\" => true") === false) { $bloqueos[] = "controlador_post_sin_guardrail_no_escribe_bd"; }
if (strpos((string) $js, "/cms/contenido_admin_estado_erp") === false) { $bloqueos[] = "js_no_consume_modulo_cms"; }
if (strpos((string) $controladorCms, "\"cms.ver\"") === false) { $bloqueos[] = "controlador_sin_permiso_cms_ver"; }
if (strpos((string) $controladorCms, "\"catalogo.ver\"") === false) { $bloqueos[] = "controlador_sin_puente_catalogo_ver"; }
if (strpos((string) $seguridadEsquema, "\"cms.ver\"") === false) { $bloqueos[] = "seguridad_sin_cms_ver"; }
if (strpos((string) $seguridadEsquema, "\"cms.editar\"") === false) { $bloqueos[] = "seguridad_sin_cms_editar"; }
if (strpos((string) $seguridadEsquema, "\"cms.publicar\"") === false) { $bloqueos[] = "seguridad_sin_cms_publicar"; }
if (strpos((string) $manualCms, "CMS > Contenido ecommerce") === false) { $bloqueos[] = "manual_sin_contenido_ecommerce"; }
if (strpos((string) $manualCms, "Cierre read-only de contenido") === false) { $bloqueos[] = "manual_sin_cierre_contenido"; }
if (strpos((string) $manualCms, "hacer que `/ecommercePublico/contenido_pagina` lea BD publicada") === false) { $bloqueos[] = "manual_cierre_sin_bd_publicada"; }
if (strpos((string) $manualCms, "CMS > Plantillas contenido") === false) { $bloqueos[] = "manual_sin_plantillas_contenido"; }
if (strpos((string) $manualCms, "Diferencia contra plantillas frontend") === false) { $bloqueos[] = "manual_plantillas_sin_diferencia_frontend"; }
if (strpos((string) $manualCms, "artiani_default") === false) { $bloqueos[] = "manual_plantillas_sin_artiani_default"; }
if (strpos((string) $manualCms, "CMS > Slots") === false) { $bloqueos[] = "manual_sin_slots"; }
if (strpos((string) $manualCms, "Detalle del slot") === false) { $bloqueos[] = "manual_slots_sin_detalle"; }
if (strpos((string) $manualCms, "Vista estructural read-only") === false) { $bloqueos[] = "manual_slots_sin_readonly"; }
if (strpos((string) $manualCms, "CMS > Media") === false) { $bloqueos[] = "manual_sin_media"; }
if (strpos((string) $manualCms, "Vista de inspeccion read-only") === false) { $bloqueos[] = "manual_media_sin_readonly"; }
if (strpos((string) $manualCms, "alt text") === false) { $bloqueos[] = "manual_media_sin_alt_text"; }
if (strpos((string) $manualCms, "CMS > Preview JSON") === false) { $bloqueos[] = "manual_sin_preview_json"; }
if (strpos((string) $manualCms, "Contrato API") === false) { $bloqueos[] = "manual_json_sin_contrato_api"; }
if (strpos((string) $manualCms, "/ecommercePublico/configuracion_inicial") === false) { $bloqueos[] = "manual_json_sin_configuracion_inicial"; }
if (strpos((string) $manualCms, "Importar preview") === false) { $bloqueos[] = "manual_json_sin_importar_preview"; }
if (strpos((string) $manualCms, "CMS > Persistencia") === false) { $bloqueos[] = "manual_sin_persistencia"; }
if (strpos((string) $manualCms, "C:\\xampp\\panel_db_backups") === false) { $bloqueos[] = "manual_persistencia_sin_respaldo"; }
if (strpos((string) $manualCms, "erp_ecommerce_contenido_bloques") === false) { $bloqueos[] = "manual_persistencia_sin_tablas"; }
if (strpos((string) $manualCms, "Activar endpoints POST con CSRF") === false) { $bloqueos[] = "manual_persistencia_sin_post_csrf"; }
if (strpos((string) $manualCms, "CMS > Frontend > Plantillas de vista") === false) { $bloqueos[] = "manual_sin_frontend_plantillas"; }
if (strpos((string) $manualCms, "wokiee_home_default") === false) { $bloqueos[] = "manual_frontend_sin_wokiee_home"; }
if (strpos((string) $manualCms, "plantilla_vista + contenido.slots") === false) { $bloqueos[] = "manual_frontend_sin_contrato_renderer"; }
if (strpos((string) $manualCms, "No edita archivos") === false) { $bloqueos[] = "manual_frontend_sin_guardrail_archivos"; }
if (strpos((string) $manualCms, "CMS > Frontend > Componentes") === false) { $bloqueos[] = "manual_sin_frontend_componentes"; }
if (strpos((string) $manualCms, "Componentes iniciales") === false) { $bloqueos[] = "manual_componentes_sin_lista_inicial"; }
if (strpos((string) $manualCms, "HeroSlider") === false) { $bloqueos[] = "manual_componentes_sin_hero_slider"; }
if (strpos((string) $manualCms, "Relacion componente / bloque / slot") === false) { $bloqueos[] = "manual_componentes_sin_relacion"; }
if (strpos((string) $manualCms, "Resumen editorial") === false) { $bloqueos[] = "manual_sin_resumen_editorial"; }
if (strpos((string) $manualCms, "Publicabilidad por slot") === false) { $bloqueos[] = "manual_sin_publicabilidad_slot"; }
if (strpos((string) $manualCms, "/cms/frontend_plantillas") === false) { $bloqueos[] = "manual_sin_frontend_pendiente"; }

$ok = empty($bloqueos);
echo json_encode(array(
  "ok" => $ok,
  "modo" => "admin_read-only",
  "senal_panel" => $ok ? "cms_contenido_readonly_listo" : "cms_contenido_readonly_incompleto",
  "bloqueos" => array_values(array_unique($bloqueos)),
  "estado" => array(
    "fase" => valorCmsAdmin($estado, array("depurar", "fase"), ""),
    "persistencia_real" => valorCmsAdmin($estado, array("depurar", "persistencia_real"), null),
    "ddl_total" => valorCmsAdmin($estado, array("depurar", "esquema", "plan", "ddl_total"), 0)
  ),
  "manifest" => array(
    "plantilla_activa" => valorCmsAdmin($manifest, array("depurar", "plantilla_activa"), ""),
    "tipos_bloque_total" => count(valorCmsAdmin($manifest, array("depurar", "tipos_bloque"), array()))
  ),
  "frontend" => array(
    "modo" => valorCmsAdmin($frontendManifest, array("depurar", "modo"), ""),
    "componentes_total" => count(valorCmsAdmin($frontendManifest, array("depurar", "componentes"), array())),
    "plantillas_total" => count(valorCmsAdmin($frontendManifest, array("depurar", "plantillas_vista"), array())),
    "sin_codigo_libre" => valorCmsAdmin($frontendManifest, array("depurar", "guardrails", "no_js_libre"), false)
  ),
  "ui" => array(
    "editor_local" => true,
    "preview_json" => "/cms/json",
    "validacion_local" => true,
    "borrador_local" => true,
    "intercambio_json" => true,
    "sidebar" => array("/cms/contenido", "/cms/plantillas", "/cms/persistencia", "/cms/slots", "/cms/media", "/cms/json", "/cms/frontend_plantillas", "/cms/frontend_componentes"),
    "navegacion" => "vistas_separadas_sin_tabs",
    "entrada_directa" => "/cms",
    "modulo_separado" => "CMS",
    "permiso_dueno" => "cms.ver",
    "permiso_puente" => "catalogo.ver",
    "acciones_locales" => array("nuevo", "editar", "duplicar", "ordenar", "pausar", "quitar")
  ),
  "post_futuros" => array(
    "estado" => "declarados_bloqueados",
    "endpoints" => array(
      "/cms/contenido_bloque_guardar_erp",
      "/cms/contenido_bloque_estatus_erp",
      "/cms/contenido_publicacion_guardar_erp",
      "/cms/contenido_publicacion_estatus_erp"
    )
  ),
  "guardrails" => array(
    "no_escribe_bd" => true,
    "no_ejecuta_ddl" => true,
    "no_modifica_catalogo" => true,
    "no_modifica_inventario" => true
  ),
  "manual" => "docs/erp_cms_manual_uso.md"
), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

function valorCmsAdmin($datos, $ruta, $default = null) {
  $actual = $datos;
  foreach ($ruta as $segmento) {
    if (!is_array($actual) || !array_key_exists($segmento, $actual)) {
      return $default;
    }
    $actual = $actual[$segmento];
  }
  return $actual;
}

function slotExisteCmsAdmin($slots, $codigo) {
  foreach ((array) $slots as $slot) {
    if (isset($slot["slot"]) && $slot["slot"] === $codigo) {
      return true;
    }
  }
  return false;
}
