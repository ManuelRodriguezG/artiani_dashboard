<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../../">
    <?php
      $cmsFrontendTitulo = isset($cmsFrontendTitulo) ? $cmsFrontendTitulo : "CMS - Frontend Home ecommerce";
      $cmsFrontendHeading = isset($cmsFrontendHeading) ? $cmsFrontendHeading : "CMS / Frontend / Home";
      $cmsFrontendSubtitulo = isset($cmsFrontendSubtitulo) ? $cmsFrontendSubtitulo : "Administra las secciones visuales de Home que consumira el ecommerce publico";
      $cmsFrontendGrupoInicial = isset($cmsFrontendGrupoInicial) ? $cmsFrontendGrupoInicial : "home";
      $cmsFrontendAvisoTitulo = isset($cmsFrontendAvisoTitulo) ? $cmsFrontendAvisoTitulo : "Home del frontend";
      $cmsFrontendAvisoTexto = isset($cmsFrontendAvisoTexto) ? $cmsFrontendAvisoTexto : "Esta pantalla concentra lo editable de Home: hero, categorias, productos destacados, colecciones y banner. La API y la persistencia real se conectaran despues de cerrar el contrato de esta pagina.";
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
        @media (max-width: 1199.98px) { .cms-actual-grid, .cms-actual-priority, .cms-actual-fields { grid-template-columns: 1fr; } }
    </style>
</head>
<body id="kt_app_body" data-cms-actual-grupo="<?= htmlspecialchars($cmsFrontendGrupoInicial, ENT_QUOTES, 'UTF-8'); ?>" data-kt-app-layout="dark-sidebar" data-kt-app-header-fixed="true" data-kt-app-sidebar-enabled="true" data-kt-app-sidebar-fixed="true" class="app-default">
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

                            <div class="cms-actual-priority mb-5" id="cms_actual_prioridad"></div>

                            <div class="cms-actual-grid">
                                <aside class="cms-actual-panel p-4">
                                    <div class="fw-bold mb-3">Paginas y grupos</div>
                                    <div class="cms-actual-nav" id="cms_actual_nav"></div>
                                    <div class="separator my-5"></div>
                                    <div class="fw-bold mb-3">Reglas publicas</div>
                                    <div id="cms_actual_reglas"></div>
                                </aside>
                                <section class="cms-actual-panel p-5">
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
<script src="/assets/js/custom/apps/erp/cms/frontend_actual.js?v=20260813-actual1"></script>
</body>
</html>
