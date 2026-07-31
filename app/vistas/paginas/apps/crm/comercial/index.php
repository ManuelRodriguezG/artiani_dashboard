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
$crmPuedeOperarComercial = in_array('crm.editar', $crmPermisosSesion, true) || in_array('crm.comercial.operar', $crmPermisosSesion, true);
?>
<html lang="es">
<head>
    <base href="../../../">
    <title>CRM Comercial</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css">
    <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css">
    <!--
      Documentacion IA: Codex GPT-5, 2026-07-30.
      Proposito: consola CRM Comercial para segmentos y condiciones.
      Impacto: separa trabajo comercial del listado de clientes y prepara listas/precios.
      Contrato: acciones de guardado siguen bloqueadas por token y respaldo.
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
                                <h1 class="page-heading text-dark fw-bold fs-3 mb-1">CRM Comercial</h1>
                                <span class="text-muted">Segmentos, condiciones y preparacion para listas de precios</span>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="/comercial/listas_precios" class="btn btn-light">
                                    <i class="bi bi-tags"></i> Listas
                                </a>
                                <button type="button" class="btn btn-light-primary" id="crm_comercial_recargar">
                                    <i class="bi bi-arrow-clockwise"></i> Actualizar
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <div id="crm_comercial_alerta" class="mb-4"></div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-3">
                                    <div class="crm-kpi p-4">
                                        <div class="text-muted fs-8 text-uppercase">Clientes CRM</div>
                                        <div class="fw-bold fs-2" id="crm_com_kpi_clientes">0</div>
                                        <div class="text-muted fs-8">Base canonica</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="crm-kpi p-4">
                                        <div class="text-muted fs-8 text-uppercase">Segmentos activos</div>
                                        <div class="fw-bold fs-2" id="crm_com_kpi_segmentos">0</div>
                                        <div class="text-muted fs-8">Tipos disponibles</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="crm-kpi p-4">
                                        <div class="text-muted fs-8 text-uppercase">Relaciones</div>
                                        <div class="fw-bold fs-2" id="crm_com_kpi_relaciones">0</div>
                                        <div class="text-muted fs-8">Clientes segmentados</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="crm-kpi p-4">
                                        <div class="text-muted fs-8 text-uppercase">Condiciones</div>
                                        <div class="fw-bold fs-2" id="crm_com_kpi_condiciones">0</div>
                                        <div class="text-muted fs-8">Preparacion comercial</div>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-4">
                                <div class="col-xl-5">
                                    <div class="crm-panel mb-4">
                                        <div class="p-4 border-bottom">
                                            <div class="fw-bold">Resumen comercial</div>
                                            <div class="text-muted fs-7">CRM define segmentacion; POS consume precios resueltos por backend</div>
                                        </div>
                                        <div class="p-4" id="crm_comercial_resumen"></div>
                                    </div>

                                    <div class="crm-panel">
                                        <div class="p-4 border-bottom d-flex justify-content-between align-items-center">
                                            <div>
                                                <div class="fw-bold">Tipo de cliente</div>
                                                <div class="text-muted fs-7">Alta/edicion controlada con dry-run y respaldo</div>
                                            </div>
                                            <?php if ($crmPuedeOperarComercial): ?>
                                                <button type="button" class="btn btn-sm btn-light" id="crm_seg_nuevo"><i class="bi bi-plus-circle"></i> Nuevo</button>
                                            <?php endif; ?>
                                        </div>
                                        <div class="p-4">
                                            <input type="hidden" id="crm_seg_id">
                                            <div class="row g-3">
                                                <div class="col-md-5">
                                                    <label class="form-label text-muted fs-8 text-uppercase">Codigo</label>
                                                    <input class="form-control form-control-solid" id="crm_seg_codigo" placeholder="RECURRENTE"<?= $crmPuedeOperarComercial ? '' : ' disabled'; ?>>
                                                </div>
                                                <div class="col-md-7">
                                                    <label class="form-label text-muted fs-8 text-uppercase">Nombre</label>
                                                    <input class="form-control form-control-solid" id="crm_seg_nombre" placeholder="Cliente recurrente"<?= $crmPuedeOperarComercial ? '' : ' disabled'; ?>>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label text-muted fs-8 text-uppercase">Tipo</label>
                                                    <select class="form-select form-select-solid" id="crm_seg_tipo"<?= $crmPuedeOperarComercial ? '' : ' disabled'; ?>>
                                                        <option value="comercial">Comercial</option>
                                                        <option value="operativo">Operativo</option>
                                                        <option value="marketing">Marketing</option>
                                                        <option value="postventa">Postventa</option>
                                                        <option value="riesgo">Riesgo</option>
                                                        <option value="otro">Otro</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label text-muted fs-8 text-uppercase">Estatus</label>
                                                    <select class="form-select form-select-solid" id="crm_seg_estatus"<?= $crmPuedeOperarComercial ? '' : ' disabled'; ?>>
                                                        <option value="activo">Activo</option>
                                                        <option value="pausado">Pausado</option>
                                                        <option value="cancelado">Cancelado</option>
                                                    </select>
                                                </div>
                                                <div class="col-12">
                                                    <label class="form-label text-muted fs-8 text-uppercase">Descripcion</label>
                                                    <input class="form-control form-control-solid" id="crm_seg_descripcion" placeholder="Uso comercial del segmento"<?= $crmPuedeOperarComercial ? '' : ' disabled'; ?>>
                                                </div>
                                                <div class="col-md-7">
                                                    <label class="form-label text-muted fs-8 text-uppercase">Referencia/respaldo</label>
                                                    <input class="form-control form-control-solid" id="crm_seg_respaldo" placeholder="C:\xampp\panel_db_backups\...sql"<?= $crmPuedeOperarComercial ? '' : ' disabled'; ?>>
                                                </div>
                                                <div class="col-md-5">
                                                    <label class="form-label text-muted fs-8 text-uppercase">Token autorizado</label>
                                                    <input class="form-control form-control-solid" id="crm_seg_autorizar" placeholder="CRM_CLIENTES_SEGMENTO_CATALOGO"<?= $crmPuedeOperarComercial ? '' : ' disabled'; ?>>
                                                </div>
                                                <?php if ($crmPuedeOperarComercial): ?>
                                                    <div class="col-md-5">
                                                        <button class="btn btn-light-primary w-100" id="crm_seg_validar" type="button"><i class="bi bi-check2-circle"></i> Validar</button>
                                                    </div>
                                                    <div class="col-md-7">
                                                        <button class="btn btn-primary w-100" id="crm_seg_guardar" type="button"><i class="bi bi-save"></i> Guardar segmento</button>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div id="crm_segmentos_dryrun" class="mt-4"></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-xl-7">
                                    <div class="crm-panel">
                                        <div class="p-4 border-bottom d-flex justify-content-between align-items-center">
                                            <div>
                                                <div class="fw-bold">Tipos de cliente</div>
                                                <div class="text-muted fs-7">Catalogo CRM para listas, condiciones y autorizaciones futuras</div>
                                            </div>
                                            <button type="button" class="btn btn-sm btn-light-primary" id="crm_segmentos_recargar"><i class="bi bi-arrow-clockwise"></i> Recargar</button>
                                        </div>
                                        <div class="p-4 border-bottom" id="crm_segmentos_resumen"></div>
                                        <div class="p-4">
                                            <div class="alert alert-light-info py-3 mb-4 fs-7">
                                                Comercial/Listas solo debe vincular listas a estos segmentos; POS no decide segmentos ni precios manualmente desde esta pantalla.
                                            </div>
                                            <div id="crm_segmentos_tabla"></div>
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
    window.CRM_COMERCIAL_PERMISOS = {
        operar: <?= $crmPuedeOperarComercial ? 'true' : 'false'; ?>
    };
</script>
<script src="assets/plugins/global/plugins.bundle.js"></script>
<script src="assets/js/scripts.bundle.js"></script>
<script src="/assets/js/custom/apps/crm/comercial/index.js?v=20260730-1"></script>
</body>
</html>
