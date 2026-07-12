<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../../">
    <title>Configuración del catálogo ERP</title>
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
                            <div><h1 class="page-heading text-dark fw-bold fs-3 mb-1">Configuración del catálogo</h1><span class="text-muted">Catálogos maestros utilizados por productos y SKU</span></div>
                            <button class="btn btn-primary" id="catalogo_aux_nuevo" data-permiso-editar><i class="bi bi-plus-lg"></i> Nueva marca</button>
                        </div>
                    </div>
                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <div class="card mb-7">
                                <div class="card-body py-4">
                                    <div class="d-flex flex-wrap gap-2" id="catalogo_config_modulos">
                                        <button type="button" class="btn btn-sm btn-primary" data-config-modulo-boton="maestros"><i class="bi bi-grid-3x3-gap"></i> Catálogos maestros</button>
                                        <button type="button" class="btn btn-sm btn-light" data-config-modulo-boton="calidad"><i class="bi bi-clipboard-check"></i> Calidad</button>
                                        <button type="button" class="btn btn-sm btn-light" data-config-modulo-boton="clasificacion" data-permiso-editar><i class="bi bi-tags"></i> Clasificación pendiente</button>
                                        <button type="button" class="btn btn-sm btn-light" data-config-modulo-boton="clasificacion_heredada"><i class="bi bi-diagram-3"></i> Clasificación heredada</button>
                                        <button type="button" class="btn btn-sm btn-light" data-config-modulo-boton="reglas" data-permiso-editar><i class="bi bi-sliders"></i> Reglas operativas</button>
                                        <button type="button" class="btn btn-sm btn-light" data-config-modulo-boton="proveedor_costos" data-permiso-costos><i class="bi bi-link-45deg"></i> Proveedor y costos</button>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-3 mb-4" data-config-modulo="calidad">
                                <span class="badge badge-light-primary fs-7">Calidad</span>
                                <div class="separator flex-grow-1"></div>
                            </div>
                            <div class="card mb-7" data-config-modulo="calidad">
                                <div class="card-header border-0 pt-5">
                                    <div class="card-title">
                                        <div>
                                            <h3 class="fw-bold mb-1">Calidad del catálogo ERP</h3>
                                            <span class="text-muted fs-7">Pendientes que conviene resolver antes de conectar inventario y canales de venta</span>
                                        </div>
                                    </div>
                                    <div class="card-toolbar">
                                        <button class="btn btn-sm btn-light-success me-3" id="catalogo_metadatos_sincronizar" type="button" data-permiso-editar><i class="bi bi-tags"></i> Sincronizar marcas y categorías</button>
                                        <button class="btn btn-sm btn-light-primary" id="catalogo_auditoria_recargar" type="button"><i class="bi bi-arrow-clockwise"></i> Actualizar</button>
                                    </div>
                                </div>
                                <div class="card-body pt-3">
                                    <div class="row g-4 mb-6" id="catalogo_auditoria_resumen"></div>
                                    <div class="table-responsive">
                                        <table class="table align-middle table-row-dashed gy-4 mb-0">
                                            <thead><tr class="text-muted fw-bold fs-7 text-uppercase"><th>Objetivo</th><th>Severidad</th><th>Problema</th><th>Producto</th><th>SKU</th><th class="text-end">Acción</th></tr></thead>
                                            <tbody id="catalogo_auditoria_problemas"></tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="card mb-7" data-config-modulo="calidad">
                                <div class="card-header border-0 pt-5">
                                    <div class="card-title">
                                        <div>
                                            <h3 class="fw-bold mb-1">Incidencias persistentes</h3>
                                            <span class="text-muted fs-7">Pendientes con estatus, evidencia y resolución documentada</span>
                                        </div>
                                    </div>
                                    <div class="card-toolbar">
                                        <button class="btn btn-sm btn-light-warning me-2" id="catalogo_incidencias_sync_bloqueos" type="button" data-permiso-editar><i class="bi bi-exclamation-triangle"></i> Bloqueos</button>
                                        <button class="btn btn-sm btn-light-info me-2" id="catalogo_incidencias_sync_compras" type="button" data-permiso-editar><i class="bi bi-receipt"></i> Compras/XML</button>
                                        <button class="btn btn-sm btn-light-primary" id="catalogo_incidencias_recargar" type="button"><i class="bi bi-arrow-clockwise"></i> Actualizar</button>
                                    </div>
                                </div>
                                <div class="card-body pt-3">
                                    <div class="row g-3 mb-5">
                                        <div class="col-md-3">
                                            <select class="form-select form-select-solid" id="catalogo_incidencias_estatus">
                                                <option value="abiertas">Abiertas</option>
                                                <option value="pendiente">Pendiente</option>
                                                <option value="en_revision">En revisión</option>
                                                <option value="bloqueada">Bloqueada</option>
                                                <option value="resuelta">Resuelta</option>
                                                <option value="descartada">Descartada</option>
                                                <option value="todas">Todas</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <select class="form-select form-select-solid" id="catalogo_incidencias_origen">
                                                <option value="">Todos los orígenes</option>
                                                <option value="catalogo">Catálogo</option>
                                                <option value="compra">Compra</option>
                                                <option value="xml">XML</option>
                                                <option value="migracion">Migración</option>
                                                <option value="captura_manual">Captura manual</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <select class="form-select form-select-solid" id="catalogo_incidencias_severidad">
                                                <option value="">Todas las severidades</option>
                                                <option value="bloqueante">Bloqueante</option>
                                                <option value="alta">Alta</option>
                                                <option value="media">Media</option>
                                                <option value="advertencia">Advertencia</option>
                                                <option value="informativo">Informativo</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <select class="form-select form-select-solid" id="catalogo_incidencias_tipo">
                                                <option value="">Todos los tipos</option>
                                                <option value="fiscal_incompleto">Fiscal incompleto</option>
                                                <option value="sku_sin_proveedor_activo">Sin proveedor activo</option>
                                                <option value="sku_sin_codigo_principal">Sin código principal</option>
                                                <option value="inventario_reorden_cero">Reorden cero</option>
                                                <option value="variantes_sin_atributos">Variantes sin atributos</option>
                                                <option value="compra_producto_nuevo">Producto nuevo</option>
                                                <option value="xml_concepto_sin_coincidencia">XML sin coincidencia</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="d-flex flex-wrap gap-3 mb-5" id="catalogo_incidencias_resumen"></div>
                                    <div class="table-responsive">
                                        <table class="table align-middle table-row-dashed gy-4 mb-0">
                                            <thead><tr class="text-muted fw-bold fs-7 text-uppercase"><th>Objetivo</th><th>Prioridad</th><th>Incidencia</th><th>Entidad</th><th>Origen</th><th class="text-end">Acciones</th></tr></thead>
                                            <tbody id="catalogo_incidencias_tabla"></tbody>
                                        </table>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-between gap-3 pt-5">
                                        <span class="text-muted fs-7" id="catalogo_incidencias_info"></span>
                                        <div class="d-flex gap-2">
                                            <button class="btn btn-sm btn-light" id="catalogo_incidencias_anterior" type="button"><i class="bi bi-chevron-left"></i></button>
                                            <button class="btn btn-sm btn-light" id="catalogo_incidencias_siguiente" type="button"><i class="bi bi-chevron-right"></i></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-3 mb-4" data-config-modulo="proveedor_costos" data-permiso-costos>
                                <span class="badge badge-light-success fs-7">Proveedor</span>
                                <div class="separator flex-grow-1"></div>
                            </div>
                            <div class="card mb-7" data-config-modulo="proveedor_costos" data-permiso-costos>
                                <div class="card-header border-0 pt-5">
                                    <div class="card-title">
                                        <div>
                                            <h3 class="fw-bold mb-1">Productos por proveedor</h3>
                                            <span class="text-muted fs-7">Puente temporal desde listas históricas; Proveedores/Compras debe validar la relación final</span>
                                        </div>
                                    </div>
                                    <div class="card-toolbar">
                                        <button class="btn btn-sm btn-primary" id="catalogo_proveedores_sincronizar" type="button"><i class="bi bi-link-45deg"></i> Vincular seleccionadas</button>
                                    </div>
                                </div>
                                <div class="card-body pt-3">
                                    <div class="d-flex flex-wrap gap-3 mb-5" id="catalogo_proveedores_resumen"></div>
                                    <div class="alert alert-light-warning">
                                        Vincula solo coincidencias exactas revisadas. La unidad de compra inicia como la unidad base del SKU y factor 1; revisa cajas, granel o empaques antes de comprar.
                                    </div>
                                    <div class="form-check form-check-custom form-check-solid mb-4">
                                        <input class="form-check-input" type="checkbox" id="catalogo_proveedores_seleccionar_todos">
                                        <label class="form-check-label" for="catalogo_proveedores_seleccionar_todos">Seleccionar coincidencias visibles</label>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table align-middle table-row-dashed gy-4 mb-0">
                                            <thead><tr class="text-muted fw-bold fs-7 text-uppercase"><th class="w-30px"></th><th>SKU ERP</th><th>Producto del proveedor</th><th>Proveedor</th><th>Lista</th><th class="text-end">Costo lista</th></tr></thead>
                                            <tbody id="catalogo_proveedores_pendientes"></tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-3 mb-4" data-config-modulo="clasificacion" data-permiso-editar>
                                <span class="badge badge-light-warning fs-7">Clasificación pendiente</span>
                                <div class="separator flex-grow-1"></div>
                            </div>
                            <div class="card mb-7" data-config-modulo="clasificacion" data-permiso-editar>
                                <div class="card-header border-0 pt-5">
                                    <div class="card-title">
                                        <div>
                                            <h3 class="fw-bold mb-1">Clasificación pendiente</h3>
                                            <span class="text-muted fs-7">Asigna categoría principal faltante y resuelve marcas contradictorias</span>
                                        </div>
                                    </div>
                                    <div class="card-toolbar">
                                        <button class="btn btn-sm btn-primary" id="catalogo_clasificacion_guardar" type="button"><i class="bi bi-check-lg"></i> Guardar asignaciones</button>
                                    </div>
                                </div>
                                <div class="card-body pt-3">
                                    <div class="row g-3 mb-5">
                                        <div class="col-md-5">
                                            <div class="position-relative">
                                                <i class="bi bi-search fs-3 position-absolute ms-5 mt-3"></i>
                                                <input class="form-control form-control-solid ps-12" id="catalogo_clasificacion_buscar" placeholder="Buscar producto, código o SKU">
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <select class="form-select form-select-solid" id="catalogo_clasificacion_filtro">
                                                <option value="">Todos los pendientes</option>
                                                <option value="categoria">Sin categoría principal</option>
                                                <option value="marca">Marca ambigua</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="input-group">
                                                <select class="form-select" id="catalogo_clasificacion_categoria_masiva"><option value="">Categoría principal para seleccionados</option></select>
                                                <button class="btn btn-light-primary" id="catalogo_clasificacion_aplicar_categoria" type="button"><i class="bi bi-arrow-down"></i></button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex flex-wrap gap-3 mb-5" id="catalogo_clasificacion_resumen"></div>
                                    <div class="table-responsive">
                                        <table class="table align-middle table-row-dashed gy-4 mb-0">
                                            <thead><tr class="text-muted fw-bold fs-7 text-uppercase"><th><input class="form-check-input" type="checkbox" id="catalogo_clasificacion_todos"></th><th>Producto</th><th>SKU</th><th>Pendiente</th><th>Asignación</th></tr></thead>
                                            <tbody id="catalogo_clasificacion_pendientes"></tbody>
                                        </table>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-between gap-3 pt-5">
                                        <span class="text-muted fs-7" id="catalogo_clasificacion_info"></span>
                                        <div class="d-flex gap-2">
                                            <button class="btn btn-sm btn-light" id="catalogo_clasificacion_anterior" type="button"><i class="bi bi-chevron-left"></i></button>
                                            <button class="btn btn-sm btn-light" id="catalogo_clasificacion_siguiente" type="button"><i class="bi bi-chevron-right"></i></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-3 mb-4" data-config-modulo="proveedor_costos" data-permiso-costos>
                                <span class="badge badge-light-success fs-7">Costos</span>
                                <div class="separator flex-grow-1"></div>
                            </div>
                            <div class="card mb-7" data-config-modulo="proveedor_costos" data-permiso-costos>
                                <div class="card-header border-0 pt-5">
                                    <div class="card-title">
                                        <div>
                                            <h3 class="fw-bold mb-1">Costos de proveedor provisionales</h3>
                                            <span class="text-muted fs-7">Puente temporal hasta operar Proveedores/Costos; no sustituye costo validado ni rentabilidad</span>
                                        </div>
                                    </div>
                                    <div class="card-toolbar">
                                        <button class="btn btn-sm btn-primary" id="catalogo_costos_aplicar" type="button"><i class="bi bi-arrow-repeat"></i> Sincronizar provisionales</button>
                                    </div>
                                </div>
                                <div class="card-body pt-3">
                                    <div class="d-flex flex-column flex-md-row gap-3 mb-5">
                                        <div class="position-relative flex-grow-1">
                                            <i class="bi bi-search fs-3 position-absolute ms-5 mt-3"></i>
                                            <input class="form-control form-control-solid ps-12" id="catalogo_costos_buscar" placeholder="Buscar SKU, producto o proveedor">
                                        </div>
                                        <select class="form-select form-select-solid w-md-225px" id="catalogo_costos_filtro">
                                            <option value="">Todas las propuestas</option>
                                            <option value="confiable">Coincidencia confiable</option>
                                                <option value="revision">Requiere revisión</option>
                                        </select>
                                    </div>
                                    <div class="d-flex flex-wrap gap-3 mb-5" id="catalogo_costos_resumen"></div>
                                    <div class="table-responsive">
                                        <table class="table align-middle table-row-dashed gy-4 mb-0">
                                            <thead><tr class="text-muted fw-bold fs-7 text-uppercase"><th>SKU / producto</th><th>Proveedor</th><th>Costo ref. actual</th><th>Costo lista</th><th>Precio prov.</th><th>Margen ref.</th><th>Fuentes</th></tr></thead>
                                            <tbody id="catalogo_costos_propuestas"></tbody>
                                        </table>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-between gap-3 pt-5">
                                        <span class="text-muted fs-7" id="catalogo_costos_paginacion_info"></span>
                                        <div class="d-flex gap-2">
                                            <button class="btn btn-sm btn-light" id="catalogo_costos_anterior" type="button"><i class="bi bi-chevron-left"></i></button>
                                            <button class="btn btn-sm btn-light" id="catalogo_costos_siguiente" type="button"><i class="bi bi-chevron-right"></i></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-3 mb-4" data-config-modulo="reglas" data-permiso-editar>
                                <span class="badge badge-light-warning fs-7">Reglas operativas</span>
                                <div class="separator flex-grow-1"></div>
                            </div>
                            <div class="card mb-7" data-config-modulo="reglas" data-permiso-editar>
                                <div class="card-header border-0 pt-5">
                                    <div class="card-title">
                                        <div>
                                            <h3 class="fw-bold mb-1">Política de reorden por rotación</h3>
                                            <span class="text-muted fs-7">Valores iniciales editables mientras se acumula historial real de movimientos</span>
                                        </div>
                                    </div>
                                    <div class="card-toolbar">
                                        <button class="btn btn-sm btn-primary" id="catalogo_reorden_aplicar" type="button"><i class="bi bi-check2-square"></i> Aplicar seleccionados</button>
                                    </div>
                                </div>
                                <div class="card-body pt-3">
                                    <div class="row g-4 mb-6" id="catalogo_reorden_politica">
                                        <?php foreach (array("AAA" => array(6, 6, 12), "AA" => array(3, 3, 6), "A" => array(1, 1, 3), "PRODUCTO DE IMPULSO" => array(2, 2, 4)) as $clase => $valores): $clave = strtolower(str_replace(" ", "_", $clase)); ?>
                                        <div class="col-md-6 col-xl-3">
                                            <div class="border rounded p-4">
                                                <div class="fw-bold mb-3"><?= $clase ?></div>
                                                <div class="row g-2">
                                                    <div class="col-4"><label class="form-label fs-8">Mínimo</label><input class="form-control form-control-sm" type="number" min="0" step="1" data-reorden-politica="<?= $clave ?>_minimo" value="<?= $valores[0] ?>"></div>
                                                    <div class="col-4"><label class="form-label fs-8">Reorden</label><input class="form-control form-control-sm" type="number" min="0" step="1" data-reorden-politica="<?= $clave ?>_reorden" value="<?= $valores[1] ?>"></div>
                                                    <div class="col-4"><label class="form-label fs-8">Máximo</label><input class="form-control form-control-sm" type="number" min="0" step="1" data-reorden-politica="<?= $clave ?>_maximo" value="<?= $valores[2] ?>"></div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table align-middle table-row-dashed gy-4 mb-0">
                                            <thead><tr class="text-muted fw-bold fs-7 text-uppercase"><th><input class="form-check-input" type="checkbox" id="catalogo_reorden_todos" checked></th><th>SKU / producto</th><th>Proveedor</th><th>Rotación</th><th>Existencia proveedor</th><th>Actual</th><th>Propuesto</th></tr></thead>
                                            <tbody id="catalogo_reorden_propuestas"></tbody>
                                        </table>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-between gap-3 pt-5">
                                        <span class="text-muted fs-7" id="catalogo_reorden_info"></span>
                                        <div class="d-flex gap-2">
                                            <button class="btn btn-sm btn-light" id="catalogo_reorden_anterior" type="button"><i class="bi bi-chevron-left"></i></button>
                                            <button class="btn btn-sm btn-light" id="catalogo_reorden_siguiente" type="button"><i class="bi bi-chevron-right"></i></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-3 mb-4" data-config-modulo="clasificacion_heredada">
                                <span class="badge badge-light-info fs-7">Clasificación heredada</span>
                                <div class="separator flex-grow-1"></div>
                            </div>
                            <div class="card mb-7" data-config-modulo="clasificacion_heredada">
                                <div class="card-header border-0 pt-5">
                                    <div class="card-title">
                                        <div>
                                            <h3 class="fw-bold mb-1">Clasificaciones heredadas</h3>
                                            <span class="text-muted fs-7">Insumo histórico para crear categorías maestras y asignar categoría principal cuando falte</span>
                                        </div>
                                    </div>
                                    <div class="card-toolbar">
                                        <button class="btn btn-sm btn-primary" id="catalogo_taxonomia_sincronizar" type="button" data-permiso-editar><i class="bi bi-diagram-3"></i> Preparar categorías desde clasificación</button>
                                    </div>
                                </div>
                                <div class="card-body pt-3">
                                    <div class="d-flex flex-wrap gap-3 mb-5" id="catalogo_taxonomia_resumen"></div>
                                    <div class="position-relative mb-5">
                                        <i class="bi bi-search fs-3 position-absolute ms-5 mt-3"></i>
                                        <input class="form-control form-control-solid ps-12" id="catalogo_taxonomia_buscar" placeholder="Buscar clasificación o categoría">
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table align-middle table-row-dashed gy-4 mb-0">
                                            <thead><tr class="text-muted fw-bold fs-7 text-uppercase"><th>Ruta</th><th>Tipo</th><th>Categoría maestra</th><th class="text-end">Productos</th></tr></thead>
                                            <tbody id="catalogo_taxonomia_nodos"></tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-3 mb-4" data-config-modulo="maestros">
                                <span class="badge badge-light-primary fs-7">Catálogos maestros</span>
                                <div class="separator flex-grow-1"></div>
                            </div>
                            <div class="card" data-config-modulo="maestros">
                                <div class="card-header border-0 pt-5">
                                    <ul class="nav nav-tabs nav-line-tabs fs-6" id="catalogo_aux_tabs">
                                        <li class="nav-item"><button class="nav-link active" data-tipo="marca" data-bs-toggle="tab" data-bs-target="#tab_marcas">Marcas</button></li>
                                        <li class="nav-item"><button class="nav-link" data-tipo="categoria" data-bs-toggle="tab" data-bs-target="#tab_categorias">Categorías</button></li>
                                        <li class="nav-item"><button class="nav-link" data-tipo="unidad" data-bs-toggle="tab" data-bs-target="#tab_unidades">Unidades</button></li>
                                        <li class="nav-item"><button class="nav-link" data-tipo="atributo" data-bs-toggle="tab" data-bs-target="#tab_atributos">Atributos</button></li>
                                    </ul>
                                    <div class="card-toolbar d-none" id="catalogo_marcas_acciones">
                                        <button class="btn btn-sm btn-primary" id="catalogo_marca_nueva" type="button" data-permiso-editar><i class="bi bi-plus-lg"></i> Nueva marca</button>
                                    </div>
                                    <div class="card-toolbar" id="catalogo_categorias_acciones">
                                        <button class="btn btn-sm btn-primary me-3" id="catalogo_categoria_nueva" type="button" data-permiso-editar><i class="bi bi-plus-lg"></i> Nueva categoria</button>
                                        <button class="btn btn-sm btn-light-success me-3 d-none" id="catalogo_categorias_relacionar" type="button" data-permiso-editar><i class="bi bi-arrow-left-right"></i> Aplicar relaciones históricas</button>
                                        <button class="btn btn-sm btn-light-primary" id="catalogo_categorias_preparar" type="button" data-permiso-editar><i class="bi bi-diagram-2"></i> Preparar árbol maestro</button>
                                    </div>
                                </div>
                                <div class="card-body pt-5">
                                    <div class="tab-content">
                                        <div class="tab-pane fade show active" id="tab_marcas">
                                            <div class="d-flex flex-column flex-md-row gap-3 mb-5">
                                                <div class="position-relative flex-grow-1"><i class="bi bi-search fs-3 position-absolute ms-5 mt-3"></i><input class="form-control form-control-solid ps-12" id="catalogo_marcas_buscar" placeholder="Buscar marca o código"></div>
                                                <select class="form-select form-select-solid w-md-200px" id="catalogo_marcas_imagen"><option value="">Cualquier imagen</option><option value="con_imagen">Con imagen</option><option value="sin_imagen">Sin imagen</option></select>
                                                <select class="form-select form-select-solid w-md-175px" id="catalogo_marcas_estatus"><option value="">Todo estado</option><option value="activa">Activas</option><option value="inactiva">Inactivas</option></select>
                                            </div>
                                            <div class="d-flex flex-wrap gap-3 mb-5" id="catalogo_marcas_resumen"></div>
                                            <div class="table-responsive"><table class="table align-middle table-row-dashed gy-4"><thead><tr class="text-muted fw-bold fs-7 text-uppercase"><th>Código</th><th>Marca</th><th>Descripción</th><th>Estado</th><th class="text-end">Acción</th></tr></thead><tbody id="aux_marcas"></tbody></table></div>
                                        </div>
                                        <div class="tab-pane fade" id="tab_categorias">
                                            <div class="alert alert-light-primary d-flex align-items-start gap-3">
                                                <i class="bi bi-info-circle fs-3 mt-1"></i>
                                                <div>
                                                    <div class="fw-bold mb-1">Categorías maestras del ERP</div>
                                                    <div class="text-muted fs-7">Usa categorías raíz como Acuario y subcategorías para ordenar el producto. Cada producto debe tener una categoría principal para operación/reportes y puede tener categorías secundarias para navegación o venta; las clasificaciones heredadas solo ayudan a construir el árbol.</div>
                                                </div>
                                            </div>
                                            <div class="d-flex flex-column flex-md-row gap-3 mb-5">
                                                <div class="position-relative flex-grow-1"><i class="bi bi-search fs-3 position-absolute ms-5 mt-3"></i><input class="form-control form-control-solid ps-12" id="catalogo_categorias_buscar" placeholder="Buscar categoría o ruta"></div>
                                                <select class="form-select form-select-solid w-md-225px" id="catalogo_categorias_filtro"><option value="principal" selected>Árbol principal ERP</option><option value="maestra">Todas las maestras</option><option value="ecommerce">Legado ecommerce</option><option value="">Todas</option></select>
                                                <select class="form-select form-select-solid w-md-225px" id="catalogo_categorias_uso"><option value="">Cualquier uso</option><option value="operativa">Operativas</option><option value="estructural">Estructurales</option><option value="con_productos">Con productos</option><option value="sin_productos">Sin productos</option><option value="texto_danado">Texto dañado</option></select>
                                                <select class="form-select form-select-solid w-md-200px" id="catalogo_categorias_imagen"><option value="">Cualquier imagen</option><option value="con_imagen">Con imagen</option><option value="sin_imagen">Sin imagen</option></select>
                                                <select class="form-select form-select-solid w-md-175px" id="catalogo_categorias_estatus"><option value="">Todo estado</option><option value="activa">Activas</option><option value="inactiva">Inactivas</option></select>
                                            </div>
                                            <div class="d-flex flex-wrap gap-3 mb-5" id="catalogo_categorias_resumen"></div>
                                            <div class="table-responsive"><table class="table align-middle table-row-dashed gy-4"><thead><tr class="text-muted fw-bold fs-7 text-uppercase"><th>Código</th><th>Categoría</th><th>Ruta</th><th>Uso</th><th>Estado</th><th class="text-end">Acción</th></tr></thead><tbody id="aux_categorias"></tbody></table></div>
                                        </div>
                                        <div class="tab-pane fade" id="tab_unidades"><div class="d-flex flex-wrap gap-3 mb-5" id="catalogo_unidades_resumen"></div><div class="table-responsive"><table class="table align-middle table-row-dashed gy-4"><thead><tr class="text-muted fw-bold fs-7 text-uppercase"><th>Código</th><th>Unidad</th><th>Abreviatura</th><th>Magnitud</th><th>SAT</th><th>Estado</th><th class="text-end">Acción</th></tr></thead><tbody id="aux_unidades"></tbody></table></div></div>
                                        <div class="tab-pane fade" id="tab_atributos"><div class="d-flex flex-wrap gap-3 mb-5" id="catalogo_atributos_resumen"></div><div class="table-responsive"><table class="table align-middle table-row-dashed gy-4"><thead><tr class="text-muted fw-bold fs-7 text-uppercase"><th>Código</th><th>Atributo</th><th>Tipo</th><th>Unidad</th><th>Variante</th><th>Estado</th><th class="text-end">Acción</th></tr></thead><tbody id="aux_atributos"></tbody></table></div></div>
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

