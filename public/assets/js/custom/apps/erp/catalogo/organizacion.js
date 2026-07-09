"use strict";

(function () {
    var propuestas = [];
    var fusiones = [];
    var fusionesError = "";
    var fusionTimers = {};
    var permisos = window.CATALOGO_PERMISOS || {};

    function escapeHtml(value) {
        var div = document.createElement("div");
        div.textContent = value == null ? "" : String(value);
        return div.innerHTML;
    }

    function escapeAttr(value) {
        return escapeHtml(value).replace(/"/g, "&quot;").replace(/'/g, "&#39;");
    }

    function request(url, data) {
        return fetch(url, {
            method: data ? "POST" : "GET",
            headers: data ? {"Content-Type": "application/x-www-form-urlencoded; charset=UTF-8"} : {},
            body: data ? new URLSearchParams(data).toString() : null,
            credentials: "same-origin"
        }).then(function (response) { return response.json(); });
    }

    function render() {
        var texto = document.getElementById("organizacion_buscar").value.trim().toLowerCase();
        var estado = document.getElementById("organizacion_estado").value;
        var visibles = propuestas.filter(function (item) {
            return (!estado || item.estatus === estado) &&
                [item.sku, item.nombre_maestro, item.nombre_actual, item.nombre_proveedor, item.nombre_propuesto].join(" ").toLowerCase().indexOf(texto) !== -1;
        });
        document.getElementById("organizacion_total").textContent = visibles.length;
        document.getElementById("organizacion_lista").innerHTML = visibles.map(function (item) {
            var acciones = item.estatus === "pendiente" && permisos.editar
                ? "<button class=\"btn btn-sm btn-icon btn-light-success\" title=\"Aprobar propuesta\" data-accion=\"aprobar\"><i class=\"bi bi-check-lg\"></i></button> " +
                  "<button class=\"btn btn-sm btn-icon btn-light-danger\" title=\"Descartar propuesta\" data-accion=\"descartar\"><i class=\"bi bi-x-lg\"></i></button>"
                : "<span class=\"badge badge-light-primary\">" + escapeHtml(item.estatus) + "</span>";
            return "<tr data-revision=\"" + escapeHtml(item.id_revision_nombre) + "\"><td class=\"fw-bold\">" + escapeHtml(item.sku) +
                "</td><td>" + escapeHtml(item.nombre_maestro) + "</td><td>" + escapeHtml(item.nombre_actual) +
                "</td><td class=\"text-muted fs-7\">" + escapeHtml(item.nombre_proveedor) +
                "</td><td><input class=\"form-control\" data-nombre-propuesto value=\"" + escapeAttr(item.nombre_propuesto) + "\"" +
                (item.estatus === "pendiente" && permisos.editar ? "" : " disabled") + "></td><td class=\"text-end\">" + acciones + "</td></tr>";
        }).join("") || "<tr><td colspan=\"6\" class=\"text-center text-muted py-10\">No hay propuestas con estos filtros</td></tr>";
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-24
     * Proposito: muestra el historial de fusiones como auditoria de solo lectura.
     * Impacto: Catalogo ERP; ayuda a revisar fusiones sin habilitar reversa automatica.
     */
    function renderFusiones() {
        var total = document.getElementById("fusion_historial_total");
        var lista = document.getElementById("fusion_historial_lista");
        if (!total || !lista) {
            return;
        }
        total.textContent = fusiones.length;
        if (fusionesError) {
            lista.innerHTML = "<tr><td colspan=\"6\" class=\"text-center text-muted py-10\">" +
                "<div class=\"fw-bold text-warning\">Historial no disponible</div><div class=\"fs-7\">" + escapeHtml(fusionesError) + "</div></td></tr>";
            return;
        }
        lista.innerHTML = fusiones.map(function (item) {
            var origen = (item.codigo_origen || "ID " + item.id_producto_origen) + " - " + (item.nombre_origen || "Producto no disponible");
            var destino = (item.codigo_destino || "ID " + item.id_producto_destino) + " - " + (item.nombre_destino || "Producto no disponible");
            return "<tr><td class=\"text-muted fs-7\">" + escapeHtml(item.fecha_registro || "") +
                "</td><td><div class=\"fw-bold\">" + escapeHtml(origen) + "</div><span class=\"badge badge-light\">" + escapeHtml(item.estatus_origen || "sin estatus") +
                "</span></td><td><div class=\"fw-bold\">" + escapeHtml(destino) + "</div><span class=\"badge badge-light-success\">" + escapeHtml(item.estatus_destino || "sin estatus") +
                "</span></td><td class=\"text-muted fs-7\">" + escapeHtml(item.motivo || "Sin motivo registrado") +
                "</td><td><span class=\"badge badge-light-primary\">" + escapeHtml(item.skus_movidos || 0) +
                "</span></td><td><span class=\"badge badge-light-warning\">Requiere snapshot</span></td></tr>";
        }).join("") || "<tr><td colspan=\"6\" class=\"text-center text-muted py-10\">Sin fusiones registradas</td></tr>";
    }

    function resolver(button) {
        var row = button.closest("[data-revision]");
        button.disabled = true;
        request("/catalogoerp/propuesta_nombre_resolver", {
            id_revision_nombre: row.getAttribute("data-revision"),
            accion: button.getAttribute("data-accion"),
            nombre_propuesto: row.querySelector("[data-nombre-propuesto]").value
        }).then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje);
            }
            return cargar();
        }).catch(function (error) {
            Swal.fire({text: error.message, icon: "error", confirmButtonText: "Aceptar"});
            button.disabled = false;
        });
    }

    function normalizarImagenUrl(url) {
        url = url || "";
        if (/^https?:\/\//i.test(url) || url.indexOf("/") === 0) {
            return url;
        }
        return "/" + url;
    }

    function configurarBusquedaFusion(tipo) {
        var input = document.getElementById("fusion_buscar_" + tipo);
        var resultados = document.getElementById("fusion_resultados_" + tipo);
        if (!input || !resultados) {
            return;
        }
        input.addEventListener("input", function () {
            clearTimeout(fusionTimers[tipo]);
            var texto = input.value.trim();
            if (texto.length < 2) {
                resultados.innerHTML = "";
                return;
            }
            fusionTimers[tipo] = setTimeout(function () {
                request("/catalogoerp/fusion_buscar_productos?q=" + encodeURIComponent(texto)).then(function (response) {
                    if (response.error) {
                        throw new Error(response.mensaje);
                    }
                    renderResultadosFusion(tipo, response.depurar || []);
                }).catch(function (error) {
                    resultados.innerHTML = "<div class=\"alert alert-light-danger mb-0\">" + escapeHtml(error.message) + "</div>";
                });
            }, 250);
        });
        resultados.addEventListener("click", function (event) {
            var button = event.target.closest("[data-fusion-seleccionar]");
            if (button) {
                seleccionarProductoFusion(tipo, JSON.parse(button.getAttribute("data-fusion-seleccionar")));
            }
        });
    }

    function renderResultadosFusion(tipo, productos) {
        var resultados = document.getElementById("fusion_resultados_" + tipo);
        resultados.innerHTML = productos.map(function (producto) {
            var payload = escapeAttr(JSON.stringify(producto));
            return "<button type=\"button\" class=\"btn btn-light w-100 text-start mb-2\" data-fusion-seleccionar=\"" + payload + "\">" +
                "<div class=\"d-flex justify-content-between gap-4\"><div><div class=\"fw-bold\">" + escapeHtml(producto.codigo_producto + " - " + producto.nombre) +
                "</div><div class=\"text-muted fs-7 text-truncate\">" + escapeHtml(producto.skus || "Sin SKU") + "</div></div>" +
                "<div class=\"text-end text-nowrap\"><span class=\"badge badge-light-primary\">" + escapeHtml(producto.total_skus) + " SKU</span><br><span class=\"badge badge-light\">" + escapeHtml(producto.total_imagenes) + " img</span></div></div></button>";
        }).join("") || "<div class=\"alert alert-light-warning mb-0\">Sin coincidencias</div>";
    }

    function seleccionarProductoFusion(tipo, producto) {
        var form = document.getElementById("fusion_form");
        var campo = tipo === "origen" ? "id_producto_origen" : "id_producto_destino";
        form.querySelector("[name='" + campo + "']").value = producto.id_producto_erp;
        document.getElementById("fusion_" + tipo + "_seleccion").innerHTML =
            "<div class=\"alert alert-light-primary mb-0\"><div class=\"fw-bold\">" + escapeHtml(producto.codigo_producto + " - " + producto.nombre) +
            "</div><div class=\"text-muted fs-7\">" + escapeHtml(producto.total_skus) + " SKU | " + escapeHtml(producto.total_imagenes) + " imagenes | ID " + escapeHtml(producto.id_producto_erp) + "</div></div>";
        document.getElementById("fusion_resultados_" + tipo).innerHTML = "";
        document.getElementById("fusion_buscar_" + tipo).value = "";
        document.getElementById("fusion_preview").innerHTML = "";
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-24
     * Proposito: previsualiza fusion solo con motivo y advertencia de alto impacto.
     * Impacto: Catalogo ERP; reduce fusiones accidentales sin cambiar esquema ni ejecutar reversas.
     */
    function previsualizarFusion() {
        var form = document.getElementById("fusion_form");
        var origen = form.querySelector("[name='id_producto_origen']").value;
        var destino = form.querySelector("[name='id_producto_destino']").value;
        var motivo = form.querySelector("[name='motivo']").value.trim();
        var box = document.getElementById("fusion_error");
        box.classList.add("d-none");
        if (motivo.length < 10) {
            box.textContent = "Indica un motivo claro antes de revisar la fusion.";
            box.classList.remove("d-none");
            return;
        }
        request("/catalogoerp/fusion_previsualizar?id_producto_origen=" + encodeURIComponent(origen) + "&id_producto_destino=" + encodeURIComponent(destino)).then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje);
            }
            var data = response.depurar;
            document.getElementById("fusion_preview").innerHTML =
                "<div class=\"border rounded p-5\"><div class=\"alert alert-light-danger\"><div class=\"fw-bold mb-1\">Accion de alto impacto</div>Al confirmar, los SKU, imagenes, vinculos de canal, propuestas de nombre y categorias secundarias del origen se moveran al destino. El origen quedara con estatus fusionado. Esta pantalla no tiene reversa automatica; una correccion posterior requiere revision de historial.</div>" +
                "<div class=\"row g-5\"><div class=\"col-lg-6\">" + bloqueFusion("Origen", data.origen, data.skus_origen, data.imagenes_origen) +
                "</div><div class=\"col-lg-6\">" + bloqueFusion("Destino", data.destino, data.skus_destino, data.imagenes_destino) +
                "</div></div>" + (permisos.editar ? "<div class=\"text-end mt-5\"><button class=\"btn btn-danger\" type=\"submit\"><i class=\"bi bi-arrow-left-right\"></i> Confirmar fusion</button></div>" : "") + "</div>";
        }).catch(function (error) {
            box.textContent = error.message;
            box.classList.remove("d-none");
        });
    }

    function bloqueFusion(titulo, producto, skus, imagenes) {
        skus = skus || [];
        imagenes = imagenes || [];
        return "<div class=\"border rounded p-4 h-100\"><div class=\"text-muted fs-7 text-uppercase\">" + escapeHtml(titulo) + "</div>" +
            "<div class=\"fw-bold fs-5\">" + escapeHtml(producto.codigo_producto + " - " + producto.nombre) + "</div>" +
            "<div class=\"d-flex flex-wrap gap-2 mt-3\"><span class=\"badge badge-light-primary\">" + escapeHtml(skus.length) + " SKU</span><span class=\"badge badge-light\">" + escapeHtml(producto.total_imagenes || imagenes.length) + " imagenes</span><span class=\"badge badge-light-success\">" + escapeHtml(producto.estatus) + "</span></div>" +
            "<div class=\"separator my-4\"></div><div class=\"fw-bold fs-7 mb-2\">SKU</div>" + listaSkus(skus) +
            "<div class=\"fw-bold fs-7 mt-4 mb-2\">Imagenes</div>" + listaImagenes(imagenes) + "</div>";
    }

    function listaSkus(skus) {
        return skus.map(function (sku) {
            return "<div class=\"d-flex justify-content-between gap-3 border-bottom py-2\"><span class=\"fw-bold\">" + escapeHtml(sku.sku) + "</span><span class=\"text-muted fs-7 text-end\">" + escapeHtml(sku.nombre) + "</span></div>";
        }).join("") || "<div class=\"text-muted fs-7\">Sin SKU</div>";
    }

    function listaImagenes(imagenes) {
        return "<div class=\"d-flex flex-wrap gap-2\">" + (imagenes.map(function (imagen) {
            return "<span class=\"symbol symbol-50px\"><span class=\"symbol-label\" title=\"" + escapeAttr(imagen.url_imagen) + "\" style=\"background-image:url('" + escapeAttr(normalizarImagenUrl(imagen.url_imagen)) + "');background-size:cover;background-position:center\"></span></span>";
        }).join("") || "<span class=\"text-muted fs-7\">Sin imagenes</span>") + "</div>";
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-24
     * Proposito: confirma fusion de productos con motivo obligatorio y doble confirmacion.
     * Impacto: Catalogo ERP; protege una accion sin reversa automatica en la estructura actual.
     */
    function fusionar(event) {
        event.preventDefault();
        if (!permisos.editar) {
            return;
        }
        var form = event.currentTarget;
        var box = document.getElementById("fusion_error");
        var data = {};
        new FormData(form).forEach(function (value, key) { data[key] = value; });
        box.classList.add("d-none");
        if (!data.motivo || String(data.motivo).trim().length < 10) {
            box.textContent = "Indica un motivo claro de al menos 10 caracteres para fusionar.";
            box.classList.remove("d-none");
            return;
        }
        Swal.fire({
            title: "Confirmar fusion",
            text: "Esta accion mueve datos al producto destino y no tiene reversa automatica en esta pantalla.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Fusionar",
            cancelButtonText: "Cancelar"
        }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }
            request("/catalogoerp/fusionar_productos", data).then(function (response) {
                if (response.error) {
                    throw new Error(response.mensaje);
                }
                Swal.fire({text: response.mensaje, icon: "success", confirmButtonText: "Aceptar"}).then(function () {
                    window.location.reload();
                });
            }).catch(function (error) {
                box.textContent = error.message;
                box.classList.remove("d-none");
            });
        });
    }

    function cargar() {
        return Promise.all([
            request("/catalogoerp/propuestas_nombres"),
            request("/catalogoerp/fusiones_listar").catch(function (error) {
                return {error: true, mensaje: error.message || "No se pudo consultar el historial de fusiones", depurar: []};
            })
        ]).then(function (responses) {
            if (responses[0].error) {
                throw new Error(responses[0].mensaje);
            }
            propuestas = responses[0].depurar || [];
            fusionesError = responses[1].error ? responses[1].mensaje : "";
            fusiones = responses[1].error ? [] : (responses[1].depurar || []);
            render();
            renderFusiones();
        });
    }

    document.addEventListener("DOMContentLoaded", function () {
        if (!permisos.editar) {
            document.getElementById("fusion_form").closest(".card").classList.add("d-none");
        }
        document.getElementById("organizacion_buscar").addEventListener("input", render);
        document.getElementById("organizacion_estado").addEventListener("change", render);
        document.getElementById("organizacion_lista").addEventListener("click", function (event) {
            var button = event.target.closest("[data-accion]");
            if (button) {
                resolver(button);
            }
        });
        document.getElementById("fusion_previsualizar").addEventListener("click", previsualizarFusion);
        document.getElementById("fusion_form").addEventListener("submit", fusionar);
        configurarBusquedaFusion("origen");
        configurarBusquedaFusion("destino");
        cargar();
    });
})();
