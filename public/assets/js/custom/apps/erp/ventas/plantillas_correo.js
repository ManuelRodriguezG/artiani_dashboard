"use strict";
(function () {
    function $(id) { return document.getElementById(id); }

    function request(url) {
        return fetch(url, {credentials: "same-origin", headers: {"Accept": "application/json"}}).then(function (response) {
            return response.json().then(function (data) {
                if (!response.ok || data.error) { throw new Error(data.mensaje || "No fue posible cargar las plantillas"); }
                return data;
            });
        });
    }

    function copiar(id) {
        var campo = $(id);
        if (!campo) { return; }
        var texto = campo.value || "";
        var confirmar = function () { $("pc_alerta").innerHTML = "<div class=\"alert alert-success\">Texto copiado al portapapeles.</div>"; };
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(texto).then(confirmar).catch(function () { campo.focus(); campo.select(); document.execCommand("copy"); confirmar(); });
            return;
        }
        campo.focus();
        campo.select();
        document.execCommand("copy");
        confirmar();
    }

    function cargar() {
        request("/ventas/facturacion_correo_plantilla_erp").then(function (response) {
            var data = response.depurar || {};
            var plantillas = data.plantillas || {};
            var factura = plantillas.factura || {asunto: data.asunto || "", cuerpo: data.cuerpo || ""};
            var cotizacion = plantillas.cotizacion || {};
            $("pc_factura_asunto").value = factura.asunto || "";
            $("pc_factura_cuerpo").value = factura.cuerpo || "";
            $("pc_cotizacion_asunto").value = cotizacion.asunto || "";
            $("pc_cotizacion_cuerpo").value = cotizacion.cuerpo || "";
            $("pc_estado").textContent = "Plantillas listas para copiar en Gmail.";
            var empresa = data.empresa || {};
            $("pc_empresa_nombre").textContent = empresa.nombre || "";
            var logo = String(empresa.logo_url || "").trim();
            if (/^(https?:\/\/|\/|assets\/)/i.test(logo)) {
                $("pc_logo").src = logo.charAt(0) === "/" ? logo : "/" + logo.replace(/^\/+/, "");
                $("pc_logo").classList.remove("d-none");
            }
        }).catch(function (error) {
            $("pc_estado").textContent = error.message || "Plantillas no disponibles.";
        });
    }

    document.addEventListener("DOMContentLoaded", function () {
        $("pc_factura_copiar_asunto").addEventListener("click", function () { copiar("pc_factura_asunto"); });
        $("pc_factura_copiar_cuerpo").addEventListener("click", function () { copiar("pc_factura_cuerpo"); });
        $("pc_cotizacion_copiar_asunto").addEventListener("click", function () { copiar("pc_cotizacion_asunto"); });
        $("pc_cotizacion_copiar_cuerpo").addEventListener("click", function () { copiar("pc_cotizacion_cuerpo"); });
        cargar();
    });
}());
