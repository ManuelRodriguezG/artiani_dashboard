"use strict";
(function () {
    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-03
     * Proposito: renderizar reportes POS/caja y resolver diferencias administrativas.
     * Impacto: muestra diferencias de caja por turno y permite cerrar expedientes sin mover caja/inventario.
     */
    var ultimoReporte = null;
    var cajasConfiguracion = [];
    var corteActual = "";

    function request(url) {
        return fetch(url, {credentials: "same-origin"}).then(function (response) { return response.json(); });
    }

    function postForm(url, data) {
        return fetch(url, {
            method: "POST",
            credentials: "same-origin",
            headers: {"Content-Type": "application/x-www-form-urlencoded; charset=UTF-8", "X-CSRF-Token": window.ERP_CSRF_TOKEN || ""},
            body: new URLSearchParams(data).toString()
        }).then(function (response) { return response.json(); });
    }

    function escapeHtml(value) {
        var div = document.createElement("div");
        div.textContent = value == null ? "" : String(value);
        return div.innerHTML;
    }

    function dinero(value) {
        return new Intl.NumberFormat("es-MX", {style: "currency", currency: "MXN"}).format(Number(value || 0));
    }

    function hoy() {
        return new Date().toISOString().slice(0, 10);
    }

    function haceDias(dias) {
        var fecha = new Date();
        fecha.setDate(fecha.getDate() - dias);
        return fecha.toISOString().slice(0, 10);
    }

    function initFechas() {
        document.getElementById("pos_rep_desde").value = haceDias(30);
        document.getElementById("pos_rep_hasta").value = hoy();
    }

    function cargarConfiguracion() {
        request("/ventas/pos_configuracion_resumen_erp").then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            var data = response.depurar || {};
            cajasConfiguracion = data.cajas || [];
            renderFiltrosSucursalCaja();
        }).catch(function () {
            cajasConfiguracion = [];
            renderFiltrosSucursalCaja();
        });
    }

    function renderFiltrosSucursalCaja() {
        var almacenes = {};
        cajasConfiguracion.forEach(function (caja) {
            var idAlmacen = String(caja.id_almacen || "");
            if (!idAlmacen) { return; }
            almacenes[idAlmacen] = caja.almacen || ("Almacen " + idAlmacen);
        });
        var almacenActual = document.getElementById("pos_rep_almacen").value;
        document.getElementById("pos_rep_almacen").innerHTML = "<option value=\"\">Todas</option>" + Object.keys(almacenes).sort(function (a, b) {
            return almacenes[a].localeCompare(almacenes[b]);
        }).map(function (id) {
            return "<option value=\"" + escapeHtml(id) + "\">" + escapeHtml(almacenes[id]) + "</option>";
        }).join("");
        if (almacenActual && almacenes[almacenActual]) {
            document.getElementById("pos_rep_almacen").value = almacenActual;
        }
        renderFiltroCajas();
    }

    function renderFiltroCajas() {
        var idAlmacen = document.getElementById("pos_rep_almacen").value;
        var cajaActual = document.getElementById("pos_rep_caja").value;
        var cajas = cajasConfiguracion.filter(function (caja) {
            return !idAlmacen || String(caja.id_almacen || "") === idAlmacen;
        });
        document.getElementById("pos_rep_caja").innerHTML = "<option value=\"\">Todas</option>" + cajas.map(function (caja) {
            var etiqueta = (caja.codigo || ("Caja " + caja.id_caja)) + (caja.nombre ? " - " + caja.nombre : "");
            return "<option value=\"" + escapeHtml(caja.id_caja || "") + "\">" + escapeHtml(etiqueta) + "</option>";
        }).join("");
        if (cajaActual && cajas.some(function (caja) { return String(caja.id_caja || "") === cajaActual; })) {
            document.getElementById("pos_rep_caja").value = cajaActual;
        }
    }

    function consultar() {
        var params = new URLSearchParams({
            fecha_desde: document.getElementById("pos_rep_desde").value,
            fecha_hasta: document.getElementById("pos_rep_hasta").value,
            id_almacen: document.getElementById("pos_rep_almacen").value || "0",
            id_caja: document.getElementById("pos_rep_caja").value || "0",
            solo_diferencias: document.getElementById("pos_rep_solo_diferencias").checked ? "1" : "0"
        });
        request("/ventas/reportes_caja_erp?" + params.toString()).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            render(response.depurar || {});
        }).catch(function (error) {
            document.getElementById("pos_reportes_alerta").innerHTML = "<div class=\"alert alert-warning py-3\">" + escapeHtml(error.message || String(error)) + "</div>";
        });
        consultarDiferencias(params);
    }

    function consultarDiferencias(paramsBase) {
        var params = new URLSearchParams(paramsBase.toString());
        params.set("estado_revision", document.getElementById("pos_rep_estado_revision").value || "pendiente_revision");
        request("/ventas/reportes_diferencias_caja_erp?" + params.toString()).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            renderDiferencias(response.depurar || {});
        }).catch(function (error) {
            document.getElementById("pos_reportes_diferencias").innerHTML = "<tr><td colspan=\"8\" class=\"text-center text-warning py-6\">" + escapeHtml(error.message || String(error)) + "</td></tr>";
        });
    }

    function render(data) {
        ultimoReporte = data;
        var resumen = data.resumen || {};
        document.getElementById("pos_reportes_alerta").innerHTML = data.schema_pendiente
            ? "<div class=\"alert alert-warning py-3\">Esquema de reportes POS pendiente.</div>"
            : "<div class=\"alert alert-info py-3\"><div class=\"fw-bold\">Reporte consultado</div><div class=\"fs-8 text-muted\">Los importes son solo lectura. Las diferencias pueden cerrarse administrativamente sin mover caja ni inventario.</div></div>";
        document.getElementById("pos_rep_kpi_turnos").textContent = Number(resumen.turnos || 0);
        document.getElementById("pos_rep_kpi_diferencias").textContent = Number(resumen.turnos_con_diferencia || 0);
        document.getElementById("pos_rep_kpi_faltantes").textContent = dinero(resumen.faltantes_total || 0);
        document.getElementById("pos_rep_kpi_sobrantes").textContent = dinero(resumen.sobrantes_total || 0);
        document.getElementById("pos_rep_kpi_ventas").textContent = dinero(resumen.ventas_total || 0);
        document.getElementById("pos_rep_kpi_movimientos").textContent = Number(resumen.movimientos_count || 0);
        document.getElementById("pos_rep_kpi_faltante_prom").textContent = dinero(resumen.faltante_promedio || 0);
        document.getElementById("pos_rep_kpi_sobrante_prom").textContent = dinero(resumen.sobrante_promedio || 0);
        renderTurnos(data.turnos || []);
        renderUsuarios(data.por_usuario || []);
        renderCajas(data.por_caja || []);
    }

    function renderTurnos(filas) {
        document.getElementById("pos_reportes_turnos").innerHTML = filas.map(function (item) {
            var diferencia = Number(item.diferencia || 0);
            var estado = item.estado_diferencia || "cuadrado";
            var badge = estado === "faltante" ? "badge-light-danger" : (estado === "sobrante" ? "badge-light-success" : "badge-light-primary");
            return "<tr><td><div class=\"fw-bold\">" + escapeHtml(item.folio || "-") + "</div><div class=\"text-muted fs-8\">#" + escapeHtml(item.id_turno_caja || "-") + "</div></td>" +
                "<td><div>" + escapeHtml(item.codigo_almacen || item.almacen || item.id_almacen || "-") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.caja_codigo || item.caja_nombre || item.id_caja || "-") + "</div></td>" +
                "<td><div>A: " + escapeHtml(item.usuario_apertura || item.id_usuario_apertura || "-") + "</div><div class=\"text-muted fs-8\">C: " + escapeHtml(item.usuario_cierre || item.id_usuario_cierre || "-") + "</div></td>" +
                "<td><div>" + escapeHtml(item.fecha_apertura || "-") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.fecha_cierre || "Abierto") + "</div></td>" +
                "<td class=\"text-end\">" + dinero(item.monto_esperado || 0) + "</td>" +
                "<td class=\"text-end\">" + dinero(item.monto_contado || 0) + "</td>" +
                "<td class=\"text-end fw-bold " + (diferencia < 0 ? "text-danger" : (diferencia > 0 ? "text-success" : "")) + "\">" + dinero(diferencia) + "</td>" +
                "<td><span class=\"badge " + badge + "\">" + escapeHtml(estado) + "</span></td>" +
                "<td class=\"text-end\"><button class=\"btn btn-sm btn-icon btn-light-primary\" type=\"button\" data-corte-turno=\"" + escapeHtml(item.id_turno_caja || "") + "\" data-corte-folio=\"" + escapeHtml(item.folio || "") + "\" title=\"Corte\"><i class=\"bi bi-printer\"></i></button></td></tr>";
        }).join("") || "<tr><td colspan=\"9\" class=\"text-center text-muted py-6\">Sin turnos en el rango</td></tr>";
        document.querySelectorAll("[data-corte-turno]").forEach(function (boton) {
            boton.addEventListener("click", function () {
                abrirCorteTurno(this.getAttribute("data-corte-turno"), this.getAttribute("data-corte-folio"));
            });
        });
    }

    function renderUsuarios(filas) {
        document.getElementById("pos_reportes_usuarios").innerHTML = filas.map(function (item) {
            var neto = Number(item.diferencia_neta || 0);
            return "<tr><td><div class=\"fw-bold\">" + escapeHtml(item.usuario || ("Usuario " + (item.id_usuario || "-"))) + "</div><div class=\"text-muted fs-8\">ID " + escapeHtml(item.id_usuario || "-") + "</div></td>" +
                "<td class=\"text-end\">" + Number(item.turnos || 0) + "</td>" +
                "<td class=\"text-end\">" + Number(item.turnos_con_diferencia || 0) + "</td>" +
                "<td class=\"text-end\">" + Number(item.porcentaje_turnos_con_diferencia || 0).toFixed(2) + "%</td>" +
                "<td class=\"text-end text-danger fw-semibold\">" + dinero(item.faltantes_total || 0) + "</td>" +
                "<td class=\"text-end text-success fw-semibold\">" + dinero(item.sobrantes_total || 0) + "</td>" +
                "<td class=\"text-end fw-bold " + (neto < 0 ? "text-danger" : (neto > 0 ? "text-success" : "")) + "\">" + dinero(neto) + "</td></tr>";
        }).join("") || "<tr><td colspan=\"7\" class=\"text-center text-muted py-6\">Sin empleados en el rango</td></tr>";
    }

    function renderCajas(filas) {
        document.getElementById("pos_reportes_cajas").innerHTML = filas.map(function (item) {
            var neto = Number(item.diferencia_neta || 0);
            var sucursal = item.codigo_almacen || item.almacen || ("Almacen " + (item.id_almacen || "-"));
            var caja = item.caja_codigo || item.caja_nombre || ("Caja " + (item.id_caja || "-"));
            return "<tr><td><div class=\"fw-bold\">" + escapeHtml(sucursal) + "</div><div class=\"text-muted fs-8\">" + escapeHtml(caja) + "</div></td>" +
                "<td class=\"text-end\">" + Number(item.turnos || 0) + "</td>" +
                "<td class=\"text-end\">" + Number(item.turnos_con_diferencia || 0) + "</td>" +
                "<td class=\"text-end\">" + Number(item.porcentaje_turnos_con_diferencia || 0).toFixed(2) + "%</td>" +
                "<td class=\"text-end text-danger fw-semibold\">" + dinero(item.faltantes_total || 0) + "</td>" +
                "<td class=\"text-end text-success fw-semibold\">" + dinero(item.sobrantes_total || 0) + "</td>" +
                "<td class=\"text-end fw-bold " + (neto < 0 ? "text-danger" : (neto > 0 ? "text-success" : "")) + "\">" + dinero(neto) + "</td></tr>";
        }).join("") || "<tr><td colspan=\"7\" class=\"text-center text-muted py-6\">Sin cajas en el rango</td></tr>";
    }

    function renderDiferencias(data) {
        document.getElementById("pos_rep_dif_schema").textContent = data.schema_revision_pendiente ? "Revision formal pendiente" : "Revision formal activa";
        document.getElementById("pos_rep_dif_schema").className = data.schema_revision_pendiente ? "badge badge-light-warning" : "badge badge-light-success";
        var filas = data.diferencias || [];
        document.getElementById("pos_reportes_diferencias").innerHTML = filas.map(function (item) {
            var diferencia = Number(item.diferencia || 0);
            var tipo = item.tipo_diferencia || (diferencia < 0 ? "faltante" : "sobrante");
            var badge = tipo === "faltante" ? "badge-light-danger" : "badge-light-success";
            var estadoRevision = item.estado_revision || "pendiente_revision";
            var puedeResolver = ["pendiente_revision", "en_revision"].indexOf(estadoRevision) !== -1 && item.id_diferencia_revision;
            var boton = puedeResolver
                ? "<button class=\"btn btn-sm btn-light-primary pos-dif-resolver\" type=\"button\" data-folio=\"" + escapeHtml(item.folio_revision || item.folio_expediente || item.id_diferencia_revision || "") + "\" data-id=\"" + escapeHtml(item.id_diferencia_revision || "") + "\" data-turno=\"" + escapeHtml(item.folio || "") + "\"><i class=\"bi bi-check2-circle\"></i> Resolver</button>"
                : "<span class=\"text-muted fs-8\">Sin accion</span>";
            return "<tr><td><div class=\"fw-bold\">" + escapeHtml(item.folio || "-") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.fecha_cierre || "") + "</div></td>" +
                "<td><div>" + escapeHtml(item.codigo_almacen || item.almacen || "-") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.caja_codigo || item.caja_nombre || "-") + "</div></td>" +
                "<td><span class=\"badge " + badge + "\">" + escapeHtml(tipo) + "</span></td>" +
                "<td class=\"text-end\">" + dinero(item.monto_esperado || 0) + "</td>" +
                "<td class=\"text-end\">" + dinero(item.monto_contado || 0) + "</td>" +
                "<td class=\"text-end fw-bold " + (diferencia < 0 ? "text-danger" : "text-success") + "\">" + dinero(diferencia) + "</td>" +
                "<td><div class=\"fw-semibold\">" + escapeHtml(estadoRevision) + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.motivo || item.observaciones_cierre || "") + "</div></td>" +
                "<td class=\"text-end\">" + boton + "</td></tr>";
        }).join("") || "<tr><td colspan=\"8\" class=\"text-center text-muted py-6\">Sin diferencias en el rango</td></tr>";
        document.querySelectorAll(".pos-dif-resolver").forEach(function (boton) {
            boton.addEventListener("click", function () {
                resolverDiferencia({
                    id_diferencia_revision: this.getAttribute("data-id") || "",
                    turno: this.getAttribute("data-turno") || ""
                });
            });
        });
    }

    function resolverDiferencia(item) {
        var html = "<div class=\"text-start\">" +
            "<label class=\"form-label\">Decision</label>" +
            "<select id=\"swal_pos_dif_decision\" class=\"form-select mb-3\">" +
            "<option value=\"explicada\">Explicada</option>" +
            "<option value=\"aceptada\">Aceptada</option>" +
            "<option value=\"ajustada\">Ajustada</option>" +
            "<option value=\"escalada\">Escalada</option>" +
            "<option value=\"cancelada\">Cancelada</option>" +
            "</select>" +
            "<label class=\"form-label\">Motivo</label>" +
            "<textarea id=\"swal_pos_dif_motivo\" class=\"form-control mb-3\" rows=\"3\" placeholder=\"Describe la causa o acuerdo de supervision\"></textarea>" +
            "<label class=\"form-label\">Referencia de evidencia</label>" +
            "<input id=\"swal_pos_dif_evidencia\" class=\"form-control\" placeholder=\"Ticket, folio, acta o nota interna\">" +
            "</div>";
        Swal.fire({
            title: "Resolver diferencia",
            text: item.turno ? ("Turno " + item.turno) : "",
            html: html,
            icon: "question",
            showCancelButton: true,
            confirmButtonText: "Guardar resolucion",
            cancelButtonText: "Cancelar",
            preConfirm: function () {
                var motivo = document.getElementById("swal_pos_dif_motivo").value.trim();
                if (!motivo) {
                    Swal.showValidationMessage("Captura el motivo de resolucion.");
                    return false;
                }
                return {
                    id_diferencia_revision: item.id_diferencia_revision,
                    decision: document.getElementById("swal_pos_dif_decision").value,
                    motivo: motivo,
                    evidencia_referencia: document.getElementById("swal_pos_dif_evidencia").value.trim()
                };
            }
        }).then(function (result) {
            if (!result.isConfirmed) { return; }
            postForm("/ventas/reportes_diferencia_caja_resolver_erp", result.value).then(function (response) {
                if (response.error) { throw new Error(response.mensaje); }
                Swal.fire("Resolucion guardada", response.mensaje || "Diferencia resuelta", "success");
                consultar();
            }).catch(function (error) {
                Swal.fire("No se pudo resolver", error.message || String(error), "warning");
            });
        });
    }

    function csvValor(value) {
        var texto = value == null ? "" : String(value);
        return "\"" + texto.replace(/"/g, "\"\"") + "\"";
    }

    function exportarCsv() {
        if (!ultimoReporte) {
            document.getElementById("pos_reportes_alerta").innerHTML = "<div class=\"alert alert-warning py-3\">Consulta el reporte antes de exportar.</div>";
            return;
        }
        var filas = [["tipo", "folio", "almacen", "caja", "usuario_apertura", "usuario_cierre", "fecha_apertura", "fecha_cierre", "monto_esperado", "monto_contado", "diferencia", "estado"]];
        (ultimoReporte.turnos || []).forEach(function (item) {
            filas.push([
                "turno",
                item.folio || "",
                item.codigo_almacen || item.almacen || "",
                item.caja_codigo || item.caja_nombre || "",
                item.usuario_apertura || item.id_usuario_apertura || "",
                item.usuario_cierre || item.id_usuario_cierre || "",
                item.fecha_apertura || "",
                item.fecha_cierre || "",
                item.monto_esperado || 0,
                item.monto_contado || 0,
                item.diferencia || 0,
                item.estado_diferencia || ""
            ]);
        });
        var contenido = filas.map(function (fila) {
            return fila.map(csvValor).join(",");
        }).join("\r\n");
        var blob = new Blob([contenido], {type: "text/csv;charset=utf-8"});
        var url = URL.createObjectURL(blob);
        var link = document.createElement("a");
        link.href = url;
        link.download = "reporte-pos-caja-" + hoy() + ".csv";
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
    }

    function abrirCorteTurno(idTurno, folio) {
        corteActual = "";
        document.getElementById("pos_rep_corte_subtitulo").textContent = folio || ("Turno " + idTurno);
        document.getElementById("pos_rep_corte_alerta").innerHTML = "<div class=\"alert alert-info py-2\">Consultando corte...</div>";
        document.getElementById("pos_rep_corte_texto").textContent = "";
        bootstrap.Modal.getOrCreateInstance(document.getElementById("pos_rep_corte_modal")).show();
        request("/ventas/corte_turno_readonly_erp?id_turno_caja=" + encodeURIComponent(idTurno || "")).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            var data = response.depurar || {};
            var hallazgos = data.hallazgos || [];
            corteActual = data.corte_texto || "";
            document.getElementById("pos_rep_corte_texto").textContent = corteActual || "Sin corte generado";
            if (hallazgos.length) {
                document.getElementById("pos_rep_corte_alerta").innerHTML = "<div class=\"alert alert-warning py-2\"><div class=\"fw-bold\">Corte con observaciones</div><ul class=\"mb-0 ps-4\">" + hallazgos.map(function (item) {
                    return "<li>" + escapeHtml(item.mensaje || item) + "</li>";
                }).join("") + "</ul></div>";
            } else {
                document.getElementById("pos_rep_corte_alerta").innerHTML = "<div class=\"alert alert-success py-2\">Corte listo para imprimir.</div>";
            }
        }).catch(function (error) {
            document.getElementById("pos_rep_corte_alerta").innerHTML = "<div class=\"alert alert-danger py-2\">" + escapeHtml(error.message || String(error)) + "</div>";
        });
    }

    function imprimirCorte() {
        if (!corteActual) { return; }
        var ventana = window.open("", "erp_pos_corte_reporte", "width=460,height=760");
        if (!ventana) { return; }
        ventana.document.write("<!doctype html><html><head><title>Corte POS</title><style>body{font-family:Consolas,'Liberation Mono',monospace;font-size:12px;white-space:pre-wrap;margin:12px;color:#111;}@media print{body{margin:0;}}</style></head><body>");
        ventana.document.write(escapeHtml(corteActual));
        ventana.document.write("</body></html>");
        ventana.document.close();
        ventana.focus();
        ventana.print();
    }

    document.addEventListener("DOMContentLoaded", function () {
        initFechas();
        cargarConfiguracion();
        document.getElementById("pos_rep_consultar").addEventListener("click", consultar);
        document.getElementById("pos_rep_exportar").addEventListener("click", exportarCsv);
        document.getElementById("pos_rep_corte_imprimir").addEventListener("click", imprimirCorte);
        document.getElementById("pos_rep_almacen").addEventListener("change", function () {
            renderFiltroCajas();
            consultar();
        });
        document.getElementById("pos_rep_caja").addEventListener("change", consultar);
        document.getElementById("pos_rep_solo_diferencias").addEventListener("change", consultar);
        document.getElementById("pos_rep_estado_revision").addEventListener("change", consultar);
        consultar();
    });
})();
