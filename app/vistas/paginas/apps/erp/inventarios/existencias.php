<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../">
    <title>Existencias ERP</title>
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
                            <div><h1 class="page-heading text-dark fw-bold fs-3 mb-1">Existencias y kardex</h1><span class="text-muted">Inventario disponible por SKU, almacén, lote y ubicación</span></div>
                            <div class="d-flex gap-2">
                                <?php if (SesionSeguridad::tienePermiso("inventario.conteo")): ?>
                                <a class="btn btn-light-info" href="/inventario/conteos"><i class="bi bi-clipboard-check"></i> Conteos</a>
                                <?php endif; ?>
                                <?php if (SesionSeguridad::tienePermiso("inventario.ajustar")): ?>
                                <a class="btn btn-light-warning" href="/inventario/reservas"><i class="bi bi-bookmark-check"></i> Reservas</a>
                                <a class="btn btn-light-primary" href="/inventario/inicial"><i class="bi bi-plus-slash-minus"></i> Ajuste</a>
                                <?php endif; ?>
                                <?php if (SesionSeguridad::tienePermiso("inventario.traspasar")): ?>
                                <a class="btn btn-primary" href="/inventario/transpaso"><i class="bi bi-arrow-left-right"></i> Traspaso</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <div class="card">
                                <div class="card-header border-0 pt-5">
                                    <ul class="nav nav-tabs nav-line-tabs fs-6">
                                        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#inventario_tab_existencias">Existencias</button></li>
                                        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#inventario_tab_kardex">Kardex</button></li>
                                        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#inventario_tab_unidades">Unidades</button></li>
                                        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#inventario_tab_valuacion">Valuacion</button></li>
                                        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#inventario_tab_pendientes_pos">Pendientes POS</button></li>
                                    </ul>
                                </div>
                                <div class="card-body pt-5">
                                    <div class="row g-3 mb-5">
                                        <div class="col-md-4"><div class="position-relative"><i class="bi bi-search fs-3 position-absolute ms-5 mt-3"></i><input class="form-control form-control-solid ps-12" id="inventario_filtro_buscar" placeholder="Buscar SKU, producto, referencia, existencia o etiqueta"></div></div>
                                        <div class="col-md-3"><select class="form-select form-select-solid" id="inventario_filtro_almacen"></select></div>
                                        <div class="col-md-2"><select class="form-select form-select-solid" id="inventario_filtro_estado_fisico"><option value="">Estado fisico</option><option value="cerrada">Cerradas</option><option value="abierta">Abiertas</option><option value="consumida">Consumidas</option><option value="agotada">Agotadas fisicas</option><option value="vendida">Vendidas</option><option value="cancelada">Canceladas</option></select></div>
                                        <div class="col-md-1 d-flex align-items-center"><label class="form-check form-switch form-check-custom form-check-solid m-0" title="Mostrar existencias agotadas"><input class="form-check-input" type="checkbox" id="inventario_filtro_agotadas"><span class="form-check-label text-muted fs-8 ms-2">Agotadas</span></label></div>
                                        <div class="col-md-2"><button class="btn btn-light-primary w-100" id="inventario_recargar" type="button"><i class="bi bi-arrow-clockwise"></i> Actualizar</button></div>
                                    </div>
                                    <div id="inventario_diagnostico" class="mb-5"></div>
                                    <div class="tab-content">
                                        <div class="tab-pane fade show active" id="inventario_tab_existencias">
                                            <div class="d-flex flex-wrap gap-3 mb-5" id="inventario_resumen"></div>
                                            <div class="table-responsive"><table class="table align-middle table-row-dashed gy-4"><thead><tr class="text-muted fw-bold fs-7 text-uppercase"><th>SKU / producto</th><th>Almacén</th><th>Lote / caducidad</th><th>Ubicación</th><th>Existencia</th><th>Disponible</th><th>Costo promedio</th><th class="text-end">Acciones</th></tr></thead><tbody id="inventario_existencias"></tbody></table></div>
                                        </div>
                                        <div class="tab-pane fade" id="inventario_tab_kardex">
                                            <div class="table-responsive"><table class="table align-middle table-row-dashed gy-4"><thead><tr class="text-muted fw-bold fs-7 text-uppercase"><th>Fecha</th><th>Movimiento</th><th>SKU / producto</th><th>Almacén</th><th>Cantidad</th><th>Antes / después</th><th>Referencia</th><th class="text-end">Acciones</th></tr></thead><tbody id="inventario_movimientos"></tbody></table></div>
                                        </div>
                                        <div class="tab-pane fade" id="inventario_tab_unidades">
                                            <div class="d-flex flex-wrap gap-3 mb-5" id="inventario_unidades_resumen"></div>
                                            <div class="table-responsive"><table class="table align-middle table-row-dashed gy-4"><thead><tr class="text-muted fw-bold fs-7 text-uppercase"><th>Codigo</th><th>SKU / producto</th><th>Almacen</th><th>Lote / caducidad</th><th>Origen</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead><tbody id="inventario_unidades"></tbody></table></div>
                                        </div>
                                        <div class="tab-pane fade" id="inventario_tab_valuacion">
                                            <div class="d-flex flex-wrap gap-3 mb-5" id="inventario_valuacion_resumen"></div>
                                            <div class="table-responsive"><table class="table align-middle table-row-dashed gy-4"><thead><tr class="text-muted fw-bold fs-7 text-uppercase"><th>SKU / producto</th><th>Almacen</th><th>Existencias</th><th>Cantidad</th><th>Disponible</th><th>Apartada</th><th>Costo prom.</th><th>Valor</th></tr></thead><tbody id="inventario_valuacion"></tbody></table></div>
                                        </div>
                                        <div class="tab-pane fade" id="inventario_tab_pendientes_pos">
                                            <div class="d-flex flex-wrap gap-3 mb-5" id="inventario_pendientes_pos_resumen"></div>
                                            <div class="table-responsive"><table class="table align-middle table-row-dashed gy-4"><thead><tr class="text-muted fw-bold fs-7 text-uppercase"><th>Pendiente / venta</th><th>SKU</th><th>Almacen</th><th>Cantidades</th><th>Estado</th><th>Fechas</th><th class="text-end">Acciones</th></tr></thead><tbody id="inventario_pendientes_pos"></tbody></table></div>
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
<div class="modal fade" id="inventario_trazabilidad_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h3 class="modal-title mb-1" id="inventario_trazabilidad_titulo">Trazabilidad</h3>
                    <div class="text-muted fs-7">Existencias, movimientos y unidades relacionadas</div>
                </div>
                <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="modal-body" id="inventario_trazabilidad_body"></div>
        </div>
    </div>
</div>
<div class="modal fade" id="inventario_pendiente_pos_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h3 class="modal-title mb-1" id="inventario_pendiente_pos_titulo">Pendiente POS</h3>
                    <div class="text-muted fs-7">Expediente de venta con inventario pendiente</div>
                </div>
                <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="modal-body" id="inventario_pendiente_pos_body"></div>
        </div>
    </div>
</div>
<script src="assets/plugins/global/plugins.bundle.js"></script>
<script src="assets/js/scripts.bundle.js"></script>
<script src="/assets/js/custom/apps/erp/inventarios/existencias_erp.js?v=20260712-pospend1"></script>
</body>
</html>
