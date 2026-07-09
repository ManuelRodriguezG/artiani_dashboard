"use strict";
(function () {
    var estado = {};
    var temporizador = null;
    var temporizadorProducto = null;
    var productosReserva = [];
    var partidasReserva = [];
    var placeholderImagen = "data:image/svg+xml;charset=UTF-8,%3Csvg%20xmlns='http://www.w3.org/2000/svg'%20viewBox='0%200%20400%20300'%3E%3Crect%20width='400'%20height='300'%20fill='%23f1f3f6'/%3E%3Cpath%20d='M80%20225h240l-70-85-55%2065-35-42z'%20fill='%23c8ced8'/%3E%3Ccircle%20cx='135'%20cy='105'%20r='28'%20fill='%23d7dce5'/%3E%3C/svg%3E";

    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-03
     * Proposito: operar modulo Pedidos/Apartados con prevalidacion y acciones reales confirmadas.
     * Impacto: mantiene simulacion previa y acciones transaccionales controladas por backend.
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

    function monto(value) {
        var parsed = Number(String(value || "0").replace(",", "."));
        return Number.isFinite(parsed) ? parsed : 0;
    }

    function numero(value) {
        return Number(monto(value).toFixed(4)).toString();
    }

    function textoLegible(value) {
        return String(value == null ? "" : value)
            .replace(/\u00c3\u00a1/g, "\u00e1").replace(/\u00c3\u00a9/g, "\u00e9").replace(/\u00c3\u00ad/g, "\u00ed")
            .replace(/\u00c3\u00b3/g, "\u00f3").replace(/\u00c3\u00ba/g, "\u00fa").replace(/\u00c3\u00b1/g, "\u00f1")
            .replace(/\u00c2/g, "").replace(/\ufffd/g, "");
    }

    function imagen(item) {
        var url = item.url_imagen || item.imagen || item.imagen_principal || item.portada || "";
        url = String(url || "").trim();
        if (!url) { return placeholderImagen; }
        if (/^(https?:)?\/\//i.test(url) || url.indexOf("data:") === 0 || url.charAt(0) === "/") {
            return url;
        }
        return "/" + url.replace(/^\/+/, "");
    }

    function confirmar(frase, accion) {
        var escrito = window.prompt("Escribe " + frase + " para " + accion);
        return escrito === frase;
    }

    function renderResultadoOperacion(targetId, response, textoSeguro) {
        var depurar = response.depurar || {};
        var tipo = response.error ? "alert-danger" : (response.tipo === "warning" ? "alert-warning" : "alert-success");
        var html = "<div class=\"alert " + tipo + " py-3\">";
        html += "<div class=\"fw-bold\">" + escapeHtml(response.mensaje || "Operacion procesada") + "</div>";
        if (depurar.folio) {
            html += "<div class=\"fs-8\">Folio: <span class=\"fw-semibold\">" + escapeHtml(depurar.folio) + "</span></div>";
        }
        if (depurar.estatus) {
            html += "<div class=\"fs-8\">Estatus: <span class=\"fw-semibold\">" + escapeHtml(depurar.estatus) + "</span></div>";
        }
        if (depurar.saldo_total != null) {
            html += "<div class=\"fs-8\">Saldo: <span class=\"fw-semibold\">" + dinero(depurar.saldo_total) + "</span></div>";
        }
        if (textoSeguro) {
            html += "<div class=\"fs-8 text-muted mt-2\">" + escapeHtml(textoSeguro) + "</div>";
        }
        html += "</div>";
        document.getElementById(targetId).innerHTML = html;
    }

    function cargarConfiguracion() {
        request("/ventas/pos_configuracion_resumen_erp").then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            estado = response.depurar || {};
            llenarSelectores();
            renderContextoPos();
            inicializarFechaReserva();
            actualizarResumenReserva();
            cargarListado();
        }).catch(mostrarError);
    }

    function llenarSelectores() {
        var almacenes = estado.almacenes || [];
        var cajas = estado.cajas || [];
        var turnos = estado.turnos_abiertos || [];
        var metodos = estado.metodos_pago || [];
        var htmlAlmacenes = almacenes.map(function (item) {
            return "<option value=\"" + escapeHtml(item.id_almacen) + "\">" + escapeHtml(item.codigo_almacen || item.almacen || item.nombre_comercial || item.id_almacen) + "</option>";
        }).join("") || "<option value=\"\">Sin almacenes</option>";
        var htmlCajas = cajas.map(function (item) {
            return "<option value=\"" + escapeHtml(item.id_caja) + "\" data-almacen=\"" + escapeHtml(item.id_almacen) + "\">" + escapeHtml(item.codigo || item.nombre || item.id_caja) + "</option>";
        }).join("") || "<option value=\"\">Sin cajas</option>";
        var htmlTurnos = turnos.map(function (item) {
            return "<option value=\"" + escapeHtml(item.id_turno_caja) + "\" data-caja=\"" + escapeHtml(item.id_caja) + "\" data-almacen=\"" + escapeHtml(item.id_almacen) + "\">" + escapeHtml(item.folio || item.id_turno_caja) + "</option>";
        }).join("") || "<option value=\"\">Sin turnos abiertos</option>";
        var htmlMetodos = metodos.map(function (item) {
            return "<option value=\"" + escapeHtml(item.id_metodo_pago) + "\">" + escapeHtml(item.metodo_pago || item.nombre || item.id_metodo_pago) + "</option>";
        }).join("") || "<option value=\"1\">Efectivo</option>";
        document.getElementById("ped_abono_almacen").innerHTML = htmlAlmacenes;
        document.getElementById("ped_abono_caja").innerHTML = htmlCajas;
        document.getElementById("ped_abono_turno").innerHTML = htmlTurnos;
        document.getElementById("ped_abono_metodo").innerHTML = htmlMetodos;
        document.getElementById("ped_reserva_almacen").innerHTML = htmlAlmacenes;
        document.getElementById("ped_reserva_caja").innerHTML = htmlCajas;
        document.getElementById("ped_reserva_turno").innerHTML = htmlTurnos;
        document.getElementById("ped_reserva_metodo").innerHTML = htmlMetodos;
        sincronizarDesdeTurnoPrefix("ped_abono");
        sincronizarDesdeTurnoPrefix("ped_reserva");
    }

    function textoSeleccion(select) {
        if (!select || !select.selectedOptions || !select.selectedOptions[0]) { return "-"; }
        return select.selectedOptions[0].textContent || "-";
    }

    function renderContextoPos() {
        var asignacion = estado.asignacion || {};
        var turno = estado.turno_abierto || {};
        var almacen = asignacion.codigo_almacen || asignacion.almacen || asignacion.nombre_comercial || asignacion.id_almacen || "-";
        var caja = asignacion.codigo_caja || asignacion.caja || asignacion.nombre_caja || asignacion.id_caja || "-";
        document.getElementById("ped_ctx_almacen").textContent = almacen;
        document.getElementById("ped_ctx_caja").textContent = caja;
        document.getElementById("ped_ctx_turno").textContent = turno.folio || (turno.id_turno_caja ? "#" + turno.id_turno_caja : "Sin turno abierto");
        document.getElementById("ped_ctx_modo").textContent = turno.id_turno_caja ? "Operativo con turno" : "Requiere abrir turno";
    }

    function actualizarResumenReserva() {
        var anticipo = monto(document.getElementById("ped_reserva_anticipo").value);
        var total = partidasReserva.reduce(function (acumulado, item) {
            return acumulado + (monto(item.cantidad) * monto(item.precio_unitario));
        }, 0);
        var cantidadTotal = partidasReserva.reduce(function (acumulado, item) {
            return acumulado + monto(item.cantidad);
        }, 0);
        var partidaEnCaptura = partidaActual();
        var resumen = document.getElementById("ped_partidas_resumen");
        document.getElementById("ped_reserva_total").textContent = dinero(total);
        document.getElementById("ped_reserva_pagado").textContent = dinero(anticipo);
        document.getElementById("ped_reserva_saldo").textContent = dinero(Math.max(0, total - anticipo));
        if (resumen) {
            var estadoCaptura = partidaEnCaptura.id_sku > 0
                ? "Hay una partida capturada pendiente de agregar."
                : "Agrega la partida para incluirla en el pedido.";
            resumen.innerHTML = "<span>" + partidasReserva.length + " partida(s) agregadas | Cantidad total " + numero(cantidadTotal) + "</span><span>" + escapeHtml(estadoCaptura) + "</span>";
        }
    }

    function inicializarFechaReserva() {
        var input = document.getElementById("ped_reserva_fecha");
        if (!input || input.value) { return; }
        var fecha = new Date();
        fecha.setDate(fecha.getDate() + 7);
        input.value = fecha.getFullYear() + "-" + String(fecha.getMonth() + 1).padStart(2, "0") + "-" + String(fecha.getDate()).padStart(2, "0");
    }

    function selectores(prefix) {
        return {
            almacen: document.getElementById(prefix + "_almacen"),
            caja: document.getElementById(prefix + "_caja"),
            turno: document.getElementById(prefix + "_turno")
        };
    }

    function seleccionarPrimeraCajaCompatible(ctx) {
        var cajaActual = ctx.caja.selectedOptions[0];
        if (cajaActual && String(cajaActual.getAttribute("data-almacen") || "") === String(ctx.almacen.value || "")) {
            return;
        }
        Array.prototype.some.call(ctx.caja.options, function (option) {
            if (String(option.getAttribute("data-almacen") || "") === String(ctx.almacen.value || "")) {
                ctx.caja.value = option.value;
                return true;
            }
            return false;
        });
    }

    function seleccionarPrimerTurnoCompatible(ctx) {
        var turnoActual = ctx.turno.selectedOptions[0];
        if (turnoActual &&
            String(turnoActual.getAttribute("data-almacen") || "") === String(ctx.almacen.value || "") &&
            String(turnoActual.getAttribute("data-caja") || "") === String(ctx.caja.value || "")) {
            return;
        }
        Array.prototype.some.call(ctx.turno.options, function (option) {
            if (String(option.getAttribute("data-almacen") || "") === String(ctx.almacen.value || "") &&
                String(option.getAttribute("data-caja") || "") === String(ctx.caja.value || "")) {
                ctx.turno.value = option.value;
                return true;
            }
            return false;
        });
    }

    function sincronizarDesdeTurnoPrefix(prefix) {
        var ctx = selectores(prefix);
        var turno = ctx.turno.selectedOptions[0];
        if (!turno) { return; }
        if (turno.getAttribute("data-almacen")) {
            ctx.almacen.value = turno.getAttribute("data-almacen");
        }
        if (turno.getAttribute("data-caja")) {
            ctx.caja.value = turno.getAttribute("data-caja");
        }
    }

    function sincronizarPorAlmacen(prefix) {
        var ctx = selectores(prefix);
        seleccionarPrimeraCajaCompatible(ctx);
        seleccionarPrimerTurnoCompatible(ctx);
    }

    function sincronizarPorCaja(prefix) {
        var ctx = selectores(prefix);
        var caja = ctx.caja.selectedOptions[0];
        if (caja && caja.getAttribute("data-almacen")) {
            ctx.almacen.value = caja.getAttribute("data-almacen");
        }
        seleccionarPrimerTurnoCompatible(ctx);
    }

    function cargarListado() {
        var tipo = document.getElementById("ped_tipo").value;
        var params = new URLSearchParams({
            tipo: tipo,
            estatus: document.getElementById("ped_estatus").value,
            q: document.getElementById("ped_q").value.trim(),
            limite: "80"
        });
        if (!tipo) {
            params.set("tipo", "");
        }
        request("/ventas/ventas_listar_erp?" + params.toString()).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            var ventas = ((response.depurar || {}).ventas || []).filter(function (item) {
                return item.tipo_documento === "pedido" || item.tipo_documento === "apartado";
            });
            renderListado(ventas);
            renderAlerta((response.depurar || {}).schema_pendiente);
        }).catch(mostrarError);
    }

    function buscarProductoReserva() {
        var termino = document.getElementById("ped_producto_q").value.trim();
        var almacen = document.getElementById("ped_reserva_almacen").value;
        clearTimeout(temporizadorProducto);
        if (termino.length < 2) {
            productosReserva = [];
            renderProductosReserva([]);
            document.getElementById("ped_producto_estado").textContent = "Escribe al menos 2 caracteres o escanea un codigo.";
            return;
        }
        temporizadorProducto = setTimeout(function () {
            var params = new URLSearchParams({q: termino, id_almacen: almacen || "", limite: "12"});
            document.getElementById("ped_producto_estado").textContent = "Buscando...";
            request("/ventas/pos_buscar_skus_erp?" + params.toString()).then(function (response) {
                if (response.error) { throw new Error(response.mensaje); }
                productosReserva = response.depurar || [];
                renderProductosReserva(productosReserva);
            }).catch(function (error) {
                document.getElementById("ped_producto_estado").textContent = error.message || String(error);
            });
        }, 180);
    }

    function renderProductosReserva(items) {
        var contenedor = document.getElementById("ped_producto_resultados");
        contenedor.classList.toggle("d-none", !items.length);
        document.getElementById("ped_producto_estado").textContent = items.length ? (items.length + " resultado(s)") : "Sin resultados visibles.";
        contenedor.innerHTML = items.map(function (item, index) {
            var disponible = monto(item.existencia_disponible || 0);
            var badges = "";
            if (Number(item.permite_venta_fraccionaria || 0) === 1) { badges += "<span class=\"badge badge-light-info\">Granel</span>"; }
            if (Number(item.unidades_cerradas || 0) > 0) { badges += "<span class=\"badge badge-light-success\">Unidad cerrada</span>"; }
            if (disponible <= 0) { badges += "<span class=\"badge badge-light-danger\">Sin stock</span>"; }
            return "<button class=\"ped-product-row w-100 bg-white border-0 text-start p-3 d-flex gap-3 align-items-center\" type=\"button\" data-ped-producto=\"" + index + "\">" +
                "<img class=\"ped-product-img\" src=\"" + escapeHtml(imagen(item)) + "\" alt=\"\">" +
                "<span class=\"flex-grow-1 min-w-0\"><span class=\"d-block fw-semibold text-truncate\">" + escapeHtml(textoLegible(item.nombre_sku || item.producto || "")) + "</span>" +
                "<span class=\"d-block text-muted fs-8\">" + escapeHtml(item.sku || "") + " | Disp. " + numero(disponible) + " " + escapeHtml(item.unidad_venta_label || "") + "</span>" +
                "<span class=\"d-flex flex-wrap gap-1 mt-1\">" + badges + "</span></span>" +
                "<span class=\"fw-bold\">" + dinero(item.precio || 0) + "</span>" +
                "</button>";
        }).join("");
    }

    function seleccionarProductoReserva(index) {
        var item = productosReserva[Number(index)];
        if (!item) { return; }
        document.getElementById("ped_reserva_sku").value = item.id_sku || "";
        document.getElementById("ped_reserva_precio").value = monto(item.precio || 0) > 0 ? monto(item.precio || 0).toFixed(2) : "";
        document.getElementById("ped_producto_q").value = (item.sku || "") + " - " + textoLegible(item.nombre_sku || item.producto || "");
        document.getElementById("ped_producto_resultados").classList.add("d-none");
        document.getElementById("ped_producto_estado").textContent = "Producto seleccionado. Captura cantidad y agrega la partida.";
        actualizarResumenReserva();
    }

    function limpiarProductoReserva() {
        productosReserva = [];
        document.getElementById("ped_producto_q").value = "";
        document.getElementById("ped_reserva_sku").value = "";
        document.getElementById("ped_reserva_precio").value = "";
        renderProductosReserva([]);
        document.getElementById("ped_producto_estado").textContent = "Busca un producto para llenar SKU y precio.";
        actualizarResumenReserva();
    }

    function partidaActual() {
        return {
            id_sku: Number(document.getElementById("ped_reserva_sku").value || 0),
            sku: "",
            descripcion: document.getElementById("ped_producto_q").value.trim(),
            imagen: placeholderImagen,
            cantidad: monto(document.getElementById("ped_reserva_cantidad").value),
            precio_unitario: monto(document.getElementById("ped_reserva_precio").value),
            modo_salida: "existencia_agregada"
        };
    }

    function normalizarPartida(item) {
        return {
            id_sku: Number(item.id_sku || 0),
            sku: item.sku || "",
            descripcion: textoLegible(item.descripcion || item.nombre_sku || item.producto || ""),
            imagen: item.imagen || imagen(item),
            cantidad: monto(item.cantidad || 1),
            precio_unitario: monto(item.precio_unitario != null ? item.precio_unitario : item.precio),
            modo_salida: item.modo_salida || "existencia_agregada"
        };
    }

    function agregarPartidaReserva() {
        var seleccionada = productosReserva.find(function (item) {
            return String(item.id_sku || "") === String(document.getElementById("ped_reserva_sku").value || "");
        });
        var actual = partidaActual();
        var base = seleccionada
            ? Object.assign({}, seleccionada, {
                cantidad: actual.cantidad,
                precio_unitario: actual.precio_unitario > 0 ? actual.precio_unitario : seleccionada.precio,
                modo_salida: actual.modo_salida
            })
            : actual;
        var partida = normalizarPartida(base);
        if (partida.id_sku <= 0) {
            document.getElementById("ped_producto_estado").textContent = "Selecciona o captura un SKU valido.";
            return;
        }
        if (partida.cantidad <= 0 || partida.precio_unitario < 0) {
            document.getElementById("ped_producto_estado").textContent = "Captura cantidad mayor a cero y precio valido.";
            return;
        }
        var existente = partidasReserva.find(function (item) {
            return String(item.id_sku) === String(partida.id_sku) && item.modo_salida === partida.modo_salida;
        });
        if (existente) {
            existente.cantidad = monto(existente.cantidad) + monto(partida.cantidad);
            existente.precio_unitario = partida.precio_unitario;
        } else {
            partidasReserva.push(partida);
        }
        document.getElementById("ped_reserva_sku").value = "";
        document.getElementById("ped_reserva_precio").value = "";
        document.getElementById("ped_reserva_cantidad").value = "1";
        document.getElementById("ped_producto_q").value = "";
        document.getElementById("ped_producto_resultados").classList.add("d-none");
        document.getElementById("ped_producto_estado").textContent = "Partida agregada. Puedes buscar otro producto o simular.";
        renderPartidasReserva();
    }

    function renderPartidasReserva() {
        var body = document.getElementById("ped_partidas_body");
        var empty = document.getElementById("ped_partidas_empty");
        empty.classList.toggle("d-none", partidasReserva.length > 0);
        body.innerHTML = partidasReserva.map(function (item, index) {
            var importe = monto(item.cantidad) * monto(item.precio_unitario);
            return "<tr>" +
                "<td><div class=\"d-flex align-items-center gap-2\"><img class=\"ped-line-img\" src=\"" + escapeHtml(item.imagen || placeholderImagen) + "\" alt=\"\"><div class=\"min-w-0\"><div class=\"fw-semibold text-truncate\">" + escapeHtml(item.descripcion || item.sku || item.id_sku) + "</div><div class=\"text-muted fs-8\">SKU " + escapeHtml(item.sku || item.id_sku) + "</div></div></div></td>" +
                "<td class=\"text-end\"><input class=\"form-control form-control-sm form-control-solid text-end\" inputmode=\"decimal\" value=\"" + escapeHtml(numero(item.cantidad)) + "\" data-ped-linea-cantidad=\"" + index + "\"></td>" +
                "<td class=\"text-end\"><input class=\"form-control form-control-sm form-control-solid text-end\" inputmode=\"decimal\" value=\"" + escapeHtml(Number(monto(item.precio_unitario).toFixed(2)).toString()) + "\" data-ped-linea-precio=\"" + index + "\"></td>" +
                "<td class=\"text-end fw-bold\">" + dinero(importe) + "</td>" +
                "<td class=\"text-end\"><button class=\"btn btn-sm btn-icon btn-light-danger\" type=\"button\" data-ped-linea-quitar=\"" + index + "\"><i class=\"bi bi-x-lg\"></i></button></td>" +
                "</tr>";
        }).join("");
        actualizarResumenReserva();
    }

    function itemsReservaPayload() {
        return partidasReserva.map(function (item) {
            return {
                id_sku: Number(item.id_sku || 0),
                cantidad: monto(item.cantidad),
                precio_unitario: monto(item.precio_unitario),
                modo_salida: item.modo_salida || "existencia_agregada"
            };
        });
    }

    function validarPartidasReserva(items) {
        var errores = [];
        if (!items.length) {
            errores.push("Agrega al menos una partida a la tabla antes de simular.");
        }
        items.forEach(function (item, index) {
            var linea = "Partida " + (index + 1) + ": ";
            if (Number(item.id_sku || 0) <= 0) {
                errores.push(linea + "SKU invalido.");
            }
            if (monto(item.cantidad) <= 0) {
                errores.push(linea + "cantidad debe ser mayor a cero.");
            }
            if (monto(item.precio_unitario) < 0) {
                errores.push(linea + "precio no puede ser negativo.");
            }
        });
        return errores;
    }

    function mostrarErroresReserva(errores) {
        document.getElementById("ped_reserva_resultado").innerHTML = "<div class=\"alert alert-warning py-3\"><div class=\"fw-bold\">Revisa el pedido antes de continuar</div><ul class=\"mb-0 mt-2 ps-4\">" +
            errores.map(function (item) { return "<li>" + escapeHtml(item) + "</li>"; }).join("") +
            "</ul></div>";
    }

    function renderListado(items) {
        document.getElementById("ped_tabla").innerHTML = items.map(function (item) {
            var estatus = String(item.estatus || "");
            var saldo = monto(item.saldo_total || 0);
            var acciones = "<a class=\"btn btn-sm btn-icon btn-light\" href=\"/ventas/venta_detalle?folio=" + encodeURIComponent(item.folio || "") + "\" title=\"Detalle\"><i class=\"bi bi-eye\"></i></a>";
            if (saldo > 0 && ["reservado", "pendiente_pago"].indexOf(estatus) !== -1) {
                acciones += "<button class=\"btn btn-sm btn-icon btn-light-success\" type=\"button\" data-ped-abono=\"" + escapeHtml(item.folio || "") + "\" data-ped-saldo=\"" + escapeHtml(saldo) + "\" title=\"Abonar\"><i class=\"bi bi-cash-coin\"></i></button>";
            }
            if (saldo <= 0 && estatus === "pagado") {
                acciones += "<button class=\"btn btn-sm btn-icon btn-light-primary\" type=\"button\" data-ped-entregar=\"" + escapeHtml(item.folio || "") + "\" title=\"Entregar\"><i class=\"bi bi-box-seam\"></i></button>";
            }
            if (["reservado", "pendiente_pago", "pagado"].indexOf(estatus) !== -1) {
                acciones += "<button class=\"btn btn-sm btn-icon btn-light-danger\" type=\"button\" data-ped-cancelar=\"" + escapeHtml(item.folio || "") + "\" title=\"Cancelar\"><i class=\"bi bi-x-circle\"></i></button>";
            }
            return "<tr><td><div class=\"fw-bold\">" + escapeHtml(item.folio || "-") + "</div><div class=\"text-muted fs-8\">#" + escapeHtml(item.id_venta || "-") + "</div></td>" +
                "<td>" + escapeHtml(item.cliente_nombre_publico || "Publico general") + "</td><td><span class=\"badge badge-light-primary\">" + escapeHtml(item.tipo_documento || "-") + "</span></td>" +
                "<td class=\"fw-bold\">" + dinero(item.total || 0) + "</td><td>" + dinero(item.pagado_total || 0) + "</td><td>" + dinero(item.saldo_total || 0) + "</td>" +
                "<td><span class=\"badge badge-light\">" + escapeHtml(item.estatus || "-") + "</span></td>" +
                "<td class=\"text-end\"><div class=\"d-inline-flex gap-2\">" +
                    acciones +
                "</div></td></tr>";
        }).join("") || "<tr><td colspan=\"8\"><div class=\"ped-empty d-flex align-items-center justify-content-center text-center text-muted\"><div><i class=\"bi bi-bookmark-check fs-1 d-block mb-3\"></i><div class=\"fw-semibold\">Sin pedidos o apartados en este filtro</div></div></div></td></tr>";
    }

    function payloadReserva() {
        return {
            id_almacen: document.getElementById("ped_reserva_almacen").value,
            id_caja: document.getElementById("ped_reserva_caja").value,
            id_turno_caja: document.getElementById("ped_reserva_turno").value,
            canal: "pedido_tienda",
            tipo_documento: document.getElementById("ped_reserva_tipo").value,
            cliente_nombre_publico: document.getElementById("ped_reserva_cliente").value.trim(),
            identificador_cliente: document.getElementById("ped_reserva_identificador").value.trim(),
            fecha_entrega_compromiso: document.getElementById("ped_reserva_fecha").value,
            items: JSON.stringify(itemsReservaPayload()),
            pagos: JSON.stringify([{
                id_metodo_pago: document.getElementById("ped_reserva_metodo").value,
                monto: monto(document.getElementById("ped_reserva_anticipo").value),
                referencia: document.getElementById("ped_reserva_referencia").value.trim()
            }])
        };
    }

    function payloadAbono() {
        return {
            id_almacen: document.getElementById("ped_abono_almacen").value,
            id_caja: document.getElementById("ped_abono_caja").value,
            id_turno_caja: document.getElementById("ped_abono_turno").value,
            folio: document.getElementById("ped_abono_folio").value.trim(),
            monto_abono: monto(document.getElementById("ped_abono_monto").value),
            id_metodo_pago: document.getElementById("ped_abono_metodo").value,
            referencia: document.getElementById("ped_abono_referencia").value.trim()
        };
    }

    function simularAbono() {
        request("/ventas/apartado_abono_dryrun_erp", payloadAbono()).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            var data = response.depurar || {};
            var bloqueos = data.bloqueos || [];
            var abono = data.abono || {};
            var html = "<div class=\"alert " + (bloqueos.length ? "alert-warning" : "alert-success") + " py-3\"><div class=\"fw-bold\">" + escapeHtml(response.mensaje || "Abono simulado") + "</div><div class=\"fs-8 text-muted\">No se registro pago ni movimiento de caja.</div>";
            html += "<div class=\"fs-8\">Folio: " + escapeHtml(data.folio || "") + " | Metodo: " + escapeHtml(abono.metodo_pago || "-") + " | Monto: " + dinero(abono.monto || 0) + "</div>";
            if (bloqueos.length) {
                html += "<ul class=\"mb-0 mt-2 ps-4\">" + bloqueos.map(function (item) { return "<li>" + escapeHtml(item) + "</li>"; }).join("") + "</ul>";
            } else {
                html += "<button class=\"btn btn-sm btn-success mt-3\" type=\"button\" id=\"ped_abono_real\"><i class=\"bi bi-check2-circle\"></i> Registrar abono real</button>";
            }
            html += "</div>";
            document.getElementById("ped_abono_resultado").innerHTML = html;
        }).catch(function (error) {
            document.getElementById("ped_abono_resultado").innerHTML = "<div class=\"alert alert-warning py-3\">" + escapeHtml(error.message || String(error)) + "</div>";
        });
    }

    function simularReserva() {
        var errores = validarPartidasReserva(itemsReservaPayload());
        if (errores.length) {
            mostrarErroresReserva(errores);
            return;
        }
        request("/ventas/pedido_reserva_dryrun_erp", payloadReserva()).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            document.getElementById("ped_reserva_resultado").innerHTML = renderReservaResultado(response);
        }).catch(function (error) {
            document.getElementById("ped_reserva_resultado").innerHTML = "<div class=\"alert alert-warning py-3\">" + escapeHtml(error.message || String(error)) + "</div>";
        });
    }

    function renderReservaResultado(response) {
        var data = response.depurar || {};
        var bloqueos = data.bloqueos || [];
        var avisos = data.avisos || [];
        var politica = data.politica_apartado || {};
        var propuesta = data.propuesta_reserva || {};
        var reservas = propuesta.reservas || [];
        var html = "<div class=\"alert " + (bloqueos.length ? "alert-warning" : "alert-success") + " py-3 mb-3\">";
        html += "<div class=\"fw-bold\">" + escapeHtml(response.mensaje || "Reserva simulada") + "</div>";
        html += "<div class=\"fs-8 text-muted\">No se creo pedido, no se aparto inventario y no se movio caja.</div>";
        html += "<div class=\"row g-2 mt-2 fs-8\">";
        html += "<div class=\"col-6\">Politica: <span class=\"fw-semibold\">" + escapeHtml(politica.codigo || "-") + "</span></div>";
        html += "<div class=\"col-6\">Anticipo minimo: <span class=\"fw-semibold\">" + dinero(data.anticipo_minimo || 0) + "</span></div>";
        html += "<div class=\"col-6\">Pagado simulado: <span class=\"fw-semibold\">" + dinero(data.pagado_total || 0) + "</span></div>";
        html += "<div class=\"col-6\">Saldo: <span class=\"fw-semibold\">" + dinero(data.saldo_estimado || 0) + "</span></div>";
        html += "<div class=\"col-12\">Fecha maxima: <span class=\"fw-semibold\">" + escapeHtml(data.fecha_maxima_compromiso || "-") + "</span></div>";
        html += "</div>";
        if (bloqueos.length) {
            html += "<div class=\"fw-semibold mt-3 fs-8\">Bloqueos</div><ul class=\"mb-0 ps-4\">" + bloqueos.map(function (item) { return "<li>" + escapeHtml(item) + "</li>"; }).join("") + "</ul>";
        }
        if (avisos.length) {
            html += "<div class=\"fw-semibold mt-3 fs-8\">Avisos</div><ul class=\"mb-0 ps-4\">" + avisos.map(function (item) { return "<li>" + escapeHtml(item) + "</li>"; }).join("") + "</ul>";
        }
        html += "</div>";
        html += "<div class=\"border rounded p-3\"><div class=\"fw-bold fs-7 mb-2\">Reservas que se crearian</div>";
        if (!reservas.length) {
            html += "<div class=\"text-muted fs-8\">Sin asignaciones de reserva. Normalmente esto indica falta de stock disponible o partida bloqueada.</div>";
        } else {
            html += "<div class=\"table-responsive\"><table class=\"table table-sm mb-0\"><thead><tr class=\"text-muted fs-8\"><th>SKU</th><th>Existencia</th><th>Cantidad</th><th>Modo</th></tr></thead><tbody>";
            html += reservas.map(function (item) {
                return "<tr><td>" + escapeHtml(item.sku || item.id_sku) + "</td><td>" + escapeHtml(item.id_existencia_inventario || "-") + "</td><td>" + escapeHtml(item.cantidad_base || 0) + "</td><td>" + escapeHtml(item.modo_reserva || "-") + "</td></tr>";
            }).join("");
            html += "</tbody></table></div>";
        }
        html += "</div>";
        if (!bloqueos.length) {
            html += "<button class=\"btn btn-sm btn-primary w-100 mt-3\" type=\"button\" id=\"ped_reserva_real\"><i class=\"bi bi-check2-circle\"></i> Crear apartado/pedido real</button>";
        }
        return html;
    }

    function crearReservaReal() {
        var errores = validarPartidasReserva(itemsReservaPayload());
        if (errores.length) {
            mostrarErroresReserva(errores);
            return;
        }
        if (!confirmar("CREAR", "crear el pedido/apartado real")) { return; }
        request("/ventas/pedido_guardar_erp", payloadReserva()).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            renderResultadoOperacion("ped_reserva_resultado", response, "Reserva, anticipo y evento registrados por backend.");
            partidasReserva = [];
            renderPartidasReserva();
            limpiarProductoReserva();
            cargarConfiguracion();
        }).catch(function (error) {
            document.getElementById("ped_reserva_resultado").innerHTML = "<div class=\"alert alert-danger py-3\">" + escapeHtml(error.message || String(error)) + "</div>";
        });
    }

    function registrarAbonoReal() {
        if (!confirmar("ABONAR", "registrar el abono real")) { return; }
        request("/ventas/apartado_abono_erp", payloadAbono()).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            renderResultadoOperacion("ped_abono_resultado", response, "Pago, caja y saldo actualizados por backend.");
            cargarListado();
        }).catch(function (error) {
            document.getElementById("ped_abono_resultado").innerHTML = "<div class=\"alert alert-danger py-3\">" + escapeHtml(error.message || String(error)) + "</div>";
        });
    }

    function entregarPedido(folio) {
        if (!confirmar("ENTREGAR", "entregar " + folio)) { return; }
        request("/ventas/pedido_entregar_erp", {folio: folio}).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            mostrarOperacionGlobal(response, "Entrega registrada. Reserva consumida y kardex generado.");
            cargarListado();
        }).catch(mostrarError);
    }

    function cancelarPedido(folio) {
        var motivo = window.prompt("Motivo de cancelacion");
        if (!motivo) { return; }
        if (!confirmar("CANCELAR", "cancelar " + folio)) { return; }
        request("/ventas/pedido_cancelar_erp", {folio: folio, motivo: motivo}).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            mostrarOperacionGlobal(response, "Reservas liberadas. Los pagos quedan para decision financiera.");
            cargarListado();
        }).catch(mostrarError);
    }

    function mostrarOperacionGlobal(response, detalle) {
        var data = response.depurar || {};
        document.getElementById("ped_alerta").innerHTML = "<div class=\"alert alert-success py-3\"><div class=\"fw-bold\">" + escapeHtml(response.mensaje || "Operacion completa") + "</div><div class=\"fs-7\">" + escapeHtml(detalle || "") + "</div><div class=\"fs-8 text-muted\">" + escapeHtml(data.folio || "") + "</div></div>";
    }

    function renderAlerta(schemaPendiente) {
        document.getElementById("ped_alerta").innerHTML = schemaPendiente
            ? "<div class=\"alert alert-warning py-3\"><div class=\"fw-bold\">Esquema de pedidos/apartados pendiente</div><div class=\"fs-7\">Esta pantalla queda en consulta/simulacion hasta autorizar reservas, abonos reales y eventos.</div></div>"
            : "<div class=\"alert alert-info py-3\"><div class=\"fw-bold\">Modulo operativo de pedidos y apartados</div><div class=\"fs-7\">Primero prevalidas; despues, cada accion real confirma en backend caja, turno, reserva, pago e inventario. Contexto: " + escapeHtml(textoSeleccion(document.getElementById("ped_reserva_almacen"))) + " / " + escapeHtml(textoSeleccion(document.getElementById("ped_reserva_caja"))) + ".</div></div>";
    }

    function mostrarError(error) {
        document.getElementById("ped_alerta").innerHTML = "<div class=\"alert alert-danger py-3\">" + escapeHtml(error.message || String(error)) + "</div>";
    }

    document.addEventListener("DOMContentLoaded", function () {
        cargarConfiguracion();
        document.getElementById("ped_recargar").addEventListener("click", cargarListado);
        document.getElementById("ped_tipo").addEventListener("change", cargarListado);
        document.getElementById("ped_estatus").addEventListener("change", cargarListado);
        document.getElementById("ped_q").addEventListener("input", function () {
            clearTimeout(temporizador);
            temporizador = setTimeout(cargarListado, 250);
        });
        document.getElementById("ped_abono_turno").addEventListener("change", function () { sincronizarDesdeTurnoPrefix("ped_abono"); });
        document.getElementById("ped_abono_almacen").addEventListener("change", function () { sincronizarPorAlmacen("ped_abono"); });
        document.getElementById("ped_abono_caja").addEventListener("change", function () { sincronizarPorCaja("ped_abono"); });
        document.getElementById("ped_reserva_turno").addEventListener("change", function () { sincronizarDesdeTurnoPrefix("ped_reserva"); });
        document.getElementById("ped_reserva_almacen").addEventListener("change", function () { sincronizarPorAlmacen("ped_reserva"); });
        document.getElementById("ped_reserva_caja").addEventListener("change", function () { sincronizarPorCaja("ped_reserva"); });
        document.getElementById("ped_producto_q").addEventListener("input", buscarProductoReserva);
        document.getElementById("ped_producto_limpiar").addEventListener("click", limpiarProductoReserva);
        document.getElementById("ped_producto_resultados").addEventListener("click", function (event) {
            var boton = event.target.closest("[data-ped-producto]");
            if (boton) { seleccionarProductoReserva(boton.getAttribute("data-ped-producto")); }
        });
        document.getElementById("ped_reserva_agregar").addEventListener("click", agregarPartidaReserva);
        document.getElementById("ped_reserva_vaciar").addEventListener("click", function () {
            partidasReserva = [];
            renderPartidasReserva();
            document.getElementById("ped_producto_estado").textContent = "Partidas vaciadas. Busca un producto para continuar.";
        });
        document.getElementById("ped_partidas_body").addEventListener("input", function (event) {
            var inputCantidad = event.target.closest("[data-ped-linea-cantidad]");
            var inputPrecio = event.target.closest("[data-ped-linea-precio]");
            if (inputCantidad) {
                partidasReserva[Number(inputCantidad.getAttribute("data-ped-linea-cantidad"))].cantidad = monto(inputCantidad.value);
                actualizarResumenReserva();
            }
            if (inputPrecio) {
                partidasReserva[Number(inputPrecio.getAttribute("data-ped-linea-precio"))].precio_unitario = monto(inputPrecio.value);
                actualizarResumenReserva();
            }
        });
        document.getElementById("ped_partidas_body").addEventListener("change", renderPartidasReserva);
        document.getElementById("ped_partidas_body").addEventListener("click", function (event) {
            var boton = event.target.closest("[data-ped-linea-quitar]");
            if (!boton) { return; }
            partidasReserva.splice(Number(boton.getAttribute("data-ped-linea-quitar")), 1);
            renderPartidasReserva();
        });
        ["ped_reserva_sku", "ped_reserva_cantidad", "ped_reserva_precio", "ped_reserva_anticipo"].forEach(function (id) {
            document.getElementById(id).addEventListener("input", actualizarResumenReserva);
        });
        document.getElementById("ped_reserva_simular").addEventListener("click", simularReserva);
        document.getElementById("ped_abono_simular").addEventListener("click", simularAbono);
        document.getElementById("ped_reserva_resultado").addEventListener("click", function (event) {
            if (event.target.closest("#ped_reserva_real")) { crearReservaReal(); }
        });
        document.getElementById("ped_abono_resultado").addEventListener("click", function (event) {
            if (event.target.closest("#ped_abono_real")) { registrarAbonoReal(); }
        });
        document.getElementById("ped_tabla").addEventListener("click", function (event) {
            var boton = event.target.closest("[data-ped-abono]");
            if (boton) {
                document.getElementById("ped_abono_folio").value = boton.getAttribute("data-ped-abono") || "";
                document.getElementById("ped_abono_monto").value = boton.getAttribute("data-ped-saldo") || "";
                document.getElementById("ped_abono_monto").focus();
                return;
            }
            boton = event.target.closest("[data-ped-entregar]");
            if (boton) {
                entregarPedido(boton.getAttribute("data-ped-entregar") || "");
                return;
            }
            boton = event.target.closest("[data-ped-cancelar]");
            if (boton) {
                cancelarPedido(boton.getAttribute("data-ped-cancelar") || "");
            }
        });
    });
})();
