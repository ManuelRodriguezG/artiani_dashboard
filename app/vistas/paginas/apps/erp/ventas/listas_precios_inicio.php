<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../">
    <title>Listas de precios ERP</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css">
    <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css">
    <!--
      Documentacion IA: Codex GPT-5, 2026-07-20.
      Proposito: portada operativa de Comercial/Listas de precios.
      Impacto: separa consulta/listado de la mesa de creacion/edicion para reducir confusion operativa.
      Contrato: vista read-only salvo enlaces a pantallas dedicadas; precios se guardan en el editor.
    -->
    <style>
        .lp-home-card { border: 1px solid #e6e8ee; border-radius: 8px; background: #fff; }
        .lp-home-list { min-height: 280px; }
        .lp-home-action { border: 1px solid #edf0f5; border-radius: 8px; background: #fff; }
        .lp-home-action:hover { border-color: #3e97ff; background: #f7fbff; }
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
                                <div class="text-muted fs-8 text-uppercase fw-semibold mb-1">ERP / Comercial</div>
                                <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Listas de precios</h1>
                                <span class="text-muted">Consulta listas, revisa su estado y abre una accion concreta.</span>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <a class="btn btn-light-info" href="/comercial/listas_precios_manual"><i class="bi bi-question-circle"></i> Manual</a>
                                <a class="btn btn-primary" href="/comercial/listas_precios_nueva?nuevo=1"><i class="bi bi-plus-lg"></i> Crear lista</a>
                            </div>
                        </div>
                    </div>
                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <div id="lp_inicio_alerta" class="mb-4"></div>
                            <div class="row g-4 mb-4">
                                <div class="col-xl-3 col-md-6">
                                    <a class="lp-home-action d-block p-4 h-100" href="/comercial/listas_precios_nueva?nuevo=1">
                                        <div class="d-flex align-items-center gap-3">
                                            <i class="bi bi-plus-circle fs-2 text-primary"></i>
                                            <div><div class="fw-bold">Crear nueva lista</div><div class="text-muted fs-8">Encabezado, vigencia y alcance.</div></div>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-xl-3 col-md-6">
                                    <button class="lp-home-action text-start w-100 p-4 h-100" id="lp_inicio_recargar" type="button">
                                        <div class="d-flex align-items-center gap-3">
                                            <i class="bi bi-arrow-clockwise fs-2 text-info"></i>
                                            <div><div class="fw-bold">Actualizar listado</div><div class="text-muted fs-8">Recarga KPIs y semaforo.</div></div>
                                        </div>
                                    </button>
                                </div>
                                <div class="col-xl-3 col-md-6">
                                    <a class="lp-home-action d-block p-4 h-100" href="/comercial/listas_precios_manual">
                                        <div class="d-flex align-items-center gap-3">
                                            <i class="bi bi-journal-text fs-2 text-success"></i>
                                            <div><div class="fw-bold">Manual operativo</div><div class="text-muted fs-8">Flujo, UAT y reglas.</div></div>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-xl-3 col-md-6">
                                    <div class="lp-home-card p-4 h-100">
                                        <div class="text-muted fs-8 text-uppercase">Fase 1</div>
                                        <div class="fw-bold" id="lp_inicio_fase1">Validando...</div>
                                        <div class="text-muted fs-8" id="lp_inicio_fase1_detalle">Semaforo POS</div>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-4 mb-4" id="lp_inicio_kpis">
                                <div class="col-md-3"><div class="lp-home-card p-4"><div class="text-muted fs-8 text-uppercase">Listas</div><div class="fw-bold fs-3" id="lp_kpi_listas">0</div></div></div>
                                <div class="col-md-3"><div class="lp-home-card p-4"><div class="text-muted fs-8 text-uppercase">Activas</div><div class="fw-bold fs-3" id="lp_kpi_activas">0</div></div></div>
                                <div class="col-md-3"><div class="lp-home-card p-4"><div class="text-muted fs-8 text-uppercase">Precios activos</div><div class="fw-bold fs-3" id="lp_kpi_detalles">0</div></div></div>
                                <div class="col-md-3"><div class="lp-home-card p-4"><div class="text-muted fs-8 text-uppercase">Segmentos</div><div class="fw-bold fs-3" id="lp_kpi_segmentos">0</div></div></div>
                            </div>

                            <section class="lp-home-card p-4">
                                <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
                                    <div>
                                        <div class="fw-bold fs-5">Listado de listas</div>
                                        <div class="text-muted fs-8">Abre una lista para editar precios, alcance, clientes o segmentos.</div>
                                    </div>
                                    <div class="d-flex flex-wrap gap-2">
                                        <input class="form-control form-control-solid w-250px" id="lp_inicio_q" placeholder="Buscar codigo o nombre">
                                        <select class="form-select form-select-solid w-150px" id="lp_inicio_estatus">
                                            <option value="">Estatus</option>
                                            <option value="borrador">Borrador</option>
                                            <option value="activa">Activa</option>
                                            <option value="pausada">Pausada</option>
                                            <option value="cancelada">Cancelada</option>
                                        </select>
                                        <select class="form-select form-select-solid w-150px" id="lp_inicio_canal">
                                            <option value="">Canal</option>
                                            <option value="general">General</option>
                                            <option value="pos">POS</option>
                                            <option value="pedido_tienda">Pedido tienda</option>
                                            <option value="ecommerce">Ecommerce</option>
                                            <option value="mayoreo">Mayoreo</option>
                                        </select>
                                        <button class="btn btn-light-primary" id="lp_inicio_filtrar" type="button"><i class="bi bi-funnel"></i></button>
                                    </div>
                                </div>
                                <div class="lp-home-list" id="lp_inicio_listas"></div>
                            </section>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="assets/plugins/global/plugins.bundle.js"></script>
<script src="assets/js/scripts.bundle.js"></script>
<script src="/assets/js/custom/apps/erp/ventas/listas_precios_inicio.js?v=20260720-estructura1"></script>
</body>
</html>
