<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../">
    <title>Checador de precios POS</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css">
    <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css">
    <!--
      Documentacion IA: Codex GPT-5, 2026-07-10.
      Proposito: checador read-only de precio/disponibilidad para POS y celular.
      Impacto: consulta Catalogo/Inventario/Ventas sin cobrar, reservar ni mover inventario.
      Contrato: la camara es ayuda de captura; el backend resuelve la informacion mostrada.
    -->
    <style>
        .checker-shell { min-height: calc(100vh - 180px); }
        .checker-search { border: 1px solid #e6e8ee; border-radius: 8px; background: #f8f9fb; }
        .checker-product { border: 1px solid #e7e9ef; border-radius: 8px; background: #fff; overflow: hidden; }
        .checker-product-img { width: 100%; aspect-ratio: 4 / 3; object-fit: cover; background: #f1f3f6; }
        .checker-price { font-size: clamp(2.1rem, 6vw, 4.4rem); line-height: 1; font-weight: 800; letter-spacing: 0; color: #181c32; }
        .checker-status { border-radius: 8px; padding: 12px 14px; }
        .checker-camera { width: 100%; min-height: 260px; max-height: 420px; border-radius: 8px; background: #111827; object-fit: contain; }
        .checker-hit { border: 1px solid #e7e9ef; border-radius: 8px; background: #fff; padding: 10px 12px; cursor: pointer; }
        .checker-hit:hover { border-color: #1b84ff; background: #f1f7ff; }
        .checker-meta { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; }
        @media (max-width: 767px) {
            .checker-meta { grid-template-columns: 1fr; }
        }
    </style>
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
                                <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Checador de precios</h1>
                                <span class="text-muted">Consulta rapida de precio, imagen y disponibilidad. No cobra ni aparta inventario.</span>
                            </div>
                            <div class="d-flex gap-2">
                                <a class="btn btn-light-primary" href="/ventas/pos"><i class="bi bi-cash-register"></i> POS</a>
                                <button class="btn btn-primary" id="checker_camera_btn" type="button"><i class="bi bi-camera"></i> Camara</button>
                            </div>
                        </div>
                    </div>
                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid checker-shell">
                            <div class="checker-search p-4 mb-4">
                                <div class="row g-3 align-items-end">
                                    <div class="col-lg-4">
                                        <label class="form-label text-muted fs-8 text-uppercase">Tienda / almacen</label>
                                        <select class="form-select form-select-solid" id="checker_almacen"></select>
                                    </div>
                                    <div class="col-lg-8">
                                        <label class="form-label text-muted fs-8 text-uppercase">Buscar o escanear</label>
                                        <div class="input-group input-group-lg">
                                            <span class="input-group-text"><i class="bi bi-upc-scan"></i></span>
                                            <input class="form-control" id="checker_q" autocomplete="off" placeholder="SKU, codigo de barras, etiqueta o producto">
                                            <button class="btn btn-primary" id="checker_buscar" type="button">Consultar</button>
                                        </div>
                                        <div class="text-muted fs-8 mt-2" id="checker_estado">Listo para consultar.</div>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-4">
                                <div class="col-xl-8">
                                    <div id="checker_resultado">
                                        <div class="checker-product p-8 text-center text-muted">
                                            <i class="bi bi-search fs-1 d-block mb-3"></i>
                                            <div class="fw-semibold">Escanea o busca un producto</div>
                                            <div class="fs-7">La informacion se revalida en POS al cobrar.</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xl-4">
                                    <div class="card mb-4">
                                        <div class="card-header border-0 pt-5">
                                            <h3 class="card-title fw-bold">Camara</h3>
                                        </div>
                                        <div class="card-body pt-0">
                                            <video class="checker-camera d-none" id="checker_video" playsinline muted></video>
                                            <div class="alert alert-info py-3 mb-3" id="checker_camera_estado">En celular, la camara puede requerir HTTPS o navegador compatible.</div>
                                            <div class="d-flex flex-wrap gap-2">
                                                <button class="btn btn-light-primary flex-grow-1 d-none" id="checker_camera_focus" type="button"><i class="bi bi-bullseye"></i> Mejorar enfoque</button>
                                                <button class="btn btn-light-warning flex-grow-1 d-none" id="checker_camera_torch" type="button"><i class="bi bi-lightbulb"></i> Luz</button>
                                                <button class="btn btn-light-danger flex-grow-1 d-none" id="checker_camera_stop" type="button"><i class="bi bi-stop-circle"></i> Detener camara</button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card">
                                        <div class="card-header border-0 pt-5">
                                            <h3 class="card-title fw-bold">Coincidencias</h3>
                                        </div>
                                        <div class="card-body pt-0" id="checker_coincidencias">
                                            <div class="text-muted fs-7">Sin busqueda todavia.</div>
                                        </div>
                                    </div>
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
<script src="/assets/js/custom/apps/erp/ventas/checador_precios.js?v=20260711-camera1"></script>
</body>
</html>
