<div class="d-flex flex-wrap gap-2 mb-5">
    <a href="/distribucionadmin/panel_prospectos" class="btn btn-light"><i class="bi bi-arrow-left"></i> Prospectos</a>
    <button id="dist_prospecto_whatsapp" type="button" class="btn btn-light-success"><i class="bi bi-whatsapp"></i> Contactar por WhatsApp</button>
    <button id="dist_prospecto_revision" type="button" class="btn btn-light-primary"><i class="bi bi-clipboard-check"></i> Registrar revisión</button>
    <button id="dist_prospecto_aprobar" type="button" class="btn btn-success"><i class="bi bi-check2-circle"></i> Aprobar acceso</button>
    <button id="dist_prospecto_rechazar" type="button" class="btn btn-light-danger"><i class="bi bi-x-circle"></i> Rechazar</button>
</div>
<div class="row g-5">
    <div class="col-12">
        <div class="card">
            <div class="card-body" id="dist_prospecto_encabezado">Cargando expediente...</div>
        </div>
    </div>
    <div class="col-xl-7">
        <div class="card h-100">
            <div class="card-header border-0 pt-6"><h3 class="card-title fw-bold">Datos proporcionados</h3></div>
            <div class="card-body pt-0"><div id="dist_prospecto_datos" class="row g-4"></div></div>
        </div>
    </div>
    <div class="col-xl-5">
        <div class="card mb-5">
            <div class="card-header border-0 pt-6"><h3 class="card-title fw-bold">Intereses y contexto comercial</h3></div>
            <div class="card-body pt-0" id="dist_prospecto_intereses"></div>
        </div>
        <div class="card">
            <div class="card-header border-0 pt-6"><h3 class="card-title fw-bold">Datos técnicos del registro</h3></div>
            <div class="card-body pt-0" id="dist_prospecto_tecnico"></div>
        </div>
    </div>
</div>
