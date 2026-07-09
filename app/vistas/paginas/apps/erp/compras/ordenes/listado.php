<?php
$puedeEditar = !empty($datos["puede_editar"]);
$puedeSeguimiento = !empty($datos["puede_seguimiento"]);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../../">
    <title>Ordenes de compra</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet">
    <link href="assets/css/style.bundle.css" rel="stylesheet">
</head>
<body id="kt_app_body" data-kt-app-layout="dark-sidebar" data-kt-app-header-fixed="true" data-kt-app-sidebar-enabled="true" data-kt-app-sidebar-fixed="true" class="app-default">
<input type="hidden" id="ordenes_puede_editar" value="<?= $puedeEditar ? '1' : '0' ?>">
<input type="hidden" id="ordenes_puede_seguimiento" value="<?= $puedeSeguimiento ? '1' : '0' ?>">
<div class="d-flex flex-column flex-root app-root">
    <div class="app-page flex-column flex-column-fluid">
        <?= include_once '../app/vistas/includes/header/header.php'; ?>
        <div class="app-wrapper flex-column flex-row-fluid">
            <?= include_once '../app/vistas/includes/header/sidebar.php'; ?>
            <main class="app-main flex-column flex-row-fluid">
                <div class="app-toolbar py-3 py-lg-6">
                    <div class="app-container container-fluid d-flex flex-stack">
                        <div><h1 class="page-heading text-dark fw-bold fs-3 mb-1">Ordenes de compra</h1><span class="text-muted">Compromisos formales con proveedores</span></div>
                        <a class="btn btn-primary" href="/compra/crear_orden_compra"><i class="bi bi-plus-lg"></i> Nueva orden</a>
                    </div>
                </div>
                <div class="app-content flex-column-fluid">
                    <div class="app-container container-fluid">
                        <div class="d-flex flex-column flex-md-row gap-3 mb-6">
                            <div class="position-relative flex-grow-1"><i class="bi bi-search position-absolute ms-5 mt-3 fs-3"></i><input id="ordenes_buscar" class="form-control form-control-solid ps-12" placeholder="Buscar folio, solicitud o proveedor"></div>
                            <select id="ordenes_estatus" class="form-select form-select-solid w-md-225px">
                                <option value="">Todos los estados</option><option value="borrador">Borrador</option><option value="enviada">Enviada</option><option value="parcial">Recepcion parcial</option><option value="recibida">Recibida</option><option value="cancelada">Cancelada</option>
                            </select>
                        </div>
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed gy-4">
                                <thead><tr class="text-muted fw-bold fs-7 text-uppercase"><th>Orden</th><th>Solicitud</th><th>Proveedor</th><th>Almacen</th><th>Entrega</th><th>Partidas</th><th class="text-end">Total</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
                                <tbody id="ordenes_body"></tbody>
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
<script src="/assets/js/custom/apps/erp/compras/ordenes/listado.js?v=20260617-1"></script>
</body>
</html>
