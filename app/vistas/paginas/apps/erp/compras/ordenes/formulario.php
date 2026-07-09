<?php
$idOrden = isset($datos["id_orden_compra"]) ? intval($datos["id_orden_compra"]) : 0;
$modo = isset($datos["modo"]) ? $datos["modo"] : "editar";
$puedeAprobar = !empty($datos["puede_aprobar"]);
$puedeCancelar = !empty($datos["puede_cancelar"]);
$puedeVerFinanzas = !empty($datos["puede_ver_finanzas"]);
$puedeOperarFinanzas = !empty($datos["puede_operar_finanzas"]);
$puedeGestionarAdjuntos = !empty($datos["puede_gestionar_adjuntos"]);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../../">
    <title>Orden de compra</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet">
    <link href="assets/css/style.bundle.css" rel="stylesheet">
    <style>
        .orden-cantidad-control {
            min-width: 132px;
        }
        .orden-cantidad-control .btn {
            width: 34px;
            padding-left: 0;
            padding-right: 0;
        }
        .orden-cantidad-control input,
        .orden-sin-spinner {
            -moz-appearance: textfield;
        }
        .orden-cantidad-control input::-webkit-outer-spin-button,
        .orden-cantidad-control input::-webkit-inner-spin-button,
        .orden-sin-spinner::-webkit-outer-spin-button,
        .orden-sin-spinner::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        .orden-seccion-operativa-body {
            padding: 2rem 2.25rem;
        }
        @media (max-width: 767.98px) {
            .orden-seccion-operativa-body {
                padding: 1.5rem 1rem;
            }
        }
    </style>
