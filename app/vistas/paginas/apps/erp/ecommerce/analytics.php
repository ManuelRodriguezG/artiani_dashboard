<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../../">
    <title>Ecommerce - Analytics</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css">
    <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css">
    <!--
      Documentacion IA: Codex GPT-5, 2026-08-04.
      Proposito: dashboard interno read-only para Ecommerce / Analytics.
      Impacto: decisiones de catalogo, navegacion y conversion sin datos personales, stock exacto, ventas ni inventario.
      Contrato: consume endpoints internos protegidos; no escribe BD.
    -->
    <style>
        .ecom-an-kpi { border: 1px solid #e7e9ef; border-radius: 8px; background: #fff; padding: 16px; min-height: 104px; }
        .ecom-an-kpi__value { font-size: 1.8rem; line-height: 1; font-weight: 800; color: #181c32; letter-spacing: 0; }
        .ecom-an-kpi__label { color: #7e8299; font-size: .78rem; text-transform: uppercase; font-weight: 700; }
        .ecom-an-panel { border: 1px solid #e7e9ef; border-radius: 8px; background: #fff; }
        .ecom-an-empty { border: 1px dashed #d8dce6; border-radius: 8px; background: #fbfcfe; }
        .ecom-an-funnel { display: grid; grid-template-columns: repeat(6, minmax(110px, 1fr)); gap: 10px; }
        .ecom-an-step { border: 1px solid #dfe4ef; border-radius: 8px; padding: 12px; background: #f9fafc; min-height: 84px; }
        .ecom-an-step strong { display: block; font-size: 1.25rem; color: #181c32; }
        @media (max-width: 991px) { .ecom-an-funnel { grid-template-columns: repeat(2, minmax(120px, 1fr)); } }
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
                                <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Ecommerce / Analytics</h1>
                                <span class="text-muted">Navegacion, busquedas y conversion anonima del ecommerce publico</span>
                            </div>
                            <div class="d-flex gap-2">
                                <a class="btn btn-light" href="/ecommercePublico/control"><i class="bi bi-sliders"></i> Control</a>
                                <a class="btn btn-light-primary" href="/ecommercePublico/cotizaciones"><i class="bi bi-chat-dots"></i> Cotizaciones</a>
                                <button class="btn btn-primary" type="button" id="ecom_an_recargar"><i class="bi bi-arrow-clockwise"></i> Recargar</button>
                            </div>
                        </div>
                    </div>
                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <div class="alert alert-info d-flex align-items-start gap-3">
                                <i class="bi bi-shield-check fs-2"></i>
                                <div>
                                    <div class="fw-bold">Fase 1 read-only</div>
                                    <div>No guarda datos personales, no muestra stock exacto, no crea checkout, no toca ventas y no descuenta inventario.</div>
                                </div>
                            </div>

                            <div class="ecom-an-panel p-4 mb-5">
                                <div class="row g-3 align-items-end">
                                    <div class="col-md-3">
                                        <label class="form-label">Desde</label>
                                        <input class="form-control form-control-solid" type="date" id="ecom_an_desde">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Hasta</label>
                                        <input class="form-control form-control-solid" type="date" id="ecom_an_hasta">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Limite</label>
                                        <select class="form-select form-select-solid" id="ecom_an_limite">
                                            <option value="10">Top 10</option>
                                            <option value="20">Top 20</option>
                                            <option value="50">Top 50</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 text-md-end">
                                        <span class="badge badge-light-primary" id="ecom_an_estado">Listo</span>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-4 mb-5">
                                <div class="col-md-3"><div class="ecom-an-kpi"><div class="ecom-an-kpi__label">Sesiones</div><div class="ecom-an-kpi__value" id="ecom_an_kpi_sesiones">0</div><div class="text-muted fs-7 mt-2">Session hash anonimo.</div></div></div>
                                <div class="col-md-3"><div class="ecom-an-kpi"><div class="ecom-an-kpi__label">Eventos</div><div class="ecom-an-kpi__value" id="ecom_an_kpi_eventos">0</div><div class="text-muted fs-7 mt-2">Navegacion registrada.</div></div></div>
                                <div class="col-md-3"><div class="ecom-an-kpi"><div class="ecom-an-kpi__label">Busquedas</div><div class="ecom-an-kpi__value" id="ecom_an_kpi_busquedas">0</div><div class="text-muted fs-7 mt-2">Demanda anonima.</div></div></div>
                                <div class="col-md-3"><div class="ecom-an-kpi"><div class="ecom-an-kpi__label">WhatsApp</div><div class="ecom-an-kpi__value" id="ecom_an_kpi_whatsapp">0</div><div class="text-muted fs-7 mt-2">Aperturas estimadas.</div></div></div>
                            </div>

                            <div class="ecom-an-panel p-5 mb-5">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <div>
                                        <h3 class="fw-bold mb-1">Embudo</h3>
                                        <span class="text-muted fs-7">Visita > producto > cotizacion > dry-run > preflight > WhatsApp</span>
                                    </div>
                                </div>
                                <div class="ecom-an-funnel" id="ecom_an_embudo"></div>
                            </div>

                            <div class="row g-5 mb-5">
                                <div class="col-lg-6"><div class="ecom-an-panel p-5 h-100"><h3 class="fw-bold mb-4">URLs mas vistas</h3><div id="ecom_an_urls"></div></div></div>
                                <div class="col-lg-6"><div class="ecom-an-panel p-5 h-100"><h3 class="fw-bold mb-4">Productos mas vistos</h3><div id="ecom_an_productos_vistos"></div></div></div>
                                <div class="col-lg-6"><div class="ecom-an-panel p-5 h-100"><h3 class="fw-bold mb-4">Agregados a cotizacion</h3><div id="ecom_an_productos_cotizacion"></div></div></div>
                                <div class="col-lg-6"><div class="ecom-an-panel p-5 h-100"><h3 class="fw-bold mb-4">Busquedas frecuentes</h3><div id="ecom_an_busquedas"></div></div></div>
                                <div class="col-lg-6"><div class="ecom-an-panel p-5 h-100"><h3 class="fw-bold mb-4">Busquedas sin resultados</h3><div id="ecom_an_sin_resultados"></div></div></div>
                                <div class="col-lg-6"><div class="ecom-an-panel p-5 h-100"><h3 class="fw-bold mb-4">Interes sin conversion</h3><div id="ecom_an_interes_sin_conversion"></div></div></div>
                                <div class="col-lg-6"><div class="ecom-an-panel p-5 h-100"><h3 class="fw-bold mb-4">Mascotas consultadas</h3><div id="ecom_an_mascotas"></div></div></div>
                                <div class="col-lg-6"><div class="ecom-an-panel p-5 h-100"><h3 class="fw-bold mb-4">Necesidades consultadas</h3><div id="ecom_an_necesidades"></div></div></div>
                            </div>

                            <div class="ecom-an-empty p-5 text-center text-muted d-none" id="ecom_an_empty">
                                El esquema Ecommerce / Analytics aun no tiene tablas aplicadas o no hay datos en el rango.
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
<script src="/assets/js/custom/apps/erp/ecommerce/analytics.js?v=20260804-readonly1"></script>
</body>
</html>
