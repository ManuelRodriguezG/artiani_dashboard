<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../../">
    <title>Proveedores ERP</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet">
    <link href="assets/css/style.bundle.css" rel="stylesheet">
</head>
<body id="kt_app_body" data-kt-app-layout="dark-sidebar" data-kt-app-header-fixed="true" data-kt-app-sidebar-enabled="true" data-kt-app-sidebar-fixed="true" class="app-default">
<div class="d-flex flex-column flex-root app-root">
    <div class="app-page flex-column flex-column-fluid">
        <?= include_once '../app/vistas/includes/header/header.php'; ?>
        <div class="app-wrapper flex-column flex-row-fluid">
            <?= include_once '../app/vistas/includes/header/sidebar.php'; ?>
            <main class="app-main flex-column flex-row-fluid">
                <div class="app-toolbar py-3 py-lg-6">
                    <div class="app-container container-fluid d-flex flex-stack">
                        <div>
                            <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Proveedores ERP</h1>
                            <span class="text-muted">Maestro operativo de proveedores</span>
                        </div>
                        <div class="d-flex gap-3">
                            <?php if (SesionSeguridad::tienePermiso('proveedores.crear')): ?>
                            <button class="btn btn-primary" type="button" id="proveedores_erp_nuevo">
                                <i class="bi bi-plus-lg"></i> Nuevo proveedor
                            </button>
                            <?php endif; ?>
                            <a class="btn btn-light-primary" href="/proveedor/auditoria_erp">
                                <i class="bi bi-clipboard-data"></i> Auditoria
                            </a>
                        </div>
                    </div>
                </div>
                <div class="app-content flex-column-fluid">
                    <div class="app-container container-fluid">
                        <div class="card">
                            <div class="card-header border-0 pt-6">
                                <div class="card-title flex-wrap gap-3 w-100">
                                    <div class="position-relative flex-grow-1 min-w-250px">
                                        <i class="bi bi-search fs-3 position-absolute ms-5 mt-3"></i>
                                        <input id="proveedores_erp_buscar" class="form-control form-control-solid ps-12" placeholder="Buscar proveedor, RFC, razon social o codigo">
                                    </div>
                                    <input id="proveedores_erp_estatus" class="form-control form-control-solid w-md-200px" placeholder="Estatus ERP">
                                    <input id="proveedores_erp_tipo" class="form-control form-control-solid w-md-200px" placeholder="Tipo proveedor">
                                </div>
                                <div class="card-toolbar">
                                    <span class="badge badge-light-primary" id="proveedores_erp_total">0 proveedores</span>
                                </div>
                            </div>
                            <div class="card-body pt-0">
                                <div class="table-responsive">
                                    <table class="table align-middle table-row-dashed gy-4 mb-0">
                                        <thead>
                                            <tr class="text-muted fw-bold fs-7 text-uppercase">
                                                <th>Proveedor</th>
                                                <th>Fiscal</th>
                                                <th>Tipo</th>
                                                <th>Estado</th>
                                                <th class="text-end">Contactos</th>
                                                <th class="text-end">Listas</th>
                                                <th class="text-end">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody id="proveedores_erp_body"></tbody>
                                    </table>
                                </div>
                                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-4 pt-5">
                                    <div class="d-flex align-items-center gap-3">
                                        <span class="text-muted fs-7">Mostrar</span>
                                        <select class="form-select form-select-sm w-80px" id="proveedores_erp_limite">
                                            <option value="25">25</option>
                                            <option value="50">50</option>
                                            <option value="100">100</option>
                                        </select>
                                    </div>
                                    <div class="d-flex align-items-center gap-3">
                                        <button class="btn btn-sm btn-light" type="button" id="proveedores_erp_anterior" title="Pagina anterior"><i class="bi bi-chevron-left"></i></button>
                                        <span class="text-muted fs-7" id="proveedores_erp_paginacion">Sin resultados</span>
                                        <button class="btn btn-sm btn-light" type="button" id="proveedores_erp_siguiente" title="Pagina siguiente"><i class="bi bi-chevron-right"></i></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</div>

