<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../../">
    <title>CMS - Plantillas</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css">
    <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css">
    <!--
      Documentacion IA: Codex GPT-5, 2026-08-10.
      Proposito: vista dedicada para revisar plantilla activa, tipos de bloque y esquema CMS propuesto.
      Impacto: CMS; separa configuracion conceptual de la captura de contenido.
      Contrato: read-only; no ejecuta DDL ni modifica plantillas reales.
    -->
    <style>
        .ecom-cms-kpi { border: 1px solid #e7e9ef; border-radius: 8px; background: #fff; padding: 16px; min-height: 104px; }
        .ecom-cms-kpi__value { font-size: 1.8rem; line-height: 1; font-weight: 800; color: #181c32; letter-spacing: 0; }
        .ecom-cms-kpi__label { color: #7e8299; font-size: .78rem; text-transform: uppercase; font-weight: 700; }
        .ecom-cms-panel { border: 1px solid #e7e9ef; border-radius: 8px; background: #fff; }
        .ecom-cms-code { min-height: 360px; max-height: 620px; overflow: auto; background: #111827; color: #e5e7eb; border-radius: 8px; padding: 16px; font-size: .78rem; line-height: 1.5; white-space: pre-wrap; }
    </style>
</head>
<body id="kt_app_body" data-cms-json-mode="manifest" data-kt-app-layout="dark-sidebar" data-kt-app-header-fixed="true" data-kt-app-sidebar-enabled="true" data-kt-app-sidebar-fixed="true" class="app-default">
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
                                <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Plantillas CMS</h1>
                                <span class="text-muted">Estructura activa, tipos de bloque y persistencia propuesta</span>
                            </div>
                            <div class="d-flex gap-2">
                                <a class="btn btn-light" href="/cms/contenido"><i class="bi bi-layout-text-window-reverse"></i> Contenido</a>
                                <a class="btn btn-light" href="/cms/slots"><i class="bi bi-grid-3x3-gap"></i> Slots</a>
                                <a class="btn btn-light" href="/docs/erp_cms_manual_uso.md" target="_blank" rel="noopener"><i class="bi bi-journal-text"></i> Manual</a>
                                <button class="btn btn-primary" type="button" id="ecom_cms_recargar"><i class="bi bi-arrow-clockwise"></i> Recargar</button>
                            </div>
                        </div>
                    </div>
                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <div class="alert alert-info d-flex align-items-start gap-3">
                                <i class="bi bi-columns-gap fs-2"></i>
                                <div>
                                    <div class="fw-bold">Plantilla de contenido read-only</div>
                                    <div>Esta pantalla define slots y tipos editoriales. Las plantillas visuales del ecommerce viven en CMS &gt; Frontend.</div>
                                </div>
                            </div>

                            <div class="row g-4 mb-5">
                                <div class="col-md-3"><div class="ecom-cms-kpi"><div class="ecom-cms-kpi__label">Plantilla</div><div class="ecom-cms-kpi__value fs-3" id="ecom_cms_plantilla">-</div><div class="text-muted fs-7 mt-2">Activa para preview.</div></div></div>
                                <div class="col-md-3"><div class="ecom-cms-kpi"><div class="ecom-cms-kpi__label">Slots</div><div class="ecom-cms-kpi__value" id="ecom_cms_slots_total">0</div><div class="text-muted fs-7 mt-2">Declarados en manifest.</div></div></div>
                                <div class="col-md-3"><div class="ecom-cms-kpi"><div class="ecom-cms-kpi__label">Bloques</div><div class="ecom-cms-kpi__value" id="ecom_cms_tipos_total">0</div><div class="text-muted fs-7 mt-2">Tipos disponibles.</div></div></div>
                                <div class="col-md-3"><div class="ecom-cms-kpi"><div class="ecom-cms-kpi__label">Persistencia</div><div class="ecom-cms-kpi__value fs-3" id="ecom_cms_persistencia">Read-only</div><div class="text-muted fs-7 mt-2">DDL no ejecutado.</div></div></div>
                            </div>

                            <div class="ecom-cms-panel p-5 mb-5">
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                                    <div>
                                        <h3 class="fw-bold mb-1">Contratos del CMS</h3>
                                        <span class="text-muted fs-7">Estado interno, endpoints administrativos y endpoints publicos que despues leera el frontend.</span>
                                    </div>
                                    <span class="badge badge-light-warning">Read-only</span>
                                </div>
                                <div id="ecom_cms_contratos"></div>
                            </div>

                            <div class="row g-5">
                                <div class="col-xl-6">
                                    <div class="ecom-cms-panel p-5 mb-5">
                                        <h3 class="fw-bold mb-4">Tipos de bloque</h3>
                                        <div id="ecom_cms_tipos"></div>
                                    </div>
                                </div>
                                <div class="col-xl-6">
                                    <div class="ecom-cms-panel p-5 mb-5">
                                        <h3 class="fw-bold mb-4">Esquema propuesto</h3>
                                        <div id="ecom_cms_esquema"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="ecom-cms-panel p-5">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <div>
                                        <h3 class="fw-bold mb-1">Manifest read-only</h3>
                                        <span class="text-muted fs-7">Contrato interno de plantilla y slots disponibles.</span>
                                    </div>
                                    <span class="badge badge-light-primary" id="ecom_cms_estado">Listo</span>
                                </div>
                                <pre class="ecom-cms-code" id="ecom_cms_json">{}</pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<input type="hidden" id="ecom_cms_pagina" value="home">
<input type="hidden" id="ecom_cms_categoria" value="peces">
<input type="hidden" id="ecom_cms_template" value="artiani_default">
<script src="assets/plugins/global/plugins.bundle.js"></script>
<script src="assets/js/scripts.bundle.js"></script>
<script src="/assets/js/custom/apps/erp/cms/contenido.js?v=20260810-vistas1"></script>
</body>
</html>
