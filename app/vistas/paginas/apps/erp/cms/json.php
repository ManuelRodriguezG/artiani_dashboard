<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../../">
    <title>CMS - Preview JSON</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css">
    <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css">
    <!--
      Documentacion IA: Codex GPT-5, 2026-08-10.
      Proposito: vista dedicada para previsualizar, copiar, exportar e importar JSON CMS local.
      Impacto: CMS/API; muestra el contrato que despues consumira el frontend ecommerce.
      Contrato: read-only/local; no publica ni persiste contenido.
    -->
    <style>
        .ecom-cms-panel { border: 1px solid #e7e9ef; border-radius: 8px; background: #fff; }
        .ecom-cms-code { min-height: 520px; max-height: 760px; overflow: auto; background: #111827; color: #e5e7eb; border-radius: 8px; padding: 16px; font-size: .78rem; line-height: 1.5; white-space: pre-wrap; }
        .ecom-cms-actions { display: flex; flex-wrap: wrap; gap: 8px; }
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
                                <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Preview JSON</h1>
                                <span class="text-muted">Contrato que consumira el frontend ecommerce por API</span>
                            </div>
                            <div class="d-flex gap-2">
                                <a class="btn btn-light" href="/cms/contenido"><i class="bi bi-pencil-square"></i> Editar contenido</a>
                                <a class="btn btn-light" href="/docs/erp_cms_manual_uso.md" target="_blank" rel="noopener"><i class="bi bi-journal-text"></i> Manual</a>
                                <button class="btn btn-primary" type="button" id="ecom_cms_recargar"><i class="bi bi-arrow-clockwise"></i> Recargar</button>
                            </div>
                        </div>
                    </div>
                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <div class="alert alert-info d-flex align-items-start gap-3">
                                <i class="bi bi-braces fs-2"></i>
                                <div>
                                    <div class="fw-bold">Contrato API read-only</div>
                                    <div>Esta pantalla previsualiza el JSON que consumira el frontend. Copiar, exportar o importar JSON no publica contenido ni escribe BD.</div>
                                </div>
                            </div>

                            <div class="ecom-cms-panel p-4 mb-5">
                                <div class="row g-3 align-items-end">
                                    <div class="col-md-3"><label class="form-label">Pagina</label><select class="form-select form-select-solid" id="ecom_cms_pagina"><option value="home">Home</option><option value="categoria">Categoria</option><option value="catalogo">Catalogo</option></select></div>
                                    <div class="col-md-3"><label class="form-label">Categoria</label><input class="form-control form-control-solid" id="ecom_cms_categoria" type="text" value="peces"></div>
                                    <div class="col-md-3"><label class="form-label">Plantilla</label><input class="form-control form-control-solid" id="ecom_cms_template" type="text" value="artiani_default"></div>
                                    <div class="col-md-3 text-md-end"><button class="btn btn-light-primary" type="button" id="ecom_cms_preview"><i class="bi bi-eye"></i> Previsualizar</button><span class="badge badge-light-primary ms-2" id="ecom_cms_estado">Listo</span></div>
                                </div>
                            </div>

                            <div class="ecom-cms-panel p-5 mb-5">
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                                    <div>
                                        <h3 class="fw-bold mb-1">Contrato API</h3>
                                        <span class="text-muted fs-7">Rutas internas del panel y rutas publicas que consumira el ecommerce externo.</span>
                                    </div>
                                    <span class="badge badge-light-warning">Defaults hasta BD</span>
                                </div>
                                <div id="ecom_cms_contratos"></div>
                            </div>

                            <div class="ecom-cms-panel p-5 mb-5">
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                                    <div>
                                        <h3 class="fw-bold mb-1">Arranque del frontend</h3>
                                        <span class="text-muted fs-7">Endpoint publico recomendado para inicializar el ecommerce externo.</span>
                                    </div>
                                    <span class="badge badge-light-success">Configuracion inicial</span>
                                </div>
                                <div id="ecom_cms_arranque"></div>
                            </div>

                            <div class="row g-5">
                                <div class="col-xl-8">
                                    <div class="ecom-cms-panel p-5">
                                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                                            <div>
                                                <h3 class="fw-bold mb-1">Respuesta JSON</h3>
                                                <span class="text-muted fs-7">Preview local; la API publica aun seguira leyendo defaults hasta activar BD.</span>
                                            </div>
                                            <div class="ecom-cms-actions">
                                                <button class="btn btn-sm btn-light" type="button" id="ecom_cms_copiar_json"><i class="bi bi-clipboard"></i> Copiar</button>
                                                <button class="btn btn-sm btn-light-primary" type="button" id="ecom_cms_exportar_json"><i class="bi bi-download"></i> Exportar</button>
                                                <span class="badge badge-light-warning">Sin persistencia real</span>
                                            </div>
                                        </div>
                                        <pre class="ecom-cms-code" id="ecom_cms_json">{}</pre>
                                    </div>
                                </div>
                                <div class="col-xl-4">
                                    <div class="ecom-cms-panel p-5">
                                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                                            <div>
                                                <h3 class="fw-bold mb-1">Importar preview</h3>
                                                <span class="text-muted fs-7">Carga un JSON exportado para revisarlo localmente.</span>
                                            </div>
                                            <button class="btn btn-sm btn-light-primary" type="button" id="ecom_cms_importar_json"><i class="bi bi-upload"></i> Importar</button>
                                        </div>
                                        <textarea class="form-control form-control-solid" id="ecom_cms_import_json" rows="14"></textarea>
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
<script src="/assets/js/custom/apps/erp/cms/contenido.js?v=20260810-vistas1"></script>
</body>
</html>
