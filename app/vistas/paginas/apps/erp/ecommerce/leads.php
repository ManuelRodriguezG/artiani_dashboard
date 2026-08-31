<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../../">
    <title>Ecommerce - Leads y carritos</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css">
    <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css">
    <!--
      Documentacion IA: Codex GPT-5, 2026-08-31.
      Proposito: bandeja interna para Ecommerce Leads / Carritos.
      Impacto: seguimiento comercial sin crear pedidos, ventas, cotizaciones reales ni inventario.
      Contrato: consume endpoints internos protegidos; las acciones son planes read-only.
    -->
    <style>
        .ecom-lead-kpi { border: 1px solid #e7e9ef; border-radius: 8px; background: #fff; padding: 16px; min-height: 100px; }
        .ecom-lead-kpi__value { font-size: 1.8rem; line-height: 1; font-weight: 800; color: #181c32; letter-spacing: 0; }
        .ecom-lead-kpi__label { color: #7e8299; font-size: .78rem; text-transform: uppercase; font-weight: 700; }
        .ecom-lead-empty { border: 1px dashed #d8dce6; border-radius: 8px; background: #fbfcfe; }
        .ecom-lead-code { white-space: pre-wrap; word-break: break-word; max-height: 420px; overflow: auto; }
        .ecom-lead-summary { border: 1px solid #edf0f6; border-radius: 8px; padding: 12px; background: #fff; min-height: 74px; }
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
                                <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Leads / Carritos ecommerce</h1>
                                <span class="text-muted">Intencion comercial capturada desde carrito, contacto, facturacion y WhatsApp</span>
                            </div>
                            <div class="d-flex gap-2">
                                <a class="btn btn-light" href="/ecommercePublico/analytics"><i class="bi bi-graph-up"></i> Analytics</a>
                                <a class="btn btn-light-primary" href="/ecommercePublico/cotizaciones"><i class="bi bi-chat-dots"></i> Cotizaciones</a>
                                <button class="btn btn-primary" type="button" id="ecom_leads_recargar"><i class="bi bi-arrow-clockwise"></i> Recargar</button>
                            </div>
                        </div>
                    </div>
                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <div class="alert alert-warning d-flex align-items-start gap-3">
                                <i class="bi bi-shield-lock fs-2"></i>
                                <div>
                                    <div class="fw-bold">Seguimiento comercial, no checkout</div>
                                    <div>Esta bandeja no crea pedidos, ventas ni movimientos de inventario. Los datos aparecen cuando el DDL y `ECOMMERCE_LEADS_PUBLICO` esten activos.</div>
                                </div>
                            </div>

                            <div class="row g-4 mb-5">
                                <div class="col-md-3"><div class="ecom-lead-kpi"><div class="ecom-lead-kpi__label">En pagina</div><div class="ecom-lead-kpi__value" id="ecom_leads_kpi_total">0</div><div class="text-muted fs-7 mt-2">Leads visibles.</div></div></div>
                                <div class="col-md-3"><div class="ecom-lead-kpi"><div class="ecom-lead-kpi__label">Anonimos</div><div class="ecom-lead-kpi__value" id="ecom_leads_kpi_anonimos">0</div><div class="text-muted fs-7 mt-2">Solo session hash.</div></div></div>
                                <div class="col-md-3"><div class="ecom-lead-kpi"><div class="ecom-lead-kpi__label">Con contacto</div><div class="ecom-lead-kpi__value" id="ecom_leads_kpi_contacto">0</div><div class="text-muted fs-7 mt-2">Cliente escribio datos.</div></div></div>
                                <div class="col-md-3"><div class="ecom-lead-kpi"><div class="ecom-lead-kpi__label">WhatsApp</div><div class="ecom-lead-kpi__value" id="ecom_leads_kpi_whatsapp">0</div><div class="text-muted fs-7 mt-2">Intentos abiertos.</div></div></div>
                            </div>

                            <div class="card mb-5">
                                <div class="card-header border-0 pt-6">
                                    <div class="card-title gap-3 flex-wrap">
                                        <input class="form-control form-control-solid w-275px" id="ecom_leads_q" placeholder="Buscar contacto o session hash">
                                        <select class="form-select form-select-solid w-225px" id="ecom_leads_estatus">
                                            <option value="">Todos los estatus</option>
                                            <option value="anonimo_activo">Anonimo activo</option>
                                            <option value="contacto_pendiente">Contacto pendiente</option>
                                            <option value="whatsapp_generado">WhatsApp generado</option>
                                            <option value="whatsapp_abierto">WhatsApp abierto</option>
                                            <option value="abandonado">Abandonado</option>
                                            <option value="en_seguimiento">En seguimiento</option>
                                            <option value="convertido">Convertido</option>
                                            <option value="descartado">Descartado</option>
                                        </select>
                                    </div>
                                    <div class="card-toolbar">
                                        <span class="badge badge-light-primary" id="ecom_leads_estado">Listo</span>
                                    </div>
                                </div>
                                <div class="card-body pt-0">
                                    <div class="table-responsive">
                                        <table class="table align-middle table-row-dashed fs-7 gy-4">
                                            <thead>
                                                <tr class="text-start text-muted fw-bold text-uppercase">
                                                    <th>Fecha</th>
                                                    <th>Sesion / contacto</th>
                                                    <th>Productos</th>
                                                    <th>Etapa</th>
                                                    <th class="text-end">Total</th>
                                                    <th>Ultima URL</th>
                                                    <th class="text-end">Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody id="ecom_leads_body"></tbody>
                                        </table>
                                    </div>
                                    <div class="ecom-lead-empty p-5 text-center text-muted d-none" id="ecom_leads_empty">Aun no hay carritos/leads registrados o el esquema no esta activo.</div>
                                </div>
                            </div>

                            <div class="card">
                                <div class="card-header border-0 pt-6">
                                    <div class="card-title">
                                        <div>
                                            <h3 class="fw-bold mb-1">Detalle</h3>
                                            <span class="text-muted fs-7">Snapshot del carrito, eventos, notas y resumen para WhatsApp.</span>
                                        </div>
                                    </div>
                                    <div class="card-toolbar">
                                        <button class="btn btn-sm btn-light-primary d-none" type="button" id="ecom_leads_copiar"><i class="bi bi-copy"></i> Copiar resumen</button>
                                    </div>
                                </div>
                                <div class="card-body pt-0" id="ecom_leads_detalle">
                                    <div class="text-muted py-4">Selecciona un lead para ver su detalle.</div>
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
<script src="/assets/js/custom/apps/erp/ecommerce/leads.js?v=20260831-readonly1"></script>
</body>
</html>
