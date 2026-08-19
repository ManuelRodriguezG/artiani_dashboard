"use strict";
(function () {
    var placeholderImagen = "data:image/svg+xml;charset=UTF-8,%3Csvg%20xmlns='http://www.w3.org/2000/svg'%20viewBox='0%200%2056%2056'%3E%3Crect%20width='56'%20height='56'%20rx='8'%20fill='%23f1f3f6'/%3E%3Cpath%20d='M11%2042h34L34%2029l-8%209-5-7z'%20fill='%23c8ced8'/%3E%3Ccircle%20cx='20'%20cy='20'%20r='6'%20fill='%23d7dce5'/%3E%3C/svg%3E";
    var paginaActual = 1;
    var paginacionActual = {pagina: 1, limite: 50, total: 0, total_paginas: 1};
    var seleccionLote = {};
    var estatusSeleccionLote = {};

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

    function postForm(url, data) {
        return fetch(url, {
            method: "POST",
            credentials: "same-origin",
            headers: {
                "Accept": "application/json",
                "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
                "X-CSRF-Token": window.ERP_CSRF_TOKEN || ""
            },
            body: new URLSearchParams(data || {}).toString()
        }).then(function (response) {
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
            q: $("ecom_filtro_busqueda") ? $("ecom_filtro_busqueda").value : "",
            estatus_publicacion: $("ecom_filtro_estatus") ? $("ecom_filtro_estatus").value : "",
            limite: $("ecom_filtro_limite").value,
            pagina: paginaActual,
            solo_publicables: modo === "publicables" ? "1" : "0",
            solo_bloqueados: modo === "bloqueados" ? "1" : "0"
        };
    }

    function resetearPaginaYCargar() {
        paginaActual = 1;
        cargarTodo();
    }

    function resetearPaginaYCargarAuditoria() {
        paginaActual = 1;
        cargarAuditoria();
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
            paginacionActual = data.paginacion || paginacionActual;
            paginaActual = Number(paginacionActual.pagina || paginaActual || 1);
            renderResumen(data.resumen || {});
            renderPaginacion(paginacionActual);
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

    function renderPaginacion(paginacion) {
        var total = Number(paginacion.total || 0);
        var limite = Math.max(1, Number(paginacion.limite || $("ecom_filtro_limite").value || 50));
        var pagina = Math.max(1, Number(paginacion.pagina || paginaActual || 1));
        var totalPaginas = Math.max(1, Number(paginacion.total_paginas || Math.ceil(total / limite) || 1));
        var desde = total === 0 ? 0 : ((pagina - 1) * limite) + 1;
        var hasta = Math.min(total, pagina * limite);
        var resumen = $("ecom_paginacion_resumen");
        if (resumen) {
            resumen.textContent = total
                ? "Mostrando " + desde + "-" + hasta + " de " + total + " productos"
                : "Sin productos para los filtros actuales";
        }
        var actual = $("ecom_pagina_actual");
        if (actual) {
            actual.textContent = "Pagina " + pagina + " de " + totalPaginas;
        }
        var anterior = $("ecom_pagina_anterior");
        var siguiente = $("ecom_pagina_siguiente");
        if (anterior) { anterior.disabled = pagina <= 1; }
        if (siguiente) { siguiente.disabled = pagina >= totalPaginas; }
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
            publicacion_existente: "Ya tiene publicacion",
            publicacion_existente_no_borrador: "Ya publicado/pausado",
            sku_agotado_requiere_confirmar_agotado: "Confirma agotado",
            confirmar_revision_requerido: "Confirma revision"
        };
        return mapa[bloqueo] || bloqueo;
    }

    function estadoPublicacionHtml(item) {
        var estado = String(item.estatus_publicacion || "");
        if (!estado) {
            return "";
        }
        var clase = estado === "publicado" ? "badge-light-success" : (estado === "borrador" ? "badge-light-warning" : "badge-light-secondary");
        return "<div class=\"mt-1\"><span class=\"badge " + clase + "\">" + escapeHtml(estado) + "</span></div>";
    }

    function renderCandidatos(items) {
        if (!items.length) {
            $("ecom_publicaciones_body").innerHTML = "<tr><td colspan=\"9\" class=\"text-center text-muted py-8\">Sin candidatos para los filtros actuales.</td></tr>";
            actualizarSeleccionLote();
            return;
        }
        $("ecom_publicaciones_body").innerHTML = items.map(function (item) {
            var idSku = String(item.id_sku || "");
            var estatus = String(item.estatus_publicacion || "");
            if (idSku && seleccionLote[idSku]) {
                estatusSeleccionLote[idSku] = estatus;
            }
            return "<tr>" +
                "<td><input class=\"form-check-input ecom-lote-check\" type=\"checkbox\" value=\"" + escapeHtml(idSku) + "\" data-estatus=\"" + escapeHtml(estatus) + "\"" + (idSku && seleccionLote[idSku] ? " checked" : "") + "></td>" +
                "<td><img class=\"ecom-product-img\" src=\"" + escapeHtml(imagenUrl(item.url_imagen)) + "\" alt=\"\"></td>" +
                "<td><div class=\"fw-bold\">" + escapeHtml(item.nombre_publico || "") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.sku || "") + " | " + escapeHtml(item.codigo_producto || "") + "</div>" + estadoPublicacionHtml(item) + "</td>" +
                "<td>" + escapeHtml(item.marca || "Sin marca") + "</td>" +
                "<td>" + escapeHtml(item.categoria || "Sin categoria") + "</td>" +
                "<td class=\"text-end fw-semibold\">" + dinero(item.precio || 0) + "</td>" +
                "<td>" + disponibilidadBadge(item.disponibilidad_publica_sugerida) + "</td>" +
                "<td>" + bloqueosHtml(item) + "</td>" +
                "<td class=\"text-end\"><button class=\"btn btn-sm btn-light-primary ecom-preparar\" type=\"button\" data-sku=\"" + escapeHtml(item.id_sku || "") + "\">Preparar</button></td>" +
            "</tr>";
        }).join("");
        actualizarSeleccionLote();
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

    function renderPreviewInicial() {
        $("ecom_preview_publicacion").innerHTML = "<div class=\"d-flex flex-wrap justify-content-between align-items-center gap-2\">" +
            "<div class=\"text-muted py-2\">Selecciona un SKU publicable para preparar su ficha ecommerce sin guardar cambios.</div>" +
        "</div>";
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
        var taxonomia = data.taxonomia_publicacion || {};
        var idPublicacion = Number(producto.id_publicacion || 0);
        var estatus = String(producto.estatus_publicacion || "");
        var bloqueosSinExistente = bloqueos.filter(function (bloqueo) { return bloqueo !== "publicacion_existente"; });
        var puedeGuardar = bloqueosSinExistente.length === 0 && (!estatus || estatus === "borrador");
        var puedeGuardarCuraduria = bloqueosSinExistente.length === 0 && idPublicacion > 0 && (estatus === "borrador" || estatus === "publicado" || estatus === "pausado");
        var puedePublicar = idPublicacion > 0 && estatus === "borrador";
        var estaPublicado = idPublicacion > 0 && estatus === "publicado";
        var agotado = String(producto.disponibilidad_publica_sugerida || "") === "agotado";
        $("ecom_preview_publicacion").innerHTML =
            "<div class=\"d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4\">" +
                "<div>" +
                    "<div class=\"fw-bold\">Editando publicacion ecommerce</div>" +
                    "<div class=\"text-muted fs-8\">Puedes cerrar este panel para seguir revisando la tabla sin perder la seleccion del lote.</div>" +
                "</div>" +
                "<button class=\"btn btn-sm btn-light\" type=\"button\" id=\"ecom_cerrar_preparacion\">Cerrar edicion</button>" +
            "</div>" +
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
                    (estatus ? "<div class=\"mb-2\"><span class=\"badge " + (estatus === "publicado" ? "badge-light-success" : "badge-light-warning") + "\">" + escapeHtml(estatus) + "</span></div>" : "") +
                    "<div class=\"text-muted fs-7\">Los precio, imagen, marca y categoria seguiran viniendo vivos desde ERP. La publicacion solo guarda curaduria.</div>" +
                "</div>" +
                "<div class=\"col-lg-4\">" +
                    "<div class=\"border rounded p-4 bg-light\">" +
                        "<div class=\"fw-bold mb-2\">Campos sugeridos</div>" +
                        "<div class=\"fs-7 mb-1\"><span class=\"text-muted\">Slug:</span> <code>" + escapeHtml(pub.slug || "") + "</code></div>" +
                        "<div class=\"fs-7 mb-1\"><span class=\"text-muted\">Presentacion:</span> " + escapeHtml(pub.presentacion_publica || "Sin dato") + "</div>" +
                        "<div class=\"fs-7 mb-1\"><span class=\"text-muted\">Mascotas:</span> " + escapeHtml(pub.mascota_especie || "Por definir") + "</div>" +
                        "<div class=\"fs-7 mb-3\"><span class=\"text-muted\">Necesidades:</span> " + escapeHtml(necesidades.length ? necesidades.join(", ") : "Por definir") + "</div>" +
                        (bloqueos.length ? "<div class=\"ecom-block-list\">" + bloqueos.map(function (b) { return "<span class=\"badge badge-light-warning\">" + escapeHtml(etiquetaBloqueo(b)) + "</span>"; }).join("") + "</div>" : "<span class=\"badge badge-light-success\">Listo para guardar borrador</span>") +
                    "</div>" +
                "</div>" +
                "<div class=\"col-12\">" +
                    "<div class=\"border rounded p-4\">" +
                        "<div class=\"row g-3\" id=\"ecom_publicacion_form\" data-id-sku=\"" + escapeHtml(producto.id_sku || "") + "\" data-id-publicacion=\"" + escapeHtml(idPublicacion || "") + "\">" +
                            "<div class=\"col-lg-6\"><label class=\"form-label fw-semibold\">Titulo publico</label><input class=\"form-control form-control-solid\" data-field=\"titulo_publico\" value=\"" + escapeHtml(pub.titulo_publico || producto.nombre || "") + "\"></div>" +
                            "<div class=\"col-lg-6\"><label class=\"form-label fw-semibold\">Slug</label><input class=\"form-control form-control-solid\" data-field=\"slug\" value=\"" + escapeHtml(pub.slug || "") + "\"></div>" +
                            "<div class=\"col-lg-4\"><label class=\"form-label fw-semibold\">Presentacion comercial opcional</label><input class=\"form-control form-control-solid\" data-field=\"presentacion_publica\" value=\"" + escapeHtml(pub.presentacion_publica || producto.presentacion_base || "") + "\"><div class=\"text-muted fs-8 mt-1\">Texto visible, no sustituye caracteristicas ERP.</div></div>" +
                            "<div class=\"col-lg-4\"><label class=\"form-label fw-semibold\">Mascotas</label>" + mascotasCheckboxesHtml(taxonomia, pub.mascota_especie || "") + "</div>" +
                            "<div class=\"col-lg-4\"><label class=\"form-label fw-semibold\">Necesidades</label>" + necesidadesCheckboxesHtml(taxonomia, necesidades) + "</div>" +
                            "<div class=\"col-12\"><label class=\"form-label fw-semibold\">Descripcion publica</label><textarea class=\"form-control form-control-solid\" rows=\"3\" data-field=\"descripcion_publica\">" + escapeHtml(pub.descripcion_publica || "") + "</textarea></div>" +
                            "<div class=\"col-12\"><div class=\"border rounded p-4 bg-light\"><div class=\"fw-bold fs-7 mb-3\">Visibilidad en ecommerce</div><div class=\"row g-3\">" +
                                togglePublicacionHtml("mostrar_precio", "Mostrar precio", pub.mostrar_precio) +
                                togglePublicacionHtml("mostrar_disponibilidad", "Mostrar disponibilidad", pub.mostrar_disponibilidad) +
                                togglePublicacionHtml("permite_cotizacion", "Permite cotizacion", pub.permite_cotizacion) +
                                togglePublicacionHtml("permite_whatsapp", "Permite WhatsApp", pub.permite_whatsapp) +
                                togglePublicacionHtml("destacado", "Destacado", pub.destacado) +
                            "</div><div class=\"text-muted fs-8 mt-3\">Si ocultas disponibilidad, la API publica respondera ese producto como consultar disponibilidad sin mostrar disponible, pocas piezas o agotado.</div></div></div>" +
                            avisoAgotadoHtml(agotado) +
                            "<div class=\"col-12 d-flex flex-wrap gap-2 justify-content-end\">" +
                                (estaPublicado ? "<span class=\"badge badge-light-success align-self-center\">Producto publicado en API publica</span>" : "") +
                                (puedeGuardar && !idPublicacion ? "<button type=\"button\" class=\"btn btn-light-primary\" id=\"ecom_guardar_borrador\">Guardar borrador</button>" : "") +
                                (puedeGuardarCuraduria ? "<button type=\"button\" class=\"btn btn-light-primary\" id=\"ecom_guardar_curaduria\">Guardar cambios</button>" : "") +
                                (puedePublicar ? "<button type=\"button\" class=\"btn btn-success\" id=\"ecom_publicar_borrador\">Publicar en ecommerce</button>" : "") +
                            "</div>" +
                        "</div>" +
                    "</div>" +
                "</div>" +
            "</div>";

        var btnGuardar = $("ecom_guardar_borrador");
        if (btnGuardar) {
            btnGuardar.addEventListener("click", guardarBorradorActual);
        }
        var btnCuraduria = $("ecom_guardar_curaduria");
        if (btnCuraduria) {
            btnCuraduria.addEventListener("click", guardarCuraduriaActual);
        }
        var btnPublicar = $("ecom_publicar_borrador");
        if (btnPublicar) {
            btnPublicar.addEventListener("click", publicarBorradorActual);
        }
        var btnCerrar = $("ecom_cerrar_preparacion");
        if (btnCerrar) {
            btnCerrar.addEventListener("click", renderPreviewInicial);
        }
    }

    function avisoAgotadoHtml(agotado) {
        if (!agotado) { return ""; }
        return "<div class=\"col-12\">" +
            "<div class=\"alert alert-warning py-3 mb-0\">" +
                "<div class=\"fw-bold fs-7 mb-1\">Producto sin stock</div>" +
                "<div class=\"fs-8 mb-3\">Puedes publicarlo para que el cliente lo vea como agotado o lo consulte por WhatsApp. No genera pedido, no descuenta inventario y no aparta producto.</div>" +
                "<div class=\"form-check form-check-custom form-check-solid\">" +
                    "<input class=\"form-check-input\" type=\"checkbox\" id=\"ecom_confirmar_agotado_publicacion\">" +
                    "<label class=\"form-check-label fs-7\" for=\"ecom_confirmar_agotado_publicacion\">Publicar aunque no haya stock</label>" +
                "</div>" +
            "</div>" +
        "</div>";
    }

    function confirmarAgotadoPublicacion() {
        var check = $("ecom_confirmar_agotado_publicacion");
        return check ? check.checked : false;
    }

    function mascotasCheckboxesHtml(taxonomia, valorActual) {
        var mascotas = (taxonomia && Array.isArray(taxonomia.mascotas)) ? taxonomia.mascotas : [];
        var seleccionadas = String(valorActual || "").split(",").map(function (valor) { return valor.trim(); }).filter(function (valor) { return valor !== ""; });
        if (!mascotas.length) { return "<div class=\"text-muted fs-8\">Sin mascotas configuradas.</div>"; }
        return "<div class=\"d-flex flex-column gap-2\">" + mascotas.map(function (item) {
            var codigo = String(item.codigo || "");
            return "<label class=\"form-check form-check-custom form-check-solid fs-7\">" +
                "<input class=\"form-check-input ecom-mascota-check\" type=\"checkbox\" value=\"" + escapeHtml(codigo) + "\"" + (seleccionadas.indexOf(codigo) !== -1 ? " checked" : "") + ">" +
                "<span class=\"form-check-label\">" + escapeHtml(item.nombre || codigo) + "</span>" +
            "</label>";
        }).join("") + "</div>";
    }

    function necesidadesCheckboxesHtml(taxonomia, actuales) {
        var necesidades = (taxonomia && Array.isArray(taxonomia.necesidades)) ? taxonomia.necesidades : [];
        var seleccionadas = Array.isArray(actuales) ? actuales.map(String) : [];
        if (!necesidades.length) { return "<div class=\"text-muted fs-8\">Sin necesidades configuradas.</div>"; }
        return "<div class=\"d-flex flex-column gap-2\">" + necesidades.map(function (item) {
            var codigo = String(item.codigo || "");
            return "<label class=\"form-check form-check-custom form-check-solid fs-7\">" +
                "<input class=\"form-check-input ecom-necesidad-check\" type=\"checkbox\" value=\"" + escapeHtml(codigo) + "\"" + (seleccionadas.indexOf(codigo) !== -1 ? " checked" : "") + ">" +
                "<span class=\"form-check-label\">" + escapeHtml(item.nombre || codigo) + "</span>" +
            "</label>";
        }).join("") + "</div>";
    }

    function checkedPublicacion(valor) {
        return String(valor) === "1" || valor === true || Number(valor || 0) === 1 ? " checked" : "";
    }

    function togglePublicacionHtml(campo, label, valor) {
        return "<div class=\"col-md-6 col-xl-4\">" +
            "<label class=\"form-check form-switch form-check-custom form-check-solid fs-7\">" +
                "<input class=\"form-check-input\" type=\"checkbox\" data-field=\"" + escapeHtml(campo) + "\"" + checkedPublicacion(valor) + ">" +
                "<span class=\"form-check-label\">" + escapeHtml(label) + "</span>" +
            "</label>" +
        "</div>";
    }

    function datosFormularioPublicacion() {
        var form = $("ecom_publicacion_form");
        var datos = {
            id_sku: form ? form.getAttribute("data-id-sku") : "",
            id_publicacion: form ? form.getAttribute("data-id-publicacion") : ""
        };
        if (!form) { return datos; }
        Array.prototype.forEach.call(form.querySelectorAll("[data-field]"), function (campo) {
            if (campo.type === "checkbox") {
                datos[campo.getAttribute("data-field")] = campo.checked ? "1" : "0";
            } else {
                datos[campo.getAttribute("data-field")] = campo.value || "";
            }
        });
        datos.necesidades = Array.prototype.map.call(form.querySelectorAll(".ecom-necesidad-check:checked"), function (check) {
            return check.value || "";
        }).filter(function (valor) { return valor !== ""; }).join(",");
        datos.mascota_especie = Array.prototype.map.call(form.querySelectorAll(".ecom-mascota-check:checked"), function (check) {
            return check.value || "";
        }).filter(function (valor) { return valor !== ""; }).join(",");
        return datos;
    }

    function guardarBorradorActual() {
        var datos = datosFormularioPublicacion();
        datos.autorizar = "ECOMMERCE_PUBLICO_PUBLICACION_BORRADOR";
        setEstado("Guardando borrador...", "badge-light-info");
        postForm("/ecommercePublico/publicaciones_guardar_borrador_erp", datos).then(function (response) {
            if (response.error) { throw new Error(response.mensaje || "No se pudo guardar borrador"); }
            setEstado("Borrador guardado", "badge-light-success");
            cargarPreparacion(datos.id_sku);
            cargarAuditoria();
        }).catch(function (error) {
            setEstado("Error", "badge-light-danger");
            $("ecom_preview_publicacion").insertAdjacentHTML("afterbegin", "<div class=\"alert alert-danger\">" + escapeHtml(error.message || String(error)) + "</div>");
        });
    }

    function guardarCuraduriaActual() {
        var datos = datosFormularioPublicacion();
        datos.autorizar = "ECOMMERCE_PUBLICO_PUBLICACION_CURADURIA";
        setEstado("Guardando cambios...", "badge-light-info");
        postForm("/ecommercePublico/publicaciones_guardar_curaduria_erp", datos).then(function (response) {
            if (response.error) { throw new Error(response.mensaje || "No se pudo guardar curaduria"); }
            setEstado("Cambios guardados", "badge-light-success");
            cargarPreparacion(datos.id_sku);
            cargarAuditoria();
        }).catch(function (error) {
            setEstado("Error", "badge-light-danger");
            $("ecom_preview_publicacion").insertAdjacentHTML("afterbegin", "<div class=\"alert alert-danger\">" + escapeHtml(error.message || String(error)) + "</div>");
        });
    }

    function skusSeleccionadosLote(filtroEstatus) {
        var skus = [];
        Object.keys(seleccionLote).forEach(function (idTexto) {
            var estatus = estatusSeleccionLote[idTexto] || "";
            if (filtroEstatus && estatus !== filtroEstatus) { return; }
            var id = Number(idTexto || 0);
            if (id > 0 && skus.indexOf(id) === -1) {
                skus.push(id);
            }
        });
        return skus;
    }

    function registrarSeleccionCheck(check) {
        var id = String(check.value || "");
        if (!id) { return; }
        if (check.checked) {
            seleccionLote[id] = true;
            estatusSeleccionLote[id] = check.getAttribute("data-estatus") || "";
            return;
        }
        delete seleccionLote[id];
        delete estatusSeleccionLote[id];
    }

    function limpiarSeleccionLote() {
        seleccionLote = {};
        estatusSeleccionLote = {};
        Array.prototype.forEach.call(document.querySelectorAll(".ecom-lote-check"), function (check) {
            check.checked = false;
        });
        actualizarSeleccionLote();
    }

    function datosConfiguracionLote() {
        var datos = {};
        Array.prototype.forEach.call(document.querySelectorAll("[data-lote-config]"), function (campo) {
            var valor = campo.value;
            if (valor === "") { return; }
            datos[campo.getAttribute("data-lote-config")] = valor;
        });
        datos.crear_borrador_si_no_existe = $("ecom_lote_config_crear_borrador") && $("ecom_lote_config_crear_borrador").checked ? "1" : "0";
        return datos;
    }

    function resumenResultadoLote(depurar) {
        var errores = (depurar.resultados || []).filter(function (resultado) {
            return resultado && resultado.ok !== true;
        }).slice(0, 5).map(function (resultado) {
            var bloqueos = (resultado.bloqueos || resultado.bloqueos_publicabilidad || []).join(", ");
            return "SKU " + (resultado.id_sku || "") + ": " + (bloqueos || resultado.mensaje || "sin detalle");
        });
        var advertencias = (depurar.resultados || []).filter(function (resultado) {
            return resultado && resultado.ok === true && (resultado.bloqueos_publicabilidad || []).length;
        }).slice(0, 5).map(function (resultado) {
            return "SKU " + (resultado.id_sku || "") + ": borrador creado, pendiente " + resultado.bloqueos_publicabilidad.join(", ");
        });
        return errores.concat(advertencias).join("\n");
    }

    function actualizarSeleccionLote() {
        var total = Object.keys(seleccionLote).length;
        var contador = $("ecom_lote_seleccionados");
        if (contador) { contador.textContent = total + " seleccionados"; }
        var all = $("ecom_lote_check_all");
        if (all) {
            var visibles = document.querySelectorAll(".ecom-lote-check");
            var visiblesSeleccionados = 0;
            Array.prototype.forEach.call(visibles, function (check) {
                if (check.checked) { visiblesSeleccionados++; }
            });
            all.checked = visibles.length > 0 && visiblesSeleccionados === visibles.length;
            all.indeterminate = visiblesSeleccionados > 0 && visiblesSeleccionados < visibles.length;
        }
    }

    function guardarBorradoresLote() {
        var skus = skusSeleccionadosLote("");
        if (!skus.length) {
            window.alert("Selecciona al menos un producto.");
            return;
        }
        if (!window.confirm("Guardar borradores ecommerce para " + skus.length + " productos seleccionados?")) {
            return;
        }
        setEstado("Guardando lote...", "badge-light-info");
        postForm("/ecommercePublico/publicaciones_lote_borrador_erp", {
            autorizar: "ECOMMERCE_PUBLICO_LOTE_BORRADOR",
            id_skus: skus.join(",")
        }).then(function (response) {
            if (response.error) { throw new Error(response.mensaje || "No se pudo guardar lote"); }
            var depurar = response.depurar || {};
            (depurar.resultados || []).forEach(function (resultado) {
                if (!resultado || resultado.ok !== true) { return; }
                var id = String(resultado.id_sku || "");
                if (id && seleccionLote[id]) {
                    estatusSeleccionLote[id] = "borrador";
                }
            });
            setEstado("Lote: " + Number(depurar.total_ok || 0) + " ok", "badge-light-success");
            cargarTodo();
        }).catch(function (error) {
            setEstado("Error", "badge-light-danger");
            window.alert(error.message || "No se pudo guardar lote.");
        });
    }

    function aplicarConfiguracionLote() {
        var skus = skusSeleccionadosLote("");
        var config = datosConfiguracionLote();
        var campos = Object.keys(config).filter(function (campo) { return campo !== "crear_borrador_si_no_existe"; });
        if (!skus.length) {
            window.alert("Selecciona al menos un producto.");
            return;
        }
        if (!campos.length && config.crear_borrador_si_no_existe !== "1") {
            window.alert("Selecciona al menos una configuracion para aplicar.");
            return;
        }
        if (!window.confirm("Aplicar configuracion masiva a " + skus.length + " productos seleccionados?")) {
            return;
        }
        config.autorizar = "ECOMMERCE_PUBLICO_LOTE_CONFIGURACION";
        config.id_skus = skus.join(",");
        setEstado("Aplicando configuracion...", "badge-light-info");
        postForm("/ecommercePublico/publicaciones_lote_configuracion_erp", config).then(function (response) {
            if (response.error) { throw new Error(response.mensaje || "No se pudo aplicar configuracion"); }
            var depurar = response.depurar || {};
            (depurar.resultados || []).forEach(function (resultado) {
                if (!resultado || resultado.ok !== true) { return; }
                var id = String(resultado.id_sku || "");
                if (id && seleccionLote[id]) {
                    estatusSeleccionLote[id] = estatusSeleccionLote[id] || "borrador";
                }
            });
            setEstado("Config: " + Number(depurar.total_ok || 0) + " ok", "badge-light-success");
            if (Number(depurar.total_error || 0) > 0 || resumenResultadoLote(depurar) !== "") {
                window.alert("Configuracion masiva procesada.\nOK: " + Number(depurar.total_ok || 0) + "\nErrores: " + Number(depurar.total_error || 0) + "\n\n" + resumenResultadoLote(depurar));
            }
            cargarTodo();
        }).catch(function (error) {
            setEstado("Error", "badge-light-danger");
            window.alert(error.message || "No se pudo aplicar configuracion.");
        });
    }

    function publicarBorradoresLote() {
        var skus = skusSeleccionadosLote("borrador");
        if (!skus.length) {
            window.alert("Selecciona productos en estado borrador.");
            return;
        }
        if (!window.confirm("Publicar " + skus.length + " borradores seleccionados en el API publico?")) {
            return;
        }
        setEstado("Publicando lote...", "badge-light-info");
        postForm("/ecommercePublico/publicaciones_lote_publicar_erp", {
            autorizar: "ECOMMERCE_PUBLICO_LOTE_PUBLICAR",
            id_skus: skus.join(","),
            confirmar_revision: "1",
            confirmar_agotado: $("ecom_lote_confirmar_agotados") && $("ecom_lote_confirmar_agotados").checked ? "1" : "0"
        }).then(function (response) {
            if (response.error) { throw new Error(response.mensaje || "No se pudo publicar lote"); }
            var depurar = response.depurar || {};
            (depurar.resultados || []).forEach(function (resultado) {
                if (!resultado || resultado.ok !== true) { return; }
                var id = String(resultado.id_sku || "");
                if (id) {
                    delete seleccionLote[id];
                    delete estatusSeleccionLote[id];
                }
            });
            setEstado("Publicados: " + Number(depurar.total_ok || 0), "badge-light-success");
            cargarTodo();
        }).catch(function (error) {
            setEstado("Error", "badge-light-danger");
            window.alert(error.message || "No se pudo publicar lote.");
        });
    }

    function publicarBorradorActual() {
        var datos = datosFormularioPublicacion();
        if (!window.confirm("Publicar este producto en el API publico del ecommerce?")) {
            return;
        }
        if ($("ecom_confirmar_agotado_publicacion") && !confirmarAgotadoPublicacion()) {
            setEstado("Falta confirmar agotado", "badge-light-warning");
            $("ecom_preview_publicacion").insertAdjacentHTML("afterbegin", "<div class=\"alert alert-warning\">Este producto esta agotado. Marca Publicar aunque no haya stock para publicarlo y permitir consulta por WhatsApp sin generar pedido.</div>");
            return;
        }
        datos.autorizar = "ECOMMERCE_PUBLICO_PUBLICAR_BORRADOR";
        datos.confirmar_revision = "1";
        datos.confirmar_agotado = confirmarAgotadoPublicacion() ? "1" : "0";
        setEstado("Publicando...", "badge-light-info");
        postForm("/ecommercePublico/publicaciones_publicar_borrador_erp", datos).then(function (response) {
            if (response.error) { throw new Error(response.mensaje || "No se pudo publicar"); }
            setEstado("Publicado", "badge-light-success");
            cargarPreparacion(datos.id_sku);
            cargarTodo();
        }).catch(function (error) {
            setEstado("Error", "badge-light-danger");
            $("ecom_preview_publicacion").insertAdjacentHTML("afterbegin", "<div class=\"alert alert-danger\">" + escapeHtml(error.message || String(error)) + "</div>");
        });
    }

    document.addEventListener("DOMContentLoaded", function () {
        $("ecom_recargar").addEventListener("click", cargarTodo);
        var filtroBusqueda = $("ecom_filtro_busqueda");
        if (filtroBusqueda) {
            var timerBusqueda = null;
            filtroBusqueda.addEventListener("input", function () {
                clearTimeout(timerBusqueda);
                timerBusqueda = setTimeout(resetearPaginaYCargarAuditoria, 300);
            });
        }
        $("ecom_filtro_modo").addEventListener("change", resetearPaginaYCargar);
        $("ecom_filtro_estatus").addEventListener("change", resetearPaginaYCargar);
        $("ecom_filtro_limite").addEventListener("change", resetearPaginaYCargar);
        $("ecom_pagina_anterior").addEventListener("click", function () {
            if (paginaActual <= 1) { return; }
            paginaActual -= 1;
            cargarAuditoria();
        });
        $("ecom_pagina_siguiente").addEventListener("click", function () {
            var totalPaginas = Number(paginacionActual.total_paginas || 1);
            if (paginaActual >= totalPaginas) { return; }
            paginaActual += 1;
            cargarAuditoria();
        });
        $("ecom_lote_limpiar").addEventListener("click", limpiarSeleccionLote);
        $("ecom_lote_borrador").addEventListener("click", guardarBorradoresLote);
        $("ecom_lote_config_aplicar").addEventListener("click", aplicarConfiguracionLote);
        $("ecom_lote_publicar").addEventListener("click", publicarBorradoresLote);
        $("ecom_lote_check_all").addEventListener("change", function (event) {
            Array.prototype.forEach.call(document.querySelectorAll(".ecom-lote-check"), function (check) {
                check.checked = event.target.checked;
                registrarSeleccionCheck(check);
            });
            actualizarSeleccionLote();
        });
        $("ecom_publicaciones_body").addEventListener("click", function (event) {
            if (event.target.classList.contains("ecom-lote-check")) {
                registrarSeleccionCheck(event.target);
                actualizarSeleccionLote();
                return;
            }
            var boton = event.target.closest(".ecom-preparar");
            if (!boton) { return; }
            cargarPreparacion(boton.getAttribute("data-sku"));
        });
        cargarTodo();
    });
})();
