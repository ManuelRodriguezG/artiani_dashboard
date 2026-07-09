<!DOCTYPE html>
<!--
Author: Keenthemes
Product Name: Metronic | Bootstrap HTML, VueJS, React, Angular, Asp.Net Core, Blazor, Django, Flask & Laravel Admin Dashboard Theme
Purchase: https://1.envato.market/EA4JP
Website: http://www.keenthemes.com
Contact: support@keenthemes.com
Follow: www.twitter.com/keenthemes
Dribbble: www.dribbble.com/keenthemes
Like: www.facebook.com/keenthemes
License: For each use you must have a valid license purchased only from above link in order to legally use the theme for your project.
-->
<html lang="en">
    <!--begin::Head-->
    <head><base href="../../../"/>
        <title>Metronic - the world's #1 selling Bootstrap Admin Theme Ecosystem for HTML, Vue, React, Angular & Laravel by Keenthemes</title>
        <meta charset="utf-8" />
        <meta name="description" content="The most advanced Bootstrap Admin Theme on Themeforest trusted by 100,000 beginners and professionals. Multi-demo, Dark Mode, RTL support and complete React, Angular, Vue, Asp.Net Core, Blazor, Django, Flask & Laravel versions. Grab your copy now and get life-time updates for free." />
        <meta name="keywords" content="metronic, bootstrap, bootstrap 5, angular, VueJs, React, Asp.Net Core, Blazor, Django, Flask & Laravel starter kits, admin themes, web design, figma, web development, free templates, free admin themes, bootstrap theme, bootstrap template, bootstrap dashboard, bootstrap dak mode, bootstrap button, bootstrap datepicker, bootstrap timepicker, fullcalendar, datatables, flaticon" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <meta property="og:locale" content="en_US" />
        <meta property="og:type" content="article" />
        <meta property="og:title" content="Metronic | Bootstrap HTML, VueJS, React, Angular, Asp.Net Core, Blazor, Django, Flask & Laravel Admin Dashboard Theme" />
        <meta property="og:url" content="https://keenthemes.com/metronic" />
        <meta property="og:site_name" content="Keenthemes | Metronic" />
        <link rel="canonical" href="https://preview.keenthemes.com/metronic8" />
        <link rel="shortcut icon" href="assets/media/logos/favicon.ico" />
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
                <a href="../../demo1/dist/index.html" class="d-block d-lg-none mx-auto py-20">
                    <img alt="Logo" src="assets/media/logos/default.svg" class="theme-light-show h-25px" />
                    <img alt="Logo" src="assets/media/logos/default-dark.svg" class="theme-dark-show h-25px" />
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
                                        <!--begin::Title-->
                                        <h1 class="text-dark mb-3 fs-3x" data-kt-translate="sign-in-title">Sign In</h1>
                                        <!--end::Title-->
                                        <!--begin::Text-->
                                        <div class="text-gray-400 fw-semibold fs-6" data-kt-translate="general-desc">Get unlimited access & earn money</div>
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
        <script src="assets/js/custom/authentication/sign-in/i18n.js"></script>
        <!--end::Custom Javascript-->
        <!--end::Javascript-->
    </body>
    <!--end::Body-->
</html>
