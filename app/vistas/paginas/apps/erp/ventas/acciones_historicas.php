<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../">
    <title>Acciones de ventas historicas</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css">
    <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css">
    <!-- IA: Codex GPT-6 | Fecha: 2026-10-31 | Bandeja de revision de SKU historico sin efectos POS. -->
    <style>
        .vha-card{border:1px solid #e6e8ee;border-radius:8px;background:#fff}.vha-list{max-height:680px;overflow:auto}.vha-row{cursor:pointer}.vha-row:hover,.vha-row.active{background:#f2f6ff}.vha-detail{min-height:440px}
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
                                <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Acciones de ventas históricas</h1>
                                <span class="text-muted">Revisa productos vendidos como venta rápida y clasifícalos con Catálogo</span>
                                <div class="d-flex flex-wrap gap-2 mt-2">
                                    <span class="badge badge-light-primary">Conserva el precio y descripción de la venta</span>
                                    <span class="badge badge-light-info">No usa caja ni turno</span>
                                    <span class="badge badge-light-warning">No mueve inventario ni kardex</span>
                                </div>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <a class="btn btn-light" href="/ventas/mostrar"><i class="bi bi-receipt"></i> Ventas</a>
                                <a class="btn btn-light-primary" href="/ventas/carga_historica"><i class="bi bi-clock-history"></i> Carga histórica</a>
                                <a class="btn btn-primary" href="/catalogoerp"><i class="bi bi-box-seam"></i> Catálogo</a>
                            </div>
                        </div>
                    </div>
                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <div class="alert alert-info py-3 mb-4">
                                <div class="fw-bold">Flujo de revisión</div>
                                <div class="fs-7">Si encuentras el producto en Catálogo, selecciónalo y previsualiza la clasificación. Si no existe, solicita revisión a Catálogo; después regresa aquí para vincularlo. Si debe permanecer como venta rápida, ciérralo con un motivo. La partida histórica conserva su importe y almacén.</div>
                            </div>
                            <div class="row g-4">
                                <div class="col-xxl-4 col-xl-5">
                                    <div class="vha-card p-4 mb-4">
                                        <div class="fw-bold fs-5 mb-1">Filtros</div>
                                        <div class="text-muted fs-7 mb-4">Busca por folio, producto o estado</div>
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label text-muted fs-8 text-uppercase">Estado</label>
                                                <select class="form-select form-select-solid" id="vha_estatus">
                                                    <option value="abiertas">Pendientes</option>
                                                    <option value="todas">Todas</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label text-muted fs-8 text-uppercase">Folio</label>
                                                <input class="form-control form-control-solid" id="vha_folio" placeholder="VHIS-...">
                                            </div>
                                            <div class="col-12 d-grid"><button class="btn btn-primary" id="vha_consultar" type="button"><i class="bi bi-search"></i> Consultar</button></div>
                                        </div>
                                    </div>
                                    <div class="vha-card p-4">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <div><div class="fw-bold fs-5">Partidas por revisar</div><div class="text-muted fs-7" id="vha_resumen">Sin consultar</div></div>
                                            <button class="btn btn-sm btn-light-primary" id="vha_recargar" type="button" aria-label="Recargar"><i class="bi bi-arrow-clockwise"></i></button>
                                        </div>
                                        <div class="vha-list" id="vha_lista"></div>
                                    </div>
                                </div>
                                <div class="col-xxl-8 col-xl-7">
                                    <div class="vha-card p-4 vha-detail">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <div><div class="fw-bold fs-5">Detalle y clasificación</div><div class="text-muted fs-7">Revisa la partida original antes de asignar un SKU</div></div>
                                            <div class="badge badge-light-info">SKU y unidad base históricos</div>
                                        </div>
                                        <div id="vha_detalle"><div class="text-center text-muted py-10">Selecciona una partida pendiente</div></div>
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
window.VHA_NOTIFICACION_INICIAL = <?= json_encode(isset($_GET["notificacion"]) ? intval($_GET["notificacion"]) : 0) ?>;
window.VHA_DETALLE_INICIAL = <?= json_encode(isset($_GET["id_venta_detalle"]) ? intval($_GET["id_venta_detalle"]) : 0) ?>;
window.VHA_FOLIO_INICIAL = <?= json_encode(isset($_GET["folio"]) ? trim((string) $_GET["folio"]) : "", JSON_UNESCAPED_UNICODE) ?>;
window.VHA_PUEDE_CLASIFICAR = <?= !empty($datos["puede_clasificar"]) ? "true" : "false" ?>;
</script>
<script src="assets/plugins/global/plugins.bundle.js"></script>
<script src="assets/js/scripts.bundle.js"></script>
<script src="/assets/js/custom/apps/erp/ventas/acciones_historicas.js?v=20261101-baseunits1"></script>
</body>
</html>
