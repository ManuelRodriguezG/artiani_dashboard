<div class="card">
    <div class="card-header border-0 pt-6">
        <div class="card-title">
            <div class="d-flex align-items-center position-relative my-1">
                <i class="bi bi-search fs-3 position-absolute ms-5"></i>
                <input type="text" id="dist_solicitudes_buscar" class="form-control form-control-solid w-250px ps-12" placeholder="Buscar solicitud">
            </div>
        </div>
        <div class="card-toolbar">
            <select id="dist_solicitudes_estatus" class="form-select form-select-solid w-175px">
                <option value="">Todos</option>
                <option value="pendiente">Pendientes</option>
                <option value="aprobado">Aprobadas</option>
                <option value="rechazado">Rechazadas</option>
            </select>
        </div>
    </div>
    <div class="card-body pt-0">
        <div class="table-responsive">
            <table class="table align-middle table-row-dashed fs-6 gy-5">
                <thead>
                    <tr class="text-start text-muted fw-bold fs-7 text-uppercase">
                        <th>Solicitud</th>
                        <th>Contacto</th>
                        <th>Negocio</th>
                        <th>Ubicacion</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody id="dist_solicitudes_lista"></tbody>
            </table>
        </div>
    </div>
</div>
