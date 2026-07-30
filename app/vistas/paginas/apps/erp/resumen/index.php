<!DOCTYPE html>
<html lang="es">
<head>
    <base href="/">
    <title>Resumen ERP</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css">
    <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css">
    <!--
      Documentacion IA: Codex GPT-5, 2026-07-29.
      Proposito: primera pantalla operativa del ERP con alertas, KPIs y accesos por permiso.
      Impacto: Inicio/Resumen; no ejecuta acciones de negocio ni consulta legacy directamente.
      Contrato: renderiza datos de /inicio/resumen_erp y tolera modulos sin esquema.
    -->
    <style>
        .resumen-kpi { border: 1px solid #e6e8ee; border-radius: 8px; background: #fff; min-height: 104px; }
        .resumen-card { border: 1px solid #e6e8ee; border-radius: 8px; background: #fff; }
        .resumen-action { border: 1px solid #e6e8ee; border-radius: 8px; background: #f8f9fb; }
        .resumen-alert-row { border-bottom: 1px dashed #e1e4ec; }
        .resumen-alert-row:last-child { border-bottom: 0; }
        .resumen-skeleton { min-height: 120px; }
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
                                <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Resumen</h1>
                                <span class="text-muted">Pendientes, ventas, compras, almacen e inventario para atender hoy</span>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge badge-light" id="resumen_fecha">Actualizando...</span>
                                <button class="btn btn-light-primary" type="button" id="resumen_refrescar"><i class="bi bi-arrow-clockwise"></i> Actualizar</button>
                            </div>
                        </div>
                    </div>
                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <div id="resumen_alerta" class="mb-4"></div>

                            <div class="row g-3 mb-5">
                                <div class="col-sm-6 col-xl-2"><div class="resumen-kpi p-4"><div class="text-muted fs-8 text-uppercase">Pendientes</div><div class="fw-bold fs-2" id="kpi_pendientes">0</div><div class="text-muted fs-8">Visibles por permiso</div></div></div>
                                <div class="col-sm-6 col-xl-2"><div class="resumen-kpi p-4"><div class="text-muted fs-8 text-uppercase">Criticas</div><div class="fw-bold fs-2 text-danger" id="kpi_criticas">0</div><div class="text-muted fs-8">Requieren atencion</div></div></div>
                                <div class="col-sm-6 col-xl-2"><div class="resumen-kpi p-4"><div class="text-muted fs-8 text-uppercase">Ventas hoy</div><div class="fw-bold fs-2" id="kpi_ventas_hoy">0</div><div class="text-muted fs-8" id="kpi_total_hoy">$0.00</div></div></div>
                                <div class="col-sm-6 col-xl-2"><div class="resumen-kpi p-4"><div class="text-muted fs-8 text-uppercase">Turnos</div><div class="fw-bold fs-2" id="kpi_turnos">0</div><div class="text-muted fs-8">Abiertos</div></div></div>
                                <div class="col-sm-6 col-xl-2"><div class="resumen-kpi p-4"><div class="text-muted fs-8 text-uppercase">Por recibir</div><div class="fw-bold fs-2" id="kpi_por_recibir">0</div><div class="text-muted fs-8">OC enviadas/parciales</div></div></div>
                                <div class="col-sm-6 col-xl-2"><div class="resumen-kpi p-4"><div class="text-muted fs-8 text-uppercase">Inventario</div><div class="fw-bold fs-2" id="kpi_inv_pendientes">0</div><div class="text-muted fs-8">Pendientes POS/stock</div></div></div>
                            </div>

                            <div class="row g-5 mb-5">
                                <div class="col-xl-7">
                                    <div class="resumen-card p-5 h-100">
                                        <div class="d-flex flex-stack mb-4">
                                            <div>
                                                <div class="fw-bold fs-5">Atender ahora</div>
                                                <div class="text-muted fs-7">Alertas operativas activas</div>
                                            </div>
                                            <a class="btn btn-sm btn-light" href="/sistema/notificaciones">Ver bandeja</a>
                                        </div>
                                        <div id="resumen_alertas" class="resumen-skeleton text-muted">Cargando alertas...</div>
                                    </div>
                                </div>
                                <div class="col-xl-5">
                                    <div class="resumen-card p-5 h-100">
                                        <div class="fw-bold fs-5 mb-1">Accesos rapidos</div>
                                        <div class="text-muted fs-7 mb-4">Acciones disponibles por tus permisos</div>
                                        <div class="row g-3" id="resumen_acciones"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-4" id="resumen_modulos">
                                <div class="col-12 text-muted">Cargando modulos...</div>
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
<script src="/assets/js/custom/apps/erp/resumen/resumen.js?v=20260729-1"></script>
</body>
</html>
