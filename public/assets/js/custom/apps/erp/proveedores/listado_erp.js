"use strict";
(function () {
    var pagina = 1;
    var limite = 25;
    var total = 0;
    var timer = null;
    var proveedorActual = null;
    var fiscalesActuales = [];
    var contactosActuales = [];
    var condicionesActuales = [];
    var documentosActuales = [];
    var listasActuales = [];
    var costosActuales = [];
    var listaDetalleActual = null;
    var listaDetalleRenglones = [];
    var listaDetalleFiltro = "todos";
    var listaDetalleBusqueda = "";
    var listaPreviewActual = null;
    var listaPreviewDatos = null;
    var matchingPropuestasActuales = [];
    var matchingResumenActual = {};
    var relacionesLotePreviewActual = null;
    var costosLotePreviewActual = null;
    var costoReferenciaPreviewActual = null;
    var incidenciasPropuestasActuales = [];
    var incidenciasRealesActuales = [];
    var incidenciasResumenActual = {};
    var catalogosListaDetalle = {unidades: []};
    var permisos = window.PROVEEDORES_ERP_PERMISOS || {};

    function esc(value) {
        var div = document.createElement("div");
        div.textContent = value == null ? "" : value;
        return div.innerHTML;
    }

    function get(url, data) {
        var query = new URLSearchParams(data || {}).toString();
        return fetch(url + (query ? "?" + query : ""), {credentials: "same-origin"}).then(function (response) {
            return leerJsonSeguro(response);
        });
    }

    function post(url, data) {
        return fetch(url, {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
                "X-CSRF-Token": window.ERP_CSRF_TOKEN || ""
            },
            body: new URLSearchParams(data || {}).toString(),
            credentials: "same-origin"
        }).then(function (response) {
            return leerJsonSeguro(response);
        });
    }

    function postForm(url, formData) {
        return fetch(url, {
            method: "POST",
            headers: {
                "X-CSRF-Token": window.ERP_CSRF_TOKEN || ""
            },
            body: formData,
            credentials: "same-origin"
        }).then(function (response) {
            return leerJsonSeguro(response);
        });
    }

    function leerJsonSeguro(response) {
        return response.text().then(function (text) {
            if (!text) {
                throw new Error("El servidor no devolvio respuesta. Revisa si la sesion expiro o si hubo un error interno.");
            }
            try {
                var parsed = JSON.parse(text);
                if (!parsed || typeof parsed !== "object" || Array.isArray(parsed)) {
                    throw new Error("Formato JSON inesperado");
                }
                return parsed;
            } catch (e) {
                throw new Error("El servidor devolvio una respuesta no valida: " + text.substring(0, 180));
            }
        });
    }

    function valor(id) {
        var el = document.getElementById(id);
        return el ? el.value.trim() : "";
    }

    function setInputValor(id, value) {
        var el = document.getElementById(id);
        if (el) {
            el.value = value == null ? "" : value;
        }
    }

    function cargar() {
        var body = document.getElementById("proveedores_erp_body");
        body.innerHTML = "<tr><td colspan=\"7\" class=\"text-center text-muted py-8\">Cargando proveedores...</td></tr>";
        limite = Number(valor("proveedores_erp_limite") || 25);

        get("/proveedor/proveedores_listar_erp", {
            busqueda: valor("proveedores_erp_buscar"),
            estatus_erp: valor("proveedores_erp_estatus"),
            tipo_proveedor: valor("proveedores_erp_tipo"),
            pagina: pagina,
            limite: limite
        }).then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje || "No fue posible consultar proveedores.");
            }
            var data = response.depurar || {};
            var paginacion = data.paginacion || {};
            total = Number(paginacion.total || 0);
            renderListado(data.registros || []);
            actualizarPaginacion();
        }).catch(function (error) {
            body.innerHTML = "<tr><td colspan=\"7\" class=\"text-center text-danger py-8\">" + esc(error.message) + "</td></tr>";
            total = 0;
            actualizarPaginacion();
        });
    }

    function cargarCatalogosListaDetalle() {
        if (!permisos.listas) {
            return;
        }
        get("/proveedor/proveedor_lista_catalogos_erp", {}).then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje || "No fue posible consultar catalogos.");
            }
            catalogosListaDetalle = response.depurar || {unidades: []};
            llenarUnidadesListaDetalle();
        }).catch(function () {
            catalogosListaDetalle = {unidades: []};
        });
    }

    function llenarUnidadesListaDetalle() {
        var selects = document.querySelectorAll(".proveedores-erp-unidad-compra");
        if (!selects.length) {
            return;
        }
        selects.forEach(function (select) {
            var actual = select.value;
            var primera = select.getAttribute("name") === "id_unidad_compra" && select.closest("#proveedores_erp_compra_lote_form") ? "No cambiar" : "Seleccionar unidad";
            select.innerHTML = "<option value=\"\">" + primera + "</option>" + (catalogosListaDetalle.unidades || []).map(function (u) {
                var etiqueta = [u.abreviatura, u.nombre].filter(Boolean).join(" - ");
                return "<option value=\"" + esc(u.id_unidad) + "\">" + esc(etiqueta || ("Unidad " + u.id_unidad)) + "</option>";
            }).join("");
            if (actual) {
                select.value = actual;
            }
        });
    }

    function renderListado(registros) {
        document.getElementById("proveedores_erp_body").innerHTML = registros.map(function (p) {
            var nombre = p.nombre_comercial || p.proveedor || "Proveedor sin nombre";
            var fiscal = [p.rfc, p.razon_social].filter(Boolean).join(" | ") || "Sin fiscal";
            var estado = p.estatus_erp || "sin estado";
            var tipo = p.tipo_proveedor || "sin tipo";
            return "<tr>" +
                "<td><div class=\"fw-bold\">" + esc(nombre) + "</div><span class=\"text-muted fs-8\">" + esc(p.codigo_proveedor_erp || ("ID " + p.id_proveedor)) + "</span></td>" +
                "<td><div>" + esc(fiscal) + "</div><span class=\"text-muted fs-8\">" + esc(p.estatus_fiscal || "") + "</span></td>" +
                "<td><span class=\"badge badge-light-info\">" + esc(tipo) + "</span></td>" +
                "<td>" + badge(estado, colorEstatusProveedor(estado)) + "</td>" +
                "<td class=\"text-end fw-bold\">" + esc(p.contactos_total || 0) + "</td>" +
                "<td class=\"text-end fw-bold\">" + esc(p.listas_erp_total || 0) + "</td>" +
                "<td class=\"text-end\"><button class=\"btn btn-sm btn-icon btn-light-primary\" type=\"button\" title=\"Ver proveedor\" data-proveedor=\"" + esc(p.id_proveedor) + "\"><i class=\"bi bi-eye\"></i></button></td>" +
                "</tr>";
        }).join("") || "<tr><td colspan=\"7\" class=\"text-center text-muted py-8\">Sin proveedores para los filtros actuales</td></tr>";
        document.getElementById("proveedores_erp_total").textContent = total + " proveedores";
    }

    function actualizarPaginacion() {
        var paginas = Math.max(1, Math.ceil(total / limite));
        if (pagina > paginas) {
            pagina = paginas;
        }
        var inicio = total ? ((pagina - 1) * limite) + 1 : 0;
        var fin = Math.min(total, pagina * limite);
        document.getElementById("proveedores_erp_paginacion").textContent = total ? inicio + "-" + fin + " de " + total + " | Pagina " + pagina + " de " + paginas : "Sin resultados";
        document.getElementById("proveedores_erp_anterior").disabled = pagina <= 1;
        document.getElementById("proveedores_erp_siguiente").disabled = pagina >= paginas;
    }

    function cargarConDebounce(resetPagina) {
        clearTimeout(timer);
        timer = setTimeout(function () {
            if (resetPagina) {
                pagina = 1;
            }
            cargar();
        }, 250);
    }

    function abrirProveedor(idProveedor) {
        limpiarModal();
        bootstrap.Modal.getOrCreateInstance(document.getElementById("proveedores_erp_modal")).show();
        get("/proveedor/proveedor_consultar_erp", {id_proveedor: idProveedor}).then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje || "No fue posible consultar el proveedor.");
            }
            renderFicha(response.depurar || {});
        }).catch(function (error) {
            var box = document.getElementById("proveedores_erp_modal_error");
            box.classList.remove("d-none");
            box.textContent = error.message;
        });
    }

    function limpiarModal() {
        document.getElementById("proveedores_erp_modal_titulo").textContent = "Cargando proveedor";
        document.getElementById("proveedores_erp_modal_subtitulo").textContent = "Ficha ERP";
        document.getElementById("proveedores_erp_modal_badges").innerHTML = "";
        document.getElementById("proveedores_erp_preparacion_pct").textContent = "0%";
        document.getElementById("proveedores_erp_preparacion_estado").textContent = "Sin revisar";
        document.getElementById("proveedores_erp_preparacion_barra").style.width = "0%";
        document.getElementById("proveedores_erp_preparacion_resumen").textContent = "Checklist solo lectura para pruebas reales.";
        document.getElementById("proveedores_erp_preparacion_conteos").innerHTML = "";
        document.getElementById("proveedores_erp_preparacion_items").innerHTML = "";
        document.getElementById("proveedores_erp_general").innerHTML = "<div class=\"col-12 text-muted\">Cargando...</div>";
        ["fiscales", "contactos", "condiciones", "documentos", "listas"].forEach(function (id) {
            document.getElementById("proveedores_erp_" + id).innerHTML = "";
        });
        document.getElementById("proveedores_erp_costos").innerHTML = "";
        document.getElementById("proveedores_erp_costos_historial").innerHTML = "";
        setInputValor("proveedores_erp_costos_buscar", "");
        limpiarComparativoCompras();
        document.getElementById("proveedores_erp_modal_error").classList.add("d-none");
        proveedorActual = null;
        fiscalesActuales = [];
        contactosActuales = [];
        condicionesActuales = [];
        documentosActuales = [];
        listasActuales = [];
        costosActuales = [];
        listaDetalleActual = null;
        listaDetalleRenglones = [];
    }

    function renderFicha(data) {
        var p = data.proveedor || {};
        proveedorActual = p;
        fiscalesActuales = data.fiscales || [];
        contactosActuales = data.contactos || [];
        condicionesActuales = data.condiciones || [];
        documentosActuales = data.documentos || [];
        listasActuales = data.listas || [];
        var nombre = p.nombre_comercial || p.proveedor || "Proveedor sin nombre";
        document.getElementById("proveedores_erp_modal_titulo").textContent = nombre;
        document.getElementById("proveedores_erp_modal_subtitulo").textContent = p.codigo_proveedor_erp || ("ID " + (p.id_proveedor || ""));
        document.getElementById("proveedores_erp_modal_badges").innerHTML =
            badge(p.estatus_erp || "sin estado", colorEstatusProveedor(p.estatus_erp || "")) +
            badge(p.tipo_proveedor || "sin tipo", "info") +
            badge(p.origen_erp || p.origen_perfil || "sin origen", "secondary");
        renderPreparacion(data.preparacion || {});
        renderGeneral(p);
        renderTablaSimple("proveedores_erp_fiscales", data.fiscales || [], fiscalRow, 2, "Sin datos fiscales");
        renderTablaSimple("proveedores_erp_contactos", data.contactos || [], contactoRow, 2, "Sin contactos");
        renderTablaSimple("proveedores_erp_condiciones", data.condiciones || [], condicionRow, 2, "Sin condiciones");
        renderTablaSimple("proveedores_erp_documentos", data.documentos || [], documentoRow, 2, "Sin documentos");
        renderTablaSimple("proveedores_erp_listas", data.listas || [], listaRow, 3, "Sin listas ERP");
        renderCostos(data.costos_resumen || {});
        cargarCostosProveedor();
    }

    function renderPreparacion(preparacion) {
        var porcentaje = Number(preparacion.porcentaje || 0);
        var estado = preparacion.estado || "sin_revision";
        var estadoTexto = estado.replace(/_/g, " ");
        var estadoClase = estado === "listo_pruebas" ? "success" : (estado === "en_preparacion" ? "warning" : "danger");
        document.getElementById("proveedores_erp_preparacion_pct").textContent = porcentaje + "%";
        document.getElementById("proveedores_erp_preparacion_estado").className = "badge badge-light-" + estadoClase + " mb-3";
        document.getElementById("proveedores_erp_preparacion_estado").textContent = estadoTexto;
        document.getElementById("proveedores_erp_preparacion_barra").className = "progress-bar bg-" + estadoClase;
        document.getElementById("proveedores_erp_preparacion_barra").style.width = Math.max(0, Math.min(100, porcentaje)) + "%";
        document.getElementById("proveedores_erp_preparacion_resumen").textContent =
            (preparacion.ok || 0) + " de " + (preparacion.total || 0) + " puntos listos. No modifica datos.";

        var conteos = preparacion.conteos || {};
        var resumenConteos = [
            ["Listas", conteos.listas],
            ["Borrador", conteos.listas_borrador],
            ["Cargadas", conteos.listas_cargadas],
            ["Validadas", conteos.listas_validadas],
            ["Aplicadas", conteos.listas_aplicadas],
            ["Renglones", conteos.renglones],
            ["Operativos", conteos.renglones_operativos],
            ["Op sin costo", conteos.renglones_operativos_sin_costo],
            ["Op sin moneda", conteos.renglones_operativos_sin_moneda],
            ["Costos aplicados", conteos.renglones_costos_aplicados]
        ];
        document.getElementById("proveedores_erp_preparacion_conteos").innerHTML = resumenConteos.map(function (item) {
            var valor = item[1] == null ? 0 : item[1];
            return "<span class=\"badge badge-light-primary\" title=\"Conteo solo lectura para revisar avance operativo\">" + esc(item[0]) + ": " + esc(valor) + "</span>";
        }).join("");

        var items = preparacion.items || [];
        document.getElementById("proveedores_erp_preparacion_items").innerHTML = items.map(function (item) {
            var ok = !!item.ok;
            return "<tr>" +
                "<td><span class=\"badge " + (ok ? "badge-light-success" : "badge-light-warning") + " me-2\">" + (ok ? "OK" : "Revisar") + "</span>" +
                "<span class=\"fw-bold\">" + esc(item.titulo || "-") + "</span></td>" +
                "<td class=\"text-end fw-bold\">" + esc(item.valor || 0) + "</td>" +
                "<td class=\"text-muted fs-7\">" + esc(item.accion || "-") + "</td>" +
                "</tr>";
        }).join("") || "<tr><td colspan=\"3\" class=\"text-center text-muted py-6\">Sin checklist disponible</td></tr>";
    }

    function renderGeneral(p) {
        var campos = [
            ["Proveedor legacy", p.proveedor],
            ["Nombre comercial", p.nombre_comercial],
            ["Nombre corto", p.nombre_corto],
            ["Codigo ERP", p.codigo_proveedor_erp],
            ["Clasificacion", p.clasificacion_operativa],
            ["Responsable interno", p.responsable_interno_id],
            ["Cuota legacy", p.cuota],
            ["Fecha actualizacion", p.fecha_actualizacion || p.perfil_fecha_actualizacion],
            ["Notas", p.notas]
        ];
        document.getElementById("proveedores_erp_general").innerHTML = campos.map(function (campo) {
            return "<div class=\"col-md-4\"><div class=\"text-muted fs-7\">" + esc(campo[0]) + "</div><div class=\"fw-bold text-gray-800\">" + esc(campo[1] || "-") + "</div></div>";
        }).join("");
    }

    function renderTablaSimple(id, items, rowFn, colspan, emptyText) {
        document.getElementById(id).innerHTML = items.map(rowFn).join("") || "<tr><td colspan=\"" + colspan + "\" class=\"text-center text-muted py-6\">" + esc(emptyText) + "</td></tr>";
    }

    function fiscalRow(x) {
        return "<tr><td><div class=\"fw-bold\">" + esc(x.rfc || "Sin RFC") + "</div><span class=\"text-muted fs-8\">" + esc(x.razon_social || "") + "</span></td><td class=\"text-end\"><span class=\"badge badge-light-primary me-2\">" + esc(x.estatus || "sin estado") + "</span>" +
            (permisos.fiscales ? "<button class=\"btn btn-sm btn-icon btn-light-primary\" type=\"button\" title=\"Editar fiscal\" data-fiscal=\"" + esc(x.id_proveedor_fiscal) + "\"><i class=\"bi bi-pencil-square\"></i></button>" : "") + "</td></tr>";
    }

    function contactoRow(x) {
        var contacto = [x.correo, x.telefono, x.celular].filter(Boolean).join(" | ");
        return "<tr><td><div class=\"fw-bold\">" + esc(x.nombre || "Sin nombre") + "</div><span class=\"text-muted fs-8\">" + esc(contacto || "-") + "</span></td><td class=\"text-end\"><span class=\"badge badge-light-info me-2\">" + esc(x.area || "sin area") + "</span>" +
            (permisos.contactos ? "<button class=\"btn btn-sm btn-icon btn-light-primary\" type=\"button\" title=\"Editar contacto\" data-contacto=\"" + esc(x.id_contacto_proveedor) + "\"><i class=\"bi bi-pencil-square\"></i></button>" : "") + "</td></tr>";
    }

    function condicionRow(x) {
        var resumen = [x.moneda_preferida, x.dias_credito ? x.dias_credito + " dias credito" : "", x.minimo_compra ? "Min " + x.minimo_compra : ""].filter(Boolean).join(" | ");
        return "<tr><td><div class=\"fw-bold\">" + esc(resumen || "Condicion sin resumen") + "</div><span class=\"text-muted fs-8\">" + esc(x.vigencia_desde || "-") + " / " + esc(x.vigencia_hasta || "-") + "</span></td><td class=\"text-end\"><span class=\"badge badge-light-primary me-2\">" + esc(x.estatus || "sin estado") + "</span>" +
            (permisos.condiciones ? "<button class=\"btn btn-sm btn-icon btn-light-primary\" type=\"button\" title=\"Editar condicion\" data-condicion=\"" + esc(x.id_condicion_proveedor) + "\"><i class=\"bi bi-pencil-square\"></i></button>" : "") + "</td></tr>";
    }

    function documentoRow(x) {
        var sensible = esDocumentoSensible(x);
        var puedeEditar = permisos.documentos && (!sensible || permisos.documentos_sensibles);
        return "<tr><td><div class=\"fw-bold\">" + esc(x.tipo_documento || "Documento") + "</div><span class=\"text-muted fs-8\">" + esc(x.archivo_nombre || x.referencia || "-") + "</span></td><td class=\"text-end\"><span class=\"badge badge-light-warning me-2\">" + esc(x.nivel_sensibilidad || "sin nivel") + "</span>" +
            (puedeEditar ? "<button class=\"btn btn-sm btn-icon btn-light-primary\" type=\"button\" title=\"Editar documento\" data-documento=\"" + esc(x.id_documento_proveedor) + "\"><i class=\"bi bi-pencil-square\"></i></button>" : "") + "</td></tr>";
    }

    function esDocumentoSensible(documento) {
        var nivel = String((documento && documento.nivel_sensibilidad) || "").toLowerCase().trim();
        return ["sensible", "confidencial", "financiero", "financiera", "bancario", "bancaria"].indexOf(nivel) !== -1;
    }

    function listaRow(x) {
        var vigencia = [x.vigencia_desde || "-", x.vigencia_hasta || "-"].join(" / ");
        var preview = permisos.listas && x.id_documento_proveedor
            ? "<button class=\"btn btn-sm btn-icon btn-light-warning me-2\" type=\"button\" title=\"Vista previa archivo\" data-lista-preview=\"" + esc(x.id_lista_proveedor_erp) + "\"><i class=\"bi bi-file-earmark-spreadsheet\"></i></button>"
            : "";
        var estatus = String(x.estatus || "borrador");
        var accionesEstado = permisos.autorizar
            ? "<div class=\"btn-group me-2\" role=\"group\">" +
                (estatus !== "validada" && estatus !== "aplicada" ? "<button class=\"btn btn-sm btn-icon btn-light-success\" type=\"button\" title=\"Validada: tiene al menos un renglon operativo y esos renglones ya tienen identidad, costo y moneda.\" data-lista-estatus=\"validada\" data-lista-id=\"" + esc(x.id_lista_proveedor_erp) + "\"><i class=\"bi bi-check2-circle\"></i></button>" : "") +
                (estatus !== "aplicada" ? "<button class=\"btn btn-sm btn-icon btn-light-warning\" type=\"button\" title=\"Aplicada: ya tiene relaciones proveedor-SKU o costos aplicados para uso operativo.\" data-lista-estatus=\"aplicada\" data-lista-id=\"" + esc(x.id_lista_proveedor_erp) + "\"><i class=\"bi bi-send-check\"></i></button>" : "") +
                (estatus !== "historica" ? "<button class=\"btn btn-sm btn-icon btn-light-secondary\" type=\"button\" title=\"Historica: conserva evidencia, pero deja de ser referencia activa.\" data-lista-estatus=\"historica\" data-lista-id=\"" + esc(x.id_lista_proveedor_erp) + "\"><i class=\"bi bi-archive\"></i></button>" : "") +
                (estatus !== "cancelada" ? "<button class=\"btn btn-sm btn-icon btn-light-danger\" type=\"button\" title=\"Cancelada: no debe usarse operativamente, pero conserva evidencia.\" data-lista-estatus=\"cancelada\" data-lista-id=\"" + esc(x.id_lista_proveedor_erp) + "\"><i class=\"bi bi-x-circle\"></i></button>" : "") +
            "</div>"
            : "";
        return "<tr><td><div class=\"fw-bold\">" + esc(x.nombre_lista || "Lista") + "</div><span class=\"text-muted fs-8\">" + esc(x.version_lista || "-") + " | " + esc(vigencia) + "</span></td><td>" + esc(x.moneda || "-") + "</td><td class=\"text-end\"><span class=\"badge badge-light-primary me-2\">" + esc(x.estatus || "sin estado") + "</span>" +
            accionesEstado + (permisos.listas ? preview + "<button class=\"btn btn-sm btn-icon btn-light-info me-2\" type=\"button\" title=\"Ver detalle\" data-lista-detalle=\"" + esc(x.id_lista_proveedor_erp) + "\"><i class=\"bi bi-list-ul\"></i></button><button class=\"btn btn-sm btn-icon btn-light-primary\" type=\"button\" title=\"Editar lista\" data-lista=\"" + esc(x.id_lista_proveedor_erp) + "\"><i class=\"bi bi-pencil-square\"></i></button>" : "") + "</td></tr>";
    }

    function renderCostos(resumen) {
        document.getElementById("proveedores_erp_costos").innerHTML =
            stat("Costos", resumen.costos_total || 0) +
            stat("SKU", resumen.skus_total || 0) +
            stat("Primera vigencia", resumen.primera_vigencia || "-") +
            stat("Ultima vigencia", resumen.ultima_vigencia || "-");
    }

    function cargarCostosProveedor() {
        var body = document.getElementById("proveedores_erp_costos_historial");
        if (!body || !proveedorActual || !proveedorActual.id_proveedor) {
            return;
        }
        if (!permisos.costos) {
            body.innerHTML = "<tr><td class=\"text-center text-muted py-6\">Historial visible con permiso de costos</td></tr>";
            return;
        }
        body.innerHTML = "<tr><td class=\"text-center text-muted py-6\">Cargando historial...</td></tr>";
        get("/proveedor/proveedor_costos_erp", {
            id_proveedor: proveedorActual.id_proveedor,
            limite: 50
        }).then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje || "No fue posible consultar costos.");
            }
            costosActuales = (response.depurar && response.depurar.registros) || [];
            renderCostosHistorial(costosActuales);
        }).catch(function (error) {
            body.innerHTML = "<tr><td class=\"text-center text-danger py-6\">" + esc(error.message) + "</td></tr>";
        });
    }

    function renderCostosHistorial(registros) {
        var filtrados = filtrarCostosHistorial(registros);
        document.getElementById("proveedores_erp_costos_historial").innerHTML = filtrados.map(costoRow).join("") ||
            "<tr><td class=\"text-center text-muted py-6\">Sin historial de costos proveedor-SKU</td></tr>";
    }

    function filtrarCostosHistorial(registros) {
        var texto = String(valor("proveedores_erp_costos_buscar") || "").toLowerCase();
        if (!texto) {
            return registros || [];
        }
        return (registros || []).filter(function (x) {
            var base = [
                x.sku_erp, x.sku_nombre, x.sku_proveedor, x.codigo_barras, x.codigo_interno,
                x.nombre_lista, x.version_lista, x.origen, x.moneda, x.estatus,
                x.vigencia_desde, x.vigencia_hasta, x.costo
            ].filter(Boolean).join(" ").toLowerCase();
            return base.indexOf(texto) >= 0;
        });
    }

    function costoRow(x) {
        var sku = [x.sku_erp, x.sku_nombre].filter(Boolean).join(" | ") || ("SKU ID " + (x.id_sku || "-"));
        var origen = [x.origen, x.nombre_lista, x.version_lista].filter(Boolean).join(" | ") || "-";
        var vigencia = [x.vigencia_desde || "-", x.vigencia_hasta || "-"].join(" / ");
        var costo = (x.costo == null ? "-" : x.costo) + " " + (x.moneda || "");
        return "<tr><td>" +
            "<div class=\"fw-bold\">" + esc(costo) + "</div>" +
            "<span class=\"text-muted fs-8\">" + esc(sku) + "</span>" +
            "<div class=\"text-muted fs-8\">" + esc(origen) + "</div>" +
            "<div class=\"text-muted fs-8\">" + esc(vigencia) + "</div>" +
            "</td><td class=\"text-end\">" +
            "<span class=\"badge badge-light-primary\">" + esc(x.estatus || "sin estado") + "</span>" +
            "</td></tr>";
    }

    function limpiarComparativoCompras() {
        var resumen = document.getElementById("proveedores_erp_compras_resumen");
        var body = document.getElementById("proveedores_erp_compras_comparativo");
        var error = document.getElementById("proveedores_erp_compras_error");
        if (resumen) {
            resumen.innerHTML = "";
        }
        if (body) {
            body.innerHTML = "";
        }
        if (error) {
            error.classList.add("d-none");
        }
    }

    function ejecutarComparativoCompras() {
        if (!proveedorActual || !proveedorActual.id_proveedor) {
            return;
        }
        var termino = valor("proveedores_erp_compras_termino");
        var body = document.getElementById("proveedores_erp_compras_comparativo");
        var error = document.getElementById("proveedores_erp_compras_error");
        if (termino.length < 2) {
            error.textContent = "Escribe al menos dos caracteres.";
            error.classList.remove("d-none");
            return;
        }
        error.classList.add("d-none");
        body.innerHTML = "<tr><td colspan=\"4\" class=\"text-center text-muted py-6\">Comparando...</td></tr>";
        document.getElementById("proveedores_erp_compras_resumen").innerHTML = "";
        get("/proveedor/compras_contrato_comparar_erp", {
            id_proveedor: proveedorActual.id_proveedor,
            termino: termino
        }).then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje || "No fue posible comparar contratos.");
            }
            renderComparativoCompras(response.depurar || {});
        }).catch(function (err) {
            body.innerHTML = "";
            error.textContent = err.message;
            error.classList.remove("d-none");
        });
    }

    function renderComparativoCompras(data) {
        var resumen = data.resumen || {};
        document.getElementById("proveedores_erp_compras_resumen").innerHTML =
            badge("Proveedores: " + (resumen.proveedores_contrato || 0), "primary") +
            badge("Solicitudes: " + (resumen.solicitudes_actual || 0), "info") +
            badge("Ordenes: " + (resumen.ordenes_actual || 0), "warning");
        var filas = {};
        agregarFuenteComparativo(filas, data.proveedores_contrato || [], "proveedores");
        agregarFuenteComparativo(filas, data.solicitudes_actual || [], "solicitudes");
        agregarFuenteComparativo(filas, data.ordenes_actual || [], "ordenes");
        var ids = Object.keys(filas).sort(function (a, b) {
            return Number(a) - Number(b);
        });
        document.getElementById("proveedores_erp_compras_comparativo").innerHTML = ids.map(function (id) {
            var fila = filas[id];
            var base = fila.proveedores || fila.solicitudes || fila.ordenes || {};
            var sku = base.sku_erp || base.sku || "";
            var nombre = base.nombre_sku || base.nombre || "";
            return "<tr>" +
                "<td><div class=\"fw-bold\">" + esc(sku || ("Relacion " + id)) + "</div><span class=\"text-muted fs-8\">" + esc(nombre) + "</span></td>" +
                "<td class=\"text-end\">" + marcaComparativo(fila.proveedores) + "</td>" +
                "<td class=\"text-end\">" + marcaComparativo(fila.solicitudes) + "</td>" +
                "<td class=\"text-end\">" + marcaComparativo(fila.ordenes) + "</td>" +
                "</tr>";
        }).join("") || "<tr><td colspan=\"4\" class=\"text-center text-muted py-6\">Sin resultados para comparar</td></tr>";
    }

    function agregarFuenteComparativo(filas, items, clave) {
        items.forEach(function (item) {
            var id = String(item.id_sku_proveedor || "");
            if (!id) {
                return;
            }
            filas[id] = filas[id] || {proveedores: null, solicitudes: null, ordenes: null};
            filas[id][clave] = item;
        });
    }

    function marcaComparativo(valor) {
        return valor ? "<span class=\"badge badge-light-success\">Si</span>" : "<span class=\"badge badge-light-danger\">No</span>";
    }

    function stat(label, value) {
        return "<div class=\"border border-dashed rounded p-4\"><div class=\"text-muted fs-7\">" + esc(label) + "</div><div class=\"fw-bold fs-4\">" + esc(value) + "</div></div>";
    }

    function badge(text, color) {
        return "<span class=\"badge badge-light-" + color + " fs-7\">" + esc(text) + "</span>";
    }

    function colorEstatusProveedor(estatus) {
        estatus = String(estatus || "").toLowerCase();
        if (estatus === "activo_compras") { return "success"; }
        if (estatus === "suspendido") { return "warning"; }
        if (estatus === "bloqueado" || estatus === "inactivo") { return "danger"; }
        if (estatus === "en_revision") { return "info"; }
        if (estatus === "prospecto") { return "secondary"; }
        return "primary";
    }

    function cambiarEstatusProveedor() {
        if (!proveedorActual || !proveedorActual.id_proveedor || !permisos.autorizar) {
            return;
        }
        var actual = proveedorActual.estatus_erp || "prospecto";
        Swal.fire({
            title: "Cambiar estatus del proveedor",
            html:
                "<div class=\"text-start\">" +
                "<label class=\"form-label\">Estatus ERP</label>" +
                "<select class=\"form-select mb-4\" id=\"swal_proveedor_estatus\">" +
                "<option value=\"prospecto\">Prospecto</option>" +
                "<option value=\"en_revision\">En revision</option>" +
                "<option value=\"activo_compras\">Activo compras</option>" +
                "<option value=\"suspendido\">Suspendido</option>" +
                "<option value=\"bloqueado\">Bloqueado</option>" +
                "<option value=\"inactivo\">Inactivo</option>" +
                "</select>" +
                "<label class=\"form-label\">Motivo</label>" +
                "<textarea class=\"form-control\" id=\"swal_proveedor_motivo\" rows=\"4\" placeholder=\"Obligatorio para suspender, bloquear o inactivar\"></textarea>" +
                "<div class=\"text-muted fs-8 mt-3\">Suspendido, bloqueado e inactivo bloquean el envio de ordenes. Prospecto y en revision no bloquean por si solos.</div>" +
                "</div>",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Guardar estatus",
            cancelButtonText: "Cancelar",
            didOpen: function () {
                var select = document.getElementById("swal_proveedor_estatus");
                if (select) {
                    select.value = actual;
                }
            },
            preConfirm: function () {
                var estatus = document.getElementById("swal_proveedor_estatus").value;
                var motivo = document.getElementById("swal_proveedor_motivo").value.trim();
                if ((estatus === "suspendido" || estatus === "bloqueado" || estatus === "inactivo") && !motivo) {
                    Swal.showValidationMessage("Captura motivo para suspender, bloquear o inactivar.");
                    return false;
                }
                return {estatus_erp: estatus, motivo: motivo};
            }
        }).then(function (result) {
            if (!result.isConfirmed || !result.value) {
                return;
            }
            return post("/proveedor/proveedor_estatus_erp", {
                id_proveedor: proveedorActual.id_proveedor,
                estatus_erp: result.value.estatus_erp,
                motivo: result.value.motivo
            }).then(function (response) {
                if (response.error) {
                    throw new Error(response.mensaje || "No fue posible cambiar el estatus.");
                }
                Swal.fire({text: response.mensaje, icon: response.tipo === "warning" ? "warning" : "success", confirmButtonText: "Aceptar"});
                cargar();
                abrirProveedor(proveedorActual.id_proveedor);
            });
        }).catch(function (err) {
            Swal.fire({text: err.message || String(err), icon: "error", confirmButtonText: "Aceptar"});
        });
    }

    function abrirFormularioGeneral(proveedor) {
        var form = document.getElementById("proveedores_erp_general_form");
        if (!form) {
            return;
        }
        form.reset();
        proveedor = proveedor || {};
        setValor(form, "id_proveedor", proveedor.id_proveedor || "");
        setValor(form, "proveedor", proveedor.proveedor || "");
        setValor(form, "nombre_comercial", proveedor.nombre_comercial || proveedor.proveedor || "");
        setValor(form, "nombre_corto", proveedor.nombre_corto || "");
        setValor(form, "codigo_proveedor_erp", proveedor.codigo_proveedor_erp || "");
        setValor(form, "origen", proveedor.origen_perfil || proveedor.origen_erp || "");
        setValor(form, "tipo_proveedor", proveedor.tipo_proveedor || "");
        setValor(form, "clasificacion_operativa", proveedor.clasificacion_operativa || "");
        setValor(form, "responsable_interno_id", proveedor.responsable_interno_id || "");
        setValor(form, "notas", proveedor.notas || "");
        document.getElementById("proveedores_erp_general_titulo").textContent = proveedor.id_proveedor ? "Editar generales" : "Nuevo proveedor";
        document.getElementById("proveedores_erp_general_error").classList.add("d-none");
        bootstrap.Modal.getOrCreateInstance(document.getElementById("proveedores_erp_general_modal")).show();
    }

    function guardarGeneral(event) {
        event.preventDefault();
        var form = event.currentTarget;
        var button = form.querySelector("[type='submit']");
        var error = document.getElementById("proveedores_erp_general_error");
        var data = {};
        new FormData(form).forEach(function (value, key) {
            data[key] = value;
        });
        button.disabled = true;
        error.classList.add("d-none");
        post("/proveedor/proveedor_generales_guardar_erp", data).then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje || "No fue posible guardar el proveedor.");
            }
            bootstrap.Modal.getInstance(document.getElementById("proveedores_erp_general_modal")).hide();
            Swal.fire({text: response.mensaje, icon: "success", confirmButtonText: "Aceptar"});
            cargar();
            if (response.depurar && response.depurar.id_proveedor) {
                abrirProveedor(response.depurar.id_proveedor);
            }
        }).catch(function (err) {
            error.textContent = err.message;
            error.classList.remove("d-none");
        }).finally(function () {
            button.disabled = false;
        });
    }

    function abrirFormularioFiscal(fiscal) {
        var form = document.getElementById("proveedores_erp_fiscal_form");
        if (!form || !proveedorActual || !proveedorActual.id_proveedor) {
            return;
        }
        form.reset();
        fiscal = fiscal || {};
        setValor(form, "id_proveedor", proveedorActual.id_proveedor);
        setValor(form, "id_proveedor_fiscal", fiscal.id_proveedor_fiscal || "");
        [
            "rfc", "razon_social", "regimen_fiscal", "codigo_postal_fiscal", "pais", "estado", "municipio",
            "colonia", "calle", "numero_exterior", "numero_interior", "domicilio_fiscal", "uso_cfdi_preferido",
            "fecha_constancia", "vigencia_desde", "vigencia_hasta", "estatus"
        ].forEach(function (campo) {
            setValor(form, campo, fiscal[campo] || "");
        });
        document.getElementById("proveedores_erp_fiscal_titulo").textContent = fiscal.id_proveedor_fiscal ? "Editar fiscal" : "Agregar fiscal";
        document.getElementById("proveedores_erp_fiscal_error").classList.add("d-none");
        bootstrap.Modal.getOrCreateInstance(document.getElementById("proveedores_erp_fiscal_modal")).show();
    }

    function guardarFiscal(event) {
        event.preventDefault();
        var form = event.currentTarget;
        var button = form.querySelector("[type='submit']");
        var error = document.getElementById("proveedores_erp_fiscal_error");
        var data = {};
        new FormData(form).forEach(function (value, key) {
            data[key] = value;
        });
        button.disabled = true;
        error.classList.add("d-none");
        post("/proveedor/proveedor_fiscal_guardar_erp", data).then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje || "No fue posible guardar datos fiscales.");
            }
            bootstrap.Modal.getInstance(document.getElementById("proveedores_erp_fiscal_modal")).hide();
            Swal.fire({text: response.mensaje, icon: "success", confirmButtonText: "Aceptar"});
            cargar();
            if (response.depurar && response.depurar.id_proveedor) {
                abrirProveedor(response.depurar.id_proveedor);
            }
        }).catch(function (err) {
            error.textContent = err.message;
            error.classList.remove("d-none");
        }).finally(function () {
            button.disabled = false;
        });
    }

    function abrirFormularioContacto(contacto) {
        var form = document.getElementById("proveedores_erp_contacto_form");
        if (!form || !proveedorActual || !proveedorActual.id_proveedor) {
            return;
        }
        form.reset();
        contacto = contacto || {};
        setValor(form, "id_proveedor", proveedorActual.id_proveedor);
        setValor(form, "id_contacto_proveedor", contacto.id_contacto_proveedor || "");
        [
            "area", "nombre", "puesto", "correo", "telefono", "extension", "celular", "whatsapp",
            "prioridad", "observaciones", "estatus"
        ].forEach(function (campo) {
            setValor(form, campo, contacto[campo] || "");
        });
        setChecked(form, "es_principal", contacto.es_principal);
        setChecked(form, "recibe_ordenes_compra", contacto.recibe_ordenes_compra);
        setChecked(form, "recibe_notificaciones", contacto.recibe_notificaciones);
        document.getElementById("proveedores_erp_contacto_titulo").textContent = contacto.id_contacto_proveedor ? "Editar contacto" : "Agregar contacto";
        document.getElementById("proveedores_erp_contacto_error").classList.add("d-none");
        bootstrap.Modal.getOrCreateInstance(document.getElementById("proveedores_erp_contacto_modal")).show();
    }

    function guardarContacto(event) {
        event.preventDefault();
        var form = event.currentTarget;
        var button = form.querySelector("[type='submit']");
        var error = document.getElementById("proveedores_erp_contacto_error");
        var data = {};
        new FormData(form).forEach(function (value, key) {
            data[key] = value;
        });
        button.disabled = true;
        error.classList.add("d-none");
        post("/proveedor/proveedor_contacto_guardar_erp", data).then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje || "No fue posible guardar el contacto.");
            }
            bootstrap.Modal.getInstance(document.getElementById("proveedores_erp_contacto_modal")).hide();
            Swal.fire({text: response.mensaje, icon: "success", confirmButtonText: "Aceptar"});
            cargar();
            if (response.depurar && response.depurar.id_proveedor) {
                abrirProveedor(response.depurar.id_proveedor);
            }
        }).catch(function (err) {
            error.textContent = err.message;
            error.classList.remove("d-none");
        }).finally(function () {
            button.disabled = false;
        });
    }

    function abrirFormularioCondicion(condicion) {
        var form = document.getElementById("proveedores_erp_condicion_form");
        if (!form || !proveedorActual || !proveedorActual.id_proveedor) {
            return;
        }
        form.reset();
        condicion = condicion || {};
        setValor(form, "id_proveedor", proveedorActual.id_proveedor);
        setValor(form, "id_condicion_proveedor", condicion.id_condicion_proveedor || "");
        [
            "moneda_preferida", "forma_pago_preferida", "metodo_pago_preferido", "dias_credito",
            "limite_credito", "minimo_compra", "minimo_unidades", "tiempo_entrega_dias",
            "dias_surtido", "tipo_flete", "cobertura_entrega", "condiciones_pago",
            "condiciones_logisticas", "restricciones_operativas", "observaciones",
            "vigencia_desde", "vigencia_hasta", "estatus"
        ].forEach(function (campo) {
            setValor(form, campo, condicion[campo] || "");
        });
        setChecked(form, "requiere_orden_compra", condicion.requiere_orden_compra);
        document.getElementById("proveedores_erp_condicion_titulo").textContent = condicion.id_condicion_proveedor ? "Editar condiciones" : "Agregar condiciones";
        document.getElementById("proveedores_erp_condicion_error").classList.add("d-none");
        bootstrap.Modal.getOrCreateInstance(document.getElementById("proveedores_erp_condicion_modal")).show();
    }

    function guardarCondicion(event) {
        event.preventDefault();
        var form = event.currentTarget;
        var button = form.querySelector("[type='submit']");
        var error = document.getElementById("proveedores_erp_condicion_error");
        var data = {};
        new FormData(form).forEach(function (value, key) {
            data[key] = value;
        });
        button.disabled = true;
        error.classList.add("d-none");
        post("/proveedor/proveedor_condicion_guardar_erp", data).then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje || "No fue posible guardar condiciones.");
            }
            bootstrap.Modal.getInstance(document.getElementById("proveedores_erp_condicion_modal")).hide();
            Swal.fire({text: response.mensaje, icon: "success", confirmButtonText: "Aceptar"});
            cargar();
            if (response.depurar && response.depurar.id_proveedor) {
                abrirProveedor(response.depurar.id_proveedor);
            }
        }).catch(function (err) {
            error.textContent = err.message;
            error.classList.remove("d-none");
        }).finally(function () {
            button.disabled = false;
        });
    }

    function abrirFormularioDocumento(documento) {
        var form = document.getElementById("proveedores_erp_documento_form");
        if (!form || !proveedorActual || !proveedorActual.id_proveedor) {
            return;
        }
        form.reset();
        documento = documento || {};
        setValor(form, "id_proveedor", proveedorActual.id_proveedor);
        setValor(form, "id_documento_proveedor", documento.id_documento_proveedor || "");
        [
            "tipo_documento", "nivel_sensibilidad", "entidad_origen", "id_referencia", "referencia_tipo",
            "referencia", "archivo_nombre", "archivo_tipo", "archivo_tamano", "archivo_hash",
            "metadatos_json", "vigencia_desde", "vigencia_hasta", "estatus"
        ].forEach(function (campo) {
            setValor(form, campo, documento[campo] || "");
        });
        document.getElementById("proveedores_erp_documento_titulo").textContent = documento.id_documento_proveedor ? "Editar documento" : "Agregar documento";
        document.getElementById("proveedores_erp_documento_error").classList.add("d-none");
        bootstrap.Modal.getOrCreateInstance(document.getElementById("proveedores_erp_documento_modal")).show();
    }

    function guardarDocumento(event) {
        event.preventDefault();
        var form = event.currentTarget;
        var button = form.querySelector("[type='submit']");
        var error = document.getElementById("proveedores_erp_documento_error");
        var data = {};
        new FormData(form).forEach(function (value, key) {
            data[key] = value;
        });
        button.disabled = true;
        error.classList.add("d-none");
        post("/proveedor/proveedor_documento_guardar_erp", data).then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje || "No fue posible guardar el documento.");
            }
            bootstrap.Modal.getInstance(document.getElementById("proveedores_erp_documento_modal")).hide();
            Swal.fire({text: response.mensaje, icon: "success", confirmButtonText: "Aceptar"});
            if (response.depurar && response.depurar.id_proveedor) {
                abrirProveedor(response.depurar.id_proveedor);
            }
        }).catch(function (err) {
            error.textContent = err.message;
            error.classList.remove("d-none");
        }).finally(function () {
            button.disabled = false;
        });
    }

    function abrirFormularioLista(lista) {
        var form = document.getElementById("proveedores_erp_lista_form");
        if (!form || !proveedorActual || !proveedorActual.id_proveedor) {
            return;
        }
        form.reset();
        lista = lista || {};
        setValor(form, "id_proveedor", proveedorActual.id_proveedor);
        setValor(form, "id_lista_proveedor_erp", lista.id_lista_proveedor_erp || "");
        [
            "nombre_lista", "version_lista", "moneda", "origen", "id_lista_legacy", "id_documento_proveedor",
            "estatus", "fecha_emision", "vigencia_desde", "vigencia_hasta", "observaciones"
        ].forEach(function (campo) {
            setValor(form, campo, lista[campo] || "");
        });
        if (!lista.id_lista_proveedor_erp && !lista.estatus) {
            setValor(form, "estatus", "borrador");
        }
        document.getElementById("proveedores_erp_lista_titulo").textContent = lista.id_lista_proveedor_erp ? "Editar lista ERP" : "Agregar lista ERP";
        document.getElementById("proveedores_erp_lista_error").classList.add("d-none");
        bootstrap.Modal.getOrCreateInstance(document.getElementById("proveedores_erp_lista_modal")).show();
    }

    function guardarLista(event) {
        event.preventDefault();
        var form = event.currentTarget;
        var button = form.querySelector("[type='submit']");
        var error = document.getElementById("proveedores_erp_lista_error");
        var data = {};
        var archivoInput = form.querySelector("[name='archivo_lista']");
        var archivoLista = archivoInput && archivoInput.files && archivoInput.files.length ? archivoInput.files[0] : null;
        new FormData(form).forEach(function (value, key) {
            if (key !== "archivo_lista") {
                data[key] = value;
            }
        });
        button.disabled = true;
        error.classList.add("d-none");
        post("/proveedor/proveedor_lista_guardar_erp", data).then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje || "No fue posible guardar la lista.");
            }
            if (archivoLista && response.depurar && response.depurar.id_lista_proveedor_erp) {
                var formData = new FormData();
                formData.append("_csrf", window.ERP_CSRF_TOKEN || "");
                formData.append("id_proveedor", response.depurar.id_proveedor || proveedorActual.id_proveedor);
                formData.append("id_lista_proveedor_erp", response.depurar.id_lista_proveedor_erp);
                formData.append("archivo_lista", archivoLista);
                return postForm("/proveedor/proveedor_lista_archivo_subir_erp", formData).then(function (archivoResponse) {
                    if (archivoResponse.error) {
                        throw new Error(archivoResponse.mensaje || "La lista se guardo, pero no fue posible subir el archivo.");
                    }
                    response.mensaje = response.mensaje + " Archivo original ligado como evidencia.";
                    return response;
                });
            }
            return response;
        }).then(function (response) {
            bootstrap.Modal.getInstance(document.getElementById("proveedores_erp_lista_modal")).hide();
            Swal.fire({text: response.mensaje, icon: "success", confirmButtonText: "Aceptar"});
            cargar();
            if (response.depurar && response.depurar.id_proveedor) {
                abrirProveedor(response.depurar.id_proveedor);
            }
        }).catch(function (err) {
            error.textContent = err.message;
            error.classList.remove("d-none");
        }).finally(function () {
            button.disabled = false;
        });
    }

    function abrirPreviewLista(lista) {
        if (!proveedorActual || !proveedorActual.id_proveedor || !lista || !lista.id_lista_proveedor_erp) {
            return;
        }
        listaPreviewActual = lista;
        listaPreviewDatos = null;
        document.getElementById("proveedores_erp_lista_preview_titulo").textContent = lista.nombre_lista || "Vista previa de lista";
        document.getElementById("proveedores_erp_lista_preview_subtitulo").textContent = "Leyendo archivo original sin importar renglones";
        document.getElementById("proveedores_erp_lista_preview_resumen").innerHTML = "<span class=\"text-muted\">Cargando vista previa...</span>";
        document.getElementById("proveedores_erp_lista_preview_mapeo").innerHTML = "";
        document.getElementById("proveedores_erp_lista_preview_head").innerHTML = "";
        document.getElementById("proveedores_erp_lista_preview_body").innerHTML = "";
        var avisoPreview = document.getElementById("proveedores_erp_lista_preview_aviso");
        if (avisoPreview) {
            avisoPreview.classList.add("d-none");
            avisoPreview.innerHTML = "";
        }
        document.getElementById("proveedores_erp_lista_preview_error").classList.add("d-none");
        bootstrap.Modal.getOrCreateInstance(document.getElementById("proveedores_erp_lista_preview_modal")).show();
        cargarPreviewListaActual();
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-20
     * Proposito: consultar vista previa con limite dinamico para listas grandes.
     * Impacto: UI Proveedores; evita previews fijos y mantiene importacion separada.
     */
    function cargarPreviewListaActual() {
        if (!proveedorActual || !listaPreviewActual || !listaPreviewActual.id_lista_proveedor_erp) {
            return;
        }
        var limite = document.getElementById("proveedores_erp_lista_preview_limite");
        document.getElementById("proveedores_erp_lista_preview_resumen").innerHTML = "<span class=\"text-muted\">Cargando vista previa...</span>";
        document.getElementById("proveedores_erp_lista_preview_head").innerHTML = "";
        document.getElementById("proveedores_erp_lista_preview_body").innerHTML = "";
        get("/proveedor/proveedor_lista_archivo_preview_erp", {
            id_proveedor: proveedorActual.id_proveedor,
            id_lista_proveedor_erp: listaPreviewActual.id_lista_proveedor_erp,
            limite_preview: limite ? limite.value : 200
        }).then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje || "No fue posible generar vista previa.");
            }
            renderPreviewLista(response.depurar || {});
        }).catch(function (err) {
            document.getElementById("proveedores_erp_lista_preview_resumen").innerHTML = "";
            var error = document.getElementById("proveedores_erp_lista_preview_error");
            error.textContent = err.message;
            error.classList.remove("d-none");
        });
    }

    function renderPreviewLista(data) {
        listaPreviewDatos = data;
        var archivo = data.archivo || {};
        var preview = data.preview || {};
        var encabezados = preview.encabezados || [];
        var filas = preview.filas || [];
        document.getElementById("proveedores_erp_lista_preview_subtitulo").textContent = archivo.nombre || "Archivo original";
        document.getElementById("proveedores_erp_lista_preview_resumen").innerHTML =
            badge("Tipo: " + (preview.tipo_preview || "-"), "primary") +
            badge("Filas muestra: " + (preview.total_muestra || 0), "info") +
            badge("Columnas: " + encabezados.length, "warning");
        var botonImportar = document.getElementById("proveedores_erp_lista_preview_importar");
        var avisoPreview = document.getElementById("proveedores_erp_lista_preview_aviso");
        if (botonImportar) {
            botonImportar.disabled = !preview.parseable;
        }
        if (avisoPreview) {
            avisoPreview.classList.add("d-none");
            avisoPreview.innerHTML = "";
        }

        renderMapeoPreviewLista(data.mapeo_sugerido || {}, encabezados, !!preview.parseable);
        document.getElementById("proveedores_erp_lista_preview_head").innerHTML = "<tr>" + encabezados.map(function (h, i) {
            return "<th>" + esc(h || ("Columna " + (i + 1))) + "</th>";
        }).join("") + "</tr>";
        document.getElementById("proveedores_erp_lista_preview_body").innerHTML = filas.map(function (fila) {
            return "<tr>" + encabezados.map(function (_, i) {
                return "<td>" + esc(fila[i] || "") + "</td>";
            }).join("") + "</tr>";
        }).join("") || "<tr><td class=\"text-center text-muted py-6\" colspan=\"" + Math.max(encabezados.length, 1) + "\">Sin filas para vista previa</td></tr>";
        if (!preview.parseable) {
            document.getElementById("proveedores_erp_lista_preview_head").innerHTML = "";
            document.getElementById("proveedores_erp_lista_preview_body").innerHTML = "<tr><td class=\"text-center text-muted py-6\">" + esc(preview.mensaje || "Archivo no parseable en esta etapa") + "</td></tr>";
            if (avisoPreview) {
                avisoPreview.innerHTML = avisoArchivoNoParseable(preview);
                avisoPreview.classList.remove("d-none");
            }
            if (botonImportar) {
                botonImportar.title = preview.mensaje || "Archivo no importable automaticamente";
            }
        } else if (botonImportar) {
            botonImportar.title = "";
        }
    }

    function avisoArchivoNoParseable(preview) {
        var tipo = preview.tipo_preview || "";
        var pasos = tipo === "excel_legado"
            ? [
                "El archivo quedo guardado como evidencia de la lista.",
                "Abre el archivo original en Excel o LibreOffice y guardalo como XLSX o CSV.",
                "Vuelve a subir o asociar la version convertida para poder mapear columnas e importar renglones."
            ]
            : [
                "El archivo quedo guardado como evidencia.",
                "La lectura automatica de columnas no esta disponible para este formato.",
                "Convierte el archivo a XLSX o CSV si necesitas importar renglones."
            ];
        return "<div class=\"fw-semibold mb-2\">" + esc(preview.mensaje || "Archivo no importable automaticamente.") + "</div>" +
            "<ul class=\"mb-0 ps-4\">" + pasos.map(function (paso) {
                return "<li>" + esc(paso) + "</li>";
            }).join("") + "</ul>";
    }

    function renderMapeoPreviewLista(mapeo, encabezados, habilitado) {
        var campos = {
            sku_proveedor: "SKU proveedor",
            codigo_barras: "Codigo barras",
            codigo_interno: "Codigo interno",
            marca_proveedor: "Marca",
            descripcion_proveedor: "Descripcion",
            costo: "Costo",
            moneda: "Moneda",
            unidad_compra_texto: "Unidad",
            factor_conversion: "Factor",
            costo_incluye_impuestos: "Incluye impuestos",
            existencia_reportada: "Existencia"
        };
        var ayudas = {
            sku_proveedor: "Clave o modelo que usa el proveedor para identificar el producto.",
            codigo_barras: "EAN/UPC/codigo escaneable si el proveedor lo envia.",
            codigo_interno: "Otro codigo interno del proveedor distinto al SKU principal.",
            marca_proveedor: "Marca escrita en la lista del proveedor.",
            descripcion_proveedor: "Nombre o descripcion del producto en la lista.",
            costo: "Precio de compra que te da el proveedor.",
            moneda: "Moneda del costo. Si la lista ya tiene moneda, puede quedar sin mapear.",
            unidad_compra_texto: "Texto de presentacion: pieza, caja, paquete, bolsa, etc.",
            factor_conversion: "Cuantas unidades base trae la presentacion, por ejemplo piezas por caja.",
            costo_incluye_impuestos: "Columna que indique si el costo ya incluye IVA/impuestos.",
            existencia_reportada: "Stock o disponible reportado por el proveedor."
        };
        document.getElementById("proveedores_erp_lista_preview_mapeo").innerHTML = Object.keys(campos).map(function (campo) {
            var item = mapeo[campo] || {};
            var opciones = "<option value=\"\">No mapear</option>" + encabezados.map(function (h, i) {
                var selected = item.indice === i ? " selected" : "";
                return "<option value=\"" + esc(i) + "\"" + selected + ">" + esc(h || ("Columna " + (i + 1))) + "</option>";
            }).join("");
            return "<div class=\"col-md-3\"><label class=\"form-label fs-8\">" + esc(campos[campo]) + "</label><select class=\"form-select form-select-sm\" data-mapeo-campo=\"" + esc(campo) + "\"" + (!habilitado ? " disabled" : "") + ">" + opciones + "</select><div class=\"text-muted fs-9 mt-1\">" + esc(ayudas[campo] || "") + "</div></div>";
        }).join("");
    }

    function importarPreviewLista() {
        if (!proveedorActual || !listaPreviewActual || !listaPreviewActual.id_lista_proveedor_erp || !listaPreviewDatos) {
            return;
        }
        var preview = (listaPreviewDatos.preview || {});
        if (!preview.parseable) {
            Swal.fire({text: "Este archivo no se puede importar automaticamente.", icon: "warning", confirmButtonText: "Aceptar"});
            return;
        }
        var mapeo = {};
        document.querySelectorAll("[data-mapeo-campo]").forEach(function (select) {
            mapeo[select.getAttribute("data-mapeo-campo")] = select.value;
        });
        Swal.fire({
            text: "Se importaran renglones en borrador. No se aplicaran relaciones ni costos vigentes.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Importar",
            cancelButtonText: "Cancelar"
        }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }
            var boton = document.getElementById("proveedores_erp_lista_preview_importar");
            boton.disabled = true;
            post("/proveedor/proveedor_lista_archivo_importar_erp", {
                id_proveedor: proveedorActual.id_proveedor,
                id_lista_proveedor_erp: listaPreviewActual.id_lista_proveedor_erp,
                mapeo_json: JSON.stringify(mapeo)
            }).then(function (response) {
                if (response.error) {
                    throw new Error(response.mensaje || "No fue posible importar renglones.");
                }
                bootstrap.Modal.getInstance(document.getElementById("proveedores_erp_lista_preview_modal")).hide();
                Swal.fire({text: response.mensaje, icon: "success", confirmButtonText: "Aceptar"});
                abrirProveedor(proveedorActual.id_proveedor);
            }).catch(function (err) {
                Swal.fire({text: err.message, icon: "error", confirmButtonText: "Aceptar"});
            }).finally(function () {
                boton.disabled = false;
            });
        });
    }

    function abrirDetalleLista(lista) {
        if (!lista || !lista.id_lista_proveedor_erp || !proveedorActual || !proveedorActual.id_proveedor) {
            return;
        }
        listaDetalleActual = lista;
        listaDetalleRenglones = [];
        document.getElementById("proveedores_erp_lista_detalle_titulo").textContent = lista.nombre_lista || "Detalle de lista";
        document.getElementById("proveedores_erp_lista_detalle_subtitulo").textContent = lista.version_lista || ("ID " + lista.id_lista_proveedor_erp);
        document.getElementById("proveedores_erp_lista_detalle_body").innerHTML = "<tr><td colspan=\"5\" class=\"text-center text-muted py-6\">Cargando renglones...</td></tr>";
        document.getElementById("proveedores_erp_lista_detalle_error").classList.add("d-none");
        bootstrap.Modal.getOrCreateInstance(document.getElementById("proveedores_erp_lista_detalle_modal")).show();
        get("/proveedor/proveedor_lista_detalle_erp", {
            id_proveedor: proveedorActual.id_proveedor,
            id_lista_proveedor_erp: lista.id_lista_proveedor_erp
        }).then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje || "No fue posible consultar detalle de lista.");
            }
            var data = response.depurar || {};
            listaDetalleActual = data.lista || lista;
            listaDetalleRenglones = data.detalle || [];
            listaDetalleFiltro = "todos";
            listaDetalleBusqueda = "";
            setInputValor("proveedores_erp_lista_detalle_buscar", "");
            actualizarBotonesFiltroDetalle();
            renderRevisionListaDetalle(data.revision || {});
            renderListaDetalle();
        }).catch(function (err) {
            var error = document.getElementById("proveedores_erp_lista_detalle_error");
            error.textContent = err.message;
            error.classList.remove("d-none");
            document.getElementById("proveedores_erp_lista_detalle_body").innerHTML = "";
        });
    }

    function renderRevisionListaDetalle(revision) {
        var contenedor = document.getElementById("proveedores_erp_lista_detalle_revision");
        if (!contenedor) {
            return;
        }
        var total = Number(revision.total || 0);
        var operativos = Number(revision.operativos || 0);
        var relaciones = Number(revision.relaciones_aplicadas || 0);
        var costos = Number(revision.costos_aplicados || 0);
        var preparacion = contarPreparacionListaDetalle();
        var porcentajeRelacion = total > 0 ? Math.round((relaciones / total) * 100) : 0;
        contenedor.innerHTML =
            badge("Renglones: " + total, "primary") +
            badge("Operativos: " + operativos, operativos > 0 ? "success" : "secondary") +
            badge("Relacionados: " + relaciones + " (" + porcentajeRelacion + "%)", relaciones > 0 ? "success" : "secondary") +
            badge("Costos aplicados: " + costos, costos > 0 ? "success" : "secondary") +
            badge("Listo relacion: " + preparacion.listoRelacion, preparacion.listoRelacion > 0 ? "success" : "secondary") +
            badge("Listo costo: " + preparacion.listoCosto, preparacion.listoCosto > 0 ? "warning" : "secondary") +
            badge("Completar compra: " + preparacion.compraPendiente, preparacion.compraPendiente > 0 ? "warning" : "success") +
            badge("Op sin identidad: " + (revision.operativos_sin_identidad || 0), Number(revision.operativos_sin_identidad || 0) > 0 ? "danger" : "success") +
            badge("Op sin costo: " + (revision.operativos_sin_costo || 0), Number(revision.operativos_sin_costo || 0) > 0 ? "warning" : "success") +
            badge("Op sin moneda: " + (revision.operativos_sin_moneda || 0), Number(revision.operativos_sin_moneda || 0) > 0 ? "warning" : "success") +
            badge("Info sin match: " + (revision.sin_match_informativo || revision.sin_match || 0), "info");
    }

    function renderListaDetalle() {
        var renglones = filtrarListaDetalleRenglones();
        var conteo = document.getElementById("proveedores_erp_lista_detalle_conteo");
        if (conteo) {
            conteo.textContent = renglones.length + " de " + listaDetalleRenglones.length + " renglones";
        }
        document.getElementById("proveedores_erp_lista_detalle_body").innerHTML = renglones.map(function (x) {
            var sku = [x.sku_proveedor, x.codigo_barras, x.codigo_interno].filter(Boolean).join(" | ") || "Sin codigo";
            var compraOk = Number(x.id_unidad_compra || 0) > 0 && Number(x.factor_conversion || 0) > 0 && Number(x.cantidad_minima || 0) > 0;
            var requiereCompra = x.id_sku && ["match_seleccionado", "relacion_aplicada", "costo_aplicado"].indexOf(String(x.estado_match || "")) >= 0;
            var unidad = [
                x.unidad_compra_texto,
                x.factor_conversion ? "Factor " + x.factor_conversion : "",
                x.cantidad_minima ? "Compra min " + x.cantidad_minima : ""
            ].filter(Boolean).join(" | ") || "-";
            var estadoCompra = requiereCompra
                ? "<div class=\"mt-1\"><span class=\"badge " + (compraOk ? "badge-light-success" : "badge-light-warning") + "\">" + (compraOk ? "Listo relacion" : "Completar compra") + "</span></div>"
                : "";
            var costo = [x.costo || "-", x.moneda || ""].filter(Boolean).join(" ");
            var aplicar = permisos.matching && x.id_sku && String(x.estado_match || "") === "match_seleccionado"
                ? "<button class=\"btn btn-sm btn-icon btn-light-success me-2\" type=\"button\" title=\"Aplicar relacion\" data-aplicar-relacion=\"" + esc(x.id_lista_detalle_erp) + "\"><i class=\"bi bi-link-45deg\"></i></button>"
                : "";
            var aplicarCosto = permisos.costos && x.id_sku && x.id_sku_proveedor && Number(x.costo || 0) > 0 && String(x.estado_match || "") === "relacion_aplicada"
                ? "<button class=\"btn btn-sm btn-icon btn-light-warning me-2\" type=\"button\" title=\"Aplicar costo vigente\" data-aplicar-costo=\"" + esc(x.id_lista_detalle_erp) + "\"><i class=\"bi bi-currency-dollar\"></i></button>"
                : "";
            var enviarCatalogo = permisos.autorizar && (!x.id_sku || Number(x.id_sku || 0) <= 0 || ["sin_match", "ambiguo"].indexOf(String(x.estado_match || "")) >= 0)
                ? "<button class=\"btn btn-sm btn-icon btn-light-secondary me-2\" type=\"button\" title=\"Enviar este producto a Catalogo\" data-enviar-catalogo-renglon=\"" + esc(x.id_lista_detalle_erp) + "\"><i class=\"bi bi-box-arrow-up-right\"></i></button>"
                : "";
            var eliminar = permisos.listas && !x.id_sku_proveedor && ["relacion_aplicada", "costo_aplicado"].indexOf(String(x.estado_match || "")) < 0
                ? "<button class=\"btn btn-sm btn-icon btn-light-danger me-2\" type=\"button\" title=\"Eliminar renglon\" data-eliminar-renglon=\"" + esc(x.id_lista_detalle_erp) + "\"><i class=\"bi bi-trash\"></i></button>"
                : "";
            return "<tr>" +
                "<td><div class=\"fw-bold\">" + esc(sku) + "</div><span class=\"text-muted fs-8\">" + esc(x.marca_proveedor || "") + "</span></td>" +
                "<td>" + esc(x.descripcion_proveedor || "-") + "</td>" +
                "<td>" + esc(unidad) + estadoCompra + "</td>" +
                "<td class=\"text-end\">" + esc(costo) + "</td>" +
                "<td class=\"text-end\">" + aplicar + aplicarCosto + enviarCatalogo + eliminar + "<button class=\"btn btn-sm btn-icon btn-light-primary\" type=\"button\" title=\"Editar renglon\" data-lista-renglon=\"" + esc(x.id_lista_detalle_erp) + "\"><i class=\"bi bi-pencil-square\"></i></button></td>" +
                "</tr>";
        }).join("") || "<tr><td colspan=\"5\" class=\"text-center text-muted py-6\">Sin renglones capturados</td></tr>";
    }

    function filtrarListaDetalleRenglones() {
        return listaDetalleRenglones.filter(function (x) {
            var idSku = Number(x.id_sku || 0);
            var costo = Number(x.costo || 0);
            var idUnidad = Number(x.id_unidad_compra || 0);
            var factor = Number(x.factor_conversion || 0);
            var moneda = String(x.moneda || "").trim();
            var estado = String(x.estado_match || "");
            var origen = String((listaDetalleActual && listaDetalleActual.origen) || "");
            var observaciones = String(x.observaciones || "");
            var operativo = esRenglonOperativoLista(x);
            var preparacion = preparacionRenglonListaDetalle(x);
            var texto = String(listaDetalleBusqueda || "").toLowerCase().trim();
            if (texto) {
                var base = [
                    x.sku_proveedor, x.codigo_barras, x.codigo_interno, x.marca_proveedor,
                    x.descripcion_proveedor, x.unidad_compra_texto, x.moneda, x.estado_match,
                    x.criterio_match, x.observaciones, x.sku_erp, x.sku_nombre
                ].filter(Boolean).join(" ").toLowerCase();
                if (base.indexOf(texto) < 0) {
                    return false;
                }
            }
            if (listaDetalleFiltro === "operativos") {
                return operativo;
            }
            if (listaDetalleFiltro === "informativos") {
                return !operativo;
            }
            if (listaDetalleFiltro === "con_sku") {
                return idSku > 0;
            }
            if (listaDetalleFiltro === "sin_sku") {
                return idSku <= 0 || estado === "sin_match" || estado === "ambiguo";
            }
            if (listaDetalleFiltro === "listo_relacion") {
                return preparacion.listoRelacion;
            }
            if (listaDetalleFiltro === "listo_costo") {
                return preparacion.listoCosto;
            }
            if (listaDetalleFiltro === "costo_pendiente") {
                return costo <= 0;
            }
            if (listaDetalleFiltro === "unidad_pendiente") {
                return idUnidad <= 0 || factor <= 0;
            }
            if (listaDetalleFiltro === "moneda_pendiente") {
                return moneda === "";
            }
            if (listaDetalleFiltro === "productivo_sql") {
                return origen === "productivo_sql" || observaciones.indexOf("productivo") >= 0 || Number(x.id_producto_legacy || 0) > 0;
            }
            return true;
        });
    }

    function preparacionRenglonListaDetalle(x) {
        var estado = String(x.estado_match || "");
        var idSku = Number(x.id_sku || 0);
        var idSkuProveedor = Number(x.id_sku_proveedor || 0);
        var costo = Number(x.costo || 0);
        var idUnidad = Number(x.id_unidad_compra || 0);
        var factor = Number(x.factor_conversion || 0);
        var cantidadMinima = Number(x.cantidad_minima || 0);
        var moneda = String(x.moneda || "").trim();
        var compraCompleta = idUnidad > 0 && factor > 0 && cantidadMinima > 0;
        var requiereCompra = idSku > 0 && ["match_seleccionado", "relacion_aplicada", "costo_aplicado"].indexOf(estado) >= 0;

        return {
            listoRelacion: idSku > 0 && estado === "match_seleccionado" && compraCompleta,
            listoCosto: idSku > 0 && idSkuProveedor > 0 && estado === "relacion_aplicada" && costo > 0 && moneda !== "" && idUnidad > 0 && factor > 0,
            compraPendiente: requiereCompra && !compraCompleta
        };
    }

    function contarPreparacionListaDetalle() {
        return listaDetalleRenglones.reduce(function (total, x) {
            var preparacion = preparacionRenglonListaDetalle(x);
            total.listoRelacion += preparacion.listoRelacion ? 1 : 0;
            total.listoCosto += preparacion.listoCosto ? 1 : 0;
            total.compraPendiente += preparacion.compraPendiente ? 1 : 0;
            return total;
        }, {listoRelacion: 0, listoCosto: 0, compraPendiente: 0});
    }

    function esRenglonOperativoLista(x) {
        var estado = String(x.estado_match || "");
        return Number(x.id_sku || 0) > 0 ||
            Number(x.id_sku_proveedor || 0) > 0 ||
            ["match_seleccionado", "relacionado", "relacion_aplicada", "costo_aplicado"].indexOf(estado) >= 0;
    }

    function actualizarBotonesFiltroDetalle() {
        document.querySelectorAll("[data-lista-detalle-filtro]").forEach(function (boton) {
            var activo = boton.getAttribute("data-lista-detalle-filtro") === listaDetalleFiltro;
            boton.classList.toggle("active", activo);
            boton.classList.toggle("btn-light-primary", activo);
            boton.classList.toggle("btn-light", !activo);
        });
    }

    function abrirFormularioListaDetalle(renglon) {
        var form = document.getElementById("proveedores_erp_lista_detalle_form");
        if (!form || !proveedorActual || !proveedorActual.id_proveedor || !listaDetalleActual || !listaDetalleActual.id_lista_proveedor_erp) {
            return;
        }
        form.reset();
        renglon = renglon || {};
        setValor(form, "id_proveedor", proveedorActual.id_proveedor);
        setValor(form, "id_lista_proveedor_erp", listaDetalleActual.id_lista_proveedor_erp);
        setValor(form, "id_lista_detalle_erp", renglon.id_lista_detalle_erp || "");
        [
            "id_producto_legacy", "id_sku", "id_sku_proveedor", "sku_proveedor", "codigo_barras",
            "codigo_interno", "marca_proveedor", "descripcion_proveedor", "unidad_compra_texto",
            "id_unidad_compra", "factor_conversion", "cantidad_minima", "costo", "moneda",
            "costo_incluye_impuestos", "existencia_reportada", "estado_match", "criterio_match", "observaciones"
        ].forEach(function (campo) {
            setValor(form, campo, renglon[campo] || "");
        });
        document.getElementById("proveedores_erp_lista_detalle_form_titulo").textContent = renglon.id_lista_detalle_erp ? "Editar renglon" : "Agregar renglon";
        document.getElementById("proveedores_erp_lista_detalle_form_error").classList.add("d-none");
        document.getElementById("proveedores_erp_lista_detalle_sku_buscar").value = renglon.sku_erp || renglon.sku || "";
        document.getElementById("proveedores_erp_lista_detalle_sku_resultados").innerHTML = "";
        llenarUnidadesListaDetalle();
        setValor(form, "id_unidad_compra", renglon.id_unidad_compra || "");
        bootstrap.Modal.getOrCreateInstance(document.getElementById("proveedores_erp_lista_detalle_form_modal")).show();
    }

    function buscarSkuErpListaDetalle() {
        var termino = valor("proveedores_erp_lista_detalle_sku_buscar");
        var contenedor = document.getElementById("proveedores_erp_lista_detalle_sku_resultados");
        if (!contenedor) {
            return;
        }
        if (termino.length < 2) {
            contenedor.innerHTML = "<div class=\"text-muted fs-8\">Escribe al menos dos caracteres.</div>";
            return;
        }
        contenedor.innerHTML = "<div class=\"text-muted fs-8\">Buscando...</div>";
        get("/proveedor/proveedor_buscar_skus_erp", {q: termino}).then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje || "No fue posible buscar SKU ERP.");
            }
            var registros = response.depurar || [];
            contenedor.innerHTML = registros.map(function (sku) {
                var titulo = [sku.sku, sku.nombre].filter(Boolean).join(" | ");
                var meta = [sku.codigo_principal, sku.unidad, sku.estatus].filter(Boolean).join(" | ");
                return "<button class=\"btn btn-sm btn-light text-start w-100 mb-2\" type=\"button\" data-sku-manual=\"" + esc(sku.id_sku) + "\" data-sku-texto=\"" + esc(titulo) + "\">" +
                    "<span class=\"fw-semibold d-block\">" + esc(titulo || ("SKU " + sku.id_sku)) + "</span>" +
                    "<span class=\"text-muted fs-8\">" + esc(meta || "Sin metadatos") + "</span>" +
                "</button>";
            }).join("") || "<div class=\"text-muted fs-8\">Sin resultados. Si el producto no existe, mandalo a Catalogo desde pendientes.</div>";
        }).catch(function (err) {
            contenedor.innerHTML = "<div class=\"text-danger fs-8\">" + esc(err.message) + "</div>";
        });
    }

    function seleccionarSkuManualListaDetalle(boton) {
        var form = document.getElementById("proveedores_erp_lista_detalle_form");
        if (!form || !boton) {
            return;
        }
        setValor(form, "id_sku", boton.getAttribute("data-sku-manual") || "");
        setValor(form, "id_sku_proveedor", "");
        setValor(form, "estado_match", "match_seleccionado");
        setValor(form, "criterio_match", "seleccion_manual_sku_erp");
        document.getElementById("proveedores_erp_lista_detalle_sku_buscar").value = boton.getAttribute("data-sku-texto") || "";
        document.getElementById("proveedores_erp_lista_detalle_sku_resultados").innerHTML = "<div class=\"alert alert-success py-2 mb-0\">SKU ERP seleccionado. Guarda el renglon para conservar la seleccion.</div>";
    }

    function guardarListaDetalle(event) {
        event.preventDefault();
        var form = event.currentTarget;
        var button = form.querySelector("[type='submit']");
        var error = document.getElementById("proveedores_erp_lista_detalle_form_error");
        var data = {};
        new FormData(form).forEach(function (value, key) {
            data[key] = value;
        });
        button.disabled = true;
        error.classList.add("d-none");
        post("/proveedor/proveedor_lista_detalle_guardar_erp", data).then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje || "No fue posible guardar el renglon.");
            }
            bootstrap.Modal.getInstance(document.getElementById("proveedores_erp_lista_detalle_form_modal")).hide();
            Swal.fire({text: response.mensaje, icon: "success", confirmButtonText: "Aceptar"});
            if (listaDetalleActual) {
                abrirDetalleLista(listaDetalleActual);
            }
        }).catch(function (err) {
            error.textContent = err.message;
            error.classList.remove("d-none");
        }).finally(function () {
            button.disabled = false;
        });
    }

    function renglonesCompraLoteSeleccionados() {
        var alcance = valor("proveedores_erp_compra_lote_alcance") || "pendientes";
        var renglones = filtrarListaDetalleRenglones();
        if (alcance === "pendientes") {
            renglones = renglones.filter(function (x) {
                return preparacionRenglonListaDetalle(x).compraPendiente;
            });
        }
        return renglones;
    }

    function actualizarConteoCompraLote() {
        var renglones = renglonesCompraLoteSeleccionados();
        var conteo = document.getElementById("proveedores_erp_compra_lote_conteo");
        if (conteo) {
            conteo.textContent = renglones.length + " renglon(es) seleccionados";
        }
        var form = document.getElementById("proveedores_erp_compra_lote_form");
        if (form) {
            setValor(form, "ids_json", JSON.stringify(renglones.map(function (x) {
                return Number(x.id_lista_detalle_erp || 0);
            }).filter(Boolean)));
        }
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-20
     * Proposito: preparar en lote datos de compra de renglones ya matcheados sin aplicar relacion ni costo.
     * Impacto: UI Proveedores; acelera limpieza operativa de listas grandes.
     */
    function abrirCompraLote() {
        if (!proveedorActual || !listaDetalleActual || !permisos.listas) {
            return;
        }
        var form = document.getElementById("proveedores_erp_compra_lote_form");
        if (!form) {
            return;
        }
        form.reset();
        setValor(form, "id_proveedor", proveedorActual.id_proveedor);
        setValor(form, "id_lista_proveedor_erp", listaDetalleActual.id_lista_proveedor_erp);
        llenarUnidadesListaDetalle();
        setValor(form, "sobrescribir", "0");
        document.getElementById("proveedores_erp_compra_lote_error").classList.add("d-none");
        actualizarConteoCompraLote();
        bootstrap.Modal.getOrCreateInstance(document.getElementById("proveedores_erp_compra_lote_modal")).show();
    }

    function guardarCompraLote(event) {
        event.preventDefault();
        var form = event.currentTarget;
        var ids = renglonesCompraLoteSeleccionados().map(function (x) {
            return Number(x.id_lista_detalle_erp || 0);
        }).filter(Boolean);
        var error = document.getElementById("proveedores_erp_compra_lote_error");
        var button = form.querySelector("[type='submit']");
        if (!ids.length) {
            error.textContent = "No hay renglones seleccionados para completar compra. Usa filtros o selecciona renglones con compra pendiente.";
            error.classList.remove("d-none");
            return;
        }
        setValor(form, "ids_json", JSON.stringify(ids));
        var data = {};
        new FormData(form).forEach(function (value, key) {
            data[key] = value;
        });
        button.disabled = true;
        error.classList.add("d-none");
        post("/proveedor/proveedor_lista_detalle_compra_lote_erp", data).then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje || "No fue posible completar compra en lote.");
            }
            bootstrap.Modal.getInstance(document.getElementById("proveedores_erp_compra_lote_modal")).hide();
            Swal.fire({text: response.mensaje, icon: "success", confirmButtonText: "Aceptar"});
            abrirDetalleLista(listaDetalleActual);
        }).catch(function (err) {
            error.textContent = err.message;
            error.classList.remove("d-none");
        }).finally(function () {
            button.disabled = false;
        });
    }

    function eliminarListaDetalle(idRenglon) {
        if (!proveedorActual || !listaDetalleActual || !idRenglon || !permisos.listas) {
            return;
        }
        var renglon = listaDetalleRenglones.find(function (item) {
            return String(item.id_lista_detalle_erp) === String(idRenglon);
        }) || {};
        var texto = "Se eliminara este renglon de la lista. No afecta Catalogo ni costos aplicados.";
        if (renglon.sku_proveedor || renglon.descripcion_proveedor) {
            texto += "\n\n" + [renglon.sku_proveedor, renglon.descripcion_proveedor].filter(Boolean).join(" | ");
        }
        Swal.fire({
            text: texto,
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Eliminar",
            cancelButtonText: "Cancelar"
        }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }
            post("/proveedor/proveedor_lista_detalle_eliminar_erp", {
                id_proveedor: proveedorActual.id_proveedor,
                id_lista_proveedor_erp: listaDetalleActual.id_lista_proveedor_erp,
                id_lista_detalle_erp: idRenglon
            }).then(function (response) {
                if (response.error) {
                    throw new Error(response.mensaje || "No fue posible eliminar el renglon.");
                }
                Swal.fire({text: response.mensaje, icon: "success", confirmButtonText: "Aceptar"});
                abrirDetalleLista(listaDetalleActual);
            }).catch(function (err) {
                Swal.fire({text: err.message, icon: "error", confirmButtonText: "Aceptar"});
            });
        });
    }

    function cambiarEstatusLista(idLista, estatus) {
        if (!proveedorActual || !idLista || !estatus || !permisos.autorizar) {
            return;
        }
        var mensajes = {
            validada: "La lista quedara validada si tiene al menos un renglon operativo y esos renglones tienen identidad, costo y moneda. Los demas quedan como evidencia del proveedor.",
            aplicada: "La lista quedara como aplicada solo si ya tiene relaciones o costos aplicados.",
            historica: "La lista quedara como historica y dejara de ser la referencia activa.",
            cancelada: "La lista quedara cancelada y no debe usarse operativamente."
        };
        Swal.fire({
            text: mensajes[estatus] || "Se actualizara el estatus de la lista.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Actualizar",
            cancelButtonText: "Cancelar"
        }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }
            post("/proveedor/proveedor_lista_estatus_erp", {
                id_proveedor: proveedorActual.id_proveedor,
                id_lista_proveedor_erp: idLista,
                estatus: estatus
            }).then(function (response) {
                if (response.error) {
                    throw new Error(response.mensaje || "No fue posible cambiar el estatus.");
                }
                Swal.fire({text: response.mensaje, icon: "success", confirmButtonText: "Aceptar"});
                abrirProveedor(proveedorActual.id_proveedor);
            }).catch(function (err) {
                Swal.fire({text: err.message, icon: "error", confirmButtonText: "Aceptar"});
            });
        });
    }

    function ejecutarMatchingDryRun() {
        if (!proveedorActual || !proveedorActual.id_proveedor || !listaDetalleActual || !listaDetalleActual.id_lista_proveedor_erp) {
            return;
        }
        document.getElementById("proveedores_erp_matching_subtitulo").textContent = listaDetalleActual.nombre_lista || "Propuestas sin escritura";
        document.getElementById("proveedores_erp_matching_resumen").innerHTML = "<span class=\"text-muted\">Calculando...</span>";
        document.getElementById("proveedores_erp_matching_body").innerHTML = "";
        document.getElementById("proveedores_erp_matching_error").classList.add("d-none");
        setInputValor("proveedores_erp_matching_buscar", "");
        setInputValor("proveedores_erp_matching_estado", "");
        actualizarBotonMatchingMasivo(0);
        bootstrap.Modal.getOrCreateInstance(document.getElementById("proveedores_erp_matching_modal")).show();
        get("/proveedor/proveedor_lista_matching_dry_run_erp", {
            id_proveedor: proveedorActual.id_proveedor,
            id_lista_proveedor_erp: listaDetalleActual.id_lista_proveedor_erp
        }).then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje || "No fue posible calcular matching.");
            }
            matchingResumenActual = (response.depurar && response.depurar.resumen) || {};
            matchingPropuestasActuales = (response.depurar && response.depurar.propuestas) || [];
            renderMatching(response.depurar || {});
        }).catch(function (err) {
            document.getElementById("proveedores_erp_matching_resumen").innerHTML = "";
            document.getElementById("proveedores_erp_matching_body").innerHTML = "";
            var error = document.getElementById("proveedores_erp_matching_error");
            error.textContent = err.message;
            error.classList.remove("d-none");
        });
    }

    function renderMatching(data) {
        var resumen = data.resumen || matchingResumenActual || {};
        var orden = ["total", "relacionado", "match_exacto_pendiente", "match_posible", "ambiguo", "sin_match"];
        document.getElementById("proveedores_erp_matching_resumen").innerHTML = orden.map(function (clave) {
            return "<span class=\"badge badge-light-primary fs-7\">" + esc(clave.replace(/_/g, " ")) + ": " + esc(resumen[clave] || 0) + "</span>";
        }).join("");

        var propuestas = filtrarMatchingPropuestas(matchingPropuestasActuales.length ? matchingPropuestasActuales : (data.propuestas || []));
        var conteo = document.getElementById("proveedores_erp_matching_conteo");
        if (conteo) {
            conteo.textContent = propuestas.length + " de " + ((data.propuestas || matchingPropuestasActuales || []).length) + " renglones";
        }
        actualizarBotonMatchingMasivo(contarMatchingMasivoElegible(matchingPropuestasActuales.length ? matchingPropuestasActuales : (data.propuestas || [])));
        document.getElementById("proveedores_erp_matching_body").innerHTML = propuestas.map(function (p) {
            var proveedor = [p.sku_proveedor, p.codigo_barras, p.codigo_interno].filter(Boolean).join(" | ") || "Sin codigo";
            var candidatos = renderCandidatosMatching(p);
            var acciones = "<div class=\"d-flex flex-wrap gap-2 mt-2\">" +
                "<button class=\"btn btn-sm btn-light-warning\" type=\"button\" data-matching-decision=\"ambiguo\" data-renglon=\"" + esc(p.id_lista_detalle_erp) + "\" data-criterio=\"" + esc(p.criterio_match || "") + "\">Ambiguo</button>" +
                "<button class=\"btn btn-sm btn-light-info\" type=\"button\" data-matching-decision=\"sin_match\" data-renglon=\"" + esc(p.id_lista_detalle_erp) + "\" data-criterio=\"" + esc(p.criterio_match || "") + "\">Sin match</button>" +
                "<button class=\"btn btn-sm btn-light-danger\" type=\"button\" data-matching-decision=\"rechazado\" data-renglon=\"" + esc(p.id_lista_detalle_erp) + "\" data-criterio=\"" + esc(p.criterio_match || "") + "\">Rechazar</button>" +
                "</div>";
            return "<tr>" +
                "<td><div class=\"fw-bold\">" + esc(proveedor) + "</div><span class=\"text-muted fs-8\">" + esc(p.descripcion_proveedor || "") + "</span></td>" +
                "<td><span class=\"badge " + claseEstadoMatching(p.estado_match) + "\">" + esc(p.estado_match || "-") + "</span></td>" +
                "<td>" + candidatos + acciones + "</td>" +
                "<td>" + esc(p.criterio_match || "-") + "</td>" +
                "</tr>";
        }).join("") || "<tr><td colspan=\"4\" class=\"text-center text-muted py-6\">Sin propuestas</td></tr>";
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-16
     * Proposito: identificar candidatos seguros para seleccion masiva antes de guardar decisiones.
     * Impacto: UI de matching de Proveedores; el servidor recalcula y valida nuevamente antes de escribir.
     */
    function esMatchingMasivoElegible(p) {
        var candidatos = p && Array.isArray(p.candidatos) ? p.candidatos : [];
        var estado = String(p && p.estado_match || "");
        var estadoActual = String(p && p.estado_match_actual || "");
        var criterio = String(p && p.criterio_match || "");
        return candidatos.length === 1 &&
            ["match_seleccionado", "relacion_aplicada", "costo_aplicado"].indexOf(estadoActual) < 0 &&
            ["relacionado", "match_exacto_pendiente"].indexOf(estado) >= 0 &&
            ["relacion_activa_proveedor_sku", "incidencia_catalogo_sku_temporal", "sku_o_codigo_exacto"].indexOf(criterio) >= 0 &&
            Number(candidatos[0].id_sku || 0) > 0;
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-16
     * Proposito: mostrar al operador cuantos matches seguros puede seleccionar en lote.
     * Impacto: UI de matching de Proveedores; no ejecuta escrituras.
     */
    function contarMatchingMasivoElegible(propuestas) {
        return (propuestas || []).filter(esMatchingMasivoElegible).length;
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-16
     * Proposito: actualizar estado y texto del boton de seleccion masiva.
     * Impacto: UI de matching de Proveedores; evita habilitar acciones sin candidatos confiables.
     */
    function actualizarBotonMatchingMasivo(total) {
        var boton = document.getElementById("proveedores_erp_matching_masivo");
        if (!boton) {
            return;
        }
        boton.disabled = !(permisos.matching && Number(total || 0) > 0);
        boton.innerHTML = "<i class=\"bi bi-magic\"></i> Seleccionar confiables" + (Number(total || 0) > 0 ? " (" + esc(total) + ")" : "");
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-16
     * Proposito: confirmar y solicitar al servidor la seleccion masiva de matches confiables.
     * Impacto: Proveedores ERP; guarda decisiones de matching, pero no aplica relaciones ni costos.
     */
    function seleccionarMatchingMasivo() {
        if (!proveedorActual || !listaDetalleActual || !permisos.matching) {
            return;
        }
        var total = contarMatchingMasivoElegible(matchingPropuestasActuales);
        if (total <= 0) {
            Swal.fire({text: "No hay candidatos confiables para seleccionar en lote.", icon: "info", confirmButtonText: "Aceptar"});
            return;
        }
        Swal.fire({
            title: "Seleccionar matches confiables",
            text: "Se seleccionaran " + total + " candidato(s) exactos o ya relacionados. No se aplicaran relaciones ni costos; los ambiguos y posibles quedaran para revision manual.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Seleccionar confiables",
            cancelButtonText: "Cancelar"
        }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }
            var boton = document.getElementById("proveedores_erp_matching_masivo");
            if (boton) {
                boton.disabled = true;
            }
            post("/proveedor/proveedor_lista_matching_masivo_erp", {
                id_proveedor: proveedorActual.id_proveedor,
                id_lista_proveedor_erp: listaDetalleActual.id_lista_proveedor_erp
            }).then(function (response) {
                if (response.error) {
                    throw new Error(response.mensaje || "No fue posible seleccionar matching masivo.");
                }
                Swal.fire({text: response.mensaje, icon: "success", confirmButtonText: "Aceptar"});
                ejecutarMatchingDryRun();
                abrirDetalleLista(listaDetalleActual);
            }).catch(function (err) {
                Swal.fire({text: err.message, icon: "error", confirmButtonText: "Aceptar"});
            }).finally(function () {
                actualizarBotonMatchingMasivo(contarMatchingMasivoElegible(matchingPropuestasActuales));
            });
        });
    }

    function filtrarMatchingPropuestas(propuestas) {
        var texto = String(valor("proveedores_erp_matching_buscar") || "").toLowerCase().trim();
        var estado = String(valor("proveedores_erp_matching_estado") || "");
        return (propuestas || []).filter(function (p) {
            if (estado && String(p.estado_match || "") !== estado) {
                return false;
            }
            if (!texto) {
                return true;
            }
            var candidatos = (p.candidatos || []).map(function (c) {
                return [c.sku, c.nombre, c.sku_proveedor, c.criterio].filter(Boolean).join(" ");
            }).join(" ");
            var base = [p.sku_proveedor, p.codigo_barras, p.codigo_interno, p.descripcion_proveedor, p.criterio_match, candidatos].filter(Boolean).join(" ").toLowerCase();
            return base.indexOf(texto) >= 0;
        });
    }

    function renderCandidatosMatching(p) {
        var candidatos = p.candidatos || [];
        if (!candidatos.length) {
            return "<div class=\"text-muted\">Sin candidato confiable</div>";
        }
        return candidatos.map(function (c, index) {
            var titulo = [c.sku || "SKU", c.nombre || ""].filter(Boolean).join(" | ");
            var relacion = c.id_sku_proveedor ? "Relacion proveedor existente" : (c.criterio === "incidencia_catalogo_sku_temporal" ? "SKU temporal creado desde incidencia de Catalogo" : "SKU ERP candidato");
            return "<div class=\"border rounded p-2 mb-2\">" +
                "<div class=\"d-flex flex-wrap align-items-center gap-2\">" +
                    "<span class=\"badge badge-light\">" + esc(index + 1) + "</span>" +
                    "<div class=\"fw-semibold flex-grow-1\">" + esc(titulo) + "</div>" +
                    "<span class=\"badge badge-light-info\">" + esc(c.criterio || p.criterio_match || "-") + "</span>" +
                    (c.estatus ? "<span class=\"badge badge-light-primary\">" + esc(c.estatus) + "</span>" : "") +
                "</div>" +
                "<div class=\"text-muted fs-8 mt-1\">" + esc(relacion) + (c.sku_proveedor ? " | SKU proveedor: " + esc(c.sku_proveedor) : "") + "</div>" +
                (permisos.matching ? "<button class=\"btn btn-sm btn-light-primary mt-2\" type=\"button\" data-matching-decision=\"match_seleccionado\" data-renglon=\"" + esc(p.id_lista_detalle_erp) + "\" data-sku=\"" + esc(c.id_sku || "") + "\" data-sku-proveedor=\"" + esc(c.id_sku_proveedor || "") + "\" data-criterio=\"" + esc(p.criterio_match || c.criterio || "") + "\">Seleccionar este SKU</button>" : "") +
            "</div>";
        }).join("");
    }

    function ejecutarPreviewRelacionesLote() {
        if (!proveedorActual || !proveedorActual.id_proveedor || !listaDetalleActual || !listaDetalleActual.id_lista_proveedor_erp || !permisos.matching) {
            return;
        }
        document.getElementById("proveedores_erp_relaciones_lote_subtitulo").textContent = listaDetalleActual.nombre_lista || "Solo lectura, sin aplicar cambios";
        document.getElementById("proveedores_erp_relaciones_lote_resumen").innerHTML = "<span class=\"text-muted\">Calculando...</span>";
        document.getElementById("proveedores_erp_relaciones_lote_incluidos_body").innerHTML = "";
        document.getElementById("proveedores_erp_relaciones_lote_excluidos_body").innerHTML = "";
        document.getElementById("proveedores_erp_relaciones_lote_error").classList.add("d-none");
        relacionesLotePreviewActual = null;
        actualizarBotonAplicarRelacionesLote();
        bootstrap.Modal.getOrCreateInstance(document.getElementById("proveedores_erp_relaciones_lote_modal")).show();
        get("/proveedor/proveedor_sku_relaciones_lote_preview_erp", {
            id_proveedor: proveedorActual.id_proveedor,
            id_lista_proveedor_erp: listaDetalleActual.id_lista_proveedor_erp
        }).then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje || "No fue posible calcular preview de relaciones.");
            }
            relacionesLotePreviewActual = response.depurar || {};
            renderPreviewRelacionesLote(response.depurar || {});
        }).catch(function (err) {
            document.getElementById("proveedores_erp_relaciones_lote_resumen").innerHTML = "";
            document.getElementById("proveedores_erp_relaciones_lote_incluidos_body").innerHTML = "";
            document.getElementById("proveedores_erp_relaciones_lote_excluidos_body").innerHTML = "";
            relacionesLotePreviewActual = null;
            actualizarBotonAplicarRelacionesLote();
            var error = document.getElementById("proveedores_erp_relaciones_lote_error");
            error.textContent = err.message;
            error.classList.remove("d-none");
        });
    }

    function renderPreviewRelacionesLote(data) {
        var resumen = data.resumen || {};
        actualizarBotonAplicarRelacionesLote();
        document.getElementById("proveedores_erp_relaciones_lote_resumen").innerHTML =
            badge("Total: " + (resumen.total || 0), "primary") +
            badge("Incluidos: " + (resumen.incluidos || 0), Number(resumen.incluidos || 0) > 0 ? "success" : "secondary") +
            badge("Crear: " + (resumen.crear_relacion || 0), "info") +
            badge("Actualizar: " + (resumen.actualizar_relacion || 0), "warning") +
            badge("Excluidos: " + (resumen.excluidos || 0), Number(resumen.excluidos || 0) > 0 ? "danger" : "success") +
            badge("Sin SKU: " + (resumen.sin_sku || 0), "secondary") +
            badge("Compra incompleta: " + (resumen.compra_incompleta || 0), "warning") +
            badge("Ya relacionado: " + (resumen.ya_relacionado || 0), "secondary");

        document.getElementById("proveedores_erp_relaciones_lote_incluidos_body").innerHTML = (data.incluidos || []).map(function (x) {
            var proveedor = [x.sku_proveedor, x.codigo_barras, x.codigo_interno].filter(Boolean).join(" | ") || "Sin codigo";
            var compra = [
                x.id_unidad_compra ? "Unidad " + x.id_unidad_compra : "",
                x.factor_conversion ? "Factor " + x.factor_conversion : "",
                x.cantidad_minima ? "Min " + x.cantidad_minima : ""
            ].filter(Boolean).join(" | ");
            return "<tr>" +
                "<td><div class=\"fw-bold\">" + esc(proveedor) + "</div><span class=\"text-muted fs-8\">" + esc(x.descripcion_proveedor || "") + "</span></td>" +
                "<td>" + esc(x.id_sku || "-") + "</td>" +
                "<td><span class=\"badge " + (x.accion === "crear" ? "badge-light-info" : "badge-light-warning") + "\">" + esc(x.accion || "-") + "</span><div class=\"text-muted fs-8 mt-1\">" + esc(x.detalle || "") + "</div></td>" +
                "<td>" + esc(compra || "-") + "</td>" +
                "</tr>";
        }).join("") || "<tr><td colspan=\"4\" class=\"text-center text-muted py-6\">No hay renglones listos para aplicar relacion</td></tr>";

        document.getElementById("proveedores_erp_relaciones_lote_excluidos_body").innerHTML = (data.excluidos || []).map(function (x) {
            var proveedor = [x.sku_proveedor, x.codigo_barras, x.codigo_interno].filter(Boolean).join(" | ") || "Sin codigo";
            return "<tr>" +
                "<td><div class=\"fw-bold\">" + esc(proveedor) + "</div><span class=\"text-muted fs-8\">" + esc(x.descripcion_proveedor || "") + "</span></td>" +
                "<td>" + esc(x.id_sku || "-") + "</td>" +
                "<td><span class=\"badge badge-light-secondary\">" + esc(x.motivo || "-") + "</span></td>" +
                "<td>" + esc(x.detalle || "-") + "</td>" +
                "</tr>";
        }).join("") || "<tr><td colspan=\"4\" class=\"text-center text-muted py-6\">Sin excluidos</td></tr>";
    }

    function actualizarBotonAplicarRelacionesLote() {
        var boton = document.getElementById("proveedores_erp_relaciones_lote_aplicar");
        if (!boton) {
            return;
        }
        var incluidos = relacionesLotePreviewActual && relacionesLotePreviewActual.resumen ? Number(relacionesLotePreviewActual.resumen.incluidos || 0) : 0;
        boton.disabled = !(permisos.matching && incluidos > 0);
        boton.innerHTML = "<i class=\"bi bi-link-45deg\"></i> Aplicar relaciones incluidas" + (incluidos > 0 ? " (" + incluidos + ")" : "");
    }

    function aplicarRelacionesLote() {
        if (!proveedorActual || !listaDetalleActual || !permisos.matching) {
            return;
        }
        var resumen = relacionesLotePreviewActual && relacionesLotePreviewActual.resumen ? relacionesLotePreviewActual.resumen : {};
        var incluidos = Number(resumen.incluidos || 0);
        if (incluidos <= 0) {
            Swal.fire({text: "No hay relaciones incluidas para aplicar.", icon: "warning", confirmButtonText: "Aceptar"});
            return;
        }
        Swal.fire({
            text: "Se aplicaran " + incluidos + " relaciones proveedor-SKU. No se aplicaran costos ni se tocara costo_referencia.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Aplicar relaciones",
            cancelButtonText: "Cancelar"
        }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }
            var boton = document.getElementById("proveedores_erp_relaciones_lote_aplicar");
            if (boton) {
                boton.disabled = true;
            }
            post("/proveedor/proveedor_sku_relaciones_lote_aplicar_erp", {
                id_proveedor: proveedorActual.id_proveedor,
                id_lista_proveedor_erp: listaDetalleActual.id_lista_proveedor_erp
            }).then(function (response) {
                if (response.error) {
                    throw new Error(response.mensaje || "No fue posible aplicar relaciones en lote.");
                }
                var datos = response.depurar || {};
                Swal.fire({
                    text: response.mensaje + ". Aplicados: " + (datos.aplicados || 0) + ", creados: " + (datos.creados || 0) + ", actualizados: " + (datos.actualizados || 0) + ".",
                    icon: "success",
                    confirmButtonText: "Aceptar"
                });
                relacionesLotePreviewActual = null;
                actualizarBotonAplicarRelacionesLote();
                var modal = bootstrap.Modal.getInstance(document.getElementById("proveedores_erp_relaciones_lote_modal"));
                if (modal) {
                    modal.hide();
                }
                abrirDetalleLista(listaDetalleActual);
            }).catch(function (err) {
                Swal.fire({text: err.message, icon: "error", confirmButtonText: "Aceptar"});
                actualizarBotonAplicarRelacionesLote();
            });
        });
    }

    function ejecutarPreviewCostosLote() {
        if (!proveedorActual || !proveedorActual.id_proveedor || !listaDetalleActual || !listaDetalleActual.id_lista_proveedor_erp || !permisos.costos) {
            return;
        }
        document.getElementById("proveedores_erp_costos_lote_subtitulo").textContent = listaDetalleActual.nombre_lista || "Solo lectura, sin aplicar cambios";
        document.getElementById("proveedores_erp_costos_lote_resumen").innerHTML = "<span class=\"text-muted\">Calculando...</span>";
        document.getElementById("proveedores_erp_costos_lote_incluidos_body").innerHTML = "";
        document.getElementById("proveedores_erp_costos_lote_excluidos_body").innerHTML = "";
        document.getElementById("proveedores_erp_costos_lote_error").classList.add("d-none");
        costosLotePreviewActual = null;
        actualizarBotonAplicarCostosLote();
        bootstrap.Modal.getOrCreateInstance(document.getElementById("proveedores_erp_costos_lote_modal")).show();
        get("/proveedor/proveedor_costos_lote_preview_erp", {
            id_proveedor: proveedorActual.id_proveedor,
            id_lista_proveedor_erp: listaDetalleActual.id_lista_proveedor_erp
        }).then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje || "No fue posible calcular preview de costos.");
            }
            costosLotePreviewActual = response.depurar || {};
            renderPreviewCostosLote(response.depurar || {});
        }).catch(function (err) {
            document.getElementById("proveedores_erp_costos_lote_resumen").innerHTML = "";
            document.getElementById("proveedores_erp_costos_lote_incluidos_body").innerHTML = "";
            document.getElementById("proveedores_erp_costos_lote_excluidos_body").innerHTML = "";
            costosLotePreviewActual = null;
            actualizarBotonAplicarCostosLote();
            var error = document.getElementById("proveedores_erp_costos_lote_error");
            error.textContent = err.message;
            error.classList.remove("d-none");
        });
    }

    function renderPreviewCostosLote(data) {
        var resumen = data.resumen || {};
        actualizarBotonAplicarCostosLote();
        document.getElementById("proveedores_erp_costos_lote_resumen").innerHTML =
            badge("Total: " + (resumen.total || 0), "primary") +
            badge("Incluidos: " + (resumen.incluidos || 0), Number(resumen.incluidos || 0) > 0 ? "success" : "secondary") +
            badge("Crear costo: " + (resumen.crear_costo || 0), "info") +
            badge("Actualizar costo: " + (resumen.actualizar_costo || 0), "warning") +
            badge("Excluidos: " + (resumen.excluidos || 0), Number(resumen.excluidos || 0) > 0 ? "danger" : "success") +
            badge("Sin relacion: " + (resumen.sin_relacion || 0), "secondary") +
            badge("Costo incompleto: " + (resumen.costo_incompleto || 0), "warning") +
            badge("Impuestos sin definir: " + (resumen.impuestos_sin_definir || 0), "warning");

        document.getElementById("proveedores_erp_costos_lote_incluidos_body").innerHTML = (data.incluidos || []).map(function (x) {
            var proveedor = [x.sku_proveedor, x.codigo_barras, x.codigo_interno].filter(Boolean).join(" | ") || "Sin codigo";
            var costo = [x.costo || "-", x.moneda || ""].filter(Boolean).join(" ");
            var relacion = [x.id_sku ? "SKU " + x.id_sku : "", x.id_sku_proveedor ? "Relacion " + x.id_sku_proveedor : ""].filter(Boolean).join(" | ");
            return "<tr>" +
                "<td><div class=\"fw-bold\">" + esc(proveedor) + "</div><span class=\"text-muted fs-8\">" + esc(x.descripcion_proveedor || "") + "</span></td>" +
                "<td>" + esc(relacion || "-") + "</td>" +
                "<td><div class=\"fw-bold\">" + esc(costo) + "</div><span class=\"text-muted fs-8\">Impuestos: " + esc(x.costo_incluye_impuestos == null ? "-" : x.costo_incluye_impuestos) + "</span></td>" +
                "<td><span class=\"badge " + (x.accion === "crear" ? "badge-light-info" : "badge-light-warning") + "\">" + esc(x.accion || "-") + "</span><div class=\"text-muted fs-8 mt-1\">" + esc(x.detalle || "") + "</div></td>" +
                "</tr>";
        }).join("") || "<tr><td colspan=\"4\" class=\"text-center text-muted py-6\">No hay renglones listos para aplicar costo</td></tr>";

        document.getElementById("proveedores_erp_costos_lote_excluidos_body").innerHTML = (data.excluidos || []).map(function (x) {
            var proveedor = [x.sku_proveedor, x.codigo_barras, x.codigo_interno].filter(Boolean).join(" | ") || "Sin codigo";
            var relacion = [x.id_sku ? "SKU " + x.id_sku : "", x.id_sku_proveedor ? "Relacion " + x.id_sku_proveedor : ""].filter(Boolean).join(" | ");
            return "<tr>" +
                "<td><div class=\"fw-bold\">" + esc(proveedor) + "</div><span class=\"text-muted fs-8\">" + esc(x.descripcion_proveedor || "") + "</span></td>" +
                "<td>" + esc(relacion || "-") + "</td>" +
                "<td><span class=\"badge badge-light-secondary\">" + esc(x.motivo || "-") + "</span></td>" +
                "<td>" + esc(x.detalle || "-") + "</td>" +
                "</tr>";
        }).join("") || "<tr><td colspan=\"4\" class=\"text-center text-muted py-6\">Sin excluidos</td></tr>";
    }

    function ejecutarPreviewCostoReferencia() {
        if (!proveedorActual || !proveedorActual.id_proveedor || !listaDetalleActual || !listaDetalleActual.id_lista_proveedor_erp || !permisos.costos) {
            return;
        }
        document.getElementById("proveedores_erp_costo_referencia_subtitulo").textContent = listaDetalleActual.nombre_lista || "Solo lectura, sin aplicar cambios";
        document.getElementById("proveedores_erp_costo_referencia_resumen").innerHTML = "<span class=\"text-muted\">Calculando...</span>";
        document.getElementById("proveedores_erp_costo_referencia_body").innerHTML = "";
        document.getElementById("proveedores_erp_costo_referencia_error").classList.add("d-none");
        costoReferenciaPreviewActual = null;
        actualizarBotonAplicarCostoReferencia();
        bootstrap.Modal.getOrCreateInstance(document.getElementById("proveedores_erp_costo_referencia_modal")).show();
        get("/proveedor/proveedor_costo_referencia_preview_erp", {
            id_proveedor: proveedorActual.id_proveedor,
            id_lista_proveedor_erp: listaDetalleActual.id_lista_proveedor_erp
        }).then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje || "No fue posible calcular preview de costo referencia.");
            }
            costoReferenciaPreviewActual = response.depurar || {};
            renderPreviewCostoReferencia(costoReferenciaPreviewActual);
        }).catch(function (err) {
            document.getElementById("proveedores_erp_costo_referencia_resumen").innerHTML = "";
            document.getElementById("proveedores_erp_costo_referencia_body").innerHTML = "";
            costoReferenciaPreviewActual = null;
            actualizarBotonAplicarCostoReferencia();
            var error = document.getElementById("proveedores_erp_costo_referencia_error");
            error.textContent = err.message;
            error.classList.remove("d-none");
        });
    }

    function renderPreviewCostoReferencia(data) {
        var resumen = data.resumen || {};
        actualizarBotonAplicarCostoReferencia();
        document.getElementById("proveedores_erp_costo_referencia_resumen").innerHTML =
            badge("Total: " + (resumen.total || 0), "primary") +
            badge("Elegibles: " + (resumen.elegibles_aplicar || 0), Number(resumen.elegibles_aplicar || 0) > 0 ? "success" : "secondary") +
            badge("Bloqueados: " + (resumen.bloqueados_aplicar || 0), Number(resumen.bloqueados_aplicar || 0) > 0 ? "warning" : "success") +
            badge("Sin costo ref: " + (resumen.sin_costo_referencia || 0), "warning") +
            badge("Con cambio: " + (resumen.con_cambio || 0), "info") +
            badge("Sin cambio: " + (resumen.sin_cambio || 0), "secondary") +
            badge("Cambio >= 10%: " + (resumen.cambio_mayor_10 || 0), "danger") +
            badge("Moneda no MXN: " + (resumen.moneda_no_mxn || 0), "warning") +
            badge("Compra real: " + (resumen.fuente_compra_real || 0), "success") +
            badge("Proveedor vigente: " + (resumen.fuente_proveedor_vigente || 0), "info") +
            badge("Proveedor preferido: " + (resumen.proveedor_preferido || 0), "success");

        document.getElementById("proveedores_erp_costo_referencia_body").innerHTML = (data.propuestas || []).map(function (x) {
            var deltaPct = x.delta_pct === null || x.delta_pct === undefined ? "-" : Number(x.delta_pct).toFixed(2) + "%";
            var delta = Number(x.delta || 0);
            var compra = x.compra_real || null;
            var fuente = x.fuente_sugerida === "compra_real"
                ? "<span class=\"badge badge-light-success\">Compra real</span>"
                : "<span class=\"badge badge-light-info\">Proveedor vigente</span>";
            var aplicacion = x.puede_aplicar_costo_referencia
                ? "<span class=\"badge badge-light-success me-1 mb-1\">Elegible</span>"
                : "<span class=\"badge badge-light-danger me-1 mb-1\">Bloqueado</span>";
            var detalleFuente = compra
                ? "OC " + esc(compra.id_orden_compra || "-") + " | Recibido: " + esc(numeroCantidad(compra.cantidad_recibida || 0))
                : "Costo proveedor: " + esc(numeroMoneda(x.costo_proveedor || 0)) + " " + esc(x.moneda_proveedor || "");
            var advertencias = (x.advertencias || []).map(function (a) {
                return "<span class=\"badge badge-light-warning me-1 mb-1\">" + esc(a) + "</span>";
            }).join("") || "<span class=\"badge badge-light-success\">Sin advertencias</span>";
            return "<tr>" +
                "<td><div class=\"fw-bold\">" + esc(x.sku_erp || ("SKU " + x.id_sku)) + "</div><span class=\"text-muted fs-8\">" + esc(x.sku_nombre || "") + "</span></td>" +
                "<td><div>" + esc(x.sku_proveedor || "-") + "</div><span class=\"text-muted fs-8\">" + (Number(x.es_preferido || 0) === 1 ? "Preferido" : "No preferido") + "</span></td>" +
                "<td class=\"text-end\">" + esc(numeroMoneda(x.costo_referencia_actual)) + "</td>" +
                "<td class=\"text-end\"><div class=\"fw-bold\">" + esc(numeroMoneda(x.costo_propuesto)) + "</div><div class=\"mt-1\">" + fuente + "</div><span class=\"text-muted fs-8\">" + esc(x.moneda || "-") + " | " + detalleFuente + "</span></td>" +
                "<td><span class=\"badge " + (delta >= 0 ? "badge-light-danger" : "badge-light-success") + "\">" + esc(numeroMoneda(delta)) + " / " + esc(deltaPct) + "</span><div class=\"text-muted fs-8 mt-1\">" + esc(x.accion_sugerida || "") + "</div></td>" +
                "<td>" + aplicacion + advertencias + "</td>" +
                "</tr>";
        }).join("") || "<tr><td colspan=\"6\" class=\"text-center text-muted py-6\">No hay costos vigentes de esta lista para comparar</td></tr>";
    }

    function actualizarBotonAplicarCostoReferencia() {
        var boton = document.getElementById("proveedores_erp_costo_referencia_aplicar");
        if (!boton) {
            return;
        }
        var resumen = costoReferenciaPreviewActual && costoReferenciaPreviewActual.resumen ? costoReferenciaPreviewActual.resumen : {};
        var elegibles = Number(resumen.elegibles_aplicar || 0);
        boton.disabled = !(permisos.costos && elegibles > 0);
        boton.innerHTML = "<i class=\"bi bi-check2-circle\"></i> Aplicar elegibles" + (elegibles > 0 ? " (" + elegibles + ")" : "");
    }

    function aplicarCostoReferencia() {
        if (!proveedorActual || !listaDetalleActual || !permisos.costos) {
            return;
        }
        var resumen = costoReferenciaPreviewActual && costoReferenciaPreviewActual.resumen ? costoReferenciaPreviewActual.resumen : {};
        var elegibles = Number(resumen.elegibles_aplicar || 0);
        if (elegibles <= 0) {
            Swal.fire({text: "No hay costos de referencia elegibles para aplicar.", icon: "warning", confirmButtonText: "Aceptar"});
            return;
        }
        Swal.fire({
            text: "Se aplicaran " + elegibles + " costos de referencia elegibles. El servidor recalculara la lista y bloqueara moneda distinta de MXN o cambios no permitidos desde proveedor vigente.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Aplicar costo referencia",
            cancelButtonText: "Cancelar"
        }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }
            var boton = document.getElementById("proveedores_erp_costo_referencia_aplicar");
            if (boton) {
                boton.disabled = true;
            }
            post("/proveedor/proveedor_costo_referencia_aplicar_erp", {
                id_proveedor: proveedorActual.id_proveedor,
                id_lista_proveedor_erp: listaDetalleActual.id_lista_proveedor_erp
            }).then(function (response) {
                if (response.error) {
                    throw new Error(response.mensaje || "No fue posible aplicar costo_referencia.");
                }
                var datos = response.depurar || {};
                Swal.fire({
                    text: response.mensaje + ". Aplicados: " + (datos.aplicados || 0) + ", excluidos: " + (datos.excluidos || 0) + ".",
                    icon: "success",
                    confirmButtonText: "Aceptar"
                });
                costoReferenciaPreviewActual = null;
                actualizarBotonAplicarCostoReferencia();
                var modal = bootstrap.Modal.getInstance(document.getElementById("proveedores_erp_costo_referencia_modal"));
                if (modal) {
                    modal.hide();
                }
                abrirDetalleLista(listaDetalleActual);
                cargarCostosProveedor();
            }).catch(function (err) {
                Swal.fire({text: err.message, icon: "error", confirmButtonText: "Aceptar"});
                actualizarBotonAplicarCostoReferencia();
            });
        });
    }

    function numeroMoneda(valor) {
        var numero = Number(valor || 0);
        return numero.toLocaleString("es-MX", {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    function numeroCantidad(valor) {
        var numero = Number(valor || 0);
        return numero.toLocaleString("es-MX", {minimumFractionDigits: 0, maximumFractionDigits: 6});
    }

    function actualizarBotonAplicarCostosLote() {
        var boton = document.getElementById("proveedores_erp_costos_lote_aplicar");
        if (!boton) {
            return;
        }
        var incluidos = costosLotePreviewActual && costosLotePreviewActual.resumen ? Number(costosLotePreviewActual.resumen.incluidos || 0) : 0;
        boton.disabled = !(permisos.costos && incluidos > 0);
        boton.innerHTML = "<i class=\"bi bi-currency-dollar\"></i> Aplicar costos incluidos" + (incluidos > 0 ? " (" + incluidos + ")" : "");
    }

    function aplicarCostosLote() {
        if (!proveedorActual || !listaDetalleActual || !permisos.costos) {
            return;
        }
        var resumen = costosLotePreviewActual && costosLotePreviewActual.resumen ? costosLotePreviewActual.resumen : {};
        var incluidos = Number(resumen.incluidos || 0);
        if (incluidos <= 0) {
            Swal.fire({text: "No hay costos incluidos para aplicar.", icon: "warning", confirmButtonText: "Aceptar"});
            return;
        }
        Swal.fire({
            text: "Se aplicaran " + incluidos + " costos como vigentes. Se actualizara costo_ultimo de la relacion, pero no se tocara costo_referencia.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Aplicar costos",
            cancelButtonText: "Cancelar"
        }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }
            var boton = document.getElementById("proveedores_erp_costos_lote_aplicar");
            if (boton) {
                boton.disabled = true;
            }
            post("/proveedor/proveedor_costos_lote_aplicar_erp", {
                id_proveedor: proveedorActual.id_proveedor,
                id_lista_proveedor_erp: listaDetalleActual.id_lista_proveedor_erp
            }).then(function (response) {
                if (response.error) {
                    throw new Error(response.mensaje || "No fue posible aplicar costos en lote.");
                }
                var datos = response.depurar || {};
                Swal.fire({
                    text: response.mensaje + ". Aplicados: " + (datos.aplicados || 0) + ", creados: " + (datos.creados || 0) + ", actualizados: " + (datos.actualizados || 0) + ".",
                    icon: "success",
                    confirmButtonText: "Aceptar"
                });
                costosLotePreviewActual = null;
                actualizarBotonAplicarCostosLote();
                var modal = bootstrap.Modal.getInstance(document.getElementById("proveedores_erp_costos_lote_modal"));
                if (modal) {
                    modal.hide();
                }
                abrirDetalleLista(listaDetalleActual);
                cargarCostosProveedor();
            }).catch(function (err) {
                Swal.fire({text: err.message, icon: "error", confirmButtonText: "Aceptar"});
                actualizarBotonAplicarCostosLote();
            });
        });
    }

    function claseEstadoMatching(estado) {
        var clases = {
            relacionado: "badge-light-success",
            match_seleccionado: "badge-light-success",
            match_exacto_pendiente: "badge-light-primary",
            match_posible: "badge-light-info",
            ambiguo: "badge-light-warning",
            sin_match: "badge-light-danger"
        };
        return clases[estado] || "badge-light";
    }

    function ejecutarIncidenciasDryRun() {
        if (!proveedorActual || !proveedorActual.id_proveedor || !listaDetalleActual || !listaDetalleActual.id_lista_proveedor_erp) {
            return;
        }
        document.getElementById("proveedores_erp_incidencias_subtitulo").textContent = listaDetalleActual.nombre_lista || "Rutas de solucion sin aplicar cambios automaticos";
        document.getElementById("proveedores_erp_incidencias_resumen").innerHTML = "<span class=\"text-muted\">Calculando...</span>";
        document.getElementById("proveedores_erp_incidencias_body").innerHTML = "";
        document.getElementById("proveedores_erp_incidencias_error").classList.add("d-none");
        setInputValor("proveedores_erp_incidencias_buscar", "");
        setInputValor("proveedores_erp_incidencias_severidad", "");
        setInputValor("proveedores_erp_incidencias_tipo", "");
        bootstrap.Modal.getOrCreateInstance(document.getElementById("proveedores_erp_incidencias_modal")).show();
        Promise.all([
            get("/proveedor/proveedor_incidencias_dry_run_erp", {
                id_proveedor: proveedorActual.id_proveedor,
                id_lista_proveedor_erp: listaDetalleActual.id_lista_proveedor_erp
            }),
            get("/proveedor/proveedor_incidencias_listar_erp", {
                id_proveedor: proveedorActual.id_proveedor,
                id_lista_proveedor_erp: listaDetalleActual.id_lista_proveedor_erp
            })
        ]).then(function (responses) {
            var response = responses[0];
            var reales = responses[1];
            if (response.error) {
                throw new Error(response.mensaje || "No fue posible calcular pendientes.");
            }
            if (reales.error) {
                throw new Error(reales.mensaje || "No fue posible consultar incidencias reales.");
            }
            incidenciasResumenActual = (response.depurar && response.depurar.resumen) || {};
            incidenciasPropuestasActuales = (response.depurar && response.depurar.propuestas) || [];
            incidenciasRealesActuales = (reales.depurar && reales.depurar.incidencias) || [];
            renderIncidenciasDryRun(response.depurar || {});
        }).catch(function (err) {
            document.getElementById("proveedores_erp_incidencias_resumen").innerHTML = "";
            document.getElementById("proveedores_erp_incidencias_body").innerHTML = "";
            var error = document.getElementById("proveedores_erp_incidencias_error");
            error.textContent = err.message;
            error.classList.remove("d-none");
        });
    }

    function renderIncidenciasDryRun(data) {
        var resumen = data.resumen || {};
        var severidad = resumen.por_severidad || {};
        var realesResumen = resumenIncidenciasReales(incidenciasRealesActuales);
        document.getElementById("proveedores_erp_incidencias_resumen").innerHTML =
            badge("Total: " + (resumen.total || 0), "primary") +
            badge("Bloqueantes: " + (severidad.bloqueante || 0), "danger") +
            badge("Altas: " + (severidad.alta || 0), "warning") +
            badge("Medias: " + (severidad.media || 0), "info") +
            badge("Incidencias reales abiertas: " + realesResumen.abiertas, realesResumen.abiertas ? "warning" : "success");
        var propuestas = filtrarIncidenciasPropuestas(incidenciasPropuestasActuales.length ? incidenciasPropuestasActuales : (data.propuestas || []));
        var reales = filtrarIncidenciasReales(incidenciasRealesActuales || []);
        var conteo = document.getElementById("proveedores_erp_incidencias_conteo");
        if (conteo) {
            conteo.textContent = propuestas.length + " propuestas, " + reales.length + " reales";
        }
        var filasReales = reales.map(renderIncidenciaRealProveedor).join("");
        var filasPropuestas = propuestas.map(function (p) {
            var evidencia = p.evidencia || {};
            var renglon = evidencia.renglon || {};
            var propuesta = p.propuesta || {};
            var resolucion = resolverPendienteProveedor(p);
            var referencia = [
                renglon.sku_proveedor || renglon.codigo_barras || renglon.codigo_interno || ("Renglon " + (p.id_referencia || "-")),
                renglon.sku_erp || "",
                renglon.sku_nombre || ""
            ].filter(Boolean).join(" | ");
            var accionesResolucion = accionesPendienteProveedor(p, resolucion);
            var accionCrear = permisos.autorizar
                ? "<button class=\"btn btn-sm btn-light-secondary\" type=\"button\" data-incidencia-crear=\"" + esc(p.id_referencia || "") + "\" data-tipo-incidencia=\"" + esc(p.tipo_incidencia || "") + "\">Enviar a Catalogo</button>"
                : "";
            return "<tr>" +
                "<td><div class=\"fw-bold\">" + esc(p.tipo_incidencia || "-") + "</div><span class=\"text-muted fs-8\">" + esc(p.titulo || "") + "</span></td>" +
                "<td><span class=\"badge " + claseSeveridadIncidencia(p.severidad) + "\">" + esc(p.severidad || "-") + "</span></td>" +
                "<td><div>" + esc(referencia || "-") + "</div><span class=\"text-muted fs-8\">" + esc(p.descripcion || "") + "</span></td>" +
                "<td>" +
                    "<div class=\"fw-semibold\">" + esc(resolucion.titulo) + "</div>" +
                    "<div class=\"text-muted fs-8 mb-2\">" + esc(resolucion.detalle) + "</div>" +
                    "<div class=\"fs-8 mb-2\"><span class=\"badge badge-light\">" + esc(resolucion.dueno) + "</span> " + esc(propuesta.accion || "") + "</div>" +
                    "<div class=\"d-flex flex-wrap gap-2\">" + accionesResolucion + accionCrear + "</div>" +
                "</td>" +
                "</tr>";
        }).join("");
        document.getElementById("proveedores_erp_incidencias_body").innerHTML =
            filasReales + filasPropuestas ||
            "<tr><td colspan=\"4\" class=\"text-center text-muted py-6\">Sin pendientes propuestos ni incidencias reales</td></tr>";
    }

    function resumenIncidenciasReales(incidencias) {
        var resumen = {total: 0, abiertas: 0, cerradas: 0};
        (incidencias || []).forEach(function (i) {
            resumen.total++;
            if (["resuelta", "descartada"].indexOf(String(i.estatus || "")) >= 0) {
                resumen.cerradas++;
            } else {
                resumen.abiertas++;
            }
        });
        return resumen;
    }

    function renderIncidenciaRealProveedor(i) {
        var referencia = i.referencia || {};
        var evidencia = i.evidencia || {};
        var renglon = evidencia.renglon || {};
        var refTexto = [
            referencia.sku_proveedor || renglon.sku_proveedor || referencia.codigo_barras || renglon.codigo_barras || referencia.codigo_interno || renglon.codigo_interno || ("Incidencia " + (i.id_incidencia_calidad || "-")),
            referencia.sku_erp || renglon.sku_erp || "",
            referencia.sku_nombre || renglon.sku_nombre || ""
        ].filter(Boolean).join(" | ");
        var cerrada = ["resuelta", "descartada"].indexOf(String(i.estatus || "")) >= 0;
        var acciones = "";
        if (!cerrada && permisos.autorizar) {
            acciones =
                "<button class=\"btn btn-sm btn-light-success\" type=\"button\" data-incidencia-resolver=\"" + esc(i.id_incidencia_calidad || "") + "\" data-estatus-incidencia=\"resuelta\">Resolver</button>" +
                "<button class=\"btn btn-sm btn-light-danger\" type=\"button\" data-incidencia-resolver=\"" + esc(i.id_incidencia_calidad || "") + "\" data-estatus-incidencia=\"descartada\">Descartar</button>";
        }
        return "<tr class=\"bg-light\">" +
            "<td><div class=\"fw-bold\">" + esc(i.tipo_incidencia || "-") + "</div><span class=\"badge badge-light-dark fs-8\">Incidencia real</span></td>" +
            "<td><span class=\"badge " + claseSeveridadIncidencia(i.severidad) + "\">" + esc(i.severidad || "-") + "</span><div class=\"mt-1\"><span class=\"badge badge-light\">" + esc(i.estatus || "-") + "</span></div></td>" +
            "<td><div>" + esc(refTexto || "-") + "</div><span class=\"text-muted fs-8\">" + esc(i.descripcion || "") + "</span></td>" +
            "<td><div class=\"fw-semibold\">" + esc(i.titulo || "Incidencia de Proveedores") + "</div><div class=\"text-muted fs-8 mb-2\">Cierre controlado con motivo; no modifica Catalogo ni Proveedores automaticamente.</div><div class=\"d-flex flex-wrap gap-2\">" + acciones + "</div></td>" +
            "</tr>";
    }

    function filtrarIncidenciasPropuestas(propuestas) {
        var texto = String(valor("proveedores_erp_incidencias_buscar") || "").toLowerCase().trim();
        var severidad = String(valor("proveedores_erp_incidencias_severidad") || "");
        var tipo = String(valor("proveedores_erp_incidencias_tipo") || "");
        return (propuestas || []).filter(function (p) {
            if (severidad && String(p.severidad || "") !== severidad) {
                return false;
            }
            if (tipo && String(p.tipo_incidencia || "") !== tipo) {
                return false;
            }
            if (!texto) {
                return true;
            }
            var evidencia = p.evidencia || {};
            var renglon = evidencia.renglon || {};
            var base = [
                p.tipo_incidencia, p.titulo, p.descripcion, p.severidad,
                renglon.sku_proveedor, renglon.codigo_barras, renglon.codigo_interno,
                renglon.descripcion_proveedor, renglon.sku_erp, renglon.sku_nombre
            ].filter(Boolean).join(" ").toLowerCase();
            return base.indexOf(texto) >= 0;
        });
    }

    function filtrarIncidenciasReales(incidencias) {
        var texto = String(valor("proveedores_erp_incidencias_buscar") || "").toLowerCase().trim();
        var severidad = String(valor("proveedores_erp_incidencias_severidad") || "");
        var tipo = String(valor("proveedores_erp_incidencias_tipo") || "");
        return (incidencias || []).filter(function (i) {
            if (severidad && String(i.severidad || "") !== severidad) {
                return false;
            }
            if (tipo && String(i.tipo_incidencia || "") !== tipo) {
                return false;
            }
            if (!texto) {
                return true;
            }
            var referencia = i.referencia || {};
            var evidencia = i.evidencia || {};
            var renglon = evidencia.renglon || {};
            var base = [
                i.tipo_incidencia, i.titulo, i.descripcion, i.severidad, i.estatus,
                referencia.sku_proveedor, referencia.codigo_barras, referencia.codigo_interno,
                referencia.descripcion_proveedor, referencia.sku_erp, referencia.sku_nombre,
                renglon.sku_proveedor, renglon.codigo_barras, renglon.codigo_interno,
                renglon.descripcion_proveedor, renglon.sku_erp, renglon.sku_nombre
            ].filter(Boolean).join(" ").toLowerCase();
            return base.indexOf(texto) >= 0;
        });
    }

    function resolverPendienteProveedor(p) {
        var tipo = String(p.tipo_incidencia || "");
        var mapa = {
            proveedor_sku_sin_match: {
                titulo: "Solicitar revision o alta temporal",
                detalle: "Este producto puede quedarse como evidencia. Si lo quieres evaluar para compra, mandalo a Catalogo para revisar si existe o crear un SKU temporal; despues haces matching.",
                dueno: "Proveedores + Catalogo",
                acciones: ["matching", "editar", "catalogo"]
            },
            proveedor_match_ambiguo: {
                titulo: "Elegir el SKU correcto",
                detalle: "Revisa los candidatos y selecciona uno solo. Si ninguno corresponde, marca sin match o manda a Catalogo.",
                dueno: "Proveedores",
                acciones: ["matching", "editar", "catalogo"]
            },
            proveedor_unidad_factor_dudoso: {
                titulo: "Completar unidad y factor",
                detalle: "Define como compra el proveedor y a cuantas unidades ERP equivale antes de aplicar relacion o costo.",
                dueno: "Proveedores",
                acciones: ["editar"]
            },
            proveedor_costo_dudoso: {
                titulo: "Corregir costo, moneda e impuestos",
                detalle: "Captura costo positivo, moneda y si incluye impuestos. Si el proveedor envio mal la lista, deja evidencia en observaciones.",
                dueno: "Proveedores",
                acciones: ["editar"]
            },
            proveedor_sku_sin_codigo_confiable: {
                titulo: "Completar codigo principal en Catalogo",
                detalle: "El SKU existe, pero Catalogo debe tener un codigo confiable para busqueda, conciliacion y compras.",
                dueno: "Catalogo",
                acciones: ["catalogo"]
            },
            proveedor_sku_fiscal_incompleto: {
                titulo: "Completar datos fiscales del SKU",
                detalle: "Catalogo debe completar clave SAT, unidad SAT, objeto de impuesto e impuestos antes de uso fiscal serio.",
                dueno: "Catalogo",
                acciones: ["catalogo"]
            }
        };
        return mapa[tipo] || {
            titulo: "Revisar pendiente",
            detalle: "Revisa el renglon y decide si se corrige en Proveedores o se envia a Catalogo.",
            dueno: "Revision",
            acciones: ["editar", "catalogo"]
        };
    }

    function accionesPendienteProveedor(p, resolucion) {
        var acciones = resolucion.acciones || [];
        var idRenglon = p.id_referencia || "";
        var botones = [];
        if (acciones.indexOf("matching") >= 0 && permisos.matching) {
            botones.push("<button class=\"btn btn-sm btn-light-info\" type=\"button\" data-pendiente-matching=\"" + esc(idRenglon) + "\">Abrir matching</button>");
        }
        if (acciones.indexOf("editar") >= 0 && permisos.listas) {
            botones.push("<button class=\"btn btn-sm btn-light-primary\" type=\"button\" data-pendiente-editar=\"" + esc(idRenglon) + "\">Editar renglon</button>");
        }
        return botones.join("");
    }

    function claseSeveridadIncidencia(severidad) {
        var clases = {
            bloqueante: "badge-light-danger",
            alta: "badge-light-warning",
            media: "badge-light-info",
            baja: "badge-light-secondary"
        };
        return clases[severidad] || "badge-light";
    }

    function guardarDecisionMatching(boton) {
        if (!proveedorActual || !listaDetalleActual) {
            return;
        }
        var estado = boton.getAttribute("data-matching-decision") || "";
        var data = {
            id_proveedor: proveedorActual.id_proveedor,
            id_lista_proveedor_erp: listaDetalleActual.id_lista_proveedor_erp,
            id_lista_detalle_erp: boton.getAttribute("data-renglon") || "",
            id_sku: boton.getAttribute("data-sku") || "",
            id_sku_proveedor: boton.getAttribute("data-sku-proveedor") || "",
            estado_match: estado,
            criterio_match: boton.getAttribute("data-criterio") || "",
            observaciones: "Decision capturada desde matching dry-run"
        };
        if (["ambiguo", "sin_match", "rechazado"].indexOf(estado) >= 0) {
            solicitarMotivoMatching(estado).then(function (motivo) {
                if (!motivo) {
                    return;
                }
                data.observaciones = motivo;
                enviarDecisionMatching(boton, data);
            });
            return;
        }
        enviarDecisionMatching(boton, data);
    }

    function enviarDecisionMatching(boton, data) {
        boton.disabled = true;
        post("/proveedor/proveedor_lista_matching_decidir_erp", data).then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje || "No fue posible guardar la decision.");
            }
            Swal.fire({text: response.mensaje, icon: "success", confirmButtonText: "Aceptar"});
            ejecutarMatchingDryRun();
            if (listaDetalleActual) {
                abrirDetalleLista(listaDetalleActual);
            }
        }).catch(function (err) {
            Swal.fire({text: err.message, icon: "error", confirmButtonText: "Aceptar"});
        }).finally(function () {
            boton.disabled = false;
        });
    }

    function solicitarMotivoMatching(estado) {
        var opciones = motivosMatchingPorEstado(estado);
        return Swal.fire({
            title: "Motivo de matching",
            text: "Selecciona por que se marca como " + estado.replace(/_/g, " ") + ".",
            input: "select",
            inputOptions: opciones,
            inputPlaceholder: "Seleccionar motivo",
            showCancelButton: true,
            confirmButtonText: "Guardar decision",
            cancelButtonText: "Cancelar",
            preConfirm: function (valor) {
                if (!valor || !opciones[valor]) {
                    Swal.showValidationMessage("Selecciona un motivo.");
                    return false;
                }
                return "Motivo matching: " + opciones[valor];
            }
        }).then(function (result) {
            return result.isConfirmed ? result.value : "";
        });
    }

    function motivosMatchingPorEstado(estado) {
        var motivos = {
            ambiguo: {
                multiples_candidatos: "Hay varios candidatos posibles y ninguno debe aplicarse sin revision.",
                datos_insuficientes: "La descripcion/codigo no alcanza para elegir con seguridad.",
                requiere_catalogo: "Debe revisarlo Catalogo antes de relacionar."
            },
            sin_match: {
                no_existe_catalogo: "No se encontro SKU ERP confiable en Catalogo.",
                codigo_no_coincide: "El codigo/SKU proveedor no coincide con un SKU ERP.",
                producto_nuevo: "Parece producto nuevo y debe enviarse a Catalogo si se va a comprar."
            },
            rechazado: {
                candidato_incorrecto: "El candidato sugerido no corresponde al producto proveedor.",
                producto_no_operativo: "El renglon queda solo como evidencia y no se va a operar.",
                informacion_erronea: "La informacion del proveedor es insuficiente o incorrecta."
            }
        };
        return motivos[estado] || {
            revision_manual: "Requiere revision manual."
        };
    }

    function aplicarRelacionSkuProveedor(idRenglon) {
        if (!proveedorActual || !listaDetalleActual || !idRenglon) {
            return;
        }
        Swal.fire({
            text: "Se aplicara la relacion SKU-proveedor sin aplicar costos.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Aplicar",
            cancelButtonText: "Cancelar"
        }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }
            post("/proveedor/proveedor_sku_relacion_aplicar_erp", {
                id_proveedor: proveedorActual.id_proveedor,
                id_lista_proveedor_erp: listaDetalleActual.id_lista_proveedor_erp,
                id_lista_detalle_erp: idRenglon
            }).then(function (response) {
                if (response.error) {
                    throw new Error(response.mensaje || "No fue posible aplicar la relacion.");
                }
                Swal.fire({text: response.mensaje, icon: "success", confirmButtonText: "Aceptar"});
                abrirDetalleLista(listaDetalleActual);
            }).catch(function (err) {
                Swal.fire({text: err.message, icon: "error", confirmButtonText: "Aceptar"});
            });
        });
    }

    function crearIncidenciaProveedor(idRenglon, tipoIncidencia, confirmar) {
        if (!proveedorActual || !listaDetalleActual || !idRenglon || !tipoIncidencia) {
            return;
        }
        var ejecutar = function () {
            post("/proveedor/proveedor_incidencia_crear_erp", {
                id_proveedor: proveedorActual.id_proveedor,
                id_lista_proveedor_erp: listaDetalleActual.id_lista_proveedor_erp,
                id_lista_detalle_erp: idRenglon,
                tipo_incidencia: tipoIncidencia
            }).then(function (response) {
                if (response.error) {
                    throw new Error(response.mensaje || "No fue posible crear la incidencia.");
                }
                Swal.fire({text: response.mensaje, icon: "success", confirmButtonText: "Aceptar"});
                if (incidenciasPropuestasActuales.length || incidenciasRealesActuales.length) {
                    ejecutarIncidenciasDryRun();
                }
                if (listaDetalleActual) {
                    abrirDetalleLista(listaDetalleActual);
                }
            }).catch(function (err) {
                Swal.fire({text: err.message, icon: "error", confirmButtonText: "Aceptar"});
            });
        };
        if (confirmar === false) {
            ejecutar();
            return;
        }
        Swal.fire({
            text: "Se enviara este pendiente a Catalogo con origen Proveedores. Usalo cuando no se pueda resolver solo editando o vinculando desde Proveedores.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Enviar a Catalogo",
            cancelButtonText: "Cancelar"
        }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }
            ejecutar();
        });
    }

    function enviarRenglonProveedorACatalogo(idRenglon) {
        if (!proveedorActual || !listaDetalleActual || !idRenglon || !permisos.autorizar) {
            return;
        }
        var renglon = listaDetalleRenglones.find(function (item) {
            return String(item.id_lista_detalle_erp) === String(idRenglon);
        }) || {};
        var etiqueta = [renglon.sku_proveedor, renglon.codigo_barras, renglon.codigo_interno, renglon.descripcion_proveedor].filter(Boolean).join(" | ");
        Swal.fire({
            title: "Enviar producto a Catalogo",
            text: "Se creara una incidencia para que Catalogo revise o cree un SKU ERP usando este renglon: " + (etiqueta || ("ID " + idRenglon)),
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Enviar a Catalogo",
            cancelButtonText: "Cancelar"
        }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }
            var tipo = String(renglon.estado_match || "") === "ambiguo" ? "proveedor_match_ambiguo" : "proveedor_sku_sin_match";
            crearIncidenciaProveedor(idRenglon, tipo, false);
        });
    }

    function resolverIncidenciaProveedor(idIncidencia, estatus) {
        if (!proveedorActual || !listaDetalleActual || !idIncidencia || !permisos.autorizar) {
            return;
        }
        var titulo = estatus === "resuelta" ? "Marcar incidencia como resuelta" : "Descartar incidencia";
        var texto = estatus === "resuelta"
            ? "Explica que ya se corrigio o que ya no queda pendiente operativo."
            : "Explica por que se descarta para conservar evidencia.";
        Swal.fire({
            title: titulo,
            html:
                "<div class=\"text-start\">" +
                "<div class=\"text-muted mb-3\">" + esc(texto) + "</div>" +
                "<textarea class=\"form-control\" id=\"proveedores_erp_incidencia_motivo\" rows=\"4\" maxlength=\"500\" placeholder=\"Motivo de la decision\"></textarea>" +
                "</div>",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: estatus === "resuelta" ? "Resolver" : "Descartar",
            cancelButtonText: "Cancelar",
            didOpen: function () {
                var motivoInput = document.getElementById("proveedores_erp_incidencia_motivo");
                if (motivoInput) {
                    motivoInput.focus();
                }
            },
            preConfirm: function () {
                var motivoInput = document.getElementById("proveedores_erp_incidencia_motivo");
                var motivo = String(motivoInput ? motivoInput.value : "").trim();
                if (!motivo) {
                    Swal.showValidationMessage("Captura el motivo.");
                    return false;
                }
                return motivo;
            }
        }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }
            post("/proveedor/proveedor_incidencia_resolver_erp", {
                id_proveedor: proveedorActual.id_proveedor,
                id_lista_proveedor_erp: listaDetalleActual.id_lista_proveedor_erp,
                id_incidencia_calidad: idIncidencia,
                estatus: estatus,
                motivo: result.value
            }).then(function (response) {
                if (response.error) {
                    throw new Error(response.mensaje || "No fue posible actualizar la incidencia.");
                }
                Swal.fire({text: response.mensaje, icon: "success", confirmButtonText: "Aceptar"});
                ejecutarIncidenciasDryRun();
                abrirDetalleLista(listaDetalleActual);
            }).catch(function (err) {
                Swal.fire({text: err.message, icon: "error", confirmButtonText: "Aceptar"});
            });
        });
    }

    function abrirRenglonDesdePendiente(idRenglon) {
        if (!idRenglon || !permisos.listas) {
            return;
        }
        var renglon = listaDetalleRenglones.find(function (item) {
            return String(item.id_lista_detalle_erp) === String(idRenglon);
        });
        if (!renglon) {
            Swal.fire({text: "No encontre el renglon en el detalle cargado.", icon: "warning", confirmButtonText: "Aceptar"});
            return;
        }
        abrirFormularioListaDetalle(renglon);
    }

    function abrirMatchingDesdePendiente() {
        if (!permisos.matching) {
            return;
        }
        var modal = bootstrap.Modal.getInstance(document.getElementById("proveedores_erp_incidencias_modal"));
        if (modal) {
            modal.hide();
        }
        ejecutarMatchingDryRun();
    }

    function aplicarCostoProveedorSku(idRenglon) {
        if (!proveedorActual || !listaDetalleActual || !idRenglon) {
            return;
        }
        Swal.fire({
            text: "Se aplicara el costo como vigente y se actualizara costo_ultimo de la relacion. No se tocara costo_referencia.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Aplicar costo",
            cancelButtonText: "Cancelar"
        }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }
            post("/proveedor/proveedor_costo_aplicar_erp", {
                id_proveedor: proveedorActual.id_proveedor,
                id_lista_proveedor_erp: listaDetalleActual.id_lista_proveedor_erp,
                id_lista_detalle_erp: idRenglon
            }).then(function (response) {
                if (response.error) {
                    throw new Error(response.mensaje || "No fue posible aplicar el costo.");
                }
                Swal.fire({text: response.mensaje, icon: "success", confirmButtonText: "Aceptar"});
                abrirDetalleLista(listaDetalleActual);
                cargarCostosProveedor();
            }).catch(function (err) {
                Swal.fire({text: err.message, icon: "error", confirmButtonText: "Aceptar"});
            });
        });
    }

    function setValor(form, name, value) {
        var input = form.querySelector("[name='" + name + "']");
        if (input) {
            input.value = value == null ? "" : value;
        }
    }

    function setChecked(form, name, value) {
        var input = form.querySelector("[name='" + name + "']");
        if (input) {
            input.checked = String(value) === "1";
        }
    }

    document.addEventListener("DOMContentLoaded", function () {
        ["proveedores_erp_buscar", "proveedores_erp_estatus", "proveedores_erp_tipo"].forEach(function (id) {
            document.getElementById(id).addEventListener("input", function () { cargarConDebounce(true); });
        });
        document.getElementById("proveedores_erp_limite").addEventListener("change", function () {
            pagina = 1;
            cargar();
        });
        document.getElementById("proveedores_erp_anterior").addEventListener("click", function () {
            if (pagina > 1) {
                pagina--;
                cargar();
            }
        });
        document.getElementById("proveedores_erp_siguiente").addEventListener("click", function () {
            if (pagina < Math.ceil(total / limite)) {
                pagina++;
                cargar();
            }
        });
        document.getElementById("proveedores_erp_body").addEventListener("click", function (event) {
            var boton = event.target.closest("[data-proveedor]");
            if (boton) {
                abrirProveedor(boton.getAttribute("data-proveedor"));
            }
        });
        var nuevo = document.getElementById("proveedores_erp_nuevo");
        if (nuevo && permisos.crear) {
            nuevo.addEventListener("click", function () {
                abrirFormularioGeneral({});
            });
        }
        var editar = document.getElementById("proveedores_erp_editar_general");
        if (editar && permisos.editar) {
            editar.addEventListener("click", function () {
                abrirFormularioGeneral(proveedorActual || {});
            });
        }
        var cambiarEstatus = document.getElementById("proveedores_erp_cambiar_estatus");
        if (cambiarEstatus && permisos.autorizar) {
            cambiarEstatus.addEventListener("click", cambiarEstatusProveedor);
        }
        var refrescarCostos = document.getElementById("proveedores_erp_refrescar_costos");
        if (refrescarCostos && permisos.costos) {
            refrescarCostos.addEventListener("click", cargarCostosProveedor);
        }
        var buscarCostos = document.getElementById("proveedores_erp_costos_buscar");
        if (buscarCostos && permisos.costos) {
            buscarCostos.addEventListener("input", function () {
                renderCostosHistorial(costosActuales);
            });
        }
        var form = document.getElementById("proveedores_erp_general_form");
        if (form) {
            form.addEventListener("submit", guardarGeneral);
        }
        var compararCompras = document.getElementById("proveedores_erp_compras_comparar");
        if (compararCompras && permisos.auditoria) {
            compararCompras.addEventListener("click", ejecutarComparativoCompras);
        }
        var terminoCompras = document.getElementById("proveedores_erp_compras_termino");
        if (terminoCompras && permisos.auditoria) {
            terminoCompras.addEventListener("keydown", function (event) {
                if (event.key === "Enter") {
                    event.preventDefault();
                    ejecutarComparativoCompras();
                }
            });
        }
        var agregarFiscal = document.getElementById("proveedores_erp_agregar_fiscal");
        if (agregarFiscal && permisos.fiscales) {
            agregarFiscal.addEventListener("click", function () {
                abrirFormularioFiscal({});
            });
        }
        document.getElementById("proveedores_erp_fiscales").addEventListener("click", function (event) {
            var boton = event.target.closest("[data-fiscal]");
            if (!boton || !permisos.fiscales) {
                return;
            }
            var idFiscal = boton.getAttribute("data-fiscal");
            var fiscal = fiscalesActuales.find(function (item) {
                return String(item.id_proveedor_fiscal) === String(idFiscal);
            });
            abrirFormularioFiscal(fiscal || {});
        });
        var formFiscal = document.getElementById("proveedores_erp_fiscal_form");
        if (formFiscal) {
            formFiscal.addEventListener("submit", guardarFiscal);
        }
        var agregarContacto = document.getElementById("proveedores_erp_agregar_contacto");
        if (agregarContacto && permisos.contactos) {
            agregarContacto.addEventListener("click", function () {
                abrirFormularioContacto({});
            });
        }
        document.getElementById("proveedores_erp_contactos").addEventListener("click", function (event) {
            var boton = event.target.closest("[data-contacto]");
            if (!boton || !permisos.contactos) {
                return;
            }
            var idContacto = boton.getAttribute("data-contacto");
            var contacto = contactosActuales.find(function (item) {
                return String(item.id_contacto_proveedor) === String(idContacto);
            });
            abrirFormularioContacto(contacto || {});
        });
        var formContacto = document.getElementById("proveedores_erp_contacto_form");
        if (formContacto) {
            formContacto.addEventListener("submit", guardarContacto);
        }
        var agregarCondicion = document.getElementById("proveedores_erp_agregar_condicion");
        if (agregarCondicion && permisos.condiciones) {
            agregarCondicion.addEventListener("click", function () {
                abrirFormularioCondicion({});
            });
        }
        document.getElementById("proveedores_erp_condiciones").addEventListener("click", function (event) {
            var boton = event.target.closest("[data-condicion]");
            if (!boton || !permisos.condiciones) {
                return;
            }
            var idCondicion = boton.getAttribute("data-condicion");
            var condicion = condicionesActuales.find(function (item) {
                return String(item.id_condicion_proveedor) === String(idCondicion);
            });
            abrirFormularioCondicion(condicion || {});
        });
        var formCondicion = document.getElementById("proveedores_erp_condicion_form");
        if (formCondicion) {
            formCondicion.addEventListener("submit", guardarCondicion);
        }
        var agregarDocumento = document.getElementById("proveedores_erp_agregar_documento");
        if (agregarDocumento && permisos.documentos) {
            agregarDocumento.addEventListener("click", function () {
                abrirFormularioDocumento({});
            });
        }
        document.getElementById("proveedores_erp_documentos").addEventListener("click", function (event) {
            var boton = event.target.closest("[data-documento]");
            if (!boton || !permisos.documentos) {
                return;
            }
            var idDocumento = boton.getAttribute("data-documento");
            var documento = documentosActuales.find(function (item) {
                return String(item.id_documento_proveedor) === String(idDocumento);
            });
            if (documento && esDocumentoSensible(documento) && !permisos.documentos_sensibles) {
                return;
            }
            abrirFormularioDocumento(documento || {});
        });
        var formDocumento = document.getElementById("proveedores_erp_documento_form");
        if (formDocumento) {
            formDocumento.addEventListener("submit", guardarDocumento);
        }
        var agregarLista = document.getElementById("proveedores_erp_agregar_lista");
        if (agregarLista && permisos.listas) {
            agregarLista.addEventListener("click", function () {
                abrirFormularioLista({});
            });
        }
        document.getElementById("proveedores_erp_listas").addEventListener("click", function (event) {
            var estatusLista = event.target.closest("[data-lista-estatus]");
            if (estatusLista && permisos.autorizar) {
                cambiarEstatusLista(estatusLista.getAttribute("data-lista-id"), estatusLista.getAttribute("data-lista-estatus"));
                return;
            }
            var preview = event.target.closest("[data-lista-preview]");
            if (preview && permisos.listas) {
                var idPreview = preview.getAttribute("data-lista-preview");
                var listaPreview = listasActuales.find(function (item) {
                    return String(item.id_lista_proveedor_erp) === String(idPreview);
                });
                abrirPreviewLista(listaPreview || {});
                return;
            }
            var detalle = event.target.closest("[data-lista-detalle]");
            if (detalle && permisos.listas) {
                var idDetalleLista = detalle.getAttribute("data-lista-detalle");
                var listaDetalle = listasActuales.find(function (item) {
                    return String(item.id_lista_proveedor_erp) === String(idDetalleLista);
                });
                abrirDetalleLista(listaDetalle || {});
                return;
            }
            var boton = event.target.closest("[data-lista]");
            if (!boton || !permisos.listas) {
                return;
            }
            var idLista = boton.getAttribute("data-lista");
            var lista = listasActuales.find(function (item) {
                return String(item.id_lista_proveedor_erp) === String(idLista);
            });
            abrirFormularioLista(lista || {});
        });
        var formLista = document.getElementById("proveedores_erp_lista_form");
        if (formLista) {
            formLista.addEventListener("submit", guardarLista);
        }
        var agregarListaDetalle = document.getElementById("proveedores_erp_agregar_lista_detalle");
        if (agregarListaDetalle && permisos.listas) {
            agregarListaDetalle.addEventListener("click", function () {
                abrirFormularioListaDetalle({});
            });
        }
        var matchingDryRun = document.getElementById("proveedores_erp_lista_matching_dry_run");
        if (matchingDryRun && permisos.matching) {
            matchingDryRun.addEventListener("click", ejecutarMatchingDryRun);
        }
        var matchingMasivo = document.getElementById("proveedores_erp_matching_masivo");
        if (matchingMasivo && permisos.matching) {
            matchingMasivo.addEventListener("click", seleccionarMatchingMasivo);
        }
        var relacionesLotePreview = document.getElementById("proveedores_erp_relaciones_lote_preview");
        if (relacionesLotePreview && permisos.matching) {
            relacionesLotePreview.addEventListener("click", ejecutarPreviewRelacionesLote);
        }
        var relacionesLoteAplicar = document.getElementById("proveedores_erp_relaciones_lote_aplicar");
        if (relacionesLoteAplicar && permisos.matching) {
            relacionesLoteAplicar.addEventListener("click", aplicarRelacionesLote);
        }
        var costosLotePreview = document.getElementById("proveedores_erp_costos_lote_preview");
        if (costosLotePreview && permisos.costos) {
            costosLotePreview.addEventListener("click", ejecutarPreviewCostosLote);
        }
        var costoReferenciaPreview = document.getElementById("proveedores_erp_costo_referencia_preview");
        if (costoReferenciaPreview && permisos.costos) {
            costoReferenciaPreview.addEventListener("click", ejecutarPreviewCostoReferencia);
        }
        var costoReferenciaAplicar = document.getElementById("proveedores_erp_costo_referencia_aplicar");
        if (costoReferenciaAplicar && permisos.costos) {
            costoReferenciaAplicar.addEventListener("click", aplicarCostoReferencia);
        }
        var costosLoteAplicar = document.getElementById("proveedores_erp_costos_lote_aplicar");
        if (costosLoteAplicar && permisos.costos) {
            costosLoteAplicar.addEventListener("click", aplicarCostosLote);
        }
        var incidenciasDryRun = document.getElementById("proveedores_erp_lista_incidencias_dry_run");
        if (incidenciasDryRun && permisos.auditoria) {
            incidenciasDryRun.addEventListener("click", ejecutarIncidenciasDryRun);
        }
        ["proveedores_erp_matching_buscar", "proveedores_erp_matching_estado"].forEach(function (id) {
            var elemento = document.getElementById(id);
            if (elemento) {
                elemento.addEventListener("input", function () {
                    renderMatching({propuestas: matchingPropuestasActuales, resumen: matchingResumenActual});
                });
                elemento.addEventListener("change", function () {
                    renderMatching({propuestas: matchingPropuestasActuales, resumen: matchingResumenActual});
                });
            }
        });
        ["proveedores_erp_incidencias_buscar", "proveedores_erp_incidencias_severidad", "proveedores_erp_incidencias_tipo"].forEach(function (id) {
            var elemento = document.getElementById(id);
            if (elemento) {
                elemento.addEventListener("input", function () {
                    renderIncidenciasDryRun({propuestas: incidenciasPropuestasActuales, resumen: incidenciasResumenActual});
                });
                elemento.addEventListener("change", function () {
                    renderIncidenciasDryRun({propuestas: incidenciasPropuestasActuales, resumen: incidenciasResumenActual});
                });
            }
        });
        document.getElementById("proveedores_erp_incidencias_body").addEventListener("click", function (event) {
            var editar = event.target.closest("[data-pendiente-editar]");
            if (editar) {
                abrirRenglonDesdePendiente(editar.getAttribute("data-pendiente-editar"));
                return;
            }
            var matching = event.target.closest("[data-pendiente-matching]");
            if (matching) {
                abrirMatchingDesdePendiente();
                return;
            }
            var resolver = event.target.closest("[data-incidencia-resolver]");
            if (resolver && permisos.autorizar) {
                resolverIncidenciaProveedor(resolver.getAttribute("data-incidencia-resolver"), resolver.getAttribute("data-estatus-incidencia"));
                return;
            }
            var boton = event.target.closest("[data-incidencia-crear]");
            if (!boton || !permisos.autorizar) {
                return;
            }
            crearIncidenciaProveedor(boton.getAttribute("data-incidencia-crear"), boton.getAttribute("data-tipo-incidencia"));
        });
        document.getElementById("proveedores_erp_matching_body").addEventListener("click", function (event) {
            var boton = event.target.closest("[data-matching-decision]");
            if (!boton || !permisos.matching) {
                return;
            }
            guardarDecisionMatching(boton);
        });
        document.getElementById("proveedores_erp_lista_detalle_body").addEventListener("click", function (event) {
            var enviarCatalogo = event.target.closest("[data-enviar-catalogo-renglon]");
            if (enviarCatalogo && permisos.autorizar) {
                enviarRenglonProveedorACatalogo(enviarCatalogo.getAttribute("data-enviar-catalogo-renglon"));
                return;
            }
            var eliminar = event.target.closest("[data-eliminar-renglon]");
            if (eliminar && permisos.listas) {
                eliminarListaDetalle(eliminar.getAttribute("data-eliminar-renglon"));
                return;
            }
            var aplicar = event.target.closest("[data-aplicar-relacion]");
            if (aplicar && permisos.matching) {
                aplicarRelacionSkuProveedor(aplicar.getAttribute("data-aplicar-relacion"));
                return;
            }
            var aplicarCosto = event.target.closest("[data-aplicar-costo]");
            if (aplicarCosto && permisos.costos) {
                aplicarCostoProveedorSku(aplicarCosto.getAttribute("data-aplicar-costo"));
                return;
            }
            var boton = event.target.closest("[data-lista-renglon]");
            if (!boton || !permisos.listas) {
                return;
            }
            var idRenglon = boton.getAttribute("data-lista-renglon");
            var renglon = listaDetalleRenglones.find(function (item) {
                return String(item.id_lista_detalle_erp) === String(idRenglon);
            });
            abrirFormularioListaDetalle(renglon || {});
        });
        document.querySelectorAll("[data-lista-detalle-filtro]").forEach(function (boton) {
            boton.addEventListener("click", function () {
                listaDetalleFiltro = boton.getAttribute("data-lista-detalle-filtro") || "todos";
                actualizarBotonesFiltroDetalle();
                renderListaDetalle();
            });
        });
        var buscarDetalle = document.getElementById("proveedores_erp_lista_detalle_buscar");
        if (buscarDetalle) {
            buscarDetalle.addEventListener("input", function () {
                listaDetalleBusqueda = buscarDetalle.value || "";
                renderListaDetalle();
            });
        }
        var limpiarBusquedaDetalle = document.getElementById("proveedores_erp_lista_detalle_limpiar_busqueda");
        if (limpiarBusquedaDetalle) {
            limpiarBusquedaDetalle.addEventListener("click", function () {
                listaDetalleBusqueda = "";
                setInputValor("proveedores_erp_lista_detalle_buscar", "");
                renderListaDetalle();
            });
        }
        var importarPreview = document.getElementById("proveedores_erp_lista_preview_importar");
        if (importarPreview) {
            importarPreview.addEventListener("click", importarPreviewLista);
        }
        var abrirCompraLoteBtn = document.getElementById("proveedores_erp_compra_lote_abrir");
        if (abrirCompraLoteBtn) {
            abrirCompraLoteBtn.addEventListener("click", abrirCompraLote);
        }
        var formCompraLote = document.getElementById("proveedores_erp_compra_lote_form");
        if (formCompraLote) {
            formCompraLote.addEventListener("submit", guardarCompraLote);
        }
        var alcanceCompraLote = document.getElementById("proveedores_erp_compra_lote_alcance");
        if (alcanceCompraLote) {
            alcanceCompraLote.addEventListener("change", actualizarConteoCompraLote);
        }
        var limitePreview = document.getElementById("proveedores_erp_lista_preview_limite");
        if (limitePreview) {
            limitePreview.addEventListener("change", cargarPreviewListaActual);
        }
        var formListaDetalle = document.getElementById("proveedores_erp_lista_detalle_form");
        if (formListaDetalle) {
            formListaDetalle.addEventListener("submit", guardarListaDetalle);
        }
        var buscarSkuBtn = document.getElementById("proveedores_erp_lista_detalle_sku_btn");
        if (buscarSkuBtn) {
            buscarSkuBtn.addEventListener("click", buscarSkuErpListaDetalle);
        }
        var buscarSkuInput = document.getElementById("proveedores_erp_lista_detalle_sku_buscar");
        if (buscarSkuInput) {
            buscarSkuInput.addEventListener("keydown", function (event) {
                if (event.key === "Enter") {
                    event.preventDefault();
                    buscarSkuErpListaDetalle();
                }
            });
        }
        var resultadosSku = document.getElementById("proveedores_erp_lista_detalle_sku_resultados");
        if (resultadosSku) {
            resultadosSku.addEventListener("click", function (event) {
                var boton = event.target.closest("[data-sku-manual]");
                if (boton) {
                    seleccionarSkuManualListaDetalle(boton);
                }
            });
        }
        cargarCatalogosListaDetalle();
        cargar();
    });
})();
