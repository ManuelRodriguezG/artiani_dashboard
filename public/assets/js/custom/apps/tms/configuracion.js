"use strict";
(function () {
    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-24
     * Proposito: mostrar configuracion/contrato TMS en modo read-only.
     * Impacto: UI TMS Configuracion; documenta limites operativos del delivery.
     * Contrato: solo GET a catalogos y acciones; no guarda politicas.
     */
    document.addEventListener("DOMContentLoaded", function () {
        var refrescar = document.getElementById("tms_config_refrescar");
        if (refrescar) {
            refrescar.addEventListener("click", cargarConfiguracion);
        }
        cargarConfiguracion();
    });

    function cargarConfiguracion() {
        Promise.all([
            getJson("/tms/catalogos_erp"),
            getJson("/tms/acciones_contrato_erp")
        ]).then(function (responses) {
            var catalogos = responses[0];
            var acciones = responses[1];
            if (catalogos.error) {
                throw new Error(catalogos.mensaje || "No fue posible consultar catalogos TMS");
            }
            if (acciones.error) {
                throw new Error(acciones.mensaje || "No fue posible consultar acciones TMS");
            }
            renderCatalogos(catalogos.depurar || {});
            renderContrato((catalogos.depurar || {}).contrato || {});
            renderAcciones((acciones.depurar || {}).acciones || []);
            mostrarAlerta("Configuracion en modo lectura. La escritura de politicas queda para una fase posterior autorizada.", "info");
        }).catch(function (error) {
            mostrarAlerta(error.message || String(error), "warning");
            renderAcciones([]);
        });
    }

    function renderCatalogos(depurar) {
        renderBadges("tms_config_tipos", depurar.tipos_servicio || []);
        renderBadges("tms_config_cobros", depurar.estatus_cobro || []);
        renderBadges("tms_config_prioridades", depurar.prioridades || []);
    }

    function renderBadges(id, items) {
        var node = document.getElementById(id);
        if (!node) {
            return;
        }
        if (!items.length) {
            node.innerHTML = "<span class=\"text-muted\">Sin catalogo.</span>";
            return;
        }
        node.innerHTML = items.map(function (item) {
            var valor = typeof item === "string" ? item : item.valor;
            var texto = typeof item === "string" ? humanize(item) : item.texto;
            return "<span class=\"badge badge-light-primary\">" + escapeHtml(texto || valor) + "</span>";
        }).join("");
    }

    function renderContrato(contrato) {
        var node = document.getElementById("tms_config_contrato");
        if (!node) {
            return;
        }
        var items = Object.keys(contrato).map(function (key) {
            return "<div class=\"d-flex align-items-center justify-content-between border-bottom py-2\">" +
                "<span>" + escapeHtml(humanize(key)) + "</span>" +
                "<span class=\"badge badge-light-" + (contrato[key] ? "success" : "secondary") + "\">" + (contrato[key] ? "Si" : "No") + "</span>" +
                "</div>";
        });
        node.innerHTML = items.length ? items.join("") : "<div class=\"text-muted\">Sin contrato disponible.</div>";
    }

    function renderAcciones(items) {
        var body = document.getElementById("tms_config_acciones");
        if (!body) {
            return;
        }
        if (!items.length) {
            body.innerHTML = "<tr><td colspan=\"3\" class=\"text-center text-muted py-8\">Sin acciones TMS configuradas.</td></tr>";
            return;
        }
        body.innerHTML = items.map(function (item) {
            return "<tr>" +
                "<td class=\"ps-6 fw-semibold\">" + escapeHtml(humanize(item.accion)) + "</td>" +
                "<td><span class=\"badge badge-light-dark\">" + escapeHtml(item.permiso || "") + "</span></td>" +
                "<td class=\"fs-8 text-muted\">" + escapeHtml((item.requiere || []).map(humanize).join(", ")) + "</td>" +
                "</tr>";
        }).join("");
    }

    function getJson(url) {
        return fetch(url, {method: "GET", credentials: "same-origin", headers: {"Accept": "application/json"}}).then(function (response) {
            if (!response.ok) {
                throw new Error("Respuesta HTTP " + response.status);
            }
            return response.json();
        });
    }

    function humanize(value) {
        return String(value || "").replace(/_/g, " ").replace(/\b\w/g, function (letter) { return letter.toUpperCase(); });
    }

    function mostrarAlerta(mensaje, tipo) {
        var alerta = document.getElementById("tms_config_alerta");
        if (alerta) {
            alerta.innerHTML = "<div class=\"alert alert-" + escapeHtml(tipo || "info") + " py-3 mb-0\"><div class=\"fw-bold\">Configuracion TMS</div><div class=\"fs-7\">" + escapeHtml(mensaje) + "</div></div>";
        }
    }

    function escapeHtml(value) {
        var div = document.createElement("div");
        div.textContent = value == null ? "" : String(value);
        return div.innerHTML;
    }
})();
