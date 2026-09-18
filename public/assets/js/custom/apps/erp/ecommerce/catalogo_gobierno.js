"use strict";
(function () {
    var paginaActual = 1;
    var paginacionActual = {pagina: 1, limite: 50, total: 0, total_paginas: 1};
    var placeholderImagen = "data:image/svg+xml;charset=UTF-8,%3Csvg%20xmlns='http://www.w3.org/2000/svg'%20viewBox='0%200%2052%2052'%3E%3Crect%20width='52'%20height='52'%20rx='8'%20fill='%23f1f3f6'/%3E%3Cpath%20d='M10%2039h32L32%2027l-8%208-5-7z'%20fill='%23c8ced8'/%3E%3Ccircle%20cx='19'%20cy='19'%20r='5'%20fill='%23d7dce5'/%3E%3C/svg%3E";

    function $(id) { return document.getElementById(id); }

    function escapeHtml(value) {
        var div = document.createElement("div");
        div.textContent = value == null ? "" : String(value);
        return div.innerHTML;
    }

    function dinero(value, moneda) {
        return new Intl.NumberFormat("es-MX", {style: "currency", currency: moneda || "MXN"}).format(Number(value || 0));
    }

    function imagenUrl(url) {
        url = String(url || "").trim();
        if (!url) { return placeholderImagen; }
        if (/^(https?:)?\/\//i.test(url) || url.indexOf("data:") === 0 || url.charAt(0) === "/") { return url; }
        return "/" + url.replace(/^\/+/, "");
    }

    function setEstado(texto, clase) {
        var el = $("ecomgov_estado");
        if (!el) { return; }
        el.className = "badge " + (clase || "badge-light-primary");
        el.textContent = texto;
    }

    function getJson(url, params) {
        var query = new URLSearchParams(params || {}).toString();
        return fetch(url + (query ? "?" + query : ""), {credentials: "same-origin", headers: {"Accept": "application/json"}})
            .then(function (response) { return response.json(); });
    }

    function filtros() {
        return {
            q: $("ecomgov_q") ? $("ecomgov_q").value : "",
            estatus_publicacion: $("ecomgov_estatus") ? $("ecomgov_estatus").value : "",
            filtro_calidad: $("ecomgov_calidad") ? $("ecomgov_calidad").value : "",
            limite: $("ecomgov_limite") ? $("ecomgov_limite").value : "50",
            pagina: paginaActual,
            base_url: "http://panel.com.local"
        };
    }

    function cargar() {
        setEstado("Cargando...", "badge-light-info");
        getJson("/ecommercePublico/catalogo_gobierno_erp", filtros()).then(function (response) {
            if (response.error) { throw new Error(response.mensaje || "No se pudo consultar gobierno ecommerce"); }
            var data = response.depurar || {};
            paginacionActual = data.paginacion || paginacionActual;
            paginaActual = Number(paginacionActual.pagina || paginaActual || 1);
            renderKpis(data.tablero || {});
            renderItems(data.items || []);
            renderAlertas(data.alertas || []);
            renderSeo(data.seo || {}, data.readiness || {});
            renderPaginacion(paginacionActual);
            setEstado("Read-only", "badge-light-success");
        }).catch(function (error) {
            setEstado("Error", "badge-light-danger");
            $("ecomgov_items").innerHTML = "<tr><td colspan=\"7\"><div class=\"alert alert-danger mb-0\">" + escapeHtml(error.message || String(error)) + "</div></td></tr>";
        });
    }

    function renderKpis(tablero) {
        var items = [
            ["Publicados", tablero.publicados || 0, "Productos visibles normales o informativos."],
            ["Informativos", tablero.informativos || 0, "Fichas visibles sin precio/cotizacion directa."],
            ["Borradores", tablero.borradores || 0, "Curaduria preparada sin exponer."],
            ["Pausados", tablero.pausados || 0, "Ocultos sin perder relacion."],
            ["Sin publicacion", tablero.sin_publicacion || 0, "SKUs ERP detectados sin ficha publica."],
            ["Sin precio", tablero.sin_precio || 0, "Sin lista activa para ecommerce normal."],
            ["Sin imagen", tablero.sin_imagen || 0, "Falta visual para vitrina."],
            ["Alerta SEO", tablero.con_alerta_seo || 0, "Slugs duplicados o redirecciones activas."],
            ["Redirecciones pendientes", tablero.redirecciones_pendientes || 0, "URLs antiguas por mapear."],
            ["Nuevos detectados", tablero.productos_nuevos_detectados || 0, "Aptos para preparar publicacion."]
        ];
        $("ecomgov_kpis").innerHTML = items.map(function (item) {
            return "<div class=\"col-6 col-md-4 col-xl-2\"><div class=\"ecomgov-kpi\">" +
                "<div class=\"ecomgov-kpi__label\">" + escapeHtml(item[0]) + "</div>" +
                "<div class=\"ecomgov-kpi__value\">" + Number(item[1] || 0) + "</div>" +
                "<div class=\"text-muted fs-8 mt-2\">" + escapeHtml(item[2]) + "</div>" +
            "</div></div>";
        }).join("");
    }

    function badgeEstado(estado, modo) {
        var mapa = {
            publicado: ["badge-light-success", "Publicado"],
            borrador: ["badge-light-warning", "Borrador"],
            pausado: ["badge-light-secondary", "Pausado"],
            sin_publicacion: ["badge-light", "Sin publicacion"]
        };
        var cfg = mapa[estado] || ["badge-light-info", estado || "Sin dato"];
        var html = "<span class=\"badge " + cfg[0] + "\">" + escapeHtml(cfg[1]) + "</span>";
        if (modo === "informativo") {
            html += " <span class=\"badge badge-light-info\">Informativo</span>";
        }
        return html;
    }

    function badgeAlerta(codigo) {
        var mapa = {
            precio_general_faltante: "Sin precio",
            imagen_faltante: "Sin imagen",
            venta_fraccionaria_bloqueada_fase_1: "Granel",
            posible_granel_textual: "Posible granel",
            html_no_permitido: "HTML",
            titulo_publico_largo: "Titulo largo",
            caracteres_danados: "Caracteres",
            descripcion_publica_vacia: "Sin descripcion",
            descripcion_publica_muy_corta: "Descripcion corta"
        };
        return "<span class=\"badge badge-light-warning\">" + escapeHtml(mapa[codigo] || codigo) + "</span>";
    }

    function renderItems(items) {
        if (!items.length) {
            $("ecomgov_items").innerHTML = "<tr><td colspan=\"7\" class=\"text-center text-muted py-8\">Sin productos para los filtros actuales.</td></tr>";
            return;
        }
        $("ecomgov_items").innerHTML = items.map(function (item) {
            var alertas = [].concat(item.bloqueos || [], item.alertas_editoriales || []);
            return "<tr>" +
                "<td><img class=\"ecomgov-img\" src=\"" + escapeHtml(imagenUrl(item.imagen)) + "\" alt=\"\"></td>" +
                "<td><div class=\"fw-bold\">" + escapeHtml(item.nombre_publico || item.nombre_erp || "") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.sku || "") + " | " + escapeHtml(item.marca || "Sin marca") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.categoria || "Sin categoria") + "</div></td>" +
                "<td><div class=\"ecomgov-badges\">" + badgeEstado(item.estatus_publicacion, item.modo_publicacion) + "</div><div class=\"text-muted fs-8 mt-2\">" + escapeHtml(item.disponibilidad_publica || "Sin disponibilidad") + "</div></td>" +
                "<td>" + (item.slug ? "<code class=\"ecomgov-url text-break\">" + escapeHtml(item.slug) + "</code><div class=\"text-muted fs-8 text-break\">" + escapeHtml(item.url_publica || "") + "</div>" : "<span class=\"badge badge-light\">Sin slug</span>") + "</td>" +
                "<td class=\"text-end\">" + (item.precio_activo ? "<span class=\"fw-semibold\">" + dinero(item.precio, item.moneda || "MXN") + "</span>" : "<span class=\"badge badge-light-danger\">Sin precio</span>") + "</td>" +
                "<td><div class=\"ecomgov-badges\">" + (alertas.length ? alertas.slice(0, 4).map(badgeAlerta).join("") : "<span class=\"badge badge-light-success\">OK</span>") + "</div></td>" +
                "<td class=\"text-end\"><div class=\"d-flex flex-wrap gap-2 justify-content-end\">" +
                    "<a class=\"btn btn-sm btn-light-primary\" href=\"/ecommercePublico/publicaciones?id_sku=" + encodeURIComponent(item.id_sku || "") + "\">Abrir</a>" +
                    (item.slug ? "<a class=\"btn btn-sm btn-light-info\" target=\"_blank\" rel=\"noopener\" href=\"/ecommercePublico/producto/" + encodeURIComponent(item.slug) + "\">API</a>" : "") +
                "</div></td>" +
            "</tr>";
        }).join("");
    }

    function renderAlertas(alertas) {
        if (!alertas.length) {
            $("ecomgov_alertas").innerHTML = "<div class=\"text-muted py-4\">Sin alertas en la muestra actual.</div>";
            return;
        }
        $("ecomgov_alertas").innerHTML = alertas.slice(0, 12).map(function (alerta) {
            var badge = alerta.severidad === "critica" ? "badge-light-danger" : "badge-light-warning";
            return "<div class=\"border rounded p-3 mb-3\">" +
                "<div class=\"d-flex justify-content-between gap-2 mb-2\"><span class=\"badge " + badge + "\">" + escapeHtml(alerta.severidad || "revision") + "</span><span class=\"text-muted fs-8\">" + escapeHtml(alerta.fecha_detectada || "") + "</span></div>" +
                "<div class=\"fw-bold fs-7\">" + escapeHtml(alerta.producto || "") + "</div>" +
                "<div class=\"text-muted fs-8 mb-2\">" + escapeHtml(alerta.sku || "") + "</div>" +
                "<div class=\"fs-8 mb-2\">" + escapeHtml(alerta.que_cambio || alerta.alerta || "") + "</div>" +
                "<div class=\"text-muted fs-8 mb-3\">" + escapeHtml(alerta.accion_sugerida || "") + "</div>" +
                "<a class=\"btn btn-sm btn-light-primary\" href=\"" + escapeHtml(alerta.abrir_publicacion_url || "/ecommercePublico/publicaciones") + "\">Abrir publicacion</a>" +
            "</div>";
        }).join("");
    }

    function renderSeo(seo, readiness) {
        var resumen = seo.resumen || {};
        var bloqueos = readiness.bloqueos_datos_reales || [];
        $("ecomgov_seo").innerHTML =
            "<div class=\"ecomgov-badges mb-4\">" +
                "<span class=\"badge badge-light-primary\">URLs muestra: " + Number(resumen.urls_total_muestra || 0) + "</span>" +
                "<span class=\"badge badge-light-info\">301 activas: " + Number(resumen.redirecciones_activas || 0) + "</span>" +
                "<span class=\"badge badge-light-warning\">410 activas: " + Number(resumen.urls_410_activas || 0) + "</span>" +
                "<span class=\"badge " + (resumen.robots_disponible ? "badge-light-success" : "badge-light-danger") + "\">Robots</span>" +
            "</div>" +
            "<div class=\"text-muted fs-8 mb-2\">Senal frontend: <span class=\"fw-semibold text-gray-700\">" + escapeHtml(readiness.senal_frontend || "sin_dato") + "</span></div>" +
            (bloqueos.length ? "<div class=\"ecomgov-badges\">" + bloqueos.map(function (b) { return "<span class=\"badge badge-light-warning\">" + escapeHtml(b) + "</span>"; }).join("") + "</div>" : "<span class=\"badge badge-light-success\">Sin bloqueos de datos reales</span>");
    }

    function renderPaginacion(paginacion) {
        var total = Number(paginacion.total || 0);
        var limite = Math.max(1, Number(paginacion.limite || 50));
        var pagina = Math.max(1, Number(paginacion.pagina || paginaActual || 1));
        var totalPaginas = Math.max(1, Number(paginacion.total_paginas || Math.ceil(total / limite) || 1));
        var desde = total === 0 ? 0 : ((pagina - 1) * limite) + 1;
        var hasta = Math.min(total, pagina * limite);
        $("ecomgov_paginacion").textContent = total ? "Mostrando " + desde + "-" + hasta + " de " + total : "Sin resultados";
        $("ecomgov_pagina").textContent = "Pagina " + pagina + " de " + totalPaginas;
        $("ecomgov_anterior").disabled = pagina <= 1;
        $("ecomgov_siguiente").disabled = pagina >= totalPaginas;
    }

    function resetYCargar() {
        paginaActual = 1;
        cargar();
    }

    document.addEventListener("DOMContentLoaded", function () {
        ["ecomgov_q", "ecomgov_estatus", "ecomgov_calidad", "ecomgov_limite"].forEach(function (id) {
            var el = $(id);
            if (!el) { return; }
            el.addEventListener(id === "ecomgov_q" ? "input" : "change", resetYCargar);
        });
        $("ecomgov_recargar").addEventListener("click", cargar);
        $("ecomgov_anterior").addEventListener("click", function () {
            if (paginaActual > 1) { paginaActual--; cargar(); }
        });
        $("ecomgov_siguiente").addEventListener("click", function () {
            if (paginaActual < Number(paginacionActual.total_paginas || 1)) { paginaActual++; cargar(); }
        });
        cargar();
    });
})();
