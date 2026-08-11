<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../../">
    <title>CMS - Media</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css">
    <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css">
    <!--
      Documentacion IA: Codex GPT-5, 2026-08-10.
      Proposito: vista dedicada para revisar media y accesibilidad de bloques CMS.
      Impacto: CMS; prepara reglas de imagen desktop/mobile sin activar carga de archivos.
      Contrato: read-only/local; no sube archivos ni escribe BD.
    -->
    <style>
        .ecom-cms-panel { border: 1px solid #e7e9ef; border-radius: 8px; background: #fff; }
        .ecom-cms-slot { border: 1px solid #e7e9ef; border-radius: 8px; padding: 14px; background: #fbfcfe; }
        .ecom-cms-slot + .ecom-cms-slot { margin-top: 10px; }
        .ecom-cms-slot.is-active { border-color: #3e97ff; background: #f1f8ff; }
        .ecom-cms-chip-list { display: flex; flex-wrap: wrap; gap: 6px; }
        .ecom-cms-block { border: 1px solid #e7e9ef; border-radius: 8px; padding: 14px; background: #fff; }
        .ecom-cms-block + .ecom-cms-block { margin-top: 10px; }
        .ecom-cms-actions { display: flex; flex-wrap: wrap; gap: 8px; }
        .ecom-cms-preview-img { width: 100%; aspect-ratio: 16 / 7; border-radius: 8px; border: 1px solid #e7e9ef; background: #f3f6f9; object-fit: cover; }
    </style>
</head>
<body id="kt_app_body" data-cms-bloques-mode="seleccion" data-kt-app-layout="dark-sidebar" data-kt-app-header-fixed="true" data-kt-app-sidebar-enabled="true" data-kt-app-sidebar-fixed="true" class="app-default">
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
                                <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Media CMS</h1>
                                <span class="text-muted">Revision visual de imagenes desktop/mobile y alt text</span>
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
                                <i class="bi bi-images fs-2"></i>
                                <div>
                                    <div class="fw-bold">Vista de inspeccion read-only</div>
                                    <div>Revisa imagenes desktop/mobile, alt text y preview visual. La carga real de archivos y guardado de rutas sigue pendiente.</div>
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

                            <div class="row g-5">
                                <div class="col-xl-4">
                                    <div class="ecom-cms-panel p-5 mb-5">
                                        <h3 class="fw-bold mb-4">Slots</h3>
                                        <div id="ecom_cms_slots"></div>
                                    </div>
                                </div>
                                <div class="col-xl-4">
                                    <div class="ecom-cms-panel p-5 mb-5">
                                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                                            <div>
                                                <h3 class="fw-bold mb-1">Bloques</h3>
                                                <span class="text-muted fs-7" id="ecom_cms_slot_activo_label">Selecciona un slot</span>
                                            </div>
                                            <select class="form-select form-select-sm form-select-solid w-150px" id="ecom_cms_filtro_estatus">
                                                <option value="">Todos</option>
                                                <option value="borrador">Borrador</option>
                                                <option value="publicado">Publicado</option>
                                                <option value="pausado">Pausado</option>
                                            </select>
                                        </div>
                                        <div id="ecom_cms_bloques"></div>
                                    </div>
                                </div>
                                <div class="col-xl-4">
                                    <div class="ecom-cms-panel p-5 mb-5">
                                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                                            <div>
                                                <h3 class="fw-bold mb-1">Preview visual</h3>
                                                <span class="text-muted fs-7">Bloque seleccionado.</span>
                                            </div>
                                            <span class="badge badge-light-primary" id="ecom_cms_preview_badge">Preview</span>
                                        </div>
                                        <div id="ecom_cms_visual"></div>
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
