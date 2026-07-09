<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../">
    <title>Costos y rentabilidad ERP</title>
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
                                <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Costos y rentabilidad</h1>
                                <span class="text-muted">Analisis read-only por SKU, canal y escenario comercial</span>
                            </div>
                            <div class="d-flex gap-2">
                                <?php if (SesionSeguridad::tienePermiso("rentabilidad.snapshot")): ?>
                                <button class="btn btn-light-success" id="rentabilidad_snapshot_guardar" type="button"><i class="bi bi-save"></i> Guardar snapshot</button>
                                <?php endif; ?>
                                <button class="btn btn-light-primary" id="rentabilidad_recargar" type="button"><i class="bi bi-arrow-clockwise"></i> Actualizar</button>
                            </div>
                        </div>
                    </div>
                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <div class="card mb-6">
                                <div class="card-body">
                                    <div class="row g-3 align-items-end">
                                        <div class="col-lg-4">
                                            <label class="form-label">Buscar</label>
                                            <div class="position-relative">
                                                <i class="bi bi-search fs-3 position-absolute ms-5 mt-3"></i>
                                                <input class="form-control form-control-solid ps-12" id="rentabilidad_buscar" placeholder="SKU o producto">
                                            </div>
                                        </div>
                                        <div class="col-lg-2">
                                            <label class="form-label">Canal</label>
                                            <select class="form-select form-select-solid" id="rentabilidad_canal">
                                                <option value="menudeo">Menudeo</option>
                                                <option value="mayoreo">Mayoreo</option>
                                                <option value="alianza">Alianza</option>
                                            </select>
                                        </div>
                                        <div class="col-lg-2">
                                            <label class="form-label">Riesgo</label>
                                            <select class="form-select form-select-solid" id="rentabilidad_riesgo">
                                                <option value="">Todos</option>
                                                <option value="perdida">Perdida</option>
                                                <option value="margen_bajo">Margen bajo</option>
                                                <option value="incompleto">Incompleto</option>
                                                <option value="rentable">Rentable</option>
                                            </select>
                                        </div>
                                        <div class="col-lg-1">
                                            <label class="form-label">Desc. %</label>
                                            <input class="form-control form-control-solid" id="rentabilidad_descuento" inputmode="decimal">
                                        </div>
                                        <div class="col-lg-1">
                                            <label class="form-label">Gasto %</label>
                                            <input class="form-control form-control-solid" id="rentabilidad_gasto" inputmode="decimal">
                                        </div>
                                        <div class="col-lg-1">
                                            <label class="form-label">Com. %</label>
                                            <input class="form-control form-control-solid" id="rentabilidad_comision" inputmode="decimal">
                                        </div>
                                        <div class="col-lg-1">
                                            <label class="form-label">Obj. %</label>
                                            <input class="form-control form-control-solid" id="rentabilidad_objetivo" inputmode="decimal">
                                        </div>
                                    </div>
                                    <div class="row g-3 align-items-end mt-1">
                                        <div class="col-lg-3">
                                            <label class="form-label">Accion</label>
                                            <select class="form-select form-select-solid" id="rentabilidad_accion">
                                                <option value="">Todas</option>
                                                <option value="perdidas">Perdidas</option>
                                                <option value="subir_precio">Subir precio</option>
                                                <option value="completar_costo">Completar costo</option>
                                                <option value="completar_precio">Completar precio</option>
                                                <option value="completar_fiscal">Completar fiscal</option>
                                                <option value="oportunidad_stock">Oportunidad con stock</option>
                                            </select>
                                        </div>
                                        <div class="col-lg-3">
                                            <label class="form-label">Stock</label>
                                            <select class="form-select form-select-solid" id="rentabilidad_stock">
                                                <option value="">Todos</option>
                                                <option value="con_stock">Con stock disponible</option>
                                                <option value="sin_stock">Sin stock disponible</option>
                                                <option value="con_valor">Con valor de inventario</option>
                                            </select>
                                        </div>
                                        <div class="col-lg-3">
                                            <label class="form-label">Origen costo</label>
                                            <select class="form-select form-select-solid" id="rentabilidad_origen_costo">
                                                <option value="">Todos</option>
                                                <option value="inventario_promedio">Inventario promedio</option>
                                                <option value="catalogo_referencia">Catalogo referencia</option>
                                                <option value="sin_costo">Sin costo</option>
                                            </select>
                                        </div>
                                        <div class="col-lg-3">
                                            <label class="form-label">Proveedor</label>
                                            <input class="form-control form-control-solid" id="rentabilidad_proveedor" placeholder="Proveedor preferido">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card mb-6">
                                <div class="card-header border-0 pt-5">
                                    <div class="card-title">
                                        <h3 class="fw-bold fs-5 mb-0">Tablero ejecutivo</h3>
                                    </div>
                                    <div class="card-toolbar">
                                        <button class="btn btn-sm btn-light" id="rentabilidad_tablero_recargar" type="button"><i class="bi bi-speedometer2"></i> Actualizar</button>
                                    </div>
                                </div>
                                <div class="card-body pt-3" id="rentabilidad_tablero"></div>
                            </div>
                            <div class="card mb-6">
                                <div class="card-header border-0 pt-5">
                                    <div class="card-title">
                                        <h3 class="fw-bold fs-5 mb-0">Escenarios comerciales</h3>
                                    </div>
                                    <div class="card-toolbar">
                                        <button class="btn btn-sm btn-light" id="rentabilidad_escenarios_auditar" type="button"><i class="bi bi-sliders"></i> Auditar</button>
                                    </div>
                                </div>
                                <div class="card-body pt-3" id="rentabilidad_escenarios"></div>
                            </div>
                            <div class="card mb-6">
                                <div class="card-header border-0 pt-5">
                                    <div class="card-title">
                                        <h3 class="fw-bold fs-5 mb-0">Estado del modulo</h3>
                                    </div>
                                    <div class="card-toolbar">
                                        <button class="btn btn-sm btn-light" id="rentabilidad_estado_modulo_recargar" type="button"><i class="bi bi-shield-check"></i> Revisar</button>
                                    </div>
                                </div>
                                <div class="card-body pt-3" id="rentabilidad_estado_modulo"></div>
                            </div>
                            <div class="card mb-6">
                                <div class="card-header border-0 pt-5">
                                    <div class="card-title">
                                        <h3 class="fw-bold fs-5 mb-0">Preflight uso comercial</h3>
                                    </div>
                                    <div class="card-toolbar">
                                        <button class="btn btn-sm btn-light" id="rentabilidad_uso_comercial_recargar" type="button"><i class="bi bi-signpost"></i> Evaluar</button>
                                    </div>
                                </div>
                                <div class="card-body pt-3" id="rentabilidad_uso_comercial"></div>
                            </div>
                            <div class="card mb-6">
                                <div class="card-header border-0 pt-5">
                                    <div class="card-title">
                                        <h3 class="fw-bold fs-5 mb-0">Plan de desbloqueo</h3>
                                    </div>
                                    <div class="card-toolbar">
                                        <button class="btn btn-sm btn-light" id="rentabilidad_desbloqueo_recargar" type="button"><i class="bi bi-unlock"></i> Ordenar</button>
                                    </div>
                                </div>
                                <div class="card-body pt-3" id="rentabilidad_desbloqueo"></div>
                            </div>
                            <div class="card mb-6">
                                <div class="card-header border-0 pt-5">
                                    <div class="card-title">
                                        <h3 class="fw-bold fs-5 mb-0">Auditoria final</h3>
                                    </div>
                                    <div class="card-toolbar">
                                        <button class="btn btn-sm btn-light" id="rentabilidad_auditoria_final_recargar" type="button"><i class="bi bi-clipboard-check"></i> Auditar</button>
                                    </div>
                                </div>
                                <div class="card-body pt-3" id="rentabilidad_auditoria_final"></div>
                            </div>
                            <div class="card mb-6">
                                <div class="card-header border-0 pt-5">
                                    <div class="card-title">
                                        <h3 class="fw-bold fs-5 mb-0">Modo revisión</h3>
                                    </div>
                                    <div class="card-toolbar">
                                        <button class="btn btn-sm btn-light" id="rentabilidad_revision_recargar" type="button"><i class="bi bi-kanban"></i> Revisar</button>
                                    </div>
                                </div>
                                <div class="card-body pt-3" id="rentabilidad_revision"></div>
                            </div>
                            <div class="card mb-6">
                                <div class="card-header border-0 pt-5">
                                    <div class="card-title">
                                        <h3 class="fw-bold fs-5 mb-0">Recomendaciones operativas</h3>
                                    </div>
                                    <div class="card-toolbar">
                                        <button class="btn btn-sm btn-light-info" id="rentabilidad_recomendaciones_recargar" type="button"><i class="bi bi-list-check"></i> Revisar</button>
                                    </div>
                                </div>
                                <div class="card-body pt-3" id="rentabilidad_recomendaciones"></div>
                            </div>
                            <div class="card mb-6">
                                <div class="card-header border-0 pt-5">
                                    <div class="card-title">
                                        <h3 class="fw-bold fs-5 mb-0">Matriz de escenarios</h3>
                                    </div>
                                    <div class="card-toolbar">
                                        <button class="btn btn-sm btn-light" id="rentabilidad_matriz_recargar" type="button"><i class="bi bi-grid-3x3-gap"></i> Comparar</button>
                                    </div>
                                </div>
                                <div class="card-body pt-3" id="rentabilidad_matriz"></div>
                            </div>
                            <div class="card mb-6">
                                <div class="card-header border-0 pt-5">
                                    <div class="card-title">
                                        <h3 class="fw-bold fs-5 mb-0">Canal recomendado</h3>
                                    </div>
                                    <div class="card-toolbar">
                                        <button class="btn btn-sm btn-light" id="rentabilidad_canales_recargar" type="button"><i class="bi bi-signpost-split"></i> Evaluar</button>
                                    </div>
                                </div>
                                <div class="card-body pt-3" id="rentabilidad_canales"></div>
                            </div>
                            <div class="card mb-6">
                                <div class="card-header border-0 pt-5">
                                    <div class="card-title">
                                        <h3 class="fw-bold fs-5 mb-0">Plan de cierre</h3>
                                    </div>
                                    <div class="card-toolbar">
                                        <button class="btn btn-sm btn-light" id="rentabilidad_plan_recargar" type="button"><i class="bi bi-list-task"></i> Priorizar</button>
                                    </div>
                                </div>
                                <div class="card-body pt-3" id="rentabilidad_plan"></div>
                            </div>
                            <div class="card mb-6">
                                <div class="card-header border-0 pt-5">
                                    <div class="card-title">
                                        <h3 class="fw-bold fs-5 mb-0">Impacto de cierre</h3>
                                    </div>
                                    <div class="card-toolbar">
                                        <button class="btn btn-sm btn-light" id="rentabilidad_impacto_recargar" type="button"><i class="bi bi-bar-chart-line"></i> Medir</button>
                                    </div>
                                </div>
                                <div class="card-body pt-3" id="rentabilidad_impacto"></div>
                            </div>
                            <div class="card mb-6">
                                <div class="card-header border-0 pt-5">
                                    <div class="card-title">
                                        <h3 class="fw-bold fs-5 mb-0">Hallazgos de cierre</h3>
                                    </div>
                                    <div class="card-toolbar">
                                        <button class="btn btn-sm btn-light" id="rentabilidad_hallazgos_recargar" type="button"><i class="bi bi-exclamation-diamond"></i> Auditar</button>
                                    </div>
                                </div>
                                <div class="card-body pt-3" id="rentabilidad_hallazgos"></div>
                            </div>
                            <div class="card mb-6">
                                <div class="card-header border-0 pt-5">
                                    <div class="card-title">
                                        <h3 class="fw-bold fs-5 mb-0">Prioridad de cierre</h3>
                                    </div>
                                    <div class="card-toolbar">
                                        <button class="btn btn-sm btn-light" id="rentabilidad_prioridades_recargar" type="button"><i class="bi bi-sort-down"></i> Ordenar</button>
                                    </div>
                                </div>
                                <div class="card-body pt-3" id="rentabilidad_prioridades"></div>
                            </div>
                            <div class="card mb-6">
                                <div class="card-header border-0 pt-5">
                                    <div class="card-title">
                                        <h3 class="fw-bold fs-5 mb-0">Responsables de cierre</h3>
                                    </div>
                                    <div class="card-toolbar">
                                        <button class="btn btn-sm btn-light" id="rentabilidad_responsables_recargar" type="button"><i class="bi bi-people"></i> Resumir</button>
                                    </div>
                                </div>
                                <div class="card-body pt-3" id="rentabilidad_responsables"></div>
                            </div>
                            <div class="card mb-6">
                                <div class="card-header border-0 pt-5">
                                    <div class="card-title">
                                        <h3 class="fw-bold fs-5 mb-0">Checklist de cierre</h3>
                                    </div>
                                    <div class="card-toolbar">
                                        <button class="btn btn-sm btn-light" id="rentabilidad_checklist_recargar" type="button"><i class="bi bi-check2-square"></i> Revisar</button>
                                    </div>
                                </div>
                                <div class="card-body pt-3" id="rentabilidad_checklist"></div>
                            </div>
                            <div class="card mb-6">
                                <div class="card-header border-0 pt-5">
                                    <div class="card-title">
                                        <h3 class="fw-bold fs-5 mb-0">Paquete de autorizacion</h3>
                                    </div>
                                    <div class="card-toolbar">
                                        <button class="btn btn-sm btn-light" id="rentabilidad_autorizaciones_recargar" type="button"><i class="bi bi-shield-lock"></i> Preparar</button>
                                    </div>
                                </div>
                                <div class="card-body pt-3" id="rentabilidad_autorizaciones"></div>
                            </div>
                            <div class="card mb-6">
                                <div class="card-header border-0 pt-5">
                                    <div class="card-title">
                                        <h3 class="fw-bold fs-5 mb-0">Precios objetivo</h3>
                                    </div>
                                    <div class="card-toolbar">
                                        <button class="btn btn-sm btn-light" id="rentabilidad_precios_objetivo_recargar" type="button"><i class="bi bi-currency-dollar"></i> Simular</button>
                                    </div>
                                </div>
                                <div class="card-body pt-3" id="rentabilidad_precios_objetivo"></div>
                            </div>
                            <div class="card mb-6">
                                <div class="card-header border-0 pt-5">
                                    <div class="card-title">
                                        <h3 class="fw-bold fs-5 mb-0">Aprobacion de precios</h3>
                                    </div>
                                    <div class="card-toolbar">
                                        <button class="btn btn-sm btn-light" id="rentabilidad_precios_aprobacion_recargar" type="button"><i class="bi bi-patch-check"></i> Preflight</button>
                                    </div>
                                </div>
                                <div class="card-body pt-3" id="rentabilidad_precios_aprobacion"></div>
                            </div>
                            <div class="card mb-6">
                                <div class="card-header border-0 pt-5">
                                    <div class="card-title">
                                        <h3 class="fw-bold fs-5 mb-0">Aprobacion interna</h3>
                                    </div>
                                    <div class="card-toolbar">
                                        <button class="btn btn-sm btn-light" id="rentabilidad_aprobaciones_internas_recargar" type="button"><i class="bi bi-journal-check"></i> Preflight</button>
                                    </div>
                                </div>
                                <div class="card-body pt-3" id="rentabilidad_aprobaciones_internas"></div>
                            </div>
                            <div class="card mb-6">
                                <div class="card-header border-0 pt-5">
                                    <div class="card-title">
                                        <h3 class="fw-bold fs-5 mb-0">Paquete autorizacion aprobaciones</h3>
                                    </div>
                                    <div class="card-toolbar">
                                        <button class="btn btn-sm btn-light" id="rentabilidad_aprobaciones_autorizacion_recargar" type="button"><i class="bi bi-key"></i> Preparar</button>
                                    </div>
                                </div>
                                <div class="card-body pt-3" id="rentabilidad_aprobaciones_autorizacion"></div>
                            </div>
                            <div class="card mb-6">
                                <div class="card-header border-0 pt-5">
                                    <div class="card-title">
                                        <h3 class="fw-bold fs-5 mb-0">Aprobaciones internas guardadas</h3>
                                    </div>
                                    <div class="card-toolbar">
                                        <button class="btn btn-sm btn-light" id="rentabilidad_aprobaciones_internas_persistentes_recargar" type="button"><i class="bi bi-archive"></i> Consultar</button>
                                    </div>
                                </div>
                                <div class="card-body pt-3" id="rentabilidad_aprobaciones_internas_persistentes"></div>
                            </div>
                            <div class="card mb-6">
                                <div class="card-header border-0 pt-5">
                                    <div class="card-title">
                                        <h3 class="fw-bold fs-5 mb-0">Sensibilidad</h3>
                                    </div>
                                    <div class="card-toolbar">
                                        <button class="btn btn-sm btn-light" id="rentabilidad_sensibilidad_recargar" type="button"><i class="bi bi-graph-down-arrow"></i> Simular</button>
                                    </div>
                                </div>
                                <div class="card-body pt-3" id="rentabilidad_sensibilidad"></div>
                            </div>
                            <div class="card mb-6">
                                <div class="card-header border-0 pt-5">
                                    <div class="card-title">
                                        <h3 class="fw-bold fs-5 mb-0">Cierre comercial</h3>
                                    </div>
                                    <div class="card-toolbar">
                                        <button class="btn btn-sm btn-light" id="rentabilidad_cierre_recargar" type="button"><i class="bi bi-clipboard2-check"></i> Auditar</button>
                                    </div>
                                </div>
                                <div class="card-body pt-3" id="rentabilidad_cierre"></div>
                            </div>
                            <div class="card mb-6">
                                <div class="card-header border-0 pt-5">
                                    <div class="card-title">
                                        <h3 class="fw-bold fs-5 mb-0">Semaforo de cierre</h3>
                                    </div>
                                    <div class="card-toolbar">
                                        <button class="btn btn-sm btn-light" id="rentabilidad_semaforo_recargar" type="button"><i class="bi bi-check2-circle"></i> Evaluar</button>
                                    </div>
                                </div>
                                <div class="card-body pt-3" id="rentabilidad_semaforo"></div>
                            </div>
                            <div class="card mb-6">
                                <div class="card-header border-0 pt-5">
                                    <div class="card-title">
                                        <h3 class="fw-bold fs-5 mb-0">Variacion de costos</h3>
                                    </div>
                                    <div class="card-toolbar">
                                        <button class="btn btn-sm btn-light" id="rentabilidad_variaciones_recargar" type="button"><i class="bi bi-activity"></i> Auditar</button>
                                    </div>
                                </div>
                                <div class="card-body pt-3" id="rentabilidad_variaciones"></div>
                            </div>
                            <div class="card mb-6">
                                <div class="card-header border-0 pt-5">
                                    <div class="card-title">
                                        <h3 class="fw-bold fs-5 mb-0">Datos base para cierre</h3>
                                    </div>
                                    <div class="card-toolbar">
                                        <button class="btn btn-sm btn-light" id="rentabilidad_datos_base_recargar" type="button"><i class="bi bi-ui-checks-grid"></i> Auditar</button>
                                    </div>
                                </div>
                                <div class="card-body pt-3" id="rentabilidad_datos_base"></div>
                            </div>
                            <div class="card mb-6">
                                <div class="card-header border-0 pt-5">
                                    <div class="card-title">
                                        <h3 class="fw-bold fs-5 mb-0">Evidencia fiscal XML</h3>
                                    </div>
                                    <div class="card-toolbar">
                                        <button class="btn btn-sm btn-light" id="rentabilidad_fiscal_xml_recargar" type="button"><i class="bi bi-filetype-xml"></i> Auditar</button>
                                    </div>
                                </div>
                                <div class="card-body pt-3" id="rentabilidad_fiscal_xml"></div>
                            </div>
                            <div class="card mb-6">
                                <div class="card-header border-0 pt-5">
                                    <div class="card-title">
                                        <h3 class="fw-bold fs-5 mb-0">Preflight fiscal</h3>
                                    </div>
                                    <div class="card-toolbar">
                                        <button class="btn btn-sm btn-light" id="rentabilidad_fiscal_preflight_recargar" type="button"><i class="bi bi-shield-exclamation"></i> Preparar</button>
                                    </div>
                                </div>
                                <div class="card-body pt-3" id="rentabilidad_fiscal_preflight"></div>
                            </div>
                            <div class="card mb-6">
                                <div class="card-header border-0 pt-5">
                                    <div class="card-title">
                                        <h3 class="fw-bold fs-5 mb-0">Workflow comercial</h3>
                                    </div>
                                    <div class="card-toolbar">
                                        <button class="btn btn-sm btn-light" id="rentabilidad_workflow_recargar" type="button"><i class="bi bi-diagram-3"></i> Revisar</button>
                                    </div>
                                </div>
                                <div class="card-body pt-3" id="rentabilidad_workflow"></div>
                            </div>
                            <div class="card mb-6">
                                <div class="card-header border-0 pt-5">
                                    <div class="card-title">
                                        <h3 class="fw-bold fs-5 mb-0">Recomendaciones persistentes</h3>
                                    </div>
                                    <div class="card-toolbar d-flex gap-2">
                                        <?php if (SesionSeguridad::tienePermiso("rentabilidad.snapshot")): ?>
                                        <button class="btn btn-sm btn-light-success" id="rentabilidad_recomendaciones_guardar" type="button"><i class="bi bi-plus-circle"></i> Crear pendientes</button>
                                        <?php endif; ?>
                                        <button class="btn btn-sm btn-light-warning" id="rentabilidad_recomendaciones_preflight_recargar" type="button"><i class="bi bi-search"></i> Preflight</button>
                                        <button class="btn btn-sm btn-light" id="rentabilidad_recomendaciones_persistentes_recargar" type="button"><i class="bi bi-arrow-clockwise"></i> Consultar</button>
                                    </div>
                                </div>
                                <div class="card-body pt-3">
                                    <div id="rentabilidad_recomendaciones_preflight" class="mb-5"></div>
                                    <div id="rentabilidad_recomendaciones_persistentes"></div>
                                </div>
                            </div>
                            <div class="card mb-6">
                                <div class="card-header border-0 pt-5">
                                    <div class="card-title">
                                        <h3 class="fw-bold fs-5 mb-0">Snapshots recientes</h3>
                                    </div>
                                    <div class="card-toolbar">
                                        <button class="btn btn-sm btn-light" id="rentabilidad_snapshots_recargar" type="button"><i class="bi bi-clock-history"></i> Consultar</button>
                                    </div>
                                </div>
                                <div class="card-body pt-3" id="rentabilidad_snapshots"></div>
                            </div>
                            <div class="card mb-6">
                                <div class="card-header border-0 pt-5">
                                    <div class="card-title">
                                        <h3 class="fw-bold fs-5 mb-0">Vigencia de snapshots</h3>
                                    </div>
                                    <div class="card-toolbar">
                                        <button class="btn btn-sm btn-light" id="rentabilidad_snapshots_vigencia_recargar" type="button"><i class="bi bi-shield-check"></i> Auditar</button>
                                    </div>
                                </div>
                                <div class="card-body pt-3" id="rentabilidad_snapshots_vigencia"></div>
                            </div>
                            <div class="card mb-6">
                                <div class="card-header border-0 pt-5">
                                    <div class="card-title">
                                        <h3 class="fw-bold fs-5 mb-0">Costos de presentaciones</h3>
                                    </div>
                                    <div class="card-toolbar">
                                        <button class="btn btn-sm btn-light" id="rentabilidad_presentaciones_recargar" type="button"><i class="bi bi-calculator"></i> Auditar</button>
                                    </div>
                                </div>
                                <div class="card-body pt-3" id="rentabilidad_presentaciones"></div>
                            </div>
                            <div class="d-flex flex-wrap gap-3 mb-5" id="rentabilidad_resumen"></div>
                            <div class="card">
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table align-middle table-row-dashed gy-4">
                                            <thead>
                                            <tr class="text-muted fw-bold fs-7 text-uppercase">
                                                <th>SKU / producto</th>
                                                <th>Costo</th>
                                                <th>Precio sin imp.</th>
                                                <th>Margen</th>
                                                <th>Utilidad est.</th>
                                                <th>Minimo rentable</th>
                                                <th>Inventario</th>
                                                <th>Compras/XML</th>
                                                <th>Riesgo</th>
                                            </tr>
                                            </thead>
                                            <tbody id="rentabilidad_items"></tbody>
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
<div class="modal fade" id="rentabilidad_comparar_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h3 class="modal-title mb-1" id="rentabilidad_comparar_titulo">Comparar escenarios</h3>
                    <div class="text-muted fs-7">Menudeo, mayoreo y alianza para el SKU seleccionado</div>
                </div>
                <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="modal-body" id="rentabilidad_comparar_body"></div>
        </div>
    </div>
</div>
<script src="assets/plugins/global/plugins.bundle.js"></script>
<script src="assets/js/scripts.bundle.js"></script>
<script>
window.RENTABILIDAD_PERMISOS = <?= json_encode(array(
    "snapshot" => SesionSeguridad::tienePermiso("rentabilidad.snapshot")
)) ?>;
</script>
<script src="/assets/js/custom/apps/erp/rentabilidad/analisis.js?v=20260623-2"></script>
</body>
</html>
