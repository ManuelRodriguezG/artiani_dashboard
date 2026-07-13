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
      Documentacion IA: Codex GPT-5, 2026-07-12.
      Proposito: abrir tablero operativo de Listas de precios con guia de responsabilidades.
      Impacto: permite auditar listas, detalles, clientes y conflictos; el guardado queda bajo UAT controlado.
      Contrato: backend resuelve precios; Catalogo conserva solo precio provisional/fallback.
    -->
    <style>
        .lp-card { border: 1px solid #e6e8ee; border-radius: 8px; background: #fff; }
        .lp-kpi { min-height: 92px; }
        .lp-table-wrap { max-height: 520px; overflow: auto; }
        .lp-detail-wrap { max-height: 360px; overflow: auto; }
        .lp-conflict { border-left: 4px solid #f1416c; }
        .lp-uat { border: 1px dashed #f6c000; background: #fff8dd; border-radius: 8px; }
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
                                <span class="text-muted">Precios por producto, cliente, canal, sucursal y vigencia</span>
                                <div class="d-flex flex-wrap gap-2 mt-2">
                                    <span class="badge badge-light-primary">Read-only inicial</span>
                                    <span class="badge badge-light-success">Backend resuelve precios</span>
                                    <span class="badge badge-light-warning">DDL CRM pendiente de autorizacion</span>
                                </div>
                            </div>
                            <div class="d-flex flex-wrap gap-2">
                                <a class="btn btn-light" href="/ventas/mostrar"><i class="bi bi-receipt"></i> Ventas</a>
                                <a class="btn btn-light-primary" href="/ventas/pos"><i class="bi bi-shop-window"></i> POS</a>
                                <a class="btn btn-light-primary" href="/ventas/checador_precios"><i class="bi bi-upc-scan"></i> Checador</a>
                                <button class="btn btn-primary" id="lp_recargar" type="button"><i class="bi bi-arrow-clockwise"></i> Actualizar</button>
                            </div>
                        </div>
                    </div>
                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <div id="lp_alerta" class="mb-4"></div>
                            <div class="lp-card p-4 mb-4">
                                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                                    <div>
                                        <div class="fw-bold fs-5 mb-1">Gobierno de precios</div>
                                        <div class="text-muted fs-7">Esta pantalla gobierna el precio formal por canal, cliente, sucursal y vigencia. POS y Checador consumen el precio resuelto por backend.</div>
                                    </div>
                                    <span class="badge badge-light-primary">Fuente comercial formal</span>
                                </div>
                                <div class="row g-3 mt-1">
                                    <div class="col-lg-4">
                                        <div class="border rounded p-3 h-100">
                                            <div class="fw-semibold mb-1">Catalogo</div>
                                            <div class="text-muted fs-8">Identidad del SKU, unidad, marca, categoria y precio provisional solo como fallback.</div>
                                        </div>
                                    </div>
                                    <div class="col-lg-4">
                                        <div class="border rounded p-3 h-100">
                                            <div class="fw-semibold mb-1">Listas de precios</div>
                                            <div class="text-muted fs-8">Precio base por SKU/producto, canal, cliente CRM, almacen, prioridad y vigencia.</div>
                                        </div>
                                    </div>
                                    <div class="col-lg-4">
                                        <div class="border rounded p-3 h-100">
                                            <div class="fw-semibold mb-1">Costos y rentabilidad</div>
                                            <div class="text-muted fs-8">Costo, margen y utilidad validan decisiones, pero no sustituyen la lista comercial.</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row g-3 mb-4">
                                <div class="col-md-3"><div class="lp-card lp-kpi p-4"><div class="text-muted fs-8 text-uppercase">Listas activas</div><div class="fw-bold fs-2" id="lp_kpi_activas">0</div></div></div>
                                <div class="col-md-3"><div class="lp-card lp-kpi p-4"><div class="text-muted fs-8 text-uppercase">Listas total</div><div class="fw-bold fs-2" id="lp_kpi_total">0</div></div></div>
                                <div class="col-md-3"><div class="lp-card lp-kpi p-4"><div class="text-muted fs-8 text-uppercase">Detalles activos</div><div class="fw-bold fs-2" id="lp_kpi_detalles">0</div></div></div>
                                <div class="col-md-3"><div class="lp-card lp-kpi p-4"><div class="text-muted fs-8 text-uppercase">Asignaciones</div><div class="fw-bold fs-2" id="lp_kpi_asignaciones">0</div></div></div>
                            </div>
                            <div class="lp-card p-4 mb-4">
                                <div class="row g-3 align-items-end">
                                    <div class="col-lg-3">
                                        <label class="form-label text-muted fs-8 text-uppercase">Buscar</label>
                                        <input class="form-control form-control-solid" id="lp_filtro_q" placeholder="Codigo o nombre">
                                    </div>
                                    <div class="col-lg-2">
                                        <label class="form-label text-muted fs-8 text-uppercase">Estatus</label>
                                        <select class="form-select form-select-solid" id="lp_filtro_estatus">
                                            <option value="">Todos</option>
                                            <option value="activa">Activa</option>
                                            <option value="borrador">Borrador</option>
                                            <option value="pausada">Pausada</option>
                                            <option value="cancelada">Cancelada</option>
                                        </select>
                                    </div>
                                    <div class="col-lg-2">
                                        <label class="form-label text-muted fs-8 text-uppercase">Canal</label>
                                        <select class="form-select form-select-solid" id="lp_filtro_canal">
                                            <option value="">Todos</option>
                                            <option value="pos">POS</option>
                                            <option value="pedido_tienda">Pedido tienda</option>
                                            <option value="ecommerce">Ecommerce</option>
                                            <option value="general">General</option>
                                        </select>
                                    </div>
                                    <div class="col-lg-2">
                                        <label class="form-label text-muted fs-8 text-uppercase">Almacen</label>
                                        <input class="form-control form-control-solid" id="lp_filtro_almacen" inputmode="numeric" placeholder="ID">
                                    </div>
                                    <div class="col-lg-3 d-flex gap-2">
                                        <button class="btn btn-light-primary flex-fill" id="lp_filtrar" type="button"><i class="bi bi-funnel"></i> Filtrar</button>
                                        <button class="btn btn-light flex-fill" id="lp_conflictos_btn" type="button"><i class="bi bi-exclamation-triangle"></i> Conflictos</button>
                                    </div>
                                </div>
                            </div>
                            <div class="lp-card p-4 mb-4">
                                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
                                    <div>
                                        <div class="fw-bold fs-5">Validaciones dry-run</div>
                                        <div class="text-muted fs-7">Valida capturas futuras sin crear ni modificar listas.</div>
                                    </div>
                                    <span class="badge badge-light-warning">No guarda BD</span>
                                </div>
                                <ul class="nav nav-tabs nav-line-tabs mb-5 fs-7" role="tablist">
                                    <li class="nav-item" role="presentation"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#lp_tab_lista" type="button" role="tab">Lista</button></li>
                                    <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#lp_tab_detalle" type="button" role="tab">Detalle</button></li>
                                    <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#lp_tab_asignacion" type="button" role="tab">Cliente CRM</button></li>
                                </ul>
                                <div class="tab-content">
                                    <div class="tab-pane fade show active" id="lp_tab_lista" role="tabpanel">
                                        <div class="row g-3 align-items-end">
                                            <div class="col-md-1"><label class="form-label text-muted fs-8 text-uppercase">ID</label><input class="form-control form-control-solid" id="lp_dry_lista_id" inputmode="numeric" placeholder="Nuevo"></div>
                                            <div class="col-md-2"><label class="form-label text-muted fs-8 text-uppercase">Codigo</label><input class="form-control form-control-solid" id="lp_dry_lista_codigo" placeholder="LP-POS-01"></div>
                                            <div class="col-md-2"><label class="form-label text-muted fs-8 text-uppercase">Nombre</label><input class="form-control form-control-solid" id="lp_dry_lista_nombre" placeholder="Lista mostrador"></div>
                                            <div class="col-md-2"><label class="form-label text-muted fs-8 text-uppercase">Canal</label><select class="form-select form-select-solid" id="lp_dry_lista_canal"><option value="pos">POS</option><option value="pedido_tienda">Pedido tienda</option><option value="ecommerce">Ecommerce</option><option value="general">General</option></select></div>
                                            <div class="col-md-1"><label class="form-label text-muted fs-8 text-uppercase">Alm.</label><input class="form-control form-control-solid" id="lp_dry_lista_almacen" inputmode="numeric"></div>
                                            <div class="col-md-1"><label class="form-label text-muted fs-8 text-uppercase">Prior.</label><input class="form-control form-control-solid" id="lp_dry_lista_prioridad" inputmode="numeric" value="100"></div>
                                            <div class="col-md-2"><label class="form-label text-muted fs-8 text-uppercase">Estatus</label><select class="form-select form-select-solid" id="lp_dry_lista_estatus"><option value="borrador">Borrador</option><option value="activa">Activa</option><option value="pausada">Pausada</option></select></div>
                                            <div class="col-md-1"><button class="btn btn-light-primary w-100" id="lp_dry_lista_validar" type="button"><i class="bi bi-check2-circle"></i></button></div>
                                        </div>
                                    </div>
                                    <div class="tab-pane fade" id="lp_tab_detalle" role="tabpanel">
                                        <div class="row g-3 align-items-end">
                                            <div class="col-md-1"><label class="form-label text-muted fs-8 text-uppercase">ID</label><input class="form-control form-control-solid" id="lp_dry_det_id" inputmode="numeric" placeholder="Nuevo"></div>
                                            <div class="col-md-2"><label class="form-label text-muted fs-8 text-uppercase">Lista ID</label><input class="form-control form-control-solid" id="lp_dry_det_lista" inputmode="numeric" value="1"></div>
                                            <div class="col-md-2"><label class="form-label text-muted fs-8 text-uppercase">SKU ID</label><input class="form-control form-control-solid" id="lp_dry_det_sku" inputmode="numeric" value="1760"></div>
                                            <div class="col-md-1"><label class="form-label text-muted fs-8 text-uppercase">Producto</label><input class="form-control form-control-solid" id="lp_dry_det_producto" inputmode="numeric"></div>
                                            <div class="col-md-2"><label class="form-label text-muted fs-8 text-uppercase">Precio</label><input class="form-control form-control-solid" id="lp_dry_det_precio" inputmode="decimal" value="295"></div>
                                            <div class="col-md-2"><label class="form-label text-muted fs-8 text-uppercase">Moneda</label><input class="form-control form-control-solid" id="lp_dry_det_moneda" value="MXN"></div>
                                            <div class="col-md-1"><label class="form-label text-muted fs-8 text-uppercase">Estatus</label><select class="form-select form-select-solid" id="lp_dry_det_estatus"><option value="activo">Activo</option><option value="pausado">Pausado</option></select></div>
                                            <div class="col-md-1"><button class="btn btn-light-primary w-100" id="lp_dry_det_validar" type="button"><i class="bi bi-check2-circle"></i></button></div>
                                        </div>
                                    </div>
                                    <div class="tab-pane fade" id="lp_tab_asignacion" role="tabpanel">
                                        <div class="row g-3 align-items-end">
                                            <div class="col-md-2"><label class="form-label text-muted fs-8 text-uppercase">ID</label><input class="form-control form-control-solid" id="lp_dry_asig_id" inputmode="numeric" placeholder="Nuevo"></div>
                                            <div class="col-md-2"><label class="form-label text-muted fs-8 text-uppercase">Lista ID</label><input class="form-control form-control-solid" id="lp_dry_asig_lista" inputmode="numeric" value="1"></div>
                                            <div class="col-md-3"><label class="form-label text-muted fs-8 text-uppercase">Cliente CRM ID</label><input class="form-control form-control-solid" id="lp_dry_asig_cliente" inputmode="numeric" value="1"></div>
                                            <div class="col-md-2"><label class="form-label text-muted fs-8 text-uppercase">Prioridad</label><input class="form-control form-control-solid" id="lp_dry_asig_prioridad" inputmode="numeric" value="1"></div>
                                            <div class="col-md-2"><label class="form-label text-muted fs-8 text-uppercase">Estatus</label><select class="form-select form-select-solid" id="lp_dry_asig_estatus"><option value="activo">Activo</option><option value="pausado">Pausado</option></select></div>
                                            <div class="col-md-1"><button class="btn btn-light-primary w-100" id="lp_dry_asig_validar" type="button"><i class="bi bi-check2-circle"></i></button></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="lp-uat p-3 mt-4">
                                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                                        <div>
                                            <div class="fw-bold">Guardado UAT controlado</div>
                                            <div class="text-muted fs-8">Requiere permisos finos, token UAT y auditoria comercial aplicada. El respaldo externo queda reservado para DDL.</div>
                                        </div>
                                        <span class="badge badge-light-danger">Puede escribir BD si todo esta autorizado</span>
                                    </div>
                                    <div class="row g-3 align-items-end">
                                        <div class="col-lg-3"><label class="form-label text-muted fs-8 text-uppercase">Referencia UAT</label><input class="form-control form-control-solid" id="lp_uat_referencia" placeholder="Folio o nota opcional"></div>
                                        <div class="col-lg-3"><label class="form-label text-muted fs-8 text-uppercase">Token</label><input class="form-control form-control-solid" id="lp_uat_token" placeholder="VENTAS_LISTAS_PRECIOS_GUARDAR_UAT"></div>
                                        <div class="col-lg-3"><label class="form-label text-muted fs-8 text-uppercase">Motivo</label><input class="form-control form-control-solid" id="lp_uat_motivo" placeholder="UAT lista de precios"></div>
                                        <div class="col-lg-3 d-flex flex-wrap gap-2">
                                            <button class="btn btn-warning flex-fill" id="lp_guardar_lista" type="button"><i class="bi bi-save"></i> Lista</button>
                                            <button class="btn btn-warning flex-fill" id="lp_guardar_detalle" type="button"><i class="bi bi-save"></i> Detalle</button>
                                            <button class="btn btn-warning flex-fill" id="lp_guardar_asig" type="button"><i class="bi bi-save"></i> Cliente</button>
                                        </div>
                                    </div>
                                </div>
                                <div id="lp_dry_resultado" class="mt-4"></div>
                            </div>
                            <div class="row g-4">
                                <div class="col-xl-7">
                                    <div class="lp-card p-4">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <div class="fw-bold fs-5">Listas</div>
                                            <span class="text-muted fs-8">Consulta sin escritura</span>
                                        </div>
                                        <div class="table-responsive lp-table-wrap">
                                            <table class="table align-middle table-row-dashed gy-3 mb-0">
                                                <thead><tr class="text-muted fw-bold fs-8 text-uppercase"><th>Lista</th><th>Canal / almacen</th><th>Detalles</th><th>Precio</th><th>Estatus</th><th class="text-end">Accion</th></tr></thead>
                                                <tbody id="lp_listas"></tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xl-5">
                                    <div class="lp-card p-4 mb-4">
                                        <div class="fw-bold fs-5 mb-2">Detalle</div>
                                        <div id="lp_detalle" class="text-muted fs-7">Selecciona una lista para revisar partidas y asignaciones.</div>
                                    </div>
                                    <div class="lp-card p-4">
                                        <div class="fw-bold fs-5 mb-2">Conflictos</div>
                                        <div id="lp_conflictos" class="text-muted fs-7">Sin conflictos cargados.</div>
                                    </div>
                                    <div class="lp-card p-4 mt-4">
                                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                                            <div>
                                                <div class="fw-bold fs-5">Auditoria</div>
                                                <div class="text-muted fs-8">Eventos comerciales de listas, detalles y clientes.</div>
                                            </div>
                                            <button class="btn btn-sm btn-light-primary" id="lp_auditoria_btn" type="button"><i class="bi bi-clock-history"></i> Ver</button>
                                        </div>
                                        <div class="row g-2 mb-3">
                                            <div class="col-7"><input class="form-control form-control-sm form-control-solid" id="lp_auditoria_lista" inputmode="numeric" placeholder="Lista ID"></div>
                                            <div class="col-5"><input class="form-control form-control-sm form-control-solid" id="lp_auditoria_accion" placeholder="Accion"></div>
                                        </div>
                                        <div id="lp_auditoria" class="text-muted fs-7">Selecciona una lista o consulta eventos.</div>
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
<script src="/assets/js/custom/apps/erp/ventas/listas_precios.js?v=20260713-comercial1"></script>
</body>
</html>
