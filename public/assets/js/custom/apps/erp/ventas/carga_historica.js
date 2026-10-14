"use strict";
(function () {
    var estado = {almacenes: [], metodos: [], acciones: {}, partidas: []};

    /**
     * IA: Codex GPT-5 | Fecha: 2026-10-14
     * Proposito: operar captura historica de ventas con acciones de regularizacion.
     * Impacto: prevalidacion/guardado documental; no usa turno POS ni mueve inventario desde UI.
     */
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

    function escapeHtml(value) {
        var div = document.createElement("div");
        div.textContent = value == null ? "" : String(value);
        return div.innerHTML;
    }

    function money(value) {
        return new Intl.NumberFormat("es-MX", {style: "currency", currency: "MXN"}).format(Number(value || 0));
    }

    function numero(value) {
        var parsed = Number(value || 0);
        return Number.isFinite(parsed) ? parsed : 0;
    }

    function setToday() {
        var now = new Date();
        now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
        document.getElementById("vh_fecha").value = now.toISOString().slice(0, 16);
    }

    function cargarCatalogos() {
        request("/ventas/ventas_historicas_catalogos_erp").then(function (response) {
            if (response.error) { throw new Error(response.mensaje || "No se pudo cargar catalogos"); }
            var data = response.depurar || {};
            estado.almacenes = data.almacenes || [];
            estado.metodos = data.metodos_pago || [];
            estado.acciones = data.acciones_regularizacion || {};
            llenarSelectores();
            alerta(data.schema_historico_listo ? "info" : "warning", data.schema_historico_listo
                ? "Carga historica lista para prevalidar. Guardar real requiere token."
                : "Faltan columnas de carga historica. Se puede preparar captura, pero guardar quedara bloqueado.");
        }).catch(function (error) {
            alerta("danger", error.message || String(error));
        });
    }

    function llenarSelectores() {
        var almacenesHtml = estado.almacenes.map(function (item) {
            return "<option value=\"" + escapeHtml(item.id_almacen) + "\">" +
                escapeHtml((item.codigo_almacen || item.id_almacen) + " - " + (item.almacen || item.nombre_comercial || "")) +
                "</option>";
        }).join("") || "<option value=\"\">Sin almacenes</option>";
        document.getElementById("vh_almacen").innerHTML = almacenesHtml;
        var metodosHtml = estado.metodos.map(function (item) {
            return "<option value=\"" + escapeHtml(item.id_metodo_pago || "") + "\">" +
                escapeHtml(item.metodo_pago || item.nombre || ("Metodo " + item.id_metodo_pago)) + "</option>";
        }).join("") || "<option value=\"\">No especificado</option>";
        document.getElementById("vh_metodo_pago").innerHTML = "<option value=\"\">No especificado</option>" + metodosHtml;
    }

    function buscar() {
        var q = document.getElementById("vh_buscar").value.trim();
        if (q.length < 2) {
            document.getElementById("vh_resultados").innerHTML = empty("Escribe al menos 2 caracteres.");
            return;
        }
        var params = new URLSearchParams({q: q, id_almacen: document.getElementById("vh_almacen").value || "0", limite: "12"});
        document.getElementById("vh_resultados").innerHTML = empty("Buscando...");
        request("/ventas/pos_buscar_skus_erp?" + params.toString()).then(function (response) {
            if (response.error) { throw new Error(response.mensaje || "No se pudo buscar"); }
            var productos = Array.isArray(response.depurar) ? response.depurar : ((response.depurar || {}).skus || []);
            renderResultados(productos);
        }).catch(function (error) {
            document.getElementById("vh_resultados").innerHTML = empty(error.message || String(error));
        });
    }

    function renderResultados(productos) {
        if (!productos.length) {
            document.getElementById("vh_resultados").innerHTML = empty("Sin resultados. Puedes agregarlo por clasificar.");
            return;
        }
        document.getElementById("vh_resultados").innerHTML = productos.map(function (item) {
            var precio = numero(item.precio || item.precio_unitario || 0);
            var nombre = item.nombre_sku || item.producto || item.descripcion || item.sku || "Producto";
            return "<div class=\"vh-row\" data-id-sku=\"" + escapeHtml(item.id_sku || "") + "\" data-sku=\"" + escapeHtml(item.sku || "") + "\" data-nombre=\"" + escapeHtml(nombre) + "\" data-precio=\"" + escapeHtml(precio) + "\">" +
                "<div class=\"d-flex justify-content-between gap-3\">" +
                "<div><div class=\"fw-bold vh-product-title\">" + escapeHtml(nombre) + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.sku || "") + "</div></div>" +
                "<div class=\"text-end\"><div class=\"fw-bold\">" + money(precio) + "</div><button class=\"btn btn-sm btn-light-primary mt-2 vh-add-sku\" type=\"button\">Agregar</button></div>" +
                "</div></div>";
        }).join("");
    }

    function agregarSku(row) {
        estado.partidas.push({
            id_sku: Number(row.getAttribute("data-id-sku") || 0),
            sku: row.getAttribute("data-sku") || "",
            descripcion: row.getAttribute("data-nombre") || "",
            id_almacen: Number(document.getElementById("vh_almacen").value || 0),
            cantidad: 1,
            unidad_venta: "pza",
            precio_unitario: numero(row.getAttribute("data-precio") || 0),
            accion_regularizacion: "revision_inventario"
        });
        renderPartidas();
    }

    function agregarRapida() {
        var descripcion = document.getElementById("vh_rapida_desc").value.trim();
        if (!descripcion) {
            alerta("warning", "Escribe una descripcion para producto por clasificar.");
            return;
        }
        estado.partidas.push({
            id_sku: 0,
            sku: "VENTA-RAPIDA",
            descripcion: descripcion,
            id_almacen: Number(document.getElementById("vh_almacen").value || 0),
            cantidad: numero(document.getElementById("vh_rapida_cantidad").value || 1),
            unidad_venta: "pza",
            precio_unitario: numero(document.getElementById("vh_rapida_precio").value || 0),
            accion_regularizacion: "clasificar_sku"
        });
        document.getElementById("vh_rapida_desc").value = "";
        document.getElementById("vh_rapida_precio").value = "";
        renderPartidas();
    }

    function renderPartidas() {
        var body = document.getElementById("vh_partidas");
        if (!estado.partidas.length) {
            body.innerHTML = "<tr><td colspan=\"7\" class=\"text-center text-muted py-10\">Agrega productos para capturar la venta historica.</td></tr>";
            recalcular();
            return;
        }
        body.innerHTML = estado.partidas.map(function (item, index) {
            return "<tr data-index=\"" + index + "\">" +
                "<td><div class=\"fw-bold vh-product-title\">" + escapeHtml(item.descripcion) + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.sku || "VENTA-RAPIDA") + "</div></td>" +
                "<td><select class=\"form-select form-select-sm vh-item-almacen\">" + opcionesAlmacen(item.id_almacen) + "</select></td>" +
                "<td><input class=\"form-control form-control-sm text-end vh-item-cantidad\" type=\"number\" min=\"0\" step=\"0.001\" value=\"" + escapeHtml(item.cantidad) + "\"></td>" +
                "<td><input class=\"form-control form-control-sm text-end vh-item-precio\" type=\"number\" min=\"0\" step=\"0.01\" value=\"" + escapeHtml(item.precio_unitario) + "\"></td>" +
                "<td class=\"text-end fw-bold vh-item-importe\">" + money(numero(item.cantidad) * numero(item.precio_unitario)) + "</td>" +
                "<td><select class=\"form-select form-select-sm vh-item-accion\">" + opcionesAccion(item.accion_regularizacion) + "</select></td>" +
                "<td class=\"text-end\"><button class=\"btn btn-sm btn-light-danger vh-item-quitar\" type=\"button\"><i class=\"bi bi-x\"></i></button></td>" +
                "</tr>";
        }).join("");
        recalcular();
    }

    function opcionesAlmacen(selected) {
        return estado.almacenes.map(function (item) {
            var value = String(item.id_almacen || "");
            return "<option value=\"" + escapeHtml(value) + "\"" + (String(selected) === value ? " selected" : "") + ">" +
                escapeHtml(item.codigo_almacen || item.almacen || value) + "</option>";
        }).join("");
    }

    function opcionesAccion(selected) {
        var html = "<option value=\"\">Sin accion</option>";
        Object.keys(estado.acciones).forEach(function (key) {
            html += "<option value=\"" + escapeHtml(key) + "\"" + (selected === key ? " selected" : "") + ">" +
                escapeHtml(estado.acciones[key].nombre || key) + "</option>";
        });
        return html;
    }

    function sincronizarPartida(row) {
        var index = Number(row.getAttribute("data-index"));
        if (!estado.partidas[index]) { return; }
        estado.partidas[index].id_almacen = Number(row.querySelector(".vh-item-almacen").value || 0);
        estado.partidas[index].cantidad = numero(row.querySelector(".vh-item-cantidad").value || 0);
        estado.partidas[index].precio_unitario = numero(row.querySelector(".vh-item-precio").value || 0);
        estado.partidas[index].accion_regularizacion = row.querySelector(".vh-item-accion").value || "";
        row.querySelector(".vh-item-importe").textContent = money(estado.partidas[index].cantidad * estado.partidas[index].precio_unitario);
        recalcular();
    }

    function recalcular() {
        var subtotal = estado.partidas.reduce(function (sum, item) {
            return sum + numero(item.cantidad) * numero(item.precio_unitario);
        }, 0);
        var descuento = numero(document.getElementById("vh_descuento").value || 0);
        var total = Math.max(0, subtotal - descuento);
        document.getElementById("vh_subtotal").textContent = money(subtotal);
        document.getElementById("vh_total").textContent = money(total);
        if (!document.getElementById("vh_pagado").value) {
            document.getElementById("vh_pagado").value = total.toFixed(2);
        }
    }

    function payload() {
        return {
            fecha_venta_real: document.getElementById("vh_fecha").value,
            id_almacen: document.getElementById("vh_almacen").value || "0",
            cliente_nombre_publico: document.getElementById("vh_cliente").value || "Cliente historico",
            id_metodo_pago: document.getElementById("vh_metodo_pago").value || "0",
            observaciones: document.getElementById("vh_observaciones").value || "Carga historica de ventas",
            descuento_total: document.getElementById("vh_descuento").value || "0",
            pagado_total: document.getElementById("vh_pagado").value || "0",
            token: document.getElementById("vh_token").value || "",
            items: JSON.stringify(estado.partidas)
        };
    }

    function prevalidar() {
        request("/ventas/ventas_historicas_dryrun_erp", payload()).then(function (response) {
            renderRespuesta(response, false);
        }).catch(function (error) {
            resultado("danger", error.message || String(error));
        });
    }

    function guardar() {
        request("/ventas/ventas_historicas_guardar_erp", payload()).then(function (response) {
            renderRespuesta(response, true);
            if (!response.error && response.tipo === "success") {
                estado.partidas = [];
                renderPartidas();
            }
        }).catch(function (error) {
            resultado("danger", error.message || String(error));
        });
    }

    function renderRespuesta(response, esGuardado) {
        var data = response.depurar || {};
        var tipo = response.error ? "danger" : (response.tipo || "info");
        var html = "<div class=\"fw-bold\">" + escapeHtml(response.mensaje || "Respuesta") + "</div>";
        if (data.folio) {
            html += "<div class=\"fs-7\">Folio: <span class=\"fw-bold\">" + escapeHtml(data.folio) + "</span></div>";
        }
        var bloqueos = data.bloqueos || [];
        if (bloqueos.length) {
            html += "<ul class=\"mb-0 mt-2\">" + bloqueos.map(function (b) { return "<li>" + escapeHtml(b) + "</li>"; }).join("") + "</ul>";
        }
        var acciones = data.acciones || data.acciones_regularizacion || [];
        if (acciones.length) {
            html += "<div class=\"fs-7 mt-2\">" + escapeHtml(String(acciones.length)) + " accion(es) " + (esGuardado ? "generadas" : "detectadas") + ".</div>";
        }
        resultado(tipo, html, true);
    }

    function alerta(tipo, mensaje) {
        document.getElementById("vh_alerta").innerHTML = "<div class=\"alert alert-" + tipo + " py-3 mb-0\">" + escapeHtml(mensaje) + "</div>";
    }

    function resultado(tipo, mensaje, html) {
        document.getElementById("vh_resultado").className = "alert alert-light-" + (tipo === "danger" ? "danger" : tipo) + " py-3 mb-0";
        document.getElementById("vh_resultado").innerHTML = html ? mensaje : escapeHtml(mensaje);
    }

    function empty(texto) {
        return "<div class=\"text-center text-muted py-8\">" + escapeHtml(texto) + "</div>";
    }

    document.addEventListener("click", function (event) {
        var add = event.target.closest(".vh-add-sku");
        if (add) { agregarSku(add.closest(".vh-row")); return; }
        var quitar = event.target.closest(".vh-item-quitar");
        if (quitar) {
            var index = Number(quitar.closest("tr").getAttribute("data-index"));
            estado.partidas.splice(index, 1);
            renderPartidas();
        }
    });

    document.addEventListener("input", function (event) {
        var row = event.target.closest("#vh_partidas tr");
        if (row) { sincronizarPartida(row); }
        if (event.target.id === "vh_descuento" || event.target.id === "vh_pagado") { recalcular(); }
    });
    document.addEventListener("change", function (event) {
        var row = event.target.closest("#vh_partidas tr");
        if (row) { sincronizarPartida(row); }
    });

    document.addEventListener("DOMContentLoaded", function () {
        setToday();
        cargarCatalogos();
        document.getElementById("vh_btn_buscar").addEventListener("click", buscar);
        document.getElementById("vh_buscar").addEventListener("keydown", function (event) {
            if (event.key === "Enter") { event.preventDefault(); buscar(); }
        });
        document.getElementById("vh_btn_rapida").addEventListener("click", agregarRapida);
        document.getElementById("vh_btn_prevalidar").addEventListener("click", prevalidar);
        document.getElementById("vh_btn_guardar").addEventListener("click", guardar);
        document.getElementById("vh_descuento").addEventListener("input", recalcular);
        document.getElementById("vh_pagado").addEventListener("input", recalcular);
    });
})();
