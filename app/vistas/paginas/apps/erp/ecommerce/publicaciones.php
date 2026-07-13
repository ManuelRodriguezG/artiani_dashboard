<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../../">
    <title>Ecommerce publico - Publicaciones</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css">
    <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css">
    <!--
      Documentacion IA: Codex GPT-5, 2026-07-11.
      Proposito: consola interna read-only para preparar publicaciones del catalogo vivo ecommerce.
      Impacto: consume auditorias de Catalogo ERP/Ecommerce sin crear publicaciones ni ejecutar DDL.
      Contrato: no muestra stock exacto como criterio comercial publico; solo ayuda a decidir preparacion interna.
    -->
    <style>
        .ecom-kpi { border: 1px solid #e7e9ef; border-radius: 8px; background: #fff; padding: 16px; min-height: 112px; }
        .ecom-kpi__value { font-size: 2rem; line-height: 1; font-weight: 800; color: #181c32; letter-spacing: 0; }
        .ecom-kpi__label { color: #7e8299; font-size: .82rem; text-transform: uppercase; font-weight: 700; }
        .ecom-product-img { width: 56px; height: 56px; border-radius: 8px; object-fit: cover; background: #f1f3f6; border: 1px solid #e7e9ef; }
        .ecom-block-list { display: flex; flex-wrap: wrap; gap: 6px; }
        .ecom-readiness { border: 1px solid #e7e9ef; border-radius: 8px; background: #fff; }
        .ecom-readiness__signal { width: 12px; height: 12px; border-radius: 50%; display: inline-block; }
        .ecom-readiness__signal--verde { background: #50cd89; }
        .ecom-readiness__signal--amarillo { background: #ffc700; }
        .ecom-readiness__signal--rojo { background: #f1416c; }
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
                                <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Ecommerce publico</h1>
                                <span class="text-muted">Preparacion read-only del catalogo vivo conectado al ERP</span>
                            </div>
                            <div class="d-flex gap-2">
                                <a class="btn btn-light-primary" href="/catalogoerp"><i class="bi bi-box-seam"></i> Catalogo ERP</a>
                                <button class="btn btn-primary" type="button" id="ecom_recargar"><i class="bi bi-arrow-clockwise"></i> Recargar</button>
                            </div>
                        </div>
                    </div>
                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <div class="alert alert-info d-flex align-items-start gap-3">
                                <i class="bi bi-info-circle fs-2"></i>
                                <div>
                                    <div class="fw-bold">Fase 1: catalogo vivo, no checkout</div>
                                    <div>Esta pantalla no publica productos, no ejecuta DDL, no registra cotizaciones y no descuenta inventario.</div>
                                </div>
                            </div>

                            <div class="ecom-readiness p-5 mb-5" id="ecom_readiness">
                                <div class="d-flex flex-wrap align-items-start justify-content-between gap-4">
                                    <div>
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <span class="ecom-readiness__signal ecom-readiness__signal--amarillo" id="ecom_readiness_signal"></span>
                                            <h2 class="fs-5 fw-bold mb-0" id="ecom_readiness_titulo">Validando readiness del frontend</h2>
                                        </div>
                                        <div class="text-muted fs-7" id="ecom_readiness_subtitulo">Revisando contratos, DDL, CORS, WhatsApp y publicaciones.</div>
                                    </div>
                                    <div class="text-lg-end">
                                        <div class="text-muted fs-8 text-uppercase fw-bold">Base API frontend</div>
                                        <code class="fs-7" id="ecom_readiness_base">http://panel.com.local/ecommercePublico</code>
                                    </div>
                                </div>
                                <div class="separator my-4"></div>
                                <div class="row g-4">
                                    <div class="col-lg-4">
                                        <div class="text-muted fs-8 text-uppercase fw-bold mb-2">Estado</div>
                                        <div id="ecom_readiness_estados" class="d-flex flex-wrap gap-2"></div>
                                    </div>
                                    <div class="col-lg-4">
                                        <div class="text-muted fs-8 text-uppercase fw-bold mb-2">Bloqueos para datos reales</div>
                                        <div id="ecom_readiness_bloqueos" class="ecom-block-list"></div>
                                    </div>
                                    <div class="col-lg-4">
                                        <div class="text-muted fs-8 text-uppercase fw-bold mb-2">Que sigue</div>
                                        <div id="ecom_readiness_siguientes" class="fs-7 text-gray-700"></div>
                                    </div>
                                </div>
                                <div class="separator my-4"></div>
                                <div class="row g-4">
                                    <div class="col-lg-6">
                                        <div class="text-muted fs-8 text-uppercase fw-bold mb-2">Comandos read-only</div>
                                        <div id="ecom_readiness_comandos_readonly" class="fs-8"></div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="text-muted fs-8 text-uppercase fw-bold mb-2">Apply autorizado</div>
                                        <div class="alert alert-warning py-2 px-3 fs-8 mb-3">No ejecutar sin respaldo externo y autorizacion explicita.</div>
                                        <div id="ecom_readiness_comandos_apply" class="fs-8"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-4 mb-5">
                                <div class="col-md-3"><div class="ecom-kpi"><div class="ecom-kpi__label">Publicables Fase 1</div><div class="ecom-kpi__value" id="ecom_kpi_publicables">0</div><div class="text-muted fs-7 mt-2">SKUs con precio, imagen y categoria, sin granel.</div></div></div>
                                <div class="col-md-3"><div class="ecom-kpi"><div class="ecom-kpi__label">Con imagen</div><div class="ecom-kpi__value" id="ecom_kpi_imagen">0</div><div class="text-muted fs-7 mt-2">Listos visualmente para vitrina.</div></div></div>
                                <div class="col-md-3"><div class="ecom-kpi"><div class="ecom-kpi__label">Con categoria</div><div class="ecom-kpi__value" id="ecom_kpi_categoria">0</div><div class="text-muted fs-7 mt-2">Permiten filtros y navegacion.</div></div></div>
                                <div class="col-md-3"><div class="ecom-kpi"><div class="ecom-kpi__label">DDL pendiente</div><div class="ecom-kpi__value" id="ecom_kpi_ddl">0</div><div class="text-muted fs-7 mt-2">Tablas faltantes para publicaciones/cotizaciones.</div></div></div>
                            </div>

                            <div class="card mb-5">
                                <div class="card-header border-0 pt-6">
                                    <div class="card-title gap-3 flex-wrap">
                                        <select class="form-select form-select-solid w-220px" id="ecom_filtro_modo">
                                            <option value="todos">Todos los candidatos</option>
                                            <option value="publicables">Solo publicables</option>
                                            <option value="bloqueados">Solo bloqueados</option>
                                        </select>
                                        <select class="form-select form-select-solid w-120px" id="ecom_filtro_limite">
                                            <option value="25">25</option>
                                            <option value="50" selected>50</option>
                                            <option value="100">100</option>
                                            <option value="200">200</option>
                                        </select>
                                    </div>
                                    <div class="card-toolbar">
                                        <span class="badge badge-light-primary" id="ecom_estado">Listo</span>
                                    </div>
                                </div>
                                <div class="card-body pt-0">
                                    <div class="table-responsive">
                                        <table class="table align-middle table-row-dashed fs-7 gy-4">
                                            <thead>
                                                <tr class="text-start text-muted fw-bold text-uppercase">
                                                    <th class="w-70px">Imagen</th>
                                                    <th>Producto / SKU</th>
                                                    <th>Marca</th>
                                                    <th>Categoria</th>
                                                    <th class="text-end">Precio</th>
                                                    <th>Disponibilidad publica</th>
                                                    <th>Dictamen</th>
                                                    <th class="text-end">Accion</th>
                                                </tr>
                                            </thead>
                                            <tbody id="ecom_publicaciones_body"></tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <div class="card mb-5">
                                <div class="card-header border-0 pt-6">
                                    <div class="card-title">
                                        <div>
                                            <h3 class="fw-bold mb-1">Preparacion de publicacion</h3>
                                            <span class="text-muted fs-7">Vista previa read-only de slug, datos publicos y metadata de mascota.</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body pt-0" id="ecom_preview_publicacion">
                                    <div class="text-muted py-4">Selecciona un SKU publicable para preparar su ficha ecommerce sin guardar cambios.</div>
                                </div>
                            </div>

                            <div class="card">
                                <div class="card-header border-0 pt-6">
                                    <div class="card-title">
                                        <div>
                                            <h3 class="fw-bold mb-1">Plan DDL Fase 1</h3>
                                            <span class="text-muted fs-7">SQL generado sin ejecutar para publicaciones, cotizaciones, eventos y configuracion.</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body pt-0">
                                    <div id="ecom_schema_resumen" class="mb-4"></div>
                                    <div class="table-responsive">
                                        <table class="table align-middle table-row-dashed fs-7 gy-3">
                                            <thead>
                                                <tr class="text-start text-muted fw-bold text-uppercase">
                                                    <th>DDL</th>
                                                    <th>Estado</th>
                                                    <th>Detalle</th>
                                                </tr>
                                            </thead>
                                            <tbody id="ecom_schema_body"></tbody>
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
<script src="/assets/js/custom/apps/erp/ecommerce/publicaciones.js?v=20260712-preparar1"></script>
</body>
</html>
