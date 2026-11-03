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

    function postForm(url, data) {
        data = data || {};
        data._csrf = window.ERP_CSRF_TOKEN || "";
        return fetch(url, {
            method: "POST",
            credentials: "same-origin",
            headers: {
                "Accept": "application/json",
                "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
                "X-CSRF-Token": window.ERP_CSRF_TOKEN || ""
            },
            body: new URLSearchParams(data).toString()
        }).then(function (response) {
            return response.text().then(function (texto) {
                try {
                    return JSON.parse(texto);
                } catch (error) {
                    throw new Error("Respuesta no JSON en " + url + " (HTTP " + response.status + "): " + texto.substring(0, 180));
                }
            });
        });
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
            renderAlertas(data.alertas || [], data.alertas_persistentes || []);
            renderSeo(data.seo || {}, data.readiness || {});
            renderCambiosSeo(data.seo && data.seo.cambios ? data.seo.cambios : []);
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
            , ["Cambios SEO", tablero.cambios_seo_pendientes || 0, "URLs nuevas, actualizadas o retiradas para revision."]
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

    function renderAlertas(alertas, persistentes) {
        persistentes = persistentes || [];
        if (persistentes.length) {
            $("ecomgov_alertas").innerHTML =
                "<div class=\"alert alert-light-primary py-3 mb-4\"><div class=\"fw-bold\">Bandeja persistente</div><div class=\"fs-8 text-muted\">Mostrando notificaciones operativas abiertas de Ecommerce/Catalogo.</div></div>" +
                persistentes.slice(0, 12).map(function (alerta) {
                    var payload = alerta.payload || {};
                    var badge = alerta.prioridad === "critica" || alerta.prioridad === "alta" ? "badge-light-danger" : "badge-light-warning";
                    return "<div class=\"border rounded p-3 mb-3\">" +
                        "<div class=\"d-flex justify-content-between gap-2 mb-2\"><span class=\"badge " + badge + "\">" + escapeHtml(alerta.prioridad || "normal") + "</span><span class=\"text-muted fs-8\">" + escapeHtml(alerta.fecha_actualizacion || alerta.fecha_creacion || "") + "</span></div>" +
                        "<div class=\"fw-bold fs-7\">" + escapeHtml(alerta.titulo || "") + "</div>" +
                        "<div class=\"text-muted fs-8 mb-2\">" + escapeHtml(payload.sku || "") + (payload.producto ? " | " + escapeHtml(payload.producto) : "") + "</div>" +
                        "<div class=\"fs-8 mb-3\">" + escapeHtml(alerta.descripcion || "") + "</div>" +
                        "<a class=\"btn btn-sm btn-light-primary\" href=\"" + escapeHtml(alerta.url_accion || "/ecommercePublico/publicaciones") + "\">Abrir publicacion</a>" +
                    "</div>";
                }).join("");
            return;
        }
        if (!alertas.length) {
            $("ecomgov_alertas").innerHTML = "<div class=\"text-muted py-4\">Sin alertas en la muestra actual.</div>";
            return;
        }
        $("ecomgov_alertas").innerHTML =
            "<div class=\"alert alert-light-warning py-3 mb-4\"><div class=\"fw-bold\">Alertas derivadas</div><div class=\"fs-8 text-muted\">Aun no hay bandeja persistente sincronizada para Ecommerce/Catalogo.</div></div>" +
            alertas.slice(0, 12).map(function (alerta) {
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

    function sincronizarAlertas() {
        var boton = $("ecomgov_sincronizar_alertas");
        if (boton) { boton.disabled = true; }
        setEstado("Sincronizando alertas...", "badge-light-info");
        postForm("/ecommercePublico/catalogo_alertas_sincronizar_erp", filtros()).then(function (response) {
            if (response.error) { throw new Error(response.mensaje || "No fue posible sincronizar alertas"); }
            var data = response.depurar || {};
            setEstado("Alertas sincronizadas", "badge-light-success");
            renderAlertas([], data.alertas_persistentes || []);
            if (window.Swal) {
                Swal.fire("Alertas sincronizadas", "Notificaciones guardadas: " + Number(data.notificaciones_guardadas || 0), "success");
            }
            cargar();
        }).catch(function (error) {
            setEstado("Error al sincronizar", "badge-light-danger");
            if (window.Swal) {
                Swal.fire("No se pudo sincronizar", error.message || String(error), "warning");
            } else {
                window.alert(error.message || String(error));
            }
        }).finally(function () {
            if (boton) { boton.disabled = false; }
        });
    }

    function prepararSincronizacionSeo() {
        var boton = $("ecomgov_plan_sync");
        if (boton) { boton.disabled = true; }
        setEstado("Preparando plan SEO...", "badge-light-info");
        postForm("/ecommercePublico/seo_urls_sincronizar_plan_erp", {limite: "3000"}).then(function (response) {
            if (response.error) { throw new Error(response.mensaje || "No se pudo preparar la sincronizacion"); }
            var data = response.depurar || {};
            var nodo = $("ecomgov_plan_sync_resultado");
            if (nodo) {
                nodo.innerHTML =
                    "<div class=\"alert alert-light-primary mb-0\"><div class=\"fw-bold\">Plan SEO preparado sin escribir datos</div>" +
                    "<div class=\"text-muted fs-8 mt-1\">Nuevas: " + Number(data.nuevas || 0) + " | Actualizar: " + Number(data.actualizar || 0) + " | Sin cambio: " + Number(data.sin_cambio || 0) + " | Bloqueadas: " + Number(data.bloqueadas || 0) + "</div>" +
                    "<div class=\"text-muted fs-8 mt-1\">Para aplicar la sincronizacion se requiere autorizacion operativa en la mesa SEO.</div></div>";
            }
            setEstado("Plan SEO listo", "badge-light-success");
        }).catch(function (error) {
            setEstado("Error", "badge-light-danger");
            var nodo = $("ecomgov_plan_sync_resultado");
            if (nodo) { nodo.innerHTML = "<div class=\"alert alert-danger mb-0\">" + escapeHtml(error.message || String(error)) + "</div>"; }
        }).finally(function () {
            if (boton) { boton.disabled = false; }
        });
    }

    function renderSeo(seo, readiness) {
        var resumen = seo.resumen || {};
        var pendientes = seo.cambios_pendientes || {};
        var bloqueos = readiness.bloqueos_datos_reales || [];
        $("ecomgov_seo").innerHTML =
            "<div class=\"ecomgov-badges mb-4\">" +
                "<span class=\"badge badge-light-primary\">URLs muestra: " + Number(resumen.urls_total_muestra || 0) + "</span>" +
                "<span class=\"badge badge-light-info\">301 activas: " + Number(resumen.redirecciones_activas || 0) + "</span>" +
                "<span class=\"badge badge-light-warning\">410 activas: " + Number(resumen.urls_410_activas || 0) + "</span>" +
                "<span class=\"badge " + (Number(pendientes.total_pendientes || 0) ? "badge-light-warning" : "badge-light-success") + "\">Cambios pendientes: " + Number(pendientes.total_pendientes || 0) + "</span>" +
                "<span class=\"badge " + (resumen.robots_disponible ? "badge-light-success" : "badge-light-danger") + "\">Robots</span>" +
            "</div>" +
            "<div class=\"text-muted fs-8 mb-2\">Nuevas: " + Number(pendientes.nuevas || 0) + " | Actualizadas: " + Number(pendientes.actualizadas || 0) + " | Retiradas para revision: " + Number(pendientes.retiradas || 0) + "</div>" +
            "<div class=\"text-muted fs-8 mb-2\">Senal frontend: <span class=\"fw-semibold text-gray-700\">" + escapeHtml(readiness.senal_frontend || "sin_dato") + "</span></div>" +
            (bloqueos.length ? "<div class=\"ecomgov-badges\">" + bloqueos.map(function (b) { return "<span class=\"badge badge-light-warning\">" + escapeHtml(b) + "</span>"; }).join("") + "</div>" : "<span class=\"badge badge-light-success\">Sin bloqueos de datos reales</span>");
    }

    function renderCambiosSeo(cambios) {
        var contenedor = $("ecomgov_cambios_seo");
        if (!contenedor) { return; }
        if (!cambios.length) {
            contenedor.innerHTML = "<div class=\"alert alert-light-success mb-0\">No hay cambios SEO pendientes en el snapshot actual.</div>";
            return;
        }
        var etiquetas = {
            nueva: ["badge-light-primary", "Nueva"],
            actualizada: ["badge-light-warning", "Actualizada"],
            retirada_revision: ["badge-light-danger", "Retirada para revision"],
            analytics_revision: ["badge-light-info", "Detectada por Analytics"]
        };
        contenedor.innerHTML =
            "<div class=\"d-flex justify-content-between align-items-center mb-3\"><div><h4 class=\"fw-bold mb-1\">Cambios SEO pendientes</h4><div class=\"text-muted fs-8\">Revisa estos cambios antes de sincronizar el snapshot o aprobar redirecciones.</div></div><a class=\"btn btn-sm btn-light-primary\" href=\"/ecommercePublico/seo_migracion\">Revisar SEO</a></div>" +
            "<div class=\"table-responsive\"><table class=\"table table-sm align-middle\"><thead><tr><th>Accion</th><th>Tipo</th><th>Ruta actual</th><th>Ruta anterior / canonical</th><th></th></tr></thead><tbody>" +
            cambios.slice(0, 30).map(function (cambio) {
                var cfg = etiquetas[cambio.accion] || ["badge-light-info", cambio.accion || "Revision"];
                var anterior = cambio.canonical_anterior || "Sin snapshot anterior";
                return "<tr>" +
                    "<td><span class=\"badge " + cfg[0] + "\">" + escapeHtml(cfg[1]) + "</span></td>" +
                    "<td>" + escapeHtml(cambio.tipo || "url") + "</td>" +
                    "<td><code class=\"text-break\">" + escapeHtml(cambio.path || "") + "</code></td>" +
                    "<td><span class=\"text-muted fs-8 text-break\">" + escapeHtml(anterior) + "</span>" + (cambio.fuente === "analytics" ? "<div class=\"badge badge-light-info mt-1\">Analytics</div>" : "") + "</td>" +
                    "<td class=\"text-end\"><a class=\"btn btn-sm btn-light\" href=\"/ecommercePublico/seo_migracion\">Abrir SEO</a></td>" +
                "</tr>";
            }).join("") +
            "</tbody></table></div>" +
            (cambios.length > 30 ? "<div class=\"text-muted fs-8 mt-2\">Mostrando 30 de " + cambios.length + " cambios detectados.</div>" : "");
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
        $("ecomgov_sincronizar_alertas").addEventListener("click", sincronizarAlertas);
        $("ecomgov_plan_sync").addEventListener("click", prepararSincronizacionSeo);
        $("ecomgov_anterior").addEventListener("click", function () {
            if (paginaActual > 1) { paginaActual--; cargar(); }
        });
        $("ecomgov_siguiente").addEventListener("click", function () {
            if (paginaActual < Number(paginacionActual.total_paginas || 1)) { paginaActual++; cargar(); }
        });
        cargar();
    });
})();
