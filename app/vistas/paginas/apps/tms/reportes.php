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
      Proposito: pantalla base para reportes TMS Delivery.
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
                            <div class="row g-4">
                                <div class="col-lg-3"><div class="card card-flush h-100"><div class="card-body"><div class="text-muted fs-8 text-uppercase">Completas</div><div class="fs-2 fw-bold">0</div></div></div></div>
                                <div class="col-lg-3"><div class="card card-flush h-100"><div class="card-body"><div class="text-muted fs-8 text-uppercase">Express</div><div class="fs-2 fw-bold">0</div></div></div></div>
                                <div class="col-lg-3"><div class="card card-flush h-100"><div class="card-body"><div class="text-muted fs-8 text-uppercase">No entregadas</div><div class="fs-2 fw-bold">0</div></div></div></div>
                                <div class="col-lg-3"><div class="card card-flush h-100"><div class="card-body"><div class="text-muted fs-8 text-uppercase">Pendiente cliente</div><div class="fs-2 fw-bold">0</div></div></div></div>
                            </div>
                            <div class="card card-flush mt-4">
                                <div class="card-body text-muted">Superficie preparada para reportes read-only despues de aplicar esquema y registrar servicios.</div>
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
</body>
</html>
