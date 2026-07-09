<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../">
    <title>Usuarios y roles</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css">
    <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css">
</head>
<body id="kt_app_body" data-kt-app-layout="dark-sidebar" data-kt-app-header-fixed="true" data-kt-app-sidebar-enabled="true" data-kt-app-sidebar-fixed="true" class="app-default">
    <input type="hidden" id="seguridad_puede_administrar" value="<?= SesionSeguridad::tienePermiso('seguridad.administrar') ? '1' : '0' ?>">
    <div class="d-flex flex-column flex-root app-root" id="kt_app_root">
        <div class="app-page flex-column flex-column-fluid" id="kt_app_page">
            <?= include_once '../app/vistas/includes/header/header.php'; ?>
            <div class="app-wrapper flex-column flex-row-fluid" id="kt_app_wrapper">
                <?= include_once '../app/vistas/includes/header/sidebar.php'; ?>
                <div class="app-main flex-column flex-row-fluid" id="kt_app_main">
                    <div class="d-flex flex-column flex-column-fluid">
                        <div class="app-toolbar py-3 py-lg-6">
                            <div class="app-container container-fluid d-flex flex-stack">
                                <div>
                                    <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Usuarios y roles</h1>
                                    <span class="text-muted">Administra acceso interno al ERP</span>
                                </div>
                                <?php if (SesionSeguridad::tienePermiso('seguridad.administrar')): ?>
                                <button type="button" id="seguridad_nuevo_usuario" class="btn btn-primary">
                                    <i class="bi bi-person-plus fs-3"></i>
                                    Nuevo usuario
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="app-content flex-column-fluid">
                            <div class="app-container container-fluid">
                                <div class="card mb-6">
                                    <div class="card-header border-0 pt-6">
                                        <div class="card-title">
                                            <div>
                                                <h2 class="fw-bold mb-1">Permisos por rol</h2>
                                                <span class="text-muted">Define las acciones disponibles para cada perfil</span>
                                            </div>
                                        </div>
                                        <div class="card-toolbar d-flex gap-3">
                                            <select id="seguridad_rol_permisos" class="form-select form-select-solid w-250px"></select>
                                            <?php if (SesionSeguridad::tienePermiso('seguridad.administrar')): ?>
                                            <button id="seguridad_guardar_permisos" class="btn btn-primary">
                                                <i class="bi bi-shield-check"></i> Guardar permisos
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="card-body pt-4">
                                        <div id="seguridad_permisos_resumen" class="text-muted mb-5"></div>
                                        <div id="seguridad_permisos_modulos" class="row g-5"></div>
                                    </div>
                                </div>
                                <div class="card">
                                    <div class="card-header border-0 pt-6">
                                        <div class="card-title">
                                            <div class="d-flex align-items-center position-relative my-1">
                                                <i class="bi bi-search fs-3 position-absolute ms-5"></i>
                                                <input type="text" id="seguridad_buscar" class="form-control form-control-solid w-250px ps-12" placeholder="Buscar usuario">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-body pt-0">
                                        <div class="row g-3 mb-5" id="seguridad_auditoria_filtros">
                                            <div class="col-md-2">
                                                <input type="text" id="seguridad_auditoria_usuario" class="form-control form-control-solid" placeholder="Usuario">
                                            </div>
                                            <div class="col-md-2">
                                                <input type="text" id="seguridad_auditoria_modulo" class="form-control form-control-solid" placeholder="Modulo">
                                            </div>
                                            <div class="col-md-2">
                                                <input type="text" id="seguridad_auditoria_accion" class="form-control form-control-solid" placeholder="Accion">
                                            </div>
                                            <div class="col-md-2">
                                                <select id="seguridad_auditoria_resultado" class="form-select form-select-solid">
                                                    <option value="">Resultado</option>
                                                    <option value="ok">ok</option>
                                                    <option value="error">error</option>
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <input type="date" id="seguridad_auditoria_desde" class="form-control form-control-solid">
                                            </div>
                                            <div class="col-md-2 d-flex gap-2">
                                                <input type="date" id="seguridad_auditoria_hasta" class="form-control form-control-solid">
                                                <button type="button" id="seguridad_auditoria_filtrar" class="btn btn-light-primary">Filtrar</button>
                                            </div>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table align-middle table-row-dashed fs-6 gy-5">
                                                <thead>
                                                    <tr class="text-start text-muted fw-bold fs-7 text-uppercase">
                                                        <th>Usuario</th>
                                                        <th>Celular</th>
                                                        <th>Estado</th>
                                                        <th>Roles</th>
                                                        <th class="text-end">Asignar rol</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="seguridad_usuarios_roles"></tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <?php if (SesionSeguridad::tienePermiso('auditoria.ver')): ?>
                                <div class="card mt-6">
                                    <div class="card-header border-0 pt-6">
                                        <div class="card-title">
                                            <h2 class="fw-bold mb-0">Actividad reciente</h2>
                                        </div>
                                        <div class="card-toolbar">
                                            <span class="text-muted">Últimos 100 eventos</span>
                                        </div>
                                    </div>
                                    <div class="card-body pt-0">
                                        <div class="table-responsive">
                                            <table class="table align-middle table-row-dashed fs-6 gy-5">
                                                <thead>
                                                    <tr class="text-start text-muted fw-bold fs-7 text-uppercase">
                                                        <th>Fecha</th>
                                                        <th>Usuario</th>
                                                        <th>Módulo</th>
                                                        <th>Acción</th>
                                                        <th>Resultado</th>
                                                        <th>Detalle</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="seguridad_auditoria"></tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="assets/plugins/global/plugins.bundle.js"></script>
    <script src="assets/js/scripts.bundle.js"></script>
    <script src="/assets/js/custom/security/users-roles.js"></script>
</body>
</html>
