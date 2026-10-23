<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../">
    <title>Facturación y cotizaciones</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css">
    <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css">
    <!--
      IA: Codex GPT-5
      Fecha: 2026-10-22
      Proposito: concentrar cotizaciones, CFDI externos y plantilla de correo en una consola inicial de Ventas.
      Impacto: captura y consulta comercial, conserva CFDI externos y prepara plantilla documental; no emite CFDI.
      Contrato: las cotizaciones se consultan desde snapshots y pueden capturarse desde Ventas; el XML se conserva solo al elegir Guardar en sistema.
    -->
    <style>
        .fc-card { border: 1px solid #e6e8ee; border-radius: 10px; background: #fff; }
        .fc-kpi { min-height: 86px; border: 1px solid #e6e8ee; border-radius: 10px; background: #fff; }
        .fc-list { max-height: 580px; overflow: auto; }
        .fc-list-row { cursor: pointer; border-bottom: 1px solid #edf0f5; }
        .fc-list-row:hover, .fc-list-row.active { background: #f2f7ff; }
        .fc-empty { min-height: 150px; border: 1px dashed #d7dbe4; border-radius: 8px; }
        .fc-pre { white-space: pre-wrap; min-height: 210px; }
        .fc-drop { border: 1px dashed #b8c2d1; border-radius: 8px; background: #fbfcfe; }
        .fc-table { border: 1px solid #e6e8ee; border-radius: 8px; overflow: hidden; }
        .fc-new-quote { border: 1px solid #b9d9da; border-left: 4px solid #0b7376; border-radius: 8px; padding: 12px; background: #f7fbfb; }
        .fc-search-results { max-height: 190px; overflow: auto; }
        .fc-search-result { cursor: pointer; border: 1px solid #e6e8ee; border-radius: 6px; background: #fff; padding: 7px 9px; }
        .fc-search-result:hover { border-color: #0b7376; background: #f0fafa; }
        .fc-invoice-preview { box-sizing: border-box; width: 100%; max-width: 980px; margin: 0 auto; border: 1px solid #d8dee8; border-radius: 8px; background: #fff; padding: 18px; color: #191919; }
        .fc-invoice-brand { border-bottom: 3px solid #e9485b; padding-bottom: 12px; }
        .fc-invoice-brand img { max-width: 170px; max-height: 58px; object-fit: contain; }
        .fc-invoice-meta { background: #fff; border: 1px solid #e8cfd3; border-radius: 4px; padding: 8px; }
        .fc-invoice-facts { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 8px 16px; margin: 0 0 14px; padding: 0 2px; }
        .fc-invoice-fact { background: transparent; padding: 0; min-height: 30px; }
        .fc-invoice-fact .text-muted { color: #98a2b3 !important; font-size: 9px; text-transform: uppercase; letter-spacing: .02em; }
        .fc-invoice-fact .fw-semibold { color: #475467; font-size: 10px; font-weight: 600; }
        .fc-invoice-table th { background: #e9485b; color: #fff; font-size: 10px; font-weight: 700; text-transform: uppercase; }
        .fc-invoice-table td { color: #191919; font-size: 12px; }
        .fc-invoice-table { box-sizing: border-box; width: 100%; }
        .fc-invoice-table table { width: 100%; table-layout: fixed; }
        .fc-invoice-table th:nth-child(1), .fc-invoice-table td:nth-child(1) { width: 16%; }
        .fc-invoice-table th:nth-child(2), .fc-invoice-table td:nth-child(2) { width: 34%; }
        .fc-invoice-table th:nth-child(3), .fc-invoice-table td:nth-child(3) { width: 8%; }
        .fc-invoice-table th:nth-child(4), .fc-invoice-table td:nth-child(4) { width: 10%; }
        .fc-invoice-table th:nth-child(5), .fc-invoice-table td:nth-child(5) { width: 12%; }
        .fc-invoice-table th:nth-child(6), .fc-invoice-table td:nth-child(6) { width: 10%; }
        .fc-invoice-table th:nth-child(7), .fc-invoice-table td:nth-child(7) { width: 10%; }
        .fc-invoice-table th, .fc-invoice-table td { overflow-wrap: anywhere; word-break: break-word; }
        .fc-invoice-table td { vertical-align: top; }
        .fc-invoice-total { background: #fff0f2; border-radius: 6px; border: 1px solid #f3bcc4; padding: 11px; }
        .fc-invoice-total strong { color: #c9304a; font-size: 20px; }
        .fc-tax-note { color: #0b7376; font-size: 10px; margin-bottom: 8px; }
        .fc-fiscal-box { border: 1px solid #b9d9da; border-radius: 8px; background: #f0fafa; }
        .fc-fiscal-box summary { color: #0b7376; }
        .fc-fiscal-layout { display: grid; grid-template-columns: 92px minmax(0, 1fr); gap: 12px; align-items: start; margin-top: 4px; }
        .fc-fiscal-qr { text-align: center; color: #0b7376; }
        .fc-fiscal-qr img { display: block; width: 82px; height: 82px; margin: 0 auto 3px; image-rendering: pixelated; }
        .fc-sello { word-break: break-all; font-family: monospace; font-size: 9px; line-height: 1.35; color: #193b3d; }
        @media print {
            .fc-invoice-preview { border: 0; padding: 0; max-width: none; }
            .fc-invoice-facts { grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 6px 12px; }
            .fc-fiscal-layout { grid-template-columns: 82px minmax(0, 1fr); gap: 10px; }
            .fc-fiscal-qr img { width: 74px; height: 74px; }
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
                                <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Facturación y cotizaciones</h1>
                                <span class="text-muted">Seguimiento comercial y documentos fiscales externos</span>
                            </div>
                            <div class="d-flex gap-2">
                                <div class="d-flex align-items-center gap-2 me-2"><img id="fc_logo" class="d-none rounded" style="max-height:34px;max-width:120px;object-fit:contain" alt="Logo del negocio"><span id="fc_empresa_nombre" class="text-muted fs-7"></span></div>
                                <a class="btn btn-light" href="/ventas/mostrar"><i class="bi bi-receipt"></i> Ventas</a>
                                <a class="btn btn-light-primary" href="/ventas/pedidos"><i class="bi bi-list-check"></i> Pedidos</a>
                            </div>
                        </div>
                    </div>
                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <div id="fc_alerta" class="mb-4"></div>
                            <div class="alert alert-warning d-flex align-items-start gap-3 mb-4">
                                <i class="bi bi-shield-check fs-2"></i>
                                <div>
                                    <div class="fw-bold">Alcance de esta fase</div>
                                    <div class="fs-7">El panel no emite CFDI ni convierte automaticamente cotizaciones en ventas. Puedes cargar un XML del SAT, conservarlo y generar su PDF Artiani Fiscal para adjuntarlo manualmente en Gmail.</div>
                                </div>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-3"><div class="fc-kpi p-3"><div class="text-muted fs-8 text-uppercase">Cotizaciones visibles</div><div class="fw-bold fs-3" id="fc_kpi_total">0</div></div></div>
                                <div class="col-md-3"><div class="fc-kpi p-3"><div class="text-muted fs-8 text-uppercase">En seguimiento</div><div class="fw-bold fs-3" id="fc_kpi_seguimiento">0</div></div></div>
                                <div class="col-md-3"><div class="fc-kpi p-3"><div class="text-muted fs-8 text-uppercase">Vigencia proxima</div><div class="fw-bold fs-3" id="fc_kpi_vigencia">0</div></div></div>
                                    <div class="col-md-3"><div class="fc-kpi p-3"><div class="text-muted fs-8 text-uppercase">Visualizador XML</div><div class="fw-bold fs-3" id="fc_kpi_cfdi">Activo</div></div></div>
                            </div>

                            <div class="row g-4">
                                <div class="col-xl-4">
                                    <div class="fc-card p-4 mb-4">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <div><div class="fw-bold fs-5">Cotizaciones</div><div class="text-muted fs-7">Snapshots y seguimiento comercial</div></div>
                                            <div class="d-flex gap-2"><button type="button" class="btn btn-sm btn-primary" id="fc_cot_nueva"><i class="bi bi-plus-lg"></i> Nueva cotización</button><button type="button" class="btn btn-sm btn-light-primary" id="fc_cot_recargar"><i class="bi bi-arrow-clockwise"></i></button></div>
                                        </div>
                                        <div id="fc_cot_nueva_panel" class="fc-new-quote mb-4 d-none">
                                            <div class="fw-bold mb-3">Nueva cotización</div>
                                            <div class="row g-2">
                                                <div class="col-md-6"><label class="form-label text-muted fs-8 text-uppercase">Cliente o empresa *</label><input class="form-control form-control-sm form-control-solid" id="fc_nueva_nombre" maxlength="180"></div>
                                                <div class="col-md-3"><label class="form-label text-muted fs-8 text-uppercase">Teléfono</label><input class="form-control form-control-sm form-control-solid" id="fc_nueva_telefono" maxlength="60"></div>
                                                <div class="col-md-3"><label class="form-label text-muted fs-8 text-uppercase">Correo</label><input class="form-control form-control-sm form-control-solid" id="fc_nueva_correo" type="email" maxlength="180"></div>
                                                <div class="col-md-5"><label class="form-label text-muted fs-8 text-uppercase">Vigencia</label><input class="form-control form-control-sm form-control-solid" id="fc_nueva_vigencia" type="date"></div>
                                                <div class="col-md-3"><label class="form-label text-muted fs-8 text-uppercase">Moneda</label><select class="form-select form-select-sm form-select-solid" id="fc_nueva_moneda"><option value="MXN">MXN - Peso mexicano</option><option value="USD">USD - Dólar estadounidense</option></select></div>
                                                <div class="col-md-4"><label class="form-label text-muted fs-8 text-uppercase">Canal</label><select class="form-select form-select-sm form-select-solid" id="fc_nueva_canal"><option value="email">Correo</option><option value="whatsapp">WhatsApp</option><option value="telefono">Teléfono</option><option value="presencial">Presencial</option></select></div>
                                            </div>
                                            <div class="mt-3"><label class="form-label text-muted fs-8 text-uppercase">Buscar producto del catálogo</label><input class="form-control form-control-sm form-control-solid" id="fc_nueva_buscar" placeholder="SKU, nombre o código"><div id="fc_nueva_resultados" class="fc-search-results mt-2"></div></div>
                                            <div class="fc-table mt-3"><table class="table table-sm align-middle mb-0"><thead><tr class="text-muted fs-8 text-uppercase"><th>Partida</th><th style="width:76px">Cantidad</th><th style="width:100px">Unidad</th><th style="width:110px">Precio</th><th style="width:110px" class="text-end">Importe</th><th style="width:34px"></th></tr></thead><tbody id="fc_nueva_lineas"><tr><td colspan="6" class="text-center text-muted py-3">Agrega productos o una partida manual.</td></tr></tbody></table></div>
                                            <div class="d-flex justify-content-between align-items-center mt-2"><button type="button" class="btn btn-sm btn-light" id="fc_nueva_manual"><i class="bi bi-pencil"></i> Agregar partida manual</button><div class="fw-bold">Total estimado: <span id="fc_nueva_total">$0.00 MXN</span></div></div>
                                            <div class="mt-3"><label class="form-label text-muted fs-8 text-uppercase">Observaciones</label><textarea class="form-control form-control-sm form-control-solid" id="fc_nueva_notas" rows="2" maxlength="1000" placeholder="Vigencia, entrega, condiciones o notas para el cliente"></textarea></div>
                                            <div class="d-flex justify-content-end gap-2 mt-3"><button type="button" class="btn btn-sm btn-light" id="fc_nueva_cancelar">Cancelar</button><button type="button" class="btn btn-sm btn-primary" id="fc_nueva_guardar"><i class="bi bi-save"></i> Guardar cotización</button></div>
                                        </div>
                                        <div class="row g-2 mb-3">
                                            <div class="col-7"><input class="form-control form-control-sm form-control-solid" id="fc_cot_q" placeholder="Folio o contacto"></div>
                                            <div class="col-5"><select class="form-select form-select-sm form-select-solid" id="fc_cot_estatus"><option value="">Todas</option><option value="borrador">Borrador</option><option value="recibida_whatsapp">Recibida</option><option value="nueva">Nueva</option><option value="seguimiento">Seguimiento</option><option value="en_seguimiento">En seguimiento</option><option value="aceptada">Aceptada</option><option value="convertida_pedido">Convertida a pedido</option><option value="convertida_venta">Convertida a venta</option><option value="descartada">Descartada</option><option value="cancelada">Cancelada</option></select></div>
                                        </div>
                                        <div id="fc_cot_lista" class="fc-list"></div>
                                    </div>
                                    <div class="fc-card p-4">
                                        <div class="fw-bold fs-5 mb-1">Plantilla para Gmail</div>
                                        <div class="text-muted fs-7 mb-3">Copia el asunto y el cuerpo para enviarlos desde tu cuenta de Gmail</div>
                                        <div id="fc_correo_estado" class="text-muted fs-7">Cargando plantilla...</div>
                                        <div class="mt-3"><label class="form-label text-muted fs-8 text-uppercase">Asunto</label><input class="form-control form-control-solid" id="fc_correo_asunto"></div>
                                        <div class="mt-3"><label class="form-label text-muted fs-8 text-uppercase">Cuerpo</label><textarea class="form-control form-control-solid fc-pre" id="fc_correo_cuerpo"></textarea></div>
                                        <div class="d-flex gap-2 mt-3"><button type="button" class="btn btn-light-primary flex-grow-1" id="fc_correo_copiar_asunto"><i class="bi bi-copy"></i> Copiar asunto</button><button type="button" class="btn btn-light-primary flex-grow-1" id="fc_correo_copiar_cuerpo"><i class="bi bi-copy"></i> Copiar cuerpo</button></div>
                                        <div class="text-muted fs-8 mt-3">Al enviar desde Gmail, adjunta manualmente el XML original y el PDF de la factura.</div>
                                    </div>
                                </div>

                                <div class="col-xl-8">
                                    <div class="fc-card p-4 mb-4">
                                        <div class="d-flex justify-content-between align-items-start gap-3 mb-4">
                                            <div><div class="fw-bold fs-5">Detalle de cotizacion</div><div class="text-muted fs-7" id="fc_cot_subtitulo">Selecciona una cotizacion</div></div>
                                            <div class="d-flex gap-2"><button type="button" class="btn btn-sm btn-light-primary" id="fc_cot_imprimir" disabled><i class="bi bi-file-earmark-pdf"></i> Imprimir cotización</button><button type="button" class="btn btn-sm btn-light" id="fc_cot_plan" disabled><i class="bi bi-signpost-split"></i> Planear seguimiento</button></div>
                                        </div>
                                        <div id="fc_cot_detalle" class="fc-empty d-flex align-items-center justify-content-center text-muted">Aun no hay una cotizacion seleccionada.</div>
                                    </div>

                                    <div class="fc-card p-4 mb-4">
                                        <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                                            <div><div class="fw-bold fs-5">Visualizador de factura XML</div><div class="text-muted fs-7">Convierte tu XML en una vista clara y presentable</div></div>
                                            <span class="badge badge-light-info">Solo lectura</span>
                                        </div>
                                        <form id="fc_cfdi_form" class="fc-drop p-3 mb-3">
                                            <div class="row g-3 align-items-end">
                                                <div class="col-md-7"><label class="form-label text-muted fs-8 text-uppercase">Archivo XML</label><input class="form-control form-control-solid" id="fc_cfdi_archivo" type="file" name="archivo_xml" accept=".xml,text/xml" required></div>
                                                <div class="col-md-5"><div class="d-flex gap-2"><button class="btn btn-primary flex-grow-1" type="submit"><i class="bi bi-file-earmark-code"></i> Ver factura</button><button class="btn btn-light-primary text-nowrap" type="button" id="fc_cfdi_importar" disabled><i class="bi bi-archive"></i> Guardar</button></div></div>
                                            </div>
                                        </form>
                                        <div id="fc_cfdi_resultado" class="text-muted fs-7">Selecciona un XML para ver la factura. Guardarla conserva el XML y sus conceptos en el expediente fiscal; no crea ventas, cobra ni modifica inventario.</div>
                                        <div class="border-top mt-4 pt-4"><div class="d-flex justify-content-between align-items-center mb-2"><div><div class="fw-bold">CFDI guardados</div><div class="text-muted fs-8">Selecciona uno para generar PDF o enviarlo</div></div><button type="button" class="btn btn-sm btn-light" id="fc_cfdi_recargar"><i class="bi bi-arrow-clockwise"></i></button></div><div id="fc_cfdi_lista" class="fc-list" style="max-height:240px"></div></div>
                                    </div>

                                    <div class="fc-card p-4">
                                        <div class="fw-bold fs-5 mb-1">Alcance del visualizador</div>
                                        <div class="text-muted fs-7">La vista de cotizaciones permite revisar partidas y vigencia y generar una representación formal para compartir desde Gmail. El visualizador conserva CFDI externos sin convertirlos en ventas.</div>
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
<script src="/assets/js/custom/apps/erp/ventas/facturacion_cotizaciones.js?v=20261023-fase3"></script>
</body>
</html>
