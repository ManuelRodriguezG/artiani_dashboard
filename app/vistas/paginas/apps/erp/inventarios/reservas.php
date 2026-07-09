<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../">
    <title>Reservas de inventario</title>
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
                                <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Reservas de inventario</h1>
                                <span class="text-muted">Apartados operativos que comprometen disponible sin descontar stock fisico</span>
                            </div>
                            <a class="btn btn-light-primary" href="/inventario/productos_existencias"><i class="bi bi-boxes"></i> Existencias</a>
                        </div>
                    </div>
                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <div class="card mb-7">
                                <div class="card-header border-0 pt-5">
                                    <div class="card-title"><h3 class="fw-bold mb-0">Nueva reserva</h3></div>
                                </div>
                                <div class="card-body pt-3">
                                    <div class="row g-4 align-items-end">
                                        <div class="col-md-3"><label class="form-label required">Almacen</label><select class="form-select" id="reserva_almacen"></select></div>
                                        <div class="col-md-4"><label class="form-label required">SKU / existencia / lote</label><input class="form-control" id="reserva_busqueda" placeholder="Buscar existencia disponible"></div>
                                        <div class="col-md-2"><button class="btn btn-light-primary w-100" type="button" id="reserva_buscar"><i class="bi bi-search"></i> Buscar</button></div>
                                        <div class="col-md-3"><label class="form-label required">Cantidad</label><input class="form-control" id="reserva_cantidad" inputmode="decimal" placeholder="0.0000"></div>
                                        <div class="col-md-3"><label class="form-label">Origen</label><select class="form-select" id="reserva_origen"><option value="reserva_manual">Reserva manual</option><option value="mayoreo">Mayoreo</option><option value="alianza_comercial">Alianza comercial</option><option value="pedido_cliente">Pedido cliente</option><option value="uat_reserva">UAT</option></select></div>
                                        <div class="col-md-3"><label class="form-label">Vencimiento</label><input class="form-control" type="date" id="reserva_vencimiento"></div>
                                        <div class="col-md-6"><label class="form-label">Observaciones</label><input class="form-control" id="reserva_observaciones" maxlength="500"></div>
                                        <div class="col-12">
                                            <div class="border rounded p-4 bg-light d-none" id="reserva_seleccion"></div>
                                        </div>
                                        <div class="col-12">
                                            <div class="table-responsive">
                                                <table class="table align-middle table-row-dashed gy-4 mb-0">
                                                    <thead><tr class="text-muted fw-bold fs-7 text-uppercase"><th>Existencia</th><th>SKU / producto</th><th>Almacen</th><th>Lote / ubicacion</th><th>Cantidad</th><th>Disponible</th><th class="text-end">Accion</th></tr></thead>
                                                    <tbody id="reserva_existencias"></tbody>
                                                </table>
                                            </div>
                                        </div>
                                        <div class="col-md-3 ms-auto"><button class="btn btn-primary w-100" type="button" id="reserva_crear"><i class="bi bi-bookmark-plus"></i> Crear reserva</button></div>
                                    </div>
                                </div>
                            </div>

                            <div class="card">
                                <div class="card-header border-0 pt-5">
                                    <div class="card-title"><h3 class="fw-bold mb-0">Reservas</h3></div>
                                    <div class="card-toolbar d-flex gap-2">
                                        <select class="form-select form-select-sm w-150px" id="reserva_estatus"><option value="">Todas</option><option value="activa">Activas</option><option value="liberada">Liberadas</option><option value="consumida">Consumidas</option><option value="vencida">Vencidas</option><option value="cancelada">Canceladas</option></select>
                                        <button class="btn btn-light-primary btn-sm" id="reserva_recargar" type="button"><i class="bi bi-arrow-clockwise"></i> Actualizar</button>
                                    </div>
                                </div>
                                <div class="card-body pt-3">
                                    <div class="table-responsive">
                                        <table class="table align-middle table-row-dashed gy-4">
                                            <thead><tr class="text-muted fw-bold fs-7 text-uppercase"><th>Folio</th><th>SKU / existencia</th><th>Almacen</th><th>Lote</th><th>Reservada</th><th>Pendiente</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
                                            <tbody id="reserva_listado"></tbody>
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
<script src="assets/plugins/global/plugins.bundle.js"></script>
<script src="assets/js/scripts.bundle.js"></script>
<script src="/assets/js/custom/apps/erp/inventarios/reservas_erp.js?v=20260622-1"></script>
</body>
</html>
