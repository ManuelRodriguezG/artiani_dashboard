<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../../">
    <title>Catalogos comerciales</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css">
    <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css">
    <!--
      IA: Codex GPT-5 | Fecha: 2026-08-26
      Proposito: listado operativo independiente de catalogos comerciales guardados.
      Impacto: Comercial/Catalogo ERP; separa el listado del constructor visual sin cambiar esquema ni productos.
      Contrato: consume endpoints `/catalogoerp/catalogos_comerciales_listar` y `/catalogoerp/catalogos_comerciales_archivar`.
    -->
</head>
<body id="kt_app_body" data-kt-app-layout="dark-sidebar" data-kt-app-header-fixed="true" data-kt-app-sidebar-enabled="true" data-kt-app-sidebar-fixed="true" class="app-default">
<div class="d-flex flex-column flex-root app-root" id="kt_app_root">
    <div class="app-page flex-column flex-column-fluid" id="kt_app_page">
        <?= include_once '../app/vistas/includes/header/header.php'; ?>
        <div class="app-wrapper flex-column flex-row-fluid" id="kt_app_wrapper">
            <?= include_once '../app/vistas/includes/header/sidebar.php'; ?>
            <main class="app-main flex-column flex-row-fluid" id="kt_app_main">
                <div class="d-flex flex-column flex-column-fluid">
                    <div class="app-toolbar py-3 py-lg-5">
                        <div class="app-container container-fluid d-flex flex-stack flex-wrap gap-3">
                            <div>
                                <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Catalogos comerciales</h1>
                                <span class="text-muted">Listado de materiales comerciales guardados</span>
                            </div>
                            <div class="d-flex gap-2">
                                <a class="btn btn-primary" href="/catalogoerp/catalogos_comerciales_nuevo"><i class="bi bi-plus-lg"></i> Nuevo catalogo</a>
                                <button class="btn btn-light-primary" type="button" id="cc_listado_recargar"><i class="bi bi-arrow-clockwise"></i> Recargar</button>
                            </div>
                        </div>
                    </div>
                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <div class="row g-3 mb-6">
                                <div class="col-lg-3 col-md-6">
                                    <div class="border rounded bg-white p-4 h-100">
                                        <div class="fs-2 fw-bold text-gray-900" id="cc_listado_total">0</div>
                                        <div class="text-muted fs-8 text-uppercase fw-bold">Catalogos</div>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <div class="border rounded bg-white p-4 h-100">
                                        <div class="fs-2 fw-bold text-gray-900" id="cc_listado_items">0</div>
                                        <div class="text-muted fs-8 text-uppercase fw-bold">Items activos</div>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <div class="border rounded bg-white p-4 h-100">
                                        <div class="fs-2 fw-bold text-gray-900" id="cc_listado_borradores">0</div>
                                        <div class="text-muted fs-8 text-uppercase fw-bold">Borradores</div>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <div class="border rounded bg-white p-4 h-100">
                                        <span class="badge badge-light-primary" id="cc_listado_estado">Listo</span>
                                        <div class="text-muted fs-8 text-uppercase fw-bold mt-3">Estado de consulta</div>
                                    </div>
                                </div>
                            </div>
                            <div class="border rounded bg-white p-4">
                                <div class="d-flex justify-content-between align-items-end gap-3 flex-wrap mb-4">
                                    <div>
                                        <label class="form-label text-muted fs-8" for="cc_listado_buscar">Busqueda</label>
                                        <div class="position-relative w-300px">
                                            <i class="bi bi-search position-absolute ms-4 mt-3"></i>
                                            <input class="form-control form-control-solid ps-11" id="cc_listado_buscar" placeholder="Nombre, codigo o titulo">
                                        </div>
                                    </div>
                                    <span class="text-muted fs-8">La lista no modifica productos, SKUs, precios ni inventario.</span>
                                </div>
                                <div class="table-responsive">
                                    <table class="table align-middle table-row-dashed gy-4">
                                        <thead>
                                            <tr class="text-muted fw-bold fs-7 text-uppercase">
                                                <th>Catalogo</th>
                                                <th>Titulo</th>
                                                <th>Plantilla</th>
                                                <th class="text-end">Items</th>
                                                <th>Estado</th>
                                                <th>Actualizado</th>
                                                <th class="text-end">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody id="cc_listado_body"></tbody>
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
<script src="/assets/js/custom/apps/erp/catalogo/catalogos_comerciales_listado.js?v=20260826-listado-tabla-1"></script>
</body>
</html>
