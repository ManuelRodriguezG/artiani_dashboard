"use strict";
(function () {
    var datos = {marcas: [], categorias: [], unidades: [], atributos: []};
    var tipoActual = "marca";
    var form;
    var modal;
    var formImagenMaestro;
    var modalImagenMaestro;
    var imagenMaestroActual = {tipo: "", id: 0, nombre: "", schema: false, tipos: [], imagenes: []};
    var propuestasCostos = [];
    var paginaCostos = 1;
    var tamanoPaginaCostos = 25;
    var propuestasReorden = [];
    var paginaReorden = 1;
    var revisionMetadatos = [];
    var categoriasRevision = [];
    var marcasRevision = [];
    var paginaClasificacion = 1;
    var taxonomias = [];
    var nodosTaxonomia = [];
    var paginaIncidencias = 1;
    var tamanoPaginaIncidencias = 25;
    var totalIncidencias = 0;
    var filtroObjetivoAuditoria = "";
    var filtroObjetivoIncidencias = "";
    var relacionesProveedorPendientes = [];
    var relacionesProveedorSeleccionadas = {};
    var permisos = window.CATALOGO_PERMISOS || {};
    var proveedorCostosInicializado = false;
    var cargasDiferidas = {};
    var modulosConfiguracion = ["maestros", "calidad", "clasificacion", "clasificacion_heredada", "reglas", "proveedor_costos"];

    function request(url, data) {
        return fetch(url, {method: data ? "POST" : "GET", headers: data ? {"Content-Type": "application/x-www-form-urlencoded; charset=UTF-8"} : {}, body: data ? new URLSearchParams(data).toString() : null, credentials: "same-origin"}).then(function (response) { return response.json(); });
    }

    function cargar() {
        request("/catalogoerp/auxiliares_listar").then(function (response) {
            datos = response.depurar || datos;
            render();
            llenarPadres();
        });
    }

    function cargarAuditoria() {
        request("/catalogoerp/auditoria_calidad").then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje);
            }
            renderAuditoria(response.depurar || {});
        }).catch(function (error) {
            document.getElementById("catalogo_auditoria_problemas").innerHTML = "<tr><td colspan=\"6\" class=\"text-center text-danger py-8\">" + escapeHtml(error.message) + "</td></tr>";
        });
    }

    function cargarIncidencias() {
        var params = new URLSearchParams({
            estatus: valor("catalogo_incidencias_estatus"),
            origen: valor("catalogo_incidencias_origen"),
            severidad: valor("catalogo_incidencias_severidad"),
            tipo_incidencia: valor("catalogo_incidencias_tipo"),
            limite: tamanoPaginaIncidencias,
            offset: (paginaIncidencias - 1) * tamanoPaginaIncidencias
        });
        request("/catalogoerp/incidencias_calidad?" + params.toString()).then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje);
            }
            renderIncidencias(response.depurar || {});
        }).catch(function (error) {
            document.getElementById("catalogo_incidencias_tabla").innerHTML = "<tr><td colspan=\"6\" class=\"text-center text-danger py-8\">" + escapeHtml(error.message) + "</td></tr>";
        });
    }

    function sincronizarIncidencias(url) {
        Swal.fire({
            text: "Se crearan o actualizaran incidencias persistentes sin modificar productos, SKUs ni ordenes.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Sincronizar",
            cancelButtonText: "Cancelar"
        }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }
            request(url, {sincronizar: "1"}).then(function (response) {
                if (response.error) {
                    throw new Error(response.mensaje);
                }
                var r = response.depurar || {};
                Swal.fire({text: response.mensaje + ": " + Object.keys(r).map(function (k) { return k + " " + r[k]; }).join(", "), icon: "success", confirmButtonText: "Aceptar"});
                cargarAuditoria();
                cargarIncidencias();
            }).catch(function (error) {
                Swal.fire({text: error.message, icon: "error", confirmButtonText: "Aceptar"});
            });
        });
    }

    function cargarTaxonomias() {
        request("/catalogoerp/taxonomias_listar").then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje);
            }
            taxonomias = response.depurar.taxonomias || [];
            nodosTaxonomia = response.depurar.nodos || [];
            renderTaxonomias();
        }).catch(function (error) {
            document.getElementById("catalogo_taxonomia_nodos").innerHTML = "<tr><td colspan=\"4\" class=\"text-center text-danger py-8\">" + escapeHtml(error.message) + "</td></tr>";
        });
    }

    function renderTaxonomias() {
        var texto = document.getElementById("catalogo_taxonomia_buscar").value.trim().toLowerCase();
        var visibles = nodosTaxonomia.filter(function (item) {
            return item.tipo_nodo !== "raiz" && [item.nombre, item.ruta, item.codigo].join(" ").toLowerCase().indexOf(texto) !== -1;
        });
        var resumen = taxonomias.reduce(function (total, item) {
            total.clasificaciones += Number(item.total_clasificaciones || 0);
            total.categorias += Number(item.total_categorias || 0);
            total.productos += Number(item.total_productos || 0);
            return total;
        }, {clasificaciones: 0, categorias: 0, productos: 0});
        document.getElementById("catalogo_taxonomia_resumen").innerHTML = taxonomias.length
            ? "<span class=\"badge badge-light-primary fs-7\">Árboles heredados " + taxonomias.length + "</span>" +
              "<span class=\"badge badge-light-info fs-7\">Clasificaciones " + escapeHtml(resumen.clasificaciones) + "</span>" +
              "<span class=\"badge badge-light-success fs-7\">Categorías operativas " + escapeHtml(resumen.categorias) + "</span>" +
              "<span class=\"badge badge-light-warning fs-7\">Productos vinculados " + escapeHtml(resumen.productos) + "</span>"
            : "<span class=\"text-muted fs-7\">Aún no hay clasificaciones heredadas sincronizadas</span>";
        document.getElementById("catalogo_taxonomia_nodos").innerHTML = visibles.map(function (item) {
            var sangria = Math.max(0, Number(item.nivel || 0) - 1) * 24;
            var tipo = item.tipo_nodo === "clasificacion" ? "Clasificación" : "Categoría";
            var clase = item.tipo_nodo === "clasificacion" ? "badge-light-primary" : "badge-light-success";
            return "<tr><td><div style=\"padding-left:" + sangria + "px\"><span class=\"fw-bold\">" + escapeHtml(item.nombre) + "</span><div class=\"text-muted fs-8\">" + escapeHtml(item.ruta) + "</div></div></td>" +
                "<td><span class=\"badge " + clase + "\">" + tipo + "</span></td>" +
                "<td>" + (item.id_categoria_erp ? "<span class=\"badge badge-light\">#" + escapeHtml(item.id_categoria_erp) + "</span>" : "-") + "</td>" +
                "<td class=\"text-end fw-bold\">" + escapeHtml(item.total_productos || 0) + "</td></tr>";
        }).join("") || "<tr><td colspan=\"4\" class=\"text-center text-muted py-8\">Sin nodos con este filtro</td></tr>";
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-29
     * Proposito: confirma la conversion de clasificacion heredada a categorias maestras antes de escribir datos.
     * Impacto: Catalogo ERP; evita ejecutar saneamiento masivo por clic accidental.
     */
    function sincronizarTaxonomiaEcommerce() {
        Swal.fire({
            text: "Se prepararán categorías maestras desde clasificación heredada y se asignará categoría principal solo a productos que aún no tengan una.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Preparar",
            cancelButtonText: "Cancelar"
        }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }
            request("/catalogoerp/taxonomia_ecommerce_sincronizar", {sincronizar: "1"}).then(function (response) {
                if (response.error) {
                    throw new Error(response.mensaje);
                }
                var r = response.depurar || {};
                Swal.fire({
                    text: "Clasificaciones: " + (r.clasificaciones || 0) + ". Categorías operativas: " + (r.ramas_categoria || 0) + ". Productos vinculados: " + (r.productos_vinculados || 0) + ". Categorías principales asignadas: " + (r.categorias_principales_asignadas || 0) + ".",
                    icon: "success",
                    confirmButtonText: "Aceptar"
                });
                cargarTaxonomias();
                cargar();
            }).catch(function (error) {
                Swal.fire({text: error.message, icon: "error", confirmButtonText: "Aceptar"});
            });
        });
    }

    function prepararArbolCategorias() {
        request("/catalogoerp/categorias_arbol_preparar", {preparar: "1"}).then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje);
            }
            var r = response.depurar || {};
            Swal.fire({
                text: "Familias: " + (r.familias || 0) + ". Categorías operativas: " + (r.categorias_operativas || 0) + ". Clasificación heredada identificada: " + (r.categorias_legado || 0) + ".",
                icon: "success",
                confirmButtonText: "Aceptar"
            });
            cargar();
        }).catch(function (error) {
            Swal.fire({text: error.message, icon: "error", confirmButtonText: "Aceptar"});
        });
    }

    function sincronizarRelacionesCategorias() {
        request("/catalogoerp/categorias_relaciones_sincronizar", {sincronizar: "1"}).then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje);
            }
            var r = response.depurar || {};
            Swal.fire({
                text: "Categorías históricas relacionadas: " + (r.categorias_legado_mapeadas || 0) +
                    ". Productos clasificados: " + (r.productos_con_categoria_maestra || 0) +
                    ". Relaciones nuevas: " + (r.relaciones_producto_creadas || 0) + ".",
                icon: "success",
                confirmButtonText: "Aceptar"
            });
            cargar();
            cargarAuditoria();
        }).catch(function (error) {
            Swal.fire({text: error.message, icon: "error", confirmButtonText: "Aceptar"});
        });
    }

    function sincronizarMetadatos() {
        request("/catalogoerp/metadatos_sincronizar", {sincronizar: "1"}).then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje);
            }
            var r = response.depurar || {};
            Swal.fire({
                text: "Marcas asignadas: " + (r.marcas_asignadas || 0) + ". Categorías asignadas: " + (r.categorias_asignadas || 0) + ".",
                icon: "success",
                confirmButtonText: "Aceptar"
            });
            cargar();
            cargarAuditoria();
        }).catch(function (error) {
            Swal.fire({text: error.message, icon: "error", confirmButtonText: "Aceptar"});
        });
    }

    function cargarRevisionMetadatos() {
        request("/catalogoerp/metadatos_revision").then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje);
            }
            categoriasRevision = response.depurar.categorias || [];
            marcasRevision = response.depurar.marcas || [];
            revisionMetadatos = (response.depurar.pendientes || []).map(function (item) {
                item.clave_revision = item.tipo_revision + ":" + item.id_producto_erp;
                item.seleccionado = item.tipo_revision === "categoria";
                item.id_categoria_erp = "";
                item.id_marca_erp = "";
                return item;
            });
            document.getElementById("catalogo_clasificacion_categoria_masiva").innerHTML =
                "<option value=\"\">Categoría para seleccionados</option>" + categoriasRevision.map(function (item) {
                    return "<option value=\"" + escapeHtml(item.id_categoria_erp) + "\">" + escapeHtml(item.ruta || item.nombre) + "</option>";
                }).join("");
            paginaClasificacion = 1;
            renderRevisionMetadatos();
        }).catch(function (error) {
            document.getElementById("catalogo_clasificacion_pendientes").innerHTML = "<tr><td colspan=\"5\" class=\"text-center text-danger py-8\">" + escapeHtml(error.message) + "</td></tr>";
        });
    }

    function revisionMetadatosVisible() {
        var texto = document.getElementById("catalogo_clasificacion_buscar").value.trim().toLowerCase();
        var filtro = document.getElementById("catalogo_clasificacion_filtro").value;
        return revisionMetadatos.filter(function (item) {
            return (!filtro || item.tipo_revision === filtro) &&
                [item.codigo_producto, item.nombre, item.skus, item.marcas_candidatas].join(" ").toLowerCase().indexOf(texto) !== -1;
        });
    }

    function opcionesRevision(items, idKey, textoKey, valor) {
        return "<option value=\"\">Seleccionar</option>" + items.map(function (item) {
            var texto = item.ruta || item[textoKey];
            return "<option value=\"" + escapeHtml(item[idKey]) + "\"" + (String(valor) === String(item[idKey]) ? " selected" : "") + ">" + escapeHtml(texto) + "</option>";
        }).join("");
    }

    function renderRevisionMetadatos() {
        var visibles = revisionMetadatosVisible();
        var totalPaginas = Math.max(1, Math.ceil(visibles.length / tamanoPaginaCostos));
        if (paginaClasificacion > totalPaginas) {
            paginaClasificacion = totalPaginas;
        }
        var inicio = (paginaClasificacion - 1) * tamanoPaginaCostos;
        var pagina = visibles.slice(inicio, inicio + tamanoPaginaCostos);
        var sinCategoria = revisionMetadatos.filter(function (x) { return x.tipo_revision === "categoria"; }).length;
        var marcasAmbiguas = revisionMetadatos.filter(function (x) { return x.tipo_revision === "marca"; }).length;
        document.getElementById("catalogo_clasificacion_resumen").innerHTML =
            "<span class=\"badge badge-light-warning fs-7\">Sin categoría principal " + sinCategoria + "</span>" +
            "<span class=\"badge badge-light-danger fs-7\">Marcas ambiguas " + marcasAmbiguas + "</span>" +
            "<span class=\"badge badge-light-info fs-7\">Con asignación " + revisionMetadatos.filter(function (x) { return x.id_categoria_erp || x.id_marca_erp; }).length + "</span>";
        document.getElementById("catalogo_clasificacion_pendientes").innerHTML = pagina.map(function (item) {
            var esCategoria = item.tipo_revision === "categoria";
            var pendiente = esCategoria
                ? "<span class=\"badge badge-light-warning\">Sin categoría principal</span>"
                : "<span class=\"badge badge-light-danger\">Marca ambigua</span><div class=\"text-muted fs-8 mt-1\">" + escapeHtml(item.marcas_candidatas || "") + "</div>";
            var select = esCategoria
                ? "<select class=\"form-select form-select-sm\" data-clasificacion-categoria=\"" + escapeHtml(item.clave_revision) + "\">" + opcionesRevision(categoriasRevision, "id_categoria_erp", "nombre", item.id_categoria_erp) + "</select>"
                : "<select class=\"form-select form-select-sm\" data-clasificacion-marca=\"" + escapeHtml(item.clave_revision) + "\">" + opcionesRevision(marcasRevision, "id_marca_erp", "nombre", item.id_marca_erp) + "</select>";
            return "<tr><td><input class=\"form-check-input\" type=\"checkbox\" data-clasificacion-seleccionar=\"" + escapeHtml(item.clave_revision) + "\"" + (item.seleccionado ? " checked" : "") + "></td>" +
                "<td><div class=\"fw-bold\">" + escapeHtml(item.codigo_producto) + "</div><div class=\"text-muted fs-7\">" + escapeHtml(item.nombre) + "</div></td>" +
                "<td>" + escapeHtml(item.skus || "-") + "</td><td>" + pendiente + "</td><td style=\"min-width:260px\">" + select + "</td></tr>";
        }).join("") || "<tr><td colspan=\"5\" class=\"text-center text-muted py-8\">No hay pendientes con estos filtros</td></tr>";
        document.getElementById("catalogo_clasificacion_info").textContent = visibles.length
            ? (inicio + 1) + "-" + (inicio + pagina.length) + " de " + visibles.length + " | Página " + paginaClasificacion + " de " + totalPaginas
            : "Sin resultados";
        document.getElementById("catalogo_clasificacion_anterior").disabled = paginaClasificacion <= 1;
        document.getElementById("catalogo_clasificacion_siguiente").disabled = paginaClasificacion >= totalPaginas;
        document.getElementById("catalogo_clasificacion_todos").checked = pagina.length > 0 && pagina.every(function (item) { return item.seleccionado; });
    }

    function aplicarCategoriaMasiva() {
        var idCategoria = document.getElementById("catalogo_clasificacion_categoria_masiva").value;
        if (!idCategoria) {
            Swal.fire({text: "Selecciona una categoría", icon: "warning", confirmButtonText: "Aceptar"});
            return;
        }
        revisionMetadatos.forEach(function (item) {
            if (item.seleccionado && item.tipo_revision === "categoria") {
                item.id_categoria_erp = idCategoria;
            }
        });
        renderRevisionMetadatos();
    }

    function guardarRevisionMetadatos() {
        var asignaciones = revisionMetadatos.filter(function (item) {
            return item.id_categoria_erp || item.id_marca_erp;
        }).map(function (item) {
            return {
                id_producto_erp: item.id_producto_erp,
                id_categoria_erp: item.id_categoria_erp || 0,
                id_marca_erp: item.id_marca_erp || 0,
                forzar_categoria_principal: item.id_categoria_erp ? 1 : 0
            };
        });
        if (!asignaciones.length) {
            Swal.fire({text: "Realiza al menos una asignación", icon: "warning", confirmButtonText: "Aceptar"});
            return;
        }
        request("/catalogoerp/metadatos_revision_aplicar", {asignaciones: JSON.stringify(asignaciones)}).then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje);
            }
            Swal.fire({text: response.mensaje, icon: "success", confirmButtonText: "Aceptar"});
            cargarRevisionMetadatos();
            cargarAuditoria();
        }).catch(function (error) {
            Swal.fire({text: error.message, icon: "error", confirmButtonText: "Aceptar"});
        });
    }

    function cargarPropuestasCostos() {
        request("/catalogoerp/propuestas_costos_proveedor").then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje);
            }
            propuestasCostos = response.depurar.propuestas || [];
            paginaCostos = 1;
            renderPropuestasCostos(response.depurar || {});
        }).catch(function (error) {
            document.getElementById("catalogo_costos_propuestas").innerHTML = "<tr><td colspan=\"7\" class=\"text-center text-danger py-8\">" + escapeHtml(error.message) + "</td></tr>";
        });
    }

    function cargarRelacionesProveedor() {
        request("/catalogoerp/relaciones_proveedor_historicas").then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje);
            }
            var data = response.depurar || {};
            var resumen = data.resumen || {};
            document.getElementById("catalogo_proveedores_resumen").innerHTML =
                "<span class=\"badge badge-light-primary fs-7\">Historicos " + escapeHtml(resumen.productos_historicos || 0) + "</span>" +
                "<span class=\"badge badge-light-success fs-7\">Relaciones ERP " + escapeHtml(resumen.relaciones_erp || 0) + "</span>" +
                "<span class=\"badge badge-light-info fs-7\">Coincidencias exactas " + escapeHtml(resumen.coincidencias_exactas || 0) + "</span>" +
                "<span class=\"badge badge-light-warning fs-7\">Pendientes exactos " + escapeHtml(resumen.relaciones_exactas_pendientes || 0) + "</span>" +
                "<span class=\"badge badge-light-secondary fs-7\">Sin coincidencia " + escapeHtml(resumen.productos_sin_coincidencia || 0) + "</span>";
            relacionesProveedorPendientes = data.pendientes || [];
            relacionesProveedorSeleccionadas = {};
            renderRelacionesProveedorPendientes();
        }).catch(function (error) {
            document.getElementById("catalogo_proveedores_pendientes").innerHTML = "<tr><td colspan=\"6\" class=\"text-center text-danger py-8\">" + escapeHtml(error.message) + "</td></tr>";
        });
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-25
     * Proposito: pinta coincidencias exactas proveedor-SKU con seleccion explicita del usuario.
     * Impacto: Catalogo ERP; evita aplicar relaciones de proveedor no revisadas.
     */
    function renderRelacionesProveedorPendientes() {
        var body = document.getElementById("catalogo_proveedores_pendientes");
        var seleccionarTodos = document.getElementById("catalogo_proveedores_seleccionar_todos");
        if (!body) {
            return;
        }
        body.innerHTML = relacionesProveedorPendientes.map(function (item) {
            var id = String(item.id_producto_proveedor);
            return "<tr><td><input class=\"form-check-input\" type=\"checkbox\" data-proveedor-relacion=\"" + escapeAttr(id) + "\"" + (relacionesProveedorSeleccionadas[id] ? " checked" : "") + "></td>" +
                "<td><div class=\"fw-bold\">" + escapeHtml(item.sku) + "</div><div class=\"text-muted fs-7\">" + escapeHtml(item.sku_nombre) + "</div></td>" +
                "<td><div class=\"fw-bold\">" + escapeHtml(item.sku_proveedor) + "</div><div class=\"text-muted fs-7\">" + escapeHtml(item.nombre_proveedor) + "</div></td>" +
                "<td>" + escapeHtml(item.proveedor || "-") + "</td><td>" + escapeHtml(item.lista || "-") + "</td>" +
                "<td class=\"text-end fw-bold\">$" + Number(item.costo || 0).toFixed(2) + "</td></tr>";
        }).join("") || "<tr><td colspan=\"6\" class=\"text-center text-muted py-8\">No hay coincidencias exactas pendientes</td></tr>";
        if (seleccionarTodos) {
            var visibles = relacionesProveedorPendientes.map(function (item) { return String(item.id_producto_proveedor); });
            seleccionarTodos.checked = visibles.length > 0 && visibles.every(function (id) { return relacionesProveedorSeleccionadas[id]; });
            seleccionarTodos.indeterminate = !seleccionarTodos.checked && visibles.some(function (id) { return relacionesProveedorSeleccionadas[id]; });
        }
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-25
     * Proposito: aplica solo relaciones proveedor-SKU seleccionadas desde matches exactos.
     * Impacto: Catalogo ERP; reemplaza la sincronizacion masiva ciega por un flujo asistido y auditado.
     */
    function sincronizarRelacionesProveedor() {
        var seleccionadas = Object.keys(relacionesProveedorSeleccionadas).filter(function (id) {
            return relacionesProveedorSeleccionadas[id];
        });
        if (!seleccionadas.length) {
            Swal.fire({text: "Selecciona al menos una coincidencia exacta revisada.", icon: "info", confirmButtonText: "Aceptar"});
            return;
        }
        Swal.fire({
            title: "Vincular seleccionadas",
            text: "Se crearan " + seleccionadas.length + " relacion(es) proveedor-SKU seleccionadas. Revisa unidad/factor despues si son cajas, granel o empaques.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Vincular",
            cancelButtonText: "Cancelar"
        }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }
            request("/catalogoerp/relaciones_proveedor_aplicar_seleccion", {ids_productos_proveedor: JSON.stringify(seleccionadas)}).then(function (response) {
                if (response.error) {
                    throw new Error(response.mensaje);
                }
                var r = response.depurar || {};
                Swal.fire({
                    text: "Aplicadas: " + (r.matches_aplicados || 0) + ". Nuevas: " + (r.relaciones_insertadas || 0) + ". Preferidos asignados: " + (r.preferidos_asignados || 0) + ".",
                    icon: "success",
                    confirmButtonText: "Aceptar"
                });
                relacionesProveedorSeleccionadas = {};
                cargarRelacionesProveedor();
                cargarPropuestasCostos();
                cargarAuditoria();
            }).catch(function (error) {
                Swal.fire({text: error.message, icon: "error", confirmButtonText: "Aceptar"});
            });
        });
    }

    function propuestasCostosVisibles() {
        var texto = document.getElementById("catalogo_costos_buscar").value.trim().toLowerCase();
        var filtro = document.getElementById("catalogo_costos_filtro").value;
        return propuestasCostos.filter(function (item) {
            var coincideTexto = [item.sku, item.sku_nombre, item.codigo_producto, item.producto, item.proveedor, item.lista]
                .join(" ").toLowerCase().indexOf(texto) !== -1;
            var coincideFiltro = !filtro ||
                (filtro === "confiable" && String(item.requiere_revision) !== "1") ||
                (filtro === "revision" && String(item.requiere_revision) === "1");
            return coincideTexto && coincideFiltro;
        });
    }

    function renderPropuestasCostos(resumen) {
        var visibles = propuestasCostosVisibles();
        var totalPaginas = Math.max(1, Math.ceil(visibles.length / tamanoPaginaCostos));
        if (paginaCostos > totalPaginas) {
            paginaCostos = totalPaginas;
        }
        var inicio = (paginaCostos - 1) * tamanoPaginaCostos;
        var pagina = visibles.slice(inicio, inicio + tamanoPaginaCostos);
        document.getElementById("catalogo_costos_resumen").innerHTML =
            "<span class=\"badge badge-light-primary fs-7\">Propuestas " + escapeHtml(resumen.total == null ? propuestasCostos.length : resumen.total) + "</span>" +
            "<span class=\"badge badge-light-success fs-7\">Confiables " + escapeHtml(resumen.confiables == null ? propuestasCostos.filter(function (x) { return String(x.requiere_revision) !== "1"; }).length : resumen.confiables) + "</span>" +
            "<span class=\"badge badge-light-warning fs-7\">Multiples fuentes " + escapeHtml(resumen.requieren_revision == null ? propuestasCostos.filter(function (x) { return String(x.requiere_revision) === "1"; }).length : resumen.requieren_revision) + "</span>";
        document.getElementById("catalogo_costos_propuestas").innerHTML = pagina.map(function (item) {
            var confianza = String(item.requiere_revision) === "1"
                ? "<span class=\"badge badge-light-warning\">Revisar</span><div class=\"text-muted fs-8 mt-1\">" + escapeHtml((item.costos_detectados || []).join(", ")) + "</div>"
                : "<span class=\"badge badge-light-success\">Confiable</span>";
            var margen = item.margen_estimado == null ? "-" : Number(item.margen_estimado).toFixed(2) + "%";
            return "<tr><td><div class=\"fw-bold\">" + escapeHtml(item.sku) + "</div><div class=\"text-muted fs-7\">" + escapeHtml(item.producto) + "</div></td>" +
                "<td><div>" + escapeHtml(item.proveedor || "Sin proveedor") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.lista || "") + "</div></td>" +
                "<td>$" + Number(item.costo_referencia || 0).toFixed(2) + "</td><td class=\"fw-bold text-primary\">$" + Number(item.costo_propuesto || 0).toFixed(2) + "</td>" +
                "<td>$" + Number(item.precio_venta || 0).toFixed(2) + "</td><td>" + escapeHtml(margen) + "</td><td>" + confianza + "</td></tr>";
        }).join("") || "<tr><td colspan=\"8\" class=\"text-center text-muted py-8\">Sin propuestas con estos filtros</td></tr>";
        document.getElementById("catalogo_costos_paginacion_info").textContent = visibles.length
            ? (inicio + 1) + "-" + (inicio + pagina.length) + " de " + visibles.length + " | Página " + paginaCostos + " de " + totalPaginas
            : "Sin resultados";
        document.getElementById("catalogo_costos_anterior").disabled = paginaCostos <= 1;
        document.getElementById("catalogo_costos_siguiente").disabled = paginaCostos >= totalPaginas;
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-24
     * Proposito: confirma la actualizacion del costo de referencia provisional desde listas de proveedor.
     * Impacto: Catalogo ERP; deja claro que el costo final pertenece a Proveedores/Costos/Rentabilidad.
     */
    function aplicarPropuestasCostos() {
        Swal.fire({
            title: "Sincronizar costos provisionales",
            text: "Se actualizara el costo de referencia del SKU desde listas de proveedor. Esto no sustituye costo validado, rentabilidad ni listas formales de costos.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Sincronizar",
            cancelButtonText: "Cancelar"
        }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }
            request("/catalogoerp/propuestas_costos_aplicar", {sincronizar: "1"}).then(function (response) {
                if (response.error) {
                    throw new Error(response.mensaje);
                }
                Swal.fire({text: response.mensaje, icon: "success", confirmButtonText: "Aceptar"});
                cargarPropuestasCostos();
                cargarAuditoria();
            }).catch(function (error) {
                Swal.fire({text: error.message, icon: "error", confirmButtonText: "Aceptar"});
            });
        });
    }

    function politicaReorden() {
        var data = {};
        document.querySelectorAll("[data-reorden-politica]").forEach(function (input) {
            data[input.getAttribute("data-reorden-politica")] = input.value;
        });
        return data;
    }

    function cargarPropuestasReorden() {
        request("/catalogoerp/propuestas_reorden?" + new URLSearchParams(politicaReorden()).toString()).then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje);
            }
            propuestasReorden = (response.depurar.propuestas || []).map(function (item) {
                item.seleccionado = true;
                return item;
            });
            paginaReorden = 1;
            renderPropuestasReorden();
        }).catch(function (error) {
            document.getElementById("catalogo_reorden_propuestas").innerHTML = "<tr><td colspan=\"7\" class=\"text-center text-danger py-8\">" + escapeHtml(error.message) + "</td></tr>";
        });
    }

    function renderPropuestasReorden() {
        var totalPaginas = Math.max(1, Math.ceil(propuestasReorden.length / tamanoPaginaCostos));
        if (paginaReorden > totalPaginas) {
            paginaReorden = totalPaginas;
        }
        var inicio = (paginaReorden - 1) * tamanoPaginaCostos;
        var pagina = propuestasReorden.slice(inicio, inicio + tamanoPaginaCostos);
        document.getElementById("catalogo_reorden_propuestas").innerHTML = pagina.map(function (item) {
            var actual = Number(item.actual_minimo || 0) + " / " + Number(item.actual_reorden || 0) + " / " + Number(item.actual_maximo || 0);
            var propuesto = Number(item.propuesto_minimo) + " / " + Number(item.propuesto_reorden) + " / " + Number(item.propuesto_maximo);
            return "<tr><td><input class=\"form-check-input\" type=\"checkbox\" data-reorden-seleccionar=\"" + escapeHtml(item.id_sku) + "\"" + (item.seleccionado ? " checked" : "") + "></td>" +
                "<td><div class=\"fw-bold\">" + escapeHtml(item.sku) + "</div><div class=\"text-muted fs-7\">" + escapeHtml(item.producto) + "</div></td>" +
                "<td>" + escapeHtml(item.proveedor || "-") + "</td><td><span class=\"badge badge-light-primary\">" + escapeHtml(item.rotacion) + "</span></td>" +
                "<td>" + escapeHtml(item.existencia_proveedor || "-") + "</td><td>" + escapeHtml(actual) + "</td><td class=\"fw-bold text-primary\">" + escapeHtml(propuesto) + "</td></tr>";
        }).join("") || "<tr><td colspan=\"7\" class=\"text-center text-muted py-8\">No hay SKU con rotación clasificada</td></tr>";
        document.getElementById("catalogo_reorden_info").textContent = propuestasReorden.length
            ? (inicio + 1) + "-" + (inicio + pagina.length) + " de " + propuestasReorden.length + " | Mínimo / reorden / máximo"
            : "Sin propuestas";
        document.getElementById("catalogo_reorden_anterior").disabled = paginaReorden <= 1;
        document.getElementById("catalogo_reorden_siguiente").disabled = paginaReorden >= totalPaginas;
        document.getElementById("catalogo_reorden_todos").checked = propuestasReorden.length > 0 && propuestasReorden.every(function (item) { return item.seleccionado; });
    }

    function aplicarPropuestasReorden() {
        var ids = propuestasReorden.filter(function (item) { return item.seleccionado; }).map(function (item) { return item.id_sku; });
        if (!ids.length) {
            Swal.fire({text: "Selecciona al menos un SKU", icon: "warning", confirmButtonText: "Aceptar"});
            return;
        }
        var data = politicaReorden();
        data.ids_sku = JSON.stringify(ids);
        request("/catalogoerp/propuestas_reorden_aplicar", data).then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje);
            }
            Swal.fire({text: response.mensaje, icon: "success", confirmButtonText: "Aceptar"});
            cargarPropuestasReorden();
            cargarAuditoria();
        }).catch(function (error) {
            Swal.fire({text: error.message, icon: "error", confirmButtonText: "Aceptar"});
        });
    }

    function render() {
        var buscarMarca = document.getElementById("catalogo_marcas_buscar");
        var filtroImagenMarca = document.getElementById("catalogo_marcas_imagen");
        var filtroEstatusMarca = document.getElementById("catalogo_marcas_estatus");
        var textoMarca = buscarMarca ? buscarMarca.value.trim().toLowerCase() : "";
        var imagenMarca = filtroImagenMarca ? filtroImagenMarca.value : "";
        var estatusMarca = filtroEstatusMarca ? filtroEstatusMarca.value : "";
        var marcasVisibles = datos.marcas.filter(function (x) {
            return (!imagenMarca || marcaCumpleImagen(x, imagenMarca)) &&
                (!estatusMarca || x.estatus === estatusMarca) &&
                [x.codigo, x.nombre, x.descripcion].join(" ").toLowerCase().indexOf(textoMarca) !== -1;
        });
        document.getElementById("aux_marcas").innerHTML = filas(marcasVisibles, function (x) {
            var visual = resumenVisualCatalogo("marca", x);
            return [escapeHtml(x.codigo), visual + escapeHtml(x.nombre), escapeHtml(x.descripcion || ""), estado(x.estatus), accionesMaestro("marca", x.id_marca_erp)];
        });
        renderResumenVisualMaestro("catalogo_marcas_resumen", marcasVisibles, "marca");
        var buscarCategoria = document.getElementById("catalogo_categorias_buscar");
        var filtroCategoria = document.getElementById("catalogo_categorias_filtro");
        var filtroUsoCategoria = document.getElementById("catalogo_categorias_uso");
        var filtroImagenCategoria = document.getElementById("catalogo_categorias_imagen");
        var filtroEstatusCategoria = document.getElementById("catalogo_categorias_estatus");
        var textoCategoria = buscarCategoria ? buscarCategoria.value.trim().toLowerCase() : "";
        var tipoCategoria = filtroCategoria ? filtroCategoria.value : "";
        var usoCategoria = filtroUsoCategoria ? filtroUsoCategoria.value : "";
        var imagenCategoria = filtroImagenCategoria ? filtroImagenCategoria.value : "";
        var estatusCategoria = filtroEstatusCategoria ? filtroEstatusCategoria.value : "";
        var categoriasVisibles = datos.categorias.filter(function (x) {
            return categoriaCumpleFiltroTipo(x, tipoCategoria) &&
                (!usoCategoria || categoriaCumpleUso(x, usoCategoria)) &&
                (!imagenCategoria || categoriaCumpleImagen(x, imagenCategoria)) &&
                (!estatusCategoria || x.estatus === estatusCategoria) &&
                [x.codigo, x.nombre, x.ruta].join(" ").toLowerCase().indexOf(textoCategoria) !== -1;
        });
        document.getElementById("aux_categorias").innerHTML = filas(categoriasVisibles, function (x) {
            var nombre = celdaCategoriaArbol(x);
            var uso = badgesUsoCategoria(x);
            return [escapeHtml(x.codigo), resumenVisualCatalogo("categoria", x) + nombre, escapeHtml(x.ruta || x.nombre), uso, estado(x.estatus), accionesMaestro("categoria", x.id_categoria_erp)];
        });
        renderResumenCategorias(categoriasVisibles);
        document.getElementById("aux_unidades").innerHTML = filas(datos.unidades, function (x) { return [escapeHtml(x.codigo), escapeHtml(x.nombre), escapeHtml(x.abreviatura), escapeHtml(x.tipo_magnitud), escapeHtml(x.clave_sat || ""), estado(x.estatus), accion("unidad", x.id_unidad)]; });
        document.getElementById("aux_atributos").innerHTML = filas(datos.atributos, function (x) { return [escapeHtml(x.codigo), escapeHtml(x.nombre), etiquetaTipo(x.tipo_dato), escapeHtml(x.unidad || ""), String(x.es_variante) === "1" ? "Sí" : "No", estado(x.estatus), accion("atributo", x.id_atributo_erp)]; });
        renderResumenCatalogoMaestro("catalogo_unidades_resumen", [
            ["Total", datos.unidades.length, "badge-light-primary"],
            ["Activas", contarPorEstatus(datos.unidades, "activa"), "badge-light-success"],
            ["Con SAT", datos.unidades.filter(function (x) { return !!x.clave_sat; }).length, "badge-light-info"],
            ["Inactivas", contarPorEstatus(datos.unidades, "inactiva"), "badge-light-danger"]
        ]);
        renderResumenCatalogoMaestro("catalogo_atributos_resumen", [
            ["Total", datos.atributos.length, "badge-light-primary"],
            ["Activos", contarPorEstatus(datos.atributos, "activo"), "badge-light-success"],
            ["Variantes", datos.atributos.filter(function (x) { return String(x.es_variante) === "1"; }).length, "badge-light-warning"],
            ["Lista", datos.atributos.filter(function (x) { return x.tipo_dato === "lista"; }).length, "badge-light-info"],
            ["Inactivos", contarPorEstatus(datos.atributos, "inactivo"), "badge-light-danger"]
        ]);
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-29
     * Proposito: pinta resumen compacto de catalogos maestros sin crear endpoints ni afectar persistencia.
     * Impacto: Catalogo ERP; facilita detectar catalogos incompletos o inactivos antes de editar productos.
     */
    function renderResumenCatalogoMaestro(idContenedor, tarjetas) {
        var contenedor = document.getElementById(idContenedor);
        if (!contenedor) {
            return;
        }
        contenedor.innerHTML = tarjetas.map(function (tarjeta) {
            return "<span class=\"badge " + tarjeta[2] + " fs-7\">" + escapeHtml(tarjeta[0]) + " " + escapeHtml(tarjeta[1]) + "</span>";
        }).join("");
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-29
     * Proposito: cuenta registros de catalogo maestro por estatus para resumen visual.
     * Impacto: Catalogo ERP; no modifica datos ni llama endpoints adicionales.
     */
    function contarPorEstatus(items, estatus) {
        return (items || []).filter(function (item) { return item.estatus === estatus; }).length;
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-29
     * Proposito: resume avance visual de marcas/categorias tras habilitar imagenes maestras.
     * Impacto: Catalogo ERP; facilita completar logos, iconos y portadas sin tocar productos.
     * Contrato: usa total_imagenes e imagen_principal devueltos por auxiliares_listar.
     */
    function renderResumenVisualMaestro(idContenedor, items, tipo) {
        var total = (items || []).length;
        var conImagen = (items || []).filter(function (item) { return Number(item.total_imagenes || 0) > 0 || !!item.imagen_principal; }).length;
        var sinImagen = total - conImagen;
        var estatusActivo = tipo === "atributo" ? "activo" : "activa";
        renderResumenCatalogoMaestro(idContenedor, [
            ["Total", total, "badge-light-primary"],
            ["Con imagen", conImagen, "badge-light-success"],
            ["Sin imagen", sinImagen, "badge-light-warning"],
            ["Activas", contarPorEstatus(items, estatusActivo), "badge-light-success"]
        ]);
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-29
     * Proposito: resume el estado del arbol de categorias y ofrece atajos de filtro.
     * Impacto: Catalogo ERP; permite priorizar saneamiento sin consultas adicionales ni escritura de datos.
     */
    function renderResumenCategorias(categoriasVisibles) {
        var resumen = (categoriasVisibles || []).reduce(function (acc, categoria) {
            acc.total++;
            if (String(categoria.permite_productos) === "1") { acc.operativas++; } else { acc.estructurales++; }
            if (Number(categoria.total_productos || 0) > 0) { acc.conProductos++; } else { acc.sinProductos++; }
            if (Number(categoria.total_imagenes || 0) > 0 || !!categoria.imagen_principal) { acc.conImagen++; } else { acc.sinImagen++; }
            if (categoria.estatus === "inactiva") { acc.inactivas++; }
            if (categoriaTieneTextoDanado(categoria)) { acc.textoDanado++; }
            return acc;
        }, {total: 0, operativas: 0, estructurales: 0, conProductos: 0, sinProductos: 0, conImagen: 0, sinImagen: 0, inactivas: 0, textoDanado: 0});
        var tarjetas = [
            ["", "", "Total", resumen.total, "badge-light-primary"],
            ["", "operativa", "Operativas", resumen.operativas, "badge-light-success"],
            ["", "estructural", "Estructurales", resumen.estructurales, "badge-light-info"],
            ["", "con_productos", "Con productos", resumen.conProductos, "badge-light-warning"],
            ["", "sin_productos", "Sin productos", resumen.sinProductos, "badge-light"],
            ["", "con_imagen", "Con imagen", resumen.conImagen, "badge-light-success"],
            ["", "sin_imagen", "Sin imagen", resumen.sinImagen, "badge-light-warning"],
            ["", "texto_danado", "Texto dañado", resumen.textoDanado, "badge-light-danger"],
            ["inactiva", "", "Inactivas", resumen.inactivas, "badge-light-danger"]
        ];
        document.getElementById("catalogo_categorias_resumen").innerHTML = tarjetas.map(function (tarjeta) {
            return "<button type=\"button\" class=\"btn btn-sm " + tarjeta[4] + "\" data-categoria-resumen-estatus=\"" + escapeAttr(tarjeta[0]) + "\" data-categoria-resumen-uso=\"" + escapeAttr(tarjeta[1]) + "\">" +
                escapeHtml(tarjeta[2]) + " <span class=\"badge badge-circle badge-light ms-2\">" + escapeHtml(tarjeta[3]) + "</span></button>";
        }).join("");
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-29
     * Proposito: muestra categorias como arbol maestro legible sin cambiar la estructura de datos.
     * Impacto: Catalogo ERP; facilita crear categorias raiz y subcategorias para clasificar productos migrados.
     */
    function celdaCategoriaArbol(categoria) {
        var nivel = Math.max(0, Number(categoria.nivel || 0));
        var sangria = nivel * 22;
        var icono = nivel === 0 ? "bi-folder2" : "bi-arrow-return-right";
        var etiquetaNivel = nivel === 0 ? "Raiz" : "Nivel " + nivel;
        return "<div style=\"padding-left:" + sangria + "px\" class=\"d-flex align-items-start gap-2\">" +
            "<i class=\"bi " + icono + " text-primary mt-1\"></i>" +
            "<div><div class=\"fw-bold\">" + escapeHtml(categoria.nombre) + "</div>" +
            "<div class=\"text-muted fs-8\">" + escapeHtml(etiquetaNivel) + "</div></div></div>";
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-29
     * Proposito: resume uso operativo de categoria para distinguir estructura, asignacion directa y legado.
     * Impacto: Catalogo ERP; reduce errores al decidir si una categoria puede recibir productos.
     */
    function badgesUsoCategoria(categoria) {
        return "<span class=\"badge badge-light-primary me-2\">" + escapeHtml(categoria.total_productos || 0) + " productos</span>" +
            "<span class=\"badge badge-light me-2\">" + escapeHtml(categoria.total_hijas || 0) + " hijas</span>" +
            "<span class=\"badge " + (categoria.tipo_categoria === "legado_canal" ? "badge-light-warning" : "badge-light-success") + "\">" +
            (categoria.tipo_categoria === "legado_canal" ? "Legado" : (String(categoria.permite_productos) === "1" ? "Operativa" : "Estructural")) + "</span>";
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-29
     * Proposito: clasifica categorias maestras para filtros operativos sin agregar columnas ni tocar esquema.
     * Impacto: Catalogo ERP; acelera saneamiento de categorias usadas, vacias, operativas o estructurales.
     */
    function categoriaCumpleUso(categoria, uso) {
        if (uso === "operativa") {
            return String(categoria.permite_productos) === "1";
        }
        if (uso === "estructural") {
            return String(categoria.permite_productos) !== "1";
        }
        if (uso === "con_productos") {
            return Number(categoria.total_productos || 0) > 0;
        }
        if (uso === "sin_productos") {
            return Number(categoria.total_productos || 0) === 0;
        }
        if (uso === "texto_danado") {
            return categoriaTieneTextoDanado(categoria);
        }
        if (uso === "con_imagen" || uso === "sin_imagen") {
            return categoriaCumpleImagen(categoria, uso);
        }
        return true;
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-11
     * Proposito: separa el arbol principal ERP de categorias heredadas de ecommerce sin borrar datos.
     * Impacto: Configuracion de Catalogo ERP; muestra raices como Acuario primero y deja ECOM-CAT como legado filtrable.
     */
    function categoriaCumpleFiltroTipo(categoria, filtro) {
        if (filtro === "principal") {
            return categoria.tipo_categoria === "maestra" && !categoriaEsLegadoEcommerce(categoria);
        }
        if (filtro === "ecommerce") {
            return categoriaEsLegadoEcommerce(categoria);
        }
        if (filtro) {
            return categoria.tipo_categoria === filtro;
        }
        return true;
    }

    function categoriaEsLegadoEcommerce(categoria) {
        return categoria.tipo_categoria === "legado_canal" || /^ECOM-CAT-/i.test(String(categoria.codigo || ""));
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-29
     * Proposito: filtra categorias por avance visual sin modificar catalogos maestros.
     * Impacto: Catalogo ERP; ayuda a priorizar iconos/portadas de categorias tras crear tablas de imagenes.
     * Contrato: considera imagen_principal o total_imagenes como evidencia de imagen cargada.
     */
    function categoriaCumpleImagen(categoria, filtro) {
        var tieneImagen = Number(categoria.total_imagenes || 0) > 0 || !!categoria.imagen_principal;
        if (filtro === "con_imagen") {
            return tieneImagen;
        }
        if (filtro === "sin_imagen") {
            return !tieneImagen;
        }
        return true;
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-29
     * Proposito: filtra marcas por avance visual sin modificar catalogos maestros.
     * Impacto: Catalogo ERP; separa marcas listas visualmente de marcas pendientes de logo/imagen.
     * Contrato: considera imagen_principal o total_imagenes como evidencia de imagen cargada.
     */
    function marcaCumpleImagen(marca, filtro) {
        var tieneImagen = Number(marca.total_imagenes || 0) > 0 || !!marca.imagen_principal;
        if (filtro === "con_imagen") {
            return tieneImagen;
        }
        if (filtro === "sin_imagen") {
            return !tieneImagen;
        }
        return true;
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-29
     * Proposito: detecta texto mojibake en categorias para saneamiento visual futuro.
     * Impacto: Catalogo ERP; no modifica datos, solo habilita filtros de calidad del maestro.
     * Contrato: revisa codigo, nombre, ruta y descripcion con patrones heredados conocidos.
     */
    function categoriaTieneTextoDanado(categoria) {
        return /(Ã|Â|�|├|┬|â€™|â€œ|â€)/.test([categoria.codigo, categoria.nombre, categoria.ruta, categoria.descripcion].join(" "));
    }

    function filas(items, convertir) {
        return items.map(function (item) { return "<tr>" + convertir(item).map(function (celda, index, arr) { return "<td" + (index === arr.length - 1 ? " class=\"text-end\"" : "") + ">" + celda + "</td>"; }).join("") + "</tr>"; }).join("") || "<tr><td colspan=\"8\" class=\"text-center text-muted py-8\">Sin registros</td></tr>";
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-24
     * Proposito: muestra la auditoria por objetivo operativo sin cambiar el contrato del backend.
     * Impacto: Catalogo ERP; ayuda a distinguir pendientes de maestro, fiscal, compra, venta, inventario y calidad.
     */
    function renderAuditoria(auditoria) {
        var resumen = auditoria.resumen || {};
        var problemas = auditoria.problemas || [];
        var tarjetas = [
            ["", "Todos", problemas.length, "badge-light-primary", "Pendientes principales mostrados en esta auditoria"],
            ["maestro", "Maestro", (resumen.productos_sin_sku || 0) + (resumen.productos_sin_categoria || 0) + (resumen.variantes_sin_atributos || 0), "badge-light-primary", "Producto sin SKU, categoría o variantes incompletas"],
            ["fiscal", "Fiscal", resumen.skus_fiscal_incompleto || 0, "badge-light-danger", "SKUs con fiscal vacío o parcial"],
            ["compra", "Compra", resumen.skus_activos_sin_proveedor_activo || 0, "badge-light-warning", "SKUs activos sin proveedor activo"],
            ["inventario", "Inventario", (resumen.skus_sin_reglas || 0) + (resumen.skus_sin_reorden || 0) + (resumen.incidencias_reglas_inventario_abiertas || 0), "badge-light-info", "Reglas, reorden o incidencias de inventario"],
            ["venta", "Venta", (resumen.skus_sin_precio || 0) + (resumen.skus_activos_sin_codigo_principal || 0), "badge-light-success", "Precio provisional o código principal pendiente"],
            ["canal", "Canales", resumen.productos_sin_imagen || 0, "badge-light-warning", "Imagen o publicación pendiente"],
            ["calidad", "Calidad", (resumen.incidencias_bloqueos_criticos_abiertas || 0) + (resumen.incidencias_migracion_pendientes || 0), "badge-light-danger", "Incidencias persistentes o migración"]
        ];
        document.getElementById("catalogo_auditoria_resumen").innerHTML = tarjetas.map(function (item) {
            var activo = filtroObjetivoAuditoria === item[0] ? " border-primary bg-light-primary" : "";
            return "<div class=\"col-6 col-md-4 col-xl-3\"><button type=\"button\" class=\"border rounded p-4 h-100 w-100 text-start bg-body" + activo + "\" title=\"" + escapeAttr(item[4]) + "\" data-auditoria-objetivo=\"" + escapeAttr(item[0]) + "\"><div class=\"text-muted fs-8 text-uppercase\">" + escapeHtml(item[1]) + "</div><div class=\"fs-2 fw-bold\"><span class=\"badge " + item[3] + " fs-5\">" + escapeHtml(item[2]) + "</span></div><div class=\"text-muted fs-8\">" + escapeHtml(item[4]) + "</div></button></div>";
        }).join("");

        var visibles = problemas.filter(function (item) {
            return !filtroObjetivoAuditoria || clasificarObjetivoAuditoria(item.problema || "").id === filtroObjetivoAuditoria;
        });
        document.getElementById("catalogo_auditoria_problemas").innerHTML = visibles.map(function (item) {
            var objetivo = clasificarObjetivoAuditoria(item.problema || "");
            var clase = claseSeveridadAuditoria(item.severidad);
            var accion = item.id_producto_erp ? "<a class=\"btn btn-sm btn-light-primary\" href=\"/catalogoerp?id_producto_erp=" + encodeURIComponent(item.id_producto_erp) + "\"><i class=\"bi bi-box-arrow-up-right\"></i> Ver</a>" : "";
            return "<tr><td><span class=\"badge " + objetivo.clase + "\">" + escapeHtml(objetivo.texto) + "</span></td><td><span class=\"badge " + clase + "\">" + escapeHtml(item.severidad) + "</span></td><td>" + escapeHtml(item.problema) + "</td><td><div class=\"fw-bold\">" + escapeHtml(item.codigo_producto || "") + "</div><div class=\"text-muted fs-7\">" + escapeHtml(item.producto || "") + "</div></td><td>" + escapeHtml(item.sku || "-") + "</td><td class=\"text-end\">" + accion + "</td></tr>";
        }).join("") || "<tr><td colspan=\"6\" class=\"text-center text-muted py-8\">Sin pendientes principales de calidad</td></tr>";
    }

    function clasificarObjetivoAuditoria(problema) {
        var texto = String(problema || "").toLowerCase();
        if (texto.indexOf("fiscal") !== -1) {
            return {id: "fiscal", texto: "Fiscal", clase: "badge-light-danger"};
        }
        if (texto.indexOf("proveedor") !== -1) {
            return {id: "compra", texto: "Compra", clase: "badge-light-warning"};
        }
        if (texto.indexOf("codigo") !== -1 || texto.indexOf("precio") !== -1) {
            return {id: "venta", texto: "Venta", clase: "badge-light-success"};
        }
        if (texto.indexOf("reorden") !== -1 || texto.indexOf("regla") !== -1) {
            return {id: "inventario", texto: "Inventario", clase: "badge-light-info"};
        }
        if (texto.indexOf("imagen") !== -1) {
            return {id: "canal", texto: "Canales", clase: "badge-light-warning"};
        }
        if (texto.indexOf("sku") !== -1 || texto.indexOf("categoria") !== -1 || texto.indexOf("variante") !== -1) {
            return {id: "maestro", texto: "Maestro", clase: "badge-light-primary"};
        }
        return {id: "calidad", texto: "Calidad", clase: "badge-light-primary"};
    }

    function claseSeveridadAuditoria(severidad) {
        if (severidad === "bloqueante" || severidad === "alta") {
            return "badge-light-danger";
        }
        if (severidad === "media") {
            return "badge-light-warning";
        }
        if (severidad === "informativo") {
            return "badge-light-info";
        }
        return "badge-light-warning";
    }

    function renderIncidencias(data) {
        var incidencias = data.incidencias || [];
        var resumen = data.resumen || {};
        totalIncidencias = Number(data.total || 0);
        var estatus = resumen.por_estatus || {};
        var severidad = resumen.por_severidad || {};
        var objetivos = resumenObjetivosIncidencias(incidencias);
        if (filtroObjetivoIncidencias && !objetivos[filtroObjetivoIncidencias]) {
            filtroObjetivoIncidencias = "";
        }
        document.getElementById("catalogo_incidencias_resumen").innerHTML =
            badgeResumen("Pendientes", estatus.pendiente || 0, "badge-light-warning") +
            badgeResumen("En revision", estatus.en_revision || 0, "badge-light-info") +
            badgeResumen("Bloqueadas", estatus.bloqueada || 0, "badge-light-danger") +
            badgeResumen("Bloqueantes", severidad.bloqueante || 0, "badge-light-danger") +
            badgeResumen("Alta", severidad.alta || 0, "badge-light-danger") +
            badgeFiltroObjetivo("Todos", "", incidencias.length, "primary") +
            badgeFiltroObjetivo("Fiscal", "fiscal", objetivos.fiscal || 0, "danger") +
            badgeFiltroObjetivo("Compra", "compra", objetivos.compra || 0, "warning") +
            badgeFiltroObjetivo("Inventario", "inventario", objetivos.inventario || 0, "info") +
            badgeFiltroObjetivo("Venta", "venta", objetivos.venta || 0, "success") +
            badgeFiltroObjetivo("Canales", "canal", objetivos.canal || 0, "warning") +
            badgeFiltroObjetivo("Maestro", "maestro", objetivos.maestro || 0, "primary");

        var visibles = incidencias.filter(function (item) {
            return !filtroObjetivoIncidencias || clasificarObjetivoIncidencia(item).id === filtroObjetivoIncidencias;
        });

        document.getElementById("catalogo_incidencias_tabla").innerHTML = visibles.map(function (item) {
            var objetivo = clasificarObjetivoIncidencia(item);
            var entidad = item.id_producto_erp
                ? "<a href=\"/catalogoerp?id_producto_erp=" + encodeURIComponent(item.id_producto_erp) + "\" class=\"fw-bold text-primary\">" + escapeHtml(item.codigo_producto || ("Producto #" + item.id_producto_erp)) + "</a>"
                : "<span class=\"fw-bold\">" + escapeHtml(item.entidad_tipo || "-") + "</span>";
            entidad += "<div class=\"text-muted fs-8\">" + escapeHtml(item.sku || item.producto || item.nombre_sku || "") + "</div>";
            var referencia = item.referencia_tipo ? "<div class=\"text-muted fs-8\">" + escapeHtml(item.referencia_tipo) + " #" + escapeHtml(item.id_referencia || "") + "</div>" : "";
            var acciones = permisos.editar
                ? "<button class=\"btn btn-sm btn-light-info me-1\" data-incidencia-accion=\"en_revision\" data-id=\"" + item.id_incidencia_calidad + "\" title=\"Tomar\"><i class=\"bi bi-person-check\"></i></button>" +
                  "<button class=\"btn btn-sm btn-light-success me-1\" data-incidencia-accion=\"resuelta\" data-id=\"" + item.id_incidencia_calidad + "\" title=\"Resolver\"><i class=\"bi bi-check2\"></i></button>" +
                  "<button class=\"btn btn-sm btn-light-warning me-1\" data-incidencia-accion=\"bloqueada\" data-id=\"" + item.id_incidencia_calidad + "\" title=\"Bloquear\"><i class=\"bi bi-pause-circle\"></i></button>" +
                  "<button class=\"btn btn-sm btn-light-danger\" data-incidencia-accion=\"descartada\" data-id=\"" + item.id_incidencia_calidad + "\" title=\"Descartar\"><i class=\"bi bi-x-lg\"></i></button>"
                : "<span class=\"text-muted fs-8\">Solo lectura</span>";
            return "<tr><td><span class=\"badge " + objetivo.clase + "\">" + escapeHtml(objetivo.texto) + "</span><div class=\"text-muted fs-8 mt-1\">" + escapeHtml(objetivo.responsable) + "</div></td><td><span class=\"badge " + claseSeveridad(item.severidad) + "\">" + escapeHtml(item.severidad) + "</span><div class=\"text-muted fs-8 mt-1\">" + escapeHtml(item.estatus) + "</div></td>" +
                "<td><div class=\"fw-bold\">" + escapeHtml(item.titulo) + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.tipo_incidencia) + "</div><div class=\"fs-8\">" + escapeHtml(item.descripcion || "") + "</div></td>" +
                "<td>" + entidad + referencia + "</td><td><span class=\"badge badge-light\">" + escapeHtml(item.origen) + "</span></td><td class=\"text-end\">" + acciones + "</td></tr>";
        }).join("") || "<tr><td colspan=\"6\" class=\"text-center text-muted py-8\">Sin incidencias con estos filtros</td></tr>";

        var inicio = totalIncidencias === 0 ? 0 : ((paginaIncidencias - 1) * tamanoPaginaIncidencias) + 1;
        var fin = Math.min(totalIncidencias, paginaIncidencias * tamanoPaginaIncidencias);
        document.getElementById("catalogo_incidencias_info").textContent = "Mostrando " + inicio + "-" + fin + " de " + totalIncidencias;
    }

    function cambiarEstatusIncidencia(id, estatus) {
        var requiereMotivo = ["resuelta", "descartada", "bloqueada"].indexOf(estatus) !== -1;
        Swal.fire({
            title: etiquetaEstatus(estatus),
            input: requiereMotivo ? "textarea" : null,
            inputPlaceholder: requiereMotivo ? "Motivo o resolución" : "",
            text: requiereMotivo ? "La decisión quedará en la resolución de la incidencia." : "La incidencia pasará a revisión.",
            icon: "question",
            showCancelButton: true,
            confirmButtonText: "Confirmar",
            cancelButtonText: "Cancelar",
            preConfirm: function (valor) {
                if (requiereMotivo && !String(valor || "").trim()) {
                    Swal.showValidationMessage("Captura el motivo o resolución");
                    return false;
                }
                return valor || "";
            }
        }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }
            request("/catalogoerp/incidencia_calidad_estatus", {
                id_incidencia_calidad: id,
                estatus: estatus,
                resolucion: result.value || ""
            }).then(function (response) {
                if (response.error) {
                    throw new Error(response.mensaje);
                }
                Swal.fire({text: response.mensaje, icon: "success", confirmButtonText: "Aceptar"});
                cargarIncidencias();
                cargarAuditoria();
            }).catch(function (error) {
                Swal.fire({text: error.message, icon: "error", confirmButtonText: "Aceptar"});
            });
        });
    }

    function badgeResumen(texto, total, clase) {
        return "<span class=\"badge " + clase + " fs-7\">" + escapeHtml(texto) + " " + escapeHtml(total) + "</span>";
    }

    function badgeFiltroObjetivo(texto, objetivo, total, color) {
        var activo = filtroObjetivoIncidencias === objetivo;
        var clase = activo ? "btn-" + color : "btn-light-" + color;
        return "<button type=\"button\" class=\"btn btn-sm " + clase + "\" data-incidencia-objetivo=\"" + escapeAttr(objetivo) + "\">" +
            escapeHtml(texto) + " <span class=\"badge badge-light ms-1\">" + escapeHtml(total) + "</span></button>";
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-24
     * Proposito: clasifica incidencias persistentes por objetivo/responsable sin tocar esquema.
     * Impacto: Catalogo ERP; facilita saber que area debe revisar cada pendiente.
     */
    function clasificarObjetivoIncidencia(item) {
        var tipo = String(item.tipo_incidencia || "").toLowerCase();
        var origen = String(item.origen || "").toLowerCase();
        var titulo = String(item.titulo || "").toLowerCase();
        var texto = [tipo, origen, titulo].join(" ");
        if (texto.indexOf("fiscal") !== -1 || texto.indexOf("xml") !== -1) {
            return {id: "fiscal", texto: "Fiscal", clase: "badge-light-danger", responsable: "Fiscal/Compras"};
        }
        if (texto.indexOf("proveedor") !== -1 || texto.indexOf("compra") !== -1) {
            return {id: "compra", texto: "Compra", clase: "badge-light-warning", responsable: "Proveedores/Compras"};
        }
        if (texto.indexOf("inventario") !== -1 || texto.indexOf("reorden") !== -1 || texto.indexOf("lote") !== -1 || texto.indexOf("caducidad") !== -1) {
            return {id: "inventario", texto: "Inventario", clase: "badge-light-info", responsable: "Almacen/Inventario"};
        }
        if (texto.indexOf("codigo") !== -1 || texto.indexOf("precio") !== -1 || texto.indexOf("venta") !== -1) {
            return {id: "venta", texto: "Venta", clase: "badge-light-success", responsable: "Ventas/Precios"};
        }
        if (texto.indexOf("imagen") !== -1 || texto.indexOf("ecommerce") !== -1 || texto.indexOf("migracion") !== -1) {
            return {id: "canal", texto: "Canales", clase: "badge-light-warning", responsable: "Catálogo/Canales"};
        }
        return {id: "maestro", texto: "Maestro", clase: "badge-light-primary", responsable: "Catalogo"};
    }

    function resumenObjetivosIncidencias(incidencias) {
        return incidencias.reduce(function (acc, item) {
            var objetivo = clasificarObjetivoIncidencia(item);
            acc[objetivo.id] = (acc[objetivo.id] || 0) + 1;
            return acc;
        }, {});
    }

    function claseSeveridad(severidad) {
        if (severidad === "bloqueante" || severidad === "alta") { return "badge-light-danger"; }
        if (severidad === "media" || severidad === "advertencia") { return "badge-light-warning"; }
        return "badge-light-info";
    }

    function etiquetaEstatus(estatus) {
        var etiquetas = {en_revision: "Tomar incidencia", resuelta: "Resolver incidencia", descartada: "Descartar incidencia", bloqueada: "Bloquear incidencia"};
        return etiquetas[estatus] || "Actualizar incidencia";
    }

    function estado(valor) { return "<span class=\"badge " + (String(valor).indexOf("inactiv") === 0 ? "badge-light-danger" : "badge-light-success") + "\">" + escapeHtml(valor) + "</span>"; }
    function accion(tipo, id) { return "<button type=\"button\" class=\"btn btn-sm btn-icon btn-light-primary\" data-editar=\"" + tipo + "\" data-id=\"" + id + "\" title=\"Editar\"><i class=\"bi bi-pencil-square\"></i></button>"; }
    function accionesMaestro(tipo, id) {
        return "<div class=\"d-flex justify-content-end gap-2\">" +
            "<button type=\"button\" class=\"btn btn-sm btn-icon btn-light-info\" data-imagen-maestro=\"" + tipo + "\" data-id=\"" + id + "\" title=\"Imágenes\"><i class=\"bi bi-image\"></i></button>" +
            accion(tipo, id) +
            "</div>";
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-29
     * Proposito: resume visualmente si una marca/categoria ya tiene imagen principal o esquema pendiente.
     * Impacto: Catalogo ERP; ayuda a completar catalogos maestros sin depender de ecommerce.
     */
    function resumenVisualCatalogo(tipo, item) {
        var disponible = datos.schema && datos.schema.imagenes_marcas_categorias;
        var total = Number(item.total_imagenes || 0);
        var url = item.imagen_principal || "";
        if (url) {
            return "<span class=\"symbol symbol-30px me-3\"><img src=\"" + escapeAttr(url) + "\" alt=\"\"></span>";
        }
        if (disponible && total > 0) {
            return "<span class=\"badge badge-light-info me-3\">" + total + " img</span>";
        }
        var titulo = disponible ? "Sin imagen" : "Esquema de imágenes pendiente";
        return "<span class=\"badge badge-light me-3\" title=\"" + escapeAttr(titulo) + "\"><i class=\"bi bi-image\"></i></span>";
    }

    function abrirNuevo() {
        form.reset();
        setValor("tipo_catalogo", tipoActual);
        setValor("id", "");
        configurarCampos(tipoActual);
        if (tipoActual === "categoria") {
            llenarPadres(0);
            setValor("tipo_categoria", "maestra");
            setValor("origen", "erp");
            marcar("permite_productos", true);
        }
        document.getElementById("catalogo_aux_titulo").textContent = "Nueva " + etiquetaCatalogoAuxiliar(tipoActual).toLowerCase();
        modal.show();
    }

    function abrirEditar(tipo, id) {
        tipoActual = tipo;
        form.reset();
        configurarCampos(tipo);
        var plural = tipo === "marca" ? "marcas" : tipo === "categoria" ? "categorias" : tipo === "unidad" ? "unidades" : "atributos";
        var keys = {marca: "id_marca_erp", categoria: "id_categoria_erp", unidad: "id_unidad", atributo: "id_atributo_erp"};
        var item = datos[plural].find(function (x) { return String(x[keys[tipo]]) === String(id); });
        setValor("tipo_catalogo", tipo); setValor("id", id);
        Object.keys(item || {}).forEach(function (key) { setValor(key, item[key]); });
        if (tipo === "categoria") {
            llenarPadres(id);
            setValor("id_categoria_padre", item ? item.id_categoria_padre : "");
        }
        setValor("opciones_lista", opcionesAtributo(item).join("\n"));
        marcar("decimales_permitidos", item && item.decimales_permitidos); marcar("es_variante", item && item.es_variante);
        marcar("permite_productos", item && item.permite_productos);
        configurarTipoAtributo();
        document.getElementById("catalogo_aux_titulo").textContent = "Editar " + tipo;
        modal.show();
    }

    function configurarCampos(tipo) {
        document.querySelectorAll(".campo").forEach(function (el) { el.classList.add("d-none"); });
        document.querySelectorAll(".campo-" + tipo).forEach(function (el) { el.classList.remove("d-none"); });
        configurarCodigoAuxiliar(tipo);
        var estados = tipo === "atributo" ? [["activo", "Activo"], ["inactivo", "Inactivo"]] : [["activa", "Activa"], ["inactiva", "Inactiva"]];
        document.getElementById("aux_estatus").innerHTML = estados.map(function (x) { return "<option value=\"" + x[0] + "\">" + x[1] + "</option>"; }).join("");
        configurarTipoAtributo();
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-11
     * Proposito: muestra codigo como opcional en marcas/categorias porque backend puede generarlo desde nombre.
     * Impacto: Configuracion de Catalogo ERP; reduce friccion al crear catálogos maestros simples.
     */
    function configurarCodigoAuxiliar(tipo) {
        var input = form ? form.querySelector("[name='codigo']") : null;
        if (!input) {
            return;
        }
        var opcional = tipo === "marca" || tipo === "categoria";
        input.required = !opcional;
        input.placeholder = opcional ? "Se genera si lo dejas vacío" : "";
        var label = input.closest(".col-md-4") ? input.closest(".col-md-4").querySelector(".form-label") : null;
        if (label) {
            label.classList.toggle("required", !opcional);
        }
    }

    function configurarTipoAtributo() {
        var tipo = form ? form.querySelector("[name='tipo_dato']").value : "texto";
        document.querySelectorAll(".campo-atributo-lista").forEach(function (el) {
            el.classList.toggle("d-none", tipo !== "lista" || tipoActual !== "atributo");
        });
    }

    function opcionesAtributo(item) {
        try {
            var configuracion = JSON.parse(item && item.configuracion_json ? item.configuracion_json : "{}");
            return Array.isArray(configuracion.opciones) ? configuracion.opciones : [];
        } catch (e) {
            return [];
        }
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-11
     * Proposito: nombra acciones de alta por catalogo maestro para evitar capturas en el tipo equivocado.
     * Impacto: Configuracion de Catalogo ERP; aclara altas de marcas, categorias, unidades y atributos.
     */
    function etiquetaCatalogoAuxiliar(tipo) {
        var etiquetas = {marca: "Marca", categoria: "Categoria", unidad: "Unidad", atributo: "Atributo"};
        return etiquetas[tipo] || "Registro";
    }

    function etiquetaTipo(tipo) {
        var etiquetas = {texto: "Texto", numero: "Número", booleano: "Sí / No", fecha: "Fecha", lista: "Lista", color: "Color"};
        return escapeHtml(etiquetas[tipo] || tipo);
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-29
     * Proposito: evita seleccionar como padre la misma categoria o cualquiera de sus descendientes.
     * Impacto: Catalogo ERP; previene ciclos visuales antes de llegar a la validacion de backend.
     */
    function llenarPadres(idCategoriaActual) {
        idCategoriaActual = Number(idCategoriaActual || 0);
        document.getElementById("aux_categoria_padre").innerHTML = "<option value=\"\">Nivel raíz</option>" + datos.categorias.filter(function (x) {
            return x.tipo_categoria === "maestra" && !categoriaEsLegadoEcommerce(x) && Number(x.id_categoria_erp) !== idCategoriaActual && !categoriaEsDescendienteFrontend(Number(x.id_categoria_erp), idCategoriaActual);
        }).map(function (x) {
            return "<option value=\"" + x.id_categoria_erp + "\">" + escapeHtml(x.ruta || x.nombre) + "</option>";
        }).join("");
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-29
     * Proposito: detecta si una categoria candidata es descendiente de la categoria editada.
     * Impacto: Catalogo ERP; bloquea ciclos desde UI y complementa la validacion del modelo.
     */
    function categoriaEsDescendienteFrontend(idCandidata, idCategoriaActual) {
        if (!idCategoriaActual || !idCandidata) {
            return false;
        }
        var visitadas = {};
        var cursor = datos.categorias.find(function (categoria) { return Number(categoria.id_categoria_erp) === idCandidata; });
        while (cursor && cursor.id_categoria_padre) {
            var idPadre = Number(cursor.id_categoria_padre);
            if (idPadre === idCategoriaActual) {
                return true;
            }
            if (visitadas[idPadre]) {
                return false;
            }
            visitadas[idPadre] = true;
            cursor = datos.categorias.find(function (categoria) { return Number(categoria.id_categoria_erp) === idPadre; });
        }
        return false;
    }

    function guardar(event) {
        event.preventDefault();
        var data = {}; new FormData(form).forEach(function (value, key) { data[key] = value; });
        request("/catalogoerp/auxiliar_guardar", data).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            modal.hide(); cargar(); Swal.fire({text: response.mensaje, icon: "success", confirmButtonText: "Aceptar"});
        }).catch(function (error) {
            var box = document.getElementById("catalogo_aux_error"); box.textContent = error.message; box.classList.remove("d-none");
        });
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-29
     * Proposito: abre el gestor de imagenes de marca/categoria con candado de esquema pendiente.
     * Impacto: Catalogo ERP; prepara captura visual sin ejecutar DDL desde la UI.
     */
    function abrirImagenesMaestro(tipo, id) {
        var item = obtenerItemCatalogo(tipo, id);
        imagenMaestroActual = {tipo: tipo, id: Number(id), nombre: item ? item.nombre : "", schema: false, tipos: [], imagenes: []};
        limpiarFormularioImagenMaestro();
        document.getElementById("catalogo_imagen_maestro_titulo").textContent = "Imágenes de " + (tipo === "marca" ? "marca" : "categoría") + ": " + (imagenMaestroActual.nombre || ("#" + id));
        modalImagenMaestro.show();
        cargarImagenesMaestro();
    }

    function cargarImagenesMaestro() {
        var params = new URLSearchParams({tipo_entidad: imagenMaestroActual.tipo, id_entidad: imagenMaestroActual.id});
        request("/catalogoerp/imagenes_maestro_listar?" + params.toString()).then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje);
            }
            var depurar = response.depurar || {};
            imagenMaestroActual.schema = !!depurar.schema_disponible;
            imagenMaestroActual.tipos = depurar.tipos_permitidos || tiposImagenMaestro(imagenMaestroActual.tipo);
            imagenMaestroActual.imagenes = depurar.imagenes || [];
            renderImagenesMaestro(response.mensaje);
        }).catch(function (error) {
            document.getElementById("catalogo_imagen_maestro_lista").innerHTML = "<tr><td colspan=\"5\" class=\"text-center text-danger py-8\">" + escapeHtml(error.message) + "</td></tr>";
        });
    }

    function renderImagenesMaestro(mensaje) {
        var aviso = document.getElementById("catalogo_imagen_maestro_schema");
        aviso.classList.toggle("d-none", imagenMaestroActual.schema);
        aviso.textContent = imagenMaestroActual.schema ? "" : (mensaje || "Esquema de imágenes de marcas/categorías pendiente.");
        var puedeEditar = imagenMaestroActual.schema && permisos.editar;
        formImagenMaestro.querySelectorAll("input, select, button[type='submit']").forEach(function (input) {
            input.disabled = !puedeEditar;
        });
        llenarTiposImagenMaestro();
        document.getElementById("catalogo_imagen_maestro_lista").innerHTML = imagenMaestroActual.imagenes.map(function (item) {
            var vista = item.url_imagen ? "<span class=\"symbol symbol-50px\"><img src=\"" + escapeAttr(item.url_imagen) + "\" alt=\"\"></span>" : "-";
            var acciones = imagenMaestroActual.schema && permisos.editar
                ? "<button type=\"button\" class=\"btn btn-sm btn-icon btn-light-primary me-1\" data-imagen-editar=\"" + escapeAttr(item.id_imagen) + "\" title=\"Editar\"><i class=\"bi bi-pencil-square\"></i></button>" +
                  "<button type=\"button\" class=\"btn btn-sm btn-icon btn-light-danger\" data-imagen-desactivar=\"" + escapeAttr(item.id_imagen) + "\" title=\"Desactivar\"><i class=\"bi bi-x-lg\"></i></button>"
                : "<span class=\"text-muted fs-8\">Solo lectura</span>";
            return "<tr><td>" + vista + "</td><td><span class=\"badge badge-light-primary\">" + escapeHtml(item.tipo_imagen) + "</span><div class=\"text-muted fs-8\">Orden " + escapeHtml(item.orden || 0) + "</div></td><td class=\"text-break\">" + escapeHtml(item.url_imagen || "") + "<div class=\"text-muted fs-8\">" + escapeHtml(item.texto_alternativo || "") + "</div></td><td>" + estado(item.estatus) + "</td><td class=\"text-end\">" + acciones + "</td></tr>";
        }).join("") || "<tr><td colspan=\"5\" class=\"text-center text-muted py-8\">" + (imagenMaestroActual.schema ? "Sin imágenes registradas" : "Esquema pendiente") + "</td></tr>";
    }

    function guardarImagenMaestro(event) {
        event.preventDefault();
        if (!imagenMaestroActual.schema) {
            Swal.fire({text: "Primero aplica el esquema autorizado de imágenes de marcas/categorías", icon: "warning", confirmButtonText: "Aceptar"});
            return;
        }
        var data = {}; new FormData(formImagenMaestro).forEach(function (value, key) { data[key] = value; });
        request("/catalogoerp/imagen_maestro_guardar", data).then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje);
            }
            limpiarFormularioImagenMaestro();
            cargarImagenesMaestro();
            cargar();
            Swal.fire({text: response.mensaje, icon: "success", confirmButtonText: "Aceptar"});
        }).catch(function (error) {
            var box = document.getElementById("catalogo_imagen_maestro_error");
            box.textContent = error.message;
            box.classList.remove("d-none");
        });
    }

    function editarImagenMaestro(idImagen) {
        var item = imagenMaestroActual.imagenes.find(function (imagen) { return String(imagen.id_imagen) === String(idImagen); });
        if (!item) {
            return;
        }
        setValorImagen("id_imagen", item.id_imagen);
        setValorImagen("tipo_imagen", item.tipo_imagen);
        setValorImagen("url_imagen", item.url_imagen);
        setValorImagen("texto_alternativo", item.texto_alternativo);
        setValorImagen("orden", item.orden);
        setValorImagen("estatus", item.estatus);
    }

    function desactivarImagenMaestro(idImagen) {
        Swal.fire({
            text: "La imagen se inactivará sin borrarse físicamente.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Desactivar",
            cancelButtonText: "Cancelar"
        }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }
            request("/catalogoerp/imagen_maestro_desactivar", {
                tipo_entidad: imagenMaestroActual.tipo,
                id_entidad: imagenMaestroActual.id,
                id_imagen: idImagen
            }).then(function (response) {
                if (response.error) {
                    throw new Error(response.mensaje);
                }
                cargarImagenesMaestro();
                cargar();
            }).catch(function (error) {
                Swal.fire({text: error.message, icon: "error", confirmButtonText: "Aceptar"});
            });
        });
    }

    function limpiarFormularioImagenMaestro() {
        if (!formImagenMaestro) {
            return;
        }
        formImagenMaestro.reset();
        setValorImagen("tipo_entidad", imagenMaestroActual.tipo);
        setValorImagen("id_entidad", imagenMaestroActual.id);
        setValorImagen("id_imagen", "");
        setValorImagen("orden", 0);
        setValorImagen("estatus", "activo");
        document.getElementById("catalogo_imagen_maestro_error").classList.add("d-none");
        llenarTiposImagenMaestro();
    }

    function llenarTiposImagenMaestro() {
        var tipos = imagenMaestroActual.tipos && imagenMaestroActual.tipos.length ? imagenMaestroActual.tipos : tiposImagenMaestro(imagenMaestroActual.tipo);
        document.getElementById("catalogo_imagen_maestro_tipo").innerHTML = tipos.map(function (tipo) {
            return "<option value=\"" + escapeAttr(tipo) + "\">" + escapeHtml(etiquetaTipoImagenMaestro(tipo)) + "</option>";
        }).join("");
    }

    function tiposImagenMaestro(tipoEntidad) {
        return tipoEntidad === "marca" ? ["logo", "banner", "referencia"] : ["icono", "portada", "referencia"];
    }

    function etiquetaTipoImagenMaestro(tipoImagen) {
        var etiquetas = {logo: "Logo", banner: "Banner", referencia: "Referencia", icono: "Icono", portada: "Portada"};
        return etiquetas[tipoImagen] || tipoImagen;
    }

    function obtenerItemCatalogo(tipo, id) {
        var lista = tipo === "marca" ? datos.marcas : datos.categorias;
        var key = tipo === "marca" ? "id_marca_erp" : "id_categoria_erp";
        return (lista || []).find(function (item) { return String(item[key]) === String(id); });
    }

    function setValorImagen(name, value) {
        var input = formImagenMaestro.querySelector("[name='" + name + "']");
        if (input) {
            input.value = value == null ? "" : value;
        }
    }

    function setValor(name, value) { var input = form.querySelector("[name='" + name + "']"); if (input) { input.value = value == null ? "" : value; } }
    function marcar(name, value) { var input = form.querySelector("[name='" + name + "']"); if (input) { input.checked = String(value) === "1"; } }
    function valor(id) { var el = document.getElementById(id); return el ? el.value : ""; }
    function escapeHtml(value) { var div = document.createElement("div"); div.textContent = value == null ? "" : String(value); return div.innerHTML; }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-24
     * Proposito: escapa texto usado en atributos HTML generados por la UI de configuracion.
     * Impacto: Catalogo ERP; evita errores al renderizar auditoria por objetivo.
     */
    function escapeAttr(value) { return escapeHtml(value).replace(/"/g, "&quot;").replace(/'/g, "&#39;"); }

    function aplicarPermisosFrontend() {
        document.querySelectorAll("[data-permiso-editar]").forEach(function (el) {
            el.classList.toggle("d-none", !permisos.editar);
        });
        document.querySelectorAll("[data-permiso-costos]").forEach(function (el) {
            el.classList.toggle("d-none", !permisos.costos);
        });
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-24
     * Proposito: inicializa proveedor/costos solo cuando el perfil tiene permiso explicito.
     * Impacto: Catalogo ERP; evita llamadas a endpoints de costos desde perfiles de captura normal.
     */
    function iniciarProveedorCostos() {
        if (!permisos.costos) {
            return;
        }
        if (proveedorCostosInicializado) {
            return;
        }
        proveedorCostosInicializado = true;
        document.getElementById("catalogo_costos_buscar").addEventListener("input", function () { paginaCostos = 1; renderPropuestasCostos({}); });
        document.getElementById("catalogo_costos_filtro").addEventListener("change", function () { paginaCostos = 1; renderPropuestasCostos({}); });
        document.getElementById("catalogo_costos_anterior").addEventListener("click", function () { paginaCostos = Math.max(1, paginaCostos - 1); renderPropuestasCostos({}); });
        document.getElementById("catalogo_costos_siguiente").addEventListener("click", function () { paginaCostos += 1; renderPropuestasCostos({}); });
        document.getElementById("catalogo_costos_aplicar").addEventListener("click", aplicarPropuestasCostos);
        document.getElementById("catalogo_proveedores_sincronizar").addEventListener("click", sincronizarRelacionesProveedor);
        document.getElementById("catalogo_proveedores_seleccionar_todos").addEventListener("change", function (event) {
            relacionesProveedorPendientes.forEach(function (item) {
                relacionesProveedorSeleccionadas[String(item.id_producto_proveedor)] = event.target.checked;
            });
            renderRelacionesProveedorPendientes();
        });
        document.getElementById("catalogo_proveedores_pendientes").addEventListener("change", function (event) {
            var input = event.target.closest("[data-proveedor-relacion]");
            if (!input) {
                return;
            }
            relacionesProveedorSeleccionadas[input.getAttribute("data-proveedor-relacion")] = input.checked;
            renderRelacionesProveedorPendientes();
        });
        cargarRelacionesProveedor();
        cargarPropuestasCostos();
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-29
     * Proposito: centraliza la marca de cargas ya ejecutadas para evitar duplicar listeners o consultas.
     * Impacto: Catalogo ERP; mantiene estable la pantalla aunque una seccion entre y salga del viewport.
     */
    function ejecutarCargaDiferida(clave, cargarCallback) {
        if (cargasDiferidas[clave]) {
            return;
        }
        cargasDiferidas[clave] = true;
        cargarCallback();
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-29
     * Proposito: muestra un solo modulo funcional dentro de Configuracion de Catalogo.
     * Impacto: Catalogo ERP; evita que maestros, calidad, costos, taxonomias y reglas compitan visualmente en una sola pantalla.
     * Contrato: el modulo debe existir en modulosConfiguracion; si no, se usa Catalogos maestros.
     */
    function activarModuloConfiguracion(modulo, actualizarHash) {
        if (modulosConfiguracion.indexOf(modulo) === -1) {
            modulo = "maestros";
        }
        document.querySelectorAll("[data-config-modulo]").forEach(function (elemento) {
            elemento.hidden = elemento.getAttribute("data-config-modulo") !== modulo;
        });
        document.querySelectorAll("[data-config-modulo-boton]").forEach(function (boton) {
            var activo = boton.getAttribute("data-config-modulo-boton") === modulo;
            boton.classList.toggle("btn-primary", activo);
            boton.classList.toggle("btn-light", !activo);
        });
        var botonNuevo = document.getElementById("catalogo_aux_nuevo");
        if (botonNuevo) {
            botonNuevo.classList.toggle("d-none", modulo !== "maestros");
        }
        cargarModuloConfiguracion(modulo);
        if (actualizarHash) {
            window.history.replaceState(null, "", "#config-" + modulo);
        }
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-29
     * Proposito: ejecuta la carga de datos correspondiente al modulo activo y solo una vez por sesion de pantalla.
     * Impacto: Catalogo ERP; reduce tiempo de entrada a Configuracion y mantiene cada bloque bajo demanda.
     */
    function cargarModuloConfiguracion(modulo) {
        if (modulo === "maestros") {
            ejecutarCargaDiferida("maestros", cargar);
        } else if (modulo === "calidad") {
            ejecutarCargaDiferida("calidad", function () {
                cargarAuditoria();
                cargarIncidencias();
            });
        } else if (modulo === "clasificacion") {
            ejecutarCargaDiferida("clasificacion", cargarRevisionMetadatos);
        } else if (modulo === "clasificacion_heredada") {
            ejecutarCargaDiferida("taxonomias", cargarTaxonomias);
        } else if (modulo === "reglas") {
            ejecutarCargaDiferida("reorden", cargarPropuestasReorden);
        } else if (modulo === "proveedor_costos" && permisos.costos) {
            ejecutarCargaDiferida("proveedor_costos", iniciarProveedorCostos);
        }
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-29
     * Proposito: permite abrir Configuracion directamente en un modulo con hashes tipo #config-maestros.
     * Impacto: Catalogo ERP; facilita continuidad de trabajo y evita navegar manualmente en pantallas largas.
     */
    function moduloConfiguracionDesdeHash() {
        var hash = (window.location.hash || "").replace("#config-", "");
        if (hash === "navegacion") {
            hash = "clasificacion_heredada";
        }
        return modulosConfiguracion.indexOf(hash) !== -1 ? hash : "maestros";
    }

    function activarTabDesdeHash() {
        var hash = window.location.hash || "";
        if (!hash || hash.indexOf("#config-") === 0) {
            return;
        }
        var tab = document.querySelector("[data-bs-target='" + hash + "']");
        if (!tab) {
            return;
        }
        var tipo = tab.getAttribute("data-tipo");
        if (tipo) {
            tipoActual = tipo;
        }
        bootstrap.Tab.getOrCreateInstance(tab).show();
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-29
     * Proposito: muestra acciones masivas de categorias solo cuando el usuario trabaja en esa pestaña.
     * Impacto: Catalogo ERP; evita confundir acciones de arbol maestro con marcas, unidades o atributos.
     */
    function actualizarAccionesCatalogosMaestros() {
        var botonNuevo = document.getElementById("catalogo_aux_nuevo");
        if (botonNuevo) {
            botonNuevo.innerHTML = "<i class=\"bi bi-plus-lg\"></i> Nueva " + etiquetaCatalogoAuxiliar(tipoActual).toLowerCase();
        }
        var accionesMarcas = document.getElementById("catalogo_marcas_acciones");
        if (accionesMarcas) {
            accionesMarcas.classList.toggle("d-none", tipoActual !== "marca");
        }
        var accionesCategorias = document.getElementById("catalogo_categorias_acciones");
        if (accionesCategorias) {
            accionesCategorias.classList.toggle("d-none", tipoActual !== "categoria");
        }
    }

    document.addEventListener("DOMContentLoaded", function () {
        form = document.getElementById("catalogo_aux_form"); modal = new bootstrap.Modal(document.getElementById("catalogo_aux_modal"));
        formImagenMaestro = document.getElementById("catalogo_imagen_maestro_form");
        modalImagenMaestro = new bootstrap.Modal(document.getElementById("catalogo_imagen_maestro_modal"));
        aplicarPermisosFrontend();
        document.getElementById("catalogo_aux_nuevo").addEventListener("click", abrirNuevo);
        document.getElementById("catalogo_auditoria_recargar").addEventListener("click", cargarAuditoria);
        document.getElementById("catalogo_auditoria_resumen").addEventListener("click", function (event) {
            var button = event.target.closest("[data-auditoria-objetivo]");
            if (!button) {
                return;
            }
            filtroObjetivoAuditoria = button.getAttribute("data-auditoria-objetivo") || "";
            cargarAuditoria();
        });
        document.getElementById("catalogo_metadatos_sincronizar").addEventListener("click", sincronizarMetadatos);
        document.getElementById("catalogo_incidencias_recargar").addEventListener("click", cargarIncidencias);
        document.getElementById("catalogo_incidencias_sync_bloqueos").addEventListener("click", function () { sincronizarIncidencias("/catalogoerp/incidencias_bloqueos_criticos_sincronizar"); });
        document.getElementById("catalogo_incidencias_sync_compras").addEventListener("click", function () { sincronizarIncidencias("/catalogoerp/incidencias_compras_xml_sincronizar"); });
        document.getElementById("catalogo_incidencias_resumen").addEventListener("click", function (event) {
            var button = event.target.closest("[data-incidencia-objetivo]");
            if (!button) {
                return;
            }
            filtroObjetivoIncidencias = button.getAttribute("data-incidencia-objetivo") || "";
            cargarIncidencias();
        });
        ["catalogo_incidencias_estatus", "catalogo_incidencias_origen", "catalogo_incidencias_severidad", "catalogo_incidencias_tipo"].forEach(function (id) {
            document.getElementById(id).addEventListener("change", function () {
                paginaIncidencias = 1;
                filtroObjetivoIncidencias = "";
                cargarIncidencias();
            });
        });
        document.getElementById("catalogo_incidencias_anterior").addEventListener("click", function () {
            paginaIncidencias = Math.max(1, paginaIncidencias - 1);
            cargarIncidencias();
        });
        document.getElementById("catalogo_incidencias_siguiente").addEventListener("click", function () {
            if (paginaIncidencias * tamanoPaginaIncidencias < totalIncidencias) {
                paginaIncidencias += 1;
                cargarIncidencias();
            }
        });
        document.getElementById("catalogo_incidencias_tabla").addEventListener("click", function (event) {
            var boton = event.target.closest("[data-incidencia-accion]");
            if (!boton) {
                return;
            }
            cambiarEstatusIncidencia(boton.getAttribute("data-id"), boton.getAttribute("data-incidencia-accion"));
        });
        document.getElementById("catalogo_taxonomia_sincronizar").addEventListener("click", sincronizarTaxonomiaEcommerce);
        document.getElementById("catalogo_taxonomia_buscar").addEventListener("input", renderTaxonomias);
        // IA: Codex GPT-5 | Fecha: 2026-07-11 | Proposito: alta directa de marcas desde la pestana Marcas.
        document.getElementById("catalogo_marca_nueva").addEventListener("click", function () {
            tipoActual = "marca";
            abrirNuevo();
        });
        // IA: Codex GPT-5 | Fecha: 2026-07-03 | Proposito: alta directa de categorias desde la pestana Categorias.
        document.getElementById("catalogo_categoria_nueva").addEventListener("click", function () {
            tipoActual = "categoria";
            abrirNuevo();
        });
        document.getElementById("catalogo_categorias_preparar").addEventListener("click", prepararArbolCategorias);
        document.getElementById("catalogo_categorias_relacionar").addEventListener("click", sincronizarRelacionesCategorias);
        ["catalogo_marcas_buscar", "catalogo_marcas_imagen", "catalogo_marcas_estatus"].forEach(function (id) {
            document.getElementById(id).addEventListener(id === "catalogo_marcas_buscar" ? "input" : "change", render);
        });
        document.getElementById("catalogo_categorias_buscar").addEventListener("input", render);
        document.getElementById("catalogo_categorias_filtro").addEventListener("change", render);
        document.getElementById("catalogo_clasificacion_buscar").addEventListener("input", function () { paginaClasificacion = 1; renderRevisionMetadatos(); });
        document.getElementById("catalogo_clasificacion_filtro").addEventListener("change", function () { paginaClasificacion = 1; renderRevisionMetadatos(); });
        document.getElementById("catalogo_clasificacion_anterior").addEventListener("click", function () { paginaClasificacion = Math.max(1, paginaClasificacion - 1); renderRevisionMetadatos(); });
        document.getElementById("catalogo_clasificacion_siguiente").addEventListener("click", function () { paginaClasificacion += 1; renderRevisionMetadatos(); });
        document.getElementById("catalogo_clasificacion_aplicar_categoria").addEventListener("click", aplicarCategoriaMasiva);
        document.getElementById("catalogo_clasificacion_guardar").addEventListener("click", guardarRevisionMetadatos);
        document.getElementById("catalogo_clasificacion_pendientes").addEventListener("change", function (event) {
            var claveRevision = event.target.getAttribute("data-clasificacion-seleccionar") ||
                event.target.getAttribute("data-clasificacion-categoria") ||
                event.target.getAttribute("data-clasificacion-marca");
            if (!claveRevision) {
                return;
            }
            revisionMetadatos.forEach(function (item) {
                if (item.clave_revision !== claveRevision) {
                    return;
                }
                if (event.target.matches("[data-clasificacion-seleccionar]")) {
                    item.seleccionado = event.target.checked;
                } else if (event.target.matches("[data-clasificacion-categoria]")) {
                    item.id_categoria_erp = event.target.value;
                } else if (event.target.matches("[data-clasificacion-marca]")) {
                    item.id_marca_erp = event.target.value;
                }
            });
            renderRevisionMetadatos();
        });
        document.getElementById("catalogo_clasificacion_todos").addEventListener("change", function () {
            var pagina = revisionMetadatosVisible().slice((paginaClasificacion - 1) * tamanoPaginaCostos, paginaClasificacion * tamanoPaginaCostos);
            var ids = pagina.map(function (item) { return item.clave_revision; });
            var seleccionar = this.checked;
            revisionMetadatos.forEach(function (item) {
                if (ids.indexOf(item.clave_revision) !== -1) {
                    item.seleccionado = seleccionar;
                }
            });
            renderRevisionMetadatos();
        });
        document.querySelectorAll("[data-reorden-politica]").forEach(function (input) {
            input.addEventListener("change", cargarPropuestasReorden);
        });
        document.getElementById("catalogo_reorden_anterior").addEventListener("click", function () { paginaReorden = Math.max(1, paginaReorden - 1); renderPropuestasReorden(); });
        document.getElementById("catalogo_reorden_siguiente").addEventListener("click", function () { paginaReorden += 1; renderPropuestasReorden(); });
        document.getElementById("catalogo_reorden_aplicar").addEventListener("click", aplicarPropuestasReorden);
        document.getElementById("catalogo_reorden_propuestas").addEventListener("change", function (event) {
            if (!event.target.matches("[data-reorden-seleccionar]")) {
                return;
            }
            var idSku = event.target.getAttribute("data-reorden-seleccionar");
            propuestasReorden.forEach(function (item) {
                if (String(item.id_sku) === String(idSku)) {
                    item.seleccionado = event.target.checked;
                }
            });
            renderPropuestasReorden();
        });
        document.getElementById("catalogo_reorden_todos").addEventListener("change", function () {
            var seleccionar = this.checked;
            propuestasReorden.forEach(function (item) { item.seleccionado = seleccionar; });
            renderPropuestasReorden();
        });
        document.getElementById("catalogo_aux_tabs").addEventListener("click", function (event) {
            var tab = event.target.closest("[data-tipo]");
            if (tab) {
                tipoActual = tab.getAttribute("data-tipo");
                actualizarAccionesCatalogosMaestros();
            }
        });
        ["aux_marcas", "aux_categorias", "aux_unidades", "aux_atributos"].forEach(function (idTabla) {
            document.getElementById(idTabla).addEventListener("click", function (event) {
                var imagenButton = event.target.closest("[data-imagen-maestro]");
                if (imagenButton) {
                    event.preventDefault();
                    abrirImagenesMaestro(imagenButton.getAttribute("data-imagen-maestro"), imagenButton.getAttribute("data-id"));
                    return;
                }
                var button = event.target.closest("[data-editar]");
                if (!button) {
                    return;
                }
                event.preventDefault();
                abrirEditar(button.getAttribute("data-editar"), button.getAttribute("data-id"));
            });
        });
        document.getElementById("aux_atributo_tipo").addEventListener("change", configurarTipoAtributo);
        formImagenMaestro.addEventListener("submit", guardarImagenMaestro);
        document.getElementById("catalogo_imagen_maestro_limpiar").addEventListener("click", limpiarFormularioImagenMaestro);
        document.getElementById("catalogo_imagen_maestro_lista").addEventListener("click", function (event) {
            var editar = event.target.closest("[data-imagen-editar]");
            if (editar) {
                event.preventDefault();
                editarImagenMaestro(editar.getAttribute("data-imagen-editar"));
                return;
            }
            var desactivar = event.target.closest("[data-imagen-desactivar]");
            if (desactivar) {
                event.preventDefault();
                desactivarImagenMaestro(desactivar.getAttribute("data-imagen-desactivar"));
            }
        });
        document.getElementById("catalogo_config_modulos").addEventListener("click", function (event) {
            var boton = event.target.closest("[data-config-modulo-boton]");
            if (!boton) {
                return;
            }
            activarModuloConfiguracion(boton.getAttribute("data-config-modulo-boton"), true);
        });
        form.addEventListener("submit", guardar);
        ["catalogo_categorias_uso", "catalogo_categorias_imagen", "catalogo_categorias_estatus"].forEach(function (id) {
            document.getElementById(id).addEventListener("change", render);
        });
        document.getElementById("catalogo_categorias_resumen").addEventListener("click", function (event) {
            var boton = event.target.closest("[data-categoria-resumen-uso]");
            if (!boton) {
                return;
            }
            var uso = boton.getAttribute("data-categoria-resumen-uso") || "";
            var filtroImagen = uso === "con_imagen" || uso === "sin_imagen" ? uso : "";
            document.getElementById("catalogo_categorias_uso").value = filtroImagen ? "" : uso;
            document.getElementById("catalogo_categorias_imagen").value = filtroImagen;
            document.getElementById("catalogo_categorias_estatus").value = boton.getAttribute("data-categoria-resumen-estatus") || "";
            render();
        });
        actualizarAccionesCatalogosMaestros();
        activarTabDesdeHash();
        actualizarAccionesCatalogosMaestros();
        activarModuloConfiguracion(moduloConfiguracionDesdeHash(), false);
    });
})();
