<?php
include __DIR__ . '/resumen.php';
return;
$distSeccionActiva = isset($datos['seccion_activa']) ? $datos['seccion_activa'] : 'resumen';
$distSecciones = array(
    'resumen' => array('titulo' => 'Resumen', 'ruta' => '/distribucionadmin/panel_resumen', 'tab' => 'dist_tab_resumen'),
    'solicitudes' => array('titulo' => 'Solicitudes', 'ruta' => '/distribucionadmin/panel_solicitudes', 'tab' => 'dist_tab_solicitudes'),
    'clientes' => array('titulo' => 'Clientes', 'ruta' => '/distribucionadmin/panel_clientes', 'tab' => 'dist_tab_clientes'),
    'pedidos' => array('titulo' => 'Pedidos', 'ruta' => '/distribucionadmin/panel_pedidos', 'tab' => 'dist_tab_cotizaciones'),
    'mi_catalogo' => array('titulo' => 'Mi catalogo', 'ruta' => '/distribucionadmin/panel_mi_catalogo', 'tab' => 'dist_tab_mi_catalogo'),
    'inventarios' => array('titulo' => 'Inventarios', 'ruta' => '/distribucionadmin/panel_inventarios', 'tab' => 'dist_tab_inventarios'),
    'sugeridos' => array('titulo' => 'Sugeridos', 'ruta' => '/distribucionadmin/panel_sugeridos', 'tab' => 'dist_tab_sugeridos'),
    'productos' => array('titulo' => 'Productos', 'ruta' => '/distribucionadmin/panel_productos', 'tab' => 'dist_tab_productos'),
    'demanda' => array('titulo' => 'Demanda', 'ruta' => '/distribucionadmin/panel_demanda', 'tab' => 'dist_tab_demanda')
);
if (!isset($distSecciones[$distSeccionActiva])) {
    $distSeccionActiva = 'resumen';
}
$distTituloActivo = $distSecciones[$distSeccionActiva]['titulo'];
$distPaneClass = function ($seccion) use ($distSeccionActiva) {
    return $seccion === $distSeccionActiva ? 'tab-pane fade show active' : 'tab-pane fade d-none';
};
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../">
    <title>Distribucion ERP</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css">
    <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css">
    <style>
        .dist-kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 12px; }
        .dist-kpi { border: 1px solid var(--bs-gray-200); border-radius: 8px; padding: 14px 16px; background: var(--bs-body-bg); min-height: 96px; }
        .dist-kpi-value { font-size: 1.65rem; font-weight: 700; line-height: 1; }
        .dist-filter-row { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
        .dist-filter-row .form-control, .dist-filter-row .form-select { min-width: 170px; width: auto; }
        .dist-table-wrap { max-height: 62vh; overflow: auto; }
        .dist-table-wrap table thead th { position: sticky; top: 0; background: var(--bs-card-bg); z-index: 1; }
        .dist-section-note { border-left: 3px solid var(--bs-primary); padding: 10px 14px; background: var(--bs-gray-100); border-radius: 6px; }
        .dist-order-kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px; }
        .dist-order-kpi { border: 1px solid var(--bs-gray-200); border-radius: 6px; padding: 10px 12px; background: var(--bs-body-bg); }
        .dist-order-kpi .value { font-weight: 700; font-size: 1.05rem; }
    </style>
