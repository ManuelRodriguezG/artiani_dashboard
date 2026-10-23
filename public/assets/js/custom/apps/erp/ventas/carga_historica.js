"use strict";
(function () {
    var estado = {almacenes: [], metodos: [], acciones: {}, partidas: [], pagos: []};

    /**
     * IA: Codex GPT-5 | Fecha: 2026-10-20
     * Proposito: operar captura historica de ventas con acciones de regularizacion y multiples pagos documentales.
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
            renderPagos();
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
        var almacenSelect = document.getElementById("vh_almacen");
        if (almacenSelect) { almacenSelect.innerHTML = almacenesHtml; }
    }

    function opcionesMetodoPago(selected) {
        return "<option value=\"\">Seleccionar método</option>" + estado.metodos.map(function (item) {
            var value = String(item.id_metodo_pago || "");
            return "<option value=\"" + escapeHtml(value) + "\"" +
                (String(selected || "") === value ? " selected" : "") + ">" +
                escapeHtml(item.metodo_pago || item.nombre || ("Método " + value)) + "</option>";
        }).join("");
    }

    function renderPagos() {
        var body = document.getElementById("vh_pagos");
        if (!body) { return; }
        if (!estado.pagos.length) {
            body.innerHTML = "<tr><td colspan=\"4\" class=\"text-muted fs-7\">Agrega un método si la venta tuvo pago.</td></tr>";
            actualizarTotalPagado();
            return;
        }
        body.innerHTML = estado.pagos.map(function (pago, index) {
            return "<tr data-pago-index=\"" + index + "\">" +
                "<td><select class=\"form-select form-select-sm vh-pago-metodo\">" + opcionesMetodoPago(pago.id_metodo_pago) + "</select></td>" +
                "<td><input class=\"form-control form-control-sm vh-pago-referencia\" type=\"text\" maxlength=\"120\" value=\"" + escapeHtml(pago.referencia || "") + "\" placeholder=\"Folio o referencia\"></td>" +
                "<td><input class=\"form-control form-control-sm text-end vh-pago-monto\" type=\"number\" min=\"0\" step=\"0.01\" value=\"" + escapeHtml(pago.monto || "") + "\" placeholder=\"0.00\"></td>" +
                "<td class=\"text-end\"><button class=\"btn btn-sm btn-light-danger vh-pago-quitar\" type=\"button\" title=\"Quitar método\"><i class=\"bi bi-trash\"></i></button></td>" +
                "</tr>";
        }).join("");
        actualizarTotalPagado();
    }

    function agregarPago() {
        estado.pagos.push({id_metodo_pago: "", referencia: "", monto: ""});
        renderPagos();
    }

    function sincronizarPago(row) {
        var index = Number(row.getAttribute("data-pago-index"));
        if (!estado.pagos[index]) { return; }
        estado.pagos[index].id_metodo_pago = row.querySelector(".vh-pago-metodo").value || "";
        estado.pagos[index].referencia = row.querySelector(".vh-pago-referencia").value || "";
        estado.pagos[index].monto = numero(row.querySelector(".vh-pago-monto").value || 0);
        actualizarTotalPagado();
    }

    function actualizarTotalPagado() {
        var total = estado.pagos.reduce(function (sum, pago) { return sum + numero(pago.monto); }, 0);
        var salida = document.getElementById("vh_pagado");
        if (salida) { salida.textContent = money(total); }
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
        if (!body) { return; }
        if (!estado.partidas.length) {
            body.innerHTML = "<tr><td colspan=\"8\" class=\"text-center text-muted py-10\">Agrega productos para capturar la venta historica.</td></tr>";
            recalcular();
            return;
        }
        body.innerHTML = estado.partidas.map(function (item, index) {
            return "<tr data-index=\"" + index + "\">" +
                "<td><input class=\"form-check-input vh-item-conjunto\" type=\"checkbox\" title=\"Incluir en precio conjunto\"" + (item.aplica_conjunto ? " checked" : "") + "></td>" +
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
        estado.partidas[index].aplica_conjunto = row.querySelector(".vh-item-conjunto").checked ? 1 : 0;
        estado.partidas[index].accion_regularizacion = row.querySelector(".vh-item-accion").value || "";
        row.querySelector(".vh-item-importe").textContent = money(estado.partidas[index].cantidad * estado.partidas[index].precio_unitario);
        recalcular();
    }

    function recalcular() {
        var subtotal = estado.partidas.reduce(function (sum, item) {
            return sum + numero(item.cantidad) * numero(item.precio_unitario);
        }, 0);
        var descuento = numero(document.getElementById("vh_descuento").value || 0);
        var conjuntoSubtotal = estado.partidas.reduce(function (sum, item) {
            return sum + (item.aplica_conjunto ? numero(item.cantidad) * numero(item.precio_unitario) : 0);
        }, 0);
        var conjuntoTotal = numero(document.getElementById("vh_paquete_total").value || 0);
        var conjuntoPorcentaje = numero(document.getElementById("vh_paquete_porcentaje").value || 0);
        var conjuntoDescuento = conjuntoTotal > 0 ? Math.max(0, conjuntoSubtotal - conjuntoTotal) : conjuntoSubtotal * (conjuntoPorcentaje / 100);
        var total = Math.max(0, subtotal - descuento - conjuntoDescuento);
        var paqueteSubtotalNodo = document.getElementById("vh_paquete_subtotal");
        var paqueteDescuentoNodo = document.getElementById("vh_paquete_descuento");
        var paqueteReglaNodo = document.getElementById("vh_paquete_regla");
        if (paqueteSubtotalNodo) { paqueteSubtotalNodo.textContent = money(conjuntoSubtotal); }
        if (paqueteDescuentoNodo) { paqueteDescuentoNodo.textContent = money(conjuntoDescuento); }
        if (paqueteReglaNodo) {
            var errores = [];
            if (descuento < 0) { errores.push("el descuento general no puede ser negativo"); }
            if (conjuntoTotal < 0) { errores.push("el total conjunto no puede ser negativo"); }
            if (conjuntoPorcentaje < 0 || conjuntoPorcentaje > 100) { errores.push("el porcentaje debe estar entre 0 y 100"); }
            if (conjuntoTotal > conjuntoSubtotal && conjuntoTotal > 0) { errores.push("el total conjunto supera el subtotal seleccionado"); }
            paqueteReglaNodo.textContent = errores.length
                ? "Revisa: " + errores.join("; ") + "."
                : conjuntoTotal > 0 && conjuntoPorcentaje > 0
                ? "Se usara el total fijo; el porcentaje capturado se ignorara."
                : "Captura total fijo o porcentaje. El descuento del conjunto se resta una sola vez.";
        }
        document.getElementById("vh_subtotal").textContent = money(subtotal);
        document.getElementById("vh_total").textContent = money(total);
        actualizarTotalPagado();
    }

    function payload() {
        return {
            fecha_venta_real: document.getElementById("vh_fecha").value,
            id_almacen: document.getElementById("vh_almacen").value || "0",
            cliente_nombre_publico: document.getElementById("vh_cliente").value || "Cliente historico",
            id_metodo_pago: estado.pagos.length ? (estado.pagos[0].id_metodo_pago || "0") : "0",
            pagos: JSON.stringify(estado.pagos),
            observaciones: document.getElementById("vh_observaciones").value || "Carga historica de ventas",
            descuento_total: document.getElementById("vh_descuento").value || "0",
            indices_precio_conjunto: JSON.stringify(estado.partidas.reduce(function (indices, item, index) {
                if (item.aplica_conjunto) { indices.push(index); }
                return indices;
            }, [])),
            precio_conjunto_total: document.getElementById("vh_paquete_total").value || "0",
            precio_conjunto_porcentaje: document.getElementById("vh_paquete_porcentaje").value || "0",
            pagado_total: estado.pagos.reduce(function (sum, pago) { return sum + numero(pago.monto); }, 0).toFixed(2),
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
        var totales = data.totales || {};
        if (Object.keys(totales).length) {
            html += "<div class=\"row g-2 mt-2 fs-7\">" +
                "<div class=\"col-6\">Subtotal: <strong>" + money(totales.subtotal || 0) + "</strong></div>" +
                "<div class=\"col-6\">Descuento general: <strong>" + money(totales.descuento_general || 0) + "</strong></div>" +
                "<div class=\"col-6\">Descuento conjunto: <strong>" + money(totales.descuento_conjunto || 0) + "</strong></div>" +
                "<div class=\"col-6\">Total: <strong>" + money(totales.total || 0) + "</strong></div>" +
                "<div class=\"col-6\">Pagado: <strong>" + money(totales.pagado_total || 0) + "</strong></div>" +
                "<div class=\"col-6\">Saldo: <strong>" + money(totales.saldo_total || 0) + "</strong></div>" +
                "</div>";
        }
        var partidasCalculadas = data.partidas || [];
        if (partidasCalculadas.length) {
            html += "<div class=\"table-responsive mt-2\"><table class=\"table table-sm align-middle mb-0 fs-8\"><thead><tr><th>Partida</th><th class=\"text-end\">Original</th><th class=\"text-end\">Desc. conjunto</th><th class=\"text-end\">Desc. general</th><th class=\"text-end\">Final</th></tr></thead><tbody>" +
                partidasCalculadas.map(function (partida) {
                    return "<tr><td>" + escapeHtml(partida.sku || partida.descripcion || ("Renglon " + (partida.renglon || "-"))) + "</td>" +
                        "<td class=\"text-end\">" + money(partida.importe_historico || 0) + "</td>" +
                        "<td class=\"text-end\">" + money(partida.descuento_conjunto || 0) + "</td>" +
                        "<td class=\"text-end\">" + money(partida.descuento_general_asignado || 0) + "</td>" +
                        "<td class=\"text-end fw-bold\">" + money(partida.total_normalizado || 0) + "</td></tr>";
                }).join("") + "</tbody></table></div>";
        }
        var avisos = data.avisos || [];
        if (avisos.length) {
            html += "<div class=\"mt-2\"><strong>Avisos</strong><ul class=\"mb-0\">" + avisos.map(function (aviso) { return "<li>" + escapeHtml(aviso) + "</li>"; }).join("") + "</ul></div>";
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
        var contenedor = document.getElementById("vh_alerta");
        if (contenedor) { contenedor.innerHTML = "<div class=\"alert alert-" + tipo + " py-3 mb-0\">" + escapeHtml(mensaje) + "</div>"; }
    }

    function resultado(tipo, mensaje, html) {
        var contenedor = document.getElementById("vh_resultado");
        if (!contenedor) { return; }
        contenedor.className = "alert alert-light-" + (tipo === "danger" ? "danger" : tipo) + " py-3 mb-0";
        contenedor.innerHTML = html ? mensaje : escapeHtml(mensaje);
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
            return;
        }
        if (event.target.closest("#vh_pago_agregar")) { agregarPago(); return; }
        var quitarPago = event.target.closest(".vh-pago-quitar");
        if (quitarPago) {
            var pagoRow = quitarPago.closest("tr");
            estado.pagos.splice(Number(pagoRow.getAttribute("data-pago-index")), 1);
            renderPagos();
        }
    });

    document.addEventListener("input", function (event) {
        var row = event.target.closest("#vh_partidas tr");
        if (row) { sincronizarPartida(row); }
        var pagoRow = event.target.closest("#vh_pagos tr");
        if (pagoRow) { sincronizarPago(pagoRow); }
        if (event.target.id === "vh_descuento") { recalcular(); }
        if (event.target.id === "vh_paquete_total" || event.target.id === "vh_paquete_porcentaje") { recalcular(); }
    });
    document.addEventListener("change", function (event) {
        var row = event.target.closest("#vh_partidas tr");
        if (row) { sincronizarPartida(row); }
        var pagoRow = event.target.closest("#vh_pagos tr");
        if (pagoRow) { sincronizarPago(pagoRow); }
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
        document.getElementById("vh_paquete_total").addEventListener("input", recalcular);
        document.getElementById("vh_paquete_porcentaje").addEventListener("input", recalcular);
        document.getElementById("vh_paquete_aplicar").addEventListener("click", prevalidar);
    });
})();
