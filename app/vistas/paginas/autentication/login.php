<?php
/**
 * IA: Codex GPT-5
 * Fecha: 2026-08-08
 * Proposito: renderizar login con branding configurable independiente del panel interno.
 * Impacto: Autenticacion; sustituye textos/logos demo por configuracion SYS no sensible.
 * Contrato: solo consulta branding; el flujo de autenticacion JS/backend no cambia.
 */
require_once '../app/modelos/SistemaConfiguracion.php';
$loginConfiguracion = new SistemaConfiguracion();
$loginBranding = $loginConfiguracion->obtenerBranding();
?>
<!DOCTYPE html>
<html lang="es">
    <!--begin::Head-->
    <head><base href="../../../"/>
        <title><?= htmlspecialchars($loginBranding["nombre_sistema"], ENT_QUOTES, "UTF-8") ?> | Login</title>
        <meta charset="utf-8" />
        <meta name="description" content="<?= htmlspecialchars($loginBranding["login_subtitulo"], ENT_QUOTES, "UTF-8") ?>" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <meta property="og:locale" content="es_MX" />
        <meta property="og:type" content="website" />
        <meta property="og:title" content="<?= htmlspecialchars($loginBranding["nombre_sistema"], ENT_QUOTES, "UTF-8") ?>" />
        <meta property="og:site_name" content="<?= htmlspecialchars($loginBranding["nombre_sistema"], ENT_QUOTES, "UTF-8") ?>" />
        <link rel="icon" href="<?= htmlspecialchars($loginBranding["favicon"], ENT_QUOTES, "UTF-8") ?>" />
        <link rel="shortcut icon" href="<?= htmlspecialchars($loginBranding["favicon"], ENT_QUOTES, "UTF-8") ?>" />
        <!--begin::Fonts-->
        <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700" />
        <!--end::Fonts-->
        <!--begin::Global Stylesheets Bundle(used by all pages)-->
        <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/style.bundle.css" rel="stylesheet" type="text/css" />
        <!--end::Global Stylesheets Bundle-->
    </head>
    <!--end::Head-->
    <!--begin::Body-->
    <body id="kt_body" class="app-blank app-blank">
        <!--begin::Theme mode setup on page load-->
        <script>var defaultThemeMode = "light";
          var themeMode;
          if (document.documentElement) {
              if (document.documentElement.hasAttribute("data-theme-mode")) {
                  themeMode = document.documentElement.getAttribute("data-theme-mode");
              } else {
                  if (localStorage.getItem("data-theme") !== null) {
                      themeMode = localStorage.getItem("data-theme");
                  } else {
                      themeMode = defaultThemeMode;
                  }
              }
              if (themeMode === "system") {
                  themeMode = window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
              }
              document.documentElement.setAttribute("data-theme", themeMode);
          }</script>
        <!--end::Theme mode setup on page load-->
        <!--begin::Root-->
        <div class="d-flex flex-column flex-root" id="kt_app_root">
            <!--begin::Authentication - Sign-in -->
            <div class="d-flex flex-column flex-lg-row flex-column-fluid">
                <!--begin::Logo-->
                <a href="/autenticacion/login" class="d-block d-lg-none mx-auto py-12">
                    <img alt="<?= htmlspecialchars($loginBranding["nombre_sistema"], ENT_QUOTES, "UTF-8") ?>" src="<?= htmlspecialchars($loginBranding["logo_login"], ENT_QUOTES, "UTF-8") ?>" style="width: 210px; max-height: 110px; object-fit: contain;" />
                </a>
                <!--end::Logo-->
                <!--begin::Aside-->
                <div class="d-flex flex-column flex-column-fluid flex-center w-lg-50 p-10">
                    <!--begin::Wrapper-->
                    <div class="d-flex justify-content-between flex-column-fluid flex-column w-100 mw-450px">
                        <!--begin::Header-->
                        <div class="d-flex flex-stack py-2">
                            <!--begin::Back link-->
                            <div class="me-2"></div>
                            <!--end::Back link-->
                            <!--begin::Sign Up link-->
                            <div class="m-0 d-none">
                                <span class="text-gray-400 fw-bold fs-5 me-2" data-kt-translate="sign-in-head-desc">Aún no eres miembro?</span>
                                <span class="link-primary fw-bold fs-5" data-kt-translate="sign-in-head-link">Alta solo por administrador</span>
                            </div>
                            <!--end::Sign Up link=-->
                        </div>
                        <!--end::Header-->
                        <!--begin::Body-->
                        <div class="py-20">
                            <!--begin::Form-->
                            <form class="form w-100" novalidate="novalidate" id="kt_sign_in_form" data-kt-redirect-url="/" action="#">
                                <!--begin::Body-->
                                <div class="card-body">
                                    <!--begin::Heading-->
                                    <div class="text-start mb-10">
                                        <div class="mb-10">
                                            <img alt="<?= htmlspecialchars($loginBranding["nombre_sistema"], ENT_QUOTES, "UTF-8") ?>" src="<?= htmlspecialchars($loginBranding["logo_login"], ENT_QUOTES, "UTF-8") ?>" style="width: 260px; max-width: 100%; max-height: 130px; object-fit: contain;" />
                                        </div>
                                        <!--begin::Title-->
                                        <h1 class="text-dark mb-3 fs-2x" data-kt-translate="sign-in-title"><?= htmlspecialchars($loginBranding["login_titulo"], ENT_QUOTES, "UTF-8") ?></h1>
                                        <!--end::Title-->
                                        <!--begin::Text-->
                                        <div class="text-gray-500 fw-semibold fs-6" data-kt-translate="general-desc"><?= htmlspecialchars($loginBranding["login_subtitulo"], ENT_QUOTES, "UTF-8") ?></div>
                                        <!--end::Link-->
                                    </div>
                                    <!--begin::Heading-->
                                    <!--begin::Input group=-->
                                    <div class="fv-row mb-8">
                                        <!--begin::Email-->
                                        <input type="number" placeholder="Celular" name="celular" id="celular" autocomplete="off" data-kt-translate="sign-in-input-celular" class="form-control form-control-solid" />
                                        <!--end::Email-->
                                    </div>
                                    <!--end::Input group=-->
                                    <div class="fv-row mb-7">
                                        <!--begin::Password-->
                                        <input type="password" placeholder="Contraseña" id="contrasenia" name="contrasenia" autocomplete="off" data-kt-translate="sign-in-input-contrasenia" class="form-control form-control-solid" />
                                        <!--end::Password-->
                                    </div>
                                    <!--end::Input group=-->
                                    <!--begin::Wrapper-->
                                    <div class="d-flex flex-stack flex-wrap gap-3 fs-base fw-semibold mb-10">
                                        <div></div>
                                        <!--begin::Link-->
                                        <a href="#" class="link-primary" data-kt-translate="sign-in-forgot-password">Olvidaste tu contraseña ?</a>
                                        <!--end::Link-->
                                    </div>
                                    <!--end::Wrapper-->
                                    <!--begin::Actions-->
                                    <div class="d-flex flex-stack">
                                        <!--begin::Submit-->
                                        <button id="kt_sign_in_submit" class="btn btn-primary me-2 flex-shrink-0">
                                            <!--begin::Indicator label-->
                                            <span class="indicator-label" data-kt-translate="sign-in-submit">Iniciar Sesión</span>
                                            <!--end::Indicator label-->
                                            <!--begin::Indicator progress-->
                                            <span class="indicator-progress">
                                                <span data-kt-translate="general-progress">Espere un momento...</span>
                                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                            </span>
                                            <!--end::Indicator progress-->
                                        </button>
                                        <!--end::Submit-->
                                    </div>
                                    <!--end::Actions-->
                                </div>
                                <!--begin::Body-->
                            </form>
                            <!--end::Form-->
                        </div>
                        <!--end::Body-->
                    </div>
                    <!--end::Wrapper-->
                </div>
                <!--end::Aside-->
                <!--begin::Body-->
                <div class="d-none d-lg-flex flex-lg-row-fluid w-50 bgi-size-cover bgi-position-y-center bgi-position-x-start bgi-no-repeat" style="background-image: url(/media/autenticacion/comming-soon.jpg)"></div>
                <!--begin::Body-->
            </div>
            <!--end::Authentication - Sign-in-->
        </div>
        <!--end::Root-->
        <!--begin::Javascript-->
        <script>var hostUrl = "assets/";</script>
        <!--begin::Global Javascript Bundle(used by all pages)-->
        <script src="assets/plugins/global/plugins.bundle.js"></script>
        <script src="assets/js/scripts.bundle.js"></script>
        <!--end::Global Javascript Bundle-->
        <!--begin::Custom Javascript(used by this page)-->
        <script src="assets/js/custom/authentication/sign-in/general.js"></script>
        <!--end::Custom Javascript-->
        <!--end::Javascript-->
    </body>
    <!--end::Body-->
</html>
