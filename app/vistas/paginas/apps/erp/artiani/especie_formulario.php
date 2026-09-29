<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../../">
    <title>Artiani - Nueva ficha</title>
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
                            <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Nueva ficha Artiani</h1>
                            <span class="text-muted">Captura guiada para una especie o grupo de especies</span>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="/artiani/enciclopedia" class="btn btn-sm btn-light"><i class="bi bi-arrow-left"></i> Enciclopedia</a>
                        </div>
                    </div>
                </div>
                <div class="app-content flex-column-fluid">
                    <div class="app-container container-fluid">
                        <div class="border border-gray-300 rounded p-5 mb-6">
                            <div class="d-flex gap-3 align-items-start">
                                <i class="bi bi-info-circle text-primary fs-2"></i>
                                <div>
                                    <div class="fw-bold mb-1">Borrador operativo</div>
                                    <div class="text-muted">Esta pantalla prepara la estructura de captura. El guardado real debe activarse despues de autorizar tablas, permisos finos y respaldo si aplica.</div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-6">
                            <div class="col-xl-8">
                                <div class="border border-gray-300 rounded p-6">
                                    <div class="row g-4">
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Nombre de especie o grupo</label>
                                            <input class="form-control form-control-solid" id="artiani_form_nombre" placeholder="Ej. Ajolote, Betta, Gecko leopardo">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-semibold">Grupo</label>
                                            <select class="form-select form-select-solid" id="artiani_form_grupo"></select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label fw-semibold">Dificultad</label>
                                            <select class="form-select form-select-solid" id="artiani_form_dificultad"></select>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label fw-semibold">Resumen para empleados</label>
                                            <textarea class="form-control form-control-solid" id="artiani_form_resumen" rows="3" placeholder="Que debe entender el equipo antes de recomendar esta especie?"></textarea>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Habitat requerido</label>
                                            <textarea class="form-control form-control-solid" id="artiani_form_habitat" rows="5" placeholder="Un punto por linea"></textarea>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Alimentacion</label>
                                            <textarea class="form-control form-control-solid" id="artiani_form_alimentacion" rows="5" placeholder="Un punto por linea"></textarea>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Preguntas clave al cliente</label>
                                            <textarea class="form-control form-control-solid" id="artiani_form_preguntas" rows="5" placeholder="Un punto por linea"></textarea>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-semibold">Alertas y limites</label>
                                            <textarea class="form-control form-control-solid" id="artiani_form_alertas" rows="5" placeholder="Un punto por linea"></textarea>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label fw-semibold">Productos puente por relacionar</label>
                                            <input class="form-control form-control-solid" id="artiani_form_productos" placeholder="Ej. terrario, sustrato, termometro, alimento">
                                        </div>
                                        <div class="col-12 d-flex gap-2">
                                            <button type="button" class="btn btn-primary" id="artiani_form_previsualizar"><i class="bi bi-eye"></i> Previsualizar</button>
                                            <button type="button" class="btn btn-light" id="artiani_form_limpiar"><i class="bi bi-eraser"></i> Limpiar</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-4">
                                <div class="border border-gray-300 rounded p-5 h-100">
                                    <h2 class="fs-5 fw-bold mb-4">Previsualizacion</h2>
                                    <div id="artiani_form_preview" class="text-muted">Captura datos y previsualiza la ficha antes de definir el guardado.</div>
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
<script src="/assets/js/custom/apps/erp/artiani/especie_formulario.js?v=20260929-1"></script>
</body>
</html>
