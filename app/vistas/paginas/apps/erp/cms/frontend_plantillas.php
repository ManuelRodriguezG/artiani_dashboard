<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../../">
    <title>CMS - Plantillas frontend</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css">
    <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css">
    <!--
      Documentacion IA: Codex GPT-5, 2026-08-10.
      Proposito: vista read-only de plantillas de vista frontend administrables desde CMS.
      Impacto: CMS frontend; prepara layouts tipo Wokiee y mapeos slot->componente para consumo por API.
      Contrato: no edita archivos del frontend, no permite HTML/CSS/JS libre.
    -->
    <style>
        .cms-front-kpi { border: 1px solid #e7e9ef; border-radius: 8px; background: #fff; padding: 16px; min-height: 104px; }
        .cms-front-kpi__value { font-size: 1.8rem; line-height: 1; font-weight: 800; color: #181c32; letter-spacing: 0; }
        .cms-front-kpi__label { color: #7e8299; font-size: .78rem; text-transform: uppercase; font-weight: 700; }
        .cms-front-panel, .cms-front-card { border: 1px solid #e7e9ef; border-radius: 8px; background: #fff; }
        .cms-front-card { padding: 16px; }
        .cms-front-code { min-height: 420px; max-height: 680px; overflow: auto; background: #111827; color: #e5e7eb; border-radius: 8px; padding: 16px; font-size: .78rem; line-height: 1.5; white-space: pre-wrap; }
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
                                <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Plantillas de vista</h1>
                                <span class="text-muted">Layouts frontend administrables por JSON, sin editar archivos del ecommerce</span>
                            </div>
                            <div class="d-flex gap-2">
                                <a class="btn btn-light" href="/cms/contenido"><i class="bi bi-layout-text-window-reverse"></i> Contenido</a>
                                <a class="btn btn-light" href="/cms/frontend_componentes"><i class="bi bi-puzzle"></i> Componentes</a>
                                <a class="btn btn-light" href="/docs/erp_cms_manual_uso.md" target="_blank" rel="noopener"><i class="bi bi-journal-text"></i> Manual</a>
                                <span class="badge badge-light-primary align-self-center" id="cms_frontend_estado">Listo</span>
                            </div>
                        </div>
                    </div>
                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <div class="alert alert-info d-flex align-items-start gap-3">
                                <i class="bi bi-window-sidebar fs-2"></i>
                                <div>
                                    <div class="fw-bold">Plantillas frontend read-only</div>
                                    <div>Esta fase define layouts, componentes y variantes por JSON seguro. No edita archivos del ecommerce y no envia JS, CSS ni HTML libre.</div>
                                </div>
                            </div>

                            <div class="row g-4 mb-5">
                                <div class="col-md-3"><div class="cms-front-kpi"><div class="cms-front-kpi__label">Layouts</div><div class="cms-front-kpi__value" id="cms_frontend_layouts_total">0</div><div class="text-muted fs-7 mt-2">Bases visuales.</div></div></div>
                                <div class="col-md-3"><div class="cms-front-kpi"><div class="cms-front-kpi__label">Componentes</div><div class="cms-front-kpi__value" id="cms_frontend_componentes_total">0</div><div class="text-muted fs-7 mt-2">Render seguros.</div></div></div>
                                <div class="col-md-3"><div class="cms-front-kpi"><div class="cms-front-kpi__label">Plantillas</div><div class="cms-front-kpi__value" id="cms_frontend_plantillas_total">0</div><div class="text-muted fs-7 mt-2">Vistas declaradas.</div></div></div>
                                <div class="col-md-3"><div class="cms-front-kpi"><div class="cms-front-kpi__label">Home activa</div><div class="cms-front-kpi__value fs-3" id="cms_frontend_activa">-</div><div class="text-muted fs-7 mt-2">Preview.</div></div></div>
                            </div>

                            <div class="cms-front-panel p-5 mb-5">
                                <h3 class="fw-bold mb-4">Contrato renderer</h3>
                                <div id="cms_frontend_renderer"></div>
                            </div>

                            <div class="row g-5">
                                <div class="col-xl-7">
                                    <div class="cms-front-panel p-5">
                                        <h3 class="fw-bold mb-4">Plantillas declaradas</h3>
                                        <div id="cms_frontend_plantillas"></div>
                                    </div>
                                </div>
                                <div class="col-xl-5">
                                    <div class="cms-front-panel p-5">
                                        <h3 class="fw-bold mb-4">Manifest frontend</h3>
                                        <pre class="cms-front-code" id="cms_frontend_json">{}</pre>
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
<script src="/assets/js/custom/apps/erp/cms/frontend.js?v=20260810-frontend1"></script>
</body>
</html>
