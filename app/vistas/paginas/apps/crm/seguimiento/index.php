<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../">
    <title>CRM Seguimiento</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css">
    <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css">
    <!--
      Documentacion IA: Codex GPT-5, 2026-06-30.
      Proposito: consola CRM Seguimiento read-only para tareas e interacciones.
      Impacto: separa seguimiento operativo del listado de clientes.
      Contrato: no crea tareas/interacciones; solo consume endpoints de lectura.
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
                                <h1 class="page-heading text-dark fw-bold fs-3 mb-1">CRM Seguimiento</h1>
                                <span class="text-muted">Tareas pendientes, historial de interacciones y estado operativo de seguimiento</span>
                            </div>
                            <button type="button" class="btn btn-light-primary" id="crm_seguimiento_recargar">
                                <i class="bi bi-arrow-clockwise"></i> Actualizar
                            </button>
                        </div>
                    </div>
                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <div id="crm_seguimiento_alerta" class="mb-4"></div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-3">
                                    <div class="crm-kpi p-4">
                                        <div class="text-muted fs-8 text-uppercase">Pendientes</div>
                                        <div class="fw-bold fs-2" id="crm_seg_kpi_pendientes">0</div>
                                        <div class="text-muted fs-8">Tareas abiertas</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="crm-kpi p-4">
                                        <div class="text-muted fs-8 text-uppercase">Vencidas</div>
                                        <div class="fw-bold fs-2" id="crm_seg_kpi_vencidas">0</div>
                                        <div class="text-muted fs-8">Requieren atencion</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="crm-kpi p-4">
                                        <div class="text-muted fs-8 text-uppercase">Alta prioridad</div>
                                        <div class="fw-bold fs-2" id="crm_seg_kpi_alta">0</div>
                                        <div class="text-muted fs-8">Alta o urgente</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="crm-kpi p-4">
                                        <div class="text-muted fs-8 text-uppercase">Interacciones</div>
                                        <div class="fw-bold fs-2" id="crm_seg_kpi_interacciones">0</div>
                                        <div class="text-muted fs-8">Ultimas consultadas</div>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-4">
                                <div class="col-xl-7">
                                    <div class="crm-panel">
                                        <div class="p-4 border-bottom d-flex justify-content-between align-items-center">
                                            <div>
                                                <div class="fw-bold">Tareas de seguimiento</div>
                                                <div class="text-muted fs-7">Bandeja operativa en modo lectura</div>
                                            </div>
                                            <select id="crm_seg_tareas_estatus" class="form-select form-select-sm w-auto">
                                                <option value="pendiente">Pendientes</option>
                                                <option value="en_proceso">En proceso</option>
                                                <option value="cerrada">Cerradas</option>
                                                <option value="cancelada">Canceladas</option>
                                                <option value="todas">Todas</option>
                                            </select>
                                        </div>
                                        <div class="p-4 border-bottom" id="crm_seg_tareas_resumen"></div>
                                        <div class="table-responsive crm-scroll">
                                            <table class="table align-middle table-row-dashed gy-3 mb-0">
                                                <thead>
                                                    <tr class="text-muted fw-bold fs-8 text-uppercase">
                                                        <th>Tarea</th>
                                                        <th>Cliente</th>
                                                        <th>Vencimiento</th>
                                                        <th class="text-end">Accion</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="crm_seg_tareas_tabla"></tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xl-5">
                                    <div class="crm-panel">
                                        <div class="p-4 border-bottom d-flex justify-content-between align-items-center">
                                            <div>
                                                <div class="fw-bold">Interacciones recientes</div>
                                                <div class="text-muted fs-7">Contactos y seguimientos registrados</div>
                                            </div>
                                            <a href="/crm/clientes#crm_tab_clientes" class="btn btn-sm btn-light-primary">
                                                <i class="bi bi-people"></i> Clientes
                                            </a>
                                        </div>
                                        <div class="p-4 crm-scroll" id="crm_seg_interacciones_lista"></div>
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
<script src="/assets/js/custom/apps/crm/seguimiento/index.js?v=20260630-1"></script>
</body>
</html>
