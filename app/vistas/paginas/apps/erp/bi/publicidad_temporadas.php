<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../../">
    <title>BI - Publicidad y temporadas</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css">
    <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css">
    <!--
      Documentacion IA: Codex GPT-5, 2026-08-31.
      Proposito: primer tablero Business Intelligence para publicidad por temporada.
      Impacto: analisis comercial read-only sobre historico legacy BI.
      Contrato: consume endpoints protegidos; no escribe BD.
    -->
    <style>
        .bi-kpi { border: 1px solid #e7e9ef; border-radius: 8px; background: #fff; padding: 16px; min-height: 108px; }
        .bi-kpi__value { font-size: 1.75rem; line-height: 1; font-weight: 800; color: #181c32; letter-spacing: 0; }
        .bi-kpi__label { color: #7e8299; font-size: .78rem; text-transform: uppercase; font-weight: 700; }
        .bi-panel { border: 1px solid #e7e9ef; border-radius: 8px; background: #fff; }
        .bi-empty { border: 1px dashed #d8dce6; border-radius: 8px; background: #fbfcfe; }
        .bi-calendar { display: grid; grid-template-columns: repeat(3, minmax(220px, 1fr)); gap: 12px; }
        .bi-month { border: 1px solid #dfe4ef; border-radius: 8px; padding: 14px; background: #f9fafc; min-height: 150px; }
        .bi-month__title { font-weight: 800; color: #181c32; }
        .bi-chart { min-height: 320px; }
        .bi-rec { border: 1px solid #dfe4ef; border-radius: 8px; padding: 14px; background: #fff; min-height: 116px; }
        .bi-rec__title { font-weight: 800; color: #181c32; }
        @media (max-width: 1199px) { .bi-calendar { grid-template-columns: repeat(2, minmax(220px, 1fr)); } }
        @media (max-width: 767px) { .bi-calendar { grid-template-columns: 1fr; } }
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
                                <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Business Intelligence</h1>
                                <span class="text-muted">Publicidad, busquedas historicas y temporadas comerciales</span>
                            </div>
                            <div class="d-flex gap-2">
                                <button class="btn btn-light" type="button" id="bi_diag_recargar"><i class="bi bi-database-check"></i> Diagnostico</button>
                                <button class="btn btn-primary" type="button" id="bi_pub_recargar"><i class="bi bi-arrow-clockwise"></i> Recargar</button>
                            </div>
                        </div>
                    </div>
                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <div class="bi-panel p-4 mb-5">
                                <div class="row g-3 align-items-end">
                                    <div class="col-md-3">
                                        <label class="form-label">Desde</label>
                                        <input class="form-control form-control-solid" type="date" id="bi_pub_desde">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Hasta</label>
                                        <input class="form-control form-control-solid" type="date" id="bi_pub_hasta">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Limite</label>
                                        <select class="form-select form-select-solid" id="bi_pub_limite">
                                            <option value="10">Top 10</option>
                                            <option value="20" selected>Top 20</option>
                                            <option value="50">Top 50</option>
                                            <option value="100">Top 100</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 text-md-end">
                                        <span class="badge badge-light-primary" id="bi_pub_estado">Listo</span>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-4 mb-5">
                                <div class="col-md-3"><div class="bi-kpi"><div class="bi-kpi__label">Eventos</div><div class="bi-kpi__value" id="bi_kpi_eventos">0</div><div class="text-muted fs-7 mt-2">Consumibles legacy.</div></div></div>
                                <div class="col-md-3"><div class="bi-kpi"><div class="bi-kpi__label">Productos</div><div class="bi-kpi__value" id="bi_kpi_productos">0</div><div class="text-muted fs-7 mt-2">Visitas a fichas.</div></div></div>
                                <div class="col-md-3"><div class="bi-kpi"><div class="bi-kpi__label">Busquedas</div><div class="bi-kpi__value" id="bi_kpi_busquedas">0</div><div class="text-muted fs-7 mt-2">Interes declarado.</div></div></div>
                                <div class="col-md-3"><div class="bi-kpi"><div class="bi-kpi__label">Sin resultado</div><div class="bi-kpi__value" id="bi_kpi_sin_resultado">0</div><div class="text-muted fs-7 mt-2">Oportunidad.</div></div></div>
                            </div>

                            <div class="row g-5 mb-5">
                                <div class="col-xl-8">
                                    <div class="bi-panel p-5 h-100">
                                        <h3 class="fw-bold mb-4">Tendencia mensual</h3>
                                        <div class="bi-chart" id="bi_tendencia_chart"></div>
                                        <div id="bi_tendencia_fallback"></div>
                                    </div>
                                </div>
                                <div class="col-xl-4">
                                    <div class="bi-panel p-5 h-100">
                                        <h3 class="fw-bold mb-4">Acciones historicas</h3>
                                        <div id="bi_acciones"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="bi-panel p-5 mb-5">
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
                                    <div>
                                        <h3 class="fw-bold mb-1">Recomendaciones iniciales</h3>
                                        <span class="text-muted fs-7">Prioridades para revisar antes de invertir en anuncios</span>
                                    </div>
                                </div>
                                <div class="row g-3" id="bi_recomendaciones"></div>
                            </div>

                            <div class="row g-5 mb-5">
                                <div class="col-xl-6"><div class="bi-panel p-5 h-100"><h3 class="fw-bold mb-4">Productos mas vistos</h3><div id="bi_productos_top"></div></div></div>
                                <div class="col-xl-6"><div class="bi-panel p-5 h-100"><h3 class="fw-bold mb-4">Busquedas frecuentes</h3><div id="bi_busquedas_top"></div></div></div>
                                <div class="col-xl-4"><div class="bi-panel p-5 h-100"><h3 class="fw-bold mb-4">Categorias</h3><div id="bi_categorias_top"></div></div></div>
                                <div class="col-xl-4"><div class="bi-panel p-5 h-100"><h3 class="fw-bold mb-4">Clasificaciones</h3><div id="bi_clasificaciones_top"></div></div></div>
                                <div class="col-xl-4"><div class="bi-panel p-5 h-100"><h3 class="fw-bold mb-4">Marcas</h3><div id="bi_marcas_top"></div></div></div>
                                <div class="col-xl-6"><div class="bi-panel p-5 h-100"><h3 class="fw-bold mb-4">Busquedas sin resultado</h3><div id="bi_busquedas_sin_resultado"></div></div></div>
                                <div class="col-xl-6"><div class="bi-panel p-5 h-100"><h3 class="fw-bold mb-4">Visitas por mes</h3><div id="bi_visitas_mes"></div></div></div>
                            </div>

                            <div class="bi-panel p-5 mb-5">
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
                                    <div>
                                        <h3 class="fw-bold mb-1">Calendario comercial</h3>
                                        <span class="text-muted fs-7">Meses con productos y busquedas destacadas</span>
                                    </div>
                                </div>
                                <div class="bi-calendar" id="bi_calendario"></div>
                            </div>

                            <div class="bi-panel p-5 mb-5">
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
                                    <h3 class="fw-bold mb-0">Diagnostico de fuentes</h3>
                                    <span class="badge badge-light-info" id="bi_fuente">Fuente pendiente</span>
                                </div>
                                <div id="bi_diagnostico"></div>
                            </div>

                            <div class="bi-empty p-5 text-center text-muted d-none" id="bi_empty">
                                No hay datos BI en el rango o las tablas legacy no estan disponibles en esta conexion.
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
<script src="/assets/js/custom/apps/erp/bi/publicidad_temporadas.js?v=20260831-readonly2"></script>
</body>
</html>
