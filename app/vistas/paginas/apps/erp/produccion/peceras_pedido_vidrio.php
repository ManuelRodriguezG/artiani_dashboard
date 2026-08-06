<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../../">
    <title>Pedido multiple de vidrio</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css">
    <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css">
    <style>
        .vidrio-sheet {
            border: 2px solid var(--bs-gray-500);
            background: var(--bs-gray-100);
            position: relative;
            width: 100%;
            max-width: 520px;
            min-height: 220px;
            overflow: hidden;
            border-radius: 6px;
        }
        .vidrio-piece {
            position: absolute;
            border: 1px solid rgba(0, 0, 0, .28);
            background: rgba(62, 151, 255, .2);
            color: var(--bs-gray-900);
            font-size: 10px;
            line-height: 1.1;
            padding: 3px;
            overflow: hidden;
            border-radius: 3px;
        }
        .vidrio-piece:nth-child(4n+1) { background: rgba(80, 205, 137, .24); }
        .vidrio-piece:nth-child(4n+2) { background: rgba(255, 199, 0, .28); }
        .vidrio-piece:nth-child(4n+3) { background: rgba(241, 65, 108, .18); }
        .vidrio-piece:nth-child(4n+4) { background: rgba(114, 57, 234, .18); }
        .vidrio-band {
            position: absolute;
            top: 0;
            bottom: 0;
            border-right: 2px dashed rgba(0, 0, 0, .35);
            background: rgba(255, 255, 255, .32);
        }
    </style>
