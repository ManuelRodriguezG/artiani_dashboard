<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../">
    <title>Planeaciones de preparacion</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css">
    <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css">
    <style>
        @media print {
            body * { visibility: hidden; }
            #alm_plan_hoja, #alm_plan_hoja * { visibility: visible; }
            #alm_plan_hoja { position: absolute; inset: 0; width: 100%; }
            .alm-plan-no-print { display: none !important; }
        }
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
                    <div class="app-toolbar py-3 py-lg-6 alm-plan-no-print">
                        <div class="app-container container-fluid d-flex flex-stack">
                            <div>
                                <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Planeaciones de preparacion</h1>
                                <span class="text-muted">Escenarios por producto basados en recetas de Catalogo</span>
                            </div>
                            <div class="d-flex gap-2">
                                <a class="btn btn-light-primary" href="/almacen/preparacion_empaque"><i class="bi bi-box-seam"></i> Ejecutar preparacion</a>
                                <button class="btn btn-primary" id="alm_plan_imprimir" type="button"><i class="bi bi-printer"></i> Imprimir hoja</button>
                            </div>
                        </div>
                    </div>
                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <div class="alert alert-light-primary border border-primary-subtle alm-plan-no-print">
                                Esta pantalla es para consultar recetas y armar planeaciones. No usa almacen, no consulta existencias y no afecta inventario.
                            </div>

                            <div class="row g-5 alm-plan-no-print">
                                <div class="col-xl-4">
                                    <div class="card mb-5">
                                        <div class="card-header">
                                            <div class="card-title">
                                                <h3 class="fw-bold m-0">Producto</h3>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-4">
                                                <label class="form-label">Buscar producto/SKU origen</label>
                                                <select class="form-select form-select-solid" id="alm_plan_producto" data-control="select2" data-placeholder="Selecciona producto"></select>
                                            </div>
                                            <div class="mb-4">
                                                <label class="form-label">Nombre de planeacion</label>
                                                <input class="form-control form-control-solid" id="alm_plan_nombre" type="text" placeholder="Ej. Rotacion chica">
                                            </div>
                                            <div class="mb-4">
                                                <label class="form-label">Cantidad base a distribuir</label>
                                                <input class="form-control form-control-solid" id="alm_plan_cantidad_base" type="text" inputmode="decimal" value="1">
                                            </div>
                                            <div class="mb-4">
                                                <label class="form-label">Responsable</label>
                                                <input class="form-control form-control-solid" id="alm_plan_responsable" type="text" placeholder="Nombre">
                                            </div>
                                            <div class="d-flex flex-wrap gap-2">
                                                <button class="btn btn-primary" id="alm_plan_guardar" type="button"><i class="bi bi-save"></i> Guardar</button>
                                                <button class="btn btn-light" id="alm_plan_nueva" type="button"><i class="bi bi-plus-circle"></i> Nueva</button>
                                            </div>
                                            <input type="hidden" id="alm_plan_id">
                                        </div>
                                    </div>

                                    <div class="card">
                                        <div class="card-header">
                                            <div class="card-title">
                                                <h3 class="fw-bold m-0">Planeaciones guardadas</h3>
                                            </div>
                                        </div>
                                        <div class="card-body" id="alm_plan_guardadas">Sin planeaciones.</div>
                                    </div>
                                </div>

                                <div class="col-xl-8">
                                    <div class="card mb-5">
                                        <div class="card-header">
                                            <div class="card-title">
                                                <h3 class="fw-bold m-0">Recetas detectadas</h3>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <div class="row g-4" id="alm_plan_recetas"></div>
                                        </div>
                                    </div>

                                    <div class="card mb-5">
                                        <div class="card-header">
                                            <div class="card-title">
                                                <h3 class="fw-bold m-0">Escenarios sugeridos</h3>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <div class="row g-4" id="alm_plan_escenarios"></div>
                                        </div>
                                    </div>

                                    <div class="card mb-5">
                                        <div class="card-header">
                                            <div class="card-title">
                                                <h3 class="fw-bold m-0">Conformacion</h3>
                                            </div>
                                            <div class="card-toolbar">
                                                <button class="btn btn-sm btn-light" id="alm_plan_limpiar" type="button"><i class="bi bi-eraser"></i> Limpiar</button>
                                            </div>
                                        </div>
                                        <div class="card-body pt-0">
                                            <div class="table-responsive">
                                                <table class="table align-middle table-row-dashed gy-4 mb-0" style="min-width: 980px;">
                                                    <thead>
                                                        <tr class="text-muted fw-bold fs-7 text-uppercase">
                                                            <th>Presentacion</th>
                                                            <th class="text-end">Unidades</th>
                                                            <th class="text-end">Consumo unitario</th>
                                                            <th class="text-end">Consumo total</th>
                                                            <th class="text-end">Participacion</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="alm_plan_body"></tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card" id="alm_plan_hoja">
                                <div class="card-header">
                                    <div>
                                        <h3 class="fw-bold mb-1">Hoja de preparacion</h3>
                                        <div class="text-muted fs-7" id="alm_plan_fecha"></div>
                                    </div>
                                    <div class="card-toolbar">
                                        <span class="badge badge-light-info">Planeacion</span>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div id="alm_plan_hoja_contenido" class="text-muted">Selecciona un producto para generar la hoja.</div>
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
<script src="/assets/js/custom/apps/erp/almacen/planeacion_preparacion/planeacion_preparacion.js?v=20261001-2"></script>
</body>
</html>
