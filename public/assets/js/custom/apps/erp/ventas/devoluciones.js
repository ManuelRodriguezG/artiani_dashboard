"use strict";
(function () {
    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-01
     * Proposito: operar pantalla separada de devoluciones POS en modo dry-run/read-only.
     * Impacto: evita mezclar reversas, reembolsos e inspeccion fisica dentro del tablero de ventas.
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

    function numero(value) {
        return new Intl.NumberFormat("es-MX", {maximumFractionDigits: 3}).format(Number(value || 0));
    }

    function simular() {
        request("/ventas/devolucion_dryrun_erp", {
            tipo: document.getElementById("dev_tipo").value,
            folio: document.getElementById("dev_folio").value.trim(),
            decision_inventario: document.getElementById("dev_decision_inventario").value,
            decision_financiera: document.getElementById("dev_decision_financiera").value,
            motivo: document.getElementById("dev_motivo").value.trim(),
            items: JSON.stringify([])
        }).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            var data = response.depurar || {};
            var bloqueos = data.bloqueos || [];
            var html = "<div class=\"alert " + (bloqueos.length ? "alert-warning" : "alert-success") + " py-3\"><div class=\"fw-bold\">" + escapeHtml(response.mensaje || "Simulacion generada") + "</div><div class=\"fs-8 text-muted\">No se aplico devolucion real, no se reembolso y no se movio inventario.</div>";
            html += "<div class=\"fs-8\">Tipo: " + escapeHtml(data.tipo || "") + " | Inventario: " + escapeHtml(data.decision_inventario || "") + " | Financiera: " + escapeHtml(document.getElementById("dev_decision_financiera").value) + "</div>";
            if (bloqueos.length) {
                html += "<ul class=\"mb-0 mt-2 ps-4\">" + bloqueos.map(function (item) { return "<li>" + escapeHtml(item) + "</li>"; }).join("") + "</ul>";
            }
            html += "</div>";
            document.getElementById("dev_resultado").innerHTML = html;
        }).catch(function (error) {
            document.getElementById("dev_resultado").innerHTML = "<div class=\"alert alert-warning py-3\">" + escapeHtml(error.message || String(error)) + "</div>";
        });
    }

    function consultarFisicas() {
        var contenedor = document.getElementById("dev_fisicas_resultado");
        var estado = document.getElementById("dev_inspeccion_estado_filtro").value || "";
        contenedor.innerHTML = "<div class=\"text-muted fs-8 py-3\">Consultando devoluciones...</div>";
        request("/ventas/devoluciones_inventario_pendientes_erp?decision_inventario=" + encodeURIComponent(document.getElementById("dev_fisicas_filtro").value) + "&inspeccion_estado=" + encodeURIComponent(estado) + "&limite=50").then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            renderFisicas(response);
        }).catch(function (error) {
            contenedor.innerHTML = "<div class=\"alert alert-warning py-3\">" + escapeHtml(error.message || String(error)) + "</div>";
        });
    }

    function renderFisicas(response) {
        var depurar = response.depurar || {};
        var resumen = depurar.resumen || {};
        var filas = depurar.pendientes || [];
        var html = "<div class=\"alert " + (filas.length ? "alert-info" : "alert-light") + " py-3 mb-3\"><div class=\"fw-bold\">" + escapeHtml(response.mensaje || "Devoluciones fisicas") + "</div>" +
            "<div class=\"fs-8 text-muted\">Partidas: " + escapeHtml(resumen.total_registros || 0) + " | Cantidad: " + numero(resumen.cantidad_total || 0) + " | Importe: " + dinero(resumen.importe_total || 0) + "</div></div>";
        if (!filas.length) {
            document.getElementById("dev_fisicas_resultado").innerHTML = html + "<div class=\"dev-empty d-flex align-items-center justify-content-center text-center text-muted\"><div><i class=\"bi bi-check2-circle fs-1 d-block mb-3\"></i><div class=\"fw-semibold\">Sin partidas en este filtro</div></div></div>";
            return;
        }
        html += "<div class=\"table-responsive\"><table class=\"table align-middle table-row-dashed gy-3 mb-0\"><thead><tr class=\"text-muted fw-bold fs-8 text-uppercase\"><th>Devolucion</th><th>Venta</th><th>SKU</th><th>Decision</th><th class=\"text-end\">Importe</th><th class=\"text-end\">Accion</th></tr></thead><tbody>";
        html += filas.map(function (item) {
            var inspeccionEstado = item.inspeccion_estado || "pendiente";
            var accion = inspeccionEstado === "pendiente"
                ? "<button class=\"btn btn-sm btn-light-warning\" type=\"button\" data-dev-inspeccionar=\"" + escapeHtml(item.id_devolucion_detalle || "") + "\" data-dev-folio=\"" + escapeHtml(item.folio_devolucion || "") + "\"><i class=\"bi bi-clipboard-check\"></i> Inspeccionar</button>"
                : "<button class=\"btn btn-sm btn-light-primary\" type=\"button\" data-dev-destino=\"" + escapeHtml(item.id_devolucion_detalle || "") + "\" data-dev-folio=\"" + escapeHtml(item.folio_devolucion || "") + "\"><i class=\"bi bi-diagram-3\"></i> Destino</button>";
            return "<tr><td><div class=\"fw-bold\">" + escapeHtml(item.folio_devolucion || "-") + "</div><div class=\"text-muted fs-8\">Inspeccion: " + escapeHtml(item.folio_inspeccion || item.inspeccion_estado || "pendiente") + "</div></td>" +
                "<td>" + escapeHtml(item.folio_venta || "-") + "</td><td><div class=\"fw-semibold\">" + escapeHtml(item.sku || "-") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.descripcion || "") + "</div></td>" +
                "<td><span class=\"badge badge-light-warning\">" + escapeHtml(item.decision_inventario || "-") + "</span></td><td class=\"text-end fw-bold\">" + dinero(item.importe_reembolso || 0) + "</td>" +
                "<td class=\"text-end\">" + accion + "</td></tr>";
        }).join("");
        html += "</tbody></table></div>";
        document.getElementById("dev_fisicas_resultado").innerHTML = html;
    }

    function datosInspeccion() {
        return {
            id_devolucion_detalle: document.getElementById("dev_inspeccion_detalle").value.trim(),
            decision_fisica: document.getElementById("dev_inspeccion_decision").value,
            condicion_producto: document.getElementById("dev_inspeccion_condicion").value,
            motivo: document.getElementById("dev_inspeccion_motivo").value.trim(),
            diagnostico: document.getElementById("dev_inspeccion_diagnostico").value.trim()
        };
    }

    function renderInspeccion(response, accion) {
        var data = response.depurar || {};
        var bloqueos = data.bloqueos || [];
        var resultado = response.depurar || {};
        if (response.resultado) {
            resultado = response.resultado.depurar || response.resultado || {};
        }
        var tipo = response.error || bloqueos.length ? "alert-warning" : "alert-success";
        var html = "<div class=\"alert " + tipo + " py-3\"><div class=\"fw-bold\">" + escapeHtml(response.mensaje || (accion === "registrar" ? "Inspeccion registrada" : "Prevalidacion generada")) + "</div>";
        if (resultado.folio) {
            html += "<div class=\"fs-8\">Folio: " + escapeHtml(resultado.folio) + " | Estado: " + escapeHtml(resultado.inspeccion_estado || "") + "</div>";
        }
        if (bloqueos.length) {
            html += "<ul class=\"mb-0 mt-2 ps-4\">" + bloqueos.map(function (item) { return "<li>" + escapeHtml(item) + "</li>"; }).join("") + "</ul>";
        }
        html += "<div class=\"fs-8 text-muted mt-2\">No reintegra disponible, no crea kardex y no mueve inventario.</div></div>";
        document.getElementById("dev_inspeccion_resultado").innerHTML = html;
    }

    function prevalidarInspeccion() {
        if (!datosInspeccion().id_devolucion_detalle) {
            document.getElementById("dev_inspeccion_resultado").innerHTML = "<div class=\"alert alert-warning py-3\">Selecciona una partida de devolucion.</div>";
            return;
        }
        document.getElementById("dev_inspeccion_resultado").innerHTML = "<div class=\"text-muted fs-8 py-3\">Prevalidando inspeccion...</div>";
        request("/ventas/devolucion_inspeccion_fisica_dryrun_erp", datosInspeccion()).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            renderInspeccion(response, "prevalidar");
        }).catch(function (error) {
            document.getElementById("dev_inspeccion_resultado").innerHTML = "<div class=\"alert alert-warning py-3\">" + escapeHtml(error.message || String(error)) + "</div>";
        });
    }

    function registrarInspeccion() {
        var datos = datosInspeccion();
        if (!datos.id_devolucion_detalle) {
            document.getElementById("dev_inspeccion_resultado").innerHTML = "<div class=\"alert alert-warning py-3\">Selecciona una partida de devolucion.</div>";
            return;
        }
        if (!window.confirm("Confirmar cuarentena documental sin mover inventario?")) {
            return;
        }
        document.getElementById("dev_inspeccion_resultado").innerHTML = "<div class=\"text-muted fs-8 py-3\">Registrando inspeccion...</div>";
        request("/ventas/devolucion_inspeccion_fisica_registrar_erp", datos).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            renderInspeccion(response, "registrar");
            consultarFisicas();
        }).catch(function (error) {
            document.getElementById("dev_inspeccion_resultado").innerHTML = "<div class=\"alert alert-warning py-3\">" + escapeHtml(error.message || String(error)) + "</div>";
        });
    }

    function seleccionarInspeccion(boton) {
        document.getElementById("dev_inspeccion_detalle").value = boton.getAttribute("data-dev-inspeccionar") || "";
        document.getElementById("dev_inspeccion_motivo").value = "Revision fisica de devolucion " + (boton.getAttribute("data-dev-folio") || "");
        document.getElementById("dev_inspeccion_diagnostico").value = "Producto pendiente de inspeccion fisica";
        document.getElementById("dev_inspeccion_resultado").innerHTML = "<div class=\"alert alert-info py-3\"><div class=\"fw-bold\">Partida seleccionada</div><div class=\"fs-8 text-muted\">Prevalida antes de confirmar cuarentena.</div></div>";
    }

    function datosDestino() {
        return {
            id_devolucion_detalle: document.getElementById("dev_destino_detalle").value.trim(),
            destino_final: document.getElementById("dev_destino_final").value,
            motivo: document.getElementById("dev_destino_motivo").value.trim()
        };
    }

    function renderDestino(response) {
        var data = response.depurar || {};
        var bloqueos = data.bloqueos || [];
        var avisos = data.avisos || [];
        var ddl = data.ddl_requerido_para_apply_real || [];
        var plan = data.plan || {};
        var tipo = bloqueos.length ? "alert-warning" : "alert-success";
        var html = "<div class=\"alert " + tipo + " py-3\"><div class=\"fw-bold\">" + escapeHtml(response.mensaje || "Prevalidacion de destino final") + "</div>";
        if (plan.tipo_movimiento) {
            html += "<div class=\"fs-8 mt-1\">Plan: " + escapeHtml(plan.tipo_movimiento) + "</div>";
        }
        if (plan.existencia_anterior !== undefined) {
            html += "<div class=\"fs-8\">Existencia: " + escapeHtml(plan.existencia_anterior) + " -> " + escapeHtml(plan.existencia_nueva) + " | Disponible: " + escapeHtml(plan.disponible_anterior) + " -> " + escapeHtml(plan.disponible_nuevo) + "</div>";
        }
        if (bloqueos.length) {
            html += "<div class=\"fw-semibold fs-8 mt-2\">Bloqueos</div><ul class=\"mb-0 ps-4\">" + bloqueos.map(function (item) { return "<li>" + escapeHtml(item) + "</li>"; }).join("") + "</ul>";
        }
        if (avisos.length) {
            html += "<div class=\"fw-semibold fs-8 mt-2\">Avisos</div><ul class=\"mb-0 ps-4\">" + avisos.map(function (item) { return "<li>" + escapeHtml(item) + "</li>"; }).join("") + "</ul>";
        }
        if (ddl.length) {
            html += "<div class=\"fw-semibold fs-8 mt-2\">DDL requerido antes de aplicar real</div><ul class=\"mb-0 ps-4\">" + ddl.map(function (item) { return "<li>" + escapeHtml(item) + "</li>"; }).join("") + "</ul>";
        }
        html += "<div class=\"fs-8 text-muted mt-2\">Dry-run: no escribe BD, no mueve inventario y no crea kardex.</div></div>";
        document.getElementById("dev_destino_resultado").innerHTML = html;
    }

    function prevalidarDestino() {
        var datos = datosDestino();
        if (!datos.id_devolucion_detalle) {
            document.getElementById("dev_destino_resultado").innerHTML = "<div class=\"alert alert-warning py-3\">Selecciona una partida en cuarentena confirmada.</div>";
            return;
        }
        document.getElementById("dev_destino_resultado").innerHTML = "<div class=\"text-muted fs-8 py-3\">Prevalidando destino final...</div>";
        request("/ventas/devolucion_destino_final_dryrun_erp", datos).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            renderDestino(response);
        }).catch(function (error) {
            document.getElementById("dev_destino_resultado").innerHTML = "<div class=\"alert alert-warning py-3\">" + escapeHtml(error.message || String(error)) + "</div>";
        });
    }

    function seleccionarDestino(boton) {
        document.getElementById("dev_destino_detalle").value = boton.getAttribute("data-dev-destino") || "";
        document.getElementById("dev_destino_motivo").value = "Resolucion de cuarentena " + (boton.getAttribute("data-dev-folio") || "");
        document.getElementById("dev_destino_resultado").innerHTML = "<div class=\"alert alert-info py-3\"><div class=\"fw-bold\">Partida seleccionada</div><div class=\"fs-8 text-muted\">Elige destino y prevalida. La aplicacion real requiere autorizacion posterior.</div></div>";
    }

    function consultarTicket() {
        var folio = document.getElementById("dev_ticket_folio").value.trim();
        if (!folio) {
            document.getElementById("dev_ticket_texto").textContent = "Captura folio de devolucion.";
            return;
        }
        document.getElementById("dev_ticket_texto").textContent = "Consultando ticket...";
        request("/ventas/pos_ticket_devolucion_erp?folio_devolucion=" + encodeURIComponent(folio)).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            var data = response.depurar || {};
            document.getElementById("dev_ticket_texto").textContent = data.ticket_texto || JSON.stringify(data, null, 2);
        }).catch(function (error) {
            document.getElementById("dev_ticket_texto").textContent = error.message || String(error);
        });
    }

    function aplicarParametrosUrl() {
        var params = new URLSearchParams(window.location.search || "");
        var folioVenta = params.get("folio") || params.get("folio_venta") || "";
        var folioDevolucion = params.get("folio_devolucion") || "";
        if (folioVenta) {
            document.getElementById("dev_folio").value = folioVenta;
            document.getElementById("dev_resultado").innerHTML = "<div class=\"alert alert-info py-3\"><div class=\"fw-bold\">Folio precargado desde ventas</div><div class=\"fs-8 text-muted\">Puedes simular la devolucion sin aplicar cambios reales.</div></div>";
        }
        if (folioDevolucion) {
            document.getElementById("dev_ticket_folio").value = folioDevolucion;
            consultarTicket();
        }
    }

    document.addEventListener("DOMContentLoaded", function () {
        document.getElementById("dev_simular").addEventListener("click", simular);
        document.getElementById("dev_fisicas_consultar").addEventListener("click", consultarFisicas);
        document.getElementById("dev_ticket_consultar").addEventListener("click", consultarTicket);
        document.getElementById("dev_inspeccion_prevalidar").addEventListener("click", prevalidarInspeccion);
        document.getElementById("dev_inspeccion_registrar").addEventListener("click", registrarInspeccion);
        document.getElementById("dev_destino_prevalidar").addEventListener("click", prevalidarDestino);
        document.addEventListener("click", function (event) {
            var boton = event.target.closest("[data-dev-inspeccionar]");
            if (boton) {
                seleccionarInspeccion(boton);
            }
            var botonDestino = event.target.closest("[data-dev-destino]");
            if (botonDestino) {
                seleccionarDestino(botonDestino);
            }
        });
        aplicarParametrosUrl();
        consultarFisicas();
    });
})();
