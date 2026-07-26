<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../">
    <title>Detalle de proyecto</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet">
    <link href="assets/css/style.bundle.css" rel="stylesheet">
</head>
<body id="kt_app_body" data-kt-app-layout="dark-sidebar" data-kt-app-header-fixed="true" data-kt-app-sidebar-enabled="true" data-kt-app-sidebar-fixed="true" class="app-default">
<input type="hidden" id="proyectos_contexto_id" value="<?= isset($datos['id_proyecto']) ? intval($datos['id_proyecto']) : 0 ?>">
<input type="hidden" id="proyectos_puede_crear" value="<?= SesionSeguridad::tienePermiso('proyectos.crear') ? '1' : '0' ?>">
<input type="hidden" id="proyectos_puede_editar" value="<?= SesionSeguridad::tienePermiso('proyectos.editar') ? '1' : '0' ?>">
<input type="hidden" id="proyectos_puede_cerrar" value="<?= SesionSeguridad::tienePermiso('proyectos.cerrar') ? '1' : '0' ?>">
<div class="d-flex flex-column flex-root app-root">
    <div class="app-page flex-column flex-column-fluid">
        <?= include_once '../app/vistas/includes/header/header.php'; ?>
        <div class="app-wrapper flex-column flex-row-fluid">
            <?= include_once '../app/vistas/includes/header/sidebar.php'; ?>
            <main class="app-main flex-column flex-row-fluid">
                <div class="app-toolbar py-3 py-lg-6">
                    <div class="app-container container-fluid d-flex flex-stack">
                        <div>
                            <h1 class="page-heading text-dark fw-bold fs-3 mb-1" id="proyectos_detalle_titulo">Proyecto</h1>
                            <span class="text-muted" id="proyectos_detalle_subtitulo">Cargando detalle...</span>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="/proyecto" class="btn btn-light">
                                <i class="bi bi-arrow-left fs-3"></i>
                                Bandeja
                            </a>
                            <?php if (SesionSeguridad::tienePermiso('proyectos.crear')): ?>
                            <button type="button" class="btn btn-primary" id="proyectos_nueva_tarea">
                                <i class="bi bi-check2-square fs-3"></i>
                                Nueva tarea
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="app-content flex-column-fluid">
                    <div class="app-container container-fluid">
                        <div class="alert alert-info d-none" id="proyectos_schema_alerta"></div>
                        <div class="card mb-6">
                            <div class="card-body">
                                <div class="d-flex flex-wrap gap-2 mb-4">
                                    <span class="badge badge-light" id="proyectos_detalle_estatus">Sin proyecto</span>
                                    <span class="badge badge-light-primary" id="proyectos_detalle_prioridad">Prioridad</span>
                                    <span class="badge badge-light-info" id="proyectos_detalle_modulo">Modulo</span>
                                </div>
                                <div class="text-gray-700" id="proyectos_detalle_descripcion"></div>
                            </div>
                        </div>
                        <div class="card">
                            <div class="card-header border-0 pt-6">
                                <div class="card-title">
                                    <h2 class="fw-bold mb-0">Tareas del proyecto</h2>
                                </div>
                                <div class="card-toolbar d-flex flex-wrap gap-2">
                                    <select class="form-select form-select-solid w-150px" id="proyectos_tareas_estatus"></select>
                                    <select class="form-select form-select-solid w-150px" id="proyectos_tareas_prioridad"></select>
                                    <label class="form-check form-check-custom form-check-solid">
                                        <input class="form-check-input" type="checkbox" id="proyectos_tareas_mias">
                                        <span class="form-check-label text-muted">Mias</span>
                                    </label>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="table-responsive">
                                    <table class="table align-middle table-row-dashed gy-4">
                                        <thead>
                                        <tr class="text-muted fw-bold fs-7 text-uppercase">
                                            <th>Tarea</th>
                                            <th>Proyecto</th>
                                            <th>Prioridad</th>
                                            <th>Estado</th>
                                            <th>Vence</th>
                                            <th class="text-end">Acciones</th>
                                        </tr>
                                        </thead>
                                        <tbody id="proyectos_tareas_body">
                                        <tr><td colspan="6" class="text-center text-muted py-10">Cargando tareas...</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</div>

<?php include __DIR__ . '/partials/modal_tarea.php'; ?>

<script src="assets/plugins/global/plugins.bundle.js"></script>
<script src="assets/js/scripts.bundle.js"></script>
<script src="/assets/js/custom/apps/proyectos/listado.js?v=20260725-4"></script>
</body>
</html>
