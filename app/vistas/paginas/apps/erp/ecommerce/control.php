<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../../">
    <title>Control ecommerce Artiani</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css">
    <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css">
    <!--
      Documentacion IA: Codex GPT-5, 2026-07-30.
      Proposito: panel operativo para controlar que productos se muestran en el ecommerce Artiani.
      Impacto: administra publicaciones, visibilidad y curaduria sin modificar catalogo ERP, inventario ni legacy ecom_*.
      Contrato: vista protegida; escrituras via endpoints con catalogo.editar, CSRF, token interno y auditoria.
    -->
    <style>
        .ecom-control-kpi { border: 1px solid #e7e9ef; border-radius: 8px; background: #fff; padding: 14px; min-height: 98px; }
        .ecom-control-kpi__value { font-size: 1.8rem; line-height: 1; font-weight: 800; color: #181c32; letter-spacing: 0; }
        .ecom-control-kpi__label { color: #7e8299; font-size: .78rem; text-transform: uppercase; font-weight: 700; }
        .ecom-control-img { width: 52px; height: 52px; border-radius: 8px; object-fit: cover; background: #f1f3f6; border: 1px solid #e7e9ef; }
        .ecom-chip-list { display: flex; flex-wrap: wrap; gap: 6px; }
        .ecom-drawer { border: 1px solid #e7e9ef; border-radius: 8px; background: #fff; }
        .ecom-table-scroll { max-height: 62vh; overflow: auto; }
        .ecom-sticky-head th { position: sticky; top: 0; background: #fff; z-index: 1; }
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
                                <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Control ecommerce Artiani</h1>
                                <span class="text-muted">Gobierno de publicaciones, visibilidad, disponibilidad publica y curaduria.</span>
                            </div>
                            <div class="d-flex gap-2">
                                <a class="btn btn-light-primary" href="/ecommercePublico/publicaciones"><i class="bi bi-clipboard-check"></i> Publicaciones</a>
                                <a class="btn btn-light" href="/ecommercePublico/cotizaciones"><i class="bi bi-chat-dots"></i> Cotizaciones</a>
                                <button class="btn btn-primary" type="button" id="ecom_control_recargar"><i class="bi bi-arrow-clockwise"></i> Recargar</button>
                            </div>
                        </div>
                    </div>
                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <div class="alert alert-info d-flex align-items-start gap-3">
                                <i class="bi bi-shield-check fs-2"></i>
                                <div>
                                    <div class="fw-bold">Canal actual: Artiani web / catalogo publico</div>
                                    <div>Desde aqui puedes decidir que se muestra, pausar productos, excluir granel, publicar borradores revisados y controlar si cada ficha permite precio, disponibilidad, cotizacion o WhatsApp.</div>
                                </div>
                            </div>

                            <div class="row g-4 mb-5">
                                <div class="col-md-3"><div class="ecom-control-kpi"><div class="ecom-control-kpi__label">Publicados</div><div class="ecom-control-kpi__value" id="ecom_ctl_publicados">0</div><div class="text-muted fs-7 mt-2">Visibles en API publica.</div></div></div>
                                <div class="col-md-3"><div class="ecom-control-kpi"><div class="ecom-control-kpi__label">Borradores</div><div class="ecom-control-kpi__value" id="ecom_ctl_borradores">0</div><div class="text-muted fs-7 mt-2">Listos para revisar/publicar.</div></div></div>
                                <div class="col-md-3"><div class="ecom-control-kpi"><div class="ecom-control-kpi__label">Pausados</div><div class="ecom-control-kpi__value" id="ecom_ctl_pausados">0</div><div class="text-muted fs-7 mt-2">Ocultos del ecommerce.</div></div></div>
                                <div class="col-md-3"><div class="ecom-control-kpi"><div class="ecom-control-kpi__label">Publicables</div><div class="ecom-control-kpi__value" id="ecom_ctl_publicables">0</div><div class="text-muted fs-7 mt-2">Con precio, imagen y categoria.</div></div></div>
                            </div>

                            <div class="card mb-5">
                                <div class="card-header border-0 pt-6">
                                    <div class="card-title gap-3 flex-wrap">
                                        <div class="position-relative w-300px">
                                            <i class="bi bi-search position-absolute top-50 translate-middle-y ms-4 text-muted"></i>
                                            <input class="form-control form-control-solid ps-12" id="ecom_ctl_busqueda" type="text" placeholder="SKU, nombre, marca">
                                        </div>
                                        <input class="form-control form-control-solid w-240px" id="ecom_ctl_categoria" type="text" placeholder="Categoria contiene">
                                        <select class="form-select form-select-solid w-180px" id="ecom_ctl_estatus">
                                            <option value="">Todos</option>
                                            <option value="publicado">Publicado</option>
                                            <option value="borrador">Borrador</option>
                                            <option value="pausado">Pausado</option>
                                            <option value="sin_publicacion">Sin publicacion</option>
                                        </select>
                                        <select class="form-select form-select-solid w-190px" id="ecom_ctl_disponibilidad">
                                            <option value="">Disponibilidad</option>
                                            <option value="disponible">Disponible</option>
                                            <option value="pocas_piezas">Pocas piezas</option>
                                            <option value="consultar_disponibilidad">Consultar</option>
                                            <option value="agotado">Agotado</option>
                                        </select>
                                        <select class="form-select form-select-solid w-150px" id="ecom_ctl_granel">
                                            <option value="">Granel: todos</option>
                                            <option value="0">Excluir granel</option>
                                            <option value="1">Solo granel</option>
                                        </select>
                                        <select class="form-select form-select-solid w-120px" id="ecom_ctl_limite">
                                            <option value="50">50</option>
                                            <option value="100" selected>100</option>
                                            <option value="200">200</option>
                                            <option value="500">500</option>
                                        </select>
                                    </div>
                                    <div class="card-toolbar">
                                        <span class="badge badge-light-info" id="ecom_ctl_seleccionados">0 seleccionados</span>
                                    </div>
                                </div>
                                <div class="card-body pt-0">
                                    <div class="d-flex flex-wrap align-items-center gap-2 mb-4">
                                        <button class="btn btn-sm btn-light-primary" type="button" id="ecom_ctl_lote_borrador"><i class="bi bi-file-earmark-plus"></i> Guardar borradores</button>
                                        <button class="btn btn-sm btn-success" type="button" id="ecom_ctl_lote_publicar"><i class="bi bi-eye"></i> Publicar</button>
                                        <button class="btn btn-sm btn-warning" type="button" id="ecom_ctl_lote_pausar"><i class="bi bi-eye-slash"></i> Pausar</button>
                                        <button class="btn btn-sm btn-light-success" type="button" id="ecom_ctl_lote_reactivar"><i class="bi bi-arrow-repeat"></i> Reactivar</button>
                                        <div class="form-check form-check-custom form-check-solid ms-lg-4">
                                            <input class="form-check-input" type="checkbox" id="ecom_ctl_confirmar_agotados">
                                            <label class="form-check-label fs-7" for="ecom_ctl_confirmar_agotados">Permitir publicar agotados revisados</label>
                                        </div>
                                        <span class="badge badge-light-primary ms-auto" id="ecom_ctl_estado">Listo</span>
                                    </div>
                                    <div class="row g-4">
                                        <div class="col-xl-8">
                                            <div class="table-responsive ecom-table-scroll">
                                                <table class="table align-middle table-row-dashed fs-7 gy-3">
                                                    <thead class="ecom-sticky-head">
                                                        <tr class="text-start text-muted fw-bold text-uppercase">
                                                            <th class="w-30px"><input class="form-check-input" type="checkbox" id="ecom_ctl_check_all"></th>
                                                            <th class="w-60px">Img</th>
                                                            <th>Producto</th>
                                                            <th>Categoria</th>
                                                            <th>Disp.</th>
                                                            <th>Estado</th>
                                                            <th class="text-end">Accion</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="ecom_ctl_body"></tbody>
                                                </table>
                                            </div>
                                        </div>
                                        <div class="col-xl-4">
                                            <div class="ecom-drawer p-5" id="ecom_ctl_editor">
                                                <div class="text-muted py-5 text-center">Selecciona un producto para editar su control ecommerce.</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="alert alert-warning">
                                <div class="fw-bold">Siguiente fase: canales para terceros</div>
                                <div class="fs-7">Para ofrecer el catalogo a otros sitios con permisos por persona/canal, se debe activar despues la capa `erp_ecommerce_canales_api`, credenciales, allowlist y logs. Este panel gobierna primero tu ecommerce Artiani.</div>
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
<script src="/assets/js/custom/apps/erp/ecommerce/control.js?v=20260730-control1"></script>
</body>
</html>
