<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../">
    <title>Correcciones historicas</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css">
    <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css">
    <!-- IA: Codex GPT-5 | 2026-10-22 | Auditoria read-only de almacenes por partida historica. -->
    <style>
        .vhc-card { border: 1px solid #e6e8ee; border-radius: 8px; background: #fff; }
        .vhc-table { min-width: 920px; }
        .vhc-table-wrap { overflow-x: auto; }
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
                                <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Correcciones historicas</h1>
                                <span class="text-muted">Revisa y propone el almacen correcto de cada partida sin usar reversa POS.</span>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <a class="btn btn-light" href="/ventas/mostrar"><i class="bi bi-receipt"></i> Ventas</a>
                                <a class="btn btn-light-primary" href="/ventas/carga_historica"><i class="bi bi-clock-history"></i> Carga historica</a>
                            </div>
                        </div>
                    </div>
                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <div id="vhc_alerta" class="mb-4"></div>
                            <div class="vhc-card p-5 mb-4">
                                <div class="row g-3 align-items-end">
                                    <div class="col-lg-6">
                                        <label class="form-label">Folio historico completo</label>
                                        <input class="form-control form-control-solid" id="vhc_folio" placeholder="VHIS-20261022-000011">
                                        <div class="form-text">Usa el folio completo. El sufijo 000011 puede coincidir con ventas POS antiguas.</div>
                                    </div>
                                    <div class="col-lg-3">
                                        <button class="btn btn-primary w-100" type="button" id="vhc_consultar"><i class="bi bi-search"></i> Consultar</button>
                                    </div>
                                    <div class="col-lg-3">
                                        <button class="btn btn-light-warning w-100" type="button" id="vhc_prevalidar" disabled><i class="bi bi-clipboard-check"></i> Prevalidar propuesta</button>
                                    </div>
                                </div>
                            </div>
                            <div class="alert alert-light-warning">
                                <div class="fw-bold">Esta pantalla no aplica cambios</div>
                                <div class="fs-7">La prevalidacion solo muestra el almacen original, el propuesto y los bloqueos. No cancela, no edita, no mueve caja, no modifica inventario y no genera kardex.</div>
                            </div>
                            <div class="vhc-card p-5">
                                <div class="d-flex justify-content-between align-items-center gap-3 mb-4">
                                    <div>
                                        <div class="fw-bold fs-4">Partidas de la venta</div>
                                        <div class="text-muted fs-7" id="vhc_resumen">Consulta un folio para revisar sus partidas.</div>
                                    </div>
                                    <span class="badge badge-light-info" id="vhc_estado">Solo lectura</span>
                                </div>
                                <div class="vhc-table-wrap">
                                    <table class="table align-middle table-row-dashed vhc-table">
                                        <thead><tr class="text-muted fw-bold fs-8 text-uppercase"><th>Partida</th><th>SKU</th><th>Almacen original</th><th>Almacen propuesto</th><th class="text-end">Importe</th></tr></thead>
                                        <tbody id="vhc_partidas"><tr><td colspan="5" class="text-center text-muted py-10">Aun no hay una venta consultada.</td></tr></tbody>
                                    </table>
                                </div>
                                <div id="vhc_resultado" class="alert alert-light-info mt-4 mb-0">La respuesta de la prevalidacion aparecera aqui.</div>
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
<script src="/assets/js/custom/apps/erp/ventas/correcciones_historicas.js?v=20261022-correcciones1"></script>
</body>
</html>
