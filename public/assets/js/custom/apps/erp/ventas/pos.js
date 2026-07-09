"use strict";
(function () {
    var catalogos = {almacenes: [], cajas: [], turnos_abiertos: [], metodos_pago: [], schema_cajas_pendiente: true, schema_turnos_pendiente: true};
    var carrito = [];
    var pagos = [];
    var cuentas = [];
    var cuentaActivaId = "";
    var excepcionActiva = null;
    var temporizador = null;
    var terminalKey = "erp_pos_terminal_config_v1";
    var cuentasKeyBase = "erp_pos_cuentas_atencion_v1";
    var asignacionOficial = null;
    var clientesCrmSeleccionables = {};
    var placeholderImagen = "data:image/svg+xml;charset=UTF-8,%3Csvg%20xmlns='http://www.w3.org/2000/svg'%20viewBox='0%200%20400%20300'%3E%3Crect%20width='400'%20height='300'%20fill='%23f1f3f6'/%3E%3Cpath%20d='M80%20225h240l-70-85-55%2065-35-42z'%20fill='%23c8ced8'/%3E%3Ccircle%20cx='135'%20cy='105'%20r='28'%20fill='%23d7dce5'/%3E%3C/svg%3E";

    function request(url, data) {
        return fetch(url, {
            method: data ? "POST" : "GET",
            headers: data ? {
                "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
                "X-CSRF-Token": window.ERP_CSRF_TOKEN || ""
            } : {},
            body: data ? new URLSearchParams(data).toString() : null,
            credentials: "same-origin"
        }).then(function (response) { return response.json(); });
    }
    function requestGet(url, params) {
        var query = new URLSearchParams(params || {}).toString();
        return fetch(url + (query ? "?" + query : ""), {
            method: "GET",
            credentials: "same-origin"
        }).then(function (response) { return response.json(); });
    }
    function escapeHtml(value) {
        var div = document.createElement("div");
        div.textContent = value == null ? "" : String(value);
        return div.innerHTML;
    }
    function textoLegible(value) {
        return String(value == null ? "" : value)
            .replace(/\u00c3\u00a1/g, "\u00e1").replace(/\u00c3\u00a9/g, "\u00e9").replace(/\u00c3\u00ad/g, "\u00ed")
            .replace(/\u00c3\u00b3/g, "\u00f3").replace(/\u00c3\u00ba/g, "\u00fa").replace(/\u00c3\u0081/g, "\u00c1")
            .replace(/\u00c3\u0089/g, "\u00c9").replace(/\u00c3\u008d/g, "\u00cd").replace(/\u00c3\u0093/g, "\u00d3")
            .replace(/\u00c3\u009a/g, "\u00da").replace(/\u00c3\u00b1/g, "\u00f1").replace(/\u00c3\u0091/g, "\u00d1")
            .replace(/\u00c2/g, "").replace(/\ufffd/g, "");
    }
    function cantidad(value) {
        var numero = Number(String(value == null ? "" : value).replace(",", "."));
        return Number.isFinite(numero) ? numero : 0;
    }
    function dinero(value) {
        return new Intl.NumberFormat("es-MX", {style: "currency", currency: "MXN"}).format(cantidad(value));
    }
    function numero(value) {
        return Number(cantidad(value).toFixed(4)).toString();
    }
    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-26
     * Proposito: normalizar imagen ERP/ecommerce para tarjetas POS.
     * Impacto: permite mostrar `url_imagen` devuelto por VentasErp y evita placeholder roto.
     */
    function imagen(item) {
        var url = item.url_imagen || item.imagen || item.imagen_principal || item.portada || "";
        url = String(url || "").trim();
        if (!url) { return placeholderImagen; }
        if (/^(https?:)?\/\//i.test(url) || url.indexOf("data:") === 0 || url.charAt(0) === "/") {
            return url;
        }
        return "/" + url.replace(/^\/+/, "");
    }
    function almacenActual() {
        return document.getElementById("pos_almacen").value || "";
    }
    function terminalConfig() {
        try {
            return JSON.parse(localStorage.getItem(terminalKey) || "{}") || {};
        } catch (error) {
            return {};
        }
    }
    function guardarTerminalConfig(config) {
        localStorage.setItem(terminalKey, JSON.stringify(config || {}));
    }
    function limpiarTerminalConfig() {
        localStorage.removeItem(terminalKey);
    }
    function cuentasKey() {
        var usuario = (window.POS_USUARIO_ACTUAL || {}).id_usuario || "sin_usuario";
        return cuentasKeyBase + "_" + usuario;
    }
    function nuevaCuenta(nombre) {
        return {
            id: "cta_" + Date.now() + "_" + Math.floor(Math.random() * 10000),
            nombre: nombre || ("Cuenta " + (cuentas.length + 1)),
            carrito: [],
            pagos: [],
            excepcion_comercial: null,
            cliente_crm: null,
            cliente_saldo_crm: null,
            cliente_nombre: "",
            cliente_telefono: "",
            fecha_creacion: new Date().toISOString(),
            fecha_actualizacion: new Date().toISOString()
        };
    }
    function cargarCuentas() {
        try {
            cuentas = JSON.parse(localStorage.getItem(cuentasKey()) || "[]") || [];
        } catch (error) {
            cuentas = [];
        }
        if (!cuentas.length) {
            cuentas = [nuevaCuenta("Cuenta 1")];
        }
        cuentaActivaId = cuentaActivaId || cuentas[0].id;
        aplicarCuentaActiva();
    }
    function guardarCuentas() {
        var cuenta = obtenerCuentaActiva();
        if (cuenta) {
            cuenta.carrito = carrito;
            cuenta.pagos = pagos;
            cuenta.excepcion_comercial = excepcionActiva;
            cuenta.cliente_crm = cuenta.cliente_crm || null;
            cuenta.cliente_saldo_crm = cuenta.cliente_saldo_crm || null;
            cuenta.cliente_nombre = document.getElementById("pos_cliente") ? document.getElementById("pos_cliente").value.trim() : cuenta.cliente_nombre;
            cuenta.cliente_telefono = document.getElementById("pos_cliente_telefono") ? document.getElementById("pos_cliente_telefono").value.trim() : cuenta.cliente_telefono;
            cuenta.fecha_actualizacion = new Date().toISOString();
        }
        localStorage.setItem(cuentasKey(), JSON.stringify(cuentas));
    }
    function obtenerCuentaActiva() {
        return cuentas.find(function (cuenta) { return cuenta.id === cuentaActivaId; }) || cuentas[0] || null;
    }
    function aplicarCuentaActiva() {
        var cuenta = obtenerCuentaActiva();
        if (!cuenta) { return; }
        cuentaActivaId = cuenta.id;
        carrito = cuenta.carrito || [];
        pagos = cuenta.pagos || [];
        excepcionActiva = cuenta.excepcion_comercial || null;
        if (document.getElementById("pos_cliente")) {
            document.getElementById("pos_cliente").value = cuenta.cliente_nombre || "";
        }
        if (document.getElementById("pos_cliente_telefono")) {
            document.getElementById("pos_cliente_telefono").value = cuenta.cliente_telefono || "";
        }
        renderSaldoClienteCrm();
        if (cuenta.cliente_crm && cuenta.cliente_crm.id_cliente_crm) {
            consultarSaldoClienteCrm(cuenta.cliente_crm.id_cliente_crm);
        }
    }
    function totalCuenta(cuenta) {
        if (cuenta.excepcion_comercial && cuenta.excepcion_comercial.totales && cuenta.excepcion_comercial.totales.total_con_excepcion != null) {
            return cantidad(cuenta.excepcion_comercial.totales.total_con_excepcion);
        }
        return (cuenta.carrito || []).reduce(function (suma, item) {
            return suma + (cantidad(item.cantidad) * cantidad(item.precio_unitario));
        }, 0);
    }
    function limpiarExcepcionActiva() {
        if (!excepcionActiva) { return; }
        excepcionActiva = null;
        var cuenta = obtenerCuentaActiva();
        if (cuenta) {
            cuenta.excepcion_comercial = null;
        }
        renderExcepcionActiva();
    }
    function renderExcepcionActiva() {
        var contenedor = document.getElementById("pos_excepcion_activa");
        if (!contenedor) { return; }
        if (!excepcionActiva || !excepcionActiva.folio) {
            contenedor.innerHTML = "";
            return;
        }
        var totales = excepcionActiva.totales || {};
        var cliente = excepcionActiva.cliente || {};
        contenedor.innerHTML = "<div class=\"alert alert-success py-3 mb-0\">" +
            "<div class=\"d-flex flex-wrap justify-content-between gap-2 align-items-start\">" +
                "<div>" +
                    "<div class=\"fw-bold\"><i class=\"bi bi-shield-check me-1\"></i> Excepcion validada " + escapeHtml(excepcionActiva.folio) + "</div>" +
                    "<div class=\"fs-8 text-muted\">" + escapeHtml(cliente.nombre_publico || cliente.cliente_nombre_snapshot || "Cliente/POS") +
                    " | " + escapeHtml(cliente.codigo_cliente || cliente.cliente_codigo_snapshot || cliente.id_cliente_crm || "") + "</div>" +
                "</div>" +
                "<div class=\"text-end\">" +
                    "<div class=\"fw-bold\">" + dinero(totales.total_con_excepcion || 0) + "</div>" +
                    "<div class=\"fs-8 text-muted\">Desc. " + dinero(totales.descuento_total || 0) + "</div>" +
                "</div>" +
                "<button class=\"btn btn-sm btn-light-danger\" id=\"pos_excepcion_limpiar\" type=\"button\"><i class=\"bi bi-x-lg\"></i></button>" +
            "</div>" +
        "</div>";
        document.getElementById("pos_excepcion_limpiar").addEventListener("click", function () {
            limpiarExcepcionActiva();
            actualizarTotales();
            guardarCuentas();
            renderCuentas();
        });
    }
    function renderCuentas() {
        var contenedor = document.getElementById("pos_cuentas");
        if (!contenedor) { return; }
        contenedor.innerHTML = cuentas.map(function (cuenta) {
            var partidas = (cuenta.carrito || []).length;
            var activa = cuenta.id === cuentaActivaId;
            var etiqueta = cuenta.cliente_nombre || cuenta.cliente_telefono || cuenta.nombre;
            return "<div class=\"d-flex align-items-start gap-1\">" +
                "<button class=\"pos-cuenta-btn" + (activa ? " active" : "") + "\" type=\"button\" data-pos-cuenta=\"" + escapeHtml(cuenta.id) + "\">" +
                    "<div class=\"fw-bold text-truncate\">" + escapeHtml(etiqueta) + "</div>" +
                    "<div class=\"text-muted fs-8\">" + partidas + " partida(s)</div>" +
                    "<div class=\"pos-cuenta-total\">" + dinero(totalCuenta(cuenta)) + "</div>" +
                "</button>" +
                "<button class=\"btn btn-sm btn-light-danger pos-cuenta-close\" type=\"button\" data-pos-cuenta-cerrar=\"" + escapeHtml(cuenta.id) + "\" title=\"Cerrar cuenta\"><i class=\"bi bi-x\"></i></button>" +
            "</div>";
        }).join("");
    }
    function seleccionarCuenta(id) {
        guardarCuentas();
        cuentaActivaId = id;
        aplicarCuentaActiva();
        renderCuentas();
        renderCarrito();
        renderPagos();
        renderExcepcionActiva();
        document.getElementById("pos_validacion").innerHTML = "";
    }
    function crearCuentaAtencion() {
        guardarCuentas();
        var cuenta = nuevaCuenta("Cuenta " + (cuentas.length + 1));
        cuentas.push(cuenta);
        cuentaActivaId = cuenta.id;
        aplicarCuentaActiva();
        guardarCuentas();
        renderCuentas();
        renderCarrito();
        renderPagos();
        renderExcepcionActiva();
        document.getElementById("pos_validacion").innerHTML = "";
        document.getElementById("pos_buscar").focus();
    }
    function cerrarCuentaAtencion(id) {
        if (cuentas.length === 1) {
            cuentas[0].carrito = [];
            cuentas[0].pagos = [];
            cuentas[0].cliente_crm = null;
            cuentas[0].cliente_nombre = "";
            cuentas[0].cliente_telefono = "";
            cuentaActivaId = cuentas[0].id;
        } else {
            cuentas = cuentas.filter(function (cuenta) { return cuenta.id !== id; });
            if (cuentaActivaId === id) {
                cuentaActivaId = cuentas[0].id;
            }
        }
        aplicarCuentaActiva();
        guardarCuentas();
        renderCuentas();
        renderCarrito();
        renderPagos();
        renderExcepcionActiva();
        document.getElementById("pos_validacion").innerHTML = "";
    }
    function cajaActual() {
        return document.getElementById("pos_caja").value || "";
    }
    function turnoActual() {
        return document.getElementById("pos_turno").value || "";
    }
    function tipoDocumentoActual() {
        return document.getElementById("pos_tipo_documento").value || "venta";
    }
    function exigePagoCompleto() {
        return tipoDocumentoActual() === "venta" ? "1" : "0";
    }
    function clientePublico() {
        var nombre = document.getElementById("pos_cliente").value.trim();
        var telefono = document.getElementById("pos_cliente_telefono").value.trim();
        if (nombre && telefono) { return nombre + " / Tel " + telefono; }
        if (telefono) { return "Tel " + telefono; }
        return nombre;
    }
    function clienteCrmActivo() {
        var cuenta = obtenerCuentaActiva();
        return cuenta && cuenta.cliente_crm ? cuenta.cliente_crm : null;
    }
    function idClienteCrmActivo() {
        var cliente = clienteCrmActivo();
        return cliente && cliente.id_cliente_crm ? cliente.id_cliente_crm : "";
    }
    function clienteSaldoCrmActivo() {
        var cuenta = obtenerCuentaActiva();
        return cuenta && cuenta.cliente_saldo_crm ? cuenta.cliente_saldo_crm : null;
    }
    function normalizarIdentificadorUi(valor) {
        valor = String(valor || "").trim().toLowerCase();
        var digitos = valor.replace(/\D+/g, "");
        return digitos.length >= 7 ? digitos : valor.replace(/\s+/g, "");
    }
    function identificadorClienteActivo() {
        var cliente = clienteCrmActivo();
        if (cliente && cliente.identificador) { return cliente.identificador; }
        if (cliente && cliente.valor) { return cliente.valor; }
        return document.getElementById("pos_cliente_telefono") ? document.getElementById("pos_cliente_telefono").value.trim() : "";
    }
    function seleccionarClienteCrm(cliente) {
        var cuenta = obtenerCuentaActiva();
        if (!cuenta || !cliente) { return; }
        cuenta.cliente_crm = {
            id_cliente_crm: cliente.id_cliente_crm || "",
            codigo_cliente: cliente.codigo_cliente || "",
            nombre_publico: cliente.nombre_publico || "",
            identificador: cliente.identificador || cliente.valor || "",
            estatus: cliente.estatus || "",
            calidad_datos: cliente.calidad_datos || ""
        };
        cuenta.cliente_saldo_crm = {loading: true, consultado: false};
        cuenta.cliente_nombre = cuenta.cliente_crm.nombre_publico || cuenta.cliente_nombre || "";
        cuenta.cliente_telefono = cuenta.cliente_crm.identificador || cuenta.cliente_telefono || "";
        if (document.getElementById("pos_cliente")) {
            document.getElementById("pos_cliente").value = cuenta.cliente_nombre;
        }
        if (document.getElementById("pos_cliente_telefono")) {
            document.getElementById("pos_cliente_telefono").value = cuenta.cliente_telefono;
        }
        guardarCuentas();
        renderCuentas();
        renderSaldoClienteCrm();
        consultarSaldoClienteCrm(cuenta.cliente_crm.id_cliente_crm);
        actualizarAltaClienteEstado(true);
    }
    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-07
     * Proposito: mostrar saldo CRM disponible en POS sin mover saldo ni caja.
     * Impacto: el cajero sabe si puede usar saldo cliente antes de agregar el pago virtual.
     */
    function renderSaldoClienteCrm() {
        var contenedor = document.getElementById("pos_cliente_saldo_crm");
        if (!contenedor) {
            actualizarBotonSaldoCrm();
            return;
        }
        var cliente = clienteCrmActivo();
        var saldo = clienteSaldoCrmActivo();
        if (!cliente || !cliente.id_cliente_crm) {
            contenedor.innerHTML = "";
            actualizarBotonSaldoCrm();
            return;
        }
        if (saldo && saldo.loading) {
            contenedor.innerHTML = "<div class=\"alert alert-info py-2 mb-0 fs-8\"><span class=\"spinner-border spinner-border-sm me-2\"></span>Consultando saldo cliente...</div>";
            actualizarBotonSaldoCrm();
            return;
        }
        if (!saldo || !saldo.consultado) {
            contenedor.innerHTML = "<div class=\"alert alert-light py-2 mb-0 fs-8\">Cliente CRM seleccionado. Saldo cliente pendiente de consulta.</div>";
            actualizarBotonSaldoCrm();
            return;
        }
        var disponible = cantidad(saldo.saldo_disponible || 0);
        var clase = disponible > 0 ? "alert-primary" : "alert-warning";
        var titulo = disponible > 0 ? "Saldo cliente disponible" : "Sin saldo cliente disponible";
        contenedor.innerHTML = "<div class=\"alert " + clase + " py-2 mb-0\">" +
            "<div class=\"d-flex justify-content-between align-items-center gap-2\">" +
                "<div><div class=\"fw-bold fs-8\">" + escapeHtml(titulo) + "</div><div class=\"text-muted fs-9\">" + escapeHtml(cliente.nombre_publico || cliente.codigo_cliente || "") + "</div></div>" +
                "<div class=\"fw-bold\">" + dinero(disponible) + "</div>" +
            "</div>" +
            (saldo.avisos && saldo.avisos.length ? "<div class=\"fs-9 text-muted mt-1\">" + escapeHtml(saldo.avisos.join(" | ")) + "</div>" : "") +
        "</div>";
        actualizarBotonSaldoCrm();
    }
    function actualizarBotonSaldoCrm() {
        var boton = document.querySelector("[data-pos-pago-rapido=\"saldo_crm\"]");
        if (!boton) { return; }
        var cliente = clienteCrmActivo();
        var saldo = clienteSaldoCrmActivo();
        var consultado = saldo && saldo.consultado;
        var disponible = consultado ? cantidad(saldo.saldo_disponible || 0) : null;
        boton.disabled = !!(cliente && (saldo && saldo.loading || (consultado && disponible <= 0)));
        boton.title = !cliente
            ? "Selecciona un cliente CRM para usar saldo cliente"
            : (saldo && saldo.loading
                ? "Consultando saldo cliente"
                : (consultado && disponible <= 0 ? "Cliente sin saldo disponible" : "Usar saldo cliente disponible"));
    }
    function consultarSaldoClienteCrm(idClienteCrm) {
        var cuenta = obtenerCuentaActiva();
        if (!cuenta || !idClienteCrm) { return; }
        requestGet("/ventas/cliente_saldo_crm_readonly_erp", {id_cliente_crm: idClienteCrm}).then(function (response) {
            var cuentaActual = obtenerCuentaActiva();
            if (!cuentaActual || !cuentaActual.cliente_crm || String(cuentaActual.cliente_crm.id_cliente_crm || "") !== String(idClienteCrm)) {
                return;
            }
            var depurar = response.depurar || {};
            cuentaActual.cliente_saldo_crm = {
                loading: false,
                consultado: !response.error,
                saldo_disponible: depurar.saldo_disponible || 0,
                saldo_retenido: depurar.saldo_retenido || 0,
                saldo_total: depurar.saldo_total || 0,
                avisos: depurar.avisos || []
            };
            guardarCuentas();
            renderSaldoClienteCrm();
        }).catch(function (error) {
            var cuentaActual = obtenerCuentaActiva();
            if (cuentaActual) {
                cuentaActual.cliente_saldo_crm = {loading: false, consultado: false, avisos: [error.message || String(error)]};
                guardarCuentas();
            }
            renderSaldoClienteCrm();
        });
    }
    function actualizarAltaClienteEstado(seleccionado) {
        var boton = document.getElementById("pos_cliente_alta_dryrun");
        if (!boton) { return; }
        if (seleccionado) {
            boton.disabled = true;
            boton.innerHTML = "<i class=\"bi bi-check2-circle\"></i> Cliente seleccionado";
            boton.title = "El cliente ya existe en CRM y esta seleccionado en la cuenta";
            return;
        }
        boton.disabled = false;
        boton.innerHTML = "<i class=\"bi bi-person-plus\"></i> Validar alta";
        boton.title = "Validar alta express si no existe el cliente";
    }
    function limpiarClienteCrmSeleccionado() {
        var cuenta = obtenerCuentaActiva();
        if (!cuenta || !cuenta.cliente_crm) { return; }
        cuenta.cliente_crm = null;
        guardarCuentas();
        renderCuentas();
        actualizarAltaClienteEstado(false);
    }
    function unidadTexto(unidad) {
        var codigo = unidad.codigo_etiqueta_interna || unidad.codigo_unico || unidad.serie_fabricante || ("Unidad " + unidad.id_inventario_unidad);
        return codigo + " | " + unidad.estado_fisico + " | disp " + numero(unidad.cantidad_base_disponible) + " " + (unidad.unidad_base || "");
    }
    function modoInicial(disponibilidad, producto) {
        var abierta = (disponibilidad.unidades || []).find(function (unidad) { return unidad.estado_fisico === "abierta"; });
        if (abierta && Number(producto.permite_venta_fraccionaria || 0) === 1) {
            return {modo_salida: "granel_unidad_abierta", id_inventario_unidad: abierta.id_inventario_unidad, cantidad: producto.incremento_minimo_venta || 1};
        }
        var cerrada = (disponibilidad.unidades || []).find(function (unidad) { return unidad.estado_fisico === "cerrada"; });
        if (cerrada) {
            return {modo_salida: "unidad_cerrada", id_inventario_unidad: cerrada.id_inventario_unidad, cantidad: cerrada.cantidad_base_disponible || 1};
        }
        return {modo_salida: "existencia_agregada", id_inventario_unidad: "", cantidad: 1};
    }
    function cargarCatalogos() {
        return request("/ventas/pos_catalogos_erp").then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            catalogos = response.depurar || catalogos;
            document.getElementById("pos_almacen").innerHTML = (catalogos.almacenes || []).map(function (item) {
                return "<option value=\"" + item.id_almacen + "\">" + escapeHtml((item.codigo_almacen || "") + " - " + (item.nombre_comercial || item.almacen || "")) + "</option>";
            }).join("") || "<option value=\"\">Sin almacenes POS</option>";
            document.getElementById("pos_terminal_almacen").innerHTML = document.getElementById("pos_almacen").innerHTML;
            return cargarAsignacionOficial();
        }).catch(mostrarError);
    }
    function cargarAsignacionOficial() {
        return request("/ventas/terminal_asignacion_actual_erp").then(function (response) {
            asignacionOficial = response && response.depurar && response.depurar.asignacion_activa ? response.depurar : null;
            aplicarTerminalConfig();
            llenarCajas();
            llenarTurnos();
        }).catch(function () {
            asignacionOficial = null;
            aplicarTerminalConfig();
            llenarCajas();
            llenarTurnos();
        });
    }
    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-26
     * Proposito: fijar visualmente el POS a la tienda configurada en esta terminal.
     * Impacto: evita que el cajero opere accidentalmente otra sucursal mientras no exista asignacion persistente en BD.
     */
    function aplicarTerminalConfig() {
        if (asignacionOficial && asignacionOficial.asignacion) {
            aplicarAsignacionOficial();
            actualizarOperador();
            return;
        }
        var config = terminalConfig();
        var select = document.getElementById("pos_almacen");
        var estado = document.getElementById("pos_terminal_estado");
        var badge = document.getElementById("pos_terminal_badge");
        var almacen = (catalogos.almacenes || []).find(function (item) { return String(item.id_almacen) === String(config.id_almacen); });
        if (almacen) {
            select.value = String(almacen.id_almacen);
            select.disabled = true;
            estado.textContent = "Terminal fijada a " + (almacen.nombre_comercial || almacen.almacen || almacen.codigo_almacen);
            badge.textContent = "Terminal: " + (almacen.codigo_almacen || almacen.nombre_comercial || almacen.almacen);
            badge.className = "badge badge-light-success fs-7";
            document.getElementById("pos_terminal_almacen").value = String(almacen.id_almacen);
        } else {
            select.disabled = false;
            estado.textContent = "Terminal sin fijar";
            badge.textContent = "Terminal libre";
            badge.className = "badge badge-light-warning fs-7";
        }
        actualizarOperador();
    }
    function aplicarAsignacionOficial() {
        var asignacion = asignacionOficial.asignacion || {};
        var select = document.getElementById("pos_almacen");
        var estado = document.getElementById("pos_terminal_estado");
        var badge = document.getElementById("pos_terminal_badge");
        var almacenNombre = asignacion.nombre_comercial || asignacion.almacen || asignacion.codigo_almacen || "Sucursal asignada";
        select.value = String(asignacion.id_almacen || "");
        select.disabled = true;
        select.title = "Sucursal fijada por asignacion oficial POS";
        estado.textContent = "Asignacion oficial: " + almacenNombre + " / " + (asignacion.caja_codigo || "Caja");
        badge.textContent = "POS oficial: " + (asignacion.terminal_codigo || asignacion.caja_codigo || asignacion.codigo_almacen || "Asignado");
        badge.className = "badge badge-light-success fs-7";
        document.getElementById("pos_terminal_almacen").value = String(asignacion.id_almacen || "");
    }
    function actualizarOperador() {
        var usuario = window.POS_USUARIO_ACTUAL || {};
        var nombre = String(usuario.nombre || "").trim() || ("Usuario " + (usuario.id_usuario || ""));
        document.getElementById("pos_operador_badge").textContent = "Operador: " + nombre;
    }
    function guardarTerminalDesdeModal() {
        var idAlmacen = document.getElementById("pos_terminal_almacen").value;
        guardarTerminalConfig({id_almacen: idAlmacen, fecha_configuracion: new Date().toISOString()});
        aplicarTerminalConfig();
        llenarCajas();
        llenarTurnos();
        bootstrap.Modal.getOrCreateInstance(document.getElementById("pos_terminal_modal")).hide();
    }
    function liberarTerminalDesdeModal() {
        limpiarTerminalConfig();
        aplicarTerminalConfig();
        llenarCajas();
        llenarTurnos();
        bootstrap.Modal.getOrCreateInstance(document.getElementById("pos_terminal_modal")).hide();
    }
    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-26
     * Proposito: filtrar cajas POS por almacen/tienda seleccionada.
     * Impacto: asegura que la venta se disene ligada a tienda/almacen/caja.
     */
    function llenarCajas() {
        var cajas = (catalogos.cajas || []).filter(function (item) {
            return String(item.id_almacen) === String(almacenActual());
        });
        var select = document.getElementById("pos_caja");
        if (catalogos.schema_cajas_pendiente) {
            select.innerHTML = "<option value=\"\">Cajas pendientes de configurar</option>";
            return;
        }
        select.innerHTML = cajas.map(function (item) {
            return "<option value=\"" + item.id_caja + "\">" + escapeHtml((item.codigo || "") + " - " + (item.nombre || "")) + "</option>";
        }).join("") || "<option value=\"\">Sin caja activa en esta tienda</option>";
        if (asignacionOficial && asignacionOficial.asignacion && asignacionOficial.asignacion.id_caja) {
            select.value = String(asignacionOficial.asignacion.id_caja);
            select.disabled = true;
            select.title = "Caja fijada por asignacion oficial POS";
        } else {
            select.disabled = false;
            select.title = "";
        }
        llenarTurnos();
    }
    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-26
     * Proposito: filtrar turnos abiertos por tienda y caja.
     * Impacto: prepara cobro POS trazable por corte de caja.
     */
    function llenarTurnos() {
        var select = document.getElementById("pos_turno");
        if (catalogos.schema_turnos_pendiente) {
            select.innerHTML = "<option value=\"\">Turnos pendientes</option>";
            return;
        }
        var turnos = (catalogos.turnos_abiertos || []).filter(function (item) {
            return String(item.id_almacen) === String(almacenActual()) && String(item.id_caja) === String(cajaActual());
        });
        select.innerHTML = turnos.map(function (item) {
            return "<option value=\"" + item.id_turno_caja + "\">" + escapeHtml((item.folio || "") + " - " + (item.fecha_apertura || "")) + "</option>";
        }).join("") || "<option value=\"\">Sin turno abierto</option>";
        if (asignacionOficial && asignacionOficial.turno_abierto && asignacionOficial.turno_abierto.id_turno_caja) {
            select.value = String(asignacionOficial.turno_abierto.id_turno_caja);
            select.disabled = true;
            select.title = "Turno vigente de la caja oficial";
        } else if (asignacionOficial && asignacionOficial.asignacion) {
            select.disabled = true;
            select.title = "Sin turno abierto para la caja oficial";
        } else {
            select.disabled = false;
            select.title = "";
        }
        actualizarEstadoCobro();
    }
    function buscar() {
        var termino = document.getElementById("pos_buscar").value.trim();
        clearTimeout(temporizador);
        if (termino.length < 2) {
            renderResultados([]);
            document.getElementById("pos_resultados_estado").textContent = "Escribe al menos 2 caracteres.";
            return;
        }
        temporizador = setTimeout(function () {
            var params = new URLSearchParams({q: termino, id_almacen: almacenActual(), limite: "24"});
            document.getElementById("pos_resultados_estado").textContent = "Buscando...";
            request("/ventas/pos_buscar_skus_erp?" + params.toString()).then(function (response) {
                if (response.error) { throw new Error(response.mensaje); }
                renderResultados(response.depurar || []);
            }).catch(mostrarError);
        }, 220);
    }
    function renderResultados(items) {
        var contenedor = document.getElementById("pos_resultados");
        var vacio = document.getElementById("pos_vacio");
        vacio.classList.toggle("d-none", items.length > 0);
        document.getElementById("pos_resultados_estado").textContent = items.length ? (items.length + " resultado(s)") : "Sin resultados visibles.";
        contenedor.innerHTML = items.map(function (item, index) {
            var disponible = Number(item.existencia_disponible || 0);
            var badges = "";
            if (Number(item.permite_venta_fraccionaria || 0) === 1) { badges += "<span class=\"badge badge-light-info\">Granel</span>"; }
            if (Number(item.unidades_cerradas || 0) > 0) { badges += "<span class=\"badge badge-light-success\">Unidad cerrada</span>"; }
            if (Number(item.unidades_abiertas || 0) > 0) { badges += "<span class=\"badge badge-light-warning\">Unidad abierta</span>"; }
            if (disponible <= 0) { badges += "<span class=\"badge badge-light-danger\">Sin stock</span>"; }
            return "<div class=\"pos-product d-flex flex-column\">" +
                "<img class=\"pos-product-img\" src=\"" + escapeHtml(imagen(item)) + "\" alt=\"\">" +
                "<div class=\"p-2 d-flex flex-column flex-grow-1\">" +
                "<div class=\"text-muted fs-8 fw-bold\">" + escapeHtml(item.sku || "") + "</div>" +
                "<div class=\"fw-semibold pos-product-title text-truncate\">" + escapeHtml(textoLegible(item.nombre_sku || item.producto || "")) + "</div>" +
                "<div class=\"pos-badge-row d-flex flex-nowrap gap-1 my-1 overflow-hidden\">" + badges + "</div>" +
                "<div class=\"d-flex justify-content-between align-items-end mt-auto\">" +
                "<div class=\"pos-product-meta\"><div class=\"fw-bold\">" + dinero(item.precio || 0) + "</div><div class=\"text-muted fs-8\">Disp. " + numero(disponible) + " " + escapeHtml(item.unidad_venta_label || "") + "</div></div>" +
                "<button class=\"btn btn-sm btn-primary\" type=\"button\" data-pos-agregar=\"" + index + "\" title=\"Agregar\"><i class=\"bi bi-plus-lg\"></i></button>" +
                "</div></div></div>";
        }).join("");
        contenedor.querySelectorAll("[data-pos-agregar]").forEach(function (button) {
            button.addEventListener("click", function () {
                agregarProducto(items[Number(button.getAttribute("data-pos-agregar"))]);
            });
        });
    }
    function agregarProducto(producto) {
        if (!almacenActual()) {
            mostrarError(new Error("Selecciona punto de venta"));
            return;
        }
        request("/ventas/pos_disponibilidad_erp?" + new URLSearchParams({id_sku: producto.id_sku, id_almacen: almacenActual()}).toString()).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            var disponibilidad = response.depurar || {};
            var modo = modoInicial(disponibilidad, producto);
            carrito.push({
                id_sku: producto.id_sku,
                sku: producto.sku,
                descripcion: producto.nombre_sku || producto.producto || "",
                imagen: imagen(producto),
                precio_unitario: cantidad(producto.precio || 0),
                cantidad: cantidad(modo.cantidad),
                modo_salida: modo.modo_salida,
                id_inventario_unidad: modo.id_inventario_unidad,
                unidad_venta_label: producto.unidad_venta_label || "",
                permite_venta_fraccionaria: Number(producto.permite_venta_fraccionaria || 0),
                incremento_minimo_venta: cantidad(producto.incremento_minimo_venta || 1),
                disponibilidad: disponibilidad,
                unidades: disponibilidad.unidades || []
            });
            limpiarExcepcionActiva();
            guardarCuentas();
            renderCarrito();
            renderCuentas();
            document.getElementById("pos_validacion").innerHTML = "";
        }).catch(mostrarError);
    }
    function tieneUnidadAbiertaGranel(item) {
        return Number(item.permite_venta_fraccionaria || 0) === 1 && item.unidades.some(function (unidad) {
            return unidad.estado_fisico === "abierta";
        });
    }
    function tieneUnidadCerrada(item) {
        return item.unidades.some(function (unidad) { return unidad.estado_fisico === "cerrada"; });
    }
    function modosRapidos(item) {
        var abiertas = item.unidades.some(function (unidad) { return unidad.estado_fisico === "abierta"; });
        var cerradas = item.unidades.some(function (unidad) { return unidad.estado_fisico === "cerrada"; });
        var granel = abiertas && Number(item.permite_venta_fraccionaria || 0) === 1;
        return "<div class=\"pos-mode-group\" role=\"group\" aria-label=\"Modo de salida\">" +
            "<button class=\"pos-mode-btn" + (item.modo_salida === "existencia_agregada" ? " active" : "") + "\" data-pos-modo-rapido=\"existencia_agregada\" type=\"button\">Stock</button>" +
            "<button class=\"pos-mode-btn" + (item.modo_salida === "unidad_cerrada" ? " active" : "") + "\" data-pos-modo-rapido=\"unidad_cerrada\" type=\"button\"" + (cerradas ? "" : " disabled") + ">Pieza</button>" +
            "<button class=\"pos-mode-btn" + (item.modo_salida === "granel_unidad_abierta" ? " active" : "") + "\" data-pos-modo-rapido=\"granel_unidad_abierta\" type=\"button\"" + (granel ? "" : " disabled") + ">Granel</button>" +
            "</div>";
    }
    function unidadResumen(item) {
        if (item.modo_salida === "existencia_agregada") {
            return "Salida por existencia disponible";
        }
        var unidad = item.unidades.find(function (actual) {
            return String(actual.id_inventario_unidad) === String(item.id_inventario_unidad);
        });
        if (!unidad) {
            return item.modo_salida === "granel_unidad_abierta" ? "Granel sin unidad abierta disponible" : "Pieza sin unidad cerrada disponible";
        }
        return unidadTexto(unidad);
    }
    function etiquetaCantidad(item) {
        if (item.modo_salida === "granel_unidad_abierta") { return "Peso"; }
        if (item.modo_salida === "unidad_cerrada") { return "Pieza"; }
        return "Cant.";
    }
    function renderCarrito() {
        var contenedor = document.getElementById("pos_carrito");
        if (!carrito.length) {
            contenedor.innerHTML = "<div class=\"text-center text-muted py-10\">Agrega productos para prevalidar inventario</div>";
            document.getElementById("pos_carrito_estado").textContent = "Sin partidas";
            actualizarTotales();
            renderExcepcionActiva();
            actualizarOpcionesExcepcionSku();
            return;
        }
        contenedor.innerHTML = "<div class=\"table-responsive\"><table class=\"table table-row-dashed align-middle pos-cart-table mb-0\"><thead><tr><th>Producto</th><th>Salida</th><th class=\"text-end\">Cantidad</th><th class=\"text-end\">Precio</th><th class=\"text-end\">Importe</th><th></th></tr></thead><tbody>" +
            carrito.map(function (item, index) {
                var inputClase = item.modo_salida === "granel_unidad_abierta" ? "form-control form-control-sm pos-weight-input" : "form-control form-control-sm text-center";
                var controlCantidad = item.modo_salida === "granel_unidad_abierta"
                    ? "<input class=\"" + inputClase + "\" data-pos-cantidad inputmode=\"decimal\" value=\"" + escapeHtml(numero(item.cantidad)) + "\">"
                    : "<div class=\"pos-qty ms-auto\"><button class=\"btn btn-light\" data-pos-cantidad-ajuste=\"-1\" type=\"button\" title=\"Disminuir\"><i class=\"bi bi-dash\"></i></button><input class=\"" + inputClase + "\" data-pos-cantidad inputmode=\"decimal\" value=\"" + escapeHtml(numero(item.cantidad)) + "\"><button class=\"btn btn-light\" data-pos-cantidad-ajuste=\"1\" type=\"button\" title=\"Aumentar\"><i class=\"bi bi-plus\"></i></button></div>";
                return "<tr data-pos-item=\"" + index + "\">" +
                    "<td><div class=\"d-flex align-items-center gap-2\"><img class=\"pos-cart-img\" src=\"" + escapeHtml(item.imagen) + "\" alt=\"\"><div class=\"min-w-0\"><div class=\"fw-bold\">" + escapeHtml(item.sku) + "</div><div class=\"text-muted fs-8 text-truncate\" style=\"max-width:220px\">" + escapeHtml(textoLegible(item.descripcion)) + "</div></div></div></td>" +
                    "<td>" + modosRapidos(item) + "<div class=\"text-muted fs-8 mt-1\">" + escapeHtml(unidadResumen(item)) + "</div></td>" +
                    "<td class=\"text-end\"><div class=\"d-flex flex-column align-items-end gap-1\"><span class=\"text-muted fs-8 pos-qty-label\">" + etiquetaCantidad(item) + " " + escapeHtml(item.unidad_venta_label || "") + "</span>" + controlCantidad + "</div></td>" +
                    "<td class=\"text-end\">" + dinero(item.precio_unitario) + "</td>" +
                    "<td class=\"text-end fw-bold\">" + dinero(item.precio_unitario * item.cantidad) + "</td>" +
                    "<td class=\"text-end\"><button class=\"btn btn-sm btn-icon btn-light-danger\" data-pos-quitar type=\"button\" title=\"Quitar\"><i class=\"bi bi-x-lg\"></i></button></td>" +
                    "</tr>";
            }).join("") + "</tbody></table></div>";
        document.getElementById("pos_carrito_estado").textContent = carrito.length ? (carrito.length + " partida(s)") : "Sin partidas";
        actualizarTotales();
        renderExcepcionActiva();
        actualizarOpcionesExcepcionSku();
        guardarCuentas();
        renderCuentas();
    }
    function actualizarOpcionesExcepcionSku() {
        var select = document.getElementById("pos_excepcion_sku_objetivo");
        if (!select) { return; }
        var actual = select.value;
        select.innerHTML = carrito.map(function (item, index) {
            var texto = (index + 1) + ". " + (item.sku || item.id_sku || "") + " - " + textoLegible(item.descripcion || "");
            return "<option value=\"" + escapeHtml(item.id_sku || "") + "\">" + escapeHtml(texto) + "</option>";
        }).join("") || "<option value=\"\">Sin partidas</option>";
        if (actual && Array.prototype.some.call(select.options, function (option) { return option.value === actual; })) {
            select.value = actual;
        }
        actualizarExcepcionCampos();
    }
    function actualizarExcepcionCampos() {
        var tipoNode = document.getElementById("pos_excepcion_tipo");
        if (!tipoNode) { return; }
        var tipo = tipoNode.value || "precio_manual";
        var sku = document.getElementById("pos_excepcion_sku_objetivo");
        var precio = document.getElementById("pos_excepcion_precio");
        var monto = document.getElementById("pos_excepcion_descuento_monto");
        var porcentaje = document.getElementById("pos_excepcion_descuento_porcentaje");
        if (sku) { sku.disabled = tipo === "descuento_general"; }
        if (precio) { precio.disabled = tipo !== "precio_manual"; }
        if (monto) { monto.disabled = tipo === "precio_manual"; }
        if (porcentaje) { porcentaje.disabled = tipo === "precio_manual"; }
    }
    function actualizarTotales() {
        var subtotal = carrito.reduce(function (suma, item) { return suma + (cantidad(item.cantidad) * cantidad(item.precio_unitario)); }, 0);
        var pagado = pagos.reduce(function (suma, item) { return suma + cantidad(item.monto); }, 0);
        var total = subtotal;
        if (excepcionActiva && excepcionActiva.totales && excepcionActiva.totales.total_con_excepcion != null) {
            total = cantidad(excepcionActiva.totales.total_con_excepcion);
            subtotal = cantidad(excepcionActiva.totales.subtotal_original || subtotal);
        }
        document.getElementById("pos_subtotal").textContent = dinero(subtotal);
        document.getElementById("pos_total").textContent = dinero(total);
        document.getElementById("pos_pagado").textContent = dinero(pagado);
        document.getElementById("pos_saldo").textContent = dinero(Math.max(0, total - pagado));
        document.getElementById("pos_cambio").textContent = dinero(Math.max(0, pagado - total));
        actualizarEstadoCobro();
    }
    function totalCobroActual() {
        if (excepcionActiva && excepcionActiva.totales && excepcionActiva.totales.total_con_excepcion != null) {
            return cantidad(excepcionActiva.totales.total_con_excepcion);
        }
        return carrito.reduce(function (suma, item) { return suma + (cantidad(item.cantidad) * cantidad(item.precio_unitario)); }, 0);
    }
    function actualizarEstadoCobro() {
        var boton = document.getElementById("pos_cobrar_real");
        if (!boton || boton.getAttribute("data-cobrando") === "1") { return; }
        if (!turnoActual()) {
            boton.disabled = true;
            boton.title = "Abre turno de caja antes de cobrar";
            boton.innerHTML = "<i class=\"bi bi-lock\"></i> Abrir turno para cobrar";
            return;
        }
        boton.disabled = false;
        boton.title = "Confirmar venta POS";
        boton.innerHTML = "<i class=\"bi bi-cash-coin\"></i> Cobrar";
    }
    function esPagoSaldoCrmUi(pago) {
        return String((pago || {}).metodo_pago || "").toLowerCase() === "saldo_crm" || String((pago || {}).tipo_pago || "").toLowerCase() === "saldo_cliente";
    }
    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-07
     * Proposito: renderizar pagos POS antes del cobro real, incluyendo saldo CRM como pago virtual.
     * Impacto: permite prevalidar saldo/cambio sin registrar caja ni confundir saldo cliente con efectivo.
     */
    function renderPagos() {
        var contenedor = document.getElementById("pos_pagos");
        contenedor.innerHTML = pagos.map(function (item, index) {
            if (esPagoSaldoCrmUi(item)) {
                return "<div class=\"border rounded p-2 mb-2 bg-light-primary\" data-pos-pago=\"" + index + "\">" +
                    "<div class=\"pos-pay-grid\"><div class=\"d-flex align-items-center gap-2 fw-bold text-primary\"><i class=\"bi bi-wallet2\"></i> Saldo cliente</div>" +
                    "<input class=\"form-control form-control-sm text-end fw-bold\" inputmode=\"decimal\" data-pos-pago-monto value=\"" + escapeHtml(numero(item.monto)) + "\">" +
                    "<input class=\"form-control form-control-sm\" data-pos-pago-ref value=\"" + escapeHtml(item.referencia || "") + "\" placeholder=\"Referencia CRM\" disabled>" +
                    "<button class=\"btn btn-sm btn-icon btn-light-danger\" type=\"button\" data-pos-pago-quitar title=\"Quitar pago\"><i class=\"bi bi-x-lg\"></i></button></div>" +
                    "<div class=\"fs-8 text-muted mt-1\">No entra a caja; el backend descuenta el saldo CRM y registra ledger.</div></div>";
            }
            return "<div class=\"border rounded p-2 mb-2\" data-pos-pago=\"" + index + "\">" +
                "<div class=\"pos-pay-grid\"><select class=\"form-select form-select-sm\" data-pos-pago-metodo>" + opcionesMetodoPago(item.id_metodo_pago) + "</select>" +
                "<input class=\"form-control form-control-sm text-end fw-bold\" inputmode=\"decimal\" data-pos-pago-monto value=\"" + escapeHtml(numero(item.monto)) + "\">" +
                "<input class=\"form-control form-control-sm\" data-pos-pago-ref value=\"" + escapeHtml(item.referencia || "") + "\" placeholder=\"Referencia\">" +
                "<button class=\"btn btn-sm btn-icon btn-light-danger\" type=\"button\" data-pos-pago-quitar title=\"Quitar pago\"><i class=\"bi bi-x-lg\"></i></button></div></div>";
        }).join("") || "<div class=\"text-muted fs-8\">Agrega pagos para prevalidar saldo y cambio.</div>";
        actualizarTotales();
        renderExcepcionActiva();
        guardarCuentas();
        renderCuentas();
    }
    function opcionesMetodoPago(seleccionado) {
        return (catalogos.metodos_pago || []).map(function (item) {
            return "<option value=\"" + item.id_metodo_pago + "\"" + (String(seleccionado) === String(item.id_metodo_pago) ? " selected" : "") + ">" + escapeHtml(textoLegible(item.metodo_pago || "")) + "</option>";
        }).join("") || "<option value=\"\">Sin metodos</option>";
    }
    function metodoPorNombre(fragmento) {
        fragmento = fragmento.toLowerCase();
        return (catalogos.metodos_pago || []).find(function (item) {
            return textoLegible(item.metodo_pago || "").toLowerCase().indexOf(fragmento) !== -1;
        }) || (catalogos.metodos_pago || [])[0] || {};
    }
    function agregarPago() {
        var total = totalCobroActual();
        var pagado = pagos.reduce(function (suma, item) { return suma + cantidad(item.monto); }, 0);
        var metodo = (catalogos.metodos_pago || [])[0] || {};
        pagos.push({
            id_metodo_pago: metodo.id_metodo_pago || "",
            monto: Math.max(0, total - pagado),
            referencia: ""
        });
        limpiarExcepcionActiva();
        renderPagos();
    }
    function agregarPagoRapido(tipo) {
        var total = totalCobroActual();
        var pagado = pagos.reduce(function (suma, item) { return suma + cantidad(item.monto); }, 0);
        if (tipo === "saldo_crm") {
            if (!idClienteCrmActivo()) {
                document.getElementById("pos_validacion").innerHTML = "<div class=\"alert alert-warning py-3 mb-0\">Selecciona primero un cliente CRM para usar saldo cliente.</div>";
                abrirClienteAutorizacion("cliente");
                return;
            }
            var saldoInfo = clienteSaldoCrmActivo();
            var saldoDisponible = saldoInfo && saldoInfo.consultado ? cantidad(saldoInfo.saldo_disponible || 0) : null;
            if (saldoDisponible !== null && saldoDisponible <= 0) {
                document.getElementById("pos_validacion").innerHTML = "<div class=\"alert alert-warning py-3 mb-0\">El cliente seleccionado no tiene saldo disponible para aplicar en POS.</div>";
                return;
            }
            var montoSugerido = Math.max(0, total - pagado);
            if (saldoDisponible !== null) {
                montoSugerido = Math.min(montoSugerido, saldoDisponible);
            }
            pagos.push({
                id_metodo_pago: null,
                metodo_pago: "saldo_crm",
                tipo_pago: "saldo_cliente",
                monto: montoSugerido,
                referencia: "saldo_cliente"
            });
            limpiarExcepcionActiva();
            renderPagos();
            return;
        }
        var metodo = tipo === "transferencia" ? metodoPorNombre("transfer") : (tipo === "tarjeta" ? metodoPorNombre("tarj") : metodoPorNombre("efect"));
        pagos.push({
            id_metodo_pago: metodo.id_metodo_pago || "",
            monto: Math.max(0, total - pagado),
            referencia: ""
        });
        limpiarExcepcionActiva();
        renderPagos();
    }
    function prevalidar() {
        if (!carrito.length) {
            mostrarError(new Error("Agrega partidas al carrito"));
            return;
        }
        enviarValidacion("/ventas/pos_carrito_prevalidar_erp", renderValidacion);
    }
    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-26
     * Proposito: simular confirmacion POS sin registrar venta ni inventario.
     * Impacto: muestra bloqueos de esquema/caja/turno/kardex antes de autorizacion.
     */
    function dryRunConfirmacion() {
        if (!carrito.length) {
            mostrarError(new Error("Agrega partidas al carrito"));
            return;
        }
        enviarValidacion("/ventas/pos_confirmar_dryrun_erp", renderDryRun);
    }
    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-26
     * Proposito: simular pedido/apartado con reserva sin apartar inventario.
     * Impacto: permite validar datos de cliente/fecha antes del modulo real de pedidos.
     */
    function dryRunPedidoReserva() {
        if (!carrito.length) {
            mostrarError(new Error("Agrega partidas al carrito"));
            return;
        }
        enviarValidacion("/ventas/pedido_reserva_dryrun_erp", renderPedidoDryRun);
    }
    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-26
     * Proposito: mostrar preview de ticket sin confirmar venta.
     * Impacto: permite validar contenido impreso antes de activar folios reales.
     */
    function ticketPreview() {
        if (!carrito.length) {
            mostrarError(new Error("Agrega partidas al carrito"));
            return;
        }
        enviarValidacion("/ventas/ticket_preview_dryrun_erp", renderTicketPreview);
    }
    function abrirClienteAutorizacion(tab) {
        document.getElementById("pos_cliente_identificador").value = document.getElementById("pos_cliente_telefono").value.trim() || document.getElementById("pos_cliente").value.trim();
        document.getElementById("pos_cliente_alta_nombre").value = document.getElementById("pos_cliente").value.trim();
        document.getElementById("pos_cliente_precio_resultado").innerHTML = "";
        actualizarAltaClienteEstado(!!idClienteCrmActivo());
        actualizarOpcionesExcepcionSku();
        var target = tab === "excepcion" ? "pos_tab_excepcion_btn" : (tab === "folio" ? "pos_tab_folio_btn" : "pos_tab_cliente_btn");
        var tabNode = document.getElementById(target);
        if (tabNode && window.bootstrap) {
            bootstrap.Tab.getOrCreateInstance(tabNode).show();
        }
        bootstrap.Modal.getOrCreateInstance(document.getElementById("pos_cliente_precio_modal")).show();
    }
    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-27
     * Proposito: simular resolucion de cliente/lista/precio desde backend.
     * Impacto: evita que POS aplique descuentos o precios especiales desde JS.
     */
    function clientePrecioDryRun() {
        if (!carrito.length) {
            document.getElementById("pos_cliente_precio_resultado").innerHTML = "<div class=\"alert alert-warning py-3\"><div class=\"fw-bold\">Precios/lista necesita productos.</div><div class=\"fs-8\">Para solo identificar al cliente usa <strong>Buscar cliente</strong>. Para calcular precios especiales, agrega partidas al carrito primero.</div></div>";
            return;
        }
        request("/ventas/pos_cliente_precio_dryrun_erp", {
            id_almacen: almacenActual(),
            canal: document.getElementById("pos_canal").value || "pos",
            id_cliente: idClienteCrmActivo(),
            identificador_cliente: document.getElementById("pos_cliente_identificador").value,
            items: JSON.stringify(carrito.map(function (item) {
                return {
                    id_sku: item.id_sku,
                    cantidad: cantidad(item.cantidad),
                    modo_salida: item.modo_salida,
                    id_inventario_unidad: item.id_inventario_unidad || ""
                };
            }))
        }).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            renderClientePrecio(response);
        }).catch(function (error) {
            document.getElementById("pos_cliente_precio_resultado").innerHTML = "<div class=\"alert alert-warning py-3\">" + escapeHtml(error.message || String(error)) + "</div>";
        });
    }
    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-30
     * Proposito: buscar cliente CRM canonico desde POS sin requerir carrito.
     * Impacto: permite seleccionar cliente antes de cobrar y evita usar telefono como modelo de cliente.
     * Contrato: read-only; no crea cliente ni aplica listas de precio.
     */
    function clienteBuscarCrmDryRun() {
        var identificador = document.getElementById("pos_cliente_identificador").value.trim();
        actualizarAltaClienteEstado(false);
        if (!identificador) {
            document.getElementById("pos_cliente_precio_resultado").innerHTML = "<div class=\"alert alert-warning py-3\">Captura telefono, correo, codigo o nombre para buscar cliente.</div>";
            return;
        }
        document.getElementById("pos_cliente_precio_resultado").innerHTML = "<div class=\"text-muted fs-8 py-3\">Buscando cliente CRM...</div>";
        requestGet("/crm/clientes_buscar_express_dryrun_erp", {q: identificador}).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            renderClienteBusquedaCrm(response);
        }).catch(function (error) {
            document.getElementById("pos_cliente_precio_resultado").innerHTML = "<div class=\"alert alert-warning py-3\">" + escapeHtml(error.message || String(error)) + "</div>";
        });
    }
    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-27
     * Proposito: simular alta rapida de cliente sin crear registros.
     * Impacto: permite revisar duplicados y contrato antes de autorizar escritura real.
     */
    function clienteAltaRapidaDryRun() {
        request("/ventas/pos_cliente_alta_rapida_dryrun_erp", {
            id_almacen: almacenActual(),
            nombre_publico: document.getElementById("pos_cliente_alta_nombre").value,
            identificador: document.getElementById("pos_cliente_identificador").value,
            consentimiento_contacto: document.getElementById("pos_cliente_alta_consentimiento").checked ? 1 : 0
        }).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            renderClienteAltaRapida(response);
        }).catch(function (error) {
            document.getElementById("pos_cliente_precio_resultado").innerHTML = "<div class=\"alert alert-warning py-3\">" + escapeHtml(error.message || String(error)) + "</div>";
        });
    }
    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-28
     * Proposito: simular precio manual/descuento sin alterar el carrito en navegador.
     * Impacto: prepara autorizaciones comerciales con motivo y snapshot backend.
     */
    function excepcionComercialDryRun() {
        if (!carrito.length) {
            document.getElementById("pos_cliente_precio_resultado").innerHTML = "<div class=\"alert alert-warning py-3\">Agrega partidas al carrito antes de validar una excepcion comercial.</div>";
            return;
        }
        request("/ventas/pos_excepcion_comercial_dryrun_erp", payloadExcepcionComercial()).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            renderExcepcionComercial(response);
        }).catch(function (error) {
            document.getElementById("pos_cliente_precio_resultado").innerHTML = "<div class=\"alert alert-warning py-3\">" + escapeHtml(error.message || String(error)) + "</div>";
        });
    }
    function payloadExcepcionComercial() {
        var tipo = document.getElementById("pos_excepcion_tipo").value || "precio_manual";
        var idSkuObjetivo = document.getElementById("pos_excepcion_sku_objetivo").value || ((carrito[0] || {}).id_sku || "");
        return {
            id_almacen: almacenActual(),
            canal: document.getElementById("pos_canal").value || "pos",
            id_cliente: idClienteCrmActivo(),
            identificador_cliente: document.getElementById("pos_cliente_identificador").value,
            tipo_excepcion: tipo,
            id_sku: tipo === "descuento_general" ? "" : idSkuObjetivo,
            precio_manual: document.getElementById("pos_excepcion_precio").value,
            descuento_monto: document.getElementById("pos_excepcion_descuento_monto").value,
            descuento_porcentaje: document.getElementById("pos_excepcion_descuento_porcentaje").value,
            motivo: document.getElementById("pos_excepcion_motivo").value,
            codigo_autorizacion: document.getElementById("pos_excepcion_autorizacion").value,
            items: JSON.stringify(carrito.map(function (item) {
                return {
                    id_sku: item.id_sku,
                    cantidad: cantidad(item.cantidad),
                    modo_salida: item.modo_salida,
                    id_inventario_unidad: item.id_inventario_unidad || ""
                };
            }))
        };
    }
    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-29
     * Proposito: registrar folio real de excepcion comercial desde POS.
     * Impacto: escribe solo autorizacion comercial; no crea venta, caja ni kardex.
     */
    function excepcionComercialRegistrar() {
        if (!carrito.length) {
            document.getElementById("pos_cliente_precio_resultado").innerHTML = "<div class=\"alert alert-warning py-3\">Agrega partidas al carrito antes de registrar una autorizacion.</div>";
            return;
        }
        confirmarRegistroExcepcion().then(function (confirmado) {
            if (!confirmado) { return; }
            ejecutarRegistroExcepcionComercial();
        });
    }
    function confirmarRegistroExcepcion() {
        var payload = payloadExcepcionComercial();
        var tipo = payload.tipo_excepcion || "precio_manual";
        var mensaje = "Se creara un folio real de autorizacion comercial. No crea venta, no mueve caja y no descuenta inventario.";
        if (window.Swal) {
            return Swal.fire({
                text: mensaje,
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Registrar folio",
                cancelButtonText: "Cancelar"
            }).then(function (resultado) {
                return !!resultado.isConfirmed;
            });
        }
        return Promise.resolve(window.confirm(mensaje + "\n\nTipo: " + tipo));
    }
    function ejecutarRegistroExcepcionComercial() {
        var boton = document.getElementById("pos_excepcion_registrar");
        if (boton && boton.disabled) { return; }
        if (boton) {
            boton.disabled = true;
            boton.setAttribute("data-original-text", boton.innerHTML);
            boton.innerHTML = "<span class=\"spinner-border spinner-border-sm me-2\"></span>Registrando...";
        }
        request("/ventas/pos_excepcion_comercial_registrar_erp", payloadExcepcionComercial()).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            renderExcepcionComercialRegistrada(response);
        }).catch(function (error) {
            document.getElementById("pos_cliente_precio_resultado").innerHTML = "<div class=\"alert alert-warning py-3\">" + escapeHtml(error.message || String(error)) + "</div>";
        }).finally(function () {
            if (boton) {
                boton.disabled = false;
                boton.innerHTML = boton.getAttribute("data-original-text") || "<i class=\"bi bi-file-earmark-lock\"></i> Registrar folio autorizado";
            }
        });
    }
    function renderExcepcionComercialRegistrada(response) {
        var depurar = response.depurar || {};
        var bloqueos = depurar.bloqueos || [];
        var folio = depurar.folio || "";
        var clase = bloqueos.length || response.tipo !== "success" ? "alert-warning" : "alert-success";
        var html = "<div class=\"alert " + clase + " py-3 mb-3\">" +
            "<div class=\"fw-bold mb-1\">" + escapeHtml(response.mensaje || "Autorizacion comercial") + "</div>";
        if (folio) {
            html += "<div class=\"fs-7\">Folio autorizado: <span class=\"fw-bold\">" + escapeHtml(folio) + "</span></div>";
        }
        if (bloqueos.length) {
            html += "<ul class=\"mb-0 mt-2 ps-4\">" + bloqueos.map(function (item) { return "<li>" + escapeHtml(item) + "</li>"; }).join("") + "</ul>";
        } else {
            html += "<div class=\"fs-8 text-muted mt-2\">No crea venta, no mueve caja y no descuenta inventario. Valida el folio contra el carrito antes de cobrar.</div>";
        }
        html += "</div>";
        document.getElementById("pos_cliente_precio_resultado").innerHTML = html;
        if (folio) {
            document.getElementById("pos_excepcion_folio").value = folio;
            var tabNode = document.getElementById("pos_tab_folio_btn");
            if (tabNode && window.bootstrap) {
                bootstrap.Tab.getOrCreateInstance(tabNode).show();
            }
        }
    }
    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-29
     * Proposito: validar un folio de excepcion autorizado contra el carrito y pagos actuales.
     * Impacto: prepara el cobro real con precio/descuento trazable sin escribir venta ni caja.
     */
    function excepcionConsumoDryRun() {
        var folio = document.getElementById("pos_excepcion_folio").value.trim();
        if (!folio) {
            document.getElementById("pos_cliente_precio_resultado").innerHTML = "<div class=\"alert alert-warning py-3\">Captura el folio autorizado de excepcion comercial.</div>";
            return;
        }
        if (!carrito.length) {
            document.getElementById("pos_cliente_precio_resultado").innerHTML = "<div class=\"alert alert-warning py-3\">Agrega partidas al carrito antes de validar el folio.</div>";
            return;
        }
        request("/ventas/pos_excepcion_consumo_dryrun_erp", {
            folio_excepcion: folio,
            id_almacen: almacenActual(),
            id_caja: cajaActual(),
            id_turno_caja: turnoActual(),
            id_usuario: (window.POS_USUARIO_ACTUAL || {}).id_usuario || "",
            canal: document.getElementById("pos_canal").value || "pos",
            id_cliente: idClienteCrmActivo(),
            identificador_cliente: document.getElementById("pos_cliente_identificador").value || identificadorClienteActivo(),
            exigir_pago_completo: exigePagoCompleto(),
            items: JSON.stringify(carrito.map(function (item) {
                return {
                    id_sku: item.id_sku,
                    cantidad: cantidad(item.cantidad),
                    modo_salida: item.modo_salida,
                    id_inventario_unidad: item.id_inventario_unidad || ""
                };
            })),
            pagos: JSON.stringify(pagos)
        }).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            renderExcepcionConsumo(response);
        }).catch(function (error) {
            document.getElementById("pos_cliente_precio_resultado").innerHTML = "<div class=\"alert alert-warning py-3\">" + escapeHtml(error.message || String(error)) + "</div>";
        });
    }
    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-27
     * Proposito: simular movimientos no venta de caja desde POS.
     * Impacto: permite revisar gasto/retiro/vale/reembolso sin escribir BD.
     */
    function cajaMovimientoDryRun() {
        request("/ventas/caja_movimiento_dryrun_erp", {
            id_almacen: almacenActual(),
            id_caja: cajaActual(),
            id_turno_caja: turnoActual(),
            tipo_movimiento: document.getElementById("pos_caja_tipo").value,
            monto: document.getElementById("pos_caja_monto").value,
            motivo: document.getElementById("pos_caja_motivo").value,
            referencia: document.getElementById("pos_caja_referencia").value,
            responsable: document.getElementById("pos_caja_responsable").value,
            observaciones: document.getElementById("pos_caja_observaciones").value
        }).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            renderCajaMovimiento(response);
        }).catch(function (error) {
            document.getElementById("pos_caja_resultado").innerHTML = "<div class=\"alert alert-warning py-3\">" + escapeHtml(error.message || String(error)) + "</div>";
        });
    }
    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-27
     * Proposito: simular corte de caja desde POS sin cerrar turno.
     * Impacto: permite comparar esperado vs contado antes de autorizacion de cierre real.
     */
    function corteTurnoDryRun() {
        request("/ventas/turno_cierre_dryrun_erp", {
            id_almacen: almacenActual(),
            id_caja: cajaActual(),
            id_turno_caja: turnoActual(),
            monto_contado: document.getElementById("pos_corte_monto_contado").value
        }).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            renderCorteTurno(response);
        }).catch(function (error) {
            document.getElementById("pos_corte_resultado").innerHTML = "<div class=\"alert alert-warning py-3\">" + escapeHtml(error.message || String(error)) + "</div>";
        });
    }
    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-27
     * Proposito: consultar bandeja futura de atenciones compartidas.
     * Impacto: muestra bloqueo de esquema pendiente sin escribir BD.
     */
    function atencionesBandejaDryRun() {
        request("/ventas/atenciones_bandeja_dryrun_erp?id_almacen=" + encodeURIComponent(almacenActual())).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            renderAtencionesBandeja(response);
        }).catch(function (error) {
            document.getElementById("pos_atenciones_resultado").innerHTML = "<div class=\"alert alert-warning py-3\">" + escapeHtml(error.message || String(error)) + "</div>";
        });
    }
    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-27
     * Proposito: simular que la cuenta local actual se comparta con caja/vendedores.
     * Impacto: prepara atenciones persistentes sin crear registros ni reservar inventario.
     */
    function atencionPersistenteDryRun() {
        if (!carrito.length) {
            document.getElementById("pos_atenciones_resultado").innerHTML = "<div class=\"alert alert-warning py-3\">Agrega partidas a la cuenta actual antes de simular.</div>";
            return;
        }
        var items = carrito.map(function (item) {
            return {
                id_sku: item.id_sku,
                cantidad: cantidad(item.cantidad),
                precio_unitario: cantidad(item.precio_unitario),
                modo_salida: item.modo_salida,
                id_inventario_unidad: item.id_inventario_unidad || ""
            };
        });
        request("/ventas/atencion_persistente_dryrun_erp", {
            id_almacen: almacenActual(),
            id_caja: cajaActual(),
            id_turno_caja: turnoActual(),
            cliente_nombre_publico: clientePublico(),
            id_cliente: idClienteCrmActivo(),
            identificador_cliente: identificadorClienteActivo(),
            origen: "pos",
            items: JSON.stringify(items)
        }).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            renderAtencionPersistente(response);
        }).catch(function (error) {
            document.getElementById("pos_atenciones_resultado").innerHTML = "<div class=\"alert alert-warning py-3\">" + escapeHtml(error.message || String(error)) + "</div>";
        });
    }
    function payloadVentaPos() {
        var items = carrito.map(function (item) {
            return {
                id_sku: item.id_sku,
                cantidad: cantidad(item.cantidad),
                precio_unitario: cantidad(item.precio_unitario),
                modo_salida: item.modo_salida,
                id_inventario_unidad: item.id_inventario_unidad || ""
            };
        });
        return {
            id_almacen: almacenActual(),
            id_caja: cajaActual(),
            id_turno_caja: turnoActual(),
            canal: document.getElementById("pos_canal").value || "pos",
            tipo_documento: tipoDocumentoActual(),
            cliente_nombre_publico: clientePublico(),
            id_cliente: idClienteCrmActivo(),
            identificador_cliente: identificadorClienteActivo(),
            fecha_entrega_compromiso: document.getElementById("pos_fecha_compromiso").value,
            exigir_pago_completo: exigePagoCompleto(),
            items: JSON.stringify(items),
            pagos: JSON.stringify(pagos),
            folio_excepcion: excepcionActiva && excepcionActiva.folio ? excepcionActiva.folio : ""
        };
    }
    function enviarValidacion(url, renderer) {
        request(url, payloadVentaPos()).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            renderer(response);
        }).catch(mostrarError);
    }
    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-29
     * Proposito: confirmar cobro POS real desde la UI usando el contrato backend.
     * Impacto: escribe venta/caja/inventario solo cuando el operador confirma y el backend revalida todo.
     */
    function cobrarReal() {
        if (!turnoActual()) {
            document.getElementById("pos_validacion").innerHTML = "<div class=\"alert alert-warning py-3 mb-0\">Abre turno de caja antes de cobrar.</div>";
            return;
        }
        if (!carrito.length) {
            document.getElementById("pos_validacion").innerHTML = "<div class=\"alert alert-warning py-3 mb-0\">Agrega partidas antes de cobrar.</div>";
            return;
        }
        if (!pagos.length) {
            document.getElementById("pos_validacion").innerHTML = "<div class=\"alert alert-warning py-3 mb-0\">Agrega al menos un pago antes de cobrar.</div>";
            return;
        }
        var pendienteAntesSaldo = totalCobroActual();
        var saldoCrmExcede = false;
        pagos.forEach(function (pago) {
            var monto = cantidad(pago.monto);
            if (esPagoSaldoCrmUi(pago) && monto > pendienteAntesSaldo + 0.0001) {
                saldoCrmExcede = true;
            }
            pendienteAntesSaldo = Math.max(0, pendienteAntesSaldo - monto);
        });
        if (saldoCrmExcede) {
            document.getElementById("pos_validacion").innerHTML = "<div class=\"alert alert-warning py-3 mb-0\">El saldo cliente no puede generar cambio. Ajusta el monto del pago con saldo CRM.</div>";
            return;
        }
        var pagoCaja = pagos.reduce(function (suma, item) { return suma + (esPagoSaldoCrmUi(item) ? 0 : cantidad(item.monto)); }, 0);
        var pagoSaldo = pagos.reduce(function (suma, item) { return suma + (esPagoSaldoCrmUi(item) ? cantidad(item.monto) : 0); }, 0);
        var mensaje = "Se confirmara una venta real por " + dinero(totalCobroActual()) + ". Caja recibira " + dinero(pagoCaja) + (pagoSaldo > 0 ? " y saldo cliente cubrira " + dinero(pagoSaldo) + " sin entrar a caja." : ".");
        var confirmar = window.Swal
            ? Swal.fire({text: mensaje, icon: "warning", showCancelButton: true, confirmButtonText: "Cobrar", cancelButtonText: "Cancelar"}).then(function (r) { return !!r.isConfirmed; })
            : Promise.resolve(window.confirm(mensaje));
        confirmar.then(function (ok) {
            if (!ok) { return; }
            ejecutarCobroReal();
        });
    }
    function ejecutarCobroReal() {
        var boton = document.getElementById("pos_cobrar_real");
        if (boton && boton.disabled) { return; }
        if (boton) {
            boton.disabled = true;
            boton.setAttribute("data-cobrando", "1");
            boton.setAttribute("data-original-text", boton.innerHTML);
            boton.innerHTML = "<span class=\"spinner-border spinner-border-sm me-2\"></span>Cobrando...";
        }
        request("/ventas/pos_confirmar_erp", payloadVentaPos()).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            renderCobroReal(response);
        }).catch(mostrarError).finally(function () {
            if (boton) {
                boton.removeAttribute("data-cobrando");
                boton.disabled = false;
                boton.innerHTML = boton.getAttribute("data-original-text") || "<i class=\"bi bi-cash-coin\"></i> Cobrar";
                actualizarEstadoCobro();
            }
        });
    }
    function renderCobroReal(response) {
        var depurar = response.depurar || {};
        var bloqueos = depurar.bloqueos || [];
        if (bloqueos.length || response.tipo !== "success") {
            document.getElementById("pos_validacion").innerHTML = "<div class=\"alert alert-warning py-3 mb-0\"><div class=\"fw-bold mb-1\">" + escapeHtml(response.mensaje || "Cobro bloqueado") + "</div>" +
                "<ul class=\"mb-0 ps-4\">" + bloqueos.map(function (item) { return "<li>" + escapeHtml(item) + "</li>"; }).join("") + "</ul></div>";
            return;
        }
        var cliente = depurar.cliente || {};
        var html = "<div class=\"alert alert-success py-3 mb-0\">" +
            "<div class=\"fw-bold mb-1\">" + escapeHtml(response.mensaje || "Venta POS confirmada") + "</div>" +
            "<div class=\"fs-7\">Folio: <span class=\"fw-bold\">" + escapeHtml(depurar.folio || "") + "</span>" +
            " | Cliente: " + escapeHtml(cliente.nombre_publico || "Publico general") +
            " | Total: " + dinero(((depurar.totales || {}).total) || 0) + "</div>" +
            "<div class=\"fs-8 text-muted mt-1\">Caja, kardex, garantias y trazabilidad registrados por backend.</div>" +
            "<div class=\"d-flex flex-wrap gap-2 mt-3\">" +
                "<button class=\"btn btn-sm btn-light-primary\" type=\"button\" data-pos-ticket-real=\"" + escapeHtml(depurar.folio || "") + "\"><i class=\"bi bi-receipt\"></i> Ver ticket</button>" +
                "<a class=\"btn btn-sm btn-light\" href=\"/ventas/venta_detalle?folio=" + encodeURIComponent(depurar.folio || "") + "\"><i class=\"bi bi-search\"></i> Ver venta</a>" +
                "<a class=\"btn btn-sm btn-light-warning\" href=\"/ventas/caja_turnos\"><i class=\"bi bi-safe\"></i> Caja/corte</a>" +
                "<a class=\"btn btn-sm btn-light-danger\" href=\"/ventas/devoluciones?folio=" + encodeURIComponent(depurar.folio || "") + "\"><i class=\"bi bi-arrow-counterclockwise\"></i> Simular devolucion</a>" +
            "</div>" +
            "</div>";
        document.getElementById("pos_validacion").innerHTML = html;
        carrito = [];
        pagos = [];
        excepcionActiva = null;
        var cuenta = obtenerCuentaActiva();
        if (cuenta) {
            cuenta.carrito = [];
            cuenta.pagos = [];
            cuenta.excepcion_comercial = null;
        }
        guardarCuentas();
        renderCarrito();
        renderPagos();
        renderCuentas();
        renderExcepcionActiva();
    }
    function abrirTicketVentaReal(folio) {
        if (!folio) { return; }
        document.getElementById("pos_ticket_texto").textContent = "Consultando ticket...";
        bootstrap.Modal.getOrCreateInstance(document.getElementById("pos_ticket_modal")).show();
        requestGet("/ventas/ticket_venta_readonly_erp", {folio: folio}).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            var depurar = response.depurar || {};
            document.getElementById("pos_ticket_texto").textContent = depurar.ticket_texto || "";
        }).catch(function (error) {
            document.getElementById("pos_ticket_texto").textContent = error.message || String(error);
        });
    }
    function renderValidacion(response) {
        var depurar = response.depurar || {};
        var bloqueos = depurar.bloqueos || [];
        var clase = bloqueos.length ? "alert-warning" : "alert-success";
        var html = "<div class=\"alert " + clase + " py-3 mb-0\"><div class=\"fw-bold mb-1\">" + escapeHtml(response.mensaje || "Prevalidacion") + "</div>";
        if (bloqueos.length) {
            html += "<ul class=\"mb-0 ps-4\">" + bloqueos.map(function (item) { return "<li>" + escapeHtml(item) + "</li>"; }).join("") + "</ul>";
        } else {
            html += "<div class=\"fs-7\">Inventario suficiente. Si el pago cubre el total y el turno sigue abierto, puedes cobrar; el backend registrara caja, kardex, garantia y trazabilidad.</div>";
        }
        html += renderPlanSalida(depurar.partidas || []);
        html += "</div>";
        document.getElementById("pos_validacion").innerHTML = html;
        if (depurar.totales) {
            document.getElementById("pos_subtotal").textContent = dinero(depurar.totales.subtotal || 0);
            document.getElementById("pos_total").textContent = dinero(depurar.totales.total_estimado || 0);
            document.getElementById("pos_pagado").textContent = dinero(depurar.totales.pagado_total || 0);
            document.getElementById("pos_saldo").textContent = dinero(depurar.totales.saldo_total || 0);
            document.getElementById("pos_cambio").textContent = dinero(depurar.totales.cambio || 0);
        }
    }
    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-26
     * Proposito: mostrar plan read-only de salida de inventario por partida.
     * Impacto: da evidencia de lote/ubicacion/unidad sin mover inventario.
     */
    function renderPlanSalida(partidas) {
        var filas = [];
        partidas.forEach(function (partida) {
            var plan = partida.plan_salida_inventario || {};
            (plan.asignaciones || []).forEach(function (asignacion) {
                filas.push("<tr><td>" + escapeHtml(partida.sku || "") + "</td><td>" + escapeHtml(asignacion.tipo || "") + "</td><td>" + escapeHtml(asignacion.id_inventario_unidad || "-") + "</td><td>" + escapeHtml(asignacion.lote || "-") + "</td><td>" + escapeHtml(asignacion.ubicacion || "-") + "</td><td class=\"text-end\">" + numero(asignacion.cantidad_base || 0) + "</td></tr>");
            });
            if (Number(plan.faltante || 0) > 0) {
                filas.push("<tr><td>" + escapeHtml(partida.sku || "") + "</td><td colspan=\"4\" class=\"text-danger\">Faltante</td><td class=\"text-end text-danger\">" + numero(plan.faltante) + "</td></tr>");
            }
        });
        if (!filas.length) {
            return "";
        }
        return "<div class=\"table-responsive mt-3\"><table class=\"table table-sm align-middle mb-0\"><thead><tr class=\"text-muted fs-8 text-uppercase\"><th>SKU</th><th>Salida</th><th>Unidad</th><th>Lote</th><th>Ubicacion</th><th class=\"text-end\">Cant.</th></tr></thead><tbody>" + filas.join("") + "</tbody></table></div>";
    }
    function renderDryRun(response) {
        var depurar = response.depurar || {};
        var bloqueos = depurar.bloqueos || [];
        var contrato = depurar.contrato_confirmacion || {};
        var prevalidacion = depurar.prevalidacion || {};
        var totales = (((prevalidacion || {}).depurar || {}).totales) || {};
        var html = "<div class=\"alert alert-warning py-3 mb-0\"><div class=\"fw-bold mb-1\">" + escapeHtml(response.mensaje || "Dry-run") + "</div>";
        if (bloqueos.length) {
            html += "<ul class=\"mb-2 ps-4\">" + bloqueos.map(function (item) { return "<li>" + escapeHtml(item) + "</li>"; }).join("") + "</ul>";
        }
        html += "<div class=\"fs-8 text-muted\">Contrato: almacen " + (contrato.requiere_id_almacen ? "OK" : "-") +
            " | caja " + (contrato.requiere_id_caja ? "OK" : "-") +
            " | turno " + (contrato.requiere_id_turno_caja ? "OK" : "-") +
            " | folio " + (contrato.requiere_folio_erp ? "OK" : "-") +
            " | kardex " + (contrato.requiere_kardex ? "OK" : "-") + "</div></div>";
        document.getElementById("pos_validacion").innerHTML = html;
        if (totales) {
            document.getElementById("pos_subtotal").textContent = dinero(totales.subtotal || 0);
            document.getElementById("pos_total").textContent = dinero(totales.total_estimado || 0);
            document.getElementById("pos_pagado").textContent = dinero(totales.pagado_total || 0);
            document.getElementById("pos_saldo").textContent = dinero(totales.saldo_total || 0);
            document.getElementById("pos_cambio").textContent = dinero(totales.cambio || 0);
        }
    }
    function renderPedidoDryRun(response) {
        var depurar = response.depurar || {};
        var bloqueos = depurar.bloqueos || [];
        var contrato = depurar.contrato_reserva || {};
        var prevalidacion = depurar.prevalidacion || {};
        var partidas = (((prevalidacion || {}).depurar || {}).partidas) || [];
        var html = "<div class=\"alert alert-warning py-3 mb-0\"><div class=\"fw-bold mb-1\">" + escapeHtml(response.mensaje || "Dry-run pedido") + "</div>";
        html += "<div class=\"fs-8 mb-2\">Tipo: " + escapeHtml(depurar.tipo_documento || "") + " | Cliente: " + escapeHtml(depurar.cliente_nombre_publico || "-") + " | Compromiso: " + escapeHtml(depurar.fecha_entrega_compromiso || "-") + "</div>";
        if (bloqueos.length) {
            html += "<ul class=\"mb-2 ps-4\">" + bloqueos.map(function (item) { return "<li>" + escapeHtml(item) + "</li>"; }).join("") + "</ul>";
        }
        html += "<div class=\"fs-8 text-muted\">Reserva: folio " + (contrato.requiere_folio_pedido ? "OK" : "-") +
            " | inventario " + (contrato.requiere_reserva_inventario ? "OK" : "-") +
            " | vencimiento " + (contrato.requiere_vencimiento_reserva ? "OK" : "-") +
            " | kardex al entregar " + (contrato.requiere_trazabilidad_kardex_al_entregar ? "OK" : "-") + "</div>";
        html += renderPlanSalida(partidas);
        html += "</div>";
        document.getElementById("pos_validacion").innerHTML = html;
    }
    function renderTicketPreview(response) {
        var depurar = response.depurar || {};
        document.getElementById("pos_ticket_texto").textContent = depurar.ticket_texto || "";
        document.getElementById("pos_validacion").innerHTML = "<div class=\"alert " + ((depurar.bloqueos || []).length ? "alert-warning" : "alert-success") + " py-3 mb-0\"><div class=\"fw-bold\">" + escapeHtml(response.mensaje || "Ticket preview") + "</div><div class=\"fs-7\">El ticket es temporal y no representa una venta confirmada.</div></div>";
        var modal = bootstrap.Modal.getOrCreateInstance(document.getElementById("pos_ticket_modal"));
        modal.show();
    }
    function renderClientePrecio(response) {
        var depurar = response.depurar || {};
        var cliente = depurar.cliente || {};
        var bloqueos = depurar.bloqueos || [];
        var contrato = depurar.contrato || {};
        var html = "<div class=\"alert " + (bloqueos.length ? "alert-warning" : "alert-success") + " py-3 mb-3\">" +
            "<div class=\"fw-bold mb-1\">" + escapeHtml(response.mensaje || "Cliente/precio") + "</div>" +
            "<div class=\"fs-7\">Cliente: " + escapeHtml(cliente.nombre_publico || cliente.identificador || "Publico general") +
            " | Estatus: " + escapeHtml(cliente.estatus || "-") +
            " | Total resuelto: " + dinero((depurar.totales || {}).total || 0) + "</div>";
        if (cliente.requiere_alta_rapida) {
            html += "<div class=\"fs-7 mt-2 text-muted\">Este identificador podria convertirse en alta rapida cuando se autorice el modulo de clientes.</div>";
        }
        if (bloqueos.length) {
            html += "<ul class=\"mb-0 mt-2 ps-4\">" + bloqueos.map(function (item) { return "<li>" + escapeHtml(item) + "</li>"; }).join("") + "</ul>";
        }
        html += "</div>";
        html += "<div class=\"table-responsive mb-3\"><table class=\"table table-sm align-middle mb-0\"><thead><tr class=\"text-muted fs-8 text-uppercase\"><th>SKU</th><th>Regla</th><th>Lista</th><th class=\"text-end\">Base</th><th class=\"text-end\">Aplicado</th><th class=\"text-end\">Importe</th></tr></thead><tbody>";
        html += (depurar.partidas || []).map(function (partida) {
            var bloqueosPartida = partida.bloqueos || [];
            return "<tr>" +
                "<td><div class=\"fw-bold\">" + escapeHtml(partida.sku || partida.id_sku || "") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(partida.descripcion || bloqueosPartida.join(", ")) + "</div></td>" +
                "<td>" + escapeHtml(partida.regla_precio_origen || "-") + "</td>" +
                "<td>" + escapeHtml(partida.lista_precio_snapshot || "-") + "</td>" +
                "<td class=\"text-end\">" + dinero(partida.precio_base || 0) + "</td>" +
                "<td class=\"text-end fw-bold\">" + dinero(partida.precio_aplicado || 0) + "</td>" +
                "<td class=\"text-end fw-bold\">" + dinero(partida.importe || 0) + "</td>" +
            "</tr>";
        }).join("") || "<tr><td colspan=\"6\" class=\"text-muted text-center py-4\">Sin partidas.</td></tr>";
        html += "</tbody></table></div>";
        html += "<div class=\"fs-8 text-muted\">Contrato: backend precios " + (contrato.backend_resuelve_precio ? "OK" : "-") +
            " | snapshot venta " + (contrato.venta_guarda_id_lista_precio ? "OK" : "-") +
            " | JS sin descuentos " + (contrato.js_no_decide_descuentos ? "OK" : "-") + "</div>";
        document.getElementById("pos_cliente_precio_resultado").innerHTML = html;
        if (cliente && cliente.id_cliente_crm && !bloqueos.length) {
            seleccionarClienteCrm(cliente);
        }
    }
    function renderClienteBusquedaCrm(response) {
        var depurar = response.depurar || {};
        var resultados = depurar.resultados || [];
        var avisos = depurar.avisos || [];
        var identificadorBuscado = normalizarIdentificadorUi(document.getElementById("pos_cliente_identificador").value);
        var exactos = resultados.filter(function (item) {
            return String(item.estatus || "") === "activo" && normalizarIdentificadorUi(item.valor || item.codigo_cliente || "") === identificadorBuscado;
        });
        clientesCrmSeleccionables = {};
        var html = "<div class=\"alert " + (resultados.length ? "alert-success" : "alert-warning") + " py-3 mb-3\">" +
            "<div class=\"fw-bold\">" + escapeHtml(response.mensaje || "Busqueda CRM") + "</div>" +
            "<div class=\"fs-8 text-muted\">Read-only: no crea cliente, no cambia precios y no escribe BD.</div>";
        if (avisos.length) {
            html += "<ul class=\"mb-0 mt-2 ps-4 text-muted\">" + avisos.map(function (item) { return "<li>" + escapeHtml(item) + "</li>"; }).join("") + "</ul>";
        }
        html += "</div>";
        if (!resultados.length) {
            document.getElementById("pos_cliente_precio_resultado").innerHTML = html + "<div class=\"text-muted fs-7 mb-3\">No hay coincidencias. Puedes validar alta express; crear el cliente real requiere autorizacion con respaldo.</div>";
            return;
        }
        if (exactos.length === 1) {
            seleccionarClienteCrm(exactos[0]);
            html += "<div class=\"alert alert-success py-3 mb-3\"><div class=\"fw-bold\">Cliente seleccionado automaticamente</div>" +
                "<div class=\"fs-8\">" + escapeHtml(exactos[0].nombre_publico || "") + " | " + escapeHtml(exactos[0].codigo_cliente || "") + " | " + escapeHtml(exactos[0].valor || "") + "</div></div>";
        }
        html += "<div class=\"table-responsive mb-3\"><table class=\"table table-sm align-middle mb-0\"><thead><tr class=\"text-muted fs-8 text-uppercase\"><th>Cliente</th><th>Codigo</th><th>Identificador</th><th>Estatus</th><th></th></tr></thead><tbody>";
        html += resultados.map(function (item, index) {
            var llave = "busqueda_crm_" + index;
            var seleccionado = exactos.length === 1 && String(item.id_cliente_crm || "") === String(exactos[0].id_cliente_crm || "");
            clientesCrmSeleccionables[llave] = item;
            return "<tr>" +
                "<td><div class=\"fw-bold\">" + escapeHtml(item.nombre_publico || "-") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.calidad_datos || "-") + "</div></td>" +
                "<td>" + escapeHtml(item.codigo_cliente || "-") + "</td>" +
                "<td>" + escapeHtml(item.valor || "-") + "</td>" +
                "<td>" + escapeHtml(item.estatus || "-") + "</td>" +
                "<td class=\"text-end\"><button class=\"btn btn-sm " + (seleccionado ? "btn-success" : "btn-light-primary") + " pos-cliente-crm-seleccionar\" type=\"button\" data-cliente-key=\"" + escapeHtml(llave) + "\"" + (seleccionado ? " disabled" : "") + ">" + (seleccionado ? "Seleccionado" : "Usar") + "</button></td>" +
            "</tr>";
        }).join("");
        html += "</tbody></table></div>";
        document.getElementById("pos_cliente_precio_resultado").innerHTML = html;
        Array.prototype.slice.call(document.querySelectorAll(".pos-cliente-crm-seleccionar")).forEach(function (boton) {
            boton.addEventListener("click", function () {
                var cliente = clientesCrmSeleccionables[boton.getAttribute("data-cliente-key") || ""];
                if (cliente) {
                    seleccionarClienteCrm(cliente);
                    boton.disabled = true;
                    boton.textContent = "Seleccionado";
                    boton.classList.remove("btn-light-primary");
                    boton.classList.add("btn-success");
                    document.getElementById("pos_cliente_precio_resultado").insertAdjacentHTML("afterbegin", "<div class=\"alert alert-success py-2 mb-3\">Cliente CRM seleccionado para esta cuenta.</div>");
                }
            });
        });
    }
    function renderClienteAltaRapida(response) {
        var depurar = response.depurar || {};
        var bloqueos = depurar.bloqueos || [];
        var avisos = depurar.avisos || [];
        var cliente = depurar.cliente_propuesto || {};
        var identificador = depurar.identificador_propuesto || {};
        var coincidencias = depurar.coincidencias || [];
        var html = "<div class=\"alert " + (bloqueos.length ? "alert-warning" : "alert-success") + " py-3 mb-3\">" +
            "<div class=\"fw-bold mb-1\">" + escapeHtml(response.mensaje || "Alta rapida") + "</div>" +
            "<div class=\"fs-7\">Codigo sugerido: " + escapeHtml(cliente.codigo_cliente || "-") +
            " | Nombre: " + escapeHtml(cliente.nombre_publico || "-") +
            " | Identificador: " + escapeHtml(identificador.valor || "-") + "</div>";
        if (bloqueos.length) {
            html += "<ul class=\"mb-0 mt-2 ps-4\">" + bloqueos.map(function (item) { return "<li>" + escapeHtml(item) + "</li>"; }).join("") + "</ul>";
        }
        if (avisos.length) {
            html += "<ul class=\"mb-0 mt-2 ps-4 text-muted\">" + avisos.map(function (item) { return "<li>" + escapeHtml(item) + "</li>"; }).join("") + "</ul>";
        }
        html += "</div>";
        if (coincidencias.length) {
            clientesCrmSeleccionables = {};
            html += "<div class=\"table-responsive mb-3\"><table class=\"table table-sm align-middle mb-0\"><thead><tr class=\"text-muted fs-8 text-uppercase\"><th>Cliente existente</th><th>Codigo</th><th>Identificador</th><th>Estatus</th></tr></thead><tbody>" +
                coincidencias.map(function (item, index) {
                    var llave = "crm_" + index;
                    clientesCrmSeleccionables[llave] = item;
                    return "<tr><td>" + escapeHtml(item.nombre_publico || "") + "</td><td>" + escapeHtml(item.codigo_cliente || "") + "</td><td>" + escapeHtml(item.valor || "") + "</td><td><button class=\"btn btn-sm btn-light-primary pos-cliente-crm-seleccionar\" type=\"button\" data-cliente-key=\"" + escapeHtml(llave) + "\">Usar</button></td></tr>";
                }).join("") + "</tbody></table></div>";
        }
        html += "<div class=\"fs-8 text-muted\">Dry-run: no crea cliente. Si no hay bloqueos, requiere autorizacion con respaldo para aplicar alta rapida.</div>";
        document.getElementById("pos_cliente_precio_resultado").innerHTML = html;
        Array.prototype.slice.call(document.querySelectorAll(".pos-cliente-crm-seleccionar")).forEach(function (boton) {
            boton.addEventListener("click", function () {
                var cliente = clientesCrmSeleccionables[boton.getAttribute("data-cliente-key") || ""];
                if (cliente) {
                    seleccionarClienteCrm(cliente);
                    boton.disabled = true;
                    boton.textContent = "Seleccionado";
                    boton.classList.remove("btn-light-primary");
                    boton.classList.add("btn-success");
                    document.getElementById("pos_cliente_precio_resultado").insertAdjacentHTML("afterbegin", "<div class=\"alert alert-success py-2 mb-3\">Cliente CRM seleccionado para esta cuenta.</div>");
                }
            });
        });
    }
    function renderExcepcionComercial(response) {
        var depurar = response.depurar || {};
        var bloqueos = depurar.bloqueos || [];
        var avisos = depurar.avisos || [];
        var contrato = depurar.contrato || {};
        var totales = depurar.totales || {};
        var html = "<div class=\"alert " + (bloqueos.length ? "alert-warning" : "alert-success") + " py-3 mb-3\">" +
            "<div class=\"fw-bold mb-1\">" + escapeHtml(response.mensaje || "Excepcion comercial") + "</div>" +
            "<div class=\"fs-7\">Tipo: " + escapeHtml(depurar.tipo_excepcion || "-") +
            " | Subtotal lista: " + dinero(totales.subtotal_lista || 0) +
            " | Descuento estimado: " + dinero(totales.descuento_total_estimado || 0) +
            " | Total estimado: " + dinero(totales.total_estimado || 0) + "</div>";
        if (bloqueos.length) {
            html += "<ul class=\"mb-0 mt-2 ps-4\">" + bloqueos.map(function (item) { return "<li>" + escapeHtml(item) + "</li>"; }).join("") + "</ul>";
        }
        if (avisos.length) {
            html += "<ul class=\"mb-0 mt-2 ps-4 text-muted\">" + avisos.map(function (item) { return "<li>" + escapeHtml(item) + "</li>"; }).join("") + "</ul>";
        }
        html += "</div>";
        html += "<div class=\"table-responsive mb-3\"><table class=\"table table-sm align-middle mb-0\"><thead><tr class=\"text-muted fs-8 text-uppercase\"><th>SKU</th><th>Regla</th><th class=\"text-end\">Lista</th><th class=\"text-end\">Final</th><th class=\"text-end\">Desc.</th><th class=\"text-end\">Importe</th></tr></thead><tbody>";
        html += (depurar.partidas || []).map(function (partida) {
            return "<tr>" +
                "<td><div class=\"fw-bold\">" + escapeHtml(partida.sku || partida.id_sku || "") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(partida.aplica_excepcion ? "Aplica excepcion" : "Sin cambio") + "</div></td>" +
                "<td>" + escapeHtml(partida.regla_precio_origen || "-") + "</td>" +
                "<td class=\"text-end\">" + dinero(partida.precio_lista || 0) + "</td>" +
                "<td class=\"text-end fw-bold\">" + dinero(partida.precio_final_estimado || 0) + "</td>" +
                "<td class=\"text-end\">" + dinero(partida.descuento_estimado || 0) + "</td>" +
                "<td class=\"text-end fw-bold\">" + dinero(partida.importe_final_estimado || 0) + "</td>" +
            "</tr>";
        }).join("") || "<tr><td colspan=\"6\" class=\"text-muted text-center py-4\">Sin partidas.</td></tr>";
        html += "</tbody></table></div>";
        html += "<div class=\"fs-8 text-muted\">Contrato: backend precio " + (contrato.backend_resuelve_precio_base_y_lista ? "OK" : "-") +
            " | JS sin precio final " + (contrato.js_no_decide_precio_final ? "OK" : "-") +
            " | autorizacion venta real " + (contrato.venta_real_bloqueada_sin_permiso_supervisor ? "OK" : "-") + "</div>";
        document.getElementById("pos_cliente_precio_resultado").innerHTML = html;
    }
    function renderExcepcionConsumo(response) {
        var depurar = response.depurar || {};
        var excepcion = depurar.excepcion || {};
        var bloqueos = depurar.bloqueos || [];
        var avisos = depurar.avisos || [];
        var totales = depurar.totales || {};
        var contrato = depurar.contrato_consumo_real || {};
        var html = "<div class=\"alert " + (bloqueos.length ? "alert-warning" : "alert-success") + " py-3 mb-3\">" +
            "<div class=\"fw-bold mb-1\">" + escapeHtml(response.mensaje || "Consumo de excepcion") + "</div>" +
            "<div class=\"fs-7\">Folio: " + escapeHtml(excepcion.folio || "-") +
            " | Estatus: " + escapeHtml(excepcion.estatus || "-") +
            " | Tipo: " + escapeHtml(excepcion.tipo_excepcion || "-") + "</div>" +
            "<div class=\"fs-7 mt-1\">Subtotal: " + dinero(totales.subtotal_original || 0) +
            " | Descuento: " + dinero(totales.descuento_total || 0) +
            " | Total: " + dinero(totales.total_con_excepcion || 0) +
            " | Saldo: " + dinero(totales.saldo_total || 0) + "</div>";
        if (bloqueos.length) {
            html += "<ul class=\"mb-0 mt-2 ps-4\">" + bloqueos.map(function (item) { return "<li>" + escapeHtml(item) + "</li>"; }).join("") + "</ul>";
        }
        if (avisos.length) {
            html += "<ul class=\"mb-0 mt-2 ps-4 text-muted\">" + avisos.map(function (item) { return "<li>" + escapeHtml(item) + "</li>"; }).join("") + "</ul>";
        }
        html += "</div>";
        html += "<div class=\"table-responsive mb-3\"><table class=\"table table-sm align-middle mb-0\"><thead><tr class=\"text-muted fs-8 text-uppercase\"><th>SKU</th><th>Folio</th><th class=\"text-end\">Original</th><th class=\"text-end\">Final</th><th class=\"text-end\">Desc.</th><th class=\"text-end\">Total</th></tr></thead><tbody>";
        html += (depurar.partidas || []).map(function (partida) {
            return "<tr>" +
                "<td><div class=\"fw-bold\">" + escapeHtml(partida.sku || partida.id_sku || "") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(partida.aplica_excepcion_comercial ? "Aplica" : "Sin cambio") + "</div></td>" +
                "<td>" + escapeHtml(partida.folio_excepcion_comercial || "-") + "</td>" +
                "<td class=\"text-end\">" + dinero(partida.precio_unitario_original || partida.precio_unitario || 0) + "</td>" +
                "<td class=\"text-end fw-bold\">" + dinero(partida.precio_unitario_final || partida.precio_unitario || 0) + "</td>" +
                "<td class=\"text-end\">" + dinero(partida.descuento_excepcion || 0) + "</td>" +
                "<td class=\"text-end fw-bold\">" + dinero(partida.total_final || partida.subtotal || 0) + "</td>" +
            "</tr>";
        }).join("") || "<tr><td colspan=\"6\" class=\"text-muted text-center py-4\">Sin partidas.</td></tr>";
        html += "</tbody></table></div>";
        html += "<div class=\"fs-8 text-muted\">Contrato: bloqueo folio " + (contrato.bloquear_excepcion_for_update ? "OK" : "-") +
            " | no reutilizar " + (contrato.validar_no_consumida ? "OK" : "-") +
            " | total backend " + (contrato.recalcular_totales_en_backend ? "OK" : "-") + "</div>";
        document.getElementById("pos_cliente_precio_resultado").innerHTML = html;
        if (!bloqueos.length) {
            excepcionActiva = {
                folio: excepcion.folio || "",
                estatus: excepcion.estatus || "",
                tipo_excepcion: excepcion.tipo_excepcion || "",
                cliente: {
                    id_cliente_crm: excepcion.id_cliente_crm || "",
                    codigo_cliente: excepcion.cliente_codigo_snapshot || "",
                    nombre_publico: excepcion.cliente_nombre_snapshot || "",
                    identificador: excepcion.cliente_identificador_snapshot || "",
                    origen: excepcion.cliente_origen_snapshot || ""
                },
                totales: {
                    subtotal_original: totales.subtotal_original || 0,
                    descuento_total: totales.descuento_total || 0,
                    total_con_excepcion: totales.total_con_excepcion || 0,
                    pagado_total: totales.pagado_total || 0,
                    saldo_total: totales.saldo_total || 0,
                    cambio: totales.cambio || 0
                }
            };
            document.getElementById("pos_subtotal").textContent = dinero(totales.subtotal_original || 0);
            document.getElementById("pos_total").textContent = dinero(totales.total_con_excepcion || 0);
            document.getElementById("pos_pagado").textContent = dinero(totales.pagado_total || 0);
            document.getElementById("pos_saldo").textContent = dinero(totales.saldo_total || 0);
            document.getElementById("pos_cambio").textContent = dinero(totales.cambio || 0);
            renderExcepcionActiva();
            guardarCuentas();
            renderCuentas();
        }
    }
    function renderCajaMovimiento(response) {
        var depurar = response.depurar || {};
        var movimiento = depurar.movimiento || {};
        var bloqueos = depurar.bloqueos || [];
        var avisos = depurar.avisos || [];
        var clase = bloqueos.length ? "alert-warning" : "alert-success";
        var html = "<div class=\"alert " + clase + " py-3 mb-3\">" +
            "<div class=\"fw-bold mb-1\">" + escapeHtml(response.mensaje || "Caja") + "</div>" +
            "<div class=\"fs-7\">Tipo: " + escapeHtml(movimiento.nombre || depurar.tipo_movimiento || "-") +
            " | Monto: " + dinero(movimiento.monto || 0) +
            " | Impacto esperado: " + dinero(movimiento.impacto_esperado || 0) + "</div>";
        if (bloqueos.length) {
            html += "<ul class=\"mb-0 mt-2 ps-4\">" + bloqueos.map(function (item) { return "<li>" + escapeHtml(item) + "</li>"; }).join("") + "</ul>";
        }
        if (avisos.length) {
            html += "<ul class=\"mb-0 mt-2 ps-4 text-muted\">" + avisos.map(function (item) { return "<li>" + escapeHtml(item) + "</li>"; }).join("") + "</ul>";
        }
        html += "</div>";
        html += "<div class=\"table-responsive\"><table class=\"table table-sm align-middle mb-0\"><tbody>" +
            "<tr><th class=\"text-muted\">Caja</th><td>" + escapeHtml(depurar.id_caja || "-") + "</td><th class=\"text-muted\">Turno</th><td>" + escapeHtml(depurar.id_turno_caja || "-") + "</td></tr>" +
            "<tr><th class=\"text-muted\">Tipo caja</th><td>" + escapeHtml(movimiento.tipo_caja || "-") + "</td><th class=\"text-muted\">Motivo caja</th><td>" + escapeHtml(movimiento.motivo_caja || "-") + "</td></tr>" +
            "<tr><th class=\"text-muted\">Referencia</th><td>" + escapeHtml(movimiento.referencia || "-") + "</td><th class=\"text-muted\">Responsable</th><td>" + escapeHtml(movimiento.responsable || "-") + "</td></tr>" +
        "</tbody></table></div>";
        document.getElementById("pos_caja_resultado").innerHTML = html;
    }
    function consultarEvidenciasCaja() {
        var contenedor = document.getElementById("pos_evidencias_resultado");
        if (!contenedor) { return; }
        contenedor.innerHTML = "<div class=\"text-muted fs-8 py-3\">Consultando evidencias...</div>";
        requestGet("/ventas/caja_evidencias_pendientes_erp", {
            id_almacen: almacenActual(),
            id_caja: cajaActual(),
            id_turno_caja: turnoActual(),
            evidencia_estado: document.getElementById("pos_evidencia_estado").value || "pendiente"
        }).then(renderEvidenciasCaja).catch(function (error) {
            contenedor.innerHTML = "<div class=\"alert alert-warning py-3\">" + escapeHtml(error.message || String(error)) + "</div>";
        });
    }
    function renderEvidenciasCaja(response) {
        var depurar = response.depurar || {};
        var resumen = depurar.resumen || response.resumen || {};
        var filas = depurar.pendientes || response.pendientes || [];
        var contenedor = document.getElementById("pos_evidencias_resultado");
        var total = Number(resumen.total_registros || 0);
        var html = "<div class=\"alert " + (total ? "alert-info" : "alert-light") + " py-3 mb-3\">" +
            "<div class=\"d-flex flex-wrap justify-content-between gap-2\">" +
                "<div><div class=\"fw-bold\">" + escapeHtml(response.mensaje || "Evidencias de caja") + "</div>" +
                "<div class=\"fs-8 text-muted\">Registros: " + escapeHtml(total) + " | Monto: " + dinero(resumen.monto_total || 0) + "</div></div>" +
                "<div class=\"text-muted fs-8\">Turno " + escapeHtml(turnoActual() || "-") + " | Caja " + escapeHtml(cajaActual() || "-") + "</div>" +
            "</div></div>";
        if (!filas.length) {
            contenedor.innerHTML = html + "<div class=\"pos-empty d-flex align-items-center justify-content-center text-center text-muted\"><div><i class=\"bi bi-folder-check fs-1 d-block mb-3\"></i><div class=\"fw-semibold\">Sin evidencias en este filtro</div></div></div>";
            return;
        }
        html += filas.map(function (item) {
            var estado = item.evidencia_estado || "pendiente";
            var badge = estado === "aprobada" ? "badge-light-success" : (estado === "rechazada" ? "badge-light-danger" : (estado === "recibida" ? "badge-light-info" : "badge-light-warning"));
            return "<div class=\"pos-evidencia-row mb-2\" data-pos-evidencia-movimiento=\"" + escapeHtml(item.id_movimiento_caja || "") + "\">" +
                "<div class=\"d-flex flex-wrap justify-content-between gap-3 align-items-start\">" +
                    "<div>" +
                        "<div class=\"fw-bold\">" + escapeHtml(item.referencia || item.folio_devolucion || item.folio_venta || "Movimiento " + (item.id_movimiento_caja || "")) + "</div>" +
                        "<div class=\"text-muted fs-8\">" + escapeHtml(item.tipo || "-") + " / " + escapeHtml(item.categoria || "-") +
                        " | " + escapeHtml(item.nombre_comercial || "") + " | " + escapeHtml(item.turno_folio || "") + "</div>" +
                    "</div>" +
                    "<div class=\"text-end\">" +
                        "<span class=\"badge " + badge + " mb-2\">" + escapeHtml(estado) + "</span>" +
                        "<div class=\"fw-bold\">" + dinero(item.monto || 0) + "</div>" +
                    "</div>" +
                "</div>" +
                "<div class=\"d-flex flex-wrap justify-content-between gap-2 align-items-center mt-3\">" +
                    "<div class=\"fs-8 text-muted\">Venta " + escapeHtml(item.folio_venta || "-") + " | Devolucion " + escapeHtml(item.folio_devolucion || "-") + "</div>" +
                    "<button class=\"btn btn-sm btn-light-primary\" type=\"button\" data-pos-evidencia-detalle=\"" + escapeHtml(item.id_movimiento_caja || "") + "\"><i class=\"bi bi-eye\"></i> Detalle</button>" +
                "</div>" +
            "</div>";
        }).join("");
        contenedor.innerHTML = html;
    }
    function consultarDevolucionesFisicas() {
        var contenedor = document.getElementById("pos_devoluciones_fisicas_resultado");
        if (!contenedor) { return; }
        contenedor.innerHTML = "<div class=\"text-muted fs-8 py-3\">Consultando devoluciones...</div>";
        requestGet("/ventas/devoluciones_inventario_pendientes_erp", {
            id_almacen: almacenActual(),
            decision_inventario: document.getElementById("pos_devolucion_fisica_decision").value || "pendientes",
            limite: 30
        }).then(renderDevolucionesFisicas).catch(function (error) {
            contenedor.innerHTML = "<div class=\"alert alert-warning py-3\">" + escapeHtml(error.message || String(error)) + "</div>";
        });
    }
    function renderDevolucionesFisicas(response) {
        var depurar = response.depurar || {};
        var resumen = depurar.resumen || {};
        var filas = depurar.pendientes || [];
        var contenedor = document.getElementById("pos_devoluciones_fisicas_resultado");
        var total = Number(resumen.total_registros || 0);
        var html = "<div class=\"alert " + (total ? "alert-info" : "alert-light") + " py-3 mb-3\">" +
            "<div class=\"d-flex flex-wrap justify-content-between gap-2\">" +
                "<div><div class=\"fw-bold\">" + escapeHtml(response.mensaje || "Devoluciones fisicas") + "</div>" +
                "<div class=\"fs-8 text-muted\">Partidas: " + escapeHtml(total) + " | Cantidad: " + numero(resumen.cantidad_total || 0) + " | Importe: " + dinero(resumen.importe_total || 0) + "</div></div>" +
                "<div class=\"text-muted fs-8\">Solo lectura; cierre fisico corresponde a Almacen/Inventario</div>" +
            "</div></div>";
        if (!filas.length) {
            contenedor.innerHTML = html + "<div class=\"pos-empty d-flex align-items-center justify-content-center text-center text-muted\"><div><i class=\"bi bi-box-seam fs-1 d-block mb-3\"></i><div class=\"fw-semibold\">Sin devoluciones fisicas en este filtro</div></div></div>";
            return;
        }
        html += filas.map(function (item) {
            var decision = item.decision_inventario || "-";
            var badge = decision === "cuarentena" ? "badge-light-warning" : (decision === "reintegrar" ? "badge-light-success" : (decision === "merma" ? "badge-light-danger" : "badge-light"));
            var inspeccion = item.inspeccion_estado || "pendiente";
            return "<div class=\"pos-evidencia-row mb-2\">" +
                "<div class=\"d-flex flex-wrap justify-content-between gap-3 align-items-start\">" +
                    "<div>" +
                        "<div class=\"fw-bold\">" + escapeHtml(item.folio_devolucion || "-") + "</div>" +
                        "<div class=\"text-muted fs-8\">Venta " + escapeHtml(item.folio_venta || "-") + " | " + escapeHtml(item.sku || "-") + "</div>" +
                        "<div class=\"fs-8 mt-2\">" + escapeHtml(item.descripcion || "Producto sin descripcion") + "</div>" +
                    "</div>" +
                    "<div class=\"text-end\">" +
                        "<span class=\"badge " + badge + " mb-2\">" + escapeHtml(decision) + "</span>" +
                        "<div class=\"fw-bold\">" + numero(item.cantidad_base || 0) + " " + escapeHtml(item.unidad_base || "") + "</div>" +
                        "<div class=\"text-muted fs-8\">" + dinero(item.importe_reembolso || 0) + "</div>" +
                        "<div class=\"text-muted fs-9 mt-1\">Inspeccion: " + escapeHtml(inspeccion) + "</div>" +
                    "</div>" +
                "</div>" +
                "<div class=\"d-flex flex-wrap justify-content-between gap-2 mt-3 text-muted fs-8\">" +
                    "<div>Almacen " + escapeHtml(item.almacen_nombre || item.id_almacen || "-") + " | Caja " + escapeHtml(item.caja_codigo || "-") + "</div>" +
                    "<div>Inspeccion " + escapeHtml(item.folio_inspeccion || item.id_inspeccion_fisica || "pendiente") + " | Movimiento inventario: " + escapeHtml(item.id_movimiento_inventario_devolucion || "pendiente") + "</div>" +
                "</div>" +
            "</div>";
        }).join("");
        contenedor.innerHTML = html;
    }
    function consultarDetalleEvidenciaCaja(idMovimiento) {
        var contenedor = document.getElementById("pos_evidencias_resultado");
        requestGet("/ventas/caja_evidencias_detalle_erp", {
            id_movimiento_caja: idMovimiento,
            limite: 20
        }).then(function (response) {
            var depurar = response.depurar || {};
            var evidencias = depurar.evidencias || [];
            var html = "<div class=\"d-flex justify-content-between align-items-center gap-3 mb-3\">" +
                "<button class=\"btn btn-sm btn-light\" type=\"button\" id=\"pos_evidencias_volver\"><i class=\"bi bi-arrow-left\"></i> Volver</button>" +
                "<div class=\"text-muted fs-8\">Detalle movimiento " + escapeHtml(idMovimiento || "-") + "</div>" +
            "</div>";
            if (!evidencias.length) {
                contenedor.innerHTML = html + "<div class=\"alert alert-warning py-3\">Sin evidencias capturadas para este movimiento.</div>";
            } else {
                html += evidencias.map(function (item) {
                    var acciones = "";
                    var ticketDevolucion = item.folio_devolucion ? "<button class=\"btn btn-sm btn-light-dark\" type=\"button\" data-pos-ticket-devolucion=\"" + escapeHtml(item.folio_devolucion || "") + "\"><i class=\"bi bi-receipt\"></i> Ticket devolucion</button>" : "";
                    var correccion = "";
                    if (item.correccion_folio) {
                        var claseCorreccion = item.correccion_estatus === "resuelta" ? "badge-light-success" : (item.correccion_estatus === "en_revision" ? "badge-light-info" : "badge-light-warning");
                        correccion = "<div class=\"alert alert-light border py-2 px-3 mt-3 mb-0\">" +
                            "<div class=\"d-flex flex-wrap justify-content-between gap-2 align-items-center\">" +
                                "<div><div class=\"fw-semibold fs-8\">Correccion " + escapeHtml(item.correccion_folio || "") + "</div>" +
                                "<div class=\"text-muted fs-9\">" + escapeHtml(item.correccion_tipo || "-") + " | " + escapeHtml(item.correccion_relacion || "-") + "</div></div>" +
                                "<div class=\"text-end\"><span class=\"badge " + claseCorreccion + "\">" + escapeHtml(item.correccion_estatus || "-") + "</span>" +
                                "<div class=\"text-muted fs-9 mt-1\">Decision " + escapeHtml(item.correccion_decision || "-") + "</div></div>" +
                            "</div>" +
                        "</div>";
                    }
                    var accionesCorreccion = "";
                    if (item.estatus === "recibida") {
                        acciones = "<div class=\"d-flex flex-wrap gap-2 mt-3\">" +
                            "<button class=\"btn btn-sm btn-success\" type=\"button\" data-pos-evidencia-revisar=\"aprobada\" data-pos-evidencia-id=\"" + escapeHtml(item.id_evidencia_caja || "") + "\"><i class=\"bi bi-check2-circle\"></i> Aprobar</button>" +
                            "<button class=\"btn btn-sm btn-light-danger\" type=\"button\" data-pos-evidencia-revisar=\"rechazada\" data-pos-evidencia-id=\"" + escapeHtml(item.id_evidencia_caja || "") + "\"><i class=\"bi bi-x-circle\"></i> Rechazar</button>" +
                            ticketDevolucion +
                        "</div>";
                    } else if (ticketDevolucion) {
                        acciones = "<div class=\"d-flex flex-wrap gap-2 mt-3\">" + ticketDevolucion + "</div>";
                    }
                    if (item.estatus === "aprobada") {
                        accionesCorreccion += "<button class=\"btn btn-sm btn-light-warning\" type=\"button\" data-pos-evidencia-correccion-solicitar=\"" + escapeHtml(item.id_evidencia_caja || "") + "\" data-pos-evidencia-movimiento=\"" + escapeHtml(item.id_movimiento_caja || "") + "\"><i class=\"bi bi-pencil-square\"></i> Solicitar correccion</button>";
                    }
                    if (item.correccion_folio && (item.correccion_estatus === "solicitada" || item.correccion_estatus === "en_revision") && !item.correccion_id_evidencia_nueva) {
                        accionesCorreccion += "<button class=\"btn btn-sm btn-light-primary\" type=\"button\" data-pos-evidencia-correccion-adjuntar=\"" + escapeHtml(item.correccion_folio || "") + "\" data-pos-evidencia-movimiento=\"" + escapeHtml(item.id_movimiento_caja || "") + "\"><i class=\"bi bi-file-earmark-plus\"></i> Evidencia correctiva</button>";
                    }
                    if (item.correccion_folio && item.correccion_estatus === "en_revision" && item.estatus === "recibida_correccion") {
                        accionesCorreccion += "<button class=\"btn btn-sm btn-success\" type=\"button\" data-pos-evidencia-correccion-resolver=\"aprobada\" data-pos-evidencia-correccion-folio=\"" + escapeHtml(item.correccion_folio || "") + "\" data-pos-evidencia-movimiento=\"" + escapeHtml(item.id_movimiento_caja || "") + "\"><i class=\"bi bi-check2-circle\"></i> Aprobar correccion</button>" +
                            "<button class=\"btn btn-sm btn-light-danger\" type=\"button\" data-pos-evidencia-correccion-resolver=\"rechazada\" data-pos-evidencia-correccion-folio=\"" + escapeHtml(item.correccion_folio || "") + "\" data-pos-evidencia-movimiento=\"" + escapeHtml(item.id_movimiento_caja || "") + "\"><i class=\"bi bi-x-circle\"></i> Rechazar correccion</button>";
                    }
                    if (accionesCorreccion) {
                        acciones += "<div class=\"d-flex flex-wrap gap-2 mt-3\">" + accionesCorreccion + "</div>";
                    }
                    return "<div class=\"pos-evidencia-row mb-2\">" +
                        "<div class=\"d-flex flex-wrap justify-content-between gap-3\">" +
                            "<div><div class=\"fw-bold\">" + escapeHtml(item.tipo_evidencia || "Evidencia") + "</div>" +
                            "<div class=\"text-muted fs-8\">" + escapeHtml(item.referencia_externa || item.archivo_nombre || "Sin archivo fisico") + "</div></div>" +
                            "<div class=\"text-end\"><span class=\"badge badge-light-primary\">" + escapeHtml(item.estatus || "-") + "</span><div class=\"fw-bold mt-1\">" + dinero(item.monto || 0) + "</div></div>" +
                        "</div>" +
                        "<div class=\"fs-8 mt-3\">" + escapeHtml(item.descripcion || "Sin descripcion") + "</div>" +
                        "<div class=\"text-muted fs-8 mt-2\">Creado por " + escapeHtml(item.creado_por || "-") +
                        " | Revisado por " + escapeHtml(item.revisado_por || "-") +
                        " | Revision " + escapeHtml(item.fecha_revision || "-") + "</div>" +
                        correccion +
                        acciones +
                    "</div>";
                }).join("");
                contenedor.innerHTML = html;
            }
            var volver = document.getElementById("pos_evidencias_volver");
            if (volver) {
                volver.addEventListener("click", consultarEvidenciasCaja);
            }
        }).catch(function (error) {
            contenedor.innerHTML = "<div class=\"alert alert-warning py-3\">" + escapeHtml(error.message || String(error)) + "</div>";
        });
    }
    function revisarEvidenciaCaja(idEvidencia, decision) {
        var contenedor = document.getElementById("pos_evidencias_resultado");
        var ejecutar = function (motivo) {
            request("/ventas/caja_evidencia_revisar_erp", {
                id_evidencia_caja: idEvidencia,
                decision: decision,
                motivo: motivo || ""
            }).then(function (response) {
                var clase = response.error ? "alert-warning" : "alert-success";
                var depurar = response.depurar || {};
                contenedor.insertAdjacentHTML("afterbegin", "<div class=\"alert " + clase + " py-3 mb-3\">" +
                    "<div class=\"fw-bold\">" + escapeHtml(response.mensaje || "Revision de evidencia") + "</div>" +
                    "<div class=\"fs-8\">Decision: " + escapeHtml(depurar.decision || decision) +
                    " | Movimiento: " + escapeHtml(depurar.id_movimiento_caja || "-") + "</div></div>");
                if (!response.error) {
                    consultarDetalleEvidenciaCaja(depurar.id_movimiento_caja || "");
                }
            }).catch(function (error) {
                contenedor.insertAdjacentHTML("afterbegin", "<div class=\"alert alert-warning py-3 mb-3\">" + escapeHtml(error.message || String(error)) + "</div>");
            });
        };
        if (decision === "rechazada") {
            if (window.Swal) {
                Swal.fire({
                    title: "Motivo de rechazo",
                    input: "textarea",
                    inputPlaceholder: "Describe que falta o que debe corregirse",
                    showCancelButton: true,
                    confirmButtonText: "Rechazar",
                    cancelButtonText: "Cancelar",
                    inputValidator: function (value) {
                        return value && value.trim() ? null : "Captura un motivo";
                    }
                }).then(function (result) {
                    if (result.isConfirmed) {
                        ejecutar(result.value || "");
                    }
                });
                return;
            }
            var motivo = window.prompt("Motivo de rechazo");
            if (motivo && motivo.trim()) {
                ejecutar(motivo);
            }
            return;
        }
        if (window.Swal) {
            Swal.fire({
                text: "Se aprobara la evidencia seleccionada.",
                icon: "question",
                showCancelButton: true,
                confirmButtonText: "Aprobar",
                cancelButtonText: "Cancelar"
            }).then(function (result) {
                if (result.isConfirmed) {
                    ejecutar("");
                }
            });
            return;
        }
        if (window.confirm("Aprobar evidencia seleccionada?")) {
            ejecutar("");
        }
    }
    function solicitarCorreccionEvidenciaCaja(idEvidencia, idMovimiento) {
        var ejecutar = function (motivo) {
            request("/ventas/caja_evidencia_correccion_solicitar_erp", {
                id_evidencia_caja: idEvidencia,
                tipo_correccion: "reemplazo_evidencia",
                motivo: motivo || ""
            }).then(function (response) {
                mostrarResultadoEvidenciaCaja(response, "Solicitud de correccion");
                if (!response.error) {
                    consultarDetalleEvidenciaCaja(idMovimiento);
                }
            }).catch(mostrarErrorEvidenciaCaja);
        };
        if (window.Swal) {
            Swal.fire({
                title: "Solicitar correccion",
                input: "textarea",
                inputPlaceholder: "Describe por que se corregira esta evidencia",
                showCancelButton: true,
                confirmButtonText: "Solicitar",
                cancelButtonText: "Cancelar",
                inputValidator: function (value) {
                    return value && value.trim() ? null : "Captura un motivo";
                }
            }).then(function (result) {
                if (result.isConfirmed) {
                    ejecutar(result.value || "");
                }
            });
            return;
        }
        var motivo = window.prompt("Motivo de correccion");
        if (motivo && motivo.trim()) {
            ejecutar(motivo);
        }
    }
    function registrarEvidenciaCorrectivaCaja(folio, idMovimiento) {
        var enviar = function (referencia, descripcion) {
            request("/ventas/caja_evidencia_correccion_evidencia_erp", {
                folio: folio,
                tipo_evidencia: "ticket_firmado_correccion",
                referencia_externa: referencia || "",
                descripcion: descripcion || ""
            }).then(function (response) {
                mostrarResultadoEvidenciaCaja(response, "Evidencia correctiva");
                if (!response.error) {
                    consultarDetalleEvidenciaCaja(idMovimiento);
                }
            }).catch(mostrarErrorEvidenciaCaja);
        };
        if (window.Swal) {
            Swal.fire({
                title: "Evidencia correctiva",
                html: "<input id=\"pos_corr_ref\" class=\"swal2-input\" placeholder=\"Referencia externa\"><textarea id=\"pos_corr_desc\" class=\"swal2-textarea\" placeholder=\"Descripcion\"></textarea>",
                showCancelButton: true,
                confirmButtonText: "Registrar",
                cancelButtonText: "Cancelar",
                preConfirm: function () {
                    var referencia = (document.getElementById("pos_corr_ref").value || "").trim();
                    var descripcion = (document.getElementById("pos_corr_desc").value || "").trim();
                    if (!referencia && !descripcion) {
                        Swal.showValidationMessage("Captura referencia o descripcion");
                        return false;
                    }
                    return {referencia: referencia, descripcion: descripcion};
                }
            }).then(function (result) {
                if (result.isConfirmed && result.value) {
                    enviar(result.value.referencia, result.value.descripcion);
                }
            });
            return;
        }
        var referencia = window.prompt("Referencia externa de evidencia correctiva");
        var descripcion = window.prompt("Descripcion de evidencia correctiva");
        if ((referencia && referencia.trim()) || (descripcion && descripcion.trim())) {
            enviar(referencia || "", descripcion || "");
        }
    }
    function resolverCorreccionEvidenciaCaja(folio, decision, idMovimiento) {
        var ejecutar = function (motivo) {
            request("/ventas/caja_evidencia_correccion_resolver_erp", {
                folio: folio,
                decision: decision,
                motivo: motivo || ""
            }).then(function (response) {
                mostrarResultadoEvidenciaCaja(response, "Resolucion de correccion");
                if (!response.error) {
                    consultarDetalleEvidenciaCaja(idMovimiento);
                }
            }).catch(mostrarErrorEvidenciaCaja);
        };
        if (window.Swal) {
            Swal.fire({
                title: decision === "aprobada" ? "Aprobar correccion" : "Rechazar correccion",
                input: "textarea",
                inputPlaceholder: "Motivo de resolucion",
                showCancelButton: true,
                confirmButtonText: decision === "aprobada" ? "Aprobar" : "Rechazar",
                cancelButtonText: "Cancelar",
                inputValidator: function (value) {
                    return value && value.trim() ? null : "Captura un motivo";
                }
            }).then(function (result) {
                if (result.isConfirmed) {
                    ejecutar(result.value || "");
                }
            });
            return;
        }
        var motivo = window.prompt("Motivo de resolucion");
        if (motivo && motivo.trim()) {
            ejecutar(motivo);
        }
    }
    function mostrarResultadoEvidenciaCaja(response, titulo) {
        var contenedor = document.getElementById("pos_evidencias_resultado");
        var clase = response && response.error ? "alert-warning" : "alert-success";
        var mensaje = response && response.mensaje ? response.mensaje : titulo;
        contenedor.insertAdjacentHTML("afterbegin", "<div class=\"alert " + clase + " py-3 mb-3\"><div class=\"fw-bold\">" + escapeHtml(titulo || "Evidencias de caja") + "</div><div class=\"fs-8\">" + escapeHtml(mensaje) + "</div></div>");
    }
    function mostrarErrorEvidenciaCaja(error) {
        document.getElementById("pos_evidencias_resultado").insertAdjacentHTML("afterbegin", "<div class=\"alert alert-warning py-3 mb-3\">" + escapeHtml(error.message || String(error)) + "</div>");
    }
    function abrirTicketDevolucion(folioDevolucion) {
        if (!folioDevolucion) { return; }
        requestGet("/ventas/pos_ticket_devolucion_erp", {
            folio_devolucion: folioDevolucion
        }).then(function (response) {
            var depurar = response.depurar || {};
            var ticket = depurar.ticket_texto || "";
            if (!ticket) {
                var mensaje = response.mensaje || "No se pudo generar ticket de devolucion";
                document.getElementById("pos_evidencias_resultado").insertAdjacentHTML("afterbegin", "<div class=\"alert alert-warning py-3 mb-3\">" + escapeHtml(mensaje) + "</div>");
                return;
            }
            document.getElementById("pos_ticket_texto").textContent = ticket;
            bootstrap.Modal.getOrCreateInstance(document.getElementById("pos_ticket_modal")).show();
        }).catch(function (error) {
            document.getElementById("pos_evidencias_resultado").insertAdjacentHTML("afterbegin", "<div class=\"alert alert-warning py-3 mb-3\">" + escapeHtml(error.message || String(error)) + "</div>");
        });
    }
    function renderCorteTurno(response) {
        var depurar = response.depurar || {};
        var resumen = depurar.resumen || {};
        var ventas = resumen.ventas || {};
        var bloqueos = depurar.bloqueos || [];
        var avisos = depurar.avisos || [];
        var diferencia = cantidad(depurar.diferencia || 0);
        var clase = bloqueos.length ? "alert-warning" : (Math.abs(diferencia) < 0.0001 ? "alert-success" : "alert-info");
        var html = "<div class=\"alert " + clase + " py-3 mb-3\">" +
            "<div class=\"fw-bold mb-1\">" + escapeHtml(response.mensaje || "Corte") + "</div>" +
            "<div class=\"fs-7\">Esperado: " + dinero(depurar.monto_esperado || 0) +
            " | Contado: " + dinero(depurar.monto_contado || 0) +
            " | Diferencia: " + dinero(depurar.diferencia || 0) + "</div>";
        if (bloqueos.length) {
            html += "<ul class=\"mb-0 mt-2 ps-4\">" + bloqueos.map(function (item) { return "<li>" + escapeHtml(item) + "</li>"; }).join("") + "</ul>";
        }
        if (avisos.length) {
            html += "<ul class=\"mb-0 mt-2 ps-4 text-muted\">" + avisos.map(function (item) { return "<li>" + escapeHtml(item) + "</li>"; }).join("") + "</ul>";
        }
        html += "</div>";
        html += "<div class=\"row g-3 mb-3\">" +
            "<div class=\"col-md-3\"><div class=\"border rounded p-3\"><div class=\"text-muted fs-8\">Ventas</div><div class=\"fw-bold fs-5\">" + escapeHtml(ventas.operaciones || 0) + "</div></div></div>" +
            "<div class=\"col-md-3\"><div class=\"border rounded p-3\"><div class=\"text-muted fs-8\">Total vendido</div><div class=\"fw-bold fs-5\">" + dinero(ventas.total || 0) + "</div></div></div>" +
            "<div class=\"col-md-3\"><div class=\"border rounded p-3\"><div class=\"text-muted fs-8\">Pagado</div><div class=\"fw-bold fs-5\">" + dinero(ventas.pagado || 0) + "</div></div></div>" +
            "<div class=\"col-md-3\"><div class=\"border rounded p-3\"><div class=\"text-muted fs-8\">Saldo</div><div class=\"fw-bold fs-5\">" + dinero(ventas.saldo || 0) + "</div></div></div>" +
        "</div>";
        html += renderCorteTabla("Pagos por metodo", resumen.pagos_por_metodo || [], ["metodo_pago", "tipo_pago", "operaciones", "monto"]);
        html += renderCorteTabla("Movimientos de caja", resumen.movimientos_por_tipo || [], ["tipo", "motivo", "operaciones", "monto"]);
        document.getElementById("pos_corte_resultado").innerHTML = html;
    }
    function renderCorteTabla(titulo, filas, columnas) {
        if (!filas.length) {
            return "<div class=\"text-muted fs-8 mb-3\">" + escapeHtml(titulo) + ": sin registros.</div>";
        }
        return "<div class=\"mb-3\"><div class=\"fw-bold mb-2\">" + escapeHtml(titulo) + "</div><div class=\"table-responsive\"><table class=\"table table-sm align-middle mb-0\"><thead><tr>" +
            columnas.map(function (columna) { return "<th class=\"text-muted fs-8 text-uppercase\">" + escapeHtml(columna.replace(/_/g, " ")) + "</th>"; }).join("") +
            "</tr></thead><tbody>" +
            filas.map(function (fila) {
                return "<tr>" + columnas.map(function (columna) {
                    var valor = valorCorteTabla(fila, columna);
                    var esMonto = columna === "monto";
                    return "<td" + (esMonto ? " class=\"text-end fw-bold\"" : "") + ">" + (esMonto ? dinero(valor || 0) : escapeHtml(valor || "-")) + "</td>";
                }).join("") + "</tr>";
            }).join("") +
            "</tbody></table></div></div>";
    }
    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-07
     * Proposito: traducir pagos virtuales en el corte POS sin alterar importes.
     * Impacto: saldo de cliente se muestra como pago sin caja para evitar errores de arqueo.
     */
    function valorCorteTabla(fila, columna) {
        var metodo = String((fila || {}).metodo_pago || "").toLowerCase();
        var tipo = String((fila || {}).tipo_pago || "").toLowerCase();
        var esSaldoCrm = metodo === "saldo_crm" || tipo === "saldo_cliente";
        if (esSaldoCrm && columna === "metodo_pago") { return "Saldo cliente"; }
        if (esSaldoCrm && columna === "tipo_pago") { return "Sin caja"; }
        return fila[columna];
    }
    function renderAtencionesBandeja(response) {
        var depurar = response.depurar || {};
        var bloqueos = depurar.bloqueos || [];
        var atenciones = depurar.atenciones || [];
        var html = "<div class=\"alert " + (bloqueos.length ? "alert-warning" : "alert-success") + " py-3 mb-3\">" +
            "<div class=\"fw-bold mb-1\">" + escapeHtml(response.mensaje || "Atenciones") + "</div>";
        if (bloqueos.length) {
            html += "<ul class=\"mb-0 ps-4\">" + bloqueos.map(function (item) { return "<li>" + escapeHtml(item) + "</li>"; }).join("") + "</ul>";
        } else {
            html += "<div class=\"fs-7\">Atenciones abiertas: " + escapeHtml(atenciones.length) + "</div>";
        }
        html += "</div>";
        if (atenciones.length) {
            html += "<div class=\"table-responsive\"><table class=\"table table-sm align-middle\"><thead><tr><th>Folio</th><th>Cliente</th><th>Estatus</th><th class=\"text-end\">Total</th><th class=\"text-end\">Partidas</th></tr></thead><tbody>" +
                atenciones.map(function (item) {
                    return "<tr><td>" + escapeHtml(item.folio_temporal || "") + "</td><td>" + escapeHtml(item.cliente_nombre_publico || "Publico general") + "</td><td>" + escapeHtml(item.estatus || "") + "</td><td class=\"text-end fw-bold\">" + dinero(item.total || 0) + "</td><td class=\"text-end\">" + escapeHtml(item.partidas || 0) + "</td></tr>";
                }).join("") + "</tbody></table></div>";
        }
        document.getElementById("pos_atenciones_resultado").innerHTML = html;
    }
    function renderAtencionPersistente(response) {
        var depurar = response.depurar || {};
        var bloqueos = depurar.bloqueos || [];
        var avisos = depurar.avisos || [];
        var totales = depurar.totales || {};
        var html = "<div class=\"alert " + (bloqueos.length ? "alert-warning" : "alert-success") + " py-3 mb-3\">" +
            "<div class=\"fw-bold mb-1\">" + escapeHtml(response.mensaje || "Atencion") + "</div>" +
            "<div class=\"fs-7\">Folio sugerido: " + escapeHtml(depurar.folio_temporal_sugerido || "-") +
            " | Partidas: " + escapeHtml(totales.partidas || 0) +
            " | Total: " + dinero(totales.total_estimado || 0) + "</div>";
        if (bloqueos.length) {
            html += "<ul class=\"mb-0 mt-2 ps-4\">" + bloqueos.map(function (item) { return "<li>" + escapeHtml(item) + "</li>"; }).join("") + "</ul>";
        }
        if (avisos.length) {
            html += "<ul class=\"mb-0 mt-2 ps-4 text-muted\">" + avisos.map(function (item) { return "<li>" + escapeHtml(item) + "</li>"; }).join("") + "</ul>";
        }
        html += "</div>";
        html += renderPlanSalida(depurar.partidas || []);
        document.getElementById("pos_atenciones_resultado").innerHTML = html;
    }
    function mostrarError(error) {
        var mensaje = error.message || String(error);
        if (window.Swal) {
            Swal.fire({text: mensaje, icon: "warning", confirmButtonText: "Aceptar"});
            return;
        }
        document.getElementById("pos_validacion").innerHTML = "<div class=\"alert alert-warning py-3\">" + escapeHtml(mensaje) + "</div>";
    }
    function ajustarCantidad(item, direccion) {
        var paso = item.modo_salida === "granel_unidad_abierta" ? (item.incremento_minimo_venta || 1) : 1;
        item.cantidad = Math.max(paso, cantidad(item.cantidad) + (paso * direccion));
    }
    function cambiarModoSalida(item, modo) {
        item.modo_salida = modo;
        item.id_inventario_unidad = "";
        var candidata = null;
        if (modo === "unidad_cerrada") {
            candidata = item.unidades.find(function (unidad) { return unidad.estado_fisico === "cerrada"; });
        } else if (modo === "granel_unidad_abierta") {
            candidata = item.unidades.find(function (unidad) { return unidad.estado_fisico === "abierta"; });
            item.cantidad = Math.max(cantidad(item.incremento_minimo_venta || 1), cantidad(item.cantidad || item.incremento_minimo_venta || 1));
        }
        if (candidata && modo !== "existencia_agregada") {
            seleccionarUnidad(item, candidata.id_inventario_unidad);
        }
    }
    function seleccionarUnidad(item, idUnidad) {
        item.id_inventario_unidad = idUnidad || "";
        var unidad = item.unidades.find(function (actual) { return String(actual.id_inventario_unidad) === String(idUnidad); });
        if (unidad && item.modo_salida === "unidad_cerrada") {
            item.cantidad = cantidad(unidad.cantidad_base_disponible || item.cantidad);
        }
    }
    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-30
     * Proposito: registrar atajos POS de bajo conflicto para operacion de mostrador.
     * Impacto: acelera busqueda, pagos y cobro sin reemplazar validaciones backend.
     * Contrato: no escribe BD; solo dispara acciones UI existentes.
     */
    function registrarAtajosPos() {
        document.addEventListener("keydown", function (event) {
            var activo = document.activeElement;
            var editando = activo && ["INPUT", "TEXTAREA", "SELECT"].indexOf(activo.tagName) >= 0;
            var key = event.key || "";
            if ((event.ctrlKey || event.metaKey) && key.toLowerCase() === "k") {
                event.preventDefault();
                document.getElementById("pos_buscar").focus();
                document.getElementById("pos_buscar").select();
                return;
            }
            if (key === "F2") {
                event.preventDefault();
                document.getElementById("pos_buscar").focus();
                document.getElementById("pos_buscar").select();
                return;
            }
            if (editando && !event.altKey && !(event.ctrlKey || event.metaKey) && ["F4", "F6", "F8", "F9", "F10"].indexOf(key) < 0) {
                return;
            }
            if (event.altKey && key === "1") {
                event.preventDefault();
                agregarPagoRapido("efectivo");
                return;
            }
            if (event.altKey && key === "2") {
                event.preventDefault();
                agregarPagoRapido("tarjeta");
                return;
            }
            if (event.altKey && key === "3") {
                event.preventDefault();
                agregarPagoRapido("transferencia");
                return;
            }
            if ((event.ctrlKey || event.metaKey) && key === "Enter") {
                event.preventDefault();
                cobrarReal();
                return;
            }
            if (key === "F4") {
                event.preventDefault();
                abrirClienteAutorizacion("cliente");
                return;
            }
            if (key === "F6") {
                event.preventDefault();
                var pago = document.querySelector("[data-pos-pago-monto]");
                if (pago) {
                    pago.focus();
                    pago.select();
                } else {
                    agregarPagoRapido("efectivo");
                }
                return;
            }
            if (key === "F8") {
                event.preventDefault();
                window.location.href = "/ventas/caja_movimientos";
                return;
            }
            if (key === "F9") {
                event.preventDefault();
                prevalidar();
                return;
            }
            if (key === "F10") {
                event.preventDefault();
                window.location.href = "/ventas/pedidos";
            }
        });
    }
    document.addEventListener("DOMContentLoaded", function () {
        cargarCuentas();
        cargarCatalogos();
        renderCuentas();
        renderCarrito();
        renderPagos();
        actualizarOperador();
        registrarAtajosPos();
        document.getElementById("pos_buscar").addEventListener("input", buscar);
        document.getElementById("pos_almacen").addEventListener("change", function () {
            cuentas = [nuevaCuenta("Cuenta 1")];
            cuentaActivaId = cuentas[0].id;
            aplicarCuentaActiva();
            guardarCuentas();
            llenarCajas();
            llenarTurnos();
            renderCuentas();
            renderCarrito();
            renderPagos();
            buscar();
        });
        document.getElementById("pos_caja").addEventListener("change", llenarTurnos);
        document.getElementById("pos_refrescar").addEventListener("click", cargarCatalogos);
        document.getElementById("pos_terminal_config_btn").addEventListener("click", function () {
            var oficial = !!(asignacionOficial && asignacionOficial.asignacion_activa);
            document.getElementById("pos_terminal_guardar").disabled = oficial;
            document.getElementById("pos_terminal_liberar").disabled = oficial;
            bootstrap.Modal.getOrCreateInstance(document.getElementById("pos_terminal_modal")).show();
        });
        document.getElementById("pos_terminal_guardar").addEventListener("click", guardarTerminalDesdeModal);
        document.getElementById("pos_terminal_liberar").addEventListener("click", liberarTerminalDesdeModal);
        document.getElementById("pos_limpiar_busqueda").addEventListener("click", function () {
            document.getElementById("pos_buscar").value = "";
            renderResultados([]);
        });
        document.getElementById("pos_vaciar").addEventListener("click", function () {
            carrito = [];
            pagos = [];
            limpiarExcepcionActiva();
            guardarCuentas();
            renderCarrito();
            renderPagos();
            document.getElementById("pos_validacion").innerHTML = "";
        });
        document.getElementById("pos_cuenta_nueva").addEventListener("click", crearCuentaAtencion);
        document.getElementById("pos_validacion").addEventListener("click", function (event) {
            var ticketReal = event.target.closest("[data-pos-ticket-real]");
            if (ticketReal) {
                abrirTicketVentaReal(ticketReal.getAttribute("data-pos-ticket-real"));
            }
        });
        document.getElementById("pos_cuentas").addEventListener("click", function (event) {
            var cerrar = event.target.closest("[data-pos-cuenta-cerrar]");
            if (cerrar) {
                cerrarCuentaAtencion(cerrar.getAttribute("data-pos-cuenta-cerrar"));
                return;
            }
            var boton = event.target.closest("[data-pos-cuenta]");
            if (boton) {
                seleccionarCuenta(boton.getAttribute("data-pos-cuenta"));
            }
        });
        document.getElementById("pos_cliente").addEventListener("input", function () {
            limpiarClienteCrmSeleccionado();
            guardarCuentas();
            renderCuentas();
        });
        document.getElementById("pos_cliente_telefono").addEventListener("input", function () {
            limpiarClienteCrmSeleccionado();
            guardarCuentas();
            renderCuentas();
        });
        var botonPagoManual = document.getElementById("pos_agregar_pago");
        if (botonPagoManual) {
            botonPagoManual.addEventListener("click", agregarPago);
        }
        document.querySelectorAll("[data-pos-pago-rapido]").forEach(function (button) {
            button.addEventListener("click", function () {
                agregarPagoRapido(button.getAttribute("data-pos-pago-rapido"));
            });
        });
        document.getElementById("pos_prevalidar").addEventListener("click", prevalidar);
        document.getElementById("pos_prevalidar_top").addEventListener("click", prevalidar);
        document.getElementById("pos_dryrun").addEventListener("click", dryRunConfirmacion);
        document.getElementById("pos_cobrar_real").addEventListener("click", cobrarReal);
        document.getElementById("pos_pedido_dryrun").addEventListener("click", dryRunPedidoReserva);
        document.getElementById("pos_ticket_preview").addEventListener("click", ticketPreview);
        document.getElementById("pos_cliente_precio_modal_btn").addEventListener("click", function () {
            abrirClienteAutorizacion("cliente");
        });
        document.getElementById("pos_excepcion_modal_btn").addEventListener("click", function () {
            abrirClienteAutorizacion("excepcion");
        });
        document.getElementById("pos_cliente_precio_simular").addEventListener("click", clientePrecioDryRun);
        document.getElementById("pos_cliente_buscar_crm").addEventListener("click", clienteBuscarCrmDryRun);
        document.getElementById("pos_cliente_alta_dryrun").addEventListener("click", clienteAltaRapidaDryRun);
        document.getElementById("pos_excepcion_tipo").addEventListener("change", actualizarExcepcionCampos);
        document.getElementById("pos_excepcion_dryrun").addEventListener("click", excepcionComercialDryRun);
        document.getElementById("pos_excepcion_registrar").addEventListener("click", excepcionComercialRegistrar);
        document.getElementById("pos_excepcion_consumo_dryrun").addEventListener("click", excepcionConsumoDryRun);
        document.getElementById("pos_atenciones_modal_btn").addEventListener("click", function () {
            document.getElementById("pos_atenciones_resultado").innerHTML = "";
            bootstrap.Modal.getOrCreateInstance(document.getElementById("pos_atenciones_modal")).show();
        });
        document.getElementById("pos_atenciones_bandeja").addEventListener("click", atencionesBandejaDryRun);
        document.getElementById("pos_atenciones_simular").addEventListener("click", atencionPersistenteDryRun);
        var cajaModalBtn = document.getElementById("pos_caja_modal_btn");
        if (cajaModalBtn) {
            cajaModalBtn.addEventListener("click", function () {
                document.getElementById("pos_caja_resultado").innerHTML = "";
                document.getElementById("pos_evidencias_resultado").innerHTML = "";
                document.getElementById("pos_devoluciones_fisicas_resultado").innerHTML = "";
                bootstrap.Modal.getOrCreateInstance(document.getElementById("pos_caja_modal")).show();
            });
        }
        var cajaSimular = document.getElementById("pos_caja_simular");
        if (cajaSimular) {
            cajaSimular.addEventListener("click", cajaMovimientoDryRun);
        }
        var evidenciasConsultar = document.getElementById("pos_evidencias_consultar");
        if (evidenciasConsultar) {
            evidenciasConsultar.addEventListener("click", consultarEvidenciasCaja);
        }
        var devolucionesFisicasConsultar = document.getElementById("pos_devoluciones_fisicas_consultar");
        if (devolucionesFisicasConsultar) {
            devolucionesFisicasConsultar.addEventListener("click", consultarDevolucionesFisicas);
        }
        var evidenciasResultado = document.getElementById("pos_evidencias_resultado");
        if (evidenciasResultado) {
            evidenciasResultado.addEventListener("click", function (event) {
            var ticketDevolucion = event.target.closest("[data-pos-ticket-devolucion]");
            if (ticketDevolucion) {
                abrirTicketDevolucion(ticketDevolucion.getAttribute("data-pos-ticket-devolucion"));
                return;
            }
            var solicitarCorreccion = event.target.closest("[data-pos-evidencia-correccion-solicitar]");
            if (solicitarCorreccion) {
                solicitarCorreccionEvidenciaCaja(
                    solicitarCorreccion.getAttribute("data-pos-evidencia-correccion-solicitar"),
                    solicitarCorreccion.getAttribute("data-pos-evidencia-movimiento")
                );
                return;
            }
            var adjuntarCorreccion = event.target.closest("[data-pos-evidencia-correccion-adjuntar]");
            if (adjuntarCorreccion) {
                registrarEvidenciaCorrectivaCaja(
                    adjuntarCorreccion.getAttribute("data-pos-evidencia-correccion-adjuntar"),
                    adjuntarCorreccion.getAttribute("data-pos-evidencia-movimiento")
                );
                return;
            }
            var resolverCorreccion = event.target.closest("[data-pos-evidencia-correccion-resolver]");
            if (resolverCorreccion) {
                resolverCorreccionEvidenciaCaja(
                    resolverCorreccion.getAttribute("data-pos-evidencia-correccion-folio"),
                    resolverCorreccion.getAttribute("data-pos-evidencia-correccion-resolver"),
                    resolverCorreccion.getAttribute("data-pos-evidencia-movimiento")
                );
                return;
            }
            var revision = event.target.closest("[data-pos-evidencia-revisar]");
            if (revision) {
                revisarEvidenciaCaja(revision.getAttribute("data-pos-evidencia-id"), revision.getAttribute("data-pos-evidencia-revisar"));
                return;
            }
            var boton = event.target.closest("[data-pos-evidencia-detalle]");
            if (!boton) { return; }
            consultarDetalleEvidenciaCaja(boton.getAttribute("data-pos-evidencia-detalle"));
            });
        }
        var corteModalBtn = document.getElementById("pos_corte_modal_btn");
        if (corteModalBtn) {
            corteModalBtn.addEventListener("click", function () {
                document.getElementById("pos_corte_resultado").innerHTML = "";
                bootstrap.Modal.getOrCreateInstance(document.getElementById("pos_corte_modal")).show();
            });
        }
        var corteSimular = document.getElementById("pos_corte_simular");
        if (corteSimular) {
            corteSimular.addEventListener("click", corteTurnoDryRun);
        }
        document.getElementById("pos_carrito").addEventListener("click", function (event) {
            var itemNode = event.target.closest("[data-pos-item]");
            if (!itemNode) { return; }
            var item = carrito[Number(itemNode.getAttribute("data-pos-item"))];
            if (event.target.closest("[data-pos-quitar]")) {
                carrito.splice(carrito.indexOf(item), 1);
                limpiarExcepcionActiva();
                guardarCuentas();
                renderCarrito();
                return;
            }
            var ajuste = event.target.closest("[data-pos-cantidad-ajuste]");
            if (ajuste) {
                ajustarCantidad(item, Number(ajuste.getAttribute("data-pos-cantidad-ajuste")));
                limpiarExcepcionActiva();
                guardarCuentas();
                renderCarrito();
                return;
            }
            var modoRapido = event.target.closest("[data-pos-modo-rapido]");
            if (modoRapido) {
                cambiarModoSalida(item, modoRapido.getAttribute("data-pos-modo-rapido"));
                limpiarExcepcionActiva();
                guardarCuentas();
                renderCarrito();
            }
        });
        document.getElementById("pos_carrito").addEventListener("input", function (event) {
            var itemNode = event.target.closest("[data-pos-item]");
            if (!itemNode || !event.target.matches("[data-pos-cantidad]")) { return; }
            carrito[Number(itemNode.getAttribute("data-pos-item"))].cantidad = cantidad(event.target.value);
            limpiarExcepcionActiva();
            guardarCuentas();
            renderCuentas();
            actualizarTotales();
        });
        document.getElementById("pos_pagos").addEventListener("click", function (event) {
            var node = event.target.closest("[data-pos-pago]");
            if (!node || !event.target.closest("[data-pos-pago-quitar]")) { return; }
            pagos.splice(Number(node.getAttribute("data-pos-pago")), 1);
            limpiarExcepcionActiva();
            guardarCuentas();
            renderPagos();
        });
        document.getElementById("pos_pagos").addEventListener("input", function (event) {
            var node = event.target.closest("[data-pos-pago]");
            if (!node) { return; }
            var pago = pagos[Number(node.getAttribute("data-pos-pago"))];
            if (event.target.matches("[data-pos-pago-monto]")) {
                pago.monto = cantidad(event.target.value);
            }
            if (event.target.matches("[data-pos-pago-ref]")) {
                pago.referencia = event.target.value;
            }
            limpiarExcepcionActiva();
            guardarCuentas();
            renderCuentas();
            actualizarTotales();
        });
        document.getElementById("pos_pagos").addEventListener("change", function (event) {
            var node = event.target.closest("[data-pos-pago]");
            if (!node || !event.target.matches("[data-pos-pago-metodo]")) { return; }
            pagos[Number(node.getAttribute("data-pos-pago"))].id_metodo_pago = event.target.value;
            limpiarExcepcionActiva();
            guardarCuentas();
        });
    });
})();
