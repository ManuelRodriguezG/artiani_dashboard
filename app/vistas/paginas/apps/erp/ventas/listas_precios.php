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
      Documentacion IA: Codex GPT-5, 2026-07-15.
      Proposito: convertir Listas de precios en una mesa operativa Comercial con productos, margen y alcance.
      Impacto: elimina accesos visuales a Ventas/POS/Checador dentro de esta vista y enfoca el flujo en listas.
      Contrato: backend guarda/resuelve precios; POS solo consume precio resuelto y snapshot.
    -->
    <style>
        .lp-shell { display: grid; grid-template-columns: 340px minmax(0, 1fr); gap: 16px; }
        .lp-card { border: 1px solid #e6e8ee; border-radius: 8px; background: #fff; }
        .lp-listas { max-height: 680px; overflow: auto; }
        .lp-productos { max-height: 560px; overflow: auto; }
        .lp-lista-item { border: 1px solid #edf0f5; border-radius: 8px; cursor: pointer; }
        .lp-lista-item.active { border-color: #3e97ff; background: #f1f7ff; }
        .lp-price-input { min-width: 120px; }
        .lp-row-dirty { background: #fff8dd; }
        .lp-side { display: grid; grid-template-columns: minmax(0, 1fr) 360px; gap: 16px; }
        @media (max-width: 1400px) {
            .lp-shell, .lp-side { grid-template-columns: 1fr; }
        }
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
                                <span class="text-muted">Construccion de precios por SKU con margen, vigencia y alcance comercial</span>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <button class="btn btn-light" id="lp_recargar" type="button"><i class="bi bi-arrow-clockwise"></i> Actualizar</button>
                                <button class="btn btn-primary" id="lp_nueva" type="button"><i class="bi bi-plus-lg"></i> Nueva lista</button>
                            </div>
                        </div>
                    </div>
                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <div id="lp_alerta" class="mb-4"></div>

                            <div class="lp-shell">
                                <aside class="lp-card p-4">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div class="fw-bold fs-5">Listas</div>
                                        <span class="badge badge-light-primary" id="lp_kpi_total">0</span>
                                    </div>
                                    <div class="row g-2 mb-3">
                                        <div class="col-12">
                                            <input class="form-control form-control-solid" id="lp_filtro_q" placeholder="Buscar lista">
                                        </div>
                                        <div class="col-6">
                                            <select class="form-select form-select-solid" id="lp_filtro_estatus">
                                                <option value="">Estatus</option>
                                                <option value="borrador">Borrador</option>
                                                <option value="activa">Activa</option>
                                                <option value="pausada">Pausada</option>
                                                <option value="cancelada">Cancelada</option>
                                            </select>
                                        </div>
                                        <div class="col-6">
                                            <select class="form-select form-select-solid" id="lp_filtro_canal">
                                                <option value="">Canal</option>
                                                <option value="general">General</option>
                                                <option value="pos">POS</option>
                                                <option value="pedido_tienda">Pedido tienda</option>
                                                <option value="ecommerce">Ecommerce</option>
                                                <option value="mayoreo">Mayoreo</option>
                                            </select>
                                        </div>
                                        <div class="col-7">
                                            <input class="form-control form-control-solid" id="lp_filtro_almacen" inputmode="numeric" placeholder="Almacen">
                                        </div>
                                        <div class="col-5">
                                            <button class="btn btn-light-primary w-100" id="lp_filtrar" type="button"><i class="bi bi-funnel"></i></button>
                                        </div>
                                    </div>
                                    <div class="lp-listas" id="lp_listas"></div>
                                </aside>

                                <main class="d-flex flex-column gap-4">
                                    <section class="lp-card p-4">
                                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
                                            <div>
                                                <div class="fw-bold fs-5" id="lp_titulo_lista">Nueva lista</div>
                                                <div class="text-muted fs-8" id="lp_subtitulo_lista">Guarda el encabezado antes de capturar precios.</div>
                                            </div>
                                            <div class="d-flex flex-wrap gap-2">
                                                <button class="btn btn-light-warning" id="lp_pausar" type="button"><i class="bi bi-pause-circle"></i> Pausar</button>
                                                <button class="btn btn-success" id="lp_activar" type="button"><i class="bi bi-check2-circle"></i> Activar</button>
                                                <button class="btn btn-primary" id="lp_guardar_lista" type="button"><i class="bi bi-save"></i> Guardar lista</button>
                                            </div>
                                        </div>
                                        <div class="row g-3">
                                            <input type="hidden" id="lp_lista_id">
                                            <div class="col-lg-2">
                                                <label class="form-label text-muted fs-8 text-uppercase">Codigo</label>
                                                <input class="form-control form-control-solid" id="lp_lista_codigo" placeholder="LP-POS-01">
                                            </div>
                                            <div class="col-lg-4">
                                                <label class="form-label text-muted fs-8 text-uppercase">Nombre</label>
                                                <input class="form-control form-control-solid" id="lp_lista_nombre" placeholder="Lista mostrador">
                                            </div>
                                            <div class="col-lg-2">
                                                <label class="form-label text-muted fs-8 text-uppercase">Vigencia inicio</label>
                                                <input class="form-control form-control-solid" id="lp_lista_inicio" type="date">
                                            </div>
                                            <div class="col-lg-2">
                                                <label class="form-label text-muted fs-8 text-uppercase">Vigencia fin</label>
                                                <input class="form-control form-control-solid" id="lp_lista_fin" type="date">
                                            </div>
                                            <div class="col-lg-2">
                                                <label class="form-label text-muted fs-8 text-uppercase">Estatus</label>
                                                <select class="form-select form-select-solid" id="lp_lista_estatus">
                                                    <option value="borrador">Borrador</option>
                                                    <option value="activa">Activa</option>
                                                    <option value="pausada">Pausada</option>
                                                    <option value="cancelada">Cancelada</option>
                                                </select>
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label text-muted fs-8 text-uppercase">Observaciones</label>
                                                <input class="form-control form-control-solid" id="lp_lista_observaciones" placeholder="Motivo comercial, temporada o condicion interna">
                                            </div>
                                        </div>
                                    </section>

                                    <div class="lp-side">
                                        <section class="lp-card p-4">
                                            <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
                                                <div>
                                                    <div class="fw-bold fs-5">Productos y precios</div>
                                                    <div class="text-muted fs-8">Costo de referencia, precio general, precio de lista y margen estimado.</div>
                                                </div>
                                                <div class="d-flex flex-wrap gap-2">
                                                    <input class="form-control form-control-solid w-250px" id="lp_producto_q" placeholder="SKU o producto">
                                                    <select class="form-select form-select-solid w-175px" id="lp_producto_solo">
                                                        <option value="todos">Todos</option>
                                                        <option value="con_precio">Con precio</option>
                                                        <option value="sin_precio">Sin precio</option>
                                                        <option value="margen_bajo">Margen bajo</option>
                                                    </select>
                                                    <button class="btn btn-light-primary" id="lp_productos_buscar" type="button"><i class="bi bi-search"></i></button>
                                                </div>
                                            </div>
                                            <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
                                                <div class="d-flex flex-wrap gap-2 align-items-end">
                                                    <div>
                                                        <label class="form-label text-muted fs-8 text-uppercase">Margen objetivo</label>
                                                        <div class="input-group input-group-solid w-175px">
                                                            <input class="form-control" id="lp_margen_objetivo" inputmode="decimal" value="35">
                                                            <span class="input-group-text">%</span>
                                                        </div>
                                                    </div>
                                                    <button class="btn btn-light" id="lp_aplicar_margen" type="button"><i class="bi bi-percent"></i> Aplicar a visibles</button>
                                                </div>
                                                <button class="btn btn-primary" id="lp_guardar_cambios" type="button"><i class="bi bi-save2"></i> Guardar cambios <span class="badge badge-light ms-2" id="lp_cambios_count">0</span></button>
                                            </div>
                                            <div class="table-responsive lp-productos">
                                                <table class="table align-middle table-row-dashed gy-3 mb-0">
                                                    <thead>
                                                    <tr class="text-muted fw-bold fs-8 text-uppercase">
                                                        <th>Producto</th>
                                                        <th>Unidad</th>
                                                        <th class="text-end">Costo</th>
                                                        <th class="text-end">General</th>
                                                        <th class="text-end">Lista</th>
                                                        <th class="text-end">Margen / utilidad</th>
                                                        <th class="text-end">Accion</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody id="lp_productos"></tbody>
                                                </table>
                                            </div>
                                        </section>

                                        <aside class="d-flex flex-column gap-4">
                                            <section class="lp-card p-4">
                                                <div class="fw-bold fs-5 mb-3">Alcance</div>
                                                <div class="row g-3">
                                                    <div class="col-12">
                                                        <label class="form-label text-muted fs-8 text-uppercase">Canal</label>
                                                        <select class="form-select form-select-solid" id="lp_lista_canal">
                                                            <option value="general">General</option>
                                                            <option value="pos">POS</option>
                                                            <option value="pedido_tienda">Pedido tienda</option>
                                                            <option value="ecommerce">Ecommerce</option>
                                                            <option value="mayoreo">Mayoreo</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-6">
                                                        <label class="form-label text-muted fs-8 text-uppercase">Almacen</label>
                                                        <input class="form-control form-control-solid" id="lp_lista_almacen" inputmode="numeric" placeholder="Todos">
                                                    </div>
                                                    <div class="col-6">
                                                        <label class="form-label text-muted fs-8 text-uppercase">Prioridad</label>
                                                        <input class="form-control form-control-solid" id="lp_lista_prioridad" inputmode="numeric" value="100">
                                                    </div>
                                                </div>
                                            </section>

                                            <section class="lp-card p-4">
                                                <div class="fw-bold fs-5 mb-3">Cliente CRM</div>
                                                <div class="row g-3">
                                                    <input type="hidden" id="lp_asig_id">
                                                    <div class="col-12">
                                                        <label class="form-label text-muted fs-8 text-uppercase">Buscar cliente</label>
                                                        <div class="input-group input-group-solid">
                                                            <input class="form-control" id="lp_cliente_q" placeholder="Nombre, codigo, telefono o correo">
                                                            <button class="btn btn-light-primary" id="lp_cliente_buscar" type="button"><i class="bi bi-search"></i></button>
                                                        </div>
                                                    </div>
                                                    <div class="col-7">
                                                        <label class="form-label text-muted fs-8 text-uppercase">Cliente seleccionado</label>
                                                        <input class="form-control form-control-solid" id="lp_asig_cliente" inputmode="numeric" placeholder="id_cliente_crm">
                                                    </div>
                                                    <div class="col-5">
                                                        <label class="form-label text-muted fs-8 text-uppercase">Prioridad</label>
                                                        <input class="form-control form-control-solid" id="lp_asig_prioridad" inputmode="numeric" value="1">
                                                    </div>
                                                    <div class="col-12">
                                                        <button class="btn btn-light-primary w-100" id="lp_guardar_asig" type="button"><i class="bi bi-person-check"></i> Asignar cliente</button>
                                                    </div>
                                                </div>
                                                <div id="lp_clientes_resultados" class="mt-3"></div>
                                                <div class="separator my-4"></div>
                                                <div id="lp_asignaciones" class="text-muted fs-7">Selecciona una lista para ver clientes.</div>
                                            </section>

                                            <section class="lp-card p-4">
                                                <div class="d-flex justify-content-between align-items-center mb-3">
                                                    <div class="fw-bold fs-5">Revision</div>
                                                    <button class="btn btn-sm btn-light-primary" id="lp_revision_btn" type="button"><i class="bi bi-shield-check"></i></button>
                                                </div>
                                                <div id="lp_revision" class="text-muted fs-7">Guarda o selecciona una lista para revisar activacion.</div>
                                            </section>

                                            <section class="lp-card p-4">
                                                <div class="d-flex justify-content-between align-items-center mb-3">
                                                    <div class="fw-bold fs-5">Auditoria</div>
                                                    <button class="btn btn-sm btn-light-primary" id="lp_auditoria_btn" type="button"><i class="bi bi-clock-history"></i></button>
                                                </div>
                                                <div id="lp_auditoria" class="text-muted fs-7">Sin eventos cargados.</div>
                                            </section>
                                        </aside>
                                    </div>
                                </main>
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
<script src="/assets/js/custom/apps/erp/ventas/listas_precios.js?v=20260716-clientes2"></script>
</body>
</html>
