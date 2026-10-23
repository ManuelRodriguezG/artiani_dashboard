<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../">
    <title>Cotizaciones</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css">
    <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css">
    <!-- IA: Codex GPT-5 | 2026-10-23 | Consola independiente de cotizaciones comerciales. -->
    <style>
        .qc-card { border: 1px solid #e6e8ee; border-radius: 10px; background: #fff; }
        .qc-list { max-height: 430px; overflow: auto; }
        .qc-list-row { cursor: pointer; border-bottom: 1px solid #edf0f5; }
        .qc-list-row:hover, .qc-list-row.active { background: #f2f7ff; }
        .qc-empty { min-height: 130px; border: 1px dashed #d7dbe4; border-radius: 8px; }
        .qc-editor { border: 1px solid #b9d9da; border-left: 4px solid #0b7376; border-radius: 8px; background: #f7fbfb; }
        .qc-results { max-height: 190px; overflow: auto; }
        .qc-result { cursor: pointer; border: 1px solid #e6e8ee; border-radius: 6px; background: #fff; padding: 8px 10px; }
        .qc-result:hover { border-color: #0b7376; background: #f0fafa; }
        .qc-line-image, .qc-preview-product-image { width: 46px; height: 46px; object-fit: contain; border: 1px solid #e6e8ee; border-radius: 6px; background: #fff; padding: 3px; }
        .qc-line-image-empty { display: inline-flex; width: 46px; height: 46px; align-items: center; justify-content: center; color: #aab3bf; border: 1px dashed #d7dbe4; border-radius: 6px; }
        .qc-logo-preview { max-width: 220px; max-height: 72px; object-fit: contain; border: 1px solid #e6e8ee; border-radius: 6px; background: #fff; padding: 4px; }
        .qc-pre { white-space: pre-wrap; }
        .qc-preview { box-sizing: border-box; width: 100%; border: 1px solid #d8dee8; border-radius: 8px; background: #fff; padding: 18px; color: #202124; }
        .qc-preview-head { display: flex; justify-content: space-between; gap: 18px; padding-bottom: 12px; border-bottom: 3px solid #e9485b; }
        .qc-preview-brand { display: flex; gap: 14px; align-items: center; min-width: 0; }
        .qc-preview-brand img { max-width: 180px; max-height: 62px; object-fit: contain; }
        .qc-preview-brand strong { display: block; font-size: 15px; margin-bottom: 3px; }
        .qc-preview-brand span, .qc-preview-title small { display: block; font-size: 9px; line-height: 1.45; overflow-wrap: anywhere; }
        .qc-preview-title { text-align: right; min-width: 150px; }
        .qc-preview-title span { display: block; color: #0b7376; font-size: 10px; font-weight: 700; letter-spacing: .1em; }
        .qc-preview-title b { display: block; color: #e9485b; font-size: 22px; line-height: 1.2; }
        .qc-preview-bar { display: grid; grid-template-columns: 1fr 1fr; gap: 1px; margin-top: 10px; color: #fff; font-size: 9px; font-weight: 700; text-align: center; text-transform: uppercase; }
        .qc-preview-bar span { background: #e9485b; padding: 5px 7px; }
        .qc-preview-bar span:last-child { background: #0b7376; }
        .qc-preview-client { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; padding: 8px; border: 1px solid #d7e1e4; border-top: 0; }
        .qc-preview-client strong, .qc-preview-client span { display: block; font-size: 10px; line-height: 1.45; }
        .qc-preview-table { margin-top: 10px; border: 1px solid #d7e1e4; }
        .qc-preview-table table { table-layout: fixed; width: 100%; }
        .qc-preview-table th { background: #e9485b; color: #fff; font-size: 9px; text-transform: uppercase; }
        .qc-preview-table td { color: #202124; font-size: 10px; overflow-wrap: anywhere; vertical-align: top; }
        .qc-preview-table td small { display: block; color: #667085; font-size: 8px; }
        .qc-preview-bottom { display: grid; grid-template-columns: 1fr 250px; gap: 16px; margin-top: 12px; align-items: start; }
        .qc-preview-label { color: #0b7376; font-size: 9px; font-weight: 700; text-transform: uppercase; }
        .qc-preview-total { border-top: 2px solid #0b7376; }
        .qc-preview-total > div { display: flex; justify-content: space-between; gap: 10px; padding: 6px 8px; border-bottom: 1px solid #e5ebed; font-size: 10px; }
        .qc-preview-total-final { background: #e9485b; color: #fff; font-size: 11px !important; }
        .qc-preview-total-final strong { font-size: 14px; }
        .qc-preview-footer { margin-top: 14px; padding: 6px; background: #0b7376; color: #fff; text-align: center; font-size: 8px; }
        @media (max-width: 768px) { .qc-preview-head, .qc-preview-bottom { display: block; } .qc-preview-title { text-align: left; margin-top: 12px; } .qc-preview-total { margin-top: 12px; } }
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
                            <div><h1 class="page-heading text-dark fw-bold fs-3 mb-1">Cotizaciones</h1><span class="text-muted">Captura, presentación y seguimiento de propuestas comerciales</span></div>
                            <div class="d-flex gap-2"><button type="button" class="btn btn-light-primary" id="qc_config_toggle"><i class="bi bi-palette"></i> Diseño y datos</button><a class="btn btn-light" href="/ventas/facturacion_cotizaciones"><i class="bi bi-file-earmark-text"></i> Facturación documental</a></div>
                        </div>
                    </div>
                    <div class="app-content flex-column-fluid"><div class="app-container container-fluid"><div id="qc_alerta" class="mb-4"></div>
                        <div id="qc_config_panel" class="qc-card p-4 mb-4 d-none">
                            <div class="d-flex justify-content-between align-items-start gap-3 mb-3"><div><div class="fw-bold fs-5">Identidad de la cotización</div><div class="text-muted fs-7">Estos datos y logo son exclusivos de la cotización y no cambian la factura fiscal.</div></div><button type="button" class="btn btn-sm btn-light" id="qc_config_cerrar">Cerrar</button></div>
                            <div class="row g-3"><div class="col-md-4"><label class="form-label text-muted fs-8 text-uppercase">Nombre comercial</label><input class="form-control form-control-solid" id="qc_cfg_nombre" maxlength="180"></div><div class="col-md-4"><label class="form-label text-muted fs-8 text-uppercase">RFC</label><input class="form-control form-control-solid" id="qc_cfg_rfc" maxlength="20"></div><div class="col-md-4"><label class="form-label text-muted fs-8 text-uppercase">Teléfono</label><input class="form-control form-control-solid" id="qc_cfg_telefono" maxlength="60"></div><div class="col-md-4"><label class="form-label text-muted fs-8 text-uppercase">Correo</label><input class="form-control form-control-solid" id="qc_cfg_correo" maxlength="180"></div><div class="col-md-8"><label class="form-label text-muted fs-8 text-uppercase">Dirección o sitio web</label><input class="form-control form-control-solid" id="qc_cfg_direccion" maxlength="300"></div><div class="col-md-6"><label class="form-label text-muted fs-8 text-uppercase">Logo para cotización</label><input class="form-control form-control-solid" id="qc_cfg_logo_archivo" type="file" accept="image/png,image/jpeg,image/webp"><div class="text-muted fs-8 mt-1">Se conserva sólo en este navegador.</div></div><div class="col-md-6 d-flex align-items-center"><img id="qc_cfg_logo_preview" class="qc-logo-preview d-none" alt="Logo de cotización"></div></div>
                            <div class="d-flex justify-content-end mt-3"><button type="button" class="btn btn-primary" id="qc_config_guardar"><i class="bi bi-save"></i> Guardar identidad</button></div>
                        </div>
                        <div class="qc-card p-4 mb-4">
                            <div class="d-flex justify-content-between align-items-start gap-3 mb-3"><div><div class="fw-bold fs-5">Nueva cotización</div><div class="text-muted fs-7">Captura amplia para preparar una propuesta comercial, sin crear una venta.</div></div><span class="badge badge-light-primary">Documento comercial</span></div>
                            <div class="qc-editor p-4"><div class="row g-3"><div class="col-md-5"><label class="form-label text-muted fs-8 text-uppercase">Cliente o empresa *</label><input class="form-control form-control-solid" id="qc_nombre"></div><div class="col-md-3"><label class="form-label text-muted fs-8 text-uppercase">Teléfono</label><input class="form-control form-control-solid" id="qc_telefono"></div><div class="col-md-4"><label class="form-label text-muted fs-8 text-uppercase">Correo</label><input class="form-control form-control-solid" id="qc_correo" type="email"></div><div class="col-md-3"><label class="form-label text-muted fs-8 text-uppercase">Vigencia</label><input class="form-control form-control-solid" id="qc_vigencia" type="date"></div><div class="col-md-3"><label class="form-label text-muted fs-8 text-uppercase">Moneda</label><select class="form-select form-select-solid" id="qc_moneda"><option value="MXN">MXN - Peso mexicano</option><option value="USD">USD - Dólar estadounidense</option></select></div><div class="col-md-3"><label class="form-label text-muted fs-8 text-uppercase">Canal</label><select class="form-select form-select-solid" id="qc_canal"><option value="email">Correo</option><option value="whatsapp">WhatsApp</option><option value="telefono">Teléfono</option><option value="presencial">Presencial</option></select></div><div class="col-md-3 d-flex align-items-end"><button type="button" class="btn btn-light-primary w-100" id="qc_manual"><i class="bi bi-pencil"></i> Partida manual</button></div></div>
                                <div class="mt-4"><label class="form-label text-muted fs-8 text-uppercase">Buscar producto del catálogo</label><input class="form-control form-control-solid" id="qc_buscar" placeholder="SKU, nombre o código"><div id="qc_resultados" class="qc-results mt-2"></div></div>
                                <div class="table-responsive mt-3"><table class="table table-sm align-middle mb-0"><thead><tr class="text-muted fs-8 text-uppercase"><th style="width:60px">Imagen</th><th>Partida</th><th style="width:100px">Cantidad</th><th style="width:100px">Unidad</th><th style="width:140px">Precio unitario</th><th style="width:130px" class="text-end">Importe</th><th style="width:40px"></th></tr></thead><tbody id="qc_lineas"><tr><td colspan="7" class="text-center text-muted py-4">Agrega productos o partidas manuales.</td></tr></tbody></table></div>
                                <div class="d-flex justify-content-between align-items-center mt-3"><div class="text-muted fs-8">Los precios quedan como snapshot de esta cotización.</div><div class="fw-bold fs-5">Total estimado: <span id="qc_total">$0.00 MXN</span></div></div>
                                <div class="mt-3"><label class="form-label text-muted fs-8 text-uppercase">Observaciones y condiciones</label><textarea class="form-control form-control-solid" id="qc_notas" rows="3" maxlength="1000" placeholder="Vigencia, entrega, condiciones o notas para el cliente"></textarea></div>
                                <div class="d-flex justify-content-end mt-4"><button type="button" class="btn btn-primary" id="qc_guardar"><i class="bi bi-save"></i> Guardar cotización</button></div>
                            </div>
                        </div>
                        <div class="row g-4"><div class="col-xl-4"><div class="qc-card p-4"><div class="d-flex justify-content-between align-items-center mb-3"><div><div class="fw-bold fs-5">Cotizaciones guardadas</div><div class="text-muted fs-7">Selecciona una para verla o imprimirla</div></div><button type="button" class="btn btn-sm btn-light-primary" id="qc_recargar"><i class="bi bi-arrow-clockwise"></i></button></div><div class="row g-2 mb-3"><div class="col-7"><input class="form-control form-control-sm form-control-solid" id="qc_filtro" placeholder="Folio o cliente"></div><div class="col-5"><select class="form-select form-select-sm form-select-solid" id="qc_estatus"><option value="">Todas</option><option value="borrador">Borrador</option><option value="seguimiento">Seguimiento</option><option value="aceptada">Aceptada</option><option value="descartada">Descartada</option></select></div></div><div id="qc_lista" class="qc-list"></div></div></div><div class="col-xl-8"><div class="qc-card p-4"><div class="d-flex justify-content-between align-items-start gap-3 mb-4"><div><div class="fw-bold fs-5">Vista previa de cotización</div><div class="text-muted fs-7" id="qc_subtitulo">Selecciona una cotización</div></div><button type="button" class="btn btn-sm btn-light-primary" id="qc_imprimir" disabled><i class="bi bi-file-earmark-pdf"></i> Imprimir cotización</button></div><div id="qc_detalle" class="qc-empty d-flex align-items-center justify-content-center text-muted">Aún no hay una cotización seleccionada.</div></div></div></div>
                    </div></div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="assets/plugins/global/plugins.bundle.js"></script><script src="assets/js/scripts.bundle.js"></script><script src="/assets/js/custom/apps/erp/ventas/cotizaciones.js?v=20261023-fase1"></script>
</body>
</html>
