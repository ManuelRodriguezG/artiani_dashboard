<div class="card">
    <div class="card-header border-0 pt-6">
        <div class="card-title">
            <div class="d-flex align-items-center position-relative my-1">
                <i class="bi bi-search fs-3 position-absolute ms-5"></i>
                <input type="text" id="dist_sugeridos_buscar" class="form-control form-control-solid w-300px ps-12" placeholder="Buscar cliente o SKU">
            </div>
        </div>
        <div class="card-toolbar dist-filter-row">
            <span id="dist_sugeridos_total" class="badge badge-light-warning">0</span>
            <select id="dist_sugeridos_marca" class="form-select form-select-solid dist-filtro-marca"><option value="">Marca</option></select>
            <select id="dist_sugeridos_categoria" class="form-select form-select-solid dist-filtro-categoria"><option value="">Categoria</option></select>
            <select id="dist_sugeridos_proveedor" class="form-select form-select-solid dist-filtro-proveedor"><option value="">Proveedor</option></select>
        </div>
    </div>
    <div class="card-body pt-0">
        <div class="table-responsive">
            <table class="table align-middle table-row-dashed fs-6 gy-5">
                <thead>
                    <tr class="text-start text-muted fw-bold fs-7 text-uppercase">
                        <th>Cliente</th>
                        <th>Producto</th>
                        <th>Marca / categoria</th>
                        <th>Proveedor</th>
                        <th class="text-end">Existencia cliente</th>
                        <th class="text-end">Min / Max</th>
                        <th class="text-end">Comprar sugerido</th>
                        <th>Ultimo conteo</th>
                    </tr>
                </thead>
                <tbody id="dist_sugeridos_lista"></tbody>
            </table>
        </div>
    </div>
</div>
