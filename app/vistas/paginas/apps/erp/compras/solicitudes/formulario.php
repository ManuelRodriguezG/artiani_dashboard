<?php
$idSolicitud = isset($datos["id_solicitud"]) ? intval($datos["id_solicitud"]) : 0;
$modo = isset($datos["modo"]) ? $datos["modo"] : "editar";
$puedeAprobar = !empty($datos["puede_aprobar"]);
$puedeCancelar = !empty($datos["puede_cancelar"]);
$puedeEditar = $modo !== "ver" && (!array_key_exists("puede_editar", $datos) || !empty($datos["puede_editar"]));
$puedeEditarPermiso = !empty($datos["puede_editar"]);
$puedeCrear = !empty($datos["puede_crear"]);
$puedeImprimir = $idSolicitud > 0;
$usuarioActualNombre = trim(
    (isset($_SESSION["nombre_mostrar"]) ? $_SESSION["nombre_mostrar"] : "") ?: (
        (isset($_SESSION["nombres"]) ? $_SESSION["nombres"] : "") . " " .
        (isset($_SESSION["apellido_paterno"]) ? $_SESSION["apellido_paterno"] : "") . " " .
        (isset($_SESSION["apellido_materno"]) ? $_SESSION["apellido_materno"] : "")
    )
);
$usuarioActualNombre = $usuarioActualNombre !== "" ? $usuarioActualNombre : "Usuario actual";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../../">
    <title>Solicitud de compra</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet">
    <link href="assets/css/style.bundle.css" rel="stylesheet">
