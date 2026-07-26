<div class="modal fade" id="proyectos_modal_tarea" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-750px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">Tarea</h2>
                <button type="button" class="btn btn-icon btn-sm btn-active-light-primary" data-bs-dismiss="modal">
                    <i class="bi bi-x fs-2"></i>
                </button>
            </div>
            <form id="proyectos_form_tarea">
                <div class="modal-body">
                    <input type="hidden" name="id_tarea" id="tarea_id_tarea">
                    <div class="row g-4">
                        <div class="col-md-12">
                            <label class="form-label">Proyecto</label>
                            <select class="form-select form-select-solid" name="id_proyecto" id="tarea_id_proyecto" required></select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Titulo</label>
                            <input type="text" class="form-control form-control-solid" name="titulo" id="tarea_titulo" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Descripcion</label>
                            <textarea class="form-control form-control-solid" name="descripcion" id="tarea_descripcion" rows="3"></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Estado</label>
                            <select class="form-select form-select-solid" name="estatus" id="tarea_estatus"></select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Prioridad</label>
                            <select class="form-select form-select-solid" name="prioridad" id="tarea_prioridad"></select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Origen</label>
                            <select class="form-select form-select-solid" name="origen" id="tarea_origen"></select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Responsable</label>
                            <select class="form-select form-select-solid" name="id_responsable" id="tarea_id_responsable"></select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Area</label>
                            <input type="text" class="form-control form-control-solid" name="area_responsable" id="tarea_area">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Modulo</label>
                            <select class="form-select form-select-solid" name="modulo_relacionado" id="tarea_modulo"></select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Vencimiento</label>
                            <input type="date" class="form-control form-control-solid" name="fecha_vencimiento" id="tarea_fecha_vencimiento">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">URL de contexto</label>
                            <input type="text" class="form-control form-control-solid" name="url_contexto" id="tarea_url_contexto" placeholder="/compra/mostrar_compra_ordenes">
                        </div>
                        <div class="col-md-12">
                            <label class="form-check form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox" value="1" name="requiere_autorizacion" id="tarea_requiere_autorizacion">
                                <span class="form-check-label">Requiere autorizacion antes de cerrar</span>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>