<div class="modal fade" id="catalogo_imagen_maestro_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="catalogo_imagen_maestro_titulo">Imágenes</h2>
                <button type="button" class="btn btn-sm btn-icon btn-light" data-bs-dismiss="modal"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-light-warning d-none" id="catalogo_imagen_maestro_schema">
                    El esquema para imágenes de marcas y categorías está pendiente. Primero debe aplicarse el DDL autorizado antes de guardar imágenes.
                </div>
                <div class="row g-5">
                    <div class="col-lg-5">
                        <form id="catalogo_imagen_maestro_form" data-erp-ajax="true">
                            <input type="hidden" name="tipo_entidad">
                            <input type="hidden" name="id_entidad">
                            <input type="hidden" name="id_imagen">
                            <div class="mb-4">
                                <label class="form-label required">Tipo de imagen</label>
                                <select class="form-select" name="tipo_imagen" id="catalogo_imagen_maestro_tipo"></select>
                            </div>
                            <div class="mb-4">
                                <label class="form-label required">URL o ruta</label>
                                <input class="form-control" name="url_imagen" placeholder="media/... o https://..." required>
                            </div>
                            <div class="mb-4">
                                <label class="form-label">Texto alternativo</label>
                                <input class="form-control" name="texto_alternativo">
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Orden</label>
                                    <input class="form-control" name="orden" type="number" value="0">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Estado</label>
                                    <select class="form-select" name="estatus">
                                        <option value="activo">Activo</option>
                                        <option value="inactivo">Inactivo</option>
                                    </select>
                                </div>
                            </div>
                            <div class="alert alert-danger d-none mt-5" id="catalogo_imagen_maestro_error"></div>
                            <div class="d-flex justify-content-end gap-2 mt-5">
                                <button type="button" class="btn btn-light" id="catalogo_imagen_maestro_limpiar">Limpiar</button>
                                <button type="submit" class="btn btn-primary" data-permiso-editar><i class="bi bi-check-lg"></i> Guardar imagen</button>
                            </div>
                        </form>
                    </div>
                    <div class="col-lg-7">
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed gy-4">
                                <thead><tr class="text-muted fw-bold fs-7 text-uppercase"><th>Vista</th><th>Tipo</th><th>URL</th><th>Estado</th><th class="text-end">Acción</th></tr></thead>
                                <tbody id="catalogo_imagen_maestro_lista"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="catalogo_aux_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form id="catalogo_aux_form" data-erp-ajax="true">
                <div class="modal-header"><h2 id="catalogo_aux_titulo">Nuevo registro</h2><button type="button" class="btn btn-sm btn-icon btn-light" data-bs-dismiss="modal"><i class="bi bi-x-lg"></i></button></div>
                <div class="modal-body">
                    <input type="hidden" name="tipo_catalogo" value="marca"><input type="hidden" name="id">
                    <div class="row g-5">
                        <div class="col-md-4"><label class="form-label required">Código</label><input class="form-control" name="codigo" required></div>
                        <div class="col-md-8"><label class="form-label required">Nombre</label><input class="form-control" name="nombre" required></div>
                        <div class="col-12 campo campo-marca campo-categoria"><label class="form-label">Descripción</label><textarea class="form-control" name="descripcion" rows="3"></textarea></div>
                        <div class="col-md-6 campo campo-categoria"><label class="form-label">Categoría padre</label><select class="form-select" name="id_categoria_padre" id="aux_categoria_padre"><option value="">Nivel raíz</option></select></div>
                        <div class="col-md-3 campo campo-categoria"><label class="form-label">Tipo</label><select class="form-select" name="tipo_categoria"><option value="maestra">Maestra</option><option value="legado_canal">Clasificación heredada</option></select></div>
                        <div class="col-md-3 campo campo-categoria"><label class="form-label">Origen</label><select class="form-select" name="origen"><option value="erp">ERP</option><option value="manual">Manual</option><option value="ecommerce">Histórico</option></select></div>
                        <div class="col-12 campo campo-categoria">
                            <label class="form-check form-switch form-check-custom form-check-solid"><input class="form-check-input" type="checkbox" name="permite_productos" value="1"><span class="form-check-label">Permitir asignar productos directamente</span></label>
                        </div>
                        <div class="col-md-4 campo campo-unidad"><label class="form-label required">Abreviatura</label><input class="form-control" name="abreviatura"></div>
                        <div class="col-md-4 campo campo-unidad"><label class="form-label">Magnitud</label><select class="form-select" name="tipo_magnitud"><option value="unidad">Unidad</option><option value="masa">Masa</option><option value="volumen">Volumen</option><option value="longitud">Longitud</option><option value="empaque">Empaque</option><option value="servicio">Servicio</option></select></div>
                        <div class="col-md-4 campo campo-unidad"><label class="form-label">Clave SAT</label><input class="form-control" name="clave_sat"></div>
                        <div class="col-md-4 campo campo-atributo"><label class="form-label">Tipo de dato</label><select class="form-select" name="tipo_dato" id="aux_atributo_tipo"><option value="texto">Texto</option><option value="numero">Número</option><option value="booleano">Sí / No</option><option value="fecha">Fecha</option><option value="lista">Lista de opciones</option><option value="color">Color</option></select></div>
                        <div class="col-md-4 campo campo-atributo"><label class="form-label">Unidad descriptiva</label><input class="form-control" name="unidad"></div>
                        <div class="col-12 campo campo-atributo campo-atributo-lista d-none"><label class="form-label required">Opciones permitidas</label><textarea class="form-control" name="opciones_lista" rows="4" placeholder="Una opción por línea"></textarea></div>
                        <div class="col-12 d-flex gap-8 campo campo-unidad campo-atributo">
                            <label class="form-check form-switch form-check-custom form-check-solid campo campo-unidad"><input class="form-check-input" type="checkbox" name="decimales_permitidos" value="1"><span class="form-check-label">Permitir decimales</span></label>
                            <label class="form-check form-switch form-check-custom form-check-solid campo campo-atributo"><input class="form-check-input" type="checkbox" name="es_variante" value="1"><span class="form-check-label">Puede definir variantes</span></label>
                        </div>
                        <div class="col-md-4"><label class="form-label">Estado</label><select class="form-select" name="estatus" id="aux_estatus"></select></div>
                    </div>
                    <div class="alert alert-danger d-none mt-6" id="catalogo_aux_error"></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Guardar</button></div>
            </form>
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
<script src="/assets/js/custom/apps/erp/catalogo/configuracion.js?v=20260712-maestros-1"></script>
</body>
</html>
