"use strict";
(function () {
    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-29
     * Proposito: consumir endpoints CRM Clientes read-only.
     * Impacto: permite revisar fuentes, duplicados y schema sin tocar BD.
     */
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

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-29
     * Proposito: escapar valores legacy antes de renderizar.
     * Impacto: protege la consola CRM de datos capturados historicamente.
     */
    function escapeHtml(value) {
        var div = document.createElement("div");
        div.textContent = value == null ? "" : String(value);
        return div.innerHTML;
    }

    var permisosCrm = window.CRM_PERMISOS || {};
    var puedeEditar = permisosCrm.editar === true;
    var puedeAuditar = permisosCrm.auditoria === true;

    function setText(id, value) {
        var node = document.getElementById(id);
        if (node) {
            node.textContent = value == null ? "" : String(value);
        }
    }

    function badge(text, type) {
        return "<span class=\"badge badge-light-" + escapeHtml(type || "primary") + "\">" + escapeHtml(text || "") + "</span>";
    }

    function mostrarAlerta(error) {
        document.getElementById("crm_clientes_alerta").innerHTML =
            "<div class=\"alert alert-warning py-3\"><div class=\"fw-bold\">No se pudo cargar CRM Clientes</div><div class=\"fs-7\">" +
            escapeHtml(error.message || String(error)) + "</div></div>";
    }

    function cargarTodo() {
        cargarCanonicos();
        cargarCalidadCola();
        cargarTareas();
        cargarComercial();
        cargarSegmentosCatalogo();
        cargarReportes();
        cargarRecompensas();
        if (puedeAuditar) {
            cargarFuentes();
            cargarMigracion();
            cargarDuplicados();
            cargarPreview();
            cargarBorrador();
            cargarSchema();
        }
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-29
     * Proposito: mostrar clientes CRM canonicos reales.
     * Impacto: separa listado principal de fuentes legacy/POS.
     */
    function cargarCanonicos() {
        request("/crm/clientes_listar_erp?limite=25").then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            renderCanonicos(((response.depurar || {}).clientes) || []);
        }).catch(mostrarAlerta);
    }

    function renderCanonicos(clientes) {
        document.getElementById("crm_clientes_canonicos_tabla").innerHTML = clientes.map(function (cliente) {
            var calidad = cliente.calidad_operativa_resumen || {};
            var identificador = cliente.identificador_valor ?
                "<div><span class=\"badge badge-light-primary\">" + escapeHtml(cliente.identificador_tipo || "") + "</span> <span class=\"crm-code\">" + escapeHtml(cliente.identificador_valor || "") + "</span></div>" :
                "<span class=\"text-muted\">Sin identificador</span>";
            return "<tr><td><div class=\"fw-bold\">" + escapeHtml(cliente.nombre_publico || "") + "</div><div class=\"text-muted crm-code\">" + escapeHtml(cliente.codigo_cliente || "") + "</div></td>" +
                "<td>" + identificador + "</td>" +
                "<td>" + badge(cliente.estatus || "-", cliente.estatus === "activo" ? "success" : "warning") + " " + badge(cliente.calidad_datos || "-", "light") +
                "<div class=\"d-flex flex-wrap gap-1 mt-2\">" +
                badge(calidad.pos ? "POS" : "Sin POS", calidad.pos ? "success" : "warning") +
                badge(calidad.contacto ? "Contacto" : "Contacto pend.", calidad.contacto ? "success" : "warning") +
                badge(calidad.comercial ? "Comercial" : "No comercial", calidad.comercial ? "success" : "light") +
                "</div><div class=\"text-muted fs-8 mt-1\">" + escapeHtml(calidad.pendiente_principal || "") + "</div></td>" +
                "<td>" + escapeHtml(cliente.origen_alta || "-") + "</td>" +
                "<td class=\"text-end\"><a class=\"btn btn-sm btn-light-primary\" href=\"/crm/cliente/" + encodeURIComponent(cliente.id_cliente_crm) + "\"><i class=\"bi bi-person-vcard\"></i> Ficha</a></td></tr>";
        }).join("") || "<tr><td colspan=\"5\" class=\"text-center text-muted py-6\">Sin clientes CRM canonicos.</td></tr>";
    }

    function cargarCalidadCola() {
        request("/crm/clientes_calidad_cola_erp?limite=30").then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            var data = response.depurar || {};
            renderCalidadResumen(data.resumen || {});
            renderCalidadTabla(data.items || []);
        }).catch(mostrarAlerta);
    }

    function renderCalidadResumen(resumen) {
        document.getElementById("crm_calidad_resumen").innerHTML =
            "<div class=\"d-flex flex-wrap gap-2\">" +
            badge("Revisados: " + (resumen.total_revisado || 0), "primary") +
            badge("Sin POS: " + (resumen.pos_pendiente || 0), (resumen.pos_pendiente || 0) ? "warning" : "success") +
            badge("Sin contacto: " + (resumen.contacto_pendiente || 0), (resumen.contacto_pendiente || 0) ? "warning" : "success") +
            badge("Sin permiso: " + (resumen.permiso_pendiente || 0), (resumen.permiso_pendiente || 0) ? "warning" : "success") +
            badge("Sin consentimiento: " + (resumen.consentimiento_pendiente || 0), (resumen.consentimiento_pendiente || 0) ? "info" : "success") +
            badge("Legacy: " + (resumen.legacy_revision || 0), (resumen.legacy_revision || 0) ? "warning" : "success") +
            badge("Operativos: " + (resumen.operativos || 0), "success") +
            "</div>";
    }

    function renderCalidadTabla(items) {
        document.getElementById("crm_calidad_tabla").innerHTML = items.map(function (item) {
            var calidad = item.calidad_operativa_resumen || {};
            var prioridad = calidad.pendiente_principal || "Revision";
            var prioridadTipo = prioridad === "Ficha operativa" ? "success" : (calidad.legacy ? "warning" : "primary");
            var identificador = item.identificador_valor ?
                "<div class=\"text-muted fs-8\"><span class=\"crm-code\">" + escapeHtml(item.identificador_valor) + "</span></div>" :
                "<div class=\"text-muted fs-8\">Sin identificador principal</div>";
            return "<tr><td><div class=\"fw-bold\">" + escapeHtml(item.nombre_publico || "") + "</div><div class=\"crm-code text-muted fs-8\">" + escapeHtml(item.codigo_cliente || "") + "</div>" + identificador + "</td>" +
                "<td>" + badge(prioridad, prioridadTipo) + "<div class=\"d-flex flex-wrap gap-1 mt-2\">" +
                badge(calidad.pos ? "POS" : "POS pend.", calidad.pos ? "success" : "warning") +
                badge(calidad.contacto ? "Contacto" : "Contacto pend.", calidad.contacto ? "success" : "warning") +
                badge(calidad.comercial ? "Comercial" : "No comercial", calidad.comercial ? "success" : "light") +
                "</div></td>" +
                "<td>" + badge(item.estatus || "-", item.estatus === "activo" ? "success" : "warning") + " " + badge(item.origen_alta || "-", calidad.legacy ? "warning" : "light") + "</td>" +
                "<td class=\"text-end\"><div class=\"d-flex justify-content-end gap-2 flex-wrap\">" +
                "<button type=\"button\" class=\"btn btn-sm btn-light-info crm-tarea-dryrun\" data-id=\"" + escapeHtml(item.id_cliente_crm) + "\" data-prioridad=\"" + escapeHtml(prioridad) + "\" data-cliente=\"" + escapeHtml(item.nombre_publico || "") + "\"><i class=\"bi bi-list-check\"></i> Tarea</button>" +
                "<a class=\"btn btn-sm btn-light-primary\" href=\"/crm/cliente/" + encodeURIComponent(item.id_cliente_crm) + "\"><i class=\"bi bi-person-vcard\"></i> Ficha</a>" +
                "</div></td></tr>";
        }).join("") || "<tr><td colspan=\"4\" class=\"text-center text-muted py-6\">Sin pendientes de calidad.</td></tr>";
        document.querySelectorAll(".crm-tarea-dryrun").forEach(function (button) {
            button.addEventListener("click", function () {
                validarTareaDesdeCalidad(button);
            });
        });
    }

    function tipoTareaDesdePrioridad(prioridad) {
        if (prioridad === "Agregar contacto") { return "contacto"; }
        if (prioridad === "Confirmar permiso" || prioridad === "Registrar consentimiento") { return "consentimiento"; }
        if (prioridad === "Revisar origen legacy") { return "calidad_datos"; }
        return "calidad_datos";
    }

    function validarTareaDesdeCalidad(button) {
        var id = button.getAttribute("data-id") || "";
        var prioridadTexto = button.getAttribute("data-prioridad") || "Revision";
        var cliente = button.getAttribute("data-cliente") || "cliente CRM";
        var titulo = prioridadTexto + " - " + cliente;
        requestPost("/crm/cliente_tarea_dryrun_erp", {
            id_cliente_crm: id,
            tipo: tipoTareaDesdePrioridad(prioridadTexto),
            prioridad: prioridadTexto === "Revisar origen legacy" ? "alta" : "normal",
            titulo: titulo,
            descripcion: "Seguimiento sugerido desde cola de calidad CRM: " + prioridadTexto,
            origen_tipo: "cola_calidad",
            origen_id: id
        }).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            renderTareaDryRun(response);
        }).catch(mostrarAlerta);
    }

    function renderTareaDryRun(response) {
        var data = response.depurar || {};
        var bloqueos = data.bloqueos || [];
        var avisos = data.avisos || [];
        var tarea = data.tarea_propuesta || {};
        var html = "<div class=\"alert " + (bloqueos.length ? "alert-warning" : "alert-success") + " py-3 mb-4\">" +
            "<div class=\"fw-bold\">" + escapeHtml(response.mensaje || "") + "</div>" +
            "<div class=\"fs-8 mt-1\">Dry-run: no crea tarea ni notificacion.</div>" +
            "<div class=\"mt-2\"><span class=\"crm-code\">" + escapeHtml(tarea.titulo || "") + "</span> " + badge(tarea.prioridad || "normal", "light") + "</div>";
        if (bloqueos.length) {
            html += "<ul class=\"mb-0 mt-2 ps-4\">" + bloqueos.map(function (item) { return "<li>" + escapeHtml(item) + "</li>"; }).join("") + "</ul>";
        }
        if (avisos.length) {
            html += "<div class=\"mt-2 text-muted fs-8\">" + avisos.map(escapeHtml).join("<br>") + "</div>";
        }
        html += "</div>";
        document.getElementById("crm_tarea_dryrun_resultado").innerHTML = html;
    }

    function cargarTareas() {
        request("/crm/clientes_tareas_listar_erp?estatus=pendiente&limite=30").then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            var data = response.depurar || {};
            renderTareasResumen(data.resumen || {}, response);
            renderTareasTabla(data.tareas || []);
        }).catch(mostrarAlerta);
    }

    function renderTareasResumen(resumen, response) {
        if (resumen.requiere_ddl_seguimiento) {
            document.getElementById("crm_tareas_resumen").innerHTML =
                "<div class=\"alert alert-warning py-3 mb-0\"><div class=\"fw-bold\">" + escapeHtml(response.mensaje || "Seguimiento pendiente") + "</div>" +
                "<div class=\"fs-8\">Primero se requiere DDL con token CRM_CLIENTES_SEGUIMIENTO_DDL.</div></div>";
            return;
        }
        document.getElementById("crm_tareas_resumen").innerHTML =
            "<div class=\"d-flex flex-wrap gap-2\">" +
            badge("Pendientes: " + (resumen.pendientes || 0), "primary") +
            badge("Vencidas: " + (resumen.vencidas || 0), (resumen.vencidas || 0) ? "danger" : "success") +
            badge("Alta prioridad: " + (resumen.alta_prioridad || 0), (resumen.alta_prioridad || 0) ? "warning" : "success") +
            "</div>";
    }

    function renderTareasTabla(tareas) {
        document.getElementById("crm_tareas_tabla").innerHTML = tareas.map(function (tarea) {
            var prioridadTipo = tarea.prioridad === "urgente" ? "danger" : (tarea.prioridad === "alta" ? "warning" : "light");
            var vencimientoTipo = tarea.vencida ? "danger" : "light";
            return "<tr><td><div class=\"fw-bold\">" + escapeHtml(tarea.titulo || "") + "</div>" +
                "<div class=\"d-flex flex-wrap gap-1 mt-2\">" + badge(tarea.tipo || "-", "primary") + badge(tarea.prioridad || "-", prioridadTipo) + badge(tarea.estatus || "-", "light") + "</div></td>" +
                "<td><div class=\"fw-semibold\">" + escapeHtml(tarea.nombre_publico || "Cliente CRM") + "</div><div class=\"crm-code text-muted fs-8\">" + escapeHtml(tarea.codigo_cliente || "") + "</div></td>" +
                "<td>" + badge(tarea.fecha_vencimiento || "Sin fecha", vencimientoTipo) + "</td>" +
                "<td class=\"text-end\"><a class=\"btn btn-sm btn-light-primary\" href=\"/crm/cliente/" + encodeURIComponent(tarea.id_cliente_crm || "") + "\"><i class=\"bi bi-person-vcard\"></i> Ficha</a></td></tr>";
        }).join("") || "<tr><td colspan=\"4\" class=\"text-center text-muted py-6\">Sin tareas pendientes.</td></tr>";
    }

    function cargarComercial() {
        request("/crm/clientes_comercial_resumen_erp").then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            renderComercialResumen(((response.depurar || {}).resumen) || {});
        }).catch(mostrarAlerta);
    }

    function renderComercialResumen(resumen) {
        var ddl = resumen.requiere_ddl_comercial;
        var html = "<div class=\"d-flex flex-wrap gap-2 mb-3\">" +
            badge("Clientes: " + (resumen.clientes_total || 0), "primary") +
            badge("Segmento default: " + (resumen.clientes_con_segmento_default || 0), (resumen.clientes_con_segmento_default || 0) ? "success" : "warning") +
            badge("Lista default: " + (resumen.clientes_con_lista_default || 0), (resumen.clientes_con_lista_default || 0) ? "success" : "light") +
            badge("Segmentos activos: " + (resumen.segmentos_activos || 0), (resumen.segmentos_activos || 0) ? "success" : "warning") +
            badge("Relaciones: " + (resumen.relaciones_segmento_activas || 0), "primary") +
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
            badge("Tipos de cliente configurables", "info") +
            "</div>";

        var sugeridosHtml = "<div class=\"alert alert-light-primary py-3 mb-3\"><div class=\"fw-semibold mb-2\">Base sugerida para precios</div>" +
            "<div class=\"d-flex flex-wrap gap-2\">" + sugeridos.map(function (item) {
                return "<button class=\"btn btn-sm btn-light\" type=\"button\" data-crm-seg-base=\"" + escapeHtml(item.codigo) + "\" data-crm-seg-nombre=\"" + escapeHtml(item.nombre) + "\" data-crm-seg-tipo=\"" + escapeHtml(item.tipo) + "\">" + escapeHtml(item.codigo) + "</button>";
            }).join("") + "</div></div>";

        var tabla = "<div class=\"table-responsive\"><table class=\"table align-middle table-row-dashed gy-3 mb-0\"><thead><tr class=\"text-muted fw-bold fs-8 text-uppercase\"><th>Tipo de cliente</th><th>Uso</th><th>Clientes</th><th>Estatus</th><th class=\"text-end\">Acciones</th></tr></thead><tbody>";
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

        document.querySelectorAll("[data-crm-seg-base]").forEach(function (boton) {
            boton.addEventListener("click", function () {
                limpiarSegmentoCatalogo();
                document.getElementById("crm_seg_codigo").value = boton.getAttribute("data-crm-seg-base") || "";
                document.getElementById("crm_seg_nombre").value = boton.getAttribute("data-crm-seg-nombre") || "";
                document.getElementById("crm_seg_tipo").value = boton.getAttribute("data-crm-seg-tipo") || "comercial";
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

    function botonSegmentoCatalogo(texto, icono, clase, item, estatusRapido) {
        if (!puedeEditar) {
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

    function cargarSegmentoCatalogoDesdeBoton(boton, estatus) {
        document.getElementById("crm_seg_id").value = boton.getAttribute("data-crm-seg-editar") || "";
        document.getElementById("crm_seg_codigo").value = boton.getAttribute("data-crm-seg-codigo") || "";
        document.getElementById("crm_seg_nombre").value = boton.getAttribute("data-crm-seg-nombre") || "";
        document.getElementById("crm_seg_tipo").value = boton.getAttribute("data-crm-seg-tipo") || "comercial";
        document.getElementById("crm_seg_estatus").value = estatus || boton.getAttribute("data-crm-seg-estatus") || "activo";
        document.getElementById("crm_seg_descripcion").value = boton.getAttribute("data-crm-seg-descripcion") || "";
    }

    function limpiarSegmentoCatalogo() {
        document.getElementById("crm_seg_id").value = "";
        document.getElementById("crm_seg_codigo").value = "";
        document.getElementById("crm_seg_nombre").value = "";
        document.getElementById("crm_seg_tipo").value = "comercial";
        document.getElementById("crm_seg_estatus").value = "activo";
        document.getElementById("crm_seg_descripcion").value = "";
        document.getElementById("crm_seg_autorizar").value = "";
        document.getElementById("crm_segmentos_dryrun").innerHTML = "";
    }

    function validarSegmentoCatalogo() {
        requestPost("/crm/segmento_catalogo_dryrun_erp", {
            id_segmento_crm: document.getElementById("crm_seg_id").value,
            codigo: document.getElementById("crm_seg_codigo").value,
            nombre: document.getElementById("crm_seg_nombre").value,
            tipo: document.getElementById("crm_seg_tipo").value,
            estatus: document.getElementById("crm_seg_estatus").value,
            descripcion: document.getElementById("crm_seg_descripcion").value
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
        if (!puedeEditar) {
            return;
        }
        requestPost("/crm/segmento_catalogo_guardar_autorizado_erp", {
            id_segmento_crm: document.getElementById("crm_seg_id").value,
            codigo: document.getElementById("crm_seg_codigo").value,
            nombre: document.getElementById("crm_seg_nombre").value,
            tipo: document.getElementById("crm_seg_tipo").value,
            estatus: document.getElementById("crm_seg_estatus").value,
            descripcion: document.getElementById("crm_seg_descripcion").value,
            respaldo: document.getElementById("crm_seg_respaldo").value,
            autorizar: document.getElementById("crm_seg_autorizar").value
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
                document.getElementById("crm_seg_autorizar").value = "";
                cargarSegmentosCatalogo();
            }
        }).catch(mostrarAlerta);
    }

    function cargarReportes() {
        request("/crm/clientes_reportes_operativos_erp").then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            var data = response.depurar || {};
            renderReportes(data.resumen || {}, data.indicadores || []);
        }).catch(mostrarAlerta);
    }

    function renderReportes(resumen, indicadores) {
        var html = "<div class=\"row g-3 mb-3\">";
        html += indicadores.map(function (item) {
            var tipo = item.riesgo === "alto" ? "danger" : (item.riesgo === "medio" ? "warning" : "success");
            var total = Number(item.total || 0);
            var valor = Number(item.valor || 0);
            var porcentaje = total > 0 ? Math.round((valor / total) * 100) : 0;
            return "<div class=\"col-md-3\"><div class=\"crm-soft p-3 h-100\">" +
                "<div class=\"text-muted fs-8 text-uppercase\">" + escapeHtml(item.titulo || "") + "</div>" +
                "<div class=\"fw-bold fs-3\">" + escapeHtml(valor) + "</div>" +
                "<div class=\"d-flex justify-content-between align-items-center\"><span class=\"text-muted fs-8\">" + porcentaje + "%</span>" + badge(item.riesgo || "bajo", tipo) + "</div>" +
                "</div></div>";
        }).join("");
        html += "</div>";
        html += "<div class=\"d-flex flex-wrap gap-2 mb-3\">" +
            badge("Identificables POS: " + (resumen.identificables_pos || 0), "primary") +
            badge("Pendientes contacto: " + (resumen.pendientes_contacto || 0), (resumen.pendientes_contacto || 0) ? "warning" : "success") +
            badge("Pendientes consentimiento: " + (resumen.pendientes_consentimiento || 0), (resumen.pendientes_consentimiento || 0) ? "warning" : "success") +
            badge("Bloqueados comercial: " + (resumen.bloqueados_comercial || 0), (resumen.bloqueados_comercial || 0) ? "danger" : "success") +
            badge("Elegibles recompensas: " + (resumen.elegibles_recompensas || 0), "info") +
            badge("Elegibles garantia: " + (resumen.elegibles_garantia_extendida || 0), "info") +
            "</div>";
        html += "<div class=\"alert " + (resumen.requiere_ddl_comercial ? "alert-warning" : "alert-success") + " py-3 mb-0\">" +
            "<div class=\"fw-bold\">" + (resumen.requiere_ddl_comercial ? "Condiciones comerciales aun simuladas" : "Condiciones comerciales disponibles") + "</div>" +
            "<div class=\"fs-8\">Read-only: no crea tareas, no modifica clientes y no usa legacy para campanas.</div>" +
            "</div>";
        document.getElementById("crm_reportes_resumen").innerHTML = html;
    }

    function cargarRecompensas() {
        request("/crm/clientes_recompensas_resumen_erp").then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            renderRecompensasResumen(((response.depurar || {}).resumen) || {});
        }).catch(mostrarAlerta);
    }

    function renderRecompensasResumen(resumen) {
        var ddl = resumen.requiere_ddl_recompensas;
        var html = "<div class=\"d-flex flex-wrap gap-2 mb-3\">" +
            badge("Programas: " + (resumen.programas_activos || 0), ddl ? "warning" : "success") +
            badge("Cuentas: " + (resumen.cuentas_activas || 0), ddl ? "warning" : "primary") +
            badge("Movimientos: " + (resumen.movimientos_aplicados || 0), "primary") +
            badge("Saldo puntos: " + (resumen.saldo_puntos_total || 0), "info") +
            badge("Elegibles: " + (resumen.elegibles_recompensas || 0), "success") +
            badge("Bloqueados: " + (resumen.bloqueados_recompensas || 0), (resumen.bloqueados_recompensas || 0) ? "warning" : "success") +
            badge("Legacy no elegible: " + (resumen.legacy_no_elegible || 0), (resumen.legacy_no_elegible || 0) ? "warning" : "success") +
            "</div>";
        html += "<div class=\"alert " + (ddl ? "alert-warning" : "alert-success") + " py-3 mb-0\">" +
            "<div class=\"fw-bold\">" + (ddl ? "DDL de recompensas pendiente" : "Recompensas listas para operacion") + "</div>" +
            "<div class=\"fs-8\">Read-only: no otorga puntos, no redime puntos y no usa legacy sin revision.</div>" +
            "</div>";
        document.getElementById("crm_recompensas_resumen").innerHTML = html;
    }

    /**
     * IA: Codex GPT-5 | Fecha: 2026-06-29
     * Proposito: mostrar conteos por fuente de cliente.
     * Impacto: evidencia por que CRM requiere migracion auditada.
     */
    function cargarFuentes() {
        request("/crm/clientes_fuentes_auditar_erp").then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            var fuentes = ((response.depurar || {}).fuentes) || {};
            var legacy = fuentes.crm_clientes || {};
            var erp = fuentes.erp_clientes || {};
            var ventas = fuentes.erp_ventas || {};
            setText("crm_kpi_legacy", legacy.registros || 0);
            setText("crm_kpi_erp", erp.registros || 0);
            renderFuentes(fuentes);
            renderHallazgos((response.depurar || {}).hallazgos || []);
            if (legacy.duplicados_identificador) {
                setText("crm_kpi_duplicados", legacy.duplicados_identificador.length || 0);
            }
            if (ventas.registros != null) {
                // No se muestra como KPI principal para no mezclar ventas con fuente canonica.
            }
        }).catch(mostrarAlerta);
    }

    function renderFuentes(fuentes) {
        var rows = [
            ["crm_clientes legacy", fuentes.crm_clientes],
            ["erp_clientes POS/UAT", fuentes.erp_clientes],
            ["erp_ventas snapshots", fuentes.erp_ventas]
        ];
        document.getElementById("crm_fuentes_tabla").innerHTML = rows.map(function (row) {
            var fuente = row[1] || {};
            var estado = fuente.existe ? badge("Existe", "success") : badge("No existe", "warning");
            var extra = "";
            if (fuente.sin_identificador_util != null) {
                extra = "<div class=\"text-muted fs-8\">Sin identificador: " + escapeHtml(fuente.sin_identificador_util) + "</div>";
            }
            if (fuente.publico_general != null) {
                extra = "<div class=\"text-muted fs-8\">Publico general: " + escapeHtml(fuente.publico_general) + "</div>";
            }
            return "<tr><td><div class=\"fw-semibold\">" + escapeHtml(row[0]) + "</div>" + extra + "</td>" +
                "<td class=\"fw-bold\">" + escapeHtml(fuente.registros == null ? "-" : fuente.registros) + "</td>" +
                "<td>" + estado + "</td></tr>";
        }).join("");
    }

    function renderHallazgos(hallazgos) {
        if (!hallazgos.length) {
            document.getElementById("crm_clientes_alerta").innerHTML = "";
            return;
        }
        document.getElementById("crm_clientes_alerta").innerHTML =
            "<div class=\"alert alert-warning py-3\"><div class=\"fw-bold mb-2\">Hallazgos CRM</div><ul class=\"mb-0 ps-4\">" +
            hallazgos.map(function (item) { return "<li>" + escapeHtml(item.id || "") + ": " + escapeHtml(item.mensaje || "") + "</li>"; }).join("") +
            "</ul></div>";
    }

    function cargarMigracion() {
        request("/crm/clientes_migracion_plan_dryrun_erp").then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            var data = response.depurar || {};
            renderBloqueos(data.bloqueos || [], data.pasos || []);
        }).catch(mostrarAlerta);
    }

    function renderBloqueos(bloqueos, pasos) {
        var html = "";
        if (bloqueos.length) {
            html += "<div class=\"alert alert-warning py-3 mb-3\"><div class=\"fw-bold mb-1\">Migracion bloqueada correctamente</div><ul class=\"mb-0 ps-4\">" +
                bloqueos.map(function (item) { return "<li>" + escapeHtml(item) + "</li>"; }).join("") + "</ul></div>";
        } else {
            html += "<div class=\"alert alert-success py-3 mb-3\">Plan sin bloqueos tecnicos. Aun requiere autorizacion.</div>";
        }
        html += "<div class=\"fw-bold mb-2\">Pasos propuestos</div>";
        html += pasos.map(function (paso) {
            return "<div class=\"d-flex gap-3 mb-2\"><span class=\"badge badge-light-primary\">" + escapeHtml(paso.orden) + "</span><div><div class=\"fw-semibold\">" + escapeHtml(paso.accion) + "</div><div class=\"text-muted fs-8\">" + escapeHtml(paso.descripcion) + "</div></div></div>";
        }).join("");
        document.getElementById("crm_migracion_bloqueos").innerHTML = html;
    }

    function cargarDuplicados() {
        request("/crm/clientes_duplicados_dryrun_erp?limite=20").then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            var data = response.depurar || {};
            setText("crm_kpi_duplicados", data.total_grupos || 0);
            renderDuplicados(data.grupos || []);
        }).catch(mostrarAlerta);
    }

    function renderDuplicados(grupos) {
        document.getElementById("crm_duplicados_tabla").innerHTML = grupos.map(function (grupo) {
            var clientes = (grupo.items || []).map(function (item) {
                return "<div class=\"mb-1\"><span class=\"crm-code\">" + escapeHtml(item.fuente) + "#" + escapeHtml(item.id_origen) + "</span> " + escapeHtml(item.nombre || "") + "</div>";
            }).join("");
            return "<tr><td><div class=\"fw-bold crm-code\">" + escapeHtml(grupo.identificador) + "</div><div class=\"text-muted fs-8\">" + escapeHtml(grupo.recomendacion || "") + "</div></td>" +
                "<td>" + badge(grupo.total, grupo.severidad === "alta" ? "danger" : "warning") + "</td>" +
                "<td>" + (grupo.fuentes || []).map(function (fuente) { return badge(fuente, "light"); }).join(" ") + "</td>" +
                "<td>" + clientes + "<button type=\"button\" class=\"btn btn-sm btn-light-primary mt-2 crm-duplicado-revisar\" data-identificador=\"" + escapeHtml(grupo.identificador) + "\"><i class=\"bi bi-search\"></i> Revisar</button></td></tr>";
        }).join("") || "<tr><td colspan=\"4\" class=\"text-center text-muted py-6\">Sin duplicados probables.</td></tr>";
        document.querySelectorAll(".crm-duplicado-revisar").forEach(function (button) {
            button.addEventListener("click", function () {
                cargarRevisionDuplicado(button.getAttribute("data-identificador"));
            });
        });
    }

    function cargarRevisionDuplicado(identificador) {
        request("/crm/clientes_duplicado_revision_dryrun_erp?identificador=" + encodeURIComponent(identificador || "")).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            renderRevisionDuplicado(response.depurar || {});
        }).catch(mostrarAlerta);
    }

    function renderRevisionDuplicado(data) {
        var recomendacion = data.recomendacion || {};
        var items = data.items || [];
        var html = "<div class=\"d-flex flex-wrap gap-2 mb-3\">" +
            badge((data.identificador || {}).llave || "-", data.severidad === "alta" ? "danger" : "warning") +
            badge("Items: " + (data.total_items || 0), "primary") +
            badge(recomendacion.accion_sugerida || "revision_manual", "info") +
            "</div>";
        if ((recomendacion.motivos || []).length) {
            html += "<ul class=\"mb-3 ps-4\">" + recomendacion.motivos.map(function (motivo) { return "<li>" + escapeHtml(motivo) + "</li>"; }).join("") + "</ul>";
        }
        html += "<div class=\"table-responsive\"><table class=\"table table-sm align-middle mb-0\"><thead><tr class=\"text-muted fs-8 text-uppercase\"><th>Fuente</th><th>ID</th><th>Nombre</th><th>Campo</th><th>Valor</th></tr></thead><tbody>";
        html += items.map(function (item) {
            return "<tr><td>" + escapeHtml(item.fuente || "") + "</td><td class=\"crm-code\">" + escapeHtml(item.id_origen || "") + "</td><td>" + escapeHtml(item.nombre || "") + "</td><td>" + escapeHtml(item.campo || "") + "</td><td class=\"crm-code\">" + escapeHtml(item.valor || "") + "</td></tr>";
        }).join("") || "<tr><td colspan=\"5\" class=\"text-center text-muted py-4\">Sin items.</td></tr>";
        html += "</tbody></table></div>";
        html += "<div class=\"fs-8 text-muted mt-3\">Dry-run: no marca, no fusiona y no migra.</div>";
        document.getElementById("crm_duplicado_revision").innerHTML = html;
    }

    function cargarPreview() {
        request("/crm/clientes_migracion_preview_dryrun_erp?limite=20").then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            renderPreview(((response.depurar || {}).preview) || []);
        }).catch(mostrarAlerta);
    }

    function cargarBorrador() {
        request("/crm/clientes_migracion_borrador_dryrun_erp?offset=0&limite=20").then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            var data = response.depurar || {};
            renderBorrador(data.resumen || {}, data.lote || []);
        }).catch(mostrarAlerta);
    }

    function renderBorrador(resumen, lote) {
        document.getElementById("crm_borrador_resumen").innerHTML =
            "<div class=\"d-flex flex-wrap gap-2\">" +
            badge("Lote: " + (resumen.total_lote || 0), "primary") +
            badge("Migrables: " + (resumen.migrables || 0), "success") +
            badge("Duplicados: " + (resumen.bloqueados_duplicado || 0), "warning") +
            badge("Revision: " + (resumen.requieren_revision || 0), "info") +
            badge("Total legacy: " + (resumen.total_legacy || 0), "light") +
            "</div>";
        document.getElementById("crm_borrador_tabla").innerHTML = lote.map(function (item) {
            var cliente = item.cliente_propuesto || {};
            var origen = item.origen || {};
            var bloqueos = (item.bloqueos || []).concat(item.avisos || []);
            var tipoBadge = item.estado_borrador === "migrable_borrador" ? "success" : (item.estado_borrador === "bloqueado_duplicado" ? "warning" : "info");
            return "<tr><td><span class=\"crm-code\">" + escapeHtml(origen.entidad_origen || "") + "#" + escapeHtml(origen.id_origen || "") + "</span></td>" +
                "<td><div class=\"fw-bold\">" + escapeHtml(cliente.nombre_publico || "") + "</div><div class=\"text-muted crm-code\">" + escapeHtml(cliente.codigo_cliente || "") + "</div></td>" +
                "<td>" + badge(item.estado_borrador || "-", tipoBadge) + "</td>" +
                "<td>" + (bloqueos.length ? bloqueos.map(function (b) { return "<div class=\"fs-8\">" + escapeHtml(b) + "</div>"; }).join("") : "<span class=\"text-muted\">Sin bloqueos</span>") + "</td></tr>";
        }).join("") || "<tr><td colspan=\"4\" class=\"text-center text-muted py-6\">Sin lote.</td></tr>";
    }

    function renderPreview(items) {
        document.getElementById("crm_preview_tabla").innerHTML = items.map(function (item) {
            var cliente = item.cliente_propuesto || {};
            var origen = item.origen || {};
            var ids = (item.identificadores_propuestos || []).map(function (identificador) {
                return "<div><span class=\"badge badge-light\">" + escapeHtml(identificador.tipo) + "</span> <span class=\"crm-code\">" + escapeHtml(identificador.valor_normalizado) + "</span></div>";
            }).join("") || "<span class=\"text-muted\">Sin identificador</span>";
            return "<tr><td><div class=\"crm-code\">" + escapeHtml(origen.entidad_origen || "") + "#" + escapeHtml(origen.id_origen || "") + "</div></td>" +
                "<td><div class=\"fw-bold\">" + escapeHtml(cliente.nombre_publico || "") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(cliente.codigo_cliente || "") + " | " + escapeHtml(cliente.calidad_datos || "") + "</div></td>" +
                "<td>" + ids + "</td>" +
                "<td>" + (item.requiere_revision ? badge("Revisar", "warning") : badge("Base", "success")) + "</td></tr>";
        }).join("") || "<tr><td colspan=\"4\" class=\"text-center text-muted py-6\">Sin preview disponible.</td></tr>";
    }

    function cargarSchema() {
        request("/crm/esquema_plan_clientes_crm").then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            var pasos = response.depurar || [];
            var pendientes = pasos.filter(function (paso) {
                return paso.depurar && paso.depurar.sql && paso.depurar.ejecutado === false;
            }).length;
            setText("crm_kpi_ddl", pendientes);
            document.getElementById("crm_schema_resumen").innerHTML =
                "<div class=\"d-flex flex-wrap gap-3 align-items-center\">" +
                "<div>" + badge("DDL total: " + pasos.length, "primary") + "</div>" +
                "<div>" + badge("Pendientes: " + pendientes, pendientes ? "warning" : "success") + "</div>" +
                "<div class=\"text-muted fs-7\">Apply bloqueado por token <span class=\"crm-code\">CRM_CLIENTES_DDL_BASE</span> y respaldo externo.</div>" +
                "</div>";
        }).catch(mostrarAlerta);
    }

    function buscarExpress() {
        var q = document.getElementById("crm_busqueda_q").value.trim();
        if (!q) {
            document.getElementById("crm_busqueda_resultado").innerHTML = "<div class=\"alert alert-warning py-3 mb-0\">Captura un identificador o nombre.</div>";
            return;
        }
        request("/crm/clientes_buscar_express_dryrun_erp?q=" + encodeURIComponent(q)).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            var data = response.depurar || {};
            var resultados = data.resultados || [];
            var avisos = data.avisos || [];
            var html = "<div class=\"alert " + (resultados.length ? "alert-success" : "alert-warning") + " py-3 mb-0\"><div class=\"fw-bold\">" + escapeHtml(response.mensaje || "") + "</div>";
            html += "<div class=\"fs-8\">Normalizado: " + escapeHtml((data.normalizado || {}).tipo || "") + " / " + escapeHtml((data.normalizado || {}).valor_normalizado || "") + "</div>";
            if (avisos.length) {
                html += "<ul class=\"mb-0 mt-2 ps-4\">" + avisos.map(function (aviso) { return "<li>" + escapeHtml(aviso) + "</li>"; }).join("") + "</ul>";
            }
            html += "</div>";
            document.getElementById("crm_busqueda_resultado").innerHTML = html;
        }).catch(mostrarAlerta);
    }

    function iniciarPestanasWorkspace() {
        var tabs = [].slice.call(document.querySelectorAll("[data-bs-toggle=\"tab\"][href^=\"#crm_tab_\"]"));
        var activa = window.location.hash && window.location.hash.indexOf("#crm_tab_") === 0 ? window.location.hash : "";
        if (!activa && window.localStorage) {
            activa = window.localStorage.getItem("crm_clientes_tab_activa") || "";
        }
        if (activa) {
            var tab = document.querySelector("[data-bs-toggle=\"tab\"][href=\"" + activa + "\"]");
            if (tab && window.bootstrap && window.bootstrap.Tab) {
                window.bootstrap.Tab.getOrCreateInstance(tab).show();
            }
        }
        tabs.forEach(function (tab) {
            tab.addEventListener("shown.bs.tab", function (event) {
                if (window.localStorage) {
                    window.localStorage.setItem("crm_clientes_tab_activa", event.target.getAttribute("href") || "");
                }
                if (window.history && window.history.replaceState) {
                    window.history.replaceState(null, "", event.target.getAttribute("href") || window.location.pathname);
                }
            });
        });
    }

    document.addEventListener("DOMContentLoaded", function () {
        iniciarPestanasWorkspace();
        cargarTodo();
        document.getElementById("crm_clientes_recargar").addEventListener("click", cargarTodo);
        document.getElementById("crm_canonicos_recargar").addEventListener("click", cargarCanonicos);
        document.getElementById("crm_calidad_recargar").addEventListener("click", cargarCalidadCola);
        document.getElementById("crm_tareas_recargar").addEventListener("click", cargarTareas);
        document.getElementById("crm_comercial_recargar").addEventListener("click", cargarComercial);
        document.getElementById("crm_segmentos_recargar").addEventListener("click", cargarSegmentosCatalogo);
        if (puedeEditar) {
            document.getElementById("crm_seg_nuevo").addEventListener("click", limpiarSegmentoCatalogo);
            document.getElementById("crm_seg_validar").addEventListener("click", validarSegmentoCatalogo);
            document.getElementById("crm_seg_guardar").addEventListener("click", guardarSegmentoCatalogo);
        }
        document.getElementById("crm_reportes_recargar").addEventListener("click", cargarReportes);
        document.getElementById("crm_recompensas_recargar").addEventListener("click", cargarRecompensas);
        if (puedeAuditar) {
            document.getElementById("crm_fuentes_recargar").addEventListener("click", cargarFuentes);
            document.getElementById("crm_duplicados_recargar").addEventListener("click", cargarDuplicados);
            document.getElementById("crm_preview_recargar").addEventListener("click", cargarPreview);
            document.getElementById("crm_borrador_recargar").addEventListener("click", cargarBorrador);
        }
        document.getElementById("crm_buscar").addEventListener("click", buscarExpress);
        document.getElementById("crm_busqueda_q").addEventListener("keydown", function (event) {
            if (event.key === "Enter") {
                buscarExpress();
            }
        });
    });
})();
