<?php
$permisosSesion = isset($_SESSION['permisos']) && is_array($_SESSION['permisos']) ? $_SESSION['permisos'] : array();

if (!empty($_SESSION['id_usuario'])) {
    require_once '../app/modelos/SeguridadPermisos.php';
    $seguridadSidebar = new SeguridadPermisos();
    $autorizacionSidebar = $seguridadSidebar->autorizacionUsuario($_SESSION['id_usuario']);
    $_SESSION['roles'] = $autorizacionSidebar['roles'];
    $_SESSION['permisos'] = $autorizacionSidebar['permisos'];
    $permisosSesion = $autorizacionSidebar['permisos'];
}

$puede = function ($permiso) use ($permisosSesion) {
    return in_array($permiso, $permisosSesion, true);
};

$gruposMenu = array(
    array(
        'seccion' => 'ERP',
        'titulo' => 'Catalogo',
        'icono' => 'bi-box-seam',
        'permiso' => 'catalogo.ver',
        'items' => array(
            array('titulo' => 'Productos ERP', 'ruta' => '/catalogoerp', 'permiso' => 'catalogo.ver'),
            array('titulo' => 'Organizacion catalogo', 'ruta' => '/catalogoerp/organizacion', 'permiso' => 'catalogo.ver'),
            array('titulo' => 'Revision migracion', 'ruta' => '/catalogoerp/migracion_ecommerce', 'permiso' => 'catalogo.ver'),
            array('titulo' => 'Configuracion catalogo', 'ruta' => '/catalogoerp/configuracion', 'permiso' => 'catalogo.editar'),
            array('titulo' => 'Existencias', 'ruta' => '/inventario/productos_existencias', 'permiso' => 'inventario.ver'),
            array('titulo' => 'Catalogo ecommerce', 'ruta' => '/producto/catalogo', 'permiso' => 'ecommerce.ver')
        )
    ),
    array(
        'seccion' => 'ERP',
        'titulo' => 'Rentabilidad',
        'icono' => 'bi-graph-up-arrow',
        'permiso' => 'rentabilidad.ver',
        'items' => array(
            array('titulo' => 'Analisis comercial', 'ruta' => '/rentabilidad/analisis', 'permiso' => 'rentabilidad.ver')
        )
    ),
    array(
        'seccion' => 'ERP',
        'titulo' => 'Compras',
        'icono' => 'bi-cart-check',
        'permiso' => 'compras.ver',
        'items' => array(
            array('titulo' => 'Ordenes de compra', 'ruta' => '/compra/mostrar_compra_ordenes', 'permiso' => 'compras.ver'),
            array('titulo' => 'Nueva orden', 'ruta' => '/compra/crear_orden_compra', 'permiso' => 'compras.crear'),
            array('titulo' => 'Solicitudes', 'ruta' => '/compra/mostrar_solicitudes', 'permiso' => 'compras.ver'),
            array('titulo' => 'Nueva solicitud', 'ruta' => '/compra/solicitud_compra_nueva', 'permiso' => 'compras.crear'),
            array('titulo' => 'Proveedores legacy', 'ruta' => '/proveedor/listas_mostrar', 'permiso' => 'compras.ver')
        )
    ),
    array(
        'seccion' => 'ERP',
        'titulo' => 'Proveedores',
        'icono' => 'bi-truck',
        'permiso' => 'proveedores.ver',
        'items' => array(
            array('titulo' => 'Maestro proveedores', 'ruta' => '/proveedor/mostrar_proveedores_erp', 'permiso' => 'proveedores.ver'),
            array('titulo' => 'Auditoria proveedores', 'ruta' => '/proveedor/auditoria_erp', 'permiso' => 'proveedores.auditoria')
        )
    ),
    array(
        'seccion' => 'ERP',
        'titulo' => 'Almacen',
        'icono' => 'bi-building',
        'permiso' => 'almacen.ver',
        'items' => array(
            array('titulo' => 'Recepciones', 'ruta' => '/almacen/mostrar_recepciones', 'permiso' => 'almacen.ver'),
            array('titulo' => 'Preparacion/Empaque', 'ruta' => '/almacen/preparacion_empaque', 'permiso' => 'almacen.ver'),
            array('titulo' => 'Etiquetado', 'ruta' => '/almacen/etiquetado', 'permiso' => 'almacen.ver'),
            array('titulo' => 'Configuracion', 'ruta' => '/almacen/configuracion', 'permiso' => 'almacen.ubicaciones')
        )
    ),
    array(
        'seccion' => 'ERP',
        'titulo' => 'Inventario',
        'icono' => 'bi-clipboard-data',
        'permiso' => 'inventario.ver',
        'items' => array(
            array('titulo' => 'Existencias', 'ruta' => '/inventario/productos_existencias', 'permiso' => 'inventario.ver'),
            array('titulo' => 'Ajuste de inventario', 'ruta' => '/inventario/inicial', 'permiso' => 'inventario.ajustar'),
            array('titulo' => 'Traspaso entre almacenes', 'ruta' => '/inventario/transpaso', 'permiso' => 'inventario.traspasar')
        )
    ),
    array(
        'seccion' => 'POS',
        'titulo' => 'Ventas',
        'icono' => 'bi-receipt',
        'permiso' => 'ventas.ver',
        'items' => array(
            array('titulo' => 'Tablero de ventas', 'ruta' => '/ventas/mostrar', 'permiso' => 'ventas.ver'),
            array('titulo' => 'POS', 'ruta' => '/ventas/pos', 'permiso' => 'ventas.operar'),
            array('titulo' => 'Checador de precios', 'ruta' => '/ventas/checador_precios', 'permiso' => 'ventas.ver'),
            array('titulo' => 'Pedidos', 'ruta' => '/ventas/pedidos', 'permiso' => 'ventas.ver'),
            array('titulo' => 'Devoluciones', 'ruta' => '/ventas/devoluciones', 'permiso' => 'ventas.ver'),
            array('titulo' => 'Caja y turnos', 'ruta' => '/ventas/caja_turnos', 'permiso' => 'ventas.ver'),
            array('titulo' => 'Movimientos caja', 'ruta' => '/ventas/caja_movimientos', 'permiso' => 'ventas.ver'),
            array('titulo' => 'Evidencias caja', 'ruta' => '/ventas/caja_evidencias', 'permiso' => 'ventas.ver'),
            array('titulo' => 'Configuracion POS', 'ruta' => '/ventas/pos_configuracion', 'permiso' => 'ventas.pos_config.ver')
        )
    ),
    array(
        'seccion' => 'CRM',
        'titulo' => 'CRM',
        'icono' => 'bi-person-vcard',
        'permiso' => 'crm.ver',
        'items' => array(
            array('titulo' => 'Clientes', 'ruta' => '/crm/clientes#crm_tab_clientes', 'permiso' => 'crm.ver'),
            array('titulo' => 'Seguimiento', 'ruta' => '/crm/seguimiento', 'permiso' => 'crm.ver'),
            array('titulo' => 'Comercial', 'ruta' => '/crm/clientes#crm_tab_comercial', 'permiso' => 'crm.ver'),
            array('titulo' => 'Recompensas', 'ruta' => '/crm/recompensas', 'permiso' => 'crm.ver'),
            array('titulo' => 'Auditoria', 'ruta' => '/crm/clientes#crm_tab_auditoria', 'permiso' => 'crm.ver')
        )
    ),
    array(
        'seccion' => 'Postventa',
        'titulo' => 'Garantias',
        'icono' => 'bi-shield-check',
        'permiso' => 'garantias.ver',
        'items' => array(
            array('titulo' => 'Politicas y reglas', 'ruta' => '/garantias/politicas', 'permiso' => 'garantias.ver')
        )
    ),
    array(
        'seccion' => 'Ecommerce',
        'titulo' => 'Ecommerce',
        'icono' => 'bi-shop',
        'permiso' => 'ecommerce.ver',
        'items' => array(
            array('titulo' => 'Catalogo ecommerce', 'ruta' => '/producto/catalogo', 'permiso' => 'ecommerce.ver')
        )
    )
);
?>

