<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../">
    <title>Rentabilidad - estudios</title>
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
                                <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Estudios de rentabilidad</h1>
                                <span class="text-muted">Analiza grupos guardados de productos sin mezclarlo con la lista completa</span>
                            </div>
                            <div class="d-flex gap-2">
                                <button class="btn btn-light" id="rentabilidad_estudios_recargar" type="button"><i class="bi bi-arrow-clockwise"></i> Actualizar</button>
                                <button class="btn btn-primary" id="rentabilidad_estudio_nuevo" type="button"><i class="bi bi-plus-lg"></i> Crear nuevo</button>
                            </div>
                        </div>
                    </div>
                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <div class="card mb-6" id="rentabilidad_estudios_bandeja_card">
                                <div class="card-header border-0 pt-5">
                                    <div class="card-title">
                                        <h3 class="fw-bold fs-5 mb-0">Estudios guardados</h3>
                                    </div>
                                </div>
                                <div class="card-body pt-0">
                                    <div class="row g-3 align-items-end mb-4">
                                        <div class="col-xl-5 col-lg-6">
                                            <label class="form-label">Buscar estudio</label>
                                            <div class="position-relative">
                                                <i class="bi bi-search fs-3 position-absolute ms-5 mt-3"></i>
                                                <input class="form-control form-control-solid ps-12" id="rentabilidad_estudios_buscar" placeholder="Nombre, folio, objetivo o lista">
                                            </div>
                                        </div>
                                        <div class="col-xl-3 col-lg-4">
                                            <label class="form-label">Estatus</label>
                                            <select class="form-select form-select-solid" id="rentabilidad_estudios_estatus">
                                                <option value="">Todos</option>
                                                <option value="activo">Activo</option>
                                                <option value="borrador">Borrador</option>
                                                <option value="cerrado">Cerrado</option>
                                                <option value="cancelado">Cancelado</option>
                                            </select>
                                        </div>
                                        <div class="col-xl-4">
                                            <div id="rentabilidad_estudios_resumen"></div>
                                        </div>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table align-middle table-row-dashed gy-3 mb-0">
                                            <thead>
                                            <tr class="text-muted fw-bold fs-8 text-uppercase">
                                                <th>Estudio</th>
                                                <th>Lista base</th>
                                                <th class="text-end">SKUs</th>
                                                <th>Parametros</th>
                                                <th>Estatus</th>
                                                <th class="text-end">Acciones</th>
                                            </tr>
                                            </thead>
                                            <tbody id="rentabilidad_estudios_tabla"></tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <div class="card mb-6 d-none" id="rentabilidad_estudio_editor_card">
                                <div class="card-header border-0 pt-5">
                                    <div class="card-title">
                                        <h3 class="fw-bold fs-5 mb-0">Editor de estudio</h3>
                                    </div>
                                    <div class="card-toolbar d-flex gap-2">
                                        <button class="btn btn-light" id="rentabilidad_estudio_cancelar" type="button"><i class="bi bi-arrow-left"></i> Volver</button>
                                        <button class="btn btn-light-success" id="rentabilidad_estudio_guardar" type="button"><i class="bi bi-save"></i> Guardar estudio</button>
                                    </div>
                                </div>
                                <div class="card-body pt-3">
                                    <input type="hidden" id="rentabilidad_estudio_id" value="">
                                    <div class="row g-3 align-items-end">
                                        <div class="col-xl-3 col-lg-4">
                                            <label class="form-label">Nombre del estudio</label>
                                            <input class="form-control form-control-solid" id="rentabilidad_estudio_nombre" maxlength="120" placeholder="Ej. Croquetas premium 8 kg">
                                        </div>
                                        <div class="col-xl-2 col-lg-4">
                                            <label class="form-label">Objetivo</label>
                                            <select class="form-select form-select-solid" id="rentabilidad_estudio_objetivo">
                                                <option value="revision_margen">Revision margen</option>
                                                <option value="cambio_precios">Cambio de precios</option>
                                                <option value="mayoreo">Mayoreo</option>
                                                <option value="alianza">Alianza</option>
                                                <option value="remate">Remate</option>
                                                <option value="lanzamiento">Lanzamiento</option>
                                                <option value="otro">Otro</option>
                                            </select>
                                        </div>
                                        <div class="col-xl-3 col-lg-4">
                                            <label class="form-label">Lista base</label>
                                            <select class="form-select form-select-solid" id="rentabilidad_estudio_lista_precio"></select>
                                        </div>
                                        <div class="col-xl-2 col-6">
                                            <label class="form-label">Gasto %</label>
                                            <input class="form-control form-control-solid" id="rentabilidad_estudio_gasto" inputmode="decimal" value="0">
                                        </div>
                                        <div class="col-xl-2 col-6">
                                            <label class="form-label">Comision %</label>
                                            <input class="form-control form-control-solid" id="rentabilidad_estudio_comision" inputmode="decimal" value="0">
                                        </div>
                                        <div class="col-xl-2 col-6">
                                            <label class="form-label">Margen objetivo %</label>
                                            <input class="form-control form-control-solid" id="rentabilidad_estudio_objetivo_margen" inputmode="decimal" value="20">
                                        </div>
                                        <div class="col-xl-2 col-6">
                                            <label class="form-label">Ajuste simulado %</label>
                                            <input class="form-control form-control-solid" id="rentabilidad_estudio_ajuste" inputmode="decimal" value="0">
                                        </div>
                                        <div class="col-xl-4">
                                            <label class="form-label">Categoria</label>
                                            <select class="form-select form-select-solid" id="rentabilidad_estudio_categoria">
                                                <option value="">Todas las categorias</option>
                                            </select>
                                        </div>
                                        <div class="col-xl-4">
                                            <label class="form-label">Buscar productos para agregar</label>
                                            <div class="d-flex gap-2">
                                                <div class="position-relative flex-grow-1">
                                                    <i class="bi bi-search fs-3 position-absolute ms-5 mt-3"></i>
                                                    <input class="form-control form-control-solid ps-12" id="rentabilidad_estudio_buscar" placeholder="SKU, familia, marca o producto">
                                                </div>
                                                <button class="btn btn-light-primary" id="rentabilidad_estudio_buscar_btn" type="button"><i class="bi bi-search"></i></button>
                                            </div>
                                        </div>
                                        <div class="col-xl-4 d-flex gap-2">
                                            <button class="btn btn-primary flex-grow-1" id="rentabilidad_estudio_analizar" type="button"><i class="bi bi-calculator"></i> Analizar seleccion</button>
                                            <button class="btn btn-light" id="rentabilidad_estudio_limpiar" type="button"><i class="bi bi-x-circle"></i> Limpiar</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-6 mb-6 d-none" id="rentabilidad_estudio_trabajo">
                                <div class="col-xl-6">
                                    <div class="card h-100">
                                        <div class="card-header border-0 pt-5">
                                            <div class="card-title">
                                                <h3 class="fw-bold fs-5 mb-0">Productos disponibles</h3>
                                            </div>
                                        </div>
                                        <div class="card-body pt-0">
                                            <div class="table-responsive">
                                                <table class="table align-middle table-row-dashed gy-3 mb-0">
                                                    <thead>
                                                    <tr class="text-muted fw-bold fs-8 text-uppercase">
                                                        <th>SKU</th>
                                                        <th>Categoria</th>
                                                        <th class="text-end">Precio</th>
                                                        <th class="text-end">Accion</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody id="rentabilidad_estudio_disponibles"></tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xl-6">
                                    <div class="card h-100">
                                        <div class="card-header border-0 pt-5">
                                            <div class="card-title">
                                                <h3 class="fw-bold fs-5 mb-0">Productos del estudio</h3>
                                            </div>
                                        </div>
                                        <div class="card-body pt-0">
                                            <div id="rentabilidad_estudio_seleccion_resumen" class="mb-3"></div>
                                            <div class="table-responsive">
                                                <table class="table align-middle table-row-dashed gy-3 mb-0">
                                                    <thead>
                                                    <tr class="text-muted fw-bold fs-8 text-uppercase">
                                                        <th>SKU seleccionado</th>
                                                        <th class="text-end">Accion</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody id="rentabilidad_estudio_seleccion"></tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card mb-6 d-none" id="rentabilidad_estudio_resultado_card">
                                <div class="card-header border-0 pt-5">
                                    <div class="card-title">
                                        <h3 class="fw-bold fs-5 mb-0">Resultado del estudio</h3>
                                    </div>
                                </div>
                                <div class="card-body pt-3" id="rentabilidad_estudio_resultado"></div>
                            </div>

                            <div class="card d-none" id="rentabilidad_estudio_detalle_card">
                                <div class="card-header border-0 pt-5">
                                    <div class="card-title">
                                        <h3 class="fw-bold fs-5 mb-0">Detalle de rentabilidad</h3>
                                    </div>
                                </div>
                                <div class="card-body pt-0">
                                    <div class="table-responsive">
                                        <table class="table align-middle table-row-dashed gy-3 mb-0">
                                            <thead>
                                            <tr class="text-muted fw-bold fs-8 text-uppercase">
                                                <th>SKU</th>
                                                <th class="text-end">Precio</th>
                                                <th class="text-end">Impuestos</th>
                                                <th class="text-end">Costo</th>
                                                <th class="text-end">Margen</th>
                                                <th class="text-end">Ganancia</th>
                                                <th class="text-end">Utilidad</th>
                                                <th class="text-end">Gasto</th>
                                                <th class="text-end">Minimo</th>
                                                <th>Estado</th>
                                                <th>Siguiente paso</th>
                                            </tr>
                                            </thead>
                                            <tbody id="rentabilidad_estudio_detalle"></tbody>
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
<script>
window.RENTABILIDAD_VISTA = "estudios";
window.RENTABILIDAD_PERMISOS = <?= json_encode(array(
    "snapshot" => Sesionseguridad::tienePermiso("rentabilidad.snapshot")
)) ?>;
</script>
<script src="/assets/js/custom/apps/erp/rentabilidad/analisis.js?v=20260930-ivas-ganancia-1"></script>
</body>
</html>
