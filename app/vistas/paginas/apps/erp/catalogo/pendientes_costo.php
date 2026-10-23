<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="/assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css">
    <link href="/assets/css/style.bundle.css" rel="stylesheet" type="text/css">
    <style>
        .pendientes-costo-table { min-width: 1500px; }
        .pendientes-costo-producto { min-width: 260px; }
        .pendientes-costo-alerta { min-width: 250px; }
        .pendientes-costo-accion { min-width: 220px; }
    </style>
</head>
<body id="kt_app_body" data-kt-app-layout="dark-sidebar" data-kt-app-header-fixed="true" data-kt-app-sidebar-enabled="true" data-kt-app-sidebar-fixed="true" class="app-default">
<div class="d-flex flex-column flex-root app-root" id="kt_app_root"><div class="app-page flex-column flex-column-fluid" id="kt_app_page">
<?= include_once '../app/vistas/includes/header/header.php'; ?>
<div class="app-wrapper flex-column flex-row-fluid" id="kt_app_wrapper"><?= include_once '../app/vistas/includes/header/sidebar.php'; ?>
<div class="app-main flex-column flex-row-fluid" id="kt_app_main"><div class="d-flex flex-column flex-column-fluid">
<div class="app-toolbar py-3 py-lg-6"><div class="app-container container-fluid d-flex flex-stack flex-wrap gap-4">
    <div><h1 class="page-heading text-dark fw-bold fs-3 mb-1">Calidad / Pendientes de costo</h1><span class="text-muted">Bandeja operativa read-only para validar costo, proveedor y precio público.</span></div>
    <div class="d-flex gap-2"><a class="btn btn-light-primary" href="/proveedor/mostrar_proveedores_erp"><i class="bi bi-truck"></i> Proveedores</a><a class="btn btn-light" href="/comercial/listas_precios"><i class="bi bi-tags"></i> Listas de precios</a></div>
</div></div>
<div class="app-content flex-column-fluid"><div class="app-container container-fluid">
<div class="alert alert-warning d-flex align-items-center gap-3"><i class="bi bi-shield-lock fs-2"></i><div><strong>Solo lectura.</strong> Esta primera fase detecta y propone el siguiente paso. No aplica costos, relaciones, productos ni precios.</div></div>
<div class="row g-4 mb-6" id="pendientes_costo_resumen"></div>
<div class="card mb-6"><div class="card-body py-5"><div class="row g-4 align-items-end">
    <div class="col-lg-4"><label class="form-label">Buscar</label><input id="pendientes_costo_buscar" class="form-control form-control-solid" placeholder="SKU, producto, marca o categoria"></div>
    <div class="col-lg-3"><label class="form-label">Tipo de pendiente</label><select id="pendientes_costo_problema" class="form-select form-select-solid"><option value="todos">Todos</option><option value="sin_costo">Sin costo</option><option value="sin_proveedor">Sin proveedor</option><option value="proveedor_sin_costo">Proveedor sin costo vigente</option><option value="costo_pendiente_validar">Costo pendiente de validar</option><option value="derivado">Derivado / variante / paquete / apertura</option><option value="sin_precio_publico">Sin precio LP-MENUDEO-01</option></select></div>
    <div class="col-lg-2"><label class="form-label">Filas</label><select id="pendientes_costo_limite" class="form-select form-select-solid"><option value="80">80</option><option value="120" selected>120</option><option value="200">200</option><option value="300">300</option></select></div>
    <div class="col-lg-3"><button class="btn btn-primary w-100" type="button" id="pendientes_costo_recargar"><i class="bi bi-arrow-repeat"></i> Actualizar bandeja</button></div>
</div></div></div>
<div class="card"><div class="card-header border-0 pt-5"><div class="card-title flex-column align-items-start"><h3 class="fw-bold mb-1">Productos activos pendientes</h3><span class="text-muted fs-7" id="pendientes_costo_estado">Consulta de fuentes actuales del ERP</span></div><div class="card-toolbar"><span class="badge badge-light-primary fs-7" id="pendientes_costo_total">0 filas</span></div></div>
<div class="card-body pt-0"><div class="table-responsive"><table class="table align-middle table-row-dashed gy-5 pendientes-costo-table"><thead><tr class="text-muted fw-bold fs-7 text-uppercase"><th>SKU / producto</th><th>Marca / categoria</th><th>Proveedor(es)</th><th>Costo actual / fuente</th><th>LP-MENUDEO-01</th><th>Relacion / tipo</th><th class="pendientes-costo-alerta">Alerta</th><th class="pendientes-costo-accion">Siguiente accion</th></tr></thead><tbody id="pendientes_costo_lista"></tbody></table></div></div></div>
</div></div></div></div></div>
<script src="/assets/plugins/global/plugins.bundle.js"></script><script src="/assets/js/scripts.bundle.js"></script>
<script>
    window.PENDIENTES_COSTO_PUEDE_VER_COSTOS = <?= !empty($puede_ver_costos) ? 'true' : 'false'; ?>;
</script>
<script src="/assets/js/custom/apps/erp/catalogo/pendientes_costo.js?v=20261022-1"></script>
</body></html>
