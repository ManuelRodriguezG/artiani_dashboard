"use strict";
(function () {
    var placeholderImagen = "data:image/svg+xml;charset=UTF-8,%3Csvg%20xmlns='http://www.w3.org/2000/svg'%20viewBox='0%200%2052%2052'%3E%3Crect%20width='52'%20height='52'%20rx='8'%20fill='%23f1f3f6'/%3E%3Cpath%20d='M10%2039h32L31%2027l-7%208-5-7z'%20fill='%23c8ced8'/%3E%3Ccircle%20cx='19'%20cy='18'%20r='5'%20fill='%23d7dce5'/%3E%3C/svg%3E";
    var skuSeleccionado = "";

    function $(id) { return document.getElementById(id); }

    function escapeHtml(value) {
        var div = document.createElement("div");
        div.textContent = value == null ? "" : String(value);
        return div.innerHTML;
    }

    function dinero(value) {
        return new Intl.NumberFormat("es-MX", {style: "currency", currency: "MXN"}).format(Number(value || 0));
    }

    function precioHtml(item) {
        if (Number((item || {}).precio_general_activo || 0) !== 1 || Number((item || {}).precio || 0) <= 0) {
            return "<span class=\"badge badge-light-danger\">Sin lista precio activa</span>";
        }
        return dinero(item.precio || 0);
    }

    function imagenUrl(url) {
        url = String(url || "").trim();
        if (!url) { return placeholderImagen; }
        if (/^(https?:)?\/\//i.test(url) || url.indexOf("data:") === 0 || url.charAt(0) === "/") { return url; }
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

    function setEstado(texto, clase) {
        var el = $("ecom_ctl_estado");
        if (!el) { return; }
        el.className = "badge " + (clase || "badge-light-primary");
        el.textContent = texto;
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-08-06
     * Proposito: mostrar diagnostico operativo cuando una publicacion no aparece en el ecommerce.
     * Impacto: traduce bloqueos reales del backend para que el usuario sepa que corregir antes de publicar.
     */
    function mostrarDiagnostico(titulo, tipo, detalle) {
        var el = $("ecom_ctl_diagnostico");
        if (!el) { return; }
        var clase = tipo === "success" ? "alert-success" : (tipo === "danger" ? "alert-danger" : (tipo === "warning" ? "alert-warning" : "alert-info"));
        var bloques = Array.isArray(detalle && detalle.bloqueos) ? detalle.bloqueos : [];
        var resultados = Array.isArray(detalle && detalle.resultados) ? detalle.resultados : [];
        var html = "<div class=\"fw-bold mb-1\">" + escapeHtml(titulo || "Diagnostico ecommerce") + "</div>";
        if (detalle && detalle.mensaje) {
            html += "<div class=\"fs-7 mb-2\">" + escapeHtml(detalle.mensaje) + "</div>";
        }
        if (bloques.length) {
            html += "<div class=\"ecom-chip-list mb-2\">" + bloques.map(function (bloqueo) {
                return "<span class=\"badge badge-light-warning\">" + escapeHtml(etiquetaBloqueo(bloqueo)) + "</span>";
            }).join("") + "</div>";
        }
        if (resultados.length) {
            html += resumenResultadosLoteHtml(resultados);
        }
        if (!bloques.length && !resultados.length && (!detalle || !detalle.mensaje)) {
            html += "<div class=\"fs-7 text-muted\">Sin observaciones.</div>";
        }
        el.className = "alert " + clase + " border mb-4";
        el.innerHTML = html;
    }

    function ocultarDiagnostico() {
        var el = $("ecom_ctl_diagnostico");
        if (!el) { return; }
        el.className = "alert alert-light border d-none mb-4";
        el.innerHTML = "";
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-08-11
     * Proposito: hacer evidente que un producto fue enviado a preparacion desde una lista grande.
     * Impacto: resalta fila activa y actualiza el editor sin forzar al usuario a bajar por toda la tabla.
     */
    function enfocarEditor() {
        var panel = $("ecom_ctl_editor_col") || $("ecom_ctl_editor");
        if (!panel) { return; }
        panel.classList.add("ecom-editor-focus");
        window.setTimeout(function () {
            panel.classList.remove("ecom-editor-focus");
        }, 1600);
    }

    function actualizarBadgeEditor(texto, clase) {
        var badge = $("ecom_ctl_editor_badge");
        if (!badge) { return; }
        badge.className = "badge " + (clase || "badge-light");
        badge.textContent = texto || "Sin seleccion";
    }

    function resaltarSeleccion(idSku) {
        skuSeleccionado = String(idSku || "");
        Array.prototype.forEach.call(document.querySelectorAll("[data-sku-row]"), function (row) {
            row.classList.toggle("ecom-ctl-row-active", row.getAttribute("data-sku-row") === skuSeleccionado);
        });
    }

    function confirmarAgotadoActivo() {
        var local = $("ecom_ctl_editor_confirmar_agotado");
        if (local) { return local.checked; }
        var global = $("ecom_ctl_confirmar_agotados");
        return global ? global.checked : false;
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-30
     * Proposito: centralizar filtros del panel de gobierno ecommerce.
     * Impacto: permite buscar por SKU/nombre/categoria y controlar granel/disponibilidad sin editar la API publica.
     */
    function filtros() {
        return {
            q: $("ecom_ctl_busqueda").value || "",
            categoria_texto: $("ecom_ctl_categoria").value || "",
            estatus_publicacion: $("ecom_ctl_estatus").value || "",
            disponibilidad: $("ecom_ctl_disponibilidad").value || "",
            granel: $("ecom_ctl_granel").value || "",
            limite: $("ecom_ctl_limite").value || "100"
        };
    }

    function cargarTodo() {
        setEstado("Cargando...", "badge-light-info");
        return Promise.all([cargarFase(), cargarReadiness(), cargarLista()]).then(function () {
            setEstado("Listo", "badge-light-success");
        }).catch(function (error) {
            setEstado("Error", "badge-light-danger");
            $("ecom_ctl_body").innerHTML = "<tr><td colspan=\"7\"><div class=\"alert alert-danger mb-0\">" + escapeHtml(error.message || String(error)) + "</div></td></tr>";
        });
    }

    function cargarFase() {
        return getJson("/ecommercePublico/publicaciones_fase_estado_erp", {base_url: "http://panel.com.local"}).then(function (response) {
            if (response.error) { throw new Error(response.mensaje || "No se pudo cargar fase ecommerce"); }
            renderFase(response.depurar || {}, response.mensaje || "");
        });
    }

    function renderFase(data, mensaje) {
        var lista = data.pendientes_para_cierre || [];
        var listo = data.puede_pasar_a_fase_2 === true;
        $("ecom_ctl_fase_box").className = "alert " + (listo ? "alert-success" : "alert-primary") + " d-flex align-items-start justify-content-between gap-4";
        $("ecom_ctl_fase_titulo").textContent = listo ? "Fase 1 lista para cierre operativo" : "Fase 1: publicaciones y control en progreso";
        $("ecom_ctl_fase_detalle").textContent = listo
            ? "El panel ya cumple criterios mínimos; el siguiente bloque es API de catálogo robusta."
            : "Pendientes para cierre: " + (lista.length ? lista.join(", ") : "validacion operativa");
        $("ecom_ctl_fase_estado").className = "badge " + (listo ? "badge-light-success" : "badge-light-primary");
        $("ecom_ctl_fase_estado").textContent = data.estado || (mensaje || "En progreso");
        $("ecom_ctl_fase_siguiente").textContent = "Siguiente: " + (data.fase_2_siguiente || "api_catalogo_robusta");
    }

    function cargarReadiness() {
        return getJson("/ecommercePublico/publicaciones_readiness_erp", {base_url: "http://panel.com.local"}).then(function (response) {
            if (response.error) { throw new Error(response.mensaje || "No se pudo cargar readiness"); }
            var pubs = (response.depurar || {}).publicaciones || {};
            var resumen = response.depurar || {};
            $("ecom_ctl_publicados").textContent = Number(pubs.total_publicadas || 0);
            $("ecom_ctl_borradores").textContent = Number(pubs.total_borradores || 0);
            $("ecom_ctl_pausados").textContent = Number(pubs.total_pausadas || 0);
            $("ecom_ctl_publicables").textContent = Number(pubs.skus_publicables_fase_1 || resumen.skus_publicables_fase_1 || 0);
        });
    }

    function cargarLista() {
        return getJson("/ecommercePublico/publicaciones_auditar_erp", filtros()).then(function (response) {
            if (response.error) { throw new Error(response.mensaje || "No se pudo cargar productos"); }
            renderLista((response.depurar || {}).candidatos || []);
        });
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

    function estadoBadge(estado) {
        if (!estado) { return "<span class=\"badge badge-light\">Sin pub.</span>"; }
        var clase = estado === "publicado" ? "badge-light-success" : (estado === "pausado" ? "badge-light-warning" : "badge-light-info");
        return "<span class=\"badge " + clase + "\">" + escapeHtml(estado) + "</span>";
    }

    function bloqueosHtml(item) {
        var bloqueos = item.bloqueos_publicacion || [];
        if (!bloqueos.length) { return ""; }
        return "<div class=\"ecom-chip-list mt-1\">" + bloqueos.map(function (bloqueo) {
            return "<span class=\"badge badge-light-warning\">" + escapeHtml(etiquetaBloqueo(bloqueo)) + "</span>";
        }).join("") + "</div>";
    }

    function etiquetaBloqueo(bloqueo) {
        var mapa = {
            precio_general_faltante: "Sin lista precio activa",
            imagen_faltante: "Sin imagen",
            categoria_principal_faltante: "Sin categoria",
            venta_fraccionaria_bloqueada_fase_1: "Granel",
            publicacion_existente: "Ya publicado",
            publicacion_existente_no_borrador: "Ya publicado/pausado",
            tabla_erp_ecommerce_publicaciones_pendiente: "Tabla de publicaciones pendiente",
            id_publicacion_o_id_sku_requerido: "Falta seleccionar producto",
            publicacion_borrador_no_encontrada: "No hay borrador",
            solo_borrador_puede_publicarse: "Solo se publica desde borrador",
            slug_requerido: "Slug requerido",
            titulo_publico_requerido: "Titulo publico requerido",
            slug_ya_usado_por_otro_sku: "Slug usado por otro SKU",
            sku_agotado_requiere_confirmar_agotado: "Confirmar agotado",
            confirmar_revision_requerido: "Confirma revision",
            sku_no_encontrado_o_inactivo: "SKU inactivo/no encontrado",
            estatus_publicacion_no_permitido: "Estatus no permitido"
        };
        return mapa[bloqueo] || bloqueo;
    }

    function bloqueosRespuesta(response) {
        var depurar = response && response.depurar ? response.depurar : {};
        var bloqueos = depurar.bloqueos_publicacion || depurar.bloqueos || [];
        return Array.isArray(bloqueos) ? bloqueos : [];
    }

    function resumenResultadosLoteHtml(resultados) {
        var fallidos = resultados.filter(function (item) { return !item.ok; });
        if (!fallidos.length) {
            return "<div class=\"fs-7\"><span class=\"badge badge-light-success\">Todos los seleccionados se procesaron correctamente.</span></div>";
        }
        var max = 8;
        var html = "<div class=\"fw-semibold fs-7 mb-1\">No procesados:</div><div class=\"d-flex flex-column gap-1\">";
        fallidos.slice(0, max).forEach(function (item) {
            var bloqueos = Array.isArray(item.bloqueos) ? item.bloqueos : [];
            html += "<div class=\"fs-7\"><span class=\"fw-semibold\">SKU ID " + escapeHtml(item.id_sku || "") + ":</span> " +
                escapeHtml(item.mensaje || "No se pudo procesar") +
                (bloqueos.length ? " <span class=\"text-muted\">(" + escapeHtml(bloqueos.map(etiquetaBloqueo).join(", ")) + ")</span>" : "") +
            "</div>";
        });
        if (fallidos.length > max) {
            html += "<div class=\"fs-8 text-muted\">Y " + (fallidos.length - max) + " mas.</div>";
        }
        html += "</div>";
        return html;
    }

    function renderLista(items) {
        if (!items.length) {
            $("ecom_ctl_body").innerHTML = "<tr><td colspan=\"7\" class=\"text-center text-muted py-8\">Sin productos para estos filtros.</td></tr>";
            actualizarSeleccion();
            return;
        }
        $("ecom_ctl_body").innerHTML = items.map(function (item) {
            var estatus = item.estatus_publicacion || "";
            var activo = String(item.id_sku || "") === skuSeleccionado;
            return "<tr data-sku-row=\"" + escapeHtml(item.id_sku || "") + "\"" + (activo ? " class=\"ecom-ctl-row-active\"" : "") + ">" +
                "<td><input class=\"form-check-input ecom-ctl-check\" type=\"checkbox\" value=\"" + escapeHtml(item.id_sku || "") + "\" data-estatus=\"" + escapeHtml(estatus) + "\"></td>" +
                "<td><img class=\"ecom-control-img\" src=\"" + escapeHtml(imagenUrl(item.url_imagen)) + "\" alt=\"\"></td>" +
                "<td><div class=\"fw-bold\">" + escapeHtml(item.nombre_publico || "") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.sku || "") + " | " + escapeHtml(item.marca || "Sin marca") + " | " + precioHtml(item) + "</div>" + bloqueosHtml(item) + "</td>" +
                "<td>" + escapeHtml(item.categoria || "Sin categoria") + "</td>" +
                "<td>" + disponibilidadBadge(item.disponibilidad_publica_sugerida) + "</td>" +
                "<td>" + estadoBadge(estatus) + "</td>" +
                "<td class=\"text-end\"><button class=\"btn btn-sm btn-light-primary ecom-ctl-editar\" type=\"button\" data-sku=\"" + escapeHtml(item.id_sku || "") + "\">Preparar</button></td>" +
            "</tr>";
        }).join("");
        actualizarSeleccion();
    }

    function seleccionados(filtroEstatus) {
        var salida = [];
        Array.prototype.forEach.call(document.querySelectorAll(".ecom-ctl-check:checked"), function (check) {
            var estatus = check.getAttribute("data-estatus") || "";
            if (filtroEstatus && estatus !== filtroEstatus) { return; }
            var id = Number(check.value || 0);
            if (id > 0 && salida.indexOf(id) === -1) { salida.push(id); }
        });
        return salida;
    }

    function actualizarSeleccion() {
        var total = document.querySelectorAll(".ecom-ctl-check:checked").length;
        $("ecom_ctl_seleccionados").textContent = total + " seleccionados";
        var all = $("ecom_ctl_check_all");
        var disponibles = document.querySelectorAll(".ecom-ctl-check").length;
        all.checked = disponibles > 0 && total === disponibles;
        all.indeterminate = total > 0 && total < disponibles;
    }

    function cargarEditor(idSku, mantenerDiagnostico) {
        if (!mantenerDiagnostico) { ocultarDiagnostico(); }
        resaltarSeleccion(idSku);
        actualizarBadgeEditor("Preparando", "badge-light-info");
        setEstado("Preparando producto...", "badge-light-info");
        $("ecom_ctl_editor").innerHTML = "<div class=\"text-muted py-5 text-center\">Cargando preparacion...</div>";
        enfocarEditor();
        return getJson("/ecommercePublico/publicaciones_preparar_erp", {id_sku: idSku}).then(function (response) {
            if (response.error) { throw new Error(response.mensaje || "No se pudo preparar control"); }
            renderEditor(response.depurar || {});
        }).catch(function (error) {
            actualizarBadgeEditor("Error", "badge-light-danger");
            $("ecom_ctl_editor").innerHTML = "<div class=\"alert alert-danger mb-0\">" + escapeHtml(error.message || String(error)) + "</div>";
        });
    }

    function checked(valor) {
        return Number(valor || 0) === 1 ? " checked" : "";
    }

    function mascotasCheckboxesHtml(taxonomia, valorActual) {
        var mascotas = (taxonomia && Array.isArray(taxonomia.mascotas)) ? taxonomia.mascotas : [];
        var seleccionadas = String(valorActual || "").split(",").map(function (valor) { return valor.trim(); }).filter(function (valor) { return valor !== ""; });
        if (!mascotas.length) { return "<div class=\"text-muted fs-8\">Sin mascotas configuradas.</div>"; }
        return "<div class=\"row g-2\">" + mascotas.map(function (item) {
            var codigo = String(item.codigo || "");
            return "<div class=\"col-6\"><label class=\"form-check form-check-custom form-check-solid fs-7\">" +
                "<input class=\"form-check-input ecom-mascota-check\" type=\"checkbox\" value=\"" + escapeHtml(codigo) + "\"" + (seleccionadas.indexOf(codigo) !== -1 ? " checked" : "") + ">" +
                "<span class=\"form-check-label\">" + escapeHtml(item.nombre || codigo) + "</span>" +
            "</label></div>";
        }).join("") + "</div>";
    }

    function necesidadesCheckboxesHtml(taxonomia, actuales) {
        var necesidades = (taxonomia && Array.isArray(taxonomia.necesidades)) ? taxonomia.necesidades : [];
        var seleccionadas = Array.isArray(actuales) ? actuales.map(String) : [];
        if (!necesidades.length) { return "<div class=\"text-muted fs-8\">Sin necesidades configuradas.</div>"; }
        return "<div class=\"row g-2\">" + necesidades.map(function (item) {
            var codigo = String(item.codigo || "");
            return "<div class=\"col-6\"><label class=\"form-check form-check-custom form-check-solid fs-7\">" +
                "<input class=\"form-check-input ecom-necesidad-check\" type=\"checkbox\" value=\"" + escapeHtml(codigo) + "\"" + (seleccionadas.indexOf(codigo) !== -1 ? " checked" : "") + ">" +
                "<span class=\"form-check-label\">" + escapeHtml(item.nombre || codigo) + "</span>" +
            "</label></div>";
        }).join("") + "</div>";
    }

    function renderEditor(data) {
        var producto = data.producto_vivo_erp || {};
        var pub = data.publicacion_sugerida || {};
        var actual = data.publicacion_actual || {};
        var idPublicacion = Number(actual.id_publicacion || producto.id_publicacion || 0);
        var estatus = String(actual.estatus_publicacion || producto.estatus_publicacion || "");
        var necesidades = pub.necesidades || [];
        var bloqueos = data.bloqueos_publicacion || producto.bloqueos_publicacion || [];
        var disponibilidad = String(producto.disponibilidad_publica_sugerida || "");
        var agotado = disponibilidad === "agotado";
        var taxonomia = data.taxonomia_publicacion || {};
        actualizarBadgeEditor(producto.sku || ("SKU ID " + (producto.id_sku || "")), agotado ? "badge-light-warning" : "badge-light-primary");
        setEstado("Producto preparado", "badge-light-success");
        $("ecom_ctl_editor").innerHTML =
            "<div id=\"ecom_ctl_form\" data-id-sku=\"" + escapeHtml(producto.id_sku || "") + "\" data-id-publicacion=\"" + escapeHtml(idPublicacion || "") + "\">" +
                "<div class=\"d-flex gap-3 mb-4\">" +
                    "<img class=\"ecom-control-img\" src=\"" + escapeHtml(imagenUrl(producto.imagen)) + "\" alt=\"\">" +
                    "<div><div class=\"fw-bold\">" + escapeHtml(producto.nombre || "") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(producto.sku || "") + " | " + precioHtml(producto) + "</div><div class=\"mt-1\">" + estadoBadge(estatus) + " " + disponibilidadBadge(producto.disponibilidad_publica_sugerida) + "</div></div>" +
                "</div>" +
                "<div class=\"alert " + (bloqueos.length ? "alert-warning" : "alert-success") + " py-3 mb-4\">" +
                    "<div class=\"fw-bold fs-7 mb-1\">" + (bloqueos.length ? "Diagnostico antes de publicar" : "Listo para publicar") + "</div>" +
                    (bloqueos.length ? "<div class=\"ecom-chip-list\">" + bloqueos.map(function (b) { return "<span class=\"badge badge-light-warning\">" + escapeHtml(etiquetaBloqueo(b)) + "</span>"; }).join("") + "</div>" : "<div class=\"fs-8\">La ficha cumple precio, imagen, categoria y regla no granel.</div>") +
                "</div>" +
                avisoAgotadoHtml(agotado) +
                "<div class=\"mb-3\"><label class=\"form-label fw-semibold\">Titulo publico</label><input class=\"form-control form-control-solid\" data-field=\"titulo_publico\" value=\"" + escapeHtml(pub.titulo_publico || "") + "\"></div>" +
                "<div class=\"mb-3\"><label class=\"form-label fw-semibold\">Slug</label><input class=\"form-control form-control-solid\" data-field=\"slug\" value=\"" + escapeHtml(pub.slug || "") + "\"></div>" +
                "<div class=\"row g-3 mb-3\">" +
                    "<div class=\"col-6\"><label class=\"form-label fw-semibold\">Mascotas</label>" + mascotasCheckboxesHtml(taxonomia, pub.mascota_especie || "") + "</div>" +
                    "<div class=\"col-6\"><label class=\"form-label fw-semibold\">Orden</label><input class=\"form-control form-control-solid\" data-field=\"orden\" value=\"" + escapeHtml(pub.orden || 0) + "\"></div>" +
                "</div>" +
                "<div class=\"mb-3\"><label class=\"form-label fw-semibold\">Necesidades</label>" + necesidadesCheckboxesHtml(taxonomia, necesidades) + "</div>" +
                "<div class=\"mb-3\"><label class=\"form-label fw-semibold\">Presentacion comercial opcional</label><input class=\"form-control form-control-solid\" data-field=\"presentacion_publica\" value=\"" + escapeHtml(pub.presentacion_publica || "") + "\"><div class=\"text-muted fs-8 mt-1\">Solo texto visible como 2 kg, 12 pzas o paquete. Las caracteristicas reales siguen viniendo del catalogo ERP.</div></div>" +
                "<div class=\"mb-3\"><label class=\"form-label fw-semibold\">Descripcion publica</label><textarea class=\"form-control form-control-solid\" rows=\"3\" data-field=\"descripcion_publica\">" + escapeHtml(pub.descripcion_publica || "") + "</textarea></div>" +
                "<div class=\"row g-2 mb-4\">" +
                    toggle("destacado", "Destacado", pub.destacado) +
                    toggle("mostrar_precio", "Mostrar precio", pub.mostrar_precio) +
                    toggle("mostrar_disponibilidad", "Mostrar disponibilidad", pub.mostrar_disponibilidad) +
                    toggle("permite_cotizacion", "Permite cotizacion", pub.permite_cotizacion) +
                    toggle("permite_whatsapp", "Permite WhatsApp", pub.permite_whatsapp) +
                "</div>" +
                "<div class=\"d-flex flex-wrap gap-2 justify-content-end\">" +
                    (!idPublicacion ? "<button class=\"btn btn-sm btn-light-primary\" type=\"button\" id=\"ecom_ctl_guardar_borrador\">Guardar borrador</button>" : "<button class=\"btn btn-sm btn-light-primary\" type=\"button\" id=\"ecom_ctl_guardar_curaduria\">Guardar control</button>") +
                    (idPublicacion && estatus !== "publicado" ? "<button class=\"btn btn-sm btn-success\" type=\"button\" id=\"ecom_ctl_publicar_uno\">Publicar</button>" : "") +
                    (idPublicacion && estatus === "publicado" ? "<button class=\"btn btn-sm btn-warning\" type=\"button\" id=\"ecom_ctl_pausar_uno\">Pausar</button>" : "") +
                    (idPublicacion && estatus === "pausado" ? "<button class=\"btn btn-sm btn-light-success\" type=\"button\" id=\"ecom_ctl_reactivar_uno\">Reactivar</button>" : "") +
                "</div>" +
            "</div>";
        enlazarEditor();
    }

    function avisoAgotadoHtml(agotado) {
        if (!agotado) { return ""; }
        return "<div class=\"alert alert-warning py-3 mb-4\">" +
            "<div class=\"fw-bold fs-7 mb-1\">Producto sin stock</div>" +
            "<div class=\"fs-8 mb-3\">Puedes publicarlo si quieres que el cliente lo vea como agotado o para consultar disponibilidad. Esto no descuenta inventario ni crea pedidos.</div>" +
            "<div class=\"form-check form-check-custom form-check-solid\">" +
                "<input class=\"form-check-input\" type=\"checkbox\" id=\"ecom_ctl_editor_confirmar_agotado\">" +
                "<label class=\"form-check-label fs-7\" for=\"ecom_ctl_editor_confirmar_agotado\">Publicar aunque no haya stock</label>" +
            "</div>" +
        "</div>";
    }

    function toggle(campo, label, valor) {
        return "<div class=\"col-md-6\"><div class=\"form-check form-switch form-check-custom form-check-solid\"><input class=\"form-check-input\" type=\"checkbox\" data-field=\"" + campo + "\"" + checked(valor) + "><label class=\"form-check-label fs-7\">" + escapeHtml(label) + "</label></div></div>";
    }

    function datosEditor() {
        var form = $("ecom_ctl_form");
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

    function guardarBorrador() {
        var datos = datosEditor();
        datos.autorizar = "ECOMMERCE_PUBLICO_PUBLICACION_BORRADOR";
        setEstado("Guardando...", "badge-light-info");
        ocultarDiagnostico();
        return postForm("/ecommercePublico/publicaciones_guardar_borrador_erp", datos).then(procesarCambio);
    }

    function guardarCuraduria() {
        var datos = datosEditor();
        datos.autorizar = "ECOMMERCE_PUBLICO_PUBLICACION_CURADURIA";
        setEstado("Guardando...", "badge-light-info");
        ocultarDiagnostico();
        return postForm("/ecommercePublico/publicaciones_guardar_curaduria_erp", datos).then(procesarCambio);
    }

    function cambiarEstatusUno(estatus) {
        var datos = datosEditor();
        datos.autorizar = "ECOMMERCE_PUBLICO_GOBIERNO_ESTATUS";
        datos.estatus_publicacion = estatus;
        datos.confirmar_agotado = confirmarAgotadoActivo() ? "1" : "0";
        if (estatus === "publicado" && $("ecom_ctl_editor_confirmar_agotado") && !confirmarAgotadoActivo()) {
            setEstado("Falta confirmar agotado", "badge-light-warning");
            mostrarDiagnostico("Confirma publicacion sin stock", "warning", {
                mensaje: "Este producto esta agotado. Marca Publicar aunque no haya stock para publicarlo de forma intencional.",
                bloqueos: ["sku_agotado_requiere_confirmar_agotado"]
            });
            return Promise.resolve();
        }
        setEstado("Aplicando...", "badge-light-info");
        ocultarDiagnostico();
        return postForm("/ecommercePublico/publicaciones_estatus_erp", datos).then(procesarCambio);
    }

    function procesarCambio(response) {
        if (response.error) {
            mostrarDiagnostico("No se pudo publicar/cambiar estatus", "warning", {
                mensaje: response.mensaje || "Revisa los bloqueos de publicacion.",
                bloqueos: bloqueosRespuesta(response)
            });
            throw new Error(response.mensaje || "No se pudo aplicar cambio");
        }
        setEstado("Cambio aplicado", "badge-light-success");
        mostrarDiagnostico("Cambio aplicado", "success", {
            mensaje: response.mensaje || "La publicacion quedo actualizada. Si esta publicada y cumple reglas, ya queda visible para la API publica."
        });
        cargarTodo();
        var sku = (response.depurar || {}).id_sku || (datosEditor().id_sku || "");
        if (sku) { cargarEditor(sku, true); }
    }

    function enlazarEditor() {
        var btn;
        btn = $("ecom_ctl_guardar_borrador"); if (btn) { btn.addEventListener("click", function () { guardarBorrador().catch(alertar); }); }
        btn = $("ecom_ctl_guardar_curaduria"); if (btn) { btn.addEventListener("click", function () { guardarCuraduria().catch(alertar); }); }
        btn = $("ecom_ctl_publicar_uno"); if (btn) { btn.addEventListener("click", function () { cambiarEstatusUno("publicado").catch(alertar); }); }
        btn = $("ecom_ctl_pausar_uno"); if (btn) { btn.addEventListener("click", function () { cambiarEstatusUno("pausado").catch(alertar); }); }
        btn = $("ecom_ctl_reactivar_uno"); if (btn) { btn.addEventListener("click", function () { cambiarEstatusUno("publicado").catch(alertar); }); }
    }

    function alertaConfirmacion(texto) {
        return window.confirm(texto);
    }

    function loteBorrador() {
        var skus = seleccionados("");
        if (!skus.length) { return window.alert("Selecciona productos."); }
        if (!alertaConfirmacion("Guardar borradores para " + skus.length + " productos?")) { return; }
        ocultarDiagnostico();
        postForm("/ecommercePublico/publicaciones_lote_borrador_erp", {
            autorizar: "ECOMMERCE_PUBLICO_LOTE_BORRADOR",
            id_skus: skus.join(",")
        }).then(procesarLote).catch(alertar);
    }

    function lotePublicar() {
        var skus = seleccionados("");
        if (!skus.length) { return window.alert("Selecciona productos."); }
        if (!alertaConfirmacion("Publicar/reactivar " + skus.length + " productos seleccionados?")) { return; }
        ocultarDiagnostico();
        postForm("/ecommercePublico/publicaciones_lote_estatus_erp", {
            autorizar: "ECOMMERCE_PUBLICO_LOTE_ESTATUS",
            id_skus: skus.join(","),
            estatus_publicacion: "publicado",
            confirmar_agotado: $("ecom_ctl_confirmar_agotados").checked ? "1" : "0",
            crear_borrador_si_no_existe: "1"
        }).then(procesarLote).catch(alertar);
    }

    function loteEstatus(estatus) {
        var skus = seleccionados("");
        if (!skus.length) { return window.alert("Selecciona productos."); }
        if (!alertaConfirmacion("Cambiar " + skus.length + " productos a " + estatus + "?")) { return; }
        ocultarDiagnostico();
        postForm("/ecommercePublico/publicaciones_lote_estatus_erp", {
            autorizar: "ECOMMERCE_PUBLICO_LOTE_ESTATUS",
            id_skus: skus.join(","),
            estatus_publicacion: estatus
        }).then(procesarLote).catch(alertar);
    }

    function procesarLote(response) {
        var depurar = response.depurar || {};
        var totalOk = Number(depurar.total_ok || 0);
        var totalError = Number(depurar.total_error || 0);
        var tipo = totalError > 0 ? "warning" : "success";
        setEstado("OK " + totalOk + " / Error " + totalError, totalError > 0 ? "badge-light-warning" : "badge-light-success");
        mostrarDiagnostico(totalError > 0 ? "Lote procesado con observaciones" : "Lote procesado", tipo, {
            mensaje: response.mensaje || "",
            resultados: depurar.resultados || []
        });
        cargarTodo();
    }

    function alertar(error) {
        setEstado("Error", "badge-light-danger");
        mostrarDiagnostico("No se pudo completar la accion", "danger", {
            mensaje: error.message || String(error)
        });
    }

    document.addEventListener("DOMContentLoaded", function () {
        var timer = null;
        ["ecom_ctl_busqueda", "ecom_ctl_categoria"].forEach(function (id) {
            $(id).addEventListener("input", function () {
                clearTimeout(timer);
                timer = setTimeout(cargarLista, 300);
            });
        });
        ["ecom_ctl_estatus", "ecom_ctl_disponibilidad", "ecom_ctl_granel", "ecom_ctl_limite"].forEach(function (id) {
            $(id).addEventListener("change", cargarLista);
        });
        $("ecom_control_recargar").addEventListener("click", cargarTodo);
        $("ecom_ctl_lote_borrador").addEventListener("click", loteBorrador);
        $("ecom_ctl_lote_publicar").addEventListener("click", lotePublicar);
        $("ecom_ctl_lote_pausar").addEventListener("click", function () { loteEstatus("pausado"); });
        $("ecom_ctl_lote_reactivar").addEventListener("click", function () { loteEstatus("publicado"); });
        $("ecom_ctl_check_all").addEventListener("change", function (event) {
            Array.prototype.forEach.call(document.querySelectorAll(".ecom-ctl-check"), function (check) {
                check.checked = event.target.checked;
            });
            actualizarSeleccion();
        });
        $("ecom_ctl_body").addEventListener("click", function (event) {
            if (event.target.classList.contains("ecom-ctl-check")) {
                actualizarSeleccion();
                return;
            }
            var btn = event.target.closest(".ecom-ctl-editar");
            if (btn) { cargarEditor(btn.getAttribute("data-sku")); }
        });
        cargarTodo();
    });
})();
