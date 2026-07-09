"use strict";

(function () {
    var productos = [];
    var productoActualId = 0;
    var tbody;
    var buscar;
    var filtroEstatus;
    var filtroSaneamiento;
    var formAlta;
    var catalogosDisponibles = {};
    var detalleActual = {};
    var incidenciasCalidad = [];
    var paginaActual = 1;
    var tamanoPagina = 25;
    var productoInicialAbierto = false;
    var filtroObjetivoSku = "todos";
    var mostrarSkusArchivados = false;
    var permisos = window.CATALOGO_PERMISOS || {};
    var productosPaginaActual = [];
    var productosSeleccionados = {};
    var componentesPaqueteForm = [];
    var paqueteGrupoActualId = 0;

    function request(url, data) {
        return fetch(url, {
            method: data ? "POST" : "GET",
            headers: data ? {"Content-Type": "application/x-www-form-urlencoded; charset=UTF-8"} : {},
            body: data ? new URLSearchParams(data).toString() : null,
            credentials: "same-origin"
        }).then(function (response) {
            return response.json();
        });
    }

    function cargar() {
        Promise.all([request("/catalogoerp/listar"), request("/catalogoerp/catalogos"), request("/catalogoerp/incidencias_calidad?estatus=abiertas&limite=50")]).then(function (responses) {
            productos = responses[0].depurar || [];
            incidenciasCalidad = (responses[2].depurar && responses[2].depurar.incidencias) || [];
            renderResumenSaneamiento();
            render();
            llenarCatalogos(responses[1].depurar || {});
            activarBuscadoresSelects(document);
            renderIncidenciasCalidad();
            var params = new URLSearchParams(window.location.search);
            if (!productoInicialAbierto && params.get("id_producto_erp")) {
                productoInicialAbierto = true;
                abrirDetalle(params.get("id_producto_erp"));
            }
        });
    }

    function llenarCatalogos(catalogos) {
        catalogosDisponibles = catalogos;
        llenarSelect("catalogo_unidad", catalogos.unidades || [], "id_unidad", etiquetaUnidad, false);
        llenarSelect("catalogo_sku_unidad", catalogos.unidades || [], "id_unidad", etiquetaUnidad, false);
        llenarSelect("catalogo_proveedor_unidad", catalogos.unidades || [], "id_unidad", etiquetaUnidad, false);
        llenarSelect("catalogo_temporal_unidad", catalogos.unidades || [], "id_unidad", etiquetaUnidad, false);
        llenarSelect("catalogo_proveedor_id", catalogos.proveedores || [], "id_proveedor", etiquetaProveedor, false);
        llenarSelect("catalogo_masivo_proveedor", catalogos.proveedores || [], "id_proveedor", etiquetaProveedor, true);
        llenarSelect("catalogo_masivo_unidad_compra", catalogos.unidades || [], "id_unidad", etiquetaUnidad, true);
        llenarSelect("catalogo_variante_atributo", catalogos.atributos || [], "id_atributo_erp", etiquetaAtributo, true);
        llenarSelect("catalogo_marca", catalogos.marcas || [], "id_marca_erp", etiquetaNombre, true);
        llenarSelect("catalogo_editar_marca", catalogos.marcas || [], "id_marca_erp", etiquetaNombre, true);
        llenarSelect("catalogo_temporal_marca", catalogos.marcas || [], "id_marca_erp", etiquetaNombre, true);
        llenarSelect("catalogo_masivo_marca", catalogos.marcas || [], "id_marca_erp", etiquetaNombre, true);
        llenarSelect("catalogo_categoria", catalogos.categorias || [], "id_categoria_erp", etiquetaCategoria, true);
        llenarSelect("catalogo_editar_categoria", catalogos.categorias || [], "id_categoria_erp", etiquetaCategoria, true);
        llenarSelect("catalogo_temporal_categoria", catalogos.categorias || [], "id_categoria_erp", etiquetaCategoria, true);
        llenarSelect("catalogo_masivo_categoria", catalogos.categorias || [], "id_categoria_erp", etiquetaCategoria, true);
        llenarSelect("catalogo_paquete_sku_unidad_inline", catalogos.unidades || [], "id_unidad", etiquetaUnidad, false);
        seleccionarUnidadPaqueteDefault();
    }

    function etiquetaUnidad(item) { return item.nombre + " (" + item.abreviatura + ")"; }
    function etiquetaNombre(item) { return item.nombre; }
    function etiquetaCategoria(item) { return item.ruta || item.nombre; }
    function etiquetaProveedor(item) { return item.proveedor; }
    function etiquetaSku(item) { return item.sku + " - " + item.nombre; }
    function etiquetaAtributo(item) { return item.nombre + (String(item.es_variante) === "1" ? " (variante)" : ""); }

    function llenarSelect(id, items, valueKey, label, conservarPrimero) {
        var select = document.getElementById(id);
        if (!select) {
            return;
        }
        var primero = conservarPrimero && select.options.length ? select.options[0].outerHTML : "";
        select.innerHTML = primero + items.map(function (item) {
            return "<option value=\"" + escapeHtml(item[valueKey]) + "\">" + escapeHtml(label(item)) + "</option>";
        }).join("");
        activarBuscadorSelect(select);
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-30
     * Proposito: activa busqueda en selects largos de Catalogo cuando Select2 esta disponible.
     * Impacto: UX de Catalogo ERP; acelera captura de marcas, categorias, unidades, SKUs, proveedores y atributos.
     * Contrato: es mejora progresiva; si jQuery/Select2 no existe, el select nativo sigue funcionando.
     */
    function activarBuscadorSelect(select) {
        if (!select || !window.jQuery || !jQuery.fn || !jQuery.fn.select2) {
            return;
        }
        var $select = jQuery(select);
        var modal = select.closest(".modal");
        if ($select.data("select2")) {
            $select.select2("destroy");
        }
        $select.select2({
            width: "100%",
            dropdownParent: modal ? jQuery(modal) : jQuery(document.body),
            placeholder: select.getAttribute("data-placeholder") || "Buscar y seleccionar",
            allowClear: !!select.querySelector("option[value='']"),
            minimumResultsForSearch: 0
        });
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-30
     * Proposito: aplica buscador a selects estaticos que no pasan por llenarSelect.
     * Impacto: UX de Catalogo ERP; mejora selects de estado/tipo/modo sin cambiar contratos de datos.
     */
    function activarBuscadoresSelects(root) {
        if (!window.jQuery || !jQuery.fn || !jQuery.fn.select2) {
            return;
        }
        (root || document).querySelectorAll("select.form-select").forEach(function (select) {
            activarBuscadorSelect(select);
        });
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-24
     * Proposito: lista productos respetando busqueda y filtro de vigentes/archivados sin borrar informacion.
     * Impacto: Catalogo ERP; los SKUs descontinuados dejan de hacer ruido en la vista normal.
     */
    function render() {
        var filtro = buscar.value.trim().toLowerCase();
        var modoEstatus = filtroEstatus ? filtroEstatus.value : "vigentes";
        var modoSaneamiento = filtroSaneamiento ? filtroSaneamiento.value : "todos";
        var visibles = productos.filter(function (producto) {
            var archivado = esEstatusArchivado(producto.estatus);
            var cumpleEstatus = modoEstatus === "todos" || (modoEstatus === "archivados" ? archivado : !archivado);
            var cumpleSaneamiento = cumpleFiltroSaneamiento(producto, modoSaneamiento);
            var skusTexto = modoEstatus === "archivados" ? producto.skus_archivados : (modoEstatus === "todos" ? producto.skus : producto.skus_vigentes);
            return cumpleEstatus && cumpleSaneamiento && [producto.codigo_producto, producto.nombre, producto.marca, producto.categoria, producto.tipo_producto, skusTexto]
                .join(" ").toLowerCase().indexOf(filtro) !== -1;
        });
        var totalPaginas = Math.max(1, Math.ceil(visibles.length / tamanoPagina));
        if (paginaActual > totalPaginas) {
            paginaActual = totalPaginas;
        }
        var inicio = (paginaActual - 1) * tamanoPagina;
        var pagina = visibles.slice(inicio, inicio + tamanoPagina);
        productosPaginaActual = pagina.map(function (producto) { return String(producto.id_producto_erp); });
        tbody.innerHTML = pagina.map(function (producto) {
            var skusTexto = modoEstatus === "archivados" ? producto.skus_archivados : (modoEstatus === "todos" ? producto.skus : producto.skus_vigentes);
            var totalSkus = modoEstatus === "archivados" ? producto.total_skus_archivados : (modoEstatus === "todos" ? producto.total_skus : producto.total_skus_vigentes);
            var notaArchivados = Number(producto.total_skus_archivados || 0) > 0 && modoEstatus === "vigentes"
                ? "<div class=\"text-muted fs-8\">" + escapeHtml(producto.total_skus_archivados) + " SKU archivado(s)</div>"
                : "";
            var alertasSaneamiento = renderAlertasSaneamientoProducto(producto);
            var tabSaneamiento = tabDetallePorFiltroSaneamiento(modoSaneamiento);
            var seleccion = permisos.editar ? "<td><input class=\"form-check-input\" type=\"checkbox\" data-seleccionar-producto=\"" + escapeAttr(producto.id_producto_erp) + "\"" + (productosSeleccionados[String(producto.id_producto_erp)] ? " checked" : "") + "></td>" : "";
            return "<tr>" + seleccion + "<td class=\"fw-bold text-nowrap\">" + escapeHtml(producto.codigo_producto) + "</td>" +
                "<td>" + imagenPortadaProductoHtml(producto) + "</td>" +
                "<td><div class=\"fw-bold\">" + escapeHtml(producto.nombre) + "</div><span class=\"text-muted fs-7\">" + escapeHtml(totalSkus || 0) + " SKU</span>" + notaArchivados + alertasSaneamiento + "</td>" +
                "<td>" + escapeHtml(producto.marca || "Sin marca") + "</td>" +
                "<td><span class=\"badge badge-light-primary\">" + escapeHtml(producto.tipo_producto) + "</span></td>" +
                "<td>" + escapeHtml(skusTexto || (modoEstatus === "vigentes" ? "Sin SKU vigente" : "Sin SKU")) + "</td>" +
                "<td><span class=\"badge badge-light-" + claseEstatusMaestro(producto.estatus) + "\">" + escapeHtml(producto.estatus) + "</span></td>" +
                "<td class=\"text-end\"><button class=\"btn btn-sm btn-icon btn-light-primary\" title=\"" + (permisos.editar ? "Administrar producto" : "Ver producto") + "\" data-producto=\"" + producto.id_producto_erp + "\" data-detalle-tab=\"" + escapeAttr(tabSaneamiento) + "\"><i class=\"bi " + (permisos.editar ? "bi-pencil-square" : "bi-eye") + "\"></i></button></td></tr>";
        }).join("") || "<tr><td colspan=\"" + (permisos.editar ? "9" : "8") + "\" class=\"text-center text-muted py-10\">Aún no hay productos en el catálogo ERP</td></tr>";
        document.getElementById("catalogo_total").textContent = visibles.length + " productos";
        actualizarPaginacion(visibles.length, totalPaginas, inicio, pagina.length);
        actualizarEstadoSeleccionMasiva();
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-26
     * Proposito: mostrar la portada del producto en el listado principal y evidenciar cuando falta imagen.
     * Impacto: Catalogo ERP; apoyo visual de saneamiento sin duplicar imagenes ni tocar otros modulos.
     */
    function imagenPortadaProductoHtml(producto) {
        var src = normalizarImagenUrl(producto && producto.url_imagen);
        if (!src) {
            return "<div class=\"rounded border bg-light d-flex align-items-center justify-content-center\" title=\"Sin imagen de portada\" style=\"width:52px;height:52px;\">" +
                "<i class=\"bi bi-image text-muted fs-2\"></i></div>";
        }
        return "<div class=\"rounded border bg-light\" title=\"" + escapeAttr(producto.nombre || "Producto") + "\" style=\"width:52px;height:52px;background-image:url('" +
            escapeAttr(src) + "');background-size:cover;background-position:center;\"></div>";
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-25
     * Proposito: muestra accesos rapidos a pendientes de saneamiento del catalogo migrado.
     * Impacto: Catalogo ERP; acelera priorizacion sin modificar datos ni tocar otros modulos.
     */
    function renderResumenSaneamiento() {
        var contenedor = document.getElementById("catalogo_resumen_saneamiento");
        if (!contenedor) {
            return;
        }
        var items = [
            {modo: "sin_marca", texto: "Sin marca", color: "warning"},
            {modo: "sin_categoria", texto: "Sin categoria", color: "warning"},
            {modo: "sin_imagen", texto: "Sin imagen", color: "warning"},
            {modo: "sin_proveedor", texto: "Sin proveedor", color: "danger"},
            {modo: "proveedor_sin_principal", texto: "Sin principal", color: "warning"},
            {modo: "multiples_proveedores", texto: "Varios proveedores", color: "primary"}
        ];
        contenedor.innerHTML = items.map(function (item) {
            var total = productos.filter(function (producto) {
                return cumpleFiltroSaneamiento(producto, item.modo);
            }).length;
            return "<button type=\"button\" class=\"btn btn-sm btn-light-" + item.color + "\" data-filtro-saneamiento=\"" + escapeAttr(item.modo) + "\">" +
                "<span class=\"fw-bold\">" + escapeHtml(total) + "</span> " + escapeHtml(item.texto) +
                "</button>";
        }).join("");
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-25
     * Proposito: filtra productos por pendientes de saneamiento para acelerar limpieza post-migracion.
     * Impacto: Catalogo ERP; prioriza marca, categoria y proveedor sin tocar reglas de otros modulos.
     */
    function cumpleFiltroSaneamiento(producto, modo) {
        if (modo === "sin_marca") {
            return !producto.marca;
        }
        if (modo === "sin_categoria") {
            return !producto.id_categoria_erp;
        }
        if (modo === "sin_imagen") {
            return !producto.url_imagen;
        }
        if (modo === "sin_proveedor") {
            return Number(producto.skus_sin_proveedor_activo || 0) > 0;
        }
        if (modo === "proveedor_sin_principal") {
            return Number(producto.skus_proveedor_sin_preferido || 0) > 0;
        }
        if (modo === "multiples_proveedores") {
            return Number(producto.skus_multiples_proveedores || 0) > 0;
        }
        return true;
    }

    function renderAlertasSaneamientoProducto(producto) {
        var alertas = [];
        if (!producto.marca) {
            alertas.push({texto: "Sin marca", color: "warning"});
        }
        if (!producto.id_categoria_erp) {
            alertas.push({texto: "Sin categoria", color: "warning"});
        }
        if (!producto.url_imagen) {
            alertas.push({texto: "Sin imagen", color: "warning"});
        }
        if (Number(producto.skus_sin_proveedor_activo || 0) > 0) {
            alertas.push({texto: producto.skus_sin_proveedor_activo + " SKU sin proveedor", color: "danger"});
        }
        if (Number(producto.skus_proveedor_sin_preferido || 0) > 0) {
            alertas.push({texto: producto.skus_proveedor_sin_preferido + " sin principal", color: "warning"});
        }
        if (Number(producto.skus_multiples_proveedores || 0) > 0) {
            alertas.push({texto: producto.skus_multiples_proveedores + " con varios proveedores", color: "primary"});
        }
        if (!alertas.length) {
            return "";
        }
        return "<div class=\"d-flex flex-wrap gap-1 mt-2\">" + alertas.map(function (alerta) {
            return "<span class=\"badge badge-light-" + alerta.color + "\">" + escapeHtml(alerta.texto) + "</span>";
        }).join("") + "</div>";
    }

    function tabDetallePorFiltroSaneamiento(modo) {
        if (modo === "sin_proveedor" || modo === "proveedor_sin_principal" || modo === "multiples_proveedores") {
            return "catalogo_detalle_proveedores";
        }
        if (modo === "sin_imagen") {
            return "catalogo_detalle_imagenes";
        }
        return "catalogo_detalle_producto";
    }

    function productosSeleccionadosIds() {
        return Object.keys(productosSeleccionados).filter(function (id) {
            return productosSeleccionados[id];
        });
    }

    function actualizarEstadoSeleccionMasiva() {
        var info = document.getElementById("catalogo_masivo_info");
        var seleccionarPagina = document.getElementById("catalogo_seleccionar_pagina");
        var total = productosSeleccionadosIds().length;
        if (info) {
            info.textContent = total ? total + " producto(s) seleccionados para actualizacion masiva." : "Selecciona productos visibles para aplicar marca, categoria o proveedor por bloque.";
        }
        if (seleccionarPagina) {
            seleccionarPagina.checked = productosPaginaActual.length > 0 && productosPaginaActual.every(function (id) {
                return productosSeleccionados[id];
            });
            seleccionarPagina.indeterminate = !seleccionarPagina.checked && productosPaginaActual.some(function (id) {
                return productosSeleccionados[id];
            });
        }
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-25
     * Proposito: aplica marca y/o categoria a productos seleccionados desde la bandeja de saneamiento.
     * Impacto: Catalogo ERP; acelera limpieza de migracion usando el contrato auditado de revision de metadatos.
     */
    function aplicarMetadatosMasivos() {
        var errorBox = document.getElementById("catalogo_masivo_error");
        var marca = document.getElementById("catalogo_masivo_marca");
        var categoria = document.getElementById("catalogo_masivo_categoria");
        var proveedor = document.getElementById("catalogo_masivo_proveedor");
        var unidadCompra = document.getElementById("catalogo_masivo_unidad_compra");
        var factor = document.getElementById("catalogo_masivo_factor");
        var minima = document.getElementById("catalogo_masivo_minima");
        var ids = productosSeleccionadosIds();
        var idMarca = marca ? marca.value : "";
        var idCategoria = categoria ? categoria.value : "";
        var idProveedor = proveedor ? proveedor.value : "";
        var idUnidadCompra = unidadCompra ? unidadCompra.value : "";
        var factorCompra = factor ? parseFloat(factor.value || "0") : 0;
        var minimaCompra = minima ? parseFloat(minima.value || "0") : 0;
        var aplicarProveedor = !!idProveedor;
        if (!ids.length) {
            mostrarError(errorBox, new Error("Selecciona al menos un producto"));
            return;
        }
        if (!idMarca && !idCategoria && !aplicarProveedor) {
            mostrarError(errorBox, new Error("Selecciona marca, categoria o proveedor para aplicar"));
            return;
        }
        if (aplicarProveedor && (!idUnidadCompra || factorCompra <= 0 || minimaCompra <= 0)) {
            mostrarError(errorBox, new Error("Para proveedor masivo selecciona unidad, factor y compra minima mayores a cero"));
            return;
        }
        errorBox.classList.add("d-none");
        var preflight = aplicarProveedor
            ? request("/catalogoerp/proveedor_masivo_skus_sin_proveedor", {
                ids_productos: JSON.stringify(ids),
                id_proveedor: idProveedor,
                id_unidad_compra: idUnidadCompra,
                factor_conversion: factorCompra,
                cantidad_minima: minimaCompra,
                simular: 1
            })
            : Promise.resolve(null);
        preflight.then(function (simulacion) {
            if (simulacion && simulacion.error) {
                throw new Error(simulacion.mensaje);
            }
            var detalleProveedor = "";
            if (simulacion && simulacion.depurar) {
                detalleProveedor = " Se relacionaran " + Number(simulacion.depurar.skus_relacionables || 0) +
                    " SKU(s) sin proveedor de " + Number(simulacion.depurar.productos_afectados || 0) + " producto(s).";
            }
            return Swal.fire({
                text: "Se actualizaran " + ids.length + " producto(s) seleccionados." + detalleProveedor,
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Aplicar",
                cancelButtonText: "Cancelar"
            });
        }).then(function (result) {
            if (!result) {
                return;
            }
            if (!result.isConfirmed) {
                return;
            }
            var tareas = [];
            if (idMarca || idCategoria) {
                var asignaciones = ids.map(function (id) {
                    return {
                        id_producto_erp: id,
                        id_marca_erp: idMarca,
                        id_categoria_erp: idCategoria,
                        forzar_categoria_principal: 1
                    };
                });
                tareas.push(request("/catalogoerp/metadatos_revision_aplicar", {
                    asignaciones: JSON.stringify(asignaciones)
                }));
            }
            if (aplicarProveedor) {
                tareas.push(request("/catalogoerp/proveedor_masivo_skus_sin_proveedor", {
                    ids_productos: JSON.stringify(ids),
                    id_proveedor: idProveedor,
                    id_unidad_compra: idUnidadCompra,
                    factor_conversion: factorCompra,
                    cantidad_minima: minimaCompra
                }));
            }
            Promise.all(tareas).then(function (responses) {
                var errores = responses.filter(function (response) { return response.error; });
                if (errores.length) {
                    throw new Error(errores.map(function (response) { return response.mensaje; }).join(" | "));
                }
                Swal.fire({text: responses.map(function (response) { return response.mensaje; }).join(" | "), icon: "success", confirmButtonText: "Aceptar"});
                productosSeleccionados = {};
                if (marca) { marca.value = ""; }
                if (categoria) { categoria.value = ""; }
                if (proveedor) { proveedor.value = ""; }
                if (unidadCompra) { unidadCompra.value = ""; }
                if (factor) { factor.value = "1"; }
                if (minima) { minima.value = "1"; }
                cargar();
            }).catch(function (error) {
                mostrarError(errorBox, error);
            });
        }).catch(function (error) {
            mostrarError(errorBox, error);
        });
    }

    function renderIncidenciasCalidad() {
        var body = document.getElementById("catalogo_incidencias_body");
        var total = document.getElementById("catalogo_incidencias_total");
        if (!body) {
            return;
        }
        var abiertas = incidenciasCalidad || [];
        if (total) {
            total.textContent = abiertas.length + " incidencias";
        }
        body.innerHTML = abiertas.map(function (item) {
            var evidencia = item.evidencia_json || {};
            var renglon = evidencia.renglon || {};
            var referencia = [item.sku, item.nombre_sku, renglon.sku_proveedor, renglon.codigo_barras, renglon.descripcion_proveedor].filter(Boolean).join(" | ") || ("Incidencia " + item.id_incidencia_calidad);
            var puedeTemporal = permisos.editar &&
                item.origen === "proveedores" &&
                item.tipo_incidencia === "proveedor_sku_sin_match" &&
                !item.id_sku &&
                ["pendiente", "en_revision", "bloqueada"].indexOf(String(item.estatus || "")) >= 0;
            var acciones = puedeTemporal
                ? "<button class=\"btn btn-sm btn-light-primary\" type=\"button\" data-sku-temporal=\"" + escapeAttr(item.id_incidencia_calidad) + "\"><i class=\"bi bi-plus-lg\"></i> SKU temporal</button>"
                : "";
            if (item.id_sku) {
                acciones = "<button class=\"btn btn-sm btn-light\" type=\"button\" data-producto=\"" + escapeAttr(item.id_producto_erp || "") + "\"><i class=\"bi bi-eye\"></i> Ver SKU</button>";
            }
            return "<tr>" +
                "<td><div class=\"fw-bold\">" + escapeHtml(item.tipo_incidencia || "-") + "</div><span class=\"text-muted\">" + escapeHtml(item.titulo || "") + "</span></td>" +
                "<td><span class=\"badge badge-light-primary\">" + escapeHtml(item.origen || "-") + "</span></td>" +
                "<td>" + escapeHtml(referencia) + "</td>" +
                "<td><span class=\"badge badge-light-" + claseEstatusIncidencia(item.estatus) + "\">" + escapeHtml(item.estatus || "-") + "</span></td>" +
                "<td class=\"text-end\">" + acciones + "</td>" +
                "</tr>";
        }).join("") || "<tr><td colspan=\"5\" class=\"text-center text-muted py-7\">Sin incidencias abiertas</td></tr>";
    }

    function claseEstatusIncidencia(estatus) {
        if (estatus === "bloqueada") {
            return "danger";
        }
        if (estatus === "en_revision") {
            return "warning";
        }
        if (estatus === "resuelta") {
            return "success";
        }
        if (estatus === "descartada") {
            return "secondary";
        }
        return "info";
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-24
     * Proposito: pinta el estatus de vida del maestro sin interpretarlo como preparacion operativa.
     * Impacto: Catalogo ERP; activo, borrador, revision, inactivo y descontinuado se distinguen visualmente.
     */
    function claseEstatusMaestro(estatus) {
        if (estatus === "activo") {
            return "success";
        }
        if (estatus === "en_revision") {
            return "warning";
        }
        if (estatus === "borrador") {
            return "info";
        }
        if (estatus === "descontinuado" || estatus === "inactivo" || estatus === "fusionado") {
            return "secondary";
        }
        return "primary";
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-24
     * Proposito: centraliza que estados se consideran archivados en Catalogo.
     * Impacto: Catalogo ERP; evita tratar inactivo/descontinuado/fusionado como vigentes.
     */
    function esEstatusArchivado(estatus) {
        return ["inactivo", "descontinuado", "fusionado"].indexOf(String(estatus || "")) >= 0;
    }

    function recargarIncidenciasCalidad() {
        request("/catalogoerp/incidencias_calidad?estatus=abiertas&limite=50").then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje);
            }
            incidenciasCalidad = (response.depurar && response.depurar.incidencias) || [];
            renderIncidenciasCalidad();
        }).catch(function (error) {
            Swal.fire({text: error.message || String(error), icon: "error", confirmButtonText: "Aceptar"});
        });
    }

    function abrirSkuTemporal(idIncidencia) {
        var incidencia = incidenciasCalidad.find(function (item) {
            return String(item.id_incidencia_calidad) === String(idIncidencia);
        });
        if (!incidencia) {
            return;
        }
        var form = document.getElementById("catalogo_form_sku_temporal");
        if (!form) {
            return;
        }
        form.reset();
        var evidencia = incidencia.evidencia_json || {};
        var renglon = evidencia.renglon || {};
        setValor(form, "id_incidencia_calidad", incidencia.id_incidencia_calidad);
        setValor(form, "codigo_producto", "TMP-PROV-" + (incidencia.id_referencia || incidencia.id_incidencia_calidad));
        setValor(form, "nombre_producto", renglon.descripcion_proveedor || incidencia.titulo || "");
        setValor(form, "sku", renglon.sku_proveedor || renglon.codigo_barras || renglon.codigo_interno || ("TMP-PROV-" + (incidencia.id_referencia || incidencia.id_incidencia_calidad)));
        setValor(form, "nombre_sku", renglon.descripcion_proveedor || incidencia.titulo || "");
        document.getElementById("catalogo_sku_temporal_error").classList.add("d-none");
        bootstrap.Modal.getOrCreateInstance(document.getElementById("catalogo_modal_sku_temporal")).show();
    }

    function guardarSkuTemporal(event) {
        event.preventDefault();
        var form = event.currentTarget;
        if (!validarFactorUnidadBase(form, document.getElementById("catalogo_sku_temporal_error"))) {
            return;
        }
        Swal.fire({
            text: "Se creara producto y SKU en borrador. No se activara, no tendra costo y no quedara ligado al proveedor automaticamente.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Crear borrador",
            cancelButtonText: "Cancelar"
        }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }
            enviarFormulario(form, "/catalogoerp/incidencia_proveedor_crear_sku_temporal", document.getElementById("catalogo_sku_temporal_error"), function () {
                bootstrap.Modal.getInstance(document.getElementById("catalogo_modal_sku_temporal")).hide();
                recargarIncidenciasCalidad();
                cargar();
            });
        });
    }

    function actualizarPaginacion(total, totalPaginas, inicio, cantidadPagina) {
        var info = document.getElementById("catalogo_paginacion_info");
        var anterior = document.getElementById("catalogo_pagina_anterior");
        var siguiente = document.getElementById("catalogo_pagina_siguiente");
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

    function guardarAlta(event) {
        event.preventDefault();
        var currentForm = event.currentTarget;
        var errorBox = document.getElementById("catalogo_error");
        if (!validarFactorUnidadBase(currentForm, errorBox)) {
            return;
        }
        if (!validarFormularioGranel(currentForm, errorBox)) {
            return;
        }
        enviarFormulario(currentForm, "/catalogoerp/registrar", errorBox, function (response) {
            bootstrap.Modal.getInstance(document.getElementById("catalogo_modal_alta")).hide();
            formAlta.reset();
            actualizarCamposGranel(formAlta);
            cargar();
            abrirDetalle(response.depurar.id_producto_erp);
        });
    }

    function abrirDetalle(idProducto, tabDestino) {
        productoActualId = Number(idProducto);
        request("/catalogoerp/consultar?id_producto_erp=" + encodeURIComponent(productoActualId)).then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje);
            }
            llenarDetalleProducto(response.depurar.producto);
            detalleActual = response.depurar;
            filtroObjetivoSku = "todos";
            mostrarSkusArchivados = false;
            renderSkus(response.depurar.skus || []);
            renderImagenes(response.depurar.imagenes || []);
            renderProveedores(response.depurar.proveedores || []);
            renderPresentaciones(response.depurar.presentaciones || []);
            renderPaquetes(response.depurar.paquetes || {});
            renderVariantes(response.depurar.skus || [], response.depurar.variantes || {});
            precargarSkuBase(response.depurar.skus || []);
            llenarSelect("catalogo_proveedor_sku", response.depurar.skus || [], "id_sku", etiquetaSku, false);
            llenarSelect("catalogo_imagen_sku", response.depurar.skus || [], "id_sku", etiquetaSku, true);
            llenarSelect("catalogo_presentacion_base", response.depurar.skus || [], "id_sku", etiquetaSku, false);
            llenarSelect("catalogo_presentacion_sku", response.depurar.skus || [], "id_sku", etiquetaSku, false);
            llenarSelect("catalogo_paquete_sku", response.depurar.skus || [], "id_sku", etiquetaSku, false);
            actualizarFormularioSkuPaqueteInline(response.depurar.skus || []);
            llenarSelect("catalogo_paquete_opcion_sku", [], "id_sku", etiquetaSku, true);
            llenarSelect("catalogo_paquete_opcion_unidad", catalogosDisponibles.unidades || [], "id_unidad", etiquetaUnidad, true);
            limpiarFormularioSkuProveedor();
            activarTabDetalle(tabDestino || "catalogo_detalle_producto");
            var modalDetalle = document.getElementById("catalogo_modal_detalle");
            activarBuscadoresSelects(modalDetalle);
            bootstrap.Modal.getOrCreateInstance(modalDetalle).show();
        }).catch(function (error) {
            mostrarError(document.getElementById("catalogo_detalle_error"), error);
        });
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-25
     * Proposito: abre el modal directamente en la pestaña que corresponde al pendiente seleccionado.
     * Impacto: Catalogo ERP; reduce pasos al sanear productos migrados.
     */
    function activarTabDetalle(tabId) {
        var modal = document.getElementById("catalogo_modal_detalle");
        if (!modal || !tabId) {
            return;
        }
        var link = modal.querySelector("[data-bs-toggle='tab'][href='#" + tabId + "']");
        if (link && window.bootstrap && bootstrap.Tab) {
            bootstrap.Tab.getOrCreateInstance(link).show();
        }
    }

    function llenarDetalleProducto(producto) {
        document.getElementById("catalogo_detalle_titulo").textContent = producto.nombre;
        document.getElementById("catalogo_detalle_codigo").textContent = producto.codigo_producto;
        var editar = document.getElementById("catalogo_form_editar");
        var skuForm = document.getElementById("catalogo_form_sku");
        if (editar) {
            ["id_producto_erp", "codigo_producto", "tipo_producto", "estatus", "id_marca_erp", "id_categoria_erp", "descripcion"].forEach(function (name) {
                var origen = name === "codigo_producto" ? producto.codigo_producto : producto[name];
                setValor(editar, name, origen || "");
            });
            setValor(editar, "nombre_producto", producto.nombre);
            editar.querySelector("[name='maneja_variantes']").checked = String(producto.maneja_variantes) === "1";
        }
        if (skuForm) {
            skuForm.reset();
            setValor(skuForm, "id_producto_erp", producto.id_producto_erp);
            setValor(skuForm, "id_sku", "");
            limpiarIdentidadSkuNuevo(skuForm);
            mostrarAvisoPlantillaSku(null);
            actualizarModoSku(false);
            actualizarCamposGranel(skuForm);
        }
        var imagenForm = document.getElementById("catalogo_form_imagen");
        if (imagenForm) {
            imagenForm.reset();
            setValor(imagenForm, "id_producto_erp", producto.id_producto_erp);
            setValor(imagenForm, "id_imagen_erp", "");
            actualizarPreviewImagen(imagenForm);
            actualizarModoImagen(false);
        }
        var variantesForm = document.getElementById("catalogo_form_variantes");
        if (variantesForm) {
            setValor(variantesForm, "id_producto_erp", producto.id_producto_erp);
        }
        var presentacionForm = document.getElementById("catalogo_form_presentacion");
        if (presentacionForm) {
            limpiarFormularioPresentacion();
        }
        limpiarFormularioPaquete();
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-24
     * Proposito: renderiza SKUs del producto ocultando archivados por defecto y permitiendo consultarlos.
     * Impacto: Catalogo ERP; descontinuar un SKU no elimina historial ni contamina la operacion diaria.
     */
    function renderSkus(skus) {
        var lista = document.getElementById("catalogo_detalle_skus_lista");
        var puedeEditar = !!document.getElementById("catalogo_form_sku");
        renderResumenObjetivosSku(skus);
        var base = skus.filter(function (sku) {
            return mostrarSkusArchivados || !esEstatusArchivado(sku.estatus);
        });
        var visibles = base.filter(function (sku) {
            return filtroObjetivoSku === "todos" || indicadoresCalidadSku(sku).some(function (indicador) {
                return indicador.objetivo === filtroObjetivoSku;
            });
        });
        lista.innerHTML = visibles.map(function (sku) {
            var controles = [
                String(sku.requiere_lote) === "1" ? "Lote" : "",
                String(sku.requiere_caducidad) === "1" ? "Caducidad" : "",
                String(sku.requiere_serie_fabricante || sku.requiere_serie) === "1" ? "Serie fabricante" : "",
                String(sku.generar_etiqueta_interna) === "1" ? "Etiqueta trazabilidad" : "",
                String(sku.requiere_escaneo_venta) === "1" ? "Escaneo venta" : "",
                String(sku.permite_venta_fraccionaria) === "1" ? "Granel " + (sku.incremento_minimo_venta || "") + " " + (sku.unidad_venta_label || sku.abreviatura || "") : ""
            ].filter(Boolean).join(", ") || "Estándar";
            var calidad = renderIndicadoresCalidadSku(sku);
            var unidadSku = "<div>" + escapeHtml(sku.unidad) + "</div><span class=\"text-muted fs-7\">Factor " + escapeHtml(sku.factor_unidad_base || "1") + "</span>";
            return "<tr><td><div class=\"fw-bold\">" + escapeHtml(sku.sku) + "</div><span class=\"text-muted fs-7\">" + escapeHtml(sku.codigo_barras || "") + "</span></td>" +
                "<td>" + escapeHtml(sku.nombre) + "</td><td>" + unidadSku + "</td>" +
                "<td>" + escapeHtml(sku.moneda || "MXN") + " " + escapeHtml(sku.precio || "0") + "</td>" +
                "<td><div>" + escapeHtml(sku.estrategia_salida || "FIFO") + "</div><span class=\"text-muted fs-7\">" + escapeHtml(controles) + "</span></td>" +
                "<td>" + calidad + "</td>" +
                "<td><span class=\"badge badge-light-" + claseEstatusMaestro(sku.estatus) + "\">" + escapeHtml(sku.estatus) + "</span></td>" +
                (puedeEditar ? "<td class=\"text-end\"><button type=\"button\" class=\"btn btn-sm btn-icon btn-light-primary\" title=\"Editar SKU\" data-editar-sku=\"" + escapeHtml(sku.id_sku) + "\"><i class=\"bi bi-pencil-square\"></i></button></td>" : "") + "</tr>";
        }).join("") || "<tr><td colspan=\"" + (puedeEditar ? "8" : "7") + "\" class=\"text-center text-muted py-7\">" + (skus.length ? "Sin SKU con este filtro" : "Sin SKU registrados") + "</td></tr>";
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-24
     * Proposito: resume alertas por objetivo operativo y permite filtrar sin crear pantallas nuevas.
     * Impacto: Catalogo ERP; vista de producto mas clara sin cambiar esquema ni permisos.
     */
    function renderResumenObjetivosSku(skus) {
        var contenedor = document.getElementById("catalogo_sku_objetivos_resumen");
        if (!contenedor) {
            return;
        }
        var base = skus.filter(function (sku) {
            return mostrarSkusArchivados || !esEstatusArchivado(sku.estatus);
        });
        var archivados = skus.filter(function (sku) {
            return esEstatusArchivado(sku.estatus);
        }).length;
        var objetivos = [
            {id: "todos", texto: "Todos", color: "primary"},
            {id: "fiscal", texto: "Fiscal", color: "danger"},
            {id: "compra", texto: "Compra", color: "warning"},
            {id: "inventario", texto: "Inventario", color: "info"},
            {id: "venta", texto: "Venta", color: "success"},
            {id: "calidad", texto: "Calidad", color: "primary"}
        ];
        var conteos = objetivos.reduce(function (acc, item) {
            acc[item.id] = item.id === "todos" ? base.length : 0;
            return acc;
        }, {});
        base.forEach(function (sku) {
            var vistos = {};
            indicadoresCalidadSku(sku).forEach(function (indicador) {
                if (indicador.objetivo && !vistos[indicador.objetivo]) {
                    conteos[indicador.objetivo] = (conteos[indicador.objetivo] || 0) + 1;
                    vistos[indicador.objetivo] = true;
                }
            });
        });
        if (filtroObjetivoSku !== "todos" && !conteos[filtroObjetivoSku]) {
            filtroObjetivoSku = "todos";
        }
        contenedor.innerHTML = objetivos.map(function (item) {
            var clase = filtroObjetivoSku === item.id ? "btn-" + item.color : "btn-light-" + item.color;
            return "<button type=\"button\" class=\"btn btn-sm " + clase + "\" data-filtro-objetivo-sku=\"" + escapeAttr(item.id) + "\">" +
                escapeHtml(item.texto) + " <span class=\"badge badge-light ms-1\">" + escapeHtml(conteos[item.id] || 0) + "</span></button>";
        }).join("") + "<button type=\"button\" class=\"btn btn-sm " + (mostrarSkusArchivados ? "btn-secondary" : "btn-light-secondary") + "\" data-toggle-skus-archivados=\"1\">" +
            escapeHtml(mostrarSkusArchivados ? "Ocultar archivados" : "Ver archivados") + " <span class=\"badge badge-light ms-1\">" + escapeHtml(archivados) + "</span></button>";
    }

    function renderIndicadoresCalidadSku(sku) {
        var indicadores = indicadoresCalidadSku(sku);
        if (!indicadores.length) {
            return "<span class=\"badge badge-light-success\" title=\"Sin alertas de calidad visibles en Catalogo; otros modulos pueden requerir validaciones propias.\">Sin alertas</span>";
        }
        return "<div class=\"d-flex flex-wrap gap-1\">" + indicadores.map(function (indicador) {
            return "<span class=\"badge badge-light-" + indicador.color + "\" title=\"" + escapeAttr(indicador.titulo) + "\">" + escapeHtml(indicador.texto) + "</span>";
        }).join("") + "</div>";
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-24
     * Proposito: separa alertas por objetivo operativo sin crear nuevos estatus ni tocar esquema.
     * Impacto: Catalogo ERP; ayuda a distinguir maestro, compra, fiscal, venta e inventario.
     */
    function indicadoresCalidadSku(sku) {
        var indicadores = [];
        var activo = String(sku.estatus || "") === "activo";
        var proveedoresActivos = parseInt(sku.proveedores_activos || 0, 10) || 0;
        var controlaInventario = !["servicio", "cargo"].includes(String(sku.tipo_inventario || ""));
        var fiscalCompleto = tieneValor(sku.clave_producto_sat) && tieneValor(sku.clave_unidad_sat) &&
            tieneValor(sku.objeto_impuesto) && tieneValor(sku.iva_porcentaje) &&
            tieneValor(sku.ieps_porcentaje) && tieneValor(sku.incluye_impuestos);

        if (!fiscalCompleto) {
            indicadores.push({objetivo: "fiscal", texto: "Fiscal pendiente", color: activo && proveedoresActivos > 0 ? "danger" : "warning", titulo: "Faltan datos fiscales minimos. Afecta compras con evidencia fiscal, facturacion y validacion fiscal."});
        }
        var ventaFraccionaria = String(sku.permite_venta_fraccionaria) === "1";
        var unidadDecimal = String(sku.decimales_permitidos) === "1";
        if (activo && proveedoresActivos <= 0) {
            indicadores.push({objetivo: "compra", texto: ventaFraccionaria ? "Compra granel sin proveedor" : "Compra sin proveedor", color: "warning", titulo: ventaFraccionaria ? "SKU granel activo sin proveedor activo ni conversion de compra." : "SKU activo sin proveedor activo. Afecta compras y recepcion."});
        }
        if (activo && controlaInventario && unidadDecimal && !ventaFraccionaria) {
            indicadores.push({objetivo: "inventario", texto: "Inventario unidad decimal", color: "info", titulo: "La unidad base permite decimales; confirma si se vende a granel o solo se usa para inventario/merma."});
        }
        if (activo && !tieneValor(sku.codigo_barras)) {
            indicadores.push({objetivo: "venta", texto: "Venta sin codigo", color: "warning", titulo: "SKU activo sin codigo principal. No bloquea compras por id_sku, pero afecta busqueda, escaneo y venta mostrador."});
        }
        if (activo && !numeroMayorCero(sku.precio)) {
            indicadores.push({objetivo: "venta", texto: "Venta sin precio prov.", color: "info", titulo: "Sin precio provisional en Catalogo. El precio final debe definirse despues en Listas de precios/canal."});
        }
        if (controlaInventario && (String(sku.tiene_regla_inventario) !== "1" || Number(sku.punto_reorden || 0) <= 0)) {
            indicadores.push({objetivo: "inventario", texto: "Inventario revisar", color: "info", titulo: "Regla de inventario pendiente o con reorden en cero. Afecta alertas de reposicion, no la existencia actual."});
        }
        if (Number(sku.incidencias_calidad_abiertas || 0) > 0) {
            indicadores.push({objetivo: "calidad", texto: "Calidad " + String(sku.incidencias_calidad_abiertas), color: "primary", titulo: "Incidencias de calidad abiertas para este SKU."});
        }
        return indicadores;
    }

    function tieneValor(valor) {
        return valor !== null && valor !== undefined && String(valor).trim() !== "";
    }

    function numeroMayorCero(valor) {
        return tieneValor(valor) && !isNaN(Number(valor)) && Number(valor) > 0;
    }

    function renderImagenes(imagenes) {
        var lista = document.getElementById("catalogo_imagenes_lista");
        if (!lista) {
            return;
        }
        var puedeEditar = !!document.getElementById("catalogo_form_imagen");
        lista.innerHTML = imagenes.map(function (imagen) {
            var src = normalizarImagenUrl(imagen.url_imagen);
            var acciones = puedeEditar
                ? "<div class=\"d-flex gap-2 mt-3\"><button type=\"button\" class=\"btn btn-sm btn-light-primary\" data-editar-imagen=\"" + escapeHtml(imagen.id_imagen_erp) + "\"><i class=\"bi bi-pencil-square\"></i> Editar</button>" +
                  (imagen.estatus === "activo" ? "<button type=\"button\" class=\"btn btn-sm btn-light-danger\" data-desactivar-imagen=\"" + escapeHtml(imagen.id_imagen_erp) + "\"><i class=\"bi bi-eye-slash\"></i></button>" : "") + "</div>"
                : "";
            return "<div class=\"col-md-3 col-sm-6\"><div class=\"border rounded p-3 h-100\">" +
                "<div class=\"bg-light rounded mb-3\" style=\"aspect-ratio:1/1;background-image:url('" + escapeAttr(src) + "');background-size:cover;background-position:center\"></div>" +
                "<div class=\"d-flex align-items-center justify-content-between gap-2\"><span class=\"badge badge-light-primary\">" + escapeHtml(imagen.tipo_imagen) + "</span><span class=\"badge badge-light-" + (imagen.estatus === "activo" ? "success" : "secondary") + "\">" + escapeHtml(imagen.estatus) + "</span></div>" +
                "<div class=\"fw-bold fs-7 mt-3 text-truncate\" title=\"" + escapeAttr(imagen.url_imagen) + "\">" + escapeHtml(imagen.url_imagen) + "</div>" +
                "<div class=\"text-muted fs-8\">" + escapeHtml(imagen.sku ? "SKU " + imagen.sku : "Producto maestro") + " | " + escapeHtml(imagen.fuente || "erp") + "</div>" +
                acciones + "</div></div>";
        }).join("") || "<div class=\"col-12\"><div class=\"alert alert-light-warning mb-0\">Este producto todavia no tiene imagenes en el catalogo ERP.</div></div>";
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-26
     * Proposito: consulta imagenes ecommerce recuperables y muestra resumen operativo en la pestaña Imagenes.
     * Impacto: Catalogo ERP; solo lectura, prepara saneamiento de productos sin imagen.
     */
    function auditarImagenesEcommerce() {
        var resumen = document.getElementById("catalogo_imagenes_ecommerce_resumen");
        var boton = document.getElementById("catalogo_imagenes_ecommerce_auditar");
        if (!resumen) {
            return;
        }
        resumen.innerHTML = "<div class=\"text-muted fs-8\">Auditando imagenes disponibles...</div>";
        if (boton) { boton.disabled = true; }
        if (!productoActualId) {
            resumen.innerHTML = "<div class=\"alert alert-light-warning mb-0\">Abre un producto para auditar imagenes relacionadas.</div>";
            if (boton) { boton.disabled = false; }
            return;
        }
        request("/catalogoerp/imagenes_ecommerce_auditar?id_producto_erp=" + encodeURIComponent(productoActualId)).then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje);
            }
            renderResumenImagenesEcommerce(response.depurar || {});
        }).catch(function (error) {
            resumen.innerHTML = "<div class=\"alert alert-light-danger mb-0\">" + escapeHtml(error.message || "No se pudo auditar imagenes") + "</div>";
        }).finally(function () {
            if (boton) { boton.disabled = false; }
        });
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-26
     * Proposito: pinta metricas de recuperacion de imagenes y habilita accion solo si hay candidatas.
     * Impacto: Catalogo ERP; evita ejecutar recuperacion sin datos disponibles.
     */
    function renderResumenImagenesEcommerce(data) {
        var contenedor = document.getElementById("catalogo_imagenes_ecommerce_resumen");
        var recuperar = document.getElementById("catalogo_imagenes_ecommerce_recuperar");
        if (!contenedor) {
            return;
        }
        var resumen = data.resumen || {};
        var candidatas = data.candidatas || [];
        var metricas = [
            ["Candidatas para este producto", candidatas.length, "primary"],
            ["ERP sin imagen", resumen.erp_productos_sin_imagen_activa || 0, "warning"],
            ["Imagenes faltantes globales", resumen.faltantes_en_erp || 0, "success"]
        ];
        contenedor.innerHTML = "<div class=\"d-flex flex-wrap gap-2 mb-3\">" + metricas.map(function (item) {
            return "<span class=\"badge badge-light-" + item[2] + "\">" + escapeHtml(item[0]) + ": " + escapeHtml(item[1]) + "</span>";
        }).join("") + "</div>" + renderCandidatasImagenesEcommerce(candidatas);
        if (recuperar) {
            recuperar.classList.add("d-none");
        }
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-26
     * Proposito: muestra imagenes ecommerce candidatas del producto abierto antes de relacionarlas.
     * Impacto: Catalogo ERP; decision manual por imagen, sin recuperacion masiva.
     */
    function renderCandidatasImagenesEcommerce(candidatas) {
        if (!candidatas.length) {
            return "<div class=\"text-muted fs-8\">No hay imagenes ecommerce pendientes para este producto.</div>";
        }
        return "<div class=\"row g-4\">" + candidatas.map(function (item) {
            var src = normalizarImagenUrl(item.url_imagen);
            return "<div class=\"col-md-4 col-xl-3\"><div class=\"border rounded p-3 h-100\">" +
                "<div class=\"bg-light rounded mb-3\" style=\"aspect-ratio:1/1;background-image:url('" + escapeAttr(src) + "');background-size:cover;background-position:center\"></div>" +
                "<div class=\"d-flex flex-wrap gap-2 mb-3\"><span class=\"badge badge-light-primary\">" + escapeHtml(item.tipo_imagen_erp || "galeria") + "</span>" +
                "<span class=\"badge badge-light\">" + escapeHtml("Ecom #" + item.id_producto_imagen) + "</span></div>" +
                "<div class=\"text-muted fs-8 text-truncate mb-3\" title=\"" + escapeAttr(item.url_imagen) + "\">" + escapeHtml(item.url_imagen) + "</div>" +
                "<div class=\"d-flex gap-2 flex-wrap\">" +
                "<button type=\"button\" class=\"btn btn-sm btn-primary\" data-recuperar-imagen-ecommerce=\"" + escapeAttr(item.id_producto_imagen) + "\" data-tipo-imagen=\"portada\">Usar portada</button>" +
                "<button type=\"button\" class=\"btn btn-sm btn-light-primary\" data-recuperar-imagen-ecommerce=\"" + escapeAttr(item.id_producto_imagen) + "\" data-tipo-imagen=\"galeria\">Usar galeria</button>" +
                "</div></div></div>";
        }).join("") + "</div>";
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-26
     * Proposito: ejecuta recuperacion de imagenes ecommerce ya vinculadas y refresca Catalogo.
     * Impacto: Catalogo ERP; inserta registros en erp_catalogo_imagenes mediante endpoint auditado.
     */
    function recuperarImagenesEcommerce(idImagenEcommerce, tipoImagen) {
        var resumen = document.getElementById("catalogo_imagenes_ecommerce_resumen");
        if (!productoActualId || !idImagenEcommerce) {
            if (resumen) {
                resumen.innerHTML = "<div class=\"alert alert-light-warning mb-0\">Selecciona una imagen del producto abierto.</div>";
            }
            return;
        }
        Swal.fire({
            text: "Se relacionara esta imagen ecommerce con el producto abierto.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Relacionar",
            cancelButtonText: "Cancelar"
        }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }
            request("/catalogoerp/imagenes_ecommerce_recuperar", {
                id_producto_erp: productoActualId,
                id_producto_imagen: idImagenEcommerce,
                tipo_imagen: tipoImagen || "galeria"
            }).then(function (response) {
                if (response.error) {
                    throw new Error(response.mensaje);
                }
                Swal.fire({text: response.mensaje, icon: "success", confirmButtonText: "Aceptar"});
                if (resumen) {
                    resumen.innerHTML = "<div class=\"alert alert-light-success mb-0\">" + escapeHtml(response.mensaje) + "</div>";
                }
                cargar();
                if (productoActualId) {
                    abrirDetalle(productoActualId, "catalogo_detalle_imagenes");
                }
            }).catch(function (error) {
                if (resumen) {
                    resumen.innerHTML = "<div class=\"alert alert-light-danger mb-0\">" + escapeHtml(error.message || "No se pudo recuperar imagenes") + "</div>";
                }
            });
        });
    }

    function normalizarImagenUrl(url) {
        url = url || "";
        if (/^https?:\/\//i.test(url) || url.indexOf("/") === 0) {
            return url;
        }
        return "/" + url;
    }

    function actualizarPreviewImagen(form) {
        var preview = document.getElementById("catalogo_imagen_preview");
        var vacio = document.getElementById("catalogo_imagen_preview_vacio");
        var input = form ? form.querySelector("[name='url_imagen']") : null;
        var url = input ? input.value.trim() : "";
        if (!preview || !vacio) {
            return;
        }
        preview.onload = function () {
            preview.classList.remove("d-none");
            vacio.classList.add("d-none");
        };
        preview.onerror = function () {
            preview.classList.add("d-none");
            vacio.textContent = "No se pudo cargar";
            vacio.classList.remove("d-none");
        };
        if (!url) {
            preview.removeAttribute("src");
            preview.classList.add("d-none");
            vacio.textContent = "Sin ruta";
            vacio.classList.remove("d-none");
            return;
        }
        vacio.textContent = "Cargando";
        vacio.classList.remove("d-none");
        preview.classList.add("d-none");
        preview.src = normalizarImagenUrl(url);
    }

    function editarImagen(idImagen) {
        var form = document.getElementById("catalogo_form_imagen");
        var imagen = (detalleActual.imagenes || []).find(function (item) { return String(item.id_imagen_erp) === String(idImagen); });
        if (!form || !imagen) {
            return;
        }
        setValor(form, "id_producto_erp", productoActualId);
        setValor(form, "id_imagen_erp", imagen.id_imagen_erp);
        setValor(form, "tipo_imagen", imagen.tipo_imagen || "galeria");
        setValor(form, "id_sku", imagen.id_sku || "");
        setValor(form, "url_imagen", imagen.url_imagen || "");
        setValor(form, "orden", imagen.orden || "0");
        setValor(form, "estatus", imagen.estatus || "activo");
        setValor(form, "texto_alternativo", imagen.texto_alternativo || "");
        actualizarPreviewImagen(form);
        actualizarModoImagen(true);
        form.scrollIntoView({behavior: "smooth", block: "start"});
    }

    function limpiarFormularioImagen() {
        var form = document.getElementById("catalogo_form_imagen");
        if (!form) {
            return;
        }
        form.reset();
        setValor(form, "id_producto_erp", productoActualId);
        setValor(form, "id_imagen_erp", "");
        actualizarPreviewImagen(form);
        actualizarModoImagen(false);
    }

    function actualizarModoImagen(editando) {
        var titulo = document.getElementById("catalogo_imagen_form_titulo");
        var cancelar = document.getElementById("catalogo_cancelar_edicion_imagen");
        if (titulo) {
            titulo.textContent = editando ? "Editar imagen" : "Agregar imagen";
        }
        if (cancelar) {
            cancelar.classList.toggle("d-none", !editando);
        }
    }

    function precargarSkuBase(skus) {
        var form = document.getElementById("catalogo_form_sku");
        if (!form || !skus.length || form.querySelector("[name='id_sku']").value) {
            return;
        }
        var base = skus[skus.length - 1];
        setValor(form, "id_unidad_base", base.id_unidad_base || "");
        setValor(form, "factor_unidad_base", base.factor_unidad_base || "1");
        setValor(form, "tipo_inventario", base.tipo_inventario || "inventariable");
        setValor(form, "costo_referencia", base.costo_referencia || "0");
        setValor(form, "precio", base.precio || "0");
        setValor(form, "moneda", base.moneda || "MXN");
        setValor(form, "stock_minimo", base.stock_minimo || "0");
        setValor(form, "stock_maximo", base.stock_maximo || "");
        setValor(form, "punto_reorden", base.punto_reorden || "0");
        setValor(form, "estrategia_salida", base.estrategia_salida || "FIFO");
        setValor(form, "iva_porcentaje", base.iva_porcentaje || "");
        setValor(form, "ieps_porcentaje", base.ieps_porcentaje || "");
        setValor(form, "dias_alerta_caducidad", base.dias_alerta_caducidad || "90");
        setValor(form, "dias_minimos_recepcion", base.dias_minimos_recepcion || "0");
        setChecked(form, "requiere_cantidad_variable_recepcion", base.requiere_cantidad_variable_recepcion);
        setChecked(form, "requiere_unidades_fisicas_recepcion", base.requiere_unidades_fisicas_recepcion);
        setValor(form, "tolerancia_recepcion_porcentaje", base.tolerancia_recepcion_porcentaje || "");
        setValor(form, "nota_recepcion_variable", base.nota_recepcion_variable || "");
        var incluye = form.querySelector("[name='incluye_impuestos']");
        if (incluye) { incluye.checked = String(base.incluye_impuestos) === "1"; }
        var lote = form.querySelector("[name='requiere_lote']");
        if (lote) { lote.checked = String(base.requiere_lote) === "1"; }
        var caducidad = form.querySelector("[name='requiere_caducidad']");
        if (caducidad) { caducidad.checked = String(base.requiere_caducidad) === "1"; }
        var serie = form.querySelector("[name='requiere_serie']");
        if (serie) { serie.checked = String(base.requiere_serie) === "1"; }
        setChecked(form, "requiere_serie_fabricante", base.requiere_serie_fabricante);
        setChecked(form, "generar_etiqueta_interna", base.generar_etiqueta_interna);
        setChecked(form, "requiere_escaneo_venta", base.requiere_escaneo_venta);
        setChecked(form, "permite_venta_fraccionaria", base.permite_venta_fraccionaria);
        setValor(form, "precision_decimal", base.precision_decimal || "0");
        setValor(form, "incremento_minimo_venta", base.incremento_minimo_venta || "1");
        setValor(form, "unidad_venta_label", base.unidad_venta_label || "");
        setChecked(form, "permite_etiqueta_fraccionada", base.permite_etiqueta_fraccionada);
        actualizarCamposGranel(form);
        setValor(form, "prefijo_etiqueta_interna", base.prefijo_etiqueta_interna || "");
        setValor(form, "plantilla_etiqueta", base.plantilla_etiqueta || "");
        setValor(form, "tipo_etiqueta_seguridad", base.tipo_etiqueta_seguridad || "");
        setValor(form, "instrucciones_etiquetado", base.instrucciones_etiquetado || "");
        var ventaSinStock = form.querySelector("[name='permite_venta_sin_existencia']");
        if (ventaSinStock) { ventaSinStock.checked = String(base.permite_venta_sin_existencia) === "1"; }
        var negativa = form.querySelector("[name='permite_existencia_negativa']");
        if (negativa) { negativa.checked = String(base.permite_existencia_negativa) === "1"; }
        limpiarIdentidadSkuNuevo(form);
        mostrarAvisoPlantillaSku(base);
    }

    function editarSku(idSku) {
        var form = document.getElementById("catalogo_form_sku");
        var sku = (detalleActual.skus || []).find(function (item) { return String(item.id_sku) === String(idSku); });
        if (!form || !sku) {
            return;
        }
        setValor(form, "id_producto_erp", productoActualId);
        setValor(form, "id_sku", sku.id_sku);
        setValor(form, "sku", sku.sku);
        form.dataset.skuOriginal = sku.sku || "";
        setValor(form, "nombre_sku", sku.nombre);
        setValor(form, "id_unidad_base", sku.id_unidad_base);
        setValor(form, "factor_unidad_base", sku.factor_unidad_base || "1");
        setValor(form, "estatus", sku.estatus || "activo");
        setValor(form, "codigo_barras", sku.codigo_barras || "");
        form.dataset.codigoBarrasOriginal = sku.codigo_barras || "";
        setValor(form, "tipo_inventario", sku.tipo_inventario || "inventariable");
        setValor(form, "costo_referencia", sku.costo_referencia || "0");
        setValor(form, "precio", sku.precio || "0");
        setValor(form, "moneda", sku.moneda || "MXN");
        setValor(form, "stock_minimo", sku.stock_minimo || "0");
        setValor(form, "stock_maximo", sku.stock_maximo || "");
        setValor(form, "punto_reorden", sku.punto_reorden || "0");
        setValor(form, "estrategia_salida", sku.estrategia_salida || "FIFO");
        setValor(form, "iva_porcentaje", sku.iva_porcentaje || "");
        setValor(form, "ieps_porcentaje", sku.ieps_porcentaje || "");
        setValor(form, "clave_producto_sat", sku.clave_producto_sat || "");
        setValor(form, "clave_unidad_sat", sku.clave_unidad_sat || "");
        setValor(form, "objeto_impuesto", sku.objeto_impuesto || "");
        setValor(form, "dias_alerta_caducidad", sku.dias_alerta_caducidad || "90");
        setValor(form, "dias_minimos_recepcion", sku.dias_minimos_recepcion || "0");
        setChecked(form, "requiere_cantidad_variable_recepcion", sku.requiere_cantidad_variable_recepcion);
        setChecked(form, "requiere_unidades_fisicas_recepcion", sku.requiere_unidades_fisicas_recepcion);
        setValor(form, "tolerancia_recepcion_porcentaje", sku.tolerancia_recepcion_porcentaje || "");
        setValor(form, "nota_recepcion_variable", sku.nota_recepcion_variable || "");
        setChecked(form, "requiere_lote", sku.requiere_lote);
        setChecked(form, "requiere_caducidad", sku.requiere_caducidad);
        setChecked(form, "requiere_serie", sku.requiere_serie);
        setChecked(form, "requiere_serie_fabricante", sku.requiere_serie_fabricante);
        setChecked(form, "generar_etiqueta_interna", sku.generar_etiqueta_interna);
        setChecked(form, "requiere_escaneo_venta", sku.requiere_escaneo_venta);
        setChecked(form, "permite_venta_fraccionaria", sku.permite_venta_fraccionaria);
        setValor(form, "precision_decimal", sku.precision_decimal || "0");
        setValor(form, "incremento_minimo_venta", sku.incremento_minimo_venta || "1");
        setValor(form, "unidad_venta_label", sku.unidad_venta_label || "");
        setChecked(form, "permite_etiqueta_fraccionada", sku.permite_etiqueta_fraccionada);
        actualizarCamposGranel(form);
        setValor(form, "prefijo_etiqueta_interna", sku.prefijo_etiqueta_interna || "");
        setValor(form, "plantilla_etiqueta", sku.plantilla_etiqueta || "");
        setValor(form, "tipo_etiqueta_seguridad", sku.tipo_etiqueta_seguridad || "");
        setValor(form, "instrucciones_etiquetado", sku.instrucciones_etiquetado || "");
        setChecked(form, "permite_existencia_negativa", sku.permite_existencia_negativa);
        setChecked(form, "permite_venta_sin_existencia", sku.permite_venta_sin_existencia);
        setChecked(form, "incluye_impuestos", sku.incluye_impuestos);
        form.dataset.impactoIdentidad = JSON.stringify(impactoIdentidadSku(sku));
        actualizarAlertaIdentidadSku(form);
        actualizarAvisoSkuArchivado(sku);
        actualizarModoSku(true);
        form.scrollIntoView({behavior: "smooth", block: "start"});
    }

    function limpiarFormularioSku() {
        var form = document.getElementById("catalogo_form_sku");
        if (!form) {
            return;
        }
        form.reset();
        setValor(form, "id_producto_erp", productoActualId);
        setValor(form, "id_sku", "");
        form.dataset.skuOriginal = "";
        form.dataset.codigoBarrasOriginal = "";
        form.dataset.impactoIdentidad = "";
        actualizarAlertaIdentidadSku(form);
        actualizarModoSku(false);
        precargarSkuBase(detalleActual.skus || []);
        limpiarIdentidadSkuNuevo(form);
        actualizarCamposGranel(form);
    }

    function limpiarIdentidadSkuNuevo(form) {
        setValor(form, "id_sku", "");
        setValor(form, "sku", "");
        setValor(form, "nombre_sku", "");
        setValor(form, "codigo_barras", "");
        setValor(form, "motivo_cambio_identidad", "");
        setValor(form, "estatus", "activo");
        actualizarAvisoSkuArchivado(null);
        form.dataset.skuOriginal = "";
        form.dataset.codigoBarrasOriginal = "";
        form.dataset.impactoIdentidad = "";
        actualizarAlertaIdentidadSku(form);
    }

    function mostrarAvisoPlantillaSku(base) {
        var alerta = document.getElementById("catalogo_sku_plantilla_alerta");
        if (!alerta) {
            return;
        }
        if (!base) {
            alerta.classList.add("d-none");
            alerta.textContent = "";
            return;
        }
        alerta.textContent = "Formulario listo para crear un SKU nuevo. Solo se copiaron reglas de inventario, fiscal y precio provisional desde " + (base.sku || "el ultimo SKU") + "; captura SKU, nombre y codigo antes de guardar.";
        alerta.classList.remove("d-none");
    }

    function impactoIdentidadSku(sku) {
        return {
            ecommerce: parseInt(sku.vinculos_ecommerce || 0, 10) || 0,
            proveedores: parseInt(sku.proveedores_activos || 0, 10) || 0,
            solicitudes: parseInt(sku.usos_solicitudes || 0, 10) || 0,
            ordenes: parseInt(sku.usos_ordenes || 0, 10) || 0,
            recepciones: parseInt(sku.usos_recepciones || 0, 10) || 0,
            movimientos: parseInt(sku.usos_movimientos || 0, 10) || 0,
            existencias: parseInt(sku.usos_existencias || 0, 10) || 0
        };
    }

    function leerImpactoIdentidad(form) {
        try {
            return JSON.parse(form.dataset.impactoIdentidad || "{}");
        } catch (e) {
            return {};
        }
    }

    function resumenImpactoIdentidad(impacto) {
        var partes = [];
        if (impacto.ecommerce) { partes.push(impacto.ecommerce + " vínculo(s) ecommerce"); }
        if (impacto.proveedores) { partes.push(impacto.proveedores + " proveedor(es) activo(s)"); }
        if (impacto.solicitudes) { partes.push(impacto.solicitudes + " solicitud(es)"); }
        if (impacto.ordenes) { partes.push(impacto.ordenes + " orden(es)"); }
        if (impacto.recepciones) { partes.push(impacto.recepciones + " recepción(es)"); }
        if (impacto.movimientos) { partes.push(impacto.movimientos + " movimiento(s)"); }
        if (impacto.existencias) { partes.push(impacto.existencias + " existencia(s)"); }
        return partes;
    }

    function actualizarAlertaIdentidadSku(form) {
        var alerta = document.getElementById("catalogo_sku_identidad_alerta");
        if (!alerta) {
            return;
        }
        var idSku = form.querySelector("[name='id_sku']").value;
        var impacto = leerImpactoIdentidad(form);
        var partes = resumenImpactoIdentidad(impacto);
        var skuActual = form.querySelector("[name='sku']").value.trim();
        var codigoActual = form.querySelector("[name='codigo_barras']").value.trim();
        var cambiaIdentidad = idSku && (skuActual !== (form.dataset.skuOriginal || "") || codigoActual !== (form.dataset.codigoBarrasOriginal || ""));
        alerta.classList.add("d-none");
        alerta.classList.remove("alert-light-danger", "alert-light-warning", "alert-light-info");
        if (!idSku) {
            alerta.textContent = "";
            return;
        }
        if (partes.length) {
            alerta.classList.add(cambiaIdentidad ? "alert-light-danger" : "alert-light-warning");
            alerta.textContent = (cambiaIdentidad ? "Cambio de identidad con impacto: " : "Este SKU ya tiene uso: ") + partes.join(", ") + ". Conserva un motivo claro antes de guardar.";
        } else if (cambiaIdentidad) {
            alerta.classList.add("alert-light-info");
            alerta.textContent = "El SKU o código cambió. Aunque no se detectó uso operativo, el motivo quedará registrado en auditoría.";
        } else {
            return;
        }
        alerta.classList.remove("d-none");
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-24
     * Proposito: explica como recuperar un SKU archivado sin borrar ni recrear registros.
     * Impacto: Catalogo ERP; refuerza politica de archivado sobre eliminacion fisica.
     */
    function actualizarAvisoSkuArchivado(sku) {
        var alerta = document.getElementById("catalogo_sku_archivado_alerta");
        if (!alerta) {
            return;
        }
        if (!sku || !esEstatusArchivado(sku.estatus)) {
            alerta.classList.add("d-none");
            alerta.textContent = "";
            return;
        }
        if (sku.estatus === "fusionado") {
            alerta.textContent = "Este SKU esta fusionado. No se recupera manualmente desde este formulario; requiere flujo de correccion de fusion.";
        } else {
            alerta.textContent = "Este SKU esta archivado. Para recuperarlo, cambia Estado maestro a Activo o En revision y guarda los cambios.";
        }
        alerta.classList.remove("d-none");
    }

    function actualizarModoSku(editando) {
        var titulo = document.getElementById("catalogo_sku_form_titulo");
        var boton = document.getElementById("catalogo_sku_guardar");
        var cancelar = document.getElementById("catalogo_cancelar_edicion_sku");
        var plantilla = document.getElementById("catalogo_sku_plantilla_alerta");
        if (titulo) {
            titulo.textContent = editando ? "Editar SKU" : "Agregar SKU";
        }
        if (boton) {
            boton.innerHTML = editando ? "<i class=\"bi bi-check-lg\"></i> Guardar cambios" : "<i class=\"bi bi-plus-lg\"></i> Agregar SKU";
        }
        if (cancelar) {
            cancelar.classList.toggle("d-none", !editando);
        }
        if (plantilla && editando) {
            plantilla.classList.add("d-none");
            plantilla.textContent = "";
        }
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-24
     * Proposito: muestra relaciones proveedor-SKU sin exponer costos a perfiles sin permiso.
     * Impacto: Catalogo ERP; costo queda como dato restringido de Proveedores/Costos.
     */
    function renderProveedores(proveedores) {
        var lista = document.getElementById("catalogo_detalle_proveedores_lista");
        if (!lista) {
            return;
        }
        var puedeVerCostos = !!(window.CATALOGO_PERMISOS && window.CATALOGO_PERMISOS.costos);
        var colspan = puedeVerCostos ? 8 : 6;
        if (!proveedores.length) {
            lista.innerHTML = "<tr><td colspan=\"" + colspan + "\" class=\"text-center text-muted py-7\">Sin proveedores vinculados</td></tr>";
            return;
        }
        var grupos = {};
        proveedores.forEach(function (relacion) {
            var clave = String(relacion.id_sku || relacion.sku || "sin_sku");
            if (!grupos[clave]) {
                grupos[clave] = {
                    sku: relacion.sku || "Sin SKU",
                    nombre: relacion.nombre_sku || "",
                    unidad: relacion.unidad_base_abreviatura || relacion.unidad_base_codigo || "",
                    relaciones: []
                };
            }
            grupos[clave].relaciones.push(relacion);
        });
        lista.innerHTML = Object.keys(grupos).map(function (clave) {
            var grupo = grupos[clave];
            var activos = grupo.relaciones.filter(function (relacion) { return relacion.estatus === "activo"; }).length;
            var preferidos = grupo.relaciones.filter(function (relacion) { return String(relacion.es_preferido) === "1"; }).length;
            var aviso = activos > 0 && preferidos === 0 ? "<span class=\"badge badge-light-warning\">Sin principal</span>" : "";
            var encabezado = "<tr class=\"bg-light\"><td colspan=\"" + colspan + "\">" +
                "<div class=\"d-flex flex-wrap align-items-center gap-2\">" +
                "<span class=\"fw-bold\">" + escapeHtml(grupo.sku) + "</span>" +
                "<span class=\"text-muted\">" + escapeHtml(grupo.nombre) + "</span>" +
                (grupo.unidad ? "<span class=\"badge badge-light\">" + escapeHtml(grupo.unidad) + "</span>" : "") +
                "<span class=\"badge badge-light-primary\">" + escapeHtml(grupo.relaciones.length) + " proveedor(es)</span>" +
                aviso +
                "</div></td></tr>";
            var filas = grupo.relaciones.map(function (relacion) {
            var unidadCompra = relacion.abreviatura || relacion.unidad_compra || "unidad";
            var unidadBase = relacion.unidad_base_abreviatura || relacion.unidad_base_codigo || "base";
            var compra = "1 " + escapeHtml(unidadCompra) + " = " + escapeHtml(relacion.factor_conversion) + " " + escapeHtml(unidadBase);
            var alertas = renderAlertasProveedor(relacion);
            compra += alertas;
            var preferido = String(relacion.es_preferido) === "1" ? " <span class=\"badge badge-light-primary\">Preferido</span>" : "";
            var costo = puedeVerCostos ? "<td>" + escapeHtml(relacion.costo_ultimo) + "</td>" : "";
                var accion = "";
                if (puedeVerCostos) {
                    var hacerPrincipal = String(relacion.es_preferido) === "1" || relacion.estatus !== "activo"
                        ? ""
                        : "<button type=\"button\" class=\"btn btn-sm btn-light-success\" data-preferir-sku-proveedor=\"" + escapeAttr(relacion.id_sku_proveedor) + "\"><i class=\"bi bi-check2-circle\"></i> Principal</button>";
                accion = "<td class=\"text-end\"><div class=\"d-flex justify-content-end gap-2 flex-wrap\">" +
                    hacerPrincipal +
                    "<button type=\"button\" class=\"btn btn-sm btn-light-primary\" data-editar-sku-proveedor=\"" + escapeAttr(relacion.id_sku_proveedor) + "\"><i class=\"bi bi-pencil-square\"></i> Editar</button>" +
                    "</div></td>";
            }
                return "<tr><td class=\"text-muted ps-8\">-</td>" +
                    "<td>" + escapeHtml(relacion.proveedor) + preferido + "</td>" +
                    "<td>" + escapeHtml(relacion.sku_proveedor || "-") + "</td>" +
                    "<td><div>" + compra + "</div><span class=\"text-muted fs-7\">Mín. " + escapeHtml(relacion.cantidad_minima) + "</span></td>" +
                    costo +
                    "<td>" + escapeHtml(relacion.dias_entrega) + " días</td>" +
                    "<td><span class=\"badge badge-light-" + claseEstatusProveedor(relacion.estatus) + "\">" + escapeHtml(relacion.estatus) + "</span></td>" + accion + "</tr>";
            }).join("");
            return encabezado + filas;
        }).join("");
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-25
     * Proposito: diferencia visualmente relaciones proveedor-SKU activas e inactivas.
     * Impacto: Catalogo ERP; evita interpretar un proveedor inactivo como disponible para compras.
     */
    function claseEstatusProveedor(estatus) {
        if (estatus === "activo") {
            return "success";
        }
        if (estatus === "inactivo") {
            return "secondary";
        }
        return "info";
    }

    function renderAlertasProveedor(relacion) {
        var alertas = [];
        var factor = Number(relacion.factor_conversion || 0);
        var unidadCompraCodigo = String(relacion.unidad_compra_codigo || "").toUpperCase();
        var unidadBaseCodigo = String(relacion.unidad_base_codigo || "").toUpperCase();
        var unidadBaseDecimal = String(relacion.unidad_base_decimales || "0") === "1";
        if (relacion.estatus === "activo" && unidadBaseDecimal && Math.abs(factor - 1) < 0.0000001 && unidadCompraCodigo !== unidadBaseCodigo) {
            alertas.push({
                texto: "Revisar factor",
                color: "warning",
                titulo: "La unidad base permite decimales y la unidad de compra es distinta, pero el factor es 1. Confirma si 1 " + (relacion.abreviatura || relacion.unidad_compra || "unidad") + " realmente equivale a 1 " + (relacion.unidad_base_abreviatura || relacion.unidad_base_codigo || "unidad base") + "."
            });
        }
        if (!alertas.length) {
            return "";
        }
        return "<div class=\"d-flex flex-wrap gap-1 mt-2\">" + alertas.map(function (alerta) {
            return "<span class=\"badge badge-light-" + alerta.color + "\" title=\"" + escapeAttr(alerta.titulo) + "\">" + escapeHtml(alerta.texto) + "</span>";
        }).join("") + "</div>";
    }

    function renderPresentaciones(presentaciones) {
        var lista = document.getElementById("catalogo_presentaciones_lista");
        if (!lista) {
            return;
        }
        var puedeEditar = !!document.getElementById("catalogo_form_presentacion");
        lista.innerHTML = presentaciones.map(function (item) {
            var acciones = puedeEditar
                ? "<div class=\"d-flex justify-content-end gap-2\"><button type=\"button\" class=\"btn btn-sm btn-icon btn-light-primary\" title=\"Editar\" data-editar-presentacion=\"" + escapeAttr(item.id_sku_presentacion_regla) + "\"><i class=\"bi bi-pencil-square\"></i></button>" +
                  (item.estatus === "activa" ? "<button type=\"button\" class=\"btn btn-sm btn-icon btn-light-danger\" title=\"Desactivar\" data-desactivar-presentacion=\"" + escapeAttr(item.id_sku_presentacion_regla) + "\"><i class=\"bi bi-eye-slash\"></i></button>" : "") + "</div>"
                : "";
            var alertas = renderAlertasPresentacion(item);
            return "<tr>" +
                "<td><div class=\"fw-bold\">" + escapeHtml(item.sku_base) + "</div><span class=\"text-muted fs-7\">" + escapeHtml(item.nombre_base || "") + "</span>" + alertas + "</td>" +
                "<td><div class=\"fw-bold\">" + escapeHtml(item.sku_presentacion) + "</div><span class=\"text-muted fs-7\">" + escapeHtml(item.nombre_presentacion || "") + "</span></td>" +
                "<td>" + escapeHtml(item.factor_salida_base) + " " + escapeHtml(item.unidad_base || "") + "</td>" +
                "<td><span class=\"badge badge-light-primary\">" + escapeHtml(item.modo_disponibilidad) + "</span></td>" +
                "<td>" + escapeHtml(item.consume_stock_base_en) + "</td>" +
                "<td>" + (String(item.requiere_empaque) === "1" ? "Si" : "No") + (item.capacidad_diaria ? "<div class=\"text-muted fs-7\">Cap. " + escapeHtml(item.capacidad_diaria) + "</div>" : "") + "</td>" +
                "<td><span class=\"badge badge-light-" + (item.estatus === "activa" ? "success" : "secondary") + "\">" + escapeHtml(item.estatus) + "</span></td>" +
                (puedeEditar ? "<td class=\"text-end\">" + acciones + "</td>" : "") +
                "</tr>";
        }).join("") || "<tr><td colspan=\"" + (puedeEditar ? "8" : "7") + "\" class=\"text-center text-muted py-7\">Sin presentaciones derivadas configuradas</td></tr>";
    }

    function renderAlertasPresentacion(item) {
        var alertas = [];
        var factor = Number(item.factor_salida_base || 0);
        var tieneDecimal = Math.abs(factor - Math.round(factor)) > 0.0000001;
        var baseDecimal = String(item.unidad_base_decimales || "0") === "1";
        var magnitudBase = String(item.unidad_base_magnitud || "");
        var magnitudPresentacion = String(item.unidad_presentacion_magnitud || "");
        if (tieneDecimal && !baseDecimal) {
            alertas.push({texto: "Unidad base sin decimales", color: "danger", titulo: "El factor consume una fraccion, pero la unidad base no permite decimales. Ejemplo: 0.025 pza no es una existencia consistente."});
        }
        if (magnitudBase && magnitudPresentacion && magnitudBase !== magnitudPresentacion && magnitudPresentacion !== "unidad" && magnitudPresentacion !== "empaque") {
            alertas.push({texto: "Magnitud no coincide", color: "warning", titulo: "La presentacion usa una unidad de " + magnitudPresentacion + " pero la base usa " + magnitudBase + "."});
        }
        if (!alertas.length) {
            return "";
        }
        return "<div class=\"d-flex flex-wrap gap-1 mt-2\">" + alertas.map(function (alerta) {
            return "<span class=\"badge badge-light-" + alerta.color + "\" title=\"" + escapeAttr(alerta.titulo) + "\">" + escapeHtml(alerta.texto) + "</span>";
        }).join("") + "</div>";
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-26
     * Proposito: muestra la configuracion de paquetes sin permitir captura antes de aplicar el esquema autorizado.
     * Impacto: Catalogo ERP; separa receta de paquete de Ventas y Almacen/Inventario.
     */
    function renderPaquetes(paquetesInfo) {
        var estado = document.getElementById("catalogo_paquetes_estado");
        var lista = document.getElementById("catalogo_paquetes_lista");
        if (!estado || !lista) {
            return;
        }
        var paquetes = paquetesInfo.paquetes || [];
        actualizarGuiaSkuPaquete(paquetes);
        actualizarSelectSkuPaquete(paquetes, null);
        if (paquetesInfo.esquema_disponible === false) {
            var faltantes = paquetesInfo.tablas_faltantes || [];
            mostrarFormularioPaquete(false);
            estado.className = "alert alert-light-warning mb-6";
            estado.innerHTML = "<div class=\"fw-bold mb-1\">" + escapeHtml(paquetesInfo.mensaje || "Paquetes configurables pendientes") + "</div>" +
                "<div class=\"text-muted fs-8\">No se muestran formularios hasta aplicar el DDL con respaldo externo y autorizacion.</div>" +
                (faltantes.length ? "<div class=\"mt-3\"><span class=\"fw-semibold\">Pendiente:</span> " + escapeHtml(faltantes.join(", ")) + "</div>" : "");
            lista.innerHTML = "";
            return;
        }
        if (!paquetes.length) {
            mostrarFormularioPaquete(true);
            estado.className = "alert alert-light-primary mb-6";
            estado.textContent = "Este producto todavia no tiene receta de paquete configurada.";
            lista.innerHTML = "";
            return;
        }

        mostrarFormularioPaquete(true);
        var puedeEditar = !!document.getElementById("catalogo_form_paquete");
        estado.className = "alert alert-light-success mb-6";
        estado.textContent = "Recetas de paquete disponibles para consulta.";
        lista.innerHTML = paquetes.map(function (paquete) {
            var componentes = paquete.componentes || [];
            var grupos = paquete.grupos || [];
            return "<div class=\"border rounded p-5 mb-5\">" +
                "<div class=\"d-flex flex-column flex-lg-row justify-content-between gap-3 mb-4\">" +
                    "<div><div class=\"fw-bold\">" + escapeHtml(paquete.sku || "") + "</div><div class=\"text-muted fs-7\">" + escapeHtml(paquete.nombre_sku || "") + "</div></div>" +
                    "<div class=\"d-flex flex-wrap gap-2 align-items-center\"><span class=\"badge badge-light-primary\">" + escapeHtml(paquete.tipo_paquete || "") + "</span>" +
                    "<span class=\"badge badge-light-info\">" + escapeHtml(paquete.modo_disponibilidad || "") + "</span>" +
                    "<span class=\"badge badge-light-" + (paquete.estatus === "activo" ? "success" : "secondary") + "\">" + escapeHtml(paquete.estatus || "") + "</span>" +
                    (puedeEditar ? "<button type=\"button\" class=\"btn btn-sm btn-icon btn-light-primary\" title=\"Editar paquete\" data-editar-paquete=\"" + escapeAttr(paquete.id_paquete) + "\"><i class=\"bi bi-pencil-square\"></i></button>" +
                    "<button type=\"button\" class=\"btn btn-sm btn-icon btn-light-info\" title=\"Nuevo grupo\" data-nuevo-grupo-paquete=\"" + escapeAttr(paquete.id_paquete) + "\"><i class=\"bi bi-plus-square\"></i></button>" +
                    (paquete.estatus === "activo" || paquete.estatus === "borrador" ? "<button type=\"button\" class=\"btn btn-sm btn-icon btn-light-danger\" title=\"Eliminar paquete de la vista\" data-desactivar-paquete=\"" + escapeAttr(paquete.id_paquete) + "\"><i class=\"bi bi-trash\"></i></button>" : "") : "") + "</div>" +
                "</div>" +
                "<div class=\"row g-5\">" +
                    "<div class=\"col-lg-5\"><div class=\"fw-semibold mb-3\">Componentes fijos</div>" +
                    (componentes.length ? "<div class=\"table-responsive\"><table class=\"table table-row-dashed gy-3\"><tbody>" +
                        componentes.map(function (componente) {
                            return "<tr><td><div class=\"fw-semibold\">" + escapeHtml(componente.sku || "") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(componente.nombre_sku || "") + "</div></td>" +
                                "<td class=\"text-end\">" + escapeHtml(componente.cantidad || "0") + " " + escapeHtml(componente.unidad || "") + "</td></tr>";
                        }).join("") + "</tbody></table></div>" : "<div class=\"text-muted fs-7\">Sin componentes fijos.</div>") + "</div>" +
                    "<div class=\"col-lg-7\"><div class=\"fw-semibold mb-3\">Grupos configurables</div>" +
                    (grupos.length ? grupos.map(function (grupo) { return renderGrupoPaquete(grupo, puedeEditar); }).join("") : "<div class=\"text-muted fs-7\">Sin grupos de seleccion.</div>") + "</div>" +
                "</div>" +
                (paquete.observaciones ? "<div class=\"text-muted fs-8 mt-4\">" + escapeHtml(paquete.observaciones) + "</div>" : "") +
            "</div>";
        }).join("");
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-30
     * Proposito: aclara que cada paquete vendible requiere un SKU paquete propio y detecta cuando faltan SKUs candidatos.
     * Impacto: Catalogo ERP; evita usar el SKU fisico base como si fuera multiples recetas comerciales.
     * Contrato: usa detalleActual.skus y paquetes consultados; no modifica datos ni otros modulos.
     */
    function actualizarGuiaSkuPaquete(paquetes) {
        var guia = document.getElementById("catalogo_paquetes_guia_sku");
        if (!guia) {
            return;
        }
        var skus = detalleActual.skus || [];
        var idsConReceta = {};
        (paquetes || []).forEach(function (paquete) {
            idsConReceta[String(paquete.id_sku_paquete)] = true;
        });
        var skusActivos = skus.filter(function (sku) {
            return !esEstatusArchivado(sku.estatus);
        });
        var disponibles = skusActivos.filter(function (sku) {
            return !idsConReceta[String(sku.id_sku)];
        });
        var extra = "";
        if (!skusActivos.length) {
            extra = "Este producto no tiene SKUs activos. Crea primero un SKU de paquete o un SKU base antes de definir recetas.";
        } else if (skusActivos.length === 1 && paquetes.length) {
            extra = "Este producto solo tiene un SKU activo y ya tiene receta. Para vender otra version del paquete, crea otro SKU de paquete y despues define su receta.";
        } else if (!disponibles.length && paquetes.length) {
            extra = "Todos los SKUs activos de este producto ya tienen receta. Crea un SKU adicional para otro paquete comercial.";
        } else if (disponibles.length) {
            extra = "SKUs sin receta disponibles para nuevo paquete: " + disponibles.map(function (sku) { return sku.sku; }).join(", ") + ".";
        }
        guia.setAttribute("data-paquete-extra", extra);
        var textoExtra = guia.querySelector("[data-paquete-guia-extra]");
        if (!textoExtra) {
            textoExtra = document.createElement("div");
            textoExtra.className = "text-muted fs-8";
            textoExtra.setAttribute("data-paquete-guia-extra", "1");
            guia.appendChild(textoExtra);
        }
        textoExtra.textContent = extra;
        textoExtra.classList.toggle("d-none", !extra);
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-30
     * Proposito: marca en el selector los SKUs que ya tienen receta para evitar sobrescrituras accidentales.
     * Impacto: Catalogo ERP; separa crear receta nueva de editar receta existente.
     * Contrato: al editar permite el SKU de la receta actual; al crear deshabilita SKUs ya usados.
     */
    function actualizarSelectSkuPaquete(paquetes, idPaqueteActual) {
        var select = document.getElementById("catalogo_paquete_sku");
        if (!select) {
            return;
        }
        var usados = {};
        (paquetes || []).forEach(function (paquete) {
            usados[String(paquete.id_sku_paquete)] = paquete;
        });
        Array.prototype.slice.call(select.options).forEach(function (option) {
            if (!option.value) {
                return;
            }
            if (!option.dataset.labelOriginal) {
                option.dataset.labelOriginal = option.textContent;
            }
            var paquete = usados[String(option.value)];
            var usadoPorOtro = paquete && String(paquete.id_paquete) !== String(idPaqueteActual || "");
            option.disabled = !!usadoPorOtro;
            option.textContent = option.dataset.labelOriginal + (usadoPorOtro ? " (receta existente)" : "");
        });
        if (select.selectedOptions.length && select.selectedOptions[0].disabled) {
            var disponible = Array.prototype.slice.call(select.options).find(function (option) {
                return option.value && !option.disabled;
            });
            select.value = disponible ? disponible.value : "";
        }
        activarBuscadorSelect(select);
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-30
     * Proposito: selecciona una unidad practica para SKUs paquete sin imponer reglas de unidad por producto.
     * Impacto: UI de Catalogo ERP; acelera alta inline de paquetes.
     * Contrato: prefiere unidad pieza/pza si existe; si no, deja la primera unidad disponible.
     */
    function seleccionarUnidadPaqueteDefault() {
        var select = document.getElementById("catalogo_paquete_sku_unidad_inline");
        if (!select || !select.options.length) {
            return;
        }
        var preferida = (catalogosDisponibles.unidades || []).find(function (unidad) {
            var abreviatura = String(unidad.abreviatura || "").toLowerCase();
            var nombre = String(unidad.nombre || "").toLowerCase();
            return ["pza", "pz", "pieza"].includes(abreviatura) || nombre === "pieza" || nombre === "piezas";
        });
        if (preferida) {
            select.value = String(preferida.id_unidad);
        }
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-30
     * Proposito: prepara el alta inline de SKU paquete con los SKUs fisicos del producto abierto.
     * Impacto: Catalogo ERP; permite crear paquetes sin salir de la pestana Paquetes.
     * Contrato: el componente base se toma de los SKUs vigentes del producto actual.
     */
    function actualizarFormularioSkuPaqueteInline(skus) {
        var form = document.getElementById("catalogo_form_paquete_sku_inline");
        var componenteSelect = document.getElementById("catalogo_paquete_sku_componente_base");
        if (!form || !componenteSelect) {
            return;
        }
        setValor(form, "id_producto_erp", productoActualId);
        var candidatos = (skus || []).filter(function (sku) {
            return !esEstatusArchivado(sku.estatus) && !["kit", "servicio", "cargo"].includes(String(sku.tipo_inventario || ""));
        });
        llenarSelect("catalogo_paquete_sku_componente_base", candidatos, "id_sku", etiquetaSku, false);
        seleccionarUnidadPaqueteDefault();
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-30
     * Proposito: enfoca el alta inline de SKU paquete desde la guia operativa de paquetes.
     * Impacto: UI de Catalogo ERP; evita sacar al usuario a la pestana SKU durante armado de paquetes.
     */
    function prepararNuevoSkuPaquete() {
        var form = document.getElementById("catalogo_form_paquete_sku_inline");
        if (form) {
            form.scrollIntoView({behavior: "smooth", block: "start"});
            var skuInput = form.querySelector("[name='sku']");
            if (skuInput) {
                skuInput.focus();
            }
        }
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-30
     * Proposito: crea un SKU paquete desde la pestana Paquetes y lo prepara como receta del producto actual.
     * Impacto: Catalogo ERP; mantiene el flujo de paquetes en una sola pantalla sin duplicar logica de alta de SKU.
     * Contrato: usa /catalogoerp/agregar_sku; agrega como componente fijo el SKU fisico seleccionado por el operador.
     */
    function guardarSkuPaqueteInline(event) {
        event.preventDefault();
        var form = event.currentTarget;
        var errorBox = document.getElementById("catalogo_paquetes_error");
        var boton = form.querySelector("[type='submit']");
        var idComponenteBase = valorCampo(form, "id_sku_componente_base");
        var cantidadBase = parseFloat(valorCampo(form, "cantidad_componente_base") || "1");
        var componenteBase = (detalleActual.skus || []).find(function (sku) {
            return String(sku.id_sku) === String(idComponenteBase);
        });
        if (!componenteBase) {
            mostrarError(errorBox, new Error("Selecciona el SKU fisico que sera componente base del paquete."));
            return;
        }
        if (!(cantidadBase > 0)) {
            mostrarError(errorBox, new Error("La cantidad base del componente debe ser mayor a cero."));
            return;
        }
        boton.disabled = true;
        errorBox.classList.add("d-none");
        var data = {
            id_producto_erp: productoActualId,
            sku: valorCampo(form, "sku"),
            nombre_sku: valorCampo(form, "nombre_sku"),
            id_unidad_base: valorCampo(form, "id_unidad_base"),
            factor_unidad_base: 1,
            tipo_inventario: "kit",
            estatus: "borrador",
            precio: 0,
            costo_referencia: 0,
            moneda: "MXN",
            stock_minimo: 0,
            punto_reorden: 0,
            estrategia_salida: "FIFO"
        };
        request("/catalogoerp/agregar_sku", data).then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje);
            }
            var unidad = (catalogosDisponibles.unidades || []).find(function (item) {
                return String(item.id_unidad) === String(data.id_unidad_base);
            }) || {};
            var nuevoSku = {
                id_sku: response.depurar.id_sku,
                sku: data.sku,
                nombre: data.nombre_sku,
                estatus: "borrador",
                tipo_inventario: "kit",
                id_unidad_base: data.id_unidad_base,
                unidad: unidad.nombre || "",
                abreviatura: unidad.abreviatura || ""
            };
            detalleActual.skus = (detalleActual.skus || []).concat([nuevoSku]);
            llenarSelect("catalogo_paquete_sku", detalleActual.skus || [], "id_sku", etiquetaSku, false);
            actualizarFormularioSkuPaqueteInline(detalleActual.skus || []);
            actualizarSelectSkuPaquete(detalleActual.paquetes && detalleActual.paquetes.paquetes ? detalleActual.paquetes.paquetes : [], null);
            var paqueteForm = document.getElementById("catalogo_form_paquete");
            if (paqueteForm) {
                setValor(paqueteForm, "id_sku_paquete", nuevoSku.id_sku);
            }
            if (!componentesPaqueteForm.some(function (item) { return String(item.id_sku) === String(componenteBase.id_sku); })) {
                componentesPaqueteForm.push({
                    id_sku: componenteBase.id_sku,
                    sku: componenteBase.sku || "",
                    nombre: componenteBase.nombre || "",
                    id_unidad: componenteBase.id_unidad_base || "",
                    unidad: componenteBase.abreviatura || componenteBase.unidad || "",
                    cantidad: cantidadBase,
                    factor: 1
                });
                renderComponentesPaqueteForm();
            }
            setValor(form, "sku", "");
            setValor(form, "nombre_sku", "");
            Swal.fire({text: "SKU paquete creado y preparado en la receta.", icon: "success", confirmButtonText: "Aceptar"});
        }).catch(function (error) {
            mostrarError(errorBox, error);
        }).finally(function () {
            boton.disabled = false;
        });
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-26
     * Proposito: renderiza grupos de seleccion de paquetes configurables en modo lectura.
     * Impacto: Catalogo ERP; ayuda a revisar recetas antes de habilitar captura operativa.
     */
    function renderGrupoPaquete(grupo, puedeEditar) {
        var opciones = grupo.opciones || [];
        return "<div class=\"border rounded p-4 mb-4\">" +
            "<div class=\"d-flex flex-column flex-md-row justify-content-between gap-2 mb-3\">" +
                "<div><div class=\"fw-semibold\">" + escapeHtml(grupo.nombre || grupo.codigo || "") + "</div>" +
                "<div class=\"text-muted fs-8\">" + escapeHtml(grupo.descripcion || "") + "</div></div>" +
                "<div class=\"d-flex flex-wrap gap-2 align-items-center\"><span class=\"badge badge-light-primary\">Min " + escapeHtml(grupo.min_selecciones || "0") + " / Max " + escapeHtml(grupo.max_selecciones || "0") + "</span>" +
                (puedeEditar ? "<button type=\"button\" class=\"btn btn-sm btn-icon btn-light-primary\" title=\"Editar grupo\" data-editar-grupo-paquete=\"" + escapeAttr(grupo.id_grupo) + "\"><i class=\"bi bi-pencil-square\"></i></button>" +
                "<button type=\"button\" class=\"btn btn-sm btn-icon btn-light-info\" title=\"Nueva opcion\" data-nueva-opcion-grupo=\"" + escapeAttr(grupo.id_grupo) + "\"><i class=\"bi bi-plus-square\"></i></button>" +
                "<button type=\"button\" class=\"btn btn-sm btn-icon btn-light-danger\" title=\"Eliminar grupo de la vista\" data-desactivar-grupo-paquete=\"" + escapeAttr(grupo.id_grupo) + "\"><i class=\"bi bi-trash\"></i></button>" : "") + "</div>" +
            "</div>" +
            (opciones.length ? "<div class=\"d-flex flex-column gap-2\">" + opciones.map(function (opcion) {
                return "<div class=\"d-flex justify-content-between gap-3\">" +
                    "<span><span class=\"fw-semibold\">" + escapeHtml(opcion.sku || "") + "</span> <span class=\"text-muted fs-8\">" + escapeHtml(opcion.nombre_sku || "") + "</span></span>" +
                    "<span class=\"text-nowrap\">" + escapeHtml(opcion.cantidad_default || "0") + " " + escapeHtml(opcion.unidad || "") + "</span>" +
                    (puedeEditar ? "<span class=\"text-nowrap\"><button type=\"button\" class=\"btn btn-sm btn-icon btn-light-primary\" title=\"Editar opcion\" data-editar-opcion-paquete=\"" + escapeAttr(opcion.id_opcion) + "\" data-grupo=\"" + escapeAttr(grupo.id_grupo) + "\"><i class=\"bi bi-pencil-square\"></i></button>" +
                    "<button type=\"button\" class=\"btn btn-sm btn-icon btn-light-danger ms-1\" title=\"Eliminar opcion de la vista\" data-desactivar-opcion-paquete=\"" + escapeAttr(opcion.id_opcion) + "\"><i class=\"bi bi-trash\"></i></button></span>" : "") +
                "</div>";
            }).join("") + "</div>" : "<div class=\"text-muted fs-8\">Sin opciones configuradas.</div>") +
        "</div>";
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-26
     * Proposito: habilita u oculta captura de paquete simple segun disponibilidad del esquema.
     * Impacto: Catalogo ERP; evita formularios operativos cuando la migracion aun no existe.
     */
    function mostrarFormularioPaquete(mostrar) {
        var form = document.getElementById("catalogo_form_paquete");
        if (!form) {
            return;
        }
        form.classList.toggle("d-none", !mostrar);
        document.querySelectorAll("[data-paquete-form]").forEach(function (item) {
            item.classList.toggle("d-none", !mostrar);
        });
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-26
     * Proposito: limpia borrador de receta simple al cambiar de producto o despues de guardar.
     * Impacto: Catalogo ERP; evita duplicar componentes al abrir/crear paquetes consecutivos.
     */
    function limpiarFormularioPaquete() {
        var form = document.getElementById("catalogo_form_paquete");
        if (form) {
            form.reset();
            setValor(form, "id_paquete", "");
        }
        componentesPaqueteForm = [];
        renderComponentesPaqueteForm();
        var paquetes = detalleActual.paquetes && detalleActual.paquetes.paquetes ? detalleActual.paquetes.paquetes : [];
        actualizarSelectSkuPaquete(paquetes, null);
        var resultados = document.getElementById("catalogo_paquete_resultados_sku");
        if (resultados) {
            resultados.innerHTML = "";
        }
        limpiarFormularioPaqueteGrupo();
        limpiarFormularioPaqueteOpcion();
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-26
     * Proposito: prepara formulario para crear/editar grupo configurable de paquete.
     * Impacto: Catalogo ERP; UI de receta configurable sin tocar ventas ni inventario.
     */
    function prepararGrupoPaquete(idPaquete, grupo) {
        var form = document.getElementById("catalogo_form_paquete_grupo");
        if (!form) { return; }
        var formPaquete = document.getElementById("catalogo_form_paquete");
        if (formPaquete && !grupo) {
            setValor(formPaquete, "tipo_paquete", "configurable");
            setChecked(formPaquete, "permite_configuracion_cliente", 1);
        }
        form.reset();
        setValor(form, "id_paquete", idPaquete || "");
        setValor(form, "id_grupo", grupo ? grupo.id_grupo : "");
        setValor(form, "codigo", grupo ? grupo.codigo : "");
        setValor(form, "nombre", grupo ? grupo.nombre : "");
        setValor(form, "min_selecciones", grupo ? grupo.min_selecciones : 1);
        setValor(form, "max_selecciones", grupo ? grupo.max_selecciones : 1);
        setValor(form, "modo_cantidad", grupo ? grupo.modo_cantidad : "cantidad_fija");
        setValor(form, "cantidad_total_grupo", grupo ? grupo.cantidad_total_grupo : "");
        setValor(form, "orden", grupo ? grupo.orden : 0);
        setValor(form, "estatus", grupo ? grupo.estatus : "activo");
        setValor(form, "descripcion", grupo ? grupo.descripcion : "");
        setChecked(form, "obligatorio", !grupo || String(grupo.obligatorio) === "1");
        form.scrollIntoView({behavior: "smooth", block: "start"});
    }

    function limpiarFormularioPaqueteGrupo() {
        var form = document.getElementById("catalogo_form_paquete_grupo");
        if (form) {
            form.reset();
            setValor(form, "id_grupo", "");
            setValor(form, "id_paquete", "");
        }
    }

    function buscarGrupoPaquete(idGrupo) {
        var paquetes = detalleActual.paquetes && detalleActual.paquetes.paquetes ? detalleActual.paquetes.paquetes : [];
        for (var i = 0; i < paquetes.length; i++) {
            var grupos = paquetes[i].grupos || [];
            for (var j = 0; j < grupos.length; j++) {
                if (String(grupos[j].id_grupo) === String(idGrupo)) {
                    return {paquete: paquetes[i], grupo: grupos[j]};
                }
            }
        }
        return null;
    }

    function buscarOpcionPaquete(idOpcion) {
        var infoGrupo;
        var paquetes = detalleActual.paquetes && detalleActual.paquetes.paquetes ? detalleActual.paquetes.paquetes : [];
        for (var i = 0; i < paquetes.length; i++) {
            var grupos = paquetes[i].grupos || [];
            for (var j = 0; j < grupos.length; j++) {
                var opciones = grupos[j].opciones || [];
                for (var k = 0; k < opciones.length; k++) {
                    if (String(opciones[k].id_opcion) === String(idOpcion)) {
                        infoGrupo = {paquete: paquetes[i], grupo: grupos[j], opcion: opciones[k]};
                    }
                }
            }
        }
        return infoGrupo || null;
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-26
     * Proposito: prepara formulario de opcion dentro de un grupo configurable.
     * Impacto: Catalogo ERP; captura alternativas elegibles sin fijar precio final.
     */
    function prepararOpcionPaquete(idGrupo, opcion) {
        var form = document.getElementById("catalogo_form_paquete_opcion");
        if (!form) { return; }
        paqueteGrupoActualId = Number(idGrupo || 0);
        form.reset();
        reiniciarSelectOpcionPaquete();
        if (opcion && opcion.id_sku_opcion) {
            seleccionarSkuOpcionPaquete({
                id_sku: opcion.id_sku_opcion,
                sku: opcion.sku || "",
                nombre: opcion.nombre_sku || ""
            });
        }
        setValor(form, "id_grupo", idGrupo || "");
        setValor(form, "id_opcion", opcion ? opcion.id_opcion : "");
        setValor(form, "cantidad_default", opcion ? opcion.cantidad_default : 1);
        setValor(form, "cantidad_minima", opcion ? opcion.cantidad_minima : "");
        setValor(form, "cantidad_maxima", opcion ? opcion.cantidad_maxima : "");
        setValor(form, "id_unidad", opcion ? opcion.id_unidad : "");
        setValor(form, "factor_conversion", opcion ? opcion.factor_conversion : 1);
        setValor(form, "orden", opcion ? opcion.orden : 0);
        setValor(form, "estatus", opcion ? opcion.estatus : "activo");
        setChecked(form, "permite_cantidad_editable", opcion ? opcion.permite_cantidad_editable : 0);
        form.scrollIntoView({behavior: "smooth", block: "start"});
    }

    function limpiarFormularioPaqueteOpcion() {
        var form = document.getElementById("catalogo_form_paquete_opcion");
        if (form) {
            form.reset();
            setValor(form, "id_opcion", "");
            setValor(form, "id_grupo", "");
            reiniciarSelectOpcionPaquete();
        }
        var input = document.getElementById("catalogo_paquete_opcion_buscar_sku");
        var resultados = document.getElementById("catalogo_paquete_opcion_resultados_sku");
        if (input) {
            input.value = "";
        }
        if (resultados) {
            resultados.innerHTML = "";
        }
        paqueteGrupoActualId = 0;
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-30
     * Proposito: reinicia el selector de SKU opcion para evitar limitar opciones al producto abierto.
     * Impacto: Catalogo ERP; prepara seleccion global de opciones de paquete sin tocar Ventas ni Inventario.
     * Contrato: conserva solo una opcion vacia hasta que el usuario elija un SKU desde busqueda global.
     */
    function reiniciarSelectOpcionPaquete() {
        var select = document.getElementById("catalogo_paquete_opcion_sku");
        if (!select) {
            return;
        }
        select.innerHTML = "<option value=\"\">Selecciona desde busqueda</option>";
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-30
     * Proposito: agrega al select de opcion un SKU encontrado globalmente y lo deja seleccionado.
     * Impacto: Catalogo ERP; permite configurar opciones con SKUs de otros productos.
     */
    function seleccionarSkuOpcionPaquete(item) {
        var select = document.getElementById("catalogo_paquete_opcion_sku");
        if (!select || !item || !item.id_sku) {
            return;
        }
        var id = String(item.id_sku);
        var existente = Array.prototype.slice.call(select.options).find(function (option) {
            return String(option.value) === id;
        });
        if (!existente) {
            var option = document.createElement("option");
            option.value = id;
            option.textContent = (item.sku || "") + " - " + (item.nombre || "");
            select.appendChild(option);
        }
        select.value = id;
        activarBuscadorSelect(select);
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-26
     * Proposito: carga una receta simple existente en el formulario para editar encabezado y componentes fijos.
     * Impacto: Catalogo ERP; mantiene la edicion en Catalogo sin afectar existencias ni ventas.
     */
    function editarPaqueteSimple(idPaquete) {
        var form = document.getElementById("catalogo_form_paquete");
        var paquetes = detalleActual.paquetes && detalleActual.paquetes.paquetes ? detalleActual.paquetes.paquetes : [];
        var paquete = paquetes.find(function (item) {
            return String(item.id_paquete) === String(idPaquete);
        });
        if (!form || !paquete) {
            return;
        }
        setValor(form, "id_paquete", paquete.id_paquete);
        setValor(form, "id_sku_paquete", paquete.id_sku_paquete);
        setValor(form, "tipo_paquete", paquete.tipo_paquete || "simple");
        setValor(form, "modo_disponibilidad", paquete.modo_disponibilidad || "por_componentes");
        setValor(form, "estatus", paquete.estatus || "activo");
        setValor(form, "observaciones", paquete.observaciones || "");
        setChecked(form, "permite_configuracion_cliente", paquete.permite_configuracion_cliente);
        setChecked(form, "requiere_armado_almacen", paquete.requiere_armado_almacen);
        setChecked(form, "permite_desarmar", paquete.permite_desarmar);
        actualizarSelectSkuPaquete(paquetes, paquete.id_paquete);
        componentesPaqueteForm = (paquete.componentes || []).map(function (item) {
            return {
                id_sku: item.id_sku_componente,
                sku: item.sku || "",
                nombre: item.nombre_sku || "",
                id_unidad: item.id_unidad || "",
                unidad: item.unidad || "",
                cantidad: item.cantidad || 1,
                factor: item.factor_conversion || 1
            };
        });
        renderComponentesPaqueteForm();
        var errorBox = document.getElementById("catalogo_paquetes_error");
        if (errorBox) {
            errorBox.classList.add("d-none");
        }
        form.scrollIntoView({behavior: "smooth", block: "start"});
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-26
     * Proposito: elimina visualmente una receta de paquete sin borrar configuracion.
     * Impacto: Catalogo ERP; oculta paquetes inactivos y conserva trazabilidad para recuperacion futura.
     */
    function desactivarPaqueteSimple(idPaquete) {
        Swal.fire({
            text: "El paquete dejara de aparecer en la vista normal. Se conserva historial para una recuperacion futura por administrador.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Eliminar de la vista",
            cancelButtonText: "Cancelar"
        }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }
            request("/catalogoerp/desactivar_paquete", {id_paquete: idPaquete}).then(function (response) {
                if (response.error) {
                    throw new Error(response.mensaje);
                }
                Swal.fire({text: response.mensaje, icon: "success", confirmButtonText: "Aceptar"});
                abrirDetalle(productoActualId, "catalogo_detalle_paquetes");
            }).catch(function (error) {
                mostrarError(document.getElementById("catalogo_paquetes_error"), error);
            });
        });
    }

    function guardarPaqueteGrupo(event) {
        event.preventDefault();
        var form = event.currentTarget;
        enviarFormulario(form, "/catalogoerp/guardar_paquete_grupo", document.getElementById("catalogo_paquetes_error"), function () {
            limpiarFormularioPaqueteGrupo();
            abrirDetalle(productoActualId, "catalogo_detalle_paquetes");
        });
    }

    function guardarPaqueteOpcion(event) {
        event.preventDefault();
        var form = event.currentTarget;
        if (!form.querySelector("[name='id_grupo']").value && paqueteGrupoActualId) {
            setValor(form, "id_grupo", paqueteGrupoActualId);
        }
        enviarFormulario(form, "/catalogoerp/guardar_paquete_opcion", document.getElementById("catalogo_paquetes_error"), function () {
            limpiarFormularioPaqueteOpcion();
            abrirDetalle(productoActualId, "catalogo_detalle_paquetes");
        });
    }

    function desactivarGrupoPaquete(idGrupo) {
        Swal.fire({
            text: "El grupo y sus opciones dejaran de aparecer en esta receta. Se conserva historial para recuperacion futura.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Eliminar grupo",
            cancelButtonText: "Cancelar"
        }).then(function (result) {
            if (!result.isConfirmed) { return; }
            request("/catalogoerp/desactivar_paquete_grupo", {id_grupo: idGrupo}).then(function (response) {
                if (response.error) { throw new Error(response.mensaje); }
                Swal.fire({text: response.mensaje, icon: "success", confirmButtonText: "Aceptar"});
                abrirDetalle(productoActualId, "catalogo_detalle_paquetes");
            }).catch(function (error) {
                mostrarError(document.getElementById("catalogo_paquetes_error"), error);
            });
        });
    }

    function desactivarOpcionPaquete(idOpcion) {
        Swal.fire({
            text: "La opcion dejara de aparecer en el grupo. Se conserva historial para recuperacion futura.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Eliminar opcion",
            cancelButtonText: "Cancelar"
        }).then(function (result) {
            if (!result.isConfirmed) { return; }
            request("/catalogoerp/desactivar_paquete_opcion", {id_opcion: idOpcion}).then(function (response) {
                if (response.error) { throw new Error(response.mensaje); }
                Swal.fire({text: response.mensaje, icon: "success", confirmButtonText: "Aceptar"});
                abrirDetalle(productoActualId, "catalogo_detalle_paquetes");
            }).catch(function (error) {
                mostrarError(document.getElementById("catalogo_paquetes_error"), error);
            });
        });
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-26
     * Proposito: busca SKUs globales para agregarlos como componentes de paquete.
     * Impacto: Catalogo ERP; soporta paquetes con productos de distintas familias.
     */
    function buscarSkuComponentePaquete() {
        var input = document.getElementById("catalogo_paquete_buscar_sku");
        var contenedor = document.getElementById("catalogo_paquete_resultados_sku");
        if (!input || !contenedor) {
            return;
        }
        var termino = input.value.trim();
        if (termino.length < 2) {
            contenedor.innerHTML = "<div class=\"text-muted fs-8\">Escribe al menos dos caracteres.</div>";
            return;
        }
        request("/catalogoerp/paquetes_buscar_skus?q=" + encodeURIComponent(termino) + "&limite=20").then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje);
            }
            var items = response.depurar || [];
            contenedor.innerHTML = items.map(function (item) {
                return "<button type=\"button\" class=\"btn btn-sm btn-light-primary me-2 mb-2\" data-agregar-componente-paquete=\"" + escapeAttr(item.id_sku) + "\" " +
                    "data-sku=\"" + escapeAttr(item.sku) + "\" data-nombre=\"" + escapeAttr(item.nombre) + "\" data-unidad=\"" + escapeAttr(item.abreviatura || "") + "\" data-unidad-id=\"" + escapeAttr(item.id_unidad || "") + "\">" +
                    escapeHtml(item.sku) + " - " + escapeHtml(item.nombre) + "</button>";
            }).join("") || "<div class=\"text-muted fs-8\">Sin coincidencias.</div>";
        }).catch(function (error) {
            contenedor.innerHTML = "<div class=\"text-danger fs-8\">" + escapeHtml(error.message || String(error)) + "</div>";
        });
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-30
     * Proposito: busca SKUs globales para usarlos como opciones dentro de un grupo configurable.
     * Impacto: Catalogo ERP; corrige el flujo que antes solo mostraba SKUs del producto abierto.
     */
    function buscarSkuOpcionPaquete() {
        var input = document.getElementById("catalogo_paquete_opcion_buscar_sku");
        var contenedor = document.getElementById("catalogo_paquete_opcion_resultados_sku");
        if (!input || !contenedor) {
            return;
        }
        var termino = input.value.trim();
        if (termino.length < 2) {
            contenedor.innerHTML = "<div class=\"text-muted fs-8\">Escribe al menos dos caracteres.</div>";
            return;
        }
        request("/catalogoerp/paquetes_buscar_skus?q=" + encodeURIComponent(termino) + "&limite=20").then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje);
            }
            var items = response.depurar || [];
            contenedor.innerHTML = items.map(function (item) {
                return "<button type=\"button\" class=\"btn btn-sm btn-light-primary me-2 mb-2\" data-seleccionar-opcion-paquete=\"" + escapeAttr(item.id_sku) + "\" " +
                    "data-sku=\"" + escapeAttr(item.sku) + "\" data-nombre=\"" + escapeAttr(item.nombre) + "\">" +
                    escapeHtml(item.sku) + " - " + escapeHtml(item.nombre) + "</button>";
            }).join("") || "<div class=\"text-muted fs-8\">Sin coincidencias.</div>";
        }).catch(function (error) {
            contenedor.innerHTML = "<div class=\"text-danger fs-8\">" + escapeHtml(error.message || String(error)) + "</div>";
        });
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-26
     * Proposito: agrega un SKU encontrado al borrador de componentes del paquete simple.
     * Impacto: Catalogo ERP; evita componentes duplicados dentro de la misma receta.
     */
    function agregarComponentePaquete(boton) {
        var idSku = Number(boton.getAttribute("data-agregar-componente-paquete") || 0);
        if (!idSku || componentesPaqueteForm.some(function (item) { return Number(item.id_sku) === idSku; })) {
            return;
        }
        componentesPaqueteForm.push({
            id_sku: idSku,
            sku: boton.getAttribute("data-sku") || "",
            nombre: boton.getAttribute("data-nombre") || "",
            id_unidad: boton.getAttribute("data-unidad-id") || "",
            unidad: boton.getAttribute("data-unidad") || "",
            cantidad: 1,
            factor: 1
        });
        renderComponentesPaqueteForm();
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-26
     * Proposito: renderiza componentes capturados para paquete simple con cantidades editables.
     * Impacto: Catalogo ERP; prepara payload consistente para backend.
     */
    function renderComponentesPaqueteForm() {
        var lista = document.getElementById("catalogo_paquete_componentes_lista");
        if (!lista) {
            return;
        }
        lista.innerHTML = componentesPaqueteForm.map(function (item, index) {
            return "<tr data-componente-paquete-row=\"" + escapeAttr(index) + "\">" +
                "<td><div class=\"fw-semibold\">" + escapeHtml(item.sku) + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.nombre) + "</div></td>" +
                "<td><input class=\"form-control form-control-sm\" type=\"number\" min=\"0.000001\" step=\"0.000001\" value=\"" + escapeAttr(item.cantidad) + "\" data-componente-cantidad></td>" +
                "<td>" + escapeHtml(item.unidad || "") + "</td>" +
                "<td><input class=\"form-control form-control-sm\" type=\"number\" min=\"0.000001\" step=\"0.000001\" value=\"" + escapeAttr(item.factor) + "\" data-componente-factor></td>" +
                "<td class=\"text-end\"><button type=\"button\" class=\"btn btn-sm btn-icon btn-light-danger\" title=\"Eliminar componente de la receta\" data-quitar-componente-paquete=\"" + escapeAttr(index) + "\"><i class=\"bi bi-trash\"></i></button></td>" +
            "</tr>";
        }).join("") || "<tr><td colspan=\"5\" class=\"text-center text-muted py-5\">Sin componentes agregados</td></tr>";
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-26
     * Proposito: guarda receta simple de paquete enviando arreglos PHP reales de componentes.
     * Impacto: Catalogo ERP; no afecta existencias ni ventas.
     */
    function guardarPaqueteSimple(event) {
        event.preventDefault();
        var form = event.currentTarget;
        var errorBox = document.getElementById("catalogo_paquetes_error");
        var button = form.querySelector("[type='submit']");
        var params = new URLSearchParams();
        errorBox.classList.add("d-none");
        new FormData(form).forEach(function (value, key) {
            params.append(key, value);
        });
        var rows = document.querySelectorAll("[data-componente-paquete-row]");
        rows.forEach(function (row) {
            var index = Number(row.getAttribute("data-componente-paquete-row"));
            var item = componentesPaqueteForm[index];
            if (!item) {
                return;
            }
            params.append("componente_sku[]", item.id_sku);
            params.append("componente_unidad[]", item.id_unidad || "");
            params.append("componente_cantidad[]", row.querySelector("[data-componente-cantidad]").value || "0");
            params.append("componente_factor[]", row.querySelector("[data-componente-factor]").value || "1");
        });
        button.disabled = true;
        fetch("/catalogoerp/guardar_paquete_simple", {
            method: "POST",
            headers: {"Content-Type": "application/x-www-form-urlencoded; charset=UTF-8"},
            body: params.toString(),
            credentials: "same-origin"
        }).then(function (response) {
            return response.json();
        }).then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje);
            }
            Swal.fire({text: response.mensaje, icon: "success", confirmButtonText: "Aceptar"});
            limpiarFormularioPaquete();
            abrirDetalle(productoActualId, "catalogo_detalle_paquetes");
        }).catch(function (error) {
            mostrarError(errorBox, error);
        }).finally(function () {
            button.disabled = false;
        });
    }

    function renderVariantes(skus, variantes) {
        var atributos = variantes.atributos || [];
        var valores = variantes.valores || {};
        var encabezado = document.getElementById("catalogo_variantes_encabezado");
        var lista = document.getElementById("catalogo_variantes_lista");
        var estado = document.getElementById("catalogo_variantes_estado");
        encabezado.innerHTML = "<tr class=\"text-muted fw-bold fs-7 text-uppercase\"><th>SKU</th><th>Nombre</th>" +
            atributos.map(function (atributo) {
                return "<th><span class=\"d-inline-flex align-items-center gap-2\">" + escapeHtml(atributo.nombre) +
                    "<button type=\"button\" class=\"btn btn-sm btn-light-primary\" title=\"Editar valores\" data-editar-variante=\"" +
                    escapeHtml(atributo.id_atributo_erp) + "\"><i class=\"bi bi-pencil-square\"></i> Editar</button></span></th>";
            }).join("") + "</tr>";
        lista.innerHTML = skus.map(function (sku) {
            return "<tr><td class=\"fw-bold\">" + escapeHtml(sku.sku) + "</td><td>" + escapeHtml(sku.nombre) + "</td>" +
                atributos.map(function (atributo) {
                    var valor = valores[sku.id_sku] && valores[sku.id_sku][atributo.id_atributo_erp] ? valores[sku.id_sku][atributo.id_atributo_erp] : "";
                    return "<td>" + mostrarValorAtributo(valor, atributo) + "</td>";
                }).join("") + "</tr>";
        }).join("") || "<tr><td colspan=\"2\" class=\"text-center text-muted py-7\">Sin SKU registrados</td></tr>";

        var faltantes = 0;
        skus.forEach(function (sku) {
            atributos.forEach(function (atributo) {
                if (!valores[sku.id_sku] || !valores[sku.id_sku][atributo.id_atributo_erp]) {
                    faltantes++;
                }
            });
        });
        var duplicadas = variantes.duplicadas || [];
        if (!atributos.length) {
            estado.className = "alert alert-light-warning mb-6";
            estado.textContent = "Este producto agrupa SKU, pero todavía no tiene atributos diferenciadores definidos.";
        } else if (duplicadas.length) {
            estado.className = "alert alert-light-danger mb-6";
            estado.textContent = "Combinaciones repetidas detectadas en: " + duplicadas.join(", ");
        } else if (faltantes > 0) {
            estado.className = "alert alert-light-warning mb-6";
            estado.textContent = "Faltan " + faltantes + " valores para completar la matriz de variantes.";
        } else {
            estado.className = "alert alert-light-success mb-6";
            estado.textContent = "Las combinaciones de variantes están completas y son únicas.";
        }
    }

    function prepararVariante() {
        var form = document.getElementById("catalogo_form_variantes");
        var atributoId = form.querySelector("[name='id_atributo_erp']").value;
        var nombreNuevo = form.querySelector("[name='nuevo_atributo']").value.trim();
        if (nombreNuevo) {
            atributoId = "";
            form.querySelector("[name='id_atributo_erp']").value = "";
        }
        var atributo = (catalogosDisponibles.atributos || []).find(function (item) { return String(item.id_atributo_erp) === String(atributoId); });
        var titulo = nombreNuevo || (atributo ? atributo.nombre : "");
        var error = document.getElementById("catalogo_variantes_error");
        if (!titulo) {
            mostrarError(error, new Error("Selecciona o escribe un atributo diferenciador"));
            return;
        }
        error.classList.add("d-none");
        var valores = detalleActual.variantes && detalleActual.variantes.valores ? detalleActual.variantes.valores : {};
        document.getElementById("catalogo_variante_valores").innerHTML =
            "<div class=\"fw-bold mb-4\">" + escapeHtml(titulo) + "</div><div class=\"row g-4\">" +
            (detalleActual.skus || []).map(function (sku) {
                var actual = atributoId && valores[sku.id_sku] ? valores[sku.id_sku][atributoId] || "" : "";
                return "<div class=\"col-md-6\"><label class=\"form-label\">" + escapeHtml(sku.sku + " - " + sku.nombre) +
                    "</label>" + campoValorAtributo(sku.id_sku, actual, atributo || {tipo_dato: "texto"}) + "</div>";
            }).join("") + "</div>";
        document.getElementById("catalogo_variante_guardar_contenedor").classList.remove("d-none");
    }

    function mostrarValorAtributo(valor, atributo) {
        if (!valor) {
            return "<span class=\"text-muted\">Pendiente</span>";
        }
        if (atributo.tipo_dato === "color") {
            return "<span class=\"d-inline-flex align-items-center gap-2\"><span class=\"border rounded\" style=\"width:24px;height:24px;background:" +
                escapeHtml(valor) + "\"></span><span>" + escapeHtml(valor) + "</span></span>";
        }
        if (atributo.tipo_dato === "booleano") {
            return String(valor) === "1" ? "Sí" : "No";
        }
        return escapeHtml(valor) + (atributo.unidad ? " " + escapeHtml(atributo.unidad) : "");
    }

    function campoValorAtributo(idSku, actual, atributo) {
        var nombre = "valores[" + escapeHtml(idSku) + "]";
        var tipo = atributo.tipo_dato || "texto";
        if (tipo === "color") {
            var color = /^#[0-9a-f]{6}$/i.test(actual) ? actual : "#000000";
            return "<div class=\"input-group\"><input type=\"color\" class=\"form-control form-control-color\" value=\"" + escapeHtml(color) +
                "\" data-color-selector><input class=\"form-control\" name=\"" + nombre + "\" value=\"" + escapeHtml(actual) +
                "\" data-color-text maxlength=\"30\" placeholder=\"#RRGGBB o rgb(r,g,b)\"></div>";
        }
        if (tipo === "booleano") {
            return "<select class=\"form-select\" name=\"" + nombre + "\"><option value=\"\">Pendiente</option><option value=\"1\"" +
                (String(actual) === "1" ? " selected" : "") + ">Sí</option><option value=\"0\"" + (String(actual) === "0" ? " selected" : "") + ">No</option></select>";
        }
        if (tipo === "lista") {
            return "<select class=\"form-select\" name=\"" + nombre + "\"><option value=\"\">Seleccionar</option>" +
                opcionesAtributo(atributo).map(function (opcion) {
                    return "<option value=\"" + escapeHtml(opcion) + "\"" + (String(actual) === String(opcion) ? " selected" : "") + ">" + escapeHtml(opcion) + "</option>";
                }).join("") + "</select>";
        }
        var input = "<input class=\"form-control\" type=\"" + (tipo === "numero" ? "number" : tipo === "fecha" ? "date" : "text") +
            "\" name=\"" + nombre + "\" value=\"" + escapeHtml(actual) + "\"" + (tipo === "numero" ? " step=\"any\"" : " maxlength=\"500\"") + ">";
        return atributo.unidad ? "<div class=\"input-group\">" + input + "<span class=\"input-group-text\">" + escapeHtml(atributo.unidad) + "</span></div>" : input;
    }

    function opcionesAtributo(atributo) {
        try {
            var configuracion = JSON.parse(atributo.configuracion_json || "{}");
            return Array.isArray(configuracion.opciones) ? configuracion.opciones : [];
        } catch (e) {
            return [];
        }
    }

    function guardarEdicion(event) {
        event.preventDefault();
        enviarFormulario(event.currentTarget, "/catalogoerp/actualizar", document.getElementById("catalogo_detalle_error"), function () {
            cargar();
            abrirDetalle(productoActualId);
        });
    }

    function guardarSku(event) {
        event.preventDefault();
        var currentForm = event.currentTarget;
        var idSku = currentForm.querySelector("[name='id_sku']").value;
        var skuActual = currentForm.querySelector("[name='sku']").value.trim();
        var codigoActual = currentForm.querySelector("[name='codigo_barras']").value.trim();
        var motivo = currentForm.querySelector("[name='motivo_cambio_identidad']");
        if (idSku && motivo && (skuActual !== (currentForm.dataset.skuOriginal || "") || codigoActual !== (currentForm.dataset.codigoBarrasOriginal || "")) && !motivo.value.trim()) {
            mostrarError(document.getElementById("catalogo_detalle_error"), new Error("Indica el motivo del cambio de SKU o código de barras"));
            motivo.focus();
            return;
        }
        if (!validarFactorUnidadBase(currentForm, document.getElementById("catalogo_detalle_error"))) {
            return;
        }
        if (!validarFormularioGranel(currentForm, document.getElementById("catalogo_detalle_error"))) {
            return;
        }
        var url = idSku ? "/catalogoerp/actualizar_sku" : "/catalogoerp/agregar_sku";
        enviarFormulario(currentForm, url, document.getElementById("catalogo_detalle_error"), function () {
            limpiarFormularioSku();
            cargar();
            abrirDetalle(productoActualId);
        });
    }

    function guardarImagen(event) {
        event.preventDefault();
        var currentForm = event.currentTarget;
        enviarFormulario(currentForm, "/catalogoerp/guardar_imagen", document.getElementById("catalogo_imagenes_error"), function () {
            limpiarFormularioImagen();
            abrirDetalle(productoActualId);
        });
    }

    function desactivarImagen(idImagen) {
        request("/catalogoerp/desactivar_imagen", {
            id_producto_erp: productoActualId,
            id_imagen_erp: idImagen
        }).then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje);
            }
            abrirDetalle(productoActualId);
        }).catch(function (error) {
            mostrarError(document.getElementById("catalogo_imagenes_error"), error);
        });
    }

    function guardarSkuProveedor(event) {
        event.preventDefault();
        var currentForm = event.currentTarget;
        if (!validarFormularioSkuProveedor(currentForm, document.getElementById("catalogo_proveedor_error"))) {
            return;
        }
        enviarFormulario(currentForm, "/catalogoerp/guardar_sku_proveedor", document.getElementById("catalogo_proveedor_error"), function () {
            limpiarFormularioSkuProveedor();
            abrirDetalle(productoActualId);
        });
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-25
     * Proposito: asigna proveedor principal en SKUs no ambiguos del producto abierto.
     * Impacto: Catalogo ERP; corrige pendientes de principal sin elegir entre proveedores multiples.
     */
    function marcarProveedorUnicoPreferido() {
        var errorBox = document.getElementById("catalogo_proveedor_error");
        if (!productoActualId) {
            mostrarError(errorBox, new Error("Abre un producto para actualizar proveedores"));
            return;
        }
        Swal.fire({
            text: "Se marcara como principal el unico proveedor activo de cada SKU no ambiguo.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Aplicar",
            cancelButtonText: "Cancelar"
        }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }
            request("/catalogoerp/proveedor_unico_preferido", {
                id_producto_erp: productoActualId
            }).then(function (response) {
                if (response.error) {
                    throw new Error(response.mensaje);
                }
                Swal.fire({text: response.mensaje, icon: "success", confirmButtonText: "Aceptar"});
                abrirDetalle(productoActualId, "catalogo_detalle_proveedores");
                cargar();
            }).catch(function (error) {
                mostrarError(errorBox, error);
            });
        });
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-25
     * Proposito: restablece el formulario SKU-proveedor a modo alta despues de guardar o cancelar edicion.
     * Impacto: Catalogo ERP; evita duplicados accidentales al sanear proveedores migrados.
     */
    function limpiarFormularioSkuProveedor() {
        var form = document.getElementById("catalogo_form_sku_proveedor");
        if (!form) {
            return;
        }
        form.reset();
        setValor(form, "id_sku_proveedor", "");
        setValor(form, "factor_conversion", "1");
        setValor(form, "cantidad_minima", "1");
        setValor(form, "costo_ultimo", "0");
        setValor(form, "dias_entrega", "0");
        setValor(form, "estatus", "activo");
        setChecked(form, "es_preferido", 0);
        actualizarModoSkuProveedor(false);
        actualizarPreviewSkuProveedor(form);
    }

    function actualizarModoSkuProveedor(editando) {
        var titulo = document.getElementById("catalogo_sku_proveedor_form_titulo");
        var boton = document.getElementById("catalogo_sku_proveedor_guardar");
        var cancelar = document.getElementById("catalogo_cancelar_edicion_sku_proveedor");
        if (titulo) {
            titulo.textContent = editando ? "Editar proveedor vinculado" : "Vincular SKU con proveedor";
        }
        if (boton) {
            boton.innerHTML = editando ? "<i class=\"bi bi-check-lg\"></i> Guardar cambios" : "<i class=\"bi bi-link-45deg\"></i> Guardar vinculo";
        }
        if (cancelar) {
            cancelar.classList.toggle("d-none", !editando);
        }
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-25
     * Proposito: precarga una relacion proveedor-SKU existente para editar unidad, factor o proveedor preferido.
     * Impacto: Catalogo ERP; acelera saneamiento de productos migrados sin duplicar relaciones proveedor.
     */
    function editarSkuProveedor(idRelacion) {
        var form = document.getElementById("catalogo_form_sku_proveedor");
        var relacion = (detalleActual.proveedores || []).find(function (item) {
            return String(item.id_sku_proveedor) === String(idRelacion);
        });
        if (!form || !relacion) {
            return;
        }
        setValor(form, "id_sku_proveedor", relacion.id_sku_proveedor);
        setValor(form, "id_sku", relacion.id_sku);
        setValor(form, "id_proveedor", relacion.id_proveedor);
        setValor(form, "sku_proveedor", relacion.sku_proveedor || "");
        setValor(form, "id_unidad_compra", relacion.id_unidad_compra);
        setValor(form, "factor_conversion", relacion.factor_conversion || "1");
        setValor(form, "cantidad_minima", relacion.cantidad_minima || "1");
        setValor(form, "costo_ultimo", relacion.costo_ultimo || "0");
        setValor(form, "dias_entrega", relacion.dias_entrega || "0");
        setValor(form, "estatus", relacion.estatus || "activo");
        setChecked(form, "es_preferido", String(relacion.es_preferido) === "1" ? "1" : "0");
        actualizarModoSkuProveedor(true);
        actualizarPreviewSkuProveedor(form);
        form.scrollIntoView({behavior: "smooth", block: "center"});
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-25
     * Proposito: marca una relacion proveedor-SKU como principal sin obligar a editar todos sus campos.
     * Impacto: Catalogo ERP; simplifica decision manual en SKUs con multiples proveedores.
     */
    function preferirSkuProveedor(idRelacion) {
        var errorBox = document.getElementById("catalogo_proveedor_error");
        var relacion = (detalleActual.proveedores || []).find(function (item) {
            return String(item.id_sku_proveedor) === String(idRelacion);
        });
        if (!relacion) {
            mostrarError(errorBox, new Error("No se encontro la relacion proveedor-SKU"));
            return;
        }
        if (relacion.estatus !== "activo") {
            mostrarError(errorBox, new Error("Solo un proveedor activo puede marcarse como principal"));
            return;
        }
        request("/catalogoerp/guardar_sku_proveedor", {
            id_sku_proveedor: relacion.id_sku_proveedor,
            id_sku: relacion.id_sku,
            id_proveedor: relacion.id_proveedor,
            sku_proveedor: relacion.sku_proveedor || "",
            id_unidad_compra: relacion.id_unidad_compra,
            factor_conversion: relacion.factor_conversion || "1",
            cantidad_minima: relacion.cantidad_minima || "1",
            costo_ultimo: relacion.costo_ultimo || "0",
            dias_entrega: relacion.dias_entrega || "0",
            estatus: "activo",
            es_preferido: "1"
        }).then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje);
            }
            Swal.fire({text: response.mensaje, icon: "success", confirmButtonText: "Aceptar"});
            abrirDetalle(productoActualId, "catalogo_detalle_proveedores");
            cargar();
        }).catch(function (error) {
            mostrarError(errorBox, error);
        });
    }

    function guardarPresentacion(event) {
        event.preventDefault();
        var currentForm = event.currentTarget;
        var error = document.getElementById("catalogo_presentaciones_error");
        var base = currentForm.querySelector("[name='id_sku_base']").value;
        var presentacion = currentForm.querySelector("[name='id_sku_presentacion']").value;
        var modo = currentForm.querySelector("[name='modo_disponibilidad']").value;
        var consume = currentForm.querySelector("[name='consume_stock_base_en']").value;
        if (base && presentacion && base === presentacion) {
            mostrarError(error, new Error("El SKU base y la presentacion no pueden ser el mismo"));
            return;
        }
        if (modo === "preparada" && consume !== "preparacion") {
            mostrarError(error, new Error("Una presentacion preparada debe consumir stock base al prepararse"));
            return;
        }
        if (modo === "bajo_demanda" && consume !== "venta") {
            mostrarError(error, new Error("Una presentacion bajo demanda debe consumir stock base al venderse"));
            return;
        }
        enviarFormulario(currentForm, "/catalogoerp/guardar_sku_presentacion", error, function () {
            limpiarFormularioPresentacion();
            abrirDetalle(productoActualId);
        });
    }

    function editarPresentacion(id) {
        var form = document.getElementById("catalogo_form_presentacion");
        var item = (detalleActual.presentaciones || []).find(function (presentacion) {
            return String(presentacion.id_sku_presentacion_regla) === String(id);
        });
        if (!form || !item) {
            return;
        }
        setValor(form, "id_sku_presentacion_regla", item.id_sku_presentacion_regla);
        setValor(form, "id_sku_base", item.id_sku_base);
        setValor(form, "id_sku_presentacion", item.id_sku_presentacion);
        setValor(form, "factor_salida_base", item.factor_salida_base || "1");
        setValor(form, "modo_disponibilidad", item.modo_disponibilidad || "preparada");
        setValor(form, "consume_stock_base_en", item.consume_stock_base_en || "preparacion");
        setValor(form, "capacidad_diaria", item.capacidad_diaria || "");
        setValor(form, "merma_porcentaje", item.merma_porcentaje || "0");
        setValor(form, "estatus", item.estatus || "activa");
        setChecked(form, "requiere_empaque", item.requiere_empaque);
        actualizarModoPresentacion(true);
        form.scrollIntoView({behavior: "smooth", block: "start"});
    }

    function limpiarFormularioPresentacion() {
        var form = document.getElementById("catalogo_form_presentacion");
        if (!form) {
            return;
        }
        form.reset();
        setValor(form, "id_sku_presentacion_regla", "");
        setValor(form, "modo_disponibilidad", "preparada");
        setValor(form, "consume_stock_base_en", "preparacion");
        setValor(form, "factor_salida_base", "1");
        setValor(form, "merma_porcentaje", "0");
        setValor(form, "capacidad_diaria", "");
        setValor(form, "estatus", "activa");
        form.querySelector("[name='requiere_empaque']").checked = true;
        actualizarModoPresentacion(false);
    }

    function actualizarModoPresentacion(editando) {
        var titulo = document.getElementById("catalogo_presentacion_form_titulo");
        var boton = document.getElementById("catalogo_presentacion_guardar");
        var cancelar = document.getElementById("catalogo_cancelar_edicion_presentacion");
        if (titulo) {
            titulo.textContent = editando ? "Editar presentacion de venta" : "Configurar presentacion de venta";
        }
        if (boton) {
            boton.innerHTML = editando ? "<i class=\"bi bi-check-lg\"></i> Guardar cambios" : "<i class=\"bi bi-diagram-3\"></i> Guardar presentacion";
        }
        if (cancelar) {
            cancelar.classList.toggle("d-none", !editando);
        }
    }

    function desactivarPresentacion(id) {
        request("/catalogoerp/desactivar_sku_presentacion", {
            id_sku_presentacion_regla: id
        }).then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje);
            }
            abrirDetalle(productoActualId);
        }).catch(function (error) {
            mostrarError(document.getElementById("catalogo_presentaciones_error"), error);
        });
    }

    function guardarVariantes(event) {
        event.preventDefault();
        var currentForm = event.currentTarget;
        enviarFormulario(currentForm, "/catalogoerp/guardar_variantes", document.getElementById("catalogo_variantes_error"), function () {
            currentForm.reset();
            setValor(currentForm, "id_producto_erp", productoActualId);
            document.getElementById("catalogo_variante_valores").innerHTML = "";
            document.getElementById("catalogo_variante_guardar_contenedor").classList.add("d-none");
            cargar();
            abrirDetalle(productoActualId);
        });
    }

    function enviarFormulario(currentForm, url, errorBox, success) {
        var button = currentForm.querySelector("[type='submit']");
        button.disabled = true;
        errorBox.classList.add("d-none");
        var data = {};
        new FormData(currentForm).forEach(function (value, key) { data[key] = value; });
        request(url, data).then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje);
            }
            Swal.fire({text: response.mensaje, icon: "success", confirmButtonText: "Aceptar"});
            success(response);
        }).catch(function (error) {
            mostrarError(errorBox, error);
        }).finally(function () {
            button.disabled = false;
        });
    }

    function mostrarError(box, error) {
        box.textContent = error.message || String(error);
        box.classList.remove("d-none");
    }

    function setValor(currentForm, name, value) {
        var input = currentForm.querySelector("[name='" + name + "']");
        if (input) {
            input.value = value == null ? "" : value;
            if (input.tagName === "SELECT" && window.jQuery && jQuery.fn && jQuery.fn.select2) {
                jQuery(input).trigger("change.select2");
            }
        }
    }

    function setChecked(currentForm, name, value) {
        var input = currentForm.querySelector("[name='" + name + "']");
        if (input) {
            input.checked = String(value) === "1";
        }
    }

    function prepararSeccionesInventario(currentForm) {
        if (!currentForm || currentForm.dataset.inventarioSeccionado === "1") {
            return;
        }
        var primerCampo = currentForm.querySelector("[name='stock_minimo']");
        if (!primerCampo) {
            return;
        }
        var contenedor = primerCampo.closest(".row");
        if (!contenedor) {
            return;
        }

        agregarAyudasInventario(currentForm);

        var stock = crearSeccionInventario("Reglas de stock", "Alertas, reorden y salida sugerida. No mueve inventario por si solo.");
        var stockRow = crearFilaSeccion(stock);
        ["stock_minimo", "stock_maximo", "punto_reorden", "estrategia_salida", "dias_alerta_caducidad", "dias_minimos_recepcion"].forEach(function (name) {
            moverCampoASeccion(currentForm, name, stockRow);
        });

        var recepcion = crearSeccionInventario("Recepcion variable", "Para SKUs donde Almacen debe capturar cantidad real recibida. No crear unidades como costal, saco o paca por lenguaje operativo.");
        moverGrupoCheckboxASeccion(currentForm, "requiere_cantidad_variable_recepcion", recepcion);
        var recepcionRow = crearFilaSeccion(recepcion);
        ["tolerancia_recepcion_porcentaje", "nota_recepcion_variable"].forEach(function (name) {
            moverCampoASeccion(currentForm, name, recepcionRow);
        });

        var control = crearSeccionInventario("Control fisico", "Lote, caducidad, serie y condiciones especiales de existencia.");
        moverGrupoCheckboxASeccion(currentForm, "requiere_lote", control);

        var granel = crearSeccionInventario("Venta a granel", "Solo para SKU vendidos en cantidades decimales como kg, litros o metros.");
        moverGrupoCheckboxASeccion(currentForm, "permite_venta_fraccionaria", granel);
        var granelRow = crearFilaSeccion(granel);
        ["precision_decimal", "incremento_minimo_venta", "unidad_venta_label"].forEach(function (name) {
            moverCampoASeccion(currentForm, name, granelRow);
        });

        var etiquetado = crearSeccionInventario("Etiquetado y trazabilidad", "Aplica a piezas o fracciones etiquetadas. No se usa para granel comun.");
        moverGrupoCheckboxASeccion(currentForm, "requiere_serie_fabricante", etiquetado);
        var etiquetadoRow = crearFilaSeccion(etiquetado);
        ["prefijo_etiqueta_interna", "plantilla_etiqueta", "tipo_etiqueta_seguridad", "instrucciones_etiquetado"].forEach(function (name) {
            moverCampoASeccion(currentForm, name, etiquetadoRow);
        });

        [stock, recepcion, control, granel, etiquetado].forEach(function (section) {
            contenedor.appendChild(section);
        });
        organizarFiscalForm(currentForm, contenedor, stock);
        currentForm.dataset.inventarioSeccionado = "1";
        inicializarTooltips(currentForm);
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-24
     * Proposito: agrupa impuestos y claves SAT para que la captura fiscal no quede mezclada con inventario.
     * Impacto: UI de Catalogo ERP; permite fiscal parcial sin confundirlo con estado de venta.
     */
    function organizarFiscalForm(currentForm, contenedor, antesDe) {
        if (!currentForm || !contenedor || currentForm.dataset.fiscalSeccionado === "1") {
            return;
        }
        var fiscal = crearSeccionInventario("Fiscal e impuestos", "Captura parcial permitida. Catalogo guarda avances y la auditoria fiscal indicara lo pendiente.");
        var fiscalRow = crearFilaSeccion(fiscal);
        ["clave_producto_sat", "clave_unidad_sat", "objeto_impuesto", "iva_porcentaje", "ieps_porcentaje"].forEach(function (name) {
            moverCampoASeccion(currentForm, name, fiscalRow);
        });
        moverCheckboxIndividualASeccion(currentForm, "incluye_impuestos", fiscal);
        if (antesDe && antesDe.parentNode === contenedor) {
            contenedor.insertBefore(fiscal, antesDe);
        } else {
            contenedor.appendChild(fiscal);
        }
        currentForm.dataset.fiscalSeccionado = "1";
    }

    function crearSeccionInventario(titulo, ayuda) {
        var outer = document.createElement("div");
        outer.className = "col-12";
        var section = document.createElement("div");
        section.className = "catalogo-config-section";
        section.innerHTML = "<div class=\"catalogo-config-section__title\">" + escapeHtml(titulo) +
            " <button type=\"button\" class=\"btn btn-icon btn-light catalogo-help\" data-bs-toggle=\"tooltip\" data-bs-placement=\"top\" title=\"" + escapeAttr(ayuda) + "\"><i class=\"bi bi-question-circle\"></i></button></div>";
        outer.appendChild(section);
        return outer;
    }

    function crearFilaSeccion(sectionOuter) {
        var row = document.createElement("div");
        row.className = "row g-5";
        sectionOuter.querySelector(".catalogo-config-section").appendChild(row);
        return row;
    }

    function moverCampoASeccion(currentForm, name, destino) {
        var input = currentForm.querySelector("[name='" + name + "']");
        var campo = input ? input.closest("[class*='col-']") : null;
        if (campo && destino) {
            destino.appendChild(campo);
        }
    }

    function moverGrupoCheckboxASeccion(currentForm, name, sectionOuter) {
        var input = currentForm.querySelector("[name='" + name + "']");
        var grupo = input ? input.closest(".col-12") : null;
        var section = sectionOuter ? sectionOuter.querySelector(".catalogo-config-section") : null;
        if (grupo && section) {
            section.appendChild(grupo);
        }
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-24
     * Proposito: separa un switch fiscal de una fila mixta sin mover controles que pertenecen a inventario.
     * Impacto: UI de Catalogo ERP; evita que precio con impuestos quede asociado visualmente a control fisico.
     */
    function moverCheckboxIndividualASeccion(currentForm, name, sectionOuter) {
        var input = currentForm.querySelector("[name='" + name + "']");
        var label = input ? input.closest("label") : null;
        var section = sectionOuter ? sectionOuter.querySelector(".catalogo-config-section") : null;
        if (!label || !section) {
            return;
        }
        var wrapper = document.createElement("div");
        wrapper.className = "d-flex flex-wrap gap-8 mt-4";
        wrapper.appendChild(label);
        section.appendChild(wrapper);
    }

    function agregarAyudasInventario(currentForm) {
        var ayudas = {
            stock_minimo: "Cantidad minima deseada antes de considerar reposicion.",
            stock_maximo: "Tope operativo sugerido para no sobrecomprar. Puede quedar vacio.",
            punto_reorden: "Cantidad desde la que debe considerarse recompra o alerta de reposicion.",
            estrategia_salida: "Orden sugerido para descontar inventario: FIFO, FEFO para caducidad o LIFO.",
            dias_alerta_caducidad: "Dias previos a caducidad para advertir producto proximo a vencer.",
            dias_minimos_recepcion: "Dias minimos de vida util que debe traer el producto al entrar a almacen.",
            tolerancia_recepcion_porcentaje: "Variacion permitida entre lo esperado por factor de compra y la cantidad real recibida.",
            nota_recepcion_variable: "Instruccion para recepcion. Ejemplo: pesar al recibir o capturar unidades fisicas.",
            clave_producto_sat: "Clave SAT del producto o servicio. Puede capturarse despues si aun no esta validada.",
            clave_unidad_sat: "Clave SAT de la unidad fiscal. No siempre es igual al nombre operativo de la unidad.",
            objeto_impuesto: "Indica si el SKU es objeto de impuesto segun SAT. Si no se sabe, dejalo pendiente.",
            iva_porcentaje: "Porcentaje IVA aplicable. Puede quedar vacio durante captura inicial.",
            ieps_porcentaje: "Porcentaje IEPS aplicable. Puede quedar vacio si aun no se valida.",
            precision_decimal: "Maximo de decimales permitidos. Ejemplo: 3 permite 0.250 kg.",
            incremento_minimo_venta: "Paso minimo permitido. Ejemplo: 0.250 kg vende por cuartos de kilo.",
            unidad_venta_label: "Texto visual opcional. Si queda vacio se usa la abreviatura de la unidad base.",
            prefijo_etiqueta_interna: "Texto inicial para codigos internos de trazabilidad, como ART. No representa granel ni presentacion.",
            plantilla_etiqueta: "Nombre del formato de impresion o QR que se usaria para la etiqueta.",
            tipo_etiqueta_seguridad: "Tipo fisico o visual de seguridad de la etiqueta, por ejemplo void o sello.",
            instrucciones_etiquetado: "Indicaciones operativas para colocar o manejar la etiqueta."
        };
        Object.keys(ayudas).forEach(function (name) {
            agregarAyudaCampo(currentForm, name, ayudas[name]);
        });
    }

    function agregarAyudaCampo(currentForm, name, ayuda) {
        var input = currentForm.querySelector("[name='" + name + "']");
        var campo = input ? input.closest("[class*='col-']") : null;
        var label = campo ? campo.querySelector(".form-label") : null;
        if (!label || label.querySelector("[data-catalogo-ayuda]")) {
            return;
        }
        var boton = document.createElement("button");
        boton.type = "button";
        boton.className = "btn btn-icon btn-light catalogo-help ms-1";
        boton.setAttribute("data-catalogo-ayuda", "1");
        boton.setAttribute("data-bs-toggle", "tooltip");
        boton.setAttribute("data-bs-placement", "top");
        boton.setAttribute("title", ayuda);
        boton.innerHTML = "<i class=\"bi bi-question-circle\"></i>";
        label.appendChild(boton);
    }

    function inicializarTooltips(scope) {
        if (!window.bootstrap || !bootstrap.Tooltip) {
            return;
        }
        (scope || document).querySelectorAll("[data-bs-toggle='tooltip']").forEach(function (element) {
            bootstrap.Tooltip.getOrCreateInstance(element);
        });
    }

    function prepararCamposGranel(currentForm) {
        if (!currentForm) {
            return;
        }
        var checkbox = currentForm.querySelector("[name='permite_venta_fraccionaria']");
        if (checkbox) {
            checkbox.addEventListener("change", function () {
                actualizarCamposGranel(currentForm);
            });
        }
        actualizarCamposGranel(currentForm);
    }

    function actualizarCamposGranel(currentForm) {
        if (!currentForm) {
            return;
        }
        var checkbox = currentForm.querySelector("[name='permite_venta_fraccionaria']");
        var activo = !!(checkbox && checkbox.checked);
        currentForm.querySelectorAll("[data-granel-campo]").forEach(function (campo) {
            campo.classList.toggle("d-none", !activo);
            campo.querySelectorAll("input, select, textarea").forEach(function (input) {
                input.disabled = !activo;
            });
        });
        if (!activo) {
            setValor(currentForm, "precision_decimal", "0");
            setValor(currentForm, "incremento_minimo_venta", "1");
            setValor(currentForm, "unidad_venta_label", "");
            setChecked(currentForm, "permite_etiqueta_fraccionada", 0);
        }
    }

    function validarFactorUnidadBase(currentForm, errorBox) {
        var unidadInput = currentForm.querySelector("[name='id_unidad_base']");
        var factorInput = currentForm.querySelector("[name='factor_unidad_base']");
        if (!unidadInput || !factorInput) {
            return true;
        }
        var factor = parseFloat(factorInput.value || "0");
        if (unidadInput.value && (isNaN(factor) || factor <= 0)) {
            mostrarError(errorBox, new Error("El factor de conversion de la unidad base debe ser mayor a 0"));
            factorInput.focus();
            return false;
        }
        return true;
    }

    function validarFormularioGranel(currentForm, errorBox) {
        var fraccionaria = currentForm.querySelector("[name='permite_venta_fraccionaria']");
        if (!fraccionaria || !fraccionaria.checked) {
            return true;
        }
        var tipo = currentForm.querySelector("[name='tipo_inventario']");
        if (tipo && ["servicio", "cargo"].indexOf(String(tipo.value)) >= 0) {
            mostrarError(errorBox, new Error("La venta fraccionaria requiere un SKU inventariable, consumible o kit"));
            tipo.focus();
            return false;
        }
        var unidadInput = currentForm.querySelector("[name='id_unidad_base']");
        var unidad = unidadPorId(unidadInput ? unidadInput.value : "");
        if (!unidad || String(unidad.decimales_permitidos) !== "1") {
            mostrarError(errorBox, new Error("La unidad base debe permitir decimales para vender a granel"));
            if (unidadInput) { unidadInput.focus(); }
            return false;
        }
        var precisionInput = currentForm.querySelector("[name='precision_decimal']");
        var precision = parseInt(precisionInput ? precisionInput.value : "0", 10);
        if (isNaN(precision) || precision < 1 || precision > 6) {
            mostrarError(errorBox, new Error("La precision decimal para granel debe estar entre 1 y 6"));
            if (precisionInput) { precisionInput.focus(); }
            return false;
        }
        var incrementoInput = currentForm.querySelector("[name='incremento_minimo_venta']");
        var incremento = parseFloat(incrementoInput ? incrementoInput.value : "0");
        if (isNaN(incremento) || incremento <= 0) {
            mostrarError(errorBox, new Error("El incremento minimo de venta a granel debe ser mayor a 0"));
            if (incrementoInput) { incrementoInput.focus(); }
            return false;
        }
        if (contarDecimales(incrementoInput ? incrementoInput.value : "") > precision) {
            mostrarError(errorBox, new Error("El incremento minimo no puede tener mas decimales que la precision configurada"));
            if (incrementoInput) { incrementoInput.focus(); }
            return false;
        }
        var etiqueta = currentForm.querySelector("[name='generar_etiqueta_interna']");
        var serie = currentForm.querySelector("[name='requiere_serie']");
        var serieFabricante = currentForm.querySelector("[name='requiere_serie_fabricante']");
        var etiquetaFraccionada = currentForm.querySelector("[name='permite_etiqueta_fraccionada']");
        if (etiqueta && etiqueta.checked && (!etiquetaFraccionada || !etiquetaFraccionada.checked)) {
            mostrarError(errorBox, new Error("No se permite etiqueta individual en venta fraccionaria salvo que actives etiqueta fraccionada"));
            etiqueta.focus();
            return false;
        }
        if (((serie && serie.checked) || (serieFabricante && serieFabricante.checked)) && (!etiquetaFraccionada || !etiquetaFraccionada.checked)) {
            mostrarError(errorBox, new Error("No se permite serie individual en venta fraccionaria salvo que actives etiqueta fraccionada"));
            (serie && serie.checked ? serie : serieFabricante).focus();
            return false;
        }
        return true;
    }

    function unidadPorId(idUnidad) {
        return (catalogosDisponibles.unidades || []).find(function (unidad) {
            return String(unidad.id_unidad) === String(idUnidad);
        });
    }

    function contarDecimales(valor) {
        var texto = String(valor || "").replace(",", ".").trim().toLowerCase();
        if (!texto) {
            return 0;
        }
        if (texto.indexOf("e") >= 0) {
            texto = Number(texto).toFixed(10).replace(/0+$/, "").replace(/\.$/, "");
        }
        var partes = texto.split(".");
        return partes.length > 1 ? partes[1].replace(/0+$/, "").length : 0;
    }

    function generarCodigoInternoDesdeSku(currentForm, errorBox) {
        var skuInput = currentForm ? currentForm.querySelector("[name='sku']") : null;
        var codigoInput = currentForm ? currentForm.querySelector("[name='codigo_barras']") : null;
        var sku = skuInput ? skuInput.value.trim() : "";
        if (!sku) {
            mostrarError(errorBox, new Error("Captura primero el SKU para generar codigo interno"));
            if (skuInput) { skuInput.focus(); }
            return;
        }
        var codigo = "INT-" + normalizarCodigoInterno(sku);
        if (codigoInput) {
            codigoInput.value = codigo;
            codigoInput.dispatchEvent(new Event("input", {bubbles: true}));
            codigoInput.focus();
        }
    }

    function normalizarCodigoInterno(valor) {
        return String(valor || "")
            .normalize("NFD")
            .replace(/[\u0300-\u036f]/g, "")
            .toUpperCase()
            .replace(/[^A-Z0-9]+/g, "-")
            .replace(/^-+|-+$/g, "")
            .replace(/-{2,}/g, "-")
            .substring(0, 150);
    }

    function prepararSkuProveedorForm(currentForm) {
        if (!currentForm) {
            return;
        }
        ["id_sku", "id_unidad_compra", "factor_conversion", "cantidad_minima"].forEach(function (name) {
            var input = currentForm.querySelector("[name='" + name + "']");
            if (input) {
                input.addEventListener("input", function () { actualizarPreviewSkuProveedor(currentForm); });
                input.addEventListener("change", function () { actualizarPreviewSkuProveedor(currentForm); });
            }
        });
        actualizarPreviewSkuProveedor(currentForm);
    }

    function actualizarPreviewSkuProveedor(currentForm) {
        var preview = document.getElementById("catalogo_proveedor_conversion_preview");
        if (!preview || !currentForm) {
            return;
        }
        var idSku = valorCampo(currentForm, "id_sku");
        var idUnidadCompra = valorCampo(currentForm, "id_unidad_compra");
        var factor = parseFloat(valorCampo(currentForm, "factor_conversion") || "0");
        var sku = (detalleActual.skus || []).find(function (item) { return String(item.id_sku) === String(idSku); });
        var unidadCompra = unidadPorId(idUnidadCompra);
        if (!sku || !unidadCompra || !factor || factor <= 0) {
            preview.textContent = "";
            return;
        }
        preview.textContent = "1 " + (unidadCompra.abreviatura || unidadCompra.nombre) + " de compra equivale a " + factor + " " + (sku.abreviatura || sku.unidad) + " de inventario.";
    }

    function validarFormularioSkuProveedor(currentForm, errorBox) {
        var factorInput = currentForm.querySelector("[name='factor_conversion']");
        var minimaInput = currentForm.querySelector("[name='cantidad_minima']");
        var factor = parseFloat(factorInput ? factorInput.value : "0");
        var minima = parseFloat(minimaInput ? minimaInput.value : "0");
        if (isNaN(factor) || factor <= 0) {
            mostrarError(errorBox, new Error("Las unidades base por compra deben ser mayores a 0"));
            if (factorInput) { factorInput.focus(); }
            return false;
        }
        if (isNaN(minima) || minima <= 0) {
            mostrarError(errorBox, new Error("La compra minima debe ser mayor a 0"));
            if (minimaInput) { minimaInput.focus(); }
            return false;
        }
        return true;
    }

    function valorCampo(currentForm, name) {
        var input = currentForm.querySelector("[name='" + name + "']");
        return input ? input.value : "";
    }

    function escapeHtml(value) {
        var div = document.createElement("div");
        div.textContent = value == null ? "" : String(value);
        return div.innerHTML;
    }

    function escapeAttr(value) {
        return escapeHtml(value).replace(/"/g, "&quot;").replace(/'/g, "&#39;");
    }

    document.addEventListener("DOMContentLoaded", function () {
        tbody = document.getElementById("catalogo_productos");
        buscar = document.getElementById("catalogo_buscar");
        filtroEstatus = document.getElementById("catalogo_filtro_estatus");
        filtroSaneamiento = document.getElementById("catalogo_filtro_saneamiento");
        formAlta = document.getElementById("catalogo_form_alta");
        if (!tbody || !buscar) {
            return;
        }
        buscar.addEventListener("input", function () {
            paginaActual = 1;
            render();
        });
        if (filtroEstatus) {
            filtroEstatus.addEventListener("change", function () {
                paginaActual = 1;
                render();
            });
        }
        if (filtroSaneamiento) {
            filtroSaneamiento.addEventListener("change", function () {
                paginaActual = 1;
                render();
            });
        }
        var resumenSaneamiento = document.getElementById("catalogo_resumen_saneamiento");
        if (resumenSaneamiento) {
            resumenSaneamiento.addEventListener("click", function (event) {
                var button = event.target.closest("[data-filtro-saneamiento]");
                if (!button || !filtroSaneamiento) {
                    return;
                }
                filtroSaneamiento.value = button.getAttribute("data-filtro-saneamiento");
                paginaActual = 1;
                render();
            });
        }
        var seleccionarPagina = document.getElementById("catalogo_seleccionar_pagina");
        if (seleccionarPagina) {
            seleccionarPagina.addEventListener("change", function () {
                productosPaginaActual.forEach(function (id) {
                    productosSeleccionados[id] = seleccionarPagina.checked;
                });
                render();
            });
        }
        var aplicarMasivo = document.getElementById("catalogo_masivo_aplicar");
        if (aplicarMasivo) {
            aplicarMasivo.addEventListener("click", aplicarMetadatosMasivos);
        }
        tbody.addEventListener("click", function (event) {
            var seleccion = event.target.closest("[data-seleccionar-producto]");
            if (seleccion) {
                productosSeleccionados[seleccion.getAttribute("data-seleccionar-producto")] = seleccion.checked;
                actualizarEstadoSeleccionMasiva();
                return;
            }
            var button = event.target.closest("[data-producto]");
            if (button) {
                abrirDetalle(button.getAttribute("data-producto"), button.getAttribute("data-detalle-tab"));
            }
        });
        if (formAlta) {
            formAlta.addEventListener("submit", guardarAlta);
            prepararSeccionesInventario(formAlta);
            prepararCamposGranel(formAlta);
            formAlta.addEventListener("click", function (event) {
                if (event.target.closest("[data-generar-codigo-interno]")) {
                    generarCodigoInternoDesdeSku(formAlta, document.getElementById("catalogo_error"));
                }
            });
        }
        var editar = document.getElementById("catalogo_form_editar");
        var skuForm = document.getElementById("catalogo_form_sku");
        var imagenForm = document.getElementById("catalogo_form_imagen");
        var proveedorForm = document.getElementById("catalogo_form_sku_proveedor");
        var proveedorUnicoPreferido = document.getElementById("catalogo_proveedor_unico_preferido");
        var variantesForm = document.getElementById("catalogo_form_variantes");
        var presentacionForm = document.getElementById("catalogo_form_presentacion");
        var temporalForm = document.getElementById("catalogo_form_sku_temporal");
        var prepararVariantes = document.getElementById("catalogo_preparar_variante");
        var cancelarSku = document.getElementById("catalogo_cancelar_edicion_sku");
        var cancelarSkuProveedor = document.getElementById("catalogo_cancelar_edicion_sku_proveedor");
        var cancelarImagen = document.getElementById("catalogo_cancelar_edicion_imagen");
        var auditarImagenesEcom = document.getElementById("catalogo_imagenes_ecommerce_auditar");
        var recuperarImagenesEcom = document.getElementById("catalogo_imagenes_ecommerce_recuperar");
        var cancelarPresentacion = document.getElementById("catalogo_cancelar_edicion_presentacion");
        var paginaAnterior = document.getElementById("catalogo_pagina_anterior");
        var paginaSiguiente = document.getElementById("catalogo_pagina_siguiente");
        var tamanoPaginaSelect = document.getElementById("catalogo_tamano_pagina");
        var incidenciasBody = document.getElementById("catalogo_incidencias_body");
        var incidenciasRecargar = document.getElementById("catalogo_incidencias_recargar");
        if (editar) {
            editar.addEventListener("submit", guardarEdicion);
        }
        if (skuForm) {
            skuForm.addEventListener("submit", guardarSku);
            prepararSeccionesInventario(skuForm);
            prepararCamposGranel(skuForm);
            ["sku", "codigo_barras"].forEach(function (name) {
                var input = skuForm.querySelector("[name='" + name + "']");
                if (input) {
                    input.addEventListener("input", function () {
                        actualizarAlertaIdentidadSku(skuForm);
                    });
                }
            });
            skuForm.addEventListener("click", function (event) {
                if (event.target.closest("[data-generar-codigo-interno]")) {
                    generarCodigoInternoDesdeSku(skuForm, document.getElementById("catalogo_detalle_error"));
                }
            });
        }
        if (cancelarSku) {
            cancelarSku.addEventListener("click", limpiarFormularioSku);
        }
        if (imagenForm) {
            imagenForm.addEventListener("submit", guardarImagen);
            var imagenUrl = imagenForm.querySelector("[name='url_imagen']");
            if (imagenUrl) {
                imagenUrl.addEventListener("input", function () {
                    actualizarPreviewImagen(imagenForm);
                });
            }
        }
        if (cancelarImagen) {
            cancelarImagen.addEventListener("click", limpiarFormularioImagen);
        }
        if (auditarImagenesEcom) {
            auditarImagenesEcom.addEventListener("click", auditarImagenesEcommerce);
        }
        if (recuperarImagenesEcom) {
            recuperarImagenesEcom.classList.add("d-none");
        }
        var resumenImagenesEcom = document.getElementById("catalogo_imagenes_ecommerce_resumen");
        if (resumenImagenesEcom) {
            resumenImagenesEcom.addEventListener("click", function (event) {
                var button = event.target.closest("[data-recuperar-imagen-ecommerce]");
                if (!button) {
                    return;
                }
                recuperarImagenesEcommerce(button.getAttribute("data-recuperar-imagen-ecommerce"), button.getAttribute("data-tipo-imagen"));
            });
        }
        if (paginaAnterior) {
            paginaAnterior.addEventListener("click", function () {
                paginaActual = Math.max(1, paginaActual - 1);
                render();
            });
        }
        if (paginaSiguiente) {
            paginaSiguiente.addEventListener("click", function () {
                paginaActual += 1;
                render();
            });
        }
        if (tamanoPaginaSelect) {
            tamanoPaginaSelect.addEventListener("change", function () {
                tamanoPagina = Number(this.value) || 25;
                paginaActual = 1;
                render();
            });
        }
        if (proveedorForm) {
            proveedorForm.addEventListener("submit", guardarSkuProveedor);
            prepararSkuProveedorForm(proveedorForm);
            document.getElementById("catalogo_detalle_proveedores_lista").addEventListener("click", function (event) {
                var preferir = event.target.closest("[data-preferir-sku-proveedor]");
                if (preferir) {
                    preferirSkuProveedor(preferir.getAttribute("data-preferir-sku-proveedor"));
                    return;
                }
                var button = event.target.closest("[data-editar-sku-proveedor]");
                if (button) {
                    editarSkuProveedor(button.getAttribute("data-editar-sku-proveedor"));
                }
            });
        }
        if (proveedorUnicoPreferido) {
            proveedorUnicoPreferido.addEventListener("click", marcarProveedorUnicoPreferido);
        }
        if (cancelarSkuProveedor) {
            cancelarSkuProveedor.addEventListener("click", limpiarFormularioSkuProveedor);
        }
        if (variantesForm) {
            variantesForm.addEventListener("submit", guardarVariantes);
        }
        if (presentacionForm) {
            presentacionForm.addEventListener("submit", guardarPresentacion);
        }
        if (cancelarPresentacion) {
            cancelarPresentacion.addEventListener("click", limpiarFormularioPresentacion);
        }
        if (temporalForm) {
            temporalForm.addEventListener("submit", guardarSkuTemporal);
        }
        if (incidenciasRecargar) {
            incidenciasRecargar.addEventListener("click", recargarIncidenciasCalidad);
        }
        if (incidenciasBody) {
            incidenciasBody.addEventListener("click", function (event) {
                var temporal = event.target.closest("[data-sku-temporal]");
                if (temporal) {
                    abrirSkuTemporal(temporal.getAttribute("data-sku-temporal"));
                    return;
                }
                var producto = event.target.closest("[data-producto]");
                if (producto && producto.getAttribute("data-producto")) {
                    abrirDetalle(producto.getAttribute("data-producto"));
                }
            });
        }
        if (prepararVariantes) {
            prepararVariantes.addEventListener("click", prepararVariante);
        }
        document.getElementById("catalogo_variante_valores").addEventListener("input", function (event) {
            if (event.target.matches("[data-color-selector]")) {
                event.target.parentElement.querySelector("[data-color-text]").value = event.target.value.toUpperCase();
            } else if (event.target.matches("[data-color-text]") && /^#[0-9a-f]{6}$/i.test(event.target.value)) {
                event.target.parentElement.querySelector("[data-color-selector]").value = event.target.value;
            }
        });
        document.getElementById("catalogo_variantes_encabezado").addEventListener("click", function (event) {
            var button = event.target.closest("[data-editar-variante]");
            if (button) {
                document.getElementById("catalogo_variante_atributo").value = button.getAttribute("data-editar-variante");
                document.querySelector("#catalogo_form_variantes [name='nuevo_atributo']").value = "";
                prepararVariante();
            }
        });
        document.getElementById("catalogo_detalle_skus_lista").addEventListener("click", function (event) {
            var button = event.target.closest("[data-editar-sku]");
            if (button) {
                editarSku(button.getAttribute("data-editar-sku"));
            }
        });
        var objetivosSku = document.getElementById("catalogo_sku_objetivos_resumen");
        if (objetivosSku) {
            objetivosSku.addEventListener("click", function (event) {
                var toggleArchivados = event.target.closest("[data-toggle-skus-archivados]");
                if (toggleArchivados) {
                    mostrarSkusArchivados = !mostrarSkusArchivados;
                    renderSkus(detalleActual.skus || []);
                    return;
                }
                var button = event.target.closest("[data-filtro-objetivo-sku]");
                if (!button) {
                    return;
                }
                filtroObjetivoSku = button.getAttribute("data-filtro-objetivo-sku") || "todos";
                renderSkus(detalleActual.skus || []);
            });
        }
        document.getElementById("catalogo_imagenes_lista").addEventListener("click", function (event) {
            var editar = event.target.closest("[data-editar-imagen]");
            var desactivar = event.target.closest("[data-desactivar-imagen]");
            if (editar) {
                editarImagen(editar.getAttribute("data-editar-imagen"));
            } else if (desactivar) {
                desactivarImagen(desactivar.getAttribute("data-desactivar-imagen"));
            }
        });
        var presentacionesLista = document.getElementById("catalogo_presentaciones_lista");
        if (presentacionesLista) {
            presentacionesLista.addEventListener("click", function (event) {
                var editar = event.target.closest("[data-editar-presentacion]");
                var desactivar = event.target.closest("[data-desactivar-presentacion]");
                if (editar) {
                    editarPresentacion(editar.getAttribute("data-editar-presentacion"));
                } else if (desactivar) {
                    desactivarPresentacion(desactivar.getAttribute("data-desactivar-presentacion"));
                }
            });
        }
        var paqueteBuscar = document.getElementById("catalogo_paquete_buscar_boton");
        if (paqueteBuscar) {
            paqueteBuscar.addEventListener("click", buscarSkuComponentePaquete);
        }
        var paqueteInput = document.getElementById("catalogo_paquete_buscar_sku");
        if (paqueteInput) {
            paqueteInput.addEventListener("keydown", function (event) {
                if (event.key === "Enter") {
                    event.preventDefault();
                    buscarSkuComponentePaquete();
                }
            });
        }
        var paqueteResultados = document.getElementById("catalogo_paquete_resultados_sku");
        if (paqueteResultados) {
            paqueteResultados.addEventListener("click", function (event) {
                var boton = event.target.closest("[data-agregar-componente-paquete]");
                if (boton) {
                    agregarComponentePaquete(boton);
                }
            });
        }
        var paqueteComponentes = document.getElementById("catalogo_paquete_componentes_lista");
        if (paqueteComponentes) {
            paqueteComponentes.addEventListener("click", function (event) {
                var quitar = event.target.closest("[data-quitar-componente-paquete]");
                if (!quitar) {
                    return;
                }
                componentesPaqueteForm.splice(Number(quitar.getAttribute("data-quitar-componente-paquete")), 1);
                renderComponentesPaqueteForm();
            });
        }
        var paqueteForm = document.getElementById("catalogo_form_paquete");
        if (paqueteForm) {
            paqueteForm.addEventListener("submit", guardarPaqueteSimple);
        }
        var paqueteSkuInlineForm = document.getElementById("catalogo_form_paquete_sku_inline");
        if (paqueteSkuInlineForm) {
            paqueteSkuInlineForm.addEventListener("submit", guardarSkuPaqueteInline);
        }
        var crearSkuPaquete = document.getElementById("catalogo_paquete_crear_sku");
        if (crearSkuPaquete) {
            crearSkuPaquete.addEventListener("click", prepararNuevoSkuPaquete);
        }
        var paqueteGrupoForm = document.getElementById("catalogo_form_paquete_grupo");
        if (paqueteGrupoForm) {
            paqueteGrupoForm.addEventListener("submit", guardarPaqueteGrupo);
        }
        var paqueteOpcionForm = document.getElementById("catalogo_form_paquete_opcion");
        if (paqueteOpcionForm) {
            paqueteOpcionForm.addEventListener("submit", guardarPaqueteOpcion);
        }
        var paqueteOpcionBuscar = document.getElementById("catalogo_paquete_opcion_buscar_boton");
        if (paqueteOpcionBuscar) {
            paqueteOpcionBuscar.addEventListener("click", buscarSkuOpcionPaquete);
        }
        var paqueteOpcionInput = document.getElementById("catalogo_paquete_opcion_buscar_sku");
        if (paqueteOpcionInput) {
            paqueteOpcionInput.addEventListener("keydown", function (event) {
                if (event.key === "Enter") {
                    event.preventDefault();
                    buscarSkuOpcionPaquete();
                }
            });
        }
        var paqueteOpcionResultados = document.getElementById("catalogo_paquete_opcion_resultados_sku");
        if (paqueteOpcionResultados) {
            paqueteOpcionResultados.addEventListener("click", function (event) {
                var boton = event.target.closest("[data-seleccionar-opcion-paquete]");
                if (!boton) {
                    return;
                }
                seleccionarSkuOpcionPaquete({
                    id_sku: boton.getAttribute("data-seleccionar-opcion-paquete"),
                    sku: boton.getAttribute("data-sku") || "",
                    nombre: boton.getAttribute("data-nombre") || ""
                });
            });
        }
        var paquetesLista = document.getElementById("catalogo_paquetes_lista");
        if (paquetesLista) {
            paquetesLista.addEventListener("click", function (event) {
                var editar = event.target.closest("[data-editar-paquete]");
                var desactivar = event.target.closest("[data-desactivar-paquete]");
                var nuevoGrupo = event.target.closest("[data-nuevo-grupo-paquete]");
                var editarGrupo = event.target.closest("[data-editar-grupo-paquete]");
                var nuevoOpcion = event.target.closest("[data-nueva-opcion-grupo]");
                var editarOpcion = event.target.closest("[data-editar-opcion-paquete]");
                var desactivarGrupo = event.target.closest("[data-desactivar-grupo-paquete]");
                var desactivarOpcion = event.target.closest("[data-desactivar-opcion-paquete]");
                if (editar) {
                    editarPaqueteSimple(editar.getAttribute("data-editar-paquete"));
                } else if (desactivar) {
                    desactivarPaqueteSimple(desactivar.getAttribute("data-desactivar-paquete"));
                } else if (nuevoGrupo) {
                    prepararGrupoPaquete(nuevoGrupo.getAttribute("data-nuevo-grupo-paquete"), null);
                } else if (editarGrupo) {
                    var infoGrupo = buscarGrupoPaquete(editarGrupo.getAttribute("data-editar-grupo-paquete"));
                    if (infoGrupo) {
                        prepararGrupoPaquete(infoGrupo.paquete.id_paquete, infoGrupo.grupo);
                    }
                } else if (nuevoOpcion) {
                    prepararOpcionPaquete(nuevoOpcion.getAttribute("data-nueva-opcion-grupo"), null);
                } else if (editarOpcion) {
                    var infoOpcion = buscarOpcionPaquete(editarOpcion.getAttribute("data-editar-opcion-paquete"));
                    if (infoOpcion) {
                        prepararOpcionPaquete(infoOpcion.grupo.id_grupo, infoOpcion.opcion);
                    }
                } else if (desactivarGrupo) {
                    desactivarGrupoPaquete(desactivarGrupo.getAttribute("data-desactivar-grupo-paquete"));
                } else if (desactivarOpcion) {
                    desactivarOpcionPaquete(desactivarOpcion.getAttribute("data-desactivar-opcion-paquete"));
                }
            });
        }
        cargar();
    });
})();
