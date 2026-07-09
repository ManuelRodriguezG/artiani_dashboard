<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../">
    <title>CRM Recompensas</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css">
    <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css">
    <!--
      Documentacion IA: Codex GPT-5, 2026-06-30.
      Proposito: consola CRM Recompensas read-only.
      Impacto: separa programas, cuentas y movimientos de recompensas del listado de clientes.
      Contrato: no otorga puntos, no redime puntos y no modifica saldos.
    -->
    <style>
        .crm-panel { border: 1px solid #e6e8ee; border-radius: 8px; background: #fff; }
        .crm-kpi { border: 1px solid #e6e8ee; border-radius: 8px; background: #fff; min-height: 96px; }
        .crm-code { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono", monospace; }
        .crm-scroll { max-height: 520px; overflow: auto; }
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
                                <h1 class="page-heading text-dark fw-bold fs-3 mb-1">CRM Recompensas</h1>
                                <span class="text-muted">Programas, cuentas, saldos y movimientos en modo lectura</span>
                            </div>
                            <button type="button" class="btn btn-light-primary" id="crm_recompensas_recargar">
                                <i class="bi bi-arrow-clockwise"></i> Actualizar
                            </button>
                        </div>
                    </div>
                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <div id="crm_recompensas_alerta" class="mb-4"></div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-3">
                                    <div class="crm-kpi p-4">
                                        <div class="text-muted fs-8 text-uppercase">Programas activos</div>
                                        <div class="fw-bold fs-2" id="crm_rec_kpi_programas">0</div>
                                        <div class="text-muted fs-8">Disponibles en CRM</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="crm-kpi p-4">
                                        <div class="text-muted fs-8 text-uppercase">Cuentas activas</div>
                                        <div class="fw-bold fs-2" id="crm_rec_kpi_cuentas">0</div>
                                        <div class="text-muted fs-8">Clientes con cuenta</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="crm-kpi p-4">
                                        <div class="text-muted fs-8 text-uppercase">Saldo puntos</div>
                                        <div class="fw-bold fs-2" id="crm_rec_kpi_saldo">0</div>
                                        <div class="text-muted fs-8">Total acumulado</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="crm-kpi p-4">
                                        <div class="text-muted fs-8 text-uppercase">Movimientos</div>
                                        <div class="fw-bold fs-2" id="crm_rec_kpi_movimientos">0</div>
                                        <div class="text-muted fs-8">Aplicados</div>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-4">
                                <div class="col-xl-5">
                                    <div class="crm-panel mb-4">
                                        <div class="p-4 border-bottom">
                                            <div class="fw-bold">Programas</div>
                                            <div class="text-muted fs-7">Catalogo de programas de recompensas</div>
                                        </div>
                                        <div class="table-responsive crm-scroll">
                                            <table class="table align-middle table-row-dashed gy-3 mb-0">
                                                <thead>
                                                    <tr class="text-muted fw-bold fs-8 text-uppercase">
                                                        <th>Programa</th>
                                                        <th>Tipo</th>
                                                        <th>Estado</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="crm_rec_programas_tabla"></tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <div class="crm-panel">
                                        <div class="p-4 border-bottom">
                                            <div class="fw-bold">Elegibilidad</div>
                                            <div class="text-muted fs-7">Lectura de condiciones actuales</div>
                                        </div>
                                        <div class="p-4" id="crm_rec_elegibilidad"></div>
                                    </div>
                                </div>
                                <div class="col-xl-7">
                                    <div class="crm-panel mb-4">
                                        <div class="p-4 border-bottom">
                                            <div class="fw-bold">Cuentas</div>
                                            <div class="text-muted fs-7">Saldos por cliente y programa</div>
                                        </div>
                                        <div class="table-responsive crm-scroll">
                                            <table class="table align-middle table-row-dashed gy-3 mb-0">
                                                <thead>
                                                    <tr class="text-muted fw-bold fs-8 text-uppercase">
                                                        <th>Cliente</th>
                                                        <th>Programa</th>
                                                        <th>Saldo</th>
                                                        <th class="text-end">Accion</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="crm_rec_cuentas_tabla"></tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <div class="crm-panel">
                                        <div class="p-4 border-bottom">
                                            <div class="fw-bold">Movimientos recientes</div>
                                            <div class="text-muted fs-7">Lectura historica; no acumula ni redime puntos</div>
                                        </div>
                                        <div class="p-4 crm-scroll" id="crm_rec_movimientos_lista"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?= include_once '../app/vistas/includes/footer/footer.php'; ?>
            </div>
        </div>
    </div>
</div>
<script src="assets/plugins/global/plugins.bundle.js"></script>
<script src="assets/js/scripts.bundle.js"></script>
<script src="/assets/js/custom/apps/crm/recompensas/index.js?v=20260630-1"></script>
</body>
</html>
