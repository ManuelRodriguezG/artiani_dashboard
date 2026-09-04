<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../../">
    <title>Asesor comercial de prospectos</title>
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
                            <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Asesor comercial de prospectos</h1>
                            <span class="text-muted">Respuestas utiles segun producto, especie, necesidad, mayoreo y catalogo</span>
                        </div>
                    </div>
                </div>
                <div class="app-content flex-column-fluid">
                    <div class="app-container container-fluid">
                        <div class="row g-6">
                            <div class="col-xl-5">
                                <div class="border border-gray-300 rounded p-6 h-100">
                                    <div class="row g-4">
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Canal</label>
                                            <select class="form-select form-select-solid" id="atencion_canal"></select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Intencion</label>
                                            <select class="form-select form-select-solid" id="atencion_intencion"></select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Categoria</label>
                                            <select class="form-select form-select-solid" id="atencion_categoria"></select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Producto o especie</label>
                                            <input class="form-control form-control-solid" id="atencion_producto" placeholder="Ej. piton bola, alimento, terrario">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label fw-semibold">Mensaje del prospecto</label>
                                            <textarea class="form-control form-control-solid" id="atencion_mensaje_cliente" rows="4" placeholder="Ej. Disculpe tendras terrarios y todo lo que necesito para una piton bola?"></textarea>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label fw-semibold">Liga de catalogo opcional</label>
                                            <input class="form-control form-control-solid" id="atencion_url_catalogo" placeholder="https://...">
                                        </div>
                                        <div class="col-12 d-flex gap-3">
                                            <button type="button" class="btn btn-primary" id="atencion_generar"><i class="bi bi-stars"></i> Generar</button>
                                            <button type="button" class="btn btn-light" id="atencion_limpiar"><i class="bi bi-eraser"></i> Limpiar</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-7">
                                <div class="border border-gray-300 rounded p-6 mb-6">
                                    <div class="d-flex flex-stack mb-4">
                                        <h2 class="fs-5 fw-bold mb-0">Respuesta sugerida</h2>
                                        <button type="button" class="btn btn-sm btn-light-primary" id="atencion_copiar"><i class="bi bi-clipboard"></i> Copiar</button>
                                    </div>
                                    <textarea class="form-control form-control-solid" id="atencion_respuesta" rows="8" placeholder="Genera una respuesta para editar antes de enviarla."></textarea>
                                    <div class="text-muted fs-8 mt-3" id="atencion_estado">Sin respuesta generada</div>
                                </div>
                                <div class="row g-6">
                                    <div class="col-lg-6">
                                        <div class="border border-gray-300 rounded p-5 h-100">
                                            <h3 class="fs-6 fw-bold mb-4">Preguntas utiles</h3>
                                            <div id="atencion_preguntas" class="d-flex flex-column gap-2 text-gray-700"></div>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="border border-gray-300 rounded p-5 h-100">
                                            <h3 class="fs-6 fw-bold mb-4">Recordatorios</h3>
                                            <div id="atencion_recordatorios" class="d-flex flex-column gap-2 text-gray-700"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="border border-gray-300 rounded p-5 mt-6">
                                    <h3 class="fs-6 fw-bold mb-4">Variantes rapidas</h3>
                                    <div id="atencion_variantes" class="row g-3"></div>
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
<script src="/assets/js/custom/apps/erp/atencion/asistente.js?v=20260904-1"></script>
</body>
</html>
