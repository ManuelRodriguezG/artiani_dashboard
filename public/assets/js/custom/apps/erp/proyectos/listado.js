/**
 * IA: Codex GPT-5 | Fecha: 2026-07-24
 * Proposito: operar bandeja inicial de proyectos y tareas.
 * Impacto: UI Proyectos ERP; no precarga avances de otros modulos.
 */
(function () {
    var endpoints = {
        catalogos: "/proyecto/catalogos_erp",
        resumen: "/proyecto/resumen_erp",
        proyectos: "/proyecto/proyectos_listar_erp",
        tareas: "/proyecto/tareas_listar_erp",
        proyecto: "/proyecto/proyecto_consultar_erp",
        guardarProyecto: "/proyecto/proyecto_guardar_erp",
        guardarTarea: "/proyecto/tarea_guardar_erp",
        estatusTarea: "/proyecto/tarea_estatus_erp"
    };
    var state = {
        catalogos: {},
        proyectos: [],
        tareas: [],
        proyectoActivo: 0,
        modalProyecto: null,
        modalTarea: null
    };

    document.addEventListener("DOMContentLoaded", function () {
        state.modalProyecto = modal("proyectos_modal_proyecto");
        state.modalTarea = modal("proyectos_modal_tarea");
        enlazarEventos();
        cargarTodo();
    });

    function enlazarEventos() {
        click("proyectos_refrescar", cargarTodo);
        click("proyectos_nuevo_proyecto", abrirProyectoNuevo);
        click("proyectos_nueva_tarea", abrirTareaNueva);
        change("proyectos_filtro_estatus", cargarProyectos);
        change("proyectos_filtro_tipo", cargarProyectos);
        change("proyectos_tareas_estatus", cargarTareas);
        change("proyectos_tareas_prioridad", cargarTareas);
        change("proyectos_tareas_mias", cargarTareas);

        var buscar = document.getElementById("proyectos_buscar");
        if (buscar) {
            buscar.addEventListener("keydown", function (event) {
                if (event.key === "Enter") {
                    cargarProyectos();
                }
            });
        }

        var formProyecto = document.getElementById("proyectos_form_proyecto");
        if (formProyecto) {
            formProyecto.addEventListener("submit", guardarProyecto);
        }

        var formTarea = document.getElementById("proyectos_form_tarea");
        if (formTarea) {
            formTarea.addEventListener("submit", guardarTarea);
        }

        document.addEventListener("click", function (event) {
            var proyecto = event.target.closest("[data-proyecto-id]");
            if (proyecto) {
                event.preventDefault();
                seleccionarProyecto(Number(proyecto.getAttribute("data-proyecto-id")));
                return;
            }
            var editarTarea = event.target.closest("[data-tarea-editar]");
            if (editarTarea) {
                event.preventDefault();
                abrirTareaEditar(Number(editarTarea.getAttribute("data-tarea-editar")));
                return;
            }
            var cerrarTarea = event.target.closest("[data-tarea-cerrar]");
            if (cerrarTarea) {
                event.preventDefault();
                cerrarTareaCompletada(Number(cerrarTarea.getAttribute("data-tarea-cerrar")));
            }
        });
    }

    function cargarTodo() {
        consultar(endpoints.catalogos).then(function (respuesta) {
            if (respuesta.error === false) {
                state.catalogos = respuesta.depurar || {};
                llenarCatalogos();
            }
            return Promise.all([cargarResumen(), cargarProyectos(), cargarTareas()]);
        }).catch(function (error) {
            renderError(error.message);
        });
    }

    function cargarResumen() {
        return consultar(endpoints.resumen).then(function (respuesta) {
            if (respuesta.error === false) {
                renderSchema(respuesta);
                var kpis = respuesta.depurar && respuesta.depurar.kpis ? respuesta.depurar.kpis : {};
                texto("proyectos_kpi_activos", numero(kpis.proyectos_activos));
                texto("proyectos_kpi_tareas", numero(kpis.tareas_pendientes));
                texto("proyectos_kpi_vencidas", numero(kpis.tareas_vencidas));
                texto("proyectos_kpi_mias", numero(kpis.mis_tareas));
            }
        });
    }

    function cargarProyectos() {
        return consultar(endpoints.proyectos + "?" + queryProyectos()).then(function (respuesta) {
            if (respuesta.error === false) {
                renderSchema(respuesta);
                state.proyectos = respuesta.depurar && Array.isArray(respuesta.depurar.proyectos) ? respuesta.depurar.proyectos : [];
                renderProyectos();
                llenarSelectProyectos();
            }
        });
    }

    function cargarTareas() {
        return consultar(endpoints.tareas + "?" + queryTareas()).then(function (respuesta) {
            if (respuesta.error === false) {
                renderSchema(respuesta);
                state.tareas = respuesta.depurar && Array.isArray(respuesta.depurar.tareas) ? respuesta.depurar.tareas : [];
                renderTareas();
            }
        });
    }

    function queryProyectos() {
        var params = new URLSearchParams();
        agregar(params, "buscar", valor("proyectos_buscar"));
        agregar(params, "estatus", valor("proyectos_filtro_estatus"));
        agregar(params, "tipo", valor("proyectos_filtro_tipo"));
        return params.toString();
    }

    function queryTareas() {
        var params = new URLSearchParams();
        agregar(params, "estatus", valor("proyectos_tareas_estatus"));
        agregar(params, "prioridad", valor("proyectos_tareas_prioridad"));
        if (state.proyectoActivo > 0) {
            params.append("id_proyecto", String(state.proyectoActivo));
        }
        if (document.getElementById("proyectos_tareas_mias") && document.getElementById("proyectos_tareas_mias").checked) {
            params.append("mias", "1");
        }
        return params.toString();
    }

    function renderProyectos() {
        var contenedor = document.getElementById("proyectos_lista");
        if (!contenedor) {
            return;
        }
        if (!state.proyectos.length) {
            contenedor.innerHTML = '<div class="text-center text-muted py-10">Sin proyectos registrados</div>';
            limpiarDetalle();
            return;
        }
        contenedor.innerHTML = state.proyectos.map(function (proyecto) {
            var activo = Number(proyecto.id_proyecto) === state.proyectoActivo ? "border-primary bg-light-primary" : "border-gray-300";
            return '' +
                '<a href="#" class="d-block border ' + activo + ' rounded p-4 mb-3" data-proyecto-id="' + numero(proyecto.id_proyecto) + '">' +
                    '<div class="d-flex justify-content-between align-items-start gap-3">' +
                        '<div>' +
                            '<div class="fw-bold text-gray-900">' + escapeHtml(proyecto.nombre || "") + '</div>' +
                            '<div class="text-muted fs-7">' + escapeHtml(proyecto.folio || "") + '</div>' +
                        '</div>' +
                        '<span class="' + clasePrioridad(proyecto.prioridad) + '">' + escapeHtml(proyecto.prioridad || "normal") + '</span>' +
                    '</div>' +
                    '<div class="d-flex flex-wrap gap-2 mt-3">' +
                        '<span class="badge badge-light">' + escapeHtml(proyecto.estatus || "") + '</span>' +
                        '<span class="badge badge-light-info">' + escapeHtml(proyecto.modulo_relacionado || "general") + '</span>' +
                    '</div>' +
                    '<div class="text-muted fs-7 mt-3">Tareas abiertas: ' + numero(proyecto.tareas_abiertas) + ' / ' + numero(proyecto.total_tareas) + '</div>' +
                '</a>';
        }).join("");
        if (state.proyectoActivo <= 0) {
            seleccionarProyecto(Number(state.proyectos[0].id_proyecto), true);
        }
    }

    function renderTareas() {
        var body = document.getElementById("proyectos_tareas_body");
        if (!body) {
            return;
        }
        if (!state.tareas.length) {
            body.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-10">Sin tareas para los filtros seleccionados</td></tr>';
            return;
        }
        body.innerHTML = state.tareas.map(function (tarea) {
            var acciones = '';
            if (puede("editar")) {
                acciones += '<button class="btn btn-sm btn-light-primary me-2" data-tarea-editar="' + numero(tarea.id_tarea) + '">Editar</button>';
            }
            if (puede("cerrar") && tarea.estatus !== "completada") {
                acciones += '<button class="btn btn-sm btn-light-success" data-tarea-cerrar="' + numero(tarea.id_tarea) + '">Completar</button>';
            }
            return '' +
                '<tr>' +
                    '<td>' +
                        '<div class="fw-bold text-gray-900">' + escapeHtml(tarea.titulo || "") + '</div>' +
                        '<div class="text-muted fs-7">' + escapeHtml(tarea.descripcion || "") + '</div>' +
                    '</td>' +
                    '<td>' + escapeHtml(tarea.proyecto_nombre || "") + '</td>' +
                    '<td><span class="' + clasePrioridad(tarea.prioridad) + '">' + escapeHtml(tarea.prioridad || "normal") + '</span></td>' +
                    '<td><span class="badge badge-light">' + escapeHtml(tarea.estatus || "") + '</span></td>' +
                    '<td>' + escapeHtml(tarea.fecha_vencimiento || "") + '</td>' +
                    '<td class="text-end">' + acciones + '</td>' +
                '</tr>';
        }).join("");
    }

    function seleccionarProyecto(idProyecto, silencioso) {
        state.proyectoActivo = idProyecto;
        var proyecto = state.proyectos.find(function (item) {
            return Number(item.id_proyecto) === idProyecto;
        });
        if (!proyecto) {
            limpiarDetalle();
            return;
        }
        texto("proyectos_detalle_titulo", proyecto.nombre || "");
        texto("proyectos_detalle_subtitulo", proyecto.folio || "");
        texto("proyectos_detalle_descripcion", proyecto.descripcion || "");
        texto("proyectos_detalle_estatus", proyecto.estatus || "");
        texto("proyectos_detalle_prioridad", proyecto.prioridad || "");
        texto("proyectos_detalle_modulo", proyecto.modulo_relacionado || "general");
        renderProyectos();
        llenarSelectProyectos();
        if (!silencioso) {
            cargarTareas();
        }
    }

    function limpiarDetalle() {
        state.proyectoActivo = 0;
        texto("proyectos_detalle_titulo", "Selecciona un proyecto");
        texto("proyectos_detalle_subtitulo", "La bandeja esta lista para comenzar sin cargar avances previos.");
        texto("proyectos_detalle_descripcion", "");
        texto("proyectos_detalle_estatus", "Sin proyecto");
        texto("proyectos_detalle_prioridad", "Prioridad");
        texto("proyectos_detalle_modulo", "Modulo");
    }

    function abrirProyectoNuevo() {
        resetForm("proyectos_form_proyecto");
        setValue("proyecto_estatus", "activo");
        setValue("proyecto_prioridad", "normal");
        setValue("proyecto_tipo", "operacion_negocio");
        setValue("proyecto_modulo", "general");
        state.modalProyecto.show();
    }

    function abrirTareaNueva() {
        resetForm("proyectos_form_tarea");
        setValue("tarea_id_proyecto", state.proyectoActivo > 0 ? String(state.proyectoActivo) : "");
        setValue("tarea_estatus", "pendiente");
        setValue("tarea_prioridad", "normal");
        setValue("tarea_origen", "manual");
        setValue("tarea_modulo", "general");
        state.modalTarea.show();
    }

    function abrirTareaEditar(idTarea) {
        var tarea = state.tareas.find(function (item) {
            return Number(item.id_tarea) === idTarea;
        });
        if (!tarea) {
            return;
        }
        resetForm("proyectos_form_tarea");
        setValue("tarea_id_tarea", tarea.id_tarea);
        setValue("tarea_id_proyecto", tarea.id_proyecto);
        setValue("tarea_titulo", tarea.titulo);
        setValue("tarea_descripcion", tarea.descripcion);
        setValue("tarea_estatus", tarea.estatus);
        setValue("tarea_prioridad", tarea.prioridad);
        setValue("tarea_origen", tarea.origen);
        setValue("tarea_area", tarea.area_responsable);
        setValue("tarea_modulo", tarea.modulo_relacionado || "general");
        setValue("tarea_fecha_vencimiento", tarea.fecha_vencimiento);
        setValue("tarea_url_contexto", tarea.url_contexto);
        var requiere = document.getElementById("tarea_requiere_autorizacion");
        if (requiere) {
            requiere.checked = numero(tarea.requiere_autorizacion) === 1;
        }
        state.modalTarea.show();
    }

    function guardarProyecto(event) {
        event.preventDefault();
        enviar(endpoints.guardarProyecto, new FormData(event.target)).then(function (respuesta) {
            if (respuesta.error === false) {
                state.modalProyecto.hide();
                cargarResumen();
                cargarProyectos();
            } else {
                aviso(respuesta.mensaje || "No fue posible guardar proyecto", "warning");
            }
        }).catch(function (error) {
            aviso(error.message, "danger");
        });
    }

    function guardarTarea(event) {
        event.preventDefault();
        enviar(endpoints.guardarTarea, new FormData(event.target)).then(function (respuesta) {
            if (respuesta.error === false) {
                state.modalTarea.hide();
                cargarResumen();
                cargarProyectos();
                cargarTareas();
            } else {
                aviso(respuesta.mensaje || "No fue posible guardar tarea", "warning");
            }
        }).catch(function (error) {
            aviso(error.message, "danger");
        });
    }

    function cerrarTareaCompletada(idTarea) {
        if (idTarea <= 0) {
            return;
        }
        var data = new URLSearchParams();
        data.append("id_tarea", String(idTarea));
        data.append("estatus", "completada");
        data.append("comentario", "Completada desde bandeja de proyectos");
        enviar(endpoints.estatusTarea, data).then(function (respuesta) {
            if (respuesta.error === false) {
                cargarResumen();
                cargarProyectos();
                cargarTareas();
            } else {
                aviso(respuesta.mensaje || "No fue posible completar tarea", "warning");
            }
        }).catch(function (error) {
            aviso(error.message, "danger");
        });
    }

    function llenarCatalogos() {
        llenarSelect("proyectos_filtro_estatus", state.catalogos.estatus_proyecto || [], "Todos los estados", "");
        llenarSelect("proyectos_filtro_tipo", state.catalogos.tipos_proyecto || [], "Todos los tipos", "");
        llenarSelect("proyectos_tareas_estatus", state.catalogos.estatus_tarea || [], "Estados activos", "");
        llenarSelect("proyectos_tareas_prioridad", state.catalogos.prioridades || [], "Prioridad", "");
        llenarSelect("proyecto_tipo", state.catalogos.tipos_proyecto || [], "", "");
        llenarSelect("proyecto_modulo", state.catalogos.modulos || [], "", "general");
        llenarSelect("proyecto_estatus", state.catalogos.estatus_proyecto || [], "", "activo");
        llenarSelect("proyecto_prioridad", state.catalogos.prioridades || [], "", "normal");
        llenarSelect("tarea_estatus", state.catalogos.estatus_tarea || [], "", "pendiente");
        llenarSelect("tarea_prioridad", state.catalogos.prioridades || [], "", "normal");
        llenarSelect("tarea_origen", state.catalogos.origenes || [], "", "manual");
        llenarSelect("tarea_modulo", state.catalogos.modulos || [], "", "general");
    }

    function llenarSelectProyectos() {
        var select = document.getElementById("tarea_id_proyecto");
        if (!select) {
            return;
        }
        select.innerHTML = '<option value="">Selecciona proyecto</option>' + state.proyectos.map(function (proyecto) {
            return '<option value="' + numero(proyecto.id_proyecto) + '">' + escapeHtml(proyecto.nombre || "") + '</option>';
        }).join("");
        if (state.proyectoActivo > 0) {
            select.value = String(state.proyectoActivo);
        }
    }

    function llenarSelect(id, items, placeholder, seleccionado) {
        var select = document.getElementById(id);
        if (!select) {
            return;
        }
        var html = placeholder !== "" ? '<option value="">' + escapeHtml(placeholder) + '</option>' : "";
        html += items.map(function (item) {
            var valor = typeof item === "object" ? item.valor : item;
            var textoItem = typeof item === "object" ? item.texto : item;
            return '<option value="' + escapeHtml(valor) + '">' + escapeHtml(textoItem) + '</option>';
        }).join("");
        select.innerHTML = html;
        if (seleccionado) {
            select.value = seleccionado;
        }
    }

    function consultar(url) {
        return fetch(url, {
            method: "GET",
            credentials: "same-origin",
            headers: {"Accept": "application/json"}
        }).then(jsonResponse);
    }

    function enviar(url, data) {
        var body = data instanceof URLSearchParams ? data : new URLSearchParams(data);
        return fetch(url, {
            method: "POST",
            credentials: "same-origin",
            headers: {
                "Accept": "application/json",
                "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
                "X-CSRF-Token": window.ERP_CSRF_TOKEN || ""
            },
            body: body.toString()
        }).then(jsonResponse);
    }

    function jsonResponse(response) {
        if (!response.ok) {
            throw new Error("No fue posible consultar Proyectos");
        }
        return response.json();
    }

    function renderSchema(respuesta) {
        var alerta = document.getElementById("proyectos_schema_alerta");
        if (!alerta) {
            return;
        }
        var pendiente = respuesta && respuesta.depurar && respuesta.depurar.schema_pendiente;
        alerta.classList.toggle("d-none", !pendiente);
        if (pendiente) {
            alerta.textContent = "El modulo esta instalado, pero falta aplicar el esquema de base de datos para comenzar a usarlo.";
        }
    }

    function renderError(mensaje) {
        var lista = document.getElementById("proyectos_lista");
        var body = document.getElementById("proyectos_tareas_body");
        if (lista) {
            lista.innerHTML = '<div class="text-center text-danger py-10">' + escapeHtml(mensaje || "Error") + '</div>';
        }
        if (body) {
            body.innerHTML = '<tr><td colspan="6" class="text-center text-danger py-10">' + escapeHtml(mensaje || "Error") + '</td></tr>';
        }
    }

    function aviso(mensaje, tipo) {
        if (window.Swal) {
            Swal.fire({text: mensaje, icon: tipo || "info", buttonsStyling: false, confirmButtonText: "Entendido", customClass: {confirmButton: "btn btn-primary"}});
            return;
        }
        alert(mensaje);
    }

    function clasePrioridad(prioridad) {
        prioridad = String(prioridad || "").toLowerCase();
        if (prioridad === "critica") {
            return "badge badge-light-danger";
        }
        if (prioridad === "alta") {
            return "badge badge-light-warning";
        }
        if (prioridad === "info") {
            return "badge badge-light-info";
        }
        return "badge badge-light-primary";
    }

    function modal(id) {
        var elemento = document.getElementById(id);
        return elemento && window.bootstrap ? new bootstrap.Modal(elemento) : {show: function () {}, hide: function () {}};
    }

    function puede(accion) {
        var el = document.getElementById("proyectos_puede_" + accion);
        return el && el.value === "1";
    }

    function click(id, handler) {
        var el = document.getElementById(id);
        if (el) {
            el.addEventListener("click", handler);
        }
    }

    function change(id, handler) {
        var el = document.getElementById(id);
        if (el) {
            el.addEventListener("change", handler);
        }
    }

    function valor(id) {
        var el = document.getElementById(id);
        return el ? String(el.value || "").trim() : "";
    }

    function setValue(id, value) {
        var el = document.getElementById(id);
        if (el) {
            el.value = value || "";
        }
    }

    function texto(id, value) {
        var el = document.getElementById(id);
        if (el) {
            el.textContent = String(value || "");
        }
    }

    function resetForm(id) {
        var form = document.getElementById(id);
        if (form) {
            form.reset();
        }
    }

    function agregar(params, key, value) {
        if (value !== "") {
            params.append(key, value);
        }
    }

    function numero(value) {
        var n = Number(value);
        return Number.isFinite(n) ? n : 0;
    }

    function escapeHtml(value) {
        return String(value === null || value === undefined ? "" : value)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
})();
