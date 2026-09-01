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
                                            <input class="form-control form-control-solid" id="contabilidad_cuenta" list="contabilidad_cuentas_sugeridas" placeholder="BBVA 0123">
                                            <datalist id="contabilidad_cuentas_sugeridas">
                                                <option value="BBVA"></option>
                                                <option value="Banamex"></option>
                                                <option value="Santander"></option>
                                                <option value="Mercado Pago"></option>
                                                <option value="Nu credito"></option>
                                                <option value="Efectivo"></option>
                                            </datalist>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Estado de cuenta</label>
                                            <input class="form-control form-control-solid" id="contabilidad_banco_archivo" type="file" accept=".csv,.txt,.xlsx">
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
                                            <label class="form-label">Movimiento</label>
                                            <select class="form-select form-select-solid" id="contabilidad_tipo">
                                                <option value="">Todos</option>
                                                <option value="gasto">Gasto</option>
                                                <option value="ingreso">Ingreso</option>
                                                <option value="transpaso">Transpaso</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Actividad</label>
                                            <select class="form-select form-select-solid" id="contabilidad_actividad">
                                                <option value="">Todas</option>
                                                <option value="negocio">Negocio</option>
                                                <option value="personal">Personal</option>
                                                <option value="inversion">Inversion</option>
                                                <option value="programacion">Programacion</option>
                                                <option value="publicidad">Publicidad</option>
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
                                            <button class="btn btn-light w-100" id="contabilidad_recalcular" type="button"><i class="bi bi-arrow-clockwise"></i> Recalcular</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card mb-6">
                                <div class="card-header border-0 pt-5">
                                    <div class="card-title">
                                        <h3 class="fw-bold fs-5 mb-0">Estados de cuenta cargados</h3>
                                    </div>
                                    <div class="card-toolbar">
                                        <span class="badge badge-light-primary">Flujo: cargar, mapear, clasificar, conciliar</span>
                                    </div>
                                </div>
                                <div class="card-body pt-0" id="contabilidad_estados"></div>
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
                                                        <th>Descripcion</th>
                                                        <th>Movimiento</th>
                                                        <th>Actividad</th>
                                                        <th>Cuenta</th>
                                                        <th class="text-end">Monto</th>
                                                        <th>Folio / factura</th>
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

                            <div class="card mt-6">
                                <div class="card-header border-0 pt-5">
                                    <div class="card-title">
                                        <h3 class="fw-bold fs-5 mb-0">Conciliacion por cuenta</h3>
                                    </div>
                                    <div class="card-toolbar">
                                        <span class="badge badge-light" id="contabilidad_conciliacion_total">0 cuentas</span>
                                    </div>
                                </div>
                                <div class="card-body pt-0" id="contabilidad_conciliacion_cuentas"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <?= include_once '../app/vistas/includes/footer/footer.php'; ?>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="contabilidad_mapeo_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h2 class="mb-1">Vista previa del estado de cuenta</h2>
                    <span class="text-muted" id="contabilidad_mapeo_subtitulo">Archivo original</span>
                </div>
                <button type="button" class="btn btn-sm btn-icon btn-light" data-bs-dismiss="modal" title="Cerrar"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="modal-body">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
                    <div class="d-flex flex-wrap gap-2" id="contabilidad_mapeo_resumen"></div>
                    <div class="d-flex align-items-center gap-2">
                        <label class="form-label text-muted mb-0 small" for="contabilidad_mapeo_fila_header">Encabezado</label>
                        <select class="form-select form-select-sm w-auto" id="contabilidad_mapeo_fila_header" title="Fila donde estan los nombres de columnas"></select>
                        <label class="form-label text-muted mb-0 small" for="contabilidad_mapeo_limite">Mostrar</label>
                        <select class="form-select form-select-sm w-auto" id="contabilidad_mapeo_limite" title="Cantidad de filas a mostrar">
                            <option value="10">10</option>
                            <option value="25" selected>25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                </div>
                <div class="alert alert-warning py-3 mb-5">
                    Prepara el archivo con una sola columna Monto antes de cargarlo. El sistema no usara Saldo posterior como monto.
                </div>
                <div class="row g-3 mb-6" id="contabilidad_mapeo_campos">
                    <div class="col-md-3"><label class="form-label fs-8">Fecha</label><select class="form-select form-select-sm" id="map_fecha"></select><div class="text-muted fs-9 mt-1">Fecha del movimiento.</div></div>
                    <div class="col-md-3"><label class="form-label fs-8">Descripcion</label><select class="form-select form-select-sm" id="map_concepto"></select><div class="text-muted fs-9 mt-1">Concepto visible del banco.</div></div>
                    <div class="col-md-3"><label class="form-label fs-8">Monto</label><select class="form-select form-select-sm" id="map_monto"></select><div class="text-muted fs-9 mt-1">Importe del movimiento.</div></div>
                    <div class="col-md-3"><label class="form-label fs-8">Folio / factura</label><select class="form-select form-select-sm" id="map_folio"></select><div class="text-muted fs-9 mt-1">Referencia para CFDI.</div></div>
                    <div class="col-md-3"><label class="form-label fs-8">Movimiento</label><select class="form-select form-select-sm" id="map_movimiento"></select><div class="text-muted fs-9 mt-1">Opcional; lo puedes corregir en la mesa.</div></div>
                    <div class="col-md-3"><label class="form-label fs-8">Actividad</label><select class="form-select form-select-sm" id="map_actividad"></select><div class="text-muted fs-9 mt-1">Negocio, programacion, personal o publicidad.</div></div>
                    <div class="col-md-3"><label class="form-label fs-8">Cuenta</label><select class="form-select form-select-sm" id="map_cuenta"></select><div class="text-muted fs-9 mt-1">Banco o cuenta origen.</div></div>
                </div>
                <div class="table-responsive border rounded">
                    <table class="table table-row-dashed gy-3 align-middle mb-0">
                        <thead id="contabilidad_mapeo_preview_head"></thead>
                        <tbody id="contabilidad_mapeo_preview_body"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
                <button class="btn btn-primary" id="contabilidad_mapeo_aplicar" type="button"><i class="bi bi-check2-circle"></i> Aplicar mapeo</button>
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
