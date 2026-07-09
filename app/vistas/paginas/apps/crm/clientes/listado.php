<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../">
    <title>CRM Clientes</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css">
    <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css">
    <!--
      Documentacion IA: Codex GPT-5, 2026-06-29.
      Proposito: crear consola CRM Clientes read-only para operacion, calidad y auditoria.
      Impacto: separa CRM de POS/Ventas y evita tratar legacy como base comercial limpia.
      Contrato: no ejecuta DDL ni migraciones; solo consume endpoints de diagnostico.
    -->
    <style>
        .crm-kpi { border: 1px solid #e6e8ee; border-radius: 8px; background: #fff; min-height: 96px; }
        .crm-panel { border: 1px solid #e6e8ee; border-radius: 8px; background: #fff; }
        .crm-soft { background: #f7f8fa; border: 1px solid #e6e8ee; border-radius: 8px; }
        .crm-code { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono", monospace; }
        .crm-scroll { max-height: 430px; overflow: auto; }
        .crm-workspace-tabs { background: #fff; border: 1px solid #e6e8ee; border-radius: 8px; padding: 0 12px; }
        .crm-workspace-tabs .nav-link { min-height: 52px; display: inline-flex; align-items: center; gap: 8px; white-space: nowrap; }
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
                                <h1 class="page-heading text-dark fw-bold fs-3 mb-1">CRM Clientes</h1>
                                <span class="text-muted">Clientes canonicos, busqueda rapida, calidad operativa y auditoria legacy controlada</span>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-light-primary" id="crm_clientes_recargar">
                                    <i class="bi bi-arrow-clockwise"></i> Actualizar
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <div id="crm_clientes_alerta" class="mb-4"></div>
                            <div class="alert alert-primary d-flex align-items-start gap-3 mb-4">
                                <i class="bi bi-info-circle fs-3"></i>
                                <div>
                                    <div class="fw-bold">CRM inicia desde clientes nuevos reales.</div>
                                    <div>Legacy queda como auditoria historica; no alimenta campanas, recompensas ni segmentacion sin revision puntual.</div>
                                </div>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-3">
                                    <div class="crm-kpi p-4">
                                        <div class="text-muted fs-8 text-uppercase">Legacy auditado</div>
                                        <div class="fw-bold fs-2" id="crm_kpi_legacy">0</div>
                                        <div class="text-muted fs-8">Solo referencia historica</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="crm-kpi p-4">
                                        <div class="text-muted fs-8 text-uppercase">POS/UAT</div>
                                        <div class="fw-bold fs-2" id="crm_kpi_erp">0</div>
                                        <div class="text-muted fs-8">No canonicos</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="crm-kpi p-4">
                                        <div class="text-muted fs-8 text-uppercase">Duplicados</div>
                                        <div class="fw-bold fs-2" id="crm_kpi_duplicados">0</div>
                                        <div class="text-muted fs-8">Grupos por identificador</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="crm-kpi p-4">
                                        <div class="text-muted fs-8 text-uppercase">DDL CRM</div>
                                        <div class="fw-bold fs-2" id="crm_kpi_ddl">0</div>
                                        <div class="text-muted fs-8">Tablas pendientes</div>
                                    </div>
                                </div>
                            </div>

                            <div class="crm-soft p-4 mb-4">
                                <div class="row g-3 align-items-end">
                                    <div class="col-lg-4">
                                        <label class="form-label text-muted fs-8 text-uppercase">Busqueda express</label>
                                        <div class="position-relative">
                                            <i class="bi bi-search fs-3 position-absolute ms-4 mt-3"></i>
                                            <input type="text" id="crm_busqueda_q" class="form-control form-control-solid ps-12" placeholder="Telefono, correo, codigo o nombre">
                                        </div>
                                    </div>
                                    <div class="col-lg-2">
                                        <button type="button" class="btn btn-light-primary w-100" id="crm_buscar">
                                            <i class="bi bi-search"></i> Buscar
                                        </button>
                                    </div>
                                    <div class="col-lg-6">
                                        <div id="crm_busqueda_resultado"></div>
                                    </div>
                                </div>
                            </div>

                            <ul class="nav nav-tabs nav-line-tabs nav-line-tabs-2x crm-workspace-tabs mb-4 overflow-auto flex-nowrap" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link active" data-bs-toggle="tab" href="#crm_tab_operacion" role="tab">
                                        <i class="bi bi-list-check"></i> Operacion
                                    </a>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link" data-bs-toggle="tab" href="#crm_tab_comercial" role="tab">
                                        <i class="bi bi-graph-up-arrow"></i> Comercial
                                    </a>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link" data-bs-toggle="tab" href="#crm_tab_recompensas" role="tab">
                                        <i class="bi bi-award"></i> Recompensas
                                    </a>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link" data-bs-toggle="tab" href="#crm_tab_clientes" role="tab">
                                        <i class="bi bi-people"></i> Clientes
                                    </a>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <a class="nav-link" data-bs-toggle="tab" href="#crm_tab_auditoria" role="tab">
                                        <i class="bi bi-shield-check"></i> Auditoria legacy
                                    </a>
                                </li>
                            </ul>

                            <div class="tab-content" id="crm_clientes_workspace">
                                <div class="tab-pane fade show active" id="crm_tab_operacion" role="tabpanel">

                            <div class="crm-panel mb-4">
                                <div class="p-4 border-bottom d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-bold">Cola de calidad operativa</div>
                                        <div class="text-muted fs-7">Prioriza fichas incompletas sin crear tareas ni escribir BD</div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-light-primary" id="crm_calidad_recargar"><i class="bi bi-arrow-clockwise"></i> Recargar</button>
                                </div>
                                <div class="p-4 border-bottom" id="crm_calidad_resumen"></div>
                                <div class="px-4 pt-4" id="crm_tarea_dryrun_resultado"></div>
                                <div class="table-responsive">
                                    <table class="table align-middle table-row-dashed gy-3 mb-0">
                                        <thead>
                                            <tr class="text-muted fw-bold fs-8 text-uppercase">
                                                <th>Cliente</th>
                                                <th>Prioridad</th>
                                                <th>Estado CRM</th>
                                                <th class="text-end">Accion</th>
                                            </tr>
                                        </thead>
                                        <tbody id="crm_calidad_tabla"></tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="crm-panel mb-4">
                                <div class="p-4 border-bottom d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-bold">Tareas de seguimiento</div>
                                        <div class="text-muted fs-7">Bandeja read-only; no cierra, reasigna ni crea tareas</div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-light-primary" id="crm_tareas_recargar"><i class="bi bi-arrow-clockwise"></i> Recargar</button>
                                </div>
                                <div class="p-4 border-bottom" id="crm_tareas_resumen"></div>
                                <div class="table-responsive">
                                    <table class="table align-middle table-row-dashed gy-3 mb-0">
                                        <thead>
                                            <tr class="text-muted fw-bold fs-8 text-uppercase">
                                                <th>Tarea</th>
                                                <th>Cliente</th>
                                                <th>Vencimiento</th>
                                                <th class="text-end">Accion</th>
                                            </tr>
                                        </thead>
                                        <tbody id="crm_tareas_tabla"></tbody>
                                    </table>
                                </div>
                            </div>

                                </div>
                                <div class="tab-pane fade" id="crm_tab_comercial" role="tabpanel">

                            <div class="crm-panel mb-4">
                                <div class="p-4 border-bottom d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-bold">CRM comercial</div>
                                        <div class="text-muted fs-7">Segmentos, condiciones y elegibilidad sin tocar POS ni ventas</div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-light-primary" id="crm_comercial_recargar"><i class="bi bi-arrow-clockwise"></i> Recargar</button>
                                </div>
                                <div class="p-4" id="crm_comercial_resumen"></div>
                            </div>

                            <div class="crm-panel mb-4">
                                <div class="p-4 border-bottom d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-bold">Reportes CRM</div>
                                        <div class="text-muted fs-7">Contactabilidad, campanas, recompensas y garantias en modo lectura</div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-light-primary" id="crm_reportes_recargar"><i class="bi bi-arrow-clockwise"></i> Recargar</button>
                                </div>
                                <div class="p-4" id="crm_reportes_resumen"></div>
                            </div>

                                </div>
                                <div class="tab-pane fade" id="crm_tab_recompensas" role="tabpanel">

                            <div class="crm-panel mb-4">
                                <div class="p-4 border-bottom d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-bold">Recompensas CRM</div>
                                        <div class="text-muted fs-7">Programas, cuentas, saldos y puntos preparados sin afectar ventas ni POS</div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-light-primary" id="crm_recompensas_recargar"><i class="bi bi-arrow-clockwise"></i> Recargar</button>
                                </div>
                                <div class="p-4" id="crm_recompensas_resumen"></div>
                            </div>

                                </div>
                                <div class="tab-pane fade" id="crm_tab_clientes" role="tabpanel">

                            <div class="crm-panel mb-4">
                                <div class="p-4 border-bottom d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-bold">Clientes CRM canonicos</div>
                                        <div class="text-muted fs-7">Identidad principal usada por POS, ventas y postventa</div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-light-primary" id="crm_canonicos_recargar"><i class="bi bi-arrow-clockwise"></i> Recargar</button>
                                </div>
                                <div class="table-responsive">
                                    <table class="table align-middle table-row-dashed gy-3 mb-0">
                                        <thead>
                                            <tr class="text-muted fw-bold fs-8 text-uppercase">
                                                <th>Cliente</th>
                                                <th>Identificador</th>
                                                <th>Estado</th>
                                                <th>Origen</th>
                                                <th class="text-end">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody id="crm_clientes_canonicos_tabla"></tbody>
                                    </table>
                                </div>
                            </div>

                                </div>
                                <div class="tab-pane fade" id="crm_tab_auditoria" role="tabpanel">

                            <div class="row g-4 mb-4">
                                <div class="col-xl-6">
                                    <div class="crm-panel">
                                        <div class="p-4 border-bottom d-flex justify-content-between align-items-center">
                                            <div>
                                                <div class="fw-bold">Fuentes externas auditadas</div>
                                                <div class="text-muted fs-7">Legacy y POS/UAT no se mezclan sin vinculo explicito</div>
                                            </div>
                                            <button type="button" class="btn btn-sm btn-icon btn-light-primary" id="crm_fuentes_recargar"><i class="bi bi-arrow-clockwise"></i></button>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table align-middle table-row-dashed gy-3 mb-0">
                                                <thead>
                                                    <tr class="text-muted fw-bold fs-8 text-uppercase">
                                                        <th>Fuente</th>
                                                        <th>Registros</th>
                                                        <th>Estado</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="crm_fuentes_tabla"></tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xl-6">
                                    <div class="crm-panel">
                                        <div class="p-4 border-bottom">
                                            <div class="fw-bold">Bloqueos de uso comercial</div>
                                            <div class="text-muted fs-7">Condiciones que impiden usar datos antiguos como clientes confiables</div>
                                        </div>
                                        <div class="p-4" id="crm_migracion_bloqueos"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="crm-panel mb-4">
                                <div class="p-4 border-bottom">
                                    <div class="fw-bold">Revision de duplicado</div>
                                    <div class="text-muted fs-7">Analisis read-only del grupo seleccionado</div>
                                </div>
                                <div class="p-4" id="crm_duplicado_revision"></div>
                            </div>

                            <div class="crm-panel mb-4">
                                <div class="p-4 border-bottom d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-bold">Duplicados probables</div>
                                        <div class="text-muted fs-7">Agrupados por telefono/correo/codigo normalizado</div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-light-primary" id="crm_duplicados_recargar"><i class="bi bi-arrow-clockwise"></i> Revisar</button>
                                </div>
                                <div class="table-responsive crm-scroll">
                                    <table class="table align-middle table-row-dashed gy-3 mb-0">
                                        <thead>
                                            <tr class="text-muted fw-bold fs-8 text-uppercase">
                                                <th>Identificador</th>
                                                <th>Total</th>
                                                <th>Fuentes</th>
                                                <th>Clientes</th>
                                            </tr>
                                        </thead>
                                        <tbody id="crm_duplicados_tabla"></tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="crm-panel mb-4">
                                <div class="p-4 border-bottom d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-bold">Preview legacy pausado</div>
                                        <div class="text-muted fs-7">Solo auditoria read-only para casos puntuales rescatables</div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-light-primary" id="crm_preview_recargar"><i class="bi bi-eye"></i> Preview</button>
                                </div>
                                <div class="table-responsive crm-scroll">
                                    <table class="table align-middle table-row-dashed gy-3 mb-0">
                                        <thead>
                                            <tr class="text-muted fw-bold fs-8 text-uppercase">
                                                <th>Origen</th>
                                                <th>Cliente propuesto</th>
                                                <th>Identificadores</th>
                                                <th>Revision</th>
                                            </tr>
                                        </thead>
                                        <tbody id="crm_preview_tabla"></tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="crm-panel mb-4">
                                <div class="p-4 border-bottom d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-bold">Borrador legacy pausado</div>
                                        <div class="text-muted fs-7">No continuar migracion masiva salvo autorizacion puntual</div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-light-primary" id="crm_borrador_recargar"><i class="bi bi-list-check"></i> Generar</button>
                                </div>
                                <div class="p-4" id="crm_borrador_resumen"></div>
                                <div class="table-responsive crm-scroll">
                                    <table class="table align-middle table-row-dashed gy-3 mb-0">
                                        <thead>
                                            <tr class="text-muted fw-bold fs-8 text-uppercase">
                                                <th>Origen</th>
                                                <th>Cliente</th>
                                                <th>Estado</th>
                                                <th>Bloqueos/Avisos</th>
                                            </tr>
                                        </thead>
                                        <tbody id="crm_borrador_tabla"></tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="crm-panel">
                                <div class="p-4 border-bottom">
                                    <div class="fw-bold">DDL CRM base</div>
                                    <div class="text-muted fs-7">Plan de esquema pendiente; aplicar requiere respaldo externo y autorizacion</div>
                                </div>
                                <div class="p-4" id="crm_schema_resumen"></div>
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
<script src="/assets/js/custom/apps/crm/clientes/listado.js?v=20260630-tabs-2"></script>
</body>
</html>
