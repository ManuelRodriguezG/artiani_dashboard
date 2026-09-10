<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../../">
    <title>Clasificacion rapida del catalogo ERP</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css">
    <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css">
    <style>
        .catalogo-rapido-skus { max-width: 260px; white-space: normal; }
        .catalogo-rapido-producto { min-width: 260px; }
        .catalogo-rapido-select { min-width: 220px; }
        .catalogo-rapido-secundarias { min-width: 320px; }
        .catalogo-rapido-row-saving { opacity: .65; pointer-events: none; }
    </style>
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
                        <div class="app-container container-fluid d-flex flex-stack flex-wrap gap-4">
                            <div>
                                <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Clasificacion rapida</h1>
                                <span class="text-muted">Productos agrupados por codigo interno/catalogo para revisar y completar marca y categorias.</span>
                            </div>
                            <div class="d-flex gap-2">
                                <a class="btn btn-light-primary" href="/catalogoerp"><i class="bi bi-box-seam"></i> Productos ERP</a>
                                <?php if (SesionSeguridad::tienePermiso("catalogo.editar")): ?>
                                <a class="btn btn-light" href="/catalogoerp/configuracion"><i class="bi bi-gear"></i> Configuracion</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <div class="card mb-6">
                                <div class="card-body py-5">
                                    <div class="row g-4 align-items-end">
                                        <div class="col-lg-4">
                                            <label class="form-label">Buscar</label>
                                            <input id="clasificacion_buscar" class="form-control form-control-solid" placeholder="SKU, producto, marca o categoria">
                                        </div>
                                        <div class="col-lg-2">
                                            <label class="form-label">Estatus</label>
                                            <select id="clasificacion_estatus" class="form-select form-select-solid">
                                                <option value="">Todos</option>
                                                <option value="activo">Activo</option>
                                                <option value="borrador">Borrador</option>
                                                <option value="en_revision">En revision</option>
                                                <option value="inactivo">Inactivo</option>
                                                <option value="descontinuado">Descontinuado</option>
                                            </select>
                                        </div>
                                        <div class="col-lg-2">
                                            <label class="form-label">Limite</label>
                                            <select id="clasificacion_limite" class="form-select form-select-solid">
                                                <option value="100">100</option>
                                                <option value="150" selected>150</option>
                                                <option value="250">250</option>
                                                <option value="500">500</option>
                                            </select>
                                        </div>
                                        <div class="col-lg-2">
                                            <label class="form-label">Faltantes</label>
                                            <div class="d-flex flex-column gap-2">
                                                <label class="form-check form-check-sm form-check-custom form-check-solid">
                                                    <input class="form-check-input" type="checkbox" id="clasificacion_sin_marca">
                                                    <span class="form-check-label">Sin marca</span>
                                                </label>
                                                <label class="form-check form-check-sm form-check-custom form-check-solid">
                                                    <input class="form-check-input" type="checkbox" id="clasificacion_sin_principal">
                                                    <span class="form-check-label">Sin principal</span>
                                                </label>
                                                <label class="form-check form-check-sm form-check-custom form-check-solid">
                                                    <input class="form-check-input" type="checkbox" id="clasificacion_sin_secundarias">
                                                    <span class="form-check-label">Sin secundarias</span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-lg-2 text-end">
                                            <button class="btn btn-primary w-100" type="button" id="clasificacion_recargar"><i class="bi bi-arrow-repeat"></i> Recargar</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card">
                                <div class="card-header border-0 pt-5">
                                    <div class="card-title flex-column align-items-start">
                                        <h3 class="fw-bold mb-1">Productos por clasificar</h3>
                                        <span class="text-muted fs-7">Cada renglon es un producto maestro; sus SKU agrupados comparten marca y categorias.</span>
                                    </div>
                                    <div class="card-toolbar"><span class="badge badge-light-primary fs-7" id="clasificacion_total">0 productos</span></div>
                                </div>
                                <div class="card-body pt-0">
                                    <div class="table-responsive">
                                        <table class="table align-middle table-row-dashed gy-5">
                                            <thead>
                                            <tr class="text-muted fw-bold fs-7 text-uppercase">
                                                <th>SKU</th>
                                                <th>Producto</th>
                                                <th>Marca</th>
                                                <th>Categoria principal</th>
                                                <th>Categorias secundarias</th>
                                                <th>Faltantes</th>
                                                <th class="text-end">Guardar</th>
                                            </tr>
                                            </thead>
                                            <tbody id="clasificacion_lista"></tbody>
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
</div>
<script src="assets/plugins/global/plugins.bundle.js"></script>
<script src="assets/js/scripts.bundle.js"></script>
<script>
    window.CATALOGO_PERMISOS = <?= json_encode(array(
        "ver" => SesionSeguridad::tienePermiso("catalogo.ver"),
        "editar" => SesionSeguridad::tienePermiso("catalogo.editar")
    )); ?>;
</script>
<script src="/assets/js/custom/apps/erp/catalogo/clasificacion_rapida.js?v=20260909-1"></script>
</body>
</html>
