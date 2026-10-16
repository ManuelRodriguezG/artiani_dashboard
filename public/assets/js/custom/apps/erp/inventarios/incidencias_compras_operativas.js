"use strict";
(function () {
    function escapeHtml(value) {
        var div = document.createElement("div");
        div.textContent = value == null ? "" : String(value);
        return div.innerHTML;
    }
    function numero(value) {
        var parsed = Number(String(value == null ? "" : value).replace(/,/g, ""));
        return Number.isFinite(parsed) ? parsed : 0;
    }
    function dinero(value) {
        return "$" + numero(value).toLocaleString("es-MX", {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }
    function request(url, data) {
        return fetch(url, {
            method: data ? "POST" : "GET",
            headers: data ? {"Content-Type": "application/x-www-form-urlencoded; charset=UTF-8", "X-CSRF-Token": window.ERP_CSRF_TOKEN || ""} : {},
            body: data ? new URLSearchParams(data).toString() : null,
            credentials: "same-origin"
        }).then(function (response) { return response.json(); });
    }
    function error(response) { throw new Error(response.mensaje || "No se pudo completar la operación"); }
    function cargarAlmacenes() {
        request("/inventario/catalogos_erp").then(function (response) {
            if (response.error) { return error(response); }
            var items = response.depurar && response.depurar.almacenes ? response.depurar.almacenes : [];
            document.getElementById("incidencias_almacen").innerHTML = "<option value=\"\">Todos</option>" + items.map(function (item) {
                return "<option value=\"" + item.id_almacen + "\">" + escapeHtml(item.almacen) + "</option>";
            }).join("");
        }).catch(mostrarError);
    }
    function cargarListado() {
        var params = new URLSearchParams({
            id_almacen: document.getElementById("incidencias_almacen").value,
            estatus: document.getElementById("incidencias_estatus").value,
            q: document.getElementById("incidencias_busqueda").value.trim()
        });
        request("/inventario/incidencias_compras_operativas_listar_erp?" + params.toString()).then(function (response) {
            if (response.error) { return error(response); }
            renderListado(response.depurar && response.depurar.incidencias ? response.depurar.incidencias : []);
        }).catch(mostrarError);
    }
    function renderListado(items) {
        var tbody = document.getElementById("incidencias_listado");
        tbody.innerHTML = items.map(function (item) {
            var cerrado = ["resuelto", "descartado"].indexOf(String(item.estatus)) >= 0;
            var reportado = item.cantidad_reportada === null || item.cantidad_reportada === "" ? "Sin capturar" : numero(item.cantidad_reportada).toFixed(4) + " (" + escapeHtml(item.tipo_cantidad_reportada || "") + ")";
            return "<tr><td><div class=\"fw-bold\">" + escapeHtml(item.folio_orden || ("OC-" + item.id_origen)) + "</div><div class=\"text-muted fs-8\">Detalle " + escapeHtml(item.id_origen_detalle || "-") + "</div></td>" +
                "<td><div class=\"fw-bold\">" + escapeHtml(item.sku || "-") + "</div><div class=\"text-muted fs-7\">" + escapeHtml(item.nombre_producto || "") + "</div></td>" +
                "<td>" + escapeHtml(item.nombre_almacen || (numero(item.id_almacen) ? "Almacén " + item.id_almacen : "Sin asignar")) + "</td>" +
                "<td class=\"text-end\">" + numero(item.cantidad_comprada).toFixed(4) + "</td><td class=\"text-end\">" + dinero(item.costo_unitario) + "</td>" +
                "<td><span class=\"badge badge-light-primary\">" + escapeHtml(item.estatus || "") + "</span></td><td>" + reportado + "</td>" +
                "<td class=\"text-end\">" + (cerrado ? "<span class=\"text-muted fs-8\">Sin acciones</span>" : "<button type=\"button\" class=\"btn btn-sm btn-light-primary\" data-capturar=\"" + item.id_incidencia_inventario + "\"><i class=\"bi bi-pencil-square\"></i> Capturar</button>") + "</td></tr>";
        }).join("") || "<tr><td colspan=\"8\" class=\"text-center text-muted py-10\">No hay incidencias para los filtros seleccionados</td></tr>";
    }
    function capturar(id) {
        Swal.fire({
            title: "Capturar cantidad",
            html: "<input id=\"incidencia_cantidad\" class=\"swal2-input\" type=\"number\" min=\"0\" step=\"0.000001\" placeholder=\"Cantidad reportada\"><select id=\"incidencia_tipo\" class=\"swal2-select\"><option value=\"conteo_fisico\">Conteo físico</option><option value=\"estimada\">Cantidad estimada</option></select><textarea id=\"incidencia_observaciones\" class=\"swal2-textarea\" placeholder=\"Observaciones\"></textarea>",
            showCancelButton: true,
            confirmButtonText: "Guardar captura",
            cancelButtonText: "Cancelar",
            focusConfirm: false,
            preConfirm: function () {
                var cantidad = document.getElementById("incidencia_cantidad").value;
                if (cantidad === "" || numero(cantidad) < 0) { Swal.showValidationMessage("Captura una cantidad válida"); return false; }
                return {cantidad_reportada: cantidad, tipo_cantidad_reportada: document.getElementById("incidencia_tipo").value, observaciones: document.getElementById("incidencia_observaciones").value};
            }
        }).then(function (result) {
            if (!result.isConfirmed) { return; }
            var datos = result.value || {};
            datos.id_incidencia_inventario = id;
            return request("/inventario/incidencia_compra_operativa_capturar_erp", datos);
        }).then(function (response) {
            if (!response) { return; }
            if (response.error) { return error(response); }
            return Swal.fire({text: response.mensaje, icon: "success", confirmButtonText: "Aceptar"});
        }).then(function () { cargarListado(); }).catch(mostrarError);
    }
    function mostrarError(error) { Swal.fire({text: error.message || String(error), icon: "error", confirmButtonText: "Aceptar"}); }
    document.addEventListener("DOMContentLoaded", function () {
        cargarAlmacenes();
        cargarListado();
        document.getElementById("incidencias_recargar").addEventListener("click", cargarListado);
        document.getElementById("incidencias_buscar").addEventListener("click", cargarListado);
        document.getElementById("incidencias_almacen").addEventListener("change", cargarListado);
        document.getElementById("incidencias_estatus").addEventListener("change", cargarListado);
        document.getElementById("incidencias_busqueda").addEventListener("keydown", function (event) { if (event.key === "Enter") { cargarListado(); } });
        document.getElementById("incidencias_listado").addEventListener("click", function (event) {
            var button = event.target.closest("[data-capturar]");
            if (button) { capturar(button.getAttribute("data-capturar")); }
        });
    });
})();
