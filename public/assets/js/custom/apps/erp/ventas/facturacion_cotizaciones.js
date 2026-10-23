"use strict";
(function () {
    var estado = {cotizaciones: [], actual: null, empresa: {}, cfdi: null};

    function $(id) { return document.getElementById(id); }

    function esc(value) {
        var div = document.createElement("div");
        div.textContent = value == null ? "" : String(value);
        return div.innerHTML;
    }

    function numero(value) {
        var n = Number(String(value == null ? "0" : value).replace(",", "."));
        return Number.isFinite(n) ? n : 0;
    }

    function dinero(value, moneda) {
        return new Intl.NumberFormat("es-MX", {style: "currency", currency: moneda || "MXN"}).format(numero(value));
    }

    function catalogoFiscal(value, catalogo) {
        var codigo = String(value || "").trim();
        if (!codigo) { return "-"; }
        return (catalogo[codigo] || "Código fiscal") + " (" + codigo + ")";
    }

    function formaPago(value) {
        return catalogoFiscal(value, {
            "01": "Efectivo", "02": "Cheque nominativo", "03": "Transferencia electrónica de fondos",
            "04": "Tarjeta de crédito", "05": "Monedero electrónico", "06": "Dinero electrónico",
            "08": "Vales de despensa", "12": "Dación en pago", "13": "Pago por subrogación",
            "14": "Pago por consignación", "15": "Condonación", "17": "Compensación",
            "23": "Novación", "24": "Confusión", "25": "Remisión de deuda",
            "26": "Prescripción o caducidad", "27": "A satisfacción del acreedor",
            "28": "Tarjeta de débito", "29": "Tarjeta de servicios", "30": "Aplicación de anticipos",
            "31": "Intermediario pagos", "99": "Por definir"
        });
    }

    function metodoPago(value) {
        return catalogoFiscal(value, {"PUE": "Pago en una sola exhibición", "PPD": "Pago en parcialidades o diferido"});
    }

    function usoCfdi(value) {
        return catalogoFiscal(value, {
            "G01": "Adquisición de mercancías", "G02": "Devoluciones, descuentos o bonificaciones", "G03": "Gastos en general",
            "I01": "Construcciones", "I02": "Mobiliario y equipo de oficina por inversiones", "I03": "Equipo de transporte",
            "I04": "Equipo de cómputo y accesorios", "I05": "Dados, troqueles, moldes, matrices y herramental",
            "I06": "Comunicaciones telefónicas", "I07": "Comunicaciones satelitales", "I08": "Otra maquinaria y equipo",
            "D01": "Honorarios médicos, dentales y gastos hospitalarios", "D02": "Gastos médicos por incapacidad o discapacidad",
            "D03": "Gastos funerales", "D04": "Donativos", "D05": "Intereses reales pagados por créditos hipotecarios",
            "D06": "Aportaciones voluntarias al SAR", "D07": "Primas por seguros de gastos médicos",
            "D08": "Gastos de transportación escolar obligatoria", "D09": "Depósitos para ahorro y planes de pensiones",
            "D10": "Pagos por servicios educativos", "S01": "Sin efectos fiscales", "CP01": "Pagos", "CN01": "Nómina"
        });
    }

    function regimenFiscal(value) {
        return catalogoFiscal(value, {
            "601": "General de Ley Personas Morales", "603": "Personas Morales con Fines no Lucrativos",
            "605": "Sueldos y Salarios e Ingresos Asimilados a Salarios", "606": "Arrendamiento",
            "607": "Régimen de Enajenación o Adquisición de Bienes", "608": "Demás ingresos",
            "609": "Consolidación", "610": "Residentes en el Extranjero sin Establecimiento Permanente en México",
            "611": "Ingresos por Dividendos", "612": "Personas Físicas con Actividades Empresariales y Profesionales",
            "614": "Ingresos por intereses", "615": "Régimen de los ingresos por obtención de premios",
            "616": "Sin obligaciones fiscales", "620": "Sociedades Cooperativas de Producción",
            "621": "Régimen de Incorporación Fiscal", "622": "Actividades Agrícolas, Ganaderas, Silvícolas y Pesqueras",
            "623": "Opcional para Grupos de Sociedades", "624": "Coordinados",
            "625": "Actividades Empresariales mediante Plataformas Tecnológicas", "626": "Régimen Simplificado de Confianza",
            "629": "Regímenes Fiscales Preferentes y Empresas Multinacionales", "630": "Enajenación de acciones en bolsa de valores"
        });
    }

    function exportacion(value) {
        var etiquetas = {"01": "No aplica", "02": "Definitiva", "03": "Temporal", "04": "Definitiva con enajenación"};
        var codigo = String(value || "").trim();
        if (!codigo) { return "-"; }
        return (etiquetas[codigo] || "Exportación") + " (" + codigo + ")";
    }

    function urlVerificacionSat(data) {
        var uuid = String(data.uuid || "").trim();
        var rfcEmisor = String(data.rfc_emisor || "").trim();
        var rfcReceptor = String(data.rfc_receptor || "").trim();
        var total = String(data.total || "").trim();
        var sello = String(data.sello_cfdi || "").replace(/\s+/g, "");
        if (!uuid || !rfcEmisor || !rfcReceptor || !total || !sello) { return ""; }
        return "https://verificacfdi.facturaelectronica.sat.gob.mx/default.aspx?id=" + encodeURIComponent(uuid) + "&re=" + encodeURIComponent(rfcEmisor) + "&rr=" + encodeURIComponent(rfcReceptor) + "&tt=" + encodeURIComponent(total) + "&fe=" + encodeURIComponent(sello.slice(-8));
    }

    function qrSatUrl(data) {
        var satUrl = urlVerificacionSat(data);
        return satUrl ? "https://quickchart.io/qr?text=" + encodeURIComponent(satUrl) + "&size=180&margin=1" : "";
    }

    function tasasImpuestos(conceptos) {
        var tasas = [];
        (conceptos || []).forEach(function (item) {
            var iva = numero(item.iva_porcentaje);
            var ieps = numero(item.ieps_porcentaje);
            if (iva > 0 && tasas.indexOf("IVA " + iva + "%") < 0) { tasas.push("IVA " + iva + "%"); }
            if (ieps > 0 && tasas.indexOf("IEPS " + ieps + "%") < 0) { tasas.push("IEPS " + ieps + "%"); }
        });
        return tasas.length ? tasas.join(" · ") : "Según XML";
    }

    function alerta(mensaje, tipo) {
        $("fc_alerta").innerHTML = "<div class=\"alert alert-" + esc(tipo || "info") + "\">" + esc(mensaje) + "</div>";
    }

    function request(url, data) {
        var options = {credentials: "same-origin", headers: {"Accept": "application/json"}};
        if (data) {
            options.method = "POST";
            options.headers["Content-Type"] = "application/x-www-form-urlencoded; charset=UTF-8";
            options.headers["X-CSRF-Token"] = window.ERP_CSRF_TOKEN || "";
            options.body = new URLSearchParams(data).toString();
        }
        return fetch(url, options).then(function (response) { return response.json(); });
    }

    function estadoCotizacion(value) {
        var etiquetas = {borrador: "Borrador", recibida_whatsapp: "Recibida", nueva: "Nueva", seguimiento: "Seguimiento", en_seguimiento: "En seguimiento", aceptada: "Aceptada", convertida_pedido: "Convertida a pedido", convertida_venta: "Convertida a venta", descartada: "Descartada", cancelada: "Cancelada"};
        return etiquetas[value] || value || "Sin estatus";
    }

    function cargarCotizaciones() {
        var params = new URLSearchParams({q: $("fc_cot_q").value.trim(), estatus: $("fc_cot_estatus").value, limite: "50"});
        request("/ventas/cotizaciones_comerciales_listar_erp?" + params.toString()).then(function (response) {
            if (response.error) { throw new Error(response.mensaje || "No fue posible consultar cotizaciones"); }
            var data = response.depurar || {};
            estado.cotizaciones = data.items || [];
            $("fc_kpi_total").textContent = String(data.paginacion ? data.paginacion.total : estado.cotizaciones.length);
            $("fc_kpi_seguimiento").textContent = String(estado.cotizaciones.filter(function (item) { return ["borrador", "recibida_whatsapp", "nueva", "seguimiento", "en_seguimiento"].indexOf(item.estatus) >= 0; }).length);
            $("fc_kpi_vigencia").textContent = String(estado.cotizaciones.filter(function (item) { return item.fecha_expiracion && new Date(item.fecha_expiracion).getTime() <= Date.now() + 3 * 86400000; }).length);
            renderLista();
            if (estado.actual && estado.cotizaciones.some(function (item) { return String(item.id_cotizacion) === String(estado.actual.id_cotizacion); })) {
                consultarCotizacion(estado.actual.id_cotizacion);
            } else if (estado.cotizaciones.length) {
                consultarCotizacion(estado.cotizaciones[0].id_cotizacion);
            } else {
                estado.actual = null;
                $("fc_cot_detalle").className = "fc-empty d-flex align-items-center justify-content-center text-muted";
                $("fc_cot_detalle").textContent = "No hay cotizaciones visibles con estos filtros.";
                $("fc_cot_plan").disabled = true;
            }
        }).catch(function (error) { alerta(error.message || "No fue posible cargar cotizaciones", "danger"); });
    }

    function renderLista() {
        var contenedor = $("fc_cot_lista");
        if (!estado.cotizaciones.length) {
            contenedor.innerHTML = "<div class=\"fc-empty d-flex align-items-center justify-content-center text-muted fs-7\">Sin cotizaciones registradas.</div>";
            return;
        }
        contenedor.innerHTML = estado.cotizaciones.map(function (item) {
            var activo = estado.actual && String(estado.actual.id_cotizacion) === String(item.id_cotizacion) ? " active" : "";
            return "<div class=\"fc-list-row p-3" + activo + "\" data-fc-cot=\"" + esc(item.id_cotizacion) + "\">" +
                "<div class=\"d-flex justify-content-between gap-2\"><span class=\"fw-bold\">" + esc(item.folio || "Sin folio") + "</span><span class=\"badge badge-light-primary\">" + esc(estadoCotizacion(item.estatus)) + "</span></div>" +
                "<div class=\"text-muted fs-8 mt-1\">" + esc(item.nombre_contacto || "Sin contacto") + " · " + esc(item.fecha_expiracion || "Sin vigencia") + "</div>" +
                "<div class=\"fw-semibold mt-2\">" + dinero(item.total_estimado, item.moneda) + " <span class=\"text-muted fs-8\">" + esc(item.moneda || "MXN") + "</span></div></div>";
        }).join("");
        contenedor.querySelectorAll("[data-fc-cot]").forEach(function (node) {
            node.addEventListener("click", function () { consultarCotizacion(node.getAttribute("data-fc-cot")); });
        });
    }

    function consultarCotizacion(id) {
        request("/ventas/cotizacion_comercial_consultar_erp?id_cotizacion=" + encodeURIComponent(id)).then(function (response) {
            if (response.error) { throw new Error(response.mensaje || "No fue posible consultar el detalle"); }
            estado.actual = (response.depurar || {}).item || null;
            var data = response.depurar || {};
            renderLista();
            renderDetalle(data);
        }).catch(function (error) { alerta(error.message || "No fue posible consultar la cotizacion", "danger"); });
    }

    function renderDetalle(data) {
        var item = data.item || {};
        var detalle = data.detalle || [];
        $("fc_cot_plan").disabled = !item.folio;
        $("fc_cot_subtitulo").textContent = item.folio ? (item.nombre_contacto || "Sin contacto") + " · " + estadoCotizacion(item.estatus) : "Selecciona una cotizacion";
        if (!item.folio) {
            $("fc_cot_detalle").className = "fc-empty d-flex align-items-center justify-content-center text-muted";
            $("fc_cot_detalle").textContent = "Cotizacion no encontrada.";
            return;
        }
        var filas = detalle.length ? detalle.map(function (linea) {
            return "<tr><td>" + esc(linea.descripcion || linea.nombre_publico || linea.nombre_snapshot || "Sin descripcion") + "</td><td class=\"text-end\">" + esc(linea.cantidad || "0") + "</td><td class=\"text-end\">" + dinero(linea.subtotal || linea.importe || 0, item.moneda) + "</td></tr>";
        }).join("") : "<tr><td colspan=\"3\" class=\"text-center text-muted py-4\">Sin partidas disponibles en este snapshot.</td></tr>";
        $("fc_cot_detalle").className = "";
        $("fc_cot_detalle").innerHTML = "<div class=\"row g-3 mb-4\"><div class=\"col-md-4\"><div class=\"text-muted fs-8 text-uppercase\">Contacto</div><div class=\"fw-semibold\">" + esc(item.nombre_contacto || "-") + "</div><div class=\"text-muted fs-7\">" + esc(item.telefono_contacto || item.correo_contacto || "Sin contacto") + "</div></div><div class=\"col-md-4\"><div class=\"text-muted fs-8 text-uppercase\">Vigencia</div><div class=\"fw-semibold\">" + esc(item.fecha_expiracion || "No definida") + "</div></div><div class=\"col-md-4\"><div class=\"text-muted fs-8 text-uppercase\">Total estimado</div><div class=\"fw-semibold\">" + dinero(item.total_estimado, item.moneda) + " " + esc(item.moneda || "MXN") + "</div></div></div>" +
            "<div class=\"fc-table table-responsive mb-4\"><table class=\"table table-sm align-middle mb-0\"><thead><tr class=\"text-muted fs-8 text-uppercase\"><th>Partida</th><th class=\"text-end\">Cantidad</th><th class=\"text-end\">Importe</th></tr></thead><tbody>" + filas + "</tbody></table></div>" +
            "<div class=\"row g-3\"><div class=\"col-md-4\"><div class=\"text-muted fs-8\">Subtotal estimado</div><div class=\"fw-semibold\">" + dinero(item.subtotal_estimado, item.moneda) + "</div></div><div class=\"col-md-4\"><div class=\"text-muted fs-8\">Descuento</div><div class=\"fw-semibold text-muted\">No disponible en este snapshot</div></div><div class=\"col-md-4\"><div class=\"text-muted fs-8\">Impuestos</div><div class=\"fw-semibold text-muted\">No disponible en este snapshot</div></div></div>" +
            "<div class=\"text-muted fs-8 mt-3\">Conversion futura: requiere revision humana, cliente confirmado y reglas de Pedido/Venta. Esta pantalla no aparta inventario.</div>";
    }

    function cargarCorreo() {
        request("/ventas/facturacion_correo_plantilla_erp").then(function (response) {
            if (response.error) { throw new Error(response.mensaje || "No fue posible cargar la plantilla"); }
            var data = response.depurar || {};
            $("fc_correo_estado").textContent = data.envio_habilitado ? "Envio habilitado" : "Solo plantilla; envio pendiente de autorizacion tecnica";
            $("fc_correo_asunto").value = data.asunto || "";
            $("fc_correo_cuerpo").value = data.cuerpo || "";
            var empresa = data.empresa || {};
            estado.empresa = empresa;
            $("fc_empresa_nombre").textContent = empresa.nombre || "";
            var logo = String(empresa.logo_url || "").trim();
            if (/^(https?:\/\/|\/|assets\/)/i.test(logo)) {
                $("fc_logo").src = logo.charAt(0) === "/" ? logo : "/" + logo.replace(/^\/+/, "");
                $("fc_logo").classList.remove("d-none");
            }
        }).catch(function (error) { $("fc_correo_estado").textContent = error.message || "Plantilla no disponible"; });
    }

    function analizarCfdi(event) {
        event.preventDefault();
        var archivo = $("fc_cfdi_archivo").files[0];
        if (!archivo) { alerta("Selecciona un XML CFDI", "warning"); return; }
        var data = new FormData();
        data.append("archivo_xml", archivo);
        $("fc_cfdi_resultado").textContent = "Analizando XML...";
        fetch("/ventas/facturacion_cfdi_parsear_erp", {method: "POST", body: data, credentials: "same-origin", headers: {"Accept": "application/json", "X-CSRF-Token": window.ERP_CSRF_TOKEN || ""}}).then(function (response) { return response.json(); }).then(function (response) {
            if (response.error) { throw new Error(response.mensaje || "El XML no pudo analizarse"); }
            estado.cfdi = response.depurar || {};
            renderCfdi(estado.cfdi);
        }).catch(function (error) { $("fc_cfdi_resultado").innerHTML = "<div class=\"alert alert-danger mb-0\">" + esc(error.message || "No fue posible analizar el XML") + "</div>"; });
    }

    function renderCfdi(data) {
        var empresa = estado.empresa || {};
        var conceptos = data.conceptos || [];
        var filas = conceptos.map(function (item) {
            return "<tr><td class=\"text-muted\">" + esc(item.no_identificacion || item.clave_producto_sat || "-") + "</td><td><div class=\"fw-semibold\">" + esc(item.descripcion || "-") + "</div><div class=\"text-muted fs-8\">" + esc(item.clave_producto_sat || "") + "</div></td><td class=\"text-end\">" + esc(item.cantidad || "0") + "</td><td>" + esc(item.unidad || item.clave_unidad_sat || "-") + "</td><td class=\"text-end\">" + dinero(item.valor_unitario, data.moneda) + "</td><td class=\"text-end\">" + dinero(item.descuento, data.moneda) + "</td><td class=\"text-end\">" + dinero(item.importe, data.moneda) + "</td></tr>";
        }).join("");
        var logoUrl = String(empresa.logo_url || "").trim();
        var logo = /^(https?:\/\/|\/|assets\/)/i.test(logoUrl) ? "<img src=\"" + esc(logoUrl.charAt(0) === "/" ? logoUrl : "/" + logoUrl.replace(/^\/+/, "")) + "\" alt=\"Logo\">" : "";
        var nombreEmpresa = empresa.nombre || data.nombre_emisor || "Mi negocio";
        var rfcEmpresa = empresa.rfc || data.rfc_emisor || "";
        var contactoEmpresa = [empresa.telefono, empresa.correo].filter(function (value) { return String(value || "").trim() !== ""; }).join(" · ");
        var direccionEmpresa = String(empresa.direccion || empresa.sitio_web || "").trim();
        var folio = [data.serie, data.folio].filter(function (value) { return String(value || "").trim() !== ""; }).join(" ") || "Sin folio";
        var condicionesOperacion = data.condiciones_pago ? "<div class=\"fc-invoice-fact\"><div class=\"text-muted fs-8\">Condiciones de pago</div><div class=\"fw-semibold\">" + esc(data.condiciones_pago) + "</div></div>" : "";
        var datosOperacion = "<div class=\"fc-invoice-facts mb-3\"><div class=\"fc-invoice-fact\"><div class=\"text-muted fs-8\">Lugar de expedición</div><div class=\"fw-semibold\">" + esc(data.lugar_expedicion || "-") + "</div></div><div class=\"fc-invoice-fact\"><div class=\"text-muted fs-8\">Forma de pago</div><div class=\"fw-semibold\">" + esc(formaPago(data.forma_pago)) + "</div></div><div class=\"fc-invoice-fact\"><div class=\"text-muted fs-8\">Método de pago</div><div class=\"fw-semibold\">" + esc(metodoPago(data.metodo_pago)) + "</div></div><div class=\"fc-invoice-fact\"><div class=\"text-muted fs-8\">Uso CFDI</div><div class=\"fw-semibold\">" + esc(usoCfdi(data.uso_cfdi)) + "</div></div><div class=\"fc-invoice-fact\"><div class=\"text-muted fs-8\">Régimen emisor</div><div class=\"fw-semibold\">" + esc(regimenFiscal(data.regimen_fiscal_emisor)) + "</div></div><div class=\"fc-invoice-fact\"><div class=\"text-muted fs-8\">Régimen receptor</div><div class=\"fw-semibold\">" + esc(regimenFiscal(data.regimen_fiscal_receptor)) + "</div></div><div class=\"fc-invoice-fact\"><div class=\"text-muted fs-8\">C.P. receptor</div><div class=\"fw-semibold\">" + esc(data.domicilio_fiscal_receptor || "-") + "</div></div><div class=\"fc-invoice-fact\"><div class=\"text-muted fs-8\">Exportación</div><div class=\"fw-semibold\">" + esc(exportacion(data.exportacion)) + "</div></div>" + condicionesOperacion + "</div>";
        var aviso = "<div class=\"alert alert-info fs-7 d-flex justify-content-between align-items-center gap-3 flex-wrap\"><span>Vista previa Artiani Compacta. Puedes guardarla o imprimirla; esta acción no crea ventas ni modifica inventario o caja.</span><div class=\"d-flex gap-2 flex-wrap\"><button type=\"button\" class=\"btn btn-sm btn-light-primary text-nowrap\" id=\"fc_cfdi_imprimir\"><i class=\"bi bi-printer\"></i> Artiani Compacta</button><button type=\"button\" class=\"btn btn-sm btn-primary text-nowrap\" id=\"fc_cfdi_imprimir_fiscal\"><i class=\"bi bi-layout-text-window-reverse\"></i> Artiani Fiscal</button></div></div>";
        var preview = "<div class=\"fc-invoice-preview\" id=\"fc_invoice_preview\"><div class=\"fc-invoice-brand d-flex justify-content-between align-items-start gap-4 mb-3\"><div>" + logo + "<div class=\"fw-bold fs-3 mt-2\">" + esc(nombreEmpresa) + "</div><div class=\"text-muted\">" + esc(rfcEmpresa) + "</div><div class=\"text-muted fs-7\">" + esc(contactoEmpresa) + "</div><div class=\"text-muted fs-7\">" + esc(direccionEmpresa) + "</div></div><div class=\"text-end\"><div class=\"text-uppercase text-muted fs-8\">Factura</div><div class=\"fw-bold fs-2\">" + esc(folio) + "</div><div class=\"text-muted fs-7\">" + esc(data.fecha || "") + "</div><span class=\"badge badge-light-primary mt-2\">CFDI " + esc(data.version || "4.0") + " · " + esc(data.tipo_comprobante || "I") + "</span></div></div>" +
            "<div class=\"row g-3 mb-3\"><div class=\"col-md-6\"><div class=\"fc-invoice-meta h-100\"><div class=\"text-muted fs-8 text-uppercase mb-1\">Emisor fiscal</div><div class=\"fw-semibold\">" + esc(data.nombre_emisor || "-") + "</div><div class=\"text-muted fs-7\">RFC: " + esc(data.rfc_emisor || "-") + "</div></div></div><div class=\"col-md-6\"><div class=\"fc-invoice-meta h-100\"><div class=\"text-muted fs-8 text-uppercase mb-1\">Receptor</div><div class=\"fw-semibold\">" + esc(data.nombre_receptor || "-") + "</div><div class=\"text-muted fs-7\">RFC: " + esc(data.rfc_receptor || "-") + "</div></div></div></div>" + datosOperacion +
            "<div class=\"fc-invoice-table table-responsive mb-3\"><table class=\"table align-middle mb-0\"><colgroup><col style=\"width:16%\"><col style=\"width:34%\"><col style=\"width:8%\"><col style=\"width:10%\"><col style=\"width:12%\"><col style=\"width:10%\"><col style=\"width:10%\"></colgroup><thead><tr><th>Clave / identificación</th><th>Descripción</th><th class=\"text-end\">Cantidad</th><th>Unidad</th><th class=\"text-end\">Precio</th><th class=\"text-end\">Descuento</th><th class=\"text-end\">Importe</th></tr></thead><tbody>" + filas + "</tbody></table></div>" +
            "<div class=\"row justify-content-end\"><div class=\"col-md-5\"><div class=\"fc-invoice-total\"><div class=\"d-flex justify-content-between mb-2\"><span>Subtotal</span><span>" + dinero(data.subtotal, data.moneda) + "</span></div><div class=\"d-flex justify-content-between mb-2\"><span>Descuento</span><span>" + dinero(data.descuento, data.moneda) + "</span></div><div class=\"d-flex justify-content-between mb-2\"><span>Impuestos trasladados</span><span>" + dinero(data.impuestos_trasladados, data.moneda) + "</span></div><div class=\"d-flex justify-content-between mb-3\"><span>Retenciones</span><span>" + dinero(data.impuestos_retenidos, data.moneda) + "</span></div><div class=\"fc-tax-note\">Tasas identificadas: " + esc(tasasImpuestos(conceptos)) + "</div><div class=\"d-flex justify-content-between align-items-end border-top pt-3\"><span class=\"fw-bold\">Total</span><strong>" + dinero(data.total, data.moneda) + "</strong></div></div></div></div>" +
            "<div class=\"text-muted fs-8 mt-4\">UUID: " + esc(data.uuid || "Sin timbre fiscal") + " · Moneda: " + esc(data.moneda || "MXN") + " · Tipo de cambio: " + esc(data.tipo_cambio || "1") + "</div></div>";
        var qrUrl = qrSatUrl(data);
        var qr = qrUrl ? "<div class=\"fc-fiscal-qr\"><img src=\"" + esc(qrUrl) + "\" alt=\"Código QR de verificación del SAT\"><div class=\"text-muted fs-8\">Verificación SAT</div></div>" : "";
        var fiscal = "<details class=\"fc-fiscal-box mt-3 p-3\"><summary class=\"fw-bold\">Datos fiscales y sellos del SAT</summary><div class=\"fc-fiscal-layout\">" + qr + "<div class=\"fc-fiscal-content\"><div class=\"row g-2 mt-1\"><div class=\"col-md-6\"><div class=\"text-muted fs-8\">UUID</div><div class=\"fw-semibold text-break\">" + esc(data.uuid || "-") + "</div></div><div class=\"col-md-3\"><div class=\"text-muted fs-8\">Fecha de timbrado</div><div class=\"fw-semibold\">" + esc(data.fecha_timbrado || "-") + "</div></div><div class=\"col-md-3\"><div class=\"text-muted fs-8\">Certificado SAT</div><div class=\"fw-semibold\">" + esc(data.no_certificado_sat || "-") + "</div></div></div><div class=\"mt-2\"><div class=\"text-muted fs-8\">Sello digital del CFDI</div><div class=\"fc-sello\">" + esc(data.sello_cfdi || "No disponible") + "</div></div><div class=\"mt-2\"><div class=\"text-muted fs-8\">Sello digital del SAT</div><div class=\"fc-sello\">" + esc(data.sello_sat || "No disponible") + "</div></div><div class=\"row g-2 mt-1\"><div class=\"col-md-4\"><div class=\"text-muted fs-8\">Certificado del emisor</div><div class=\"fw-semibold\">" + esc(data.no_certificado || "-") + "</div></div><div class=\"col-md-8\"><div class=\"text-muted fs-8\">Certificado incluido</div><div class=\"fw-semibold\">" + (data.certificado ? "Sí, " + esc(String(data.certificado).length) + " caracteres" : "No disponible") + "</div></div></div></div></div></details>";
        $("fc_cfdi_resultado").innerHTML = aviso + preview + fiscal;
        $("fc_cfdi_imprimir").addEventListener("click", imprimirCfdi);
        $("fc_cfdi_imprimir_fiscal").addEventListener("click", imprimirCfdiFiscal);
    }

    function imprimirCfdiFiscal() {
        var data = estado.cfdi || {};
        var empresa = estado.empresa || {};
        var logoUrl = String(empresa.logo_url || "").trim();
        var logo = /^(https?:\/\/|\/|assets\/)/i.test(logoUrl) ? "<img src=\"" + esc(logoUrl.charAt(0) === "/" ? logoUrl : "/" + logoUrl.replace(/^\/+/, "")) + "\" alt=\"Logo del negocio\">" : "";
        var folio = [data.serie, data.folio].filter(function (value) { return String(value || "").trim() !== ""; }).join(" ") || "Sin folio";
        var conceptos = data.conceptos || [];
        var filas = conceptos.map(function (item) {
            var tasas = [];
            if (numero(item.iva_porcentaje) > 0) { tasas.push("IVA " + numero(item.iva_porcentaje) + "%"); }
            if (numero(item.ieps_porcentaje) > 0) { tasas.push("IEPS " + numero(item.ieps_porcentaje) + "%"); }
            return "<tr><td class=\"text-end\">" + esc(item.cantidad || "0") + "</td><td>" + esc(item.unidad || item.clave_unidad_sat || "-") + "</td><td><strong>" + esc(item.descripcion || "-") + "</strong><small>Clave SAT: " + esc(item.clave_producto_sat || "-") + (tasas.length ? " · " + esc(tasas.join(" · ")) : "") + "</small></td><td class=\"text-end\">" + dinero(item.valor_unitario, data.moneda) + "</td><td class=\"text-end\">" + dinero(item.descuento, data.moneda) + "</td><td class=\"text-end\">" + dinero(item.importe, data.moneda) + "</td></tr>";
        }).join("");
        var qrUrl = qrSatUrl(data);
        var qr = qrUrl ? "<img class=\"af-qr\" src=\"" + esc(qrUrl) + "\" alt=\"Código QR de verificación del SAT\">" : "<div class=\"af-qr af-qr-empty\">QR no disponible</div>";
        var selloCfdi = data.sello_cfdi || "No disponible";
        var selloSat = data.sello_sat || "No disponible";
        var cadena = data.uuid ? "||" + (data.version || "4.0") + "|" + data.uuid + "|" + (data.fecha_timbrado || "") + "|" + (data.rfc_prov_certif || "") + "|" + selloCfdi + "|" + (data.no_certificado_sat || "") + "||" : "No disponible en el XML cargado";
        var documento = [
            "<div class=\"af-document\"><header class=\"af-header\"><div class=\"af-supplier\">" + logo + "<div><strong>" + esc(empresa.nombre || data.nombre_emisor || "Mi negocio") + "</strong><span>RFC: " + esc(empresa.rfc || data.rfc_emisor || "") + "</span><span>Régimen fiscal: " + esc(regimenFiscal(data.regimen_fiscal_emisor)) + "</span><span>Lugar de expedición: " + esc(data.lugar_expedicion || "-") + "</span></div></div><div class=\"af-invoice-title\"><span>FACTURA</span><b>" + esc(folio) + "</b><small>Fecha de emisión: " + esc(data.fecha || "-") + "</small><small>Fecha de certificación: " + esc(data.fecha_timbrado || "-") + "</small></div></header>",
            "<div class=\"af-bar af-bar-split\"><span>Receptor del comprobante fiscal</span><span>Folio fiscal</span></div><section class=\"af-receiver\"><div><strong>" + esc(data.nombre_receptor || "-") + "</strong><span>RFC: " + esc(data.rfc_receptor || "-") + "</span><span>Régimen fiscal: " + esc(regimenFiscal(data.regimen_fiscal_receptor)) + "</span><span>Domicilio fiscal: " + esc(data.domicilio_fiscal_receptor || "-") + "</span><span>Uso CFDI: " + esc(usoCfdi(data.uso_cfdi)) + "</span><span>Exportación: " + esc(exportacion(data.exportacion)) + "</span></div><div class=\"af-certificates\"><b>" + esc(data.uuid || "-") + "</b><span>No. certificado digital: " + esc(data.no_certificado || "-") + "</span><span>No. certificado SAT: " + esc(data.no_certificado_sat || "-") + "</span></div></section>",
            "<section class=\"af-table\"><table><colgroup><col style=\"width:10%\"><col style=\"width:11%\"><col style=\"width:39%\"><col style=\"width:14%\"><col style=\"width:12%\"><col style=\"width:14%\"></colgroup><thead><tr><th>Cantidad</th><th>Unidad</th><th>Descripción</th><th>Precio</th><th>Descuento</th><th>Importe</th></tr></thead><tbody>" + filas + "</tbody></table></section>",
            "<div class=\"af-bar\">Información de pago y totales</div><section class=\"af-payment\"><div><p><b>Lugar de expedición:</b> " + esc(data.lugar_expedicion || "-") + "</p><p><b>Forma de pago:</b> " + esc(formaPago(data.forma_pago)) + "</p><p><b>Método de pago:</b> " + esc(metodoPago(data.metodo_pago)) + "</p><p><b>Condiciones de pago:</b> " + esc(data.condiciones_pago || "No especificadas en el XML") + "</p><p><b>Moneda:</b> " + esc(data.moneda || "MXN") + " · Tipo de cambio: " + esc(data.tipo_cambio || "1") + "</p></div><div class=\"af-totals\"><p><span>Subtotal</span><b>" + dinero(data.subtotal, data.moneda) + "</b></p><p><span>Descuento</span><b>" + dinero(data.descuento, data.moneda) + "</b></p><p><span>Impuestos trasladados</span><b>" + dinero(data.impuestos_trasladados, data.moneda) + "</b></p><p class=\"af-total\"><span>Total</span><b>" + dinero(data.total, data.moneda) + "</b></p></div></section>",
            "<div class=\"af-bar af-bar-split\"><span>Código bidimensional</span><span>Sellos digitales</span></div><section class=\"af-seals\"><div class=\"af-qr-wrap\">" + qr + "<small>Consulta la factura en el SAT</small></div><div><label>Sello digital del CFDI</label><p>" + esc(selloCfdi) + "</p><label>Sello digital del SAT</label><p>" + esc(selloSat) + "</p></div></section><div class=\"af-bar af-chain-bar\">Cadena original del complemento de certificación digital del SAT</div><p class=\"af-chain\">" + esc(cadena) + "</p><footer>Este documento es una representación impresa de un CFDI · Versión " + esc(data.version || "4.0") + "</footer></div>"
        ].join("");
        var estilos = "body{font-family:Arial,sans-serif;background:#f4f6f8;color:#202124;margin:0;padding:18px}.af-document{box-sizing:border-box;width:100%;max-width:980px;margin:0 auto;background:#fff;padding:18px;border:1px solid #d9e2e5}.af-header{display:grid;grid-template-columns:minmax(0,1fr) 170px;gap:14px;align-items:start;padding-bottom:12px;border-bottom:3px solid #e9485b}.af-supplier{display:flex;gap:16px;align-items:center;min-width:0}.af-supplier>div{min-width:0}.af-supplier img{max-width:190px;max-height:62px;object-fit:contain}.af-supplier strong{display:block;font-size:11px;color:#202124;margin-bottom:4px}.af-supplier span,.af-invoice-title small{display:block;font-size:9px;color:#202124;line-height:1.4;overflow-wrap:anywhere;word-break:break-word}.af-invoice-title{min-width:0;text-align:right}.af-invoice-title span{display:block;font-size:10px;color:#0b7376;font-weight:700;letter-spacing:.08em}.af-invoice-title b{display:block;font-size:24px;color:#e9485b;line-height:1.15}.af-bar{display:flex;justify-content:center;align-items:center;min-height:22px;margin-top:10px;padding:4px 8px;box-sizing:border-box;background:#0b7376;color:#fff;border-radius:3px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.02em}.af-chain-bar{background:#0b7376}.af-bar-split{display:grid;grid-template-columns:1fr 1fr;gap:1px;padding:0;background:transparent}.af-bar-split span{display:block;background:#e9485b;padding:5px 8px;text-align:center}.af-bar-split span:last-child{background:#0b7376}.af-receiver{display:grid;grid-template-columns:minmax(0,1fr) minmax(260px,42%);gap:16px;padding:9px 6px 8px;border:1px solid #e8eef0;border-top:0}.af-receiver span,.af-receiver strong{display:block;font-size:10px;line-height:1.45;overflow-wrap:anywhere;word-break:break-word;color:#202124}.af-receiver strong{font-size:11px;color:#202124}.af-certificates{display:grid;gap:5px;align-content:start}.af-certificates b,.af-certificates span{display:block;padding:5px 7px;background:#f1fbfb;border-left:3px solid #0b7376;font-size:9px;word-break:break-word;color:#202124}.af-certificates b{color:#202124}.af-table{box-sizing:border-box;width:100%;margin-top:10px;border:1px solid #d7e1e4;overflow:hidden}.af-table table{box-sizing:border-box;width:100%;table-layout:fixed;border-collapse:collapse}.af-table th,.af-table td{box-sizing:border-box;overflow-wrap:anywhere;word-break:break-word}.af-table th{background:#e9485b;color:#fff;padding:5px 4px;font-size:8.5px;line-height:1.2;text-transform:uppercase}.af-table td{padding:5px 4px;border-bottom:1px solid #e7edef;font-size:8.5px;line-height:1.25;vertical-align:top;color:#202124}.af-table td small{display:block;color:#202124;font-size:7.5px;margin-top:2px}.af-table tr:nth-child(even) td{background:#fffafa}.af-payment{display:grid;grid-template-columns:minmax(0,1fr) 270px;gap:18px;border:1px solid #d7e1e4;border-top:0;padding:10px 8px}.af-payment p{margin:0 0 5px;font-size:10px;color:#202124}.af-payment p:last-child{margin-bottom:0}.af-totals{border-left:1px solid #b9d9da;padding-left:18px}.af-totals p{display:flex;justify-content:space-between;gap:12px;color:#202124}.af-total{box-sizing:border-box;border-top:0;padding:7px 9px;margin-top:8px!important;background:#e9485b;color:#fff;font-size:11px!important}.af-total b{font-size:14px}.af-seals{display:grid;grid-template-columns:150px minmax(0,1fr);gap:15px;border:1px solid #d7e1e4;border-top:0;padding:10px 8px}.af-qr-wrap{text-align:center}.af-qr{display:block;width:132px;height:132px;margin:0 auto 4px;image-rendering:pixelated}.af-qr-empty{height:132px;background:#f1fbfb;color:#0b7376;font-size:10px;padding-top:58px;box-sizing:border-box}.af-qr-wrap small{font-size:8px;color:#202124}.af-seals label{display:block;color:#0b7376;font-size:9px;font-weight:700;margin-bottom:2px}.af-seals p{margin:0 0 8px;font-family:Courier,monospace;font-size:8px;line-height:1.3;word-break:break-all;color:#202124}.af-chain{margin:0;padding:8px;border:1px solid #d7e1e4;border-top:0;font-family:Courier,monospace;font-size:8px;line-height:1.3;word-break:break-all;color:#202124}.af-document footer{margin-top:10px;padding:6px;background:#e9485b;color:#fff;text-align:center;font-size:8px}@media print{body{padding:0;background:#fff}.af-document{border:0;max-width:none;padding:10px}.af-table,.af-payment,.af-seals,.af-chain{break-inside:avoid}}";
        var ventana = window.open("", "_blank", "width=980,height=900");
        if (!ventana) { alerta("Permite las ventanas emergentes para imprimir la factura", "warning"); return; }
        ventana.document.write("<!doctype html><html lang=\"es\"><head><meta charset=\"utf-8\"><title>Artiani Fiscal</title><style>" + estilos + "</style></head><body>" + documento + "</body></html>");
        ventana.document.close(); ventana.focus();
        var imprimir = function () { ventana.focus(); ventana.print(); };
        var pendientes = Array.prototype.filter.call(ventana.document.images, function (imagen) { return !imagen.complete; });
        if (pendientes.length) {
            pendientes.forEach(function (imagen) { var terminar = function () { pendientes.splice(pendientes.indexOf(imagen), 1); if (!pendientes.length) { imprimir(); } }; imagen.addEventListener("load", terminar); imagen.addEventListener("error", terminar); });
            setTimeout(imprimir, 1800);
        } else { setTimeout(imprimir, 300); }
    }

    function imprimirCfdi() {
        var preview = $("fc_invoice_preview");
        if (!preview) { return; }
        var fiscal = $("fc_cfdi_resultado").querySelector(".fc-fiscal-box");
        if (fiscal) { fiscal.setAttribute("open", "open"); }
        var documento = "<div class=\"fc-invoice-document\">" + preview.innerHTML + (fiscal ? fiscal.outerHTML : "") + "</div>";
        var ventana = window.open("", "_blank", "width=980,height=900");
        if (!ventana) { alerta("Permite las ventanas emergentes para imprimir la factura", "warning"); return; }
        ventana.document.write("<!doctype html><html lang=\"es\"><head><meta charset=\"utf-8\"><title>Factura</title><style>body{font-family:Arial,sans-serif;color:#191919;margin:14px}.fc-invoice-document{display:flex;flex-direction:column}.fc-invoice-preview{box-sizing:border-box;max-width:980px;width:100%;margin:0 auto;border:1px solid #d8dee8;border-radius:8px;padding:18px}.fc-invoice-brand{border-bottom:3px solid #e9485b;padding-bottom:12px;display:flex;justify-content:space-between;gap:24px}.fc-invoice-brand img{max-width:170px;max-height:58px;object-fit:contain}.fc-invoice-meta{background:#fff;border:1px solid #e8cfd3;border-radius:4px;padding:8px;display:inline-block;width:44%;vertical-align:top}.fc-invoice-facts{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:6px 12px;margin:0 0 10px;padding:0 2px}.fc-invoice-fact{background:transparent;padding:0;min-height:26px}.fc-invoice-fact .text-muted{color:#98a2b3!important;font-size:8px;text-transform:uppercase;letter-spacing:.02em}.fc-invoice-fact .fw-semibold{color:#475467;font-size:9px;font-weight:600}.fc-invoice-table{box-sizing:border-box;width:100%;border:1px solid #d8dee8;border-radius:6px;overflow:hidden;margin:10px 0}.fc-invoice-table table{width:100%;table-layout:fixed;border-collapse:collapse}.fc-invoice-table th:nth-child(1),.fc-invoice-table td:nth-child(1){width:16%}.fc-invoice-table th:nth-child(2),.fc-invoice-table td:nth-child(2){width:34%}.fc-invoice-table th:nth-child(3),.fc-invoice-table td:nth-child(3){width:8%}.fc-invoice-table th:nth-child(4),.fc-invoice-table td:nth-child(4){width:10%}.fc-invoice-table th:nth-child(5),.fc-invoice-table td:nth-child(5){width:12%}.fc-invoice-table th:nth-child(6),.fc-invoice-table td:nth-child(6){width:10%}.fc-invoice-table th:nth-child(7),.fc-invoice-table td:nth-child(7){width:10%}.fc-invoice-table th,.fc-invoice-table td{padding:5px;border-bottom:1px solid #e8ecf2;text-align:left;vertical-align:top;font-size:10px;color:#191919;overflow-wrap:anywhere;word-break:break-word}.fc-invoice-table th{background:#e9485b;color:#fff;font-size:9px;font-weight:700;text-transform:uppercase}.text-end{text-align:right!important}.text-muted{color:#667085}.fw-bold{font-weight:700}.fw-semibold{font-weight:600}.fs-8{font-size:9px}.fs-7{font-size:10px}.fs-2{font-size:23px}.fs-3{font-size:18px}.fc-invoice-total{background:#fff0f2;border:1px solid #f3bcc4;border-radius:6px;padding:9px;margin-left:auto;max-width:320px}.fc-invoice-total strong{color:#c9304a;font-size:20px}.fc-tax-note{color:#0b7376;font-size:10px;margin-bottom:8px}.fc-fiscal-box{border:1px solid #b9d9da;border-radius:6px;background:#f0fafa;padding:9px}.fc-fiscal-box summary{font-size:11px;color:#0b7376}.fc-fiscal-layout{display:grid;grid-template-columns:82px minmax(0,1fr);gap:10px;align-items:start;margin-top:4px}.fc-fiscal-qr{text-align:center;color:#0b7376}.fc-fiscal-qr img{display:block;width:74px;height:74px;margin:0 auto 3px;image-rendering:pixelated}.fc-sello{word-break:break-all;font-family:Courier,monospace;font-size:6px;line-height:1.35;color:#193b3d;margin-top:4px}@media print{body{margin:0}.fc-fiscal-box{display:block}}</style></head><body>" + documento + "</body></html>");
        ventana.document.close();
        ventana.focus();
        var imprimir = function () { ventana.focus(); ventana.print(); };
        var imagenesPendientes = Array.prototype.filter.call(ventana.document.images, function (imagen) { return !imagen.complete; });
        if (imagenesPendientes.length) {
            imagenesPendientes.forEach(function (imagen) { imagen.addEventListener("load", function () { imagenesPendientes.splice(imagenesPendientes.indexOf(imagen), 1); if (!imagenesPendientes.length) { imprimir(); } }); imagen.addEventListener("error", function () { imagenesPendientes.splice(imagenesPendientes.indexOf(imagen), 1); if (!imagenesPendientes.length) { imprimir(); } }); });
            setTimeout(imprimir, 1800);
        } else {
            setTimeout(imprimir, 300);
        }
    }

    document.addEventListener("DOMContentLoaded", function () {
        $("fc_cot_recargar").addEventListener("click", cargarCotizaciones);
        $("fc_cot_q").addEventListener("keyup", function () { clearTimeout(window.fcCotTimer); window.fcCotTimer = setTimeout(cargarCotizaciones, 350); });
        $("fc_cot_estatus").addEventListener("change", cargarCotizaciones);
        $("fc_cfdi_form").addEventListener("submit", analizarCfdi);
        $("fc_cot_plan").addEventListener("click", function () {
            if (!estado.actual) { return; }
            request("/ventas/cotizacion_comercial_accion_plan_erp", {id_cotizacion: estado.actual.id_cotizacion, folio: estado.actual.folio, accion: "marcar_seguimiento"}).then(function (response) { alerta(response.mensaje || "Plan consultado", response.error ? "warning" : "info"); });
        });
        cargarCotizaciones();
        cargarCorreo();
    });
}());
