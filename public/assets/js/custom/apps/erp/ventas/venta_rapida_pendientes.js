"use strict";
(function () {
    var estado = {pendientes: [], detalle: null, skuSeleccionado: null};

    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-30
     * Proposito: atender pendientes de venta rapida POS y vincularlos a SKU definitivo.
     * Impacto: convierte alertas de Catalogo en una bandeja resolutiva sin crear SKU automatico.
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

    function dinero(value) {
        return new Intl.NumberFormat("es-MX", {style: "currency", currency: "MXN"}).format(Number(value || 0));
    }

    function badge(valor) {
        var texto = String(valor || "-");
        var clase = texto === "clasificado" ? "badge-light-success" : (texto === "en_revision" ? "badge-light-info" : "badge-light-warning");
        return "<span class=\"badge " + clase + " vrp-pill\">" + escapeHtml(texto.replace(/_/g, " ")) + "</span>";
    }

    function filtros() {
        var estatus = document.getElementById("vrp_estatus").value || "pendiente_catalogo";
        var inventario = document.getElementById("vrp_inventario").value || "todos";
        var q = document.getElementById("vrp_busqueda").value.trim();
        return {
            estatus: estatus,
            inventario_estado: inventario,
            q: q,
            limite: "80"
        };
    }

    function consultarPendientes() {
        var params = new URLSearchParams(filtros());
        setListaLoading("Consultando pendientes...");
        request("/ventas/pos_venta_rapida_pendientes_erp?" + params.toString()).then(function (response) {
            if (response.error) { throw new Error(response.mensaje || "No se pudo consultar"); }
            estado.pendientes = (response.depurar || {}).pendientes || [];
            renderLista(response);
        }).catch(mostrarErrorLista);
    }

    function cargarFolio(folio) {
        if (!folio) { return; }
        setDetalleLoading("Consultando " + folio + "...");
        request("/ventas/pos_venta_rapida_pendiente_erp?folio=" + encodeURIComponent(folio)).then(function (response) {
            if (response.error) { throw new Error(response.mensaje || "No se pudo consultar detalle"); }
            estado.detalle = response.depurar || {};
            estado.skuSeleccionado = null;
            renderDetalle();
        }).catch(mostrarErrorDetalle);
    }

    function renderLista(response) {
        var data = response.depurar || {};
        var filas = data.pendientes || [];
        document.getElementById("vrp_resumen").textContent = String(filas.length) + " registro(s)";
        if (!filas.length) {
            document.getElementById("vrp_lista").innerHTML = empty("Sin pendientes en este filtro");
            return;
        }
        document.getElementById("vrp_lista").innerHTML = filas.map(function (item) {
            return "<div class=\"vrp-pending-row border rounded p-3 mb-2\" data-vrp-folio=\"" + escapeHtml(item.folio || "") + "\">" +
                "<div class=\"d-flex justify-content-between gap-2\">" +
                "<div><div class=\"fw-bold\">" + escapeHtml(item.folio || "-") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.folio_venta || "") + " | " + escapeHtml(item.fecha_registro || "") + "</div></div>" +
                "<div class=\"text-end\">" + badge(item.estatus) + "<div class=\"fw-bold mt-1\">" + dinero(item.total || 0) + "</div></div>" +
                "</div>" +
                "<div class=\"mt-2 fs-7\">" + escapeHtml(item.descripcion_manual || "-") + "</div>" +
                "<div class=\"d-flex flex-wrap gap-2 mt-2 fs-8 text-muted\">" +
                "<span>Cant. " + escapeHtml(item.cantidad || "0") + "</span>" +
                "<span>Inv. " + escapeHtml((item.inventario_estado || "-").replace(/_/g, " ")) + "</span>" +
                (item.proveedor_provisional ? "<span>Prov. " + escapeHtml(item.proveedor_provisional) + "</span>" : "") +
                "</div></div>";
        }).join("");
    }

    function renderDetalle() {
        var data = estado.detalle || {};
        var pendiente = data.pendiente || {};
        var eventos = data.eventos || [];
        if (!pendiente.folio) {
            document.getElementById("vrp_detalle").innerHTML = empty("Selecciona un pendiente");
            return;
        }
        var abierto = ["pendiente_catalogo", "en_revision"].indexOf(String(pendiente.estatus || "")) >= 0;
        var html = "<div class=\"row g-4\">" +
            "<div class=\"col-lg-6\"><div class=\"border rounded p-4 h-100\">" +
            "<div class=\"d-flex justify-content-between gap-3 mb-3\"><div><div class=\"fw-bold fs-5\">" + escapeHtml(pendiente.folio || "-") + "</div><div class=\"text-muted fs-8\">Venta " + escapeHtml(pendiente.folio_venta || "-") + "</div></div>" +
            "<div class=\"text-end\">" + badge(pendiente.estatus) + "<div class=\"fw-bold mt-1\">" + dinero(pendiente.total || 0) + "</div></div></div>" +
            "<div class=\"fs-7 mb-3\"><span class=\"text-muted\">Descripcion POS:</span><br><span class=\"fw-semibold\">" + escapeHtml(pendiente.descripcion_manual || "-") + "</span></div>" +
            "<div class=\"row g-3 fs-7\">" +
            campo("Cantidad", pendiente.cantidad) + campo("Precio", dinero(pendiente.precio_unitario || 0)) +
            campo("Codigo barras", pendiente.codigo_barras || "-") + campo("Proveedor provisional", pendiente.proveedor_provisional || "-") +
            campo("Marca provisional", pendiente.marca_provisional || "-") + campo("Categoria provisional", pendiente.categoria_provisional || "-") +
            campo("Inventario", String(pendiente.inventario_estado || "-").replace(/_/g, " ")) + campo("Operador", pendiente.id_usuario_operador || "-") +
            "</div></div></div>" +
            "<div class=\"col-lg-6\"><div class=\"border rounded p-4 h-100\">" +
            "<div class=\"fw-bold fs-5 mb-1\">Resolver contra SKU</div>" +
            "<div class=\"text-muted fs-7 mb-4\">Busca el SKU definitivo. Si no existe, solicita a Catalogo un borrador con la informacion capturada en POS.</div>" +
            "<button class=\"btn btn-light-warning w-100 mb-4\" id=\"vrp_solicitar_borrador\" type=\"button\"" + (abierto ? "" : " disabled") + "><i class=\"bi bi-send-plus\"></i> Solicitar borrador a Catalogo</button>" +
            "<label class=\"form-label text-muted fs-8 text-uppercase\">Buscar SKU</label>" +
            "<div class=\"input-group mb-3\"><input class=\"form-control form-control-solid\" id=\"vrp_sku_busqueda\" placeholder=\"SKU, codigo o nombre\"><button class=\"btn btn-light-primary\" id=\"vrp_sku_buscar\" type=\"button\"><i class=\"bi bi-search\"></i></button></div>" +
            "<div id=\"vrp_sku_resultados\" class=\"mb-4\"></div>" +
            "<label class=\"form-label text-muted fs-8 text-uppercase\">SKU seleccionado</label>" +
            "<input class=\"form-control form-control-solid mb-3\" id=\"vrp_sku_id\" inputmode=\"numeric\" placeholder=\"ID SKU\" value=\"" + escapeHtml(pendiente.id_sku_erp_resuelto || "") + "\">" +
            "<label class=\"form-label text-muted fs-8 text-uppercase\">Motivo</label>" +
            "<textarea class=\"form-control form-control-solid mb-3\" id=\"vrp_motivo\" rows=\"3\" placeholder=\"Ej. Producto vendido rapido, clasificado contra SKU correcto\"></textarea>" +
            "<div class=\"d-grid gap-2\">" +
            "<button class=\"btn btn-light-primary\" id=\"vrp_simular\" type=\"button\"" + (abierto ? "" : " disabled") + "><i class=\"bi bi-clipboard-check\"></i> Simular vinculo</button>" +
            "<button class=\"btn btn-primary\" id=\"vrp_resolver\" type=\"button\"" + (abierto ? "" : " disabled") + "><i class=\"bi bi-check2-circle\"></i> Resolver pendiente</button>" +
            "</div><div id=\"vrp_resolucion_resultado\" class=\"mt-4\"></div>" +
            "</div></div></div>";
        html += "<div class=\"mt-4\"><div class=\"fw-bold fs-5 mb-3\">Eventos</div>" + renderEventos(eventos) + "</div>";
        document.getElementById("vrp_detalle").innerHTML = html;
    }

    function campo(label, value) {
        return "<div class=\"col-sm-6\"><div class=\"text-muted fs-8 text-uppercase\">" + escapeHtml(label) + "</div><div class=\"fw-semibold\">" + escapeHtml(value == null || value === "" ? "-" : value) + "</div></div>";
    }

    function renderEventos(eventos) {
        if (!eventos.length) { return empty("Sin eventos registrados"); }
        return eventos.map(function (item) {
            return "<div class=\"border rounded p-3 mb-2\"><div class=\"d-flex justify-content-between gap-2\"><div class=\"fw-semibold\">" + escapeHtml(item.tipo_evento || "-") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.fecha_registro || "") + "</div></div>" +
                "<div class=\"text-muted fs-8\">" + escapeHtml(item.estatus_anterior || "-") + " -> " + escapeHtml(item.estatus_nuevo || "-") + "</div>" +
                "<div class=\"fs-7 mt-1\">" + escapeHtml(item.resumen || item.motivo || "") + "</div></div>";
        }).join("");
    }

    function buscarSku() {
        var q = document.getElementById("vrp_sku_busqueda").value.trim();
        if (q.length < 2) {
            document.getElementById("vrp_sku_resultados").innerHTML = "<div class=\"alert alert-light-warning py-2 fs-8\">Escribe al menos dos caracteres.</div>";
            return;
        }
        var params = new URLSearchParams({q: q, limite: "8"});
        request("/ventas/pos_buscar_skus_erp?" + params.toString()).then(function (response) {
            if (response.error) { throw new Error(response.mensaje || "No se pudo buscar SKU"); }
            renderSkus(response.depurar || []);
        }).catch(function (error) {
            document.getElementById("vrp_sku_resultados").innerHTML = "<div class=\"alert alert-warning py-2 fs-8\">" + escapeHtml(error.message || String(error)) + "</div>";
        });
    }

    function renderSkus(items) {
        if (!items.length) {
            document.getElementById("vrp_sku_resultados").innerHTML = "<div class=\"alert alert-light py-2 fs-8\">Sin resultados. Crea el SKU en Catalogo ERP y vuelve a buscarlo.</div>";
            return;
        }
        document.getElementById("vrp_sku_resultados").innerHTML = items.map(function (item) {
            return "<button class=\"btn btn-light text-start w-100 mb-2\" type=\"button\" data-vrp-sku=\"" + escapeHtml(item.id_sku || "") + "\">" +
                "<div class=\"fw-bold\">" + escapeHtml(item.sku || "-") + " | " + escapeHtml(item.nombre_sku || item.producto || "-") + "</div>" +
                "<div class=\"text-muted fs-8\">ID " + escapeHtml(item.id_sku || "-") + " | " + dinero(item.precio || 0) + " | Disp. " + escapeHtml(item.existencia_disponible || "0") + "</div></button>";
        }).join("");
    }

    function datosResolucion() {
        var pendiente = (estado.detalle || {}).pendiente || {};
        return {
            folio: pendiente.folio || "",
            id_sku: document.getElementById("vrp_sku_id").value.trim(),
            decision_inventario: "mantener_pendiente_regularizacion",
            motivo: document.getElementById("vrp_motivo").value.trim()
        };
    }

    function simularResolucion() {
        setResolucionLoading("Simulando vinculo...");
        request("/ventas/pos_venta_rapida_resolucion_dryrun_erp", datosResolucion()).then(renderResultadoResolucion).catch(mostrarErrorResolucion);
    }

    function solicitarBorradorCatalogo() {
        var pendiente = (estado.detalle || {}).pendiente || {};
        if (!pendiente.folio) { return; }
        var motivo = document.getElementById("vrp_motivo") ? document.getElementById("vrp_motivo").value.trim() : "";
        if (!motivo) {
            motivo = "Solicitar borrador de Catalogo desde venta rapida POS";
        }
        var confirmar = window.Swal
            ? Swal.fire({
                text: "Se enviara una tarea a Catalogo con los datos capturados en POS. No se creara SKU automaticamente.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Enviar a Catalogo",
                cancelButtonText: "Cancelar"
            }).then(function (r) { return !!r.isConfirmed; })
            : Promise.resolve(window.confirm("Enviar solicitud de borrador a Catalogo?"));
        confirmar.then(function (ok) {
            if (!ok) { return; }
            setResolucionLoading("Enviando solicitud a Catalogo...");
            request("/ventas/pos_venta_rapida_solicitar_borrador_catalogo_erp", {
                folio: pendiente.folio,
                motivo: motivo
            }).then(function (response) {
                renderResultadoBorrador(response);
                if (!response.error) { cargarFolio(pendiente.folio); }
            }).catch(mostrarErrorResolucion);
        });
    }

    function renderResultadoBorrador(response) {
        var data = response.depurar || {};
        var tipo = response.error ? "alert-warning" : "alert-success";
        var html = "<div class=\"alert " + tipo + " py-3\"><div class=\"fw-bold\">" + escapeHtml(response.mensaje || "Solicitud enviada") + "</div>";
        if (data.id_incidencia_calidad) {
            html += "<div class=\"fs-8 mt-2\">Incidencia Catalogo: <strong>#" + escapeHtml(data.id_incidencia_calidad) + "</strong></div>";
        }
        if (data.url_catalogo) {
            html += "<div class=\"mt-3\"><a class=\"btn btn-sm btn-light-primary\" href=\"" + escapeHtml(data.url_catalogo) + "\" target=\"_blank\"><i class=\"bi bi-box-seam\"></i> Ver en Catalogo</a></div>";
        }
        html += "<div class=\"fs-8 text-muted mt-2\">La venta rapida sigue abierta hasta vincularla a un SKU definitivo.</div></div>";
        document.getElementById("vrp_resolucion_resultado").innerHTML = html;
    }

    function resolverReal() {
        var datos = datosResolucion();
        if (!datos.id_sku || !datos.motivo) {
            mostrarErrorResolucion(new Error("Selecciona SKU y captura motivo antes de resolver."));
            return;
        }
        if (!window.confirm("Esto cerrara el pendiente de venta rapida y lo vinculara al SKU seleccionado. No movera kardex. Continuar?")) {
            return;
        }
        datos.token = "VENTAS_POS_VENTA_RAPIDA_RESOLVER_REAL";
        datos.confirmacion = "RESOLVER VENTA RAPIDA POS";
        setResolucionLoading("Resolviendo pendiente...");
        request("/ventas/pos_venta_rapida_resolver_erp", datos).then(function (response) {
            renderResultadoResolucion(response);
            if (!response.error) {
                cargarFolio(datos.folio);
                consultarPendientes();
            }
        }).catch(mostrarErrorResolucion);
    }

    function renderResultadoResolucion(response) {
        var data = response.depurar || {};
        var bloqueos = data.bloqueos || [];
        var avisos = data.avisos || [];
        var tipo = response.error ? "alert-warning" : (response.tipo === "success" ? "alert-success" : "alert-info");
        var html = "<div class=\"alert " + tipo + " py-3\"><div class=\"fw-bold\">" + escapeHtml(response.mensaje || "Resultado") + "</div>";
        if (bloqueos.length) {
            html += "<div class=\"mt-2 fs-8\"><strong>Bloqueos:</strong><br>" + bloqueos.map(escapeHtml).join("<br>") + "</div>";
        }
        if (avisos.length) {
            html += "<div class=\"mt-2 fs-8\"><strong>Avisos:</strong><br>" + avisos.map(escapeHtml).join("<br>") + "</div>";
        }
        if (data.sku) {
            html += "<div class=\"mt-2 fs-8\">SKU: <strong>" + escapeHtml(data.sku.sku || data.sku.id_sku || "-") + "</strong></div>";
        }
        if (data.inventario_estado) {
            html += "<div class=\"mt-2 fs-8\">Inventario: " + escapeHtml(String(data.inventario_estado).replace(/_/g, " ")) + "</div>";
        }
        html += "</div>";
        document.getElementById("vrp_resolucion_resultado").innerHTML = html;
    }

    function setListaLoading(texto) {
        document.getElementById("vrp_lista").innerHTML = "<div class=\"text-muted fs-8 py-4\">" + escapeHtml(texto) + "</div>";
    }

    function setDetalleLoading(texto) {
        document.getElementById("vrp_detalle").innerHTML = "<div class=\"text-muted fs-8 py-4\">" + escapeHtml(texto) + "</div>";
    }

    function setResolucionLoading(texto) {
        document.getElementById("vrp_resolucion_resultado").innerHTML = "<div class=\"text-muted fs-8 py-2\">" + escapeHtml(texto) + "</div>";
    }

    function empty(texto) {
        return "<div class=\"vrp-empty d-flex align-items-center justify-content-center text-center text-muted\"><div><i class=\"bi bi-folder2-open fs-1 d-block mb-3\"></i><div class=\"fw-semibold\">" + escapeHtml(texto) + "</div></div></div>";
    }

    function mostrarErrorLista(error) {
        document.getElementById("vrp_lista").innerHTML = "<div class=\"alert alert-warning py-3\">" + escapeHtml(error.message || String(error)) + "</div>";
    }

    function mostrarErrorDetalle(error) {
        document.getElementById("vrp_detalle").innerHTML = "<div class=\"alert alert-warning py-3\">" + escapeHtml(error.message || String(error)) + "</div>";
    }

    function mostrarErrorResolucion(error) {
        document.getElementById("vrp_resolucion_resultado").innerHTML = "<div class=\"alert alert-warning py-3\">" + escapeHtml(error.message || String(error)) + "</div>";
    }

    document.addEventListener("DOMContentLoaded", function () {
        document.getElementById("vrp_consultar").addEventListener("click", consultarPendientes);
        document.getElementById("vrp_recargar").addEventListener("click", consultarPendientes);
        document.getElementById("vrp_busqueda").addEventListener("keydown", function (event) {
            if (event.key === "Enter") { consultarPendientes(); }
        });
        document.getElementById("vrp_lista").addEventListener("click", function (event) {
            var boton = event.target.closest("[data-vrp-folio]");
            if (boton) { cargarFolio(boton.getAttribute("data-vrp-folio") || ""); }
        });
        document.getElementById("vrp_detalle").addEventListener("click", function (event) {
            if (event.target.closest("#vrp_sku_buscar")) { buscarSku(); return; }
            if (event.target.closest("#vrp_solicitar_borrador")) { solicitarBorradorCatalogo(); return; }
            if (event.target.closest("#vrp_simular")) { simularResolucion(); return; }
            if (event.target.closest("#vrp_resolver")) { resolverReal(); return; }
            var sku = event.target.closest("[data-vrp-sku]");
            if (sku) {
                document.getElementById("vrp_sku_id").value = sku.getAttribute("data-vrp-sku") || "";
            }
        });
        document.getElementById("vrp_detalle").addEventListener("keydown", function (event) {
            if (event.key === "Enter" && event.target && event.target.id === "vrp_sku_busqueda") {
                buscarSku();
            }
        });
        if (window.VRP_FOLIO_INICIAL) {
            document.getElementById("vrp_busqueda").value = window.VRP_FOLIO_INICIAL;
            cargarFolio(window.VRP_FOLIO_INICIAL);
        }
        consultarPendientes();
    });
})();