</head>
<body id="kt_app_body" data-kt-app-layout="dark-sidebar" data-kt-app-header-fixed="true" data-kt-app-sidebar-enabled="true" data-kt-app-sidebar-fixed="true" class="app-default">
    <script>
        window.ERP_CSRF_TOKEN = "<?= htmlspecialchars(Sesionseguridad::csrfToken(), ENT_QUOTES, 'UTF-8') ?>";
        window.DISTRIBUCION_ADMIN_PERMISOS = {
            editar: <?= Sesionseguridad::tienePermiso('distribucion.editar') ? 'true' : 'false' ?>,
            aprobar: <?= Sesionseguridad::tienePermiso('distribucion.aprobar_clientes') ? 'true' : 'false' ?>,
            asignar_precios: <?= Sesionseguridad::tienePermiso('distribucion.asignar_precios') ? 'true' : 'false' ?>,
            cotizaciones_gestionar: <?= Sesionseguridad::tienePermiso('distribucion.cotizaciones.gestionar') ? 'true' : 'false' ?>
        };
        window.DISTRIBUCION_ADMIN_SECCION = "<?= htmlspecialchars($distSeccionActiva, ENT_QUOTES, 'UTF-8') ?>";
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
                                    <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Distribucion / <?= htmlspecialchars($distTituloActivo, ENT_QUOTES, 'UTF-8') ?></h1>
                                    <span class="text-muted">Modulo interno separado por areas operativas</span>
                                </div>
                                <button type="button" id="distribucion_refrescar" class="btn btn-light-primary">
                                    <i class="bi bi-arrow-clockwise"></i>
                                    Refrescar
                                </button>
                            </div>
                        </div>
                        <div class="app-content flex-column-fluid">
                            <div class="app-container container-fluid">
                                <ul class="nav nav-tabs nav-line-tabs nav-line-tabs-2x mb-5 fs-6" role="navigation">
                                    <?php foreach ($distSecciones as $claveSeccion => $seccionMenu): ?>
                                        <li class="nav-item" role="presentation">
                                            <a class="nav-link <?= $claveSeccion === $distSeccionActiva ? 'active' : '' ?>" href="<?= htmlspecialchars($seccionMenu['ruta'], ENT_QUOTES, 'UTF-8') ?>">
                                                <?= htmlspecialchars($seccionMenu['titulo'], ENT_QUOTES, 'UTF-8') ?>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                                <div class="tab-content">
                                    <div class="<?= $distPaneClass('resumen') ?>" id="dist_tab_resumen" role="tabpanel">
                                        <div class="dist-kpi-grid mb-5" id="dist_resumen_kpis"></div>
                                        <div class="row g-5">
                                            <div class="col-xl-4">
                                                <div class="card">
                                                    <div class="card-header border-0"><h3 class="card-title">Reciente en Mi catalogo</h3></div>
                                                    <div class="card-body pt-0" id="dist_resumen_recientes"></div>
                                                </div>
                                            </div>
                                            <div class="col-xl-4">
                                                <div class="card">
                                                    <div class="card-header border-0"><h3 class="card-title">Mas agregados</h3></div>
                                                    <div class="card-body pt-0" id="dist_resumen_top_catalogo"></div>
                                                </div>
                                            </div>
                                            <div class="col-xl-4">
                                                <div class="card">
                                                    <div class="card-header border-0"><h3 class="card-title">Mas solicitados</h3></div>
                                                    <div class="card-body pt-0" id="dist_resumen_top_pedidos"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="<?= $distPaneClass('solicitudes') ?>" id="dist_tab_solicitudes" role="tabpanel">
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
                                    <div class="<?= $distPaneClass('clientes') ?>" id="dist_tab_clientes" role="tabpanel">
                                        <div class="card">
                                            <div class="card-header border-0 pt-6">
                                                <div class="card-title">
                                                    <div class="d-flex align-items-center position-relative my-1">
                                                        <i class="bi bi-search fs-3 position-absolute ms-5"></i>
                                                        <input type="text" id="dist_clientes_buscar" class="form-control form-control-solid w-250px ps-12" placeholder="Buscar cliente">
                                                    </div>
                                                </div>
                                                <div class="card-toolbar dist-filter-row">
                                                    <select id="dist_clientes_estatus" class="form-select form-select-solid w-175px">
                                                        <option value="">Todos</option>
                                                        <option value="aprobado">Aprobados</option>
                                                        <option value="suspendido">Suspendidos</option>
                                                        <option value="rechazado">Rechazados</option>
                                                    </select>
                                                    <select id="dist_clientes_incompletos" class="form-select form-select-solid w-175px">
                                                        <option value="">Completitud</option>
                                                        <option value="sin_lista">Sin lista</option>
                                                        <option value="sin_permisos">Sin permisos</option>
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
                                                                <th>Indicadores</th>
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
                                    <div class="<?= $distPaneClass('pedidos') ?>" id="dist_tab_cotizaciones" role="tabpanel">
                                        <div class="card">
                                            <div class="card-header border-0 pt-6">
                                                <div class="card-title">
                                                    <div>
                                                        <h2 class="fw-bold mb-1">Pedidos</h2>
                                                        <div class="text-muted fs-7">Bandeja de revision interna: solicitado no se modifica; aqui se confirma existencia, precio y respuesta.</div>
                                                    </div>
                                                </div>
                                                <div class="card-toolbar dist-filter-row">
                                                    <span id="dist_cotizaciones_total" class="badge badge-light-primary">0</span>
                                                    <input type="text" id="dist_cotizaciones_buscar" class="form-control form-control-solid" placeholder="Buscar folio o cliente">
                                                    <select id="dist_cotizaciones_estatus" class="form-select form-select-solid"><option value="">Todos</option><option value="pedido_solicitado">Pedido solicitado</option><option value="recibida">Recibida</option><option value="recibida_revision">Revision</option><option value="en_revision">En revision</option><option value="respondida">Respondida</option><option value="cerrada">Cerrada</option><option value="cancelada">Cancelada</option></select>
                                                </div>
                                            </div>
                                            <div class="card-body pt-0">
                                                <div class="table-responsive">
                                                    <table class="table align-middle table-row-dashed fs-6 gy-5">
                                                        <thead>
                                                            <tr class="text-start text-muted fw-bold fs-7 text-uppercase">
                                                                <th>Folio</th>
                                                                <th>Cliente</th>
                                                                <th>Revision</th>
                                                                <th>Solicitado</th>
                                                                <th>Confirmado</th>
                                                                <th>Valor</th>
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
                                    <div class="<?= $distPaneClass('mi_catalogo') ?>" id="dist_tab_mi_catalogo" role="tabpanel">
                                        <div class="card">
                                            <div class="card-header border-0 pt-6">
                                                <div class="card-title">
                                                    <div class="d-flex align-items-center position-relative my-1">
                                                        <i class="bi bi-search fs-3 position-absolute ms-5"></i>
                                                        <input type="text" id="dist_mi_catalogo_buscar" class="form-control form-control-solid w-300px ps-12" placeholder="Buscar cliente o SKU">
                                                    </div>
                                                </div>
                                                <div class="card-toolbar dist-filter-row">
                                                    <span id="dist_mi_catalogo_total" class="badge badge-light-primary">0</span>
                                                    <select id="dist_mi_catalogo_marca" class="form-select form-select-solid dist-filtro-marca"><option value="">Marca</option></select>
                                                    <select id="dist_mi_catalogo_categoria" class="form-select form-select-solid dist-filtro-categoria"><option value="">Categoria</option></select>
                                                    <select id="dist_mi_catalogo_proveedor" class="form-select form-select-solid dist-filtro-proveedor"><option value="">Proveedor</option></select>
                                                </div>
                                            </div>
                                            <div class="card-body pt-0">
                                                <div class="table-responsive">
                                                    <table class="table align-middle table-row-dashed fs-6 gy-5">
                                                        <thead>
                                                            <tr class="text-start text-muted fw-bold fs-7 text-uppercase">
                                                                <th>Cliente</th>
                                                                <th>Producto</th>
                                                                <th>Marca</th>
                                                                <th>Categoria</th>
                                                                <th>Proveedor</th>
                                                                <th>Alias / ubicacion</th>
                                                                <th>Prioridad</th>
                                                                <th>Fecha</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="dist_mi_catalogo_lista"></tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="<?= $distPaneClass('inventarios') ?>" id="dist_tab_inventarios" role="tabpanel">
                                        <div class="card">
                                            <div class="card-header border-0 pt-6">
                                                <div class="card-title">
                                                    <div class="d-flex align-items-center position-relative my-1">
                                                        <i class="bi bi-search fs-3 position-absolute ms-5"></i>
                                                        <input type="text" id="dist_inventarios_buscar" class="form-control form-control-solid w-300px ps-12" placeholder="Buscar cliente o SKU">
                                                    </div>
                                                </div>
                                                <div class="card-toolbar dist-filter-row">
                                                    <span id="dist_inventarios_total" class="badge badge-light-primary">0</span>
                                                    <select id="dist_inventarios_marca" class="form-select form-select-solid dist-filtro-marca"><option value="">Marca</option></select>
                                                    <select id="dist_inventarios_categoria" class="form-select form-select-solid dist-filtro-categoria"><option value="">Categoria</option></select>
                                                    <select id="dist_inventarios_proveedor" class="form-select form-select-solid dist-filtro-proveedor"><option value="">Proveedor</option></select>
                                                    <select id="dist_inventarios_estado" class="form-select form-select-solid"><option value="">Estado</option><option value="debajo_minimo">Debajo de minimo</option><option value="sin_min_max">Sin minimo/maximo</option></select>
                                                </div>
                                            </div>
                                            <div class="card-body pt-0">
                                                <div class="table-responsive">
                                                    <table class="table align-middle table-row-dashed fs-6 gy-5">
                                                        <thead>
                                                            <tr class="text-start text-muted fw-bold fs-7 text-uppercase">
                                                                <th>Cliente</th>
                                                                <th>Producto</th>
                                                                <th>Marca / categoria</th>
                                                                <th>Proveedor</th>
                                                                <th class="text-end">Existencia cliente</th>
                                                                <th class="text-end">Min / Max</th>
                                                                <th class="text-end">Sugerido</th>
                                                                <th>Ultimo conteo</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="dist_inventarios_lista"></tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="<?= $distPaneClass('sugeridos') ?>" id="dist_tab_sugeridos" role="tabpanel">
                                        <div class="card">
                                            <div class="card-header border-0 pt-6">
                                                <div class="card-title">
                                                    <div class="d-flex align-items-center position-relative my-1">
                                                        <i class="bi bi-search fs-3 position-absolute ms-5"></i>
                                                        <input type="text" id="dist_sugeridos_buscar" class="form-control form-control-solid w-300px ps-12" placeholder="Buscar cliente o SKU">
                                                    </div>
                                                </div>
                                                <div class="card-toolbar dist-filter-row">
                                                    <span id="dist_sugeridos_total" class="badge badge-light-warning">0</span>
                                                    <select id="dist_sugeridos_marca" class="form-select form-select-solid dist-filtro-marca"><option value="">Marca</option></select>
                                                    <select id="dist_sugeridos_categoria" class="form-select form-select-solid dist-filtro-categoria"><option value="">Categoria</option></select>
                                                    <select id="dist_sugeridos_proveedor" class="form-select form-select-solid dist-filtro-proveedor"><option value="">Proveedor</option></select>
                                                </div>
                                            </div>
                                            <div class="card-body pt-0">
                                                <div class="table-responsive">
                                                    <table class="table align-middle table-row-dashed fs-6 gy-5">
                                                        <thead>
                                                            <tr class="text-start text-muted fw-bold fs-7 text-uppercase">
                                                                <th>Cliente</th>
                                                                <th>Producto</th>
                                                                <th>Marca / categoria</th>
                                                                <th>Proveedor</th>
                                                                <th class="text-end">Existencia cliente</th>
                                                                <th class="text-end">Min / Max</th>
                                                                <th class="text-end">Comprar sugerido</th>
                                                                <th>Ultimo conteo</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="dist_sugeridos_lista"></tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="<?= $distPaneClass('productos') ?>" id="dist_tab_productos" role="tabpanel">
                                        <div class="card">
                                            <div class="card-header border-0 pt-6">
                                                <div class="card-title">
                                                    <div class="d-flex align-items-center position-relative my-1">
                                                        <i class="bi bi-search fs-3 position-absolute ms-5"></i>
                                                        <input type="text" id="dist_productos_buscar" class="form-control form-control-solid w-300px ps-12" placeholder="Buscar SKU o producto">
                                                    </div>
                                                </div>
                                                <div class="card-toolbar dist-filter-row">
                                                    <select id="dist_productos_marca" class="form-select form-select-solid dist-filtro-marca"><option value="">Marca</option></select>
                                                    <select id="dist_productos_categoria" class="form-select form-select-solid dist-filtro-categoria"><option value="">Categoria</option></select>
                                                    <select id="dist_productos_proveedor" class="form-select form-select-solid dist-filtro-proveedor"><option value="">Proveedor</option></select>
                                                    <select id="dist_productos_canal" class="form-select form-select-solid"><option value="">Canal</option><option value="publicado">Publicado</option><option value="no_publicado">No publicado</option><option value="inactivo">Inactivo</option></select>
                                                    <select id="dist_productos_precio" class="form-select form-select-solid"><option value="">Precio</option><option value="con_precio">Con precio</option><option value="sin_precio">Sin precio</option></select>
                                                    <select id="dist_productos_imagen" class="form-select form-select-solid"><option value="">Imagen</option><option value="con_imagen">Con imagen</option><option value="sin_imagen">Sin imagen</option></select>
                                                    <select id="dist_productos_ficha" class="form-select form-select-solid"><option value="">Ficha</option><option value="completa">Completa</option><option value="incompleta">Incompleta</option></select>
                                                    <button type="button" id="dist_productos_buscar_btn" class="btn btn-light-primary">
                                                        <i class="bi bi-search"></i>
                                                        Buscar
                                                    </button>
                                                    <button type="button" id="dist_productos_publicar_lote" class="btn btn-light-success">
                                                        <i class="bi bi-cloud-upload"></i>
                                                        Publicar lote
                                                    </button>
                                                    <button type="button" id="dist_productos_publicar_filtrados" class="btn btn-success">
                                                        <i class="bi bi-cloud-check"></i>
                                                        Publicar todos filtrados
                                                    </button>
                                                    <button type="button" id="dist_productos_desactivar_lote" class="btn btn-light-danger">
                                                        <i class="bi bi-eye-slash"></i>
                                                        Desactivar lote
                                                    </button>
                                                    <select id="dist_productos_limite" class="form-select form-select-solid w-100px">
                                                        <option value="50">50</option>
                                                        <option value="120" selected>120</option>
                                                        <option value="300">300</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="card-body pt-0">
                                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
                                                    <div id="dist_productos_paginacion_info" class="text-muted fs-7">Productos 0 de 0</div>
                                                    <div class="d-flex align-items-center gap-2">
                                                        <button type="button" id="dist_productos_pagina_anterior" class="btn btn-sm btn-light">
                                                            <i class="bi bi-chevron-left"></i>
                                                            Anterior
                                                        </button>
                                                        <span id="dist_productos_pagina_actual" class="badge badge-light-primary">1 / 1</span>
                                                        <button type="button" id="dist_productos_pagina_siguiente" class="btn btn-sm btn-light">
                                                            Siguiente
                                                            <i class="bi bi-chevron-right"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="table-responsive">
                                                    <table class="table align-middle table-row-dashed fs-6 gy-5">
                                                        <thead>
                                                            <tr class="text-start text-muted fw-bold fs-7 text-uppercase">
                                                                <th><input class="form-check-input" type="checkbox" id="dist_productos_select_all"></th>
                                                                <th>SKU</th>
                                                                <th>Producto</th>
                                                                <th>Marca / categoria</th>
                                                                <th>Proveedor</th>
                                                                <th>Calidad</th>
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
                                    <div class="<?= $distPaneClass('demanda') ?>" id="dist_tab_demanda" role="tabpanel">
                                        <div class="row g-5">
                                            <div class="col-xl-4"><div class="card"><div class="card-header border-0"><h3 class="card-title">Productos en Mi catalogo</h3></div><div class="card-body pt-0" id="dist_demanda_catalogo"></div></div></div>
                                            <div class="col-xl-4"><div class="card"><div class="card-header border-0"><h3 class="card-title">Productos solicitados</h3></div><div class="card-body pt-0" id="dist_demanda_pedidos"></div></div></div>
                                            <div class="col-xl-4"><div class="card"><div class="card-header border-0"><h3 class="card-title">Clientes activos</h3></div><div class="card-body pt-0" id="dist_demanda_clientes"></div></div></div>
                                            <div class="col-xl-4"><div class="card"><div class="card-header border-0"><h3 class="card-title">Marcas</h3></div><div class="card-body pt-0" id="dist_demanda_marcas"></div></div></div>
                                            <div class="col-xl-4"><div class="card"><div class="card-header border-0"><h3 class="card-title">Categorias</h3></div><div class="card-body pt-0" id="dist_demanda_categorias"></div></div></div>
                                            <div class="col-xl-4"><div class="card"><div class="card-header border-0"><h3 class="card-title">Proveedores</h3></div><div class="card-body pt-0" id="dist_demanda_proveedores"></div></div></div>
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
    <script src="/assets/js/custom/apps/erp/distribucion/administracion.js?v=20261001-secciones"></script>
</body>
</html>

