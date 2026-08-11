<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../">
    <title>Reclasificacion de inventario</title>
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
                                <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Reclasificacion de inventario</h1>
                                <span class="text-muted">Salida y entrada entre SKUs permitidos por Catalogo con el mismo folio</span>
                            </div>
                            <a class="btn btn-light-primary" href="/inventario/productos_existencias"><i class="bi bi-boxes"></i> Existencias</a>
                        </div>
                    </div>
                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <div id="reclas_alerta_esquema" class="mb-5"></div>
                            <div class="row g-7">
                                <div class="col-xl-5">
                                    <div class="card h-100">
                                        <div class="card-header border-0 pt-5">
                                            <div class="card-title"><h3 class="fw-bold mb-0">Origen</h3></div>
                                        </div>
                                        <div class="card-body pt-3">
                                            <div class="mb-5">
                                                <label class="form-label required">Almacen</label>
                                                <select class="form-select" id="reclas_almacen"></select>
                                            </div>
                                            <div class="position-relative mb-5">
                                                <label class="form-label required">SKU origen / existencia</label>
                                                <i class="bi bi-search fs-3 position-absolute ms-5" style="top:42px"></i>
                                                <input class="form-control form-control-solid ps-12" id="reclas_buscar_origen" placeholder="Buscar SKU, producto, lote o existencia" autocomplete="off">
                                                <div class="position-absolute start-0 end-0 bg-white border shadow-sm d-none" id="reclas_resultados_origen" style="z-index:10;max-height:360px;overflow:auto"></div>
                                            </div>
                                            <div id="reclas_origen_seleccionado" class="border rounded p-4 text-muted">Selecciona una existencia origen</div>
                                            <div class="mt-5 d-none" id="reclas_unidad_wrap">
                                                <label class="form-label required">Unidad fisica</label>
                                                <select class="form-select" id="reclas_unidad_origen"></select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xl-7">
                                    <div class="card mb-7">
                                        <div class="card-header border-0 pt-5">
                                            <div class="card-title"><h3 class="fw-bold mb-0">Destino y motivo</h3></div>
                                        </div>
                                        <div class="card-body pt-3">
                                            <div class="row g-5">
                                                <div class="col-md-6">
                                                    <label class="form-label required">SKU destino permitido</label>
                                                    <select class="form-select" id="reclas_sku_destino"></select>
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label required">Cantidad</label>
                                                    <input class="form-control" id="reclas_cantidad" inputmode="decimal" value="1">
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label">Folio</label>
                                                    <input class="form-control" id="reclas_referencia" maxlength="60" placeholder="Automatico">
                                                </div>
                                                <div class="col-12">
                                                    <label class="form-label required">Motivo</label>
                                                    <textarea class="form-control" id="reclas_motivo" rows="2" maxlength="700" placeholder="Describe la revision fisica o criterio de reclasificacion"></textarea>
                                                </div>
                                                <div class="col-12">
                                                    <label class="form-label">Observaciones</label>
                                                    <textarea class="form-control" id="reclas_observaciones" rows="2" maxlength="700"></textarea>
                                                </div>
                                            </div>
                                            <div class="d-flex justify-content-end gap-3 mt-7">
                                                <button class="btn btn-light-primary" id="reclas_previsualizar" type="button"><i class="bi bi-eye"></i> Previsualizar</button>
                                                <button class="btn btn-primary" id="reclas_guardar" type="button" disabled><i class="bi bi-check2-circle"></i> Guardar reclasificacion</button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card">
                                        <div class="card-header border-0 pt-5">
                                            <div class="card-title"><h3 class="fw-bold mb-0">Resumen</h3></div>
                                        </div>
                                        <div class="card-body pt-3" id="reclas_resumen">
                                            <div class="text-muted py-8 text-center">Previsualiza antes de guardar</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card mt-7">
                                <div class="card-header border-0 pt-5">
                                    <div class="card-title">
                                        <div>
                                            <h3 class="fw-bold mb-1">Historial reciente</h3>
                                            <span class="text-muted fs-7">Folios de reclasificacion con movimientos de salida y entrada ligados</span>
                                        </div>
                                    </div>
                                    <div class="card-toolbar d-flex gap-2">
                                        <input class="form-control form-control-sm w-250px" id="reclas_historial_buscar" placeholder="Buscar folio, SKU o lote">
                                        <button class="btn btn-sm btn-light-primary" type="button" id="reclas_historial_recargar"><i class="bi bi-arrow-clockwise"></i> Recargar</button>
                                    </div>
                                </div>
                                <div class="card-body pt-0">
                                    <div class="table-responsive">
                                        <table class="table align-middle table-row-dashed fs-7 gy-4">
                                            <thead>
                                                <tr class="text-start text-muted fw-bold text-uppercase">
                                                    <th>Folio</th>
                                                    <th>Salida</th>
                                                    <th>Entrada</th>
                                                    <th>Almacen</th>
                                                    <th>Lote</th>
                                                    <th>Cantidad</th>
                                                    <th>Movimientos</th>
                                                    <th>Estado</th>
                                                </tr>
                                            </thead>
                                            <tbody id="reclas_historial_body"></tbody>
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
<script src="/assets/js/custom/apps/erp/inventarios/reclasificacion_erp.js?v=20260808-3"></script>
</body>
</html>
