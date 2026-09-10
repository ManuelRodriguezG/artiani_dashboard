<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../">
    <title>Distribucion</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css">
    <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css">
</head>
<body id="kt_app_body" data-kt-app-layout="dark-sidebar" data-kt-app-header-fixed="true" data-kt-app-sidebar-enabled="true" data-kt-app-sidebar-fixed="true" class="app-default">
    <script>
        window.DISTRIBUCION_ADMIN_PERMISOS = {
            editar: <?= SesionSeguridad::tienePermiso('distribucion.editar') ? 'true' : 'false' ?>,
            aprobar: <?= SesionSeguridad::tienePermiso('distribucion.aprobar_clientes') ? 'true' : 'false' ?>,
            asignar_precios: <?= SesionSeguridad::tienePermiso('distribucion.asignar_precios') ? 'true' : 'false' ?>,
            cotizaciones_gestionar: <?= SesionSeguridad::tienePermiso('distribucion.cotizaciones.gestionar') ? 'true' : 'false' ?>
        };
    </script>
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
                                    <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Distribucion</h1>
                                    <span class="text-muted">Clientes externos, permisos comerciales y solicitudes de pedido</span>
                                </div>
                                <button type="button" id="distribucion_refrescar" class="btn btn-light-primary">
                                    <i class="bi bi-arrow-clockwise"></i>
                                    Refrescar
                                </button>
                            </div>
                        </div>
                        <div class="app-content flex-column-fluid">
                            <div class="app-container container-fluid">
                                <ul class="nav nav-tabs nav-line-tabs nav-line-tabs-2x mb-5 fs-6" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#dist_tab_solicitudes" type="button" role="tab">Solicitudes</button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#dist_tab_clientes" type="button" role="tab">Clientes</button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#dist_tab_cotizaciones" type="button" role="tab">Pedidos</button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#dist_tab_productos" type="button" role="tab">Productos</button>
                                    </li>
                                </ul>
                                <div class="tab-content">
                                    <div class="tab-pane fade show active" id="dist_tab_solicitudes" role="tabpanel">
                                        <div class="card">
                                            <div class="card-header border-0 pt-6">
                                                <div class="card-title">
                                                    <div class="d-flex align-items-center position-relative my-1">
                                                        <i class="bi bi-search fs-3 position-absolute ms-5"></i>
                                                        <input type="text" id="dist_solicitudes_buscar" class="form-control form-control-solid w-250px ps-12" placeholder="Buscar solicitud">
                                                    </div>
                                                </div>
                                                <div class="card-toolbar">
                                                    <select id="dist_solicitudes_estatus" class="form-select form-select-solid w-175px">
                                                        <option value="">Todos</option>
                                                        <option value="pendiente">Pendientes</option>
                                                        <option value="aprobado">Aprobadas</option>
                                                        <option value="rechazado">Rechazadas</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="card-body pt-0">
                                                <div class="table-responsive">
                                                    <table class="table align-middle table-row-dashed fs-6 gy-5">
                                                        <thead>
                                                            <tr class="text-start text-muted fw-bold fs-7 text-uppercase">
                                                                <th>Solicitud</th>
                                                                <th>Contacto</th>
                                                                <th>Negocio</th>
                                                                <th>Ubicacion</th>
                                                                <th>Estado</th>
                                                                <th class="text-end">Acciones</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="dist_solicitudes_lista"></tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="tab-pane fade" id="dist_tab_clientes" role="tabpanel">
                                        <div class="card">
                                            <div class="card-header border-0 pt-6">
                                                <div class="card-title">
                                                    <div class="d-flex align-items-center position-relative my-1">
                                                        <i class="bi bi-search fs-3 position-absolute ms-5"></i>
                                                        <input type="text" id="dist_clientes_buscar" class="form-control form-control-solid w-250px ps-12" placeholder="Buscar cliente">
                                                    </div>
                                                </div>
                                                <div class="card-toolbar">
                                                    <select id="dist_clientes_estatus" class="form-select form-select-solid w-175px">
                                                        <option value="">Todos</option>
                                                        <option value="aprobado">Aprobados</option>
                                                        <option value="suspendido">Suspendidos</option>
                                                        <option value="rechazado">Rechazados</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="card-body pt-0">
                                                <div class="table-responsive">
                                                    <table class="table align-middle table-row-dashed fs-6 gy-5">
                                                        <thead>
                                                            <tr class="text-start text-muted fw-bold fs-7 text-uppercase">
                                                                <th>Cliente</th>
                                                                <th>Tipo</th>
                                                                <th>Lista</th>
                                                                <th>Permisos</th>
                                                                <th>Estado</th>
                                                                <th class="text-end">Acciones</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="dist_clientes_lista"></tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="tab-pane fade" id="dist_tab_cotizaciones" role="tabpanel">
                                        <div class="card">
                                            <div class="card-header border-0 pt-6">
                                                <div class="card-title">
                                                    <h2 class="fw-bold mb-0">Solicitudes recibidas</h2>
                                                </div>
                                                <div class="card-toolbar">
                                                    <span id="dist_cotizaciones_total" class="badge badge-light-primary">0</span>
                                                </div>
                                            </div>
                                            <div class="card-body pt-0">
                                                <div class="table-responsive">
                                                    <table class="table align-middle table-row-dashed fs-6 gy-5">
                                                        <thead>
                                                            <tr class="text-start text-muted fw-bold fs-7 text-uppercase">
                                                                <th>Folio</th>
                                                                <th>Cliente</th>
                                                                <th>Total</th>
                                                                <th>Estado</th>
                                                                <th>Fecha</th>
                                                                <th class="text-end">Acciones</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="dist_cotizaciones_lista"></tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="tab-pane fade" id="dist_tab_productos" role="tabpanel">
                                        <div class="card">
                                            <div class="card-header border-0 pt-6">
                                                <div class="card-title">
                                                    <div class="d-flex align-items-center position-relative my-1">
                                                        <i class="bi bi-search fs-3 position-absolute ms-5"></i>
                                                        <input type="text" id="dist_productos_buscar" class="form-control form-control-solid w-300px ps-12" placeholder="Buscar SKU o producto">
                                                    </div>
                                                </div>
                                                <div class="card-toolbar d-flex gap-3">
                                                    <button type="button" id="dist_productos_buscar_btn" class="btn btn-light-primary">
                                                        <i class="bi bi-search"></i>
                                                        Buscar
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="card-body pt-0">
                                                <div class="table-responsive">
                                                    <table class="table align-middle table-row-dashed fs-6 gy-5">
                                                        <thead>
                                                            <tr class="text-start text-muted fw-bold fs-7 text-uppercase">
                                                                <th>SKU</th>
                                                                <th>Producto</th>
                                                                <th>Slug</th>
                                                                <th>Estado canal</th>
                                                                <th class="text-end">Acciones</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="dist_productos_lista"></tbody>
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
    <script src="/assets/js/custom/apps/erp/distribucion/administracion.js"></script>
</body>
</html>
