<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../">
    <title>Envios foraneos</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css">
    <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css">
    <!--
      IA: Codex GPT-5
      Fecha: 2026-10-04
      Proposito: vista operativa inicial para cotizar envios foraneos desde Ventas.
      Impacto: Ventas/Pedidos, Catalogo y futuro TMS; no escribe BD ni descuenta inventario.
      Contrato: captura borradores locales y detecta pendientes logisticos del catalogo.
    -->
    <style>
        .env-card { border: 1px solid #e6e8ee; border-radius: 8px; background: #fff; }
        .env-kpi { min-height: 78px; border: 1px solid #e6e8ee; border-radius: 8px; background: #fff; }
        .env-list { max-height: 520px; overflow: auto; }
        .env-product-results { max-height: 280px; overflow: auto; border: 1px solid #e6e8ee; border-radius: 8px; }
        .env-product-row { cursor: pointer; border-bottom: 1px solid #edf0f5; }
        .env-product-row:last-child { border-bottom: 0; }
        .env-product-row:hover { background: #f8fafc; }
        .env-product-img { width: 48px; height: 48px; object-fit: cover; border-radius: 8px; background: #f1f3f6; flex: 0 0 48px; }
        .env-line-img { width: 38px; height: 38px; object-fit: cover; border-radius: 6px; background: #f1f3f6; }
        .env-lines { border: 1px solid #e6e8ee; border-radius: 8px; overflow: hidden; }
        .env-empty { min-height: 130px; border: 1px dashed #d7dbe4; border-radius: 8px; background: #fbfcfe; }
        .env-sticky { position: sticky; top: 92px; }
        .env-chip { border: 1px solid #e1e5ee; border-radius: 999px; padding: .35rem .65rem; background: #f8fafc; }
        .env-dim-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .75rem; }
        @media (max-width: 991.98px) {
            .env-sticky { position: static; }
            .env-dim-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 575.98px) {
            .env-dim-grid { grid-template-columns: 1fr; }
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
                                <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Envios foraneos</h1>
                                <span class="text-muted">Cotizacion, paquetes y pendientes de catalogo</span>
                                <div class="d-flex flex-wrap gap-2 mt-2">
                                    <span class="badge badge-light-primary">Ventas</span>
                                    <span class="badge badge-light-warning">Borrador local</span>
                                    <span class="badge badge-light-info">Catalogo ERP</span>
                                </div>
                            </div>
                            <div class="d-flex gap-2">
                                <a class="btn btn-light" href="/ventas/pedidos"><i class="bi bi-bookmark-check"></i> Pedidos</a>
                                <a class="btn btn-light-primary" href="/ventas/mostrar"><i class="bi bi-receipt"></i> Ventas</a>
                                <a class="btn btn-primary" href="/ventas/pos"><i class="bi bi-shop-window"></i> POS</a>
                            </div>
                        </div>
                    </div>
                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <div id="env_alerta" class="mb-4"></div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-3">
                                    <div class="env-kpi p-3">
                                        <div class="text-muted fs-8 text-uppercase">Cotizaciones abiertas</div>
                                        <div class="fw-bold fs-4" id="env_kpi_abiertas">0</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="env-kpi p-3">
                                        <div class="text-muted fs-8 text-uppercase">Pendientes catalogo</div>
                                        <div class="fw-bold fs-4" id="env_kpi_catalogo">0</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="env-kpi p-3">
                                        <div class="text-muted fs-8 text-uppercase">Cotizacion enviada</div>
                                        <div class="fw-bold fs-4" id="env_kpi_enviadas">0</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="env-kpi p-3">
                                        <div class="text-muted fs-8 text-uppercase">Aceptadas</div>
                                        <div class="fw-bold fs-4" id="env_kpi_aceptadas">0</div>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-4">
                                <div class="col-xl-4">
                                    <div class="env-card p-4 env-sticky">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <div>
                                                <div class="fw-bold fs-5">Bandeja</div>
                                                <div class="text-muted fs-7">Seguimiento de interesados</div>
                                            </div>
                                            <button class="btn btn-sm btn-primary" id="env_nuevo" type="button"><i class="bi bi-plus-lg"></i> Nuevo</button>
                                        </div>
                                        <div class="row g-2 mb-3">
                                            <div class="col-7">
                                                <input class="form-control form-control-sm form-control-solid" id="env_filtro_q" placeholder="Buscar cliente, folio o destino">
                                            </div>
                                            <div class="col-5">
                                                <select class="form-select form-select-sm form-select-solid" id="env_filtro_estatus">
                                                    <option value="">Todos</option>
                                                    <option value="borrador">Borrador</option>
                                                    <option value="datos_incompletos">Datos incompletos</option>
                                                    <option value="cotizando_envio">Cotizando envio</option>
                                                    <option value="cotizacion_enviada">Cotizacion enviada</option>
                                                    <option value="aceptada">Aceptada</option>
                                                    <option value="convertida_pedido">Convertida a pedido</option>
                                                    <option value="descartada">Descartada</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div id="env_bandeja" class="env-list"></div>
                                    </div>
                                </div>

                                <div class="col-xl-8">
                                    <div class="env-card p-4 mb-4">
                                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
                                            <div>
                                                <div class="fw-bold fs-5">Cotizacion</div>
                                                <div class="text-muted fs-7" id="env_folio_label">Nuevo borrador</div>
                                            </div>
                                            <div class="d-flex flex-wrap gap-2">
                                                <select class="form-select form-select-sm form-select-solid w-auto" id="env_estatus">
                                                    <option value="borrador">Borrador</option>
                                                    <option value="datos_incompletos">Datos incompletos</option>
                                                    <option value="cotizando_envio">Cotizando envio</option>
                                                    <option value="cotizacion_enviada">Cotizacion enviada</option>
                                                    <option value="aceptada">Aceptada</option>
                                                    <option value="convertida_pedido">Convertida a pedido</option>
                                                    <option value="descartada">Descartada</option>
                                                </select>
                                                <button class="btn btn-sm btn-light" id="env_duplicar" type="button"><i class="bi bi-copy"></i> Duplicar</button>
                                                <button class="btn btn-sm btn-success" id="env_guardar" type="button"><i class="bi bi-save"></i> Guardar</button>
                                            </div>
                                        </div>

                                        <div class="row g-4">
                                            <div class="col-lg-6">
                                                <div class="fw-bold fs-6 mb-3">Contacto</div>
                                                <div class="row g-3">
                                                    <div class="col-md-7">
                                                        <label class="form-label text-muted fs-8 text-uppercase">Nombre</label>
                                                        <input class="form-control form-control-solid" id="env_cliente_nombre" placeholder="Cliente o interesado">
                                                    </div>
                                                    <div class="col-md-5">
                                                        <label class="form-label text-muted fs-8 text-uppercase">Telefono</label>
                                                        <input class="form-control form-control-solid" id="env_cliente_telefono" inputmode="tel" placeholder="WhatsApp">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label text-muted fs-8 text-uppercase">Correo</label>
                                                        <input class="form-control form-control-solid" id="env_cliente_correo" inputmode="email" placeholder="Opcional">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label text-muted fs-8 text-uppercase">Origen</label>
                                                        <input class="form-control form-control-solid" id="env_origen" placeholder="Campana, WhatsApp, Meta">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-lg-6">
                                                <div class="fw-bold fs-6 mb-3">Destino</div>
                                                <div class="row g-3">
                                                    <div class="col-md-4">
                                                        <label class="form-label text-muted fs-8 text-uppercase">CP</label>
                                                        <input class="form-control form-control-solid" id="env_cp" inputmode="numeric">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label text-muted fs-8 text-uppercase">Estado</label>
                                                        <input class="form-control form-control-solid" id="env_estado">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label text-muted fs-8 text-uppercase">Ciudad</label>
                                                        <input class="form-control form-control-solid" id="env_ciudad">
                                                    </div>
                                                    <div class="col-12">
                                                        <label class="form-label text-muted fs-8 text-uppercase">Direccion / referencia</label>
                                                        <input class="form-control form-control-solid" id="env_direccion" placeholder="Calle, colonia o referencia si ya existe">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="env-card p-4 mb-4">
                                        <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-3">
                                            <div>
                                                <div class="fw-bold fs-5">Productos</div>
                                                <div class="text-muted fs-7">Catalogo ERP y pendientes logisticos</div>
                                            </div>
                                            <div class="d-flex gap-2">
                                                <button class="btn btn-sm btn-light" id="env_producto_manual" type="button"><i class="bi bi-pencil-square"></i> Producto no encontrado</button>
                                                <button class="btn btn-sm btn-light" id="env_vaciar_productos" type="button"><i class="bi bi-trash"></i> Vaciar</button>
                                            </div>
                                        </div>
                                        <div class="input-group input-group-solid mb-2">
                                            <span class="input-group-text"><i class="bi bi-search"></i></span>
                                            <input class="form-control" id="env_producto_q" placeholder="Escanea codigo, SKU o escribe producto">
                                        </div>
                                        <div id="env_producto_resultados" class="env-product-results d-none mb-3"></div>
                                        <div class="env-lines">
                                            <div class="table-responsive">
                                                <table class="table table-sm align-middle mb-0">
                                                    <thead>
                                                    <tr class="text-muted fs-8 text-uppercase">
                                                        <th>Producto</th>
                                                        <th class="text-end">Cant.</th>
                                                        <th class="text-end">Precio</th>
                                                        <th>Logistica</th>
                                                        <th class="text-end">Importe</th>
                                                        <th></th>
                                                    </tr>
                                                    </thead>
                                                    <tbody id="env_partidas_body"></tbody>
                                                </table>
                                            </div>
                                            <div id="env_partidas_empty" class="env-empty d-flex align-items-center justify-content-center text-muted fs-8">Agrega productos para preparar la cotizacion.</div>
                                        </div>
                                    </div>

                                    <div class="env-card p-4 mb-4">
                                        <div class="fw-bold fs-5 mb-3">Paquete y cotizacion externa</div>
                                        <div class="env-dim-grid mb-3">
                                            <div>
                                                <label class="form-label text-muted fs-8 text-uppercase">Largo cm</label>
                                                <input class="form-control form-control-solid text-end" id="env_paquete_largo" inputmode="decimal">
                                            </div>
                                            <div>
                                                <label class="form-label text-muted fs-8 text-uppercase">Ancho cm</label>
                                                <input class="form-control form-control-solid text-end" id="env_paquete_ancho" inputmode="decimal">
                                            </div>
                                            <div>
                                                <label class="form-label text-muted fs-8 text-uppercase">Alto cm</label>
                                                <input class="form-control form-control-solid text-end" id="env_paquete_alto" inputmode="decimal">
                                            </div>
                                            <div>
                                                <label class="form-label text-muted fs-8 text-uppercase">Peso kg</label>
                                                <input class="form-control form-control-solid text-end" id="env_paquete_peso" inputmode="decimal">
                                            </div>
                                        </div>
                                        <div class="row g-3">
                                            <div class="col-md-3">
                                                <label class="form-label text-muted fs-8 text-uppercase">Paquetes</label>
                                                <input class="form-control form-control-solid text-end" id="env_paquete_cantidad" inputmode="numeric" value="1">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label text-muted fs-8 text-uppercase">Plataforma</label>
                                                <input class="form-control form-control-solid" id="env_paqueteria" placeholder="Skydrop, Envia, etc.">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label text-muted fs-8 text-uppercase">Costo envio</label>
                                                <input class="form-control form-control-solid text-end" id="env_costo_envio" inputmode="decimal">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label text-muted fs-8 text-uppercase">Cobrar cliente</label>
                                                <input class="form-control form-control-solid text-end" id="env_precio_envio" inputmode="decimal">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label text-muted fs-8 text-uppercase">Servicio</label>
                                                <input class="form-control form-control-solid" id="env_servicio" placeholder="Terrestre, express">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label text-muted fs-8 text-uppercase">Vigencia</label>
                                                <input class="form-control form-control-solid" id="env_vigencia" type="date">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label text-muted fs-8 text-uppercase">Guia / referencia</label>
                                                <input class="form-control form-control-solid" id="env_guia" placeholder="Solo si ya se contrato">
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label text-muted fs-8 text-uppercase">Notas</label>
                                                <textarea class="form-control form-control-solid" id="env_notas" rows="3" placeholder="Condiciones, fragilidad, acuerdo con cliente"></textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="env-card p-4">
                                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                                            <div>
                                                <div class="fw-bold fs-5">Resumen</div>
                                                <div class="d-flex flex-wrap gap-2 mt-2" id="env_resumen_chips"></div>
                                            </div>
                                            <div class="text-end">
                                                <div class="text-muted fs-8 text-uppercase">Total estimado</div>
                                                <div class="fw-bold fs-2" id="env_total">$0.00</div>
                                            </div>
                                        </div>
                                        <div class="separator my-4"></div>
                                        <div class="row g-3">
                                            <div class="col-md-4">
                                                <div class="env-chip">
                                                    <div class="text-muted fs-8 text-uppercase">Productos</div>
                                                    <div class="fw-bold" id="env_total_productos">$0.00</div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="env-chip">
                                                    <div class="text-muted fs-8 text-uppercase">Envio cliente</div>
                                                    <div class="fw-bold" id="env_total_envio">$0.00</div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="env-chip">
                                                    <div class="text-muted fs-8 text-uppercase">Peso volumetrico</div>
                                                    <div class="fw-bold" id="env_volumetrico">0.00 kg</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="d-flex flex-wrap justify-content-end gap-2 mt-4">
                                            <button class="btn btn-light" id="env_copiar_resumen" type="button"><i class="bi bi-clipboard"></i> Copiar resumen</button>
                                            <button class="btn btn-light-danger" id="env_eliminar" type="button"><i class="bi bi-trash"></i> Eliminar borrador</button>
                                            <button class="btn btn-success" id="env_marcar_enviada" type="button"><i class="bi bi-send"></i> Marcar enviada</button>
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
<script src="/assets/js/custom/apps/erp/ventas/envios_foraneos.js?v=20261004-operativo1"></script>
</body>
</html>
