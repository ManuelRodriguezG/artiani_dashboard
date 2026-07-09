"use strict";

(function () {
    var incidencias = [];
    var modal;
    var paginaActual = 1;
    var tamanoPagina = 25;
    var productoErpDestino = null;
    var timerBusquedaErp = null;
    var permisos = window.CATALOGO_PERMISOS || {};

    function escapeHtml(value) {
        var div = document.createElement("div");
        div.textContent = value == null ? "" : String(value);
        return div.innerHTML;
    }

    function escapeAttr(value) {
        return escapeHtml(value).replace(/"/g, "&quot;").replace(/'/g, "&#39;");
    }

    function etiquetaMotivo(motivo) {
        return {
            sku_invalido: "SKU invalido",
            sku_duplicado: "SKU duplicado",
            grupo_variante_ambiguo: "Grupo ambiguo",
            nombre_codificacion_dudosa: "Nombre por revisar",
            sku_existente_erp: "SKU existente en ERP",
            sku_duplicado_productivo: "SKU duplicado productivo",
            migracion_parcial_previa: "Migracion parcial"
        }[motivo] || motivo;
    }

    function detalleLegible(valor) {
        try {
            var detalle = JSON.parse(valor || "{}");
            if (detalle.motivos_grupo) {
                return detalle.motivos_grupo.map(etiquetaMotivo).join(", ");
            }
            if (detalle.repeticiones) {
                return detalle.repeticiones + " productos usan este SKU";
            }
            if (detalle.resolucion && detalle.resolucion.accion === "descartada") {
                return "Descartada" + (detalle.resolucion.motivo ? ": " + detalle.resolucion.motivo : "");
            }
            return detalle.sku_original ? "Valor original: " + detalle.sku_original : "";
        } catch (error) {
            return "";
        }
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
        var texto = document.getElementById("migracion_buscar").value.trim().toLowerCase();
        var motivo = document.getElementById("migracion_motivo").value;
        var estatus = document.getElementById("migracion_estatus").value;
        var visibles = incidencias.filter(function (item) {
            var coincide = [item.id_producto_ecom, item.id_variante_ecom, item.sku, item.nombre_producto, item.motivo]
                .join(" ").toLowerCase().indexOf(texto) !== -1;
            return coincide && (!motivo || item.motivo === motivo) && (!estatus || item.estatus === estatus);
        });
        var totalPaginas = Math.max(1, Math.ceil(visibles.length / tamanoPagina));
        if (paginaActual > totalPaginas) {
            paginaActual = totalPaginas;
        }
        var inicio = (paginaActual - 1) * tamanoPagina;
        var pagina = visibles.slice(inicio, inicio + tamanoPagina);
        document.getElementById("migracion_incidencias").innerHTML = pagina.map(function (item) {
            var acciones = item.estatus === "pendiente" && permisos.editar
                ? "<button class=\"btn btn-sm btn-icon btn-light-primary\" title=\"Resolver incidencia\" data-resolver=\"" + escapeHtml(item.id_incidencia) + "\"><i class=\"bi bi-arrow-right-circle\"></i></button> " +
                  "<button class=\"btn btn-sm btn-icon btn-light-danger\" title=\"Descartar incidencia\" data-descartar=\"" + escapeHtml(item.id_incidencia) + "\"><i class=\"bi bi-x-lg\"></i></button>"
                : "<span class=\"badge badge-light-" + (item.estatus === "resuelta" ? "success" : "secondary") + "\">" + escapeHtml(item.estatus) + "</span>";
            return "<tr><td class=\"fw-bold\">" + escapeHtml(item.id_producto_ecom) + "</td>" +
                "<td>" + escapeHtml(item.id_variante_ecom || "-") + "</td>" +
                "<td>" + escapeHtml(item.sku || "-") + "</td>" +
                "<td>" + escapeHtml(item.nombre_producto) + "</td>" +
                "<td><span class=\"badge badge-light-warning\">" + escapeHtml(etiquetaMotivo(item.motivo)) + "</span></td>" +
                "<td class=\"text-muted fs-7\">" + escapeHtml(detalleLegible(item.detalle_json)) + "</td>" +
                "<td class=\"text-end\">" + acciones + "</td></tr>";
        }).join("") || "<tr><td colspan=\"7\" class=\"text-center text-muted py-10\">No hay incidencias con estos filtros</td></tr>";
        actualizarPaginacion(visibles.length, totalPaginas, inicio, pagina.length);
    }

    function actualizarPaginacion(total, totalPaginas, inicio, cantidadPagina) {
        var info = document.getElementById("migracion_paginacion_info");
        var anterior = document.getElementById("migracion_pagina_anterior");
        var siguiente = document.getElementById("migracion_pagina_siguiente");
        if (info) {
            info.textContent = total ? (inicio + 1) + "-" + (inicio + cantidadPagina) + " de " + total + " | Pagina " + paginaActual + " de " + totalPaginas : "Sin resultados";
        }
        if (anterior) {
            anterior.disabled = paginaActual <= 1;
        }
        if (siguiente) {
            siguiente.disabled = paginaActual >= totalPaginas;
        }
    }

    function abrirResolver(idIncidencia) {
        var form = document.getElementById("migracion_resolver_form");
        form.reset();
        form.setAttribute("data-motivo", "");
        document.getElementById("migracion_resolver_modo").disabled = false;
        form.querySelector("[type='submit']").disabled = false;
        productoErpDestino = null;
        form.querySelector("[name='id_incidencia']").value = idIncidencia;
        form.querySelector("[name='id_producto_erp_existente']").value = "";
        document.getElementById("migracion_producto_erp_seleccion").innerHTML = "";
        document.getElementById("migracion_producto_erp_resultados").innerHTML = "";
        document.getElementById("migracion_buscar_producto_erp").value = "";
        aplicarModoResolver();
        document.getElementById("migracion_resolver_error").classList.add("d-none");
        request("/catalogoerp/incidencia_migracion_detalle?id_incidencia=" + encodeURIComponent(idIncidencia)).then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje);
            }
            var incidencia = response.depurar.incidencia;
            var productos = response.depurar.productos || [];
            var resolucionPermitida = response.depurar.resolucion_permitida !== false;
            form.setAttribute("data-motivo", incidencia.motivo || "");
            if (incidencia.motivo === "nombre_codificacion_dudosa") {
                document.getElementById("migracion_resolver_modo").value = "crear";
                document.getElementById("migracion_resolver_modo").disabled = true;
            }
            form.querySelector("[name='nombre_maestro']").value = productos.length ? productos[0].nombre : "";
            document.getElementById("migracion_resolver_contexto").textContent = !resolucionPermitida
                ? "Esta incidencia usa productos historicos de ecommerce. No se puede resolver creando o vinculando como migracion normal; descartala, conservala como historial o reabre contra una fuente vigente."
                : incidencia.motivo === "nombre_codificacion_dudosa"
                ? "Este producto ya existe en ERP como borrador. Corrige el nombre y activalo cuando este listo."
                : productos.length > 1
                ? "Selecciona los productos que realmente pertenecen al mismo maestro. Cada variante incluida necesita un SKU ERP unico."
                : "Se creara un producto independiente con un SKU ERP valido.";
            document.getElementById("migracion_resolver_productos").innerHTML = productos.map(function (producto) {
                var accion = incidencia.motivo === "nombre_codificacion_dudosa"
                    ? "<input class=\"form-control\" required maxlength=\"255\" name=\"nombres[" + escapeHtml(producto.id_producto) + "]\" value=\"" + escapeHtml(producto.nombre_sugerido || producto.nombre) + "\">"
                    : "<div class=\"d-flex flex-column gap-2\"><select class=\"form-select form-select-sm d-none\" name=\"skus_existentes[" + escapeHtml(producto.id_producto) + "]\" data-sku-existente-producto=\"" + escapeHtml(producto.id_producto) + "\"><option value=\"0\">Crear SKU nuevo</option></select>" +
                    "<input class=\"form-control\" required maxlength=\"150\" name=\"skus[" + escapeHtml(producto.id_producto) + "]\" data-sku-producto=\"" + escapeHtml(producto.id_producto) + "\" value=\"" + escapeHtml(producto.sku_sugerido) + "\"></div>";
                var imagen = producto.url_imagen ? "<img src=\"/" + escapeHtml(producto.url_imagen) + "\" class=\"rounded me-3\" style=\"width:48px;height:48px;object-fit:cover\" alt=\"\">" : "";
                var erp = producto.erp_vinculado ? "<div class=\"text-muted fs-8\">ERP: " + escapeHtml(producto.erp_vinculado.codigo_producto + " / " + producto.erp_vinculado.sku_erp) + "</div>" : "";
                var fuente = producto.fuente_ecommerce && producto.fuente_ecommerce !== "local" ? "<div class=\"text-warning fs-8\">Fuente historica</div>" : "";
                return "<tr><td><input class=\"form-check-input\" type=\"checkbox\" checked name=\"productos_incluidos[]\" value=\"" + escapeHtml(producto.id_producto) + "\" data-incluir-producto=\"" + escapeHtml(producto.id_producto) +
                    "\"></td><td><div class=\"d-flex align-items-center\">" + imagen + "<div><div class=\"fw-bold\">" + escapeHtml(producto.nombre) +
                    "</div><span class=\"text-muted fs-7\">ID ecommerce " + escapeHtml(producto.id_producto) + "</span>" + erp +
                    fuente + "</div></div></td><td class=\"text-muted fs-7\">" + escapeHtml(producto.atributos || "Sin atributos") +
                    "</td><td>" + escapeHtml(producto.sku || "-") + "</td><td>" + accion + "</td></tr>";
            }).join("");
            aplicarModoResolver();
            llenarSelectsSkuExistente();
            if (!resolucionPermitida) {
                document.querySelectorAll("#migracion_resolver_productos input, #migracion_resolver_productos select").forEach(function (campo) {
                    campo.disabled = true;
                });
                form.querySelector("[type='submit']").disabled = true;
            }
            modal.show();
        }).catch(function (error) {
            Swal.fire({text: error.message, icon: "error", confirmButtonText: "Aceptar"});
        });
    }

    function resolver(event) {
        event.preventDefault();
        if (!permisos.editar) {
            return;
        }
        var form = event.currentTarget;
        var button = form.querySelector("[type='submit']");
        var data = {};
        new FormData(form).forEach(function (value, key) { data[key] = value; });
        var url = "/catalogoerp/incidencia_migracion_resolver";
        var modo = document.getElementById("migracion_resolver_modo").value;
        if ((form.getAttribute("data-motivo") || "") === "nombre_codificacion_dudosa") {
            var campoNombre = Object.keys(data).filter(function (key) { return key.indexOf("nombres[") === 0; })[0];
            if (campoNombre) {
                data.id_producto_ecom = campoNombre.replace("nombres[", "").replace("]", "");
                data.nombre_corregido = data[campoNombre];
            }
            url = "/catalogoerp/incidencia_nombre_resolver";
        } else if (modo === "existente") {
            url = "/catalogoerp/incidencia_migracion_vincular_existente";
        }
        button.disabled = true;
        request(url, data).then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje);
            }
            modal.hide();
            Swal.fire({text: response.mensaje, icon: "success", confirmButtonText: "Aceptar"}).then(function () {
                window.location.reload();
            });
        }).catch(function (error) {
            var box = document.getElementById("migracion_resolver_error");
            box.textContent = error.message;
            box.classList.remove("d-none");
        }).finally(function () {
            button.disabled = false;
        });
    }

    function aplicarModoResolver() {
        var modo = document.getElementById("migracion_resolver_modo").value;
        var esExistente = modo === "existente";
        var panel = document.getElementById("migracion_erp_existente_panel");
        var form = document.getElementById("migracion_resolver_form");
        panel.classList.toggle("d-none", !esExistente);
        form.querySelector("[name='nombre_maestro']").required = !esExistente;
        form.querySelector("[name='nombre_maestro']").closest(".row").classList.toggle("d-none", esExistente);
        form.querySelector("[type='submit']").innerHTML = esExistente ? "<i class=\"bi bi-link-45deg\"></i> Vincular a ERP existente" : "<i class=\"bi bi-check-lg\"></i> Crear producto ERP";
        document.querySelectorAll("[data-sku-existente-producto]").forEach(function (select) {
            select.classList.toggle("d-none", !esExistente);
        });
    }

    function buscarProductosErp(texto) {
        clearTimeout(timerBusquedaErp);
        var resultados = document.getElementById("migracion_producto_erp_resultados");
        if (texto.trim().length < 2) {
            resultados.innerHTML = "";
            return;
        }
        timerBusquedaErp = setTimeout(function () {
            request("/catalogoerp/fusion_buscar_productos?q=" + encodeURIComponent(texto.trim())).then(function (response) {
                if (response.error) {
                    throw new Error(response.mensaje);
                }
                resultados.innerHTML = (response.depurar || []).map(function (producto) {
                    return "<button type=\"button\" class=\"btn btn-light w-100 text-start mb-2\" data-seleccionar-erp=\"" + escapeAttr(JSON.stringify(producto)) + "\">" +
                        "<div class=\"fw-bold\">" + escapeHtml(producto.codigo_producto + " - " + producto.nombre) + "</div><div class=\"text-muted fs-7\">" +
                        escapeHtml(producto.skus || "Sin SKU") + "</div></button>";
                }).join("") || "<div class=\"alert alert-light-warning mb-0\">Sin coincidencias</div>";
            }).catch(function (error) {
                resultados.innerHTML = "<div class=\"alert alert-light-danger mb-0\">" + escapeHtml(error.message) + "</div>";
            });
        }, 250);
    }

    function seleccionarProductoErp(producto) {
        var form = document.getElementById("migracion_resolver_form");
        form.querySelector("[name='id_producto_erp_existente']").value = producto.id_producto_erp;
        document.getElementById("migracion_producto_erp_seleccion").innerHTML =
            "<div class=\"alert alert-light-primary mb-0\"><div class=\"fw-bold\">" + escapeHtml(producto.codigo_producto + " - " + producto.nombre) +
            "</div><div class=\"text-muted fs-7\">ID " + escapeHtml(producto.id_producto_erp) + " | " + escapeHtml(producto.total_skus) + " SKU | " + escapeHtml(producto.total_imagenes) + " imagenes</div></div>";
        document.getElementById("migracion_producto_erp_resultados").innerHTML = "";
        document.getElementById("migracion_buscar_producto_erp").value = "";
        request("/catalogoerp/consultar?id_producto_erp=" + encodeURIComponent(producto.id_producto_erp)).then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje);
            }
            productoErpDestino = response.depurar;
            llenarSelectsSkuExistente();
        }).catch(function (error) {
            mostrarErrorResolver(error);
        });
    }

    function llenarSelectsSkuExistente() {
        var skus = productoErpDestino && productoErpDestino.skus ? productoErpDestino.skus : [];
        document.querySelectorAll("[data-sku-existente-producto]").forEach(function (select) {
            var actual = select.value;
            select.innerHTML = "<option value=\"0\">Crear SKU nuevo</option>" + skus.map(function (sku) {
                return "<option value=\"" + escapeHtml(sku.id_sku) + "\">" + escapeHtml(sku.sku + " - " + sku.nombre) + "</option>";
            }).join("");
            select.value = actual || "0";
        });
    }

    function mostrarErrorResolver(error) {
        var box = document.getElementById("migracion_resolver_error");
        box.textContent = error.message || String(error);
        box.classList.remove("d-none");
    }

    function descartar(idIncidencia) {
        if (!permisos.editar) {
            return;
        }
        Swal.fire({
            title: "Descartar incidencia",
            input: "text",
            inputPlaceholder: "Motivo opcional",
            showCancelButton: true,
            confirmButtonText: "Descartar",
            cancelButtonText: "Cancelar",
            icon: "warning"
        }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }
            request("/catalogoerp/incidencia_migracion_descartar", {
                id_incidencia: idIncidencia,
                motivo_descarte: result.value || ""
            }).then(function (response) {
                if (response.error) {
                    throw new Error(response.mensaje);
                }
                return cargar();
            }).catch(function (error) {
                Swal.fire({text: error.message, icon: "error", confirmButtonText: "Aceptar"});
            });
        });
    }

    function cargar() {
        fetch("/catalogoerp/incidencias_migracion_ecommerce", {credentials: "same-origin"})
            .then(function (response) { return response.json(); })
            .then(function (response) {
                if (response.error) {
                    throw new Error(response.mensaje);
                }
                incidencias = response.depurar.incidencias || [];
                var resumen = response.depurar.resumen || {};
                document.getElementById("migracion_pendientes").textContent = resumen.pendientes || 0;
                document.getElementById("migracion_invalidos").textContent = resumen.sku_invalidos || 0;
                document.getElementById("migracion_duplicados").textContent = resumen.sku_duplicados || 0;
                document.getElementById("migracion_ambiguos").textContent = resumen.grupos_ambiguos || 0;
                render();
            });
    }

    document.addEventListener("DOMContentLoaded", function () {
        modal = new bootstrap.Modal(document.getElementById("migracion_resolver_modal"));
        cargar();
        document.getElementById("migracion_buscar").addEventListener("input", function () {
            paginaActual = 1;
            render();
        });
        document.getElementById("migracion_motivo").addEventListener("change", function () {
            paginaActual = 1;
            render();
        });
        document.getElementById("migracion_estatus").addEventListener("change", function () {
            paginaActual = 1;
            render();
        });
        document.getElementById("migracion_tamano_pagina").addEventListener("change", function () {
            tamanoPagina = Number(this.value) || 25;
            paginaActual = 1;
            render();
        });
        document.getElementById("migracion_pagina_anterior").addEventListener("click", function () {
            paginaActual = Math.max(1, paginaActual - 1);
            render();
        });
        document.getElementById("migracion_pagina_siguiente").addEventListener("click", function () {
            paginaActual += 1;
            render();
        });
        document.getElementById("migracion_resolver_modo").addEventListener("change", aplicarModoResolver);
        document.getElementById("migracion_buscar_producto_erp").addEventListener("input", function () {
            buscarProductosErp(this.value);
        });
        document.getElementById("migracion_producto_erp_resultados").addEventListener("click", function (event) {
            var button = event.target.closest("[data-seleccionar-erp]");
            if (button) {
                seleccionarProductoErp(JSON.parse(button.getAttribute("data-seleccionar-erp")));
            }
        });
        document.getElementById("migracion_incidencias").addEventListener("click", function (event) {
            var resolverButton = event.target.closest("[data-resolver]");
            var descartarButton = event.target.closest("[data-descartar]");
            if (resolverButton) {
                abrirResolver(resolverButton.getAttribute("data-resolver"));
            } else if (descartarButton) {
                descartar(descartarButton.getAttribute("data-descartar"));
            }
        });
        document.getElementById("migracion_resolver_form").addEventListener("submit", resolver);
        document.getElementById("migracion_resolver_productos").addEventListener("change", function (event) {
            if (event.target.matches("[data-incluir-producto]")) {
                var id = event.target.getAttribute("data-incluir-producto");
                var input = document.querySelector("[data-sku-producto='" + id + "']");
                var select = document.querySelector("[data-sku-existente-producto='" + id + "']");
                if (input) {
                    input.disabled = !event.target.checked || (select && Number(select.value) > 0);
                    input.required = event.target.checked && !(select && Number(select.value) > 0);
                }
                if (select) {
                    select.disabled = !event.target.checked;
                }
            } else if (event.target.matches("[data-sku-existente-producto]")) {
                var idSkuProducto = event.target.getAttribute("data-sku-existente-producto");
                var skuInput = document.querySelector("[data-sku-producto='" + idSkuProducto + "']");
                if (skuInput) {
                    skuInput.disabled = Number(event.target.value) > 0;
                    skuInput.required = Number(event.target.value) <= 0;
                }
            }
        });
    });
})();
