<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../../">
    <title>Organización del catálogo ERP</title>
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
                            <div><h1 class="page-heading text-dark fw-bold fs-3 mb-1">Organización del catálogo</h1><span class="text-muted">Propuestas de nombres encontradas por coincidencia exacta de SKU con listas de proveedores</span></div>
                            <a class="btn btn-light-primary" href="/catalogoerp"><i class="bi bi-box-seam"></i> Productos ERP</a>
                        </div>
                    </div>
                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <div class="d-flex gap-4 mb-6">
                                <input id="organizacion_buscar" class="form-control form-control-solid mw-400px" placeholder="Buscar SKU, maestro o nombre">
                                <select id="organizacion_estado" class="form-select form-select-solid mw-200px"><option value="pendiente">Pendientes</option><option value="">Todos</option><option value="aprobado">Aprobados</option><option value="descartado">Descartados</option></select>
                                <span class="badge badge-light-primary align-self-center fs-6" id="organizacion_total">0</span>
                            </div>
                            <div class="card mb-7">
                                <div class="card-header border-0 pt-5"><div class="card-title"><h3 class="fw-bold mb-0">Fusionar productos maestros</h3></div></div>
                                <div class="card-body pt-0">
                                    <form id="fusion_form" data-erp-ajax="true">
                                        <div class="row g-5 mb-5">
                                            <div class="col-lg-6">
                                                <label class="form-label required">Buscar origen</label>
                                                <div class="position-relative">
                                                    <i class="bi bi-search fs-3 position-absolute ms-5 mt-3"></i>
                                                    <input class="form-control form-control-solid ps-12" id="fusion_buscar_origen" placeholder="Buscar por SKU, codigo o nombre">
                                                </div>
                                                <div class="mt-3" id="fusion_origen_seleccion"></div>
                                                <div class="mt-3" id="fusion_resultados_origen"></div>
                                            </div>
                                            <div class="col-lg-6">
                                                <label class="form-label required">Buscar destino</label>
                                                <div class="position-relative">
                                                    <i class="bi bi-search fs-3 position-absolute ms-5 mt-3"></i>
                                                    <input class="form-control form-control-solid ps-12" id="fusion_buscar_destino" placeholder="Buscar por SKU, codigo o nombre">
                                                </div>
                                                <div class="mt-3" id="fusion_destino_seleccion"></div>
                                                <div class="mt-3" id="fusion_resultados_destino"></div>
                                            </div>
                                        </div>
                                        <div class="row g-5 align-items-end">
                                            <div class="col-md-3"><label class="form-label required">ID maestro origen</label><input class="form-control" name="id_producto_origen" type="number" min="1" required></div>
                                            <div class="col-md-3"><label class="form-label required">ID maestro destino</label><input class="form-control" name="id_producto_destino" type="number" min="1" required></div>
                                            <div class="col-md-4"><label class="form-label required">Motivo</label><input class="form-control" name="motivo" maxlength="255" minlength="10" required placeholder="Duplicado confirmado; conservar destino como maestro"></div>
                                            <div class="col-md-2 text-end"><button class="btn btn-light-primary w-100" type="button" id="fusion_previsualizar"><i class="bi bi-search"></i> Revisar</button></div>
                                        </div>
                                        <div id="fusion_preview" class="mt-6"></div>
                                        <div class="alert alert-danger d-none mt-6" id="fusion_error"></div>
                                    </form>
                                </div>
                            </div>
                            <div class="card mb-7">
                                <div class="card-header border-0 pt-5">
                                    <div class="card-title flex-column align-items-start">
                                        <h3 class="fw-bold mb-1">Historial de fusiones</h3>
                                        <span class="text-muted fs-7">Auditoria de productos maestros fusionados. La reversa requiere flujo controlado.</span>
                                    </div>
                                    <div class="card-toolbar"><span class="badge badge-light-primary fs-7" id="fusion_historial_total">0</span></div>
                                </div>
                                <div class="card-body pt-0">
                                    <div class="table-responsive">
                                        <table class="table align-middle table-row-dashed gy-5">
                                            <thead><tr class="text-muted fw-bold fs-7 text-uppercase"><th>Fecha</th><th>Origen</th><th>Destino</th><th>Motivo</th><th>SKUs</th><th>Reversa</th></tr></thead>
                                            <tbody id="fusion_historial_lista"></tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="card"><div class="card-body">
                                <div class="table-responsive"><table class="table align-middle table-row-dashed gy-5">
                                    <thead><tr class="text-muted fw-bold fs-7 text-uppercase"><th>SKU</th><th>Producto maestro</th><th>Nombre actual</th><th>Referencia proveedor</th><th>Propuesta editable</th><th class="text-end">Decisión</th></tr></thead>
                                    <tbody id="organizacion_lista"></tbody>
                                </table></div>
                            </div></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="assets/plugins/global/plugins.bundle.js"></script>
<script src="assets/js/scripts.bundle.js"></script>
<script>
    window.CATALOGO_PERMISOS = <?= json_encode(array(
        "ver" => SesionSeguridad::tienePermiso("catalogo.ver"),
        "editar" => SesionSeguridad::tienePermiso("catalogo.editar"),
        "costos" => SesionSeguridad::tienePermiso("catalogo.costos")
    )); ?>;
</script>
<script src="/assets/js/custom/apps/erp/catalogo/organizacion.js?v=20260604-2"></script>
</body>
</html>
