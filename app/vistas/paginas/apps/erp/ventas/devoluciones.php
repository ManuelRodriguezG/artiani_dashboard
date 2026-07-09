<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../">
    <title>Devoluciones POS</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css">
    <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css">
    <!--
      Documentacion IA: Codex GPT-5, 2026-07-01.
      Proposito: separar devoluciones/cancelaciones POS del tablero de ventas.
      Impacto: prepara postventa ligada a caja, inventario, garantia y ticket.
      Contrato: vista dry-run/read-only; no aplica reversas reales.
    -->
    <style>
        .dev-card { border: 1px solid #e6e8ee; border-radius: 8px; background: #fff; }
        .dev-result { max-height: 420px; overflow: auto; }
        .dev-empty { min-height: 220px; border: 1px dashed #d7dbe4; border-radius: 8px; }
        .dev-ticket-pre { white-space: pre-wrap; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono", monospace; line-height: 1.35; }
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
                                <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Devoluciones POS</h1>
                                <span class="text-muted">Cancelaciones, reembolsos, saldo a favor e inspeccion fisica</span>
                                <div class="d-flex flex-wrap gap-2 mt-2">
                                    <span class="badge badge-light-warning">Simular reversa = no aplica devolucion</span>
                                    <span class="badge badge-light-primary">Pendientes/ticket = solo consulta</span>
                                    <span class="badge badge-light-danger">Reembolso real requiere autorizacion</span>
                                </div>
                            </div>
                            <div class="d-flex gap-2">
                                <a class="btn btn-light" href="/ventas/mostrar"><i class="bi bi-receipt"></i> Ventas</a>
                                <a class="btn btn-primary" href="/ventas/pos"><i class="bi bi-shop-window"></i> POS</a>
                            </div>
                        </div>
                    </div>
                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <div class="alert alert-info py-3 mb-4">
                                <div class="fw-bold">Modulo separado de postventa</div>
                                <div class="fs-7">Esta version simula y consulta. No reembolsa, no mueve inventario y no crea kardex. Aplicar devoluciones reales sigue requiriendo autorizacion con respaldo.</div>
                            </div>
                            <div class="row g-4">
                                <div class="col-xl-5">
                                    <div class="dev-card p-4">
                                        <div class="fw-bold fs-5 mb-1">Simular devolucion/cancelacion</div>
                                        <div class="text-muted fs-7 mb-3">Valida reglas sin aplicar la reversa real</div>
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label text-muted fs-8 text-uppercase">Tipo</label>
                                                <select class="form-select form-select-solid" id="dev_tipo">
                                                    <option value="devolucion">Devolucion</option>
                                                    <option value="cancelacion">Cancelacion</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label text-muted fs-8 text-uppercase">Folio venta</label>
                                                <input class="form-control form-control-solid" id="dev_folio" placeholder="POS-...">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label text-muted fs-8 text-uppercase">Decision inventario</label>
                                                <select class="form-select form-select-solid" id="dev_decision_inventario">
                                                    <option value="cuarentena">Cuarentena</option>
                                                    <option value="reintegrar">Reintegrar</option>
                                                    <option value="merma">Merma</option>
                                                    <option value="sin_reingreso">Sin reingreso</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label text-muted fs-8 text-uppercase">Decision financiera</label>
                                                <select class="form-select form-select-solid" id="dev_decision_financiera">
                                                    <option value="saldo_favor">Saldo a favor</option>
                                                    <option value="reembolso_caja">Reembolso de caja</option>
                                                    <option value="sin_reembolso">Sin reembolso</option>
                                                </select>
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label text-muted fs-8 text-uppercase">Motivo</label>
                                                <textarea class="form-control form-control-solid" id="dev_motivo" rows="3" placeholder="Motivo documentado para auditoria"></textarea>
                                            </div>
                                            <div class="col-12">
                                                <button class="btn btn-light-danger w-100" id="dev_simular" type="button"><i class="bi bi-arrow-counterclockwise"></i> Simular reversa sin aplicar</button>
                                            </div>
                                        </div>
                                        <div id="dev_resultado" class="dev-result mt-4"></div>
                                    </div>
                                </div>
                                <div class="col-xl-7">
                                    <div class="dev-card p-4 mb-4">
                                        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-3">
                                            <div>
                                                <div class="fw-bold fs-5">Devoluciones fisicas pendientes</div>
                                                <div class="text-muted fs-7">Partidas devueltas que esperan decision de almacen/inventario</div>
                                            </div>
                                            <div class="d-flex gap-2 align-items-end">
                                                <select class="form-select form-select-solid form-select-sm" id="dev_fisicas_filtro">
                                                    <option value="pendientes">Pendientes</option>
                                                    <option value="cuarentena">Cuarentena</option>
                                                    <option value="merma">Merma</option>
                                                    <option value="reintegrar">Reintegrar</option>
                                                    <option value="todos">Todas</option>
                                                </select>
                                                <button class="btn btn-sm btn-light-primary" id="dev_fisicas_consultar" type="button"><i class="bi bi-search"></i> Consultar</button>
                                            </div>
                                        </div>
                                        <div id="dev_fisicas_resultado" class="dev-result"></div>
                                    </div>
                                    <div class="dev-card p-4">
                                        <div class="fw-bold fs-5 mb-3">Ticket devolucion</div>
                                        <div class="row g-3 align-items-end">
                                            <div class="col-md-8">
                                                <label class="form-label text-muted fs-8 text-uppercase">Folio devolucion</label>
                                                <input class="form-control form-control-solid" id="dev_ticket_folio" placeholder="DEV-...">
                                            </div>
                                            <div class="col-md-4">
                                                <button class="btn btn-light-primary w-100" id="dev_ticket_consultar" type="button"><i class="bi bi-receipt"></i> Consultar</button>
                                            </div>
                                        </div>
                                        <pre class="bg-light p-4 rounded fs-7 mt-4 mb-0 dev-ticket-pre" id="dev_ticket_texto"></pre>
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
<script src="/assets/js/custom/apps/erp/ventas/devoluciones.js?v=20260702-acciones-venta1"></script>
</body>
</html>
