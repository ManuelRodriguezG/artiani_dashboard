<?php
$slugArtiani = isset($datos["slug"]) ? trim((string) $datos["slug"]) : "";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../../">
    <title>Artiani - Ficha de especie</title>
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
                            <h1 class="page-heading text-dark fw-bold fs-3 mb-1" id="artiani_ficha_titulo">Ficha de especie</h1>
                            <span class="text-muted" id="artiani_ficha_subtitulo">Detalle operativo Artiani</span>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="/artiani/enciclopedia" class="btn btn-sm btn-light"><i class="bi bi-arrow-left"></i> Enciclopedia</a>
                            <a href="/artiani/especie_nueva" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> Nueva ficha</a>
                        </div>
                    </div>
                </div>
                <div class="app-content flex-column-fluid">
                    <div class="app-container container-fluid">
                        <input type="hidden" id="artiani_ficha_slug" value="<?= htmlspecialchars($slugArtiani, ENT_QUOTES, 'UTF-8') ?>">
                        <div id="artiani_ficha_contenido" class="border border-gray-300 rounded p-6">
                            <div class="text-muted">Cargando ficha Artiani...</div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</div>
<script src="assets/plugins/global/plugins.bundle.js"></script>
<script src="assets/js/scripts.bundle.js"></script>
<script src="/assets/js/custom/apps/erp/artiani/especie.js?v=20260929-1"></script>
</body>
</html>
