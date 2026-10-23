"use strict";
(function () {
    var estado = {venta: null, partidas: [], almacenes: []};

    function escapeHtml(value) { var div = document.createElement("div"); div.textContent = value == null ? "" : String(value); return div.innerHTML; }
    function money(value) { return new Intl.NumberFormat("es-MX", {style: "currency", currency: "MXN"}).format(Number(value || 0)); }
    function request(data) {
        return fetch("/ventas/ventas_historicas_correccion_dryrun_erp", {
            method: "POST",
            headers: {"Content-Type": "application/x-www-form-urlencoded; charset=UTF-8", "X-CSRF-Token": window.ERP_CSRF_TOKEN || ""},
            body: new URLSearchParams(data).toString(), credentials: "same-origin"
        }).then(function (response) { return response.json(); });
    }
    function alerta(tipo, mensaje) { document.getElementById("vhc_alerta").innerHTML = "<div class=\"alert alert-" + tipo + " py-3 mb-0\">" + escapeHtml(mensaje) + "</div>"; }
    function almacenOptions(selected) {
        return estado.almacenes.map(function (item) {
            var value = String(item.id_almacen || "");
            var label = item.nombre_comercial || item.almacen || item.codigo_almacen || value;
            return "<option value=\"" + escapeHtml(value) + "\"" + (String(selected) === value ? " selected" : "") + ">" + escapeHtml(label) + "</option>";
        }).join("");
    }
    function renderPartidas() {
        var body = document.getElementById("vhc_partidas");
        if (!estado.partidas.length) { body.innerHTML = "<tr><td colspan=\"5\" class=\"text-center text-muted py-10\">No hay partidas para mostrar.</td></tr>"; return; }
        body.innerHTML = estado.partidas.map(function (item, index) {
            return "<tr data-index=\"" + index + "\"><td>Renglon " + escapeHtml(item.renglon || "-") + "<div class=\"text-muted fs-8\">Detalle " + escapeHtml(item.id_venta_detalle) + "</div></td>" +
                "<td><div class=\"fw-bold\">" + escapeHtml(item.sku || "VENTA-RAPIDA") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.descripcion || "") + "</div></td>" +
                "<td>" + escapeHtml(item.almacen_actual || "Sin almacen") + "</td>" +
                "<td><select class=\"form-select form-select-sm vhc-almacen\" data-index=\"" + index + "\">" + almacenOptions(item.id_almacen_propuesto) + "</select></td>" +
                "<td class=\"text-end fw-bold\">" + money(item.total || item.subtotal || 0) + "</td></tr>";
        }).join("");
        body.querySelectorAll(".vhc-almacen").forEach(function (select) { select.addEventListener("change", function () { estado.partidas[Number(select.dataset.index)].id_almacen_propuesto = Number(select.value || 0); }); });
    }
    function payload() {
        return {folio: document.getElementById("vhc_folio").value.trim(), correcciones: JSON.stringify(estado.partidas.map(function (item) { return {id_venta_detalle: item.id_venta_detalle, id_almacen_nuevo: item.id_almacen_propuesto}; }))};
    }
    function renderRespuesta(response) {
        var data = response.depurar || {};
        var tipo = response.error ? "danger" : (response.tipo || "info");
        var html = "<div class=\"fw-bold\">" + escapeHtml(response.mensaje || "Respuesta") + "</div>";
        if (data.venta && data.venta.folio) { html += "<div class=\"fs-7\">Folio: <strong>" + escapeHtml(data.venta.folio) + "</strong> | Cabecera: almacen " + escapeHtml(data.venta.id_almacen || "-") + "</div>"; }
        if (data.cambios && data.cambios.length) { html += "<div class=\"mt-2\"><strong>Cambios propuestos: " + data.cambios.length + "</strong><ul class=\"mb-0\">" + data.cambios.map(function (item) { return "<li>Detalle " + escapeHtml(item.id_venta_detalle) + ": " + escapeHtml(item.almacen_actual) + " &rarr; " + escapeHtml(item.almacen_propuesto) + "</li>"; }).join("") + "</ul></div>"; }
        if (data.avisos && data.avisos.length) { html += "<div class=\"mt-2\"><strong>Avisos</strong><ul class=\"mb-0\">" + data.avisos.map(function (item) { return "<li>" + escapeHtml(item) + "</li>"; }).join("") + "</ul></div>"; }
        if (data.bloqueos && data.bloqueos.length) { html += "<div class=\"mt-2\"><strong>Bloqueos</strong><ul class=\"mb-0\">" + data.bloqueos.map(function (item) { return "<li>" + escapeHtml(item) + "</li>"; }).join("") + "</ul></div>"; }
        document.getElementById("vhc_resultado").className = "alert alert-light-" + tipo + " mt-4 mb-0";
        document.getElementById("vhc_resultado").innerHTML = html;
    }
    function consultar() {
        var folio = document.getElementById("vhc_folio").value.trim();
        if (!folio) { alerta("warning", "Captura el folio historico completo."); return; }
        document.getElementById("vhc_resultado").textContent = "Consultando...";
        request({folio: folio, correcciones: "[]"}).then(function (response) {
            renderRespuesta(response);
            var data = response.depurar || {};
            estado.venta = data.venta || null;
            estado.partidas = data.partidas || [];
            estado.almacenes = data.almacenes || [];
            renderPartidas();
            document.getElementById("vhc_prevalidar").disabled = !estado.partidas.length;
            document.getElementById("vhc_resumen").textContent = estado.venta ? (estado.venta.estatus || "Venta historica") + " | " + estado.partidas.length + " partida(s)" : "No se pudo cargar la venta";
            alerta(response.error ? "danger" : (response.tipo === "warning" ? "warning" : "info"), response.mensaje || "Consulta completada");
        }).catch(function (error) { alerta("danger", error.message || String(error)); });
    }
    function prevalidar() { request(payload()).then(renderRespuesta).catch(function (error) { alerta("danger", error.message || String(error)); }); }
    document.getElementById("vhc_consultar").addEventListener("click", consultar);
    document.getElementById("vhc_prevalidar").addEventListener("click", prevalidar);
    document.getElementById("vhc_folio").addEventListener("keydown", function (event) { if (event.key === "Enter") { event.preventDefault(); consultar(); } });
})();
