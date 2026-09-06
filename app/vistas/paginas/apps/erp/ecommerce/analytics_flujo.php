<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../../">
    <title>Ecommerce - Flujo de sesiones</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css">
    <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css">
    <!--
      Documentacion IA: Codex GPT-5, 2026-09-05.
      Proposito: vista interna read-only para flujo de navegacion por session hash anonimo.
      Impacto: analisis operativo de recorridos sin PII, stock exacto, ventas ni inventario.
      Contrato: consume endpoint protegido analytics_flujo_erp; no escribe BD.
    -->
    <style>
        .ecom-flow-panel { border: 1px solid #e7e9ef; border-radius: 8px; background: #fff; }
        .ecom-flow-session { width: 100%; border: 1px solid #e7e9ef; border-radius: 8px; background: #fff; text-align: left; padding: 12px; transition: border-color .15s, background .15s; }
        .ecom-flow-session:hover, .ecom-flow-session.is-active { border-color: #3e97ff; background: #f5f9ff; }
        .ecom-flow-sessions { max-height: 680px; overflow: auto; }
        .ecom-flow-timeline { position: relative; display: grid; gap: 12px; }
        .ecom-flow-item { display: grid; grid-template-columns: 34px minmax(0, 1fr); gap: 12px; align-items: start; }
        .ecom-flow-dot { width: 34px; height: 34px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; background: #f1f3f8; color: #3f4254; }
        .ecom-flow-dot.is-conversion { background: #e8fff3; color: #50cd89; }
        .ecom-flow-dot.is-search { background: #fff8dd; color: #f6c000; }
        .ecom-flow-card { border: 1px solid #edf0f5; border-radius: 8px; padding: 12px 14px; background: #fff; min-width: 0; }
        .ecom-flow-kpi { border: 1px solid #e7e9ef; border-radius: 8px; background: #fbfcfe; padding: 12px; min-height: 78px; }
        .ecom-flow-kpi strong { display: block; font-size: 1.35rem; color: #181c32; }
        .ecom-flow-route { max-width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
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
                                <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Flujo de sesiones</h1>
                                <span class="text-muted">Recorridos anonimos del ecommerce publico</span>
                            </div>
                            <div class="d-flex gap-2">
                                <a class="btn btn-light" href="/ecommercePublico/analytics"><i class="bi bi-graph-up"></i> Analytics</a>
                                <a class="btn btn-light-primary" href="/ecommercePublico/leads"><i class="bi bi-cart-check"></i> Leads</a>
                                <button class="btn btn-primary" type="button" id="ecom_flow_recargar"><i class="bi bi-arrow-clockwise"></i> Recargar</button>
                            </div>
                        </div>
                    </div>
                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <div class="ecom-flow-panel p-4 mb-5">
                                <div class="row g-3 align-items-end">
                                    <div class="col-md-3">
                                        <label class="form-label">Desde</label>
                                        <input class="form-control form-control-solid" type="date" id="ecom_flow_desde">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Hasta</label>
                                        <input class="form-control form-control-solid" type="date" id="ecom_flow_hasta">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Limite sesiones</label>
                                        <select class="form-select form-select-solid" id="ecom_flow_limite">
                                            <option value="25">25</option>
                                            <option value="50">50</option>
                                            <option value="100">100</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 text-md-end">
                                        <span class="badge badge-light-primary" id="ecom_flow_estado">Listo</span>
                                        <div class="text-muted fs-8 mt-2">Actualizado: <span id="ecom_flow_actualizado">-</span></div>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-5">
                                <div class="col-xl-4">
                                    <div class="ecom-flow-panel p-5 h-100">
                                        <div class="d-flex justify-content-between align-items-center mb-4">
                                            <h3 class="fw-bold mb-0">Sesiones</h3>
                                            <span class="badge badge-light" id="ecom_flow_sesiones_total">0</span>
                                        </div>
                                        <div class="ecom-flow-sessions d-grid gap-3" id="ecom_flow_sesiones"></div>
                                    </div>
                                </div>
                                <div class="col-xl-8">
                                    <div class="ecom-flow-panel p-5 mb-5">
                                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
                                            <div>
                                                <h3 class="fw-bold mb-1" id="ecom_flow_session_titulo">Sesion</h3>
                                                <span class="text-muted fs-7" id="ecom_flow_session_subtitulo">-</span>
                                            </div>
                                            <span class="badge badge-light-success" id="ecom_flow_session_estado">Anonima</span>
                                        </div>
                                        <div class="row g-3">
                                            <div class="col-md-3"><div class="ecom-flow-kpi"><span class="text-muted fs-8 text-uppercase fw-bold">Eventos</span><strong id="ecom_flow_kpi_eventos">0</strong></div></div>
                                            <div class="col-md-3"><div class="ecom-flow-kpi"><span class="text-muted fs-8 text-uppercase fw-bold">Busquedas</span><strong id="ecom_flow_kpi_busquedas">0</strong></div></div>
                                            <div class="col-md-3"><div class="ecom-flow-kpi"><span class="text-muted fs-8 text-uppercase fw-bold">Conversiones</span><strong id="ecom_flow_kpi_conversiones">0</strong></div></div>
                                            <div class="col-md-3"><div class="ecom-flow-kpi"><span class="text-muted fs-8 text-uppercase fw-bold">Timeline</span><strong id="ecom_flow_kpi_timeline">0</strong></div></div>
                                        </div>
                                    </div>

                                    <div class="ecom-flow-panel p-5">
                                        <h3 class="fw-bold mb-4">Timeline</h3>
                                        <div class="ecom-flow-timeline" id="ecom_flow_timeline"></div>
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
<script src="/assets/js/custom/apps/erp/ecommerce/analytics_flujo.js?v=20260905-flow1"></script>
</body>
</html>
