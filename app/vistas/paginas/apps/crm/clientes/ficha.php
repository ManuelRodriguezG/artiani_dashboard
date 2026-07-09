<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../../">
    <title>Ficha CRM Cliente</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css">
    <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css">
    <!--
      Documentacion IA: Codex GPT-5, 2026-06-29.
      Proposito: ficha operativa CRM fuera del cobro POS.
      Impacto: centraliza cliente canonico, identificadores, contactos, fiscal, notas e historial.
      Contrato: vista read-only/dry-run; escrituras reales requieren endpoints autorizados.
    -->
    <style>
        .crm-panel { border: 1px solid #e6e8ee; border-radius: 8px; background: #fff; }
        .crm-code { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono", monospace; }
        .crm-scroll { max-height: 360px; overflow: auto; }
        .crm-label { color: #7e8299; font-size: .78rem; text-transform: uppercase; font-weight: 700; }
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
                                <h1 class="page-heading text-dark fw-bold fs-3 mb-1" id="crm_ficha_titulo">Ficha CRM</h1>
                                <span class="text-muted" id="crm_ficha_subtitulo">Cliente canonico</span>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="/crm/clientes" class="btn btn-light"><i class="bi bi-arrow-left"></i> Clientes</a>
                                <button type="button" class="btn btn-light-primary" id="crm_ficha_recargar"><i class="bi bi-arrow-clockwise"></i> Actualizar</button>
                            </div>
                        </div>
                    </div>
                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <input type="hidden" id="crm_id_cliente_crm" value="<?= intval($datos['id_cliente_crm'] ?? 0); ?>">
                            <div id="crm_ficha_alerta" class="mb-4"></div>

                            <div class="row g-4">
                                <div class="col-xl-4">
                                    <div class="crm-panel p-4 mb-4">
                                        <div class="d-flex justify-content-between align-items-start mb-4">
                                            <div>
                                                <div class="crm-label">Identidad</div>
                                                <div class="fw-bold fs-4" id="crm_nombre_publico_header">-</div>
                                                <div class="text-muted crm-code" id="crm_codigo_header">-</div>
                                            </div>
                                            <span class="badge badge-light-success" id="crm_estatus_header">-</span>
                                        </div>
                                        <div class="row g-3">
                                            <div class="col-6">
                                                <div class="crm-label">Calidad</div>
                                                <div id="crm_calidad_header">-</div>
                                            </div>
                                            <div class="col-6">
                                                <div class="crm-label">Origen</div>
                                                <div id="crm_origen_header">-</div>
                                            </div>
                                            <div class="col-12">
                                                <div class="crm-label">Registro</div>
                                                <div id="crm_fecha_header">-</div>
                                            </div>
                                        </div>
                                        <div class="border-top pt-4 mt-4" id="crm_calidad_operativa"></div>
                                    </div>

                                    <div class="crm-panel p-4">
                                        <div class="fw-bold mb-3">Editar basico</div>
                                        <div class="mb-3">
                                            <label class="form-label">Nombre publico</label>
                                            <input class="form-control form-control-solid" id="crm_form_nombre" maxlength="220">
                                        </div>
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label">Tipo</label>
                                                <select class="form-select form-select-solid" id="crm_form_tipo">
                                                    <option value="persona">Persona</option>
                                                    <option value="empresa">Empresa</option>
                                                    <option value="institucion">Institucion</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Estatus</label>
                                                <select class="form-select form-select-solid" id="crm_form_estatus">
                                                    <option value="activo">Activo</option>
                                                    <option value="inactivo">Inactivo</option>
                                                    <option value="bloqueado">Bloqueado</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="mt-3">
                                            <label class="form-label">Observaciones operativas</label>
                                            <textarea class="form-control form-control-solid" id="crm_form_observaciones" rows="4"></textarea>
                                        </div>
                                        <button type="button" class="btn btn-light-primary w-100 mt-4" id="crm_validar_basico"><i class="bi bi-check2-circle"></i> Validar cambios</button>
                                        <div id="crm_form_resultado" class="mt-3"></div>
                                    </div>
                                </div>

                                <div class="col-xl-8">
                                    <ul class="nav nav-tabs nav-line-tabs nav-line-tabs-2x mb-4 fs-6">
                                        <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#crm_tab_identificadores">Identificadores</a></li>
                                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#crm_tab_contacto">Contacto</a></li>
                                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#crm_tab_fiscal">Fiscal</a></li>
                                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#crm_tab_recompensas">Recompensas</a></li>
                                        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#crm_tab_historial">Historial</a></li>
                                    </ul>
                                    <div class="tab-content">
                                        <div class="tab-pane fade show active" id="crm_tab_identificadores">
                                            <div class="crm-panel">
                                                <div class="p-4 border-bottom fw-bold">Identificadores</div>
                                                <div class="table-responsive">
                                                    <table class="table align-middle table-row-dashed gy-3 mb-0">
                                                        <thead><tr class="text-muted fw-bold fs-8 text-uppercase"><th>Tipo</th><th>Valor</th><th>Principal</th><th>Estado</th></tr></thead>
                                                        <tbody id="crm_identificadores_tabla"></tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="tab-pane fade" id="crm_tab_contacto">
                                            <div class="row g-4">
                                                <div class="col-lg-6">
                                                    <div class="crm-panel">
                                                        <div class="p-4 border-bottom fw-bold">Contactos</div>
                                                        <div class="p-4 border-bottom">
                                                            <div class="row g-2">
                                                                <div class="col-4">
                                                                    <select class="form-select form-select-sm form-select-solid" id="crm_contacto_tipo">
                                                                        <option value="telefono">Telefono</option>
                                                                        <option value="whatsapp">WhatsApp</option>
                                                                        <option value="correo">Correo</option>
                                                                        <option value="otro">Otro</option>
                                                                    </select>
                                                                </div>
                                                                <div class="col-8"><input class="form-control form-control-sm form-control-solid" id="crm_contacto_valor" placeholder="Valor"></div>
                                                                <div class="col-12"><input class="form-control form-control-sm form-control-solid" id="crm_contacto_etiqueta" placeholder="Etiqueta opcional"></div>
                                                                <div class="col-12"><button class="btn btn-sm btn-light-primary w-100" id="crm_contacto_validar" type="button"><i class="bi bi-check2-circle"></i> Validar contacto</button></div>
                                                            </div>
                                                            <div id="crm_contacto_resultado" class="mt-3"></div>
                                                        </div>
                                                        <div class="p-4 crm-scroll" id="crm_contactos_lista"></div>
                                                    </div>
                                                </div>
                                                <div class="col-lg-6">
                                                    <div class="crm-panel">
                                                        <div class="p-4 border-bottom fw-bold">Direcciones</div>
                                                        <div class="p-4 border-bottom">
                                                            <div class="row g-2">
                                                                <div class="col-5">
                                                                    <select class="form-select form-select-sm form-select-solid" id="crm_direccion_tipo">
                                                                        <option value="entrega">Entrega</option>
                                                                        <option value="facturacion">Facturacion</option>
                                                                        <option value="fiscal">Fiscal</option>
                                                                        <option value="referencia">Referencia</option>
                                                                    </select>
                                                                </div>
                                                                <div class="col-7"><input class="form-control form-control-sm form-control-solid" id="crm_direccion_alias" placeholder="Alias"></div>
                                                                <div class="col-8"><input class="form-control form-control-sm form-control-solid" id="crm_direccion_calle" placeholder="Calle"></div>
                                                                <div class="col-4"><input class="form-control form-control-sm form-control-solid" id="crm_direccion_cp" placeholder="CP"></div>
                                                                <div class="col-12"><button class="btn btn-sm btn-light-primary w-100" id="crm_direccion_validar" type="button"><i class="bi bi-check2-circle"></i> Validar direccion</button></div>
                                                            </div>
                                                            <div id="crm_direccion_resultado" class="mt-3"></div>
                                                        </div>
                                                        <div class="p-4 crm-scroll" id="crm_direcciones_lista"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="tab-pane fade" id="crm_tab_fiscal">
                                            <div class="row g-4">
                                                <div class="col-lg-6">
                                                    <div class="crm-panel">
                                                        <div class="p-4 border-bottom fw-bold">Datos fiscales</div>
                                                        <div class="p-4 border-bottom">
                                                            <div class="row g-2">
                                                                <div class="col-5"><input class="form-control form-control-sm form-control-solid" id="crm_fiscal_rfc" placeholder="RFC"></div>
                                                                <div class="col-7"><input class="form-control form-control-sm form-control-solid" id="crm_fiscal_razon" placeholder="Razon social"></div>
                                                                <div class="col-6"><input class="form-control form-control-sm form-control-solid" id="crm_fiscal_regimen" placeholder="Regimen"></div>
                                                                <div class="col-6"><input class="form-control form-control-sm form-control-solid" id="crm_fiscal_cp" placeholder="CP fiscal"></div>
                                                                <div class="col-12"><button class="btn btn-sm btn-light-primary w-100" id="crm_fiscal_validar" type="button"><i class="bi bi-check2-circle"></i> Validar fiscal</button></div>
                                                            </div>
                                                            <div id="crm_fiscal_resultado" class="mt-3"></div>
                                                        </div>
                                                        <div class="p-4 crm-scroll" id="crm_fiscales_lista"></div>
                                                    </div>
                                                </div>
                                                <div class="col-lg-6">
                                                    <div class="crm-panel">
                                                        <div class="p-4 border-bottom fw-bold">Consentimientos</div>
                                                        <div class="p-4 border-bottom">
                                                            <div class="row g-2">
                                                                <div class="col-6">
                                                                    <select class="form-select form-select-sm form-select-solid" id="crm_consentimiento_tipo">
                                                                        <option value="contacto_operativo">Contacto operativo</option>
                                                                        <option value="contacto_comercial">Contacto comercial</option>
                                                                        <option value="marketing">Marketing</option>
                                                                        <option value="privacidad">Privacidad</option>
                                                                        <option value="whatsapp">WhatsApp</option>
                                                                    </select>
                                                                </div>
                                                                <div class="col-6">
                                                                    <select class="form-select form-select-sm form-select-solid" id="crm_consentimiento_medio">
                                                                        <option value="verbal">Verbal</option>
                                                                        <option value="whatsapp">WhatsApp</option>
                                                                        <option value="correo">Correo</option>
                                                                        <option value="formulario">Formulario</option>
                                                                        <option value="documento">Documento</option>
                                                                        <option value="otro">Otro</option>
                                                                    </select>
                                                                </div>
                                                                <div class="col-12">
                                                                    <select class="form-select form-select-sm form-select-solid" id="crm_consentimiento_otorgado">
                                                                        <option value="1">Otorgado</option>
                                                                        <option value="0">Revocado / no otorgado</option>
                                                                    </select>
                                                                </div>
                                                                <div class="col-12"><input class="form-control form-control-sm form-control-solid" id="crm_consentimiento_evidencia" placeholder="Evidencia o referencia"></div>
                                                                <div class="col-12"><button class="btn btn-sm btn-light-primary w-100" id="crm_consentimiento_validar" type="button"><i class="bi bi-check2-circle"></i> Validar consentimiento</button></div>
                                                            </div>
                                                            <div id="crm_consentimiento_resultado" class="mt-3"></div>
                                                        </div>
                                                        <div class="p-4 crm-scroll" id="crm_consentimientos_lista"></div>
                                                    </div>
                                                </div>
                                                <div class="col-12">
                                                    <div class="crm-panel">
                                                        <div class="p-4 border-bottom fw-bold">Preferencias de contacto</div>
                                                        <div class="p-4 border-bottom">
                                                            <div class="row g-2">
                                                                <div class="col-md-3">
                                                                    <select class="form-select form-select-sm form-select-solid" id="crm_preferencia_canal">
                                                                        <option value="whatsapp">WhatsApp</option>
                                                                        <option value="telefono">Telefono</option>
                                                                        <option value="correo">Correo</option>
                                                                        <option value="presencial">Presencial</option>
                                                                        <option value="ninguno">Ninguno</option>
                                                                    </select>
                                                                </div>
                                                                <div class="col-md-3"><input class="form-control form-control-sm form-control-solid" id="crm_preferencia_canales" placeholder="Canales permitidos: whatsapp,correo"></div>
                                                                <div class="col-md-3"><input class="form-control form-control-sm form-control-solid" id="crm_preferencia_horario" placeholder="Horario preferido"></div>
                                                                <div class="col-md-3">
                                                                    <select class="form-select form-select-sm form-select-solid" id="crm_preferencia_frecuencia">
                                                                        <option value="normal">Normal</option>
                                                                        <option value="baja">Baja</option>
                                                                        <option value="alta">Alta</option>
                                                                        <option value="solo_operativo">Solo operativo</option>
                                                                    </select>
                                                                </div>
                                                                <div class="col-md-8"><input class="form-control form-control-sm form-control-solid" id="crm_preferencia_temas" placeholder="Temas de interes separados por coma"></div>
                                                                <div class="col-md-4">
                                                                    <select class="form-select form-select-sm form-select-solid" id="crm_preferencia_no_contactar">
                                                                        <option value="0">Puede contactarse</option>
                                                                        <option value="1">No contactar</option>
                                                                    </select>
                                                                </div>
                                                                <div class="col-12"><input class="form-control form-control-sm form-control-solid" id="crm_preferencia_motivo_no_contactar" placeholder="Motivo si no debe contactarse"></div>
                                                                <div class="col-12"><button class="btn btn-sm btn-light-primary w-100" id="crm_preferencia_validar" type="button"><i class="bi bi-check2-circle"></i> Validar preferencias</button></div>
                                                            </div>
                                                            <div id="crm_preferencia_resultado" class="mt-3"></div>
                                                        </div>
                                                        <div class="p-4 crm-scroll" id="crm_preferencias_lista"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="tab-pane fade" id="crm_tab_recompensas">
                                            <div class="row g-4">
                                                <div class="col-12">
                                                    <div class="crm-panel">
                                                        <div class="p-4 border-bottom d-flex justify-content-between align-items-center">
                                                            <div>
                                                                <div class="fw-bold">Resumen de recompensas</div>
                                                                <div class="text-muted fs-7">Programas, cuentas y saldos del cliente sin tocar POS ni ventas</div>
                                                            </div>
                                                            <span class="badge badge-light-info" id="crm_recompensas_saldo">0 pts</span>
                                                        </div>
                                                        <div class="p-4" id="crm_recompensas_resumen"></div>
                                                    </div>
                                                </div>
                                                <div class="col-lg-5">
                                                    <div class="crm-panel">
                                                        <div class="p-4 border-bottom fw-bold">Cuentas</div>
                                                        <div class="p-4 crm-scroll" id="crm_recompensas_cuentas"></div>
                                                    </div>
                                                </div>
                                                <div class="col-lg-7">
                                                    <div class="crm-panel">
                                                        <div class="p-4 border-bottom fw-bold">Movimientos</div>
                                                        <div class="p-4 crm-scroll" id="crm_recompensas_movimientos"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="tab-pane fade" id="crm_tab_historial">
                                            <div class="row g-4">
                                                <div class="col-lg-6">
                                                    <div class="crm-panel">
                                                        <div class="p-4 border-bottom fw-bold">Notas</div>
                                                        <div class="p-4 border-bottom">
                                                            <select class="form-select form-select-sm form-select-solid mb-2" id="crm_nota_tipo">
                                                                <option value="operativa">Operativa</option>
                                                                <option value="comercial">Comercial</option>
                                                                <option value="postventa">Postventa</option>
                                                                <option value="interna">Interna</option>
                                                            </select>
                                                            <textarea class="form-control form-control-sm form-control-solid" id="crm_nota_texto" rows="3" placeholder="Nota"></textarea>
                                                            <button class="btn btn-sm btn-light-primary w-100 mt-2" id="crm_nota_validar" type="button"><i class="bi bi-check2-circle"></i> Validar nota</button>
                                                            <div id="crm_nota_resultado" class="mt-3"></div>
                                                        </div>
                                                        <div class="p-4 crm-scroll" id="crm_notas_lista"></div>
                                                    </div>
                                                </div>
                                                <div class="col-lg-6">
                                                    <div class="crm-panel">
                                                        <div class="p-4 border-bottom fw-bold">Eventos</div>
                                                        <div class="p-4 crm-scroll" id="crm_eventos_lista"></div>
                                                    </div>
                                                </div>
                                                <div class="col-12">
                                                    <div class="crm-panel">
                                                        <div class="p-4 border-bottom fw-bold">Interacciones</div>
                                                        <div class="p-4 border-bottom">
                                                            <div class="row g-2">
                                                                <div class="col-md-3">
                                                                    <select class="form-select form-select-sm form-select-solid" id="crm_interaccion_tipo">
                                                                        <option value="contacto">Contacto</option>
                                                                        <option value="seguimiento">Seguimiento</option>
                                                                        <option value="postventa">Postventa</option>
                                                                        <option value="comercial">Comercial</option>
                                                                        <option value="garantia">Garantia</option>
                                                                        <option value="apartado">Apartado</option>
                                                                        <option value="devolucion">Devolucion</option>
                                                                        <option value="calidad_datos">Calidad de datos</option>
                                                                        <option value="otro">Otro</option>
                                                                    </select>
                                                                </div>
                                                                <div class="col-md-3">
                                                                    <select class="form-select form-select-sm form-select-solid" id="crm_interaccion_canal">
                                                                        <option value="whatsapp">WhatsApp</option>
                                                                        <option value="telefono">Telefono</option>
                                                                        <option value="correo">Correo</option>
                                                                        <option value="presencial">Presencial</option>
                                                                        <option value="sistema">Sistema</option>
                                                                        <option value="otro">Otro</option>
                                                                    </select>
                                                                </div>
                                                                <div class="col-md-3">
                                                                    <select class="form-select form-select-sm form-select-solid" id="crm_interaccion_direccion">
                                                                        <option value="saliente">Saliente</option>
                                                                        <option value="entrante">Entrante</option>
                                                                        <option value="interna">Interna</option>
                                                                    </select>
                                                                </div>
                                                                <div class="col-md-3">
                                                                    <select class="form-select form-select-sm form-select-solid" id="crm_interaccion_resultado">
                                                                        <option value="registrado">Registrado</option>
                                                                        <option value="contactado">Contactado</option>
                                                                        <option value="sin_respuesta">Sin respuesta</option>
                                                                        <option value="pendiente">Pendiente</option>
                                                                        <option value="resuelto">Resuelto</option>
                                                                        <option value="no_procede">No procede</option>
                                                                        <option value="otro">Otro</option>
                                                                    </select>
                                                                </div>
                                                                <div class="col-md-8"><input class="form-control form-control-sm form-control-solid" id="crm_interaccion_resumen" maxlength="255" placeholder="Resumen de la interaccion"></div>
                                                                <div class="col-md-4"><input class="form-control form-control-sm form-control-solid" id="crm_interaccion_fecha" placeholder="AAAA-MM-DD HH:MM opcional"></div>
                                                                <div class="col-12"><textarea class="form-control form-control-sm form-control-solid" id="crm_interaccion_detalle" rows="3" placeholder="Detalle opcional"></textarea></div>
                                                                <div class="col-12"><button class="btn btn-sm btn-light-primary w-100" id="crm_interaccion_validar" type="button"><i class="bi bi-check2-circle"></i> Validar interaccion</button></div>
                                                            </div>
                                                            <div id="crm_interaccion_resultado_panel" class="mt-3"></div>
                                                        </div>
                                                        <div class="p-4 crm-scroll" id="crm_interacciones_lista"></div>
                                                    </div>
                                                </div>
                                                <div class="col-12">
                                                    <div class="crm-panel">
                                                        <div class="p-4 border-bottom fw-bold">Vinculos externos</div>
                                                        <div class="p-4" id="crm_vinculos_lista"></div>
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
                <?= include_once '../app/vistas/includes/footer/footer.php'; ?>
            </div>
        </div>
    </div>
</div>
<script src="assets/plugins/global/plugins.bundle.js"></script>
<script src="assets/js/scripts.bundle.js"></script>
<script src="/assets/js/custom/apps/crm/clientes/ficha.js?v=20260630-recompensas-1"></script>
</body>
</html>
