<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../">
    <title>Contabilidad - cierre mensual</title>
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
                        <div class="app-container container-fluid d-flex flex-stack flex-wrap gap-3">
                            <div>
                                <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Cierre mensual contable</h1>
                                <span class="text-muted">Movimientos bancarios, CFDI y paquete para contador</span>
                            </div>
                            <div class="d-flex gap-2">
                                <button class="btn btn-light" id="contabilidad_limpiar" type="button"><i class="bi bi-trash3"></i></button>
                                <button class="btn btn-light-primary" id="contabilidad_exportar_json" type="button"><i class="bi bi-braces"></i> JSON</button>
                                <button class="btn btn-primary" id="contabilidad_exportar_csv" type="button"><i class="bi bi-download"></i> CSV</button>
                            </div>
                        </div>
                    </div>
                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <div class="card mb-6">
                                <div class="card-body">
                                    <div class="row g-4 align-items-end">
                                        <div class="col-md-2">
                                            <label class="form-label">Mes</label>
                                            <input class="form-control form-control-solid" id="contabilidad_periodo" type="month">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Banco / cuenta</label>
                                            <input class="form-control form-control-solid" id="contabilidad_cuenta" placeholder="BBVA 0123">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Estado de cuenta CSV/TXT</label>
                                            <input class="form-control form-control-solid" id="contabilidad_banco_archivo" type="file" accept=".csv,.txt">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">CFDI XML</label>
                                            <input class="form-control form-control-solid" id="contabilidad_xml_archivos" type="file" accept=".xml" multiple>
                                        </div>
                                        <div class="col-md-1">
                                            <button class="btn btn-light-primary w-100" id="contabilidad_demo" type="button"><i class="bi bi-stars"></i></button>
                                        </div>
                                    </div>
                                    <div class="row g-4 mt-2">
                                        <div class="col-md-4">
                                            <label class="form-label">Buscar</label>
                                            <div class="position-relative">
                                                <i class="bi bi-search fs-3 position-absolute ms-5 mt-3"></i>
                                                <input class="form-control form-control-solid ps-12" id="contabilidad_buscar" placeholder="Concepto, RFC, folio o monto">
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Tipo</label>
                                            <select class="form-select form-select-solid" id="contabilidad_tipo">
                                                <option value="">Todos</option>
                                                <option value="deposito">Depositos</option>
                                                <option value="compra">Compras</option>
                                                <option value="gasto">Gastos</option>
                                                <option value="interno">Movimientos internos</option>
                                                <option value="impuesto">Impuestos</option>
                                                <option value="revision">Por revisar</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">CFDI</label>
                                            <select class="form-select form-select-solid" id="contabilidad_cfdi">
                                                <option value="">Todos</option>
                                                <option value="ligado">Ligado</option>
                                                <option value="pendiente">Pendiente</option>
                                                <option value="no_aplica">No aplica</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Flujo</label>
                                            <select class="form-select form-select-solid" id="contabilidad_flujo">
                                                <option value="">Todos</option>
                                                <option value="ingreso">Ingreso</option>
                                                <option value="egreso">Egreso</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <button class="btn btn-light w-100" id="contabilidad_recalcular" type="button"><i class="bi bi-arrow-clockwise"></i> Recalcular</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-4 mb-6" id="contabilidad_kpis"></div>

                            <div class="row g-6">
                                <div class="col-xl-8">
                                    <div class="card">
                                        <div class="card-header border-0 pt-5">
                                            <div class="card-title"><h3 class="fw-bold fs-5 mb-0">Movimientos bancarios</h3></div>
                                            <div class="card-toolbar"><span class="badge badge-light" id="contabilidad_total_visible">0 visibles</span></div>
                                        </div>
                                        <div class="card-body pt-0">
                                            <div class="table-responsive">
                                                <table class="table table-row-dashed align-middle">
                                                    <thead>
                                                    <tr class="fw-bold text-muted">
                                                        <th>Fecha</th>
                                                        <th>Concepto</th>
                                                        <th class="text-end">Cargo</th>
                                                        <th class="text-end">Abono</th>
                                                        <th>Tipo</th>
                                                        <th>CFDI</th>
                                                        <th class="text-end">Acciones</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody id="contabilidad_movimientos"></tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xl-4">
                                    <div class="card mb-6">
                                        <div class="card-header border-0 pt-5">
                                            <div class="card-title"><h3 class="fw-bold fs-5 mb-0">CFDI cargados</h3></div>
                                        </div>
                                        <div class="card-body pt-0" id="contabilidad_cfdis"></div>
                                    </div>
                                    <div class="card">
                                        <div class="card-header border-0 pt-5">
                                            <div class="card-title"><h3 class="fw-bold fs-5 mb-0">Pendientes del contador</h3></div>
                                        </div>
                                        <div class="card-body pt-0" id="contabilidad_pendientes"></div>
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
<script>window.CONTABILIDAD_CIERRE_MVP = true;</script>
<script src="assets/plugins/global/plugins.bundle.js"></script>
<script src="assets/js/scripts.bundle.js"></script>
<script src="assets/js/custom/apps/erp/contabilidad/cierre_mensual.js"></script>
</body>
</html>
