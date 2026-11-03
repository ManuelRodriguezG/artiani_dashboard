/*
 * IA: Codex GPT-5
 * Fecha: 2026-11-02
 * Proposito: renderizar auditoria read-only de consolidacion de Compras/Recepcion.
 * Impacto: solo UI; no ejecuta POST ni modifica datos.
 */
(function () {
    "use strict";

    var endpoint = "/compra/auditoria_transicion_almacen_erp";
    var escapeHtml = function (value) {
        return String(value === null || value === undefined ? "" : value)
            .replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;")
            .replace(/\"/g, "&quot;").replace(/'/g, "&#039;");
    };
    var text = function (value, fallback) {
        var result = value === null || value === undefined || String(value).trim() === "" ? fallback : value;
        return escapeHtml(result);
    };
    var money = function (value) {
        var number = Number(value || 0);
        return "$" + number.toLocaleString("es-MX", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    };
    var badge = function (classification) {
        var labels = {
            sin_destino: ["Pendiente de destino", "warning"],
            migrable_revision: ["Revisar antes de continuar", "info"],
            conservar_origen: ["Conservar origen", "primary"]
        };
        var item = labels[classification] || ["Sin clasificar", "secondary"];
        return '<span class="badge badge-light-' + item[1] + '">' + item[0] + "</span>";
    };
    var destination = function (row, key) {
        var id = row[key] || 0;
        var name = row.almacen || row.nombre_comercial || "";
        return id ? text(name, "Almacén") + '<div class="fs-8 ta-muted">ID ' + text(id, "0") + "</div>" : '<span class="badge badge-light-warning">Sin destino</span>';
    };
    var renderRows = function (selector, rows, type) {
        var element = document.querySelector(selector);
        if (!element) return;
        if (!rows.length) {
            element.innerHTML = '<tr><td colspan="' + (type === "orden" ? "5" : "4") + '" class="text-center ta-muted py-6">No hay registros en esta categoría.</td></tr>';
            return;
        }
        element.innerHTML = rows.map(function (row) {
            var folio = row.folio || row.folio_orden_compra || ("#" + (row.id_solicitud || row.id_recepcion_almacen || row.id_orden_compra));
            var destino = destination(row, type === "recepcion" ? "id_almacen" : "id_almacen_destino");
            var cells = '<td><div class="fw-bold">' + text(folio, "Sin folio") + '</div><div class="fs-8 ta-muted">' + text(row.motivo_transicion, "") + '</div></td>';
            cells += '<td>' + destino + '</td><td>' + text(row.estatus, "Sin estado") + '</td>';
            if (type === "orden") cells += '<td class="text-end">' + money(row.total) + '</td>';
            cells += '<td>' + badge(row.clasificacion_transicion) + '</td>';
            return "<tr>" + cells + "</tr>";
        }).join("");
    };
    var renderWarehouses = function (rows) {
        var element = document.querySelector("#ta_almacenes");
        if (!rows.length) {
            element.innerHTML = '<tr><td colspan="6" class="text-center ta-muted py-6">No hay almacenes registrados.</td></tr>';
            return;
        }
        element.innerHTML = rows.map(function (row) {
            return "<tr><td>" + text(row.codigo_almacen, "") + "</td><td><div class=\"fw-bold\">" + text(row.almacen, "Sin nombre") + "</div><div class=\"fs-8 ta-muted\">" + text(row.nombre_comercial, "") + "</div></td><td>" + text(row.tipo_almacen, "") + "</td><td>" + text(row.estatus, "") + "</td><td>" + (Number(row.permite_recepcion) ? "Sí" : "No") + "</td><td>" + (Number(row.permite_venta) ? "Sí" : "No") + "</td></tr>";
        }).join("");
    };
    var load = function () {
        var alert = document.querySelector("#ta_alerta");
        alert.innerHTML = '<div class="alert alert-light-info">Consultando auditoría de transición...</div>';
        fetch(endpoint, { credentials: "same-origin", headers: { "Accept": "application/json" } })
            .then(function (response) { return response.json(); })
            .then(function (response) {
                if (!response || response.error) throw new Error(response && response.mensaje ? response.mensaje : "No se pudo consultar la auditoría");
                var data = response.depurar || {};
                var all = [].concat(data.solicitudes || [], data.ordenes || [], data.recepciones || []);
                document.querySelector("#ta_sin_destino").textContent = all.filter(function (row) { return row.clasificacion_transicion === "sin_destino"; }).length;
                document.querySelector("#ta_migrables").textContent = all.filter(function (row) { return row.clasificacion_transicion === "migrable_revision"; }).length;
                document.querySelector("#ta_conservar").textContent = all.filter(function (row) { return row.clasificacion_transicion === "conservar_origen"; }).length;
                renderWarehouses(data.almacenes || []);
                renderRows("#ta_solicitudes", data.solicitudes || [], "solicitud");
                renderRows("#ta_ordenes", data.ordenes || [], "orden");
                renderRows("#ta_recepciones", data.recepciones || [], "recepcion");
                alert.innerHTML = '<div class="alert alert-light-success">Auditoría actualizada. No se escribieron datos y no se movió inventario.</div>';
            })
            .catch(function (error) {
                alert.innerHTML = '<div class="alert alert-light-danger">' + escapeHtml(error.message || "Error al consultar") + "</div>";
            });
    };
    document.addEventListener("DOMContentLoaded", function () {
        var button = document.querySelector("#ta_btn_refrescar");
        if (button) button.addEventListener("click", load);
        load();
    });
})();
