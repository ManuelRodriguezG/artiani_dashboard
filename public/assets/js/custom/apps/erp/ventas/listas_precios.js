"use strict";
(function () {
    var estado = {listas: [], conflictos: [], listaSeleccionada: null};

    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-12
     * Proposito: consultar modulo read-only de Listas de precios.
     * Impacto: permite auditar precios/listas antes de habilitar CRUD real.
     */
    function request(url) {
        return fetch(url, {credentials: "same-origin"}).then(function (response) { return response.json(); });
    }

    function postRequest(url, data) {
        return fetch(url, {
            method: "POST",
            headers: {"Content-Type": "application/x-www-form-urlencoded; charset=UTF-8", "X-CSRF-Token": window.ERP_CSRF_TOKEN || ""},
            body: new URLSearchParams(data).toString(),
            credentials: "same-origin"
        }).then(function (response) { return response.json(); });
    }

    function escapeHtml(value) {
        var div = document.createElement("div");
        div.textContent = value == null ? "" : String(value);
        return div.innerHTML;
    }

    function dinero(value) {
        return new Intl.NumberFormat("es-MX", {style: "currency", currency: "MXN"}).format(Number(value || 0));
    }

    function filtros() {
        return {
            q: document.getElementById("lp_filtro_q").value.trim(),
            estatus: document.getElementById("lp_filtro_estatus").value,
            canal: document.getElementById("lp_filtro_canal").value,
            id_almacen: document.getElementById("lp_filtro_almacen").value.trim(),
            limite: "80"
        };
    }

    function cargarResumen() {
        request("/comercial/listas_precios_resumen_erp?" + new URLSearchParams(filtros()).toString()).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            var data = response.depurar || {};
            estado.listas = data.listas || [];
            estado.conflictos = data.conflictos || [];
            renderKpis(data.kpis || {});
            renderSchema(data.schema || {});
            renderListas(estado.listas);
            renderConflictos(estado.conflictos);
        }).catch(mostrarError);
    }

    function renderKpis(kpis) {
        document.getElementById("lp_kpi_activas").textContent = Number(kpis.listas_activas || 0);
        document.getElementById("lp_kpi_total").textContent = Number(kpis.listas_total || 0);
        document.getElementById("lp_kpi_detalles").textContent = Number(kpis.detalles_activos || 0);
        document.getElementById("lp_kpi_asignaciones").textContent = Number(kpis.asignaciones_activas || 0);
    }

    function renderSchema(schema) {
        var avisos = [];
        if (!schema.listas || !schema.detalle) {
            avisos.push("Faltan tablas base de listas de precios.");
        }
        if (!schema.cliente_crm_columna) {
            avisos.push("Falta columna id_cliente_crm en asignaciones; el backend usa compatibilidad temporal.");
        }
        document.getElementById("lp_alerta").innerHTML = avisos.length
            ? "<div class=\"alert alert-warning py-3\"><div class=\"fw-bold\">Listas en preparacion</div><ul class=\"mb-0 ps-4 fs-7\">" + avisos.map(function (item) { return "<li>" + escapeHtml(item) + "</li>"; }).join("") + "</ul></div>"
            : "<div class=\"alert alert-success py-3\"><div class=\"fw-bold\">Contrato CRM/listas listo</div><div class=\"fs-7\">La consulta read-only puede auditar asignaciones por cliente CRM.</div></div>";
    }

    function renderListas(listas) {
        document.getElementById("lp_listas").innerHTML = (listas || []).map(function (item) {
            var rango = item.precio_min == null ? "-" : dinero(item.precio_min) + (Number(item.precio_max || 0) !== Number(item.precio_min || 0) ? " - " + dinero(item.precio_max) : "");
            var canal = item.canal || "general";
            var almacen = item.id_almacen && Number(item.id_almacen) > 0 ? "Alm. " + item.id_almacen : "Todos";
            return "<tr>" +
                "<td><div class=\"fw-bold\">" + escapeHtml(item.codigo || "") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.nombre || "") + "</div></td>" +
                "<td><span class=\"badge badge-light-primary me-1\">" + escapeHtml(canal) + "</span><span class=\"badge badge-light\">" + escapeHtml(almacen) + "</span><div class=\"text-muted fs-8\">Prioridad " + escapeHtml(item.prioridad || "100") + "</div></td>" +
                "<td><div class=\"fw-semibold\">" + escapeHtml(item.detalles_activos || 0) + " activos</div><div class=\"text-muted fs-8\">" + escapeHtml(item.asignaciones_activas || 0) + " clientes</div></td>" +
                "<td>" + escapeHtml(rango) + "</td>" +
                "<td>" + badgeEstatus(item.estatus || "") + "</td>" +
                "<td class=\"text-end\"><button class=\"btn btn-sm btn-light-primary\" type=\"button\" data-lp-consultar=\"" + escapeHtml(item.id_lista_precio || "") + "\"><i class=\"bi bi-eye\"></i></button></td>" +
            "</tr>";
        }).join("") || "<tr><td colspan=\"6\" class=\"text-center text-muted py-6\">Sin listas para los filtros actuales</td></tr>";

        document.querySelectorAll("[data-lp-consultar]").forEach(function (boton) {
            boton.addEventListener("click", function () {
                consultarLista(boton.getAttribute("data-lp-consultar"));
            });
        });
    }

    function badgeEstatus(estatus) {
        var clases = {
            activa: "badge-light-success",
            borrador: "badge-light",
            pausada: "badge-light-warning",
            cancelada: "badge-light-danger"
        };
        return "<span class=\"badge " + (clases[estatus] || "badge-light") + "\">" + escapeHtml(estatus || "-") + "</span>";
    }

    function consultarLista(idLista) {
        estado.listaSeleccionada = idLista;
        var auditoriaLista = document.getElementById("lp_auditoria_lista");
        if (auditoriaLista) {
            auditoriaLista.value = idLista;
        }
        request("/comercial/listas_precios_consultar_erp?id_lista_precio=" + encodeURIComponent(idLista)).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            renderDetalle(response.depurar || {});
        }).catch(function (error) {
            document.getElementById("lp_detalle").innerHTML = "<div class=\"alert alert-danger py-3\">" + escapeHtml(error.message || String(error)) + "</div>";
        });
    }

    function renderDetalle(data) {
        var lista = data.lista || {};
        var detalles = data.detalles || [];
        var asignaciones = data.asignaciones || [];
        var html = "<div class=\"mb-3\"><div class=\"fw-bold\">" + escapeHtml(lista.codigo || "") + " | " + escapeHtml(lista.nombre || "") + "</div>" +
            "<div class=\"text-muted fs-8\">Canal " + escapeHtml(lista.canal || "general") + " | Almacen " + escapeHtml(lista.id_almacen || "todos") + " | Prioridad " + escapeHtml(lista.prioridad || "") + "</div></div>";
        html += "<div class=\"fw-semibold mb-2\">Detalles</div><div class=\"table-responsive lp-detail-wrap mb-4\"><table class=\"table table-sm align-middle mb-0\"><thead><tr class=\"text-muted fs-8 text-uppercase\"><th>SKU/producto</th><th>Precio</th><th>Vigencia</th><th>Estatus</th></tr></thead><tbody>";
        html += detalles.map(function (item) {
            return "<tr><td><div class=\"fw-semibold\">" + escapeHtml(item.sku || item.codigo_producto || item.id_sku || item.id_producto_erp || "-") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.nombre_sku || item.producto || "") + "</div></td>" +
                "<td>" + dinero(item.precio) + " <span class=\"text-muted fs-8\">" + escapeHtml(item.moneda || "MXN") + "</span></td>" +
                "<td><div class=\"fs-8\">" + escapeHtml(item.fecha_inicio || "sin inicio") + "</div><div class=\"fs-8 text-muted\">" + escapeHtml(item.fecha_fin || "sin fin") + "</div></td>" +
                "<td>" + badgeDetalle(item.estatus || "") + "</td></tr>";
        }).join("") || "<tr><td colspan=\"4\" class=\"text-center text-muted py-4\">Sin detalles</td></tr>";
        html += "</tbody></table></div>";
        html += "<div class=\"fw-semibold mb-2\">Asignaciones CRM</div><div class=\"lp-detail-wrap\">" + renderAsignaciones(asignaciones) + "</div>";
        document.getElementById("lp_detalle").innerHTML = html;
        renderConflictos(data.conflictos || estado.conflictos || []);
    }

    function badgeDetalle(estatus) {
        return "<span class=\"badge " + (estatus === "activo" ? "badge-light-success" : "badge-light") + "\">" + escapeHtml(estatus || "-") + "</span>";
    }

    function renderAsignaciones(asignaciones) {
        return "<table class=\"table table-sm align-middle mb-0\"><thead><tr class=\"text-muted fs-8 text-uppercase\"><th>Cliente</th><th>Prioridad</th><th>Vigencia</th><th>Estatus</th></tr></thead><tbody>" +
            ((asignaciones || []).map(function (item) {
                return "<tr><td><div class=\"fw-semibold\">" + escapeHtml(item.nombre_publico || ("Cliente " + (item.id_cliente_crm || item.id_cliente || "-"))) + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.codigo_cliente || "") + "</div></td>" +
                    "<td>" + escapeHtml(item.prioridad || "1") + "</td>" +
                    "<td><div class=\"fs-8\">" + escapeHtml(item.fecha_inicio || "") + "</div><div class=\"fs-8 text-muted\">" + escapeHtml(item.fecha_fin || "sin fin") + "</div></td>" +
                    "<td>" + badgeDetalle(item.estatus || "") + "</td></tr>";
            }).join("") || "<tr><td colspan=\"4\" class=\"text-center text-muted py-4\">Sin asignaciones</td></tr>") +
            "</tbody></table>";
    }

    function payloadLista() {
        return {
            id_lista_precio: document.getElementById("lp_dry_lista_id").value,
            codigo: document.getElementById("lp_dry_lista_codigo").value,
            nombre: document.getElementById("lp_dry_lista_nombre").value,
            canal: document.getElementById("lp_dry_lista_canal").value,
            id_almacen: document.getElementById("lp_dry_lista_almacen").value,
            prioridad: document.getElementById("lp_dry_lista_prioridad").value,
            estatus: document.getElementById("lp_dry_lista_estatus").value
        };
    }

    function payloadDetalle() {
        return {
            id_lista_precio_detalle: document.getElementById("lp_dry_det_id").value,
            id_lista_precio: document.getElementById("lp_dry_det_lista").value,
            id_sku: document.getElementById("lp_dry_det_sku").value,
            id_producto_erp: document.getElementById("lp_dry_det_producto").value,
            precio: document.getElementById("lp_dry_det_precio").value,
            moneda: document.getElementById("lp_dry_det_moneda").value,
            estatus: document.getElementById("lp_dry_det_estatus").value
        };
    }

    function payloadAsignacion() {
        return {
            id_cliente_lista_precio: document.getElementById("lp_dry_asig_id").value,
            id_lista_precio: document.getElementById("lp_dry_asig_lista").value,
            id_cliente_crm: document.getElementById("lp_dry_asig_cliente").value,
            prioridad: document.getElementById("lp_dry_asig_prioridad").value,
            estatus: document.getElementById("lp_dry_asig_estatus").value
        };
    }

    function agregarAutorizacionUat(data) {
        var referencia = document.getElementById("lp_uat_referencia").value.trim();
        var motivo = document.getElementById("lp_uat_motivo").value.trim();
        data.autorizar = document.getElementById("lp_uat_token").value;
        data.motivo = referencia !== "" ? (motivo + " | Ref: " + referencia).trim() : motivo;
        return data;
    }

    function cargarConflictos() {
        request("/comercial/listas_precios_conflictos_erp").then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            estado.conflictos = ((response.depurar || {}).conflictos) || [];
            renderConflictos(estado.conflictos);
        }).catch(mostrarError);
    }

    function renderConflictos(conflictos) {
        document.getElementById("lp_conflictos").innerHTML = (conflictos || []).map(function (item) {
            return "<div class=\"lp-conflict bg-light-danger p-3 rounded mb-2\"><div class=\"fw-bold\">" + escapeHtml(item.tipo || "conflicto") + " <span class=\"badge badge-light-danger ms-2\">" + escapeHtml(item.severidad || "") + "</span></div>" +
                "<div class=\"fs-7\">" + escapeHtml(item.mensaje || "") + "</div>" +
                "<div class=\"text-muted fs-8\">Lista " + escapeHtml(item.id_lista_precio || "-") + (item.id_lista_precio_detalle ? " | Detalle " + escapeHtml(item.id_lista_precio_detalle) : "") + "</div></div>";
        }).join("") || "<div class=\"text-muted fs-7\">Sin conflictos detectados con las reglas actuales.</div>";
    }

    function cargarAuditoria() {
        var idLista = document.getElementById("lp_auditoria_lista").value.trim() || estado.listaSeleccionada || "";
        var accion = document.getElementById("lp_auditoria_accion").value.trim();
        var params = new URLSearchParams({id_lista_precio: idLista, accion: accion, limite: "30"});
        request("/comercial/listas_precios_auditoria_erp?" + params.toString()).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            renderAuditoria(response.depurar || {});
        }).catch(function (error) {
            document.getElementById("lp_auditoria").innerHTML = "<div class=\"alert alert-danger py-3\">" + escapeHtml(error.message || String(error)) + "</div>";
        });
    }

    function renderAuditoria(data) {
        if (data.schema_pendiente) {
            document.getElementById("lp_auditoria").innerHTML = "<div class=\"alert alert-warning py-3 mb-0\"><div class=\"fw-bold\">Auditoria pendiente</div><div class=\"fs-8\">Falta aplicar <code>erp_listas_precios_eventos</code>.</div></div>";
            return;
        }
        var eventos = data.eventos || [];
        document.getElementById("lp_auditoria").innerHTML = eventos.map(function (item) {
            return "<div class=\"border rounded p-3 mb-2\">" +
                "<div class=\"d-flex justify-content-between gap-2\"><div class=\"fw-semibold\">" + escapeHtml(item.accion || "-") + "</div><span class=\"badge badge-light\">" + escapeHtml(item.resultado || "ok") + "</span></div>" +
                "<div class=\"fs-8 text-muted\">" + escapeHtml(item.fecha_registro || "") + " | Usuario " + escapeHtml(item.creado_por || "-") + "</div>" +
                "<div class=\"fs-8 mt-1\">" + escapeHtml(item.resumen || "") + "</div>" +
                (item.motivo ? "<div class=\"fs-8 text-muted mt-1\">Motivo: " + escapeHtml(item.motivo) + "</div>" : "") +
            "</div>";
        }).join("") || "<div class=\"text-muted fs-7\">Sin eventos para los filtros actuales.</div>";
    }

    function validarListaDryRun() {
        postRequest("/comercial/listas_precios_lista_dryrun_erp", payloadLista()).then(renderDryRun).catch(renderDryRunError);
    }

    function validarDetalleDryRun() {
        postRequest("/comercial/listas_precios_detalle_dryrun_erp", payloadDetalle()).then(renderDryRun).catch(renderDryRunError);
    }

    function validarAsignacionDryRun() {
        postRequest("/comercial/listas_precios_asignacion_dryrun_erp", payloadAsignacion()).then(renderDryRun).catch(renderDryRunError);
    }

    function guardarListaUat() {
        postRequest("/comercial/listas_precios_lista_guardar_erp", agregarAutorizacionUat(payloadLista())).then(renderGuardado).catch(renderDryRunError);
    }

    function guardarDetalleUat() {
        postRequest("/comercial/listas_precios_detalle_guardar_erp", agregarAutorizacionUat(payloadDetalle())).then(renderGuardado).catch(renderDryRunError);
    }

    function guardarAsignacionUat() {
        postRequest("/comercial/listas_precios_asignacion_guardar_erp", agregarAutorizacionUat(payloadAsignacion())).then(renderGuardado).catch(renderDryRunError);
    }

    function renderGuardado(response) {
        renderDryRun(response);
        if (!response.error && response.tipo === "success") {
            cargarResumen();
        }
    }

    function renderDryRun(response) {
        var data = response.depurar || {};
        var bloqueos = data.bloqueos || [];
        var avisos = data.avisos || [];
        var duplicados = data.duplicados || [];
        var html = "<div class=\"alert " + (bloqueos.length ? "alert-warning" : "alert-success") + " py-3 mb-0\">" +
            "<div class=\"fw-bold mb-1\">" + escapeHtml(response.mensaje || "Dry-run") + "</div>" +
            "<div class=\"fs-8\">Puede guardar en fase real: " + (data.puede_guardar ? "si" : "no") + " | Esta validacion no escribe BD.</div>";
        if (bloqueos.length) {
            html += "<div class=\"fw-semibold mt-2\">Bloqueos</div><ul class=\"mb-0 ps-4\">" + bloqueos.map(function (item) { return "<li>" + escapeHtml(item) + "</li>"; }).join("") + "</ul>";
        }
        if (avisos.length) {
            html += "<div class=\"fw-semibold mt-2\">Avisos</div><ul class=\"mb-0 ps-4 text-muted\">" + avisos.map(function (item) { return "<li>" + escapeHtml(item) + "</li>"; }).join("") + "</ul>";
        }
        if (duplicados.length) {
            html += "<div class=\"fw-semibold mt-2\">Duplicados detectados</div><div class=\"fs-8 text-muted\">" + escapeHtml(duplicados.length) + " registro(s)</div>";
        }
        html += "</div>";
        document.getElementById("lp_dry_resultado").innerHTML = html;
    }

    function renderDryRunError(error) {
        document.getElementById("lp_dry_resultado").innerHTML = "<div class=\"alert alert-danger py-3\">" + escapeHtml(error.message || String(error)) + "</div>";
    }

    function mostrarError(error) {
        document.getElementById("lp_alerta").innerHTML = "<div class=\"alert alert-danger py-3\">" + escapeHtml(error.message || String(error)) + "</div>";
    }

    document.addEventListener("DOMContentLoaded", function () {
        document.getElementById("lp_recargar").addEventListener("click", cargarResumen);
        document.getElementById("lp_filtrar").addEventListener("click", cargarResumen);
        document.getElementById("lp_conflictos_btn").addEventListener("click", cargarConflictos);
        document.getElementById("lp_auditoria_btn").addEventListener("click", cargarAuditoria);
        document.getElementById("lp_dry_lista_validar").addEventListener("click", validarListaDryRun);
        document.getElementById("lp_dry_det_validar").addEventListener("click", validarDetalleDryRun);
        document.getElementById("lp_dry_asig_validar").addEventListener("click", validarAsignacionDryRun);
        document.getElementById("lp_guardar_lista").addEventListener("click", guardarListaUat);
        document.getElementById("lp_guardar_detalle").addEventListener("click", guardarDetalleUat);
        document.getElementById("lp_guardar_asig").addEventListener("click", guardarAsignacionUat);
        document.getElementById("lp_filtro_q").addEventListener("keyup", function (event) {
            if (event.key === "Enter") {
                cargarResumen();
            }
        });
        cargarResumen();
    });
})();
