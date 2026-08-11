"use strict";
(function () {
    var catalogos = {almacenes: [], ubicaciones: [], esquema_reclasificacion: {disponible: false}};
    var origen = null;
    var destinos = [];
    var preview = null;
    var timer = null;
    var historialTimer = null;

    function request(url, data) {
        return fetch(url, {
            method: data ? "POST" : "GET",
            credentials: "same-origin",
            headers: data ? {"Content-Type": "application/x-www-form-urlencoded; charset=UTF-8", "X-CSRF-Token": window.ERP_CSRF_TOKEN || ""} : {},
            body: data ? new URLSearchParams(data).toString() : null
        }).then(function (response) { return response.json(); });
    }
    function escapeHtml(value) { var div = document.createElement("div"); div.textContent = value == null ? "" : String(value); return div.innerHTML; }
    function dinero(value) { return "$" + Number(value || 0).toLocaleString("es-MX", {minimumFractionDigits: 2, maximumFractionDigits: 2}); }
    function cantidad(value) { return Number(value || 0).toFixed(4); }
    function payload() {
        return {
            id_almacen: document.getElementById("reclas_almacen").value,
            id_existencia_origen: origen ? origen.id_existencia_inventario : "",
            id_unidad_origen: document.getElementById("reclas_unidad_origen").value || "",
            id_sku_reclasificacion: document.getElementById("reclas_sku_destino").value,
            id_sku_destino: destinoSeleccionado() ? destinoSeleccionado().id_sku_destino : "",
            cantidad: document.getElementById("reclas_cantidad").value,
            referencia: document.getElementById("reclas_referencia").value,
            motivo: document.getElementById("reclas_motivo").value,
            observaciones: document.getElementById("reclas_observaciones").value
        };
    }
    function destinoSeleccionado() {
        var id = document.getElementById("reclas_sku_destino").value;
        return destinos.find(function (item) { return String(item.id_sku_reclasificacion) === String(id); }) || null;
    }
    function cargarCatalogos() {
        request("/inventario/reclasificacion_catalogos_erp").then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            catalogos = response.depurar || catalogos;
            document.getElementById("reclas_almacen").innerHTML = "<option value=\"\">Seleccionar almacen</option>" + (catalogos.almacenes || []).map(function (item) {
                return "<option value=\"" + item.id_almacen + "\">" + escapeHtml(item.almacen) + "</option>";
            }).join("");
            renderAlertaEsquema();
            cargarHistorial();
        }).catch(mostrarError);
    }
    function renderAlertaEsquema() {
        var box = document.getElementById("reclas_alerta_esquema");
        var disponible = catalogos.esquema_reclasificacion && catalogos.esquema_reclasificacion.disponible;
        if (disponible) {
            box.innerHTML = "";
            return;
        }
        box.innerHTML = "<div class=\"alert alert-warning d-flex align-items-start\"><i class=\"bi bi-exclamation-triangle fs-2 me-3\"></i><div><div class=\"fw-bold\">Esquema pendiente</div><div class=\"fs-7\">La pantalla puede consultar origenes, pero para destinos y guardado falta aplicar el DDL de reclasificacion con respaldo y autorizacion.</div></div></div>";
    }
    function buscarOrigen() {
        clearTimeout(timer);
        var q = document.getElementById("reclas_buscar_origen").value.trim();
        var almacen = document.getElementById("reclas_almacen").value;
        if (!almacen || q.length < 2) {
            document.getElementById("reclas_resultados_origen").classList.add("d-none");
            return;
        }
        timer = setTimeout(function () {
            request("/inventario/reclasificacion_existencias_origen_erp?" + new URLSearchParams({id_almacen: almacen, q: q}).toString()).then(function (response) {
                if (response.error) { throw new Error(response.mensaje); }
                renderOrigenes(response.depurar || []);
            }).catch(mostrarError);
        }, 250);
    }
    function renderOrigenes(items) {
        var box = document.getElementById("reclas_resultados_origen");
        box.innerHTML = items.map(function (item, index) {
            return "<button type=\"button\" class=\"w-100 border-0 border-bottom bg-white text-start p-4\" data-origen=\"" + index + "\">" +
                "<div class=\"d-flex justify-content-between gap-3\"><div><strong>" + escapeHtml(item.sku) + "</strong><div class=\"text-muted fs-7\">" + escapeHtml(item.producto || item.nombre_sku || "") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.codigo_existencia || "") + " - " + escapeHtml(item.lote || "Sin lote") + "</div></div>" +
                "<div class=\"text-end\"><span class=\"badge badge-light-success\">Disp. " + cantidad(item.cantidad_disponible) + "</span>" + (Number(item.unidades_disponibles || 0) ? "<span class=\"badge badge-light-warning d-block mt-1\">Unidades " + Number(item.unidades_disponibles || 0).toFixed(0) + "</span>" : "") + "</div></div></button>";
        }).join("") || "<div class=\"p-5 text-muted\">Sin existencias disponibles</div>";
        box.classList.remove("d-none");
        box.querySelectorAll("[data-origen]").forEach(function (button) {
            button.addEventListener("click", function () {
                origen = items[Number(button.getAttribute("data-origen"))];
                box.classList.add("d-none");
                document.getElementById("reclas_buscar_origen").value = "";
                preview = null;
                renderOrigen();
                cargarDestinos();
            });
        });
    }
    function renderOrigen() {
        document.getElementById("reclas_guardar").disabled = true;
        if (!origen) {
            document.getElementById("reclas_origen_seleccionado").innerHTML = "Selecciona una existencia origen";
            return;
        }
        document.getElementById("reclas_origen_seleccionado").innerHTML =
            "<div class=\"fw-bold\">" + escapeHtml(origen.sku) + "</div>" +
            "<div class=\"text-muted fs-7 mb-2\">" + escapeHtml(origen.producto || origen.nombre_sku || "") + "</div>" +
            "<div class=\"d-flex flex-wrap gap-2\">" +
            "<span class=\"badge badge-light-primary\">" + escapeHtml(origen.codigo_existencia || "") + "</span>" +
            "<span class=\"badge badge-light-success\">Disponible " + cantidad(origen.cantidad_disponible) + "</span>" +
            "<span class=\"badge badge-light-info\">Costo " + dinero(origen.costo_promedio) + "</span>" +
            "</div><div class=\"text-muted fs-8 mt-2\">" + escapeHtml(origen.almacen || "") + " - " + escapeHtml(origen.lote || "Sin lote") + " - " + escapeHtml(origen.fecha_caducidad || "Sin caducidad") + " - " + escapeHtml(origen.ubicacion || "Sin ubicacion") + "</div>";
        var unidadWrap = document.getElementById("reclas_unidad_wrap");
        var unidadSelect = document.getElementById("reclas_unidad_origen");
        var unidades = origen.unidades || [];
        if (unidades.length) {
            unidadWrap.classList.remove("d-none");
            unidadSelect.innerHTML = "<option value=\"\">Seleccionar unidad</option>" + unidades.map(function (item) {
                var codigo = item.codigo_etiqueta_interna || item.codigo_unico || item.serie_fabricante || "";
                return "<option value=\"" + item.id_inventario_unidad + "\">" + escapeHtml(codigo) + " - " + cantidad(item.cantidad_base_disponible) + " " + escapeHtml(item.unidad_base || "") + " - " + escapeHtml(item.estado_fisico || "") + "</option>";
            }).join("");
        } else {
            unidadWrap.classList.add("d-none");
            unidadSelect.innerHTML = "";
        }
    }
    function cargarDestinos() {
        destinos = [];
        document.getElementById("reclas_sku_destino").innerHTML = "<option value=\"\">Consultando destinos</option>";
        request("/inventario/reclasificacion_destinos_erp?" + new URLSearchParams({id_sku_origen: origen.id_sku}).toString()).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            destinos = response.depurar || [];
            document.getElementById("reclas_sku_destino").innerHTML = "<option value=\"\">Seleccionar destino</option>" + destinos.map(function (item) {
                return "<option value=\"" + item.id_sku_reclasificacion + "\">" + escapeHtml(item.sku + " - " + (item.nombre_sku || item.producto || "")) + "</option>";
            }).join("");
        }).catch(function (error) {
            document.getElementById("reclas_sku_destino").innerHTML = "<option value=\"\">Sin destinos disponibles</option>";
            mostrarError(error);
        });
    }
    function previsualizar() {
        preview = null;
        document.getElementById("reclas_guardar").disabled = true;
        request("/inventario/reclasificacion_previsualizar_erp", payload()).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            preview = response.depurar || {};
            renderPreview();
            document.getElementById("reclas_guardar").disabled = false;
        }).catch(mostrarError);
    }
    function renderPreview() {
        var salida = preview.salida || {};
        var entrada = preview.entrada || {};
        document.getElementById("reclas_resumen").innerHTML =
            "<div class=\"alert alert-info py-3\"><strong>Folio:</strong> " + escapeHtml(preview.folio || "") + "</div>" +
            "<div class=\"row g-4\"><div class=\"col-md-6\"><div class=\"border rounded p-4 h-100\"><div class=\"text-danger fw-bold mb-2\"><i class=\"bi bi-box-arrow-up\"></i> Salida origen</div><div class=\"fw-bold\">" + escapeHtml(salida.sku || "") + "</div><div class=\"text-muted fs-7\">" + escapeHtml(salida.producto || "") + "</div><div class=\"mt-3\">Cantidad <strong>" + cantidad(salida.cantidad) + "</strong></div><div>Costo <strong>" + dinero(salida.costo_unitario) + "</strong></div><div class=\"text-muted fs-8 mt-2\">" + escapeHtml(salida.lote || "Sin lote") + " - " + escapeHtml(salida.fecha_caducidad || "Sin caducidad") + "</div></div></div>" +
            "<div class=\"col-md-6\"><div class=\"border rounded p-4 h-100\"><div class=\"text-success fw-bold mb-2\"><i class=\"bi bi-box-arrow-in-down\"></i> Entrada destino</div><div class=\"fw-bold\">" + escapeHtml(entrada.sku || "") + "</div><div class=\"mt-3\">Cantidad <strong>" + cantidad(entrada.cantidad) + "</strong></div><div>Costo <strong>" + dinero(entrada.costo_unitario) + "</strong></div><div class=\"text-muted fs-8 mt-2\">" + escapeHtml(entrada.lote || "Sin lote") + " - " + escapeHtml(entrada.fecha_caducidad || "Sin caducidad") + "</div></div></div></div>" +
            "<div class=\"mt-4\"><div class=\"fw-bold\">Motivo</div><div class=\"text-muted\">" + escapeHtml(preview.motivo || "") + "</div></div>";
    }
    function guardar() {
        if (!preview) {
            mostrarError(new Error("Previsualiza antes de guardar"));
            return;
        }
        Swal.fire({
            text: "Se generara kardex de salida y entrada con el mismo folio. Esta accion mueve inventario.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Guardar",
            cancelButtonText: "Cancelar"
        }).then(function (result) {
            if (!result.isConfirmed) { return; }
            request("/inventario/reclasificacion_guardar_erp", payload()).then(function (response) {
                if (response.error) { throw new Error(response.mensaje); }
                Swal.fire({text: response.mensaje + " - " + (response.depurar.folio || ""), icon: "success", confirmButtonText: "Aceptar"}).then(function () {
                    window.location.href = "/inventario/productos_existencias#kardex";
                });
            }).catch(mostrarError);
        });
    }
    function cargarHistorial() {
        var body = document.getElementById("reclas_historial_body");
        if (!body) {
            return;
        }
        var params = {
            id_almacen: document.getElementById("reclas_almacen").value || "",
            q: document.getElementById("reclas_historial_buscar").value || "",
            limite: 50
        };
        body.innerHTML = "<tr><td colspan=\"8\" class=\"text-center text-muted py-7\">Consultando historial</td></tr>";
        request("/inventario/reclasificacion_listar_erp?" + new URLSearchParams(params).toString()).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            renderHistorial(response.depurar || []);
        }).catch(function (error) {
            body.innerHTML = "<tr><td colspan=\"8\" class=\"text-center text-warning py-7\">" + escapeHtml(error.message || String(error)) + "</td></tr>";
        });
    }
    function renderHistorial(items) {
        var body = document.getElementById("reclas_historial_body");
        if (!body) {
            return;
        }
        body.innerHTML = items.map(function (item) {
            var movimientos = [];
            if (item.id_movimiento_salida) {
                movimientos.push("S:" + item.id_movimiento_salida);
            }
            if (item.id_movimiento_entrada) {
                movimientos.push("E:" + item.id_movimiento_entrada);
            }
            var unidadOrigen = item.unidad_origen_codigo ? "<div class=\"text-muted fs-8\">Unidad: " + escapeHtml(item.unidad_origen_codigo) + "</div>" : "";
            var unidadDestino = item.unidad_destino_codigo ? "<div class=\"text-muted fs-8\">Unidad: " + escapeHtml(item.unidad_destino_codigo) + "</div>" : "";
            return "<tr>" +
                "<td><div class=\"fw-bold\">" + escapeHtml(item.folio || "") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.fecha_reclasificacion || item.fecha_registro || "") + "</div></td>" +
                "<td><div class=\"fw-bold text-danger\">" + escapeHtml(item.sku_origen || "") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.producto_origen || "") + "</div>" + unidadOrigen + "</td>" +
                "<td><div class=\"fw-bold text-success\">" + escapeHtml(item.sku_destino || "") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.producto_destino || "") + "</div>" + unidadDestino + "</td>" +
                "<td>" + escapeHtml(item.almacen || "") + "</td>" +
                "<td>" + escapeHtml(item.lote || "Sin lote") + "<div class=\"text-muted fs-8\">" + escapeHtml(item.fecha_caducidad || "Sin caducidad") + "</div></td>" +
                "<td><div class=\"fw-bold\">" + cantidad(item.cantidad) + "</div><div class=\"text-muted fs-8\">" + dinero(item.costo_unitario_origen) + "</div></td>" +
                "<td>" + (movimientos.length ? movimientos.map(function (mov) { return "<span class=\"badge badge-light-info me-1\">" + escapeHtml(mov) + "</span>"; }).join("") : "<span class=\"text-muted\">Pendiente</span>") + "</td>" +
                "<td><span class=\"badge badge-light-" + (item.estatus === "confirmada" ? "success" : "secondary") + "\">" + escapeHtml(item.estatus || "") + "</span></td>" +
                "</tr>";
        }).join("") || "<tr><td colspan=\"8\" class=\"text-center text-muted py-7\">Sin reclasificaciones registradas</td></tr>";
    }
    function programarHistorial() {
        clearTimeout(historialTimer);
        historialTimer = setTimeout(cargarHistorial, 250);
    }
    function mostrarError(error) {
        Swal.fire({text: error.message || String(error), icon: "warning", confirmButtonText: "Aceptar"});
    }
    document.addEventListener("DOMContentLoaded", function () {
        cargarCatalogos();
        document.getElementById("reclas_buscar_origen").addEventListener("input", buscarOrigen);
        document.getElementById("reclas_almacen").addEventListener("change", function () {
            origen = null;
            destinos = [];
            preview = null;
            document.getElementById("reclas_sku_destino").innerHTML = "";
            document.getElementById("reclas_resumen").innerHTML = "<div class=\"text-muted py-8 text-center\">Previsualiza antes de guardar</div>";
            renderOrigen();
            cargarHistorial();
        });
        ["reclas_sku_destino", "reclas_cantidad", "reclas_unidad_origen", "reclas_motivo", "reclas_observaciones"].forEach(function (id) {
            document.getElementById(id).addEventListener("change", function () {
                preview = null;
                document.getElementById("reclas_guardar").disabled = true;
            });
        });
        document.getElementById("reclas_previsualizar").addEventListener("click", previsualizar);
        document.getElementById("reclas_guardar").addEventListener("click", guardar);
        document.getElementById("reclas_historial_recargar").addEventListener("click", cargarHistorial);
        document.getElementById("reclas_historial_buscar").addEventListener("input", programarHistorial);
    });
})();
