<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../">
    <title>Ventas ERP</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css">
    <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css">
    <!--
      Documentacion IA: Codex GPT-5, 2026-06-26.
      Proposito: reemplazar el listado legacy de ventas por tablero ERP operativo.
      Impacto: Ventas/POS/Pedidos; no consulta ecommerce ni ejecuta cobros.
      Contrato: usa endpoints ERP read-only y muestra esquema pendiente cuando aun no existen tablas nuevas.
    -->
    <style>
        .ventas-kpi { border: 1px solid #e6e8ee; border-radius: 8px; background: #fff; min-height: 92px; }
        .ventas-filter { background: #f7f8fa; border: 1px solid #e6e8ee; border-radius: 8px; }
        .ventas-empty { min-height: 250px; border: 1px dashed #d7dbe4; border-radius: 8px; }
        .ventas-table-wrap { border: 1px solid #e6e8ee; border-radius: 8px; background: #fff; }
        .ventas-status-dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; }
        .ventas-ticket-pre { white-space: pre-wrap; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono", monospace; line-height: 1.35; }
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
                                <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Ventas ERP</h1>
                                <span class="text-muted">POS, pedidos, reservas, pagos y trazabilidad de inventario</span>
                            </div>
                            <div class="d-flex gap-2">
                                <a class="btn btn-light" href="/ventas/devoluciones"><i class="bi bi-arrow-counterclockwise"></i> Devoluciones</a>
                                <a class="btn btn-light" href="/ventas/caja_turnos"><i class="bi bi-safe"></i> Caja</a>
                                <a class="btn btn-light" href="/ventas/reportes"><i class="bi bi-bar-chart"></i> Reportes</a>
                                <a class="btn btn-light-primary" href="/ventas/pedidos"><i class="bi bi-list-check"></i> Pedidos</a>
                                <?php if (SesionSeguridad::tienePermiso("ventas.operar")): ?>
                                <a class="btn btn-primary" href="/ventas/pos"><i class="bi bi-shop-window"></i> POS</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <input type="hidden" id="ventas_tipo_inicial" value="<?= htmlspecialchars(isset($datos['tipo_inicial']) ? $datos['tipo_inicial'] : '', ENT_QUOTES, 'UTF-8') ?>">
                            <div class="row g-3 mb-4" id="ventas_kpis">
                                <div class="col-md-3"><div class="ventas-kpi p-4"><div class="text-muted fs-8 text-uppercase">Ventas hoy</div><div class="fw-bold fs-2" id="ventas_kpi_hoy">0</div></div></div>
                                <div class="col-md-3"><div class="ventas-kpi p-4"><div class="text-muted fs-8 text-uppercase">Total hoy</div><div class="fw-bold fs-2" id="ventas_kpi_total">$0.00</div></div></div>
                                <div class="col-md-3"><div class="ventas-kpi p-4"><div class="text-muted fs-8 text-uppercase">Pedidos abiertos</div><div class="fw-bold fs-2" id="ventas_kpi_pedidos">0</div></div></div>
                                <div class="col-md-3"><div class="ventas-kpi p-4"><div class="text-muted fs-8 text-uppercase">Turnos abiertos</div><div class="fw-bold fs-2" id="ventas_kpi_turnos">0</div></div></div>
                            </div>
                            <div id="ventas_alerta" class="mb-4"></div>
                            <div class="ventas-filter p-4 mb-4">
                                <div class="row g-3 align-items-end">
                                    <div class="col-lg-3">
                                        <label class="form-label text-muted fs-8 text-uppercase">Tipo</label>
                                        <select class="form-select form-select-solid" id="ventas_filtro_tipo">
                                            <option value="">Todos</option>
                                            <option value="venta">Ventas</option>
                                            <option value="pedido">Pedidos</option>
                                            <option value="apartado">Apartados</option>
                                        </select>
                                    </div>
                                    <div class="col-lg-3">
                                        <label class="form-label text-muted fs-8 text-uppercase">Estatus</label>
                                        <select class="form-select form-select-solid" id="ventas_filtro_estatus">
                                            <option value="">Todos</option>
                                            <option value="borrador">Borrador</option>
                                            <option value="reservado">Reservado</option>
                                            <option value="pendiente_pago">Pendiente pago</option>
                                            <option value="pagado">Pagado</option>
                                            <option value="entregado">Entregado</option>
                                            <option value="cancelado">Cancelado</option>
                                        </select>
                                    </div>
                                    <div class="col-lg-4">
                                        <label class="form-label text-muted fs-8 text-uppercase">Buscar</label>
                                        <div class="position-relative">
                                            <i class="bi bi-search fs-3 position-absolute ms-4 mt-3"></i>
                                            <input class="form-control form-control-solid ps-12" id="ventas_filtro_q" placeholder="Folio o cliente">
                                        </div>
                                    </div>
                                    <div class="col-lg-2">
                                        <button class="btn btn-light-primary w-100" id="ventas_recargar" type="button"><i class="bi bi-arrow-clockwise"></i> Actualizar</button>
                                    </div>
                                </div>
                            </div>
                            <div class="ventas-table-wrap">
                                <div class="table-responsive">
                                    <table class="table align-middle table-row-dashed gy-4 mb-0">
                                        <thead>
                                            <tr class="text-muted fw-bold fs-7 text-uppercase">
                                                <th>Folio</th>
                                                <th>Tipo / canal</th>
                                                <th>Cliente</th>
                                                <th>Almacen</th>
                                                <th>Partidas</th>
                                                <th>Total</th>
                                                <th>Estatus</th>
                                                <th>Fecha</th>
                                                <th class="text-end">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody id="ventas_listado"></tbody>
                                    </table>
                                </div>
                                <div class="ventas-empty d-none align-items-center justify-content-center text-center text-muted m-4" id="ventas_vacio">
                                    <div>
                                        <i class="bi bi-receipt fs-1 d-block mb-3"></i>
                                        <div class="fw-semibold">Aun no hay ventas ERP para mostrar</div>
                                        <div class="fs-7">El historial legacy queda fuera de este modulo hasta auditar migracion.</div>
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
<div class="modal fade" id="ventas_ticket_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title">Ticket POS</h5>
                    <div class="text-muted fs-7" id="ventas_ticket_subtitulo">Consulta read-only</div>
                </div>
                <button type="button" class="btn btn-sm btn-icon btn-light" data-bs-dismiss="modal" aria-label="Cerrar"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="modal-body">
                <div id="ventas_ticket_alerta"></div>
                <pre class="bg-light p-4 rounded fs-7 mb-0 ventas-ticket-pre" id="ventas_ticket_texto"></pre>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" type="button" data-bs-dismiss="modal">Cerrar</button>
                <button class="btn btn-primary" id="ventas_ticket_imprimir" type="button"><i class="bi bi-printer"></i> Imprimir</button>
            </div>
        </div>
    </div>
</div>
<script src="assets/plugins/global/plugins.bundle.js"></script>
<script src="assets/js/scripts.bundle.js"></script>
<script src="/assets/js/custom/apps/erp/ventas/listado.js?v=20260702-detalle-venta1"></script>
</body>
</html>
