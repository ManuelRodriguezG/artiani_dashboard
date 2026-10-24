<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../">
    <title>Plantillas de correo</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css">
    <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css">
    <style>
        .pc-card { border: 1px solid #e6e8ee; border-radius: 10px; background: #fff; }
        .pc-template { border-top: 3px solid #0b7376; }
        .pc-template.invoice { border-top-color: #e9485b; }
        .pc-pre { min-height: 260px; white-space: pre-wrap; resize: vertical; }
        .pc-logo { max-height: 38px; max-width: 145px; object-fit: contain; }
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
                            <div><h1 class="page-heading text-dark fw-bold fs-3 mb-1">Plantillas de correo</h1><span class="text-muted">Mensajes listos para copiar y enviar desde Gmail</span></div>
                            <div class="d-flex align-items-center gap-2"><img id="pc_logo" class="pc-logo d-none" alt="Logo del negocio"><span id="pc_empresa_nombre" class="text-muted fs-7"></span><a class="btn btn-light" href="/ventas/facturacion_cotizaciones"><i class="bi bi-file-earmark-text"></i> Facturación documental</a></div>
                        </div>
                    </div>
                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <div id="pc_alerta" class="mb-4"></div>
                            <div class="alert alert-info d-flex align-items-start gap-3 mb-4">
                                <i class="bi bi-envelope-check fs-2"></i>
                                <div><div class="fw-bold">Uso manual desde Gmail</div><div class="fs-7">El panel no envía correos. Copia el asunto y el cuerpo, pega el contenido en Gmail y adjunta los archivos correspondientes.</div></div>
                            </div>
                            <div id="pc_estado" class="text-muted fs-7 mb-3">Cargando plantillas...</div>
                            <div class="row g-4">
                                <div class="col-xl-6">
                                    <div class="pc-card pc-template invoice p-4 h-100">
                                        <div class="fw-bold fs-5 mb-1">Enviar factura al cliente</div>
                                        <div class="text-muted fs-7 mb-3">Adjunta el XML original y el PDF Artiani Fiscal.</div>
                                        <label class="form-label text-muted fs-8 text-uppercase">Asunto</label>
                                        <input class="form-control form-control-solid" id="pc_factura_asunto">
                                        <label class="form-label text-muted fs-8 text-uppercase mt-3">Cuerpo</label>
                                        <textarea class="form-control form-control-solid pc-pre" id="pc_factura_cuerpo"></textarea>
                                        <div class="d-flex gap-2 mt-3"><button type="button" class="btn btn-light-primary flex-grow-1" id="pc_factura_copiar_asunto"><i class="bi bi-copy"></i> Copiar asunto</button><button type="button" class="btn btn-light-primary flex-grow-1" id="pc_factura_copiar_cuerpo"><i class="bi bi-copy"></i> Copiar cuerpo</button></div>
                                    </div>
                                </div>
                                <div class="col-xl-6">
                                    <div class="pc-card pc-template p-4 h-100">
                                        <div class="fw-bold fs-5 mb-1">Enviar cotización al cliente</div>
                                        <div class="text-muted fs-7 mb-3">Adjunta el PDF de la propuesta comercial.</div>
                                        <label class="form-label text-muted fs-8 text-uppercase">Asunto</label>
                                        <input class="form-control form-control-solid" id="pc_cotizacion_asunto">
                                        <label class="form-label text-muted fs-8 text-uppercase mt-3">Cuerpo</label>
                                        <textarea class="form-control form-control-solid pc-pre" id="pc_cotizacion_cuerpo"></textarea>
                                        <div class="d-flex gap-2 mt-3"><button type="button" class="btn btn-light-primary flex-grow-1" id="pc_cotizacion_copiar_asunto"><i class="bi bi-copy"></i> Copiar asunto</button><button type="button" class="btn btn-light-primary flex-grow-1" id="pc_cotizacion_copiar_cuerpo"><i class="bi bi-copy"></i> Copiar cuerpo</button></div>
                                    </div>
                                </div>
                            </div>
                            <div class="pc-card p-4 mt-4">
                                <div class="fw-bold mb-2">Campos reemplazables</div>
                                <div class="text-muted fs-7">{cliente_nombre}, {folio}, {uuid}, {vigencia}, {moneda}, {total}, {empresa_nombre}, {empresa_rfc} y {correo_respuesta}.</div>
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
<script src="/assets/js/custom/apps/erp/ventas/plantillas_correo.js?v=20261023-fase1"></script>
</body>
</html>
