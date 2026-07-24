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
      Proposito: MVP visual read-only para validar catalogos comerciales desde Catalogo ERP.
      Impacto: Catalogo ERP/Comercial; selecciona candidatos en navegador sin guardar BD ni generar archivos.
      Contrato: consume `/catalogoerp/catalogos_comerciales_candidatos` con permiso `catalogo.ver`.
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
        .cc-preview-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(210px, 1fr)); gap: 14px; }
        .cc-card { border: 1px solid #dfe3ea; border-radius: 8px; overflow: hidden; background: #fff; min-height: 330px; display: flex; flex-direction: column; }
        .cc-card__media { aspect-ratio: 1 / 1; background: #f5f7fb; display: grid; place-items: center; overflow: hidden; }
        .cc-card__media img { width: 100%; height: 100%; object-fit: cover; }
        .cc-card__body { padding: 12px; display: flex; flex-direction: column; gap: 7px; flex: 1; }
        .cc-card__title { font-weight: 800; font-size: .98rem; line-height: 1.25; color: #181c32; letter-spacing: 0; }
        .cc-card__meta { color: #5e6278; font-size: .78rem; line-height: 1.35; }
        .cc-card__price { font-weight: 800; color: #0f7a5f; font-size: 1.05rem; margin-top: auto; }
        @media (max-width: 1200px) {
            .cc-toolbar { grid-template-columns: repeat(2, minmax(180px, 1fr)); }
            .cc-summary { grid-template-columns: repeat(3, minmax(110px, 1fr)); }
        }
        @media (max-width: 640px) {
            .cc-toolbar { grid-template-columns: 1fr; }
            .cc-summary { grid-template-columns: repeat(2, minmax(110px, 1fr)); }
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
                            <div class="cc-panel p-4 mb-5">
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

                            <div class="row g-5">
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
                                    </div>
                                </div>
                                <div class="col-xl-5">
                                    <div class="cc-panel p-4 h-100">
                                        <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
                                            <h2 class="fs-5 fw-bold mb-0">Seleccion temporal</h2>
                                            <button class="btn btn-sm btn-light-danger" type="button" id="cc_limpiar"><i class="bi bi-trash"></i> Quitar todo</button>
                                        </div>
                                        <div id="cc_seleccion" class="d-flex flex-column gap-3"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="cc-panel p-4 mt-5">
                                <div class="d-flex justify-content-between align-items-center gap-3 mb-4 flex-wrap">
                                    <h2 class="fs-5 fw-bold mb-0">Vista previa</h2>
                                    <div class="d-flex align-items-center gap-3 flex-wrap">
                                        <label class="form-check form-check-sm form-check-custom form-check-solid mb-0">
                                            <input class="form-check-input" type="checkbox" id="cc_mostrar_precio" checked>
                                            <span class="form-check-label">Precio</span>
                                        </label>
                                        <label class="form-check form-check-sm form-check-custom form-check-solid mb-0">
                                            <input class="form-check-input" type="checkbox" id="cc_mostrar_sku">
                                            <span class="form-check-label">SKU</span>
                                        </label>
                                        <label class="form-check form-check-sm form-check-custom form-check-solid mb-0">
                                            <input class="form-check-input" type="checkbox" id="cc_mostrar_disponibilidad">
                                            <span class="form-check-label">Disponibilidad</span>
                                        </label>
                                        <button class="btn btn-light-dark" type="button" onclick="window.print()"><i class="bi bi-printer"></i> Imprimir</button>
                                    </div>
                                </div>
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
<script src="/assets/js/custom/apps/erp/catalogo/catalogos_comerciales.js?v=20260724-orden-local1"></script>
</body>
</html>
