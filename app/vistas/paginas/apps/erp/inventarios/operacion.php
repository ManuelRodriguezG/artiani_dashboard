<?php $modo = isset($datos["modo"]) && $datos["modo"] === "traspaso" ? "traspaso" : "ajuste"; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../">
    <title><?= $modo === "traspaso" ? "Traspaso de inventario" : "Inventario inicial / ajuste" ?></title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css">
    <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css">
    <style>
        .inventario-cantidad-control {
            width: 150px;
        }
        .inventario-cantidad-control .btn {
            width: 36px;
        }
        .inventario-cantidad-control input {
            min-width: 70px;
            text-align: center;
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
                    <div class="app-toolbar py-3 py-lg-6">
                        <div class="app-container container-fluid d-flex flex-stack">
                            <div>
                                <h1 class="page-heading text-dark fw-bold fs-3 mb-1"><?= $modo === "traspaso" ? "Traspaso entre almacenes" : "Inventario inicial / ajuste" ?></h1>
                                <span class="text-muted">Movimientos por SKU registrados directamente en el kardex ERP</span>
                            </div>
                            <a class="btn btn-light-primary" href="/inventario/productos_existencias"><i class="bi bi-boxes"></i> Existencias</a>
                        </div>
                    </div>
                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <div class="card mb-7">
                                <div class="card-body">
                                    <input type="hidden" id="inventario_modo" value="<?= $modo ?>">
                                    <div class="row g-5">
                                        <?php if ($modo === "ajuste"): ?>
                                        <div class="col-md-3"><label class="form-label required">Almacen</label><select class="form-select" id="inventario_almacen"></select></div>
                                        <div class="col-md-3"><label class="form-label required">Tipo</label><select class="form-select" id="inventario_tipo"><option value="entrada">Entrada</option><option value="salida">Salida</option></select></div>
                                        <div class="col-md-3"><label class="form-label required">Documento</label><select class="form-select" id="inventario_documento_operacion"><option value="inventario_inicial">Inventario inicial</option><option value="ajuste">Ajuste documentado</option></select></div>
                                        <div class="col-md-3"><label class="form-label">Ubicacion de entrada</label><select class="form-select" id="inventario_ubicacion"><option value="">Sin ubicacion</option></select></div>
                                        <div class="col-md-4"><label class="form-label required">Motivo</label><select class="form-select" id="inventario_motivo_ajuste"></select></div>
                                        <div class="col-md-6"><label class="form-label required">Referencia</label><input class="form-control" id="inventario_referencia" maxlength="120" placeholder="INV-INICIAL-YYYYMMDD-0001"></div>
                                        <?php else: ?>
                                        <div class="col-md-4"><label class="form-label required">Almacen origen</label><select class="form-select" id="inventario_almacen_origen"></select></div>
                                        <div class="col-md-4"><label class="form-label required">Almacen destino</label><select class="form-select" id="inventario_almacen_destino"></select></div>
                                        <div class="col-md-4"><label class="form-label">Ubicacion destino</label><select class="form-select" id="inventario_ubicacion_destino"><option value="">Sin ubicacion</option></select></div>
                                        <?php endif; ?>
                                        <div class="col-12"><label class="form-label">Observaciones</label><textarea class="form-control" id="inventario_observaciones" rows="2" maxlength="500"></textarea></div>
                                    </div>
                                </div>
                            </div>
                            <div class="card">
                                <div class="card-header border-0 pt-5">
                                    <div class="card-title"><h3 class="fw-bold mb-0">Partidas</h3></div>
                                    <div class="card-toolbar">
                                        <button class="btn btn-primary" id="inventario_aplicar" type="button"><i class="bi bi-check-lg"></i> Aplicar movimiento</button>
                                    </div>
                                </div>
                                <div class="card-body pt-3">
                                    <div class="position-relative mb-5">
                                        <i class="bi bi-search fs-3 position-absolute ms-5 mt-3"></i>
                                        <input class="form-control form-control-solid ps-12" id="inventario_buscar_sku" placeholder="Buscar por SKU, producto o codigo" autocomplete="off">
                                        <div class="position-absolute start-0 end-0 bg-white border shadow-sm d-none" id="inventario_resultados" style="z-index:10;max-height:320px;overflow:auto"></div>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table align-middle table-row-dashed gy-4 mb-0">
                                            <thead><tr class="text-muted fw-bold fs-7 text-uppercase"><th>SKU / producto</th><th>Disponible</th><th style="width:150px">Cantidad</th><th style="width:260px">Existencia / lote</th><th style="width:170px">Caducidad</th><th class="text-end" style="width:70px"></th></tr></thead>
                                            <tbody id="inventario_partidas"></tbody>
                                        </table>
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
<script src="/assets/js/custom/apps/erp/inventarios/operacion_erp.js?v=20260709-1"></script>
</body>
</html>
