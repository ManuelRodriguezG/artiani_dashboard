<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../">
    <title>Pedidos y apartados POS</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css">
    <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css">
    <!--
      Documentacion IA: Codex GPT-5, 2026-07-02.
      Proposito: separar pedidos/apartados POS del listado general y del cobro inmediato.
      Impacto: prepara reservas, abonos y seguimiento sin escribir BD.
      Contrato: vista operativa; acciones reales requieren simulacion previa y confirmacion explicita.
    -->
    <style>
        .ped-card { border: 1px solid #e6e8ee; border-radius: 8px; background: #fff; }
        .ped-result { max-height: 420px; overflow: auto; }
        .ped-empty { min-height: 220px; border: 1px dashed #d7dbe4; border-radius: 8px; }
        .ped-kpi { min-height: 78px; border: 1px solid #e6e8ee; border-radius: 8px; background: #fff; }
        .ped-total-box { border: 1px solid #d7dbe4; border-radius: 8px; background: #f8fafc; }
        .ped-product-results { max-height: 260px; overflow: auto; border: 1px solid #e6e8ee; border-radius: 8px; }
        .ped-product-row { cursor: pointer; border-bottom: 1px solid #edf0f5; }
        .ped-product-row:last-child { border-bottom: 0; }
        .ped-product-row:hover { background: #f8fafc; }
        .ped-product-img { width: 48px; height: 48px; object-fit: cover; border-radius: 8px; background: #f1f3f6; flex: 0 0 48px; }
        .ped-lines { border: 1px solid #e6e8ee; border-radius: 8px; overflow: hidden; }
        .ped-line-img { width: 36px; height: 36px; object-fit: cover; border-radius: 6px; background: #f1f3f6; }
        .ped-line-empty { min-height: 72px; background: #fbfcfe; }
        .ped-line-summary { min-height: 34px; border: 1px solid #e6e8ee; border-top: 0; border-radius: 0 0 8px 8px; background: #fbfcfe; }
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
                                <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Pedidos y apartados POS</h1>
                                <span class="text-muted">Seguimiento de encargos, anticipos, saldos y entregas</span>
                                <div class="d-flex flex-wrap gap-2 mt-2">
                                    <span class="badge badge-light-primary">Listado operativo</span>
                                    <span class="badge badge-light-warning">Simular antes de guardar</span>
                                    <span class="badge badge-light-danger">Accion real con confirmacion</span>
                                </div>
                            </div>
                            <div class="d-flex gap-2">
                                <a class="btn btn-light" href="/ventas/mostrar"><i class="bi bi-receipt"></i> Ventas</a>
                                <a class="btn btn-light-primary" href="/ventas/caja_turnos"><i class="bi bi-safe"></i> Caja</a>
                                <a class="btn btn-primary" href="/ventas/pos"><i class="bi bi-shop-window"></i> POS</a>
                            </div>
                        </div>
                    </div>
                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <div id="ped_alerta" class="mb-4"></div>
                            <div class="row g-3 mb-4" id="ped_contexto">
                                <div class="col-md-3">
                                    <div class="ped-kpi p-3">
                                        <div class="text-muted fs-8 text-uppercase">Tienda / almacen</div>
                                        <div class="fw-bold fs-6" id="ped_ctx_almacen">-</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="ped-kpi p-3">
                                        <div class="text-muted fs-8 text-uppercase">Caja asignada</div>
                                        <div class="fw-bold fs-6" id="ped_ctx_caja">-</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="ped-kpi p-3">
                                        <div class="text-muted fs-8 text-uppercase">Turno</div>
                                        <div class="fw-bold fs-6" id="ped_ctx_turno">-</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="ped-kpi p-3">
                                        <div class="text-muted fs-8 text-uppercase">Modo actual</div>
                                        <div class="fw-bold fs-6" id="ped_ctx_modo">Operacion</div>
                                    </div>
                                </div>
                            </div>
                            <div class="row g-4">
                                <div class="col-xl-8">
                                    <div class="ped-card p-4">
                                        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
                                            <div>
                                                <div class="fw-bold fs-5">Pedidos/apartados</div>
                                                <div class="text-muted fs-7">Seguimiento y acciones controladas por estado</div>
                                            </div>
                                            <div class="d-flex flex-wrap gap-2">
                                                <select class="form-select form-select-sm form-select-solid w-auto" id="ped_tipo">
                                                    <option value="">Pedidos y apartados</option>
                                                    <option value="pedido">Pedidos</option>
                                                    <option value="apartado">Apartados</option>
                                                </select>
                                                <select class="form-select form-select-sm form-select-solid w-auto" id="ped_estatus">
                                                    <option value="">Todos</option>
                                                    <option value="borrador">Borrador</option>
                                                    <option value="reservado">Reservado</option>
                                                    <option value="pendiente_pago">Pendiente pago</option>
                                                    <option value="pagado">Pagado</option>
                                                    <option value="entregado">Entregado</option>
                                                    <option value="cancelado">Cancelado</option>
                                                </select>
                                                <input class="form-control form-control-sm form-control-solid w-auto" id="ped_q" placeholder="Folio o cliente">
                                                <button class="btn btn-sm btn-light-primary" id="ped_recargar" type="button"><i class="bi bi-arrow-clockwise"></i></button>
                                            </div>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table align-middle table-row-dashed gy-3 mb-0">
                                                <thead><tr class="text-muted fw-bold fs-8 text-uppercase"><th>Folio</th><th>Cliente</th><th>Tipo</th><th>Total</th><th>Pagado</th><th>Saldo</th><th>Estatus</th><th class="text-end">Acciones</th></tr></thead>
                                                <tbody id="ped_tabla"></tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xl-4">
                                    <div class="ped-card p-4 mb-4">
                                        <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                                            <div>
                                                <div class="fw-bold fs-5 mb-1">Simular pedido/apartado</div>
                                                <div class="text-muted fs-7">Valida cliente, politica, anticipo, stock y reserva sin crear folio</div>
                                            </div>
                                            <span class="badge badge-light-warning">Prevalidacion</span>
                                        </div>
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label text-muted fs-8 text-uppercase">Tipo</label>
                                                <select class="form-select form-select-solid" id="ped_reserva_tipo">
                                                    <option value="apartado">Apartado</option>
                                                    <option value="pedido">Pedido</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label text-muted fs-8 text-uppercase">Fecha compromiso</label>
                                                <input class="form-control form-control-solid" id="ped_reserva_fecha" type="date">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label text-muted fs-8 text-uppercase">Almacen</label>
                                                <select class="form-select form-select-solid" id="ped_reserva_almacen"></select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label text-muted fs-8 text-uppercase">Caja</label>
                                                <select class="form-select form-select-solid" id="ped_reserva_caja"></select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label text-muted fs-8 text-uppercase">Turno</label>
                                                <select class="form-select form-select-solid" id="ped_reserva_turno"></select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label text-muted fs-8 text-uppercase">Metodo anticipo</label>
                                                <select class="form-select form-select-solid" id="ped_reserva_metodo"></select>
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label text-muted fs-8 text-uppercase">Cliente</label>
                                                <input class="form-control form-control-solid" id="ped_reserva_cliente" placeholder="Nombre o referencia del cliente">
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label text-muted fs-8 text-uppercase">Telefono / identificador</label>
                                                <input class="form-control form-control-solid" id="ped_reserva_identificador" inputmode="tel" placeholder="Telefono o identificador CRM">
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label text-muted fs-8 text-uppercase">Buscar producto</label>
                                                <div class="input-group input-group-solid">
                                                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                                                    <input class="form-control" id="ped_producto_q" placeholder="Escanea codigo, SKU o escribe producto">
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center mt-2">
                                                    <span class="text-muted fs-8" id="ped_producto_estado">Busca un producto para llenar SKU y precio.</span>
                                                    <button class="btn btn-sm btn-light" id="ped_producto_limpiar" type="button">Limpiar</button>
                                                </div>
                                                <div id="ped_producto_resultados" class="ped-product-results mt-2 d-none"></div>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label text-muted fs-8 text-uppercase">SKU ID</label>
                                                <input class="form-control form-control-solid text-end" id="ped_reserva_sku" inputmode="numeric" placeholder="0">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label text-muted fs-8 text-uppercase">Cantidad</label>
                                                <input class="form-control form-control-solid text-end" id="ped_reserva_cantidad" inputmode="decimal" value="1">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label text-muted fs-8 text-uppercase">Precio</label>
                                                <input class="form-control form-control-solid text-end" id="ped_reserva_precio" inputmode="decimal" placeholder="0.00">
                                            </div>
                                            <div class="col-12">
                                                <button class="btn btn-light-primary w-100" id="ped_reserva_agregar" type="button"><i class="bi bi-plus-lg"></i> Agregar partida</button>
                                            </div>
                                            <div class="col-12">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <div class="fw-bold fs-7">Partidas del pedido</div>
                                                    <button class="btn btn-sm btn-light" id="ped_reserva_vaciar" type="button">Vaciar</button>
                                                </div>
                                                <div class="ped-lines">
                                                    <div class="table-responsive">
                                                        <table class="table table-sm align-middle mb-0">
                                                            <thead>
                                                            <tr class="text-muted fs-8 text-uppercase">
                                                                <th>Producto</th>
                                                                <th class="text-end">Cantidad</th>
                                                                <th class="text-end">Precio</th>
                                                                <th class="text-end">Importe</th>
                                                                <th></th>
                                                            </tr>
                                                            </thead>
                                                            <tbody id="ped_partidas_body"></tbody>
                                                        </table>
                                                    </div>
                                                    <div id="ped_partidas_empty" class="ped-line-empty d-flex align-items-center justify-content-center text-muted fs-8">Agrega una o varias partidas antes de simular.</div>
                                                </div>
                                                <div class="ped-line-summary d-flex justify-content-between align-items-center px-3 text-muted fs-8" id="ped_partidas_resumen">
                                                    <span>0 partidas agregadas</span>
                                                    <span>Agrega la partida para incluirla en el pedido.</span>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label text-muted fs-8 text-uppercase">Anticipo</label>
                                                <input class="form-control form-control-solid text-end fw-bold" id="ped_reserva_anticipo" inputmode="decimal" value="100">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label text-muted fs-8 text-uppercase">Referencia</label>
                                                <input class="form-control form-control-solid" id="ped_reserva_referencia" placeholder="Opcional">
                                            </div>
                                            <div class="col-12">
                                                <div class="ped-total-box p-3">
                                                    <div class="row g-3 align-items-center">
                                                        <div class="col-4">
                                                            <div class="text-muted fs-8 text-uppercase">Total</div>
                                                            <div class="fw-bold fs-5" id="ped_reserva_total">$0.00</div>
                                                        </div>
                                                        <div class="col-4">
                                                            <div class="text-muted fs-8 text-uppercase">Anticipo</div>
                                                            <div class="fw-bold fs-5" id="ped_reserva_pagado">$0.00</div>
                                                        </div>
                                                        <div class="col-4">
                                                            <div class="text-muted fs-8 text-uppercase">Saldo</div>
                                                            <div class="fw-bold fs-5" id="ped_reserva_saldo">$0.00</div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <button class="btn btn-primary w-100" id="ped_reserva_simular" type="button"><i class="bi bi-bookmark-check"></i> Simular reserva</button>
                                            </div>
                                        </div>
                                        <div id="ped_reserva_resultado" class="ped-result mt-4"></div>
                                    </div>
                                    <div class="ped-card p-4 mb-4">
                                        <div class="fw-bold fs-5 mb-1">Simular abono</div>
                                        <div class="text-muted fs-7 mb-4">Valida caja/turno/pago antes de registrar dinero</div>
                                        <div class="row g-3">
                                            <div class="col-12">
                                                <label class="form-label text-muted fs-8 text-uppercase">Folio pedido/apartado</label>
                                                <input class="form-control form-control-solid" id="ped_abono_folio" placeholder="APT-...">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label text-muted fs-8 text-uppercase">Almacen</label>
                                                <select class="form-select form-select-solid" id="ped_abono_almacen"></select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label text-muted fs-8 text-uppercase">Caja</label>
                                                <select class="form-select form-select-solid" id="ped_abono_caja"></select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label text-muted fs-8 text-uppercase">Turno</label>
                                                <select class="form-select form-select-solid" id="ped_abono_turno"></select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label text-muted fs-8 text-uppercase">Metodo</label>
                                                <select class="form-select form-select-solid" id="ped_abono_metodo"></select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label text-muted fs-8 text-uppercase">Monto</label>
                                                <input class="form-control form-control-solid text-end fw-bold" id="ped_abono_monto" inputmode="decimal" placeholder="0.00">
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label text-muted fs-8 text-uppercase">Referencia</label>
                                                <input class="form-control form-control-solid" id="ped_abono_referencia" placeholder="Opcional">
                                            </div>
                                            <div class="col-12">
                                                <button class="btn btn-success w-100" id="ped_abono_simular" type="button"><i class="bi bi-calculator"></i> Simular abono</button>
                                            </div>
                                        </div>
                                        <div id="ped_abono_resultado" class="ped-result mt-4"></div>
                                    </div>
                                    <div class="alert alert-info py-3">
                                        <div class="fw-bold">Pendiente productivo</div>
                                        <div class="fs-7">Las acciones reales recalculan en backend y piden confirmacion antes de escribir.</div>
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
<script src="/assets/js/custom/apps/erp/ventas/pedidos.js?v=20260705-pedidos-real-ui3"></script>
</body>
</html>
