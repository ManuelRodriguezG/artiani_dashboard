"use strict";
(function () {
    var placeholderImagen = "data:image/svg+xml;charset=UTF-8,%3Csvg%20xmlns='http://www.w3.org/2000/svg'%20viewBox='0%200%2056%2056'%3E%3Crect%20width='56'%20height='56'%20rx='8'%20fill='%23f1f3f6'/%3E%3Cpath%20d='M11%2042h34L34%2029l-8%209-5-7z'%20fill='%23c8ced8'/%3E%3Ccircle%20cx='20'%20cy='20'%20r='6'%20fill='%23d7dce5'/%3E%3C/svg%3E";

    function $(id) { return document.getElementById(id); }

    function escapeHtml(value) {
        var div = document.createElement("div");
        div.textContent = value == null ? "" : String(value);
        return div.innerHTML;
    }

    function dinero(value) {
        return new Intl.NumberFormat("es-MX", {style: "currency", currency: "MXN"}).format(Number(value || 0));
    }

    function imagenUrl(url) {
        url = String(url || "").trim();
        if (!url) { return placeholderImagen; }
        if (/^(https?:)?\/\//i.test(url) || url.indexOf("data:") === 0 || url.charAt(0) === "/") {
            return url;
        }
        return "/" + url.replace(/^\/+/, "");
    }

    function getJson(url, params) {
        var query = new URLSearchParams(params || {}).toString();
        return fetch(url + (query ? "?" + query : ""), {credentials: "same-origin"}).then(function (response) {
            return response.json();
        });
    }

    function setEstado(texto, tipo) {
        var el = $("ecom_estado");
        if (!el) { return; }
        el.className = "badge " + (tipo || "badge-light-primary");
        el.textContent = texto;
    }

    function filtrosAuditoria() {
        var modo = $("ecom_filtro_modo").value;
        return {
            limite: $("ecom_filtro_limite").value,
            solo_publicables: modo === "publicables" ? "1" : "0",
            solo_bloqueados: modo === "bloqueados" ? "1" : "0"
        };
    }

    function cargarTodo() {
        setEstado("Cargando...", "badge-light-info");
        Promise.all([cargarReadiness(), cargarAuditoria(), cargarSchema()]).then(function () {
            setEstado("Read-only", "badge-light-success");
        }).catch(function (error) {
            setEstado("Error", "badge-light-danger");
            renderError(error.message || String(error));
        });
    }

    function cargarReadiness() {
        return getJson("/ecommercePublico/publicaciones_readiness_erp", {base_url: "http://panel.com.local"}).then(function (response) {
            if (response.error) { throw new Error(response.mensaje || "No se pudo cargar readiness"); }
            renderReadiness(response.depurar || {}, response.mensaje || "");
        });
    }

    function cargarAuditoria() {
        return getJson("/ecommercePublico/publicaciones_auditar_erp", filtrosAuditoria()).then(function (response) {
            if (response.error) { throw new Error(response.mensaje || "No se pudo cargar auditoria"); }
            var data = response.depurar || {};
            renderResumen(data.resumen || {});
            renderCandidatos(data.candidatos || []);
        });
    }

    function cargarSchema() {
        return Promise.all([
            getJson("/ecommercePublico/esquema_auditar_ecommerce_publico", {}),
            getJson("/ecommercePublico/esquema_plan_ecommerce_publico", {})
        ]).then(function (responses) {
            var auditoria = responses[0];
            var plan = responses[1];
            if (auditoria.error) { throw new Error(auditoria.mensaje || "No se pudo auditar esquema"); }
            if (plan.error) { throw new Error(plan.mensaje || "No se pudo generar plan DDL"); }
            renderSchema(auditoria.depurar || {}, plan.depurar || {});
        });
    }

    function renderResumen(resumen) {
        $("ecom_kpi_publicables").textContent = Number(resumen.skus_publicables_fase_1 || 0);
        $("ecom_kpi_imagen").textContent = Number(resumen.skus_con_imagen || 0);
        $("ecom_kpi_categoria").textContent = Number(resumen.skus_con_categoria || 0);
    }

    function renderReadiness(data, mensaje) {
        var senal = String(data.senal_frontend || "amarillo_mock_contratos");
        var esVerde = senal.indexOf("verde") === 0;
        var esRojo = senal.indexOf("rojo") === 0;
        var signal = $("ecom_readiness_signal");
        var bloqueos = data.bloqueos_datos_reales || [];
        var publicaciones = data.publicaciones || {};
        var schema = data.schema || {};
        var configuracion = data.configuracion || {};
        var comandosReadonly = data.comandos_readonly || {};
        var comandosApply = data.comandos_apply_autorizados || {};

        signal.className = "ecom-readiness__signal " + (esVerde ? "ecom-readiness__signal--verde" : (esRojo ? "ecom-readiness__signal--rojo" : "ecom-readiness__signal--amarillo"));
        $("ecom_readiness_titulo").textContent = mensaje || (esVerde ? "Frontend listo para datos reales" : "Frontend listo para iniciar con mocks");
        $("ecom_readiness_subtitulo").textContent = esVerde
            ? "El frontend externo ya puede consumir catalogo real publicado desde ERP."
            : "Puedes iniciar el proyecto frontend con cliente API, mocks y contratos; datos reales siguen bloqueados hasta activar la operacion.";
        $("ecom_readiness_base").textContent = data.base_api_recomendada || "http://panel.com.local/ecommercePublico";

        $("ecom_readiness_estados").innerHTML = [
            badgeEstado(data.puede_iniciar_frontend_mock, "Mock frontend"),
            badgeEstado(data.puede_integrar_datos_reales, "Datos reales"),
            badgeEstado(!(schema.ddl_pendiente), "DDL"),
            badgeEstado(Number(publicaciones.total_publicadas || 0) > 0, "Publicados"),
            badgeEstado(configuracion.whatsapp_configurado, "WhatsApp"),
            badgeEstado(configuracion.cors_configurado, "CORS")
        ].join("");

        $("ecom_readiness_bloqueos").innerHTML = bloqueos.length
            ? bloqueos.map(function (bloqueo) {
                return "<span class=\"badge badge-light-warning\">" + escapeHtml(etiquetaBloqueoReadiness(bloqueo)) + "</span>";
            }).join("")
            : "<span class=\"badge badge-light-success\">Sin bloqueos</span>";

        var pasos = data.siguientes_pasos || [];
        $("ecom_readiness_siguientes").innerHTML = pasos.length
            ? pasos.map(function (paso) { return "<div class=\"mb-1\">- " + escapeHtml(etiquetaPasoReadiness(paso)) + "</div>"; }).join("")
            : "<span class=\"text-muted\">Sin pendientes.</span>";

        $("ecom_readiness_comandos_readonly").innerHTML = renderComandosReadiness(comandosReadonly, ["readiness_frontend", "bundle_activacion", "secuencia_activacion", "green_gate"]);
        $("ecom_readiness_comandos_apply").innerHTML = renderComandosReadiness(comandosApply, ["ddl", "configuracion", "borrador", "publicar_borrador"]);
    }

    function renderComandosReadiness(comandos, orden) {
        var html = [];
        orden.forEach(function (clave) {
            if (!comandos[clave]) { return; }
            html.push(
                "<div class=\"mb-3\">" +
                    "<div class=\"fw-semibold text-gray-700 mb-1\">" + escapeHtml(etiquetaComandoReadiness(clave)) + "</div>" +
                    "<code class=\"d-block text-break bg-light border rounded p-2\">" + escapeHtml(comandos[clave]) + "</code>" +
                "</div>"
            );
        });
        return html.join("") || "<span class=\"text-muted\">Sin comandos disponibles.</span>";
    }

    function etiquetaComandoReadiness(clave) {
        var mapa = {
            readiness_frontend: "Semaforo frontend",
            bundle_activacion: "Bundle activacion",
            secuencia_activacion: "Secuencia sugerida",
            green_gate: "Compuerta verde",
            ddl: "Aplicar DDL",
            configuracion: "Guardar configuracion",
            borrador: "Crear borrador",
            publicar_borrador: "Publicar borrador"
        };
        return mapa[clave] || clave;
    }

    function badgeEstado(ok, texto) {
        return "<span class=\"badge " + (ok ? "badge-light-success" : "badge-light-warning") + "\">" + escapeHtml(texto) + "</span>";
    }

    function etiquetaBloqueoReadiness(bloqueo) {
        var mapa = {
            ddl_ecommerce_publico_pendiente: "DDL ecommerce pendiente",
            sin_publicaciones_activas: "Sin publicaciones activas",
            whatsapp_no_configurado: "WhatsApp sin configurar",
            cors_origenes_permitidos_no_configurado: "CORS sin configurar",
            conexion_mysql_no_disponible: "Sin conexion MySQL"
        };
        return mapa[bloqueo] || bloqueo;
    }

    function etiquetaPasoReadiness(paso) {
        var mapa = {
            iniciar_frontend_con_mocks_y_cliente_api: "Iniciar frontend con mocks y cliente API",
            aplicar_ddl_solo_con_respaldo_y_token: "Aplicar DDL solo con respaldo y token autorizado",
            configurar_whatsapp_y_cors: "Configurar WhatsApp y CORS",
            crear_borradores_y_publicar_lote_inicial: "Crear borradores y publicar lote inicial",
            iniciar_frontend_con_datos_reales: "Iniciar frontend con datos reales",
            validar_whatsapp_en_dispositivo: "Validar WhatsApp en dispositivo",
            monitorear_cotizacion_dryrun: "Monitorear cotizacion dry-run"
        };
        return mapa[paso] || paso;
    }

    function disponibilidadBadge(estado) {
        var mapa = {
            disponible: ["badge-light-success", "Disponible"],
            pocas_piezas: ["badge-light-warning", "Pocas piezas"],
            consultar_disponibilidad: ["badge-light-info", "Consultar"],
            agotado: ["badge-light-danger", "Agotado"]
        };
        var item = mapa[estado] || ["badge-light-secondary", estado || "Sin dato"];
        return "<span class=\"badge " + item[0] + "\">" + escapeHtml(item[1]) + "</span>";
    }

    function bloqueosHtml(item) {
        var bloqueos = item.bloqueos_publicacion || [];
        if (!bloqueos.length) {
            return "<span class=\"badge badge-light-success\">Publicable</span>";
        }
        return "<div class=\"ecom-block-list\">" + bloqueos.map(function (bloqueo) {
            return "<span class=\"badge badge-light-warning\">" + escapeHtml(etiquetaBloqueo(bloqueo)) + "</span>";
        }).join("") + "</div>";
    }

    function etiquetaBloqueo(bloqueo) {
        var mapa = {
            precio_general_faltante: "Sin precio",
            imagen_faltante: "Sin imagen",
            categoria_principal_faltante: "Sin categoria",
            venta_fraccionaria_bloqueada_fase_1: "Granel bloqueado",
            publicacion_existente: "Ya tiene publicacion"
        };
        return mapa[bloqueo] || bloqueo;
    }

    function renderCandidatos(items) {
        if (!items.length) {
            $("ecom_publicaciones_body").innerHTML = "<tr><td colspan=\"8\" class=\"text-center text-muted py-8\">Sin candidatos para los filtros actuales.</td></tr>";
            return;
        }
        $("ecom_publicaciones_body").innerHTML = items.map(function (item) {
            return "<tr>" +
                "<td><img class=\"ecom-product-img\" src=\"" + escapeHtml(imagenUrl(item.url_imagen)) + "\" alt=\"\"></td>" +
                "<td><div class=\"fw-bold\">" + escapeHtml(item.nombre_publico || "") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.sku || "") + " | " + escapeHtml(item.codigo_producto || "") + "</div></td>" +
                "<td>" + escapeHtml(item.marca || "Sin marca") + "</td>" +
                "<td>" + escapeHtml(item.categoria || "Sin categoria") + "</td>" +
                "<td class=\"text-end fw-semibold\">" + dinero(item.precio || 0) + "</td>" +
                "<td>" + disponibilidadBadge(item.disponibilidad_publica_sugerida) + "</td>" +
                "<td>" + bloqueosHtml(item) + "</td>" +
                "<td class=\"text-end\"><button class=\"btn btn-sm btn-light-primary ecom-preparar\" type=\"button\" data-sku=\"" + escapeHtml(item.id_sku || "") + "\">Preparar</button></td>" +
            "</tr>";
        }).join("");
    }

    function renderSchema(auditoria, plan) {
        $("ecom_kpi_ddl").textContent = Number(auditoria.tablas_faltantes || 0);
        $("ecom_schema_resumen").innerHTML = "<div class=\"alert alert-" + (Number(auditoria.tablas_faltantes || 0) ? "warning" : "success") + " py-3 mb-0\">" +
            "<div class=\"fw-bold\">Tablas faltantes: " + Number(auditoria.tablas_faltantes || 0) + " de " + Number(auditoria.tablas_total || 0) + "</div>" +
            "<div class=\"fs-8\">El plan DDL se genera sin ejecutar. Requiere respaldo externo y autorizacion fuerte antes de aplicar.</div>" +
        "</div>";
        var planItems = plan.plan || [];
        $("ecom_schema_body").innerHTML = planItems.map(function (item, index) {
            var depurar = item.depurar || {};
            var sql = depurar.sql || "";
            var estado = depurar.ejecutado ? "Ejecutado" : (sql ? "Pendiente" : "Disponible");
            var badge = depurar.ejecutado ? "badge-light-success" : (sql ? "badge-light-warning" : "badge-light-info");
            return "<tr>" +
                "<td class=\"fw-semibold\">DDL " + (index + 1) + "</td>" +
                "<td><span class=\"badge " + badge + "\">" + escapeHtml(estado) + "</span></td>" +
                "<td><div class=\"text-muted\">" + escapeHtml(item.mensaje || "") + "</div>" + (sql ? "<code class=\"fs-8 d-block text-break mt-1\">" + escapeHtml(sql.substring(0, 240)) + (sql.length > 240 ? "..." : "") + "</code>" : "") + "</td>" +
            "</tr>";
        }).join("") || "<tr><td colspan=\"3\" class=\"text-center text-muted py-6\">Sin plan DDL.</td></tr>";
    }

    function renderError(mensaje) {
        $("ecom_publicaciones_body").innerHTML = "<tr><td colspan=\"8\"><div class=\"alert alert-danger mb-0\">" + escapeHtml(mensaje) + "</div></td></tr>";
    }

    function cargarPreparacion(idSku) {
        var preview = $("ecom_preview_publicacion");
        preview.innerHTML = "<div class=\"text-muted py-4\">Preparando ficha...</div>";
        return getJson("/ecommercePublico/publicaciones_preparar_erp", {id_sku: idSku}).then(function (response) {
            if (response.error) { throw new Error(response.mensaje || "No se pudo preparar publicacion"); }
            renderPreparacion(response.depurar || {}, response.mensaje || "");
        }).catch(function (error) {
            preview.innerHTML = "<div class=\"alert alert-danger mb-0\">" + escapeHtml(error.message || String(error)) + "</div>";
        });
    }

    function renderPreparacion(data, mensaje) {
        var producto = data.producto_vivo_erp || {};
        var pub = data.publicacion_sugerida || {};
        var bloqueos = data.bloqueos_publicacion || [];
        var necesidades = pub.necesidades || [];
        $("ecom_preview_publicacion").innerHTML =
            "<div class=\"row g-4 align-items-start\">" +
                "<div class=\"col-lg-3\">" +
                    "<img class=\"w-100 rounded border\" style=\"max-height:220px;object-fit:cover;background:#f1f3f6\" src=\"" + escapeHtml(imagenUrl(producto.imagen)) + "\" alt=\"\">" +
                "</div>" +
                "<div class=\"col-lg-5\">" +
                    "<div class=\"d-flex align-items-center gap-2 mb-2\">" +
                        "<span class=\"badge " + (data.publicable_fase_1 ? "badge-light-success" : "badge-light-warning") + "\">" + escapeHtml(mensaje || "Preparado") + "</span>" +
                        "<span class=\"badge badge-light-info\">Read-only</span>" +
                    "</div>" +
                    "<h4 class=\"fw-bold mb-1\">" + escapeHtml(pub.titulo_publico || producto.nombre || "") + "</h4>" +
                    "<div class=\"text-muted mb-3\">" + escapeHtml(producto.sku || "") + " | " + escapeHtml(producto.marca || "Sin marca") + " | " + escapeHtml(producto.categoria || "Sin categoria") + "</div>" +
                    "<div class=\"fs-6 fw-semibold mb-2\">" + dinero(producto.precio || 0) + " " + escapeHtml(producto.moneda || "MXN") + "</div>" +
                    "<div class=\"mb-2\">" + disponibilidadBadge(producto.disponibilidad_publica_sugerida) + "</div>" +
                    "<div class=\"text-muted fs-7\">Los precio, imagen, marca y categoria seguiran viniendo vivos desde ERP. La publicacion solo guarda curaduria.</div>" +
                "</div>" +
                "<div class=\"col-lg-4\">" +
                    "<div class=\"border rounded p-4 bg-light\">" +
                        "<div class=\"fw-bold mb-2\">Campos sugeridos</div>" +
                        "<div class=\"fs-7 mb-1\"><span class=\"text-muted\">Slug:</span> <code>" + escapeHtml(pub.slug || "") + "</code></div>" +
                        "<div class=\"fs-7 mb-1\"><span class=\"text-muted\">Presentacion:</span> " + escapeHtml(pub.presentacion_publica || "Sin dato") + "</div>" +
                        "<div class=\"fs-7 mb-1\"><span class=\"text-muted\">Mascota:</span> " + escapeHtml(pub.mascota_especie || "Por definir") + "</div>" +
                        "<div class=\"fs-7 mb-3\"><span class=\"text-muted\">Necesidades:</span> " + escapeHtml(necesidades.length ? necesidades.join(", ") : "Por definir") + "</div>" +
                        (bloqueos.length ? "<div class=\"ecom-block-list\">" + bloqueos.map(function (b) { return "<span class=\"badge badge-light-warning\">" + escapeHtml(etiquetaBloqueo(b)) + "</span>"; }).join("") + "</div>" : "<span class=\"badge badge-light-success\">Listo para borrador cuando exista esquema</span>") +
                    "</div>" +
                "</div>" +
            "</div>";
    }

    document.addEventListener("DOMContentLoaded", function () {
        $("ecom_recargar").addEventListener("click", cargarTodo);
        $("ecom_filtro_modo").addEventListener("change", cargarTodo);
        $("ecom_filtro_limite").addEventListener("change", cargarTodo);
        $("ecom_publicaciones_body").addEventListener("click", function (event) {
            var boton = event.target.closest(".ecom-preparar");
            if (!boton) { return; }
            cargarPreparacion(boton.getAttribute("data-sku"));
        });
        cargarTodo();
    });
})();
