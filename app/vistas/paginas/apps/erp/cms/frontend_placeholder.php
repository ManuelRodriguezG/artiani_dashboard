<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../../">
    <title>CMS - Frontend <?= htmlspecialchars(isset($datos["pagina"]) ? $datos["pagina"] : "pagina", ENT_QUOTES, 'UTF-8'); ?></title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css">
    <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css">
    <!--
      Documentacion IA: Codex GPT-5, 2026-08-14.
      Proposito: reservar rutas CMS frontend por pagina mientras se implementa cada editor.
      Impacto: ordena CMS/frontend/home, categorias, producto, carrito y global sin exponer funcionalidad incompleta.
      Contrato: vista protegida; no escribe BD ni edita archivos del frontend.
    -->
</head>
<body id="kt_app_body" data-kt-app-layout="dark-sidebar" data-kt-app-header-fixed="true" data-kt-app-sidebar-enabled="true" data-kt-app-sidebar-fixed="true" class="app-default">
<div class="d-flex flex-column flex-root app-root" id="kt_app_root">
    <div class="app-page flex-column flex-column-fluid" id="kt_app_page">
        <?= include_once '../app/vistas/includes/header/header.php'; ?>
        <div class="app-wrapper flex-column flex-row-fluid" id="kt_app_wrapper">
            <?= include_once '../app/vistas/includes/header/sidebar.php'; ?>
            <div class="app-main flex-column flex-row-fluid" id="kt_app_main">
                <div class="d-flex flex-column flex-column-fluid">
                    <div class="app-toolbar py-3 py-lg-5">
                        <div class="app-container container-fluid d-flex flex-stack flex-wrap gap-3">
                            <div>
                                <h1 class="page-heading text-dark fw-bold fs-3 mb-1">CMS / Frontend / <?= htmlspecialchars(ucfirst((string) ($datos["pagina"] ?? "pagina")), ENT_QUOTES, 'UTF-8'); ?></h1>
                                <span class="text-muted">Editor preparado para trabajar esta pagina despues de cerrar Home.</span>
                            </div>
                            <a class="btn btn-light" href="/cms/frontend/home"><i class="bi bi-house"></i> Volver a Home</a>
                        </div>
                    </div>
                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <div class="card">
                                <div class="card-body p-8">
                                    <div class="badge badge-light-info mb-4">Pendiente de editor especifico</div>
                                    <h2 class="fw-bold mb-3">Seccion reservada</h2>
                                    <p class="text-muted mb-0">Esta ruta ya existe para ordenar el CMS por paginas reales del frontend. La siguiente fase implementara sus campos, media y contrato JSON especifico.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="assets/plugins/global/plugins.bundle.js"></script>
<script src="assets/js/scripts.bundle.js"></script>
</body>
</html>
