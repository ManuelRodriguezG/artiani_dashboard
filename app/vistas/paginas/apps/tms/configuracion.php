<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../">
    <title>TMS Configuracion</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css">
    <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css">
    <!--
      Documentacion IA: Codex GPT-5, 2026-07-24.
      Proposito: pantalla base para configuracion y politicas logisticas TMS.
      Impacto: TMS Delivery; prepara reglas de servicio sin crear productos de envio.
      Contrato: vista protegida por `tms.autorizar`; sin escritura en fase actual.
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
                            <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Configuracion delivery</h1>
                            <span class="text-muted">Zonas, reglas express, cortesias y condiciones logisticas</span>
                        </div>
                    </div>
                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <div id="tms_config_alerta" class="mb-4"></div>
                            <div class="row g-4">
                                <div class="col-lg-4"><div class="card card-flush h-100"><div class="card-body"><div class="fw-bold mb-2">Tipos de servicio</div><div id="tms_config_tipos" class="d-flex flex-wrap gap-2"></div></div></div></div>
                                <div class="col-lg-4"><div class="card card-flush h-100"><div class="card-body"><div class="fw-bold mb-2">Cobro logistico</div><div id="tms_config_cobros" class="d-flex flex-wrap gap-2"></div></div></div></div>
                                <div class="col-lg-4"><div class="card card-flush h-100"><div class="card-body"><div class="fw-bold mb-2">Prioridades</div><div id="tms_config_prioridades" class="d-flex flex-wrap gap-2"></div></div></div></div>
                            </div>
                            <div class="row g-4 mt-0">
                                <div class="col-lg-5">
                                    <div class="card card-flush h-100">
                                        <div class="card-header align-items-center">
                                            <h3 class="card-title">Contrato del modulo</h3>
                                            <button type="button" class="btn btn-sm btn-light-primary" id="tms_config_refrescar">
                                                <i class="bi bi-arrow-clockwise"></i> Actualizar
                                            </button>
                                        </div>
                                        <div class="card-body" id="tms_config_contrato"></div>
                                    </div>
                                </div>
                                <div class="col-lg-7">
                                    <div class="card card-flush h-100">
                                        <div class="card-header"><h3 class="card-title">Acciones operativas</h3></div>
                                        <div class="card-body p-0">
                                            <div class="table-responsive">
                                                <table class="table table-row-dashed align-middle mb-0">
                                                    <thead>
                                                        <tr class="fw-bold text-muted">
                                                            <th class="ps-6">Accion</th>
                                                            <th>Permiso</th>
                                                            <th>Requiere</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="tms_config_acciones">
                                                        <tr><td colspan="3" class="text-center text-muted py-8">Cargando contrato TMS...</td></tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
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
<script src="/assets/js/custom/apps/tms/configuracion.js?v=20260724-1"></script>
</body>
</html>
