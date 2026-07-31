"use strict";
(function () {
    var placeholderImagen = "data:image/svg+xml;charset=UTF-8,%3Csvg%20xmlns='http://www.w3.org/2000/svg'%20viewBox='0%200%2052%2052'%3E%3Crect%20width='52'%20height='52'%20rx='8'%20fill='%23f1f3f6'/%3E%3Cpath%20d='M10%2039h32L31%2027l-7%208-5-7z'%20fill='%23c8ced8'/%3E%3Ccircle%20cx='19'%20cy='18'%20r='5'%20fill='%23d7dce5'/%3E%3C/svg%3E";

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
        return Promise.all([cargarReadiness(), cargarLista()]).then(function () {
            setEstado("Listo", "badge-light-success");
        }).catch(function (error) {
            setEstado("Error", "badge-light-danger");
            $("ecom_ctl_body").innerHTML = "<tr><td colspan=\"7\"><div class=\"alert alert-danger mb-0\">" + escapeHtml(error.message || String(error)) + "</div></td></tr>";
        });
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
            precio_general_faltante: "Sin precio",
            imagen_faltante: "Sin imagen",
            categoria_principal_faltante: "Sin categoria",
            venta_fraccionaria_bloqueada_fase_1: "Granel",
            publicacion_existente: "Ya publicado",
            sku_agotado_requiere_confirmar_agotado: "Confirmar agotado"
        };
        return mapa[bloqueo] || bloqueo;
    }

    function renderLista(items) {
        if (!items.length) {
            $("ecom_ctl_body").innerHTML = "<tr><td colspan=\"7\" class=\"text-center text-muted py-8\">Sin productos para estos filtros.</td></tr>";
            actualizarSeleccion();
            return;
        }
        $("ecom_ctl_body").innerHTML = items.map(function (item) {
            var estatus = item.estatus_publicacion || "";
            return "<tr>" +
                "<td><input class=\"form-check-input ecom-ctl-check\" type=\"checkbox\" value=\"" + escapeHtml(item.id_sku || "") + "\" data-estatus=\"" + escapeHtml(estatus) + "\"></td>" +
                "<td><img class=\"ecom-control-img\" src=\"" + escapeHtml(imagenUrl(item.url_imagen)) + "\" alt=\"\"></td>" +
                "<td><div class=\"fw-bold\">" + escapeHtml(item.nombre_publico || "") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.sku || "") + " | " + escapeHtml(item.marca || "Sin marca") + " | " + dinero(item.precio || 0) + "</div>" + bloqueosHtml(item) + "</td>" +
                "<td>" + escapeHtml(item.categoria || "Sin categoria") + "</td>" +
                "<td>" + disponibilidadBadge(item.disponibilidad_publica_sugerida) + "</td>" +
                "<td>" + estadoBadge(estatus) + "</td>" +
                "<td class=\"text-end\"><button class=\"btn btn-sm btn-light-primary ecom-ctl-editar\" type=\"button\" data-sku=\"" + escapeHtml(item.id_sku || "") + "\">Controlar</button></td>" +
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

    function cargarEditor(idSku) {
        $("ecom_ctl_editor").innerHTML = "<div class=\"text-muted py-5 text-center\">Cargando control...</div>";
        return getJson("/ecommercePublico/publicaciones_preparar_erp", {id_sku: idSku}).then(function (response) {
            if (response.error) { throw new Error(response.mensaje || "No se pudo preparar control"); }
            renderEditor(response.depurar || {});
        }).catch(function (error) {
            $("ecom_ctl_editor").innerHTML = "<div class=\"alert alert-danger mb-0\">" + escapeHtml(error.message || String(error)) + "</div>";
        });
    }

    function checked(valor) {
        return Number(valor || 0) === 1 ? " checked" : "";
    }

    function renderEditor(data) {
        var producto = data.producto_vivo_erp || {};
        var pub = data.publicacion_sugerida || {};
        var actual = data.publicacion_actual || {};
        var idPublicacion = Number(actual.id_publicacion || producto.id_publicacion || 0);
        var estatus = String(actual.estatus_publicacion || producto.estatus_publicacion || "");
        var necesidades = pub.necesidades || [];
        $("ecom_ctl_editor").innerHTML =
            "<div id=\"ecom_ctl_form\" data-id-sku=\"" + escapeHtml(producto.id_sku || "") + "\" data-id-publicacion=\"" + escapeHtml(idPublicacion || "") + "\">" +
                "<div class=\"d-flex gap-3 mb-4\">" +
                    "<img class=\"ecom-control-img\" src=\"" + escapeHtml(imagenUrl(producto.imagen)) + "\" alt=\"\">" +
                    "<div><div class=\"fw-bold\">" + escapeHtml(producto.nombre || "") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(producto.sku || "") + " | " + dinero(producto.precio || 0) + "</div><div class=\"mt-1\">" + estadoBadge(estatus) + " " + disponibilidadBadge(producto.disponibilidad_publica_sugerida) + "</div></div>" +
                "</div>" +
                "<div class=\"mb-3\"><label class=\"form-label fw-semibold\">Titulo publico</label><input class=\"form-control form-control-solid\" data-field=\"titulo_publico\" value=\"" + escapeHtml(pub.titulo_publico || "") + "\"></div>" +
                "<div class=\"mb-3\"><label class=\"form-label fw-semibold\">Slug</label><input class=\"form-control form-control-solid\" data-field=\"slug\" value=\"" + escapeHtml(pub.slug || "") + "\"></div>" +
                "<div class=\"row g-3 mb-3\">" +
                    "<div class=\"col-6\"><label class=\"form-label fw-semibold\">Mascota</label><input class=\"form-control form-control-solid\" data-field=\"mascota_especie\" value=\"" + escapeHtml(pub.mascota_especie || "") + "\"></div>" +
                    "<div class=\"col-6\"><label class=\"form-label fw-semibold\">Orden</label><input class=\"form-control form-control-solid\" data-field=\"orden\" value=\"" + escapeHtml(pub.orden || 0) + "\"></div>" +
                "</div>" +
                "<div class=\"mb-3\"><label class=\"form-label fw-semibold\">Necesidades</label><input class=\"form-control form-control-solid\" data-field=\"necesidades\" value=\"" + escapeHtml(necesidades.join(",")) + "\"></div>" +
                "<div class=\"mb-3\"><label class=\"form-label fw-semibold\">Presentacion</label><input class=\"form-control form-control-solid\" data-field=\"presentacion_publica\" value=\"" + escapeHtml(pub.presentacion_publica || "") + "\"></div>" +
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
        return datos;
    }

    function guardarBorrador() {
        var datos = datosEditor();
        datos.autorizar = "ECOMMERCE_PUBLICO_PUBLICACION_BORRADOR";
        setEstado("Guardando...", "badge-light-info");
        return postForm("/ecommercePublico/publicaciones_guardar_borrador_erp", datos).then(procesarCambio);
    }

    function guardarCuraduria() {
        var datos = datosEditor();
        datos.autorizar = "ECOMMERCE_PUBLICO_PUBLICACION_CURADURIA";
        setEstado("Guardando...", "badge-light-info");
        return postForm("/ecommercePublico/publicaciones_guardar_curaduria_erp", datos).then(procesarCambio);
    }

    function cambiarEstatusUno(estatus) {
        var datos = datosEditor();
        datos.autorizar = "ECOMMERCE_PUBLICO_GOBIERNO_ESTATUS";
        datos.estatus_publicacion = estatus;
        datos.confirmar_agotado = $("ecom_ctl_confirmar_agotados").checked ? "1" : "0";
        setEstado("Aplicando...", "badge-light-info");
        return postForm("/ecommercePublico/publicaciones_estatus_erp", datos).then(procesarCambio);
    }

    function procesarCambio(response) {
        if (response.error) { throw new Error(response.mensaje || "No se pudo aplicar cambio"); }
        setEstado("Cambio aplicado", "badge-light-success");
        cargarTodo();
        var sku = (response.depurar || {}).id_sku || (datosEditor().id_sku || "");
        if (sku) { cargarEditor(sku); }
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
        postForm("/ecommercePublico/publicaciones_lote_borrador_erp", {
            autorizar: "ECOMMERCE_PUBLICO_LOTE_BORRADOR",
            id_skus: skus.join(",")
        }).then(procesarLote).catch(alertar);
    }

    function lotePublicar() {
        var skus = seleccionados("");
        if (!skus.length) { return window.alert("Selecciona productos."); }
        if (!alertaConfirmacion("Publicar/reactivar " + skus.length + " productos seleccionados?")) { return; }
        postForm("/ecommercePublico/publicaciones_lote_estatus_erp", {
            autorizar: "ECOMMERCE_PUBLICO_LOTE_ESTATUS",
            id_skus: skus.join(","),
            estatus_publicacion: "publicado",
            confirmar_agotado: $("ecom_ctl_confirmar_agotados").checked ? "1" : "0"
        }).then(procesarLote).catch(alertar);
    }

    function loteEstatus(estatus) {
        var skus = seleccionados("");
        if (!skus.length) { return window.alert("Selecciona productos."); }
        if (!alertaConfirmacion("Cambiar " + skus.length + " productos a " + estatus + "?")) { return; }
        postForm("/ecommercePublico/publicaciones_lote_estatus_erp", {
            autorizar: "ECOMMERCE_PUBLICO_LOTE_ESTATUS",
            id_skus: skus.join(","),
            estatus_publicacion: estatus
        }).then(procesarLote).catch(alertar);
    }

    function procesarLote(response) {
        if (response.error) { throw new Error(response.mensaje || "No se pudo procesar lote"); }
        var depurar = response.depurar || {};
        setEstado("OK " + Number(depurar.total_ok || 0) + " / Error " + Number(depurar.total_error || 0), "badge-light-success");
        cargarTodo();
    }

    function alertar(error) {
        setEstado("Error", "badge-light-danger");
        window.alert(error.message || String(error));
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
