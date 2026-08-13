<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../../">
    <title>CMS - Persistencia</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css">
    <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css">
    <!--
      Documentacion IA: Codex GPT-5, 2026-08-10.
      Proposito: vista read-only para preparar persistencia real del CMS ecommerce.
      Impacto: CMS; muestra esquema propuesto, guardrails y contratos POST bloqueados.
      Contrato: no ejecuta DDL, no escribe BD y no publica contenido real.
    -->
    <style>
        .ecom-cms-kpi { border: 1px solid #e7e9ef; border-radius: 8px; background: #fff; padding: 16px; min-height: 104px; }
        .ecom-cms-kpi__value { font-size: 1.8rem; line-height: 1; font-weight: 800; color: #181c32; letter-spacing: 0; }
        .ecom-cms-kpi__label { color: #7e8299; font-size: .78rem; text-transform: uppercase; font-weight: 700; }
        .ecom-cms-panel { border: 1px solid #e7e9ef; border-radius: 8px; background: #fff; }
        .ecom-cms-actions { display: flex; flex-wrap: wrap; gap: 8px; }
        .ecom-cms-check { display: grid; gap: 10px; }
        .ecom-cms-check__item { border: 1px solid #e7e9ef; border-radius: 8px; padding: 12px 14px; background: #fbfcfe; }
        .ecom-cms-persistence-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 20px; }
        @media (max-width: 991.98px) { .ecom-cms-persistence-grid { grid-template-columns: 1fr; } }
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
                                <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Persistencia CMS</h1>
                                <span class="text-muted">Preparacion read-only de tablas, respaldos y escrituras futuras</span>
                            </div>
                            <div class="d-flex gap-2">
                                <a class="btn btn-light" href="/cms/plantillas"><i class="bi bi-columns-gap"></i> Plantillas</a>
                                <a class="btn btn-light" href="/cms/json"><i class="bi bi-braces"></i> JSON</a>
                                <a class="btn btn-light" href="/docs/erp_cms_manual_uso.md" target="_blank" rel="noopener"><i class="bi bi-journal-text"></i> Manual</a>
                                <button class="btn btn-primary" type="button" id="ecom_cms_recargar"><i class="bi bi-arrow-clockwise"></i> Recargar</button>
                            </div>
                        </div>
                    </div>
                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <div class="alert alert-warning d-flex align-items-start gap-3">
                                <i class="bi bi-lock fs-2"></i>
                                <div>
                                    <div class="fw-bold">Persistencia real bloqueada</div>
                                    <div>Esta pantalla solo revisa el plan. No ejecuta DDL, no guarda bloques y no publica contenido hasta contar con respaldo y autorizacion.</div>
                                </div>
                            </div>

                            <div class="row g-4 mb-5">
                                <div class="col-md-3"><div class="ecom-cms-kpi"><div class="ecom-cms-kpi__label">Plantilla</div><div class="ecom-cms-kpi__value fs-3" id="ecom_cms_plantilla">-</div><div class="text-muted fs-7 mt-2">Activa para preview.</div></div></div>
                                <div class="col-md-3"><div class="ecom-cms-kpi"><div class="ecom-cms-kpi__label">Slots</div><div class="ecom-cms-kpi__value" id="ecom_cms_slots_total">0</div><div class="text-muted fs-7 mt-2">Declarados.</div></div></div>
                                <div class="col-md-3"><div class="ecom-cms-kpi"><div class="ecom-cms-kpi__label">Bloques</div><div class="ecom-cms-kpi__value" id="ecom_cms_tipos_total">0</div><div class="text-muted fs-7 mt-2">Tipos base.</div></div></div>
                                <div class="col-md-3"><div class="ecom-cms-kpi"><div class="ecom-cms-kpi__label">Persistencia</div><div class="ecom-cms-kpi__value fs-3" id="ecom_cms_persistencia">Read-only</div><div class="text-muted fs-7 mt-2">DDL pendiente.</div></div></div>
                            </div>

                            <div class="ecom-cms-panel p-5 mb-5">
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                                    <div>
                                        <h3 class="fw-bold mb-1">Planes DDL read-only</h3>
                                        <span class="text-muted fs-7">Contenido editorial y frontend visual se revisan por separado antes de autorizar cualquier cambio.</span>
                                    </div>
                                    <span class="badge badge-light-warning">No ejecuta DDL</span>
                                </div>
                                <div class="ecom-cms-persistence-grid">
                                    <div>
                                        <h4 class="fw-bold mb-3">CMS Contenido</h4>
                                        <div id="ecom_cms_esquema"></div>
                                    </div>
                                    <div>
                                        <h4 class="fw-bold mb-3">CMS Frontend</h4>
                                        <div id="ecom_cms_esquema_frontend"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-5">
                                <div class="col-xl-7">
                                    <div class="ecom-cms-panel p-5 mb-5">
                                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                                            <div>
                                                <h3 class="fw-bold mb-1">Checklist de autorizacion</h3>
                                                <span class="text-muted fs-7">Condiciones necesarias antes de activar escrituras reales.</span>
                                            </div>
                                            <span class="badge badge-light-warning">Pendiente</span>
                                        </div>
                                        <div class="ecom-cms-check">
                                            <div class="ecom-cms-check__item"><span class="badge badge-light-warning me-2">1</span> Generar respaldo externo en <code>C:\xampp\panel_db_backups</code>.</div>
                                            <div class="ecom-cms-check__item"><span class="badge badge-light-warning me-2">2</span> Autorizar y aplicar DDL de tablas CMS.</div>
                                            <div class="ecom-cms-check__item"><span class="badge badge-light-warning me-2">3</span> Activar endpoints POST con CSRF y auditoria explicita.</div>
                                            <div class="ecom-cms-check__item"><span class="badge badge-light-warning me-2">4</span> Definir sanitizacion para <code>content_html_safe</code>.</div>
                                            <div class="ecom-cms-check__item"><span class="badge badge-light-warning me-2">5</span> Definir politica de media, nombres, tamanos y rutas publicas.</div>
                                            <div class="ecom-cms-check__item"><span class="badge badge-light-warning me-2">6</span> Cambiar endpoints publicos para leer BD publicada con fallback default.</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xl-5">
                                    <div class="ecom-cms-panel p-5 mb-5">
                                        <h3 class="fw-bold mb-4">Alcance de persistencia</h3>
                                        <div class="ecom-cms-check">
                                            <div class="ecom-cms-check__item"><span class="badge badge-light-primary me-2">Contenido</span> Guarda plantillas editoriales, slots, bloques, publicaciones y media.</div>
                                            <div class="ecom-cms-check__item"><span class="badge badge-light-primary me-2">Frontend</span> Guarda temas visuales, layouts, componentes, plantillas, secciones y activaciones.</div>
                                            <div class="ecom-cms-check__item"><span class="badge badge-light-success me-2">API</span> Publicara solo contenido publicado/vigente desde BD, con fallback default.</div>
                                            <div class="ecom-cms-check__item"><span class="badge badge-light-danger me-2">Guardrail</span> No toca catalogo, precios, inventario ni publicaciones de producto.</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="ecom-cms-panel p-5 mb-5">
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                                    <div>
                                        <h3 class="fw-bold mb-1">Contratos bloqueados</h3>
                                        <span class="text-muted fs-7">Nombres estables para la siguiente fase; hoy responden read-only.</span>
                                    </div>
                                    <span class="badge badge-light-danger">No escribe BD</span>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-row-dashed fs-7 gy-3 mb-0">
                                        <tbody>
                                            <tr><td class="fw-semibold">Guardar bloque</td><td><code>/cms/contenido_bloque_guardar_erp</code></td><td class="text-end"><span class="badge badge-light-warning">Bloqueado</span></td></tr>
                                            <tr><td class="fw-semibold">Estatus bloque</td><td><code>/cms/contenido_bloque_estatus_erp</code></td><td class="text-end"><span class="badge badge-light-warning">Bloqueado</span></td></tr>
                                            <tr><td class="fw-semibold">Guardar publicacion</td><td><code>/cms/contenido_publicacion_guardar_erp</code></td><td class="text-end"><span class="badge badge-light-warning">Bloqueado</span></td></tr>
                                            <tr><td class="fw-semibold">Estatus publicacion</td><td><code>/cms/contenido_publicacion_estatus_erp</code></td><td class="text-end"><span class="badge badge-light-warning">Bloqueado</span></td></tr>
                                            <tr><td class="fw-semibold">Guardar plantilla frontend</td><td><code>/cms/frontend_plantilla_guardar_erp</code></td><td class="text-end"><span class="badge badge-light-warning">Bloqueado</span></td></tr>
                                            <tr><td class="fw-semibold">Estatus plantilla frontend</td><td><code>/cms/frontend_plantilla_estatus_erp</code></td><td class="text-end"><span class="badge badge-light-warning">Bloqueado</span></td></tr>
                                            <tr><td class="fw-semibold">Guardar seccion frontend</td><td><code>/cms/frontend_seccion_guardar_erp</code></td><td class="text-end"><span class="badge badge-light-warning">Bloqueado</span></td></tr>
                                            <tr><td class="fw-semibold">Estatus seccion frontend</td><td><code>/cms/frontend_seccion_estatus_erp</code></td><td class="text-end"><span class="badge badge-light-warning">Bloqueado</span></td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="ecom-cms-panel p-5">
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                                    <div>
                                        <h3 class="fw-bold mb-1">Contratos del CMS</h3>
                                        <span class="text-muted fs-7">Estado interno y endpoints publicos futuros.</span>
                                    </div>
                                    <span class="badge badge-light-primary" id="ecom_cms_estado">Listo</span>
                                </div>
                                <div id="ecom_cms_contratos"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<input type="hidden" id="ecom_cms_pagina" value="home">
<input type="hidden" id="ecom_cms_categoria" value="peces">
<input type="hidden" id="ecom_cms_template" value="artiani_default">
<script src="assets/plugins/global/plugins.bundle.js"></script>
<script src="assets/js/scripts.bundle.js"></script>
<script src="/assets/js/custom/apps/erp/cms/contenido.js?v=20260810-persistencia1"></script>
</body>
</html>
