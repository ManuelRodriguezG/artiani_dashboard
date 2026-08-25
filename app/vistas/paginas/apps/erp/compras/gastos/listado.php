<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../../">
    <title>Gastos de compra</title>
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
                        <div><h1 class="page-heading text-dark fw-bold fs-3 mb-1">Gastos de compra</h1><span class="text-muted">Cargos, servicios y conceptos no inventariables capturados en ordenes</span></div>
                    </div>
                </div>
                <div class="app-content flex-column-fluid">
                    <div class="app-container container-fluid">
                        <div class="row g-4 mb-6">
                            <div class="col-md-3"><div class="border rounded p-4"><div class="text-muted fs-7">Registros</div><div class="fs-3 fw-bold" id="gastos_resumen_registros">0</div></div></div>
                            <div class="col-md-3"><div class="border rounded p-4"><div class="text-muted fs-7">Subtotal</div><div class="fs-3 fw-bold" id="gastos_resumen_subtotal">$0.00</div></div></div>
                            <div class="col-md-3"><div class="border rounded p-4"><div class="text-muted fs-7">Impuestos</div><div class="fs-3 fw-bold" id="gastos_resumen_impuestos">$0.00</div></div></div>
                            <div class="col-md-3"><div class="border rounded p-4"><div class="text-muted fs-7">Total</div><div class="fs-3 fw-bold" id="gastos_resumen_total">$0.00</div></div></div>
                        </div>
                        <div class="d-flex flex-column flex-xl-row gap-3 mb-6">
                            <div class="position-relative flex-grow-1"><i class="bi bi-search position-absolute ms-5 mt-3 fs-3"></i><input id="gastos_buscar" class="form-control form-control-solid ps-12" placeholder="Buscar orden, proveedor o concepto"></div>
                            <select id="gastos_tipo" class="form-select form-select-solid w-xl-200px"><option value="">Todos los tipos</option><option value="cargo">Cargo</option><option value="servicio">Servicio</option><option value="adicional">Adicional</option><option value="no_inventariable">No inventariable</option></select>
                            <select id="gastos_tratamiento" class="form-select form-select-solid w-xl-225px"><option value="">Todos los tratamientos</option><option value="gasto">Gasto</option><option value="rentabilidad">Rentabilidad</option><option value="prorrateo_inventario">Prorrateo inventario</option></select>
                            <select id="gastos_estatus" class="form-select form-select-solid w-xl-225px"><option value="">Activos</option><option value="pendiente_finanzas">Pendiente finanzas</option><option value="validado_finanzas">Validado finanzas</option><option value="aplicado_costos">Aplicado costos</option><option value="cancelado">Cancelado</option></select>
                        </div>
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed gy-4">
                                <thead><tr class="text-muted fw-bold fs-7 text-uppercase"><th>Orden</th><th>Proveedor</th><th>Tipo</th><th>Concepto</th><th>Tratamiento</th><th>Estado</th><th class="text-end">Subtotal</th><th class="text-end">IVA/Imp.</th><th class="text-end">Total</th><th class="text-end">Acciones</th></tr></thead>
                                <tbody id="gastos_body"></tbody>
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
<script src="/assets/js/custom/apps/erp/compras/gastos/listado.js?v=20260821-1"></script>
</body>
</html>