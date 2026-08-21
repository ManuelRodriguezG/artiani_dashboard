<?php
$puedeCrear = !empty($datos["puede_crear"]);
$puedeEditar = !empty($datos["puede_editar"]);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../../">
    <title>Sugeridos de compra</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet">
    <link href="assets/css/style.bundle.css" rel="stylesheet">
</head>
<body id="kt_app_body" data-kt-app-layout="dark-sidebar" data-kt-app-header-fixed="true" data-kt-app-sidebar-enabled="true" data-kt-app-sidebar-fixed="true" class="app-default">
<input type="hidden" id="sugeridos_permiso_crear" value="<?= $puedeCrear ? 1 : 0 ?>">
<input type="hidden" id="sugeridos_permiso_editar" value="<?= $puedeEditar ? 1 : 0 ?>">
<div class="d-flex flex-column flex-root app-root">
    <div class="app-page flex-column flex-column-fluid">
        <?= include_once '../app/vistas/includes/header/header.php'; ?>
        <div class="app-wrapper flex-column flex-row-fluid">
            <?= include_once '../app/vistas/includes/header/sidebar.php'; ?>
            <main class="app-main flex-column flex-row-fluid">
                <div class="app-toolbar py-3 py-lg-6">
                    <div class="app-container container-fluid d-flex flex-stack">
                        <div>
                            <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Sugerido de compra</h1>
                            <span class="text-muted">Revisiones por proveedor sin afectar inventario</span>
                        </div>
                        <div class="d-flex gap-2">
                            <a class="btn btn-light" href="/compra/mostrar_solicitudes"><i class="bi bi-arrow-left"></i></a>
                            <?php if ($puedeCrear): ?>
                                <a class="btn btn-primary" href="/compra/sugerido_compra"><i class="bi bi-plus-lg"></i> Nueva revision</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="app-content flex-column-fluid">
                    <div class="app-container container-fluid">
                        <div id="sugeridos_alerta_schema" class="alert alert-warning d-none mb-6">
                            El esquema de Sugerido de compra aun no esta preparado. El listado aparecera cuando se autorice y ejecute la preparacion de tablas.
                        </div>
                        <div class="row g-3 mb-6">
                            <div class="col-lg-4 col-md-6">
                                <label class="form-label text-muted fs-8" for="sugeridos_buscar">Busqueda</label>
                                <div class="position-relative">
                                    <i class="bi bi-search position-absolute ms-5 mt-3 fs-3"></i>
                                    <input id="sugeridos_buscar" class="form-control form-control-solid ps-12" placeholder="Folio, proveedor u observacion">
                                </div>
                            </div>
                            <div class="col-lg-3 col-md-6">
                                <label class="form-label text-muted fs-8" for="sugeridos_estatus">Estado</label>
                                <select id="sugeridos_estatus" class="form-select form-select-solid">
                                    <option value="">Todos</option>
                                    <option value="borrador">Borrador</option>
                                    <option value="lista">Lista</option>
                                    <option value="solicitud_generada">Solicitud generada</option>
                                    <option value="cancelada">Cancelada</option>
                                </select>
                            </div>
                            <div class="col-lg-5 col-md-12">
                                <label class="form-label text-muted fs-8" for="sugeridos_proveedor">Proveedor</label>
                                <select id="sugeridos_proveedor" class="form-select form-select-solid"><option value="">Todos</option></select>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed gy-4">
                                <thead>
                                    <tr class="text-muted fw-bold fs-7 text-uppercase">
                                        <th>Folio</th>
                                        <th>Proveedor</th>
                                        <th>Fecha</th>
                                        <th class="text-end">Partidas</th>
                                        <th class="text-end">A solicitar</th>
                                        <th class="text-end">Estimado</th>
                                        <th>Solicitud</th>
                                        <th>Estado</th>
                                        <th class="text-end">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="sugeridos_body"></tbody>
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
<script src="/assets/js/custom/apps/erp/compras/sugeridos/listado.js?v=20260820-1"></script>
</body>
</html>
