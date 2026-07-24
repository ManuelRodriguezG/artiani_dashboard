<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../">
    <title>TMS Reportes</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css">
    <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css">
    <!--
      Documentacion IA: Codex GPT-5, 2026-07-24.
      Proposito: pantalla read-only para reportes TMS Delivery.
      Impacto: TMS Delivery; mide desempeno logistico sin recalcular ventas.
      Contrato: vista protegida por `tms.reportes`; read-only.
    -->
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
                        <div class="app-container container-fluid">
                            <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Reportes delivery</h1>
                            <span class="text-muted">Cumplimiento, express, zonas e incidencias</span>
                        </div>
                    </div>
                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <div id="tms_reportes_alerta" class="mb-4"></div>
                            <div class="card card-flush mb-4">
                                <div class="card-body d-flex flex-wrap gap-3 align-items-end">
                                    <div>
                                        <label class="form-label">Desde</label>
                                        <input type="date" class="form-control form-control-sm" id="tms_reportes_desde">
                                    </div>
                                    <div>
                                        <label class="form-label">Hasta</label>
                                        <input type="date" class="form-control form-control-sm" id="tms_reportes_hasta">
                                    </div>
                                    <button type="button" class="btn btn-sm btn-primary" id="tms_reportes_refrescar">
                                        <i class="bi bi-arrow-clockwise"></i> Actualizar
                                    </button>
                                </div>
                            </div>
                            <div class="row g-4">
                                <div class="col-lg-3"><div class="card card-flush h-100"><div class="card-body"><div class="text-muted fs-8 text-uppercase">Servicios</div><div class="fs-2 fw-bold" id="tms_rep_total">0</div></div></div></div>
                                <div class="col-lg-3"><div class="card card-flush h-100"><div class="card-body"><div class="text-muted fs-8 text-uppercase">Completas</div><div class="fs-2 fw-bold" id="tms_rep_completas">0</div></div></div></div>
                                <div class="col-lg-3"><div class="card card-flush h-100"><div class="card-body"><div class="text-muted fs-8 text-uppercase">Express</div><div class="fs-2 fw-bold" id="tms_rep_express">0</div></div></div></div>
                                <div class="col-lg-3"><div class="card card-flush h-100"><div class="card-body"><div class="text-muted fs-8 text-uppercase">No entregadas</div><div class="fs-2 fw-bold" id="tms_rep_no_entregadas">0</div></div></div></div>
                            </div>
                            <div class="row g-4 mt-0">
                                <div class="col-lg-3"><div class="card card-flush h-100"><div class="card-body"><div class="text-muted fs-8 text-uppercase">Pendiente cliente</div><div class="fs-2 fw-bold" id="tms_rep_pendiente_cliente">0</div></div></div></div>
                                <div class="col-lg-3"><div class="card card-flush h-100"><div class="card-body"><div class="text-muted fs-8 text-uppercase">Ingresos envio</div><div class="fs-2 fw-bold" id="tms_rep_ingresos">$0.00</div></div></div></div>
                                <div class="col-lg-3"><div class="card card-flush h-100"><div class="card-body"><div class="text-muted fs-8 text-uppercase">Bonificado</div><div class="fs-2 fw-bold" id="tms_rep_bonificado">$0.00</div></div></div></div>
                                <div class="col-lg-3"><div class="card card-flush h-100"><div class="card-body"><div class="text-muted fs-8 text-uppercase">Tiempo prom.</div><div class="fs-2 fw-bold" id="tms_rep_tiempo">0 min</div></div></div></div>
                            </div>
                            <div class="row g-4 mt-0">
                                <div class="col-lg-4"><div class="card card-flush h-100"><div class="card-header"><h3 class="card-title">Por tipo</h3></div><div class="card-body" id="tms_rep_tipo"></div></div></div>
                                <div class="col-lg-4"><div class="card card-flush h-100"><div class="card-header"><h3 class="card-title">Por resultado</h3></div><div class="card-body" id="tms_rep_resultado"></div></div></div>
                                <div class="col-lg-4"><div class="card card-flush h-100"><div class="card-header"><h3 class="card-title">Por zona</h3></div><div class="card-body" id="tms_rep_zona"></div></div></div>
                            </div>
                        </div>
                    </div>
                </div>
                <?= include_once '../app/vistas/includes/footer/footer.php'; ?>
            </div>
        </div>
    </div>
</div>
<script src="assets/plugins/global/plugins.bundle.js"></script>
<script src="assets/js/scripts.bundle.js"></script>
<script src="/assets/js/custom/apps/tms/reportes.js?v=20260724-1"></script>
</body>
</html>
