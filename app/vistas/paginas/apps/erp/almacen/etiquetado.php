<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../">
    <title>Etiquetado de almacen</title>
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
                                <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Etiquetado</h1>
                                <span class="text-muted">Impresion y pegado de etiquetas generadas por recepcion o preparacion</span>
                            </div>
                            <a class="btn btn-light-primary" href="/almacen/mostrar_recepciones"><i class="bi bi-box-arrow-in-down"></i> Recepciones</a>
                        </div>
                    </div>
                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <div class="card">
                                <div class="card-header border-0 pt-5">
                                    <div class="card-title">
                                        <div class="d-flex align-items-center position-relative my-1">
                                            <i class="bi bi-search fs-3 position-absolute ms-5"></i>
                                            <input class="form-control form-control-solid w-300px ps-12" id="almacen_etiquetado_buscar" placeholder="Buscar codigo, SKU o recepcion">
                                        </div>
                                    </div>
                                    <div class="card-toolbar d-flex gap-3">
                                        <button class="btn btn-primary" id="almacen_etiquetado_imprimir_seleccion" type="button" disabled><i class="bi bi-printer"></i> Imprimir seleccion</button>
                                        <button class="btn btn-light-success" id="almacen_etiquetado_pegar_seleccion" type="button" disabled><i class="bi bi-check2-circle"></i> Marcar pegadas</button>
                                        <select class="form-select form-select-solid w-225px" id="almacen_etiquetado_estado">
                                            <option value="">Todos los estados</option>
                                            <option value="pendiente_impresion" selected>Pendiente impresion</option>
                                            <option value="impresa">Impresa</option>
                                            <option value="pegada">Pegada</option>
                                            <option value="reimpresa">Reimpresa</option>
                                            <option value="cancelada">Cancelada</option>
                                        </select>
                                        <select class="form-select form-select-solid w-225px" id="almacen_etiquetado_almacen"></select>
                                        <button class="btn btn-light-primary" id="almacen_etiquetado_recargar" type="button"><i class="bi bi-arrow-clockwise"></i> Actualizar</button>
                                    </div>
                                </div>
                                <div class="card-body pt-0">
                                    <div class="d-flex flex-wrap gap-3 mb-5" id="almacen_etiquetado_resumen"></div>
                                    <div class="table-responsive">
                                                <table class="table align-middle table-row-dashed gy-4" style="min-width: 1100px;">
                                            <thead>
                                                <tr class="text-muted fw-bold fs-7 text-uppercase">
                                                    <th class="w-40px">
                                                        <input class="form-check-input" type="checkbox" id="almacen_etiquetado_seleccionar_todo">
                                                    </th>
                                                    <th>Codigo</th>
                                                    <th>SKU / producto</th>
                                                    <th>Almacen</th>
                                                    <th>Origen</th>
                                                    <th>Contenido</th>
                                                    <th>Estado</th>
                                                    <th class="text-end">Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody id="almacen_etiquetado_body"></tbody>
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
<script src="/assets/js/custom/apps/erp/almacen/etiquetado/etiquetado.js?v=20260625-1"></script>
</body>
</html>
