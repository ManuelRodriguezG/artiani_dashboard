<?php
$puedeCompras = !empty($datos["puede_compras"]);
$puedeCatalogo = !empty($datos["puede_catalogo"]);
$puedeAlmacen = !empty($datos["puede_almacen"]);
$puedeInventario = !empty($datos["puede_inventario"]);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../">
    <title>Mini inventario operativo</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet">
    <link href="assets/css/style.bundle.css" rel="stylesheet">
    <style>
        .mini-inv-img { width: 48px; height: 48px; border: 1px solid #e4e6ef; border-radius: 8px; background: #f5f8fa center/cover no-repeat; display: inline-flex; align-items: center; justify-content: center; color: #a1a5b7; flex: 0 0 auto; }
        .mini-inv-num { min-width: 7.75rem; width: 7.75rem; max-width: 100%; text-align: right; }
        .mini-inv-select { min-width: 9rem; }
        .mini-inv-note { min-width: 13rem; }
        .mini-inv-doc-select { min-width: 18rem; }
        @media (max-width: 767.98px) {
            #mini_inv_body td { white-space: nowrap; }
            .mini-inv-num { min-width: 8.75rem; width: 8.75rem; }
        }
    </style>
</head>
<body id="kt_app_body" data-kt-app-layout="dark-sidebar" data-kt-app-header-fixed="true" data-kt-app-sidebar-enabled="true" data-kt-app-sidebar-fixed="true" class="app-default">
<input type="hidden" id="mini_inv_perm_compras" value="<?= $puedeCompras ? 1 : 0 ?>">
<input type="hidden" id="mini_inv_perm_catalogo" value="<?= $puedeCatalogo ? 1 : 0 ?>">
<input type="hidden" id="mini_inv_perm_almacen" value="<?= $puedeAlmacen ? 1 : 0 ?>">
<input type="hidden" id="mini_inv_perm_inventario" value="<?= $puedeInventario ? 1 : 0 ?>">
<div class="d-flex flex-column flex-root app-root">
    <div class="app-page flex-column flex-column-fluid">
        <?= include_once '../app/vistas/includes/header/header.php'; ?>
        <div class="app-wrapper flex-column flex-row-fluid">
            <?= include_once '../app/vistas/includes/header/sidebar.php'; ?>
            <main class="app-main flex-column flex-row-fluid">
                <div class="app-toolbar py-3 py-lg-6">
                    <div class="app-container container-fluid d-flex flex-stack">
                        <div>
                            <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Mini inventario operativo</h1>
                            <span class="text-muted">Mini inventarios personalizados sin afectar inventario oficial</span>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <a class="btn btn-light" href="/operacion/mini_inventarios"><i class="bi bi-arrow-left"></i> Bandeja</a>
                            <select class="form-select form-select-solid mini-inv-doc-select" id="mini_inv_documento"></select>
                            <button class="btn btn-light-success" id="mini_inv_nuevo" type="button"><i class="bi bi-plus-circle"></i> Nuevo</button>
                            <button class="btn btn-success" id="mini_inv_guardar_doc" type="button"><i class="bi bi-save"></i> Guardar</button>
                            <button class="btn btn-light-danger" id="mini_inv_descartar_doc" type="button"><i class="bi bi-x-circle"></i> Descartar</button>
                            <button class="btn btn-light" id="mini_inv_restaurar" type="button"><i class="bi bi-arrow-clockwise"></i> Restaurar captura</button>
                            <button class="btn btn-light-warning" id="mini_inv_exportar" type="button"><i class="bi bi-download"></i> CSV</button>
                            <button class="btn btn-light-primary" id="mini_inv_copiar_compra" type="button"><i class="bi bi-cart-plus"></i> Copiar compra</button>
                            <button class="btn btn-primary" id="mini_inv_copiar_tareas" type="button"><i class="bi bi-clipboard-check"></i> Copiar tareas</button>
                            <button class="btn btn-light" id="mini_inv_imprimir" type="button"><i class="bi bi-printer"></i> Imprimir</button>
                        </div>
                    </div>
                </div>
                <div class="app-content flex-column-fluid">
                    <div class="app-container container-fluid">
                        <div class="alert alert-info mb-6">
                            Esta herramienta es puente operativo: puedes crear mini inventarios personalizados, editarlos y retomarlos en este navegador. No guarda en BD, no crea kardex, no modifica existencias y no reemplaza Inventario formal.
                        </div>
                        <div class="card mb-6">
                            <div class="card-body">
                                <div class="row g-4 mb-4">
                                    <div class="col-md-8">
                                        <label class="form-label" for="mini_inv_nombre">Nombre del mini inventario</label>
                                        <input class="form-control form-control-solid" id="mini_inv_nombre" placeholder="Ej. Resurtido Sunny Acuario, Reempaque alimentos, Conteo mostrador">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label" for="mini_inv_estado_doc">Estado operativo</label>
                                        <select class="form-select form-select-solid" id="mini_inv_estado_doc">
                                            <option value="borrador">Borrador</option>
                                            <option value="en_revision">En revision</option>
                                            <option value="listo_decision">Listo para decision</option>
                                            <option value="cerrado_manual">Cerrado manual</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row g-4 align-items-end">
                                    <div class="col-md-3">
                                        <label class="form-label" for="mini_inv_almacen">Tienda / almacen</label>
                                        <select class="form-select form-select-solid" id="mini_inv_almacen"><option value="">Todas</option></select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label" for="mini_inv_proveedor">Proveedor preferido</label>
                                        <select class="form-select form-select-solid" id="mini_inv_proveedor"><option value="">Todos</option></select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label" for="mini_inv_categoria">Categoria</label>
                                        <select class="form-select form-select-solid" id="mini_inv_categoria"><option value="">Todas</option></select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label" for="mini_inv_tipo">Enfoque</label>
                                        <select class="form-select form-select-solid" id="mini_inv_tipo">
                                            <option value="">Todos</option>
                                            <option value="reempacar">Reempacar</option>
                                            <option value="abrir_empaque">Abrir empaque</option>
                                            <option value="etiquetar">Etiquetar</option>
                                        </select>
                                    </div>
                                    <div class="col-md-9">
                                        <label class="form-label" for="mini_inv_buscar">Buscar productos para agregar</label>
                                        <div class="position-relative">
                                            <i class="bi bi-search position-absolute ms-5 mt-3 fs-3"></i>
                                            <input class="form-control form-control-solid ps-12" id="mini_inv_buscar" placeholder="SKU, producto o codigo">
                                        </div>
                                    </div>
                                    <div class="col-md-3 d-grid">
                                        <button class="btn btn-primary" id="mini_inv_consultar" type="button"><i class="bi bi-search"></i> Buscar</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card mb-6" id="mini_inv_resultados_card" style="display:none;">
                            <div class="card-header border-0 pt-5">
                                <div>
                                    <h3 class="fw-bold mb-1">Resultados para agregar</h3>
                                    <div class="text-muted fs-7" id="mini_inv_resultados_estado"></div>
                                </div>
                            </div>
                            <div class="card-body pt-3">
                                <div class="table-responsive">
                                    <table class="table align-middle table-row-dashed gy-4">
                                        <thead>
                                            <tr class="text-muted fw-bold fs-7 text-uppercase">
                                                <th>SKU venta</th>
                                                <th>Proveedor / origen</th>
                                                <th class="text-end">Min</th>
                                                <th class="text-end">Max</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody id="mini_inv_resultados_body"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-3 mb-6" id="mini_inv_resumen"></div>

                        <div class="card mb-6" id="mini_inv_plan_apertura_card" style="display:none;">
                            <div class="card-header border-0 pt-5">
                                <div>
                                    <h3 class="fw-bold mb-1">Plan de apertura de empaques</h3>
                                    <div class="text-muted fs-7">Calcula empaques origen necesarios para cubrir productos que vienen de apertura.</div>
                                </div>
                            </div>
                            <div class="card-body pt-3" id="mini_inv_plan_apertura"></div>
                        </div>

                        <div class="card">
                            <div class="card-header border-0 pt-5">
                                <div>
                                    <h3 class="fw-bold mb-1">Productos del mini inventario</h3>
                                    <div class="text-muted fs-7" id="mini_inv_estado">Crea un mini inventario y agrega productos desde la busqueda.</div>
                                </div>
                                <div class="card-toolbar d-flex gap-2">
                                    <button class="btn btn-sm btn-light" id="mini_inv_ocultar_ok" type="button">Ocultar sin accion</button>
                                    <button class="btn btn-sm btn-light-danger" id="mini_inv_eliminar_doc" type="button">Eliminar mini inventario</button>
                                    <button class="btn btn-sm btn-light-danger" id="mini_inv_limpiar" type="button">Limpiar captura local</button>
                                </div>
                            </div>
                            <div class="card-body pt-3">
                                <div class="table-responsive">
                                    <table class="table align-middle table-row-dashed gy-4">
                                        <thead>
                                            <tr class="text-muted fw-bold fs-7 text-uppercase">
                                                <th>SKU venta</th>
                                                <th>Proveedor / origen</th>
                                                <th class="text-end">Min</th>
                                                <th class="text-end">Max</th>
                                                <th class="text-end">Sistema</th>
                                                <th class="text-end">Listo venta</th>
                                                <th class="text-end">Sugerido</th>
                                                <th class="text-end">Cantidad</th>
                                                <th>Accion</th>
                                                <th>Responsable</th>
                                                <th>Nota</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody id="mini_inv_body"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</div>
<script src="assets/plugins/global/plugins.bundle.js"></script>
<script src="assets/js/scripts.bundle.js"></script>
<script src="/assets/js/custom/apps/erp/operacion/mini_inventario.js?v=20260928-4"></script>
</body>
</html>
