<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../">
    <title>Transición a almacén único</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css">
    <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css">
    <!--
      IA: Codex GPT-5
      Fecha: 2026-11-02
      Proposito: hacer visible la auditoria de consolidacion operativa sin ejecutar cambios.
      Impacto: Compras/Recepcion; solo lectura.
      Contrato: el endpoint consultado no escribe BD ni mueve inventario.
    -->
    <style>
        .ta-card { border: 1px solid #e6e8ee; border-radius: 8px; background: #fff; }
        .ta-table-wrap { overflow-x: auto; }
        .ta-table { min-width: 760px; }
        .ta-muted { color: #7e8299; }
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
                                <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Transición a almacén único</h1>
                                <span class="text-muted">Auditoría de Compras y Recepción antes de consolidar la operación</span>
                                <div class="d-flex flex-wrap gap-2 mt-2">
                                    <span class="badge badge-light-primary">Solo lectura</span>
                                    <span class="badge badge-light-warning">No mueve inventario</span>
                                    <span class="badge badge-light-info">No cambia documentos</span>
                                </div>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <a class="btn btn-light" href="/compra/mostrar_solicitudes"><i class="bi bi-list-check"></i> Solicitudes</a>
                                <a class="btn btn-light" href="/compra/mostrar_compra_ordenes"><i class="bi bi-cart-check"></i> Órdenes</a>
                                <button class="btn btn-primary" type="button" id="ta_btn_refrescar"><i class="bi bi-arrow-clockwise"></i> Actualizar</button>
                            </div>
                        </div>
                    </div>
                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <div id="ta_alerta" class="mb-4"></div>
                            <div class="row g-4 mb-4" id="ta_resumen">
                                <div class="col-md-4"><div class="ta-card p-5"><div class="ta-muted">Sin destino</div><div class="fs-2 fw-bold" id="ta_sin_destino">0</div><div class="fs-7 ta-muted">Requiere definir operación general antes de continuar</div></div></div>
                                <div class="col-md-4"><div class="ta-card p-5"><div class="ta-muted">Revisables</div><div class="fs-2 fw-bold text-warning" id="ta_migrables">0</div><div class="fs-7 ta-muted">Borradores que pueden revisarse</div></div></div>
                                <div class="col-md-4"><div class="ta-card p-5"><div class="ta-muted">Conservar origen</div><div class="fs-2 fw-bold text-primary" id="ta_conservar">0</div><div class="fs-7 ta-muted">Flujos iniciados o recepciones existentes</div></div></div>
                            </div>
                            <div class="ta-card p-5 mb-4">
                                <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap mb-3">
                                    <div><div class="fw-bold fs-4">Almacenes registrados</div><div class="ta-muted fs-7">Referencia para elegir la futura operación general; no se desactiva nada desde aquí.</div></div>
                                </div>
                                <div class="ta-table-wrap"><table class="table align-middle ta-table"><thead><tr class="text-muted fw-bold fs-8 text-uppercase"><th>Código</th><th>Almacén</th><th>Tipo</th><th>Estatus</th><th>Recepción</th><th>Venta</th></tr></thead><tbody id="ta_almacenes"><tr><td colspan="6" class="text-center ta-muted py-6">Consultando...</td></tr></tbody></table></div>
                            </div>
                            <div class="row g-4">
                                <div class="col-xl-4"><div class="ta-card p-5 h-100"><div class="fw-bold fs-4 mb-3">Solicitudes</div><div class="ta-table-wrap"><table class="table table-sm align-middle ta-table"><thead><tr class="text-muted fw-bold fs-8 text-uppercase"><th>Folio</th><th>Destino</th><th>Estado</th><th>Clasificación</th></tr></thead><tbody id="ta_solicitudes"><tr><td colspan="4" class="text-center ta-muted py-6">Consultando...</td></tr></tbody></table></div></div></div>
                                <div class="col-xl-4"><div class="ta-card p-5 h-100"><div class="fw-bold fs-4 mb-3">Órdenes de compra</div><div class="ta-table-wrap"><table class="table table-sm align-middle ta-table"><thead><tr class="text-muted fw-bold fs-8 text-uppercase"><th>Folio</th><th>Destino</th><th>Estado</th><th>Total</th><th>Clasificación</th></tr></thead><tbody id="ta_ordenes"><tr><td colspan="5" class="text-center ta-muted py-6">Consultando...</td></tr></tbody></table></div></div></div>
                                <div class="col-xl-4"><div class="ta-card p-5 h-100"><div class="fw-bold fs-4 mb-3">Recepciones</div><div class="ta-table-wrap"><table class="table table-sm align-middle ta-table"><thead><tr class="text-muted fw-bold fs-8 text-uppercase"><th>Folio</th><th>Destino</th><th>Estado</th><th>Clasificación</th></tr></thead><tbody id="ta_recepciones"><tr><td colspan="4" class="text-center ta-muted py-6">Consultando...</td></tr></tbody></table></div></div></div>
                            </div>
                            <div class="alert alert-light-warning mt-4 mb-0"><div class="fw-bold">Regla de transición</div><div class="fs-7">Los documentos nuevos podrán usar una operación general cuando se autorice su configuración. Las órdenes, recepciones y movimientos que ya iniciaron flujo conservan su almacén de origen para no romper trazabilidad.</div></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>window.ERP_CSRF_TOKEN = <?= json_encode(Sesionseguridad::csrfToken(), JSON_UNESCAPED_UNICODE) ?>;</script>
<script src="/assets/plugins/global/plugins.bundle.js"></script>
<script src="/assets/js/scripts.bundle.js"></script>
<script src="/assets/js/custom/apps/erp/compras/auditoria_transicion_almacen.js?v=20261102-transicion1"></script>
</body>
</html>
