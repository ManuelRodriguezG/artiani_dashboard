"use strict";
(function () {
    var estado = {};
    var denominaciones = [1000, 500, 200, 100, 50, 20, 10, 5, 2, 1, 0.5];
    var corteActual = "";

    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-04
     * Proposito: consumir endpoints read-only/dry-run de Caja POS y calcular arqueo operativo.
     * Impacto: separa apertura, corte, arqueo y supervision de turnos del POS de cobro.
     */
    function request(url, data) {
        var opciones = {credentials: "same-origin"};
        if (data) {
            opciones.method = "POST";
            opciones.headers = {"Content-Type": "application/x-www-form-urlencoded; charset=UTF-8", "X-CSRF-Token": window.ERP_CSRF_TOKEN || ""};
            opciones.body = new URLSearchParams(data).toString();
        }
        return fetch(url, opciones).then(function (response) { return response.json(); });
    }

    function escapeHtml(value) {
        var div = document.createElement("div");
        div.textContent = value == null ? "" : String(value);
        return div.innerHTML;
    }

    function dinero(value) {
        return new Intl.NumberFormat("es-MX", {style: "currency", currency: "MXN"}).format(Number(value || 0));
    }

    function monto(value) {
        var numero = Number(String(value || "0").replace(",", "."));
        return Number.isFinite(numero) ? numero : 0;
    }

    function cargar() {
        Promise.all([
            request("/ventas/pos_configuracion_resumen_erp"),
            request("/ventas/terminal_asignacion_actual_erp").catch(function () { return {error: true, depurar: {}}; })
        ]).then(function (responses) {
            var response = responses[0];
            var asignacionResponse = responses[1] || {};
            if (response.error) { throw new Error(response.mensaje); }
            estado = response.depurar || {};
            aplicarAsignacionActual(asignacionResponse.depurar || {});
            render();
        }).catch(mostrarError);
    }

    function aplicarAsignacionActual(data) {
        var asignacion = data.asignacion || {};
        estado.contexto = {
            id_almacen: Number(asignacion.id_almacen || 0),
            id_caja: Number(asignacion.id_caja || 0),
            id_terminal_pos: Number(asignacion.id_terminal_pos || 0),
            asignacion_activa: Boolean(data.asignacion_activa),
            turno_abierto: Boolean(data.turno_abierto)
        };
    }

    function render() {
        var resumen = estado.resumen || {};
        document.getElementById("pos_caja_kpi_cajas").textContent = Number(resumen.cajas || 0);
        document.getElementById("pos_caja_kpi_turnos").textContent = Number(resumen.turnos_abiertos || 0);
        document.getElementById("pos_caja_kpi_movimientos").textContent = Number(resumen.movimientos_recientes || 0);
        document.getElementById("pos_caja_alerta").innerHTML = estado.schema_pendiente
            ? "<div class=\"alert alert-warning py-3\"><div class=\"fw-bold\">Configuracion POS incompleta</div><div class=\"fs-7\">Faltan tablas o columnas para operar caja productiva. Esta vista solo consulta y simula.</div></div>"
            : "<div class=\"alert alert-info py-3\"><div class=\"fw-bold\">Caja separada del POS</div><div class=\"fs-7\">Esta pantalla abre/cierra turnos con dry-run previo, caja asignada y confirmacion escrita.</div></div>";
        llenarSelectores();
        renderDenominaciones();
        calcularArqueo();
        renderTurnos();
        renderMovimientos();
    }

    function llenarSelectores() {
        var almacenes = estado.almacenes || [];
        var cajas = estado.cajas || [];
        var turnos = estado.turnos_abiertos || [];
        document.getElementById("pos_caja_almacen").innerHTML = almacenes.map(function (item) {
            return "<option value=\"" + escapeHtml(item.id_almacen) + "\">" + escapeHtml(item.codigo_almacen || item.almacen || item.id_almacen) + "</option>";
        }).join("") || "<option value=\"\">Sin almacenes</option>";
        document.getElementById("pos_caja_caja").innerHTML = cajas.map(function (item) {
            return "<option value=\"" + escapeHtml(item.id_caja) + "\" data-almacen=\"" + escapeHtml(item.id_almacen) + "\">" + escapeHtml(item.codigo || item.nombre || item.id_caja) + "</option>";
        }).join("") || "<option value=\"\">Sin cajas</option>";
        document.getElementById("pos_caja_apertura_almacen").innerHTML = document.getElementById("pos_caja_almacen").innerHTML;
        document.getElementById("pos_caja_apertura_caja").innerHTML = document.getElementById("pos_caja_caja").innerHTML;
        aplicarContextoApertura();
        document.getElementById("pos_caja_turno").innerHTML = turnos.map(function (item) {
            return "<option value=\"" + escapeHtml(item.id_turno_caja) + "\" data-caja=\"" + escapeHtml(item.id_caja) + "\" data-almacen=\"" + escapeHtml(item.id_almacen) + "\" data-inicial=\"" + escapeHtml(item.monto_inicial || 0) + "\" data-esperado=\"" + escapeHtml(item.monto_esperado || item.monto_inicial || 0) + "\">" + escapeHtml(item.folio || item.id_turno_caja) + "</option>";
        }).join("") || "<option value=\"\">Sin turnos abiertos</option>";
        sincronizarDesdeTurno();
        sugerirArqueoDesdeEsperado();
    }

    function aplicarContextoApertura() {
        var contexto = estado.contexto || {};
        if (contexto.id_almacen) {
            document.getElementById("pos_caja_apertura_almacen").value = contexto.id_almacen;
        }
        if (contexto.id_caja) {
            document.getElementById("pos_caja_apertura_caja").value = contexto.id_caja;
        } else {
            sincronizarAperturaCaja();
        }
    }

    function sincronizarDesdeTurno() {
        var turno = document.getElementById("pos_caja_turno").selectedOptions[0];
        if (!turno) { return; }
        if (turno.getAttribute("data-almacen")) {
            document.getElementById("pos_caja_almacen").value = turno.getAttribute("data-almacen");
        }
        if (turno.getAttribute("data-caja")) {
            document.getElementById("pos_caja_caja").value = turno.getAttribute("data-caja");
        }
    }

    function renderDenominaciones() {
        var contenedor = document.getElementById("pos_caja_denominaciones");
        if (!contenedor || contenedor.childElementCount) { return; }
        contenedor.innerHTML = denominaciones.map(function (denominacion) {
            var id = "pos_caja_denom_" + String(denominacion).replace(".", "_");
            return "<div class=\"pos-denom-row\">" +
                "<label class=\"form-label fs-8 text-muted mb-0\" for=\"" + id + "\">" + dinero(denominacion) + "</label>" +
                "<input class=\"form-control form-control-solid text-end pos-caja-denom\" id=\"" + id + "\" data-denominacion=\"" + denominacion + "\" inputmode=\"numeric\" value=\"0\">" +
                "<div class=\"fw-bold pos-denom-total\" data-total-denominacion=\"" + denominacion + "\">$0.00</div>" +
                "</div>";
        }).join("");
    }

    function calcularArqueo() {
        var total = 0;
        document.querySelectorAll(".pos-caja-denom").forEach(function (input) {
            var denominacion = Number(input.getAttribute("data-denominacion") || 0);
            var piezas = Math.max(0, Math.floor(monto(input.value)));
            if (String(input.value) !== String(piezas)) {
                input.value = String(piezas);
            }
            var subtotal = denominacion * piezas;
            total += subtotal;
            var totalEl = document.querySelector("[data-total-denominacion=\"" + denominacion + "\"]");
            if (totalEl) { totalEl.textContent = dinero(subtotal); }
        });
        document.querySelectorAll(".pos-caja-arqueo-extra").forEach(function (input) {
            total += Math.max(0, monto(input.value));
        });
        document.getElementById("pos_caja_arqueo_total").textContent = dinero(total);
        document.getElementById("pos_caja_monto_contado").value = total.toFixed(2);
        return total;
    }

    function sugerirArqueoDesdeEsperado() {
        var turno = document.getElementById("pos_caja_turno").selectedOptions[0];
        if (!turno || !turno.value) { return; }
        var esperado = monto(turno.getAttribute("data-esperado") || turno.getAttribute("data-inicial") || 0);
        document.querySelectorAll(".pos-caja-denom").forEach(function (input) { input.value = "0"; });
        document.querySelectorAll(".pos-caja-arqueo-extra").forEach(function (input) { input.value = "0"; });
        var restante = Math.round(esperado * 100);
        denominaciones.forEach(function (denominacion) {
            var centavos = Math.round(denominacion * 100);
            var piezas = Math.floor(restante / centavos);
            restante -= piezas * centavos;
            var id = "pos_caja_denom_" + String(denominacion).replace(".", "_");
            var input = document.getElementById(id);
            if (input) { input.value = String(piezas); }
        });
        calcularArqueo();
    }

    function sincronizarAperturaCaja() {
        var almacen = document.getElementById("pos_caja_apertura_almacen").value;
        var caja = document.getElementById("pos_caja_apertura_caja");
        var actual = caja.selectedOptions[0];
        if (actual && (!almacen || actual.getAttribute("data-almacen") === almacen)) {
            return;
        }
        for (var i = 0; i < caja.options.length; i += 1) {
            if (!almacen || caja.options[i].getAttribute("data-almacen") === almacen) {
                caja.value = caja.options[i].value;
                return;
            }
        }
    }

    function simularApertura() {
        request("/ventas/turno_apertura_dryrun_erp", {
            id_almacen: document.getElementById("pos_caja_apertura_almacen").value,
            id_caja: document.getElementById("pos_caja_apertura_caja").value,
            monto_inicial: monto(document.getElementById("pos_caja_monto_inicial").value)
        }).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            var data = response.depurar || {};
            var bloqueos = data.bloqueos || [];
            var html = "<div class=\"alert " + (bloqueos.length ? "alert-warning" : "alert-success") + " py-3\"><div class=\"fw-bold\">" + escapeHtml(response.mensaje || "Apertura validada") + "</div><div class=\"fs-8 text-muted\">El dry-run no creo turno ni movimiento de caja.</div>";
            html += "<div class=\"fs-8\">Monto inicial: " + dinero(data.monto_inicial || 0) + " | Folio sugerido: " + escapeHtml(data.folio_sugerido || "-") + "</div>";
            if (bloqueos.length) {
                html += "<ul class=\"mb-0 mt-2 ps-4\">" + bloqueos.map(function (item) { return "<li>" + escapeHtml(item) + "</li>"; }).join("") + "</ul>";
            } else {
                html += renderAperturaRealControlada(data);
            }
            html += "</div>";
            document.getElementById("pos_caja_apertura_resultado").innerHTML = html;
        }).catch(function (error) {
            document.getElementById("pos_caja_apertura_resultado").innerHTML = "<div class=\"alert alert-warning py-3\">" + escapeHtml(error.message || String(error)) + "</div>";
        });
    }

    function renderAperturaRealControlada(data) {
        var montoInicial = Number(data.monto_inicial || 0);
        var idAlmacen = data.id_almacen || document.getElementById("pos_caja_apertura_almacen").value || "";
        var idCaja = data.id_caja || document.getElementById("pos_caja_apertura_caja").value || "";
        return "<div class=\"separator my-3\"></div>" +
            "<div class=\"border rounded bg-white p-3\">" +
            "<div class=\"d-flex justify-content-between align-items-start gap-3 mb-3\">" +
            "<div><div class=\"fw-bold\">Apertura real de turno</div><div class=\"text-muted fs-8\">Valida que sea la caja correcta y confirma para iniciar ventas.</div></div>" +
            "<span class=\"badge badge-light-success\">" + dinero(montoInicial) + "</span>" +
            "</div>" +
            "<div class=\"row g-2 mb-3\">" +
            resumenCaja("Almacen", idAlmacen) +
            resumenCaja("Caja", idCaja) +
            "</div>" +
            "<label class=\"form-label fs-8 text-muted text-uppercase\">Observaciones de apertura</label>" +
            "<input class=\"form-control form-control-solid mb-2\" id=\"pos_caja_apertura_observaciones\" value=\"Apertura POS desde Caja/Turnos\">" +
            "<label class=\"form-label fs-8 text-muted text-uppercase\">Confirmacion</label>" +
            "<input class=\"form-control form-control-solid mb-3\" id=\"pos_caja_apertura_confirmacion\" placeholder=\"Escribe ABRIR TURNO\">" +
            "<button class=\"btn btn-danger w-100\" id=\"pos_caja_apertura_real\" type=\"button\"><i class=\"bi bi-lock-fill\"></i> Abrir turno real</button>" +
            "<div class=\"text-muted fs-8 mt-2 text-center\">La apertura queda ligada al usuario actual, caja oficial y movimiento inicial.</div>" +
            "</div>";
    }

    function renderTurnos() {
        var filas = estado.turnos_abiertos || [];
        document.getElementById("pos_caja_turnos_tabla").innerHTML = filas.map(function (item) {
            return "<tr><td><div class=\"fw-bold\">" + escapeHtml(item.folio || "-") + "</div><div class=\"text-muted fs-8\">#" + escapeHtml(item.id_turno_caja || "-") + "</div></td>" +
                "<td>" + escapeHtml(item.id_caja || "-") + "</td><td>" + escapeHtml(item.id_almacen || "-") + "</td>" +
                "<td>" + dinero(item.monto_inicial || 0) + "</td><td>" + escapeHtml(item.fecha_apertura || "-") + "</td></tr>";
        }).join("") || "<tr><td colspan=\"5\" class=\"text-center text-muted py-6\">No hay turnos abiertos</td></tr>";
    }

    function renderMovimientos() {
        var filas = estado.movimientos_recientes || [];
        document.getElementById("pos_caja_movimientos_tabla").innerHTML = filas.map(function (item) {
            return "<tr><td>" + escapeHtml(item.fecha_registro || "-") + "</td><td>" + escapeHtml(item.turno_folio || item.id_turno_caja || "-") + "</td>" +
                "<td>" + escapeHtml(item.tipo || "-") + "</td><td><div class=\"fw-semibold\">" + escapeHtml(item.motivo || "-") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.referencia || "") + "</div></td>" +
                "<td class=\"text-end fw-bold\">" + dinero(item.monto || 0) + "</td></tr>";
        }).join("") || "<tr><td colspan=\"5\" class=\"text-center text-muted py-6\">Sin movimientos recientes</td></tr>";
    }

    function simularCorte() {
        calcularArqueo();
        request("/ventas/turno_cierre_dryrun_erp", {
            id_almacen: document.getElementById("pos_caja_almacen").value,
            id_caja: document.getElementById("pos_caja_caja").value,
            id_turno_caja: document.getElementById("pos_caja_turno").value,
            monto_contado: monto(document.getElementById("pos_caja_monto_contado").value)
        }).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            var data = response.depurar || {};
            var bloqueos = data.bloqueos || [];
            var diferencia = Number(data.diferencia || 0);
            var html = "<div class=\"alert " + (bloqueos.length ? "alert-warning" : "alert-success") + " py-3\"><div class=\"fw-bold\">" + escapeHtml(response.mensaje || "Corte simulado") + "</div><div class=\"fs-8 text-muted\">No se cerro el turno real.</div>";
            html += "<div class=\"fs-8\">Esperado: " + dinero(data.monto_esperado || 0) + " | Contado: " + dinero(data.monto_contado || 0) + " | Diferencia: " + dinero(data.diferencia || 0) + "</div>";
            html += renderResumenArqueo();
            if (!bloqueos.length && Math.abs(diferencia) > 0.0001) {
                html += "<div class=\"alert alert-warning py-2 mt-3 mb-0 fs-8\">Este cierre tiene " + (diferencia > 0 ? "sobrante" : "faltante") + ". Se puede cerrar, pero quedara registrado para revision y reportes.</div>";
            }
            if (bloqueos.length) {
                html += "<ul class=\"mb-0 mt-2 ps-4\">" + bloqueos.map(function (item) { return "<li>" + escapeHtml(item) + "</li>"; }).join("") + "</ul>";
            } else {
                html += renderCierreRealControlado(data);
            }
            html += "</div>";
            document.getElementById("pos_caja_corte_resultado").innerHTML = html;
        }).catch(function (error) {
            document.getElementById("pos_caja_corte_resultado").innerHTML = "<div class=\"alert alert-warning py-3\">" + escapeHtml(error.message || String(error)) + "</div>";
        });
    }

    function renderResumenArqueo() {
        var efectivo = 0;
        document.querySelectorAll(".pos-caja-denom").forEach(function (input) {
            efectivo += Number(input.getAttribute("data-denominacion") || 0) * Math.max(0, Math.floor(monto(input.value)));
        });
        var tarjeta = Math.max(0, monto(document.getElementById("pos_caja_arqueo_tarjeta").value));
        var transferencia = Math.max(0, monto(document.getElementById("pos_caja_arqueo_transferencia").value));
        var vales = Math.max(0, monto(document.getElementById("pos_caja_arqueo_vales").value));
        return "<div class=\"row g-2 mt-3\">" +
            resumenCaja("Efectivo", efectivo) +
            resumenCaja("Tarjeta", tarjeta) +
            resumenCaja("Transferencia", transferencia) +
            resumenCaja("Vales/otros", vales) +
            "<div class=\"col-12\"><div class=\"text-muted fs-8\">El saldo cliente CRM no se captura en arqueo porque no entra a caja.</div></div>" +
            "</div>";
    }

    function resumenCaja(label, value) {
        var texto = typeof value === "number" ? dinero(value) : String(value == null ? "" : value);
        return "<div class=\"col-6\"><div class=\"border rounded p-2 bg-white\"><div class=\"text-muted fs-8 text-uppercase\">" + escapeHtml(label) + "</div><div class=\"fw-bold\">" + escapeHtml(texto) + "</div></div></div>";
    }

    function renderAutorizacionCierre(data) {
        var turno = data.turno || {};
        var folio = turno.folio || "TURNO";
        var montoContado = Number(data.monto_contado || 0);
        var usuario = (window.POS_USUARIO_ACTUAL || {}).id_usuario || "";
        var texto = "AUTORIZO CERRAR TURNO POS UAT REAL usando respaldo UAT POS vigente con id_usuario=" + usuario + " monto_contado=" + montoContado.toFixed(0) + " observaciones=\"Cierre UAT POS " + folio + "\"";
        return "<div class=\"separator my-3\"></div>" +
            "<div class=\"fw-bold mb-2\">Siguiente paso si quieres cerrar real</div>" +
            "<div class=\"fs-8 text-muted mb-2\">Copia esta autorizacion en el chat. No se ejecuta desde esta pantalla. Si hay diferencia, se guardara para reportes.</div>" +
            "<div class=\"d-flex justify-content-end mb-2\"><button type=\"button\" class=\"btn btn-sm btn-light-primary\" data-copy-autorizacion=\"" + escapeHtml(texto) + "\">Copiar autorizacion</button></div>" +
            "<pre class=\"bg-light p-3 rounded fs-8 mb-0\" style=\"white-space: pre-wrap;\">" + escapeHtml(texto) + "</pre>";
    }

    function renderCierreRealControlado(data) {
        var turno = data.turno || {};
        var folio = turno.folio || "TURNO";
        var montoContado = Number(data.monto_contado || 0);
        var diferencia = Number(data.diferencia || 0);
        var tono = Math.abs(diferencia) > 0.0001 ? "warning" : "success";
        return "<div class=\"separator my-3\"></div>" +
            "<div class=\"border rounded bg-white p-3\">" +
            "<div class=\"d-flex justify-content-between align-items-start gap-3 mb-3\">" +
            "<div><div class=\"fw-bold\">Cierre real de turno</div><div class=\"text-muted fs-8\">Valida el arqueo y confirma para cerrar. Esta accion escribe BD.</div></div>" +
            "<span class=\"badge badge-light-" + tono + "\">Diferencia " + dinero(diferencia) + "</span>" +
            "</div>" +
            "<div class=\"row g-2 mb-3\">" +
            resumenCaja("Turno", folio) +
            resumenCaja("Contado", montoContado) +
            "</div>" +
            "<label class=\"form-label fs-8 text-muted text-uppercase\">Observaciones de cierre</label>" +
            "<input class=\"form-control form-control-solid mb-2\" id=\"pos_caja_cierre_observaciones\" value=\"Cierre POS " + escapeHtml(folio) + "\">" +
            "<label class=\"form-label fs-8 text-muted text-uppercase\">Confirmacion</label>" +
            "<input class=\"form-control form-control-solid mb-3\" id=\"pos_caja_cierre_confirmacion\" placeholder=\"Escribe CERRAR TURNO\">" +
            "<button class=\"btn btn-danger w-100\" id=\"pos_caja_cierre_real\" type=\"button\" data-turno=\"" + escapeHtml(data.id_turno_caja || "") + "\"><i class=\"bi bi-lock-fill\"></i> Cerrar turno real</button>" +
            "<div class=\"text-muted fs-8 mt-2 text-center\">Si existe faltante o sobrante, se guarda en el turno para reportes y seguimiento.</div>" +
            "</div>";
    }

    function consultarReadiness() {
        request("/ventas/pos_readiness_readonly_erp", {
            id_usuario: (window.POS_USUARIO_ACTUAL || {}).id_usuario || "",
            id_almacen: document.getElementById("pos_caja_almacen").value,
            id_sku: document.getElementById("pos_caja_readiness_sku").value,
            cantidad: 1,
            precio: 295,
            monto_contado: monto(document.getElementById("pos_caja_readiness_contado").value),
            monto_abono: 100,
            folio_venta: document.getElementById("pos_caja_readiness_folio").value.trim(),
            folio_apartado: "APT-UAT-000001"
        }).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            document.getElementById("pos_caja_readiness_resultado").innerHTML = renderReadiness(response.depurar || {});
        }).catch(function (error) {
            document.getElementById("pos_caja_readiness_resultado").innerHTML = "<div class=\"alert alert-warning py-3\">" + escapeHtml(error.message || String(error)) + "</div>";
        });
    }

    function renderReadiness(data) {
        var resumen = data.resumen || {};
        var hallazgos = data.hallazgos || [];
        var contexto = data.contexto || {};
        var html = "<div class=\"alert alert-info py-3 mb-3\"><div class=\"fw-bold\">Readiness consultado</div><div class=\"fs-8 text-muted\">No se cerro turno, no se creo pedido, no se reservo inventario y no se genero kardex.</div></div>";
        html += "<div class=\"row g-2 mb-3\">";
        html += readinessKpi("Turno", contexto.turno_abierto ? "Abierto" : "Sin turno", contexto.turno_abierto ? "success" : "warning");
        html += readinessKpi("Diferencia", dinero(resumen.cierre_diferencia || 0), Number(resumen.cierre_diferencia || 0) === 0 ? "success" : "warning");
        html += readinessKpi("Ticket", String(resumen.ticket_lineas || 0) + " lineas", Number(resumen.ticket_lineas || 0) > 0 ? "success" : "warning");
        html += readinessKpi("Dev. fisicas", String(resumen.devoluciones_fisicas_pendientes || 0), Number(resumen.devoluciones_fisicas_pendientes || 0) > 0 ? "warning" : "success");
        html += "</div>";
        if (hallazgos.length) {
            html += "<div class=\"border rounded p-3\"><div class=\"fw-bold fs-7 mb-2\">Hallazgos</div><ul class=\"mb-0 ps-4\">" + hallazgos.map(function (item) {
                return "<li>" + escapeHtml(item) + "</li>";
            }).join("") + "</ul></div>";
        } else {
            html += "<div class=\"alert alert-success py-3 mb-0\">Sin hallazgos de readiness.</div>";
        }
        html += "<div class=\"text-muted fs-8 mt-3\">" + escapeHtml(data.siguiente_recomendado || "") + "</div>";
        if (contexto.turno_abierto && !(resumen.cierre_bloqueos || []).length) {
            html += renderAutorizacionReadiness(data);
        }
        return html;
    }

    function readinessKpi(label, value, tipo) {
        var badge = tipo === "success" ? "badge-light-success" : "badge-light-warning";
        return "<div class=\"col-6\"><div class=\"border rounded p-3 h-100\"><div class=\"text-muted fs-8 text-uppercase\">" + escapeHtml(label) + "</div><span class=\"badge " + badge + " mt-1\">" + escapeHtml(value) + "</span></div></div>";
    }

    function renderAutorizacionReadiness(data) {
        var detalle = data.detalle || {};
        var cierre = (detalle.cierre || {}).depurar || {};
        var turno = cierre.turno || {};
        var folio = turno.folio || "TURNO";
        var montoContado = Number(cierre.monto_contado || 0);
        var usuario = (window.POS_USUARIO_ACTUAL || {}).id_usuario || "";
        var texto = "AUTORIZO CERRAR TURNO POS UAT REAL usando respaldo UAT POS vigente con id_usuario=" + usuario + " monto_contado=" + montoContado.toFixed(0) + " observaciones=\"Cierre UAT POS readiness " + folio + "\"";
        return "<div class=\"separator my-3\"></div>" +
            "<div class=\"fw-bold mb-2\">Autorizacion sugerida para cierre real</div>" +
            "<div class=\"fs-8 text-muted mb-2\">Copiala al chat si quieres cerrar el turno. Esta pantalla no ejecuta el cierre. Las diferencias quedan registradas.</div>" +
            "<div class=\"d-flex justify-content-end mb-2\"><button type=\"button\" class=\"btn btn-sm btn-light-primary\" data-copy-autorizacion=\"" + escapeHtml(texto) + "\">Copiar autorizacion</button></div>" +
            "<pre class=\"bg-light p-3 rounded fs-8 mb-0\" style=\"white-space: pre-wrap;\">" + escapeHtml(texto) + "</pre>";
    }

    function copiarAutorizacion(event) {
        var boton = event.target.closest("[data-copy-autorizacion]");
        if (!boton) { return; }
        var texto = boton.getAttribute("data-copy-autorizacion") || "";
        if (!texto) { return; }
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(texto).then(function () {
                boton.textContent = "Copiado";
                setTimeout(function () { boton.textContent = "Copiar autorizacion"; }, 1500);
            }).catch(function () {
                boton.textContent = "Selecciona el texto";
            });
            return;
        }
        boton.textContent = "Selecciona el texto";
    }

    function cerrarTurnoReal(event) {
        var boton = event.target.closest("#pos_caja_cierre_real");
        if (!boton) { return; }
        var confirmacionEl = document.getElementById("pos_caja_cierre_confirmacion");
        var confirmacion = confirmacionEl ? confirmacionEl.value : "";
        if (confirmacion.trim().toUpperCase() !== "CERRAR TURNO") {
            document.getElementById("pos_caja_corte_resultado").insertAdjacentHTML("afterbegin", "<div class=\"alert alert-warning py-2\">Escribe CERRAR TURNO para confirmar el cierre real.</div>");
            return;
        }
        boton.disabled = true;
        boton.innerHTML = "<span class=\"spinner-border spinner-border-sm me-2\"></span>Cerrando turno";
        request("/ventas/turno_cierre_real_erp", {
            id_almacen: document.getElementById("pos_caja_almacen").value,
            id_caja: document.getElementById("pos_caja_caja").value,
            id_turno_caja: boton.getAttribute("data-turno") || document.getElementById("pos_caja_turno").value,
            monto_contado: calcularArqueo(),
            observaciones: (document.getElementById("pos_caja_cierre_observaciones") || {}).value || "",
            confirmacion: confirmacion
        }).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            var data = response.depurar || {};
            var bloqueos = data.bloqueos || [];
            var html = "<div class=\"alert " + (bloqueos.length ? "alert-warning" : "alert-success") + " py-3\"><div class=\"fw-bold\">" + escapeHtml(response.mensaje || "Cierre procesado") + "</div>";
            if (bloqueos.length) {
                html += "<ul class=\"mb-0 mt-2 ps-4\">" + bloqueos.map(function (item) { return "<li>" + escapeHtml(item) + "</li>"; }).join("") + "</ul>";
            } else {
                html += "<div class=\"fs-8\">Turno: " + escapeHtml(data.folio || "-") + " | Esperado: " + dinero(data.monto_esperado || 0) + " | Contado: " + dinero(data.monto_contado || 0) + " | Diferencia: " + dinero(data.diferencia || 0) + "</div>";
            }
            html += "</div>";
            document.getElementById("pos_caja_corte_resultado").innerHTML = html;
            cargar();
        }).catch(function (error) {
            boton.disabled = false;
            boton.innerHTML = "<i class=\"bi bi-lock-fill\"></i> Cerrar turno real";
            document.getElementById("pos_caja_corte_resultado").insertAdjacentHTML("afterbegin", "<div class=\"alert alert-danger py-2\">" + escapeHtml(error.message || String(error)) + "</div>");
        });
    }

    function abrirTurnoReal(event) {
        var boton = event.target.closest("#pos_caja_apertura_real");
        if (!boton) { return; }
        var confirmacionEl = document.getElementById("pos_caja_apertura_confirmacion");
        var confirmacion = confirmacionEl ? confirmacionEl.value : "";
        if (confirmacion.trim().toUpperCase() !== "ABRIR TURNO") {
            document.getElementById("pos_caja_apertura_resultado").insertAdjacentHTML("afterbegin", "<div class=\"alert alert-warning py-2\">Escribe ABRIR TURNO para confirmar la apertura real.</div>");
            return;
        }
        boton.disabled = true;
        boton.innerHTML = "<span class=\"spinner-border spinner-border-sm me-2\"></span>Abriendo turno";
        request("/ventas/turno_apertura_real_erp", {
            id_almacen: document.getElementById("pos_caja_apertura_almacen").value,
            id_caja: document.getElementById("pos_caja_apertura_caja").value,
            monto_inicial: monto(document.getElementById("pos_caja_monto_inicial").value),
            observaciones: (document.getElementById("pos_caja_apertura_observaciones") || {}).value || "",
            confirmacion: confirmacion
        }).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            var data = response.depurar || {};
            var bloqueos = data.bloqueos || [];
            var html = "<div class=\"alert " + (bloqueos.length ? "alert-warning" : "alert-success") + " py-3\"><div class=\"fw-bold\">" + escapeHtml(response.mensaje || "Apertura procesada") + "</div>";
            if (bloqueos.length) {
                html += "<ul class=\"mb-0 mt-2 ps-4\">" + bloqueos.map(function (item) { return "<li>" + escapeHtml(item) + "</li>"; }).join("") + "</ul>";
            } else {
                html += "<div class=\"fs-8\">Turno: " + escapeHtml(data.folio || "-") + " | Inicial: " + dinero(data.monto_inicial || 0) + " | Movimiento: " + escapeHtml(data.id_movimiento_caja || "-") + "</div>";
            }
            html += "</div>";
            document.getElementById("pos_caja_apertura_resultado").innerHTML = html;
            cargar();
        }).catch(function (error) {
            boton.disabled = false;
            boton.innerHTML = "<i class=\"bi bi-lock-fill\"></i> Abrir turno real";
            document.getElementById("pos_caja_apertura_resultado").insertAdjacentHTML("afterbegin", "<div class=\"alert alert-danger py-2\">" + escapeHtml(error.message || String(error)) + "</div>");
        });
    }

    function consultarCorteImprimible() {
        var referencia = (document.getElementById("pos_caja_corte_folio") || {}).value || "";
        referencia = referencia.trim();
        if (!referencia) {
            document.getElementById("pos_caja_corte_alerta").innerHTML = "<div class=\"alert alert-warning py-2\">Captura folio o ID de turno.</div>";
            return;
        }
        corteActual = "";
        document.getElementById("pos_caja_corte_texto").textContent = "Consultando corte...";
        document.getElementById("pos_caja_corte_alerta").innerHTML = "";
        var parametro = /^\d+$/.test(referencia) ? "id_turno_caja=" + encodeURIComponent(referencia) : "folio=" + encodeURIComponent(referencia);
        request("/ventas/corte_turno_readonly_erp?" + parametro).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            var data = response.depurar || {};
            var hallazgos = data.hallazgos || [];
            corteActual = data.corte_texto || "";
            document.getElementById("pos_caja_corte_texto").textContent = corteActual || "Sin corte generado";
            if (hallazgos.length) {
                document.getElementById("pos_caja_corte_alerta").innerHTML = "<div class=\"alert alert-warning py-2\"><div class=\"fw-bold\">Corte con observaciones</div><ul class=\"mb-0 ps-4\">" + hallazgos.map(function (item) {
                    return "<li>" + escapeHtml(item.mensaje || item) + "</li>";
                }).join("") + "</ul></div>";
            } else {
                document.getElementById("pos_caja_corte_alerta").innerHTML = "<div class=\"alert alert-success py-2\">Corte listo para imprimir.</div>";
            }
        }).catch(function (error) {
            document.getElementById("pos_caja_corte_texto").textContent = "";
            document.getElementById("pos_caja_corte_alerta").innerHTML = "<div class=\"alert alert-danger py-2\">" + escapeHtml(error.message || String(error)) + "</div>";
        });
    }

    function imprimirCorte() {
        if (!corteActual) { return; }
        var ventana = window.open("", "erp_pos_corte", "width=460,height=760");
        if (!ventana) { return; }
        ventana.document.write("<!doctype html><html><head><title>Corte POS</title><style>body{font-family:Consolas,'Liberation Mono',monospace;font-size:12px;white-space:pre-wrap;margin:12px;color:#111;}@media print{body{margin:0;}}</style></head><body>");
        ventana.document.write(escapeHtml(corteActual));
        ventana.document.write("</body></html>");
        ventana.document.close();
        ventana.focus();
        ventana.print();
    }

    function mostrarError(error) {
        document.getElementById("pos_caja_alerta").innerHTML = "<div class=\"alert alert-danger py-3\">" + escapeHtml(error.message || String(error)) + "</div>";
    }

    document.addEventListener("DOMContentLoaded", function () {
        cargar();
        document.getElementById("pos_caja_recargar").addEventListener("click", cargar);
        document.getElementById("pos_caja_turno").addEventListener("change", function () {
            sincronizarDesdeTurno();
            sugerirArqueoDesdeEsperado();
        });
        document.getElementById("pos_caja_apertura_almacen").addEventListener("change", sincronizarAperturaCaja);
        document.getElementById("pos_caja_apertura_dryrun").addEventListener("click", simularApertura);
        document.getElementById("pos_caja_corte_dryrun").addEventListener("click", simularCorte);
        document.getElementById("pos_caja_readiness_consultar").addEventListener("click", consultarReadiness);
        document.getElementById("pos_caja_corte_consultar").addEventListener("click", consultarCorteImprimible);
        document.getElementById("pos_caja_corte_imprimir").addEventListener("click", imprimirCorte);
        document.getElementById("pos_caja_apertura_resultado").addEventListener("click", copiarAutorizacion);
        document.getElementById("pos_caja_apertura_resultado").addEventListener("click", abrirTurnoReal);
        document.getElementById("pos_caja_corte_resultado").addEventListener("click", copiarAutorizacion);
        document.getElementById("pos_caja_corte_resultado").addEventListener("click", cerrarTurnoReal);
        document.getElementById("pos_caja_readiness_resultado").addEventListener("click", copiarAutorizacion);
        document.addEventListener("input", function (event) {
            if (event.target.closest(".pos-caja-denom") || event.target.closest(".pos-caja-arqueo-extra")) {
                calcularArqueo();
            }
        });
    });
})();
