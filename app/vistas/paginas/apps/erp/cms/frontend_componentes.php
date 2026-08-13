<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../../">
    <title>CMS - Componentes frontend</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css">
    <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css">
    <!--
      Documentacion IA: Codex GPT-5, 2026-08-10.
      Proposito: vista read-only de componentes frontend permitidos para plantillas CMS.
      Impacto: CMS frontend; audita componentes, variantes, bloques permitidos y slots compatibles.
      Contrato: no habilita codigo libre ni modifica el frontend.
    -->
    <style>
        .cms-front-kpi { border: 1px solid #e7e9ef; border-radius: 8px; background: #fff; padding: 16px; min-height: 104px; }
        .cms-front-kpi__value { font-size: 1.8rem; line-height: 1; font-weight: 800; color: #181c32; letter-spacing: 0; }
        .cms-front-kpi__label { color: #7e8299; font-size: .78rem; text-transform: uppercase; font-weight: 700; }
        .cms-front-panel, .cms-front-card { border: 1px solid #e7e9ef; border-radius: 8px; background: #fff; }
        .cms-front-card { padding: 16px; }
        .cms-front-chips { display: flex; flex-wrap: wrap; gap: 6px; }
        .cms-front-subnav { display: flex; flex-wrap: wrap; gap: 8px; border: 1px solid #e7e9ef; border-radius: 8px; background: #fff; padding: 10px; margin-bottom: 20px; }
        .cms-front-subnav .btn { border-radius: 8px; }
        .cms-front-components-grid { display: grid; grid-template-columns: minmax(240px, 320px) minmax(0, 1fr); gap: 20px; align-items: start; }
        .cms-front-component-btn { width: 100%; text-align: left; border: 1px solid #e7e9ef; border-radius: 8px; background: #fff; padding: 12px; transition: border-color .15s ease, background-color .15s ease; }
        .cms-front-component-btn:hover, .cms-front-component-btn.is-active { border-color: #009ef7; background: #f1faff; }
        .cms-front-preview-box { border: 1px solid #dfe3ea; border-radius: 8px; background: #f8fafc; padding: 16px; }
        .cms-front-hero-preview { min-height: 170px; background: linear-gradient(135deg, #101828 0%, #344054 55%, #0ba5ec 100%); color: #fff; display: flex; align-items: center; padding: 24px; border-radius: 8px; }
        .cms-front-promo-preview { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; }
        .cms-front-promo-preview > div, .cms-front-card-preview, .cms-front-product-preview { border: 1px solid #e7e9ef; border-radius: 8px; padding: 12px; background: #fff; }
        .cms-front-grid-preview { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; }
        .cms-front-products-preview { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; }
        @media (max-width: 991.98px) { .cms-front-components-grid { grid-template-columns: 1fr; } }
        @media (max-width: 767.98px) { .cms-front-promo-preview, .cms-front-grid-preview, .cms-front-products-preview { grid-template-columns: 1fr 1fr; } }
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
                                <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Componentes frontend</h1>
                                <span class="text-muted">Catalogo seguro de componentes, variantes y slots compatibles</span>
                            </div>
                            <div class="d-flex gap-2">
                                <a class="btn btn-light" href="/cms/frontend_plantillas"><i class="bi bi-window-sidebar"></i> Plantillas</a>
                                <a class="btn btn-light" href="/cms/frontend_activaciones"><i class="bi bi-toggle-on"></i> Activaciones</a>
                                <a class="btn btn-light" href="/docs/erp_cms_manual_uso.md" target="_blank" rel="noopener"><i class="bi bi-journal-text"></i> Manual</a>
                                <span class="badge badge-light-primary align-self-center" id="cms_frontend_estado">Listo</span>
                            </div>
                        </div>
                    </div>
                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <div class="cms-front-subnav" data-cms-frontend-nav="true">
                                <a class="btn btn-sm btn-light" href="/cms/frontend_constructor"><i class="bi bi-display"></i> Constructor</a>
                                <a class="btn btn-sm btn-light" href="/cms/frontend_plantillas"><i class="bi bi-window-sidebar"></i> Plantillas</a>
                                <a class="btn btn-sm btn-primary" href="/cms/frontend_componentes"><i class="bi bi-puzzle"></i> Componentes</a>
                                <a class="btn btn-sm btn-light" href="/cms/frontend_activaciones"><i class="bi bi-toggle-on"></i> Activaciones</a>
                            </div>
                            <div class="alert alert-info d-flex align-items-start gap-3">
                                <i class="bi bi-puzzle fs-2"></i>
                                <div>
                                    <div class="fw-bold">Catalogo de componentes read-only</div>
                                    <div>Esta pantalla registra componentes que el frontend ya debe tener programados. No crea archivos, no carga JS/CSS y no permite HTML libre.</div>
                                </div>
                            </div>

                            <div class="row g-4 mb-5">
                                <div class="col-md-3"><div class="cms-front-kpi"><div class="cms-front-kpi__label">Layouts</div><div class="cms-front-kpi__value" id="cms_frontend_layouts_total">0</div><div class="text-muted fs-7 mt-2">Bases visuales.</div></div></div>
                                <div class="col-md-3"><div class="cms-front-kpi"><div class="cms-front-kpi__label">Componentes</div><div class="cms-front-kpi__value" id="cms_frontend_componentes_total">0</div><div class="text-muted fs-7 mt-2">Disponibles.</div></div></div>
                                <div class="col-md-3"><div class="cms-front-kpi"><div class="cms-front-kpi__label">Plantillas</div><div class="cms-front-kpi__value" id="cms_frontend_plantillas_total">0</div><div class="text-muted fs-7 mt-2">Usan estos componentes.</div></div></div>
                                <div class="col-md-3"><div class="cms-front-kpi"><div class="cms-front-kpi__label">Tema activo</div><div class="cms-front-kpi__value fs-3" id="cms_frontend_activa">-</div><div class="text-muted fs-7 mt-2">Base visual.</div></div></div>
                            </div>

                            <div class="cms-front-panel p-5 mb-5">
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                                    <div>
                                        <h3 class="fw-bold mb-1">Explorador visual de componentes</h3>
                                        <span class="text-muted fs-7">Selecciona un componente para revisar preview, compatibilidad y uso dentro de plantillas.</span>
                                    </div>
                                    <span class="badge badge-light-info">Tema activo read-only</span>
                                </div>
                                <div class="cms-front-components-grid">
                                    <aside>
                                        <label class="form-label fw-bold">Tema visual</label>
                                        <select class="form-select form-select-sm mb-4" id="cms_frontend_tema_selector"></select>
                                        <div class="fw-bold mb-3">Componentes del tema</div>
                                        <div id="cms_frontend_componentes_selector"></div>
                                    </aside>
                                    <section>
                                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
                                            <div>
                                                <h3 class="fw-bold mb-1" id="cms_frontend_componente_titulo">Componente</h3>
                                                <span class="text-muted fs-7" id="cms_frontend_componente_subtitulo">Catalogo seguro.</span>
                                            </div>
                                            <span class="badge badge-light-warning">No ejecuta codigo</span>
                                        </div>
                                        <div class="cms-front-preview-box mb-5" id="cms_frontend_componente_preview"></div>
                                        <div class="row g-4">
                                            <div class="col-xl-6">
                                                <div class="border rounded p-4 h-100">
                                                    <div class="fw-bold mb-3">Compatibilidad</div>
                                                    <div id="cms_frontend_componente_compatibilidad"></div>
                                                </div>
                                            </div>
                                            <div class="col-xl-6">
                                                <div class="border rounded p-4 h-100">
                                                    <div class="fw-bold mb-3">Uso en plantillas</div>
                                                    <div id="cms_frontend_componente_uso"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </section>
                                </div>
                            </div>

                            <div class="cms-front-panel p-5 mb-5">
                                <h3 class="fw-bold mb-4">Contrato renderer</h3>
                                <div id="cms_frontend_renderer"></div>
                            </div>

                            <div class="cms-front-panel p-5 mb-5">
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                                    <div>
                                        <h3 class="fw-bold mb-1">Persistencia frontend propuesta</h3>
                                        <span class="text-muted fs-7">Catalogo seguro persistible cuando se autorice respaldo y DDL.</span>
                                    </div>
                                    <span class="badge badge-light-warning">Read-only</span>
                                </div>
                                <div id="cms_frontend_esquema"></div>
                            </div>

                            <div class="cms-front-panel p-5 mb-5">
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                                    <div>
                                        <h3 class="fw-bold mb-1">Contratos frontend bloqueados</h3>
                                        <span class="text-muted fs-7">Los componentes se validaran contra estos contratos cuando se active persistencia.</span>
                                    </div>
                                    <span class="badge badge-light-danger">No ejecuta codigo</span>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-row-dashed fs-7 gy-3 mb-0">
                                        <tbody>
                                            <tr><td class="fw-semibold">Guardar plantilla</td><td><code>/cms/frontend_plantilla_guardar_erp</code></td><td class="text-end"><span class="badge badge-light-warning">Bloqueado</span></td></tr>
                                            <tr><td class="fw-semibold">Estatus plantilla</td><td><code>/cms/frontend_plantilla_estatus_erp</code></td><td class="text-end"><span class="badge badge-light-warning">Bloqueado</span></td></tr>
                                            <tr><td class="fw-semibold">Guardar seccion</td><td><code>/cms/frontend_seccion_guardar_erp</code></td><td class="text-end"><span class="badge badge-light-warning">Bloqueado</span></td></tr>
                                            <tr><td class="fw-semibold">Estatus seccion</td><td><code>/cms/frontend_seccion_estatus_erp</code></td><td class="text-end"><span class="badge badge-light-warning">Bloqueado</span></td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="cms-front-panel p-5">
                                <h3 class="fw-bold mb-4">Componentes permitidos</h3>
                                <div id="cms_frontend_componentes"></div>
                            </div>
                            <pre class="d-none" id="cms_frontend_json">{}</pre>
                            <div class="d-none" id="cms_frontend_plantillas"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="assets/plugins/global/plugins.bundle.js"></script>
<script src="assets/js/scripts.bundle.js"></script>
<script src="/assets/js/custom/apps/erp/cms/frontend.js?v=20260810-frontend1"></script>
</body>
</html>
