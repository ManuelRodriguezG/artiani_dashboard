<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../../">
    <title>CMS - Contenido ecommerce</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css">
    <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css">
    <!--
      Documentacion IA: Codex GPT-5, 2026-08-10.
      Proposito: vista editorial principal del modulo CMS ecommerce.
      Impacto: CMS; permite armar contenido local por slot sin mezclar plantillas, media ni JSON/API como tabs.
      Contrato: vista protegida; consume endpoints GET internos read-only y no escribe BD.
    -->
    <style>
        .ecom-cms-kpi { border: 1px solid #e7e9ef; border-radius: 8px; background: #fff; padding: 16px; min-height: 104px; }
        .ecom-cms-kpi__value { font-size: 1.8rem; line-height: 1; font-weight: 800; color: #181c32; letter-spacing: 0; }
        .ecom-cms-kpi__label { color: #7e8299; font-size: .78rem; text-transform: uppercase; font-weight: 700; }
        .ecom-cms-panel { border: 1px solid #e7e9ef; border-radius: 8px; background: #fff; }
        .ecom-cms-slot { border: 1px solid #e7e9ef; border-radius: 8px; padding: 14px; background: #fbfcfe; }
        .ecom-cms-slot + .ecom-cms-slot { margin-top: 10px; }
        .ecom-cms-slot.is-active { border-color: #3e97ff; background: #f1f8ff; }
        .ecom-cms-chip-list { display: flex; flex-wrap: wrap; gap: 6px; }
        .ecom-cms-block { border: 1px solid #e7e9ef; border-radius: 8px; padding: 14px; background: #fff; }
        .ecom-cms-block + .ecom-cms-block { margin-top: 10px; }
        .ecom-cms-actions { display: flex; flex-wrap: wrap; gap: 8px; }
        .ecom-cms-form-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
        .ecom-cms-validation { border: 1px dashed #d8dce6; border-radius: 8px; background: #fbfcfe; padding: 14px; }
        .ecom-cms-validation__item + .ecom-cms-validation__item { margin-top: 8px; }
        .ecom-cms-slot-status { border: 1px solid #e7e9ef; border-radius: 8px; padding: 14px; background: #fbfcfe; }
        .ecom-cms-slot-status + .ecom-cms-slot-status { margin-top: 10px; }
        .ecom-cms-storefront { border: 1px solid #dfe3ea; border-radius: 8px; background: #fff; overflow: hidden; }
        .ecom-cms-storefront__bar { height: 46px; border-bottom: 1px solid #e7e9ef; background: #f8fafc; display: flex; align-items: center; justify-content: space-between; padding: 0 18px; }
        .ecom-cms-storefront__brand { font-weight: 800; color: #181c32; letter-spacing: 0; }
        .ecom-cms-storefront__nav { display: flex; gap: 14px; color: #7e8299; font-size: .78rem; font-weight: 700; text-transform: uppercase; }
        .ecom-cms-storefront__section { padding: 22px; border-bottom: 1px solid #eef1f6; }
        .ecom-cms-storefront__section:last-child { border-bottom: 0; }
        .ecom-cms-storefront__hero { min-height: 220px; border-radius: 8px; background: #eef4ff; display: grid; grid-template-columns: minmax(0, 1.1fr) minmax(180px, .9fr); gap: 18px; align-items: center; padding: 26px; overflow: hidden; }
        .ecom-cms-storefront__hero-img, .ecom-cms-storefront__placeholder { width: 100%; aspect-ratio: 16 / 9; border-radius: 8px; object-fit: cover; background: #dce7f8; border: 1px solid #d7dfed; display: flex; align-items: center; justify-content: center; color: #7e8299; font-size: .82rem; }
        .ecom-cms-storefront__promo { border: 1px solid #d7eadf; background: #f2fbf5; border-radius: 8px; padding: 12px 14px; color: #246b3d; font-weight: 700; }
        .ecom-cms-storefront__grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; }
        .ecom-cms-storefront__card, .ecom-cms-storefront__product { border: 1px solid #e7e9ef; border-radius: 8px; padding: 12px; background: #fff; min-height: 120px; }
        .ecom-cms-storefront__thumb { width: 100%; aspect-ratio: 4 / 3; border-radius: 6px; background: #f3f6f9; border: 1px solid #e7e9ef; object-fit: cover; display: flex; align-items: center; justify-content: center; color: #a1a5b7; font-size: .75rem; margin-bottom: 10px; }
        .ecom-cms-storefront__products { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; }
        @media (max-width: 991px) {
            .ecom-cms-storefront__hero { grid-template-columns: 1fr; }
            .ecom-cms-storefront__grid, .ecom-cms-storefront__products { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .ecom-cms-storefront__nav { display: none; }
        }
        @media (max-width: 767px) { .ecom-cms-form-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body id="kt_app_body" data-kt-app-layout="dark-sidebar" data-kt-app-header-fixed="true" data-kt-app-sidebar-enabled="true" data-kt-app-sidebar-fixed="true" class="app-default">
<div class="d-flex flex-column flex-root app-root" id="kt_app_root">
    <div class="app-page flex-column flex-column-fluid" id="kt_app_page">
        <?= include_once '../app/vistas/includes/header/header.php'; ?>
        <div class="app-wrapper flex-column flex-row-fluid" id="kt_app_wrapper">
            <?= include_once '../app/vistas/includes/header/sidebar.php'; ?>
            <div class="app-main flex-column flex-row-fluid" id="kt_app_main">
                <div class="d-flex flex-column flex-column-fluid">
                    <div class="app-toolbar py-3 py-lg-5">
                        <div class="app-container container-fluid d-flex flex-stack flex-wrap gap-3">
                            <div>
                                <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Contenido ecommerce</h1>
                                <span class="text-muted">Editor local de bloques por slot para el CMS headless</span>
                            </div>
                            <div class="d-flex gap-2">
                                <a class="btn btn-light" href="/cms/plantillas"><i class="bi bi-columns-gap"></i> Plantillas</a>
                                <a class="btn btn-light-primary" href="/cms/frontend_constructor"><i class="bi bi-display"></i> Constructor visual</a>
                                <a class="btn btn-light" href="/cms/json"><i class="bi bi-braces"></i> JSON</a>
                                <button class="btn btn-primary" type="button" id="ecom_cms_recargar"><i class="bi bi-arrow-clockwise"></i> Recargar</button>
                            </div>
                        </div>
                    </div>
                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <div class="alert alert-info d-flex align-items-start gap-3">
                                <i class="bi bi-shield-check fs-2"></i>
                                <div>
                                    <div class="fw-bold">Fase inicial read-only</div>
                                    <div>Esta vista administra datos editoriales: banners, textos, imagenes, CTAs, orden, vigencia y estatus. La vista visual de la tienda esta en <a href="/cms/frontend_constructor" class="fw-bold">CMS &gt; Frontend &gt; Constructor visual</a>.</div>
                                </div>
                            </div>

                            <div class="row g-4 mb-5">
                                <div class="col-md-3"><div class="ecom-cms-kpi"><div class="ecom-cms-kpi__label">Plantilla</div><div class="ecom-cms-kpi__value fs-3" id="ecom_cms_plantilla">-</div><div class="text-muted fs-7 mt-2">Activa para preview.</div></div></div>
                                <div class="col-md-3"><div class="ecom-cms-kpi"><div class="ecom-cms-kpi__label">Slots</div><div class="ecom-cms-kpi__value" id="ecom_cms_slots_total">0</div><div class="text-muted fs-7 mt-2">Espacios disponibles.</div></div></div>
                                <div class="col-md-3"><div class="ecom-cms-kpi"><div class="ecom-cms-kpi__label">Bloques</div><div class="ecom-cms-kpi__value" id="ecom_cms_tipos_total">0</div><div class="text-muted fs-7 mt-2">Tipos permitidos.</div></div></div>
                                <div class="col-md-3"><div class="ecom-cms-kpi"><div class="ecom-cms-kpi__label">Persistencia</div><div class="ecom-cms-kpi__value fs-3" id="ecom_cms_persistencia">Read-only</div><div class="text-muted fs-7 mt-2">Pendiente de respaldo.</div></div></div>
                            </div>

                            <div class="ecom-cms-panel p-4 mb-5">
                                <div class="row g-3 align-items-end">
                                    <div class="col-md-3">
                                        <label class="form-label">Pagina</label>
                                        <select class="form-select form-select-solid" id="ecom_cms_pagina">
                                            <option value="home">Home</option>
                                            <option value="categoria">Categoria</option>
                                            <option value="catalogo">Catalogo</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Categoria</label>
                                        <input class="form-control form-control-solid" id="ecom_cms_categoria" type="text" value="peces">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Plantilla</label>
                                        <input class="form-control form-control-solid" id="ecom_cms_template" type="text" value="artiani_default">
                                    </div>
                                    <div class="col-md-3 text-md-end">
                                        <button class="btn btn-light-primary" type="button" id="ecom_cms_preview"><i class="bi bi-arrow-repeat"></i> Actualizar JSON</button>
                                        <button class="btn btn-light-success" type="button" id="ecom_cms_validar"><i class="bi bi-check2-circle"></i> Validar</button>
                                        <span class="badge badge-light-primary ms-2" id="ecom_cms_estado">Listo</span>
                                    </div>
                                </div>
                            </div>

                            <div class="ecom-cms-panel p-5 mb-5">
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                                    <div>
                                        <h3 class="fw-bold mb-1">Resumen editorial</h3>
                                        <span class="text-muted fs-7">Estatus, vigencia y volumen de bloques del preview local.</span>
                                    </div>
                                    <span class="badge badge-light-warning">Sin persistencia real</span>
                                </div>
                                <div id="ecom_cms_resumen_editorial"></div>
                            </div>

                            <div class="ecom-cms-panel p-5 mb-5">
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                                    <div>
                                        <h3 class="fw-bold mb-1">Vista visual separada</h3>
                                        <span class="text-muted fs-7">Contenido solo administra datos. Para ver como se armaria la pagina con plantilla, secciones y componentes, usa el constructor visual.</span>
                                    </div>
                                    <a class="btn btn-light-primary" href="/cms/frontend_constructor"><i class="bi bi-display"></i> Abrir constructor visual</a>
                                </div>
                            </div>

                            <div class="ecom-cms-panel p-5 mb-5">
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                                    <div>
                                        <h3 class="fw-bold mb-1">Cierre de contenido</h3>
                                        <span class="text-muted fs-7">Estado de la seccion antes de conectar la API publica a contenido publicado.</span>
                                    </div>
                                    <span class="badge badge-light-success">Contenido preparado</span>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <div class="border rounded p-4 h-100">
                                            <div class="fw-bold mb-2"><i class="bi bi-check2-circle text-success"></i> Listo</div>
                                            <div class="text-muted fs-7">Slots, bloques, editor local, orden, estatus, vigencia, validacion, publicabilidad y preview JSON.</div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="border rounded p-4 h-100">
                                            <div class="fw-bold mb-2"><i class="bi bi-lock text-warning"></i> Bloqueado</div>
                                            <div class="text-muted fs-7">Carga de media estructurada, lectura publica desde BD y renderer final del frontend.</div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="border rounded p-4 h-100">
                                            <div class="fw-bold mb-2"><i class="bi bi-arrow-right-circle text-primary"></i> Siguiente</div>
                                            <div class="text-muted fs-7">Conectar endpoints publicos a contenido publicado, vigente y autorizado.</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-5">
                                <div class="col-xl-4">
                                    <div class="ecom-cms-panel p-5 mb-5">
                                        <div class="d-flex justify-content-between align-items-center mb-4">
                                            <h3 class="fw-bold mb-0">Slots</h3>
                                            <a class="btn btn-sm btn-light" href="/cms/slots">Ver detalle</a>
                                        </div>
                                        <div id="ecom_cms_slots"></div>
                                    </div>
                                    <div class="ecom-cms-panel p-5 mb-5">
                                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                                            <div>
                                                <h3 class="fw-bold mb-1">Publicabilidad por slot</h3>
                                                <span class="text-muted fs-7">Semaforo local antes de publicar contenido real.</span>
                                            </div>
                                            <span class="badge badge-light-info">Preview</span>
                                        </div>
                                        <div id="ecom_cms_publicabilidad_slots"></div>
                                    </div>
                                    <div class="ecom-cms-panel p-5">
                                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                                            <div>
                                                <h3 class="fw-bold mb-1">Validacion</h3>
                                                <span class="text-muted fs-7">Revision local antes de persistir o publicar.</span>
                                            </div>
                                            <span class="badge badge-light-secondary" id="ecom_cms_validacion_resumen">Sin validar</span>
                                        </div>
                                        <div class="ecom-cms-validation" id="ecom_cms_validacion"></div>
                                    </div>
                                </div>

                                <div class="col-xl-8">
                                    <div class="ecom-cms-panel p-5 mb-5">
                                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                                            <div>
                                                <h3 class="fw-bold mb-1">Bloques del slot</h3>
                                                <span class="text-muted fs-7" id="ecom_cms_slot_activo_label">Selecciona un slot</span>
                                            </div>
                                            <div class="ecom-cms-actions">
                                                <select class="form-select form-select-sm form-select-solid w-150px" id="ecom_cms_filtro_estatus">
                                                    <option value="">Todos</option>
                                                    <option value="borrador">Borrador</option>
                                                    <option value="publicado">Publicado</option>
                                                    <option value="pausado">Pausado</option>
                                                </select>
                                                <button class="btn btn-sm btn-light-primary" type="button" id="ecom_cms_nuevo"><i class="bi bi-plus-circle"></i> Nuevo bloque</button>
                                                <button class="btn btn-sm btn-light" type="button" id="ecom_cms_cargar_defaults"><i class="bi bi-arrow-repeat"></i> Restaurar defaults</button>
                                                <button class="btn btn-sm btn-light-success" type="button" id="ecom_cms_guardar_local"><i class="bi bi-save"></i> Borrador local</button>
                                                <button class="btn btn-sm btn-light" type="button" id="ecom_cms_cargar_local"><i class="bi bi-folder2-open"></i> Cargar local</button>
                                                <button class="btn btn-sm btn-light-danger" type="button" id="ecom_cms_descartar_local"><i class="bi bi-x-circle"></i> Descartar local</button>
                                            </div>
                                        </div>
                                        <div id="ecom_cms_bloques"></div>
                                        <div class="border-top pt-5 mt-5">
                                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                                                <div>
                                                    <div class="fw-bold">Biblioteca BD</div>
                                                    <div class="text-muted fs-8">Bloques borrador/pausados guardados para reutilizar en el slot activo.</div>
                                                </div>
                                                <button class="btn btn-sm btn-light-primary" type="button" id="ecom_cms_recargar_bloques_bd"><i class="bi bi-arrow-clockwise"></i> Recargar BD</button>
                                            </div>
                                            <div id="ecom_cms_bloques_bd"></div>
                                        </div>
                                    </div>

                                    <div class="ecom-cms-panel p-5 mb-5">
                                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                                            <div>
                                                <h3 class="fw-bold mb-1">Editor</h3>
                                                <span class="text-muted fs-7" id="ecom_cms_editor_modo">Bloque local sin guardar en BD</span>
                                            </div>
                                            <span class="badge badge-light-info" id="ecom_cms_editor_tipo">-</span>
                                        </div>
                                        <form id="ecom_cms_form">
                                            <input type="hidden" id="ecom_cms_block_id">
                                            <div class="ecom-cms-form-grid mb-4">
                                                <div><label class="form-label">Tipo</label><select class="form-select form-select-solid" id="ecom_cms_block_tipo"></select></div>
                                                <div><label class="form-label">Estatus</label><select class="form-select form-select-solid" id="ecom_cms_block_estatus"><option value="borrador">Borrador</option><option value="publicado">Publicado</option><option value="pausado">Pausado</option></select></div>
                                                <div><label class="form-label">Titulo</label><input class="form-control form-control-solid" id="ecom_cms_block_titulo" type="text"></div>
                                                <div><label class="form-label">Subtitulo / texto</label><input class="form-control form-control-solid" id="ecom_cms_block_subtitulo" type="text"></div>
                                                <div><label class="form-label">CTA texto</label><input class="form-control form-control-solid" id="ecom_cms_block_cta_label" type="text"></div>
                                                <div><label class="form-label">CTA URL</label><input class="form-control form-control-solid" id="ecom_cms_block_cta_url" type="text"></div>
                                                <div><label class="form-label">Imagen desktop</label><input class="form-control form-control-solid" id="ecom_cms_block_img_desktop" type="text"></div>
                                                <div><label class="form-label">Imagen mobile</label><input class="form-control form-control-solid" id="ecom_cms_block_img_mobile" type="text"></div>
                                                <div><label class="form-label">Alt text</label><input class="form-control form-control-solid" id="ecom_cms_block_alt" type="text"></div>
                                                <div><label class="form-label">Source endpoint</label><input class="form-control form-control-solid" id="ecom_cms_block_source" type="text"></div>
                                                <div><label class="form-label">Vigente desde</label><input class="form-control form-control-solid" id="ecom_cms_block_desde" type="datetime-local"></div>
                                                <div><label class="form-label">Vigente hasta</label><input class="form-control form-control-solid" id="ecom_cms_block_hasta" type="datetime-local"></div>
                                            </div>
                                            <div class="mb-4">
                                                <label class="form-label">Contenido HTML seguro / items JSON</label>
                                                <textarea class="form-control form-control-solid" id="ecom_cms_block_payload" rows="5"></textarea>
                                            </div>
                                            <div class="d-flex flex-wrap gap-2 justify-content-between">
                                                <div class="ecom-cms-actions">
                                                    <button class="btn btn-primary" type="submit"><i class="bi bi-check2"></i> Aplicar a preview</button>
                                                    <button class="btn btn-light-primary" type="button" id="ecom_cms_guardar_bd"><i class="bi bi-database-check"></i> Guardar borrador en BD</button>
                                                    <button class="btn btn-light-success" type="button" id="ecom_cms_publicar_slot_bd"><i class="bi bi-layout-three-columns"></i> Colocar en slot BD</button>
                                                    <button class="btn btn-success" type="button" id="ecom_cms_publicar_publicacion_bd"><i class="bi bi-megaphone"></i> Publicar slot</button>
                                                    <button class="btn btn-light-warning" type="button" id="ecom_cms_pausar_publicacion_bd"><i class="bi bi-pause-circle"></i> Pausar slot</button>
                                                    <button class="btn btn-light" type="button" id="ecom_cms_limpiar_form"><i class="bi bi-eraser"></i> Limpiar</button>
                                                </div>
                                                <span class="badge badge-light-primary align-self-center">BD: borrador</span>
                                            </div>
                                        </form>
                                    </div>

                                    <div class="alert alert-warning mb-0">
                                        <div class="fw-bold">Guardado controlado</div>
                                        <div class="fs-7">El guardado actual crea bloques y publicaciones internas. Publicar slot marca la colocacion como publicada, pero la API publica seguira en fallback hasta conectar la lectura publica desde BD.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="assets/plugins/global/plugins.bundle.js"></script>
<script src="assets/js/scripts.bundle.js"></script>
<script src="/assets/js/custom/apps/erp/cms/contenido.js?v=20260810-vistas1"></script>
</body>
</html>
