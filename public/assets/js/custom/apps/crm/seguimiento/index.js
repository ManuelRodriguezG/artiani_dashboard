"use strict";
(function () {
    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-30
     * Proposito: consola CRM Seguimiento con lectura y preflight operativo.
     * Impacto: separa tareas/interacciones de la consola general de clientes.
     */
    var permisos = window.CRM_SEGUIMIENTO_PERMISOS || {};
    var puedeOperar = permisos.operar === true;

    function request(url) {
        return fetch(url, {credentials: "same-origin"}).then(function (response) { return response.json(); });
    }

    function requestPost(url, data) {
        return fetch(url, {
            credentials: "same-origin",
            method: "POST",
            headers: {"Content-Type": "application/x-www-form-urlencoded; charset=UTF-8", "X-CSRF-Token": window.ERP_CSRF_TOKEN || ""},
            body: new URLSearchParams(data || {}).toString()
        }).then(function (response) { return response.json(); });
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

    function value(id) {
        var node = document.getElementById(id);
        return node ? node.value : "";
    }

    function badge(text, type) {
        return "<span class=\"badge badge-light-" + escapeHtml(type || "primary") + "\">" + escapeHtml(text || "") + "</span>";
    }

    function mostrarAlerta(error) {
        document.getElementById("crm_seguimiento_alerta").innerHTML =
            "<div class=\"alert alert-warning py-3\"><div class=\"fw-bold\">No se pudo cargar seguimiento CRM</div><div class=\"fs-7\">" +
            escapeHtml(error.message || String(error)) + "</div></div>";
    }

    function renderDryRun(targetId, response) {
        var node = document.getElementById(targetId);
        if (!node) { return; }
        var data = response.depurar || {};
        var puedeGuardar = data.puede_guardar === true;
        var bloqueos = data.bloqueos || [];
        var avisos = data.avisos || [];
        var tipo = response.error ? "danger" : (puedeGuardar ? "success" : "warning");
        var html = "<div class=\"alert alert-" + tipo + " py-3 mb-3\">" +
            "<div class=\"fw-bold\">" + escapeHtml(response.mensaje || "Validacion CRM") + "</div>" +
            "<div class=\"fs-8\">" + (puedeGuardar ? "Preflight correcto; el guardado real requiere token y respaldo." : "Hay bloqueos o avisos que revisar antes de pedir autorizacion.") + "</div>" +
            "</div>";
        if (bloqueos.length) {
            html += "<div class=\"mb-3\"><div class=\"fw-semibold fs-8 text-uppercase text-muted mb-2\">Bloqueos</div>" +
                bloqueos.map(function (item) { return badge(item, "warning"); }).join(" ") + "</div>";
        }
        if (avisos.length) {
            html += "<div class=\"mb-3\"><div class=\"fw-semibold fs-8 text-uppercase text-muted mb-2\">Avisos</div>" +
                avisos.map(function (item) { return badge(item, "info"); }).join(" ") + "</div>";
        }
        html += "<pre class=\"bg-light p-3 rounded fs-8 crm-code mb-0\">" + escapeHtml(JSON.stringify(data.cambio_propuesto || data.interaccion_propuesta || data, null, 2)) + "</pre>";
        node.innerHTML = html;
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
            var acciones = "<a class=\"btn btn-sm btn-light-primary\" href=\"/crm/cliente/" + encodeURIComponent(tarea.id_cliente_crm || "") + "\"><i class=\"bi bi-person-vcard\"></i> Ficha</a>";
            if (puedeOperar && tarea.id_cliente_tarea && tarea.estatus !== "cerrada" && tarea.estatus !== "cancelada") {
                acciones += " <button class=\"btn btn-sm btn-light\" type=\"button\" data-crm-tarea=\"" + escapeHtml(tarea.id_cliente_tarea) + "\"><i class=\"bi bi-check2-square\"></i></button>";
            }
            return "<tr><td><div class=\"fw-bold\">" + escapeHtml(tarea.titulo || "") + "</div>" +
                "<div class=\"d-flex flex-wrap gap-1 mt-2\">" + badge(tarea.tipo || "-", "primary") + badge(tarea.prioridad || "-", prioridadTipo) + badge(tarea.estatus || "-", "light") + "</div></td>" +
                "<td><div class=\"fw-semibold\">" + escapeHtml(tarea.nombre_publico || "Cliente CRM") + "</div><div class=\"crm-code text-muted fs-8\">" + escapeHtml(tarea.codigo_cliente || "") + "</div></td>" +
                "<td>" + badge(tarea.fecha_vencimiento || "Sin fecha", vencimientoTipo) + "</td>" +
                "<td class=\"text-end\">" + acciones + "</td></tr>";
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

    function validarInteraccion() {
        requestPost("/crm/cliente_interaccion_dryrun_erp", {
            id_cliente_crm: value("crm_seg_int_cliente"),
            tipo: value("crm_seg_int_tipo"),
            canal: value("crm_seg_int_canal"),
            direccion: value("crm_seg_int_direccion"),
            resultado: value("crm_seg_int_resultado"),
            fecha_interaccion: value("crm_seg_int_fecha"),
            resumen: value("crm_seg_int_resumen"),
            detalle: value("crm_seg_int_detalle"),
            origen_tipo: "crm_seguimiento"
        }).then(function (response) {
            renderDryRun("crm_seg_int_resultado_box", response);
        }).catch(mostrarAlerta);
    }

    function validarEstatusTarea() {
        requestPost("/crm/cliente_tarea_estatus_dryrun_erp", {
            id_cliente_tarea: value("crm_seg_est_tarea"),
            estatus: value("crm_seg_est_estatus"),
            resultado_cierre: value("crm_seg_est_resultado"),
            nota: value("crm_seg_est_nota")
        }).then(function (response) {
            renderDryRun("crm_seg_est_resultado_box", response);
        }).catch(mostrarAlerta);
    }

    document.addEventListener("DOMContentLoaded", function () {
        cargarTodo();
        document.getElementById("crm_seguimiento_recargar").addEventListener("click", cargarTodo);
        document.getElementById("crm_seg_tareas_estatus").addEventListener("change", cargarTareas);
        document.getElementById("crm_seg_tareas_tabla").addEventListener("click", function (event) {
            var button = event.target.closest("[data-crm-tarea]");
            if (button) {
                var target = document.getElementById("crm_seg_est_tarea");
                if (target) {
                    target.value = button.getAttribute("data-crm-tarea") || "";
                    target.focus();
                }
            }
        });
        if (puedeOperar) {
            document.getElementById("crm_seg_int_validar").addEventListener("click", validarInteraccion);
            document.getElementById("crm_seg_est_validar").addEventListener("click", validarEstatusTarea);
        }
    });
})();
