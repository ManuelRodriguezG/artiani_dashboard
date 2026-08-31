<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../../">
    <?php
      $cmsFrontendTitulo = isset($cmsFrontendTitulo) ? $cmsFrontendTitulo : "CMS - Frontend ecommerce";
      $cmsFrontendHeading = isset($cmsFrontendHeading) ? $cmsFrontendHeading : "CMS / Frontend";
      $cmsFrontendSubtitulo = isset($cmsFrontendSubtitulo) ? $cmsFrontendSubtitulo : "Mapa general de paginas y grupos configurables del ecommerce publico";
      $cmsFrontendGrupoInicial = isset($cmsFrontendGrupoInicial) ? $cmsFrontendGrupoInicial : "home";
      $cmsFrontendVistaDedicada = !empty($cmsFrontendVistaDedicada);
      $cmsFrontendAvisoTitulo = isset($cmsFrontendAvisoTitulo) ? $cmsFrontendAvisoTitulo : "Mapa CMS Frontend";
      $cmsFrontendAvisoTexto = isset($cmsFrontendAvisoTexto) ? $cmsFrontendAvisoTexto : "Esta entrada sirve para ubicar paginas y grupos. Al abrir una pagina, el editor cambia a una vista dedicada para capturar solo lo que pertenece a esa parte del frontend.";
    ?>
    <title><?= htmlspecialchars($cmsFrontendTitulo, ENT_QUOTES, 'UTF-8'); ?></title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css">
    <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css">
    <!--
      Documentacion IA: Codex GPT-5, 2026-08-13.
      Proposito: adaptar CMS al contrato real del frontend ecommerce publico.
      Impacto: reemplaza el enfoque generico por secciones concretas requeridas por el frontend actual.
      Contrato: vista protegida; no escribe archivos del frontend ni modifica catalogo/precios/inventario.
    -->
    <style>
        .cms-actual-panel { border: 1px solid #e7e9ef; border-radius: 8px; background: #fff; }
        .cms-actual-card { border: 1px solid #e7e9ef; border-radius: 8px; background: #fff; padding: 16px; }
        .cms-actual-grid { display: grid; grid-template-columns: minmax(260px, 340px) minmax(0, 1fr); gap: 20px; align-items: start; }
        .cms-actual-nav button { width: 100%; text-align: left; border: 1px solid #e7e9ef; border-radius: 8px; background: #fff; padding: 12px; margin-bottom: 10px; }
        .cms-actual-nav button.is-active, .cms-actual-nav button:hover { border-color: #009ef7; background: #f1faff; }
        .cms-actual-priority { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 10px; }
        .cms-actual-json { min-height: 420px; max-height: 620px; overflow: auto; background: #111827; color: #e5e7eb; border-radius: 8px; padding: 16px; font-size: .82rem; }
        .cms-actual-fields { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
        .cms-actual-slide { border: 1px solid #e7e9ef; border-radius: 8px; background: #fbfdff; padding: 14px; }
        .cms-actual-slide-preview { min-height: 180px; border-radius: 8px; background: #111827; color: #fff; background-size: cover; background-position: center; display: flex; align-items: center; padding: 24px; position: relative; overflow: hidden; }
        .cms-actual-slide-preview::before { content: ""; position: absolute; inset: 0; background: linear-gradient(90deg, rgba(17, 24, 39, .82), rgba(17, 24, 39, .28)); }
        .cms-actual-slide-preview > div { position: relative; max-width: 520px; }
        .cms-actual-status-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; }
        .cms-actual-status-card { border: 1px solid #e7e9ef; border-radius: 8px; background: #fff; padding: 14px; min-height: 104px; }
        .cms-actual-page-links { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; }
        .cms-actual-page-link { display: block; border: 1px solid #e7e9ef; border-radius: 8px; background: #fff; padding: 12px; color: inherit; }
        .cms-actual-page-link:hover { border-color: #009ef7; background: #f1faff; color: inherit; }
        body[data-cms-actual-dedicada="1"] .cms-actual-grid { grid-template-columns: minmax(0, 1fr); }
        body[data-cms-actual-dedicada="1"] .cms-actual-hub-only { display: none !important; }
        body[data-cms-actual-dedicada="1"] .cms-actual-editor-panel { padding: 28px !important; }
        @media (max-width: 1199.98px) { .cms-actual-grid, .cms-actual-priority, .cms-actual-fields { grid-template-columns: 1fr; } }
        @media (max-width: 1199.98px) { .cms-actual-page-links { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (max-width: 1199.98px) { .cms-actual-status-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (max-width: 767.98px) { .cms-actual-status-grid, .cms-actual-page-links { grid-template-columns: 1fr; } }
    </style>
</head>
<body id="kt_app_body" data-cms-actual-grupo="<?= htmlspecialchars($cmsFrontendGrupoInicial, ENT_QUOTES, 'UTF-8'); ?>" data-cms-actual-dedicada="<?= $cmsFrontendVistaDedicada ? '1' : '0'; ?>" data-kt-app-layout="dark-sidebar" data-kt-app-header-fixed="true" data-kt-app-sidebar-enabled="true" data-kt-app-sidebar-fixed="true" class="app-default">
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
                                <h1 class="page-heading text-dark fw-bold fs-3 mb-1"><?= htmlspecialchars($cmsFrontendHeading, ENT_QUOTES, 'UTF-8'); ?></h1>
                                <span class="text-muted"><?= htmlspecialchars($cmsFrontendSubtitulo, ENT_QUOTES, 'UTF-8'); ?></span>
                            </div>
                            <div class="d-flex gap-2">
                                <?php if ($cmsFrontendVistaDedicada) : ?>
                                <a class="btn btn-light" href="/cms"><i class="bi bi-grid"></i> Mapa CMS</a>
                                <?php endif; ?>
                                <a class="btn btn-light" href="/cms/media"><i class="bi bi-images"></i> Media / Archivos</a>
                                <a class="btn btn-light" href="/cms/contenido"><i class="bi bi-sliders"></i> Editor avanzado</a>
                                <a class="btn btn-light" href="/docs/erp_cms_manual_uso.md" target="_blank" rel="noopener"><i class="bi bi-journal-text"></i> Manual</a>
                                <span class="badge badge-light-primary" id="cms_actual_estado">Listo</span>
                            </div>
                        </div>
                    </div>
                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <div class="alert alert-info d-flex align-items-start gap-3">
                                <i class="bi bi-filetype-json fs-2"></i>
                                <div>
                                    <div class="fw-bold"><?= htmlspecialchars($cmsFrontendAvisoTitulo, ENT_QUOTES, 'UTF-8'); ?></div>
                                    <div><?= htmlspecialchars($cmsFrontendAvisoTexto, ENT_QUOTES, 'UTF-8'); ?></div>
                                </div>
                            </div>

                            <?php if ($cmsFrontendVistaDedicada) : ?>
                            <div class="cms-actual-panel p-4 mb-5">
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                                    <div>
                                        <div class="fw-bold">Vista dedicada</div>
                                        <div class="text-muted fs-7">Esta pantalla solo muestra y guarda configuracion de este grupo. Para cambiar de pagina usa el menu lateral o el mapa del CMS.</div>
                                    </div>
                                    <a class="btn btn-sm btn-light-primary" href="/cms"><i class="bi bi-arrow-left"></i> Ver paginas y grupos</a>
                                </div>
                            </div>
                            <?php else : ?>
                            <div class="cms-actual-panel p-4 mb-5 cms-actual-hub-only">
                                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
                                    <div>
                                        <div class="fw-bold">Paginas y grupos del CMS Frontend</div>
                                        <div class="text-muted fs-7">Selecciona una pagina para abrir su editor dedicado.</div>
                                    </div>
                                    <span class="badge badge-light-info">Mapa general</span>
                                </div>
                                <div class="cms-actual-page-links" id="cms_actual_page_links"></div>
                            </div>
                            <div class="cms-actual-priority mb-5 cms-actual-hub-only" id="cms_actual_prioridad"></div>
                            <?php endif; ?>

                            <div class="cms-actual-panel p-4 mb-5" id="cms_actual_home_estado_panel">
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                                    <div>
                                        <div class="fw-bold">Estado publicado de Home</div>
                                        <div class="text-muted fs-7">Resumen real de lo que esta entregando la API publica.</div>
                                    </div>
                                    <button class="btn btn-sm btn-light-info" type="button" id="cms_actual_home_estado_refrescar"><i class="bi bi-arrow-clockwise"></i> Refrescar estado</button>
                                </div>
                                <div class="cms-actual-status-grid" id="cms_actual_home_estado">Consultando API publica...</div>
                            </div>

                            <div class="cms-actual-grid">
                                <aside class="cms-actual-panel p-4 cms-actual-hub-only">
                                    <div class="fw-bold mb-3">Paginas y grupos</div>
                                    <div class="cms-actual-nav" id="cms_actual_nav"></div>
                                    <div class="separator my-5"></div>
                                    <div class="fw-bold mb-3">Reglas publicas</div>
                                    <div id="cms_actual_reglas"></div>
                                </aside>
                                <section class="cms-actual-panel p-5 cms-actual-editor-panel">
                                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
                                        <div>
                                            <h3 class="fw-bold mb-1" id="cms_actual_titulo">Contrato</h3>
                                            <div class="text-muted fs-7" id="cms_actual_subtitulo">Selecciona una pagina o grupo.</div>
                                        </div>
                                        <span class="badge badge-light-info" id="cms_actual_endpoint">endpoint pendiente</span>
                                    </div>
                                    <div id="cms_actual_secciones"></div>
                                    <div class="separator my-5"></div>
                                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                                        <div class="fw-bold">Preview JSON esperado</div>
                                        <button class="btn btn-sm btn-light-primary" type="button" id="cms_actual_copiar_json"><i class="bi bi-clipboard"></i> Copiar JSON</button>
                                    </div>
                                    <pre class="cms-actual-json mb-0" id="cms_actual_json">{}</pre>
                                </section>
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
<script>
    window.ERP_CSRF_TOKEN = "<?= htmlspecialchars(SesionSeguridad::csrfToken(), ENT_QUOTES, 'UTF-8') ?>";
</script>
<script src="/assets/js/custom/apps/erp/cms/frontend_actual.js?v=20260830-global-whatsapp1"></script>
</body>
</html>
