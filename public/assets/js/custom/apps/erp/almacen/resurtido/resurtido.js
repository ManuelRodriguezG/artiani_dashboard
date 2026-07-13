"use strict";
(function () {
    var almacenes = [];

    function $(id) { return document.getElementById(id); }
    function escapeHtml(value) {
        var div = document.createElement("div");
        div.textContent = value == null ? "" : String(value);
        return div.innerHTML;
    }
    function request(url) {
        return fetch(url, {credentials: "same-origin"}).then(function (response) { return response.json(); });
    }
    function numero(value) {
        return Number(value || 0).toLocaleString("es-MX", {minimumFractionDigits: 0, maximumFractionDigits: 6});
    }
    function estadoBadge(estado) {
        var mapa = {
            borrador: "secondary",
            solicitado: "primary",
            autorizado: "info",
            rechazado: "danger",
            preparando: "warning",
            preparado: "success",
            enviado: "dark",
            recibido_parcial: "warning",
            recibido: "success",
            cerrado: "secondary",
            cancelado: "danger"
        };
        var clase = mapa[estado] || "secondary";
        return "<span class=\"badge badge-light-" + clase + "\">" + escapeHtml(estado || "-") + "</span>";
    }
    function renderAlmacenes() {
        var opciones = almacenes.map(function (item) {
            var etiqueta = item.codigo_almacen ? item.codigo_almacen + " - " + item.almacen : item.almacen;
            return "<option value=\"" + escapeHtml(item.id_almacen) + "\">" + escapeHtml(etiqueta) + "</option>";
        }).join("");
        $("alm_res_stock_almacen").innerHTML = opciones || "<option value=\"\">Sin almacenes activos</option>";
        renderOrigenes();
    }
    function renderOrigenes() {
        var destino = $("alm_res_stock_almacen").value || "";
        var opciones = "<option value=\"\">Automatico</option>" + almacenes
            .filter(function (item) { return String(item.id_almacen) !== String(destino); })
            .map(function (item) {
                var etiqueta = item.codigo_almacen ? item.codigo_almacen + " - " + item.almacen : item.almacen;
                return "<option value=\"" + escapeHtml(item.id_almacen) + "\">" + escapeHtml(etiqueta) + "</option>";
            }).join("");
        $("alm_res_stock_origen").innerHTML = opciones;
    }
    function cargarAlmacenes() {
        return request("/almacen/consultar_almacenes?permite_venta=1").then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            almacenes = response.depurar || [];
            if (!almacenes.length) {
                return request("/almacen/consultar_almacenes").then(function (fallback) {
                    if (fallback.error) { throw new Error(fallback.mensaje); }
                    almacenes = fallback.depurar || [];
                    renderAlmacenes();
                });
            }
            renderAlmacenes();
        });
    }
    function renderSchemaPendiente(payload) {
        var alert = $("alm_res_schema_alert");
        if (!payload || !payload.schema_pendiente) {
            alert.classList.add("d-none");
            alert.innerHTML = "";
            return;
        }
        alert.classList.remove("d-none");
        alert.innerHTML = "<div class=\"fw-bold\">Esquema de resurtido pendiente</div>" +
            "<div>La pantalla esta en modo lectura. Falta aplicar el DDL autorizado con respaldo externo antes de crear solicitudes.</div>";
    }
    function cargarResurtidos() {
        var params = new URLSearchParams({
            estatus: $("alm_res_estatus").value || "",
            q: $("alm_res_q").value || ""
        });
        $("alm_res_body").innerHTML = "<tr><td colspan=\"9\" class=\"text-muted\">Cargando...</td></tr>";
        return request("/almacen/resurtido_listar_erp?" + params.toString()).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            var payload = response.depurar || {};
            renderSchemaPendiente(payload);
            var rows = payload.items || [];
            if (!rows.length) {
                $("alm_res_body").innerHTML = "<tr><td colspan=\"9\" class=\"text-muted\">Sin solicitudes de resurtido.</td></tr>";
                return;
            }
            $("alm_res_body").innerHTML = rows.map(function (item) {
                return "<tr>" +
                    "<td><button class=\"btn btn-link p-0 fw-bold\" type=\"button\" data-res-detalle=\"" + escapeHtml(item.id_resurtido_almacen || item.id_resurtido || "") + "\">" + escapeHtml(item.folio || "-") + "</button><div class=\"text-muted fs-8\">" + escapeHtml(item.fecha_solicitud || "") + "</div></td>" +
                    "<td>" + escapeHtml(item.almacen_origen || "-") + "</td>" +
                    "<td>" + escapeHtml(item.almacen_destino || "-") + "</td>" +
                    "<td>" + estadoBadge(item.estatus) + "</td>" +
                    "<td class=\"text-end\">" + numero(item.total_partidas) + "</td>" +
                    "<td class=\"text-end\">" + numero(item.cantidad_solicitada) + "</td>" +
                    "<td class=\"text-end\">" + numero(item.cantidad_enviada) + "</td>" +
                    "<td class=\"text-end\">" + numero(item.cantidad_recibida) + "</td>" +
                    "<td class=\"text-end\">" + numero(item.total_diferencias) + "</td>" +
                    "</tr>";
            }).join("");
            enlazarDetalle();
        }).catch(function (error) {
            $("alm_res_body").innerHTML = "<tr><td colspan=\"9\" class=\"text-danger\">" + escapeHtml(error.message) + "</td></tr>";
        });
    }
    function cargarDetalle(id) {
        if (!id) { return; }
        $("alm_res_detalle").innerHTML = "Consultando folio...";
        return request("/almacen/resurtido_consultar_erp?id_resurtido_almacen=" + encodeURIComponent(id)).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            var payload = response.depurar || {};
            renderSchemaPendiente(payload);
            if (payload.schema_pendiente) {
                $("alm_res_detalle").innerHTML = "<div class=\"text-muted\">El detalle estara disponible cuando se aplique el esquema autorizado.</div>";
                return;
            }
            var h = payload.encabezado || {};
            $("alm_res_detalle").innerHTML =
                "<div class=\"d-flex justify-content-between align-items-start mb-3\">" +
                "<div><div class=\"fw-bold\">" + escapeHtml(h.folio || "-") + "</div>" +
                "<div class=\"text-muted fs-8\">" + escapeHtml(h.almacen_origen || "-") + " -> " + escapeHtml(h.almacen_destino || h.almacen_solicitante || "-") + "</div></div>" +
                estadoBadge(h.estatus) + "</div>" +
                "<div class=\"d-flex flex-wrap gap-2 mb-4\">" +
                "<span class=\"badge badge-light-primary\">" + numero((payload.detalle || []).length) + " partidas</span>" +
                "<span class=\"badge badge-light-success\">" + numero((payload.preparacion || []).length) + " preparaciones</span>" +
                "<span class=\"badge badge-light-dark\">" + numero((payload.envios || []).length) + " envios</span>" +
                "<span class=\"badge badge-light-info\">" + numero((payload.recepciones || []).length) + " recepciones</span>" +
                "<span class=\"badge badge-light-warning\">" + numero((payload.diferencias || []).length) + " diferencias</span>" +
                "</div>" +
                renderPartidas(payload.detalle || []) +
                renderDiferencias(payload.diferencias || []);
        }).catch(function (error) {
            $("alm_res_detalle").innerHTML = "<span class=\"text-danger\">" + escapeHtml(error.message) + "</span>";
        });
    }
    function renderPartidas(rows) {
        if (!rows.length) {
            return "<div class=\"text-muted\">Sin partidas.</div>";
        }
        return "<div class=\"fw-bold mb-2\">Partidas</div>" + rows.map(function (item) {
            return "<div class=\"border-bottom py-2\">" +
                "<div class=\"fw-bold\">" + escapeHtml(item.sku || item.id_sku_erp || "-") + "</div>" +
                "<div class=\"text-muted fs-8\">" + escapeHtml(item.nombre_producto || "") + "</div>" +
                "<div class=\"d-flex flex-wrap gap-2 mt-2\">" +
                "<span class=\"badge badge-light-primary\">Sol. " + numero(item.cantidad_solicitada) + "</span>" +
                "<span class=\"badge badge-light-info\">Aut. " + numero(item.cantidad_autorizada) + "</span>" +
                "<span class=\"badge badge-light-dark\">Env. " + numero(item.cantidad_enviada) + "</span>" +
                "<span class=\"badge badge-light-success\">Rec. " + numero(item.cantidad_recibida) + "</span>" +
                estadoBadge(item.estatus) +
                "</div></div>";
        }).join("");
    }
    function renderDiferencias(rows) {
        if (!rows.length) { return ""; }
        return "<div class=\"fw-bold mt-4 mb-2\">Diferencias</div>" + rows.map(function (item) {
            return "<div class=\"border-bottom py-2\">" +
                "<div class=\"fw-bold\">" + escapeHtml(item.tipo_diferencia || "-") + "</div>" +
                "<div class=\"text-muted fs-8\">Esperado " + numero(item.cantidad_esperada) + " | Recibido " + numero(item.cantidad_recibida) + " | Dif. " + numero(item.cantidad_diferencia) + "</div>" +
                "<div class=\"mt-2\">" + estadoBadge(item.estatus) + "</div>" +
                "</div>";
        }).join("");
    }
    function enlazarDetalle() {
        Array.prototype.forEach.call(document.querySelectorAll("[data-res-detalle]"), function (button) {
            button.addEventListener("click", function () {
                cargarDetalle(button.getAttribute("data-res-detalle"));
            });
        });
    }
    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-11
     * Proposito: consulta stock bajo por tienda/SKU sin crear solicitudes de resurtido.
     * Impacto: UI Almacen/Resurtido; usa reglas globales hasta que existan politicas locales por tienda/SKU.
     * Contrato: GET read-only; no mueve inventario ni persiste alertas.
     */
    function consultarStockBajo() {
        var params = new URLSearchParams({
            id_almacen: $("alm_res_stock_almacen").value || "",
            q: $("alm_res_stock_q").value || "",
            solo_bajos: $("alm_res_stock_solo_bajos").checked ? "1" : "0"
        });
        $("alm_res_stock_resumen").innerHTML = "Consultando...";
        return request("/almacen/resurtido_stock_bajo_preflight_erp?" + params.toString()).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            var payload = response.depurar || {};
            var rows = payload.items || [];
            var header = "<div class=\"d-flex justify-content-between align-items-center mb-3\">" +
                "<div><div class=\"fw-bold\">" + escapeHtml(payload.almacen ? payload.almacen.almacen : "") + "</div>" +
                "<div class=\"text-muted fs-8\">Politica " + escapeHtml(rows.length ? rows[0].politica_fuente : "catalogo_global") + "</div></div>" +
                "<span class=\"badge badge-light-primary\">" + numero(payload.total) + " SKUs</span></div>";
            if (!rows.length) {
                $("alm_res_stock_resumen").innerHTML = header + "<div class=\"text-muted\">Sin SKUs bajo reorden con estos filtros.</div>";
                return;
            }
            $("alm_res_stock_resumen").innerHTML = header + rows.map(function (item) {
                return "<div class=\"border-bottom py-3\">" +
                    "<div class=\"fw-bold\">" + escapeHtml(item.sku) + "</div>" +
                    "<div class=\"text-muted fs-8\">" + escapeHtml(item.producto || item.nombre_sku || "") + "</div>" +
                    "<div class=\"d-flex flex-wrap gap-2 mt-2\">" +
                    "<span class=\"badge badge-light-success\">Disp. " + numero(item.cantidad_disponible) + " " + escapeHtml(item.unidad_base || "") + "</span>" +
                    "<span class=\"badge badge-light-warning\">Umbral " + numero(item.umbral_usado) + "</span>" +
                    "<span class=\"badge badge-light-primary\">Sugerido " + numero(item.cantidad_sugerida) + "</span>" +
                    "</div></div>";
            }).join("");
        }).catch(function (error) {
            $("alm_res_stock_resumen").innerHTML = "<span class=\"text-danger\">" + escapeHtml(error.message) + "</span>";
        });
    }
    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-12
     * Proposito: arma una solicitud preview desde stock bajo sin persistir folio ni partidas.
     * Impacto: UI Almacen/Resurtido; prepara captura real posterior a DDL/autorizacion.
     * Contrato: GET read-only; no crea solicitud, no aparta stock y no mueve inventario.
     */
    function simularSolicitud() {
        var params = new URLSearchParams({
            id_almacen_destino: $("alm_res_stock_almacen").value || "",
            id_almacen_origen: $("alm_res_stock_origen").value || "",
            q: $("alm_res_stock_q").value || ""
        });
        $("alm_res_simulacion").innerHTML = "Simulando solicitud...";
        return request("/almacen/resurtido_simular_solicitud_erp?" + params.toString()).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            var payload = response.depurar || {};
            var rows = payload.lineas || [];
            if (!rows.length) {
                $("alm_res_simulacion").innerHTML = "<div class=\"text-muted\">Sin partidas sugeridas para esta tienda.</div>";
                return;
            }
            $("alm_res_simulacion").innerHTML =
                "<div class=\"d-flex justify-content-between align-items-start mb-3\">" +
                "<div><div class=\"fw-bold\">" + escapeHtml(payload.folio_preview || "-") + "</div>" +
                "<div class=\"text-muted fs-8\">" + escapeHtml(payload.almacen_origen ? payload.almacen_origen.almacen : "-") + " -> " + escapeHtml(payload.almacen_destino ? payload.almacen_destino.almacen : "-") + "</div></div>" +
                "<span class=\"badge badge-light-info\">Read-only</span></div>" +
                "<div class=\"d-flex flex-wrap gap-2 mb-3\">" +
                "<span class=\"badge badge-light-primary\">" + numero(payload.total_partidas) + " partidas</span>" +
                "<span class=\"badge badge-light-success\">Total " + numero(payload.cantidad_total_sugerida) + "</span>" +
                "<span class=\"badge badge-light-dark\">Surtible " + numero(payload.cantidad_total_surtible_origen) + "</span>" +
                "<span class=\"badge badge-light-warning\">" + numero(payload.partidas_con_origen_insuficiente) + " insuf.</span>" +
                "</div>" +
                rows.map(function (item) {
                    var cobertura = Number(item.puede_surtir_origen || 0) === 1
                        ? "<span class=\"badge badge-light-success\">Origen suficiente</span>"
                        : "<span class=\"badge badge-light-danger\">Origen insuficiente</span>";
                    return "<div class=\"border-bottom py-2\">" +
                        "<div class=\"fw-bold\">" + escapeHtml(item.sku || "-") + "</div>" +
                        "<div class=\"text-muted fs-8\">" + escapeHtml(item.nombre_producto || item.nombre_sku || "") + "</div>" +
                        "<div class=\"d-flex flex-wrap gap-2 mt-2\">" +
                        "<span class=\"badge badge-light-primary\">Solicitar " + numero(item.cantidad_solicitada) + " " + escapeHtml(item.unidad_base || "") + "</span>" +
                        "<span class=\"badge badge-light-success\">Disp. " + numero(item.cantidad_disponible_destino) + "</span>" +
                        "<span class=\"badge badge-light-dark\">Origen " + numero(item.cantidad_disponible_origen) + "</span>" +
                        "<span class=\"badge badge-light-warning\">Umbral " + numero(item.umbral_usado) + "</span>" +
                        cobertura +
                        "</div></div>";
                }).join("");
        }).catch(function (error) {
            $("alm_res_simulacion").innerHTML = "<span class=\"text-danger\">" + escapeHtml(error.message) + "</span>";
        });
    }
    function cargarResumenTiendas() {
        var params = new URLSearchParams({
            id_almacen_origen: $("alm_res_stock_origen").value || "",
            q: $("alm_res_stock_q").value || ""
        });
        $("alm_res_resumen_tiendas").innerHTML = "Consultando tiendas...";
        return request("/almacen/resurtido_resumen_tiendas_erp?" + params.toString()).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            var payload = response.depurar || {};
            var rows = payload.items || [];
            var totales = payload.totales || {};
            if (!rows.length) {
                $("alm_res_resumen_tiendas").innerHTML = "<div class=\"text-muted\">Sin tiendas activas para venta.</div>";
                return;
            }
            $("alm_res_resumen_tiendas").innerHTML =
                "<div class=\"fw-bold mb-2\">Resumen tiendas</div>" +
                "<div class=\"d-flex flex-wrap gap-2 mb-3\">" +
                "<span class=\"badge badge-light-primary\">" + numero(totales.tiendas) + " tiendas</span>" +
                "<span class=\"badge badge-light-success\">" + numero(totales.partidas_sugeridas) + " partidas</span>" +
                "<span class=\"badge badge-light-warning\">" + numero(totales.partidas_origen_insuficiente) + " insuf.</span>" +
                "</div>" +
                rows.map(function (item) {
                    return "<div class=\"border-bottom py-2\">" +
                        "<div class=\"fw-bold\">" + escapeHtml(item.codigo_almacen || "") + "</div>" +
                        "<div class=\"text-muted fs-8\">" + escapeHtml(item.almacen || "") + "</div>" +
                        "<div class=\"d-flex flex-wrap gap-2 mt-2\">" +
                        "<span class=\"badge badge-light-primary\">" + numero(item.total_partidas) + " partidas</span>" +
                        "<span class=\"badge badge-light-success\">Sug. " + numero(item.cantidad_total_sugerida) + "</span>" +
                        "<span class=\"badge badge-light-dark\">Surtible " + numero(item.cantidad_total_surtible_origen) + "</span>" +
                        "<span class=\"badge badge-light-warning\">" + numero(item.partidas_con_origen_insuficiente) + " insuf.</span>" +
                        "</div></div>";
                }).join("");
        }).catch(function (error) {
            $("alm_res_resumen_tiendas").innerHTML = "<span class=\"text-danger\">" + escapeHtml(error.message) + "</span>";
        });
    }
    function validarSolicitud() {
        var params = new URLSearchParams({
            id_almacen_destino: $("alm_res_stock_almacen").value || "",
            id_almacen_origen: $("alm_res_stock_origen").value || "",
            q: $("alm_res_stock_q").value || ""
        });
        $("alm_res_validacion").innerHTML = "Validando solicitud...";
        return request("/almacen/resurtido_validar_solicitud_erp?" + params.toString()).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            var payload = response.depurar || {};
            var bloqueos = payload.bloqueos || [];
            var advertencias = payload.advertencias || [];
            var estado = Number(payload.puede_guardar || 0) === 1
                ? "<span class=\"badge badge-light-success\">Puede guardar</span>"
                : "<span class=\"badge badge-light-danger\">Bloqueado</span>";
            $("alm_res_validacion").innerHTML =
                "<div class=\"d-flex justify-content-between align-items-center mb-3\">" +
                "<div class=\"fw-bold\">Validacion</div>" + estado + "</div>" +
                "<div class=\"d-flex flex-wrap gap-2 mb-3\">" +
                "<span class=\"badge badge-light-danger\">" + numero(bloqueos.length) + " bloqueos</span>" +
                "<span class=\"badge badge-light-warning\">" + numero(advertencias.length) + " advertencias</span>" +
                "</div>" +
                renderMensajesValidacion("Bloqueos", bloqueos, "danger") +
                renderMensajesValidacion("Advertencias", advertencias, "warning");
        }).catch(function (error) {
            $("alm_res_validacion").innerHTML = "<span class=\"text-danger\">" + escapeHtml(error.message) + "</span>";
        });
    }
    function renderMensajesValidacion(titulo, rows, tipo) {
        if (!rows.length) { return ""; }
        return "<div class=\"fw-bold mb-2\">" + escapeHtml(titulo) + "</div>" + rows.map(function (item) {
            return "<div class=\"alert alert-" + tipo + " py-2 px-3 mb-2\">" +
                "<div class=\"fw-bold\">" + escapeHtml(item.id || "-") + "</div>" +
                "<div>" + escapeHtml(item.mensaje || "") + "</div>" +
                "</div>";
        }).join("");
    }
    function cargarPayloadSolicitud() {
        var params = new URLSearchParams({
            id_almacen_destino: $("alm_res_stock_almacen").value || "",
            id_almacen_origen: $("alm_res_stock_origen").value || "",
            q: $("alm_res_stock_q").value || ""
        });
        $("alm_res_payload_preview").innerHTML = "Generando payload...";
        return request("/almacen/resurtido_payload_solicitud_erp?" + params.toString()).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            var payload = response.depurar || {};
            var data = payload.payload || {};
            var detalle = data.detalle || [];
            var estado = Number(payload.puede_enviar_post || 0) === 1
                ? "<span class=\"badge badge-light-success\">POST posible</span>"
                : "<span class=\"badge badge-light-danger\">POST bloqueado</span>";
            $("alm_res_payload_preview").innerHTML =
                "<div class=\"d-flex justify-content-between align-items-center mb-3\">" +
                "<div class=\"fw-bold\">Payload RES-T008</div>" + estado + "</div>" +
                "<div class=\"d-flex flex-wrap gap-2 mb-3\">" +
                "<span class=\"badge badge-light-primary\">" + numero(detalle.length) + " lineas</span>" +
                "<span class=\"badge badge-light-info\">" + escapeHtml(payload.metodo_futuro || "POST") + "</span>" +
                "</div>" +
                "<pre class=\"bg-light rounded p-3 fs-8\" style=\"max-height: 260px; overflow:auto; white-space: pre-wrap;\">" +
                escapeHtml(JSON.stringify(data, null, 2)) +
                "</pre>";
        }).catch(function (error) {
            $("alm_res_payload_preview").innerHTML = "<span class=\"text-danger\">" + escapeHtml(error.message) + "</span>";
        });
    }
    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-13
     * Proposito: muestra el contrato read-only de estados/transiciones del resurtido.
     * Impacto: UI Almacen/Resurtido RES-T009A; prepara acciones futuras sin habilitar escrituras.
     * Contrato: GET read-only; no cambia estados, no crea permisos y no mueve inventario.
     */
    function cargarEstados() {
        $("alm_res_estados_panel").innerHTML = "Consultando contrato...";
        return request("/almacen/resurtido_estados_erp").then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            var payload = response.depurar || {};
            var estados = payload.estados_encabezado || [];
            var transiciones = payload.transiciones || [];
            var bloqueadas = payload.transiciones_bloqueadas_ejemplo || [];
            $("alm_res_estados_panel").innerHTML =
                "<div class=\"d-flex flex-wrap gap-2 mb-4\">" +
                "<span class=\"badge badge-light-primary\">" + numero(estados.length) + " estados</span>" +
                "<span class=\"badge badge-light-info\">" + numero(transiciones.length) + " transiciones</span>" +
                "<span class=\"badge badge-light-dark\">Read-only</span>" +
                "</div>" +
                "<div class=\"fw-bold mb-2\">Flujo permitido</div>" +
                transiciones.map(function (item) {
                    return "<div class=\"border-bottom py-2\">" +
                        "<div class=\"d-flex flex-wrap align-items-center gap-2\">" +
                        estadoBadge(item.desde) +
                        "<span class=\"text-muted\">a</span>" +
                        estadoBadge(item.hacia) +
                        "<span class=\"badge badge-light-secondary\">" + escapeHtml(item.accion || "") + "</span>" +
                        (Number(item.afecta_inventario || 0) === 1 ? "<span class=\"badge badge-light-warning\">Afecta inventario</span>" : "") +
                        "</div>" +
                        "<div class=\"text-muted fs-8 mt-1\">" + escapeHtml(item.permiso_candidato || item.permiso_actual || "") + "</div>" +
                        "</div>";
                }).join("") +
                "<div class=\"fw-bold mt-4 mb-2\">Saltos bloqueados</div>" +
                bloqueadas.map(function (item) {
                    return "<span class=\"badge badge-light-danger me-2 mb-2\">" +
                        escapeHtml(item.desde || "-") + " -> " + escapeHtml(item.hacia || "-") +
                        "</span>";
                }).join("");
        }).catch(function (error) {
            $("alm_res_estados_panel").innerHTML = "<span class=\"text-danger\">" + escapeHtml(error.message) + "</span>";
        });
    }
    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-13
     * Proposito: muestra el contrato tecnico read-only de preparacion/envio.
     * Impacto: UI Almacen/Resurtido RES-T009; permite revisar trazabilidad antes de habilitar movimientos.
     * Contrato: GET read-only; no prepara, no envia y no modifica inventario.
     */
    function cargarContratoPreparacionEnvio() {
        $("alm_res_prep_envio_panel").innerHTML = "Consultando contrato...";
        return request("/almacen/resurtido_preparacion_envio_contrato_erp").then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            var payload = response.depurar || {};
            var preparacion = payload.preparacion || {};
            var envio = payload.envio || {};
            $("alm_res_prep_envio_panel").innerHTML =
                "<div class=\"d-flex flex-wrap gap-2 mb-4\">" +
                "<span class=\"badge badge-light-dark\">Read-only</span>" +
                "<span class=\"badge badge-light-info\">" + escapeHtml(payload.modo_transito || "") + "</span>" +
                "<span class=\"badge badge-light-warning\">Envio afecta inventario</span>" +
                "</div>" +
                renderContratoEtapa("Preparacion", preparacion, "primary") +
                renderContratoEtapa("Envio", envio, "warning") +
                "<div class=\"fw-bold mt-4 mb-2\">Movimientos esperados</div>" +
                ((envio.movimientos_esperados || []).map(function (item) {
                    return "<div class=\"border-bottom py-2\">" +
                        "<div class=\"fw-bold\">" + escapeHtml(item.tipo_movimiento || "-") + " | " + escapeHtml(item.origen_tipo || "-") + "</div>" +
                        "<div class=\"text-muted fs-8\">" + escapeHtml(item.almacen || "") + " | " + escapeHtml((item.conserva || []).join(", ")) + "</div>" +
                        "</div>";
                }).join("") || "<div class=\"text-muted\">Sin movimientos definidos.</div>");
        }).catch(function (error) {
            $("alm_res_prep_envio_panel").innerHTML = "<span class=\"text-danger\">" + escapeHtml(error.message) + "</span>";
        });
    }
    function renderContratoEtapa(titulo, etapa, tipo) {
        var validaciones = etapa.validaciones_bloqueantes || [];
        var tablas = etapa.escritura_futura || [];
        return "<div class=\"mb-4\">" +
            "<div class=\"d-flex justify-content-between align-items-center mb-2\">" +
            "<div class=\"fw-bold\">" + escapeHtml(titulo) + "</div>" +
            "<span class=\"badge badge-light-" + tipo + "\">" + escapeHtml(etapa.estado_requerido_encabezado || "-") + " -> " + escapeHtml(etapa.estado_destino_encabezado || "-") + "</span>" +
            "</div>" +
            "<div class=\"text-muted fs-8 mb-2\">" + escapeHtml(etapa.permiso_candidato || etapa.permiso_actual || "") + "</div>" +
            "<div class=\"d-flex flex-wrap gap-2 mb-2\">" +
            tablas.map(function (tabla) { return "<span class=\"badge badge-light-secondary\">" + escapeHtml(tabla) + "</span>"; }).join("") +
            "</div>" +
            validaciones.slice(0, 4).map(function (item) {
                return "<div class=\"alert alert-light-" + tipo + " py-2 px-3 mb-2\">" +
                    "<span class=\"fw-bold\">" + escapeHtml(item.id || "-") + "</span> " + escapeHtml(item.mensaje || "") +
                    "</div>";
            }).join("") +
            (validaciones.length > 4 ? "<div class=\"text-muted fs-8\">+" + numero(validaciones.length - 4) + " validaciones adicionales</div>" : "") +
            "</div>";
    }
    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-13
     * Proposito: muestra el contrato read-only de recepcion/diferencias del resurtido.
     * Impacto: UI Almacen/Resurtido RES-T010; prepara comparacion enviado vs recibido sin habilitar movimientos.
     * Contrato: GET read-only; no recibe folios, no registra diferencias y no mueve inventario.
     */
    function cargarContratoRecepcion() {
        $("alm_res_recepcion_panel").innerHTML = "Consultando contrato...";
        return request("/almacen/resurtido_recepcion_diferencias_contrato_erp").then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            var payload = response.depurar || {};
            var recepcion = payload.recepcion || {};
            var diferencias = payload.diferencias || [];
            var cierres = payload.cierres || [];
            $("alm_res_recepcion_panel").innerHTML =
                "<div class=\"d-flex flex-wrap gap-2 mb-4\">" +
                "<span class=\"badge badge-light-dark\">Read-only</span>" +
                "<span class=\"badge badge-light-info\">" + escapeHtml(payload.modo_transito || "") + "</span>" +
                "<span class=\"badge badge-light-warning\">Recepcion afecta inventario</span>" +
                "</div>" +
                renderContratoEtapa("Recepcion", recepcion, "danger") +
                "<div class=\"fw-bold mt-4 mb-2\">Diferencias</div>" +
                diferencias.map(function (item) {
                    return "<div class=\"border-bottom py-2\">" +
                        "<div class=\"fw-bold\">" + escapeHtml(item.tipo || "-") + "</div>" +
                        "<div class=\"text-muted fs-8\">" + escapeHtml(item.accion_sugerida || "") + " | severidad " + escapeHtml(item.severidad_default || "") + "</div>" +
                        "</div>";
                }).join("") +
                "<div class=\"fw-bold mt-4 mb-2\">Cierre</div>" +
                cierres.map(function (item) {
                    return "<div class=\"alert alert-light-secondary py-2 px-3 mb-2\">" +
                        "<span class=\"fw-bold\">" + escapeHtml(item.estado || "-") + "</span> " + escapeHtml(item.regla || "") +
                        "</div>";
                }).join("");
        }).catch(function (error) {
            $("alm_res_recepcion_panel").innerHTML = "<span class=\"text-danger\">" + escapeHtml(error.message) + "</span>";
        });
    }
    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-13
     * Proposito: muestra contrato read-only de politicas por tienda/SKU y alertas futuras.
     * Impacto: UI Almacen/Resurtido RES-T011/RES-T012; prepara min/max/reorden sin crear alertas persistentes.
     * Contrato: GET read-only; no crea politicas, no crea notificaciones y no mueve inventario.
     */
    function cargarContratoPoliticas() {
        $("alm_res_politicas_panel").innerHTML = "Consultando contrato...";
        return request("/almacen/resurtido_politicas_alertas_contrato_erp").then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            var payload = response.depurar || {};
            var contrato = payload.contrato_politica || {};
            var alertas = (payload.alertas_futuras || {}).eventos || [];
            var validaciones = payload.validaciones_bloqueantes || [];
            $("alm_res_politicas_panel").innerHTML =
                "<div class=\"d-flex flex-wrap gap-2 mb-4\">" +
                "<span class=\"badge badge-light-dark\">Read-only</span>" +
                "<span class=\"badge badge-light-" + (Number(payload.politica_local_disponible || 0) === 1 ? "success" : "warning") + "\">" +
                (Number(payload.politica_local_disponible || 0) === 1 ? "Politica local disponible" : "Usa fallback Catalogo") +
                "</span>" +
                "</div>" +
                "<div class=\"fw-bold mb-2\">" + escapeHtml(contrato.tabla || "erp_inventario_politicas_almacen_sku") + "</div>" +
                "<div class=\"d-flex flex-wrap gap-2 mb-3\">" +
                (contrato.campos_obligatorios || []).map(function (campo) {
                    return "<span class=\"badge badge-light-primary\">" + escapeHtml(campo) + "</span>";
                }).join("") +
                "</div>" +
                "<div class=\"fw-bold mt-4 mb-2\">Formula</div>" +
                renderFormulaPoliticas(payload.formula || {}) +
                "<div class=\"fw-bold mt-4 mb-2\">Alertas futuras</div>" +
                alertas.map(function (item) {
                    return "<div class=\"border-bottom py-2\">" +
                        "<div class=\"fw-bold\">" + escapeHtml(item.id || "-") + " | " + escapeHtml(item.tipo || "-") + "</div>" +
                        "<div class=\"text-muted fs-8\">" + escapeHtml(item.cuando || "") + " | prioridad " + escapeHtml(item.prioridad || "") + "</div>" +
                        "</div>";
                }).join("") +
                "<div class=\"fw-bold mt-4 mb-2\">Validaciones</div>" +
                validaciones.slice(0, 4).map(function (item) {
                    return "<div class=\"alert alert-light-success py-2 px-3 mb-2\">" +
                        "<span class=\"fw-bold\">" + escapeHtml(item.id || "-") + "</span> " + escapeHtml(item.mensaje || "") +
                        "</div>";
                }).join("") +
                (validaciones.length > 4 ? "<div class=\"text-muted fs-8\">+" + numero(validaciones.length - 4) + " validaciones adicionales</div>" : "");
        }).catch(function (error) {
            $("alm_res_politicas_panel").innerHTML = "<span class=\"text-danger\">" + escapeHtml(error.message) + "</span>";
        });
    }
    function renderFormulaPoliticas(formula) {
        var rows = Object.keys(formula).map(function (key) {
            return "<div class=\"border-bottom py-2\"><span class=\"fw-bold\">" + escapeHtml(key) + "</span><div class=\"text-muted fs-8\">" + escapeHtml(formula[key]) + "</div></div>";
        });
        return rows.join("") || "<div class=\"text-muted\">Formula pendiente.</div>";
    }
    function bind() {
        $("alm_res_recargar").addEventListener("click", cargarResurtidos);
        $("alm_res_estatus").addEventListener("change", cargarResurtidos);
        $("alm_res_q").addEventListener("keyup", function (event) {
            if (event.key === "Enter") { cargarResurtidos(); }
        });
        $("alm_res_stock_almacen").addEventListener("change", function () {
            renderOrigenes();
            consultarStockBajo();
        });
        $("alm_res_stock_buscar").addEventListener("click", consultarStockBajo);
        $("alm_res_btn_stock").addEventListener("click", consultarStockBajo);
        $("alm_res_simular").addEventListener("click", simularSolicitud);
        $("alm_res_validar").addEventListener("click", validarSolicitud);
        $("alm_res_payload").addEventListener("click", cargarPayloadSolicitud);
        $("alm_res_btn_resumen").addEventListener("click", cargarResumenTiendas);
        $("alm_res_btn_estados").addEventListener("click", cargarEstados);
        $("alm_res_btn_prep_envio").addEventListener("click", cargarContratoPreparacionEnvio);
        $("alm_res_btn_recepcion").addEventListener("click", cargarContratoRecepcion);
        $("alm_res_btn_politicas").addEventListener("click", cargarContratoPoliticas);
        $("alm_res_stock_q").addEventListener("keyup", function (event) {
            if (event.key === "Enter") { consultarStockBajo(); }
        });
    }
    document.addEventListener("DOMContentLoaded", function () {
        bind();
        cargarAlmacenes().then(function () {
            cargarResurtidos();
            cargarEstados();
            cargarContratoPreparacionEnvio();
            cargarContratoRecepcion();
            cargarContratoPoliticas();
            if ($("alm_res_stock_almacen").value) {
                consultarStockBajo();
            }
        }).catch(function (error) {
            $("alm_res_stock_resumen").innerHTML = "<span class=\"text-danger\">" + escapeHtml(error.message) + "</span>";
            $("alm_res_body").innerHTML = "<tr><td colspan=\"9\" class=\"text-danger\">" + escapeHtml(error.message) + "</td></tr>";
        });
    });
}());
