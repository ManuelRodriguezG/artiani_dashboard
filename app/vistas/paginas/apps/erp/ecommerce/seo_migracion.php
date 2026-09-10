<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../../">
    <title>Ecommerce - SEO y migracion URLs</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css">
    <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css">
    <!--
      Documentacion IA: Codex GPT-5, 2026-09-03.
      Proposito: consola interna para preparar SEO y migracion de URLs ecommerce.
      Impacto: Ecommerce publico; revisa canonical, sitemap, robots, redirecciones y DDL antes de aplicar cambios.
      Contrato: vista protegida; los POST de escritura requieren permiso/CSRF; acciones masivas conservan token operativo.
    -->
    <style>
        .ecom-seo-kpi { border: 1px solid #e7e9ef; border-radius: 8px; background: #fff; padding: 16px; min-height: 106px; }
        .ecom-seo-kpi__value { font-size: 1.7rem; line-height: 1; font-weight: 800; color: #181c32; letter-spacing: 0; }
        .ecom-seo-kpi__label { color: #7e8299; font-size: .78rem; text-transform: uppercase; font-weight: 700; }
        .ecom-seo-panel { border: 1px solid #e7e9ef; border-radius: 8px; background: #fff; }
        .ecom-seo-scroll { max-height: 410px; overflow: auto; }
        .ecom-seo-code { max-height: 260px; overflow: auto; white-space: pre-wrap; font-size: .78rem; }
        .ecom-seo-path { word-break: break-word; }
        .ecom-seo-review-table { min-width: 1120px; }
        .ecom-seo-products-table { min-width: 1320px; }
        .ecom-seo-row-muted { opacity: .55; }
        .ecom-seo-steps { display: grid; grid-template-columns: repeat(6, minmax(120px, 1fr)); gap: 10px; }
        .ecom-seo-step { border: 1px solid #dfe4ef; border-radius: 8px; padding: 12px; background: #f9fafc; min-height: 86px; }
        @media (max-width: 1199.98px) { .ecom-seo-steps { grid-template-columns: repeat(3, minmax(120px, 1fr)); } }
        @media (max-width: 767.98px) { .ecom-seo-steps { grid-template-columns: repeat(1, minmax(120px, 1fr)); } }
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
                                <h1 class="page-heading text-dark fw-bold fs-3 mb-1">SEO y migracion URLs</h1>
                                <span class="text-muted">Canonical, sitemap, robots, redirecciones y URLs antiguas del ecommerce publico</span>
                            </div>
                            <div class="d-flex gap-2">
                                <a class="btn btn-light" href="/ecommercePublico/control"><i class="bi bi-sliders"></i> Control</a>
                                <a class="btn btn-light" href="/ecommercePublico/analytics"><i class="bi bi-graph-up"></i> Analytics</a>
                                <button class="btn btn-primary" type="button" id="ecom_seo_recargar"><i class="bi bi-arrow-clockwise"></i> Recargar</button>
                            </div>
                        </div>
                    </div>
                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <div class="alert alert-primary d-flex align-items-start justify-content-between gap-4">
                                <div>
                                    <div class="fw-bold">Fuente de verdad: ERP / Ecommerce API</div>
                                    <div>El frontend publico consume estos contratos y materializa 301, canonical, robots.txt y sitemap.xml.</div>
                                </div>
                                <span class="badge badge-light-primary" id="ecom_seo_estado">Listo</span>
                            </div>

                            <div class="row g-4 mb-5">
                                <div class="col-md-3"><div class="ecom-seo-kpi"><div class="ecom-seo-kpi__label">URLs publicas</div><div class="ecom-seo-kpi__value" id="ecom_seo_kpi_urls">0</div><div class="text-muted fs-7 mt-2">Muestra indexable.</div></div></div>
                                <div class="col-md-3"><div class="ecom-seo-kpi"><div class="ecom-seo-kpi__label">Redirecciones</div><div class="ecom-seo-kpi__value" id="ecom_seo_kpi_redirecciones">0</div><div class="text-muted fs-7 mt-2">Activas aprobadas.</div></div></div>
                                <div class="col-md-3"><div class="ecom-seo-kpi"><div class="ecom-seo-kpi__label">Pendientes</div><div class="ecom-seo-kpi__value" id="ecom_seo_kpi_pendientes">0</div><div class="text-muted fs-7 mt-2">URLs viejas sin resolver.</div></div></div>
                                <div class="col-md-3"><div class="ecom-seo-kpi"><div class="ecom-seo-kpi__label">Sitemap</div><div class="ecom-seo-kpi__value" id="ecom_seo_kpi_sitemap">0</div><div class="text-muted fs-7 mt-2">Items en muestra.</div></div></div>
                            </div>

                            <div class="ecom-seo-panel p-5 mb-5">
                                <div class="row g-4 align-items-end">
                                    <div class="col-md-5">
                                        <label class="form-label">Dominio produccion</label>
                                        <input class="form-control form-control-solid" id="ecom_seo_dominio" type="text" readonly>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Limite muestra</label>
                                        <select class="form-select form-select-solid" id="ecom_seo_limite">
                                            <option value="25">25</option>
                                            <option value="50">50</option>
                                            <option value="80">80</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 text-md-end">
                                        <span class="badge badge-light" id="ecom_seo_robots_badge">Robots</span>
                                        <span class="badge badge-light" id="ecom_seo_ddl_badge">DDL</span>
                                    </div>
                                </div>
                            </div>

                            <div class="ecom-seo-panel p-5 mb-5">
                                <h3 class="fw-bold mb-4">Proceso operativo</h3>
                                <div class="ecom-seo-steps" id="ecom_seo_pasos"></div>
                            </div>

                            <div class="ecom-seo-panel p-5 mb-5">
                                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                                    <div>
                                        <h3 class="fw-bold mb-1">Productos y slugs publicos</h3>
                                        <div class="text-muted fs-7">SKU, nombre publico, slug guardado en BD y candidatos de URLs anteriores para relacionar.</div>
                                    </div>
                                    <div class="d-flex flex-wrap gap-2">
                                        <button class="btn btn-sm btn-light" type="button" id="ecom_seo_productos_recargar"><i class="bi bi-arrow-clockwise"></i> Recargar</button>
                                        <button class="btn btn-sm btn-light-primary" type="button" id="ecom_seo_producto_slug_plan"><i class="bi bi-check2-circle"></i> Validar</button>
                                        <button class="btn btn-sm btn-primary" type="button" id="ecom_seo_producto_slug_guardar"><i class="bi bi-save"></i> Guardar nombre/slug</button>
                                    </div>
                                </div>
                                <div class="row g-3 mb-4">
                                    <div class="col-lg-3">
                                        <label class="form-label">Buscar producto</label>
                                        <input class="form-control form-control-solid" id="ecom_seo_productos_q" placeholder="SKU, nombre o slug">
                                    </div>
                                    <div class="col-lg-2">
                                        <label class="form-label">Estatus</label>
                                        <select class="form-select form-select-solid" id="ecom_seo_productos_estatus">
                                            <option value="">Todos</option>
                                            <option value="publicado">Publicado</option>
                                            <option value="borrador">Borrador</option>
                                            <option value="pausado">Pausado</option>
                                        </select>
                                    </div>
                                    <div class="col-lg-2">
                                        <label class="form-label">Relacion vieja</label>
                                        <select class="form-select form-select-solid" id="ecom_seo_productos_relacion">
                                            <option value="">Todas</option>
                                            <option value="con_sugerencia">Con sugerencia</option>
                                            <option value="sin_sugerencia">Sin sugerencia</option>
                                        </select>
                                    </div>
                                    <div class="col-lg-2">
                                        <label class="form-label">Limite</label>
                                        <select class="form-select form-select-solid" id="ecom_seo_productos_limite">
                                            <option value="50">50</option>
                                            <option value="80" selected>80</option>
                                            <option value="150">150</option>
                                            <option value="250">250</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row g-4 mb-4">
                                    <div class="col-xl-7">
                                        <div class="table-responsive ecom-seo-scroll">
                                            <table class="table table-row-dashed fs-7 gy-3 mb-0 ecom-seo-products-table">
                                                <thead>
                                                <tr class="text-muted fw-bold">
                                                    <th>SKU</th>
                                                    <th>Nombre publico</th>
                                                    <th>Slug / URL nueva</th>
                                                    <th>URL anterior sugerida</th>
                                                    <th class="text-end">Opciones</th>
                                                </tr>
                                                </thead>
                                                <tbody id="ecom_seo_productos_body"></tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <div class="col-xl-5">
                                        <div class="border rounded p-4 h-100">
                                            <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                                                <div>
                                                    <div class="text-muted fs-8 text-uppercase fw-bold">Editor SEO producto</div>
                                                    <div class="fw-bold" id="ecom_seo_producto_editor_titulo">Selecciona un producto</div>
                                                </div>
                                                <span class="badge badge-light" id="ecom_seo_producto_editor_badge">Sin seleccion</span>
                                            </div>
                                            <input type="hidden" id="ecom_seo_producto_id_publicacion">
                                            <input type="hidden" id="ecom_seo_producto_id_sku">
                                            <label class="form-label">SKU</label>
                                            <input class="form-control form-control-solid mb-3" id="ecom_seo_producto_sku" readonly>
                                            <label class="form-label">Nombre publico</label>
                                            <input class="form-control form-control-solid mb-3" id="ecom_seo_producto_titulo_publico" placeholder="Nombre visible del producto">
                                            <label class="form-label">Slug publico</label>
                                            <div class="input-group mb-3">
                                                <span class="input-group-text">/producto/</span>
                                                <input class="form-control form-control-solid" id="ecom_seo_producto_slug" placeholder="slug-del-producto">
                                                <button class="btn btn-light-primary" type="button" id="ecom_seo_producto_slug_sugerir"><i class="bi bi-stars"></i></button>
                                            </div>
                                            <div class="d-flex flex-column gap-1 mb-3">
                                                <a class="fs-8 ecom-seo-path" id="ecom_seo_producto_url_local" target="_blank" rel="noopener" href="#"></a>
                                                <a class="fs-8 ecom-seo-path" id="ecom_seo_producto_url_canonical" target="_blank" rel="noopener" href="#"></a>
                                            </div>
                                            <div class="border rounded p-3 mb-3">
                                                <label class="form-label">Buscar URL anterior productiva</label>
                                                <div class="input-group mb-3">
                                                    <input class="form-control form-control-solid" id="ecom_seo_producto_url_vieja_q" placeholder="SKU, nombre viejo o path anterior">
                                                    <button class="btn btn-light-primary" type="button" id="ecom_seo_producto_url_vieja_buscar"><i class="bi bi-search"></i></button>
                                                </div>
                                                <div id="ecom_seo_producto_url_vieja_resultados"></div>
                                            </div>
                                            <div id="ecom_seo_producto_plan" class="mb-3"></div>
                                            <div class="text-muted fs-8">Cambiar el nombre no cambia el slug automaticamente; usa la estrella solo cuando quieras recalcularlo desde el nombre.</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="ecom-seo-panel p-5 mb-5">
                                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                                    <div>
                                        <h3 class="fw-bold mb-1">Revision de URLs anteriores</h3>
                                        <div class="text-muted fs-7">URLs rastreadas de artiani.com.mx comparadas contra la nueva estructura canonica y preview local.</div>
                                    </div>
                                    <div class="d-flex flex-wrap gap-2">
                                        <button class="btn btn-sm btn-light" type="button" id="ecom_seo_revision_recargar"><i class="bi bi-arrow-clockwise"></i> Recargar</button>
                                    </div>
                                </div>
                                <div class="row g-3 mb-4">
                                    <div class="col-md-3">
                                        <label class="form-label">Buscar</label>
                                        <input class="form-control form-control-solid" id="ecom_seo_revision_q" placeholder="Producto, categoria, SKU o path">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Fuente</label>
                                        <select class="form-select form-select-solid" id="ecom_seo_revision_fuente">
                                            <option value="indexadas" selected>Indexadas Google</option>
                                            <option value="relaciones">Crawl / relaciones</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Accion</label>
                                        <select class="form-select form-select-solid" id="ecom_seo_revision_accion">
                                            <option value="">Todas</option>
                                            <option value="sin_redireccion_necesaria">Sin redireccion necesaria</option>
                                            <option value="aprobar_301_candidato">Aprobar 301 candidato</option>
                                            <option value="validar_301_candidato">Validar 301 candidato</option>
                                            <option value="revisar_manual">Revisar manual</option>
                                            <option value="excluir_o_410">Excluir o 410</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Prioridad</label>
                                        <select class="form-select form-select-solid" id="ecom_seo_revision_prioridad">
                                            <option value="">Todas</option>
                                            <option value="alta">Alta</option>
                                            <option value="media">Media</option>
                                            <option value="baja">Baja</option>
                                        </select>
                                    </div>
                                    <div class="col-md-1">
                                        <label class="form-label">Limite</label>
                                        <select class="form-select form-select-solid" id="ecom_seo_revision_limite">
                                            <option value="80">80</option>
                                            <option value="120" selected>120</option>
                                            <option value="250">250</option>
                                            <option value="500">500</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row g-3 mb-4" id="ecom_seo_revision_resumen"></div>
                                <div id="ecom_seo_revision_info" class="mb-3"></div>
                                <div class="table-responsive ecom-seo-scroll">
                                    <table class="table table-row-dashed fs-7 gy-3 mb-0 ecom-seo-review-table">
                                        <thead>
                                        <tr class="text-muted fw-bold">
                                            <th>Origen anterior</th>
                                            <th>HTTP</th>
                                            <th>Tipo</th>
                                            <th>Sugerencia nueva</th>
                                            <th>Accion</th>
                                            <th class="text-end">Opciones</th>
                                        </tr>
                                        </thead>
                                        <tbody id="ecom_seo_revision_body"></tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="row g-5 mb-5">
                                <div class="col-xl-7">
                                    <div class="ecom-seo-panel p-5 h-100">
                                        <div class="d-flex justify-content-between align-items-center mb-4">
                                            <h3 class="fw-bold mb-0">Importar URLs viejas</h3>
                                            <div class="d-flex gap-2">
                                                <button class="btn btn-sm btn-light-primary" type="button" id="ecom_seo_importar_plan"><i class="bi bi-search"></i> Preparar plan</button>
                                                <button class="btn btn-sm btn-primary" type="button" id="ecom_seo_importar_guardar"><i class="bi bi-database-check"></i> Importar</button>
                                            </div>
                                        </div>
                                        <textarea class="form-control form-control-solid mb-4" rows="6" id="ecom_seo_urls_viejas" placeholder="Pega una URL por linea"></textarea>
                                        <label class="form-label">Token importacion</label>
                                        <input class="form-control form-control-solid mb-4" id="ecom_seo_token_importar" type="password" autocomplete="off">
                                        <div id="ecom_seo_importacion_plan"></div>
                                    </div>
                                </div>
                                <div class="col-xl-5">
                                    <div class="ecom-seo-panel p-5 h-100">
                                        <div class="d-flex justify-content-between align-items-center mb-4">
                                            <h3 class="fw-bold mb-0">Redireccion manual</h3>
                                            <div class="d-flex gap-2">
                                                <button class="btn btn-sm btn-light-primary" type="button" id="ecom_seo_redireccion_plan"><i class="bi bi-check2-circle"></i> Validar</button>
                                                <button class="btn btn-sm btn-primary" type="button" id="ecom_seo_redireccion_guardar"><i class="bi bi-save"></i> Guardar</button>
                                            </div>
                                        </div>
                                        <label class="form-label">Origen viejo</label>
                                        <input class="form-control form-control-solid mb-3" id="ecom_seo_redir_from" placeholder="/producto-viejo.html">
                                        <label class="form-label">Destino canonico</label>
                                        <input class="form-control form-control-solid mb-3" id="ecom_seo_redir_to" placeholder="/producto/slug-nuevo">
                                        <div class="border rounded p-3 mb-3">
                                            <div class="row g-2 align-items-end">
                                                <div class="col-5">
                                                    <label class="form-label">Buscar destino</label>
                                                    <input class="form-control form-control-solid" id="ecom_seo_destino_q" placeholder="producto o categoria">
                                                </div>
                                                <div class="col-4">
                                                    <label class="form-label">Tipo</label>
                                                    <select class="form-select form-select-solid" id="ecom_seo_destino_tipo">
                                                        <option value="producto">Producto</option>
                                                        <option value="categoria">Categoria</option>
                                                        <option value="marca">Marca</option>
                                                    </select>
                                                </div>
                                                <div class="col-3">
                                                    <button class="btn btn-light-primary w-100" type="button" id="ecom_seo_destino_buscar"><i class="bi bi-search"></i></button>
                                                </div>
                                            </div>
                                            <div class="mt-3" id="ecom_seo_destino_resultados"></div>
                                        </div>
                                        <div class="row g-3 mb-4">
                                            <div class="col-6">
                                                <label class="form-label">Status</label>
                                                <select class="form-select form-select-solid" id="ecom_seo_redir_status">
                                                    <option value="301">301</option>
                                                    <option value="302">302</option>
                                                    <option value="308">308</option>
                                                </select>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label">Tipo</label>
                                                <select class="form-select form-select-solid" id="ecom_seo_redir_tipo">
                                                    <option value="producto">Producto</option>
                                                    <option value="categoria">Categoria</option>
                                                    <option value="marca">Marca</option>
                                                    <option value="manual">Manual</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div id="ecom_seo_redireccion_resultado"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-5 mb-5">
                                <div class="col-xl-8">
                                    <div class="ecom-seo-panel p-5 h-100">
                                        <div class="d-flex justify-content-between align-items-center mb-4">
                                            <h3 class="fw-bold mb-0">URLs publicas canonicas</h3>
                                            <div class="d-flex gap-2">
                                                <button class="btn btn-sm btn-light-primary" type="button" id="ecom_seo_urls_sync_plan"><i class="bi bi-search"></i> Plan sync</button>
                                                <button class="btn btn-sm btn-primary" type="button" id="ecom_seo_urls_sync_guardar"><i class="bi bi-database-check"></i> Sincronizar</button>
                                            </div>
                                        </div>
                                        <div class="row g-3 align-items-end mb-4">
                                            <div class="col-md-7">
                                                <label class="form-label">Token sincronizacion</label>
                                                <input class="form-control form-control-solid" id="ecom_seo_token_urls_sync" type="password" autocomplete="off">
                                            </div>
                                            <div class="col-md-5 text-md-end">
                                                <span class="badge badge-light-success">Sin /ecommercePublico</span>
                                            </div>
                                        </div>
                                        <div id="ecom_seo_urls_sync_resultado" class="mb-4"></div>
                                        <div class="table-responsive ecom-seo-scroll">
                                            <table class="table table-row-dashed fs-7 gy-3 mb-0">
                                                <thead><tr class="text-muted fw-bold"><th>Tipo</th><th>Path</th><th>Title</th><th class="text-end">Indexable</th></tr></thead>
                                                <tbody id="ecom_seo_urls_body"></tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xl-4">
                                    <div class="ecom-seo-panel p-5 h-100">
                                        <h3 class="fw-bold mb-4">robots.txt</h3>
                                        <pre class="bg-light p-4 rounded ecom-seo-code" id="ecom_seo_robots_txt"></pre>
                                    </div>
                                </div>
                                <div class="col-xl-7">
                                    <div class="ecom-seo-panel p-5 h-100">
                                        <h3 class="fw-bold mb-4">Redirecciones 301</h3>
                                        <div class="table-responsive ecom-seo-scroll">
                                            <table class="table table-row-dashed fs-7 gy-3 mb-0">
                                                <thead><tr class="text-muted fw-bold"><th>Origen</th><th>Destino</th><th>Estado</th></tr></thead>
                                                <tbody id="ecom_seo_redirecciones_body"></tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xl-5">
                                    <div class="ecom-seo-panel p-5 h-100">
                                        <h3 class="fw-bold mb-4">DDL pendiente</h3>
                                        <div id="ecom_seo_ddl"></div>
                                    </div>
                                </div>
                                <div class="col-xl-5">
                                    <div class="ecom-seo-panel p-5 h-100">
                                        <h3 class="fw-bold mb-4">Tablas SEO</h3>
                                        <div id="ecom_seo_tablas"></div>
                                    </div>
                                </div>
                                <div class="col-xl-7">
                                    <div class="ecom-seo-panel p-5 h-100">
                                        <h3 class="fw-bold mb-4">Acciones autorizadas</h3>
                                        <div id="ecom_seo_autorizados"></div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="ecom-seo-panel p-5">
                                        <h3 class="fw-bold mb-4">Sitemap estructurado</h3>
                                        <div class="table-responsive ecom-seo-scroll">
                                            <table class="table table-row-dashed fs-7 gy-3 mb-0">
                                                <thead><tr class="text-muted fw-bold"><th>Loc</th><th>Changefreq</th><th class="text-end">Priority</th></tr></thead>
                                                <tbody id="ecom_seo_sitemap_body"></tbody>
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
</div>
<script src="assets/plugins/global/plugins.bundle.js"></script>
<script src="assets/js/scripts.bundle.js"></script>
<script>
    window.ERP_CSRF_TOKEN = "<?= htmlspecialchars(SesionSeguridad::csrfToken(), ENT_QUOTES, 'UTF-8') ?>";
</script>
<script src="/assets/js/custom/apps/erp/ecommerce/seo_migracion.js?v=20260910-seo-slug-redir-sin-token1"></script>
</body>
</html>
