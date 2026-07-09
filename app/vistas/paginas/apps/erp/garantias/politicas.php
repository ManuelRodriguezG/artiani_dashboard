<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../../">
    <title>Garantias ERP</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet">
    <link href="assets/css/style.bundle.css" rel="stylesheet">
</head>
<body id="kt_app_body" data-kt-app-layout="dark-sidebar" data-kt-app-header-fixed="true" data-kt-app-sidebar-enabled="true" data-kt-app-sidebar-fixed="true" class="app-default">
<input type="hidden" id="garantias_puede_politicas" value="<?= SesionSeguridad::tienePermiso('garantias.politicas') ? '1' : '0' ?>">
<input type="hidden" id="garantias_politica_id" value="">
<input type="hidden" id="garantias_regla_id" value="">
<div class="d-flex flex-column flex-root app-root">
    <div class="app-page flex-column flex-column-fluid">
        <?= include_once '../app/vistas/includes/header/header.php'; ?>
        <div class="app-wrapper flex-column flex-row-fluid">
            <?= include_once '../app/vistas/includes/header/sidebar.php'; ?>
            <main class="app-main flex-column flex-row-fluid">
                <div class="app-toolbar py-3 py-lg-6">
                    <div class="app-container container-fluid d-flex flex-stack">
                        <div>
                            <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Garantias</h1>
                            <span class="text-muted">Politicas, reglas vigentes y pruebas de elegibilidad</span>
                        </div>
                        <button type="button" class="btn btn-light-primary" id="garantias_refrescar">
                            <i class="bi bi-arrow-clockwise"></i>
                            Actualizar
                        </button>
                    </div>
                </div>

                <div class="app-content flex-column-fluid">
                    <div class="app-container container-fluid">
                        <div class="row g-5 mb-7">
                            <div class="col-md-3">
                                <div class="border border-gray-300 rounded p-5 h-100">
                                    <div class="text-muted fw-semibold">Politicas activas</div>
                                    <div class="fs-2 fw-bold" id="garantias_total_politicas">0</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="border border-gray-300 rounded p-5 h-100">
                                    <div class="text-muted fw-semibold">Reglas activas</div>
                                    <div class="fs-2 fw-bold" id="garantias_total_reglas">0</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="border border-gray-300 rounded p-5 h-100">
                                    <div class="text-muted fw-semibold">SKUs sin regla</div>
                                    <div class="fs-2 fw-bold text-warning" id="garantias_skus_sin_regla">0</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="border border-gray-300 rounded p-5 h-100">
                                    <div class="text-muted fw-semibold">Estado resolver</div>
                                    <div class="fs-5 fw-bold" id="garantias_estado_resolver">Pendiente</div>
                                </div>
                            </div>
                        </div>

                        <div class="border border-gray-300 rounded p-5 mb-7" id="garantias_cobertura_panel">
                            <div class="d-flex flex-column flex-md-row flex-md-stack gap-3">
                                <div>
                                    <div class="fw-bold text-gray-800">Cobertura de garantias</div>
                                    <div class="text-muted fs-7" id="garantias_cobertura_resumen">Calculando cobertura...</div>
                                </div>
                                <div class="d-flex flex-wrap gap-2" id="garantias_cobertura_ejemplos"></div>
                            </div>
                        </div>

                        <!--
                          IA: Codex GPT-5
                          Fecha: 2026-06-29
                          Proposito: separar conceptos operativos de Garantias para que politica, regla, cobertura e impacto no se confundan.
                          Impacto: Garantias UI; texto informativo sin persistencia.
                          Contrato: no ejecuta consultas ni escritura.
                        -->
                        <div class="row g-5 mb-7">
                            <div class="col-md-3">
                                <div class="border border-gray-200 rounded p-4 h-100">
                                    <div class="fw-bold mb-1">Politica</div>
                                    <div class="text-muted fs-7">Plantilla reutilizable: tipo, duracion, requisitos y resultados permitidos.</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="border border-gray-200 rounded p-4 h-100">
                                    <div class="fw-bold mb-1">Regla</div>
                                    <div class="text-muted fs-7">Asignacion de una politica a SKU, producto, categoria, marca o proveedor.</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="border border-gray-200 rounded p-4 h-100">
                                    <div class="fw-bold mb-1">Cobertura</div>
                                    <div class="text-muted fs-7">Cuantos SKUs activos ya tienen una regla aplicable.</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="border border-gray-200 rounded p-4 h-100">
                                    <div class="fw-bold mb-1">Impacto</div>
                                    <div class="text-muted fs-7">Vista previa de cuantos SKUs tocaria una regla antes de guardarla.</div>
                                </div>
                            </div>
                        </div>

                        <?php if (SesionSeguridad::tienePermiso('garantias.politicas')): ?>
                        <div class="card mb-7">
                            <div class="card-header border-0 pt-6">
                                <div class="card-title">
                                    <div>
                                        <h2 class="fw-bold mb-1">Configurar politica</h2>
                                        <span class="text-muted">Validacion previa sin guardado</span>
                                    </div>
                                </div>
                                <div class="card-toolbar">
                                    <button type="button" class="btn btn-light" id="garantias_politica_limpiar">
                                        <i class="bi bi-eraser"></i>
                                        Limpiar
                                    </button>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <!--
                                  IA: Codex GPT-5
                                  Fecha: 2026-06-27
                                  Proposito: aclarar que el guardado de politicas es operacion normal del ERP y no requiere respaldo manual.
                                  Impacto: Garantias UI; conserva confirmacion y auditoria sin mezclar DDL con CRUD operativo.
                                  Contrato: texto informativo; no modifica persistencia por si mismo.
                                -->
                                <div class="alert alert-light-info border border-info border-dashed mb-6">
                                    <div class="fw-bold mb-1">Guardado operativo</div>
                                    <div class="text-muted fs-7">Crear o editar politicas y reglas es una operacion normal del modulo. El sistema valida permisos y registra auditoria. Para reglas por categoria, marca o proveedor, revisa `Impacto` antes de guardar.</div>
                                </div>

                                <div class="row g-4">
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold" for="garantias_politica_codigo">Codigo</label>
                                        <input type="text" class="form-control form-control-solid" id="garantias_politica_codigo" value="GAR_TIENDA_7_DIAS_CAMBIO">
                                    </div>
                                    <div class="col-md-5">
                                        <label class="form-label fw-semibold" for="garantias_politica_nombre">Nombre</label>
                                        <input type="text" class="form-control form-control-solid" id="garantias_politica_nombre" value="Garantia tienda 7 dias cambio">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fw-semibold" for="garantias_politica_tipo">Tipo</label>
                                        <select class="form-select form-select-solid" id="garantias_politica_tipo">
                                            <option value="sin_garantia">Sin garantia</option>
                                            <option value="garantia_tienda" selected>Garantia tienda</option>
                                            <option value="garantia_proveedor">Garantia proveedor</option>
                                            <option value="garantia_fabricante">Garantia fabricante</option>
                                            <option value="cambio_inmediato">Cambio inmediato</option>
                                            <option value="reparacion">Reparacion</option>
                                            <option value="satisfaccion_limitada">Satisfaccion limitada</option>
                                            <option value="caducidad_calidad">Caducidad/calidad</option>
                                        </select>
                                        <div class="text-muted fs-8 mt-1" id="garantias_politica_tipo_ayuda">La tienda responde directamente con sus reglas internas.</div>
                                    </div>
                                    <div class="col-md-1">
                                        <label class="form-label fw-semibold" for="garantias_politica_duracion">Dias</label>
                                        <input type="number" min="0" step="1" class="form-control form-control-solid" id="garantias_politica_duracion" value="7">
                                    </div>
                                    <div class="col-md-1">
                                        <label class="form-label fw-semibold" for="garantias_politica_unidad">Unidad</label>
                                        <select class="form-select form-select-solid" id="garantias_politica_unidad">
                                            <option value="dias">Dias</option>
                                            <option value="meses">Meses</option>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-semibold" for="garantias_politica_descripcion">Descripcion</label>
                                        <textarea class="form-control form-control-solid" id="garantias_politica_descripcion" rows="2"></textarea>
                                    </div>
                                </div>

                                <div class="separator my-6"></div>

                                <!--
                                  IA: Codex GPT-5
                                  Fecha: 2026-06-27
                                  Proposito: explicar requisitos/resultados configurables de garantia sin obligar al operador a consultar documentacion externa.
                                  Impacto: Garantias UI; no modifica contratos ni persistencia.
                                  Contrato: textos de ayuda operativa, sin escritura de BD.
                                -->
                                <div class="row g-5">
                                    <div class="col-lg-6">
                                        <div class="fw-bold text-gray-800 mb-1">Requisitos</div>
                                        <div class="text-muted fs-7 mb-3">Condiciones que el operador debe revisar antes de aceptar el reclamo.</div>
                                        <div class="row g-3">
                                            <div class="col-md-4"><label class="form-check form-switch form-check-custom form-check-solid"><input class="form-check-input garantia-politica-check" type="checkbox" id="garantias_req_ticket" checked><span class="form-check-label">Ticket</span></label><div class="text-muted fs-8 mt-1">Debe existir comprobante de venta.</div></div>
                                            <div class="col-md-4"><label class="form-check form-switch form-check-custom form-check-solid"><input class="form-check-input garantia-politica-check" type="checkbox" id="garantias_req_cliente"><span class="form-check-label">Cliente</span></label><div class="text-muted fs-8 mt-1">Requiere identificar al cliente.</div></div>
                                            <div class="col-md-4"><label class="form-check form-switch form-check-custom form-check-solid"><input class="form-check-input garantia-politica-check" type="checkbox" id="garantias_req_serie"><span class="form-check-label">Serie</span></label><div class="text-muted fs-8 mt-1">Se valida numero de serie/equipo.</div></div>
                                            <div class="col-md-4"><label class="form-check form-switch form-check-custom form-check-solid"><input class="form-check-input garantia-politica-check" type="checkbox" id="garantias_req_lote"><span class="form-check-label">Lote</span></label><div class="text-muted fs-8 mt-1">Se revisa lote, caducidad o trazabilidad.</div></div>
                                            <div class="col-md-4"><label class="form-check form-switch form-check-custom form-check-solid"><input class="form-check-input garantia-politica-check" type="checkbox" id="garantias_req_empaque"><span class="form-check-label">Empaque</span></label><div class="text-muted fs-8 mt-1">Debe conservar empaque o accesorios.</div></div>
                                            <div class="col-md-4"><label class="form-check form-switch form-check-custom form-check-solid"><input class="form-check-input garantia-politica-check" type="checkbox" id="garantias_req_diagnostico"><span class="form-check-label">Diagnostico</span></label><div class="text-muted fs-8 mt-1">Requiere revision antes de decidir.</div></div>
                                            <div class="col-md-4"><label class="form-check form-switch form-check-custom form-check-solid"><input class="form-check-input garantia-politica-check" type="checkbox" id="garantias_req_fotos"><span class="form-check-label">Fotos</span></label><div class="text-muted fs-8 mt-1">Debe anexar evidencia visual.</div></div>
                                            <div class="col-md-4"><label class="form-check form-switch form-check-custom form-check-solid"><input class="form-check-input garantia-politica-check" type="checkbox" id="garantias_req_autorizacion"><span class="form-check-label">Autorizacion</span></label><div class="text-muted fs-8 mt-1">Necesita aprobacion de supervisor.</div></div>
                                            <div class="col-md-4"><label class="form-check form-switch form-check-custom form-check-solid"><input class="form-check-input garantia-politica-check" type="checkbox" id="garantias_req_proveedor"><span class="form-check-label">Proveedor</span></label><div class="text-muted fs-8 mt-1">Depende de respuesta externa.</div></div>
                                        </div>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="fw-bold text-gray-800 mb-1">Resultados permitidos</div>
                                        <div class="text-muted fs-7 mb-3">Opciones que el operador podria resolver si la garantia procede.</div>
                                        <div class="row g-3">
                                            <div class="col-md-4"><label class="form-check form-switch form-check-custom form-check-solid"><input class="form-check-input garantia-politica-check" type="checkbox" id="garantias_res_cambio" checked><span class="form-check-label">Cambio</span></label><div class="text-muted fs-8 mt-1">Puede reemplazarse por otro producto.</div></div>
                                            <div class="col-md-4"><label class="form-check form-switch form-check-custom form-check-solid"><input class="form-check-input garantia-politica-check" type="checkbox" id="garantias_res_reparacion"><span class="form-check-label">Reparacion</span></label><div class="text-muted fs-8 mt-1">Puede pasar a reparacion o servicio.</div></div>
                                            <div class="col-md-4"><label class="form-check form-switch form-check-custom form-check-solid"><input class="form-check-input garantia-politica-check" type="checkbox" id="garantias_res_devolucion"><span class="form-check-label">Devolucion</span></label><div class="text-muted fs-8 mt-1">Puede devolver dinero si procede.</div></div>
                                            <div class="col-md-4"><label class="form-check form-switch form-check-custom form-check-solid"><input class="form-check-input garantia-politica-check" type="checkbox" id="garantias_res_nota"><span class="form-check-label">Nota</span></label><div class="text-muted fs-8 mt-1">Puede generar saldo/nota de credito.</div></div>
                                            <div class="col-md-4"><label class="form-check form-switch form-check-custom form-check-solid"><input class="form-check-input garantia-politica-check" type="checkbox" id="garantias_res_proveedor"><span class="form-check-label">Enviar proveedor</span></label><div class="text-muted fs-8 mt-1">Puede abrir seguimiento externo.</div></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end gap-3 mt-6">
                                    <button type="button" class="btn btn-primary" id="garantias_politica_validar">
                                        <i class="bi bi-check2-circle"></i>
                                        Validar politica
                                    </button>
                                    <button type="button" class="btn btn-light-danger" id="garantias_politica_guardar">
                                        <i class="bi bi-save"></i>
                                        Guardar politica
                                    </button>
                                </div>
                                <div class="mt-4" id="garantias_politica_resultado">
                                    <span class="text-muted">Sin validacion ejecutada.</span>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="row g-5 mb-7">
                            <div class="col-xl-8">
                                <div class="card h-100">
                                    <div class="card-header border-0 pt-6">
                                        <div class="card-title">
                                            <div>
                                                <h2 class="fw-bold mb-1">Politicas base</h2>
                                                <span class="text-muted">Consulta de configuracion vigente</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-body pt-0">
                                        <div class="table-responsive">
                                            <table class="table align-middle table-row-dashed gy-4">
                                                <thead>
                                                <tr class="text-muted fw-bold fs-7 text-uppercase">
                                                    <th>Codigo</th>
                                                    <th>Tipo</th>
                                                    <th>Duracion</th>
                                                    <th>Requisitos</th>
                                                    <th>Reglas</th>
                                                    <th>Estado</th>
                                                </tr>
                                                </thead>
                                                <tbody id="garantias_politicas_body">
                                                <tr><td colspan="6" class="text-center text-muted py-10">Cargando politicas...</td></tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-xl-4">
                                <div class="card h-100">
                                    <div class="card-header border-0 pt-6">
                                        <div class="card-title">
                                            <div>
                                                <h2 class="fw-bold mb-1">Prueba por SKU</h2>
                                                <span class="text-muted">Resolver y simular snapshot</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-body pt-0">
                                        <div class="mb-4">
                                            <label class="form-label fw-semibold" for="garantias_sku_busqueda">SKU o producto</label>
                                            <div class="input-group">
                                                <input type="text" class="form-control form-control-solid" id="garantias_sku_busqueda" value="ART.10198">
                                                <button type="button" class="btn btn-light-primary" id="garantias_buscar_sku">
                                                    <i class="bi bi-search"></i>
                                                </button>
                                            </div>
                                            <input type="hidden" id="garantias_sku_prueba" value="7">
                                            <div class="mt-3" id="garantias_sku_resultados"></div>
                                            <div class="text-muted fs-7 mt-2" id="garantias_sku_seleccionado">Seleccionado: ID 7 - ART.10198</div>
                                        </div>
                                        <div class="mb-4">
                                            <label class="form-label fw-semibold" for="garantias_canal_prueba">Canal</label>
                                            <select class="form-select form-select-solid" id="garantias_canal_prueba">
                                                <option value="pos">POS</option>
                                                <option value="ecommerce">Ecommerce</option>
                                                <option value="mayoreo">Mayoreo</option>
                                            </select>
                                        </div>
                                        <div class="d-flex gap-3 mb-5">
                                            <button type="button" class="btn btn-primary flex-grow-1" id="garantias_resolver_sku">
                                                <i class="bi bi-shield-check"></i>
                                                Resolver
                                            </button>
                                            <button type="button" class="btn btn-light-primary flex-grow-1" id="garantias_snapshot_sku">
                                                <i class="bi bi-receipt"></i>
                                                Snapshot
                                            </button>
                                        </div>
                                        <div class="border border-gray-300 rounded p-4" id="garantias_resultado_prueba">
                                            <div class="text-muted">Sin consulta ejecutada.</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php if (SesionSeguridad::tienePermiso('garantias.politicas')): ?>
                        <div class="card mb-7">
                            <div class="card-header border-0 pt-6">
                                <div class="card-title">
                                    <div>
                                        <h2 class="fw-bold mb-1">Validar regla nueva</h2>
                                        <span class="text-muted">Dry-run previo a cualquier asignacion real</span>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <!--
                                  IA: Codex GPT-5
                                  Fecha: 2026-06-27
                                  Proposito: aclarar que una politica se puede reutilizar con varias reglas y distintos ambitos.
                                  Impacto: Garantias UI; reduce errores de asignacion por SKU/producto/categoria/marca/proveedor.
                                  Contrato: texto informativo y labels, sin escritura de BD.
                                -->
                                <div class="alert alert-light-info border border-info border-dashed mb-5">
                                    <div class="fw-bold mb-1">Asignacion de politicas</div>
                                    <div class="fs-7 text-gray-700">
                                        Una politica puede tener varias reglas. Puedes asignarla a un SKU especifico, a todo un producto, categoria, marca o proveedor.
                                        Si un SKU coincide con varias reglas, se usa la regla mas especifica y con mejor prioridad.
                                    </div>
                                    <div class="d-flex flex-wrap gap-2 mt-3">
                                        <span class="badge badge-light-primary">1. SKU</span>
                                        <span class="badge badge-light-primary">2. Producto</span>
                                        <span class="badge badge-light-primary">3. Categoria</span>
                                        <span class="badge badge-light-primary">4. Marca</span>
                                        <span class="badge badge-light-primary">5. Proveedor</span>
                                        <span class="badge badge-light">Menor prioridad gana si empatan</span>
                                    </div>
                                </div>
                                <div class="row g-4 align-items-end">
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold" for="garantias_dryrun_politica">Politica</label>
                                        <select class="form-select form-select-solid" id="garantias_dryrun_politica"></select>
                                        <div class="text-muted fs-8 mt-1">Plantilla reutilizable de garantia.</div>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fw-semibold" for="garantias_dryrun_ambito">Asignar por</label>
                                        <select class="form-select form-select-solid" id="garantias_dryrun_ambito">
                                            <option value="sku">SKU</option>
                                            <option value="producto">Producto</option>
                                            <option value="categoria">Categoria</option>
                                            <option value="marca">Marca</option>
                                            <option value="proveedor">Proveedor</option>
                                        </select>
                                        <div class="text-muted fs-8 mt-1">Define el alcance de la regla.</div>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fw-semibold" for="garantias_dryrun_referencia">ID referencia</label>
                                        <input type="number" min="1" step="1" class="form-control form-control-solid" id="garantias_dryrun_referencia" value="7">
                                        <div class="text-muted fs-8 mt-1">ID del SKU, producto, categoria, marca o proveedor.</div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold" for="garantias_referencia_busqueda">Buscar referencia</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control form-control-solid" id="garantias_referencia_busqueda" placeholder="SKU, producto, categoria, marca o proveedor">
                                            <button type="button" class="btn btn-light-primary" id="garantias_buscar_referencia" title="Buscar referencia">
                                                <i class="bi bi-search"></i>
                                            </button>
                                        </div>
                                        <div class="text-muted fs-8 mt-1" id="garantias_referencia_seleccionada">Referencia actual: ID 7</div>
                                    </div>
                                    <div class="col-md-1">
                                        <label class="form-label fw-semibold" for="garantias_dryrun_prioridad">Prioridad</label>
                                        <input type="number" min="1" step="1" class="form-control form-control-solid" id="garantias_dryrun_prioridad" value="10">
                                        <div class="text-muted fs-8 mt-1">Menor numero gana.</div>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fw-semibold" for="garantias_dryrun_canal">Canal</label>
                                        <select class="form-select form-select-solid" id="garantias_dryrun_canal">
                                            <option value="">Todos</option>
                                            <option value="pos">POS</option>
                                            <option value="ecommerce">Ecommerce</option>
                                            <option value="mayoreo">Mayoreo</option>
                                        </select>
                                        <div class="text-muted fs-8 mt-1">Vacio aplica a todos.</div>
                                    </div>
                                    <div class="col-md-1">
                                        <button type="button" class="btn btn-light-primary w-100" id="garantias_validar_regla" title="Validar">
                                            <i class="bi bi-check2-circle"></i>
                                        </button>
                                    </div>
                                    <div class="col-md-1">
                                        <button type="button" class="btn btn-light-info w-100" id="garantias_impacto_regla" title="Impacto">
                                            <i class="bi bi-diagram-3"></i>
                                        </button>
                                    </div>
                                    <div class="col-md-1">
                                        <button type="button" class="btn btn-light-danger w-100" id="garantias_guardar_regla" title="Guardar regla">
                                            <i class="bi bi-save"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="mt-4" id="garantias_dryrun_regla_resultado">
                                    <span class="text-muted">Sin validacion ejecutada.</span>
                                </div>
                                <div class="mt-4" id="garantias_referencia_resultados"></div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="card">
                            <div class="card-header border-0 pt-6">
                                <div class="card-title">
                                    <div>
                                        <h2 class="fw-bold mb-1">Reglas asignadas</h2>
                                        <span class="text-muted">Alcance actual de politicas por ambito</span>
                                    </div>
                                </div>
                                <div class="card-toolbar d-flex gap-3">
                                    <select class="form-select form-select-solid w-175px" id="garantias_filtro_regla_estatus">
                                        <option value="activa">Activas</option>
                                        <option value="inactiva">Inactivas</option>
                                        <option value="">Todas</option>
                                    </select>
                                    <select class="form-select form-select-solid w-175px" id="garantias_filtro_ambito">
                                        <option value="">Todos</option>
                                        <option value="sku">SKU</option>
                                        <option value="producto">Producto</option>
                                        <option value="categoria">Categoria</option>
                                        <option value="marca">Marca</option>
                                        <option value="proveedor">Proveedor</option>
                                    </select>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="d-flex flex-wrap gap-3 mb-5" id="garantias_reglas_resumen"></div>
                                <div class="table-responsive">
                                    <table class="table align-middle table-row-dashed gy-4">
                                        <thead>
                                        <tr class="text-muted fw-bold fs-7 text-uppercase">
                                            <th>Politica</th>
                                            <th>Ambito</th>
                                            <th>Referencia</th>
                                            <th>Canal</th>
                                            <th>Prioridad</th>
                                            <th>Vigencia</th>
                                            <th>Estado</th>
                                        </tr>
                                        </thead>
                                        <tbody id="garantias_reglas_body">
                                        <tr><td colspan="7" class="text-center text-muted py-10">Cargando reglas...</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</div>
<script src="assets/plugins/global/plugins.bundle.js"></script>
<script src="assets/js/scripts.bundle.js"></script>
<script src="/assets/js/custom/apps/erp/garantias/politicas.js?v=20260629-1"></script>
</body>
</html>