</head>
<body id="kt_app_body" data-kt-app-layout="dark-sidebar" data-kt-app-header-fixed="true" data-kt-app-sidebar-enabled="true" data-kt-app-sidebar-fixed="true" class="app-default">
<input type="hidden" id="orden_id" value="<?= $idOrden ?>">
<input type="hidden" id="orden_modo" value="<?= htmlspecialchars($modo) ?>">
<input type="hidden" id="orden_puede_aprobar" value="<?= $puedeAprobar ? '1' : '0' ?>">
<input type="hidden" id="orden_puede_cancelar" value="<?= $puedeCancelar ? '1' : '0' ?>">
<input type="hidden" id="orden_puede_ver_finanzas" value="<?= $puedeVerFinanzas ? '1' : '0' ?>">
<input type="hidden" id="orden_puede_operar_finanzas" value="<?= $puedeOperarFinanzas ? '1' : '0' ?>">
<input type="hidden" id="orden_puede_gestionar_adjuntos" value="<?= $puedeGestionarAdjuntos ? '1' : '0' ?>">
<div class="d-flex flex-column flex-root app-root">
    <div class="app-page flex-column flex-column-fluid">
        <?= include_once '../app/vistas/includes/header/header.php'; ?>
        <div class="app-wrapper flex-column flex-row-fluid">
            <?= include_once '../app/vistas/includes/header/sidebar.php'; ?>
            <main class="app-main flex-column flex-row-fluid">
                <div class="app-toolbar py-3 py-lg-6">
                    <div class="app-container container-fluid d-flex flex-stack">
                        <div><h1 class="page-heading text-dark fw-bold fs-3 mb-1" id="orden_titulo">Nueva orden de compra</h1><span class="text-muted" id="orden_estado_texto">Borrador</span></div>
                        <div class="d-flex gap-2">
                            <a href="/compra/mostrar_compra_ordenes" class="btn btn-light" title="Regresar"><i class="bi bi-arrow-left"></i></a>
                            <button class="btn btn-light-primary orden-edicion" id="orden_guardar">Guardar borrador</button>
                            <button class="btn btn-primary orden-edicion" id="orden_enviar">Enviar orden</button>
                            <button class="btn btn-light-info d-none" id="orden_ver_diferencias">Ver diferencias</button>
                            <button class="btn btn-light-danger d-none" id="orden_cancelar">Cancelar orden</button>
                        </div>
                    </div>
                </div>
                <div class="app-content flex-column-fluid">
                    <div class="app-container container-fluid">
                        <div class="row g-5 mb-7">
                            <div class="col-lg-4" id="orden_proveedor_selector_grupo"><label class="form-label required">Proveedor</label><select class="form-select form-select-solid" id="orden_proveedor_selector"><option value="">Seleccionar</option></select></div>
                            <div class="col-lg-4 d-none" id="orden_proveedor_grupo"><label class="form-label">Proveedor</label><input class="form-control form-control-solid" id="orden_proveedor" disabled></div>
                            <div class="col-lg-4"><label class="form-label required">Almacen de destino</label><select class="form-select form-select-solid" id="orden_almacen"><option value="">Seleccionar</option></select></div>
                            <div class="col-md-4 col-lg-2"><label class="form-label">Entrega estimada</label><input class="form-control form-control-solid" type="date" id="orden_fecha_entrega"></div>
                            <div class="col-md-4 col-lg-2"><label class="form-label">Folio proveedor</label><input class="form-control form-control-solid" id="orden_folio_proveedor" maxlength="150"></div>
                            <div class="col-md-4 col-lg-2"><label class="form-label">Moneda</label><select class="form-select form-select-solid" id="orden_moneda"><option value="MXN">MXN</option><option value="USD">USD</option><option value="EUR">EUR</option></select></div>
                            <div class="col-md-4 col-lg-2"><label class="form-label">Tipo de cambio</label><input class="form-control form-control-solid text-end orden-sin-spinner" type="number" min="0.000001" step="0.000001" id="orden_tipo_cambio" value="1"></div>
                            <div class="col-lg-4"><label class="form-label">Contacto de recepcion</label><input class="form-control form-control-solid" id="orden_contacto" maxlength="150"></div>
                            <div class="col-lg-2"><label class="form-label">Telefono</label><input class="form-control form-control-solid" id="orden_telefono" maxlength="80"></div>
                            <div class="col-lg-6"><label class="form-label">Direccion de entrega</label><input class="form-control form-control-solid" id="orden_direccion"></div>
                            <div class="col-12"><label class="form-label">Observaciones</label><textarea class="form-control form-control-solid" id="orden_observaciones" rows="2"></textarea></div>
                        </div>
                        <div class="orden-edicion mb-6" id="orden_busqueda_grupo">
                            <!-- [Codex: v2026.06.07] XML de productos se usa como carga masiva dentro de productos; no es el módulo de adjuntos -->
                            <label class="form-label">Agregar producto del proveedor</label>
                            <div class="position-relative"><i class="bi bi-search position-absolute ms-5 mt-3 fs-3"></i><input id="orden_buscar_sku" class="form-control form-control-solid ps-12" placeholder="Buscar por SKU o nombre"><div id="orden_resultados" class="position-absolute bg-white border rounded w-100 shadow-sm z-index-3 d-none" style="max-height:320px;overflow:auto"></div></div>
                            <div class="row g-2 mt-2">
                                <div class="col-lg-8"><label class="form-label">Importar conceptos desde XML</label><input class="form-control form-control-solid" type="file" id="orden_xml_archivo" accept=".xml"></div>
                                <div class="col-lg-4"><label class="form-label">&nbsp;</label><button type="button" class="btn btn-light-primary w-100" id="orden_xml_cargar"><i class="bi bi-filetype-xml me-1"></i> Cargar XML</button></div>
                            </div>
                            <div class="row g-2 mt-2">
                                <!-- [Codex GPT-5 2026-06-17] Captura rapida de cargos no inventariables: impactan total sin requerir SKU ERP ni recepcion de almacen. -->
                                <div class="col-lg-5"><label class="form-label">Cargo o servicio</label><input class="form-control form-control-solid" id="orden_cargo_concepto" maxlength="255" placeholder="Flete, maniobra, empaque, servicio"></div>
                                <div class="col-lg-3"><label class="form-label">Tipo</label><select class="form-select form-select-solid" id="orden_cargo_tipo"><option value="cargo">Cargo</option><option value="servicio">Servicio</option><option value="adicional">Adicional</option><option value="no_inventariable">No inventariable</option></select></div>
                                <div class="col-lg-2"><label class="form-label">Importe</label><input class="form-control form-control-solid text-end orden-sin-spinner" type="number" min="0" step="0.01" id="orden_cargo_importe" placeholder="0.00"></div>
                                <div class="col-lg-2"><label class="form-label">&nbsp;</label><button type="button" class="btn btn-light-info w-100" id="orden_cargo_agregar"><i class="bi bi-plus-lg me-1"></i> Agregar</button></div>
                            </div>
                            <div class="row g-2 mt-2">
                                <!-- [Codex GPT-5 2026-06-17] Producto fisico pendiente: permite guardar borrador y generar incidencia para Catalogo/Proveedores sin enviarlo todavia. -->
                                <div class="col-lg-3"><label class="form-label">SKU nuevo</label><input class="form-control form-control-solid" id="orden_producto_nuevo_sku" maxlength="120" placeholder="SKU de factura/proveedor"></div>
                                <div class="col-lg-5"><label class="form-label">Producto nuevo</label><input class="form-control form-control-solid" id="orden_producto_nuevo_nombre" maxlength="255" placeholder="Nombre o descripcion"></div>
                                <div class="col-lg-2"><label class="form-label">Costo inicial</label><input class="form-control form-control-solid text-end orden-sin-spinner" type="number" min="0" step="0.01" id="orden_producto_nuevo_costo" placeholder="0.00"></div>
                                <div class="col-lg-2"><label class="form-label">&nbsp;</label><button type="button" class="btn btn-light-warning w-100" id="orden_producto_nuevo_agregar"><i class="bi bi-plus-lg me-1"></i> Pendiente</button></div>
                            </div>
                            <div class="text-muted fs-8 mt-2">La carga de XML es una forma de alta rápida de productos; los documentos fiscales/otros se adjuntan en el bloque de adjuntos.</div>
                        </div>
                        <div class="table-responsive">
                            <div class="d-flex flex-wrap justify-content-between gap-2 mb-3">
                                <div class="form-check ms-2 orden-edicion">
                                    <input type="checkbox" class="form-check-input" id="orden_items_check_all">
                                    <label class="form-check-label" for="orden_items_check_all">Seleccionar todo</label>
                                </div>
                                <div class="d-flex flex-wrap gap-2 align-items-end">
                                    <button type="button" class="btn btn-light-danger d-none orden-edicion" id="orden_items_eliminar"><i class="bi bi-trash me-1"></i> Eliminar seleccionados</button>
                                </div>
                            </div>
                                <table class="table align-middle table-row-dashed gy-4">
                                <thead><tr class="text-muted fw-bold fs-7 text-uppercase">
                                    <th class="text-center">Item</th>
                                    <th>SKU</th><th>Producto</th><th>Tipo</th><th class="text-end">Cantidad</th>
                                    <th class="text-end">
                                        <div>Costo sin impuestos</div>
                                        <label class="form-check form-check-sm form-check-custom form-check-solid justify-content-end orden-edicion mt-1 text-none">
                                            <input class="form-check-input" type="checkbox" id="orden_modo_costo_sin_impuestos" checked>
                                            <span class="form-check-label fs-8 text-muted ms-1">Capturar</span>
                                        </label>
                                    </th>
                                    <th class="text-end">
                                        <div>Costo neto</div>
                                        <label class="form-check form-check-sm form-check-custom form-check-solid justify-content-end orden-edicion mt-1 text-none">
                                            <input class="form-check-input" type="checkbox" id="orden_modo_costo_con_impuestos">
                                            <span class="form-check-label fs-8 text-muted ms-1">Capturar</span>
                                        </label>
                                    </th>
                                    <th class="text-end">Impuesto %</th>
                                    <th class="text-end">
                                        <div>Descuento</div>
                                        <div class="input-group input-group-sm orden-edicion mt-1" style="min-width: 110px;">
                                            <input type="number" class="form-control form-control-sm text-end orden-sin-spinner" id="orden_items_descuento_masivo" min="0" max="100" step="0.01" value="0" placeholder="%">
                                            <span class="input-group-text">%</span>
                                        </div>
                                    </th><th class="text-end">Fiscal</th><th class="text-end">Total</th><th class="orden-edicion"></th>
                                </tr></thead>
                                <tbody id="orden_items"></tbody>
                                <tfoot>
                                    <tr><td colspan="11" class="text-end fw-bold">Subtotal</td><td class="text-end fw-bold" id="orden_subtotal">$0.00</td></tr>
                                    <tr><td colspan="11" class="text-end fw-bold">Impuestos</td><td class="text-end fw-bold" id="orden_impuestos">$0.00</td></tr>
                                <tr><td colspan="11" class="text-end fw-bold fs-5">Total</td><td class="text-end fw-bold fs-5" id="orden_total">$0.00</td></tr>
                                </tfoot>
                                </table>
                            </div>
                            <div class="separator my-6"></div>
                            <h2 class="fs-5 fw-bold">Pendientes de datos fiscales</h2>
                            <div class="row g-3 mb-4">
                                <div class="col-lg-2 col-md-4">
                                    <div class="card card-dashed h-100">
                                        <div class="card-body">
                                            <div class="text-muted fs-8">Partidas totales</div>
                                            <div class="fs-4 fw-bold" id="orden_resumen_productos_total">0</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-2 col-md-4">
                                    <div class="card card-dashed h-100">
                                        <div class="card-body">
                                            <div class="text-muted fs-8">Productos registrados</div>
                                            <div class="fs-4 fw-bold text-success" id="orden_resumen_productos_registrados">0</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-2 col-md-4">
                                    <div class="card card-dashed h-100">
                                        <div class="card-body">
                                            <div class="text-muted fs-8">Productos nuevos</div>
                                            <div class="fs-4 fw-bold text-warning" id="orden_resumen_productos_nuevos">0</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-2 col-md-4">
                                    <div class="card card-dashed h-100">
                                        <div class="card-body">
                                            <div class="text-muted fs-8">Datos fiscales completos</div>
                                            <div class="fs-4 fw-bold text-success" id="orden_resumen_fiscales_completos">0</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-2 col-md-4">
                                    <div class="card card-dashed h-100">
                                        <div class="card-body">
                                            <div class="text-muted fs-8">Datos fiscales pendientes</div>
                                            <div class="fs-4 fw-bold text-danger" id="orden_resumen_fiscales_pendientes">0</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table align-middle table-row-dashed gy-3">
                                    <thead><tr class="text-muted fw-bold fs-7 text-uppercase">
                                        <th>SKU</th><th>Producto</th><th>Unidad</th><th>Estado fiscal</th><th class="text-end">Acción</th>
                                    </tr></thead>
                                    <tbody id="orden_fiscales_pendientes"><tr><td colspan="5" class="text-center text-muted py-6">Sin productos pendientes de datos fiscales</td></tr></tbody>
                                </table>
                            </div>
                        </div>
                        <div id="orden_finanzas_seccion" class="orden-seccion-operativa mb-7 <?= $idOrden > 0 && $puedeVerFinanzas ? '' : 'd-none' ?>">
                            <div class="orden-seccion-operativa-body">
                            <div class="d-flex flex-column flex-lg-row justify-content-between gap-6 mb-6">
                                <div>
                                    <h2 class="fs-5 fw-bold mb-1">Pago y condiciones</h2>
                                    <span class="text-muted">Pagos y notas de credito aplicados a la orden</span>
                                </div>
                                <div class="d-flex flex-wrap gap-8">
                                    <div><span class="text-muted fs-8 d-block">Total de orden</span><span class="fw-bold fs-4" id="orden_finanzas_total">$0.00</span></div>
                                    <div><span class="text-muted fs-8 d-block">Total aplicado</span><span class="fw-bold fs-4 text-success" id="orden_finanzas_aplicado">$0.00</span></div>
                                    <div><span class="text-muted fs-8 d-block">Saldo pendiente</span><span class="fw-bold fs-4" id="orden_finanzas_saldo">$0.00</span></div>
                                </div>
                            </div>
                            <div id="orden_finanzas_pagada" class="alert alert-success d-none py-3 mb-6">
                                La orden se encuentra totalmente pagada.
                            </div>
                            <div class="<?= $puedeOperarFinanzas ? '' : 'd-none' ?>" id="orden_pago_formulario">
                                <div class="row g-4 align-items-end mb-5">
                                    <div class="col-md-3"><label class="form-label">Metodo de pago</label><select class="form-select form-select-solid" id="orden_pago_metodo"><option value="tarjeta_debito">Tarjeta de debito</option><option value="tarjeta_credito">Tarjeta de credito</option><option value="transferencia">Transferencia electronica de fondos</option><option value="efectivo">Efectivo</option></select></div>
                                    <div class="col-md-2"><label class="form-label">Estado</label><select class="form-select form-select-solid" id="orden_pago_estado"><option value="aplicado">Aplicado</option><option value="pendiente">Pendiente</option><option value="conciliado">Conciliado</option></select></div>
                                    <div class="col-md-2"><label class="form-label">Monto</label><input class="form-control form-control-solid text-end orden-sin-spinner" type="number" min="0.01" step="0.01" id="orden_pago_monto"></div>
                                    <div class="col-md-2"><label class="form-label">Fecha</label><input class="form-control form-control-solid" type="date" id="orden_pago_fecha"></div>
                                    <div class="col-md-3"><label class="form-label required">Folio o referencia</label><input class="form-control form-control-solid" id="orden_pago_referencia" maxlength="150"></div>
                                    <div class="col-md-9"><label class="form-label">Observaciones</label><input class="form-control form-control-solid" id="orden_pago_observaciones"></div>
                                    <div class="col-md-3"><button type="button" class="btn btn-light-primary w-100" id="orden_pago_agregar"><i class="bi bi-plus-lg"></i> Agregar pago</button></div>
                                </div>
                            </div>
                            <div class="table-responsive mb-8">
                                <table class="table align-middle table-row-dashed gy-3">
                                    <thead><tr class="text-muted fw-bold fs-7 text-uppercase"><th>Metodo</th><th>Estado</th><th>Referencia</th><th>Fecha</th><th>Observaciones</th><th class="text-end">Monto</th><th></th></tr></thead>
                                    <tbody id="orden_pagos"><tr><td colspan="7" class="text-center text-muted py-6">Sin pagos registrados</td></tr></tbody>
                                </table>
                            </div>
                            <div class="<?= $puedeOperarFinanzas ? '' : 'd-none' ?>" id="orden_nota_formulario">
                                <div class="row g-4 align-items-end mb-5">
                                    <div class="col-md-2"><label class="form-label">Estado</label><select class="form-select form-select-solid" id="orden_nota_estado"><option value="aplicada">Aplicada</option><option value="pendiente">Pendiente</option></select></div>
                                    <div class="col-md-2"><label class="form-label">Monto</label><input class="form-control form-control-solid text-end orden-sin-spinner" type="number" min="0.01" step="0.01" id="orden_nota_monto"></div>
                                    <div class="col-md-2"><label class="form-label">Fecha</label><input class="form-control form-control-solid" type="date" id="orden_nota_fecha"></div>
                                    <div class="col-md-3"><label class="form-label required">Folio o referencia</label><input class="form-control form-control-solid" id="orden_nota_referencia" maxlength="150"></div>
                                    <div class="col-md-3"><label class="form-label">Observaciones</label><input class="form-control form-control-solid" id="orden_nota_observaciones"></div>
                                    <div class="col-md-3 ms-auto"><button type="button" class="btn btn-light-primary w-100" id="orden_nota_agregar"><i class="bi bi-plus-lg"></i> Agregar nota de credito</button></div>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table align-middle table-row-dashed gy-3">
                                    <thead><tr class="text-muted fw-bold fs-7 text-uppercase"><th>Referencia</th><th>Estado</th><th>Fecha</th><th>Observaciones</th><th class="text-end">Monto</th><th></th></tr></thead>
                                    <tbody id="orden_notas_credito"><tr><td colspan="6" class="text-center text-muted py-6">Sin notas de credito</td></tr></tbody>
                                </table>
                            </div>
                            </div>
                        </div>
                        <div id="orden_adjuntos_seccion" class="orden-seccion-operativa mb-7 <?= $idOrden > 0 ? '' : 'd-none' ?>">
                            <div class="orden-seccion-operativa-body">
                            <div class="d-flex flex-column flex-md-row align-items-md-end justify-content-between gap-4 mb-5">
                                <div>
                                    <h2 class="fs-5 fw-bold mb-1">Adjuntos</h2>
                                    <span class="text-muted">Cotizaciones, facturas, comprobantes, capturas y documentos relacionados</span>
                                </div>
                            </div>
                            <div id="orden_adjunto_formulario" class="<?= $modo === 'editar' && $puedeGestionarAdjuntos ? '' : 'd-none' ?>">
                                <div class="row g-4 align-items-end mb-5">
                                    <div class="col-md-3"><label class="form-label">Tipo de documento</label><select class="form-select form-select-solid" id="orden_adjunto_tipo"><option value="cotizacion">Cotizacion</option><option value="factura">Factura</option><option value="comprobante_pago">Comprobante de pago</option><option value="nota_credito">Nota de credito</option><option value="orden_firmada">Orden firmada</option><option value="otro">Otro</option></select></div>
                                    <div class="col-md-3"><label class="form-label">Folio o referencia</label><input class="form-control form-control-solid" id="orden_adjunto_referencia" maxlength="150"></div>
                                    <div class="col-md-6"><label class="form-label">Archivo</label><input class="form-control form-control-solid" type="file" id="orden_adjunto_archivo" accept=".pdf,.xml,.txt,.csv,.jpg,.jpeg,.png,.webp,.zip,.doc,.docx,.xls,.xlsx"></div>
                                    <div class="col-md-9"><label class="form-label">Observaciones</label><input class="form-control form-control-solid" id="orden_adjunto_observaciones"></div>
                                    <div class="col-md-3"><button type="button" class="btn btn-light-primary w-100" id="orden_adjunto_subir"><i class="bi bi-paperclip"></i> Adjuntar archivo</button></div>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table align-middle table-row-dashed gy-3">
                                    <thead><tr class="text-muted fw-bold fs-7 text-uppercase"><th>Documento</th><th>Referencia</th><th>Archivo</th><th>Observaciones</th><th>Fecha</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead>
                                    <tbody id="orden_adjuntos"><tr><td colspan="7" class="text-center text-muted py-6">Sin archivos adjuntos</td></tr></tbody>
                                </table>
                            </div>
                            </div>
                        </div>
                        <div id="orden_xml_seccion" class="orden-seccion-operativa mb-7 <?= $idOrden > 0 ? '' : 'd-none' ?>">
                            <div class="orden-seccion-operativa-body">
                            <div class="d-flex flex-column flex-md-row align-items-md-end justify-content-between gap-4 mb-5">
                                <div><h2 class="fs-5 fw-bold mb-1">Documentos fiscales</h2><span class="text-muted">CFDI XML conciliados contra las partidas de la orden</span></div>
                            </div>
                            <div class="table-responsive">
                                <table class="table align-middle table-row-dashed gy-3">
                                    <thead><tr class="text-muted fw-bold fs-7 text-uppercase"><th>UUID / Folio</th><th>Emisor</th><th>Conceptos</th><th>Coincidencias</th><th>Pendientes</th><th class="text-end">Total</th><th>Estado</th></tr></thead>
                                    <tbody id="orden_xml_documentos"><tr><td colspan="7" class="text-center text-muted py-6">Sin documentos fiscales</td></tr></tbody>
                                </table>
                            </div>
                            <div id="orden_conciliacion_seccion" class="d-none">
                                <div class="separator my-8"></div>
                                <div class="d-flex flex-wrap gap-8 mb-6">
                                    <div><span class="text-muted fs-8 d-block">Conceptos XML</span><span class="fw-bold fs-4" id="orden_conciliacion_total">0</span></div>
                                    <div><span class="text-muted fs-8 d-block">Coincidencias</span><span class="fw-bold fs-4 text-success" id="orden_conciliacion_coincidencias">0</span></div>
                                    <div><span class="text-muted fs-8 d-block">Requieren revision</span><span class="fw-bold fs-4 text-warning" id="orden_conciliacion_revision">0</span></div>
                                    <div><span class="text-muted fs-8 d-block">Esperados no incluidos</span><span class="fw-bold fs-4 text-danger" id="orden_conciliacion_faltantes">0</span></div>
                                </div>
                                <h2 class="fs-5 fw-bold mb-4">Conciliacion de conceptos</h2>
                                <div class="d-flex flex-wrap gap-2 mb-4">
                                    <button type="button" class="btn btn-light-primary d-none" id="orden_conciliacion_mover">
                                        <i class="bi bi-arrow-right-circle me-1"></i>Relacionar seleccionados
                                    </button>
                                    <button type="button" class="btn btn-light-danger d-none" id="orden_conciliacion_descartar">
                                        <i class="bi bi-trash me-1"></i>Descartar seleccionados
                                    </button>
                                </div>
                                <div class="table-responsive mb-8">
                                    <table class="table align-middle table-row-dashed gy-3">
                                        <thead><tr class="text-muted fw-bold fs-7 text-uppercase"><th class="text-center" style="width: 38px"><input type="checkbox" id="orden_conciliacion_check_all" title="Seleccionar todos los conceptos"></th><th>Concepto XML</th><th class="text-end">Cantidad</th><th class="text-end">Costo XML</th><th>Partida relacionada</th><th>Diferencias</th><th>Resultado</th><th class="orden-edicion"></th></tr></thead>
                                        <tbody id="orden_conciliacion_conceptos"><tr><td colspan="8" class="text-center text-muted py-6">Sin conceptos para conciliar</td></tr></tbody>
                                    </table>
                                </div>
                                <h2 class="fs-5 fw-bold mb-4">Pendientes para futuras solicitudes</h2>
                                <div class="table-responsive">
                                    <table class="table align-middle table-row-dashed gy-3">
                                        <thead><tr class="text-muted fw-bold fs-7 text-uppercase"><th>SKU</th><th>Producto</th><th class="text-end">Solicitado</th><th class="text-end">Localizado en XML</th><th>Motivo</th><th>Estado</th></tr></thead>
                                        <tbody id="orden_conciliacion_pendientes"><tr><td colspan="6" class="text-center text-muted py-6">Sin productos pendientes</td></tr></tbody>
                                    </table>
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
<div class="modal fade" id="orden_diferencias_modal" tabindex="-1" aria-labelledby="orden_diferencias_modal_titulo" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="orden_diferencias_modal_titulo">Diferencias contra solicitud de compra</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3 mb-5">
                    <div class="col-lg-4"><div class="card card-dashed"><div class="card-body">
                        <div class="text-muted fs-8">Faltantes</div>
                        <div class="fs-4 fw-bold text-warning" id="orden_dif_resumen_faltantes">0</div>
                    </div></div></div>
                    <div class="col-lg-4"><div class="card card-dashed"><div class="card-body">
                        <div class="text-muted fs-8">Adicionales</div>
                        <div class="fs-4 fw-bold text-danger" id="orden_dif_resumen_adicionales">0</div>
                    </div></div></div>
                    <div class="col-lg-4"><div class="card card-dashed"><div class="card-body">
                        <div class="text-muted fs-8">Cambios</div>
                        <div class="fs-4 fw-bold text-info" id="orden_dif_resumen_cambios">0</div>
                    </div></div></div>
                </div>
                <div class="row g-4">
                    <div class="col-lg-4">
                        <h3 class="fs-6 fw-bold mb-3">No surtidas</h3>
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed gy-3">
                                <thead><tr class="text-muted fw-bold fs-7 text-uppercase"><th>SKU</th><th>Producto</th><th class="text-end">Cant.</th><th class="text-end">Costo</th></tr></thead>
                                <tbody id="orden_dif_faltantes"><tr><td colspan="4" class="text-center text-muted py-5">Sin faltantes</td></tr></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <h3 class="fs-6 fw-bold mb-3">Adicionales</h3>
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed gy-3">
                                <thead><tr class="text-muted fw-bold fs-7 text-uppercase"><th>SKU</th><th>Producto</th><th class="text-end">Cant.</th><th class="text-end">Costo</th></tr></thead>
                                <tbody id="orden_dif_adicionales"><tr><td colspan="4" class="text-center text-muted py-5">Sin adicionales</td></tr></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <h3 class="fs-6 fw-bold mb-3">Cambios</h3>
                        <div class="table-responsive">
                            <table class="table align-middle table-row-dashed gy-3">
                                <thead><tr class="text-muted fw-bold fs-7 text-uppercase"><th>SKU</th><th>Producto</th><th class="text-end">Δ Cant.</th><th class="text-end">Δ Costo</th></tr></thead>
                                <tbody id="orden_dif_cambios"><tr><td colspan="4" class="text-center text-muted py-5">Sin cambios</td></tr></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button></div>
        </div>
    </div>
