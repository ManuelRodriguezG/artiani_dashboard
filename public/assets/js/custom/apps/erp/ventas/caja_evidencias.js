"use strict";
(function () {
    var estado = {};

    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-02
     * Proposito: consultar evidencias de caja POS en pantalla dedicada.
     * Impacto: separa seguimiento documental de la venta y evita acciones reales accidentales.
     */
    function request(url) {
        return fetch(url, {credentials: "same-origin"}).then(function (response) { return response.json(); });
    }

    function escapeHtml(value) {
        var div = document.createElement("div");
        div.textContent = value == null ? "" : String(value);
        return div.innerHTML;
    }

    function dinero(value) {
        return new Intl.NumberFormat("es-MX", {style: "currency", currency: "MXN"}).format(Number(value || 0));
    }

    function cargarConfiguracion() {
        request("/ventas/pos_configuracion_resumen_erp").then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            estado = response.depurar || {};
            llenarSelectores();
            consultarMovimientos();
        }).catch(mostrarError);
    }

    function llenarSelectores() {
        var almacenes = estado.almacenes || [];
        var cajas = estado.cajas || [];
        document.getElementById("pos_evc_almacen").innerHTML = "<option value=\"\">Todos</option>" + almacenes.map(function (item) {
            return "<option value=\"" + escapeHtml(item.id_almacen) + "\">" + escapeHtml(item.codigo_almacen || item.almacen || item.nombre_comercial || item.id_almacen) + "</option>";
        }).join("");
        document.getElementById("pos_evc_caja").innerHTML = "<option value=\"\">Todas</option>" + cajas.map(function (item) {
            return "<option value=\"" + escapeHtml(item.id_caja) + "\">" + escapeHtml(item.codigo || item.nombre || item.id_caja) + "</option>";
        }).join("");
    }

    function consultarMovimientos() {
        var params = new URLSearchParams({
            evidencia_estado: document.getElementById("pos_evc_estado_movimiento").value || "pendiente",
            id_almacen: document.getElementById("pos_evc_almacen").value || "",
            id_caja: document.getElementById("pos_evc_caja").value || "",
            limite: "80"
        });
        var movimiento = document.getElementById("pos_evc_movimiento").value.trim();
        if (movimiento) {
            params.set("id_movimiento_caja", movimiento);
        }
        setLoading("Consultando movimientos...");
        request("/ventas/caja_evidencias_pendientes_erp?" + params.toString()).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            renderMovimientos(response);
        }).catch(mostrarError);
    }

    function consultarDetalle() {
        var params = new URLSearchParams({
            id_movimiento_caja: document.getElementById("pos_evc_movimiento").value.trim(),
            estatus: "todos",
            limite: "80"
        });
        setLoading("Consultando evidencias...");
        request("/ventas/caja_evidencias_detalle_erp?" + params.toString()).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            renderDetalle(response);
        }).catch(mostrarError);
    }

    function renderMovimientos(response) {
        var data = response.depurar || {};
        var resumen = data.resumen || {};
        var filas = data.pendientes || [];
        var html = "<div class=\"alert " + (filas.length ? "alert-info" : "alert-light") + " py-3 mb-3\"><div class=\"fw-bold\">" + escapeHtml(response.mensaje || "Movimientos consultados") + "</div>" +
            "<div class=\"fs-8 text-muted\">Registros: " + escapeHtml(resumen.total_registros || 0) + " | Monto: " + dinero(resumen.monto_total || 0) + " | No modifica caja ni evidencias.</div></div>";
        if (!filas.length) {
            document.getElementById("pos_evc_resultado").innerHTML = html + empty("Sin movimientos en este filtro");
            return;
        }
        html += "<div class=\"table-responsive\"><table class=\"table align-middle table-row-dashed gy-3 mb-0\"><thead><tr class=\"text-muted fw-bold fs-8 text-uppercase\"><th>Movimiento</th><th>Turno</th><th>Tipo</th><th>Referencia</th><th>Estado</th><th class=\"text-end\">Monto</th></tr></thead><tbody>";
        html += filas.map(function (item) {
            return "<tr><td><div class=\"fw-bold\">#" + escapeHtml(item.id_movimiento_caja || "-") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.fecha_registro || "") + "</div></td>" +
                "<td>" + escapeHtml(item.turno_folio || item.id_turno_caja || "-") + "</td><td><div class=\"fw-semibold\">" + escapeHtml(item.tipo || "-") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.motivo || "") + "</div></td>" +
                "<td><div>" + escapeHtml(item.referencia || item.folio_devolucion || item.folio_venta || "-") + "</div><button class=\"btn btn-sm btn-light-primary mt-2\" type=\"button\" data-evc-detalle=\"" + escapeHtml(item.id_movimiento_caja || "") + "\"><i class=\"bi bi-eye\"></i> Evidencias</button></td>" +
                "<td><span class=\"badge badge-light-warning\">" + escapeHtml(item.evidencia_estado || "pendiente") + "</span></td><td class=\"text-end fw-bold\">" + dinero(item.monto || 0) + "</td></tr>";
        }).join("");
        html += "</tbody></table></div>";
        document.getElementById("pos_evc_resultado").innerHTML = html;
    }

    function renderDetalle(response) {
        var data = response.depurar || {};
        var filas = data.evidencias || [];
        var html = "<div class=\"alert " + (filas.length ? "alert-info" : "alert-light") + " py-3 mb-3\"><div class=\"fw-bold\">" + escapeHtml(response.mensaje || "Evidencias consultadas") + "</div>" +
            "<div class=\"fs-8 text-muted\">Registros: " + escapeHtml(data.total_registros || 0) + " | Solo lectura; no aprueba ni corrige evidencias.</div></div>";
        if (!filas.length) {
            document.getElementById("pos_evc_resultado").innerHTML = html + empty("Sin evidencias capturadas");
            return;
        }
        html += filas.map(function (item) {
            var correccion = item.correccion_folio
                ? "<div class=\"mt-2\"><span class=\"badge badge-light-warning\">" + escapeHtml(item.correccion_folio) + "</span> <span class=\"text-muted fs-8\">" + escapeHtml(item.correccion_estatus || "") + "</span></div>"
                : "";
            return "<div class=\"border rounded p-4 mb-3\">" +
                "<div class=\"d-flex flex-wrap justify-content-between gap-3\"><div><div class=\"fw-bold\">" + escapeHtml(item.tipo_evidencia || "Evidencia") + "</div>" +
                "<div class=\"text-muted fs-8\">Evidencia #" + escapeHtml(item.id_evidencia_caja || "-") + " | Movimiento #" + escapeHtml(item.id_movimiento_caja || "-") + "</div></div>" +
                "<div class=\"text-end\"><span class=\"badge badge-light-primary\">" + escapeHtml(item.estatus || "-") + "</span><div class=\"fw-bold mt-1\">" + dinero(item.monto || 0) + "</div></div></div>" +
                "<div class=\"row g-3 mt-2 fs-7\"><div class=\"col-md-6\"><span class=\"text-muted\">Referencia:</span> " + escapeHtml(item.referencia_externa || item.referencia || item.folio_devolucion || item.folio_venta || "-") + "</div>" +
                "<div class=\"col-md-6\"><span class=\"text-muted\">Turno:</span> " + escapeHtml(item.turno_folio || item.id_turno_caja || "-") + "</div>" +
                "<div class=\"col-12\"><span class=\"text-muted\">Descripcion:</span> " + escapeHtml(item.descripcion || item.titulo || "-") + "</div></div>" +
                correccion +
                "<div class=\"alert alert-light-warning py-2 mt-3 mb-0 fs-8\">Acciones reales de aprobar, rechazar o corregir se ejecutan solo con autorizacion y permisos.</div>" +
                "</div>";
        }).join("");
        document.getElementById("pos_evc_resultado").innerHTML = html;
    }

    function setLoading(texto) {
        document.getElementById("pos_evc_resultado").innerHTML = "<div class=\"text-muted fs-8 py-4\">" + escapeHtml(texto) + "</div>";
    }

    function empty(texto) {
        return "<div class=\"pos-empty d-flex align-items-center justify-content-center text-center text-muted\"><div><i class=\"bi bi-folder2-open fs-1 d-block mb-3\"></i><div class=\"fw-semibold\">" + escapeHtml(texto) + "</div></div></div>";
    }

    function mostrarError(error) {
        document.getElementById("pos_evc_resultado").innerHTML = "<div class=\"alert alert-warning py-3\">" + escapeHtml(error.message || String(error)) + "</div>";
    }

    document.addEventListener("DOMContentLoaded", function () {
        cargarConfiguracion();
        document.getElementById("pos_evc_consultar").addEventListener("click", consultarMovimientos);
        document.getElementById("pos_evc_detalle").addEventListener("click", consultarDetalle);
        document.getElementById("pos_evc_recargar").addEventListener("click", consultarMovimientos);
        document.getElementById("pos_evc_resultado").addEventListener("click", function (event) {
            var boton = event.target.closest("[data-evc-detalle]");
            if (!boton) { return; }
            document.getElementById("pos_evc_movimiento").value = boton.getAttribute("data-evc-detalle") || "";
            consultarDetalle();
        });
    });
})();
