<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../../">
    <title>Paquetes - Catalogo ERP</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css">
    <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css">
    <style>
        .catalogo-paquetes-layout{display:grid;grid-template-columns:minmax(340px,420px) 1fr;gap:1.5rem;align-items:start}.catalogo-paquete-card{border:1px solid var(--bs-gray-300);border-radius:8px;padding:1rem;background:#fff}.catalogo-paquete-card.is-active{border-color:var(--bs-primary);box-shadow:0 0 0 3px rgba(0,158,247,.08)}.catalogo-paquete-search-results{max-height:240px;overflow:auto}.catalogo-paquete-sticky{position:sticky;top:90px}.catalogo-paquete-list{max-height:calc(100vh - 250px);overflow:auto}@media(max-width:1199px){.catalogo-paquetes-layout{grid-template-columns:1fr}.catalogo-paquete-sticky{position:static}.catalogo-paquete-list{max-height:none}}
    </style>
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
                        <div class="app-container container-fluid d-flex flex-stack flex-wrap gap-3">
                            <div>
                                <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Paquetes de Catalogo</h1>
                                <span class="text-muted">SKU vendibles con receta, componentes fijos y grupos configurables.</span>
                            </div>
                            <div class="d-flex gap-2">
                                <a class="btn btn-light-primary" href="/catalogoerp"><i class="bi bi-box-seam"></i> Productos ERP</a>
                                <button class="btn btn-primary" type="button" id="paquetes_nuevo"><i class="bi bi-plus-lg"></i> Nuevo paquete</button>
                            </div>
                        </div>
                    </div>
                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <div class="alert alert-light-info mb-6">
                                <div class="fw-semibold mb-1">Un paquete es un SKU vendible propio.</div>
                                <div class="text-muted fs-8">Catalogo define la receta. Rentabilidad resuelve costos, Listas define precio e Inventario valida disponibilidad cuando el paquete se venda o se arme.</div>
                            </div>
                            <div class="catalogo-paquetes-layout">
                                <div class="catalogo-paquete-sticky">
                                    <div class="card mb-6">
                                        <div class="card-body">
                                            <div class="d-flex gap-3 mb-4">
                                                <input class="form-control form-control-solid" id="paquetes_buscar" placeholder="Buscar paquete, SKU o producto">
                                                <select class="form-select form-select-solid w-150px" id="paquetes_estatus">
                                                    <option value="activos">Activos</option>
                                                    <option value="todos">Todos</option>
                                                    <option value="activo">Solo activos</option>
                                                    <option value="borrador">Borrador</option>
                                                    <option value="inactivo">Inactivos</option>
                                                </select>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <span class="fw-semibold">Paquetes</span>
                                                <span class="badge badge-light-primary" id="paquetes_total">0</span>
                                            </div>
                                            <div class="catalogo-paquete-list d-flex flex-column gap-3" id="paquetes_lista"></div>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <div class="card mb-6">
                                        <div class="card-header border-0 pt-5">
                                            <div class="card-title flex-column align-items-start">
                                                <h3 class="fw-bold mb-1" id="paquete_editor_titulo">Nuevo paquete</h3>
                                                <span class="text-muted fs-7">Crea o selecciona el SKU paquete y arma su receta.</span>
                                            </div>
                                            <div class="card-toolbar"><button class="btn btn-sm btn-light" type="button" id="paquetes_limpiar"><i class="bi bi-arrow-counterclockwise"></i> Limpiar</button></div>
                                        </div>
                                        <div class="card-body pt-0">
                                            <?php if (SesionSeguridad::tienePermiso('catalogo.editar')): ?>
                                            <div class="border rounded p-5 mb-6 bg-light">
                                                <div class="fw-semibold mb-4">Crear producto/SKU paquete minimo</div>
                                                <form id="paquete_form_sku" data-erp-ajax="true">
                                                    <div class="row g-4 align-items-end">
                                                        <div class="col-md-3"><label class="form-label required">SKU paquete</label><input class="form-control" name="sku" maxlength="150" required placeholder="PK-PECERA-40L"></div>
                                                        <div class="col-md-5"><label class="form-label required">Nombre paquete</label><input class="form-control" name="nombre" maxlength="255" required placeholder="Pecera equipada 40 L"></div>
                                                        <div class="col-md-2"><label class="form-label required">Unidad</label><select class="form-select" name="id_unidad_base" id="paquete_sku_unidad" required></select></div>
                                                        <div class="col-md-2"><button class="btn btn-light-primary w-100" type="submit"><i class="bi bi-plus-lg"></i> Crear SKU</button></div>
                                                    </div>
                                                </form>
                                            </div>
                                            <form id="paquete_form_receta" data-erp-ajax="true">
                                                <input type="hidden" name="id_paquete">
                                                <div class="row g-5">
                                                    <div class="col-lg-6">
                                                        <label class="form-label required">SKU paquete</label>
                                                        <div class="input-group mb-3">
                                                            <input class="form-control" id="paquete_buscar_sku" placeholder="Buscar SKU paquete existente">
                                                            <button class="btn btn-light-primary" type="button" id="paquete_buscar_sku_btn"><i class="bi bi-search"></i></button>
                                                        </div>
                                                        <select class="form-select" name="id_sku_paquete" id="paquete_sku" required><option value="">Selecciona desde busqueda o crea uno nuevo</option></select>
                                                        <div class="catalogo-paquete-search-results mt-3" id="paquete_resultados_sku"></div>
                                                    </div>
                                                    <div class="col-md-2"><label class="form-label">Tipo</label><select class="form-select" name="tipo_paquete"><option value="simple">Simple</option><option value="configurable">Configurable</option><option value="prearmado">Prearmado</option><option value="virtual">Virtual</option><option value="combo">Combo</option><option value="comprado_cerrado">Comprado cerrado</option></select></div>
                                                    <div class="col-md-2"><label class="form-label">Disponibilidad</label><select class="form-select" name="modo_disponibilidad"><option value="por_componentes">Por componentes</option><option value="por_existencia_armada">Por existencia armada</option><option value="mixto">Mixto</option></select></div>
                                                    <div class="col-md-2"><label class="form-label">Estado</label><select class="form-select" name="estatus"><option value="activo">Activo</option><option value="borrador">Borrador</option><option value="inactivo">Inactivo</option></select></div>
                                                    <div class="col-12 d-flex flex-wrap gap-8">
                                                        <label class="form-check form-switch form-check-custom form-check-solid"><input class="form-check-input" type="checkbox" name="permite_configuracion_cliente" value="1"><span class="form-check-label">Permite configurar opciones</span></label>
                                                        <label class="form-check form-switch form-check-custom form-check-solid"><input class="form-check-input" type="checkbox" name="requiere_armado_almacen" value="1"><span class="form-check-label">Requiere armado en almacen</span></label>
                                                        <label class="form-check form-switch form-check-custom form-check-solid"><input class="form-check-input" type="checkbox" name="permite_desarmar" value="1"><span class="form-check-label">Permite desarmar</span></label>
                                                    </div>
                                                    <div class="col-lg-8">
                                                        <label class="form-label">Agregar componente fijo</label>
                                                        <div class="input-group">
                                                            <input class="form-control" id="paquete_buscar_componente" placeholder="Buscar componente por SKU, nombre o producto">
                                                            <button class="btn btn-light-primary" type="button" id="paquete_buscar_componente_btn"><i class="bi bi-search"></i></button>
                                                        </div>
                                                        <div class="catalogo-paquete-search-results mt-3" id="paquete_resultados_componente"></div>
                                                    </div>
                                                    <div class="col-lg-4"><label class="form-label">Observaciones</label><textarea class="form-control" name="observaciones" rows="3"></textarea></div>
                                                    <div class="col-12">
                                                        <div class="table-responsive">
                                                            <table class="table align-middle table-row-dashed gy-3">
                                                                <thead><tr class="text-muted fw-bold fs-7 text-uppercase"><th>Componente fijo</th><th>Cantidad</th><th>Unidad</th><th>Factor</th><th class="text-end">Accion</th></tr></thead>
                                                                <tbody id="paquete_componentes"></tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="text-end mt-6"><button class="btn btn-primary" type="submit"><i class="bi bi-box-seam"></i> Guardar receta</button></div>
                                            </form>
                                            <div class="separator my-7"></div>
                                            <div class="row g-6">
                                                <div class="col-xl-6">
                                                    <form id="paquete_form_grupo" data-erp-ajax="true">
                                                        <input type="hidden" name="id_grupo"><input type="hidden" name="id_paquete">
                                                        <h3 class="fs-5 mb-2">Grupo configurable</h3>
                                                        <div class="text-muted fs-8 mb-5">Usalo cuando el cliente pueda elegir alternativas del paquete.</div>
                                                        <div class="row g-4">
                                                            <div class="col-md-4"><label class="form-label required">Codigo</label><input class="form-control" name="codigo" maxlength="80" required></div>
                                                            <div class="col-md-8"><label class="form-label required">Nombre</label><input class="form-control" name="nombre" maxlength="150" required></div>
                                                            <div class="col-md-3"><label class="form-label">Min</label><input class="form-control" type="number" name="min_selecciones" min="0" step="1" value="1"></div>
                                                            <div class="col-md-3"><label class="form-label">Max</label><input class="form-control" type="number" name="max_selecciones" min="1" step="1" value="1"></div>
                                                            <div class="col-md-6"><label class="form-label">Cantidad</label><select class="form-select" name="modo_cantidad"><option value="cantidad_fija">Cantidad fija</option><option value="cantidad_editable">Cantidad editable</option><option value="distribuir_total">Distribuir total</option></select></div>
                                                            <div class="col-md-4"><label class="form-label">Total grupo</label><input class="form-control" type="number" name="cantidad_total_grupo" min="0.000001" step="0.000001"></div>
                                                            <div class="col-md-4"><label class="form-label">Orden</label><input class="form-control" type="number" name="orden" value="0"></div>
                                                            <div class="col-md-4"><label class="form-label">Estado</label><select class="form-select" name="estatus"><option value="activo">Activo</option><option value="borrador">Borrador</option><option value="inactivo">Inactivo</option></select></div>
                                                            <div class="col-12"><label class="form-label">Descripcion</label><input class="form-control" name="descripcion" maxlength="255"></div>
                                                            <div class="col-12"><label class="form-check form-switch form-check-custom form-check-solid"><input class="form-check-input" type="checkbox" name="obligatorio" value="1" checked><span class="form-check-label">Grupo obligatorio</span></label></div>
                                                        </div>
                                                        <div class="text-end mt-5"><button class="btn btn-primary" type="submit"><i class="bi bi-list-check"></i> Guardar grupo</button></div>
                                                    </form>
                                                </div>
                                                <div class="col-xl-6">
                                                    <form id="paquete_form_opcion" data-erp-ajax="true">
                                                        <input type="hidden" name="id_opcion"><input type="hidden" name="id_grupo">
                                                        <h3 class="fs-5 mb-2">Opcion del grupo</h3>
                                                        <div class="text-muted fs-8 mb-5">Primero elige un grupo de la receta y despues busca el SKU opcion.</div>
                                                        <div class="row g-4">
                                                            <div class="col-12"><label class="form-label required">Grupo</label><select class="form-select" id="paquete_opcion_grupo" required><option value="">Selecciona grupo</option></select></div>
                                                            <div class="col-12">
                                                                <label class="form-label">Buscar SKU opcion</label>
                                                                <div class="input-group"><input class="form-control" id="paquete_buscar_opcion" placeholder="SKU, nombre o producto"><button class="btn btn-light-primary" type="button" id="paquete_buscar_opcion_btn"><i class="bi bi-search"></i></button></div>
                                                                <div class="catalogo-paquete-search-results mt-3" id="paquete_resultados_opcion"></div>
                                                            </div>
                                                            <div class="col-12"><label class="form-label required">SKU opcion seleccionado</label><select class="form-select" name="id_sku_opcion" id="paquete_opcion_sku" required><option value="">Selecciona desde busqueda</option></select></div>
                                                            <div class="col-md-4"><label class="form-label required">Cantidad</label><input class="form-control" type="number" name="cantidad_default" min="0.000001" step="0.000001" value="1" required></div>
                                                            <div class="col-md-4"><label class="form-label">Minima</label><input class="form-control" type="number" name="cantidad_minima" min="0.000001" step="0.000001"></div>
                                                            <div class="col-md-4"><label class="form-label">Maxima</label><input class="form-control" type="number" name="cantidad_maxima" min="0.000001" step="0.000001"></div>
                                                            <div class="col-md-4"><label class="form-label">Unidad</label><select class="form-select" name="id_unidad" id="paquete_opcion_unidad"><option value="">Base SKU</option></select></div>
                                                            <div class="col-md-4"><label class="form-label">Factor</label><input class="form-control" type="number" name="factor_conversion" min="0.000001" step="0.000001" value="1"></div>
                                                            <div class="col-md-4"><label class="form-label">Orden</label><input class="form-control" type="number" name="orden" value="0"></div>
                                                            <div class="col-md-4"><label class="form-label">Estado</label><select class="form-select" name="estatus"><option value="activo">Activo</option><option value="borrador">Borrador</option><option value="inactivo">Inactivo</option></select></div>
                                                            <div class="col-md-8 d-flex align-items-end pb-3"><label class="form-check form-switch form-check-custom form-check-solid"><input class="form-check-input" type="checkbox" name="permite_cantidad_editable" value="1"><span class="form-check-label">Cantidad editable</span></label></div>
                                                        </div>
                                                        <div class="text-end mt-5"><button class="btn btn-primary" type="submit"><i class="bi bi-ui-checks-grid"></i> Guardar opcion</button></div>
                                                    </form>
                                                </div>
                                            </div>
                                            <?php else: ?>
                                            <div class="alert alert-light-warning mb-0">Tu usuario puede consultar paquetes, pero no editarlos.</div>
                                            <?php endif; ?>
                                            <div class="alert alert-danger d-none mt-6" id="paquetes_error"></div>
                                        </div>
                                    </div>
                                    <div class="card"><div class="card-body"><div id="paquete_detalle_actual" class="text-muted">Selecciona un paquete para revisar su receta.</div></div></div>
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
<script>
    window.CATALOGO_PERMISOS = <?= json_encode(array(
        "ver" => SesionSeguridad::tienePermiso("catalogo.ver"),
        "editar" => SesionSeguridad::tienePermiso("catalogo.editar"),
        "costos" => SesionSeguridad::tienePermiso("catalogo.costos")
    )); ?>;
</script>
<script src="/assets/js/custom/apps/erp/catalogo/paquetes.js?v=20260828-1"></script>
</body>
</html>