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
                                                <option value="egreso">Egreso</option>
                                                <option value="ingreso">Ingreso</option>
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
                                                <option value="transpaso">Transpaso</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Categoria</label>
                                            <select class="form-select form-select-solid" id="contabilidad_categoria">
                                                <option value="">Todas</option>
                                                <option value="no_aplica">No aplica</option>
                                                <option value="compra_mercancia">Compras</option>
                                                <option value="gasto_operativo">Gasto operativo</option>
                                                <option value="comision_plataforma">Comision plataforma</option>
                                                <option value="servicio">Servicio</option>
                                                <option value="publicidad">Publicidad</option>
                                                <option value="software">Software</option>
                                                <option value="impuestos">Impuestos</option>
                                                <option value="personal">Personal</option>
                                                <option value="inversion">Inversion</option>
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
                                        <div class="col-md-1">
                                            <button class="btn btn-light w-100" id="contabilidad_recalcular" type="button" title="Recalcular"><i class="bi bi-arrow-clockwise"></i></button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-4 mb-6" id="contabilidad_kpis"></div>

                            <ul class="nav nav-tabs nav-line-tabs nav-line-tabs-2x fs-6 mb-6" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link active" data-bs-toggle="tab" href="#contabilidad_tab_resumen" role="tab">Resumen</a>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link" data-bs-toggle="tab" href="#contabilidad_tab_estados" role="tab">Estados de cuenta</a>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link" data-bs-toggle="tab" href="#contabilidad_tab_movimientos" role="tab">Clasificacion</a>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link" data-bs-toggle="tab" href="#contabilidad_tab_cfdi" role="tab">CFDI</a>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link" data-bs-toggle="tab" href="#contabilidad_tab_conciliacion" role="tab">Conciliacion</a>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link" data-bs-toggle="tab" href="#contabilidad_tab_reporte" role="tab">Reporte</a>
                                </li>
                            </ul>

                            <div class="tab-content">
                                <div class="tab-pane fade show active" id="contabilidad_tab_resumen" role="tabpanel">
                                    <div class="row g-6">
                                        <div class="col-xl-7">
                                            <div class="card h-100">
                                                <div class="card-header border-0 pt-5">
                                                    <div class="card-title"><h3 class="fw-bold fs-5 mb-0">Cierres guardados</h3></div>
                                                    <div class="card-toolbar"><span class="badge badge-light" id="contabilidad_guardados_total">0 guardados</span></div>
                                                </div>
                                                <div class="card-body pt-0" id="contabilidad_guardados"></div>
                                            </div>
                                        </div>
                                        <div class="col-xl-5">
                                            <div class="card h-100">
                                                <div class="card-header border-0 pt-5">
                                                    <div class="card-title"><h3 class="fw-bold fs-5 mb-0">Flujo del mes</h3></div>
                                                </div>
                                                <div class="card-body pt-0">
                                                    <div class="d-flex align-items-start gap-3 border-bottom py-4">
                                                        <span class="badge badge-light-primary p-3"><i class="bi bi-file-earmark-spreadsheet fs-3"></i></span>
                                                        <div><div class="fw-bold">1. Estados de cuenta</div><div class="text-muted fs-8">Carga uno o varios archivos por cuenta.</div></div>
                                                    </div>
                                                    <div class="d-flex align-items-start gap-3 border-bottom py-4">
                                                        <span class="badge badge-light-info p-3"><i class="bi bi-pencil-square fs-3"></i></span>
                                                        <div><div class="fw-bold">2. Clasificacion</div><div class="text-muted fs-8">Edita movimiento, actividad, categoria y folio.</div></div>
                                                    </div>
                                                    <div class="d-flex align-items-start gap-3 py-4">
                                                        <span class="badge badge-light-success p-3"><i class="bi bi-check2-circle fs-3"></i></span>
                                                        <div><div class="fw-bold">3. Conciliacion</div><div class="text-muted fs-8">Filtra agosto por compras, egresos, ingresos o cuenta.</div></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="tab-pane fade" id="contabilidad_tab_estados" role="tabpanel">
                                    <div class="card mb-6">
                                        <div class="card-header border-0 pt-5">
                                            <div class="card-title"><h3 class="fw-bold fs-5 mb-0">Agregar estado de cuenta</h3></div>
                                            <div class="card-toolbar">
                                                <button class="btn btn-sm btn-light-primary" id="contabilidad_guardar_borrador" type="button"><i class="bi bi-save"></i> Guardar cierre</button>
                                            </div>
                                        </div>
                                        <div class="card-body pt-0">
                                            <div class="row g-4 align-items-end">
                                                <div class="col-md-2">
                                                    <label class="form-label">Mes</label>
                                                    <input class="form-control form-control-solid" id="contabilidad_estado_periodo" type="month">
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label">Banco / cuenta</label>
                                                    <input class="form-control form-control-solid" id="contabilidad_cuenta" list="contabilidad_cuentas_sugeridas" placeholder="Santander CTA2780">
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
                                                    <input class="form-control form-control-solid" id="contabilidad_banco_archivo" type="file" accept=".csv,.txt,.xlsx,.json">
                                                </div>
                                                <div class="col-md-1">
                                                    <button class="btn btn-light-primary w-100" id="contabilidad_demo" type="button" title="Demo"><i class="bi bi-stars"></i></button>
                                                </div>
                                            </div>
                                            <div class="separator my-6"></div>
                                            <div class="row g-4 align-items-end">
                                                <div class="col-md-2">
                                                    <label class="form-label">Fecha</label>
                                                    <input class="form-control form-control-solid" id="manual_fecha" type="date">
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label">Concepto manual</label>
                                                    <input class="form-control form-control-solid" id="manual_concepto" placeholder="Compra tarjeta credito">
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label">Movimiento</label>
                                                    <select class="form-select form-select-solid" id="manual_movimiento">
                                                        <option value="egreso">Egreso</option>
                                                        <option value="ingreso">Ingreso</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label">Actividad</label>
                                                    <select class="form-select form-select-solid" id="manual_actividad">
                                                        <option value="negocio">Negocio</option>
                                                        <option value="programacion">Programacion</option>
                                                        <option value="personal">Personal</option>
                                                        <option value="publicidad">Publicidad</option>
                                                        <option value="inversion">Inversion</option>
                                                        <option value="transpaso">Transpaso</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label">Monto</label>
                                                    <input class="form-control form-control-solid" id="manual_monto" type="number" step="0.01" placeholder="0.00">
                                                </div>
                                                <div class="col-md-1">
                                                    <button class="btn btn-light-success w-100" id="manual_agregar" type="button" title="Agregar movimiento manual"><i class="bi bi-plus-lg"></i></button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="card">
                                        <div class="card-header border-0 pt-5">
                                            <div class="card-title"><h3 class="fw-bold fs-5 mb-0">Estados cargados</h3></div>
                                            <div class="card-toolbar d-flex gap-2">
                                                <button class="btn btn-sm btn-light" id="contabilidad_ver_todos_estados" type="button"><i class="bi bi-list-ul"></i> Ver todos</button>
                                                <span class="badge badge-light-primary">Por archivo y cuenta</span>
                                            </div>
                                        </div>
                                        <div class="card-body pt-0" id="contabilidad_estados"></div>
                                    </div>
                                </div>

                                <div class="tab-pane fade" id="contabilidad_tab_movimientos" role="tabpanel">
                                    <div class="row g-6">
                                        <div class="col-xl-8">
                                            <div class="card">
                                                <div class="card-header border-0 pt-5">
                                                    <div class="card-title"><h3 class="fw-bold fs-5 mb-0">Movimientos bancarios</h3></div>
                                                    <div class="card-toolbar"><span class="badge badge-light" id="contabilidad_total_visible">0 visibles</span></div>
                                                </div>
                                                <div class="card-body pt-0">
                                                    <div class="border rounded p-4 mb-5">
                                                        <div class="d-flex flex-wrap align-items-end gap-3">
                                                            <div>
                                                                <label class="form-label fs-8">Seleccionados</label>
                                                                <div><span class="badge badge-light-primary" id="contabilidad_masivo_total">0 movimientos</span></div>
                                                            </div>
                                                            <div class="min-w-150px">
                                                                <label class="form-label fs-8">Movimiento</label>
                                                                <select class="form-select form-select-sm" id="masivo_movimiento">
                                                                    <option value="">Sin cambio</option>
                                                                    <option value="egreso">Egreso</option>
                                                                    <option value="ingreso">Ingreso</option>
                                                                </select>
                                                            </div>
                                                            <div class="min-w-150px">
                                                                <label class="form-label fs-8">Actividad</label>
                                                                <select class="form-select form-select-sm" id="masivo_actividad">
                                                                    <option value="">Sin cambio</option>
                                                                    <option value="negocio">Negocio</option>
                                                                    <option value="programacion">Programacion</option>
                                                                    <option value="personal">Personal</option>
                                                                    <option value="publicidad">Publicidad</option>
                                                                    <option value="inversion">Inversion</option>
                                                                    <option value="transpaso">Transpaso</option>
                                                                </select>
                                                            </div>
                                                            <div class="min-w-175px">
                                                                <label class="form-label fs-8">Categoria</label>
                                                                <select class="form-select form-select-sm" id="masivo_categoria">
                                                                    <option value="">Sin cambio</option>
                                                                    <option value="no_aplica">No aplica</option>
                                                                    <option value="compra_mercancia">Compras</option>
                                                                    <option value="gasto_operativo">Gasto operativo</option>
                                                                    <option value="comision_plataforma">Comision plataforma</option>
                                                                    <option value="servicio">Servicio</option>
                                                                    <option value="publicidad">Publicidad</option>
                                                                    <option value="software">Software</option>
                                                                    <option value="impuestos">Impuestos</option>
                                                                    <option value="personal">Personal</option>
                                                                    <option value="inversion">Inversion</option>
                                                                    <option value="por_definir">Por definir</option>
                                                                </select>
                                                            </div>
                                                            <div class="min-w-150px">
                                                                <label class="form-label fs-8">CFDI</label>
                                                                <select class="form-select form-select-sm" id="masivo_cfdi">
                                                                    <option value="">Sin cambio</option>
                                                                    <option value="ligado">Ligado</option>
                                                                    <option value="pendiente">Pendiente</option>
                                                                    <option value="no_aplica">No aplica</option>
                                                                </select>
                                                            </div>
                                                            <div class="min-w-175px">
                                                                <label class="form-label fs-8">Cuenta</label>
                                                                <input class="form-control form-control-sm" id="masivo_cuenta" placeholder="Sin cambio">
                                                            </div>
                                                            <div class="ms-auto d-flex gap-2">
                                                                <button class="btn btn-sm btn-light" id="contabilidad_masivo_limpiar" type="button"><i class="bi bi-x-circle"></i> Limpiar</button>
                                                                <button class="btn btn-sm btn-primary" id="contabilidad_masivo_aplicar" type="button"><i class="bi bi-check2-square"></i> Aplicar</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="table-responsive">
                                                        <table class="table table-row-dashed align-middle">
                                                            <thead>
                                                            <tr class="fw-bold text-muted">
                                                                <th class="w-25px"><input class="form-check-input" id="contabilidad_select_todos" type="checkbox"></th>
                                                                <th>Fecha</th>
                                                                <th>Descripcion</th>
                                                                <th>Movimiento</th>
                                                                <th>Actividad</th>
                                                                <th>Categoria</th>
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
                                            <div class="card">
                                                <div class="card-header border-0 pt-5">
                                                    <div class="card-title"><h3 class="fw-bold fs-5 mb-0">Pendientes del contador</h3></div>
                                                </div>
                                                <div class="card-body pt-0" id="contabilidad_pendientes"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="tab-pane fade" id="contabilidad_tab_cfdi" role="tabpanel">
                                    <div class="card">
                                        <div class="card-header border-0 pt-5">
                                            <div class="card-title"><h3 class="fw-bold fs-5 mb-0">CFDI cargados</h3></div>
                                            <div class="card-toolbar"><span class="badge badge-light" id="contabilidad_cfdi_total">0 CFDI</span></div>
                                        </div>
                                        <div class="card-body pt-0">
                                            <div class="row g-4 align-items-end mb-6">
                                                <div class="col-md-2">
                                                    <label class="form-label">Mes CFDI</label>
                                                    <input class="form-control form-control-solid" id="contabilidad_cfdi_periodo" type="month">
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label">CFDI XML</label>
                                                    <input class="form-control form-control-solid" id="contabilidad_xml_archivos" type="file" accept=".xml" multiple>
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label">Tratamiento</label>
                                                    <select class="form-select form-select-solid" id="contabilidad_cfdi_tratamiento_default">
                                                        <option value="conciliar_banco">Conciliar con banco</option>
                                                        <option value="crear_auxiliar">Crear gasto desde CFDI</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label">Cuenta si no hay banco</label>
                                                    <select class="form-select form-select-solid" id="contabilidad_cfdi_cuenta_default">
                                                    </select>
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label">Clasificacion inicial</label>
                                                    <select class="form-select form-select-solid" id="contabilidad_cfdi_categoria_default">
                                                        <option value="gasto_operativo">Gasto operativo</option>
                                                        <option value="compra_mercancia">Compra</option>
                                                        <option value="comision_plataforma">Comision plataforma</option>
                                                        <option value="no_aplica">No aplica</option>
                                                        <option value="servicio">Servicio</option>
                                                        <option value="publicidad">Publicidad</option>
                                                        <option value="software">Software</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-2">
                                                    <button class="btn btn-light-primary w-100" id="contabilidad_cfdi_crear_movimientos" type="button"><i class="bi bi-plus-circle"></i> Crear gastos</button>
                                                </div>
                                            </div>
                                            <div class="border rounded p-4 mb-5">
                                                <div class="d-flex flex-wrap align-items-end gap-3">
                                                    <div>
                                                        <label class="form-label fs-8">Seleccionados</label>
                                                        <div><span class="badge badge-light-primary" id="contabilidad_cfdi_masivo_total">0 CFDI</span></div>
                                                    </div>
                                                    <div class="min-w-150px">
                                                        <label class="form-label fs-8">Tratamiento</label>
                                                        <select class="form-select form-select-sm" id="cfdi_masivo_tratamiento">
                                                            <option value="">Sin cambio</option>
                                                            <option value="conciliar_banco">Conciliar con banco</option>
                                                            <option value="crear_auxiliar">Crear gasto desde CFDI</option>
                                                        </select>
                                                    </div>
                                                    <div class="min-w-175px">
                                                        <label class="form-label fs-8">Clasificacion</label>
                                                        <select class="form-select form-select-sm" id="cfdi_masivo_categoria">
                                                            <option value="">Sin cambio</option>
                                                            <option value="gasto_operativo">Gasto operativo</option>
                                                            <option value="compra_mercancia">Compra</option>
                                                            <option value="comision_plataforma">Comision plataforma</option>
                                                            <option value="servicio">Servicio</option>
                                                            <option value="publicidad">Publicidad</option>
                                                            <option value="software">Software</option>
                                                        </select>
                                                    </div>
                                                    <div class="min-w-150px">
                                                        <label class="form-label fs-8">Actividad</label>
                                                        <select class="form-select form-select-sm" id="cfdi_masivo_actividad">
                                                            <option value="">Sin cambio</option>
                                                            <option value="negocio">Negocio</option>
                                                            <option value="programacion">Programacion</option>
                                                            <option value="personal">Personal</option>
                                                            <option value="publicidad">Publicidad</option>
                                                            <option value="inversion">Inversion</option>
                                                        </select>
                                                    </div>
                                                    <div class="min-w-150px">
                                                        <label class="form-label fs-8">Pago</label>
                                                        <select class="form-select form-select-sm" id="cfdi_masivo_forma_pago"></select>
                                                    </div>
                                                    <div class="min-w-175px">
                                                        <label class="form-label fs-8">Cuenta</label>
                                                        <select class="form-select form-select-sm" id="cfdi_masivo_cuenta"></select>
                                                    </div>
                                                    <div class="ms-auto d-flex gap-2">
                                                        <button class="btn btn-sm btn-light" id="cfdi_masivo_limpiar" type="button"><i class="bi bi-x-circle"></i> Limpiar</button>
                                                        <button class="btn btn-sm btn-light-success" id="cfdi_masivo_crear_aux" type="button"><i class="bi bi-plus-circle"></i> Crear auxiliares</button>
                                                        <button class="btn btn-sm btn-primary" id="cfdi_masivo_aplicar" type="button"><i class="bi bi-check2-square"></i> Aplicar</button>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="table-responsive">
                                                <table class="table table-row-dashed align-middle">
                                                    <thead>
                                                    <tr class="fw-bold text-muted">
                                                        <th class="w-25px"><input class="form-check-input" id="cfdi_select_todos" type="checkbox"></th>
                                                        <th>Fecha</th>
                                                        <th>CFDI / Conceptos</th>
                                                        <th>Clasificacion</th>
                                                        <th>Actividad</th>
                                                        <th>Pago</th>
                                                        <th>Cuenta</th>
                                                        <th class="text-end">Total</th>
                                                        <th>Relacion</th>
                                                        <th class="text-end">Acciones</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody id="contabilidad_cfdis"></tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="tab-pane fade" id="contabilidad_tab_conciliacion" role="tabpanel">
                                    <div class="card">
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

                                <div class="tab-pane fade" id="contabilidad_tab_reporte" role="tabpanel">
                                    <div class="card">
                                        <div class="card-header border-0 pt-5">
                                            <div class="card-title"><h3 class="fw-bold fs-5 mb-0">Reporte para contador</h3></div>
                                            <div class="card-toolbar d-flex gap-2">
                                                <button class="btn btn-sm btn-light" id="contabilidad_reporte_generar" type="button"><i class="bi bi-arrow-clockwise"></i> Generar</button>
                                                <button class="btn btn-sm btn-primary" id="contabilidad_reporte_copiar" type="button"><i class="bi bi-clipboard"></i> Copiar</button>
                                            </div>
                                        </div>
                                        <div class="card-body pt-0">
                                            <textarea class="form-control form-control-solid" id="contabilidad_reporte_texto" rows="16"></textarea>
                                        </div>
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
                    <div class="col-md-3"><label class="form-label fs-8">Movimiento</label><select class="form-select form-select-sm" id="map_movimiento"></select><div class="text-muted fs-9 mt-1">Egreso o ingreso; lo puedes corregir en la mesa.</div></div>
                    <div class="col-md-3"><label class="form-label fs-8">Actividad</label><select class="form-select form-select-sm" id="map_actividad"></select><div class="text-muted fs-9 mt-1">Negocio, programacion, personal, publicidad o transpaso.</div></div>
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