<div id="kt_app_sidebar" class="app-sidebar flex-column" data-kt-drawer="true" data-kt-drawer-name="app-sidebar" data-kt-drawer-activate="{default: true, lg: false}" data-kt-drawer-overlay="true" data-kt-drawer-width="225px" data-kt-drawer-direction="start" data-kt-drawer-toggle="#kt_app_sidebar_mobile_toggle">
    <div class="app-sidebar-logo px-6" id="kt_app_sidebar_logo">
        <a href="/">
            <img alt="ERP" src="assets/media/logos/default-dark.svg" class="h-25px app-sidebar-logo-default" />
            <img alt="ERP" src="assets/media/logos/default-small.svg" class="h-20px app-sidebar-logo-minimize" />
        </a>
        <div id="kt_app_sidebar_toggle" class="app-sidebar-toggle btn btn-icon btn-shadow btn-sm btn-color-muted btn-active-color-primary body-bg h-30px w-30px position-absolute top-50 start-100 translate-middle rotate" data-kt-toggle="true" data-kt-toggle-state="active" data-kt-toggle-target="body" data-kt-toggle-name="app-sidebar-minimize">
            <i class="bi bi-chevron-double-left fs-4"></i>
        </div>
    </div>

    <div class="app-sidebar-menu overflow-hidden flex-column-fluid">
        <div id="kt_app_sidebar_menu_wrapper" class="app-sidebar-wrapper hover-scroll-overlay-y my-5" data-kt-scroll="true" data-kt-scroll-activate="true" data-kt-scroll-height="auto" data-kt-scroll-dependencies="#kt_app_sidebar_logo, #kt_app_sidebar_footer" data-kt-scroll-wrappers="#kt_app_sidebar_menu" data-kt-scroll-offset="5px" data-kt-scroll-save-state="true">
            <div class="menu menu-column menu-rounded menu-sub-indention px-3" id="kt_app_sidebar_menu" data-kt-menu="true" data-kt-menu-expand="false">
                <div class="menu-item">
                    <a class="menu-link" href="/">
                        <span class="menu-icon"><i class="bi bi-speedometer2 fs-3"></i></span>
                        <span class="menu-title">Resumen</span>
                    </a>
                </div>

                <?php $seccionMenuActual = ''; ?>
                <?php foreach ($gruposMenu as $grupo): ?>
                    <?php
                    $itemsVisibles = array_values(array_filter($grupo['items'], function ($item) use ($puede) {
                        return $puede($item['permiso']);
                    }));
                    if (!$puede($grupo['permiso']) || empty($itemsVisibles)) {
                        continue;
                    }
                    $seccionGrupo = isset($grupo['seccion']) ? $grupo['seccion'] : 'Operacion';
                    ?>
                    <?php if ($seccionGrupo !== $seccionMenuActual): ?>
                        <?php $seccionMenuActual = $seccionGrupo; ?>
                        <div class="menu-item pt-5">
                            <div class="menu-content"><span class="menu-heading fw-bold text-uppercase fs-7"><?= htmlspecialchars($seccionGrupo) ?></span></div>
                        </div>
                    <?php endif; ?>
                    <div data-kt-menu-trigger="click" class="menu-item menu-accordion">
                        <span class="menu-link">
                            <span class="menu-icon"><i class="bi <?= htmlspecialchars($grupo['icono']) ?> fs-3"></i></span>
                            <span class="menu-title"><?= htmlspecialchars($grupo['titulo']) ?></span>
                            <span class="menu-arrow"></span>
                        </span>
                        <div class="menu-sub menu-sub-accordion">
                            <?php foreach ($itemsVisibles as $item): ?>
                                <div class="menu-item">
                                    <a class="menu-link" href="<?= htmlspecialchars($item['ruta']) ?>">
                                        <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                        <span class="menu-title"><?= htmlspecialchars($item['titulo']) ?></span>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <?php if ($puede('seguridad.ver')): ?>
                    <div class="menu-item pt-5">
                        <div class="menu-content"><span class="menu-heading fw-bold text-uppercase fs-7">Administracion</span></div>
                    </div>
                    <div class="menu-item">
                        <a class="menu-link" href="/sistema/seguridad">
                            <span class="menu-icon"><i class="bi bi-shield-lock fs-3"></i></span>
                            <span class="menu-title">Usuarios y roles</span>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="app-sidebar-footer flex-column-auto pt-2 pb-6 px-6" id="kt_app_sidebar_footer">
        <a href="/autenticacion/cerrar_session" class="btn btn-flex flex-center btn-custom btn-light-danger overflow-hidden text-nowrap px-0 h-40px w-100">
            <i class="bi bi-box-arrow-right fs-3 me-2"></i>
            <span class="btn-label">Cerrar sesion</span>
        </a>
    </div>
