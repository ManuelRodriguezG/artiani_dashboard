<?php
$puedeEditar = !empty($datos["puede_editar"]);
$puedeAprobar = !empty($datos["puede_aprobar"]);
$puedeCancelar = !empty($datos["puede_cancelar"]);
$puedeCrear = !empty($datos["puede_crear"]);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../../">
    <title>Solicitudes de compra</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet">
    <link href="assets/css/style.bundle.css" rel="stylesheet">
</head>
<body id="kt_app_body" data-kt-app-layout="dark-sidebar" data-kt-app-header-fixed="true" data-kt-app-sidebar-enabled="true" data-kt-app-sidebar-fixed="true" class="app-default">
<input type="hidden" id="solicitud_permiso_editar" value="<?= $puedeEditar ? 1 : 0 ?>">
<input type="hidden" id="solicitud_permiso_aprobar" value="<?= $puedeAprobar ? 1 : 0 ?>">
<input type="hidden" id="solicitud_permiso_cancelar" value="<?= $puedeCancelar ? 1 : 0 ?>">
<input type="hidden" id="solicitud_permiso_crear" value="<?= $puedeCrear ? 1 : 0 ?>">
<div class="d-flex flex-column flex-root app-root">
    <div class="app-page flex-column flex-column-fluid">
        <?= include_once '../app/vistas/includes/header/header.php'; ?>
        <div class="app-wrapper flex-column flex-row-fluid">
            <?= include_once '../app/vistas/includes/header/sidebar.php'; ?>
            <main class="app-main flex-column flex-row-fluid">
                        <div class="app-toolbar py-3 py-lg-6">
                        <div class="app-container container-fluid d-flex flex-stack">
                            <div><h1 class="page-heading text-dark fw-bold fs-3 mb-1">Solicitudes de compra</h1><span class="text-muted">Requisiciones internas basadas en SKU ERP</span></div>
                        <?php if ($puedeCrear): ?>
                            <a class="btn btn-primary" href="/compra/solicitud_compra_nueva"><i class="bi bi-plus-lg"></i> Nueva solicitud</a>
                        <?php endif; ?>
                        </div>
                    </div>
                <div class="app-content flex-column-fluid">
                    <div class="app-container container-fluid">
                        <div class="row g-3 mb-6">
                            <div class="col-lg-3 col-md-6">
                                <label class="form-label text-muted fs-8" for="solicitudes_buscar">Busqueda</label>
                                <div class="position-relative"><i class="bi bi-search position-absolute ms-5 mt-3 fs-3"></i><input id="solicitudes_buscar" class="form-control form-control-solid ps-12" placeholder="Folio, proveedor u orden"></div>
                            </div>
                            <div class="col-lg-2 col-md-6">
                                <label class="form-label text-muted fs-8" for="solicitudes_estatus">Estado</label>
                                <select id="solicitudes_estatus" class="form-select form-select-solid">
                                <option value="">Todos los estados</option><option value="borrador">Borrador</option><option value="pendiente">Pendiente</option><option value="aprobada">Aprobada</option><option value="rechazada">Rechazada</option><option value="orden_generada">Orden generada</option><option value="cancelada">Cancelada</option>
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-6">
                                <label class="form-label text-muted fs-8" for="solicitudes_prioridad">Prioridad</label>
                                <select id="solicitudes_prioridad" class="form-select form-select-solid">
                                    <option value="">Todas</option><option value="baja">Baja</option><option value="normal">Normal</option><option value="alta">Alta</option><option value="urgente">Urgente</option>
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-6">
                                <label class="form-label text-muted fs-8" for="solicitudes_proveedor">Proveedor</label>
                                <select id="solicitudes_proveedor" class="form-select form-select-solid"><option value="">Todos</option></select>
                            </div>
                            <div class="col-lg-2 col-md-6">
                                <label class="form-label text-muted fs-8" for="solicitudes_almacen">Almacen</label>
                                <select id="solicitudes_almacen" class="form-select form-select-solid"><option value="">Todos</option></select>
                            </div>
                            <div class="col-lg-2 col-md-6">
                                <label class="form-label text-muted fs-8" for="solicitudes_solicitante">Solicitante</label>
                                <select id="solicitudes_solicitante" class="form-select form-select-solid"><option value="">Todos</option></select>
                            </div>
                            <div class="col-lg-1 col-md-6">
                                <label class="form-label text-muted fs-8" for="solicitudes_fecha_desde">Desde</label>
                                <input id="solicitudes_fecha_desde" class="form-control form-control-solid" type="date">
                            </div>
                            <div class="col-lg-1 col-md-6">
                                <label class="form-label text-muted fs-8" for="solicitudes_fecha_hasta">Hasta</label>
                                <input id="solicitudes_fecha_hasta" class="form-control form-control-solid" type="date">
                            </div>
                            <div class="col-lg-1 col-md-6">
                                <label class="form-label text-muted fs-8" for="solicitudes_con_orden">Orden</label>
                                <select id="solicitudes_con_orden" class="form-select form-select-solid">
                                    <option value="">Todas</option><option value="1">Con orden</option><option value="0">Sin orden</option>
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-6">
                                <label class="form-label text-muted fs-8" for="solicitudes_productos_nuevos">Productos</label>
                                <select id="solicitudes_productos_nuevos" class="form-select form-select-solid">
                                    <option value="">Todos</option><option value="1">Con productos nuevos</option>
                                </select>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed gy-4">
                                <thead><tr class="text-muted fw-bold fs-7 text-uppercase"><th>Folio</th><th>Solicitante</th><th>Proveedor</th><th>Almacen</th><th>Fecha requerida</th><th>Prioridad</th><th>Partidas</th><th>Orden</th><th class="text-end">Estimado</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
                                <tbody id="solicitudes_body"></tbody>
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
<script src="/assets/js/custom/apps/erp/compras/solicitudes/listado.js?v=20260606-1"></script>
</body>
</html>
