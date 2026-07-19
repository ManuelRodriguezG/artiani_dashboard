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
      Documentacion IA: Codex GPT-5, 2026-07-09.
      Proposito: separar devoluciones/cancelaciones POS del tablero de ventas y habilitar inspeccion fisica documental.
      Impacto: prepara postventa ligada a caja, inventario, garantia, ticket e inspeccion en cuarentena.
      Contrato: reversas siguen controladas; inspeccion fisica solo confirma cuarentena sin mover inventario.
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
                                    <span class="badge badge-light-success">Inspeccion cuarentena = accion real controlada</span>
                                </div>
                            </div>
                            <div class="d-flex gap-2">
                                <a class="btn btn-light" href="/ventas/mostrar"><i class="bi bi-receipt"></i> Ventas</a>
                                <a class="btn btn-light-primary" href="/ventas/reportes"><i class="bi bi-bar-chart"></i> Reportes</a>
                                <a class="btn btn-primary" href="/ventas/pos"><i class="bi bi-shop-window"></i> POS</a>
                            </div>
                        </div>
                    </div>
                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <div class="alert alert-info py-3 mb-4">
                                <div class="fw-bold">Modulo separado de postventa</div>
                                <div class="fs-7">La reversa, reembolso y reintegro siguen protegidos. La inspeccion fisica permite confirmar cuarentena documental sin crear kardex ni mover inventario.</div>
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
                                                <select class="form-select form-select-solid form-select-sm" id="dev_inspeccion_estado_filtro">
                                                    <option value="">Estado inspeccion</option>
                                                    <option value="pendiente">Pendiente</option>
                                                    <option value="cuarentena_confirmada">Cuarentena confirmada</option>
                                                    <option value="todos">Todos</option>
                                                </select>
                                                <button class="btn btn-sm btn-light-primary" id="dev_fisicas_consultar" type="button"><i class="bi bi-search"></i> Consultar</button>
                                            </div>
                                        </div>
                                        <div id="dev_fisicas_resultado" class="dev-result"></div>
                                    </div>
                                    <div class="dev-card p-4 mb-4">
                                        <div class="fw-bold fs-5 mb-1">Inspeccion fisica</div>
                                        <div class="text-muted fs-7 mb-3">Confirma cuarentena documental sin mover inventario</div>
                                        <div class="row g-3">
                                            <div class="col-md-4">
                                                <label class="form-label text-muted fs-8 text-uppercase">Detalle devolucion</label>
                                                <input class="form-control form-control-solid" id="dev_inspeccion_detalle" placeholder="ID detalle">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label text-muted fs-8 text-uppercase">Decision fisica</label>
                                                <select class="form-select form-select-solid" id="dev_inspeccion_decision">
                                                    <option value="mantener_cuarentena">Mantener cuarentena</option>
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label text-muted fs-8 text-uppercase">Condicion</label>
                                                <select class="form-select form-select-solid" id="dev_inspeccion_condicion">
                                                    <option value="pendiente_revision">Pendiente revision</option>
                                                    <option value="empaque_danado">Empaque danado</option>
                                                    <option value="producto_danado">Producto danado</option>
                                                    <option value="no_apto_venta">No apto venta</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label text-muted fs-8 text-uppercase">Motivo</label>
                                                <textarea class="form-control form-control-solid" id="dev_inspeccion_motivo" rows="3" placeholder="Motivo documentado"></textarea>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label text-muted fs-8 text-uppercase">Diagnostico</label>
                                                <textarea class="form-control form-control-solid" id="dev_inspeccion_diagnostico" rows="3" placeholder="Diagnostico fisico"></textarea>
                                            </div>
                                            <div class="col-md-6">
                                                <button class="btn btn-light-primary w-100" id="dev_inspeccion_prevalidar" type="button"><i class="bi bi-search"></i> Prevalidar</button>
                                            </div>
                                            <div class="col-md-6">
                                                <button class="btn btn-warning w-100" id="dev_inspeccion_registrar" type="button"><i class="bi bi-clipboard-check"></i> Confirmar cuarentena</button>
                                            </div>
                                        </div>
                                        <div class="alert alert-light-warning py-3 mt-4 mb-0 fs-8">
                                            Las partidas ya inspeccionadas quedan en cuarentena confirmada; el destino final como reintegro, merma, garantia o reparacion se habilitara en una fase separada con kardex y autorizaciones.
                                        </div>
                                        <div id="dev_inspeccion_resultado" class="dev-result mt-4"></div>
                                    </div>
                                    <div class="dev-card p-4 mb-4">
                                        <div class="fw-bold fs-5 mb-1">Destino final de cuarentena</div>
                                        <div class="text-muted fs-7 mb-3">Prevalida reintegro, merma, garantia o reparacion sin escribir BD</div>
                                        <div class="row g-3">
                                            <div class="col-md-4">
                                                <label class="form-label text-muted fs-8 text-uppercase">Detalle devolucion</label>
                                                <input class="form-control form-control-solid" id="dev_destino_detalle" placeholder="ID detalle">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label text-muted fs-8 text-uppercase">Destino</label>
                                                <select class="form-select form-select-solid" id="dev_destino_final">
                                                    <option value="reintegrar_disponible">Reintegrar disponible</option>
                                                    <option value="merma">Merma</option>
                                                    <option value="garantia_proveedor">Garantia proveedor</option>
                                                    <option value="reparacion">Reparacion</option>
                                                    <option value="mantener_cuarentena">Mantener cuarentena</option>
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label text-muted fs-8 text-uppercase">Accion</label>
                                                <button class="btn btn-light-primary w-100" id="dev_destino_prevalidar" type="button"><i class="bi bi-shield-check"></i> Prevalidar destino</button>
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label text-muted fs-8 text-uppercase">Motivo</label>
                                                <textarea class="form-control form-control-solid" id="dev_destino_motivo" rows="2" placeholder="Motivo para resolver cuarentena"></textarea>
                                            </div>
                                        </div>
                                        <div class="alert alert-light-info py-3 mt-4 mb-0 fs-8">
                                            Esta prevalidacion no reintegra inventario, no crea merma, no crea garantia y no cierra la cuarentena. Sirve para preparar la autorizacion robusta.
                                        </div>
                                        <div id="dev_destino_resultado" class="dev-result mt-4"></div>
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
<script>
    window.ERP_CSRF_TOKEN = "<?= htmlspecialchars(SesionSeguridad::csrfToken(), ENT_QUOTES, 'UTF-8') ?>";
</script>
<script src="/assets/js/custom/apps/erp/ventas/devoluciones.js?v=20260709-destino-dryrun1"></script>
</body>
</html>
