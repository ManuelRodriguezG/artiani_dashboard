<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../../">
    <title>Auditoria de proveedores</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet">
    <link href="assets/css/style.bundle.css" rel="stylesheet">
</head>
<body id="kt_app_body" data-kt-app-layout="dark-sidebar" data-kt-app-header-fixed="true" data-kt-app-sidebar-enabled="true" data-kt-app-sidebar-fixed="true" class="app-default">
<div class="d-flex flex-column flex-root app-root">
    <div class="app-page flex-column flex-column-fluid">
        <?= include_once '../app/vistas/includes/header/header.php'; ?>
        <div class="app-wrapper flex-column flex-row-fluid">
            <?= include_once '../app/vistas/includes/header/sidebar.php'; ?>
            <main class="app-main flex-column flex-row-fluid">
                <div class="app-toolbar py-3 py-lg-6">
                    <div class="app-container container-fluid d-flex flex-stack">
                        <div>
                            <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Auditoria de proveedores</h1>
                            <span class="text-muted">Revision solo lectura de listas legacy, relaciones SKU proveedor y huecos para Compras</span>
                        </div>
                        <div class="d-flex gap-2">
                            <a class="btn btn-light-success" href="/proveedor/auditoria_exportar_erp?formato=csv">
                                <i class="bi bi-filetype-csv"></i> CSV
                            </a>
                            <a class="btn btn-light-info" href="/proveedor/auditoria_exportar_erp?formato=json">
                                <i class="bi bi-filetype-json"></i> JSON
                            </a>
                            <button class="btn btn-light-primary" id="proveedores_auditoria_recargar" type="button">
                                <i class="bi bi-arrow-clockwise"></i> Actualizar
                            </button>
                        </div>
                    </div>
                </div>
                <div class="app-content flex-column-fluid">
                    <div class="app-container container-fluid">
                        <div class="alert alert-info d-flex align-items-start gap-3 mb-6">
                            <i class="bi bi-shield-check fs-2"></i>
                            <div>
                                <div class="fw-bold">Dry-run sin escrituras</div>
                                <div class="text-muted">Esta pantalla solo consulta conteos. No crea incidencias, no aplica costos, no modifica Catalogo y no ejecuta migraciones.</div>
                            </div>
                        </div>
                        <div class="row g-4 mb-6" id="proveedores_auditoria_resumen">
                            <div class="col-12 text-muted">Cargando auditoria...</div>
                        </div>
                        <div class="card mb-7">
                            <div class="card-header border-0 pt-5">
                                <div class="card-title">
                                    <div>
                                        <h3 class="fw-bold mb-1">Esquema planeado</h3>
                                        <span class="text-muted fs-7">Comparacion solo lectura entre el contrato propuesto y la base actual</span>
                                    </div>
                                </div>
                                <div class="card-toolbar">
                                    <button class="btn btn-sm btn-light-primary" id="proveedores_esquema_recargar" type="button">
                                        <i class="bi bi-arrow-clockwise"></i> Actualizar esquema
                                    </button>
                                </div>
                            </div>
                            <div class="card-body pt-3">
                                <div class="d-flex flex-wrap gap-3 mb-5" id="proveedores_esquema_resumen">
                                    <span class="text-muted">Cargando esquema...</span>
                                </div>
                                <div class="table-responsive">
                                    <table class="table align-middle table-row-dashed gy-4 mb-0">
                                        <thead>
                                            <tr class="text-muted fw-bold fs-7 text-uppercase">
                                                <th>Tabla</th>
                                                <th>Severidad</th>
                                                <th class="text-end">Columnas faltantes</th>
                                                <th class="text-end">Indices faltantes</th>
                                            </tr>
                                        </thead>
                                        <tbody id="proveedores_esquema_tabla"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="card mb-7">
                            <div class="card-header border-0 pt-5">
                                <div class="card-title">
                                    <div>
                                        <h3 class="fw-bold mb-1">Hallazgos</h3>
                                        <span class="text-muted fs-7">Conteos tecnicos para decidir el siguiente trabajo de Proveedores</span>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body pt-3">
                                <div class="table-responsive">
                                    <table class="table align-middle table-row-dashed gy-4 mb-0">
                                        <thead>
                                            <tr class="text-muted fw-bold fs-7 text-uppercase">
                                                <th>Revision</th>
                                                <th>Severidad</th>
                                                <th>Estado</th>
                                                <th class="text-end">Conteo</th>
                                            </tr>
                                        </thead>
                                        <tbody id="proveedores_auditoria_hallazgos"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="card mb-7">
                            <div class="card-header border-0 pt-5">
                                <div class="card-title">
                                    <div>
                                        <h3 class="fw-bold mb-1">Muestras de hallazgos</h3>
                                        <span class="text-muted fs-7">Ejemplos limitados para decidir migracion, limpieza o correccion operativa</span>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body pt-3">
                                <div class="table-responsive">
                                    <table class="table align-middle table-row-dashed gy-4 mb-0">
                                        <thead>
                                            <tr class="text-muted fw-bold fs-7 text-uppercase">
                                                <th>Grupo</th>
                                                <th>Proveedor/lista</th>
                                                <th>SKU/nombre</th>
                                                <th class="text-end">Dato</th>
                                            </tr>
                                        </thead>
                                        <tbody id="proveedores_auditoria_muestras"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="card mb-7">
                            <div class="card-header border-0 pt-5">
                                <div class="card-title">
                                    <div>
                                        <h3 class="fw-bold mb-1">Productivo legacy SQL</h3>
                                        <span class="text-muted fs-7">Revision de archivos en db/productivo sin ejecutar importacion</span>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body pt-3">
                                <div class="d-flex flex-wrap gap-3 mb-5" id="proveedores_productivo_resumen"></div>
                                <div class="table-responsive mb-6">
                                    <table class="table align-middle table-row-dashed gy-4 mb-0">
                                        <thead>
                                            <tr class="text-muted fw-bold fs-7 text-uppercase">
                                                <th>Archivo</th>
                                                <th>Estado</th>
                                                <th class="text-end">Tamano</th>
                                            </tr>
                                        </thead>
                                        <tbody id="proveedores_productivo_archivos"></tbody>
                                    </table>
                                </div>
                                <div class="table-responsive">
                                    <table class="table align-middle table-row-dashed gy-4 mb-0">
                                        <thead>
                                            <tr class="text-muted fw-bold fs-7 text-uppercase">
                                                <th>Grupo</th>
                                                <th>Referencia</th>
                                                <th>Descripcion</th>
                                                <th class="text-end">Dato</th>
                                            </tr>
                                        </thead>
                                        <tbody id="proveedores_productivo_muestras"></tbody>
                                    </table>
                                </div>
                                <div class="mt-5" id="proveedores_productivo_advertencias"></div>
                                <div class="separator my-7"></div>
                                <h4 class="fw-bold mb-4">Vista previa de migracion</h4>
                                <div class="d-flex flex-wrap gap-3 mb-5" id="proveedores_productivo_preview_resumen"></div>
                                <div class="table-responsive">
                                    <table class="table align-middle table-row-dashed gy-4 mb-0">
                                        <thead>
                                            <tr class="text-muted fw-bold fs-7 text-uppercase">
                                                <th>Tipo</th>
                                                <th>Origen</th>
                                                <th>Referencia</th>
                                                <th class="text-end">Accion propuesta</th>
                                            </tr>
                                        </thead>
                                        <tbody id="proveedores_productivo_preview_muestras"></tbody>
                                    </table>
                                </div>
                                <div class="separator my-7"></div>
                                <h4 class="fw-bold mb-4">Lotes staging cargados</h4>
                                <div class="table-responsive mb-6">
                                    <table class="table align-middle table-row-dashed gy-4 mb-0">
                                        <thead>
                                            <tr class="text-muted fw-bold fs-7 text-uppercase">
                                                <th>Lote</th>
                                                <th>Fecha</th>
                                                <th class="text-end">Registros</th>
                                            </tr>
                                        </thead>
                                        <tbody id="proveedores_staging_lotes"></tbody>
                                    </table>
                                </div>
                                <div class="table-responsive">
                                    <table class="table align-middle table-row-dashed gy-4 mb-0">
                                        <thead>
                                            <tr class="text-muted fw-bold fs-7 text-uppercase">
                                                <th>Lote/tipo</th>
                                                <th>Accion</th>
                                                <th class="text-end">Total</th>
                                            </tr>
                                        </thead>
                                        <tbody id="proveedores_staging_resumen"></tbody>
                                    </table>
                                </div>
                                <div class="table-responsive mt-6">
                                    <table class="table align-middle table-row-dashed gy-4 mb-0">
                                        <thead>
                                            <tr class="text-muted fw-bold fs-7 text-uppercase">
                                                <th>Revision</th>
                                                <th>Referencia</th>
                                                <th>Motivo</th>
                                            </tr>
                                        </thead>
                                        <tbody id="proveedores_staging_revision"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="card mb-7">
                            <div class="card-header border-0 pt-5">
                                <div class="card-title">
                                    <div>
                                        <h3 class="fw-bold mb-1">Preflight P9-08 migracion por lotes</h3>
                                        <span class="text-muted fs-7">Revision previa solo lectura antes de autorizar respaldos, staging o conversion</span>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body pt-3">
                                <div class="d-flex flex-wrap gap-3 mb-5" id="proveedores_preflight_resumen"></div>
                                <div class="row g-5">
                                    <div class="col-lg-6">
                                        <h5 class="fw-bold mb-3">Riesgos y pendientes</h5>
                                        <div id="proveedores_preflight_riesgos" class="d-flex flex-column gap-3"></div>
                                    </div>
                                    <div class="col-lg-6">
                                        <h5 class="fw-bold mb-3">Pasos antes de migrar</h5>
                                        <div id="proveedores_preflight_pasos" class="d-flex flex-column gap-3"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card mb-7">
                            <div class="card-header border-0 pt-5">
                                <div class="card-title">
                                    <div>
                                        <h3 class="fw-bold mb-1">Permisos Proveedores por rol</h3>
                                        <span class="text-muted fs-7">Comparativo solo lectura contra la matriz base de Seguridad</span>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body pt-3">
                                <div class="d-flex flex-wrap gap-3 mb-5" id="proveedores_permisos_resumen"></div>
                                <div class="table-responsive">
                                    <table class="table align-middle table-row-dashed gy-4 mb-0">
                                        <thead>
                                            <tr class="text-muted fw-bold fs-7 text-uppercase">
                                                <th>Rol</th>
                                                <th class="text-end">Asignados</th>
                                                <th class="text-end">Esperados</th>
                                                <th>Revision</th>
                                            </tr>
                                        </thead>
                                        <tbody id="proveedores_permisos_roles"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="row g-4">
                            <div class="col-lg-7">
                                <div class="card h-100">
                                    <div class="card-header border-0 pt-5">
                                        <div class="card-title">
                                            <div>
                                                <h3 class="fw-bold mb-1">Tablas revisadas</h3>
                                                <span class="text-muted fs-7">Disponibilidad detectada antes de ejecutar cada conteo</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-body pt-3">
                                        <div class="d-flex flex-wrap gap-2" id="proveedores_auditoria_tablas"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-5">
                                <div class="card h-100">
                                    <div class="card-header border-0 pt-5">
                                        <div class="card-title">
                                            <div>
                                                <h3 class="fw-bold mb-1">Limitaciones</h3>
                                                <span class="text-muted fs-7">Puntos que siguen requiriendo decision antes de escribir datos</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-body pt-3">
                                        <div id="proveedores_auditoria_limitaciones" class="d-flex flex-column gap-3"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</div>
<script src="assets/plugins/global/plugins.bundle.js"></script>
<script src="assets/js/scripts.bundle.js"></script>
<script src="/assets/js/custom/apps/erp/proveedores/auditoria.js?v=20260612-1"></script>
</body>
</html>
