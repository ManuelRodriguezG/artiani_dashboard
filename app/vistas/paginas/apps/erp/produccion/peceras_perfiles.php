<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../../">
    <title>Perfiles de peceras</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css">
    <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css">
</head>
<body id="kt_app_body" data-kt-app-layout="dark-sidebar" data-kt-app-header-fixed="true" data-kt-app-sidebar-enabled="true" data-kt-app-sidebar-fixed="true" class="app-default">
<div class="d-flex flex-column flex-root app-root">
    <div class="app-page flex-column flex-column-fluid">
        <?= include_once '../app/vistas/includes/header/header.php'; ?>
        <div class="app-wrapper flex-column flex-row-fluid">
            <?= include_once '../app/vistas/includes/header/sidebar.php'; ?>
            <main class="app-main flex-column flex-row-fluid">
                <div class="app-toolbar py-3 py-lg-6">
                    <div class="app-container container-fluid d-flex flex-stack flex-wrap gap-3">
                        <div>
                            <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Perfiles de peceras</h1>
                            <span class="text-muted">Borradores guardados localmente para editar o usar en pedidos de vidrio</span>
                        </div>
                        <div class="d-flex gap-2">
                            <a class="btn btn-light" href="/produccion/peceras"><i class="bi bi-plus-circle"></i> Nuevo perfil</a>
                            <a class="btn btn-primary" href="/produccion/peceras_pedido_vidrio"><i class="bi bi-layers"></i> Pedido multiple</a>
                        </div>
                    </div>
                </div>
                <div class="app-content flex-column-fluid">
                    <div class="app-container container-fluid">
                        <div class="card">
                            <div class="card-body">
                                <div class="row g-3 align-items-end mb-5">
                                    <div class="col-lg-8">
                                        <label class="form-label fw-semibold">Buscar perfil</label>
                                        <input class="form-control form-control-solid" id="peceras_perfiles_buscar" placeholder="Nombre, proveedor o medida">
                                    </div>
                                    <div class="col-lg-4 text-lg-end">
                                        <button class="btn btn-light-danger" type="button" id="peceras_perfiles_limpiar"><i class="bi bi-trash"></i> Limpiar archivados locales</button>
                                    </div>
                                </div>
                                <div class="row g-4" id="peceras_perfiles_lista"></div>
                                <div class="alert alert-info d-none" id="peceras_perfiles_vacio">No hay perfiles guardados en este navegador.</div>
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
<script src="/assets/js/custom/apps/erp/produccion/peceras_perfiles.js?v=20260804-1"></script>
</body>
</html>
