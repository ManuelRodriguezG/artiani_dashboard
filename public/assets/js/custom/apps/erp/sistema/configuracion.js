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

    function requestArchivo(url, formData) {
        var headers = {};
        if (window.ERP_CSRF_TOKEN) {
            headers["X-CSRF-Token"] = window.ERP_CSRF_TOKEN;
        }
        return fetch(url, {
            method: "POST",
            headers: headers,
            body: formData,
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

    /**
     * IA: Codex GPT-5 | Fecha: 2026-08-08
     * Proposito: subir logo principal o compacto desde la consola SYS.
     * Impacto: Branding global; refresca layout para usar el nuevo archivo publico.
     */
    function subirLogo() {
        var inputArchivo = document.getElementById("sys_config_logo_archivo");
        var tipoLogo = document.getElementById("sys_config_logo_tipo");
        if (!inputArchivo || !inputArchivo.files || !inputArchivo.files.length) {
            Swal.fire({
                text: "Selecciona un archivo de logo",
                icon: "warning",
                confirmButtonText: "Aceptar"
            });
            return;
        }

        var formData = new FormData();
        formData.append("logo", inputArchivo.files[0]);
        formData.append("tipo_logo", tipoLogo ? tipoLogo.value : "principal");
        formData.append("motivo", document.getElementById("sys_config_motivo").value || "Actualizacion de logo del panel");

        requestArchivo("/sistema/configuracion_logo_subir", formData).then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje || "No fue posible subir el logo");
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
        var botonLogo = document.getElementById("sys_config_logo_subir");
        if (botonLogo) {
            botonLogo.addEventListener("click", subirLogo);
        }
    });
})();
