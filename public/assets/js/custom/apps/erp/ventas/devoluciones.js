"use strict";
(function () {
    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-01
     * Proposito: operar pantalla separada de devoluciones POS en modo dry-run/read-only.
     * Impacto: evita mezclar reversas, reembolsos e inspeccion fisica dentro del tablero de ventas.
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
        return new Intl.NumberFormat("es-MX", {maximumFractionDigits: 3}).format(Number(value || 0));
    }

    function simular() {
        request("/ventas/devolucion_dryrun_erp", {
            tipo: document.getElementById("dev_tipo").value,
            folio: document.getElementById("dev_folio").value.trim(),
            decision_inventario: document.getElementById("dev_decision_inventario").value,
            decision_financiera: document.getElementById("dev_decision_financiera").value,
            motivo: document.getElementById("dev_motivo").value.trim(),
            items: JSON.stringify([])
        }).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            var data = response.depurar || {};
            var bloqueos = data.bloqueos || [];
            var html = "<div class=\"alert " + (bloqueos.length ? "alert-warning" : "alert-success") + " py-3\"><div class=\"fw-bold\">" + escapeHtml(response.mensaje || "Simulacion generada") + "</div><div class=\"fs-8 text-muted\">No se aplico devolucion real, no se reembolso y no se movio inventario.</div>";
            html += "<div class=\"fs-8\">Tipo: " + escapeHtml(data.tipo || "") + " | Inventario: " + escapeHtml(data.decision_inventario || "") + " | Financiera: " + escapeHtml(document.getElementById("dev_decision_financiera").value) + "</div>";
            if (bloqueos.length) {
                html += "<ul class=\"mb-0 mt-2 ps-4\">" + bloqueos.map(function (item) { return "<li>" + escapeHtml(item) + "</li>"; }).join("") + "</ul>";
            }
            html += "</div>";
            document.getElementById("dev_resultado").innerHTML = html;
        }).catch(function (error) {
            document.getElementById("dev_resultado").innerHTML = "<div class=\"alert alert-warning py-3\">" + escapeHtml(error.message || String(error)) + "</div>";
        });
    }

    function consultarFisicas() {
        var contenedor = document.getElementById("dev_fisicas_resultado");
        contenedor.innerHTML = "<div class=\"text-muted fs-8 py-3\">Consultando devoluciones...</div>";
        request("/ventas/devoluciones_inventario_pendientes_erp?decision_inventario=" + encodeURIComponent(document.getElementById("dev_fisicas_filtro").value) + "&limite=50").then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            renderFisicas(response);
        }).catch(function (error) {
            contenedor.innerHTML = "<div class=\"alert alert-warning py-3\">" + escapeHtml(error.message || String(error)) + "</div>";
        });
    }

    function renderFisicas(response) {
        var depurar = response.depurar || {};
        var resumen = depurar.resumen || {};
        var filas = depurar.pendientes || [];
        var html = "<div class=\"alert " + (filas.length ? "alert-info" : "alert-light") + " py-3 mb-3\"><div class=\"fw-bold\">" + escapeHtml(response.mensaje || "Devoluciones fisicas") + "</div>" +
            "<div class=\"fs-8 text-muted\">Partidas: " + escapeHtml(resumen.total_registros || 0) + " | Cantidad: " + numero(resumen.cantidad_total || 0) + " | Importe: " + dinero(resumen.importe_total || 0) + "</div></div>";
        if (!filas.length) {
            document.getElementById("dev_fisicas_resultado").innerHTML = html + "<div class=\"dev-empty d-flex align-items-center justify-content-center text-center text-muted\"><div><i class=\"bi bi-check2-circle fs-1 d-block mb-3\"></i><div class=\"fw-semibold\">Sin partidas en este filtro</div></div></div>";
            return;
        }
        html += "<div class=\"table-responsive\"><table class=\"table align-middle table-row-dashed gy-3 mb-0\"><thead><tr class=\"text-muted fw-bold fs-8 text-uppercase\"><th>Devolucion</th><th>Venta</th><th>SKU</th><th>Decision</th><th class=\"text-end\">Importe</th></tr></thead><tbody>";
        html += filas.map(function (item) {
            return "<tr><td><div class=\"fw-bold\">" + escapeHtml(item.folio_devolucion || "-") + "</div><div class=\"text-muted fs-8\">Inspeccion: " + escapeHtml(item.folio_inspeccion || item.inspeccion_estado || "pendiente") + "</div></td>" +
                "<td>" + escapeHtml(item.folio_venta || "-") + "</td><td><div class=\"fw-semibold\">" + escapeHtml(item.sku || "-") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.descripcion || "") + "</div></td>" +
                "<td><span class=\"badge badge-light-warning\">" + escapeHtml(item.decision_inventario || "-") + "</span></td><td class=\"text-end fw-bold\">" + dinero(item.importe_reembolso || 0) + "</td></tr>";
        }).join("");
        html += "</tbody></table></div>";
        document.getElementById("dev_fisicas_resultado").innerHTML = html;
    }

    function consultarTicket() {
        var folio = document.getElementById("dev_ticket_folio").value.trim();
        if (!folio) {
            document.getElementById("dev_ticket_texto").textContent = "Captura folio de devolucion.";
            return;
        }
        document.getElementById("dev_ticket_texto").textContent = "Consultando ticket...";
        request("/ventas/pos_ticket_devolucion_erp?folio_devolucion=" + encodeURIComponent(folio)).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            var data = response.depurar || {};
            document.getElementById("dev_ticket_texto").textContent = data.ticket_texto || JSON.stringify(data, null, 2);
        }).catch(function (error) {
            document.getElementById("dev_ticket_texto").textContent = error.message || String(error);
        });
    }

    function aplicarParametrosUrl() {
        var params = new URLSearchParams(window.location.search || "");
        var folioVenta = params.get("folio") || params.get("folio_venta") || "";
        var folioDevolucion = params.get("folio_devolucion") || "";
        if (folioVenta) {
            document.getElementById("dev_folio").value = folioVenta;
            document.getElementById("dev_resultado").innerHTML = "<div class=\"alert alert-info py-3\"><div class=\"fw-bold\">Folio precargado desde ventas</div><div class=\"fs-8 text-muted\">Puedes simular la devolucion sin aplicar cambios reales.</div></div>";
        }
        if (folioDevolucion) {
            document.getElementById("dev_ticket_folio").value = folioDevolucion;
            consultarTicket();
        }
    }

    document.addEventListener("DOMContentLoaded", function () {
        document.getElementById("dev_simular").addEventListener("click", simular);
        document.getElementById("dev_fisicas_consultar").addEventListener("click", consultarFisicas);
        document.getElementById("dev_ticket_consultar").addEventListener("click", consultarTicket);
        aplicarParametrosUrl();
        consultarFisicas();
    });
})();
