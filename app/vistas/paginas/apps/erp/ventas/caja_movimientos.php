<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../">
    <title>Movimientos de caja POS</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css">
    <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css">
    <!--
      Documentacion IA: Codex GPT-5, 2026-07-02.
      Proposito: separar gastos, retiros y entradas de caja del POS de cobro.
      Impacto: Ventas/POS/Caja; no registra movimientos reales.
      Contrato: vista dry-run/read-only hasta autorizacion explicita.
    -->
    <style>
        .pos-admin-card { border: 1px solid #e6e8ee; border-radius: 8px; background: #fff; }
        .pos-admin-result { max-height: 420px; overflow: auto; }
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
                                <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Movimientos de caja POS</h1>
                                <span class="text-muted">Gastos, retiros, entradas, vales y reembolsos controlados</span>
                                <div class="d-flex flex-wrap gap-2 mt-2">
                                    <span class="badge badge-light-warning">Simular movimiento = no registra dinero</span>
                                    <span class="badge badge-light-primary">Movimientos recientes = solo consulta</span>
                                    <span class="badge badge-light-danger">Aplicar movimiento real requiere autorizacion</span>
                                </div>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <a class="btn btn-light" href="/ventas/mostrar"><i class="bi bi-receipt"></i> Ventas</a>
                                <a class="btn btn-light-primary" href="/ventas/caja_turnos"><i class="bi bi-clock-history"></i> Turnos</a>
                                <a class="btn btn-light-primary" href="/ventas/caja_evidencias"><i class="bi bi-file-earmark-check"></i> Evidencias</a>
                                <a class="btn btn-light-primary" href="/ventas/pos_configuracion"><i class="bi bi-sliders"></i> Configuracion</a>
                                <a class="btn btn-primary" href="/ventas/pos"><i class="bi bi-shop-window"></i> POS</a>
                            </div>
                        </div>
                    </div>
                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <div id="pos_mov_alerta" class="mb-4"></div>
                            <div class="row g-4">
                                <div class="col-xl-5">
                                    <div class="pos-admin-card p-4">
                                        <div class="d-flex justify-content-between align-items-center mb-4">
                                            <div>
                                                <div class="fw-bold fs-5">Simular movimiento</div>
                                                <div class="text-muted fs-7">Valida reglas de caja sin escribir BD</div>
                                            </div>
                                            <button class="btn btn-sm btn-light-primary" id="pos_mov_recargar" type="button"><i class="bi bi-arrow-clockwise"></i></button>
                                        </div>
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label text-muted fs-8 text-uppercase">Almacen</label>
                                                <select class="form-select form-select-solid" id="pos_mov_almacen"></select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label text-muted fs-8 text-uppercase">Caja</label>
                                                <select class="form-select form-select-solid" id="pos_mov_caja"></select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label text-muted fs-8 text-uppercase">Turno</label>
                                                <select class="form-select form-select-solid" id="pos_mov_turno"></select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label text-muted fs-8 text-uppercase">Tipo</label>
                                                <select class="form-select form-select-solid" id="pos_mov_tipo">
                                                    <option value="gasto_caja">Gasto de caja</option>
                                                    <option value="retiro_efectivo">Retiro de efectivo</option>
                                                    <option value="entrada_extraordinaria">Entrada extraordinaria</option>
                                                    <option value="vale_interno">Vale interno</option>
                                                    <option value="reembolso_cliente">Reembolso cliente</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label text-muted fs-8 text-uppercase">Monto</label>
                                                <input class="form-control form-control-solid text-end fw-bold" id="pos_mov_monto" inputmode="decimal" placeholder="0.00">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label text-muted fs-8 text-uppercase">Referencia</label>
                                                <input class="form-control form-control-solid" id="pos_mov_referencia" placeholder="Folio, comprobante o nota">
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label text-muted fs-8 text-uppercase">Motivo</label>
                                                <input class="form-control form-control-solid" id="pos_mov_motivo" placeholder="Motivo operativo">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label text-muted fs-8 text-uppercase">Responsable</label>
                                                <input class="form-control form-control-solid" id="pos_mov_responsable" placeholder="Quien recibe/entrega">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label text-muted fs-8 text-uppercase">Observaciones</label>
                                                <input class="form-control form-control-solid" id="pos_mov_observaciones" placeholder="Notas internas">
                                            </div>
                                            <div class="col-12">
                                                <button class="btn btn-success w-100" id="pos_mov_simular" type="button"><i class="bi bi-calculator"></i> Simular sin registrar</button>
                                                <div class="text-muted fs-8 mt-2 text-center">El movimiento real queda pendiente de autorizacion y evidencia cuando aplique.</div>
                                            </div>
                                        </div>
                                        <div id="pos_mov_resultado" class="pos-admin-result mt-4"></div>
                                    </div>
                                </div>
                                <div class="col-xl-7">
                                    <div class="pos-admin-card p-4">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <div>
                                                <div class="fw-bold fs-5">Movimientos recientes</div>
                                                <div class="text-muted fs-7">Lectura de caja, sin edicion</div>
                                            </div>
                                            <a class="btn btn-sm btn-light" href="/ventas/caja_turnos"><i class="bi bi-safe"></i> Ver corte</a>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table align-middle table-row-dashed gy-3 mb-0">
                                                <thead><tr class="text-muted fw-bold fs-8 text-uppercase"><th>Fecha</th><th>Turno</th><th>Tipo</th><th>Motivo</th><th>Evidencia</th><th class="text-end">Monto</th></tr></thead>
                                                <tbody id="pos_mov_tabla"></tbody>
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
<script src="assets/plugins/global/plugins.bundle.js"></script>
<script src="assets/js/scripts.bundle.js"></script>
<script src="/assets/js/custom/apps/erp/ventas/caja_movimientos.js?v=20260702-readonly1"></script>
</body>
</html>
