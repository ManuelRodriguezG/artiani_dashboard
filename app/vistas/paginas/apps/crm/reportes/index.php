<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../">
    <title>CRM Reportes</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css">
    <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css">
    <!--
      Documentacion IA: Codex GPT-5, 2026-07-30.
      Proposito: tablero CRM Reportes read-only.
      Impacto: separa indicadores y calidad CRM de la consola operativa de clientes.
      Contrato: no modifica clientes, tareas, recompensas ni condiciones comerciales.
    -->
    <style>
        .crm-panel { border: 1px solid #e6e8ee; border-radius: 8px; background: #fff; }
        .crm-kpi { border: 1px solid #e6e8ee; border-radius: 8px; background: #fff; min-height: 112px; }
        .crm-meter { height: 8px; border-radius: 999px; background: #eef1f6; overflow: hidden; }
        .crm-meter > span { display: block; height: 100%; border-radius: 999px; background: #3e97ff; }
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
                                <h1 class="page-heading text-dark fw-bold fs-3 mb-1">CRM Reportes</h1>
                                <span class="text-muted">Contactabilidad, calidad operativa, elegibilidad comercial y preparacion postventa</span>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="/crm/clientes#crm_tab_clientes" class="btn btn-light">
                                    <i class="bi bi-people"></i> Clientes
                                </a>
                                <button type="button" class="btn btn-light-primary" id="crm_reportes_recargar">
                                    <i class="bi bi-arrow-clockwise"></i> Actualizar
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <div id="crm_reportes_alerta" class="mb-4"></div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-4 col-xl-2">
                                    <div class="crm-kpi p-4">
                                        <div class="text-muted fs-8 text-uppercase">Identificables POS</div>
                                        <div class="fw-bold fs-2" id="crm_rep_kpi_identificables">0</div>
                                        <div class="text-muted fs-8">Clientes listos para venta</div>
                                    </div>
                                </div>
                                <div class="col-md-4 col-xl-2">
                                    <div class="crm-kpi p-4">
                                        <div class="text-muted fs-8 text-uppercase">Contacto pendiente</div>
                                        <div class="fw-bold fs-2" id="crm_rep_kpi_contacto">0</div>
                                        <div class="text-muted fs-8">Fichas a completar</div>
                                    </div>
                                </div>
                                <div class="col-md-4 col-xl-2">
                                    <div class="crm-kpi p-4">
                                        <div class="text-muted fs-8 text-uppercase">Consentimiento</div>
                                        <div class="fw-bold fs-2" id="crm_rep_kpi_consentimiento">0</div>
                                        <div class="text-muted fs-8">Pendiente comercial</div>
                                    </div>
                                </div>
                                <div class="col-md-4 col-xl-2">
                                    <div class="crm-kpi p-4">
                                        <div class="text-muted fs-8 text-uppercase">Bloqueo comercial</div>
                                        <div class="fw-bold fs-2" id="crm_rep_kpi_bloqueados">0</div>
                                        <div class="text-muted fs-8">Requiere revision</div>
                                    </div>
                                </div>
                                <div class="col-md-4 col-xl-2">
                                    <div class="crm-kpi p-4">
                                        <div class="text-muted fs-8 text-uppercase">Recompensas</div>
                                        <div class="fw-bold fs-2" id="crm_rep_kpi_recompensas">0</div>
                                        <div class="text-muted fs-8">Elegibles</div>
                                    </div>
                                </div>
                                <div class="col-md-4 col-xl-2">
                                    <div class="crm-kpi p-4">
                                        <div class="text-muted fs-8 text-uppercase">Garantia extendida</div>
                                        <div class="fw-bold fs-2" id="crm_rep_kpi_garantia">0</div>
                                        <div class="text-muted fs-8">Elegibles futuro</div>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-4">
                                <div class="col-xl-8">
                                    <div class="crm-panel">
                                        <div class="p-4 border-bottom">
                                            <div class="fw-bold">Indicadores operativos</div>
                                            <div class="text-muted fs-7">Lectura de salud CRM; no genera tareas ni cambia clientes</div>
                                        </div>
                                        <div class="p-4" id="crm_rep_indicadores"></div>
                                    </div>
                                </div>
                                <div class="col-xl-4">
                                    <div class="crm-panel mb-4">
                                        <div class="p-4 border-bottom">
                                            <div class="fw-bold">Estado comercial</div>
                                            <div class="text-muted fs-7">Disponibilidad de condiciones CRM</div>
                                        </div>
                                        <div class="p-4" id="crm_rep_estado"></div>
                                    </div>
                                    <div class="crm-panel">
                                        <div class="p-4 border-bottom">
                                            <div class="fw-bold">Acciones naturales</div>
                                            <div class="text-muted fs-7">Navegacion a modulos responsables</div>
                                        </div>
                                        <div class="p-4 d-grid gap-2">
                                            <a href="/crm/seguimiento" class="btn btn-light-primary text-start"><i class="bi bi-list-task"></i> Revisar seguimiento</a>
                                            <a href="/crm/clientes#crm_tab_comercial" class="btn btn-light-primary text-start"><i class="bi bi-tags"></i> Revisar comercial</a>
                                            <a href="/crm/recompensas" class="btn btn-light-primary text-start"><i class="bi bi-stars"></i> Revisar recompensas</a>
                                        </div>
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
<script src="/assets/js/custom/apps/crm/reportes/index.js?v=20260730-1"></script>
</body>
</html>
