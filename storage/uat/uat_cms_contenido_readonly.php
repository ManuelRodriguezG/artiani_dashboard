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
$auditoriaFrontend = $esquema->auditarCmsFrontend();
$planFrontend = $esquema->planActualizarCmsFrontend(false);
$estado = $cms->contenidoAdminEstadoInterno($auditoria, $plan);
$manifest = $cms->contenidoAdminManifestInterno();
$frontendManifest = $cms->frontendPlantillasAdminManifestInterno();
$home = $cms->contenidoAdminPaginaInterna(array("pagina" => "home"));
$categoria = $cms->contenidoAdminPaginaInterna(array("pagina" => "categoria", "categoria" => "peces"));
$configuracionInicial = $cms->configuracionInicialPublica(array("limite_secciones" => 2));
$vistaContenido = file_get_contents("../app/vistas/paginas/apps/erp/cms/contenido.php");
$vistaPlantillas = file_get_contents("../app/vistas/paginas/apps/erp/cms/plantillas.php");
$vistaPersistencia = file_get_contents("../app/vistas/paginas/apps/erp/cms/persistencia.php");
$vistaSlots = file_get_contents("../app/vistas/paginas/apps/erp/cms/slots.php");
$vistaMedia = file_get_contents("../app/vistas/paginas/apps/erp/cms/media.php");
$vistaJson = file_get_contents("../app/vistas/paginas/apps/erp/cms/json.php");
$vistaFrontendPlantillas = file_get_contents("../app/vistas/paginas/apps/erp/cms/frontend_plantillas.php");
$vistaFrontendConstructor = file_get_contents("../app/vistas/paginas/apps/erp/cms/frontend_constructor.php");
$vistaFrontendComponentes = file_get_contents("../app/vistas/paginas/apps/erp/cms/frontend_componentes.php");
$vistaFrontendActivaciones = file_get_contents("../app/vistas/paginas/apps/erp/cms/frontend_activaciones.php");
$js = file_get_contents("../public/assets/js/custom/apps/erp/cms/contenido.js");
$jsFrontend = file_get_contents("../public/assets/js/custom/apps/erp/cms/frontend.js");
$sidebar = file_get_contents("../app/vistas/includes/header/sidebar.php");
$controladorCms = file_get_contents("../app/controladores/Cms.php");
$seguridadEsquema = file_get_contents("../app/modelos/SeguridadEsquema.php");
$manualCms = file_get_contents("../docs/erp_cms_manual_uso.md");
$contratoRenderer = file_get_contents("../docs/erp_cms_frontend_renderer_contrato.md");
$planBuilderWokiee = file_get_contents("../docs/erp_cms_visual_builder_wokiee_plan.md");
$uatPersistenciaPreflight = file_get_contents("../storage/uat/uat_cms_persistencia_preflight.php");
$uatSeedReadonly = file_get_contents("../storage/uat/uat_cms_seed_readonly.php");

$bloqueos = array();

