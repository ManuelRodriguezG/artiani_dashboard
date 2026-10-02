<?php
$distSeccionActiva = isset($distSeccionActiva) ? $distSeccionActiva : 'resumen';
$distTituloActivo = isset($distTituloActivo) ? $distTituloActivo : 'Resumen';
$distContenidoVista = isset($distContenidoVista) ? $distContenidoVista : null;
$distSecciones = array(
    'resumen' => array('titulo' => 'Resumen', 'ruta' => '/DistribucionAdmin/panel_resumen'),
    'solicitudes' => array('titulo' => 'Solicitudes', 'ruta' => '/DistribucionAdmin/panel_solicitudes'),
    'clientes' => array('titulo' => 'Clientes', 'ruta' => '/DistribucionAdmin/panel_clientes'),
    'pedidos' => array('titulo' => 'Pedidos', 'ruta' => '/DistribucionAdmin/panel_pedidos'),
    'mi_catalogo' => array('titulo' => 'Mi catalogo', 'ruta' => '/DistribucionAdmin/panel_mi_catalogo'),
    'inventarios' => array('titulo' => 'Inventarios', 'ruta' => '/DistribucionAdmin/panel_inventarios'),
    'sugeridos' => array('titulo' => 'Sugeridos', 'ruta' => '/DistribucionAdmin/panel_sugeridos'),
    'productos' => array('titulo' => 'Productos', 'ruta' => '/DistribucionAdmin/panel_productos'),
    'demanda' => array('titulo' => 'Demanda', 'ruta' => '/DistribucionAdmin/panel_demanda')
);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../">
    <title>Distribucion ERP - <?= htmlspecialchars($distTituloActivo, ENT_QUOTES, 'UTF-8') ?></title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css">
    <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css">
    <style>
        .dist-kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 12px; }
        .dist-kpi { border: 1px solid var(--bs-gray-200); border-radius: 8px; padding: 14px 16px; background: var(--bs-body-bg); min-height: 96px; }
        .dist-kpi-value { font-size: 1.65rem; font-weight: 700; line-height: 1; }
        .dist-filter-row { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
        .dist-filter-row .form-control, .dist-filter-row .form-select { min-width: 170px; width: auto; }
        .dist-table-wrap { max-height: 62vh; overflow: auto; }
        .dist-table-wrap table thead th { position: sticky; top: 0; background: var(--bs-card-bg); z-index: 1; }
        .dist-order-kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px; }
        .dist-order-kpi { border: 1px solid var(--bs-gray-200); border-radius: 6px; padding: 10px 12px; background: var(--bs-body-bg); }
        .dist-order-kpi .value { font-weight: 700; font-size: 1.05rem; }
    </style>
</head>
<body id="kt_app_body" data-kt-app-layout="dark-sidebar" data-kt-app-header-fixed="true" data-kt-app-sidebar-enabled="true" data-kt-app-sidebar-fixed="true" class="app-default">
    <script>
        window.ERP_CSRF_TOKEN = "<?= htmlspecialchars(Sesionseguridad::csrfToken(), ENT_QUOTES, 'UTF-8') ?>";
        window.DISTRIBUCION_ADMIN_SECCION = "<?= htmlspecialchars($distSeccionActiva, ENT_QUOTES, 'UTF-8') ?>";
        window.DISTRIBUCION_ADMIN_PERMISOS = {
            editar: <?= Sesionseguridad::tienePermiso('distribucion.editar') ? 'true' : 'false' ?>,
            aprobar: <?= Sesionseguridad::tienePermiso('distribucion.aprobar_clientes') ? 'true' : 'false' ?>,
            asignar_precios: <?= Sesionseguridad::tienePermiso('distribucion.asignar_precios') ? 'true' : 'false' ?>,
            cotizaciones_gestionar: <?= Sesionseguridad::tienePermiso('distribucion.cotizaciones.gestionar') ? 'true' : 'false' ?>
        };
    </script>
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
                                    <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Distribucion / <?= htmlspecialchars($distTituloActivo, ENT_QUOTES, 'UTF-8') ?></h1>
                                    <span class="text-muted">Modulo interno separado por areas operativas</span>
                                </div>
                                <button type="button" id="distribucion_refrescar" class="btn btn-light-primary">
                                    <i class="bi bi-arrow-clockwise"></i>
                                    Refrescar
                                </button>
                            </div>
                        </div>
                        <div class="app-content flex-column-fluid">
                            <div class="app-container container-fluid">
                                <?php
                                if ($distContenidoVista && is_file($distContenidoVista)) {
                                    include $distContenidoVista;
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="assets/plugins/global/plugins.bundle.js"></script>
    <script src="assets/js/scripts.bundle.js"></script>
    <script src="/assets/js/custom/apps/erp/distribucion/administracion.js?v=20261001-vistas-separadas"></script>
</body>
</html>
