<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../../">
    <title>Ecommerce publico - Cotizaciones</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css">
    <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css">
    <!--
      Documentacion IA: Codex GPT-5, 2026-07-29.
      Proposito: bandeja interna read-only para cotizaciones ecommerce.
      Impacto: seguimiento operativo futuro sin registrar cotizaciones, pedidos, ventas ni inventario.
      Contrato: consume endpoints internos protegidos y solo muestra datos existentes.
    -->
    <style>
        .ecom-kpi { border: 1px solid #e7e9ef; border-radius: 8px; background: #fff; padding: 16px; min-height: 100px; }
        .ecom-kpi__value { font-size: 1.8rem; line-height: 1; font-weight: 800; color: #181c32; letter-spacing: 0; }
        .ecom-kpi__label { color: #7e8299; font-size: .78rem; text-transform: uppercase; font-weight: 700; }
        .ecom-empty { border: 1px dashed #d8dce6; border-radius: 8px; background: #fbfcfe; }
        .ecom-code { white-space: pre-wrap; word-break: break-word; }
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
                                <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Cotizaciones ecommerce</h1>
                                <span class="text-muted">Bandeja read-only para seguimiento futuro de carritos enviados por WhatsApp</span>
                            </div>
                            <div class="d-flex gap-2">
                                <a class="btn btn-light-primary" href="/ecommercePublico/publicaciones"><i class="bi bi-grid"></i> Publicaciones</a>
                                <button class="btn btn-primary" type="button" id="ecom_cotizaciones_recargar"><i class="bi bi-arrow-clockwise"></i> Recargar</button>
                            </div>
                        </div>
                    </div>
                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <div class="alert alert-info d-flex align-items-start gap-3">
                                <i class="bi bi-info-circle fs-2"></i>
                                <div>
                                    <div class="fw-bold">Fase 1: seguimiento, no checkout</div>
                                    <div>Esta pantalla no registra cotizaciones, no cambia estatus, no crea pedidos, no crea ventas y no descuenta inventario.</div>
                                </div>
                            </div>

                            <div class="row g-4 mb-5">
                                <div class="col-md-3"><div class="ecom-kpi"><div class="ecom-kpi__label">En pagina</div><div class="ecom-kpi__value" id="ecom_cot_kpi_total">0</div><div class="text-muted fs-7 mt-2">Cotizaciones visibles.</div></div></div>
                                <div class="col-md-3"><div class="ecom-kpi"><div class="ecom-kpi__label">Nuevas</div><div class="ecom-kpi__value" id="ecom_cot_kpi_nuevas">0</div><div class="text-muted fs-7 mt-2">Pendientes de revisar.</div></div></div>
                                <div class="col-md-3"><div class="ecom-kpi"><div class="ecom-kpi__label">Seguimiento</div><div class="ecom-kpi__value" id="ecom_cot_kpi_seguimiento">0</div><div class="text-muted fs-7 mt-2">Conversaciones abiertas.</div></div></div>
                                <div class="col-md-3"><div class="ecom-kpi"><div class="ecom-kpi__label">Convertidas</div><div class="ecom-kpi__value" id="ecom_cot_kpi_convertidas">0</div><div class="text-muted fs-7 mt-2">Futuro pedido o venta.</div></div></div>
                            </div>

                            <div class="card mb-5">
                                <div class="card-header border-0 pt-6">
                                    <div class="card-title gap-3 flex-wrap">
                                        <input class="form-control form-control-solid w-250px" id="ecom_cot_q" placeholder="Buscar folio, telefono o cliente">
                                        <select class="form-select form-select-solid w-200px" id="ecom_cot_estatus">
                                            <option value="">Todos los estatus</option>
                                            <option value="recibida_whatsapp">Recibida WhatsApp</option>
                                            <option value="en_seguimiento">En seguimiento</option>
                                            <option value="convertida_pedido">Convertida pedido</option>
                                            <option value="convertida_venta">Convertida venta</option>
                                            <option value="descartada">Descartada</option>
                                        </select>
                                    </div>
                                    <div class="card-toolbar">
                                        <span class="badge badge-light-primary" id="ecom_cot_estado">Listo</span>
                                    </div>
                                </div>
                                <div class="card-body pt-0">
                                    <div class="table-responsive">
                                        <table class="table align-middle table-row-dashed fs-7 gy-4">
                                            <thead>
                                                <tr class="text-start text-muted fw-bold text-uppercase">
                                                    <th>Folio</th>
                                                    <th>Contacto</th>
                                                    <th>Estatus</th>
                                                    <th class="text-end">Total</th>
                                                    <th class="text-end">Partidas</th>
                                                    <th>Fecha</th>
                                                    <th class="text-end">Accion</th>
                                                </tr>
                                            </thead>
                                            <tbody id="ecom_cot_body"></tbody>
                                        </table>
                                    </div>
                                    <div class="ecom-empty p-5 text-center text-muted d-none" id="ecom_cot_empty">Aun no hay cotizaciones ecommerce registradas.</div>
                                </div>
                            </div>

                            <div class="card">
                                <div class="card-header border-0 pt-6">
                                    <div class="card-title">
                                        <div>
                                            <h3 class="fw-bold mb-1">Detalle read-only</h3>
                                            <span class="text-muted fs-7">Snapshots y eventos de la cotizacion seleccionada.</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body pt-0" id="ecom_cot_detalle">
                                    <div class="text-muted py-4">Selecciona una cotizacion para ver su detalle.</div>
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
<script src="/assets/js/custom/apps/erp/ecommerce/cotizaciones.js?v=20260729-readonly1"></script>
</body>
</html>
