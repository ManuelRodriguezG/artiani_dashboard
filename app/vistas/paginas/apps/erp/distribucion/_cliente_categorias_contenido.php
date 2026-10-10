<div class="d-flex flex-wrap gap-2 mb-5">
    <a id="dist_cliente_categorias_volver" href="/distribucionadmin/panel_clientes" class="btn btn-light"><i class="bi bi-arrow-left"></i> Cliente</a>
    <a id="dist_cliente_categorias_listas" href="#" class="btn btn-light-primary"><i class="bi bi-tags"></i> Listas y productos</a>
</div>
<div class="card">
    <div class="card-header border-0 pt-6">
        <div class="card-title">
            <div>
                <h3 class="fw-bold mb-1">Preferencias de categorias</h3>
                <div class="text-muted fs-7">Agrega o quita categorias de interes para guiar que productos se habilitan en sus listas.</div>
            </div>
        </div>
        <div class="card-toolbar">
            <button id="dist_cliente_categorias_guardar" type="button" class="btn btn-primary"><i class="bi bi-save"></i> Guardar preferencias</button>
        </div>
    </div>
    <div class="card-body pt-0">
        <div id="dist_cliente_categorias_resumen" class="text-muted mb-5">Cargando cliente...</div>
        <div id="dist_cliente_categorias_actuales" class="mb-5"></div>
        <div class="row g-5">
            <div class="col-xl-8">
                <div id="dist_cliente_categorias_lista" class="row g-3"></div>
            </div>
            <div class="col-xl-4">
                <div class="border rounded p-4 h-100">
                    <div class="fw-bold mb-2">Asignaciones sugeridas</div>
                    <div class="text-muted fs-8 mb-3">Elige un tipo de negocio para marcar categorias recomendadas.</div>
                    <select id="dist_cliente_sugerencia_tipo" class="form-select mb-3">
                        <option value="">Tipo de negocio</option>
                        <option value="venta_internet">Venta por internet</option>
                        <option value="veterinaria">Veterinaria</option>
                        <option value="petshop">Petshop</option>
                        <option value="acuario">Acuario</option>
                        <option value="acuario_petshop">Acuario + petshop</option>
                        <option value="estetica_canina">Estetica canina</option>
                        <option value="criador">Criador</option>
                        <option value="vendedor_mercado">Vendedor de mercado</option>
                        <option value="vendedor_ambulante">Vendedor ambulante</option>
                    </select>
                    <button id="dist_cliente_sugerencia_aplicar" type="button" class="btn btn-light-primary w-100">Aplicar sugerencia</button>
                    <div id="dist_cliente_sugerencia_detalle" class="text-muted fs-8 mt-3"></div>
                </div>
            </div>
        </div>
    </div>
</div>
