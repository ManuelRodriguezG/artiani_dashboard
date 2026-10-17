<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../">
    <title>Incidencias de compras</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css">
    <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css">
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
                        <div class="app-container container-fluid d-flex flex-stack">
                            <div>
                                <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Incidencias de compras</h1>
                                <span class="text-muted">Pendientes operativos para confirmar cantidades sin afectar inventario oficial</span>
                            </div>
                            <a class="btn btn-light-primary" href="/inventario/productos_existencias"><i class="bi bi-boxes"></i> Existencias</a>
                        </div>
                    </div>
                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <div class="alert alert-info d-flex align-items-center gap-3 mb-6">
                                <i class="bi bi-info-circle fs-2"></i>
                                <div>La cantidad reportada es una evidencia estimada o física. No crea existencias, kardex ni recepciones.</div>
                            </div>
                            <div class="card">
                                <div class="card-header border-0 pt-5">
                                    <div class="card-title"><h3 class="fw-bold mb-0">Pendientes de compras</h3></div>
                                    <div class="card-toolbar"><button class="btn btn-light-primary" type="button" id="incidencias_recargar"><i class="bi bi-arrow-clockwise"></i> Actualizar</button></div>
                                </div>
                                <div class="card-body pt-3">
                                    <div class="row g-4 align-items-end mb-6">
                                        <div class="col-md-3"><label class="form-label">Almacén</label><select class="form-select" id="incidencias_almacen"><option value="">Todos</option></select></div>
                                        <div class="col-md-3"><label class="form-label">Estatus</label><select class="form-select" id="incidencias_estatus"><option value="abiertos">Abiertos</option><option value="pendiente">Pendiente</option><option value="en_revision">En revisión</option><option value="capturado">Capturado</option><option value="conciliado">Conciliado</option><option value="resuelto">Resuelto</option><option value="descartado">Descartado</option><option value="todos">Todos</option></select></div>
                                        <div class="col-md-4"><label class="form-label">Buscar</label><input class="form-control" id="incidencias_busqueda" placeholder="SKU, producto, orden o almacén"></div>
                                        <div class="col-md-2"><button class="btn btn-primary w-100" type="button" id="incidencias_buscar"><i class="bi bi-search"></i> Buscar</button></div>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table align-middle table-row-dashed gy-4">
                                            <thead><tr class="text-muted fw-bold fs-7 text-uppercase"><th>Orden</th><th>Producto</th><th>Almacén</th><th class="text-end">Comprado</th><th class="text-end">Existencia registrada</th><th class="text-end">Existencia capturada</th><th class="text-end">Quedará</th><th class="text-end">Diferencia</th><th>Estatus</th><th class="text-end">Acción</th></tr></thead>
                                            <tbody id="incidencias_listado"></tbody>
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
<script src="/assets/js/custom/apps/erp/inventarios/incidencias_compras_operativas.js?v=20261015-1"></script>
</body>
</html>
