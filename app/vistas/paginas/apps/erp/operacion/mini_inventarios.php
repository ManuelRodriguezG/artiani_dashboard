<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../">
    <title>Mini inventarios operativos</title>
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
                            <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Mini inventarios operativos</h1>
                            <span class="text-muted">Bandeja local para continuar, revisar o decidir pedidos y tareas</span>
                        </div>
                        <div class="d-flex gap-2">
                            <a class="btn btn-light" href="/operacion/mini_inventario"><i class="bi bi-pencil-square"></i> Abrir editor</a>
                            <button class="btn btn-primary" id="mini_inv_lista_nuevo" type="button"><i class="bi bi-plus-circle"></i> Nuevo mini inventario</button>
                        </div>
                    </div>
                </div>
                <div class="app-content flex-column-fluid">
                    <div class="app-container container-fluid">
                        <div class="alert alert-info mb-6">
                            Esta bandeja usa datos locales de este navegador. Todavia no guarda en BD ni comparte mini inventarios entre equipos o usuarios.
                        </div>

                        <div class="card mb-6">
                            <div class="card-body">
                                <div class="row g-4 align-items-end">
                                    <div class="col-md-8">
                                        <label class="form-label" for="mini_inv_lista_buscar">Buscar mini inventario</label>
                                        <div class="position-relative">
                                            <i class="bi bi-search position-absolute ms-5 mt-3 fs-3"></i>
                                            <input class="form-control form-control-solid ps-12" id="mini_inv_lista_buscar" placeholder="Nombre, estado, SKU, proveedor, nota o responsable">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label" for="mini_inv_lista_estado">Estado</label>
                                        <select class="form-select form-select-solid" id="mini_inv_lista_estado">
                                            <option value="">Todos</option>
                                            <option value="borrador">Borrador</option>
                                            <option value="en_revision">En revision</option>
                                            <option value="listo_decision">Listo para decision</option>
                                            <option value="cerrado_manual">Cerrado manual</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-3 mb-6" id="mini_inv_lista_resumen"></div>

                        <div class="card">
                            <div class="card-header border-0 pt-5">
                                <div>
                                    <h3 class="fw-bold mb-1">Bandeja</h3>
                                    <div class="text-muted fs-7" id="mini_inv_lista_estado_texto">Mini inventarios guardados localmente.</div>
                                </div>
                            </div>
                            <div class="card-body pt-3">
                                <div class="table-responsive">
                                    <table class="table align-middle table-row-dashed gy-4">
                                        <thead>
                                            <tr class="text-muted fw-bold fs-7 text-uppercase">
                                                <th>Mini inventario</th>
                                                <th>Estado</th>
                                                <th class="text-end">Productos</th>
                                                <th class="text-end">Tareas</th>
                                                <th class="text-end">Comprar</th>
                                                <th>Ultima edicion</th>
                                                <th class="text-end">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody id="mini_inv_lista_body"></tbody>
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
<script src="/assets/js/custom/apps/erp/operacion/mini_inventarios.js?v=20260928-2"></script>
</body>
</html>
