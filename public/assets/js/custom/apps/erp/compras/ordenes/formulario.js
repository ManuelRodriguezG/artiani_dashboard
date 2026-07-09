"use strict";
(function () {
    // [Codex v2026.06.07] Formulario ordenes compra: mejoras de control de items y costes.
var items = [];
var modo = "editar";
var puedeEditar = true;
    var puedeAprobar = false;
    var puedeCancelar = false;
    var puedeVerFinanzas = false;
var puedeOperarFinanzas = false;
var puedeGestionarAdjuntos = false;
var estatusOrden = "borrador";
var timerBusqueda;
var timerDescuentoMasivo;
var itemUidCounter = 1;
var promesaBorradorEnCurso = null;
var eventosDescuentoMasivo = [];
var modoCostoCaptura = "sin_impuestos";

function esc(value) { var d = document.createElement("div"); d.textContent = value == null ? "" : value; return d.innerHTML; }
function parseDecimal(value) { return Number(String(value == null ? "" : value).replace(/,/g, "")) || 0; }
function parseFlag(value) { return value === true || value === "1" || value === 1 || value === "true" || value === "TRUE"; }
function parseFlagSeguro(value) { return parseFlag(value); }
function estaVacio(value) { return value === undefined || value === null || String(value).trim() === ""; }
function parseJsonSeguro(value) { if (!value) { return {}; } try { var json = typeof value === "string" ? JSON.parse(value) : value; return (json && typeof json === "object") ? json : {}; } catch (e) { return {}; } }
function normalizarTexto(value) { return String(value == null ? "" : value).trim(); }
function normalizarDatoFiscalRaw(value) { return value == null ? "" : String(value).trim(); }
/**
 * IA: Codex GPT-5 | Fecha: 2026-06-25
 * Proposito: normaliza la URL de imagen del SKU para que Compras pinte miniaturas desde Catalogo sin duplicar archivos.
 * Impacto: UI de ordenes de compra; contrato visual de busqueda y detalle de partidas.
 */
function normalizarImagenSku(url) {
    var valor = normalizarTexto(url);
    if (!valor) { return ""; }
    if (/^(https?:)?\/\//i.test(valor) || valor.indexOf("data:") === 0) { return valor; }
    return "/" + valor.replace(/^\/+/, "");
}
/**
 * IA: Codex GPT-5 | Fecha: 2026-06-25
 * Proposito: genera la miniatura del SKU o un marcador neutro cuando Catalogo no tiene imagen activa.
 * Impacto: busqueda/agregado de productos en ordenes de compra.
 */
function imagenSkuHtml(item, extraClass) {
    var url = normalizarImagenSku(item && item.url_imagen);
    var clase = "rounded border bg-light d-flex align-items-center justify-content-center flex-shrink-0 " + (extraClass || "");
    if (!url) {
        return "<span class=\"" + clase + "\" style=\"width:48px;height:48px;\"><i class=\"bi bi-image text-muted fs-3\"></i></span>";
    }
    return "<img src=\"" + esc(url) + "\" alt=\"" + esc(item && item.nombre ? item.nombre : "SKU") +
        "\" class=\"" + clase + "\" style=\"width:48px;height:48px;object-fit:cover;\">";
}
function mergearDatosFiscales(destino, origen) {
    var base = normalizarDatosFiscales(parseJsonSeguro(destino));
    var nuevo = normalizarDatosFiscales(parseJsonSeguro(origen));
    Object.keys(nuevo).forEach(function (k) {
        if (k === "incluye_iva" || k === "requiere_factura") {
            if (nuevo[k] !== undefined) {
                base[k] = parseFlagSeguro(nuevo[k]);
            }
            return;
        }
        if (!estaVacio(nuevo[k])) {
            base[k] = nuevo[k];
        }
    });
    return base;
}
function normalizarDatosFiscales(datos) {
    if (!datos || typeof datos !== "object") { datos = {}; }
    var tipo = normalizarTexto(datos.tipo_impuesto);
    if (estaVacio(tipo)) {
        var iva = parseDecimal(datos.porcentaje_iva || datos.iva_porcentaje);
        var ieps = parseDecimal(datos.porcentaje_ieps || datos.ieps_porcentaje);
        if (ieps > 0 && iva <= 0) { tipo = "IEPS"; }
        else if (iva >= 0) { tipo = "IVA"; }
    }
    return {
        clave_sat: normalizarDatoFiscalRaw(datos.clave_sat || datos.clave_producto_sat || datos.ClaveProdServ || datos.clave_producto),
        clave_unidad_sat: normalizarTexto(datos.clave_unidad_sat || datos.clave_unidad || datos.ClaveUnidad),
        unidad: normalizarTexto(datos.unidad),
        objeto_impuesto: normalizarTexto(datos.objeto_impuesto),
        tipo_impuesto: tipo,
        porcentaje_iva: parseDecimal(datos.porcentaje_iva || datos.iva_porcentaje),
        porcentaje_ieps: parseDecimal(datos.porcentaje_ieps || datos.ieps_porcentaje),
        incluye_iva: parseFlagSeguro(datos.incluye_iva !== undefined ? datos.incluye_iva : (datos.valor_unitario_incluye_impuesto || 1)),
        requiere_factura: parseFlagSeguro(datos.requiere_factura !== undefined ? datos.requiere_factura : 1)
    };
}
function fiscalCompleto(datos) {
    var x = normalizarDatosFiscales(datos);
    return !estaVacio(x.clave_sat) && !estaVacio(x.clave_unidad_sat) &&
        !estaVacio(x.unidad) && !estaVacio(x.objeto_impuesto) && !estaVacio(x.tipo_impuesto);
}
function uidItem(item) {
    if (!item) { return ""; }
    if (!item._uid) {
        item._uid = "it_" + (itemUidCounter++);
    }
    return item._uid;
}
function costoUnitarioNeto(item) {
        var precio = parseDecimal(item.costo_unitario);
        var iva = parseDecimal(item.porcentaje_impuesto);
        return parseFlag(item.costo_unitario_incluye_impuesto) ? precio / (1 + iva / 100) : precio;
    }
function costoUnitarioConImpuestos(item) {
        var neto = costoUnitarioNeto(item);
        var iva = parseDecimal(item.porcentaje_impuesto);
        return neto * (1 + iva / 100);
    }
function esTipoItemNoInventariable(tipoItem) {
    return ["servicio", "cargo", "no_inventariable", "adicional"].indexOf(String(tipoItem || "").toLowerCase()) >= 0;
}
/**
 * Ordenes compra ERP
 * Documentacion IA: Codex GPT-5
 * Version: 2026.06.15
 * Descripcion: pinta el tipo de partida para distinguir productos fisicos de cargos no inventariables.
 */
function tipoItemControlHtml(item, index, disabled) {
    var tipo = String(item && item.tipo_item || "").toLowerCase();
    var idSku = parseInt(item && item.id_sku_erp || 0, 10);
    if (!tipo) {
        tipo = idSku > 0 ? "producto" : "producto_nuevo";
    }
    if (idSku > 0) {
        return "<span class=\"badge badge-light-primary\">Producto</span>";
    }
    var opciones = [
        ["producto_nuevo", "Producto nuevo"],
        ["servicio", "Servicio"],
        ["cargo", "Cargo"],
        ["adicional", "Adicional"],
        ["no_inventariable", "No inventariable"]
    ];
    return "<select class=\"form-select form-select-sm\" data-item=\"tipo_item\" data-index=\"" + index + "\"" + disabled + ">" +
        opciones.map(function (opcion) {
            return "<option value=\"" + opcion[0] + "\"" + (tipo === opcion[0] ? " selected" : "") + ">" + opcion[1] + "</option>";
        }).join("") + "</select>";
}
function camposFiscalModal() {
    return [
        "orden_fiscal_clave_sat",
        "orden_fiscal_clave_unidad_sat",
        "orden_fiscal_unidad",
        "orden_fiscal_objeto",
        "orden_fiscal_tipo",
        "orden_fiscal_iva",
        "orden_fiscal_ieps",
        "orden_fiscal_incluye_iva",
        "orden_fiscal_requiere_factura"
    ];
}
function etiquetaRegistroProducto(item) {
    if (esTipoItemNoInventariable(item && item.tipo_item)) {
        return "<span class=\"badge badge-light-info\">No inventariable</span>";
    }
    if (parseInt(item && item.id_sku_erp || 0, 10) > 0 || parseInt(item && item.producto_registrado || 0, 10) === 1) {
        return "<span class=\"badge badge-light-success\">Producto registrado</span>";
    }
    return "<span class=\"badge badge-light-warning\">Pendiente de alta</span>";
}
function advertenciasOperativasHtml(item) {
    var advertencias = Array.isArray(item && item.advertencias_operativas) ? item.advertencias_operativas : [];
    if (!advertencias.length) { return ""; }
    return "<div class=\"mt-1\">" + advertencias.map(function (x) {
        var clase = x.nivel === "info" ? "badge-light-info" : "badge-light-warning";
        return "<span class=\"badge " + clase + " me-1 mb-1\">" + esc(x.mensaje || x.codigo || "Revision") + "</span>";
    }).join("") + "</div>";
}
function evidenciaCostoHtml(item) {
    if (!item) { return ""; }
    var fuente = String(item.fuente_costo || "");
    var moneda = item.moneda_costo || "MXN";
    var origen = item.origen_costo || "";
    var vigencia = [item.vigencia_desde, item.vigencia_hasta].filter(Boolean).join(" a ");
    var texto = fuente === "historial_vigente" ? "Costo vigente" : "Costo ultimo";
    var clase = fuente === "historial_vigente" ? "badge-light-success" : "badge-light-info";
    var detalle = [moneda, origen, vigencia].filter(Boolean).join(" | ");
    return "<div class=\"mt-1\"><span class=\"badge " + clase + " me-1\">" + esc(texto) + "</span>" +
        (detalle ? "<span class=\"text-muted fs-8\">" + esc(detalle) + "</span>" : "") + "</div>";
}
function estadoFiscalTexto(item) {
    return fiscalCompleto(item && item.datos_fiscales || {}) ? "fiscal_ok" : "fiscal_pendiente";
}
function renderResumenFiscales() {
    var body = document.getElementById("orden_fiscales_pendientes");
    if (!body) { return; }
    var faltantes = items.filter(function (x) {
        return !fiscalCompleto(x && x.datos_fiscales || {});
    });
    if (!faltantes.length) {
        body.innerHTML = "<tr><td colspan=\"6\" class=\"text-center text-muted py-6\">Sin productos pendientes de datos fiscales</td></tr>";
        return;
    }
    body.innerHTML = faltantes.map(function (x) {
        var registro = parseInt(x && x.id_sku_erp || 0, 10) > 0 || parseInt(x && x.producto_registrado || 0, 10) === 1;
        return "<tr>" +
            "<td class=\"fw-bold\">" + esc(x.sku || x.sku_proveedor || "") + "</td>" +
            "<td>" + esc(x.nombre || "") + "</td>" +
            "<td>" + esc(x.unidad || "") + "</td>" +
            "<td><span class=\"badge badge-light-danger\">Pendiente fiscal</span>" +
            " <span class=\"badge " + (registro ? "badge-light-success" : "badge-light-warning") + "\">" +
            (registro ? "Registrado" : "Nuevo") + "</span></td>" +
            "<td class=\"text-end\"><button type=\"button\" class=\"btn btn-sm btn-light-primary\" data-fiscal=\"" +
            esc(x._uid || "") + "\">" + (puedeEditar ? "Completar" : "Ver") + "</button></td>" +
            "</tr>";
    }).join("");
}
function renderResumenCatalogoFiscales() {
    var total = items.length;
    var inventariables = items.filter(function (x) { return !esTipoItemNoInventariable(x && x.tipo_item); });
    var registrados = inventariables.filter(function (x) {
        return parseInt(x && x.id_sku_erp || 0, 10) > 0 || parseInt(x && x.producto_registrado || 0, 10) === 1;
    }).length;
    var nuevos = inventariables.length - registrados;
    var fiscalesCompletos = items.filter(function (x) { return fiscalCompleto(x && x.datos_fiscales || {}); }).length;
    var fiscalesPendientes = total - fiscalesCompletos;
    var totalBox = document.getElementById("orden_resumen_productos_total");
    var registradosBox = document.getElementById("orden_resumen_productos_registrados");
    var nuevosBox = document.getElementById("orden_resumen_productos_nuevos");
    var fiscalesCompletosBox = document.getElementById("orden_resumen_fiscales_completos");
    var fiscalesPendientesBox = document.getElementById("orden_resumen_fiscales_pendientes");
    if (totalBox) { totalBox.textContent = String(total); }
    if (registradosBox) { registradosBox.textContent = String(registrados); }
    if (nuevosBox) { nuevosBox.textContent = String(nuevos); }
    if (fiscalesCompletosBox) { fiscalesCompletosBox.textContent = String(fiscalesCompletos); }
    if (fiscalesPendientesBox) { fiscalesPendientesBox.textContent = String(fiscalesPendientes); }
}
    function claveItem(item) {
        if (Number(item.id_detalle || 0) > 0) { return "d:" + Number(item.id_detalle); }
        if (Number(item.id_sku_erp || 0) > 0) { return "s:" + Number(item.id_sku_erp); }
        var sku = String(item.sku || "").trim().toLowerCase();
        if (sku) { return "sku:" + sku; }
        var skuProveedor = String(item.sku_proveedor || "").trim().toLowerCase();
        if (skuProveedor) { return "sp:" + skuProveedor; }
        return "n:" + String(item.nombre || "").trim().toLowerCase();
    }
    function clavesItemBusqueda(item) {
        var claves = [];
        var idDetalle = Number(item && item.id_detalle || 0);
        var idSku = Number(item && item.id_sku_erp || 0);
        var idSkuProveedor = Number(item && item.id_sku_proveedor || 0);
        var sku = String(item && item.sku || "").trim().toLowerCase();
        var skuProveedor = String(item && item.sku_proveedor || "").trim().toLowerCase();
        var nombre = String(item && item.nombre || "").trim().toLowerCase();
        if (idDetalle > 0) { claves.push("d:" + idDetalle); }
        if (idSku > 0) { claves.push("s:" + idSku); }
        if (idSkuProveedor > 0) { claves.push("spid:" + idSkuProveedor); }
        if (sku) { claves.push("sku:" + sku); }
        if (skuProveedor) { claves.push("sp:" + skuProveedor); }
        if (nombre) { claves.push("n:" + nombre); }
        return claves;
    }
    function registrarItemEnMapa(mapa, item, valor) {
        clavesItemBusqueda(item).forEach(function (clave) {
            if (mapa[clave] === undefined) { mapa[clave] = valor; }
        });
    }
    function buscarItemEnMapa(mapa, item) {
        var claves = clavesItemBusqueda(item);
        for (var i = 0; i < claves.length; i++) {
            if (mapa[claves[i]] !== undefined) { return mapa[claves[i]]; }
        }
        return null;
    }
    function compactarItemsDuplicados() {
        // [Codex: v2026.06.16] Evita que XML reutilizado o cargado varias veces duplique SKUs en la tabla.
        var mapa = {};
        var compactados = [];
        items.forEach(function (item) {
            var existente = buscarItemEnMapa(mapa, item);
            if (existente) {
                existente.datos_fiscales = mergearDatosFiscales(existente.datos_fiscales, item.datos_fiscales);
                if (!existente.id_sku_erp && item.id_sku_erp) { existente.id_sku_erp = item.id_sku_erp; }
                if (!existente.id_sku_proveedor && item.id_sku_proveedor) { existente.id_sku_proveedor = item.id_sku_proveedor; }
                if (!existente.sku_proveedor && item.sku_proveedor) { existente.sku_proveedor = item.sku_proveedor; }
                if (item.producto_registrado) { existente.producto_registrado = 1; }
                if (item.id_sku_erp > 0) { existente.requiere_revision = 0; existente.tipo_item = "producto"; }
                registrarItemEnMapa(mapa, existente, existente);
                return;
            }
            compactados.push(item);
            registrarItemEnMapa(mapa, item, item);
        });
        items = compactados;
    }
    function claveCatalogoBusqueda(data) {
        var idSku = Number(data.id_sku || data.id_sku_erp || 0);
        if (idSku > 0) { return "s:" + idSku; }
        var sku = String(data.sku || "").trim().toLowerCase();
        if (sku) { return "sku:" + sku; }
        var skuProveedor = String(data.sku_proveedor || "").trim().toLowerCase();
        if (skuProveedor) { return "sp:" + skuProveedor; }
        var nombre = String(data.nombre || "").trim().toLowerCase();
        return nombre ? "n:" + nombre : "n:";
    }
    function existeItemDuplicadoCatalogo(data) {
        var clave = claveCatalogoBusqueda(data);
        return items.some(function (x) { return claveItem(x) === clave; });
    }
    function normalizarConcepto(concepto) {
        return {
            id_orden_detalle: Number(concepto.id_orden_detalle || 0),
            id_sku_erp: Number(concepto.id_sku_erp || 0),
            id_sku_proveedor: Number(concepto.id_sku_proveedor || 0),
            costo_unitario: parseDecimal(concepto.costo_xml),
            porcentaje_impuesto: parseDecimal(concepto.iva_porcentaje),
            descuento: parseDecimal(concepto.descuento || concepto.descuento_xml),
            // [Codex: v2026.06.07] Si viene el indicador desde XML, respetarlo; por defecto se toma como costo sin impuestos.
            costo_unitario_incluye_impuesto: parseFlag(concepto.costo_unitario_incluye_impuesto || concepto.valor_unitario_incluye_impuesto),
            cantidad: parseDecimal(concepto.cantidad_xml),
            sku: String(concepto.no_identificacion || "").trim(),
            nombre: String(concepto.descripcion || "").trim() || "Producto XML",
            unidad: String(concepto.unidad || concepto.unidad_medida || "Pza").trim(),
            resultado_conciliacion: String(concepto.resultado_conciliacion || ""),
            datos_fiscales: normalizarDatosFiscales({
                clave_sat: concepto.clave_producto_sat || concepto.clave_prod_serv || concepto.clave_sat || concepto.ClaveProdServ,
                clave_unidad_sat: concepto.clave_unidad_sat || concepto.clave_unidad || concepto.ClaveUnidad,
                unidad: concepto.unidad || concepto.unidad_medida,
                objeto_impuesto: concepto.objeto_impuesto,
                tipo_impuesto: concepto.tipo_impuesto,
                porcentaje_iva: concepto.iva_porcentaje || concepto.tasa_iva,
                porcentaje_ieps: concepto.ieps_porcentaje || concepto.tasa_cuota_ieps,
                incluye_iva: concepto.valor_unitario_incluye_impuesto,
                requiere_factura: concepto.requiere_factura
            })
        };
    }
function normalizarConceptoParaItems(concepto) {
    var idSku = Number(concepto.id_sku_erp || concepto.id_sku || 0);
    return {
        id_detalle: 0,
            id_solicitud_detalle: 0,
            id_sku_erp: idSku,
            id_sku_proveedor: Number(concepto.id_sku_proveedor || 0),
            costo_unitario: parseDecimal(concepto.valor_unitario || concepto.costo_xml || concepto.valor_unitario_bruto || 0),
            costo_unitario_incluye_impuesto: false,
            porcentaje_impuesto: parseDecimal(concepto.iva_porcentaje || 0),
            descuento: parseDecimal(concepto.descuento || 0),
            cantidad: parseDecimal(concepto.cantidad_xml || concepto.cantidad || 0),
            sku: String(concepto.sku || concepto.no_identificacion || "").trim(),
            sku_proveedor: String(concepto.sku_proveedor || concepto.no_identificacion || "").trim(),
            nombre: String(concepto.nombre || concepto.descripcion || "Producto XML").trim(),
            unidad: String(concepto.unidad_erp || concepto.unidad || concepto.unidad_medida || "Pza").trim(),
            resultado_conciliacion: String(concepto.resultado_conciliacion || ""),
            datos_fiscales: normalizarDatosFiscales({
                clave_sat: concepto.clave_producto_sat || concepto.clave_prod_serv || concepto.clave_sat || concepto.ClaveProdServ,
                clave_unidad_sat: concepto.clave_unidad_sat || concepto.clave_unidad || concepto.ClaveUnidad,
                unidad: concepto.unidad || concepto.unidad_medida,
                objeto_impuesto: concepto.objeto_impuesto,
                tipo_impuesto: concepto.tipo_impuesto,
                porcentaje_iva: concepto.iva_porcentaje || concepto.tasa_iva,
                porcentaje_ieps: concepto.ieps_porcentaje || concepto.tasa_cuota_ieps,
                incluye_iva: concepto.valor_unitario_incluye_impuesto,
                requiere_factura: concepto.requiere_factura
            }),
        tipo_item: idSku > 0 ? "producto" : "producto_nuevo",
        producto_registrado: idSku > 0 ? 1 : 0,
        requiere_revision: idSku > 0 ? 0 : 1,
        es_importado_xml: true,
        esImportadoXml: true
    };
}
    function post(url, data) {
        return fetch(url, {method: "POST", headers: {"Content-Type": "application/x-www-form-urlencoded; charset=UTF-8"}, body: new URLSearchParams(data), credentials: "same-origin"}).then(function (r) { return r.json(); });
    }

    /**
     * Modulo: ERP Compras
     * Funcion: asegurarOrdenBorrador
     * Documentacion IA: Codex GPT-5
     * Fecha: 2026-06-15
     * Descripcion: Garantiza que una orden de compra nueva tenga id de base antes de
     * operaciones que requieren orden existente (adjuntos, pagos, notas).
     * Endpoints: /compra/orden_guardar_erp
     * Notas UX: Si no hay id, guarda borrador automaticamente; evita doble captura.
     */
    function asegurarOrdenBorrador(contexto, opciones) {
        contexto = contexto || "la accion";
        opciones = opciones || {};
        var requiereItems = opciones.requiereItems === undefined ? false : !!opciones.requiereItems;
        var idActual = Number(document.getElementById("orden_id").value || 0);
        if (idActual > 0) {
            return Promise.resolve(idActual);
        }

        if (promesaBorradorEnCurso) {
            return promesaBorradorEnCurso;
        }

        var proveedor = Number(document.getElementById("orden_proveedor_selector").value || 0);
        if (!proveedor) {
            return Promise.reject(new Error("Selecciona un proveedor antes de " + contexto));
        }
        if (requiereItems && !items.length) {
            return Promise.reject(new Error("Agrega al menos una partida antes de " + contexto));
        }

        promesaBorradorEnCurso = guardarSinRedireccion("borrador").then(function (respuesta) {
            var idGenerado = Number(respuesta.depurar && respuesta.depurar.id_orden_compra || 0);
            if (!idGenerado) {
                throw new Error("No se pudo crear la orden temporal");
            }
            document.getElementById("orden_id").value = String(idGenerado);
            estatusOrden = "borrador";
            cargarAdjuntos();
            return idGenerado;
        }).finally(function () {
            promesaBorradorEnCurso = null;
        });
        return promesaBorradorEnCurso;
    }

    function guardarSinRedireccion(estatus) {
        return post("/compra/orden_guardar_erp", datos(estatus)).then(function (r) {
            if (r.error) { throw new Error(r.mensaje); }
            return r;
    });
}
function renderizarDiferenciasFila(items, template) {
    if (!Array.isArray(items) || items.length === 0) {
        return "";
    }
    return items.map(function (x) {
        return template(x);
    }).join("");
}
function renderFilaDiferenciaSimple(x) {
    return "<tr><td>" + esc(x.sku || "-") + "</td><td>" + esc(x.nombre || x.producto || "-") +
        "</td><td class=\"text-end\">" + Number(x.cantidad || 0).toFixed(6) +
        "</td><td class=\"text-end\">$" + Number(x.costo_unitario || 0).toFixed(2) + "</td></tr>";
}
function renderFilaDiferenciaCambio(x) {
    var deltaCantidad = Number((x.delta && x.delta.cantidad) || 0);
    var deltaCosto = Number((x.delta && x.delta.costo_unitario) || 0);
    var signoCantidad = deltaCantidad >= 0 ? "+" : "";
    var signoCosto = deltaCosto >= 0 ? "+" : "";
    return "<tr><td>" + esc(x.sku || "-") + "</td><td>" + esc(x.nombre || x.producto || "-") +
        "</td><td class=\"text-end\">" + signoCantidad + deltaCantidad.toFixed(6) +
        "</td><td class=\"text-end\">" + signoCosto + "$" + deltaCosto.toFixed(2) + "</td></tr>";
}
    function direccion(a) {
        return [a.calle, a.numero_exterior, a.numero_interior, a.colonia, a.ciudad, a.estado, a.codigo_postal].filter(Boolean).join(", ");
    }
    function cargarCatalogos() {
        return fetch("/compra/ordenes_catalogos_erp", {credentials: "same-origin"}).then(function (r) { return r.json(); }).then(function (r) {
            var data = r.depurar || {};
            document.getElementById("orden_proveedor_selector").innerHTML = "<option value=\"\">Seleccionar</option>" + (data.proveedores || []).map(function (x) {
                return "<option value=\"" + esc(x.id_proveedor) + "\">" + esc(x.proveedor) + "</option>";
            }).join("");
            document.getElementById("orden_almacen").innerHTML = "<option value=\"\">Seleccionar</option>" + (data.almacenes || []).map(function (x) {
                return "<option value=\"" + esc(x.id_almacen) + "\" data-contacto=\"" + esc(x.contacto_recepcion || "") + "\" data-telefono=\"" + esc(x.telefono_recepcion || "") + "\" data-direccion=\"" + esc(direccion(x)) + "\">" + esc(x.almacen) + "</option>";
            }).join("");
        });
    }
    function buscarSku() {
        var proveedor = document.getElementById("orden_proveedor_selector").value;
        var q = document.getElementById("orden_buscar_sku").value.trim();
        var box = document.getElementById("orden_resultados");
        if (!proveedor || q.length < 2) { box.classList.add("d-none"); return; }
        fetch("/compra/orden_buscar_skus_erp?" + new URLSearchParams({id_proveedor: proveedor, q: q}), {credentials: "same-origin"})
            .then(function (r) { return r.json(); }).then(function (r) {
                box.innerHTML = (r.depurar || []).map(function (x) {
                    return "<button type=\"button\" class=\"btn btn-flush text-start w-100 p-4 border-bottom\" data-sku='" + encodeURIComponent(JSON.stringify(x)) +
                        "'><div class=\"d-flex align-items-center gap-3\">" + imagenSkuHtml(x) +
                        "<div class=\"flex-grow-1 min-w-0\"><div><span class=\"fw-bold\">" + esc(x.sku) + "</span> " + esc(x.nombre) +
                        "<span class=\"float-end\">$" + Number(x.costo_ultimo || 0).toFixed(2) + "</span></div>" +
                        evidenciaCostoHtml(x) + advertenciasOperativasHtml(x) + "</div></div></button>";
                }).join("") || "<div class=\"p-5 text-muted\">Sin relacion activa proveedor-SKU. Revisa Proveedores/Catalogo o captura producto propuesto.</div>";
                box.classList.remove("d-none");
            }).catch(alertaError);
    }
function agregarSku(data) {
    if (existeItemDuplicadoCatalogo(data)) { return; }
    var costoIncluyeImpuestos = !estaVacio(data.costo_incluye_impuestos)
        ? parseFlag(data.costo_incluye_impuestos)
        : parseFlag(data.incluye_impuestos);
    items.push({id_detalle: 0, id_solicitud_detalle: 0, id_sku_erp: data.id_sku, sku: data.sku,
        sku_proveedor: data.sku_proveedor, nombre: data.nombre, unidad: data.unidad,
        url_imagen: data.url_imagen || "",
        cantidad: Math.max(1, Number(data.cantidad_minima || 1)),
        costo_unitario: Number(data.costo_ultimo || 0),
        costo_unitario_incluye_impuesto: costoIncluyeImpuestos,
        porcentaje_impuesto: Number(data.iva_porcentaje || 0), descuento: 0,
        fuente_costo: data.fuente_costo || "",
        origen_costo: data.origen_costo || "",
        moneda_costo: data.moneda_costo || "",
        vigencia_desde: data.vigencia_desde || "",
        vigencia_hasta: data.vigencia_hasta || "",
        id_costo_proveedor_sku: data.id_costo_proveedor_sku || 0,
        id_lista_proveedor_erp: data.id_lista_proveedor_erp || 0,
        id_sku_proveedor: data.id_sku_proveedor || 0,
        tipo_item: "producto",
        producto_registrado: 1,
        requiere_revision: 0,
        advertencias_operativas: data.advertencias_operativas || [],
        datos_fiscales: normalizarDatosFiscales({
            clave_sat: data.clave_producto_sat,
            clave_unidad_sat: data.clave_unidad_sat,
            unidad: data.unidad || "Pza",
            objeto_impuesto: data.objeto_impuesto,
            porcentaje_iva: data.iva_porcentaje,
            porcentaje_ieps: data.ieps_porcentaje,
            incluye_iva: data.incluye_impuestos
        })});
    uidItem(items[items.length - 1]);
        document.getElementById("orden_buscar_sku").value = "";
        document.getElementById("orden_resultados").classList.add("d-none");
        render();
    }
/**
 * ERP Compras - Ordenes
 * Documentacion IA: Codex GPT-5
 * Version: 2026.06.17
 * Descripcion: agrega cargos/servicios no inventariables desde el formulario de orden.
 * Regla: impactan total de compra, no requieren SKU ERP y no deben generar recepcion de almacen.
 */
function agregarCargoNoInventariable() {
    if (!puedeEditar) { return; }
    var conceptoInput = document.getElementById("orden_cargo_concepto");
    var tipoInput = document.getElementById("orden_cargo_tipo");
    var importeInput = document.getElementById("orden_cargo_importe");
    var concepto = normalizarTexto(conceptoInput ? conceptoInput.value : "");
    var tipo = normalizarTexto(tipoInput ? tipoInput.value : "cargo").toLowerCase();
    var importe = parseDecimal(importeInput ? importeInput.value : 0);
    if (!concepto) {
        alertaError(new Error("Escribe el concepto del cargo o servicio"));
        return;
    }
    if (!esTipoItemNoInventariable(tipo)) {
        alertaError(new Error("Selecciona un tipo no inventariable valido"));
        return;
    }
    if (importe <= 0) {
        alertaError(new Error("El importe del cargo o servicio debe ser mayor a cero"));
        return;
    }
    var claveConcepto = concepto.toLowerCase();
    var duplicado = items.some(function (item) {
        return esTipoItemNoInventariable(item && item.tipo_item) &&
            normalizarTexto(item.nombre).toLowerCase() === claveConcepto &&
            normalizarTexto(item.tipo_item).toLowerCase() === tipo;
    });
    if (duplicado) {
        alertaError(new Error("Ese cargo o servicio ya esta agregado en la orden"));
        return;
    }
    var item = {
        id_detalle: 0,
        id_solicitud_detalle: 0,
        id_sku_erp: 0,
        id_sku_proveedor: 0,
        sku: "",
        sku_proveedor: "",
        nombre: concepto,
        unidad: "Servicio",
        cantidad: 1,
        costo_unitario: importe,
        costo_unitario_incluye_impuesto: false,
        porcentaje_impuesto: 0,
        descuento: 0,
        tipo_item: tipo,
        producto_registrado: 0,
        requiere_revision: 0,
        advertencias_operativas: [],
        datos_fiscales: normalizarDatosFiscales({
            unidad: "Servicio",
            tipo_impuesto: "IVA",
            porcentaje_iva: 0,
            incluye_iva: 0,
            requiere_factura: 1
        })
    };
    uidItem(item);
    items.push(item);
    conceptoInput.value = "";
    importeInput.value = "";
    render();
}
/**
 * ERP Compras - Ordenes
 * Documentacion IA: Codex GPT-5
 * Version: 2026.06.17
 * Descripcion: agrega producto fisico pendiente de alta/relacion para generar incidencia operativa.
 * Regla: se puede guardar en borrador; el envio queda bloqueado hasta resolver Catalogo/Proveedores.
 */
function agregarProductoNuevoPendiente() {
    if (!puedeEditar) { return; }
    var skuInput = document.getElementById("orden_producto_nuevo_sku");
    var nombreInput = document.getElementById("orden_producto_nuevo_nombre");
    var costoInput = document.getElementById("orden_producto_nuevo_costo");
    var sku = normalizarTexto(skuInput ? skuInput.value : "");
    var nombre = normalizarTexto(nombreInput ? nombreInput.value : "");
    var costo = parseDecimal(costoInput ? costoInput.value : 0);
    if (!sku) {
        alertaError(new Error("Escribe el SKU del producto pendiente"));
        return;
    }
    if (!nombre) {
        alertaError(new Error("Escribe el nombre del producto pendiente"));
        return;
    }
    if (costo < 0) {
        alertaError(new Error("El costo inicial no puede ser negativo"));
        return;
    }
    if (existeItemDuplicadoCatalogo({sku: sku, nombre: nombre})) {
        alertaError(new Error("Ese SKU ya esta agregado en la orden"));
        return;
    }
    var item = {
        id_detalle: 0,
        id_solicitud_detalle: 0,
        id_sku_erp: 0,
        id_sku_proveedor: 0,
        sku: sku,
        sku_proveedor: sku,
        nombre: nombre,
        unidad: "Pza",
        cantidad: 1,
        costo_unitario: costo,
        costo_unitario_incluye_impuesto: false,
        porcentaje_impuesto: 0,
        descuento: 0,
        tipo_item: "producto_nuevo",
        producto_registrado: 0,
        requiere_revision: 1,
        advertencias_operativas: [{
            codigo: "producto_pendiente_alta",
            nivel: "warning",
            mensaje: "Catalogo debe crear o vincular este producto antes de enviar"
        }],
        datos_fiscales: normalizarDatosFiscales({
            unidad: "Pza",
            tipo_impuesto: "IVA",
            porcentaje_iva: 0,
            incluye_iva: 0,
            requiere_factura: 1
        })
    };
    uidItem(item);
    items.push(item);
    skuInput.value = "";
    nombreInput.value = "";
    costoInput.value = "";
    render();
}
    function cargarOrden() {
        var id = Number(document.getElementById("orden_id").value || 0);
        if (!id) { return Promise.resolve(); }
        return fetch("/compra/orden_consultar_erp?id_orden_compra=" + id, {credentials: "same-origin"}).then(function (r) { return r.json(); }).then(function (r) {
            if (r.error) { throw new Error(r.mensaje); }
            var o = r.depurar.orden;
            estatusOrden = o.estatus;
            document.getElementById("orden_titulo").textContent = o.folio;
            document.getElementById("orden_estado_texto").textContent = o.estatus;
            var botonVerDiferencias = document.getElementById("orden_ver_diferencias");
            if (botonVerDiferencias) {
                botonVerDiferencias.classList.toggle("d-none", !(Number(o.id_solicitud || 0) > 0));
            }
            document.getElementById("orden_proveedor_selector_grupo").classList.add("d-none");
            document.getElementById("orden_proveedor_grupo").classList.remove("d-none");
            document.getElementById("orden_proveedor_selector").value = o.id_proveedor || "";
            document.getElementById("orden_busqueda_grupo").classList.remove("d-none");
            document.getElementById("orden_proveedor").value = o.proveedor;
            document.getElementById("orden_almacen").value = o.id_almacen_destino || "";
            document.getElementById("orden_fecha_entrega").value = (o.fecha_entrega_estimada || "").slice(0, 10);
            document.getElementById("orden_folio_proveedor").value = o.folio_proveedor || "";
            document.getElementById("orden_moneda").value = o.moneda || "MXN";
            document.getElementById("orden_tipo_cambio").value = o.tipo_cambio || 1;
            document.getElementById("orden_contacto").value = o.contacto_recepcion || "";
            document.getElementById("orden_telefono").value = o.telefono_recepcion || "";
            document.getElementById("orden_direccion").value = o.direccion_entrega || "";
            document.getElementById("orden_observaciones").value = o.observaciones || "";
            items = (r.depurar.detalle || []).map(function (x) {
                var incluyeImpuesto = parseFlag(x.costo_unitario_incluye_impuesto);
                var impuestoPct = Number(x.porcentaje_impuesto || 0);
                var costoGuardado = Number(x.costo_unitario || 0);
                var costoCapturado = incluyeImpuesto && impuestoPct > 0
                    ? costoGuardado * (1 + impuestoPct / 100)
                    : costoGuardado;
                return {id_detalle: x.id_detalle, id_solicitud_detalle: x.id_solicitud_detalle, id_sku_erp: x.id_sku_erp,
                    sku: x.sku || x.sku_proveedor || "",
                    sku_proveedor: x.sku_proveedor || x.sku || "",
                    nombre: x.nombre_producto || x.nombre_sku || "Producto sin nombre",
                    unidad: x.unidad || "Pza",
                    url_imagen: x.url_imagen || "",
                    cantidad: Number(x.cantidad), costo_unitario: costoCapturado,
                    costo_unitario_incluye_impuesto: incluyeImpuesto,
                    porcentaje_impuesto: impuestoPct, descuento: Number(x.descuento),
                    fuente_costo: parseJsonSeguro(x.evidencia_costo_json || {}).fuente_costo || "",
                    origen_costo: parseJsonSeguro(x.evidencia_costo_json || {}).origen_costo || "",
                    moneda_costo: parseJsonSeguro(x.evidencia_costo_json || {}).moneda_costo || "",
                    vigencia_desde: parseJsonSeguro(x.evidencia_costo_json || {}).vigencia_desde || "",
                    vigencia_hasta: parseJsonSeguro(x.evidencia_costo_json || {}).vigencia_hasta || "",
                    id_costo_proveedor_sku: parseJsonSeguro(x.evidencia_costo_json || {}).id_costo_proveedor_sku || 0,
                    id_lista_proveedor_erp: parseJsonSeguro(x.evidencia_costo_json || {}).id_lista_proveedor_erp || 0,
                    tipo_item: x.tipo_item || "producto",
                    producto_registrado: Number(x.producto_registrado || 0),
                    requiere_revision: Number(x.requiere_revision || 0),
                    datos_fiscales: normalizarDatosFiscales(parseJsonSeguro(x.datos_fiscales_json || x.datos_fiscales))};
            }).map(function (x) { return uidItem(x), x; });
            puedeEditar = modo === "editar" && o.estatus === "borrador";
            if (!puedeEditar) { deshabilitar(); }
            if (puedeCancelar && (o.estatus === "borrador" || o.estatus === "enviada")) {
                document.getElementById("orden_cancelar").classList.remove("d-none");
            }
            render();
            cargarFinanzas();
            cargarAdjuntos();
            cargarDocumentosXml();
            if (Number(o.id_solicitud || 0) > 0) {
                cargarDiferenciasSolicitud().then(function (r) {
                    if (r && r.error) {
                        document.getElementById("orden_ver_diferencias").classList.add("d-none");
                    }
                });
            }
        });
    }
    function moneda(value) {
        return "$" + Number(value || 0).toFixed(2);
    }
    function etiquetaMetodo(value) {
        return {
            tarjeta_debito: "Tarjeta de debito",
            tarjeta_credito: "Tarjeta de credito",
            transferencia: "Transferencia electronica de fondos",
            efectivo: "Efectivo"
        }[value] || value;
    }
    function cargarFinanzas() {
        var id = Number(document.getElementById("orden_id").value || 0);
        if (!id || !puedeVerFinanzas) { return; }
        fetch("/compra/orden_finanzas_consultar_erp?id_orden_compra=" + id, {credentials: "same-origin"})
            .then(function (r) { return r.json(); }).then(function (r) {
                if (r.error) { throw new Error(r.mensaje); }
                var data = r.depurar || {};
                var resumen = data.resumen || {};
                document.getElementById("orden_finanzas_total").textContent = moneda(resumen.total_orden);
                document.getElementById("orden_finanzas_aplicado").textContent = moneda(resumen.total_aplicado);
                document.getElementById("orden_finanzas_saldo").textContent = moneda(resumen.saldo_pendiente);
                document.getElementById("orden_finanzas_saldo").className = "fw-bold fs-4 " +
                    (resumen.pagada ? "text-success" : "text-danger");
                document.getElementById("orden_finanzas_pagada").classList.toggle("d-none", !resumen.pagada);

                document.getElementById("orden_pagos").innerHTML = (data.pagos || []).map(function (x) {
                    var cancelado = x.estado_pago === "cancelado";
                    var accion = puedeOperarFinanzas && !cancelado && estatusOrden !== "cancelada"
                        ? "<button type=\"button\" class=\"btn btn-sm btn-light-danger\" data-cancelar-pago=\"" +
                            x.id_pago_orden + "\">Cancelar</button>" : "";
                    return "<tr><td>" + esc(etiquetaMetodo(x.metodo_pago)) +
                        "</td><td><span class=\"badge " + (cancelado ? "badge-light-danger" :
                            (x.estado_pago === "pendiente" ? "badge-light-warning" : "badge-light-success")) +
                        "\">" + esc(x.estado_pago) + "</span></td><td>" + esc(x.referencia || "-") +
                        "</td><td>" + esc((x.fecha_pago || x.fecha_registro || "").slice(0, 10)) +
                        "</td><td>" + esc(x.observaciones || "-") +
                        "</td><td class=\"text-end fw-bold\">" + moneda(x.monto) +
                        "</td><td class=\"text-end\">" + accion + "</td></tr>";
                }).join("") || "<tr><td colspan=\"7\" class=\"text-center text-muted py-6\">Sin pagos registrados</td></tr>";

                document.getElementById("orden_notas_credito").innerHTML = (data.notas_credito || []).map(function (x) {
                    var cancelada = x.estatus === "cancelada";
                    var accion = puedeOperarFinanzas && !cancelada && estatusOrden !== "cancelada"
                        ? "<button type=\"button\" class=\"btn btn-sm btn-light-danger\" data-cancelar-nota=\"" +
                            x.id_nota_credito_orden + "\">Cancelar</button>" : "";
                    return "<tr><td>" + esc(x.referencia || "-") +
                        "</td><td><span class=\"badge " + (cancelada ? "badge-light-danger" :
                            (x.estatus === "pendiente" ? "badge-light-warning" : "badge-light-success")) +
                        "\">" + esc(x.estatus) + "</span></td><td>" +
                        esc((x.fecha_aplicacion || x.fecha_registro || "").slice(0, 10)) +
                        "</td><td>" + esc(x.observaciones || "-") +
                        "</td><td class=\"text-end fw-bold\">" + moneda(x.monto) +
                        "</td><td class=\"text-end\">" + accion + "</td></tr>";
                }).join("") || "<tr><td colspan=\"6\" class=\"text-center text-muted py-6\">Sin notas de credito</td></tr>";

                var permitir = puedeOperarFinanzas && modo !== "ver" &&
                    estatusOrden !== "borrador" && estatusOrden !== "cancelada";
                document.getElementById("orden_pago_formulario").classList.toggle("d-none", !permitir);
                document.getElementById("orden_nota_formulario").classList.toggle("d-none", !permitir);
            }).catch(alertaError);
    }
    function registrarPago() {
        var metodo = document.getElementById("orden_pago_metodo").value;
        var referencia = document.getElementById("orden_pago_referencia").value.trim();
        if (metodo !== "efectivo" && !referencia) {
            alertaError(new Error("Captura el folio o referencia del pago"));
            return;
        }
        asegurarOrdenBorrador("registrar el pago").then(function () {
            return post("/compra/orden_pago_registrar_erp", {
                id_orden_compra: document.getElementById("orden_id").value,
                metodo_pago: metodo,
                estado_pago: document.getElementById("orden_pago_estado").value,
                monto: document.getElementById("orden_pago_monto").value,
                fecha_pago: document.getElementById("orden_pago_fecha").value,
                referencia: referencia,
                observaciones: document.getElementById("orden_pago_observaciones").value
            });
        }).then(function (r) {
            if (r.error) { throw new Error(r.mensaje); }
            document.getElementById("orden_pago_monto").value = "";
            document.getElementById("orden_pago_referencia").value = "";
            document.getElementById("orden_pago_observaciones").value = "";
            cargarFinanzas();
        }).catch(alertaError);
    }
    function registrarNotaCredito() {
        var referencia = document.getElementById("orden_nota_referencia").value.trim();
        if (!referencia) {
            alertaError(new Error("Captura el folio o referencia de la nota de credito"));
            return;
        }
        asegurarOrdenBorrador("registrar la nota de credito").then(function () {
            return post("/compra/orden_nota_credito_registrar_erp", {
                id_orden_compra: document.getElementById("orden_id").value,
                estatus: document.getElementById("orden_nota_estado").value,
                monto: document.getElementById("orden_nota_monto").value,
                fecha_aplicacion: document.getElementById("orden_nota_fecha").value,
                referencia: referencia,
                observaciones: document.getElementById("orden_nota_observaciones").value
            });
        }).then(function (r) {
            if (r.error) { throw new Error(r.mensaje); }
            document.getElementById("orden_nota_monto").value = "";
            document.getElementById("orden_nota_referencia").value = "";
            document.getElementById("orden_nota_observaciones").value = "";
            cargarFinanzas();
        }).catch(alertaError);
    }
    function etiquetaDocumento(value) {
        return {
            cotizacion: "Cotizacion",
            factura: "Factura",
            comprobante_pago: "Comprobante de pago",
            nota_credito: "Nota de credito",
            orden_firmada: "Orden firmada",
            otro: "Otro"
        }[value] || value;
    }
    function tamanoArchivo(bytes) {
        var value = Number(bytes || 0);
        if (value < 1024) { return value + " B"; }
        if (value < 1048576) { return (value / 1024).toFixed(1) + " KB"; }
        return (value / 1048576).toFixed(1) + " MB";
    }
    function aplicarConceptosXmlAItems() {
        var id = Number(document.getElementById("orden_id").value || 0);
        if (!id) { return Promise.resolve({agregados: 0, actualizados: 0}); }
        return fetch("/compra/orden_xml_conciliacion_erp?id_orden_compra=" + id, {credentials: "same-origin"})
            .then(function (r) { return r.json(); }).then(function (r) {
                if (r.error) { throw new Error(r.mensaje); }
                var data = r.depurar || {};
                var conceptos = data.conceptos || [];
                var detalle = data.detalle_orden || [];
                var mapaDetalle = {};
                detalle.forEach(function (d) { mapaDetalle[Number(d.id_detalle || 0)] = d; });
                var mapaItem = {};
                items.forEach(function (it) { registrarItemEnMapa(mapaItem, it, it); });
                var itemsActualizadosXml = {};
                var agregados = 0;
                var actualizados = 0;

                conceptos.forEach(function (raw) {
                    var concepto = normalizarConcepto(raw);
                    if (concepto.cantidad <= 0) { return; }

                    var item = null;

                    if (concepto.id_orden_detalle) {
                        for (var i = 0; i < items.length; i++) {
                            if (Number(items[i].id_detalle || 0) === concepto.id_orden_detalle) { item = items[i]; break; }
                        }
                    }
                    if (!item && concepto.id_sku_erp) {
                        for (var j = 0; j < items.length; j++) {
                            if (Number(items[j].id_sku_erp || 0) === concepto.id_sku_erp) { item = items[j]; break; }
                        }
                    }
                    if (!item && concepto.sku) {
                        for (var k = 0; k < items.length; k++) {
                            if (String(items[k].sku || "").trim() === concepto.sku) { item = items[k]; break; }
                        }
                    }

                    if (item) {
                        var claveProcesada = uidItem(item);
                        item.cantidad = itemsActualizadosXml[claveProcesada]
                            ? parseDecimal(item.cantidad) + concepto.cantidad
                            : concepto.cantidad;
                        itemsActualizadosXml[claveProcesada] = true;
                        if (concepto.costo_unitario > 0) { item.costo_unitario = concepto.costo_unitario; }
                        if (concepto.costo_unitario_incluye_impuesto !== undefined && concepto.costo_unitario_incluye_impuesto !== null) {
                            item.costo_unitario_incluye_impuesto = parseFlag(concepto.costo_unitario_incluye_impuesto);
                        } else if (item.costo_unitario_incluye_impuesto === undefined || item.costo_unitario_incluye_impuesto === null) {
                            item.costo_unitario_incluye_impuesto = false;
                        }
                        if (concepto.porcentaje_impuesto >= 0) { item.porcentaje_impuesto = concepto.porcentaje_impuesto; }
                        if (concepto.descuento > 0) { item.descuento = concepto.descuento; }
                        if (concepto.nombre) { item.nombre = concepto.nombre; }
                        if (concepto.unidad) { item.unidad = concepto.unidad; }
                        if (!item.sku && concepto.sku) { item.sku = concepto.sku; }
                        if (concepto.id_sku_erp && !item.id_sku_erp) { item.id_sku_erp = concepto.id_sku_erp; }
                        if (!item.producto_registrado) { item.producto_registrado = concepto.id_sku_erp > 0 ? 1 : 0; }
                        if (concepto.id_sku_erp > 0 && item.requiere_revision) { item.requiere_revision = 0; item.tipo_item = "producto"; }
                        item.tipo_item = item.tipo_item || (item.id_sku_erp > 0 ? "producto" : "producto_nuevo");
                        item.datos_fiscales = mergearDatosFiscales(item.datos_fiscales, concepto.datos_fiscales);
                        if (mapaDetalle[concepto.id_orden_detalle] && !item.sku_proveedor) {
                            item.sku_proveedor = mapaDetalle[concepto.id_orden_detalle].sku_proveedor || "";
                        }
                        item.es_importado_xml = true;
                        item.esImportadoXml = true;
                        actualizados++;
                        return;
                    }

                    var filaDetalle = mapaDetalle[concepto.id_orden_detalle] || {};
                    var existente = buscarItemEnMapa(mapaItem, {
                        id_sku_erp: concepto.id_sku_erp || 0,
                        id_sku_proveedor: concepto.id_sku_proveedor || 0,
                        sku: concepto.sku || "",
                        sku_proveedor: concepto.sku_proveedor || "",
                        nombre: concepto.nombre || ""
                    });
                    if (existente) {
                        var claveExistente = uidItem(existente);
                        existente.cantidad = itemsActualizadosXml[claveExistente]
                            ? parseDecimal(existente.cantidad) + concepto.cantidad
                            : concepto.cantidad;
                        itemsActualizadosXml[claveExistente] = true;
                        if (concepto.costo_unitario > 0) { existente.costo_unitario = concepto.costo_unitario; }
                        if (concepto.costo_unitario_incluye_impuesto !== undefined && concepto.costo_unitario_incluye_impuesto !== null) {
                            existente.costo_unitario_incluye_impuesto = parseFlag(concepto.costo_unitario_incluye_impuesto);
                        } else if (existente.costo_unitario_incluye_impuesto === undefined || existente.costo_unitario_incluye_impuesto === null) {
                            existente.costo_unitario_incluye_impuesto = false;
                        }
                        if (concepto.porcentaje_impuesto >= 0) { existente.porcentaje_impuesto = concepto.porcentaje_impuesto; }
                        if (concepto.descuento > 0) { existente.descuento = concepto.descuento; }
                        if (concepto.nombre) { existente.nombre = concepto.nombre; }
                        if (concepto.unidad) { existente.unidad = concepto.unidad; }
                        existente.datos_fiscales = mergearDatosFiscales(existente.datos_fiscales, concepto.datos_fiscales);
                        if (!existente.producto_registrado) { existente.producto_registrado = concepto.id_sku_erp > 0 ? 1 : 0; }
                        if (concepto.id_sku_erp > 0 && existente.requiere_revision) { existente.requiere_revision = 0; existente.tipo_item = "producto"; }
                        existente.tipo_item = existente.tipo_item || (concepto.id_sku_erp > 0 ? "producto" : "producto_nuevo");
                        if (existente.costo_unitario_incluye_impuesto === undefined || existente.costo_unitario_incluye_impuesto === null) {
                            existente.costo_unitario_incluye_impuesto = false;
                        }
                        existente.es_importado_xml = true;
                        existente.esImportadoXml = true;
                        actualizados++;
                        return;
                    }

                    items.push({
                        id_detalle: 0,
                        id_solicitud_detalle: 0,
                        id_sku_erp: concepto.id_sku_erp || 0,
                        id_sku_proveedor: concepto.id_sku_proveedor || 0,
                        sku: concepto.sku,
                        sku_proveedor: concepto.sku_proveedor || filaDetalle.sku_proveedor || "",
                        nombre: concepto.nombre,
                        unidad: concepto.unidad,
                        cantidad: concepto.cantidad,
                        costo_unitario: concepto.costo_unitario || parseDecimal(filaDetalle.costo_unitario || 0),
                        costo_unitario_incluye_impuesto: parseFlag(concepto.costo_unitario_incluye_impuesto),
                        porcentaje_impuesto: concepto.porcentaje_impuesto >= 0 ? concepto.porcentaje_impuesto : parseDecimal(filaDetalle.porcentaje_impuesto || 0),
                        descuento: concepto.descuento,
                        tipo_item: concepto.id_sku_erp ? "producto" : "producto_nuevo",
                        producto_registrado: concepto.id_sku_erp ? 1 : 0,
                        requiere_revision: concepto.id_sku_erp ? 0 : 1,
                        datos_fiscales: concepto.datos_fiscales,
                        es_importado_xml: true,
                        esImportadoXml: true
                    });
                    registrarItemEnMapa(mapaItem, items[items.length - 1], items[items.length - 1]);
                    agregados++;
                });

                compactarItemsDuplicados();
                if (agregados || actualizados) { render(); }
                return {agregados: agregados, actualizados: actualizados};
            });
    }
    function incorporarConceptosAItems(conceptos) {
        // [Codex: v2026.06.08] Permite incorporar XML temporalmente en órdenes sin guardar.
        if (!Array.isArray(conceptos)) { return {agregados: 0, actualizados: 0}; }
        var mapaItem = {};
        items.forEach(function (it, i) { registrarItemEnMapa(mapaItem, it, i); });
        var itemsActualizadosXml = {};
        var agregados = 0;
        var actualizados = 0;

        conceptos.forEach(function (raw) {
            var concepto = normalizarConceptoParaItems(raw);
            if (concepto.cantidad <= 0) { return; }
            var itemIndex = -1;
            var matchIndex = buscarItemEnMapa(mapaItem, concepto);
            if (matchIndex !== null) { itemIndex = matchIndex; }

            if (itemIndex >= 0) {
                var item = items[itemIndex];
                var claveProcesada = uidItem(item);
                item.cantidad = itemsActualizadosXml[claveProcesada]
                    ? parseDecimal(item.cantidad) + concepto.cantidad
                    : concepto.cantidad;
                itemsActualizadosXml[claveProcesada] = true;
                if (concepto.costo_unitario > 0) { item.costo_unitario = concepto.costo_unitario; }
                if (concepto.porcentaje_impuesto >= 0) { item.porcentaje_impuesto = concepto.porcentaje_impuesto; }
                if (concepto.descuento > 0) { item.descuento = concepto.descuento; }
                if (concepto.nombre) { item.nombre = concepto.nombre; }
                if (concepto.unidad) { item.unidad = concepto.unidad; }
                if (!item.producto_registrado) { item.producto_registrado = concepto.id_sku_erp > 0 ? 1 : 0; }
                if (concepto.id_sku_erp > 0 && item.requiere_revision) { item.requiere_revision = 0; item.tipo_item = "producto"; }
                item.tipo_item = item.tipo_item || (item.id_sku_erp > 0 ? "producto" : "producto_nuevo");
                item.datos_fiscales = mergearDatosFiscales(item.datos_fiscales, concepto.datos_fiscales);
                item.es_importado_xml = true;
                item.esImportadoXml = true;
                actualizados++;
                return;
            }

            items.push(concepto);
            registrarItemEnMapa(mapaItem, concepto, items.length - 1);
            agregados++;
        });

        compactarItemsDuplicados();
        render();
        return {agregados: agregados, actualizados: actualizados};
    }
    function enviarXmlDesdeAdjunto(id, archivo) {
        var data = new FormData();
        data.append("id_orden_compra", id);
        data.append("archivo_xml", archivo);
        return fetch("/compra/orden_xml_importar_erp", {method: "POST", body: data, credentials: "same-origin"})
            .then(function (r) { return r.json(); });
    }
    function esXmlYaImportado(resp) {
        var mensaje = String(resp && resp.mensaje || "").toLowerCase();
        return mensaje.indexOf("ya fue importado") >= 0 || mensaje.indexOf("ya existe") >= 0;
    }
    function parsearXmlSinOrden(archivo) {
        var data = new FormData();
        data.append("archivo_xml", archivo);
        data.append("id_proveedor", document.getElementById("orden_proveedor_selector").value || "0");
        return fetch("/compra/orden_xml_parse_erp", {method: "POST", body: data, credentials: "same-origin"})
            .then(function (r) { return r.json(); });
    }
function cargarXmlDesdeProductos() {
        // [Codex: v2026.06.07] Carga XML desde el bloque de productos y mezcla resultados.
        var archivo = document.getElementById("orden_xml_archivo").files[0];
        if (!archivo) {
            alertaError(new Error("Selecciona un archivo xml para cargar los productos"));
            return;
        }
        var id = Number(document.getElementById("orden_id").value || 0);
        if (!/\.xml$/i.test(String(archivo.name || ""))) {
            alertaError(new Error("El archivo debe ser un XML"));
            return;
        }
        var boton = document.getElementById("orden_xml_cargar");
        boton.disabled = true;
        var promesa;
        if (id) {
            promesa = enviarXmlDesdeAdjunto(id, archivo).then(function (resp) {
                if (resp.error && esXmlYaImportado(resp)) {
                    return parsearXmlSinOrden(archivo).then(function (parseo) {
                        if (parseo.error) { throw new Error(parseo.mensaje); }
                        var resumenReuso = incorporarConceptosAItems(parseo.depurar && parseo.depurar.conceptos ? parseo.depurar.conceptos : []);
                        resumenReuso.xml_reutilizado = true;
                        return resumenReuso;
                    });
                }
                if (resp.error) { throw new Error(resp.mensaje); }
                return aplicarConceptosXmlAItems();
            });
        } else {
            promesa = parsearXmlSinOrden(archivo).then(function (resp) {
                if (resp.error) { throw new Error(resp.mensaje); }
                var resumen = incorporarConceptosAItems(resp.depurar && resp.depurar.conceptos ? resp.depurar.conceptos : []);
                if (!resumen || (!resumen.agregados && !resumen.actualizados)) {
                    throw new Error("No se pudo extraer conceptos válidos del XML");
                }
                return resumen;
            });
        }
        promesa.then(function (resumen) {
            document.getElementById("orden_xml_archivo").value = "";
            if (id) { cargarDocumentosXml(); }
            var total = (resumen && resumen.agregados || 0) + (resumen && resumen.actualizados || 0);
            return Swal.fire({
                text: (resumen && resumen.xml_reutilizado ? "XML ya registrado anteriormente; se reutilizaron sus conceptos sin duplicar el documento. " : (id ? "XML cargado y conciliado. " : "XML parseado y productos cargados. ")) +
                    "Partidas agregadas/actualizadas: " + total,
                icon: resumen && resumen.xml_reutilizado ? "info" : "success",
                confirmButtonText: "Aceptar"
            });
        }).catch(alertaError).finally(function () {
            boton.disabled = false;
        });
    }
    function obtenerConciliacionSelecciones() {
        var filas = document.querySelectorAll("[data-concepto-check]:not(:disabled)");
        var conceptos = [];
        var asignaciones = {};
        var faltan = [];
        filas.forEach(function (x) {
            if (!x.checked) { return; }
            var idConcepto = Number(x.value);
            conceptos.push(idConcepto);
            var select = document.querySelector("[data-concepto-select=\"" + idConcepto + "\"]");
            if (select && select.value) {
                asignaciones[idConcepto] = Number(select.value);
            } else {
                faltan.push(idConcepto);
            }
        });
        return {conceptos: conceptos, asignaciones: asignaciones, faltan: faltan};
    }
    function alternarAccionesConciliacion() {
        if (!puedeEditar) { return; }
        var datos = obtenerConciliacionSelecciones();
        var botones = ["orden_conciliacion_mover", "orden_conciliacion_descartar"];
        botones.forEach(function (id) {
            var btn = document.getElementById(id);
            if (!btn) { return; }
            btn.classList.toggle("d-none", datos.conceptos.length === 0);
        });
    }
    function moverConceptosSeleccionados() {
        var seleccion = obtenerConciliacionSelecciones();
        if (!seleccion.conceptos.length) {
            alertaError(new Error("Selecciona al menos un concepto"));
            return;
        }
        if (seleccion.faltan.length) {
            alertaError(new Error("Selecciona la partida para cada concepto que deseas mover"));
            return;
        }
        return post("/compra/orden_xml_mover_conceptos_erp", {
            id_orden_compra: document.getElementById("orden_id").value,
            conceptos: seleccion.conceptos,
            asignaciones_json: JSON.stringify(seleccion.asignaciones)
        }).then(function (r) {
            if (r.error) { throw new Error(r.mensaje); }
            cargarDocumentosXml();
            return Swal.fire({text: r.mensaje, icon: r.tipo === "warning" ? "warning" : "success", confirmButtonText: "Aceptar"});
        }).catch(alertaError);
    }
    function descartarConceptosSeleccionados() {
        var seleccion = obtenerConciliacionSelecciones();
        if (!seleccion.conceptos.length) {
            alertaError(new Error("Selecciona al menos un concepto"));
            return;
        }
        return post("/compra/orden_xml_descartar_conceptos_erp", {
            id_orden_compra: document.getElementById("orden_id").value,
            conceptos: seleccion.conceptos
        }).then(function (r) {
            if (r.error) { throw new Error(r.mensaje); }
            cargarDocumentosXml();
            return Swal.fire({text: r.mensaje, icon: r.tipo === "warning" ? "warning" : "success", confirmButtonText: "Aceptar"});
        }).catch(alertaError);
    }
function obtenerIndicesItemsSeleccionados() {
    var seleccionados = [];
    var vistos = {};
    document.querySelectorAll("[data-item-check]").forEach(function (x) {
        if (x.checked) {
            var uid = x.getAttribute("data-item-uid") || "";
            var idx = Number(x.getAttribute("data-index"));
            if (!Number.isNaN(idx) && items[idx] && items[idx]._uid === uid) {
                if (!vistos[idx]) {
                    seleccionados.push(idx);
                    vistos[idx] = true;
                }
                return;
            }
            if (uid) {
                for (var i = 0; i < items.length; i++) {
                    if ((items[i]._uid || "") === uid && !vistos[i]) {
                        seleccionados.push(i);
                        vistos[i] = true;
                        return;
                    }
                }
            }
        }
    });
    return seleccionados;
}
    function alternarAccionesItems() {
        if (!puedeEditar) { return; }
        var seleccionados = obtenerIndicesItemsSeleccionados();
        var haySeleccion = seleccionados.length > 0;
        var total = items.length;
        var tieneItems = total > 0;
        var checkAll = document.getElementById("orden_items_check_all");
        if (checkAll) { checkAll.checked = total > 0 && haySeleccion && seleccionados.length === total; }
        document.getElementById("orden_items_eliminar").classList.toggle("d-none", !haySeleccion);
        var descuentoMasivo = document.getElementById("orden_items_descuento_masivo");
        if (descuentoMasivo) {
            descuentoMasivo.disabled = !tieneItems;
            if (!tieneItems) {
                descuentoMasivo.value = "0";
            }
        }
        var costoSinImpuestos = document.getElementById("orden_modo_costo_sin_impuestos");
        var costoConImpuestos = document.getElementById("orden_modo_costo_con_impuestos");
        if (costoSinImpuestos && costoConImpuestos) {
            costoSinImpuestos.disabled = !tieneItems;
            costoConImpuestos.disabled = !tieneItems;
            if (!tieneItems) {
                modoCostoCaptura = "sin_impuestos";
                costoSinImpuestos.checked = true;
                costoConImpuestos.checked = false;
            }
        }
    }
    function aplicarDescuentoMasivo() {
        // [Codex: v2026.06.16] Descuento de encabezado: aplica a todas las partidas para reducir pasos de captura.
        if (!items.length) {
            return;
        }
        var descuentoPct = parseDecimal(document.getElementById("orden_items_descuento_masivo").value);
        if (descuentoPct < 0 || descuentoPct > 100) {
            alertaError(new Error("El descuento debe estar entre 0 y 100"));
            return;
        }
        var partidas = [];
        items.forEach(function (item) {
            if (!item) { return; }
            var descuentoAnterior = parseDecimal(item.descuento);
            var costoBase = parseDecimal(item.cantidad) * costoUnitarioNeto(item);
            var descuentoNuevo = Math.max(0, costoBase * (descuentoPct / 100));
            item.descuento = descuentoNuevo;
            partidas.push({
                uid: uidItem(item),
                id_detalle: Number(item.id_detalle || 0),
                id_sku_erp: Number(item.id_sku_erp || 0),
                sku: item.sku || item.sku_proveedor || "",
                nombre: item.nombre || "",
                costo_base: Number(costoBase.toFixed(6)),
                descuento_anterior: Number(descuentoAnterior.toFixed(6)),
                descuento_nuevo: Number(descuentoNuevo.toFixed(6))
            });
        });
        eventosDescuentoMasivo.push({
            fecha_cliente: new Date().toISOString(),
            porcentaje: descuentoPct,
            motivo: descuentoPct > 0 ? "Descuento capturado en encabezado" : "Descuento removido desde encabezado",
            partidas: partidas
        });
        render();
    }
    function programarDescuentoMasivo() {
        clearTimeout(timerDescuentoMasivo);
        timerDescuentoMasivo = setTimeout(aplicarDescuentoMasivo, 350);
    }
    function cambiarModoCostoCaptura(modo) {
        modoCostoCaptura = modo === "con_impuestos" ? "con_impuestos" : "sin_impuestos";
        for (var i = 0; i < items.length; i++) {
            if (items[i]) {
                if (modoCostoCaptura === "con_impuestos") {
                    items[i].costo_unitario = Number(costoUnitarioConImpuestos(items[i]).toFixed(6));
                    items[i].costo_unitario_incluye_impuesto = true;
                } else {
                    items[i].costo_unitario = Number(costoUnitarioNeto(items[i]).toFixed(6));
                    items[i].costo_unitario_incluye_impuesto = false;
                }
            }
        }
        render();
    }
    function sincronizarModoCostoEncabezado() {
        var costoSinImpuestos = document.getElementById("orden_modo_costo_sin_impuestos");
        var costoConImpuestos = document.getElementById("orden_modo_costo_con_impuestos");
        if (!costoSinImpuestos || !costoConImpuestos) { return; }
        costoSinImpuestos.checked = modoCostoCaptura === "sin_impuestos";
        costoConImpuestos.checked = modoCostoCaptura === "con_impuestos";
    }
    function ajustarCantidadItem(uid, delta) {
        // [Codex: v2026.06.16] Stepper operativo de cantidad: botones +/- con paso 1 y minimo positivo.
        var item = items.find(function (x) { return (x._uid || "") === uid; });
        if (!item || !puedeEditar) { return; }
        var actual = parseDecimal(item.cantidad);
        var siguiente = actual + delta;
        item.cantidad = Number(Math.max(0.000001, siguiente).toFixed(6));
        render();
    }
function eliminarProductosSeleccionados() {
        // [Codex: v2026.06.07] Borrado masivo de partidas seleccionadas en la tabla.
        var indiceSeleccionados = obtenerIndicesItemsSeleccionados();
        if (!indiceSeleccionados.length) {
            alertaError(new Error("Selecciona al menos un producto para eliminar"));
            return;
        }
    indiceSeleccionados.sort(function (a, b) { return b - a; }).forEach(function (idx) {
        if (idx >= 0 && idx < items.length) {
            items.splice(idx, 1);
        }
    });
        render();
    }
    function cargarAdjuntos() {
        var id = Number(document.getElementById("orden_id").value || 0);
        if (!id) { return; }
        fetch("/compra/orden_adjuntos_listar_erp?id_orden_compra=" + id, {credentials: "same-origin"})
            .then(function (r) { return r.json(); }).then(function (r) {
                if (r.error) { throw new Error(r.mensaje); }
                document.getElementById("orden_adjuntos").innerHTML = (r.depurar || []).map(function (x) {
                    var activo = x.estatus === "activo";
                    var base = "/compra/orden_adjunto_archivo_erp?" + new URLSearchParams({
                        id_orden_compra: id,
                        id_adjunto_orden: x.id_adjunto_orden
                    });
                    var esImagen = /^image\//.test(String(x.archivo_tipo || ""));
                    var vistaArchivo = "<div class=\"fw-bold\">" + esc(x.archivo_nombre) +
                        "</div><div class=\"text-muted fs-8\">" + esc(tamanoArchivo(x.archivo_tamano)) + "</div>";
                    if (activo && esImagen) {
                        vistaArchivo = "<div class=\"d-flex align-items-center gap-3\">" +
                            "<a href=\"" + base + "\" target=\"_blank\" rel=\"noopener\" title=\"Vista previa\">" +
                            "<img src=\"" + base + "\" class=\"rounded border\" style=\"width:52px;height:52px;object-fit:cover\" alt=\"Adjunto\"></a>" +
                            "<div>" + vistaArchivo + "</div></div>";
                    }
                    var acciones = activo
                        ? "<a class=\"btn btn-sm btn-icon btn-light-primary me-2\" href=\"" + base +
                            "\" target=\"_blank\" rel=\"noopener\" title=\"Vista previa\"><i class=\"bi bi-eye\"></i></a>" +
                            "<a class=\"btn btn-sm btn-icon btn-light me-2\" href=\"" + base +
                            "&descargar=1\" title=\"Descargar\"><i class=\"bi bi-download\"></i></a>"
                        : "";
                    if (puedeGestionarAdjuntos && activo && modo === "editar" && estatusOrden !== "cancelada") {
                        acciones += "<button type=\"button\" class=\"btn btn-sm btn-light-danger\" data-cancelar-adjunto=\"" +
                            x.id_adjunto_orden + "\">Cancelar</button>";
                    }
                    return "<tr><td>" + esc(etiquetaDocumento(x.tipo_documento)) +
                        "</td><td>" + esc(x.referencia || "-") +
                        "</td><td>" + vistaArchivo +
                        "</td><td>" + esc(x.observaciones || "-") +
                        "</td><td>" + esc((x.fecha_registro || "").slice(0, 16)) +
                        "</td><td><span class=\"badge " + (activo ? "badge-light-success" : "badge-light-danger") +
                        "\">" + esc(x.estatus) + "</span></td><td class=\"text-end text-nowrap\">" +
                        acciones + "</td></tr>";
                }).join("") || "<tr><td colspan=\"7\" class=\"text-center text-muted py-6\">Sin archivos adjuntos</td></tr>";
                document.getElementById("orden_adjunto_formulario").classList.toggle(
                    "d-none", !puedeGestionarAdjuntos || modo === "ver" || estatusOrden === "cancelada"
                );
            }).catch(alertaError);
    }
    function subirAdjunto() {
        // [Codex: v2026.06.07] Adjuntos y documentos de compra (sin importación automática desde aquí).
        var archivo = document.getElementById("orden_adjunto_archivo").files[0];
        if (!archivo) {
            alertaError(new Error("Selecciona el archivo que deseas adjuntar"));
            return;
        }
        asegurarOrdenBorrador("subir un adjunto", { requiereItems: false }).then(function (idOrden) {
            var data = new FormData();
            data.append("id_orden_compra", String(idOrden));
            data.append("tipo_documento", document.getElementById("orden_adjunto_tipo").value);
            data.append("referencia", document.getElementById("orden_adjunto_referencia").value);
            data.append("observaciones", document.getElementById("orden_adjunto_observaciones").value);
            data.append("archivo", archivo);
            var boton = document.getElementById("orden_adjunto_subir");
            boton.disabled = true;
            return fetch("/compra/orden_adjunto_subir_erp", {
                method: "POST", body: data, credentials: "same-origin"
            }).then(function (r) { return r.json(); }).then(function (r) {
                if (r.error) { throw new Error(r.mensaje); }
                document.getElementById("orden_adjunto_archivo").value = "";
                document.getElementById("orden_adjunto_referencia").value = "";
                document.getElementById("orden_adjunto_observaciones").value = "";
                document.getElementById("orden_adjunto_tipo").value = "cotizacion";
                cargarAdjuntos();
                return Swal.fire({text: r.mensaje, icon: "success", confirmButtonText: "Aceptar"});
            }).finally(function () {
                boton.disabled = false;
            });
        }).catch(alertaError);
    }
    function cargarDocumentosXml() {
        var id = Number(document.getElementById("orden_id").value || 0);
        if (!id) { return; }
        fetch("/compra/orden_xml_listar_erp?id_orden_compra=" + id, {credentials: "same-origin"})
            .then(function (r) { return r.json(); }).then(function (r) {
                document.getElementById("orden_xml_documentos").innerHTML = (r.depurar || []).map(function (x) {
                    return "<tr><td><div class=\"fw-bold\">" + esc(x.uuid || x.folio || "-") +
                        "</div><div class=\"text-muted fs-8\">" + esc(x.fecha_emision || "") +
                        "</div></td><td>" + esc(x.nombre_emisor || x.rfc_emisor || "-") +
                        "</td><td>" + esc(x.conceptos) + "</td><td>" + esc(x.coincidencias || 0) +
                        "</td><td>" + esc(x.sin_coincidencia || 0) +
                        "</td><td class=\"text-end fw-bold\">$" + Number(x.total || 0).toFixed(2) +
                        "</td><td><span class=\"badge badge-light-primary\">" + esc(x.estatus_conciliacion) + "</span></td></tr>";
                }).join("") || "<tr><td colspan=\"7\" class=\"text-center text-muted py-6\">Sin documentos fiscales</td></tr>";
                cargarConciliacionXml();
            }).catch(alertaError);
    }
    function cargarDiferenciasSolicitud() {
        var id = Number(document.getElementById("orden_id").value || 0);
        if (!id) {
            return Promise.resolve(null);
        }
        return fetch("/compra/orden_diferencias_solicitud_erp?id_orden_compra=" + id, {credentials: "same-origin"})
            .then(function (r) { return r.json(); })
            .then(function (r) {
                if (r.error) {
                    return r;
                }
                var data = r.depurar || {};
                var resumen = data.resumen || {};
                var faltantes = data.faltantes || [];
                var adicionales = data.adicionales || [];
                var cambios = data.cambios || [];
                document.getElementById("orden_dif_resumen_faltantes").textContent = String(resumen.faltantes || 0);
                document.getElementById("orden_dif_resumen_adicionales").textContent = String(resumen.adicionales || 0);
                document.getElementById("orden_dif_resumen_cambios").textContent = String(resumen.cambios || 0);
                document.getElementById("orden_dif_faltantes").innerHTML = renderizarDiferenciasFila(faltantes, renderFilaDiferenciaSimple) ||
                    "<tr><td colspan=\"4\" class=\"text-center text-muted py-5\">Sin faltantes</td></tr>";
                document.getElementById("orden_dif_adicionales").innerHTML = renderizarDiferenciasFila(adicionales, renderFilaDiferenciaSimple) ||
                    "<tr><td colspan=\"4\" class=\"text-center text-muted py-5\">Sin adicionales</td></tr>";
                document.getElementById("orden_dif_cambios").innerHTML = renderizarDiferenciasFila(cambios, renderFilaDiferenciaCambio) ||
                    "<tr><td colspan=\"4\" class=\"text-center text-muted py-5\">Sin cambios</td></tr>";
                return r;
            });
    }
    function cargarConciliacionXml() {
        var id = Number(document.getElementById("orden_id").value || 0);
        if (!id) { return; }
        fetch("/compra/orden_xml_conciliacion_erp?id_orden_compra=" + id, {credentials: "same-origin"})
            .then(function (r) { return r.json(); }).then(function (r) {
                if (r.error) { throw new Error(r.mensaje); }
                var data = r.depurar || {};
                var conceptos = data.conceptos || [];
                var detalle = data.detalle_orden || [];
                var pendientes = data.pendientes || [];
                var resumen = data.resumen || {};
                var seccion = document.getElementById("orden_conciliacion_seccion");
                seccion.classList.toggle("d-none", conceptos.length === 0 && pendientes.length === 0);
                document.getElementById("orden_conciliacion_total").textContent = resumen.conceptos || 0;
                document.getElementById("orden_conciliacion_coincidencias").textContent = resumen.coincidencias || 0;
                document.getElementById("orden_conciliacion_revision").textContent = resumen.requieren_revision || 0;
                document.getElementById("orden_conciliacion_faltantes").textContent = resumen.esperados_no_incluidos || 0;

                document.getElementById("orden_conciliacion_conceptos").innerHTML = conceptos.map(function (x) {
                    var puedeAccion = puedeEditar && x.resultado_conciliacion !== "coincidencia_manual" &&
                        x.resultado_conciliacion !== "coincidencia_exacta";
                    var filaSeleccion = "<td><input type=\"checkbox\" class=\"form-check-input\" data-concepto-check=\"" +
                        esc(x.id_documento_concepto) + "\" value=\"" + esc(x.id_documento_concepto) + "\"" +
                        (puedeAccion ? "" : " disabled") + "></td>";
                    var relacionado = x.id_orden_detalle
                        ? "<div class=\"fw-bold\">" + esc(x.sku_orden || "") + "</div><div class=\"text-muted fs-8\">" + esc(x.nombre_orden || "") + "</div>"
                        : "<span class=\"text-muted\">Sin relacion</span>";
                    var diferencias = x.id_orden_detalle
                        ? "<div>Cant. " + Number(x.diferencia_cantidad || 0).toFixed(6) +
                            "</div><div class=\"text-muted fs-8\">Costo $" + Number(x.diferencia_costo || 0).toFixed(6) + "</div>"
                        : "-";
                    var resuelto = x.resultado_conciliacion === "coincidencia_exacta" ||
                        x.resultado_conciliacion === "coincidencia_manual";
                    var accion = "";
                    if (puedeAccion) {
                        accion = "<div class=\"d-flex gap-2\"><select class=\"form-select form-select-sm\" data-concepto-select=\"" +
                            x.id_documento_concepto + "\"><option value=\"\">Relacionar con partida</option>" +
                            detalle.map(function (d) {
                                return "<option value=\"" + esc(d.id_detalle) + "\">" +
                                    esc(d.sku + " - " + d.nombre_producto) + "</option>";
                            }).join("") + "</select><button type=\"button\" class=\"btn btn-sm btn-light-primary\" data-resolver-concepto=\"" +
                            x.id_documento_concepto + "\" title=\"Relacionar\"><i class=\"bi bi-link-45deg\"></i></button></div>";
                    }
                    return "<tr>" + filaSeleccion + "<td><div class=\"fw-bold\">" + esc(x.no_identificacion || "-") +
                        "</div><div class=\"text-muted fs-8\">" + esc(x.descripcion) +
                        "</div></td><td class=\"text-end\">" + Number(x.cantidad_xml || 0).toFixed(6) +
                        "</td><td class=\"text-end\">$" + Number(x.costo_xml || 0).toFixed(6) +
                        "</td><td>" + relacionado + "</td><td>" + diferencias +
                        "</td><td><span class=\"badge " + (resuelto ? "badge-light-success" : "badge-light-warning") +
                        "\">" + esc(x.resultado_conciliacion) + "</span></td><td>" + accion + "</td></tr>";
                }).join("") || "<tr><td colspan=\"8\" class=\"text-center text-muted py-6\">Sin conceptos para conciliar</td></tr>";
                alternarAccionesConciliacion();

                document.getElementById("orden_conciliacion_pendientes").innerHTML = pendientes.map(function (x) {
                    return "<tr><td class=\"fw-bold\">" + esc(x.sku || "-") +
                        "</td><td>" + esc(x.nombre_producto) +
                        "</td><td class=\"text-end\">" + Number(x.cantidad_solicitada || 0).toFixed(6) +
                        "</td><td class=\"text-end\">" + Number(x.cantidad_comprada || 0).toFixed(6) +
                        "</td><td>" + esc(x.motivo) +
                        "</td><td><span class=\"badge " + (x.estatus === "resuelto" ? "badge-light-success" : "badge-light-warning") +
                        "\">" + esc(x.estatus) + "</span></td></tr>";
                }).join("") || "<tr><td colspan=\"6\" class=\"text-center text-muted py-6\">Sin productos pendientes</td></tr>";
            }).catch(alertaError);
    }
    function deshabilitar() {
        document.querySelectorAll(".orden-edicion").forEach(function (x) { x.classList.add("d-none"); });
        ["orden_almacen", "orden_fecha_entrega", "orden_folio_proveedor", "orden_moneda", "orden_tipo_cambio", "orden_contacto", "orden_telefono", "orden_direccion", "orden_observaciones"].forEach(function (id) {
            document.getElementById(id).disabled = true;
        });
    }
function render() {
        // [Codex: v2026.06.07] Render y totales de partidas.
        var subtotal = 0;
        var impuestos = 0;
        var total = 0;
        var filas = items.map(function (x, i) {
            var costoNeto = costoUnitarioNeto(x);
            var costoConImpuestos = costoUnitarioConImpuestos(x);
            var bruto = Number(x.cantidad || 0) * Number(costoNeto || 0);
            var sub = Math.max(0, bruto - Number(x.descuento || 0));
            var imp = sub * Number(x.porcentaje_impuesto || 0) / 100;
            subtotal += sub;
            impuestos += imp;
            total += sub + imp;
            var disabled = puedeEditar ? "" : " disabled";
            var disabledCostoSin = puedeEditar && modoCostoCaptura === "sin_impuestos" ? "" : " disabled";
            var disabledCostoCon = puedeEditar && modoCostoCaptura === "con_impuestos" ? "" : " disabled";
            var uiUid = uidItem(x);
            var registroBadge = etiquetaRegistroProducto(x);
            var tipoItemControl = tipoItemControlHtml(x, i, disabled);
            var fiscalBadge = fiscalCompleto(x.datos_fiscales || {})
                ? "<span class=\"badge badge-light-success\">Fiscal completo</span>"
                : "<span class=\"badge badge-light-warning\">Fiscal pendiente</span>";
            var advertencias = advertenciasOperativasHtml(x);
            var evidenciaCosto = evidenciaCostoHtml(x);
            return "<tr><td class=\"text-center\"><input type=\"checkbox\" class=\"form-check-input\" data-item-check=\"" + i +
                "\" data-item-uid=\"" + uiUid + "\" value=\"" + i + "\"" + (puedeEditar ? "" : " disabled") + "></td><td><div class=\"d-flex align-items-start gap-3\">" +
                imagenSkuHtml(x, "mt-1") + "<div class=\"min-w-0\"><div class=\"fw-bold\">" +
                esc(x.sku) + "</div><div class=\"text-muted fs-8\">" + esc(x.sku_proveedor || "") +
                "</div>" + advertencias + "</div></div></td><td>" + esc(x.nombre) + "<div class=\"text-muted fs-8\">" + registroBadge + "</div><div class=\"text-muted fs-8\">" + esc(x.unidad || "") +
                "</div>" + evidenciaCosto + "</td><td>" + tipoItemControl + "</td><td><div class=\"input-group input-group-sm orden-cantidad-control\"><button class=\"btn btn-light\" type=\"button\" data-cantidad-ajuste=\"-1\" data-item-uid=\"" + uiUid + "\" title=\"Disminuir cantidad\"" + disabled +
                ">-</button><input class=\"form-control text-center\" min=\"0.000001\" step=\"1\" type=\"number\" data-item=\"cantidad\" data-index=\"" + i + "\" value=\"" + x.cantidad + "\"" + disabled +
                "><button class=\"btn btn-light\" type=\"button\" data-cantidad-ajuste=\"1\" data-item-uid=\"" + uiUid + "\" title=\"Aumentar cantidad\"" + disabled +
                ">+</button></div></td><td><input class=\"form-control form-control-sm text-end orden-sin-spinner\" type=\"number\" min=\"0\" step=\"0.000001\" data-item=\"costo_sin_impuestos\" data-index=\"" + i + "\" value=\"" + costoNeto.toFixed(6) + "\"" + disabledCostoSin +
                "></td><td><input class=\"form-control form-control-sm text-end orden-sin-spinner\" type=\"number\" min=\"0\" step=\"0.000001\" data-item=\"costo_con_impuestos\" data-index=\"" + i + "\" value=\"" + costoConImpuestos.toFixed(6) + "\"" + disabledCostoCon +
                "></td><td><input class=\"form-control form-control-sm text-end orden-sin-spinner\" type=\"number\" min=\"0\" max=\"100\" step=\"0.000001\" data-item=\"porcentaje_impuesto\" data-index=\"" + i + "\" value=\"" + x.porcentaje_impuesto + "\"" + disabled +
                "></td><td><input class=\"form-control form-control-sm text-end orden-sin-spinner\" type=\"number\" min=\"0\" step=\"0.000001\" data-item=\"descuento\" data-index=\"" + i + "\" value=\"" + x.descuento + "\"" + disabled +
                "></td><td class=\"text-center\">" + fiscalBadge + "<div class=\"mt-1\"><button type=\"button\" class=\"btn btn-sm btn-light-primary\" data-fiscal=\"" +
                uiUid + "\">" + (puedeEditar ? "Editar fiscal" : "Ver fiscal") + "</button></div></td><td class=\"text-end fw-bold\">$" + (sub + imp).toFixed(2) +
                "</td><td class=\"orden-edicion text-end" + (puedeEditar ? "" : " d-none") +
                "\"><button type=\"button\" class=\"btn btn-sm btn-icon btn-light-danger\" data-quitar=\"" + uiUid + "\" title=\"Quitar\"><i class=\"bi bi-trash\"></i></button></td></tr>";
        }).join("") || "<tr><td colspan=\"12\" class=\"text-center text-muted py-8\">Agrega productos del proveedor</td></tr>";
        document.getElementById("orden_items").innerHTML = filas;
        renderResumenCatalogoFiscales();
        renderResumenFiscales();
        document.getElementById("orden_subtotal").textContent = "$" + subtotal.toFixed(2);
        document.getElementById("orden_impuestos").textContent = "$" + impuestos.toFixed(2);
        document.getElementById("orden_total").textContent = "$" + total.toFixed(2);
        sincronizarModoCostoEncabezado();
        alternarAccionesItems();
    }
    function abrirFiscalModal(uid) {
        var item = items.find(function (x) { return (x._uid || "") === uid; });
        if (!item) { return; }
        document.getElementById("orden_fiscal_item_uid").value = uid;
        var f = normalizarDatosFiscales(item.datos_fiscales || {});
        document.getElementById("orden_fiscal_clave_sat").value = f.clave_sat || "";
        document.getElementById("orden_fiscal_clave_unidad_sat").value = f.clave_unidad_sat || "";
        document.getElementById("orden_fiscal_unidad").value = f.unidad || "";
        document.getElementById("orden_fiscal_objeto").value = f.objeto_impuesto || "";
        document.getElementById("orden_fiscal_tipo").value = f.tipo_impuesto || "";
        document.getElementById("orden_fiscal_iva").value = f.porcentaje_iva || 0;
        document.getElementById("orden_fiscal_ieps").value = f.porcentaje_ieps || 0;
        document.getElementById("orden_fiscal_incluye_iva").checked = parseFlag(f.incluye_iva);
        document.getElementById("orden_fiscal_requiere_factura").checked = parseFlag(f.requiere_factura);
        document.getElementById("orden_fiscal_producto").textContent = esc(item.nombre || "Producto");
        document.getElementById("orden_fiscal_producto").textContent += " · SKU: " + esc(item.sku || item.sku_proveedor || "-");
        camposFiscalModal().forEach(function (idCampo) {
            var campo = document.getElementById(idCampo);
            if (campo) { campo.disabled = !puedeEditar; }
        });
        document.getElementById("orden_fiscal_guardar").classList.toggle("d-none", !puedeEditar);
        var modal = bootstrap.Modal.getOrCreateInstance(document.getElementById("orden_fiscal_modal"));
        modal.show();
    }
    function guardarFiscalModal() {
        if (!puedeEditar) { return; }
        var uid = document.getElementById("orden_fiscal_item_uid").value;
        var item = items.find(function (x) { return (x._uid || "") === uid; });
        if (!item) { return; }
        item.datos_fiscales = normalizarDatosFiscales({
            clave_sat: document.getElementById("orden_fiscal_clave_sat").value,
            clave_unidad_sat: document.getElementById("orden_fiscal_clave_unidad_sat").value,
            unidad: document.getElementById("orden_fiscal_unidad").value,
            objeto_impuesto: document.getElementById("orden_fiscal_objeto").value,
            tipo_impuesto: document.getElementById("orden_fiscal_tipo").value,
            porcentaje_iva: document.getElementById("orden_fiscal_iva").value,
            porcentaje_ieps: document.getElementById("orden_fiscal_ieps").value,
            incluye_iva: document.getElementById("orden_fiscal_incluye_iva").checked,
            requiere_factura: document.getElementById("orden_fiscal_requiere_factura").checked
        });
        render();
        bootstrap.Modal.getInstance(document.getElementById("orden_fiscal_modal")).hide();
    }
    function datos(estatus) {
        var itemsPayload = items.map(function (item) {
            var copia = Object.assign({}, item);
            copia.evidencia_costo = {
                id_costo_proveedor_sku: Number(item.id_costo_proveedor_sku || 0),
                id_lista_proveedor_erp: Number(item.id_lista_proveedor_erp || 0),
                fuente_costo: item.fuente_costo || "",
                origen_costo: item.origen_costo || "",
                moneda_costo: item.moneda_costo || "",
                vigencia_desde: item.vigencia_desde || "",
                vigencia_hasta: item.vigencia_hasta || ""
            };
            return copia;
        });
        return {id_orden_compra: document.getElementById("orden_id").value,
            id_proveedor: document.getElementById("orden_proveedor_selector").value,
            id_almacen_destino: document.getElementById("orden_almacen").value,
            fecha_entrega_estimada: document.getElementById("orden_fecha_entrega").value,
            folio_proveedor: document.getElementById("orden_folio_proveedor").value,
            moneda: document.getElementById("orden_moneda").value,
            tipo_cambio: document.getElementById("orden_tipo_cambio").value,
            contacto_recepcion: document.getElementById("orden_contacto").value,
            telefono_recepcion: document.getElementById("orden_telefono").value,
            direccion_entrega: document.getElementById("orden_direccion").value,
            observaciones: document.getElementById("orden_observaciones").value,
            estatus: estatus,
            descuento_masivo_eventos: JSON.stringify(eventosDescuentoMasivo),
            items: JSON.stringify(itemsPayload)};
    }
    function advertenciasRequierenConfirmacionOrden() {
        var codigos = {sin_costo_vigente: true, unidad_factor_incompleto: true};
        var hallazgos = [];
        items.forEach(function (item) {
            var advertencias = Array.isArray(item && item.advertencias_operativas) ? item.advertencias_operativas : [];
            advertencias.forEach(function (advertencia) {
                if (!codigos[advertencia.codigo]) { return; }
                hallazgos.push({
                    sku: item.sku || item.sku_proveedor || "",
                    nombre: item.nombre || "",
                    mensaje: advertencia.mensaje || advertencia.codigo || "Revision operativa"
                });
            });
        });
        return hallazgos;
    }
    function confirmarAdvertenciasOrden(estatus) {
        if (estatus !== "enviada") {
            return Promise.resolve(true);
        }
        var hallazgos = advertenciasRequierenConfirmacionOrden();
        if (!hallazgos.length) {
            return Promise.resolve(true);
        }
        var lista = hallazgos.slice(0, 8).map(function (x) {
            return "<li><strong>" + esc(x.sku || "-") + "</strong> " + esc(x.nombre || "") + ": " + esc(x.mensaje) + "</li>";
        }).join("");
        if (hallazgos.length > 8) {
            lista += "<li>Y " + (hallazgos.length - 8) + " advertencias mas.</li>";
        }
        return Swal.fire({
            title: "Revisar antes de enviar",
            html: "<div class=\"text-start\"><p>La orden tiene advertencias operativas que no bloquean, pero conviene validar:</p><ul>" + lista + "</ul></div>",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Enviar de todos modos",
            cancelButtonText: "Volver a revisar"
        }).then(function (r) {
            return !!r.isConfirmed;
        });
    }
    function guardar(estatus) {
        confirmarAdvertenciasOrden(estatus).then(function (confirmado) {
            if (!confirmado) { return; }
            post("/compra/orden_guardar_erp", datos(estatus)).then(function (r) {
                if (r.error) { throw new Error(r.mensaje); }
                var advertenciasServidor = r.depurar && Array.isArray(r.depurar.advertencias_operativas) ? r.depurar.advertencias_operativas : [];
                var htmlAdvertencias = "";
                if (advertenciasServidor.length) {
                    htmlAdvertencias = "<div class=\"text-start mt-3\"><div class=\"fw-bold mb-2\">Advertencias registradas</div><ul>" +
                        advertenciasServidor.slice(0, 8).map(function (x) {
                            return "<li><strong>" + esc(x.sku || "-") + "</strong> " + esc(x.nombre || "") + ": " + esc(x.mensaje || x.codigo || "Revision") + "</li>";
                        }).join("") + (advertenciasServidor.length > 8 ? "<li>Y " + (advertenciasServidor.length - 8) + " advertencias mas.</li>" : "") + "</ul></div>";
                }
                return Swal.fire({html: esc(r.mensaje) + htmlAdvertencias, icon: r.tipo === "warning" ? "warning" : "success", confirmButtonText: "Aceptar"}).then(function () {
                    window.location.href = estatus === "borrador"
                        ? "/compra/editar_orden_compra/" + r.depurar.id_orden_compra
                        : "/compra/mostrar_compra_ordenes";
                });
            }).catch(alertaError);
        }).catch(alertaError);
    }
    function abrirDiferenciasSolicitud() {
        var id = Number(document.getElementById("orden_id").value || 0);
        if (!id) { return; }
        cargarDiferenciasSolicitud().then(function (r) {
            if (r && r.error) {
                throw new Error(r.mensaje || "No se pudo consultar diferencias");
            }
            bootstrap.Modal.getOrCreateInstance(document.getElementById("orden_diferencias_modal")).show();
        }).catch(alertaError);
    }
    function alertaError(e) { Swal.fire({text: e.message || String(e), icon: "error", confirmButtonText: "Aceptar"}); }
    document.addEventListener("DOMContentLoaded", function () {
        modo = document.getElementById("orden_modo").value;
        puedeEditar = modo === "editar";
        puedeAprobar = document.getElementById("orden_puede_aprobar").value === "1";
        puedeCancelar = document.getElementById("orden_puede_cancelar").value === "1";
        puedeVerFinanzas = document.getElementById("orden_puede_ver_finanzas").value === "1";
        puedeOperarFinanzas = document.getElementById("orden_puede_operar_finanzas").value === "1";
        puedeGestionarAdjuntos = document.getElementById("orden_puede_gestionar_adjuntos").value === "1";
        if (!puedeAprobar) {
            document.getElementById("orden_enviar").classList.add("d-none");
        }
        var fechaLocal = new Date();
        var hoy = fechaLocal.getFullYear() + "-" +
            String(fechaLocal.getMonth() + 1).padStart(2, "0") + "-" +
            String(fechaLocal.getDate()).padStart(2, "0");
        document.getElementById("orden_pago_fecha").value = hoy;
        document.getElementById("orden_nota_fecha").value = hoy;
        cargarCatalogos().then(cargarOrden).catch(alertaError);
        document.getElementById("orden_proveedor_selector").addEventListener("change", function () {
            items = [];
            document.getElementById("orden_buscar_sku").value = "";
            document.getElementById("orden_resultados").classList.add("d-none");
            render();
        });
        document.getElementById("orden_buscar_sku").addEventListener("input", function () {
            clearTimeout(timerBusqueda);
            timerBusqueda = setTimeout(buscarSku, 250);
        });
        document.getElementById("orden_resultados").addEventListener("click", function (e) {
            var b = e.target.closest("[data-sku]");
            if (b) { agregarSku(JSON.parse(decodeURIComponent(b.getAttribute("data-sku")))); }
        });
        document.getElementById("orden_xml_cargar").addEventListener("click", cargarXmlDesdeProductos);
        document.getElementById("orden_cargo_agregar").addEventListener("click", agregarCargoNoInventariable);
        document.getElementById("orden_producto_nuevo_agregar").addEventListener("click", agregarProductoNuevoPendiente);
        document.getElementById("orden_items_check_all").addEventListener("change", function () {
            var activo = this.checked;
            document.querySelectorAll("[data-item-check]").forEach(function (x) { x.checked = activo; });
            alternarAccionesItems();
        });
        document.getElementById("orden_almacen").addEventListener("change", function () {
            var o = this.selectedOptions[0];
            if (!o || !this.value) { return; }
            document.getElementById("orden_contacto").value = o.getAttribute("data-contacto") || "";
            document.getElementById("orden_telefono").value = o.getAttribute("data-telefono") || "";
            document.getElementById("orden_direccion").value = o.getAttribute("data-direccion") || "";
        });
    document.getElementById("orden_items").addEventListener("change", function (e) {
        var campo = e.target.getAttribute("data-item");
        var index = e.target.getAttribute("data-index");
        if (campo && index != null) {
            var item = items[Number(index)];
            if (!item) { return; }
            if (campo === "tipo_item") {
                item.tipo_item = e.target.value;
                if (esTipoItemNoInventariable(item.tipo_item)) {
                    item.requiere_revision = 0;
                } else if (parseInt(item.id_sku_erp || 0, 10) <= 0) {
                    item.tipo_item = "producto_nuevo";
                    item.requiere_revision = 1;
                }
            } else if (campo === "costo_sin_impuestos") {
                item.costo_unitario = parseDecimal(e.target.value);
                item.costo_unitario_incluye_impuesto = false;
            } else if (campo === "costo_con_impuestos") {
                item.costo_unitario = parseDecimal(e.target.value);
                item.costo_unitario_incluye_impuesto = true;
            } else {
                item[campo] = parseDecimal(e.target.value);
            }
            render();
            return;
            }
        if (e.target.matches("[data-item-check]")) {
            alternarAccionesItems();
        }
        if (e.target.matches("[data-fiscal-item]")) {
            abrirFiscalModal(e.target.getAttribute("data-fiscal-item"));
        }
    });
    document.getElementById("orden_items").addEventListener("click", function (e) {
        var botonCantidad = e.target.closest("[data-cantidad-ajuste]");
        if (botonCantidad) {
            ajustarCantidadItem(botonCantidad.getAttribute("data-item-uid"), Number(botonCantidad.getAttribute("data-cantidad-ajuste") || 0));
            return;
        }
        var b = e.target.closest("[data-quitar]");
        if (!b) { return; }
        var uid = b.getAttribute("data-quitar");
        var idx = -1;
        for (var i = 0; i < items.length; i++) {
            if ((items[i]._uid || "") === uid) { idx = i; break; }
        }
        if (idx >= 0) {
            items.splice(idx, 1);
            render();
        }
    });
    document.getElementById("orden_items").addEventListener("click", function (e) {
        var b = e.target.closest("[data-fiscal]");
        if (!b) { return; }
        abrirFiscalModal(b.getAttribute("data-fiscal"));
    });
        document.getElementById("orden_items_eliminar").addEventListener("click", eliminarProductosSeleccionados);
        document.getElementById("orden_items_descuento_masivo").addEventListener("input", programarDescuentoMasivo);
        document.getElementById("orden_modo_costo_sin_impuestos").addEventListener("change", function () {
            if (!this.checked) {
                document.getElementById("orden_modo_costo_con_impuestos").checked = true;
                cambiarModoCostoCaptura("con_impuestos");
                return;
            }
            cambiarModoCostoCaptura("sin_impuestos");
        });
        document.getElementById("orden_modo_costo_con_impuestos").addEventListener("change", function () {
            if (!this.checked) {
                document.getElementById("orden_modo_costo_sin_impuestos").checked = true;
                cambiarModoCostoCaptura("sin_impuestos");
                return;
            }
            cambiarModoCostoCaptura("con_impuestos");
        });
        document.getElementById("orden_ver_diferencias").addEventListener("click", abrirDiferenciasSolicitud);
        document.getElementById("orden_fiscal_guardar").addEventListener("click", guardarFiscalModal);
        document.getElementById("orden_conciliacion_conceptos").addEventListener("click", function (e) {
            var boton = e.target.closest("[data-resolver-concepto]");
            if (!boton) { return; }
            var concepto = boton.getAttribute("data-resolver-concepto");
            var select = document.querySelector("[data-concepto-select=\"" + concepto + "\"]");
            if (!select || !select.value) {
                alertaError(new Error("Selecciona la partida de la orden que corresponde al concepto"));
                return;
            }
            post("/compra/orden_xml_resolver_concepto_erp", {
                id_orden_compra: document.getElementById("orden_id").value,
                id_documento_concepto: concepto,
                id_orden_detalle: select.value
            }).then(function (r) {
                if (r.error) { throw new Error(r.mensaje); }
                cargarDocumentosXml();
            }).catch(alertaError);
        });
        document.getElementById("orden_conciliacion_check_all").addEventListener("change", function () {
            var activo = this.checked;
            document.querySelectorAll("[data-concepto-check]:not(:disabled)").forEach(function (x) { x.checked = activo; });
            alternarAccionesConciliacion();
        });
        document.getElementById("orden_conciliacion_conceptos").addEventListener("change", function (e) {
            if (e.target.matches("[data-concepto-check]") || e.target.matches("[data-concepto-select]")) {
                alternarAccionesConciliacion();
            }
        });
        document.getElementById("orden_conciliacion_mover").addEventListener("click", moverConceptosSeleccionados);
        document.getElementById("orden_conciliacion_descartar").addEventListener("click", descartarConceptosSeleccionados);
        document.getElementById("orden_guardar").addEventListener("click", function () { guardar("borrador"); });
        document.getElementById("orden_enviar").addEventListener("click", function () { guardar("enviada"); });
        document.getElementById("orden_cancelar").addEventListener("click", function () {
            Swal.fire({text: "La orden quedara cancelada y la solicitud podra generar un reemplazo.", icon: "warning", showCancelButton: true, confirmButtonText: "Cancelar orden", cancelButtonText: "Conservar"}).then(function (x) {
                if (!x.isConfirmed) { return; }
                post("/compra/orden_cancelar_erp", {id_orden_compra: document.getElementById("orden_id").value}).then(function (r) {
                    if (r.error) { throw new Error(r.mensaje); }
                    window.location.href = "/compra/mostrar_compra_ordenes";
                }).catch(alertaError);
            });
        });
        document.getElementById("orden_pago_agregar").addEventListener("click", registrarPago);
        document.getElementById("orden_nota_agregar").addEventListener("click", registrarNotaCredito);
        document.getElementById("orden_pagos").addEventListener("click", function (e) {
            var boton = e.target.closest("[data-cancelar-pago]");
            if (!boton) { return; }
            post("/compra/orden_pago_cancelar_erp", {
                id_orden_compra: document.getElementById("orden_id").value,
                id_pago_orden: boton.getAttribute("data-cancelar-pago")
            }).then(function (r) {
                if (r.error) { throw new Error(r.mensaje); }
                cargarFinanzas();
            }).catch(alertaError);
        });
        document.getElementById("orden_notas_credito").addEventListener("click", function (e) {
            var boton = e.target.closest("[data-cancelar-nota]");
            if (!boton) { return; }
            post("/compra/orden_nota_credito_cancelar_erp", {
                id_orden_compra: document.getElementById("orden_id").value,
                id_nota_credito_orden: boton.getAttribute("data-cancelar-nota")
            }).then(function (r) {
                if (r.error) { throw new Error(r.mensaje); }
                cargarFinanzas();
            }).catch(alertaError);
        });
        document.getElementById("orden_adjunto_subir").addEventListener("click", subirAdjunto);
        document.getElementById("orden_adjuntos").addEventListener("click", function (e) {
            var boton = e.target.closest("[data-cancelar-adjunto]");
            if (!boton) { return; }
            Swal.fire({
                text: "El archivo fisico se eliminara, pero el movimiento permanecera en el historial.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Cancelar adjunto",
                cancelButtonText: "Conservar"
            }).then(function (resultado) {
                if (!resultado.isConfirmed) { return; }
                post("/compra/orden_adjunto_cancelar_erp", {
                    id_orden_compra: document.getElementById("orden_id").value,
                    id_adjunto_orden: boton.getAttribute("data-cancelar-adjunto")
                }).then(function (r) {
                    if (r.error) { throw new Error(r.mensaje); }
                    cargarAdjuntos();
                }).catch(alertaError);
            });
        });
        render();
    });
})();

