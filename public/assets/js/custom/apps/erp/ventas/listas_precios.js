"use strict";
(function () {
    var estado = {listas: [], lista: null, productos: [], productosVisibles: [], cambios: {}, revision: null};

    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-15
     * Proposito: operar Listas de precios desde Comercial con productos, margen y alcance.
     * Impacto: elimina la consola UAT visible y usa endpoints operativos con permisos finos.
     */
    function request(url) {
        return fetch(url, {credentials: "same-origin"}).then(function (response) { return response.json(); });
    }

    function postRequest(url, data) {
        return fetch(url, {
            method: "POST",
            headers: {"Content-Type": "application/x-www-form-urlencoded; charset=UTF-8", "X-CSRF-Token": window.ERP_CSRF_TOKEN || ""},
            body: new URLSearchParams(data).toString(),
            credentials: "same-origin"
        }).then(function (response) { return response.json(); });
    }

    function escapeHtml(value) {
        var div = document.createElement("div");
        div.textContent = value == null ? "" : String(value);
        return div.innerHTML;
    }

    function dinero(value) {
        return new Intl.NumberFormat("es-MX", {style: "currency", currency: "MXN"}).format(Number(value || 0));
    }

    function numero(value, decimales) {
        var n = Number(value);
        if (!isFinite(n)) {
            n = 0;
        }
        return n.toFixed(decimales || 2);
    }

    function fechaInput(value) {
        if (!value) {
            return "";
        }
        return String(value).substring(0, 10);
    }

    function mostrarAlerta(tipo, mensaje) {
        document.getElementById("lp_alerta").innerHTML = "<div class=\"alert alert-" + escapeHtml(tipo || "info") + " py-3 mb-4\">" + escapeHtml(mensaje || "") + "</div>";
    }

    function filtrosListas() {
        return {
            q: document.getElementById("lp_filtro_q").value.trim(),
            estatus: document.getElementById("lp_filtro_estatus").value,
            canal: document.getElementById("lp_filtro_canal").value,
            id_almacen: document.getElementById("lp_filtro_almacen").value.trim(),
            limite: "80"
        };
    }

    function cargarResumen() {
        request("/comercial/listas_precios_resumen_erp?" + new URLSearchParams(filtrosListas()).toString()).then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje);
            }
            var data = response.depurar || {};
            estado.listas = data.listas || [];
            renderSchema(data.schema || {});
            renderListas(estado.listas);
            document.getElementById("lp_kpi_total").textContent = Number((data.kpis || {}).listas_total || estado.listas.length);
            if (estado.lista && estado.lista.id_lista_precio) {
                marcarLista(estado.lista.id_lista_precio);
            }
        }).catch(mostrarError);
    }

    function renderSchema(schema) {
        if (!schema.listas || !schema.detalle) {
            mostrarAlerta("warning", "Falta esquema base de listas de precios.");
            return;
        }
        if (!schema.cliente_crm_columna) {
            mostrarAlerta("warning", "La asignacion CRM/lista requiere completar id_cliente_crm.");
            return;
        }
        document.getElementById("lp_alerta").innerHTML = "";
    }

    function renderListas(listas) {
        document.getElementById("lp_listas").innerHTML = (listas || []).map(function (item) {
            var canal = item.canal || "general";
            var almacen = item.id_almacen && Number(item.id_almacen) > 0 ? "Alm. " + item.id_almacen : "Todos";
            var rango = item.precio_min == null ? "Sin precios" : dinero(item.precio_min) + (Number(item.precio_max || 0) !== Number(item.precio_min || 0) ? " - " + dinero(item.precio_max) : "");
            return "<div class=\"lp-lista-item p-3 mb-2\" data-lp-lista=\"" + escapeHtml(item.id_lista_precio) + "\">" +
                "<div class=\"d-flex justify-content-between gap-2\"><div class=\"fw-bold\">" + escapeHtml(item.codigo || "") + "</div>" + badgeEstatus(item.estatus || "") + "</div>" +
                "<div class=\"text-muted fs-8 text-truncate\">" + escapeHtml(item.nombre || "") + "</div>" +
                "<div class=\"d-flex flex-wrap gap-1 mt-2\"><span class=\"badge badge-light-primary\">" + escapeHtml(canal) + "</span><span class=\"badge badge-light\">" + escapeHtml(almacen) + "</span><span class=\"badge badge-light\">" + escapeHtml(item.detalles_activos || 0) + " SKU</span></div>" +
                "<div class=\"text-muted fs-8 mt-2\">" + escapeHtml(rango) + "</div>" +
            "</div>";
        }).join("") || "<div class=\"text-center text-muted py-8\">Sin listas para los filtros actuales</div>";

        document.querySelectorAll("[data-lp-lista]").forEach(function (item) {
            item.addEventListener("click", function () {
                consultarLista(item.getAttribute("data-lp-lista"));
            });
        });
    }

    function badgeEstatus(estatus) {
        var clases = {activa: "badge-light-success", borrador: "badge-light", pausada: "badge-light-warning", cancelada: "badge-light-danger"};
        return "<span class=\"badge " + (clases[estatus] || "badge-light") + "\">" + escapeHtml(estatus || "-") + "</span>";
    }

    function marcarLista(idLista) {
        document.querySelectorAll("[data-lp-lista]").forEach(function (item) {
            item.classList.toggle("active", String(item.getAttribute("data-lp-lista")) === String(idLista));
        });
    }

    function consultarLista(idLista) {
        request("/comercial/listas_precios_consultar_erp?id_lista_precio=" + encodeURIComponent(idLista)).then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje);
            }
            var data = response.depurar || {};
            estado.lista = data.lista || null;
            llenarFormularioLista(estado.lista || {});
            renderAsignaciones(data.asignaciones || []);
            renderAsignacionesSegmentos(data.asignaciones_segmentos || [], (data.schema || {}).segmentos_listas);
            renderAuditoriaVacia();
            marcarLista(idLista);
            cargarSegmentosCrm();
            cargarRevision();
            cargarProductos();
        }).catch(mostrarError);
    }

    function llenarFormularioLista(lista) {
        document.getElementById("lp_lista_id").value = lista.id_lista_precio || "";
        document.getElementById("lp_lista_codigo").value = lista.codigo || "";
        document.getElementById("lp_lista_nombre").value = lista.nombre || "";
        document.getElementById("lp_lista_inicio").value = fechaInput(lista.fecha_inicio);
        document.getElementById("lp_lista_fin").value = fechaInput(lista.fecha_fin);
        document.getElementById("lp_lista_estatus").value = lista.estatus || "borrador";
        document.getElementById("lp_lista_observaciones").value = lista.observaciones || "";
        document.getElementById("lp_lista_canal").value = lista.canal || "general";
        document.getElementById("lp_lista_almacen").value = lista.id_almacen || "";
        document.getElementById("lp_preview_almacen").value = lista.id_almacen || document.getElementById("lp_preview_almacen").value || "";
        document.getElementById("lp_lista_prioridad").value = lista.prioridad || "100";
        document.getElementById("lp_seg_canal").value = lista.canal || "general";
        document.getElementById("lp_seg_almacen").value = lista.id_almacen || "";
        document.getElementById("lp_seg_prioridad").value = lista.prioridad || "100";
        document.getElementById("lp_seg_inicio").value = fechaInput(lista.fecha_inicio);
        document.getElementById("lp_seg_fin").value = fechaInput(lista.fecha_fin);
        document.getElementById("lp_titulo_lista").textContent = lista.id_lista_precio ? (lista.codigo + " | " + lista.nombre) : "Nueva lista";
        document.getElementById("lp_subtitulo_lista").textContent = lista.id_lista_precio ? ("Lista " + lista.id_lista_precio + " en " + (lista.estatus || "borrador")) : "Guarda el encabezado antes de capturar precios.";
        actualizarResumenAlcance();
        actualizarBotonesEstatus();
    }

    function nuevaLista() {
        estado.lista = null;
        estado.productos = [];
        estado.productosVisibles = [];
        estado.cambios = {};
        estado.revision = null;
        llenarFormularioLista({estatus: "borrador", prioridad: 100, canal: "general"});
        renderAsignaciones([]);
        renderAsignacionesSegmentos([], false);
        renderClientesResultados([]);
        cargarSegmentosCrm();
        limpiarSegmentoSeleccionado();
        renderRevisionVacia();
        actualizarContadorCambios();
        document.getElementById("lp_productos").innerHTML = "<tr><td colspan=\"7\" class=\"text-center text-muted py-8\">Guarda la lista para cargar productos.</td></tr>";
        document.querySelectorAll("[data-lp-lista]").forEach(function (item) { item.classList.remove("active"); });
    }

    function payloadLista() {
        return {
            id_lista_precio: document.getElementById("lp_lista_id").value,
            codigo: document.getElementById("lp_lista_codigo").value,
            nombre: document.getElementById("lp_lista_nombre").value,
            fecha_inicio: document.getElementById("lp_lista_inicio").value,
            fecha_fin: document.getElementById("lp_lista_fin").value,
            estatus: document.getElementById("lp_lista_estatus").value,
            canal: document.getElementById("lp_lista_canal").value,
            id_almacen: document.getElementById("lp_lista_almacen").value,
            prioridad: document.getElementById("lp_lista_prioridad").value,
            observaciones: document.getElementById("lp_lista_observaciones").value,
            motivo: document.getElementById("lp_lista_observaciones").value || "Operacion Comercial/Listas de precios"
        };
    }

    function aplicarPresetAlcance(canal) {
        document.getElementById("lp_lista_canal").value = canal;
        if (canal === "general") {
            document.getElementById("lp_lista_almacen").value = "";
            document.getElementById("lp_lista_prioridad").value = "500";
        } else if (canal === "pos") {
            document.getElementById("lp_lista_prioridad").value = "100";
        } else if (canal === "ecommerce") {
            document.getElementById("lp_lista_almacen").value = "";
            document.getElementById("lp_lista_prioridad").value = "300";
        } else if (canal === "mayoreo") {
            document.getElementById("lp_lista_prioridad").value = "50";
        }
        actualizarResumenAlcance();
    }

    function actualizarResumenAlcance() {
        var canal = document.getElementById("lp_lista_canal").value || "general";
        var almacen = document.getElementById("lp_lista_almacen").value.trim();
        var prioridad = Number(document.getElementById("lp_lista_prioridad").value || 100);
        var alcance = almacen ? "solo almacen " + almacen : "todos los almacenes";
        var mensajes = [];
        var tipo = "light";
        if (canal === "general") {
            mensajes.push("Lista base para " + alcance + "; conviene dejarla con menor prioridad que listas especificas.");
        } else if (canal === "pos") {
            mensajes.push("Lista para punto de venta en " + alcance + ".");
        } else if (canal === "pedido_tienda") {
            mensajes.push("Lista para pedidos levantados por tienda en " + alcance + ".");
        } else if (canal === "ecommerce") {
            mensajes.push("Lista para ecommerce; debe exponerse solo cuando el canal ecommerce este autorizado.");
            tipo = "warning";
        } else if (canal === "mayoreo") {
            mensajes.push("Lista para ventas de mayoreo; normalmente debe combinarse con cliente o segmento.");
            tipo = "info";
        }
        if (!isFinite(prioridad) || prioridad < 1 || prioridad > 9999) {
            mensajes.push("La prioridad debe estar entre 1 y 9999.");
            tipo = "danger";
        } else if (prioridad <= 50) {
            mensajes.push("Prioridad muy alta: esta lista ganara sobre reglas menos especificas.");
            if (tipo === "light") {
                tipo = "warning";
            }
        }
        document.querySelectorAll("[data-lp-alcance]").forEach(function (boton) {
            boton.className = boton.getAttribute("data-lp-alcance") === canal ? "btn btn-sm btn-light-primary" : "btn btn-sm btn-light";
        });
        var resumen = document.getElementById("lp_alcance_resumen");
        if (resumen) {
            resumen.className = "alert alert-" + tipo + " py-3 mt-3 mb-0 fs-7";
            resumen.innerHTML = mensajes.map(function (item) { return "<div>" + escapeHtml(item) + "</div>"; }).join("");
        }
        if (!document.getElementById("lp_preview_almacen").value.trim()) {
            document.getElementById("lp_preview_almacen").value = almacen;
        }
    }

    function guardarLista() {
        postRequest("/comercial/listas_precios_lista_guardar_operativo_erp", payloadLista()).then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje);
            }
            var data = response.depurar || {};
            estado.lista = data.lista || null;
            llenarFormularioLista(estado.lista || {});
            mostrarAlerta("success", response.mensaje || "Lista guardada");
            cargarResumen();
            cargarRevision();
            cargarProductos();
        }).catch(mostrarError);
    }

    function cambiarEstatusLista(estatus) {
        if (estatus === "activa" && Object.keys(estado.cambios || {}).length > 0) {
            mostrarAlerta("warning", "Guarda o descarta los cambios pendientes antes de activar la lista.");
            return;
        }
        if (estatus === "activa" && estado.revision && estado.revision.puede_activar === false) {
            mostrarAlerta("warning", "La lista tiene bloqueos de revision. Resuelvelos antes de activar.");
            return;
        }
        document.getElementById("lp_lista_estatus").value = estatus;
        guardarLista();
    }

    function filtrosProductos() {
        var solo = document.getElementById("lp_producto_solo").value;
        return {
            id_lista_precio: document.getElementById("lp_lista_id").value,
            q: document.getElementById("lp_producto_q").value.trim(),
            solo: solo === "modificados" ? "todos" : solo,
            limite: "160"
        };
    }

    function cargarProductos() {
        var idLista = document.getElementById("lp_lista_id").value;
        var solo = document.getElementById("lp_producto_solo").value;
        if (!idLista) {
            document.getElementById("lp_productos").innerHTML = "<tr><td colspan=\"7\" class=\"text-center text-muted py-8\">Guarda la lista para cargar productos.</td></tr>";
            actualizarResumenProductos([]);
            return;
        }
        if (solo === "modificados") {
            renderProductos(productosModificados());
            return;
        }
        request("/comercial/listas_precios_productos_erp?" + new URLSearchParams(filtrosProductos()).toString()).then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje);
            }
            estado.productos = (response.depurar || {}).productos || [];
            estado.cambios = {};
            actualizarContadorCambios();
            renderProductos(estado.productos);
        }).catch(mostrarError);
    }

    function renderProductos(productos) {
        productos = productos || [];
        estado.productosVisibles = productos;
        actualizarResumenProductos(productos);
        document.getElementById("lp_productos").innerHTML = (productos || []).map(function (item) {
            var precioLista = item.precio_lista == null ? "" : numero(item.precio_lista, 2);
            var margen = item.margen_estimado == null ? "-" : numero(item.margen_estimado, 2) + "%";
            var riesgo = item.riesgo_margen || {};
            var tipoBadge = riesgo.tipo === "danger" ? "badge-light-danger" : (riesgo.tipo === "warning" ? "badge-light-warning" : (riesgo.tipo === "success" ? "badge-light-success" : "badge-light"));
            return "<tr data-lp-producto=\"" + escapeHtml(item.id_sku) + "\">" +
                "<td><div class=\"fw-bold\">" + escapeHtml(item.sku || "") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.sku_nombre || item.producto || "") + "</div><div class=\"text-muted fs-8\">" + escapeHtml([item.marca, item.categoria].filter(Boolean).join(" / ")) + "</div></td>" +
                "<td><span class=\"badge badge-light\">" + escapeHtml(item.unidad_base || "-") + "</span></td>" +
                "<td class=\"text-end\">" + dinero(item.costo_referencia) + "</td>" +
                "<td class=\"text-end\">" + dinero(item.precio_general) + "</td>" +
                "<td class=\"text-end\"><input class=\"form-control form-control-sm form-control-solid text-end lp-price-input\" data-lp-precio=\"" + escapeHtml(item.id_sku) + "\" data-lp-original=\"" + escapeHtml(precioLista) + "\" value=\"" + escapeHtml(precioLista) + "\" placeholder=\"0.00\"></td>" +
                "<td class=\"text-end\"><div class=\"fw-semibold\" data-lp-margen=\"" + escapeHtml(item.id_sku) + "\">" + escapeHtml(margen) + "</div><div class=\"text-muted fs-8\" data-lp-utilidad=\"" + escapeHtml(item.id_sku) + "\">" + dinero(item.utilidad_estimada || 0) + "</div><span class=\"badge " + tipoBadge + "\" data-lp-riesgo=\"" + escapeHtml(item.id_sku) + "\">" + escapeHtml(riesgo.texto || "-") + "</span></td>" +
                "<td class=\"text-end\"><div class=\"d-flex justify-content-end gap-1\"><button class=\"btn btn-sm btn-light\" data-lp-preview-sku=\"" + escapeHtml(item.id_sku) + "\" type=\"button\"><i class=\"bi bi-calculator\"></i></button><button class=\"btn btn-sm btn-light-primary\" data-lp-guardar-precio=\"" + escapeHtml(item.id_sku) + "\" type=\"button\"><i class=\"bi bi-save\"></i></button>" + (item.id_lista_precio_detalle ? "<button class=\"btn btn-sm btn-light-danger\" data-lp-quitar-precio=\"" + escapeHtml(item.id_sku) + "\" type=\"button\"><i class=\"bi bi-x-circle\"></i></button>" : "") + "</div></td>" +
            "</tr>";
        }).join("") || "<tr><td colspan=\"7\" class=\"text-center text-muted py-8\">Sin productos para los filtros actuales</td></tr>";

        document.querySelectorAll("[data-lp-precio]").forEach(function (input) {
            input.addEventListener("input", function () {
                var idSku = input.getAttribute("data-lp-precio");
                recalcularMargenLocal(idSku);
                marcarCambioPrecio(idSku);
            });
        });
        document.querySelectorAll("[data-lp-guardar-precio]").forEach(function (boton) {
            boton.addEventListener("click", function () {
                guardarPrecioProducto(boton.getAttribute("data-lp-guardar-precio"));
            });
        });
        document.querySelectorAll("[data-lp-preview-sku]").forEach(function (boton) {
            boton.addEventListener("click", function () {
                document.getElementById("lp_preview_sku").value = boton.getAttribute("data-lp-preview-sku") || "";
                previsualizarPrecio();
            });
        });
        document.querySelectorAll("[data-lp-quitar-precio]").forEach(function (boton) {
            boton.addEventListener("click", function () {
                quitarPrecioProducto(boton.getAttribute("data-lp-quitar-precio"));
            });
        });
    }

    function productosModificados() {
        var cambios = estado.cambios || {};
        return (estado.productos || []).filter(function (item) {
            return cambios[String(item.id_sku)] != null;
        });
    }

    function actualizarResumenProductos(productos) {
        productos = productos || [];
        var cambios = Object.keys(estado.cambios || {}).length;
        var margenBajo = 0;
        var sinCosto = 0;
        productos.forEach(function (item) {
            var costo = Number(item.costo_referencia || 0);
            var input = document.querySelector("[data-lp-precio='" + item.id_sku + "']");
            var precio = input ? Number(input.value || 0) : Number(item.precio_lista || 0);
            var margen = item.margen_estimado == null ? null : Number(item.margen_estimado);
            if (precio > 0 && costo > 0) {
                margen = ((precio - costo) / precio) * 100;
            }
            if (costo <= 0) {
                sinCosto++;
            }
            if (precio > 0 && costo > 0 && (precio < costo || (margen != null && margen < 15))) {
                margenBajo++;
            }
        });
        var nodos = {
            lp_res_productos: productos.length,
            lp_res_margen_bajo: margenBajo,
            lp_res_sin_costo: sinCosto,
            lp_res_cambios: cambios
        };
        Object.keys(nodos).forEach(function (id) {
            var nodo = document.getElementById(id);
            if (nodo) {
                nodo.textContent = nodos[id];
            }
        });
    }

    function productoPorSku(idSku) {
        return (estado.productos || []).find(function (item) { return String(item.id_sku) === String(idSku); }) || null;
    }

    function recalcularMargenLocal(idSku) {
        var item = productoPorSku(idSku);
        var input = document.querySelector("[data-lp-precio='" + idSku + "']");
        if (!item || !input) {
            return;
        }
        var precio = Number(input.value || 0);
        var costo = Number(item.costo_referencia || 0);
        var margen = precio > 0 ? ((precio - costo) / precio) * 100 : null;
        var margenNodo = document.querySelector("[data-lp-margen='" + idSku + "']");
        var utilidadNodo = document.querySelector("[data-lp-utilidad='" + idSku + "']");
        var riesgoNodo = document.querySelector("[data-lp-riesgo='" + idSku + "']");
        margenNodo.textContent = margen == null ? "-" : numero(margen, 2) + "%";
        utilidadNodo.textContent = dinero(precio - costo);
        var riesgo = precio <= 0 ? ["Sin precio", "badge-light"] : (costo <= 0 ? ["Sin costo", "badge-light-warning"] : (precio < costo ? ["Perdida", "badge-light-danger"] : (margen < 15 ? ["Margen bajo", "badge-light-warning"] : ["Margen OK", "badge-light-success"])));
        riesgoNodo.className = "badge " + riesgo[1];
        riesgoNodo.textContent = riesgo[0];
    }

    function marcarCambioPrecio(idSku) {
        var item = productoPorSku(idSku);
        var input = document.querySelector("[data-lp-precio='" + idSku + "']");
        var row = document.querySelector("[data-lp-producto='" + idSku + "']");
        if (!item || !input) {
            return;
        }
        var valor = String(input.value || "").trim();
        var original = String(input.getAttribute("data-lp-original") || "").trim();
        if (valor !== "" && valor !== original) {
            estado.cambios[idSku] = {
                id_lista_precio_detalle: item.id_lista_precio_detalle || "",
                id_sku: item.id_sku,
                id_producto_erp: item.id_producto_erp,
                precio: valor,
                moneda: "MXN",
                estatus: "activo"
            };
            if (row) {
                row.classList.add("lp-row-dirty");
            }
        } else {
            delete estado.cambios[idSku];
            if (row) {
                row.classList.remove("lp-row-dirty");
            }
        }
        actualizarContadorCambios();
    }

    function actualizarContadorCambios() {
        var contador = document.getElementById("lp_cambios_count");
        if (contador) {
            contador.textContent = Object.keys(estado.cambios || {}).length;
        }
        var visibles = document.getElementById("lp_producto_solo").value === "modificados" ? productosModificados() : (estado.productosVisibles || estado.productos || []);
        actualizarResumenProductos(visibles);
        actualizarBotonesEstatus();
    }

    function aplicarMargenObjetivo() {
        var margen = Number(document.getElementById("lp_margen_objetivo").value || 0);
        if (!isFinite(margen) || margen <= 0 || margen >= 95) {
            mostrarAlerta("warning", "Captura un margen objetivo mayor a 0 y menor a 95.");
            return;
        }
        var aplicados = 0;
        (estado.productos || []).forEach(function (item) {
            var costo = Number(item.costo_referencia || 0);
            var input = document.querySelector("[data-lp-precio='" + item.id_sku + "']");
            if (costo <= 0 || !input) {
                return;
            }
            var precio = costo / (1 - (margen / 100));
            input.value = numero(Math.ceil(precio * 100) / 100, 2);
            recalcularMargenLocal(item.id_sku);
            marcarCambioPrecio(item.id_sku);
            aplicados++;
        });
        mostrarAlerta(aplicados ? "success" : "warning", aplicados ? ("Margen aplicado a " + aplicados + " producto(s) visibles.") : "No hay productos visibles con costo para aplicar margen.");
    }

    function copiarPreciosDesdeLista() {
        var idOrigen = document.getElementById("lp_copiar_lista_id").value.trim();
        var idActual = document.getElementById("lp_lista_id").value.trim();
        if (!idActual) {
            mostrarAlerta("warning", "Guarda o selecciona una lista destino antes de copiar precios.");
            return;
        }
        if (!idOrigen || idOrigen === idActual) {
            mostrarAlerta("warning", "Captura una lista origen distinta a la lista actual.");
            return;
        }
        request("/comercial/listas_precios_productos_erp?" + new URLSearchParams({
            id_lista_precio: idOrigen,
            q: document.getElementById("lp_producto_q").value.trim(),
            solo: "con_precio",
            limite: "300"
        }).toString()).then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje);
            }
            var origen = {};
            ((response.depurar || {}).productos || []).forEach(function (item) {
                if (item.precio_lista != null) {
                    origen[String(item.id_sku)] = item.precio_lista;
                }
            });
            var copiados = 0;
            (estado.productos || []).forEach(function (item) {
                var precio = origen[String(item.id_sku)];
                var input = document.querySelector("[data-lp-precio='" + item.id_sku + "']");
                if (precio == null || !input) {
                    return;
                }
                input.value = numero(precio, 2);
                recalcularMargenLocal(item.id_sku);
                marcarCambioPrecio(item.id_sku);
                copiados++;
            });
            mostrarAlerta(copiados ? "success" : "warning", copiados ? ("Se copiaron " + copiados + " precio(s) visibles desde la lista " + idOrigen + ".") : "La lista origen no tiene precios para los productos visibles.");
        }).catch(mostrarError);
    }

    function copiarPrecioGeneralVisibles() {
        if (!document.getElementById("lp_lista_id").value.trim()) {
            mostrarAlerta("warning", "Guarda o selecciona una lista destino antes de copiar precios.");
            return;
        }
        var copiados = 0;
        (estado.productos || []).forEach(function (item) {
            var precio = Number(item.precio_general || 0);
            var input = document.querySelector("[data-lp-precio='" + item.id_sku + "']");
            if (precio <= 0 || !input) {
                return;
            }
            input.value = numero(precio, 2);
            recalcularMargenLocal(item.id_sku);
            marcarCambioPrecio(item.id_sku);
            copiados++;
        });
        mostrarAlerta(copiados ? "success" : "warning", copiados ? ("Precio general copiado a " + copiados + " producto(s) visibles.") : "No hay productos visibles con precio general.");
    }

    function redondearPrecio(precio, modo) {
        precio = Number(precio || 0);
        if (!isFinite(precio) || precio <= 0) {
            return 0;
        }
        if (modo === "medio") {
            return Math.ceil(precio * 2) / 2;
        }
        if (modo === "noventa") {
            var entero = Math.floor(precio);
            var candidato = entero + 0.90;
            return candidato >= precio ? candidato : entero + 1.90;
        }
        return Math.ceil(precio);
    }

    function redondearPreciosVisibles() {
        if (!document.getElementById("lp_lista_id").value.trim()) {
            mostrarAlerta("warning", "Guarda o selecciona una lista destino antes de redondear precios.");
            return;
        }
        var modo = document.getElementById("lp_redondeo_modo").value || "entero";
        var redondeados = 0;
        (estado.productos || []).forEach(function (item) {
            var input = document.querySelector("[data-lp-precio='" + item.id_sku + "']");
            if (!input) {
                return;
            }
            var precio = Number(input.value || 0);
            if (precio <= 0) {
                return;
            }
            input.value = numero(redondearPrecio(precio, modo), 2);
            recalcularMargenLocal(item.id_sku);
            marcarCambioPrecio(item.id_sku);
            redondeados++;
        });
        mostrarAlerta(redondeados ? "success" : "warning", redondeados ? ("Redondeo aplicado a " + redondeados + " producto(s) visibles.") : "No hay precios visibles para redondear.");
    }

    function limpiarCambiosPendientes() {
        estado.cambios = {};
        document.querySelectorAll("[data-lp-precio]").forEach(function (input) {
            input.value = input.getAttribute("data-lp-original") || "";
            recalcularMargenLocal(input.getAttribute("data-lp-precio"));
        });
        document.querySelectorAll(".lp-row-dirty").forEach(function (row) {
            row.classList.remove("lp-row-dirty");
        });
        actualizarContadorCambios();
        mostrarAlerta("info", "Cambios pendientes descartados en pantalla.");
    }

    function guardarPrecioProducto(idSku) {
        var item = productoPorSku(idSku);
        var input = document.querySelector("[data-lp-precio='" + idSku + "']");
        var idLista = document.getElementById("lp_lista_id").value;
        if (!item || !input || !idLista) {
            mostrarAlerta("warning", "Selecciona y guarda una lista antes de capturar precios.");
            return;
        }
        postRequest("/comercial/listas_precios_detalle_guardar_operativo_erp", {
            id_lista_precio_detalle: item.id_lista_precio_detalle || "",
            id_lista_precio: idLista,
            id_sku: item.id_sku,
            id_producto_erp: item.id_producto_erp,
            precio: input.value,
            moneda: "MXN",
            estatus: "activo",
            motivo: "Captura operativa de precio por SKU"
        }).then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje);
            }
            mostrarAlerta("success", response.mensaje || "Precio guardado");
            cargarProductos();
            cargarRevision();
            cargarResumen();
        }).catch(mostrarError);
    }

    function quitarPrecioProducto(idSku) {
        var item = productoPorSku(idSku);
        var idLista = document.getElementById("lp_lista_id").value;
        if (!item || !item.id_lista_precio_detalle || !idLista) {
            mostrarAlerta("warning", "Este producto no tiene precio activo en la lista.");
            return;
        }
        if (!window.confirm("Quitar este precio de la lista?")) {
            return;
        }
        postRequest("/comercial/listas_precios_detalle_guardar_operativo_erp", {
            id_lista_precio_detalle: item.id_lista_precio_detalle,
            id_lista_precio: idLista,
            id_sku: item.id_sku,
            id_producto_erp: item.id_producto_erp,
            precio: item.precio_lista || item.precio_general || "0",
            moneda: "MXN",
            estatus: "cancelado",
            motivo: "Cancelacion operativa de precio SKU/lista"
        }).then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje);
            }
            mostrarAlerta("success", response.mensaje || "Precio cancelado");
            delete estado.cambios[idSku];
            actualizarContadorCambios();
            cargarProductos();
            cargarRevision();
            cargarResumen();
        }).catch(mostrarError);
    }

    function guardarCambiosLote() {
        var idLista = document.getElementById("lp_lista_id").value;
        var cambios = Object.keys(estado.cambios || {}).map(function (idSku) { return estado.cambios[idSku]; });
        if (!idLista) {
            mostrarAlerta("warning", "Selecciona y guarda una lista antes de guardar cambios.");
            return;
        }
        if (!cambios.length) {
            mostrarAlerta("info", "No hay precios modificados por guardar.");
            return;
        }
        postRequest("/comercial/listas_precios_detalles_lote_guardar_operativo_erp", {
            id_lista_precio: idLista,
            precios_json: JSON.stringify(cambios),
            motivo: "Guardado operativo por lote"
        }).then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje);
            }
            var data = response.depurar || {};
            mostrarAlerta(response.tipo === "warning" ? "warning" : "success", (response.mensaje || "Cambios guardados") + " (" + (data.guardados || 0) + "/" + (data.total || cambios.length) + ")");
            cargarProductos();
            cargarRevision();
            cargarResumen();
        }).catch(mostrarError);
    }

    function payloadAsignacion() {
        return {
            id_cliente_lista_precio: document.getElementById("lp_asig_id").value,
            id_lista_precio: document.getElementById("lp_lista_id").value,
            id_cliente_crm: document.getElementById("lp_asig_cliente").value,
            prioridad: document.getElementById("lp_asig_prioridad").value,
            estatus: "activo",
            motivo: "Asignacion operativa cliente CRM/lista"
        };
    }

    function guardarAsignacion() {
        if (!document.getElementById("lp_lista_id").value) {
            mostrarAlerta("warning", "Selecciona y guarda una lista antes de asignar clientes.");
            return;
        }
        postRequest("/comercial/listas_precios_asignacion_guardar_operativo_erp", payloadAsignacion()).then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje);
            }
            mostrarAlerta("success", response.mensaje || "Cliente asignado");
            consultarLista(document.getElementById("lp_lista_id").value);
            cargarResumen();
        }).catch(mostrarError);
    }

    function buscarClientesCrm() {
        var q = document.getElementById("lp_cliente_q").value.trim();
        if (!q) {
            mostrarAlerta("warning", "Captura nombre, codigo, telefono o correo para buscar cliente.");
            return;
        }
        request("/comercial/listas_precios_clientes_buscar_erp?" + new URLSearchParams({q: q}).toString()).then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje);
            }
            renderClientesResultados((response.depurar || {}).resultados || []);
        }).catch(mostrarError);
    }

    function renderClientesResultados(clientes) {
        var contenedor = document.getElementById("lp_clientes_resultados");
        if (!contenedor) {
            return;
        }
        contenedor.innerHTML = (clientes || []).map(function (item) {
            return "<div class=\"border rounded p-3 mb-2\">" +
                "<div class=\"d-flex justify-content-between gap-2 align-items-start\">" +
                    "<div><div class=\"fw-semibold\">" + escapeHtml(item.nombre_publico || "Cliente CRM") + "</div>" +
                    "<div class=\"text-muted fs-8\">" + escapeHtml(item.codigo_cliente || "") + (item.valor ? " | " + escapeHtml(item.valor) : "") + "</div></div>" +
                    "<button class=\"btn btn-sm btn-light-primary\" type=\"button\" data-lp-cliente-usar=\"" + escapeHtml(item.id_cliente_crm) + "\" data-lp-cliente-nombre=\"" + escapeHtml(item.nombre_publico || "") + "\">Usar</button>" +
                "</div>" +
            "</div>";
        }).join("") || "<div class=\"text-muted fs-7\">Sin coincidencias CRM.</div>";

        document.querySelectorAll("[data-lp-cliente-usar]").forEach(function (boton) {
            boton.addEventListener("click", function () {
                document.getElementById("lp_asig_id").value = "";
                document.getElementById("lp_asig_cliente").value = boton.getAttribute("data-lp-cliente-usar") || "";
                document.getElementById("lp_cliente_q").value = boton.getAttribute("data-lp-cliente-nombre") || "";
                mostrarAlerta("success", "Cliente CRM seleccionado para asignacion.");
            });
        });
    }

    function cargarSegmentosCrm() {
        var idLista = document.getElementById("lp_lista_id").value || "";
        request("/comercial/listas_precios_segmentos_crm_erp?" + new URLSearchParams({id_lista_precio: idLista, limite: "12"}).toString()).then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje);
            }
            renderSegmentosCrm(response.depurar || {});
        }).catch(function (error) {
            var contenedor = document.getElementById("lp_segmentos_crm");
            if (contenedor) {
                contenedor.innerHTML = "<div class=\"alert alert-warning py-3 mb-0\">" + escapeHtml(error.message || String(error)) + "</div>";
            }
        });
    }

    function renderSegmentosCrm(data) {
        var contenedor = document.getElementById("lp_segmentos_crm");
        if (!contenedor) {
            return;
        }
        var schema = data.schema || {};
        var segmentos = data.segmentos || [];
        var modo = data.modo || "planeado";
        var html = "<div class=\"alert " + (schema.segmentos_listas ? "alert-light-success" : "alert-light-warning") + " py-3 mb-3\">" +
            "<div class=\"fw-semibold\">" + (schema.segmentos_listas ? "Asignacion por segmento preparada" : "Asignacion por segmento pendiente de DDL") + "</div>" +
            "<div class=\"fs-8\">Modo " + escapeHtml(modo) + ": CRM es dueno de segmentos; Listas solo debe vincularlos con permiso y auditoria.</div>" +
        "</div>";
        html += segmentos.map(function (item) {
            var asignado = Number(item.asignaciones_lista || 0) > 0;
            return "<div class=\"border rounded p-3 mb-2\">" +
                "<div class=\"d-flex justify-content-between gap-2\"><div class=\"fw-semibold\">" + escapeHtml(item.nombre || item.codigo || "Segmento") + "</div>" +
                "<span class=\"badge " + (asignado ? "badge-light-success" : "badge-light") + "\">" + (asignado ? "vinculado" : "disponible") + "</span></div>" +
                "<div class=\"text-muted fs-8\">" + escapeHtml(item.codigo || "") + " | " + escapeHtml(item.tipo || "comercial") + " | " + escapeHtml(item.clientes_activos || 0) + " cliente(s)</div>" +
                (item.descripcion ? "<div class=\"fs-8 mt-1\">" + escapeHtml(item.descripcion) + "</div>" : "") +
                "<button class=\"btn btn-sm btn-light-primary mt-2\" type=\"button\" data-lp-segmento-usar=\"" + escapeHtml(item.id_segmento_crm || "") + "\" data-lp-segmento-nombre=\"" + escapeHtml(item.nombre || item.codigo || "") + "\"><i class=\"bi bi-check2-circle\"></i> Usar segmento</button>" +
            "</div>";
        }).join("");
        if (!segmentos.length) {
            html += "<div class=\"text-muted fs-7\">No hay segmentos CRM activos para mostrar.</div>";
        }
        contenedor.innerHTML = html;
        document.querySelectorAll("[data-lp-segmento-usar]").forEach(function (boton) {
            boton.addEventListener("click", function () {
                document.getElementById("lp_seg_id").value = boton.getAttribute("data-lp-segmento-usar") || "";
                mostrarAlerta("success", "Segmento seleccionado: " + (boton.getAttribute("data-lp-segmento-nombre") || ""));
                validarSegmentoLista();
            });
        });
    }

    function limpiarSegmentoSeleccionado() {
        ["lp_seg_asig_id", "lp_seg_id", "lp_segmento_dryrun"].forEach(function (id) {
            var el = document.getElementById(id);
            if (el) {
                if (id === "lp_segmento_dryrun") {
                    el.innerHTML = "";
                } else {
                    el.value = "";
                }
            }
        });
        if (document.getElementById("lp_seg_estatus")) {
            document.getElementById("lp_seg_estatus").value = "activo";
        }
    }

    function payloadSegmentoLista() {
        return {
            id_segmento_lista_precio: document.getElementById("lp_seg_asig_id").value,
            id_segmento_crm: document.getElementById("lp_seg_id").value,
            id_lista_precio: document.getElementById("lp_lista_id").value,
            canal: document.getElementById("lp_seg_canal").value,
            id_almacen: document.getElementById("lp_seg_almacen").value,
            prioridad: document.getElementById("lp_seg_prioridad").value,
            fecha_inicio: document.getElementById("lp_seg_inicio").value,
            fecha_fin: document.getElementById("lp_seg_fin").value,
            estatus: document.getElementById("lp_seg_estatus").value,
            motivo: "Operacion Comercial/Listas de precios por segmento CRM"
        };
    }

    function validarSegmentoLista() {
        if (!document.getElementById("lp_lista_id").value) {
            mostrarAlerta("warning", "Guarda o selecciona una lista antes de validar segmento.");
            return;
        }
        postRequest("/comercial/listas_precios_segmento_dryrun_erp", payloadSegmentoLista()).then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje);
            }
            renderSegmentoDryRun(response);
        }).catch(mostrarError);
    }

    function renderSegmentoDryRun(response) {
        var data = response.depurar || {};
        var bloqueos = data.bloqueos || [];
        var avisos = data.avisos || [];
        var tipo = bloqueos.length ? "warning" : "success";
        var html = "<div class=\"alert alert-" + tipo + " py-3 mb-0\"><div class=\"fw-bold\">" + escapeHtml(response.mensaje || "") + "</div>";
        if (bloqueos.length) {
            html += "<ul class=\"fs-8 ps-4 mt-2 mb-0\">" + bloqueos.map(function (item) { return "<li>" + escapeHtml(item) + "</li>"; }).join("") + "</ul>";
        }
        if (avisos.length) {
            html += "<div class=\"fs-8 mt-2\">" + avisos.map(function (item) { return escapeHtml(item); }).join(" | ") + "</div>";
        }
        html += "</div>";
        document.getElementById("lp_segmento_dryrun").innerHTML = html;
    }

    function guardarSegmentoLista() {
        if (!document.getElementById("lp_lista_id").value) {
            mostrarAlerta("warning", "Guarda o selecciona una lista antes de guardar segmento.");
            return;
        }
        postRequest("/comercial/listas_precios_segmento_guardar_operativo_erp", payloadSegmentoLista()).then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje);
            }
            renderSegmentoDryRun(response);
            cargarSegmentosCrm();
            cargarRevision();
            mostrarAlerta("success", response.mensaje || "Vinculo segmento/lista guardado.");
        }).catch(mostrarError);
    }

    function previsualizarPrecio() {
        var idSku = document.getElementById("lp_preview_sku").value.trim();
        var cantidad = document.getElementById("lp_preview_cantidad").value.trim() || "1";
        var idAlmacen = document.getElementById("lp_preview_almacen").value.trim() || document.getElementById("lp_lista_almacen").value.trim();
        var canal = document.getElementById("lp_lista_canal").value || "pos";
        var idCliente = document.getElementById("lp_asig_cliente").value.trim();
        if (!idSku) {
            mostrarAlerta("warning", "Selecciona SKU para previsualizar precio.");
            return;
        }
        if (!idAlmacen) {
            mostrarAlerta("warning", "Captura almacen de prueba para previsualizar como POS.");
            return;
        }
        postRequest("/comercial/listas_precios_precio_preview_erp", {
            id_almacen: idAlmacen,
            canal: canal === "general" ? "pos" : canal,
            id_cliente: idCliente,
            items: JSON.stringify([{id_sku: idSku, cantidad: cantidad}])
        }).then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje);
            }
            renderPreviewPrecio(response.depurar || {});
        }).catch(mostrarError);
    }

    function renderPreviewPrecio(data) {
        var partidas = data.partidas || [];
        var partida = partidas.length ? partidas[0] : null;
        if (!partida) {
            document.getElementById("lp_preview_resultado").innerHTML = "<div class=\"alert alert-warning py-3 mb-0\">Sin partida resuelta.</div>";
            return;
        }
        var bloqueos = data.bloqueos || partida.bloqueos || [];
        var tipo = bloqueos.length ? "warning" : "success";
        var html = "<div class=\"alert alert-" + tipo + " py-3 mb-3\"><div class=\"fw-bold\">" + escapeHtml(partida.sku || ("SKU " + partida.id_sku)) + "</div>" +
            "<div class=\"fs-8\">Origen: " + escapeHtml(partida.regla_precio_origen || "-") + " | Lista: " + escapeHtml(partida.lista_precio_snapshot || "-") + "</div></div>";
        html += "<div class=\"d-flex justify-content-between fs-7 mb-1\"><span>Precio base</span><strong>" + dinero(partida.precio_base || 0) + "</strong></div>";
        html += "<div class=\"d-flex justify-content-between fs-7 mb-1\"><span>Precio aplicado</span><strong>" + dinero(partida.precio_aplicado || 0) + "</strong></div>";
        html += "<div class=\"d-flex justify-content-between fs-7\"><span>Importe</span><strong>" + dinero(partida.importe || 0) + "</strong></div>";
        if (partida.id_lista_precio) {
            html += "<div class=\"text-muted fs-8 mt-2\">id_lista_precio " + escapeHtml(partida.id_lista_precio) + "</div>";
        }
        if (bloqueos.length) {
            html += "<ul class=\"fs-8 ps-4 mt-2 mb-0\">" + bloqueos.map(function (item) { return "<li>" + escapeHtml(item) + "</li>"; }).join("") + "</ul>";
        }
        document.getElementById("lp_preview_resultado").innerHTML = html;
    }

    function cargarRevision() {
        var idLista = document.getElementById("lp_lista_id").value;
        if (!idLista) {
            renderRevisionVacia();
            return;
        }
        request("/comercial/listas_precios_revision_erp?id_lista_precio=" + encodeURIComponent(idLista)).then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje);
            }
            renderRevision(response.depurar || {});
        }).catch(mostrarError);
    }

    function renderRevision(data) {
        estado.revision = data || null;
        var bloqueos = data.bloqueos || [];
        var avisos = data.avisos || [];
        var lista = data.lista || {};
        var margen = data.margen || {};
        var conflictos = data.conflictos || [];
        var tipo = bloqueos.length ? "danger" : (avisos.length ? "warning" : "success");
        var titulo = bloqueos.length ? "No lista para activar" : (avisos.length ? "Activable con avisos" : "Lista para activar");
        var almacen = lista.id_almacen && Number(lista.id_almacen) > 0 ? "Almacen " + lista.id_almacen : "Todos los almacenes";
        var rango = data.precio_min == null ? "Sin rango" : dinero(data.precio_min) + " - " + dinero(data.precio_max || data.precio_min);
        var html = "<div class=\"alert alert-" + tipo + " py-3 mb-3\"><div class=\"fw-bold\">" + titulo + "</div><div class=\"fs-8\">" + escapeHtml(lista.codigo || "") + (lista.nombre ? " | " + escapeHtml(lista.nombre) : "") + "</div></div>";
        html += "<div class=\"row g-2 mb-3\">" +
            "<div class=\"col-6\"><div class=\"border rounded p-2\"><div class=\"text-muted fs-9 text-uppercase\">Activos</div><div class=\"fw-bold\">" + escapeHtml(data.detalles_activos || 0) + " SKU</div></div></div>" +
            "<div class=\"col-6\"><div class=\"border rounded p-2\"><div class=\"text-muted fs-9 text-uppercase\">Clientes</div><div class=\"fw-bold\">" + escapeHtml(data.asignaciones_activas || 0) + "</div></div></div>" +
            "<div class=\"col-6\"><div class=\"border rounded p-2\"><div class=\"text-muted fs-9 text-uppercase\">Segmentos</div><div class=\"fw-bold\">" + escapeHtml(data.segmentos_activos || 0) + "</div></div></div>" +
            "<div class=\"col-12\"><div class=\"border rounded p-2\"><div class=\"text-muted fs-9 text-uppercase\">Alcance</div><div class=\"fw-semibold\">" + escapeHtml((lista.canal || "general") + " / " + almacen + " / prioridad " + (lista.prioridad || "100")) + "</div></div></div>" +
            "<div class=\"col-12\"><div class=\"border rounded p-2\"><div class=\"text-muted fs-9 text-uppercase\">Rango de precios</div><div class=\"fw-semibold\">" + escapeHtml(rango) + "</div></div></div>" +
        "</div>";
        html += "<div class=\"d-flex flex-wrap gap-2 mb-3\">" +
            "<span class=\"badge badge-light-danger\">Perdida " + escapeHtml(margen.perdida || 0) + "</span>" +
            "<span class=\"badge badge-light-warning\">Margen bajo " + escapeHtml(margen.margen_bajo || 0) + "</span>" +
            "<span class=\"badge badge-light\">Sin costo " + escapeHtml(margen.sin_costo || 0) + "</span>" +
            "<span class=\"badge badge-light-primary\">Conflictos " + escapeHtml(conflictos.length || 0) + "</span>" +
        "</div>";
        if (bloqueos.length) {
            html += "<div class=\"fw-semibold fs-8 text-uppercase text-muted mb-1\">Bloqueos</div><ul class=\"fs-8 ps-4 mb-3\">" + bloqueos.map(function (item) { return "<li>" + escapeHtml(item) + "</li>"; }).join("") + "</ul>";
        }
        if (avisos.length) {
            html += "<div class=\"fw-semibold fs-8 text-uppercase text-muted mb-1\">Avisos</div><ul class=\"fs-8 ps-4 mb-0\">" + avisos.map(function (item) { return "<li>" + escapeHtml(item) + "</li>"; }).join("") + "</ul>";
        }
        document.getElementById("lp_revision").innerHTML = html;
        actualizarBotonesEstatus();
    }

    function renderRevisionVacia() {
        estado.revision = null;
        document.getElementById("lp_revision").innerHTML = "<div class=\"text-muted fs-7\">Guarda o selecciona una lista para revisar activacion.</div>";
        actualizarBotonesEstatus();
    }

    function actualizarBotonesEstatus() {
        var activar = document.getElementById("lp_activar");
        var pausar = document.getElementById("lp_pausar");
        var idLista = document.getElementById("lp_lista_id").value;
        var estatus = document.getElementById("lp_lista_estatus").value;
        var pendientes = Object.keys(estado.cambios || {}).length;
        var revision = estado.revision || {};
        var bloqueos = revision.puede_activar === false || (revision.bloqueos || []).length > 0;
        var avisos = (revision.avisos || []).length > 0;
        if (activar) {
            activar.disabled = !idLista || pendientes > 0 || bloqueos || estatus === "activa";
            activar.className = avisos && !bloqueos ? "btn btn-warning" : "btn btn-success";
            activar.innerHTML = avisos && !bloqueos ? "<i class=\"bi bi-exclamation-triangle\"></i> Activar con avisos" : "<i class=\"bi bi-check2-circle\"></i> Activar";
        }
        if (pausar) {
            pausar.disabled = !idLista || estatus === "pausada" || estatus === "cancelada";
        }
    }

    function renderAsignaciones(asignaciones) {
        document.getElementById("lp_asignaciones").innerHTML = (asignaciones || []).map(function (item) {
            return "<div class=\"border rounded p-3 mb-2\">" +
                "<div class=\"d-flex justify-content-between gap-2\"><div class=\"fw-semibold\">" + escapeHtml(item.nombre_publico || ("Cliente " + (item.id_cliente_crm || "-"))) + "</div>" + badgeDetalle(item.estatus || "") + "</div>" +
                "<div class=\"text-muted fs-8 mb-2\">" + escapeHtml(item.codigo_cliente || "") + " Prioridad " + escapeHtml(item.prioridad || "1") + "</div>" +
                "<div class=\"d-flex gap-2\">" +
                    "<button class=\"btn btn-sm btn-light-primary\" type=\"button\" data-lp-asig-editar=\"" + escapeHtml(item.id_cliente_lista_precio || "") + "\" data-lp-asig-cliente=\"" + escapeHtml(item.id_cliente_crm || item.id_cliente || "") + "\" data-lp-asig-prioridad=\"" + escapeHtml(item.prioridad || "1") + "\" data-lp-asig-nombre=\"" + escapeHtml(item.nombre_publico || "") + "\"><i class=\"bi bi-pencil\"></i> Editar</button>" +
                    "<button class=\"btn btn-sm btn-light-danger\" type=\"button\" data-lp-asig-quitar=\"" + escapeHtml(item.id_cliente_lista_precio || "") + "\" data-lp-asig-cliente=\"" + escapeHtml(item.id_cliente_crm || item.id_cliente || "") + "\" data-lp-asig-prioridad=\"" + escapeHtml(item.prioridad || "1") + "\"><i class=\"bi bi-x-circle\"></i> Quitar</button>" +
                "</div>" +
            "</div>";
        }).join("") || "<div class=\"text-muted fs-7\">Sin clientes asignados.</div>";

        document.querySelectorAll("[data-lp-asig-editar]").forEach(function (boton) {
            boton.addEventListener("click", function () {
                document.getElementById("lp_asig_id").value = boton.getAttribute("data-lp-asig-editar") || "";
                document.getElementById("lp_asig_cliente").value = boton.getAttribute("data-lp-asig-cliente") || "";
                document.getElementById("lp_asig_prioridad").value = boton.getAttribute("data-lp-asig-prioridad") || "1";
                document.getElementById("lp_cliente_q").value = boton.getAttribute("data-lp-asig-nombre") || "";
                mostrarAlerta("info", "Asignacion cargada para edicion.");
            });
        });
        document.querySelectorAll("[data-lp-asig-quitar]").forEach(function (boton) {
            boton.addEventListener("click", function () {
                quitarAsignacionCliente(
                    boton.getAttribute("data-lp-asig-quitar") || "",
                    boton.getAttribute("data-lp-asig-cliente") || "",
                    boton.getAttribute("data-lp-asig-prioridad") || "1"
                );
            });
        });
    }

    function renderAsignacionesSegmentos(asignaciones, schemaDisponible) {
        var contenedor = document.getElementById("lp_asignaciones_segmentos");
        if (!contenedor) {
            return;
        }
        if (!schemaDisponible) {
            contenedor.innerHTML = "<div class=\"alert alert-light-warning py-3 mb-0\">Tabla puente de segmentos pendiente. Ya puedes validar, pero el guardado real espera DDL autorizado.</div>";
            return;
        }
        contenedor.innerHTML = (asignaciones || []).map(function (item) {
            var almacen = item.id_almacen && Number(item.id_almacen) > 0 ? ("Alm. " + item.id_almacen) : "Todos";
            return "<div class=\"border rounded p-3 mb-2\">" +
                "<div class=\"d-flex justify-content-between gap-2\"><div class=\"fw-semibold\">" + escapeHtml(item.nombre_segmento || item.codigo_segmento || ("Segmento " + (item.id_segmento_crm || "-"))) + "</div>" + badgeDetalle(item.estatus || "") + "</div>" +
                "<div class=\"text-muted fs-8 mb-2\">" + escapeHtml(item.codigo_segmento || "") + " | " + escapeHtml(item.canal || "general") + " | " + escapeHtml(almacen) + " | prioridad " + escapeHtml(item.prioridad || "100") + "</div>" +
                "<button class=\"btn btn-sm btn-light-primary\" type=\"button\" data-lp-seg-editar=\"" + escapeHtml(item.id_segmento_lista_precio || "") + "\" data-lp-seg-id=\"" + escapeHtml(item.id_segmento_crm || "") + "\" data-lp-seg-canal=\"" + escapeHtml(item.canal || "general") + "\" data-lp-seg-almacen=\"" + escapeHtml(item.id_almacen || "") + "\" data-lp-seg-prioridad=\"" + escapeHtml(item.prioridad || "100") + "\" data-lp-seg-estatus=\"" + escapeHtml(item.estatus || "activo") + "\" data-lp-seg-inicio=\"" + escapeHtml(fechaInput(item.fecha_inicio)) + "\" data-lp-seg-fin=\"" + escapeHtml(fechaInput(item.fecha_fin)) + "\"><i class=\"bi bi-pencil\"></i> Cargar</button>" +
            "</div>";
        }).join("") || "<div class=\"text-muted fs-7\">Sin segmentos vinculados.</div>";

        document.querySelectorAll("[data-lp-seg-editar]").forEach(function (boton) {
            boton.addEventListener("click", function () {
                document.getElementById("lp_seg_asig_id").value = boton.getAttribute("data-lp-seg-editar") || "";
                document.getElementById("lp_seg_id").value = boton.getAttribute("data-lp-seg-id") || "";
                document.getElementById("lp_seg_canal").value = boton.getAttribute("data-lp-seg-canal") || "general";
                document.getElementById("lp_seg_almacen").value = boton.getAttribute("data-lp-seg-almacen") || "";
                document.getElementById("lp_seg_prioridad").value = boton.getAttribute("data-lp-seg-prioridad") || "100";
                document.getElementById("lp_seg_estatus").value = boton.getAttribute("data-lp-seg-estatus") || "activo";
                document.getElementById("lp_seg_inicio").value = boton.getAttribute("data-lp-seg-inicio") || "";
                document.getElementById("lp_seg_fin").value = boton.getAttribute("data-lp-seg-fin") || "";
                validarSegmentoLista();
            });
        });
    }

    function quitarAsignacionCliente(idAsignacion, idCliente, prioridad) {
        var idLista = document.getElementById("lp_lista_id").value;
        if (!idLista || !idAsignacion || !idCliente) {
            mostrarAlerta("warning", "No se pudo identificar la asignacion a quitar.");
            return;
        }
        if (!window.confirm("Quitar este cliente de la lista de precios?")) {
            return;
        }
        postRequest("/comercial/listas_precios_asignacion_guardar_operativo_erp", {
            id_cliente_lista_precio: idAsignacion,
            id_lista_precio: idLista,
            id_cliente_crm: idCliente,
            prioridad: prioridad || "1",
            estatus: "cancelado",
            motivo: "Cancelacion operativa de asignacion cliente/lista"
        }).then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje);
            }
            mostrarAlerta("success", response.mensaje || "Asignacion cancelada");
            document.getElementById("lp_asig_id").value = "";
            document.getElementById("lp_asig_cliente").value = "";
            consultarLista(idLista);
            cargarResumen();
        }).catch(mostrarError);
    }

    function badgeDetalle(estatus) {
        return "<span class=\"badge " + (estatus === "activo" ? "badge-light-success" : "badge-light") + "\">" + escapeHtml(estatus || "-") + "</span>";
    }

    function cargarAuditoria() {
        var idLista = document.getElementById("lp_lista_id").value;
        if (!idLista) {
            renderAuditoriaVacia();
            return;
        }
        request("/comercial/listas_precios_auditoria_erp?" + new URLSearchParams({id_lista_precio: idLista, limite: "20"}).toString()).then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje);
            }
            renderAuditoria((response.depurar || {}).eventos || []);
        }).catch(mostrarError);
    }

    function renderAuditoria(eventos) {
        document.getElementById("lp_auditoria").innerHTML = (eventos || []).map(function (item) {
            return "<div class=\"border rounded p-3 mb-2\">" +
                "<div class=\"fw-semibold\">" + escapeHtml(item.accion || "-") + "</div>" +
                "<div class=\"text-muted fs-8\">" + escapeHtml(item.fecha_registro || "") + "</div>" +
                "<div class=\"fs-8\">" + escapeHtml(item.resumen || "") + "</div>" +
            "</div>";
        }).join("") || "<div class=\"text-muted fs-7\">Sin eventos para esta lista.</div>";
    }

    function renderAuditoriaVacia() {
        document.getElementById("lp_auditoria").innerHTML = "<div class=\"text-muted fs-7\">Sin eventos cargados.</div>";
    }

    function mostrarError(error) {
        mostrarAlerta("danger", error.message || String(error));
    }

    document.addEventListener("DOMContentLoaded", function () {
        document.getElementById("lp_recargar").addEventListener("click", function () { cargarResumen(); cargarProductos(); });
        document.getElementById("lp_nueva").addEventListener("click", nuevaLista);
        document.getElementById("lp_filtrar").addEventListener("click", cargarResumen);
        document.getElementById("lp_guardar_lista").addEventListener("click", guardarLista);
        document.getElementById("lp_activar").addEventListener("click", function () { cambiarEstatusLista("activa"); });
        document.getElementById("lp_pausar").addEventListener("click", function () { cambiarEstatusLista("pausada"); });
        document.getElementById("lp_productos_buscar").addEventListener("click", cargarProductos);
        document.getElementById("lp_aplicar_margen").addEventListener("click", aplicarMargenObjetivo);
        document.getElementById("lp_copiar_general").addEventListener("click", copiarPrecioGeneralVisibles);
        document.getElementById("lp_redondear").addEventListener("click", redondearPreciosVisibles);
        document.getElementById("lp_copiar_lista").addEventListener("click", copiarPreciosDesdeLista);
        document.getElementById("lp_limpiar_cambios").addEventListener("click", limpiarCambiosPendientes);
        document.getElementById("lp_guardar_cambios").addEventListener("click", guardarCambiosLote);
        document.getElementById("lp_cliente_buscar").addEventListener("click", buscarClientesCrm);
        document.getElementById("lp_segmentos_recargar").addEventListener("click", cargarSegmentosCrm);
        document.getElementById("lp_seg_validar").addEventListener("click", validarSegmentoLista);
        document.getElementById("lp_seg_guardar").addEventListener("click", guardarSegmentoLista);
        document.getElementById("lp_guardar_asig").addEventListener("click", guardarAsignacion);
        document.getElementById("lp_preview_btn").addEventListener("click", previsualizarPrecio);
        document.getElementById("lp_revision_btn").addEventListener("click", cargarRevision);
        document.getElementById("lp_auditoria_btn").addEventListener("click", cargarAuditoria);
        document.querySelectorAll("[data-lp-alcance]").forEach(function (boton) {
            boton.addEventListener("click", function () {
                aplicarPresetAlcance(boton.getAttribute("data-lp-alcance") || "general");
            });
        });
        ["lp_lista_canal", "lp_lista_almacen", "lp_lista_prioridad"].forEach(function (id) {
            document.getElementById(id).addEventListener("input", actualizarResumenAlcance);
            document.getElementById(id).addEventListener("change", actualizarResumenAlcance);
        });
        document.getElementById("lp_filtro_q").addEventListener("keyup", function (event) {
            if (event.key === "Enter") { cargarResumen(); }
        });
        document.getElementById("lp_producto_q").addEventListener("keyup", function (event) {
            if (event.key === "Enter") { cargarProductos(); }
        });
        document.getElementById("lp_cliente_q").addEventListener("keyup", function (event) {
            if (event.key === "Enter") { buscarClientesCrm(); }
        });
        nuevaLista();
        cargarResumen();
    });
})();
