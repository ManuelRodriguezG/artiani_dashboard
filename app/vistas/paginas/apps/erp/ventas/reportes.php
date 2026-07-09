<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../">
    <title>Reportes POS</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css">
    <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css">
    <!--
      Documentacion IA: Codex GPT-5, 2026-07-03.
      Proposito: tablero read-only de reportes POS/caja.
      Impacto: supervisa diferencias por turno sin mover caja, ventas ni inventario.
      Contrato: consulta y resolucion administrativa de diferencias con permiso; no mueve caja/inventario.
    -->
    <style>
        .pos-report-card { border: 1px solid #e6e8ee; border-radius: 8px; background: #fff; }
        .pos-report-kpi { min-height: 104px; }
        .pos-report-table { max-height: 560px; overflow: auto; }
        .pos-corte-pre { white-space: pre-wrap; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono", monospace; line-height: 1.35; }
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
                    <div class="app-toolbar py-3 py-lg-5">
                        <div class="app-container container-fluid d-flex flex-stack flex-wrap gap-3">
                            <div>
                                <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Reportes POS</h1>
                                <span class="text-muted">Caja, diferencias, turnos y supervision operativa</span>
                                <div class="d-flex flex-wrap gap-2 mt-2">
                                    <span class="badge badge-light-primary">Reportes solo lectura</span>
                                    <span class="badge badge-light-warning">Diferencias no bloquean cierre</span>
                                    <span class="badge badge-light-info">Resolucion administrativa</span>
                                </div>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <a class="btn btn-light" href="/ventas/mostrar"><i class="bi bi-receipt"></i> Ventas</a>
                                <a class="btn btn-light-primary" href="/ventas/caja_turnos"><i class="bi bi-clock-history"></i> Turnos</a>
                                <a class="btn btn-light-primary" href="/ventas/caja_movimientos"><i class="bi bi-cash-stack"></i> Movimientos</a>
                                <a class="btn btn-light-primary" href="/ventas/pos_configuracion"><i class="bi bi-sliders"></i> Configuracion</a>
                                <a class="btn btn-primary" href="/ventas/pos"><i class="bi bi-shop-window"></i> POS</a>
                            </div>
                        </div>
                    </div>
                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <div id="pos_reportes_alerta" class="mb-4"></div>
                            <div class="pos-report-card p-4 mb-4">
                                <div class="row g-3 align-items-end">
                                    <div class="col-lg-2 col-md-4">
                                        <label class="form-label text-muted fs-8 text-uppercase">Desde</label>
                                        <input class="form-control form-control-solid" id="pos_rep_desde" type="date">
                                    </div>
                                    <div class="col-lg-2 col-md-4">
                                        <label class="form-label text-muted fs-8 text-uppercase">Hasta</label>
                                        <input class="form-control form-control-solid" id="pos_rep_hasta" type="date">
                                    </div>
                                    <div class="col-lg-3 col-md-4">
                                        <label class="form-label text-muted fs-8 text-uppercase">Sucursal</label>
                                        <select class="form-select form-select-solid" id="pos_rep_almacen">
                                            <option value="">Todas</option>
                                        </select>
                                    </div>
                                    <div class="col-lg-3 col-md-4">
                                        <label class="form-label text-muted fs-8 text-uppercase">Caja</label>
                                        <select class="form-select form-select-solid" id="pos_rep_caja">
                                            <option value="">Todas</option>
                                        </select>
                                    </div>
                                    <div class="col-lg-2 col-md-4">
                                        <label class="form-check form-check-custom form-check-solid mt-8">
                                            <input class="form-check-input" id="pos_rep_solo_diferencias" type="checkbox">
                                            <span class="form-check-label">Solo con diferencia</span>
                                        </label>
                                    </div>
                                    <div class="col-lg-12 col-md-4">
                                        <div class="d-flex gap-2">
                                            <button class="btn btn-primary flex-grow-1" id="pos_rep_consultar" type="button"><i class="bi bi-bar-chart"></i> Consultar</button>
                                            <button class="btn btn-light-primary" id="pos_rep_exportar" type="button" title="Exportar CSV"><i class="bi bi-download"></i></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row g-3 mb-4">
                                <div class="col-md-3"><div class="pos-report-card pos-report-kpi p-4"><div class="text-muted fs-8 text-uppercase">Turnos</div><div class="fw-bold fs-2" id="pos_rep_kpi_turnos">0</div></div></div>
                                <div class="col-md-3"><div class="pos-report-card pos-report-kpi p-4"><div class="text-muted fs-8 text-uppercase">Con diferencia</div><div class="fw-bold fs-2" id="pos_rep_kpi_diferencias">0</div></div></div>
                                <div class="col-md-3"><div class="pos-report-card pos-report-kpi p-4"><div class="text-muted fs-8 text-uppercase">Faltantes</div><div class="fw-bold fs-4 text-danger" id="pos_rep_kpi_faltantes">$0.00</div></div></div>
                                <div class="col-md-3"><div class="pos-report-card pos-report-kpi p-4"><div class="text-muted fs-8 text-uppercase">Sobrantes</div><div class="fw-bold fs-4 text-success" id="pos_rep_kpi_sobrantes">$0.00</div></div></div>
                                <div class="col-md-3"><div class="pos-report-card pos-report-kpi p-4"><div class="text-muted fs-8 text-uppercase">Ventas</div><div class="fw-bold fs-4" id="pos_rep_kpi_ventas">$0.00</div></div></div>
                                <div class="col-md-3"><div class="pos-report-card pos-report-kpi p-4"><div class="text-muted fs-8 text-uppercase">Movimientos caja</div><div class="fw-bold fs-4" id="pos_rep_kpi_movimientos">0</div></div></div>
                                <div class="col-md-3"><div class="pos-report-card pos-report-kpi p-4"><div class="text-muted fs-8 text-uppercase">Faltante promedio</div><div class="fw-bold fs-4 text-danger" id="pos_rep_kpi_faltante_prom">$0.00</div></div></div>
                                <div class="col-md-3"><div class="pos-report-card pos-report-kpi p-4"><div class="text-muted fs-8 text-uppercase">Sobrante promedio</div><div class="fw-bold fs-4 text-success" id="pos_rep_kpi_sobrante_prom">$0.00</div></div></div>
                            </div>
                            <div class="pos-report-card p-4">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <div class="fw-bold fs-5">Turnos y diferencias</div>
                                        <div class="text-muted fs-7">La diferencia se registra al cerrar caja y no modifica inventario ni ventas.</div>
                                    </div>
                                </div>
                                <div class="table-responsive pos-report-table">
                                    <table class="table align-middle table-row-dashed gy-3 mb-0">
                                        <thead>
                                            <tr class="text-muted fw-bold fs-8 text-uppercase">
                                                <th>Turno</th>
                                                <th>Sucursal/Caja</th>
                                                <th>Usuarios</th>
                                                <th>Fechas</th>
                                                <th class="text-end">Esperado</th>
                                                <th class="text-end">Contado</th>
                                                <th class="text-end">Diferencia</th>
                                                <th>Estado</th>
                                                <th class="text-end">Corte</th>
                                            </tr>
                                        </thead>
                                        <tbody id="pos_reportes_turnos"></tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="pos-report-card p-4 mt-4">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <div class="fw-bold fs-5">Diferencias por empleado</div>
                                        <div class="text-muted fs-7">Agrupa turnos por usuario de cierre para detectar patrones sin asumir causa.</div>
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="table align-middle table-row-dashed gy-3 mb-0">
                                        <thead>
                                            <tr class="text-muted fw-bold fs-8 text-uppercase">
                                                <th>Empleado</th>
                                                <th class="text-end">Turnos</th>
                                                <th class="text-end">Con diferencia</th>
                                                <th class="text-end">% diferencia</th>
                                                <th class="text-end">Faltantes</th>
                                                <th class="text-end">Sobrantes</th>
                                                <th class="text-end">Neto</th>
                                            </tr>
                                        </thead>
                                        <tbody id="pos_reportes_usuarios"></tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="pos-report-card p-4 mt-4">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <div class="fw-bold fs-5">Diferencias por sucursal y caja</div>
                                        <div class="text-muted fs-7">Separa patrones por ubicacion operativa para supervision y ajustes de proceso.</div>
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="table align-middle table-row-dashed gy-3 mb-0">
                                        <thead>
                                            <tr class="text-muted fw-bold fs-8 text-uppercase">
                                                <th>Sucursal / caja</th>
                                                <th class="text-end">Turnos</th>
                                                <th class="text-end">Con diferencia</th>
                                                <th class="text-end">% diferencia</th>
                                                <th class="text-end">Faltantes</th>
                                                <th class="text-end">Sobrantes</th>
                                                <th class="text-end">Neto</th>
                                            </tr>
                                        </thead>
                                        <tbody id="pos_reportes_cajas"></tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="pos-report-card p-4 mt-4">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <div class="fw-bold fs-5">Seguimiento de diferencias</div>
                                        <div class="text-muted fs-7">Faltantes y sobrantes cerrados que requieren explicacion o cierre administrativo sin mover efectivo.</div>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <select class="form-select form-select-sm form-select-solid w-auto" id="pos_rep_estado_revision">
                                            <option value="pendiente_revision">Pendientes</option>
                                            <option value="todos">Todos</option>
                                            <option value="en_revision">En revision</option>
                                            <option value="explicada">Explicadas</option>
                                            <option value="aceptada">Aceptadas</option>
                                            <option value="ajustada">Ajustadas</option>
                                            <option value="escalada">Escaladas</option>
                                            <option value="cancelada">Canceladas</option>
                                        </select>
                                        <span class="badge badge-light-warning" id="pos_rep_dif_schema">Revision formal pendiente</span>
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="table align-middle table-row-dashed gy-3 mb-0">
                                        <thead>
                                            <tr class="text-muted fw-bold fs-8 text-uppercase">
                                                <th>Turno</th>
                                                <th>Sucursal / caja</th>
                                                <th>Tipo</th>
                                                <th class="text-end">Esperado</th>
                                                <th class="text-end">Contado</th>
                                                <th class="text-end">Diferencia</th>
                                                <th>Revision</th>
                                                <th class="text-end">Accion</th>
                                            </tr>
                                        </thead>
                                        <tbody id="pos_reportes_diferencias"></tbody>
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
<div class="modal fade" id="pos_rep_corte_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h3 class="modal-title">Corte de caja</h3>
                    <div class="text-muted fs-7" id="pos_rep_corte_subtitulo">Consulta read-only</div>
                </div>
                <button type="button" class="btn btn-sm btn-icon btn-light" data-bs-dismiss="modal"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="modal-body">
                <div id="pos_rep_corte_alerta"></div>
                <pre class="bg-light p-4 rounded fs-7 mb-0 pos-corte-pre" id="pos_rep_corte_texto"></pre>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" data-bs-dismiss="modal" type="button">Cerrar</button>
                <button class="btn btn-primary" id="pos_rep_corte_imprimir" type="button"><i class="bi bi-printer"></i> Imprimir</button>
            </div>
        </div>
    </div>
</div>
<script src="assets/plugins/global/plugins.bundle.js"></script>
<script src="assets/js/scripts.bundle.js"></script>
<script src="/assets/js/custom/apps/erp/ventas/reportes.js?v=20260703-filtros-caja1"></script>
</body>
</html>