</head>
<body id="kt_app_body" data-kt-app-layout="dark-sidebar" data-kt-app-header-fixed="true" data-kt-app-sidebar-enabled="true" data-kt-app-sidebar-fixed="true" class="app-default">
<input type="hidden" id="solicitud_id" value="<?= $idSolicitud ?>">
<input type="hidden" id="solicitud_modo" value="<?= htmlspecialchars($modo) ?>">
<input type="hidden" id="solicitud_permiso_aprobar" value="<?= $puedeAprobar ? 1 : 0 ?>">
<input type="hidden" id="solicitud_permiso_cancelar" value="<?= $puedeCancelar ? 1 : 0 ?>">
<input type="hidden" id="solicitud_permiso_editar" value="<?= $puedeEditar ? 1 : 0 ?>">
<input type="hidden" id="solicitud_permiso_editar_accion" value="<?= $puedeEditarPermiso ? 1 : 0 ?>">
<input type="hidden" id="solicitud_permiso_crear" value="<?= $puedeCrear ? 1 : 0 ?>">
<div class="d-flex flex-column flex-root app-root">
    <div class="app-page flex-column flex-column-fluid">
        <?= include_once '../app/vistas/includes/header/header.php'; ?>
        <div class="app-wrapper flex-column flex-row-fluid">
            <?= include_once '../app/vistas/includes/header/sidebar.php'; ?>
            <main class="app-main flex-column flex-row-fluid">
                <div class="app-toolbar py-3 py-lg-6">
                    <div class="app-container container-fluid d-flex flex-stack">
                        <div>
                            <h1 class="page-heading text-dark fw-bold fs-3 mb-1" id="solicitud_titulo">Nueva solicitud</h1>
                            <span class="text-muted" id="solicitud_estado_texto">Borrador</span>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="/compra/mostrar_solicitudes" class="btn btn-light"><i class="bi bi-arrow-left"></i></a>
                            <?php if ($puedeImprimir): ?>
                                <a class="btn btn-light-success" href="/compra/solicitud_imprimir_erp/<?= $idSolicitud ?>" target="_blank" rel="noopener"><i class="bi bi-file-earmark-text"></i> Documento</a>
                            <?php endif; ?>
                            <a class="btn btn-light-info d-none" id="solicitud_editar_link" href="/compra/editar_solicitud/<?= $idSolicitud ?>"><i class="bi bi-pencil-square"></i> Editar</a>
                            <button class="btn btn-light-info d-none" id="solicitud_ver_diferencias">Ver diferencias</button>
                            <button class="btn btn-light-primary solicitud-edicion" id="solicitud_guardar_borrador">Guardar borrador</button>
                            <button class="btn btn-primary solicitud-edicion" id="solicitud_enviar">Enviar a aprobacion</button>
                        </div>
                    </div>
                </div>
                <div class="app-content flex-column-fluid">
                    <div class="app-container container-fluid">
                        <div class="row g-6 mb-7">
                            <div class="col-lg-4">
                                <label class="form-label required">Proveedor</label>
                                <select class="form-select form-select-solid" id="solicitud_proveedor"><option value="">Seleccionar</option></select>
                            </div>
                            <div class="col-lg-4">
                                <label class="form-label">Solicitante</label>
                                <input class="form-control form-control-solid" id="solicitud_solicitante" value="<?= htmlspecialchars($usuarioActualNombre) ?>" disabled>
                            </div>
                            <div class="col-lg-4">
                                <label class="form-label">Almacen destino</label>
                                <select class="form-select form-select-solid" id="solicitud_almacen"><option value="">Seleccionar</option></select>
                            </div>
                            <div class="col-md-4 col-lg-2">
                                <label class="form-label">Prioridad</label>
                                <select class="form-select form-select-solid" id="solicitud_prioridad">
                                    <option value="baja">Baja</option>
                                    <option value="normal" selected>Normal</option>
                                    <option value="alta">Alta</option>
                                    <option value="urgente">Urgente</option>
                                </select>
                            </div>
                            <div class="col-md-4 col-lg-2">
                                <label class="form-label">Fecha requerida</label>
                                <input class="form-control form-control-solid" type="date" id="solicitud_fecha_requerida">
                            </div>
                            <div class="col-md-4 col-lg-4">
                                <label class="form-label">Observaciones</label>
                                <input class="form-control form-control-solid" id="solicitud_observaciones" maxlength="1000">
                            </div>
                        </div>

                        <div class="solicitud-edicion mb-6">
                            <label class="form-label">Agregar producto del proveedor</label>
                            <div class="position-relative">
                                <i class="bi bi-search position-absolute ms-5 mt-3 fs-3"></i>
                                <input id="solicitud_buscar_sku" class="form-control form-control-solid ps-12" placeholder="Buscar por SKU o nombre" disabled>
                                <div id="solicitud_resultados" class="position-absolute bg-white border rounded w-100 shadow-sm z-index-3 d-none" style="max-height:320px;overflow:auto"></div>
                            </div>
                        </div>

                        <div class="solicitud-edicion mb-6">
                            <label class="form-label">Agregar producto sugerido (nuevo)</label>
                            <div class="row g-2 align-items-end">
                                <div class="col-md-3">
                                    <label class="form-label text-muted fs-8" for="solicitud_sku_nuevo">SKU sugerido</label>
                                    <input id="solicitud_sku_nuevo" class="form-control form-control-solid" placeholder="SKU sugerido">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label text-muted fs-8" for="solicitud_nombre_nuevo">Descripción del producto</label>
                                    <input id="solicitud_nombre_nuevo" class="form-control form-control-solid" placeholder="Descripción del producto">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label text-muted fs-8" for="solicitud_cantidad_nueva">Cantidad</label>
                                    <input id="solicitud_cantidad_nueva" type="number" min="1" step="1" class="form-control form-control-solid" value="1">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label text-muted fs-8" for="solicitud_costo_nuevo">Costo estimado</label>
                                    <input id="solicitud_costo_nuevo" type="number" min="0" step="0.01" class="form-control form-control-solid" value="0">
                                </div>
                                <div class="col-md-1">
                                    <label class="form-label text-muted fs-8 opacity-0" for="solicitud_agregar_nuevo">&nbsp;</label>
                                    <button id="solicitud_agregar_nuevo" class="btn btn-light-primary w-100" type="button">Agregar</button>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed gy-4">
                                <thead>
                                    <tr class="text-muted fw-bold fs-7 text-uppercase">
                                        <th>SKU</th>
                                        <th>Producto</th>
                                        <th class="text-end">Cantidad</th>
                                        <th class="text-end">Costo estimado</th>
                                        <th class="text-end">Subtotal</th>
                                        <th>Observaciones</th>
                                        <th class="solicitud-edicion"></th>
                                    </tr>
                                </thead>
                                <tbody id="solicitud_items"></tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="4" class="text-end fw-bold">Total estimado</td>
                                        <td class="text-end fw-bold fs-5" id="solicitud_total">$0.00</td>
                                        <td colspan="2"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <div id="solicitud_aprobacion" class="d-none d-flex justify-content-end gap-2 mt-6">
                            <button class="btn btn-light-danger d-none" data-solicitud-estatus="rechazada">Rechazar</button>
                            <button class="btn btn-success d-none" data-solicitud-estatus="aprobada">Aprobar</button>
                            <button class="btn btn-dark d-none" data-solicitud-estatus="cancelada">Cancelar</button>
                        </div>
                        <div id="solicitud_generar_orden" class="d-none d-flex justify-content-end mt-6">
                            <button class="btn btn-primary" id="solicitud_generar_orden_btn">
                                <i class="bi bi-file-earmark-plus"></i> Generar orden de compra
                            </button>
                        </div>
                        <div id="solicitud_orden_relacionada" class="d-none mt-8">
                            <div class="separator mb-6"></div>
                            <div class="d-flex flex-column flex-md-row justify-content-between gap-3">
                                <div>
                                    <h2 class="fs-5 fw-bold mb-1">Orden relacionada</h2>
                                    <span class="text-muted">Trazabilidad de la solicitud hacia compra</span>
                                </div>
                                <a class="btn btn-light-primary align-self-start" id="solicitud_orden_link" href="#">
                                    <i class="bi bi-eye"></i> Ver orden
                                </a>
                            </div>
                            <div class="table-responsive mt-4">
                                <table class="table align-middle table-row-dashed gy-3">
                                    <thead>
                                        <tr class="text-muted fw-bold fs-7 text-uppercase">
                                            <th>Folio</th>
                                            <th>Proveedor</th>
                                            <th>Fecha</th>
                                            <th class="text-end">Partidas</th>
                                            <th class="text-end">Total</th>
                                            <th>Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody id="solicitud_orden_body"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</div>

