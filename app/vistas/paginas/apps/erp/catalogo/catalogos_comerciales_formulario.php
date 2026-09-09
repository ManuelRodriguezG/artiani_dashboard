<?php
$ccModo = isset($datos["modo"]) ? $datos["modo"] : "editor";
if (!in_array($ccModo, array("editor", "preview"), true)) {
    $ccModo = "editor";
}
$ccIdCatalogo = isset($datos["id_catalogo_comercial"]) ? intval($datos["id_catalogo_comercial"]) : 0;
$ccEsNuevo = !empty($datos["nuevo"]);
$ccSoloVista = $ccModo === "preview";
$ccTitulo = $ccSoloVista ? "Ver catalogo comercial" : ($ccIdCatalogo > 0 ? "Editar catalogo comercial" : "Nuevo catalogo comercial");
$ccSubtitulo = $ccSoloVista ? "Vista previa y exportacion para redes" : "Informacion del catalogo y seleccion de productos";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../../">
    <title><?= htmlspecialchars($ccTitulo, ENT_QUOTES, 'UTF-8') ?> - Catalogo ERP</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css">
    <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css">
    <!--
      IA: Codex GPT-5 | Fecha: 2026-08-26
      Proposito: formulario independiente para crear, editar y previsualizar catalogos comerciales.
      Impacto: Comercial/Catalogo ERP; separa la captura del listado y ordena informacion antes de productos.
      Contrato: reutiliza endpoints existentes sin DDL, sin costos, sin rentabilidad y sin tocar inventario.
    -->
    <style>
        .cc-panel { border: 1px solid #e7e9ef; border-radius: 8px; background: #fff; }
        .cc-toolbar { display: grid; grid-template-columns: minmax(220px, 1.2fr) minmax(190px, .9fr) repeat(4, minmax(140px, .7fr)) auto; gap: 10px; align-items: end; }
        .cc-summary { display: grid; grid-template-columns: repeat(6, minmax(110px, 1fr)); gap: 10px; }
        .cc-metric { border: 1px solid #e7e9ef; border-radius: 8px; padding: 12px; background: #fff; min-height: 82px; }
        .cc-metric__value { font-weight: 800; font-size: 1.45rem; line-height: 1; color: #181c32; letter-spacing: 0; }
        .cc-metric__label { color: #7e8299; font-size: .72rem; text-transform: uppercase; font-weight: 700; margin-top: 6px; }
        .cc-thumb { width: 64px; height: 64px; border-radius: 8px; object-fit: cover; background: #f1f3f6; border: 1px solid #e7e9ef; }
        .cc-empty-img { width: 64px; height: 64px; border-radius: 8px; display: grid; place-items: center; background: #f1f3f6; border: 1px dashed #b5b5c3; color: #7e8299; }
        .cc-alerts { display: flex; flex-wrap: wrap; gap: 5px; }
        .cc-preview-grid { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 10px; }
        .cc-card { border: 1px solid #dfe3ea; border-radius: 8px; overflow: hidden; background: #fff; min-height: 330px; display: flex; flex-direction: column; }
        .cc-card__media { aspect-ratio: 1 / 1; background: #f8fafc; display: grid; place-items: center; overflow: hidden; }
        .cc-card__media img { width: 100%; height: 100%; object-fit: contain; padding: 6px; box-sizing: border-box; }
        .cc-card__body { padding: 7px; display: flex; flex-direction: column; gap: 3px; flex: 1; }
        .cc-print-area { --cc-font-family: Arial, sans-serif; --cc-title-color: #181c32; --cc-product-color: #181c32; --cc-meta-color: #5e6278; --cc-price-color: #0f7a5f; --cc-title-size: 23px; --cc-product-size: 11px; --cc-meta-size: 9px; --cc-price-size: 13px; font-family: var(--cc-font-family); }
        .cc-card__title { font-weight: 800; font-size: var(--cc-product-size); line-height: 1.16; color: var(--cc-product-color); letter-spacing: 0; overflow-wrap: anywhere; }
        .cc-card__meta { color: var(--cc-meta-color); font-size: var(--cc-meta-size); line-height: 1.18; }
        .cc-card__price { font-weight: 800; color: var(--cc-price-color); font-size: var(--cc-price-size); margin-top: auto; }
        .cc-card__variants { display: flex; flex-wrap: wrap; gap: 4px; margin-top: 4px; }
        .cc-card__variant { border: 1px solid #dfe3ea; border-radius: 6px; color: var(--cc-meta-color); font-size: var(--cc-meta-size); line-height: 1.15; padding: 3px 5px; background: #f8fafc; overflow-wrap: anywhere; }
        .cc-card__variant-images { display: flex; gap: 5px; margin-top: 4px; flex-wrap: wrap; }
        .cc-card__variant-image { width: 42px; height: 42px; border-radius: 6px; border: 1px solid #dfe3ea; object-fit: contain; background: #f8fafc; padding: 2px; box-sizing: border-box; }
        .cc-preview-grid--square, .cc-preview-grid--story { grid-template-columns: repeat(5, minmax(0, 1fr)); }
        .cc-preview-grid--compact { grid-template-columns: 1fr; gap: 8px; }
        .cc-preview-grid--compact .cc-card { min-height: 136px; flex-direction: row; }
        .cc-preview-grid--compact .cc-card__media { width: 136px; min-width: 136px; aspect-ratio: 1 / 1; }
        .cc-preview-grid--compact .cc-card__body { padding: 10px 12px; gap: 4px; }
        .cc-page-preview-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 12px; }
        .cc-page-preview-card { border: 1px solid #dfe3ea; border-radius: 8px; background: #fff; padding: 10px; }
        .cc-page-preview-card canvas { width: 100%; height: auto; display: block; border: 1px solid #edf0f5; border-radius: 6px; background: #fff; }
        .cc-page-preview-card__meta { display: flex; justify-content: space-between; gap: 8px; align-items: center; margin-bottom: 8px; color: #5e6278; font-size: .72rem; font-weight: 700; }
        .cc-form-grid { display: grid; grid-template-columns: minmax(180px, 1fr) minmax(220px, 1.4fr) minmax(180px, 1fr); gap: 10px; }
        .cc-preview-header { border: 1px solid #dfe3ea; border-radius: 8px; padding: 18px; margin-bottom: 14px; background: #fff; }
        .cc-preview-header__title { font-size: var(--cc-title-size); line-height: 1.15; font-weight: 850; color: var(--cc-title-color); letter-spacing: 0; margin: 0; }
        .cc-preview-header__subtitle { color: var(--cc-meta-color); font-size: .92rem; margin-top: 6px; }
        .cc-preview-header__cta { color: var(--cc-price-color); font-size: .9rem; font-weight: 700; margin-top: 10px; }
        .cc-cover-card { border: 1px solid #dfe3ea; border-radius: 8px; min-height: 150px; padding: 18px; margin-bottom: 12px; background: #f8fafc; display: flex; flex-direction: column; justify-content: center; gap: 7px; }
        .cc-cover-card--image { min-height: 320px; position: relative; overflow: hidden; justify-content: flex-end; padding: 0; background: #111827; }
        .cc-cover-card--image img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
        .cc-cover-card__overlay { position: relative; z-index: 1; width: 100%; padding: 18px; background: linear-gradient(180deg, rgba(17,24,39,0), rgba(17,24,39,.82)); color: #fff; }
        .cc-cover-card__overlay .cc-cover-card__title,
        .cc-cover-card__overlay .cc-cover-card__desc,
        .cc-cover-card__overlay .cc-cover-card__cta { color: #fff; }
        .cc-cover-card__logo { width: 84px; height: 84px; object-fit: contain; border-radius: 8px; background: rgba(255,255,255,.92); padding: 8px; margin-bottom: 8px; }
        .cc-cover-card__label { color: #0f7a5f; font-size: .78rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0; }
        .cc-cover-card__title { color: var(--cc-title-color); font-size: var(--cc-title-size); line-height: 1.08; font-weight: 850; letter-spacing: 0; margin: 0; }
        .cc-cover-card__desc { color: var(--cc-meta-color); font-size: .88rem; line-height: 1.3; max-width: 760px; }
        .cc-cover-card__cta { color: var(--cc-price-color); font-size: .82rem; font-weight: 750; }
        .cc-style-grid { display: grid; grid-template-columns: minmax(150px, 1fr) repeat(8, minmax(92px, .65fr)); gap: 10px; align-items: end; margin-bottom: 14px; }
        .cc-color-input { width: 100%; min-height: 38px; padding: 4px; border: 1px solid #e4e6ef; border-radius: 6px; background: #fff; }
        .cc-pager { display: flex; align-items: center; justify-content: flex-end; gap: 8px; flex-wrap: wrap; }
        @media print {
            body { background: #fff !important; }
            .app-sidebar, .app-toolbar, .cc-edit-area, .cc-summary, #kt_app_header { display: none !important; }
            .app-main, .app-content, .app-container { margin: 0 !important; padding: 0 !important; max-width: none !important; }
            .cc-print-area { border: 0 !important; padding: 0 !important; }
            .cc-card, .cc-preview-header { break-inside: avoid; page-break-inside: avoid; }
        }
        body.cc-capture-mode { background: #fff !important; }
        body.cc-capture-mode .app-sidebar,
        body.cc-capture-mode .app-toolbar,
        body.cc-capture-mode .cc-edit-area,
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
            .cc-form-grid { grid-template-columns: 1fr; }
            .cc-summary { grid-template-columns: repeat(3, minmax(110px, 1fr)); }
            .cc-preview-grid, .cc-preview-grid--square, .cc-preview-grid--story { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .cc-style-grid { grid-template-columns: repeat(3, minmax(120px, 1fr)); }
        }
        @media (max-width: 640px) {
            .cc-toolbar { grid-template-columns: 1fr; }
            .cc-summary { grid-template-columns: repeat(2, minmax(110px, 1fr)); }
            .cc-preview-grid, .cc-preview-grid--square, .cc-preview-grid--story { grid-template-columns: 1fr; }
            .cc-style-grid { grid-template-columns: repeat(2, minmax(110px, 1fr)); }
        }
    </style>
</head>
<body id="kt_app_body" data-kt-app-layout="dark-sidebar" data-kt-app-header-fixed="true" data-kt-app-sidebar-enabled="true" data-kt-app-sidebar-fixed="true" class="app-default">
<input type="hidden" id="cc_modo_inicial" value="<?= $ccSoloVista ? 'preview' : 'editor' ?>">
<input type="hidden" id="cc_catalogo_inicial" value="<?= intval($ccIdCatalogo) ?>">
<input type="hidden" id="cc_nuevo_inicial" value="<?= $ccEsNuevo ? 1 : 0 ?>">
<div class="d-flex flex-column flex-root app-root" id="kt_app_root">
    <div class="app-page flex-column flex-column-fluid" id="kt_app_page">
        <?= include_once '../app/vistas/includes/header/header.php'; ?>
        <div class="app-wrapper flex-column flex-row-fluid" id="kt_app_wrapper">
            <?= include_once '../app/vistas/includes/header/sidebar.php'; ?>
            <main class="app-main flex-column flex-row-fluid" id="kt_app_main">
                <div class="d-flex flex-column flex-column-fluid">
                    <div class="app-toolbar py-3 py-lg-5">
                        <div class="app-container container-fluid d-flex flex-stack flex-wrap gap-3">
                            <div>
                                <h1 class="page-heading text-dark fw-bold fs-3 mb-1"><?= htmlspecialchars($ccTitulo, ENT_QUOTES, 'UTF-8') ?></h1>
                                <span class="text-muted"><?= htmlspecialchars($ccSubtitulo, ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <div class="d-flex gap-2">
                                <a class="btn btn-light-primary" href="/catalogoerp/catalogos_comerciales"><i class="bi bi-arrow-left"></i> Catalogos</a>
                                <?php if (!$ccSoloVista): ?>
                                    <button class="btn btn-primary" type="button" id="cc_guardar_borrador"><i class="bi bi-save"></i> Guardar</button>
                                <?php endif; ?>
                                <button class="btn btn-light-primary" type="button" id="cc_recargar"><i class="bi bi-arrow-clockwise"></i> Recargar</button>
                            </div>
                        </div>
                    </div>
                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <div class="cc-summary mb-5">
                                <div class="cc-metric"><div class="cc-metric__value" id="cc_res_total">0</div><div class="cc-metric__label">Candidatos</div></div>
                                <div class="cc-metric"><div class="cc-metric__value" id="cc_res_alertas">0</div><div class="cc-metric__label">Con alertas</div></div>
                                <div class="cc-metric"><div class="cc-metric__value" id="cc_res_imagen">0</div><div class="cc-metric__label">Sin imagen</div></div>
                                <div class="cc-metric"><div class="cc-metric__value" id="cc_res_precio">0</div><div class="cc-metric__label">Sin precio</div></div>
                                <div class="cc-metric"><div class="cc-metric__value" id="cc_res_paquetes">0</div><div class="cc-metric__label">Paquetes</div></div>
                                <div class="cc-metric"><div class="cc-metric__value" id="cc_res_sel">0</div><div class="cc-metric__label">Seleccionados</div></div>
                            </div>

                            <?php if (!$ccSoloVista): ?>
                            <section class="cc-panel p-4 mb-5 cc-edit-area">
                                <div class="d-flex justify-content-between align-items-center gap-3 mb-4 flex-wrap">
                                    <div>
                                        <h2 class="fs-5 fw-bold mb-1">Informacion del catalogo</h2>
                                        <div class="text-muted fs-8">Nombre interno, titulo visible, portada y mensaje de contacto.</div>
                                    </div>
                                    <button class="btn btn-sm btn-light-danger" type="button" id="cc_reiniciar_borrador"><i class="bi bi-arrow-counterclockwise"></i> Nuevo limpio</button>
                                </div>
                                <div class="cc-form-grid mb-4">
                                    <div>
                                        <label class="form-label fw-semibold">Nombre interno</label>
                                        <input class="form-control form-control-solid" id="cc_borrador_nombre" maxlength="80" placeholder="Ej. Acuario panoramicas">
                                    </div>
                                    <div>
                                        <label class="form-label fw-semibold">Titulo para cliente</label>
                                        <input class="form-control form-control-solid" id="cc_material_titulo" maxlength="80" placeholder="Catalogo de productos">
                                    </div>
                                    <div>
                                        <label class="form-label fw-semibold">Contacto / CTA</label>
                                        <input class="form-control form-control-solid" id="cc_material_cta" maxlength="120" placeholder="Pregunta por disponibilidad">
                                    </div>
                                </div>
                                <div class="cc-form-grid">
                                    <div>
                                        <label class="form-label fw-semibold">Subtitulo</label>
                                        <input class="form-control form-control-solid" id="cc_material_subtitulo" maxlength="140" placeholder="Promociones, novedades o categoria">
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
                                <div class="d-flex gap-4 flex-wrap mt-4">
                                    <label class="form-check form-check-sm form-check-custom form-check-solid">
                                        <input class="form-check-input" type="checkbox" id="cc_portada_activa" checked>
                                        <span class="form-check-label fw-semibold">Mostrar portada</span>
                                    </label>
                                    <div class="w-200px">
                                        <select class="form-select form-select-solid form-select-sm" id="cc_portada_tipo">
                                            <option value="plantilla">Plantilla editable</option>
                                            <option value="imagen_completa">Imagen pagina completa</option>
                                        </select>
                                    </div>
                                    <div class="w-250px">
                                        <input class="form-control form-control-solid form-control-sm" id="cc_portada_etiqueta" maxlength="50" placeholder="Etiqueta de portada">
                                    </div>
                                    <div class="w-300px">
                                        <input class="form-control form-control-solid form-control-sm" id="cc_portada_imagen_url" maxlength="255" placeholder="Ruta imagen portada /uploads/...">
                                    </div>
                                    <div class="w-300px">
                                        <div class="input-group input-group-sm">
                                            <input class="form-control form-control-solid" type="file" id="cc_portada_imagen_archivo" accept="image/jpeg,image/png,image/webp,image/gif">
                                            <button class="btn btn-light-primary" type="button" id="cc_portada_imagen_subir"><i class="bi bi-cloud-arrow-up"></i> Cargar portada</button>
                                        </div>
                                    </div>
                                    <div class="w-250px">
                                        <input class="form-control form-control-solid form-control-sm" id="cc_logo_url" maxlength="255" placeholder="Ruta logo /uploads/...">
                                    </div>
                                    <div class="w-300px">
                                        <div class="input-group input-group-sm">
                                            <input class="form-control form-control-solid" type="file" id="cc_logo_archivo" accept="image/jpeg,image/png,image/webp,image/gif">
                                            <button class="btn btn-light-primary" type="button" id="cc_logo_subir"><i class="bi bi-cloud-arrow-up"></i> Cargar logo</button>
                                        </div>
                                    </div>
                                    <div class="w-300px">
                                        <input class="form-control form-control-solid form-control-sm" id="cc_contacto_texto" maxlength="255" placeholder="WhatsApp, redes o sucursal">
                                    </div>
                                    <span class="badge badge-light-primary" id="cc_estado">Listo</span>
                                </div>
                            </section>

                            <section class="cc-panel p-4 mb-5 cc-edit-area">
                                <div class="d-flex justify-content-between align-items-center gap-3 mb-4 flex-wrap">
                                    <div>
                                        <h2 class="fs-5 fw-bold mb-1">Productos del catalogo</h2>
                                        <div class="text-muted fs-8">Selecciona SKUs desde Catalogo ERP; no se fusionan ni modifican productos.</div>
                                    </div>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <button class="btn btn-sm btn-light-primary" type="button" id="cc_seleccionar_visibles"><i class="bi bi-check2-square"></i> Seleccionar visibles</button>
                                        <button class="btn btn-sm btn-light-primary" type="button" id="cc_seleccionar_cargados"><i class="bi bi-plus-square-dotted"></i> Agregar cargados</button>
                                        <button class="btn btn-sm btn-light-danger" type="button" id="cc_quitar_visibles"><i class="bi bi-x-square"></i> Quitar visibles</button>
                                        <button class="btn btn-sm btn-light-danger" type="button" id="cc_quitar_cargados"><i class="bi bi-dash-square-dotted"></i> Quitar cargados</button>
                                    </div>
                                </div>
                                <div class="cc-toolbar mb-5">
                                    <div>
                                        <label class="form-label fw-semibold">Buscar</label>
                                        <input class="form-control form-control-solid" type="search" id="cc_q" placeholder="Producto, SKU, marca o categoria">
                                    </div>
                                    <div>
                                        <label class="form-label fw-semibold">Categoria</label>
                                        <select class="form-select form-select-solid" id="cc_categoria">
                                            <option value="">Todas</option>
                                        </select>
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
                                            <option value="200">200</option>
                                        </select>
                                    </div>
                                    <button class="btn btn-dark" type="button" id="cc_buscar"><i class="bi bi-search"></i> Buscar</button>
                                </div>
                                <div class="row g-5">
                                    <div class="col-xl-7">
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
                                    <div class="col-xl-5">
                                        <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
                                            <h3 class="fs-6 fw-bold mb-0">Seleccionados</h3>
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
                            </section>
                            <?php else: ?>
                                <span class="badge badge-light-primary d-none" id="cc_estado">Listo</span>
                                <input type="hidden" id="cc_borrador_nombre">
                                <input type="hidden" id="cc_material_titulo">
                                <input type="hidden" id="cc_material_subtitulo">
                                <input type="hidden" id="cc_material_cta">
                                <input type="checkbox" class="d-none" id="cc_portada_activa" checked>
                                <input type="hidden" id="cc_portada_tipo">
                                <input type="hidden" id="cc_portada_etiqueta">
                                <input type="hidden" id="cc_portada_imagen_url">
                                <input type="hidden" id="cc_logo_url">
                                <input type="hidden" id="cc_contacto_texto">
                                <input type="hidden" id="cc_portada_descripcion">
                                <input type="hidden" id="cc_portada_nota">
                                <input type="hidden" id="cc_q">
                                <input type="hidden" id="cc_modo_precio" value="indistinto">
                                <input type="hidden" id="cc_imagen" value="0">
                                <input type="hidden" id="cc_alertas" value="0">
                                <input type="hidden" id="cc_limite" value="48">
                                <div id="cc_seleccion" class="d-none"></div>
                            <?php endif; ?>

                            <section class="cc-panel cc-print-area p-4 mb-5">
                                <div class="d-flex justify-content-between align-items-center gap-3 mb-4 flex-wrap">
                                    <div>
                                        <h2 class="fs-5 fw-bold mb-1">Vista previa</h2>
                                        <div class="text-muted fs-8">Revisa el material como saldra para WhatsApp/redes.</div>
                                    </div>
                                    <div class="cc-preview-toolbar d-flex align-items-center gap-3 flex-wrap">
                                        <select class="form-select form-select-sm form-select-solid w-170px" id="cc_plantilla">
                                            <option value="square">Cuadrada redes</option>
                                            <option value="story">Vertical redes</option>
                                            <option value="compact">Compacta</option>
                                        </select>
                                        <select class="form-select form-select-sm form-select-solid w-170px" id="cc_columnas_exportacion">
                                            <option value="2">2 por fila</option>
                                            <option value="3" selected>3 por fila</option>
                                            <option value="4">4 por fila</option>
                                            <option value="5">5 por fila</option>
                                        </select>
                                        <select class="form-select form-select-sm form-select-solid w-160px" id="cc_filas_exportacion">
                                            <option value="auto" selected>Filas auto</option>
                                            <option value="2">2 filas</option>
                                            <option value="3">3 filas</option>
                                            <option value="4">4 filas</option>
                                            <option value="5">5 filas</option>
                                            <option value="6">6 filas</option>
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
                                        <label class="form-check form-check-sm form-check-custom form-check-solid mb-0">
                                            <input class="form-check-input" type="checkbox" id="cc_agrupar_variantes">
                                            <span class="form-check-label">Agrupar variantes</span>
                                        </label>
                                        <button class="btn btn-light-primary" type="button" id="cc_previsualizar_paginas"><i class="bi bi-layout-three-columns"></i> Preview paginas</button>
                                        <button class="btn btn-light-success" type="button" id="cc_exportar_png"><i class="bi bi-file-earmark-image"></i> Exportar paginas PNG</button>
                                        <button class="btn btn-light-primary" type="button" id="cc_modo_captura"><i class="bi bi-aspect-ratio"></i> Modo captura</button>
                                        <button class="btn btn-light-dark" type="button" onclick="window.print()"><i class="bi bi-printer"></i> Imprimir</button>
                                    </div>
                                </div>
                                <div class="cc-style-grid">
                                    <div>
                                        <label class="form-label fw-semibold fs-8">Tipografia</label>
                                        <select class="form-select form-select-sm form-select-solid" id="cc_fuente_visual">
                                            <option value="arial">Arial</option>
                                            <option value="verdana">Verdana</option>
                                            <option value="georgia">Georgia</option>
                                            <option value="trebuchet">Trebuchet</option>
                                            <option value="impacto">Impacto</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="form-label fw-semibold fs-8">Color titulo</label>
                                        <input class="cc-color-input" type="color" id="cc_color_titulo" value="#181c32">
                                    </div>
                                    <div>
                                        <label class="form-label fw-semibold fs-8">Color producto</label>
                                        <input class="cc-color-input" type="color" id="cc_color_producto" value="#181c32">
                                    </div>
                                    <div>
                                        <label class="form-label fw-semibold fs-8">Color datos</label>
                                        <input class="cc-color-input" type="color" id="cc_color_meta" value="#5e6278">
                                    </div>
                                    <div>
                                        <label class="form-label fw-semibold fs-8">Color precio</label>
                                        <input class="cc-color-input" type="color" id="cc_color_precio" value="#0f7a5f">
                                    </div>
                                    <div>
                                        <label class="form-label fw-semibold fs-8">Titulo</label>
                                        <select class="form-select form-select-sm form-select-solid" id="cc_tam_titulo">
                                            <option value="21">21 px</option>
                                            <option value="23" selected>23 px</option>
                                            <option value="26">26 px</option>
                                            <option value="30">30 px</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="form-label fw-semibold fs-8">Producto</label>
                                        <select class="form-select form-select-sm form-select-solid" id="cc_tam_producto">
                                            <option value="10">10 px</option>
                                            <option value="11" selected>11 px</option>
                                            <option value="13">13 px</option>
                                            <option value="15">15 px</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="form-label fw-semibold fs-8">Datos</label>
                                        <select class="form-select form-select-sm form-select-solid" id="cc_tam_meta">
                                            <option value="8">8 px</option>
                                            <option value="9" selected>9 px</option>
                                            <option value="10">10 px</option>
                                            <option value="12">12 px</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="form-label fw-semibold fs-8">Precio</label>
                                        <select class="form-select form-select-sm form-select-solid" id="cc_tam_precio">
                                            <option value="12">12 px</option>
                                            <option value="13" selected>13 px</option>
                                            <option value="15">15 px</option>
                                            <option value="18">18 px</option>
                                        </select>
                                    </div>
                                </div>
                                <div id="cc_preview_header"></div>
                                <div class="cc-preview-grid" id="cc_preview"></div>
                                <div class="mt-5 d-none" id="cc_preview_paginas_wrap">
                                    <div class="d-flex justify-content-between align-items-center gap-3 mb-3 flex-wrap">
                                        <h3 class="fs-6 fw-bold mb-0">Preview de paginas PNG</h3>
                                        <span class="text-muted fs-8" id="cc_preview_paginas_resumen"></span>
                                    </div>
                                    <div class="cc-page-preview-list" id="cc_preview_paginas"></div>
                                </div>
                            </section>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</div>
<script src="assets/plugins/global/plugins.bundle.js"></script>
<script src="assets/js/scripts.bundle.js"></script>
<script src="/assets/js/custom/apps/erp/catalogo/catalogos_comerciales.js?v=20260908-portada-upload-1"></script>
</body>
</html>
