<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../../">
    <title>Listas variables vivos</title>
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
                            <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Listas variables vivos</h1>
                            <span class="text-muted">Interpretacion previa de archivos de peces vivos</span>
                        </div>
                        <div class="d-flex gap-3">
                            <a class="btn btn-light" href="/proveedor/mostrar_proveedores_erp">
                                <i class="bi bi-arrow-left"></i> Proveedores
                            </a>
                        </div>
                    </div>
                </div>
                <div class="app-content flex-column-fluid">
                    <div class="app-container container-fluid">
                        <div class="card mb-6">
                            <div class="card-header border-0 pt-6">
                                <div class="card-title">
                                    <h3 class="fw-bold mb-0">Archivo de proveedor</h3>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <form id="proveedores_vivos_form" enctype="multipart/form-data">
                                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Sesionseguridad::csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                                    <div class="row g-4 align-items-end">
                                        <div class="col-lg-5">
                                            <label class="form-label required">Lista XLSX o CSV</label>
                                            <input class="form-control" type="file" name="archivo_lista" id="proveedores_vivos_archivo" accept=".xlsx,.csv,.txt" required>
                                        </div>
                                        <div class="col-md-3 col-lg-2">
                                            <label class="form-label">Filas</label>
                                            <select class="form-select" name="limite_preview" id="proveedores_vivos_limite">
                                                <option value="300">300</option>
                                                <option value="600" selected>600</option>
                                                <option value="1000">1000</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4 col-lg-3">
                                            <button class="btn btn-primary w-100" type="submit" id="proveedores_vivos_analizar">
                                                <i class="bi bi-search"></i> Analizar lista
                                            </button>
                                        </div>
                                        <div class="col-lg-2 text-lg-end">
                                            <span class="badge badge-light-primary" id="proveedores_vivos_estado">Sin archivo</span>
                                        </div>
                                    </div>
                                </form>
                                <div class="alert alert-danger d-none mt-5" id="proveedores_vivos_error"></div>
                            </div>
                        </div>

                        <div class="row g-6 mb-6" id="proveedores_vivos_resumen"></div>

                        <div class="card mb-6">
                            <div class="card-header border-0 pt-6">
                                <div class="card-title flex-column align-items-start">
                                    <h3 class="fw-bold mb-1">Condiciones detectadas</h3>
                                    <span class="text-muted fs-7" id="proveedores_vivos_archivo_nombre">Sin archivo analizado</span>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="d-flex flex-wrap gap-2" id="proveedores_vivos_condiciones"></div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header border-0 pt-6">
                                <div class="card-title flex-wrap gap-3 w-100">
                                    <div class="position-relative flex-grow-1 min-w-250px">
                                        <i class="bi bi-search fs-3 position-absolute ms-5 mt-3"></i>
                                        <input id="proveedores_vivos_buscar" class="form-control form-control-solid ps-12" placeholder="Buscar renglon, especie o seccion">
                                    </div>
                                    <select id="proveedores_vivos_tipo" class="form-select form-select-solid w-md-200px">
                                        <option value="todos">Todos</option>
                                        <option value="producto">Productos</option>
                                        <option value="seccion">Secciones</option>
                                        <option value="nota">Notas</option>
                                        <option value="descuento">Descuentos</option>
                                        <option value="basura">Basura</option>
                                    </select>
                                </div>
                                <div class="card-toolbar">
                                    <span class="badge badge-light-info" id="proveedores_vivos_total">0 renglones</span>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="table-responsive">
                                    <table class="table align-middle table-row-dashed gy-3">
                                        <thead>
                                            <tr class="text-muted fw-bold fs-7 text-uppercase">
                                                <th>Fila</th>
                                                <th>Tipo</th>
                                                <th>Seccion</th>
                                                <th>Producto / texto</th>
                                                <th>Tamano</th>
                                                <th class="text-end">Precio</th>
                                                <th class="text-end">Minimo bolsa</th>
                                                <th>Datos pedido</th>
                                            </tr>
                                        </thead>
                                        <tbody id="proveedores_vivos_body">
                                            <tr><td colspan="8" class="text-center text-muted py-8">Carga una lista para iniciar revision.</td></tr>
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

<script src="assets/plugins/global/plugins.bundle.js"></script>
<script src="assets/js/scripts.bundle.js"></script>
<script>
window.ERP_CSRF_TOKEN = <?= json_encode(Sesionseguridad::csrfToken()) ?>;
</script>
<script src="/assets/js/custom/apps/erp/proveedores/listas_variables_vivos.js?v=20260921-1"></script>
</body>
</html>
