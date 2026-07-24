<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../">
    <title>POS ERP</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css">
    <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css">
    <!--
      Documentacion IA: Codex GPT-5, 2026-06-26.
      Proposito: primera superficie POS ERP para busqueda, carrito y prevalidacion contra inventario.
      Impacto: no mezcla ecommerce legacy ni ejecuta cobros/descuentos.
      Contrato: todos los datos operativos se consultan por endpoints VentasErp read-only.
    -->
    <style>
        .pos-shell { min-height: calc(100vh - 190px); }
        .pos-toolbar { background: #f7f8fa; border: 1px solid #e6e8ee; border-radius: 8px; }
        .pos-results-wrap { overflow-x: auto; overflow-y: hidden; padding-bottom: 8px; }
        .pos-results { display: flex; flex-wrap: nowrap; gap: 12px; min-height: 204px; }
        .pos-product { border: 1px solid #e7e9ef; border-radius: 8px; background: #fff; width: 210px; min-width: 210px; min-height: 196px; }
        .pos-product-img { width: 100%; height: 92px; object-fit: cover; border-radius: 7px 7px 0 0; background: #f1f3f6; }
        .pos-cart { width: 100%; }
        .pos-cart-list { max-height: 46vh; overflow: auto; }
        .pos-cart-img { width: 54px; height: 54px; object-fit: cover; border-radius: 8px; background: #f1f3f6; flex: 0 0 54px; }
        .pos-qty { width: 146px; min-width: 146px; max-width: 146px; display: grid; grid-template-columns: 34px 78px 34px; }
        .pos-qty .btn { width: 34px; height: 34px; padding: 0; display: inline-flex; align-items: center; justify-content: center; }
        .pos-qty input { width: 78px; min-width: 0; height: 34px; font-weight: 700; box-sizing: border-box; }
        .pos-weight-input { width: 146px; min-width: 146px; max-width: 146px; height: 38px; font-size: 1.12rem; font-weight: 700; text-align: right; box-sizing: border-box; }
        .pos-qty-label { min-width: 146px; text-align: center; }
        .pos-cart-table { min-width: 760px; }
        .pos-cart-table th { color: #7e8299; font-size: .72rem; text-transform: uppercase; white-space: nowrap; }
        .pos-cart-table td { vertical-align: middle; }
        .pos-cart-table th:nth-child(3), .pos-cart-table td:nth-child(3) { width: 185px; min-width: 185px; }
        .pos-mode-group { display: inline-flex; border: 1px solid #e3e6ee; border-radius: 8px; overflow: hidden; background: #fff; }
        .pos-mode-btn { border: 0; background: transparent; padding: 7px 10px; font-weight: 600; color: #5e6278; white-space: nowrap; }
        .pos-mode-btn.active { background: #1b84ff; color: #fff; }
        .pos-mode-btn:disabled { color: #b5b5c3; background: #f5f6fa; cursor: not-allowed; }
        .pos-pay-grid { display: grid; grid-template-columns: minmax(150px, 1fr) 110px minmax(120px, .8fr) 34px; gap: 8px; align-items: center; }
        .pos-cuentas { display: flex; flex-wrap: nowrap; gap: 8px; overflow-x: auto; padding-bottom: 6px; }
        .pos-cuenta-btn { min-width: 142px; border: 1px solid #e4e6ef; background: #fff; border-radius: 8px; padding: 8px 10px; text-align: left; }
        .pos-cuenta-btn.active { border-color: #1b84ff; background: #f1f7ff; }
        .pos-cuenta-total { font-weight: 700; color: #181c32; }
        .pos-cuenta-close { width: 28px; height: 28px; padding: 0; }
        .pos-module-bar { display: flex; flex-wrap: wrap; gap: 8px; padding: 8px 0 0; width: 100%; }
        .pos-module-btn { min-width: 112px; border: 1px solid #e4e6ef; background: #fff; border-radius: 8px; padding: 9px 10px; color: #3f4254; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; gap: 7px; white-space: nowrap; }
        .pos-module-btn:hover { border-color: #1b84ff; background: #f1f7ff; color: #1b84ff; }
        .pos-module-btn.pos-module-primary { background: #f1f7ff; color: #1b84ff; border-color: #b8d8ff; }
        .pos-action-strip { border: 1px solid #e6e8ee; border-radius: 8px; background: #fff; padding: 10px; }
        .pos-action-strip .pos-module-bar { justify-content: flex-start; }
        .pos-shortcut-hint { margin-left: 2px; padding: 1px 5px; border-radius: 5px; background: rgba(126,130,153,.12); color: #7e8299; font-size: .67rem; font-weight: 800; line-height: 1.25; }
        .pos-pay-quick { min-width: 132px; border-radius: 8px; font-weight: 700; display: inline-flex; align-items: center; justify-content: center; gap: 7px; }
        .pos-total-panel { border-left: 1px solid #eef0f6; padding-left: 18px; }
        .pos-badge-row { min-height: 26px; }
        .pos-empty { min-height: 180px; border: 1px dashed #d7dbe4; border-radius: 8px; }
        .pos-product-title { min-height: 38px; line-height: 1.25; }
        .pos-product-meta { min-height: 35px; }
        .pos-caja-result { max-height: 260px; overflow: auto; }
        .pos-evidencias-result { max-height: 360px; overflow: auto; }
        .pos-evidencia-row { border: 1px solid #e7e9ef; border-radius: 8px; padding: 12px; background: #fff; }
        .pos-corte-result { max-height: 320px; overflow: auto; }
        .pos-atenciones-result { max-height: 320px; overflow: auto; }
        .pos-cliente-result { max-height: 320px; overflow: auto; }
        .pos-scan-preview { position: relative; border-radius: 8px; overflow: hidden; background: #111827; min-height: 360px; }
        .pos-scan-preview video { width: 100%; min-height: 360px; object-fit: cover; display: block; }
        .pos-scan-guide { position: absolute; left: 10%; right: 10%; top: 38%; height: 86px; border: 2px solid rgba(255,255,255,.9); border-radius: 8px; box-shadow: 0 0 0 999px rgba(0,0,0,.22); pointer-events: none; }
        .pos-scan-line { position: absolute; left: 12%; right: 12%; top: calc(38% + 43px); height: 2px; background: #50cd89; box-shadow: 0 0 12px rgba(80,205,137,.75); pointer-events: none; }
        @media (max-width: 991px) {
            .pos-cart-list { max-height: none; }
            .pos-product { width: 190px; min-width: 190px; }
            .pos-pay-grid { grid-template-columns: 1fr 96px 1fr 34px; }
            .pos-total-panel { border-left: 0; padding-left: 0; }
            .pos-action-strip .pos-module-btn { min-width: 104px; flex: 1 1 104px; }
        }
        @media (max-width: 575px) {
            .pos-pay-grid { grid-template-columns: 1fr; }
            .pos-pay-grid .btn { width: 100%; }
            .pos-module-btn { min-width: 98px; font-size: .86rem; }
            .pos-pay-quick { min-width: 100%; }
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
                                <h1 class="page-heading text-dark fw-bold fs-3 mb-1">POS ERP</h1>
                                <span class="text-muted">Venta real con inventario, caja, kardex y ticket; simulaciones marcadas por separado</span>
                                <div class="d-flex flex-wrap gap-2 mt-2">
                                    <span class="badge badge-light-success">Cobrar = accion real</span>
                                    <span class="badge badge-light-primary">Prevalidar/Simular = no escribe</span>
                                    <span class="badge badge-light-info">Ticket preview = solo vista previa</span>
                                </div>
                            </div>
                            <div class="d-flex gap-2">
                                <button class="btn btn-light-primary" id="pos_refrescar" type="button" title="Actualizar catalogos">
                                    <i class="bi bi-arrow-clockwise"></i>
                                </button>
                                <a class="btn btn-light" href="/ventas/manual_pos"><i class="bi bi-question-circle"></i> Manual</a>
                            </div>
                        </div>
                    </div>
                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <div class="pos-toolbar p-4 mb-4">
                                <div class="pos-action-strip mb-4">
                                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                                        <div>
                                            <div class="fw-bold">Acciones POS</div>
                                            <div class="text-muted fs-8">Venta rapida, ticket, caja y consultas sin mezclar modulos</div>
                                        </div>
                                        <div class="pos-module-bar py-0">
                                            <button class="pos-module-btn pos-module-primary" id="pos_prevalidar" type="button" title="Revisar stock, caja, pagos y reglas antes de cobrar"><i class="bi bi-shield-check"></i> Prevalidar <span class="pos-shortcut-hint">F9</span></button>
                                            <button class="pos-module-btn" id="pos_venta_rapida_btn" type="button" title="Producto no catalogado con pendiente para Catalogo"><i class="bi bi-lightning-charge"></i> Venta rapida</button>
                                            <button class="pos-module-btn" id="pos_ticket_preview" type="button" title="Vista previa del ticket sin confirmar venta"><i class="bi bi-receipt"></i> Ticket</button>
                                            <button class="pos-module-btn" id="pos_cliente_precio_modal_btn" type="button" title="Buscar o preparar cliente CRM"><i class="bi bi-person-vcard"></i> Cliente</button>
                                            <button class="pos-module-btn" id="pos_excepcion_modal_btn" type="button" title="Precio manual o descuento autorizado"><i class="bi bi-shield-lock"></i> Autorizar</button>
                                            <button class="pos-module-btn" id="pos_atenciones_modal_btn" type="button" title="Cuentas creadas por otros operadores"><i class="bi bi-people"></i> Atenciones</button>
                                            <a class="pos-module-btn" href="/ventas/manual_pos#manual-arranque"><i class="bi bi-clipboard-check"></i> Arranque</a>
                                            <a class="pos-module-btn" href="/ventas/caja_turnos"><i class="bi bi-calculator"></i> Caja</a>
                                            <a class="pos-module-btn" href="/ventas/mostrar"><i class="bi bi-receipt-cutoff"></i> Ventas</a>
                                            <a class="pos-module-btn" href="/ventas/caja_movimientos"><i class="bi bi-cash-stack"></i> Movimientos</a>
                                            <a class="pos-module-btn" href="/ventas/caja_evidencias"><i class="bi bi-file-earmark-check"></i> Evidencias</a>
                                            <a class="pos-module-btn" href="/ventas/reportes"><i class="bi bi-bar-chart"></i> Reportes</a>
                                            <div class="dropdown">
                                                <button class="pos-module-btn dropdown-toggle" type="button" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i> Mas</button>
                                                <div class="dropdown-menu dropdown-menu-end">
                                                    <button class="dropdown-item" id="pos_dryrun" type="button"><i class="bi bi-clipboard-pulse me-2"></i> Simular venta</button>
                                                    <button class="dropdown-item" id="pos_pedido_dryrun" type="button"><i class="bi bi-bookmark-check me-2"></i> Simular pedido</button>
                                                    <button class="dropdown-item" id="pos_inventario_pendiente_dryrun" type="button"><i class="bi bi-exclamation-triangle me-2"></i> Validar inventario pendiente</button>
                                                    <a class="dropdown-item" href="/ventas/pedidos"><i class="bi bi-journal-bookmark me-2"></i> Pedidos/apartados</a>
                                                    <a class="dropdown-item" href="/ventas/manual_pos"><i class="bi bi-question-circle me-2"></i> Manual POS</a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row g-3 align-items-end">
                                    <div class="col-lg-3">
                                        <label class="form-label text-muted fs-8 text-uppercase">Punto de venta</label>
                                        <div class="input-group">
                                            <select class="form-select form-select-solid" id="pos_almacen"></select>
                                            <button class="btn btn-light-primary" id="pos_terminal_config_btn" type="button" title="Configurar terminal"><i class="bi bi-gear"></i></button>
                                        </div>
                                        <div class="text-muted fs-8 mt-1" id="pos_terminal_estado">Terminal sin fijar</div>
                                    </div>
                                    <div class="col-lg-2">
                                        <label class="form-label text-muted fs-8 text-uppercase">Canal</label>
                                        <select class="form-select form-select-solid" id="pos_canal">
                                            <option value="pos">Mostrador</option>
                                        </select>
                                    </div>
                                    <div class="col-lg-2">
                                        <label class="form-label text-muted fs-8 text-uppercase">Documento</label>
                                        <select class="form-select form-select-solid" id="pos_tipo_documento">
                                            <option value="venta">Venta</option>
                                            <option value="pedido">Pedido</option>
                                            <option value="apartado">Apartado</option>
                                        </select>
                                    </div>
                                    <div class="col-lg-2">
                                        <label class="form-label text-muted fs-8 text-uppercase">Caja</label>
                                        <select class="form-select form-select-solid" id="pos_caja"></select>
                                    </div>
                                    <div class="col-lg-2">
                                        <label class="form-label text-muted fs-8 text-uppercase">Turno</label>
                                        <select class="form-select form-select-solid" id="pos_turno"></select>
                                    </div>
                                    <div class="col-lg-2">
                                        <label class="form-label text-muted fs-8 text-uppercase">Cliente</label>
                                        <input class="form-control form-control-solid" id="pos_cliente" placeholder="Nombre opcional">
                                    </div>
                                    <div class="col-lg-2">
                                        <label class="form-label text-muted fs-8 text-uppercase">Telefono</label>
                                        <input class="form-control form-control-solid" id="pos_cliente_telefono" inputmode="tel" placeholder="Rapido">
                                    </div>
                                    <div class="col-lg-2 d-none" id="pos_compromiso_wrap">
                                        <label class="form-label text-muted fs-8 text-uppercase">Compromiso</label>
                                        <input class="form-control form-control-solid" id="pos_fecha_compromiso" type="datetime-local">
                                        <div class="text-muted fs-8 mt-1">Solo pedidos/apartados</div>
                                    </div>
                                    <div class="col-lg-12">
                                        <label class="form-label text-muted fs-8 text-uppercase">Buscar producto</label>
                                        <div class="input-group input-group-lg">
                                            <span class="input-group-text bg-white"><i class="bi bi-search fs-3"></i></span>
                                            <input class="form-control" id="pos_buscar" autocomplete="off" placeholder="Escanea o busca SKU, producto, codigo o etiqueta">
                                            <button class="btn btn-light-primary" id="pos_scan_camera_btn" type="button" title="Escanear con camara"><i class="bi bi-camera"></i></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="pos-shell">
                                <div class="mb-4">
                                    <div class="d-flex flex-wrap gap-2 mb-3">
                                        <span class="badge badge-light-primary fs-7" id="pos_operador_badge">Operador</span>
                                        <span class="badge badge-light-info fs-7" id="pos_terminal_badge">Terminal libre</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div>
                                            <div class="fw-bold fs-5">Productos</div>
                                            <div class="text-muted fs-7" id="pos_resultados_estado">Selecciona un punto de venta y busca un SKU.</div>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <button class="btn btn-sm btn-light" id="pos_limpiar_busqueda" type="button" title="Limpiar busqueda"><i class="bi bi-x-lg"></i></button>
                                        </div>
                                    </div>
                                    <div class="pos-results-wrap">
                                        <div class="pos-results" id="pos_resultados"></div>
                                    </div>
                                    <div class="pos-empty d-flex align-items-center justify-content-center text-center text-muted" id="pos_vacio">
                                        <div>
                                            <i class="bi bi-upc-scan fs-1 d-block mb-3"></i>
                                            <div class="fw-semibold">Listo para escanear o buscar</div>
                                            <div class="fs-7">Las unidades abiertas solo aparecen como granel cuando el SKU lo permite.</div>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <div class="card pos-cart">
                                        <div class="card-header border-0 pt-5">
                                            <div>
                                                <h2 class="card-title fw-bold fs-4 mb-1">Carrito</h2>
                                                <div class="text-muted fs-7" id="pos_carrito_estado">Sin partidas</div>
                                            </div>
                                            <button class="btn btn-sm btn-icon btn-light-danger" id="pos_vaciar" type="button" title="Vaciar carrito"><i class="bi bi-trash"></i></button>
                                        </div>
                                        <div class="card-body pt-2">
                                            <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
                                                <div>
                                                    <div class="fw-bold">Cuentas en atencion</div>
                                                    <div class="text-muted fs-8">Carritos locales para atender varios clientes sin mezclar partidas</div>
                                                </div>
                                                <button class="btn btn-sm btn-light-primary" id="pos_cuenta_nueva" type="button"><i class="bi bi-plus-lg"></i> Nueva cuenta</button>
                                            </div>
                                            <div class="pos-cuentas mb-3" id="pos_cuentas"></div>
                                            <div class="pos-cart-list" id="pos_carrito"></div>
                                            <div class="separator my-4"></div>
                                            <div class="row g-4">
                                                <div class="col-xl-7">
                                                    <div class="mb-4">
                                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                                            <div class="fw-bold">Pagos</div>
                                                            <span class="text-muted fs-8">Elige metodo rapido</span>
                                                        </div>
                                                        <div class="d-flex flex-wrap gap-2 mb-2">
                                                            <button class="btn btn-sm btn-light-success pos-pay-quick" data-pos-pago-rapido="efectivo" type="button" title="Alt+1"><i class="bi bi-cash"></i> Efectivo <span class="pos-shortcut-hint">Alt+1</span></button>
                                                            <button class="btn btn-sm btn-light-info pos-pay-quick" data-pos-pago-rapido="tarjeta" type="button" title="Alt+2"><i class="bi bi-credit-card"></i> Tarjeta <span class="pos-shortcut-hint">Alt+2</span></button>
                                                            <button class="btn btn-sm btn-light-warning pos-pay-quick" data-pos-pago-rapido="transferencia" type="button" title="Alt+3"><i class="bi bi-bank"></i> Transferencia <span class="pos-shortcut-hint">Alt+3</span></button>
                                                            <button class="btn btn-sm btn-light-primary pos-pay-quick" data-pos-pago-rapido="saldo_crm" type="button"><i class="bi bi-wallet2"></i> Saldo cliente</button>
                                                            <button class="btn btn-sm btn-light pos-pay-quick" id="pos_agregar_pago" type="button"><i class="bi bi-plus-lg"></i> Otro pago</button>
                                                        </div>
                                                        <div id="pos_cliente_saldo_crm" class="mb-2"></div>
                                                        <div id="pos_pagos"></div>
                                                    </div>
                                                    <div id="pos_validacion" class="mb-4"></div>
                                                    <div id="pos_excepcion_activa" class="mb-4"></div>
                                                </div>
                                                <div class="col-xl-5">
                                                    <div class="pos-total-panel">
                                                    <div class="d-flex justify-content-between fs-6 mb-2">
                                                        <span class="text-muted">Subtotal</span>
                                                        <strong id="pos_subtotal">$0.00</strong>
                                                    </div>
                                                    <div class="d-flex justify-content-between fs-6 mb-2">
                                                        <span class="text-muted">Pagado</span>
                                                        <strong id="pos_pagado">$0.00</strong>
                                                    </div>
                                                    <div class="d-flex justify-content-between fs-6 mb-2">
                                                        <span class="text-muted">Saldo</span>
                                                        <strong id="pos_saldo">$0.00</strong>
                                                    </div>
                                                    <div class="d-flex justify-content-between fs-6 mb-2">
                                                        <span class="text-muted">Cambio</span>
                                                        <strong id="pos_cambio">$0.00</strong>
                                                    </div>
                                                    <div class="d-flex justify-content-between fs-5 mb-4">
                                                        <span>Total estimado</span>
                                                        <strong id="pos_total">$0.00</strong>
                                                    </div>
                                                    <button class="btn btn-success btn-lg w-100" id="pos_cobrar_real" type="button"><i class="bi bi-cash-coin"></i> Cobrar <span class="pos-shortcut-hint">Ctrl+Enter</span></button>
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
        </div>
    </div>
</div>
<div class="modal fade" id="pos_venta_rapida_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h3 class="modal-title mb-1">Venta rapida controlada</h3>
                    <div class="text-muted fs-7">Producto por clasificar; no crea SKU definitivo ni descuenta inventario todavia</div>
                </div>
                <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning py-3">
                    <div class="fw-bold">Usala solo cuando el producto fisico no aparece en Catalogo ERP.</div>
                    <div class="fs-8">Si el SKU existe pero no tiene stock, usa inventario pendiente. Esta captura quedara como Producto por clasificar y debe generar pendiente a Catalogo en la etapa real.</div>
                </div>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label text-muted fs-8 text-uppercase">Descripcion detallada</label>
                        <textarea class="form-control form-control-solid" id="pos_vr_descripcion" rows="3" placeholder="Ej. Filtro interno marca X, color negro, para pecera chica, etiqueta azul"></textarea>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-muted fs-8 text-uppercase">Cantidad</label>
                        <input class="form-control form-control-solid" id="pos_vr_cantidad" inputmode="decimal" value="1">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-muted fs-8 text-uppercase">Precio unitario</label>
                        <input class="form-control form-control-solid" id="pos_vr_precio" inputmode="decimal" placeholder="0.00">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-muted fs-8 text-uppercase">Codigo barras</label>
                        <input class="form-control form-control-solid" id="pos_vr_codigo" placeholder="Opcional">
                    </div>
                    <div class="col-md-3">
                        <label class="form-check form-check-custom form-check-solid mt-8">
                            <input class="form-check-input" id="pos_vr_controla_inventario" type="checkbox" value="1" checked>
                            <span class="form-check-label">Controla inventario</span>
                        </label>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-muted fs-8 text-uppercase">Categoria provisional</label>
                        <input class="form-control form-control-solid" id="pos_vr_categoria" placeholder="Opcional">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-muted fs-8 text-uppercase">Marca provisional</label>
                        <input class="form-control form-control-solid" id="pos_vr_marca" placeholder="Opcional">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-muted fs-8 text-uppercase">Proveedor probable</label>
                        <input class="form-control form-control-solid" id="pos_vr_proveedor" placeholder="Opcional">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted fs-8 text-uppercase">Motivo</label>
                        <select class="form-select form-select-solid" id="pos_vr_motivo">
                            <option value="">Selecciona motivo</option>
                            <option value="producto_no_registrado">Producto no registrado</option>
                            <option value="codigo_no_encontrado">Codigo no encontrado</option>
                            <option value="venta_urgente_mostrador">Venta urgente mostrador</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted fs-8 text-uppercase">Observaciones</label>
                        <input class="form-control form-control-solid" id="pos_vr_observaciones" placeholder="Opcional para Catalogo">
                    </div>
                </div>
                <div id="pos_vr_resultado" class="mt-4"></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" type="button" data-bs-dismiss="modal">Cerrar</button>
                <button class="btn btn-light-primary" id="pos_vr_validar" type="button"><i class="bi bi-shield-check"></i> Validar</button>
                <button class="btn btn-primary" id="pos_vr_agregar" type="button" disabled><i class="bi bi-cart-plus"></i> Agregar al carrito</button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="pos_scan_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h3 class="modal-title mb-1">Escanear producto</h3>
                    <div class="text-muted fs-7">Lee codigo de barras para agregar a la cuenta actual</div>
                </div>
                <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="modal-body">
                <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
                    <label class="form-label text-muted fs-8 text-uppercase mb-0 d-none" id="pos_scan_camera_device_label">Camara</label>
                    <select class="form-select form-select-solid w-auto d-none" id="pos_scan_camera_device"></select>
                    <button class="btn btn-light-primary" id="pos_scan_start" type="button"><i class="bi bi-camera-video"></i> Iniciar</button>
                    <button class="btn btn-light-warning d-none" id="pos_scan_torch" type="button"><i class="bi bi-lightbulb"></i> Luz</button>
                    <button class="btn btn-light-info d-none" id="pos_scan_focus" type="button"><i class="bi bi-bullseye"></i> Enfoque</button>
                    <button class="btn btn-light-danger d-none" id="pos_scan_stop" type="button"><i class="bi bi-stop-circle"></i> Detener</button>
                </div>
                <div class="pos-scan-preview d-none" id="pos_scan_wrap">
                    <video id="pos_scan_video" playsinline muted autoplay></video>
                    <div class="pos-scan-guide"></div>
                    <div class="pos-scan-line"></div>
                </div>
                <div class="text-muted fs-7 mt-3" id="pos_scan_estado">Abre la camara y apunta al codigo. El producto se agregara solo si hay coincidencia unica.</div>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="pos_ticket_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h3 class="modal-title mb-1" id="pos_ticket_titulo">Ticket POS</h3>
                    <div class="text-muted fs-7" id="pos_ticket_subtitulo">Consulta de ticket</div>
                </div>
                <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="modal-body">
                <pre class="bg-light p-4 rounded fs-7 mb-0" id="pos_ticket_texto" style="white-space: pre-wrap;"></pre>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" type="button" data-bs-dismiss="modal">Cerrar</button>
                <button class="btn btn-primary" id="pos_ticket_imprimir" type="button" disabled><i class="bi bi-printer"></i> Imprimir</button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="pos_cliente_precio_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h3 class="modal-title mb-1">Cliente y autorizacion</h3>
                    <div class="text-muted fs-7">Cliente, lista vigente y excepciones comerciales</div>
                </div>
                <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="modal-body">
                <ul class="nav nav-tabs nav-line-tabs mb-5 fs-7" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="pos_tab_cliente_btn" data-bs-toggle="tab" data-bs-target="#pos_tab_cliente" type="button" role="tab">Cliente</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="pos_tab_excepcion_btn" data-bs-toggle="tab" data-bs-target="#pos_tab_excepcion" type="button" role="tab">Autorizacion</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="pos_tab_folio_btn" data-bs-toggle="tab" data-bs-target="#pos_tab_folio" type="button" role="tab">Folio</button>
                    </li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="pos_tab_cliente" role="tabpanel">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-6">
                                <label class="form-label text-muted fs-8 text-uppercase">Identificador</label>
                                <input class="form-control form-control-solid" id="pos_cliente_identificador" placeholder="Telefono, correo, codigo o nombre">
                            </div>
                            <div class="col-md-3">
                                <button class="btn btn-light-primary w-100" id="pos_cliente_buscar_crm" type="button"><i class="bi bi-person-lines-fill"></i> Buscar cliente</button>
                            </div>
                            <div class="col-md-3">
                                <button class="btn btn-primary w-100" id="pos_cliente_precio_simular" type="button"><i class="bi bi-tags"></i> Precios/lista</button>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted fs-8 text-uppercase">Nombre o alias</label>
                                <input class="form-control form-control-solid" id="pos_cliente_alta_nombre" placeholder="Nombre publico">
                            </div>
                            <div class="col-md-3">
                                <label class="form-check form-check-custom form-check-solid mt-8">
                                    <input class="form-check-input" id="pos_cliente_alta_consentimiento" type="checkbox" value="1">
                                    <span class="form-check-label">Contacto</span>
                                </label>
                            </div>
                            <div class="col-md-3">
                                <button class="btn btn-light-primary w-100" id="pos_cliente_alta_dryrun" type="button"><i class="bi bi-person-plus"></i> Validar alta</button>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="pos_tab_excepcion" role="tabpanel">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label text-muted fs-8 text-uppercase">Tipo</label>
                                <select class="form-select form-select-solid" id="pos_excepcion_tipo">
                                    <option value="precio_manual">Precio manual</option>
                                    <option value="descuento_partida">Descuento partida</option>
                                    <option value="descuento_general">Descuento general</option>
                                </select>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label text-muted fs-8 text-uppercase">Partida</label>
                                <select class="form-select form-select-solid" id="pos_excepcion_sku_objetivo"></select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-muted fs-8 text-uppercase">Precio manual</label>
                                <input class="form-control form-control-solid" id="pos_excepcion_precio" inputmode="decimal" placeholder="0.00">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-muted fs-8 text-uppercase">Desc. monto</label>
                                <input class="form-control form-control-solid" id="pos_excepcion_descuento_monto" inputmode="decimal" placeholder="0.00">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-muted fs-8 text-uppercase">Desc. %</label>
                                <input class="form-control form-control-solid" id="pos_excepcion_descuento_porcentaje" inputmode="decimal" placeholder="0">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label text-muted fs-8 text-uppercase">Motivo</label>
                                <input class="form-control form-control-solid" id="pos_excepcion_motivo" placeholder="Motivo comercial">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-muted fs-8 text-uppercase">Supervisor</label>
                                <input class="form-control form-control-solid" id="pos_excepcion_autorizacion" placeholder="Codigo o usuario">
                            </div>
                            <div class="col-md-3">
                                <button class="btn btn-warning w-100" id="pos_excepcion_dryrun" type="button"><i class="bi bi-shield-check"></i> Validar</button>
                            </div>
                            <div class="col-12">
                                <button class="btn btn-light-warning w-100" id="pos_excepcion_registrar" type="button"><i class="bi bi-file-earmark-lock"></i> Registrar folio autorizado</button>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="pos_tab_folio" role="tabpanel">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-8">
                                <label class="form-label text-muted fs-8 text-uppercase">Folio autorizado</label>
                                <input class="form-control form-control-solid" id="pos_excepcion_folio" placeholder="EXC-YYYYMMDD-000001">
                            </div>
                            <div class="col-md-4">
                                <button class="btn btn-success w-100" id="pos_excepcion_consumo_dryrun" type="button"><i class="bi bi-check2-circle"></i> Aplicar folio</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="pos_cliente_precio_resultado" class="pos-cliente-result"></div>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="pos_atenciones_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h3 class="modal-title mb-1">Atenciones</h3>
                    <div class="text-muted fs-7">Cuentas compartidas entre vendedores y caja</div>
                </div>
                <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info py-3">Actualmente las cuentas son locales del navegador. Esta pantalla simula la futura bandeja persistente multiusuario.</div>
                <div class="d-flex flex-wrap gap-2 mb-4">
                    <button class="btn btn-light-primary" id="pos_atenciones_bandeja" type="button"><i class="bi bi-list-check"></i> Consultar bandeja</button>
                    <button class="btn btn-primary" id="pos_atenciones_simular" type="button"><i class="bi bi-cloud-arrow-up"></i> Simular compartir cuenta actual</button>
                </div>
                <div id="pos_atenciones_resultado" class="pos-atenciones-result"></div>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="pos_terminal_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h3 class="modal-title mb-1">Configurar terminal POS</h3>
                    <div class="text-muted fs-7">Fija esta computadora a una tienda para evitar ventas en otro punto</div>
                </div>
                <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="modal-body">
                <label class="form-label text-muted fs-8 text-uppercase">Tienda de esta terminal</label>
                <select class="form-select form-select-solid mb-4" id="pos_terminal_almacen"></select>
                <div class="alert alert-info py-3 mb-0">Esta configuracion local es solo para UAT. Cuando exista asignacion oficial en BD, el POS abrira con usuario, terminal, caja y sucursal asignados.</div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" id="pos_terminal_liberar" type="button">Liberar terminal</button>
                <button class="btn btn-primary" id="pos_terminal_guardar" type="button">Fijar terminal</button>
            </div>
        </div>
    </div>
</div>
<script>
window.POS_USUARIO_ACTUAL = <?= json_encode(array(
    "id_usuario" => isset($_SESSION["id_usuario"]) ? intval($_SESSION["id_usuario"]) : 0,
    "nombre" => trim((isset($_SESSION["nombres"]) ? $_SESSION["nombres"] : "") . " " . (isset($_SESSION["apellido_paterno"]) ? $_SESSION["apellido_paterno"] : "") . " " . (isset($_SESSION["apellido_materno"]) ? $_SESSION["apellido_materno"] : ""))
), JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="assets/plugins/global/plugins.bundle.js"></script>
<script src="assets/js/scripts.bundle.js"></script>
<script src="/assets/js/custom/apps/erp/ventas/pos.js?v=20260723-venta-rapida-ui-real"></script>
</body>
</html>
