<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../../">
    <title>Catalogos comerciales - Catalogo ERP</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css">
    <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css">
    <!--
      IA: Codex GPT-5 | Fecha: 2026-07-23
      Proposito: editor visual persistente para catalogos comerciales desde Catalogo ERP.
      Impacto: Catalogo ERP/Comercial; guarda borradores en BD sin publicar enlaces ni generar archivos automaticos.
      Contrato: consume endpoints `/catalogoerp/catalogos_comerciales_*` con permisos `catalogo.ver`/`catalogo.editar`.
    -->
    <style>
        .cc-panel { border: 1px solid #e7e9ef; border-radius: 8px; background: #fff; }
        .cc-toolbar { display: grid; grid-template-columns: minmax(220px, 1.2fr) repeat(4, minmax(140px, .7fr)) auto; gap: 10px; align-items: end; }
        .cc-summary { display: grid; grid-template-columns: repeat(6, minmax(110px, 1fr)); gap: 10px; }
        .cc-metric { border: 1px solid #e7e9ef; border-radius: 8px; padding: 12px; background: #fff; min-height: 82px; }
        .cc-metric__value { font-weight: 800; font-size: 1.45rem; line-height: 1; color: #181c32; letter-spacing: 0; }
        .cc-metric__label { color: #7e8299; font-size: .72rem; text-transform: uppercase; font-weight: 700; margin-top: 6px; }
        .cc-thumb { width: 64px; height: 64px; border-radius: 8px; object-fit: cover; background: #f1f3f6; border: 1px solid #e7e9ef; }
        .cc-empty-img { width: 64px; height: 64px; border-radius: 8px; display: grid; place-items: center; background: #f1f3f6; border: 1px dashed #b5b5c3; color: #7e8299; }
        .cc-alerts { display: flex; flex-wrap: wrap; gap: 5px; }
        .cc-preview-grid { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 10px; }
        .cc-export-surface { background: #fff; color: #181c32; }
        .cc-card { border: 1px solid #dfe3ea; border-radius: 8px; overflow: hidden; background: #fff; min-height: 330px; display: flex; flex-direction: column; }
        .cc-card__media { aspect-ratio: 4 / 5; background: #f8fafc; display: grid; place-items: center; overflow: hidden; }
        .cc-card__media img { width: 100%; height: 100%; object-fit: contain; padding: 8px; box-sizing: border-box; }
        .cc-card__body { padding: 7px; display: flex; flex-direction: column; gap: 3px; flex: 1; }
        .cc-card__title { font-weight: 800; font-size: .7rem; line-height: 1.16; color: #181c32; letter-spacing: 0; overflow-wrap: anywhere; }
        .cc-card__meta { color: #5e6278; font-size: .56rem; line-height: 1.18; }
        .cc-card__price { font-weight: 800; color: #0f7a5f; font-size: .74rem; margin-top: auto; }
        .cc-preview-grid--square { grid-template-columns: repeat(5, minmax(0, 1fr)); }
        .cc-preview-grid--square .cc-card { min-height: 300px; }
        .cc-preview-grid--story { grid-template-columns: repeat(5, minmax(0, 1fr)); align-items: start; }
        .cc-preview-grid--story .cc-card { min-height: 310px; }
        .cc-preview-grid--story .cc-card__media { aspect-ratio: 4 / 5; }
        .cc-preview-grid--compact { grid-template-columns: 1fr; gap: 8px; }
        .cc-preview-grid--compact .cc-card { min-height: 136px; flex-direction: row; }
        .cc-preview-grid--compact .cc-card__media { width: 136px; min-width: 136px; aspect-ratio: 1 / 1; }
        .cc-preview-grid--compact .cc-card__body { padding: 10px 12px; gap: 4px; }
        .cc-preview-grid--compact .cc-card__price { margin-top: 4px; }
        .cc-material-form { display: grid; grid-template-columns: minmax(180px, 1fr) minmax(220px, 1.4fr) minmax(180px, 1fr); gap: 10px; }
        .cc-preview-header { border: 1px solid #dfe3ea; border-radius: 8px; padding: 18px; margin-bottom: 14px; background: #fff; }
        .cc-preview-header__title { font-size: 1.45rem; line-height: 1.15; font-weight: 850; color: #181c32; letter-spacing: 0; margin: 0; }
        .cc-preview-header__subtitle { color: #5e6278; font-size: .92rem; margin-top: 6px; }
        .cc-preview-header__cta { color: #0f7a5f; font-size: .9rem; font-weight: 700; margin-top: 10px; }
        .cc-cover-card { border: 1px solid #dfe3ea; border-radius: 8px; min-height: 150px; padding: 18px; margin-bottom: 12px; background: #f8fafc; display: flex; flex-direction: column; justify-content: center; gap: 7px; }
        .cc-cover-card__label { color: #0f7a5f; font-size: .78rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0; }
        .cc-cover-card__title { color: #181c32; font-size: 1.45rem; line-height: 1.08; font-weight: 850; letter-spacing: 0; margin: 0; }
        .cc-cover-card__desc { color: #5e6278; font-size: .88rem; line-height: 1.3; max-width: 760px; }
        .cc-cover-card__cta { color: #181c32; font-size: .82rem; font-weight: 750; }
        .cc-draft-form { display: grid; grid-template-columns: minmax(180px, 1fr) minmax(180px, 1fr) auto auto auto; gap: 10px; align-items: end; }
        .cc-pager { display: flex; align-items: center; justify-content: flex-end; gap: 8px; flex-wrap: wrap; }
        .cc-nav { display: flex; gap: 8px; flex-wrap: wrap; }
        .cc-saved-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 12px; }
        .cc-saved-card { border: 1px solid #e7e9ef; border-radius: 8px; padding: 14px; background: #fff; min-height: 132px; display: flex; flex-direction: column; gap: 10px; }
        .cc-saved-card__title { font-weight: 800; color: #181c32; line-height: 1.25; }
        .cc-saved-card__meta { color: #7e8299; font-size: .82rem; }
        @media print {
            body { background: #fff !important; }
            .app-sidebar, .app-toolbar, .cc-panel:not(.cc-print-area), .cc-summary, #kt_app_header { display: none !important; }
            .app-main, .app-content, .app-container { margin: 0 !important; padding: 0 !important; max-width: none !important; }
            .cc-print-area { border: 0 !important; padding: 0 !important; }
            .cc-card, .cc-preview-header { break-inside: avoid; page-break-inside: avoid; }
        }
        body.cc-capture-mode { background: #fff !important; }
        body.cc-capture-mode .app-sidebar,
        body.cc-capture-mode .app-toolbar,
        body.cc-capture-mode .cc-panel:not(.cc-print-area),
        body.cc-capture-mode .cc-summary,
        body.cc-capture-mode #kt_app_header { display: none !important; }
        body.cc-capture-mode .app-main,
        body.cc-capture-mode .app-content,
        body.cc-capture-mode .app-container { margin: 0 !important; padding: 0 !important; max-width: none !important; }
        body.cc-capture-mode .cc-print-area { border: 0 !important; padding: 16px !important; margin: 0 !important; }
        body.cc-capture-mode .cc-preview-toolbar select,
        body.cc-capture-mode .cc-preview-toolbar label,
        body.cc-capture-mode .cc-preview-toolbar button:not(#cc_modo_captura) { display: none !important; }
        body.cc-capture-mode #cc_modo_captura { position: fixed; top: 12px; right: 12px; z-index: 9999; box-shadow: 0 8px 24px rgba(15, 23, 42, .16); }
        @media (max-width: 1200px) {
            .cc-toolbar { grid-template-columns: repeat(2, minmax(180px, 1fr)); }
            .cc-material-form { grid-template-columns: 1fr; }
            .cc-draft-form { grid-template-columns: 1fr; }
            .cc-summary { grid-template-columns: repeat(3, minmax(110px, 1fr)); }
            .cc-preview-grid,
            .cc-preview-grid--square,
            .cc-preview-grid--story { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 640px) {
            .cc-toolbar { grid-template-columns: 1fr; }
            .cc-summary { grid-template-columns: repeat(2, minmax(110px, 1fr)); }
            .cc-preview-grid,
            .cc-preview-grid--square,
            .cc-preview-grid--story { grid-template-columns: 1fr; }
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
                                <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Catalogos comerciales</h1>
                                <span class="text-muted">Galeria interna desde Catalogo ERP</span>
                            </div>
                            <div class="d-flex gap-2">
                                <a class="btn btn-light-primary" href="/catalogoerp"><i class="bi bi-box-seam"></i> Productos</a>
                                <button class="btn btn-primary" type="button" id="cc_recargar"><i class="bi bi-arrow-clockwise"></i> Recargar</button>
                            </div>
                        </div>
                    </div>
                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <div class="cc-panel p-3 mb-5">
                                <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap">
                                    <div class="cc-nav">
                                        <button class="btn btn-sm btn-primary" type="button" data-cc-view-button="editor"><i class="bi bi-pencil-square"></i> Editor</button>
                                        <button class="btn btn-sm btn-light-primary" type="button" data-cc-view-button="guardados"><i class="bi bi-collection"></i> Guardados</button>
                                        <button class="btn btn-sm btn-light-primary" type="button" data-cc-view-button="preview"><i class="bi bi-eye"></i> Vista previa</button>
                                    </div>
                                    <span class="text-muted fs-8">Trabaja el catalogo por pasos: selecciona, guarda y exporta paginas para redes.</span>
                                </div>
                            </div>

                            <div class="cc-panel p-4 mb-5 cc-view-section d-none" data-cc-view="guardados">
                                <div class="d-flex justify-content-between align-items-center gap-3 mb-3 flex-wrap">
                                    <div>
                                        <h2 class="fs-5 fw-bold mb-1">Catalogos guardados</h2>
                                        <div class="text-muted fs-8">Edita o archiva catalogos comerciales sin entrar al selector del editor.</div>
                                    </div>
                                    <button class="btn btn-sm btn-light-primary" type="button" id="cc_guardados_recargar"><i class="bi bi-arrow-clockwise"></i> Recargar</button>
                                </div>
                                <div class="cc-saved-grid" id="cc_catalogos_guardados_lista"></div>
                            </div>
                            <div class="cc-panel p-4 mb-5 cc-view-section" data-cc-view="editor">
                                <div class="cc-toolbar">
                                    <div>
                                        <label class="form-label fw-semibold">Buscar</label>
                                        <input class="form-control form-control-solid" type="search" id="cc_q" placeholder="Producto, SKU, marca o categoria">
                                    </div>
                                    <div>
                                        <label class="form-label fw-semibold">Precio</label>
                                        <select class="form-select form-select-solid" id="cc_modo_precio">
                                            <option value="indistinto">Indistinto</option>
                                            <option value="con_precio">Con precio</option>
                                            <option value="sin_precio">Sin precio</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="form-label fw-semibold">Imagen</label>
                                        <select class="form-select form-select-solid" id="cc_imagen">
                                            <option value="0">Indistinto</option>
                                            <option value="1">Con imagen</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="form-label fw-semibold">Alertas</label>
                                        <select class="form-select form-select-solid" id="cc_alertas">
                                            <option value="0">Todos</option>
                                            <option value="1">Con alertas</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="form-label fw-semibold">Limite</label>
                                        <select class="form-select form-select-solid" id="cc_limite">
                                            <option value="24">24</option>
                                            <option value="48" selected>48</option>
                                            <option value="96">96</option>
                                            <option value="160">160</option>
                                        </select>
                                    </div>
                                    <button class="btn btn-dark" type="button" id="cc_buscar"><i class="bi bi-search"></i> Buscar</button>
                                </div>
                            </div>

                            <div class="cc-summary mb-5">
                                <div class="cc-metric"><div class="cc-metric__value" id="cc_res_total">0</div><div class="cc-metric__label">Items</div></div>
                                <div class="cc-metric"><div class="cc-metric__value" id="cc_res_alertas">0</div><div class="cc-metric__label">Con alertas</div></div>
                                <div class="cc-metric"><div class="cc-metric__value" id="cc_res_imagen">0</div><div class="cc-metric__label">Sin imagen</div></div>
                                <div class="cc-metric"><div class="cc-metric__value" id="cc_res_precio">0</div><div class="cc-metric__label">Sin precio</div></div>
                                <div class="cc-metric"><div class="cc-metric__value" id="cc_res_paquetes">0</div><div class="cc-metric__label">Paquetes</div></div>
                                <div class="cc-metric"><div class="cc-metric__value" id="cc_res_sel">0</div><div class="cc-metric__label">Seleccionados</div></div>
                            </div>

                            <div class="row g-5 cc-view-section" data-cc-view="editor">
                                <div class="col-xl-7">
                                    <div class="cc-panel p-4 h-100">
                                        <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
                                            <h2 class="fs-5 fw-bold mb-0">Candidatos</h2>
                                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                                <button class="btn btn-sm btn-light-primary" type="button" id="cc_seleccionar_visibles"><i class="bi bi-check2-square"></i> Seleccionar visibles</button>
                                                <button class="btn btn-sm btn-light-danger" type="button" id="cc_quitar_visibles"><i class="bi bi-x-square"></i> Quitar visibles</button>
                                                <span class="badge badge-light-primary" id="cc_estado">Listo</span>
                                            </div>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table align-middle table-row-dashed fs-7 gy-4 mb-0">
                                                <thead>
                                                    <tr class="text-start text-muted fw-bold text-uppercase">
                                                        <th class="w-80px">Imagen</th>
                                                        <th>Producto</th>
                                                        <th>Categoria</th>
                                                        <th>Precio</th>
                                                        <th>Alertas</th>
                                                        <th class="text-end">Accion</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="cc_body"></tbody>
                                            </table>
                                        </div>
                                        <div class="cc-pager mt-4">
                                            <span class="text-muted fs-8" id="cc_cand_paginacion_info">Pagina 1 de 1</span>
                                            <button class="btn btn-icon btn-sm btn-light" type="button" id="cc_cand_prev"><i class="bi bi-chevron-left"></i></button>
                                            <button class="btn btn-icon btn-sm btn-light" type="button" id="cc_cand_next"><i class="bi bi-chevron-right"></i></button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xl-5">
                                    <div class="cc-panel p-4 h-100">
                                        <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
                                            <h2 class="fs-5 fw-bold mb-0">Seleccion temporal</h2>
                                            <div class="d-flex gap-2 flex-wrap">
                                                <button class="btn btn-sm btn-light-primary" type="button" id="cc_copiar_listado"><i class="bi bi-clipboard"></i> Copiar listado</button>
                                                <button class="btn btn-sm btn-light-danger" type="button" id="cc_limpiar"><i class="bi bi-trash"></i> Quitar todo</button>
                                            </div>
                                        </div>
                                        <div id="cc_seleccion" class="d-flex flex-column gap-3"></div>
                                        <div class="cc-pager mt-4">
                                            <span class="text-muted fs-8" id="cc_sel_paginacion_info">Pagina 1 de 1</span>
                                            <button class="btn btn-icon btn-sm btn-light" type="button" id="cc_sel_prev"><i class="bi bi-chevron-left"></i></button>
                                            <button class="btn btn-icon btn-sm btn-light" type="button" id="cc_sel_next"><i class="bi bi-chevron-right"></i></button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="cc-panel p-4 mt-5 cc-view-section" data-cc-view="editor">
                                <div class="mb-4">
                                    <div class="d-flex justify-content-between align-items-center gap-3 mb-3 flex-wrap">
                                        <h2 class="fs-5 fw-bold mb-0">Datos del material</h2>
                                        <button class="btn btn-sm btn-light-danger" type="button" id="cc_reiniciar_borrador"><i class="bi bi-arrow-counterclockwise"></i> Reiniciar borrador</button>
                                    </div>
                                    <div class="cc-material-form">
                                        <div>
                                            <label class="form-label fw-semibold">Titulo</label>
                                            <input class="form-control form-control-solid" id="cc_material_titulo" maxlength="80" placeholder="Catalogo de productos">
                                        </div>
                                        <div>
                                            <label class="form-label fw-semibold">Subtitulo</label>
                                            <input class="form-control form-control-solid" id="cc_material_subtitulo" maxlength="140" placeholder="Promociones, novedades o categoria">
                                        </div>
                                        <div>
                                            <label class="form-label fw-semibold">Contacto / CTA</label>
                                            <input class="form-control form-control-solid" id="cc_material_cta" maxlength="120" placeholder="Pregunta por disponibilidad">
                                        </div>
                                    </div>
                                    <div class="mt-4">
                                        <label class="form-check form-check-sm form-check-custom form-check-solid mb-3">
                                            <input class="form-check-input" type="checkbox" id="cc_portada_activa" checked>
                                            <span class="form-check-label fw-semibold">Mostrar portada antes de productos</span>
                                        </label>
                                        <div class="cc-material-form">
                                            <div>
                                                <label class="form-label fw-semibold">Etiqueta portada</label>
                                                <input class="form-control form-control-solid" id="cc_portada_etiqueta" maxlength="50" placeholder="Catalogo recomendado">
                                            </div>
                                            <div>
                                                <label class="form-label fw-semibold">Descripcion portada</label>
                                                <input class="form-control form-control-solid" id="cc_portada_descripcion" maxlength="180" placeholder="Seleccion de productos para tu proyecto">
                                            </div>
                                            <div>
                                                <label class="form-label fw-semibold">Nota portada</label>
                                                <input class="form-control form-control-solid" id="cc_portada_nota" maxlength="120" placeholder="Precios sujetos a disponibilidad">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="separator my-4"></div>
                                    <div class="cc-draft-form">
                                        <div>
                                            <label class="form-label fw-semibold">Nombre borrador</label>
                                            <input class="form-control form-control-solid" id="cc_borrador_nombre" maxlength="80" placeholder="Ej. Promos acuario">
                                        </div>
                                        <div>
                                            <label class="form-label fw-semibold">Catalogos guardados</label>
                                            <select class="form-select form-select-solid" id="cc_borradores_guardados">
                                                <option value="">Sin catalogos</option>
                                            </select>
                                        </div>
                                        <button class="btn btn-light-primary" type="button" id="cc_guardar_borrador"><i class="bi bi-save"></i> Guardar</button>
                                        <button class="btn btn-light-dark" type="button" id="cc_cargar_borrador"><i class="bi bi-folder2-open"></i> Cargar</button>
                                        <button class="btn btn-light-info" type="button" id="cc_exportar_borrador"><i class="bi bi-download"></i> Exportar JSON</button>
                                        <button class="btn btn-light-success" type="button" id="cc_importar_borrador"><i class="bi bi-upload"></i> Importar JSON</button>
                                        <button class="btn btn-light-danger" type="button" id="cc_eliminar_borrador"><i class="bi bi-archive"></i> Archivar</button>
                                        <input class="d-none" type="file" id="cc_importar_borrador_archivo" accept="application/json,.json">
                                    </div>
                                </div>
                            </div>

                            <div class="cc-panel cc-print-area p-4 mt-5 cc-view-section d-none" data-cc-view="preview">
                                <div class="d-flex justify-content-between align-items-center gap-3 mb-4 flex-wrap">
                                    <h2 class="fs-5 fw-bold mb-0">Vista previa</h2>
                                    <div class="cc-preview-toolbar d-flex align-items-center gap-3 flex-wrap">
                                        <select class="form-select form-select-sm form-select-solid w-170px" id="cc_plantilla">
                                            <option value="square">Cuadrada redes</option>
                                            <option value="story">Vertical redes</option>
                                            <option value="compact">Compacta</option>
                                        </select>
                                        <label class="form-check form-check-sm form-check-custom form-check-solid mb-0">
                                            <input class="form-check-input" type="checkbox" id="cc_mostrar_precio" checked>
                                            <span class="form-check-label">Precio</span>
                                        </label>
                                        <label class="form-check form-check-sm form-check-custom form-check-solid mb-0">
                                            <input class="form-check-input" type="checkbox" id="cc_mostrar_marca" checked>
                                            <span class="form-check-label">Marca</span>
                                        </label>
                                        <label class="form-check form-check-sm form-check-custom form-check-solid mb-0">
                                            <input class="form-check-input" type="checkbox" id="cc_mostrar_categoria">
                                            <span class="form-check-label">Categoria</span>
                                        </label>
                                        <label class="form-check form-check-sm form-check-custom form-check-solid mb-0">
                                            <input class="form-check-input" type="checkbox" id="cc_mostrar_presentacion" checked>
                                            <span class="form-check-label">Presentacion</span>
                                        </label>
                                        <label class="form-check form-check-sm form-check-custom form-check-solid mb-0">
                                            <input class="form-check-input" type="checkbox" id="cc_mostrar_sku">
                                            <span class="form-check-label">SKU</span>
                                        </label>
                                        <label class="form-check form-check-sm form-check-custom form-check-solid mb-0">
                                            <input class="form-check-input" type="checkbox" id="cc_mostrar_disponibilidad">
                                            <span class="form-check-label">Disponibilidad</span>
                                        </label>
                                        <button class="btn btn-light-success" type="button" id="cc_exportar_png"><i class="bi bi-file-earmark-image"></i> Exportar paginas PNG</button>
                                        <button class="btn btn-light-primary" type="button" id="cc_modo_captura"><i class="bi bi-aspect-ratio"></i> Modo captura</button>
                                        <button class="btn btn-light-dark" type="button" onclick="window.print()"><i class="bi bi-printer"></i> Imprimir</button>
                                    </div>
                                </div>
                                <div id="cc_preview_header"></div>
                                <div class="cc-preview-grid" id="cc_preview"></div>
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
<script src="/assets/js/custom/apps/erp/catalogo/catalogos_comerciales.js?v=20260804-grid-5x4-5x5-1"></script>
</body>
</html>