<div class="modal fade" id="solicitud_diferencias_modal" tabindex="-1" aria-labelledby="solicitud_diferencias_modal_titulo" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="solicitud_diferencias_modal_titulo">Diferencias contra orden de compra</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3 mb-5">
                    <div class="col-lg-4">
                        <div class="card card-dashed">
                            <div class="card-body">
                                <div class="text-muted fs-8">Faltantes</div>
                                <div class="fs-4 fw-bold text-warning" id="solicitud_dif_resumen_faltantes">0</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="card card-dashed">
                            <div class="card-body">
                                <div class="text-muted fs-8">Adicionales</div>
                                <div class="fs-4 fw-bold text-danger" id="solicitud_dif_resumen_adicionales">0</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="card card-dashed">
                            <div class="card-body">
                                <div class="text-muted fs-8">Cambios</div>
                                <div class="fs-4 fw-bold text-info" id="solicitud_dif_resumen_cambios">0</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row g-4">
                    <div class="col-lg-4">
                        <h3 class="fs-6 fw-bold mb-3">No surtidas</h3>
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed gy-3">
                                <thead><tr class="text-muted fw-bold fs-7 text-uppercase"><th>SKU</th><th>Producto</th><th class="text-end">Cant.</th><th class="text-end">Costo</th></tr></thead>
                                <tbody id="solicitud_dif_faltantes"><tr><td colspan="4" class="text-center text-muted py-5">Sin faltantes</td></tr></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <h3 class="fs-6 fw-bold mb-3">Adicionales</h3>
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed gy-3">
                                <thead><tr class="text-muted fw-bold fs-7 text-uppercase"><th>SKU</th><th>Producto</th><th class="text-end">Cant.</th><th class="text-end">Costo</th></tr></thead>
                                <tbody id="solicitud_dif_adicionales"><tr><td colspan="4" class="text-center text-muted py-5">Sin adicionales</td></tr></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <h3 class="fs-6 fw-bold mb-3">Cambios</h3>
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed gy-3">
                                <thead><tr class="text-muted fw-bold fs-7 text-uppercase"><th>SKU</th><th>Producto</th><th class="text-end">Î” Cant.</th><th class="text-end">Î” Costo</th></tr></thead>
                                <tbody id="solicitud_dif_cambios"><tr><td colspan="4" class="text-center text-muted py-5">Sin cambios</td></tr></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button></div>
        </div>
    </div>
</div>
<script src="assets/plugins/global/plugins.bundle.js"></script>
<script src="assets/js/scripts.bundle.js"></script>
<script src="/assets/js/custom/apps/erp/compras/solicitudes/formulario.js?v=20260614-1"></script>
</body>
</html>
