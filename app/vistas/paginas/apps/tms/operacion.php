<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../">
    <title>TMS Operacion</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css">
    <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css">
    <!--
      Documentacion IA: Codex GPT-5, 2026-07-24.
      Proposito: pantalla base para operacion y rutas TMS.
      Impacto: TMS Delivery; prepara estados logisticos sin tocar Ventas, garantias ni inventario.
      Contrato: vista protegida por `tms.operar`; sin escritura en fase actual.
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
                            <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Operacion TMS</h1>
                            <span class="text-muted">Programacion, salida, ruta y cierre logistico</span>
                        </div>
                    </div>
                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <div id="tms_operacion_alerta" class="mb-4"></div>
                            <div class="row g-4">
                                <div class="col-lg-3"><div class="card card-flush h-100"><div class="card-body"><div class="text-muted fs-8 text-uppercase">Programadas</div><div class="fs-2 fw-bold" id="tms_op_programadas">0</div></div></div></div>
                                <div class="col-lg-3"><div class="card card-flush h-100"><div class="card-body"><div class="text-muted fs-8 text-uppercase">Listas</div><div class="fs-2 fw-bold" id="tms_op_listas">0</div></div></div></div>
                                <div class="col-lg-3"><div class="card card-flush h-100"><div class="card-body"><div class="text-muted fs-8 text-uppercase">En ruta</div><div class="fs-2 fw-bold" id="tms_op_ruta">0</div></div></div></div>
                                <div class="col-lg-3"><div class="card card-flush h-100"><div class="card-body"><div class="text-muted fs-8 text-uppercase">Incidencias</div><div class="fs-2 fw-bold" id="tms_op_incidencias">0</div></div></div></div>
                            </div>
                            <div class="card card-flush mt-4">
                                <div class="card-header align-items-center">
                                    <h3 class="card-title">Cola operativa</h3>
                                    <button type="button" class="btn btn-sm btn-light-primary" id="tms_operacion_refrescar">
                                        <i class="bi bi-arrow-clockwise"></i> Actualizar
                                    </button>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-row-dashed align-middle mb-0">
                                            <thead>
                                                <tr class="fw-bold text-muted">
                                                    <th class="ps-6">Servicio</th>
                                                    <th>Cliente</th>
                                                    <th>Ventana</th>
                                                    <th>Estado</th>
                                                    <th>Resultado</th>
                                                </tr>
                                            </thead>
                                            <tbody id="tms_operacion_body">
                                                <tr><td colspan="5" class="text-center text-muted py-8">Cargando operacion TMS...</td></tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
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
<script src="/assets/js/custom/apps/tms/operacion.js?v=20260724-1"></script>
</body>
</html>
