<?php
$puedeConfigurar = !empty($datos["puede_configurar"]);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../../">
    <title>Documentos de Compras</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet">
    <link href="assets/css/style.bundle.css" rel="stylesheet">
</head>
<body id="kt_app_body" data-kt-app-layout="dark-sidebar" data-kt-app-header-fixed="true" data-kt-app-sidebar-enabled="true" data-kt-app-sidebar-fixed="true" class="app-default">
<input type="hidden" id="compras_documentos_puede_configurar" value="<?= $puedeConfigurar ? '1' : '0' ?>">
<div class="d-flex flex-column flex-root app-root">
    <div class="app-page flex-column flex-column-fluid">
        <?= include_once '../app/vistas/includes/header/header.php'; ?>
        <div class="app-wrapper flex-column flex-row-fluid">
            <?= include_once '../app/vistas/includes/header/sidebar.php'; ?>
            <main class="app-main flex-column flex-row-fluid">
                <div class="app-toolbar py-3 py-lg-6">
                    <div class="app-container container-fluid d-flex flex-stack">
                        <div>
                            <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Documentos de Compras</h1>
                            <span class="text-muted">Plantillas para solicitudes y ordenes internas o para proveedor</span>
                        </div>
                        <a href="/compra/mostrar_solicitudes" class="btn btn-light"><i class="bi bi-arrow-left"></i> Compras</a>
                    </div>
                </div>
                <div class="app-content flex-column-fluid">
                    <div class="app-container container-fluid">
                        <div class="row g-6">
                            <div class="col-xl-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h3 class="card-title">Plantillas</h3>
                                    </div>
                                    <div class="card-body">
                                        <div id="compras_documentos_plantillas" class="d-flex flex-column gap-3"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-8">
                                <div class="card mb-6">
                                    <div class="card-header">
                                        <h3 class="card-title">Datos del negocio para documentos</h3>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-5 align-items-end">
                                            <div class="col-lg-4">
                                                <label class="form-label">Logo compartido</label>
                                                <div class="border rounded p-4 d-flex align-items-center justify-content-center" style="min-height: 92px;">
                                                    <img id="documentos_logo_preview" src="" alt="Logo" class="mw-100 mh-75px d-none">
                                                    <span id="documentos_logo_empty" class="text-muted fs-7">Sin logo cargado</span>
                                                </div>
                                            </div>
                                            <div class="col-lg-5">
                                                <label class="form-label">Cargar logo</label>
                                                <input type="file" class="form-control form-control-solid" id="documentos_logo_archivo" accept=".png,.jpg,.jpeg,.webp,.ico,image/png,image/jpeg,image/webp,image/x-icon">
                                                <div class="text-muted fs-8 mt-1">PNG, JPG, WEBP o ICO. Maximo 2 MB.</div>
                                            </div>
                                            <div class="col-lg-3">
                                                <button type="button" class="btn btn-light-primary w-100" id="documentos_logo_subir">
                                                    <i class="bi bi-upload"></i> Subir logo
                                                </button>
                                            </div>
                                        </div>

                                        <div class="separator my-8"></div>

                                        <form id="compras_documentos_negocio_form">
                                            <div class="row g-5">
                                                <div class="col-lg-6">
                                                    <label class="form-label">Nombre comercial</label>
                                                    <input class="form-control form-control-solid" id="negocio_empresa_nombre" name="empresa_nombre" maxlength="180">
                                                </div>
                                                <div class="col-lg-6">
                                                    <label class="form-label">Razon social</label>
                                                    <input class="form-control form-control-solid" id="negocio_empresa_razon_social" name="empresa_razon_social" maxlength="180">
                                                </div>
                                                <div class="col-lg-3">
                                                    <label class="form-label">RFC</label>
                                                    <input class="form-control form-control-solid" id="negocio_empresa_rfc" name="empresa_rfc" maxlength="20">
                                                </div>
                                                <div class="col-lg-3">
                                                    <label class="form-label">Contacto compras</label>
                                                    <input class="form-control form-control-solid" id="negocio_empresa_contacto" name="empresa_contacto" maxlength="120">
                                                </div>
                                                <div class="col-lg-3">
                                                    <label class="form-label">Email</label>
                                                    <input class="form-control form-control-solid" id="negocio_empresa_email" name="empresa_email" maxlength="120">
                                                </div>
                                                <div class="col-lg-3">
                                                    <label class="form-label">Telefono</label>
                                                    <input class="form-control form-control-solid" id="negocio_empresa_telefono" name="empresa_telefono" maxlength="60">
                                                </div>
                                                <div class="col-12">
                                                    <label class="form-label">Direccion visible</label>
                                                    <input class="form-control form-control-solid" id="negocio_empresa_direccion" name="empresa_direccion">
                                                </div>
                                            </div>
                                            <div class="d-flex justify-content-end mt-6">
                                                <button type="button" class="btn btn-primary" id="compras_documentos_negocio_guardar">Guardar datos del negocio</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                <div class="card">
                                    <div class="card-header">
                                        <h3 class="card-title" id="compras_documentos_titulo">Configuracion</h3>
                                    </div>
                                    <div class="card-body">
                                        <form id="compras_documentos_form" class="d-none">
                                            <input type="hidden" id="plantilla_id" name="id_plantilla_documento">
                                            <input type="hidden" id="plantilla_codigo" name="codigo">
                                            <div class="row g-5">
                                                <div class="col-lg-6">
                                                    <label class="form-label">Nombre</label>
                                                    <input class="form-control form-control-solid" id="plantilla_nombre" name="nombre" maxlength="150">
                                                </div>
                                                <div class="col-lg-6">
                                                    <label class="form-label">Titulo del documento</label>
                                                    <input class="form-control form-control-solid" id="plantilla_titulo_documento" name="titulo_documento" maxlength="150" placeholder="Solicitud de cotizacion">
                                                </div>
                                                <div class="col-lg-6">
                                                    <label class="form-label">Subtitulo para impresion</label>
                                                    <input class="form-control form-control-solid" id="plantilla_subtitulo_documento" name="subtitulo_documento" maxlength="255">
                                                </div>
                                                <div class="col-12">
                                                    <label class="form-label">Descripcion</label>
                                                    <textarea class="form-control form-control-solid" id="plantilla_descripcion" name="descripcion" rows="2"></textarea>
                                                </div>
                                            </div>

                                            <div class="separator my-8"></div>

                                            <div class="row g-4">
                                                <div class="col-md-6 col-lg-4"><label class="form-check form-switch form-check-custom form-check-solid"><input class="form-check-input" type="checkbox" id="mostrar_logo"><span class="form-check-label">Mostrar logo</span></label></div>
                                                <div class="col-md-6 col-lg-4"><label class="form-check form-switch form-check-custom form-check-solid"><input class="form-check-input" type="checkbox" id="mostrar_costos"><span class="form-check-label">Mostrar costos</span></label></div>
                                                <div class="col-md-6 col-lg-4"><label class="form-check form-switch form-check-custom form-check-solid"><input class="form-check-input" type="checkbox" id="mostrar_impuestos"><span class="form-check-label">Mostrar impuestos</span></label></div>
                                                <div class="col-md-6 col-lg-4"><label class="form-check form-switch form-check-custom form-check-solid"><input class="form-check-input" type="checkbox" id="mostrar_totales"><span class="form-check-label">Mostrar totales</span></label></div>
                                                <div class="col-md-6 col-lg-4"><label class="form-check form-switch form-check-custom form-check-solid"><input class="form-check-input" type="checkbox" id="mostrar_sku_proveedor"><span class="form-check-label">SKU proveedor</span></label></div>
                                                <div class="col-md-6 col-lg-4"><label class="form-check form-switch form-check-custom form-check-solid"><input class="form-check-input" type="checkbox" id="mostrar_sku_erp"><span class="form-check-label">SKU ERP</span></label></div>
                                                <div class="col-md-6 col-lg-4"><label class="form-check form-switch form-check-custom form-check-solid"><input class="form-check-input" type="checkbox" id="mostrar_nombre_proveedor"><span class="form-check-label">Nombre proveedor</span></label></div>
                                                <div class="col-md-6 col-lg-4"><label class="form-check form-switch form-check-custom form-check-solid"><input class="form-check-input" type="checkbox" id="mostrar_nombre_erp"><span class="form-check-label">Nombre ERP</span></label></div>
                                                <div class="col-md-6 col-lg-4"><label class="form-check form-switch form-check-custom form-check-solid"><input class="form-check-input" type="checkbox" id="mostrar_observaciones_publicas"><span class="form-check-label">Observaciones publicas</span></label></div>
                                                <div class="col-md-6 col-lg-4"><label class="form-check form-switch form-check-custom form-check-solid"><input class="form-check-input" type="checkbox" id="mostrar_observaciones_internas"><span class="form-check-label">Observaciones internas</span></label></div>
                                            </div>

                                            <div class="separator my-8"></div>

                                            <div class="row g-5">
                                                <div class="col-12">
                                                    <label class="form-label">Pie de pagina</label>
                                                    <textarea class="form-control form-control-solid" id="plantilla_pie_pagina" rows="2"></textarea>
                                                </div>
                                            </div>

                                            <div class="d-flex justify-content-end mt-8">
                                                <button type="button" class="btn btn-primary" id="compras_documentos_guardar">Guardar configuracion</button>
                                            </div>
                                        </form>
                                        <div id="compras_documentos_empty" class="text-muted">Selecciona una plantilla para configurarla.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</div>
<script src="assets/plugins/global/plugins.bundle.js"></script>
<script src="assets/js/scripts.bundle.js"></script>
<script src="/assets/js/custom/apps/erp/compras/documentos/configuracion.js?v=20260813-1"></script>
</body>
</html>
