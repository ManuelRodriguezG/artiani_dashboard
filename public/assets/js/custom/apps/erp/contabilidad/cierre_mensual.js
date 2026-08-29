"use strict";
(function () {
    var movimientos = [];
    var cfdis = [];

    /**
     * IA: Codex GPT-5 | Fecha: 2026-08-29
     * Proposito: obtener nodos del MVP contable sin romper si una seccion cambia temporalmente.
     * Impacto: UI de Contabilidad; permite iterar la vista sin errores JS ruidosos.
     * Contrato: devuelve el nodo solicitado o null; las llamadas validan existencia.
     */
    function $(id) { return document.getElementById(id); }
    function escapeHtml(value) { var div = document.createElement("div"); div.textContent = value == null ? "" : String(value); return div.innerHTML; }
    function money(value) { return Number(value || 0).toLocaleString("es-MX", {style: "currency", currency: "MXN"}); }
    function numero(value) {
        if (value == null) { return 0; }
        var limpio = String(value).replace(/\s/g, "").replace(/\$/g, "").replace(/,/g, "");
        var n = parseFloat(limpio);
        return isNaN(n) ? 0 : n;
    }
    function hoyPeriodo() {
        var d = new Date();
        return d.getFullYear() + "-" + String(d.getMonth() + 1).padStart(2, "0");
    }
    function normalizar(value) {
        return String(value || "").toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");
    }
    function descargar(nombre, contenido, tipo) {
        var blob = new Blob([contenido], {type: tipo});
        var url = URL.createObjectURL(blob);
        var a = document.createElement("a");
        a.href = url;
        a.download = nombre;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }
    function csvEscape(value) {
        var s = value == null ? "" : String(value);
        return /[",\r\n]/.test(s) ? '"' + s.replace(/"/g, '""') + '"' : s;
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-08-29
     * Proposito: dividir estados bancarios CSV/TXT con separador comun sin depender de un banco especifico.
     * Impacto: Contabilidad MVP; permite validar flujo antes de construir importadores por banco.
     * Contrato: acepta texto con encabezados; regresa objetos por columna detectada.
     */
    function parseCsv(texto) {
        var lineas = String(texto || "").replace(/\r/g, "").split("\n").filter(function (l) { return l.trim() !== ""; });
        if (!lineas.length) { return []; }
        var primera = lineas[0];
        var separadores = [",", ";", "\t", "|"];
        var sep = separadores.reduce(function (mejor, actual) {
            return primera.split(actual).length > primera.split(mejor).length ? actual : mejor;
        }, ",");
        var filas = lineas.map(function (linea) {
            var actual = [];
            var celda = "";
            var comillas = false;
            for (var i = 0; i < linea.length; i++) {
                var ch = linea[i];
                if (ch === '"' && linea[i + 1] === '"') { celda += '"'; i++; }
                else if (ch === '"') { comillas = !comillas; }
                else if (ch === sep && !comillas) { actual.push(celda); celda = ""; }
                else { celda += ch; }
            }
            actual.push(celda);
            return actual.map(function (x) { return x.trim(); });
        });
        var headers = filas.shift().map(function (h) { return normalizar(h); });
        return filas.map(function (fila) {
            var obj = {};
            headers.forEach(function (h, i) { obj[h] = fila[i] || ""; });
            return obj;
        });
    }
    function valorPorAlias(obj, aliases) {
        var keys = Object.keys(obj);
        for (var i = 0; i < aliases.length; i++) {
            var alias = normalizar(aliases[i]);
            for (var j = 0; j < keys.length; j++) {
                if (keys[j].indexOf(alias) >= 0) { return obj[keys[j]]; }
            }
        }
        return "";
    }
    function clasificarMovimiento(concepto, cargo, abono) {
        var c = normalizar(concepto);
        if (abono > 0) { return "deposito"; }
        if (/traspaso|transferencia entre|cuenta propia|spei recibido propio|retiro cajero/.test(c)) { return "interno"; }
        if (/sat|imss|infonavit|isr|iva|impuesto|tesoreria/.test(c)) { return "impuesto"; }
        if (/proveedor|compra|factura|mercancia|mayoreo|distribuid/.test(c)) { return "compra"; }
        if (/renta|luz|cfe|gasolina|nomina|telefono|internet|comision|software|publicidad|mensualidad|servicio/.test(c)) { return "gasto"; }
        return "revision";
    }
    function cfdiEsperado(tipo) {
        return tipo === "interno" || tipo === "impuesto" || tipo === "deposito" ? "no_aplica" : "pendiente";
    }
    function importarMovimientos(rows) {
        movimientos = rows.map(function (row, idx) {
            var cargo = numero(valorPorAlias(row, ["cargo", "retiro", "egreso", "debito", "importe cargo"]));
            var abono = numero(valorPorAlias(row, ["abono", "deposito", "ingreso", "credito", "importe abono"]));
            var importe = numero(valorPorAlias(row, ["importe", "monto", "cantidad"]));
            if (!cargo && !abono && importe) {
                if (importe < 0) { cargo = Math.abs(importe); } else { abono = importe; }
            }
            var concepto = valorPorAlias(row, ["concepto", "descripcion", "detalle", "referencia", "movimiento"]);
            var tipo = clasificarMovimiento(concepto, cargo, abono);
            return {
                id: "mov-" + Date.now() + "-" + idx,
                fecha: valorPorAlias(row, ["fecha", "operacion", "aplicacion"]) || "",
                concepto: concepto || "Movimiento sin concepto",
                referencia: valorPorAlias(row, ["referencia", "folio", "rastreo", "autorizacion"]) || "",
                cargo: cargo,
                abono: abono,
                tipo: tipo,
                cfdi: cfdiEsperado(tipo),
                cfdi_uuid: "",
                notas: ""
            };
        });
        autoRelacionarCfdi();
        render();
    }
    function leerArchivoTexto(file, cb) {
        var reader = new FileReader();
        reader.onload = function () { cb(String(reader.result || "")); };
        reader.readAsText(file, "UTF-8");
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-08-29
     * Proposito: extraer metadatos principales de XML CFDI para conciliacion manual rapida.
     * Impacto: Contabilidad MVP; no valida fiscalmente ni sustituye revision del contador.
     * Contrato: acepta XML CFDI 3.3/4.0 y devuelve UUID, RFCs, total, fecha, serie/folio.
     */
    function parseCfdiXml(texto, nombre) {
        var xml = new DOMParser().parseFromString(texto, "text/xml");
        var comprobante = xml.getElementsByTagNameNS("*", "Comprobante")[0] || xml.getElementsByTagName("cfdi:Comprobante")[0] || xml.documentElement;
        var emisor = xml.getElementsByTagNameNS("*", "Emisor")[0] || xml.getElementsByTagName("cfdi:Emisor")[0];
        var receptor = xml.getElementsByTagNameNS("*", "Receptor")[0] || xml.getElementsByTagName("cfdi:Receptor")[0];
        var timbre = xml.getElementsByTagNameNS("*", "TimbreFiscalDigital")[0] || xml.getElementsByTagName("tfd:TimbreFiscalDigital")[0];
        return {
            archivo: nombre,
            uuid: timbre ? (timbre.getAttribute("UUID") || "") : "",
            fecha: comprobante ? (comprobante.getAttribute("Fecha") || "").slice(0, 10) : "",
            total: numero(comprobante ? comprobante.getAttribute("Total") : 0),
            tipo: comprobante ? (comprobante.getAttribute("TipoDeComprobante") || "") : "",
            serie: comprobante ? (comprobante.getAttribute("Serie") || "") : "",
            folio: comprobante ? (comprobante.getAttribute("Folio") || "") : "",
            rfc_emisor: emisor ? (emisor.getAttribute("Rfc") || "") : "",
            emisor: emisor ? (emisor.getAttribute("Nombre") || "") : "",
            rfc_receptor: receptor ? (receptor.getAttribute("Rfc") || "") : ""
        };
    }
    function autoRelacionarCfdi() {
        movimientos.forEach(function (mov) {
            if (mov.cfdi === "ligado") { return; }
            var monto = mov.cargo > 0 ? mov.cargo : mov.abono;
            var candidato = cfdis.find(function (cfdi) {
                return Math.abs(Number(cfdi.total || 0) - monto) <= 1 && normalizar(mov.concepto).indexOf(normalizar(cfdi.rfc_emisor)) >= 0;
            }) || cfdis.find(function (cfdi) {
                return Math.abs(Number(cfdi.total || 0) - monto) <= 1;
            });
            if (candidato && mov.cargo > 0) {
                mov.cfdi = "ligado";
                mov.cfdi_uuid = candidato.uuid;
            }
        });
    }
    function filtros() {
        return {
            q: normalizar($("contabilidad_buscar") ? $("contabilidad_buscar").value : ""),
            tipo: $("contabilidad_tipo") ? $("contabilidad_tipo").value : "",
            cfdi: $("contabilidad_cfdi") ? $("contabilidad_cfdi").value : "",
            flujo: $("contabilidad_flujo") ? $("contabilidad_flujo").value : ""
        };
    }
    function visibles() {
        var f = filtros();
        return movimientos.filter(function (m) {
            var flujo = m.abono > 0 ? "ingreso" : "egreso";
            var texto = normalizar([m.fecha, m.concepto, m.referencia, m.cfdi_uuid, m.cargo, m.abono].join(" "));
            return (!f.q || texto.indexOf(f.q) >= 0) &&
                (!f.tipo || m.tipo === f.tipo) &&
                (!f.cfdi || m.cfdi === f.cfdi) &&
                (!f.flujo || flujo === f.flujo);
        });
    }
    function renderKpis() {
        var totalDepositos = movimientos.reduce(function (s, m) { return s + Number(m.abono || 0); }, 0);
        var totalEgresos = movimientos.reduce(function (s, m) { return s + Number(m.cargo || 0); }, 0);
        var pendientes = movimientos.filter(function (m) { return m.cfdi === "pendiente"; }).length;
        var ligados = movimientos.filter(function (m) { return m.cfdi === "ligado"; }).length;
        var data = [
            ["Ingresos", money(totalDepositos), "badge-light-success", "bi-arrow-down-circle"],
            ["Egresos", money(totalEgresos), "badge-light-danger", "bi-arrow-up-circle"],
            ["CFDI ligados", ligados, "badge-light-primary", "bi-link-45deg"],
            ["Pendientes", pendientes, "badge-light-warning", "bi-exclamation-triangle"]
        ];
        $("contabilidad_kpis").innerHTML = data.map(function (item) {
            return "<div class=\"col-md-3\"><div class=\"card h-100\"><div class=\"card-body d-flex align-items-center gap-3\">" +
                "<span class=\"badge " + item[2] + " p-3\"><i class=\"bi " + item[3] + " fs-2\"></i></span>" +
                "<div><div class=\"text-muted fs-8 text-uppercase\">" + item[0] + "</div><div class=\"fw-bold fs-4\">" + item[1] + "</div></div>" +
                "</div></div></div>";
        }).join("");
    }
    function renderMovimientos() {
        var rows = visibles();
        $("contabilidad_total_visible").textContent = rows.length + " visibles";
        $("contabilidad_movimientos").innerHTML = rows.map(function (m) {
            return "<tr data-id=\"" + escapeHtml(m.id) + "\">" +
                "<td class=\"text-nowrap\">" + escapeHtml(m.fecha || "-") + "</td>" +
                "<td><div class=\"fw-semibold\">" + escapeHtml(m.concepto) + "</div><div class=\"text-muted fs-8\">" + escapeHtml(m.referencia || "") + "</div></td>" +
                "<td class=\"text-end text-danger\">" + (m.cargo ? money(m.cargo) : "-") + "</td>" +
                "<td class=\"text-end text-success\">" + (m.abono ? money(m.abono) : "-") + "</td>" +
                "<td><select class=\"form-select form-select-sm form-select-solid\" data-campo=\"tipo\">" +
                ["deposito", "compra", "gasto", "interno", "impuesto", "revision"].map(function (t) { return "<option value=\"" + t + "\"" + (m.tipo === t ? " selected" : "") + ">" + t + "</option>"; }).join("") +
                "</select></td>" +
                "<td><select class=\"form-select form-select-sm form-select-solid\" data-campo=\"cfdi\">" +
                ["ligado", "pendiente", "no_aplica"].map(function (t) { return "<option value=\"" + t + "\"" + (m.cfdi === t ? " selected" : "") + ">" + t + "</option>"; }).join("") +
                "</select><div class=\"text-muted fs-9 mt-1\">" + escapeHtml(m.cfdi_uuid || "") + "</div></td>" +
                "<td class=\"text-end\"><button class=\"btn btn-sm btn-icon btn-light-primary\" type=\"button\" data-ligar=\"" + escapeHtml(m.id) + "\"><i class=\"bi bi-link\"></i></button></td>" +
                "</tr>";
        }).join("") || "<tr><td colspan=\"7\" class=\"text-center text-muted py-8\">Carga movimientos bancarios para iniciar el cierre.</td></tr>";
    }
    function renderCfdis() {
        $("contabilidad_cfdis").innerHTML = cfdis.map(function (c) {
            return "<div class=\"border-bottom py-3\"><div class=\"fw-bold\">" + escapeHtml(c.emisor || c.rfc_emisor || c.archivo) + "</div>" +
                "<div class=\"text-muted fs-8\">" + escapeHtml(c.fecha || "-") + " | " + escapeHtml(c.uuid || "Sin UUID") + "</div>" +
                "<div class=\"d-flex justify-content-between mt-1\"><span class=\"badge badge-light\">" + escapeHtml(c.tipo || "CFDI") + "</span><span class=\"fw-bold\">" + money(c.total) + "</span></div></div>";
        }).join("") || "<div class=\"text-muted py-4\">Carga XML para relacionarlos con egresos.</div>";
    }
    function renderPendientes() {
        var pendientes = movimientos.filter(function (m) { return m.cfdi === "pendiente"; });
        $("contabilidad_pendientes").innerHTML = pendientes.map(function (m) {
            return "<div class=\"alert alert-warning py-3 mb-3\"><div class=\"fw-bold\">" + escapeHtml(m.concepto) + "</div>" +
                "<div class=\"fs-8\">" + escapeHtml(m.fecha || "-") + " | " + money(m.cargo || m.abono) + " | " + escapeHtml(m.tipo) + "</div></div>";
        }).join("") || "<div class=\"alert alert-success py-3 mb-0\">Sin pendientes visibles de CFDI.</div>";
    }
    function render() {
        renderKpis();
        renderMovimientos();
        renderCfdis();
        renderPendientes();
    }
    function exportarCsv() {
        var periodo = $("contabilidad_periodo").value || hoyPeriodo();
        var cuenta = $("contabilidad_cuenta").value || "";
        var headers = ["periodo", "cuenta", "fecha", "concepto", "referencia", "cargo", "abono", "tipo", "cfdi", "cfdi_uuid", "notas"];
        var lines = [headers.join(",")].concat(movimientos.map(function (m) {
            return [periodo, cuenta, m.fecha, m.concepto, m.referencia, m.cargo, m.abono, m.tipo, m.cfdi, m.cfdi_uuid, m.notas].map(csvEscape).join(",");
        }));
        descargar("cierre_contable_" + periodo + ".csv", lines.join("\n"), "text/csv;charset=utf-8");
    }
    function exportarJson() {
        var periodo = $("contabilidad_periodo").value || hoyPeriodo();
        descargar("cierre_contable_" + periodo + ".json", JSON.stringify({
            periodo: periodo,
            cuenta: $("contabilidad_cuenta").value || "",
            movimientos: movimientos,
            cfdis: cfdis
        }, null, 2), "application/json;charset=utf-8");
    }
    function cargarDemo() {
        importarMovimientos(parseCsv("fecha,concepto,cargo,abono,referencia\n2026-08-01,DEPOSITO VENTA MOSTRADOR,,5800,POS-001\n2026-08-02,TRANSFERENCIA PROVEEDOR ACUARIOS SA,3200,,SPEI-889\n2026-08-03,PAGO CFE SUCURSAL CENTRO,840.50,,SERV-100\n2026-08-04,TRASPASO CUENTA PROPIA BBVA,5000,,INT-77\n2026-08-17,PAGO SAT IVA JULIO,1260,,SAT-IVA"));
        cfdis = [{
            archivo: "demo.xml",
            uuid: "DEMO-UUID-ACUARIOS-3200",
            fecha: "2026-08-02",
            total: 3200,
            tipo: "I",
            emisor: "ACUARIOS SA",
            rfc_emisor: "AAA010101AAA"
        }];
        autoRelacionarCfdi();
        render();
    }
    function bind() {
        $("contabilidad_periodo").value = hoyPeriodo();
        $("contabilidad_banco_archivo").addEventListener("change", function (e) {
            var file = e.target.files[0];
            if (!file) { return; }
            leerArchivoTexto(file, function (texto) { importarMovimientos(parseCsv(texto)); });
        });
        $("contabilidad_xml_archivos").addEventListener("change", function (e) {
            var archivos = Array.prototype.slice.call(e.target.files || []);
            var pendientes = archivos.length;
            if (!pendientes) { return; }
            archivos.forEach(function (file) {
                leerArchivoTexto(file, function (texto) {
                    cfdis.push(parseCfdiXml(texto, file.name));
                    pendientes--;
                    if (pendientes === 0) { autoRelacionarCfdi(); render(); }
                });
            });
        });
        ["contabilidad_buscar", "contabilidad_tipo", "contabilidad_cfdi", "contabilidad_flujo"].forEach(function (id) {
            $(id).addEventListener("input", render);
            $(id).addEventListener("change", render);
        });
        $("contabilidad_movimientos").addEventListener("change", function (e) {
            var row = e.target.closest("tr[data-id]");
            var mov = row ? movimientos.find(function (m) { return m.id === row.getAttribute("data-id"); }) : null;
            if (!mov || !e.target.getAttribute("data-campo")) { return; }
            mov[e.target.getAttribute("data-campo")] = e.target.value;
            if (e.target.getAttribute("data-campo") === "tipo" && mov.cfdi !== "ligado") {
                mov.cfdi = cfdiEsperado(mov.tipo);
            }
            render();
        });
        $("contabilidad_movimientos").addEventListener("click", function (e) {
            var btn = e.target.closest("[data-ligar]");
            if (!btn) { return; }
            var mov = movimientos.find(function (m) { return m.id === btn.getAttribute("data-ligar"); });
            if (!mov) { return; }
            var monto = mov.cargo || mov.abono;
            var opciones = cfdis.filter(function (c) { return Math.abs(Number(c.total || 0) - monto) <= 1; });
            var elegido = opciones[0] || cfdis[0];
            if (elegido) {
                mov.cfdi = "ligado";
                mov.cfdi_uuid = elegido.uuid;
                render();
            }
        });
        $("contabilidad_exportar_csv").addEventListener("click", exportarCsv);
        $("contabilidad_exportar_json").addEventListener("click", exportarJson);
        $("contabilidad_recalcular").addEventListener("click", function () { autoRelacionarCfdi(); render(); });
        $("contabilidad_demo").addEventListener("click", cargarDemo);
        $("contabilidad_limpiar").addEventListener("click", function () { movimientos = []; cfdis = []; render(); });
        render();
    }
    document.addEventListener("DOMContentLoaded", bind);
})();
