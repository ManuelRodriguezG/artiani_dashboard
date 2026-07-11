"use strict";
(function () {
    var catalogos = {almacenes: []};
    var debounce = null;
    var stream = null;
    var detectorActivo = false;
    var placeholderImagen = "data:image/svg+xml;charset=UTF-8,%3Csvg%20xmlns='http://www.w3.org/2000/svg'%20viewBox='0%200%20400%20300'%3E%3Crect%20width='400'%20height='300'%20fill='%23f1f3f6'/%3E%3Cpath%20d='M80%20225h240l-70-85-55%2065-35-42z'%20fill='%23c8ced8'/%3E%3Ccircle%20cx='135'%20cy='105'%20r='28'%20fill='%23d7dce5'/%3E%3C/svg%3E";

    function $(id) { return document.getElementById(id); }
    function escapeHtml(value) {
        var div = document.createElement("div");
        div.textContent = value == null ? "" : String(value);
        return div.innerHTML;
    }
    function cantidad(value) {
        var numero = Number(String(value == null ? "" : value).replace(",", "."));
        return Number.isFinite(numero) ? numero : 0;
    }
    function dinero(value) {
        return new Intl.NumberFormat("es-MX", {style: "currency", currency: "MXN"}).format(cantidad(value));
    }
    function numero(value) {
        return Number(cantidad(value).toFixed(4)).toString();
    }
    function imagen(item) {
        var url = item.url_imagen || item.imagen || "";
        url = String(url || "").trim();
        if (!url) { return placeholderImagen; }
        if (/^(https?:)?\/\//i.test(url) || url.indexOf("data:") === 0 || url.charAt(0) === "/") {
            return url;
        }
        return "/" + url.replace(/^\/+/, "");
    }
    function requestGet(url, params) {
        var query = new URLSearchParams(params || {}).toString();
        return fetch(url + (query ? "?" + query : ""), {
            method: "GET",
            credentials: "same-origin"
        }).then(function (response) { return response.json(); });
    }
    function estadoBadge(estado) {
        var mapa = {
            disponible: ["badge-light-success", "Disponible"],
            pocas_piezas: ["badge-light-warning", "Pocas piezas"],
            consultar_disponibilidad: ["badge-light-info", "Consultar disponibilidad"],
            sin_control_inventario: ["badge-light-primary", "Sin control de inventario"],
            agotado: ["badge-light-danger", "Agotado"]
        };
        var item = mapa[estado] || ["badge-light-secondary", estado || "Sin estado"];
        return "<span class=\"badge " + item[0] + " fs-6\">" + escapeHtml(item[1]) + "</span>";
    }
    function almacenActual() {
        return $("checker_almacen") ? $("checker_almacen").value : "";
    }
    function cargarCatalogos() {
        return requestGet("/ventas/pos_catalogos_erp", {}).then(function (response) {
            catalogos = response.depurar || {};
            var almacenes = catalogos.almacenes || [];
            $("checker_almacen").innerHTML = "<option value=\"0\">Todas las tiendas</option>" + almacenes.map(function (almacen) {
                return "<option value=\"" + Number(almacen.id_almacen || 0) + "\">" + escapeHtml(almacen.almacen || almacen.nombre || ("Almacen " + almacen.id_almacen)) + "</option>";
            }).join("");
            var primeroVendible = almacenes.find(function (almacen) { return Number(almacen.id_almacen || 0) > 0; });
            if (primeroVendible) {
                $("checker_almacen").value = primeroVendible.id_almacen;
            }
        }).catch(function (error) {
            $("checker_estado").textContent = "No se pudieron cargar almacenes: " + error.message;
        });
    }
    function consultar(params) {
        $("checker_estado").textContent = "Consultando...";
        params = params || {};
        params.id_almacen = almacenActual();
        params.canal = "pos";
        return requestGet("/ventas/pos_checador_precio_erp", params).then(function (response) {
            var data = response.depurar || {};
            renderResultado(data.producto || null, response);
            renderCoincidencias(data.coincidencias || []);
            $("checker_estado").textContent = response.mensaje || "Consulta lista";
        }).catch(function (error) {
            $("checker_estado").textContent = "Error al consultar: " + error.message;
        });
    }
    function buscarActual() {
        var q = ($("checker_q").value || "").trim();
        if (q.length < 2) {
            $("checker_estado").textContent = "Escribe al menos dos caracteres.";
            return;
        }
        consultar({q: q});
    }
    function renderResultado(producto, response) {
        if (!producto) {
            $("checker_resultado").innerHTML = "<div class=\"checker-product p-8 text-center text-muted\">" +
                "<i class=\"bi bi-exclamation-circle fs-1 d-block mb-3\"></i>" +
                "<div class=\"fw-semibold\">" + escapeHtml(response.mensaje || "Producto no encontrado") + "</div>" +
                "<div class=\"fs-7\">Prueba con SKU, codigo de barras o nombre.</div>" +
            "</div>";
            return;
        }
        var disponibilidad = producto.disponibilidad || {};
        var fraccionaria = Number(producto.permite_venta_fraccionaria || 0) === 1;
        $("checker_resultado").innerHTML = "<div class=\"checker-product\">" +
            "<div class=\"row g-0\">" +
                "<div class=\"col-md-5\"><img class=\"checker-product-img\" src=\"" + escapeHtml(imagen(producto)) + "\" alt=\"\"></div>" +
                "<div class=\"col-md-7 p-5\">" +
                    "<div class=\"d-flex flex-wrap gap-2 mb-3\">" + estadoBadge(producto.estado_publico) +
                        (fraccionaria ? "<span class=\"badge badge-light-info fs-7\">Granel permitido</span>" : "") +
                        (Number(producto.permite_venta_sin_existencia || 0) === 1 ? "<span class=\"badge badge-light-warning fs-7\">Venta sin existencia controlada</span>" : "") +
                    "</div>" +
                    "<div class=\"text-muted fs-7 mb-1\">" + escapeHtml(producto.sku || "") + (producto.codigo_barras ? " | " + escapeHtml(producto.codigo_barras) : "") + "</div>" +
                    "<h2 class=\"fw-bold mb-2\">" + escapeHtml(producto.nombre_sku || producto.producto || "") + "</h2>" +
                    "<div class=\"text-muted mb-4\">" + escapeHtml(producto.marca || "") + (producto.categoria ? " | " + escapeHtml(producto.categoria) : "") + "</div>" +
                    "<div class=\"checker-price mb-3\">" + dinero(producto.precio_aplicado || 0) + "</div>" +
                    "<div class=\"text-muted fs-7 mb-4\">Lista: " + escapeHtml(producto.lista_precio_snapshot || "general") + " | Origen: " + escapeHtml(producto.regla_precio_origen || "catalogo") + "</div>" +
                    "<div class=\"checker-meta\">" +
                        "<div class=\"checker-status bg-light-success\"><div class=\"text-muted fs-8 text-uppercase\">Disponible</div><div class=\"fw-bold fs-4\">" + numero(disponibilidad.disponible || 0) + " " + escapeHtml(producto.unidad_venta_label || "") + "</div></div>" +
                        "<div class=\"checker-status bg-light\"><div class=\"text-muted fs-8 text-uppercase\">Apartado</div><div class=\"fw-bold fs-4\">" + numero(disponibilidad.apartada || 0) + "</div></div>" +
                        "<div class=\"checker-status bg-light-primary\"><div class=\"text-muted fs-8 text-uppercase\">Unidades cerradas</div><div class=\"fw-bold fs-4\">" + Number(disponibilidad.unidades_cerradas || 0) + "</div></div>" +
                        "<div class=\"checker-status bg-light-info\"><div class=\"text-muted fs-8 text-uppercase\">Unidades abiertas</div><div class=\"fw-bold fs-4\">" + Number(disponibilidad.unidades_abiertas || 0) + "</div></div>" +
                    "</div>" +
                    "<div class=\"alert alert-info py-3 mt-4 mb-0\">Consulta read-only: el POS revalida precio e inventario al cobrar.</div>" +
                "</div>" +
            "</div>" +
        "</div>";
    }
    function renderCoincidencias(items) {
        if (!items.length) {
            $("checker_coincidencias").innerHTML = "<div class=\"text-muted fs-7\">Sin coincidencias adicionales.</div>";
            return;
        }
        $("checker_coincidencias").innerHTML = items.map(function (item) {
            return "<div class=\"checker-hit mb-2\" data-sku=\"" + Number(item.id_sku || 0) + "\">" +
                "<div class=\"fw-bold\">" + escapeHtml(item.nombre_sku || item.producto || "") + "</div>" +
                "<div class=\"text-muted fs-8\">" + escapeHtml(item.sku || "") + (item.codigo_barras ? " | " + escapeHtml(item.codigo_barras) : "") + "</div>" +
                "<div class=\"d-flex justify-content-between mt-1\"><span>" + dinero(item.precio || 0) + "</span><span class=\"text-muted fs-8\">Disp. " + numero(item.existencia_disponible || 0) + "</span></div>" +
            "</div>";
        }).join("");
    }
    function iniciarCamara() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            $("checker_camera_estado").textContent = "Este navegador no expone camara para la pagina actual.";
            return;
        }
        if (!("BarcodeDetector" in window)) {
            $("checker_camera_estado").textContent = "Tu navegador no tiene lector nativo de codigos; usa busqueda manual o escaner USB.";
            return;
        }
        navigator.mediaDevices.getUserMedia({video: {facingMode: "environment"}, audio: false}).then(function (mediaStream) {
            stream = mediaStream;
            detectorActivo = true;
            $("checker_video").srcObject = stream;
            $("checker_video").classList.remove("d-none");
            $("checker_camera_stop").classList.remove("d-none");
            $("checker_video").play();
            $("checker_camera_estado").textContent = "Apunta al codigo de barras.";
            detectarLoop(new BarcodeDetector({formats: ["ean_13", "ean_8", "code_128", "code_39", "upc_a", "upc_e", "qr_code"]}));
        }).catch(function (error) {
            $("checker_camera_estado").textContent = "No se pudo abrir la camara: " + error.message;
        });
    }
    function detectarLoop(detector) {
        if (!detectorActivo || !$("checker_video") || $("checker_video").readyState < 2) {
            if (detectorActivo) { setTimeout(function () { detectarLoop(detector); }, 250); }
            return;
        }
        detector.detect($("checker_video")).then(function (codigos) {
            if (codigos && codigos.length) {
                var valor = codigos[0].rawValue || "";
                if (valor) {
                    $("checker_q").value = valor;
                    consultar({q: valor});
                    detenerCamara();
                    return;
                }
            }
            if (detectorActivo) { setTimeout(function () { detectarLoop(detector); }, 350); }
        }).catch(function () {
            if (detectorActivo) { setTimeout(function () { detectarLoop(detector); }, 600); }
        });
    }
    function detenerCamara() {
        detectorActivo = false;
        if (stream) {
            stream.getTracks().forEach(function (track) { track.stop(); });
        }
        stream = null;
        $("checker_video").classList.add("d-none");
        $("checker_camera_stop").classList.add("d-none");
        $("checker_camera_estado").textContent = "Camara detenida.";
    }
    function bind() {
        $("checker_buscar").addEventListener("click", buscarActual);
        $("checker_q").addEventListener("keydown", function (event) {
            if (event.key === "Enter") { buscarActual(); }
        });
        $("checker_q").addEventListener("input", function () {
            clearTimeout(debounce);
            debounce = setTimeout(function () {
                if (($("checker_q").value || "").trim().length >= 3) {
                    buscarActual();
                }
            }, 450);
        });
        $("checker_coincidencias").addEventListener("click", function (event) {
            var row = event.target.closest("[data-sku]");
            if (!row) { return; }
            consultar({id_sku: row.getAttribute("data-sku")});
        });
        $("checker_camera_btn").addEventListener("click", iniciarCamara);
        $("checker_camera_stop").addEventListener("click", detenerCamara);
        window.addEventListener("beforeunload", detenerCamara);
    }
    document.addEventListener("DOMContentLoaded", function () {
        bind();
        cargarCatalogos();
    });
})();