</head>
<body id="kt_app_body" data-kt-app-layout="dark-sidebar" data-kt-app-header-fixed="true" data-kt-app-sidebar-enabled="true" data-kt-app-sidebar-fixed="true" class="app-default">
<div class="d-flex flex-column flex-root app-root">
    <div class="app-page flex-column flex-column-fluid">
        <?= include_once '../app/vistas/includes/header/header.php'; ?>
        <div class="app-wrapper flex-column flex-row-fluid">
            <?= include_once '../app/vistas/includes/header/sidebar.php'; ?>
            <main class="app-main flex-column flex-row-fluid">
                <div class="app-toolbar py-3 py-lg-6">
                    <div class="app-container container-fluid d-flex flex-stack flex-wrap gap-3">
                        <div>
                            <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Pedido multiple de vidrio</h1>
                            <span class="text-muted">Combina varios perfiles de peceras y estima hojas requeridas</span>
                        </div>
                        <div class="d-flex gap-2">
                            <a class="btn btn-light" href="/produccion/peceras_perfiles"><i class="bi bi-collection"></i> Perfiles</a>
                            <button class="btn btn-light-info" type="button" id="peceras_pedido_copiar"><i class="bi bi-clipboard"></i> Copiar pedido</button>
                            <button class="btn btn-primary" type="button" id="peceras_pedido_csv"><i class="bi bi-filetype-csv"></i> CSV</button>
                        </div>
                    </div>
                </div>
                <div class="app-content flex-column-fluid">
                    <div class="app-container container-fluid">
                        <div class="alert alert-warning d-flex align-items-start gap-3 mb-6">
                            <i class="bi bi-rulers fs-2"></i>
                            <div>
                                <div class="fw-bold">Acomodo configurable</div>
                                <div class="text-muted">Puedes usar estimacion por area o acomodo por filas con rotacion. El acomodo por filas es una heuristica operativa, no un nesting industrial perfecto.</div>
                            </div>
                        </div>

                        <div class="card mb-6">
                            <div class="card-body">
                                <div class="row g-4 align-items-end">
                                    <div class="col-lg-3">
                                        <label class="form-label fw-semibold">Perfil guardado</label>
                                        <select class="form-select" id="peceras_pedido_perfil"></select>
                                    </div>
                                    <div class="col-lg-2">
                                        <label class="form-label fw-semibold">Cantidad</label>
                                        <input class="form-control text-center" id="peceras_pedido_cantidad" inputmode="numeric" value="1">
                                    </div>
                                    <div class="col-lg-2">
                                        <label class="form-label fw-semibold">Hoja largo cm</label>
                                        <input class="form-control" id="peceras_pedido_hoja_largo" inputmode="decimal" value="260">
                                    </div>
                                    <div class="col-lg-2">
                                        <label class="form-label fw-semibold">Hoja ancho cm</label>
                                        <input class="form-control" id="peceras_pedido_hoja_ancho" inputmode="decimal" value="180">
                                    </div>
                                    <div class="col-lg-2">
                                        <label class="form-label fw-semibold">Tipo acomodo</label>
                                        <select class="form-select" id="peceras_pedido_acomodo">
                                            <option value="columnas_ancho" selected>Bandas corte ancho</option>
                                            <option value="filas_rotacion">Filas con rotacion</option>
                                            <option value="area">Solo area + merma</option>
                                        </select>
                                    </div>
                                    <div class="col-lg-1">
                                        <label class="form-label fw-semibold">Merma %</label>
                                        <input class="form-control" id="peceras_pedido_merma" inputmode="decimal" value="12">
                                    </div>
                                    <div class="col-lg-1">
                                        <label class="form-label fw-semibold">Separacion mm</label>
                                        <input class="form-control" id="peceras_pedido_separacion" inputmode="decimal" value="3">
                                    </div>
                                    <div class="col-lg-1 d-grid">
                                        <button class="btn btn-primary" type="button" id="peceras_pedido_agregar"><i class="bi bi-plus-lg"></i></button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card mb-6">
                            <div class="card-header border-0 pt-5">
                                <h2 class="fw-bold fs-4 mb-0">Tipos de hoja detectados</h2>
                            </div>
                            <div class="card-body pt-3">
                                <div class="text-muted mb-4">El sistema agrupa por espesor y permite ajustar largo/ancho/merma por cada tipo de hoja.</div>
                                <div class="table-responsive">
                                    <table class="table table-row-dashed align-middle gy-3">
                                        <thead><tr class="text-muted fw-bold fs-7 text-uppercase"><th>Espesor</th><th>Hoja largo cm</th><th>Hoja ancho cm</th><th>Merma %</th><th>Separacion mm</th></tr></thead>
                                        <tbody id="peceras_pedido_tipos_hoja"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="row g-4 mb-6">
                            <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted">Perfiles</div><div class="fs-2 fw-bold" id="peceras_pedido_kpi_perfiles">0</div></div></div></div>
                            <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted">Piezas</div><div class="fs-2 fw-bold" id="peceras_pedido_kpi_piezas">0</div></div></div></div>
                            <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted">Area vidrio</div><div class="fs-2 fw-bold" id="peceras_pedido_kpi_area">0 m2</div></div></div></div>
                            <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted">Hojas estimadas</div><div class="fs-2 fw-bold" id="peceras_pedido_kpi_hojas">0</div></div></div></div>
                        </div>

                        <div class="row g-6">
                            <div class="col-xl-5">
                                <div class="card h-100">
                                    <div class="card-header border-0 pt-5"><h2 class="fw-bold fs-4 mb-0">Perfiles del pedido</h2></div>
                                    <div class="card-body pt-3" id="peceras_pedido_items"></div>
                                </div>
                            </div>
                            <div class="col-xl-7">
                                <div class="card h-100">
                                    <div class="card-header border-0 pt-5"><h2 class="fw-bold fs-4 mb-0">Resumen por hoja / espesor</h2></div>
                                    <div class="card-body pt-3">
                                        <div class="table-responsive">
                                            <table class="table table-row-dashed align-middle gy-3">
                                                <thead><tr class="text-muted fw-bold fs-7 text-uppercase"><th>Espesor</th><th>Piezas</th><th>Area</th><th>Acomodo</th><th>Hojas</th><th>Alertas</th></tr></thead>
                                                <tbody id="peceras_pedido_hojas"></tbody>
                                            </table>
                                        </div>
                                        <div class="mt-4" id="peceras_pedido_acomodo_detalle"></div>
                                        <div class="separator my-5"></div>
                                        <div>
                                            <h3 class="fw-bold fs-5 mb-1">Acomodo visual</h3>
                                            <div class="text-muted mb-4">En `Bandas corte ancho`, cada banda alinea una linea recta que cruza el lado angosto de la hoja.</div>
                                            <div class="d-flex flex-column gap-6" id="peceras_pedido_acomodo_visual"></div>
                                        </div>
                                        <div class="separator my-5"></div>
                                        <div class="table-responsive">
                                            <table class="table table-row-dashed align-middle gy-3">
                                                <thead><tr class="text-muted fw-bold fs-7 text-uppercase"><th>Perfil</th><th>Pieza</th><th>Medida</th><th>Espesor</th><th>Total</th></tr></thead>
                                                <tbody id="peceras_pedido_piezas"></tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</div>
<script src="assets/plugins/global/plugins.bundle.js"></script>
<script src="assets/js/scripts.bundle.js"></script>
<script src="/assets/js/custom/apps/erp/produccion/peceras_pedido_vidrio.js?v=20260804-6"></script>
</body>
</html>
