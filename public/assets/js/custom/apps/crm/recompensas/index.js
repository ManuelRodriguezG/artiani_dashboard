"use strict";
(function () {
    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-30
     * Proposito: consola CRM Recompensas read-only.
     * Impacto: permite revisar programas, cuentas y movimientos sin tocar saldos.
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
        document.getElementById("crm_recompensas_alerta").innerHTML =
            "<div class=\"alert alert-warning py-3\"><div class=\"fw-bold\">No se pudo cargar CRM Recompensas</div><div class=\"fs-7\">" +
            escapeHtml(error.message || String(error)) + "</div></div>";
    }

    function cargarTodo() {
        request("/crm/clientes_recompensas_detalle_erp?limite=30").then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            var data = response.depurar || {};
            renderResumen(data.resumen || {});
            renderProgramas(data.programas || []);
            renderCuentas(data.cuentas || []);
            renderMovimientos(data.movimientos || []);
        }).catch(mostrarAlerta);
    }

    function renderResumen(resumen) {
        setText("crm_rec_kpi_programas", resumen.programas_activos || 0);
        setText("crm_rec_kpi_cuentas", resumen.cuentas_activas || 0);
        setText("crm_rec_kpi_saldo", resumen.saldo_puntos_total || 0);
        setText("crm_rec_kpi_movimientos", resumen.movimientos_aplicados || 0);

        var html = "<div class=\"d-flex flex-wrap gap-2 mb-3\">" +
            badge("Elegibles: " + (resumen.elegibles_recompensas || 0), "success") +
            badge("Bloqueados: " + (resumen.bloqueados_recompensas || 0), (resumen.bloqueados_recompensas || 0) ? "warning" : "success") +
            badge("Legacy no elegible: " + (resumen.legacy_no_elegible || 0), (resumen.legacy_no_elegible || 0) ? "warning" : "success") +
            "</div>";
        html += "<div class=\"alert " + (resumen.requiere_ddl_recompensas ? "alert-warning" : "alert-success") + " py-3 mb-0\">" +
            "<div class=\"fw-bold\">" + (resumen.requiere_ddl_recompensas ? "DDL de recompensas pendiente" : "Recompensas disponibles") + "</div>" +
            "<div class=\"fs-8\">Read-only: no otorga puntos, no redime puntos y no modifica clientes.</div>" +
            "</div>";
        document.getElementById("crm_rec_elegibilidad").innerHTML = html;
    }

    function renderProgramas(programas) {
        document.getElementById("crm_rec_programas_tabla").innerHTML = programas.map(function (programa) {
            return "<tr><td><div class=\"fw-bold\">" + escapeHtml(programa.nombre || "") + "</div>" +
                "<div class=\"crm-code text-muted fs-8\">" + escapeHtml(programa.codigo || "") + "</div></td>" +
                "<td>" + badge(programa.tipo || "-", "primary") + "</td>" +
                "<td>" + badge(programa.estatus || "-", programa.estatus === "activo" ? "success" : "warning") + "</td></tr>";
        }).join("") || "<tr><td colspan=\"3\" class=\"text-center text-muted py-6\">Sin programas registrados.</td></tr>";
    }

    function renderCuentas(cuentas) {
        document.getElementById("crm_rec_cuentas_tabla").innerHTML = cuentas.map(function (cuenta) {
            return "<tr><td><div class=\"fw-bold\">" + escapeHtml(cuenta.nombre_publico || "Cliente CRM") + "</div>" +
                "<div class=\"crm-code text-muted fs-8\">" + escapeHtml(cuenta.codigo_cliente || "") + "</div></td>" +
                "<td><div class=\"fw-semibold\">" + escapeHtml(cuenta.programa_nombre || "") + "</div><div class=\"crm-code text-muted fs-8\">" + escapeHtml(cuenta.programa_codigo || "") + "</div></td>" +
                "<td>" + badge((cuenta.saldo_puntos || 0) + " pts", "info") + "<div class=\"text-muted fs-8\">" + escapeHtml(cuenta.nivel || "") + "</div></td>" +
                "<td class=\"text-end\"><a class=\"btn btn-sm btn-light-primary\" href=\"/crm/cliente/" + encodeURIComponent(cuenta.id_cliente_crm || "") + "\"><i class=\"bi bi-person-vcard\"></i> Ficha</a></td></tr>";
        }).join("") || "<tr><td colspan=\"4\" class=\"text-center text-muted py-6\">Sin cuentas de recompensas.</td></tr>";
    }

    function renderMovimientos(movimientos) {
        document.getElementById("crm_rec_movimientos_lista").innerHTML = movimientos.map(function (movimiento) {
            var tipo = movimiento.tipo === "redencion" ? "warning" : (movimiento.tipo === "caducidad" ? "danger" : "success");
            return "<div class=\"border-bottom pb-3 mb-3\">" +
                "<div class=\"d-flex justify-content-between gap-3\"><div class=\"fw-bold\">" + escapeHtml(movimiento.nombre_publico || "Cliente CRM") + "</div>" + badge(movimiento.tipo || "-", tipo) + "</div>" +
                "<div class=\"text-muted fs-8 mt-1\">" + escapeHtml(movimiento.programa_nombre || "") + " · " + escapeHtml(movimiento.puntos || 0) + " pts · saldo " + escapeHtml(movimiento.saldo_resultante || 0) + "</div>" +
                "<div class=\"crm-code text-muted fs-8 mt-1\">" + escapeHtml(movimiento.fecha_registro || "") + " · " + escapeHtml(movimiento.origen_id || "") + "</div>" +
                "</div>";
        }).join("") || "<div class=\"text-center text-muted py-8\">Sin movimientos registrados.</div>";
    }

    document.addEventListener("DOMContentLoaded", function () {
        cargarTodo();
        document.getElementById("crm_recompensas_recargar").addEventListener("click", cargarTodo);
    });
})();
