<!DOCTYPE html>
<?php
if (!empty($_SESSION['id_usuario'])) {
    require_once '../app/modelos/SeguridadPermisos.php';
    $crmSeguridadPermisos = new SeguridadPermisos();
    $crmAutorizacion = $crmSeguridadPermisos->autorizacionUsuario($_SESSION['id_usuario']);
    $_SESSION['roles'] = $crmAutorizacion['roles'];
    $_SESSION['permisos'] = $crmAutorizacion['permisos'];
}
$crmPermisosSesion = isset($_SESSION['permisos']) && is_array($_SESSION['permisos']) ? $_SESSION['permisos'] : array();
$crmPuedeOperarSeguimiento = in_array('crm.editar', $crmPermisosSesion, true) || in_array('crm.seguimiento.operar', $crmPermisosSesion, true);
?>
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
      Documentacion IA: Codex GPT-5, 2026-07-30.
      Proposito: consola CRM Seguimiento para tareas, interacciones y preflight operativo.
      Impacto: separa seguimiento operativo del listado de clientes.
      Contrato: los paneles operativos solo ejecutan dry-run; guardados reales requieren token/respaldo.
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

                            <div class="row g-4 mt-1">
                                <div class="col-xl-6">
                                    <div class="crm-panel">
                                        <div class="p-4 border-bottom">
                                            <div class="fw-bold">Registrar interaccion</div>
                                            <div class="text-muted fs-7">Preflight sin escritura para llamadas, WhatsApp, visita o seguimiento</div>
                                        </div>
                                        <div class="p-4">
                                            <?php if (!$crmPuedeOperarSeguimiento): ?>
                                                <div class="alert alert-light-warning py-3 mb-0 fs-7">Tu usuario puede consultar seguimiento, pero no validar operaciones.</div>
                                            <?php else: ?>
                                                <div class="row g-3">
                                                    <div class="col-md-4">
                                                        <label class="form-label text-muted fs-8 text-uppercase">Cliente CRM</label>
                                                        <input class="form-control form-control-solid" id="crm_seg_int_cliente" type="number" min="1" placeholder="1">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label text-muted fs-8 text-uppercase">Tipo</label>
                                                        <select class="form-select form-select-solid" id="crm_seg_int_tipo">
                                                            <option value="seguimiento">Seguimiento</option>
                                                            <option value="contacto">Contacto</option>
                                                            <option value="postventa">Postventa</option>
                                                            <option value="comercial">Comercial</option>
                                                            <option value="calidad_datos">Calidad datos</option>
                                                            <option value="otro">Otro</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label text-muted fs-8 text-uppercase">Canal</label>
                                                        <select class="form-select form-select-solid" id="crm_seg_int_canal">
                                                            <option value="whatsapp">WhatsApp</option>
                                                            <option value="telefono">Telefono</option>
                                                            <option value="correo">Correo</option>
                                                            <option value="presencial">Presencial</option>
                                                            <option value="sistema">Sistema</option>
                                                            <option value="otro">Otro</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label text-muted fs-8 text-uppercase">Direccion</label>
                                                        <select class="form-select form-select-solid" id="crm_seg_int_direccion">
                                                            <option value="saliente">Saliente</option>
                                                            <option value="entrante">Entrante</option>
                                                            <option value="interna">Interna</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label text-muted fs-8 text-uppercase">Resultado</label>
                                                        <select class="form-select form-select-solid" id="crm_seg_int_resultado">
                                                            <option value="registrado">Registrado</option>
                                                            <option value="contactado">Contactado</option>
                                                            <option value="sin_respuesta">Sin respuesta</option>
                                                            <option value="pendiente">Pendiente</option>
                                                            <option value="resuelto">Resuelto</option>
                                                            <option value="no_procede">No procede</option>
                                                            <option value="otro">Otro</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label text-muted fs-8 text-uppercase">Fecha</label>
                                                        <input class="form-control form-control-solid" id="crm_seg_int_fecha" placeholder="YYYY-MM-DD HH:MM">
                                                    </div>
                                                    <div class="col-12">
                                                        <label class="form-label text-muted fs-8 text-uppercase">Resumen</label>
                                                        <input class="form-control form-control-solid" id="crm_seg_int_resumen" maxlength="180" placeholder="Resumen operativo del contacto">
                                                    </div>
                                                    <div class="col-12">
                                                        <label class="form-label text-muted fs-8 text-uppercase">Detalle</label>
                                                        <textarea class="form-control form-control-solid" id="crm_seg_int_detalle" rows="3" placeholder="Detalle opcional"></textarea>
                                                    </div>
                                                    <div class="col-12">
                                                        <button class="btn btn-light-primary w-100" id="crm_seg_int_validar" type="button"><i class="bi bi-check2-circle"></i> Validar interaccion</button>
                                                    </div>
                                                </div>
                                                <div id="crm_seg_int_resultado_box" class="mt-4"></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-xl-6">
                                    <div class="crm-panel">
                                        <div class="p-4 border-bottom">
                                            <div class="fw-bold">Cambiar estatus de tarea</div>
                                            <div class="text-muted fs-7">Preflight para cerrar, cancelar o pasar a proceso sin modificar la tarea</div>
                                        </div>
                                        <div class="p-4">
                                            <?php if (!$crmPuedeOperarSeguimiento): ?>
                                                <div class="alert alert-light-warning py-3 mb-0 fs-7">Tu usuario puede consultar tareas, pero no validar cambios de estatus.</div>
                                            <?php else: ?>
                                                <div class="row g-3">
                                                    <div class="col-md-4">
                                                        <label class="form-label text-muted fs-8 text-uppercase">Tarea CRM</label>
                                                        <input class="form-control form-control-solid" id="crm_seg_est_tarea" type="number" min="1" placeholder="ID">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label text-muted fs-8 text-uppercase">Estatus</label>
                                                        <select class="form-select form-select-solid" id="crm_seg_est_estatus">
                                                            <option value="en_proceso">En proceso</option>
                                                            <option value="cerrada">Cerrada</option>
                                                            <option value="cancelada">Cancelada</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label text-muted fs-8 text-uppercase">Resultado</label>
                                                        <input class="form-control form-control-solid" id="crm_seg_est_resultado" maxlength="160" placeholder="Resultado">
                                                    </div>
                                                    <div class="col-12">
                                                        <label class="form-label text-muted fs-8 text-uppercase">Nota</label>
                                                        <textarea class="form-control form-control-solid" id="crm_seg_est_nota" rows="3" placeholder="Nota opcional del cambio"></textarea>
                                                    </div>
                                                    <div class="col-12">
                                                        <button class="btn btn-light-primary w-100" id="crm_seg_est_validar" type="button"><i class="bi bi-check2-circle"></i> Validar cambio</button>
                                                    </div>
                                                </div>
                                                <div id="crm_seg_est_resultado_box" class="mt-4"></div>
                                            <?php endif; ?>
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
<script>
    window.CRM_SEGUIMIENTO_PERMISOS = {
        operar: <?= $crmPuedeOperarSeguimiento ? 'true' : 'false'; ?>
    };
</script>
<script src="assets/plugins/global/plugins.bundle.js"></script>
<script src="assets/js/scripts.bundle.js"></script>
<script src="/assets/js/custom/apps/crm/seguimiento/index.js?v=20260730-1"></script>
</body>
</html>
