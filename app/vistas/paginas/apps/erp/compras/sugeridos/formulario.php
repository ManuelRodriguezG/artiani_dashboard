<?php
$idSugerido = isset($datos["id_sugerido_compra"]) ? intval($datos["id_sugerido_compra"]) : 0;
$puedeCrear = !empty($datos["puede_crear"]);
$puedeEditar = !empty($datos["puede_editar"]);
$modo = isset($datos["modo"]) ? $datos["modo"] : "editar";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../../">
    <title>Sugerido de compra</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet">
    <link href="assets/css/style.bundle.css" rel="stylesheet">
</head>
<body id="kt_app_body" data-kt-app-layout="dark-sidebar" data-kt-app-header-fixed="true" data-kt-app-sidebar-enabled="true" data-kt-app-sidebar-fixed="true" class="app-default">
<input type="hidden" id="sugerido_id" value="<?= $idSugerido ?>">
<input type="hidden" id="sugerido_puede_crear" value="<?= $puedeCrear ? 1 : 0 ?>">
<input type="hidden" id="sugerido_puede_editar" value="<?= $puedeEditar ? 1 : 0 ?>">
<input type="hidden" id="sugerido_modo" value="<?= htmlspecialchars($modo, ENT_QUOTES, "UTF-8") ?>">
<div class="d-flex flex-column flex-root app-root">
    <div class="app-page flex-column flex-column-fluid">
        <?= include_once '../app/vistas/includes/header/header.php'; ?>
        <div class="app-wrapper flex-column flex-row-fluid">
            <?= include_once '../app/vistas/includes/header/sidebar.php'; ?>
            <main class="app-main flex-column flex-row-fluid">
                <div class="app-toolbar py-3 py-lg-6">
                    <div class="app-container container-fluid d-flex flex-stack">
                        <div>
                            <h1 class="page-heading text-dark fw-bold fs-3 mb-1" id="sugerido_titulo">Sugerido de compra</h1>
                            <span class="text-muted" id="sugerido_estado_texto">Revision por proveedor sin afectar inventario</span>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="/compra/mostrar_sugeridos_compra" class="btn btn-light"><i class="bi bi-arrow-left"></i></a>
                            <button type="button" class="btn btn-light-primary" id="sugerido_guardar_borrador">Guardar borrador</button>
                            <button type="button" class="btn btn-light-success" id="sugerido_marcar_lista">Marcar lista</button>
                            <button type="button" class="btn btn-primary" id="sugerido_generar_solicitud">Generar solicitud</button>
                        </div>
                    </div>
                </div>
                <div class="app-content flex-column-fluid">
                    <div class="app-container container-fluid">
                        <div id="sugerido_alerta_schema" class="alert alert-warning d-none mb-6">
                            El esquema de Sugerido de compra aun no esta preparado. Puedes revisar productos y calculos, pero para guardar se requiere respaldo externo y autorizacion de BD.
                        </div>
                        <div class="row g-5 mb-6">
                            <div class="col-lg-4">
                                <label class="form-label required" for="sugerido_proveedor">Proveedor</label>
                                <select class="form-select form-select-solid" id="sugerido_proveedor" required><option value="">Seleccionar</option></select>
                            </div>
                            <div class="col-lg-5">
                                <label class="form-label" for="sugerido_observaciones">Observaciones</label>
                                <input class="form-control form-control-solid" id="sugerido_observaciones" maxlength="1000" placeholder="Notas internas de la revision">
                            </div>
                            <div class="col-lg-3">
                                <label class="form-label" for="sugerido_buscar">Filtrar productos</label>
                                <div class="d-flex gap-2">
                                    <div class="position-relative flex-grow-1">
                                        <i class="bi bi-search position-absolute ms-5 mt-3 fs-3"></i>
                                        <input class="form-control form-control-solid ps-12" id="sugerido_buscar" placeholder="SKU o producto">
                                    </div>
                                    <button type="button" class="btn btn-primary" id="sugerido_buscar_productos">Buscar</button>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
                            <div class="text-muted fs-7" id="sugerido_resumen">Selecciona proveedor para consultar productos.</div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-light" id="sugerido_recalcular"><i class="bi bi-calculator"></i> Recalcular</button>
                                <button type="button" class="btn btn-sm btn-light-danger" id="sugerido_limpiar_ceros">Ocultar ceros</button>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed gy-4">
                                <thead>
                                    <tr class="text-muted fw-bold fs-7 text-uppercase">
                                        <th>SKU proveedor</th>
                                        <th>Producto proveedor</th>
                                        <th class="text-end">Min</th>
                                        <th class="text-end">Max</th>
                                        <th class="text-end">Reorden</th>
                                        <th class="text-end">Existencia revisada</th>
                                        <th class="text-end">Sugerido</th>
                                        <th class="text-end">A solicitar</th>
                                        <th class="text-end">Costo</th>
                                        <th>Obs.</th>
                                    </tr>
                                </thead>
                                <tbody id="sugerido_items"></tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="7" class="text-end fw-bold">Total estimado</td>
                                        <td class="text-end fw-bold" id="sugerido_total_piezas">0</td>
                                        <td class="text-end fw-bold fs-5" id="sugerido_total">$0.00</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</div>
<script src="assets/plugins/global/plugins.bundle.js"></script>
<script src="assets/js/scripts.bundle.js"></script>
<script src="/assets/js/custom/apps/erp/compras/sugeridos/formulario.js?v=20260821-3"></script>
</body>
</html>


