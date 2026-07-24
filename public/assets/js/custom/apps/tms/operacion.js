"use strict";
(function () {
    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-24
     * Proposito: consultar cola operativa TMS en modo read-only.
     * Impacto: UI TMS Operacion; prepara seguimiento sin cambiar estados.
     * Contrato: solo GET a servicios/reportes TMS; no modifica ventas, garantias ni inventario.
     */
    document.addEventListener("DOMContentLoaded", function () {
        var refrescar = document.getElementById("tms_operacion_refrescar");
        if (refrescar) {
            refrescar.addEventListener("click", cargarOperacion);
        }
        cargarOperacion();
    });

    function cargarOperacion() {
        Promise.all([
            getJson("/tms/servicios_listar_erp?limite=50"),
            getJson("/tms/reportes_resumen_erp")
        ]).then(function (responses) {
            var servicios = responses[0];
            var reportes = responses[1];
            if (servicios.error) {
                throw new Error(servicios.mensaje || "No fue posible consultar servicios TMS");
            }
            var depurarServicios = servicios.depurar || {};
            var depurarReportes = reportes.depurar || {};
            if (depurarServicios.schema_pendiente || depurarReportes.schema_pendiente) {
                mostrarAlerta("Esquema TMS pendiente. La operacion esta lista, pero aun no hay servicios reales.", "info");
            } else {
                limpiarAlerta();
            }
            renderServicios(depurarServicios.servicios || []);
        }).catch(function (error) {
            mostrarAlerta(error.message || String(error), "warning");
            renderServicios([]);
        });
    }

    function renderServicios(items) {
        setText("tms_op_programadas", contar(items, "programada") + contar(items, "reprogramada"));
        setText("tms_op_listas", contar(items, "lista_para_salida"));
        setText("tms_op_ruta", contar(items, "en_ruta"));
        setText("tms_op_incidencias", contar(items, "no_entregada") + contar(items, "pendiente_cliente"));

        var body = document.getElementById("tms_operacion_body");
        if (!body) {
            return;
        }
        if (!items.length) {
            body.innerHTML = "<tr><td colspan=\"5\" class=\"text-center text-muted py-8\">Sin servicios TMS para operar.</td></tr>";
            return;
        }
        body.innerHTML = items.map(function (item) {
            return "<tr>" +
                "<td class=\"ps-6\"><div class=\"fw-bold\">" + escapeHtml(item.folio || "TMS") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(humanize(item.tipo_servicio)) + "</div></td>" +
                "<td><div class=\"fw-semibold\">" + escapeHtml(item.cliente_nombre_snapshot || "Sin cliente") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.cliente_contacto_snapshot || "") + "</div></td>" +
                "<td>" + escapeHtml(formatVentana(item)) + "</td>" +
                "<td>" + badge(humanize(item.estatus_servicio), badgeEstado(item.estatus_servicio)) + "</td>" +
                "<td>" + badge(humanize(item.resultado_logistico), badgeResultado(item.resultado_logistico)) + "</td>" +
                "</tr>";
        }).join("");
    }

    function getJson(url) {
        return fetch(url, {method: "GET", credentials: "same-origin", headers: {"Accept": "application/json"}}).then(function (response) {
            if (!response.ok) {
                throw new Error("Respuesta HTTP " + response.status);
            }
            return response.json();
        });
    }

    function contar(items, estado) {
        return items.filter(function (item) { return item.estatus_servicio === estado; }).length;
    }

    function setText(id, value) {
        var node = document.getElementById(id);
        if (node) {
            node.textContent = String(value);
        }
    }

    function formatVentana(item) {
        var fecha = item.fecha_programada || "Sin fecha";
        var inicio = item.ventana_inicio || "";
        var fin = item.ventana_fin || "";
        return inicio && fin ? fecha + " " + inicio + "-" + fin : fecha;
    }

    function badge(texto, tipo) {
        return "<span class=\"badge badge-light-" + escapeHtml(tipo || "secondary") + "\">" + escapeHtml(texto || "Pendiente") + "</span>";
    }

    function badgeEstado(estado) {
        if (estado === "en_ruta") { return "primary"; }
        if (estado === "entregada") { return "success"; }
        if (estado === "no_entregada" || estado === "cancelada") { return "danger"; }
        if (estado === "pendiente_cliente" || estado === "reprogramada") { return "warning"; }
        return "secondary";
    }

    function badgeResultado(resultado) {
        if (resultado === "completa") { return "success"; }
        if (resultado === "sin_entrega" || resultado === "cerrada_sin_entrega") { return "danger"; }
        if (resultado === "cliente_recogera" || resultado === "nuevo_intento_requerido") { return "warning"; }
        return "secondary";
    }

    function humanize(value) {
        return String(value || "").replace(/_/g, " ").replace(/\b\w/g, function (letter) { return letter.toUpperCase(); });
    }

    function mostrarAlerta(mensaje, tipo) {
        var alerta = document.getElementById("tms_operacion_alerta");
        if (alerta) {
            alerta.innerHTML = "<div class=\"alert alert-" + escapeHtml(tipo || "info") + " py-3 mb-0\"><div class=\"fw-bold\">Operacion TMS</div><div class=\"fs-7\">" + escapeHtml(mensaje) + "</div></div>";
        }
    }

    function limpiarAlerta() {
        var alerta = document.getElementById("tms_operacion_alerta");
        if (alerta) {
            alerta.innerHTML = "";
        }
    }

    function escapeHtml(value) {
        var div = document.createElement("div");
        div.textContent = value == null ? "" : String(value);
        return div.innerHTML;
    }
})();
