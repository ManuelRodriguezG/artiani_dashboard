"use strict";
(function () {
    var catalogos = {almacenes: [], ubicaciones: []};
    var conteoActual = null;
    var detalleActual = [];

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
    function dinero(value) {
        return "$" + Number(value || 0).toLocaleString("es-MX", {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }
    function cargarCatalogos() {
        request("/inventario/catalogos_erp").then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            catalogos = response.depurar || {almacenes: [], ubicaciones: []};
            document.getElementById("conteo_almacen").innerHTML = "<option value=\"\">Seleccionar</option>" + catalogos.almacenes.map(function (item) {
                return "<option value=\"" + item.id_almacen + "\">" + escapeHtml(item.almacen) + "</option>";
            }).join("");
            actualizarUbicaciones();
        }).catch(mostrarError);
    }
    function actualizarUbicaciones() {
        var almacen = document.getElementById("conteo_almacen").value;
        document.getElementById("conteo_ubicacion").innerHTML = "<option value=\"\">Todas</option>" + catalogos.ubicaciones.filter(function (item) {
            return String(item.id_almacen) === String(almacen);
        }).map(function (item) {
            return "<option value=\"" + item.id_ubicacion + "\">" + escapeHtml(item.codigo_ubicacion + " - " + item.nombre) + "</option>";
        }).join("");
    }
    function cargarListado() {
        var params = new URLSearchParams({id_almacen: document.getElementById("conteo_almacen").value || ""});
        request("/inventario/conteos_listar_erp?" + params.toString()).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            renderListado(response.depurar || []);
        }).catch(mostrarError);
    }
    function renderListado(items) {
        document.getElementById("conteo_listado").innerHTML = items.map(function (item) {
            return "<tr><td><div class=\"fw-bold\">" + escapeHtml(item.folio) + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.tipo_conteo || "") + "</div></td>" +
                "<td>" + escapeHtml(item.almacen || "-") + "</td>" +
                "<td>" + escapeHtml(item.ubicacion || item.codigo_ubicacion || "-") + "</td>" +
                "<td><span class=\"badge badge-light-primary\">" + escapeHtml(item.estatus || "") + "</span></td>" +
                "<td>" + Number(item.capturadas || 0).toFixed(0) + " / " + Number(item.partidas || 0).toFixed(0) + "</td>" +
                "<td>" + Number(item.diferencias || 0).toFixed(0) + "</td>" +
                "<td>" + dinero(item.costo_diferencia || 0) + "</td>" +
                "<td class=\"text-end\"><button class=\"btn btn-sm btn-light-primary\" data-abrir-conteo=\"" + item.id_conteo_inventario + "\" type=\"button\"><i class=\"bi bi-pencil-square\"></i> Capturar</button></td></tr>";
        }).join("") || "<tr><td colspan=\"8\" class=\"text-center text-muted py-10\">Sin conteos registrados</td></tr>";
    }
    function crearConteo() {
        var almacen = document.getElementById("conteo_almacen").value;
        if (!almacen) {
            mostrarError(new Error("Selecciona almacen"));
            return;
        }
        request("/inventario/conteo_crear_erp", {
            id_almacen: almacen,
            ubicacion_id: document.getElementById("conteo_ubicacion").value,
            tipo_conteo: document.getElementById("conteo_tipo").value,
            fecha_programada: document.getElementById("conteo_fecha").value,
            observaciones: document.getElementById("conteo_observaciones").value
        }).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            Swal.fire({text: response.mensaje + ". Folio: " + response.depurar.folio, icon: "success", confirmButtonText: "Capturar"}).then(function () {
                cargarListado();
                abrirConteo(response.depurar.id_conteo_inventario);
            });
        }).catch(mostrarError);
    }
    function abrirConteo(idConteo) {
        request("/inventario/conteo_consultar_erp?" + new URLSearchParams({id_conteo_inventario: idConteo}).toString()).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            conteoActual = response.depurar.conteo;
            detalleActual = response.depurar.detalle || [];
            renderDetalle();
        }).catch(mostrarError);
    }
    function renderDetalle() {
        document.getElementById("conteo_detalle_card").classList.remove("d-none");
        document.getElementById("conteo_detalle_titulo").textContent = "Detalle " + (conteoActual ? conteoActual.folio : "");
        document.getElementById("conteo_detalle").innerHTML = detalleActual.map(function (item, index) {
            var fisica = item.cantidad_fisica === null || item.cantidad_fisica === undefined ? "" : Number(item.cantidad_fisica || 0).toFixed(4);
            var diferencia = fisica === "" ? 0 : numero(fisica) - Number(item.cantidad_sistema || 0);
            var clase = Math.abs(diferencia) < 0.0001 ? "text-muted" : (diferencia > 0 ? "text-success" : "text-danger");
            var motivo = item.motivo_diferencia || "";
            function optionMotivo(valor, texto) {
                return "<option value=\"" + valor + "\"" + (motivo === valor ? " selected" : "") + ">" + texto + "</option>";
            }
            return "<tr><td><div class=\"fw-bold\">" + escapeHtml(item.sku || "") + "</div><div class=\"text-muted fs-7\">" + escapeHtml(item.producto || "") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.codigo_existencia || "") + "</div></td>" +
                "<td>" + escapeHtml(item.lote || "-") + "<div class=\"text-muted fs-8\">" + escapeHtml(item.ubicacion || "") + "</div></td>" +
                "<td class=\"fw-bold\">" + Number(item.cantidad_sistema || 0).toFixed(4) + "</td>" +
                "<td><input class=\"form-control form-control-sm\" inputmode=\"decimal\" value=\"" + escapeHtml(fisica) + "\" data-conteo-fisico=\"" + index + "\"></td>" +
                "<td class=\"fw-bold " + clase + "\">" + diferencia.toFixed(4) + "</td>" +
                "<td><select class=\"form-select form-select-sm\" data-conteo-motivo=\"" + index + "\">" + optionMotivo("", "Sin diferencia") + optionMotivo("sobrante_conteo", "Sobrante") + optionMotivo("faltante_conteo", "Faltante") + optionMotivo("merma", "Merma") + optionMotivo("danado", "Danado") + optionMotivo("caducado", "Caducado") + "</select></td>" +
                "<td><input class=\"form-control form-control-sm\" value=\"" + escapeHtml(item.observaciones || "") + "\" data-conteo-notas=\"" + index + "\"></td></tr>";
        }).join("") || "<tr><td colspan=\"7\" class=\"text-center text-muted py-10\">Sin partidas</td></tr>";
    }
    function guardarCaptura() {
        if (!conteoActual) {
            return;
        }
        var items = detalleActual.filter(function (item) {
            return item.cantidad_fisica !== null && item.cantidad_fisica !== undefined && String(item.cantidad_fisica).trim() !== "";
        }).map(function (item) {
            return {
                id_conteo_detalle: item.id_conteo_detalle,
                cantidad_fisica: item.cantidad_fisica,
                motivo_diferencia: item.motivo_diferencia || "",
                observaciones: item.observaciones || ""
            };
        });
        if (!items.length) {
            mostrarError(new Error("Captura al menos una cantidad fisica"));
            return;
        }
        request("/inventario/conteo_capturar_erp", {
            id_conteo_inventario: conteoActual.id_conteo_inventario,
            items: JSON.stringify(items)
        }).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            Swal.fire({text: response.mensaje, icon: "success", confirmButtonText: "Aceptar"});
            abrirConteo(conteoActual.id_conteo_inventario);
            cargarListado();
        }).catch(mostrarError);
    }
    function cerrarConteo() {
        if (!conteoActual) {
            mostrarError(new Error("Abre un conteo"));
            return;
        }
        request("/inventario/conteo_preview_cierre_erp?" + new URLSearchParams({id_conteo_inventario: conteoActual.id_conteo_inventario}).toString()).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            var r = response.depurar || {};
            var texto = "Partidas: " + Number(r.partidas || 0).toFixed(0) +
                "\nPendientes: " + Number(r.pendientes || 0).toFixed(0) +
                "\nDiferencias: " + Number(r.diferencias || 0).toFixed(0) +
                "\nSobrante: " + Number(r.sobrante || 0).toFixed(4) +
                "\nFaltante: " + Number(r.faltante || 0).toFixed(4) +
                "\nCosto diferencia: " + dinero(r.costo_diferencia || 0);
            return Swal.fire({
                title: "Cerrar conteo " + (conteoActual.folio || ""),
                text: texto,
                icon: Number(r.pendientes || 0) > 0 ? "warning" : "question",
                showCancelButton: true,
                confirmButtonText: "Cerrar conteo",
                cancelButtonText: "Cancelar"
            });
        }).then(function (result) {
            if (!result || !result.isConfirmed) { return; }
            return request("/inventario/conteo_cerrar_erp", {
                id_conteo_inventario: conteoActual.id_conteo_inventario,
                observaciones: "Cierre desde pantalla de conteos"
            });
        }).then(function (response) {
            if (!response) { return; }
            if (response.error) { throw new Error(response.mensaje); }
            Swal.fire({text: response.mensaje + ". Movimientos: " + Number(response.depurar.movimientos || 0).toFixed(0), icon: "success", confirmButtonText: "Aceptar"});
            abrirConteo(conteoActual.id_conteo_inventario);
            cargarListado();
        }).catch(mostrarError);
    }
    function mostrarError(error) {
        Swal.fire({text: error.message || String(error), icon: "error", confirmButtonText: "Aceptar"});
    }

    document.addEventListener("DOMContentLoaded", function () {
        cargarCatalogos();
        cargarListado();
        document.getElementById("conteo_almacen").addEventListener("change", function () {
            actualizarUbicaciones();
            cargarListado();
        });
        document.getElementById("conteo_recargar").addEventListener("click", cargarListado);
        document.getElementById("conteo_crear").addEventListener("click", crearConteo);
        document.getElementById("conteo_guardar").addEventListener("click", guardarCaptura);
        document.getElementById("conteo_cerrar").addEventListener("click", cerrarConteo);
        document.getElementById("conteo_listado").addEventListener("click", function (event) {
            var boton = event.target.closest("[data-abrir-conteo]");
            if (boton) {
                abrirConteo(boton.getAttribute("data-abrir-conteo"));
            }
        });
        document.getElementById("conteo_detalle").addEventListener("input", function (event) {
            var fisico = event.target.getAttribute("data-conteo-fisico");
            var notas = event.target.getAttribute("data-conteo-notas");
            if (fisico !== null) {
                detalleActual[Number(fisico)].cantidad_fisica = event.target.value;
                renderDetalle();
            }
            if (notas !== null) {
                detalleActual[Number(notas)].observaciones = event.target.value;
            }
        });
        document.getElementById("conteo_detalle").addEventListener("change", function (event) {
            var motivo = event.target.getAttribute("data-conteo-motivo");
            if (motivo !== null) {
                detalleActual[Number(motivo)].motivo_diferencia = event.target.value;
            }
        });
    });
})();
