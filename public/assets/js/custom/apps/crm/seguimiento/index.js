"use strict";
(function () {
    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-30
     * Proposito: consola CRM Seguimiento read-only.
     * Impacto: separa tareas/interacciones de la consola general de clientes.
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
        document.getElementById("crm_seguimiento_alerta").innerHTML =
            "<div class=\"alert alert-warning py-3\"><div class=\"fw-bold\">No se pudo cargar seguimiento CRM</div><div class=\"fs-7\">" +
            escapeHtml(error.message || String(error)) + "</div></div>";
    }

    function cargarTodo() {
        cargarTareas();
        cargarInteracciones();
    }

    function cargarTareas() {
        var estatus = document.getElementById("crm_seg_tareas_estatus").value || "pendiente";
        request("/crm/clientes_tareas_listar_erp?estatus=" + encodeURIComponent(estatus) + "&limite=50").then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            var data = response.depurar || {};
            renderTareasResumen(data.resumen || {}, response);
            renderTareasTabla(data.tareas || []);
        }).catch(mostrarAlerta);
    }

    function renderTareasResumen(resumen, response) {
        if (resumen.requiere_ddl_seguimiento) {
            document.getElementById("crm_seg_tareas_resumen").innerHTML =
                "<div class=\"alert alert-warning py-3 mb-0\"><div class=\"fw-bold\">" + escapeHtml(response.mensaje || "Seguimiento pendiente") + "</div>" +
                "<div class=\"fs-8\">DDL CRM Seguimiento pendiente.</div></div>";
            setText("crm_seg_kpi_pendientes", 0);
            setText("crm_seg_kpi_vencidas", 0);
            setText("crm_seg_kpi_alta", 0);
            return;
        }
        setText("crm_seg_kpi_pendientes", resumen.pendientes || 0);
        setText("crm_seg_kpi_vencidas", resumen.vencidas || 0);
        setText("crm_seg_kpi_alta", resumen.alta_prioridad || 0);
        document.getElementById("crm_seg_tareas_resumen").innerHTML =
            "<div class=\"d-flex flex-wrap gap-2\">" +
            badge("Pendientes: " + (resumen.pendientes || 0), "primary") +
            badge("Vencidas: " + (resumen.vencidas || 0), (resumen.vencidas || 0) ? "danger" : "success") +
            badge("Alta prioridad: " + (resumen.alta_prioridad || 0), (resumen.alta_prioridad || 0) ? "warning" : "success") +
            "</div>";
    }

    function renderTareasTabla(tareas) {
        document.getElementById("crm_seg_tareas_tabla").innerHTML = tareas.map(function (tarea) {
            var prioridadTipo = tarea.prioridad === "urgente" ? "danger" : (tarea.prioridad === "alta" ? "warning" : "light");
            var vencimientoTipo = tarea.vencida ? "danger" : "light";
            return "<tr><td><div class=\"fw-bold\">" + escapeHtml(tarea.titulo || "") + "</div>" +
                "<div class=\"d-flex flex-wrap gap-1 mt-2\">" + badge(tarea.tipo || "-", "primary") + badge(tarea.prioridad || "-", prioridadTipo) + badge(tarea.estatus || "-", "light") + "</div></td>" +
                "<td><div class=\"fw-semibold\">" + escapeHtml(tarea.nombre_publico || "Cliente CRM") + "</div><div class=\"crm-code text-muted fs-8\">" + escapeHtml(tarea.codigo_cliente || "") + "</div></td>" +
                "<td>" + badge(tarea.fecha_vencimiento || "Sin fecha", vencimientoTipo) + "</td>" +
                "<td class=\"text-end\"><a class=\"btn btn-sm btn-light-primary\" href=\"/crm/cliente/" + encodeURIComponent(tarea.id_cliente_crm || "") + "\"><i class=\"bi bi-person-vcard\"></i> Ficha</a></td></tr>";
        }).join("") || "<tr><td colspan=\"4\" class=\"text-center text-muted py-6\">Sin tareas para este filtro.</td></tr>";
    }

    function cargarInteracciones() {
        request("/crm/clientes_interacciones_listar_erp?limite=20").then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            var data = response.depurar || {};
            var interacciones = data.interacciones || [];
            setText("crm_seg_kpi_interacciones", (data.resumen || {}).total || interacciones.length || 0);
            renderInteracciones(interacciones);
        }).catch(mostrarAlerta);
    }

    function renderInteracciones(items) {
        document.getElementById("crm_seg_interacciones_lista").innerHTML = items.map(function (item) {
            return "<div class=\"border-bottom pb-3 mb-3\">" +
                "<div class=\"d-flex justify-content-between gap-3\"><div class=\"fw-bold\">" + escapeHtml(item.resumen || "") + "</div>" + badge(item.canal || "-", "primary") + "</div>" +
                "<div class=\"text-muted fs-8 mt-1\">" + escapeHtml(item.nombre_publico || "Cliente CRM") + " · " + escapeHtml(item.tipo || "-") + " · " + escapeHtml(item.resultado || "-") + "</div>" +
                "<div class=\"crm-code text-muted fs-8 mt-1\">" + escapeHtml(item.fecha_interaccion || "") + " · " + escapeHtml(item.origen_id || "") + "</div>" +
                "</div>";
        }).join("") || "<div class=\"text-center text-muted py-8\">Sin interacciones registradas.</div>";
    }

    document.addEventListener("DOMContentLoaded", function () {
        cargarTodo();
        document.getElementById("crm_seguimiento_recargar").addEventListener("click", cargarTodo);
        document.getElementById("crm_seg_tareas_estatus").addEventListener("change", cargarTareas);
    });
})();
