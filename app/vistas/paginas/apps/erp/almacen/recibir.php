<!DOCTYPE html>
<html lang="en">
    <head><base href="../../../"/>
        <title>Recibir mercancia</title>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <link rel="shortcut icon" href="assets/media/logos/favicon.ico" />
        <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700" />
        <link href="assets/plugins/custom/datatables/datatables.bundle.css" rel="stylesheet" type="text/css" />
        <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css" />
    </head>
    <body id="kt_app_body" data-kt-app-layout="dark-sidebar" data-kt-app-header-fixed="true" data-kt-app-sidebar-enabled="true" data-kt-app-sidebar-fixed="true" data-kt-app-sidebar-hoverable="true" data-kt-app-sidebar-push-header="true" data-kt-app-sidebar-push-toolbar="true" data-kt-app-sidebar-push-footer="true" data-kt-app-toolbar-enabled="true" class="app-default">
        <script>var defaultThemeMode = "light";
            var themeMode;
            if (document.documentElement) {
                themeMode = document.documentElement.hasAttribute("data-theme-mode") ? document.documentElement.getAttribute("data-theme-mode") : (localStorage.getItem("data-theme") || defaultThemeMode);
                if (themeMode === "system") {
                    themeMode = window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
                }
                document.documentElement.setAttribute("data-theme", themeMode);
            }</script>
        <input type="hidden" id="id_recepcion_almacen" value="<?= isset($datos["id_recepcion_almacen"]) ? $datos["id_recepcion_almacen"] : 0; ?>" />
        <div class="d-flex flex-column flex-root app-root" id="kt_app_root">
            <div class="app-page flex-column flex-column-fluid" id="kt_app_page">
                <?= include_once '../app/vistas/includes/header/header.php'; ?>
                <div class="app-wrapper flex-column flex-row-fluid" id="kt_app_wrapper">
                    <?= include_once '../app/vistas/includes/header/sidebar.php'; ?>
                    <div class="app-main flex-column flex-row-fluid" id="kt_app_main">
                        <div class="d-flex flex-column flex-column-fluid">
                            <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
                                <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
                                    <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                                        <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">Recibir mercancia</h1>
                                        <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                            <li class="breadcrumb-item text-muted">ERP</li>
                                            <li class="breadcrumb-item">
                                                <span class="bullet bg-gray-400 w-5px h-2px"></span>
                                            </li>
                                            <li class="breadcrumb-item text-muted">Almacen</li>
                                            <li class="breadcrumb-item">
                                                <span class="bullet bg-gray-400 w-5px h-2px"></span>
                                            </li>
                                            <li class="breadcrumb-item text-muted">Recepcion</li>
                                        </ul>
                                    </div>
                                    <div class="d-flex align-items-center gap-2 gap-lg-3">
                                        <a href="/almacen/mostrar_recepciones" class="btn btn-sm btn-light">Volver</a>
                                        <button type="button" class="btn btn-sm btn-primary" id="btn_guardar_recepcion" disabled>Guardar recepcion</button>
                                    </div>
                                </div>
                            </div>
                            <div id="kt_app_content" class="app-content flex-column-fluid">
                                <div id="kt_app_content_container" class="app-container container-xxl">
                                    <div class="card card-flush mb-6">
                                        <div class="card-header">
                                            <div class="card-title">
                                                <h3 class="fw-bold m-0" id="recepcion_folio">Recepcion</h3>
                                            </div>
                                            <div class="card-toolbar">
                                                <span class="badge badge-light-primary" id="recepcion_estatus">pendiente</span>
                                            </div>
                                        </div>
                                        <div class="card-body pt-0">
                                            <div class="row g-5">
                                                <div class="col-md-3">
                                                    <div class="text-muted fs-7">Orden de compra</div>
                                                    <a href="#" class="fw-bold text-gray-800 text-hover-primary" id="recepcion_orden_compra">-</a>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="text-muted fs-7">Proveedor</div>
                                                    <div class="fw-bold text-gray-800" id="recepcion_proveedor">-</div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="text-muted fs-7">Almacen</div>
                                                    <div class="fw-bold text-gray-800" id="recepcion_almacen">-</div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="text-muted fs-7">Fecha alerta</div>
                                                    <div class="fw-bold text-gray-800" id="recepcion_fecha_alerta">-</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card card-flush">
                                        <div class="card-header align-items-center py-5 gap-2 gap-md-5">
                                            <div class="card-title">
                                                <div class="d-flex align-items-center position-relative my-1">
                                                    <span class="svg-icon svg-icon-1 position-absolute ms-4">
                                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <rect opacity="0.5" x="17.0365" y="15.1223" width="8.15546" height="2" rx="1" transform="rotate(45 17.0365 15.1223)" fill="currentColor" />
                                                        <path d="M11 19C6.55556 19 3 15.4444 3 11C3 6.55556 3 3 11 3C15.4444 3 19 6.55556 19 11C19 15.4444 15.4444 19 11 19Z" fill="currentColor" />
                                                        </svg>
                                                    </span>
                                                    <input type="text" data-kt-almacen-recibir-filter="search" class="form-control form-control-solid w-250px ps-14" placeholder="Buscar producto" />
                                                </div>
                                            </div>
                                        </div>
                                        <div class="card-body pt-0">
                                            <table class="table align-middle table-row-dashed fs-6 gy-5" id="kt_almacen_recibir_table">
                                                <thead>
                                                    <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
                                                        <th class="min-w-120px">SKU</th>
                                                        <th class="min-w-220px">Producto</th>
                                                        <th class="text-end min-w-100px">Ordenado</th>
                                                        <th class="text-end min-w-100px">Recibido</th>
                                                        <th class="text-end min-w-120px">Pendiente al guardar</th>
                                                        <th class="min-w-140px">Control</th>
                                                        <th class="min-w-130px">Lote</th>
                                                        <th class="min-w-140px">Caducidad</th>
                                                        <th class="min-w-160px">Ubicacion</th>
                                                        <th class="text-end min-w-130px">A recibir</th>
                                                        <th class="min-w-170px">Codigo interno</th>
                                                        <th class="text-end min-w-100px">Acciones</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="fw-semibold text-gray-600" id="body-recepcion-detalle"></tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id="kt_app_footer" class="app-footer">
                            <div class="app-container container-fluid d-flex flex-column flex-md-row flex-center flex-md-stack py-3">
                                <div class="text-dark order-2 order-md-1">
                                    <span class="text-muted fw-semibold me-1">2022&copy;</span>
                                    <a href="https://keenthemes.com" target="_blank" class="text-gray-800 text-hover-primary">Keenthemes</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script>var hostUrl = "assets/";</script>
        <script src="assets/plugins/global/plugins.bundle.js"></script>
        <script src="assets/js/scripts.bundle.js"></script>
        <script src="assets/plugins/custom/datatables/datatables.bundle.js"></script>
        <script src="assets/js/custom/apps/erp/almacen/recibir/recepcion.js?v=20260618-1"></script>
        <script src="assets/js/custom/apps/erp/almacen/recibir/recibir.js?v=20260627-var1"></script>
        <script src="assets/js/widgets.bundle.js"></script>
        <script src="assets/js/custom/widgets.js"></script>
    </body>
</html>
