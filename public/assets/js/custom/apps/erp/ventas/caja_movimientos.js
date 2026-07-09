"use strict";
(function () {
    var estado = {};

    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-02
     * Proposito: operar pantalla de movimientos de caja POS en modo dry-run/read-only.
     * Impacto: separa gastos/retiros/entradas del POS mostrador sin registrar dinero.
     */
    function request(url, data) {
        var opciones = {credentials: "same-origin"};
        if (data) {
            opciones.method = "POST";
            opciones.headers = {"Content-Type": "application/x-www-form-urlencoded; charset=UTF-8", "X-CSRF-Token": window.ERP_CSRF_TOKEN || ""};
            opciones.body = new URLSearchParams(data).toString();
        }
        return fetch(url, opciones).then(function (response) { return response.json(); });
    }

    function escapeHtml(value) {
        var div = document.createElement("div");
        div.textContent = value == null ? "" : String(value);
        return div.innerHTML;
    }

    function dinero(value) {
        return new Intl.NumberFormat("es-MX", {style: "currency", currency: "MXN"}).format(Number(value || 0));
    }

    function numero(value) {
        var parsed = Number(String(value || "0").replace(",", "."));
        return Number.isFinite(parsed) ? parsed : 0;
    }

    function cargar() {
        request("/ventas/pos_configuracion_resumen_erp").then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            estado = response.depurar || {};
            render();
        }).catch(mostrarError);
    }

    function render() {
        document.getElementById("pos_mov_alerta").innerHTML = estado.schema_pendiente
            ? "<div class=\"alert alert-warning py-3\"><div class=\"fw-bold\">Configuracion POS incompleta</div><div class=\"fs-7\">La pantalla queda en consulta/simulacion hasta completar esquema y autorizaciones.</div></div>"
            : "<div class=\"alert alert-info py-3\"><div class=\"fw-bold\">Movimientos separados de la venta</div><div class=\"fs-7\">Aqui se simulan gastos, retiros y entradas. Registrar movimientos reales requiere autorizacion con respaldo.</div></div>";
        llenarSelectores();
        renderMovimientos();
    }

    function llenarSelectores() {
        var almacenes = estado.almacenes || [];
        var cajas = estado.cajas || [];
        var turnos = estado.turnos_abiertos || [];
        document.getElementById("pos_mov_almacen").innerHTML = almacenes.map(function (item) {
            return "<option value=\"" + escapeHtml(item.id_almacen) + "\">" + escapeHtml(item.codigo_almacen || item.almacen || item.nombre_comercial || item.id_almacen) + "</option>";
        }).join("") || "<option value=\"\">Sin almacenes</option>";
        document.getElementById("pos_mov_caja").innerHTML = cajas.map(function (item) {
            return "<option value=\"" + escapeHtml(item.id_caja) + "\" data-almacen=\"" + escapeHtml(item.id_almacen) + "\">" + escapeHtml(item.codigo || item.nombre || item.id_caja) + "</option>";
        }).join("") || "<option value=\"\">Sin cajas</option>";
        document.getElementById("pos_mov_turno").innerHTML = turnos.map(function (item) {
            return "<option value=\"" + escapeHtml(item.id_turno_caja) + "\" data-caja=\"" + escapeHtml(item.id_caja) + "\" data-almacen=\"" + escapeHtml(item.id_almacen) + "\">" + escapeHtml(item.folio || item.id_turno_caja) + "</option>";
        }).join("") || "<option value=\"\">Sin turnos abiertos</option>";
        sincronizarDesdeTurno();
    }

    function sincronizarDesdeTurno() {
        var turno = document.getElementById("pos_mov_turno").selectedOptions[0];
        if (!turno) { return; }
        if (turno.getAttribute("data-almacen")) {
            document.getElementById("pos_mov_almacen").value = turno.getAttribute("data-almacen");
        }
        if (turno.getAttribute("data-caja")) {
            document.getElementById("pos_mov_caja").value = turno.getAttribute("data-caja");
        }
    }

    function renderMovimientos() {
        var filas = estado.movimientos_recientes || [];
        document.getElementById("pos_mov_tabla").innerHTML = filas.map(function (item) {
            var evidencia = item.requiere_evidencia === "1" || item.requiere_evidencia === 1
                ? "<span class=\"badge badge-light-warning\">" + escapeHtml(item.evidencia_estado || "pendiente") + "</span>"
                : "<span class=\"badge badge-light\">No requerida</span>";
            return "<tr><td>" + escapeHtml(item.fecha_registro || "-") + "</td><td>" + escapeHtml(item.turno_folio || item.id_turno_caja || "-") + "</td>" +
                "<td>" + escapeHtml(item.tipo || "-") + "</td><td><div class=\"fw-semibold\">" + escapeHtml(item.motivo || "-") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.referencia || "") + "</div></td>" +
                "<td>" + evidencia + "</td><td class=\"text-end fw-bold\">" + dinero(item.monto || 0) + "</td></tr>";
        }).join("") || "<tr><td colspan=\"6\" class=\"text-center text-muted py-6\">Sin movimientos recientes</td></tr>";
    }

    function simular() {
        request("/ventas/caja_movimiento_dryrun_erp", {
            id_almacen: document.getElementById("pos_mov_almacen").value,
            id_caja: document.getElementById("pos_mov_caja").value,
            id_turno_caja: document.getElementById("pos_mov_turno").value,
            tipo_movimiento: document.getElementById("pos_mov_tipo").value,
            monto: numero(document.getElementById("pos_mov_monto").value),
            motivo: document.getElementById("pos_mov_motivo").value.trim(),
            referencia: document.getElementById("pos_mov_referencia").value.trim(),
            responsable: document.getElementById("pos_mov_responsable").value.trim(),
            observaciones: document.getElementById("pos_mov_observaciones").value.trim()
        }).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            var data = response.depurar || {};
            var bloqueos = data.bloqueos || [];
            var avisos = data.avisos || [];
            var movimiento = data.movimiento || {};
            var html = "<div class=\"alert " + (bloqueos.length ? "alert-warning" : "alert-success") + " py-3\"><div class=\"fw-bold\">" + escapeHtml(response.mensaje || "Movimiento simulado") + "</div><div class=\"fs-8 text-muted\">No se registro movimiento real ni se modifico el corte.</div>";
            html += "<div class=\"fs-8\">Impacto esperado: " + dinero(movimiento.impacto_esperado || 0) + " | Monto: " + dinero(movimiento.monto || 0) + "</div>";
            if (avisos.length) {
                html += "<div class=\"mt-2\">" + avisos.map(function (item) { return "<span class=\"badge badge-light-info me-1\">" + escapeHtml(item) + "</span>"; }).join("") + "</div>";
            }
            if (bloqueos.length) {
                html += "<ul class=\"mb-0 mt-2 ps-4\">" + bloqueos.map(function (item) { return "<li>" + escapeHtml(item) + "</li>"; }).join("") + "</ul>";
            } else {
                html += "<div class=\"separator my-3\"></div><div class=\"fs-8 text-muted\">Para aplicar real se requiere autorizacion especifica de movimiento de caja y, si aplica, evidencia.</div>";
            }
            html += "</div>";
            document.getElementById("pos_mov_resultado").innerHTML = html;
        }).catch(function (error) {
            document.getElementById("pos_mov_resultado").innerHTML = "<div class=\"alert alert-warning py-3\">" + escapeHtml(error.message || String(error)) + "</div>";
        });
    }

    function mostrarError(error) {
        document.getElementById("pos_mov_alerta").innerHTML = "<div class=\"alert alert-danger py-3\">" + escapeHtml(error.message || String(error)) + "</div>";
    }

    document.addEventListener("DOMContentLoaded", function () {
        cargar();
        document.getElementById("pos_mov_recargar").addEventListener("click", cargar);
        document.getElementById("pos_mov_turno").addEventListener("change", sincronizarDesdeTurno);
        document.getElementById("pos_mov_simular").addEventListener("click", simular);
    });
})();
