"use strict";
(function () {
    var ticketActual = "";

    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-02
     * Proposito: renderizar detalle read-only de venta POS ERP.
     * Impacto: usa el ticket formal como fuente unica de venta, pagos, garantia y trazabilidad.
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

    function numero(value) {
        return new Intl.NumberFormat("es-MX", {maximumFractionDigits: 3}).format(Number(value || 0));
    }

    function consultar() {
        var ref = document.getElementById("venta_detalle_ref").value.trim();
        if (!ref) {
            alerta("warning", "Captura folio o id de venta.");
            return;
        }
        alerta("info", "Consultando venta...");
        var params = /^\d+$/.test(ref) ? "id_venta=" + encodeURIComponent(ref) : "folio=" + encodeURIComponent(ref);
        request("/ventas/ticket_venta_readonly_erp?" + params).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            render(response.depurar || {});
        }).catch(function (error) {
            alerta("danger", error.message || String(error));
        });
    }

    function render(data) {
        var venta = data.venta || {};
        var detalles = data.detalles || [];
        var pagos = data.pagos || [];
        var trazabilidad = data.trazabilidad_inventario || [];
        var hallazgos = data.hallazgos || [];
        ticketActual = data.ticket_texto || "";
        document.getElementById("venta_detalle_devolucion").href = "/ventas/devoluciones?folio=" + encodeURIComponent(venta.folio || "");
        alerta(hallazgos.length ? "warning" : "success", hallazgos.length ? "Venta consultada con observaciones." : "Venta consultada en modo solo lectura.");

        document.getElementById("venta_detalle_contenido").innerHTML =
            resumenVenta(venta, detalles, pagos, trazabilidad) +
            "<div class=\"row g-4\">" +
                "<div class=\"col-xl-7\">" +
                    card("Partidas", tablaPartidas(detalles)) +
                    card("Pagos", tablaPagos(pagos)) +
                    card("Trazabilidad inventario", tablaTrazabilidad(trazabilidad)) +
                "</div>" +
                "<div class=\"col-xl-5\">" +
                    card("Ticket formal", ticketPanel(ticketActual, hallazgos)) +
                "</div>" +
            "</div>";
    }

    function resumenVenta(venta, detalles, pagos, trazabilidad) {
        return "<div class=\"row g-3 mb-4\">" +
            kpi("Folio", venta.folio || "-") +
            kpi("Cliente", venta.cliente_nombre_publico || "Publico general") +
            kpi("Total", dinero(venta.total || 0)) +
            kpi("Pagado", dinero(venta.pagado_total || 0)) +
            kpi("Saldo", dinero(venta.saldo_total || 0)) +
            kpi("Partidas", detalles.length) +
            kpi("Pagos", pagos.length) +
            kpi("Kardex", trazabilidad.length) +
            "<div class=\"col-12\"><div class=\"venta-card p-4\"><div class=\"row g-3 fs-7\">" +
                dato("Estatus", venta.estatus || "-") +
                dato("Canal", venta.canal || "-") +
                dato("Documento", venta.tipo_documento || "-") +
                dato("Tienda", venta.nombre_comercial || venta.almacen || "-") +
                dato("Caja", (venta.caja_codigo || "-") + " " + (venta.caja_nombre || "")) +
                dato("Turno", venta.turno_folio || "-") +
                dato("Fecha", venta.fecha_venta || "-") +
                dato("CRM", venta.cliente_codigo_snapshot || "-") +
            "</div></div></div>" +
        "</div>";
    }

    function tablaPartidas(items) {
        if (!items.length) { return empty("Sin partidas"); }
        return "<div class=\"table-responsive\"><table class=\"table align-middle table-row-dashed gy-3 mb-0\"><thead><tr class=\"text-muted fw-bold fs-8 text-uppercase\"><th>SKU</th><th>Cantidad</th><th>Precio</th><th>Total</th><th>Garantia</th></tr></thead><tbody>" +
            items.map(function (item) {
                return "<tr><td><div class=\"fw-bold\">" + escapeHtml(item.sku || "-") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.descripcion || "") + "</div></td>" +
                    "<td>" + numero(item.cantidad_venta || item.cantidad_base || 0) + " " + escapeHtml(item.unidad_venta || item.unidad_base || "") + "</td>" +
                    "<td>" + dinero(item.precio_unitario || 0) + "</td><td class=\"fw-bold\">" + dinero(item.total || 0) + "</td>" +
                    "<td><span class=\"badge badge-light-info\">" + escapeHtml(item.resumen_ticket || item.nombre_politica_snapshot || "Sin garantia") + "</span></td></tr>";
            }).join("") + "</tbody></table></div>";
    }

    function tablaPagos(items) {
        if (!items.length) { return empty("Sin pagos registrados"); }
        return "<div class=\"table-responsive\"><table class=\"table align-middle table-row-dashed gy-3 mb-0\"><thead><tr class=\"text-muted fw-bold fs-8 text-uppercase\"><th>Metodo</th><th>Tipo</th><th>Referencia</th><th>Fecha</th><th class=\"text-end\">Monto</th></tr></thead><tbody>" +
            items.map(function (item) {
                return "<tr><td class=\"fw-semibold\">" + escapeHtml(etiquetaMetodoPago(item)) + "</td><td>" + escapeHtml(etiquetaTipoPago(item)) + "</td>" +
                    "<td>" + escapeHtml(item.referencia || item.autorizacion || "-") + "</td><td>" + escapeHtml(item.fecha_pago || "-") + "</td><td class=\"text-end fw-bold\">" + dinero(item.monto || 0) + "</td></tr>";
            }).join("") + "</tbody></table></div>";
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-07
     * Proposito: etiquetar pagos virtuales de saldo CRM sin exponer claves tecnicas al operador.
     * Impacto: el detalle de venta separa saldo de cliente de pagos que entran a caja.
     */
    function esPagoSaldoCrm(item) {
        return String((item || {}).metodo_pago || "").toLowerCase() === "saldo_crm" ||
            String((item || {}).tipo_pago || "").toLowerCase() === "saldo_cliente";
    }

    function etiquetaMetodoPago(item) {
        if (esPagoSaldoCrm(item)) { return "Saldo cliente"; }
        return item.metodo_pago || item.id_metodo_pago || "-";
    }

    function etiquetaTipoPago(item) {
        if (esPagoSaldoCrm(item)) { return "No entra a caja"; }
        return item.tipo_pago || "-";
    }

    function tablaTrazabilidad(items) {
        if (!items.length) { return empty("Sin trazabilidad de inventario"); }
        return "<div class=\"table-responsive\"><table class=\"table align-middle table-row-dashed gy-3 mb-0\"><thead><tr class=\"text-muted fw-bold fs-8 text-uppercase\"><th>Movimiento</th><th>Existencia</th><th>Cantidad</th><th>Antes</th><th>Despues</th></tr></thead><tbody>" +
            items.map(function (item) {
                return "<tr><td><div class=\"fw-bold\">#" + escapeHtml(item.id_movimiento_inventario || "-") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.movimiento_referencia || "") + "</div></td>" +
                    "<td>" + escapeHtml(item.codigo_existencia || item.id_existencia_inventario || "-") + "</td><td>" + numero(item.cantidad_base || 0) + "</td>" +
                    "<td>" + numero(item.existencia_anterior || 0) + "</td><td class=\"fw-bold\">" + numero(item.existencia_nueva || 0) + "</td></tr>";
            }).join("") + "</tbody></table></div>";
    }

    function ticketPanel(ticket, hallazgos) {
        var alertas = hallazgos.length ? "<div class=\"alert alert-warning py-3\"><div class=\"fw-bold\">Observaciones</div><ul class=\"mb-0 ps-4\">" + hallazgos.map(function (item) {
            return "<li>" + escapeHtml(item.id || "") + ": " + escapeHtml(item.mensaje || item) + "</li>";
        }).join("") + "</ul></div>" : "";
        return alertas + "<pre class=\"bg-light p-4 rounded fs-7 venta-ticket-pre\">" + escapeHtml(ticket || "Sin ticket") + "</pre>" +
            "<div class=\"d-grid gap-2\"><button class=\"btn btn-light-primary\" id=\"venta_detalle_imprimir\" type=\"button\"><i class=\"bi bi-printer\"></i> Imprimir ticket</button></div>";
    }

    function card(titulo, contenido) {
        return "<div class=\"venta-card p-4 mb-4\"><div class=\"fw-bold fs-5 mb-3\">" + escapeHtml(titulo) + "</div><div class=\"venta-result\">" + contenido + "</div></div>";
    }

    function kpi(titulo, valor) {
        return "<div class=\"col-md-3\"><div class=\"venta-card p-4 h-100\"><div class=\"text-muted fs-8 text-uppercase\">" + escapeHtml(titulo) + "</div><div class=\"fw-bold fs-4\">" + escapeHtml(valor) + "</div></div></div>";
    }

    function dato(titulo, valor) {
        return "<div class=\"col-md-3\"><span class=\"text-muted\">" + escapeHtml(titulo) + ":</span> <span class=\"fw-semibold\">" + escapeHtml(valor) + "</span></div>";
    }

    function empty(texto) {
        return "<div class=\"venta-empty d-flex align-items-center justify-content-center text-center text-muted\"><div><i class=\"bi bi-folder2-open fs-1 d-block mb-3\"></i><div class=\"fw-semibold\">" + escapeHtml(texto) + "</div></div></div>";
    }

    function alerta(tipo, mensaje) {
        document.getElementById("venta_detalle_alerta").innerHTML = "<div class=\"alert alert-" + tipo + " py-3 mb-0\">" + escapeHtml(mensaje) + "</div>";
    }

    function imprimirTicket() {
        if (!ticketActual) { return; }
        var ventana = window.open("", "erp_pos_ticket_detalle", "width=420,height=720");
        if (!ventana) { return; }
        ventana.document.write("<!doctype html><html><head><title>Ticket POS</title><style>body{font-family:Consolas,'Liberation Mono',monospace;font-size:12px;white-space:pre-wrap;margin:12px;color:#111;}@media print{body{margin:0;}}</style></head><body>");
        ventana.document.write(escapeHtml(ticketActual));
        ventana.document.write("</body></html>");
        ventana.document.close();
        ventana.focus();
        ventana.print();
    }

    document.addEventListener("DOMContentLoaded", function () {
        var params = new URLSearchParams(window.location.search || "");
        var ref = params.get("folio") || params.get("id_venta") || "";
        if (ref) {
            document.getElementById("venta_detalle_ref").value = ref;
            consultar();
        }
        document.getElementById("venta_detalle_consultar").addEventListener("click", consultar);
        document.getElementById("venta_detalle_ref").addEventListener("keydown", function (event) {
            if (event.key === "Enter") {
                event.preventDefault();
                consultar();
            }
        });
        document.getElementById("venta_detalle_contenido").addEventListener("click", function (event) {
            if (event.target.closest("#venta_detalle_imprimir")) {
                imprimirTicket();
            }
        });
    });
})();
