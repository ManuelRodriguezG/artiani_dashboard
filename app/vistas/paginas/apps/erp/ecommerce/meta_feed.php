<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../../">
    <title>Meta Commerce - Feed de productos</title>
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
                    <div class="app-toolbar py-3 py-lg-5">
                        <div class="app-container container-fluid d-flex flex-stack flex-wrap gap-3">
                            <div>
                                <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Meta Commerce</h1>
                                <span class="text-muted">Previsualización y exclusiones del feed dinámico de productos.</span>
                            </div>
                            <button class="btn btn-primary" type="button" id="meta_feed_recargar"><i class="bi bi-arrow-clockwise"></i> Actualizar vista</button>
                        </div>
                    </div>
                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <div class="alert alert-info">
                                <div class="fw-bold">Fuente configurada para Meta</div>
                                <code>https://sys.artiani.com.mx/ecommercePublico/feed_meta</code>
                                <div class="fs-7 mt-2">Esta pantalla no genera archivos. Meta consulta la misma URL y recibe los cambios del catálogo en su siguiente actualización.</div>
                            </div>
                            <div class="row g-4 mb-5">
                                <div class="col-md-4"><div class="card card-flush h-100"><div class="card-body"><div class="text-muted fs-7">Publicados en Ecommerce</div><div class="fs-2hx fw-bold" id="meta_feed_publicados">0</div><div class="text-muted fs-8">No significa que todos entren a Meta.</div></div></div></div>
                                <div class="col-md-4"><div class="card card-flush h-100"><div class="card-body"><div class="text-muted fs-7">Enviados a Meta</div><div class="fs-2hx fw-bold text-primary" id="meta_feed_enviados">0</div><div class="text-muted fs-8">Cumplen datos mínimos y no están excluidos.</div></div></div></div>
                                <div class="col-md-4"><div class="card card-flush h-100"><div class="card-body"><div class="text-muted fs-7">No enviados</div><div class="fs-2hx fw-bold text-warning" id="meta_feed_no_enviados">0</div><div class="text-muted fs-8">Sin precio, marca, descripción, imagen o exclusión manual.</div></div></div></div>
                            </div>
                            <div class="card mb-5">
                                <div class="card-header border-0 pt-6"><div><div class="card-title mb-1">Previsualización del feed</div><div class="text-muted fs-7">La disponibilidad mostrada aquí corresponde al valor que se envía a Meta: <strong>in stock</strong> para productos consultables.</div></div><span class="badge badge-light-success" id="meta_feed_estado">Listo</span></div>
                                <div class="card-body pt-0">
                                    <input class="form-control form-control-solid mb-4" id="meta_feed_busqueda" type="search" placeholder="Buscar SKU, nombre o marca">
                                    <div class="table-responsive" style="max-height: 58vh; overflow:auto"><table class="table align-middle table-row-dashed fs-7"><thead><tr class="text-muted text-uppercase fw-bold"><th>SKU / ID</th><th>Producto</th><th>Marca</th><th>Disponibilidad Meta</th><th>Precio</th><th>Imagen</th></tr></thead><tbody id="meta_feed_body"></tbody></table></div>
                                </div>
                            </div>
                            <div class="card">
                                <div class="card-header border-0 pt-6"><div><div class="card-title mb-1">Exclusiones por SKU</div><div class="text-muted fs-7">Solo afectan Meta; no ocultan el producto del ecommerce público.</div></div></div>
                                <div class="card-body pt-0"><div class="row g-4 align-items-end"><div class="col-xl-8"><label class="form-label fw-semibold" for="meta_feed_skus">SKUs excluidos</label><textarea class="form-control form-control-solid" id="meta_feed_skus" rows="4" placeholder="Un SKU por línea o separados por coma"></textarea></div><div class="col-xl-4"><label class="form-label fw-semibold" for="meta_feed_motivo">Motivo</label><input class="form-control form-control-solid mb-3" id="meta_feed_motivo" type="text" value="Revision de politicas de Meta" maxlength="250"><button class="btn btn-primary w-100" type="button" id="meta_feed_guardar"><i class="bi bi-shield-x"></i> Guardar exclusiones</button></div></div><div class="text-muted fs-8 mt-3" id="meta_feed_mensaje">Sin cambios pendientes.</div></div>
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
<script src="/assets/js/custom/apps/erp/ecommerce/meta_feed.js?v=20261023"></script>
</body>
</html>
