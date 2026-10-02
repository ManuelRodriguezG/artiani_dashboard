<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../../">
    <title>Ecommerce - Analisis Analytics</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css">
    <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css">
    <!--
      Documentacion IA: Codex GPT-5, 2026-09-29.
      Proposito: vista interna read-only para analizar secciones de Ecommerce Analytics.
      Impacto: exploracion de sesiones, page views, productos, busquedas y WhatsApp sin PII.
      Contrato: consume endpoint protegido analytics_analisis_erp; no escribe BD.
    -->
    <style>
        .ecom-aa-panel { border: 1px solid #e7e9ef; border-radius: 8px; background: #fff; }
        .ecom-aa-tabs { display: flex; flex-wrap: wrap; gap: 8px; }
        .ecom-aa-tab { border: 1px solid #dfe4ef; border-radius: 8px; background: #fff; color: #3f4254; padding: 10px 12px; font-weight: 700; }
        .ecom-aa-tab.is-active { border-color: #3e97ff; background: #f5f9ff; color: #1b84ff; }
        .ecom-aa-kpi { border: 1px solid #e7e9ef; border-radius: 8px; background: #fbfcfe; padding: 14px; min-height: 88px; }
        .ecom-aa-kpi strong { display: block; font-size: 1.45rem; line-height: 1; color: #181c32; }
        .ecom-aa-route { max-width: 520px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .ecom-aa-table-wrap { max-height: 640px; overflow: auto; }
        .ecom-aa-table th { position: sticky; top: 0; background: #fff; z-index: 1; }
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
                                <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Analisis Ecommerce / Analytics</h1>
                                <span class="text-muted">Exploracion detallada por seccion anonima</span>
                            </div>
                            <div class="d-flex gap-2">
                                <a class="btn btn-light" href="/ecommercePublico/analytics"><i class="bi bi-graph-up"></i> Dashboard</a>
                                <a class="btn btn-light" href="/ecommercePublico/analytics_flujo"><i class="bi bi-diagram-3"></i> Flujo</a>
                                <button class="btn btn-primary" type="button" id="ecom_aa_recargar"><i class="bi bi-arrow-clockwise"></i> Recargar</button>
                            </div>
                        </div>
                    </div>
                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <div class="ecom-aa-panel p-4 mb-5">
                                <div class="ecom-aa-tabs" id="ecom_aa_tabs">
                                    <button class="ecom-aa-tab is-active" type="button" data-section="sesiones"><i class="bi bi-person-lines-fill"></i> Sesiones</button>
                                    <button class="ecom-aa-tab" type="button" data-section="page_views"><i class="bi bi-window"></i> Page views</button>
                                    <button class="ecom-aa-tab" type="button" data-section="productos"><i class="bi bi-box"></i> Productos vistos</button>
                                    <button class="ecom-aa-tab" type="button" data-section="busquedas"><i class="bi bi-search"></i> Busquedas</button>
                                    <button class="ecom-aa-tab" type="button" data-section="whatsapp"><i class="bi bi-whatsapp"></i> WhatsApp</button>
                                    <button class="ecom-aa-tab" type="button" data-section="eventos"><i class="bi bi-activity"></i> Eventos</button>
                                </div>
                            </div>

                            <div class="ecom-aa-panel p-4 mb-5">
                                <div class="row g-3 align-items-end">
                                    <div class="col-md-2">
                                        <label class="form-label">Desde</label>
                                        <input class="form-control form-control-solid" type="date" id="ecom_aa_desde">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Hasta</label>
                                        <input class="form-control form-control-solid" type="date" id="ecom_aa_hasta">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Limite</label>
                                        <select class="form-select form-select-solid" id="ecom_aa_limite">
                                            <option value="50">50</option>
                                            <option value="100" selected>100</option>
                                            <option value="250">250</option>
                                            <option value="500">500</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Buscar</label>
                                        <input class="form-control form-control-solid" type="search" id="ecom_aa_q" placeholder="Ruta, producto, busqueda, session key">
                                    </div>
                                    <div class="col-md-2 text-md-end">
                                        <span class="badge badge-light-primary" id="ecom_aa_estado">Listo</span>
                                        <div class="text-muted fs-8 mt-2">Actualizado: <span id="ecom_aa_actualizado">-</span></div>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-4 mb-5" id="ecom_aa_resumen"></div>

                            <div class="ecom-aa-panel p-5">
                                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
                                    <div>
                                        <h3 class="fw-bold mb-1" id="ecom_aa_titulo">Sesiones</h3>
                                        <span class="text-muted fs-7" id="ecom_aa_subtitulo">Registros anonimos del rango seleccionado</span>
                                    </div>
                                    <span class="badge badge-light" id="ecom_aa_total_items">0 registros</span>
                                </div>
                                <div class="ecom-aa-table-wrap" id="ecom_aa_tabla"></div>
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
<script src="/assets/js/custom/apps/erp/ecommerce/analytics_analisis.js?v=20260929-v1"></script>
</body>
</html>
