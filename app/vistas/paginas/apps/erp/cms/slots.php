<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../../">
    <title>CMS - Slots</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css">
    <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css">
    <!--
      Documentacion IA: Codex GPT-5, 2026-08-10.
      Proposito: vista dedicada para revisar slots CMS por pagina y plantilla.
      Impacto: CMS; separa mapa estructural del editor de bloques.
      Contrato: read-only; no modifica slots ni plantilla.
    -->
    <style>
        .ecom-cms-panel { border: 1px solid #e7e9ef; border-radius: 8px; background: #fff; }
        .ecom-cms-slot { border: 1px solid #e7e9ef; border-radius: 8px; padding: 14px; background: #fbfcfe; }
        .ecom-cms-slot + .ecom-cms-slot { margin-top: 10px; }
        .ecom-cms-slot.is-active { border-color: #3e97ff; background: #f1f8ff; }
        .ecom-cms-chip-list { display: flex; flex-wrap: wrap; gap: 6px; }
        .ecom-cms-code { min-height: 420px; max-height: 700px; overflow: auto; background: #111827; color: #e5e7eb; border-radius: 8px; padding: 16px; font-size: .78rem; line-height: 1.5; white-space: pre-wrap; }
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
                                <h1 class="page-heading text-dark fw-bold fs-3 mb-1">Slots CMS</h1>
                                <span class="text-muted">Mapa de espacios disponibles por pagina, contexto y tipo de bloque</span>
                            </div>
                            <div class="d-flex gap-2">
                                <a class="btn btn-light" href="/cms/contenido"><i class="bi bi-pencil-square"></i> Editar contenido</a>
                                <a class="btn btn-light" href="/docs/erp_cms_manual_uso.md" target="_blank" rel="noopener"><i class="bi bi-journal-text"></i> Manual</a>
                                <button class="btn btn-primary" type="button" id="ecom_cms_recargar"><i class="bi bi-arrow-clockwise"></i> Recargar</button>
                            </div>
                        </div>
                    </div>
                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <div class="alert alert-info d-flex align-items-start gap-3">
                                <i class="bi bi-info-circle fs-2"></i>
                                <div>
                                    <div class="fw-bold">Vista estructural read-only</div>
                                    <div>Usa esta pantalla para entender limites, tipos permitidos y obligatoriedad de cada slot. La captura de bloques se hace en Contenido ecommerce.</div>
                                </div>
                            </div>

                            <div class="ecom-cms-panel p-4 mb-5">
                                <div class="row g-3 align-items-end">
                                    <div class="col-md-3"><label class="form-label">Pagina</label><select class="form-select form-select-solid" id="ecom_cms_pagina"><option value="home">Home</option><option value="categoria">Categoria</option><option value="catalogo">Catalogo</option></select></div>
                                    <div class="col-md-3"><label class="form-label">Categoria</label><input class="form-control form-control-solid" id="ecom_cms_categoria" type="text" value="peces"></div>
                                    <div class="col-md-3"><label class="form-label">Plantilla</label><input class="form-control form-control-solid" id="ecom_cms_template" type="text" value="artiani_default"></div>
                                    <div class="col-md-3 text-md-end"><button class="btn btn-light-primary" type="button" id="ecom_cms_preview"><i class="bi bi-eye"></i> Previsualizar</button><span class="badge badge-light-primary ms-2" id="ecom_cms_estado">Listo</span></div>
                                </div>
                            </div>

                            <div class="row g-5">
                                <div class="col-xl-5">
                                    <div class="ecom-cms-panel p-5">
                                        <h3 class="fw-bold mb-4">Slots declarados</h3>
                                        <div id="ecom_cms_slots"></div>
                                    </div>
                                </div>
                                <div class="col-xl-7">
                                    <div class="ecom-cms-panel p-5 mb-5">
                                        <h3 class="fw-bold mb-4">Detalle del slot</h3>
                                        <div id="ecom_cms_slot_detalle"></div>
                                    </div>
                                    <div class="ecom-cms-panel p-5">
                                        <h3 class="fw-bold mb-1">JSON de pagina</h3>
                                        <span class="text-muted fs-7 d-block mb-4">Preview estructural de la pagina seleccionada.</span>
                                        <pre class="ecom-cms-code" id="ecom_cms_json">{}</pre>
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
<script src="/assets/js/custom/apps/erp/cms/contenido.js?v=20260810-vistas1"></script>
</body>
</html>
