<div class="card">
    <div class="card-header border-0 pt-6">
        <div class="card-title">
            <div class="d-flex align-items-center position-relative my-1">
                <i class="bi bi-search fs-3 position-absolute ms-5"></i>
                <input type="text" id="dist_clientes_buscar" class="form-control form-control-solid w-250px ps-12" placeholder="Buscar cliente">
            </div>
        </div>
        <div class="card-toolbar dist-filter-row">
            <select id="dist_clientes_estatus" class="form-select form-select-solid w-175px">
                <option value="">Todos</option>
                <option value="aprobado">Aprobados</option>
                <option value="suspendido">Suspendidos</option>
                <option value="rechazado">Rechazados</option>
            </select>
            <select id="dist_clientes_incompletos" class="form-select form-select-solid w-175px">
                <option value="">Completitud</option>
                <option value="sin_lista">Sin lista</option>
                <option value="sin_permisos">Sin permisos</option>
            </select>
        </div>
    </div>
    <div class="card-body pt-0">
        <div class="table-responsive">
            <table class="table align-middle table-row-dashed fs-6 gy-5">
                <thead>
                    <tr class="text-start text-muted fw-bold fs-7 text-uppercase">
                        <th>Cliente</th>
                        <th>Tipo</th>
                        <th>Lista</th>
                        <th>Indicadores</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody id="dist_clientes_lista"></tbody>
            </table>
        </div>
    </div>
</div>
