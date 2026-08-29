<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../">
    <title>Rentabilidad - herramienta por lista</title>
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
                                <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Rentabilidad por lista</h1>
                                <span class="text-muted">Analiza precios reales por lista/canal con costos vigentes e impuestos</span>
                            </div>
                            <div class="d-flex gap-2">
                                <a class="btn btn-light" href="/comercial/listas_precios"><i class="bi bi-tags"></i> Listas</a>
                                <button class="btn btn-light-primary" id="rentabilidad_recargar" type="button"><i class="bi bi-arrow-clockwise"></i> Actualizar</button>
                            </div>
                        </div>
                    </div>
                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <div class="card mb-6">
                                <div class="card-body">
                                    <div class="row g-3 align-items-end">
                                        <div class="col-xl-3 col-lg-4">
                                            <label class="form-label">Lista de precios</label>
                                            <select class="form-select form-select-solid" id="rentabilidad_lista_precio"></select>
                                        </div>
                                        <div class="col-xl-3 col-lg-4">
                                            <label class="form-label">Buscar SKU</label>
                                            <div class="position-relative">
                                                <i class="bi bi-search fs-3 position-absolute ms-5 mt-3"></i>
                                                <input class="form-control form-control-solid ps-12" id="rentabilidad_buscar" placeholder="SKU o producto">
                                            </div>
                                        </div>
                                        <div class="col-xl-2 col-lg-4">
                                            <label class="form-label">Riesgo</label>
                                            <select class="form-select form-select-solid" id="rentabilidad_riesgo">
                                                <option value="">Todos</option>
                                                <option value="perdida">Perdida</option>
                                                <option value="margen_bajo">Margen bajo</option>
                                                <option value="incompleto">Incompleto</option>
                                                <option value="rentable">Rentable</option>
                                            </select>
                                        </div>
                                        <div class="col-xl-1 col-6">
                                            <label class="form-label">Gasto %</label>
                                            <input class="form-control form-control-solid" id="rentabilidad_gasto" inputmode="decimal" value="0">
                                        </div>
                                        <div class="col-xl-1 col-6">
                                            <label class="form-label">Com. %</label>
                                            <input class="form-control form-control-solid" id="rentabilidad_comision" inputmode="decimal" value="0">
                                        </div>
                                        <div class="col-xl-1 col-6">
                                            <label class="form-label">Margen %</label>
                                            <input class="form-control form-control-solid" id="rentabilidad_objetivo" inputmode="decimal" value="20">
                                        </div>
                                        <div class="col-xl-1 col-6">
                                            <label class="form-label">Ajuste %</label>
                                            <input class="form-control form-control-solid" id="rentabilidad_ajuste" inputmode="decimal" value="0">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card mb-6">
                                <div class="card-header border-0 pt-5">
                                    <div class="card-title">
                                        <h3 class="fw-bold fs-5 mb-0">Resumen de lista</h3>
                                    </div>
                                </div>
                                <div class="card-body pt-3" id="rentabilidad_herramienta_resumen"></div>
                            </div>

                            <div class="card mb-6">
                                <div class="card-header border-0 pt-5">
                                    <div class="card-title">
                                        <h3 class="fw-bold fs-5 mb-0">Atencion de costos</h3>
                                    </div>
                                </div>
                                <div class="card-body pt-0">
                                    <div id="rentabilidad_herramienta_atencion_resumen" class="mb-3"></div>
                                    <div class="table-responsive">
                                        <table class="table align-middle table-row-dashed gy-3 mb-0">
                                            <thead>
                                            <tr class="text-muted fw-bold fs-8 text-uppercase">
                                                <th>SKU sin costo</th>
                                                <th>Candidato origen</th>
                                                <th>Costo origen</th>
                                                <th>Accion</th>
                                                <th>Siguiente paso</th>
                                            </tr>
                                            </thead>
                                            <tbody id="rentabilidad_herramienta_atencion_costos"></tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="card mb-6">
                                <div class="card-header border-0 pt-5">
                                    <div class="card-title">
                                        <h3 class="fw-bold fs-5 mb-0">Propuestas read-only</h3>
                                    </div>
                                </div>
                                <div class="card-body pt-0">
                                    <div class="table-responsive">
                                        <table class="table align-middle table-row-dashed gy-3 mb-0">
                                            <thead>
                                            <tr class="text-muted fw-bold fs-8 text-uppercase">
                                                <th>SKU</th>
                                                <th class="text-end">Actual</th>
                                                <th class="text-end">Sugerido</th>
                                                <th class="text-end">Diferencia</th>
                                                <th>Accion</th>
                                                <th>Siguiente paso</th>
                                            </tr>
                                            </thead>
                                            <tbody id="rentabilidad_herramienta_propuestas"></tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="card">
                                <div class="card-header border-0 pt-5">
                                    <div class="card-title">
                                        <h3 class="fw-bold fs-5 mb-0">Productos de la lista</h3>
                                    </div>
                                </div>
                                <div class="card-body pt-0">
                                    <div class="table-responsive">
                                        <table class="table align-middle table-row-dashed gy-3 mb-0">
                                            <thead>
                                            <tr class="text-muted fw-bold fs-8 text-uppercase">
                                                <th>SKU</th>
                                                <th class="text-end">Precio lista</th>
                                                <th class="text-end">Costo</th>
                                                <th class="text-end">Margen</th>
                                                <th class="text-end">Utilidad</th>
                                                <th class="text-end">Minimo</th>
                                                <th>Estado</th>
                                                <th>Siguiente paso</th>
                                            </tr>
                                            </thead>
                                            <tbody id="rentabilidad_herramienta_items"></tbody>
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
window.RENTABILIDAD_VISTA = "herramienta";
window.RENTABILIDAD_PERMISOS = <?= json_encode(array(
    "snapshot" => SesionSeguridad::tienePermiso("rentabilidad.snapshot")
)) ?>;
</script>
<script src="/assets/js/custom/apps/erp/rentabilidad/analisis.js?v=20260829-herramienta-atencion-2"></script>
</body>
</html>
