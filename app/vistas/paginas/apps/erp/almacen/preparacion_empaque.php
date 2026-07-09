<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../">
    <title>Preparacion y empaque</title>
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
                                <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Preparacion/Empaque</h1>
                                <span class="text-muted">Conversion fisica de una existencia origen a presentaciones vendibles</span>
                            </div>
                            <div class="d-flex gap-2">
                                <a class="btn btn-light-primary" href="/almacen/etiquetado"><i class="bi bi-upc-scan"></i> Etiquetado</a>
                                <button class="btn btn-primary" id="alm_prep_btn_nuevo" type="button"><i class="bi bi-plus-circle"></i> Nuevo borrador</button>
                            </div>
                        </div>
                    </div>
                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <div class="row g-5">
                                <div class="col-xl-4">
                                    <div class="card">
                                        <div class="card-header">
                                            <div class="card-title">
                                                <h3 class="fw-bold m-0">Borrador</h3>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <input type="hidden" id="alm_prep_id">
                                            <div class="mb-4">
                                                <label class="form-label">Almacen</label>
                                                <select class="form-select form-select-solid" id="alm_prep_almacen"></select>
                                            </div>
                                            <div class="mb-4">
                                                <label class="form-label">SKU origen</label>
                                                <select class="form-select form-select-solid" id="alm_prep_sku_base" data-control="select2" data-placeholder="Selecciona SKU origen"></select>
                                            </div>
                                            <div class="mb-4">
                                                <label class="form-label">Presentacion a preparar</label>
                                                <select class="form-select form-select-solid" id="alm_prep_presentacion"></select>
                                            </div>
                                            <div class="mb-4">
                                                <label class="form-label">Unidades a preparar</label>
                                                <input class="form-control form-control-solid" id="alm_prep_unidades" type="number" min="1" step="1" value="1">
                                            </div>
                                            <div class="rounded border p-4 mb-4 bg-light" id="alm_prep_resumen">Selecciona una presentacion.</div>
                                            <div class="mb-4">
                                                <label class="form-label">Observaciones</label>
                                                <textarea class="form-control form-control-solid" id="alm_prep_observaciones" rows="3"></textarea>
                                            </div>
                                            <div class="d-flex gap-2">
                                                <button class="btn btn-primary" id="alm_prep_guardar" type="button"><i class="bi bi-save"></i> Guardar</button>
                                                <button class="btn btn-light-success" id="alm_prep_confirmar" type="button" disabled><i class="bi bi-check2-circle"></i> Confirmar</button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card mt-5">
                                        <div class="card-header">
                                            <div class="card-title">
                                                <h3 class="fw-bold m-0">Existencia origen</h3>
                                            </div>
                                        </div>
                                        <div class="card-body" id="alm_prep_existencias">Sin SKU origen seleccionado.</div>
                                    </div>
                                </div>
                                <div class="col-xl-8">
                                    <div class="card">
                                        <div class="card-header border-0 pt-5">
                                            <div class="card-title">
                                                <div class="d-flex align-items-center position-relative my-1">
                                                    <i class="bi bi-search fs-3 position-absolute ms-5"></i>
                                                    <input class="form-control form-control-solid w-300px ps-12" id="alm_prep_buscar" placeholder="Buscar folio o SKU">
                                                </div>
                                            </div>
                                            <div class="card-toolbar d-flex gap-3">
                                                <select class="form-select form-select-solid w-200px" id="alm_prep_estado">
                                                    <option value="">Todos</option>
                                                    <option value="borrador" selected>Borrador</option>
                                                    <option value="confirmada">Confirmada</option>
                                                    <option value="cancelada">Cancelada</option>
                                                </select>
                                                <button class="btn btn-light-primary" id="alm_prep_recargar" type="button"><i class="bi bi-arrow-clockwise"></i> Actualizar</button>
                                            </div>
                                        </div>
                                        <div class="card-body pt-0">
                                            <div class="table-responsive">
                                                <table class="table align-middle table-row-dashed gy-4" style="min-width: 900px;">
                                                    <thead>
                                                        <tr class="text-muted fw-bold fs-7 text-uppercase">
                                                            <th>Folio</th>
                                                            <th>Presentacion</th>
                                                            <th>Almacen</th>
                                                            <th class="text-end">Unidades</th>
                                                            <th class="text-end">Origen</th>
                                                            <th>Estado</th>
                                                            <th class="text-end">Acciones</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="alm_prep_body"></tbody>
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
    </div>
</div>
<script src="assets/plugins/global/plugins.bundle.js"></script>
<script src="assets/js/scripts.bundle.js"></script>
<script src="/assets/js/custom/apps/erp/almacen/preparacion_empaque/preparacion_empaque.js?v=20260625-1"></script>
</body>
</html>
