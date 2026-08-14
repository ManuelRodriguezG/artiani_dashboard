<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-08-08
 * Proposito: renderizar un header ERP real sin menus demo del template.
 * Impacto: Layout global; conserva buscador rapido, notificaciones reales, tema y menu de usuario.
 * Contrato: solo lee sesion/permisos y configuracion SYS no sensible; no modifica datos.
 */

require_once '../app/modelos/SistemaConfiguracion.php';
require_once '../app/vistas/includes/header/menu_busqueda.php';
$headerConfiguracion = new SistemaConfiguracion();
$headerBranding = $headerConfiguracion->obtenerBranding();

$headerPermisos = isset($_SESSION["permisos"]) && is_array($_SESSION["permisos"]) ? $_SESSION["permisos"] : array();
$headerPuede = function ($permiso) use ($headerPermisos) {
    if (is_array($permiso)) {
        foreach ($permiso as $opcion) {
            if (in_array($opcion, $headerPermisos, true)) {
                return true;
            }
        }
        return false;
    }
    return $permiso === "" || in_array($permiso, $headerPermisos, true);
};

$headerAccesos = erpHeaderCatalogoBusqueda();
$headerAccesosVisibles = array_values(array_filter($headerAccesos, function ($acceso) use ($headerPuede) {
    return $headerPuede($acceso["permiso"]);
}));
?>
<div id="kt_app_header" class="app-header">
    <div class="app-container container-fluid d-flex align-items-stretch justify-content-between" id="kt_app_header_container">
        <div class="d-flex align-items-center d-lg-none ms-n2 me-2" title="Mostrar menu">
            <button class="btn btn-icon btn-active-color-primary w-35px h-35px" id="kt_app_sidebar_mobile_toggle" type="button" aria-label="Mostrar menu">
                <i class="bi bi-list fs-1"></i>
            </button>
        </div>

        <div class="d-flex align-items-center flex-grow-1 flex-lg-grow-0">
            <a href="/" class="d-lg-none">
                <img alt="<?= htmlspecialchars($headerBranding["nombre_sistema"], ENT_QUOTES, "UTF-8") ?>" src="<?= htmlspecialchars($headerBranding["logo_compacto"], ENT_QUOTES, "UTF-8") ?>" style="width: 38px; height: 38px; object-fit: contain;">
            </a>
        </div>

        <div class="d-flex align-items-stretch justify-content-between flex-lg-grow-1" id="kt_app_header_wrapper">
            <div class="d-flex align-items-center gap-3">
                <div class="d-none d-lg-flex flex-column">
                    <span class="fw-bold text-gray-800"><?= htmlspecialchars($headerBranding["nombre_sistema"], ENT_QUOTES, "UTF-8") ?></span>
                    <span class="text-muted fs-8">Panel operativo</span>
                </div>
            </div>

            <div class="app-navbar flex-shrink-0">
                <div class="app-navbar-item align-items-stretch ms-1 ms-lg-3">
                    <div class="d-flex align-items-center" data-kt-menu-trigger="click" data-kt-menu-attach="parent" data-kt-menu-placement="bottom-end" id="erp_header_search_toggle" title="Buscar modulo">
                        <button type="button" class="btn btn-icon btn-custom btn-icon-muted btn-active-light btn-active-color-primary w-35px h-35px w-md-40px h-md-40px" aria-label="Buscar modulo">
                            <i class="bi bi-search fs-3"></i>
                        </button>
                    </div>
                    <div class="menu menu-sub menu-sub-dropdown menu-column p-5 w-325px w-md-425px" data-kt-menu="true">
                        <div class="position-relative mb-4">
                            <i class="bi bi-search position-absolute top-50 translate-middle-y ms-4 text-muted"></i>
                            <input type="text" class="form-control form-control-solid ps-12" id="erp_header_search_input" placeholder="Buscar pantalla, modulo o accion" autocomplete="off">
                        </div>
                        <div class="text-muted fs-8 mb-3">Busca dentro de las pantallas disponibles para tu usuario.</div>
                        <div class="scroll-y mh-300px" id="erp_header_search_results">
                            <?php foreach ($headerAccesosVisibles as $acceso): ?>
                                <?php
                                $textoBusqueda = implode(' ', array(
                                    $acceso["titulo"],
                                    $acceso["detalle"],
                                    isset($acceso["grupo"]) ? $acceso["grupo"] : '',
                                    isset($acceso["seccion"]) ? $acceso["seccion"] : '',
                                    $acceso["ruta"]
                                ));
                                ?>
                                <a href="<?= htmlspecialchars($acceso["ruta"], ENT_QUOTES, "UTF-8") ?>" class="d-flex align-items-center rounded px-3 py-3 bg-hover-light erp-header-search-item" data-search="<?= htmlspecialchars($textoBusqueda, ENT_QUOTES, "UTF-8") ?>">
                                    <span class="symbol symbol-35px me-3">
                                        <span class="symbol-label bg-light-primary"><i class="bi <?= htmlspecialchars($acceso["icono"], ENT_QUOTES, "UTF-8") ?> text-primary"></i></span>
                                    </span>
                                    <span class="d-flex flex-column">
                                        <span class="fw-semibold text-gray-800"><?= htmlspecialchars($acceso["titulo"], ENT_QUOTES, "UTF-8") ?></span>
                                        <span class="text-muted fs-8"><?= htmlspecialchars((isset($acceso["seccion"]) ? $acceso["seccion"] . " / " : "") . (isset($acceso["grupo"]) ? $acceso["grupo"] . " - " : "") . $acceso["detalle"], ENT_QUOTES, "UTF-8") ?></span>
                                    </span>
                                </a>
                            <?php endforeach; ?>
                            <div class="text-center text-muted py-8 d-none" id="erp_header_search_empty">
                                No encontre pantallas con esa busqueda.
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (SesionSeguridad::tienePermiso("notificaciones.ver")): ?>
                    <div class="app-navbar-item ms-1 ms-lg-3">
                        <button type="button" class="btn btn-icon btn-custom btn-icon-muted btn-active-light btn-active-color-primary w-35px h-35px w-md-40px h-md-40px position-relative" id="erp_notificaciones_toggle" data-kt-menu-trigger="click" data-kt-menu-attach="parent" data-kt-menu-placement="bottom-end" aria-label="Notificaciones">
                            <i class="bi bi-bell fs-3"></i>
                            <span id="erp_notificaciones_badge" class="badge badge-circle badge-primary position-absolute top-0 start-100 translate-middle d-none">0</span>
                        </button>
                        <div class="menu menu-sub menu-sub-dropdown w-350px w-md-425px" data-kt-menu="true">
                            <div class="px-9 pt-8 pb-5 bg-primary rounded-top">
                                <div class="d-flex flex-stack">
                                    <h3 class="text-white fw-bold mb-0">Notificaciones</h3>
                                    <span class="badge badge-light-primary" id="erp_notificaciones_resumen">0 pendientes</span>
                                </div>
                                <ul class="nav nav-line-tabs nav-line-tabs-2x border-transparent fs-6 fw-semibold mt-6">
                                    <li class="nav-item">
                                        <a class="nav-link text-white opacity-75 opacity-state-100 pb-4 active" data-bs-toggle="tab" href="#kt_topbar_notifications_1">Alertas</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link text-white opacity-75 opacity-state-100 pb-4" data-bs-toggle="tab" href="#kt_topbar_notifications_2">Areas</a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link text-white opacity-75 opacity-state-100 pb-4" data-bs-toggle="tab" href="#kt_topbar_notifications_3">Historial</a>
                                    </li>
                                </ul>
                            </div>
                            <div class="tab-content">
                                <div class="tab-pane fade show active" id="kt_topbar_notifications_1" role="tabpanel"></div>
                                <div class="tab-pane fade" id="kt_topbar_notifications_2" role="tabpanel"></div>
                                <div class="tab-pane fade" id="kt_topbar_notifications_3" role="tabpanel"></div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="app-navbar-item ms-1 ms-lg-3">
                    <button type="button" class="btn btn-icon btn-custom btn-icon-muted btn-active-light btn-active-color-primary w-35px h-35px w-md-40px h-md-40px" data-kt-menu-trigger="click" data-kt-menu-attach="parent" data-kt-menu-placement="bottom-end" aria-label="Tema">
                        <i class="bi bi-circle-half fs-3"></i>
                    </button>
                    <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-title-gray-700 menu-icon-muted menu-active-bg menu-state-color fw-semibold py-4 fs-base w-175px" data-kt-menu="true" data-kt-element="theme-mode-menu">
                        <div class="menu-item px-3 my-0">
                            <a href="#" class="menu-link px-3 py-2" data-kt-element="mode" data-kt-value="light"><span class="menu-icon"><i class="bi bi-sun"></i></span><span class="menu-title">Claro</span></a>
                        </div>
                        <div class="menu-item px-3 my-0">
                            <a href="#" class="menu-link px-3 py-2" data-kt-element="mode" data-kt-value="dark"><span class="menu-icon"><i class="bi bi-moon"></i></span><span class="menu-title">Oscuro</span></a>
                        </div>
                        <div class="menu-item px-3 my-0">
                            <a href="#" class="menu-link px-3 py-2" data-kt-element="mode" data-kt-value="system"><span class="menu-icon"><i class="bi bi-display"></i></span><span class="menu-title">Sistema</span></a>
                        </div>
                    </div>
                </div>

                <?php include '../app/vistas/includes/header/user_menu.php'; ?>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener("DOMContentLoaded", function () {
    var faviconHref = <?= json_encode($headerBranding["favicon"]) ?>;
    if (faviconHref) {
        var favicon = document.querySelector("link[rel='icon'], link[rel='shortcut icon']");
        if (!favicon) {
            favicon = document.createElement("link");
            favicon.rel = "icon";
            document.head.appendChild(favicon);
        }
        favicon.href = faviconHref;
    }

    var input = document.getElementById("erp_header_search_input");
    if (!input) {
        return;
    }
    var toggle = document.getElementById("erp_header_search_toggle");
    var empty = document.getElementById("erp_header_search_empty");
    var items = Array.prototype.slice.call(document.querySelectorAll(".erp-header-search-item"));
    var normalizar = function (valor) {
        return (valor || "").toString().toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "").trim();
    };
    var aplicarFiltro = function () {
        var filtro = normalizar(input.value);
        var visibles = 0;
        items.forEach(function (item) {
            var visible = filtro === "" || normalizar(item.getAttribute("data-search")).indexOf(filtro) !== -1;
            item.classList.toggle("d-none", !visible);
            if (visible) {
                visibles++;
            }
        });
        if (empty) {
            empty.classList.toggle("d-none", visibles > 0);
        }
    };

    if (toggle) {
        toggle.addEventListener("click", function () {
            window.setTimeout(function () {
                input.focus();
                input.select();
            }, 120);
        });
    }

    input.addEventListener("input", aplicarFiltro);
    input.addEventListener("keydown", function (evento) {
        if (evento.key === "Enter") {
            var primeroVisible = items.find(function (item) {
                return !item.classList.contains("d-none");
            });
            if (primeroVisible) {
                evento.preventDefault();
                window.location.href = primeroVisible.getAttribute("href");
            }
        }
        if (evento.key === "Escape") {
            input.value = "";
            aplicarFiltro();
            input.blur();
        }
    });
});
</script>
