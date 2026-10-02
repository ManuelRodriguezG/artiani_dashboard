<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../../">
    <title>PIT - Process Image Tool</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css">
    <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css">
    <!--
      IA: Codex GPT-6 | Fecha: 2026-09-30.
      Proposito: crear la primera mesa de trabajo PIT independiente.
      Impacto: PIT/Media; permite preparar imagenes sin tocar Catalogo, CMS ni otros modulos consumidores.
      Contrato: usa endpoints /pit/*; la asignacion a productos queda fuera de esta fase.
    -->
    <style>
        .pit-panel { border: 1px solid #e7e9ef; border-radius: 8px; background: #fff; }
        .pit-drop { border: 1px dashed #b5c4d8; border-radius: 8px; background: #f8fbff; padding: 18px; }
        .pit-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 16px; }
        .pit-card { border: 1px solid #e7e9ef; border-radius: 8px; background: #fff; overflow: hidden; cursor: pointer; }
        .pit-card.is-active { border-color: #009ef7; box-shadow: 0 0 0 3px rgba(0, 158, 247, .12); }
        .pit-thumb { width: 100%; aspect-ratio: 16 / 10; object-fit: contain; background: #f3f6f9; display: block; }
        .pit-preview-img { width: 100%; max-height: 340px; object-fit: contain; border: 1px solid #e7e9ef; border-radius: 8px; background: #f3f6f9; }
        .pit-preview-stage { position: relative; width: min(100%, 520px); aspect-ratio: 1 / 1; margin: 0 auto; border: 1px solid #d7dce5; border-radius: 8px; background: #f3f6f9; overflow: hidden; cursor: grab; touch-action: none; }
        .pit-preview-stage.is-dragging { cursor: grabbing; }
        .pit-preview-stage[data-fit="contain"] { cursor: default; }
        .pit-preview-stage[data-movable="1"] { cursor: grab; }
        .pit-preview-stage img { width: 100%; height: 100%; object-fit: cover; display: block; }
        .pit-preview-stage[data-fit="contain"] img { object-fit: contain; }
        .pit-preview-guides { position: absolute; inset: 0; pointer-events: none; background-image: linear-gradient(to right, transparent 33.333%, rgba(255,255,255,.72) 33.333%, rgba(255,255,255,.72) 33.8%, transparent 33.8%, transparent 66.666%, rgba(255,255,255,.72) 66.666%, rgba(255,255,255,.72) 67.13%, transparent 67.13%), linear-gradient(to bottom, transparent 33.333%, rgba(255,255,255,.72) 33.333%, rgba(255,255,255,.72) 33.8%, transparent 33.8%, transparent 66.666%, rgba(255,255,255,.72) 66.666%, rgba(255,255,255,.72) 67.13%, transparent 67.13%); box-shadow: inset 0 0 0 999px rgba(0,0,0,.08); }
        .pit-preview-handle { position: absolute; width: 34px; height: 34px; border: 3px solid #009ef7; border-radius: 999px; background: rgba(255,255,255,.94); transform: translate(-50%, -50%); box-shadow: 0 4px 14px rgba(15,23,42,.25); cursor: grab; z-index: 2; }
        .pit-preview-handle::before, .pit-preview-handle::after { content: ""; position: absolute; background: #009ef7; left: 50%; top: 50%; transform: translate(-50%, -50%); }
        .pit-preview-handle::before { width: 18px; height: 2px; }
        .pit-preview-handle::after { width: 2px; height: 18px; }
        .pit-preview-orientation { position: absolute; left: 10px; top: 10px; z-index: 2; }
        .pit-preview-help { max-width: 520px; margin: 10px auto 0; }
        .pit-meta { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
        .pit-actions { display: flex; flex-wrap: wrap; gap: 8px; }
        .pit-queue { display: grid; gap: 10px; max-height: 360px; overflow: auto; }
        .pit-queue-item { display: grid; grid-template-columns: 54px minmax(180px, 1fr) minmax(180px, 320px) auto; gap: 12px; align-items: center; border: 1px solid #e7e9ef; border-radius: 8px; padding: 10px; background: #fff; }
        .pit-queue-thumb { width: 54px; height: 54px; object-fit: cover; border-radius: 6px; background: #f3f6f9; border: 1px solid #e7e9ef; }
        .pit-queue-result { min-width: 112px; text-align: right; }
        .pit-tabbar { display: flex; gap: 8px; flex-wrap: wrap; }
        .pit-workspace[hidden] { display: none !important; }
        @media (max-width: 991.98px) { .pit-meta { grid-template-columns: 1fr; } }
        @media (max-width: 991.98px) { .pit-queue-item { grid-template-columns: 54px minmax(0, 1fr); } .pit-queue-seo, .pit-queue-result { grid-column: 1 / -1; text-align: left; } }
        @media (max-width: 575.98px) { .pit-queue-item { grid-template-columns: 44px minmax(0, 1fr); } .pit-queue-result { grid-column: 1 / -1; text-align: left; } }
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
                                <h1 class="page-heading text-dark fw-bold fs-3 mb-1">PIT / Process Image Tool</h1>
                                <span class="text-muted">Mesa independiente para cargar, optimizar y preparar imagenes reutilizables.</span>
                            </div>
                            <div class="d-flex gap-2 flex-wrap">
                                <button class="btn btn-primary" type="button" id="pit_tab_editor"><i class="bi bi-sliders"></i> Editor</button>
                                <button class="btn btn-light" type="button" id="pit_tab_biblioteca"><i class="bi bi-images"></i> Biblioteca</button>
                                <button class="btn btn-light" type="button" id="pit_media_recargar_top"><i class="bi bi-arrow-clockwise"></i> Actualizar</button>
                            </div>
                        </div>
                    </div>
                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <div class="alert alert-info d-flex align-items-start gap-3">
                                <i class="bi bi-images fs-2"></i>
                                <div>
                                    <div class="fw-bold">PIT nace independiente</div>
                                    <div>Primero se prepara la imagen y queda en biblioteca. Despues se agregaran adaptadores para asignarla a producto, CMS, marca, categoria u otros modulos.</div>
                                </div>
                            </div>

                            <div class="pit-workspace" id="pit_workspace_editor">
                                <div class="pit-tabbar mb-5">
                                    <button class="btn btn-sm btn-primary" type="button" data-pit-view="editor"><i class="bi bi-sliders"></i> Editor PIT</button>
                                    <button class="btn btn-sm btn-light" type="button" data-pit-view="biblioteca"><i class="bi bi-images"></i> Ver biblioteca</button>
                                </div>
                                <div class="row g-5">
                                    <div class="col-12">
                                    <div class="pit-panel p-5" id="pit_media_alta_panel" hidden>
                                        <h3 class="fw-bold mb-1">Procesar y cargar</h3>
                                        <div class="text-muted fs-7 mb-4">JPG, PNG, WebP, GIF, AVIF o ICO. Maximo final: 2 MB por imagen.</div>
                                        <div class="row g-4 align-items-start">
                                            <div class="col-xl-4">
                                                <div class="pit-drop h-100">
                                                    <label class="form-label fw-bold" for="pit_media_archivo">Archivos</label>
                                                    <input class="form-control" type="file" id="pit_media_archivo" accept=".jpg,.jpeg,.png,.webp,.gif,.avif,.ico" multiple>
                                                    <label class="form-check form-check-custom form-check-solid mt-4">
                                                        <input class="form-check-input" type="checkbox" id="pit_media_optimizar" checked>
                                                        <span class="form-check-label">Optimizar al subir</span>
                                                    </label>
                                                    <div class="text-muted fs-8 mt-2">El original no se modifica. GIF, AVIF e ICO se conservan originales salvo que los reemplaces con otro archivo.</div>
                                                </div>
                                            </div>
                                            <div class="col-xl-8">
                                                <div class="row g-3">
                                            <div class="col-md-3">
                                                <label class="form-label" for="pit_media_formato_salida">Formato de salida</label>
                                                <select class="form-select form-select-solid" id="pit_media_formato_salida">
                                                    <option value="original">Conservar formato</option>
                                                    <option value="webp">WebP</option>
                                                    <option value="png">PNG</option>
                                                    <option value="jpg">JPG</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label" for="pit_media_calidad">Calidad</label>
                                                <input class="form-range" type="range" min="55" max="95" step="1" value="82" id="pit_media_calidad">
                                                <div class="text-muted fs-8" id="pit_media_calidad_label">82%</div>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label" for="pit_media_max_ancho">Ancho maximo</label>
                                                <input class="form-control form-control-solid" id="pit_media_max_ancho" type="number" min="1" max="6000" step="1" placeholder="2560">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label" for="pit_media_max_alto">Alto maximo</label>
                                                <input class="form-control form-control-solid" id="pit_media_max_alto" type="number" min="1" max="6000" step="1" placeholder="2560">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label" for="pit_media_aspecto_salida">Orientacion</label>
                                                <select class="form-select form-select-solid" id="pit_media_aspecto_salida">
                                                    <option value="original">Original</option>
                                                    <option value="1:1">Cuadrada 1:1</option>
                                                    <option value="4:3">Horizontal 4:3</option>
                                                    <option value="16:9">Horizontal 16:9</option>
                                                    <option value="3:4">Vertical 3:4</option>
                                                    <option value="9:16">Vertical 9:16</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label" for="pit_media_ajuste_salida">Encuadre</label>
                                                <select class="form-select form-select-solid" id="pit_media_ajuste_salida">
                                                    <option value="contain">Encajar completa</option>
                                                    <option value="cover">Recortar al formato</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label" for="pit_media_zoom_salida">Zoom</label>
                                                <input class="form-range" type="range" min="1" max="3" step="0.05" value="1" id="pit_media_zoom_salida">
                                                <div class="text-muted fs-8" id="pit_media_zoom_label">100%</div>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label" for="pit_media_fondo_salida">Fondo</label>
                                                <select class="form-select form-select-solid" id="pit_media_fondo_salida">
                                                    <option value="transparent">Transparente</option>
                                                    <option value="white">Blanco</option>
                                                    <option value="custom">Color</option>
                                                </select>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label" for="pit_media_fondo_color">Color de fondo</label>
                                                <input class="form-control form-control-color" type="color" value="#ffffff" id="pit_media_fondo_color">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label" for="pit_media_alt">Descripcion accesible opcional</label>
                                                <input class="form-control form-control-solid" id="pit_media_alt" type="text" placeholder="Si lo dejas vacio se usa el nombre del archivo">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label" for="pit_media_nombre_seo">Prefijo SEO opcional</label>
                                                <div class="input-group">
                                                    <input class="form-control form-control-solid" id="pit_media_nombre_seo" maxlength="120" type="text" placeholder="ej. alimento-premium">
                                                    <button class="btn btn-light" type="button" id="pit_media_sugerir_nombre"><i class="bi bi-magic"></i></button>
                                                </div>
                                                <div class="text-muted fs-8 mt-2" id="pit_media_nombre_preview"></div>
                                            </div>
                                            <div class="col-12">
                                                <details>
                                                    <summary class="fw-semibold text-muted">Opciones avanzadas opcionales</summary>
                                                    <div class="row g-3 mt-1">
                                                        <div class="col-md-6">
                                                            <label class="form-label" for="pit_media_uso">Uso sugerido</label>
                                                            <select class="form-select form-select-solid" id="pit_media_uso">
                                                                <option value="general">General</option>
                                                                <option value="producto">Producto</option>
                                                                <option value="categoria">Categoria</option>
                                                                <option value="marca">Marca</option>
                                                                <option value="cms">CMS</option>
                                                                <option value="comercial">Comercial</option>
                                                                <option value="global">Global</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label" for="pit_media_tipo">Tipo sugerido</label>
                                                            <select class="form-select form-select-solid" id="pit_media_tipo">
                                                                <option value="referencia">Referencia</option>
                                                                <option value="portada">Portada</option>
                                                                <option value="galeria">Galeria</option>
                                                                <option value="detalle">Detalle</option>
                                                                <option value="empaque">Empaque</option>
                                                                <option value="banner">Banner</option>
                                                                <option value="hero">Hero</option>
                                                                <option value="thumb">Thumbnail</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </details>
                                            </div>
                                            <div class="col-12 d-flex gap-2">
                                                <button class="btn btn-primary flex-grow-1" type="button" id="pit_media_agregar"><i class="bi bi-cloud-upload"></i> Procesar y subir</button>
                                                <button class="btn btn-light" type="button" id="pit_media_limpiar_lote"><i class="bi bi-x-lg"></i></button>
                                            </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                    <div class="col-12">
                                    <div class="pit-panel p-5">
                                        <h3 class="fw-bold mb-2">Vista previa</h3>
                                        <div id="pit_media_visual" class="text-muted">Selecciona archivos para previsualizar el lote.</div>
                                        <div class="text-muted fs-8 pit-preview-help">Para recorte cuadrado u otra orientacion, selecciona una imagen en la cola y marca el punto importante sobre la vista previa.</div>
                                    </div>
                                </div>

                                    <div class="col-12">
                                        <div class="pit-panel p-5">
                                            <div class="d-flex justify-content-between align-items-start gap-3 mb-4">
                                                <div>
                                                    <h3 class="fw-bold mb-1">Cola de trabajo</h3>
                                                    <span class="text-muted fs-7">Aqui solo ves lo que vas a procesar y subir desde PIT.</span>
                                                </div>
                                                <span class="badge badge-light-primary">Editor</span>
                                            </div>
                                            <div class="alert alert-light-info mb-4">La biblioteca queda separada. Este espacio es para preparar archivos: lote, formato, dimensiones, calidad y subida.</div>
                                            <div id="pit_editor_resumen" class="text-muted">Selecciona imagenes para iniciar.</div>
                                            <div class="pit-queue mt-4" id="pit_media_lote"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="pit-workspace" id="pit_workspace_biblioteca" hidden>
                                <div class="pit-tabbar mb-5">
                                    <button class="btn btn-sm btn-light" type="button" data-pit-view="editor"><i class="bi bi-sliders"></i> Volver al editor</button>
                                    <button class="btn btn-sm btn-primary" type="button" data-pit-view="biblioteca"><i class="bi bi-images"></i> Biblioteca PIT</button>
                                </div>
                                <div class="row g-5">
                                    <div class="col-12">
                                    <div class="pit-panel p-5 mb-5">
                                        <div id="pit_media_estado" class="alert alert-light-info" role="status" aria-live="polite" tabindex="-1">Cargando PIT...</div>
                                        <div id="pit_media_preflight" class="text-muted fs-7 mb-4"></div>
                                        <div class="d-flex justify-content-between align-items-end flex-wrap gap-3 mb-4">
                                            <div>
                                                <h3 class="fw-bold mb-1">Biblioteca PIT</h3>
                                                <span class="text-muted fs-7" id="pit_media_resumen">Imagenes listas para reutilizar.</span>
                                            </div>
                                            <div class="d-flex flex-wrap gap-2">
                                                <input class="form-control form-control-sm form-control-solid w-200px" id="pit_media_buscar" type="search" placeholder="Buscar imagen">
                                                <select class="form-select form-select-sm form-select-solid w-150px" id="pit_media_filtro_uso">
                                                    <option value="">Todos</option>
                                                    <option value="producto">Producto</option>
                                                    <option value="categoria">Categoria</option>
                                                    <option value="marca">Marca</option>
                                                    <option value="cms">CMS</option>
                                                    <option value="comercial">Comercial</option>
                                                    <option value="general">General</option>
                                                </select>
                                                <select class="form-select form-select-sm form-select-solid w-180px" id="pit_media_orden">
                                                    <option value="peso_desc">Mayor peso</option>
                                                    <option value="recientes">Recientes</option>
                                                    <option value="nombre">Nombre</option>
                                                </select>
                                                <button class="btn btn-sm btn-light" id="pit_media_recargar" type="button"><i class="bi bi-arrow-clockwise"></i></button>
                                            </div>
                                        </div>
                                        <div class="pit-grid" id="pit_media_biblioteca"></div>
                                    </div>

                                    <div class="pit-panel p-5">
                                        <div class="d-flex justify-content-between align-items-start gap-3 mb-4">
                                            <div>
                                                <h3 class="fw-bold mb-1">Detalle y referencia</h3>
                                                <span class="text-muted fs-7">Copia la referencia o revisa si ya tiene usos guardados.</span>
                                            </div>
                                            <span class="badge badge-light-primary">PIT</span>
                                        </div>
                                        <div id="pit_media_detalle"></div>
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
    window.ERP_CSRF_TOKEN = "<?= htmlspecialchars(Sesionseguridad::csrfToken(), ENT_QUOTES, 'UTF-8') ?>";
</script>
<script src="/assets/js/custom/apps/erp/cms/media_tools.js?v=20260925-media-acceso2"></script>
<script src="/assets/js/custom/apps/erp/pit/index.js?v=20260930-pit-editor8"></script>
</body>
</html>
