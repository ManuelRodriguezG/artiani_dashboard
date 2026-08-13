<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../../">
    <title>CMS - Constructor visual frontend</title>
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
        .cms-front-builder { display: grid; grid-template-columns: minmax(240px, 310px) minmax(0, 1fr) minmax(280px, 370px); gap: 20px; align-items: start; }
        .cms-front-builder__rail, .cms-front-builder__canvas, .cms-front-builder__inspect { border: 1px solid #e7e9ef; border-radius: 8px; background: #fff; padding: 16px; }
        .cms-front-template-btn, .cms-front-section-btn { width: 100%; text-align: left; border: 1px solid #e7e9ef; border-radius: 8px; background: #fff; padding: 12px; transition: border-color .15s ease, background-color .15s ease; }
        .cms-front-template-btn:hover, .cms-front-section-btn:hover, .cms-front-template-btn.is-active, .cms-front-section-btn.is-active { border-color: #009ef7; background: #f1faff; }
        .cms-front-page-frame { border: 1px solid #dfe3ea; border-radius: 8px; background: #f8fafc; overflow: hidden; }
        .cms-front-page-top { height: 54px; background: #fff; border-bottom: 1px solid #e7e9ef; display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 0 16px; }
        .cms-front-logo { font-weight: 800; letter-spacing: 0; color: #181c32; }
        .cms-front-preview-section { border-bottom: 1px solid #e7e9ef; background: #fff; padding: 18px; min-height: 84px; }
        .cms-front-preview-section.is-selected { outline: 2px solid #009ef7; outline-offset: -2px; background: #fbfdff; }
        .cms-front-hero-preview { min-height: 170px; background: linear-gradient(135deg, #101828 0%, #344054 55%, #0ba5ec 100%); color: #fff; display: flex; align-items: center; padding: 24px; border-radius: 8px; }
        .cms-front-promo-preview { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; }
        .cms-front-promo-preview > div, .cms-front-card-preview, .cms-front-product-preview { border: 1px solid #e7e9ef; border-radius: 8px; padding: 12px; background: #fff; }
        .cms-front-grid-preview { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; }
        .cms-front-products-preview { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; }
        .cms-front-component-item { border: 1px dashed #cbd5e1; border-radius: 8px; padding: 12px; background: #fbfdff; }
        .cms-front-chips { display: flex; flex-wrap: wrap; gap: 6px; }
        .cms-front-flow { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; }
        .cms-front-flow__step { border: 1px solid #e7e9ef; border-radius: 8px; background: #fff; padding: 16px; min-height: 126px; }
        @media (max-width: 1199.98px) { .cms-front-builder { grid-template-columns: 1fr; } .cms-front-flow { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (max-width: 767.98px) { .cms-front-promo-preview, .cms-front-grid-preview, .cms-front-products-preview, .cms-front-flow { grid-template-columns: 1fr; } }
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
                                <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Constructor visual</h1>
                                <span class="text-muted">Vista administrativa para entender como se armara el frontend con plantillas, componentes y slots</span>
                            </div>
                            <div class="d-flex gap-2">
                                <a class="btn btn-light" href="/cms/contenido"><i class="bi bi-layout-text-window-reverse"></i> Contenido</a>
                                <a class="btn btn-light" href="/cms/frontend_plantillas"><i class="bi bi-window-sidebar"></i> Plantillas</a>
                                <a class="btn btn-light" href="/docs/erp_cms_manual_uso.md" target="_blank" rel="noopener"><i class="bi bi-journal-text"></i> Manual</a>
                                <span class="badge badge-light-primary align-self-center" id="cms_frontend_estado">Listo</span>
                            </div>
                        </div>
                    </div>
                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <div class="cms-front-subnav" data-cms-frontend-nav="true">
                                <a class="btn btn-sm btn-primary" href="/cms/frontend_constructor"><i class="bi bi-display"></i> Constructor</a>
                                <a class="btn btn-sm btn-light" href="/cms/frontend_plantillas"><i class="bi bi-window-sidebar"></i> Plantillas</a>
                                <a class="btn btn-sm btn-light" href="/cms/frontend_componentes"><i class="bi bi-puzzle"></i> Componentes</a>
                                <a class="btn btn-sm btn-light" href="/cms/frontend_activaciones"><i class="bi bi-toggle-on"></i> Activaciones</a>
                            </div>

                            <div class="alert alert-info d-flex align-items-start gap-3">
                                <i class="bi bi-display fs-2"></i>
                                <div>
                                    <div class="fw-bold">Aqui vive la parte visual</div>
                                    <div>Contenido crea los datos editables. Este constructor muestra que plantilla, secciones, componentes y variantes usaria el frontend para pintar la pagina.</div>
                                </div>
                            </div>

                            <div class="cms-front-flow mb-5">
                                <div class="cms-front-flow__step">
                                    <div class="fw-bold mb-2"><span class="badge badge-light-primary me-2">1</span>Contenido</div>
                                    <div class="text-muted fs-7">Se crean banners, textos, imagenes, CTAs y colecciones.</div>
                                </div>
                                <div class="cms-front-flow__step">
                                    <div class="fw-bold mb-2"><span class="badge badge-light-primary me-2">2</span>Slot</div>
                                    <div class="text-muted fs-7">Cada bloque se coloca en un espacio como <code>home.hero</code>.</div>
                                </div>
                                <div class="cms-front-flow__step">
                                    <div class="fw-bold mb-2"><span class="badge badge-light-primary me-2">3</span>Componente</div>
                                    <div class="text-muted fs-7">El slot usa un componente frontend seguro como <code>HeroSlider</code>.</div>
                                </div>
                                <div class="cms-front-flow__step">
                                    <div class="fw-bold mb-2"><span class="badge badge-light-primary me-2">4</span>Frontend</div>
                                    <div class="text-muted fs-7">La tienda real consume JSON y renderiza con sus componentes programados.</div>
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
                                        <h3 class="fw-bold mb-1">Pagina armada por plantilla</h3>
                                        <span class="text-muted fs-7">Selecciona Home, Categoria o Catalogo para ver sus secciones y el componente que renderizaria cada slot.</span>
                                    </div>
                                    <div class="d-flex flex-wrap gap-2">
                                        <span class="badge badge-light-success" id="cms_frontend_contenido_estado">Cargando contenido</span>
                                        <span class="badge badge-light-warning">Preview administrativo</span>
                                    </div>
                                </div>
                                <div class="cms-front-builder">
                                    <aside class="cms-front-builder__rail">
                                        <div class="mb-4">
                                            <label class="form-label fw-bold">Tema visual</label>
                                            <select class="form-select form-select-sm" id="cms_frontend_tema_selector"></select>
                                        </div>
                                        <div>
                                            <div class="fw-bold mb-3">Paginas / plantillas</div>
                                            <div id="cms_frontend_builder_plantillas"></div>
                                        </div>
                                    </aside>
                                    <section class="cms-front-builder__canvas">
                                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
                                            <div>
                                                <h3 class="fw-bold mb-1" id="cms_frontend_builder_titulo">Plantilla</h3>
                                                <div class="text-muted fs-7" id="cms_frontend_builder_subtitulo">Selecciona una plantilla declarada.</div>
                                            </div>
                                            <span class="badge badge-light-info">No es el HTML final</span>
                                        </div>
                                        <div id="cms_frontend_preview"></div>
                                    </section>
                                    <aside class="cms-front-builder__inspect">
                                        <div class="fw-bold mb-3">Inspector de seccion</div>
                                        <div id="cms_frontend_inspector"></div>
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
<script src="assets/plugins/global/plugins.bundle.js"></script>
<script src="assets/js/scripts.bundle.js"></script>
<script src="/assets/js/custom/apps/erp/cms/frontend.js?v=20260812-constructor1"></script>
</body>
</html>
