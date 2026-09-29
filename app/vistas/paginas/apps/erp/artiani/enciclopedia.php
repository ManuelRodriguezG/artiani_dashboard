<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../../">
    <title>Artiani - Enciclopedia de especies</title>
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
                            <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Enciclopedia de especies</h1>
                            <span class="text-muted">Fichas Artiani para cuidado, asesoria, capacitacion y contenido web</span>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="/artiani/especie_nueva" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> Nueva ficha</a>
                            <button type="button" class="btn btn-sm btn-light-primary" id="artiani_btn_recargar"><i class="bi bi-arrow-clockwise"></i> Recargar</button>
                        </div>
                    </div>
                </div>
                <div class="app-content flex-column-fluid">
                    <div class="app-container container-fluid">
                        <div class="border border-gray-300 rounded p-5 mb-6">
                            <div class="row g-4 align-items-end">
                                <div class="col-lg-5">
                                    <label class="form-label fw-semibold">Buscar</label>
                                    <input class="form-control form-control-solid" id="artiani_busqueda" placeholder="Ej. serpiente, heno, filtro, humedad">
                                </div>
                                <div class="col-md-3 col-lg-2">
                                    <label class="form-label fw-semibold">Grupo</label>
                                    <select class="form-select form-select-solid" id="artiani_grupo"></select>
                                </div>
                                <div class="col-md-3 col-lg-2">
                                    <label class="form-label fw-semibold">Dificultad</label>
                                    <select class="form-select form-select-solid" id="artiani_dificultad"></select>
                                </div>
                                <div class="col-md-6 col-lg-3">
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-primary" id="artiani_btn_buscar"><i class="bi bi-search"></i> Buscar</button>
                                        <button type="button" class="btn btn-light" id="artiani_btn_limpiar"><i class="bi bi-eraser"></i> Limpiar</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="border border-gray-300 rounded p-5">
                            <div class="d-flex flex-stack mb-4">
                                <div>
                                    <h2 class="fs-5 fw-bold mb-1">Fichas disponibles</h2>
                                    <div class="text-muted fs-8">Abre una ficha para ver detalle, preguntas clave, alertas y productos por vincular.</div>
                                </div>
                                <span class="badge badge-light-primary" id="artiani_total">0 fichas</span>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-row-dashed align-middle">
                                    <thead>
                                    <tr class="text-muted fw-bold fs-8 text-uppercase">
                                        <th>Especie</th>
                                        <th>Grupo</th>
                                        <th>Dificultad</th>
                                        <th>Productos puente</th>
                                        <th class="text-end">Acciones</th>
                                    </tr>
                                    </thead>
                                    <tbody id="artiani_lista">
                                    <tr><td colspan="5" class="text-muted">Preparando Enciclopedia Artiani...</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="row g-6 mt-1">
                            <div class="col-lg-4">
                                <div class="border border-gray-300 rounded p-5 h-100">
                                    <h3 class="fs-6 fw-bold mb-3">Flujo recomendado</h3>
                                    <div class="d-flex flex-column gap-2 text-gray-700">
                                        <div><i class="bi bi-search text-primary me-2"></i>Buscar especie o necesidad.</div>
                                        <div><i class="bi bi-file-earmark-text text-primary me-2"></i>Abrir ficha completa.</div>
                                        <div><i class="bi bi-bag-check text-primary me-2"></i>Relacionar productos cuando se autorice.</div>
                                        <div><i class="bi bi-globe2 text-primary me-2"></i>Preparar version web para clientes.</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="border border-gray-300 rounded p-5 h-100">
                                    <h3 class="fs-6 fw-bold mb-3">Prioridad operativa</h3>
                                    <div class="d-flex flex-column gap-2 text-gray-700">
                                        <div><i class="bi bi-check2-circle text-success me-2"></i>Venta responsable.</div>
                                        <div><i class="bi bi-check2-circle text-success me-2"></i>Capacitacion uniforme.</div>
                                        <div><i class="bi bi-check2-circle text-success me-2"></i>Datos reutilizables para IA.</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="border border-gray-300 rounded p-5 h-100">
                                    <h3 class="fs-6 fw-bold mb-3">Estatus actual</h3>
                                    <div class="text-muted" id="artiani_estado">Cargando...</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</div>
<script src="assets/plugins/global/plugins.bundle.js"></script>
<script src="assets/js/scripts.bundle.js"></script>
<script src="/assets/js/custom/apps/erp/artiani/enciclopedia.js?v=20260929-1"></script>
</body>
</html>
