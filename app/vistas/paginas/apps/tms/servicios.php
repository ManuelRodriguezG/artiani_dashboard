<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../">
    <title>TMS Delivery</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css">
    <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css">
    <!--
      Documentacion IA: Codex GPT-5, 2026-07-24.
      Proposito: consola inicial TMS Delivery para servicios logisticos read-only/dry-run.
      Impacto: separa entrega/recoleccion/traslado de Ventas, productos y garantias.
      Contrato: no guarda servicios, no cancela ventas y no mueve inventario.
    -->
    <style>
        .tms-panel { border: 1px solid #e6e8ee; border-radius: 8px; background: #fff; }
        .tms-kpi { border: 1px solid #e6e8ee; border-radius: 8px; background: #fff; min-height: 96px; }
        .tms-code { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono", monospace; }
        .tms-scroll { max-height: 520px; overflow: auto; }
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
                                <h1 class="page-heading text-dark fw-bold fs-3 mb-1">TMS Delivery</h1>
                                <span class="text-muted">Servicios logisticos independientes de ventas, productos y garantias</span>
                            </div>
                            <button type="button" class="btn btn-light-primary" id="tms_refrescar">
                                <i class="bi bi-arrow-clockwise"></i> Actualizar
                            </button>
                        </div>
                    </div>

                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <div id="tms_alerta" class="mb-4"></div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-3">
                                    <div class="tms-kpi p-4">
                                        <div class="text-muted fs-8 text-uppercase">Servicios</div>
                                        <div class="fw-bold fs-2" id="tms_kpi_total">0</div>
                                        <div class="text-muted fs-8">Listado visible</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="tms-kpi p-4">
                                        <div class="text-muted fs-8 text-uppercase">En ruta</div>
                                        <div class="fw-bold fs-2" id="tms_kpi_ruta">0</div>
                                        <div class="text-muted fs-8">Operacion activa</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="tms-kpi p-4">
                                        <div class="text-muted fs-8 text-uppercase">No entregadas</div>
                                        <div class="fw-bold fs-2" id="tms_kpi_no_entregadas">0</div>
                                        <div class="text-muted fs-8">Requieren decision</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="tms-kpi p-4">
                                        <div class="text-muted fs-8 text-uppercase">Pendiente cliente</div>
                                        <div class="fw-bold fs-2" id="tms_kpi_cliente">0</div>
                                        <div class="text-muted fs-8">Recogera o confirmara</div>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-4">
                                <div class="col-xl-8">
                                    <div class="tms-panel">
                                        <div class="p-4 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-3">
                                            <div>
                                                <div class="fw-bold">Bandeja de servicios</div>
                                                <div class="text-muted fs-7">Consulta read-only; el esquema puede estar pendiente</div>
                                            </div>
                                            <div class="d-flex flex-wrap gap-2">
                                                <select class="form-select form-select-sm w-auto" id="tms_filtro_estatus">
                                                    <option value="">Todos los estados</option>
                                                </select>
                                                <select class="form-select form-select-sm w-auto" id="tms_filtro_tipo">
                                                    <option value="">Todos los tipos</option>
                                                </select>
                                                <select class="form-select form-select-sm w-auto" id="tms_filtro_cobro">
                                                    <option value="">Todos los cobros</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="table-responsive tms-scroll">
                                            <table class="table align-middle table-row-dashed gy-3 mb-0">
                                                <thead>
                                                    <tr class="text-muted fw-bold fs-8 text-uppercase">
                                                        <th>Servicio</th>
                                                        <th>Cliente</th>
                                                        <th>Ventana</th>
                                                        <th>Cobro</th>
                                                        <th>Resultado</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="tms_servicios_body">
                                                    <tr><td colspan="5" class="text-center text-muted py-8">Cargando servicios...</td></tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-xl-4">
                                    <div class="tms-panel mb-4">
                                        <div class="p-4 border-bottom">
                                            <div class="fw-bold">Solicitud manual</div>
                                            <div class="text-muted fs-7">Validacion previa y creacion protegida por esquema</div>
                                        </div>
                                        <form class="p-4" id="tms_form_dryrun">
                                            <div class="mb-3">
                                                <label class="form-label">Tipo</label>
                                                <select class="form-select form-select-sm" name="tipo_servicio" id="tms_tipo_servicio"></select>
                                            </div>
                                            <div class="row g-3 mb-3">
                                                <div class="col-6">
                                                    <label class="form-label">Prioridad</label>
                                                    <select class="form-select form-select-sm" name="prioridad" id="tms_prioridad"></select>
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label">Cobro</label>
                                                    <select class="form-select form-select-sm" name="estatus_cobro" id="tms_estatus_cobro"></select>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Cliente</label>
                                                <input class="form-control form-control-sm" name="cliente_nombre_snapshot" placeholder="Nombre o referencia">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Contacto</label>
                                                <input class="form-control form-control-sm" name="cliente_contacto_snapshot" placeholder="Telefono / WhatsApp">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Direccion</label>
                                                <textarea class="form-control form-control-sm" name="direccion_snapshot" rows="3" placeholder="Direccion o punto de entrega"></textarea>
                                            </div>
                                            <div class="row g-3 mb-3">
                                                <div class="col-6">
                                                    <label class="form-label">Fecha</label>
                                                    <input type="date" class="form-control form-control-sm" name="fecha_programada">
                                                </div>
                                                <div class="col-3">
                                                    <label class="form-label">Inicio</label>
                                                    <input type="time" class="form-control form-control-sm" name="ventana_inicio">
                                                </div>
                                                <div class="col-3">
                                                    <label class="form-label">Fin</label>
                                                    <input type="time" class="form-control form-control-sm" name="ventana_fin">
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Precio envio</label>
                                                <input type="number" step="0.01" min="0" class="form-control form-control-sm" name="precio_cobrado" value="0">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Motivo bonificacion</label>
                                                <input class="form-control form-control-sm" name="motivo_bonificacion" placeholder="Solo si aplica">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Detalle paquete</label>
                                                <input class="form-control form-control-sm" name="descripcion_detalle" placeholder="Ej. Pecera 40 L empacada">
                                            </div>
                                            <div class="d-grid gap-2">
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="bi bi-check2-circle"></i> Validar
                                                </button>
                                                <button type="button" class="btn btn-light-primary" id="tms_guardar_servicio">
                                                    <i class="bi bi-send-check"></i> Crear servicio
                                                </button>
                                            </div>
                                        </form>
                                    </div>

                                    <div class="tms-panel">
                                        <div class="p-4 border-bottom">
                                            <div class="fw-bold">Resultado</div>
                                            <div class="text-muted fs-7">Validacion o respuesta del servicio</div>
                                        </div>
                                        <div class="p-4" id="tms_dryrun_resultado">
                                            <div class="text-muted">Sin validacion ejecutada.</div>
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
<script src="assets/plugins/global/plugins.bundle.js"></script>
<script src="assets/js/scripts.bundle.js"></script>
<script src="/assets/js/custom/apps/tms/servicios.js?v=20260724-1"></script>
</body>
</html>
