"use strict";
(function () {
    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-29
     * Proposito: operar ficha CRM en lectura y validacion dry-run.
     * Impacto: mantiene ficha completa fuera del cobro POS.
     */
    function request(url, data) {
        var options = {credentials: "same-origin"};
        if (data) {
            options.method = "POST";
            options.headers = {"Content-Type": "application/x-www-form-urlencoded; charset=UTF-8"};
            options.body = new URLSearchParams(data).toString();
        }
        return fetch(url, options).then(function (response) { return response.json(); });
    }

    function escapeHtml(value) {
        var div = document.createElement("div");
        div.textContent = value == null ? "" : String(value);
        return div.innerHTML;
    }

    function setText(id, value) {
        var node = document.getElementById(id);
        if (node) { node.textContent = value == null || value === "" ? "-" : String(value); }
    }

    function badge(text, type) {
        return "<span class=\"badge badge-light-" + escapeHtml(type || "primary") + "\">" + escapeHtml(text || "-") + "</span>";
    }

    function alerta(message, type) {
        document.getElementById("crm_ficha_alerta").innerHTML =
            "<div class=\"alert alert-" + escapeHtml(type || "warning") + " py-3\">" + escapeHtml(message || "") + "</div>";
    }

    function idCliente() {
        return Number(document.getElementById("crm_id_cliente_crm").value || 0);
    }

    function cargarFicha() {
        var id = idCliente();
        if (!id) {
            alerta("Cliente CRM invalido", "warning");
            return;
        }
        request("/crm/cliente_consultar_erp?id_cliente_crm=" + encodeURIComponent(id)).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            renderFicha(response.depurar || {});
        }).catch(function (error) {
            alerta(error.message || String(error), "warning");
        });
    }

    function renderFicha(data) {
        var cliente = data.cliente || {};
        document.getElementById("crm_ficha_alerta").innerHTML = "";
        setText("crm_ficha_titulo", cliente.nombre_publico || "Ficha CRM");
        setText("crm_ficha_subtitulo", (cliente.codigo_cliente || "") + " | " + (cliente.calidad_datos || ""));
        setText("crm_nombre_publico_header", cliente.nombre_publico);
        setText("crm_codigo_header", cliente.codigo_cliente);
        document.getElementById("crm_estatus_header").textContent = cliente.estatus || "-";
        setText("crm_calidad_header", cliente.calidad_datos);
        setText("crm_origen_header", cliente.origen_alta);
        setText("crm_fecha_header", cliente.fecha_registro);
        document.getElementById("crm_form_nombre").value = cliente.nombre_publico || "";
        document.getElementById("crm_form_tipo").value = cliente.tipo_cliente || "persona";
        document.getElementById("crm_form_estatus").value = cliente.estatus || "activo";
        document.getElementById("crm_form_observaciones").value = cliente.observaciones_operativas || "";
        renderCalidadOperativa(data.calidad_operativa || {});
        renderIdentificadores(data.identificadores || []);
        renderSimpleList("crm_contactos_lista", data.contactos || [], function (item) {
            return "<div class=\"fw-semibold\">" + escapeHtml(item.nombre_contacto || item.etiqueta || item.tipo || "Contacto") + "</div>" +
                "<div class=\"text-muted\">" + escapeHtml(item.valor || "") + "</div>";
        });
        renderSimpleList("crm_direcciones_lista", data.direcciones || [], function (item) {
            return "<div class=\"fw-semibold\">" + escapeHtml(item.alias || item.tipo || "Direccion") + "</div>" +
                "<div class=\"text-muted\">" + escapeHtml([item.calle, item.numero_exterior, item.colonia, item.ciudad, item.estado].filter(Boolean).join(" ")) + "</div>";
        });
        renderSimpleList("crm_fiscales_lista", data.fiscales || [], function (item) {
            return "<div class=\"fw-semibold crm-code\">" + escapeHtml(item.rfc || "-") + "</div><div class=\"text-muted\">" + escapeHtml(item.razon_social || "") + "</div>";
        });
        renderSimpleList("crm_consentimientos_lista", data.consentimientos || [], function (item) {
            return "<div class=\"fw-semibold\">" + escapeHtml(item.tipo || "-") + " " + badge(Number(item.otorgado) === 1 ? "Otorgado" : "No", Number(item.otorgado) === 1 ? "success" : "warning") + "</div>" +
                "<div class=\"text-muted\">" + escapeHtml(item.fecha_consentimiento || "") + "</div>";
        });
        renderPreferencias(data.condiciones || []);
        renderSimpleList("crm_notas_lista", data.notas || [], function (item) {
            return "<div class=\"fw-semibold\">" + escapeHtml(item.tipo || "Nota") + "</div><div>" + escapeHtml(item.nota || "") + "</div>";
        });
        renderInteracciones(data.interacciones || []);
        renderSimpleList("crm_eventos_lista", data.eventos || [], function (item) {
            return "<div class=\"fw-semibold\">" + escapeHtml(item.tipo_evento || "-") + "</div><div class=\"text-muted\">" + escapeHtml(item.resumen || "") + "</div><div class=\"fs-8 text-muted\">" + escapeHtml(item.fecha_registro || "") + "</div>";
        });
        renderSimpleList("crm_vinculos_lista", data.vinculos_externos || [], function (item) {
            return "<div><span class=\"crm-code\">" + escapeHtml(item.sistema_origen || "") + "/" + escapeHtml(item.entidad_origen || "") + "#" + escapeHtml(item.id_origen || "") + "</span> " + badge(item.confianza || "pendiente", "warning") + "</div>";
        });
        renderRecompensas(data.recompensas || {});
    }

    function renderCalidadOperativa(calidad) {
        var nivel = calidad.nivel || "incompleta";
        var type = nivel === "comercial" ? "success" : (nivel === "operativa" ? "primary" : (nivel === "basica_pos" ? "info" : "warning"));
        var pendientes = calidad.pendientes || [];
        var avisos = calidad.avisos || [];
        var fortalezas = calidad.fortalezas || [];
        var html = "<div class=\"d-flex justify-content-between align-items-center mb-3\">" +
            "<div class=\"crm-label\">Calidad operativa</div>" +
            badge((calidad.puntaje || 0) + "/100 " + nivel, type) +
            "</div>";
        html += "<div class=\"d-flex flex-wrap gap-2 mb-3\">" +
            badge(calidad.puede_usarse_pos ? "Listo POS" : "POS pendiente", calidad.puede_usarse_pos ? "success" : "warning") +
            badge(calidad.puede_contactarse ? "Contactable" : "Contacto pendiente", calidad.puede_contactarse ? "success" : "warning") +
            badge(calidad.apto_comercial ? "Comercial" : "No comercial", calidad.apto_comercial ? "success" : "light") +
            "</div>";
        if (pendientes.length) {
            html += "<div class=\"fs-8 text-muted mb-1\">Pendientes</div><ul class=\"mb-3 ps-4 fs-8\">" +
                pendientes.slice(0, 4).map(function (item) { return "<li>" + escapeHtml(item) + "</li>"; }).join("") +
                "</ul>";
        }
        if (avisos.length) {
            html += "<div class=\"alert alert-warning py-2 px-3 fs-8 mb-3\">" + avisos.map(escapeHtml).join("<br>") + "</div>";
        }
        if (!pendientes.length && fortalezas.length) {
            html += "<div class=\"fs-8 text-muted\">" + escapeHtml(fortalezas.slice(0, 3).join(" | ")) + "</div>";
        }
        document.getElementById("crm_calidad_operativa").innerHTML = html;
    }

    function renderIdentificadores(items) {
        document.getElementById("crm_identificadores_tabla").innerHTML = items.map(function (item) {
            return "<tr><td>" + badge(item.tipo || "-", "primary") + "</td><td><div class=\"fw-semibold\">" + escapeHtml(item.valor || "") + "</div><div class=\"text-muted crm-code\">" + escapeHtml(item.valor_normalizado || "") + "</div></td><td>" + (Number(item.principal) === 1 ? badge("Si", "success") : badge("No", "light")) + "</td><td>" + badge(item.estatus || "-", item.estatus === "activo" ? "success" : "warning") + "</td></tr>";
        }).join("") || "<tr><td colspan=\"4\" class=\"text-center text-muted py-6\">Sin identificadores.</td></tr>";
    }

    function renderSimpleList(id, items, renderer) {
        document.getElementById(id).innerHTML = items.map(function (item) {
            return "<div class=\"border-bottom pb-3 mb-3\">" + renderer(item) + "</div>";
        }).join("") || "<div class=\"text-center text-muted py-6\">Sin registros.</div>";
    }

    function renderInteracciones(items) {
        renderSimpleList("crm_interacciones_lista", items, function (item) {
            return "<div class=\"d-flex justify-content-between gap-3 mb-1\">" +
                "<div class=\"fw-semibold\">" + escapeHtml(item.resumen || item.tipo || "Interaccion") + "</div>" +
                "<div>" + badge(item.resultado || "-", item.resultado === "resuelto" || item.resultado === "contactado" ? "success" : "primary") + "</div>" +
                "</div>" +
                "<div class=\"text-muted fs-8\">" + escapeHtml([item.tipo, item.canal, item.direccion].filter(Boolean).join(" | ")) + "</div>" +
                "<div class=\"text-muted fs-8\">" + escapeHtml(item.fecha_interaccion || item.fecha_registro || "") + "</div>";
        });
    }

    function renderPreferencias(condiciones) {
        renderSimpleList("crm_preferencias_lista", condiciones, function (item) {
            var preferencias = {};
            try {
                preferencias = item.preferencias ? JSON.parse(item.preferencias) : {};
            } catch (e) {
                preferencias = {};
            }
            return "<div class=\"fw-semibold\">" + escapeHtml(preferencias.canal_preferido || "Sin canal preferido") + " " +
                badge(preferencias.no_contactar ? "No contactar" : "Contactable", preferencias.no_contactar ? "warning" : "success") + "</div>" +
                "<div class=\"text-muted fs-8\">Canales: " + escapeHtml((preferencias.canales_permitidos || []).join(", ") || "-") + "</div>" +
                "<div class=\"text-muted fs-8\">Horario: " + escapeHtml(preferencias.horario_contacto || "-") + " | Frecuencia: " + escapeHtml(preferencias.frecuencia_contacto || "-") + "</div>" +
                "<div class=\"text-muted fs-8\">Temas: " + escapeHtml((preferencias.temas_interes || []).join(", ") || "-") + "</div>";
        });
    }

    function renderRecompensas(recompensas) {
        var resumen = recompensas.resumen || {};
        var cuentas = recompensas.cuentas || [];
        var movimientos = recompensas.movimientos || [];
        var disponible = recompensas.disponible === true;
        var saldo = Number(resumen.saldo_puntos_total || 0);
        setText("crm_recompensas_saldo", saldo.toFixed(2) + " pts");
        if (!disponible) {
            document.getElementById("crm_recompensas_resumen").innerHTML =
                "<div class=\"alert alert-warning py-3 mb-0\"><div class=\"fw-bold\">Recompensas no disponibles</div><div class=\"fs-8\">El esquema de recompensas aun no esta aplicado.</div></div>";
            document.getElementById("crm_recompensas_cuentas").innerHTML = "<div class=\"text-center text-muted py-6\">Sin cuentas.</div>";
            document.getElementById("crm_recompensas_movimientos").innerHTML = "<div class=\"text-center text-muted py-6\">Sin movimientos.</div>";
            return;
        }
        document.getElementById("crm_recompensas_resumen").innerHTML =
            "<div class=\"d-flex flex-wrap gap-2\">" +
            badge("Cuentas: " + (resumen.cuentas || 0), (resumen.cuentas || 0) ? "success" : "warning") +
            badge("Movimientos: " + (resumen.movimientos || 0), (resumen.movimientos || 0) ? "primary" : "light") +
            badge("Saldo: " + saldo.toFixed(2) + " pts", saldo > 0 ? "info" : "light") +
            "</div><div class=\"text-muted fs-8 mt-3\">Read-only: esta ficha no otorga ni redime puntos.</div>";

        renderSimpleList("crm_recompensas_cuentas", cuentas, function (item) {
            var saldoCuenta = Number(item.saldo_puntos || 0);
            return "<div class=\"d-flex justify-content-between gap-3 mb-1\">" +
                "<div><div class=\"fw-semibold\">" + escapeHtml(item.programa_nombre || "Programa") + "</div>" +
                "<div class=\"crm-code text-muted fs-8\">" + escapeHtml(item.programa_codigo || "") + "</div></div>" +
                "<div>" + badge(saldoCuenta.toFixed(2) + " pts", saldoCuenta > 0 ? "info" : "light") + "</div></div>" +
                "<div class=\"text-muted fs-8\">Tipo: " + escapeHtml(item.programa_tipo || "-") + " | Estado: " + escapeHtml(item.estatus || "-") + "</div>";
        });
        renderSimpleList("crm_recompensas_movimientos", movimientos, function (item) {
            var tipo = item.tipo === "redencion" || item.tipo === "caducidad" ? "warning" : "success";
            return "<div class=\"d-flex justify-content-between gap-3 mb-1\">" +
                "<div><div class=\"fw-semibold\">" + escapeHtml(item.descripcion || item.tipo || "Movimiento") + "</div>" +
                "<div class=\"crm-code text-muted fs-8\">" + escapeHtml(item.origen_modulo || "") + "/" + escapeHtml(item.origen_tipo || "") + "/" + escapeHtml(item.origen_id || "") + "</div></div>" +
                "<div>" + badge((Number(item.puntos || 0)).toFixed(2) + " pts", tipo) + "</div></div>" +
                "<div class=\"text-muted fs-8\">" + escapeHtml(item.programa_codigo || "") + " | Saldo: " + escapeHtml(item.saldo_resultante || "0") + " | " + escapeHtml(item.fecha_registro || "") + "</div>";
        });
    }

    function validarBasico() {
        request("/crm/cliente_basico_guardar_dryrun_erp", {
            id_cliente_crm: idCliente(),
            nombre_publico: document.getElementById("crm_form_nombre").value,
            tipo_cliente: document.getElementById("crm_form_tipo").value,
            estatus: document.getElementById("crm_form_estatus").value,
            observaciones_operativas: document.getElementById("crm_form_observaciones").value
        }).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            var data = response.depurar || {};
            var bloqueos = data.bloqueos || [];
            var html = "<div class=\"alert " + (bloqueos.length ? "alert-warning" : "alert-success") + " py-3 mb-0\"><div class=\"fw-bold\">" + escapeHtml(response.mensaje || "") + "</div>";
            if (bloqueos.length) {
                html += "<ul class=\"mb-0 mt-2 ps-4\">" + bloqueos.map(function (item) { return "<li>" + escapeHtml(item) + "</li>"; }).join("") + "</ul>";
            } else {
                html += "<div class=\"fs-8 text-muted\">Dry-run: no guardo cambios. El apply requerira autorizacion y respaldo.</div>";
            }
            html += "</div>";
            document.getElementById("crm_form_resultado").innerHTML = html;
        }).catch(function (error) {
            document.getElementById("crm_form_resultado").innerHTML = "<div class=\"alert alert-warning py-3\">" + escapeHtml(error.message || String(error)) + "</div>";
        });
    }

    function renderDryRunResultado(targetId, response) {
        var data = response.depurar || {};
        var bloqueos = data.bloqueos || [];
        var html = "<div class=\"alert " + (bloqueos.length ? "alert-warning" : "alert-success") + " py-3 mb-0\"><div class=\"fw-bold\">" + escapeHtml(response.mensaje || "") + "</div>";
        if (bloqueos.length) {
            html += "<ul class=\"mb-0 mt-2 ps-4\">" + bloqueos.map(function (item) { return "<li>" + escapeHtml(item) + "</li>"; }).join("") + "</ul>";
        } else {
            html += "<div class=\"fs-8 text-muted\">Dry-run: no guardo cambios. El apply requerira autorizacion y respaldo.</div>";
        }
        html += "</div>";
        document.getElementById(targetId).innerHTML = html;
    }

    function validarComplemento(tipo, targetId, extra) {
        var data = Object.assign({id_cliente_crm: idCliente(), tipo_complemento: tipo}, extra || {});
        request("/crm/cliente_complemento_guardar_dryrun_erp", data).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            renderDryRunResultado(targetId, response);
        }).catch(function (error) {
            document.getElementById(targetId).innerHTML = "<div class=\"alert alert-warning py-3\">" + escapeHtml(error.message || String(error)) + "</div>";
        });
    }

    function validarInteraccion() {
        request("/crm/cliente_interaccion_dryrun_erp", {
            id_cliente_crm: idCliente(),
            tipo: document.getElementById("crm_interaccion_tipo").value,
            canal: document.getElementById("crm_interaccion_canal").value,
            direccion: document.getElementById("crm_interaccion_direccion").value,
            resultado: document.getElementById("crm_interaccion_resultado").value,
            resumen: document.getElementById("crm_interaccion_resumen").value,
            fecha_interaccion: document.getElementById("crm_interaccion_fecha").value,
            detalle: document.getElementById("crm_interaccion_detalle").value
        }).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            renderDryRunResultado("crm_interaccion_resultado_panel", response);
        }).catch(function (error) {
            document.getElementById("crm_interaccion_resultado_panel").innerHTML = "<div class=\"alert alert-warning py-3\">" + escapeHtml(error.message || String(error)) + "</div>";
        });
    }

    function validarPreferencias() {
        request("/crm/cliente_preferencias_dryrun_erp", {
            id_cliente_crm: idCliente(),
            canal_preferido: document.getElementById("crm_preferencia_canal").value,
            canales_permitidos: document.getElementById("crm_preferencia_canales").value,
            horario_contacto: document.getElementById("crm_preferencia_horario").value,
            frecuencia_contacto: document.getElementById("crm_preferencia_frecuencia").value,
            temas_interes: document.getElementById("crm_preferencia_temas").value,
            no_contactar: document.getElementById("crm_preferencia_no_contactar").value,
            motivo_no_contactar: document.getElementById("crm_preferencia_motivo_no_contactar").value
        }).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            renderDryRunResultado("crm_preferencia_resultado", response);
        }).catch(function (error) {
            document.getElementById("crm_preferencia_resultado").innerHTML = "<div class=\"alert alert-warning py-3\">" + escapeHtml(error.message || String(error)) + "</div>";
        });
    }

    document.addEventListener("DOMContentLoaded", function () {
        cargarFicha();
        document.getElementById("crm_ficha_recargar").addEventListener("click", cargarFicha);
        document.getElementById("crm_validar_basico").addEventListener("click", validarBasico);
        document.getElementById("crm_contacto_validar").addEventListener("click", function () {
            validarComplemento("contacto", "crm_contacto_resultado", {
                tipo: document.getElementById("crm_contacto_tipo").value,
                valor: document.getElementById("crm_contacto_valor").value,
                etiqueta: document.getElementById("crm_contacto_etiqueta").value
            });
        });
        document.getElementById("crm_direccion_validar").addEventListener("click", function () {
            validarComplemento("direccion", "crm_direccion_resultado", {
                tipo: document.getElementById("crm_direccion_tipo").value,
                alias: document.getElementById("crm_direccion_alias").value,
                calle: document.getElementById("crm_direccion_calle").value,
                codigo_postal: document.getElementById("crm_direccion_cp").value
            });
        });
        document.getElementById("crm_fiscal_validar").addEventListener("click", function () {
            validarComplemento("fiscal", "crm_fiscal_resultado", {
                rfc: document.getElementById("crm_fiscal_rfc").value,
                razon_social: document.getElementById("crm_fiscal_razon").value,
                regimen_fiscal: document.getElementById("crm_fiscal_regimen").value,
                codigo_postal_fiscal: document.getElementById("crm_fiscal_cp").value
            });
        });
        document.getElementById("crm_consentimiento_validar").addEventListener("click", function () {
            validarComplemento("consentimiento", "crm_consentimiento_resultado", {
                tipo: document.getElementById("crm_consentimiento_tipo").value,
                otorgado: document.getElementById("crm_consentimiento_otorgado").value,
                medio: document.getElementById("crm_consentimiento_medio").value,
                evidencia: document.getElementById("crm_consentimiento_evidencia").value
            });
        });
        document.getElementById("crm_nota_validar").addEventListener("click", function () {
            validarComplemento("nota", "crm_nota_resultado", {
                tipo: document.getElementById("crm_nota_tipo").value,
                nota: document.getElementById("crm_nota_texto").value
            });
        });
        document.getElementById("crm_interaccion_validar").addEventListener("click", validarInteraccion);
        document.getElementById("crm_preferencia_validar").addEventListener("click", validarPreferencias);
    });
})();
