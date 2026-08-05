<?php
$diagnostico = isset($datos["diagnostico"]["depurar"]) ? $datos["diagnostico"]["depurar"] : array();
$ambienteLocal = isset($diagnostico["ambiente_local"]) ? $diagnostico["ambiente_local"] : array();
$ambientes = isset($diagnostico["ambientes"]) ? $diagnostico["ambientes"] : array();
$tablasLocales = isset($diagnostico["tablas_locales"]) ? $diagnostico["tablas_locales"] : array();
$totales = isset($diagnostico["totales"]) ? $diagnostico["totales"] : array();
$configuracion = isset($diagnostico["configuracion"]) ? $diagnostico["configuracion"] : array();
$archivoAmbientes = !empty($configuracion["archivo_ambientes"]);
$esquemaTecnico = isset($configuracion["esquema_tecnico"]) ? $configuracion["esquema_tecnico"] : array("listo" => false, "faltantes" => array());
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../">
    <title>Migraciones BD</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css">
    <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css">
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
                                <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Migraciones BD</h1>
                                <span class="text-muted">Promocion controlada de local hacia productivo con dry-run, politicas y respaldo</span>
                            </div>
                            <span class="badge badge-light-info fs-7">Fase 1: solo lectura</span>
                        </div>
                    </div>
                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <div class="alert alert-primary d-flex align-items-start gap-4">
                                <i class="bi bi-database-check fs-2"></i>
                                <div>
                                    <div class="fw-bold mb-1">Esta consola todavia no aplica cambios reales.</div>
                                    <div>Sirve para preparar la primera base productiva, comparar esquema y decidir que tablas migran solo estructura, datos semilla o merge controlado.</div>
                                </div>
                            </div>

                            <?php if (!$archivoAmbientes): ?>
                                <div class="alert alert-warning d-flex align-items-start gap-4">
                                    <i class="bi bi-shield-lock fs-2"></i>
                                    <div>
                                        <div class="fw-bold mb-1">Destino productivo no configurado.</div>
                                        <div>Crea un archivo local basado en <code>app/config/migraciones_ambientes.example.php</code> como <code>app/config/migraciones_ambientes.local.php</code>. Ese archivo no debe exponer passwords en UI ni documentacion.</div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if (empty($esquemaTecnico["listo"])): ?>
                                <div class="alert alert-info d-flex align-items-start gap-4">
                                    <i class="bi bi-database-add fs-2"></i>
                                    <div>
                                        <div class="fw-bold mb-1">Esquema tecnico pendiente.</div>
                                        <div>La consola puede analizar y preparar paquetes temporales. Para guardar politicas y paquetes en SYS falta aplicar el esquema <code>sys_migraciones_*</code> con respaldo externo y autorizacion.</div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="row g-5 mb-6">
                                <div class="col-xl-3 col-md-6">
                                    <div class="card h-100">
                                        <div class="card-body">
                                            <div class="text-muted fs-7 text-uppercase mb-2">Origen activo</div>
                                            <div class="fw-bold fs-4"><?= htmlspecialchars(isset($ambienteLocal["base"]) ? $ambienteLocal["base"] : "") ?></div>
                                            <div class="text-muted"><?= htmlspecialchars(isset($ambienteLocal["host"]) ? $ambienteLocal["host"] : "") ?></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xl-3 col-md-6">
                                    <div class="card h-100">
                                        <div class="card-body">
                                            <div class="text-muted fs-7 text-uppercase mb-2">Tablas locales</div>
                                            <div class="fw-bold fs-4"><?= htmlspecialchars(isset($totales["tablas"]) ? $totales["tablas"] : "0") ?></div>
                                            <div class="text-muted"><?= htmlspecialchars(isset($totales["columnas"]) ? $totales["columnas"] : "0") ?> columnas / <?= htmlspecialchars(isset($totales["indices"]) ? $totales["indices"] : "0") ?> indices / <?= htmlspecialchars(isset($totales["foraneas"]) ? $totales["foraneas"] : "0") ?> FKs</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xl-3 col-md-6">
                                    <div class="card h-100">
                                        <div class="card-body">
                                            <div class="text-muted fs-7 text-uppercase mb-2">Ambientes</div>
                                            <div class="fw-bold fs-4"><?= count($ambientes) ?></div>
                                            <div class="text-muted"><?= $archivoAmbientes ? "Archivo local configurado" : "Solo ambiente local" ?></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xl-3 col-md-6">
                                    <div class="card h-100">
                                        <div class="card-body">
                                            <div class="text-muted fs-7 text-uppercase mb-2">Aplicacion real</div>
                                            <div class="fw-bold fs-4">Bloqueada</div>
                                            <div class="text-muted"><?= empty($esquemaTecnico["listo"]) ? "Esquema tecnico pendiente" : "Pendiente Fase 2 con respaldo" ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card">
                                <div class="card-header border-0 pt-6">
                                    <div class="card-title">
                                        <h2 class="fw-bold mb-0">Consola de preparacion</h2>
                                    </div>
                                    <div class="card-toolbar d-flex gap-3">
                                        <select class="form-select form-select-solid w-225px" id="migbd_destino">
                                            <option value="">Seleccionar destino</option>
                                            <?php foreach ($ambientes as $ambiente): ?>
                                                <?php if (isset($ambiente["alias"]) && $ambiente["alias"] !== "local"): ?>
                                                    <option value="<?= htmlspecialchars($ambiente["alias"]) ?>"><?= htmlspecialchars($ambiente["alias"] . " - " . $ambiente["base"]) ?></option>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="button" class="btn btn-light-primary" id="migbd_btn_clasificar">
                                            <i class="bi bi-list-check"></i>
                                            Politicas
                                        </button>
                                        <button type="button" class="btn btn-light-primary" id="migbd_btn_selfcheck">
                                            <i class="bi bi-activity"></i>
                                            Selfcheck
                                        </button>
                                        <button type="button" class="btn btn-light-primary" id="migbd_btn_checklist">
                                            <i class="bi bi-ui-checks"></i>
                                            Checklist
                                        </button>
                                        <button type="button" class="btn btn-light-success" id="migbd_btn_guardar_politicas">
                                            <i class="bi bi-save"></i>
                                            Guardar politicas
                                        </button>
                                        <button type="button" class="btn btn-light-info" id="migbd_btn_perfil_datos">
                                            <i class="bi bi-table"></i>
                                            Perfil datos
                                        </button>
                                        <button type="button" class="btn btn-light-info" id="migbd_btn_orden">
                                            <i class="bi bi-diagram-3"></i>
                                            Orden
                                        </button>
                                        <button type="button" class="btn btn-light-dark" id="migbd_btn_resumen_decision">
                                            <i class="bi bi-clipboard-data"></i>
                                            Resumen
                                        </button>
                                        <button type="button" class="btn btn-light-dark" id="migbd_btn_manifiesto">
                                            <i class="bi bi-braces"></i>
                                            Manifiesto
                                        </button>
                                        <button type="button" class="btn btn-primary" id="migbd_btn_comparar">
                                            <i class="bi bi-arrow-left-right"></i>
                                            Comparar
                                        </button>
                                        <button type="button" class="btn btn-light-warning" id="migbd_btn_paquete">
                                            <i class="bi bi-box-seam"></i>
                                            Paquete dry-run
                                        </button>
                                        <button type="button" class="btn btn-light" id="migbd_btn_sql">
                                            <i class="bi bi-filetype-sql"></i>
                                            SQL dry-run
                                        </button>
                                        <button type="button" class="btn btn-light" id="migbd_btn_paquetes_listar">
                                            <i class="bi bi-archive"></i>
                                            Paquetes
                                        </button>
                                        <button type="button" class="btn btn-light" id="migbd_btn_ejecuciones_listar">
                                            <i class="bi bi-clock-history"></i>
                                            Ejecuciones
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body pt-0">
                                    <ul class="nav nav-tabs nav-line-tabs mb-5 fs-6">
                                        <li class="nav-item">
                                            <a class="nav-link active" data-bs-toggle="tab" href="#migbd_tab_ambientes">Ambientes</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" data-bs-toggle="tab" href="#migbd_tab_politicas">Politicas</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" data-bs-toggle="tab" href="#migbd_tab_selfcheck">Selfcheck</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" data-bs-toggle="tab" href="#migbd_tab_checklist">Checklist</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" data-bs-toggle="tab" href="#migbd_tab_perfil_datos">Perfil datos</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" data-bs-toggle="tab" href="#migbd_tab_orden">Orden</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" data-bs-toggle="tab" href="#migbd_tab_resumen_decision">Resumen</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" data-bs-toggle="tab" href="#migbd_tab_manifiesto">Manifiesto</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" data-bs-toggle="tab" href="#migbd_tab_comparacion">Comparacion</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" data-bs-toggle="tab" href="#migbd_tab_sql">SQL</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" data-bs-toggle="tab" href="#migbd_tab_paquetes">Paquetes</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" data-bs-toggle="tab" href="#migbd_tab_ejecuciones">Ejecuciones</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" data-bs-toggle="tab" href="#migbd_tab_activacion">Activacion</a>
                                        </li>
                                    </ul>
                                    <div class="tab-content">
                                        <div class="tab-pane fade show active" id="migbd_tab_ambientes">
                                            <div class="d-flex flex-wrap gap-3 mb-5">
                                                <button type="button" class="btn btn-sm btn-light-primary" id="migbd_btn_preflight_destino">
                                                    <i class="bi bi-hdd-network"></i>
                                                    Preflight destino
                                                </button>
                                            </div>
                                            <div class="table-responsive">
                                                <table class="table table-row-dashed align-middle">
                                                    <thead>
                                                    <tr class="text-muted text-uppercase fs-8">
                                                        <th>Alias</th>
                                                        <th>Tipo</th>
                                                        <th>Host</th>
                                                        <th>Base</th>
                                                        <th>Usuario</th>
                                                        <th>Estado</th>
                                                        <th>Acciones</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    <?php foreach ($ambientes as $ambiente): ?>
                                                        <tr>
                                                            <td class="fw-semibold"><?= htmlspecialchars($ambiente["alias"]) ?></td>
                                                            <td><?= htmlspecialchars($ambiente["tipo"]) ?></td>
                                                            <td><?= htmlspecialchars($ambiente["host"]) ?></td>
                                                            <td><?= htmlspecialchars($ambiente["base"]) ?></td>
                                                            <td><?= htmlspecialchars($ambiente["usuario"]) ?></td>
                                                            <td>
                                                                <?php if (!empty($ambiente["configurado"])): ?>
                                                                    <span class="badge badge-light-success">Configurado</span>
                                                                <?php else: ?>
                                                                    <span class="badge badge-light-warning">Incompleto</span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <td>
                                                                <?php if (!empty($ambiente["configurado"])): ?>
                                                                    <button type="button" class="btn btn-sm btn-light-primary migbd-probar-ambiente" data-alias="<?= htmlspecialchars($ambiente["alias"]) ?>">
                                                                        <i class="bi bi-plug"></i>
                                                                        Probar
                                                                    </button>
                                                                <?php else: ?>
                                                                    <span class="text-muted fs-8">Pendiente</span>
                                                                <?php endif; ?>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                            <div id="migbd_ambiente_prueba_resultado" class="mt-5 text-muted">Selecciona Probar para validar conexion y metadatos del ambiente.</div>
                                        </div>
                                        <div class="tab-pane fade" id="migbd_tab_politicas">
                                            <div id="migbd_politicas_resultado" class="text-muted py-8 text-center">Genera las politicas sugeridas para revisar tabla por tabla.</div>
                                        </div>
                                        <div class="tab-pane fade" id="migbd_tab_selfcheck">
                                            <div id="migbd_selfcheck_resultado" class="text-muted py-8 text-center">Ejecuta el selfcheck para revisar prerequisitos de respaldo, esquema tecnico y paquetes.</div>
                                        </div>
                                        <div class="tab-pane fade" id="migbd_tab_checklist">
                                            <div id="migbd_checklist_resultado" class="text-muted py-8 text-center">Ejecuta el checklist para revisar el orden operativo completo antes de activar o aplicar.</div>
                                        </div>
                                        <div class="tab-pane fade" id="migbd_tab_perfil_datos">
                                            <div id="migbd_perfil_datos_resultado" class="text-muted py-8 text-center">Genera el perfil de datos para detectar llaves, columnas sensibles y riesgos por tabla.</div>
                                        </div>
                                        <div class="tab-pane fade" id="migbd_tab_orden">
                                            <div id="migbd_orden_resultado" class="text-muted py-8 text-center">Genera el orden sugerido de migracion segun llaves foraneas.</div>
                                        </div>
                                        <div class="tab-pane fade" id="migbd_tab_resumen_decision">
                                            <div id="migbd_resumen_decision_resultado" class="text-muted py-8 text-center">Genera el resumen ejecutivo de politicas, riesgos y candidatas para datos.</div>
                                        </div>
                                        <div class="tab-pane fade" id="migbd_tab_manifiesto">
                                            <div class="d-flex justify-content-between align-items-center mb-4">
                                                <div class="text-muted">Manifiesto JSON portable de preparacion. No ejecuta cambios ni guarda en BD.</div>
                                                <div class="d-flex gap-2">
                                                    <button type="button" class="btn btn-sm btn-light" id="migbd_btn_copiar_manifiesto">
                                                        <i class="bi bi-clipboard"></i>
                                                        Copiar manifiesto
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-light" id="migbd_btn_descargar_manifiesto">
                                                        <i class="bi bi-download"></i>
                                                        Descargar JSON
                                                    </button>
                                                </div>
                                            </div>
                                            <pre class="bg-light rounded p-5 mh-500px overflow-auto"><code id="migbd_manifiesto_resultado">Sin manifiesto generado.</code></pre>
                                        </div>
                                        <div class="tab-pane fade" id="migbd_tab_comparacion">
                                            <div id="migbd_comparacion_resultado" class="text-muted py-8 text-center">Selecciona un destino y ejecuta la comparacion.</div>
                                        </div>
                                        <div class="tab-pane fade" id="migbd_tab_sql">
                                            <div class="d-flex justify-content-between align-items-center mb-4">
                                                <div class="text-muted">SQL generado solo para revision. No se ejecuta desde esta fase.</div>
                                                <div class="d-flex gap-2">
                                                    <button type="button" class="btn btn-sm btn-light" id="migbd_btn_copiar_sql">
                                                        <i class="bi bi-clipboard"></i>
                                                        Copiar SQL
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-light" id="migbd_btn_descargar_sql">
                                                        <i class="bi bi-download"></i>
                                                        Descargar SQL
                                                    </button>
                                                </div>
                                            </div>
                                            <pre class="bg-light rounded p-5 mh-500px overflow-auto"><code id="migbd_sql_resultado">Sin SQL generado.</code></pre>
                                        </div>
                                        <div class="tab-pane fade" id="migbd_tab_paquetes">
                                            <div id="migbd_paquetes_resultado" class="text-muted py-8 text-center">Consulta paquetes persistidos cuando el esquema tecnico este aplicado.</div>
                                            <div id="migbd_paquete_detalle" class="mt-5"></div>
                                        </div>
                                        <div class="tab-pane fade" id="migbd_tab_ejecuciones">
                                            <div id="migbd_ejecuciones_resultado" class="text-muted py-8 text-center">Consulta ejecuciones registradas cuando existan aplicaciones de paquetes.</div>
                                            <div id="migbd_ejecucion_detalle" class="mt-5"></div>
                                        </div>
                                        <div class="tab-pane fade" id="migbd_tab_activacion">
                                            <div class="row g-5">
                                                <div class="col-xl-5">
                                                    <label class="form-label">Ruta o referencia de respaldo</label>
                                                    <div class="input-group">
                                                        <input class="form-control form-control-solid" id="migbd_respaldo_ruta" placeholder="C:\xampp\panel_db_backups\...sql">
                                                        <button type="button" class="btn btn-primary" id="migbd_btn_preflight">
                                                            <i class="bi bi-shield-check"></i>
                                                            Validar
                                                        </button>
                                                        <button type="button" class="btn btn-light-primary" id="migbd_btn_respaldo_validar" title="Validar respaldo">
                                                            <i class="bi bi-check-circle"></i>
                                                        </button>
                                                    </div>
                                                    <div class="text-muted fs-8 mt-2">Para DDL se recomienda ruta .sql externa al proyecto.</div>
                                                </div>
                                                <div class="col-xl-7">
                                                    <div id="migbd_activacion_resultado" class="border rounded p-5 text-muted">
                                                        Ejecuta el preflight para ver checklist, comando de respaldo sugerido y texto de autorizacion.
                                                    </div>
                                                </div>
                                                <div class="col-12">
                                                    <div class="separator my-5"></div>
                                                    <div class="fw-bold mb-3">Respaldo local</div>
                                                    <div class="row g-5">
                                                        <div class="col-xl-4">
                                                            <label class="form-label">Token respaldo</label>
                                                            <input class="form-control form-control-solid" id="migbd_respaldo_token" placeholder="MIGRACIONES_BD_RESPALDO">
                                                        </div>
                                                        <div class="col-xl-6">
                                                            <label class="form-label">Confirmacion respaldo</label>
                                                            <textarea class="form-control form-control-solid" id="migbd_respaldo_confirmacion" rows="2" placeholder="AUTORIZO GENERAR RESPALDO MIGRACIONES BD ..."></textarea>
                                                        </div>
                                                        <div class="col-xl-2 d-flex align-items-end justify-content-end gap-2">
                                                            <button type="button" class="btn btn-light-primary" id="migbd_btn_respaldo_generar">
                                                                <i class="bi bi-database-down"></i>
                                                                Generar respaldo
                                                            </button>
                                                            <button type="button" class="btn btn-light" id="migbd_btn_restore_preflight">
                                                                <i class="bi bi-arrow-counterclockwise"></i>
                                                                Plan restore
                                                            </button>
                                                            <button type="button" class="btn btn-light" id="migbd_btn_respaldos_listar">
                                                                <i class="bi bi-list-ul"></i>
                                                                Ver respaldos
                                                            </button>
                                                        </div>
                                                        <div class="col-12">
                                                            <div id="migbd_respaldos_resultado" class="text-muted"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-12">
                                                    <div class="separator my-5"></div>
                                                    <div class="row g-5">
                                                        <div class="col-xl-4">
                                                            <label class="form-label">Token</label>
                                                            <input class="form-control form-control-solid" id="migbd_schema_token" placeholder="MIGRACIONES_BD_SCHEMA">
                                                        </div>
                                                        <div class="col-xl-8">
                                                            <label class="form-label">Confirmacion literal</label>
                                                            <textarea class="form-control form-control-solid" id="migbd_schema_confirmacion" rows="3" placeholder="AUTORIZO CREAR ESQUEMA TECNICO MIGRACIONES BD ... sys_migraciones_*"></textarea>
                                                        </div>
                                                        <div class="col-12 d-flex justify-content-end gap-3">
                                                            <button type="button" class="btn btn-light-success" id="migbd_btn_schema_verificar">
                                                                <i class="bi bi-patch-check"></i>
                                                                Verificar esquema
                                                            </button>
                                                            <button type="button" class="btn btn-light-info" id="migbd_btn_schema_preflight_final">
                                                                <i class="bi bi-shield-lock"></i>
                                                                Preflight esquema
                                                            </button>
                                                            <button type="button" class="btn btn-light" id="migbd_btn_schema_dryrun">
                                                                <i class="bi bi-file-earmark-text"></i>
                                                                Dry-run esquema
                                                            </button>
                                                            <button type="button" class="btn btn-danger" id="migbd_btn_schema_aplicar">
                                                                <i class="bi bi-database-add"></i>
                                                                Aplicar esquema tecnico
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-12">
                                                    <div class="separator my-5"></div>
                                                    <div class="fw-bold mb-3">Aplicacion controlada de paquete</div>
                                                    <div class="row g-5">
                                                        <div class="col-xl-4">
                                                            <label class="form-label">Codigo o ID de paquete</label>
                                                            <input class="form-control form-control-solid" id="migbd_paquete_codigo" placeholder="MIGBD_YYYYMMDD_HHMMSS_xxxxxxxx">
                                                        </div>
                                                        <div class="col-xl-4">
                                                            <label class="form-label">Token paquete</label>
                                                            <input class="form-control form-control-solid" id="migbd_paquete_token" placeholder="MIGRACIONES_BD_AUTORIZAR o MIGRACIONES_BD_APLICAR">
                                                        </div>
                                                        <div class="col-xl-4 d-flex align-items-end gap-3">
                                                            <button type="button" class="btn btn-light-primary" id="migbd_btn_paquete_preflight">
                                                                <i class="bi bi-shield-check"></i>
                                                                Preflight paquete
                                                            </button>
                                                            <button type="button" class="btn btn-light-info" id="migbd_btn_preflight_final">
                                                                <i class="bi bi-traffic-light"></i>
                                                                Semaforo final
                                                            </button>
                                                            <button type="button" class="btn btn-light-success" id="migbd_btn_paquete_autorizar">
                                                                <i class="bi bi-check2-square"></i>
                                                                Autorizar
                                                            </button>
                                                            <button type="button" class="btn btn-light" id="migbd_btn_paquete_simular">
                                                                <i class="bi bi-play-circle"></i>
                                                                Simular
                                                            </button>
                                                        </div>
                                                        <div class="col-xl-9">
                                                            <label class="form-label">Confirmacion paquete</label>
                                                            <textarea class="form-control form-control-solid" id="migbd_paquete_confirmacion" rows="3" placeholder="AUTORIZO APLICAR PAQUETE MIGRACIONES BD ... hacia ... usando respaldo ..."></textarea>
                                                        </div>
                                                        <div class="col-xl-3 d-flex align-items-end justify-content-end">
                                                            <button type="button" class="btn btn-danger" id="migbd_btn_paquete_aplicar">
                                                                <i class="bi bi-database-up"></i>
                                                                Aplicar paquete
                                                            </button>
                                                        </div>
                                                        <div class="col-12">
                                                            <div id="migbd_paquete_aplicacion_resultado" class="border rounded p-5 text-muted">
                                                                La aplicacion real exige paquete persistido, respaldo valido, permiso, token, confirmacion literal y bandera local de habilitacion.
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card mt-6">
                                <div class="card-body">
                                    <div class="fw-bold mb-2">Regla de arranque productivo</div>
                                    <div class="text-gray-700">
                                        Mientras productivo sea solo una copia de revision, local puede considerarse la base candidata oficial. Antes de activar operacion real, esta consola debe generar evidencia de esquema, politicas por tabla y respaldo externo. Una vez que productivo empiece a recibir ventas, inventario, caja o clientes reales, esas tablas pasan a ser propiedad de productivo y ya no deben reemplazarse desde local.
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
<script src="/assets/js/custom/apps/erp/sistema/migraciones_bd.js?v=20260730-fase1"></script>
</body>
</html>
