"use strict";
(function () {
    var estado = {listas: [], lista: null, productos: [], productosVisibles: [], cambios: {}, seleccionados: {}, sugeridos: {}, importacion: null, comparacion: null, revision: null, asignacionesClientes: [], asignacionesSegmentos: [], segmentosListasDisponible: false, segmentosPorId: {}};

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

    function actualizarFlujoOperativo(pasoActivo) {
        var idLista = document.getElementById("lp_lista_id") ? document.getElementById("lp_lista_id").value : "";
        var codigo = document.getElementById("lp_lista_codigo") ? document.getElementById("lp_lista_codigo").value.trim() : "";
        var nombre = document.getElementById("lp_lista_nombre") ? document.getElementById("lp_lista_nombre").value.trim() : "";
        var canal = document.getElementById("lp_lista_canal") ? document.getElementById("lp_lista_canal").value : "general";
        var prioridad = Number(document.getElementById("lp_lista_prioridad") ? document.getElementById("lp_lista_prioridad").value || 0 : 0);
        var productos = estado.productos || [];
        var conPrecio = productos.filter(function (item) { return Number(item.precio_lista || 0) > 0; }).length;
        var cambios = Object.keys(estado.cambios || {}).length;
        var asignaciones = (estado.asignacionesClientes || []).length + (estado.asignacionesSegmentos || []).length;
        var revision = estado.revision || null;
        var pasos = {
            encabezado: {
                listo: !!idLista && !!codigo && !!nombre,
                texto: idLista ? (codigo + " guardada") : "Captura codigo y nombre."
            },
            productos: {
                listo: conPrecio > 0 || cambios > 0,
                texto: productos.length ? (conPrecio + " con precio, " + cambios + " pendiente(s)") : "Carga SKUs y precios."
            },
            alcance: {
                listo: !!canal && isFinite(prioridad) && prioridad > 0,
                texto: (canal || "general") + " | prioridad " + (prioridad || "-")
            },
            asignacion: {
                listo: asignaciones > 0 || canal === "general",
                texto: asignaciones ? (asignaciones + " vinculo(s)") : "General o pendiente de segmento."
            },
            revision: {
                listo: !!revision && revision.puede_activar !== false && cambios === 0,
                texto: revision ? (revision.puede_activar === false ? "Con bloqueos por resolver." : "Lista revisada.") : "Prevalida antes de activar."
            }
        };
        Object.keys(pasos).forEach(function (clave) {
            var boton = document.querySelector("[data-lp-flujo='" + clave + "']");
            var texto = document.getElementById("lp_flujo_" + clave);
            if (boton) {
                boton.classList.toggle("is-ready", !!pasos[clave].listo);
                boton.classList.toggle("is-active", pasoActivo === clave);
            }
            if (texto) {
                texto.textContent = pasos[clave].texto;
            }
        });
        var listos = Object.keys(pasos).filter(function (clave) { return pasos[clave].listo; }).length;
        var estadoNodo = document.getElementById("lp_flujo_estado");
        if (estadoNodo) {
            estadoNodo.textContent = idLista ? (listos + "/5 listo") : "Sin lista";
            estadoNodo.className = listos >= 5 ? "badge badge-light-success" : (idLista ? "badge badge-light-primary" : "badge badge-light");
        }
    }

    function activarFlujoOperativo() {
        document.querySelectorAll("[data-lp-flujo]").forEach(function (boton) {
            boton.addEventListener("click", function () {
                var destino = document.querySelector(boton.getAttribute("data-lp-scroll") || "");
                var paso = boton.getAttribute("data-lp-flujo") || "";
                cambiarTabEditor(tabDesdePasoFlujo(paso));
                actualizarFlujoOperativo(paso);
                if (destino) {
                    destino.scrollIntoView({behavior: "smooth", block: "center"});
                    if (typeof destino.focus === "function") {
                        setTimeout(function () { destino.focus(); }, 250);
                    }
                }
            });
        });
    }

    function activarTabsEditor() {
        document.querySelectorAll("[data-lp-editor-tab]").forEach(function (boton) {
            boton.addEventListener("click", function () {
                cambiarTabEditor(boton.getAttribute("data-lp-editor-tab") || "encabezado");
            });
        });
    }

    function activarTabsProductos() {
        document.querySelectorAll("[data-lp-product-tab]").forEach(function (boton) {
            boton.addEventListener("click", function () {
                cambiarTabProducto(boton.getAttribute("data-lp-product-tab") || "captura");
            });
        });
    }

    function cambiarTabProducto(tab, activarEditor) {
        tab = tab || "captura";
        document.querySelectorAll("[data-lp-product-tab]").forEach(function (boton) {
            boton.classList.toggle("is-active", boton.getAttribute("data-lp-product-tab") === tab);
        });
        document.querySelectorAll("[data-lp-product-panel]").forEach(function (panel) {
            panel.hidden = panel.getAttribute("data-lp-product-panel") !== tab;
        });
        if (activarEditor !== false) {
            cambiarTabEditor("productos");
        }
    }

    function cambiarTabEditor(tab) {
        tab = tab || "encabezado";
        document.querySelectorAll("[data-lp-editor-tab]").forEach(function (boton) {
            boton.classList.toggle("is-active", boton.getAttribute("data-lp-editor-tab") === tab);
        });
        document.querySelectorAll("[data-lp-editor-panel]").forEach(function (panel) {
            panel.hidden = panel.getAttribute("data-lp-editor-panel") !== tab;
        });
        var estadoTab = document.getElementById("lp_editor_tab_estado");
        if (estadoTab) {
            estadoTab.textContent = etiquetaTabEditor(tab);
        }
    }

    function etiquetaTabEditor(tab) {
        var etiquetas = {
            encabezado: "Encabezado",
            productos: "Productos",
            alcance: "Alcance",
            asignacion: "Clientes/Segmentos",
            revision: "Revision"
        };
        return etiquetas[tab] || "Encabezado";
    }

    function tabDesdePasoFlujo(paso) {
        var mapa = {
            encabezado: "encabezado",
            productos: "productos",
            alcance: "alcance",
            asignacion: "asignacion",
            revision: "revision"
        };
        return mapa[paso] || "encabezado";
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

    function cargarFase1Readiness() {
        var contenedor = document.getElementById("lp_fase1_readiness");
        if (contenedor) {
            contenedor.innerHTML = "<div class=\"text-muted fs-7\">Validando arranque...</div>";
        }
        request("/comercial/listas_precios_fase1_readiness_erp").then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje);
            }
            renderFase1Readiness(response.depurar || {}, response.mensaje || "");
        }).catch(mostrarError);
    }

    function renderFase1Readiness(data, mensaje) {
        var contenedor = document.getElementById("lp_fase1_readiness");
        var badge = document.getElementById("lp_fase1_estado");
        if (!contenedor) {
            return;
        }
        var listo = !!data.puede_piloto_pos;
        if (badge) {
            badge.textContent = listo ? "Listo piloto POS" : "Con bloqueos";
            badge.className = listo ? "badge badge-light-success" : "badge badge-light-warning";
        }
        var checks = data.checks || [];
        var kpis = data.kpis || {};
        var html = "<div class=\"row g-3\">" +
            "<div class=\"col-xl-3 col-md-6\"><div class=\"border rounded p-3 h-100\"><div class=\"text-muted fs-9 text-uppercase\">Estado</div><div class=\"fw-bold " + (listo ? "text-success" : "text-warning") + "\">" + escapeHtml(mensaje || data.estado || "-") + "</div></div></div>" +
            "<div class=\"col-xl-3 col-md-6\"><div class=\"border rounded p-3 h-100\"><div class=\"text-muted fs-9 text-uppercase\">Listas activas</div><div class=\"fw-bold\">" + escapeHtml(kpis.listas_activas || 0) + " / " + escapeHtml(kpis.listas_total || 0) + "</div></div></div>" +
            "<div class=\"col-xl-3 col-md-6\"><div class=\"border rounded p-3 h-100\"><div class=\"text-muted fs-9 text-uppercase\">Precios activos</div><div class=\"fw-bold\">" + escapeHtml(kpis.detalles_activos || 0) + "</div></div></div>" +
            "<div class=\"col-xl-3 col-md-6\"><div class=\"border rounded p-3 h-100\"><div class=\"text-muted fs-9 text-uppercase\">Ecommerce</div><div class=\"fw-bold text-muted\">" + (data.puede_ecommerce ? "Listo" : "Pendiente contrato") + "</div></div></div>" +
        "</div>";
        html += "<div class=\"row g-3 mt-1\">" + checks.map(function (check) {
            var clase = check.ok ? "badge-light-success" : (check.tipo === "pendiente" ? "badge-light" : (check.tipo === "recomendado" ? "badge-light-warning" : "badge-light-danger"));
            var texto = check.ok ? "OK" : (check.tipo === "pendiente" ? "Fase 2" : (check.tipo === "recomendado" ? "Recomendado" : "Bloquea"));
            return "<div class=\"col-xl-3 col-md-4 col-sm-6\"><div class=\"border rounded p-3 h-100\">" +
                "<div class=\"d-flex justify-content-between gap-2 align-items-start\"><div class=\"fw-semibold\">" + escapeHtml(check.texto || check.id || "") + "</div><span class=\"badge " + clase + "\">" + texto + "</span></div>" +
            "</div></div>";
        }).join("") + "</div>";
        if ((data.bloqueos || []).length) {
            html += "<div class=\"alert alert-light-danger py-3 mt-3 mb-0\"><div class=\"fw-semibold mb-1\">Bloqueos antes de piloto</div>" + renderListaSimple(data.bloqueos) + "</div>";
        } else {
            html += "<div class=\"alert alert-light-success py-3 mt-3 mb-0\"><div class=\"fw-semibold\">Puedes iniciar piloto POS controlado.</div><div class=\"fs-8\">Ecommerce y granel/presentaciones siguen como fases posteriores.</div></div>";
        }
        if ((data.recomendaciones || []).length || (data.pendientes_fase_2 || []).length) {
            html += "<div class=\"alert alert-light py-3 mt-3 mb-0\"><div class=\"fw-semibold mb-1\">Siguientes notas</div>" + renderListaSimple((data.recomendaciones || []).concat(data.pendientes_fase_2 || [])) + "</div>";
        }
        if ((data.siguiente_uat || []).length) {
            html += "<div class=\"mt-3\"><div class=\"fw-semibold fs-7 mb-1\">UAT operativo minimo</div>" + renderListaSimple(data.siguiente_uat) + "</div>";
        }
        contenedor.innerHTML = html;
    }

    function renderListaSimple(items) {
        return "<ul class=\"mb-0 ps-4\">" + (items || []).map(function (item) {
            return "<li>" + escapeHtml(item) + "</li>";
        }).join("") + "</ul>";
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
        actualizarFlujoOperativo("encabezado");
    }

    function nuevaLista() {
        estado.lista = null;
        estado.productos = [];
        estado.productosVisibles = [];
        estado.cambios = {};
        estado.seleccionados = {};
        estado.sugeridos = {};
        estado.importacion = null;
        estado.comparacion = null;
        estado.revision = null;
        llenarFormularioLista({estatus: "borrador", prioridad: 100, canal: "general"});
        renderAsignaciones([]);
        renderAsignacionesSegmentos([], false);
        renderClientesResultados([]);
        renderPreparacionSegmentos({crm_segmentos: false, crm_segmentos_rel: false, segmentos_listas: false}, "sin_validar");
        cargarSegmentosCrm();
        limpiarSegmentoSeleccionado();
        renderRevisionVacia();
        renderComparacionListas();
        actualizarContadorCambios();
        document.getElementById("lp_productos").innerHTML = "<tr><td colspan=\"8\" class=\"text-center text-muted py-8\">Guarda la lista para cargar productos.</td></tr>";
        actualizarCheckboxSeleccionTodos();
        document.querySelectorAll("[data-lp-lista]").forEach(function (item) { item.classList.remove("active"); });
        actualizarFlujoOperativo("encabezado");
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
        actualizarGuiaPrioridad(prioridad, canal, almacen);
        actualizarFlujoOperativo("alcance");
    }

    function actualizarGuiaPrioridad(prioridad, canal, almacen) {
        var nodo = document.getElementById("lp_prioridad_resumen");
        if (!nodo) {
            return;
        }
        var tipo = "primary";
        var texto = "Dentro del mismo nivel, menor prioridad gana.";
        if (!isFinite(prioridad) || prioridad < 1 || prioridad > 9999) {
            tipo = "danger";
            texto = "Prioridad invalida: usa un numero de 1 a 9999.";
        } else if (prioridad <= 50) {
            tipo = "warning";
            texto = "Prioridad alta: esta lista gana ante listas equivalentes de mayor numero.";
        } else if (prioridad >= 500) {
            tipo = "info";
            texto = "Prioridad baja/base: adecuada para lista general o respaldo comercial.";
        } else {
            texto = "Prioridad normal: cliente o segmento aun pueden ganar por ser mas especificos.";
        }
        if (canal === "general" && !almacen && prioridad < 500) {
            tipo = "warning";
            texto += " Para lista general conviene usar prioridad 500 o mayor.";
        }
        nodo.className = "alert alert-light-" + tipo + " py-2 mt-3 mb-0 fs-8";
        nodo.textContent = texto;
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
            actualizarFlujoOperativo("encabezado");
        }).catch(mostrarError);
    }

    function cambiarEstatusLista(estatus) {
        var revisionLocal = revisionLocalPantalla();
        if (estatus === "activa" && revisionLocal.bloqueos.length > 0) {
            mostrarAlerta("warning", revisionLocal.bloqueos[0]);
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
            margen_minimo: margenMinimoOperativo(),
            limite: "160"
        };
    }

    function margenMinimoOperativo() {
        var valor = Number((document.getElementById("lp_margen_minimo") || {}).value || 15);
        if (!isFinite(valor) || valor <= 0 || valor > 95) {
            return 15;
        }
        return valor;
    }

    function cargarProductos() {
        var idLista = document.getElementById("lp_lista_id").value;
        var solo = document.getElementById("lp_producto_solo").value;
        if (!idLista) {
            document.getElementById("lp_productos").innerHTML = "<tr><td colspan=\"8\" class=\"text-center text-muted py-8\">Guarda la lista para cargar productos.</td></tr>";
            actualizarResumenProductos([]);
            actualizarCheckboxSeleccionTodos();
            actualizarFlujoOperativo("productos");
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
            estado.seleccionados = {};
            estado.sugeridos = {};
            estado.importacion = null;
            estado.comparacion = null;
            document.getElementById("lp_importar_resultado").innerHTML = "CSV esperado: id_sku o sku, y precio_lista.";
            renderComparacionListas();
            renderPrevalidacionLote(null);
            actualizarContadorCambios();
            renderProductos(estado.productos);
            actualizarFlujoOperativo("productos");
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
            var seleccionado = !!estado.seleccionados[String(item.id_sku)];
            var pendiente = motivoPendienteComercial(item);
            return "<tr data-lp-producto=\"" + escapeHtml(item.id_sku) + "\"" + (seleccionado ? " class=\"lp-row-selected\"" : "") + ">" +
                "<td class=\"text-center\"><input class=\"form-check-input\" type=\"checkbox\" data-lp-seleccionar-sku=\"" + escapeHtml(item.id_sku) + "\"" + (seleccionado ? " checked" : "") + "></td>" +
                "<td><div class=\"fw-bold\">" + escapeHtml(item.sku || "") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.sku_nombre || item.producto || "") + "</div><div class=\"text-muted fs-8\">" + escapeHtml([item.marca, item.categoria].filter(Boolean).join(" / ")) + "</div></td>" +
                "<td><span class=\"badge badge-light\">" + escapeHtml(item.unidad_base || "-") + "</span></td>" +
                "<td class=\"text-end\">" + dinero(item.costo_referencia) + "</td>" +
                "<td class=\"text-end\">" + dinero(item.precio_general) + "</td>" +
                "<td class=\"text-end\"><input class=\"form-control form-control-sm form-control-solid text-end lp-price-input\" data-lp-precio=\"" + escapeHtml(item.id_sku) + "\" data-lp-original=\"" + escapeHtml(precioLista) + "\" value=\"" + escapeHtml(precioLista) + "\" placeholder=\"0.00\"><div class=\"text-muted fs-9 lp-suggested\" data-lp-sugerido=\"" + escapeHtml(item.id_sku) + "\">" + textoPrecioSugerido(item.id_sku) + "</div></td>" +
                "<td class=\"text-end\"><div class=\"fw-semibold\" data-lp-margen=\"" + escapeHtml(item.id_sku) + "\">" + escapeHtml(margen) + "</div><div class=\"text-muted fs-8\" data-lp-utilidad=\"" + escapeHtml(item.id_sku) + "\">" + dinero(item.utilidad_estimada || 0) + "</div><span class=\"badge " + tipoBadge + "\" data-lp-riesgo=\"" + escapeHtml(item.id_sku) + "\">" + escapeHtml(riesgo.texto || "-") + "</span><div class=\"text-muted fs-9 mt-1\" data-lp-pendiente-motivo=\"" + escapeHtml(item.id_sku) + "\">" + escapeHtml(pendiente) + "</div></td>" +
                "<td class=\"text-end\"><div class=\"d-flex justify-content-end gap-1\"><button class=\"btn btn-sm btn-light\" data-lp-preview-sku=\"" + escapeHtml(item.id_sku) + "\" type=\"button\"><i class=\"bi bi-calculator\"></i></button><button class=\"btn btn-sm btn-light-success\" data-lp-usar-sugerido-fila=\"" + escapeHtml(item.id_sku) + "\" type=\"button\" title=\"Aplicar sugerido a este SKU\"><i class=\"bi bi-magic\"></i></button>" + (item.id_lista_precio_detalle ? "<button class=\"btn btn-sm btn-light\" data-lp-historial-sku=\"" + escapeHtml(item.id_sku) + "\" data-lp-historial-detalle=\"" + escapeHtml(item.id_lista_precio_detalle) + "\" type=\"button\"><i class=\"bi bi-clock-history\"></i></button>" : "") + "<button class=\"btn btn-sm btn-light-primary\" data-lp-guardar-precio=\"" + escapeHtml(item.id_sku) + "\" type=\"button\"><i class=\"bi bi-save\"></i></button>" + (item.id_lista_precio_detalle ? "<button class=\"btn btn-sm btn-light-danger\" data-lp-quitar-precio=\"" + escapeHtml(item.id_sku) + "\" type=\"button\"><i class=\"bi bi-x-circle\"></i></button>" : "") + "</div></td>" +
            "</tr>";
        }).join("") || "<tr><td colspan=\"8\" class=\"text-center text-muted py-8\">Sin productos para los filtros actuales</td></tr>";

        document.querySelectorAll("[data-lp-seleccionar-sku]").forEach(function (input) {
            input.addEventListener("change", function () {
                cambiarSeleccionSku(input.getAttribute("data-lp-seleccionar-sku"), input.checked);
            });
        });
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
        document.querySelectorAll("[data-lp-usar-sugerido-fila]").forEach(function (boton) {
            boton.addEventListener("click", function () {
                usarSugeridoFila(boton.getAttribute("data-lp-usar-sugerido-fila") || "");
            });
        });
        document.querySelectorAll("[data-lp-historial-sku]").forEach(function (boton) {
            boton.addEventListener("click", function () {
                cargarAuditoriaSku(
                    boton.getAttribute("data-lp-historial-sku") || "",
                    boton.getAttribute("data-lp-historial-detalle") || ""
                );
            });
        });
        document.querySelectorAll("[data-lp-quitar-precio]").forEach(function (boton) {
            boton.addEventListener("click", function () {
                quitarPrecioProducto(boton.getAttribute("data-lp-quitar-precio"));
            });
        });
        actualizarCheckboxSeleccionTodos();
        actualizarFlujoOperativo("productos");
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
        var perdida = 0;
        var sinPrecio = 0;
        var margenMinimo = margenMinimoOperativo();
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
            if (precio <= 0) {
                sinPrecio++;
            }
            if (precio > 0 && costo > 0 && precio < costo) {
                perdida++;
            }
            if (precio > 0 && costo > 0 && (precio < costo || (margen != null && margen < margenMinimo))) {
                margenBajo++;
            }
        });
        var nodos = {
            lp_res_productos: productos.length,
            lp_tab_productos_count: productos.length,
            lp_res_seleccionados: Object.keys(estado.seleccionados || {}).length,
            lp_res_margen_bajo: margenBajo,
            lp_res_perdida: perdida,
            lp_res_sin_costo: sinCosto,
            lp_res_sin_precio: sinPrecio,
            lp_res_cambios: cambios
        };
        Object.keys(nodos).forEach(function (id) {
            var nodo = document.getElementById(id);
            if (nodo) {
                nodo.textContent = nodos[id];
            }
        });
        renderBarraCambios(productos.length, cambios, perdida, margenBajo, sinCosto);
    }

    function renderBarraCambios(visibles, cambios, perdida, margenBajo, sinCosto) {
        var nodo = document.getElementById("lp_cambios_barra");
        if (!nodo) {
            return;
        }
        var hayCambios = cambios > 0;
        var tipo = hayCambios ? (perdida > 0 ? "danger" : (margenBajo > 0 || sinCosto > 0 ? "warning" : "primary")) : "light";
        nodo.className = "alert alert-" + tipo + " py-3 mb-4";
        var titulo = hayCambios ? (cambios + " cambio(s) pendiente(s)") : "Sin cambios pendientes";
        var detalle = hayCambios
            ? ("Visibles " + visibles + " | perdida " + perdida + " | margen bajo " + margenBajo + " | sin costo " + sinCosto)
            : "Captura precios o aplica sugeridos para prevalidar el lote.";
        var tituloNodo = nodo.querySelector(".fw-semibold");
        var detalleNodo = nodo.querySelector(".text-muted");
        if (tituloNodo) {
            tituloNodo.textContent = titulo;
        }
        if (detalleNodo) {
            detalleNodo.textContent = detalle;
        }
    }

    function verProductosModificados() {
        var select = document.getElementById("lp_producto_solo");
        if (select) {
            select.value = "modificados";
        }
        renderProductos(productosModificados());
        mostrarAlerta("info", "Mostrando productos con cambios pendientes.");
    }

    function productoPorSku(idSku) {
        return (estado.productos || []).find(function (item) { return String(item.id_sku) === String(idSku); }) || null;
    }

    function cambiarSeleccionSku(idSku, seleccionado) {
        var row = document.querySelector("[data-lp-producto='" + idSku + "']");
        if (seleccionado) {
            estado.seleccionados[String(idSku)] = true;
        } else {
            delete estado.seleccionados[String(idSku)];
        }
        if (row) {
            row.classList.toggle("lp-row-selected", !!seleccionado);
        }
        actualizarResumenProductos(estado.productosVisibles || []);
        actualizarCheckboxSeleccionTodos();
    }

    function seleccionarProductosVisibles(seleccionado) {
        (estado.productosVisibles || []).forEach(function (item) {
            var idSku = String(item.id_sku);
            if (seleccionado) {
                estado.seleccionados[idSku] = true;
            } else {
                delete estado.seleccionados[idSku];
            }
        });
        renderProductos(estado.productosVisibles || []);
    }

    function actualizarCheckboxSeleccionTodos() {
        var checkbox = document.getElementById("lp_productos_select_all");
        if (!checkbox) {
            return;
        }
        var visibles = estado.productosVisibles || [];
        var seleccionadosVisibles = visibles.filter(function (item) {
            return !!estado.seleccionados[String(item.id_sku)];
        }).length;
        checkbox.checked = visibles.length > 0 && seleccionadosVisibles === visibles.length;
        checkbox.indeterminate = seleccionadosVisibles > 0 && seleccionadosVisibles < visibles.length;
    }

    function productosParaAccionMasiva() {
        var alcance = document.getElementById("lp_accion_alcance").value || "seleccionados";
        var visibles = estado.productosVisibles || [];
        if (alcance === "visibles") {
            return visibles;
        }
        return visibles.filter(function (item) {
            return !!estado.seleccionados[String(item.id_sku)];
        });
    }

    function textoAlcanceAccion() {
        return (document.getElementById("lp_accion_alcance").value || "seleccionados") === "visibles" ? "visible(s)" : "seleccionado(s)";
    }

    function validarProductosAccionMasiva() {
        var productos = productosParaAccionMasiva();
        if (!productos.length) {
            mostrarAlerta("warning", "Selecciona productos o cambia el alcance a visibles.");
            return null;
        }
        return productos;
    }

    function textoPrecioSugerido(idSku) {
        var sugerido = estado.sugeridos[String(idSku)];
        if (sugerido == null) {
            return "";
        }
        return "Sugerido " + dinero(sugerido);
    }

    function renderSugeridos() {
        document.querySelectorAll("[data-lp-sugerido]").forEach(function (nodo) {
            nodo.textContent = textoPrecioSugerido(nodo.getAttribute("data-lp-sugerido"));
        });
    }

    function precioActualFila(item) {
        var input = document.querySelector("[data-lp-precio='" + item.id_sku + "']");
        if (input) {
            return Number(input.value || 0);
        }
        return Number(item.precio_lista || 0);
    }

    function motivoPendienteComercial(item) {
        var precio = precioActualFila(item);
        var costo = Number(item.costo_referencia || 0);
        var margenMinimo = margenMinimoOperativo();
        if (precio <= 0) {
            return "Pendiente: sin precio";
        }
        if (costo <= 0) {
            return "Revisar: sin costo";
        }
        if (precio < costo) {
            return "Pendiente: precio debajo de costo";
        }
        var margen = ((precio - costo) / precio) * 100;
        if (margen < margenMinimo) {
            return "Pendiente: margen menor a " + numero(margenMinimo, 2) + "%";
        }
        return "";
    }

    function actualizarMotivoPendienteFila(idSku) {
        var item = productoPorSku(idSku);
        var nodo = document.querySelector("[data-lp-pendiente-motivo='" + idSku + "']");
        if (item && nodo) {
            nodo.textContent = motivoPendienteComercial(item);
        }
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
        var margenMinimo = margenMinimoOperativo();
        var margenNodo = document.querySelector("[data-lp-margen='" + idSku + "']");
        var utilidadNodo = document.querySelector("[data-lp-utilidad='" + idSku + "']");
        var riesgoNodo = document.querySelector("[data-lp-riesgo='" + idSku + "']");
        margenNodo.textContent = margen == null ? "-" : numero(margen, 2) + "%";
        utilidadNodo.textContent = dinero(precio - costo);
        var riesgo = precio <= 0 ? ["Sin precio", "badge-light"] : (costo <= 0 ? ["Sin costo", "badge-light-warning"] : (precio < costo ? ["Perdida", "badge-light-danger"] : (margen < margenMinimo ? ["Margen < " + numero(margenMinimo, 2) + "%", "badge-light-warning"] : ["Margen OK", "badge-light-success"])));
        riesgoNodo.className = "badge " + riesgo[1];
        riesgoNodo.textContent = riesgo[0];
        actualizarMotivoPendienteFila(idSku);
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
        actualizarCheckboxSeleccionTodos();
        actualizarBotonesEstatus();
        actualizarFlujoOperativo("productos");
    }

    function aplicarMargenObjetivo() {
        var margen = Number(document.getElementById("lp_margen_objetivo").value || 0);
        if (!isFinite(margen) || margen <= 0 || margen >= 95) {
            mostrarAlerta("warning", "Captura un margen objetivo mayor a 0 y menor a 95.");
            return;
        }
        var productos = validarProductosAccionMasiva();
        if (!productos) {
            return;
        }
        var aplicados = 0;
        productos.forEach(function (item) {
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
        mostrarAlerta(aplicados ? "success" : "warning", aplicados ? ("Margen aplicado a " + aplicados + " producto(s) " + textoAlcanceAccion() + ".") : "No hay productos con costo para aplicar margen en el alcance elegido.");
    }

    function calcularPreciosSugeridos() {
        var margen = Number(document.getElementById("lp_margen_objetivo").value || 0);
        if (!isFinite(margen) || margen <= 0 || margen >= 95) {
            mostrarAlerta("warning", "Captura un margen objetivo mayor a 0 y menor a 95.");
            return;
        }
        var productos = validarProductosAccionMasiva();
        if (!productos) {
            return;
        }
        var modo = document.getElementById("lp_redondeo_modo").value || "entero";
        var calculados = 0;
        var omitidos = 0;
        productos.forEach(function (item) {
            var costo = Number(item.costo_referencia || 0);
            if (costo <= 0) {
                omitidos++;
                return;
            }
            var precio = costo / (1 - (margen / 100));
            estado.sugeridos[String(item.id_sku)] = numero(redondearPrecio(precio, modo), 2);
            calculados++;
        });
        renderSugeridos();
        mostrarAlerta(calculados ? "success" : "warning", calculados ? ("Se calcularon " + calculados + " sugerido(s). No se guardaron cambios." + (omitidos ? " Omitidos sin costo: " + omitidos + "." : "")) : "No hay productos con costo para sugerir precio.");
    }

    function esProductoPendienteComercial(item) {
        var input = document.querySelector("[data-lp-precio='" + item.id_sku + "']");
        var precio = input ? Number(input.value || 0) : Number(item.precio_lista || 0);
        var costo = Number(item.costo_referencia || 0);
        var margenMinimo = margenMinimoOperativo();
        var margen = precio > 0 && costo > 0 ? ((precio - costo) / precio) * 100 : null;
        return precio <= 0 || (precio > 0 && costo > 0 && precio < costo) || (margen !== null && margen < margenMinimo);
    }

    function productosPendientesComerciales() {
        return (estado.productosVisibles || []).filter(function (item) {
            return esProductoPendienteComercial(item);
        });
    }

    function calcularSugeridosPendientes() {
        var margen = Number(document.getElementById("lp_margen_objetivo").value || 0);
        if (!isFinite(margen) || margen <= 0 || margen >= 95) {
            mostrarAlerta("warning", "Captura un margen objetivo mayor a 0 y menor a 95.");
            return;
        }
        var productos = productosPendientesComerciales();
        if (!productos.length) {
            mostrarAlerta("success", "No hay pendientes comerciales visibles para sugerir.");
            return;
        }
        var modo = document.getElementById("lp_redondeo_modo").value || "entero";
        var calculados = 0;
        var omitidos = 0;
        productos.forEach(function (item) {
            var costo = Number(item.costo_referencia || 0);
            if (costo <= 0) {
                omitidos++;
                return;
            }
            var precio = costo / (1 - (margen / 100));
            estado.sugeridos[String(item.id_sku)] = numero(redondearPrecio(precio, modo), 2);
            calculados++;
        });
        renderSugeridos();
        mostrarAlerta(calculados ? "success" : "warning", calculados ? ("Se calcularon sugeridos para " + calculados + " pendiente(s). No se guardaron cambios." + (omitidos ? " Omitidos sin costo: " + omitidos + "." : "")) : "Los pendientes visibles no tienen costo para calcular sugeridos.");
    }

    function calcularSugeridoFila(item) {
        var margen = Number(document.getElementById("lp_margen_objetivo").value || 0);
        if (!isFinite(margen) || margen <= 0 || margen >= 95) {
            mostrarAlerta("warning", "Captura un margen objetivo mayor a 0 y menor a 95.");
            return null;
        }
        var costo = Number(item.costo_referencia || 0);
        if (costo <= 0) {
            mostrarAlerta("warning", "Este SKU no tiene costo de referencia para calcular sugerido.");
            return null;
        }
        var modo = document.getElementById("lp_redondeo_modo").value || "entero";
        var precio = costo / (1 - (margen / 100));
        return numero(redondearPrecio(precio, modo), 2);
    }

    function usarPreciosSugeridos() {
        var productos = validarProductosAccionMasiva();
        if (!productos) {
            return;
        }
        var usados = 0;
        productos.forEach(function (item) {
            var precio = estado.sugeridos[String(item.id_sku)];
            var input = document.querySelector("[data-lp-precio='" + item.id_sku + "']");
            if (precio == null || !input) {
                return;
            }
            input.value = numero(precio, 2);
            recalcularMargenLocal(item.id_sku);
            marcarCambioPrecio(item.id_sku);
            usados++;
        });
        mostrarAlerta(usados ? "success" : "warning", usados ? ("Se aplicaron " + usados + " sugerido(s) como cambios pendientes.") : "Primero calcula precios sugeridos para los productos seleccionados.");
    }

    function usarSugeridosPendientes() {
        var productos = productosPendientesComerciales();
        if (!productos.length) {
            mostrarAlerta("success", "No hay pendientes comerciales visibles para aplicar.");
            return;
        }
        var usados = 0;
        productos.forEach(function (item) {
            var precio = estado.sugeridos[String(item.id_sku)];
            var input = document.querySelector("[data-lp-precio='" + item.id_sku + "']");
            if (precio == null || !input) {
                return;
            }
            input.value = numero(precio, 2);
            recalcularMargenLocal(item.id_sku);
            marcarCambioPrecio(item.id_sku);
            usados++;
        });
        mostrarAlerta(usados ? "success" : "warning", usados ? ("Se aplicaron " + usados + " sugerido(s) de pendientes como cambios pendientes.") : "Primero calcula sugeridos para pendientes visibles.");
    }

    function usarSugeridoFila(idSku) {
        var item = productoPorSku(idSku);
        var input = document.querySelector("[data-lp-precio='" + idSku + "']");
        if (!item || !input) {
            mostrarAlerta("warning", "No se encontro el SKU visible para aplicar sugerido.");
            return;
        }
        var precio = estado.sugeridos[String(idSku)];
        if (precio == null) {
            precio = calcularSugeridoFila(item);
            if (precio == null) {
                return;
            }
            estado.sugeridos[String(idSku)] = precio;
            renderSugeridos();
        }
        input.value = numero(precio, 2);
        recalcularMargenLocal(idSku);
        marcarCambioPrecio(idSku);
        mostrarAlerta("success", "Sugerido aplicado al SKU " + (item.sku || idSku) + " como cambio pendiente.");
    }

    function csvValor(value) {
        var texto = value == null ? "" : String(value);
        return "\"" + texto.replace(/"/g, "\"\"") + "\"";
    }

    function exportarProductosCsv() {
        var productos = estado.productosVisibles || [];
        if (!productos.length) {
            mostrarAlerta("warning", "No hay productos visibles para exportar.");
            return;
        }
        var encabezados = ["id_sku", "sku", "producto", "unidad", "costo_referencia", "precio_general", "precio_lista", "precio_sugerido", "margen_estimado"];
        var filas = productos.map(function (item) {
            var input = document.querySelector("[data-lp-precio='" + item.id_sku + "']");
            var precioLista = input ? input.value : (item.precio_lista == null ? "" : item.precio_lista);
            return [
                item.id_sku,
                item.sku || "",
                item.sku_nombre || item.producto || "",
                item.unidad_base || "",
                numero(item.costo_referencia || 0, 2),
                numero(item.precio_general || 0, 2),
                precioLista,
                estado.sugeridos[String(item.id_sku)] || "",
                item.margen_estimado == null ? "" : numero(item.margen_estimado, 2)
            ].map(csvValor).join(",");
        });
        var csv = encabezados.join(",") + "\r\n" + filas.join("\r\n");
        var blob = new Blob([csv], {type: "text/csv;charset=utf-8;"});
        var enlace = document.createElement("a");
        var idLista = document.getElementById("lp_lista_id").value || "nueva";
        enlace.href = URL.createObjectURL(blob);
        enlace.download = "lista_precios_" + idLista + "_productos.csv";
        document.body.appendChild(enlace);
        enlace.click();
        document.body.removeChild(enlace);
        URL.revokeObjectURL(enlace.href);
        mostrarAlerta("success", "CSV generado con " + productos.length + " producto(s) visible(s).");
    }

    function parseCsv(texto) {
        var filas = [];
        var fila = [];
        var valor = "";
        var entreComillas = false;
        for (var i = 0; i < texto.length; i++) {
            var c = texto.charAt(i);
            var siguiente = texto.charAt(i + 1);
            if (c === "\"" && entreComillas && siguiente === "\"") {
                valor += "\"";
                i++;
            } else if (c === "\"") {
                entreComillas = !entreComillas;
            } else if (c === "," && !entreComillas) {
                fila.push(valor);
                valor = "";
            } else if ((c === "\n" || c === "\r") && !entreComillas) {
                if (c === "\r" && siguiente === "\n") {
                    i++;
                }
                fila.push(valor);
                if (fila.some(function (item) { return String(item).trim() !== ""; })) {
                    filas.push(fila);
                }
                fila = [];
                valor = "";
            } else {
                valor += c;
            }
        }
        fila.push(valor);
        if (fila.some(function (item) { return String(item).trim() !== ""; })) {
            filas.push(fila);
        }
        return filas;
    }

    function normalizarClaveCsv(value) {
        return String(value || "").trim().toLowerCase().replace(/\s+/g, "_");
    }

    function numeroCsv(value) {
        var texto = String(value == null ? "" : value).trim().replace(/\$/g, "").replace(/\s/g, "");
        if (texto.indexOf(",") >= 0 && texto.indexOf(".") < 0) {
            texto = texto.replace(",", ".");
        } else {
            texto = texto.replace(/,/g, "");
        }
        var n = Number(texto);
        return isFinite(n) ? n : null;
    }

    function mapaProductosVisibles() {
        var mapa = {porId: {}, porSku: {}};
        (estado.productosVisibles || []).forEach(function (item) {
            mapa.porId[String(item.id_sku)] = item;
            mapa.porSku[String(item.sku || "").trim().toUpperCase()] = item;
        });
        return mapa;
    }

    function prevalidarImportacionCsv() {
        cambiarTabProducto("importar");
        var archivo = document.getElementById("lp_importar_csv").files[0];
        if (!archivo) {
            mostrarAlerta("warning", "Selecciona un archivo CSV para prevalidar.");
            return;
        }
        if (!(estado.productosVisibles || []).length) {
            mostrarAlerta("warning", "Carga productos visibles antes de importar para poder cruzar SKUs.");
            return;
        }
        archivo.text().then(function (texto) {
            var filas = parseCsv(texto);
            if (filas.length < 2) {
                throw new Error("El CSV no tiene encabezado y datos.");
            }
            var encabezados = filas[0].map(normalizarClaveCsv);
            var idxId = encabezados.indexOf("id_sku");
            var idxSku = encabezados.indexOf("sku");
            var idxPrecio = encabezados.indexOf("precio_lista");
            if (idxPrecio < 0) {
                idxPrecio = encabezados.indexOf("precio");
            }
            if (idxId < 0 && idxSku < 0) {
                throw new Error("El CSV debe incluir columna id_sku o sku.");
            }
            if (idxPrecio < 0) {
                throw new Error("El CSV debe incluir columna precio_lista o precio.");
            }
            var mapa = mapaProductosVisibles();
            var validos = [];
            var errores = [];
            filas.slice(1).forEach(function (fila, index) {
                var idSku = idxId >= 0 ? String(fila[idxId] || "").trim() : "";
                var sku = idxSku >= 0 ? String(fila[idxSku] || "").trim().toUpperCase() : "";
                var producto = idSku ? mapa.porId[idSku] : null;
                if (!producto && sku) {
                    producto = mapa.porSku[sku] || null;
                }
                var precio = numeroCsv(fila[idxPrecio]);
                if (!producto) {
                    errores.push("Fila " + (index + 2) + ": SKU no visible o no encontrado.");
                    return;
                }
                if (precio == null || precio <= 0) {
                    errores.push("Fila " + (index + 2) + ": precio invalido.");
                    return;
                }
                validos.push({id_sku: producto.id_sku, sku: producto.sku, precio: numero(precio, 2)});
            });
            estado.importacion = {validos: validos, errores: errores};
            renderImportacionCsv();
        }).catch(function (error) {
            estado.importacion = null;
            document.getElementById("lp_importar_resultado").innerHTML = "<div class=\"alert alert-danger py-3 mb-0\">" + escapeHtml(error.message || String(error)) + "</div>";
        });
    }

    function renderImportacionCsv() {
        var data = estado.importacion || {validos: [], errores: []};
        var tipo = data.validos.length ? (data.errores.length ? "warning" : "success") : "warning";
        var html = "<div class=\"alert alert-" + tipo + " py-3 mb-2\"><div class=\"fw-semibold\">Prevalidacion CSV</div>" +
            "<div class=\"fs-8\">Validos: " + escapeHtml(data.validos.length) + " | Errores: " + escapeHtml(data.errores.length) + " | No se guardo en BD.</div></div>";
        if (data.validos.length) {
            html += "<div class=\"fs-8 mb-2\">Primeros validos: " + data.validos.slice(0, 5).map(function (item) {
                return escapeHtml((item.sku || item.id_sku) + " = " + dinero(item.precio));
            }).join(" | ") + "</div>";
        }
        if (data.errores.length) {
            html += "<ul class=\"fs-8 ps-4 mb-0\">" + data.errores.slice(0, 8).map(function (item) {
                return "<li>" + escapeHtml(item) + "</li>";
            }).join("") + (data.errores.length > 8 ? "<li>" + escapeHtml("Mas errores: " + (data.errores.length - 8)) + "</li>" : "") + "</ul>";
        }
        document.getElementById("lp_importar_resultado").innerHTML = html;
    }

    function aplicarImportacionCsv() {
        var data = estado.importacion || null;
        if (!data || !data.validos || !data.validos.length) {
            mostrarAlerta("warning", "Prevalida un CSV con productos validos antes de aplicar importacion.");
            return;
        }
        var aplicados = 0;
        data.validos.forEach(function (item) {
            var producto = productoPorSku(item.id_sku);
            var input = document.querySelector("[data-lp-precio='" + item.id_sku + "']");
            if (!producto || !input) {
                return;
            }
            input.value = numero(item.precio, 2);
            recalcularMargenLocal(item.id_sku);
            marcarCambioPrecio(item.id_sku);
            aplicados++;
        });
        mostrarAlerta(aplicados ? "success" : "warning", aplicados ? ("Importacion aplicada como cambios pendientes: " + aplicados + " precio(s).") : "No se pudo aplicar ningun precio del CSV.");
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
            var productos = validarProductosAccionMasiva();
            if (!productos) {
                return;
            }
            var copiados = 0;
            productos.forEach(function (item) {
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
            mostrarAlerta(copiados ? "success" : "warning", copiados ? ("Se copiaron " + copiados + " precio(s) " + textoAlcanceAccion() + " desde la lista " + idOrigen + ".") : "La lista origen no tiene precios para el alcance elegido.");
        }).catch(mostrarError);
    }

    function precioActualProducto(item) {
        var input = document.querySelector("[data-lp-precio='" + item.id_sku + "']");
        if (input && String(input.value || "").trim() !== "") {
            return Number(input.value || 0);
        }
        return item.precio_lista == null ? null : Number(item.precio_lista);
    }

    function compararListaOrigen() {
        var idOrigen = document.getElementById("lp_copiar_lista_id").value.trim();
        var idActual = document.getElementById("lp_lista_id").value.trim();
        var productos = estado.productosVisibles || [];
        if (!idActual) {
            mostrarAlerta("warning", "Selecciona una lista destino antes de comparar.");
            return;
        }
        if (!idOrigen || idOrigen === idActual) {
            mostrarAlerta("warning", "Captura una lista origen distinta a la lista actual.");
            return;
        }
        if (!productos.length) {
            mostrarAlerta("warning", "Carga productos visibles antes de comparar listas.");
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
                    origen[String(item.id_sku)] = item;
                }
            });
            var filas = [];
            var iguales = 0;
            var diferentes = 0;
            var faltantes = 0;
            productos.forEach(function (item) {
                var itemOrigen = origen[String(item.id_sku)] || null;
                var precioActual = precioActualProducto(item);
                var precioOrigen = itemOrigen ? Number(itemOrigen.precio_lista || 0) : null;
                var diferencia = precioOrigen == null || precioActual == null ? null : precioOrigen - precioActual;
                var diferenciaPct = diferencia == null || Number(precioActual || 0) === 0 ? null : (diferencia / precioActual) * 100;
                var tipo = "faltante";
                if (precioOrigen != null && precioActual != null && Math.abs(precioOrigen - precioActual) < 0.005) {
                    tipo = "igual";
                    iguales++;
                } else if (precioOrigen != null) {
                    tipo = "diferente";
                    diferentes++;
                } else {
                    faltantes++;
                }
                filas.push({
                    id_sku: item.id_sku,
                    sku: item.sku || "",
                    producto: item.sku_nombre || item.producto || "",
                    costo: Number(item.costo_referencia || 0),
                    precio_actual: precioActual,
                    precio_origen: precioOrigen,
                    diferencia: diferencia,
                    diferencia_pct: diferenciaPct,
                    tipo: tipo
                });
            });
            estado.comparacion = {
                id_origen: idOrigen,
                filas: filas,
                resumen: {iguales: iguales, diferentes: diferentes, faltantes: faltantes, visibles: productos.length}
            };
            cambiarTabProducto("herramientas");
            renderComparacionListas();
            mostrarAlerta("success", "Comparacion lista. Revisa diferencias antes de usarlas como cambios pendientes.");
        }).catch(mostrarError);
    }

    function renderComparacionListas() {
        var nodo = document.getElementById("lp_comparacion_resultado");
        if (!nodo) {
            return;
        }
        var data = estado.comparacion || null;
        if (!data) {
            nodo.innerHTML = "<div class=\"text-muted fs-7\">Compara contra otra lista para revisar diferencias antes de copiar precios.</div>";
            return;
        }
        var resumen = data.resumen || {};
        var diferencias = (data.filas || []).filter(function (item) { return item.tipo === "diferente"; }).slice(0, 8);
        var faltantes = (data.filas || []).filter(function (item) { return item.tipo === "faltante"; }).length;
        var html = "<div class=\"d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3\">" +
            "<div><div class=\"fw-bold\">Comparacion contra lista " + escapeHtml(data.id_origen) + "</div><div class=\"text-muted fs-8\">Solo productos visibles en esta mesa.</div></div>" +
            "<div class=\"d-flex flex-wrap gap-1\"><span class=\"badge badge-light-primary\">" + escapeHtml(resumen.visibles || 0) + " visibles</span><span class=\"badge badge-light-success\">" + escapeHtml(resumen.iguales || 0) + " iguales</span><span class=\"badge badge-light-warning\">" + escapeHtml(resumen.diferentes || 0) + " diferentes</span><span class=\"badge badge-light-danger\">" + escapeHtml(faltantes) + " sin precio origen</span></div>" +
            "</div>";
        if (!diferencias.length) {
            html += "<div class=\"text-muted fs-7\">No hay diferencias aplicables en los productos visibles.</div>";
            nodo.innerHTML = html;
            return;
        }
        html += "<div class=\"table-responsive\"><table class=\"table table-sm align-middle mb-0\"><thead><tr class=\"text-muted fs-8 text-uppercase\"><th>SKU</th><th>Producto</th><th class=\"text-end\">Actual</th><th class=\"text-end\">Origen</th><th class=\"text-end\">Diferencia</th><th class=\"text-end\">%</th><th class=\"text-end\">Margen origen</th></tr></thead><tbody>";
        html += diferencias.map(function (item) {
            var margenOrigen = item.precio_origen && item.costo > 0 ? ((item.precio_origen - item.costo) / item.precio_origen) * 100 : null;
            return "<tr><td class=\"fw-bold\">" + escapeHtml(item.sku) + "</td><td class=\"text-muted\">" + escapeHtml(item.producto) + "</td><td class=\"text-end\">" + (item.precio_actual == null ? "-" : dinero(item.precio_actual)) + "</td><td class=\"text-end\">" + dinero(item.precio_origen) + "</td><td class=\"text-end\">" + dinero(item.diferencia || 0) + "</td><td class=\"text-end\">" + (item.diferencia_pct == null ? "-" : numero(item.diferencia_pct, 2) + "%") + "</td><td class=\"text-end\">" + (margenOrigen == null ? "-" : numero(margenOrigen, 2) + "%") + "</td></tr>";
        }).join("");
        html += "</tbody></table></div>";
        if ((resumen.diferentes || 0) > diferencias.length) {
            html += "<div class=\"text-muted fs-8 mt-2\">Mostrando primeras " + diferencias.length + " diferencias de " + escapeHtml(resumen.diferentes) + ".</div>";
        }
        nodo.innerHTML = html;
    }

    function aplicarDiferenciasComparacion() {
        var data = estado.comparacion || null;
        if (!data || !data.filas || !data.filas.length) {
            mostrarAlerta("warning", "Primero compara contra una lista origen.");
            return;
        }
        var aplicados = 0;
        data.filas.forEach(function (item) {
            if (item.tipo !== "diferente" || item.precio_origen == null) {
                return;
            }
            var input = document.querySelector("[data-lp-precio='" + item.id_sku + "']");
            if (!input) {
                return;
            }
            input.value = numero(item.precio_origen, 2);
            recalcularMargenLocal(item.id_sku);
            marcarCambioPrecio(item.id_sku);
            aplicados++;
        });
        mostrarAlerta(aplicados ? "success" : "warning", aplicados ? ("Se aplicaron " + aplicados + " diferencia(s) como cambios pendientes.") : "No hay diferencias aplicables.");
    }

    function copiarPrecioGeneralVisibles() {
        if (!document.getElementById("lp_lista_id").value.trim()) {
            mostrarAlerta("warning", "Guarda o selecciona una lista destino antes de copiar precios.");
            return;
        }
        var productos = validarProductosAccionMasiva();
        if (!productos) {
            return;
        }
        var copiados = 0;
        productos.forEach(function (item) {
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
        mostrarAlerta(copiados ? "success" : "warning", copiados ? ("Precio general copiado a " + copiados + " producto(s) " + textoAlcanceAccion() + ".") : "No hay productos con precio general en el alcance elegido.");
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
        var productos = validarProductosAccionMasiva();
        if (!productos) {
            return;
        }
        var modo = document.getElementById("lp_redondeo_modo").value || "entero";
        var redondeados = 0;
        productos.forEach(function (item) {
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
        mostrarAlerta(redondeados ? "success" : "warning", redondeados ? ("Redondeo aplicado a " + redondeados + " producto(s) " + textoAlcanceAccion() + ".") : "No hay precios para redondear en el alcance elegido.");
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
        renderPrevalidacionLote(null);
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
        cambiarTabProducto("prevalidacion");
        var idLista = document.getElementById("lp_lista_id").value;
        var cambios = Object.keys(estado.cambios || {}).map(function (idSku) { return estado.cambios[idSku]; });
        if (!validarCambiosPendientes(idLista, cambios)) {
            return;
        }
        prevalidarCambiosLoteBackend(idLista, cambios, true).then(function (ok) {
            if (!ok) {
                return;
            }
            guardarCambiosLoteConfirmado(idLista, cambios);
        }).catch(mostrarError);
    }

    function prevalidarCambiosPendientes() {
        cambiarTabProducto("prevalidacion");
        var idLista = document.getElementById("lp_lista_id").value;
        var cambios = Object.keys(estado.cambios || {}).map(function (idSku) { return estado.cambios[idSku]; });
        if (!validarCambiosPendientes(idLista, cambios)) {
            return;
        }
        prevalidarCambiosLoteBackend(idLista, cambios, false).then(function (ok) {
            if (ok) {
                mostrarAlerta("success", "Prevalidacion completada. Revisa el resumen antes de guardar.");
            }
        }).catch(mostrarError);
    }

    function validarCambiosPendientes(idLista, cambios) {
        if (!idLista) {
            mostrarAlerta("warning", "Selecciona y guarda una lista antes de guardar cambios.");
            return false;
        }
        if (!cambios.length) {
            mostrarAlerta("info", "No hay precios modificados por guardar.");
            return false;
        }
        return true;
    }

    function prevalidarCambiosLoteBackend(idLista, cambios, confirmarAvisos) {
        return postRequest("/comercial/listas_precios_detalles_lote_dryrun_erp", {
            id_lista_precio: idLista,
            precios_json: JSON.stringify(cambios),
            motivo: "Prevalidacion backend de lote"
        }).then(function (response) {
            var data = response.depurar || {};
            var resumen = data.resumen || {};
            var errores = data.errores || [];
            var avisos = data.avisos || [];
            renderPrevalidacionLote(data);
            if (response.error || data.puede_guardar === false) {
                var detalle = errores.slice(0, 4).map(function (item) {
                    return "Fila " + (item.fila || "-") + ": " + (item.bloqueos || [item.mensaje || "Error"]).join(" | ");
                }).join("; ");
                mostrarAlerta("warning", (response.mensaje || "Lote con bloqueos") + (detalle ? ". " + detalle : ""));
                return false;
            }
            if (Number(resumen.perdida || 0) > 0) {
                mostrarAlerta("warning", "El lote tiene precios con perdida. Corrige esos cambios antes de guardar.");
                return false;
            }
            if (avisos.length || Number(resumen.margen_bajo || 0) > 0 || Number(resumen.sin_costo || 0) > 0) {
                mostrarAlerta("warning", "Prevalidacion backend OK con avisos: margen bajo " + Number(resumen.margen_bajo || 0) + ", sin costo " + Number(resumen.sin_costo || 0) + ".");
                if (!confirmarAvisos) {
                    return true;
                }
                return window.confirm("El lote tiene avisos comerciales. ¿Deseas guardar de todas formas?");
            }
            return true;
        });
    }

    function renderPrevalidacionLote(data) {
        var nodo = document.getElementById("lp_lote_prevalidacion");
        if (!nodo) {
            return;
        }
        if (!data) {
            nodo.innerHTML = "<div class=\"text-muted fs-7\">Los cambios pendientes se prevalidan antes de guardar.</div>";
            return;
        }
        var resumen = data.resumen || {};
        var errores = data.errores || [];
        var avisos = data.avisos || [];
        var perdida = Number(resumen.perdida || 0);
        var tipo = errores.length || perdida > 0 || data.puede_guardar === false ? "danger" : ((avisos.length || Number(resumen.margen_bajo || 0) > 0 || Number(resumen.sin_costo || 0) > 0) ? "warning" : "success");
        var titulo = tipo === "danger" ? "Lote bloqueado" : (tipo === "warning" ? "Lote guardable con avisos" : "Lote listo para guardar");
        var html = "<div class=\"alert alert-" + tipo + " py-3 mb-3\"><div class=\"fw-semibold\">" + titulo + "</div><div class=\"fs-8\">Prevalidacion backend de cambios pendientes.</div></div>";
        html += "<div class=\"d-flex flex-wrap gap-1 mb-2\">" +
            "<span class=\"badge badge-light-primary\">Total " + escapeHtml(resumen.total || 0) + "</span>" +
            "<span class=\"badge badge-light-success\">Validos " + escapeHtml(resumen.validos || 0) + "</span>" +
            "<span class=\"badge badge-light-danger\">Errores " + escapeHtml(resumen.errores || 0) + "</span>" +
            "<span class=\"badge badge-light-danger\">Perdida " + escapeHtml(resumen.perdida || 0) + "</span>" +
            "<span class=\"badge badge-light-warning\">Margen bajo " + escapeHtml(resumen.margen_bajo || 0) + "</span>" +
            "<span class=\"badge badge-light-warning\">Sin costo " + escapeHtml(resumen.sin_costo || 0) + "</span>" +
            "<span class=\"badge badge-light-success\">OK margen " + escapeHtml(resumen.ok_margen || 0) + "</span>" +
        "</div>";
        if (errores.length) {
            html += "<div class=\"fs-8 text-muted mb-1\">Primeros errores</div><ul class=\"fs-8 ps-4 mb-2\">" + errores.slice(0, 4).map(function (item) {
                return "<li>Fila " + escapeHtml(item.fila || "-") + ": " + escapeHtml((item.bloqueos || [item.mensaje || "Error"]).join(" | ")) + "</li>";
            }).join("") + "</ul>";
        }
        if (avisos.length) {
            html += "<div class=\"fs-8 text-muted mb-1\">Avisos</div><ul class=\"fs-8 ps-4 mb-0\">" + avisos.slice(0, 4).map(function (item) {
                return "<li>" + escapeHtml(item) + "</li>";
            }).join("") + "</ul>";
        }
        nodo.innerHTML = html;
    }

    function guardarCambiosLoteConfirmado(idLista, cambios) {
        postRequest("/comercial/listas_precios_detalles_lote_guardar_operativo_erp", {
            id_lista_precio: idLista,
            precios_json: JSON.stringify(cambios),
            motivo: "Guardado operativo por lote"
        }).then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje);
            }
            var data = response.depurar || {};
            renderResultadoGuardadoLote(data, response);
            mostrarAlerta(response.tipo === "warning" ? "warning" : "success", (response.mensaje || "Cambios guardados") + " (" + (data.guardados || 0) + "/" + (data.total || cambios.length) + ")");
            cargarProductos();
            cargarRevision();
            cargarResumen();
        }).catch(mostrarError);
    }

    function renderResultadoGuardadoLote(data, response) {
        var nodo = document.getElementById("lp_lote_prevalidacion");
        if (!nodo) {
            return;
        }
        data = data || {};
        var guardados = data.guardados_detalle || [];
        var errores = data.errores || [];
        var total = Number(data.total || 0);
        var guardadosConteo = Number(data.guardados || guardados.length || 0);
        var tipo = errores.length ? "warning" : "success";
        var html = "<div class=\"alert alert-" + tipo + " py-3 mb-3\"><div class=\"fw-semibold\">" + escapeHtml((response || {}).mensaje || "Resultado de guardado") + "</div><div class=\"fs-8\">Guardados " + escapeHtml(guardadosConteo) + " de " + escapeHtml(total) + ". Auditoria por partida conservada en backend.</div></div>";
        html += "<div class=\"d-flex flex-wrap gap-1 mb-2\">" +
            "<span class=\"badge badge-light-success\">Guardados " + escapeHtml(guardadosConteo) + "</span>" +
            "<span class=\"badge badge-light-danger\">Errores " + escapeHtml(errores.length) + "</span>" +
            "<span class=\"badge badge-light-primary\">Total " + escapeHtml(total) + "</span>" +
        "</div>";
        if (guardados.length) {
            html += "<div class=\"fs-8 text-muted mb-1\">Primeros guardados</div><ul class=\"fs-8 ps-4 mb-2\">" + guardados.slice(0, 8).map(function (item) {
                return "<li>Fila " + escapeHtml(item.fila || "-") + ": SKU " + escapeHtml(item.id_sku || "-") + " | " + dinero(item.precio || 0) + " | " + escapeHtml(item.estatus || "activo") + "</li>";
            }).join("") + (guardadosConteo > guardados.length ? "<li>" + escapeHtml("Mas guardados: " + (guardadosConteo - guardados.length)) + "</li>" : "") + "</ul>";
        }
        if (errores.length) {
            html += "<div class=\"fs-8 text-muted mb-1\">Errores</div><ul class=\"fs-8 ps-4 mb-0\">" + errores.slice(0, 8).map(function (item) {
                return "<li>Fila " + escapeHtml(item.fila || "-") + ": SKU " + escapeHtml(item.id_sku || "-") + " | " + escapeHtml(item.mensaje || "Error") + "</li>";
            }).join("") + (errores.length > 8 ? "<li>" + escapeHtml("Mas errores: " + (errores.length - 8)) + "</li>" : "") + "</ul>";
        }
        nodo.innerHTML = html;
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
            renderPreparacionSegmentos({crm_segmentos: false, crm_segmentos_rel: false, segmentos_listas: false}, "sin_validar");
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
        estado.segmentosPorId = {};
        segmentos.forEach(function (item) {
            estado.segmentosPorId[String(item.id_segmento_crm || "")] = item;
        });
        estado.segmentosListasDisponible = !!schema.segmentos_listas;
        actualizarBotonGuardarSegmento();
        renderPreparacionSegmentos(schema, modo);
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
                seleccionarSegmento(
                    boton.getAttribute("data-lp-segmento-usar") || "",
                    boton.getAttribute("data-lp-segmento-nombre") || ""
                );
                validarSegmentoLista();
            });
        });
    }

    function seleccionarSegmento(idSegmento, nombre) {
        document.getElementById("lp_seg_id").value = idSegmento || "";
        document.getElementById("lp_seg_nombre").value = nombre || nombreSegmentoPorId(idSegmento) || "";
        mostrarAlerta("success", "Segmento seleccionado: " + (document.getElementById("lp_seg_nombre").value || idSegmento || ""));
    }

    function nombreSegmentoPorId(idSegmento) {
        var segmento = estado.segmentosPorId[String(idSegmento || "")] || null;
        if (!segmento) {
            return "";
        }
        return segmento.nombre || segmento.codigo || ("Segmento " + idSegmento);
    }

    function renderPreparacionSegmentos(schema, modo) {
        var contenedor = document.getElementById("lp_segmentos_preparacion");
        if (!contenedor) {
            return;
        }
        var pasos = [
            {ok: !!schema.crm_segmentos, texto: "Catalogo de segmentos CRM"},
            {ok: !!schema.crm_segmentos_rel, texto: "Relacion cliente/segmento"},
            {ok: !!schema.segmentos_listas, texto: "Puente segmento/lista de precios"}
        ];
        contenedor.innerHTML = "<div class=\"border rounded p-3 bg-light\">" +
            "<div class=\"d-flex justify-content-between align-items-center mb-2\">" +
                "<div class=\"fw-semibold fs-8 text-uppercase text-muted\">Preparacion segura</div>" +
                "<span class=\"badge " + (schema.segmentos_listas ? "badge-light-success" : "badge-light-warning") + "\">" + escapeHtml(modo || "planeado") + "</span>" +
            "</div>" +
            pasos.map(function (paso) {
                return "<div class=\"d-flex justify-content-between align-items-center fs-8 py-1\">" +
                    "<span>" + escapeHtml(paso.texto) + "</span>" +
                    "<span class=\"badge " + (paso.ok ? "badge-light-success" : "badge-light-warning") + "\">" + (paso.ok ? "listo" : "pendiente") + "</span>" +
                "</div>";
            }).join("") +
        "</div>";
    }

    function limpiarSegmentoSeleccionado() {
        ["lp_seg_asig_id", "lp_seg_id", "lp_seg_nombre", "lp_segmento_dryrun"].forEach(function (id) {
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
        if (!estado.segmentosListasDisponible) {
            mostrarAlerta("warning", "La tabla de segmentos/listas no esta disponible. Recarga la pantalla o valida esquema.");
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
        var revisionLocal = revisionLocalPantalla();
        var revisionProductos = construirRevisionProductosVisibles();
        var bloqueos = (data.bloqueos || []).concat(revisionLocal.bloqueos || []);
        var avisos = (data.avisos || []).concat(revisionLocal.avisos || []);
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
        html += renderRevisionProductosVisibles(revisionProductos);
        if (bloqueos.length) {
            html += "<div class=\"fw-semibold fs-8 text-uppercase text-muted mb-1\">Bloqueos</div><ul class=\"fs-8 ps-4 mb-3\">" + bloqueos.map(function (item) { return "<li>" + escapeHtml(item) + "</li>"; }).join("") + "</ul>";
        }
        if (avisos.length) {
            html += "<div class=\"fw-semibold fs-8 text-uppercase text-muted mb-1\">Avisos</div><ul class=\"fs-8 ps-4 mb-0\">" + avisos.map(function (item) { return "<li>" + escapeHtml(item) + "</li>"; }).join("") + "</ul>";
        }
        document.getElementById("lp_revision").innerHTML = html;
        activarAccionesRevisionComercial();
        actualizarBotonesEstatus();
        actualizarFlujoOperativo("revision");
    }

    function revisionLocalPantalla() {
        var bloqueos = [];
        var avisos = [];
        var pendientes = Object.keys(estado.cambios || {}).length;
        var sugeridos = Object.keys(estado.sugeridos || {}).length;
        var importacion = estado.importacion || null;
        var productos = construirRevisionProductosVisibles();
        if (pendientes > 0) {
            bloqueos.push("Hay " + pendientes + " cambio(s) de precio sin guardar en pantalla");
        }
        if (sugeridos > 0) {
            avisos.push("Hay precios sugeridos calculados; aplicalos o descarta cambios antes de activar");
        }
        if (importacion && importacion.validos && importacion.validos.length > 0) {
            avisos.push("Hay una importacion CSV prevalidada; aplicala o cambia productos antes de activar");
        }
        if (productos.perdida > 0) {
            avisos.push("La vista actual tiene " + productos.perdida + " producto(s) con perdida");
        }
        if (productos.margen_bajo > 0) {
            avisos.push("La vista actual tiene " + productos.margen_bajo + " producto(s) bajo el margen minimo");
        }
        if (productos.sin_costo > 0) {
            avisos.push("La vista actual tiene " + productos.sin_costo + " producto(s) sin costo de referencia");
        }
        if (productos.sin_precio > 0) {
            avisos.push("La vista actual tiene " + productos.sin_precio + " producto(s) sin precio de lista capturado");
        }
        return {bloqueos: bloqueos, avisos: avisos};
    }

    function construirRevisionProductosVisibles() {
        var resumen = {visibles: 0, con_precio: 0, sin_precio: 0, sin_costo: 0, perdida: 0, margen_bajo: 0, margen_minimo: margenMinimoOperativo()};
        (estado.productosVisibles || []).forEach(function (item) {
            var input = document.querySelector("[data-lp-precio='" + item.id_sku + "']");
            var precio = input ? Number(input.value || 0) : Number(item.precio_lista || 0);
            var costo = Number(item.costo_referencia || 0);
            var margen = precio > 0 && costo > 0 ? ((precio - costo) / precio) * 100 : null;
            resumen.visibles++;
            if (precio > 0) {
                resumen.con_precio++;
            } else {
                resumen.sin_precio++;
            }
            if (costo <= 0) {
                resumen.sin_costo++;
            }
            if (precio > 0 && costo > 0 && precio < costo) {
                resumen.perdida++;
            }
            if (precio > 0 && costo > 0 && margen !== null && margen < resumen.margen_minimo) {
                resumen.margen_bajo++;
            }
        });
        return resumen;
    }

    function renderRevisionProductosVisibles(resumen) {
        if (!resumen || !resumen.visibles) {
            return "<div class=\"alert alert-light py-3 mb-3 fs-8\">Carga productos para ver la revision comercial de la pantalla actual.</div>";
        }
        var tipo = resumen.perdida > 0 ? "danger" : (resumen.margen_bajo > 0 || resumen.sin_costo > 0 || resumen.sin_precio > 0 ? "warning" : "success");
        var titulo = tipo === "success" ? "Pantalla actual sin alertas comerciales" : "Pantalla actual con puntos por revisar";
        var acciones = [
            {filtro: "sin_precio", texto: "Ver sin precio", total: resumen.sin_precio, clase: "btn-light"},
            {filtro: "sin_costo", texto: "Ver sin costo", total: resumen.sin_costo, clase: "btn-light-warning"},
            {filtro: "perdida", texto: "Ver perdida", total: resumen.perdida, clase: "btn-light-danger"},
            {filtro: "margen_bajo", texto: "Ver margen bajo", total: resumen.margen_bajo, clase: "btn-light-warning"}
        ].filter(function (item) { return Number(item.total || 0) > 0; });
        return "<div class=\"alert alert-" + tipo + " py-3 mb-3\">" +
            "<div class=\"fw-semibold\">" + titulo + "</div>" +
            "<div class=\"fs-8 mb-2\">Revision local de " + escapeHtml(resumen.visibles) + " producto(s) visibles con margen minimo " + escapeHtml(numero(resumen.margen_minimo, 2)) + "%.</div>" +
            "<div class=\"d-flex flex-wrap gap-1\">" +
                "<span class=\"badge badge-light-success\">Con precio " + escapeHtml(resumen.con_precio) + "</span>" +
                "<span class=\"badge badge-light\">Sin precio " + escapeHtml(resumen.sin_precio) + "</span>" +
                "<span class=\"badge badge-light-warning\">Sin costo " + escapeHtml(resumen.sin_costo) + "</span>" +
                "<span class=\"badge badge-light-danger\">Perdida " + escapeHtml(resumen.perdida) + "</span>" +
                "<span class=\"badge badge-light-warning\">Margen bajo " + escapeHtml(resumen.margen_bajo) + "</span>" +
            "</div>" +
            (acciones.length ? "<div class=\"d-flex flex-wrap gap-2 mt-3\">" + acciones.map(function (item) {
                return "<button class=\"btn btn-sm " + item.clase + "\" type=\"button\" data-lp-revision-filtro=\"" + escapeHtml(item.filtro) + "\">" + escapeHtml(item.texto) + " (" + escapeHtml(item.total) + ")</button>";
            }).join("") + "</div>" : "") +
        "</div>";
    }

    function activarAccionesRevisionComercial() {
        document.querySelectorAll("[data-lp-revision-filtro]").forEach(function (boton) {
            boton.addEventListener("click", function () {
                filtrarDesdeRevisionComercial(boton.getAttribute("data-lp-revision-filtro") || "todos");
            });
        });
    }

    function filtrarDesdeRevisionComercial(filtro) {
        var select = document.getElementById("lp_producto_solo");
        if (!select) {
            return;
        }
        select.value = filtro;
        cargarProductos();
        mostrarAlerta("info", "Filtro aplicado desde revision: " + filtro + ".");
    }

    function renderRevisionVacia() {
        estado.revision = null;
        document.getElementById("lp_revision").innerHTML = "<div class=\"text-muted fs-7\">Guarda o selecciona una lista para revisar activacion.</div>";
        actualizarBotonesEstatus();
        actualizarFlujoOperativo("revision");
    }

    function actualizarBotonesEstatus() {
        var activar = document.getElementById("lp_activar");
        var pausar = document.getElementById("lp_pausar");
        var idLista = document.getElementById("lp_lista_id").value;
        var estatus = document.getElementById("lp_lista_estatus").value;
        var pendientes = Object.keys(estado.cambios || {}).length;
        var revision = estado.revision || {};
        var revisionLocal = revisionLocalPantalla();
        var bloqueos = revision.puede_activar === false || (revision.bloqueos || []).length > 0 || revisionLocal.bloqueos.length > 0;
        var avisos = (revision.avisos || []).length > 0 || revisionLocal.avisos.length > 0;
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
        estado.asignacionesClientes = asignaciones || [];
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
        actualizarFlujoOperativo("asignacion");
    }

    function renderAsignacionesSegmentos(asignaciones, schemaDisponible) {
        var contenedor = document.getElementById("lp_asignaciones_segmentos");
        if (!contenedor) {
            return;
        }
        estado.asignacionesSegmentos = asignaciones || [];
        if (!schemaDisponible) {
            estado.segmentosListasDisponible = false;
            actualizarBotonGuardarSegmento();
            contenedor.innerHTML = "<div class=\"alert alert-light-warning py-3 mb-0\">Tabla puente de segmentos pendiente. Ya puedes validar, pero el guardado real espera DDL autorizado.</div>";
            actualizarFlujoOperativo("asignacion");
            return;
        }
        estado.segmentosListasDisponible = true;
        actualizarBotonGuardarSegmento();
        contenedor.innerHTML = (asignaciones || []).map(function (item) {
            var almacen = item.id_almacen && Number(item.id_almacen) > 0 ? ("Alm. " + item.id_almacen) : "Todos";
            return "<div class=\"border rounded p-3 mb-2\">" +
                "<div class=\"d-flex justify-content-between gap-2\"><div class=\"fw-semibold\">" + escapeHtml(item.nombre_segmento || item.codigo_segmento || ("Segmento " + (item.id_segmento_crm || "-"))) + "</div>" + badgeDetalle(item.estatus || "") + "</div>" +
                "<div class=\"text-muted fs-8 mb-2\">" + escapeHtml(item.codigo_segmento || "") + " | " + escapeHtml(item.canal || "general") + " | " + escapeHtml(almacen) + " | prioridad " + escapeHtml(item.prioridad || "100") + "</div>" +
                "<div class=\"d-flex flex-wrap gap-2\">" +
                    botonSegmentoAccion("Cargar", "bi-pencil", "light-primary", item, "") +
                    (item.estatus === "activo" ? botonSegmentoAccion("Pausar", "bi-pause-circle", "light-warning", item, "pausado") : "") +
                    (item.estatus === "pausado" ? botonSegmentoAccion("Activar", "bi-check2-circle", "light-success", item, "activo") : "") +
                    (item.estatus !== "cancelado" ? botonSegmentoAccion("Cancelar", "bi-x-circle", "light-danger", item, "cancelado") : "") +
                "</div>" +
            "</div>";
        }).join("") || "<div class=\"text-muted fs-7\">Sin segmentos vinculados.</div>";

        document.querySelectorAll("[data-lp-seg-cargar]").forEach(function (boton) {
            boton.addEventListener("click", function () {
                document.getElementById("lp_seg_asig_id").value = boton.getAttribute("data-lp-seg-editar") || "";
                document.getElementById("lp_seg_id").value = boton.getAttribute("data-lp-seg-id") || "";
                document.getElementById("lp_seg_nombre").value = boton.getAttribute("data-lp-seg-nombre") || nombreSegmentoPorId(boton.getAttribute("data-lp-seg-id") || "");
                document.getElementById("lp_seg_canal").value = boton.getAttribute("data-lp-seg-canal") || "general";
                document.getElementById("lp_seg_almacen").value = boton.getAttribute("data-lp-seg-almacen") || "";
                document.getElementById("lp_seg_prioridad").value = boton.getAttribute("data-lp-seg-prioridad") || "100";
                document.getElementById("lp_seg_estatus").value = boton.getAttribute("data-lp-seg-estatus") || "activo";
                document.getElementById("lp_seg_inicio").value = boton.getAttribute("data-lp-seg-inicio") || "";
                document.getElementById("lp_seg_fin").value = boton.getAttribute("data-lp-seg-fin") || "";
                validarSegmentoLista();
            });
        });
        document.querySelectorAll("[data-lp-seg-estatus-rapido]").forEach(function (boton) {
            boton.addEventListener("click", function () {
                cambiarEstatusSegmento(
                    boton.getAttribute("data-lp-seg-estatus-rapido") || "",
                    boton
                );
            });
        });
        actualizarFlujoOperativo("asignacion");
    }

    function botonSegmentoAccion(texto, icono, clase, item, estatusRapido) {
        var attrs = " data-lp-seg-editar=\"" + escapeHtml(item.id_segmento_lista_precio || "") + "\"" +
            " data-lp-seg-id=\"" + escapeHtml(item.id_segmento_crm || "") + "\"" +
            " data-lp-seg-nombre=\"" + escapeHtml(item.nombre_segmento || item.codigo_segmento || "") + "\"" +
            " data-lp-seg-canal=\"" + escapeHtml(item.canal || "general") + "\"" +
            " data-lp-seg-almacen=\"" + escapeHtml(item.id_almacen || "") + "\"" +
            " data-lp-seg-prioridad=\"" + escapeHtml(item.prioridad || "100") + "\"" +
            " data-lp-seg-estatus=\"" + escapeHtml(item.estatus || "activo") + "\"" +
            " data-lp-seg-inicio=\"" + escapeHtml(fechaInput(item.fecha_inicio)) + "\"" +
            " data-lp-seg-fin=\"" + escapeHtml(fechaInput(item.fecha_fin)) + "\"";
        if (estatusRapido) {
            attrs += " data-lp-seg-estatus-rapido=\"" + escapeHtml(estatusRapido) + "\"";
        } else {
            attrs += " data-lp-seg-cargar=\"1\"";
        }
        return "<button class=\"btn btn-sm btn-" + escapeHtml(clase) + "\" type=\"button\"" + attrs + "><i class=\"bi " + escapeHtml(icono) + "\"></i> " + escapeHtml(texto) + "</button>";
    }

    function cambiarEstatusSegmento(estatus, boton) {
        if (!estatus || !boton) {
            return;
        }
        var texto = estatus === "cancelado" ? "Cancelar este vinculo por segmento?" : "Cambiar este vinculo a " + estatus + "?";
        if (!window.confirm(texto)) {
            return;
        }
        postRequest("/comercial/listas_precios_segmento_guardar_operativo_erp", {
            id_segmento_lista_precio: boton.getAttribute("data-lp-seg-editar") || "",
            id_segmento_crm: boton.getAttribute("data-lp-seg-id") || "",
            id_lista_precio: document.getElementById("lp_lista_id").value,
            canal: boton.getAttribute("data-lp-seg-canal") || "general",
            id_almacen: boton.getAttribute("data-lp-seg-almacen") || "",
            prioridad: boton.getAttribute("data-lp-seg-prioridad") || "100",
            fecha_inicio: boton.getAttribute("data-lp-seg-inicio") || "",
            fecha_fin: boton.getAttribute("data-lp-seg-fin") || "",
            estatus: estatus,
            motivo: "Cambio operativo de estatus de vinculo segmento/lista"
        }).then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje);
            }
            mostrarAlerta("success", response.mensaje || "Vinculo actualizado.");
            consultarLista(document.getElementById("lp_lista_id").value);
            cargarResumen();
        }).catch(mostrarError);
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

    function actualizarBotonGuardarSegmento() {
        var boton = document.getElementById("lp_seg_guardar");
        if (!boton) {
            return;
        }
        boton.disabled = !estado.segmentosListasDisponible;
        boton.className = estado.segmentosListasDisponible ? "btn btn-primary w-100" : "btn btn-light w-100";
        boton.title = estado.segmentosListasDisponible ? "Guardar vinculo segmento/lista" : "Pendiente DDL erp_segmentos_listas_precios";
    }

    function cargarAuditoria() {
        var idLista = document.getElementById("lp_lista_id").value;
        var tipo = document.getElementById("lp_auditoria_tipo").value || "";
        if (!idLista) {
            renderAuditoriaVacia();
            return;
        }
        var params = {id_lista_precio: idLista, limite: "30"};
        if (tipo === "lista") {
            params.entidad = "erp_listas_precios";
        } else if (tipo === "precio") {
            params.entidad = "erp_listas_precios_detalle";
        } else if (tipo === "cliente") {
            params.entidad = "erp_clientes_listas_precios";
        } else if (tipo === "segmento") {
            params.entidad = "erp_segmentos_listas_precios";
        }
        request("/comercial/listas_precios_auditoria_erp?" + new URLSearchParams(params).toString()).then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje);
            }
            renderAuditoria(response.depurar || {});
        }).catch(mostrarError);
    }

    function cargarAuditoriaSku(idSku, idDetalle) {
        var idLista = document.getElementById("lp_lista_id").value;
        if (!idLista || !idDetalle) {
            mostrarAlerta("warning", "Este SKU aun no tiene historial de precio guardado en la lista.");
            return;
        }
        document.getElementById("lp_auditoria_tipo").value = "precio";
        request("/comercial/listas_precios_auditoria_erp?" + new URLSearchParams({
            id_lista_precio: idLista,
            id_lista_precio_detalle: idDetalle,
            limite: "30"
        }).toString()).then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje);
            }
            var data = response.depurar || {};
            data.titulo_contexto = "Historial SKU " + idSku;
            renderAuditoria(data);
        }).catch(mostrarError);
    }

    function renderAuditoria(data) {
        var eventos = data.eventos || [];
        if (data.schema_pendiente) {
            document.getElementById("lp_auditoria").innerHTML = "<div class=\"alert alert-light-warning py-3 mb-0\">Auditoria comercial pendiente de esquema.</div>";
            return;
        }
        var titulo = data.titulo_contexto ? "<div class=\"alert alert-light-primary py-3 mb-3\"><div class=\"fw-semibold\">" + escapeHtml(data.titulo_contexto) + "</div><div class=\"fs-8\">Eventos filtrados por detalle de precio.</div></div>" : "";
        document.getElementById("lp_auditoria").innerHTML = titulo + ((eventos || []).map(function (item) {
            var cambios = item.cambios_resumen || [];
            var motivo = item.motivo ? "<div class=\"fs-8 mt-1\"><span class=\"text-muted\">Motivo:</span> " + escapeHtml(item.motivo) + "</div>" : "";
            var cambiosHtml = cambios.length ? "<div class=\"mt-2\">" + cambios.map(function (cambio) {
                return "<div class=\"fs-9 text-muted\">" + escapeHtml(cambio.campo || "") + ": " + escapeHtml(cambio.antes || "") + " -> <span class=\"text-dark\">" + escapeHtml(cambio.despues || "") + "</span></div>";
            }).join("") + "</div>" : "";
            return "<div class=\"border rounded p-3 mb-2\">" +
                "<div class=\"d-flex justify-content-between gap-2 align-items-start\">" +
                    "<div><div class=\"fw-semibold\">" + escapeHtml(item.accion_etiqueta || item.accion || "-") + "</div>" +
                    "<div class=\"text-muted fs-8\">" + escapeHtml(item.fecha_registro || "") + " | Usuario " + escapeHtml(item.creado_por || "0") + "</div></div>" +
                    badgeAuditoria(item.accion_tipo || "operacion") +
                "</div>" +
                "<div class=\"fs-8 mt-2\">" + escapeHtml(item.resumen || "") + "</div>" +
                motivo +
                cambiosHtml +
            "</div>";
        }).join("") || "<div class=\"text-muted fs-7\">Sin eventos para esta lista.</div>");
    }

    function badgeAuditoria(tipo) {
        var clases = {
            lista: "badge-light-primary",
            precio: "badge-light-success",
            segmento: "badge-light-info",
            cliente: "badge-light-warning",
            operacion: "badge-light"
        };
        return "<span class=\"badge " + (clases[tipo] || "badge-light") + "\">" + escapeHtml(tipo || "operacion") + "</span>";
    }

    function renderAuditoriaVacia() {
        document.getElementById("lp_auditoria").innerHTML = "<div class=\"text-muted fs-7\">Sin eventos cargados.</div>";
    }

    function mostrarError(error) {
        mostrarAlerta("danger", error.message || String(error));
    }

    document.addEventListener("DOMContentLoaded", function () {
        activarFlujoOperativo();
        activarTabsEditor();
        activarTabsProductos();
        cambiarTabEditor("encabezado");
        cambiarTabProducto("captura", false);
        document.getElementById("lp_recargar").addEventListener("click", function () { cargarResumen(); cargarProductos(); });
        document.getElementById("lp_fase1_recargar").addEventListener("click", cargarFase1Readiness);
        document.getElementById("lp_nueva").addEventListener("click", nuevaLista);
        document.getElementById("lp_filtrar").addEventListener("click", cargarResumen);
        document.getElementById("lp_guardar_lista").addEventListener("click", guardarLista);
        document.getElementById("lp_activar").addEventListener("click", function () { cambiarEstatusLista("activa"); });
        document.getElementById("lp_pausar").addEventListener("click", function () { cambiarEstatusLista("pausada"); });
        document.getElementById("lp_productos_buscar").addEventListener("click", cargarProductos);
        document.getElementById("lp_exportar_csv").addEventListener("click", exportarProductosCsv);
        document.getElementById("lp_importar_prevalidar").addEventListener("click", prevalidarImportacionCsv);
        document.getElementById("lp_importar_aplicar").addEventListener("click", aplicarImportacionCsv);
        document.getElementById("lp_sugerir_margen").addEventListener("click", calcularPreciosSugeridos);
        document.getElementById("lp_usar_sugeridos").addEventListener("click", usarPreciosSugeridos);
        document.getElementById("lp_sugerir_pendientes").addEventListener("click", calcularSugeridosPendientes);
        document.getElementById("lp_usar_pendientes").addEventListener("click", usarSugeridosPendientes);
        document.getElementById("lp_aplicar_margen").addEventListener("click", aplicarMargenObjetivo);
        document.getElementById("lp_copiar_general").addEventListener("click", copiarPrecioGeneralVisibles);
        document.getElementById("lp_redondear").addEventListener("click", redondearPreciosVisibles);
        document.getElementById("lp_comparar_lista").addEventListener("click", compararListaOrigen);
        document.getElementById("lp_aplicar_comparacion").addEventListener("click", aplicarDiferenciasComparacion);
        document.getElementById("lp_copiar_lista").addEventListener("click", copiarPreciosDesdeLista);
        document.getElementById("lp_limpiar_cambios").addEventListener("click", limpiarCambiosPendientes);
        document.getElementById("lp_prevalidar_cambios").addEventListener("click", prevalidarCambiosPendientes);
        document.getElementById("lp_guardar_cambios").addEventListener("click", guardarCambiosLote);
        document.getElementById("lp_ver_modificados").addEventListener("click", verProductosModificados);
        document.getElementById("lp_prevalidar_cambios_top").addEventListener("click", prevalidarCambiosPendientes);
        document.getElementById("lp_limpiar_cambios_top").addEventListener("click", limpiarCambiosPendientes);
        document.getElementById("lp_cliente_buscar").addEventListener("click", buscarClientesCrm);
        document.getElementById("lp_segmentos_recargar").addEventListener("click", cargarSegmentosCrm);
        document.getElementById("lp_seg_nuevo").addEventListener("click", limpiarSegmentoSeleccionado);
        document.getElementById("lp_seg_validar").addEventListener("click", validarSegmentoLista);
        document.getElementById("lp_seg_guardar").addEventListener("click", guardarSegmentoLista);
        document.getElementById("lp_guardar_asig").addEventListener("click", guardarAsignacion);
        document.getElementById("lp_preview_btn").addEventListener("click", previsualizarPrecio);
        document.getElementById("lp_revision_btn").addEventListener("click", cargarRevision);
        document.getElementById("lp_auditoria_btn").addEventListener("click", cargarAuditoria);
        document.getElementById("lp_auditoria_tipo").addEventListener("change", cargarAuditoria);
        document.querySelectorAll("[data-lp-alcance]").forEach(function (boton) {
            boton.addEventListener("click", function () {
                aplicarPresetAlcance(boton.getAttribute("data-lp-alcance") || "general");
            });
        });
        ["lp_lista_codigo", "lp_lista_nombre", "lp_lista_estatus"].forEach(function (id) {
            document.getElementById(id).addEventListener("input", function () { actualizarFlujoOperativo("encabezado"); });
            document.getElementById(id).addEventListener("change", function () { actualizarFlujoOperativo("encabezado"); });
        });
        document.getElementById("lp_productos_select_all").addEventListener("change", function () {
            seleccionarProductosVisibles(this.checked);
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
        document.getElementById("lp_producto_solo").addEventListener("change", cargarProductos);
        document.getElementById("lp_margen_minimo").addEventListener("input", function () {
            actualizarResumenProductos(estado.productosVisibles || []);
            (estado.productosVisibles || []).forEach(function (item) {
                recalcularMargenLocal(item.id_sku);
            });
        });
        document.getElementById("lp_margen_minimo").addEventListener("keyup", function (event) {
            if (event.key === "Enter") { cargarProductos(); }
        });
        document.getElementById("lp_cliente_q").addEventListener("keyup", function (event) {
            if (event.key === "Enter") { buscarClientesCrm(); }
        });
        cargarFase1Readiness();
        cargarResumen();
        var params = new URLSearchParams(window.location.search || "");
        var idInicial = params.get("id_lista_precio") || params.get("id");
        if (idInicial) {
            consultarLista(idInicial);
        } else {
            nuevaLista();
        }
    });
})();
