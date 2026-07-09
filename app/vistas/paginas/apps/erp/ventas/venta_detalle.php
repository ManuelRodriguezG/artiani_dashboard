<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../">
    <title>Detalle venta POS</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css">
    <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css">
    <!--
      Documentacion IA: Codex GPT-5, 2026-07-02.
      Proposito: mostrar detalle read-only de venta POS ERP.
      Impacto: conecta ticket, pagos, garantia y trazabilidad sin escribir BD.
      Contrato: vista de consulta; acciones operativas se abren en modulos dedicados.
    -->
    <style>
        .venta-card { border: 1px solid #e6e8ee; border-radius: 8px; background: #fff; }
        .venta-ticket-pre { white-space: pre-wrap; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono", monospace; line-height: 1.35; }
        .venta-result { max-height: 520px; overflow: auto; }
        .venta-empty { min-height: 220px; border: 1px dashed #d7dbe4; border-radius: 8px; }
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
                                <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Detalle venta POS</h1>
                                <span class="text-muted">Ticket, pagos, garantias y trazabilidad de inventario</span>
                                <div class="d-flex flex-wrap gap-2 mt-2">
                                    <span class="badge badge-light-primary">Solo consulta</span>
                                    <span class="badge badge-light-info">Usa snapshots historicos</span>
                                    <span class="badge badge-light-warning">Acciones reales viven en modulos dedicados</span>
                                </div>
                            </div>
                            <div class="d-flex gap-2">
                                <a class="btn btn-light" href="/ventas/mostrar"><i class="bi bi-list-ul"></i> Ventas</a>
                                <a class="btn btn-light-warning" id="venta_detalle_devolucion" href="/ventas/devoluciones"><i class="bi bi-arrow-counterclockwise"></i> Devolucion</a>
                                <a class="btn btn-primary" href="/ventas/pos"><i class="bi bi-shop-window"></i> POS</a>
                            </div>
                        </div>
                    </div>
                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <div class="venta-card p-4 mb-4">
                                <div class="row g-3 align-items-end">
                                    <div class="col-lg-5">
                                        <label class="form-label text-muted fs-8 text-uppercase">Folio o id venta</label>
                                        <input class="form-control form-control-solid" id="venta_detalle_ref" placeholder="POS-...">
                                    </div>
                                    <div class="col-lg-2">
                                        <button class="btn btn-light-primary w-100" id="venta_detalle_consultar" type="button"><i class="bi bi-search"></i> Consultar</button>
                                    </div>
                                    <div class="col-lg-5">
                                        <div id="venta_detalle_alerta"></div>
                                    </div>
                                </div>
                            </div>
                            <div id="venta_detalle_contenido">
                                <div class="venta-empty d-flex align-items-center justify-content-center text-center text-muted">
                                    <div>
                                        <i class="bi bi-receipt fs-1 d-block mb-3"></i>
                                        <div class="fw-semibold">Captura o abre un folio de venta</div>
                                        <div class="fs-7">La consulta no modifica venta, caja, ticket ni inventario.</div>
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
<script src="/assets/js/custom/apps/erp/ventas/venta_detalle.js?v=20260702-readonly1"></script>
</body>
</html>
