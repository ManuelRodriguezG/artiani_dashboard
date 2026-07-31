<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../">
    <title>Pendientes venta rapida POS</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css">
    <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css">
    <!--
      Documentacion IA: Codex GPT-5, 2026-07-30.
      Proposito: resolver pendientes de venta rapida POS desde una pantalla operativa.
      Impacto: Catalogo puede vincular ventas cobradas a SKU existente y dejar trazabilidad para Inventario.
      Contrato: no crea SKU nuevo; la resolucion real requiere permiso, token y confirmacion backend.
    -->
    <style>
        .vrp-card { border: 1px solid #e6e8ee; border-radius: 8px; background: #fff; }
        .vrp-list { max-height: 620px; overflow: auto; }
        .vrp-detail { min-height: 420px; }
        .vrp-empty { min-height: 220px; border: 1px dashed #d7dbe4; border-radius: 8px; }
        .vrp-pending-row { cursor: pointer; }
        .vrp-pending-row:hover { background: #f7f9fc; }
        .vrp-pill { border-radius: 6px; }
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
                                <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Pendientes venta rapida POS</h1>
                                <span class="text-muted">Productos vendidos en POS que deben clasificarse en Catalogo ERP</span>
                                <div class="d-flex flex-wrap gap-2 mt-2">
                                    <span class="badge badge-light-primary">Solo ventas cobradas</span>
                                    <span class="badge badge-light-warning">No crea SKU automatico</span>
                                    <span class="badge badge-light-info">No mueve kardex al clasificar</span>
                                </div>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <a class="btn btn-light" href="/ventas/mostrar"><i class="bi bi-receipt"></i> Ventas</a>
                                <a class="btn btn-light-primary" href="/catalogoerp"><i class="bi bi-box-seam"></i> Catalogo</a>
                                <a class="btn btn-light-primary" href="/ventas/manual_pos#manual-venta-rapida"><i class="bi bi-question-circle"></i> Manual</a>
                                <a class="btn btn-primary" href="/ventas/pos"><i class="bi bi-shop-window"></i> POS</a>
                            </div>
                        </div>
                    </div>
                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <div class="alert alert-info py-3 mb-4">
                                <div class="fw-bold">Flujo correcto</div>
                                <div class="fs-7">Si el producto ya existe, vincula el pendiente al SKU correcto. Si no existe, crealo primero en Catalogo ERP y regresa a esta bandeja para cerrar el pendiente.</div>
                            </div>
                            <div class="row g-4">
                                <div class="col-xxl-4 col-xl-5">
                                    <div class="vrp-card p-4 mb-4">
                                        <div class="fw-bold fs-5 mb-1">Filtros</div>
                                        <div class="text-muted fs-7 mb-4">Busca por folio, descripcion, codigo, marca o proveedor provisional</div>
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label text-muted fs-8 text-uppercase">Estatus</label>
                                                <select class="form-select form-select-solid" id="vrp_estatus">
                                                    <option value="pendiente_catalogo">Pendiente</option>
                                                    <option value="en_revision">En revision</option>
                                                    <option value="clasificado">Clasificado</option>
                                                    <option value="todos">Todos</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label text-muted fs-8 text-uppercase">Inventario</label>
                                                <select class="form-select form-select-solid" id="vrp_inventario">
                                                    <option value="todos">Todos</option>
                                                    <option value="pendiente_regularizacion">Pendiente regularizacion</option>
                                                    <option value="no_inventariable_provisional">No inventariable provisional</option>
                                                    <option value="no_inventariable_resuelto">No inventariable resuelto</option>
                                                </select>
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label text-muted fs-8 text-uppercase">Buscar</label>
                                                <input class="form-control form-control-solid" id="vrp_busqueda" placeholder="VRP, descripcion, codigo o proveedor">
                                            </div>
                                            <div class="col-12 d-grid">
                                                <button class="btn btn-primary" id="vrp_consultar" type="button"><i class="bi bi-search"></i> Consultar pendientes</button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="vrp-card p-4">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <div>
                                                <div class="fw-bold fs-5">Pendientes</div>
                                                <div class="text-muted fs-7" id="vrp_resumen">Sin consultar</div>
                                            </div>
                                            <button class="btn btn-sm btn-light-primary" id="vrp_recargar" type="button"><i class="bi bi-arrow-clockwise"></i></button>
                                        </div>
                                        <div id="vrp_lista" class="vrp-list"></div>
                                    </div>
                                </div>
                                <div class="col-xxl-8 col-xl-7">
                                    <div class="vrp-card p-4 vrp-detail">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <div>
                                                <div class="fw-bold fs-5">Detalle y resolucion</div>
                                                <div class="text-muted fs-7">Revisa evidencia antes de vincular a un SKU definitivo</div>
                                            </div>
                                            <a class="btn btn-sm btn-light-primary" href="/catalogoerp" target="_blank"><i class="bi bi-box-seam"></i> Abrir Catalogo</a>
                                        </div>
                                        <div id="vrp_detalle"></div>
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
<script>
window.VRP_FOLIO_INICIAL = <?= json_encode(isset($_GET["folio"]) ? trim((string) $_GET["folio"]) : "", JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="assets/plugins/global/plugins.bundle.js"></script>
<script src="assets/js/scripts.bundle.js"></script>
<script src="/assets/js/custom/apps/erp/ventas/venta_rapida_pendientes.js?v=20260730-borrador-catalogo1"></script>
</body>
</html>
