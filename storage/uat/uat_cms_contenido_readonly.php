<?php

/**
 * Documentacion IA: Codex GPT-5, 2026-08-10.
 * Proposito: validar contratos internos del modulo CMS contenido con persistencia parcial.
 * Impacto: protege estado, manifest, preview y primer guardado de bloques sin publicar contenido real.
 * Contrato: permite guardar bloques borrador; no ejecuta DDL, no publica slots, no sube media ni modifica catalogo/inventario.
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
$planMedia = $esquema->planActualizarCmsMediaBiblioteca(false);
$estado = $cms->contenidoAdminEstadoInterno($auditoria, $plan);
$mediaPreflight = $cms->mediaAdminPreflightInterno($planMedia);
$mediaListado = $cms->mediaAdminListarInterno(array("limite" => 5));
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
$vistaFrontendActual = file_get_contents("../app/vistas/paginas/apps/erp/cms/frontend_actual.php");
$vistaFrontendHome = file_get_contents("../app/vistas/paginas/apps/erp/cms/frontend_home.php");
$vistaFrontendGlobal = file_get_contents("../app/vistas/paginas/apps/erp/cms/frontend_global.php");
$vistaFrontendNavegacion = file_get_contents("../app/vistas/paginas/apps/erp/cms/frontend_navegacion.php");
$vistaFrontendCategorias = file_get_contents("../app/vistas/paginas/apps/erp/cms/frontend_categorias.php");
$vistaFrontendMarcas = file_get_contents("../app/vistas/paginas/apps/erp/cms/frontend_marcas.php");
$vistaFrontendPaginas = file_get_contents("../app/vistas/paginas/apps/erp/cms/frontend_paginas.php");
$vistaFrontendPoliticas = file_get_contents("../app/vistas/paginas/apps/erp/cms/frontend_politicas.php");
$vistaFrontendPlaceholder = file_get_contents("../app/vistas/paginas/apps/erp/cms/frontend_placeholder.php");
$vistaFrontendComponentes = file_get_contents("../app/vistas/paginas/apps/erp/cms/frontend_componentes.php");
$vistaFrontendActivaciones = file_get_contents("../app/vistas/paginas/apps/erp/cms/frontend_activaciones.php");
$js = file_get_contents("../public/assets/js/custom/apps/erp/cms/contenido.js");
$jsFrontend = file_get_contents("../public/assets/js/custom/apps/erp/cms/frontend.js");
$jsFrontendActual = file_get_contents("../public/assets/js/custom/apps/erp/cms/frontend_actual.js");
$jsMedia = file_get_contents("../public/assets/js/custom/apps/erp/cms/media.js");
$sidebar = file_get_contents("../app/vistas/includes/header/sidebar.php");
$controladorCms = file_get_contents("../app/controladores/Cms.php");
$modeloPublico = file_get_contents("../app/modelos/EcommerceCatalogoPublico.php");
$modeloEsquema = file_get_contents("../app/modelos/EcommercePublicoEsquema.php");
$seguridadEsquema = file_get_contents("../app/modelos/SeguridadEsquema.php");
$manualCms = file_get_contents("../docs/erp_cms_manual_uso.md");
$planCmsApi = file_get_contents("../docs/erp_cms_api_ecommerce_publico_artiani_plan.md");
$contratoRenderer = file_get_contents("../docs/erp_cms_frontend_renderer_contrato.md");
$planBuilderWokiee = file_get_contents("../docs/erp_cms_visual_builder_wokiee_plan.md");
$uatPersistenciaPreflight = file_get_contents("../storage/uat/uat_cms_persistencia_preflight.php");
$uatSeedReadonly = file_get_contents("../storage/uat/uat_cms_seed_readonly.php");
$uatMediaPreflight = file_get_contents("../storage/uat/uat_cms_media_persistencia_preflight.php");

$bloqueos = array();

if (!empty($estado["error"])) { $bloqueos[] = "estado_error"; }
if (!empty($manifest["error"])) { $bloqueos[] = "manifest_error"; }
if (!empty($frontendManifest["error"])) { $bloqueos[] = "frontend_manifest_error"; }
if (!empty($mediaPreflight["error"])) { $bloqueos[] = "media_preflight_error"; }
if (!empty($mediaListado["error"])) { $bloqueos[] = "media_listado_error"; }
if (valorCmsAdmin($mediaPreflight, array("depurar", "carpeta_publica_propuesta"), "") !== "/assets/media/cms/ecommerce") { $bloqueos[] = "media_preflight_carpeta_incorrecta"; }
if (valorCmsAdmin($mediaPreflight, array("depurar", "tabla_archivos"), "") !== "erp_ecommerce_media_archivos") { $bloqueos[] = "media_preflight_sin_tabla_archivos"; }
if (valorCmsAdmin($mediaPreflight, array("depurar", "tabla_usos"), "") !== "erp_ecommerce_media_usos") { $bloqueos[] = "media_preflight_sin_tabla_usos"; }
if (empty(valorCmsAdmin($mediaPreflight, array("depurar", "guardrails", "no_mueve_archivos"), false))) { $bloqueos[] = "media_preflight_no_declara_no_mueve_archivos"; }
if (empty(valorCmsAdmin($mediaPreflight, array("depurar", "guardrails", "requiere_respaldo_antes_ddl"), false))) { $bloqueos[] = "media_preflight_sin_respaldo"; }
if (empty(valorCmsAdmin($mediaListado, array("depurar", "guardrails", "no_mueve_archivos"), false))) { $bloqueos[] = "media_listado_no_declara_no_mueve_archivos"; }
if (!empty($home["error"])) { $bloqueos[] = "home_error"; }
if (!empty($categoria["error"])) { $bloqueos[] = "categoria_error"; }
if (empty(valorCmsAdmin($estado, array("depurar", "guardrails", "persistencia_contenido_interna"), false))) { $bloqueos[] = "estado_sin_persistencia_contenido_interna"; }
if (empty(valorCmsAdmin($estado, array("depurar", "guardrails", "no_ejecuta_ddl"), false))) { $bloqueos[] = "estado_no_declara_no_ejecuta_ddl"; }
if (valorCmsAdmin($estado, array("depurar", "persistencia_real"), false) !== true) { $bloqueos[] = "persistencia_parcial_no_activa"; }
if (valorCmsAdmin($estado, array("depurar", "persistencia_alcance"), "") !== "bloques_y_publicaciones_internas") { $bloqueos[] = "persistencia_alcance_incorrecto"; }
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
if (strpos((string) $vistaContenido, "Cierre de contenido") === false) { $bloqueos[] = "vista_contenido_sin_cierre_contenido"; }
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
if (strpos((string) $vistaMedia, "Biblioteca local con preflight de persistencia") === false) { $bloqueos[] = "vista_media_sin_biblioteca_preflight"; }
if (strpos((string) $vistaMedia, "cms_media_preflight") === false) { $bloqueos[] = "vista_media_sin_preflight"; }
if (strpos((string) $vistaMedia, "/cms/media_admin_preflight_erp") === false) { $bloqueos[] = "vista_media_sin_endpoint_preflight"; }
if (strpos((string) $vistaMedia, "cms_media_biblioteca") === false) { $bloqueos[] = "vista_media_sin_grid_biblioteca"; }
if (strpos((string) $vistaMedia, "cms_media_archivo") === false) { $bloqueos[] = "vista_media_sin_selector_archivo"; }
if (strpos((string) $vistaMedia, "cms_media_limpiar_archivados") === false) { $bloqueos[] = "vista_media_sin_limpiar_archivados"; }
if (strpos((string) $vistaMedia, "media.js") === false) { $bloqueos[] = "vista_media_no_carga_js_media"; }
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
if (strpos((string) $vistaFrontendConstructor, "Constructor de paginas ecommerce") === false) { $bloqueos[] = "vista_frontend_constructor_sin_titulo_paginas"; }
if (strpos((string) $vistaFrontendConstructor, "Este es el lugar principal para construir tu tienda") === false) { $bloqueos[] = "vista_frontend_constructor_sin_explicacion_visual"; }
if (strpos((string) $vistaFrontendConstructor, "cms_frontend_preview") === false) { $bloqueos[] = "vista_frontend_constructor_sin_preview"; }
if (strpos((string) $vistaFrontendConstructor, "Pagina construida") === false) { $bloqueos[] = "vista_frontend_constructor_sin_pagina_construida"; }
if (strpos((string) $vistaFrontendConstructor, "Paginas ecommerce") === false) { $bloqueos[] = "vista_frontend_constructor_sin_paginas_ecommerce"; }
if (strpos((string) $vistaFrontendConstructor, "cms_frontend_paginas") === false) { $bloqueos[] = "vista_frontend_constructor_sin_selector_paginas"; }
if (strpos((string) $vistaFrontendConstructor, "Secciones de esta pagina") === false) { $bloqueos[] = "vista_frontend_constructor_sin_mapa_secciones"; }
if (strpos((string) $vistaFrontendConstructor, "cms_frontend_mapa_secciones") === false) { $bloqueos[] = "vista_frontend_constructor_sin_id_mapa_secciones"; }
if (strpos((string) $vistaFrontendConstructor, "Edicion rapida") === false) { $bloqueos[] = "vista_frontend_constructor_sin_edicion_rapida"; }
if (strpos((string) $vistaFrontendConstructor, "cms_frontend_editor_rapido") === false) { $bloqueos[] = "vista_frontend_constructor_sin_id_editor_rapido"; }
if (strpos((string) $vistaFrontendConstructor, "cms_frontend_estado_home") === false) { $bloqueos[] = "vista_frontend_constructor_sin_estado_home"; }
if (strpos((string) $vistaFrontendConstructor, "cms-home-status") === false) { $bloqueos[] = "vista_frontend_constructor_sin_panel_estado_home"; }
if (strpos((string) $vistaFrontendConstructor, "cms_frontend_agregar_modulo") === false) { $bloqueos[] = "vista_frontend_constructor_sin_boton_agregar_modulo"; }
if (strpos((string) $vistaFrontendConstructor, "cms_frontend_preview_full") === false) { $bloqueos[] = "vista_frontend_constructor_sin_boton_preview_full"; }
if (strpos((string) $vistaFrontendConstructor, "cms_frontend_modulos") === false) { $bloqueos[] = "vista_frontend_constructor_sin_paleta_modulos"; }
if (strpos((string) $vistaFrontendConstructor, "cms_frontend_preview_modal") === false) { $bloqueos[] = "vista_frontend_constructor_sin_modal_preview"; }
if (strpos((string) $vistaFrontendConstructor, "cms-section-map__actions") === false) { $bloqueos[] = "vista_frontend_constructor_sin_acciones_mapa_secciones"; }
if (strpos((string) $vistaFrontendConstructor, "cms-front-page-nav") === false) { $bloqueos[] = "vista_frontend_constructor_sin_nav_visual"; }
if (strpos((string) $vistaFrontendConstructor, "cms-front-card-image") === false) { $bloqueos[] = "vista_frontend_constructor_sin_cards_visuales"; }
if (strpos((string) $vistaFrontendConstructor, "cms_frontend_inspector") === false) { $bloqueos[] = "vista_frontend_constructor_sin_inspector"; }
if (strpos((string) $vistaFrontendConstructor, "cms_frontend_contenido_estado") === false) { $bloqueos[] = "vista_frontend_constructor_sin_estado_contenido"; }
if (strpos((string) $vistaFrontendConstructor, "cms_frontend_cargar_borrador") === false) { $bloqueos[] = "vista_frontend_constructor_sin_boton_borrador"; }
if (strpos((string) $vistaFrontendConstructor, "cms_frontend_ignorar_borrador") === false) { $bloqueos[] = "vista_frontend_constructor_sin_boton_api"; }
if (strpos((string) $vistaFrontendConstructor, "Editor avanzado") === false) { $bloqueos[] = "vista_frontend_constructor_no_explica_editor_avanzado"; }
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
if (strpos((string) $js, "function aplicarEntradaUrl") === false) { $bloqueos[] = "js_contenido_no_lee_parametros_url"; }
if (strpos((string) $js, "slotInicialUrlValido") === false) { $bloqueos[] = "js_contenido_no_abre_slot_url"; }
if (strpos((string) $js, "function aplicarForm") === false) { $bloqueos[] = "js_sin_aplicar_form"; }
if (strpos((string) $js, "function ejecutarAccionBloque") === false) { $bloqueos[] = "js_sin_acciones_bloque"; }
if (strpos((string) $js, "function validarContenido") === false) { $bloqueos[] = "js_sin_validacion_local"; }
if (strpos((string) $js, "function guardarBorradorLocal") === false) { $bloqueos[] = "js_sin_borrador_local"; }
if (strpos((string) $js, "function guardarBloqueBd") === false) { $bloqueos[] = "js_sin_guardar_bloque_bd"; }
if (strpos((string) $js, "function guardarPublicacionBd") === false) { $bloqueos[] = "js_sin_guardar_publicacion_bd"; }
if (strpos((string) $js, "function cambiarEstatusPublicacionBd") === false) { $bloqueos[] = "js_sin_cambiar_estatus_publicacion_bd"; }
if (strpos((string) $js, "function cambiarEstatusBloqueBd") === false) { $bloqueos[] = "js_sin_cambiar_estatus_bloque_bd"; }
if (strpos((string) $js, "function cargarBloquesBd") === false) { $bloqueos[] = "js_sin_cargar_bloques_bd"; }
if (strpos((string) $js, "function insertarBloqueBd") === false) { $bloqueos[] = "js_sin_insertar_bloque_bd"; }
if (strpos((string) $js, "function renderBloquesBd") === false) { $bloqueos[] = "js_sin_render_biblioteca_bd"; }
if (strpos((string) $js, "/cms/contenido_admin_bloques_erp") === false) { $bloqueos[] = "js_sin_endpoint_listar_bloques_bd"; }
if (strpos((string) $js, "/cms/contenido_bloque_guardar_erp") === false) { $bloqueos[] = "js_sin_endpoint_guardar_bloque"; }
if (strpos((string) $js, "/cms/contenido_bloque_estatus_erp") === false) { $bloqueos[] = "js_sin_endpoint_estatus_bloque"; }
if (strpos((string) $js, "/cms/contenido_publicacion_guardar_erp") === false) { $bloqueos[] = "js_sin_endpoint_guardar_publicacion"; }
if (strpos((string) $js, "/cms/contenido_publicacion_estatus_erp") === false) { $bloqueos[] = "js_sin_endpoint_estatus_publicacion"; }
if (strpos((string) $js, "X-CSRF-Token") === false) { $bloqueos[] = "js_guardar_bloque_sin_csrf"; }
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
if (strpos((string) $jsFrontend, "function renderPaginasEcommerce") === false) { $bloqueos[] = "js_frontend_sin_paginas_ecommerce"; }
if (strpos((string) $jsFrontend, "function renderMapaSecciones") === false) { $bloqueos[] = "js_frontend_sin_mapa_secciones"; }
if (strpos((string) $jsFrontend, "function renderEstadoHome") === false) { $bloqueos[] = "js_frontend_sin_estado_home"; }
if (strpos((string) $jsFrontend, "function renderPaletaModulos") === false) { $bloqueos[] = "js_frontend_sin_paleta_modulos"; }
if (strpos((string) $jsFrontend, "function agregarModuloHome") === false) { $bloqueos[] = "js_frontend_sin_agregar_modulo_home"; }
if (strpos((string) $jsFrontend, "function abrirPreviewCompleto") === false) { $bloqueos[] = "js_frontend_sin_preview_completo"; }
if (strpos((string) $jsFrontend, "function renderPaginaPreviewPublica") === false) { $bloqueos[] = "js_frontend_sin_render_preview_publica"; }
if (strpos((string) $jsFrontend, "cms_frontend_preview_full") === false) { $bloqueos[] = "js_frontend_sin_evento_preview_full"; }
if (strpos((string) $jsFrontend, "home.local.") === false) { $bloqueos[] = "js_frontend_sin_slots_locales_home"; }
if (strpos((string) $jsFrontend, "function estadoPaginaConstructor") === false) { $bloqueos[] = "js_frontend_sin_checklist_home"; }
if (strpos((string) $jsFrontend, "function validarSeccionParaEstado") === false) { $bloqueos[] = "js_frontend_sin_validacion_estado_seccion"; }
if (strpos((string) $jsFrontend, "Estado de Home") === false) { $bloqueos[] = "js_frontend_sin_titulo_estado_home"; }
if (strpos((string) $jsFrontend, "Lista para frontend") === false) { $bloqueos[] = "js_frontend_sin_senal_lista_frontend"; }
if (strpos((string) $jsFrontend, "function ejecutarAccionMapaSeccion") === false) { $bloqueos[] = "js_frontend_sin_acciones_mapa_secciones"; }
if (strpos((string) $jsFrontend, "function guardarLayoutConstructorLocal") === false) { $bloqueos[] = "js_frontend_sin_layout_local_constructor"; }
if (strpos((string) $jsFrontend, "function cargarLayoutConstructorLocal") === false) { $bloqueos[] = "js_frontend_sin_cargar_layout_local"; }
if (strpos((string) $jsFrontend, "data-section-action=\"subir\"") === false) { $bloqueos[] = "js_frontend_sin_subir_seccion"; }
if (strpos((string) $jsFrontend, "data-section-action=\"bajar\"") === false) { $bloqueos[] = "js_frontend_sin_bajar_seccion"; }
if (strpos((string) $jsFrontend, "data-section-action=\"toggle\"") === false) { $bloqueos[] = "js_frontend_sin_ocultar_mostrar_seccion"; }
if (strpos((string) $jsFrontend, "data-section-action=\"duplicar\"") === false) { $bloqueos[] = "js_frontend_sin_duplicar_seccion"; }
if (strpos((string) $jsFrontend, "maqueta_local_no_persistida") === false) { $bloqueos[] = "js_frontend_no_declara_layout_local_no_persistido"; }
if (strpos((string) $jsFrontend, "function renderEditorRapido") === false) { $bloqueos[] = "js_frontend_sin_editor_rapido"; }
if (strpos((string) $jsFrontend, "function aplicarEditorRapido") === false) { $bloqueos[] = "js_frontend_sin_aplicar_editor_rapido"; }
if (strpos((string) $jsFrontend, "function guardarBloqueRapido") === false) { $bloqueos[] = "js_frontend_sin_guardar_bloque_rapido"; }
if (strpos((string) $jsFrontend, "function guardarPublicacionRapida") === false) { $bloqueos[] = "js_frontend_sin_guardar_publicacion_rapida"; }
if (strpos((string) $jsFrontend, "function publicarSeccionRapida") === false) { $bloqueos[] = "js_frontend_sin_publicar_seccion_rapida"; }
if (strpos((string) $jsFrontend, "function pausarSeccionRapida") === false) { $bloqueos[] = "js_frontend_sin_pausar_seccion_rapida"; }
if (strpos((string) $jsFrontend, "function cambiarEstatusPublicacionRapida") === false) { $bloqueos[] = "js_frontend_sin_cambiar_estatus_publicacion_rapida"; }
if (strpos((string) $jsFrontend, "/cms/contenido_publicacion_estatus_erp") === false) { $bloqueos[] = "js_frontend_sin_endpoint_publicar_seccion"; }
if (strpos((string) $jsFrontend, "bloqueos_publicacion") === false) { $bloqueos[] = "js_frontend_sin_bloqueos_publicacion_legibles"; }
if (strpos((string) $jsFrontend, "Guardar borrador en CMS") === false) { $bloqueos[] = "js_frontend_sin_boton_guardar_borrador_cms"; }
if (strpos((string) $jsFrontend, "Publicar seccion") === false) { $bloqueos[] = "js_frontend_sin_boton_publicar_seccion"; }
if (strpos((string) $jsFrontend, "Pausar seccion") === false) { $bloqueos[] = "js_frontend_sin_boton_pausar_seccion"; }
if (strpos((string) $jsFrontend, "function nombreHumanoSeccion") === false) { $bloqueos[] = "js_frontend_sin_nombres_humanos_seccion"; }
if (strpos((string) $jsFrontend, "function textoHumanoSeccion") === false) { $bloqueos[] = "js_frontend_sin_texto_humano_seccion"; }
if (strpos((string) $jsFrontend, "function renderPaginaPendiente") === false) { $bloqueos[] = "js_frontend_sin_pagina_pendiente"; }
if (strpos((string) $jsFrontend, "function plantillaCodigoPorPagina") === false) { $bloqueos[] = "js_frontend_sin_plantilla_por_pagina"; }
if (strpos((string) $jsFrontend, "function renderBuilderCanvas") === false) { $bloqueos[] = "js_frontend_sin_canvas_builder"; }
if (strpos((string) $jsFrontend, "function imagenBloque") === false) { $bloqueos[] = "js_frontend_sin_imagen_bloque"; }
if (strpos((string) $jsFrontend, "cms-front-page-nav") === false) { $bloqueos[] = "js_frontend_sin_nav_tienda"; }
if (strpos((string) $jsFrontend, "cms-front-card-image") === false) { $bloqueos[] = "js_frontend_sin_card_imagen"; }
if (strpos((string) $jsFrontend, "/cms/contenido_admin_pagina_erp") === false) { $bloqueos[] = "js_frontend_no_conecta_contenido_pagina"; }
if (strpos((string) $jsFrontend, "function bloquesDeSeccion") === false) { $bloqueos[] = "js_frontend_sin_bloques_por_slot"; }
if (strpos((string) $jsFrontend, "Contenido conectado") === false) { $bloqueos[] = "js_frontend_sin_estado_contenido_conectado"; }
if (strpos((string) $jsFrontend, "erp_ecommerce_cms_preview_local_v1") === false) { $bloqueos[] = "js_frontend_no_lee_borrador_local"; }
if (strpos((string) $jsFrontend, "Borrador local conectado") === false) { $bloqueos[] = "js_frontend_sin_borrador_conectado"; }
if (strpos((string) $jsFrontend, "function aplicarBorradorLocal") === false) { $bloqueos[] = "js_frontend_sin_aplicar_borrador"; }
if (strpos((string) $jsFrontend, "function urlEditarContenido") === false) { $bloqueos[] = "js_frontend_sin_link_editar_contenido"; }
if (strpos((string) $jsFrontend, "Editar esta seccion") === false) { $bloqueos[] = "js_frontend_sin_boton_editar_seccion"; }
if (strpos((string) $jsFrontend, "function renderInspector") === false) { $bloqueos[] = "js_frontend_sin_inspector"; }
if (strpos((string) $jsFrontend, "function renderPaleta") === false) { $bloqueos[] = "js_frontend_sin_paleta"; }
if (strpos((string) $jsFrontend, "function renderComponentesSelector") === false) { $bloqueos[] = "js_frontend_sin_selector_componentes"; }
if (strpos((string) $jsFrontend, "function renderComponenteDetalle") === false) { $bloqueos[] = "js_frontend_sin_detalle_componente"; }
if (strpos((string) $jsFrontend, "function usosComponente") === false) { $bloqueos[] = "js_frontend_sin_uso_componentes"; }
if (strpos((string) $jsFrontend, "function renderActivaciones") === false) { $bloqueos[] = "js_frontend_sin_activaciones"; }
if (strpos((string) $jsFrontend, "function plantillaPorCodigo") === false) { $bloqueos[] = "js_frontend_sin_lookup_plantilla"; }
if (strpos((string) $vistaFrontendActual, "CMS / Frontend / Home") === false) { $bloqueos[] = "vista_frontend_actual_sin_titulo_home"; }
if (strpos((string) $vistaFrontendActual, "cms_actual_prioridad") === false) { $bloqueos[] = "vista_frontend_actual_sin_prioridad"; }
if (strpos((string) $vistaFrontendActual, "cms_actual_json") === false) { $bloqueos[] = "vista_frontend_actual_sin_json"; }
if (strpos((string) $vistaFrontendActual, "frontend_actual.js") === false) { $bloqueos[] = "vista_frontend_actual_sin_js"; }
if (strpos((string) $vistaFrontendHome, "CMS / Frontend / Home") === false) { $bloqueos[] = "vista_frontend_home_sin_titulo_operativo"; }
if (strpos((string) $vistaFrontendHome, "\$cmsFrontendGrupoInicial = \"home\"") === false) { $bloqueos[] = "vista_frontend_home_no_abre_home"; }
if (strpos((string) $vistaFrontendGlobal, "CMS / Frontend / Global") === false) { $bloqueos[] = "vista_frontend_global_sin_titulo_operativo"; }
if (strpos((string) $vistaFrontendGlobal, "\$cmsFrontendGrupoInicial = \"global\"") === false) { $bloqueos[] = "vista_frontend_global_no_abre_global"; }
if (strpos((string) $vistaFrontendNavegacion, "CMS / Frontend / Navegacion") === false) { $bloqueos[] = "vista_frontend_navegacion_sin_titulo_operativo"; }
if (strpos((string) $vistaFrontendNavegacion, "\$cmsFrontendGrupoInicial = \"navegacion\"") === false) { $bloqueos[] = "vista_frontend_navegacion_no_abre_navegacion"; }
if (strpos((string) $vistaFrontendCategorias, "CMS / Frontend / Categorias") === false) { $bloqueos[] = "vista_frontend_categorias_sin_titulo_operativo"; }
if (strpos((string) $vistaFrontendCategorias, "\$cmsFrontendGrupoInicial = \"categorias\"") === false) { $bloqueos[] = "vista_frontend_categorias_no_abre_categorias"; }
if (strpos((string) $vistaFrontendMarcas, "CMS / Frontend / Marcas") === false) { $bloqueos[] = "vista_frontend_marcas_sin_titulo_operativo"; }
if (strpos((string) $vistaFrontendMarcas, "\$cmsFrontendGrupoInicial = \"marcas\"") === false) { $bloqueos[] = "vista_frontend_marcas_no_abre_marcas"; }
if (strpos((string) $vistaFrontendPaginas, "CMS / Frontend / Paginas") === false) { $bloqueos[] = "vista_frontend_paginas_sin_titulo_operativo"; }
if (strpos((string) $vistaFrontendPaginas, "\$cmsFrontendGrupoInicial = \"paginas\"") === false) { $bloqueos[] = "vista_frontend_paginas_no_abre_paginas"; }
if (strpos((string) $vistaFrontendPoliticas, "CMS / Frontend / Politicas") === false) { $bloqueos[] = "vista_frontend_politicas_sin_titulo_operativo"; }
if (strpos((string) $vistaFrontendPoliticas, "\$cmsFrontendGrupoInicial = \"politicas\"") === false) { $bloqueos[] = "vista_frontend_politicas_no_abre_politicas"; }
if (strpos((string) $vistaFrontendPlaceholder, "Seccion reservada") === false) { $bloqueos[] = "vista_frontend_placeholder_sin_estado"; }
if (strpos((string) $jsFrontendActual, "data-cms-actual-grupo") === false) { $bloqueos[] = "js_frontend_actual_no_lee_grupo_inicial"; }
if (strpos((string) $jsFrontendActual, "erp_cms_media_biblioteca_local_v1") === false) { $bloqueos[] = "js_frontend_actual_no_lee_biblioteca_media"; }
if (strpos((string) $jsFrontendActual, "data-media-picker") === false) { $bloqueos[] = "js_frontend_actual_sin_boton_media"; }
if (strpos((string) $jsFrontendActual, "function abrirSelectorMedia") === false) { $bloqueos[] = "js_frontend_actual_sin_selector_media"; }
if (strpos((string) $jsFrontendActual, "function aplicarMediaSeleccionada") === false) { $bloqueos[] = "js_frontend_actual_no_aplica_media"; }
if (strpos((string) $jsFrontendActual, "function mediaLocalItems") === false) { $bloqueos[] = "js_frontend_actual_sin_media_local"; }
if (strpos((string) $jsFrontendActual, "cms_actual_media_archivo") === false) { $bloqueos[] = "js_frontend_actual_modal_sin_carga_media"; }
if (strpos((string) $jsFrontendActual, "function prepararMediaDesdeModal") === false) { $bloqueos[] = "js_frontend_actual_sin_preparar_media_modal"; }
if (strpos((string) $jsFrontendActual, "function agregarYUsarMediaDesdeModal") === false) { $bloqueos[] = "js_frontend_actual_sin_agregar_y_usar_media"; }
if (strpos((string) $jsFrontendActual, "Galeria disponible") === false) { $bloqueos[] = "js_frontend_actual_modal_sin_galeria"; }
if (strpos((string) $jsFrontendActual, "Filtro opcional") === false) { $bloqueos[] = "js_frontend_actual_modal_sin_filtro_opcional"; }
if (strpos((string) $jsFrontendActual, "cms_actual_media_preview_seleccion") === false) { $bloqueos[] = "js_frontend_actual_modal_sin_preview_seleccion"; }
if (strpos((string) $jsFrontendActual, "function seleccionarMediaPreview") === false) { $bloqueos[] = "js_frontend_actual_sin_seleccion_preview_media"; }
if (strpos((string) $jsFrontendActual, "function renderMediaPickerPreview") === false) { $bloqueos[] = "js_frontend_actual_sin_render_preview_media"; }
if (strpos((string) $jsFrontendActual, "Usar imagen seleccionada") === false) { $bloqueos[] = "js_frontend_actual_sin_boton_usar_media_preview"; }
if (strpos((string) $jsMedia, "function renderBibliotecaMedia") === false) { $bloqueos[] = "js_media_sin_render_biblioteca"; }
if (strpos((string) $jsMedia, "function agregarArchivoLocal") === false) { $bloqueos[] = "js_media_sin_agregar_local"; }
if (strpos((string) $jsMedia, "function limpiarArchivados") === false) { $bloqueos[] = "js_media_sin_limpiar_archivados"; }
if (strpos((string) $jsMedia, "no sube archivos") === false) { $bloqueos[] = "js_media_no_declara_sin_upload"; }
if (strpos((string) $jsMedia, "function cargarPreflightServidor") === false) { $bloqueos[] = "js_media_sin_preflight_servidor"; }
if (strpos((string) $jsMedia, "/cms/media_admin_preflight_erp") === false) { $bloqueos[] = "js_media_sin_endpoint_preflight"; }
if (strpos((string) $jsMedia, "function cargarListadoServidor") === false) { $bloqueos[] = "js_media_sin_listado_servidor"; }
if (strpos((string) $jsMedia, "/cms/media_admin_listar_erp") === false) { $bloqueos[] = "js_media_sin_endpoint_listar"; }
if (strpos((string) $jsFrontendActual, "hero_carrusel") === false) { $bloqueos[] = "js_frontend_actual_sin_hero_carrusel"; }
if (strpos((string) $jsFrontendActual, "function renderHeroCarrusel") === false) { $bloqueos[] = "js_frontend_actual_sin_editor_hero"; }
if (strpos((string) $jsFrontendActual, "cms_actual_hero_agregar") === false) { $bloqueos[] = "js_frontend_actual_sin_agregar_slide_hero"; }
if (strpos((string) $jsFrontendActual, "data-hero-slide-field") === false) { $bloqueos[] = "js_frontend_actual_sin_campos_slide_hero"; }
if (strpos((string) $jsFrontendActual, "imagen_desktop") === false) { $bloqueos[] = "js_frontend_actual_sin_imagen_desktop_hero"; }
if (strpos((string) $jsFrontendActual, "imagen_mobile") === false) { $bloqueos[] = "js_frontend_actual_sin_imagen_mobile_hero"; }
if (strpos((string) $jsFrontendActual, "alt") === false) { $bloqueos[] = "js_frontend_actual_sin_alt_hero"; }
if (strpos((string) $jsFrontendActual, "categorias_destacadas") === false) { $bloqueos[] = "js_frontend_actual_sin_categorias_destacadas"; }
if (strpos((string) $jsFrontendActual, "function renderCategoriasDestacadas") === false) { $bloqueos[] = "js_frontend_actual_sin_editor_categorias"; }
if (strpos((string) $jsFrontendActual, "cms_actual_categoria_agregar") === false) { $bloqueos[] = "js_frontend_actual_sin_agregar_categoria"; }
if (strpos((string) $jsFrontendActual, "data-categoria-field") === false) { $bloqueos[] = "js_frontend_actual_sin_campos_categoria"; }
if (strpos((string) $jsFrontendActual, "categoria_id") === false) { $bloqueos[] = "js_frontend_actual_sin_categoria_id"; }
if (strpos((string) $jsFrontendActual, "imagen_card") === false) { $bloqueos[] = "js_frontend_actual_sin_imagen_card_categoria"; }
if (strpos((string) $jsFrontendActual, "imagen_banner") === false) { $bloqueos[] = "js_frontend_actual_sin_imagen_banner_categoria"; }
if (strpos((string) $jsFrontendActual, "productos_destacados") === false) { $bloqueos[] = "js_frontend_actual_sin_productos_destacados"; }
if (strpos((string) $jsFrontendActual, "function renderProductosDestacados") === false) { $bloqueos[] = "js_frontend_actual_sin_editor_productos"; }
if (strpos((string) $jsFrontendActual, "data-productos-config") === false) { $bloqueos[] = "js_frontend_actual_sin_config_productos"; }
if (strpos((string) $jsFrontendActual, "data-producto-field") === false) { $bloqueos[] = "js_frontend_actual_sin_campos_producto_manual"; }
if (strpos((string) $jsFrontendActual, "fuente.modo") === false) { $bloqueos[] = "js_frontend_actual_sin_modo_fuente_productos"; }
if (strpos((string) $jsFrontendActual, "cms_actual_producto_agregar") === false) { $bloqueos[] = "js_frontend_actual_sin_agregar_producto_manual"; }
if (strpos((string) $jsFrontendActual, "No mostrar disponibilidad") === false) { $bloqueos[] = "js_frontend_actual_no_refuerza_sin_stock"; }
if (strpos((string) $jsFrontendActual, "coleccion_productos") === false) { $bloqueos[] = "js_frontend_actual_sin_coleccion_productos"; }
if (strpos((string) $jsFrontendActual, "function renderColeccionesProductos") === false) { $bloqueos[] = "js_frontend_actual_sin_editor_colecciones"; }
if (strpos((string) $jsFrontendActual, "cms_actual_coleccion_agregar") === false) { $bloqueos[] = "js_frontend_actual_sin_agregar_coleccion"; }
if (strpos((string) $jsFrontendActual, "data-coleccion-field") === false) { $bloqueos[] = "js_frontend_actual_sin_campos_coleccion"; }
if (strpos((string) $jsFrontendActual, "fuente.productos_csv") === false) { $bloqueos[] = "js_frontend_actual_sin_productos_csv_coleccion"; }
if (strpos((string) $jsFrontendActual, "home_banner") === false) { $bloqueos[] = "js_frontend_actual_sin_home_banner"; }
if (strpos((string) $jsFrontendActual, "function renderBannerHome") === false) { $bloqueos[] = "js_frontend_actual_sin_editor_banner_home"; }
if (strpos((string) $jsFrontendActual, "data-banner-field") === false) { $bloqueos[] = "js_frontend_actual_sin_campos_banner_home"; }
if (strpos((string) $jsFrontendActual, "cms_actual_banner_agregar") === false) { $bloqueos[] = "js_frontend_actual_sin_agregar_banner_home"; }
if (strpos((string) $jsFrontendActual, "home_banner_temporada") !== false) { $bloqueos[] = "js_frontend_actual_usa_banner_temporada"; }
if (strpos((string) $jsFrontendActual, "GET /ecommercePublico/cms_frontend?pagina=home") === false) { $bloqueos[] = "js_frontend_actual_sin_endpoint_home"; }
if (strpos((string) $jsFrontendActual, "No mostrar disponibilidad") === false) { $bloqueos[] = "js_frontend_actual_sin_reglas_publicas"; }
if (strpos((string) $jsFrontendActual, "global_negocio") === false) { $bloqueos[] = "js_frontend_actual_sin_global_negocio"; }
if (strpos((string) $jsFrontendActual, "global_ubicacion") === false) { $bloqueos[] = "js_frontend_actual_sin_global_ubicacion"; }
if (strpos((string) $jsFrontendActual, "global_horarios") === false) { $bloqueos[] = "js_frontend_actual_sin_global_horarios"; }
if (strpos((string) $jsFrontendActual, "global_redes") === false) { $bloqueos[] = "js_frontend_actual_sin_global_redes"; }
if (strpos((string) $jsFrontendActual, "global_navegacion") === false) { $bloqueos[] = "js_frontend_actual_sin_global_navegacion"; }
if (strpos((string) $jsFrontendActual, "function renderGlobalSeccion") === false) { $bloqueos[] = "js_frontend_actual_sin_editor_global"; }
if (strpos((string) $jsFrontendActual, "function previewGlobalJson") === false) { $bloqueos[] = "js_frontend_actual_sin_preview_global"; }
if (strpos((string) $jsFrontendActual, "nav_menu_principal") === false) { $bloqueos[] = "js_frontend_actual_sin_nav_menu_principal"; }
if (strpos((string) $jsFrontendActual, "nav_footer_columnas") === false) { $bloqueos[] = "js_frontend_actual_sin_nav_footer_columnas"; }
if (strpos((string) $jsFrontendActual, "function renderNavegacionSeccion") === false) { $bloqueos[] = "js_frontend_actual_sin_editor_navegacion"; }
if (strpos((string) $jsFrontendActual, "function previewNavegacionJson") === false) { $bloqueos[] = "js_frontend_actual_sin_preview_navegacion"; }
if (strpos((string) $jsFrontendActual, "categorias_items") === false) { $bloqueos[] = "js_frontend_actual_sin_categorias_items"; }
if (strpos((string) $jsFrontendActual, "function renderCategoriasCmsSeccion") === false) { $bloqueos[] = "js_frontend_actual_sin_editor_categorias"; }
if (strpos((string) $jsFrontendActual, "function previewCategoriasCmsJson") === false) { $bloqueos[] = "js_frontend_actual_sin_preview_categorias"; }
if (strpos((string) $jsFrontendActual, "cms_categoria") === false) { $bloqueos[] = "js_frontend_actual_sin_media_categorias"; }
if (strpos((string) $jsFrontendActual, "marcas_items") === false) { $bloqueos[] = "js_frontend_actual_sin_marcas_items"; }
if (strpos((string) $jsFrontendActual, "function renderMarcasCmsSeccion") === false) { $bloqueos[] = "js_frontend_actual_sin_editor_marcas"; }
if (strpos((string) $jsFrontendActual, "function previewMarcasCmsJson") === false) { $bloqueos[] = "js_frontend_actual_sin_preview_marcas"; }
if (strpos((string) $jsFrontendActual, "cms_marca") === false) { $bloqueos[] = "js_frontend_actual_sin_media_marcas"; }
if (strpos((string) $jsFrontendActual, "paginas_items") === false) { $bloqueos[] = "js_frontend_actual_sin_paginas_items"; }
if (strpos((string) $jsFrontendActual, "function renderPaginasCmsSeccion") === false) { $bloqueos[] = "js_frontend_actual_sin_editor_paginas"; }
if (strpos((string) $jsFrontendActual, "function previewPaginasCmsJson") === false) { $bloqueos[] = "js_frontend_actual_sin_preview_paginas"; }
if (strpos((string) $jsFrontendActual, "cms_pagina") === false) { $bloqueos[] = "js_frontend_actual_sin_media_paginas"; }
if (strpos((string) $jsFrontendActual, "politicas_items") === false) { $bloqueos[] = "js_frontend_actual_sin_politicas_items"; }
if (strpos((string) $jsFrontendActual, "function renderPoliticasCmsSeccion") === false) { $bloqueos[] = "js_frontend_actual_sin_editor_politicas"; }
if (strpos((string) $jsFrontendActual, "function previewPoliticasCmsJson") === false) { $bloqueos[] = "js_frontend_actual_sin_preview_politicas"; }
if (strpos((string) $jsFrontendActual, "requiere_revision_legal") === false) { $bloqueos[] = "js_frontend_actual_sin_guardrail_legal"; }
if (strpos((string) $vistaContenido, "ecom_cms_validacion") === false) { $bloqueos[] = "vista_contenido_sin_panel_validacion"; }
if (strpos((string) $vistaContenido, "ecom_cms_guardar_local") === false) { $bloqueos[] = "vista_contenido_sin_borrador_local"; }
if (strpos((string) $vistaContenido, "ecom_cms_guardar_bd") === false) { $bloqueos[] = "vista_contenido_sin_guardar_bd"; }
if (strpos((string) $vistaContenido, "Guardar borrador en BD") === false) { $bloqueos[] = "vista_contenido_sin_boton_borrador_bd"; }
if (strpos((string) $vistaContenido, "ecom_cms_publicar_slot_bd") === false) { $bloqueos[] = "vista_contenido_sin_colocar_slot_bd"; }
if (strpos((string) $vistaContenido, "Colocar en slot BD") === false) { $bloqueos[] = "vista_contenido_sin_boton_slot_bd"; }
if (strpos((string) $vistaContenido, "ecom_cms_publicar_publicacion_bd") === false) { $bloqueos[] = "vista_contenido_sin_publicar_slot"; }
if (strpos((string) $vistaContenido, "Pausar slot") === false) { $bloqueos[] = "vista_contenido_sin_pausar_slot"; }
if (strpos((string) $vistaContenido, "ecom_cms_bloques_bd") === false) { $bloqueos[] = "vista_contenido_sin_biblioteca_bd"; }
if (strpos((string) $vistaContenido, "Biblioteca BD") === false) { $bloqueos[] = "vista_contenido_sin_titulo_biblioteca_bd"; }
if (strpos((string) $vistaContenido, "ecom_cms_filtro_estatus") === false) { $bloqueos[] = "vista_contenido_sin_filtro_estatus"; }
if (strpos((string) $vistaJson, "ecom_cms_import_json") === false) { $bloqueos[] = "vista_json_sin_importar_json"; }
if (strpos((string) $sidebar, "/cms/contenido") === false) { $bloqueos[] = "sidebar_sin_cms_contenido"; }
if (strpos((string) $sidebar, "/cms/plantillas") === false) { $bloqueos[] = "sidebar_sin_cms_plantillas"; }
if (strpos((string) $sidebar, "/cms/persistencia") === false) { $bloqueos[] = "sidebar_sin_cms_persistencia"; }
if (strpos((string) $sidebar, "/cms/slots") === false) { $bloqueos[] = "sidebar_sin_cms_slots"; }
if (strpos((string) $sidebar, "/cms/media") === false) { $bloqueos[] = "sidebar_sin_cms_media"; }
if (strpos((string) $sidebar, "/cms/json") === false) { $bloqueos[] = "sidebar_sin_cms_json"; }
if (strpos((string) $sidebar, "/cms/frontend_plantillas") === false) { $bloqueos[] = "sidebar_sin_cms_frontend_plantillas"; }
if (strpos((string) $sidebar, "/cms/frontend/home") === false) { $bloqueos[] = "sidebar_sin_cms_frontend_home"; }
if (strpos((string) $sidebar, "/cms/frontend/categorias") === false) { $bloqueos[] = "sidebar_sin_cms_frontend_categorias"; }
if (strpos((string) $sidebar, "/cms/frontend/producto") === false) { $bloqueos[] = "sidebar_sin_cms_frontend_producto"; }
if (strpos((string) $sidebar, "/cms/frontend/carrito") === false) { $bloqueos[] = "sidebar_sin_cms_frontend_carrito"; }
if (strpos((string) $sidebar, "/cms/frontend/global") === false) { $bloqueos[] = "sidebar_sin_cms_frontend_global"; }
if (strpos((string) $sidebar, "/cms/frontend/navegacion") === false) { $bloqueos[] = "sidebar_sin_cms_frontend_navegacion"; }
if (strpos((string) $sidebar, "/cms/frontend/marcas") === false) { $bloqueos[] = "sidebar_sin_cms_frontend_marcas"; }
if (strpos((string) $sidebar, "/cms/frontend/paginas") === false) { $bloqueos[] = "sidebar_sin_cms_frontend_paginas"; }
if (strpos((string) $sidebar, "/cms/frontend/politicas") === false) { $bloqueos[] = "sidebar_sin_cms_frontend_politicas"; }
if (strpos((string) $sidebar, "/cms/frontend_componentes") === false) { $bloqueos[] = "sidebar_sin_cms_frontend_componentes"; }
if (strpos((string) $sidebar, "/cms/frontend_activaciones") === false) { $bloqueos[] = "sidebar_sin_cms_frontend_activaciones"; }
if (strpos((string) $sidebar, "'titulo' => 'Frontend'") === false) { $bloqueos[] = "sidebar_sin_grupo_cms_frontend"; }
if (strpos((string) $sidebar, "'titulo' => 'Avanzado contenido'") === false) { $bloqueos[] = "sidebar_sin_grupo_cms_avanzado_contenido"; }
if (strpos((string) $sidebar, "'titulo' => 'Home'") === false) { $bloqueos[] = "sidebar_sin_home_frontend"; }
if (strpos((string) $sidebar, "'seccion' => 'CMS'") === false) { $bloqueos[] = "sidebar_sin_seccion_cms"; }
if (strpos((string) $sidebar, "/ecommercePublico/contenido") !== false) { $bloqueos[] = "sidebar_cms_colgado_de_ecommerce"; }
if (strpos((string) $sidebar, "#cms_tab_") !== false) { $bloqueos[] = "sidebar_usa_hashes_de_tabs"; }
if (strpos((string) $vistaContenido . $vistaPlantillas . $vistaPersistencia . $vistaSlots . $vistaMedia . $vistaJson . $vistaFrontendPlantillas . $vistaFrontendComponentes, "data-cms-tab") !== false) { $bloqueos[] = "vistas_aun_usan_tabs"; }
if (strpos((string) $controladorCms, "public function plantillas") === false) { $bloqueos[] = "controlador_sin_ruta_plantillas"; }
if (strpos((string) $controladorCms, "public function persistencia") === false) { $bloqueos[] = "controlador_sin_ruta_persistencia"; }
if (strpos((string) $controladorCms, "public function slots") === false) { $bloqueos[] = "controlador_sin_ruta_slots"; }
if (strpos((string) $controladorCms, "public function media") === false) { $bloqueos[] = "controlador_sin_ruta_media"; }
if (strpos((string) $controladorCms, "public function media_admin_preflight_erp") === false) { $bloqueos[] = "controlador_sin_media_preflight"; }
if (strpos((string) $controladorCms, "public function media_admin_listar_erp") === false) { $bloqueos[] = "controlador_sin_media_listar"; }
if (strpos((string) $controladorCms, "public function media_admin_subir_erp") === false) { $bloqueos[] = "controlador_sin_media_subir_bloqueado"; }
if (strpos((string) $controladorCms, "public function media_admin_actualizar_erp") === false) { $bloqueos[] = "controlador_sin_media_actualizar_bloqueado"; }
if (strpos((string) $controladorCms, "public function media_admin_archivar_erp") === false) { $bloqueos[] = "controlador_sin_media_archivar_bloqueado"; }
if (strpos((string) $controladorCms, "public function media_admin_usos_erp") === false) { $bloqueos[] = "controlador_sin_media_usos_bloqueado"; }
if (strpos((string) $controladorCms, "respuestaEscrituraCmsMediaBloqueada") === false) { $bloqueos[] = "controlador_media_post_no_bloquea"; }
if (strpos((string) $controladorCms, "public function json") === false) { $bloqueos[] = "controlador_sin_ruta_json"; }
if (strpos((string) $controladorCms, "public function frontend_plantillas") === false) { $bloqueos[] = "controlador_sin_ruta_frontend_plantillas"; }
if (strpos((string) $controladorCms, "public function frontend_constructor") === false) { $bloqueos[] = "controlador_sin_ruta_frontend_constructor"; }
if (strpos((string) $controladorCms, "public function frontend(") === false) { $bloqueos[] = "controlador_sin_ruta_frontend_pagina"; }
if (strpos((string) $controladorCms, "public function frontend_home") === false) { $bloqueos[] = "controlador_sin_ruta_frontend_home"; }
if (strpos((string) $controladorCms, "public function frontend_global") === false) { $bloqueos[] = "controlador_sin_ruta_frontend_global"; }
if (strpos((string) $controladorCms, "public function frontend_navegacion") === false) { $bloqueos[] = "controlador_sin_ruta_frontend_navegacion"; }
if (strpos((string) $controladorCms, "public function frontend_categorias") === false) { $bloqueos[] = "controlador_sin_ruta_frontend_categorias"; }
if (strpos((string) $controladorCms, "public function frontend_marcas") === false) { $bloqueos[] = "controlador_sin_ruta_frontend_marcas"; }
if (strpos((string) $controladorCms, "public function frontend_paginas") === false) { $bloqueos[] = "controlador_sin_ruta_frontend_paginas"; }
if (strpos((string) $controladorCms, "public function frontend_politicas") === false) { $bloqueos[] = "controlador_sin_ruta_frontend_politicas"; }
if (strpos((string) $controladorCms, "public function frontend_actual") === false) { $bloqueos[] = "controlador_sin_ruta_frontend_actual"; }
if (strpos((string) $controladorCms, "public function frontend_componentes") === false) { $bloqueos[] = "controlador_sin_ruta_frontend_componentes"; }
if (strpos((string) $controladorCms, "public function frontend_activaciones") === false) { $bloqueos[] = "controlador_sin_ruta_frontend_activaciones"; }
if (strpos((string) $controladorCms, "public function frontend_admin_manifest_erp") === false) { $bloqueos[] = "controlador_sin_manifest_frontend"; }
if (strpos((string) $controladorCms, "public function frontend_admin_estado_erp") === false) { $bloqueos[] = "controlador_sin_estado_frontend"; }
if (strpos((string) $controladorCms, "public function index") === false) { $bloqueos[] = "controlador_sin_ruta_index_cms"; }
if (strpos((string) $controladorCms, "\$this->frontend_home();") === false) { $bloqueos[] = "controlador_index_no_abre_frontend_home"; }
if (strpos((string) $controladorCms, "public function contenido_admin_bloques_erp") === false) { $bloqueos[] = "controlador_sin_listado_bloques_bd"; }
if (strpos((string) $controladorCms, "public function contenido_bloque_guardar_erp") === false) { $bloqueos[] = "controlador_sin_post_bloque_guardar"; }
if (strpos((string) $controladorCms, "contenidoBloqueGuardarInterno") === false) { $bloqueos[] = "controlador_bloque_guardar_no_llama_modelo"; }
if (strpos((string) $controladorCms, "registrarAuditoria(\"cms\", \"contenido_bloque_guardar_erp\"") === false) { $bloqueos[] = "controlador_bloque_guardar_sin_auditoria"; }
if (strpos((string) $controladorCms, "public function contenido_bloque_estatus_erp") === false) { $bloqueos[] = "controlador_sin_post_bloque_estatus"; }
if (strpos((string) $controladorCms, "contenidoBloqueEstatusInterno") === false) { $bloqueos[] = "controlador_bloque_estatus_no_llama_modelo"; }
if (strpos((string) $controladorCms, "registrarAuditoria(\"cms\", \"contenido_bloque_estatus_erp\"") === false) { $bloqueos[] = "controlador_bloque_estatus_sin_auditoria"; }
if (strpos((string) $controladorCms, "public function contenido_publicacion_guardar_erp") === false) { $bloqueos[] = "controlador_sin_post_publicacion_guardar"; }
if (strpos((string) $controladorCms, "contenidoPublicacionGuardarInterna") === false) { $bloqueos[] = "controlador_publicacion_guardar_no_llama_modelo"; }
if (strpos((string) $controladorCms, "registrarAuditoria(\"cms\", \"contenido_publicacion_guardar_erp\"") === false) { $bloqueos[] = "controlador_publicacion_guardar_sin_auditoria"; }
if (strpos((string) $controladorCms, "public function contenido_publicacion_estatus_erp") === false) { $bloqueos[] = "controlador_sin_post_publicacion_estatus"; }
if (strpos((string) $controladorCms, "contenidoPublicacionEstatusInterna") === false) { $bloqueos[] = "controlador_publicacion_estatus_no_llama_modelo"; }
if (strpos((string) $controladorCms, "registrarAuditoria(\"cms\", \"contenido_publicacion_estatus_erp\"") === false) { $bloqueos[] = "controlador_publicacion_estatus_sin_auditoria"; }
if (strpos((string) $controladorCms, "\"cms.publicar\"") === false) { $bloqueos[] = "controlador_publicacion_estatus_sin_permiso_publicar"; }
if (strpos((string) $modeloPublico, "contenidoPublicacionEstatusInterna") === false) { $bloqueos[] = "modelo_sin_publicacion_estatus_interna"; }
if (strpos((string) $modeloPublico, "cmsValidarPublicacionAntesDePublicar") === false) { $bloqueos[] = "modelo_sin_validacion_server_publicacion"; }
if (strpos((string) $modeloPublico, "bloqueos_publicacion") === false) { $bloqueos[] = "modelo_publicacion_sin_bloqueos_legibles"; }
if (strpos((string) $modeloPublico, "falta alt text de imagen") === false) { $bloqueos[] = "modelo_publicacion_no_valida_alt"; }
if (strpos((string) $modeloPublico, "falta endpoint source") === false) { $bloqueos[] = "modelo_publicacion_no_valida_source"; }
if (strpos((string) $modeloPublico, "function mediaAdminPreflightInterno") === false) { $bloqueos[] = "modelo_sin_media_preflight"; }
if (strpos((string) $modeloPublico, "function mediaAdminListarInterno") === false) { $bloqueos[] = "modelo_sin_media_listar"; }
if (strpos((string) $modeloEsquema, "function planActualizarCmsMediaBiblioteca") === false) { $bloqueos[] = "esquema_sin_plan_media_biblioteca"; }
if (strpos((string) $modeloEsquema, "erp_ecommerce_media_archivos") === false) { $bloqueos[] = "esquema_media_sin_tabla_archivos"; }
if (strpos((string) $modeloEsquema, "erp_ecommerce_media_usos") === false) { $bloqueos[] = "esquema_media_sin_tabla_usos"; }
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
if (strpos((string) $manualCms, "Cierre de persistencia parcial de contenido") === false) { $bloqueos[] = "manual_sin_cierre_contenido"; }
if (strpos((string) $manualCms, "validar en `/ecommercePublico/contenido_pagina` si la fuente es `bd_publicada`") === false) { $bloqueos[] = "manual_cierre_sin_bd_publicada"; }
if (strpos((string) $manualCms, "CMS > Plantillas contenido") === false) { $bloqueos[] = "manual_sin_plantillas_contenido"; }
if (strpos((string) $manualCms, "Diferencia contra plantillas frontend") === false) { $bloqueos[] = "manual_plantillas_sin_diferencia_frontend"; }
if (strpos((string) $manualCms, "artiani_default") === false) { $bloqueos[] = "manual_plantillas_sin_artiani_default"; }
if (strpos((string) $manualCms, "CMS > Slots") === false) { $bloqueos[] = "manual_sin_slots"; }
if (strpos((string) $manualCms, "Detalle del slot") === false) { $bloqueos[] = "manual_slots_sin_detalle"; }
if (strpos((string) $manualCms, "Vista estructural read-only") === false) { $bloqueos[] = "manual_slots_sin_readonly"; }
if (strpos((string) $manualCms, "CMS > Media") === false) { $bloqueos[] = "manual_sin_media"; }
if (strpos((string) $manualCms, "biblioteca local") === false) { $bloqueos[] = "manual_media_sin_biblioteca_local"; }
if (strpos((string) $manualCms, "alt text") === false) { $bloqueos[] = "manual_media_sin_alt_text"; }
if (strpos((string) $manualCms, "/cms/media_admin_preflight_erp") === false) { $bloqueos[] = "manual_media_sin_preflight"; }
if (strpos((string) $manualCms, "/cms/media_admin_listar_erp") === false) { $bloqueos[] = "manual_media_sin_listar"; }
if (strpos((string) $manualCms, "erp_ecommerce_media_archivos") === false) { $bloqueos[] = "manual_media_sin_tabla_archivos"; }
if (strpos((string) $manualCms, "CMS > Preview JSON") === false) { $bloqueos[] = "manual_sin_preview_json"; }
if (strpos((string) $manualCms, "Contrato API") === false) { $bloqueos[] = "manual_json_sin_contrato_api"; }
if (strpos((string) $manualCms, "/ecommercePublico/configuracion_inicial") === false) { $bloqueos[] = "manual_json_sin_configuracion_inicial"; }
if (strpos((string) $manualCms, "Importar preview") === false) { $bloqueos[] = "manual_json_sin_importar_preview"; }
if (strpos((string) $manualCms, "CMS > Persistencia") === false) { $bloqueos[] = "manual_sin_persistencia"; }
if (strpos((string) $manualCms, "C:\\xampp\\panel_db_backups") === false) { $bloqueos[] = "manual_persistencia_sin_respaldo"; }
if (strpos((string) $manualCms, "erp_ecommerce_contenido_bloques") === false) { $bloqueos[] = "manual_persistencia_sin_tablas"; }
if (strpos((string) $manualCms, "erp_ecommerce_frontend_temas") === false) { $bloqueos[] = "manual_persistencia_sin_tablas_frontend"; }
if (strpos((string) $manualCms, "Planes DDL read-only") === false) { $bloqueos[] = "manual_persistencia_sin_planes_ddl"; }
if (strpos((string) $manualCms, "Completado para primera escritura controlada") === false) { $bloqueos[] = "manual_persistencia_sin_primera_escritura"; }
if (strpos((string) $manualCms, "uat_cms_seed_readonly.php") === false) { $bloqueos[] = "manual_persistencia_sin_uat_seed"; }
if (strpos((string) $manualCms, "Activar endpoints POST con CSRF") === false) { $bloqueos[] = "manual_persistencia_sin_post_csrf"; }
if (strpos((string) $manualCms, "CMS > Frontend > Plantillas de vista") === false) { $bloqueos[] = "manual_sin_frontend_plantillas"; }
if (strpos((string) $manualCms, "CMS > Frontend > Navegacion") === false) { $bloqueos[] = "manual_sin_frontend_navegacion"; }
if (strpos((string) $manualCms, "CMS > Frontend > Categorias") === false) { $bloqueos[] = "manual_sin_frontend_categorias"; }
if (strpos((string) $manualCms, "CMS > Frontend > Marcas") === false) { $bloqueos[] = "manual_sin_frontend_marcas"; }
if (strpos((string) $manualCms, "CMS > Frontend > Paginas") === false) { $bloqueos[] = "manual_sin_frontend_paginas"; }
if (strpos((string) $manualCms, "CMS > Frontend > Politicas") === false) { $bloqueos[] = "manual_sin_frontend_politicas"; }
if (strpos((string) $manualCms, "CMS > Frontend > Constructor visual") === false) { $bloqueos[] = "manual_sin_frontend_constructor"; }
if (strpos((string) $manualCms, "Navegacion del submodulo") === false) { $bloqueos[] = "manual_frontend_sin_subnav"; }
if (strpos((string) $manualCms, "Builder visual read-only") === false) { $bloqueos[] = "manual_frontend_sin_builder_visual"; }
if (strpos((string) $manualCms, "Paleta de componentes") === false) { $bloqueos[] = "manual_frontend_sin_paleta_componentes"; }
if (strpos((string) $manualCms, "El builder visual es una previsualizacion administrativa") === false) { $bloqueos[] = "manual_frontend_no_aclara_preview_admin"; }
if (strpos((string) $manualCms, "maqueta tipo tienda") === false) { $bloqueos[] = "manual_frontend_sin_maqueta_tienda"; }
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
if (strpos((string) $manualCms, "Borrador local conectado") === false) { $bloqueos[] = "manual_sin_borrador_local_constructor"; }
if (strpos((string) $manualCms, "Editar contenido de este slot") === false) { $bloqueos[] = "manual_sin_editar_slot_desde_constructor"; }
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
if (strpos((string) $planCmsApi, "CMS / API ecommerce publico Artiani") === false) { $bloqueos[] = "plan_cms_api_sin_titulo"; }
if (strpos((string) $planCmsApi, "/ecommercePublico/configuracion_inicial") === false) { $bloqueos[] = "plan_cms_api_sin_configuracion_inicial"; }
if (strpos((string) $planCmsApi, "/cms/frontend/global") === false) { $bloqueos[] = "plan_cms_api_sin_frontend_global"; }
if (strpos((string) $planCmsApi, "/cms/frontend/navegacion") === false) { $bloqueos[] = "plan_cms_api_sin_frontend_navegacion"; }
if (strpos((string) $planCmsApi, "/cms/frontend/marcas") === false) { $bloqueos[] = "plan_cms_api_sin_frontend_marcas"; }
if (strpos((string) $planCmsApi, "/cms/frontend/paginas") === false) { $bloqueos[] = "plan_cms_api_sin_frontend_paginas"; }
if (strpos((string) $planCmsApi, "/cms/frontend/politicas") === false) { $bloqueos[] = "plan_cms_api_sin_frontend_politicas"; }
if (strpos((string) $planCmsApi, "/cms/media_admin_preflight_erp") === false) { $bloqueos[] = "plan_cms_api_sin_media_preflight"; }
if (strpos((string) $planCmsApi, "/cms/media_admin_listar_erp") === false) { $bloqueos[] = "plan_cms_api_sin_media_listar"; }
if (strpos((string) $planCmsApi, "/cms/media_admin_subir_erp") === false) { $bloqueos[] = "plan_cms_api_sin_media_subir"; }
if (strpos((string) $planCmsApi, "filtros invalidos devuelven vacio") === false) { $bloqueos[] = "plan_cms_api_sin_guardrail_filtros"; }
if (strpos((string) $planBuilderWokiee, "app/Pages/catalog.php") === false) { $bloqueos[] = "plan_builder_wokiee_sin_catalog_php"; }
if (strpos((string) $planBuilderWokiee, "erp_ecommerce_frontend_temas") === false) { $bloqueos[] = "plan_builder_wokiee_sin_tabla_temas"; }
if (strpos((string) $uatPersistenciaPreflight, "cms_persistencia_lista_para_respaldo_y_autorizacion") === false) { $bloqueos[] = "uat_preflight_persistencia_sin_senal"; }
if (strpos((string) $uatPersistenciaPreflight, '"total" => $ddlContenido + $ddlFrontend') === false) { $bloqueos[] = "uat_preflight_persistencia_sin_total_ddl"; }
if (strpos((string) $uatMediaPreflight, "cms_media_preflight_readonly") === false) { $bloqueos[] = "uat_media_preflight_sin_senal"; }
if (strpos((string) $uatMediaPreflight, "no ejecuta DDL") === false) { $bloqueos[] = "uat_media_preflight_no_declara_readonly"; }
if (strpos((string) $uatSeedReadonly, "cms_seed_base_verificado") === false) { $bloqueos[] = "uat_seed_readonly_sin_senal"; }
if (strpos((string) $uatSeedReadonly, "bloques_publicados_sin_flujo_publicacion") === false) { $bloqueos[] = "uat_seed_readonly_no_protege_publicados"; }

$ok = empty($bloqueos);
echo json_encode(array(
  "ok" => $ok,
  "modo" => "admin_contenido_interno",
  "senal_panel" => $ok ? "cms_contenido_interno_listo" : "cms_contenido_interno_incompleto",
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
  "media_preflight" => array(
    "modo" => valorCmsAdmin($mediaPreflight, array("depurar", "modo"), ""),
    "listado_modo" => valorCmsAdmin($mediaListado, array("depurar", "modo"), ""),
    "carpeta_publica" => valorCmsAdmin($mediaPreflight, array("depurar", "carpeta_publica_propuesta"), ""),
    "tabla_archivos" => valorCmsAdmin($mediaPreflight, array("depurar", "tabla_archivos"), ""),
    "tabla_usos" => valorCmsAdmin($mediaPreflight, array("depurar", "tabla_usos"), ""),
    "no_mueve_archivos" => valorCmsAdmin($mediaPreflight, array("depurar", "guardrails", "no_mueve_archivos"), false)
  ),
  "ui" => array(
    "editor_local" => true,
    "preview_json" => "/cms/json",
    "validacion_local" => true,
    "borrador_local" => true,
    "intercambio_json" => true,
    "sidebar" => array("/cms/contenido", "/cms/plantillas", "/cms/persistencia", "/cms/slots", "/cms/media", "/cms/json", "/cms/frontend/home", "/cms/frontend/global", "/cms/frontend/navegacion", "/cms/frontend/categorias", "/cms/frontend/marcas", "/cms/frontend/paginas", "/cms/frontend/politicas", "/cms/frontend_constructor", "/cms/frontend_plantillas", "/cms/frontend_componentes", "/cms/frontend_activaciones"),
    "frontend_operativo" => array("/cms/frontend/home", "/cms/frontend/global", "/cms/frontend/navegacion", "/cms/frontend/categorias", "/cms/frontend/marcas", "/cms/frontend/paginas", "/cms/frontend/politicas"),
    "navegacion" => "vistas_separadas_sin_tabs",
    "entrada_directa" => "/cms",
    "modulo_separado" => "CMS",
    "permiso_dueno" => "cms.ver",
    "permiso_puente" => "catalogo.ver",
    "acciones_locales" => array("nuevo", "editar", "duplicar", "ordenar", "pausar", "quitar")
  ),
  "post_futuros" => array(
    "estado" => "contenido_interno_activo_api_publica_pendiente",
    "activo" => array(
      "/cms/contenido_bloque_guardar_erp",
      "/cms/contenido_bloque_estatus_erp",
      "/cms/contenido_publicacion_guardar_erp",
      "/cms/contenido_publicacion_estatus_erp"
    ),
    "endpoints" => array(
      "/cms/frontend_plantilla_guardar_erp",
      "/cms/frontend_plantilla_estatus_erp",
      "/cms/frontend_seccion_guardar_erp",
      "/cms/frontend_seccion_estatus_erp"
    )
  ),
  "guardrails" => array(
    "no_escribe_bd" => false,
    "solo_bloques_borrador" => true,
    "publicaciones_slot_publicables" => true,
    "no_ejecuta_ddl" => true,
    "no_modifica_catalogo" => true,
    "no_modifica_inventario" => true
  ),
  "manual" => "docs/erp_cms_manual_uso.md",
  "plan_cms_api" => "docs/erp_cms_api_ecommerce_publico_artiani_plan.md",
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
