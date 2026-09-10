<!DOCTYPE html>
<html lang="es">
<head>
    <base href="../../../../">
    <title>CMS - Frontend Busqueda</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="assets/media/logos/favicon.ico">
    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css">
    <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css">
    <!--
      Documentacion IA: Codex GPT-5, 2026-09-09.
      Proposito: administrar configuracion CMS para busqueda inteligente ecommerce.
      Impacto: CMS/API publica; permite ajustar sinonimos, reglas y mensajes consumidos por /ecommercePublico/busqueda.
      Contrato: editor protegido; publica JSON validado en configuracion ecommerce sin tocar catalogo, precios ni inventario.
    -->
    <style>
        .cms-search-panel { border: 1px solid #e7e9ef; border-radius: 8px; background: #fff; }
        .cms-search-editor { min-height: 540px; font-family: Consolas, Monaco, monospace; font-size: 12px; line-height: 1.5; }
        .cms-search-output { min-height: 220px; max-height: 420px; overflow: auto; font-family: Consolas, Monaco, monospace; font-size: 12px; }
        .cms-search-stat { border: 1px solid #e7e9ef; border-radius: 8px; padding: 14px; background: #fbfcfe; }
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
                                <h1 class="page-heading text-dark fw-bold fs-3 mb-1">CMS / Frontend / Busqueda</h1>
                                <span class="text-muted">Sinonimos, reglas, prioridades y mensajes del buscador publico</span>
                            </div>
                            <div class="d-flex gap-2">
                                <a class="btn btn-light" href="/cms/frontend/catalogo"><i class="bi bi-grid"></i> Catalogo</a>
                                <a class="btn btn-light" href="/ecommercePublico/busqueda_manifest" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right"></i> Manifest publico</a>
                                <button class="btn btn-primary" type="button" id="cms_busqueda_publicar"><i class="bi bi-cloud-upload"></i> Publicar</button>
                            </div>
                        </div>
                    </div>

                    <div class="app-content flex-column-fluid">
                        <div class="app-container container-fluid">
                            <div class="alert alert-info d-flex align-items-start gap-3">
                                <i class="bi bi-search fs-2"></i>
                                <div>
                                    <div class="fw-bold">Configuracion consumida por API</div>
                                    <div>Esta pantalla administra la clave <code>busqueda_inteligente_config</code>. El frontend debe seguir consultando <code>/ecommercePublico/busqueda</code>, <code>/ecommercePublico/busqueda_sugerencias</code> y <code>/ecommercePublico/busqueda_manifest</code>.</div>
                                </div>
                            </div>

                            <div class="row g-5">
                                <div class="col-xl-8">
                                    <div class="cms-search-panel p-5">
                                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
                                            <div>
                                                <h3 class="fw-bold mb-1">Editor JSON</h3>
                                                <span class="text-muted fs-7">Mantiene URLs limpias y reglas de busqueda sin que frontend genere logica propia.</span>
                                            </div>
                                            <span class="badge badge-light-primary" id="cms_busqueda_fuente">Cargando</span>
                                        </div>
                                        <textarea class="form-control form-control-solid cms-search-editor" id="cms_busqueda_json" spellcheck="false"></textarea>
                                        <div class="d-flex flex-wrap gap-2 mt-4">
                                            <button class="btn btn-light" type="button" id="cms_busqueda_formatear"><i class="bi bi-braces"></i> Formatear</button>
                                            <button class="btn btn-light" type="button" id="cms_busqueda_recargar"><i class="bi bi-arrow-clockwise"></i> Recargar</button>
                                            <button class="btn btn-light-warning" type="button" id="cms_busqueda_restaurar"><i class="bi bi-arrow-counterclockwise"></i> Restaurar defaults</button>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-xl-4">
                                    <div class="cms-search-panel p-5 mb-5">
                                        <h3 class="fw-bold mb-4">Estado</h3>
                                        <div class="row g-3" id="cms_busqueda_stats"></div>
                                    </div>

                                    <div class="cms-search-panel p-5 mb-5">
                                        <h3 class="fw-bold mb-4">Probar busqueda</h3>
                                        <div class="input-group mb-3">
                                            <input class="form-control form-control-solid" id="cms_busqueda_q" type="text" value="filtro para pecera" placeholder="Buscar como cliente">
                                            <button class="btn btn-light-primary" type="button" id="cms_busqueda_probar"><i class="bi bi-play"></i></button>
                                        </div>
                                        <pre class="cms-search-output bg-light rounded p-3 mb-0" id="cms_busqueda_resultado">{}</pre>
                                    </div>

                                    <div class="cms-search-panel p-5">
                                        <h3 class="fw-bold mb-4">Guardrails</h3>
                                        <div class="d-flex flex-column gap-3 fs-7">
                                            <div><span class="badge badge-light-success me-2">OK</span>No modifica productos, precios ni inventario.</div>
                                            <div><span class="badge badge-light-success me-2">OK</span>Frontend no debe generar slugs desde nombres.</div>
                                            <div><span class="badge badge-light-warning me-2">SEO</span>Usar URLs publicas: <code>/categoria</code>, <code>/marca</code>, <code>/buscar</code>.</div>
                                            <div><span class="badge badge-light-danger me-2">No</span>No usar rutas internas del ERP como URLs publicas.</div>
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
<script>
    window.ERP_CSRF_TOKEN = "<?= htmlspecialchars(SesionSeguridad::csrfToken(), ENT_QUOTES, 'UTF-8') ?>";
</script>
<script src="assets/plugins/global/plugins.bundle.js"></script>
<script src="assets/js/scripts.bundle.js"></script>
<script src="assets/js/custom/apps/erp/cms/frontend_busqueda.js?v=20260909-busqueda1"></script>
</body>
</html>
