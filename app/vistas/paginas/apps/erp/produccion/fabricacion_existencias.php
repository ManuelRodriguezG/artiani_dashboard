<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../../">
    <title>Existencias de fabricacion</title>
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
                            <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Existencias de fabricacion</h1>
                            <span class="text-muted">Piezas disponibles, juegos armables y sugerencias de compra</span>
                        </div>
                        <div class="d-flex gap-2">
                            <a class="btn btn-light" href="/produccion/peceras_perfiles"><i class="bi bi-collection"></i> Perfiles</a>
                            <a class="btn btn-primary" href="/produccion/peceras_pedido_vidrio"><i class="bi bi-layers"></i> Pedido multiple</a>
                        </div>
                    </div>
                </div>
                <div class="app-content flex-column-fluid">
                    <div class="app-container container-fluid">
                        <div class="alert alert-info d-flex align-items-start gap-3 mb-6">
                            <i class="bi bi-box-seam fs-2"></i>
                            <div>
                                <div class="fw-bold">Control por pieza</div>
                                <div class="text-muted">Captura lo que realmente tienes en vidrio. La meta de juegos se usa solo para sugerir compras y no modifica inventario ni crea una orden automaticamente.</div>
                            </div>
                        </div>
                        <div class="card">
                            <div class="card-body">
                                <div class="row g-3 align-items-end mb-5">
                                    <div class="col-lg-8">
                                        <label class="form-label fw-semibold">Buscar perfil o pieza</label>
                                        <input class="form-control form-control-solid" id="fabricacion_existencias_buscar" placeholder="Codigo, nombre o tipo de pieza">
                                    </div>
                                    <div class="col-lg-4 text-lg-end">
                                        <button class="btn btn-light" type="button" id="fabricacion_existencias_actualizar"><i class="bi bi-arrow-clockwise"></i> Actualizar</button>
                                    </div>
                                </div>
                                <div id="fabricacion_existencias_lista"></div>
                                <div class="alert alert-info d-none" id="fabricacion_existencias_vacio">No hay perfiles con piezas configuradas.</div>
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
<script src="/assets/js/custom/apps/erp/produccion/fabricacion_existencias.js?v=20261024-1"></script>
</body>
</html>
