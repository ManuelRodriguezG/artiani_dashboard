<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../">
    <title>Evidencias de caja POS</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css">
    <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css">
    <!--
      Documentacion IA: Codex GPT-5, 2026-07-02.
      Proposito: separar evidencias y comprobantes sensibles de caja POS.
      Impacto: permite seguimiento read-only sin aprobar, rechazar ni adjuntar desde esta vista.
      Contrato: consulta informacion; acciones reales quedan en flujo autorizado.
    -->
    <style>
        .pos-admin-card { border: 1px solid #e6e8ee; border-radius: 8px; background: #fff; }
        .pos-admin-result { max-height: 520px; overflow: auto; }
        .pos-empty { min-height: 220px; border: 1px dashed #d7dbe4; border-radius: 8px; }
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
                                <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Evidencias de caja POS</h1>
                                <span class="text-muted">Comprobantes, revisiones y correcciones de movimientos sensibles</span>
                                <div class="d-flex flex-wrap gap-2 mt-2">
                                    <span class="badge badge-light-primary">Consulta = no modifica caja</span>
                                    <span class="badge badge-light-warning">Detalle = no aprueba evidencia</span>
                                    <span class="badge badge-light-danger">Correcciones reales requieren autorizacion</span>
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
                            <div class="alert alert-info py-3 mb-4">
                                <div class="fw-bold">Seguimiento documental de caja</div>
                                <div class="fs-7">Esta pantalla consulta movimientos que requieren comprobante y evidencia capturada. No aprueba, rechaza, reemplaza ni corrige evidencias.</div>
                            </div>
                            <div class="row g-4">
                                <div class="col-xl-4">
                                    <div class="pos-admin-card p-4">
                                        <div class="fw-bold fs-5 mb-1">Filtros</div>
                                        <div class="text-muted fs-7 mb-4">Busca pendientes o evidencia capturada</div>
                                        <div class="row g-3">
                                            <div class="col-12">
                                                <label class="form-label text-muted fs-8 text-uppercase">Estado movimiento</label>
                                                <select class="form-select form-select-solid" id="pos_evc_estado_movimiento">
                                                    <option value="pendiente">Pendiente</option>
                                                    <option value="recibida">Recibida</option>
                                                    <option value="aprobada">Aprobada</option>
                                                    <option value="rechazada">Rechazada</option>
                                                    <option value="todos">Todos</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label text-muted fs-8 text-uppercase">Almacen</label>
                                                <select class="form-select form-select-solid" id="pos_evc_almacen"></select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label text-muted fs-8 text-uppercase">Caja</label>
                                                <select class="form-select form-select-solid" id="pos_evc_caja"></select>
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label text-muted fs-8 text-uppercase">Movimiento caja</label>
                                                <input class="form-control form-control-solid" id="pos_evc_movimiento" inputmode="numeric" placeholder="Opcional">
                                            </div>
                                            <div class="col-12 d-grid gap-2">
                                                <button class="btn btn-primary" id="pos_evc_consultar" type="button"><i class="bi bi-search"></i> Consultar movimientos</button>
                                                <button class="btn btn-light-primary" id="pos_evc_detalle" type="button"><i class="bi bi-file-earmark-text"></i> Ver evidencias</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xl-8">
                                    <div class="pos-admin-card p-4">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <div>
                                                <div class="fw-bold fs-5">Resultado</div>
                                                <div class="text-muted fs-7">Lectura operativa sin acciones reales</div>
                                            </div>
                                            <button class="btn btn-sm btn-light-primary" id="pos_evc_recargar" type="button"><i class="bi bi-arrow-clockwise"></i></button>
                                        </div>
                                        <div id="pos_evc_resultado" class="pos-admin-result"></div>
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
<script src="/assets/js/custom/apps/erp/ventas/caja_evidencias.js?v=20260702-readonly1"></script>
</body>
</html>