</div>
<div class="modal fade" id="orden_fiscal_modal" tabindex="-1" aria-labelledby="orden_fiscal_modal_titulo" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="orden_fiscal_modal_titulo">Datos fiscales del producto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="fw-bold text-muted mb-3">Producto: <span id="orden_fiscal_producto"></span></div>
                <input type="hidden" id="orden_fiscal_item_uid">
                <div class="row g-3">
                    <div class="col-md-4"><label class="form-label">Clave SAT producto</label><input class="form-control" id="orden_fiscal_clave_sat"></div>
                    <div class="col-md-4"><label class="form-label">Clave SAT unidad</label><input class="form-control" id="orden_fiscal_clave_unidad_sat"></div>
                    <div class="col-md-4"><label class="form-label">Unidad</label><input class="form-control" id="orden_fiscal_unidad"></div>
                    <div class="col-md-6"><label class="form-label">Objeto impuesto</label><input class="form-control" id="orden_fiscal_objeto"></div>
                    <div class="col-md-6"><label class="form-label">Tipo impuesto</label><input class="form-control" id="orden_fiscal_tipo"></div>
                    <div class="col-md-4"><label class="form-label">IVA %</label><input class="form-control orden-sin-spinner" id="orden_fiscal_iva" type="number" step="0.01" min="0" max="100"></div>
                    <div class="col-md-4"><label class="form-label">IEPS %</label><input class="form-control orden-sin-spinner" id="orden_fiscal_ieps" type="number" step="0.01" min="0" max="100"></div>
                    <div class="col-md-4">
                        <div class="form-check form-switch mt-4">
                            <input class="form-check-input" type="checkbox" id="orden_fiscal_incluye_iva">
                            <label class="form-check-label" for="orden_fiscal_incluye_iva">Precio incluye IVA</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check form-switch mt-4">
                            <input class="form-check-input" type="checkbox" id="orden_fiscal_requiere_factura">
                            <label class="form-check-label" for="orden_fiscal_requiere_factura">Requiere factura</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="orden_fiscal_guardar">Guardar</button>
            </div>
        </div>
    </div>
</div>
<script src="assets/plugins/global/plugins.bundle.js"></script>
<script src="assets/js/scripts.bundle.js"></script>
<script src="/assets/js/custom/apps/erp/compras/ordenes/formulario.js?v=20260614-3"></script>
</body>
</html>
