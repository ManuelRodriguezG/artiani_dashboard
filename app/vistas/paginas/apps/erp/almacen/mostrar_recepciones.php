<!DOCTYPE html>
<html lang="en">
    <head><base href="../../../"/>
        <title>Recepciones de almacen</title>
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
                if (document.documentElement.hasAttribute("data-theme-mode")) {
                    themeMode = document.documentElement.getAttribute("data-theme-mode");
                } else {
                    themeMode = localStorage.getItem("data-theme") !== null ? localStorage.getItem("data-theme") : defaultThemeMode;
                }
                if (themeMode === "system") {
                    themeMode = window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
                }
                document.documentElement.setAttribute("data-theme", themeMode);
            }</script>
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
                                        <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">Recepciones de almacen</h1>
                                        <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                                            <li class="breadcrumb-item text-muted">ERP</li>
                                            <li class="breadcrumb-item">
                                                <span class="bullet bg-gray-400 w-5px h-2px"></span>
                                            </li>
                                            <li class="breadcrumb-item text-muted">Almacen</li>
                                            <li class="breadcrumb-item">
                                                <span class="bullet bg-gray-400 w-5px h-2px"></span>
                                            </li>
                                            <li class="breadcrumb-item text-muted">Recepciones</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div id="kt_app_content" class="app-content flex-column-fluid">
                                <div id="kt_app_content_container" class="app-container container-xxl">
                                    <div class="card card-flush">
                                        <div class="card-header align-items-center py-5 gap-2 gap-md-5">
                                            <div class="card-title">
                                                <div class="d-flex align-items-center position-relative my-1">
                                                    <span class="svg-icon svg-icon-1 position-absolute ms-4">
                                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <rect opacity="0.5" x="17.0365" y="15.1223" width="8.15546" height="2" rx="1" transform="rotate(45 17.0365 15.1223)" fill="currentColor" />
                                                        <path d="M11 19C6.55556 19 3 15.4444 3 11C3 6.55556 6.55556 3 11 3C15.4444 3 19 6.55556 19 11C19 15.4444 15.4444 19 11 19ZM11 5C7.53333 5 5 7.53333 5 11C5 14.4667 7.53333 17 11 17C14.4667 17 17 14.4667 17 11C17 7.53333 14.4667 5 11 5Z" fill="currentColor" />
                                                        </svg>
                                                    </span>
                                                    <input type="text" data-kt-almacen-recepcion-filter="search" class="form-control form-control-solid w-250px ps-14" placeholder="Buscar recepcion" />
                                                </div>
                                            </div>
                                            <div class="card-toolbar flex-row-fluid justify-content-end gap-5">
                                                <div class="input-group w-250px">
                                                    <input class="form-control form-control-solid rounded rounded-end-0" placeholder="Rango de fechas" id="kt_almacen_recepciones_flatpickr" />
                                                    <button class="btn btn-icon btn-light" id="kt_almacen_recepciones_flatpickr_clear">
                                                        <span class="svg-icon svg-icon-2">
                                                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                            <rect opacity="0.5" x="7.05025" y="15.5356" width="12" height="2" rx="1" transform="rotate(-45 7.05025 15.5356)" fill="currentColor" />
                                                            <rect x="8.46447" y="7.05029" width="12" height="2" rx="1" transform="rotate(45 8.46447 7.05029)" fill="currentColor" />
                                                            </svg>
                                                        </span>
                                                    </button>
                                                </div>
                                                <div class="w-100 mw-175px">
                                                    <select class="form-select form-select-solid" data-control="select2" data-hide-search="true" data-placeholder="Estatus" data-kt-almacen-recepcion-filter="status">
                                                        <option></option>
                                                        <option value="all">Todos</option>
                                                        <option value="pendiente">Pendiente</option>
                                                        <option value="parcial">Parcial</option>
                                                        <option value="recibida">Recibida</option>
                                                        <option value="cancelada">Cancelada</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="card-body pt-0">
                                            <table class="table align-middle table-row-dashed fs-6 gy-5" id="kt_almacen_recepciones_table">
                                                <thead>
                                                    <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
                                                        <th class="w-10px pe-2">
                                                            <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                                                                <input class="form-check-input" type="checkbox" data-kt-check="true" data-kt-check-target="#kt_almacen_recepciones_table .form-check-input" value="1" />
                                                            </div>
                                                        </th>
                                                        <th class="min-w-80px">ID</th>
                                                        <th class="min-w-130px">Folio</th>
                                                        <th class="min-w-120px">OC</th>
                                                        <th class="text-end min-w-160px">Proveedor</th>
                                                        <th class="text-end min-w-120px">Almacen</th>
                                                        <th class="text-end min-w-100px">Estatus</th>
                                                        <th class="text-end min-w-90px">Partidas</th>
                                                        <th class="text-end min-w-120px">Pendiente</th>
                                                        <th class="text-end min-w-140px">Fecha alerta</th>
                                                        <th class="text-end min-w-90px">Acciones</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="fw-semibold text-gray-600" id="body-recepciones"></tbody>
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
        <script src="assets/js/custom/apps/erp/almacen/mostrar_recepciones/recepciones.js"></script>
        <script src="assets/js/custom/apps/erp/almacen/mostrar_recepciones/listing_recepciones.js"></script>
        <script src="assets/js/widgets.bundle.js"></script>
        <script src="assets/js/custom/widgets.js"></script>
    </body>
</html>
