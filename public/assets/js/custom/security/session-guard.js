"use strict";

(function () {
    var modal;
    var form;
    var submit;
    var errorBox;
    var pendingJqueryRequests = [];
    var waitingResolvers = [];
    var showing = false;

    function init() {
        var modalElement = document.getElementById("erp_session_modal");
        form = document.getElementById("erp_session_form");
        submit = document.getElementById("erp_session_submit");
        errorBox = document.getElementById("erp_session_error");

        if (!modalElement || !form || typeof bootstrap === "undefined") {
            return;
        }

        modal = new bootstrap.Modal(modalElement, {
            backdrop: "static",
            keyboard: false
        });

        form.addEventListener("submit", reauthenticate);
        installJqueryGuard();
        installFetchGuard();
        installFormGuard();

        window.setInterval(checkSession, 60000);
    }

    function showModal() {
        if (!modal || showing) {
            return;
        }
        showing = true;
        errorBox.classList.add("d-none");
        errorBox.textContent = "";
        modal.show();
        window.setTimeout(function () {
            var celular = form.querySelector("[name='celular']");
            if (celular) {
                celular.focus();
            }
        }, 250);
    }

    function waitForReauthentication() {
        showModal();
        return new Promise(function (resolve) {
            waitingResolvers.push(resolve);
        });
    }

    function reauthenticate(event) {
        event.preventDefault();
        submit.setAttribute("data-kt-indicator", "on");
        submit.disabled = true;
        errorBox.classList.add("d-none");

        var data = new URLSearchParams(new FormData(form));
        window.fetch("/autenticacion/reautenticar_session", {
            method: "POST",
            headers: {"Content-Type": "application/x-www-form-urlencoded; charset=UTF-8"},
            body: data.toString(),
            credentials: "same-origin",
            erpSkipSessionGuard: true
        }).then(function (response) {
            return response.json();
        }).then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje || "No fue posible reactivar la sesion");
            }

            if (response.depurar && response.depurar.csrf_token) {
                updateCsrfToken(response.depurar.csrf_token);
            }
            form.querySelector("[name='contrasenia']").value = "";
            modal.hide();
            showing = false;

            var requests = pendingJqueryRequests.splice(0);
            requests.forEach(function (settings) {
                settings.headers = settings.headers || {};
                settings.headers["X-CSRF-Token"] = window.ERP_CSRF_TOKEN || "";
                window.jQuery.ajax(settings);
            });

            waitingResolvers.splice(0).forEach(function (resolve) {
                resolve();
            });
        }).catch(function (error) {
            errorBox.textContent = error.message;
            errorBox.classList.remove("d-none");
        }).finally(function () {
            submit.removeAttribute("data-kt-indicator");
            submit.disabled = false;
        });
    }

    function installJqueryGuard() {
        if (!window.jQuery) {
            return;
        }

        window.jQuery.ajaxSetup({
            headers: {
                "X-CSRF-Token": window.ERP_CSRF_TOKEN || "",
                "X-Requested-With": "XMLHttpRequest"
            }
        });

        window.jQuery(document).ajaxError(function (_event, jqxhr, settings) {
            if (jqxhr.status === 419) {
                window.Swal ? Swal.fire({
                    text: "La solicitud de seguridad expiro. Recarga la pagina antes de continuar.",
                    icon: "warning",
                    confirmButtonText: "Recargar"
                }).then(function () {
                    window.location.reload();
                }) : window.location.reload();
                return;
            }
            if (jqxhr.status !== 401 || settings.url.indexOf("/autenticacion/") === 0) {
                return;
            }
            pendingJqueryRequests.push(settings);
            showModal();
        });
    }

    function installFetchGuard() {
        if (!window.fetch || window.fetch.__erpSessionGuard) {
            return;
        }

        var originalFetch = window.fetch.bind(window);
        var guardedFetch = function (input, init) {
            init = init || {};
            if (init.erpSkipSessionGuard) {
                delete init.erpSkipSessionGuard;
                return originalFetch(input, init);
            }

            init.headers = new Headers(init.headers || {});
            init.headers.set("X-Requested-With", "XMLHttpRequest");
            init.headers.set("Accept", "application/json");
            init.headers.set("X-CSRF-Token", window.ERP_CSRF_TOKEN || "");

            return originalFetch(input, init).then(function (response) {
                if (response.status === 419) {
                    window.location.reload();
                    return response;
                }
                if (response.status !== 401) {
                    return response;
                }
                return waitForReauthentication().then(function () {
                    init.headers.set("X-CSRF-Token", window.ERP_CSRF_TOKEN || "");
                    return originalFetch(input, init);
                });
            });
        };
        guardedFetch.__erpSessionGuard = true;
        window.fetch = guardedFetch;
    }

    function installFormGuard() {
        document.querySelectorAll("form").forEach(function (currentForm) {
            if ((currentForm.method || "get").toLowerCase() !== "post") {
                return;
            }
            var input = currentForm.querySelector("input[name='_csrf']");
            if (!input) {
                input = document.createElement("input");
                input.type = "hidden";
                input.name = "_csrf";
                currentForm.appendChild(input);
            }
            input.value = window.ERP_CSRF_TOKEN || "";

            if (currentForm.id === "erp_session_form" || currentForm.dataset.erpAjax === "true") {
                return;
            }
            currentForm.addEventListener("submit", function (event) {
                if (currentForm.dataset.erpSessionVerified === "1") {
                    delete currentForm.dataset.erpSessionVerified;
                    return;
                }

                event.preventDefault();
                var submitter = event.submitter;
                window.fetch("/autenticacion/estado_session", {
                    credentials: "same-origin"
                }).then(function (response) {
                    if (!response.ok) {
                        throw new Error("No fue posible validar la sesion");
                    }
                    updateCsrfToken(window.ERP_CSRF_TOKEN || "");
                    currentForm.dataset.erpSessionVerified = "1";
                    if (typeof currentForm.requestSubmit === "function") {
                        currentForm.requestSubmit(submitter);
                    } else {
                        HTMLFormElement.prototype.submit.call(currentForm);
                    }
                }).catch(function (error) {
                    errorBox.textContent = error.message;
                    errorBox.classList.remove("d-none");
                    showModal();
                });
            });
        });
    }

    function updateCsrfToken(token) {
        window.ERP_CSRF_TOKEN = token;
        if (window.jQuery) {
            window.jQuery.ajaxSetup({
                headers: {
                    "X-CSRF-Token": token,
                    "X-Requested-With": "XMLHttpRequest"
                }
            });
        }
        document.querySelectorAll("input[name='_csrf']").forEach(function (input) {
            input.value = token;
        });
    }

    function checkSession() {
        window.fetch("/autenticacion/estado_session", {
            credentials: "same-origin"
        }).then(function (response) {
            if (response.status === 401) {
                showModal();
            }
        }).catch(function () {
            // Los fallos de red no deben bloquear el trabajo del usuario.
        });
    }

    document.addEventListener("DOMContentLoaded", init);
})();
