<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../">
    <title>Configuracion de almacen</title>
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
                                <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Configuracion de almacen</h1>
                                <span class="text-muted">Almacenes, puntos de venta y ubicaciones internas</span>
                            </div>
                        </div>
                    </div>
                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <ul class="nav nav-tabs nav-line-tabs mb-5 fs-6">
                                <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#alm_cfg_tab_almacenes">Almacenes</a></li>
                                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#alm_cfg_tab_ubicaciones">Ubicaciones</a></li>
                            </ul>
                            <div class="tab-content">
                                <div class="tab-pane fade show active" id="alm_cfg_tab_almacenes">
                                    <div class="row g-5">
                                        <div class="col-xl-4">
                                            <div class="card">
                                                <div class="card-header"><div class="card-title"><h3 class="fw-bold m-0">Almacen</h3></div></div>
                                                <div class="card-body">
                                                    <input type="hidden" id="alm_cfg_id">
                                                    <div class="row g-3">
                                                        <div class="col-md-5"><label class="form-label">Codigo</label><input class="form-control form-control-solid" id="alm_cfg_codigo"></div>
                                                        <div class="col-md-7"><label class="form-label">Tipo</label><select class="form-select form-select-solid" id="alm_cfg_tipo"></select></div>
                                                        <div class="col-12"><label class="form-label">Nombre operativo</label><input class="form-control form-control-solid" id="alm_cfg_nombre"></div>
                                                        <div class="col-12"><label class="form-label">Nombre comercial</label><input class="form-control form-control-solid" id="alm_cfg_nombre_comercial"></div>
                                                        <div class="col-md-6"><label class="form-label">Estatus</label><select class="form-select form-select-solid" id="alm_cfg_estatus"><option value="activo">Activo</option><option value="inactivo">Inactivo</option></select></div>
                                                        <div class="col-md-6"><label class="form-label">Orden</label><input class="form-control form-control-solid" id="alm_cfg_orden" type="number" value="100"></div>
                                                        <div class="col-md-6"><label class="form-label">Pais</label><input class="form-control form-control-solid" id="alm_cfg_pais"></div>
                                                        <div class="col-md-6"><label class="form-label">Estado</label><input class="form-control form-control-solid" id="alm_cfg_estado"></div>
                                                        <div class="col-md-6"><label class="form-label">Municipio</label><input class="form-control form-control-solid" id="alm_cfg_municipio"></div>
                                                        <div class="col-md-6"><label class="form-label">Ciudad</label><input class="form-control form-control-solid" id="alm_cfg_ciudad"></div>
                                                        <div class="col-md-6"><label class="form-label">Colonia</label><input class="form-control form-control-solid" id="alm_cfg_colonia"></div>
                                                        <div class="col-md-6"><label class="form-label">Codigo postal</label><input class="form-control form-control-solid" id="alm_cfg_cp"></div>
                                                        <div class="col-md-8"><label class="form-label">Calle</label><input class="form-control form-control-solid" id="alm_cfg_calle"></div>
                                                        <div class="col-md-4"><label class="form-label">Numero</label><input class="form-control form-control-solid" id="alm_cfg_numero"></div>
                                                        <div class="col-md-6"><label class="form-label">Contacto recepcion</label><input class="form-control form-control-solid" id="alm_cfg_contacto"></div>
                                                        <div class="col-md-6"><label class="form-label">Telefono recepcion</label><input class="form-control form-control-solid" id="alm_cfg_telefono"></div>
                                                        <div class="col-12"><label class="form-label">Email recepcion</label><input class="form-control form-control-solid" id="alm_cfg_email"></div>
                                                        <div class="col-12"><label class="form-label">Referencias direccion</label><input class="form-control form-control-solid" id="alm_cfg_referencias"></div>
                                                        <div class="col-12">
                                                            <label class="form-label">Operaciones permitidas</label>
                                                            <div class="d-flex flex-wrap gap-4">
                                                                <label class="form-check form-check-custom form-check-solid"><input class="form-check-input" type="checkbox" id="alm_cfg_recepcion"><span class="form-check-label">Recepcion</span></label>
                                                                <label class="form-check form-check-custom form-check-solid"><input class="form-check-input" type="checkbox" id="alm_cfg_venta"><span class="form-check-label">Venta</span></label>
                                                                <label class="form-check form-check-custom form-check-solid"><input class="form-check-input" type="checkbox" id="alm_cfg_preparacion"><span class="form-check-label">Preparacion</span></label>
                                                                <label class="form-check form-check-custom form-check-solid"><input class="form-check-input" type="checkbox" id="alm_cfg_ajustes"><span class="form-check-label">Ajustes</span></label>
                                                                <label class="form-check form-check-custom form-check-solid"><input class="form-check-input" type="checkbox" id="alm_cfg_tecnico"><span class="form-check-label">Tecnico</span></label>
                                                            </div>
                                                        </div>
                                                        <div class="col-12"><label class="form-label">Observaciones</label><textarea class="form-control form-control-solid" id="alm_cfg_observaciones" rows="3"></textarea></div>
                                                    </div>
                                                    <div class="d-flex gap-2 mt-5">
                                                        <button class="btn btn-primary" id="alm_cfg_guardar" type="button"><i class="bi bi-save"></i> Guardar</button>
                                                        <button class="btn btn-light" id="alm_cfg_nuevo" type="button">Nuevo</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-xl-8">
                                            <div class="card">
                                                <div class="card-header border-0 pt-5">
                                                    <div class="card-title"><input class="form-control form-control-solid w-300px" id="alm_cfg_buscar" placeholder="Buscar almacen"></div>
                                                    <div class="card-toolbar"><select class="form-select form-select-solid w-175px" id="alm_cfg_filtro_estatus"><option value="">Todos</option><option value="activo" selected>Activos</option><option value="inactivo">Inactivos</option></select></div>
                                                </div>
                                                <div class="card-body pt-0">
                                                    <div class="table-responsive">
                                                        <table class="table align-middle table-row-dashed gy-4">
                                                            <thead><tr class="text-muted fw-bold fs-7 text-uppercase"><th>Codigo</th><th>Almacen</th><th>Tipo</th><th>Operaciones</th><th>Estatus</th><th class="text-end">Acciones</th></tr></thead>
                                                            <tbody id="alm_cfg_body"></tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="alm_cfg_tab_ubicaciones">
                                    <div class="row g-5">
                                        <div class="col-xl-4">
                                            <div class="card">
                                                <div class="card-header"><div class="card-title"><h3 class="fw-bold m-0">Ubicacion</h3></div></div>
                                                <div class="card-body">
                                                    <input type="hidden" id="alm_ubi_id">
                                                    <div class="mb-3"><label class="form-label">Almacen</label><select class="form-select form-select-solid" id="alm_ubi_almacen"></select></div>
                                                    <div class="mb-3"><label class="form-label">Codigo</label><input class="form-control form-control-solid" id="alm_ubi_codigo"></div>
                                                    <div class="mb-3"><label class="form-label">Nombre</label><input class="form-control form-control-solid" id="alm_ubi_nombre"></div>
                                                    <div class="row g-3">
                                                        <div class="col-md-6"><label class="form-label">Zona</label><input class="form-control form-control-solid" id="alm_ubi_zona"></div>
                                                        <div class="col-md-6"><label class="form-label">Pasillo</label><input class="form-control form-control-solid" id="alm_ubi_pasillo"></div>
                                                        <div class="col-md-6"><label class="form-label">Rack</label><input class="form-control form-control-solid" id="alm_ubi_rack"></div>
                                                        <div class="col-md-6"><label class="form-label">Nivel</label><input class="form-control form-control-solid" id="alm_ubi_nivel"></div>
                                                    </div>
                                                    <div class="mt-3"><label class="form-label">Contenedor</label><input class="form-control form-control-solid" id="alm_ubi_contenedor"></div>
                                                    <div class="mt-3"><label class="form-label">Estatus</label><select class="form-select form-select-solid" id="alm_ubi_estatus"><option value="activa">Activa</option><option value="inactiva">Inactiva</option></select></div>
                                                    <div class="mt-3"><label class="form-label">Descripcion</label><textarea class="form-control form-control-solid" id="alm_ubi_descripcion" rows="3"></textarea></div>
                                                    <div class="d-flex gap-2 mt-5">
                                                        <button class="btn btn-primary" id="alm_ubi_guardar" type="button"><i class="bi bi-save"></i> Guardar</button>
                                                        <button class="btn btn-light" id="alm_ubi_nuevo" type="button">Nuevo</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-xl-8">
                                            <div class="card">
                                                <div class="card-header border-0 pt-5">
                                                    <div class="card-title"><select class="form-select form-select-solid w-300px" id="alm_ubi_filtro_almacen"></select></div>
                                                    <div class="card-toolbar"><select class="form-select form-select-solid w-175px" id="alm_ubi_filtro_estatus"><option value="">Todas</option><option value="activa" selected>Activas</option><option value="inactiva">Inactivas</option></select></div>
                                                </div>
                                                <div class="card-body pt-0">
                                                    <div class="table-responsive">
                                                        <table class="table align-middle table-row-dashed gy-4">
                                                            <thead><tr class="text-muted fw-bold fs-7 text-uppercase"><th>Codigo</th><th>Ubicacion</th><th>Almacen</th><th>Detalle</th><th>Estatus</th><th class="text-end">Acciones</th></tr></thead>
                                                            <tbody id="alm_ubi_body"></tbody>
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
            </div>
        </div>
    </div>
</div>
<script src="assets/plugins/global/plugins.bundle.js"></script>
<script src="assets/js/scripts.bundle.js"></script>
<script src="/assets/js/custom/apps/erp/almacen/configuracion.js?v=20260621-1"></script>
</body>
</html>
