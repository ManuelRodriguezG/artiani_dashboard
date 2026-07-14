<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../">
    <title>Resurtido entre tiendas</title>
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
                        <div class="app-container container-fluid d-flex flex-stack">
                            <div>
                                <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Resurtido entre tiendas</h1>
                                <span class="text-muted">Solicitud, preparacion, transito y recepcion por folio</span>
                            </div>
                            <div class="d-flex gap-2">
                                                <button class="btn btn-light-primary" id="alm_res_btn_stock" type="button"><i class="bi bi-graph-down-arrow"></i> Stock bajo</button>
                                                <button class="btn btn-light-info" id="alm_res_btn_resumen" type="button"><i class="bi bi-shop"></i> Resumen tiendas</button>
                                                <button class="btn btn-light-dark" id="alm_res_btn_estados" type="button"><i class="bi bi-diagram-3"></i> Estados</button>
                                                <button class="btn btn-light-warning" id="alm_res_btn_prep_envio" type="button"><i class="bi bi-box-seam"></i> Prep/envio</button>
                                                <button class="btn btn-light-success" id="alm_res_btn_plan_prep" type="button"><i class="bi bi-list-check"></i> Plan prep</button>
                                                <button class="btn btn-light-danger" id="alm_res_btn_recepcion" type="button"><i class="bi bi-clipboard2-check"></i> Recepcion</button>
                                                <button class="btn btn-light-primary" id="alm_res_btn_acciones" type="button"><i class="bi bi-lightning-charge"></i> Acciones</button>
                                                <button class="btn btn-light-success" id="alm_res_btn_politicas" type="button"><i class="bi bi-sliders"></i> Politicas</button>
                                                <button class="btn btn-primary" id="alm_res_btn_nuevo" type="button" disabled title="Pendiente de esquema autorizado"><i class="bi bi-plus-circle"></i> Nueva solicitud</button>
                                            </div>
                        </div>
                    </div>
                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <div class="alert alert-info d-none" id="alm_res_schema_alert"></div>
                            <div class="row g-5">
                                <div class="col-xl-4">
                                    <div class="card">
                                        <div class="card-header">
                                            <div class="card-title">
                                                <h3 class="fw-bold m-0">Stock bajo</h3>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <div class="mb-4">
                                                <label class="form-label">Tienda/almacen</label>
                                                <select class="form-select form-select-solid" id="alm_res_stock_almacen"></select>
                                            </div>
                                            <div class="mb-4">
                                                <label class="form-label">Origen sugerido</label>
                                                <select class="form-select form-select-solid" id="alm_res_stock_origen"></select>
                                            </div>
                                            <div class="mb-4">
                                                <label class="form-label">SKU o producto</label>
                                                <input class="form-control form-control-solid" id="alm_res_stock_q" placeholder="Buscar SKU o producto">
                                            </div>
                                            <div class="form-check form-switch form-check-custom form-check-solid mb-4">
                                                <input class="form-check-input" id="alm_res_stock_solo_bajos" type="checkbox" checked>
                                                <label class="form-check-label" for="alm_res_stock_solo_bajos">Solo bajo reorden</label>
                                            </div>
                                            <button class="btn btn-primary w-100" id="alm_res_stock_buscar" type="button"><i class="bi bi-search"></i> Consultar</button>
                                            <button class="btn btn-light-primary w-100 mt-3" id="alm_res_simular" type="button"><i class="bi bi-file-earmark-text"></i> Simular solicitud</button>
                                            <button class="btn btn-light-warning w-100 mt-3" id="alm_res_validar" type="button"><i class="bi bi-shield-check"></i> Validar solicitud</button>
                                            <button class="btn btn-light-info w-100 mt-3" id="alm_res_payload" type="button"><i class="bi bi-braces"></i> Payload RES-T008</button>
                                            <div class="separator my-5"></div>
                                            <div id="alm_res_stock_resumen" class="text-muted">Selecciona una tienda para calcular necesidades.</div>
                                            <div class="separator my-5"></div>
                                            <div id="alm_res_simulacion" class="text-muted">Sin simulacion generada.</div>
                                            <div class="separator my-5"></div>
                                            <div id="alm_res_validacion" class="text-muted">Validacion pendiente.</div>
                                            <div class="separator my-5"></div>
                                            <div id="alm_res_payload_preview" class="text-muted">Payload pendiente.</div>
                                            <div class="separator my-5"></div>
                                            <div id="alm_res_resumen_tiendas" class="text-muted">Resumen multi-tienda pendiente.</div>
                                        </div>
                                    </div>
                                    <div class="card mt-5">
                                        <div class="card-header">
                                            <div class="card-title">
                                                <h3 class="fw-bold m-0">Detalle folio</h3>
                                            </div>
                                        </div>
                                        <div class="card-body" id="alm_res_detalle">
                                            Selecciona una solicitud para revisar partidas y trazabilidad.
                                        </div>
                                    </div>
                                    <div class="card mt-5">
                                        <div class="card-header">
                                            <div class="card-title">
                                                <h3 class="fw-bold m-0">Estados</h3>
                                            </div>
                                        </div>
                                        <div class="card-body" id="alm_res_estados_panel">
                                            Contrato operativo pendiente de consultar.
                                        </div>
                                    </div>
                                    <div class="card mt-5">
                                        <div class="card-header">
                                            <div class="card-title">
                                                <h3 class="fw-bold m-0">Preparacion/envio</h3>
                                            </div>
                                        </div>
                                        <div class="card-body" id="alm_res_prep_envio_panel">
                                            Contrato tecnico pendiente de consultar.
                                        </div>
                                    </div>
                                    <div class="card mt-5">
                                        <div class="card-header">
                                            <div class="card-title">
                                                <h3 class="fw-bold m-0">Plan preparacion</h3>
                                            </div>
                                        </div>
                                        <div class="card-body" id="alm_res_plan_prep_panel">
                                            Plan FEFO pendiente de consultar.
                                        </div>
                                    </div>
                                    <div class="card mt-5">
                                        <div class="card-header">
                                            <div class="card-title">
                                                <h3 class="fw-bold m-0">Payload RES-T009</h3>
                                            </div>
                                        </div>
                                        <div class="card-body" id="alm_res_payload_prep_panel">
                                            Payload de preparacion/envio pendiente.
                                        </div>
                                    </div>
                                    <div class="card mt-5">
                                        <div class="card-header">
                                            <div class="card-title">
                                                <h3 class="fw-bold m-0">Recepcion/diferencias</h3>
                                            </div>
                                        </div>
                                        <div class="card-body" id="alm_res_recepcion_panel">
                                            Contrato tecnico pendiente de consultar.
                                        </div>
                                    </div>
                                    <div class="card mt-5">
                                        <div class="card-header">
                                            <div class="card-title">
                                                <h3 class="fw-bold m-0">Politicas/alertas</h3>
                                            </div>
                                        </div>
                                        <div class="card-body" id="alm_res_politicas_panel">
                                            Contrato tecnico pendiente de consultar.
                                        </div>
                                    </div>
                                    <div class="card mt-5">
                                        <div class="card-header">
                                            <div class="card-title">
                                                <h3 class="fw-bold m-0">Acciones</h3>
                                            </div>
                                        </div>
                                        <div class="card-body" id="alm_res_acciones_panel">
                                            Contrato de acciones pendiente de consultar.
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xl-8">
                                    <div class="card">
                                        <div class="card-header border-0 pt-5">
                                            <div class="card-title">
                                                <div class="d-flex align-items-center position-relative my-1">
                                                    <i class="bi bi-search fs-3 position-absolute ms-5"></i>
                                                    <input class="form-control form-control-solid w-300px ps-12" id="alm_res_q" placeholder="Buscar folio o almacen">
                                                </div>
                                            </div>
                                            <div class="card-toolbar d-flex gap-3">
                                                <select class="form-select form-select-solid w-200px" id="alm_res_estatus">
                                                    <option value="">Todos</option>
                                                    <option value="solicitado">Solicitado</option>
                                                    <option value="autorizado">Autorizado</option>
                                                    <option value="preparando">Preparando</option>
                                                    <option value="preparado">Preparado</option>
                                                    <option value="enviado">Enviado</option>
                                                    <option value="recibido_parcial">Recibido parcial</option>
                                                    <option value="recibido">Recibido</option>
                                                    <option value="cerrado">Cerrado</option>
                                                    <option value="cancelado">Cancelado</option>
                                                </select>
                                                <button class="btn btn-light-primary" id="alm_res_recargar" type="button"><i class="bi bi-arrow-clockwise"></i> Actualizar</button>
                                            </div>
                                        </div>
                                        <div class="card-body pt-0">
                                            <div class="table-responsive">
                                                <table class="table align-middle table-row-dashed gy-4" style="min-width: 980px;">
                                                    <thead>
                                                        <tr class="text-muted fw-bold fs-7 text-uppercase">
                                                            <th>Folio</th>
                                                            <th>Origen</th>
                                                            <th>Destino</th>
                                                            <th>Estado</th>
                                                            <th class="text-end">Partidas</th>
                                                            <th class="text-end">Solicitado</th>
                                                            <th class="text-end">Enviado</th>
                                                            <th class="text-end">Recibido</th>
                                                            <th class="text-end">Dif.</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="alm_res_body"></tbody>
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
</div>
<script src="assets/plugins/global/plugins.bundle.js"></script>
<script src="assets/js/scripts.bundle.js"></script>
<script src="/assets/js/custom/apps/erp/almacen/resurtido/resurtido.js?v=20260713-2"></script>
</body>
</html>
