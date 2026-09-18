<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../../">
    <title>Ecommerce publico - Catalogo gobierno</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css">
    <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css">
    <!--
      Documentacion IA: Codex GPT-5, 2026-09-18.
      Proposito: tablero profesional para gobernar la relacion Catalogo ERP -> Ecommerce publico.
      Impacto: muestra publicaciones, alertas y SEO sin cambiar slugs, precios, inventario ni redirecciones.
      Contrato: vista protegida por catalogo.ver; consume GET read-only /ecommercePublico/catalogo_gobierno_erp.
    -->
    <style>
        .ecomgov-kpi { border: 1px solid #e7e9ef; border-radius: 8px; background: #fff; padding: 14px; min-height: 98px; }
        .ecomgov-kpi__value { font-size: 1.65rem; line-height: 1; font-weight: 800; color: #181c32; letter-spacing: 0; }
        .ecomgov-kpi__label { color: #7e8299; font-size: .78rem; text-transform: uppercase; font-weight: 700; }
        .ecomgov-img { width: 52px; height: 52px; border-radius: 8px; object-fit: cover; background: #f1f3f6; border: 1px solid #e7e9ef; }
        .ecomgov-table { max-height: 52vh; overflow: auto; border: 1px solid #eef0f5; border-radius: 8px; }
        .ecomgov-table th { position: sticky; top: 0; background: #fff; z-index: 1; }
        .ecomgov-badges { display: flex; flex-wrap: wrap; gap: 6px; }
        .ecomgov-url { max-width: 230px; display: inline-block; }
        @media (max-width: 991.98px) {
            .ecomgov-table { max-height: 44vh; }
        }
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
                    <div class="app-toolbar py-3 py-lg-5">
                        <div class="app-container container-fluid d-flex flex-stack flex-wrap gap-3">
                            <div>
                                <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Catalogo Ecommerce</h1>
                                <span class="text-muted">Gobierno de publicaciones, slugs, precio vivo y alertas desde Catalogo ERP</span>
                            </div>
                            <div class="d-flex gap-2">
                                <a class="btn btn-light-primary" href="/ecommercePublico/publicaciones"><i class="bi bi-pencil-square"></i> Publicaciones</a>
                                <a class="btn btn-light-info" href="/ecommercePublico/seo_migracion"><i class="bi bi-diagram-3"></i> SEO</a>
                                <button class="btn btn-primary" type="button" id="ecomgov_recargar"><i class="bi bi-arrow-clockwise"></i> Recargar</button>
                            </div>
                        </div>
                    </div>
                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <div class="alert alert-info d-flex align-items-start gap-3">
                                <i class="bi bi-shield-check fs-2"></i>
                                <div>
                                    <div class="fw-bold">URLs estables por regla</div>
                                    <div>El nombre ERP puede cambiar sin mover el slug publico. Los cambios de slug se gobiernan desde Ecommerce/SEO y deben conservar historial o redireccion.</div>
                                </div>
                            </div>

                            <div class="row g-4 mb-5" id="ecomgov_kpis"></div>

                            <div class="row g-5">
                                <div class="col-xl-8">
                                    <div class="card">
                                        <div class="card-header border-0 pt-6">
                                            <div class="card-title gap-3 flex-wrap">
                                                <div class="position-relative w-260px">
                                                    <i class="bi bi-search position-absolute top-50 translate-middle-y ms-4 text-muted"></i>
                                                    <input class="form-control form-control-solid ps-12" id="ecomgov_q" type="text" placeholder="Buscar SKU, producto, marca">
                                                </div>
                                                <select class="form-select form-select-solid w-190px" id="ecomgov_estatus">
                                                    <option value="">Todos los estados</option>
                                                    <option value="sin_publicacion">Sin publicacion</option>
                                                    <option value="borrador">Borrador</option>
                                                    <option value="publicado">Publicado</option>
                                                    <option value="pausado">Pausado</option>
                                                </select>
                                                <select class="form-select form-select-solid w-190px" id="ecomgov_calidad">
                                                    <option value="">Todas las alertas</option>
                                                    <option value="sin_precio">Sin precio</option>
                                                    <option value="sin_imagen">Sin imagen</option>
                                                    <option value="posible_granel">Granel/fraccionario</option>
                                                    <option value="alerta_editorial">Alerta editorial</option>
                                                    <option value="informativo_apto">Apto informativo</option>
                                                </select>
                                                <select class="form-select form-select-solid w-120px" id="ecomgov_limite">
                                                    <option value="25">25</option>
                                                    <option value="50" selected>50</option>
                                                    <option value="100">100</option>
                                                </select>
                                            </div>
                                            <div class="card-toolbar">
                                                <span class="badge badge-light-primary" id="ecomgov_estado">Listo</span>
                                            </div>
                                        </div>
                                        <div class="card-body pt-0">
                                            <div class="ecomgov-table table-responsive">
                                                <table class="table align-middle table-row-dashed fs-7 gy-4">
                                                    <thead>
                                                        <tr class="text-start text-muted fw-bold text-uppercase">
                                                            <th>Imagen</th>
                                                            <th>Producto / SKU</th>
                                                            <th>Estado publico</th>
                                                            <th>Slug / URL</th>
                                                            <th class="text-end">Precio</th>
                                                            <th>Alertas</th>
                                                            <th class="text-end">Accion</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="ecomgov_items"></tbody>
                                                </table>
                                            </div>
                                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mt-4">
                                                <div class="text-muted fs-7" id="ecomgov_paginacion">Sin datos</div>
                                                <div class="d-flex align-items-center gap-2">
                                                    <button class="btn btn-sm btn-light" type="button" id="ecomgov_anterior">Anterior</button>
                                                    <span class="badge badge-light-primary" id="ecomgov_pagina">Pagina 1</span>
                                                    <button class="btn btn-sm btn-light" type="button" id="ecomgov_siguiente">Siguiente</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xl-4">
                                    <div class="card mb-5">
                                        <div class="card-header border-0 pt-6">
                                            <div class="card-title">
                                                <div>
                                                    <h3 class="fw-bold mb-1">Alertas Ecommerce</h3>
                                                    <span class="text-muted fs-7">Bandeja read-only derivada de la muestra actual.</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="card-body pt-0" id="ecomgov_alertas"></div>
                                    </div>
                                    <div class="card">
                                        <div class="card-header border-0 pt-6">
                                            <div class="card-title">
                                                <div>
                                                    <h3 class="fw-bold mb-1">SEO y Slugs</h3>
                                                    <span class="text-muted fs-7">Resumen de URLs, sitemap y redirecciones.</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="card-body pt-0">
                                            <div id="ecomgov_seo"></div>
                                            <div class="d-flex flex-wrap gap-2 mt-4">
                                                <a class="btn btn-sm btn-light-info" href="/ecommercePublico/seo_migracion">Abrir mesa SEO</a>
                                                <a class="btn btn-sm btn-light" href="/ecommercePublico/seo_verificacion">Verificar frontend</a>
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
<script src="/assets/js/custom/apps/erp/ecommerce/catalogo_gobierno.js?v=20260918-1"></script>
</body>
</html>
