<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../">
    <title>Configuracion POS</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css">
    <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css">
    <!--
      Documentacion IA: Codex GPT-5, 2026-07-01.
      Proposito: preparar administracion POS de tiendas, cajas, terminales y asignaciones.
      Impacto: separa configuracion sensible del POS de mostrador.
      Contrato: CRUD productivo protegido por permisos finos y baja logica.
    -->
    <style>
        .pos-config-card { border: 1px solid #e6e8ee; border-radius: 8px; background: #fff; }
        .pos-config-kpi { min-height: 96px; }
        .pos-config-table { max-height: 420px; overflow: auto; }
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
                                <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Configuracion POS</h1>
                                <span class="text-muted">Tiendas, cajas, terminales y asignaciones oficiales</span>
                                <div class="d-flex flex-wrap gap-2 mt-2">
                                    <span class="badge badge-light-primary">Configuracion separada del mostrador</span>
                                    <span class="badge badge-light-warning">Validar sin crear = no guarda registros</span>
                                    <span class="badge badge-light-success">Guardar requiere permiso administrativo</span>
                                </div>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <a class="btn btn-light" href="/ventas/mostrar"><i class="bi bi-receipt"></i> Ventas</a>
                                <a class="btn btn-light-primary" href="/ventas/caja_turnos"><i class="bi bi-clock-history"></i> Turnos</a>
                                <a class="btn btn-light-primary" href="/ventas/caja_movimientos"><i class="bi bi-cash-stack"></i> Movimientos</a>
                                <a class="btn btn-light-primary" href="/ventas/caja_evidencias"><i class="bi bi-file-earmark-check"></i> Evidencias</a>
                                <a class="btn btn-light-primary" href="/ventas/reportes"><i class="bi bi-bar-chart"></i> Reportes</a>
                                <a class="btn btn-primary" href="/ventas/pos"><i class="bi bi-shop-window"></i> POS</a>
                            </div>
                        </div>
                    </div>
                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <div id="pos_config_alerta" class="mb-4"></div>
                            <div class="row g-3 mb-4">
                                <div class="col-md-3"><div class="pos-config-card pos-config-kpi p-4"><div class="text-muted fs-8 text-uppercase">Cajas</div><div class="fw-bold fs-2" id="pos_config_kpi_cajas">0</div></div></div>
                                <div class="col-md-3"><div class="pos-config-card pos-config-kpi p-4"><div class="text-muted fs-8 text-uppercase">Terminales</div><div class="fw-bold fs-2" id="pos_config_kpi_terminales">0</div></div></div>
                                <div class="col-md-3"><div class="pos-config-card pos-config-kpi p-4"><div class="text-muted fs-8 text-uppercase">Asignaciones</div><div class="fw-bold fs-2" id="pos_config_kpi_asignaciones">0</div></div></div>
                                <div class="col-md-3"><div class="pos-config-card pos-config-kpi p-4"><div class="text-muted fs-8 text-uppercase">Modo</div><div class="fw-bold fs-5">Administracion POS</div></div></div>
                            </div>
                            <div class="pos-config-card p-4 mb-4">
                                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                                    <div>
                                        <div class="fw-bold fs-5">Politica operativa</div>
                                        <div class="text-muted fs-7">El POS real debe abrir con tienda/caja/terminal asignadas, no con selector libre</div>
                                    </div>
                                    <button class="btn btn-light-primary" id="pos_config_recargar" type="button"><i class="bi bi-arrow-clockwise"></i> Actualizar</button>
                                </div>
                            </div>
                            <div class="pos-config-card p-4 mb-4">
                                <div class="fw-bold fs-5 mb-1">Validaciones de alta</div>
                                <div class="text-muted fs-7 mb-4">Valida primero cuando tengas duda. Guardar crea o actualiza configuracion oficial sin abrir turnos ni mover caja.</div>
                                <ul class="nav nav-tabs nav-line-tabs mb-5 fs-7" role="tablist">
                                    <li class="nav-item" role="presentation"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#pos_config_tab_caja" type="button" role="tab">Caja</button></li>
                                    <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#pos_config_tab_terminal" type="button" role="tab">Terminal</button></li>
                                    <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#pos_config_tab_asignacion" type="button" role="tab">Asignacion</button></li>
                                </ul>
                                <div class="tab-content">
                                    <div class="tab-pane fade show active" id="pos_config_tab_caja" role="tabpanel">
                                        <div class="row g-3 align-items-end">
                                            <input type="hidden" id="pos_cfg_caja_id">
                                            <div class="col-md-3"><label class="form-label text-muted fs-8 text-uppercase">Tienda</label><select class="form-select form-select-solid" id="pos_cfg_caja_almacen"></select></div>
                                            <div class="col-md-3"><label class="form-label text-muted fs-8 text-uppercase">Codigo</label><input class="form-control form-control-solid" id="pos_cfg_caja_codigo" placeholder="CJ-TIENDA-01"></div>
                                            <div class="col-md-4"><label class="form-label text-muted fs-8 text-uppercase">Nombre</label><input class="form-control form-control-solid" id="pos_cfg_caja_nombre" placeholder="Caja principal"></div>
                                            <div class="col-md-2 d-grid gap-2"><button class="btn btn-light-primary" id="pos_cfg_caja_validar" type="button"><i class="bi bi-check2-circle"></i> Validar</button><button class="btn btn-primary" id="pos_cfg_caja_guardar" type="button"><i class="bi bi-save"></i> Guardar</button></div>
                                            <div class="col-12 d-flex flex-wrap gap-4">
                                                <label class="form-check form-check-custom form-check-solid"><input class="form-check-input" id="pos_cfg_caja_efectivo" type="checkbox" checked><span class="form-check-label">Efectivo</span></label>
                                                <label class="form-check form-check-custom form-check-solid"><input class="form-check-input" id="pos_cfg_caja_tarjeta" type="checkbox" checked><span class="form-check-label">Tarjeta</span></label>
                                                <label class="form-check form-check-custom form-check-solid"><input class="form-check-input" id="pos_cfg_caja_transferencia" type="checkbox" checked><span class="form-check-label">Transferencia</span></label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="tab-pane fade" id="pos_config_tab_terminal" role="tabpanel">
                                        <div class="row g-3 align-items-end">
                                            <input type="hidden" id="pos_cfg_terminal_id">
                                            <div class="col-md-3"><label class="form-label text-muted fs-8 text-uppercase">Tienda</label><select class="form-select form-select-solid" id="pos_cfg_terminal_almacen"></select></div>
                                            <div class="col-md-3"><label class="form-label text-muted fs-8 text-uppercase">Caja</label><select class="form-select form-select-solid" id="pos_cfg_terminal_caja"></select></div>
                                            <div class="col-md-3"><label class="form-label text-muted fs-8 text-uppercase">Codigo</label><input class="form-control form-control-solid" id="pos_cfg_terminal_codigo" placeholder="TERM-TIENDA-01"></div>
                                            <div class="col-md-3"><label class="form-label text-muted fs-8 text-uppercase">Nombre</label><input class="form-control form-control-solid" id="pos_cfg_terminal_nombre" placeholder="Terminal principal"></div>
                                            <div class="col-md-9"><label class="form-label text-muted fs-8 text-uppercase">Identificador terminal</label><input class="form-control form-control-solid" id="pos_cfg_terminal_identificador" placeholder="Huella local futura de dispositivo/navegador"></div>
                                            <div class="col-md-3 d-grid gap-2"><button class="btn btn-light-primary" id="pos_cfg_terminal_validar" type="button"><i class="bi bi-check2-circle"></i> Validar</button><button class="btn btn-primary" id="pos_cfg_terminal_guardar" type="button"><i class="bi bi-save"></i> Guardar</button></div>
                                        </div>
                                    </div>
                                    <div class="tab-pane fade" id="pos_config_tab_asignacion" role="tabpanel">
                                        <div class="row g-3 align-items-end">
                                            <input type="hidden" id="pos_cfg_asig_id">
                                            <div class="col-md-2"><label class="form-label text-muted fs-8 text-uppercase">Usuario</label><input class="form-control form-control-solid" id="pos_cfg_asig_usuario" inputmode="numeric" placeholder="ID"></div>
                                            <div class="col-md-3"><label class="form-label text-muted fs-8 text-uppercase">Tienda</label><select class="form-select form-select-solid" id="pos_cfg_asig_almacen"></select></div>
                                            <div class="col-md-3"><label class="form-label text-muted fs-8 text-uppercase">Caja</label><select class="form-select form-select-solid" id="pos_cfg_asig_caja"></select></div>
                                            <div class="col-md-3"><label class="form-label text-muted fs-8 text-uppercase">Terminal</label><select class="form-select form-select-solid" id="pos_cfg_asig_terminal"></select></div>
                                            <div class="col-md-1"><label class="form-label text-muted fs-8 text-uppercase">Prior.</label><input class="form-control form-control-solid" id="pos_cfg_asig_prioridad" inputmode="numeric" value="1"></div>
                                            <div class="col-12 d-flex flex-wrap gap-2"><button class="btn btn-light-primary" id="pos_cfg_asig_validar" type="button"><i class="bi bi-check2-circle"></i> Validar</button><button class="btn btn-primary" id="pos_cfg_asig_guardar" type="button"><i class="bi bi-save"></i> Guardar asignacion</button><button class="btn btn-light" id="pos_cfg_limpiar" type="button"><i class="bi bi-eraser"></i> Limpiar captura</button></div>
                                        </div>
                                    </div>
                                </div>
                                <div id="pos_config_validacion_resultado" class="mt-4"></div>
                            </div>
                            <div class="row g-4">
                                <div class="col-xl-6">
                                    <div class="pos-config-card p-4 mb-4">
                                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                                            <div class="fw-bold fs-5">Cajas</div>
                                            <div class="btn-group btn-group-sm" role="group" aria-label="Filtro cajas">
                                                <button class="btn btn-light-primary active" type="button" data-pos-filtro="cajas" data-valor="activos">Activos</button>
                                                <button class="btn btn-light" type="button" data-pos-filtro="cajas" data-valor="historico">Historial</button>
                                                <button class="btn btn-light" type="button" data-pos-filtro="cajas" data-valor="todos">Todos</button>
                                            </div>
                                        </div>
                                        <div class="table-responsive pos-config-table">
                                            <table class="table align-middle table-row-dashed gy-3 mb-0">
                                                <thead><tr class="text-muted fw-bold fs-8 text-uppercase"><th>Caja</th><th>Tienda</th><th>Metodos</th><th>Estatus</th><th class="text-end">Acciones</th></tr></thead>
                                                <tbody id="pos_config_cajas"></tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xl-6">
                                    <div class="pos-config-card p-4 mb-4">
                                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                                            <div class="fw-bold fs-5">Terminales</div>
                                            <div class="btn-group btn-group-sm" role="group" aria-label="Filtro terminales">
                                                <button class="btn btn-light-primary active" type="button" data-pos-filtro="terminales" data-valor="activos">Activos</button>
                                                <button class="btn btn-light" type="button" data-pos-filtro="terminales" data-valor="historico">Historial</button>
                                                <button class="btn btn-light" type="button" data-pos-filtro="terminales" data-valor="todos">Todos</button>
                                            </div>
                                        </div>
                                        <div class="table-responsive pos-config-table">
                                            <table class="table align-middle table-row-dashed gy-3 mb-0">
                                                <thead><tr class="text-muted fw-bold fs-8 text-uppercase"><th>Terminal</th><th>Tienda</th><th>Caja</th><th>Estatus</th><th class="text-end">Acciones</th></tr></thead>
                                                <tbody id="pos_config_terminales"></tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="pos-config-card p-4">
                                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                                            <div class="fw-bold fs-5">Asignaciones usuario/caja</div>
                                            <div class="btn-group btn-group-sm" role="group" aria-label="Filtro asignaciones">
                                                <button class="btn btn-light-primary active" type="button" data-pos-filtro="asignaciones" data-valor="activos">Activos</button>
                                                <button class="btn btn-light" type="button" data-pos-filtro="asignaciones" data-valor="historico">Historial</button>
                                                <button class="btn btn-light" type="button" data-pos-filtro="asignaciones" data-valor="todos">Todos</button>
                                            </div>
                                        </div>
                                        <div class="table-responsive pos-config-table">
                                            <table class="table align-middle table-row-dashed gy-3 mb-0">
                                                <thead><tr class="text-muted fw-bold fs-8 text-uppercase"><th>Usuario</th><th>Tienda</th><th>Caja</th><th>Terminal</th><th>Prioridad</th><th>Estatus</th><th class="text-end">Acciones</th></tr></thead>
                                                <tbody id="pos_config_asignaciones"></tbody>
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
<script src="/assets/js/custom/apps/erp/ventas/pos_configuracion.js?v=20260704-filtros-historico1"></script>
</body>
</html>
