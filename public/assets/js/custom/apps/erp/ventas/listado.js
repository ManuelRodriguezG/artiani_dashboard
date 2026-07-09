"use strict";
(function () {
    var temporizador = null;
    var ticketActual = "";

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-26
     * Proposito: llamar endpoints ERP read-only del tablero de ventas.
     * Impacto: evita consumir endpoints legacy ecommerce desde la nueva seccion.
     */
    function request(url) {
        return fetch(url, {credentials: "same-origin"}).then(function (response) { return response.json(); });
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-26
     * Proposito: escapar texto renderizado en tabla de ventas.
     * Impacto: protege la UI ante folios/clientes capturados por usuarios.
     */
    function escapeHtml(value) {
        var div = document.createElement("div");
        div.textContent = value == null ? "" : String(value);
        return div.innerHTML;
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-26
     * Proposito: mostrar importes de ventas en MXN.
     * Impacto: mantiene lectura consistente del tablero operativo.
     */
    function dinero(value) {
        return new Intl.NumberFormat("es-MX", {style: "currency", currency: "MXN"}).format(Number(value || 0));
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-26
     * Proposito: obtener filtros actuales del tablero.
     * Impacto: centraliza parametros para listado ERP.
     */
    function filtros() {
        return {
            tipo: document.getElementById("ventas_filtro_tipo").value,
            estatus: document.getElementById("ventas_filtro_estatus").value,
            q: document.getElementById("ventas_filtro_q").value.trim(),
            limite: "50"
        };
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-26
     * Proposito: cargar contadores del modulo Ventas/POS.
     * Impacto: muestra si el esquema nuevo ya esta listo para operar.
     */
    function cargarResumen() {
        request("/ventas/ventas_resumen_erp").then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            var data = response.depurar || {};
            document.getElementById("ventas_kpi_hoy").textContent = Number(data.ventas_hoy || 0);
            document.getElementById("ventas_kpi_total").textContent = dinero(data.total_hoy || 0);
            document.getElementById("ventas_kpi_pedidos").textContent = Number(data.pedidos_abiertos || 0);
            document.getElementById("ventas_kpi_turnos").textContent = Number(data.turnos_abiertos || 0);
            renderAlertaSchema(data.schema_pendiente);
        }).catch(mostrarError);
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-26
     * Proposito: listar ventas/pedidos ERP filtrados.
     * Impacto: reemplaza el listado gigante legacy por una tabla operativa sencilla.
     */
    function cargarListado() {
        var params = new URLSearchParams(filtros());
        request("/ventas/ventas_listar_erp?" + params.toString()).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            var data = response.depurar || {};
            renderAlertaSchema(data.schema_pendiente);
            renderVentas(data.ventas || []);
        }).catch(mostrarError);
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-26
     * Proposito: mostrar plan dry-run de cajas iniciales por tienda.
     * Impacto: prepara autorizacion de caja POS sin escribir BD.
     */
    function cargarPlanCajas() {
        request("/ventas/cajas_plan_inicial_erp").then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            renderPlanCajas(((response.depurar || {}).propuestas) || []);
            llenarAlmacenesTurno(((response.depurar || {}).propuestas) || []);
        }).catch(mostrarError);
    }

    function renderPlanCajas(items) {
        document.getElementById("ventas_cajas_plan").innerHTML = items.map(function (item) {
            var caja = item.caja_sugerida || {};
            var metodos = [];
            if (Number(caja.permite_efectivo || 0)) { metodos.push("Efectivo"); }
            if (Number(caja.permite_tarjeta || 0)) { metodos.push("Tarjeta"); }
            if (Number(caja.permite_transferencia || 0)) { metodos.push("Transferencia"); }
            return "<tr><td><div class=\"fw-bold\">" + escapeHtml(item.codigo_almacen || "") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.nombre_comercial || item.almacen || "") + "</div></td>" +
                "<td><div class=\"fw-semibold\">" + escapeHtml(caja.codigo || "") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(caja.nombre || "") + "</div></td>" +
                "<td>" + metodos.map(function (metodo) { return "<span class=\"badge badge-light me-1\">" + escapeHtml(metodo) + "</span>"; }).join("") + "</td>" +
                "<td>" + (item.crear ? "<span class=\"badge badge-light-warning\">Por crear</span>" : "<span class=\"badge badge-light-success\">Existe</span>") + "</td></tr>";
        }).join("") || "<tr><td colspan=\"4\" class=\"text-center text-muted py-6\">Sin tiendas POS para preparar</td></tr>";
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-26
     * Proposito: llenar almacenes para simulacion de apertura/cierre de turno.
     * Impacto: permite validar contratos de caja desde tablero sin escribir BD.
     */
    function llenarAlmacenesTurno(items) {
        var select = document.getElementById("ventas_turno_almacen");
        select.innerHTML = items.map(function (item) {
            return "<option value=\"" + item.id_almacen + "\">" + escapeHtml(item.codigo_almacen || "") + "</option>";
        }).join("") || "<option value=\"\">Sin POS</option>";
    }

    function postRequest(url, data) {
        return fetch(url, {
            method: "POST",
            headers: {"Content-Type": "application/x-www-form-urlencoded; charset=UTF-8", "X-CSRF-Token": window.ERP_CSRF_TOKEN || ""},
            body: new URLSearchParams(data).toString(),
            credentials: "same-origin"
        }).then(function (response) { return response.json(); });
    }

    function parseMonto(value) {
        var numero = Number(String(value || "0").replace(",", "."));
        return Number.isFinite(numero) ? numero : 0;
    }

    function simularApertura() {
        postRequest("/ventas/turno_apertura_dryrun_erp", {
            id_almacen: document.getElementById("ventas_turno_almacen").value,
            id_caja: document.getElementById("ventas_turno_caja").value,
            monto_inicial: parseMonto(document.getElementById("ventas_turno_inicial").value)
        }).then(renderTurnoResultado).catch(mostrarError);
    }

    function simularCierre() {
        postRequest("/ventas/turno_cierre_dryrun_erp", {
            id_almacen: document.getElementById("ventas_turno_almacen").value,
            id_caja: document.getElementById("ventas_turno_caja").value,
            id_turno_caja: document.getElementById("ventas_turno_id").value,
            monto_esperado: parseMonto(document.getElementById("ventas_turno_esperado").value),
            monto_contado: parseMonto(document.getElementById("ventas_turno_contado").value)
        }).then(renderTurnoResultado).catch(mostrarError);
    }

    function renderTurnoResultado(response) {
        if (response.error) { throw new Error(response.mensaje); }
        var data = response.depurar || {};
        var bloqueos = data.bloqueos || [];
        var html = "<div class=\"alert " + (bloqueos.length ? "alert-warning" : "alert-success") + " py-3 mb-0\"><div class=\"fw-bold\">" + escapeHtml(response.mensaje || "") + "</div>";
        if (data.folio_sugerido) { html += "<div class=\"fs-8\">Folio sugerido: " + escapeHtml(data.folio_sugerido) + "</div>"; }
        if (data.diferencia != null) { html += "<div class=\"fs-8\">Diferencia: " + dinero(data.diferencia || 0) + "</div>"; }
        if (bloqueos.length) { html += "<ul class=\"mb-0 ps-4\">" + bloqueos.map(function (item) { return "<li>" + escapeHtml(item) + "</li>"; }).join("") + "</ul>"; }
        html += "</div>";
        document.getElementById("ventas_turno_resultado").innerHTML = html;
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-26
     * Proposito: simular cancelacion/devolucion sin afectar venta ni inventario.
     * Impacto: valida contrato de reversa controlada desde el tablero.
     */
    function simularDevolucion() {
        postRequest("/ventas/devolucion_dryrun_erp", {
            tipo: document.getElementById("ventas_dev_tipo").value,
            folio: document.getElementById("ventas_dev_folio").value.trim(),
            decision_inventario: document.getElementById("ventas_dev_decision").value,
            motivo: document.getElementById("ventas_dev_motivo").value.trim(),
            items: JSON.stringify([])
        }).then(renderDevolucionResultado).catch(mostrarError);
    }

    function renderDevolucionResultado(response) {
        if (response.error) { throw new Error(response.mensaje); }
        var data = response.depurar || {};
        var bloqueos = data.bloqueos || [];
        var html = "<div class=\"alert " + (bloqueos.length ? "alert-warning" : "alert-success") + " py-3 mb-0\"><div class=\"fw-bold\">" + escapeHtml(response.mensaje || "") + "</div>";
        html += "<div class=\"fs-8\">Tipo: " + escapeHtml(data.tipo || "") + " | Decision: " + escapeHtml(data.decision_inventario || "") + "</div>";
        if (bloqueos.length) { html += "<ul class=\"mb-0 ps-4\">" + bloqueos.map(function (item) { return "<li>" + escapeHtml(item) + "</li>"; }).join("") + "</ul>"; }
        html += "</div>";
        document.getElementById("ventas_dev_resultado").innerHTML = html;
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-26
     * Proposito: explicar en UI cuando falta autorizar/crear esquema.
     * Impacto: evita que el usuario confunda ausencia de ventas con error operativo.
     */
    function renderAlertaSchema(pendiente) {
        document.getElementById("ventas_alerta").innerHTML = pendiente ? "<div class=\"alert alert-warning d-flex align-items-center gap-3 py-3\"><i class=\"bi bi-database-exclamation fs-2\"></i><div><div class=\"fw-bold\">Esquema Ventas/POS pendiente</div><div class=\"fs-7\">La navegacion ERP ya esta lista, pero falta autorizar respaldo externo y creacion de tablas para cobrar, reservar, generar folios y kardex.</div></div></div>" : "";
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-26
     * Proposito: convertir el estatus de venta en etiqueta legible.
     * Impacto: ayuda al operador a escanear pedidos y ventas.
     */
    function badgeEstatus(estatus) {
        var clases = {
            borrador: "badge-light",
            reservado: "badge-light-warning",
            pendiente_pago: "badge-light-info",
            pagado: "badge-light-success",
            entregado: "badge-light-primary",
            cancelado: "badge-light-danger"
        };
        return "<span class=\"badge " + (clases[estatus] || "badge-light") + "\">" + escapeHtml(estatus || "sin estatus") + "</span>";
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-26
     * Proposito: renderizar filas del tablero de ventas ERP.
     * Impacto: no muestra datos legacy ni acciones destructivas.
     */
    function renderVentas(items) {
        var tbody = document.getElementById("ventas_listado");
        document.getElementById("ventas_vacio").classList.toggle("d-none", items.length > 0);
        document.getElementById("ventas_vacio").classList.toggle("d-flex", items.length === 0);
        tbody.innerHTML = items.map(function (item) {
            var folio = escapeHtml(item.folio || "");
            return "<tr>" +
                "<td><div class=\"fw-bold\">" + folio + "</div><div class=\"text-muted fs-8\">#" + escapeHtml(item.id_venta) + "</div></td>" +
                "<td><div class=\"fw-semibold\">" + escapeHtml(item.tipo_documento) + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.canal) + "</div></td>" +
                "<td>" + escapeHtml(item.cliente_nombre_publico || "Publico general") + "</td>" +
                "<td>" + escapeHtml(item.almacen || "-") + "</td>" +
                "<td>" + Number(item.partidas || 0) + "</td>" +
                "<td class=\"fw-bold\">" + dinero(item.total || 0) + "</td>" +
                "<td>" + badgeEstatus(item.estatus) + "</td>" +
                "<td><div>" + escapeHtml(item.fecha_venta || "") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.fecha_entrega_compromiso || "") + "</div></td>" +
                "<td class=\"text-end\"><div class=\"d-inline-flex gap-2\">" +
                    "<a class=\"btn btn-sm btn-icon btn-light\" href=\"/ventas/venta_detalle?folio=" + encodeURIComponent(item.folio || "") + "\" title=\"Detalle\"><i class=\"bi bi-eye\"></i></a>" +
                    "<button class=\"btn btn-sm btn-icon btn-light-primary\" data-ticket-folio=\"" + folio + "\" type=\"button\" title=\"Ticket\"><i class=\"bi bi-receipt\"></i></button>" +
                    "<a class=\"btn btn-sm btn-icon btn-light-warning\" href=\"/ventas/devoluciones?folio=" + encodeURIComponent(item.folio || "") + "\" title=\"Simular devolucion\"><i class=\"bi bi-arrow-counterclockwise\"></i></a>" +
                "</div></td>" +
                "</tr>";
        }).join("");
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-27
     * Proposito: consultar ticket formal POS sin modificar venta ni reabrir turno.
     * Impacto: habilita reimpresion operativa con snapshot de precio/garantia cuando exista.
     */
    function abrirTicket(folio) {
        if (!folio) { return; }
        ticketActual = "";
        document.getElementById("ventas_ticket_subtitulo").textContent = folio;
        document.getElementById("ventas_ticket_alerta").innerHTML = "<div class=\"alert alert-info py-3\">Consultando ticket...</div>";
        document.getElementById("ventas_ticket_texto").textContent = "";
        var modal = bootstrap.Modal.getOrCreateInstance(document.getElementById("ventas_ticket_modal"));
        modal.show();
        request("/ventas/ticket_venta_readonly_erp?folio=" + encodeURIComponent(folio)).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            var data = response.depurar || {};
            ticketActual = data.ticket_texto || "";
            document.getElementById("ventas_ticket_texto").textContent = ticketActual;
            renderTicketAlertas(data.hallazgos || []);
        }).catch(function (error) {
            document.getElementById("ventas_ticket_alerta").innerHTML = "<div class=\"alert alert-danger py-3\">" + escapeHtml(error.message || String(error)) + "</div>";
        });
    }

    function renderTicketAlertas(hallazgos) {
        if (!hallazgos.length) {
            document.getElementById("ventas_ticket_alerta").innerHTML = "<div class=\"alert alert-success py-3\">Ticket listo para imprimir</div>";
            return;
        }
        document.getElementById("ventas_ticket_alerta").innerHTML = "<div class=\"alert alert-warning py-3 mb-4\"><div class=\"fw-bold\">Ticket con observaciones</div><ul class=\"mb-0 ps-4\">" +
            hallazgos.map(function (item) { return "<li>" + escapeHtml(item.id || "") + ": " + escapeHtml(item.mensaje || "") + "</li>"; }).join("") +
            "</ul></div>";
    }

    function imprimirTicket() {
        if (!ticketActual) { return; }
        var ventana = window.open("", "erp_pos_ticket", "width=420,height=720");
        if (!ventana) { return; }
        ventana.document.write("<!doctype html><html><head><title>Ticket POS</title><style>body{font-family:Consolas,'Liberation Mono',monospace;font-size:12px;white-space:pre-wrap;margin:12px;color:#111;}@media print{body{margin:0;}}</style></head><body>");
        ventana.document.write(escapeHtml(ticketActual));
        ventana.document.write("</body></html>");
        ventana.document.close();
        ventana.focus();
        ventana.print();
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-26
     * Proposito: mostrar errores de consulta del tablero.
     * Impacto: conserva feedback operativo sin interrumpir navegacion.
     */
    function mostrarError(error) {
        document.getElementById("ventas_alerta").innerHTML = "<div class=\"alert alert-danger py-3\">" + escapeHtml(error.message || String(error)) + "</div>";
    }

    document.addEventListener("DOMContentLoaded", function () {
        var tipoInicial = document.getElementById("ventas_tipo_inicial").value;
        var params = new URLSearchParams(window.location.search || "");
        var folioInicial = params.get("folio") || "";
        if (tipoInicial) {
            document.getElementById("ventas_filtro_tipo").value = tipoInicial;
        }
        if (folioInicial) {
            document.getElementById("ventas_filtro_q").value = folioInicial;
        }
        cargarResumen();
        cargarListado();
        document.getElementById("ventas_recargar").addEventListener("click", function () {
            cargarResumen();
            cargarListado();
        });
        document.getElementById("ventas_ticket_imprimir").addEventListener("click", imprimirTicket);
        document.getElementById("ventas_listado").addEventListener("click", function (event) {
            var boton = event.target.closest("[data-ticket-folio]");
            if (boton) {
                abrirTicket(boton.getAttribute("data-ticket-folio"));
            }
        });
        ["ventas_filtro_tipo", "ventas_filtro_estatus"].forEach(function (id) {
            document.getElementById(id).addEventListener("change", cargarListado);
        });
        document.getElementById("ventas_filtro_q").addEventListener("input", function () {
            clearTimeout(temporizador);
            temporizador = setTimeout(cargarListado, 250);
        });
    });
})();
