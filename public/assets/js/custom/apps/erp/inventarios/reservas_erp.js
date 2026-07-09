"use strict";
(function () {
    var catalogos = {almacenes: []};
    var existencias = [];
    var seleccion = null;

    function request(url, data) {
        return fetch(url, {
            method: data ? "POST" : "GET",
            headers: data ? {
                "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
                "X-CSRF-Token": window.ERP_CSRF_TOKEN || ""
            } : {},
            body: data ? new URLSearchParams(data).toString() : null,
            credentials: "same-origin"
        }).then(function (response) { return response.json(); });
    }
    function escapeHtml(value) { var div = document.createElement("div"); div.textContent = value == null ? "" : String(value); return div.innerHTML; }
    function numero(value) {
        var parsed = Number(String(value == null ? "" : value).replace(",", "."));
        return Number.isFinite(parsed) ? parsed : 0;
    }
    function cargarCatalogos() {
        request("/inventario/catalogos_erp").then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            catalogos = response.depurar || {almacenes: []};
            document.getElementById("reserva_almacen").innerHTML = "<option value=\"\">Todos</option>" + catalogos.almacenes.map(function (item) {
                return "<option value=\"" + item.id_almacen + "\">" + escapeHtml(item.almacen) + "</option>";
            }).join("");
        }).catch(mostrarError);
    }
    function buscarExistencias() {
        var params = new URLSearchParams({
            id_almacen: document.getElementById("reserva_almacen").value || "",
            q: document.getElementById("reserva_busqueda").value || "",
            incluir_agotadas: "0"
        });
        request("/inventario/existencias_erp?" + params.toString()).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            existencias = (response.depurar || []).filter(function (item) {
                return Number(item.cantidad_disponible || 0) > 0;
            });
            renderExistencias();
        }).catch(mostrarError);
    }
    function renderExistencias() {
        document.getElementById("reserva_existencias").innerHTML = existencias.map(function (item, index) {
            return "<tr><td class=\"fw-bold\">" + escapeHtml(item.codigo_existencia || "") + "</td>" +
                "<td>" + escapeHtml(item.sku || "") + "<div class=\"text-muted fs-8\">" + escapeHtml(item.nombre_sku || item.producto || "") + "</div></td>" +
                "<td>" + escapeHtml(item.almacen || "-") + "</td>" +
                "<td>" + escapeHtml(item.lote || "-") + "<div class=\"text-muted fs-8\">" + escapeHtml(item.ubicacion || "") + "</div></td>" +
                "<td>" + Number(item.cantidad || 0).toFixed(4) + "</td>" +
                "<td class=\"text-success fw-bold\">" + Number(item.cantidad_disponible || 0).toFixed(4) + "</td>" +
                "<td class=\"text-end\"><button class=\"btn btn-sm btn-light-primary\" data-reserva-seleccionar=\"" + index + "\" type=\"button\"><i class=\"bi bi-check2-circle\"></i> Seleccionar</button></td></tr>";
        }).join("") || "<tr><td colspan=\"7\" class=\"text-center text-muted py-10\">Sin existencias disponibles</td></tr>";
    }
    function seleccionarExistencia(index) {
        seleccion = existencias[Number(index)] || null;
        if (!seleccion) { return; }
        document.getElementById("reserva_seleccion").classList.remove("d-none");
        document.getElementById("reserva_seleccion").innerHTML =
            "<div class=\"fw-bold\">" + escapeHtml(seleccion.codigo_existencia || "") + " · " + escapeHtml(seleccion.sku || "") + "</div>" +
            "<div class=\"text-muted\">" + escapeHtml(seleccion.nombre_sku || seleccion.producto || "") + "</div>" +
            "<div class=\"mt-2\">Disponible: <span class=\"fw-bold text-success\">" + Number(seleccion.cantidad_disponible || 0).toFixed(4) + "</span> · Lote: " + escapeHtml(seleccion.lote || "-") + " · " + escapeHtml(seleccion.almacen || "-") + "</div>";
        document.getElementById("reserva_cantidad").focus();
    }
    function crearReserva() {
        if (!seleccion) {
            mostrarError(new Error("Selecciona una existencia"));
            return;
        }
        var cantidad = numero(document.getElementById("reserva_cantidad").value);
        if (cantidad <= 0) {
            mostrarError(new Error("Captura una cantidad mayor a cero"));
            return;
        }
        if (cantidad > Number(seleccion.cantidad_disponible || 0) + 0.0001) {
            mostrarError(new Error("La cantidad supera lo disponible"));
            return;
        }
        request("/inventario/reserva_crear_erp", {
            id_existencia_inventario: seleccion.id_existencia_inventario,
            cantidad: cantidad.toFixed(6),
            origen_tipo: document.getElementById("reserva_origen").value,
            fecha_vencimiento: document.getElementById("reserva_vencimiento").value,
            observaciones: document.getElementById("reserva_observaciones").value
        }).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            Swal.fire({text: response.mensaje + ". Folio: " + response.depurar.folio, icon: "success", confirmButtonText: "Aceptar"});
            seleccion = null;
            document.getElementById("reserva_seleccion").classList.add("d-none");
            document.getElementById("reserva_cantidad").value = "";
            buscarExistencias();
            cargarReservas();
        }).catch(mostrarError);
    }
    function cargarReservas() {
        var params = new URLSearchParams({
            id_almacen: document.getElementById("reserva_almacen").value || "",
            estatus: document.getElementById("reserva_estatus").value || "",
            q: document.getElementById("reserva_busqueda").value || ""
        });
        request("/inventario/reservas_listar_erp?" + params.toString()).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            renderReservas(response.depurar || []);
        }).catch(mostrarError);
    }
    function renderReservas(items) {
        document.getElementById("reserva_listado").innerHTML = items.map(function (item) {
            var activa = item.estatus === "activa";
            return "<tr><td><div class=\"fw-bold\">" + escapeHtml(item.folio || "") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.origen_tipo || "") + "</div></td>" +
                "<td>" + escapeHtml(item.sku || "") + "<div class=\"text-muted fs-8\">" + escapeHtml(item.codigo_existencia || "") + "</div></td>" +
                "<td>" + escapeHtml(item.almacen || "-") + "</td>" +
                "<td>" + escapeHtml(item.lote || "-") + "<div class=\"text-muted fs-8\">" + escapeHtml(item.fecha_caducidad || "") + "</div></td>" +
                "<td>" + Number(item.cantidad_reservada || 0).toFixed(4) + "</td>" +
                "<td class=\"fw-bold\">" + Number(item.cantidad_pendiente || 0).toFixed(4) + "</td>" +
                "<td><span class=\"badge " + (activa ? "badge-light-primary" : "badge-light-secondary") + "\">" + escapeHtml(item.estatus || "") + "</span></td>" +
                "<td class=\"text-end\">" + (activa ? "<button class=\"btn btn-sm btn-light-danger\" data-reserva-liberar=\"" + item.id_reserva_inventario + "\" data-folio=\"" + escapeHtml(item.folio || "") + "\" type=\"button\"><i class=\"bi bi-unlock\"></i> Liberar</button>" : "") + "</td></tr>";
        }).join("") || "<tr><td colspan=\"8\" class=\"text-center text-muted py-10\">Sin reservas registradas</td></tr>";
    }
    function liberarReserva(idReserva, folio) {
        Swal.fire({
            title: "Liberar reserva",
            text: folio || "",
            icon: "question",
            showCancelButton: true,
            confirmButtonText: "Liberar",
            cancelButtonText: "Cancelar"
        }).then(function (result) {
            if (!result.isConfirmed) { return null; }
            return request("/inventario/reserva_liberar_erp", {
                id_reserva_inventario: idReserva,
                observaciones: "Liberacion desde pantalla de reservas"
            });
        }).then(function (response) {
            if (!response) { return; }
            if (response.error) { throw new Error(response.mensaje); }
            Swal.fire({text: response.mensaje, icon: "success", confirmButtonText: "Aceptar"});
            buscarExistencias();
            cargarReservas();
        }).catch(mostrarError);
    }
    function mostrarError(error) {
        Swal.fire({text: error.message || String(error), icon: "error", confirmButtonText: "Aceptar"});
    }

    document.addEventListener("DOMContentLoaded", function () {
        cargarCatalogos();
        buscarExistencias();
        cargarReservas();
        document.getElementById("reserva_buscar").addEventListener("click", function () {
            buscarExistencias();
            cargarReservas();
        });
        document.getElementById("reserva_recargar").addEventListener("click", cargarReservas);
        document.getElementById("reserva_crear").addEventListener("click", crearReserva);
        document.getElementById("reserva_almacen").addEventListener("change", function () {
            seleccion = null;
            document.getElementById("reserva_seleccion").classList.add("d-none");
            buscarExistencias();
            cargarReservas();
        });
        document.getElementById("reserva_estatus").addEventListener("change", cargarReservas);
        document.getElementById("reserva_existencias").addEventListener("click", function (event) {
            var boton = event.target.closest("[data-reserva-seleccionar]");
            if (boton) {
                seleccionarExistencia(boton.getAttribute("data-reserva-seleccionar"));
            }
        });
        document.getElementById("reserva_listado").addEventListener("click", function (event) {
            var boton = event.target.closest("[data-reserva-liberar]");
            if (boton) {
                liberarReserva(boton.getAttribute("data-reserva-liberar"), boton.getAttribute("data-folio"));
            }
        });
    });
})();
