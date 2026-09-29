<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../">
    <title>Rentabilidad - envios nacionales</title>
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
                                <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Envios nacionales</h1>
                                <span class="text-muted">Simula envio barato o gratis con precios, costos y gasto fijo del local</span>
                            </div>
                            <button class="btn btn-light-primary" id="rentabilidad_envios_recargar" type="button"><i class="bi bi-arrow-clockwise"></i> Actualizar</button>
                        </div>
                    </div>
                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <div class="card mb-6">
                                <div class="card-body">
                                    <div class="row g-3 align-items-end">
                                        <div class="col-xl-3 col-lg-4">
                                            <label class="form-label">Lista de precios</label>
                                            <select class="form-select form-select-solid" id="rentabilidad_envios_lista_precio"></select>
                                        </div>
                                        <div class="col-xl-3 col-lg-4">
                                            <label class="form-label">Buscar SKU</label>
                                            <div class="position-relative">
                                                <i class="bi bi-search fs-3 position-absolute ms-5 mt-3"></i>
                                                <input class="form-control form-control-solid ps-12" id="rentabilidad_envios_buscar" placeholder="SKU o producto">
                                            </div>
                                        </div>
                                        <div class="col-xl-2 col-md-4 col-6">
                                            <label class="form-label">Gasto fijo %</label>
                                            <input class="form-control form-control-solid" id="rentabilidad_envios_gasto" inputmode="decimal" value="23">
                                        </div>
                                        <div class="col-xl-2 col-md-4 col-6">
                                            <label class="form-label">Costo envio</label>
                                            <input class="form-control form-control-solid" id="rentabilidad_envios_costo" inputmode="decimal" value="180">
                                        </div>
                                        <div class="col-xl-2 col-md-4 col-6">
                                            <label class="form-label">Cobrar envio</label>
                                            <input class="form-control form-control-solid" id="rentabilidad_envios_cobrado" inputmode="decimal" value="0">
                                        </div>
                                        <div class="col-xl-2 col-md-4 col-6">
                                            <label class="form-label">Gratis desde</label>
                                            <input class="form-control form-control-solid" id="rentabilidad_envios_gratis_desde" inputmode="decimal" value="0">
                                        </div>
                                        <div class="col-xl-2 col-md-4 col-6">
                                            <label class="form-label">Comision %</label>
                                            <input class="form-control form-control-solid" id="rentabilidad_envios_comision" inputmode="decimal" value="0">
                                        </div>
                                        <div class="col-xl-2 col-md-4 col-6">
                                            <label class="form-label">Margen meta %</label>
                                            <input class="form-control form-control-solid" id="rentabilidad_envios_margen" inputmode="decimal" value="15">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card mb-6">
                                <div class="card-header border-0 pt-5">
                                    <div class="card-title">
                                        <h3 class="fw-bold fs-5 mb-0">Resumen nacional</h3>
                                    </div>
                                </div>
                                <div class="card-body pt-3" id="rentabilidad_envios_resumen"></div>
                            </div>

                            <div class="card mb-6">
                                <div class="card-header border-0 pt-5">
                                    <div class="card-title">
                                        <h3 class="fw-bold fs-5 mb-0">Politica sugerida</h3>
                                    </div>
                                </div>
                                <div class="card-body pt-3" id="rentabilidad_envios_recomendacion"></div>
                            </div>

                            <div class="card">
                                <div class="card-header border-0 pt-5">
                                    <div class="card-title">
                                        <h3 class="fw-bold fs-5 mb-0">Productos evaluados</h3>
                                    </div>
                                </div>
                                <div class="card-body pt-0">
                                    <div class="table-responsive">
                                        <table class="table align-middle table-row-dashed gy-3 mb-0">
                                            <thead>
                                            <tr class="text-muted fw-bold fs-8 text-uppercase">
                                                <th>SKU</th>
                                                <th class="text-end">Publico</th>
                                                <th class="text-end">Costo</th>
                                                <th class="text-end">Utilidad gratis</th>
                                                <th class="text-end">Min. gratis</th>
                                                <th>Decision</th>
                                                <th>Siguiente paso</th>
                                            </tr>
                                            </thead>
                                            <tbody id="rentabilidad_envios_items"></tbody>
                                        </table>
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
<script>
window.RENTABILIDAD_VISTA = "envios_nacionales";
</script>
<script src="/assets/js/custom/apps/erp/rentabilidad/analisis.js?v=20260928-envios-nacionales"></script>
</body>
</html>
