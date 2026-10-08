<div class="d-flex flex-wrap gap-2 mb-5">
    <a id="dist_cliente_listas_volver" href="/distribucionadmin/panel_clientes" class="btn btn-light"><i class="bi bi-arrow-left"></i> Cliente</a>
</div>
<div class="row g-5">
    <div class="col-xl-4">
        <div class="card">
            <div class="card-header border-0 pt-6">
                <h3 class="card-title fw-bold">Listas asignadas</h3>
            </div>
            <div class="card-body pt-0">
                <div id="dist_cliente_listas_resumen" class="mb-4 text-muted">Cargando cliente...</div>
                <div id="dist_cliente_listas_asignadas" class="d-flex flex-column gap-3"></div>
                <div class="separator my-6"></div>
                <div class="mb-3">
                    <label class="form-label">Agregar lista</label>
                    <select id="dist_cliente_lista_nueva" class="form-select"></select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Tipo</label>
                    <select id="dist_cliente_lista_tipo" class="form-select">
                        <option value="base">Base</option>
                        <option value="express">Express</option>
                        <option value="especial">Especial</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Productos</label>
                    <select id="dist_cliente_lista_modo" class="form-select">
                        <option value="todos">Todos los productos de la lista</option>
                        <option value="seleccionados">Solo productos seleccionados</option>
                    </select>
                </div>
                <button id="dist_cliente_lista_guardar" type="button" class="btn btn-primary w-100"><i class="bi bi-plus-circle"></i> Guardar lista</button>
            </div>
        </div>
    </div>
    <div class="col-xl-8">
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
                <div class="dist-filter-row mb-4">
                    <input id="dist_cliente_lista_productos_buscar" type="text" class="form-control form-control-solid w-300px" placeholder="Buscar producto de esta lista">
                    <button id="dist_cliente_lista_productos_todos" type="button" class="btn btn-light-primary">Seleccionar visibles</button>
                    <button id="dist_cliente_lista_productos_limpiar" type="button" class="btn btn-light">Limpiar visibles</button>
                </div>
                <div class="table-responsive dist-table-wrap">
                    <table class="table align-middle table-row-dashed fs-6 gy-4">
                        <thead>
                            <tr class="text-start text-muted fw-bold fs-7 text-uppercase">
                                <th class="w-50px"></th>
                                <th>Producto</th>
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
