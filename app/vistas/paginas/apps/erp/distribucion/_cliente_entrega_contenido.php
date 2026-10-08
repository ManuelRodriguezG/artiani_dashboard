<div class="d-flex flex-wrap gap-2 mb-5">
    <a id="dist_cliente_entrega_volver" href="/distribucionadmin/panel_clientes" class="btn btn-light"><i class="bi bi-arrow-left"></i> Cliente</a>
</div>
<div class="card">
    <div class="card-header border-0 pt-6">
        <h3 class="card-title fw-bold">Entrega y logistica</h3>
    </div>
    <div class="card-body pt-0">
        <div id="dist_cliente_entrega_resumen" class="text-muted mb-5">Cargando cliente...</div>
        <div class="row g-4">
            <div class="col-md-4">
                <label class="form-label">Metodo default</label>
                <select id="dist_cliente_entrega_metodo" class="form-select">
                    <option value="por_definir">Por definir</option>
                    <option value="envio">Envio</option>
                    <option value="recoger_tienda">Recoger en tienda</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Costo envio default</label>
                <input id="dist_cliente_entrega_costo" type="number" min="0" step="0.01" class="form-control" value="0">
            </div>
            <div class="col-md-4 d-flex align-items-end gap-4">
                <label class="form-check form-switch form-check-custom form-check-solid">
                    <input id="dist_cliente_entrega_envio" class="form-check-input" type="checkbox">
                    <span class="form-check-label">Envio</span>
                </label>
                <label class="form-check form-switch form-check-custom form-check-solid">
                    <input id="dist_cliente_entrega_recoger" class="form-check-input" type="checkbox">
                    <span class="form-check-label">Recoger</span>
                </label>
            </div>
        </div>
        <div class="d-flex justify-content-end mt-6">
            <button id="dist_cliente_entrega_guardar" type="button" class="btn btn-primary"><i class="bi bi-save"></i> Guardar entrega</button>
        </div>
    </div>
</div>
