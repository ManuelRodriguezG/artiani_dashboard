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
      Documentacion IA: Codex GPT-5, 2026-07-30; actualizado 2026-08-11.
      Proposito: consola interna para preparar, guardar borradores y publicar productos del catalogo vivo ecommerce.
      Impacto: administra curaduria ecommerce; no modifica precios/imagenes ERP, no descuenta inventario y no usa legacy ecom_*; permite confirmar publicacion de agotados y evita recorrer listas largas para preparar.
      Contrato: POST protegidos por catalogo.editar, CSRF, token interno y auditoria explicita.
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
        .ecom-table-scroll { max-height: 42vh; overflow: auto; border: 1px solid #eef0f5; border-radius: 8px; }
        .ecom-sticky-head th { position: sticky; top: 0; background: #fff; z-index: 1; }
        .ecom-preview-sticky { position: sticky; top: 82px; z-index: 2; }
        @media (max-width: 991.98px) {
            .ecom-preview-sticky { position: static; }
            .ecom-table-scroll { max-height: 38vh; }
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
                                    <div>Esta pantalla permite guardar borradores y publicar productos revisados. No ejecuta DDL, no registra cotizaciones, no descuenta inventario y no modifica precios ni imagenes del ERP.</div>
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
                                        <div class="position-relative w-300px">
                                            <i class="bi bi-search position-absolute top-50 translate-middle-y ms-4 text-muted"></i>
                                            <input class="form-control form-control-solid ps-12" id="ecom_filtro_busqueda" type="text" placeholder="Buscar SKU, nombre, marca o categoria">
                                        </div>
                                        <select class="form-select form-select-solid w-220px" id="ecom_filtro_modo">
                                            <option value="todos">Todos los candidatos</option>
                                            <option value="publicables">Solo publicables</option>
                                            <option value="bloqueados">Solo bloqueados</option>
                                        </select>
                                        <select class="form-select form-select-solid w-180px" id="ecom_filtro_estatus">
                                            <option value="">Todos los estados</option>
                                            <option value="sin_publicacion">Sin publicacion</option>
                                            <option value="borrador">Borrador</option>
                                            <option value="publicado">Publicado</option>
                                            <option value="pausado">Pausado</option>
                                        </select>
                                        <select class="form-select form-select-solid w-120px" id="ecom_filtro_limite">
                                            <option value="25">25</option>
                                            <option value="50" selected>50</option>
                                            <option value="100">100</option>
                                            <option value="200">200</option>
                                        </select>
                                    </div>
                                    <div class="card-toolbar">
                                        <div class="d-flex flex-wrap align-items-center gap-2">
                                            <span class="badge badge-light-info" id="ecom_lote_seleccionados">0 seleccionados</span>
                                            <button class="btn btn-sm btn-light" type="button" id="ecom_lote_limpiar">Limpiar seleccion</button>
                                            <button class="btn btn-sm btn-light-primary" type="button" id="ecom_lote_borrador">Guardar borradores</button>
                                            <button class="btn btn-sm btn-success" type="button" id="ecom_lote_publicar">Publicar borradores</button>
                                            <label class="form-check form-check-custom form-check-solid fs-7">
                                                <input class="form-check-input" type="checkbox" id="ecom_lote_confirmar_agotados">
                                                <span class="form-check-label">Permitir agotados en lote</span>
                                            </label>
                                            <span class="badge badge-light-primary" id="ecom_estado">Listo</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body pt-0">
                                    <div class="border rounded p-4 mb-4 bg-light ecom-preview-sticky" id="ecom_preview_publicacion">
                                        <div class="text-muted py-2">Selecciona un SKU publicable para preparar su ficha ecommerce sin guardar cambios.</div>
                                    </div>
                                    <div class="border rounded p-4 mb-4">
                                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
                                            <div>
                                                <div class="fw-bold">Configuracion masiva</div>
                                                <div class="text-muted fs-8">Aplica visibilidad a los productos seleccionados sin tocar inventario ni catalogo ERP.</div>
                                            </div>
                                            <button class="btn btn-sm btn-light-primary" type="button" id="ecom_lote_config_aplicar">Aplicar configuracion</button>
                                        </div>
                                        <div class="row g-3 align-items-end">
                                            <div class="col-md-4 col-xl-2">
                                                <label class="form-label fs-8 fw-semibold">Precio</label>
                                                <select class="form-select form-select-solid form-select-sm" data-lote-config="mostrar_precio">
                                                    <option value="">Sin cambio</option>
                                                    <option value="1">Mostrar</option>
                                                    <option value="0">Ocultar</option>
                                                </select>
                                            </div>
                                            <div class="col-md-4 col-xl-2">
                                                <label class="form-label fs-8 fw-semibold">Disponibilidad</label>
                                                <select class="form-select form-select-solid form-select-sm" data-lote-config="mostrar_disponibilidad">
                                                    <option value="">Sin cambio</option>
                                                    <option value="1">Mostrar</option>
                                                    <option value="0">Ocultar</option>
                                                </select>
                                            </div>
                                            <div class="col-md-4 col-xl-2">
                                                <label class="form-label fs-8 fw-semibold">Cotizacion</label>
                                                <select class="form-select form-select-solid form-select-sm" data-lote-config="permite_cotizacion">
                                                    <option value="">Sin cambio</option>
                                                    <option value="1">Permitir</option>
                                                    <option value="0">No permitir</option>
                                                </select>
                                            </div>
                                            <div class="col-md-4 col-xl-2">
                                                <label class="form-label fs-8 fw-semibold">WhatsApp</label>
                                                <select class="form-select form-select-solid form-select-sm" data-lote-config="permite_whatsapp">
                                                    <option value="">Sin cambio</option>
                                                    <option value="1">Permitir</option>
                                                    <option value="0">No permitir</option>
                                                </select>
                                            </div>
                                            <div class="col-md-4 col-xl-2">
                                                <label class="form-label fs-8 fw-semibold">Destacado</label>
                                                <select class="form-select form-select-solid form-select-sm" data-lote-config="destacado">
                                                    <option value="">Sin cambio</option>
                                                    <option value="1">Destacar</option>
                                                    <option value="0">No destacar</option>
                                                </select>
                                            </div>
                                            <div class="col-md-4 col-xl-2">
                                                <label class="form-check form-check-custom form-check-solid fs-8 mb-2">
                                                    <input class="form-check-input" type="checkbox" id="ecom_lote_config_crear_borrador" checked>
                                                    <span class="form-check-label">Crear borrador si falta</span>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="table-responsive ecom-table-scroll">
                                        <table class="table align-middle table-row-dashed fs-7 gy-4">
                                            <thead class="ecom-sticky-head">
                                                <tr class="text-start text-muted fw-bold text-uppercase">
                                                    <th class="w-30px"><input class="form-check-input" type="checkbox" id="ecom_lote_check_all"></th>
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
                                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mt-4">
                                        <div class="text-muted fs-7" id="ecom_paginacion_resumen">Mostrando 0 productos</div>
                                        <div class="d-flex align-items-center gap-2">
                                            <button class="btn btn-sm btn-light" type="button" id="ecom_pagina_anterior">Anterior</button>
                                            <span class="badge badge-light-primary" id="ecom_pagina_actual">Pagina 1</span>
                                            <button class="btn btn-sm btn-light" type="button" id="ecom_pagina_siguiente">Siguiente</button>
                                        </div>
                                    </div>
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
<script src="/assets/js/custom/apps/erp/ecommerce/publicaciones.js?v=20260812-loteconfig1"></script>
</body>
</html>
