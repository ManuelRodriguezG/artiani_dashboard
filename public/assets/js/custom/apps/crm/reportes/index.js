"use strict";
(function () {
    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-30
     * Proposito: tablero CRM Reportes read-only.
     * Impacto: separa indicadores de la consola principal de clientes.
     */
    function request(url) {
        return fetch(url, {credentials: "same-origin"}).then(function (response) { return response.json(); });
    }

    function escapeHtml(value) {
        var div = document.createElement("div");
        div.textContent = value == null ? "" : String(value);
        return div.innerHTML;
    }

    function setText(id, value) {
        var node = document.getElementById(id);
        if (node) {
            node.textContent = value == null ? "0" : String(value);
        }
    }

    function badge(text, type) {
        return "<span class=\"badge badge-light-" + escapeHtml(type || "primary") + "\">" + escapeHtml(text || "") + "</span>";
    }

    function mostrarAlerta(error) {
        document.getElementById("crm_reportes_alerta").innerHTML =
            "<div class=\"alert alert-warning py-3\"><div class=\"fw-bold\">No se pudo cargar CRM Reportes</div><div class=\"fs-7\">" +
            escapeHtml(error.message || String(error)) + "</div></div>";
    }

    function cargarTodo() {
        request("/crm/clientes_reportes_operativos_erp").then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            var data = response.depurar || {};
            renderResumen(data.resumen || {});
            renderIndicadores(data.indicadores || []);
            renderEstado(data.resumen || {});
        }).catch(mostrarAlerta);
    }

    function renderResumen(resumen) {
        setText("crm_rep_kpi_identificables", resumen.identificables_pos || 0);
        setText("crm_rep_kpi_contacto", resumen.pendientes_contacto || 0);
        setText("crm_rep_kpi_consentimiento", resumen.pendientes_consentimiento || 0);
        setText("crm_rep_kpi_bloqueados", resumen.bloqueados_comercial || 0);
        setText("crm_rep_kpi_recompensas", resumen.elegibles_recompensas || 0);
        setText("crm_rep_kpi_garantia", resumen.elegibles_garantia_extendida || 0);
    }

    function renderIndicadores(indicadores) {
        document.getElementById("crm_rep_indicadores").innerHTML = indicadores.map(function (item) {
            var total = Number(item.total || 0);
            var valor = Number(item.valor || 0);
            var porcentaje = total > 0 ? Math.round((valor / total) * 100) : 0;
            var tipo = item.riesgo === "alto" ? "danger" : (item.riesgo === "medio" ? "warning" : "success");
            return "<div class=\"border-bottom pb-4 mb-4\">" +
                "<div class=\"d-flex justify-content-between gap-3 mb-2\">" +
                "<div><div class=\"fw-bold\">" + escapeHtml(item.titulo || "") + "</div>" +
                "<div class=\"text-muted fs-8\">Valor " + escapeHtml(valor) + " de " + escapeHtml(total) + "</div></div>" +
                badge(item.riesgo || "bajo", tipo) +
                "</div>" +
                "<div class=\"crm-meter\"><span style=\"width:" + Math.max(0, Math.min(100, porcentaje)) + "%\"></span></div>" +
                "<div class=\"text-muted fs-8 mt-2\">" + porcentaje + "%</div>" +
                "</div>";
        }).join("") || "<div class=\"text-center text-muted py-8\">Sin indicadores disponibles.</div>";
    }

    function renderEstado(resumen) {
        var disponible = !resumen.requiere_ddl_comercial;
        var html = "<div class=\"alert " + (disponible ? "alert-success" : "alert-warning") + " py-3\">" +
            "<div class=\"fw-bold\">" + (disponible ? "Condiciones comerciales disponibles" : "Condiciones comerciales aun simuladas") + "</div>" +
            "<div class=\"fs-8\">Read-only: no crea tareas, no modifica clientes y no usa legacy para campanas.</div>" +
            "</div>";
        html += "<div class=\"d-flex flex-wrap gap-2\">" +
            badge("Identificables POS: " + (resumen.identificables_pos || 0), "primary") +
            badge("Contacto pendiente: " + (resumen.pendientes_contacto || 0), (resumen.pendientes_contacto || 0) ? "warning" : "success") +
            badge("Consentimiento pendiente: " + (resumen.pendientes_consentimiento || 0), (resumen.pendientes_consentimiento || 0) ? "warning" : "success") +
            badge("Bloqueados: " + (resumen.bloqueados_comercial || 0), (resumen.bloqueados_comercial || 0) ? "danger" : "success") +
            "</div>";
        document.getElementById("crm_rep_estado").innerHTML = html;
    }

    document.addEventListener("DOMContentLoaded", function () {
        cargarTodo();
        document.getElementById("crm_reportes_recargar").addEventListener("click", cargarTodo);
    });
})();
