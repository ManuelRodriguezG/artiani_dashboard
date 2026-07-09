<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../../">
    <title>Notificaciones ERP</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet">
    <link href="assets/css/style.bundle.css" rel="stylesheet">
</head>
<body id="kt_app_body" data-kt-app-layout="dark-sidebar" data-kt-app-header-fixed="true" data-kt-app-sidebar-enabled="true" data-kt-app-sidebar-fixed="true" class="app-default">
<div class="d-flex flex-column flex-root app-root">
    <div class="app-page flex-column flex-column-fluid">
        <?= include_once '../app/vistas/includes/header/header.php'; ?>
        <div class="app-wrapper flex-column flex-row-fluid">
            <?= include_once '../app/vistas/includes/header/sidebar.php'; ?>
            <main class="app-main flex-column flex-row-fluid">
                <div class="app-toolbar py-3 py-lg-6">
                    <div class="app-container container-fluid d-flex flex-stack">
                        <div>
                            <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Notificaciones</h1>
                            <span class="text-muted">Alertas operativas visibles por tus permisos</span>
                        </div>
                    </div>
                </div>
                <div class="app-content flex-column-fluid">
                    <div class="app-container container-fluid">
                        <div class="row g-5 mb-7">
                            <div class="col-md-3">
                                <div class="border border-gray-300 rounded p-5 h-100">
                                    <div class="text-muted fw-semibold">Pendientes</div>
                                    <div class="fs-2 fw-bold" id="notificaciones_total">0</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="border border-gray-300 rounded p-5 h-100">
                                    <div class="text-muted fw-semibold">Criticas</div>
                                    <div class="fs-2 fw-bold text-danger" id="notificaciones_criticas">0</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="border border-gray-300 rounded p-5 h-100">
                                    <div class="text-muted fw-semibold">Altas</div>
                                    <div class="fs-2 fw-bold text-warning" id="notificaciones_altas">0</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="border border-gray-300 rounded p-5 h-100">
                                    <div class="text-muted fw-semibold">Areas con pendientes</div>
                                    <div class="fs-2 fw-bold" id="notificaciones_areas_total">0</div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex flex-column flex-md-row gap-3 mb-6">
                            <select class="form-select form-select-solid w-md-225px" id="notificaciones_estatus">
                                <option value="">Pendientes activas</option>
                                <option value="pendiente">Pendiente</option>
                                <option value="en_revision">En revision</option>
                                <option value="bloqueada">Bloqueada</option>
                                <option value="resuelta">Resuelta</option>
                            </select>
                            <input class="form-control form-control-solid w-md-300px" id="notificaciones_area" placeholder="Filtrar por area responsable">
                            <button type="button" class="btn btn-light-primary" id="notificaciones_refrescar">Actualizar</button>
                        </div>

                        <div class="d-flex flex-wrap gap-2 mb-6" id="notificaciones_modulos">
                            <span class="text-muted">Sin pendientes por modulo</span>
                        </div>

                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed gy-4">
                                <thead>
                                <tr class="text-muted fw-bold fs-7 text-uppercase">
                                    <th>Prioridad</th>
                                    <th>Notificacion</th>
                                    <th>Modulo</th>
                                    <th>Area</th>
                                    <th>Estado</th>
                                    <th>Fecha</th>
                                    <th class="text-end">Acciones</th>
                                </tr>
                                </thead>
                                <tbody id="notificaciones_body">
                                <tr><td colspan="7" class="text-center text-muted py-10">Cargando notificaciones...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</div>
<script src="assets/plugins/global/plugins.bundle.js"></script>
<script src="assets/js/scripts.bundle.js"></script>
<script src="/assets/js/custom/apps/erp/notificaciones/listado.js?v=20260616-1"></script>
</body>
</html>
