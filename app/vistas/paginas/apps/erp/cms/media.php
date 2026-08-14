<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../../">
    <title>CMS - Media / Archivos</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css">
    <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css">
    <!--
      Documentacion IA: Codex GPT-5, 2026-08-14.
      Proposito: iniciar biblioteca media del CMS para seleccionar imagenes de frontend.
      Impacto: CMS media; reemplaza captura manual de URLs por flujo local preparado.
      Contrato: biblioteca local/read-only servidor; no sube archivos, no borra fisicos y no escribe BD.
    -->
    <style>
        .cms-media-panel { border: 1px solid #e7e9ef; border-radius: 8px; background: #fff; }
        .cms-media-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 16px; }
        .cms-media-card { border: 1px solid #e7e9ef; border-radius: 8px; background: #fff; overflow: hidden; cursor: pointer; }
        .cms-media-card.is-active { border-color: #009ef7; box-shadow: 0 0 0 3px rgba(0, 158, 247, .12); }
        .cms-media-thumb { width: 100%; aspect-ratio: 16 / 10; object-fit: cover; background: #f3f6f9; display: block; }
        .cms-media-detail-img, .ecom-cms-preview-img { width: 100%; aspect-ratio: 16 / 9; object-fit: cover; border: 1px solid #e7e9ef; border-radius: 8px; background: #f3f6f9; }
        .cms-media-actions { display: flex; flex-wrap: wrap; gap: 8px; }
        .cms-media-drop { border: 1px dashed #b5c4d8; border-radius: 8px; background: #f8fbff; padding: 18px; }
        .cms-media-meta { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
        @media (max-width: 991.98px) { .cms-media-meta { grid-template-columns: 1fr; } }
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
                                <h1 class="page-heading text-dark fw-bold fs-3 mb-1">CMS / Media / Archivos</h1>
                                <span class="text-muted">Biblioteca inicial para imagenes del frontend ecommerce</span>
                            </div>
                            <div class="d-flex gap-2">
                                <a class="btn btn-light" href="/cms/frontend/home"><i class="bi bi-house"></i> Home</a>
                                <a class="btn btn-light" href="/docs/erp_cms_manual_uso.md" target="_blank" rel="noopener"><i class="bi bi-journal-text"></i> Manual</a>
                                <button class="btn btn-light-danger" type="button" id="cms_media_limpiar_archivados"><i class="bi bi-archive"></i> Limpiar archivados</button>
                            </div>
                        </div>
                    </div>
                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <div class="alert alert-info d-flex align-items-start gap-3">
                                <i class="bi bi-images fs-2"></i>
                                <div>
                                    <div class="fw-bold">Biblioteca local preparada</div>
                                    <div>Selecciona imagenes, revisa miniaturas, asigna alt text y clasifica su uso. En esta fase no sube archivos al servidor ni borra fisicos; prepara el flujo profesional antes de activar persistencia real.</div>
                                </div>
                            </div>

                            <div class="row g-5">
                                <div class="col-xl-4">
                                    <div class="cms-media-panel p-5 mb-5">
                                        <div class="d-flex justify-content-between align-items-start gap-3 mb-4">
                                            <div>
                                                <h3 class="fw-bold mb-1">Agregar imagen</h3>
                                                <span class="text-muted fs-7">JPG, PNG o WebP. Maximo local recomendado: 2 MB.</span>
                                            </div>
                                            <span class="badge badge-light-primary" id="cms_media_estado">Listo</span>
                                        </div>
                                        <div class="cms-media-drop mb-4">
                                            <label class="form-label fw-bold">Archivo</label>
                                            <input class="form-control" type="file" id="cms_media_archivo" accept="image/jpeg,image/png,image/webp">
                                        </div>
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label">Uso</label>
                                                <select class="form-select form-select-solid" id="cms_media_uso">
                                                    <option value="home">Home</option>
                                                    <option value="categoria">Categoria</option>
                                                    <option value="producto">Producto</option>
                                                    <option value="global">Global</option>
                                                    <option value="blog">Blog futuro</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Tipo</label>
                                                <select class="form-select form-select-solid" id="cms_media_tipo">
                                                    <option value="banner">Banner</option>
                                                    <option value="hero">Hero</option>
                                                    <option value="card">Card</option>
                                                    <option value="thumb">Thumbnail</option>
                                                    <option value="editorial">Editorial</option>
                                                </select>
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label">Alt text</label>
                                                <input class="form-control form-control-solid" id="cms_media_alt" type="text" placeholder="Descripcion accesible de la imagen">
                                            </div>
                                            <div class="col-12">
                                                <button class="btn btn-primary w-100" type="button" id="cms_media_agregar"><i class="bi bi-plus-circle"></i> Agregar a biblioteca local</button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="cms-media-panel p-5 mb-5">
                                        <h3 class="fw-bold mb-4">Politica inicial</h3>
                                        <div class="d-flex flex-column gap-3 fs-7">
                                            <div><span class="badge badge-light-success me-2">OK</span>Validar extension, peso y alt text antes de usar.</div>
                                            <div><span class="badge badge-light-warning me-2">Pendiente</span>Subida real a carpeta publica controlada.</div>
                                            <div><span class="badge badge-light-warning me-2">Pendiente</span>BD para uso, referencias y limpieza segura.</div>
                                            <div><span class="badge badge-light-danger me-2">No</span>No guardar rutas internas del ERP como URL publica.</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-xl-5">
                                    <div class="cms-media-panel p-5 mb-5">
                                        <div class="d-flex justify-content-between align-items-end flex-wrap gap-3 mb-4">
                                            <div>
                                                <h3 class="fw-bold mb-1">Biblioteca</h3>
                                                <span class="text-muted fs-7">Imagenes disponibles para seleccionar en Home y paginas futuras.</span>
                                            </div>
                                            <div class="d-flex gap-2">
                                                <input class="form-control form-control-sm form-control-solid w-200px" id="cms_media_buscar" type="text" placeholder="Buscar">
                                                <select class="form-select form-select-sm form-select-solid w-150px" id="cms_media_filtro_uso">
                                                    <option value="">Todos</option>
                                                    <option value="home">Home</option>
                                                    <option value="categoria">Categoria</option>
                                                    <option value="producto">Producto</option>
                                                    <option value="global">Global</option>
                                                    <option value="blog">Blog futuro</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="cms-media-grid" id="cms_media_biblioteca"></div>
                                    </div>
                                </div>

                                <div class="col-xl-3">
                                    <div class="cms-media-panel p-5 mb-5">
                                        <div class="d-flex justify-content-between align-items-start gap-3 mb-4">
                                            <div>
                                                <h3 class="fw-bold mb-1">Detalle</h3>
                                                <span class="text-muted fs-7">Selecciona una imagen.</span>
                                            </div>
                                            <span class="badge badge-light-primary" id="ecom_cms_preview_badge">Media</span>
                                        </div>
                                        <div id="cms_media_detalle"></div>
                                        <div id="ecom_cms_visual" class="mt-4"></div>
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
<script src="/assets/js/custom/apps/erp/cms/media.js?v=20260814-media1"></script>
</body>
</html>
