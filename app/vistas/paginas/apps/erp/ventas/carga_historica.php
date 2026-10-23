<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../">
    <title>Carga historica de ventas</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css">
    <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css">
    <!--
      IA: Codex GPT-5
      Fecha: 2026-10-20
      Proposito: capturar ventas pasadas separadas del POS tiempo real y generar acciones de regularizacion.
      Impacto: Ventas/POS; permite multiples pagos documentales y no afecta caja ni inventario, pero prepara alertas hacia Inventario/Catalogo/Compras.
      Contrato: el guardado real requiere token backend; la prevalidacion no escribe BD.
    -->
    <style>
        .vh-card { border: 1px solid #e6e8ee; border-radius: 8px; background: #fff; }
        .vh-products { min-height: 290px; max-height: 420px; overflow: auto; }
        .vh-row { border: 1px solid #edf0f5; border-radius: 8px; padding: 12px; margin-bottom: 10px; }
        .vh-row:hover { border-color: #c7dcff; background: #fbfdff; }
        .vh-table-wrap { overflow-x: auto; }
        .vh-table { min-width: 980px; }
        .vh-product-title { white-space: normal; line-height: 1.25; }
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
                                <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Carga historica de ventas</h1>
                                <span class="text-muted">Registra ventas pasadas para reportes y crea acciones de regularizacion sin afectar caja ni inventario</span>
                                <div class="d-flex flex-wrap gap-2 mt-2">
                                    <span class="badge badge-light-primary">Historico</span>
                                    <span class="badge badge-light-warning">No mueve inventario</span>
                                    <span class="badge badge-light-info">Acciones por partida</span>
                                </div>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <a class="btn btn-light" href="/ventas/mostrar"><i class="bi bi-receipt"></i> Ventas</a>
                                <a class="btn btn-light-primary" href="/ventas/reportes"><i class="bi bi-bar-chart"></i> Reportes</a>
                                <a class="btn btn-primary" href="/ventas/pos"><i class="bi bi-shop-window"></i> POS</a>
                            </div>
                        </div>
                    </div>
                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <div id="vh_alerta" class="mb-4"></div>
                            <div class="row g-4">
                                <div class="col-xl-4">
                                    <div class="vh-card p-5 mb-4">
                                        <div class="fw-bold fs-4 mb-4">Datos de la venta</div>
                                        <div class="row g-3">
                                            <div class="col-md-6 col-xl-12">
                                                <label class="form-label">Fecha real</label>
                                                <input class="form-control form-control-solid" type="datetime-local" id="vh_fecha">
                                            </div>
                                            <div class="col-md-6 col-xl-12">
                                                <label class="form-label">Almacen/sucursal referencia</label>
                                                <select class="form-select form-select-solid" id="vh_almacen"></select>
                                            </div>
                                            <div class="col-md-6 col-xl-12">
                                                <label class="form-label">Cliente</label>
                                                <input class="form-control form-control-solid" id="vh_cliente" placeholder="Cliente mostrador / nombre opcional">
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label">Observaciones</label>
                                                <textarea class="form-control form-control-solid" id="vh_observaciones" rows="3" placeholder="Ej. venta capturada desde nota, WhatsApp o libreta"></textarea>
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label">Token para guardar</label>
                                                <input class="form-control form-control-solid" id="vh_token" placeholder="VENTAS_HISTORICAS_ACCIONES_REAL">
                                                <div class="form-text">Solo se requiere al guardar real. Prevalidar no escribe datos.</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="vh-card p-5">
                                        <div class="fw-bold fs-4 mb-3">Agregar producto</div>
                                        <div class="input-group mb-3">
                                            <input class="form-control form-control-solid" id="vh_buscar" placeholder="Buscar SKU, codigo o nombre">
                                            <button class="btn btn-light-primary" type="button" id="vh_btn_buscar"><i class="bi bi-search"></i></button>
                                        </div>
                                        <div id="vh_resultados" class="vh-products"></div>
                                        <div class="separator my-5"></div>
                                        <div class="fw-bold mb-2">Producto no encontrado</div>
                                        <input class="form-control form-control-solid mb-2" id="vh_rapida_desc" placeholder="Descripcion detallada">
                                        <div class="row g-2">
                                            <div class="col-6"><input class="form-control form-control-solid text-end" id="vh_rapida_cantidad" type="number" min="0" step="0.001" value="1" placeholder="Cantidad"></div>
                                            <div class="col-6"><input class="form-control form-control-solid text-end" id="vh_rapida_precio" type="number" min="0" step="0.01" placeholder="Precio"></div>
                                        </div>
                                        <button class="btn btn-light-warning w-100 mt-3" type="button" id="vh_btn_rapida"><i class="bi bi-lightning-charge"></i> Agregar por clasificar</button>
                                    </div>
                                </div>
                                <div class="col-xl-8">
                                    <div class="vh-card p-5">
                                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                                            <div>
                                                <div class="fw-bold fs-4">Partidas historicas</div>
                                                <div class="text-muted fs-7">Elige una accion por partida solo cuando quieras generar alerta operativa.</div>
                                            </div>
                                            <div class="d-flex flex-wrap gap-2">
                                                <button class="btn btn-light-primary" type="button" id="vh_btn_prevalidar"><i class="bi bi-clipboard-check"></i> Prevalidar</button>
                                                <button class="btn btn-primary" type="button" id="vh_btn_guardar"><i class="bi bi-check2-circle"></i> Guardar historica</button>
                                            </div>
                                        </div>
                                        <div class="vh-table-wrap">
                                            <table class="table align-middle table-row-dashed vh-table">
                                                <thead>
                                                    <tr class="text-muted fw-bold fs-8 text-uppercase">
                                                        <th class="w-60px">Conjunto</th>
                                                        <th>Producto</th>
                                                        <th class="w-125px">Almacen</th>
                                                        <th class="w-100px text-end">Cantidad</th>
                                                        <th class="w-115px text-end">Precio</th>
                                                        <th class="w-130px text-end">Importe</th>
                                                        <th class="w-210px">Accion</th>
                                                        <th class="w-60px"></th>
                                                    </tr>
                                                </thead>
                                                <tbody id="vh_partidas">
                                                    <tr><td colspan="8" class="text-center text-muted py-10">Agrega productos para capturar la venta historica.</td></tr>
                                                </tbody>
                                            </table>
                                        </div>
                                        <div class="row g-4 mt-2">
                                            <div class="col-lg-7">
                                                <div id="vh_resultado" class="alert alert-light-info py-3 mb-0">Prevalida antes de guardar para revisar acciones y totales.</div>
                                            </div>
                                            <div class="col-lg-5">
                                                <div class="border rounded p-4">
                                                    <div class="d-flex justify-content-between mb-2"><span class="text-muted">Subtotal</span><span class="fw-bold" id="vh_subtotal">$0.00</span></div>
                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                        <span class="text-muted">Descuento</span>
                                                        <input class="form-control form-control-sm text-end w-125px" id="vh_descuento" type="number" min="0" step="0.01" value="0">
                                                    </div>
                                                    <div class="separator my-3"></div>
                                                    <div class="fw-bold mb-2">Precio conjunto historico</div>
                                                    <div class="text-muted fs-8 mb-2">Marca las partidas del conjunto y captura total o porcentaje. Las no seleccionadas no cambian.</div>
                                                    <div class="row g-2 mb-2">
                                                        <div class="col-6"><input class="form-control form-control-sm text-end" id="vh_paquete_total" type="number" min="0" step="0.01" placeholder="Total conjunto"></div>
                                                        <div class="col-6"><input class="form-control form-control-sm text-end" id="vh_paquete_porcentaje" type="number" min="0" max="100" step="0.01" placeholder="Descuento %"></div>
                                                    </div>
                                                    <div class="form-text mb-2" id="vh_paquete_regla">Captura total fijo o porcentaje. Si capturas ambos, se usara el total fijo.</div>
                                                    <button class="btn btn-sm btn-light-warning w-100 mb-2" type="button" id="vh_paquete_aplicar"><i class="bi bi-box-seam"></i> Prevalidar conjunto</button>
                                                    <div class="d-flex justify-content-between mb-2"><span class="text-muted">Subtotal conjunto</span><span class="fw-bold" id="vh_paquete_subtotal">$0.00</span></div>
                                                    <div class="d-flex justify-content-between mb-2"><span class="text-muted">Descuento conjunto</span><span class="fw-bold" id="vh_paquete_descuento">$0.00</span></div>
                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                        <span class="text-muted">Total pagado</span>
                                                        <span class="fw-bold" id="vh_pagado">$0.00</span>
                                                    </div>
                                                    <div class="separator my-3"></div>
                                                    <div class="d-flex justify-content-between fs-4"><span class="fw-bold">Total</span><span class="fw-bold" id="vh_total">$0.00</span></div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="border rounded p-4 mt-4">
                                            <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                                                <div>
                                                    <div class="fw-bold">Métodos de pago documentales</div>
                                                    <div class="text-muted fs-7">Registra uno o varios métodos sin afectar caja.</div>
                                                </div>
                                                <button class="btn btn-sm btn-light-primary" type="button" id="vh_pago_agregar"><i class="bi bi-plus-lg"></i> Agregar método</button>
                                            </div>
                                            <div class="table-responsive">
                                                <table class="table table-sm align-middle mb-0">
                                                    <thead>
                                                        <tr class="text-muted fw-bold fs-8 text-uppercase">
                                                            <th>Método</th>
                                                            <th>Referencia</th>
                                                            <th class="text-end">Monto</th>
                                                            <th class="w-40px"></th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="vh_pagos">
                                                        <tr><td colspan="4" class="text-muted fs-7">Agrega un método si la venta tuvo pago.</td></tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                            <div class="form-text">El total pagado se calcula con los montos de esta tabla.</div>
                                        </div>
                                    </div>
                                    <div class="alert alert-light-warning mt-4 mb-0">
                                        <div class="fw-bold">Regla operativa</div>
                                        <div class="fs-7">Esto sirve para capturar rotacion pasada y disparar tareas. No corrige existencias por si solo; Inventario o Catalogo atienden las acciones generadas.</div>
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
<script>window.ERP_CSRF_TOKEN = <?= json_encode(Sesionseguridad::csrfToken(), JSON_UNESCAPED_UNICODE) ?>;</script>
<script src="/assets/plugins/global/plugins.bundle.js"></script>
<script src="/assets/js/scripts.bundle.js"></script>
<script src="/assets/js/custom/apps/erp/ventas/carga_historica.js?v=20261022-precio-conjunto3"></script>
</body>
</html>
