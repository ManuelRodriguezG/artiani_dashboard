<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../">
    <title>Caja POS</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css">
    <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css">
    <!--
      Documentacion IA: Codex GPT-5, 2026-07-04.
      Proposito: separar turnos/corte de caja POS de la pantalla de cobro.
      Impacto: Ventas/POS/Caja; valida apertura/cierre, calcula arqueo y ejecuta turnos reales con confirmacion fuerte.
      Contrato: vista operativa; apertura/cierre real requieren permiso, CSRF, dry-run previo y confirmacion escrita.
    -->
    <style>
        .pos-admin-card { border: 1px solid #e6e8ee; border-radius: 8px; background: #fff; }
        .pos-admin-kpi { min-height: 96px; }
        .pos-admin-result { max-height: 320px; overflow: auto; }
        .pos-denom-row { display: grid; grid-template-columns: 72px 1fr 108px; gap: 8px; align-items: center; }
        .pos-denom-row + .pos-denom-row { margin-top: 8px; }
        .pos-denom-total { min-width: 92px; text-align: right; }
        .pos-corte-total { border: 1px solid #d7dde8; border-radius: 8px; background: #f8fafc; }
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
                                <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Caja POS</h1>
                                <span class="text-muted">Turnos, corte, arqueo y movimientos recientes</span>
                                <div class="d-flex flex-wrap gap-2 mt-2">
                                    <span class="badge badge-light-primary">Consulta de caja = solo lectura</span>
                                    <span class="badge badge-light-warning">Dry-run antes de escribir</span>
                                    <span class="badge badge-light-danger">Abrir/cerrar real exige confirmacion</span>
                                    <span class="badge badge-light-info">Diferencias quedan en reportes</span>
                                </div>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <a class="btn btn-light" href="/ventas/mostrar"><i class="bi bi-receipt"></i> Ventas</a>
                                <a class="btn btn-light-primary" href="/ventas/caja_movimientos"><i class="bi bi-cash-stack"></i> Movimientos</a>
                                <a class="btn btn-light-primary" href="/ventas/caja_evidencias"><i class="bi bi-file-earmark-check"></i> Evidencias</a>
                                <a class="btn btn-light-primary" href="/ventas/reportes"><i class="bi bi-bar-chart"></i> Reportes</a>
                                <a class="btn btn-light-primary" href="/ventas/pos_configuracion"><i class="bi bi-sliders"></i> Configuracion</a>
                                <a class="btn btn-primary" href="/ventas/pos"><i class="bi bi-shop-window"></i> POS</a>
                            </div>
                        </div>
                    </div>
                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <div id="pos_caja_alerta" class="mb-4"></div>
                            <div class="row g-3 mb-4">
                                <div class="col-md-3"><div class="pos-admin-card pos-admin-kpi p-4"><div class="text-muted fs-8 text-uppercase">Cajas</div><div class="fw-bold fs-2" id="pos_caja_kpi_cajas">0</div></div></div>
                                <div class="col-md-3"><div class="pos-admin-card pos-admin-kpi p-4"><div class="text-muted fs-8 text-uppercase">Turnos abiertos</div><div class="fw-bold fs-2" id="pos_caja_kpi_turnos">0</div></div></div>
                                <div class="col-md-3"><div class="pos-admin-card pos-admin-kpi p-4"><div class="text-muted fs-8 text-uppercase">Movimientos recientes</div><div class="fw-bold fs-2" id="pos_caja_kpi_movimientos">0</div></div></div>
                                <div class="col-md-3"><div class="pos-admin-card pos-admin-kpi p-4"><div class="text-muted fs-8 text-uppercase">Modo</div><div class="fw-bold fs-5">Consulta / validacion</div></div></div>
                            </div>
                            <div class="row g-4">
                                <div class="col-xl-5">
                                    <div class="pos-admin-card p-4 mb-4">
                                        <div class="d-flex justify-content-between align-items-center mb-4">
                                            <div>
                                                <div class="fw-bold fs-5">Apertura de turno</div>
                                                <div class="text-muted fs-7">Valida y abre turno para la caja asignada</div>
                                            </div>
                                            <span class="badge badge-light-danger">Confirmacion</span>
                                        </div>
                                        <div class="row g-3 align-items-end">
                                            <div class="col-md-6">
                                                <label class="form-label text-muted fs-8 text-uppercase">Almacen</label>
                                                <select class="form-select form-select-solid" id="pos_caja_apertura_almacen" disabled></select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label text-muted fs-8 text-uppercase">Caja</label>
                                                <select class="form-select form-select-solid" id="pos_caja_apertura_caja" disabled></select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label text-muted fs-8 text-uppercase">Monto inicial</label>
                                                <input class="form-control form-control-solid text-end fw-bold" id="pos_caja_monto_inicial" inputmode="decimal" value="500">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label text-muted fs-8 text-uppercase">Responsable</label>
                                                <input class="form-control form-control-solid" value="Usuario actual" disabled>
                                            </div>
                                            <div class="col-12">
                                                <button class="btn btn-primary w-100" id="pos_caja_apertura_dryrun" type="button"><i class="bi bi-door-open"></i> Validar apertura</button>
                                                <div class="text-muted fs-8 mt-2 text-center">Usa la caja asignada al usuario actual. Si valida, podras confirmar apertura real.</div>
                                            </div>
                                        </div>
                                        <div id="pos_caja_apertura_resultado" class="pos-admin-result mt-4"></div>
                                    </div>
                                    <div class="pos-admin-card p-4 mb-4">
                                        <div class="d-flex justify-content-between align-items-center mb-4">
                                            <div>
                                                <div class="fw-bold fs-5">Corte de turno</div>
                                                <div class="text-muted fs-7">Valida el corte sin cerrar el turno real</div>
                                            </div>
                                            <button class="btn btn-sm btn-light-primary" id="pos_caja_recargar" type="button"><i class="bi bi-arrow-clockwise"></i></button>
                                        </div>
                                        <div class="row g-3 align-items-end">
                                            <div class="col-md-6">
                                                <label class="form-label text-muted fs-8 text-uppercase">Almacen</label>
                                                <select class="form-select form-select-solid" id="pos_caja_almacen"></select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label text-muted fs-8 text-uppercase">Caja</label>
                                                <select class="form-select form-select-solid" id="pos_caja_caja"></select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label text-muted fs-8 text-uppercase">Turno</label>
                                                <select class="form-select form-select-solid" id="pos_caja_turno"></select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label text-muted fs-8 text-uppercase">Monto contado</label>
                                                <input class="form-control form-control-solid text-end fw-bold" id="pos_caja_monto_contado" inputmode="decimal" placeholder="0.00" readonly>
                                            </div>
                                            <div class="col-12">
                                                <div class="pos-corte-total p-3">
                                                    <div class="d-flex justify-content-between align-items-center gap-3">
                                                        <div>
                                                            <div class="fw-bold">Arqueo rapido</div>
                                                            <div class="text-muted fs-8">Captura piezas de efectivo y montos de otros metodos</div>
                                                        </div>
                                                        <div class="text-end">
                                                            <div class="text-muted fs-8 text-uppercase">Total contado</div>
                                                            <div class="fw-bold fs-4" id="pos_caja_arqueo_total">$0.00</div>
                                                        </div>
                                                    </div>
                                                    <div class="separator my-3"></div>
                                                    <div class="row g-3">
                                                        <div class="col-lg-7">
                                                            <div class="text-muted fs-8 text-uppercase mb-2">Efectivo por denominacion</div>
                                                            <div id="pos_caja_denominaciones"></div>
                                                        </div>
                                                        <div class="col-lg-5">
                                                            <div class="text-muted fs-8 text-uppercase mb-2">Otros metodos</div>
                                                            <label class="form-label fs-8 text-muted">Tarjeta</label>
                                                            <input class="form-control form-control-solid text-end pos-caja-arqueo-extra mb-2" id="pos_caja_arqueo_tarjeta" inputmode="decimal" value="0">
                                                            <label class="form-label fs-8 text-muted">Transferencia</label>
                                                            <input class="form-control form-control-solid text-end pos-caja-arqueo-extra mb-2" id="pos_caja_arqueo_transferencia" inputmode="decimal" value="0">
                                                            <label class="form-label fs-8 text-muted">Vales / saldo a favor</label>
                                                            <input class="form-control form-control-solid text-end pos-caja-arqueo-extra" id="pos_caja_arqueo_vales" inputmode="decimal" value="0">
                                                            <div class="alert alert-light-info py-2 mt-3 mb-0 fs-8">El total alimenta el dry-run. Si valida, podras confirmar cierre real.</div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <button class="btn btn-success w-100" id="pos_caja_corte_dryrun" type="button"><i class="bi bi-calculator"></i> Validar corte</button>
                                                <div class="text-muted fs-8 mt-2 text-center">El cierre real exige escribir CERRAR TURNO.</div>
                                            </div>
                                        </div>
                                        <div id="pos_caja_corte_resultado" class="pos-admin-result mt-4"></div>
                                    </div>
                                    <div class="pos-admin-card p-4 mb-4">
                                        <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                                            <div>
                                                <div class="fw-bold fs-5">Readiness POS</div>
                                                <div class="text-muted fs-7">Revision consolidada sin cerrar turno ni mover inventario</div>
                                            </div>
                                            <span class="badge badge-light-info">Solo lectura</span>
                                        </div>
                                        <div class="row g-3 align-items-end">
                                            <div class="col-md-6">
                                                <label class="form-label text-muted fs-8 text-uppercase">Folio venta</label>
                                                <input class="form-control form-control-solid" id="pos_caja_readiness_folio" value="POS-20260701-000001">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label text-muted fs-8 text-uppercase">SKU ID</label>
                                                <input class="form-control form-control-solid text-end" id="pos_caja_readiness_sku" inputmode="numeric" value="1760">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label text-muted fs-8 text-uppercase">Contado</label>
                                                <input class="form-control form-control-solid text-end" id="pos_caja_readiness_contado" inputmode="decimal" value="795">
                                            </div>
                                            <div class="col-12">
                                                <button class="btn btn-light-primary w-100" id="pos_caja_readiness_consultar" type="button"><i class="bi bi-clipboard-check"></i> Revisar readiness</button>
                                            </div>
                                        </div>
                                        <div id="pos_caja_readiness_resultado" class="pos-admin-result mt-4"></div>
                                    </div>
                                </div>
                                <div class="col-xl-7">
                                    <div class="pos-admin-card p-4 mb-4">
                                        <div class="fw-bold fs-5 mb-3">Turnos abiertos</div>
                                        <div class="table-responsive">
                                            <table class="table align-middle table-row-dashed gy-3 mb-0">
                                                <thead><tr class="text-muted fw-bold fs-8 text-uppercase"><th>Turno</th><th>Caja</th><th>Almacen</th><th>Inicial</th><th>Apertura</th></tr></thead>
                                                <tbody id="pos_caja_turnos_tabla"></tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <div class="pos-admin-card p-4 mb-4">
                                        <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                                            <div>
                                                <div class="fw-bold fs-5">Corte imprimible</div>
                                                <div class="text-muted fs-7">Consulta y reimprime cortes sin modificar caja</div>
                                            </div>
                                            <span class="badge badge-light-info">Read-only</span>
                                        </div>
                                        <div class="row g-3 align-items-end">
                                            <div class="col-md-8">
                                                <label class="form-label text-muted fs-8 text-uppercase">Folio o ID turno</label>
                                                <input class="form-control form-control-solid" id="pos_caja_corte_folio" value="TUR-20260704-002-002">
                                            </div>
                                            <div class="col-md-4">
                                                <button class="btn btn-light-primary w-100" id="pos_caja_corte_consultar" type="button"><i class="bi bi-receipt-cutoff"></i> Consultar</button>
                                            </div>
                                        </div>
                                        <div id="pos_caja_corte_alerta" class="mt-3"></div>
                                        <pre class="bg-light p-4 rounded fs-7 mt-3 mb-3 pos-corte-pre" id="pos_caja_corte_texto"></pre>
                                        <button class="btn btn-primary w-100" id="pos_caja_corte_imprimir" type="button"><i class="bi bi-printer"></i> Imprimir corte</button>
                                    </div>
                                    <div class="pos-admin-card p-4">
                                        <div class="fw-bold fs-5 mb-3">Movimientos recientes</div>
                                        <div class="table-responsive">
                                            <table class="table align-middle table-row-dashed gy-3 mb-0">
                                                <thead><tr class="text-muted fw-bold fs-8 text-uppercase"><th>Fecha</th><th>Turno</th><th>Tipo</th><th>Motivo</th><th class="text-end">Monto</th></tr></thead>
                                                <tbody id="pos_caja_movimientos_tabla"></tbody>
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
<script>
window.POS_USUARIO_ACTUAL = <?= json_encode(array(
    "id_usuario" => isset($_SESSION["id_usuario"]) ? intval($_SESSION["id_usuario"]) : 0
), JSON_UNESCAPED_UNICODE); ?>;
</script>
<script src="/assets/js/custom/apps/erp/ventas/caja_turnos.js?v=20260718-apertura-real1"></script>
</body>
</html>
