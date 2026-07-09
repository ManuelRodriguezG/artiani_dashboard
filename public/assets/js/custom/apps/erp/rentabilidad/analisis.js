"use strict";
(function () {
    var defaults = {};
    function $(id) { return document.getElementById(id); }
    function request(url) { return fetch(url, {credentials: "same-origin"}).then(function (response) { return response.json(); }); }
    function post(url, data) {
        return fetch(url, {
            method: "POST",
            credentials: "same-origin",
            headers: {"Content-Type": "application/x-www-form-urlencoded", "X-CSRF-Token": window.ERP_CSRF_TOKEN || ""},
            body: new URLSearchParams(data).toString()
        }).then(function (response) { return response.json(); });
    }
    function escapeHtml(value) { var div = document.createElement("div"); div.textContent = value == null ? "" : String(value); return div.innerHTML; }
    function dinero(value) { return "$" + Number(value || 0).toLocaleString("es-MX", {minimumFractionDigits: 2, maximumFractionDigits: 2}); }
    function pct(value) { return value === null || value === undefined ? "-" : Number(value || 0).toFixed(2) + "%"; }
    function filtros() {
        return new URLSearchParams({
            q: $("rentabilidad_buscar").value.trim(),
            canal: $("rentabilidad_canal").value,
            riesgo: $("rentabilidad_riesgo").value,
            accion: $("rentabilidad_accion").value,
            stock: $("rentabilidad_stock").value,
            origen_costo: $("rentabilidad_origen_costo").value,
            proveedor: $("rentabilidad_proveedor").value.trim(),
            descuento_pct: $("rentabilidad_descuento").value,
            gasto_pct: $("rentabilidad_gasto").value,
            comision_pct: $("rentabilidad_comision").value,
            margen_objetivo_pct: $("rentabilidad_objetivo").value
        }).toString();
    }
    function cargarEscenarios() {
        return request("/rentabilidad/escenarios_erp").then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            (response.depurar.escenarios || []).forEach(function (item) { defaults[item.clave] = item; });
            aplicarDefaults();
        });
    }
    function aplicarDefaults() {
        var item = defaults[$("rentabilidad_canal").value] || {};
        $("rentabilidad_descuento").value = item.descuento_pct == null ? 0 : item.descuento_pct;
        $("rentabilidad_gasto").value = item.gasto_pct == null ? 0 : item.gasto_pct;
        $("rentabilidad_comision").value = item.comision_pct == null ? 0 : item.comision_pct;
        $("rentabilidad_objetivo").value = item.margen_objetivo_pct == null ? 0 : item.margen_objetivo_pct;
    }
    function cargar() {
        Promise.all([
            request("/rentabilidad/analizar_erp?" + filtros()),
            request("/rentabilidad/tablero_ejecutivo_erp?" + filtros()),
            request("/rentabilidad/revision_operativa_erp?" + filtros()),
            request("/rentabilidad/recomendaciones_erp?" + filtros() + "&limite=120"),
            request("/rentabilidad/matriz_escenarios_erp?" + filtros() + "&limite=120"),
            request("/rentabilidad/canales_recomendados_erp?" + filtros() + "&limite=120"),
            request("/rentabilidad/plan_cierre_erp?" + filtros() + "&limite=120"),
            request("/rentabilidad/impacto_cierre_erp?" + filtros() + "&limite=120"),
            request("/rentabilidad/hallazgos_cierre_erp?" + filtros() + "&limite=120"),
            request("/rentabilidad/prioridades_cierre_erp?" + filtros() + "&limite=120"),
            request("/rentabilidad/responsables_cierre_erp?" + filtros() + "&limite=120"),
            request("/rentabilidad/checklist_cierre_erp?" + filtros() + "&limite=120"),
            request("/rentabilidad/autorizaciones_cierre_erp?" + filtros() + "&limite=120"),
            request("/rentabilidad/precios_objetivo_erp?" + filtros() + "&limite=120"),
            request("/rentabilidad/sensibilidad_erp?" + filtros() + "&limite=120"),
            request("/rentabilidad/cierre_precios_auditar_erp?" + filtros()),
            request("/rentabilidad/semaforo_cierre_erp?" + filtros() + "&limite=120"),
            request("/rentabilidad/variaciones_costos_erp?" + filtros() + "&limite=120"),
            request("/rentabilidad/datos_base_auditar_erp?" + filtros() + "&limite=120"),
            request("/rentabilidad/fiscal_xml_auditar_erp?" + filtros() + "&limite=120")
        ]).then(function (responses) {
            var response = responses[0];
            if (response.error) { throw new Error(response.mensaje); }
            render(response.depurar || {});
            if (responses[1].error) { throw new Error(responses[1].mensaje); }
            renderTablero(responses[1].depurar || {});
            if (responses[2].error) { throw new Error(responses[2].mensaje); }
            renderRevision(responses[2].depurar || {});
            if (responses[3].error) { throw new Error(responses[3].mensaje); }
            renderRecomendaciones(responses[3].depurar || {});
            if (responses[4].error) { throw new Error(responses[4].mensaje); }
            renderMatriz(responses[4].depurar || {});
            if (responses[5].error) { throw new Error(responses[5].mensaje); }
            renderCanales(responses[5].depurar || {});
            if (responses[6].error) { throw new Error(responses[6].mensaje); }
            renderPlanCierre(responses[6].depurar || {});
            if (responses[7].error) { throw new Error(responses[7].mensaje); }
            renderImpactoCierre(responses[7].depurar || {});
            if (responses[8].error) { throw new Error(responses[8].mensaje); }
            renderHallazgosCierre(responses[8].depurar || {});
            if (responses[9].error) { throw new Error(responses[9].mensaje); }
            renderPrioridadesCierre(responses[9].depurar || {});
            if (responses[10].error) { throw new Error(responses[10].mensaje); }
            renderResponsablesCierre(responses[10].depurar || {});
            if (responses[11].error) { throw new Error(responses[11].mensaje); }
            renderChecklistCierre(responses[11].depurar || {});
            if (responses[12].error) { throw new Error(responses[12].mensaje); }
            renderAutorizacionesCierre(responses[12].depurar || {});
            if (responses[13].error) { throw new Error(responses[13].mensaje); }
            renderPreciosObjetivo(responses[13].depurar || {});
            if (responses[14].error) { throw new Error(responses[14].mensaje); }
            renderSensibilidad(responses[14].depurar || {});
            if (responses[15].error) { throw new Error(responses[15].mensaje); }
            renderCierre(responses[15].depurar || {});
            if (responses[16].error) { throw new Error(responses[16].mensaje); }
            renderSemaforo(responses[16].depurar || {});
            if (responses[17].error) { throw new Error(responses[17].mensaje); }
            renderVariaciones(responses[17].depurar || {});
            if (responses[18].error) { throw new Error(responses[18].mensaje); }
            renderDatosBase(responses[18].depurar || {});
            if (responses[19].error) { throw new Error(responses[19].mensaje); }
            renderFiscalXml(responses[19].depurar || {});
            cargarEscenariosAuditoria();
            cargarSnapshots();
            cargarVigenciaSnapshots();
            cargarPresentaciones();
            cargarPreciosAprobacion();
            cargarAprobacionesInternas();
            cargarAprobacionesAutorizacion();
            cargarAprobacionesInternasPersistentes();
            cargarFiscalPreflight();
            cargarWorkflowComercial();
            cargarEstadoModulo();
            cargarUsoComercial();
            cargarDesbloqueo();
            cargarAuditoriaFinal();
            cargarPreflightRecomendaciones();
            cargarRecomendacionesPersistentes();
        }).catch(function (error) {
            Swal.fire({text: error.message, icon: "error", confirmButtonText: "Aceptar"});
        });
    }
    function cargarCierre() {
        return request("/rentabilidad/cierre_precios_auditar_erp?" + filtros()).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            renderCierre(response.depurar || {});
        }).catch(function (error) {
            $("rentabilidad_cierre").innerHTML = "<div class=\"alert alert-danger mb-0\">" + escapeHtml(error.message) + "</div>";
        });
    }
    function cargarEscenariosAuditoria() {
        return request("/rentabilidad/escenarios_auditar_erp").then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            renderEscenariosAuditoria(response.depurar || {});
        }).catch(function (error) {
            $("rentabilidad_escenarios").innerHTML = "<div class=\"alert alert-danger mb-0\">" + escapeHtml(error.message) + "</div>";
        });
    }
    function cargarDatosBase() {
        return request("/rentabilidad/datos_base_auditar_erp?" + filtros() + "&limite=120").then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            renderDatosBase(response.depurar || {});
        }).catch(function (error) {
            $("rentabilidad_datos_base").innerHTML = "<div class=\"alert alert-danger mb-0\">" + escapeHtml(error.message) + "</div>";
        });
    }
    function cargarSemaforo() {
        return request("/rentabilidad/semaforo_cierre_erp?" + filtros() + "&limite=120").then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            renderSemaforo(response.depurar || {});
        }).catch(function (error) {
            $("rentabilidad_semaforo").innerHTML = "<div class=\"alert alert-danger mb-0\">" + escapeHtml(error.message) + "</div>";
        });
    }
    function cargarVariaciones() {
        return request("/rentabilidad/variaciones_costos_erp?" + filtros() + "&limite=120").then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            renderVariaciones(response.depurar || {});
        }).catch(function (error) {
            $("rentabilidad_variaciones").innerHTML = "<div class=\"alert alert-danger mb-0\">" + escapeHtml(error.message) + "</div>";
        });
    }
    function cargarFiscalXml() {
        return request("/rentabilidad/fiscal_xml_auditar_erp?" + filtros() + "&limite=120").then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            renderFiscalXml(response.depurar || {});
        }).catch(function (error) {
            $("rentabilidad_fiscal_xml").innerHTML = "<div class=\"alert alert-danger mb-0\">" + escapeHtml(error.message) + "</div>";
        });
    }
    function cargarFiscalPreflight() {
        return request("/rentabilidad/fiscal_preflight_erp?" + filtros() + "&limite=120").then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            renderFiscalPreflight(response.depurar || {});
        }).catch(function (error) {
            $("rentabilidad_fiscal_preflight").innerHTML = "<div class=\"alert alert-danger mb-0\">" + escapeHtml(error.message) + "</div>";
        });
    }
    function cargarWorkflowComercial() {
        return request("/rentabilidad/workflow_comercial_erp?" + filtros() + "&limite=120").then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            renderWorkflowComercial(response.depurar || {});
        }).catch(function (error) {
            $("rentabilidad_workflow").innerHTML = "<div class=\"alert alert-danger mb-0\">" + escapeHtml(error.message) + "</div>";
        });
    }
    function cargarEstadoModulo() {
        return request("/rentabilidad/estado_modulo_erp?" + filtros() + "&limite=120").then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            renderEstadoModulo(response.depurar || {});
        }).catch(function (error) {
            $("rentabilidad_estado_modulo").innerHTML = "<div class=\"alert alert-danger mb-0\">" + escapeHtml(error.message) + "</div>";
        });
    }
    function cargarUsoComercial() {
        return request("/rentabilidad/preflight_uso_comercial_erp?" + filtros() + "&limite=120").then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            renderUsoComercial(response.depurar || {});
        }).catch(function (error) {
            $("rentabilidad_uso_comercial").innerHTML = "<div class=\"alert alert-danger mb-0\">" + escapeHtml(error.message) + "</div>";
        });
    }
    function cargarDesbloqueo() {
        return request("/rentabilidad/plan_desbloqueo_erp?" + filtros() + "&limite=120").then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            renderDesbloqueo(response.depurar || {});
        }).catch(function (error) {
            $("rentabilidad_desbloqueo").innerHTML = "<div class=\"alert alert-danger mb-0\">" + escapeHtml(error.message) + "</div>";
        });
    }
    function cargarAuditoriaFinal() {
        return request("/rentabilidad/auditoria_final_erp?" + filtros() + "&limite=120").then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            renderAuditoriaFinal(response.depurar || {});
        }).catch(function (error) {
            $("rentabilidad_auditoria_final").innerHTML = "<div class=\"alert alert-danger mb-0\">" + escapeHtml(error.message) + "</div>";
        });
    }
    function cargarMatriz() {
        return request("/rentabilidad/matriz_escenarios_erp?" + filtros() + "&limite=120").then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            renderMatriz(response.depurar || {});
        }).catch(function (error) {
            $("rentabilidad_matriz").innerHTML = "<div class=\"alert alert-danger mb-0\">" + escapeHtml(error.message) + "</div>";
        });
    }
    function cargarCanales() {
        return request("/rentabilidad/canales_recomendados_erp?" + filtros() + "&limite=120").then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            renderCanales(response.depurar || {});
        }).catch(function (error) {
            $("rentabilidad_canales").innerHTML = "<div class=\"alert alert-danger mb-0\">" + escapeHtml(error.message) + "</div>";
        });
    }
    function cargarPlanCierre() {
        return request("/rentabilidad/plan_cierre_erp?" + filtros() + "&limite=120").then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            renderPlanCierre(response.depurar || {});
        }).catch(function (error) {
            $("rentabilidad_plan").innerHTML = "<div class=\"alert alert-danger mb-0\">" + escapeHtml(error.message) + "</div>";
        });
    }
    function cargarImpactoCierre() {
        return request("/rentabilidad/impacto_cierre_erp?" + filtros() + "&limite=120").then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            renderImpactoCierre(response.depurar || {});
        }).catch(function (error) {
            $("rentabilidad_impacto").innerHTML = "<div class=\"alert alert-danger mb-0\">" + escapeHtml(error.message) + "</div>";
        });
    }
    function cargarHallazgosCierre() {
        return request("/rentabilidad/hallazgos_cierre_erp?" + filtros() + "&limite=120").then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            renderHallazgosCierre(response.depurar || {});
        }).catch(function (error) {
            $("rentabilidad_hallazgos").innerHTML = "<div class=\"alert alert-danger mb-0\">" + escapeHtml(error.message) + "</div>";
        });
    }
    function cargarPrioridadesCierre() {
        return request("/rentabilidad/prioridades_cierre_erp?" + filtros() + "&limite=120").then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            renderPrioridadesCierre(response.depurar || {});
        }).catch(function (error) {
            $("rentabilidad_prioridades").innerHTML = "<div class=\"alert alert-danger mb-0\">" + escapeHtml(error.message) + "</div>";
        });
    }
    function cargarResponsablesCierre() {
        return request("/rentabilidad/responsables_cierre_erp?" + filtros() + "&limite=120").then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            renderResponsablesCierre(response.depurar || {});
        }).catch(function (error) {
            $("rentabilidad_responsables").innerHTML = "<div class=\"alert alert-danger mb-0\">" + escapeHtml(error.message) + "</div>";
        });
    }
    function cargarChecklistCierre() {
        return request("/rentabilidad/checklist_cierre_erp?" + filtros() + "&limite=120").then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            renderChecklistCierre(response.depurar || {});
        }).catch(function (error) {
            $("rentabilidad_checklist").innerHTML = "<div class=\"alert alert-danger mb-0\">" + escapeHtml(error.message) + "</div>";
        });
    }
    function cargarAutorizacionesCierre() {
        return request("/rentabilidad/autorizaciones_cierre_erp?" + filtros() + "&limite=120").then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            renderAutorizacionesCierre(response.depurar || {});
        }).catch(function (error) {
            $("rentabilidad_autorizaciones").innerHTML = "<div class=\"alert alert-danger mb-0\">" + escapeHtml(error.message) + "</div>";
        });
    }
    function cargarPreciosAprobacion() {
        return request("/rentabilidad/precios_aprobacion_preflight_erp?" + filtros() + "&limite=120").then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            renderPreciosAprobacion(response.depurar || {});
        }).catch(function (error) {
            $("rentabilidad_precios_aprobacion").innerHTML = "<div class=\"alert alert-danger mb-0\">" + escapeHtml(error.message) + "</div>";
        });
    }
    function cargarAprobacionesInternas() {
        return request("/rentabilidad/aprobaciones_internas_preflight_erp?" + filtros() + "&limite=120").then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            renderAprobacionesInternas(response.depurar || {});
        }).catch(function (error) {
            $("rentabilidad_aprobaciones_internas").innerHTML = "<div class=\"alert alert-danger mb-0\">" + escapeHtml(error.message) + "</div>";
        });
    }
    function cargarAprobacionesInternasPersistentes() {
        return request("/rentabilidad/aprobaciones_internas_listar_erp?" + filtros() + "&limite=30").then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            renderAprobacionesInternasPersistentes(response.depurar || {});
        }).catch(function (error) {
            $("rentabilidad_aprobaciones_internas_persistentes").innerHTML = "<div class=\"alert alert-danger mb-0\">" + escapeHtml(error.message) + "</div>";
        });
    }
    function cargarAprobacionesAutorizacion() {
        return request("/rentabilidad/aprobaciones_autorizacion_paquete_erp?" + filtros() + "&limite=120").then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            renderAprobacionesAutorizacion(response.depurar || {});
        }).catch(function (error) {
            $("rentabilidad_aprobaciones_autorizacion").innerHTML = "<div class=\"alert alert-danger mb-0\">" + escapeHtml(error.message) + "</div>";
        });
    }
    function cargarPreciosObjetivo() {
        return request("/rentabilidad/precios_objetivo_erp?" + filtros() + "&limite=120").then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            renderPreciosObjetivo(response.depurar || {});
        }).catch(function (error) {
            $("rentabilidad_precios_objetivo").innerHTML = "<div class=\"alert alert-danger mb-0\">" + escapeHtml(error.message) + "</div>";
        });
    }
    function cargarSensibilidad() {
        return request("/rentabilidad/sensibilidad_erp?" + filtros() + "&limite=120").then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            renderSensibilidad(response.depurar || {});
        }).catch(function (error) {
            $("rentabilidad_sensibilidad").innerHTML = "<div class=\"alert alert-danger mb-0\">" + escapeHtml(error.message) + "</div>";
        });
    }
    function cargarTablero() {
        return request("/rentabilidad/tablero_ejecutivo_erp?" + filtros()).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            renderTablero(response.depurar || {});
        }).catch(function (error) {
            $("rentabilidad_tablero").innerHTML = "<div class=\"alert alert-danger mb-0\">" + escapeHtml(error.message) + "</div>";
        });
    }
    function cargarRevision() {
        return request("/rentabilidad/revision_operativa_erp?" + filtros()).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            renderRevision(response.depurar || {});
        }).catch(function (error) {
            $("rentabilidad_revision").innerHTML = "<div class=\"alert alert-danger mb-0\">" + escapeHtml(error.message) + "</div>";
        });
    }
    function guardarSnapshot() {
        Swal.fire({
            title: "Respaldo externo",
            text: "Indica la referencia o ruta del respaldo antes de guardar snapshot.",
            input: "text",
            inputPlaceholder: "Ej. C:\\xampp\\panel_db_backups\\...",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Continuar",
            cancelButtonText: "Cancelar",
            inputValidator: function (value) {
                return value && value.trim().length >= 8 ? undefined : "Captura una referencia de respaldo valida";
            }
        }).then(function (respaldo) {
            if (!respaldo.isConfirmed) { return; }
            return Swal.fire({
                title: "Confirmar snapshot",
                html: "Escribe <strong>AUTORIZO GUARDAR SNAPSHOT</strong> para continuar.",
                input: "text",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Guardar snapshot",
                cancelButtonText: "Cancelar",
                inputValidator: function (value) {
                    return value === "AUTORIZO GUARDAR SNAPSHOT" ? undefined : "La frase no coincide";
                }
            }).then(function (confirmacion) {
                if (!confirmacion.isConfirmed) { return; }
                var data = {
                    q: $("rentabilidad_buscar").value.trim(),
                    canal: $("rentabilidad_canal").value,
                    riesgo: $("rentabilidad_riesgo").value,
                    descuento_pct: $("rentabilidad_descuento").value,
                    gasto_pct: $("rentabilidad_gasto").value,
                    comision_pct: $("rentabilidad_comision").value,
                    margen_objetivo_pct: $("rentabilidad_objetivo").value,
                    respaldo_externo_ref: respaldo.value.trim(),
                    confirmar_autorizacion: confirmacion.value
                };
                post("/rentabilidad/snapshot_guardar_erp", data).then(function (response) {
                    if (response.error) { throw new Error(response.mensaje); }
                    Swal.fire({text: response.mensaje + " (" + (response.depurar.folio || "") + ")", icon: "success", confirmButtonText: "Aceptar"});
                    cargarSnapshots();
                    cargarVigenciaSnapshots();
                }).catch(function (error) {
                    Swal.fire({text: error.message, icon: "error", confirmButtonText: "Aceptar"});
                });
            });
        });
    }
    function cargarSnapshots() {
        return request("/rentabilidad/snapshots_erp").then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            renderSnapshots(response.depurar || {});
        }).catch(function (error) {
            $("rentabilidad_snapshots").innerHTML = "<div class=\"alert alert-danger mb-0\">" + escapeHtml(error.message) + "</div>";
        });
    }
    function cargarVigenciaSnapshots() {
        return request("/rentabilidad/snapshots_vigencia_erp").then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            renderVigenciaSnapshots(response.depurar || {});
        }).catch(function (error) {
            $("rentabilidad_snapshots_vigencia").innerHTML = "<div class=\"alert alert-danger mb-0\">" + escapeHtml(error.message) + "</div>";
        });
    }
    function cargarPresentaciones() {
        return request("/rentabilidad/costos_presentaciones_auditar_erp?" + new URLSearchParams({q: $("rentabilidad_buscar").value.trim(), limite: "120"}).toString()).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            renderPresentaciones(response.depurar || {});
        }).catch(function (error) {
            $("rentabilidad_presentaciones").innerHTML = "<div class=\"alert alert-danger mb-0\">" + escapeHtml(error.message) + "</div>";
        });
    }
    function guardarRecomendacionesPersistentes() {
        Swal.fire({
            title: "Respaldo externo",
            text: "Indica la referencia o ruta del respaldo antes de crear pendientes.",
            input: "text",
            inputPlaceholder: "Ej. C:\\xampp\\panel_db_backups\\...",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Continuar",
            cancelButtonText: "Cancelar",
            inputValidator: function (value) {
                return value && value.trim().length >= 8 ? undefined : "Captura una referencia de respaldo valida";
            }
        }).then(function (respaldo) {
            if (!respaldo.isConfirmed) { return; }
            return Swal.fire({
                title: "Confirmar escritura",
                html: "Escribe <strong>AUTORIZO CREAR RECOMENDACIONES</strong> para continuar.",
                input: "text",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Crear pendientes",
                cancelButtonText: "Cancelar",
                inputValidator: function (value) {
                    return value === "AUTORIZO CREAR RECOMENDACIONES" ? undefined : "La frase no coincide";
                }
            }).then(function (confirmacion) {
                if (!confirmacion.isConfirmed) { return; }
                var data = {
                    q: $("rentabilidad_buscar").value.trim(),
                    canal: $("rentabilidad_canal").value,
                    riesgo: $("rentabilidad_riesgo").value,
                    descuento_pct: $("rentabilidad_descuento").value,
                    gasto_pct: $("rentabilidad_gasto").value,
                    comision_pct: $("rentabilidad_comision").value,
                    margen_objetivo_pct: $("rentabilidad_objetivo").value,
                    respaldo_externo_ref: respaldo.value.trim(),
                    confirmar_autorizacion: confirmacion.value
                };
                post("/rentabilidad/recomendaciones_guardar_erp", data).then(function (response) {
                    if (response.error) { throw new Error(response.mensaje); }
                    Swal.fire({text: response.mensaje + ": " + Number(response.depurar.creadas || 0) + " nuevas", icon: "success", confirmButtonText: "Aceptar"});
                    cargarPreflightRecomendaciones();
                    cargarRecomendacionesPersistentes();
                }).catch(function (error) {
                    Swal.fire({text: error.message, icon: "error", confirmButtonText: "Aceptar"});
                });
            });
        });
    }
    function cargarPreflightRecomendaciones() {
        return request("/rentabilidad/recomendaciones_preflight_erp?" + filtros() + "&limite=120").then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            renderPreflightRecomendaciones(response.depurar || {});
        }).catch(function (error) {
            $("rentabilidad_recomendaciones_preflight").innerHTML = "<div class=\"alert alert-danger mb-0\">" + escapeHtml(error.message) + "</div>";
        });
    }
    function cargarRecomendacionesPersistentes() {
        return request("/rentabilidad/recomendaciones_listar_erp?estatus=pendiente").then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            renderRecomendacionesPersistentes(response.depurar || {});
        }).catch(function (error) {
            $("rentabilidad_recomendaciones_persistentes").innerHTML = "<div class=\"alert alert-danger mb-0\">" + escapeHtml(error.message) + "</div>";
        });
    }
    function resolverRecomendacion(id, accion) {
        Swal.fire({
            title: "Respaldo externo",
            text: "Indica la referencia o ruta del respaldo antes de resolver la recomendacion.",
            input: "text",
            inputPlaceholder: "Ej. C:\\xampp\\panel_db_backups\\...",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Continuar",
            cancelButtonText: "Cancelar",
            inputValidator: function (value) {
                return value && value.trim().length >= 8 ? undefined : "Captura una referencia de respaldo valida";
            }
        }).then(function (respaldo) {
            if (!respaldo.isConfirmed) { return; }
            return Swal.fire({
                title: "Confirmar resolucion",
                html: "Escribe <strong>AUTORIZO RESOLVER RECOMENDACION</strong> para continuar.",
                input: "text",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Resolver",
                cancelButtonText: "Cancelar",
                inputValidator: function (value) {
                    return value === "AUTORIZO RESOLVER RECOMENDACION" ? undefined : "La frase no coincide";
                }
            }).then(function (confirmacion) {
                if (!confirmacion.isConfirmed) { return; }
                post("/rentabilidad/recomendacion_resolver_erp", {
                    id_recomendacion: id,
                    accion: accion,
                    respaldo_externo_ref: respaldo.value.trim(),
                    confirmar_autorizacion: confirmacion.value
                }).then(function (response) {
                    if (response.error) { throw new Error(response.mensaje); }
                    cargarWorkflowComercial();
                    cargarRecomendacionesPersistentes();
                }).catch(function (error) {
                    Swal.fire({text: error.message, icon: "error", confirmButtonText: "Aceptar"});
                });
            });
        });
    }
    function render(data) {
        var resumen = data.resumen || {};
        $("rentabilidad_resumen").innerHTML =
            "<span class=\"badge badge-light-primary fs-7\">SKUs " + Number(resumen.skus || 0) + "</span>" +
            "<span class=\"badge badge-light-danger fs-7\">Perdida " + Number(resumen.perdida || 0) + "</span>" +
            "<span class=\"badge badge-light-warning fs-7\">Margen bajo " + Number(resumen.margen_bajo || 0) + "</span>" +
            "<span class=\"badge badge-light-info fs-7\">Sin costo " + Number(resumen.sin_costo || 0) + "</span>" +
            "<span class=\"badge badge-light-secondary fs-7\">Sin precio " + Number(resumen.sin_precio || 0) + "</span>" +
            "<span class=\"badge badge-light-success fs-7\">Valor inventario " + dinero(resumen.valor_inventario || 0) + "</span>";
        $("rentabilidad_items").innerHTML = (data.items || []).map(renderItem).join("") ||
            "<tr><td colspan=\"9\" class=\"text-center text-muted py-10\">Sin SKUs para el filtro seleccionado</td></tr>";
    }
    function renderRecomendaciones(data) {
        var grupos = data.grupos || {};
        var orden = ["cerrar_precio", "completar_costo", "completar_fiscal", "revisar_margen", "validar_inventario"];
        $("rentabilidad_recomendaciones").innerHTML = "<div class=\"row g-4\">" + orden.map(function (clave) {
            var grupo = grupos[clave] || {titulo: clave, total: 0, items: []};
            var items = (grupo.items || []).slice(0, 3).map(function (item) {
                return "<div class=\"border rounded p-3 mb-2\"><div class=\"fw-bold\">" + escapeHtml(item.sku || "") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.recomendacion || "") + "</div></div>";
            }).join("");
            return "<div class=\"col-xl col-md-6\"><div class=\"border rounded p-4 h-100\"><div class=\"d-flex justify-content-between align-items-center mb-3\"><span class=\"fw-bold\">" + escapeHtml(grupo.titulo || clave) + "</span><span class=\"badge badge-light-primary\">" + Number(grupo.total || 0) + "</span></div>" + (items || "<div class=\"text-muted fs-8\">Sin casos en la muestra</div>") + "</div></div>";
        }).join("") + "</div>";
    }
    function renderTablero(data) {
        var metricas = data.metricas || {};
        function lista(titulo, items, tipo, campo) {
            var filas = (items || []).slice(0, 4).map(function (item) {
                var valor = campo === "valor_inventario" ? dinero(item.valor_inventario) : dinero(item.utilidad);
                if (campo === "delta") { valor = dinero(item.delta); }
                return "<div class=\"border rounded p-3 mb-2\"><div class=\"d-flex justify-content-between\"><span class=\"fw-bold\">" + escapeHtml(item.sku || "") + "</span><span class=\"badge " + tipo + "\">" + valor + "</span></div><div class=\"text-muted fs-8\">" + escapeHtml(item.producto || "") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.recomendacion || "") + "</div></div>";
            }).join("");
            return "<div class=\"col-xl-3 col-md-6\"><div class=\"border rounded p-4 h-100\"><div class=\"fw-bold mb-3\">" + escapeHtml(titulo) + "</div>" + (filas || "<div class=\"text-muted fs-8\">Sin casos</div>") + "</div></div>";
        }
        $("rentabilidad_tablero").innerHTML =
            "<div class=\"d-flex flex-wrap gap-2 mb-4\">" +
            "<span class=\"badge badge-light-primary\">Utilidad " + dinero(metricas.utilidad_estimada_total || 0) + "</span>" +
            "<span class=\"badge badge-light-danger\">Utilidad negativa " + dinero(metricas.utilidad_negativa_total || 0) + "</span>" +
            "<span class=\"badge badge-light-warning\">Inventario riesgo " + dinero(metricas.valor_inventario_en_riesgo || 0) + "</span>" +
            "<span class=\"badge badge-light-success\">Inventario rentable " + dinero(metricas.valor_inventario_rentable || 0) + "</span>" +
            "<span class=\"badge badge-light-info\">Con stock " + Number(metricas.skus_con_stock || 0) + "</span>" +
            "</div><div class=\"row g-4\">" +
            lista("Perdidas", data.perdidas || [], "badge-light-danger", "utilidad") +
            lista("Oportunidades", data.oportunidades || [], "badge-light-success", "utilidad") +
            lista("Inventario expuesto", data.inventario_riesgo || [], "badge-light-warning", "valor_inventario") +
            lista("Accion de precio", data.acciones_precio || [], "badge-light-info", "delta") +
            "</div>";
    }
    function renderEscenariosAuditoria(data) {
        var resumen = data.resumen || {};
        var filas = (data.items || []).map(function (item) {
            var clase = item.estado === "activo" ? "badge-light-success" : (item.estado === "faltante" ? "badge-light-danger" : "badge-light-warning");
            var persistido = item.persistido || {};
            var diffs = (item.diferencias || []).map(function (diff) {
                return escapeHtml(diff.campo || "") + ": " + escapeHtml(diff.default) + " -> " + escapeHtml(diff.persistido);
            }).join("<br>");
            var defaults = item.default || {};
            return "<tr>" +
                "<td><div class=\"fw-bold\">" + escapeHtml(item.nombre || "") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.clave || "") + " / " + escapeHtml(item.canal || "") + "</div></td>" +
                "<td><span class=\"badge " + clase + "\">" + escapeHtml(item.estado || "") + "</span></td>" +
                "<td class=\"text-muted fs-8\">Desc. " + Number(defaults.descuento_pct || 0).toFixed(2) + "% / Gasto " + Number(defaults.gasto_pct || 0).toFixed(2) + "% / Com. " + Number(defaults.comision_pct || 0).toFixed(2) + "% / Margen " + Number(defaults.margen_objetivo_pct || 0).toFixed(2) + "%</td>" +
                "<td class=\"text-muted fs-8\">" + (item.persistido ? ("Desc. " + Number(persistido.descuento_pct || 0).toFixed(2) + "% / Gasto " + Number(persistido.gasto_pct || 0).toFixed(2) + "% / Com. " + Number(persistido.comision_pct || 0).toFixed(2) + "% / Margen " + Number(persistido.margen_objetivo_pct || 0).toFixed(2) + "%") : "Sin configuracion persistida") + "</td>" +
                "<td class=\"text-muted fs-8\">" + (diffs || "Sin diferencias") + "</td>" +
                "</tr>";
        }).join("");
        $("rentabilidad_escenarios").innerHTML =
            "<div class=\"d-flex flex-wrap gap-2 mb-4\">" +
            "<span class=\"badge badge-light-primary\">Semilla " + Number(resumen.semilla || 0) + "</span>" +
            "<span class=\"badge badge-light-success\">Persistidos " + Number(resumen.persistidos || 0) + "</span>" +
            "<span class=\"badge badge-light-danger\">Faltantes " + Number(resumen.faltantes || 0) + "</span>" +
            "<span class=\"badge badge-light-warning\">Diferentes " + Number(resumen.diferentes_default || 0) + "</span>" +
            "</div>" +
            (filas ? "<div class=\"table-responsive\"><table class=\"table align-middle table-row-dashed gy-3 mb-0\"><thead><tr class=\"text-muted fw-bold fs-8 text-uppercase\"><th>Escenario</th><th>Estado</th><th>Default</th><th>Persistido</th><th>Diferencias</th></tr></thead><tbody>" + filas + "</tbody></table></div>" : "<div class=\"text-muted fs-8\">Sin escenarios para auditar</div>");
    }
    function renderRevision(data) {
        var grupos = data.grupos || {};
        var orden = ["perdidas", "subir_precio", "completar_costo", "completar_precio", "inventario_expuesto", "oportunidad_stock"];
        var clases = {danger: "badge-light-danger", warning: "badge-light-warning", success: "badge-light-success", info: "badge-light-info"};
        $("rentabilidad_revision").innerHTML = "<div class=\"row g-4\">" + orden.map(function (clave) {
            var grupo = grupos[clave] || {titulo: clave, tipo: "info", total: 0, items: []};
            var items = (grupo.items || []).slice(0, 4).map(function (item) {
                var principal = clave === "subir_precio" ? dinero(item.delta) : (clave === "inventario_expuesto" ? dinero(item.valor_inventario) : dinero(item.utilidad));
                return "<div class=\"border rounded p-3 mb-2\"><div class=\"d-flex justify-content-between\"><span class=\"fw-bold\">" + escapeHtml(item.sku || "") + "</span><span class=\"badge " + (clases[grupo.tipo] || "badge-light-info") + "\">" + principal + "</span></div><div class=\"text-muted fs-8\">" + escapeHtml(item.producto || "") + "</div><div class=\"text-muted fs-8\">Margen " + pct(item.margen) + " / Disp. " + Number(item.disponible || 0).toFixed(2) + "</div></div>";
            }).join("");
            return "<div class=\"col-xl-4 col-md-6\"><div class=\"border rounded p-4 h-100\"><div class=\"d-flex justify-content-between mb-3\"><span class=\"fw-bold\">" + escapeHtml(grupo.titulo || clave) + "</span><span class=\"badge " + (clases[grupo.tipo] || "badge-light-info") + "\">" + Number(grupo.total || 0) + "</span></div>" + (items || "<div class=\"text-muted fs-8\">Sin casos</div>") + "</div></div>";
        }).join("") + "</div>";
    }
    function renderMatriz(data) {
        var canales = data.canales || [];
        var items = data.items || [];
        var resumen = canales.map(function (canal) {
            return "<div class=\"col-xl-4 col-md-6\"><div class=\"border rounded p-4 h-100\"><div class=\"d-flex justify-content-between mb-2\"><span class=\"fw-bold text-capitalize\">" + escapeHtml(canal.canal || "") + "</span><span class=\"badge badge-light-primary\">" + dinero(canal.utilidad_total || 0) + "</span></div><div class=\"d-flex flex-wrap gap-2\"><span class=\"badge badge-light-success\">Rentables " + Number(canal.rentables || 0) + "</span><span class=\"badge badge-light-warning\">Precaucion " + Number(canal.precaucion || 0) + "</span><span class=\"badge badge-light-danger\">Perdida " + Number(canal.perdida || 0) + "</span><span class=\"badge badge-light-info\">Bloq. " + Number(canal.bloqueados || 0) + "</span></div><div class=\"text-muted fs-8 mt-2\">Margen promedio " + pct(canal.margen_promedio) + "</div></div></div>";
        }).join("");
        var filas = items.slice(0, 12).map(function (item) {
            var escenarios = item.escenarios || {};
            function celda(canal) {
                var esc = escenarios[canal] || {};
                var clase = esc.riesgo === "perdida" ? "badge-light-danger" : (esc.riesgo === "incompleto" ? "badge-light-info" : (esc.riesgo === "margen_bajo" ? "badge-light-warning" : "badge-light-success"));
                return "<td><div class=\"fw-bold\">" + dinero(esc.utilidad) + "</div><div class=\"text-muted fs-8\">" + pct(esc.margen) + " / " + dinero(esc.precio) + "</div><span class=\"badge " + clase + "\">" + escapeHtml(esc.riesgo || "") + "</span></td>";
            }
            return "<tr><td><div class=\"fw-bold\">" + escapeHtml(item.sku || "") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.producto || "") + "</div><div class=\"text-muted fs-8\">Mejor: " + escapeHtml(item.mejor_canal || "-") + "</div></td>" + celda("menudeo") + celda("mayoreo") + celda("alianza") + "</tr>";
        }).join("");
        $("rentabilidad_matriz").innerHTML = "<div class=\"row g-4 mb-4\">" + resumen + "</div>" +
            (filas ? "<div class=\"table-responsive\"><table class=\"table align-middle table-row-dashed gy-3 mb-0\"><thead><tr class=\"text-muted fw-bold fs-8 text-uppercase\"><th>SKU</th><th>Menudeo</th><th>Mayoreo</th><th>Alianza</th></tr></thead><tbody>" + filas + "</tbody></table></div>" : "<div class=\"text-muted fs-8\">Sin SKUs para comparar</div>");
    }
    function renderCanales(data) {
        var resumen = data.resumen || {};
        var clases = {listos: "badge-light-success", precaucion: "badge-light-warning", bloqueados: "badge-light-danger"};
        var filas = (data.items || []).slice(0, 12).map(function (item) {
            var estado = item.estado || "";
            var canal = item.canal_recomendado || "-";
            return "<tr><td><div class=\"fw-bold\">" + escapeHtml(item.sku || "") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.producto || "") + "</div></td>" +
                "<td><span class=\"badge " + (clases[estado] || "badge-light-info") + "\">" + escapeHtml(estado) + "</span></td>" +
                "<td><div class=\"fw-bold text-capitalize\">" + escapeHtml(canal) + "</div><div class=\"text-muted fs-8\">Utilidad " + (item.utilidad == null ? "-" : dinero(item.utilidad)) + " / Margen " + pct(item.margen) + "</div></td>" +
                "<td class=\"text-muted fs-8\">" + escapeHtml(item.motivo || "") + "</td></tr>";
        }).join("");
        $("rentabilidad_canales").innerHTML =
            "<div class=\"d-flex flex-wrap gap-2 mb-4\">" +
            "<span class=\"badge badge-light-primary\">Evaluados " + Number(resumen.evaluados || 0) + "</span>" +
            "<span class=\"badge badge-light-success\">Listos " + Number(resumen.listos || 0) + "</span>" +
            "<span class=\"badge badge-light-warning\">Precaucion " + Number(resumen.precaucion || 0) + "</span>" +
            "<span class=\"badge badge-light-danger\">Bloqueados " + Number(resumen.bloqueados || 0) + "</span>" +
            "<span class=\"badge badge-light-info\">Menudeo " + Number(resumen.menudeo || 0) + "</span>" +
            "<span class=\"badge badge-light-info\">Mayoreo " + Number(resumen.mayoreo || 0) + "</span>" +
            "<span class=\"badge badge-light-info\">Alianza " + Number(resumen.alianza || 0) + "</span>" +
            "</div>" +
            (filas ? "<div class=\"table-responsive\"><table class=\"table align-middle table-row-dashed gy-3 mb-0\"><thead><tr class=\"text-muted fw-bold fs-8 text-uppercase\"><th>SKU</th><th>Estado</th><th>Canal</th><th>Motivo</th></tr></thead><tbody>" + filas + "</tbody></table></div>" : "<div class=\"text-muted fs-8\">Sin SKUs para recomendar canal</div>");
    }
    function renderPlanCierre(data) {
        var resumen = data.resumen || {};
        var grupos = data.grupos || {};
        var orden = ["completar_datos", "revisar_precio", "validar_costo", "completar_fiscal", "revisar_canal", "cerrar"];
        var clases = {danger: "badge-light-danger", warning: "badge-light-warning", success: "badge-light-success", info: "badge-light-info"};
        var tarjetas = orden.map(function (clave) {
            var grupo = grupos[clave] || {titulo: clave, tipo: "info", total: 0, items: []};
            var items = (grupo.items || []).slice(0, 4).map(function (item) {
                return "<div class=\"border rounded p-3 mb-2\">" +
                    "<div class=\"d-flex justify-content-between gap-3\"><span class=\"fw-bold\">" + escapeHtml(item.sku || "") + "</span><span class=\"badge badge-light-secondary text-capitalize\">" + escapeHtml(item.canal || "-") + "</span></div>" +
                    "<div class=\"text-muted fs-8\">" + escapeHtml(item.producto || "") + "</div>" +
                    "<div class=\"text-muted fs-8\">Utilidad " + dinero(item.utilidad) + " / Margen " + pct(item.margen) + "</div>" +
                    "<div class=\"fs-8 mt-1\">" + escapeHtml(item.siguiente_paso || "") + "</div>" +
                    "</div>";
            }).join("");
            return "<div class=\"col-xl-4 col-md-6\"><div class=\"border rounded p-4 h-100\">" +
                "<div class=\"d-flex justify-content-between align-items-center gap-3 mb-3\"><span class=\"fw-bold\">" + escapeHtml(grupo.titulo || clave) + "</span><span class=\"badge " + (clases[grupo.tipo] || "badge-light-info") + "\">" + Number(grupo.total || 0) + "</span></div>" +
                (items || "<div class=\"text-muted fs-8\">Sin casos</div>") +
                "</div></div>";
        }).join("");
        $("rentabilidad_plan").innerHTML =
            "<div class=\"d-flex flex-wrap gap-2 mb-4\">" +
            "<span class=\"badge badge-light-primary\">Evaluados " + Number(resumen.evaluados || 0) + "</span>" +
            "<span class=\"badge badge-light-danger\">Datos " + Number(resumen.completar_datos || 0) + "</span>" +
            "<span class=\"badge badge-light-danger\">Precio " + Number(resumen.revisar_precio || 0) + "</span>" +
            "<span class=\"badge badge-light-warning\">Costo " + Number(resumen.validar_costo || 0) + "</span>" +
            "<span class=\"badge badge-light-warning\">Fiscal " + Number(resumen.completar_fiscal || 0) + "</span>" +
            "<span class=\"badge badge-light-info\">Canal " + Number(resumen.revisar_canal || 0) + "</span>" +
            "<span class=\"badge badge-light-success\">Listos " + Number(resumen.cerrar || 0) + "</span>" +
            "</div><div class=\"row g-4\">" + tarjetas + "</div>";
    }
    function renderImpactoCierre(data) {
        var resumen = data.resumen || {};
        var grupos = data.grupos || {};
        var orden = ["completar_datos", "revisar_precio", "validar_costo", "completar_fiscal", "revisar_canal", "cerrar"];
        var clases = {danger: "badge-light-danger", warning: "badge-light-warning", success: "badge-light-success", info: "badge-light-info"};
        var filas = orden.map(function (clave) {
            var grupo = grupos[clave] || {};
            return "<tr>" +
                "<td><span class=\"badge " + (clases[grupo.tipo] || "badge-light-info") + "\">" + escapeHtml(grupo.titulo || clave) + "</span></td>" +
                "<td class=\"text-end\">" + Number(grupo.skus || 0) + "</td>" +
                "<td class=\"text-end\">" + dinero(grupo.utilidad_estimada) + "</td>" +
                "<td class=\"text-end\">" + dinero(grupo.utilidad_no_confiable) + "</td>" +
                "<td class=\"text-end\">" + dinero(grupo.utilidad_negativa) + "</td>" +
                "<td class=\"text-end\">" + dinero(grupo.deficit_precio) + "</td>" +
                "<td class=\"text-end\">" + dinero(grupo.valor_inventario) + "</td>" +
                "</tr>";
        }).join("");
        $("rentabilidad_impacto").innerHTML =
            "<div class=\"d-flex flex-wrap gap-2 mb-4\">" +
            "<span class=\"badge badge-light-primary\">Evaluados " + Number(resumen.evaluados || 0) + "</span>" +
            "<span class=\"badge badge-light-success\">Utilidad " + dinero(resumen.utilidad_estimada) + "</span>" +
            "<span class=\"badge badge-light-info\">No confiable " + dinero(resumen.utilidad_no_confiable) + "</span>" +
            "<span class=\"badge badge-light-danger\">Negativa " + dinero(resumen.utilidad_negativa) + "</span>" +
            "<span class=\"badge badge-light-warning\">Deficit precio " + dinero(resumen.deficit_precio) + "</span>" +
            "<span class=\"badge badge-light-secondary\">Inventario " + dinero(resumen.valor_inventario) + "</span>" +
            "</div>" +
            "<div class=\"table-responsive\"><table class=\"table align-middle table-row-dashed gy-3 mb-0\"><thead><tr class=\"text-muted fw-bold fs-8 text-uppercase\"><th>Bandeja</th><th class=\"text-end\">SKUs</th><th class=\"text-end\">Utilidad</th><th class=\"text-end\">No confiable</th><th class=\"text-end\">Negativa</th><th class=\"text-end\">Deficit</th><th class=\"text-end\">Inventario</th></tr></thead><tbody>" + filas + "</tbody></table></div>";
    }
    function renderHallazgosCierre(data) {
        var resumen = data.resumen || {};
        var clases = {danger: "badge-light-danger", warning: "badge-light-warning", success: "badge-light-success", info: "badge-light-info"};
        var filas = (data.hallazgos || []).slice(0, 10).map(function (item) {
            var skus = (item.items || []).slice(0, 3).map(function (sku) {
                return "<div class=\"text-muted fs-8\">" + escapeHtml(sku.sku || "") + " - " + escapeHtml(sku.recomendacion || "") + "</div>";
            }).join("");
            return "<tr>" +
                "<td><span class=\"badge " + (clases[item.tipo] || "badge-light-info") + "\">" + escapeHtml(item.id || "") + "</span><div class=\"fw-bold mt-1\">" + escapeHtml(item.clave || "") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.mensaje || "") + "</div></td>" +
                "<td class=\"text-end\">" + Number(item.skus || 0) + "</td>" +
                "<td class=\"text-end\">" + dinero(item.utilidad_confiable) + "</td>" +
                "<td class=\"text-end\">" + dinero(item.utilidad_no_confiable) + "</td>" +
                "<td class=\"text-end\">" + dinero(item.valor_inventario) + "</td>" +
                "<td>" + (skus || "<div class=\"text-muted fs-8\">Sin muestra</div>") + "</td>" +
                "</tr>";
        }).join("");
        $("rentabilidad_hallazgos").innerHTML =
            "<div class=\"d-flex flex-wrap gap-2 mb-4\">" +
            "<span class=\"badge badge-light-primary\">Evaluados " + Number(resumen.evaluados || 0) + "</span>" +
            "<span class=\"badge badge-light-warning\">SKUs con hallazgos " + Number(resumen.skus_con_hallazgos || 0) + "</span>" +
            "<span class=\"badge badge-light-secondary\">Hallazgos " + Number(resumen.hallazgos || 0) + "</span>" +
            "<span class=\"badge badge-light-success\">Utilidad " + dinero(resumen.utilidad_confiable_en_hallazgos) + "</span>" +
            "<span class=\"badge badge-light-info\">No confiable " + dinero(resumen.utilidad_no_confiable_en_hallazgos) + "</span>" +
            "<span class=\"badge badge-light-secondary\">Inventario " + dinero(resumen.valor_inventario_en_hallazgos) + "</span>" +
            "</div>" +
            (filas ? "<div class=\"table-responsive\"><table class=\"table align-middle table-row-dashed gy-3 mb-0\"><thead><tr class=\"text-muted fw-bold fs-8 text-uppercase\"><th>Hallazgo</th><th class=\"text-end\">SKUs</th><th class=\"text-end\">Utilidad</th><th class=\"text-end\">No confiable</th><th class=\"text-end\">Inventario</th><th>Muestra</th></tr></thead><tbody>" + filas + "</tbody></table></div>" : "<div class=\"text-muted fs-8\">Sin hallazgos para el filtro seleccionado</div>");
    }
    function renderPrioridadesCierre(data) {
        var resumen = data.resumen || {};
        var clases = {alta: "badge-light-danger", media: "badge-light-warning", baja: "badge-light-info"};
        var filas = (data.items || []).slice(0, 15).map(function (item) {
            return "<tr>" +
                "<td><div class=\"fw-bold\">" + escapeHtml(item.sku || "") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.producto || "") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.siguiente_paso || "") + "</div></td>" +
                "<td><span class=\"badge " + (clases[item.nivel] || "badge-light-info") + "\">" + escapeHtml(item.nivel || "") + "</span><div class=\"text-muted fs-8 mt-1\">Score " + Number(item.score || 0).toFixed(2) + "</div></td>" +
                "<td><div class=\"fw-bold\">" + escapeHtml(item.grupo || "") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.responsable_sugerido || "") + "</div></td>" +
                "<td class=\"text-end\"><div class=\"fw-bold\">" + dinero(item.utilidad) + "</div><div class=\"text-muted fs-8\">" + (item.utilidad_confiable ? "confiable" : "no confiable") + "</div></td>" +
                "<td class=\"text-end\">" + dinero(item.deficit_precio) + "</td>" +
                "<td class=\"text-end\">" + dinero(item.valor_inventario) + "</td>" +
                "</tr>";
        }).join("");
        $("rentabilidad_prioridades").innerHTML =
            "<div class=\"d-flex flex-wrap gap-2 mb-4\">" +
            "<span class=\"badge badge-light-primary\">Evaluados " + Number(resumen.evaluados || 0) + "</span>" +
            "<span class=\"badge badge-light-secondary\">Priorizados " + Number(resumen.prioridades || 0) + "</span>" +
            "<span class=\"badge badge-light-danger\">Alta " + Number(resumen.alta || 0) + "</span>" +
            "<span class=\"badge badge-light-warning\">Media " + Number(resumen.media || 0) + "</span>" +
            "<span class=\"badge badge-light-info\">Baja " + Number(resumen.baja || 0) + "</span>" +
            "</div>" +
            (filas ? "<div class=\"table-responsive\"><table class=\"table align-middle table-row-dashed gy-3 mb-0\"><thead><tr class=\"text-muted fw-bold fs-8 text-uppercase\"><th>SKU</th><th>Nivel</th><th>Bandeja</th><th class=\"text-end\">Utilidad</th><th class=\"text-end\">Deficit</th><th class=\"text-end\">Inventario</th></tr></thead><tbody>" + filas + "</tbody></table></div>" : "<div class=\"text-muted fs-8\">Sin prioridades para el filtro seleccionado</div>");
    }
    function renderResponsablesCierre(data) {
        var resumen = data.resumen || {};
        var filas = (data.items || []).map(function (item) {
            var muestra = (item.items || []).slice(0, 3).map(function (sku) {
                return "<div class=\"text-muted fs-8\">" + escapeHtml(sku.sku || "") + " - " + escapeHtml(sku.grupo || "") + "</div>";
            }).join("");
            return "<tr>" +
                "<td><div class=\"fw-bold\">" + escapeHtml(item.responsable || "") + "</div><div class=\"text-muted fs-8\">Score " + Number(item.score_total || 0).toFixed(2) + "</div></td>" +
                "<td class=\"text-end\">" + Number(item.skus || 0) + "</td>" +
                "<td class=\"text-end\"><span class=\"badge badge-light-danger\">" + Number(item.alta || 0) + "</span></td>" +
                "<td class=\"text-end\"><span class=\"badge badge-light-warning\">" + Number(item.media || 0) + "</span></td>" +
                "<td class=\"text-end\"><span class=\"badge badge-light-info\">" + Number(item.baja || 0) + "</span></td>" +
                "<td class=\"text-end\">" + dinero(item.utilidad_confiable) + "</td>" +
                "<td class=\"text-end\">" + dinero(item.utilidad_no_confiable) + "</td>" +
                "<td class=\"text-end\">" + dinero(item.deficit_precio) + "</td>" +
                "<td>" + (muestra || "<div class=\"text-muted fs-8\">Sin muestra</div>") + "</td>" +
                "</tr>";
        }).join("");
        $("rentabilidad_responsables").innerHTML =
            "<div class=\"d-flex flex-wrap gap-2 mb-4\">" +
            "<span class=\"badge badge-light-primary\">Responsables " + Number(resumen.responsables || 0) + "</span>" +
            "<span class=\"badge badge-light-secondary\">Prioridades " + Number(resumen.prioridades || 0) + "</span>" +
            "<span class=\"badge badge-light-danger\">Alta " + Number(resumen.alta || 0) + "</span>" +
            "<span class=\"badge badge-light-warning\">Media " + Number(resumen.media || 0) + "</span>" +
            "<span class=\"badge badge-light-info\">Baja " + Number(resumen.baja || 0) + "</span>" +
            "</div>" +
            (filas ? "<div class=\"table-responsive\"><table class=\"table align-middle table-row-dashed gy-3 mb-0\"><thead><tr class=\"text-muted fw-bold fs-8 text-uppercase\"><th>Responsable</th><th class=\"text-end\">SKUs</th><th class=\"text-end\">Alta</th><th class=\"text-end\">Media</th><th class=\"text-end\">Baja</th><th class=\"text-end\">Utilidad</th><th class=\"text-end\">No confiable</th><th class=\"text-end\">Deficit</th><th>Muestra</th></tr></thead><tbody>" + filas + "</tbody></table></div>" : "<div class=\"text-muted fs-8\">Sin responsables para el filtro seleccionado</div>");
    }
    function renderChecklistCierre(data) {
        var resumen = data.resumen || {};
        var clases = {ok: "badge-light-success", bloqueado: "badge-light-danger", info: "badge-light-info"};
        var checks = (data.checks || []).map(function (check) {
            var items = (check.items || []).slice(0, 3).map(function (item) {
                return "<div class=\"text-muted fs-8\">" + escapeHtml(item.sku || "") + " - " + escapeHtml(item.siguiente_paso || "") + "</div>";
            }).join("");
            return "<div class=\"col-xl-4 col-md-6\"><div class=\"border rounded p-4 h-100\">" +
                "<div class=\"d-flex justify-content-between align-items-start gap-3 mb-2\"><div><span class=\"badge badge-light-secondary\">" + escapeHtml(check.id || "") + "</span><div class=\"fw-bold mt-2\">" + escapeHtml(check.titulo || "") + "</div></div><span class=\"badge " + (clases[check.estado] || "badge-light-info") + "\">" + escapeHtml(check.estado || "") + "</span></div>" +
                "<div class=\"text-muted fs-8 mb-2\">" + escapeHtml(check.responsable || "") + " / " + escapeHtml(check.criterio || "") + "</div>" +
                "<div class=\"fw-bold mb-2\">SKUs " + Number(check.total || 0) + "</div>" +
                (items || "<div class=\"text-muted fs-8\">Sin muestra</div>") +
                "</div></div>";
        }).join("");
        $("rentabilidad_checklist").innerHTML =
            "<div class=\"d-flex flex-wrap gap-2 mb-4\">" +
            "<span class=\"badge badge-light-primary\">Evaluados " + Number(resumen.evaluados || 0) + "</span>" +
            "<span class=\"badge badge-light-success\">OK " + Number(resumen.ok || 0) + "</span>" +
            "<span class=\"badge badge-light-danger\">Bloqueados " + Number(resumen.bloqueados || 0) + "</span>" +
            "<span class=\"badge badge-light-info\">Informativos " + Number(resumen.informativos || 0) + "</span>" +
            "<span class=\"badge badge-light-warning\">SKUs bloqueados " + Number(resumen.skus_bloqueados || 0) + "</span>" +
            "<span class=\"badge badge-light-secondary\">Listos " + Number(resumen.skus_listos || 0) + "</span>" +
            "</div><div class=\"row g-4\">" + checks + "</div>";
    }
    function renderAutorizacionesCierre(data) {
        var resumen = data.resumen || {};
        var clases = {bloqueada: "badge-light-danger", requiere_respaldo: "badge-light-warning", lista: "badge-light-success"};
        var filas = (data.acciones || []).map(function (item) {
            var metricas = Object.keys(item.metricas || {}).map(function (clave) {
                return escapeHtml(clave) + ": " + escapeHtml(item.metricas[clave]);
            }).join(" / ");
            return "<tr>" +
                "<td><span class=\"badge badge-light-secondary\">" + escapeHtml(item.id || "") + "</span><div class=\"fw-bold mt-1\">" + escapeHtml(item.titulo || "") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.objetivo || "") + "</div></td>" +
                "<td><span class=\"badge " + (clases[item.estado] || "badge-light-info") + "\">" + escapeHtml(item.estado || "") + "</span><div class=\"text-muted fs-8 mt-1\">" + escapeHtml(item.permiso_requerido || "") + "</div></td>" +
                "<td class=\"text-muted fs-8\">" + escapeHtml(item.respaldo_requerido || "") + "</td>" +
                "<td><div class=\"text-muted fs-8\">" + escapeHtml(item.restriccion || "") + "</div><div class=\"fw-bold fs-8 mt-1\">" + metricas + "</div></td>" +
                "</tr>";
        }).join("");
        $("rentabilidad_autorizaciones").innerHTML =
            "<div class=\"d-flex flex-wrap gap-2 mb-4\">" +
            "<span class=\"badge badge-light-primary\">Acciones " + Number(resumen.acciones || 0) + "</span>" +
            "<span class=\"badge badge-light-danger\">Bloqueadas " + Number(resumen.bloqueadas || 0) + "</span>" +
            "<span class=\"badge badge-light-warning\">Con respaldo " + Number(resumen.requieren_respaldo || 0) + "</span>" +
            "<span class=\"badge badge-light-success\">Listas " + Number(resumen.listas || 0) + "</span>" +
            "</div>" +
            (filas ? "<div class=\"table-responsive\"><table class=\"table align-middle table-row-dashed gy-3 mb-0\"><thead><tr class=\"text-muted fw-bold fs-8 text-uppercase\"><th>Accion</th><th>Estado</th><th>Respaldo</th><th>Restriccion</th></tr></thead><tbody>" + filas + "</tbody></table></div>" : "<div class=\"text-muted fs-8\">Sin acciones preparadas</div>");
    }
    function renderPreciosAprobacion(data) {
        var resumen = data.resumen || {};
        var clases = {bloqueados: "badge-light-danger", requieren_revision: "badge-light-warning", aprobables: "badge-light-success"};
        var filas = (data.items || []).slice(0, 20).map(function (item) {
            var motivos = (item.bloqueos || []).concat(item.alertas || []).slice(0, 3).map(function (motivo) {
                return "<div class=\"text-muted fs-8\">" + escapeHtml(motivo.id || "") + " " + escapeHtml(motivo.clave || "") + "</div>";
            }).join("");
            return "<tr>" +
                "<td><div class=\"fw-bold\">" + escapeHtml(item.sku || "") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.producto || "") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.siguiente_paso || "") + "</div></td>" +
                "<td><span class=\"badge " + (clases[item.estado] || "badge-light-info") + "\">" + escapeHtml(item.estado || "") + "</span><div class=\"text-muted fs-8 mt-1\">" + escapeHtml(item.accion_precio || "") + "</div></td>" +
                "<td class=\"text-end\">" + dinero(item.precio_actual_sin_impuesto) + "</td>" +
                "<td class=\"text-end\">" + (item.precio_minimo_rentable == null ? "-" : dinero(item.precio_minimo_rentable)) + "</td>" +
                "<td class=\"text-end fw-bold\">" + dinero(item.precio_sugerido_sin_impuesto) + "</td>" +
                "<td class=\"text-end\">" + dinero(item.delta) + "</td>" +
                "<td>" + (motivos || "<div class=\"text-muted fs-8\">Sin bloqueos</div>") + "</td>" +
                "</tr>";
        }).join("");
        $("rentabilidad_precios_aprobacion").innerHTML =
            "<div class=\"d-flex flex-wrap gap-2 mb-4\">" +
            "<span class=\"badge badge-light-primary\">Evaluados " + Number(resumen.evaluados || 0) + "</span>" +
            "<span class=\"badge badge-light-success\">Aprobables " + Number(resumen.aprobables || 0) + "</span>" +
            "<span class=\"badge badge-light-warning\">Revision " + Number(resumen.requieren_revision || 0) + "</span>" +
            "<span class=\"badge badge-light-danger\">Bloqueados " + Number(resumen.bloqueados || 0) + "</span>" +
            "<span class=\"badge badge-light-info\">Subir " + Number(resumen.subir_precio || 0) + "</span>" +
            "<span class=\"badge badge-light-secondary\">Delta " + dinero(resumen.delta_total || 0) + "</span>" +
            "</div>" +
            (filas ? "<div class=\"table-responsive\"><table class=\"table align-middle table-row-dashed gy-3 mb-0\"><thead><tr class=\"text-muted fw-bold fs-8 text-uppercase\"><th>SKU</th><th>Estado</th><th class=\"text-end\">Actual</th><th class=\"text-end\">Minimo</th><th class=\"text-end\">Sugerido</th><th class=\"text-end\">Delta</th><th>Motivos</th></tr></thead><tbody>" + filas + "</tbody></table></div>" : "<div class=\"text-muted fs-8\">Sin SKUs para preflight de aprobacion</div>");
    }
    function renderAprobacionesInternas(data) {
        var resumen = data.resumen || {};
        var clases = {crear_aprobacion: "badge-light-success", schema_pendiente: "badge-light-warning", requiere_revision: "badge-light-info", bloqueada: "badge-light-danger"};
        var filas = (data.items || []).slice(0, 15).map(function (item) {
            var evidencia = item.evidencia || {};
            var bloqueos = (item.bloqueos || []).slice(0, 2).map(function (bloqueo) {
                return "<div class=\"text-muted fs-8\">" + escapeHtml(bloqueo.id || "") + " " + escapeHtml(bloqueo.clave || "") + "</div>";
            }).join("");
            return "<tr>" +
                "<td><div class=\"fw-bold\">" + escapeHtml(item.sku || "") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.producto || "") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.siguiente_paso || "") + "</div></td>" +
                "<td><span class=\"badge " + (clases[item.accion_preflight] || "badge-light-secondary") + "\">" + escapeHtml(item.accion_preflight || "") + "</span><div class=\"text-muted fs-8 mt-1\">" + escapeHtml(item.estado_precio || "") + "</div></td>" +
                "<td class=\"text-end\">" + dinero(item.precio_actual_sin_impuesto) + "</td>" +
                "<td class=\"text-end fw-bold\">" + dinero(item.precio_aprobado_sin_impuesto) + "</td>" +
                "<td><div class=\"text-muted fs-8\">Costo " + dinero(evidencia.costo_real_sin_impuesto) + " / " + escapeHtml(evidencia.origen_costo || "") + "</div><div class=\"text-muted fs-8\">Utilidad " + dinero(evidencia.utilidad_estimada) + " / Margen " + pct(evidencia.margen_bruto_pct) + "</div></td>" +
                "<td>" + (bloqueos || "<div class=\"text-muted fs-8\">Sin bloqueos</div>") + "</td>" +
                "</tr>";
        }).join("");
        $("rentabilidad_aprobaciones_internas").innerHTML =
            "<div class=\"d-flex flex-wrap gap-2 mb-4\">" +
            "<span class=\"badge badge-light-primary\">Evaluados " + Number(resumen.evaluados || 0) + "</span>" +
            "<span class=\"badge badge-light-success\">Creables " + Number(resumen.creables || 0) + "</span>" +
            "<span class=\"badge badge-light-warning\">Schema pendiente " + Number(resumen.schema_pendiente || 0) + "</span>" +
            "<span class=\"badge badge-light-info\">Revision " + Number(resumen.requieren_revision || 0) + "</span>" +
            "<span class=\"badge badge-light-danger\">Bloqueados " + Number(resumen.bloqueados || 0) + "</span>" +
            "<span class=\"badge badge-light-secondary\">Schema " + (Number(resumen.schema_disponible || 0) ? "disponible" : "pendiente") + "</span>" +
            "</div>" +
            (filas ? "<div class=\"table-responsive\"><table class=\"table align-middle table-row-dashed gy-3 mb-0\"><thead><tr class=\"text-muted fw-bold fs-8 text-uppercase\"><th>SKU</th><th>Accion</th><th class=\"text-end\">Actual</th><th class=\"text-end\">Aprobado</th><th>Evidencia</th><th>Bloqueos</th></tr></thead><tbody>" + filas + "</tbody></table></div>" : "<div class=\"text-muted fs-8\">Sin candidatos para aprobacion interna</div>");
    }
    function renderAprobacionesInternasPersistentes(data) {
        var resumen = data.resumen || {};
        if (!Number(resumen.schema_disponible || 0)) {
            $("rentabilidad_aprobaciones_internas_persistentes").innerHTML =
                "<div class=\"alert alert-warning mb-0\">Esquema de aprobaciones internas pendiente. El listado quedara disponible despues de aplicar el esquema autorizado.</div>";
            return;
        }
        var clases = {pendiente: "badge-light-warning", aprobada: "badge-light-success", rechazada: "badge-light-danger", cancelada: "badge-light-secondary", obsoleta: "badge-light-info", requiere_revision: "badge-light-primary"};
        var filas = (data.items || []).map(function (item) {
            return "<tr>" +
                "<td><div class=\"fw-bold\">" + escapeHtml(item.folio || "") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.fecha_registro || "") + "</div></td>" +
                "<td><div class=\"fw-bold\">" + escapeHtml(item.sku || "") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.producto || "") + "</div></td>" +
                "<td><span class=\"badge " + (clases[item.estatus] || "badge-light-secondary") + "\">" + escapeHtml(item.estatus || "") + "</span><div class=\"text-muted fs-8 mt-1\">" + escapeHtml(item.canal || "") + "</div></td>" +
                "<td class=\"text-end\">" + dinero(item.precio_actual_sin_impuesto) + "</td>" +
                "<td class=\"text-end fw-bold\">" + dinero(item.precio_aprobado_sin_impuesto) + "</td>" +
                "<td class=\"text-end\">" + dinero(item.utilidad_estimada) + "</td>" +
                "</tr>";
        }).join("");
        $("rentabilidad_aprobaciones_internas_persistentes").innerHTML =
            "<div class=\"d-flex flex-wrap gap-2 mb-4\">" +
            "<span class=\"badge badge-light-primary\">Total " + Number(resumen.total || 0) + "</span>" +
            "<span class=\"badge badge-light-warning\">Pendientes " + Number(resumen.pendiente || 0) + "</span>" +
            "<span class=\"badge badge-light-success\">Aprobadas " + Number(resumen.aprobada || 0) + "</span>" +
            "<span class=\"badge badge-light-danger\">Rechazadas " + Number(resumen.rechazada || 0) + "</span>" +
            "<span class=\"badge badge-light-info\">Revision " + Number(resumen.requiere_revision || 0) + "</span>" +
            "</div>" +
            (filas ? "<div class=\"table-responsive\"><table class=\"table align-middle table-row-dashed gy-3 mb-0\"><thead><tr class=\"text-muted fw-bold fs-8 text-uppercase\"><th>Folio</th><th>SKU</th><th>Estado</th><th class=\"text-end\">Actual</th><th class=\"text-end\">Aprobado</th><th class=\"text-end\">Utilidad</th></tr></thead><tbody>" + filas + "</tbody></table></div>" : "<div class=\"text-muted fs-8\">Sin aprobaciones internas guardadas</div>");
    }
    function renderAprobacionesAutorizacion(data) {
        var resumen = data.resumen || {};
        var acciones = (data.acciones || []).map(function (item) {
            var clase = item.estado === "requiere_autorizacion" ? "badge-light-warning" : (item.estado === "habilitable" ? "badge-light-success" : (item.estado === "bloqueado" ? "badge-light-danger" : "badge-light-info"));
            return "<tr>" +
                "<td><div class=\"fw-bold\">" + escapeHtml(item.id || "") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.prioridad || "") + "</div></td>" +
                "<td><span class=\"badge " + clase + "\">" + escapeHtml(item.estado || "") + "</span></td>" +
                "<td>" + escapeHtml(item.accion || "") + "<div class=\"text-muted fs-8 mt-1\">" + escapeHtml(item.comando || "") + "</div></td>" +
                "</tr>";
        }).join("");
        var auth = data.autorizaciones_requeridas || {};
        $("rentabilidad_aprobaciones_autorizacion").innerHTML =
            "<div class=\"d-flex flex-wrap gap-2 mb-4\">" +
            "<span class=\"badge badge-light-primary\">Estado " + escapeHtml(resumen.estado || "") + "</span>" +
            "<span class=\"badge badge-light-" + (Number(resumen.schema_disponible || 0) ? "success" : "warning") + "\">Schema " + (Number(resumen.schema_disponible || 0) ? "disponible" : "pendiente") + "</span>" +
            "<span class=\"badge badge-light-warning\">Tablas pendientes " + Number(resumen.schema_pendiente || 0) + "</span>" +
            "<span class=\"badge badge-light-success\">Creables " + Number(resumen.aprobaciones_creables || 0) + "</span>" +
            "<span class=\"badge badge-light-danger\">Bloqueadas " + Number(resumen.aprobaciones_bloqueadas || 0) + "</span>" +
            "<span class=\"badge badge-light-info\">Construccion " + escapeHtml(resumen.construccion || "") + "</span>" +
            "<span class=\"badge badge-light-secondary\">Uso comercial " + escapeHtml(resumen.uso_comercial || "") + "</span>" +
            "</div>" +
            "<div class=\"alert alert-warning py-3 mb-4\"><div class=\"fw-bold mb-1\">Frase para esquema</div><code>" + escapeHtml(auth.aplicar_esquema || "") + "</code></div>" +
            (acciones ? "<div class=\"table-responsive\"><table class=\"table align-middle table-row-dashed gy-3 mb-0\"><thead><tr class=\"text-muted fw-bold fs-8 text-uppercase\"><th>ID</th><th>Estado</th><th>Accion</th></tr></thead><tbody>" + acciones + "</tbody></table></div>" : "<div class=\"text-muted fs-8\">Sin acciones de autorizacion</div>");
    }
    function renderPreciosObjetivo(data) {
        var resumen = data.resumen || {};
        var items = data.items || [];
        function celda(item, canal) {
            var esc = (item.escenarios || {})[canal] || {};
            var clase = esc.estado === "subir_precio" ? "badge-light-warning" : (esc.estado === "viable" ? "badge-light-success" : "badge-light-info");
            return "<td><div class=\"fw-bold\">" + (esc.precio_minimo == null ? "-" : dinero(esc.precio_minimo)) + "</div><div class=\"text-muted fs-8\">Actual " + dinero(esc.precio_actual) + " / Delta " + (esc.delta == null ? "-" : dinero(esc.delta)) + "</div><span class=\"badge " + clase + "\">" + escapeHtml(esc.estado || "") + "</span></td>";
        }
        var filas = items.slice(0, 12).map(function (item) {
            return "<tr><td><div class=\"fw-bold\">" + escapeHtml(item.sku || "") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.producto || "") + "</div></td>" + celda(item, "menudeo") + celda(item, "mayoreo") + celda(item, "alianza") + "</tr>";
        }).join("");
        $("rentabilidad_precios_objetivo").innerHTML =
            "<div class=\"d-flex flex-wrap gap-2 mb-3\"><span class=\"badge badge-light-primary\">Evaluados " + Number(resumen.evaluados || 0) + "</span><span class=\"badge badge-light-warning\">Subir " + Number(resumen.requieren_subir || 0) + "</span><span class=\"badge badge-light-success\">Viables " + Number(resumen.ya_viables || 0) + "</span><span class=\"badge badge-light-info\">Sin costo " + Number(resumen.sin_costo || 0) + "</span><span class=\"badge badge-light-secondary\">Sin precio " + Number(resumen.sin_precio || 0) + "</span></div>" +
            (filas ? "<div class=\"table-responsive\"><table class=\"table align-middle table-row-dashed gy-3 mb-0\"><thead><tr class=\"text-muted fw-bold fs-8 text-uppercase\"><th>SKU</th><th>Menudeo</th><th>Mayoreo</th><th>Alianza</th></tr></thead><tbody>" + filas + "</tbody></table></div>" : "<div class=\"text-muted fs-8\">Sin precios objetivo para mostrar</div>");
    }
    function renderSensibilidad(data) {
        var resumen = data.resumen || {};
        var shock = data.shock || {};
        var claseEstado = {rentable: "badge-light-success", margen_bajo: "badge-light-warning", perdida: "badge-light-danger", incompleto: "badge-light-info"};
        var filas = (data.items || []).slice(0, 12).map(function (item) {
            var base = item.base || {};
            var combinado = item.combinado || {};
            return "<tr><td><div class=\"fw-bold\">" + escapeHtml(item.sku || "") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.producto || "") + "</div></td>" +
                "<td><div class=\"fw-bold\">" + dinero(base.utilidad) + "</div><div class=\"text-muted fs-8\">Margen " + pct(base.margen) + "</div></td>" +
                "<td><div class=\"fw-bold\">" + dinero(combinado.utilidad) + "</div><div class=\"text-muted fs-8\">Margen " + pct(combinado.margen) + "</div></td>" +
                "<td><span class=\"badge " + (claseEstado[combinado.estado] || "badge-light-info") + "\">" + escapeHtml(combinado.estado || "") + "</span><div class=\"text-muted fs-8 mt-1\">" + escapeHtml(item.recomendacion || "") + "</div></td></tr>";
        }).join("");
        $("rentabilidad_sensibilidad").innerHTML =
            "<div class=\"d-flex flex-wrap gap-2 mb-4\">" +
            "<span class=\"badge badge-light-primary\">Evaluados " + Number(resumen.evaluados || 0) + "</span>" +
            "<span class=\"badge badge-light-danger\">Vulnerables " + Number(resumen.vulnerables || 0) + "</span>" +
            "<span class=\"badge badge-light-success\">Resisten " + Number(resumen.resisten || 0) + "</span>" +
            "<span class=\"badge badge-light-info\">Incompletos " + Number(resumen.incompletos || 0) + "</span>" +
            "<span class=\"badge badge-light-warning\">Costo +" + Number(shock.costo_alza_pct || 0).toFixed(2) + "% / Precio -" + Number(shock.precio_baja_pct || 0).toFixed(2) + "%</span>" +
            "</div>" +
            (filas ? "<div class=\"table-responsive\"><table class=\"table align-middle table-row-dashed gy-3 mb-0\"><thead><tr class=\"text-muted fw-bold fs-8 text-uppercase\"><th>SKU</th><th>Base</th><th>Combinado</th><th>Dictamen</th></tr></thead><tbody>" + filas + "</tbody></table></div>" : "<div class=\"text-muted fs-8\">Sin SKUs completos para simular</div>");
    }
    function renderCierre(data) {
        var clases = {success: "badge-light-success", warning: "badge-light-warning", danger: "badge-light-danger", info: "badge-light-info"};
        var bloques = (data.bloqueos || []).filter(function (item) { return Number(item.total || 0) > 0; });
        var items = bloques.slice(0, 6).map(function (bloqueo) {
            var skus = (bloqueo.skus || []).map(function (item) {
                return "<div class=\"text-muted fs-8\">" + escapeHtml(item.sku || "") + " - " + escapeHtml(item.recomendacion || "") + "</div>";
            }).join("");
            return "<div class=\"col-xl-4 col-md-6\"><div class=\"border rounded p-4 h-100\"><div class=\"d-flex justify-content-between mb-2\"><span class=\"fw-bold\">" + escapeHtml(bloqueo.id || "") + " " + escapeHtml(bloqueo.titulo || "") + "</span><span class=\"badge " + (clases[bloqueo.tipo] || "badge-light-secondary") + "\">" + Number(bloqueo.total || 0) + "</span></div>" + (skus || "<div class=\"text-muted fs-8\">Sin SKUs</div>") + "</div></div>";
        }).join("");
        $("rentabilidad_cierre").innerHTML =
            "<div class=\"d-flex flex-wrap gap-2 mb-4\">" +
            "<span class=\"badge " + (clases[data.tipo] || "badge-light-secondary") + "\">" + escapeHtml(data.estado || "") + "</span>" +
            "<span class=\"badge badge-light-danger\">Bloqueos " + Number(data.bloqueos_duros || 0) + "</span>" +
            "<span class=\"badge badge-light-warning\">Alertas " + Number(data.alertas || 0) + "</span>" +
            "<span class=\"badge badge-light-info\">Presentaciones " + Number((data.presentaciones || {}).alertas || 0) + "</span>" +
            "<span class=\"badge badge-light-secondary\">Snapshots desfasados " + Number((data.snapshots || {}).desfasados || 0) + "</span>" +
            "<span class=\"badge badge-light-primary\">Pendientes " + Number(data.recomendaciones_pendientes || 0) + "</span>" +
            "</div><div class=\"fw-bold mb-3\">" + escapeHtml(data.mensaje_estado || "") + "</div>" +
            (items ? "<div class=\"row g-4\">" + items + "</div>" : "<div class=\"text-muted fs-8\">Sin bloqueos en la muestra</div>");
    }
    function renderSemaforo(data) {
        var resumen = data.resumen || {};
        var clases = {bloqueado: "badge-light-danger", precaucion: "badge-light-warning", listo: "badge-light-success"};
        var items = (data.items || []).slice(0, 12).map(function (item) {
            var motivos = (item.bloqueos || []).concat(item.alertas || []).slice(0, 3).map(function (motivo) {
                return "<span class=\"badge badge-light-secondary me-1 mb-1\">" + escapeHtml(motivo.id || "") + " " + escapeHtml(motivo.clave || "") + "</span>";
            }).join("");
            return "<tr><td><div class=\"fw-bold\">" + escapeHtml(item.sku || "") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.producto || "") + "</div></td>" +
                "<td><span class=\"badge " + (clases[item.estado] || "badge-light-info") + "\">" + escapeHtml(item.estado || "") + "</span></td>" +
                "<td><div class=\"fw-bold\">" + dinero(item.utilidad) + "</div><div class=\"text-muted fs-8\">Margen " + pct(item.margen) + "</div></td>" +
                "<td><div class=\"text-muted fs-8\">Precio " + dinero(item.precio) + "</div><div class=\"text-muted fs-8\">Min. " + (item.precio_minimo == null ? "-" : dinero(item.precio_minimo)) + "</div></td>" +
                "<td><div>" + (motivos || "<span class=\"badge badge-light-success\">Sin bloqueos</span>") + "</div><div class=\"text-muted fs-8 mt-1\">" + escapeHtml(item.siguiente_paso || "") + "</div></td></tr>";
        }).join("");
        $("rentabilidad_semaforo").innerHTML =
            "<div class=\"d-flex flex-wrap gap-2 mb-4\">" +
            "<span class=\"badge badge-light-primary\">Evaluados " + Number(resumen.evaluados || 0) + "</span>" +
            "<span class=\"badge badge-light-success\">Listos " + Number(resumen.listos || 0) + "</span>" +
            "<span class=\"badge badge-light-warning\">Precaucion " + Number(resumen.precaucion || 0) + "</span>" +
            "<span class=\"badge badge-light-danger\">Bloqueados " + Number(resumen.bloqueados || 0) + "</span>" +
            "<span class=\"badge badge-light-secondary\">Valor bloqueado " + dinero(resumen.valor_inventario_bloqueado || 0) + "</span>" +
            "</div>" +
            (items ? "<div class=\"table-responsive\"><table class=\"table align-middle table-row-dashed gy-3 mb-0\"><thead><tr class=\"text-muted fw-bold fs-8 text-uppercase\"><th>SKU</th><th>Estado</th><th>Utilidad</th><th>Precio</th><th>Dictamen</th></tr></thead><tbody>" + items + "</tbody></table></div>" : "<div class=\"text-muted fs-8\">Sin SKUs para el filtro seleccionado</div>");
    }
    function renderVariaciones(data) {
        var resumen = data.resumen || {};
        var items = data.items || [];
        var filas = items.slice(0, 12).map(function (item) {
            var comps = (item.comparaciones || []).slice(0, 4).map(function (comp) {
                var clase = comp.alerta ? "badge-light-warning" : "badge-light-success";
                return "<div class=\"d-flex justify-content-between gap-3 border-bottom py-1\"><span class=\"text-muted fs-8\">" + escapeHtml(comp.fuente || "") + " " + dinero(comp.costo_evidencia) + "</span><span class=\"badge " + clase + "\">" + Number(comp.diferencia_pct || 0).toFixed(2) + "%</span></div>";
            }).join("");
            return "<tr><td><div class=\"fw-bold\">" + escapeHtml(item.sku || "") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.producto || "") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.origen_costo || "") + "</div></td>" +
                "<td><div class=\"fw-bold\">" + dinero(item.costo_actual) + "</div><div class=\"text-muted fs-8\">Dif. max " + Number(item.mayor_diferencia_pct || 0).toFixed(2) + "%</div></td>" +
                "<td>" + (comps || "<div class=\"text-muted fs-8\">Sin comparaciones</div>") + "</td>" +
                "<td><span class=\"badge " + (Number(item.alertas || 0) ? "badge-light-warning" : "badge-light-success") + "\">" + Number(item.alertas || 0) + "</span><div class=\"text-muted fs-8 mt-1\">" + escapeHtml(item.recomendacion || "") + "</div></td></tr>";
        }).join("");
        $("rentabilidad_variaciones").innerHTML =
            "<div class=\"d-flex flex-wrap gap-2 mb-4\">" +
            "<span class=\"badge badge-light-primary\">Evaluados " + Number(resumen.evaluados || 0) + "</span>" +
            "<span class=\"badge badge-light-success\">Con evidencia " + Number(resumen.con_evidencia || 0) + "</span>" +
            "<span class=\"badge badge-light-secondary\">Sin evidencia " + Number(resumen.sin_evidencia || 0) + "</span>" +
            "<span class=\"badge badge-light-warning\">Alertas " + Number(resumen.alertas || 0) + "</span>" +
            "<span class=\"badge badge-light-info\">Umbral " + Number(data.umbral_pct || 0).toFixed(2) + "%</span>" +
            "</div>" +
            (filas ? "<div class=\"table-responsive\"><table class=\"table align-middle table-row-dashed gy-3 mb-0\"><thead><tr class=\"text-muted fw-bold fs-8 text-uppercase\"><th>SKU</th><th>Costo actual</th><th>Evidencia</th><th>Revision</th></tr></thead><tbody>" + filas + "</tbody></table></div>" : "<div class=\"text-muted fs-8\">Sin evidencia historica para la muestra</div>");
    }
    function renderDatosBase(data) {
        var grupos = data.grupos || {};
        var orden = ["costo", "precio", "fiscal", "margen"];
        $("rentabilidad_datos_base").innerHTML = "<div class=\"row g-4\">" + orden.map(function (clave) {
            var grupo = grupos[clave] || {titulo: clave, total: 0, items: [], tipo: "info"};
            var clase = grupo.tipo === "danger" ? "badge-light-danger" : (grupo.tipo === "warning" ? "badge-light-warning" : "badge-light-info");
            var items = (grupo.items || []).slice(0, 4).map(function (item) {
                var detalle = clave === "fiscal"
                    ? "Falta: " + escapeHtml((item.faltantes_fiscal || []).join(", "))
                    : "Costo " + dinero(item.costo_referencia) + " / Precio " + (item.precio_general == null ? "-" : dinero(item.precio_general));
                return "<div class=\"border rounded p-3 mb-2\"><div class=\"fw-bold\">" + escapeHtml(item.sku || "") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.producto || "") + "</div><div class=\"text-muted fs-8\">" + detalle + "</div><div class=\"fs-8 mt-1\">" + escapeHtml(item.accion_sugerida || "") + "</div></div>";
            }).join("");
            return "<div class=\"col-xl-3 col-md-6\"><div class=\"border rounded p-4 h-100\"><div class=\"d-flex justify-content-between mb-3\"><span class=\"fw-bold\">" + escapeHtml(grupo.titulo || clave) + "</span><span class=\"badge " + clase + "\">" + Number(grupo.total || 0) + "</span></div>" + (items || "<div class=\"text-muted fs-8\">Sin casos en la muestra</div>") + "</div></div>";
        }).join("") + "</div>";
    }
    function renderFiscalXml(data) {
        var items = data.items || [];
        var filas = items.slice(0, 20).map(function (item) {
            var xml = item.xml || {};
            var badge = item.tiene_sugerencia_xml ? "badge-light-success" : "badge-light-warning";
            var sugerencia = item.tiene_sugerencia_xml
                ? [xml.clave_producto_sat, xml.clave_unidad_sat, xml.objeto_impuesto, pct(xml.iva_porcentaje), pct(xml.ieps_porcentaje)].filter(Boolean).join(" / ")
                : "Sin XML vinculado util";
            return "<tr><td><div class=\"fw-bold\">" + escapeHtml(item.sku || "") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.producto || "") + "</div></td>" +
                "<td><span class=\"badge " + badge + "\">" + (item.tiene_sugerencia_xml ? "con XML" : "sin evidencia") + "</span></td>" +
                "<td><div class=\"fw-bold\">" + escapeHtml(sugerencia) + "</div><div class=\"text-muted fs-8\">" + escapeHtml(xml.descripcion || "") + "</div></td>" +
                "<td class=\"text-muted fs-8\">" + escapeHtml((item.faltantes || []).join(", ")) + "</td></tr>";
        }).join("");
        $("rentabilidad_fiscal_xml").innerHTML =
            "<div class=\"d-flex flex-wrap gap-2 mb-3\"><span class=\"badge badge-light-warning\">Fiscal incompleto " + Number(data.total_fiscal_incompleto || 0) + "</span><span class=\"badge badge-light-success\">Con XML " + Number(data.con_sugerencia_xml || 0) + "</span><span class=\"badge badge-light-secondary\">Sin XML " + Number(data.sin_sugerencia_xml || 0) + "</span></div>" +
            (filas ? "<div class=\"table-responsive\"><table class=\"table align-middle table-row-dashed gy-3 mb-0\"><thead><tr class=\"text-muted fw-bold fs-8 text-uppercase\"><th>SKU</th><th>Estado</th><th>Sugerencia XML</th><th>Faltantes</th></tr></thead><tbody>" + filas + "</tbody></table></div>" : "<div class=\"text-muted fs-8\">Sin pendientes fiscales en la muestra</div>");
    }
    function renderFiscalPreflight(data) {
        var resumen = data.resumen || {};
        var campos = resumen.campos_faltantes || {};
        var clases = {aplicable_xml: "badge-light-warning", captura_manual: "badge-light-info", sin_evidencia: "badge-light-danger"};
        var filas = (data.items || []).slice(0, 20).map(function (item) {
            var xml = item.xml || {};
            return "<tr>" +
                "<td><div class=\"fw-bold\">" + escapeHtml(item.sku || "") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.producto || "") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.siguiente_paso || "") + "</div></td>" +
                "<td><span class=\"badge " + (clases[item.accion] || "badge-light-secondary") + "\">" + escapeHtml(item.accion || "") + "</span><div class=\"text-muted fs-8 mt-1\">" + escapeHtml(item.estado || "") + "</div></td>" +
                "<td class=\"text-muted fs-8\">" + escapeHtml((item.faltantes || []).join(", ")) + "</td>" +
                "<td><div class=\"fw-bold\">" + escapeHtml(xml.uuid || "-") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(xml.clave_producto_sat || "") + " / " + escapeHtml(xml.clave_unidad_sat || "") + " / " + escapeHtml(xml.objeto_impuesto || "") + "</div><div class=\"text-muted fs-8\">IVA " + (xml.iva_porcentaje == null ? "-" : Number(xml.iva_porcentaje).toFixed(4)) + " / IEPS " + (xml.ieps_porcentaje == null ? "-" : Number(xml.ieps_porcentaje).toFixed(4)) + "</div></td>" +
                "</tr>";
        }).join("");
        $("rentabilidad_fiscal_preflight").innerHTML =
            "<div class=\"d-flex flex-wrap gap-2 mb-4\">" +
            "<span class=\"badge badge-light-primary\">Evaluados " + Number(resumen.evaluados || 0) + "</span>" +
            "<span class=\"badge badge-light-warning\">XML aplicable " + Number(resumen.aplicable_xml || 0) + "</span>" +
            "<span class=\"badge badge-light-info\">Manual " + Number(resumen.captura_manual || 0) + "</span>" +
            "<span class=\"badge badge-light-danger\">Sin evidencia " + Number(resumen.sin_evidencia || 0) + "</span>" +
            "<span class=\"badge badge-light-secondary\">IVA faltante " + Number(campos.iva_porcentaje || 0) + "</span>" +
            "<span class=\"badge badge-light-secondary\">Incluye faltante " + Number(campos.incluye_impuestos || 0) + "</span>" +
            "</div>" +
            (filas ? "<div class=\"table-responsive\"><table class=\"table align-middle table-row-dashed gy-3 mb-0\"><thead><tr class=\"text-muted fw-bold fs-8 text-uppercase\"><th>SKU</th><th>Accion</th><th>Faltantes</th><th>XML</th></tr></thead><tbody>" + filas + "</tbody></table></div>" : "<div class=\"text-muted fs-8\">Sin pendientes fiscales para el filtro seleccionado</div>");
    }
    function renderWorkflowComercial(data) {
        var resumen = data.resumen || {};
        var bandejas = data.bandejas || {};
        var orden = ["crear_pendientes", "resolver_pendientes", "aprobar_precios", "trabajo_prioritario"];
        var clases = {requiere_autorizacion: "badge-light-warning", requiere_politica: "badge-light-info", bloqueado: "badge-light-danger", activo: "badge-light-primary", sin_casos: "badge-light-secondary"};
        var columnas = orden.map(function (clave) {
            var grupo = bandejas[clave] || {titulo: clave, estado: "sin_casos", total: 0, items: []};
            var items = (grupo.items || []).slice(0, 3).map(function (item) {
                var texto = item.siguiente_paso || item.recomendacion || item.motivo || item.estatus || "";
                return "<div class=\"border rounded p-3 mb-2\"><div class=\"fw-bold\">" + escapeHtml(item.sku || "") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.producto || "") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(texto) + "</div></div>";
            }).join("");
            return "<div class=\"col-xl-3 col-md-6\"><div class=\"border rounded p-4 h-100\">" +
                "<div class=\"d-flex justify-content-between align-items-start gap-3 mb-2\"><div><div class=\"fw-bold\">" + escapeHtml(grupo.titulo || clave) + "</div><div class=\"text-muted fs-8\">" + escapeHtml(grupo.permiso_requerido || "") + "</div></div><span class=\"badge " + (clases[grupo.estado] || "badge-light-info") + "\">" + escapeHtml(grupo.estado || "") + "</span></div>" +
                "<div class=\"fw-bold mb-2\">Casos " + Number(grupo.total || 0) + "</div>" +
                "<div class=\"text-muted fs-8 mb-3\">" + escapeHtml(grupo.siguiente_paso || "") + "</div>" +
                (items || "<div class=\"text-muted fs-8\">Sin muestra</div>") +
                "</div></div>";
        }).join("");
        $("rentabilidad_workflow").innerHTML =
            "<div class=\"d-flex flex-wrap gap-2 mb-4\">" +
            "<span class=\"badge badge-light-warning\">Candidatos " + Number(resumen.candidatos_creables || 0) + "</span>" +
            "<span class=\"badge badge-light-primary\">Pendientes " + Number(resumen.pendientes || 0) + "</span>" +
            "<span class=\"badge badge-light-success\">Aprobables " + Number(resumen.aprobables || 0) + "</span>" +
            "<span class=\"badge badge-light-danger\">Bloq. aprobacion " + Number(resumen.bloqueados_aprobacion || 0) + "</span>" +
            "<span class=\"badge badge-light-info\">Prioridades " + Number(resumen.prioridades || 0) + "</span>" +
            "</div><div class=\"row g-4\">" + columnas + "</div>";
    }
    function renderEstadoModulo(data) {
        var resumen = data.resumen || {};
        var componentes = data.componentes || {};
        var orden = ["escenarios_comerciales", "workflow_comercial", "aprobacion_precios", "snapshots", "paquete_autorizacion"];
        var clases = {
            listo: "badge-light-success",
            requiere_autorizacion: "badge-light-warning",
            bloqueado: "badge-light-danger",
            advertencia: "badge-light-info"
        };
        var general = resumen.estado_general || "listo";
        var tarjetas = orden.map(function (clave) {
            var item = componentes[clave] || {titulo: clave, estado: "advertencia", detalle: "", siguiente_paso: "", conteo: 0};
            return "<div class=\"col-xl-4 col-md-6\"><div class=\"border rounded p-4 h-100\">" +
                "<div class=\"d-flex justify-content-between align-items-start gap-3 mb-2\"><div><div class=\"fw-bold\">" + escapeHtml(item.titulo || clave) + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.detalle || "") + "</div></div><span class=\"badge " + (clases[item.estado] || "badge-light-secondary") + "\">" + escapeHtml(item.estado || "") + "</span></div>" +
                "<div class=\"fw-bold mb-1\">Casos " + Number(item.conteo || 0) + "</div>" +
                "<div class=\"text-muted fs-8\">" + escapeHtml(item.siguiente_paso || "") + "</div>" +
                "</div></div>";
        }).join("");
        $("rentabilidad_estado_modulo").innerHTML =
            "<div class=\"d-flex flex-wrap gap-2 mb-4\">" +
            "<span class=\"badge " + (clases[general] || "badge-light-secondary") + "\">Estado " + escapeHtml(general) + "</span>" +
            "<span class=\"badge badge-light-success\">Listos " + Number(resumen.listos || 0) + "</span>" +
            "<span class=\"badge badge-light-warning\">Autorizacion " + Number(resumen.requieren_autorizacion || 0) + "</span>" +
            "<span class=\"badge badge-light-danger\">Bloqueados " + Number(resumen.bloqueados || 0) + "</span>" +
            "<span class=\"badge badge-light-info\">Advertencias " + Number(resumen.advertencias || 0) + "</span>" +
            "</div><div class=\"row g-4\">" + tarjetas + "</div>";
    }
    function renderUsoComercial(data) {
        var resumen = data.resumen || {};
        var destinos = data.destinos || {};
        var orden = ["catalogo_precios", "menudeo", "mayoreo_pedidos", "alianzas", "catalogo_fiscal", "pendientes_comerciales"];
        var clases = {listo: "badge-light-success", requiere_autorizacion: "badge-light-warning", bloqueado: "badge-light-danger", sin_casos: "badge-light-secondary"};
        var general = resumen.estado_general || "listo";
        var tarjetas = orden.map(function (clave) {
            var item = destinos[clave] || {titulo: clave, estado: "bloqueado", listos: 0, bloqueados: 0, requieren_autorizacion: 0, muestra: [], siguiente_paso: ""};
            var muestra = (item.muestra || []).slice(0, 3).map(function (sku) {
                return "<div class=\"text-muted fs-8\">" + escapeHtml(sku.sku || "") + ": " + escapeHtml(sku.motivo || "") + "</div>";
            }).join("");
            return "<div class=\"col-xl-4 col-md-6\"><div class=\"border rounded p-4 h-100\">" +
                "<div class=\"d-flex justify-content-between align-items-start gap-3 mb-2\"><div><div class=\"fw-bold\">" + escapeHtml(item.titulo || clave) + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.siguiente_paso || "") + "</div></div><span class=\"badge " + (clases[item.estado] || "badge-light-secondary") + "\">" + escapeHtml(item.estado || "") + "</span></div>" +
                "<div class=\"d-flex flex-wrap gap-2 mb-2\"><span class=\"badge badge-light-success\">Listos " + Number(item.listos || 0) + "</span><span class=\"badge badge-light-danger\">Bloq. " + Number(item.bloqueados || 0) + "</span><span class=\"badge badge-light-warning\">Aut. " + Number(item.requieren_autorizacion || 0) + "</span></div>" +
                (muestra || "<div class=\"text-muted fs-8\">Sin muestra de bloqueo</div>") +
                "</div></div>";
        }).join("");
        $("rentabilidad_uso_comercial").innerHTML =
            "<div class=\"d-flex flex-wrap gap-2 mb-4\">" +
            "<span class=\"badge " + (clases[general] || "badge-light-secondary") + "\">Estado " + escapeHtml(general) + "</span>" +
            "<span class=\"badge badge-light-success\">Destinos listos " + Number(resumen.listos || 0) + "</span>" +
            "<span class=\"badge badge-light-warning\">Con autorizacion " + Number(resumen.requieren_autorizacion || 0) + "</span>" +
            "<span class=\"badge badge-light-danger\">Bloqueados " + Number(resumen.bloqueados || 0) + "</span>" +
            "<span class=\"badge badge-light-secondary\">Sin casos " + Number(resumen.sin_casos || 0) + "</span>" +
            "</div><div class=\"row g-4\">" + tarjetas + "</div>";
    }
    function renderDesbloqueo(data) {
        var resumen = data.resumen || {};
        var clases = {alta: "badge-light-danger", media: "badge-light-warning", baja: "badge-light-info"};
        var filas = (data.acciones || []).slice(0, 15).map(function (item) {
            var muestra = (item.muestra || []).slice(0, 2).map(function (sku) {
                return "<div class=\"text-muted fs-8\">" + escapeHtml(sku.sku || "") + " " + escapeHtml(sku.siguiente_paso || sku.motivo || "") + "</div>";
            }).join("");
            return "<tr>" +
                "<td><span class=\"badge " + (clases[item.prioridad] || "badge-light-secondary") + "\">" + escapeHtml(item.prioridad || "") + "</span><div class=\"text-muted fs-8 mt-1\">" + escapeHtml(item.id || "") + "</div></td>" +
                "<td><div class=\"fw-bold\">" + escapeHtml(item.titulo || "") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.bloqueo_resuelve || "") + "</div></td>" +
                "<td><div class=\"fw-bold\">" + escapeHtml(item.responsable || "") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.tipo || "") + "</div></td>" +
                "<td class=\"text-end fw-bold\">" + Number(item.casos || 0) + "</td>" +
                "<td><div class=\"text-muted fs-8\">" + escapeHtml(item.siguiente_paso || "") + "</div>" + (muestra || "") + "</td>" +
                "</tr>";
        }).join("");
        $("rentabilidad_desbloqueo").innerHTML =
            "<div class=\"d-flex flex-wrap gap-2 mb-4\">" +
            "<span class=\"badge badge-light-primary\">Acciones " + Number(resumen.acciones || 0) + "</span>" +
            "<span class=\"badge badge-light-danger\">Alta " + Number(resumen.alta || 0) + "</span>" +
            "<span class=\"badge badge-light-warning\">Media " + Number(resumen.media || 0) + "</span>" +
            "<span class=\"badge badge-light-info\">Baja " + Number(resumen.baja || 0) + "</span>" +
            "<span class=\"badge badge-light-secondary\">Responsables " + Number(resumen.responsables || 0) + "</span>" +
            "</div>" +
            (filas ? "<div class=\"table-responsive\"><table class=\"table align-middle table-row-dashed gy-3 mb-0\"><thead><tr class=\"text-muted fw-bold fs-8 text-uppercase\"><th>Prioridad</th><th>Accion</th><th>Responsable</th><th class=\"text-end\">Casos</th><th>Siguiente paso</th></tr></thead><tbody>" + filas + "</tbody></table></div>" : "<div class=\"text-muted fs-8\">Sin acciones de desbloqueo para el filtro seleccionado</div>");
    }
    function renderAuditoriaFinal(data) {
        var resumen = data.resumen || {};
        var clases = {ok: "badge-light-success", bloqueado: "badge-light-danger", bloqueado_operativo: "badge-light-warning"};
        var filas = (data.criterios || []).map(function (item) {
            return "<tr>" +
                "<td><div class=\"fw-bold\">" + escapeHtml(item.id || "") + "</div></td>" +
                "<td><div class=\"fw-bold\">" + escapeHtml(item.titulo || "") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.detalle || "") + "</div></td>" +
                "<td><span class=\"badge " + (clases[item.estado] || "badge-light-secondary") + "\">" + escapeHtml(item.estado || "") + "</span></td>" +
                "</tr>";
        }).join("");
        $("rentabilidad_auditoria_final").innerHTML =
            "<div class=\"d-flex flex-wrap gap-2 mb-4\">" +
            "<span class=\"badge badge-light-primary\">Construccion " + escapeHtml(resumen.estado_construccion || "-") + "</span>" +
            "<span class=\"badge badge-light-warning\">Uso comercial " + escapeHtml(resumen.estado_uso_comercial || "-") + "</span>" +
            "<span class=\"badge badge-light-success\">OK " + Number(resumen.ok || 0) + "</span>" +
            "<span class=\"badge badge-light-danger\">Tecnicos " + Number(resumen.bloqueados_tecnicos || 0) + "</span>" +
            "<span class=\"badge badge-light-warning\">Operativos " + Number(resumen.bloqueados_operativos || 0) + "</span>" +
            "</div>" +
            "<div class=\"alert alert-info py-3 fs-8\">" + escapeHtml(data.siguiente_paso || "") + "</div>" +
            (filas ? "<div class=\"table-responsive\"><table class=\"table align-middle table-row-dashed gy-3 mb-0\"><thead><tr class=\"text-muted fw-bold fs-8 text-uppercase\"><th>ID</th><th>Criterio</th><th>Estado</th></tr></thead><tbody>" + filas + "</tbody></table></div>" : "<div class=\"text-muted fs-8\">Sin criterios de auditoria</div>");
    }
    function renderSnapshots(data) {
        var items = data.items || [];
        if (!items.length) {
            $("rentabilidad_snapshots").innerHTML = "<div class=\"text-muted fs-8\">Sin snapshots guardados</div>";
            return;
        }
        $("rentabilidad_snapshots").innerHTML = "<div class=\"table-responsive\"><table class=\"table align-middle table-row-dashed gy-3 mb-0\"><thead><tr class=\"text-muted fw-bold fs-8 text-uppercase\"><th>Folio</th><th>Canal</th><th>SKUs</th><th>Perdida</th><th>Sin costo</th><th>Valor inventario</th><th>Fecha</th></tr></thead><tbody>" +
            items.map(function (item) {
                var resumen = item.resumen || {};
                return "<tr><td class=\"fw-bold\">" + escapeHtml(item.folio || "") + "</td><td class=\"text-capitalize\">" + escapeHtml(item.canal || "") + "</td><td>" + Number(resumen.skus || 0) + "</td><td>" + Number(resumen.perdida || 0) + "</td><td>" + Number(resumen.sin_costo || 0) + "</td><td>" + dinero(resumen.valor_inventario || 0) + "</td><td class=\"text-muted fs-8\">" + escapeHtml(item.fecha_registro || "") + "</td></tr>";
            }).join("") + "</tbody></table></div>";
    }
    function renderVigenciaSnapshots(data) {
        var items = data.items || [];
        if (!items.length) {
            $("rentabilidad_snapshots_vigencia").innerHTML = "<div class=\"text-muted fs-8\">Sin snapshots para auditar</div>";
            return;
        }
        $("rentabilidad_snapshots_vigencia").innerHTML = "<div class=\"table-responsive\"><table class=\"table align-middle table-row-dashed gy-3 mb-0\"><thead><tr class=\"text-muted fw-bold fs-8 text-uppercase\"><th>Folio</th><th>Estado</th><th>Detalles</th><th>Diferencias</th><th>Primer hallazgo</th></tr></thead><tbody>" +
            items.map(function (item) {
                var diff = (item.diferencias || [])[0] || {};
                var campos = (diff.diferencias || []).slice(0, 3).map(function (campo) {
                    return escapeHtml(campo.campo || "") + ": " + escapeHtml(campo.snapshot == null ? "-" : campo.snapshot) + " -> " + escapeHtml(campo.actual == null ? "-" : campo.actual);
                }).join("<br>");
                var badge = item.vigencia === "vigente" ? "badge-light-success" : "badge-light-warning";
                return "<tr><td><div class=\"fw-bold\">" + escapeHtml(item.folio || "") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.fecha_registro || "") + "</div></td><td><span class=\"badge " + badge + "\">" + escapeHtml(item.vigencia || "") + "</span></td><td>" + Number(item.detalles || 0) + "</td><td>" + Number(item.diferencias_total || 0) + "</td><td><div class=\"fw-bold\">" + escapeHtml(diff.sku || "-") + "</div><div class=\"text-muted fs-8\">" + (campos || "Sin diferencias") + "</div></td></tr>";
            }).join("") + "</tbody></table></div>";
    }
    function renderPresentaciones(data) {
        var items = data.items || [];
        if (!items.length) {
            $("rentabilidad_presentaciones").innerHTML = "<div class=\"text-muted fs-8\">Sin transformaciones para auditar</div>";
            return;
        }
        var alertas = Number(data.alertas || 0);
        $("rentabilidad_presentaciones").innerHTML = "<div class=\"d-flex gap-2 mb-3\"><span class=\"badge badge-light-primary\">Reglas " + Number(data.total || 0) + "</span><span class=\"badge " + (alertas ? "badge-light-warning" : "badge-light-success") + "\">Alertas " + alertas + "</span></div>" +
            "<div class=\"table-responsive\"><table class=\"table align-middle table-row-dashed gy-3 mb-0\"><thead><tr class=\"text-muted fw-bold fs-8 text-uppercase\"><th>Transformacion</th><th>Esperado</th><th>Actual</th><th>Diferencia</th><th>Estado</th></tr></thead><tbody>" +
            items.slice(0, 25).map(function (item) {
                var badge = item.estatus_consistencia === "ok" ? "badge-light-success" : (item.estatus_consistencia === "diferencia" ? "badge-light-warning" : "badge-light-info");
                return "<tr><td><div class=\"fw-bold\">" + escapeHtml(item.sku_origen || "") + " -> " + escapeHtml(item.sku_resultado || "") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.tipo_transformacion || "") + " / " + Number(item.cantidad_origen || 0).toFixed(4) + " a " + Number(item.unidades_resultado || 0).toFixed(2) + " pza</div></td>" +
                    "<td><div class=\"fw-bold\">" + dinero(item.costo_resultado_esperado) + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.origen_costo_base || "") + "</div></td>" +
                    "<td><div class=\"fw-bold\">" + dinero(item.costo_resultado_actual) + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.origen_costo_resultado || "") + "</div></td>" +
                    "<td><div class=\"fw-bold\">" + dinero(item.diferencia) + "</div><div class=\"text-muted fs-8\">" + pct(item.diferencia_pct) + "</div></td>" +
                    "<td><span class=\"badge " + badge + "\">" + escapeHtml(item.estatus_consistencia || "") + "</span></td></tr>";
            }).join("") + "</tbody></table></div>";
    }
    function renderRecomendacionesPersistentes(data) {
        var items = data.items || [];
        if (!items.length) {
            $("rentabilidad_recomendaciones_persistentes").innerHTML = "<div class=\"text-muted fs-8\">Sin recomendaciones pendientes</div>";
            return;
        }
        $("rentabilidad_recomendaciones_persistentes").innerHTML = "<div class=\"table-responsive\"><table class=\"table align-middle table-row-dashed gy-3 mb-0\"><thead><tr class=\"text-muted fw-bold fs-8 text-uppercase\"><th>SKU</th><th>Canal</th><th>Actual</th><th>Recomendado</th><th>Motivo</th><th class=\"text-end\">Acciones</th></tr></thead><tbody>" +
            items.map(function (item) {
                return "<tr><td><div class=\"fw-bold\">" + escapeHtml(item.sku || "") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.producto || "") + "</div></td><td class=\"text-capitalize\">" + escapeHtml(item.canal || "") + "</td><td>" + dinero(item.precio_actual_sin_impuesto) + "</td><td class=\"fw-bold\">" + dinero(item.precio_recomendado_sin_impuesto) + "</td><td><div class=\"text-muted fs-8\">" + escapeHtml(item.motivo || "") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.comentario || "") + "</div></td><td class=\"text-end\"><button class=\"btn btn-sm btn-light-success me-2\" data-rec-accion=\"aprobar\" data-rec-id=\"" + Number(item.id_recomendacion || 0) + "\"><i class=\"bi bi-check2\"></i></button><button class=\"btn btn-sm btn-light-danger\" data-rec-accion=\"rechazar\" data-rec-id=\"" + Number(item.id_recomendacion || 0) + "\"><i class=\"bi bi-x\"></i></button></td></tr>";
            }).join("") + "</tbody></table></div>";
    }
    function renderPreflightRecomendaciones(data) {
        var resumen = data.resumen || {};
        var items = data.items || [];
        var filas = items.slice(0, 10).map(function (item) {
            var clase = item.accion_preflight === "crear" ? "badge-light-success" : "badge-light-warning";
            return "<tr>" +
                "<td><div class=\"fw-bold\">" + escapeHtml(item.sku || "") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.producto || "") + "</div></td>" +
                "<td><span class=\"badge " + clase + "\">" + escapeHtml(item.accion_preflight || "") + "</span></td>" +
                "<td class=\"text-end\">" + dinero(item.precio_actual_sin_impuesto) + "</td>" +
                "<td class=\"text-end fw-bold\">" + dinero(item.precio_recomendado_sin_impuesto) + "</td>" +
                "<td class=\"text-end\">" + dinero(item.delta) + "</td>" +
                "<td><div class=\"text-muted fs-8\">" + escapeHtml(item.motivo || "") + "</div></td>" +
                "</tr>";
        }).join("");
        $("rentabilidad_recomendaciones_preflight").innerHTML =
            "<div class=\"d-flex flex-wrap gap-2 mb-3\">" +
            "<span class=\"badge badge-light-primary\">Evaluados " + Number(resumen.evaluados || 0) + "</span>" +
            "<span class=\"badge badge-light-warning\">Candidatos " + Number(resumen.candidatos || 0) + "</span>" +
            "<span class=\"badge badge-light-success\">Creables " + Number(resumen.creables || 0) + "</span>" +
            "<span class=\"badge badge-light-secondary\">Omitidas " + Number(resumen.omitidas_pendientes || 0) + "</span>" +
            "<span class=\"badge badge-light-info\">Delta " + dinero(resumen.delta_total || 0) + "</span>" +
            "</div>" +
            (filas ? "<div class=\"table-responsive\"><table class=\"table align-middle table-row-dashed gy-3 mb-0\"><thead><tr class=\"text-muted fw-bold fs-8 text-uppercase\"><th>SKU</th><th>Accion</th><th class=\"text-end\">Actual</th><th class=\"text-end\">Recomendado</th><th class=\"text-end\">Delta</th><th>Motivo</th></tr></thead><tbody>" + filas + "</tbody></table></div>" : "<div class=\"text-muted fs-8\">Sin candidatos para crear recomendaciones con el filtro actual</div>");
    }
    function badgeRiesgo(item) {
        var clases = {danger: "badge-light-danger", warning: "badge-light-warning", success: "badge-light-success", info: "badge-light-info"};
        return "<span class=\"badge " + (clases[item.riesgo_tipo] || "badge-light-secondary") + "\">" + escapeHtml(item.riesgo_texto || "Revision") + "</span>";
    }
    function renderItem(item) {
        var compras = item.compras || {};
        var xml = item.xml || {};
        var inv = item.inventario || {};
        var costoFactor = "";
        if (Number(inv.factor_unidad_base || 1) > 1 && Number(inv.costo_promedio_unitario_inventario || 0) > 0) {
            costoFactor = "<div class=\"text-muted fs-8\">Unit. inv. " + dinero(inv.costo_promedio_unitario_inventario) + " x " + Number(inv.factor_unidad_base || 1).toFixed(2) + "</div>";
        }
        var hallazgos = (item.hallazgos_detalle || []).map(function (hallazgo) {
            return escapeHtml(hallazgo.id || "") + " " + escapeHtml(hallazgo.clave || "");
        }).join("<br>");
        return "<tr>" +
            "<td><div class=\"fw-bold\">" + escapeHtml(item.sku || "") + "</div><div class=\"text-muted fs-7\">" + escapeHtml(item.producto || "") + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.recomendacion || "") + "</div><div class=\"d-flex flex-wrap gap-2 mt-2\"><button class=\"btn btn-sm btn-light-info\" type=\"button\" data-comparar-sku=\"" + escapeHtml(item.sku || "") + "\"><i class=\"bi bi-layout-three-columns\"></i> Comparar</button><button class=\"btn btn-sm btn-light-primary\" type=\"button\" data-detalle-sku=\"" + escapeHtml(item.sku || "") + "\"><i class=\"bi bi-clipboard-data\"></i> Evidencia</button></div></td>" +
            "<td><div class=\"fw-bold\">" + dinero(item.costo_real_sin_impuesto) + "</div><div class=\"text-muted fs-8\">" + escapeHtml(item.origen_costo || "") + "</div>" + costoFactor + "</td>" +
            "<td><div class=\"fw-bold\">" + dinero(item.precio_escenario_sin_impuesto) + "</div><div class=\"text-muted fs-8\">Base " + dinero(item.precio_venta_sin_impuesto) + "</div></td>" +
            "<td><div class=\"fw-bold\">" + pct(item.margen_bruto_pct) + "</div><div class=\"text-muted fs-8\">Bruta " + dinero(item.utilidad_bruta) + "</div></td>" +
            "<td><div class=\"fw-bold\">" + dinero(item.utilidad_estimada) + "</div><div class=\"text-muted fs-8\">" + pct(item.utilidad_estimada_pct) + " despues de gastos</div></td>" +
            "<td class=\"fw-bold\">" + (item.precio_minimo_rentable == null ? "-" : dinero(item.precio_minimo_rentable)) + "</td>" +
            "<td><div class=\"fw-bold\">" + Number(inv.disponible_total || 0).toFixed(2) + "</div><div class=\"text-muted fs-8\">Valor " + dinero(inv.valor_total || 0) + "</div></td>" +
            "<td><div class=\"text-muted fs-8\">OC " + (compras.ultimo_costo == null ? "-" : dinero(compras.ultimo_costo)) + "</div><div class=\"text-muted fs-8\">XML " + (xml.ultimo_costo == null ? "-" : dinero(xml.ultimo_costo)) + "</div></td>" +
            "<td>" + badgeRiesgo(item) + "<div class=\"text-muted fs-8 mt-1\">" + hallazgos + "</div></td>" +
            "</tr>";
    }
    function compararSku(sku) {
        var titulo = $("rentabilidad_comparar_titulo");
        var body = $("rentabilidad_comparar_body");
        titulo.textContent = "Comparar escenarios: " + sku;
        body.innerHTML = "<div class=\"text-center text-muted py-10\"><span class=\"spinner-border spinner-border-sm me-2\"></span>Consultando escenarios</div>";
        bootstrap.Modal.getOrCreateInstance($("rentabilidad_comparar_modal")).show();
        request("/rentabilidad/comparar_erp?" + new URLSearchParams({q: sku}).toString()).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            body.innerHTML = renderComparacion(response.depurar || {});
        }).catch(function (error) {
            body.innerHTML = "<div class=\"alert alert-danger mb-0\">" + escapeHtml(error.message) + "</div>";
        });
    }
    function detalleSku(sku) {
        var titulo = $("rentabilidad_comparar_titulo");
        var body = $("rentabilidad_comparar_body");
        titulo.textContent = "Evidencia SKU: " + sku;
        body.innerHTML = "<div class=\"text-center text-muted py-10\"><span class=\"spinner-border spinner-border-sm me-2\"></span>Consultando evidencia</div>";
        bootstrap.Modal.getOrCreateInstance($("rentabilidad_comparar_modal")).show();
        var params = new URLSearchParams(filtros());
        params.set("q", sku);
        request("/rentabilidad/detalle_sku_erp?" + params.toString()).then(function (response) {
            if (response.error) { throw new Error(response.mensaje); }
            body.innerHTML = renderDetalleSku(response.depurar || {});
        }).catch(function (error) {
            body.innerHTML = "<div class=\"alert alert-danger mb-0\">" + escapeHtml(error.message) + "</div>";
        });
    }
    function renderComparacion(data) {
        var escenarios = data.escenarios || [];
        if (!escenarios.length) { return "<div class=\"text-center text-muted py-10\">Sin escenarios</div>"; }
        return "<div class=\"table-responsive\"><table class=\"table align-middle table-row-dashed gy-4\"><thead><tr class=\"text-muted fw-bold fs-7 text-uppercase\"><th>Canal</th><th>Precio</th><th>Costo</th><th>Margen</th><th>Utilidad</th><th>Minimo</th><th>Riesgo</th><th>Recomendacion</th></tr></thead><tbody>" +
            escenarios.map(function (item) {
                return "<tr><td class=\"fw-bold text-capitalize\">" + escapeHtml(item.canal || "") + "</td>" +
                    "<td>" + dinero(item.precio_escenario_sin_impuesto) + "</td>" +
                    "<td>" + dinero(item.costo_real_sin_impuesto) + "<div class=\"text-muted fs-8\">" + escapeHtml(item.origen_costo || "") + "</div></td>" +
                    "<td>" + pct(item.margen_bruto_pct) + "</td>" +
                    "<td class=\"fw-bold\">" + dinero(item.utilidad_estimada) + "<div class=\"text-muted fs-8\">" + pct(item.utilidad_estimada_pct) + "</div></td>" +
                    "<td>" + (item.precio_minimo_rentable == null ? "-" : dinero(item.precio_minimo_rentable)) + "</td>" +
                    "<td>" + badgeRiesgo(item) + "</td>" +
                    "<td class=\"text-muted fs-8\">" + escapeHtml(item.recomendacion || "") + "</td></tr>";
            }).join("") + "</tbody></table></div>";
    }
    function renderDetalleSku(data) {
        var item = data.escenario_activo || {};
        var datos = data.datos_base || {};
        var presentaciones = data.presentaciones || {};
        var fiscal = data.fiscal_xml || {};
        var snapshots = data.snapshots || {};
        var dictamen = data.dictamen_cierre || {};
        var clasesDictamen = {listo: "badge-light-success", precaucion: "badge-light-info", requiere_autorizacion: "badge-light-warning", bloqueado: "badge-light-danger"};
        var escenarios = (data.escenarios || []).map(function (esc) {
            return "<tr><td class=\"text-capitalize fw-bold\">" + escapeHtml(esc.canal || "") + "</td><td>" + dinero(esc.precio_escenario_sin_impuesto) + "</td><td>" + dinero(esc.utilidad_estimada) + "</td><td>" + pct(esc.margen_bruto_pct) + "</td><td>" + badgeRiesgo(esc) + "</td></tr>";
        }).join("");
        var hallazgos = (item.hallazgos_detalle || []).map(function (hallazgo) {
            return "<span class=\"badge badge-light-warning me-2 mb-2\">" + escapeHtml(hallazgo.id || "") + " " + escapeHtml(hallazgo.clave || "") + "</span>";
        }).join("");
        var bloqueos = (dictamen.bloqueos || []).map(function (bloqueo) {
            return "<div class=\"text-muted fs-8\">" + escapeHtml(bloqueo.id || "") + " " + escapeHtml(bloqueo.clave || "") + ": " + escapeHtml(bloqueo.mensaje || "") + "</div>";
        }).join("");
        var alertas = (dictamen.alertas || []).map(function (alerta) {
            return "<div class=\"text-muted fs-8\">" + escapeHtml(alerta.id || "") + " " + escapeHtml(alerta.clave || "") + ": " + escapeHtml(alerta.mensaje || "") + "</div>";
        }).join("");
        var aprobacion = dictamen.aprobacion || {};
        var recomendacion = dictamen.recomendacion_preflight || {};
        var sugerencias = (datos.sugerencias || []).map(function (texto) {
            return "<div class=\"text-muted fs-8\">" + escapeHtml(texto) + "</div>";
        }).join("");
        var faltantesFiscal = (datos.faltantes_fiscal || []).join(", ");
        return "<div class=\"d-flex flex-wrap gap-2 mb-4\">" +
            "<span class=\"badge " + (clasesDictamen[dictamen.estado] || "badge-light-secondary") + "\">Cierre " + escapeHtml(dictamen.estado || "-") + "</span>" +
            "<span class=\"badge badge-light-primary\">Costo " + dinero(item.costo_real_sin_impuesto) + "</span>" +
            "<span class=\"badge badge-light-info\">" + escapeHtml(item.origen_costo || "") + "</span>" +
            "<span class=\"badge badge-light-success\">Precio " + dinero(item.precio_escenario_sin_impuesto) + "</span>" +
            "<span class=\"badge badge-light-warning\">Minimo " + (item.precio_minimo_rentable == null ? "-" : dinero(item.precio_minimo_rentable)) + "</span>" +
            "<span class=\"badge badge-light-secondary\">Stock " + Number((item.inventario || {}).disponible_total || 0).toFixed(2) + "</span>" +
            "</div>" +
            "<div class=\"border rounded p-4 mb-4\">" +
            "<div class=\"d-flex justify-content-between align-items-start gap-3 mb-2\"><div><div class=\"fw-bold\">Dictamen de cierre</div><div class=\"text-muted fs-8\">" + escapeHtml(dictamen.siguiente_paso || "") + "</div></div><span class=\"badge " + (clasesDictamen[dictamen.estado] || "badge-light-secondary") + "\">" + escapeHtml(dictamen.estado || "-") + "</span></div>" +
            "<div class=\"row g-4\"><div class=\"col-md-6\"><div class=\"fw-bold fs-8 mb-1\">Bloqueos</div>" + (bloqueos || "<div class=\"text-muted fs-8\">Sin bloqueos directos</div>") + "</div>" +
            "<div class=\"col-md-6\"><div class=\"fw-bold fs-8 mb-1\">Alertas</div>" + (alertas || "<div class=\"text-muted fs-8\">Sin alertas directas</div>") + "</div></div>" +
            "<div class=\"d-flex flex-wrap gap-2 mt-3\">" +
            "<span class=\"badge badge-light-danger\">Aprobacion " + escapeHtml(aprobacion.estado || "-") + "</span>" +
            "<span class=\"badge badge-light-warning\">Delta " + dinero(aprobacion.delta || 0) + "</span>" +
            "<span class=\"badge badge-light-info\">Recomendacion " + escapeHtml(recomendacion.accion || "sin candidato") + "</span>" +
            "<span class=\"badge badge-light-secondary\">Modulo " + escapeHtml((dictamen.estado_modulo || {}).estado_general || "-") + "</span>" +
            "</div></div>" +
            "<div class=\"row g-4 mb-4\">" +
            "<div class=\"col-lg-6\"><div class=\"border rounded p-4 h-100\"><div class=\"fw-bold mb-3\">Calculo activo</div><div class=\"text-muted fs-8\">Margen " + pct(item.margen_bruto_pct) + " / Utilidad " + dinero(item.utilidad_estimada) + "</div><div class=\"text-muted fs-8\">Proveedor " + escapeHtml(((item.proveedor || {}).proveedor) || "-") + "</div><div class=\"mt-3\">" + (hallazgos || "<span class=\"badge badge-light-success\">Sin hallazgos economicos</span>") + "</div><div class=\"fs-8 mt-2\">" + escapeHtml(item.recomendacion || "") + "</div></div></div>" +
            "<div class=\"col-lg-6\"><div class=\"border rounded p-4 h-100\"><div class=\"fw-bold mb-3\">Datos base</div><div class=\"text-muted fs-8\">Costo ref. " + dinero(datos.costo_referencia) + " / Precio general " + (datos.precio_general == null ? "-" : dinero(datos.precio_general)) + "</div><div class=\"text-muted fs-8\">OC " + (datos.ultimo_costo_compra == null ? "-" : dinero(datos.ultimo_costo_compra)) + " / XML " + (datos.ultimo_costo_xml == null ? "-" : dinero(datos.ultimo_costo_xml)) + "</div><div class=\"text-muted fs-8\">Fiscal faltante: " + escapeHtml(faltantesFiscal || "sin faltantes") + "</div><div class=\"mt-2\">" + (sugerencias || "<div class=\"text-muted fs-8\">Sin sugerencias de datos base</div>") + "</div></div></div>" +
            "</div>" +
            "<div class=\"table-responsive mb-4\"><table class=\"table align-middle table-row-dashed gy-3 mb-0\"><thead><tr class=\"text-muted fw-bold fs-8 text-uppercase\"><th>Canal</th><th>Precio</th><th>Utilidad</th><th>Margen</th><th>Riesgo</th></tr></thead><tbody>" + (escenarios || "<tr><td colspan=\"5\" class=\"text-muted text-center py-6\">Sin escenarios</td></tr>") + "</tbody></table></div>" +
            "<div class=\"d-flex flex-wrap gap-2\">" +
            "<span class=\"badge badge-light-info\">Presentaciones " + Number(presentaciones.total || 0) + "</span>" +
            "<span class=\"badge " + (Number(presentaciones.alertas || 0) ? "badge-light-warning" : "badge-light-success") + "\">Alertas presentaciones " + Number(presentaciones.alertas || 0) + "</span>" +
            "<span class=\"badge badge-light-warning\">Fiscal XML pendiente " + Number(fiscal.total_fiscal_incompleto || 0) + "</span>" +
            "<span class=\"badge badge-light-secondary\">Snapshots desfasados " + Number(snapshots.desfasados || 0) + "</span>" +
            "</div>";
    }
    document.addEventListener("DOMContentLoaded", function () {
        $("rentabilidad_canal").addEventListener("change", function () { aplicarDefaults(); cargar(); });
        $("rentabilidad_recargar").addEventListener("click", cargar);
        if ($("rentabilidad_snapshot_guardar")) {
            $("rentabilidad_snapshot_guardar").addEventListener("click", guardarSnapshot);
        }
        if ($("rentabilidad_recomendaciones_guardar")) {
            $("rentabilidad_recomendaciones_guardar").addEventListener("click", guardarRecomendacionesPersistentes);
        }
        $("rentabilidad_recomendaciones_preflight_recargar").addEventListener("click", cargarPreflightRecomendaciones);
        $("rentabilidad_recomendaciones_persistentes_recargar").addEventListener("click", cargarRecomendacionesPersistentes);
        $("rentabilidad_workflow_recargar").addEventListener("click", cargarWorkflowComercial);
        $("rentabilidad_estado_modulo_recargar").addEventListener("click", cargarEstadoModulo);
        $("rentabilidad_uso_comercial_recargar").addEventListener("click", cargarUsoComercial);
        $("rentabilidad_desbloqueo_recargar").addEventListener("click", cargarDesbloqueo);
        $("rentabilidad_auditoria_final_recargar").addEventListener("click", cargarAuditoriaFinal);
        $("rentabilidad_tablero_recargar").addEventListener("click", cargarTablero);
        $("rentabilidad_escenarios_auditar").addEventListener("click", cargarEscenariosAuditoria);
        $("rentabilidad_revision_recargar").addEventListener("click", cargarRevision);
        $("rentabilidad_recomendaciones_persistentes").addEventListener("click", function (event) {
            var boton = event.target.closest("[data-rec-id]");
            if (boton) { resolverRecomendacion(boton.getAttribute("data-rec-id"), boton.getAttribute("data-rec-accion")); }
        });
        $("rentabilidad_snapshots_recargar").addEventListener("click", cargarSnapshots);
        $("rentabilidad_snapshots_vigencia_recargar").addEventListener("click", cargarVigenciaSnapshots);
        $("rentabilidad_presentaciones_recargar").addEventListener("click", cargarPresentaciones);
        $("rentabilidad_matriz_recargar").addEventListener("click", cargarMatriz);
        $("rentabilidad_canales_recargar").addEventListener("click", cargarCanales);
        $("rentabilidad_plan_recargar").addEventListener("click", cargarPlanCierre);
        $("rentabilidad_impacto_recargar").addEventListener("click", cargarImpactoCierre);
        $("rentabilidad_hallazgos_recargar").addEventListener("click", cargarHallazgosCierre);
        $("rentabilidad_prioridades_recargar").addEventListener("click", cargarPrioridadesCierre);
        $("rentabilidad_responsables_recargar").addEventListener("click", cargarResponsablesCierre);
        $("rentabilidad_checklist_recargar").addEventListener("click", cargarChecklistCierre);
        $("rentabilidad_autorizaciones_recargar").addEventListener("click", cargarAutorizacionesCierre);
        $("rentabilidad_precios_objetivo_recargar").addEventListener("click", cargarPreciosObjetivo);
        $("rentabilidad_precios_aprobacion_recargar").addEventListener("click", cargarPreciosAprobacion);
        $("rentabilidad_aprobaciones_internas_recargar").addEventListener("click", cargarAprobacionesInternas);
        $("rentabilidad_aprobaciones_autorizacion_recargar").addEventListener("click", cargarAprobacionesAutorizacion);
        $("rentabilidad_aprobaciones_internas_persistentes_recargar").addEventListener("click", cargarAprobacionesInternasPersistentes);
        $("rentabilidad_sensibilidad_recargar").addEventListener("click", cargarSensibilidad);
        $("rentabilidad_cierre_recargar").addEventListener("click", cargarCierre);
        $("rentabilidad_semaforo_recargar").addEventListener("click", cargarSemaforo);
        $("rentabilidad_variaciones_recargar").addEventListener("click", cargarVariaciones);
        $("rentabilidad_datos_base_recargar").addEventListener("click", cargarDatosBase);
        $("rentabilidad_fiscal_xml_recargar").addEventListener("click", cargarFiscalXml);
        $("rentabilidad_fiscal_preflight_recargar").addEventListener("click", cargarFiscalPreflight);
        $("rentabilidad_recomendaciones_recargar").addEventListener("click", cargar);
        $("rentabilidad_riesgo").addEventListener("change", cargar);
        ["rentabilidad_buscar", "rentabilidad_descuento", "rentabilidad_gasto", "rentabilidad_comision", "rentabilidad_objetivo", "rentabilidad_proveedor"].forEach(function (id) {
            $(id).addEventListener("change", cargar);
        });
        ["rentabilidad_accion", "rentabilidad_stock", "rentabilidad_origen_costo"].forEach(function (id) {
            $(id).addEventListener("change", cargar);
        });
        $("rentabilidad_items").addEventListener("click", function (event) {
            var botonComparar = event.target.closest("[data-comparar-sku]");
            var botonDetalle = event.target.closest("[data-detalle-sku]");
            if (botonComparar) { compararSku(botonComparar.getAttribute("data-comparar-sku")); }
            if (botonDetalle) { detalleSku(botonDetalle.getAttribute("data-detalle-sku")); }
        });
        cargarEscenarios().then(cargar).catch(function (error) {
            Swal.fire({text: error.message, icon: "error", confirmButtonText: "Aceptar"});
        });
    });
})();
