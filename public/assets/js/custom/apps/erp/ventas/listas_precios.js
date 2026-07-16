"use strict";
(function () {
    var estado = {listas: [], lista: null, productos: [], cambios: {}};

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
            renderAuditoriaVacia();
            marcarLista(idLista);
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
        document.getElementById("lp_lista_prioridad").value = lista.prioridad || "100";
        document.getElementById("lp_titulo_lista").textContent = lista.id_lista_precio ? (lista.codigo + " | " + lista.nombre) : "Nueva lista";
        document.getElementById("lp_subtitulo_lista").textContent = lista.id_lista_precio ? ("Lista " + lista.id_lista_precio + " en " + (lista.estatus || "borrador")) : "Guarda el encabezado antes de capturar precios.";
    }

    function nuevaLista() {
        estado.lista = null;
        estado.productos = [];
        estado.cambios = {};
        llenarFormularioLista({estatus: "borrador", prioridad: 100, canal: "general"});
        renderAsignaciones([]);
        renderClientesResultados([]);
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
        document.getElementById("lp_lista_estatus").value = estatus;
        guardarLista();
    }

    function filtrosProductos() {
        return {
            id_lista_precio: document.getElementById("lp_lista_id").value,
            q: document.getElementById("lp_producto_q").value.trim(),
            solo: document.getElementById("lp_producto_solo").value,
            limite: "160"
        };
    }

    function cargarProductos() {
        var idLista = document.getElementById("lp_lista_id").value;
        if (!idLista) {
            document.getElementById("lp_productos").innerHTML = "<tr><td colspan=\"7\" class=\"text-center text-muted py-8\">Guarda la lista para cargar productos.</td></tr>";
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
                "<td class=\"text-end\"><button class=\"btn btn-sm btn-light-primary\" data-lp-guardar-precio=\"" + escapeHtml(item.id_sku) + "\" type=\"button\"><i class=\"bi bi-save\"></i></button></td>" +
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
        var bloqueos = data.bloqueos || [];
        var avisos = data.avisos || [];
        var tipo = bloqueos.length ? "danger" : (avisos.length ? "warning" : "success");
        var titulo = bloqueos.length ? "No lista para activar" : (avisos.length ? "Activable con avisos" : "Lista para activar");
        var html = "<div class=\"alert alert-" + tipo + " py-3 mb-3\"><div class=\"fw-bold\">" + titulo + "</div><div class=\"fs-8\">" + escapeHtml(data.detalles_activos || 0) + " producto(s) activos</div></div>";
        if (bloqueos.length) {
            html += "<div class=\"fw-semibold fs-8 text-uppercase text-muted mb-1\">Bloqueos</div><ul class=\"fs-8 ps-4 mb-3\">" + bloqueos.map(function (item) { return "<li>" + escapeHtml(item) + "</li>"; }).join("") + "</ul>";
        }
        if (avisos.length) {
            html += "<div class=\"fw-semibold fs-8 text-uppercase text-muted mb-1\">Avisos</div><ul class=\"fs-8 ps-4 mb-0\">" + avisos.map(function (item) { return "<li>" + escapeHtml(item) + "</li>"; }).join("") + "</ul>";
        }
        document.getElementById("lp_revision").innerHTML = html;
    }

    function renderRevisionVacia() {
        document.getElementById("lp_revision").innerHTML = "<div class=\"text-muted fs-7\">Guarda o selecciona una lista para revisar activacion.</div>";
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
        document.getElementById("lp_guardar_cambios").addEventListener("click", guardarCambiosLote);
        document.getElementById("lp_cliente_buscar").addEventListener("click", buscarClientesCrm);
        document.getElementById("lp_guardar_asig").addEventListener("click", guardarAsignacion);
        document.getElementById("lp_revision_btn").addEventListener("click", cargarRevision);
        document.getElementById("lp_auditoria_btn").addEventListener("click", cargarAuditoria);
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
