<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../">
    <title>Proyectos y tareas</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet">
    <link href="assets/css/style.bundle.css" rel="stylesheet">
</head>
<body id="kt_app_body" data-kt-app-layout="dark-sidebar" data-kt-app-header-fixed="true" data-kt-app-sidebar-enabled="true" data-kt-app-sidebar-fixed="true" class="app-default">
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
                            <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Proyectos y tareas</h1>
                            <span class="text-muted">Control operativo de pendientes, responsables y objetivos</span>
                        </div>
                        <?php if (SesionSeguridad::tienePermiso('proyectos.crear')): ?>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-light-primary" id="proyectos_nueva_tarea">
                                <i class="bi bi-check2-square fs-3"></i>
                                Nueva tarea
                            </button>
                            <button type="button" class="btn btn-primary" id="proyectos_nuevo_proyecto">
                                <i class="bi bi-folder-plus fs-3"></i>
                                Nuevo proyecto
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="app-content flex-column-fluid">
                    <div class="app-container container-fluid">
                        <div class="alert alert-info d-none" id="proyectos_schema_alerta"></div>

                        <ul class="nav nav-tabs nav-line-tabs mb-7 fs-6">
                            <li class="nav-item">
                                <a class="nav-link active" data-bs-toggle="tab" href="#proyectos_tab_resumen">Resumen</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#proyectos_tab_trabajo">Proyectos y tareas</a>
                            </li>
                        </ul>

                        <div class="tab-content">
                            <div class="tab-pane fade show active" id="proyectos_tab_resumen">
                        <div class="row g-5 mb-7">
                            <div class="col-md-3">
                                <div class="border border-gray-300 rounded p-5 h-100">
                                    <div class="text-muted fw-semibold">Proyectos activos</div>
                                    <div class="fs-2 fw-bold" id="proyectos_kpi_activos">0</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="border border-gray-300 rounded p-5 h-100">
                                    <div class="text-muted fw-semibold">Tareas pendientes</div>
                                    <div class="fs-2 fw-bold" id="proyectos_kpi_tareas">0</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="border border-gray-300 rounded p-5 h-100">
                                    <div class="text-muted fw-semibold">Vencidas</div>
                                    <div class="fs-2 fw-bold text-danger" id="proyectos_kpi_vencidas">0</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="border border-gray-300 rounded p-5 h-100">
                                    <div class="text-muted fw-semibold">Mi trabajo</div>
                                    <div class="fs-2 fw-bold" id="proyectos_kpi_mias">0</div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-6 mb-7">
                            <div class="col-xl-5">
                                <div class="card h-100">
                                    <div class="card-header border-0 pt-6">
                                        <div class="card-title">
                                            <h2 class="fw-bold mb-0">Avance general</h2>
                                        </div>
                                    </div>
                                    <div class="card-body pt-0">
                                        <div class="d-flex align-items-end gap-3 mb-3">
                                            <div class="fs-1 fw-bold" id="proyectos_avance_general">0%</div>
                                            <div class="text-muted pb-3" id="proyectos_avance_detalle">0 de 0 tareas completadas</div>
                                        </div>
                                        <div class="progress h-8px bg-light">
                                            <div class="progress-bar bg-primary" id="proyectos_avance_barra" role="progressbar" style="width: 0%"></div>
                                        </div>
                                        <div class="separator my-5"></div>
                                        <div class="d-flex flex-wrap gap-2" id="proyectos_prioridades_panel">
                                            <span class="text-muted">Sin tareas activas</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-4">
                                <div class="card h-100">
                                    <div class="card-header border-0 pt-6">
                                        <div class="card-title">
                                            <h2 class="fw-bold mb-0">Trabajo por estado</h2>
                                        </div>
                                    </div>
                                    <div class="card-body pt-0" id="proyectos_estatus_panel">
                                        <div class="text-muted">Sin tareas registradas</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-3">
                                <div class="card h-100">
                                    <div class="card-header border-0 pt-6">
                                        <div class="card-title">
                                            <h2 class="fw-bold mb-0">Modulos</h2>
                                        </div>
                                    </div>
                                    <div class="card-body pt-0" id="proyectos_modulos_panel">
                                        <div class="text-muted">Sin tareas registradas</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                            </div>
                            <div class="tab-pane fade" id="proyectos_tab_trabajo">
                        <div class="row g-6">
                            <div class="col-xl-4">
                                <div class="card h-100">
                                    <div class="card-header border-0 pt-6">
                                        <div class="card-title">
                                            <div class="d-flex align-items-center position-relative">
                                                <i class="bi bi-search fs-3 position-absolute ms-5"></i>
                                                <input type="text" class="form-control form-control-solid ps-12" id="proyectos_buscar" placeholder="Buscar proyecto">
                                            </div>
                                        </div>
                                        <div class="card-toolbar">
                                            <button type="button" class="btn btn-sm btn-light" id="proyectos_refrescar">
                                                <i class="bi bi-arrow-clockwise"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-body pt-0">
                                        <div class="row g-3 mb-5">
                                            <div class="col-6">
                                                <select class="form-select form-select-solid" id="proyectos_filtro_estatus"></select>
                                            </div>
                                            <div class="col-6">
                                                <select class="form-select form-select-solid" id="proyectos_filtro_tipo"></select>
                                            </div>
                                        </div>
                                        <div class="scroll-y mh-650px pe-2" id="proyectos_lista">
                                            <div class="text-center text-muted py-10">Cargando proyectos...</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-xl-8">
                                <div class="card mb-6">
                                    <div class="card-header border-0 pt-6">
                                        <div class="card-title">
                                            <div>
                                                <h2 class="fw-bold mb-1" id="proyectos_detalle_titulo">Selecciona un proyecto</h2>
                                                <span class="text-muted" id="proyectos_detalle_subtitulo">La bandeja esta lista para comenzar sin cargar avances previos.</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-body pt-0">
                                        <div class="d-flex flex-wrap gap-2 mb-5">
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
                                            <h2 class="fw-bold mb-0">Tareas</h2>
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
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</div>

