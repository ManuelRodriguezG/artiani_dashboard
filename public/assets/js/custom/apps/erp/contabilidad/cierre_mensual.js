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
    var clasificacionSeleccionada = "";
    var seleccionados = {};
    var cfdisSeleccionados = {};
    var STORAGE_KEY = "erp_contabilidad_cierres_local_v1";
    var DRAFT_KEY = "erp_contabilidad_cierre_borrador_activo_v1";
    var autosaveTimer = null;
    var cuentaConciliacionActual = "";
    var conciliacionModalRows = [];
    var conciliacionModalNombre = "";
    var relacionContexto = {tipo: "", movId: "", cfdiId: "", busqueda: ""};

    var tiposMovimiento = ["egreso", "ingreso"];
    var actividades = ["negocio", "programacion", "personal", "publicidad", "inversion", "transpaso"];
    var formasPago = ["efectivo", "tarjeta_debito", "tarjeta_credito", "transferencia", "retencion_plataforma", "cheque", "comision_bancaria"];
    var categorias = ["no_aplica", "venta", "compra_mercancia", "gasto_operativo", "comision_plataforma", "servicio", "nomina", "impuestos", "renta", "publicidad", "software", "banco_comision", "inversion", "personal", "por_definir"];

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
    function periodoCfdiActual() {
        return ($("contabilidad_cfdi_periodo") && $("contabilidad_cfdi_periodo").value) || periodoCierreActual();
    }
    function fijarPeriodo(periodo) {
        var valor = periodo || hoyPeriodo();
        if ($("contabilidad_periodo")) { $("contabilidad_periodo").value = valor; }
        if ($("contabilidad_estado_periodo")) { $("contabilidad_estado_periodo").value = valor; }
        if ($("contabilidad_cfdi_periodo")) { $("contabilidad_cfdi_periodo").value = valor; }
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
    function hayTrabajoLocal() {
        return movimientos.length || cfdis.length || estadosCuenta.length;
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
    function guardarBorradorActivoLocal() {
        if (!hayTrabajoLocal()) { return; }
        localStorage.setItem(DRAFT_KEY, JSON.stringify(snapshotCierreActual()));
    }
    function programarAutosave() {
        if (!hayTrabajoLocal()) { return; }
        window.clearTimeout(autosaveTimer);
        autosaveTimer = window.setTimeout(guardarBorradorActivoLocal, 350);
    }
    function guardarCierreLocal() {
        if (!hayTrabajoLocal()) {
            mostrarError("Carga movimientos o CFDI antes de guardar el cierre.");
            return;
        }
        var cierre = snapshotCierreActual();
        var cierres = leerCierresGuardados().filter(function (item) { return item.id !== cierre.id; });
        cierres.unshift(cierre);
        escribirCierresGuardados(cierres.slice(0, 24));
        guardarBorradorActivoLocal();
        renderGuardados();
        if (window.Swal) {
            Swal.fire({text: "Cierre guardado en este navegador.", icon: "success", timer: 1400, showConfirmButton: false});
        }
    }
    function restaurarBorradorActivoLocal() {
        try {
            var raw = localStorage.getItem(DRAFT_KEY);
            var data = raw ? JSON.parse(raw) : null;
            if (!data || (!Array.isArray(data.movimientos) && !Array.isArray(data.cfdis))) { return false; }
            fijarPeriodo(data.periodo || hoyPeriodo());
            $("contabilidad_cuenta").value = data.cuenta || data.cuenta_base || "";
            estadosCuenta = Array.isArray(data.estados_cuenta) ? data.estados_cuenta : [];
            movimientos = Array.isArray(data.movimientos) ? data.movimientos : [];
            cfdis = Array.isArray(data.cfdis) ? data.cfdis : [];
            normalizarMovimientosLegacy();
            normalizarCfdisLegacy();
            return hayTrabajoLocal();
        } catch (e) {
            return false;
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
        clasificacionSeleccionada = "";
        seleccionados = {};
        cfdisSeleccionados = {};
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
        estadoSeleccionadoId = "";
        clasificacionSeleccionada = "";
        seleccionados = {};
        cfdisSeleccionados = {};
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
    function slug(value) { return String(value || "archivo").replace(/[^a-z0-9]+/gi, "_").toLowerCase(); }
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
        if (forma === "retencion_plataforma") { return fallback || "Mercado Pago - comisiones"; }
        if (forma === "efectivo") { return "Efectivo"; }
        if (forma === "tarjeta_credito") { return "Tarjeta de credito"; }
        if (forma === "tarjeta_debito") { return "Tarjeta debito"; }
        if (forma === "transferencia") { return fallback || "Transferencia"; }
        return fallback || "Efectivo";
    }
    function tratamientoCfdiDefault() {
        return $("contabilidad_cfdi_tratamiento_default") ? $("contabilidad_cfdi_tratamiento_default").value : "conciliar_banco";
    }
    function categoriaCfdiDefault() {
        return $("contabilidad_cfdi_categoria_default") ? $("contabilidad_cfdi_categoria_default").value : "gasto_operativo";
    }
    function cuentaCfdiDefault() {
        return $("contabilidad_cfdi_cuenta_default") ? $("contabilidad_cfdi_cuenta_default").value : "Por relacionar";
    }
    function formaPagoCfdiDefault(categoria, formaSat) {
        if (categoria === "comision_plataforma") { return "retencion_plataforma"; }
        return satFormaPagoOperativa(formaSat);
    }
    function cuentasDisponiblesCfdi(valorActual) {
        var periodo = periodoCfdiActual();
        var map = {};
        ["Por relacionar", "Efectivo", "Mercado Pago - comisiones", "Tarjeta de credito", "Tarjeta debito", "Transferencia"].forEach(function (cuenta) {
            map[cuenta] = true;
        });
        estadosCuenta.forEach(function (edo) {
            if ((!periodo || edo.periodo === periodo) && edo.cuenta) {
                map[edo.cuenta] = true;
            }
        });
        if ($("contabilidad_cuenta") && $("contabilidad_cuenta").value) {
            map[$("contabilidad_cuenta").value] = true;
        }
        if (valorActual) { map[valorActual] = true; }
        return Object.keys(map);
    }
    function optionsCuentas(valorActual) {
        return cuentasDisponiblesCfdi(valorActual).map(function (cuenta) {
            return "<option value=\"" + escapeHtml(cuenta) + "\"" + (cuenta === valorActual ? " selected" : "") + ">" + escapeHtml(cuenta) + "</option>";
        }).join("");
    }
    function renderCuentaCfdiDefault() {
        var select = $("contabilidad_cfdi_cuenta_default");
        if (!select) { return; }
        var actual = select.value || "Por relacionar";
        var html = optionsCuentas(actual);
        if (select.innerHTML !== html) {
            select.innerHTML = html;
        }
        select.value = cuentasDisponiblesCfdi(actual).indexOf(actual) >= 0 ? actual : "Por relacionar";
    }
    function renderCfdiMasivoControls() {
        if ($("cfdi_masivo_forma_pago")) {
            var actualForma = $("cfdi_masivo_forma_pago").value;
            $("cfdi_masivo_forma_pago").innerHTML = "<option value=\"\">Sin cambio</option>" + options(formasPago, actualForma);
            $("cfdi_masivo_forma_pago").value = actualForma;
        }
        if ($("cfdi_masivo_cuenta")) {
            var actualCuenta = $("cfdi_masivo_cuenta").value;
            $("cfdi_masivo_cuenta").innerHTML = "<option value=\"\">Sin cambio</option>" + optionsCuentas(actualCuenta);
            $("cfdi_masivo_cuenta").value = actualCuenta;
        }
        if ($("cfdi_filtro_forma_pago")) {
            var filtroForma = $("cfdi_filtro_forma_pago").value;
            $("cfdi_filtro_forma_pago").innerHTML = "<option value=\"\">Todos</option>" + options(formasPago, filtroForma);
            $("cfdi_filtro_forma_pago").value = filtroForma;
        }
        if ($("cfdi_filtro_cuenta")) {
            var filtroCuenta = $("cfdi_filtro_cuenta").value;
            $("cfdi_filtro_cuenta").innerHTML = "<option value=\"\">Todas</option>" + optionsCuentas(filtroCuenta);
            $("cfdi_filtro_cuenta").value = filtroCuenta;
        }
    }
    function renderClasificadorOrigen() {
        var select = $("clasificacion_origen");
        if (!select) { return; }
        var periodo = periodoCierreActual();
        var actual = estadoSeleccionadoId ? valorFiltroEstado(estadoSeleccionadoId) : clasificacionSeleccionada;
        var validos = {"": true};
        var estadosHtml = estadosCuenta.filter(function (edo) {
            return !periodo || edo.periodo === periodo;
        }).map(function (edo) {
            var valor = valorFiltroEstado(edo.id);
            validos[valor] = true;
            return "<option value=\"" + escapeHtml(valor) + "\">" + escapeHtml(edo.cuenta || "Cuenta sin nombre") + " | " + escapeHtml(edo.archivo || "Estado") + "</option>";
        }).join("");
        var cuentasAux = {};
        movimientosPeriodoActual().forEach(function (mov) {
            if (!mov.estado_cuenta_id && mov.cuenta) { cuentasAux[mov.cuenta] = true; }
        });
        var auxHtml = Object.keys(cuentasAux).map(function (cuenta) {
            var valor = valorFiltroCuentaAuxiliar(cuenta);
            validos[valor] = true;
            return "<option value=\"" + escapeHtml(valor) + "\">" + escapeHtml(cuenta) + "</option>";
        }).join("");
        select.innerHTML = "<option value=\"\">Todo el mes</option>" +
            (estadosHtml ? "<optgroup label=\"Estados cargados\">" + estadosHtml + "</optgroup>" : "") +
            (auxHtml ? "<optgroup label=\"Cuentas generadas\">" + auxHtml + "</optgroup>" : "");
        if (!validos[actual]) {
            actual = "";
            estadoSeleccionadoId = "";
            clasificacionSeleccionada = "";
        }
        select.value = actual;
    }
    function movimientosBaseClasificacion() {
        var periodo = periodoCierreActual();
        var clasificar = $("clasificacion_origen") ? $("clasificacion_origen").value : "";
        return movimientos.filter(function (mov) {
            return (!periodo || (mov.periodo || periodoEstadoPorId(mov.estado_cuenta_id)) === periodo) &&
                (!clasificar || coincideFiltroClasificacion(mov, clasificar));
        });
    }
    function renderDescripcionesFiltro() {
        var datalist = $("clasificacion_descripciones_sugeridas");
        if (!datalist) { return; }
        var vistas = {};
        movimientosBaseClasificacion().forEach(function (mov) {
            var concepto = String(mov.concepto || "").trim();
            if (concepto) { vistas[concepto] = true; }
        });
        datalist.innerHTML = Object.keys(vistas).sort(function (a, b) {
            return a.localeCompare(b, "es");
        }).slice(0, 250).map(function (concepto) {
            return "<option value=\"" + escapeHtml(concepto) + "\"></option>";
        }).join("");
    }
    function prepararDefaultsCfdiAuxiliar() {
        if ($("contabilidad_cfdi_tratamiento_default") && $("contabilidad_cfdi_tratamiento_default").value === "crear_auxiliar") {
            if ($("contabilidad_cfdi_categoria_default") && $("contabilidad_cfdi_categoria_default").value === "gasto_operativo") {
                $("contabilidad_cfdi_categoria_default").value = "comision_plataforma";
            }
            if ($("contabilidad_cfdi_cuenta_default")) {
                renderCuentaCfdiDefault();
                $("contabilidad_cfdi_cuenta_default").value = "Mercado Pago - comisiones";
            }
        }
        if ($("contabilidad_cfdi_categoria_default") && $("contabilidad_cfdi_categoria_default").value === "comision_plataforma" && $("contabilidad_cfdi_cuenta_default")) {
            renderCuentaCfdiDefault();
            $("contabilidad_cfdi_cuenta_default").value = "Mercado Pago - comisiones";
        }
    }
    function options(valores, actual) {
        return valores.map(function (v) {
            return "<option value=\"" + v + "\"" + (v === actual ? " selected" : "") + ">" + label(v) + "</option>";
        }).join("");
    }
    function valorFiltroEstado(estadoId) {
        return "estado:" + estadoId;
    }
    function valorFiltroCuentaAuxiliar(cuenta) {
        return "aux:" + encodeURIComponent(cuenta || "");
    }
    function cuentaDesdeFiltroAuxiliar(valor) {
        return decodeURIComponent(String(valor || "").replace(/^aux:/, ""));
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
    function tipoPorMontoFirmado(monto) {
        return Number(monto || 0) >= 0 ? "ingreso" : "egreso";
    }
    function montoFirmado(tipo, monto) {
        var abs = Math.abs(Number(monto || 0));
        return tipo === "egreso" ? -abs : abs;
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
        if (actividad === "transpaso" || tipo === "ingreso") { return "no_aplica"; }
        if (actividad === "personal") { return "personal"; }
        if (actividad === "inversion") { return "inversion"; }
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
        if (categorias.indexOf(mov.categoria) < 0) {
            mov.categoria = sugerirCategoria(mov.concepto, mov.tipo_movimiento, mov.actividad);
        }
        if (mov.actividad === "transpaso" || mov.tipo_movimiento === "ingreso") {
            mov.categoria = "no_aplica";
        }
        mov.traspaso_relacionado = mov.traspaso_relacionado || "";
        mov.traspaso_grupo = mov.traspaso_grupo || "";
        mov.monto = montoFirmado(mov.tipo_movimiento, mov.monto || mov.cargo || mov.abono);
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
        cfdi.subtotal = Number(cfdi.subtotal || 0);
        cfdi.descuento = Number(cfdi.descuento || 0);
        cfdi.total = Number(cfdi.total || cfdi.pago_monto || 0);
        cfdi.pago_monto = Number(cfdi.pago_monto || 0);
        cfdi.iva_trasladado = Number(cfdi.iva_trasladado || 0);
        cfdi.iva_retenido = Number(cfdi.iva_retenido || 0);
        cfdi.isr_retenido = Number(cfdi.isr_retenido || 0);
        cfdi.total_impuestos_trasladados = Number(cfdi.total_impuestos_trasladados || 0);
        cfdi.total_impuestos_retenidos = Number(cfdi.total_impuestos_retenidos || 0);
        cfdi.moneda = cfdi.moneda || "MXN";
        cfdi.uso_cfdi = cfdi.uso_cfdi || "";
        cfdi.receptor = cfdi.receptor || "";
        cfdi.rfc_receptor = cfdi.rfc_receptor || "";
        cfdi.concepto_principal = cfdi.concepto_principal || "";
        cfdi.conceptos_resumen = cfdi.conceptos_resumen || cfdi.concepto_principal || "";
        cfdi.conceptos_total = Number(cfdi.conceptos_total || 0);
        cfdi.pago_complemento = !!cfdi.pago_complemento;
        cfdi.pago_fecha = cfdi.pago_fecha || "";
        cfdi.pagos_resumen = cfdi.pagos_resumen || "";
        cfdi.doctos_relacionados = cfdi.doctos_relacionados || "";
        cfdi.num_operacion = cfdi.num_operacion || "";
        cfdi.tratamiento = cfdi.tratamiento || tratamientoCfdiDefault();
        cfdi.origen = cfdi.origen || (cfdi.tratamiento === "crear_auxiliar" ? "cfdi_auxiliar" : "cfdi");
        cfdi.categoria = cfdi.categoria || cfdi.clasificacion || categoriaCfdiDefault();
        cfdi.actividad = cfdi.actividad || "negocio";
        cfdi.forma_pago = cfdi.forma_pago || satFormaPagoOperativa(cfdi.forma_pago_sat);
        if (cfdi.categoria === "comision_plataforma") {
            cfdi.forma_pago = "retencion_plataforma";
        }
        cfdi.cuenta_pago = cfdi.cuenta_pago || (cfdi.tratamiento === "crear_auxiliar" ? cuentaPorFormaPago(cfdi.forma_pago, cuentaCfdiDefault()) : cuentaCfdiDefault());
        cfdi.movimiento_relacionado = cfdi.movimiento_relacionado || "";
        cfdi.estatus_relacion = cfdi.movimiento_relacionado ? "ligado" : (cfdi.estatus_relacion || "pendiente");
        return cfdi;
    }
    function normalizarCfdisLegacy() {
        cfdis = cfdis.map(normalizarCfdiLegacy);
    }
    function cfdisPeriodoActual() {
        var periodo = periodoCfdiActual();
        return cfdis.filter(function (cfdi) {
            normalizarCfdiLegacy(cfdi);
            return !periodo || cfdi.periodo === periodo;
        });
    }
    function relacionCfdi(cfdi) {
        var ligado = movimientos.find(function (m) { return m.cfdi_uuid === cfdi.uuid || m.id === cfdi.movimiento_relacionado; });
        if (ligado) { return "ligado"; }
        var sugerido = movimientos.map(function (mov) { return {mov: mov, score: scoreCfdi(mov, cfdi)}; })
            .filter(function (x) { return x.score >= 40; })
            .sort(function (a, b) { return b.score - a.score; })[0];
        return sugerido ? "sugerido" : "sin_banco";
    }
    function filtrosCfdi() {
        return {
            descripcion: normalizar($("cfdi_filtro_descripcion") ? $("cfdi_filtro_descripcion").value : ""),
            categoria: $("cfdi_filtro_categoria") ? $("cfdi_filtro_categoria").value : "",
            actividad: $("cfdi_filtro_actividad") ? $("cfdi_filtro_actividad").value : "",
            forma_pago: $("cfdi_filtro_forma_pago") ? $("cfdi_filtro_forma_pago").value : "",
            cuenta: $("cfdi_filtro_cuenta") ? $("cfdi_filtro_cuenta").value : "",
            relacion: $("cfdi_filtro_relacion") ? $("cfdi_filtro_relacion").value : ""
        };
    }
    function cfdisFiltrados() {
        var f = filtrosCfdi();
        return cfdisPeriodoActual().filter(function (cfdi) {
            var texto = normalizar([cfdi.emisor, cfdi.rfc_emisor, cfdi.uuid, cfdi.archivo, cfdi.concepto_principal, cfdi.conceptos_resumen, cfdi.doctos_relacionados].join(" "));
            return (!f.descripcion || texto.indexOf(f.descripcion) >= 0) &&
                (!f.categoria || cfdi.categoria === f.categoria) &&
                (!f.actividad || cfdi.actividad === f.actividad) &&
                (!f.forma_pago || cfdi.forma_pago === f.forma_pago) &&
                (!f.cuenta || cfdi.cuenta_pago === f.cuenta) &&
                (!f.relacion || relacionCfdi(cfdi) === f.relacion);
        });
    }
    function renderDescripcionesCfdiFiltro() {
        var datalist = $("cfdi_descripciones_sugeridas");
        if (!datalist) { return; }
        var opciones = {};
        cfdisPeriodoActual().forEach(function (cfdi) {
            [cfdi.emisor, cfdi.rfc_emisor, cfdi.uuid, cfdi.concepto_principal, cfdi.archivo].forEach(function (valor) {
                valor = String(valor || "").trim();
                if (valor) { opciones[valor] = true; }
            });
        });
        datalist.innerHTML = Object.keys(opciones).sort(function (a, b) {
            return a.localeCompare(b, "es");
        }).slice(0, 250).map(function (valor) {
            return "<option value=\"" + escapeHtml(valor) + "\"></option>";
        }).join("");
    }
    function idsCfdiSeleccionadosValidos() {
        var ids = {};
        cfdis.forEach(function (cfdi) {
            normalizarCfdiLegacy(cfdi);
            ids[cfdi.id] = true;
        });
        Object.keys(cfdisSeleccionados).forEach(function (id) {
            if (!ids[id]) { delete cfdisSeleccionados[id]; }
        });
        return Object.keys(cfdisSeleccionados).filter(function (id) { return cfdisSeleccionados[id]; });
    }
    function limpiarSeleccionCfdi() {
        cfdisSeleccionados = {};
        render();
    }
    function seleccionarCfdisVisibles(activo) {
        cfdisFiltrados().forEach(function (cfdi) {
            if (activo) {
                cfdisSeleccionados[cfdi.id] = true;
            } else {
                delete cfdisSeleccionados[cfdi.id];
            }
        });
        render();
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
    function estadoManualCuenta(cuenta, periodo) {
        var existente = estadosCuenta.find(function (edo) {
            return edo.origen === "manual" && edo.cuenta === cuenta && edo.periodo === periodo;
        });
        if (existente) { return existente.id; }
        var id = "edo-manual-" + Date.now() + "-" + estadosCuenta.length;
        estadosCuenta.push({
            id: id,
            archivo: "captura_manual",
            periodo: periodo,
            cuenta: cuenta,
            filas: 0,
            columnas: 0,
            hoja: "",
            movimientos: 0,
            origen: "manual",
            fecha_carga: new Date().toLocaleString("es-MX")
        });
        return id;
    }
    function agregarMovimientoManual() {
        var fecha = $("manual_fecha").value || "";
        var concepto = $("manual_concepto").value.trim();
        var tipo = $("manual_movimiento").value || "egreso";
        var actividad = $("manual_actividad").value || "negocio";
        var monto = numero($("manual_monto").value);
        var periodo = periodoEstadoCuentaActual();
        var cuenta = $("contabilidad_cuenta").value || "Tarjeta de credito";
        if (!fecha || !concepto || !monto) {
            mostrarError("Captura fecha, concepto y monto para agregar el movimiento manual.");
            return;
        }
        var estadoId = estadoManualCuenta(cuenta, periodo);
        var mov = normalizarMovimientoLegacy({
            id: "mov-manual-" + Date.now() + "-" + movimientos.length,
            fecha: fecha,
            periodo: periodo,
            cuenta: cuenta,
            concepto: concepto,
            referencia: "",
            cargo: tipo === "egreso" ? Math.abs(monto) : 0,
            abono: tipo === "ingreso" ? Math.abs(monto) : 0,
            monto: montoFirmado(tipo, monto),
            tipo_movimiento: tipo,
            actividad: actividad,
            forma_pago: sugerirFormaPago(concepto, tipo),
            categoria: sugerirCategoria(concepto, tipo, actividad),
            origen: "manual",
            cfdi: "",
            cfdi_uuid: "",
            cfdi_sugerencia: "",
            archivo_estado_cuenta: "captura_manual",
            estado_cuenta_id: estadoId,
            notas: "Captura manual."
        });
        mov.cfdi = cfdiEsperado(mov);
        movimientos.push(mov);
        estadosCuenta.forEach(function (edo) {
            edo.movimientos = movimientos.filter(function (m) { return m.estado_cuenta_id === edo.id; }).length;
        });
        estadoSeleccionadoId = estadoId;
        $("manual_concepto").value = "";
        $("manual_monto").value = "";
        render();
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
                monto: montoFirmado(tipo, abono > 0 ? abono : cargo),
                periodo: periodoEstadoPorId(estadoId),
                tipo_movimiento: tipo,
                actividad: actividad,
                forma_pago: formaPago,
                categoria: categoria,
                origen: "estado_cuenta",
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
            var tipoMapeado = valorMapeado(row, "map_movimiento") ? normalizarTipoMapeado(valorMapeado(row, "map_movimiento"), "", 0, 0) : tipoPorMontoFirmado(monto);
            var cargo = tipoMapeado === "ingreso" ? 0 : Math.abs(monto);
            var abono = tipoMapeado === "ingreso" ? Math.abs(monto) : 0;
            var concepto = valorMapeado(row, "map_concepto") || "Movimiento sin concepto";
            var tipo = valorMapeado(row, "map_movimiento") ? normalizarTipoMapeado(valorMapeado(row, "map_movimiento"), concepto, cargo, abono) : tipoPorMontoFirmado(monto);
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
                monto: montoFirmado(tipo, monto),
                periodo: periodoEstadoPorId(estadoId),
                tipo_movimiento: tipo,
                actividad: actividad,
                forma_pago: formaPago,
                categoria: categoria,
                origen: "estado_cuenta",
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
    function nodosXml(xml, localName) {
        return Array.prototype.slice.call(xml.getElementsByTagNameNS("*", localName));
    }
    function resumenConceptosCfdi(xml) {
        var conceptos = nodosXml(xml, "Concepto").map(function (nodo) {
            return {
                descripcion: nodo.getAttribute("Descripcion") || "",
                clave: nodo.getAttribute("ClaveProdServ") || "",
                cantidad: nodo.getAttribute("Cantidad") || "",
                importe: numero(nodo.getAttribute("Importe") || 0)
            };
        });
        var resumen = conceptos.slice(0, 3).map(function (c) {
            return c.descripcion + (c.importe ? " " + money(c.importe) : "");
        }).filter(Boolean).join(" | ");
        return {
            conceptos: conceptos,
            concepto_principal: conceptos.length ? conceptos[0].descripcion : "",
            conceptos_resumen: resumen,
            conceptos_total: conceptos.length
        };
    }
    function impuestosCfdi(xml) {
        var data = {
            iva_trasladado: 0,
            iva_retenido: 0,
            isr_retenido: 0,
            total_impuestos_trasladados: 0,
            total_impuestos_retenidos: 0
        };
        nodosXml(xml, "Impuestos").forEach(function (nodo) {
            data.total_impuestos_trasladados += numero(nodo.getAttribute("TotalImpuestosTrasladados") || 0);
            data.total_impuestos_retenidos += numero(nodo.getAttribute("TotalImpuestosRetenidos") || 0);
        });
        nodosXml(xml, "Traslado").forEach(function (nodo) {
            if ((nodo.getAttribute("Impuesto") || "") === "002") {
                data.iva_trasladado += numero(nodo.getAttribute("Importe") || 0);
            }
        });
        nodosXml(xml, "Retencion").forEach(function (nodo) {
            var impuesto = nodo.getAttribute("Impuesto") || "";
            if (impuesto === "002") { data.iva_retenido += numero(nodo.getAttribute("Importe") || 0); }
            if (impuesto === "001") { data.isr_retenido += numero(nodo.getAttribute("Importe") || 0); }
        });
        return data;
    }
    function pagosCfdi(xml) {
        var pagos = nodosXml(xml, "Pago").map(function (nodo) {
            return {
                fecha: (nodo.getAttribute("FechaPago") || "").slice(0, 10),
                forma_pago_sat: nodo.getAttribute("FormaDePagoP") || "",
                moneda: nodo.getAttribute("MonedaP") || "MXN",
                monto: numero(nodo.getAttribute("Monto") || 0),
                num_operacion: nodo.getAttribute("NumOperacion") || ""
            };
        });
        var totalNodo = nodosXml(xml, "Totales")[0];
        var totalPagos = numero(totalNodo ? totalNodo.getAttribute("MontoTotalPagos") : 0);
        var sumaPagos = pagos.reduce(function (s, pago) { return s + Number(pago.monto || 0); }, 0);
        var doctos = nodosXml(xml, "DoctoRelacionado").map(function (nodo) {
            return {
                uuid: nodo.getAttribute("IdDocumento") || "",
                serie: nodo.getAttribute("Serie") || "",
                folio: nodo.getAttribute("Folio") || "",
                moneda: nodo.getAttribute("MonedaDR") || "",
                metodo_pago: nodo.getAttribute("MetodoDePagoDR") || "",
                parcialidad: nodo.getAttribute("NumParcialidad") || "",
                saldo_anterior: numero(nodo.getAttribute("ImpSaldoAnt") || 0),
                pagado: numero(nodo.getAttribute("ImpPagado") || 0),
                saldo_insoluto: numero(nodo.getAttribute("ImpSaldoInsoluto") || 0)
            };
        });
        var doctosResumen = doctos.slice(0, 4).map(function (d) {
            return [d.serie, d.folio].filter(Boolean).join("-") || d.uuid || "Docto relacionado";
        }).join(" | ");
        var pagosResumen = pagos.map(function (pago) {
            return [pago.fecha, pago.forma_pago_sat, money(pago.monto)].filter(Boolean).join(" | ");
        }).join(" / ");
        return {
            es_pago: pagos.length > 0 || totalPagos > 0,
            monto: totalPagos || sumaPagos,
            fecha: pagos.length ? pagos[0].fecha : "",
            forma_pago_sat: pagos.length ? pagos[0].forma_pago_sat : "",
            moneda: pagos.length ? pagos[0].moneda : "",
            num_operacion: pagos.map(function (pago) { return pago.num_operacion; }).filter(Boolean).join(" | "),
            pagos_resumen: pagosResumen,
            doctos_relacionados: doctosResumen
        };
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-08-29
     * Proposito: extraer metadatos principales de XML CFDI para sugerir su movimiento bancario.
     * Impacto: Contabilidad; ayuda a corroborar compras/gastos sin validar fiscalmente el comprobante.
     * Contrato: acepta CFDI 3.3/4.0, incluyendo complementos de pago con Total 0.
     */
    function parseCfdiXml(texto, nombre) {
        var xml = new DOMParser().parseFromString(texto, "text/xml");
        var comprobante = xml.getElementsByTagNameNS("*", "Comprobante")[0] || xml.getElementsByTagName("cfdi:Comprobante")[0] || xml.documentElement;
        var emisor = xml.getElementsByTagNameNS("*", "Emisor")[0] || xml.getElementsByTagName("cfdi:Emisor")[0];
        var receptor = xml.getElementsByTagNameNS("*", "Receptor")[0] || xml.getElementsByTagName("cfdi:Receptor")[0];
        var timbre = xml.getElementsByTagNameNS("*", "TimbreFiscalDigital")[0] || xml.getElementsByTagName("tfd:TimbreFiscalDigital")[0];
        var pagos = pagosCfdi(xml);
        var tipoComprobante = comprobante ? (comprobante.getAttribute("TipoDeComprobante") || "") : "";
        var totalComprobante = numero(comprobante ? comprobante.getAttribute("Total") : 0);
        var fecha = pagos.es_pago && pagos.fecha ? pagos.fecha : (comprobante ? (comprobante.getAttribute("Fecha") || "").slice(0, 10) : "");
        var formaSat = pagos.es_pago && pagos.forma_pago_sat ? pagos.forma_pago_sat : (comprobante ? (comprobante.getAttribute("FormaPago") || "") : "");
        var categoriaDefault = categoriaCfdiDefault();
        var forma = formaPagoCfdiDefault(categoriaDefault, formaSat);
        var tratamientoDefault = tratamientoCfdiDefault();
        var conceptos = resumenConceptosCfdi(xml);
        var impuestos = impuestosCfdi(xml);
        var totalOperativo = pagos.es_pago ? pagos.monto : totalComprobante;
        var conceptosResumen = pagos.es_pago && pagos.pagos_resumen ? pagos.pagos_resumen : conceptos.conceptos_resumen;
        var conceptoPrincipal = pagos.es_pago ? "Complemento de pago" : conceptos.concepto_principal;
        return normalizarCfdiLegacy({
            archivo: nombre,
            uuid: timbre ? (timbre.getAttribute("UUID") || "") : "",
            fecha: fecha,
            periodo: fecha ? fecha.slice(0, 7) : periodoCfdiActual(),
            subtotal: numero(comprobante ? comprobante.getAttribute("SubTotal") : 0),
            descuento: numero(comprobante ? comprobante.getAttribute("Descuento") : 0),
            total: totalOperativo,
            moneda: pagos.moneda || (comprobante ? (comprobante.getAttribute("Moneda") || "MXN") : "MXN"),
            tipo: tipoComprobante,
            metodo_pago: comprobante ? (comprobante.getAttribute("MetodoPago") || "") : "",
            forma_pago_sat: formaSat,
            forma_pago: forma,
            cuenta_pago: tratamientoDefault === "crear_auxiliar" ? cuentaPorFormaPago(forma, cuentaCfdiDefault()) : cuentaCfdiDefault(),
            tratamiento: tratamientoDefault,
            origen: tratamientoDefault === "crear_auxiliar" ? "cfdi_auxiliar" : "cfdi",
            categoria: categoriaDefault,
            actividad: "negocio",
            estatus_relacion: "pendiente",
            movimiento_relacionado: "",
            serie: comprobante ? (comprobante.getAttribute("Serie") || "") : "",
            folio: comprobante ? (comprobante.getAttribute("Folio") || "") : "",
            rfc_emisor: emisor ? (emisor.getAttribute("Rfc") || "") : "",
            emisor: emisor ? (emisor.getAttribute("Nombre") || "") : "",
            rfc_receptor: receptor ? (receptor.getAttribute("Rfc") || "") : "",
            receptor: receptor ? (receptor.getAttribute("Nombre") || "") : "",
            uso_cfdi: receptor ? (receptor.getAttribute("UsoCFDI") || "") : "",
            concepto_principal: conceptoPrincipal,
            conceptos_resumen: conceptosResumen,
            conceptos_total: conceptos.conceptos_total,
            pago_complemento: pagos.es_pago || tipoComprobante === "P",
            pago_fecha: pagos.fecha,
            pago_monto: pagos.monto,
            pagos_resumen: pagos.pagos_resumen,
            doctos_relacionados: pagos.doctos_relacionados,
            num_operacion: pagos.num_operacion,
            iva_trasladado: impuestos.iva_trasladado,
            iva_retenido: impuestos.iva_retenido,
            isr_retenido: impuestos.isr_retenido,
            total_impuestos_trasladados: impuestos.total_impuestos_trasladados,
            total_impuestos_retenidos: impuestos.total_impuestos_retenidos
        });
    }
    function montoMovimiento(mov) {
        return Math.abs(Number(mov.monto || mov.cargo || mov.abono || 0));
    }
    function diasEntre(fechaA, fechaB) {
        if (!fechaA || !fechaB) { return 999; }
        return Math.abs((new Date(fechaA + "T00:00:00") - new Date(fechaB + "T00:00:00")) / 86400000);
    }
    function montoCfdiRelacion(cfdi) {
        return Math.abs(Number(cfdi.pago_complemento ? (cfdi.pago_monto || cfdi.total || 0) : (cfdi.total || 0)));
    }
    function fechaCfdiRelacion(cfdi) {
        return cfdi.pago_complemento ? (cfdi.pago_fecha || cfdi.fecha || "") : (cfdi.fecha || "");
    }
    function coincidenciaCfdi(mov, cfdi) {
        var monto = montoMovimiento(mov);
        var totalCfdi = montoCfdiRelacion(cfdi);
        var texto = normalizar([mov.concepto, mov.referencia, mov.cfdi_uuid].join(" "));
        var dias = diasEntre(mov.fecha, fechaCfdiRelacion(cfdi));
        var diferencia = Math.abs(totalCfdi - monto);
        var motivos = [];
        var score = 0;
        var montoCompatible = diferencia <= 1;
        if (diferencia <= 0.01) { score += 70; motivos.push("monto exacto"); }
        else if (montoCompatible) { score += 55; motivos.push("monto por redondeo"); }
        else if (diferencia <= 5) { score += 40; motivos.push("monto cercano"); }
        if (dias === 0) { score += 20; motivos.push("misma fecha"); }
        else if (dias <= 3) { score += 10; motivos.push("fecha cercana"); }
        else if (dias <= 7) { score += 5; motivos.push("misma semana"); }
        if (cfdi.rfc_emisor && texto.indexOf(normalizar(cfdi.rfc_emisor)) >= 0) { score += 15; motivos.push("RFC en concepto"); }
        if (cfdi.emisor && texto.indexOf(normalizar(cfdi.emisor).slice(0, 10)) >= 0) { score += 10; motivos.push("emisor parecido"); }
        if (cfdi.folio && texto.indexOf(normalizar(cfdi.folio)) >= 0) { score += 10; motivos.push("folio en concepto"); }
        if (cfdi.uuid && texto.indexOf(normalizar(cfdi.uuid)) >= 0) { score += 20; motivos.push("UUID en concepto"); }
        if (cfdi.cuenta_pago && mov.cuenta && normalizar(cfdi.cuenta_pago) === normalizar(mov.cuenta)) { score += 15; motivos.push("misma cuenta"); }
        if (mov.tipo_movimiento === "egreso") { score += 5; }
        if (mov.actividad === "transpaso" || mov.tipo_movimiento === "ingreso") { score -= 30; }
        return {
            score: score,
            exacta: montoCompatible,
            montoCompatible: montoCompatible,
            diferencia: diferencia,
            dias: dias,
            motivos: motivos
        };
    }
    function scoreCfdi(mov, cfdi) {
        return coincidenciaCfdi(mov, cfdi).score;
    }
    function autoRelacionarCfdi() {
        movimientos.forEach(function (mov) {
            if (mov.cfdi === "ligado") { return; }
            if (mov.tipo_movimiento !== "egreso" || mov.actividad === "transpaso") {
                mov.cfdi_sugerencia = "";
                return;
            }
            var candidatos = cfdis.map(function (cfdi) {
                return {cfdi: cfdi, score: scoreCfdi(mov, cfdi)};
            }).filter(function (x) {
                return x.score >= 40 && !cfdiTieneMovimiento(x.cfdi);
            }).sort(function (a, b) {
                return b.score - a.score;
            });
            mov.cfdi_sugerencia = candidatos.length ? candidatos[0].cfdi.uuid : "";
        });
    }
    function ordenarCandidatosRelacion(items) {
        return items.sort(function (a, b) {
            if (a.coincidencia.exacta !== b.coincidencia.exacta) { return a.coincidencia.exacta ? -1 : 1; }
            return b.coincidencia.score - a.coincidencia.score;
        });
    }
    function movimientoDisponibleParaCfdi(mov, cfdi) {
        if (!mov || mov.tipo_movimiento !== "egreso" || mov.actividad === "transpaso") { return false; }
        if (mov.cfdi_uuid && mov.cfdi_uuid !== cfdi.uuid) { return false; }
        if (mov.cfdi === "ligado" && mov.cfdi_uuid !== cfdi.uuid) { return false; }
        return true;
    }
    function cfdiDisponibleParaMovimiento(cfdi, mov) {
        if (!cfdi) { return false; }
        if (cfdi.movimiento_relacionado && cfdi.movimiento_relacionado !== mov.id) { return false; }
        return !cfdiTieneMovimiento(cfdi) || cfdi.movimiento_relacionado === mov.id || mov.cfdi_uuid === cfdi.uuid;
    }
    function relacionarMovimientoCfdi(movId, cfdiId) {
        var mov = movimientos.find(function (m) { return m.id === movId; });
        var cfdi = cfdiPorId(cfdiId);
        if (!mov || !cfdi) { return; }
        if (mov.cfdi_uuid && mov.cfdi_uuid !== cfdi.uuid) {
            mostrarError("Ese movimiento ya tiene otro CFDI. Deshaz la relacion antes de cambiarlo.");
            return;
        }
        if (cfdi.movimiento_relacionado && cfdi.movimiento_relacionado !== mov.id) {
            mostrarError("Ese CFDI ya esta relacionado con otro movimiento. Deshaz la relacion antes de cambiarlo.");
            return;
        }
        mov.cfdi = "ligado";
        mov.cfdi_uuid = cfdi.uuid;
        mov.cfdi_sugerencia = "";
        mov.categoria = cfdi.categoria || mov.categoria;
        mov.actividad = cfdi.actividad || mov.actividad;
        mov.forma_pago = cfdi.forma_pago || mov.forma_pago;
        cfdi.movimiento_relacionado = mov.id;
        cfdi.estatus_relacion = "ligado";
        cfdi.tratamiento = "conciliar_banco";
        cfdi.origen = "cfdi";
        var modal = bootstrap.Modal.getInstance($("contabilidad_relacion_modal"));
        if (modal) { modal.hide(); }
        autoRelacionarCfdi();
        render();
    }
    function textoBusquedaMovimiento(mov) {
        return normalizar([mov.fecha, mov.cuenta, mov.concepto, mov.referencia, mov.monto, mov.cargo, mov.abono, mov.cfdi_uuid].join(" "));
    }
    function textoBusquedaCfdi(cfdi) {
        return normalizar([cfdi.fecha, fechaCfdiRelacion(cfdi), cfdi.cuenta_pago, cfdi.emisor, cfdi.rfc_emisor, cfdi.uuid, cfdi.serie, cfdi.folio, cfdi.conceptos_resumen, montoCfdiRelacion(cfdi)].join(" "));
    }
    function renderCandidatosRelacion() {
        var tbody = $("contabilidad_relacion_candidatos");
        if (!tbody) { return; }
        var busqueda = normalizar(relacionContexto.busqueda || "");
        var rows = [];
        if (relacionContexto.tipo === "cfdi") {
            var cfdi = cfdiPorId(relacionContexto.cfdiId);
            if (!cfdi) { return; }
            rows = movimientosPeriodoActual().filter(function (mov) {
                return movimientoDisponibleParaCfdi(mov, cfdi) && (!busqueda || textoBusquedaMovimiento(mov).indexOf(busqueda) >= 0);
            });
            rows = ordenarCandidatosRelacion(rows.map(function (mov) {
                return {mov: mov, cfdi: cfdi, coincidencia: coincidenciaCfdi(mov, cfdi)};
            }));
        } else {
            var movBase = movimientos.find(function (m) { return m.id === relacionContexto.movId; });
            if (!movBase) { return; }
            rows = cfdisPeriodoActual().filter(function (cfdi) {
                return cfdiDisponibleParaMovimiento(cfdi, movBase) && (!busqueda || textoBusquedaCfdi(cfdi).indexOf(busqueda) >= 0);
            }).map(function (cfdi) {
                return {mov: movBase, cfdi: cfdi, coincidencia: coincidenciaCfdi(movBase, cfdi)};
            });
            rows = ordenarCandidatosRelacion(rows);
        }
        var exactas = rows.filter(function (item) { return item.coincidencia.exacta; }).length;
        $("contabilidad_relacion_estado").textContent = exactas ? exactas + " candidato(s) con monto compatible. La fecha solo es referencia." : "Sin monto compatible; selecciona manualmente solo si corresponde.";
        tbody.innerHTML = rows.map(function (item) {
            var mov = item.mov;
            var cfdi = item.cfdi;
            var c = item.coincidencia;
            var descripcion = relacionContexto.tipo === "cfdi" ?
                "<div class=\"fw-semibold\">" + escapeHtml(mov.concepto || "") + "</div><div class=\"text-muted fs-9\">" + escapeHtml(mov.referencia || "") + "</div>" :
                "<div class=\"fw-semibold\">" + escapeHtml(cfdi.emisor || cfdi.rfc_emisor || cfdi.archivo || "") + "</div><div class=\"text-muted fs-9\">" + escapeHtml(cfdi.uuid || "") + "</div><div class=\"fs-9\">" + escapeHtml(cfdi.conceptos_resumen || cfdi.concepto_principal || "") + "</div>";
            var cuenta = relacionContexto.tipo === "cfdi" ? (mov.cuenta || "-") : (cfdi.cuenta_pago || "-");
            var fecha = relacionContexto.tipo === "cfdi" ? (mov.fecha || "-") : (fechaCfdiRelacion(cfdi) || cfdi.fecha || "-");
            var monto = relacionContexto.tipo === "cfdi" ? montoMovimiento(mov) : montoCfdiRelacion(cfdi);
            var badge = c.exacta ? "badge-light-success" : (c.score >= 70 ? "badge-light-primary" : "badge-light-warning");
            var etiqueta = c.exacta ? "Monto compatible" : "Revisar";
            var detalle = c.motivos.length ? c.motivos.join(", ") : "sin coincidencias fuertes";
            return "<tr>" +
                "<td class=\"text-nowrap\">" + escapeHtml(fecha) + "</td>" +
                "<td>" + escapeHtml(cuenta) + "</td>" +
                "<td>" + descripcion + "</td>" +
                "<td><span class=\"badge " + badge + "\">" + etiqueta + "</span><div class=\"text-muted fs-9 mt-1\">" + escapeHtml(detalle) + " | " + c.score + " pts</div></td>" +
                "<td class=\"text-end fw-bold\">" + money(monto) + "</td>" +
                "<td class=\"text-end\"><button class=\"btn btn-sm btn-primary\" type=\"button\" data-confirmar-relacion data-mov-id=\"" + escapeHtml(mov.id) + "\" data-cfdi-id=\"" + escapeHtml(cfdi.id) + "\"><i class=\"bi bi-check2-circle\"></i> Relacionar</button></td>" +
                "</tr>";
        }).join("") || "<tr><td colspan=\"6\" class=\"text-center text-muted py-8\">No hay candidatos con los filtros actuales.</td></tr>";
    }
    function abrirRelacionCfdi(id) {
        var cfdi = cfdiPorId(id);
        if (!cfdi) { return; }
        relacionContexto = {tipo: "cfdi", movId: "", cfdiId: cfdi.id, busqueda: ""};
        $("contabilidad_relacion_titulo").textContent = "Relacionar CFDI con estado de cuenta";
        $("contabilidad_relacion_subtitulo").textContent = cfdi.uuid || cfdi.archivo || "";
        $("contabilidad_relacion_resumen").innerHTML = "<div class=\"fw-bold\">" + escapeHtml(cfdi.emisor || cfdi.rfc_emisor || "CFDI") + "</div>" +
            "<div class=\"fs-8\">" + escapeHtml(fechaCfdiRelacion(cfdi) || cfdi.fecha || "-") + " | " + money(montoCfdiRelacion(cfdi)) + " | " + escapeHtml(cfdi.cuenta_pago || "Por relacionar") + "</div>";
        $("contabilidad_relacion_buscar").value = "";
        renderCandidatosRelacion();
        bootstrap.Modal.getOrCreateInstance($("contabilidad_relacion_modal")).show();
    }
    function abrirRelacionMovimiento(id) {
        var mov = movimientos.find(function (m) { return m.id === id; });
        if (!mov) { return; }
        relacionContexto = {tipo: "mov", movId: mov.id, cfdiId: "", busqueda: ""};
        $("contabilidad_relacion_titulo").textContent = "Relacionar movimiento con CFDI";
        $("contabilidad_relacion_subtitulo").textContent = mov.cuenta || "";
        $("contabilidad_relacion_resumen").innerHTML = "<div class=\"fw-bold\">" + escapeHtml(mov.concepto || "Movimiento") + "</div>" +
            "<div class=\"fs-8\">" + escapeHtml(mov.fecha || "-") + " | " + money(montoMovimiento(mov)) + " | " + escapeHtml(mov.referencia || "") + "</div>";
        $("contabilidad_relacion_buscar").value = "";
        renderCandidatosRelacion();
        bootstrap.Modal.getOrCreateInstance($("contabilidad_relacion_modal")).show();
    }
    function cfdiPorId(id) {
        return cfdis.find(function (c) { return c.id === id || c.uuid === id; });
    }
    function ligarCfdiSugerido(id) {
        abrirRelacionCfdi(id);
    }
    function crearMovimientoDesdeCfdi(id, diferirRender) {
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
            monto: montoFirmado("egreso", cfdi.total),
            tipo_movimiento: "egreso",
            actividad: cfdi.actividad || "negocio",
            forma_pago: cfdi.forma_pago || "efectivo",
            categoria: cfdi.categoria || "gasto_operativo",
            cfdi: "ligado",
            cfdi_uuid: cfdi.uuid,
            cfdi_sugerencia: "",
            origen: "cfdi_auxiliar",
            archivo_estado_cuenta: "CFDI auxiliar",
            estado_cuenta_id: "",
            notas: cfdi.categoria === "comision_plataforma" ? "Comision/descuento retenido por plataforma; creado desde CFDI." : "Movimiento auxiliar creado desde CFDI."
        });
        movimientos.push(mov);
        cfdi.movimiento_relacionado = mov.id;
        cfdi.estatus_relacion = "ligado";
        cfdi.tratamiento = "crear_auxiliar";
        cfdi.origen = "cfdi_auxiliar";
        if (!diferirRender) { render(); }
    }
    function crearMovimientosCfdiSinBanco() {
        var creados = 0;
        cfdis.forEach(function (cfdi) {
            var ligado = movimientos.some(function (m) { return m.cfdi_uuid === cfdi.uuid; });
            if (!ligado) {
                crearMovimientoDesdeCfdi(cfdi.id, true);
                creados++;
            }
        });
        if (!creados) {
            mostrarError("Todos los CFDI ya tienen movimiento relacionado.");
            return;
        }
        render();
    }
    function aplicarMasivoCfdi() {
        var ids = idsCfdiSeleccionadosValidos();
        if (!ids.length) {
            mostrarError("Selecciona al menos un CFDI.");
            return;
        }
        var cambios = {
            tratamiento: $("cfdi_masivo_tratamiento").value,
            categoria: $("cfdi_masivo_categoria").value,
            actividad: $("cfdi_masivo_actividad").value,
            forma_pago: $("cfdi_masivo_forma_pago").value,
            cuenta_pago: $("cfdi_masivo_cuenta").value
        };
        if (!cambios.tratamiento && !cambios.categoria && !cambios.actividad && !cambios.forma_pago && !cambios.cuenta_pago) {
            mostrarError("Elige al menos un campo para aplicar.");
            return;
        }
        cfdis.forEach(function (cfdi) {
            if (!cfdisSeleccionados[cfdi.id]) { return; }
            Object.keys(cambios).forEach(function (campo) {
                if (cambios[campo]) { cfdi[campo] = cambios[campo]; }
            });
            if (cfdi.categoria === "comision_plataforma" && !cambios.forma_pago) {
                cfdi.forma_pago = "retencion_plataforma";
            }
            if (!cambios.cuenta_pago) {
                cfdi.cuenta_pago = cuentaPorFormaPago(cfdi.forma_pago, cfdi.cuenta_pago || cuentaCfdiDefault());
            }
            cfdi.origen = cfdi.tratamiento === "crear_auxiliar" ? "cfdi_auxiliar" : "cfdi";
            normalizarCfdiLegacy(cfdi);
        });
        ["cfdi_masivo_tratamiento", "cfdi_masivo_categoria", "cfdi_masivo_actividad", "cfdi_masivo_forma_pago", "cfdi_masivo_cuenta"].forEach(function (id) {
            if ($(id)) { $(id).value = ""; }
        });
        render();
    }
    function crearAuxiliaresCfdiSeleccionados() {
        var ids = idsCfdiSeleccionadosValidos();
        if (!ids.length) {
            mostrarError("Selecciona al menos un CFDI.");
            return;
        }
        var creados = 0;
        ids.forEach(function (id) {
            var cfdi = cfdiPorId(id);
            var ligado = cfdi && movimientos.some(function (m) { return m.cfdi_uuid === cfdi.uuid; });
            if (cfdi && !ligado) {
                cfdi.tratamiento = "crear_auxiliar";
                cfdi.origen = "cfdi_auxiliar";
                crearMovimientoDesdeCfdi(id, true);
                creados++;
            }
        });
        if (!creados) {
            mostrarError("Los CFDI seleccionados ya tienen movimiento relacionado.");
            return;
        }
        render();
    }
    function eliminarCfdi(id) {
        confirmarAccion("Eliminar este CFDI cargado de la revision local?", function () {
            var cfdi = cfdiPorId(id);
            cfdis = cfdis.filter(function (c) { return c.id !== id && c.uuid !== id; });
            delete cfdisSeleccionados[id];
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
    function esMovimientoAuxiliarCfdi(mov) {
        var archivo = normalizar(mov.archivo_estado_cuenta || "");
        var notas = normalizar(mov.notas || "");
        return mov.origen === "cfdi_auxiliar" ||
            archivo.indexOf("cfdi auxiliar") >= 0 ||
            archivo.indexOf("cfdi sin estado") >= 0 ||
            notas.indexOf("movimiento auxiliar creado desde cfdi") >= 0 ||
            notas.indexOf("retenido por plataforma") >= 0;
    }
    function desligarCfdi(id) {
        var cfdi = cfdiPorId(id);
        if (!cfdi) { return; }
        var movimientoId = cfdi.movimiento_relacionado || "";
        var ligados = movimientos.filter(function (mov) {
            return (cfdi.uuid && mov.cfdi_uuid === cfdi.uuid) || (movimientoId && mov.id === movimientoId);
        });
        ligados.forEach(function (mov) {
            if (esMovimientoAuxiliarCfdi(mov)) {
                movimientos = movimientos.filter(function (m) { return m.id !== mov.id; });
                delete seleccionados[mov.id];
            } else {
                mov.cfdi_uuid = "";
                mov.cfdi_sugerencia = "";
                mov.cfdi = cfdiEsperado(mov);
            }
        });
        cfdi.movimiento_relacionado = "";
        cfdi.estatus_relacion = "pendiente";
        cfdi.tratamiento = "conciliar_banco";
        cfdi.origen = "cfdi";
        render();
    }
    function movimientoBancarioLigadoCfdi(cfdi) {
        var movimientoId = cfdi.movimiento_relacionado || "";
        return movimientos.find(function (mov) {
            if (esMovimientoAuxiliarCfdi(mov)) { return false; }
            return (cfdi.uuid && mov.cfdi_uuid === cfdi.uuid) || (movimientoId && mov.id === movimientoId);
        });
    }
    function deshacerRelacionNoExacta(cfdi) {
        var mov = movimientoBancarioLigadoCfdi(cfdi);
        if (!mov || coincidenciaCfdi(mov, cfdi).exacta) { return false; }
        mov.cfdi_uuid = "";
        mov.cfdi_sugerencia = "";
        mov.cfdi = cfdiEsperado(mov);
        cfdi.movimiento_relacionado = "";
        cfdi.estatus_relacion = "pendiente";
        cfdi.tratamiento = "conciliar_banco";
        cfdi.origen = "cfdi";
        delete cfdisSeleccionados[cfdi.id];
        delete seleccionados[mov.id];
        return true;
    }
    function deshacerRelacionesNoExactas() {
        var candidatos = cfdisPeriodoActual().filter(function (cfdi) {
            normalizarCfdiLegacy(cfdi);
            return !!movimientoBancarioLigadoCfdi(cfdi) && !coincidenciaCfdi(movimientoBancarioLigadoCfdi(cfdi), cfdi).exacta;
        });
        if (!candidatos.length) {
            mostrarError("No encontre relaciones bancarias no exactas en el mes actual.");
            return;
        }
        confirmarAccion("Se quitaran " + candidatos.length + " relaciones CFDI-banco cuyo monto no coincide o no cuadra por redondeo. La fecha no se tomara como requisito. No se eliminaran estados de cuenta ni auxiliares CFDI.", function () {
            var quitadas = 0;
            candidatos.forEach(function (cfdi) {
                if (deshacerRelacionNoExacta(cfdi)) { quitadas++; }
            });
            autoRelacionarCfdi();
            render();
            if (window.Swal) {
                Swal.fire({text: quitadas + " relaciones con monto no compatible quedaron pendientes para revisar.", icon: "success", timer: 1600, showConfirmButton: false});
            }
        });
    }
    function deshacerAuxiliaresCfdiSeleccionados() {
        var ids = idsCfdiSeleccionadosValidos();
        if (!ids.length) {
            mostrarError("Selecciona los CFDI a los que quieres quitarles el auxiliar.");
            return;
        }
        confirmarAccion("Se quitaran los movimientos auxiliares creados desde los CFDI seleccionados. Los estados de cuenta no se tocaran.", function () {
            var uuids = {};
            ids.forEach(function (id) {
                var cfdi = cfdiPorId(id);
                if (cfdi && cfdi.uuid) { uuids[cfdi.uuid] = true; }
            });
            var eliminados = 0;
            movimientos = movimientos.filter(function (mov) {
                if (uuids[mov.cfdi_uuid] && esMovimientoAuxiliarCfdi(mov)) {
                    delete seleccionados[mov.id];
                    eliminados++;
                    return false;
                }
                return true;
            });
            ids.forEach(function (id) {
                var cfdi = cfdiPorId(id);
                if (!cfdi) { return; }
                cfdi.movimiento_relacionado = "";
                cfdi.estatus_relacion = "pendiente";
                cfdi.tratamiento = "conciliar_banco";
                cfdi.origen = "cfdi";
            });
            if (!eliminados) {
                mostrarError("No encontre auxiliares creados desde los CFDI seleccionados.");
            }
            render();
        });
    }
    function filtros() {
        return {
            periodo: periodoCierreActual(),
            descripcion: normalizar($("clasificacion_descripcion") ? $("clasificacion_descripcion").value : ""),
            tipo: $("clasificacion_tipo") ? $("clasificacion_tipo").value : "",
            actividad: $("clasificacion_actividad") ? $("clasificacion_actividad").value : "",
            categoria: $("clasificacion_categoria") ? $("clasificacion_categoria").value : "",
            cfdi: $("clasificacion_cfdi") ? $("clasificacion_cfdi").value : "",
            cuenta: normalizar($("clasificacion_cuenta") ? $("clasificacion_cuenta").value : ""),
            clasificar: $("clasificacion_origen") ? $("clasificacion_origen").value : "",
            estado: estadoSeleccionadoId,
            pago: ""
        };
    }
    function visibles() {
        var f = filtros();
        return movimientos.filter(function (m) {
            var descripcion = normalizar(m.concepto || "");
            return (!f.descripcion || descripcion.indexOf(f.descripcion) >= 0) &&
                (!f.periodo || (m.periodo || periodoEstadoPorId(m.estado_cuenta_id)) === f.periodo) &&
                (!f.clasificar || coincideFiltroClasificacion(m, f.clasificar)) &&
                (!f.tipo || m.tipo_movimiento === f.tipo) &&
                (!f.actividad || m.actividad === f.actividad) &&
                (!f.categoria || m.categoria === f.categoria) &&
                (!f.cfdi || m.cfdi === f.cfdi) &&
                (!f.cuenta || normalizar(m.cuenta || "").indexOf(f.cuenta) >= 0) &&
                (!f.estado || m.estado_cuenta_id === f.estado);
        });
    }
    function coincideFiltroClasificacion(mov, filtro) {
        if (!filtro) { return true; }
        if (filtro.indexOf("estado:") === 0) {
            var estadoId = filtro.replace(/^estado:/, "");
            var edo = estadosCuenta.find(function (item) { return item.id === estadoId; });
            var mismoEstado = mov.estado_cuenta_id === estadoId;
            var mismaCuentaAuxiliar = edo && edo.origen === "manual" && !mov.estado_cuenta_id &&
                (mov.periodo || periodoEstadoPorId(mov.estado_cuenta_id)) === edo.periodo &&
                (mov.cuenta || "") === (edo.cuenta || "");
            return mismoEstado || mismaCuentaAuxiliar;
        }
        if (filtro.indexOf("aux:") === 0) {
            return !mov.estado_cuenta_id && (mov.cuenta || "Cuenta sin nombre") === cuentaDesdeFiltroAuxiliar(filtro);
        }
        return true;
    }
    function movimientosPeriodoActual() {
        var periodo = periodoCierreActual();
        return movimientos.filter(function (m) {
            return (m.periodo || periodoEstadoPorId(m.estado_cuenta_id)) === periodo;
        });
    }
    function cfdiTieneMovimiento(cfdi) {
        if (!cfdi) { return false; }
        var movimientoId = cfdi.movimiento_relacionado || "";
        return movimientos.some(function (mov) {
            return (cfdi.uuid && mov.cfdi_uuid === cfdi.uuid) || (movimientoId && mov.id === movimientoId);
        });
    }
    function movimientosCfdiNoMaterializados() {
        return cfdisPeriodoActual().filter(function (cfdi) {
            var cuenta = cfdi.cuenta_pago || "";
            return cuenta && cuenta !== "Por relacionar" && !cfdiTieneMovimiento(cfdi);
        }).map(function (cfdi) {
            var folio = [cfdi.serie, cfdi.folio].filter(Boolean).join("-") || cfdi.uuid || cfdi.archivo || "";
            return normalizarMovimientoLegacy({
                id: "cfdi-pend-" + (cfdi.id || cfdi.uuid || folio),
                fecha: cfdi.fecha || "",
                periodo: cfdi.periodo || periodoCierreActual(),
                cuenta: cfdi.cuenta_pago || "Cuenta sin nombre",
                concepto: cfdi.emisor || cfdi.rfc_emisor || cfdi.archivo || "CFDI sin emisor",
                referencia: folio,
                cargo: Number(cfdi.total || 0),
                abono: 0,
                monto: montoFirmado("egreso", cfdi.total),
                tipo_movimiento: "egreso",
                actividad: cfdi.actividad || "negocio",
                forma_pago: cfdi.forma_pago || "",
                categoria: cfdi.categoria || "gasto_operativo",
                cfdi: "pendiente_movimiento",
                cfdi_uuid: cfdi.uuid || "",
                cfdi_sugerencia: "",
                origen: "cfdi_sin_movimiento",
                archivo_estado_cuenta: "CFDI asignado sin movimiento",
                estado_cuenta_id: "",
                notas: "CFDI asignado a cuenta; falta crear movimiento auxiliar o ligarlo."
            });
        });
    }
    function movimientosParaConciliacion() {
        return movimientosPeriodoActual().concat(movimientosCfdiNoMaterializados());
    }
    function movimientosConciliacionCuenta(cuentaSeleccionada) {
        var periodo = periodoCierreActual();
        return movimientosParaConciliacion().filter(function (m) {
            return (m.cuenta || "Cuenta sin nombre") === cuentaSeleccionada &&
                (m.periodo || periodoEstadoPorId(m.estado_cuenta_id)) === periodo;
        });
    }
    function movimientosVentasPeriodo() {
        return movimientosPeriodoActual().filter(function (m) {
            return m.tipo_movimiento === "ingreso" && m.actividad === "negocio";
        });
    }
    function movimientosTraspasosPeriodo() {
        return movimientosPeriodoActual().filter(function (m) {
            return m.actividad === "transpaso";
        });
    }
    function conceptoPareceTraspaso(mov) {
        return /traspaso|transpaso|transferencia entre|cuenta propia|spei recibido propio|pago tarjeta|tarjeta credito|tdc/.test(normalizar([mov.concepto, mov.referencia, mov.notas].join(" ")));
    }
    function candidatoTraspaso(mov) {
        return mov.actividad === "transpaso" || conceptoPareceTraspaso(mov);
    }
    function diasEntre(fechaA, fechaB) {
        if (!fechaA || !fechaB) { return 999; }
        return Math.abs((new Date(fechaA + "T00:00:00") - new Date(fechaB + "T00:00:00")) / 86400000);
    }
    function agregarNotaMovimiento(mov, nota) {
        if (!nota) { return; }
        if (!mov.notas) {
            mov.notas = nota;
            return;
        }
        if (mov.notas.indexOf(nota) < 0) {
            mov.notas += " " + nota;
        }
    }
    function detectarTraspasos() {
        var candidatos = movimientosPeriodoActual().filter(function (m) {
            return !m.traspaso_relacionado && candidatoTraspaso(m) && Math.abs(Number(m.monto || 0)) > 0;
        });
        var salidas = candidatos.filter(function (m) { return m.tipo_movimiento === "egreso"; });
        var entradas = candidatos.filter(function (m) { return m.tipo_movimiento === "ingreso"; });
        var usados = {};
        var creados = 0;
        salidas.forEach(function (salida) {
            var mejor = entradas.filter(function (entrada) {
                return !usados[entrada.id] &&
                    entrada.id !== salida.id &&
                    (entrada.cuenta || "") !== (salida.cuenta || "") &&
                    Math.abs(Math.abs(Number(entrada.monto || 0)) - Math.abs(Number(salida.monto || 0))) <= 0.99 &&
                    diasEntre(entrada.fecha, salida.fecha) <= 5;
            }).sort(function (a, b) {
                return diasEntre(a.fecha, salida.fecha) - diasEntre(b.fecha, salida.fecha);
            })[0];
            if (!mejor) { return; }
            var grupo = "TR-" + periodoCierreActual().replace("-", "") + "-" + String(creados + 1).padStart(3, "0");
            salida.traspaso_relacionado = mejor.id;
            salida.traspaso_grupo = grupo;
            salida.actividad = "transpaso";
            salida.cfdi = "no_aplica";
            salida.categoria = "no_aplica";
            mejor.traspaso_relacionado = salida.id;
            mejor.traspaso_grupo = grupo;
            mejor.actividad = "transpaso";
            mejor.cfdi = "no_aplica";
            mejor.categoria = "no_aplica";
            agregarNotaMovimiento(salida, "Traspaso sugerido contra " + (mejor.cuenta || "cuenta destino") + ".");
            agregarNotaMovimiento(mejor, "Traspaso sugerido contra " + (salida.cuenta || "cuenta origen") + ".");
            usados[mejor.id] = true;
            creados++;
        });
        if (!creados) {
            mostrarError("No encontre traspasos nuevos con mismo monto, cuentas distintas y fechas cercanas.");
            return;
        }
        if (window.Swal) {
            Swal.fire({text: creados + " traspasos sugeridos.", icon: "success", timer: 1400, showConfirmButton: false});
        }
        render();
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
        var rows = movimientosPeriodoActual();
        var totalIngresos = rows.reduce(function (s, m) { return s + (m.tipo_movimiento === "ingreso" ? Number(m.monto || 0) : 0); }, 0);
        var totalEgresos = rows.reduce(function (s, m) { return s + (m.tipo_movimiento === "egreso" ? Math.abs(Number(m.monto || 0)) : 0); }, 0);
        var traspasos = rows.filter(function (m) { return m.actividad === "transpaso"; }).length;
        var pendientes = rows.filter(function (m) { return m.cfdi === "pendiente"; }).length;
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
            var cfdiSugerido = m.cfdi_sugerencia ? cfdiPorId(m.cfdi_sugerencia) : null;
            var coincidenciaSugerida = cfdiSugerido ? coincidenciaCfdi(m, cfdiSugerido) : null;
            var textoSugerencia = coincidenciaSugerida && coincidenciaSugerida.exacta ? "Monto compatible" : "Sugerido";
            var sugerencia = m.cfdi_sugerencia && m.cfdi !== "ligado" ? "<div class=\"text-primary fs-9 mt-1\">" + textoSugerencia + ": " + escapeHtml(m.cfdi_sugerencia) + "</div>" : "";
            var origen = m.origen || (m.estado_cuenta_id ? "estado_cuenta" : "manual");
            var traspaso = m.traspaso_grupo ? "<div class=\"text-info fs-9 mt-1\">Traspaso " + escapeHtml(m.traspaso_grupo) + "</div>" : "";
            return "<tr data-id=\"" + escapeHtml(m.id) + "\">" +
                "<td><input class=\"form-check-input\" type=\"checkbox\" data-seleccionar-mov=\"" + escapeHtml(m.id) + "\"" + (seleccionados[m.id] ? " checked" : "") + "></td>" +
                "<td class=\"text-nowrap\">" + escapeHtml(m.fecha || "-") + "</td>" +
                "<td><div class=\"fw-semibold\">" + escapeHtml(m.concepto) + "</div><div class=\"text-muted fs-8\">" + escapeHtml(m.referencia || "") + (origen === "cfdi_auxiliar" ? " | CFDI auxiliar" : "") + "</div>" + traspaso + "</td>" +
                "<td><select class=\"form-select form-select-sm form-select-solid min-w-125px\" data-campo=\"tipo_movimiento\">" + options(tiposMovimiento, m.tipo_movimiento) + "</select></td>" +
                "<td><select class=\"form-select form-select-sm form-select-solid min-w-125px\" data-campo=\"actividad\">" + options(actividades, m.actividad) + "</select></td>" +
                "<td><select class=\"form-select form-select-sm form-select-solid min-w-150px\" data-campo=\"categoria\">" + options(categorias, m.categoria) + "</select></td>" +
                "<td><input class=\"form-control form-control-sm form-control-solid min-w-125px\" data-campo=\"cuenta\" value=\"" + escapeHtml(m.cuenta || "") + "\"></td>" +
                "<td class=\"text-end fw-bold\">" + money(m.monto || 0) + "</td>" +
                "<td><input class=\"form-control form-control-sm form-control-solid min-w-150px\" data-campo=\"referencia\" value=\"" + escapeHtml(m.referencia || "") + "\"></td>" +
                "<td><select class=\"form-select form-select-sm form-select-solid min-w-125px\" data-campo=\"cfdi\">" + options(["ligado", "pendiente", "no_aplica"], m.cfdi) + "</select>" +
                "<div class=\"text-muted fs-9 mt-1\">" + escapeHtml(m.cfdi_uuid || "") + "</div>" + sugerencia + "</td>" +
                "<td class=\"text-end\"><div class=\"d-flex justify-content-end gap-2\">" +
                "<button class=\"btn btn-sm btn-icon btn-light-primary\" title=\"Revisar relacion CFDI\" type=\"button\" data-ligar=\"" + escapeHtml(m.id) + "\"><i class=\"bi bi-link\"></i></button>" +
                "<button class=\"btn btn-sm btn-icon btn-light-danger\" title=\"Eliminar movimiento\" type=\"button\" data-eliminar-mov=\"" + escapeHtml(m.id) + "\"><i class=\"bi bi-trash3\"></i></button>" +
                "</div></td>" +
                "</tr>";
        }).join("") || "<tr><td colspan=\"11\" class=\"text-center text-muted py-8\">Carga movimientos bancarios para iniciar el cierre.</td></tr>";
    }
    function renderCfdis() {
        var rows = cfdisFiltrados();
        var seleccion = idsCfdiSeleccionadosValidos();
        var visiblesSeleccionados = rows.filter(function (c) { return cfdisSeleccionados[c.id]; }).length;
        if ($("contabilidad_cfdi_total")) {
            $("contabilidad_cfdi_total").textContent = rows.length + " CFDI";
        }
        if ($("contabilidad_cfdi_masivo_total")) {
            $("contabilidad_cfdi_masivo_total").textContent = seleccion.length + " seleccionados";
        }
        if ($("cfdi_select_todos")) {
            $("cfdi_select_todos").checked = rows.length > 0 && visiblesSeleccionados === rows.length;
            $("cfdi_select_todos").indeterminate = visiblesSeleccionados > 0 && visiblesSeleccionados < rows.length;
        }
        $("contabilidad_cfdis").innerHTML = rows.map(function (c) {
            normalizarCfdiLegacy(c);
            var ligado = movimientos.find(function (m) { return m.cfdi_uuid === c.uuid || m.id === c.movimiento_relacionado; });
            var sugerido = movimientos.map(function (mov) { return {mov: mov, score: scoreCfdi(mov, c)}; })
                .filter(function (x) { return x.score >= 40; })
                .sort(function (a, b) { return b.score - a.score; })[0];
            var coincidenciaSugerida = sugerido ? coincidenciaCfdi(sugerido.mov, c) : null;
            var relacion = ligado ? "Ligado" : (sugerido ? (coincidenciaSugerida.exacta ? "Monto compatible" : "Sugerido") : "Sin banco");
            var pagoComplemento = c.pago_complemento ? "<span class=\"badge badge-light-info ms-2\">Complemento pago</span>" : "";
            var detallePago = c.pago_complemento ? "<div class=\"text-primary fs-9 mt-1\">Pago " + escapeHtml(c.pago_fecha || c.fecha || "-") + " | " + money(c.pago_monto || c.total || 0) + (c.num_operacion ? " | Op. " + escapeHtml(c.num_operacion) : "") + "</div>" : "";
            var doctos = c.doctos_relacionados ? "<div class=\"text-muted fs-9 mt-1\">Relacionado: " + escapeHtml(c.doctos_relacionados) + "</div>" : "";
            return "<tr data-cfdi-id=\"" + escapeHtml(c.id) + "\">" +
                "<td><input class=\"form-check-input\" type=\"checkbox\" data-seleccionar-cfdi=\"" + escapeHtml(c.id) + "\"" + (cfdisSeleccionados[c.id] ? " checked" : "") + "></td>" +
                "<td class=\"text-nowrap\">" + escapeHtml(c.fecha || "-") + "</td>" +
                "<td><div class=\"fw-semibold\">" + escapeHtml(c.emisor || c.rfc_emisor || c.archivo) + pagoComplemento + "</div>" +
                "<div class=\"text-muted fs-9\">" + escapeHtml(c.rfc_emisor || "") + " | " + escapeHtml(c.uuid || "Sin UUID") + "</div>" +
                "<div class=\"text-muted fs-9\">" + escapeHtml(c.receptor || c.rfc_receptor || "") + (c.uso_cfdi ? " | Uso " + escapeHtml(c.uso_cfdi) : "") + "</div>" +
                "<div class=\"fs-9 mt-1\">" + escapeHtml(c.conceptos_resumen || c.concepto_principal || "Sin conceptos") + "</div>" +
                detallePago + doctos +
                "<div class=\"text-muted fs-9 mt-1\">IVA " + money(c.iva_trasladado || 0) + " | Ret. IVA " + money(c.iva_retenido || 0) + " | Ret. ISR " + money(c.isr_retenido || 0) + "</div></td>" +
                "<td><select class=\"form-select form-select-sm form-select-solid min-w-150px\" data-cfdi-campo=\"categoria\">" + options(categorias, c.categoria) + "</select></td>" +
                "<td><select class=\"form-select form-select-sm form-select-solid min-w-125px\" data-cfdi-campo=\"actividad\">" + options(actividades, c.actividad) + "</select></td>" +
                "<td><select class=\"form-select form-select-sm form-select-solid min-w-150px\" data-cfdi-campo=\"forma_pago\">" + options(formasPago, c.forma_pago) + "</select>" +
                "<div class=\"text-muted fs-9 mt-1\">" + escapeHtml(c.metodo_pago || "-") + " / " + escapeHtml(c.forma_pago_sat || "-") + "</div></td>" +
                "<td><select class=\"form-select form-select-sm form-select-solid min-w-175px\" data-cfdi-campo=\"cuenta_pago\">" + optionsCuentas(c.cuenta_pago || cuentaCfdiDefault()) + "</select></td>" +
                "<td class=\"text-end\"><div class=\"fw-bold\">" + money(c.total) + "</div><div class=\"text-muted fs-9\">" + escapeHtml(c.moneda || "MXN") + " | Sub " + money(c.subtotal || 0) + "</div></td>" +
                "<td><span class=\"badge " + (ligado ? "badge-light-success" : (sugerido ? "badge-light-primary" : "badge-light-warning")) + "\">" + relacion + "</span>" +
                (sugerido && !ligado ? "<div class=\"text-muted fs-9 mt-1\">" + escapeHtml(sugerido.mov.fecha || "") + " | " + money(Math.abs(sugerido.mov.monto)) + "</div>" : "") + "</td>" +
                "<td class=\"text-end\"><div class=\"d-flex justify-content-end gap-2\">" +
                "<button class=\"btn btn-sm btn-icon btn-light-primary\" title=\"Revisar relacion\" type=\"button\" data-ligar-cfdi=\"" + escapeHtml(c.id) + "\"><i class=\"bi bi-link\"></i></button>" +
                "<button class=\"btn btn-sm btn-icon btn-light-success\" title=\"Crear movimiento auxiliar\" type=\"button\" data-crear-mov-cfdi=\"" + escapeHtml(c.id) + "\"><i class=\"bi bi-plus-circle\"></i></button>" +
                "<button class=\"btn btn-sm btn-icon btn-light\" title=\"Deshacer relacion\" type=\"button\" data-desligar-cfdi=\"" + escapeHtml(c.id) + "\"><i class=\"bi bi-link-45deg\"></i></button>" +
                "<button class=\"btn btn-sm btn-icon btn-light-danger\" title=\"Eliminar CFDI\" type=\"button\" data-eliminar-cfdi=\"" + escapeHtml(c.id) + "\"><i class=\"bi bi-trash3\"></i></button>" +
                "</div></td></tr>";
        }).join("") || "<tr><td colspan=\"10\" class=\"text-center text-muted py-8\">Carga XML para registrar compras, gastos y relacionarlos con movimientos.</td></tr>";
    }
    function renderPendientes() {
        var periodo = periodoCierreActual();
        var pendientes = movimientos.filter(function (m) {
            return m.cfdi === "pendiente" && (m.periodo || periodoEstadoPorId(m.estado_cuenta_id)) === periodo;
        });
        $("contabilidad_pendientes").innerHTML = pendientes.map(function (m) {
            return "<div class=\"alert alert-warning py-3 mb-3\">" +
                "<div class=\"d-flex align-items-start justify-content-between gap-3\">" +
                "<div><div class=\"fw-bold\">" + escapeHtml(m.concepto) + "</div>" +
                "<div class=\"fs-8\">" + escapeHtml(m.fecha || "-") + " | " + money(m.cargo || m.abono) + " | " + label(m.actividad) + " | " + label(m.categoria) + "</div></div>" +
                "<button class=\"btn btn-sm btn-light-warning\" type=\"button\" data-revisar-pendiente=\"" + escapeHtml(m.id) + "\"><i class=\"bi bi-search\"></i> Revisar</button>" +
                "</div></div>";
        }).join("") || "<div class=\"alert alert-success py-3 mb-0\">Sin pendientes visibles de CFDI.</div>";
    }
    function movimientosEstadoVista(edo) {
        return movimientos.filter(function (m) {
            var mismoPeriodo = (m.periodo || periodoEstadoPorId(m.estado_cuenta_id)) === edo.periodo;
            var mismoEstado = m.estado_cuenta_id === edo.id;
            var mismaCuentaAuxiliar = !m.estado_cuenta_id && edo.origen === "manual" && (m.cuenta || "") === (edo.cuenta || "");
            return mismoPeriodo && (mismoEstado || mismaCuentaAuxiliar);
        });
    }
    function cfdisPendientesCuenta(edo, rows) {
        var ligados = {};
        rows.forEach(function (m) {
            if (m.cfdi_uuid) { ligados[m.cfdi_uuid] = true; }
        });
        return cfdis.filter(function (cfdi) {
            normalizarCfdiLegacy(cfdi);
            return cfdi.periodo === edo.periodo &&
                (cfdi.cuenta_pago || "") === (edo.cuenta || "") &&
                !ligados[cfdi.uuid] &&
                !cfdiTieneMovimiento(cfdi);
        }).length;
    }
    function renderEstadosCuenta() {
        var contenedor = $("contabilidad_estados");
        if (!contenedor) { return; }
        contenedor.innerHTML = estadosCuenta.map(function (edo) {
            var rows = movimientosEstadoVista(edo);
            var ligados = rows.filter(function (m) { return m.cfdi === "ligado" || m.cfdi_uuid; }).length;
            var pendientes = rows.filter(function (m) { return m.cfdi === "pendiente"; }).length + cfdisPendientesCuenta(edo, rows);
            var activo = estadoSeleccionadoId === edo.id;
            var esCuentaSinArchivo = edo.origen === "manual" || (!Number(edo.filas || 0) && !Number(edo.columnas || 0));
            var badgesOrigen = esCuentaSinArchivo ?
                badge("Cuenta sin archivo", "info") :
                badge((edo.filas || 0) + " filas leidas", "info") + badge((edo.columnas || 0) + " columnas", "warning");
            return "<div class=\"d-flex flex-wrap align-items-center justify-content-between gap-3 border-bottom py-3" + (activo ? " bg-light-primary px-3 rounded" : "") + "\">" +
                "<div><div class=\"fw-bold\">" + escapeHtml(edo.cuenta) + "</div>" +
                "<div class=\"text-muted fs-8\">" + escapeHtml(edo.archivo) + (edo.hoja ? " | Hoja: " + escapeHtml(edo.hoja) : "") + " | " + escapeHtml(edo.periodo) + " | " + escapeHtml(edo.fecha_carga) + "</div></div>" +
                "<div class=\"d-flex flex-wrap gap-2\">" +
                badge((rows.length || edo.movimientos || 0) + " movimientos", "primary") +
                badgesOrigen +
                badge(ligados + " CFDI ligados", ligados ? "success" : "secondary") +
                badge(pendientes + " CFDI pendientes", pendientes ? "warning" : "secondary") +
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
        return movimientosParaConciliacion().reduce(function (map, mov) {
            var cuenta = mov.cuenta || "Cuenta sin nombre";
            if (!map[cuenta]) {
                map[cuenta] = {cuenta: cuenta, movimientos: [], egreso: 0, ingreso: 0, transpaso: 0, pendientes: 0, cfdi_sin_movimiento: 0};
            }
            map[cuenta].movimientos.push(mov);
            if (mov.tipo_movimiento === "ingreso") { map[cuenta].ingreso += Number(mov.monto || 0); }
            if (mov.tipo_movimiento === "egreso") { map[cuenta].egreso += Math.abs(Number(mov.monto || 0)); }
            if (mov.actividad === "transpaso") { map[cuenta].transpaso += Number(mov.monto || 0); }
            if (mov.cfdi === "pendiente") { map[cuenta].pendientes++; }
            if (mov.origen === "cfdi_sin_movimiento") { map[cuenta].cfdi_sin_movimiento++; }
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
                var total = lista.reduce(function (s, m) { return s + (tipo === "egreso" ? Math.abs(Number(m.monto || 0)) : Number(m.monto || 0)); }, 0);
                return "<div class=\"col-md-4\"><div class=\"border rounded p-4 h-100\">" +
                    "<div class=\"text-muted fs-8 text-uppercase\">" + label(tipo) + "</div>" +
                    "<div class=\"fw-bold fs-5\">" + money(total) + "</div>" +
                    "<div class=\"text-muted fs-8\">" + lista.length + " movimientos</div></div></div>";
            }).join("");
            return "<div class=\"border-bottom py-5\">" +
                "<div class=\"d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4\">" +
                "<div><div class=\"fw-bold fs-5\">" + escapeHtml(grupo.cuenta) + "</div>" +
                "<div class=\"text-muted fs-8\">" + grupo.movimientos.length + " movimientos | " + grupo.pendientes + " CFDI pendientes" + (grupo.cfdi_sin_movimiento ? " | " + grupo.cfdi_sin_movimiento + " CFDI sin movimiento" : "") + "</div></div>" +
                "<div class=\"d-flex gap-2\">" +
                "<button class=\"btn btn-sm btn-light-primary\" type=\"button\" data-ver-conciliacion=\"" + escapeHtml(grupo.cuenta) + "\"><i class=\"bi bi-eye\"></i> Ver</button>" +
                "<button class=\"btn btn-sm btn-light\" type=\"button\" data-export-cuenta=\"" + escapeHtml(grupo.cuenta) + "\"><i class=\"bi bi-download\"></i> CSV cuenta</button>" +
                "</div>" +
                "</div><div class=\"row g-3\">" + porTipo + "</div></div>";
        }).join("") || "<div class=\"text-muted py-4\">Cuando importes estados de cuenta, aqui veras los movimientos separados por cuenta y por tipo.</div>";
    }
    function renderResumenConciliacion(rows) {
        var ingreso = rows.reduce(function (s, m) { return s + (m.tipo_movimiento === "ingreso" ? Number(m.monto || 0) : 0); }, 0);
        var egreso = rows.reduce(function (s, m) { return s + (m.tipo_movimiento === "egreso" ? Math.abs(Number(m.monto || 0)) : 0); }, 0);
        var cfdiSinMovimiento = rows.filter(function (m) { return m.origen === "cfdi_sin_movimiento"; }).length;
        var pendientes = rows.filter(function (m) { return m.cfdi === "pendiente" || m.cfdi === "pendiente_movimiento"; }).length;
        var traspasosLigados = rows.filter(function (m) { return m.actividad === "transpaso" && m.traspaso_relacionado; }).length;
        var resumen = [
            ["Ingresos", money(ingreso), "success"],
            ["Egresos", money(egreso), "danger"],
            ["Pendientes CFDI", pendientes, "warning"],
            ["CFDI sin movimiento", cfdiSinMovimiento, "info"],
            ["Traspasos ligados", traspasosLigados, "primary"]
        ];
        return resumen.map(function (item) {
            return "<div class=\"col-md\"><div class=\"border rounded p-4 h-100\">" +
                "<div class=\"text-muted fs-8 text-uppercase\">" + item[0] + "</div>" +
                "<div class=\"fw-bold fs-5 text-" + item[2] + "\">" + item[1] + "</div>" +
                "</div></div>";
        }).join("");
    }
    function abrirDetalleConciliacion(titulo, rows, nombreArchivo) {
        cuentaConciliacionActual = "";
        conciliacionModalRows = rows || [];
        conciliacionModalNombre = nombreArchivo || titulo || "conciliacion";
        $("contabilidad_conciliacion_modal_titulo").textContent = titulo;
        $("contabilidad_conciliacion_modal_subtitulo").textContent = periodoCierreActual() + " | " + conciliacionModalRows.length + " movimientos para revisar";
        $("contabilidad_conciliacion_modal_resumen").innerHTML = renderResumenConciliacion(conciliacionModalRows);
        $("contabilidad_conciliacion_modal_movimientos").innerHTML = conciliacionModalRows.map(function (m) {
            var relacionado = m.traspaso_grupo ? "<div class=\"text-info fs-9 mt-1\">Traspaso " + escapeHtml(m.traspaso_grupo) + "</div>" : "";
            return filaDetalleConciliacion(m, relacionado);
        }).join("") || "<tr><td colspan=\"8\" class=\"text-center text-muted py-8\">No hay movimientos para revisar.</td></tr>";
        bootstrap.Modal.getOrCreateInstance($("contabilidad_conciliacion_modal")).show();
    }
    function filaDetalleConciliacion(m, extra) {
        var cfdi = cfdiLigadoMovimiento(m);
        var cfdiTexto = m.cfdi_uuid || m.cfdi || "";
        var fiscal = cfdi.emisor ? "<div class=\"text-muted fs-9\">" + escapeHtml(cfdi.emisor) + "</div>" : "";
        var aviso = m.origen === "cfdi_sin_movimiento" ? "<div class=\"badge badge-light-info mt-1\">CFDI sin movimiento</div>" : "";
        return "<tr>" +
            "<td class=\"text-nowrap\">" + escapeHtml(m.fecha || "-") + "</td>" +
            "<td><div class=\"fw-semibold\">" + escapeHtml(m.concepto || "") + "</div>" +
            "<div class=\"text-muted fs-9\">" + escapeHtml(m.referencia || "") + (m.archivo_estado_cuenta ? " | " + escapeHtml(m.archivo_estado_cuenta) : "") + "</div>" + aviso + (extra || "") + "</td>" +
            "<td>" + badge(label(m.tipo_movimiento), m.tipo_movimiento === "ingreso" ? "success" : "danger") + "</td>" +
            "<td>" + escapeHtml(label(m.actividad)) + "</td>" +
            "<td>" + escapeHtml(label(m.categoria)) + "</td>" +
            "<td>" + escapeHtml(label(m.forma_pago)) + "</td>" +
            "<td><div class=\"fs-8\">" + escapeHtml(cfdiTexto) + "</div>" + fiscal + "</td>" +
            "<td class=\"text-end fw-bold\">" + money(m.monto || 0) + "</td>" +
            "</tr>";
    }
    function verConciliacionCuenta(cuentaSeleccionada) {
        var rows = movimientosConciliacionCuenta(cuentaSeleccionada);
        cuentaConciliacionActual = cuentaSeleccionada;
        conciliacionModalRows = rows;
        conciliacionModalNombre = cuentaSeleccionada;
        $("contabilidad_conciliacion_modal_titulo").textContent = cuentaSeleccionada;
        $("contabilidad_conciliacion_modal_subtitulo").textContent = periodoCierreActual() + " | " + rows.length + " movimientos para revisar";
        $("contabilidad_conciliacion_modal_resumen").innerHTML = renderResumenConciliacion(rows);
        $("contabilidad_conciliacion_modal_movimientos").innerHTML = rows.map(function (m) {
            var relacionado = m.traspaso_grupo ? "<div class=\"text-info fs-9 mt-1\">Traspaso " + escapeHtml(m.traspaso_grupo) + "</div>" : "";
            return filaDetalleConciliacion(m, relacionado);
        }).join("") || "<tr><td colspan=\"8\" class=\"text-center text-muted py-8\">No hay movimientos para esta cuenta.</td></tr>";
        bootstrap.Modal.getOrCreateInstance($("contabilidad_conciliacion_modal")).show();
    }
    function verVentasConciliacion() {
        abrirDetalleConciliacion("Ventas del mes", movimientosVentasPeriodo(), "ventas");
    }
    function verTraspasosConciliacion() {
        abrirDetalleConciliacion("Traspasos entre cuentas", movimientosTraspasosPeriodo(), "traspasos");
    }
    function render() {
        renderEstadosCuenta();
        renderGuardados();
        renderKpis();
        renderClasificadorOrigen();
        renderDescripcionesFiltro();
        renderDescripcionesCfdiFiltro();
        renderMovimientos();
        renderCuentaCfdiDefault();
        renderCfdiMasivoControls();
        renderCfdis();
        renderPendientes();
        renderConciliacion();
        if ($("contabilidad_reporte_texto")) { generarReporteContador(); }
        programarAutosave();
    }
    function cfdiLigadoMovimiento(mov) {
        if (!mov || !mov.cfdi_uuid) { return {}; }
        return cfdis.find(function (cfdi) { return cfdi.uuid === mov.cfdi_uuid; }) || {};
    }
    function headersReporteMovimientos() {
        return ["periodo", "fecha", "origen", "descripcion", "movimiento", "actividad", "categoria", "cuenta", "forma_pago", "monto", "folio_factura", "cfdi", "cfdi_uuid", "traspaso_grupo", "traspaso_relacionado", "rfc_emisor", "emisor", "uso_cfdi", "metodo_pago_sat", "forma_pago_sat", "subtotal_cfdi", "iva", "ret_iva", "ret_isr", "conceptos_cfdi", "es_complemento_pago", "pago_fecha", "pago_monto", "pago_operacion", "pago_doctos_relacionados", "archivo_estado_cuenta", "notas"];
    }
    function filaReporteMovimiento(m, periodoFallback, cuentaFallback) {
        var cfdi = cfdiLigadoMovimiento(m);
        return [
            m.periodo || periodoFallback || periodoEstadoPorId(m.estado_cuenta_id),
            m.fecha,
            m.origen || (m.estado_cuenta_id ? "estado_cuenta" : "manual"),
            m.concepto,
            m.tipo_movimiento,
            m.actividad,
            m.categoria,
            m.cuenta || cuentaFallback || "",
            m.forma_pago,
            m.monto,
            m.referencia,
            m.cfdi,
            m.cfdi_uuid,
            m.traspaso_grupo || "",
            m.traspaso_relacionado || "",
            cfdi.rfc_emisor || "",
            cfdi.emisor || "",
            cfdi.uso_cfdi || "",
            cfdi.metodo_pago || "",
            cfdi.forma_pago_sat || "",
            cfdi.subtotal || "",
            cfdi.iva_trasladado || "",
            cfdi.iva_retenido || "",
            cfdi.isr_retenido || "",
            cfdi.conceptos_resumen || "",
            cfdi.pago_complemento ? "si" : "no",
            cfdi.pago_fecha || "",
            cfdi.pago_monto || "",
            cfdi.num_operacion || "",
            cfdi.doctos_relacionados || "",
            m.archivo_estado_cuenta,
            m.notas
        ];
    }
    function exportarCsv() {
        var periodo = periodoCierreActual();
        var cuenta = $("contabilidad_cuenta").value || "";
        var headers = headersReporteMovimientos();
        var lines = [headers.join(",")].concat(movimientos.filter(function (m) {
            return (m.periodo || periodoEstadoPorId(m.estado_cuenta_id)) === periodo;
        }).map(function (m) {
            return filaReporteMovimiento(m, periodo, cuenta).map(csvEscape).join(",");
        }));
        descargar("clasificacion_movimientos_" + periodo + ".csv", lines.join("\n"), "text/csv;charset=utf-8");
    }
    function exportarCsvCuenta(cuentaSeleccionada) {
        var periodo = periodoCierreActual();
        var rows = movimientosConciliacionCuenta(cuentaSeleccionada);
        exportarRowsCsv("clasificacion_" + slug(cuentaSeleccionada) + "_" + periodo + ".csv", rows, cuentaSeleccionada);
    }
    function exportarRowsCsv(nombre, rows, cuentaFallback) {
        var periodo = periodoCierreActual();
        var headers = headersReporteMovimientos();
        var lines = [headers.join(",")].concat((rows || []).map(function (m) {
            return filaReporteMovimiento(m, periodo, cuentaFallback || "").map(csvEscape).join(",");
        }));
        descargar(nombre, lines.join("\n"), "text/csv;charset=utf-8");
    }
    function exportarCsvCuentaSimple(cuentaSeleccionada) {
        var periodo = periodoCierreActual();
        var rows = movimientosConciliacionCuenta(cuentaSeleccionada);
        exportarRowsSimple("estado_cuenta_simple_" + slug(cuentaSeleccionada) + "_" + periodo + ".csv", rows, cuentaSeleccionada);
    }
    function exportarRowsSimple(nombre, rows, cuentaFallback) {
        var headers = ["fecha", "descripcion", "movimiento", "actividad", "categoria", "cuenta", "forma_pago", "monto", "cfdi", "cfdi_uuid", "traspaso_grupo", "origen", "notas"];
        var lines = [headers.join(",")].concat((rows || []).map(function (m) {
            return [
                m.fecha,
                m.concepto,
                m.tipo_movimiento,
                m.actividad,
                m.categoria,
                m.cuenta || cuentaFallback || "",
                m.forma_pago,
                m.monto,
                m.cfdi,
                m.cfdi_uuid,
                m.traspaso_grupo || "",
                m.origen || (m.estado_cuenta_id ? "estado_cuenta" : "manual"),
                m.notas
            ].map(csvEscape).join(",");
        }));
        descargar(nombre, lines.join("\n"), "text/csv;charset=utf-8");
    }
    function exportarCsvEstado(estadoId) {
        var estado = estadosCuenta.find(function (edo) { return edo.id === estadoId; });
        var periodo = (estado && estado.periodo) || periodoCierreActual();
        var headers = headersReporteMovimientos();
        var rows = estado ? movimientosEstadoVista(estado) : movimientos.filter(function (m) { return m.estado_cuenta_id === estadoId; });
        var lines = [headers.join(",")].concat(rows.map(function (m) {
            return filaReporteMovimiento(m, periodo, estado ? estado.cuenta : "").map(csvEscape).join(",");
        }));
        var base = estado ? estado.archivo : "estado_cuenta";
        descargar("clasificacion_" + slug(base.replace(/\.[^.]+$/, "")) + "_" + periodo + ".csv", lines.join("\n"), "text/csv;charset=utf-8");
    }
    function activarTab(id) {
        var link = document.querySelector("a[href=\"#" + id + "\"]");
        if (link && window.bootstrap && bootstrap.Tab) {
            bootstrap.Tab.getOrCreateInstance(link).show();
        }
    }
    function limpiarFiltrosClasificacion() {
        ["clasificacion_descripcion", "clasificacion_tipo", "clasificacion_actividad", "clasificacion_categoria", "clasificacion_cfdi", "clasificacion_cuenta"].forEach(function (id) {
            if ($(id)) { $(id).value = ""; }
        });
    }
    function revisarPendienteContador(movimientoId) {
        var mov = movimientos.find(function (m) { return m.id === movimientoId; });
        if (!mov) {
            mostrarError("No encontre ese pendiente.");
            return;
        }
        fijarPeriodo(mov.periodo || periodoEstadoPorId(mov.estado_cuenta_id) || periodoCierreActual());
        limpiarFiltrosClasificacion();
        if (mov.estado_cuenta_id) {
            estadoSeleccionadoId = mov.estado_cuenta_id;
            clasificacionSeleccionada = valorFiltroEstado(mov.estado_cuenta_id);
        } else if (mov.cuenta) {
            estadoSeleccionadoId = "";
            clasificacionSeleccionada = valorFiltroCuentaAuxiliar(mov.cuenta);
        }
        if ($("clasificacion_descripcion")) { $("clasificacion_descripcion").value = mov.concepto || ""; }
        if ($("clasificacion_cfdi")) { $("clasificacion_cfdi").value = "pendiente"; }
        seleccionados = {};
        seleccionados[mov.id] = true;
        activarTab("contabilidad_tab_movimientos");
        render();
        window.setTimeout(function () {
            var row = document.querySelector("#contabilidad_movimientos tr[data-id=\"" + CSS.escape(mov.id) + "\"]");
            if (row) { row.scrollIntoView({behavior: "smooth", block: "center"}); }
        }, 150);
    }
    function verEstadoCuenta(estadoId) {
        estadoSeleccionadoId = estadoId;
        clasificacionSeleccionada = valorFiltroEstado(estadoId);
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
                clasificacionSeleccionada = "";
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
        var rows = movimientosPeriodoActual();
        var lineas = [headersReporteMovimientos().join("\t")];
        rows.forEach(function (m) {
            lineas.push(filaReporteMovimiento(m, periodo, $("contabilidad_cuenta").value || "").map(function (v) {
                return String(v == null ? "" : v).replace(/\t/g, " ").replace(/\r?\n/g, " ");
            }).join("\t"));
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
            if ($("contabilidad_cfdi_periodo")) {
                $("contabilidad_cfdi_periodo").value = periodoCierreActual();
            }
            render();
        });
        $("contabilidad_estado_periodo").addEventListener("change", function () {
            if ($("contabilidad_periodo")) {
                $("contabilidad_periodo").value = periodoEstadoCuentaActual();
            }
            if ($("contabilidad_cfdi_periodo")) {
                $("contabilidad_cfdi_periodo").value = periodoEstadoCuentaActual();
            }
            render();
        });
        $("contabilidad_cfdi_periodo").addEventListener("change", function () {
            if ($("contabilidad_periodo")) {
                $("contabilidad_periodo").value = periodoCfdiActual();
            }
            if ($("contabilidad_estado_periodo")) {
                $("contabilidad_estado_periodo").value = periodoCfdiActual();
            }
            render();
        });
        $("manual_fecha").value = new Date().toISOString().slice(0, 10);
        $("manual_agregar").addEventListener("click", agregarMovimientoManual);
        $("contabilidad_cuenta").addEventListener("input", renderCuentaCfdiDefault);
        $("contabilidad_cfdi_cuenta_default").addEventListener("change", function () { programarAutosave(); });
        $("contabilidad_cfdi_tratamiento_default").addEventListener("change", function () { prepararDefaultsCfdiAuxiliar(); render(); });
        $("contabilidad_cfdi_categoria_default").addEventListener("change", function () { prepararDefaultsCfdiAuxiliar(); render(); });
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
            var check = e.target.closest("[data-seleccionar-cfdi]");
            if (check) {
                if (check.checked) {
                    cfdisSeleccionados[check.getAttribute("data-seleccionar-cfdi")] = true;
                } else {
                    delete cfdisSeleccionados[check.getAttribute("data-seleccionar-cfdi")];
                }
                renderCfdis();
                return;
            }
            var row = e.target.closest("tr[data-cfdi-id]");
            var cfdi = row ? cfdiPorId(row.getAttribute("data-cfdi-id")) : null;
            var campo = e.target.getAttribute("data-cfdi-campo");
            if (!cfdi || !campo) { return; }
            cfdi[campo] = e.target.value;
            if (campo === "categoria" && cfdi.categoria === "comision_plataforma") {
                cfdi.forma_pago = "retencion_plataforma";
                cfdi.cuenta_pago = cfdi.cuenta_pago || "Mercado Pago - comisiones";
            }
            if (campo === "forma_pago") {
                cfdi.cuenta_pago = cuentaPorFormaPago(cfdi.forma_pago, cfdi.cuenta_pago);
            }
            render();
        });
        $("contabilidad_cfdis").addEventListener("click", function (e) {
            var ligar = e.target.closest("[data-ligar-cfdi]");
            var crear = e.target.closest("[data-crear-mov-cfdi]");
            var desligar = e.target.closest("[data-desligar-cfdi]");
            var eliminar = e.target.closest("[data-eliminar-cfdi]");
            if (ligar) { ligarCfdiSugerido(ligar.getAttribute("data-ligar-cfdi")); }
            if (crear) { crearMovimientoDesdeCfdi(crear.getAttribute("data-crear-mov-cfdi")); }
            if (desligar) { desligarCfdi(desligar.getAttribute("data-desligar-cfdi")); }
            if (eliminar) { eliminarCfdi(eliminar.getAttribute("data-eliminar-cfdi")); }
        });
        $("cfdi_select_todos").addEventListener("change", function (e) {
            seleccionarCfdisVisibles(e.target.checked);
        });
        $("cfdi_masivo_aplicar").addEventListener("click", aplicarMasivoCfdi);
        $("cfdi_masivo_crear_aux").addEventListener("click", crearAuxiliaresCfdiSeleccionados);
        $("cfdi_masivo_deshacer_aux").addEventListener("click", deshacerAuxiliaresCfdiSeleccionados);
        $("cfdi_deshacer_no_exactas").addEventListener("click", deshacerRelacionesNoExactas);
        $("cfdi_masivo_limpiar").addEventListener("click", limpiarSeleccionCfdi);
        $("contabilidad_cfdi_crear_movimientos").addEventListener("click", crearMovimientosCfdiSinBanco);
        $("clasificacion_origen").addEventListener("change", function (e) {
            var valor = e.target.value || "";
            clasificacionSeleccionada = valor;
            estadoSeleccionadoId = valor.indexOf("estado:") === 0 ? valor.replace(/^estado:/, "") : "";
            seleccionados = {};
            render();
        });
        ["clasificacion_descripcion", "clasificacion_tipo", "clasificacion_actividad", "clasificacion_categoria", "clasificacion_cfdi", "clasificacion_cuenta"].forEach(function (id) {
            var refrescarClasificacion = function () {
                seleccionados = {};
                render();
            };
            $(id).addEventListener("input", refrescarClasificacion);
            $(id).addEventListener("change", refrescarClasificacion);
        });
        $("clasificacion_filtros_limpiar").addEventListener("click", function () {
            limpiarFiltrosClasificacion();
            seleccionados = {};
            render();
        });
        ["cfdi_filtro_descripcion", "cfdi_filtro_categoria", "cfdi_filtro_actividad", "cfdi_filtro_forma_pago", "cfdi_filtro_cuenta", "cfdi_filtro_relacion"].forEach(function (id) {
            var refrescarCfdi = function () {
                cfdisSeleccionados = {};
                render();
            };
            $(id).addEventListener("input", refrescarCfdi);
            $(id).addEventListener("change", refrescarCfdi);
        });
        $("cfdi_filtros_limpiar").addEventListener("click", function () {
            ["cfdi_filtro_descripcion", "cfdi_filtro_categoria", "cfdi_filtro_actividad", "cfdi_filtro_forma_pago", "cfdi_filtro_cuenta", "cfdi_filtro_relacion"].forEach(function (id) {
                $(id).value = "";
            });
            cfdisSeleccionados = {};
            render();
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
            abrirRelacionMovimiento(btn.getAttribute("data-ligar"));
        });
        $("contabilidad_relacion_buscar").addEventListener("input", function (e) {
            relacionContexto.busqueda = e.target.value || "";
            renderCandidatosRelacion();
        });
        $("contabilidad_relacion_candidatos").addEventListener("click", function (e) {
            var btn = e.target.closest("[data-confirmar-relacion]");
            if (!btn) { return; }
            relacionarMovimientoCfdi(btn.getAttribute("data-mov-id"), btn.getAttribute("data-cfdi-id"));
        });
        $("contabilidad_pendientes").addEventListener("click", function (e) {
            var revisar = e.target.closest("[data-revisar-pendiente]");
            if (revisar) { revisarPendienteContador(revisar.getAttribute("data-revisar-pendiente")); }
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
            var ver = e.target.closest("[data-ver-conciliacion]");
            var btn = e.target.closest("[data-export-cuenta]");
            if (ver) { verConciliacionCuenta(ver.getAttribute("data-ver-conciliacion")); }
            if (btn) { exportarCsvCuenta(btn.getAttribute("data-export-cuenta")); }
        });
        $("contabilidad_conciliacion_modal_exportar").addEventListener("click", function () {
            if (conciliacionModalRows.length) {
                exportarRowsCsv("conciliacion_" + slug(conciliacionModalNombre) + "_" + periodoCierreActual() + ".csv", conciliacionModalRows, cuentaConciliacionActual);
            }
        });
        $("contabilidad_conciliacion_modal_simple").addEventListener("click", function () {
            if (conciliacionModalRows.length) {
                exportarRowsSimple("conciliacion_simple_" + slug(conciliacionModalNombre) + "_" + periodoCierreActual() + ".csv", conciliacionModalRows, cuentaConciliacionActual);
            }
        });
        $("contabilidad_ver_ventas").addEventListener("click", verVentasConciliacion);
        $("contabilidad_ver_traspasos").addEventListener("click", verTraspasosConciliacion);
        $("contabilidad_detectar_traspasos").addEventListener("click", detectarTraspasos);
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
            clasificacionSeleccionada = "";
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
        $("contabilidad_limpiar").addEventListener("click", function () {
            movimientos = [];
            cfdis = [];
            estadosCuenta = [];
            filasCrudas = [];
            matrizCruda = [];
            encabezadosCrudos = [];
            archivoCrudoHoja = "";
            estadoSeleccionadoId = "";
            clasificacionSeleccionada = "";
            seleccionados = {};
            localStorage.removeItem(DRAFT_KEY);
            render();
        });
        restaurarBorradorActivoLocal();
        render();
    }
    document.addEventListener("DOMContentLoaded", bind);
})();
