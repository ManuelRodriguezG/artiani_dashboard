"use strict";
(function () {
    var movimientos = [];
    var cfdis = [];
    var estadosCuenta = [];
    var filasCrudas = [];
    var matrizCruda = [];
    var encabezadosCrudos = [];
    var archivoCrudoNombre = "";
    var archivoCrudoHoja = "";
    var estadoCuentaActualId = "";
    var estadoSeleccionadoId = "";
    var seleccionados = {};
    var STORAGE_KEY = "erp_contabilidad_cierres_local_v1";

    var tiposMovimiento = ["egreso", "ingreso"];
    var actividades = ["negocio", "programacion", "personal", "publicidad", "inversion", "transpaso"];
    var formasPago = ["efectivo", "tarjeta_debito", "tarjeta_credito", "transferencia", "cheque", "comision_bancaria"];
    var categorias = ["venta", "compra_mercancia", "gasto_operativo", "servicio", "nomina", "impuestos", "renta", "publicidad", "software", "banco_comision", "inversion", "personal", "por_definir"];
    var cuentasAuxiliares = ["Efectivo", "Tarjeta de credito", "Tarjeta debito", "Transferencia"];

    /**
     * IA: Codex GPT-5 | Fecha: 2026-08-29
     * Proposito: controlar el ambiente local de clasificacion mensual sin persistir datos.
     * Impacto: Contabilidad; prepara movimientos bancarios y XML para revision del contador.
     * Contrato: procesa CSV/TXT/XML en navegador y exporta CSV/JSON con clasificacion editable.
     */
    function $(id) { return document.getElementById(id); }
    function escapeHtml(value) { var div = document.createElement("div"); div.textContent = value == null ? "" : String(value); return div.innerHTML; }
    function money(value) { return Number(value || 0).toLocaleString("es-MX", {style: "currency", currency: "MXN"}); }
    function numero(value) {
        if (value == null) { return 0; }
        var limpio = String(value).replace(/\s/g, "").replace(/\$/g, "").replace(/,/g, "");
        var n = parseFloat(limpio);
        if (isNaN(n)) { return 0; }
        return Math.abs(n) < 0.000001 ? 0 : n;
    }
    function hoyPeriodo() {
        var d = new Date();
        return d.getFullYear() + "-" + String(d.getMonth() + 1).padStart(2, "0");
    }
    function periodoCierreActual() {
        return ($("contabilidad_periodo") && $("contabilidad_periodo").value) || hoyPeriodo();
    }
    function periodoEstadoCuentaActual() {
        return ($("contabilidad_estado_periodo") && $("contabilidad_estado_periodo").value) || periodoCierreActual();
    }
    function fijarPeriodo(periodo) {
        var valor = periodo || hoyPeriodo();
        if ($("contabilidad_periodo")) { $("contabilidad_periodo").value = valor; }
        if ($("contabilidad_estado_periodo")) { $("contabilidad_estado_periodo").value = valor; }
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
    function leerCierresGuardados() {
        try {
            var raw = localStorage.getItem(STORAGE_KEY);
            var data = raw ? JSON.parse(raw) : [];
            return Array.isArray(data) ? data : [];
        } catch (e) {
            return [];
        }
    }
    function escribirCierresGuardados(cierres) {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(cierres || []));
    }
    function snapshotCierreActual() {
        var periodo = periodoCierreActual();
        return {
            id: periodo,
            periodo: periodo,
            cuenta_base: $("contabilidad_cuenta").value || "",
            actualizado: new Date().toISOString(),
            estados_cuenta: estadosCuenta,
            movimientos: movimientos,
            cfdis: cfdis
        };
    }
    function guardarCierreLocal() {
        if (!movimientos.length) {
            mostrarError("Carga y clasifica movimientos antes de guardar el cierre.");
            return;
        }
        var cierre = snapshotCierreActual();
        var cierres = leerCierresGuardados().filter(function (item) { return item.id !== cierre.id; });
        cierres.unshift(cierre);
        escribirCierresGuardados(cierres.slice(0, 24));
        renderGuardados();
        if (window.Swal) {
            Swal.fire({text: "Cierre guardado en este navegador.", icon: "success", timer: 1400, showConfirmButton: false});
        }
    }
    function cargarCierreLocal(id) {
        var cierre = leerCierresGuardados().find(function (item) { return item.id === id; });
        if (!cierre) {
            mostrarError("No encontre ese cierre guardado.");
            return;
        }
        fijarPeriodo(cierre.periodo || hoyPeriodo());
        $("contabilidad_cuenta").value = cierre.cuenta_base || "";
        estadosCuenta = Array.isArray(cierre.estados_cuenta) ? cierre.estados_cuenta : [];
        movimientos = Array.isArray(cierre.movimientos) ? cierre.movimientos : [];
        cfdis = Array.isArray(cierre.cfdis) ? cierre.cfdis : [];
        normalizarMovimientosLegacy();
        normalizarCfdisLegacy();
        filasCrudas = [];
        matrizCruda = [];
        encabezadosCrudos = [];
        archivoCrudoNombre = "";
        archivoCrudoHoja = "";
        estadoSeleccionadoId = "";
        seleccionados = {};
        render();
    }
    function restaurarCierre(data) {
        if (!data || !Array.isArray(data.movimientos)) {
            mostrarError("El JSON no parece ser un cierre contable exportado.");
            return;
        }
        fijarPeriodo(data.periodo || hoyPeriodo());
        $("contabilidad_cuenta").value = data.cuenta || data.cuenta_base || "";
        estadosCuenta = Array.isArray(data.estados_cuenta) ? data.estados_cuenta : [];
        movimientos = data.movimientos;
        cfdis = Array.isArray(data.cfdis) ? data.cfdis : [];
        normalizarMovimientosLegacy();
        normalizarCfdisLegacy();
        filasCrudas = [];
        matrizCruda = [];
        encabezadosCrudos = [];
        archivoCrudoNombre = "";
        archivoCrudoHoja = "";
        seleccionados = {};
        render();
    }
    function eliminarCierreLocal(id) {
        escribirCierresGuardados(leerCierresGuardados().filter(function (item) { return item.id !== id; }));
        renderGuardados();
    }
    function csvEscape(value) {
        var s = value == null ? "" : String(value);
        return /[",\r\n]/.test(s) ? '"' + s.replace(/"/g, '""') + '"' : s;
    }
    function label(value) { return String(value || "").replace(/_/g, " "); }
    function satFormaPagoOperativa(clave) {
        var mapa = {
            "01": "efectivo",
            "02": "cheque",
            "03": "transferencia",
            "04": "tarjeta_credito",
            "28": "tarjeta_debito",
            "29": "tarjeta_debito"
        };
        return mapa[String(clave || "").padStart(2, "0")] || "";
    }
    function cuentaPorFormaPago(forma, fallback) {
        if (forma === "efectivo") { return "Efectivo"; }
        if (forma === "tarjeta_credito") { return "Tarjeta de credito"; }
        if (forma === "tarjeta_debito") { return "Tarjeta debito"; }
        if (forma === "transferencia") { return fallback || "Transferencia"; }
        return fallback || "Efectivo";
    }
    function options(valores, actual) {
        return valores.map(function (v) {
            return "<option value=\"" + v + "\"" + (v === actual ? " selected" : "") + ">" + label(v) + "</option>";
        }).join("");
    }
    function badge(texto, tipo) {
        return "<span class=\"badge badge-light-" + (tipo || "primary") + "\">" + escapeHtml(texto) + "</span>";
    }
    function requestXlsx(file) {
        var form = new FormData();
        form.append("archivo", file);
        return fetch("/contabilidad/importar_estado_cuenta_erp", {
            method: "POST",
            credentials: "same-origin",
            headers: {"X-CSRF-Token": window.ERP_CSRF_TOKEN || ""},
            body: form
        }).then(function (response) { return response.json(); });
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-08-29
     * Proposito: leer estados de cuenta con separadores comunes para no amarrar el MVP a un banco.
     * Impacto: Contabilidad; habilita pruebas con CSV/TXT reales antes de crear importadores por banco.
     * Contrato: la primera fila debe contener encabezados detectables como fecha/concepto/cargo/abono/importe.
     */
    function parseCsv(texto) {
        var matriz = parseCsvMatriz(texto);
        if (!matriz.length) { return []; }
        var headers = matriz.shift().map(function (h) { return normalizar(h); });
        return matriz.map(function (fila) {
            var obj = {};
            headers.forEach(function (h, i) { obj[h] = fila[i] || ""; });
            return obj;
        });
    }
    function parseCsvMatriz(texto) {
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
        var ancho = filas.reduce(function (max, fila) { return Math.max(max, fila.length); }, 0);
        return filas.map(function (fila) {
            while (fila.length < ancho) { fila.push(""); }
            return fila;
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
    function sugerirTipo(concepto, cargo, abono) {
        if (abono > 0) { return "ingreso"; }
        return "egreso";
    }
    function sugerirActividad(concepto, tipo) {
        var c = normalizar(concepto);
        if (/traspaso|transpaso|transferencia entre|cuenta propia|spei recibido propio|pago tarjeta|tarjeta credito/.test(c)) { return "transpaso"; }
        if (/personal|super|restaurant|uber|netflix|spotify|farmacia/.test(c)) { return "personal"; }
        if (/inversion|gbm|cetes|acciones|fondo|broker/.test(c)) { return "inversion"; }
        if (/facebook|meta|google ads|publicidad|mercado ads|marketing/.test(c)) { return "publicidad"; }
        if (/host|dominio|servidor|software|openai|github|cloud|programacion|desarrollo/.test(c)) { return "programacion"; }
        return "negocio";
    }
    function sugerirFormaPago(concepto, tipo) {
        var c = normalizar(concepto);
        if (/efectivo|retiro cajero|disposicion/.test(c)) { return "efectivo"; }
        if (/tdc|tarjeta credito|pago tarjeta|visa credito|mastercard credito/.test(c)) { return "tarjeta_credito"; }
        if (/debito|tarjeta|tpv|terminal/.test(c)) { return "tarjeta_debito"; }
        if (/cheque/.test(c)) { return "cheque"; }
        if (/comision|manejo de cuenta|iva comision/.test(c)) { return "comision_bancaria"; }
        if (/spei|transferencia|traspaso|transpaso/.test(c)) { return "transferencia"; }
        return "transferencia";
    }
    function sugerirCategoria(concepto, tipo, actividad) {
        var c = normalizar(concepto);
        if (actividad === "personal") { return "personal"; }
        if (actividad === "inversion") { return "inversion"; }
        if (tipo === "ingreso") { return "venta"; }
        if (/sat|imss|infonavit|isr|iva|impuesto|tesoreria/.test(c)) { return "impuestos"; }
        if (/proveedor|compra|factura|mercancia|mayoreo|distribuid/.test(c)) { return "compra_mercancia"; }
        if (/renta|arrendamiento/.test(c)) { return "renta"; }
        if (/nomina|sueldo|salario/.test(c)) { return "nomina"; }
        if (/facebook|meta|google ads|publicidad|mercado ads/.test(c)) { return "publicidad"; }
        if (/host|dominio|software|openai|github|cloud|servidor/.test(c)) { return "software"; }
        if (/cfe|luz|telefono|internet|agua|servicio/.test(c)) { return "servicio"; }
        if (/comision|manejo de cuenta|iva comision/.test(c)) { return "banco_comision"; }
        return "gasto_operativo";
    }
    function cfdiEsperado(mov) {
        if (mov.tipo_movimiento !== "egreso") { return "no_aplica"; }
        if (["personal", "inversion", "transpaso"].indexOf(mov.actividad) >= 0) { return "no_aplica"; }
        if (["impuestos", "banco_comision"].indexOf(mov.categoria) >= 0) { return "no_aplica"; }
        return "pendiente";
    }
    function normalizarMovimientoLegacy(mov) {
        if (!mov) { return mov; }
        if (mov.tipo_movimiento === "gasto") {
            mov.tipo_movimiento = "egreso";
        }
        if (mov.tipo_movimiento === "transpaso") {
            mov.tipo_movimiento = "egreso";
            mov.actividad = "transpaso";
        }
        if (mov.actividad === "interno") {
            mov.actividad = "transpaso";
        }
        if (tiposMovimiento.indexOf(mov.tipo_movimiento) < 0) {
            mov.tipo_movimiento = "egreso";
        }
        if (actividades.indexOf(mov.actividad) < 0) {
            mov.actividad = "negocio";
        }
        if (!mov.periodo) {
            mov.periodo = periodoEstadoPorId(mov.estado_cuenta_id);
        }
        return mov;
    }
    function normalizarMovimientosLegacy() {
        movimientos = movimientos.map(normalizarMovimientoLegacy);
    }
    function normalizarCfdiLegacy(cfdi) {
        if (!cfdi) { return cfdi; }
        cfdi.id = cfdi.id || cfdi.uuid || ("cfdi-" + Date.now() + "-" + Math.random().toString(16).slice(2));
        cfdi.periodo = cfdi.periodo || (cfdi.fecha ? cfdi.fecha.slice(0, 7) : periodoCierreActual());
        cfdi.categoria = cfdi.categoria || cfdi.clasificacion || ($("contabilidad_cfdi_categoria_default") ? $("contabilidad_cfdi_categoria_default").value : "gasto_operativo");
        cfdi.actividad = cfdi.actividad || "negocio";
        cfdi.forma_pago = cfdi.forma_pago || satFormaPagoOperativa(cfdi.forma_pago_sat);
        cfdi.cuenta_pago = cfdi.cuenta_pago || cuentaPorFormaPago(cfdi.forma_pago, $("contabilidad_cfdi_cuenta_default") ? $("contabilidad_cfdi_cuenta_default").value : "Efectivo");
        cfdi.movimiento_relacionado = cfdi.movimiento_relacionado || "";
        cfdi.estatus_relacion = cfdi.movimiento_relacionado ? "ligado" : (cfdi.estatus_relacion || "pendiente");
        return cfdi;
    }
    function normalizarCfdisLegacy() {
        cfdis = cfdis.map(normalizarCfdiLegacy);
    }
    function leerArchivoTexto(file, cb) {
        var reader = new FileReader();
        reader.onload = function () { cb(String(reader.result || "")); };
        reader.readAsText(file, "UTF-8");
    }
    function mostrarError(texto) {
        if (window.Swal) {
            Swal.fire({text: texto, icon: "warning", confirmButtonText: "Aceptar"});
        } else {
            alert(texto);
        }
    }
    function confirmarAccion(texto, cb) {
        if (window.Swal) {
            Swal.fire({
                text: texto,
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Eliminar",
                cancelButtonText: "Cancelar"
            }).then(function (result) {
                if (result.isConfirmed) { cb(); }
            });
            return;
        }
        if (confirm(texto)) { cb(); }
    }
    function prepararMapeo(rows) {
        filasCrudas = rows || [];
        matrizCruda = [];
        encabezadosCrudos = filasCrudas.length ? Object.keys(filasCrudas[0]) : [];
        if (!encabezadosCrudos.length) {
            mostrarError("No encontre columnas legibles en el archivo.");
            return;
        }
        renderMapeo();
    }
    function encabezadoUnico(valor, indice, usados) {
        var base = String(valor || "").trim() || "Columna " + (indice + 1);
        var nombre = base;
        var n = 2;
        while (usados[nombre]) {
            nombre = base + " " + n;
            n++;
        }
        usados[nombre] = true;
        return nombre;
    }
    function reconstruirFilasDesdeMatriz() {
        var select = $("contabilidad_mapeo_fila_header");
        var headerIndex = select ? Number(select.value || 0) : 0;
        var usados = {};
        encabezadosCrudos = (matrizCruda[headerIndex] || []).map(function (h, i) { return encabezadoUnico(h, i, usados); });
        filasCrudas = matrizCruda.slice(headerIndex + 1).map(function (fila) {
            var obj = {};
            encabezadosCrudos.forEach(function (h, i) { obj[h] = fila[i] || ""; });
            return obj;
        }).filter(function (row) {
            return Object.keys(row).some(function (key) { return String(row[key] || "").trim() !== ""; });
        });
    }
    function prepararMapeoMatriz(matriz) {
        matrizCruda = matriz || [];
        if (!matrizCruda.length) {
            mostrarError("No encontre filas legibles en el archivo.");
            return;
        }
        var select = $("contabilidad_mapeo_fila_header");
        var opciones = matrizCruda.slice(0, Math.min(12, matrizCruda.length)).map(function (fila, i) {
            var muestra = fila.filter(function (x) { return String(x || "").trim() !== ""; }).slice(0, 3).join(" / ");
            return "<option value=\"" + i + "\">Fila " + (i + 1) + (muestra ? " - " + escapeHtml(muestra).slice(0, 70) : "") + "</option>";
        }).join("");
        select.innerHTML = opciones || "<option value=\"0\">Fila 1</option>";
        var mejor = 0;
        var scoreMejor = -1;
        matrizCruda.slice(0, Math.min(18, matrizCruda.length)).forEach(function (fila, i) {
            var texto = normalizar(fila.join(" "));
            var score = ["fecha", "concepto", "descripcion", "monto", "importe", "cuenta", "movimiento", "actividad"].reduce(function (s, palabra) {
                return s + (texto.indexOf(palabra) >= 0 ? 1 : 0);
            }, 0);
            if (/fecha/.test(texto) && /concepto/.test(texto) && /monto|importe/.test(texto) && !/saldo posterior/.test(texto)) {
                score += 5;
            }
            if (score > scoreMejor) { scoreMejor = score; mejor = i; }
        });
        select.value = String(mejor);
        reconstruirFilasDesdeMatriz();
        if (!encabezadosCrudos.length) {
            mostrarError("No encontre columnas legibles en el archivo.");
            return;
        }
        renderMapeo();
    }
    function sugerirColumna(patrones) {
        var normalizados = encabezadosCrudos.map(function (h) { return {original: h, n: normalizar(h)}; });
        for (var i = 0; i < patrones.length; i++) {
            var patron = normalizar(patrones[i]);
            var match = normalizados.find(function (h) { return h.n.indexOf(patron) >= 0; });
            if (match) { return match.original; }
        }
        return "";
    }
    function renderSelectMapeo(id, seleccionado) {
        var html = "<option value=\"\">No usar</option>" + encabezadosCrudos.map(function (h) {
            return "<option value=\"" + escapeHtml(h) + "\"" + (h === seleccionado ? " selected" : "") + ">" + escapeHtml(h) + "</option>";
        }).join("");
        $(id).innerHTML = html;
    }
    function renderMapeo() {
        if (matrizCruda.length) { reconstruirFilasDesdeMatriz(); }
        $("contabilidad_mapeo_subtitulo").textContent = (archivoCrudoNombre || "Archivo original") + (archivoCrudoHoja ? " | Hoja: " + archivoCrudoHoja : "");
        $("contabilidad_mapeo_resumen").innerHTML =
            badge("Filas: " + filasCrudas.length, "info") +
            badge("Columnas: " + encabezadosCrudos.length, "warning") +
            (matrizCruda.length ? badge("Encabezado editable", "success") : "") +
            badge("Salida: 7 columnas utiles", "primary");
        renderSelectMapeo("map_fecha", sugerirColumna(["fecha", "date", "operation date"]));
        renderSelectMapeo("map_concepto", sugerirColumna(["descripcion", "description", "concepto", "detalle", "movimiento"]));
        renderSelectMapeo("map_monto", sugerirColumna(["monto", "importe", "amount", "total operacion", "importe operacion"]));
        renderSelectMapeo("map_movimiento", sugerirColumna(["movimiento", "tipo", "transaction type"]));
        renderSelectMapeo("map_actividad", sugerirColumna(["actividad"]));
        renderSelectMapeo("map_cuenta", sugerirColumna(["cuenta", "banco", "account"]));
        renderSelectMapeo("map_folio", sugerirColumna(["folio", "factura", "referencia", "reference", "uuid"]));
        renderPreviewMapeo();
        bootstrap.Modal.getOrCreateInstance($("contabilidad_mapeo_modal")).show();
    }

    function columnasPreviewMapeo() {
        var ids = ["map_fecha", "map_concepto", "map_monto", "map_movimiento", "map_actividad", "map_cuenta", "map_folio"];
        var columnas = [];
        ids.forEach(function (id) {
            var columna = $(id).value;
            if (columna && columnas.indexOf(columna) < 0) {
                columnas.push(columna);
            }
        });
        if (!columnas.length) {
            columnas = encabezadosCrudos.slice(0, 8);
        }
        return columnas;
    }
    function renderPreviewMapeo() {
        if (matrizCruda.length) { reconstruirFilasDesdeMatriz(); }
        var columnas = columnasPreviewMapeo();
        var limite = Number($("contabilidad_mapeo_limite").value || 25);
        $("contabilidad_mapeo_preview_head").innerHTML = "<tr class=\"fw-bold text-muted\">" + columnas.map(function (h) {
            return "<th class=\"text-nowrap\">" + escapeHtml(h) + "</th>";
        }).join("") + "</tr>";
        $("contabilidad_mapeo_preview_body").innerHTML = filasCrudas.slice(0, limite).map(function (row) {
            return "<tr>" + columnas.map(function (h) {
                return "<td class=\"text-nowrap\">" + escapeHtml(row[h] || "") + "</td>";
            }).join("") + "</tr>";
        }).join("") || "<tr><td class=\"text-center text-muted py-6\" colspan=\"" + Math.max(columnas.length, 1) + "\">Sin filas para vista previa</td></tr>";
    }
    function valorMapeado(row, id) {
        var columna = $(id).value;
        return columna ? (row[columna] || "") : "";
    }
    function normalizarTipoMapeado(valor, concepto, cargo, abono) {
        var v = normalizar(valor);
        if (/ingreso|deposito|cobro/.test(v)) { return "ingreso"; }
        if (/egreso|gasto|cargo|pago|retiro|traspaso|transpaso|transferencia interna|interno/.test(v)) { return "egreso"; }
        return "egreso";
    }
    function normalizarActividadMapeada(valor, concepto, tipo) {
        var v = normalizar(valor);
        if (/negocio/.test(v)) { return "negocio"; }
        if (/programacion|desarrollo|software/.test(v)) { return "programacion"; }
        if (/personal/.test(v)) { return "personal"; }
        if (/publicidad|ads|marketing/.test(v)) { return "publicidad"; }
        if (/traspaso|transpaso|interno/.test(v)) { return "transpaso"; }
        return sugerirActividad(concepto, tipo);
    }
    function normalizarFechaMapeada(valor) {
        var raw = String(valor || "").trim();
        if (/^\d{5}(\.\d+)?$/.test(raw)) {
            var base = new Date(Date.UTC(1899, 11, 30));
            base.setUTCDate(base.getUTCDate() + Math.floor(Number(raw)));
            return base.toISOString().slice(0, 10);
        }
        var partes = raw.match(/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{2,4})/);
        if (partes) {
            var anio = partes[3].length === 2 ? "20" + partes[3] : partes[3];
            return anio + "-" + partes[2].padStart(2, "0") + "-" + partes[1].padStart(2, "0");
        }
        var meses = {ene: "01", enero: "01", feb: "02", febrero: "02", mar: "03", marzo: "03", abr: "04", abril: "04", may: "05", mayo: "05", jun: "06", junio: "06", jul: "07", julio: "07", ago: "08", agosto: "08", sep: "09", sept: "09", septiembre: "09", oct: "10", octubre: "10", nov: "11", noviembre: "11", dic: "12", diciembre: "12"};
        var textoMes = normalizar(raw).replace(/\./g, "");
        var partesMes = textoMes.match(/^(\d{1,2})[\/\-\s]+([a-z]+)[\/\-\s]+(\d{2,4})/);
        if (partesMes && meses[partesMes[2]]) {
            var anioMes = partesMes[3].length === 2 ? "20" + partesMes[3] : partesMes[3];
            return anioMes + "-" + meses[partesMes[2]] + "-" + partesMes[1].padStart(2, "0");
        }
        return raw.slice(0, 10);
    }
    function registrarEstadoCuenta(cantidadMovimientos) {
        var cuenta = $("contabilidad_cuenta").value || "Cuenta sin nombre";
        var periodo = periodoEstadoCuentaActual();
        fijarPeriodo(periodo);
        estadoCuentaActualId = "edo-" + Date.now() + "-" + estadosCuenta.length;
        estadosCuenta.push({
            id: estadoCuentaActualId,
            archivo: archivoCrudoNombre || "captura_manual.csv",
            periodo: periodo,
            cuenta: cuenta,
            filas: filasCrudas.length,
            columnas: encabezadosCrudos.length,
            hoja: archivoCrudoHoja || "",
            movimientos: cantidadMovimientos || 0,
            fecha_carga: new Date().toLocaleString("es-MX")
        });
        estadoSeleccionadoId = estadoCuentaActualId;
        return estadoCuentaActualId;
    }
    function periodoEstadoPorId(estadoId) {
        var estado = estadosCuenta.find(function (edo) { return edo.id === estadoId; });
        return (estado && estado.periodo) || periodoCierreActual();
    }
    function importarMovimientos(rows) {
        var cuentaDefault = $("contabilidad_cuenta").value || "";
        filasCrudas = rows || [];
        encabezadosCrudos = filasCrudas.length ? Object.keys(filasCrudas[0]) : [];
        var estadoId = registrarEstadoCuenta(rows.length);
        var nuevos = rows.map(function (row, idx) {
            var cargo = numero(valorPorAlias(row, ["cargo", "retiro", "egreso", "debito", "importe cargo"]));
            var abono = numero(valorPorAlias(row, ["abono", "deposito", "ingreso", "credito", "importe abono"]));
            var importe = numero(valorPorAlias(row, ["importe", "monto", "cantidad"]));
            if (!cargo && !abono && importe) {
                if (importe < 0) { cargo = Math.abs(importe); } else { abono = importe; }
            }
            var concepto = valorPorAlias(row, ["concepto", "descripcion", "detalle", "referencia", "movimiento"]) || "Movimiento sin concepto";
            var tipo = sugerirTipo(concepto, cargo, abono);
            var actividad = sugerirActividad(concepto, tipo);
            var formaPago = sugerirFormaPago(concepto, tipo);
            var categoria = sugerirCategoria(concepto, tipo, actividad);
            var mov = {
                id: "mov-" + Date.now() + "-" + idx,
                fecha: valorPorAlias(row, ["fecha", "operacion", "aplicacion"]) || "",
                cuenta: valorPorAlias(row, ["cuenta", "banco"]) || cuentaDefault,
                concepto: concepto,
                referencia: valorPorAlias(row, ["referencia", "folio", "rastreo", "autorizacion"]) || "",
                cargo: cargo,
                abono: abono,
                monto: abono > 0 ? abono : cargo,
                periodo: periodoEstadoPorId(estadoId),
                tipo_movimiento: tipo,
                actividad: actividad,
                forma_pago: formaPago,
                categoria: categoria,
                cfdi: "",
                cfdi_uuid: "",
                cfdi_sugerencia: "",
                notas: ""
            };
            mov.estado_cuenta_id = estadoId;
            mov.archivo_estado_cuenta = archivoCrudoNombre || "demo.csv";
            mov.cfdi = cfdiEsperado(mov);
            return normalizarMovimientoLegacy(mov);
        });
        movimientos = movimientos.concat(nuevos);
        autoRelacionarCfdi();
        render();
        var modal = bootstrap.Modal.getInstance($("contabilidad_mapeo_modal"));
        if (modal) {
            modal.hide();
        }
    }
    function importarDesdeMapeo() {
        var cuentaDefault = $("contabilidad_cuenta").value || "";
        if (matrizCruda.length) { reconstruirFilasDesdeMatriz(); }
        if (!$("map_fecha").value || !$("map_concepto").value || !$("map_monto").value) {
            mostrarError("Mapea al menos fecha, descripcion/concepto y monto.");
            return;
        }
        var estadoId = registrarEstadoCuenta(filasCrudas.length);
        var nuevos = filasCrudas.map(function (row, idx) {
            var monto = numero(valorMapeado(row, "map_monto"));
            var tipoMapeado = normalizarTipoMapeado(valorMapeado(row, "map_movimiento"), "", 0, 0);
            var cargo = tipoMapeado === "ingreso" ? 0 : Math.abs(monto);
            var abono = tipoMapeado === "ingreso" ? Math.abs(monto) : 0;
            var concepto = valorMapeado(row, "map_concepto") || "Movimiento sin concepto";
            var tipo = normalizarTipoMapeado(valorMapeado(row, "map_movimiento"), concepto, cargo, abono);
            var actividad = normalizarActividadMapeada(valorMapeado(row, "map_actividad"), concepto, tipo);
            var formaPago = sugerirFormaPago(concepto, tipo);
            var categoria = sugerirCategoria(concepto, tipo, actividad);
            var mov = {
                id: "mov-" + Date.now() + "-" + idx,
                fecha: normalizarFechaMapeada(valorMapeado(row, "map_fecha")),
                cuenta: valorMapeado(row, "map_cuenta") || cuentaDefault,
                concepto: concepto,
                referencia: valorMapeado(row, "map_folio"),
                cargo: cargo,
                abono: abono,
                monto: Math.abs(monto),
                periodo: periodoEstadoPorId(estadoId),
                tipo_movimiento: tipo,
                actividad: actividad,
                forma_pago: formaPago,
                categoria: categoria,
                cfdi: "",
                cfdi_uuid: "",
                cfdi_sugerencia: "",
                notas: ""
            };
            mov.estado_cuenta_id = estadoId;
            mov.archivo_estado_cuenta = archivoCrudoNombre || "";
            mov.cfdi = cfdiEsperado(mov);
            return normalizarMovimientoLegacy(mov);
        }).filter(function (m) { return m.fecha || (m.concepto && m.concepto !== "Movimiento sin concepto") || m.monto; });
        estadosCuenta[estadosCuenta.length - 1].movimientos = nuevos.length;
        movimientos = movimientos.concat(nuevos);
        autoRelacionarCfdi();
        render();
        var modal = bootstrap.Modal.getInstance($("contabilidad_mapeo_modal"));
        if (modal) {
            modal.hide();
        }
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-08-29
     * Proposito: extraer metadatos principales de XML CFDI para sugerir su movimiento bancario.
     * Impacto: Contabilidad; ayuda a corroborar compras/gastos sin validar fiscalmente el comprobante.
     * Contrato: acepta CFDI 3.3/4.0 y devuelve UUID, RFCs, total, fecha, serie y folio.
     */
    function parseCfdiXml(texto, nombre) {
        var xml = new DOMParser().parseFromString(texto, "text/xml");
        var comprobante = xml.getElementsByTagNameNS("*", "Comprobante")[0] || xml.getElementsByTagName("cfdi:Comprobante")[0] || xml.documentElement;
        var emisor = xml.getElementsByTagNameNS("*", "Emisor")[0] || xml.getElementsByTagName("cfdi:Emisor")[0];
        var receptor = xml.getElementsByTagNameNS("*", "Receptor")[0] || xml.getElementsByTagName("cfdi:Receptor")[0];
        var timbre = xml.getElementsByTagNameNS("*", "TimbreFiscalDigital")[0] || xml.getElementsByTagName("tfd:TimbreFiscalDigital")[0];
        var fecha = comprobante ? (comprobante.getAttribute("Fecha") || "").slice(0, 10) : "";
        var formaSat = comprobante ? (comprobante.getAttribute("FormaPago") || "") : "";
        var forma = satFormaPagoOperativa(formaSat);
        return normalizarCfdiLegacy({
            archivo: nombre,
            uuid: timbre ? (timbre.getAttribute("UUID") || "") : "",
            fecha: fecha,
            periodo: fecha ? fecha.slice(0, 7) : periodoCierreActual(),
            total: numero(comprobante ? comprobante.getAttribute("Total") : 0),
            tipo: comprobante ? (comprobante.getAttribute("TipoDeComprobante") || "") : "",
            metodo_pago: comprobante ? (comprobante.getAttribute("MetodoPago") || "") : "",
            forma_pago_sat: formaSat,
            forma_pago: forma,
            cuenta_pago: cuentaPorFormaPago(forma, $("contabilidad_cfdi_cuenta_default") ? $("contabilidad_cfdi_cuenta_default").value : "Efectivo"),
            categoria: $("contabilidad_cfdi_categoria_default") ? $("contabilidad_cfdi_categoria_default").value : "gasto_operativo",
            actividad: "negocio",
            estatus_relacion: "pendiente",
            movimiento_relacionado: "",
            serie: comprobante ? (comprobante.getAttribute("Serie") || "") : "",
            folio: comprobante ? (comprobante.getAttribute("Folio") || "") : "",
            rfc_emisor: emisor ? (emisor.getAttribute("Rfc") || "") : "",
            emisor: emisor ? (emisor.getAttribute("Nombre") || "") : "",
            rfc_receptor: receptor ? (receptor.getAttribute("Rfc") || "") : ""
        });
    }
    function scoreCfdi(mov, cfdi) {
        var monto = mov.cargo > 0 ? mov.cargo : mov.abono;
        var score = 0;
        if (Math.abs(Number(cfdi.total || 0) - monto) <= 0.99) { score += 70; }
        else if (Math.abs(Number(cfdi.total || 0) - monto) <= 5) { score += 40; }
        if (mov.fecha && cfdi.fecha && mov.fecha === cfdi.fecha) { score += 15; }
        if (normalizar(mov.concepto).indexOf(normalizar(cfdi.rfc_emisor)) >= 0) { score += 10; }
        if (normalizar(mov.concepto).indexOf(normalizar(cfdi.emisor).slice(0, 10)) >= 0) { score += 10; }
        return score;
    }
    function autoRelacionarCfdi() {
        movimientos.forEach(function (mov) {
            if (mov.cfdi === "ligado" || mov.tipo_movimiento !== "egreso" || mov.actividad === "transpaso") { return; }
            var candidatos = cfdis.map(function (cfdi) {
                return {cfdi: cfdi, score: scoreCfdi(mov, cfdi)};
            }).filter(function (x) { return x.score >= 40; }).sort(function (a, b) { return b.score - a.score; });
            if (candidatos.length) {
                mov.cfdi_sugerencia = candidatos[0].cfdi.uuid;
                if (candidatos[0].score >= 70 && mov.cfdi === "pendiente") {
                    mov.cfdi = "ligado";
                    mov.cfdi_uuid = candidatos[0].cfdi.uuid;
                    candidatos[0].cfdi.movimiento_relacionado = mov.id;
                    candidatos[0].cfdi.estatus_relacion = "ligado";
                }
            }
        });
    }
    function cfdiPorId(id) {
        return cfdis.find(function (c) { return c.id === id || c.uuid === id; });
    }
    function ligarCfdiSugerido(id) {
        var cfdi = cfdiPorId(id);
        if (!cfdi) { return; }
        var elegido = movimientos.map(function (mov) { return {mov: mov, score: scoreCfdi(mov, cfdi)}; })
            .filter(function (x) { return x.score >= 40; })
            .sort(function (a, b) { return b.score - a.score; })[0];
        if (!elegido) {
            mostrarError("No encontre un movimiento bancario sugerido para ese CFDI.");
            return;
        }
        elegido.mov.cfdi = "ligado";
        elegido.mov.cfdi_uuid = cfdi.uuid;
        elegido.mov.cfdi_sugerencia = "";
        elegido.mov.categoria = cfdi.categoria || elegido.mov.categoria;
        elegido.mov.actividad = cfdi.actividad || elegido.mov.actividad;
        elegido.mov.forma_pago = cfdi.forma_pago || elegido.mov.forma_pago;
        cfdi.movimiento_relacionado = elegido.mov.id;
        cfdi.estatus_relacion = "ligado";
        render();
    }
    function crearMovimientoDesdeCfdi(id) {
        var cfdi = cfdiPorId(id);
        if (!cfdi) { return; }
        normalizarCfdiLegacy(cfdi);
        var existente = movimientos.find(function (m) { return m.cfdi_uuid === cfdi.uuid; });
        if (existente) {
            mostrarError("Ese CFDI ya tiene un movimiento relacionado.");
            return;
        }
        var mov = normalizarMovimientoLegacy({
            id: "mov-cfdi-" + Date.now() + "-" + movimientos.length,
            fecha: cfdi.fecha || "",
            periodo: cfdi.periodo || periodoCierreActual(),
            cuenta: cfdi.cuenta_pago || "Efectivo",
            concepto: cfdi.emisor || cfdi.rfc_emisor || cfdi.archivo || "CFDI sin emisor",
            referencia: [cfdi.serie, cfdi.folio].filter(Boolean).join("-") || cfdi.uuid,
            cargo: Number(cfdi.total || 0),
            abono: 0,
            monto: Number(cfdi.total || 0),
            tipo_movimiento: "egreso",
            actividad: cfdi.actividad || "negocio",
            forma_pago: cfdi.forma_pago || "efectivo",
            categoria: cfdi.categoria || "gasto_operativo",
            cfdi: "ligado",
            cfdi_uuid: cfdi.uuid,
            cfdi_sugerencia: "",
            archivo_estado_cuenta: "CFDI sin estado de cuenta",
            estado_cuenta_id: "",
            notas: "Movimiento auxiliar creado desde CFDI."
        });
        movimientos.push(mov);
        cfdi.movimiento_relacionado = mov.id;
        cfdi.estatus_relacion = "ligado";
        render();
    }
    function crearMovimientosCfdiSinBanco() {
        var creados = 0;
        cfdis.forEach(function (cfdi) {
            var ligado = movimientos.some(function (m) { return m.cfdi_uuid === cfdi.uuid; });
            if (!ligado) {
                crearMovimientoDesdeCfdi(cfdi.id);
                creados++;
            }
        });
        if (!creados) {
            mostrarError("Todos los CFDI ya tienen movimiento relacionado.");
        }
    }
    function eliminarCfdi(id) {
        confirmarAccion("Eliminar este CFDI cargado de la revision local?", function () {
            var cfdi = cfdiPorId(id);
            cfdis = cfdis.filter(function (c) { return c.id !== id && c.uuid !== id; });
            if (cfdi && cfdi.uuid) {
                movimientos.forEach(function (mov) {
                    if (mov.cfdi_uuid === cfdi.uuid) {
                        mov.cfdi_uuid = "";
                        mov.cfdi = cfdiEsperado(mov);
                    }
                });
            }
            render();
        });
    }
    function filtros() {
        return {
            periodo: periodoCierreActual(),
            q: normalizar($("contabilidad_buscar").value),
            tipo: $("contabilidad_tipo").value,
            actividad: $("contabilidad_actividad").value,
            categoria: $("contabilidad_categoria").value,
            cfdi: $("contabilidad_cfdi").value,
            estado: estadoSeleccionadoId,
            pago: ""
        };
    }
    function visibles() {
        var f = filtros();
        return movimientos.filter(function (m) {
            var texto = normalizar([m.fecha, m.cuenta, m.concepto, m.referencia, m.cfdi_uuid, m.categoria, m.forma_pago, m.cargo, m.abono].join(" "));
            return (!f.q || texto.indexOf(f.q) >= 0) &&
                (!f.periodo || (m.periodo || periodoEstadoPorId(m.estado_cuenta_id)) === f.periodo) &&
                (!f.tipo || m.tipo_movimiento === f.tipo) &&
                (!f.actividad || m.actividad === f.actividad) &&
                (!f.categoria || m.categoria === f.categoria) &&
                (!f.cfdi || m.cfdi === f.cfdi) &&
                (!f.estado || m.estado_cuenta_id === f.estado);
        });
    }
    function idsSeleccionadosValidos() {
        var ids = {};
        movimientos.forEach(function (m) { ids[m.id] = true; });
        Object.keys(seleccionados).forEach(function (id) {
            if (!ids[id]) { delete seleccionados[id]; }
        });
        return Object.keys(seleccionados).filter(function (id) { return seleccionados[id]; });
    }
    function limpiarSeleccion() {
        seleccionados = {};
        render();
    }
    function seleccionarVisibles(activo) {
        visibles().forEach(function (m) {
            if (activo) {
                seleccionados[m.id] = true;
            } else {
                delete seleccionados[m.id];
            }
        });
        render();
    }
    function aplicarMasivo() {
        var ids = idsSeleccionadosValidos();
        if (!ids.length) {
            mostrarError("Selecciona al menos un movimiento.");
            return;
        }
        var cambios = {
            tipo_movimiento: $("masivo_movimiento").value,
            actividad: $("masivo_actividad").value,
            categoria: $("masivo_categoria").value,
            cfdi: $("masivo_cfdi").value,
            cuenta: $("masivo_cuenta").value.trim()
        };
        if (!cambios.tipo_movimiento && !cambios.actividad && !cambios.categoria && !cambios.cfdi && !cambios.cuenta) {
            mostrarError("Elige al menos un campo para aplicar.");
            return;
        }
        movimientos.forEach(function (mov) {
            if (!seleccionados[mov.id]) { return; }
            Object.keys(cambios).forEach(function (campo) {
                if (cambios[campo]) { mov[campo] = cambios[campo]; }
            });
            if (!cambios.cfdi && mov.cfdi !== "ligado") {
                mov.cfdi = cfdiEsperado(mov);
            }
            normalizarMovimientoLegacy(mov);
        });
        ["masivo_movimiento", "masivo_actividad", "masivo_categoria", "masivo_cfdi", "masivo_cuenta"].forEach(function (id) {
            if ($(id).tagName === "SELECT") { $(id).value = ""; } else { $(id).value = ""; }
        });
        render();
    }
    function renderKpis() {
        var totalIngresos = movimientos.reduce(function (s, m) { return s + (m.tipo_movimiento === "ingreso" ? Number(m.monto || 0) : 0); }, 0);
        var totalEgresos = movimientos.reduce(function (s, m) { return s + (m.tipo_movimiento === "egreso" ? Number(m.monto || 0) : 0); }, 0);
        var traspasos = movimientos.filter(function (m) { return m.actividad === "transpaso"; }).length;
        var pendientes = movimientos.filter(function (m) { return m.cfdi === "pendiente"; }).length;
        var data = [
            ["Ingresos", money(totalIngresos), "badge-light-success", "bi-arrow-down-circle"],
            ["Egresos", money(totalEgresos), "badge-light-danger", "bi-arrow-up-circle"],
            ["Traspasos", traspasos, "badge-light-info", "bi-arrow-left-right"],
            ["CFDI pendientes", pendientes, "badge-light-warning", "bi-exclamation-triangle"]
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
        var ids = idsSeleccionadosValidos();
        var visiblesSeleccionados = rows.filter(function (m) { return seleccionados[m.id]; }).length;
        if ($("contabilidad_masivo_total")) {
            $("contabilidad_masivo_total").textContent = ids.length + " seleccionados";
        }
        if ($("contabilidad_select_todos")) {
            $("contabilidad_select_todos").checked = rows.length > 0 && visiblesSeleccionados === rows.length;
            $("contabilidad_select_todos").indeterminate = visiblesSeleccionados > 0 && visiblesSeleccionados < rows.length;
        }
        $("contabilidad_movimientos").innerHTML = rows.map(function (m) {
            var sugerencia = m.cfdi_sugerencia && m.cfdi !== "ligado" ? "<div class=\"text-primary fs-9 mt-1\">Sugerido: " + escapeHtml(m.cfdi_sugerencia) + "</div>" : "";
            return "<tr data-id=\"" + escapeHtml(m.id) + "\">" +
                "<td><input class=\"form-check-input\" type=\"checkbox\" data-seleccionar-mov=\"" + escapeHtml(m.id) + "\"" + (seleccionados[m.id] ? " checked" : "") + "></td>" +
                "<td class=\"text-nowrap\">" + escapeHtml(m.fecha || "-") + "</td>" +
                "<td><div class=\"fw-semibold\">" + escapeHtml(m.concepto) + "</div><div class=\"text-muted fs-8\">" + escapeHtml(m.referencia || "") + "</div></td>" +
                "<td><select class=\"form-select form-select-sm form-select-solid min-w-125px\" data-campo=\"tipo_movimiento\">" + options(tiposMovimiento, m.tipo_movimiento) + "</select></td>" +
                "<td><select class=\"form-select form-select-sm form-select-solid min-w-125px\" data-campo=\"actividad\">" + options(actividades, m.actividad) + "</select></td>" +
                "<td><select class=\"form-select form-select-sm form-select-solid min-w-150px\" data-campo=\"categoria\">" + options(categorias, m.categoria) + "</select></td>" +
                "<td><input class=\"form-control form-control-sm form-control-solid min-w-125px\" data-campo=\"cuenta\" value=\"" + escapeHtml(m.cuenta || "") + "\"></td>" +
                "<td class=\"text-end fw-bold\">" + money(m.monto || 0) + "</td>" +
                "<td><input class=\"form-control form-control-sm form-control-solid min-w-150px\" data-campo=\"referencia\" value=\"" + escapeHtml(m.referencia || "") + "\"></td>" +
                "<td><select class=\"form-select form-select-sm form-select-solid min-w-125px\" data-campo=\"cfdi\">" + options(["ligado", "pendiente", "no_aplica"], m.cfdi) + "</select>" +
                "<div class=\"text-muted fs-9 mt-1\">" + escapeHtml(m.cfdi_uuid || "") + "</div>" + sugerencia + "</td>" +
                "<td class=\"text-end\"><div class=\"d-flex justify-content-end gap-2\">" +
                "<button class=\"btn btn-sm btn-icon btn-light-primary\" title=\"Ligar CFDI sugerido\" type=\"button\" data-ligar=\"" + escapeHtml(m.id) + "\"><i class=\"bi bi-link\"></i></button>" +
                "<button class=\"btn btn-sm btn-icon btn-light-danger\" title=\"Eliminar movimiento\" type=\"button\" data-eliminar-mov=\"" + escapeHtml(m.id) + "\"><i class=\"bi bi-trash3\"></i></button>" +
                "</div></td>" +
                "</tr>";
        }).join("") || "<tr><td colspan=\"11\" class=\"text-center text-muted py-8\">Carga movimientos bancarios para iniciar el cierre.</td></tr>";
    }
    function renderCfdis() {
        if ($("contabilidad_cfdi_total")) {
            $("contabilidad_cfdi_total").textContent = cfdis.length + " CFDI";
        }
        $("contabilidad_cfdis").innerHTML = cfdis.map(function (c) {
            normalizarCfdiLegacy(c);
            var ligado = movimientos.find(function (m) { return m.cfdi_uuid === c.uuid || m.id === c.movimiento_relacionado; });
            var sugerido = movimientos.map(function (mov) { return {mov: mov, score: scoreCfdi(mov, c)}; })
                .filter(function (x) { return x.score >= 40; })
                .sort(function (a, b) { return b.score - a.score; })[0];
            var relacion = ligado ? "Ligado" : (sugerido ? "Sugerido" : "Sin banco");
            return "<tr data-cfdi-id=\"" + escapeHtml(c.id) + "\">" +
                "<td class=\"text-nowrap\">" + escapeHtml(c.fecha || "-") + "</td>" +
                "<td><div class=\"fw-semibold\">" + escapeHtml(c.emisor || c.rfc_emisor || c.archivo) + "</div>" +
                "<div class=\"text-muted fs-9\">" + escapeHtml(c.uuid || "Sin UUID") + "</div></td>" +
                "<td><select class=\"form-select form-select-sm form-select-solid min-w-150px\" data-cfdi-campo=\"categoria\">" + options(categorias, c.categoria) + "</select></td>" +
                "<td><select class=\"form-select form-select-sm form-select-solid min-w-125px\" data-cfdi-campo=\"actividad\">" + options(actividades, c.actividad) + "</select></td>" +
                "<td><select class=\"form-select form-select-sm form-select-solid min-w-150px\" data-cfdi-campo=\"forma_pago\">" + options(formasPago, c.forma_pago) + "</select>" +
                "<div class=\"text-muted fs-9 mt-1\">" + escapeHtml(c.metodo_pago || "-") + " / " + escapeHtml(c.forma_pago_sat || "-") + "</div></td>" +
                "<td><input class=\"form-control form-control-sm form-control-solid min-w-150px\" data-cfdi-campo=\"cuenta_pago\" value=\"" + escapeHtml(c.cuenta_pago || "") + "\"></td>" +
                "<td class=\"text-end fw-bold\">" + money(c.total) + "</td>" +
                "<td><span class=\"badge " + (ligado ? "badge-light-success" : (sugerido ? "badge-light-primary" : "badge-light-warning")) + "\">" + relacion + "</span>" +
                (sugerido && !ligado ? "<div class=\"text-muted fs-9 mt-1\">" + escapeHtml(sugerido.mov.fecha || "") + " | " + money(sugerido.mov.monto) + "</div>" : "") + "</td>" +
                "<td class=\"text-end\"><div class=\"d-flex justify-content-end gap-2\">" +
                "<button class=\"btn btn-sm btn-icon btn-light-primary\" title=\"Ligar sugerido\" type=\"button\" data-ligar-cfdi=\"" + escapeHtml(c.id) + "\"><i class=\"bi bi-link\"></i></button>" +
                "<button class=\"btn btn-sm btn-icon btn-light-success\" title=\"Crear movimiento auxiliar\" type=\"button\" data-crear-mov-cfdi=\"" + escapeHtml(c.id) + "\"><i class=\"bi bi-plus-circle\"></i></button>" +
                "<button class=\"btn btn-sm btn-icon btn-light-danger\" title=\"Eliminar CFDI\" type=\"button\" data-eliminar-cfdi=\"" + escapeHtml(c.id) + "\"><i class=\"bi bi-trash3\"></i></button>" +
                "</div></td></tr>";
        }).join("") || "<tr><td colspan=\"9\" class=\"text-center text-muted py-8\">Carga XML para registrar compras, gastos y relacionarlos con movimientos.</td></tr>";
    }
    function renderPendientes() {
        var pendientes = movimientos.filter(function (m) { return m.cfdi === "pendiente"; });
        $("contabilidad_pendientes").innerHTML = pendientes.map(function (m) {
            return "<div class=\"alert alert-warning py-3 mb-3\"><div class=\"fw-bold\">" + escapeHtml(m.concepto) + "</div>" +
                "<div class=\"fs-8\">" + escapeHtml(m.fecha || "-") + " | " + money(m.cargo || m.abono) + " | " + label(m.actividad) + " | " + label(m.categoria) + "</div></div>";
        }).join("") || "<div class=\"alert alert-success py-3 mb-0\">Sin pendientes visibles de CFDI.</div>";
    }
    function renderEstadosCuenta() {
        var contenedor = $("contabilidad_estados");
        if (!contenedor) { return; }
        contenedor.innerHTML = estadosCuenta.map(function (edo) {
            var ligados = movimientos.filter(function (m) { return m.estado_cuenta_id === edo.id && m.cfdi === "ligado"; }).length;
            var activo = estadoSeleccionadoId === edo.id;
            return "<div class=\"d-flex flex-wrap align-items-center justify-content-between gap-3 border-bottom py-3" + (activo ? " bg-light-primary px-3 rounded" : "") + "\">" +
                "<div><div class=\"fw-bold\">" + escapeHtml(edo.cuenta) + "</div>" +
                "<div class=\"text-muted fs-8\">" + escapeHtml(edo.archivo) + (edo.hoja ? " | Hoja: " + escapeHtml(edo.hoja) : "") + " | " + escapeHtml(edo.periodo) + " | " + escapeHtml(edo.fecha_carga) + "</div></div>" +
                "<div class=\"d-flex flex-wrap gap-2\">" +
                badge(edo.movimientos + " movimientos", "primary") +
                badge(edo.filas + " filas leidas", "info") +
                badge(edo.columnas + " columnas", "warning") +
                badge(ligados + " CFDI ligados", ligados ? "success" : "secondary") +
                "<button class=\"btn btn-sm " + (activo ? "btn-primary" : "btn-light-primary") + "\" type=\"button\" data-ver-estado=\"" + escapeHtml(edo.id) + "\"><i class=\"bi bi-pencil-square\"></i> " + (activo ? "Editando" : "Ver / editar") + "</button>" +
                "<button class=\"btn btn-sm btn-light\" type=\"button\" data-export-estado=\"" + escapeHtml(edo.id) + "\"><i class=\"bi bi-download\"></i></button>" +
                "<button class=\"btn btn-sm btn-light-danger\" type=\"button\" data-eliminar-estado=\"" + escapeHtml(edo.id) + "\"><i class=\"bi bi-trash3\"></i></button>" +
                "</div></div>";
        }).join("") || "<div class=\"text-muted py-4\">Carga un estado de cuenta para iniciar. Puedes cargar varios archivos y se mantendran separados por cuenta.</div>";
    }
    function renderGuardados() {
        var contenedor = $("contabilidad_guardados");
        if (!contenedor) { return; }
        var cierres = leerCierresGuardados();
        $("contabilidad_guardados_total").textContent = cierres.length + " guardados";
        contenedor.innerHTML = cierres.map(function (cierre) {
            var cuentas = {};
            (cierre.movimientos || []).forEach(function (m) { cuentas[m.cuenta || "Cuenta sin nombre"] = true; });
            var actualizado = cierre.actualizado ? new Date(cierre.actualizado).toLocaleString("es-MX") : "";
            return "<div class=\"d-flex flex-wrap align-items-center justify-content-between gap-3 border-bottom py-3\">" +
                "<div><div class=\"fw-bold\">" + escapeHtml(cierre.periodo || cierre.id) + "</div>" +
                "<div class=\"text-muted fs-8\">" + (cierre.movimientos || []).length + " movimientos | " + Object.keys(cuentas).length + " cuentas | " + escapeHtml(actualizado) + "</div></div>" +
                "<div class=\"d-flex gap-2\">" +
                "<button class=\"btn btn-sm btn-light-primary\" type=\"button\" data-cargar-cierre=\"" + escapeHtml(cierre.id) + "\"><i class=\"bi bi-folder2-open\"></i> Abrir</button>" +
                "<button class=\"btn btn-sm btn-light\" type=\"button\" data-eliminar-cierre=\"" + escapeHtml(cierre.id) + "\"><i class=\"bi bi-trash3\"></i></button>" +
                "</div></div>";
        }).join("") || "<div class=\"text-muted py-4\">Todavia no hay cierres guardados en este navegador.</div>";
    }
    function movimientosPorCuenta() {
        return movimientos.reduce(function (map, mov) {
            var cuenta = mov.cuenta || "Cuenta sin nombre";
            if (!map[cuenta]) {
                map[cuenta] = {cuenta: cuenta, movimientos: [], egreso: 0, ingreso: 0, transpaso: 0, pendientes: 0};
            }
            map[cuenta].movimientos.push(mov);
            if (mov.tipo_movimiento === "ingreso") { map[cuenta].ingreso += Number(mov.monto || 0); }
            if (mov.tipo_movimiento === "egreso") { map[cuenta].egreso += Number(mov.monto || 0); }
            if (mov.actividad === "transpaso") { map[cuenta].transpaso += Number(mov.monto || 0); }
            if (mov.cfdi === "pendiente") { map[cuenta].pendientes++; }
            return map;
        }, {});
    }
    function renderConciliacion() {
        var contenedor = $("contabilidad_conciliacion_cuentas");
        if (!contenedor) { return; }
        var grupos = Object.values(movimientosPorCuenta());
        $("contabilidad_conciliacion_total").textContent = grupos.length + " cuentas";
        contenedor.innerHTML = grupos.map(function (grupo) {
            var porTipo = tiposMovimiento.map(function (tipo) {
                var lista = grupo.movimientos.filter(function (m) { return m.tipo_movimiento === tipo; });
                var total = lista.reduce(function (s, m) { return s + Number(m.monto || 0); }, 0);
                return "<div class=\"col-md-4\"><div class=\"border rounded p-4 h-100\">" +
                    "<div class=\"text-muted fs-8 text-uppercase\">" + label(tipo) + "</div>" +
                    "<div class=\"fw-bold fs-5\">" + money(total) + "</div>" +
                    "<div class=\"text-muted fs-8\">" + lista.length + " movimientos</div></div></div>";
            }).join("");
            return "<div class=\"border-bottom py-5\">" +
                "<div class=\"d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4\">" +
                "<div><div class=\"fw-bold fs-5\">" + escapeHtml(grupo.cuenta) + "</div>" +
                "<div class=\"text-muted fs-8\">" + grupo.movimientos.length + " movimientos | " + grupo.pendientes + " CFDI pendientes</div></div>" +
                "<button class=\"btn btn-sm btn-light-primary\" type=\"button\" data-export-cuenta=\"" + escapeHtml(grupo.cuenta) + "\"><i class=\"bi bi-download\"></i> CSV cuenta</button>" +
                "</div><div class=\"row g-3\">" + porTipo + "</div></div>";
        }).join("") || "<div class=\"text-muted py-4\">Cuando importes estados de cuenta, aqui veras los movimientos separados por cuenta y por tipo.</div>";
    }
    function render() {
        renderEstadosCuenta();
        renderGuardados();
        renderKpis();
        renderMovimientos();
        renderCfdis();
        renderPendientes();
        renderConciliacion();
        if ($("contabilidad_reporte_texto")) { generarReporteContador(); }
    }
    function exportarCsv() {
        var periodo = periodoCierreActual();
        var cuenta = $("contabilidad_cuenta").value || "";
        var headers = ["periodo", "fecha", "descripcion", "movimiento", "actividad", "categoria", "cuenta", "monto", "folio_factura", "cfdi", "cfdi_uuid", "cfdi_sugerencia", "archivo_estado_cuenta", "notas"];
        var lines = [headers.join(",")].concat(movimientos.filter(function (m) {
            return (m.periodo || periodoEstadoPorId(m.estado_cuenta_id)) === periodo;
        }).map(function (m) {
            return [m.periodo || periodoEstadoPorId(m.estado_cuenta_id), m.fecha, m.concepto, m.tipo_movimiento, m.actividad, m.categoria, m.cuenta || cuenta, m.monto, m.referencia, m.cfdi, m.cfdi_uuid, m.cfdi_sugerencia, m.archivo_estado_cuenta, m.notas].map(csvEscape).join(",");
        }));
        descargar("clasificacion_movimientos_" + periodo + ".csv", lines.join("\n"), "text/csv;charset=utf-8");
    }
    function exportarCsvCuenta(cuentaSeleccionada) {
        var periodo = periodoCierreActual();
        var headers = ["periodo", "fecha", "descripcion", "movimiento", "actividad", "categoria", "cuenta", "monto", "folio_factura", "cfdi", "cfdi_uuid", "cfdi_sugerencia", "archivo_estado_cuenta", "notas"];
        var rows = movimientos.filter(function (m) {
            return (m.cuenta || "Cuenta sin nombre") === cuentaSeleccionada &&
                (m.periodo || periodoEstadoPorId(m.estado_cuenta_id)) === periodo;
        });
        var lines = [headers.join(",")].concat(rows.map(function (m) {
            return [m.periodo || periodoEstadoPorId(m.estado_cuenta_id), m.fecha, m.concepto, m.tipo_movimiento, m.actividad, m.categoria, m.cuenta, m.monto, m.referencia, m.cfdi, m.cfdi_uuid, m.cfdi_sugerencia, m.archivo_estado_cuenta, m.notas].map(csvEscape).join(",");
        }));
        descargar("clasificacion_" + cuentaSeleccionada.replace(/[^a-z0-9]+/gi, "_").toLowerCase() + "_" + periodo + ".csv", lines.join("\n"), "text/csv;charset=utf-8");
    }
    function exportarCsvEstado(estadoId) {
        var estado = estadosCuenta.find(function (edo) { return edo.id === estadoId; });
        var periodo = (estado && estado.periodo) || periodoCierreActual();
        var headers = ["periodo", "fecha", "descripcion", "movimiento", "actividad", "categoria", "cuenta", "monto", "folio_factura", "cfdi", "cfdi_uuid", "cfdi_sugerencia", "archivo_estado_cuenta", "notas"];
        var rows = movimientos.filter(function (m) { return m.estado_cuenta_id === estadoId; });
        var lines = [headers.join(",")].concat(rows.map(function (m) {
            return [m.periodo || periodo, m.fecha, m.concepto, m.tipo_movimiento, m.actividad, m.categoria, m.cuenta, m.monto, m.referencia, m.cfdi, m.cfdi_uuid, m.cfdi_sugerencia, m.archivo_estado_cuenta, m.notas].map(csvEscape).join(",");
        }));
        var base = estado ? estado.archivo : "estado_cuenta";
        descargar("clasificacion_" + base.replace(/\.[^.]+$/, "").replace(/[^a-z0-9]+/gi, "_").toLowerCase() + "_" + periodo + ".csv", lines.join("\n"), "text/csv;charset=utf-8");
    }
    function activarTab(id) {
        var link = document.querySelector("a[href=\"#" + id + "\"]");
        if (link && window.bootstrap && bootstrap.Tab) {
            bootstrap.Tab.getOrCreateInstance(link).show();
        }
    }
    function verEstadoCuenta(estadoId) {
        estadoSeleccionadoId = estadoId;
        activarTab("contabilidad_tab_movimientos");
        render();
    }
    function eliminarMovimiento(movimientoId) {
        confirmarAccion("Se eliminara este movimiento del cierre actual.", function () {
            movimientos = movimientos.filter(function (m) { return m.id !== movimientoId; });
            delete seleccionados[movimientoId];
            estadosCuenta.forEach(function (edo) {
                edo.movimientos = movimientos.filter(function (m) { return m.estado_cuenta_id === edo.id; }).length;
            });
            render();
        });
    }
    function eliminarEstadoCuenta(estadoId) {
        confirmarAccion("Se eliminara este estado de cuenta y todos sus movimientos del cierre actual.", function () {
            estadosCuenta = estadosCuenta.filter(function (edo) { return edo.id !== estadoId; });
            movimientos.filter(function (m) { return m.estado_cuenta_id === estadoId; }).forEach(function (m) { delete seleccionados[m.id]; });
            movimientos = movimientos.filter(function (m) { return m.estado_cuenta_id !== estadoId; });
            if (estadoSeleccionadoId === estadoId) {
                estadoSeleccionadoId = "";
            }
            render();
        });
    }
    function exportarJson() {
        var periodo = periodoCierreActual();
        descargar("clasificacion_movimientos_" + periodo + ".json", JSON.stringify({
            periodo: periodo,
            cuenta: $("contabilidad_cuenta").value || "",
            estados_cuenta: estadosCuenta,
            movimientos: movimientos,
            cfdis: cfdis
        }, null, 2), "application/json;charset=utf-8");
    }
    function generarReporteContador() {
        var periodo = periodoCierreActual();
        var rows = visibles();
        var lineas = [
            ["periodo", "fecha", "cuenta", "movimiento", "actividad", "categoria", "forma_pago", "monto", "concepto", "folio_factura", "cfdi_uuid", "cfdi"].join("\t")
        ];
        rows.forEach(function (m) {
            lineas.push([
                m.periodo || periodoEstadoPorId(m.estado_cuenta_id),
                m.fecha,
                m.cuenta,
                m.tipo_movimiento,
                m.actividad,
                m.categoria,
                m.forma_pago,
                m.monto,
                m.concepto,
                m.referencia,
                m.cfdi_uuid,
                m.cfdi
            ].map(function (v) { return String(v == null ? "" : v).replace(/\t/g, " ").replace(/\r?\n/g, " "); }).join("\t"));
        });
        $("contabilidad_reporte_texto").value = lineas.join("\n");
    }
    function copiarReporteContador() {
        generarReporteContador();
        var textarea = $("contabilidad_reporte_texto");
        textarea.focus();
        textarea.select();
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(textarea.value);
            return;
        }
        document.execCommand("copy");
    }
    function cargarDemo() {
        $("contabilidad_cuenta").value = "BBVA negocio";
        archivoCrudoNombre = "demo_estado_cuenta.csv";
        archivoCrudoHoja = "";
        importarMovimientos(parseCsv("fecha,concepto,cargo,abono,referencia\n2026-08-01,DEPOSITO VENTA MOSTRADOR,,5800,POS-001\n2026-08-02,SPEI PROVEEDOR ACUARIOS SA,3200,,SPEI-889\n2026-08-03,PAGO CFE SUCURSAL CENTRO,840.50,,SERV-100\n2026-08-04,TRANSPASO CUENTA PROPIA BBVA,5000,,INT-77\n2026-08-05,PAGO TARJETA CREDITO NU,2400,,TDC-1\n2026-08-08,OPENAI SOFTWARE PROGRAMACION,399,,DEV-55\n2026-08-17,PAGO SAT IVA JULIO,1260,,SAT-IVA"));
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
        fijarPeriodo(hoyPeriodo());
        $("contabilidad_periodo").addEventListener("change", function () {
            if ($("contabilidad_estado_periodo")) {
                $("contabilidad_estado_periodo").value = periodoCierreActual();
            }
            render();
        });
        $("contabilidad_estado_periodo").addEventListener("change", function () {
            if ($("contabilidad_periodo")) {
                $("contabilidad_periodo").value = periodoEstadoCuentaActual();
            }
            render();
        });
        $("contabilidad_banco_archivo").addEventListener("change", function (e) {
            var file = e.target.files[0];
            if (!file) { return; }
            archivoCrudoNombre = file.name;
            archivoCrudoHoja = "";
            var extension = file.name.split(".").pop().toLowerCase();
            if (extension === "json") {
                leerArchivoTexto(file, function (texto) {
                    try {
                        restaurarCierre(JSON.parse(texto));
                    } catch (error) {
                        mostrarError("No pude leer el JSON del cierre.");
                    }
                });
                return;
            }
            if (extension === "xlsx") {
                requestXlsx(file).then(function (response) {
                    if (response.error) {
                        mostrarError(response.mensaje || "No pude leer el XLSX.");
                        return;
                    }
                    archivoCrudoHoja = (response.depurar && response.depurar.hoja) || "";
                    if (response.depurar && response.depurar.matriz) {
                        prepararMapeoMatriz(response.depurar.matriz);
                    } else {
                        prepararMapeo((response.depurar && response.depurar.filas) || []);
                    }
                }).catch(function () {
                    mostrarError("No pude enviar el XLSX para lectura.");
                });
                return;
            }
            if (extension === "xls") {
                mostrarError("El formato XLS antiguo no se puede leer todavia. Exportalo como XLSX, CSV o TXT.");
                return;
            }
            leerArchivoTexto(file, function (texto) { prepararMapeoMatriz(parseCsvMatriz(texto)); });
        });
        $("contabilidad_xml_archivos").addEventListener("change", function (e) {
            var archivos = Array.prototype.slice.call(e.target.files || []);
            var pendientes = archivos.length;
            if (!pendientes) { return; }
            archivos.forEach(function (file) {
                leerArchivoTexto(file, function (texto) {
                    var cfdi = parseCfdiXml(texto, file.name);
                    if (!cfdi.uuid || !cfdis.some(function (c) { return c.uuid === cfdi.uuid; })) {
                        cfdis.push(cfdi);
                    }
                    pendientes--;
                    if (pendientes === 0) { autoRelacionarCfdi(); render(); }
                });
            });
        });
        $("contabilidad_cfdis").addEventListener("change", function (e) {
            var row = e.target.closest("tr[data-cfdi-id]");
            var cfdi = row ? cfdiPorId(row.getAttribute("data-cfdi-id")) : null;
            var campo = e.target.getAttribute("data-cfdi-campo");
            if (!cfdi || !campo) { return; }
            cfdi[campo] = e.target.value;
            if (campo === "forma_pago") {
                cfdi.cuenta_pago = cuentaPorFormaPago(cfdi.forma_pago, cfdi.cuenta_pago);
            }
            render();
        });
        $("contabilidad_cfdis").addEventListener("click", function (e) {
            var ligar = e.target.closest("[data-ligar-cfdi]");
            var crear = e.target.closest("[data-crear-mov-cfdi]");
            var eliminar = e.target.closest("[data-eliminar-cfdi]");
            if (ligar) { ligarCfdiSugerido(ligar.getAttribute("data-ligar-cfdi")); }
            if (crear) { crearMovimientoDesdeCfdi(crear.getAttribute("data-crear-mov-cfdi")); }
            if (eliminar) { eliminarCfdi(eliminar.getAttribute("data-eliminar-cfdi")); }
        });
        $("contabilidad_cfdi_crear_movimientos").addEventListener("click", crearMovimientosCfdiSinBanco);
        ["contabilidad_buscar", "contabilidad_tipo", "contabilidad_actividad", "contabilidad_categoria", "contabilidad_cfdi"].forEach(function (id) {
            $(id).addEventListener("input", render);
            $(id).addEventListener("change", render);
        });
        $("contabilidad_movimientos").addEventListener("change", function (e) {
            var check = e.target.closest("[data-seleccionar-mov]");
            if (check) {
                if (check.checked) {
                    seleccionados[check.getAttribute("data-seleccionar-mov")] = true;
                } else {
                    delete seleccionados[check.getAttribute("data-seleccionar-mov")];
                }
                renderMovimientos();
                return;
            }
            var row = e.target.closest("tr[data-id]");
            var mov = row ? movimientos.find(function (m) { return m.id === row.getAttribute("data-id"); }) : null;
            var campo = e.target.getAttribute("data-campo");
            if (!mov || !campo) { return; }
            mov[campo] = e.target.value;
            if (["tipo_movimiento", "actividad", "categoria"].indexOf(campo) >= 0 && mov.cfdi !== "ligado") {
                mov.cfdi = cfdiEsperado(mov);
            }
            render();
        });
        $("contabilidad_movimientos").addEventListener("click", function (e) {
            var eliminarMov = e.target.closest("[data-eliminar-mov]");
            if (eliminarMov) {
                eliminarMovimiento(eliminarMov.getAttribute("data-eliminar-mov"));
                return;
            }
            var btn = e.target.closest("[data-ligar]");
            if (!btn) { return; }
            var mov = movimientos.find(function (m) { return m.id === btn.getAttribute("data-ligar"); });
            if (!mov) { return; }
            var elegido = cfdis.find(function (c) { return c.uuid === mov.cfdi_sugerencia; });
            if (!elegido) {
                elegido = cfdis.map(function (cfdi) { return {cfdi: cfdi, score: scoreCfdi(mov, cfdi)}; })
                    .sort(function (a, b) { return b.score - a.score; })[0];
                elegido = elegido ? elegido.cfdi : null;
            }
            if (elegido) {
                mov.cfdi = "ligado";
                mov.cfdi_uuid = elegido.uuid;
                mov.cfdi_sugerencia = "";
                elegido.movimiento_relacionado = mov.id;
                elegido.estatus_relacion = "ligado";
                render();
            }
        });
        $("contabilidad_select_todos").addEventListener("change", function (e) {
            seleccionarVisibles(e.target.checked);
        });
        $("contabilidad_masivo_aplicar").addEventListener("click", aplicarMasivo);
        $("contabilidad_masivo_limpiar").addEventListener("click", limpiarSeleccion);
        $("contabilidad_mapeo_limite").addEventListener("change", renderPreviewMapeo);
        $("contabilidad_mapeo_fila_header").addEventListener("change", function () { renderMapeo(); });
        ["map_fecha", "map_concepto", "map_monto", "map_movimiento", "map_actividad", "map_cuenta", "map_folio"].forEach(function (id) {
            $(id).addEventListener("change", renderPreviewMapeo);
        });
        $("contabilidad_conciliacion_cuentas").addEventListener("click", function (e) {
            var btn = e.target.closest("[data-export-cuenta]");
            if (btn) { exportarCsvCuenta(btn.getAttribute("data-export-cuenta")); }
        });
        $("contabilidad_estados").addEventListener("click", function (e) {
            var ver = e.target.closest("[data-ver-estado]");
            var exportar = e.target.closest("[data-export-estado]");
            var eliminar = e.target.closest("[data-eliminar-estado]");
            if (ver) {
                verEstadoCuenta(ver.getAttribute("data-ver-estado"));
            }
            if (exportar) {
                exportarCsvEstado(exportar.getAttribute("data-export-estado"));
            }
            if (eliminar) {
                eliminarEstadoCuenta(eliminar.getAttribute("data-eliminar-estado"));
            }
        });
        $("contabilidad_ver_todos_estados").addEventListener("click", function () {
            estadoSeleccionadoId = "";
            activarTab("contabilidad_tab_movimientos");
            render();
        });
        $("contabilidad_guardar_borrador").addEventListener("click", guardarCierreLocal);
        $("contabilidad_guardados").addEventListener("click", function (e) {
            var cargar = e.target.closest("[data-cargar-cierre]");
            var eliminar = e.target.closest("[data-eliminar-cierre]");
            if (cargar) {
                cargarCierreLocal(cargar.getAttribute("data-cargar-cierre"));
            }
            if (eliminar) {
                eliminarCierreLocal(eliminar.getAttribute("data-eliminar-cierre"));
            }
        });
        $("contabilidad_exportar_csv").addEventListener("click", exportarCsv);
        $("contabilidad_exportar_json").addEventListener("click", exportarJson);
        $("contabilidad_reporte_generar").addEventListener("click", generarReporteContador);
        $("contabilidad_reporte_copiar").addEventListener("click", copiarReporteContador);
        $("contabilidad_recalcular").addEventListener("click", function () { autoRelacionarCfdi(); render(); });
        $("contabilidad_mapeo_aplicar").addEventListener("click", importarDesdeMapeo);
        $("contabilidad_demo").addEventListener("click", cargarDemo);
        $("contabilidad_limpiar").addEventListener("click", function () { movimientos = []; cfdis = []; estadosCuenta = []; filasCrudas = []; matrizCruda = []; encabezadosCrudos = []; archivoCrudoHoja = ""; estadoSeleccionadoId = ""; render(); });
        render();
    }
    document.addEventListener("DOMContentLoaded", bind);
})();
