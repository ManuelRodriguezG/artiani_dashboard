<div class="card">
    <div class="card-header border-0 pt-6">
        <div class="card-title">
            <div>
                <h2 class="fw-bold mb-1">Pedidos</h2>
                <div class="text-muted fs-7">Bandeja de revision interna: solicitado no se modifica; aqui se confirma existencia, precio y respuesta.</div>
            </div>
        </div>
        <div class="card-toolbar dist-filter-row">
            <span id="dist_cotizaciones_total" class="badge badge-light-primary">0</span>
            <input type="text" id="dist_cotizaciones_buscar" class="form-control form-control-solid" placeholder="Buscar folio o cliente">
            <select id="dist_cotizaciones_estatus" class="form-select form-select-solid">
                <option value="">Todos</option>
                <option value="pedido_solicitado">Pedido solicitado</option>
                <option value="recibida">Recibida</option>
                <option value="recibida_revision">Revision</option>
                <option value="en_revision">En revision</option>
                <option value="respondida">Respondida</option>
                <option value="cerrada">Cerrada</option>
                <option value="cancelada">Cancelada</option>
            </select>
        </div>
    </div>
    <div class="card-body pt-0">
        <div class="table-responsive">
            <table class="table align-middle table-row-dashed fs-6 gy-5">
                <thead>
                    <tr class="text-start text-muted fw-bold fs-7 text-uppercase">
                        <th>Folio</th>
                        <th>Cliente</th>
                        <th>Revision</th>
                        <th>Solicitado</th>
                        <th>Confirmado</th>
                        <th>Valor</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody id="dist_cotizaciones_lista"></tbody>
            </table>
        </div>
    </div>
</div>
