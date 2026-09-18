<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../../">
    <title>Ecommerce - Verificacion SEO local</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css">
    <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css">
    <!--
      Documentacion IA: Codex GPT-5, 2026-09-17.
      Proposito: vista para verificar redirecciones, 410 y sitemap contra frontend local con marcas en BD.
      Impacto: Ecommerce SEO; ayuda a revisar Artiani v2 antes de produccion sin modificar reglas.
      Contrato: consulta endpoints internos protegidos y guarda marcas operativas; no cambia URLs.
    -->
    <style>
        .seo-check-card { border: 1px solid #e7e9ef; border-radius: 8px; background: #fff; }
        .seo-check-kpi { border: 1px solid #e7e9ef; border-radius: 8px; background: #fff; padding: 16px; min-height: 104px; }
        .seo-check-kpi__label { color: #7e8299; font-size: .78rem; text-transform: uppercase; font-weight: 700; }
        .seo-check-kpi__value { font-size: 1.7rem; line-height: 1; font-weight: 800; color: #181c32; letter-spacing: 0; }
        .seo-check-path { word-break: break-word; }
        .seo-check-scroll { max-height: 520px; overflow: auto; }
        .seo-check-table { min-width: 1180px; }
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
                                <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Verificacion SEO local</h1>
                                <span class="text-muted">Prueba redirecciones, 410 y URLs de sitemap contra el frontend local</span>
                            </div>
                            <div class="d-flex gap-2">
                                <a class="btn btn-light" href="/ecommercePublico/seo_migracion"><i class="bi bi-arrow-left"></i> Mesa SEO</a>
                                <button class="btn btn-primary" type="button" id="seo_check_ejecutar"><i class="bi bi-play-circle"></i> Verificar</button>
                            </div>
                        </div>
                    </div>
                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <div class="alert alert-primary d-flex align-items-start justify-content-between gap-4">
                                <div>
                                    <div class="fw-bold">Como leer esta pantalla</div>
                                    <div>Las reglas 301/410 salen del ERP. Esta vista separa la prueba de la URL vieja y la prueba de la URL nueva, guardando cada avance en base de datos.</div>
                                </div>
                                <span class="badge badge-light-primary" id="seo_check_estado">Listo</span>
                            </div>

                            <div class="seo-check-card p-5 mb-5">
                                <div class="row g-3 align-items-end">
                                    <div class="col-lg-5">
                                        <label class="form-label">Frontend local/staging</label>
                                        <input class="form-control form-control-solid" id="seo_check_frontend" value="http://artiani.com.local">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Limite reglas</label>
                                        <input class="form-control form-control-solid" id="seo_check_limite_reglas" type="number" min="1" max="500" value="120">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Limite sitemap</label>
                                        <input class="form-control form-control-solid" id="seo_check_limite_sitemap" type="number" min="1" max="500" value="120">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Prueba HTTP</label>
                                        <select class="form-select form-select-solid" id="seo_check_probar_http">
                                            <option value="1">Si, probar URLs</option>
                                            <option value="0">No, solo listar</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-4 mb-5">
                                <div class="col-md-6 col-xl"><div class="seo-check-kpi"><div class="seo-check-kpi__label">Reglas</div><div class="seo-check-kpi__value" id="seo_check_kpi_reglas">0</div><div class="text-muted fs-7 mt-2">301 y 410 revisadas.</div></div></div>
                                <div class="col-md-6 col-xl"><div class="seo-check-kpi"><div class="seo-check-kpi__label">Reglas OK</div><div class="seo-check-kpi__value" id="seo_check_kpi_reglas_ok">0</div><div class="text-muted fs-7 mt-2">Status y destino correctos.</div></div></div>
                                <div class="col-md-6 col-xl"><div class="seo-check-kpi"><div class="seo-check-kpi__label">Sitemap</div><div class="seo-check-kpi__value" id="seo_check_kpi_sitemap">0</div><div class="text-muted fs-7 mt-2">URLs indexables listadas.</div></div></div>
                                <div class="col-md-6 col-xl"><div class="seo-check-kpi"><div class="seo-check-kpi__label">Revisar</div><div class="seo-check-kpi__value" id="seo_check_kpi_revisar">0</div><div class="text-muted fs-7 mt-2">Algo no respondio esperado.</div></div></div>
                                <div class="col-md-6 col-xl"><div class="seo-check-kpi"><div class="seo-check-kpi__label">Probadas</div><div class="seo-check-kpi__value" id="seo_check_kpi_probadas">0</div><div class="text-muted fs-7 mt-2">Guardadas en BD.</div></div></div>
                            </div>

                            <div id="seo_check_mensaje" class="mb-5"></div>

                            <div class="seo-check-card p-4 mb-5">
                                <div class="d-flex flex-wrap gap-3 align-items-end justify-content-between">
                                    <div>
                                        <label class="form-label">Filtro de revision</label>
                                        <select class="form-select form-select-solid" id="seo_check_filtro_revision">
                                            <option value="todas">Todas</option>
                                            <option value="pendientes">Solo pendientes</option>
                                            <option value="probadas">Solo probadas</option>
                                        </select>
                                    </div>
                                    <div class="d-flex flex-wrap gap-2">
                                        <button class="btn btn-light" type="button" id="seo_check_marcar_visibles"><i class="bi bi-check2-square"></i> Marcar visibles como probadas</button>
                                        <button class="btn btn-light-danger" type="button" id="seo_check_limpiar_marcas"><i class="bi bi-trash"></i> Limpiar marcas</button>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-5">
                                <div class="col-12">
                                    <div class="seo-check-card p-5">
                                        <div class="d-flex justify-content-between align-items-center mb-4">
                                            <h3 class="fw-bold mb-0">Reglas SEO aprobadas</h3>
                                            <span class="badge badge-light-info">301 / 410</span>
                                        </div>
                                        <div class="table-responsive seo-check-scroll">
                                            <table class="table table-row-dashed fs-7 gy-3 mb-0 seo-check-table">
                                                <thead><tr class="text-muted fw-bold"><th>Pruebas</th><th>Regla</th><th>URL vieja probada</th><th>URL nueva esperada</th><th>Respuestas</th><th>Resultado</th></tr></thead>
                                                <tbody id="seo_check_reglas_body"></tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xl-8">
                                    <div class="seo-check-card p-5 h-100">
                                        <div class="d-flex justify-content-between align-items-center mb-4">
                                            <h3 class="fw-bold mb-0">URLs que entran al sitemap</h3>
                                            <span class="badge badge-light-success">Indexables</span>
                                        </div>
                                        <div class="table-responsive seo-check-scroll">
                                            <table class="table table-row-dashed fs-7 gy-3 mb-0 seo-check-table">
                                                <thead><tr class="text-muted fw-bold"><th>Probada</th><th>URL productiva</th><th>URL local</th><th>Frecuencia</th><th>Respuesta</th><th>Resultado</th></tr></thead>
                                                <tbody id="seo_check_sitemap_body"></tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xl-4">
                                    <div class="seo-check-card p-5 h-100">
                                        <h3 class="fw-bold mb-4">Como se decide el sitemap</h3>
                                        <div id="seo_check_explicacion"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?= include_once '../app/vistas/includes/footer/footer.php'; ?>
            </div>
        </div>
    </div>
</div>
<script src="assets/plugins/global/plugins.bundle.js"></script>
<script src="assets/js/scripts.bundle.js"></script>
<script src="/assets/js/custom/apps/erp/ecommerce/seo_verificacion.js?v=20260918-bd-verificacion-doble"></script>
</body>
</html>
