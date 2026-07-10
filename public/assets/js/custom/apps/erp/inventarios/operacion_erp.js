"use strict";
(function () {
    var modo;
    var catalogos = {almacenes: [], ubicaciones: []};
    var partidas = [];
    var temporizador;
    var motivosAjuste = {
        inventario_inicial: [{valor: "inventario_inicial", texto: "Inventario inicial"}],
        entrada: [
            {valor: "sobrante_conteo", texto: "Sobrante por conteo fisico"},
            {valor: "correccion_documentada", texto: "Correccion documentada"},
            {valor: "devolucion_cliente", texto: "Devolucion de cliente"},
            {valor: "recuperacion", texto: "Recuperacion de inventario"}
        ],
        salida: [
            {valor: "faltante_conteo", texto: "Faltante por conteo fisico"},
            {valor: "merma", texto: "Merma"},
            {valor: "caducado", texto: "Caducado"},
            {valor: "danado", texto: "Danado"},
            {valor: "uso_interno", texto: "Uso interno"},
            {valor: "robo_perdida", texto: "Robo o perdida"},
            {valor: "correccion_documentada", texto: "Correccion documentada"}
        ]
    };

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
    function parseCantidad(value) {
        var numero = Number(String(value == null ? "" : value).replace(",", "."));
        return Number.isFinite(numero) ? numero : 0;
    }
    function formatoCantidad(value) {
        var numero = parseCantidad(value);
        return Number(numero.toFixed(4)).toString();
    }
    function pasoPartida(item) {
        var paso = parseCantidad(item.incremento_minimo_venta);
        return paso > 0 ? paso : 1;
    }
    function factorCompra(item) {
        var factor = parseCantidad(item.factor_conversion);
        return factor > 0 ? factor : 1;
    }
    function unidadBase(item) {
        return item.unidad_base_label || item.unidad_venta_label || "";
    }
    function cantidadBasePartida(item) {
        var modoCaptura = item.modo_captura || "base";
        if (modoCaptura === "unidad_compra") {
            return parseCantidad(item.cantidad_compra) * factorCompra(item);
        }
        if (modoCaptura === "unidad_fisica_cerrada") {
            return parseCantidad(item.cantidad_unidades_fisicas) * parseCantidad(item.contenido_base_original);
        }
        if (modoCaptura === "unidad_fisica_abierta") {
            return parseCantidad(item.contenido_base_disponible);
        }
        return parseCantidad(item.cantidad);
    }
    function esSalidaOperacion() {
        return modo === "traspaso" || document.getElementById("inventario_tipo").value === "salida";
    }
    function esInventarioInicial() {
        return modo === "ajuste" && document.getElementById("inventario_tipo").value === "entrada" && documentoOperacionActual() === "inventario_inicial";
    }
    function almacenOrigenOperacion() {
        return modo === "ajuste" ? document.getElementById("inventario_almacen").value : document.getElementById("inventario_almacen_origen").value;
    }
    function opcionesAlmacen(seleccionar) {
        return "<option value=\"\">Seleccionar</option>" + catalogos.almacenes.map(function (item) {
            return "<option value=\"" + item.id_almacen + "\">" + escapeHtml(item.almacen) + "</option>";
        }).join("");
    }
    function llenarCatalogos() {
        request("/inventario/catalogos_erp").then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            catalogos = response.depurar;
            if (modo === "ajuste") {
                document.getElementById("inventario_almacen").innerHTML = opcionesAlmacen();
            } else {
                document.getElementById("inventario_almacen_origen").innerHTML = opcionesAlmacen();
                document.getElementById("inventario_almacen_destino").innerHTML = opcionesAlmacen();
            }
            actualizarUbicaciones();
        }).catch(mostrarError);
    }
    function actualizarUbicaciones() {
        var almacen = modo === "ajuste" ? document.getElementById("inventario_almacen").value : document.getElementById("inventario_almacen_destino").value;
        var select = document.getElementById(modo === "ajuste" ? "inventario_ubicacion" : "inventario_ubicacion_destino");
        select.innerHTML = "<option value=\"\">Sin ubicacion</option>" + catalogos.ubicaciones.filter(function (item) {
            return String(item.id_almacen) === String(almacen);
        }).map(function (item) {
            return "<option value=\"" + item.id_ubicacion + "\">" + escapeHtml(item.codigo_ubicacion + " - " + item.nombre) + "</option>";
        }).join("");
    }
    function documentoOperacionActual() {
        if (modo !== "ajuste") {
            return "traspaso";
        }
        if (document.getElementById("inventario_tipo").value !== "entrada") {
            return "ajuste";
        }
        return document.getElementById("inventario_documento_operacion").value || "ajuste";
    }
    function actualizarDocumentoOperacion() {
        if (modo !== "ajuste") {
            return;
        }
        var tipo = document.getElementById("inventario_tipo").value;
        var documento = document.getElementById("inventario_documento_operacion");
        var referencia = document.getElementById("inventario_referencia");
        if (tipo === "salida") {
            documento.value = "ajuste";
            documento.disabled = true;
            referencia.placeholder = "AJU-YYYYMMDD-0001";
            llenarMotivosAjuste("salida");
            return;
        }
        documento.disabled = false;
        referencia.placeholder = documento.value === "inventario_inicial" ? "INV-INICIAL-YYYYMMDD-0001" : "AJU-YYYYMMDD-0001";
        llenarMotivosAjuste(documento.value === "inventario_inicial" ? "inventario_inicial" : "entrada");
    }
    function llenarMotivosAjuste(grupo) {
        var select = document.getElementById("inventario_motivo_ajuste");
        if (!select) { return; }
        select.innerHTML = (motivosAjuste[grupo] || motivosAjuste.entrada).map(function (item) {
            return "<option value=\"" + escapeHtml(item.valor) + "\">" + escapeHtml(item.texto) + "</option>";
        }).join("");
    }
    function buscar() {
        var input = document.getElementById("inventario_buscar_sku");
        var termino = input.value.trim();
        clearTimeout(temporizador);
        if (termino.length < 2) {
            document.getElementById("inventario_resultados").classList.add("d-none");
            return;
        }
        temporizador = setTimeout(function () {
            var almacen = almacenOrigenOperacion();
            request("/inventario/buscar_skus_erp?" + new URLSearchParams({q: termino, id_almacen: almacen}).toString()).then(function (response) {
                if (response.error) { throw new Error(response.mensaje); }
                renderResultados(response.depurar || []);
            }).catch(mostrarError);
        }, 250);
    }
    function renderResultados(items) {
        var box = document.getElementById("inventario_resultados");
        box.innerHTML = items.map(function (item) {
            return "<button type=\"button\" class=\"d-flex justify-content-between align-items-center w-100 border-0 border-bottom bg-white text-start p-4\" data-agregar-sku=\"" + item.id_sku + "\">" +
                "<span><strong>" + escapeHtml(item.sku) + "</strong><span class=\"d-block text-muted fs-7\">" + escapeHtml(item.nombre_sku) + "</span></span>" +
                "<span class=\"text-end\"><span class=\"badge badge-light-primary\">" + Number(item.existencia_disponible || 0).toFixed(2) + " " + escapeHtml(item.unidad_venta_label || "") + "</span>" +
                (Number(item.permite_venta_fraccionaria || 0) ? "<span class=\"badge badge-light-info d-block mt-1\">Fraccionario</span>" : "") +
                (Number(item.generar_etiqueta_interna || 0) ? "<span class=\"badge badge-light-warning d-block mt-1\">Etiqueta</span>" : "") + "</span></button>";
        }).join("") || "<div class=\"text-muted p-5\">Sin resultados</div>";
        box.classList.remove("d-none");
        box.querySelectorAll("[data-agregar-sku]").forEach(function (button, index) {
            button.addEventListener("click", function () {
                var item = items[index];
                box.classList.add("d-none");
                document.getElementById("inventario_buscar_sku").value = "";
                agregarPartida(item);
            });
        });
    }
    function agregarPartida(item) {
        var partida = {
            id_sku: item.id_sku,
            sku: item.sku,
            nombre: item.nombre_sku,
            disponible: item.existencia_disponible,
            cantidad: 1,
            modo_captura: "base",
            cantidad_compra: 1,
            factor_conversion: item.factor_conversion_compra || item.factor_unidad_base || 1,
            unidad_compra_label: item.unidad_compra_label || "",
            unidad_base_label: item.unidad_base_label || item.unidad_venta_label || "",
            cantidad_unidades_fisicas: 1,
            contenido_base_original: item.factor_conversion_compra || item.factor_unidad_base || 1,
            contenido_base_disponible: item.factor_conversion_compra || item.factor_unidad_base || 1,
            lote: "",
            fecha_caducidad: "",
            id_existencia_inventario: "",
            existencias: [],
            permite_venta_fraccionaria: Number(item.permite_venta_fraccionaria || 0),
            precision_decimal: Number(item.precision_decimal || 0),
            incremento_minimo_venta: item.incremento_minimo_venta || 1,
            unidad_venta_label: item.unidad_venta_label || "",
            generar_etiqueta_interna: Number(item.generar_etiqueta_interna || 0),
            requiere_lote: Number(item.requiere_lote || 0),
            requiere_caducidad: Number(item.requiere_caducidad || 0)
        };
        partidas.push(partida);
        if (!esSalidaOperacion()) {
            renderPartidas();
            return;
        }
        cargarExistenciasPartida(partida).then(function () {
            renderPartidas();
        }).catch(mostrarError);
        renderPartidas();
    }
    function cargarExistenciasPartida(partida) {
        var almacen = almacenOrigenOperacion();
        if (!almacen) {
            partida.existencias = [];
            return Promise.resolve();
        }
        return request("/inventario/existencias_erp?" + new URLSearchParams({
            q: partida.sku,
            id_almacen: almacen,
            incluir_agotadas: "0"
        }).toString()).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            partida.existencias = (response.depurar || []).filter(function (existencia) {
                return String(existencia.sku) === String(partida.sku) && Number(existencia.cantidad_disponible || 0) > 0;
            });
            if (partida.existencias.length) {
                var existenciaLibre = partida.existencias.find(function (existencia) {
                    return !partidas.some(function (otra) {
                        return otra !== partida && String(otra.id_existencia_inventario) === String(existencia.id_existencia_inventario);
                    });
                }) || partida.existencias[0];
                aplicarExistenciaSeleccionada(partida, existenciaLibre.id_existencia_inventario);
            } else {
                partida.id_existencia_inventario = "";
                partida.disponible = 0;
            }
        });
    }
    function aplicarExistenciaSeleccionada(partida, idExistencia) {
        var existencia = (partida.existencias || []).find(function (item) {
            return String(item.id_existencia_inventario) === String(idExistencia);
        });
        if (!existencia) {
            partida.id_existencia_inventario = "";
            partida.disponible = 0;
            partida.lote = "";
            partida.fecha_caducidad = "";
            return;
        }
        partida.id_existencia_inventario = existencia.id_existencia_inventario;
        partida.disponible = existencia.cantidad_disponible;
        partida.lote = existencia.lote || "";
        partida.fecha_caducidad = existencia.fecha_caducidad || "";
        partida.codigo_existencia = existencia.codigo_existencia || "";
        partida.ubicacion = existencia.ubicacion || "";
        partida.almacen = existencia.almacen || "";
        if (parseCantidad(partida.cantidad) > Number(partida.disponible || 0)) {
            partida.cantidad = Number(partida.disponible || 0);
        }
    }
    function renderPartidas() {
        var esSalida = esSalidaOperacion();
        var inventarioInicial = esInventarioInicial();
        document.getElementById("inventario_partidas").innerHTML = partidas.map(function (item, index) {
            var loteControl = "";
            var caducidadControl = "";
            var capturaInicial = "";
            if (esSalida) {
                loteControl = (item.existencias || []).length ? "<select class=\"form-select form-select-sm\" data-partida-existencia=\"" + index + "\">" + item.existencias.map(function (existencia) {
                    var texto = (existencia.codigo_existencia || "EXI") + " | " + (existencia.lote || "Sin lote") + " | " + (existencia.ubicacion || "Sin ubicacion") + " | disp " + Number(existencia.cantidad_disponible || 0).toFixed(2);
                    return "<option value=\"" + existencia.id_existencia_inventario + "\"" + (String(existencia.id_existencia_inventario) === String(item.id_existencia_inventario) ? " selected" : "") + ">" + escapeHtml(texto) + "</option>";
                }).join("") + "</select>" : "<div class=\"text-muted fs-8\">Sin existencia disponible</div>";
                caducidadControl = escapeHtml(item.fecha_caducidad || "-") + "<div class=\"text-muted fs-8\">" + escapeHtml(item.ubicacion || "") + "</div>";
            } else {
                loteControl = "<input class=\"form-control form-control-sm\" value=\"" + escapeHtml(item.lote) + "\" data-partida-lote=\"" + index + "\">";
                caducidadControl = "<input class=\"form-control form-control-sm\" type=\"date\" value=\"" + escapeHtml(item.fecha_caducidad) + "\" data-partida-caducidad=\"" + index + "\">";
                if (inventarioInicial) {
                    capturaInicial = "<div class=\"mt-3\"><select class=\"form-select form-select-sm\" data-partida-modo-captura=\"" + index + "\">" +
                        "<option value=\"base\"" + (item.modo_captura === "base" ? " selected" : "") + ">Unidad base</option>" +
                        "<option value=\"unidad_compra\"" + (item.modo_captura === "unidad_compra" ? " selected" : "") + ">Unidad compra</option>" +
                        "<option value=\"unidad_fisica_cerrada\"" + (item.modo_captura === "unidad_fisica_cerrada" ? " selected" : "") + ">Unidad cerrada</option>" +
                        "<option value=\"unidad_fisica_abierta\"" + (item.modo_captura === "unidad_fisica_abierta" ? " selected" : "") + ">Unidad abierta</option>" +
                        "</select>" + renderCapturaInicial(item, index) + "</div>";
                }
            }
            var badges = (item.permite_venta_fraccionaria ? "<span class=\"badge badge-light-info ms-1\">Fraccionario</span>" : "") + (item.generar_etiqueta_interna ? "<span class=\"badge badge-light-warning ms-1\">Etiqueta</span>" : "");
            return "<tr><td><div class=\"fw-bold\">" + escapeHtml(item.sku) + badges + "</div><div class=\"text-muted fs-7\">" + escapeHtml(item.nombre) + "</div>" + (item.codigo_existencia ? "<div class=\"text-muted fs-8\">" + escapeHtml(item.codigo_existencia) + "</div>" : "") + "</td>" +
                "<td>" + Number(item.disponible || 0).toFixed(2) + (item.unidad_venta_label ? " " + escapeHtml(item.unidad_venta_label) : "") + "</td>" +
                "<td><div class=\"input-group input-group-sm inventario-cantidad-control\"><button class=\"btn btn-light\" type=\"button\" data-partida-cantidad-ajuste=\"-1\" data-index=\"" + index + "\" title=\"Disminuir cantidad\"><i class=\"bi bi-dash\"></i></button><input class=\"form-control\" inputmode=\"decimal\" value=\"" + escapeHtml(formatoCantidad(item.cantidad)) + "\" data-partida-cantidad=\"" + index + "\"><button class=\"btn btn-light\" type=\"button\" data-partida-cantidad-ajuste=\"1\" data-index=\"" + index + "\" title=\"Aumentar cantidad\"><i class=\"bi bi-plus\"></i></button></div>" + (item.unidad_venta_label ? "<div class=\"text-muted fs-8 mt-1\">" + escapeHtml(item.unidad_venta_label) + "</div>" : "") + capturaInicial + "</td>" +
                "<td>" + loteControl + "</td>" +
                "<td>" + caducidadControl + "</td>" +
                "<td class=\"text-end\"><button type=\"button\" class=\"btn btn-sm btn-icon btn-light-danger\" data-partida-quitar=\"" + index + "\" title=\"Quitar\"><i class=\"bi bi-trash\"></i></button></td></tr>";
        }).join("") || "<tr><td colspan=\"6\" class=\"text-center text-muted py-10\">Agrega al menos un SKU</td></tr>";
    }
    function renderCapturaInicial(item, index) {
        var modoCaptura = item.modo_captura || "base";
        var unidadBaseTexto = unidadBase(item);
        var calculado = formatoCantidad(cantidadBasePartida(item)) + (unidadBaseTexto ? " " + escapeHtml(unidadBaseTexto) : "");
        if (modoCaptura === "unidad_compra") {
            return "<div class=\"row g-2 mt-1\"><div class=\"col-6\"><input class=\"form-control form-control-sm\" inputmode=\"decimal\" value=\"" + escapeHtml(formatoCantidad(item.cantidad_compra)) + "\" data-partida-cantidad-compra=\"" + index + "\"></div>" +
                "<div class=\"col-6\"><input class=\"form-control form-control-sm\" inputmode=\"decimal\" value=\"" + escapeHtml(formatoCantidad(item.factor_conversion)) + "\" data-partida-factor-conversion=\"" + index + "\"></div>" +
                "<div class=\"col-12 text-muted fs-8\">" + escapeHtml(item.unidad_compra_label || "unidad compra") + " x factor = " + calculado + "</div></div>";
        }
        if (modoCaptura === "unidad_fisica_cerrada") {
            return "<div class=\"row g-2 mt-1\"><div class=\"col-6\"><input class=\"form-control form-control-sm\" inputmode=\"numeric\" value=\"" + escapeHtml(formatoCantidad(item.cantidad_unidades_fisicas)) + "\" data-partida-unidades-fisicas=\"" + index + "\"></div>" +
                "<div class=\"col-6\"><input class=\"form-control form-control-sm\" inputmode=\"decimal\" value=\"" + escapeHtml(formatoCantidad(item.contenido_base_original)) + "\" data-partida-contenido-original=\"" + index + "\"></div>" +
                "<div class=\"col-12 text-muted fs-8\">unidades x contenido = " + calculado + "</div></div>";
        }
        if (modoCaptura === "unidad_fisica_abierta") {
            return "<div class=\"row g-2 mt-1\"><div class=\"col-6\"><input class=\"form-control form-control-sm\" inputmode=\"decimal\" value=\"" + escapeHtml(formatoCantidad(item.contenido_base_original)) + "\" data-partida-contenido-original=\"" + index + "\"></div>" +
                "<div class=\"col-6\"><input class=\"form-control form-control-sm\" inputmode=\"decimal\" value=\"" + escapeHtml(formatoCantidad(item.contenido_base_disponible)) + "\" data-partida-contenido-disponible=\"" + index + "\"></div>" +
                "<div class=\"col-12 text-muted fs-8\">disponible inicial = " + calculado + "</div></div>";
        }
        return "<div class=\"text-muted fs-8 mt-1\">Entrada base = " + calculado + "</div>";
    }
    function validarAntesDeAplicar() {
        var esSalida = esSalidaOperacion();
        if (modo === "ajuste" && !document.getElementById("inventario_almacen").value) {
            throw new Error("Selecciona almacen");
        }
        if (modo === "traspaso") {
            var origen = document.getElementById("inventario_almacen_origen").value;
            var destino = document.getElementById("inventario_almacen_destino").value;
            if (!origen || !destino || origen === destino) {
                throw new Error("Selecciona almacenes diferentes");
            }
        }
        if (modo === "ajuste") {
            var documento = documentoOperacionActual();
            var referencia = document.getElementById("inventario_referencia").value.trim().toUpperCase();
            if (!document.getElementById("inventario_motivo_ajuste").value) {
                throw new Error("Selecciona motivo de ajuste");
            }
            if (documento === "inventario_inicial" && referencia.indexOf("INV-INICIAL-") !== 0) {
                throw new Error("La referencia debe iniciar con INV-INICIAL-");
            }
        }
        var acumuladoPorExistencia = {};
        partidas.forEach(function (item) {
            item.cantidad = parseCantidad(item.cantidad);
            var cantidadBase = cantidadBasePartida(item);
            if (cantidadBase <= 0) {
                throw new Error("Todas las cantidades deben ser mayores a cero");
            }
            if (!item.permite_venta_fraccionaria && Math.abs(cantidadBase - Math.round(cantidadBase)) > 0.0001) {
                throw new Error("La cantidad de " + item.sku + " debe ser entera");
            }
            if (esSalida && !item.id_existencia_inventario) {
                throw new Error("Selecciona la existencia fisica de " + item.sku);
            }
            if (esSalida && item.cantidad > Number(item.disponible || 0)) {
                throw new Error("La cantidad de " + item.sku + " supera lo disponible");
            }
            if (esSalida) {
                var clave = String(item.id_existencia_inventario);
                acumuladoPorExistencia[clave] = (acumuladoPorExistencia[clave] || 0) + item.cantidad;
                if (acumuladoPorExistencia[clave] > Number(item.disponible || 0)) {
                    throw new Error("La suma de partidas para " + (item.codigo_existencia || item.sku) + " supera lo disponible");
                }
            } else {
                if (item.requiere_lote && !String(item.lote || "").trim()) {
                    throw new Error("Captura lote para " + item.sku);
                }
                if (item.requiere_caducidad && !String(item.fecha_caducidad || "").trim()) {
                    throw new Error("Captura caducidad para " + item.sku);
                }
                if (item.modo_captura === "unidad_fisica_abierta" && parseCantidad(item.contenido_base_disponible) > parseCantidad(item.contenido_base_original)) {
                    throw new Error("La unidad abierta de " + item.sku + " no puede tener disponible mayor al contenido original");
                }
                if (item.generar_etiqueta_interna && item.modo_captura !== "unidad_fisica_cerrada" && item.modo_captura !== "unidad_fisica_abierta" && Math.abs(cantidadBase - Math.round(cantidadBase)) > 0.0001) {
                    throw new Error("La cantidad de " + item.sku + " debe ser entera para generar etiquetas");
                }
            }
        });
    }
    function aplicar() {
        if (!partidas.length) {
            Swal.fire({text: "Agrega al menos un SKU", icon: "warning", confirmButtonText: "Aceptar"});
            return;
        }
        try {
            validarAntesDeAplicar();
        } catch (error) {
            mostrarError(error);
            return;
        }
        var data = {items: JSON.stringify(partidas), observaciones: document.getElementById("inventario_observaciones").value};
        var url;
        if (modo === "ajuste") {
            data.id_almacen = document.getElementById("inventario_almacen").value;
            data.tipo_ajuste = document.getElementById("inventario_tipo").value;
            data.documento_operacion = documentoOperacionActual();
            data.motivo_ajuste = document.getElementById("inventario_motivo_ajuste").value;
            data.referencia = document.getElementById("inventario_referencia").value.trim();
            var ubicacion = document.getElementById("inventario_ubicacion").value;
            partidas.forEach(function (item) { item.ubicacion_id = ubicacion; });
            data.items = JSON.stringify(partidas);
            url = "/inventario/ajustar_erp";
        } else {
            data.id_almacen_origen = document.getElementById("inventario_almacen_origen").value;
            data.id_almacen_destino = document.getElementById("inventario_almacen_destino").value;
            var destino = document.getElementById("inventario_ubicacion_destino").value;
            partidas.forEach(function (item) { item.ubicacion_destino_id = destino; });
            data.items = JSON.stringify(partidas);
            url = "/inventario/traspasar_erp";
        }
        request(url, data).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            Swal.fire({text: response.mensaje + ". Referencia: " + response.depurar.referencia, icon: "success", confirmButtonText: "Ver kardex"}).then(function () {
                window.location.href = "/inventario/productos_existencias#kardex";
            });
        }).catch(mostrarError);
    }
    function mostrarError(error) { Swal.fire({text: error.message || String(error), icon: "error", confirmButtonText: "Aceptar"}); }

    document.addEventListener("DOMContentLoaded", function () {
        modo = document.getElementById("inventario_modo").value;
        llenarCatalogos();
        renderPartidas();
        document.getElementById("inventario_buscar_sku").addEventListener("input", buscar);
        document.getElementById("inventario_aplicar").addEventListener("click", aplicar);
        if (modo === "ajuste") {
            document.getElementById("inventario_almacen").addEventListener("change", function () {
                partidas = [];
                actualizarUbicaciones();
                renderPartidas();
            });
            document.getElementById("inventario_tipo").addEventListener("change", function () {
                partidas = [];
                actualizarDocumentoOperacion();
                renderPartidas();
            });
            document.getElementById("inventario_documento_operacion").addEventListener("change", function () {
                partidas = [];
                actualizarDocumentoOperacion();
                renderPartidas();
            });
            actualizarDocumentoOperacion();
        } else {
            document.getElementById("inventario_almacen_origen").addEventListener("change", function () {
                partidas = [];
                renderPartidas();
            });
            document.getElementById("inventario_almacen_destino").addEventListener("change", actualizarUbicaciones);
        }
        document.getElementById("inventario_partidas").addEventListener("input", function (event) {
            ["cantidad", "lote", "caducidad", "cantidad-compra", "factor-conversion", "unidades-fisicas", "contenido-original", "contenido-disponible"].forEach(function (campo) {
                var indice = event.target.getAttribute("data-partida-" + campo);
                if (indice !== null) {
                    var key = campo === "cantidad" ? "cantidad" : (campo === "caducidad" ? "fecha_caducidad" : campo.replace(/-/g, "_"));
                    partidas[Number(indice)][key] = campo === "lote" || campo === "caducidad" ? event.target.value : parseCantidad(event.target.value);
                }
            });
        });
        document.getElementById("inventario_partidas").addEventListener("change", function (event) {
            var indice = event.target.getAttribute("data-partida-existencia");
            if (indice !== null) {
                aplicarExistenciaSeleccionada(partidas[Number(indice)], event.target.value);
                renderPartidas();
            }
            indice = event.target.getAttribute("data-partida-modo-captura");
            if (indice !== null) {
                partidas[Number(indice)].modo_captura = event.target.value;
                renderPartidas();
            }
        });
        document.getElementById("inventario_partidas").addEventListener("click", function (event) {
            var ajuste = event.target.closest("[data-partida-cantidad-ajuste]");
            if (ajuste) {
                var indice = Number(ajuste.getAttribute("data-index"));
                var direccion = Number(ajuste.getAttribute("data-partida-cantidad-ajuste") || 0);
                var delta = pasoPartida(partidas[indice]) * direccion;
                partidas[indice].cantidad = Math.max(pasoPartida(partidas[indice]), parseCantidad(partidas[indice].cantidad) + delta);
                renderPartidas();
                return;
            }
            var button = event.target.closest("[data-partida-quitar]");
            if (button) {
                partidas.splice(Number(button.getAttribute("data-partida-quitar")), 1);
                renderPartidas();
            }
        });
    });
})();
