<div class="card">
    <div class="card-header border-0 pt-6">
        <div class="card-title">
            <div class="d-flex align-items-center position-relative my-1">
                <i class="bi bi-search fs-3 position-absolute ms-5"></i>
                <input type="text" id="dist_productos_buscar" class="form-control form-control-solid w-300px ps-12" placeholder="Buscar SKU o producto">
            </div>
        </div>
        <div class="card-toolbar dist-filter-row">
            <select id="dist_productos_marca" class="form-select form-select-solid dist-filtro-marca"><option value="">Marca</option></select>
            <select id="dist_productos_categoria" class="form-select form-select-solid dist-filtro-categoria"><option value="">Categoria</option></select>
            <select id="dist_productos_proveedor" class="form-select form-select-solid dist-filtro-proveedor"><option value="">Proveedor</option></select>
            <select id="dist_productos_canal" class="form-select form-select-solid"><option value="">Canal</option><option value="publicado">Publicado</option><option value="no_publicado">No publicado</option><option value="inactivo">Inactivo</option></select>
            <select id="dist_productos_precio" class="form-select form-select-solid"><option value="">Precio</option><option value="con_precio">Con precio</option><option value="sin_precio">Sin precio</option></select>
            <select id="dist_productos_imagen" class="form-select form-select-solid"><option value="">Imagen</option><option value="con_imagen">Con imagen</option><option value="sin_imagen">Sin imagen</option></select>
            <select id="dist_productos_ficha" class="form-select form-select-solid"><option value="">Ficha</option><option value="completa">Completa</option><option value="incompleta">Incompleta</option></select>
            <button type="button" id="dist_productos_buscar_btn" class="btn btn-light-primary"><i class="bi bi-search"></i> Buscar</button>
            <button type="button" id="dist_productos_publicar_lote" class="btn btn-light-success"><i class="bi bi-cloud-upload"></i> Publicar lote</button>
            <button type="button" id="dist_productos_publicar_filtrados" class="btn btn-success"><i class="bi bi-cloud-check"></i> Publicar todos filtrados</button>
            <button type="button" id="dist_productos_asignar_cliente" class="btn btn-light-primary"><i class="bi bi-person-plus"></i> Asignar a cliente</button>
            <button type="button" id="dist_productos_desactivar_lote" class="btn btn-light-danger"><i class="bi bi-eye-slash"></i> Desactivar lote</button>
            <select id="dist_productos_limite" class="form-select form-select-solid w-100px">
                <option value="50">50</option>
                <option value="120" selected>120</option>
                <option value="300">300</option>
            </select>
        </div>
    </div>
    <div class="card-body pt-0">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
            <div id="dist_productos_paginacion_info" class="text-muted fs-7">Productos 0 de 0</div>
            <div class="d-flex align-items-center gap-2">
                <button type="button" id="dist_productos_pagina_anterior" class="btn btn-sm btn-light"><i class="bi bi-chevron-left"></i> Anterior</button>
                <span id="dist_productos_pagina_actual" class="badge badge-light-primary">1 / 1</span>
                <button type="button" id="dist_productos_pagina_siguiente" class="btn btn-sm btn-light">Siguiente <i class="bi bi-chevron-right"></i></button>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table align-middle table-row-dashed fs-6 gy-5">
                <thead>
                    <tr class="text-start text-muted fw-bold fs-7 text-uppercase">
                        <th><input class="form-check-input" type="checkbox" id="dist_productos_select_all"></th>
                        <th>SKU</th>
                        <th>Producto</th>
                        <th>Marca / categoria</th>
                        <th>Proveedor</th>
                        <th>Calidad</th>
                        <th>Slug</th>
                        <th>Estado canal</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody id="dist_productos_lista"></tbody>
            </table>
        </div>
    </div>
</div>
