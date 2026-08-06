<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../../">
    <title>Fabricacion de peceras</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css">
    <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css">
    <style>
        .peceras-toolbar {
            gap: .75rem;
        }
        .peceras-numero {
            min-width: 92px;
        }
        .peceras-tabla input,
        .peceras-tabla select {
            min-width: 92px;
        }
        .peceras-tabla .peceras-pieza-nombre {
            min-width: 150px;
        }
        .peceras-kpi {
            border: 1px solid var(--bs-border-color);
            border-radius: 8px;
            padding: 1rem;
            height: 100%;
        }
        .peceras-print {
            display: none;
        }
        @media print {
            body * {
                visibility: hidden;
            }
            #peceras_print_area,
            #peceras_print_area * {
                visibility: visible;
            }
            #peceras_print_area {
                display: block;
                position: absolute;
                inset: 0;
                padding: 20px;
            }
        }
    </style>
</head>
<body id="kt_app_body" data-kt-app-layout="dark-sidebar" data-kt-app-header-fixed="true" data-kt-app-sidebar-enabled="true" data-kt-app-sidebar-fixed="true" class="app-default">
<div class="d-flex flex-column flex-root app-root" id="kt_app_root">
    <div class="app-page flex-column flex-column-fluid" id="kt_app_page">
        <?= include_once '../app/vistas/includes/header/header.php'; ?>
        <div class="app-wrapper flex-column flex-row-fluid" id="kt_app_wrapper">
            <?= include_once '../app/vistas/includes/header/sidebar.php'; ?>
            <main class="app-main flex-column flex-row-fluid" id="kt_app_main">
                <div class="d-flex flex-column flex-column-fluid">
                    <div class="app-toolbar py-3 py-lg-6">
                        <div class="app-container container-fluid d-flex flex-stack flex-wrap gap-3">
                            <div>
                                <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Fabricacion de peceras</h1>
                                <span class="text-muted">Calculadora de cortes de vidrio para preparar pedido a proveedor</span>
                            </div>
                            <div class="d-flex flex-wrap peceras-toolbar">
                                <button class="btn btn-light" type="button" id="peceras_nuevo"><i class="bi bi-file-earmark-plus"></i> Nuevo</button>
                                <button class="btn btn-light-primary" type="button" id="peceras_guardar_local"><i class="bi bi-save"></i> Guardar borrador</button>
                                <a class="btn btn-light" href="/produccion/peceras_perfiles"><i class="bi bi-collection"></i> Perfiles</a>
                                <a class="btn btn-light" href="/produccion/peceras_pedido_vidrio"><i class="bi bi-layers"></i> Pedido multiple</a>
                                <button class="btn btn-light-info" type="button" id="peceras_importar_json"><i class="bi bi-upload"></i> Importar</button>
                                <button class="btn btn-light-info" type="button" id="peceras_exportar_csv"><i class="bi bi-filetype-csv"></i> CSV</button>
                                <button class="btn btn-light-info" type="button" id="peceras_exportar_json"><i class="bi bi-braces"></i> JSON</button>
                                <button class="btn btn-primary" type="button" id="peceras_imprimir"><i class="bi bi-printer"></i> Imprimir pedido</button>
                                <input class="d-none" type="file" id="peceras_importar_json_archivo" accept="application/json,.json">
                            </div>
                        </div>
                    </div>

                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <div class="alert alert-info d-flex align-items-start gap-3 mb-6">
                                <i class="bi bi-info-circle fs-2"></i>
                                <div>
                                    <div class="fw-bold">Primera fase sin escrituras</div>
                                    <div class="text-muted">Esta pantalla calcula piezas y hoja de cortes. No crea compras, no recibe inventario y no registra fabricacion todavia.</div>
                                </div>
                            </div>

                            <div class="card mb-6">
                                <div class="card-header border-0 pt-5">
                                    <div class="card-title"><h2 class="fw-bold fs-4 mb-0">Datos de la pecera</h2></div>
                                    <div class="card-toolbar">
                                        <select class="form-select form-select-solid" id="peceras_borradores" style="min-width:260px">
                                            <option value="">Borradores locales</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="card-body pt-3">
                                    <div class="row g-4 align-items-end">
                                        <div class="col-lg-3 col-md-6">
                                            <label class="form-label fw-semibold">Nombre / referencia</label>
                                            <input class="form-control" id="peceras_nombre" maxlength="120" placeholder="Pecera 60x40x40">
                                        </div>
                                        <div class="col-lg-3 col-md-6">
                                            <label class="form-label fw-semibold">Proveedor</label>
                                            <input class="form-control" id="peceras_proveedor" maxlength="120" placeholder="Proveedor de vidrio">
                                        </div>
                                        <div class="col-lg-2 col-md-4">
                                            <label class="form-label required fw-semibold">Largo cm</label>
                                            <input class="form-control peceras-numero" id="peceras_largo" inputmode="decimal" value="60">
                                        </div>
                                        <div class="col-lg-2 col-md-4">
                                            <label class="form-label required fw-semibold">Fondo cm</label>
                                            <input class="form-control peceras-numero" id="peceras_fondo" inputmode="decimal" value="40">
                                        </div>
                                        <div class="col-lg-2 col-md-4">
                                            <label class="form-label required fw-semibold">Alto cm</label>
                                            <input class="form-control peceras-numero" id="peceras_alto" inputmode="decimal" value="40">
                                        </div>
                                        <div class="col-lg-2 col-md-4">
                                            <label class="form-label fw-semibold">Espesor mm</label>
                                            <select class="form-select" id="peceras_espesor">
                                                <option value="3">3 mm</option>
                                                <option value="4">4 mm</option>
                                                <option value="5" selected>5 mm</option>
                                                <option value="6">6 mm</option>
                                                <option value="9">9 mm</option>
                                                <option value="10">10 mm</option>
                                                <option value="12">12 mm</option>
                                            </select>
                                        </div>
                                        <div class="col-lg-2 col-md-4">
                                            <label class="form-label fw-semibold">Cantidad</label>
                                            <div class="input-group">
                                                <button class="btn btn-light" type="button" data-stepper="peceras_cantidad" data-delta="-1"><i class="bi bi-dash"></i></button>
                                                <input class="form-control text-center" id="peceras_cantidad" inputmode="numeric" value="1">
                                                <button class="btn btn-light" type="button" data-stepper="peceras_cantidad" data-delta="1"><i class="bi bi-plus"></i></button>
                                            </div>
                                        </div>
                                        <div class="col-lg-3 col-md-4">
                                            <label class="form-label fw-semibold">Base</label>
                                            <select class="form-select" id="peceras_base">
                                                <option value="interior" selected>Base interior</option>
                                                <option value="exterior">Base exterior</option>
                                            </select>
                                        </div>
                                        <div class="col-lg-2 col-md-4">
                                            <label class="form-label fw-semibold">Holgura mm</label>
                                            <input class="form-control peceras-numero" id="peceras_holgura" inputmode="decimal" value="2">
                                        </div>
                                        <div class="col-lg-2 col-md-4">
                                            <label class="form-label fw-semibold">Descuento corte mm</label>
                                            <input class="form-control peceras-numero" id="peceras_descuento_corte" inputmode="decimal" value="2">
                                        </div>
                                        <div class="col-lg-3 col-md-4">
                                            <label class="form-label fw-semibold">Refuerzos superiores</label>
                                            <select class="form-select" id="peceras_refuerzos">
                                                <option value="ninguno" selected>Sin refuerzos</option>
                                                <option value="frente_fondo">Frente y fondo</option>
                                                <option value="perimetral">Perimetral</option>
                                            </select>
                                        </div>
                                        <div class="col-lg-2 col-md-4">
                                            <label class="form-label fw-semibold">Ancho refuerzo cm</label>
                                            <input class="form-control peceras-numero" id="peceras_refuerzo_ancho" inputmode="decimal" value="5">
                                        </div>
                                        <div class="col-lg-2 col-md-4">
                                            <label class="form-label fw-semibold">Tapa vidrio</label>
                                            <select class="form-select" id="peceras_tapa">
                                                <option value="no" selected>Sin tapa</option>
                                                <option value="si">Agregar tapa</option>
                                            </select>
                                        </div>
                                        <div class="col-lg-2 col-md-4">
                                            <label class="form-label fw-semibold">Piezas tapa</label>
                                            <div class="input-group">
                                                <button class="btn btn-light" type="button" data-stepper="peceras_tapa_piezas" data-delta="-1"><i class="bi bi-dash"></i></button>
                                                <input class="form-control text-center" id="peceras_tapa_piezas" inputmode="numeric" value="1">
                                                <button class="btn btn-light" type="button" data-stepper="peceras_tapa_piezas" data-delta="1"><i class="bi bi-plus"></i></button>
                                            </div>
                                        </div>
                                        <div class="col-lg-3 col-md-4">
                                            <label class="form-label fw-semibold">Costo vidrio por m2</label>
                                            <input class="form-control peceras-numero" id="peceras_costo_m2" inputmode="decimal" placeholder="0.00">
                                        </div>
                                        <div class="col-lg-2 col-md-4">
                                            <label class="form-label fw-semibold">Corte por pieza</label>
                                            <input class="form-control peceras-numero" id="peceras_costo_corte" inputmode="decimal" placeholder="0.00">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label fw-semibold">Observaciones para proveedor</label>
                                            <textarea class="form-control" id="peceras_observaciones" rows="2" maxlength="700" placeholder="Ej. pulir cantos visibles, entregar separado por medidas, confirmar disponibilidad de espesor."></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-4 mb-6">
                                <div class="col-md-3"><div class="peceras-kpi"><div class="text-muted">Piezas totales</div><div class="fs-2 fw-bold" id="peceras_kpi_piezas">0</div></div></div>
                                <div class="col-md-3"><div class="peceras-kpi"><div class="text-muted">Area vidrio</div><div class="fs-2 fw-bold" id="peceras_kpi_area">0 m2</div></div></div>
                                <div class="col-md-3"><div class="peceras-kpi"><div class="text-muted">Costo estimado</div><div class="fs-2 fw-bold" id="peceras_kpi_costo">$0.00</div></div></div>
                                <div class="col-md-3"><div class="peceras-kpi"><div class="text-muted">Volumen aprox.</div><div class="fs-2 fw-bold" id="peceras_kpi_volumen">0 L</div></div></div>
                            </div>

                            <div class="card">
                                <div class="card-header border-0 pt-5">
                                    <div class="card-title"><h2 class="fw-bold fs-4 mb-0">Piezas de vidrio</h2></div>
                                    <div class="card-toolbar d-flex gap-2">
                                        <button class="btn btn-light" type="button" id="peceras_agregar_pieza"><i class="bi bi-plus-circle"></i> Pieza manual</button>
                                        <button class="btn btn-light-info" type="button" id="peceras_copiar_texto"><i class="bi bi-clipboard"></i> Copiar pedido</button>
                                        <button class="btn btn-light-primary" type="button" id="peceras_copiar_solicitud"><i class="bi bi-cart-plus"></i> Texto para solicitud</button>
                                    </div>
                                </div>
                                <div class="card-body pt-3">
                                    <div class="table-responsive">
                                        <table class="table table-row-dashed align-middle gy-3 peceras-tabla">
                                            <thead>
                                            <tr class="text-muted fw-bold fs-7 text-uppercase">
                                                <th>Pieza</th>
                                                <th style="width:120px">Largo cm</th>
                                                <th style="width:120px">Ancho cm</th>
                                                <th style="width:120px">Espesor</th>
                                                <th style="width:130px">Por pecera</th>
                                                <th style="width:100px">Total</th>
                                                <th style="width:160px">Acabado</th>
                                                <th>Observaciones</th>
                                                <th class="text-end" style="width:70px"></th>
                                            </tr>
                                            </thead>
                                            <tbody id="peceras_piezas"></tbody>
                                        </table>
                                    </div>
                                    <div class="alert alert-warning d-none mt-4" id="peceras_error"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</div>

<section class="peceras-print" id="peceras_print_area"></section>

<script src="assets/plugins/global/plugins.bundle.js"></script>
<script src="assets/js/scripts.bundle.js"></script>
<script src="/assets/js/custom/apps/erp/produccion/peceras.js?v=20260804-2"></script>
</body>
</html>