if (!empty($estado["error"])) { $bloqueos[] = "estado_error"; }
if (!empty($manifest["error"])) { $bloqueos[] = "manifest_error"; }
if (!empty($frontendManifest["error"])) { $bloqueos[] = "frontend_manifest_error"; }
if (!empty($home["error"])) { $bloqueos[] = "home_error"; }
if (!empty($categoria["error"])) { $bloqueos[] = "categoria_error"; }
if (empty(valorCmsAdmin($estado, array("depurar", "guardrails", "read_only"), false))) { $bloqueos[] = "estado_no_readonly"; }
if (empty(valorCmsAdmin($estado, array("depurar", "guardrails", "no_ejecuta_ddl"), false))) { $bloqueos[] = "estado_no_declara_no_ejecuta_ddl"; }
if (valorCmsAdmin($estado, array("depurar", "persistencia_real"), true) !== false) { $bloqueos[] = "persistencia_real_activa"; }
if (!empty($auditoriaFrontend["error"])) { $bloqueos[] = "frontend_auditoria_error"; }
if (!empty($planFrontend["error"])) { $bloqueos[] = "frontend_plan_error"; }
if (empty(valorCmsAdmin($auditoriaFrontend, array("depurar", "read_only"), false))) { $bloqueos[] = "frontend_auditoria_no_readonly"; }
if ((int) valorCmsAdmin($planFrontend, array("depurar", "ddl_total"), 0) !== 6) { $bloqueos[] = "frontend_plan_ddl_no_declara_6_tablas"; }
if (valorCmsAdmin($planFrontend, array("depurar", "read_only"), false) !== true) { $bloqueos[] = "frontend_plan_no_readonly"; }
if (count(valorCmsAdmin($manifest, array("depurar", "tipos_bloque"), array())) < 6) { $bloqueos[] = "manifest_tipos_insuficientes"; }
if (count(valorCmsAdmin($manifest, array("depurar", "plantillas_vista"), array())) < 3) { $bloqueos[] = "manifest_sin_plantillas_vista"; }
if (count(valorCmsAdmin($manifest, array("depurar", "componentes_frontend"), array())) < 6) { $bloqueos[] = "manifest_sin_componentes_frontend"; }
if (valorCmsAdmin($manifest, array("depurar", "tema_visual_activo", "codigo"), "") !== "wokiee_artiani") { $bloqueos[] = "manifest_sin_tema_visual_activo"; }
if (valorCmsAdmin($manifest, array("depurar", "admin", "fuente_estructura"), "") !== "bd_seed") { $bloqueos[] = "manifest_admin_no_lee_bd_seed"; }
if (valorCmsAdmin($frontendManifest, array("depurar", "tema_activo", "codigo"), "") !== "wokiee_artiani") { $bloqueos[] = "frontend_sin_tema_activo"; }
if (valorCmsAdmin($frontendManifest, array("depurar", "fuente_estructura"), "") !== "bd_seed") { $bloqueos[] = "frontend_manifest_no_lee_bd_seed"; }
if (count(valorCmsAdmin($frontendManifest, array("depurar", "temas_disponibles"), array())) < 1) { $bloqueos[] = "frontend_sin_temas_disponibles"; }
if (count(valorCmsAdmin($frontendManifest, array("depurar", "activaciones"), array())) < 3) { $bloqueos[] = "frontend_sin_activaciones"; }
if (count(valorCmsAdmin($frontendManifest, array("depurar", "componentes"), array())) < 6) { $bloqueos[] = "frontend_componentes_insuficientes"; }
if (count(valorCmsAdmin($frontendManifest, array("depurar", "plantillas_vista"), array())) < 3) { $bloqueos[] = "frontend_plantillas_insuficientes"; }
if (empty(valorCmsAdmin($frontendManifest, array("depurar", "guardrails", "no_js_libre"), false))) { $bloqueos[] = "frontend_no_bloquea_js_libre"; }
if (!slotExisteCmsAdmin(valorCmsAdmin($home, array("depurar", "slots"), array()), "home.hero")) { $bloqueos[] = "home_sin_hero"; }
if (!slotExisteCmsAdmin(valorCmsAdmin($home, array("depurar", "slots"), array()), "home.destacados")) { $bloqueos[] = "home_sin_destacados"; }
if (valorCmsAdmin($home, array("depurar", "plantilla_vista", "codigo"), "") !== "wokiee_home_default") { $bloqueos[] = "home_sin_plantilla_vista"; }
if (valorCmsAdmin($configuracionInicial, array("depurar", "contenido_inicial", "home", "plantilla_vista", "codigo"), "") !== "wokiee_home_default") { $bloqueos[] = "configuracion_inicial_sin_plantilla_vista"; }
if (!slotExisteCmsAdmin(valorCmsAdmin($categoria, array("depurar", "slots"), array()), "categoria.banner")) { $bloqueos[] = "categoria_sin_banner"; }
if (valorCmsAdmin($categoria, array("depurar", "plantilla_vista", "codigo"), "") !== "wokiee_categoria_default") { $bloqueos[] = "categoria_sin_plantilla_vista"; }
if ((int) valorCmsAdmin($estado, array("depurar", "esquema", "plan", "ddl_total"), 0) < 5) { $bloqueos[] = "plan_ddl_incompleto"; }
if (strpos((string) $vistaContenido, "ecom_cms_bloques") === false) { $bloqueos[] = "vista_contenido_sin_listado_bloques"; }
if (strpos((string) $vistaContenido, "ecom_cms_form") === false) { $bloqueos[] = "vista_contenido_sin_editor"; }
if (strpos((string) $vistaContenido, "ecom_cms_resumen_editorial") === false) { $bloqueos[] = "vista_contenido_sin_resumen_editorial"; }
if (strpos((string) $vistaContenido, "ecom_cms_plantilla_visual") !== false) { $bloqueos[] = "vista_contenido_aun_tiene_plantilla_visual"; }
if (strpos((string) $vistaContenido, "/cms/frontend_constructor") === false) { $bloqueos[] = "vista_contenido_sin_link_constructor_frontend"; }
if (strpos((string) $vistaContenido, "/cms/frontend_constructor") === false) { $bloqueos[] = "vista_contenido_sin_link_constructor_visual"; }
if (strpos((string) $vistaContenido, "Vista visual separada") === false) { $bloqueos[] = "vista_contenido_no_separa_visual"; }
if (strpos((string) $vistaContenido, "Preview visual frontend") !== false) { $bloqueos[] = "vista_contenido_aun_mezcla_preview_visual"; }
if (strpos((string) $vistaContenido, "Renderer simulado") !== false) { $bloqueos[] = "vista_contenido_aun_mezcla_renderer"; }
if (strpos((string) $vistaContenido, "ecom_cms_publicabilidad_slots") === false) { $bloqueos[] = "vista_contenido_sin_publicabilidad_slots"; }
if (strpos((string) $vistaContenido, "Cierre read-only de contenido") === false) { $bloqueos[] = "vista_contenido_sin_cierre_readonly"; }
if (strpos((string) $vistaPlantillas, "ecom_cms_tipos") === false) { $bloqueos[] = "vista_plantillas_sin_tipos"; }
if (strpos((string) $vistaPlantillas, "ecom_cms_esquema") === false) { $bloqueos[] = "vista_plantillas_sin_esquema"; }
if (strpos((string) $vistaPlantillas, "data-cms-json-mode=\"manifest\"") === false) { $bloqueos[] = "vista_plantillas_sin_json_manifest"; }
if (strpos((string) $vistaPlantillas, "ecom_cms_contratos") === false) { $bloqueos[] = "vista_plantillas_sin_contratos"; }
if (strpos((string) $vistaPlantillas, "Plantilla de contenido read-only") === false) { $bloqueos[] = "vista_plantillas_sin_aviso_contenido"; }
if (strpos((string) $vistaPlantillas, "erp_cms_manual_uso.md") === false) { $bloqueos[] = "vista_plantillas_sin_link_manual"; }
if (strpos((string) $vistaPersistencia, "ecom_cms_esquema") === false) { $bloqueos[] = "vista_persistencia_sin_esquema"; }
if (strpos((string) $vistaPersistencia, "ecom_cms_esquema_frontend") === false) { $bloqueos[] = "vista_persistencia_sin_esquema_frontend"; }
if (strpos((string) $vistaPersistencia, "Planes DDL read-only") === false) { $bloqueos[] = "vista_persistencia_sin_planes_ddl"; }
if (strpos((string) $vistaPersistencia, "Alcance de persistencia") === false) { $bloqueos[] = "vista_persistencia_sin_alcance"; }
if (strpos((string) $vistaPersistencia, "Checklist de autorizacion") === false) { $bloqueos[] = "vista_persistencia_sin_checklist"; }
if (strpos((string) $vistaPersistencia, "contenido_bloque_guardar_erp") === false) { $bloqueos[] = "vista_persistencia_sin_post_bloque_guardar"; }
if (strpos((string) $vistaPersistencia, "frontend_plantilla_guardar_erp") === false) { $bloqueos[] = "vista_persistencia_sin_post_frontend_plantilla"; }
if (strpos((string) $vistaPersistencia, "frontend_seccion_guardar_erp") === false) { $bloqueos[] = "vista_persistencia_sin_post_frontend_seccion"; }
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
if (strpos((string) $vistaFrontendPlantillas, "data-cms-frontend-nav=\"true\"") === false) { $bloqueos[] = "vista_frontend_plantillas_sin_subnav"; }
if (strpos((string) $vistaFrontendPlantillas, "/cms/frontend_constructor") === false) { $bloqueos[] = "vista_frontend_plantillas_sin_link_constructor"; }
if (strpos((string) $vistaFrontendPlantillas, "/cms/frontend_componentes") === false) { $bloqueos[] = "vista_frontend_plantillas_sin_link_componentes"; }
if (strpos((string) $vistaFrontendPlantillas, "/cms/frontend_activaciones") === false) { $bloqueos[] = "vista_frontend_plantillas_sin_link_activaciones"; }
if (strpos((string) $vistaFrontendPlantillas, "Builder visual read-only") === false) { $bloqueos[] = "vista_frontend_plantillas_sin_builder_visual"; }
if (strpos((string) $vistaFrontendPlantillas, "cms_frontend_tema_selector") === false) { $bloqueos[] = "vista_frontend_plantillas_sin_selector_tema"; }
if (strpos((string) $vistaFrontendPlantillas, "cms_frontend_preview") === false) { $bloqueos[] = "vista_frontend_plantillas_sin_preview_builder"; }
if (strpos((string) $vistaFrontendPlantillas, "cms_frontend_inspector") === false) { $bloqueos[] = "vista_frontend_plantillas_sin_inspector"; }
if (strpos((string) $vistaFrontendPlantillas, "cms_frontend_paleta") === false) { $bloqueos[] = "vista_frontend_plantillas_sin_paleta"; }
if (strpos((string) $vistaFrontendPlantillas, "cms_frontend_renderer") === false) { $bloqueos[] = "vista_frontend_plantillas_sin_renderer"; }
if (strpos((string) $vistaFrontendPlantillas, "cms_frontend_esquema") === false) { $bloqueos[] = "vista_frontend_plantillas_sin_esquema_frontend"; }
if (strpos((string) $vistaFrontendPlantillas, "Tema activo") === false) { $bloqueos[] = "vista_frontend_plantillas_sin_tema_activo"; }
if (strpos((string) $vistaFrontendPlantillas, "frontend_plantilla_guardar_erp") === false) { $bloqueos[] = "vista_frontend_plantillas_sin_post_guardar"; }
if (strpos((string) $vistaFrontendPlantillas, "frontend_seccion_guardar_erp") === false) { $bloqueos[] = "vista_frontend_plantillas_sin_post_seccion"; }
if (strpos((string) $vistaFrontendPlantillas, "erp_cms_manual_uso.md") === false) { $bloqueos[] = "vista_frontend_plantillas_sin_link_manual"; }
if (strpos((string) $vistaFrontendPlantillas, "No edita archivos del ecommerce") === false) { $bloqueos[] = "vista_frontend_plantillas_sin_guardrail_archivos"; }
if (strpos((string) $vistaFrontendConstructor, "Constructor visual") === false) { $bloqueos[] = "vista_frontend_constructor_sin_titulo"; }
if (strpos((string) $vistaFrontendConstructor, "Aqui vive la parte visual") === false) { $bloqueos[] = "vista_frontend_constructor_sin_explicacion_visual"; }
if (strpos((string) $vistaFrontendConstructor, "cms_frontend_preview") === false) { $bloqueos[] = "vista_frontend_constructor_sin_preview"; }
if (strpos((string) $vistaFrontendConstructor, "cms_frontend_inspector") === false) { $bloqueos[] = "vista_frontend_constructor_sin_inspector"; }
if (strpos((string) $vistaFrontendConstructor, "cms_frontend_contenido_estado") === false) { $bloqueos[] = "vista_frontend_constructor_sin_estado_contenido"; }
if (strpos((string) $vistaFrontendConstructor, "Contenido crea los datos editables") === false) { $bloqueos[] = "vista_frontend_constructor_no_explica_contenido"; }
if (strpos((string) $vistaFrontendComponentes, "cms_frontend_componentes") === false) { $bloqueos[] = "vista_frontend_componentes_sin_listado"; }
if (strpos((string) $vistaFrontendComponentes, "data-cms-frontend-nav=\"true\"") === false) { $bloqueos[] = "vista_frontend_componentes_sin_subnav"; }
if (strpos((string) $vistaFrontendComponentes, "/cms/frontend_constructor") === false) { $bloqueos[] = "vista_frontend_componentes_sin_link_constructor"; }
if (strpos((string) $vistaFrontendComponentes, "/cms/frontend_plantillas") === false) { $bloqueos[] = "vista_frontend_componentes_sin_link_plantillas"; }
if (strpos((string) $vistaFrontendComponentes, "/cms/frontend_activaciones") === false) { $bloqueos[] = "vista_frontend_componentes_sin_link_activaciones"; }
if (strpos((string) $vistaFrontendComponentes, "Explorador visual de componentes") === false) { $bloqueos[] = "vista_frontend_componentes_sin_explorador_visual"; }
if (strpos((string) $vistaFrontendComponentes, "cms_frontend_componentes_selector") === false) { $bloqueos[] = "vista_frontend_componentes_sin_selector"; }
if (strpos((string) $vistaFrontendComponentes, "cms_frontend_componente_preview") === false) { $bloqueos[] = "vista_frontend_componentes_sin_preview"; }
if (strpos((string) $vistaFrontendComponentes, "cms_frontend_componente_compatibilidad") === false) { $bloqueos[] = "vista_frontend_componentes_sin_compatibilidad"; }
if (strpos((string) $vistaFrontendComponentes, "cms_frontend_componente_uso") === false) { $bloqueos[] = "vista_frontend_componentes_sin_uso"; }
if (strpos((string) $vistaFrontendComponentes, "cms_frontend_renderer") === false) { $bloqueos[] = "vista_frontend_componentes_sin_renderer"; }
if (strpos((string) $vistaFrontendComponentes, "cms_frontend_esquema") === false) { $bloqueos[] = "vista_frontend_componentes_sin_esquema_frontend"; }
if (strpos((string) $vistaFrontendComponentes, "Tema activo") === false) { $bloqueos[] = "vista_frontend_componentes_sin_tema_activo"; }
if (strpos((string) $vistaFrontendComponentes, "frontend_plantilla_estatus_erp") === false) { $bloqueos[] = "vista_frontend_componentes_sin_post_estatus"; }
if (strpos((string) $vistaFrontendComponentes, "frontend_seccion_estatus_erp") === false) { $bloqueos[] = "vista_frontend_componentes_sin_post_seccion_estatus"; }
if (strpos((string) $vistaFrontendComponentes, "erp_cms_manual_uso.md") === false) { $bloqueos[] = "vista_frontend_componentes_sin_link_manual"; }
if (strpos((string) $vistaFrontendComponentes, "Catalogo de componentes read-only") === false) { $bloqueos[] = "vista_frontend_componentes_sin_readonly"; }
if (strpos((string) $vistaFrontendActivaciones, "Activaciones read-only") === false) { $bloqueos[] = "vista_frontend_activaciones_sin_readonly"; }
if (strpos((string) $vistaFrontendActivaciones, "data-cms-frontend-nav=\"true\"") === false) { $bloqueos[] = "vista_frontend_activaciones_sin_subnav"; }
if (strpos((string) $vistaFrontendActivaciones, "/cms/frontend_constructor") === false) { $bloqueos[] = "vista_frontend_activaciones_sin_link_constructor"; }
if (strpos((string) $vistaFrontendActivaciones, "/cms/frontend_plantillas") === false) { $bloqueos[] = "vista_frontend_activaciones_sin_link_plantillas"; }
if (strpos((string) $vistaFrontendActivaciones, "/cms/frontend_componentes") === false) { $bloqueos[] = "vista_frontend_activaciones_sin_link_componentes"; }
if (strpos((string) $vistaFrontendActivaciones, "cms_frontend_activaciones") === false) { $bloqueos[] = "vista_frontend_activaciones_sin_matriz"; }
if (strpos((string) $vistaFrontendActivaciones, "Flujo futuro para cambiar plantilla") === false) { $bloqueos[] = "vista_frontend_activaciones_sin_flujo"; }
if (strpos((string) $vistaFrontendActivaciones, "erp_cms_manual_uso.md") === false) { $bloqueos[] = "vista_frontend_activaciones_sin_link_manual"; }
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
if (strpos((string) $js, "function renderPlantillaVisual") === false) { $bloqueos[] = "js_sin_compat_plantilla_visual"; }
if (strpos((string) $js, "plantilla_vista") === false) { $bloqueos[] = "js_sin_payload_plantilla_vista"; }
if (strpos((string) $js, "function renderStorefrontPreview") === false) { $bloqueos[] = "js_sin_preview_storefront"; }
if (strpos((string) $js, "function renderStorefrontHero") === false) { $bloqueos[] = "js_sin_preview_hero"; }
if (strpos((string) $js, "/cms/frontend_admin_estado_erp") === false) { $bloqueos[] = "js_sin_estado_frontend_persistencia"; }
if (strpos((string) $js, "function renderEstadoFrontend") === false) { $bloqueos[] = "js_sin_render_estado_frontend"; }
if (strpos((string) $js, "function estadoVigencia") === false) { $bloqueos[] = "js_sin_estado_vigencia"; }
if (strpos((string) $js, "function renderPublicabilidadSlots") === false) { $bloqueos[] = "js_sin_publicabilidad_slots"; }
if (strpos((string) $js, "function revisarSlot") === false) { $bloqueos[] = "js_sin_revision_slot"; }
if (strpos((string) $jsFrontend, "/cms/frontend_admin_manifest_erp") === false) { $bloqueos[] = "js_frontend_no_consume_manifest"; }
if (strpos((string) $jsFrontend, "/cms/frontend_admin_estado_erp") === false) { $bloqueos[] = "js_frontend_no_consume_estado"; }
if (strpos((string) $jsFrontend, "function renderPlantillas") === false) { $bloqueos[] = "js_frontend_sin_plantillas"; }
if (strpos((string) $jsFrontend, "function renderComponentes") === false) { $bloqueos[] = "js_frontend_sin_componentes"; }
if (strpos((string) $jsFrontend, "function renderEsquema") === false) { $bloqueos[] = "js_frontend_sin_esquema"; }
if (strpos((string) $jsFrontend, "function renderBuilder") === false) { $bloqueos[] = "js_frontend_sin_builder"; }
if (strpos((string) $jsFrontend, "function renderBuilderCanvas") === false) { $bloqueos[] = "js_frontend_sin_canvas_builder"; }
if (strpos((string) $jsFrontend, "/cms/contenido_admin_pagina_erp") === false) { $bloqueos[] = "js_frontend_no_conecta_contenido_pagina"; }
if (strpos((string) $jsFrontend, "function bloquesDeSeccion") === false) { $bloqueos[] = "js_frontend_sin_bloques_por_slot"; }
if (strpos((string) $jsFrontend, "Contenido conectado") === false) { $bloqueos[] = "js_frontend_sin_estado_contenido_conectado"; }
if (strpos((string) $jsFrontend, "function renderInspector") === false) { $bloqueos[] = "js_frontend_sin_inspector"; }
if (strpos((string) $jsFrontend, "function renderPaleta") === false) { $bloqueos[] = "js_frontend_sin_paleta"; }
if (strpos((string) $jsFrontend, "function renderComponentesSelector") === false) { $bloqueos[] = "js_frontend_sin_selector_componentes"; }
if (strpos((string) $jsFrontend, "function renderComponenteDetalle") === false) { $bloqueos[] = "js_frontend_sin_detalle_componente"; }
if (strpos((string) $jsFrontend, "function usosComponente") === false) { $bloqueos[] = "js_frontend_sin_uso_componentes"; }
if (strpos((string) $jsFrontend, "function renderActivaciones") === false) { $bloqueos[] = "js_frontend_sin_activaciones"; }
if (strpos((string) $jsFrontend, "function plantillaPorCodigo") === false) { $bloqueos[] = "js_frontend_sin_lookup_plantilla"; }
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
if (strpos((string) $sidebar, "/cms/frontend_constructor") === false) { $bloqueos[] = "sidebar_sin_cms_frontend_constructor"; }
if (strpos((string) $sidebar, "/cms/frontend_componentes") === false) { $bloqueos[] = "sidebar_sin_cms_frontend_componentes"; }
if (strpos((string) $sidebar, "/cms/frontend_activaciones") === false) { $bloqueos[] = "sidebar_sin_cms_frontend_activaciones"; }
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
if (strpos((string) $controladorCms, "public function frontend_constructor") === false) { $bloqueos[] = "controlador_sin_ruta_frontend_constructor"; }
if (strpos((string) $controladorCms, "public function frontend_componentes") === false) { $bloqueos[] = "controlador_sin_ruta_frontend_componentes"; }
if (strpos((string) $controladorCms, "public function frontend_activaciones") === false) { $bloqueos[] = "controlador_sin_ruta_frontend_activaciones"; }
if (strpos((string) $controladorCms, "public function frontend_admin_manifest_erp") === false) { $bloqueos[] = "controlador_sin_manifest_frontend"; }
if (strpos((string) $controladorCms, "public function frontend_admin_estado_erp") === false) { $bloqueos[] = "controlador_sin_estado_frontend"; }
if (strpos((string) $controladorCms, "public function index") === false) { $bloqueos[] = "controlador_sin_ruta_index_cms"; }
if (strpos((string) $controladorCms, "public function contenido_bloque_guardar_erp") === false) { $bloqueos[] = "controlador_sin_post_bloque_guardar"; }
if (strpos((string) $controladorCms, "public function contenido_bloque_estatus_erp") === false) { $bloqueos[] = "controlador_sin_post_bloque_estatus"; }
if (strpos((string) $controladorCms, "public function contenido_publicacion_guardar_erp") === false) { $bloqueos[] = "controlador_sin_post_publicacion_guardar"; }
if (strpos((string) $controladorCms, "public function contenido_publicacion_estatus_erp") === false) { $bloqueos[] = "controlador_sin_post_publicacion_estatus"; }
if (strpos((string) $controladorCms, "public function frontend_plantilla_guardar_erp") === false) { $bloqueos[] = "controlador_sin_post_frontend_plantilla_guardar"; }
if (strpos((string) $controladorCms, "public function frontend_plantilla_estatus_erp") === false) { $bloqueos[] = "controlador_sin_post_frontend_plantilla_estatus"; }
if (strpos((string) $controladorCms, "public function frontend_seccion_guardar_erp") === false) { $bloqueos[] = "controlador_sin_post_frontend_seccion_guardar"; }
if (strpos((string) $controladorCms, "public function frontend_seccion_estatus_erp") === false) { $bloqueos[] = "controlador_sin_post_frontend_seccion_estatus"; }
if (strpos((string) $controladorCms, "respuestaEscrituraCmsFrontendBloqueada") === false) { $bloqueos[] = "controlador_post_frontend_no_bloquea_escritura"; }
if (strpos((string) $controladorCms, "respuestaEscrituraCmsBloqueada") === false) { $bloqueos[] = "controlador_post_no_bloquea_escritura"; }
if (strpos((string) $controladorCms, "\"no_escribe_bd\" => true") === false) { $bloqueos[] = "controlador_post_sin_guardrail_no_escribe_bd"; }
if (strpos((string) $controladorCms, "\"no_edita_archivos_frontend\" => true") === false) { $bloqueos[] = "controlador_post_frontend_sin_guardrail_archivos"; }
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
if (strpos((string) $manualCms, "erp_ecommerce_frontend_temas") === false) { $bloqueos[] = "manual_persistencia_sin_tablas_frontend"; }
if (strpos((string) $manualCms, "Planes DDL read-only") === false) { $bloqueos[] = "manual_persistencia_sin_planes_ddl"; }
if (strpos((string) $manualCms, "uat_cms_persistencia_preflight.php") === false) { $bloqueos[] = "manual_persistencia_sin_uat_preflight"; }
if (strpos((string) $manualCms, "uat_cms_seed_readonly.php") === false) { $bloqueos[] = "manual_persistencia_sin_uat_seed"; }
if (strpos((string) $manualCms, "Activar endpoints POST con CSRF") === false) { $bloqueos[] = "manual_persistencia_sin_post_csrf"; }
if (strpos((string) $manualCms, "CMS > Frontend > Plantillas de vista") === false) { $bloqueos[] = "manual_sin_frontend_plantillas"; }
if (strpos((string) $manualCms, "CMS > Frontend > Constructor visual") === false) { $bloqueos[] = "manual_sin_frontend_constructor"; }
if (strpos((string) $manualCms, "Navegacion del submodulo") === false) { $bloqueos[] = "manual_frontend_sin_subnav"; }
if (strpos((string) $manualCms, "Builder visual read-only") === false) { $bloqueos[] = "manual_frontend_sin_builder_visual"; }
if (strpos((string) $manualCms, "Paleta de componentes") === false) { $bloqueos[] = "manual_frontend_sin_paleta_componentes"; }
if (strpos((string) $manualCms, "El builder visual es una previsualizacion administrativa") === false) { $bloqueos[] = "manual_frontend_no_aclara_preview_admin"; }
if (strpos((string) $manualCms, "wokiee_home_default") === false) { $bloqueos[] = "manual_frontend_sin_wokiee_home"; }
if (strpos((string) $manualCms, "plantilla_vista + contenido.slots") === false) { $bloqueos[] = "manual_frontend_sin_contrato_renderer"; }
if (strpos((string) $manualCms, "No edita archivos") === false) { $bloqueos[] = "manual_frontend_sin_guardrail_archivos"; }
if (strpos((string) $manualCms, "CMS > Frontend > Componentes") === false) { $bloqueos[] = "manual_sin_frontend_componentes"; }
if (strpos((string) $manualCms, "Explorador visual de componentes") === false) { $bloqueos[] = "manual_componentes_sin_explorador_visual"; }
if (strpos((string) $manualCms, "Uso en plantillas") === false) { $bloqueos[] = "manual_componentes_sin_uso_plantillas"; }
if (strpos((string) $manualCms, "El preview del explorador es administrativo") === false) { $bloqueos[] = "manual_componentes_no_aclara_preview_admin"; }
if (strpos((string) $manualCms, "Componentes iniciales") === false) { $bloqueos[] = "manual_componentes_sin_lista_inicial"; }
if (strpos((string) $manualCms, "HeroSlider") === false) { $bloqueos[] = "manual_componentes_sin_hero_slider"; }
if (strpos((string) $manualCms, "Relacion componente / bloque / slot") === false) { $bloqueos[] = "manual_componentes_sin_relacion"; }
if (strpos((string) $manualCms, "CMS > Frontend > Activaciones") === false) { $bloqueos[] = "manual_sin_frontend_activaciones"; }
if (strpos((string) $manualCms, "erp_ecommerce_frontend_plantilla_activas") === false) { $bloqueos[] = "manual_activaciones_sin_tabla"; }
if (strpos((string) $manualCms, "Matriz de activacion") === false) { $bloqueos[] = "manual_activaciones_sin_matriz"; }
if (strpos((string) $manualCms, "Resumen editorial") === false) { $bloqueos[] = "manual_sin_resumen_editorial"; }
if (strpos((string) $manualCms, "La vista visual esta separada en `/cms/frontend_constructor`") === false) { $bloqueos[] = "manual_no_separa_vista_visual"; }
if (strpos((string) $manualCms, "Contenido no es la tienda final") === false) { $bloqueos[] = "manual_no_aclara_contenido_no_tienda"; }
if (strpos((string) $manualCms, "no genera archivos ni HTML productivo") === false) { $bloqueos[] = "manual_preview_no_aclara_html_final"; }
if (strpos((string) $manualCms, "Publicabilidad por slot") === false) { $bloqueos[] = "manual_sin_publicabilidad_slot"; }
if (strpos((string) $manualCms, "/cms/frontend_plantillas") === false) { $bloqueos[] = "manual_sin_frontend_pendiente"; }
if (strpos((string) $manualCms, "erp_cms_frontend_renderer_contrato.md") === false) { $bloqueos[] = "manual_sin_contrato_renderer_doc"; }
if (strpos((string) $manualCms, "erp_cms_visual_builder_wokiee_plan.md") === false) { $bloqueos[] = "manual_sin_plan_builder_wokiee"; }
if (strpos((string) $manualCms, "tablas separadas para layouts, componentes, plantillas, secciones y activaciones") === false) { $bloqueos[] = "manual_frontend_sin_esquema_futuro"; }
if (strpos((string) $manualCms, "wokiee_artiani") === false) { $bloqueos[] = "manual_frontend_sin_tema_wokiee_artiani"; }
if (strpos((string) $manualCms, "/cms/frontend_plantilla_guardar_erp") === false) { $bloqueos[] = "manual_sin_post_frontend_plantilla_guardar"; }
if (strpos((string) $manualCms, "/cms/frontend_seccion_guardar_erp") === false) { $bloqueos[] = "manual_sin_post_frontend_seccion_guardar"; }
if (strpos((string) $contratoRenderer, "CMS - Contrato frontend renderer") === false) { $bloqueos[] = "contrato_renderer_sin_titulo"; }
if (strpos((string) $contratoRenderer, "/ecommercePublico/configuracion_inicial") === false) { $bloqueos[] = "contrato_renderer_sin_configuracion_inicial"; }
if (strpos((string) $contratoRenderer, "/ecommercePublico/contenido_pagina") === false) { $bloqueos[] = "contrato_renderer_sin_contenido_pagina"; }
if (strpos((string) $contratoRenderer, "plantilla_vista") === false) { $bloqueos[] = "contrato_renderer_sin_plantilla_vista"; }
if (strpos((string) $contratoRenderer, "HeroSlider") === false) { $bloqueos[] = "contrato_renderer_sin_hero_slider"; }
if (strpos((string) $contratoRenderer, "No llamar endpoints internos `/cms/*`") === false) { $bloqueos[] = "contrato_renderer_sin_guardrail_cms_interno"; }
if (strpos((string) $planBuilderWokiee, "CMS - Builder visual Wokiee ecommerce") === false) { $bloqueos[] = "plan_builder_wokiee_sin_titulo"; }
if (strpos((string) $planBuilderWokiee, "WokieeHeroRevolution") === false) { $bloqueos[] = "plan_builder_wokiee_sin_hero"; }
if (strpos((string) $planBuilderWokiee, "No conviene que el CMS guarde HTML libre") === false) { $bloqueos[] = "plan_builder_wokiee_sin_guardrail_html"; }
if (strpos((string) $planBuilderWokiee, "app/Pages/catalog.php") === false) { $bloqueos[] = "plan_builder_wokiee_sin_catalog_php"; }
if (strpos((string) $planBuilderWokiee, "erp_ecommerce_frontend_temas") === false) { $bloqueos[] = "plan_builder_wokiee_sin_tabla_temas"; }
if (strpos((string) $uatPersistenciaPreflight, "cms_persistencia_lista_para_respaldo_y_autorizacion") === false) { $bloqueos[] = "uat_preflight_persistencia_sin_senal"; }
if (strpos((string) $uatPersistenciaPreflight, '"total" => $ddlContenido + $ddlFrontend') === false) { $bloqueos[] = "uat_preflight_persistencia_sin_total_ddl"; }
if (strpos((string) $uatSeedReadonly, "cms_seed_base_verificado") === false) { $bloqueos[] = "uat_seed_readonly_sin_senal"; }
if (strpos((string) $uatSeedReadonly, "semilla_creo_bloques_comerciales") === false) { $bloqueos[] = "uat_seed_readonly_no_protege_bloques"; }

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
    "tema_visual_activo" => valorCmsAdmin($manifest, array("depurar", "tema_visual_activo", "codigo"), ""),
    "tipos_bloque_total" => count(valorCmsAdmin($manifest, array("depurar", "tipos_bloque"), array())),
    "plantillas_vista_total" => count(valorCmsAdmin($manifest, array("depurar", "plantillas_vista"), array())),
    "componentes_frontend_total" => count(valorCmsAdmin($manifest, array("depurar", "componentes_frontend"), array())),
    "fuente_estructura" => valorCmsAdmin($manifest, array("depurar", "admin", "fuente_estructura"), "fallback")
  ),
  "frontend" => array(
    "modo" => valorCmsAdmin($frontendManifest, array("depurar", "modo"), ""),
    "fuente_estructura" => valorCmsAdmin($frontendManifest, array("depurar", "fuente_estructura"), "fallback"),
    "tema_activo" => valorCmsAdmin($frontendManifest, array("depurar", "tema_activo", "codigo"), ""),
    "temas_total" => count(valorCmsAdmin($frontendManifest, array("depurar", "temas_disponibles"), array())),
    "plantilla_vista_home" => valorCmsAdmin($home, array("depurar", "plantilla_vista", "codigo"), ""),
    "configuracion_inicial_home" => valorCmsAdmin($configuracionInicial, array("depurar", "contenido_inicial", "home", "plantilla_vista", "codigo"), ""),
    "componentes_total" => count(valorCmsAdmin($frontendManifest, array("depurar", "componentes"), array())),
    "activaciones_total" => count(valorCmsAdmin($frontendManifest, array("depurar", "activaciones"), array())),
    "plantillas_total" => count(valorCmsAdmin($frontendManifest, array("depurar", "plantillas_vista"), array())),
    "sin_codigo_libre" => valorCmsAdmin($frontendManifest, array("depurar", "guardrails", "no_js_libre"), false)
  ),
  "esquema_frontend" => array(
    "read_only" => valorCmsAdmin($planFrontend, array("depurar", "read_only"), false),
    "ddl_total" => valorCmsAdmin($planFrontend, array("depurar", "ddl_total"), 0),
    "tablas_faltantes" => valorCmsAdmin($auditoriaFrontend, array("depurar", "tablas_faltantes"), 0)
  ),
  "ui" => array(
    "editor_local" => true,
    "preview_json" => "/cms/json",
    "validacion_local" => true,
    "borrador_local" => true,
    "intercambio_json" => true,
    "sidebar" => array("/cms/contenido", "/cms/plantillas", "/cms/persistencia", "/cms/slots", "/cms/media", "/cms/json", "/cms/frontend_constructor", "/cms/frontend_plantillas", "/cms/frontend_componentes", "/cms/frontend_activaciones"),
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
      "/cms/contenido_publicacion_estatus_erp",
      "/cms/frontend_plantilla_guardar_erp",
      "/cms/frontend_plantilla_estatus_erp",
      "/cms/frontend_seccion_guardar_erp",
      "/cms/frontend_seccion_estatus_erp"
    )
  ),
  "guardrails" => array(
    "no_escribe_bd" => true,
    "no_ejecuta_ddl" => true,
    "no_modifica_catalogo" => true,
    "no_modifica_inventario" => true
  ),
  "manual" => "docs/erp_cms_manual_uso.md",
  "contrato_renderer" => "docs/erp_cms_frontend_renderer_contrato.md",
  "plan_builder_wokiee" => "docs/erp_cms_visual_builder_wokiee_plan.md"
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
