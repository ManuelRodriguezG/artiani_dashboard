<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../">
    <title>Rentabilidad - incidencias de costo</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css">
    <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css">
</head>
<body id="kt_app_body" data-kt-app-layout="dark-sidebar" data-kt-app-header-fixed="true" data-kt-app-sidebar-enabled="true" data-kt-app-sidebar-fixed="true" class="app-default">
<div class="d-flex flex-column flex-root app-root" id="kt_app_root">
    <div class="app-page flex-column flex-column-fluid" id="kt_app_page">
        <?= include_once '../app/vistas/includes/header/header.php'; ?>
        <div class="app-wrapper flex-column flex-row-fluid" id="kt_app_wrapper">
            <?= include_once '../app/vistas/includes/header/sidebar.php'; ?>
            <div class="app-main flex-column flex-row-fluid" id="kt_app_main">
                <div class="d-flex flex-column flex-column-fluid">
                    <div class="app-toolbar py-3 py-lg-6">
                        <div class="app-container container-fluid d-flex flex-stack">
                            <div>
                                <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Rentabilidad - incidencias de costo</h1>
                                <span class="text-muted">Pendientes persistentes enviados por Catalogo para SKUs derivados</span>
                            </div>
                            <div class="d-flex gap-2">
                                <button class="btn btn-light-primary" id="rentabilidad_recargar" type="button"><i class="bi bi-arrow-clockwise"></i> Actualizar</button>
                            </div>
                        </div>
                    </div>
                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <div class="card mb-6">
                                <div class="card-body">
                                    <div class="row g-3 align-items-end">
                                        <div class="col-lg-8">
                                            <label class="form-label">Buscar incidencia</label>
                                            <div class="position-relative">
                                                <i class="bi bi-search fs-3 position-absolute ms-5 mt-3"></i>
                                                <input class="form-control form-control-solid ps-12" id="rentabilidad_buscar" placeholder="SKU derivado, SKU origen o texto de la incidencia">
                                            </div>
                                        </div>
                                        <div class="col-lg-4">
                                            <button class="btn btn-primary w-100" id="rentabilidad_incidencias_costos_recargar" type="button"><i class="bi bi-inbox"></i> Consultar incidencias</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card">
                                <div class="card-header border-0 pt-5">
                                    <div class="card-title">
                                        <h3 class="fw-bold fs-5 mb-0">Incidencias de costo desde Catalogo</h3>
                                    </div>
                                </div>
                                <div class="card-body pt-3" id="rentabilidad_incidencias_costos"></div>
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
<script>
window.RENTABILIDAD_VISTA = "incidencias_costos";
window.RENTABILIDAD_PERMISOS = <?= json_encode(array(
    "snapshot" => SesionSeguridad::tienePermiso("rentabilidad.snapshot")
)) ?>;
</script>
<script src="/assets/js/custom/apps/erp/rentabilidad/analisis.js?v=20260824-incidencias-costos-persistente-1"></script>
</body>
</html>
