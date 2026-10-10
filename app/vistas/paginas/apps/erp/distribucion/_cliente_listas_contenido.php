<div class="d-flex flex-wrap gap-2 mb-5">
    <a id="dist_cliente_listas_volver" href="/distribucionadmin/panel_clientes" class="btn btn-light"><i class="bi bi-arrow-left"></i> Cliente</a>
    <a id="dist_cliente_listas_categorias" href="#" class="btn btn-light-primary"><i class="bi bi-ui-checks-grid"></i> Preferencias de categorias</a>
</div>
<div class="row g-5">
    <div class="col-12">
        <div class="card">
            <div class="card-header border-0 pt-6">
                <h3 class="card-title fw-bold">Listas asignadas</h3>
            </div>
            <div class="card-body pt-0">
                <div id="dist_cliente_listas_resumen" class="mb-4 text-muted">Cargando cliente...</div>
                <div id="dist_cliente_listas_asignadas" class="d-flex flex-wrap gap-3 mb-5"></div>
                <div class="row g-3 align-items-end">
                    <div class="col-lg-9">
                        <label class="form-label">Agregar lista</label>
                        <select id="dist_cliente_lista_nueva" class="form-select"></select>
                    </div>
                    <div class="col-lg-3">
                        <button id="dist_cliente_lista_guardar" type="button" class="btn btn-primary w-100"><i class="bi bi-plus-circle"></i> Agregar lista</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12">
        <div class="card">
            <div class="card-header border-0 pt-6">
                <div class="card-title">
                    <div>
                        <h3 class="fw-bold mb-1">Productos de la lista seleccionada</h3>
                        <div id="dist_cliente_lista_activa" class="text-muted fs-7">Selecciona una lista para revisar productos.</div>
                    </div>
                </div>
                <div class="card-toolbar">
                    <button id="dist_cliente_lista_productos_guardar" type="button" class="btn btn-success"><i class="bi bi-check2-circle"></i> Guardar productos</button>
                </div>
            </div>
            <div class="card-body pt-0">
                <div id="dist_cliente_lista_intereses" class="mb-4"></div>
                <div class="dist-filter-row mb-4">
                    <input id="dist_cliente_lista_productos_buscar" type="text" class="form-control form-control-solid w-300px" placeholder="Buscar producto de esta lista">
                    <button id="dist_cliente_lista_productos_preferencias" type="button" class="btn btn-light-success">Marcar por preferencias</button>
                    <button id="dist_cliente_lista_productos_todos" type="button" class="btn btn-light-primary">Seleccionar visibles</button>
                    <button id="dist_cliente_lista_productos_limpiar" type="button" class="btn btn-light">Limpiar visibles</button>
                </div>
                <div class="table-responsive dist-table-wrap">
                    <table class="table align-middle table-row-dashed fs-6 gy-4">
                        <thead>
                            <tr class="text-start text-muted fw-bold fs-7 text-uppercase">
                                <th class="w-50px"></th>
                                <th>Producto</th>
                                <th>Categoria</th>
                                <th>SKU</th>
                                <th class="text-end">Precio</th>
                            </tr>
                        </thead>
                        <tbody id="dist_cliente_lista_productos"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