<div class="modal fade" id="proyectos_modal_proyecto" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-650px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">Proyecto</h2>
                <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal">
                    <i class="bi bi-x fs-2"></i>
                </button>
            </div>
            <form id="proyectos_form_proyecto">
                <div class="modal-body">
                    <input type="hidden" name="id_proyecto" id="proyecto_id_proyecto">
                    <div class="mb-4">
                        <label class="form-label">Nombre</label>
                        <input type="text" class="form-control form-control-solid" name="nombre" id="proyecto_nombre" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Descripcion</label>
                        <textarea class="form-control form-control-solid" name="descripcion" id="proyecto_descripcion" rows="3"></textarea>
                    </div>
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label">Tipo</label>
                            <select class="form-select form-select-solid" name="tipo" id="proyecto_tipo"></select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Modulo</label>
                            <select class="form-select form-select-solid" name="modulo_relacionado" id="proyecto_modulo"></select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Estado</label>
                            <select class="form-select form-select-solid" name="estatus" id="proyecto_estatus"></select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Prioridad</label>
                            <select class="form-select form-select-solid" name="prioridad" id="proyecto_prioridad"></select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Responsable</label>
                            <select class="form-select form-select-solid" name="id_responsable" id="proyecto_id_responsable"></select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Inicio</label>
                            <input type="date" class="form-control form-control-solid" name="fecha_inicio" id="proyecto_fecha_inicio">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Objetivo</label>
                            <input type="date" class="form-control form-control-solid" name="fecha_objetivo" id="proyecto_fecha_objetivo">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/partials/modal_tarea.php'; ?>

<script src="assets/plugins/global/plugins.bundle.js"></script>
<script src="assets/js/scripts.bundle.js"></script>
<script src="/assets/js/custom/apps/proyectos/listado.js?v=20260725-4"></script>
</body>
</html>

