<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../../">
    <title>Revision de migracion ecommerce</title>
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
                                    <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Revision de migracion ecommerce</h1>
                                    <span class="text-muted">Productos que requieren una decision antes de incorporarse al catalogo ERP</span>
                                </div>
                                <a class="btn btn-light-primary" href="/catalogoerp"><i class="bi bi-box-seam"></i> Catalogo ERP</a>
                            </div>
                        </div>
                        <div class="app-content flex-column-fluid">
                            <div class="app-container container-fluid">
                                <div class="row g-5 mb-7">
                                    <div class="col-md-3"><div class="border rounded p-5"><div class="text-muted fs-7">Pendientes</div><div class="fs-2 fw-bold" id="migracion_pendientes">0</div></div></div>
                                    <div class="col-md-3"><div class="border rounded p-5"><div class="text-muted fs-7">SKU invalidos</div><div class="fs-2 fw-bold" id="migracion_invalidos">0</div></div></div>
                                    <div class="col-md-3"><div class="border rounded p-5"><div class="text-muted fs-7">SKU duplicados</div><div class="fs-2 fw-bold" id="migracion_duplicados">0</div></div></div>
                                    <div class="col-md-3"><div class="border rounded p-5"><div class="text-muted fs-7">Grupos ambiguos</div><div class="fs-2 fw-bold" id="migracion_ambiguos">0</div></div></div>
                                </div>
                                <div class="card">
                                    <div class="card-header border-0 pt-6">
                                        <div class="card-title">
                                            <div class="d-flex align-items-center position-relative my-1">
                                                <i class="bi bi-search fs-3 position-absolute ms-5"></i>
                                                <input id="migracion_buscar" class="form-control form-control-solid w-350px ps-12" placeholder="Buscar producto, SKU, grupo o motivo">
                                            </div>
                                        </div>
                                        <div class="card-toolbar">
                                            <div class="d-flex gap-3">
                                                <select id="migracion_estatus" class="form-select form-select-solid w-175px">
                                                    <option value="pendiente">Pendientes</option>
                                                    <option value="">Todos</option>
                                                    <option value="resuelta">Resueltas</option>
                                                    <option value="descartada">Descartadas</option>
                                                </select>
                                                <select id="migracion_motivo" class="form-select form-select-solid w-250px">
                                                    <option value="">Todos los motivos</option>
                                                    <option value="sku_invalido">SKU invalido</option>
                                                    <option value="sku_duplicado">SKU duplicado</option>
                                                    <option value="grupo_variante_ambiguo">Grupo de variantes ambiguo</option>
                                                    <option value="nombre_codificacion_dudosa">Nombre por revisar</option>
                                                    <option value="sku_existente_erp">SKU existente en ERP</option>
                                                    <option value="sku_duplicado_productivo">SKU duplicado productivo</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-body pt-0">
                                        <div class="table-responsive">
                                            <table class="table align-middle table-row-dashed gy-5">
                                                <thead><tr class="text-muted fw-bold fs-7 text-uppercase"><th>ID ecommerce</th><th>Grupo</th><th>SKU</th><th>Producto</th><th>Motivo</th><th>Detalle</th><th class="text-end">Acción</th></tr></thead>
                                                <tbody id="migracion_incidencias"></tbody>
                                            </table>
                                        </div>
                                        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-4 pt-4">
                                            <div class="d-flex align-items-center gap-3">
                                                <span class="text-muted fs-7">Mostrar</span>
                                                <select class="form-select form-select-sm w-80px" id="migracion_tamano_pagina">
                                                    <option value="25">25</option>
                                                    <option value="50">50</option>
                                                    <option value="100">100</option>
                                                </select>
                                            </div>
                                            <div class="d-flex align-items-center gap-3">
                                                <button class="btn btn-sm btn-light" type="button" id="migracion_pagina_anterior"><i class="bi bi-chevron-left"></i></button>
                                                <span class="text-muted fs-7" id="migracion_paginacion_info">Pagina 1 de 1</span>
                                                <button class="btn btn-sm btn-light" type="button" id="migracion_pagina_siguiente"><i class="bi bi-chevron-right"></i></button>
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
    <div class="modal fade" id="migracion_resolver_modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <form id="migracion_resolver_form" data-erp-ajax="true">
                    <div class="modal-header">
                        <h2>Resolver incidencia de catálogo</h2>
                        <button type="button" class="btn btn-sm btn-icon btn-light" data-bs-dismiss="modal"><i class="bi bi-x-lg"></i></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id_incidencia">
                        <input type="hidden" name="id_producto_erp_existente">
                        <div class="alert alert-light-primary mb-6" id="migracion_resolver_contexto"></div>
                        <div class="row g-5 mb-7">
                            <div class="col-md-4">
                                <label class="form-label">Modo de resolucion</label>
                                <select class="form-select" id="migracion_resolver_modo">
                                    <option value="crear">Crear producto ERP nuevo</option>
                                    <option value="existente">Vincular a producto ERP existente</option>
                                </select>
                            </div>
                            <div class="col-md-8 d-none" id="migracion_erp_existente_panel">
                                <label class="form-label required">Producto ERP existente</label>
                                <div class="position-relative">
                                    <i class="bi bi-search fs-3 position-absolute ms-5 mt-3"></i>
                                    <input class="form-control form-control-solid ps-12" id="migracion_buscar_producto_erp" placeholder="Buscar por SKU, codigo o nombre">
                                </div>
                                <div class="mt-3" id="migracion_producto_erp_seleccion"></div>
                                <div class="mt-3" id="migracion_producto_erp_resultados"></div>
                            </div>
                        </div>
                        <div class="row g-5 mb-7">
                            <div class="col-md-5"><label class="form-label required">Nombre del producto maestro ERP</label><input class="form-control" name="nombre_maestro" required maxlength="255"></div>
                            <div class="col-md-4"><label class="form-label">Código maestro ERP</label><input class="form-control" name="codigo_maestro" maxlength="80" placeholder="Se genera automáticamente"></div>
                        </div>
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed gy-4">
                                <thead><tr class="text-muted fw-bold fs-7 text-uppercase"><th>Incluir</th><th>Producto ecommerce</th><th>Atributos</th><th>SKU original</th><th>SKU ERP / nombre corregido</th></tr></thead>
                                <tbody id="migracion_resolver_productos"></tbody>
                            </table>
                        </div>
                        <div class="alert alert-danger d-none mt-6" id="migracion_resolver_error"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Crear producto ERP</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="assets/plugins/global/plugins.bundle.js"></script>
    <script src="assets/js/scripts.bundle.js"></script>
    <script>
        window.CATALOGO_PERMISOS = <?= json_encode(array(
            "ver" => SesionSeguridad::tienePermiso("catalogo.ver"),
            "editar" => SesionSeguridad::tienePermiso("catalogo.editar"),
            "costos" => SesionSeguridad::tienePermiso("catalogo.costos")
        )); ?>;
    </script>
    <script src="/assets/js/custom/apps/erp/catalogo/migracion_ecommerce.js?v=20260610-2"></script>
</body>
</html>
