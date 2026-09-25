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
      IA: Codex GPT-6 | Fecha: 2026-09-24.
      Proposito: administrar formatos originales, peso, reemplazo y usos de medios CMS.
      Impacto: biblioteca compartida por el contenido ecommerce.
      Contrato: acciones segun permisos; reemplazo conserva referencias y eliminacion valida usos en servidor.
    -->
    <style>
        .cms-media-panel { border: 1px solid #e7e9ef; border-radius: 8px; background: #fff; }
        .cms-media-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 16px; }
        .cms-media-card { border: 1px solid #e7e9ef; border-radius: 8px; background: #fff; overflow: hidden; cursor: pointer; }
        .cms-media-card.is-active { border-color: #009ef7; box-shadow: 0 0 0 3px rgba(0, 158, 247, .12); }
        .cms-media-thumb { width: 100%; aspect-ratio: 16 / 10; object-fit: contain; background: #f3f6f9; display: block; }
        .cms-media-detail-img, .ecom-cms-preview-img { width: 100%; max-height: 300px; object-fit: contain; border: 1px solid #e7e9ef; border-radius: 8px; background: #f3f6f9; }
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
                                <span class="text-muted">Imagenes reutilizables del sitio: revisa peso, formato y donde se utilizan.</span>
                            </div>
                            <div class="d-flex gap-2">
                                <a class="btn btn-light" href="/cms/frontend/home"><i class="bi bi-house"></i> Home</a>
                                <a class="btn btn-light" href="/docs/erp_cms_manual_uso.md" target="_blank" rel="noopener"><i class="bi bi-journal-text"></i> Manual</a>
                                <button class="btn btn-light-warning" type="button" id="cms_media_limpiar_temporales"><i class="bi bi-eraser"></i> Limpiar temporales</button>
                                <button class="btn btn-light-danger" type="button" id="cms_media_limpiar_archivados"><i class="bi bi-archive"></i> Limpiar temporales archivados</button>
                            </div>
                        </div>
                    </div>
                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <div class="alert alert-info d-flex align-items-start gap-3">
                                <i class="bi bi-images fs-2"></i>
                                <div>
                                    <div class="fw-bold">Biblioteca Media CMS</div>
                                    <div>Puedes mejorar el nombre, convertir a WebP por eleccion o reemplazar con otro formato. Las referencias anteriores siguen funcionando. Los archivos conservan su formato original salvo que elijas convertirlos; incluido ICO para favicon. Para eliminar, primero se revisan sus usos guardados.</div>
                                </div>
                            </div>

                            <div class="row g-5">
                                <div class="col-12">
                                    <div class="cms-media-panel p-5" id="cms_media_alta_panel" hidden>
                                        <div class="d-flex justify-content-between align-items-start gap-3 mb-4">
                                            <div>
                                                <h3 class="fw-bold mb-1">Agregar imagen</h3>
                                                <span class="text-muted fs-7">JPG, JPEG, PNG, WebP, GIF, AVIF o ICO. Maximo: 2 MB por archivo.</span>
                                            </div>
                                        </div>
                                        <div class="cms-media-drop mb-4">
                                            <label class="form-label fw-bold" for="cms_media_archivo">Archivo</label>
                                            <input class="form-control" type="file" id="cms_media_archivo" accept=".jpg,.jpeg,.png,.webp,.gif,.avif,.ico">
                                            <label class="form-check form-check-custom form-check-solid mt-4">
                                                <input class="form-check-input" type="checkbox" id="cms_media_optimizar">
                                                <span class="form-check-label">Optimizar al subir (JPG, PNG y WebP estaticos; conserva el formato)</span>
                                            </label>
                                            <label class="form-check form-check-custom form-check-solid mt-3">
                                                <input class="form-check-input" type="checkbox" id="cms_media_webp">
                                                <span class="form-check-label">Convertir a WebP al subir (opcional; JPG, PNG y WebP estaticos)</span>
                                            </label>
                                            <div class="text-muted fs-7 mt-2">Sin esta opcion se conserva el archivo original. Optimizar admite una fuente de hasta 20 MB y reduce a un maximo de 2560 px por lado y calidad 82% cuando el formato lo permite; se aplica solo si pesa menos y el resultado no supera 2 MB. GIF, AVIF, ICO e imagenes animadas se conservan sin optimizacion.</div>
                                        </div>
                                        <div class="row g-3">
                                            <div class="col-md-3">
                                                <label class="form-label" for="cms_media_uso">Uso</label>
                                                <select class="form-select form-select-solid" id="cms_media_uso">
                                                    <option value="home">Home</option>
                                                    <option value="categoria">Categoria</option>
                                                    <option value="producto">Producto</option>
                                                    <option value="global">Global</option>
                                                    <option value="blog">Blog futuro</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label" for="cms_media_tipo">Tipo</label>
                                                <select class="form-select form-select-solid" id="cms_media_tipo">
                                                    <option value="logo">Logo principal</option>
                                                    <option value="logo_blanco">Logo blanco</option>
                                                    <option value="favicon">Favicon</option>
                                                    <option value="open_graph">Imagen social SEO</option>
                                                    <option value="banner">Banner</option>
                                                    <option value="hero">Hero</option>
                                                    <option value="card">Card</option>
                                                    <option value="thumb">Thumbnail</option>
                                                    <option value="editorial">Editorial</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label" for="cms_media_alt">Alt text</label>
                                                <input class="form-control form-control-solid" id="cms_media_alt" type="text" placeholder="Descripcion accesible de la imagen">
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label" for="cms_media_nombre_seo">Nombre del archivo para SEO (sin extension)</label>
                                                <div class="input-group"><input class="form-control form-control-solid" id="cms_media_nombre_seo" maxlength="120" type="text" placeholder="collares-para-perros"><button class="btn btn-light" type="button" id="cms_media_sugerir_nombre">Sugerir desde descripcion</button></div>
                                                <div class="text-muted fs-7 mt-2">Usa un nombre breve que describa lo visible, sin repetir palabras clave. Revisa la sugerencia antes de subir.</div>
                                                <div class="text-muted fs-7 mt-2" id="cms_media_nombre_preview">Ejemplo: collares-para-perros.webp</div>
                                            </div>
                                            <div class="col-12">
                                                <button class="btn btn-primary w-100" type="button" id="cms_media_agregar"><i class="bi bi-cloud-upload"></i> Subir a biblioteca</button>
                                            </div>
                                        </div>
                                    </div>

                                </div>

                                <div class="col-12">
                                    <div class="cms-media-panel p-5 mb-5">
                                        <div id="cms_media_estado" class="alert alert-light-info" role="status" aria-live="polite" tabindex="-1">Cargando biblioteca...</div>
                                        <div id="cms_media_preflight" class="text-muted fs-7 mb-4"></div>
                                        <div class="d-flex justify-content-between align-items-end flex-wrap gap-3 mb-4">
                                            <div>
                                                <h3 class="fw-bold mb-1">Biblioteca</h3>
                                                <span class="text-muted fs-7" id="cms_media_resumen">Selecciona una imagen para consultar sus usos y administrarla.</span>
                                            </div>
                                            <div class="d-flex flex-wrap gap-2">
                                                <input class="form-control form-control-sm form-control-solid w-200px" id="cms_media_buscar" type="search" placeholder="Buscar nombre o descripcion" aria-label="Buscar imagen">
                                                <select class="form-select form-select-sm form-select-solid w-150px" id="cms_media_filtro_uso" aria-label="Filtrar uso">
                                                    <option value="">Todos</option>
                                                    <option value="home">Home</option>
                                                    <option value="categoria">Categoria</option>
                                                    <option value="producto">Producto</option>
                                                    <option value="global">Global</option>
                                                    <option value="blog">Blog futuro</option>
                                                </select>
                                                <select class="form-select form-select-sm form-select-solid w-200px" id="cms_media_orden" aria-label="Ordenar biblioteca">
                                                    <option value="peso_desc">Mayor peso primero</option>
                                                    <option value="recientes">Mas recientes</option>
                                                    <option value="nombre">Nombre</option>
                                                </select>
                                                <button class="btn btn-sm btn-light" id="cms_media_recargar" type="button">Actualizar</button>
                                            </div>
                                        </div>
                                        <div class="cms-media-grid" id="cms_media_biblioteca"></div>
                                    </div>
                                </div>

                                <div class="col-12">
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
<script>
    window.ERP_CSRF_TOKEN = "<?= htmlspecialchars(Sesionseguridad::csrfToken(), ENT_QUOTES, 'UTF-8') ?>";
</script>
<!-- IA: Codex GPT-6 | 2026-09-25 | Renovar cache para avisos visibles y reparacion puntual de acceso, conservando ID y URL. -->
<script src="/assets/js/custom/apps/erp/cms/media_tools.js?v=20260925-media-acceso2"></script>
<script src="/assets/js/custom/apps/erp/cms/media.js?v=20260925-media-acceso2"></script>
</body>
</html>
