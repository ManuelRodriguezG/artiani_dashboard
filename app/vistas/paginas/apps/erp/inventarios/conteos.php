<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../">
    <title>Conteos de inventario</title>
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
                                <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Conteos fisicos</h1>
                                <span class="text-muted">Inventario ciclico con captura de diferencias sin aplicar ajuste automatico</span>
                            </div>
                            <a class="btn btn-light-primary" href="/inventario/productos_existencias"><i class="bi bi-boxes"></i> Existencias</a>
                        </div>
                    </div>
                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <div class="card mb-7">
                                <div class="card-body">
                                    <div class="row g-4 align-items-end">
                                        <div class="col-md-3"><label class="form-label required">Almacen</label><select class="form-select" id="conteo_almacen"></select></div>
                                        <div class="col-md-3"><label class="form-label">Ubicacion</label><select class="form-select" id="conteo_ubicacion"><option value="">Todas</option></select></div>
                                        <div class="col-md-2"><label class="form-label required">Tipo</label><select class="form-select" id="conteo_tipo"><option value="ciclico">Ciclico</option><option value="ubicacion">Ubicacion</option><option value="general">General</option></select></div>
                                        <div class="col-md-2"><label class="form-label">Fecha programada</label><input class="form-control" type="date" id="conteo_fecha"></div>
                                        <div class="col-md-2"><button class="btn btn-primary w-100" type="button" id="conteo_crear"><i class="bi bi-clipboard-plus"></i> Crear conteo</button></div>
                                        <div class="col-12"><label class="form-label">Observaciones</label><textarea class="form-control" rows="2" id="conteo_observaciones" maxlength="500"></textarea></div>
                                    </div>
                                </div>
                            </div>

                            <div class="card mb-7">
                                <div class="card-header border-0 pt-5">
                                    <div class="card-title"><h3 class="fw-bold mb-0">Conteos abiertos</h3></div>
                                    <div class="card-toolbar"><button class="btn btn-light-primary" id="conteo_recargar" type="button"><i class="bi bi-arrow-clockwise"></i> Actualizar</button></div>
                                </div>
                                <div class="card-body pt-3">
                                    <div class="table-responsive">
                                        <table class="table align-middle table-row-dashed gy-4">
                                            <thead><tr class="text-muted fw-bold fs-7 text-uppercase"><th>Folio</th><th>Almacen</th><th>Ubicacion</th><th>Estado</th><th>Partidas</th><th>Diferencias</th><th>Costo dif.</th><th class="text-end">Acciones</th></tr></thead>
                                            <tbody id="conteo_listado"></tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <div class="card d-none" id="conteo_detalle_card">
                                <div class="card-header border-0 pt-5">
                                    <div class="card-title"><h3 class="fw-bold mb-0" id="conteo_detalle_titulo">Detalle de conteo</h3></div>
                                    <div class="card-toolbar d-flex gap-2">
                                        <button class="btn btn-light-danger" id="conteo_cerrar" type="button"><i class="bi bi-lock"></i> Cerrar conteo</button>
                                        <button class="btn btn-primary" id="conteo_guardar" type="button"><i class="bi bi-save"></i> Guardar captura</button>
                                    </div>
                                </div>
                                <div class="card-body pt-3">
                                    <div class="alert alert-info py-3">Esta pantalla captura diferencias. El ajuste de inventario se aplicara en una fase posterior con autorizacion y respaldo.</div>
                                    <div class="table-responsive">
                                        <table class="table align-middle table-row-dashed gy-4">
                                            <thead><tr class="text-muted fw-bold fs-7 text-uppercase"><th>SKU / existencia</th><th>Lote / ubicacion</th><th>Sistema</th><th style="width:150px">Fisico</th><th>Diferencia</th><th>Motivo</th><th>Notas</th></tr></thead>
                                            <tbody id="conteo_detalle"></tbody>
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
<script src="/assets/js/custom/apps/erp/inventarios/conteos_erp.js?v=20260622-1"></script>
</body>
</html>
