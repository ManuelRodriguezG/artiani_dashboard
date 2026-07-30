<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../../">
    <title>Analisis de abastecimiento</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet">
    <link href="assets/css/style.bundle.css" rel="stylesheet">
</head>
<body id="kt_app_body" data-kt-app-layout="dark-sidebar" data-kt-app-header-fixed="true" data-kt-app-sidebar-enabled="true" data-kt-app-sidebar-fixed="true" class="app-default">
<div class="d-flex flex-column flex-root app-root">
    <div class="app-page flex-column flex-column-fluid">
        <?= include_once '../app/vistas/includes/header/header.php'; ?>
        <div class="app-wrapper flex-column flex-row-fluid">
            <?= include_once '../app/vistas/includes/header/sidebar.php'; ?>
            <main class="app-main flex-column flex-row-fluid">
                <div class="app-toolbar py-3 py-lg-6">
                    <div class="app-container container-fluid d-flex flex-stack flex-wrap gap-3">
                        <div>
                            <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Analisis de abastecimiento</h1>
                            <span class="text-muted">Comparativo read-only de proveedores por SKU</span>
                        </div>
                        <div class="d-flex gap-2">
                            <a class="btn btn-light" href="/proveedor/mostrar_proveedores_erp">
                                <i class="bi bi-arrow-left"></i> Proveedores
                            </a>
                            <a class="btn btn-light-info" href="/proveedor/manual_erp">
                                <i class="bi bi-question-circle"></i> Manual
                            </a>
                        </div>
                    </div>
                </div>
                <div class="app-content flex-column-fluid">
                    <div class="app-container container-fluid">
                        <div class="alert alert-info d-flex align-items-start gap-3 mb-6">
                            <i class="bi bi-shield-check fs-2"></i>
                            <div>
                                <div class="fw-bold">Primera fase sin escrituras</div>
                                <div class="text-muted">Consulta contratos proveedor-SKU, costos vigentes, listas y costo ultimo. No cambia proveedor preferido ni crea ordenes.</div>
                            </div>
                        </div>

                        <div class="card mb-6">
                            <div class="card-body">
                                <div class="row g-3 align-items-end">
                                    <div class="col-lg-7">
                                        <label class="form-label fw-semibold">SKU, codigo, producto o proveedor</label>
                                        <div class="position-relative">
                                            <i class="bi bi-search fs-3 position-absolute ms-5 mt-3"></i>
                                            <input class="form-control form-control-solid ps-12" id="abastecimiento_termino" maxlength="120" placeholder="Buscar alternativas de compra">
                                        </div>
                                    </div>
                                    <div class="col-sm-6 col-lg-2">
                                        <label class="form-label fw-semibold">Mostrar</label>
                                        <select class="form-select form-select-solid" id="abastecimiento_limite">
                                            <option value="40">40</option>
                                            <option value="80" selected>80</option>
                                            <option value="120">120</option>
                                            <option value="200">200</option>
                                        </select>
                                    </div>
                                    <div class="col-sm-6 col-lg-2">
                                        <div class="form-check form-switch form-check-custom form-check-solid mt-8">
                                            <input class="form-check-input" type="checkbox" id="abastecimiento_solo_multiples">
                                            <label class="form-check-label" for="abastecimiento_solo_multiples">Solo multiples</label>
                                        </div>
                                    </div>
                                    <div class="col-lg-1 d-grid">
                                        <button class="btn btn-primary" type="button" id="abastecimiento_buscar">
                                            <i class="bi bi-arrow-right-circle"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-4 mb-6" id="abastecimiento_resumen">
                            <div class="col-12 text-muted">Busca un SKU o producto para comparar proveedores.</div>
                        </div>

                        <div id="abastecimiento_resultados" class="d-flex flex-column gap-6"></div>
                        <div class="alert alert-danger d-none" id="abastecimiento_error"></div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</div>
<script src="assets/plugins/global/plugins.bundle.js"></script>
<script src="assets/js/scripts.bundle.js"></script>
<script src="/assets/js/custom/apps/erp/proveedores/analisis_abastecimiento.js?v=20260729-1"></script>
</body>
</html>