<div class="modal fade" id="proveedores_erp_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h2 class="mb-1" id="proveedores_erp_modal_titulo">Proveedor</h2>
                    <span class="text-muted" id="proveedores_erp_modal_subtitulo">Ficha ERP</span>
                </div>
                <button type="button" class="btn btn-sm btn-icon btn-light" data-bs-dismiss="modal" title="Cerrar"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="modal-body">
                <div class="d-flex flex-wrap gap-2 mb-6" id="proveedores_erp_modal_badges"></div>
                <?php if (SesionSeguridad::tienePermiso('proveedores.editar') || SesionSeguridad::tienePermiso('proveedores.autorizar')): ?>
                <div class="d-flex justify-content-end gap-2 mb-4">
                    <?php if (SesionSeguridad::tienePermiso('proveedores.autorizar')): ?>
                    <button class="btn btn-sm btn-light-warning" type="button" id="proveedores_erp_cambiar_estatus">
                        <i class="bi bi-shield-check"></i> Cambiar estatus
                    </button>
                    <?php endif; ?>
                    <?php if (SesionSeguridad::tienePermiso('proveedores.editar')): ?>
                    <button class="btn btn-sm btn-light-primary" type="button" id="proveedores_erp_editar_general">
                        <i class="bi bi-pencil-square"></i> Editar generales
                    </button>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <ul class="nav nav-tabs nav-line-tabs mb-6 fs-6">
                    <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#proveedores_erp_tab_preparacion">Preparacion</a></li>
                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#proveedores_erp_tab_general">General</a></li>
                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#proveedores_erp_tab_fiscal">Fiscal y contactos</a></li>
                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#proveedores_erp_tab_condiciones">Condiciones y documentos</a></li>
                    <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#proveedores_erp_tab_listas">Listas y costos</a></li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="proveedores_erp_tab_preparacion">
                        <div class="row g-5">
                            <div class="col-lg-4">
                                <div class="border rounded p-5 h-100">
                                    <div class="text-muted fs-7">Preparacion operativa</div>
                                    <div class="d-flex align-items-end gap-2 mt-2">
                                        <span class="fs-2hx fw-bold" id="proveedores_erp_preparacion_pct">0%</span>
                                        <span class="badge badge-light-primary mb-3" id="proveedores_erp_preparacion_estado">Sin revisar</span>
                                    </div>
                                    <div class="progress h-8px mt-4">
                                        <div class="progress-bar bg-primary" id="proveedores_erp_preparacion_barra" style="width: 0%"></div>
                                    </div>
                                    <div class="text-muted fs-7 mt-4" id="proveedores_erp_preparacion_resumen">Checklist solo lectura para pruebas reales.</div>
                                    <div class="d-flex flex-wrap gap-2 mt-4" id="proveedores_erp_preparacion_conteos"></div>
                                </div>
                            </div>
                            <div class="col-lg-8">
                                <div class="table-responsive">
                                    <table class="table table-row-dashed gy-3 align-middle mb-0">
                                        <thead>
                                            <tr class="text-muted fw-bold fs-7 text-uppercase">
                                                <th>Punto</th>
                                                <th class="text-end">Valor</th>
                                                <th>Accion sugerida</th>
                                            </tr>
                                        </thead>
                                        <tbody id="proveedores_erp_preparacion_items"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="proveedores_erp_tab_general">
                        <div class="row g-5" id="proveedores_erp_general"></div>
                    </div>
                    <div class="tab-pane fade" id="proveedores_erp_tab_fiscal">
                        <div class="row g-5">
                            <div class="col-lg-6">
                                <div class="d-flex align-items-center justify-content-between gap-3 mb-4">
                                    <h4 class="fw-bold mb-0">Datos fiscales</h4>
                                    <?php if (SesionSeguridad::tienePermiso('proveedores.fiscales')): ?>
                                    <button class="btn btn-sm btn-light-primary" type="button" id="proveedores_erp_agregar_fiscal">
                                        <i class="bi bi-plus-lg"></i> Agregar fiscal
                                    </button>
                                    <?php endif; ?>
                                </div>
                                <div class="table-responsive"><table class="table table-row-dashed gy-3"><tbody id="proveedores_erp_fiscales"></tbody></table></div>
                            </div>
                            <div class="col-lg-6">
                                <div class="d-flex align-items-center justify-content-between gap-3 mb-4">
                                    <h4 class="fw-bold mb-0">Contactos</h4>
                                    <?php if (SesionSeguridad::tienePermiso('proveedores.contactos')): ?>
                                    <button class="btn btn-sm btn-light-primary" type="button" id="proveedores_erp_agregar_contacto">
                                        <i class="bi bi-plus-lg"></i> Agregar contacto
                                    </button>
                                    <?php endif; ?>
                                </div>
                                <div class="table-responsive"><table class="table table-row-dashed gy-3"><tbody id="proveedores_erp_contactos"></tbody></table></div>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="proveedores_erp_tab_condiciones">
                        <div class="row g-5">
                            <div class="col-lg-6">
                                <div class="d-flex align-items-center justify-content-between gap-3 mb-4">
                                    <h4 class="fw-bold mb-0">Condiciones</h4>
                                    <?php if (SesionSeguridad::tienePermiso('proveedores.condiciones')): ?>
                                    <button class="btn btn-sm btn-light-primary" type="button" id="proveedores_erp_agregar_condicion">
                                        <i class="bi bi-plus-lg"></i> Agregar condicion
                                    </button>
                                    <?php endif; ?>
                                </div>
                                <div class="table-responsive"><table class="table table-row-dashed gy-3"><tbody id="proveedores_erp_condiciones"></tbody></table></div>
                            </div>
                            <div class="col-lg-6">
                                <div class="d-flex align-items-center justify-content-between gap-3 mb-4">
                                    <h4 class="fw-bold mb-0">Documentos</h4>
                                    <?php if (SesionSeguridad::tienePermiso('proveedores.documentos')): ?>
                                    <button class="btn btn-sm btn-light-primary" type="button" id="proveedores_erp_agregar_documento">
                                        <i class="bi bi-plus-lg"></i> Agregar documento
                                    </button>
                                    <?php endif; ?>
                                </div>
                                <div class="table-responsive"><table class="table table-row-dashed gy-3"><tbody id="proveedores_erp_documentos"></tbody></table></div>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="proveedores_erp_tab_listas">
                        <div class="row g-5">
                            <div class="col-lg-8">
                                <div class="d-flex align-items-center justify-content-between gap-3 mb-4">
                                    <h4 class="fw-bold mb-0">Listas ERP</h4>
                                    <?php if (SesionSeguridad::tienePermiso('proveedores.listas')): ?>
                                    <button class="btn btn-sm btn-light-primary" type="button" id="proveedores_erp_agregar_lista">
                                        <i class="bi bi-plus-lg"></i> Agregar lista
                                    </button>
                                    <?php endif; ?>
                                </div>
                                <div class="table-responsive"><table class="table table-row-dashed gy-3"><tbody id="proveedores_erp_listas"></tbody></table></div>
                            </div>
                            <div class="col-lg-4">
                                <div class="d-flex align-items-center justify-content-between gap-3 mb-4">
                                    <h4 class="fw-bold mb-0">Costos</h4>
                                    <?php if (SesionSeguridad::tienePermiso('proveedores.costos')): ?>
                                    <button class="btn btn-sm btn-light-info" type="button" id="proveedores_erp_refrescar_costos" title="Refrescar historial de costos">
                                        <i class="bi bi-arrow-clockwise"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                                <div id="proveedores_erp_costos" class="d-flex flex-column gap-3"></div>
                                <?php if (SesionSeguridad::tienePermiso('proveedores.costos')): ?>
                                <input class="form-control form-control-sm mt-4" id="proveedores_erp_costos_buscar" maxlength="120" placeholder="Filtrar historial de costos">
                                <?php endif; ?>
                                <div class="table-responsive mt-4">
                                    <table class="table table-row-dashed gy-3">
                                        <tbody id="proveedores_erp_costos_historial"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <?php if (SesionSeguridad::tienePermiso('proveedores.auditoria')): ?>
                        <div class="separator my-8"></div>
                        <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-4 mb-4">
                            <h4 class="fw-bold mb-0">Comparativo Compras</h4>
                            <div class="d-flex gap-2 w-100 w-lg-auto">
                                <input class="form-control form-control-sm" id="proveedores_erp_compras_termino" maxlength="120" placeholder="Buscar SKU, nombre o codigo">
                                <button class="btn btn-sm btn-light-info" type="button" id="proveedores_erp_compras_comparar">
                                    <i class="bi bi-search"></i>
                                </button>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap gap-2 mb-4" id="proveedores_erp_compras_resumen"></div>
                        <div class="table-responsive">
                            <table class="table table-row-dashed gy-3 align-middle">
                                <thead>
                                    <tr class="text-muted fw-bold fs-7 text-uppercase">
                                        <th>SKU</th>
                                        <th class="text-end">Proveedores</th>
                                        <th class="text-end">Solicitudes</th>
                                        <th class="text-end">Ordenes</th>
                                    </tr>
                                </thead>
                                <tbody id="proveedores_erp_compras_comparativo"></tbody>
                            </table>
                        </div>
                        <div class="alert alert-danger d-none mt-4" id="proveedores_erp_compras_error"></div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="alert alert-danger d-none mt-6" id="proveedores_erp_modal_error"></div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="proveedores_erp_general_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form id="proveedores_erp_general_form" data-erp-ajax="true">
                <div class="modal-header">
                    <div>
                        <h2 class="mb-1" id="proveedores_erp_general_titulo">Datos generales</h2>
                        <span class="text-muted">Identidad y perfil operativo del proveedor</span>
                    </div>
                    <button type="button" class="btn btn-sm btn-icon btn-light" data-bs-dismiss="modal" title="Cerrar"><i class="bi bi-x-lg"></i></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(SesionSeguridad::csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="id_proveedor" id="proveedores_erp_form_id">
                    <div class="row g-5">
                        <div class="col-md-6">
                            <label class="form-label required">Proveedor</label>
                            <input class="form-control" name="proveedor" maxlength="255" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nombre comercial</label>
                            <input class="form-control" name="nombre_comercial" maxlength="255">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Nombre corto</label>
                            <input class="form-control" name="nombre_corto" maxlength="150">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Codigo ERP</label>
                            <input class="form-control" name="codigo_proveedor_erp" maxlength="80">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Origen</label>
                            <input class="form-control" name="origen" maxlength="40" placeholder="manual, legado, migracion">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Tipo proveedor</label>
                            <input class="form-control" name="tipo_proveedor" maxlength="80">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Clasificacion operativa</label>
                            <input class="form-control" name="clasificacion_operativa" maxlength="80">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Responsable interno ID</label>
                            <input class="form-control" name="responsable_interno_id" type="number" min="1" step="1">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notas</label>
                            <textarea class="form-control" name="notas" rows="3" maxlength="5000"></textarea>
                        </div>
                    </div>
                    <div class="alert alert-danger d-none mt-6" id="proveedores_erp_general_error"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Guardar generales</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="proveedores_erp_fiscal_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <form id="proveedores_erp_fiscal_form" data-erp-ajax="true">
                <div class="modal-header">
                    <div>
                        <h2 class="mb-1" id="proveedores_erp_fiscal_titulo">Datos fiscales</h2>
                        <span class="text-muted">Registro fiscal del proveedor, sin validacion SAT automatica</span>
                    </div>
                    <button type="button" class="btn btn-sm btn-icon btn-light" data-bs-dismiss="modal" title="Cerrar"><i class="bi bi-x-lg"></i></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(SesionSeguridad::csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="id_proveedor" id="proveedores_erp_fiscal_id_proveedor">
                    <input type="hidden" name="id_proveedor_fiscal">
                    <div class="row g-5">
                        <div class="col-md-3"><label class="form-label">RFC</label><input class="form-control text-uppercase" name="rfc" maxlength="20"></div>
                        <div class="col-md-5"><label class="form-label">Razon social</label><input class="form-control" name="razon_social" maxlength="255"></div>
                        <div class="col-md-2"><label class="form-label">Regimen</label><input class="form-control" name="regimen_fiscal" maxlength="120"></div>
                        <div class="col-md-2"><label class="form-label">CP fiscal</label><input class="form-control" name="codigo_postal_fiscal" maxlength="10"></div>
                        <div class="col-md-3"><label class="form-label">Pais</label><input class="form-control" name="pais" maxlength="80"></div>
                        <div class="col-md-3"><label class="form-label">Estado</label><input class="form-control" name="estado" maxlength="120"></div>
                        <div class="col-md-3"><label class="form-label">Municipio</label><input class="form-control" name="municipio" maxlength="120"></div>
                        <div class="col-md-3"><label class="form-label">Colonia</label><input class="form-control" name="colonia" maxlength="160"></div>
                        <div class="col-md-5"><label class="form-label">Calle</label><input class="form-control" name="calle" maxlength="160"></div>
                        <div class="col-md-2"><label class="form-label">No. ext.</label><input class="form-control" name="numero_exterior" maxlength="40"></div>
                        <div class="col-md-2"><label class="form-label">No. int.</label><input class="form-control" name="numero_interior" maxlength="40"></div>
                        <div class="col-md-3"><label class="form-label">Uso CFDI preferido</label><input class="form-control" name="uso_cfdi_preferido" maxlength="20"></div>
                        <div class="col-12"><label class="form-label">Domicilio fiscal completo</label><textarea class="form-control" name="domicilio_fiscal" rows="2" maxlength="5000"></textarea></div>
                        <div class="col-md-3"><label class="form-label">Fecha constancia</label><input class="form-control" type="date" name="fecha_constancia"></div>
                        <div class="col-md-3"><label class="form-label">Vigencia desde</label><input class="form-control" type="date" name="vigencia_desde"></div>
                        <div class="col-md-3"><label class="form-label">Vigencia hasta</label><input class="form-control" type="date" name="vigencia_hasta"></div>
                        <div class="col-md-3"><label class="form-label">Estatus fiscal</label><input class="form-control" name="estatus" maxlength="40"></div>
                    </div>
                    <div class="alert alert-danger d-none mt-6" id="proveedores_erp_fiscal_error"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Guardar fiscal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="proveedores_erp_contacto_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form id="proveedores_erp_contacto_form" data-erp-ajax="true">
                <div class="modal-header">
                    <div>
                        <h2 class="mb-1" id="proveedores_erp_contacto_titulo">Contacto</h2>
                        <span class="text-muted">Canales operativos por area del proveedor</span>
                    </div>
                    <button type="button" class="btn btn-sm btn-icon btn-light" data-bs-dismiss="modal" title="Cerrar"><i class="bi bi-x-lg"></i></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(SesionSeguridad::csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="id_proveedor">
                    <input type="hidden" name="id_contacto_proveedor">
                    <div class="row g-5">
                        <div class="col-md-4"><label class="form-label">Area</label><input class="form-control" name="area" maxlength="80" placeholder="compras, ventas, cobranza"></div>
                        <div class="col-md-5"><label class="form-label">Nombre</label><input class="form-control" name="nombre" maxlength="180"></div>
                        <div class="col-md-3"><label class="form-label">Puesto</label><input class="form-control" name="puesto" maxlength="120"></div>
                        <div class="col-md-5"><label class="form-label">Correo</label><input class="form-control" name="correo" type="email" maxlength="180"></div>
                        <div class="col-md-3"><label class="form-label">Telefono</label><input class="form-control" name="telefono" maxlength="60"></div>
                        <div class="col-md-2"><label class="form-label">Extension</label><input class="form-control" name="extension" maxlength="20"></div>
                        <div class="col-md-2"><label class="form-label">Prioridad</label><input class="form-control" name="prioridad" type="number" min="0" step="1"></div>
                        <div class="col-md-4"><label class="form-label">Celular</label><input class="form-control" name="celular" maxlength="60"></div>
                        <div class="col-md-4"><label class="form-label">WhatsApp</label><input class="form-control" name="whatsapp" maxlength="60"></div>
                        <div class="col-md-4"><label class="form-label">Estatus</label><input class="form-control" name="estatus" maxlength="40"></div>
                        <div class="col-12 d-flex flex-wrap gap-8 pt-2">
                            <label class="form-check form-switch form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox" name="es_principal" value="1">
                                <span class="form-check-label">Principal</span>
                            </label>
                            <label class="form-check form-switch form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox" name="recibe_ordenes_compra" value="1">
                                <span class="form-check-label">Recibe OC</span>
                            </label>
                            <label class="form-check form-switch form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox" name="recibe_notificaciones" value="1">
                                <span class="form-check-label">Recibe notificaciones</span>
                            </label>
                        </div>
                        <div class="col-12"><label class="form-label">Observaciones</label><textarea class="form-control" name="observaciones" rows="3" maxlength="5000"></textarea></div>
                    </div>
                    <div class="alert alert-danger d-none mt-6" id="proveedores_erp_contacto_error"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Guardar contacto</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="proveedores_erp_condicion_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <form id="proveedores_erp_condicion_form" data-erp-ajax="true">
                <div class="modal-header">
                    <div>
                        <h2 class="mb-1" id="proveedores_erp_condicion_titulo">Condiciones</h2>
                        <span class="text-muted">Referencia comercial y logistica, sin bloqueos automaticos</span>
                    </div>
                    <button type="button" class="btn btn-sm btn-icon btn-light" data-bs-dismiss="modal" title="Cerrar"><i class="bi bi-x-lg"></i></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(SesionSeguridad::csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="id_proveedor">
                    <input type="hidden" name="id_condicion_proveedor">
                    <div class="row g-5">
                        <div class="col-md-2"><label class="form-label">Moneda</label><input class="form-control text-uppercase" name="moneda_preferida" maxlength="10"></div>
                        <div class="col-md-3"><label class="form-label">Forma pago</label><input class="form-control" name="forma_pago_preferida" maxlength="80"></div>
                        <div class="col-md-3"><label class="form-label">Metodo pago</label><input class="form-control" name="metodo_pago_preferido" maxlength="80"></div>
                        <div class="col-md-2"><label class="form-label">Dias credito</label><input class="form-control" type="number" name="dias_credito" min="0" step="1"></div>
                        <div class="col-md-2"><label class="form-label">Estatus</label><input class="form-control" name="estatus" maxlength="40"></div>
                        <div class="col-md-3"><label class="form-label">Limite credito</label><input class="form-control" type="number" name="limite_credito" min="0" step="0.01"></div>
                        <div class="col-md-3"><label class="form-label">Minimo compra</label><input class="form-control" type="number" name="minimo_compra" min="0" step="0.01"></div>
                        <div class="col-md-3"><label class="form-label">Minimo unidades</label><input class="form-control" type="number" name="minimo_unidades" min="0" step="0.0001"></div>
                        <div class="col-md-3"><label class="form-label">Entrega dias</label><input class="form-control" type="number" name="tiempo_entrega_dias" min="0" step="1"></div>
                        <div class="col-md-4"><label class="form-label">Dias surtido</label><input class="form-control" name="dias_surtido" maxlength="120"></div>
                        <div class="col-md-4"><label class="form-label">Tipo flete</label><input class="form-control" name="tipo_flete" maxlength="80"></div>
                        <div class="col-md-2"><label class="form-label">Vigencia desde</label><input class="form-control" type="date" name="vigencia_desde"></div>
                        <div class="col-md-2"><label class="form-label">Vigencia hasta</label><input class="form-control" type="date" name="vigencia_hasta"></div>
                        <div class="col-12 d-flex flex-wrap gap-8 pt-2">
                            <label class="form-check form-switch form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox" name="requiere_orden_compra" value="1">
                                <span class="form-check-label">Requiere orden de compra</span>
                            </label>
                        </div>
                        <div class="col-md-6"><label class="form-label">Condiciones pago</label><textarea class="form-control" name="condiciones_pago" rows="3" maxlength="5000"></textarea></div>
                        <div class="col-md-6"><label class="form-label">Condiciones logisticas</label><textarea class="form-control" name="condiciones_logisticas" rows="3" maxlength="5000"></textarea></div>
                        <div class="col-md-6"><label class="form-label">Cobertura entrega</label><textarea class="form-control" name="cobertura_entrega" rows="2" maxlength="5000"></textarea></div>
                        <div class="col-md-6"><label class="form-label">Restricciones operativas</label><textarea class="form-control" name="restricciones_operativas" rows="2" maxlength="5000"></textarea></div>
                        <div class="col-12"><label class="form-label">Observaciones</label><textarea class="form-control" name="observaciones" rows="2" maxlength="5000"></textarea></div>
                    </div>
                    <div class="alert alert-danger d-none mt-6" id="proveedores_erp_condicion_error"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Guardar condiciones</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="proveedores_erp_documento_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form id="proveedores_erp_documento_form" data-erp-ajax="true">
                <div class="modal-header">
                    <div>
                        <h2 class="mb-1" id="proveedores_erp_documento_titulo">Documento</h2>
                        <span class="text-muted">Evidencia y referencia del proveedor</span>
                    </div>
                    <button type="button" class="btn btn-sm btn-icon btn-light" data-bs-dismiss="modal" title="Cerrar"><i class="bi bi-x-lg"></i></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(SesionSeguridad::csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="id_proveedor">
                    <input type="hidden" name="id_documento_proveedor">
                    <div class="row g-5">
                        <div class="col-md-4"><label class="form-label">Tipo documento</label><input class="form-control" name="tipo_documento" maxlength="80"></div>
                        <div class="col-md-4"><label class="form-label">Sensibilidad</label><input class="form-control" name="nivel_sensibilidad" maxlength="40"></div>
                        <div class="col-md-4"><label class="form-label">Estatus</label><input class="form-control" name="estatus" maxlength="40"></div>
                        <div class="col-md-4"><label class="form-label">Entidad origen</label><input class="form-control" name="entidad_origen" maxlength="80"></div>
                        <div class="col-md-4"><label class="form-label">ID referencia</label><input class="form-control" type="number" name="id_referencia" min="1" step="1"></div>
                        <div class="col-md-4"><label class="form-label">Tipo referencia</label><input class="form-control" name="referencia_tipo" maxlength="80"></div>
                        <div class="col-12"><label class="form-label">Referencia</label><input class="form-control" name="referencia" maxlength="255"></div>
                        <div class="col-md-6"><label class="form-label">Archivo nombre</label><input class="form-control" name="archivo_nombre" maxlength="255"></div>
                        <div class="col-md-3"><label class="form-label">Archivo tipo</label><input class="form-control" name="archivo_tipo" maxlength="120"></div>
                        <div class="col-md-3"><label class="form-label">Archivo tamano</label><input class="form-control" type="number" name="archivo_tamano" min="0" step="1"></div>
                        <div class="col-12"><label class="form-label">Archivo hash</label><input class="form-control" name="archivo_hash" maxlength="128"></div>
                        <div class="col-md-6"><label class="form-label">Vigencia desde</label><input class="form-control" type="date" name="vigencia_desde"></div>
                        <div class="col-md-6"><label class="form-label">Vigencia hasta</label><input class="form-control" type="date" name="vigencia_hasta"></div>
                        <div class="col-12"><label class="form-label">Metadatos JSON</label><textarea class="form-control" name="metadatos_json" rows="4" maxlength="5000"></textarea></div>
                    </div>
                    <div class="alert alert-danger d-none mt-6" id="proveedores_erp_documento_error"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Guardar documento</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="proveedores_erp_lista_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form id="proveedores_erp_lista_form" data-erp-ajax="true">
                <div class="modal-header">
                    <div>
                        <h2 class="mb-1" id="proveedores_erp_lista_titulo">Lista ERP</h2>
                        <span class="text-muted">Encabezado versionado de lista proveedor</span>
                    </div>
                    <button type="button" class="btn btn-sm btn-icon btn-light" data-bs-dismiss="modal" title="Cerrar"><i class="bi bi-x-lg"></i></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(SesionSeguridad::csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="id_proveedor">
                    <input type="hidden" name="id_lista_proveedor_erp">
                    <div class="row g-5">
                        <div class="col-md-6"><label class="form-label">Nombre lista</label><input class="form-control" name="nombre_lista" maxlength="180"></div>
                        <div class="col-md-3"><label class="form-label">Version</label><input class="form-control" name="version_lista" maxlength="80"></div>
                        <div class="col-md-3"><label class="form-label">Moneda</label><input class="form-control text-uppercase" name="moneda" maxlength="10"></div>
                        <div class="col-md-3"><label class="form-label">Origen</label><input class="form-control" name="origen" maxlength="40"></div>
                        <div class="col-md-3"><label class="form-label">ID lista legacy</label><input class="form-control" type="number" name="id_lista_legacy" min="1" step="1"></div>
                        <div class="col-md-3"><label class="form-label">Documento evidencia</label><input class="form-control" type="number" name="id_documento_proveedor" min="1" step="1"></div>
                        <div class="col-md-3">
                            <label class="form-label">Estatus</label>
                            <select class="form-select" name="estatus">
                                <option value="borrador">Borrador</option>
                                <option value="cargada">Cargada</option>
                                <option value="en_validacion">En validacion</option>
                                <option value="conciliacion">Conciliacion</option>
                                <option value="validada">Validada</option>
                                <option value="aplicada">Aplicada</option>
                                <option value="historica">Historica</option>
                                <option value="cancelada">Cancelada</option>
                            </select>
                        </div>
                        <div class="col-md-4"><label class="form-label">Fecha emision</label><input class="form-control" type="date" name="fecha_emision"></div>
                        <div class="col-md-4"><label class="form-label">Vigencia desde</label><input class="form-control" type="date" name="vigencia_desde"></div>
                        <div class="col-md-4"><label class="form-label">Vigencia hasta</label><input class="form-control" type="date" name="vigencia_hasta"></div>
                        <div class="col-12">
                            <label class="form-label">Archivo original</label>
                            <input class="form-control" type="file" name="archivo_lista" accept=".xlsx,.xls,.csv,.txt,.pdf,.zip">
                            <div class="text-muted fs-8 mt-1">Se guarda como evidencia; la vista previa y el mapeo de columnas se hacen despues.</div>
                        </div>
                        <div class="col-12"><label class="form-label">Observaciones</label><textarea class="form-control" name="observaciones" rows="3" maxlength="5000"></textarea></div>
                    </div>
                    <div class="alert alert-danger d-none mt-6" id="proveedores_erp_lista_error"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Guardar lista</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="proveedores_erp_lista_preview_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h2 class="mb-1" id="proveedores_erp_lista_preview_titulo">Vista previa de lista</h2>
                    <span class="text-muted" id="proveedores_erp_lista_preview_subtitulo">Archivo original</span>
                </div>
                <button type="button" class="btn btn-sm btn-icon btn-light" data-bs-dismiss="modal" title="Cerrar"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="modal-body">
                <div class="d-flex flex-wrap gap-2 mb-4" id="proveedores_erp_lista_preview_resumen"></div>
                <div class="alert alert-warning d-none mb-6" id="proveedores_erp_lista_preview_aviso"></div>
                <div class="row g-3 mb-6" id="proveedores_erp_lista_preview_mapeo"></div>
                <div class="table-responsive">
                    <table class="table table-row-dashed gy-3 align-middle">
                        <thead id="proveedores_erp_lista_preview_head"></thead>
                        <tbody id="proveedores_erp_lista_preview_body"></tbody>
                    </table>
                </div>
                <div class="alert alert-danger d-none mt-6" id="proveedores_erp_lista_preview_error"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" id="proveedores_erp_lista_preview_importar">
                    <i class="bi bi-box-arrow-in-down"></i> Importar renglones
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="proveedores_erp_lista_detalle_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h2 class="mb-1" id="proveedores_erp_lista_detalle_titulo">Detalle de lista</h2>
                    <span class="text-muted" id="proveedores_erp_lista_detalle_subtitulo">Renglones capturados</span>
                </div>
                <div class="d-flex gap-2">
                    <?php if (SesionSeguridad::tienePermiso('proveedores.matching')): ?>
                    <button class="btn btn-sm btn-light-info" type="button" id="proveedores_erp_lista_matching_dry_run">
                        <i class="bi bi-diagram-3"></i> Matching
                    </button>
                    <button class="btn btn-sm btn-light-success" type="button" id="proveedores_erp_relaciones_lote_preview">
                        <i class="bi bi-list-check"></i> Preview relaciones
                    </button>
                    <?php endif; ?>
                    <?php if (SesionSeguridad::tienePermiso('proveedores.costos')): ?>
                    <button class="btn btn-sm btn-light-danger" type="button" id="proveedores_erp_costos_lote_preview">
                        <i class="bi bi-currency-dollar"></i> Preview costos
                    </button>
                    <button class="btn btn-sm btn-light-dark" type="button" id="proveedores_erp_costo_referencia_preview">
                        <i class="bi bi-graph-up-arrow"></i> Preview costo ref
                    </button>
                    <?php endif; ?>
                    <?php if (SesionSeguridad::tienePermiso('proveedores.auditoria')): ?>
                    <button class="btn btn-sm btn-light-warning" type="button" id="proveedores_erp_lista_incidencias_dry_run">
                        <i class="bi bi-exclamation-triangle"></i> Resolver pendientes
                    </button>
                    <?php endif; ?>
                    <?php if (SesionSeguridad::tienePermiso('proveedores.listas')): ?>
                    <button class="btn btn-sm btn-light-primary" type="button" id="proveedores_erp_agregar_lista_detalle">
                        <i class="bi bi-plus-lg"></i> Agregar renglon
                    </button>
                    <?php endif; ?>
                    <button type="button" class="btn btn-sm btn-icon btn-light" data-bs-dismiss="modal" title="Cerrar"><i class="bi bi-x-lg"></i></button>
                </div>
            </div>
            <div class="modal-body">
                <div class="d-flex flex-wrap gap-2 mb-4" id="proveedores_erp_lista_detalle_revision"></div>
                <div class="d-flex flex-wrap align-items-center gap-2 mb-4">
                    <div class="position-relative flex-grow-1 min-w-250px">
                        <i class="bi bi-search position-absolute top-50 translate-middle-y ms-4 text-muted"></i>
                        <input class="form-control form-control-sm ps-10" id="proveedores_erp_lista_detalle_buscar" placeholder="Buscar SKU, codigo, marca o descripcion del proveedor">
                    </div>
                    <button class="btn btn-sm btn-light" type="button" id="proveedores_erp_lista_detalle_limpiar_busqueda" title="Limpiar busqueda">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2 mb-4">
                    <button class="btn btn-sm btn-light-primary active" type="button" data-lista-detalle-filtro="todos" title="Muestra todos los renglones cargados en la lista.">Todos</button>
                    <button class="btn btn-sm btn-light" type="button" data-lista-detalle-filtro="operativos" title="Renglones que ya tienen alguna relacion, seleccion o dato util para compras.">Operativos</button>
                    <button class="btn btn-sm btn-light" type="button" data-lista-detalle-filtro="informativos" title="Renglones cargados como referencia, sin relacion operativa todavia.">Informativos</button>
                    <button class="btn btn-sm btn-light" type="button" data-lista-detalle-filtro="con_sku" title="Renglones que ya apuntan a un SKU del Catalogo ERP.">Con SKU ERP</button>
                    <button class="btn btn-sm btn-light" type="button" data-lista-detalle-filtro="sin_sku" title="Renglones que aun no estan relacionados con Catalogo ERP.">Sin SKU ERP</button>
                    <button class="btn btn-sm btn-light" type="button" data-lista-detalle-filtro="listo_relacion" title="Ya tiene SKU ERP, unidad, factor y cantidad minima para aplicar la relacion proveedor-SKU.">Listo relacion</button>
                    <button class="btn btn-sm btn-light" type="button" data-lista-detalle-filtro="listo_costo" title="Ya tiene relacion proveedor-SKU y datos suficientes para aplicar costo vigente.">Listo costo</button>
                    <button class="btn btn-sm btn-light" type="button" data-lista-detalle-filtro="costo_pendiente" title="Renglones sin costo capturado.">Costo pendiente</button>
                    <button class="btn btn-sm btn-light" type="button" data-lista-detalle-filtro="unidad_pendiente" title="Renglones sin unidad de compra o factor de conversion.">Unidad/factor pendiente</button>
                    <button class="btn btn-sm btn-light" type="button" data-lista-detalle-filtro="moneda_pendiente" title="Renglones sin moneda capturada.">Moneda pendiente</button>
                    <button class="btn btn-sm btn-light" type="button" data-lista-detalle-filtro="productivo_sql" title="Renglones provenientes de las tablas productivas importadas como base de trabajo.">Productivo</button>
                    <span class="text-muted fs-8 ms-auto" id="proveedores_erp_lista_detalle_conteo">0 renglones</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-row-dashed gy-3 align-middle">
                        <thead>
                            <tr class="text-muted fw-bold fs-7 text-uppercase">
                                <th>SKU proveedor</th>
                                <th>Descripcion</th>
                                <th>Unidad</th>
                                <th class="text-end">Costo</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="proveedores_erp_lista_detalle_body"></tbody>
                    </table>
                </div>
                <div class="alert alert-danger d-none mt-6" id="proveedores_erp_lista_detalle_error"></div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="proveedores_erp_lista_detalle_form_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <form id="proveedores_erp_lista_detalle_form" data-erp-ajax="true">
                <div class="modal-header">
                    <div>
                        <h2 class="mb-1" id="proveedores_erp_lista_detalle_form_titulo">Renglon de lista</h2>
                        <span class="text-muted">Dato capturado desde lista proveedor</span>
                    </div>
                    <button type="button" class="btn btn-sm btn-icon btn-light" data-bs-dismiss="modal" title="Cerrar"><i class="bi bi-x-lg"></i></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(SesionSeguridad::csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="id_proveedor">
                    <input type="hidden" name="id_lista_proveedor_erp">
                    <input type="hidden" name="id_lista_detalle_erp">
                    <div class="row g-5">
                        <div class="col-md-3"><label class="form-label">SKU proveedor</label><input class="form-control" name="sku_proveedor" maxlength="120"></div>
                        <div class="col-md-3"><label class="form-label">Codigo barras</label><input class="form-control" name="codigo_barras" maxlength="120"></div>
                        <div class="col-md-3"><label class="form-label">Codigo interno</label><input class="form-control" name="codigo_interno" maxlength="120"></div>
                        <div class="col-md-3"><label class="form-label">Marca proveedor</label><input class="form-control" name="marca_proveedor" maxlength="160"></div>
                        <div class="col-12"><label class="form-label">Descripcion proveedor</label><textarea class="form-control" name="descripcion_proveedor" rows="3" maxlength="5000"></textarea></div>
                        <div class="col-md-3"><label class="form-label">Unidad texto</label><input class="form-control" name="unidad_compra_texto" maxlength="80"></div>
                        <div class="col-md-3"><label class="form-label">Unidad compra</label><select class="form-select" name="id_unidad_compra" id="proveedores_erp_lista_detalle_unidad"><option value="">Seleccionar unidad</option></select></div>
                        <div class="col-md-3"><label class="form-label">Factor</label><input class="form-control" type="number" name="factor_conversion" step="0.000001"></div>
                        <div class="col-md-3"><label class="form-label">Cantidad minima</label><input class="form-control" type="number" name="cantidad_minima" step="0.0001"></div>
                        <div class="col-md-3"><label class="form-label">Costo</label><input class="form-control" type="number" name="costo" step="0.0001"></div>
                        <div class="col-md-3"><label class="form-label">Moneda</label><input class="form-control text-uppercase" name="moneda" maxlength="10"></div>
                        <div class="col-md-3"><label class="form-label">Incluye impuestos</label><input class="form-control" type="number" name="costo_incluye_impuestos" min="0" max="1" step="1"></div>
                        <div class="col-md-3"><label class="form-label">Existencia reportada</label><input class="form-control" type="number" name="existencia_reportada" step="0.0001"></div>
                        <div class="col-12">
                            <label class="form-label">Buscar SKU ERP</label>
                            <div class="input-group">
                                <input class="form-control" id="proveedores_erp_lista_detalle_sku_buscar" placeholder="Buscar por SKU, nombre o codigo">
                                <button class="btn btn-light-primary" type="button" id="proveedores_erp_lista_detalle_sku_btn"><i class="bi bi-search"></i> Buscar</button>
                            </div>
                            <div class="mt-3" id="proveedores_erp_lista_detalle_sku_resultados"></div>
                        </div>
                        <div class="col-md-3"><label class="form-label">Estado match</label><input class="form-control" name="estado_match" maxlength="40"></div>
                        <div class="col-md-6"><label class="form-label">Criterio match</label><input class="form-control" name="criterio_match" maxlength="120"></div>
                        <div class="col-12">
                            <button class="btn btn-sm btn-light" type="button" data-bs-toggle="collapse" data-bs-target="#proveedores_erp_lista_detalle_tecnico">
                                <i class="bi bi-info-circle"></i> Campos tecnicos
                            </button>
                            <div class="collapse mt-4" id="proveedores_erp_lista_detalle_tecnico">
                                <div class="alert alert-info mb-4">Estos IDs normalmente los llena el matching o la migracion. No necesitas capturarlos a mano.</div>
                                <div class="row g-5">
                                    <div class="col-md-4"><label class="form-label">ID SKU ERP</label><input class="form-control" type="number" name="id_sku" min="1" step="1" readonly></div>
                                    <div class="col-md-4"><label class="form-label">ID SKU proveedor</label><input class="form-control" type="number" name="id_sku_proveedor" min="1" step="1" readonly></div>
                                    <div class="col-md-4"><label class="form-label">ID producto legacy</label><input class="form-control" type="number" name="id_producto_legacy" min="1" step="1" readonly></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12"><label class="form-label">Observaciones</label><textarea class="form-control" name="observaciones" rows="2" maxlength="5000"></textarea></div>
                    </div>
                    <div class="alert alert-danger d-none mt-6" id="proveedores_erp_lista_detalle_form_error"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Guardar renglon</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="proveedores_erp_matching_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h2 class="mb-1">Matching dry-run</h2>
                    <span class="text-muted" id="proveedores_erp_matching_subtitulo">Propuestas sin escritura</span>
                </div>
                <button type="button" class="btn btn-sm btn-icon btn-light" data-bs-dismiss="modal" title="Cerrar"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="modal-body">
                <div class="d-flex flex-wrap gap-2 mb-5" id="proveedores_erp_matching_resumen"></div>
                <div class="d-flex flex-wrap gap-2 mb-4">
                    <input class="form-control form-control-sm w-md-250px" id="proveedores_erp_matching_buscar" placeholder="Filtrar candidato o renglon">
                    <select class="form-select form-select-sm w-md-200px" id="proveedores_erp_matching_estado">
                        <option value="">Todos los estados</option>
                        <option value="relacionado">Relacionado</option>
                        <option value="match_exacto_pendiente">Exacto pendiente</option>
                        <option value="match_posible">Posible</option>
                        <option value="ambiguo">Ambiguo</option>
                        <option value="sin_match">Sin match</option>
                    </select>
                    <span class="text-muted fs-8 align-self-center" id="proveedores_erp_matching_conteo"></span>
                </div>
                <div class="table-responsive">
                    <table class="table table-row-dashed gy-3 align-middle">
                        <thead>
                            <tr class="text-muted fw-bold fs-7 text-uppercase">
                                <th>Renglon proveedor</th>
                                <th>Estado</th>
                                <th>Candidato</th>
                                <th>Criterio</th>
                            </tr>
                        </thead>
                        <tbody id="proveedores_erp_matching_body"></tbody>
                    </table>
                </div>
                <div class="alert alert-danger d-none mt-6" id="proveedores_erp_matching_error"></div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="proveedores_erp_relaciones_lote_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h2 class="mb-1">Preview relaciones en lote</h2>
                    <span class="text-muted" id="proveedores_erp_relaciones_lote_subtitulo">Solo lectura, sin aplicar cambios</span>
                </div>
                <button type="button" class="btn btn-sm btn-icon btn-light" data-bs-dismiss="modal" title="Cerrar"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    Esta vista solo muestra que renglones podrian aplicar relacion proveedor-SKU. No crea ni actualiza relaciones.
                </div>
                <div class="d-flex flex-wrap gap-2 mb-5" id="proveedores_erp_relaciones_lote_resumen"></div>
                <ul class="nav nav-tabs nav-line-tabs mb-5">
                    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#proveedores_erp_relaciones_lote_incluidos" type="button">Incluidos</button></li>
                    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#proveedores_erp_relaciones_lote_excluidos" type="button">Excluidos</button></li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="proveedores_erp_relaciones_lote_incluidos">
                        <div class="table-responsive">
                            <table class="table table-row-dashed gy-3 align-middle">
                                <thead>
                                    <tr class="text-muted fw-bold fs-7 text-uppercase">
                                        <th>Renglon proveedor</th>
                                        <th>SKU ERP</th>
                                        <th>Accion prevista</th>
                                        <th>Compra</th>
                                    </tr>
                                </thead>
                                <tbody id="proveedores_erp_relaciones_lote_incluidos_body"></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="proveedores_erp_relaciones_lote_excluidos">
                        <div class="table-responsive">
                            <table class="table table-row-dashed gy-3 align-middle">
                                <thead>
                                    <tr class="text-muted fw-bold fs-7 text-uppercase">
                                        <th>Renglon proveedor</th>
                                        <th>SKU ERP</th>
                                        <th>Motivo</th>
                                        <th>Detalle</th>
                                    </tr>
                                </thead>
                                <tbody id="proveedores_erp_relaciones_lote_excluidos_body"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="alert alert-danger d-none mt-6" id="proveedores_erp_relaciones_lote_error"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-success" id="proveedores_erp_relaciones_lote_aplicar" disabled>
                    <i class="bi bi-link-45deg"></i> Aplicar relaciones incluidas
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="proveedores_erp_costos_lote_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h2 class="mb-1">Preview costos en lote</h2>
                    <span class="text-muted" id="proveedores_erp_costos_lote_subtitulo">Solo lectura, sin aplicar cambios</span>
                </div>
                <button type="button" class="btn btn-sm btn-icon btn-light" data-bs-dismiss="modal" title="Cerrar"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    Esta vista solo muestra que renglones podrian aplicar costo vigente. No crea historicos, no actualiza costos y no toca costo de referencia.
                </div>
                <div class="d-flex flex-wrap gap-2 mb-5" id="proveedores_erp_costos_lote_resumen"></div>
                <ul class="nav nav-tabs nav-line-tabs mb-5">
                    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#proveedores_erp_costos_lote_incluidos" type="button">Incluidos</button></li>
                    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#proveedores_erp_costos_lote_excluidos" type="button">Excluidos</button></li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="proveedores_erp_costos_lote_incluidos">
                        <div class="table-responsive">
                            <table class="table table-row-dashed gy-3 align-middle">
                                <thead>
                                    <tr class="text-muted fw-bold fs-7 text-uppercase">
                                        <th>Renglon proveedor</th>
                                        <th>SKU / Relacion</th>
                                        <th>Costo</th>
                                        <th>Accion prevista</th>
                                    </tr>
                                </thead>
                                <tbody id="proveedores_erp_costos_lote_incluidos_body"></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="proveedores_erp_costos_lote_excluidos">
                        <div class="table-responsive">
                            <table class="table table-row-dashed gy-3 align-middle">
                                <thead>
                                    <tr class="text-muted fw-bold fs-7 text-uppercase">
                                        <th>Renglon proveedor</th>
                                        <th>SKU / Relacion</th>
                                        <th>Motivo</th>
                                        <th>Detalle</th>
                                    </tr>
                                </thead>
                                <tbody id="proveedores_erp_costos_lote_excluidos_body"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="alert alert-danger d-none mt-6" id="proveedores_erp_costos_lote_error"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-danger" id="proveedores_erp_costos_lote_aplicar" disabled>
                    <i class="bi bi-currency-dollar"></i> Aplicar costos incluidos
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="proveedores_erp_costo_referencia_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h2 class="mb-1">Preview costo referencia</h2>
                    <span class="text-muted" id="proveedores_erp_costo_referencia_subtitulo">Solo lectura, sin aplicar cambios</span>
                </div>
                <button type="button" class="btn btn-sm btn-icon btn-light" data-bs-dismiss="modal" title="Cerrar"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    Esta vista compara el costo de referencia del SKU contra la mejor fuente disponible. Compra real recibida tiene prioridad; proveedor vigente solo puede llenar SKUs sin costo de referencia.
                </div>
                <div class="d-flex flex-wrap gap-2 mb-5" id="proveedores_erp_costo_referencia_resumen"></div>
                <div class="table-responsive">
                    <table class="table table-row-dashed gy-3 align-middle">
                        <thead>
                            <tr class="text-muted fw-bold fs-7 text-uppercase">
                                <th>SKU ERP</th>
                                <th>Proveedor</th>
                                <th class="text-end">Actual</th>
                                <th class="text-end">Propuesto</th>
                                <th>Diferencia</th>
                                <th>Advertencias</th>
                            </tr>
                        </thead>
                        <tbody id="proveedores_erp_costo_referencia_body"></tbody>
                    </table>
                </div>
                <div class="alert alert-danger d-none mt-6" id="proveedores_erp_costo_referencia_error"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-danger" id="proveedores_erp_costo_referencia_aplicar" disabled>
                    <i class="bi bi-check2-circle"></i> Aplicar elegibles
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="proveedores_erp_incidencias_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h2 class="mb-1">Pendientes y resolucion</h2>
                    <span class="text-muted" id="proveedores_erp_incidencias_subtitulo">Rutas de solucion sin aplicar cambios automaticos</span>
                </div>
                <button type="button" class="btn btn-sm btn-icon btn-light" data-bs-dismiss="modal" title="Cerrar"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="modal-body">
                <div class="d-flex flex-wrap gap-2 mb-5" id="proveedores_erp_incidencias_resumen"></div>
                <div class="d-flex flex-wrap gap-2 mb-4">
                    <input class="form-control form-control-sm w-md-250px" id="proveedores_erp_incidencias_buscar" placeholder="Filtrar pendiente, SKU o texto">
                    <select class="form-select form-select-sm w-md-180px" id="proveedores_erp_incidencias_severidad">
                        <option value="">Todas las severidades</option>
                        <option value="bloqueante">Bloqueante</option>
                        <option value="alta">Alta</option>
                        <option value="media">Media</option>
                        <option value="baja">Baja</option>
                    </select>
                    <select class="form-select form-select-sm w-md-220px" id="proveedores_erp_incidencias_tipo">
                        <option value="">Todos los tipos</option>
                        <option value="proveedor_sku_sin_match">Sin SKU ERP</option>
                        <option value="proveedor_match_ambiguo">Match ambiguo</option>
                        <option value="proveedor_unidad_factor_dudoso">Unidad/factor</option>
                        <option value="proveedor_costo_dudoso">Costo/moneda</option>
                        <option value="proveedor_sku_sin_codigo_confiable">Codigo catalogo</option>
                        <option value="proveedor_sku_fiscal_incompleto">Fiscal catalogo</option>
                    </select>
                    <span class="text-muted fs-8 align-self-center" id="proveedores_erp_incidencias_conteo"></span>
                </div>
                <div class="table-responsive">
                    <table class="table table-row-dashed gy-3 align-middle">
                        <thead>
                            <tr class="text-muted fw-bold fs-7 text-uppercase">
                                <th>Tipo</th>
                                <th>Severidad</th>
                                <th>Referencia</th>
                                <th>Como resolver</th>
                            </tr>
                        </thead>
                        <tbody id="proveedores_erp_incidencias_body"></tbody>
                    </table>
                </div>
                <div class="alert alert-danger d-none mt-6" id="proveedores_erp_incidencias_error"></div>
            </div>
        </div>
    </div>
</div>

<script src="assets/plugins/global/plugins.bundle.js"></script>
<script src="assets/js/scripts.bundle.js"></script>
<script>
window.PROVEEDORES_ERP_PERMISOS = {
    crear: <?= json_encode(SesionSeguridad::tienePermiso('proveedores.crear')) ?>,
    editar: <?= json_encode(SesionSeguridad::tienePermiso('proveedores.editar')) ?>,
    fiscales: <?= json_encode(SesionSeguridad::tienePermiso('proveedores.fiscales')) ?>,
    contactos: <?= json_encode(SesionSeguridad::tienePermiso('proveedores.contactos')) ?>,
    condiciones: <?= json_encode(SesionSeguridad::tienePermiso('proveedores.condiciones')) ?>,
    documentos: <?= json_encode(SesionSeguridad::tienePermiso('proveedores.documentos')) ?>,
    documentos_sensibles: <?= json_encode(SesionSeguridad::tienePermiso('proveedores.documentos_sensibles')) ?>,
    listas: <?= json_encode(SesionSeguridad::tienePermiso('proveedores.listas')) ?>,
    matching: <?= json_encode(SesionSeguridad::tienePermiso('proveedores.matching')) ?>,
    costos: <?= json_encode(SesionSeguridad::tienePermiso('proveedores.costos')) ?>,
    auditoria: <?= json_encode(SesionSeguridad::tienePermiso('proveedores.auditoria')) ?>,
    autorizar: <?= json_encode(SesionSeguridad::tienePermiso('proveedores.autorizar')) ?>
};
</script>
<script src="/assets/js/custom/apps/erp/proveedores/listado_erp.js?v=20260614-20"></script>
</body>
</html>
