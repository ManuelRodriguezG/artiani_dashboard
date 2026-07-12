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
    function bind() {
        $("alm_res_recargar").addEventListener("click", cargarResurtidos);
        $("alm_res_estatus").addEventListener("change", cargarResurtidos);
        $("alm_res_q").addEventListener("keyup", function (event) {
            if (event.key === "Enter") { cargarResurtidos(); }
        });
        $("alm_res_stock_buscar").addEventListener("click", consultarStockBajo);
        $("alm_res_btn_stock").addEventListener("click", consultarStockBajo);
        $("alm_res_stock_q").addEventListener("keyup", function (event) {
            if (event.key === "Enter") { consultarStockBajo(); }
        });
    }
    document.addEventListener("DOMContentLoaded", function () {
        bind();
        cargarAlmacenes().then(function () {
            cargarResurtidos();
            if ($("alm_res_stock_almacen").value) {
                consultarStockBajo();
            }
        }).catch(function (error) {
            $("alm_res_stock_resumen").innerHTML = "<span class=\"text-danger\">" + escapeHtml(error.message) + "</span>";
            $("alm_res_body").innerHTML = "<tr><td colspan=\"9\" class=\"text-danger\">" + escapeHtml(error.message) + "</td></tr>";
        });
    });
}());
