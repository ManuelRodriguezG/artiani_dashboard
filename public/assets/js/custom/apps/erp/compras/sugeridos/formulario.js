"use strict";
(function () {
    var items = [];
    var schemaPendiente = false;
    var ocultarCeros = false;
    var puedeCrear = false;
    var puedeEditar = false;
    var modoLectura = false;
    var timer = null;

    function esc(valor) {
        var d = document.createElement("div");
        d.textContent = valor == null ? "" : valor;
        return d.innerHTML;
    }

    function money(valor) {
        return "$" + Number(valor || 0).toFixed(2);
    }

    function post(url, data) {
        return fetch(url, {
            method: "POST",
            headers: {"Content-Type": "application/x-www-form-urlencoded; charset=UTF-8"},
            body: new URLSearchParams(data),
            credentials: "same-origin"
        }).then(function (r) { return r.json(); });
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-08-20
     * Proposito: calcular sugerido de compra desde existencia revisada sin leer ni modificar inventario.
     * Impacto: Compras/Sugerido; calculo visual replicado por backend al guardar.
     */
    function calcularCantidadSugerida(item) {
        var existencia = Math.max(0, Number(item.existencia_revisada || 0));
        var minimo = Math.max(0, Number(item.stock_minimo || 0));
        var maximo = item.stock_maximo === null || item.stock_maximo === "" ? null : Math.max(0, Number(item.stock_maximo || 0));
        var reorden = Math.max(0, Number(item.punto_reorden || 0));
        var factor = Math.max(0.000001, Number(item.factor_conversion || 1));
        var minimaCompra = Math.max(0, Number(item.cantidad_minima || 1));
        var necesidadBase = 0;

        if (maximo !== null && maximo > 0) {
            necesidadBase = Math.max(0, maximo - existencia);
        } else if (reorden > 0) {
            necesidadBase = Math.max(0, reorden - existencia);
        } else if (minimo > 0) {
            necesidadBase = Math.max(0, minimo - existencia);
        }
        if (necesidadBase <= 0) {
            return 0;
        }
        var cantidadCompra = Math.ceil((necesidadBase / factor) * 1000000) / 1000000;
        if (minimaCompra > 0 && cantidadCompra > 0 && cantidadCompra < minimaCompra) {
            cantidadCompra = minimaCompra;
        }
        return Number(cantidadCompra.toFixed(6));
    }

    function cargarCatalogos() {
        return fetch("/compra/sugeridos_catalogos_erp", {credentials: "same-origin"})
            .then(function (r) { return r.json(); })
            .then(function (r) {
                if (r.error) { throw new Error(r.mensaje || "No se pudieron cargar proveedores"); }
                var proveedores = r.depurar && Array.isArray(r.depurar.proveedores) ? r.depurar.proveedores : [];
                document.getElementById("sugerido_proveedor").innerHTML = "<option value=\"\">Seleccionar</option>" + proveedores.map(function (x) {
                    return "<option value=\"" + esc(x.id_proveedor) + "\">" + esc(x.proveedor) + "</option>";
                }).join("");
            });
    }

    function cargarSugerido() {
        var id = Number(document.getElementById("sugerido_id").value || 0);
        if (!id) {
            return fetch("/compra/sugerido_consultar_erp?id_sugerido_compra=0", {credentials: "same-origin"})
                .then(function (r) { return r.json(); })
                .then(function (r) {
                    schemaPendiente = !!(r.depurar && Number(r.depurar.schema_pendiente || 0) === 1);
                    document.getElementById("sugerido_alerta_schema").classList.toggle("d-none", !schemaPendiente);
                    render();
                });
        }
        return fetch("/compra/sugerido_consultar_erp?id_sugerido_compra=" + id, {credentials: "same-origin"})
            .then(function (r) { return r.json(); })
            .then(function (r) {
                if (r.error) { throw new Error(r.mensaje); }
                schemaPendiente = !!(r.depurar && Number(r.depurar.schema_pendiente || 0) === 1);
                document.getElementById("sugerido_alerta_schema").classList.toggle("d-none", !schemaPendiente);
                var sugerido = r.depurar.sugerido || null;
                if (!sugerido) {
                    render();
                    return;
                }
                document.getElementById("sugerido_titulo").textContent = sugerido.folio || "Sugerido de compra";
                document.getElementById("sugerido_estado_texto").textContent = sugerido.estatus || "borrador";
                document.getElementById("sugerido_proveedor").value = sugerido.id_proveedor || "";
                document.getElementById("sugerido_observaciones").value = sugerido.observaciones || "";
                items = (r.depurar.detalle || []).map(function (x) {
                    x.id_sku_erp = Number(x.id_sku_erp || 0);
                    x.id_sku_proveedor = Number(x.id_sku_proveedor || 0);
                    x.factor_conversion = Number(x.factor_conversion || 1);
                    x.cantidad_minima = Number(x.cantidad_minima || 1);
                    x.stock_minimo = Number(x.stock_minimo || 0);
                    x.stock_maximo = x.stock_maximo === null ? null : Number(x.stock_maximo || 0);
                    x.punto_reorden = Number(x.punto_reorden || 0);
                    x.existencia_revisada = Number(x.existencia_revisada || 0);
                    x.cantidad_sugerida = Number(x.cantidad_sugerida || 0);
                    x.cantidad_solicitar = Number(x.cantidad_solicitar || 0);
                    x.costo_estimado = Number(x.costo_estimado || 0);
                    return x;
                });
                render();
            });
    }

    /**
     * IA: Codex GPT-5
     * Fecha: 2026-08-21
     * Proposito: agregar productos al sugerido bajo demanda desde codigos del proveedor, sin cargar variantes internas ni reemplazar la revision actual.
     * Impacto: Compras/Sugerido; la seleccion del proveedor no llena toda la tabla y la busqueda fusiona sin duplicar por relacion proveedor-SKU.
     */
    function consultarProveedor() {
        var proveedor = document.getElementById("sugerido_proveedor").value;
        var q = document.getElementById("sugerido_buscar").value.trim();
        if (!proveedor) {
            Swal.fire({text: "Selecciona un proveedor para buscar productos.", icon: "warning", confirmButtonText: "Aceptar"});
            return;
        }
        if (q.length < 2) {
            document.getElementById("sugerido_resumen").textContent = "Escribe al menos dos caracteres del SKU o producto del proveedor.";
            return;
        }
        document.getElementById("sugerido_resumen").textContent = "Buscando productos del proveedor...";
        fetch("/compra/sugeridos_productos_proveedor_erp?" + new URLSearchParams({id_proveedor: proveedor, q: q, limite: 80}), {credentials: "same-origin"})
            .then(function (r) { return r.json(); })
            .then(function (r) {
                if (r.error) { throw new Error(r.mensaje); }
                var agregados = 0;
                var existentes = {};
                items.forEach(function (item) { existentes[Number(item.id_sku_proveedor || 0)] = true; });
                (r.depurar.items || []).forEach(function (x) {
                    var idRelacion = Number(x.id_sku_proveedor || 0);
                    if (!idRelacion || existentes[idRelacion]) { return; }
                    items.push({
                        id_sku_erp: Number(x.id_sku_erp || x.id_sku || 0),
                        id_sku_proveedor: idRelacion,
                        sku_erp: x.sku_erp || "",
                        sku_proveedor: x.sku_proveedor || x.sku_erp || "",
                        nombre_erp: x.nombre_erp || "",
                        nombre_proveedor: x.nombre_proveedor || x.nombre_erp || "",
                        unidad_compra: x.unidad_compra || "",
                        factor_conversion: Number(x.factor_conversion || 1),
                        cantidad_minima: Number(x.cantidad_minima || 1),
                        stock_minimo: Number(x.stock_minimo || 0),
                        stock_maximo: x.stock_maximo === null ? null : Number(x.stock_maximo || 0),
                        punto_reorden: Number(x.punto_reorden || 0),
                        existencia_revisada: Number(x.existencia_revisada || 0),
                        cantidad_sugerida: Number(x.cantidad_sugerida || 0),
                        cantidad_solicitar: Number(x.cantidad_solicitar || x.cantidad_sugerida || 0),
                        costo_estimado: Number(x.costo_estimado || x.costo_ultimo || 0),
                        observaciones: ""
                    });
                    existentes[idRelacion] = true;
                    agregados++;
                });
                recalcular(false);
                if (agregados <= 0) {
                    document.getElementById("sugerido_resumen").textContent = "No se agregaron productos nuevos con esa busqueda.";
                }
            }).catch(function (e) {
                Swal.fire({text: e.message || "No se pudieron consultar productos", icon: "error", confirmButtonText: "Aceptar"});
                document.getElementById("sugerido_resumen").textContent = "No se pudieron consultar productos.";
            });
    }

    function recalcular(reemplazarCantidadFinal) {
        items.forEach(function (item) {
            item.cantidad_sugerida = calcularCantidadSugerida(item);
            if (reemplazarCantidadFinal || Number(item.cantidad_solicitar || 0) <= 0) {
                item.cantidad_solicitar = item.cantidad_sugerida;
            }
        });
        render();
    }

    function actualizarResumen() {
        var totalPiezas = items.reduce(function (t, x) { return t + Number(x.cantidad_solicitar || 0); }, 0);
        var total = items.reduce(function (t, x) { return t + Number(x.cantidad_solicitar || 0) * Number(x.costo_estimado || 0); }, 0);
        document.getElementById("sugerido_total_piezas").textContent = totalPiezas.toFixed(6);
        document.getElementById("sugerido_total").textContent = money(total);
        document.getElementById("sugerido_resumen").textContent = items.length + " productos consultados; " +
            items.filter(function (x) { return Number(x.cantidad_solicitar || 0) > 0; }).length + " con cantidad a solicitar.";
    }

    function actualizarSugeridoVisual(indice) {
        var nodo = document.querySelector("[data-sugerido-sugerida=\"" + indice + "\"]");
        if (nodo) {
            nodo.textContent = Number(items[indice].cantidad_sugerida || 0).toFixed(6);
        }
        actualizarResumen();
    }

    function render() {
        var visibles = items.filter(function (x) { return !ocultarCeros || Number(x.cantidad_solicitar || 0) > 0 || Number(x.cantidad_sugerida || 0) > 0; });
        var readonly = modoLectura ? " readonly disabled" : "";
        document.getElementById("sugerido_items").innerHTML = visibles.map(function (x) {
            var i = items.indexOf(x);
            var maximo = x.stock_maximo === null || x.stock_maximo === "" ? "-" : Number(x.stock_maximo || 0).toFixed(2);
            return "<tr>" +
                "<td><div class=\"fw-bold\">" + esc(x.sku_proveedor || x.sku_erp) + "</div><div class=\"text-muted fs-8\">SKU ERP: " + esc(x.sku_erp || "-") + "</div></td>" +
                "<td>" + esc(x.nombre_proveedor || x.nombre_erp) + "<div class=\"text-muted fs-8\">" + esc(x.unidad_compra || "") + " | factor " + Number(x.factor_conversion || 1).toFixed(6) + "</div></td>" +
                "<td class=\"text-end\"><input class=\"form-control form-control-sm text-end\" inputmode=\"decimal\" data-sugerido-minimo=\"" + i + "\" value=\"" + Number(x.stock_minimo || 0) + "\"" + readonly + "></td>" +
                "<td class=\"text-end\"><input class=\"form-control form-control-sm text-end\" inputmode=\"decimal\" data-sugerido-maximo=\"" + i + "\" value=\"" + (x.stock_maximo === null || x.stock_maximo === "" ? "" : Number(x.stock_maximo || 0)) + "\"" + readonly + "></td>" +
                "<td class=\"text-end\"><input class=\"form-control form-control-sm text-end\" inputmode=\"decimal\" data-sugerido-reorden=\"" + i + "\" value=\"" + Number(x.punto_reorden || 0) + "\"" + readonly + "></td>" +
                "<td class=\"text-end\"><input class=\"form-control form-control-sm text-end\" inputmode=\"decimal\" data-sugerido-existencia=\"" + i + "\" value=\"" + Number(x.existencia_revisada || 0) + "\"" + readonly + "></td>" +
                "<td class=\"text-end fw-bold\"><span data-sugerido-sugerida=\"" + i + "\">" + Number(x.cantidad_sugerida || 0).toFixed(6) + "</span></td>" +
                "<td class=\"text-end\"><input class=\"form-control form-control-sm text-end\" inputmode=\"decimal\" data-sugerido-cantidad=\"" + i + "\" value=\"" + Number(x.cantidad_solicitar || 0) + "\"" + readonly + "></td>" +
                "<td class=\"text-end\">" + money(x.costo_estimado) + "</td>" +
                "<td><input class=\"form-control form-control-sm\" data-sugerido-obs=\"" + i + "\" value=\"" + esc(x.observaciones || "") + "\"" + readonly + "></td>" +
                "</tr>";
        }).join("") || "<tr><td colspan=\"10\" class=\"text-center text-muted py-8\">Selecciona proveedor para consultar productos vinculados.</td></tr>";

        actualizarResumen();
    }

    function guardar(estatus) {
        if (schemaPendiente) {
            Swal.fire({text: "Primero hay que preparar el esquema de Sugerido de compra con respaldo externo y autorizacion.", icon: "warning", confirmButtonText: "Aceptar"});
            return;
        }
        if (!puedeEditar && !puedeCrear) {
            Swal.fire({text: "No tienes permiso para guardar sugeridos", icon: "warning", confirmButtonText: "Aceptar"});
            return;
        }
        post("/compra/sugerido_guardar_erp", {
            id_sugerido_compra: document.getElementById("sugerido_id").value,
            id_proveedor: document.getElementById("sugerido_proveedor").value,
            observaciones: document.getElementById("sugerido_observaciones").value,
            estatus: estatus,
            items: JSON.stringify(items)
        }).then(function (r) {
            if (r.error) { throw new Error(r.mensaje); }
            Swal.fire({text: r.mensaje, icon: "success", confirmButtonText: "Aceptar"}).then(function () {
                if (r.depurar && r.depurar.id_sugerido_compra) {
                    window.location.href = "/compra/sugerido_compra/" + r.depurar.id_sugerido_compra;
                }
            });
        }).catch(function (e) {
            Swal.fire({text: e.message || "No se pudo guardar", icon: "error", confirmButtonText: "Aceptar"});
        });
    }

    function generarSolicitud() {
        var id = Number(document.getElementById("sugerido_id").value || 0);
        if (!id) {
            Swal.fire({text: "Guarda el sugerido antes de generar solicitud.", icon: "warning", confirmButtonText: "Aceptar"});
            return;
        }
        post("/compra/sugerido_generar_solicitud_erp", {id_sugerido_compra: id}).then(function (r) {
            if (r.error) { throw new Error(r.mensaje); }
            window.location.href = "/compra/editar_solicitud/" + r.depurar.id_solicitud;
        }).catch(function (e) {
            Swal.fire({text: e.message || "No se pudo generar solicitud", icon: "error", confirmButtonText: "Aceptar"});
        });
    }

    document.addEventListener("DOMContentLoaded", function () {
        puedeCrear = Number(document.getElementById("sugerido_puede_crear").value || 0) === 1;
        puedeEditar = Number(document.getElementById("sugerido_puede_editar").value || 0) === 1;
        modoLectura = (document.getElementById("sugerido_modo").value || "editar") === "ver";
        document.getElementById("sugerido_guardar_borrador").classList.toggle("d-none", modoLectura);
        document.getElementById("sugerido_marcar_lista").classList.toggle("d-none", modoLectura);
        document.getElementById("sugerido_generar_solicitud").classList.toggle("d-none", modoLectura);
        document.getElementById("sugerido_recalcular").classList.toggle("d-none", modoLectura);
        document.getElementById("sugerido_buscar_productos").classList.toggle("d-none", modoLectura);
        document.getElementById("sugerido_proveedor").disabled = modoLectura;
        document.getElementById("sugerido_observaciones").readOnly = modoLectura;
        document.getElementById("sugerido_buscar").readOnly = modoLectura;
        cargarCatalogos().then(cargarSugerido).catch(function (e) {
            document.getElementById("sugerido_resumen").textContent = e.message || "No se pudieron cargar proveedores.";
            Swal.fire({text: e.message || "No se pudieron cargar proveedores.", icon: "error", confirmButtonText: "Aceptar"});
        });

        document.getElementById("sugerido_proveedor").addEventListener("change", function () { if (!modoLectura) { items = []; document.getElementById("sugerido_buscar").value = ""; render(); } });
        document.getElementById("sugerido_buscar").addEventListener("keydown", function (e) {
            if (modoLectura) { return; }
            if (e.key === "Enter") {
                e.preventDefault();
                consultarProveedor();
            }
        });
        document.getElementById("sugerido_recalcular").addEventListener("click", function () { recalcular(true); });
        document.getElementById("sugerido_buscar_productos").addEventListener("click", consultarProveedor);
        document.getElementById("sugerido_limpiar_ceros").addEventListener("click", function () {
            ocultarCeros = !ocultarCeros;
            this.textContent = ocultarCeros ? "Mostrar todos" : "Ocultar ceros";
            render();
        });
        document.getElementById("sugerido_items").addEventListener("input", function (e) {
            if (modoLectura) { return; }
            var existencia = e.target.getAttribute("data-sugerido-existencia");
            var cantidad = e.target.getAttribute("data-sugerido-cantidad");
            var obs = e.target.getAttribute("data-sugerido-obs");
            var minimo = e.target.getAttribute("data-sugerido-minimo");
            var maximo = e.target.getAttribute("data-sugerido-maximo");
            var reorden = e.target.getAttribute("data-sugerido-reorden");
            if (minimo !== null) {
                items[Number(minimo)].stock_minimo = Number(e.target.value || 0);
                items[Number(minimo)].cantidad_sugerida = calcularCantidadSugerida(items[Number(minimo)]);
                actualizarSugeridoVisual(Number(minimo));
            } else if (maximo !== null) {
                items[Number(maximo)].stock_maximo = e.target.value === "" ? null : Number(e.target.value || 0);
                items[Number(maximo)].cantidad_sugerida = calcularCantidadSugerida(items[Number(maximo)]);
                actualizarSugeridoVisual(Number(maximo));
            } else if (reorden !== null) {
                items[Number(reorden)].punto_reorden = Number(e.target.value || 0);
                items[Number(reorden)].cantidad_sugerida = calcularCantidadSugerida(items[Number(reorden)]);
                actualizarSugeridoVisual(Number(reorden));
            } else if (existencia !== null) {
                items[Number(existencia)].existencia_revisada = Number(e.target.value || 0);
                items[Number(existencia)].cantidad_sugerida = calcularCantidadSugerida(items[Number(existencia)]);
                actualizarSugeridoVisual(Number(existencia));
            } else if (cantidad !== null) {
                items[Number(cantidad)].cantidad_solicitar = Number(e.target.value || 0);
                actualizarResumen();
            } else if (obs !== null) {
                items[Number(obs)].observaciones = e.target.value;
            }
        });
        document.getElementById("sugerido_guardar_borrador").addEventListener("click", function () { guardar("borrador"); });
        document.getElementById("sugerido_marcar_lista").addEventListener("click", function () { guardar("lista"); });
        document.getElementById("sugerido_generar_solicitud").addEventListener("click", generarSolicitud);
        render();
    });
})();


