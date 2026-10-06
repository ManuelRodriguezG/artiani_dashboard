"use strict";
(function () {
    var estado = {cotizaciones: [], actualId: null};
    var timerBusqueda = null;
    var placeholderImagen = "data:image/svg+xml;charset=UTF-8,%3Csvg%20xmlns='http://www.w3.org/2000/svg'%20viewBox='0%200%20400%20300'%3E%3Crect%20width='400'%20height='300'%20fill='%23f1f3f6'/%3E%3Cpath%20d='M80%20225h240l-70-85-55%2065-35-42z'%20fill='%23c8ced8'/%3E%3Ccircle%20cx='135'%20cy='105'%20r='28'%20fill='%23d7dce5'/%3E%3C/svg%3E";

    /**
     * IA: Codex GPT-5 | Fecha: 2026-10-04
     * Proposito: operar cotizaciones locales de envios foraneos sin escribir BD.
     * Impacto: Ventas/Envios foraneos; prepara captura urgente y pendientes de Catalogo.
     * Contrato: localStorage es temporal; Pedido/TMS formal requieren persistencia autorizada posterior.
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

    function $(id) {
        return document.getElementById(id);
    }

    function escapeHtml(value) {
        var div = document.createElement("div");
        div.textContent = value == null ? "" : String(value);
        return div.innerHTML;
    }

    function dinero(value) {
        return new Intl.NumberFormat("es-MX", {style: "currency", currency: "MXN"}).format(numero(value));
    }

    function numero(value) {
        var normalizado = String(value || "0").replace(",", ".");
        var match = normalizado.match(/-?\d+(\.\d+)?/);
        var parsed = match ? Number(match[0]) : Number(normalizado);
        return Number.isFinite(parsed) ? parsed : 0;
    }

    function cantidad(value, decimales) {
        var n = numero(value);
        return n.toLocaleString("es-MX", {minimumFractionDigits: decimales || 0, maximumFractionDigits: decimales == null ? 2 : decimales});
    }

    function uid() {
        return "ENV-" + new Date().toISOString().slice(0, 10).replace(/-/g, "") + "-" + Math.random().toString(36).slice(2, 7).toUpperCase();
    }

    function hoyMas(dias) {
        var fecha = new Date();
        fecha.setDate(fecha.getDate() + dias);
        return fecha.getFullYear() + "-" + String(fecha.getMonth() + 1).padStart(2, "0") + "-" + String(fecha.getDate()).padStart(2, "0");
    }

    function imagen(item) {
        var url = String(item.url_imagen || item.imagen || "").trim();
        if (!url) { return placeholderImagen; }
        if (/^(https?:)?\/\//i.test(url) || url.indexOf("data:") === 0 || url.charAt(0) === "/") {
            return url;
        }
        return "/" + url.replace(/^\/+/, "");
    }

    function cargarEstado() {
        var params = new URLSearchParams({
            q: $("env_filtro_q").value.trim(),
            estatus: $("env_filtro_estatus").value,
            limite: "120"
        });
        request("/ventas/envios_foraneos_listar_erp?" + params.toString()).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            estado.cotizaciones = ((response.depurar || {}).cotizaciones || []).map(mapearResumenDb);
            if (!estado.actualId || !estado.cotizaciones.some(function (item) { return item.id === estado.actualId; })) {
                estado.actualId = estado.cotizaciones.length ? estado.cotizaciones[0].id : null;
            }
            if (estado.actualId) {
                cargarCotizacion(estado.actualId);
            } else {
                nuevaCotizacion(false);
                renderTodo();
            }
        }).catch(function (error) {
            mostrarAlerta(error.message || "No fue posible cargar envios foraneos", "danger");
            nuevaCotizacion(false);
            renderTodo();
        });
    }

    function persistir() {
        return true;
    }

    function mapearResumenDb(row) {
        return {
            id: "db-" + row.id_envio_foraneo,
            id_envio_foraneo: Number(row.id_envio_foraneo || 0),
            folio: row.folio || "",
            estatus: row.estatus || "borrador",
            fecha: row.fecha_registro || "",
            cliente: {nombre: row.cliente_nombre || "", telefono: row.cliente_telefono || "", correo: row.cliente_correo || ""},
            origen: row.origen_contacto || "",
            destino: {cp: row.destino_cp || "", estado: row.destino_estado || "", ciudad: row.destino_ciudad || "", direccion: row.destino_direccion || ""},
            partidas: [],
            paquete: {largo: row.paquete_largo_cm || "", ancho: row.paquete_ancho_cm || "", alto: row.paquete_alto_cm || "", peso: row.paquete_peso_kg || "", cantidad: row.paquete_cantidad || "1"},
            envio: {paqueteria: row.paqueteria || "", servicio: row.servicio_envio || "", costo: row.costo_envio || "", precio: row.precio_envio_cliente || "", vigencia: row.vigencia_cotizacion || "", guia: row.guia_referencia || ""},
            notas: row.observaciones || "",
            resumen_db: row
        };
    }

    function mapearConsultaDb(data) {
        var row = data.cotizacion || {};
        var item = null;
        try {
            item = row.datos_snapshot ? JSON.parse(row.datos_snapshot) : null;
        } catch (error) {
            item = null;
        }
        if (!item || typeof item !== "object") {
            item = mapearResumenDb(row);
            item.partidas = (data.detalle || []).map(function (detalle) {
                var snapshot = {};
                try { snapshot = detalle.datos_snapshot ? JSON.parse(detalle.datos_snapshot) : {}; } catch (error) { snapshot = {}; }
                return Object.assign({
                    id_sku: detalle.id_sku_erp || null,
                    sku: detalle.sku || "",
                    nombre: detalle.descripcion || "",
                    cantidad: detalle.cantidad || "1",
                    precio: detalle.precio_unitario || "0",
                    largo_cm: detalle.largo_cm_snapshot || "",
                    ancho_cm: detalle.ancho_cm_snapshot || "",
                    alto_cm: detalle.alto_cm_snapshot || "",
                    peso_kg: detalle.peso_kg_snapshot || "",
                    manual: Number(detalle.producto_no_identificado || 0) === 1
                }, snapshot);
            });
        }
        item.id_envio_foraneo = Number(row.id_envio_foraneo || item.id_envio_foraneo || 0);
        item.id = "db-" + item.id_envio_foraneo;
        item.folio = row.folio || item.folio || item.id;
        item.estatus = row.estatus || item.estatus || "borrador";
        return item;
    }

    function cargarCotizacion(id) {
        var item = estado.cotizaciones.find(function (cot) { return cot.id === id; });
        if (!item) { return; }
        if (!item.id_envio_foraneo) {
            estado.actualId = item.id;
            renderTodo();
            return;
        }
        request("/ventas/envios_foraneos_consultar_erp?id_envio_foraneo=" + encodeURIComponent(item.id_envio_foraneo)).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            var completo = mapearConsultaDb(response.depurar || {});
            var index = estado.cotizaciones.findIndex(function (cot) { return cot.id === id; });
            if (index >= 0) {
                estado.cotizaciones[index] = completo;
            } else {
                estado.cotizaciones.unshift(completo);
            }
            estado.actualId = completo.id;
            renderTodo();
        }).catch(function (error) {
            mostrarAlerta(error.message || "No fue posible consultar la cotizacion", "danger");
        });
    }

    function cotizacionVacia() {
        return {
            id: uid(),
            folio: "",
            estatus: "borrador",
            fecha: new Date().toISOString(),
            cliente: {nombre: "", telefono: "", correo: ""},
            origen: "",
            destino: {cp: "", estado: "", ciudad: "", direccion: ""},
            partidas: [],
            paquete: {largo: "", ancho: "", alto: "", peso: "", cantidad: "1"},
            envio: {paqueteria: "", servicio: "", costo: "", precio: "", vigencia: hoyMas(2), guia: ""},
            notas: ""
        };
    }

    function actual() {
        return estado.cotizaciones.find(function (item) { return item.id === estado.actualId; }) || null;
    }

    function tieneContenido(item) {
        if (!item) { return false; }
        return Boolean(
            (item.cliente && (item.cliente.nombre || item.cliente.telefono || item.cliente.correo)) ||
            item.origen ||
            (item.destino && (item.destino.cp || item.destino.estado || item.destino.ciudad || item.destino.direccion)) ||
            (item.partidas && item.partidas.length) ||
            (item.paquete && (item.paquete.largo || item.paquete.ancho || item.paquete.alto || item.paquete.peso)) ||
            (item.envio && (item.envio.paqueteria || item.envio.servicio || item.envio.costo || item.envio.precio || item.envio.guia)) ||
            item.notas
        );
    }

    function nuevaCotizacion(render) {
        var item = cotizacionVacia();
        item.folio = item.id;
        estado.cotizaciones.unshift(item);
        estado.actualId = item.id;
        if (render !== false) {
            renderTodo();
            mostrarAlerta("Borrador creado.", "success");
        }
    }

    function leerFormulario() {
        var item = actual();
        if (!item) { return; }
        item.estatus = $("env_estatus").value;
        item.cliente.nombre = $("env_cliente_nombre").value.trim();
        item.cliente.telefono = $("env_cliente_telefono").value.trim();
        item.cliente.correo = $("env_cliente_correo").value.trim();
        item.origen = $("env_origen").value.trim();
        item.destino.cp = $("env_cp").value.trim();
        item.destino.estado = $("env_estado").value.trim();
        item.destino.ciudad = $("env_ciudad").value.trim();
        item.destino.direccion = $("env_direccion").value.trim();
        item.paquete.largo = $("env_paquete_largo").value;
        item.paquete.ancho = $("env_paquete_ancho").value;
        item.paquete.alto = $("env_paquete_alto").value;
        item.paquete.peso = $("env_paquete_peso").value;
        item.paquete.cantidad = $("env_paquete_cantidad").value || "1";
        item.envio.paqueteria = $("env_paqueteria").value.trim();
        item.envio.servicio = $("env_servicio").value.trim();
        item.envio.costo = $("env_costo_envio").value;
        item.envio.precio = $("env_precio_envio").value;
        item.envio.vigencia = $("env_vigencia").value;
        item.envio.guia = $("env_guia").value.trim();
        item.notas = $("env_notas").value.trim();
    }

    function escribirFormulario() {
        var item = actual();
        if (!item) { return; }
        $("env_folio_label").textContent = item.folio || item.id;
        $("env_estatus").value = item.estatus || "borrador";
        $("env_cliente_nombre").value = item.cliente.nombre || "";
        $("env_cliente_telefono").value = item.cliente.telefono || "";
        $("env_cliente_correo").value = item.cliente.correo || "";
        $("env_origen").value = item.origen || "";
        $("env_cp").value = item.destino.cp || "";
        $("env_estado").value = item.destino.estado || "";
        $("env_ciudad").value = item.destino.ciudad || "";
        $("env_direccion").value = item.destino.direccion || "";
        $("env_paquete_largo").value = item.paquete.largo || "";
        $("env_paquete_ancho").value = item.paquete.ancho || "";
        $("env_paquete_alto").value = item.paquete.alto || "";
        $("env_paquete_peso").value = item.paquete.peso || "";
        $("env_paquete_cantidad").value = item.paquete.cantidad || "1";
        $("env_paqueteria").value = item.envio.paqueteria || "";
        $("env_servicio").value = item.envio.servicio || "";
        $("env_costo_envio").value = item.envio.costo || "";
        $("env_precio_envio").value = item.envio.precio || "";
        $("env_vigencia").value = item.envio.vigencia || hoyMas(2);
        $("env_guia").value = item.envio.guia || "";
        $("env_notas").value = item.notas || "";
    }

    function guardarActual(silencioso) {
        leerFormulario();
        var item = actual();
        if (!item) { return; }
        request("/ventas/envios_foraneos_guardar_erp", {payload: JSON.stringify(item)}).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            var depurar = response.depurar || {};
            item.id_envio_foraneo = depurar.id_envio_foraneo;
            item.id = "db-" + depurar.id_envio_foraneo;
            item.folio = depurar.folio || item.folio;
            item.estatus = depurar.estatus || item.estatus;
            estado.actualId = item.id;
            if (!estado.cotizaciones.some(function (cot) { return cot.id === item.id; })) {
                estado.cotizaciones.unshift(item);
            }
            renderTodo(false);
            cargarEstado();
            if (!silencioso) {
                mostrarAlerta("Cotizacion guardada en base de datos.", "success");
            }
        }).catch(function (error) {
            mostrarAlerta(error.message || "No fue posible guardar", "danger");
        });
    }

    function pendientesPartida(item) {
        var pendientes = [];
        if (item.manual) {
            pendientes.push("producto_no_identificado");
        }
        if (!numero(item.largo_cm) || !numero(item.ancho_cm) || !numero(item.alto_cm)) {
            pendientes.push("faltan_medidas_producto");
        }
        if (!numero(item.peso_kg)) {
            pendientes.push("falta_peso_producto");
        }
        return pendientes;
    }

    function pendientesCotizacion(item) {
        var pendientes = [];
        if (!item.cliente.nombre || !item.cliente.telefono) {
            pendientes.push("contacto_incompleto");
        }
        if (!item.destino.cp || !item.destino.estado || !item.destino.ciudad) {
            pendientes.push("destino_incompleto");
        }
        if (!item.partidas.length) {
            pendientes.push("sin_productos");
        }
        if (!numero(item.paquete.largo) || !numero(item.paquete.ancho) || !numero(item.paquete.alto) || !numero(item.paquete.peso)) {
            pendientes.push("paquete_pendiente_medicion");
        }
        item.partidas.forEach(function (partida) {
            pendientes = pendientes.concat(pendientesPartida(partida));
        });
        return pendientes;
    }

    function totales(item) {
        var productos = item.partidas.reduce(function (total, partida) {
            return total + numero(partida.cantidad) * numero(partida.precio);
        }, 0);
        var envioCliente = numero(item.envio.precio);
        var costoEnvio = numero(item.envio.costo);
        var volumetrico = (numero(item.paquete.largo) * numero(item.paquete.ancho) * numero(item.paquete.alto) / 5000) * Math.max(1, numero(item.paquete.cantidad));
        return {
            productos: productos,
            envio_cliente: envioCliente,
            costo_envio: costoEnvio,
            total: productos + envioCliente,
            volumetrico: volumetrico
        };
    }

    function badge(texto, tipo) {
        return "<span class=\"badge badge-light-" + tipo + "\">" + escapeHtml(texto) + "</span>";
    }

    function estatusLabel(estatus) {
        var labels = {
            borrador: "Borrador",
            datos_incompletos: "Datos incompletos",
            cotizando_envio: "Cotizando envio",
            cotizacion_enviada: "Cotizacion enviada",
            aceptada: "Aceptada",
            convertida_pedido: "Convertida a pedido",
            descartada: "Descartada"
        };
        return labels[estatus] || estatus || "Borrador";
    }

    function renderBandeja() {
        var q = $("env_filtro_q").value.trim().toLowerCase();
        var estatus = $("env_filtro_estatus").value;
        var lista = estado.cotizaciones.filter(function (item) {
            if (!tieneContenido(item)) { return false; }
            var texto = [
                item.folio,
                item.cliente.nombre,
                item.cliente.telefono,
                item.destino.cp,
                item.destino.estado,
                item.destino.ciudad
            ].join(" ").toLowerCase();
            return (!q || texto.indexOf(q) >= 0) && (!estatus || item.estatus === estatus);
        });
        if (!lista.length) {
            $("env_bandeja").innerHTML = "<div class=\"env-empty d-flex align-items-center justify-content-center text-muted fs-8\">Sin cotizaciones en el filtro.</div>";
            return;
        }
        $("env_bandeja").innerHTML = lista.map(function (item) {
            var pendientes = pendientesCotizacion(item);
            var total = totales(item).total;
            var activo = item.id === estado.actualId ? " border-primary bg-light-primary" : "";
            var destino = [item.destino.ciudad, item.destino.estado, item.destino.cp].filter(Boolean).join(", ");
            return "<button class=\"btn w-100 text-start env-card p-3 mb-2" + activo + "\" type=\"button\" data-env-id=\"" + escapeHtml(item.id) + "\">" +
                "<div class=\"d-flex justify-content-between gap-2\">" +
                "<div class=\"fw-bold\">" + escapeHtml(item.cliente.nombre || "Sin nombre") + "</div>" +
                "<div class=\"fw-semibold\">" + dinero(total) + "</div>" +
                "</div>" +
                "<div class=\"text-muted fs-8\">" + escapeHtml(item.folio || item.id) + "</div>" +
                "<div class=\"text-muted fs-8\">" + escapeHtml(destino || "Destino pendiente") + "</div>" +
                "<div class=\"d-flex flex-wrap gap-1 mt-2\">" +
                badge(estatusLabel(item.estatus), item.estatus === "aceptada" ? "success" : (pendientes.length ? "warning" : "primary")) +
                (pendientes.length ? badge(pendientes.length + " pendientes", "danger") : badge("Completa", "success")) +
                "</div>" +
                "</button>";
        }).join("");
    }

    function renderKpis() {
        var utiles = estado.cotizaciones.filter(tieneContenido);
        var abiertas = utiles.filter(function (item) {
            return ["borrador", "datos_incompletos", "cotizando_envio"].indexOf(item.estatus) >= 0;
        }).length;
        var pendientes = utiles.reduce(function (total, item) {
            return total + item.partidas.reduce(function (acc, partida) {
                return acc + pendientesPartida(partida).length;
            }, 0);
        }, 0);
        $("env_kpi_abiertas").textContent = abiertas;
        $("env_kpi_catalogo").textContent = pendientes;
        $("env_kpi_enviadas").textContent = utiles.filter(function (item) { return item.estatus === "cotizacion_enviada"; }).length;
        $("env_kpi_aceptadas").textContent = utiles.filter(function (item) { return item.estatus === "aceptada"; }).length;
    }

    function renderPartidas() {
        var item = actual();
        if (!item) { return; }
        var body = $("env_partidas_body");
        $("env_partidas_empty").classList.toggle("d-none", item.partidas.length > 0);
        body.innerHTML = item.partidas.map(function (partida, index) {
            var pendientes = pendientesPartida(partida);
            var logistica = pendientes.length
                ? pendientes.map(function (p) { return badge(etiquetaPendiente(p), "warning"); }).join(" ")
                : badge("Completa", "success");
            return "<tr>" +
                "<td><div class=\"d-flex align-items-center gap-2\">" +
                "<img class=\"env-line-img\" src=\"" + escapeHtml(partida.imagen || placeholderImagen) + "\" alt=\"\">" +
                "<div><div class=\"fw-bold\">" + escapeHtml(partida.nombre || "Producto") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(partida.sku || "Sin SKU") + "</div></div>" +
                "</div></td>" +
                "<td class=\"text-end\"><input class=\"form-control form-control-sm form-control-solid text-end env-line-cantidad\" data-index=\"" + index + "\" value=\"" + escapeHtml(partida.cantidad) + "\" inputmode=\"decimal\"></td>" +
                "<td class=\"text-end\"><input class=\"form-control form-control-sm form-control-solid text-end env-line-precio\" data-index=\"" + index + "\" value=\"" + escapeHtml(partida.precio) + "\" inputmode=\"decimal\"></td>" +
                "<td><div class=\"d-flex flex-wrap gap-1\">" + logistica + "</div><div class=\"text-muted fs-8 mt-1\">" + medidasTexto(partida) + "</div></td>" +
                "<td class=\"text-end fw-bold\">" + dinero(numero(partida.cantidad) * numero(partida.precio)) + "</td>" +
                "<td class=\"text-end\"><button class=\"btn btn-sm btn-icon btn-light-danger env-line-remove\" data-index=\"" + index + "\" type=\"button\"><i class=\"bi bi-x-lg\"></i></button></td>" +
                "</tr>";
        }).join("");
    }

    function etiquetaPendiente(pendiente) {
        var labels = {
            producto_no_identificado: "No identificado",
            faltan_medidas_producto: "Faltan medidas",
            falta_peso_producto: "Falta peso",
            contacto_incompleto: "Contacto",
            destino_incompleto: "Destino",
            sin_productos: "Productos",
            paquete_pendiente_medicion: "Paquete"
        };
        return labels[pendiente] || pendiente;
    }

    function medidasTexto(partida) {
        var dim = [partida.largo_cm, partida.ancho_cm, partida.alto_cm].filter(function (v) { return numero(v) > 0; }).join(" x ");
        var peso = numero(partida.peso_kg) > 0 ? cantidad(partida.peso_kg, 2) + " kg" : "peso pendiente";
        return (dim ? dim + " cm" : "medidas pendientes") + " | " + peso;
    }

    function renderResumen() {
        var item = actual();
        if (!item) { return; }
        var total = totales(item);
        var pendientes = pendientesCotizacion(item);
        $("env_total_productos").textContent = dinero(total.productos);
        $("env_total_envio").textContent = dinero(total.envio_cliente);
        $("env_total").textContent = dinero(total.total);
        $("env_volumetrico").textContent = cantidad(total.volumetrico, 2) + " kg";
        $("env_resumen_chips").innerHTML = [
            badge(item.partidas.length + " partida(s)", "primary"),
            badge(pendientes.length + " pendiente(s)", pendientes.length ? "warning" : "success"),
            badge("Costo envio " + dinero(total.costo_envio), "info"),
            badge("Margen envio " + dinero(total.envio_cliente - total.costo_envio), total.envio_cliente >= total.costo_envio ? "success" : "danger")
        ].join(" ");
    }

    function renderTodo(mantenerFormulario) {
        if (!mantenerFormulario) {
            escribirFormulario();
        }
        renderBandeja();
        renderKpis();
        renderPartidas();
        renderResumen();
    }

    function mostrarAlerta(mensaje, tipo) {
        $("env_alerta").innerHTML = "<div class=\"alert alert-" + (tipo || "info") + " py-3 mb-0\">" + escapeHtml(mensaje) + "</div>";
        window.setTimeout(function () { $("env_alerta").innerHTML = ""; }, 3500);
    }

    function buscarProductos() {
        var q = $("env_producto_q").value.trim();
        var contenedor = $("env_producto_resultados");
        if (q.length < 2) {
            contenedor.classList.add("d-none");
            contenedor.innerHTML = "";
            return;
        }
        request("/ventas/envios_foraneos_buscar_skus_erp?q=" + encodeURIComponent(q) + "&limite=20").then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            var productos = response.depurar || [];
            if (!productos.length) {
                contenedor.classList.remove("d-none");
                contenedor.innerHTML = "<div class=\"p-3 text-muted fs-8\">Sin resultados. Puedes registrar producto no encontrado.</div>";
                return;
            }
            contenedor.classList.remove("d-none");
            contenedor.innerHTML = productos.map(function (item, index) {
                var pendientes = [];
                if (!numero(item.largo_cm) || !numero(item.ancho_cm) || !numero(item.alto_cm)) { pendientes.push("medidas"); }
                if (!numero(item.peso_kg)) { pendientes.push("peso"); }
                return "<div class=\"env-product-row p-3\" data-product-index=\"" + index + "\">" +
                    "<div class=\"d-flex gap-3\">" +
                    "<img class=\"env-product-img\" src=\"" + escapeHtml(imagen(item)) + "\" alt=\"\">" +
                    "<div class=\"flex-grow-1 min-w-0\">" +
                    "<div class=\"fw-bold text-truncate\">" + escapeHtml(item.nombre_sku || item.producto || item.sku) + "</div>" +
                    "<div class=\"text-muted fs-8\">" + escapeHtml(item.sku || "") + " | " + escapeHtml(item.producto || "") + "</div>" +
                    "<div class=\"d-flex flex-wrap gap-1 mt-2\">" +
                    badge(dinero(item.precio || 0), "primary") +
                    badge("Disp. " + cantidad(item.existencia_disponible || 0, 2), "info") +
                    (pendientes.length ? badge("Falta " + pendientes.join("/"), "warning") : badge("Logistica completa", "success")) +
                    "</div>" +
                    "</div></div></div>";
            }).join("");
            contenedor._productos = productos;
        }).catch(function (error) {
            contenedor.classList.remove("d-none");
            contenedor.innerHTML = "<div class=\"p-3 text-danger fs-8\">" + escapeHtml(error.message || "No fue posible buscar productos") + "</div>";
        });
    }

    function agregarProducto(item) {
        var cot = actual();
        if (!cot) { return; }
        cot.partidas.push({
            id_sku: item.id_sku || null,
            sku: item.sku || "",
            nombre: item.nombre_sku || item.producto || item.sku || "Producto",
            cantidad: "1",
            precio: String(item.precio || "0"),
            imagen: imagen(item),
            largo_cm: item.largo_cm || "",
            ancho_cm: item.ancho_cm || "",
            alto_cm: item.alto_cm || "",
            peso_kg: item.peso_kg || "",
            existencia_disponible: item.existencia_disponible || 0,
            manual: !!item.manual
        });
        $("env_producto_q").value = "";
        $("env_producto_resultados").classList.add("d-none");
        guardarActual(true);
        renderPartidas();
        renderResumen();
    }

    function agregarManual() {
        var nombre = window.prompt("Producto solicitado");
        if (!nombre) { return; }
        agregarProducto({nombre_sku: nombre, sku: "PENDIENTE", precio: 0, manual: true});
    }

    function eliminarActual() {
        var item = actual();
        if (!item) { return; }
        if (!window.confirm("Eliminar este borrador local?")) { return; }
        if (item.id_envio_foraneo) {
            $("env_estatus").value = "descartada";
            guardarActual(false);
            return;
        }
        estado.cotizaciones = estado.cotizaciones.filter(function (cot) { return cot.id !== item.id; });
        estado.actualId = estado.cotizaciones.length ? estado.cotizaciones[0].id : null;
        if (!estado.actualId) { nuevaCotizacion(false); }
        renderTodo();
        mostrarAlerta("Borrador local eliminado.", "success");
    }

    function duplicarActual() {
        guardarActual(true);
        var item = actual();
        if (!item) { return; }
        var copia = JSON.parse(JSON.stringify(item));
        copia.id = uid();
        copia.folio = copia.id;
        copia.estatus = "borrador";
        copia.fecha = new Date().toISOString();
        estado.cotizaciones.unshift(copia);
        estado.actualId = copia.id;
        renderTodo();
        mostrarAlerta("Borrador duplicado.", "success");
    }

    function resumenTexto() {
        guardarActual(true);
        var item = actual();
        var total = totales(item);
        var destino = [item.destino.ciudad, item.destino.estado, item.destino.cp].filter(Boolean).join(", ");
        var lineas = [
            "Cotizacion " + (item.folio || item.id),
            "Cliente: " + (item.cliente.nombre || "pendiente") + " | " + (item.cliente.telefono || "sin telefono"),
            "Destino: " + (destino || "pendiente"),
            "Productos:"
        ];
        item.partidas.forEach(function (partida) {
            lineas.push("- " + partida.cantidad + " x " + partida.nombre + " (" + (partida.sku || "sin SKU") + ") " + dinero(numero(partida.cantidad) * numero(partida.precio)));
        });
        lineas.push("Paquete: " + [item.paquete.largo, item.paquete.ancho, item.paquete.alto].join(" x ") + " cm | " + (item.paquete.peso || "0") + " kg");
        lineas.push("Envio: " + (item.envio.paqueteria || "pendiente") + " " + (item.envio.servicio || "") + " | " + dinero(item.envio.precio));
        lineas.push("Total estimado: " + dinero(total.total));
        return lineas.join("\n");
    }

    function copiarResumen() {
        var texto = resumenTexto();
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(texto).then(function () {
                mostrarAlerta("Resumen copiado.", "success");
            }).catch(function () {
                window.prompt("Resumen", texto);
            });
        } else {
            window.prompt("Resumen", texto);
        }
    }

    function eventos() {
        $("env_nuevo").addEventListener("click", function () { guardarActual(true); nuevaCotizacion(true); });
        $("env_guardar").addEventListener("click", function () { guardarActual(false); });
        $("env_eliminar").addEventListener("click", eliminarActual);
        $("env_duplicar").addEventListener("click", duplicarActual);
        $("env_producto_manual").addEventListener("click", agregarManual);
        $("env_vaciar_productos").addEventListener("click", function () {
            var item = actual();
            if (!item || !item.partidas.length || !window.confirm("Vaciar productos?")) { return; }
            item.partidas = [];
            guardarActual(true);
            renderPartidas();
            renderResumen();
        });
        $("env_marcar_enviada").addEventListener("click", function () {
            $("env_estatus").value = "cotizacion_enviada";
            guardarActual(false);
        });
        $("env_copiar_resumen").addEventListener("click", copiarResumen);
        $("env_producto_q").addEventListener("input", function () {
            window.clearTimeout(timerBusqueda);
            timerBusqueda = window.setTimeout(buscarProductos, 250);
        });
        $("env_producto_resultados").addEventListener("click", function (event) {
            var row = event.target.closest("[data-product-index]");
            if (!row) { return; }
            var productos = $("env_producto_resultados")._productos || [];
            var item = productos[Number(row.getAttribute("data-product-index"))];
            if (item) { agregarProducto(item); }
        });
        $("env_bandeja").addEventListener("click", function (event) {
            var row = event.target.closest("[data-env-id]");
            if (!row) { return; }
            guardarActual(true);
            estado.actualId = row.getAttribute("data-env-id");
            renderTodo();
        });
        $("env_partidas_body").addEventListener("input", function (event) {
            var item = actual();
            var index = Number(event.target.getAttribute("data-index"));
            if (!item || !item.partidas[index]) { return; }
            if (event.target.classList.contains("env-line-cantidad")) {
                item.partidas[index].cantidad = event.target.value;
            }
            if (event.target.classList.contains("env-line-precio")) {
                item.partidas[index].precio = event.target.value;
            }
            renderResumen();
        });
        $("env_partidas_body").addEventListener("click", function (event) {
            var btn = event.target.closest(".env-line-remove");
            var item = actual();
            if (!btn || !item) { return; }
            item.partidas.splice(Number(btn.getAttribute("data-index")), 1);
            renderPartidas();
            renderResumen();
        });
        ["env_filtro_q", "env_filtro_estatus"].forEach(function (id) {
            $(id).addEventListener("input", cargarEstado);
            $(id).addEventListener("change", cargarEstado);
        });
        [
            "env_estatus", "env_cliente_nombre", "env_cliente_telefono", "env_cliente_correo", "env_origen",
            "env_cp", "env_estado", "env_ciudad", "env_direccion", "env_paquete_largo", "env_paquete_ancho",
            "env_paquete_alto", "env_paquete_peso", "env_paquete_cantidad", "env_paqueteria", "env_servicio",
            "env_costo_envio", "env_precio_envio", "env_vigencia", "env_guia", "env_notas"
        ].forEach(function (id) {
            $(id).addEventListener("input", function () {
                leerFormulario();
                renderKpis();
                renderBandeja();
                renderResumen();
            });
            $(id).addEventListener("change", function () {
                leerFormulario();
                renderKpis();
                renderBandeja();
                renderResumen();
            });
        });
    }

    document.addEventListener("DOMContentLoaded", function () {
        cargarEstado();
        eventos();
        renderTodo();
    });
})();
