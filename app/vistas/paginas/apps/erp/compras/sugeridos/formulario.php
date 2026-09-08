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
    <style>
        /* IA: Codex GPT-5 | Fecha: 2026-08-27
           Proposito: ampliar inputs numericos de Sugerido de compra para captura movil.
           Impacto: UX Compras/Sugerido; no cambia calculos ni persistencia. */
        .sugerido-cantidad-input {
            min-width: 7.5rem;
            width: 7.5rem;
            max-width: 100%;
        }
        .sugerido-cantidad-final-input {
            min-width: 8.5rem;
            width: 8.5rem;
            max-width: 100%;
            font-weight: 600;
        }
        .sugerido-cantidad-readonly {
            display: inline-block;
            min-width: 7.5rem;
            text-align: right;
        }
        .sugerido-filtro-partidas {
            min-width: 16rem;
        }
        .sugerido-scan-preview { position: relative; border-radius: 8px; overflow: hidden; background: #111827; min-height: 320px; }
        .sugerido-scan-preview video { width: 100%; min-height: 320px; object-fit: cover; display: block; }
        .sugerido-scan-guide { position: absolute; left: 10%; right: 10%; top: 38%; height: 86px; border: 2px solid rgba(255,255,255,.9); border-radius: 8px; box-shadow: 0 0 0 999px rgba(0,0,0,.22); pointer-events: none; }
        .sugerido-scan-line { position: absolute; left: 12%; right: 12%; top: calc(38% + 43px); height: 2px; background: #50cd89; box-shadow: 0 0 12px rgba(80,205,137,.75); pointer-events: none; }
        @media (max-width: 767.98px) {
            .sugerido-cantidad-input,
            .sugerido-cantidad-final-input {
                min-width: 8.75rem;
                width: 8.75rem;
            }
            #sugerido_items td {
                white-space: nowrap;
            }
        }
    </style>
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
                                        <input class="form-control form-control-solid ps-12" id="sugerido_buscar" placeholder="SKU, producto o codigo">
                                    </div>
                                    <button type="button" class="btn btn-light-primary" id="sugerido_scan_camera_btn" title="Escanear codigo"><i class="bi bi-camera"></i></button>
                                    <button type="button" class="btn btn-primary" id="sugerido_buscar_productos">Buscar</button>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive mb-6 d-none" id="sugerido_resultados_wrap">
                            <div class="text-muted fw-bold fs-7 text-uppercase mb-2">Resultados del proveedor</div>
                            <table class="table align-middle table-row-dashed gy-3 mb-0">
                                <thead>
                                    <tr class="text-muted fw-bold fs-8 text-uppercase">
                                        <th>SKU proveedor</th>
                                        <th>Producto proveedor</th>
                                        <th class="text-end">Costo</th>
                                        <th class="text-end">Accion</th>
                                    </tr>
                                </thead>
                                <tbody id="sugerido_resultados"></tbody>
                            </table>
                        </div>

                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
                            <div class="text-muted fs-7" id="sugerido_resumen">Selecciona proveedor para buscar productos.</div>
                            <div class="d-flex flex-wrap align-items-center gap-3">
                                <label class="form-check form-check-custom form-check-solid mb-0">
                                    <input class="form-check-input" type="checkbox" id="sugerido_actualizar_reglas_resurtido">
                                    <span class="form-check-label text-muted fs-7">Guardar min/max/reorden para futuras revisiones</span>
                                </label>
                                <button type="button" class="btn btn-sm btn-light-primary" id="sugerido_actualizar_reglas"><i class="bi bi-save"></i> Actualizar reglas</button>
                                <button type="button" class="btn btn-sm btn-light" id="sugerido_recalcular"><i class="bi bi-calculator"></i> Recalcular</button>
                                <button type="button" class="btn btn-sm btn-light-warning" id="sugerido_reiniciar_existencias"><i class="bi bi-arrow-counterclockwise"></i> Existencias a 0</button>
                                <button type="button" class="btn btn-sm btn-light-danger" id="sugerido_limpiar_ceros">Ocultar ceros</button>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap align-items-end justify-content-between gap-3 mb-4">
                            <div class="flex-grow-1 sugerido-filtro-partidas">
                                <label class="form-label text-muted fs-8" for="sugerido_filtro_partidas">Buscar dentro de productos agregados</label>
                                <div class="d-flex gap-2">
                                    <div class="position-relative flex-grow-1">
                                        <i class="bi bi-search position-absolute ms-5 mt-3 fs-3"></i>
                                        <input class="form-control form-control-solid ps-12" id="sugerido_filtro_partidas" placeholder="SKU, nombre o codigo escaneado">
                                    </div>
                                    <button type="button" class="btn btn-light-primary" id="sugerido_scan_partidas_btn" title="Escanear dentro de agregados"><i class="bi bi-camera"></i></button>
                                    <button type="button" class="btn btn-light" id="sugerido_limpiar_filtro_partidas"><i class="bi bi-x-lg"></i></button>
                                </div>
                            </div>
                            <div class="text-muted fs-8" id="sugerido_filtro_partidas_resumen">0 partidas agregadas</div>
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
                                        <th class="text-end">Accion</th>
                                    </tr>
                                </thead>
                                <tbody id="sugerido_items"></tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="7" class="text-end fw-bold">Total a solicitar estimado</td>
                                        <td class="text-end fw-bold" id="sugerido_total_piezas">0</td>
                                        <td class="text-end fw-bold fs-5" id="sugerido_total">$0.00</td>
                                        <td colspan="2"></td>
                                    </tr>
                                    <tr>
                                        <td colspan="7" class="text-end text-muted fw-bold">Cantidad revisada total</td>
                                        <td class="text-end text-muted fw-bold" id="sugerido_total_existencia_revisada">0</td>
                                        <td class="text-muted fs-8" colspan="3">Suma de existencia revisada</td>
                                    </tr>
                                    <tr>
                                        <td colspan="8" class="text-end text-muted fw-bold">Inventario fisico estimado</td>
                                        <td class="text-end text-muted fw-bold" id="sugerido_total_inventario_estimado">$0.00</td>
                                        <td class="text-muted fs-8" colspan="2">Existencia revisada x costo</td>
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
<div class="modal fade" id="sugerido_scan_modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h3 class="modal-title mb-1" id="sugerido_scan_titulo">Escanear producto</h3>
                    <div class="text-muted fs-7" id="sugerido_scan_descripcion">Lee el codigo para buscarlo dentro del proveedor seleccionado</div>
                </div>
                <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="modal-body">
                <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
                    <label class="form-label text-muted fs-8 text-uppercase mb-0 d-none" id="sugerido_scan_camera_device_label">Camara</label>
                    <select class="form-select form-select-solid w-auto d-none" id="sugerido_scan_camera_device"></select>
                    <button class="btn btn-light-primary" id="sugerido_scan_start" type="button"><i class="bi bi-camera-video"></i> Iniciar</button>
                    <button class="btn btn-light-warning d-none" id="sugerido_scan_torch" type="button"><i class="bi bi-lightbulb"></i> Luz</button>
                    <button class="btn btn-light-info d-none" id="sugerido_scan_focus" type="button"><i class="bi bi-bullseye"></i> Enfoque</button>
                    <button class="btn btn-light-danger d-none" id="sugerido_scan_stop" type="button"><i class="bi bi-stop-circle"></i> Detener</button>
                </div>
                <div class="sugerido-scan-preview d-none" id="sugerido_scan_wrap">
                    <video id="sugerido_scan_video" playsinline muted autoplay></video>
                    <div class="sugerido-scan-guide"></div>
                    <div class="sugerido-scan-line"></div>
                </div>
                <div class="text-muted fs-7 mt-3" id="sugerido_scan_estado">Selecciona proveedor, abre la camara y apunta al codigo. Se agregara si hay coincidencia unica.</div>
            </div>
        </div>
    </div>
</div>
<script src="assets/plugins/global/plugins.bundle.js"></script>
<script src="assets/js/scripts.bundle.js"></script>
<script src="/assets/js/custom/apps/erp/compras/sugeridos/formulario.js?v=20260908-3"></script>
</body>
</html>



