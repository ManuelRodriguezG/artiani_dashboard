"use strict";
(function () {
    var estado = {};
    var filtros = {
        cajas: "activos",
        terminales: "activos",
        asignaciones: "activos",
        politicas: "activos"
    };

    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-01
     * Proposito: administrar configuracion POS separada del mostrador.
     * Impacto: permite crear, editar y desactivar cajas, terminales y asignaciones con permisos finos.
     */
    function request(url) {
        return fetch(url, {credentials: "same-origin"}).then(function (response) { return response.json(); });
    }

    function postRequest(url, data) {
        return fetch(url, {
            method: "POST",
            headers: {"Content-Type": "application/x-www-form-urlencoded; charset=UTF-8", "X-CSRF-Token": window.ERP_CSRF_TOKEN || ""},
            body: new URLSearchParams(data).toString(),
            credentials: "same-origin"
        }).then(function (response) { return response.json(); });
    }

    function escapeHtml(value) {
        var div = document.createElement("div");
        div.textContent = value == null ? "" : String(value);
        return div.innerHTML;
    }

    function cargar() {
        request("/ventas/pos_configuracion_resumen_erp").then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            estado = response.depurar || {};
            render(estado);
        }).catch(function (error) {
            document.getElementById("pos_config_alerta").innerHTML = "<div class=\"alert alert-danger py-3\">" + escapeHtml(error.message || String(error)) + "</div>";
        });
    }

    function render(data) {
        var cajas = data.cajas || [];
        var terminales = data.terminales || [];
        var asignaciones = data.asignaciones || [];
        var politicas = data.politicas_inventario_pendiente || [];
        var tickets = data.ticket_configuraciones || [];
        renderKpi("pos_config_kpi_cajas", contarActivos(cajas, "activa"), contarHistoricos(cajas, "activa"));
        renderKpi("pos_config_kpi_terminales", contarActivos(terminales, "activa"), contarHistoricos(terminales, "activa"));
        renderKpi("pos_config_kpi_asignaciones", contarActivos(asignaciones, "activo"), contarHistoricos(asignaciones, "activo"));
        renderKpi("pos_config_kpi_politicas", contarActivos(politicas, "activa"), contarHistoricos(politicas, "activa"));
        document.getElementById("pos_config_alerta").innerHTML = data.schema_pendiente
            ? "<div class=\"alert alert-warning py-3\"><div class=\"fw-bold\">Configuracion incompleta</div><div class=\"fs-7\">Hay tablas POS pendientes. La administracion queda en modo solo consulta.</div></div>"
            : "<div class=\"alert alert-info py-3\"><div class=\"fw-bold\">Configuracion POS separada</div><div class=\"fs-7\">Guardar requiere permiso administrativo; validar no escribe datos.</div></div>";
        renderCajas(filtrarPorEstado(cajas, filtros.cajas, "activa"));
        renderTerminales(filtrarPorEstado(terminales, filtros.terminales, "activa"));
        renderAsignaciones(filtrarPorEstado(asignaciones, filtros.asignaciones, "activo"));
        renderPoliticasInventario(filtrarPorEstado(politicas, filtros.politicas, "activa"));
        renderTickets(tickets);
        llenarSelectores(data);
    }

    function renderKpi(id, activos, historicos) {
        document.getElementById(id).innerHTML = String(activos) + (historicos > 0 ? " <span class=\"fs-8 text-muted\">+" + historicos + " hist.</span>" : "");
    }

    function contarActivos(filas, estatusActivo) {
        return filtrarPorEstado(filas, "activos", estatusActivo).length;
    }

    function contarHistoricos(filas, estatusActivo) {
        return filtrarPorEstado(filas, "historico", estatusActivo).length;
    }

    function filtrarPorEstado(filas, filtro, estatusActivo) {
        if (filtro === "todos") {
            return filas || [];
        }
        return (filas || []).filter(function (item) {
            var activo = String(item.estatus || estatusActivo) === estatusActivo;
            return filtro === "activos" ? activo : !activo;
        });
    }

    function actualizarBotonesFiltro(grupo, valor) {
        document.querySelectorAll("[data-pos-filtro=\"" + grupo + "\"]").forEach(function (boton) {
            var activo = boton.getAttribute("data-valor") === valor;
            boton.classList.toggle("active", activo);
            boton.classList.toggle("btn-light-primary", activo);
            boton.classList.toggle("btn-light", !activo);
        });
    }

    function llenarSelectores(data) {
        var almacenes = data.almacenes || [];
        var cajas = data.cajas || [];
        var terminales = data.terminales || [];
        var almacenesHtml = almacenes.map(function (item) {
            return "<option value=\"" + escapeHtml(item.id_almacen) + "\">" + escapeHtml(item.codigo_almacen || item.almacen || item.id_almacen) + "</option>";
        }).join("") || "<option value=\"\">Sin tiendas</option>";
        ["pos_cfg_caja_almacen", "pos_cfg_terminal_almacen", "pos_cfg_asig_almacen", "pos_cfg_pinv_almacen", "pos_cfg_ticket_almacen"].forEach(function (id) {
            document.getElementById(id).innerHTML = almacenesHtml;
        });
        var cajasHtml = cajas.map(function (item) {
            return "<option value=\"" + escapeHtml(item.id_caja) + "\" data-almacen=\"" + escapeHtml(item.id_almacen) + "\">" + escapeHtml(item.codigo || item.nombre || item.id_caja) + "</option>";
        }).join("") || "<option value=\"\">Sin cajas</option>";
        ["pos_cfg_terminal_caja", "pos_cfg_asig_caja", "pos_cfg_ticket_caja"].forEach(function (id) {
            document.getElementById(id).innerHTML = cajasHtml;
        });
        var terminalesHtml = "<option value=\"\">Sin terminal</option>" + terminales.map(function (item) {
            return "<option value=\"" + escapeHtml(item.id_terminal_pos) + "\" data-almacen=\"" + escapeHtml(item.id_almacen) + "\" data-caja=\"" + escapeHtml(item.id_caja || "") + "\">" + escapeHtml(item.codigo || item.nombre || item.id_terminal_pos) + "</option>";
        }).join("");
        document.getElementById("pos_cfg_asig_terminal").innerHTML = terminalesHtml;
        document.getElementById("pos_cfg_ticket_terminal").innerHTML = terminalesHtml;
        sincronizarTerminal();
        sincronizarAsignacion("almacen");
        sincronizarTicket("almacen");
        llenarTicketDesdeActual(data.ticket_configuracion_efectiva || {});
    }

    function seleccionarCompatible(select, predicate) {
        var actual = select.selectedOptions[0];
        if (actual && predicate(actual)) { return; }
        for (var i = 0; i < select.options.length; i += 1) {
            if (predicate(select.options[i])) {
                select.value = select.options[i].value;
                return;
            }
        }
    }

    function sincronizarTerminal() {
        var almacen = document.getElementById("pos_cfg_terminal_almacen").value;
        var caja = document.getElementById("pos_cfg_terminal_caja");
        seleccionarCompatible(caja, function (option) {
            return !almacen || option.getAttribute("data-almacen") === almacen || option.value === "";
        });
    }

    function sincronizarAsignacion(origen) {
        var almacen = document.getElementById("pos_cfg_asig_almacen").value;
        var caja = document.getElementById("pos_cfg_asig_caja");
        var terminal = document.getElementById("pos_cfg_asig_terminal");
        if (origen === "almacen") {
            seleccionarCompatible(caja, function (option) {
                return !almacen || option.getAttribute("data-almacen") === almacen || option.value === "";
            });
        }
        var cajaValor = caja.value;
        seleccionarCompatible(terminal, function (option) {
            var optionAlmacen = option.getAttribute("data-almacen");
            var optionCaja = option.getAttribute("data-caja");
            if (option.value === "") { return true; }
            return (!almacen || optionAlmacen === almacen) && (!cajaValor || optionCaja === cajaValor || optionCaja === "");
        });
    }

    function sincronizarTicket(origen) {
        var almacen = document.getElementById("pos_cfg_ticket_almacen").value;
        var caja = document.getElementById("pos_cfg_ticket_caja");
        var terminal = document.getElementById("pos_cfg_ticket_terminal");
        if (origen === "almacen") {
            seleccionarCompatible(caja, function (option) {
                return !almacen || option.getAttribute("data-almacen") === almacen || option.value === "";
            });
        }
        var cajaValor = caja.value;
        seleccionarCompatible(terminal, function (option) {
            var optionAlmacen = option.getAttribute("data-almacen");
            var optionCaja = option.getAttribute("data-caja");
            if (option.value === "") { return true; }
            return (!almacen || optionAlmacen === almacen) && (!cajaValor || optionCaja === cajaValor || optionCaja === "");
        });
    }

    function renderCajas(filas) {
        document.getElementById("pos_config_cajas").innerHTML = filas.map(function (item) {
            var metodos = [];
            var activo = String(item.estatus || "activa") === "activa";
            if (Number(item.permite_efectivo || 0)) { metodos.push("Efectivo"); }
            if (Number(item.permite_tarjeta || 0)) { metodos.push("Tarjeta"); }
            if (Number(item.permite_transferencia || 0)) { metodos.push("Transferencia"); }
            return "<tr><td><div class=\"fw-bold\">" + escapeHtml(item.codigo || "-") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.nombre || "") + "</div></td>" +
                "<td>" + escapeHtml(item.almacen || item.id_almacen || "-") + "</td><td>" + metodos.map(function (metodo) { return "<span class=\"badge badge-light me-1\">" + escapeHtml(metodo) + "</span>"; }).join("") + "</td>" +
                "<td><span class=\"badge " + (activo ? "badge-light-success" : "badge-light") + "\">" + escapeHtml(item.estatus || "activa") + "</span></td>" +
                "<td class=\"text-end\"><button class=\"btn btn-sm btn-light-primary me-2\" type=\"button\" data-pos-config-editar=\"caja\" data-id=\"" + escapeHtml(item.id_caja || "") + "\"><i class=\"bi bi-pencil\"></i></button>" +
                (activo ? "<button class=\"btn btn-sm btn-light-danger\" type=\"button\" data-pos-config-desactivar=\"caja\" data-id=\"" + escapeHtml(item.id_caja || "") + "\"><i class=\"bi bi-slash-circle\"></i></button>" : "<span class=\"badge badge-light\">Historico</span>") + "</td></tr>";
        }).join("") || "<tr><td colspan=\"5\" class=\"text-center text-muted py-6\">Sin cajas POS para este filtro</td></tr>";
    }

    function renderTerminales(filas) {
        document.getElementById("pos_config_terminales").innerHTML = filas.map(function (item) {
            var activo = String(item.estatus || "") === "activa";
            return "<tr><td><div class=\"fw-bold\">" + escapeHtml(item.codigo || "-") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.nombre || "") + "</div></td>" +
                "<td>" + escapeHtml(item.almacen || item.id_almacen || "-") + "</td><td>" + escapeHtml(item.caja_codigo || item.id_caja || "-") + "</td>" +
                "<td><span class=\"badge " + (activo ? "badge-light-success" : "badge-light") + "\">" + escapeHtml(item.estatus || "-") + "</span></td>" +
                "<td class=\"text-end\"><button class=\"btn btn-sm btn-light-primary me-2\" type=\"button\" data-pos-config-editar=\"terminal\" data-id=\"" + escapeHtml(item.id_terminal_pos || "") + "\"><i class=\"bi bi-pencil\"></i></button>" +
                (activo ? "<button class=\"btn btn-sm btn-light-danger\" type=\"button\" data-pos-config-desactivar=\"terminal\" data-id=\"" + escapeHtml(item.id_terminal_pos || "") + "\"><i class=\"bi bi-slash-circle\"></i></button>" : "<span class=\"badge badge-light\">Historico</span>") + "</td></tr>";
        }).join("") || "<tr><td colspan=\"5\" class=\"text-center text-muted py-6\">Sin terminales POS para este filtro</td></tr>";
    }

    function renderAsignaciones(filas) {
        document.getElementById("pos_config_asignaciones").innerHTML = filas.map(function (item) {
            var activo = String(item.estatus || "") === "activo";
            return "<tr><td><div class=\"fw-bold\">Usuario " + escapeHtml(item.id_usuario || "-") + "</div><div class=\"text-muted fs-8\">Asignacion " + escapeHtml(item.id_usuario_caja || "-") + "</div></td>" +
                "<td>" + escapeHtml(item.almacen || item.id_almacen || "-") + "</td><td>" + escapeHtml(item.caja_codigo || item.id_caja || "-") + "</td>" +
                "<td>" + escapeHtml(item.terminal_codigo || item.id_terminal_pos || "-") + "</td><td>" + escapeHtml(item.prioridad || "-") + "</td>" +
                "<td><span class=\"badge " + (activo ? "badge-light-primary" : "badge-light") + "\">" + escapeHtml(item.estatus || "-") + "</span></td>" +
                "<td class=\"text-end\"><button class=\"btn btn-sm btn-light-primary me-2\" type=\"button\" data-pos-config-editar=\"asignacion\" data-id=\"" + escapeHtml(item.id_usuario_caja || "") + "\"><i class=\"bi bi-pencil\"></i></button>" +
                (activo ? "<button class=\"btn btn-sm btn-light-danger\" type=\"button\" data-pos-config-desactivar=\"asignacion\" data-id=\"" + escapeHtml(item.id_usuario_caja || "") + "\"><i class=\"bi bi-slash-circle\"></i></button>" : "<span class=\"badge badge-light\">Historico</span>") + "</td></tr>";
        }).join("") || "<tr><td colspan=\"7\" class=\"text-center text-muted py-6\">Sin asignaciones para este filtro</td></tr>";
    }

    function renderPoliticasInventario(filas) {
        document.getElementById("pos_config_politicas").innerHTML = filas.map(function (item) {
            var activo = String(item.estatus || "") === "activa";
            var alcance = String(item.alcance || "") === "almacen"
                ? "<span class=\"badge badge-light-warning\">Toda la tienda</span>"
                : "<span class=\"badge badge-light-primary\">SKU " + escapeHtml(item.sku || item.id_sku_erp || "-") + "</span>";
            var reglas = [];
            if (Number(item.requiere_autorizacion || 0)) { reglas.push("Autorizacion"); }
            if (Number(item.motivo_obligatorio || 0)) { reglas.push("Motivo"); }
            reglas.push(String(item.canal || "pos").toUpperCase());
            return "<tr><td><div class=\"fw-bold\">" + escapeHtml(item.codigo || "-") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.nombre || "") + "</div></td>" +
                "<td>" + escapeHtml(item.almacen || item.id_almacen || "-") + "</td>" +
                "<td>" + alcance + "<div class=\"text-muted fs-8\">" + escapeHtml(item.sku_nombre || "") + "</div></td>" +
                "<td><div class=\"fw-semibold\">Cant. " + escapeHtml(item.cantidad_maxima_pendiente || "0") + "</div><div class=\"text-muted fs-8\">Monto $" + escapeHtml(item.monto_maximo || "0") + "</div></td>" +
                "<td>" + reglas.map(function (regla) { return "<span class=\"badge badge-light me-1\">" + escapeHtml(regla) + "</span>"; }).join("") + "</td>" +
                "<td><span class=\"badge " + (activo ? "badge-light-success" : "badge-light") + "\">" + escapeHtml(item.estatus || "-") + "</span></td></tr>";
        }).join("") || "<tr><td colspan=\"6\" class=\"text-center text-muted py-6\">Sin politicas de inventario pendiente para este filtro</td></tr>";
    }

    function renderTickets(filas) {
        document.getElementById("pos_config_tickets").innerHTML = (filas || []).map(function (item) {
            var activo = String(item.estatus || "") === "activa";
            var scope = [];
            scope.push(item.almacen || item.id_almacen ? "Tienda " + (item.almacen || item.id_almacen) : "Global");
            if (item.caja_codigo || item.id_caja) { scope.push("Caja " + (item.caja_codigo || item.id_caja)); }
            if (item.terminal_codigo || item.id_terminal_pos) { scope.push("Terminal " + (item.terminal_codigo || item.id_terminal_pos)); }
            var contacto = [];
            if (item.telefono) { contacto.push("Tel. " + item.telefono); }
            if (item.whatsapp) { contacto.push("WA " + item.whatsapp); }
            if (item.email) { contacto.push(item.email); }
            if (item.sitio_web) { contacto.push(item.sitio_web); }
            return "<tr><td><div class=\"fw-bold\">" + escapeHtml(item.nombre_comercial || "-") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.rfc || item.razon_social || "") + "</div></td>" +
                "<td>" + escapeHtml(scope.join(" / ")) + "</td>" +
                "<td><div class=\"fw-semibold\">" + escapeHtml(item.ticket_ancho_mm || "80") + " mm / " + escapeHtml(item.ticket_columnas || "42") + " col.</div><div class=\"text-muted fs-8\">" + escapeHtml(item.impresion_modo || "navegador") + " / logo " + escapeHtml(item.logo_modo || "texto") + "</div></td>" +
                "<td>" + escapeHtml(contacto.join(" | ") || "-") + "</td>" +
                "<td><span class=\"badge " + (activo ? "badge-light-success" : "badge-light") + "\">" + escapeHtml(item.estatus || "-") + "</span></td>" +
                "<td class=\"text-end\"><button class=\"btn btn-sm btn-light-primary\" type=\"button\" data-pos-config-editar=\"ticket\" data-id=\"" + escapeHtml(item.id_ticket_configuracion || "") + "\"><i class=\"bi bi-pencil\"></i></button></td></tr>";
        }).join("") || "<tr><td colspan=\"6\" class=\"text-center text-muted py-6\">Sin configuraciones de ticket. Guarda una configuracion base para el POS.</td></tr>";
    }

    function bool(id) {
        return document.getElementById(id).checked ? "1" : "0";
    }

    function datosCaja() {
        return {
            id_caja: document.getElementById("pos_cfg_caja_id").value,
            id_almacen: document.getElementById("pos_cfg_caja_almacen").value,
            codigo: document.getElementById("pos_cfg_caja_codigo").value.trim(),
            nombre: document.getElementById("pos_cfg_caja_nombre").value.trim(),
            permite_efectivo: bool("pos_cfg_caja_efectivo"),
            permite_tarjeta: bool("pos_cfg_caja_tarjeta"),
            permite_transferencia: bool("pos_cfg_caja_transferencia")
        };
    }

    function datosTerminal() {
        return {
            id_terminal_pos: document.getElementById("pos_cfg_terminal_id").value,
            id_almacen: document.getElementById("pos_cfg_terminal_almacen").value,
            id_caja: document.getElementById("pos_cfg_terminal_caja").value,
            codigo: document.getElementById("pos_cfg_terminal_codigo").value.trim(),
            nombre: document.getElementById("pos_cfg_terminal_nombre").value.trim(),
            identificador_terminal: document.getElementById("pos_cfg_terminal_identificador").value.trim()
        };
    }

    function datosAsignacion() {
        return {
            id_usuario_caja: document.getElementById("pos_cfg_asig_id").value,
            id_usuario: document.getElementById("pos_cfg_asig_usuario").value,
            id_almacen: document.getElementById("pos_cfg_asig_almacen").value,
            id_caja: document.getElementById("pos_cfg_asig_caja").value,
            id_terminal_pos: document.getElementById("pos_cfg_asig_terminal").value,
            prioridad: document.getElementById("pos_cfg_asig_prioridad").value
        };
    }

    function datosPoliticaInventario() {
        return {
            id_almacen: document.getElementById("pos_cfg_pinv_almacen").value,
            alcance: document.getElementById("pos_cfg_pinv_alcance").value,
            id_sku: document.getElementById("pos_cfg_pinv_sku").value,
            cantidad_maxima: document.getElementById("pos_cfg_pinv_cantidad").value,
            monto_maximo: document.getElementById("pos_cfg_pinv_monto").value,
            codigo: document.getElementById("pos_cfg_pinv_codigo").value.trim(),
            nombre: document.getElementById("pos_cfg_pinv_nombre").value.trim(),
            motivo: document.getElementById("pos_cfg_pinv_motivo").value.trim()
        };
    }

    function datosTicket() {
        return {
            id_empresa_configuracion: document.getElementById("pos_cfg_ticket_id_empresa").value,
            id_ticket_configuracion: document.getElementById("pos_cfg_ticket_id").value,
            nombre_comercial: document.getElementById("pos_cfg_ticket_nombre_comercial").value.trim(),
            razon_social: document.getElementById("pos_cfg_ticket_razon_social").value.trim(),
            rfc: document.getElementById("pos_cfg_ticket_rfc").value.trim(),
            direccion_fiscal: document.getElementById("pos_cfg_ticket_direccion").value.trim(),
            telefono: document.getElementById("pos_cfg_ticket_telefono").value.trim(),
            whatsapp: document.getElementById("pos_cfg_ticket_whatsapp").value.trim(),
            email: document.getElementById("pos_cfg_ticket_email").value.trim(),
            sitio_web: document.getElementById("pos_cfg_ticket_sitio").value.trim(),
            logo_url: document.getElementById("pos_cfg_ticket_logo").value.trim(),
            id_almacen: document.getElementById("pos_cfg_ticket_almacen").value,
            id_caja: document.getElementById("pos_cfg_ticket_caja").value,
            id_terminal_pos: document.getElementById("pos_cfg_ticket_terminal").value,
            nombre_configuracion: document.getElementById("pos_cfg_ticket_nombre_config").value.trim(),
            ticket_ancho_mm: document.getElementById("pos_cfg_ticket_ancho").value,
            ticket_columnas: document.getElementById("pos_cfg_ticket_columnas").value,
            mostrar_logo: document.getElementById("pos_cfg_ticket_logo").value.trim() ? "1" : "0",
            logo_modo: document.getElementById("pos_cfg_ticket_logo_modo").value,
            impresion_modo: document.getElementById("pos_cfg_ticket_impresion").value,
            copias_venta: document.getElementById("pos_cfg_ticket_copias_venta").value,
            copias_devolucion: document.getElementById("pos_cfg_ticket_copias_devolucion").value,
            leyenda_no_fiscal: document.getElementById("pos_cfg_ticket_no_fiscal").value.trim(),
            leyenda_ticket_general: document.getElementById("pos_cfg_ticket_leyenda").value.trim(),
            mensaje_sucursal: document.getElementById("pos_cfg_ticket_mensaje_sucursal").value.trim(),
            leyenda_devoluciones: document.getElementById("pos_cfg_ticket_devoluciones").value.trim(),
            leyenda_garantias: document.getElementById("pos_cfg_ticket_garantias").value.trim()
        };
    }

    function validarCaja() {
        postRequest("/ventas/pos_configuracion_caja_dryrun_erp", datosCaja()).then(renderValidacion).catch(renderErrorValidacion);
    }

    function validarTerminal() {
        postRequest("/ventas/pos_configuracion_terminal_dryrun_erp", datosTerminal()).then(renderValidacion).catch(renderErrorValidacion);
    }

    function validarAsignacion() {
        postRequest("/ventas/pos_configuracion_asignacion_dryrun_erp", datosAsignacion()).then(renderValidacion).catch(renderErrorValidacion);
    }

    function validarPoliticaInventario() {
        postRequest("/ventas/pos_configuracion_politica_inventario_pendiente_dryrun_erp", datosPoliticaInventario()).then(renderValidacion).catch(renderErrorValidacion);
    }

    function validarTicket() {
        postRequest("/ventas/pos_configuracion_ticket_dryrun_erp", datosTicket()).then(renderValidacion).catch(renderErrorValidacion);
    }

    function guardar(url, data) {
        postRequest(url, data).then(function (response) {
            renderValidacion(response);
            if (!response.error && response.tipo === "success") {
                limpiarCaptura();
                cargar();
            }
        }).catch(renderErrorValidacion);
    }

    function guardarCaja() {
        guardar("/ventas/pos_configuracion_caja_guardar_erp", datosCaja());
    }

    function guardarTerminal() {
        guardar("/ventas/pos_configuracion_terminal_guardar_erp", datosTerminal());
    }

    function guardarAsignacion() {
        guardar("/ventas/pos_configuracion_asignacion_guardar_erp", datosAsignacion());
    }

    function guardarPoliticaInventario() {
        guardar("/ventas/pos_configuracion_politica_inventario_pendiente_guardar_erp", datosPoliticaInventario());
    }

    function guardarTicket() {
        guardar("/ventas/pos_configuracion_ticket_guardar_erp", datosTicket());
    }

    function activarTab(selector) {
        var trigger = document.querySelector("[data-bs-target=\"" + selector + "\"]");
        if (trigger && window.bootstrap && window.bootstrap.Tab) {
            window.bootstrap.Tab.getOrCreateInstance(trigger).show();
        }
    }

    function buscarPorId(lista, campo, id) {
        id = String(id || "");
        return (lista || []).filter(function (item) { return String(item[campo] || "") === id; })[0] || null;
    }

    function editar(tipo, id) {
        var item;
        if (tipo === "caja") {
            item = buscarPorId(estado.cajas, "id_caja", id);
            if (!item) { return; }
            activarTab("#pos_config_tab_caja");
            document.getElementById("pos_cfg_caja_id").value = item.id_caja || "";
            document.getElementById("pos_cfg_caja_almacen").value = item.id_almacen || "";
            document.getElementById("pos_cfg_caja_codigo").value = item.codigo || "";
            document.getElementById("pos_cfg_caja_nombre").value = item.nombre || "";
            document.getElementById("pos_cfg_caja_efectivo").checked = Number(item.permite_efectivo || 0) === 1;
            document.getElementById("pos_cfg_caja_tarjeta").checked = Number(item.permite_tarjeta || 0) === 1;
            document.getElementById("pos_cfg_caja_transferencia").checked = Number(item.permite_transferencia || 0) === 1;
        } else if (tipo === "terminal") {
            item = buscarPorId(estado.terminales, "id_terminal_pos", id);
            if (!item) { return; }
            activarTab("#pos_config_tab_terminal");
            document.getElementById("pos_cfg_terminal_id").value = item.id_terminal_pos || "";
            document.getElementById("pos_cfg_terminal_almacen").value = item.id_almacen || "";
            sincronizarTerminal();
            document.getElementById("pos_cfg_terminal_caja").value = item.id_caja || "";
            document.getElementById("pos_cfg_terminal_codigo").value = item.codigo || "";
            document.getElementById("pos_cfg_terminal_nombre").value = item.nombre || "";
            document.getElementById("pos_cfg_terminal_identificador").value = item.identificador_terminal || "";
        } else if (tipo === "asignacion") {
            item = buscarPorId(estado.asignaciones, "id_usuario_caja", id);
            if (!item) { return; }
            activarTab("#pos_config_tab_asignacion");
            document.getElementById("pos_cfg_asig_id").value = item.id_usuario_caja || "";
            document.getElementById("pos_cfg_asig_usuario").value = item.id_usuario || "";
            document.getElementById("pos_cfg_asig_almacen").value = item.id_almacen || "";
            sincronizarAsignacion("almacen");
            document.getElementById("pos_cfg_asig_caja").value = item.id_caja || "";
            sincronizarAsignacion("caja");
            document.getElementById("pos_cfg_asig_terminal").value = item.id_terminal_pos || "";
            document.getElementById("pos_cfg_asig_prioridad").value = item.prioridad || "1";
        } else if (tipo === "ticket") {
            item = buscarPorId(estado.ticket_configuraciones, "id_ticket_configuracion", id);
            if (!item) { return; }
            activarTab("#pos_config_tab_ticket");
            llenarTicketDesdeActual(item);
        }
    }

    function llenarTicketDesdeActual(item) {
        item = item || {};
        document.getElementById("pos_cfg_ticket_id_empresa").value = item.id_empresa_configuracion || "";
        document.getElementById("pos_cfg_ticket_id").value = item.id_ticket_configuracion || "";
        document.getElementById("pos_cfg_ticket_nombre_comercial").value = item.nombre_comercial || "ARTIANI";
        document.getElementById("pos_cfg_ticket_razon_social").value = item.razon_social || "";
        document.getElementById("pos_cfg_ticket_rfc").value = item.rfc || "";
        document.getElementById("pos_cfg_ticket_direccion").value = item.direccion_fiscal || "";
        document.getElementById("pos_cfg_ticket_telefono").value = item.telefono || "";
        document.getElementById("pos_cfg_ticket_whatsapp").value = item.whatsapp || "";
        document.getElementById("pos_cfg_ticket_email").value = item.email || "";
        document.getElementById("pos_cfg_ticket_sitio").value = item.sitio_web || "";
        document.getElementById("pos_cfg_ticket_logo").value = item.logo_url || "";
        document.getElementById("pos_cfg_ticket_almacen").value = item.id_almacen || document.getElementById("pos_cfg_ticket_almacen").value;
        sincronizarTicket("almacen");
        document.getElementById("pos_cfg_ticket_caja").value = item.id_caja || document.getElementById("pos_cfg_ticket_caja").value;
        sincronizarTicket("caja");
        document.getElementById("pos_cfg_ticket_terminal").value = item.id_terminal_pos || "";
        document.getElementById("pos_cfg_ticket_nombre_config").value = item.nombre_configuracion || "Ticket POS principal";
        document.getElementById("pos_cfg_ticket_ancho").value = item.ticket_ancho_mm || "80";
        document.getElementById("pos_cfg_ticket_columnas").value = item.ticket_columnas || "42";
        document.getElementById("pos_cfg_ticket_logo_modo").value = item.logo_modo || "texto";
        document.getElementById("pos_cfg_ticket_impresion").value = item.impresion_modo || "navegador";
        document.getElementById("pos_cfg_ticket_copias_venta").value = item.copias_venta || "1";
        document.getElementById("pos_cfg_ticket_copias_devolucion").value = item.copias_devolucion || "1";
        document.getElementById("pos_cfg_ticket_no_fiscal").value = item.leyenda_no_fiscal || "Ticket no fiscal. Conserve este comprobante.";
        document.getElementById("pos_cfg_ticket_leyenda").value = item.leyenda_ticket_general || "Gracias por su compra.";
        document.getElementById("pos_cfg_ticket_mensaje_sucursal").value = item.mensaje_sucursal || "";
        document.getElementById("pos_cfg_ticket_devoluciones").value = item.leyenda_devoluciones || "";
        document.getElementById("pos_cfg_ticket_garantias").value = item.leyenda_garantias || "";
    }

    function limpiarCaptura() {
        ["pos_cfg_caja_id", "pos_cfg_terminal_id", "pos_cfg_asig_id", "pos_cfg_caja_codigo", "pos_cfg_caja_nombre", "pos_cfg_terminal_codigo", "pos_cfg_terminal_nombre", "pos_cfg_terminal_identificador", "pos_cfg_asig_usuario", "pos_cfg_pinv_codigo", "pos_cfg_pinv_nombre", "pos_cfg_pinv_motivo", "pos_cfg_pinv_sku"].forEach(function (id) {
            var element = document.getElementById(id);
            if (element) { element.value = ""; }
        });
        document.getElementById("pos_cfg_asig_prioridad").value = "1";
        document.getElementById("pos_config_validacion_resultado").innerHTML = "";
    }

    function solicitarMotivo() {
        if (window.Swal && window.Swal.fire) {
            return window.Swal.fire({
                title: "Motivo de desactivacion",
                input: "textarea",
                inputPlaceholder: "Explica por que se desactiva",
                showCancelButton: true,
                confirmButtonText: "Desactivar",
                cancelButtonText: "Cancelar",
                inputValidator: function (value) {
                    if (!value || !value.trim()) { return "Captura el motivo"; }
                    return null;
                }
            }).then(function (result) { return result.isConfirmed ? result.value.trim() : ""; });
        }
        return Promise.resolve((window.prompt("Motivo de desactivacion") || "").trim());
    }

    function desactivar(tipo, id) {
        solicitarMotivo().then(function (motivo) {
            if (!motivo) { return; }
            guardar("/ventas/pos_configuracion_desactivar_erp", {tipo: tipo, id: id, motivo: motivo});
        });
    }

    function renderValidacion(response) {
        if (response.error) { throw new Error(response.mensaje); }
        var data = response.depurar || {};
        var bloqueos = data.bloqueos || [];
        var avisos = data.avisos || [];
        var propuesta = data.propuesta || {};
        var esDryRun = data.dry_run === true;
        var html = "<div class=\"alert " + (bloqueos.length ? "alert-warning" : "alert-success") + " py-3 mb-3\"><div class=\"fw-bold\">" + escapeHtml(response.mensaje || "Validacion") + "</div>" +
            "<div class=\"fs-8 text-muted\">" + (esDryRun ? "Validacion sin crear: no crea ni modifica registros." : "Operacion real protegida: registra auditoria y no abre turnos ni mueve caja.") + "</div>";
        if (bloqueos.length) {
            html += "<ul class=\"mb-0 mt-2 ps-4\">" + bloqueos.map(function (item) { return "<li>" + escapeHtml(item) + "</li>"; }).join("") + "</ul>";
        }
        if (avisos.length) {
            html += "<ul class=\"mb-0 mt-2 ps-4 text-muted\">" + avisos.map(function (item) { return "<li>" + escapeHtml(item) + "</li>"; }).join("") + "</ul>";
        }
        html += "</div><pre class=\"bg-light p-3 rounded fs-8 mb-0\">" + escapeHtml(JSON.stringify(propuesta, null, 2)) + "</pre>";
        document.getElementById("pos_config_validacion_resultado").innerHTML = html;
    }

    function renderErrorValidacion(error) {
        document.getElementById("pos_config_validacion_resultado").innerHTML = "<div class=\"alert alert-warning py-3\">" + escapeHtml(error.message || String(error)) + "</div>";
    }

    document.addEventListener("DOMContentLoaded", function () {
        cargar();
        document.getElementById("pos_config_recargar").addEventListener("click", cargar);
        document.getElementById("pos_cfg_terminal_almacen").addEventListener("change", sincronizarTerminal);
        document.getElementById("pos_cfg_asig_almacen").addEventListener("change", function () { sincronizarAsignacion("almacen"); });
        document.getElementById("pos_cfg_asig_caja").addEventListener("change", function () { sincronizarAsignacion("caja"); });
        document.getElementById("pos_cfg_ticket_almacen").addEventListener("change", function () { sincronizarTicket("almacen"); });
        document.getElementById("pos_cfg_ticket_caja").addEventListener("change", function () { sincronizarTicket("caja"); });
        document.getElementById("pos_cfg_caja_validar").addEventListener("click", validarCaja);
        document.getElementById("pos_cfg_terminal_validar").addEventListener("click", validarTerminal);
        document.getElementById("pos_cfg_asig_validar").addEventListener("click", validarAsignacion);
        document.getElementById("pos_cfg_pinv_validar").addEventListener("click", validarPoliticaInventario);
        document.getElementById("pos_cfg_ticket_validar").addEventListener("click", validarTicket);
        document.getElementById("pos_cfg_caja_guardar").addEventListener("click", guardarCaja);
        document.getElementById("pos_cfg_terminal_guardar").addEventListener("click", guardarTerminal);
        document.getElementById("pos_cfg_asig_guardar").addEventListener("click", guardarAsignacion);
        document.getElementById("pos_cfg_pinv_guardar").addEventListener("click", guardarPoliticaInventario);
        document.getElementById("pos_cfg_ticket_guardar").addEventListener("click", guardarTicket);
        document.getElementById("pos_cfg_limpiar").addEventListener("click", limpiarCaptura);
        document.addEventListener("click", function (event) {
            var filtroBoton = event.target.closest("[data-pos-filtro]");
            var editarBoton = event.target.closest("[data-pos-config-editar]");
            var desactivarBoton = event.target.closest("[data-pos-config-desactivar]");
            if (filtroBoton) {
                var grupo = filtroBoton.getAttribute("data-pos-filtro");
                var valor = filtroBoton.getAttribute("data-valor");
                filtros[grupo] = valor;
                actualizarBotonesFiltro(grupo, valor);
                render(estado);
                return;
            }
            if (editarBoton) {
                editar(editarBoton.getAttribute("data-pos-config-editar"), editarBoton.getAttribute("data-id"));
            }
            if (desactivarBoton) {
                desactivar(desactivarBoton.getAttribute("data-pos-config-desactivar"), desactivarBoton.getAttribute("data-id"));
            }
        });
    });
})();
