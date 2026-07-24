"use strict";
(function () {
    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-24
     * Proposito: consultar costos logisticos TMS en modo read-only.
     * Impacto: UI TMS Costos; separa ingreso/costo logistico del precio de producto.
     * Contrato: solo GET a reportes TMS; no recalcula ventas ni modifica servicios.
     */
    document.addEventListener("DOMContentLoaded", function () {
        var refrescar = document.getElementById("tms_costos_refrescar");
        if (refrescar) {
            refrescar.addEventListener("click", cargarCostos);
        }
        cargarCostos();
    });

    function cargarCostos() {
        getJson("/tms/reportes_resumen_erp").then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje || "No fue posible consultar costos TMS");
            }
            var depurar = response.depurar || {};
            if (depurar.schema_pendiente) {
                mostrarAlerta("Esquema TMS pendiente. Costos esta listo, pero aun no hay datos reales.", "info");
            } else {
                limpiarAlerta();
            }
            renderKpis(depurar.kpis || {});
            renderLista("tms_costos_tipo", depurar.por_tipo || []);
            renderLista("tms_costos_zona", depurar.por_zona || []);
        }).catch(function (error) {
            mostrarAlerta(error.message || String(error), "warning");
            renderKpis({});
            renderLista("tms_costos_tipo", []);
            renderLista("tms_costos_zona", []);
        });
    }

    function renderKpis(kpis) {
        var ingresos = Number(kpis.ingresos_logisticos || 0);
        var costo = Number(kpis.costo_real || 0);
        var bonificado = Number(kpis.monto_bonificado || 0);
        setText("tms_costos_ingresos", "$" + money(ingresos));
        setText("tms_costos_real", "$" + money(costo));
        setText("tms_costos_bonificado", "$" + money(bonificado));
        setText("tms_costos_margen", "$" + money(ingresos - costo - bonificado));
    }

    function renderLista(id, items) {
        var node = document.getElementById(id);
        if (!node) {
            return;
        }
        if (!items.length) {
            node.innerHTML = "<div class=\"text-muted\">Sin datos.</div>";
            return;
        }
        node.innerHTML = items.map(function (item) {
            return "<div class=\"d-flex justify-content-between border-bottom py-2\">" +
                "<span>" + escapeHtml(humanize(item.etiqueta)) + "</span>" +
                "<span class=\"fw-bold\">" + escapeHtml(numero(item.total)) + "</span>" +
                "</div>";
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

    function setText(id, value) {
        var node = document.getElementById(id);
        if (node) {
            node.textContent = String(value);
        }
    }

    function money(value) {
        return Number(value || 0).toFixed(2);
    }

    function numero(value) {
        return String(Math.round(Number(value || 0)));
    }

    function humanize(value) {
        return String(value || "").replace(/_/g, " ").replace(/\b\w/g, function (letter) { return letter.toUpperCase(); });
    }

    function mostrarAlerta(mensaje, tipo) {
        var alerta = document.getElementById("tms_costos_alerta");
        if (alerta) {
            alerta.innerHTML = "<div class=\"alert alert-" + escapeHtml(tipo || "info") + " py-3 mb-0\"><div class=\"fw-bold\">Costos TMS</div><div class=\"fs-7\">" + escapeHtml(mensaje) + "</div></div>";
        }
    }

    function limpiarAlerta() {
        var alerta = document.getElementById("tms_costos_alerta");
        if (alerta) {
            alerta.innerHTML = "";
        }
    }

    function escapeHtml(value) {
        var div = document.createElement("div");
        div.textContent = value == null ? "" : String(value);
        return div.innerHTML;
    }
})();
