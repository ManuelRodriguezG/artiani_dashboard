"use strict";

(function () {
    function request(url, data) {
        var headers = data ? {"Content-Type": "application/x-www-form-urlencoded; charset=UTF-8"} : {};
        if (data && window.ERP_CSRF_TOKEN) {
            headers["X-CSRF-Token"] = window.ERP_CSRF_TOKEN;
        }
        return fetch(url, {
            method: data ? "POST" : "GET",
            headers: headers,
            body: data ? new URLSearchParams(data).toString() : null,
            credentials: "same-origin"
        }).then(function (response) {
            return response.json();
        });
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-23
     * Proposito: guardar parametros SYS editables desde Administracion.
     * Impacto: Configuracion general e impresion POS; envia solo claves renderizadas por backend.
     */
    function guardarConfiguracion() {
        var parametros = {};
        document.querySelectorAll(".sys-config-input").forEach(function (input) {
            parametros[input.getAttribute("data-clave")] = input.value;
        });

        request("/sistema/configuracion_guardar", {
            parametros: JSON.stringify(parametros),
            motivo: document.getElementById("sys_config_motivo").value || ""
        }).then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje || "No fue posible guardar");
            }
            return Swal.fire({
                text: response.mensaje,
                icon: "success",
                confirmButtonText: "Aceptar"
            });
        }).then(function () {
            window.location.reload();
        }).catch(function (error) {
            Swal.fire({
                text: error.message || String(error),
                icon: "error",
                confirmButtonText: "Aceptar"
            });
        });
    }

    document.addEventListener("DOMContentLoaded", function () {
        var botonGuardar = document.getElementById("sys_config_guardar");
        if (botonGuardar) {
            botonGuardar.addEventListener("click", guardarConfiguracion);
        }
    });
})();
