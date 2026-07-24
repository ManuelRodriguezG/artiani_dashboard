"use strict";
(function () {
    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-24
     * Proposito: consola inicial TMS Delivery para consulta, validacion y creacion protegida.
     * Impacto: TMS Delivery; consulta catalogos/listado y prepara servicios logisticos independientes.
     * Contrato: crear servicio solo afecta TMS cuando el esquema exista; no cambia ventas, inventario ni garantias.
     */
    var catalogos = {};

    document.addEventListener("DOMContentLoaded", function () {
        enlazarEventos();
        cargarInicial();
    });

    function enlazarEventos() {
        var refrescar = document.getElementById("tms_refrescar");
        var form = document.getElementById("tms_form_dryrun");
        var guardar = document.getElementById("tms_guardar_servicio");
        ["tms_filtro_estatus", "tms_filtro_tipo", "tms_filtro_cobro"].forEach(function (id) {
            var node = document.getElementById(id);
            if (node) {
                node.addEventListener("change", cargarServicios);
            }
        });
        if (refrescar) {
            refrescar.addEventListener("click", cargarServicios);
        }
        if (form) {
            form.addEventListener("submit", function (event) {
                event.preventDefault();
                validarDryRun(form);
            });
        }
        if (guardar && form) {
            guardar.addEventListener("click", function () {
                guardarServicio(form);
            });
        }
    }

    function cargarInicial() {
        getJson("/tms/catalogos_erp").then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje || "No fue posible cargar catalogos TMS");
            }
            catalogos = response.depurar || {};
            renderCatalogos();
            cargarServicios();
        }).catch(function (error) {
            mostrarAlerta(error.message || String(error), "warning");
            renderServicios([]);
        });
    }

    function cargarServicios() {
        var params = new URLSearchParams();
        params.append("limite", "50");
        appendIf(params, "estatus_servicio", value("tms_filtro_estatus"));
        appendIf(params, "tipo_servicio", value("tms_filtro_tipo"));
        appendIf(params, "estatus_cobro", value("tms_filtro_cobro"));

        getJson("/tms/servicios_listar_erp?" + params.toString()).then(function (response) {
            if (response.error) {
                throw new Error(response.mensaje || "No fue posible consultar TMS");
            }
            var depurar = response.depurar || {};
            if (depurar.schema_pendiente) {
                mostrarAlerta("Esquema TMS pendiente. La bandeja esta lista, pero aun no hay tablas erp_tms_* aplicadas.", "info");
            } else {
                limpiarAlerta();
            }
            renderServicios(depurar.servicios || []);
        }).catch(function (error) {
            mostrarAlerta(error.message || String(error), "warning");
            renderServicios([]);
        });
    }

    function validarDryRun(form) {
        postForm("/tms/servicio_dryrun_erp", datosServicio(form)).then(renderDryRun).catch(function (error) {
            renderDryRun({error: true, tipo: "warning", mensaje: error.message || String(error), depurar: {}});
        });
    }

    function guardarServicio(form) {
        setGuardarCargando(true);
        postForm("/tms/servicio_guardar_erp", datosServicio(form)).then(function (response) {
            renderRespuestaServicio(response);
            if (!response.error) {
                form.reset();
                renderCatalogos();
                cargarServicios();
            }
        }).catch(function (error) {
            renderRespuestaServicio({error: true, tipo: "warning", mensaje: error.message || String(error), depurar: {}});
        }).finally(function () {
            setGuardarCargando(false);
        });
    }

    function datosServicio(form) {
        var datos = new FormData(form);
        datos.append("_csrf", window.ERP_CSRF_TOKEN || "");
        datos.append("solicitado_por_modulo", "manual");
        datos.append("solicitado_por_tipo", "solicitud_manual");

        var descripcion = String(datos.get("descripcion_detalle") || "").trim();
        if (descripcion) {
            datos.append("detalle", JSON.stringify([{
                descripcion_snapshot: descripcion,
                cantidad: 1,
                requiere_cuidado_especial: 0
            }]));
        }
        return datos;
    }

    function renderCatalogos() {
        renderOptions("tms_filtro_estatus", catalogos.estatus_servicio || [], "Todos los estados");
        renderOptions("tms_filtro_cobro", catalogos.estatus_cobro || [], "Todos los cobros");
        renderOptions("tms_filtro_tipo", catalogos.tipos_servicio || [], "Todos los tipos");
        renderOptions("tms_tipo_servicio", catalogos.tipos_servicio || [], "");
        renderOptions("tms_prioridad", catalogos.prioridades || [], "");
        renderOptions("tms_estatus_cobro", catalogos.estatus_cobro || [], "");
    }

    function renderOptions(id, items, placeholder) {
        var select = document.getElementById(id);
        if (!select) {
            return;
        }
        var html = placeholder ? "<option value=\"\">" + escapeHtml(placeholder) + "</option>" : "";
        html += items.map(function (item) {
            var valor = typeof item === "string" ? item : item.valor;
            var texto = typeof item === "string" ? humanize(item) : item.texto;
            return "<option value=\"" + escapeHtml(valor) + "\">" + escapeHtml(texto) + "</option>";
        }).join("");
        select.innerHTML = html;
    }

    function renderServicios(items) {
        setText("tms_kpi_total", items.length);
        setText("tms_kpi_ruta", items.filter(function (item) { return item.estatus_servicio === "en_ruta"; }).length);
        setText("tms_kpi_no_entregadas", items.filter(function (item) { return item.estatus_servicio === "no_entregada"; }).length);
        setText("tms_kpi_cliente", items.filter(function (item) { return item.estatus_servicio === "pendiente_cliente"; }).length);

        var body = document.getElementById("tms_servicios_body");
        if (!body) {
            return;
        }
        if (!items.length) {
            body.innerHTML = "<tr><td colspan=\"5\" class=\"text-center text-muted py-8\">Sin servicios TMS para mostrar.</td></tr>";
            return;
        }
        body.innerHTML = items.map(function (item) {
            return "<tr>" +
                "<td><div class=\"fw-bold\">" + escapeHtml(item.folio || "TMS") + "</div>" +
                "<div class=\"d-flex flex-wrap gap-1 mt-2\">" + badge(humanize(item.tipo_servicio), "primary") + badge(humanize(item.estatus_servicio), badgeEstado(item.estatus_servicio)) + "</div>" +
                "<div class=\"text-muted fs-8 mt-1\">" + escapeHtml(item.referencia_externa || item.solicitado_por_modulo || "") + "</div></td>" +
                "<td><div class=\"fw-semibold\">" + escapeHtml(item.cliente_nombre_snapshot || "Sin cliente") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.cliente_contacto_snapshot || item.zona_snapshot || "") + "</div></td>" +
                "<td>" + escapeHtml(formatVentana(item)) + "</td>" +
                "<td>" + badge(humanize(item.estatus_cobro), "light") + "</td>" +
                "<td>" + badge(humanize(item.resultado_logistico), badgeResultado(item.resultado_logistico)) + "</td>" +
                "</tr>";
        }).join("");
    }

    function renderDryRun(response) {
        var contenedor = document.getElementById("tms_dryrun_resultado");
        if (!contenedor) {
            return;
        }
        var depurar = response.depurar || {};
        var bloqueos = depurar.bloqueos || [];
        var advertencias = depurar.advertencias || [];
        var preview = depurar.servicio_preview || {};
        var tipo = response.error ? "danger" : (response.tipo === "success" ? "success" : "warning");

        contenedor.innerHTML =
            "<div class=\"alert alert-" + tipo + " py-3 mb-4\"><div class=\"fw-bold\">" + escapeHtml(response.mensaje || "Dry-run") + "</div>" +
            "<div class=\"fs-8\">No se guardo ningun servicio.</div></div>" +
            renderLista("Bloqueos", bloqueos, "danger") +
            renderLista("Advertencias", advertencias, "warning") +
            "<div class=\"border rounded p-3\">" +
            "<div class=\"fw-bold mb-2\">" + escapeHtml(preview.folio_preview || "Preview") + "</div>" +
            "<div class=\"fs-8 text-muted mb-1\">Tipo: " + escapeHtml(humanize(preview.tipo_servicio)) + "</div>" +
            "<div class=\"fs-8 text-muted mb-1\">Cliente: " + escapeHtml(preview.cliente_nombre_snapshot || "") + "</div>" +
            "<div class=\"fs-8 text-muted mb-1\">Direccion: " + escapeHtml(preview.direccion_snapshot || "") + "</div>" +
            "<div class=\"fs-8 text-muted\">Cobro: " + escapeHtml(humanize(preview.estatus_cobro)) + " $" + money(preview.precio_cobrado) + "</div>" +
            "</div>";
    }

    function renderRespuestaServicio(response) {
        var contenedor = document.getElementById("tms_dryrun_resultado");
        if (!contenedor) {
            return;
        }
        var depurar = response.depurar || {};
        var tipo = response.error ? (response.tipo === "warning" ? "warning" : "danger") : "success";
        var folio = depurar.folio || "";
        var schemaPendiente = depurar.schema_pendiente ? "<div class=\"fs-8 text-muted mt-2\">Tablas requeridas: " + escapeHtml((depurar.tablas_requeridas || []).join(", ")) + "</div>" : "";

        contenedor.innerHTML =
            "<div class=\"alert alert-" + tipo + " py-3 mb-0\">" +
            "<div class=\"fw-bold\">" + escapeHtml(response.mensaje || "Respuesta TMS") + "</div>" +
            (folio ? "<div class=\"fs-8\">Folio: " + escapeHtml(folio) + "</div>" : "") +
            schemaPendiente +
            "<div class=\"fs-8 mt-2\">TMS no modifica ventas, garantias ni inventario.</div>" +
            "</div>";
    }

    function renderLista(titulo, items, tipo) {
        if (!items.length) {
            return "";
        }
        return "<div class=\"mb-3\"><div class=\"fw-bold text-" + tipo + " mb-1\">" + escapeHtml(titulo) + "</div><ul class=\"mb-0 ps-4\">" +
            items.map(function (item) { return "<li class=\"fs-8\">" + escapeHtml(item) + "</li>"; }).join("") +
            "</ul></div>";
    }

    function getJson(url) {
        return fetch(url, {
            method: "GET",
            credentials: "same-origin",
            headers: {"Accept": "application/json"}
        }).then(function (response) {
            if (!response.ok) {
                throw new Error("Solicitud no disponible");
            }
            return response.json();
        });
    }

    function postForm(url, datos) {
        return fetch(url, {
            method: "POST",
            credentials: "same-origin",
            headers: {
                "Accept": "application/json",
                "X-CSRF-Token": window.ERP_CSRF_TOKEN || ""
            },
            body: datos
        }).then(function (response) {
            if (!response.ok) {
                throw new Error("Solicitud TMS no disponible");
            }
            return response.json();
        });
    }

    function appendIf(params, key, val) {
        if (val) {
            params.append(key, val);
        }
    }

    function value(id) {
        var node = document.getElementById(id);
        return node ? String(node.value || "").trim() : "";
    }

    function setText(id, val) {
        var node = document.getElementById(id);
        if (node) {
            node.textContent = String(val == null ? 0 : val);
        }
    }

    function setGuardarCargando(cargando) {
        var guardar = document.getElementById("tms_guardar_servicio");
        if (!guardar) {
            return;
        }
        guardar.disabled = !!cargando;
        guardar.innerHTML = cargando
            ? "<span class=\"spinner-border spinner-border-sm me-2\"></span>Creando"
            : "<i class=\"bi bi-send-check\"></i> Crear servicio";
    }

    function mostrarAlerta(mensaje, tipo) {
        var alerta = document.getElementById("tms_alerta");
        if (alerta) {
            alerta.innerHTML = "<div class=\"alert alert-" + escapeHtml(tipo || "info") + " py-3 mb-0\"><div class=\"fw-bold\">TMS Delivery</div><div class=\"fs-7\">" + escapeHtml(mensaje) + "</div></div>";
        }
    }

    function limpiarAlerta() {
        var alerta = document.getElementById("tms_alerta");
        if (alerta) {
            alerta.innerHTML = "";
        }
    }

    function badge(text, type) {
        return "<span class=\"badge badge-light-" + escapeHtml(type || "primary") + "\">" + escapeHtml(text || "") + "</span>";
    }

    function badgeEstado(estado) {
        if (estado === "entregada") { return "success"; }
        if (estado === "no_entregada" || estado === "cancelada") { return "danger"; }
        if (estado === "en_ruta" || estado === "lista_para_salida") { return "primary"; }
        if (estado === "pendiente_cliente" || estado === "reprogramada") { return "warning"; }
        return "light";
    }

    function badgeResultado(resultado) {
        if (resultado === "completa") { return "success"; }
        if (resultado === "sin_entrega" || resultado === "cerrada_sin_entrega") { return "danger"; }
        if (resultado === "cliente_recogera" || resultado === "nuevo_intento_requerido") { return "warning"; }
        return "light";
    }

    function formatVentana(item) {
        var fecha = item.fecha_programada || "Sin fecha";
        var inicio = item.ventana_inicio || "";
        var fin = item.ventana_fin || "";
        return inicio && fin ? fecha + " " + inicio + "-" + fin : fecha;
    }

    function humanize(value) {
        return String(value || "").replace(/_/g, " ").replace(/\b\w/g, function (letter) { return letter.toUpperCase(); });
    }

    function money(value) {
        var numero = Number(value || 0);
        return numero.toFixed(2);
    }

    function escapeHtml(value) {
        var div = document.createElement("div");
        div.textContent = value == null ? "" : String(value);
        return div.innerHTML;
    }
})();
