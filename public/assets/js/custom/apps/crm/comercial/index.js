"use strict";
(function () {
    /**
     * IA: Codex GPT-5 | Fecha: 2026-07-30
     * Proposito: consola CRM Comercial.
     * Impacto: separa segmentos y condiciones del listado principal de clientes.
     */
    var permisos = window.CRM_COMERCIAL_PERMISOS || {};
    var puedeOperar = permisos.operar === true;

    function request(url) {
        return fetch(url, {credentials: "same-origin"}).then(function (response) { return response.json(); });
    }

    function requestPost(url, data) {
        return fetch(url, {
            credentials: "same-origin",
            method: "POST",
            headers: {"Content-Type": "application/x-www-form-urlencoded; charset=UTF-8", "X-CSRF-Token": window.ERP_CSRF_TOKEN || ""},
            body: new URLSearchParams(data || {}).toString()
        }).then(function (response) { return response.json(); });
    }

    function escapeHtml(value) {
        var div = document.createElement("div");
        div.textContent = value == null ? "" : String(value);
        return div.innerHTML;
    }

    function setText(id, value) {
        var node = document.getElementById(id);
        if (node) {
            node.textContent = value == null ? "0" : String(value);
        }
    }

    function value(id) {
        var node = document.getElementById(id);
        return node ? node.value : "";
    }

    function setValue(id, val) {
        var node = document.getElementById(id);
        if (node) {
            node.value = val == null ? "" : String(val);
        }
    }

    function badge(text, type) {
        return "<span class=\"badge badge-light-" + escapeHtml(type || "primary") + "\">" + escapeHtml(text || "") + "</span>";
    }

    function mostrarAlerta(error) {
        document.getElementById("crm_comercial_alerta").innerHTML =
            "<div class=\"alert alert-warning py-3\"><div class=\"fw-bold\">No se pudo cargar CRM Comercial</div><div class=\"fs-7\">" +
            escapeHtml(error.message || String(error)) + "</div></div>";
    }

    function cargarTodo() {
        cargarComercial();
        cargarSegmentosCatalogo();
    }

    function cargarComercial() {
        request("/crm/clientes_comercial_resumen_erp").then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            renderComercialResumen(((response.depurar || {}).resumen) || {});
        }).catch(mostrarAlerta);
    }

    function renderComercialResumen(resumen) {
        var ddl = resumen.requiere_ddl_comercial;
        setText("crm_com_kpi_clientes", resumen.clientes_total || 0);
        setText("crm_com_kpi_segmentos", resumen.segmentos_activos || 0);
        setText("crm_com_kpi_relaciones", resumen.relaciones_segmento_activas || 0);
        setText("crm_com_kpi_condiciones", resumen.condiciones_comerciales || 0);

        var html = "<div class=\"d-flex flex-wrap gap-2 mb-3\">" +
            badge("Segmento default: " + (resumen.clientes_con_segmento_default || 0), (resumen.clientes_con_segmento_default || 0) ? "success" : "warning") +
            badge("Lista default: " + (resumen.clientes_con_lista_default || 0), (resumen.clientes_con_lista_default || 0) ? "success" : "light") +
            badge("Condiciones: " + (resumen.condiciones_comerciales || 0), ddl ? "warning" : "success") +
            "</div>";
        html += "<div class=\"alert " + (ddl ? "alert-warning" : "alert-success") + " py-3 mb-0\">" +
            "<div class=\"fw-bold\">" + (ddl ? "DDL comercial pendiente" : "Condiciones comerciales disponibles") + "</div>" +
            "<div class=\"fs-8\">CRM define segmentacion y condiciones; POS/Ventas solo deben consumir contratos aprobados.</div>" +
            "</div>";
        document.getElementById("crm_comercial_resumen").innerHTML = html;
    }

    function segmentosBaseSugeridos() {
        return [
            {codigo: "PUBLICO_GENERAL", nombre: "Publico general", tipo: "comercial"},
            {codigo: "RECURRENTE", nombre: "Cliente recurrente", tipo: "comercial"},
            {codigo: "MAYOREO", nombre: "Mayoreo", tipo: "comercial"},
            {codigo: "VIP", nombre: "VIP autorizado", tipo: "comercial"},
            {codigo: "INSTALADOR", nombre: "Instalador / tecnico", tipo: "comercial"},
            {codigo: "CONVENIO", nombre: "Convenio especial", tipo: "comercial"},
            {codigo: "ECOMMERCE_REG", nombre: "Ecommerce registrado", tipo: "comercial"}
        ];
    }

    function cargarSegmentosCatalogo() {
        request("/crm/segmentos_catalogo_listar_erp?limite=100").then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            renderSegmentosCatalogo((response.depurar || {}).segmentos || []);
        }).catch(mostrarAlerta);
    }

    function renderSegmentosCatalogo(segmentos) {
        var sugeridos = segmentosBaseSugeridos();
        document.getElementById("crm_segmentos_resumen").innerHTML =
            "<div class=\"d-flex flex-wrap gap-2\">" +
            badge("Actuales: " + segmentos.length, segmentos.length ? "success" : "warning") +
            badge("Base sugerida: " + sugeridos.length, "primary") +
            badge("Tipos configurables", "info") +
            "</div>";

        var sugeridosHtml = "<div class=\"alert alert-light-primary py-3 mb-3\"><div class=\"fw-semibold mb-2\">Base sugerida para precios</div>" +
            "<div class=\"d-flex flex-wrap gap-2\">" + sugeridos.map(function (item) {
                return "<button class=\"btn btn-sm btn-light\" type=\"button\" data-crm-seg-base=\"" + escapeHtml(item.codigo) + "\" data-crm-seg-nombre=\"" + escapeHtml(item.nombre) + "\" data-crm-seg-tipo=\"" + escapeHtml(item.tipo) + "\">" + escapeHtml(item.codigo) + "</button>";
            }).join("") + "</div></div>";

        var tabla = "<div class=\"table-responsive crm-scroll\"><table class=\"table align-middle table-row-dashed gy-3 mb-0\"><thead><tr class=\"text-muted fw-bold fs-8 text-uppercase\"><th>Tipo de cliente</th><th>Uso</th><th>Clientes</th><th>Estatus</th><th class=\"text-end\">Acciones</th></tr></thead><tbody>";
        tabla += segmentos.map(function (item) {
            return "<tr><td><div class=\"fw-semibold\">" + escapeHtml(item.nombre || "") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.codigo || "") + "</div></td>" +
                "<td>" + badge(item.tipo || "comercial", "light") + "</td>" +
                "<td>" + escapeHtml(item.clientes_activos || 0) + "</td>" +
                "<td>" + badge(item.estatus || "-", item.estatus === "activo" ? "success" : "warning") + "</td>" +
                "<td class=\"text-end\"><div class=\"d-flex justify-content-end flex-wrap gap-2\">" +
                    botonSegmentoCatalogo("Cargar", "bi-pencil", "light-primary", item, "") +
                    (item.estatus === "activo" ? botonSegmentoCatalogo("Pausar", "bi-pause-circle", "light-warning", item, "pausado") : "") +
                    (item.estatus === "pausado" ? botonSegmentoCatalogo("Activar", "bi-check2-circle", "light-success", item, "activo") : "") +
                    (item.estatus !== "cancelado" ? botonSegmentoCatalogo("Cancelar", "bi-x-circle", "light-danger", item, "cancelado") : "") +
                "</div></td></tr>";
        }).join("") || "<tr><td colspan=\"5\" class=\"text-center text-muted py-6\">Sin segmentos configurados.</td></tr>";
        tabla += "</tbody></table></div>";
        document.getElementById("crm_segmentos_tabla").innerHTML = sugeridosHtml + tabla;
        bindSegmentos();
    }

    function botonSegmentoCatalogo(texto, icono, clase, item, estatusRapido) {
        if (!puedeOperar) {
            return "";
        }
        var attrs = " data-crm-seg-editar=\"" + escapeHtml(item.id_segmento_crm || "") + "\"" +
            " data-crm-seg-codigo=\"" + escapeHtml(item.codigo || "") + "\"" +
            " data-crm-seg-nombre=\"" + escapeHtml(item.nombre || "") + "\"" +
            " data-crm-seg-tipo=\"" + escapeHtml(item.tipo || "comercial") + "\"" +
            " data-crm-seg-estatus=\"" + escapeHtml(item.estatus || "activo") + "\"" +
            " data-crm-seg-descripcion=\"" + escapeHtml(item.descripcion || "") + "\"";
        if (estatusRapido) {
            attrs += " data-crm-seg-estatus-rapido=\"" + escapeHtml(estatusRapido) + "\"";
        } else {
            attrs += " data-crm-seg-cargar=\"1\"";
        }
        return "<button class=\"btn btn-sm btn-" + escapeHtml(clase) + "\" type=\"button\"" + attrs + "><i class=\"bi " + escapeHtml(icono) + "\"></i> " + escapeHtml(texto) + "</button>";
    }

    function bindSegmentos() {
        if (!puedeOperar) {
            return;
        }
        document.querySelectorAll("[data-crm-seg-base]").forEach(function (boton) {
            boton.addEventListener("click", function () {
                limpiarSegmentoCatalogo();
                setValue("crm_seg_codigo", boton.getAttribute("data-crm-seg-base") || "");
                setValue("crm_seg_nombre", boton.getAttribute("data-crm-seg-nombre") || "");
                setValue("crm_seg_tipo", boton.getAttribute("data-crm-seg-tipo") || "comercial");
                validarSegmentoCatalogo();
            });
        });
        document.querySelectorAll("[data-crm-seg-cargar]").forEach(function (boton) {
            boton.addEventListener("click", function () {
                cargarSegmentoCatalogoDesdeBoton(boton, boton.getAttribute("data-crm-seg-estatus") || "activo");
                validarSegmentoCatalogo();
            });
        });
        document.querySelectorAll("[data-crm-seg-estatus-rapido]").forEach(function (boton) {
            boton.addEventListener("click", function () {
                cargarSegmentoCatalogoDesdeBoton(boton, boton.getAttribute("data-crm-seg-estatus-rapido") || "activo");
                validarSegmentoCatalogo();
            });
        });
    }

    function cargarSegmentoCatalogoDesdeBoton(boton, estatus) {
        setValue("crm_seg_id", boton.getAttribute("data-crm-seg-editar") || "");
        setValue("crm_seg_codigo", boton.getAttribute("data-crm-seg-codigo") || "");
        setValue("crm_seg_nombre", boton.getAttribute("data-crm-seg-nombre") || "");
        setValue("crm_seg_tipo", boton.getAttribute("data-crm-seg-tipo") || "comercial");
        setValue("crm_seg_estatus", estatus || boton.getAttribute("data-crm-seg-estatus") || "activo");
        setValue("crm_seg_descripcion", boton.getAttribute("data-crm-seg-descripcion") || "");
    }

    function limpiarSegmentoCatalogo() {
        setValue("crm_seg_id", "");
        setValue("crm_seg_codigo", "");
        setValue("crm_seg_nombre", "");
        setValue("crm_seg_tipo", "comercial");
        setValue("crm_seg_estatus", "activo");
        setValue("crm_seg_descripcion", "");
        setValue("crm_seg_autorizar", "");
        document.getElementById("crm_segmentos_dryrun").innerHTML = "";
    }

    function validarSegmentoCatalogo() {
        if (!puedeOperar) {
            return;
        }
        requestPost("/crm/segmento_catalogo_dryrun_erp", {
            id_segmento_crm: value("crm_seg_id"),
            codigo: value("crm_seg_codigo"),
            nombre: value("crm_seg_nombre"),
            tipo: value("crm_seg_tipo"),
            estatus: value("crm_seg_estatus"),
            descripcion: value("crm_seg_descripcion")
        }).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            var data = response.depurar || {};
            var bloqueos = data.bloqueos || [];
            var avisos = data.avisos || [];
            var tipo = bloqueos.length ? "warning" : "success";
            var html = "<div class=\"alert alert-" + tipo + " py-3 mb-0\"><div class=\"fw-bold\">" + escapeHtml(response.mensaje || "") + "</div>";
            if (bloqueos.length) {
                html += "<ul class=\"fs-8 ps-4 mt-2 mb-0\">" + bloqueos.map(function (item) { return "<li>" + escapeHtml(item) + "</li>"; }).join("") + "</ul>";
            }
            if (avisos.length) {
                html += "<div class=\"fs-8 mt-2\">" + avisos.map(escapeHtml).join(" | ") + "</div>";
            }
            html += "</div>";
            document.getElementById("crm_segmentos_dryrun").innerHTML = html;
        }).catch(mostrarAlerta);
    }

    function guardarSegmentoCatalogo() {
        if (!puedeOperar) {
            return;
        }
        requestPost("/crm/segmento_catalogo_guardar_autorizado_erp", {
            id_segmento_crm: value("crm_seg_id"),
            codigo: value("crm_seg_codigo"),
            nombre: value("crm_seg_nombre"),
            tipo: value("crm_seg_tipo"),
            estatus: value("crm_seg_estatus"),
            descripcion: value("crm_seg_descripcion"),
            respaldo: value("crm_seg_respaldo"),
            autorizar: value("crm_seg_autorizar")
        }).then(function (response) {
            var tipo = response.error ? "danger" : (response.tipo || "success");
            var data = response.depurar || {};
            var html = "<div class=\"alert alert-" + tipo + " py-3 mb-0\"><div class=\"fw-bold\">" + escapeHtml(response.mensaje || "") + "</div>";
            if (data.validacion_respaldo) {
                html += "<div class=\"fs-8 mt-2\">Respaldo: " + escapeHtml(data.validacion_respaldo.ok ? "validado" : "pendiente") + "</div>";
            }
            if (response.error && data.requerido) {
                html += "<div class=\"fs-8 mt-2\">Requiere respaldo externo y token <span class=\"crm-code\">" + escapeHtml(data.requerido.autorizar || "CRM_CLIENTES_SEGMENTO_CATALOGO") + "</span>.</div>";
            }
            html += "</div>";
            document.getElementById("crm_segmentos_dryrun").innerHTML = html;
            if (!response.error) {
                setValue("crm_seg_autorizar", "");
                cargarSegmentosCatalogo();
                cargarComercial();
            }
        }).catch(mostrarAlerta);
    }

    document.addEventListener("DOMContentLoaded", function () {
        cargarTodo();
        document.getElementById("crm_comercial_recargar").addEventListener("click", cargarTodo);
        document.getElementById("crm_segmentos_recargar").addEventListener("click", cargarSegmentosCatalogo);
        if (puedeOperar) {
            document.getElementById("crm_seg_nuevo").addEventListener("click", limpiarSegmentoCatalogo);
            document.getElementById("crm_seg_validar").addEventListener("click", validarSegmentoCatalogo);
            document.getElementById("crm_seg_guardar").addEventListener("click", guardarSegmentoCatalogo);
        }
    });
})();
