"use strict";
(function () {
    function $(id) { return document.getElementById(id); }
    function escapeHtml(value) { var div = document.createElement("div"); div.textContent = value == null ? "" : String(value); return div.innerHTML; }
    function getJson(url, params) { var query = new URLSearchParams(params || {}).toString(); return fetch(url + (query ? "?" + query : ""), {credentials: "same-origin"}).then(function (r) { return r.json(); }); }
    function postForm(url, data) { return fetch(url, {method: "POST", credentials: "same-origin", headers: {"Accept": "application/json", "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8", "X-CSRF-Token": window.ERP_CSRF_TOKEN || ""}, body: new URLSearchParams(data || {}).toString()}).then(function (r) { return r.json(); }); }
    function cargar() {
        $("meta_feed_estado").textContent = "Cargando...";
        return Promise.all([getJson("/ecommercePublico/meta_feed_preview_erp", {q: $("meta_feed_busqueda").value || "", limite: 200}), getJson("/ecommercePublico/meta_feed_config_erp")]).then(function (respuestas) {
            var preview = respuestas[0], config = respuestas[1];
            if (preview.error) { throw new Error(preview.mensaje || "No se pudo cargar el feed"); }
            if (config.error) { throw new Error(config.mensaje || "No se pudo cargar la configuracion"); }
            var data = preview.depurar || {}, items = data.items || [], skus = (config.depurar || {}).skus_excluidos || [];
            $("meta_feed_publicados").textContent = Number(data.publicados_ecommerce || 0);
            $("meta_feed_enviados").textContent = Number(data.enviados_meta || 0);
            $("meta_feed_no_enviados").textContent = Number(data.excluidos_por_completitud_o_configuracion || 0);
            $("meta_feed_skus").value = skus.join("\n");
            $("meta_feed_motivo").value = (config.depurar || {}).motivo || "Revision de politicas de Meta";
            $("meta_feed_body").innerHTML = items.map(function (item) { return "<tr><td><strong>" + escapeHtml(item.id || "") + "</strong></td><td>" + escapeHtml(item.title || "") + "</td><td>" + escapeHtml(item.brand || "") + "</td><td><span class=\"badge badge-light-success\">" + escapeHtml(item.availability || "") + "</span></td><td>" + escapeHtml(item.price || "") + "</td><td><a href=\"" + escapeHtml(item.image_link || "") + "\" target=\"_blank\">Ver imagen</a></td></tr>"; }).join("") || "<tr><td colspan=\"6\" class=\"text-muted\">Sin registros.</td></tr>";
            $("meta_feed_estado").textContent = "Actualizado";
        }).catch(function (error) { $("meta_feed_estado").textContent = "Error"; $("meta_feed_body").innerHTML = "<tr><td colspan=\"6\" class=\"text-danger\">" + escapeHtml(error.message || error) + "</td></tr>"; });
    }
    function guardar() {
        $("meta_feed_mensaje").textContent = "Guardando...";
        return postForm("/ecommercePublico/meta_feed_exclusiones_guardar_erp", {skus: $("meta_feed_skus").value || "", motivo: $("meta_feed_motivo").value || "Revision de politicas de Meta"}).then(function (response) { if (response.error) { throw new Error(response.mensaje || "No se guardaron las exclusiones"); } $("meta_feed_mensaje").textContent = response.mensaje || "Exclusiones guardadas."; return cargar(); }).catch(function (error) { $("meta_feed_mensaje").textContent = error.message || String(error); });
    }
    document.addEventListener("DOMContentLoaded", function () { $("meta_feed_recargar").addEventListener("click", cargar); $("meta_feed_busqueda").addEventListener("input", cargar); $("meta_feed_guardar").addEventListener("click", guardar); cargar(); });
})();
