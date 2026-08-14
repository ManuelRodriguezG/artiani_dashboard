<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../../">
    <title>CMS - Constructor de paginas ecommerce</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css">
    <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css">
    <!--
      Documentacion IA: Codex GPT-5, 2026-08-12.
      Proposito: constructor visual administrativo para entender como se arma una pagina frontend desde CMS.
      Impacto: CMS frontend; separa previsualizacion visual de la captura editorial.
      Contrato: read-only; no genera HTML productivo, no edita archivos frontend y no escribe BD.
    -->
    <style>
        .cms-front-kpi { border: 1px solid #e7e9ef; border-radius: 8px; background: #fff; padding: 16px; min-height: 104px; }
        .cms-front-kpi__value { font-size: 1.8rem; line-height: 1; font-weight: 800; color: #181c32; letter-spacing: 0; }
        .cms-front-kpi__label { color: #7e8299; font-size: .78rem; text-transform: uppercase; font-weight: 700; }
        .cms-front-panel, .cms-front-card { border: 1px solid #e7e9ef; border-radius: 8px; background: #fff; }
        .cms-front-card { padding: 16px; }
        .cms-front-subnav { display: flex; flex-wrap: wrap; gap: 8px; border: 1px solid #e7e9ef; border-radius: 8px; background: #fff; padding: 10px; margin-bottom: 20px; }
        .cms-front-subnav .btn { border-radius: 8px; }
        .cms-front-builder { display: grid; grid-template-columns: minmax(260px, 340px) minmax(0, 1fr) minmax(290px, 390px); gap: 20px; align-items: start; }
        .cms-front-builder__rail, .cms-front-builder__canvas, .cms-front-builder__inspect { border: 1px solid #e7e9ef; border-radius: 8px; background: #fff; padding: 16px; }
        .cms-front-template-btn, .cms-front-section-btn { width: 100%; text-align: left; border: 1px solid #e7e9ef; border-radius: 8px; background: #fff; padding: 12px; transition: border-color .15s ease, background-color .15s ease; }
        .cms-front-template-btn:hover, .cms-front-section-btn:hover, .cms-front-template-btn.is-active, .cms-front-section-btn.is-active { border-color: #009ef7; background: #f1faff; }
        .cms-front-page-frame { border: 1px solid #dfe3ea; border-radius: 8px; background: #f7f8fb; overflow: hidden; box-shadow: 0 10px 30px rgba(15, 23, 42, .08); }
        .cms-front-page-top { min-height: 64px; background: #fff; border-bottom: 1px solid #e7e9ef; display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 0 18px; }
        .cms-front-page-nav { display: flex; align-items: center; gap: 16px; color: #475467; font-size: .82rem; font-weight: 600; }
        .cms-front-page-tools { display: flex; align-items: center; gap: 8px; color: #667085; font-size: .78rem; }
        .cms-front-logo { font-weight: 900; letter-spacing: 0; color: #181c32; font-size: 1.15rem; }
        .cms-front-preview-section { border-bottom: 1px solid #e7e9ef; background: #fff; padding: 0; min-height: 84px; }
        .cms-front-preview-section.is-selected { outline: 2px solid #009ef7; outline-offset: -2px; background: #fbfdff; }
        .cms-front-section-meta { padding: 12px 18px; background: #f9fafb; border-bottom: 1px solid #eef1f5; }
        .cms-front-section-body { padding: 18px; }
        .cms-front-hero-preview { min-height: 320px; background: #111827; color: #fff; display: flex; align-items: center; padding: 40px; border-radius: 0; background-size: cover; background-position: center; position: relative; overflow: hidden; }
        .cms-front-hero-preview::before { content: ""; position: absolute; inset: 0; background: linear-gradient(90deg, rgba(17, 24, 39, .82), rgba(17, 24, 39, .35), rgba(17, 24, 39, .08)); }
        .cms-front-hero-preview > div { position: relative; max-width: 520px; }
        .cms-front-hero-preview h2 { font-size: 2.25rem; line-height: 1.05; letter-spacing: 0; }
        .cms-front-promo-preview { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; }
        .cms-front-promo-preview > div, .cms-front-card-preview, .cms-front-product-preview { border: 1px solid #e7e9ef; border-radius: 8px; padding: 12px; background: #fff; }
        .cms-front-grid-preview { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; }
        .cms-front-products-preview { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; }
        .cms-front-card-image { height: 120px; border-radius: 8px; background: #eef2f6; background-size: cover; background-position: center; margin-bottom: 10px; }
        .cms-front-product-image { aspect-ratio: 1 / 1; border-radius: 8px; background: linear-gradient(135deg, #eef2f6, #d9e2ec); margin-bottom: 10px; display: flex; align-items: center; justify-content: center; color: #667085; font-size: .72rem; font-weight: 700; }
        .cms-front-component-item { border: 1px dashed #cbd5e1; border-radius: 8px; padding: 12px; background: #fbfdff; }
        .cms-page-choice { width: 100%; text-align: left; border: 1px solid #e7e9ef; border-radius: 8px; background: #fff; padding: 14px; transition: border-color .15s ease, background-color .15s ease; }
        .cms-page-choice:hover, .cms-page-choice.is-active { border-color: #009ef7; background: #f1faff; }
        .cms-section-map { border: 1px solid #e7e9ef; border-radius: 8px; background: #fff; padding: 12px; }
        .cms-section-map__row { display: grid; grid-template-columns: 32px minmax(0, 1fr); gap: 10px; align-items: center; border-bottom: 1px solid #eef1f5; padding: 10px 0; }
        .cms-section-map__row:last-child { border-bottom: 0; }
        .cms-section-map__row.is-active { background: #f1faff; margin-left: -8px; margin-right: -8px; padding-left: 8px; padding-right: 8px; border-radius: 8px; }
        .cms-section-map__row.is-hidden { opacity: .55; }
        .cms-section-map__actions { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px; }
        .cms-section-map__actions .btn { width: 30px; height: 30px; padding: 0; display: inline-flex; align-items: center; justify-content: center; }
        .cms-quick-editor { border: 1px solid #e7e9ef; border-radius: 8px; background: #fbfdff; padding: 14px; }
        .cms-quick-editor .form-control, .cms-quick-editor .form-select { min-height: 38px; }
        .cms-home-status { border: 1px solid #e7e9ef; border-radius: 8px; background: #fbfdff; padding: 16px; }
        .cms-home-status__grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; }
        .cms-home-status__item { border: 1px solid #e7e9ef; border-radius: 8px; background: #fff; padding: 12px; min-height: 82px; }
        .cms-home-status__value { font-weight: 800; font-size: 1.35rem; line-height: 1; color: #181c32; }
        .cms-home-status__sections { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
        .cms-home-status__section { border: 1px solid #e7e9ef; border-radius: 8px; background: #fff; padding: 12px; }
        .cms-module-picker { display: grid; gap: 8px; }
        .cms-module-picker__btn { width: 100%; text-align: left; border: 1px solid #e7e9ef; border-radius: 8px; background: #fff; padding: 12px; }
        .cms-module-picker__btn:hover { border-color: #009ef7; background: #f1faff; }
        .cms-public-preview { position: fixed; inset: 0; background: rgba(15, 23, 42, .72); z-index: 1060; padding: 24px; overflow: auto; }
        .cms-public-preview__shell { max-width: 1320px; margin: 0 auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 24px 70px rgba(15, 23, 42, .28); }
        .cms-public-preview__bar { min-height: 58px; display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 0 18px; border-bottom: 1px solid #e7e9ef; background: #fff; }
        .cms-public-preview__page { background: #fff; }
        .cms-front-chips { display: flex; flex-wrap: wrap; gap: 6px; }
        .cms-front-flow { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; }
        .cms-front-flow__step { border: 1px solid #e7e9ef; border-radius: 8px; background: #fff; padding: 16px; min-height: 126px; }
        @media (max-width: 1199.98px) { .cms-front-builder { grid-template-columns: 1fr; } .cms-front-flow, .cms-home-status__grid, .cms-home-status__sections { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (max-width: 767.98px) { .cms-front-promo-preview, .cms-front-grid-preview, .cms-front-products-preview, .cms-front-flow, .cms-home-status__grid, .cms-home-status__sections { grid-template-columns: 1fr; } }
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
                                <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Constructor de paginas ecommerce</h1>
                                <span class="text-muted">Administra la estructura visual de Home, Header, Footer, Producto y Carrito desde un solo lugar</span>
                            </div>
                            <div class="d-flex gap-2">
                                <a class="btn btn-light" href="/cms/contenido"><i class="bi bi-sliders"></i> Editor avanzado</a>
                                <a class="btn btn-light" href="/cms/frontend_plantillas"><i class="bi bi-window-sidebar"></i> Plantillas</a>
                                <a class="btn btn-light" href="/docs/erp_cms_manual_uso.md" target="_blank" rel="noopener"><i class="bi bi-journal-text"></i> Manual</a>
                                <span class="badge badge-light-primary align-self-center" id="cms_frontend_estado">Listo</span>
                            </div>
                        </div>
                    </div>
                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <div class="cms-front-subnav" data-cms-frontend-nav="true">
                                <a class="btn btn-sm btn-primary" href="/cms/frontend_constructor"><i class="bi bi-display"></i> Paginas</a>
                                <a class="btn btn-sm btn-light" href="/cms/frontend_plantillas"><i class="bi bi-window-sidebar"></i> Plantillas</a>
                                <a class="btn btn-sm btn-light" href="/cms/frontend_componentes"><i class="bi bi-puzzle"></i> Componentes</a>
                                <a class="btn btn-sm btn-light" href="/cms/frontend_activaciones"><i class="bi bi-toggle-on"></i> Activaciones</a>
                            </div>

                            <div class="alert alert-info d-flex align-items-start gap-3">
                                <i class="bi bi-display fs-2"></i>
                                <div>
                                    <div class="fw-bold">Este es el lugar principal para construir tu tienda</div>
                                    <div>Primero eliges una pagina, por ejemplo Home. Luego ves sus secciones: header, portada/carrusel, promos, categorias, productos, banners y footer. Cada seccion se puede editar desde el panel derecho o abrir en el editor avanzado.</div>
                                </div>
                            </div>

                            <div class="cms-front-flow mb-5">
                                <div class="cms-front-flow__step">
                                    <div class="fw-bold mb-2"><span class="badge badge-light-primary me-2">1</span>Elige pagina</div>
                                    <div class="text-muted fs-7">Home, categoria, producto, carrito, header o footer.</div>
                                </div>
                                <div class="cms-front-flow__step">
                                    <div class="fw-bold mb-2"><span class="badge badge-light-primary me-2">2</span>Ordena secciones</div>
                                    <div class="text-muted fs-7">Portada, promos, categorias, productos, banners y CTAs.</div>
                                </div>
                                <div class="cms-front-flow__step">
                                    <div class="fw-bold mb-2"><span class="badge badge-light-primary me-2">3</span>Edita contenido</div>
                                    <div class="text-muted fs-7">Imagenes, textos, botones, productos y vigencia.</div>
                                </div>
                                <div class="cms-front-flow__step">
                                    <div class="fw-bold mb-2"><span class="badge badge-light-primary me-2">4</span>Publica</div>
                                    <div class="text-muted fs-7">Cuando este listo, publicas la seccion y la tienda la puede leer.</div>
                                </div>
                            </div>

                            <div class="row g-4 mb-5">
                                <div class="col-md-3"><div class="cms-front-kpi"><div class="cms-front-kpi__label">Layouts</div><div class="cms-front-kpi__value" id="cms_frontend_layouts_total">0</div><div class="text-muted fs-7 mt-2">Bases visuales.</div></div></div>
                                <div class="col-md-3"><div class="cms-front-kpi"><div class="cms-front-kpi__label">Componentes</div><div class="cms-front-kpi__value" id="cms_frontend_componentes_total">0</div><div class="text-muted fs-7 mt-2">Render seguros.</div></div></div>
                                <div class="col-md-3"><div class="cms-front-kpi"><div class="cms-front-kpi__label">Plantillas</div><div class="cms-front-kpi__value" id="cms_frontend_plantillas_total">0</div><div class="text-muted fs-7 mt-2">Vistas declaradas.</div></div></div>
                                <div class="col-md-3"><div class="cms-front-kpi"><div class="cms-front-kpi__label">Tema activo</div><div class="cms-front-kpi__value fs-3" id="cms_frontend_activa">-</div><div class="text-muted fs-7 mt-2">Base visual.</div></div></div>
                            </div>

                            <div class="cms-front-panel p-5 mb-5">
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                                    <div>
                                                <h3 class="fw-bold mb-1">Pagina construida</h3>
                                                <span class="text-muted fs-7">Selecciona una pagina. Home ya tiene maqueta funcional; las demas quedan preparadas para irlas completando.</span>
                                    </div>
                                    <div class="d-flex flex-wrap gap-2">
                                        <span class="badge badge-light-success" id="cms_frontend_contenido_estado">Cargando contenido</span>
                                        <button class="btn btn-sm btn-primary" type="button" id="cms_frontend_agregar_modulo"><i class="bi bi-plus-circle"></i> Agregar modulo</button>
                                        <button class="btn btn-sm btn-dark" type="button" id="cms_frontend_preview_full"><i class="bi bi-arrows-fullscreen"></i> Previsualizar Home</button>
                                        <button class="btn btn-sm btn-light-success" type="button" id="cms_frontend_cargar_borrador"><i class="bi bi-folder2-open"></i> Usar borrador local</button>
                                        <button class="btn btn-sm btn-light" type="button" id="cms_frontend_ignorar_borrador"><i class="bi bi-cloud"></i> Usar API read-only</button>
                                        <span class="badge badge-light-warning">Preview administrativo</span>
                                    </div>
                                </div>
                                <div class="cms-home-status mb-4" id="cms_frontend_estado_home">
                                    <div class="text-muted fs-7">Cargando estado de Home.</div>
                                </div>
                                <div class="cms-front-builder">
                                    <aside class="cms-front-builder__rail">
                                        <div class="mb-4">
                                            <div class="fw-bold mb-3">Paginas ecommerce</div>
                                            <div id="cms_frontend_paginas"></div>
                                        </div>
                                        <div class="mb-4">
                                            <label class="form-label fw-bold">Tema visual</label>
                                            <select class="form-select form-select-sm" id="cms_frontend_tema_selector"></select>
                                        </div>
                                        <div>
                                            <div class="fw-bold mb-3">Plantillas tecnicas</div>
                                            <div id="cms_frontend_builder_plantillas"></div>
                                        </div>
                                        <div class="separator my-5"></div>
                                        <div>
                                            <div class="fw-bold mb-3">Agregar modulos</div>
                                            <div id="cms_frontend_modulos"></div>
                                        </div>
                                    </aside>
                                    <section class="cms-front-builder__canvas">
                                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
                                            <div>
                                                <h3 class="fw-bold mb-1" id="cms_frontend_builder_titulo">Plantilla</h3>
                                                <div class="text-muted fs-7" id="cms_frontend_builder_subtitulo">Selecciona una plantilla declarada.</div>
                                            </div>
                                            <span class="badge badge-light-info">Maqueta visual CMS</span>
                                        </div>
                                        <div id="cms_frontend_preview"></div>
                                    </section>
                                    <aside class="cms-front-builder__inspect">
                                        <div class="fw-bold mb-3">Inspector de seccion</div>
                                        <div id="cms_frontend_inspector"></div>
                                        <div class="separator my-5"></div>
                                        <div class="fw-bold mb-3">Edicion rapida</div>
                                        <div id="cms_frontend_editor_rapido"></div>
                                        <div class="separator my-5"></div>
                                        <div class="fw-bold mb-3">Secciones de esta pagina</div>
                                        <div id="cms_frontend_mapa_secciones"></div>
                                        <div class="separator my-5"></div>
                                        <div class="fw-bold mb-3">Componentes disponibles</div>
                                        <div id="cms_frontend_paleta"></div>
                                    </aside>
                                </div>
                            </div>

                            <div class="cms-front-panel p-5">
                                <h3 class="fw-bold mb-4">Como lo consumira frontend</h3>
                                <div id="cms_frontend_renderer"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="cms-public-preview d-none" id="cms_frontend_preview_modal">
    <div class="cms-public-preview__shell">
        <div class="cms-public-preview__bar">
            <div>
                <div class="fw-bold">Previsualizacion Home</div>
                <div class="text-muted fs-8">Maqueta completa desde CMS. No es aun el frontend productivo.</div>
            </div>
            <button class="btn btn-sm btn-light" type="button" id="cms_frontend_preview_cerrar"><i class="bi bi-x-lg"></i> Cerrar</button>
        </div>
        <div class="cms-public-preview__page" id="cms_frontend_preview_publica"></div>
    </div>
</div>
<script src="assets/plugins/global/plugins.bundle.js"></script>
<script src="assets/js/scripts.bundle.js"></script>
<script src="/assets/js/custom/apps/erp/cms/frontend.js?v=20260812-constructor1"></script>
</body>
</html>
