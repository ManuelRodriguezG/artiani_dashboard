"use strict";
(function () {
    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-24
     * Proposito: consultar reportes read-only de TMS Delivery.
     * Impacto: UI TMS; muestra indicadores logisticos sin recalcular ventas.
     * Contrato: solo GET a `/tms/reportes_resumen_erp`; no modifica servicios.
     */
    document.addEventListener("DOMContentLoaded", function () {
        var refrescar = document.getElementById("tms_reportes_refrescar");
        if (refrescar) {
            refrescar.addEventListener("click", cargarReportes);
        }
        cargarReportes();
    });

    function cargarReportes() {
        var params = new URLSearchParams();
        appendIf(params, "desde", value("tms_reportes_desde"));
        appendIf(params, "hasta", value("tms_reportes_hasta"));
        fetch("/tms/reportes_resumen_erp?" + params.toString(), {
            method: "GET",
            credentials: "same-origin",
            headers: {"Accept": "application/json"}
        }).then(function (response) {
            if (!response.ok) {
                throw new Error("No fue posible consultar reportes TMS");
            }
            return response.json();
        }).then(renderReportes).catch(function (error) {
            mostrarAlerta(error.message || String(error), "warning");
            renderKpis({});
            renderLista("tms_rep_tipo", []);
            renderLista("tms_rep_resultado", []);
            renderLista("tms_rep_zona", []);
        });
    }

    function renderReportes(response) {
        if (response.error) {
            mostrarAlerta(response.mensaje || "No fue posible consultar reportes TMS", "warning");
        }
        var depurar = response.depurar || {};
        if (depurar.schema_pendiente) {
            mostrarAlerta("Esquema TMS pendiente. Los reportes estan listos, pero aun no hay datos reales.", "info");
        } else {
            limpiarAlerta();
        }
        renderKpis(depurar.kpis || {});
        renderLista("tms_rep_tipo", depurar.por_tipo || []);
        renderLista("tms_rep_resultado", depurar.por_resultado || []);
        renderLista("tms_rep_zona", depurar.por_zona || []);
    }

    function renderKpis(kpis) {
        setText("tms_rep_total", numero(kpis.total));
        setText("tms_rep_completas", numero(kpis.completas));
        setText("tms_rep_express", numero(kpis.express));
        setText("tms_rep_no_entregadas", numero(kpis.no_entregadas));
        setText("tms_rep_pendiente_cliente", numero(kpis.pendiente_cliente));
        setText("tms_rep_ingresos", "$" + dinero(kpis.ingresos_logisticos));
        setText("tms_rep_bonificado", "$" + dinero(kpis.monto_bonificado));
        setText("tms_rep_tiempo", numero(kpis.tiempo_promedio_minutos) + " min");
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

    function appendIf(params, key, val) {
        if (val) {
            params.append(key, val);
        }
    }

    function value(id) {
        var node = document.getElementById(id);
        return node ? String(node.value || "").trim() : "";
    }

    function setText(id, val) {
        var node = document.getElementById(id);
        if (node) {
            node.textContent = String(val);
        }
    }

    function numero(value) {
        return String(Math.round(Number(value || 0)));
    }

    function dinero(value) {
        return Number(value || 0).toFixed(2);
    }

    function humanize(value) {
        return String(value || "").replace(/_/g, " ").replace(/\b\w/g, function (letter) { return letter.toUpperCase(); });
    }

    function mostrarAlerta(mensaje, tipo) {
        var alerta = document.getElementById("tms_reportes_alerta");
        if (alerta) {
            alerta.innerHTML = "<div class=\"alert alert-" + escapeHtml(tipo || "info") + " py-3 mb-0\"><div class=\"fw-bold\">Reportes TMS</div><div class=\"fs-7\">" + escapeHtml(mensaje) + "</div></div>";
        }
    }

    function limpiarAlerta() {
        var alerta = document.getElementById("tms_reportes_alerta");
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