</div>

<div class="modal fade" id="erp_session_modal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered mw-450px">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h2 class="mb-1">Sesion pausada</h2>
                    <div class="text-muted">Vuelve a autenticarte para continuar sin perder tu trabajo.</div>
                </div>
            </div>
            <div class="modal-body pt-6">
                <form id="erp_session_form" autocomplete="off">
                    <div class="fv-row mb-5">
                        <label class="form-label">Celular</label>
                        <input class="form-control form-control-solid" type="text" name="celular" inputmode="numeric" required>
                    </div>
                    <div class="fv-row mb-5">
                        <label class="form-label">Contrasena</label>
                        <input class="form-control form-control-solid" type="password" name="contrasenia" required>
                    </div>
                    <div class="alert alert-danger d-none" id="erp_session_error"></div>
                    <button type="submit" class="btn btn-primary w-100" id="erp_session_submit">
                        <span class="indicator-label">Reactivar sesion</span>
                        <span class="indicator-progress">Validando... <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
window.ERP_CSRF_TOKEN = <?= json_encode(SesionSeguridad::csrfToken()) ?>;
</script>
<script src="/assets/js/custom/security/session-guard.js"></script>
<script src="/assets/js/custom/apps/erp/notificaciones/notificaciones.js?v=20260616-1"></script>
