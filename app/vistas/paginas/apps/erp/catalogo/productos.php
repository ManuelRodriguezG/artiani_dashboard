<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../../">
    <title>Catálogo maestro ERP</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css">
    <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css">
    <style>
        .catalogo-config-section{border:1px solid var(--bs-gray-300);border-radius:8px;padding:1.25rem;background:var(--bs-gray-100)}
        .catalogo-config-section + .catalogo-config-section{margin-top:1.25rem}
        .catalogo-config-section__title{display:flex;align-items:center;gap:.5rem;font-size:.95rem;font-weight:700;color:var(--bs-gray-800);margin-bottom:1rem}
        .catalogo-help{width:24px;height:24px}
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
                            <div class="app-container container-fluid d-flex flex-stack">
                                <div>
                                    <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Catálogo maestro ERP</h1>
                                    <span class="text-muted">Productos y SKU administrados por el núcleo del negocio</span>
                                </div>
                                <?php if (SesionSeguridad::tienePermiso('catalogo.editar')): ?>
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#catalogo_modal_alta">
                                    <i class="bi bi-plus-lg fs-3"></i> Nuevo producto
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="app-content flex-column-fluid">
                            <div class="app-container container-fluid">
                                <div class="card">
                                    <div class="card-header border-0 pt-6">
                                        <div class="card-title gap-4 flex-wrap">
                                            <div class="d-flex align-items-center position-relative my-1">
                                                <i class="bi bi-search fs-3 position-absolute ms-5"></i>
                                                <input id="catalogo_buscar" class="form-control form-control-solid w-300px ps-12" placeholder="Buscar producto o SKU">
                                            </div>
                                            <select class="form-select form-select-solid w-200px" id="catalogo_filtro_estatus">
                                                <option value="vigentes">Vigentes</option>
                                                <option value="archivados">Archivados</option>
                                                <option value="todos">Todos</option>
                                            </select>
                                            <select class="form-select form-select-solid w-250px" id="catalogo_filtro_saneamiento">
                                                <option value="todos">Todos los pendientes</option>
                                                <option value="sin_marca">Sin marca</option>
                                                <option value="sin_categoria">Sin categoria</option>
                                                <option value="sin_proveedor">SKU sin proveedor</option>
                                                <option value="proveedor_sin_principal">Proveedor sin principal</option>
                                                <option value="multiples_proveedores">Varios proveedores</option>
                                            </select>
                                        </div>
                                        <div class="card-toolbar">
                                            <span class="badge badge-light-primary" id="catalogo_total">0 productos</span>
                                        </div>
                                    </div>
                                    <div class="card-body pt-0">
                                        <div class="d-flex flex-wrap gap-3 mb-6" id="catalogo_resumen_saneamiento"></div>
                                        <?php if (SesionSeguridad::tienePermiso('catalogo.editar')): ?>
                                        <div class="border rounded bg-light p-4 mb-6">
                                            <div class="d-flex flex-column flex-xl-row gap-3 align-items-xl-end">
                                                <div class="form-check form-check-custom form-check-solid">
                                                    <input class="form-check-input" type="checkbox" id="catalogo_seleccionar_pagina">
                                                    <label class="form-check-label" for="catalogo_seleccionar_pagina">Seleccionar pagina</label>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <label class="form-label fs-8 text-muted">Marca para seleccionados</label>
                                                    <select class="form-select form-select-sm" id="catalogo_masivo_marca"><option value="">No cambiar marca</option></select>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <label class="form-label fs-8 text-muted">Categoria principal para seleccionados</label>
                                                    <select class="form-select form-select-sm" id="catalogo_masivo_categoria"><option value="">No cambiar categoria</option></select>
                                                </div>
                                                <div style="width:210px">
                                                    <label class="form-label fs-8 text-muted">Estado maestro</label>
                                                    <select class="form-select form-select-sm" id="catalogo_masivo_estatus">
                                                        <option value="">No cambiar estado</option>
                                                        <option value="borrador">Borrador</option>
                                                        <option value="en_revision">En revision</option>
                                                        <option value="activo">Activo</option>
                                                        <option value="inactivo">Inactivo</option>
                                                        <option value="descontinuado">Descontinuado</option>
                                                    </select>
                                                </div>
                                                <?php if (SesionSeguridad::tienePermiso('catalogo.costos')): ?>
                                                <div class="flex-grow-1">
                                                    <label class="form-label fs-8 text-muted">Proveedor para SKU sin proveedor</label>
                                                    <select class="form-select form-select-sm" id="catalogo_masivo_proveedor"><option value="">No agregar proveedor</option></select>
                                                </div>
                                                <div style="width:180px">
                                                    <label class="form-label fs-8 text-muted">Unidad compra</label>
                                                    <select class="form-select form-select-sm" id="catalogo_masivo_unidad_compra"><option value="">Unidad</option></select>
                                                </div>
                                                <div style="width:130px">
                                                    <label class="form-label fs-8 text-muted">Factor</label>
                                                    <input class="form-control form-control-sm" type="number" id="catalogo_masivo_factor" min="0.000001" step="0.000001" value="1">
                                                </div>
                                                <div style="width:130px">
                                                    <label class="form-label fs-8 text-muted">Minima</label>
                                                    <input class="form-control form-control-sm" type="number" id="catalogo_masivo_minima" min="0.000001" step="0.000001" value="1">
                                                </div>
                                                <?php endif; ?>
                                                <button class="btn btn-sm btn-primary" type="button" id="catalogo_masivo_aplicar"><i class="bi bi-check2-square"></i> Aplicar a seleccionados</button>
                                            </div>
                                            <div class="form-text mt-2" id="catalogo_masivo_info">Selecciona productos visibles para aplicar marca, categoria, estado maestro o proveedor por bloque.</div>
                                            <div class="alert alert-danger d-none mt-3 mb-0" id="catalogo_masivo_error"></div>
                                        </div>
                                        <?php endif; ?>
                                        <div class="table-responsive">
                                            <table class="table align-middle table-row-dashed fs-6 gy-5">
                                                <thead>
                                                    <tr class="text-start text-muted fw-bold fs-7 text-uppercase">
                                                        <?php if (SesionSeguridad::tienePermiso('catalogo.editar')): ?><th class="w-30px"></th><?php endif; ?>
                                                        <th>Código</th>
                                                        <th class="w-70px">Imagen</th>
                                                        <th>Producto</th>
                                                        <th>Marca</th>
                                                        <th>Tipo</th>
                                                        <th>SKU</th>
                                                        <th>Estado</th>
                                                        <th class="text-end">Acciones</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="catalogo_productos"></tbody>
                                            </table>
                                        </div>
                                        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-4 pt-4">
                                            <div class="d-flex align-items-center gap-3">
                                                <span class="text-muted fs-7">Mostrar</span>
                                                <select class="form-select form-select-sm w-80px" id="catalogo_tamano_pagina">
                                                    <option value="25">25</option>
                                                    <option value="50">50</option>
                                                    <option value="100">100</option>
                                                </select>
                                            </div>
                                            <div class="d-flex align-items-center gap-3">
                                                <button class="btn btn-sm btn-light" type="button" id="catalogo_pagina_anterior"><i class="bi bi-chevron-left"></i></button>
                                                <span class="text-muted fs-7" id="catalogo_paginacion_info">Pagina 1 de 1</span>
                                                <button class="btn btn-sm btn-light" type="button" id="catalogo_pagina_siguiente"><i class="bi bi-chevron-right"></i></button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card mt-6">
                                    <div class="card-header border-0 pt-6">
                                        <div class="card-title">
                                            <div>
                                                <h3 class="fw-bold mb-1">Incidencias de calidad</h3>
                                                <span class="text-muted fs-7">Pendientes abiertos de Catalogo, Proveedores y otros modulos</span>
                                            </div>
                                        </div>
                                        <div class="card-toolbar d-flex gap-2">
                                            <span class="badge badge-light-primary" id="catalogo_incidencias_total">0 incidencias</span>
                                            <button class="btn btn-sm btn-light-primary" type="button" id="catalogo_incidencias_recargar">
                                                <i class="bi bi-arrow-clockwise"></i> Recargar
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-body pt-0">
                                        <div class="table-responsive">
                                            <table class="table align-middle table-row-dashed fs-7 gy-4">
                                                <thead>
                                                    <tr class="text-start text-muted fw-bold text-uppercase">
                                                        <th>Tipo</th>
                                                        <th>Origen</th>
                                                        <th>Referencia</th>
                                                        <th>Estado</th>
                                                        <th class="text-end">Acciones</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="catalogo_incidencias_body"></tbody>
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

    <?php if (SesionSeguridad::tienePermiso('catalogo.editar')): ?>
    <div class="modal fade" id="catalogo_modal_alta" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <form id="catalogo_form_alta" data-erp-ajax="true">
                    <div class="modal-header">
                        <div>
                            <h2 class="mb-1">Nuevo producto ERP</h2>
                            <span class="text-muted">Registra el producto maestro y su primer SKU</span>
                        </div>
                        <button type="button" class="btn btn-sm btn-icon btn-light" data-bs-dismiss="modal" title="Cerrar"><i class="bi bi-x-lg"></i></button>
                    </div>
                    <div class="modal-body">
                        <ul class="nav nav-tabs nav-line-tabs mb-7 fs-6">
                            <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#catalogo_tab_producto">Producto</a></li>
                            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#catalogo_tab_sku">SKU y venta</a></li>
                            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#catalogo_tab_inventario">Inventario</a></li>
                            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#catalogo_tab_fiscal">Fiscal</a></li>
                        </ul>
                        <div class="tab-content">
                            <div class="tab-pane fade show active" id="catalogo_tab_producto">
                                <div class="row g-5">
                                    <div class="col-md-4"><label class="form-label required">Código interno</label><input class="form-control" name="codigo_producto" maxlength="80" required></div>
                                    <div class="col-md-8"><label class="form-label required">Nombre del producto</label><input class="form-control" name="nombre_producto" maxlength="255" required></div>
                                    <div class="col-md-4"><label class="form-label">Tipo</label><select class="form-select" name="tipo_producto"><option value="producto">Producto</option><option value="insumo">Insumo</option><option value="kit">Kit</option><option value="servicio">Servicio</option></select></div>
                                    <div class="col-md-4"><label class="form-label">Marca existente</label><select class="form-select" name="id_marca_erp" id="catalogo_marca"><option value="">Sin marca</option></select></div>
                                    <div class="col-md-4"><label class="form-label">O crear marca</label><input class="form-control" name="marca_nueva" maxlength="150"></div>
                                    <div class="col-md-4"><label class="form-label">Estado maestro inicial</label><select class="form-select" name="estatus"><option value="activo">Activo - maestro vigente</option><option value="borrador">Borrador - captura inicial</option><option value="en_revision">En revision - validar datos</option><option value="inactivo">Inactivo - no operativo</option><option value="descontinuado">Descontinuado</option></select><div class="form-text">Activo no significa listo para vender.</div></div>
                                    <div class="col-md-8"><label class="form-label">Categoría principal</label><select class="form-select" name="id_categoria_erp" id="catalogo_categoria"><option value="">Sin categoría</option></select><div class="form-text">Las categorías nuevas se crean en Configuración para definir padre, uso e imagen.</div></div>
                                    <div class="col-12"><label class="form-label">Descripción</label><textarea class="form-control" name="descripcion" rows="3"></textarea></div>
                                    <div class="col-12"><label class="form-check form-switch form-check-custom form-check-solid"><input class="form-check-input" type="checkbox" name="maneja_variantes" value="1"><span class="form-check-label">Este producto tendrá variantes</span></label></div>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="catalogo_tab_sku">
                                <div class="row g-5">
                                    <div class="col-md-4"><label class="form-label required">SKU</label><input class="form-control" name="sku" maxlength="150" required></div>
                                    <div class="col-md-8"><label class="form-label">Nombre específico del SKU</label><input class="form-control" name="nombre_sku" maxlength="255"></div>
                                    <div class="col-md-2"><label class="form-label required">Unidad base</label><select class="form-select" name="id_unidad_base" id="catalogo_unidad" required></select></div>
                                    <div class="col-md-2"><label class="form-label required">Factor conversi&oacute;n</label><input class="form-control" type="number" name="factor_unidad_base" min="0.000001" step="0.000001" value="1" required><div class="form-text">Equivalencia configurable del SKU.</div></div>
                                    <div class="col-md-4">
                                        <label class="form-label">Código interno / barras</label>
                                        <div class="input-group">
                                            <input class="form-control" name="codigo_barras" maxlength="180">
                                            <button class="btn btn-light-primary" type="button" data-generar-codigo-interno title="Generar codigo interno desde SKU"><i class="bi bi-upc-scan"></i></button>
                                        </div>
                                        <div class="form-text">Uso interno escaneable. No sustituye un EAN/UPC/GTIN oficial.</div>
                                    </div>
                                    <div class="col-md-4"><label class="form-label">Tipo de inventario</label><select class="form-select" name="tipo_inventario"><option value="inventariable">Inventariable</option><option value="consumible">Consumible</option><option value="kit">Kit</option><option value="servicio">Servicio</option><option value="cargo">Cargo/Gasto</option></select></div>
                                    <div class="col-md-4"><label class="form-label">Precio provisional</label><input class="form-control" type="number" name="precio" min="0" step="0.000001" value="0"><div class="form-text">Temporal hasta operar listas de precios por canal.</div></div>
                                    <div class="col-md-4"><label class="form-label">Moneda</label><select class="form-select" name="moneda"><option value="MXN">MXN</option><option value="USD">USD</option></select></div>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="catalogo_tab_inventario">
                                <div class="row g-5">
                                    <div class="col-md-3"><label class="form-label">Stock mínimo</label><input class="form-control" type="number" name="stock_minimo" min="0" step="0.000001" value="0"></div>
                                    <div class="col-md-3"><label class="form-label">Stock máximo</label><input class="form-control" type="number" name="stock_maximo" min="0" step="0.000001"></div>
                                    <div class="col-md-3"><label class="form-label">Punto de reorden</label><input class="form-control" type="number" name="punto_reorden" min="0" step="0.000001" value="0"></div>
                                    <div class="col-md-3"><label class="form-label">Estrategia de salida</label><select class="form-select" name="estrategia_salida"><option value="FIFO">FIFO</option><option value="FEFO">FEFO</option><option value="LIFO">LIFO</option></select></div>
                                    <div class="col-md-3"><label class="form-label">Días alerta caducidad</label><input class="form-control" type="number" name="dias_alerta_caducidad" min="0" value="90"></div>
                                    <div class="col-md-3"><label class="form-label">Vida mínima al recibir</label><input class="form-control" type="number" name="dias_minimos_recepcion" min="0" value="0"></div>
                                    <div class="col-12 d-flex flex-wrap gap-8">
                                        <label class="form-check form-switch form-check-custom form-check-solid"><input class="form-check-input" type="checkbox" name="requiere_cantidad_variable_recepcion" value="1"><span class="form-check-label">Cantidad real variable en recepcion</span></label>
                                        <label class="form-check form-switch form-check-custom form-check-solid"><input class="form-check-input" type="checkbox" name="requiere_unidades_fisicas_recepcion" value="1"><span class="form-check-label">Capturar unidades fisicas recibidas</span></label>
                                    </div>
                                    <div class="col-md-3"><label class="form-label">Tolerancia recepcion %</label><input class="form-control" type="number" name="tolerancia_recepcion_porcentaje" min="0" max="100" step="0.0001"></div>
                                    <div class="col-md-9"><label class="form-label">Nota recepcion variable</label><input class="form-control" name="nota_recepcion_variable" maxlength="255" placeholder="Ej. pesar al recibir; no crear unidad costal/saco"></div>
                                    <div class="col-12 d-flex flex-wrap gap-8 pt-3">
                                        <label class="form-check form-switch form-check-custom form-check-solid"><input class="form-check-input" type="checkbox" name="requiere_lote" value="1"><span class="form-check-label">Controlar lote</span></label>
                                        <label class="form-check form-switch form-check-custom form-check-solid"><input class="form-check-input" type="checkbox" name="requiere_caducidad" value="1"><span class="form-check-label">Controlar caducidad</span></label>
                                        <label class="form-check form-switch form-check-custom form-check-solid"><input class="form-check-input" type="checkbox" name="requiere_serie" value="1"><span class="form-check-label">Controlar número de serie</span></label>
                                        <label class="form-check form-switch form-check-custom form-check-solid"><input class="form-check-input" type="checkbox" name="permite_existencia_negativa" value="1"><span class="form-check-label">Permitir existencia negativa</span></label>
                                        <label class="form-check form-switch form-check-custom form-check-solid"><input class="form-check-input" type="checkbox" name="permite_venta_sin_existencia" value="1"><span class="form-check-label">Permitir venta sin existencia</span></label>
                                    </div>
                                    <div class="col-12 d-flex flex-wrap gap-8">
                                        <label class="form-check form-switch form-check-custom form-check-solid"><input class="form-check-input" type="checkbox" name="requiere_serie_fabricante" value="1"><span class="form-check-label">Serie fabricante</span></label>
                                        <label class="form-check form-switch form-check-custom form-check-solid"><input class="form-check-input" type="checkbox" name="generar_etiqueta_interna" value="1"><span class="form-check-label">Etiqueta de trazabilidad</span></label>
                                        <label class="form-check form-switch form-check-custom form-check-solid"><input class="form-check-input" type="checkbox" name="requiere_escaneo_venta" value="1"><span class="form-check-label">Escanear venta</span></label>
                                    </div>
                                    <div class="col-12 d-flex flex-wrap gap-8">
                                        <label class="form-check form-switch form-check-custom form-check-solid"><input class="form-check-input" type="checkbox" name="permite_venta_fraccionaria" value="1"><span class="form-check-label">Venta fraccionaria</span></label>
                                        <label class="form-check form-switch form-check-custom form-check-solid"><input class="form-check-input" type="checkbox" name="permite_etiqueta_fraccionada" value="1"><span class="form-check-label">Etiqueta fraccionada</span></label>
                                    </div>
                                    <div class="col-md-3" data-granel-campo><label class="form-label">Precision decimal</label><input class="form-control" type="number" name="precision_decimal" min="0" max="6" step="1" value="0"></div>
                                    <div class="col-md-3" data-granel-campo><label class="form-label">Incremento minimo venta</label><input class="form-control" type="number" name="incremento_minimo_venta" min="0" step="0.000001" value="1"></div>
                                    <div class="col-md-3" data-granel-campo><label class="form-label">Etiqueta unidad venta</label><input class="form-control" name="unidad_venta_label" maxlength="30" placeholder="kg, l, m"></div>
                                    <div class="col-md-3"><label class="form-label">Prefijo etiqueta</label><input class="form-control" name="prefijo_etiqueta_interna" maxlength="30" placeholder="ART"></div>
                                    <div class="col-md-3"><label class="form-label">Plantilla etiqueta</label><input class="form-control" name="plantilla_etiqueta" maxlength="80" placeholder="estandar_qr"></div>
                                    <div class="col-md-3"><label class="form-label">Tipo seguridad</label><input class="form-control" name="tipo_etiqueta_seguridad" maxlength="40" placeholder="void"></div>
                                    <div class="col-md-3"><label class="form-label">Instrucciones etiqueta</label><input class="form-control" name="instrucciones_etiquetado" maxlength="500" placeholder="Pegar en zona visible"></div>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="catalogo_tab_fiscal">
                                <div class="row g-5">
                                    <div class="col-md-3"><label class="form-label">Clave producto SAT</label><input class="form-control" name="clave_producto_sat" maxlength="20"></div>
                                    <div class="col-md-3"><label class="form-label">Clave unidad SAT</label><input class="form-control" name="clave_unidad_sat" maxlength="20"></div>
                                    <div class="col-md-3">
                                        <label class="form-label">Objeto de impuesto</label>
                                        <select class="form-select" name="objeto_impuesto">
                                            <option value="">Pendiente</option>
                                            <option value="01">01 - No objeto</option>
                                            <option value="02">02 - Si objeto</option>
                                            <option value="03">03 - Si objeto sin desglose</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3"><label class="form-label">IVA %</label><input class="form-control" type="number" name="iva_porcentaje" min="0" step="0.0001"></div>
                                    <div class="col-md-3"><label class="form-label">IEPS %</label><input class="form-control" type="number" name="ieps_porcentaje" min="0" step="0.0001"></div>
                                    <div class="col-md-4 d-flex align-items-end pb-3"><label class="form-check form-switch form-check-custom form-check-solid"><input class="form-check-input" type="checkbox" name="incluye_impuestos" value="1"><span class="form-check-label">El precio incluye impuestos</span></label></div>
                                </div>
                            </div>
                        </div>
                        <div class="alert alert-danger d-none mt-6" id="catalogo_error"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary" id="catalogo_guardar"><i class="bi bi-check-lg"></i> Guardar producto</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (SesionSeguridad::tienePermiso('catalogo.editar')): ?>
    <div class="modal fade" id="catalogo_modal_sku_temporal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <form id="catalogo_form_sku_temporal" data-erp-ajax="true">
                    <div class="modal-header">
                        <div>
                            <h2 class="mb-1">Crear SKU temporal</h2>
                            <span class="text-muted">Nace como borrador para completar en Catalogo y luego hacer matching en Proveedores</span>
                        </div>
                        <button type="button" class="btn btn-sm btn-icon btn-light" data-bs-dismiss="modal" title="Cerrar"><i class="bi bi-x-lg"></i></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id_incidencia_calidad">
                        <div class="alert alert-light-warning">
                            No crea relacion proveedor-SKU, no aplica costo y no activa el producto.
                        </div>
                        <div class="row g-5">
                            <div class="col-md-4"><label class="form-label">Codigo producto</label><input class="form-control" name="codigo_producto" maxlength="80"></div>
                            <div class="col-md-8"><label class="form-label">Nombre producto</label><input class="form-control" name="nombre_producto" maxlength="255"></div>
                            <div class="col-md-4"><label class="form-label">SKU temporal</label><input class="form-control" name="sku" maxlength="150"></div>
                            <div class="col-md-8"><label class="form-label">Nombre SKU</label><input class="form-control" name="nombre_sku" maxlength="255"></div>
                            <div class="col-md-3"><label class="form-label required">Unidad base</label><select class="form-select" name="id_unidad_base" id="catalogo_temporal_unidad" required></select></div>
                            <div class="col-md-3"><label class="form-label required">Factor conversi&oacute;n</label><input class="form-control" type="number" name="factor_unidad_base" min="0.000001" step="0.000001" value="1" required></div>
                            <div class="col-md-6"><label class="form-label">Marca existente</label><select class="form-select" name="id_marca_erp" id="catalogo_temporal_marca"><option value="">Sin marca</option></select></div>
                            <div class="col-md-6"><label class="form-label">Categoria existente</label><select class="form-select" name="id_categoria_erp" id="catalogo_temporal_categoria"><option value="">Sin categoria</option></select></div>
                            <div class="col-md-6"><label class="form-label">O crear marca</label><input class="form-control" name="marca_nueva" maxlength="150"></div>
                        </div>
                        <div class="alert alert-danger d-none mt-6" id="catalogo_sku_temporal_error"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Crear borrador</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="modal fade" id="catalogo_modal_detalle" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h2 class="mb-1" id="catalogo_detalle_titulo">Producto ERP</h2>
                        <span class="text-muted" id="catalogo_detalle_codigo"></span>
                    </div>
                    <button type="button" class="btn btn-sm btn-icon btn-light" data-bs-dismiss="modal" title="Cerrar"><i class="bi bi-x-lg"></i></button>
                </div>
                <div class="modal-body">
                    <ul class="nav nav-tabs nav-line-tabs mb-7 fs-6">
                        <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#catalogo_detalle_producto">Datos maestros</a></li>
                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#catalogo_detalle_skus">SKU</a></li>
                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#catalogo_detalle_variantes">Variantes</a></li>
                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#catalogo_detalle_presentaciones">Presentaciones</a></li>
                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#catalogo_detalle_paquetes">Paquetes</a></li>
                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#catalogo_detalle_imagenes">Imagenes</a></li>
                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#catalogo_detalle_proveedores">Proveedores</a></li>
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="catalogo_detalle_producto">
                            <form id="catalogo_form_editar" data-erp-ajax="true">
                                <input type="hidden" name="id_producto_erp">
                                <div class="row g-5">
                                    <div class="col-md-4"><label class="form-label required">Código interno</label><input class="form-control" name="codigo_producto" required></div>
                                    <div class="col-md-8"><label class="form-label required">Nombre</label><input class="form-control" name="nombre_producto" required></div>
                                    <div class="col-md-3"><label class="form-label">Tipo</label><select class="form-select" name="tipo_producto"><option value="producto">Producto</option><option value="insumo">Insumo</option><option value="kit">Kit</option><option value="servicio">Servicio</option></select></div>
                                    <div class="col-md-3"><label class="form-label">Estado maestro</label><select class="form-select" name="estatus"><option value="activo">Activo - maestro vigente</option><option value="borrador">Borrador - captura inicial</option><option value="en_revision">En revision - validar datos</option><option value="inactivo">Inactivo - no operativo</option><option value="descontinuado">Descontinuado</option></select><div class="form-text">Activo no significa listo para vender.</div></div>
                                    <div class="col-md-3"><label class="form-label">Marca</label><select class="form-select" name="id_marca_erp" id="catalogo_editar_marca"><option value="">Sin marca</option></select></div>
                                    <div class="col-md-3"><label class="form-label">Categoría principal</label><select class="form-select" name="id_categoria_erp" id="catalogo_editar_categoria"><option value="">Sin categoría</option></select><div class="form-text">Default operativo para reportes y reglas.</div></div>
                                    <div class="col-md-6"><label class="form-label">Categorías secundarias</label><select class="form-select" name="categorias_secundarias[]" id="catalogo_editar_categorias_secundarias" multiple data-placeholder="Buscar categorías alternas"></select><div class="form-text">Opcionales para navegación, venta o clasificación alterna. No sustituyen la principal.</div></div>
                                    <div class="col-12"><label class="form-label">Descripción</label><textarea class="form-control" name="descripcion" rows="3"></textarea></div>
                                    <div class="col-12"><label class="form-check form-switch form-check-custom form-check-solid"><input class="form-check-input" type="checkbox" name="maneja_variantes" value="1"><span class="form-check-label">Maneja variantes</span></label></div>
                                </div>
                                <?php if (SesionSeguridad::tienePermiso('catalogo.editar')): ?>
                                <div class="text-end mt-6"><button class="btn btn-primary" type="submit"><i class="bi bi-check-lg"></i> Guardar datos maestros</button></div>
                                <?php endif; ?>
                            </form>
                        </div>
                        <div class="tab-pane fade" id="catalogo_detalle_skus">
                            <div id="catalogo_sku_objetivos_resumen" class="d-flex flex-wrap gap-2 mb-5"></div>
                            <div class="table-responsive">
                                <table class="table align-middle table-row-dashed gy-4">
                                    <thead><tr class="text-muted fw-bold fs-7 text-uppercase"><th>SKU</th><th>Nombre</th><th>Unidad</th><th>Precio</th><th>Inventario</th><th>Calidad</th><th>Estado</th><?php if (SesionSeguridad::tienePermiso('catalogo.editar')): ?><th class="text-end">Accion</th><?php endif; ?></tr></thead>
                                    <tbody id="catalogo_detalle_skus_lista"></tbody>
                                </table>
                            </div>
                            <?php if (SesionSeguridad::tienePermiso('catalogo.editar')): ?>
                            <div class="separator my-7"></div>
                            <h3 class="fs-5 mb-5" id="catalogo_sku_form_titulo">Agregar SKU</h3>
                            <div class="alert alert-light-info d-none mb-5" id="catalogo_sku_plantilla_alerta"></div>
                            <div class="alert alert-light-warning d-none mb-5" id="catalogo_sku_archivado_alerta"></div>
                            <form id="catalogo_form_sku" data-erp-ajax="true">
                                <input type="hidden" name="id_producto_erp">
                                <input type="hidden" name="id_sku">
                                <div class="row g-5">
                                    <div class="col-md-3"><label class="form-label required">SKU</label><input class="form-control" name="sku" required></div>
                                    <div class="col-md-5"><label class="form-label required">Nombre del SKU</label><input class="form-control" name="nombre_sku" maxlength="255" required></div>
                                    <div class="col-md-2"><label class="form-label required">Unidad base</label><select class="form-select" name="id_unidad_base" id="catalogo_sku_unidad" required></select></div>
                                    <div class="col-md-2"><label class="form-label required">Factor conversi&oacute;n</label><input class="form-control" type="number" name="factor_unidad_base" min="0.000001" step="0.000001" value="1" required><div class="form-text">Ej. base kg con factor 4.</div></div>
                                    <div class="col-md-2"><label class="form-label">Estado maestro</label><select class="form-select" name="estatus"><option value="activo">Activo - maestro vigente</option><option value="borrador">Borrador - captura inicial</option><option value="en_revision">En revision - validar datos</option><option value="inactivo">Inactivo - no operativo</option><option value="descontinuado">Descontinuado</option></select><div class="form-text">No equivale a venta habilitada.</div></div>
                                    <div class="col-md-3">
                                        <label class="form-label">Código interno / barras</label>
                                        <div class="input-group">
                                            <input class="form-control" name="codigo_barras">
                                            <button class="btn btn-light-primary" type="button" data-generar-codigo-interno title="Generar codigo interno desde SKU"><i class="bi bi-upc-scan"></i></button>
                                        </div>
                                        <div class="form-text">Uso interno escaneable. No sustituye un EAN/UPC/GTIN oficial.</div>
                                    </div>
                                    <div class="col-md-3"><label class="form-label">Tipo inventario</label><select class="form-select" name="tipo_inventario"><option value="inventariable">Inventariable</option><option value="consumible">Consumible</option><option value="kit">Kit</option><option value="servicio">Servicio</option><option value="cargo">Cargo/Gasto</option></select></div>
                                    <div class="col-md-2"><label class="form-label">Precio provisional</label><input class="form-control" type="number" name="precio" min="0" step="0.000001" value="0"><div class="form-text">No sustituye lista/canal.</div></div>
                                    <div class="col-md-2"><label class="form-label">Moneda</label><select class="form-select" name="moneda"><option value="MXN">MXN</option><option value="USD">USD</option></select></div>
                                    <div class="col-md-2"><label class="form-label">Stock mínimo</label><input class="form-control" type="number" name="stock_minimo" min="0" step="0.000001" value="0"></div>
                                    <div class="col-md-2"><label class="form-label">Stock máximo</label><input class="form-control" type="number" name="stock_maximo" min="0" step="0.000001"></div>
                                    <div class="col-md-2"><label class="form-label">Reorden</label><input class="form-control" type="number" name="punto_reorden" min="0" step="0.000001" value="0"></div>
                                    <div class="col-md-2"><label class="form-label">Salida</label><select class="form-select" name="estrategia_salida"><option value="FIFO">FIFO</option><option value="FEFO">FEFO</option><option value="LIFO">LIFO</option></select></div>
                                    <div class="col-md-2"><label class="form-label">IVA %</label><input class="form-control" type="number" name="iva_porcentaje" min="0" step="0.0001"></div>
                                    <div class="col-md-2"><label class="form-label">IEPS %</label><input class="form-control" type="number" name="ieps_porcentaje" min="0" step="0.0001"></div>
                                    <div class="col-md-2"><label class="form-label">Clave SAT producto</label><input class="form-control" name="clave_producto_sat" maxlength="20"></div>
                                    <div class="col-md-2"><label class="form-label">Clave SAT unidad</label><input class="form-control" name="clave_unidad_sat" maxlength="20"></div>
                                    <div class="col-md-2">
                                        <label class="form-label">Objeto impuesto</label>
                                        <select class="form-select" name="objeto_impuesto">
                                            <option value="">Pendiente</option>
                                            <option value="01">01 - No objeto</option>
                                            <option value="02">02 - Si objeto</option>
                                            <option value="03">03 - Si objeto sin desglose</option>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <div class="alert alert-light-warning d-none mb-0" id="catalogo_sku_identidad_alerta"></div>
                                    </div>
                                    <div class="col-md-6"><label class="form-label">Motivo si cambia SKU o código</label><input class="form-control" name="motivo_cambio_identidad" maxlength="255"></div>
                                    <div class="col-md-2"><label class="form-label">Dias alerta</label><input class="form-control" type="number" name="dias_alerta_caducidad" min="0" value="90"></div>
                                    <div class="col-md-2"><label class="form-label">Vida minima</label><input class="form-control" type="number" name="dias_minimos_recepcion" min="0" value="0"></div>
                                    <div class="col-12 d-flex flex-wrap gap-8">
                                        <label class="form-check form-switch form-check-custom form-check-solid"><input class="form-check-input" type="checkbox" name="requiere_cantidad_variable_recepcion" value="1"><span class="form-check-label">Cantidad real variable en recepcion</span></label>
                                        <label class="form-check form-switch form-check-custom form-check-solid"><input class="form-check-input" type="checkbox" name="requiere_unidades_fisicas_recepcion" value="1"><span class="form-check-label">Capturar unidades fisicas recibidas</span></label>
                                    </div>
                                    <div class="col-md-3"><label class="form-label">Tolerancia recepcion %</label><input class="form-control" type="number" name="tolerancia_recepcion_porcentaje" min="0" max="100" step="0.0001"></div>
                                    <div class="col-md-9"><label class="form-label">Nota recepcion variable</label><input class="form-control" name="nota_recepcion_variable" maxlength="255" placeholder="Ej. pesar al recibir; no crear unidad costal/saco"></div>
                                    <div class="col-12 d-flex flex-wrap gap-8">
                                        <label class="form-check form-switch form-check-custom form-check-solid"><input class="form-check-input" type="checkbox" name="requiere_lote" value="1"><span class="form-check-label">Lote</span></label>
                                        <label class="form-check form-switch form-check-custom form-check-solid"><input class="form-check-input" type="checkbox" name="requiere_caducidad" value="1"><span class="form-check-label">Caducidad</span></label>
                                        <label class="form-check form-switch form-check-custom form-check-solid"><input class="form-check-input" type="checkbox" name="requiere_serie" value="1"><span class="form-check-label">Serie</span></label>
                                        <label class="form-check form-switch form-check-custom form-check-solid"><input class="form-check-input" type="checkbox" name="permite_existencia_negativa" value="1"><span class="form-check-label">Existencia negativa</span></label>
                                        <label class="form-check form-switch form-check-custom form-check-solid"><input class="form-check-input" type="checkbox" name="permite_venta_sin_existencia" value="1"><span class="form-check-label">Venta sin existencia</span></label>
                                        <label class="form-check form-switch form-check-custom form-check-solid"><input class="form-check-input" type="checkbox" name="incluye_impuestos" value="1"><span class="form-check-label">Precio con impuestos</span></label>
                                    </div>
                                    <div class="col-12 d-flex flex-wrap gap-8">
                                        <label class="form-check form-switch form-check-custom form-check-solid"><input class="form-check-input" type="checkbox" name="requiere_serie_fabricante" value="1"><span class="form-check-label">Serie fabricante</span></label>
                                        <label class="form-check form-switch form-check-custom form-check-solid"><input class="form-check-input" type="checkbox" name="generar_etiqueta_interna" value="1"><span class="form-check-label">Etiqueta de trazabilidad</span></label>
                                        <label class="form-check form-switch form-check-custom form-check-solid"><input class="form-check-input" type="checkbox" name="requiere_escaneo_venta" value="1"><span class="form-check-label">Escanear venta</span></label>
                                    </div>
                                    <div class="col-12 d-flex flex-wrap gap-8">
                                        <label class="form-check form-switch form-check-custom form-check-solid"><input class="form-check-input" type="checkbox" name="permite_venta_fraccionaria" value="1"><span class="form-check-label">Venta fraccionaria</span></label>
                                        <label class="form-check form-switch form-check-custom form-check-solid"><input class="form-check-input" type="checkbox" name="permite_etiqueta_fraccionada" value="1"><span class="form-check-label">Etiqueta fraccionada</span></label>
                                    </div>
                                    <div class="col-md-3" data-granel-campo><label class="form-label">Precision decimal</label><input class="form-control" type="number" name="precision_decimal" min="0" max="6" step="1" value="0"></div>
                                    <div class="col-md-3" data-granel-campo><label class="form-label">Incremento minimo venta</label><input class="form-control" type="number" name="incremento_minimo_venta" min="0" step="0.000001" value="1"></div>
                                    <div class="col-md-3" data-granel-campo><label class="form-label">Etiqueta unidad venta</label><input class="form-control" name="unidad_venta_label" maxlength="30" placeholder="kg, l, m"></div>
                                    <div class="col-md-3"><label class="form-label">Prefijo etiqueta</label><input class="form-control" name="prefijo_etiqueta_interna" maxlength="30" placeholder="ART"></div>
                                    <div class="col-md-3"><label class="form-label">Plantilla etiqueta</label><input class="form-control" name="plantilla_etiqueta" maxlength="80" placeholder="estandar_qr"></div>
                                    <div class="col-md-3"><label class="form-label">Tipo seguridad</label><input class="form-control" name="tipo_etiqueta_seguridad" maxlength="40" placeholder="void"></div>
                                    <div class="col-md-3"><label class="form-label">Instrucciones etiqueta</label><input class="form-control" name="instrucciones_etiquetado" maxlength="500" placeholder="Pegar en zona visible"></div>
                                </div>
                                <div class="text-end mt-6">
                                    <button class="btn btn-light-primary d-none" type="button" id="catalogo_cancelar_edicion_sku">Limpiar para nuevo SKU</button>
                                    <button class="btn btn-primary" type="submit" id="catalogo_sku_guardar"><i class="bi bi-plus-lg"></i> Agregar SKU</button>
                                </div>
                            </form>
                            <?php endif; ?>
                            <div class="alert alert-danger d-none mt-6" id="catalogo_detalle_error"></div>
                        </div>
                        <div class="tab-pane fade" id="catalogo_detalle_variantes">
                            <div id="catalogo_variantes_estado" class="alert alert-light-primary mb-6"></div>
                            <div class="table-responsive">
                                <table class="table align-middle table-row-dashed gy-4">
                                    <thead id="catalogo_variantes_encabezado"></thead>
                                    <tbody id="catalogo_variantes_lista"></tbody>
                                </table>
                            </div>
                            <?php if (SesionSeguridad::tienePermiso('catalogo.editar')): ?>
                            <div class="separator my-7"></div>
                            <form id="catalogo_form_variantes" data-erp-ajax="true">
                                <input type="hidden" name="id_producto_erp">
                                <div class="row g-5 align-items-end">
                                    <div class="col-md-5">
                                        <label class="form-label">Atributo existente</label>
                                        <select class="form-select" name="id_atributo_erp" id="catalogo_variante_atributo">
                                            <option value="">Seleccionar atributo</option>
                                        </select>
                                    </div>
                                    <div class="col-md-5">
                                        <label class="form-label">O crear atributo diferenciador</label>
                                        <input class="form-control" name="nuevo_atributo" maxlength="100" placeholder="Ej. Color, Talla, Presentación">
                                    </div>
                                    <div class="col-md-2 text-end">
                                        <button class="btn btn-primary w-100" type="button" id="catalogo_preparar_variante"><i class="bi bi-layout-three-columns"></i> Preparar</button>
                                    </div>
                                </div>
                                <div id="catalogo_variante_valores" class="mt-6"></div>
                                <div class="text-end mt-6 d-none" id="catalogo_variante_guardar_contenedor">
                                    <button class="btn btn-primary" type="submit"><i class="bi bi-check-lg"></i> Guardar valores</button>
                                </div>
                            </form>
                            <?php endif; ?>
                            <div class="alert alert-danger d-none mt-6" id="catalogo_variantes_error"></div>
                        </div>
                        <div class="tab-pane fade" id="catalogo_detalle_presentaciones">
                            <div class="alert alert-light-info mb-6">
                                Relaciona un SKU vendible con el SKU base que consume. Esto solo configura Catalogo; el stock terminado se resolvera despues en Almacen/Inventario.
                            </div>
                            <div class="table-responsive">
                                <table class="table align-middle table-row-dashed gy-4">
                                    <thead><tr class="text-muted fw-bold fs-7 text-uppercase"><th>SKU base</th><th>Presentacion</th><th>Factor</th><th>Disponibilidad</th><th>Consumo</th><th>Empaque</th><th>Estado</th><?php if (SesionSeguridad::tienePermiso('catalogo.editar')): ?><th class="text-end">Accion</th><?php endif; ?></tr></thead>
                                    <tbody id="catalogo_presentaciones_lista"></tbody>
                                </table>
                            </div>
                            <?php if (SesionSeguridad::tienePermiso('catalogo.editar')): ?>
                            <div class="separator my-7"></div>
                            <h3 class="fs-5 mb-5" id="catalogo_presentacion_form_titulo">Configurar presentacion de venta</h3>
                            <form id="catalogo_form_presentacion" data-erp-ajax="true">
                                <input type="hidden" name="id_sku_presentacion_regla">
                                <div class="row g-5">
                                    <div class="col-md-3">
                                        <label class="form-label required">SKU base</label>
                                        <select class="form-select" name="id_sku_base" id="catalogo_presentacion_base" required></select>
                                        <div class="form-text">SKU que guarda la existencia real. Ejemplo: costal o producto base en KG.</div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label required">SKU presentacion</label>
                                        <select class="form-select" name="id_sku_presentacion" id="catalogo_presentacion_sku" required></select>
                                        <div class="form-text">SKU que vas a vender. Ejemplo: bolsa 25 g, bolsa 50 g o bolsa 500 g.</div>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label required">Factor salida base</label>
                                        <input class="form-control" type="number" name="factor_salida_base" min="0.000001" step="0.000001" value="1" required>
                                        <div class="form-text">Cantidad del SKU base que consume 1 presentacion. Bolsa 25 g = 0.025 si la base es KG.</div>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Modo</label>
                                        <select class="form-select" name="modo_disponibilidad"><option value="preparada">Preparada</option><option value="bajo_demanda">Bajo demanda</option><option value="mixta">Mixta</option></select>
                                        <div class="form-text">Preparada: ya existe lista. Bajo demanda: se arma al vender. Mixta: usa ambas reglas.</div>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Consume base en</label>
                                        <select class="form-select" name="consume_stock_base_en"><option value="preparacion">Preparacion</option><option value="venta">Venta</option></select>
                                        <div class="form-text">Preparacion descuenta al embolsar. Venta descuenta al vender.</div>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Capacidad diaria</label>
                                        <input class="form-control" type="number" name="capacidad_diaria" min="0.000001" step="0.000001">
                                        <div class="form-text">Limite operativo sugerido si se prepara bajo demanda. Puede quedar vacio.</div>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Merma %</label>
                                        <input class="form-control" type="number" name="merma_porcentaje" min="0" max="99.9999" step="0.0001" value="0">
                                        <div class="form-text">Perdida esperada al preparar. Si no aplica, deja 0.</div>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Estado</label>
                                        <select class="form-select" name="estatus"><option value="activa">Activa</option><option value="inactiva">Inactiva</option></select>
                                        <div class="form-text">Activa permite usar la regla; inactiva conserva historial sin usarla.</div>
                                    </div>
                                    <div class="col-md-3 d-flex align-items-end pb-3">
                                        <label class="form-check form-switch form-check-custom form-check-solid"><input class="form-check-input" type="checkbox" name="requiere_empaque" value="1" checked><span class="form-check-label">Requiere empaque/preparacion</span></label>
                                    </div>
                                </div>
                                <div class="form-text mt-2">Si la presentacion ya se compra cerrada del proveedor y no la preparas, desactiva empaque y configura su proveedor en la pestaña Proveedores.</div>
                                <div class="text-end mt-6">
                                    <button class="btn btn-light d-none" type="button" id="catalogo_cancelar_edicion_presentacion">Cancelar edicion</button>
                                    <button class="btn btn-primary" type="submit" id="catalogo_presentacion_guardar"><i class="bi bi-diagram-3"></i> Guardar presentacion</button>
                                </div>
                            </form>
                            <?php endif; ?>
                            <div class="alert alert-danger d-none mt-6" id="catalogo_presentaciones_error"></div>
                        </div>
                        <div class="tab-pane fade" id="catalogo_detalle_paquetes">
                            <div class="alert alert-light-info mb-6">
                                <div class="fw-semibold mb-2">Catalogo define la receta del paquete. Ventas debe guardar la seleccion final del cliente y Almacen/Inventario debe consumir o armar los componentes reales.</div>
                                <div class="text-muted fs-8">Componentes fijos son productos que siempre van en el paquete. Grupos configurables son conjuntos de opciones donde despues se elige una o varias alternativas.</div>
                            </div>
                            <div class="alert alert-light-primary mb-6" id="catalogo_paquetes_guia_sku">
                                <div class="fw-semibold mb-2">Un paquete vendible necesita su propio SKU.</div>
                                <div class="text-muted fs-8 mb-3">Si el mismo producto fisico participa en varios paquetes, crea un SKU por paquete comercial y usa el producto fisico como componente. Ejemplo: un SKU para terrario equipado y otro SKU para acuario equipado.</div>
                                <?php if (SesionSeguridad::tienePermiso('catalogo.editar')): ?>
                                <button class="btn btn-sm btn-light-primary" type="button" id="catalogo_paquete_crear_sku"><i class="bi bi-plus-lg"></i> Crear SKU de paquete</button>
                                <?php endif; ?>
                            </div>
                            <div id="catalogo_paquetes_estado" class="mb-6"></div>
                            <div id="catalogo_paquetes_lista"></div>
                            <?php if (SesionSeguridad::tienePermiso('catalogo.editar')): ?>
                            <div class="separator my-7 d-none" data-paquete-form></div>
                            <div class="border rounded p-5 mb-6 bg-light d-none" data-paquete-form>
                                <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-4">
                                    <div>
                                        <div class="fw-semibold">Crear SKU paquete aqui mismo</div>
                                        <div class="text-muted fs-8">Usalo cuando este producto fisico sera la base de otro paquete vendible. Se crea el SKU paquete y se agrega el SKU fisico como componente fijo inicial.</div>
                                    </div>
                                </div>
                                <form id="catalogo_form_paquete_sku_inline" data-erp-ajax="true">
                                    <input type="hidden" name="id_producto_erp">
                                    <div class="row g-4 align-items-end">
                                        <div class="col-md-3"><label class="form-label required">Nuevo SKU paquete</label><input class="form-control" name="sku" maxlength="150" required placeholder="PER-05-ACUARIO-KIT"></div>
                                        <div class="col-md-4"><label class="form-label required">Nombre del paquete</label><input class="form-control" name="nombre_sku" maxlength="255" required placeholder="Pecera equipada para acuario"></div>
                                        <div class="col-md-2"><label class="form-label required">Unidad paquete</label><select class="form-select" name="id_unidad_base" id="catalogo_paquete_sku_unidad_inline" required></select></div>
                                        <div class="col-md-3"><label class="form-label required">Componente fisico base</label><select class="form-select" name="id_sku_componente_base" id="catalogo_paquete_sku_componente_base" required></select></div>
                                        <div class="col-md-2"><label class="form-label">Cantidad base</label><input class="form-control" type="number" name="cantidad_componente_base" min="0.000001" step="0.000001" value="1"></div>
                                        <div class="col-md-10 text-end"><button class="btn btn-light-primary" type="submit"><i class="bi bi-plus-lg"></i> Crear SKU paquete y usar como receta</button></div>
                                    </div>
                                </form>
                            </div>
                            <form id="catalogo_form_paquete" class="d-none" data-erp-ajax="true">
                                <input type="hidden" name="id_paquete">
                                <div class="row g-5">
                                    <div class="col-md-4"><label class="form-label required">SKU paquete</label><select class="form-select" name="id_sku_paquete" id="catalogo_paquete_sku" required></select></div>
                                    <div class="col-md-2"><label class="form-label">Tipo</label><select class="form-select" name="tipo_paquete"><option value="simple">Simple</option><option value="configurable">Configurable</option><option value="prearmado">Prearmado</option><option value="virtual">Virtual</option><option value="combo">Combo</option><option value="comprado_cerrado">Comprado cerrado</option></select></div>
                                    <div class="col-md-3"><label class="form-label">Disponibilidad</label><select class="form-select" name="modo_disponibilidad"><option value="por_componentes">Por componentes</option><option value="por_existencia_armada">Por existencia armada</option><option value="mixto">Mixto</option></select></div>
                                    <div class="col-md-3"><label class="form-label">Estado</label><select class="form-select" name="estatus"><option value="activo">Activo</option><option value="borrador">Borrador</option><option value="inactivo">Inactivo</option></select></div>
                                    <div class="col-12 d-flex flex-wrap gap-8">
                                        <label class="form-check form-switch form-check-custom form-check-solid"><input class="form-check-input" type="checkbox" name="permite_configuracion_cliente" value="1"><span class="form-check-label">Permite configurar opciones</span></label>
                                        <label class="form-check form-switch form-check-custom form-check-solid"><input class="form-check-input" type="checkbox" name="requiere_armado_almacen" value="1"><span class="form-check-label">Requiere armado en almacen</span></label>
                                        <label class="form-check form-switch form-check-custom form-check-solid"><input class="form-check-input" type="checkbox" name="permite_desarmar" value="1"><span class="form-check-label">Permite desarmar</span></label>
                                    </div>
                                    <div class="col-md-8">
                                        <label class="form-label">Buscar componente</label>
                                        <div class="input-group">
                                            <input class="form-control" id="catalogo_paquete_buscar_sku" placeholder="SKU, nombre o producto">
                                            <button class="btn btn-light-primary" type="button" id="catalogo_paquete_buscar_boton"><i class="bi bi-search"></i> Buscar</button>
                                        </div>
                                        <div id="catalogo_paquete_resultados_sku" class="mt-3"></div>
                                    </div>
                                    <div class="col-md-4"><label class="form-label">Observaciones</label><textarea class="form-control" name="observaciones" rows="3"></textarea></div>
                                    <div class="col-12">
                                        <div class="text-muted fs-8 mb-2">Para eliminar un componente guardado, edita el paquete, quita el renglon y vuelve a guardar la receta.</div>
                                        <div class="table-responsive">
                                            <table class="table align-middle table-row-dashed gy-3">
                                                <thead><tr class="text-muted fw-bold fs-7 text-uppercase"><th>Componente</th><th>Cantidad</th><th>Unidad</th><th>Factor</th><th class="text-end">Accion</th></tr></thead>
                                                <tbody id="catalogo_paquete_componentes_lista"></tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-end mt-6"><button class="btn btn-primary" type="submit"><i class="bi bi-box-seam"></i> Guardar receta del SKU paquete</button></div>
                            </form>
                            <div class="separator my-7 d-none" data-paquete-form></div>
                            <div class="row g-6 d-none" data-paquete-form>
                                <div class="col-lg-6">
                                    <form id="catalogo_form_paquete_grupo" data-erp-ajax="true">
                                        <input type="hidden" name="id_grupo">
                                        <input type="hidden" name="id_paquete">
                                        <h3 class="fs-5 mb-2">Grupo configurable</h3>
                                        <div class="text-muted fs-8 mb-5">Usalo cuando el paquete permite elegir entre alternativas, por ejemplo color, tipo de filtro, decoracion o accesorio.</div>
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
                                <div class="col-lg-6">
                                    <form id="catalogo_form_paquete_opcion" data-erp-ajax="true">
                                        <input type="hidden" name="id_opcion">
                                        <input type="hidden" name="id_grupo">
                                        <h3 class="fs-5 mb-2">Opcion del grupo</h3>
                                        <div class="text-muted fs-8 mb-5">Busca globalmente el SKU que puede elegirse dentro del grupo. No tiene que pertenecer al producto actual.</div>
                                        <div class="row g-4">
                                            <div class="col-12">
                                                <label class="form-label">Buscar SKU opcion</label>
                                                <div class="input-group">
                                                    <input class="form-control" id="catalogo_paquete_opcion_buscar_sku" placeholder="SKU, nombre o producto">
                                                    <button class="btn btn-light-primary" type="button" id="catalogo_paquete_opcion_buscar_boton"><i class="bi bi-search"></i> Buscar</button>
                                                </div>
                                                <div id="catalogo_paquete_opcion_resultados_sku" class="mt-3"></div>
                                            </div>
                                            <div class="col-12"><label class="form-label required">SKU opcion seleccionado</label><select class="form-select" name="id_sku_opcion" id="catalogo_paquete_opcion_sku" required><option value="">Selecciona desde busqueda</option></select></div>
                                            <div class="col-md-4"><label class="form-label required">Cantidad</label><input class="form-control" type="number" name="cantidad_default" min="0.000001" step="0.000001" value="1" required></div>
                                            <div class="col-md-4"><label class="form-label">Minima</label><input class="form-control" type="number" name="cantidad_minima" min="0.000001" step="0.000001"></div>
                                            <div class="col-md-4"><label class="form-label">Maxima</label><input class="form-control" type="number" name="cantidad_maxima" min="0.000001" step="0.000001"></div>
                                            <div class="col-md-4"><label class="form-label">Unidad</label><select class="form-select" name="id_unidad" id="catalogo_paquete_opcion_unidad"><option value="">Base SKU</option></select></div>
                                            <div class="col-md-4"><label class="form-label">Factor</label><input class="form-control" type="number" name="factor_conversion" min="0.000001" step="0.000001" value="1"></div>
                                            <div class="col-md-4"><label class="form-label">Orden</label><input class="form-control" type="number" name="orden" value="0"></div>
                                            <div class="col-md-4"><label class="form-label">Estado</label><select class="form-select" name="estatus"><option value="activo">Activo</option><option value="borrador">Borrador</option><option value="inactivo">Inactivo</option></select></div>
                                            <div class="col-md-8 d-flex align-items-end pb-3"><label class="form-check form-switch form-check-custom form-check-solid"><input class="form-check-input" type="checkbox" name="permite_cantidad_editable" value="1"><span class="form-check-label">Cantidad editable</span></label></div>
                                        </div>
                                        <div class="text-end mt-5"><button class="btn btn-primary" type="submit"><i class="bi bi-ui-checks-grid"></i> Guardar opcion</button></div>
                                    </form>
                                </div>
                            </div>
                            <?php endif; ?>
                            <div class="alert alert-danger d-none mt-6" id="catalogo_paquetes_error"></div>
                        </div>
                        <div class="tab-pane fade" id="catalogo_detalle_imagenes">
                            <div id="catalogo_imagenes_lista" class="row g-5"></div>
                            <?php if (SesionSeguridad::tienePermiso('catalogo.editar')): ?>
                            <div class="separator my-7"></div>
                            <div class="catalogo-config-section">
                                <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4">
                                    <div>
                                        <h3 class="fs-5 mb-1">Recuperacion ecommerce</h3>
                                        <div class="text-muted fs-8">Busca imagenes migradas pendientes y las inserta en el catalogo ERP.</div>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-sm btn-light-primary" type="button" id="catalogo_imagenes_ecommerce_auditar"><i class="bi bi-search"></i> Auditar</button>
                                        <button class="btn btn-sm btn-primary d-none" type="button" id="catalogo_imagenes_ecommerce_recuperar"><i class="bi bi-cloud-download"></i> Recuperar disponibles</button>
                                    </div>
                                </div>
                                <div class="mt-4" id="catalogo_imagenes_ecommerce_resumen"></div>
                            </div>
                            <div class="separator my-7"></div>
                            <h3 class="fs-5 mb-5" id="catalogo_imagen_form_titulo">Agregar imagen</h3>
                            <div class="alert alert-light-info">
                                Alcance de imagen: producto maestro para imagen general; SKU especifico para variante, presentacion o empaque.
                            </div>
                            <form id="catalogo_form_imagen" data-erp-ajax="true">
                                <input type="hidden" name="id_producto_erp">
                                <input type="hidden" name="id_imagen_erp">
                                <div class="row g-5 align-items-end">
                                    <div class="col-md-2"><label class="form-label">Tipo</label><select class="form-select" name="tipo_imagen"><option value="portada">Portada</option><option value="galeria">Galeria</option><option value="detalle">Detalle</option><option value="empaque">Empaque</option><option value="referencia">Referencia</option></select></div>
                                    <div class="col-md-2"><label class="form-label">SKU especifico</label><select class="form-select" name="id_sku" id="catalogo_imagen_sku"><option value="">Producto maestro</option></select></div>
                                    <div class="col-md-4"><label class="form-label required">URL o ruta</label><input class="form-control" name="url_imagen" maxlength="700" required></div>
                                    <div class="col-md-2"><label class="form-label">Orden</label><input class="form-control" type="number" name="orden" value="0"></div>
                                    <div class="col-md-2"><label class="form-label">Estado</label><select class="form-select" name="estatus"><option value="activo">Activo</option><option value="inactivo">Inactivo</option></select></div>
                                    <div class="col-md-8"><label class="form-label">Texto alternativo</label><input class="form-control" name="texto_alternativo" maxlength="255"></div>
                                    <div class="col-md-4">
                                        <label class="form-label">Vista previa</label>
                                        <div class="border rounded bg-light d-flex align-items-center justify-content-center overflow-hidden" style="aspect-ratio:16/9">
                                            <img id="catalogo_imagen_preview" alt="Vista previa" class="mw-100 mh-100 d-none">
                                            <span id="catalogo_imagen_preview_vacio" class="text-muted fs-8">Sin ruta</span>
                                        </div>
                                    </div>
                                    <div class="col-12 text-end">
                                        <button class="btn btn-light d-none" type="button" id="catalogo_cancelar_edicion_imagen">Cancelar edicion</button>
                                        <button class="btn btn-primary" type="submit" id="catalogo_imagen_guardar"><i class="bi bi-check-lg"></i> Guardar imagen</button>
                                    </div>
                                </div>
                            </form>
                            <?php endif; ?>
                            <div class="alert alert-danger d-none mt-6" id="catalogo_imagenes_error"></div>
                        </div>
                        <div class="tab-pane fade" id="catalogo_detalle_proveedores">
                            <?php if (SesionSeguridad::tienePermiso('catalogo.costos')): ?>
                            <div class="d-flex justify-content-end mb-4">
                                <button class="btn btn-sm btn-light-primary" type="button" id="catalogo_proveedor_unico_preferido">
                                    <i class="bi bi-check2-circle"></i> Marcar unicos como principales
                                </button>
                            </div>
                            <?php endif; ?>
                            <div class="table-responsive">
                                <table class="table align-middle table-row-dashed gy-4">
                                    <thead><tr class="text-muted fw-bold fs-7 text-uppercase"><th>SKU ERP</th><th>Proveedor</th><th>SKU proveedor</th><th>Compra</th><?php if (SesionSeguridad::tienePermiso('catalogo.costos')): ?><th>Costo</th><?php endif; ?><th>Entrega</th><th>Estado</th><?php if (SesionSeguridad::tienePermiso('catalogo.costos')): ?><th class="text-end">Accion</th><?php endif; ?></tr></thead>
                                    <tbody id="catalogo_detalle_proveedores_lista"></tbody>
                                </table>
                            </div>
                            <?php if (SesionSeguridad::tienePermiso('catalogo.costos')): ?>
                            <div class="separator my-7"></div>
                            <h3 class="fs-5 mb-5" id="catalogo_sku_proveedor_form_titulo">Vincular SKU con proveedor</h3>
                            <form id="catalogo_form_sku_proveedor" data-erp-ajax="true">
                                <input type="hidden" name="id_sku_proveedor">
                                <div class="row g-5">
                                    <div class="col-md-4"><label class="form-label required">SKU ERP</label><select class="form-select" name="id_sku" id="catalogo_proveedor_sku" required></select></div>
                                    <div class="col-md-4"><label class="form-label required">Proveedor</label><select class="form-select" name="id_proveedor" id="catalogo_proveedor_id" required></select></div>
                                    <div class="col-md-4"><label class="form-label">SKU del proveedor</label><input class="form-control" name="sku_proveedor" maxlength="150"></div>
                                    <div class="col-md-3"><label class="form-label required">Unidad de compra</label><select class="form-select" name="id_unidad_compra" id="catalogo_proveedor_unidad" required></select></div>
                                    <div class="col-md-3"><label class="form-label required">Factor compra -> inventario</label><input class="form-control" type="number" name="factor_conversion" min="0.000001" step="0.000001" value="1" required><div class="form-text" id="catalogo_proveedor_conversion_preview">Ejemplo: 1 caja = 10 pza, o 1 empaque = 4 kg.</div></div>
                                    <div class="col-md-2"><label class="form-label required">Compra mínima</label><input class="form-control" type="number" name="cantidad_minima" min="0.000001" step="0.000001" value="1" required></div>
                                    <div class="col-md-2"><label class="form-label">Último costo</label><input class="form-control" type="number" name="costo_ultimo" min="0" step="0.000001" value="0"></div>
                                    <div class="col-md-2"><label class="form-label">Días entrega</label><input class="form-control" type="number" name="dias_entrega" min="0" value="0"></div>
                                    <div class="col-md-3"><label class="form-label">Estado</label><select class="form-select" name="estatus"><option value="activo">Activo</option><option value="inactivo">Inactivo</option></select></div>
                                    <div class="col-md-4 d-flex align-items-end pb-3"><label class="form-check form-switch form-check-custom form-check-solid"><input class="form-check-input" type="checkbox" name="es_preferido" value="1"><span class="form-check-label">Proveedor preferido para este SKU</span></label></div>
                                </div>
                                <div class="text-end mt-6">
                                    <button class="btn btn-light d-none" type="button" id="catalogo_cancelar_edicion_sku_proveedor">Cancelar edicion</button>
                                    <button class="btn btn-primary" type="submit" id="catalogo_sku_proveedor_guardar"><i class="bi bi-link-45deg"></i> Guardar vinculo</button>
                                </div>
                            </form>
                            <?php endif; ?>
                            <div class="alert alert-danger d-none mt-6" id="catalogo_proveedor_error"></div>
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
    <script src="/assets/js/custom/apps/erp/catalogo/productos.js?v=20260712-categorias-1"></script>
</body>
</html>
